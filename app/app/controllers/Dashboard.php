<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Models\Domain;
use Altum\Response;

defined('ALTUMCODE') || die();

class Dashboard extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Custom code: FC-2026-02-24: FCC core education notice */
        $needs_fcc_education = $this->user->type == 0 && !\Altum\Authentication::is_fcc_core_completed();
        /* /Custom code: FC-2026-02-24 */

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['is_enabled', 'type'], ['url', 'location_url'], ['link_id', 'last_datetime', 'datetime', 'clicks', 'url']));
        $filters->set_default_order_by($this->user->preferences->links_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = \Altum\Cache::cache_function_result('links_total?user_id=' . $this->user->user_id, null, function() {
            return db()->where('user_id', $this->user->user_id)->getValue('links', 'count(*)');
        });
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('links?' . $filters->get_get() . '&page=%d')));

        /* Get domains */
        $domains = (new Domain())->get_available_domains_by_user($this->user);

        /* Get the links list for the project */
        /* Custom code */
        $links_result = database()->query("
            SELECT 
                *
            FROM 
                `links`
            LEFT JOIN `users_biolinks` ON `links`.`link_id` = `users_biolinks`.`biolink_id`
            LEFT JOIN `users_vcards` ON `links`.`link_id` = `users_vcards`.`vcard_id`
            WHERE 
                `links`.`user_id` = {$this->user->user_id}
            {$filters->get_sql_order_by()}
            {$paginator->get_sql_limit()}
        ");
        /* /Custom code */

        /* Iterate over the links */
        $links = [];

        if(!$links_result) {
            error_log('Dashboard links query failed: ' . database()->error);
            Alerts::add_error('Database update required. Run /update to apply schema changes.');
        } else {
            while($row = $links_result->fetch_object()) {
                $row->full_url = $row->domain_id && isset($domains[$row->domain_id]) ? $domains[$row->domain_id]->scheme . $domains[$row->domain_id]->host . '/' . ($domains[$row->domain_id]->link_id == $row->link_id ? null : $row->url) : SITE_URL . $row->url;

                /* Static links need the / for proper asset pathing */
                if($row->type == 'static') {
                    $row->full_url .= '/';
                }

                $row->settings = json_decode($row->settings);

                $links[] = $row;
            }
        }

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Delete Modal */
        $view = new \Altum\View('links/link_delete_modal', (array) $this);
        \Altum\Event::add_content($view->run(), 'modals');

        /* Create Link Modal */
        $domains = (new Domain())->get_available_domains_by_user($this->user);
        $data = [
            'domains' => $domains
        ];

        $view = new \Altum\View('links/create_link_modals', (array) $this);
        \Altum\Event::add_content($view->run($data), 'modals');

        /* Existing projects */
        $projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);

        /* Prepare the Links View */
        $data = [
            'links'             => $links,
            'pagination'        => $pagination,
            'filters'           => $filters,
            'projects'          => $projects,
            'links_types'       => require APP_PATH . 'includes/links_types.php',
        ];
        $view = new \Altum\View('links/links_content', (array) $this);
        $this->add_view_content('links_content', $view->run($data));

        /* Prepare the view */
        $data = [
            'has_links' => count($links),
            /* Custom code: FC-2026-02-24: FCC core education notice */
            'needs_fcc_education' => $needs_fcc_education,
            /* /Custom code: FC-2026-02-24 */
        ];

        $view = new \Altum\View('dashboard/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function get_stats_ajax() {

        session_write_close();

        \Altum\Authentication::guard();

        if($_SERVER['REQUEST_METHOD'] !== 'GET') {
            throw_404();
        }

        /* Custom code: FC-2026-03-05: dashboard demo mode toggle */
        $dashboard_demo_mode = isset($_GET['demo']) && $_GET['demo'] == '1';
        /* /Custom code: FC-2026-03-05 */

        $start_date_query = (new \DateTime())->modify('-' . (settings()->main->chart_days ?? 30) . ' day')->format('Y-m-d');
        $end_date_query = (new \DateTime())->modify('+1 day')->format('Y-m-d');

        $convert_tz_sql = get_convert_tz_sql('`datetime`', $this->user->timezone);

        $track_links_result_query = "
                SELECT
                    COUNT(`id`) AS `pageviews`,
                    SUM(`is_unique`) AS `visitors`,
                    DATE_FORMAT({$convert_tz_sql}, '%Y-%m-%d') AS `formatted_date`
                FROM
                    `track_links`
                WHERE   
                    `user_id` = {$this->user->user_id} 
                    AND ({$convert_tz_sql} BETWEEN '{$start_date_query}' AND '{$end_date_query}')
                GROUP BY
                    `formatted_date`
                ORDER BY
                    `formatted_date`
            ";

        $links_chart = \Altum\Cache::cache_function_result('track_links?user_id=' . $this->user->user_id, 'user_id=' . $this->user->user_id, function() use ($track_links_result_query) {
            $links_chart = [];

            $track_links_result = database()->query($track_links_result_query);

            /* Generate the raw chart data and save logs for later usage */
            while($row = $track_links_result->fetch_object()) {
                $label = \Altum\Date::get($row->formatted_date, 5, \Altum\Date::$default_timezone);

                $links_chart[$label] = [
                    'pageviews' => $row->pageviews,
                    'visitors' => $row->visitors
                ];
            }

            return $links_chart;
        }, 60 * 60 * settings()->main->chart_cache ?? 12);

        $links_chart = get_chart_data($links_chart);

        /* Widgets stats */
        if(settings()->links->shortener_is_enabled) {
            $link_links_total = \Altum\Cache::cache_function_result('link_links_total?user_id=' . $this->user->user_id, 'user_id=' . $this->user->user_id, function() {
                return db()->where('user_id', $this->user->user_id)->where('type', 'link')->getValue('links', 'count(*)');
            });
        }

        if(settings()->links->files_is_enabled) {
            $file_links_total = \Altum\Cache::cache_function_result('file_links_total?user_id=' . $this->user->user_id, 'user_id=' . $this->user->user_id, function() {
                return db()->where('user_id', $this->user->user_id)->where('type', 'file')->getValue('links', 'count(*)');
            });
        }

        if(settings()->links->vcards_is_enabled) {
            $vcard_links_total = \Altum\Cache::cache_function_result('vcard_links_total?user_id=' . $this->user->user_id, 'user_id=' . $this->user->user_id, function() {
                return db()->where('user_id', $this->user->user_id)->where('type', 'vcard')->getValue('links', 'count(*)');
            });
        }

        if(settings()->links->biolinks_is_enabled) {
            $biolink_links_total = \Altum\Cache::cache_function_result('biolink_links_total?user_id=' . $this->user->user_id, 'user_id=' . $this->user->user_id, function() {
                return db()->where('user_id', $this->user->user_id)->where('type', 'biolink')->getValue('links', 'count(*)');
            });
        }

        if(settings()->links->events_is_enabled) {
            $event_links_total = \Altum\Cache::cache_function_result('event_links_total?user_id=' . $this->user->user_id, null, function() {
                return db()->where('user_id', $this->user->user_id)->where('type', 'event')->getValue('links', 'count(*)');
            });
        }

        if(settings()->links->static_is_enabled) {
            $static_links_total = \Altum\Cache::cache_function_result('static_links_total?user_id=' . $this->user->user_id, null, function() {
                return db()->where('user_id', $this->user->user_id)->where('type', 'static')->getValue('links', 'count(*)');
            });
        }

        /* Custom code: FC-2026-03-05: dashboard productivity analytics and action center */
        $thirty_days_start_datetime = (new \DateTime())->modify('-29 days')->format('Y-m-d 00:00:00');
        $previous_thirty_days_start_datetime = (new \DateTime())->modify('-59 days')->format('Y-m-d 00:00:00');

        $forever_shop_block_types = [
            'link_forever_shop',
            'link_forever_living_bih',
            'link_forever_living_alb_kosovo',
            'link_forever_living_albania_kosovo',
        ];
        $forever_shop_block_types_sql = "'" . implode("','", $forever_shop_block_types) . "'";

        $track_clicks_total = (int) db()->where('user_id', $this->user->user_id)->getValue('track_links', 'COUNT(`id`)');
        $track_clicks_unique_total = (int) (db()->where('user_id', $this->user->user_id)->getValue('track_links', 'SUM(`is_unique`)') ?? 0);
        $track_clicks_30d = (int) db()->where('user_id', $this->user->user_id)->where('datetime', $thirty_days_start_datetime, '>=')->getValue('track_links', 'COUNT(`id`)');
        $track_clicks_unique_30d = (int) (db()->where('user_id', $this->user->user_id)->where('datetime', $thirty_days_start_datetime, '>=')->getValue('track_links', 'SUM(`is_unique`)') ?? 0);

        $biolink_visits_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `links`.`type` = 'biolink'")->fetch_object()->total;

        $biolink_visits_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' AND `links`.`type` = 'biolink'")->fetch_object()->total;

        $forever_shop_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql})")->fetch_object()->total;
        $forever_shop_clicks_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql})")->fetch_object()->total;
        $forever_registration_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `biolinks_blocks`.`type` = 'link_discount'")->fetch_object()->total;
        $forever_registration_clicks_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' AND `biolinks_blocks`.`type` = 'link_discount'")->fetch_object()->total;

        $revenue_total = (float) (db()->where('user_id', $this->user->user_id)->where('status', 'paid')->getValue('payments', 'SUM(`total_amount_default_currency`)') ?? 0);
        $revenue_30d = (float) (db()->where('user_id', $this->user->user_id)->where('status', 'paid')->where('datetime', $thirty_days_start_datetime, '>=')->getValue('payments', 'SUM(`total_amount_default_currency`)') ?? 0);

        $shop_ctr_30d = $biolink_visits_30d > 0 ? round(($forever_shop_clicks_30d / $biolink_visits_30d) * 100, 1) : 0;
        $shop_ctr_prev_30d = $biolink_visits_prev_30d > 0 ? round(($forever_shop_clicks_prev_30d / $biolink_visits_prev_30d) * 100, 1) : 0;
        $registration_ctr_30d = $forever_shop_clicks_30d > 0 ? round(($forever_registration_clicks_30d / $forever_shop_clicks_30d) * 100, 1) : 0;
        $registration_ctr_prev_30d = $forever_shop_clicks_prev_30d > 0 ? round(($forever_registration_clicks_prev_30d / $forever_shop_clicks_prev_30d) * 100, 1) : 0;

        $calculate_delta_percent = function($current, $previous) {
            if($previous > 0) {
                return round((($current - $previous) / $previous) * 100, 1);
            }

            return $current > 0 ? 100.0 : 0.0;
        };

        $biolink_visits_delta_percent = $calculate_delta_percent($biolink_visits_30d, $biolink_visits_prev_30d);
        $shop_clicks_delta_percent = $calculate_delta_percent($forever_shop_clicks_30d, $forever_shop_clicks_prev_30d);
        $shop_ctr_delta_points = round($shop_ctr_30d - $shop_ctr_prev_30d, 1);
        $registration_clicks_delta_percent = $calculate_delta_percent($forever_registration_clicks_30d, $forever_registration_clicks_prev_30d);
        $registration_ctr_delta_points = round($registration_ctr_30d - $registration_ctr_prev_30d, 1);

        $team_active_partners_30d = (int) database()->query("SELECT COUNT(DISTINCT `track_links`.`user_id`) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `users`.`type` = 0")->fetch_object()->total;
        $team_shop_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `users`.`type` = 0 AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql})")->fetch_object()->total;
        $team_shop_clicks_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' AND `users`.`type` = 0 AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql})")->fetch_object()->total;
        $team_registration_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `users`.`type` = 0 AND `biolinks_blocks`.`type` = 'link_discount'")->fetch_object()->total;
        $team_registration_clicks_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' AND `users`.`type` = 0 AND `biolinks_blocks`.`type` = 'link_discount'")->fetch_object()->total;
        $team_biolink_visits_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `users`.`type` = 0 AND `links`.`type` = 'biolink'")->fetch_object()->total;

        $team_avg_shop_clicks_30d = $team_active_partners_30d > 0 ? round($team_shop_clicks_30d / $team_active_partners_30d, 1) : 0;
        $team_avg_shop_ctr_30d = $team_biolink_visits_30d > 0 ? round(($team_shop_clicks_30d / $team_biolink_visits_30d) * 100, 1) : 0;
        $team_shop_trend_delta_percent = $calculate_delta_percent($team_shop_clicks_30d, $team_shop_clicks_prev_30d);
        $team_avg_registration_clicks_30d = $team_active_partners_30d > 0 ? round($team_registration_clicks_30d / $team_active_partners_30d, 1) : 0;
        $team_avg_registration_ctr_30d = $team_shop_clicks_30d > 0 ? round(($team_registration_clicks_30d / $team_shop_clicks_30d) * 100, 1) : 0;
        $team_registration_trend_delta_percent = $calculate_delta_percent($team_registration_clicks_30d, $team_registration_clicks_prev_30d);

        $shop_clicks_good_threshold = $team_avg_shop_clicks_30d > 0 ? max(8, (int) round($team_avg_shop_clicks_30d * 1.1)) : 30;
        $shop_clicks_warning_threshold = $team_avg_shop_clicks_30d > 0 ? max(3, (int) round($team_avg_shop_clicks_30d * 0.6)) : 10;
        $shop_ctr_good_threshold = $team_avg_shop_ctr_30d > 0 ? max(15.0, round($team_avg_shop_ctr_30d * 1.1, 1)) : 25.0;
        $shop_ctr_warning_threshold = $team_avg_shop_ctr_30d > 0 ? max(7.0, round($team_avg_shop_ctr_30d * 0.7, 1)) : 12.0;
        $shop_trend_good_threshold = round($team_shop_trend_delta_percent + 5, 1);
        $shop_trend_warning_threshold = round($team_shop_trend_delta_percent - 5, 1);
        $registration_clicks_good_threshold = $team_avg_registration_clicks_30d > 0 ? max(3, (int) round($team_avg_registration_clicks_30d * 1.1)) : 8;
        $registration_clicks_warning_threshold = $team_avg_registration_clicks_30d > 0 ? max(1, (int) round($team_avg_registration_clicks_30d * 0.6)) : 3;
        $registration_ctr_good_threshold = $team_avg_registration_ctr_30d > 0 ? max(8.0, round($team_avg_registration_ctr_30d * 1.1, 1)) : 18.0;
        $registration_ctr_warning_threshold = $team_avg_registration_ctr_30d > 0 ? max(3.0, round($team_avg_registration_ctr_30d * 0.7, 1)) : 7.0;

        $shop_clicks_status = $forever_shop_clicks_30d >= $shop_clicks_good_threshold ? 'good' : ($forever_shop_clicks_30d >= $shop_clicks_warning_threshold ? 'warning' : 'danger');
        $shop_ctr_status = $shop_ctr_30d >= $shop_ctr_good_threshold ? 'good' : ($shop_ctr_30d >= $shop_ctr_warning_threshold ? 'warning' : 'danger');
        $shop_trend_status = $shop_clicks_delta_percent >= $shop_trend_good_threshold ? 'good' : ($shop_clicks_delta_percent >= $shop_trend_warning_threshold ? 'warning' : 'danger');
        $registration_clicks_status = $forever_registration_clicks_30d >= $registration_clicks_good_threshold ? 'good' : ($forever_registration_clicks_30d >= $registration_clicks_warning_threshold ? 'warning' : 'danger');
        $registration_ctr_status = $registration_ctr_30d >= $registration_ctr_good_threshold ? 'good' : ($registration_ctr_30d >= $registration_ctr_warning_threshold ? 'warning' : 'danger');

        $benchmark_note = sprintf(
            l('dashboard.forever_analytics.benchmark_note'),
            $team_active_partners_30d,
            (int) round($team_avg_shop_clicks_30d),
            $team_avg_shop_ctr_30d
        );

        $dashboard_recommendations = [];

        if($biolink_visits_30d == 0) {
            $dashboard_recommendations[] = [
                'status' => 'danger',
                'title' => l('dashboard.forever_analytics.recommendation.share_more.title'),
                'description' => l('dashboard.forever_analytics.recommendation.share_more.description'),
                'cta_label' => l('dashboard.forever_analytics.recommendation.share_more.cta'),
                'cta_url' => url('links?type=biolink'),
            ];
        }

        if($biolink_visits_30d > 0 && $forever_shop_clicks_30d == 0) {
            $dashboard_recommendations[] = [
                'status' => 'danger',
                'title' => l('dashboard.forever_analytics.recommendation.zero_shop_clicks.title'),
                'description' => l('dashboard.forever_analytics.recommendation.zero_shop_clicks.description'),
                'cta_label' => l('dashboard.forever_analytics.recommendation.zero_shop_clicks.cta'),
                'cta_url' => url('links?type=biolink'),
            ];
        }

        if($forever_shop_clicks_30d > 0 && $forever_registration_clicks_30d == 0) {
            $dashboard_recommendations[] = [
                'status' => 'danger',
                'title' => l('dashboard.forever_analytics.recommendation.zero_registrations.title'),
                'description' => l('dashboard.forever_analytics.recommendation.zero_registrations.description'),
                'cta_label' => l('dashboard.forever_analytics.recommendation.zero_registrations.cta'),
                'cta_url' => url('links?type=biolink'),
            ];
        }

        if($shop_ctr_30d > 0 && $shop_ctr_30d < $shop_ctr_warning_threshold) {
            $dashboard_recommendations[] = [
                'status' => 'warning',
                'title' => l('dashboard.forever_analytics.recommendation.low_ctr.title'),
                'description' => sprintf(l('dashboard.forever_analytics.recommendation.low_ctr.description'), $shop_ctr_30d),
                'cta_label' => l('dashboard.forever_analytics.recommendation.low_ctr.cta'),
                'cta_url' => url('links?type=biolink'),
            ];
        }

        if($shop_clicks_delta_percent < $shop_trend_warning_threshold) {
            $dashboard_recommendations[] = [
                'status' => 'warning',
                'title' => l('dashboard.forever_analytics.recommendation.falling_trend.title'),
                'description' => sprintf(l('dashboard.forever_analytics.recommendation.falling_trend.description'), abs($shop_clicks_delta_percent)),
                'cta_label' => l('dashboard.forever_analytics.recommendation.falling_trend.cta'),
                'cta_url' => url('links?type=biolink'),
            ];
        }

        if($registration_ctr_30d > 0 && $registration_ctr_30d < $registration_ctr_warning_threshold) {
            $dashboard_recommendations[] = [
                'status' => 'warning',
                'title' => l('dashboard.forever_analytics.recommendation.low_registration_ctr.title'),
                'description' => sprintf(l('dashboard.forever_analytics.recommendation.low_registration_ctr.description'), $registration_ctr_30d),
                'cta_label' => l('dashboard.forever_analytics.recommendation.low_registration_ctr.cta'),
                'cta_url' => url('links?type=biolink'),
            ];
        }

        $top_countries_30d = [];
        $top_countries_30d_result = database()->query("SELECT `track_links`.`country_code`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) AND `track_links`.`country_code` IS NOT NULL AND `track_links`.`country_code` != '' GROUP BY `track_links`.`country_code` ORDER BY `total` DESC LIMIT 15");
        while($country_row = $top_countries_30d_result->fetch_object()) {
            $top_countries_30d[] = [
                'country_code' => (string) ($country_row->country_code ?? ''),
                'total' => (int) ($country_row->total ?? 0),
            ];
        }

        $top_forever_pages_30d = [];
        $top_forever_pages_30d_result = database()->query("SELECT `track_links`.`link_id`, `links`.`url`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `track_links`.`link_id` ORDER BY `total` DESC LIMIT 15");
        while($top_forever_page = $top_forever_pages_30d_result->fetch_object()) {
            $top_forever_pages_30d[] = [
                'link_id' => (int) ($top_forever_page->link_id ?? 0),
                'url' => (string) ($top_forever_page->url ?? ''),
                'total' => (int) ($top_forever_page->total ?? 0),
            ];
        }

        $source_label_sql = "CASE WHEN `track_links`.`utm_source` IS NOT NULL AND `track_links`.`utm_source` != '' THEN CONCAT('utm:', `track_links`.`utm_source`) WHEN `track_links`.`referrer_host` IS NOT NULL AND `track_links`.`referrer_host` != '' THEN `track_links`.`referrer_host` ELSE '(direct)' END";

        $top_shop_sources_30d = [];
        $top_shop_sources_30d_result = database()->query("SELECT {$source_label_sql} AS `source`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `source` ORDER BY `total` DESC LIMIT 15");
        while($source_row = $top_shop_sources_30d_result->fetch_object()) {
            $top_shop_sources_30d[] = [
                'source' => (string) ($source_row->source ?? '(direct)'),
                'total' => (int) ($source_row->total ?? 0),
            ];
        }

        $top_registration_sources_30d = [];
        $top_registration_sources_30d_result = database()->query("SELECT {$source_label_sql} AS `source`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' AND `biolinks_blocks`.`type` = 'link_discount' GROUP BY `source` ORDER BY `total` DESC LIMIT 15");
        while($source_row = $top_registration_sources_30d_result->fetch_object()) {
            $top_registration_sources_30d[] = [
                'source' => (string) ($source_row->source ?? '(direct)'),
                'total' => (int) ($source_row->total ?? 0),
            ];
        }

        $top_country = $top_countries_30d[0] ?? null;
        if($top_country && !empty($top_country['country_code'])) {
            $dashboard_recommendations[] = [
                'status' => 'good',
                'title' => l('dashboard.forever_analytics.recommendation.top_country.title'),
                'description' => sprintf(l('dashboard.forever_analytics.recommendation.top_country.description'), $top_country['country_code'], $top_country['total']),
                'cta_label' => l('dashboard.forever_analytics.recommendation.top_country.cta'),
                'cta_url' => url('links?type=biolink'),
            ];
        }

        $top_forever_page = $top_forever_pages_30d[0] ?? null;
        if($top_forever_page && !empty($top_forever_page['link_id'])) {
            $dashboard_recommendations[] = [
                'status' => 'good',
                'title' => l('dashboard.forever_analytics.recommendation.top_page.title'),
                'description' => sprintf(l('dashboard.forever_analytics.recommendation.top_page.description'), $top_forever_page['url'] ?: '-', $top_forever_page['total']),
                'cta_label' => l('dashboard.forever_analytics.recommendation.top_page.cta'),
                'cta_url' => url('link/' . $top_forever_page['link_id'] . '?tab=blocks'),
            ];
        }

        if(empty($dashboard_recommendations)) {
            $dashboard_recommendations[] = [
                'status' => 'good',
                'title' => l('dashboard.forever_analytics.recommendation.keep_going.title'),
                'description' => l('dashboard.forever_analytics.recommendation.keep_going.description'),
                'cta_label' => l('dashboard.forever_analytics.recommendation.keep_going.cta'),
                'cta_url' => url('links?type=biolink'),
            ];
        }

        $dashboard_recommendations = array_slice($dashboard_recommendations, 0, 3);

        $current_plan_name = (string) $this->user->plan_id;
        if($plan = (new \Altum\Models\Plan())->get_plan_by_id($this->user->plan_id)) {
            $current_plan_name = (string) ($plan->name ?? $current_plan_name);
        }

        $package_active_until = $this->user->plan_expiration_date ? \Altum\Date::get($this->user->plan_expiration_date, 2) : l('global.na');

        $dashboard_forever_analytics = [
            'track_clicks_total' => $track_clicks_total,
            'track_clicks_unique_total' => $track_clicks_unique_total,
            'track_clicks_30d' => $track_clicks_30d,
            'track_clicks_unique_30d' => $track_clicks_unique_30d,
            'biolink_visits_30d' => $biolink_visits_30d,
            'biolink_visits_prev_30d' => $biolink_visits_prev_30d,
            'biolink_visits_delta_percent' => $biolink_visits_delta_percent,
            'forever_shop_clicks_30d' => $forever_shop_clicks_30d,
            'forever_shop_clicks_prev_30d' => $forever_shop_clicks_prev_30d,
            'forever_registration_clicks_30d' => $forever_registration_clicks_30d,
            'forever_registration_clicks_prev_30d' => $forever_registration_clicks_prev_30d,
            'shop_clicks_delta_percent' => $shop_clicks_delta_percent,
            'shop_ctr_30d' => $shop_ctr_30d,
            'shop_ctr_prev_30d' => $shop_ctr_prev_30d,
            'shop_ctr_delta_points' => $shop_ctr_delta_points,
            'registration_clicks_delta_percent' => $registration_clicks_delta_percent,
            'registration_ctr_30d' => $registration_ctr_30d,
            'registration_ctr_prev_30d' => $registration_ctr_prev_30d,
            'registration_ctr_delta_points' => $registration_ctr_delta_points,
            'status_shop_clicks' => $shop_clicks_status,
            'status_shop_ctr' => $shop_ctr_status,
            'status_shop_trend' => $shop_trend_status,
            'status_registration_clicks' => $registration_clicks_status,
            'status_registration_ctr' => $registration_ctr_status,
            'benchmark_note' => $benchmark_note,
            'recommendations' => $dashboard_recommendations,
            'current_package_name' => $current_plan_name,
            'package_active_until' => $package_active_until,
            'revenue_total' => round($revenue_total, 2),
            'revenue_30d' => round($revenue_30d, 2),
            'top_countries_30d' => $top_countries_30d,
            'top_forever_pages_30d' => $top_forever_pages_30d,
            'top_shop_sources_30d' => $top_shop_sources_30d,
            'top_registration_sources_30d' => $top_registration_sources_30d,
        ];

        /* Custom code: FC-2026-03-05: synthetic demo traffic preview */
        if($dashboard_demo_mode) {
            $links_chart = [
                'is_empty' => false,
                'labels' => json_encode(['T-6', 'T-5', 'T-4', 'T-3', 'T-2', 'T-1', 'Danas']),
                'pageviews' => json_encode([34, 41, 38, 52, 49, 63, 71]),
                'visitors' => json_encode([23, 27, 25, 33, 31, 42, 46]),
            ];

            $dashboard_forever_analytics = [
                'track_clicks_total' => 1874,
                'track_clicks_unique_total' => 1189,
                'track_clicks_30d' => 642,
                'track_clicks_unique_30d' => 403,
                'biolink_visits_30d' => 312,
                'biolink_visits_prev_30d' => 248,
                'biolink_visits_delta_percent' => 25.8,
                'forever_shop_clicks_30d' => 88,
                'forever_shop_clicks_prev_30d' => 61,
                'forever_registration_clicks_30d' => 19,
                'forever_registration_clicks_prev_30d' => 12,
                'shop_clicks_delta_percent' => 44.3,
                'shop_ctr_30d' => 28.2,
                'shop_ctr_prev_30d' => 24.6,
                'shop_ctr_delta_points' => 3.6,
                'registration_clicks_delta_percent' => 58.3,
                'registration_ctr_30d' => 21.6,
                'registration_ctr_prev_30d' => 19.7,
                'registration_ctr_delta_points' => 1.9,
                'status_shop_clicks' => 'good',
                'status_shop_ctr' => 'good',
                'status_shop_trend' => 'good',
                'status_registration_clicks' => 'good',
                'status_registration_ctr' => 'good',
                'benchmark_note' => sprintf(l('dashboard.forever_analytics.benchmark_note'), 87, 46, 19.4),
                'recommendations' => [
                    [
                        'status' => 'good',
                        'title' => l('dashboard.forever_analytics.recommendation.top_page.title'),
                        'description' => sprintf(l('dashboard.forever_analytics.recommendation.top_page.description'), 'moja-forever-kartica', 37),
                        'cta_label' => l('dashboard.forever_analytics.recommendation.top_page.cta'),
                        'cta_url' => url('links?type=biolink'),
                    ],
                    [
                        'status' => 'good',
                        'title' => l('dashboard.forever_analytics.recommendation.top_country.title'),
                        'description' => sprintf(l('dashboard.forever_analytics.recommendation.top_country.description'), 'HR', 42),
                        'cta_label' => l('dashboard.forever_analytics.recommendation.top_country.cta'),
                        'cta_url' => url('links?type=biolink'),
                    ],
                    [
                        'status' => 'warning',
                        'title' => l('dashboard.forever_analytics.recommendation.low_ctr.title'),
                        'description' => sprintf(l('dashboard.forever_analytics.recommendation.low_ctr.description'), 28.2),
                        'cta_label' => l('dashboard.forever_analytics.recommendation.low_ctr.cta'),
                        'cta_url' => url('links?type=biolink'),
                    ],
                ],
                'current_package_name' => $current_plan_name,
                'package_active_until' => $package_active_until,
                'revenue_total' => round($revenue_total, 2),
                'revenue_30d' => round($revenue_30d, 2),
                'top_countries_30d' => [
                    ['country_code' => 'HR', 'total' => 42],
                    ['country_code' => 'BA', 'total' => 21],
                    ['country_code' => 'AL', 'total' => 15],
                    ['country_code' => 'RS', 'total' => 8],
                ],
                'top_forever_pages_30d' => [
                    ['link_id' => 0, 'url' => 'moja-forever-kartica', 'total' => 37],
                    ['link_id' => 0, 'url' => 'detox-forever', 'total' => 24],
                    ['link_id' => 0, 'url' => 'aloe-proizvodi', 'total' => 19],
                ],
                'top_shop_sources_30d' => [
                    ['source' => 'utm:instagram', 'total' => 29],
                    ['source' => 'facebook.com', 'total' => 24],
                    ['source' => 'utm:whatsapp', 'total' => 19],
                    ['source' => '(direct)', 'total' => 16],
                ],
                'top_registration_sources_30d' => [
                    ['source' => 'utm:instagram', 'total' => 8],
                    ['source' => 'facebook.com', 'total' => 6],
                    ['source' => 'utm:whatsapp', 'total' => 4],
                    ['source' => '(direct)', 'total' => 1],
                ],
            ];
        }
        /* /Custom code: FC-2026-03-05 */
        /* /Custom code: FC-2026-03-05 */

        /* Prepare the data */
        $data = [
            'links_chart' => $links_chart,

            /* Widgets */
            'link_links_total' => $link_links_total ?? null,
            'file_links_total' => $file_links_total ?? null,
            'vcard_links_total' => $vcard_links_total ?? null,
            'biolink_links_total' => $biolink_links_total ?? null,
            'event_links_total' => $event_links_total ?? null,
            'static_links_total' => $static_links_total ?? null,

            /* Custom code: FC-2026-03-05: detailed forever dashboard data */
            'dashboard_forever_analytics' => $dashboard_forever_analytics,
            /* /Custom code: FC-2026-03-05 */
        ];

        /* Set a nice success message */
        Response::json('', 'success', $data);

    }

}
