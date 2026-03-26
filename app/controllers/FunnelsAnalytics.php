<?php

namespace Altum\Controllers;

use Altum\Alerts;

defined('ALTUMCODE') || die();

class FunnelsAnalytics extends Controller {

    private function get_selected_period_bounds(int $days): array {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->user->timezone));

        return [
            'start' => $now->modify('-' . ($days - 1) . ' days')->format('Y-m-d 00:00:00'),
            'end' => $now->format('Y-m-d 23:59:59'),
        ];
    }

    public function index() {

        if(!settings()->links->biolinks_is_enabled) {
            throw_404();
        }

        \Altum\Authentication::guard();

        /* Custom code: FC-2026-03-23: sync funnels analytics access with lead funnel plan availability */
        $enabled_biolink_blocks = (object) ($this->user->plan_settings->enabled_biolink_blocks ?? []);
        $has_lead_funnel_access = (bool) ($enabled_biolink_blocks->lead_funnel ?? false);

        if(!$has_lead_funnel_access) {
            Alerts::add_error(l('global.info_message.plan_feature_no_access') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
            redirect('links');
        }
        /* /Custom code: FC-2026-03-23 */

        if(!$this->user->plan_settings->statistics) {
            Alerts::add_error(l('global.info_message.plan_feature_no_access') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
            redirect('links');
        }

        $_GET['project_id'] = isset($_GET['project_id']) ? (int) $_GET['project_id'] : null;
        $_GET['link_id'] = isset($_GET['link_id']) ? (int) $_GET['link_id'] : null;
        $_GET['biolink_block_id'] = isset($_GET['biolink_block_id']) ? (int) $_GET['biolink_block_id'] : null;
        $_GET['summary_window'] = isset($_GET['summary_window']) && in_array((int) $_GET['summary_window'], [7, 30, 90]) ? (int) $_GET['summary_window'] : 30;

        $datetime = \Altum\Date::get_start_end_dates_new();

        $projects = [];
        $projects_result = database()->query("SELECT DISTINCT `projects`.`project_id`, `projects`.`name` FROM `biolinks_blocks` LEFT JOIN `links` ON `links`.`link_id` = `biolinks_blocks`.`link_id` LEFT JOIN `projects` ON `projects`.`project_id` = `links`.`project_id` WHERE `biolinks_blocks`.`user_id` = {$this->user->user_id} AND `biolinks_blocks`.`type` = 'lead_funnel' AND `links`.`type` = 'biolink' AND `links`.`project_id` IS NOT NULL ORDER BY `projects`.`name` ASC");
        while($row = $projects_result->fetch_object()) {
            $projects[] = $row;
        }

        $biolinks = [];
        $biolinks_result = database()->query("SELECT DISTINCT `links`.`link_id`, `links`.`url`, `links`.`project_id` FROM `biolinks_blocks` LEFT JOIN `links` ON `links`.`link_id` = `biolinks_blocks`.`link_id` WHERE `biolinks_blocks`.`user_id` = {$this->user->user_id} AND `biolinks_blocks`.`type` = 'lead_funnel' AND `links`.`type` = 'biolink' ORDER BY `links`.`url` ASC");
        while($row = $biolinks_result->fetch_object()) {
            $biolinks[] = $row;
        }

        $context_filters_sql = '';

        if($_GET['project_id']) {
            $context_filters_sql .= " AND `links`.`project_id` = {$_GET['project_id']}";
        }

        if($_GET['link_id']) {
            $context_filters_sql .= " AND `links`.`link_id` = {$_GET['link_id']}";
        }

        $filters_sql = $context_filters_sql;

        if($_GET['biolink_block_id']) {
            $filters_sql .= " AND `biolinks_blocks`.`biolink_block_id` = {$_GET['biolink_block_id']}";
        }

        $funnel_blocks = [];
        $funnel_ids = [];
        $funnel_options = [];

        $funnel_options_result = database()->query("
            SELECT
                `biolinks_blocks`.`biolink_block_id`,
                `biolinks_blocks`.`settings`,
                `links`.`url` AS `biolink_url`
            FROM
                `biolinks_blocks`
            LEFT JOIN
                `links` ON `links`.`link_id` = `biolinks_blocks`.`link_id`
            WHERE
                `biolinks_blocks`.`user_id` = {$this->user->user_id}
                AND `biolinks_blocks`.`type` = 'lead_funnel'
                AND `links`.`type` = 'biolink'
                {$context_filters_sql}
            ORDER BY
                `links`.`url` ASC,
                `biolinks_blocks`.`order` ASC
        ");

        while($row = $funnel_options_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $funnel_options[] = (object) [
                'biolink_block_id' => $row->biolink_block_id,
                'name' => $row->settings->name ?? l('link.biolink.blocks.lead_funnel'),
                'biolink_url' => $row->biolink_url,
            ];
        }

        $funnel_blocks_result = database()->query("
            SELECT
                `biolinks_blocks`.`biolink_block_id`,
                `biolinks_blocks`.`link_id`,
                `biolinks_blocks`.`settings`,
                `biolinks_blocks`.`order`,
                `biolinks_blocks`.`datetime`,
                `links`.`url` AS `biolink_url`,
                `links`.`domain_id`,
                `links`.`project_id`,
                `projects`.`name` AS `project_name`
            FROM
                `biolinks_blocks`
            LEFT JOIN
                `links` ON `links`.`link_id` = `biolinks_blocks`.`link_id`
            LEFT JOIN
                `projects` ON `projects`.`project_id` = `links`.`project_id`
            WHERE
                `biolinks_blocks`.`user_id` = {$this->user->user_id}
                AND `biolinks_blocks`.`type` = 'lead_funnel'
                AND `links`.`type` = 'biolink'
                {$filters_sql}
            ORDER BY
                `links`.`url` ASC,
                `biolinks_blocks`.`order` ASC
        ");

        while($row = $funnel_blocks_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $row->name = $row->settings->name ?? l('link.biolink.blocks.lead_funnel');
            $row->open_mode = in_array(($row->settings->open_mode ?? 'popup'), ['popup', 'page'], true) ? $row->settings->open_mode : 'popup';
            $row->thank_you_type = in_array(($row->settings->thank_you_type ?? 'message'), ['message', 'external_url', 'biolink_redirect', 'file_download'], true) ? $row->settings->thank_you_type : 'message';
            $row->edit_url = url('link/' . $row->link_id . '?tab=blocks&biolink_block_id=' . $row->biolink_block_id);
            $row->data_url = url('data?type=lead_funnel&biolink_block_id=' . $row->biolink_block_id);
            $row->analytics_url = url('funnels-analytics?biolink_block_id=' . $row->biolink_block_id);
            $row->full_biolink_url = url($row->biolink_url, null, $row->domain_id);
            $row->total_clicks = 0;
            $row->unique_clicks = 0;
            $row->total_leads = 0;
            $row->conversion_rate = 0;
            $row->last_submission = null;

            $funnel_blocks[$row->biolink_block_id] = $row;
            $funnel_ids[] = (int) $row->biolink_block_id;
        }

        $summary = [
            'total_funnels' => count($funnel_blocks),
            'active_funnels' => 0,
            'total_clicks' => 0,
            'unique_clicks' => 0,
            'total_leads' => 0,
            'conversion_rate' => 0,
            'last_submission' => null,
        ];

        $sources = [];
        $active_biolinks = [];
        $device_totals = [
            'desktop' => 0,
            'mobile' => 0,
            'tablet' => 0,
        ];
        $recent_submissions = [];
        $lead_contacts_preview = [];
        $pagination = null;
        $period_summaries = [];
        $selected_period_clicks = [];
        $selected_period_leads = [];
        $flow_summary = [
            'entry_points' => 0,
            'page_views' => 0,
            'popup_opens' => 0,
            'form_starts' => 0,
            'submit_attempts' => 0,
            'submit_success' => 0,
            'submit_errors' => 0,
            'thank_you_views' => 0,
            'cta_clicks' => 0,
            'entry_to_start_rate' => null,
            'start_to_success_rate' => null,
            'success_to_cta_rate' => null,
            'weakest_stage' => null,
        ];
        $type_performance = [
            'best_open_mode' => null,
            'best_thank_you_type' => null,
        ];
        $top_funnels = [];
        $top_conversion_funnels = [];
        $chart = [
            'labels' => '[]',
            'leads' => '[]',
            'unique_clicks' => '[]',
        ];

        if($funnel_ids) {
            $funnel_ids_sql = implode(',', $funnel_ids);
            $track_links_convert_tz_sql = get_convert_tz_sql('`track_links`.`datetime`', $this->user->timezone);
            $data_convert_tz_sql = get_convert_tz_sql('`data`.`datetime`', $this->user->timezone);

            $clicks_summary = database()->query("SELECT COUNT(*) AS `total_clicks`, SUM(`is_unique`) AS `unique_clicks` FROM `track_links` WHERE `user_id` = {$this->user->user_id} AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$track_links_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')")->fetch_object();
            $leads_summary = database()->query("SELECT COUNT(*) AS `total_leads`, MAX(`datetime`) AS `last_submission` FROM `data` WHERE `user_id` = {$this->user->user_id} AND `type` = 'lead_funnel' AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$data_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')")->fetch_object();

            $summary['total_clicks'] = (int) ($clicks_summary->total_clicks ?? 0);
            $summary['unique_clicks'] = (int) ($clicks_summary->unique_clicks ?? 0);
            $summary['total_leads'] = (int) ($leads_summary->total_leads ?? 0);
            $summary['last_submission'] = $leads_summary->last_submission ?? null;
            $summary['conversion_rate'] = $summary['unique_clicks'] ? round(($summary['total_leads'] / $summary['unique_clicks']) * 100, 1) : 0;

            $clicks_per_funnel = [];
            $clicks_per_funnel_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total_clicks`, SUM(`is_unique`) AS `unique_clicks` FROM `track_links` WHERE `user_id` = {$this->user->user_id} AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$track_links_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') GROUP BY `biolink_block_id`");
            while($row = $clicks_per_funnel_result->fetch_object()) {
                $clicks_per_funnel[$row->biolink_block_id] = $row;
            }

            $leads_per_funnel = [];
            $leads_per_funnel_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total_leads`, MAX(`datetime`) AS `last_submission` FROM `data` WHERE `user_id` = {$this->user->user_id} AND `type` = 'lead_funnel' AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$data_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') GROUP BY `biolink_block_id`");
            while($row = $leads_per_funnel_result->fetch_object()) {
                $leads_per_funnel[$row->biolink_block_id] = $row;
            }

            /* Custom code: FC-2026-03-23: aggregate funnel activity and biolink editor drilldowns */
            foreach($funnel_blocks as $biolink_block_id => $row) {
                $row->total_clicks = (int) ($clicks_per_funnel[$biolink_block_id]->total_clicks ?? 0);
                $row->unique_clicks = (int) ($clicks_per_funnel[$biolink_block_id]->unique_clicks ?? 0);
                $row->total_leads = (int) ($leads_per_funnel[$biolink_block_id]->total_leads ?? 0);
                $row->last_submission = $leads_per_funnel[$biolink_block_id]->last_submission ?? null;
                $row->conversion_rate = $row->unique_clicks ? round(($row->total_leads / $row->unique_clicks) * 100, 1) : 0;

                if($row->total_clicks || $row->total_leads) {
                    $summary['active_funnels']++;

                    if(!isset($active_biolinks[$row->link_id])) {
                        $active_biolinks[$row->link_id] = (object) [
                            'link_id' => $row->link_id,
                            'biolink_url' => $row->biolink_url,
                            'project_name' => $row->project_name,
                            'editor_url' => url('link/' . $row->link_id . '?tab=blocks'),
                            'analytics_url' => url('funnels-analytics?link_id=' . $row->link_id),
                            'active_funnels' => 0,
                            'total_leads' => 0,
                        ];
                    }

                    $active_biolinks[$row->link_id]->active_funnels++;
                    $active_biolinks[$row->link_id]->total_leads += $row->total_leads;
                }
            }
            /* /Custom code: FC-2026-03-23 */

            $chart_data = [];

            $leads_chart_result = database()->query("SELECT DATE_FORMAT({$data_convert_tz_sql}, '{$datetime['query_date_format']}') AS `formatted_date`, COUNT(*) AS `total` FROM `data` WHERE `user_id` = {$this->user->user_id} AND `type` = 'lead_funnel' AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$data_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') GROUP BY `formatted_date` ORDER BY `formatted_date`");
            while($row = $leads_chart_result->fetch_object()) {
                $row->formatted_date = $datetime['process']($row->formatted_date, true);
                $chart_data[$row->formatted_date]['leads'] = (int) $row->total;
            }

            $clicks_chart_result = database()->query("SELECT DATE_FORMAT({$track_links_convert_tz_sql}, '{$datetime['query_date_format']}') AS `formatted_date`, SUM(`is_unique`) AS `total` FROM `track_links` WHERE `user_id` = {$this->user->user_id} AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$track_links_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') GROUP BY `formatted_date` ORDER BY `formatted_date`");
            while($row = $clicks_chart_result->fetch_object()) {
                $row->formatted_date = $datetime['process']($row->formatted_date, true);
                $chart_data[$row->formatted_date]['unique_clicks'] = (int) $row->total;
            }

            if($chart_data) {
                $chart = get_chart_data($chart_data);
            }

            $sources_result = database()->query("SELECT COALESCE(NULLIF(`referrer_host`, ''), 'direct') AS `source`, COUNT(*) AS `total` FROM `track_links` WHERE `user_id` = {$this->user->user_id} AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$track_links_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') GROUP BY `source` ORDER BY `total` DESC LIMIT 6");
            while($row = $sources_result->fetch_object()) {
                $sources[] = $row;
            }

            $device_result = database()->query("SELECT `device_type`, COUNT(*) AS `total` FROM `track_links` WHERE `user_id` = {$this->user->user_id} AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$track_links_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') GROUP BY `device_type`");
            while($row = $device_result->fetch_object()) {
                if(isset($device_totals[$row->device_type])) {
                    $device_totals[$row->device_type] = (int) $row->total;
                }
            }

            /* Custom code: FC-2026-03-23: fixed 7 30 90 day summary cards */
            $selected_summary_window = (int) ($_GET['summary_window'] ?? 30);

            foreach([7, 30, 90] as $period_days) {
                $period_bounds = $this->get_selected_period_bounds($period_days);
                $period_start = $period_bounds['start'];
                $period_end = $period_bounds['end'];

                $period_clicks_summary = database()->query("SELECT SUM(`is_unique`) AS `unique_clicks` FROM `track_links` WHERE `user_id` = {$this->user->user_id} AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$track_links_convert_tz_sql} BETWEEN '{$period_start}' AND '{$period_end}')")->fetch_object();
                $period_leads_summary = database()->query("SELECT COUNT(*) AS `total_leads` FROM `data` WHERE `user_id` = {$this->user->user_id} AND `type` = 'lead_funnel' AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$data_convert_tz_sql} BETWEEN '{$period_start}' AND '{$period_end}')")->fetch_object();

                $period_active_clicks = [];
                $period_active_clicks_result = database()->query("SELECT `biolink_block_id`, SUM(`is_unique`) AS `unique_clicks` FROM `track_links` WHERE `user_id` = {$this->user->user_id} AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$track_links_convert_tz_sql} BETWEEN '{$period_start}' AND '{$period_end}') GROUP BY `biolink_block_id`");
                while($row = $period_active_clicks_result->fetch_object()) {
                    $period_active_clicks[$row->biolink_block_id] = (int) $row->unique_clicks;
                }

                $period_active_leads = [];
                $period_active_leads_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total_leads` FROM `data` WHERE `user_id` = {$this->user->user_id} AND `type` = 'lead_funnel' AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$data_convert_tz_sql} BETWEEN '{$period_start}' AND '{$period_end}') GROUP BY `biolink_block_id`");
                while($row = $period_active_leads_result->fetch_object()) {
                    $period_active_leads[$row->biolink_block_id] = (int) $row->total_leads;
                }

                if($period_days === $selected_summary_window) {
                    $selected_period_clicks = $period_active_clicks;
                    $selected_period_leads = $period_active_leads;
                }

                $period_active_funnels = 0;
                foreach($funnel_ids as $funnel_id) {
                    if(($period_active_clicks[$funnel_id] ?? 0) || ($period_active_leads[$funnel_id] ?? 0)) {
                        $period_active_funnels++;
                    }
                }

                $period_unique_clicks = (int) ($period_clicks_summary->unique_clicks ?? 0);
                $period_total_leads = (int) ($period_leads_summary->total_leads ?? 0);

                $period_summaries[$period_days] = [
                    'days' => $period_days,
                    'total_funnels' => count($funnel_blocks),
                    'active_funnels' => $period_active_funnels,
                    'unique_clicks' => $period_unique_clicks,
                    'total_leads' => $period_total_leads,
                    'conversion_rate' => $period_unique_clicks ? round(($period_total_leads / $period_unique_clicks) * 100, 1) : 0,
                ];
            }
            /* /Custom code: FC-2026-03-23 */

            $open_mode_breakdown = [];
            foreach(['popup', 'page'] as $open_mode_key) {
                $open_mode_breakdown[$open_mode_key] = [
                    'type' => $open_mode_key,
                    'funnels_count' => 0,
                    'unique_clicks' => 0,
                    'total_leads' => 0,
                    'conversion_rate' => 0,
                ];
            }

            $thank_you_type_breakdown = [];
            foreach(['message', 'external_url', 'biolink_redirect', 'file_download'] as $thank_you_type_key) {
                $thank_you_type_breakdown[$thank_you_type_key] = [
                    'type' => $thank_you_type_key,
                    'funnels_count' => 0,
                    'unique_clicks' => 0,
                    'total_leads' => 0,
                    'conversion_rate' => 0,
                ];
            }

            foreach($funnel_blocks as $biolink_block_id => $row) {
                $row->selected_unique_clicks = (int) ($selected_period_clicks[$biolink_block_id] ?? 0);
                $row->selected_total_leads = (int) ($selected_period_leads[$biolink_block_id] ?? 0);
                $row->selected_conversion_rate = $row->selected_unique_clicks ? round(($row->selected_total_leads / $row->selected_unique_clicks) * 100, 1) : 0;

                $open_mode_breakdown[$row->open_mode]['funnels_count']++;
                $open_mode_breakdown[$row->open_mode]['unique_clicks'] += $row->selected_unique_clicks;
                $open_mode_breakdown[$row->open_mode]['total_leads'] += $row->selected_total_leads;

                $thank_you_type_breakdown[$row->thank_you_type]['funnels_count']++;
                $thank_you_type_breakdown[$row->thank_you_type]['unique_clicks'] += $row->selected_unique_clicks;
                $thank_you_type_breakdown[$row->thank_you_type]['total_leads'] += $row->selected_total_leads;
            }

            foreach($open_mode_breakdown as &$breakdown_row) {
                $breakdown_row['conversion_rate'] = $breakdown_row['unique_clicks'] ? round(($breakdown_row['total_leads'] / $breakdown_row['unique_clicks']) * 100, 1) : 0;
            }
            unset($breakdown_row);

            foreach($thank_you_type_breakdown as &$breakdown_row) {
                $breakdown_row['conversion_rate'] = $breakdown_row['unique_clicks'] ? round(($breakdown_row['total_leads'] / $breakdown_row['unique_clicks']) * 100, 1) : 0;
            }
            unset($breakdown_row);

            $open_mode_ranked = array_values(array_filter($open_mode_breakdown, static function($row) {
                return $row['unique_clicks'] > 0 || $row['total_leads'] > 0;
            }));
            usort($open_mode_ranked, static function($a, $b) {
                return [$b['conversion_rate'], $b['total_leads'], $b['unique_clicks']] <=> [$a['conversion_rate'], $a['total_leads'], $a['unique_clicks']];
            });

            $thank_you_type_ranked = array_values(array_filter($thank_you_type_breakdown, static function($row) {
                return $row['unique_clicks'] > 0 || $row['total_leads'] > 0;
            }));
            usort($thank_you_type_ranked, static function($a, $b) {
                return [$b['conversion_rate'], $b['total_leads'], $b['unique_clicks']] <=> [$a['conversion_rate'], $a['total_leads'], $a['unique_clicks']];
            });

            $type_performance['best_open_mode'] = $open_mode_ranked[0] ?? null;
            $type_performance['best_thank_you_type'] = $thank_you_type_ranked[0] ?? null;

            $top_funnels = array_values($funnel_blocks);
            usort($top_funnels, static function($a, $b) {
                return [$b->selected_total_leads, $b->selected_unique_clicks, $b->selected_conversion_rate] <=> [$a->selected_total_leads, $a->selected_unique_clicks, $a->selected_conversion_rate];
            });
            $top_funnels = array_slice($top_funnels, 0, 3);

            $top_conversion_funnels = array_values(array_filter($funnel_blocks, static function($row) {
                return $row->selected_unique_clicks >= 10;
            }));
            usort($top_conversion_funnels, static function($a, $b) {
                return [$b->selected_conversion_rate, $b->selected_total_leads, $b->selected_unique_clicks] <=> [$a->selected_conversion_rate, $a->selected_total_leads, $a->selected_unique_clicks];
            });
            $top_conversion_funnels = array_slice($top_conversion_funnels, 0, 3);

            if(function_exists('fc_ensure_funnel_analytics_tables')) {
                fc_ensure_funnel_analytics_tables();
            }

            $selected_period_bounds = $this->get_selected_period_bounds($selected_summary_window);
            $funnel_events_convert_tz_sql = get_convert_tz_sql('`datetime`', $this->user->timezone);
            $funnel_events = [];
            $funnel_events_result = database()->query("SELECT `event_type`, COUNT(*) AS `total` FROM `funnel_events` WHERE `user_id` = {$this->user->user_id} AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$funnel_events_convert_tz_sql} BETWEEN '{$selected_period_bounds['start']}' AND '{$selected_period_bounds['end']}') GROUP BY `event_type`");
            while($row = $funnel_events_result->fetch_object()) {
                $funnel_events[$row->event_type] = (int) $row->total;
            }

            $flow_summary['page_views'] = (int) ($funnel_events['view'] ?? 0);
            $flow_summary['popup_opens'] = (int) ($funnel_events['open'] ?? 0);
            $flow_summary['entry_points'] = $flow_summary['page_views'] + $flow_summary['popup_opens'];
            if(!$flow_summary['entry_points']) {
                $flow_summary['entry_points'] = (int) ($period_summaries[$selected_summary_window]['unique_clicks'] ?? 0);
            }

            $flow_summary['submit_attempts'] = (int) ($funnel_events['submit_attempt'] ?? 0);
            $flow_summary['form_starts'] = (int) ($funnel_events['form_start'] ?? 0);
            if(!$flow_summary['form_starts']) {
                $flow_summary['form_starts'] = $flow_summary['submit_attempts'];
            }

            $flow_summary['submit_success'] = (int) ($funnel_events['submit_success'] ?? 0);
            if(!$flow_summary['submit_success']) {
                $flow_summary['submit_success'] = (int) ($period_summaries[$selected_summary_window]['total_leads'] ?? 0);
            }

            $flow_summary['submit_errors'] = (int) ($funnel_events['submit_error'] ?? 0);
            $flow_summary['thank_you_views'] = (int) ($funnel_events['thank_you_view'] ?? 0);
            if(!$flow_summary['thank_you_views']) {
                $flow_summary['thank_you_views'] = $flow_summary['submit_success'];
            }
            $flow_summary['cta_clicks'] = (int) ($funnel_events['thank_you_cta_click'] ?? 0);

            $flow_summary['entry_to_start_rate'] = $flow_summary['entry_points'] ? round(($flow_summary['form_starts'] / $flow_summary['entry_points']) * 100, 1) : null;
            $flow_summary['start_to_success_rate'] = $flow_summary['form_starts'] ? round(($flow_summary['submit_success'] / $flow_summary['form_starts']) * 100, 1) : null;
            $flow_summary['success_to_cta_rate'] = $flow_summary['submit_success'] ? round(($flow_summary['cta_clicks'] / $flow_summary['submit_success']) * 100, 1) : null;

            $flow_rates = array_filter([
                'entry_to_start' => $flow_summary['entry_to_start_rate'],
                'start_to_success' => $flow_summary['start_to_success_rate'],
                'success_to_cta' => $flow_summary['cta_clicks'] > 0 ? $flow_summary['success_to_cta_rate'] : null,
            ], static function($value) {
                return $value !== null;
            });

            if($flow_rates) {
                asort($flow_rates, SORT_NUMERIC);
                $flow_summary['weakest_stage'] = array_key_first($flow_rates);
            }

            /* Custom code: FC-2026-03-23: paginated recent submissions and contact modal actions */
            $pagination_url_parameters = [];
            foreach(['project_id', 'link_id', 'biolink_block_id', 'start_date', 'end_date'] as $parameter) {
                if(isset($_GET[$parameter]) && $_GET[$parameter] !== '') {
                    $pagination_url_parameters[$parameter] = $_GET[$parameter];
                }
            }

            $total_recent_submissions = database()->query("SELECT COUNT(*) AS `total` FROM `data` WHERE `user_id` = {$this->user->user_id} AND `type` = 'lead_funnel' AND `biolink_block_id` IN ({$funnel_ids_sql}) AND ({$data_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')")->fetch_object()->total ?? 0;
            $pagination_base = 'funnels-analytics?' . http_build_query($pagination_url_parameters);
            $pagination_base .= $pagination_url_parameters ? '&page=%d' : 'page=%d';
            $paginator = new \Altum\Paginator($total_recent_submissions, 5, $_GET['page'] ?? 1, url($pagination_base));

            $submission_rows_processor = function($result) {
                $rows = [];

                while($row = $result->fetch_object()) {
                    $row->data = json_decode($row->data ?? '');
                    $row->settings = json_decode($row->settings ?? '');
                    $row->funnel_name = $row->settings->name ?? l('link.biolink.blocks.lead_funnel');
                    $row->data_url = url('data?datum_id=' . $row->datum_id);

                    $first_name = trim((string) ($row->data->first_name ?? ''));
                    $last_name = trim((string) ($row->data->last_name ?? ''));
                    $full_name = trim((string) ($row->data->name ?? $row->data->full_name ?? trim($first_name . ' ' . $last_name)));
                    $email = trim((string) ($row->data->email ?? ''));
                    $phone = trim((string) ($row->data->phone_e164 ?? $row->data->phone ?? $row->data->whatsapp ?? $row->data->mobile ?? ''));
                    $message = "Zahvaljujem se što ste ostavili kontakt podatke. Da li Vam odgovara da se čujemo pozivom ovdje\n\nS poštovanjem {$this->user->name}";
                    $phone_for_whatsapp = preg_replace('/[^0-9]/', '', $phone);
                    $phone_for_direct = preg_replace('/[^0-9\+]/', '', $phone);

                    $identity = [];
                    foreach([$full_name, $email, $phone] as $field_value) {
                        if(!empty($field_value)) {
                            $identity[] = $field_value;
                        }
                    }

                    $row->contact_name = $full_name ?: l('funnels_analytics.empty_submission');
                    $row->contact_email = $email;
                    $row->contact_phone = $phone;
                    $row->preferred_contact_channel = trim((string) ($row->data->preferred_contact_channel ?? ''));
                    $row->identity = implode(' • ', array_slice($identity, 0, 2)) ?: l('funnels_analytics.empty_submission');
                    $row->whatsapp_url = $phone_for_whatsapp ? 'https://wa.me/' . $phone_for_whatsapp . '?text=' . rawurlencode($message) : null;
                    $row->viber_url = $phone_for_direct ? 'viber://chat?number=' . rawurlencode($phone_for_direct) : null;
                    $row->sms_url = $phone_for_direct ? 'sms:' . $phone_for_direct . '?body=' . rawurlencode($message) : null;
                    $row->call_url = $phone_for_direct ? 'tel:' . $phone_for_direct : null;
                    $row->email_url = $email ? 'mailto:' . $email . '?subject=' . rawurlencode('Upit preko FCC aplikacije') . '&body=' . rawurlencode($message) : null;

                    $action_meta = [
                        'whatsapp_url' => ['label' => 'WhatsApp', 'icon' => 'fab fa-whatsapp', 'class' => 'is-whatsapp'],
                        'viber_url' => ['label' => 'Viber', 'icon' => 'fas fa-comment-dots', 'class' => 'is-viber'],
                        'sms_url' => ['label' => 'SMS', 'icon' => 'fas fa-sms', 'class' => 'is-sms'],
                        'call_url' => ['label' => 'Nazovi', 'icon' => 'fas fa-phone-alt', 'class' => 'is-call'],
                        'email_url' => ['label' => 'Email', 'icon' => 'fas fa-envelope', 'class' => 'is-email'],
                    ];
                    $preferred_order = [
                        'whatsapp' => ['whatsapp_url', 'sms_url', 'call_url', 'email_url'],
                        'viber' => ['viber_url', 'whatsapp_url', 'sms_url', 'call_url', 'email_url'],
                        'sms' => ['sms_url', 'whatsapp_url', 'call_url', 'email_url'],
                        'phone' => ['call_url', 'whatsapp_url', 'sms_url', 'email_url'],
                        'email' => ['email_url', 'whatsapp_url', 'sms_url', 'call_url'],
                    ][$row->preferred_contact_channel ?: 'whatsapp'] ?? ['whatsapp_url', 'sms_url', 'call_url', 'email_url'];

                    $row->primary_action = null;
                    foreach($preferred_order as $action_key) {
                        if(empty($row->{$action_key})) {
                            continue;
                        }

                        $row->primary_action = array_merge(['key' => $action_key, 'url' => $row->{$action_key}], $action_meta[$action_key]);
                        break;
                    }

                    $row->available_actions = [];
                    foreach($action_meta as $action_key => $meta) {
                        if(empty($row->{$action_key})) {
                            continue;
                        }

                        $row->available_actions[] = array_merge(['key' => $action_key, 'url' => $row->{$action_key}], $meta);
                    }
                    $row->contact_status = $row->primary_action ? 'ready' : 'needs_review';
                    $row->contact_status_label = $row->primary_action ? 'Spreman za ' . $row->primary_action['label'] : 'Ručno provjeri kontakt';

                    $rows[] = $row;
                }

                return $rows;
            };

            $recent_submissions_result = database()->query("SELECT `data`.`datum_id`, `data`.`biolink_block_id`, `data`.`data`, `data`.`datetime`, `biolinks_blocks`.`settings`, `links`.`url` AS `biolink_url` FROM `data` LEFT JOIN `biolinks_blocks` ON `biolinks_blocks`.`biolink_block_id` = `data`.`biolink_block_id` LEFT JOIN `links` ON `links`.`link_id` = `data`.`link_id` WHERE `data`.`user_id` = {$this->user->user_id} AND `data`.`type` = 'lead_funnel' AND `data`.`biolink_block_id` IN ({$funnel_ids_sql}) AND ({$data_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') ORDER BY `data`.`datetime` DESC {$paginator->get_sql_limit()}");
            $recent_submissions = $submission_rows_processor($recent_submissions_result);

            $lead_contacts_preview_result = database()->query("SELECT `data`.`datum_id`, `data`.`biolink_block_id`, `data`.`data`, `data`.`datetime`, `biolinks_blocks`.`settings`, `links`.`url` AS `biolink_url` FROM `data` LEFT JOIN `biolinks_blocks` ON `biolinks_blocks`.`biolink_block_id` = `data`.`biolink_block_id` LEFT JOIN `links` ON `links`.`link_id` = `data`.`link_id` WHERE `data`.`user_id` = {$this->user->user_id} AND `data`.`type` = 'lead_funnel' AND `data`.`biolink_block_id` IN ({$funnel_ids_sql}) AND ({$data_convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') ORDER BY `data`.`datetime` DESC LIMIT 25");
            $lead_contacts_preview = $submission_rows_processor($lead_contacts_preview_result);

            $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);
            /* /Custom code: FC-2026-03-23 */
        }

        $funnel_blocks = array_values($funnel_blocks);
        usort($funnel_blocks, function($a, $b) {
            return [$b->total_leads, $b->unique_clicks, strtotime($b->last_submission ?? '1970-01-01 00:00:00')] <=> [$a->total_leads, $a->unique_clicks, strtotime($a->last_submission ?? '1970-01-01 00:00:00')];
        });

        $active_biolinks = array_values($active_biolinks);
        usort($active_biolinks, function($a, $b) {
            return [$b->active_funnels, $b->total_leads] <=> [$a->active_funnels, $a->total_leads];
        });

        $view = new \Altum\View('funnels-analytics/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'projects' => $projects,
            'biolinks' => $biolinks,
            'funnel_options' => $funnel_options,
            'funnel_blocks' => $funnel_blocks,
            'summary' => $summary,
            'sources' => $sources,
            'active_biolinks' => $active_biolinks,
            'device_totals' => $device_totals,
            'device_total_clicks' => array_sum($device_totals),
            'recent_submissions' => $recent_submissions,
            'lead_contacts_preview' => $lead_contacts_preview,
            'pagination' => $pagination,
            'period_summaries' => $period_summaries,
            'selected_period_summary' => $period_summaries[$_GET['summary_window']] ?? null,
            'selected_period_days' => (int) ($_GET['summary_window'] ?? 30),
            'flow_summary' => $flow_summary,
            'type_performance' => $type_performance,
            'top_funnels' => $top_funnels,
            'top_conversion_funnels' => $top_conversion_funnels,
            'datetime' => $datetime,
            'has_funnels' => !empty($funnel_blocks),
            'chart' => $chart,
        ]));
    }
}
