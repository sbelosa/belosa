<?php

namespace Altum\Controllers;

use Altum\Alerts;

defined('ALTUMCODE') || die();

class FunnelsAnalytics extends Controller {

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
            $row->open_mode = $row->settings->open_mode ?? 'popup';
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
            $now = new \DateTimeImmutable('now', new \DateTimeZone($this->user->timezone));

            foreach([7, 30, 90] as $period_days) {
                $period_start = $now->modify('-' . ($period_days - 1) . ' days')->format('Y-m-d 00:00:00');
                $period_end = $now->format('Y-m-d 23:59:59');

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
            $paginator = new \Altum\Paginator($total_recent_submissions, 10, $_GET['page'] ?? 1, url($pagination_base));

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
                    $phone = trim((string) ($row->data->phone ?? $row->data->whatsapp ?? $row->data->mobile ?? ''));
                    $phone_for_whatsapp = preg_replace('/[^0-9]/', '', $phone);
                    $phone_for_sms = preg_replace('/[^0-9\+]/', '', $phone);
                    $message = "Zahvaljujem se što ste ostavili kontakt podatke. Da li Vam odgovara da se čujemo pozivom ovdje\n\nS poštovanjem {$this->user->name}";

                    $identity = [];
                    foreach([$full_name, $email, $phone] as $field_value) {
                        if(!empty($field_value)) {
                            $identity[] = $field_value;
                        }
                    }

                    $row->contact_name = $full_name ?: l('funnels_analytics.empty_submission');
                    $row->contact_email = $email;
                    $row->contact_phone = $phone;
                    $row->identity = implode(' • ', array_slice($identity, 0, 2)) ?: l('funnels_analytics.empty_submission');
                    $row->whatsapp_url = $phone_for_whatsapp ? 'https://wa.me/' . $phone_for_whatsapp . '?text=' . rawurlencode($message) : null;
                    $row->sms_url = $phone_for_sms ? 'sms:' . $phone_for_sms . '?body=' . rawurlencode($message) : null;

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
            'datetime' => $datetime,
            'has_funnels' => !empty($funnel_blocks),
            'chart' => $chart,
        ]));
    }
}