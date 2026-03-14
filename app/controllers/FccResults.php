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

    /* Custom code: FC-2026-03-14: FCC results page and qualification metrics */
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

        $forever_shop_block_types = [
            'link_discount',
            'link_forever_living_bih',
            'link_forever_living_alb_kosovo',
            'link_forever_living_albania_kosovo',
        ];
        $forever_shop_block_types_sql = "'" . implode("','", $forever_shop_block_types) . "'";

        $periods = [];

        foreach($allowed_periods as $period_key => $period_days) {
            $period_start_datetime = $this->get_period_start_datetime($period_days);
            $period_previous_start_datetime = $this->get_previous_period_start_datetime($period_days);

            $previous_clicks_map = [];
            $previous_clicks_result = database()->query("SELECT `track_links`.`user_id`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$period_previous_start_datetime}' AND `track_links`.`datetime` < '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `track_links`.`user_id`");
            while($previous_click_row = $previous_clicks_result->fetch_object()) {
                $previous_clicks_map[(int) $previous_click_row->user_id] = (int) $previous_click_row->total;
            }

            $leaderboard = [];
            $leaderboard_result = database()->query("SELECT `track_links`.`user_id`, `users`.`name`, SUM(CASE WHEN `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `shop_clicks`, SUM(CASE WHEN `links`.`type` = 'biolink' AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `biolink_visits` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' AND `users`.`type` = 0 GROUP BY `track_links`.`user_id` HAVING `shop_clicks` >= {$min_qualified_clicks} ORDER BY `shop_clicks` DESC, `biolink_visits` DESC, `users`.`name` ASC");

            $rank = 1;
            while($leaderboard_row = $leaderboard_result->fetch_object()) {
                $user_id = (int) ($leaderboard_row->user_id ?? 0);
                $shop_clicks = (int) ($leaderboard_row->shop_clicks ?? 0);
                $biolink_visits = (int) ($leaderboard_row->biolink_visits ?? 0);
                $previous_shop_clicks = (int) ($previous_clicks_map[$user_id] ?? 0);
                $trend_percent = $this->get_trend_percent($shop_clicks, $previous_shop_clicks);

                $leaderboard[] = [
                    'rank' => $rank,
                    'user_id' => $user_id,
                    'name' => (string) ($leaderboard_row->name ?? l('global.unknown')),
                    'shop_clicks' => $shop_clicks,
                    'biolink_visits' => $biolink_visits,
                    'ctr' => $biolink_visits > 0 ? round(($shop_clicks / $biolink_visits) * 100, 2) : 0.0,
                    'previous_shop_clicks' => $previous_shop_clicks,
                    'trend_percent' => $trend_percent,
                    'is_top_three' => $rank <= 3,
                    'is_rising' => $trend_percent > 0,
                    'is_falling' => $trend_percent < 0,
                ];

                $rank++;
            }

            $current_user_totals_result = database()->query("SELECT SUM(CASE WHEN `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `shop_clicks`, SUM(CASE WHEN `links`.`type` = 'biolink' AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `biolink_visits` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$period_start_datetime}'");
            $current_user_totals = $current_user_totals_result->fetch_object();

            $current_user_shop_clicks = (int) ($current_user_totals->shop_clicks ?? 0);
            $current_user_biolink_visits = (int) ($current_user_totals->biolink_visits ?? 0);
            $current_user_previous_shop_clicks = (int) ($previous_clicks_map[$this->user->user_id] ?? 0);

            $periods[$period_key] = [
                'days' => $period_days,
                'period_start_datetime' => $period_start_datetime,
                'leaderboard' => $leaderboard,
                'qualified_total' => count($leaderboard),
                'current_user' => [
                    'shop_clicks' => $current_user_shop_clicks,
                    'biolink_visits' => $current_user_biolink_visits,
                    'ctr' => $current_user_biolink_visits > 0 ? round(($current_user_shop_clicks / $current_user_biolink_visits) * 100, 2) : 0.0,
                    'previous_shop_clicks' => $current_user_previous_shop_clicks,
                    'trend_percent' => $this->get_trend_percent($current_user_shop_clicks, $current_user_previous_shop_clicks),
                    'is_qualified' => $current_user_shop_clicks >= $min_qualified_clicks,
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
                $current_user_next_rank_clicks = (int) $selected_period_data['leaderboard'][$index - 1]['shop_clicks'];
            }
            break;
        }

        $selected_user_clicks = (int) ($selected_period_data['current_user']['shop_clicks'] ?? 0);
        $distance_to_qualification = max(0, $min_qualified_clicks - $selected_user_clicks);
        $distance_to_next_rank = null;

        if($current_user_rank && $current_user_next_rank_clicks !== null) {
            $distance_to_next_rank = max(0, $current_user_next_rank_clicks - $selected_user_clicks + 1);
        }

        $data = [
            'selected_period' => $selected_period,
            'periods' => $periods,
            'min_qualified_clicks' => $min_qualified_clicks,
            'current_user_rank' => $current_user_rank,
            'distance_to_qualification' => $distance_to_qualification,
            'distance_to_next_rank' => $distance_to_next_rank,
            'tips' => [
                l('fcc_results.tips.item_1'),
                l('fcc_results.tips.item_2'),
                l('fcc_results.tips.item_3'),
                l('fcc_results.tips.item_4'),
                l('fcc_results.tips.item_5'),
            ],
        ];

        $view = new \Altum\View('fcc-results/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }
    /* /Custom code: FC-2026-03-14 */
}
