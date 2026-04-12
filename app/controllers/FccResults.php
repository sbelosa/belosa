<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class FccResults extends Controller {

    private function get_active_pro_leaderboard_user_condition_sql(string $users_alias, string $now_datetime): string {
        return "{$users_alias}.`status` = 1
            AND LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT({$users_alias}.`plan_settings`, '$.ai_growth_plan_is_enabled')), '')) IN ('1', 'true')
            AND ({$users_alias}.`plan_expiration_date` IS NULL OR {$users_alias}.`plan_expiration_date` = '' OR {$users_alias}.`plan_expiration_date` >= '{$now_datetime}')";
    }

    private function get_visitor_conversion_map(string $period_start_datetime, string $qualified_click_condition_sql): array {
        if(function_exists('fc_ensure_track_links_visitor_key_schema')) {
            fc_ensure_track_links_visitor_key_schema();
        }

        $map = [];
        $result = database()->query("SELECT
                `visitor_rows`.`user_id`,
                SUM(CASE WHEN `visitor_rows`.`has_biolink_visit` = 1 THEN 1 ELSE 0 END) AS `biolink_visitors`,
                SUM(CASE WHEN `visitor_rows`.`has_biolink_visit` = 1 AND `visitor_rows`.`has_qualified_click` = 1 THEN 1 ELSE 0 END) AS `qualified_visitors`
            FROM (
                SELECT
                    `track_links`.`user_id`,
                    `track_links`.`visitor_key`,
                    MAX(CASE WHEN `links`.`type` = 'biolink' THEN 1 ELSE 0 END) AS `has_biolink_visit`,
                    MAX(CASE WHEN {$qualified_click_condition_sql} AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `has_qualified_click`
                FROM `track_links`
                LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
                LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`
                WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
                    AND `track_links`.`visitor_key` IS NOT NULL
                    AND `track_links`.`visitor_key` != ''
                GROUP BY `track_links`.`user_id`, `track_links`.`visitor_key`
            ) AS `visitor_rows`
            GROUP BY `visitor_rows`.`user_id`");

        while($row = $result->fetch_object()) {
            $map[(int) $row->user_id] = [
                'biolink_visitors' => (int) ($row->biolink_visitors ?? 0),
                'qualified_visitors' => (int) ($row->qualified_visitors ?? 0),
            ];
        }

        return $map;
    }

    private function get_contact_breakdown_map(string $period_start_datetime): array {
        $map = [];
        $result = database()->query("SELECT
                `user_id`,
                SUM(CASE WHEN `type` = 'lead_funnel' THEN 1 ELSE 0 END) AS `funnel_contacts`,
                SUM(CASE WHEN `type` = 'ai_chat_lead' THEN 1 ELSE 0 END) AS `ai_chat_contacts`,
                COUNT(*) AS `total`
            FROM `data`
            WHERE `type` IN ('lead_funnel', 'ai_chat_lead')
              AND `datetime` >= '{$period_start_datetime}'
            GROUP BY `user_id`");

        while($row = $result->fetch_object()) {
            $map[(int) $row->user_id] = [
                'funnel_contacts' => (int) ($row->funnel_contacts ?? 0),
                'ai_chat_contacts' => (int) ($row->ai_chat_contacts ?? 0),
                'total_contacts' => (int) ($row->total ?? 0),
            ];
        }

        return $map;
    }

    /* Custom code: FC-2026-03-14: FCC results page and qualification metrics */
    private function is_active_pro_user(): bool {
        if(\Altum\Authentication::is_admin()) {
            return true;
        }

        if(empty($this->user->plan_settings->ai_growth_plan_is_enabled ?? false)) {
            return false;
        }

        $plan_expiration_date = (string) ($this->user->plan_expiration_date ?? '');

        if($plan_expiration_date === '') {
            return true;
        }

        try {
            return (new \DateTimeImmutable($plan_expiration_date)) >= (new \DateTimeImmutable());
        } catch(\Throwable $exception) {
            return false;
        }
    }

    private function get_period_start_datetime(int $days): string {
        $days = max(1, $days);

        return (new \DateTime())->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00');
    }

    private function get_previous_period_start_datetime(int $days): string {
        $days = max(1, $days);

        return (new \DateTime())->modify('-' . (($days * 2) - 1) . ' days')->format('Y-m-d 00:00:00');
    }

    private function get_trend_percent(int $current, int $previous): float {
        if($previous <= 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function index() {

        \Altum\Authentication::guard();

        $allowed_periods = [
            '7d' => 7,
            '30d' => 30,
        ];

        $selected_period = input_clean($_GET['period'] ?? '30d', 10);
        if(!isset($allowed_periods[$selected_period])) {
            $selected_period = '30d';
        }

        $min_qualified_clicks = 15;

        if(function_exists('fc_ensure_track_links_visitor_key_schema')) {
            fc_ensure_track_links_visitor_key_schema();
        }

        $forever_shop_block_types = \Altum\Link::get_fcc_results_qualified_block_types();
        $forever_shop_block_types_sql = "'" . implode("','", $forever_shop_block_types) . "'";
        $blog_forever_mediums = \Altum\Link::get_fcc_results_qualified_blog_mediums();
        $blog_forever_mediums_sql = "'" . implode("','", $blog_forever_mediums) . "'";
        $qualified_click_condition_sql = \Altum\Link::get_fcc_results_qualified_click_condition_sql('`track_links`', '`biolinks_blocks`');
        $active_pro_user_condition_sql = $this->get_active_pro_leaderboard_user_condition_sql('`users`', get_date());

        $periods = [];

        foreach($allowed_periods as $period_key => $period_days) {
            $period_start_datetime = $this->get_period_start_datetime($period_days);
            $period_previous_start_datetime = $this->get_previous_period_start_datetime($period_days);
            $visitor_conversion_map = $this->get_visitor_conversion_map($period_start_datetime, $qualified_click_condition_sql);
            $contact_breakdown_map = $this->get_contact_breakdown_map($period_start_datetime);

            $previous_clicks_map = [];
            $previous_clicks_result = database()->query("SELECT `track_links`.`user_id`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$period_previous_start_datetime}' AND `track_links`.`datetime` < '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND {$qualified_click_condition_sql} GROUP BY `track_links`.`user_id`");
            while($previous_click_row = $previous_clicks_result->fetch_object()) {
                $previous_clicks_map[(int) $previous_click_row->user_id] = (int) $previous_click_row->total;
            }

            $leaderboard = [];
            $leaderboard_result = database()->query("SELECT `track_links`.`user_id`, `users`.`name`,
                SUM(CASE WHEN {$qualified_click_condition_sql} AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `qualified_clicks`,
                SUM(CASE WHEN `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `app_clicks`,
                SUM(CASE WHEN `track_links`.`utm_medium` IN ({$blog_forever_mediums_sql}) AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `blog_clicks`,
                SUM(CASE WHEN `links`.`type` = 'biolink' AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `biolink_visits`
                FROM `track_links`
                LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
                LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`
                LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id`
                WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
                  AND {$active_pro_user_condition_sql}
                GROUP BY `track_links`.`user_id`
                HAVING `qualified_clicks` >= {$min_qualified_clicks}
                ORDER BY `qualified_clicks` DESC, `biolink_visits` DESC, `users`.`name` ASC");

            $rank = 1;
            while($leaderboard_row = $leaderboard_result->fetch_object()) {
                $user_id = (int) ($leaderboard_row->user_id ?? 0);
                $qualified_clicks = (int) ($leaderboard_row->qualified_clicks ?? 0);
                $app_clicks = (int) ($leaderboard_row->app_clicks ?? 0);
                $blog_clicks = (int) ($leaderboard_row->blog_clicks ?? 0);
                $contact_breakdown = $contact_breakdown_map[$user_id] ?? [
                    'funnel_contacts' => 0,
                    'ai_chat_contacts' => 0,
                    'total_contacts' => 0,
                ];
                $funnel_contacts = (int) ($contact_breakdown['funnel_contacts'] ?? 0);
                $ai_chat_contacts = (int) ($contact_breakdown['ai_chat_contacts'] ?? 0);
                $total_contacts = (int) ($contact_breakdown['total_contacts'] ?? 0);
                $biolink_visits = (int) ($leaderboard_row->biolink_visits ?? 0);
                $biolink_visitors = (int) ($visitor_conversion_map[$user_id]['biolink_visitors'] ?? 0);
                $qualified_visitors = (int) ($visitor_conversion_map[$user_id]['qualified_visitors'] ?? 0);
                $previous_qualified_clicks = (int) ($previous_clicks_map[$user_id] ?? 0);
                $trend_percent = $this->get_trend_percent($qualified_clicks, $previous_qualified_clicks);

                $leaderboard[] = [
                    'rank' => $rank,
                    'user_id' => $user_id,
                    'name' => (string) ($leaderboard_row->name ?? l('global.unknown')),
                    'qualified_clicks' => $qualified_clicks,
                    'app_clicks' => $app_clicks,
                    'blog_clicks' => $blog_clicks,
                    'funnel_contacts' => $funnel_contacts,
                    'ai_chat_contacts' => $ai_chat_contacts,
                    'total_contacts' => $total_contacts,
                    'biolink_visits' => $biolink_visits,
                    'biolink_visitors' => $biolink_visitors,
                    'qualified_visitors' => $qualified_visitors,
                    'ctr' => $biolink_visitors > 0 ? round(($qualified_visitors / $biolink_visitors) * 100, 2) : null,
                    'previous_qualified_clicks' => $previous_qualified_clicks,
                    'trend_percent' => $trend_percent,
                    'is_top_three' => $rank <= 3,
                    'is_rising' => $trend_percent > 0,
                    'is_falling' => $trend_percent < 0,
                ];

                $rank++;
            }

            $current_user_totals_result = database()->query("SELECT
                SUM(CASE WHEN {$qualified_click_condition_sql} AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `qualified_clicks`,
                SUM(CASE WHEN `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `app_clicks`,
                SUM(CASE WHEN `track_links`.`utm_medium` IN ({$blog_forever_mediums_sql}) AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `blog_clicks`,
                SUM(CASE WHEN `links`.`type` = 'biolink' AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `biolink_visits`
                FROM `track_links`
                LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
                LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`
                WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$period_start_datetime}'");
            $current_user_totals = $current_user_totals_result->fetch_object();

            $current_user_qualified_clicks = (int) ($current_user_totals->qualified_clicks ?? 0);
            $current_user_app_clicks = (int) ($current_user_totals->app_clicks ?? 0);
            $current_user_blog_clicks = (int) ($current_user_totals->blog_clicks ?? 0);
            $current_user_contact_breakdown = $contact_breakdown_map[$this->user->user_id] ?? [
                'funnel_contacts' => 0,
                'ai_chat_contacts' => 0,
                'total_contacts' => 0,
            ];
            $current_user_funnel_contacts = (int) ($current_user_contact_breakdown['funnel_contacts'] ?? 0);
            $current_user_ai_chat_contacts = (int) ($current_user_contact_breakdown['ai_chat_contacts'] ?? 0);
            $current_user_total_contacts = (int) ($current_user_contact_breakdown['total_contacts'] ?? 0);
            $current_user_biolink_visits = (int) ($current_user_totals->biolink_visits ?? 0);
            $current_user_biolink_visitors = (int) ($visitor_conversion_map[$this->user->user_id]['biolink_visitors'] ?? 0);
            $current_user_qualified_visitors = (int) ($visitor_conversion_map[$this->user->user_id]['qualified_visitors'] ?? 0);
            $current_user_previous_qualified_clicks = (int) ($previous_clicks_map[$this->user->user_id] ?? 0);

            $periods[$period_key] = [
                'days' => $period_days,
                'period_start_datetime' => $period_start_datetime,
                'leaderboard' => $leaderboard,
                'qualified_total' => count($leaderboard),
                'current_user' => [
                    'qualified_clicks' => $current_user_qualified_clicks,
                    'app_clicks' => $current_user_app_clicks,
                    'blog_clicks' => $current_user_blog_clicks,
                    'funnel_contacts' => $current_user_funnel_contacts,
                    'ai_chat_contacts' => $current_user_ai_chat_contacts,
                    'total_contacts' => $current_user_total_contacts,
                    'biolink_visits' => $current_user_biolink_visits,
                    'biolink_visitors' => $current_user_biolink_visitors,
                    'qualified_visitors' => $current_user_qualified_visitors,
                    'ctr' => $current_user_biolink_visitors > 0 ? round(($current_user_qualified_visitors / $current_user_biolink_visitors) * 100, 2) : null,
                    'previous_qualified_clicks' => $current_user_previous_qualified_clicks,
                    'trend_percent' => $this->get_trend_percent($current_user_qualified_clicks, $current_user_previous_qualified_clicks),
                    'is_qualified' => $current_user_qualified_clicks >= $min_qualified_clicks,
                ],
            ];
        }

        $selected_period_data = $periods[$selected_period];

        $current_user_rank = null;
        $current_user_next_rank_clicks = null;
        foreach($selected_period_data['leaderboard'] as $index => $entry) {
            if((int) $entry['user_id'] !== (int) $this->user->user_id) {
                continue;
            }

            $current_user_rank = (int) $entry['rank'];
            if($index > 0) {
                $current_user_next_rank_clicks = (int) $selected_period_data['leaderboard'][$index - 1]['qualified_clicks'];
            }
            break;
        }

        $selected_user_clicks = (int) ($selected_period_data['current_user']['qualified_clicks'] ?? 0);
        $distance_to_qualification = max(0, $min_qualified_clicks - $selected_user_clicks);
        $distance_to_next_rank = null;

        if($current_user_rank && $current_user_next_rank_clicks !== null) {
            $distance_to_next_rank = max(0, $current_user_next_rank_clicks - $selected_user_clicks + 1);
        }

        $ai_signal_period = $periods['30d']['current_user'] ?? $selected_period_data['current_user'];
        $ai_signal_30d = (int) ($ai_signal_period['qualified_clicks'] ?? 0);
        $ai_active_threshold = 15;
        $ai_vip_threshold = 50;
        $is_pro_ai_user = $this->is_active_pro_user();
        $ai_stage = $ai_signal_30d >= $ai_vip_threshold ? 'vip' : ($ai_signal_30d >= $ai_active_threshold ? 'active' : 'starter');

        $data = [
            'selected_period' => $selected_period,
            'periods' => $periods,
            'min_qualified_clicks' => $min_qualified_clicks,
            'current_user_rank' => $current_user_rank,
            'distance_to_qualification' => $distance_to_qualification,
            'distance_to_next_rank' => $distance_to_next_rank,
            'ai_unlock' => [
                'is_pro' => $is_pro_ai_user,
                'stage' => $ai_stage,
                'signal_30d' => $ai_signal_30d,
                'app_clicks_30d' => (int) ($ai_signal_period['app_clicks'] ?? 0),
                'blog_clicks_30d' => (int) ($ai_signal_period['blog_clicks'] ?? 0),
                'active_threshold' => $ai_active_threshold,
                'vip_threshold' => $ai_vip_threshold,
                'to_active' => max(0, $ai_active_threshold - $ai_signal_30d),
                'to_vip' => max(0, $ai_vip_threshold - $ai_signal_30d),
                'active_progress_percent' => min(100, round(($ai_signal_30d / max(1, $ai_active_threshold)) * 100)),
                'vip_progress_percent' => min(100, round(($ai_signal_30d / max(1, $ai_vip_threshold)) * 100)),
            ],
            'tips' => [
                l('fcc_results.tips.item_1'),
                l('fcc_results.tips.item_2'),
                l('fcc_results.tips.item_3'),
                l('fcc_results.tips.item_4'),
                l('fcc_results.tips.item_5'),
                l('fcc_results.tips.item_6'),
                l('fcc_results.tips.item_7'),
            ],
        ];

        $view = new \Altum\View('fcc-results/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }
    /* /Custom code: FC-2026-03-14 */
}
