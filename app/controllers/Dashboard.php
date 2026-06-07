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

    /* Custom code: FC-2026-03-07: normalize and aggregate collaborator traffic sources */
    private function normalize_dashboard_traffic_source_label(string $source): string {
        $source = mb_strtolower(trim($source));

        if($source === '' || in_array($source, ['(direct)', 'direct', 'none', '(none)'], true)) {
            return '(direct)';
        }

        if(strpos($source, 'utm:') === 0) {
            $source = substr($source, 4);
        }

        if(strpos($source, 'http://') === 0 || strpos($source, 'https://') === 0) {
            $parsed_host = parse_url($source, PHP_URL_HOST);
            if(is_string($parsed_host) && $parsed_host !== '') {
                $source = $parsed_host;
            }
        }

        $source = preg_replace('/:\d+$/', '', $source);
        $source = preg_replace('/^www\./', '', $source);
        $source = preg_replace('/^m\./', '', $source);
        $source = preg_replace('/^l\./', '', $source);

        if(strpos($source, '/') !== false) {
            $source = explode('/', $source)[0];
        }

        $site_host = parse_url(SITE_URL, PHP_URL_HOST);
        if(is_string($site_host) && $site_host !== '') {
            $site_host = mb_strtolower(trim($site_host));
            $site_host = preg_replace('/^www\./', '', $site_host);

            if($source === $site_host || str_ends_with($source, '.' . $site_host)) {
                return '(direct)';
            }
        }

        if($source === 'forevercard.club' || str_ends_with($source, '.forevercard.club')) {
            return '(direct)';
        }

        if($source === '' || in_array($source, ['(direct)', 'direct', 'none', '(none)'], true)) {
            return '(direct)';
        }

        if($source === 'fb' || strpos($source, 'facebook') !== false) {
            return 'facebook';
        }

        if($source === 'ig' || strpos($source, 'instagram') !== false) {
            return 'instagram';
        }

        if(strpos($source, 'whatsapp') !== false || $source === 'wa') {
            return 'whatsapp';
        }

        if(strpos($source, 'tiktok') !== false) {
            return 'tiktok';
        }

        if(strpos($source, 'youtube') !== false || $source === 'youtu.be') {
            return 'youtube';
        }

        if(strpos($source, 'telegram') !== false) {
            return 'telegram';
        }

        if(strpos($source, 'viber') !== false) {
            return 'viber';
        }

        if(strpos($source, 'google') !== false || $source === 'gclid') {
            return 'google';
        }

        if(strpos($source, 'linkedin') !== false) {
            return 'linkedin';
        }

        return $source;
    }

    private function normalize_and_rank_dashboard_traffic_sources($result, int $limit = 15): array {
        $aggregated_sources = [];

        while($source_row = $result->fetch_object()) {
            $raw_source = (string) ($source_row->source ?? '(direct)');
            $source = $this->normalize_dashboard_traffic_source_label($raw_source);
            $total = (int) ($source_row->total ?? 0);

            if(!isset($aggregated_sources[$source])) {
                $aggregated_sources[$source] = 0;
            }

            $aggregated_sources[$source] += $total;
        }

        arsort($aggregated_sources);

        $normalized_sources = [];
        foreach(array_slice($aggregated_sources, 0, max(1, $limit), true) as $source => $total) {
            $normalized_sources[] = [
                'source' => (string) $source,
                'total' => (int) $total,
            ];
        }

        return $normalized_sources;
    }
    /* /Custom code: FC-2026-03-07 */

    public function index() {

        \Altum\Authentication::guard();

        /* Custom code: FC-2026-02-24: FCC core education notice */
        $needs_fcc_education = $this->user->type == 0 && !\Altum\Authentication::is_fcc_core_completed();
        /* /Custom code: FC-2026-02-24 */

        /* Custom code: FC-2026-04-06: dashboard onboarding should auto-open only once per user */
        $preferences = is_string($this->user->preferences ?? null) ? json_decode($this->user->preferences ?? '{}') : ($this->user->preferences ?? (object) []);
        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }
        if(!is_object($preferences)) {
            $preferences = (object) [];
        }

        $preferences_meta = $preferences->meta ?? (object) [];
        if(is_array($preferences_meta)) {
            $preferences_meta = (object) $preferences_meta;
        }
        if(!is_object($preferences_meta)) {
            $preferences_meta = (object) [];
        }

        $dashboard_onboarding_seen = !empty($preferences_meta->fcc_dashboard_onboarding_seen_at)
            || !empty($preferences_meta->fcc_dashboard_onboarding_seen)
            || !empty($preferences->fcc_dashboard_onboarding_seen_at)
            || !empty($preferences->fcc_dashboard_onboarding_seen);
        /* /Custom code: FC-2026-04-06 */

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
            /* Custom code: FC-2026-04-06: dashboard onboarding should auto-open only once per user */
            'dashboard_onboarding_seen' => $dashboard_onboarding_seen,
            'should_auto_open_dashboard_onboarding' => !$dashboard_onboarding_seen,
            /* /Custom code: FC-2026-04-06 */
        ];

        $view = new \Altum\View('dashboard/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    /* Custom code: FC-2026-04-06: persist dashboard onboarding seen state per user */
    public function mark_onboarding_seen() {
        \Altum\Authentication::guard();

        if($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw_404();
        }

        $preferences = is_string($this->user->preferences ?? null) ? json_decode($this->user->preferences ?? '{}') : ($this->user->preferences ?? (object) []);
        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }
        if(!is_object($preferences)) {
            $preferences = (object) [];
        }

        $preferences_meta = $preferences->meta ?? (object) [];
        if(is_array($preferences_meta)) {
            $preferences_meta = (object) $preferences_meta;
        }
        if(!is_object($preferences_meta)) {
            $preferences_meta = (object) [];
        }

        $already_seen = !empty($preferences_meta->fcc_dashboard_onboarding_seen_at)
            || !empty($preferences_meta->fcc_dashboard_onboarding_seen)
            || !empty($preferences->fcc_dashboard_onboarding_seen_at)
            || !empty($preferences->fcc_dashboard_onboarding_seen);

        if(!$already_seen) {
            $seen_at = get_date();
            $preferences_meta->fcc_dashboard_onboarding_seen = true;
            $preferences_meta->fcc_dashboard_onboarding_seen_at = $seen_at;
            $preferences->meta = $preferences_meta;
            $preferences->fcc_dashboard_onboarding_seen = true;
            $preferences->fcc_dashboard_onboarding_seen_at = $seen_at;

            db()->where('user_id', $this->user->user_id)->update('users', [
                'preferences' => json_encode($preferences),
            ]);

            $this->user->preferences = $preferences;
            \Altum\Authentication::$user->preferences = $preferences;

            cache()->deleteItemsByTag('user_id=' . $this->user->user_id);
            cache()->deleteItem('user?user_id=' . $this->user->user_id);
        }

        Response::json('', 'success', [
            'seen' => true,
        ]);
    }
    /* /Custom code: FC-2026-04-06 */

    public function get_stats_ajax() {
        \Altum\Authentication::guard();

        if(session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

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

        /* Custom code: FC-2026-03-08: correct forever webshop vs registration block mappings */
        $forever_shop_block_types = [
            'link_discount',
            'link_forever_webshop_reg',
            'link_forever_living_bih',
            'link_forever_living_alb_kosovo',
            'link_forever_living_albania_kosovo',
        ];
        $forever_registration_block_types = [
            'link_forever_shop',
        ];
        /* /Custom code: FC-2026-03-08 */
        $forever_shop_block_types_sql = "'" . implode("','", $forever_shop_block_types) . "'";
        $forever_registration_block_types_sql = "'" . implode("','", $forever_registration_block_types) . "'";
        $forever_shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`track_links`', '`biolinks_blocks`', $forever_shop_block_types_sql);
        $forever_registration_condition = \Altum\Link::get_forever_registration_click_condition_sql('`track_links`', '`biolinks_blocks`', $forever_registration_block_types_sql);
        $forever_outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $forever_shop_block_types_sql, $forever_registration_block_types_sql);
        $blog_product_medium = \Altum\Link::get_blog_cta_tracking_medium('product');
        $blog_business_medium = \Altum\Link::get_blog_cta_tracking_medium('business');
        $qualified_forever_block_types = array_values(array_unique(array_merge(
            \Altum\Link::get_monitored_forever_outbound_types(),
            ['link_forever_living_albania_kosovo']
        )));
        $qualified_forever_block_types_sql = "'" . implode("','", $qualified_forever_block_types) . "'";
        $qualified_blog_mediums = [$blog_product_medium, $blog_business_medium];
        $qualified_blog_mediums_sql = "'" . implode("','", $qualified_blog_mediums) . "'";
        $qualified_click_condition = "((`biolinks_blocks`.`type` IN ({$qualified_forever_block_types_sql})) OR (`track_links`.`utm_medium` IN ({$qualified_blog_mediums_sql})))";
        $unique_track_links_condition = " AND `track_links`.`is_unique` = 1";

        $track_clicks_total = (int) db()->where('user_id', $this->user->user_id)->getValue('track_links', 'COUNT(`id`)');
        $track_clicks_unique_total = (int) (db()->where('user_id', $this->user->user_id)->getValue('track_links', 'SUM(`is_unique`)') ?? 0);
        $track_clicks_30d = (int) db()->where('user_id', $this->user->user_id)->where('datetime', $thirty_days_start_datetime, '>=')->getValue('track_links', 'COUNT(`id`)');
        $track_clicks_unique_30d = (int) (db()->where('user_id', $this->user->user_id)->where('datetime', $thirty_days_start_datetime, '>=')->getValue('track_links', 'SUM(`is_unique`)') ?? 0);

        $biolink_visits_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `links`.`type` = 'biolink'")->fetch_object()->total;

        $biolink_visits_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `links`.`type` = 'biolink'")->fetch_object()->total;

        $forever_shop_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND {$forever_shop_condition}")->fetch_object()->total;
        $forever_shop_clicks_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND {$forever_shop_condition}")->fetch_object()->total;
        $forever_registration_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND {$forever_registration_condition}")->fetch_object()->total;
        $forever_registration_clicks_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND {$forever_registration_condition}")->fetch_object()->total;
        $ai_chat_leads_30d = (int) db()->where('user_id', $this->user->user_id)->where('type', 'ai_chat_lead')->where('datetime', $thirty_days_start_datetime, '>=')->getValue('data', 'COUNT(*)');
        $qualified_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`user_id` = {$this->user->user_id}
              AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}'
              {$unique_track_links_condition}
              AND {$qualified_click_condition}")->fetch_object()->total;
        $qualified_clicks_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`user_id` = {$this->user->user_id}
              AND `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}'
              AND `track_links`.`datetime` < '{$thirty_days_start_datetime}'
              {$unique_track_links_condition}
              AND {$qualified_click_condition}")->fetch_object()->total;
        $app_qualified_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`user_id` = {$this->user->user_id}
              AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}'
              {$unique_track_links_condition}
              AND `biolinks_blocks`.`type` IN ({$qualified_forever_block_types_sql})")->fetch_object()->total;
        $blog_qualified_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total`
            FROM `track_links`
            WHERE `track_links`.`user_id` = {$this->user->user_id}
              AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}'
              {$unique_track_links_condition}
              AND `track_links`.`utm_medium` IN ({$qualified_blog_mediums_sql})")->fetch_object()->total;
        $funnel_public_signal_30d = vip_funnel_get_public_qualification_signal_payload($this->user->user_id, $thirty_days_start_datetime);
        $funnel_public_signal_prev_30d = vip_funnel_get_public_qualification_signal_payload($this->user->user_id, $previous_thirty_days_start_datetime, $thirty_days_start_datetime);
        $qualified_clicks_30d += (int) ($funnel_public_signal_30d['total'] ?? 0);
        $qualified_clicks_prev_30d += (int) ($funnel_public_signal_prev_30d['total'] ?? 0);

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
        $qualified_clicks_delta_percent = $calculate_delta_percent($qualified_clicks_30d, $qualified_clicks_prev_30d);
        $shop_clicks_delta_percent = $calculate_delta_percent($forever_shop_clicks_30d, $forever_shop_clicks_prev_30d);
        $shop_ctr_delta_points = round($shop_ctr_30d - $shop_ctr_prev_30d, 1);
        $registration_clicks_delta_percent = $calculate_delta_percent($forever_registration_clicks_30d, $forever_registration_clicks_prev_30d);
        $registration_ctr_delta_points = round($registration_ctr_30d - $registration_ctr_prev_30d, 1);

        $team_active_partners_30d = (int) database()->query("SELECT COUNT(DISTINCT `track_links`.`user_id`) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `users`.`type` = 0")->fetch_object()->total;
        $team_shop_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `users`.`type` = 0 AND {$forever_shop_condition}")->fetch_object()->total;
        $team_shop_clicks_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `users`.`type` = 0 AND {$forever_shop_condition}")->fetch_object()->total;
        $team_registration_clicks_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `users`.`type` = 0 AND {$forever_registration_condition}")->fetch_object()->total;
        $team_registration_clicks_prev_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`datetime` >= '{$previous_thirty_days_start_datetime}' AND `track_links`.`datetime` < '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `users`.`type` = 0 AND {$forever_registration_condition}")->fetch_object()->total;
        $team_biolink_visits_30d = (int) database()->query("SELECT COUNT(*) AS `total` FROM `track_links` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id` WHERE `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND `users`.`type` = 0 AND `links`.`type` = 'biolink'")->fetch_object()->total;

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
        $registration_clicks_good_threshold = $team_avg_registration_clicks_30d > 0 ? max(3, (int) round($team_avg_registration_clicks_30d * 1.1)) : 8;
        $registration_clicks_warning_threshold = $team_avg_registration_clicks_30d > 0 ? max(1, (int) round($team_avg_registration_clicks_30d * 0.6)) : 3;
        $registration_ctr_good_threshold = $team_avg_registration_ctr_30d > 0 ? max(8.0, round($team_avg_registration_ctr_30d * 1.1, 1)) : 18.0;
        $registration_ctr_warning_threshold = $team_avg_registration_ctr_30d > 0 ? max(3.0, round($team_avg_registration_ctr_30d * 0.7, 1)) : 7.0;

        $shop_clicks_status = $forever_shop_clicks_30d >= $shop_clicks_good_threshold ? 'good' : ($forever_shop_clicks_30d >= $shop_clicks_warning_threshold ? 'warning' : 'danger');
        $shop_ctr_status = $shop_ctr_30d >= $shop_ctr_good_threshold ? 'good' : ($shop_ctr_30d >= $shop_ctr_warning_threshold ? 'warning' : 'danger');
        /* Custom code: FC-2026-03-30: webshop trend status follows positive-vs-team logic */
        if($shop_clicks_delta_percent < 0) {
            $shop_trend_status = 'danger';
        } elseif($shop_clicks_delta_percent >= $team_shop_trend_delta_percent) {
            $shop_trend_status = 'good';
        } else {
            $shop_trend_status = 'warning';
        }
        /* /Custom code: FC-2026-03-30 */
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

        if($shop_clicks_delta_percent < 0) {
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
        $top_countries_30d_result = database()->query("SELECT `track_links`.`country_code`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND {$forever_shop_condition} AND `track_links`.`country_code` IS NOT NULL AND `track_links`.`country_code` != '' GROUP BY `track_links`.`country_code` ORDER BY `total` DESC LIMIT 15");
        while($country_row = $top_countries_30d_result->fetch_object()) {
            $top_countries_30d[] = [
                'country_code' => (string) ($country_row->country_code ?? ''),
                'total' => (int) ($country_row->total ?? 0),
            ];
        }

        $top_forever_pages_30d = [];
        $top_forever_pages_30d_result = database()->query("SELECT
            CASE
                WHEN `track_links`.`utm_medium` IN ('{$blog_product_medium}', '{$blog_business_medium}') THEN CONCAT('blog:', COALESCE(`blog_posts`.`blog_post_id`, 0))
                ELSE CONCAT('link:', COALESCE(`biolink_links`.`link_id`, 0))
            END AS `page_key`,
            COALESCE(`biolink_links`.`link_id`, 0) AS `resolved_link_id`,
            COALESCE(`biolink_links`.`url`, `links`.`url`, '') AS `link_url`,
            `blog_posts`.`blog_post_id`,
            `blog_posts`.`title` AS `blog_post_title`,
            `blog_posts`.`url` AS `blog_post_url`,
            `track_links`.`utm_medium`,
            COUNT(*) AS `total`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`
            LEFT JOIN `links` AS `biolink_links` ON `biolinks_blocks`.`link_id` = `biolink_links`.`link_id`
            LEFT JOIN `blog_posts` ON `blog_posts`.`blog_post_id` = CAST(SUBSTRING_INDEX(`track_links`.`utm_campaign`, ':', -1) AS UNSIGNED)
            WHERE `track_links`.`user_id` = {$this->user->user_id}
              AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}'
              {$unique_track_links_condition}
              AND {$qualified_click_condition}
              AND (
                    `track_links`.`utm_medium` IN ('{$blog_product_medium}', '{$blog_business_medium}')
                    OR (`track_links`.`biolink_block_id` IS NOT NULL AND `biolink_links`.`link_id` IS NOT NULL)
              )
            GROUP BY `page_key`
            ORDER BY `total` DESC
            LIMIT 15");
        while($top_forever_page = $top_forever_pages_30d_result->fetch_object()) {
            $is_blog_article = !empty($top_forever_page->blog_post_id);
            $display_url = $is_blog_article
                ? 'blog/' . (string) ($top_forever_page->blog_post_url ?? '')
                : (string) ($top_forever_page->link_url ?? '');
            $open_url = $is_blog_article
                ? url('blog/' . (string) ($top_forever_page->blog_post_url ?? ''))
                : (!empty($top_forever_page->resolved_link_id) ? url('link/' . (int) $top_forever_page->resolved_link_id . '?tab=blocks') : '');

            $top_forever_pages_30d[] = [
                'link_id' => (int) ($top_forever_page->resolved_link_id ?? 0),
                'blog_post_id' => (int) ($top_forever_page->blog_post_id ?? 0),
                'url' => $display_url,
                'title' => $is_blog_article ? (string) ($top_forever_page->blog_post_title ?? '') : '',
                'kind' => $is_blog_article ? 'blog' : 'app',
                'open_url' => $open_url,
                'total' => (int) ($top_forever_page->total ?? 0),
            ];
        }

        $normalized_top_forever_pages_30d = [];
        foreach($top_forever_pages_30d as $entry) {
            $entry_label = trim((string) (($entry['kind'] ?? '') === 'blog' ? ($entry['title'] ?? '') : ($entry['url'] ?? '')));
            $entry_key = ($entry['kind'] ?? 'app') . '|' . mb_strtolower($entry_label !== '' ? $entry_label : (string) ($entry['link_id'] ?? 0));

            if(!isset($normalized_top_forever_pages_30d[$entry_key])) {
                $normalized_top_forever_pages_30d[$entry_key] = $entry;
                continue;
            }

            $normalized_top_forever_pages_30d[$entry_key]['total'] += (int) ($entry['total'] ?? 0);

            if(empty($normalized_top_forever_pages_30d[$entry_key]['open_url']) && !empty($entry['open_url'])) {
                $normalized_top_forever_pages_30d[$entry_key]['open_url'] = $entry['open_url'];
            }
        }

        $top_forever_pages_30d = array_values($normalized_top_forever_pages_30d);
        usort($top_forever_pages_30d, fn($a, $b) => ((int) ($b['total'] ?? 0)) <=> ((int) ($a['total'] ?? 0)));
        $top_forever_pages_30d = array_slice($top_forever_pages_30d, 0, 15);

        $top_blog_content_30d = null;
        $top_app_content_30d = null;
        foreach($top_forever_pages_30d as $entry) {
            if(!$top_blog_content_30d && ($entry['kind'] ?? '') === 'blog') {
                $top_blog_content_30d = $entry;
            }

            if(!$top_app_content_30d && ($entry['kind'] ?? '') === 'app') {
                $top_app_content_30d = $entry;
            }
        }

        $source_label_sql = "CASE WHEN `track_links`.`utm_source` IS NOT NULL AND `track_links`.`utm_source` != '' THEN CONCAT('utm:', `track_links`.`utm_source`) WHEN `track_links`.`referrer_host` IS NOT NULL AND `track_links`.`referrer_host` != '' THEN `track_links`.`referrer_host` ELSE '(direct)' END";

        /* Custom code: FC-2026-03-07: normalize source channels for collaborator dashboard cards */
        $top_shop_sources_30d_result = database()->query("SELECT {$source_label_sql} AS `source`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND {$forever_shop_condition} GROUP BY `source` ORDER BY `total` DESC LIMIT 100");
        $top_shop_sources_30d = $this->normalize_and_rank_dashboard_traffic_sources($top_shop_sources_30d_result, 15);

        $top_registration_sources_30d_result = database()->query("SELECT {$source_label_sql} AS `source`, COUNT(*) AS `total` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` WHERE `track_links`.`user_id` = {$this->user->user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} AND {$forever_registration_condition} GROUP BY `source` ORDER BY `total` DESC LIMIT 100");
        $top_registration_sources_30d = $this->normalize_and_rank_dashboard_traffic_sources($top_registration_sources_30d_result, 15);
        /* /Custom code: FC-2026-03-07 */

        $signal_chart_labels = [];
        $signal_chart_app_clicks = [];
        $signal_chart_blog_clicks = [];
        $signal_chart_registration_clicks = [];
        $signal_chart_leads = [];

        $signal_chart_start_datetime = (new \DateTimeImmutable($thirty_days_start_datetime))->setTime(0, 0, 0);
        $signal_chart_days_map = [];
        for($day_offset = 0; $day_offset < 30; $day_offset++) {
            $day_key = $signal_chart_start_datetime->modify("+{$day_offset} days")->format('Y-m-d');
            $signal_chart_days_map[$day_key] = [
                'label' => $signal_chart_start_datetime->modify("+{$day_offset} days")->format('d.m.'),
                'app_clicks' => 0,
                'blog_clicks' => 0,
                'registration_clicks' => 0,
                'leads' => 0,
            ];
        }

        $signal_chart_clicks_result = database()->query("SELECT
            DATE(`track_links`.`datetime`) AS `day`,
            SUM(CASE WHEN `biolinks_blocks`.`type` IN ({$qualified_forever_block_types_sql}) AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `app_clicks`,
            SUM(CASE WHEN `track_links`.`utm_medium` IN ({$qualified_blog_mediums_sql}) AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `blog_clicks`,
            SUM(CASE WHEN {$forever_registration_condition} AND `track_links`.`is_unique` = 1 THEN 1 ELSE 0 END) AS `registration_clicks`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`user_id` = {$this->user->user_id}
              AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}'
            GROUP BY DATE(`track_links`.`datetime`)");
        while($signal_chart_row = $signal_chart_clicks_result->fetch_object()) {
            $day_key = (string) ($signal_chart_row->day ?? '');
            if(!isset($signal_chart_days_map[$day_key])) {
                continue;
            }

            $signal_chart_days_map[$day_key]['app_clicks'] = (int) ($signal_chart_row->app_clicks ?? 0);
            $signal_chart_days_map[$day_key]['blog_clicks'] = (int) ($signal_chart_row->blog_clicks ?? 0);
            $signal_chart_days_map[$day_key]['registration_clicks'] = (int) ($signal_chart_row->registration_clicks ?? 0);
        }

        $signal_chart_leads_result = database()->query("SELECT DATE(`datetime`) AS `day`, COUNT(*) AS `total`
            FROM `data`
            WHERE `user_id` = {$this->user->user_id}
              AND `type` IN ('lead_funnel', 'ai_chat_lead')
              AND `datetime` >= '{$thirty_days_start_datetime}'
            GROUP BY DATE(`datetime`)");
        while($signal_chart_lead_row = $signal_chart_leads_result->fetch_object()) {
            $day_key = (string) ($signal_chart_lead_row->day ?? '');
            if(!isset($signal_chart_days_map[$day_key])) {
                continue;
            }

            $signal_chart_days_map[$day_key]['leads'] = (int) ($signal_chart_lead_row->total ?? 0);
        }

        foreach($signal_chart_days_map as $day_row) {
            $signal_chart_labels[] = $day_row['label'];
            $signal_chart_app_clicks[] = $day_row['app_clicks'];
            $signal_chart_blog_clicks[] = $day_row['blog_clicks'];
            $signal_chart_registration_clicks[] = $day_row['registration_clicks'];
            $signal_chart_leads[] = $day_row['leads'];
        }

        /* Custom code: FC-2026-03-30: dashboard geo breakdowns for key FCC KPIs */
        $dashboard_user_id = (int) $this->user->user_id;
        $get_dashboard_geo_breakdown = static function(string $joins_sql, string $where_sql, string $field) use ($thirty_days_start_datetime, $unique_track_links_condition, $dashboard_user_id) {
            $field = $field === 'city_name' ? 'city_name' : 'country_code';
            $value_filter_sql = $field === 'city_name'
                ? "AND `track_links`.`city_name` IS NOT NULL AND `track_links`.`city_name` != ''"
                : "AND `track_links`.`country_code` IS NOT NULL AND `track_links`.`country_code` != ''";

            $select_sql = $field === 'city_name'
                ? "`track_links`.`city_name` AS `value`, `track_links`.`country_code` AS `country_code`, COUNT(*) AS `total`"
                : "`track_links`.`country_code` AS `value`, COUNT(*) AS `total`";

            $group_by_sql = $field === 'city_name'
                ? "`track_links`.`city_name`, `track_links`.`country_code`"
                : "`track_links`.`country_code`";

            $result = database()->query("SELECT {$select_sql} FROM `track_links` {$joins_sql} WHERE `track_links`.`user_id` = {$dashboard_user_id} AND `track_links`.`datetime` >= '{$thirty_days_start_datetime}' {$unique_track_links_condition} {$where_sql} {$value_filter_sql} GROUP BY {$group_by_sql} ORDER BY `total` DESC LIMIT 15");
            $rows = [];

            while($row = $result->fetch_object()) {
                $rows[] = [
                    'value' => (string) ($row->value ?? ''),
                    'total' => (int) ($row->total ?? 0),
                    'country_code' => isset($row->country_code) ? (string) ($row->country_code ?? '') : null,
                ];
            }

            return $rows;
        };

        $biolink_visits_countries_30d = $get_dashboard_geo_breakdown(
            "LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`",
            "AND `links`.`type` = 'biolink'",
            'country_code'
        );
        $biolink_visits_cities_30d = $get_dashboard_geo_breakdown(
            "LEFT JOIN `links` ON `track_links`.`link_id` = `links`.`link_id`",
            "AND `links`.`type` = 'biolink'",
            'city_name'
        );
        $forever_shop_clicks_countries_30d = $get_dashboard_geo_breakdown(
            "LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`",
            "AND {$forever_shop_condition}",
            'country_code'
        );
        $forever_shop_clicks_cities_30d = $get_dashboard_geo_breakdown(
            "LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`",
            "AND {$forever_shop_condition}",
            'city_name'
        );
        $forever_registration_clicks_countries_30d = $get_dashboard_geo_breakdown(
            "LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`",
            "AND {$forever_registration_condition}",
            'country_code'
        );
        $forever_registration_clicks_cities_30d = $get_dashboard_geo_breakdown(
            "LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`",
            "AND {$forever_registration_condition}",
            'city_name'
        );
        /* /Custom code: FC-2026-03-30 */

        $dashboard_funnel_analytics = [
            'has_funnels' => false,
            'total_funnels' => 0,
            'active_funnels_30d' => 0,
            'unique_clicks_30d' => 0,
            'leads_30d' => 0,
            'funnel_leads_30d' => 0,
            'ai_chat_leads_30d' => $ai_chat_leads_30d,
            'contact_captures_30d' => $ai_chat_leads_30d,
            'conversion_rate_30d' => 0,
            'best_open_mode' => null,
            'best_thank_you_type' => null,
            'last_lead_datetime' => null,
            'last_lead_display' => null,
            'status' => 'setup',
            'status_title' => l('dashboard.funnel.status.setup_title'),
            'status_description' => $biolink_links_total > 0 ? l('dashboard.funnel.status.setup_description') : l('dashboard.funnel.status.no_biolinks_description'),
            'cta_label' => $biolink_links_total > 0 ? l('dashboard.funnel.status.setup_cta') : l('dashboard.funnel.status.no_biolinks_cta'),
            'cta_url' => $biolink_links_total > 0 ? url('links?type=biolink') : url('link-create'),
        ];

        $growth_active_threshold = 15;
        $growth_vip_threshold = 50;
        $growth_stage = $qualified_clicks_30d >= $growth_vip_threshold ? 'vip' : ($qualified_clicks_30d >= $growth_active_threshold ? 'active' : 'building');

        if($qualified_clicks_30d < $growth_active_threshold) {
            $hero_title = l('dashboard.hero.state.building.title');
            $hero_description = l('dashboard.hero.state.building.description');
            $next_focus = l('dashboard.hero.state.building.next_focus');
        } elseif($forever_registration_clicks_30d <= 0 && $qualified_clicks_30d > 0) {
            $hero_title = l('dashboard.hero.state.registration.title');
            $hero_description = l('dashboard.hero.state.registration.description');
            $next_focus = l('dashboard.hero.state.registration.next_focus');
        } elseif(($dashboard_funnel_analytics['contact_captures_30d'] ?? 0) > 0) {
            $hero_title = l('dashboard.hero.state.leads.title');
            $hero_description = l('dashboard.hero.state.leads.description');
            $next_focus = l('dashboard.hero.state.leads.next_focus');
        } else {
            $hero_title = l('dashboard.hero.state.active.title');
            $hero_description = l('dashboard.hero.state.active.description');
            $next_focus = l('dashboard.hero.state.active.next_focus');
        }

        $support_summary = [
            'available' => false,
            'open_total' => 0,
            'answered_total' => 0,
            'closed_total' => 0,
            'unread_total' => 0,
            'webinar_total' => 0,
            'repeated_issue_detected' => false,
            'repeated_issue_label' => '',
            'selected_ticket_url' => url('feedback-tickets'),
        ];

        $has_feedback_tickets = database()->query("SHOW TABLES LIKE 'feedback_tickets'");
        $has_feedback_ticket_messages = database()->query("SHOW TABLES LIKE 'feedback_ticket_messages'");
        if($has_feedback_tickets && $has_feedback_tickets->num_rows && $has_feedback_ticket_messages && $has_feedback_ticket_messages->num_rows) {
            $support_summary['available'] = true;
            $support_tickets_result = database()->query("SELECT `feedback_ticket_id`, `category`, `status`, `admin_last_replied_at`, `user_last_read_at`, `is_webinar_topic_suggestion`
                FROM `feedback_tickets`
                WHERE `user_id` = {$this->user->user_id}
                ORDER BY `last_datetime` DESC");
            $support_category_counter = [];
            $first_ticket_id = null;
            $preferred_ticket_id = null;
            while($ticket_row = $support_tickets_result->fetch_object()) {
                if($first_ticket_id === null) {
                    $first_ticket_id = (int) ($ticket_row->feedback_ticket_id ?? 0);
                }

                $status = (string) ($ticket_row->status ?? 'open');
                if($status === 'open') {
                    $support_summary['open_total']++;
                } elseif($status === 'answered') {
                    $support_summary['answered_total']++;
                } elseif($status === 'closed') {
                    $support_summary['closed_total']++;
                }

                if(!empty($ticket_row->is_webinar_topic_suggestion)) {
                    $support_summary['webinar_total']++;
                }

                $admin_last_replied_at = (string) ($ticket_row->admin_last_replied_at ?? '');
                $user_last_read_at = (string) ($ticket_row->user_last_read_at ?? '');
                if($admin_last_replied_at !== '' && in_array($status, ['answered', 'closed'], true) && ($user_last_read_at === '' || $user_last_read_at < $admin_last_replied_at)) {
                    $support_summary['unread_total']++;
                    if($preferred_ticket_id === null) {
                        $preferred_ticket_id = (int) ($ticket_row->feedback_ticket_id ?? 0);
                    }
                } elseif($preferred_ticket_id === null && in_array($status, ['open', 'answered'], true)) {
                    $preferred_ticket_id = (int) ($ticket_row->feedback_ticket_id ?? 0);
                }

                $category = (string) ($ticket_row->category ?? 'other');
                $support_category_counter[$category] = ($support_category_counter[$category] ?? 0) + 1;
            }

            $selected_ticket_id = $preferred_ticket_id ?: $first_ticket_id;
            if($selected_ticket_id) {
                $support_summary['selected_ticket_url'] = url('feedback-tickets?ticket_id=' . $selected_ticket_id);
            }

            arsort($support_category_counter);
            $repeated_category = key($support_category_counter);
            $repeated_total = (int) current($support_category_counter);
            if($repeated_category && $repeated_total >= 2) {
                $support_summary['repeated_issue_detected'] = true;
                $support_summary['repeated_issue_label'] = $repeated_category;
            }
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
        if($top_forever_page && !empty($top_forever_page['open_url'])) {
            $dashboard_recommendations[] = [
                'status' => 'good',
                'title' => l('dashboard.forever_analytics.recommendation.top_page.title'),
                'description' => sprintf(l('dashboard.forever_analytics.recommendation.top_page.description'), $top_forever_page['title'] ?: ($top_forever_page['url'] ?: '-'), $top_forever_page['total']),
                'cta_label' => l('dashboard.forever_analytics.recommendation.top_page.cta'),
                'cta_url' => $top_forever_page['open_url'],
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
            'qualified_clicks_30d' => $qualified_clicks_30d,
            'qualified_clicks_prev_30d' => $qualified_clicks_prev_30d,
            'qualified_clicks_delta_percent' => $qualified_clicks_delta_percent,
            'qualified_ctr_30d' => $biolink_visits_30d > 0 ? round(($qualified_clicks_30d / $biolink_visits_30d) * 100, 1) : 0,
            'app_qualified_clicks_30d' => $app_qualified_clicks_30d,
            'blog_qualified_clicks_30d' => $blog_qualified_clicks_30d,
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
            'top_blog_content_30d' => $top_blog_content_30d,
            'top_app_content_30d' => $top_app_content_30d,
            'top_shop_sources_30d' => $top_shop_sources_30d,
            'top_registration_sources_30d' => $top_registration_sources_30d,
            'top_country_30d' => $top_country,
            'top_shop_source_30d' => $top_shop_sources_30d[0] ?? null,
            'growth_active_threshold' => $growth_active_threshold,
            'growth_vip_threshold' => $growth_vip_threshold,
            'growth_stage' => $growth_stage,
            'to_active' => max(0, $growth_active_threshold - $qualified_clicks_30d),
            'to_vip' => max(0, $growth_vip_threshold - $qualified_clicks_30d),
            'active_progress_percent' => min(100, round(($qualified_clicks_30d / max(1, $growth_active_threshold)) * 100)),
            'vip_progress_percent' => min(100, round(($qualified_clicks_30d / max(1, $growth_vip_threshold)) * 100)),
            'hero_title' => $hero_title,
            'hero_description' => $hero_description,
            'next_focus' => $next_focus,
            'signal_chart_30d' => [
                'labels' => $signal_chart_labels,
                'app_clicks' => $signal_chart_app_clicks,
                'blog_clicks' => $signal_chart_blog_clicks,
                'registration_clicks' => $signal_chart_registration_clicks,
                'leads' => $signal_chart_leads,
            ],
            /* Custom code: FC-2026-03-30: dashboard geo breakdown payload */
            'biolink_visits_countries_30d' => $biolink_visits_countries_30d,
            'biolink_visits_cities_30d' => $biolink_visits_cities_30d,
            'forever_shop_clicks_countries_30d' => $forever_shop_clicks_countries_30d,
            'forever_shop_clicks_cities_30d' => $forever_shop_clicks_cities_30d,
            'forever_registration_clicks_countries_30d' => $forever_registration_clicks_countries_30d,
            'forever_registration_clicks_cities_30d' => $forever_registration_clicks_cities_30d,
            /* /Custom code: FC-2026-03-30 */
        ];

        $funnel_blocks_map = [];
        $funnel_ids = [];
        $funnel_blocks_result = database()->query("SELECT `biolink_block_id`, `settings` FROM `biolinks_blocks` WHERE `user_id` = {$this->user->user_id} AND `type` = 'lead_funnel'");
        while($row = $funnel_blocks_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $row->open_mode = in_array(($row->settings->open_mode ?? 'popup'), ['popup', 'page'], true) ? $row->settings->open_mode : 'popup';
            $row->thank_you_type = in_array(($row->settings->thank_you_type ?? 'message'), ['message', 'external_url', 'biolink_redirect', 'file_download'], true) ? $row->settings->thank_you_type : 'message';

            $funnel_blocks_map[(int) $row->biolink_block_id] = $row;
            $funnel_ids[] = (int) $row->biolink_block_id;
        }

        if($funnel_ids) {
            $dashboard_funnel_analytics['has_funnels'] = true;
            $dashboard_funnel_analytics['total_funnels'] = count($funnel_ids);

            $funnel_ids_sql = implode(',', $funnel_ids);

            $funnel_unique_clicks_result = database()->query("SELECT `biolink_block_id`, SUM(`is_unique`) AS `unique_clicks` FROM `track_links` WHERE `user_id` = {$this->user->user_id} AND `biolink_block_id` IN ({$funnel_ids_sql}) AND `datetime` >= '{$thirty_days_start_datetime}' GROUP BY `biolink_block_id`");
            $funnel_unique_clicks_map = [];
            while($row = $funnel_unique_clicks_result->fetch_object()) {
                $funnel_unique_clicks_map[(int) $row->biolink_block_id] = (int) ($row->unique_clicks ?? 0);
            }

            $funnel_leads_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total_leads`, MAX(`datetime`) AS `last_lead_datetime` FROM `data` WHERE `user_id` = {$this->user->user_id} AND `type` = 'lead_funnel' AND `biolink_block_id` IN ({$funnel_ids_sql}) AND `datetime` >= '{$thirty_days_start_datetime}' GROUP BY `biolink_block_id`");
            $funnel_leads_map = [];
            while($row = $funnel_leads_result->fetch_object()) {
                $funnel_leads_map[(int) $row->biolink_block_id] = [
                    'total_leads' => (int) ($row->total_leads ?? 0),
                    'last_lead_datetime' => $row->last_lead_datetime ?? null,
                ];
            }

            $open_mode_breakdown = [
                'popup' => ['type' => 'popup', 'unique_clicks' => 0, 'leads' => 0, 'conversion_rate' => 0],
                'page' => ['type' => 'page', 'unique_clicks' => 0, 'leads' => 0, 'conversion_rate' => 0],
            ];
            $thank_you_type_breakdown = [
                'message' => ['type' => 'message', 'unique_clicks' => 0, 'leads' => 0, 'conversion_rate' => 0],
                'external_url' => ['type' => 'external_url', 'unique_clicks' => 0, 'leads' => 0, 'conversion_rate' => 0],
                'biolink_redirect' => ['type' => 'biolink_redirect', 'unique_clicks' => 0, 'leads' => 0, 'conversion_rate' => 0],
                'file_download' => ['type' => 'file_download', 'unique_clicks' => 0, 'leads' => 0, 'conversion_rate' => 0],
            ];

            foreach($funnel_ids as $funnel_id) {
                $unique_clicks = (int) ($funnel_unique_clicks_map[$funnel_id] ?? 0);
                $leads = (int) ($funnel_leads_map[$funnel_id]['total_leads'] ?? 0);
                $dashboard_funnel_analytics['unique_clicks_30d'] += $unique_clicks;
                $dashboard_funnel_analytics['leads_30d'] += $leads;
                $dashboard_funnel_analytics['funnel_leads_30d'] += $leads;

                if($unique_clicks > 0 || $leads > 0) {
                    $dashboard_funnel_analytics['active_funnels_30d']++;
                }

                $last_lead_datetime = $funnel_leads_map[$funnel_id]['last_lead_datetime'] ?? null;
                if($last_lead_datetime && (!$dashboard_funnel_analytics['last_lead_datetime'] || strtotime($last_lead_datetime) > strtotime($dashboard_funnel_analytics['last_lead_datetime']))) {
                    $dashboard_funnel_analytics['last_lead_datetime'] = $last_lead_datetime;
                    $dashboard_funnel_analytics['last_lead_display'] = \Altum\Date::get($last_lead_datetime, 2);
                }

                $funnel = $funnel_blocks_map[$funnel_id];
                $open_mode_breakdown[$funnel->open_mode]['unique_clicks'] += $unique_clicks;
                $open_mode_breakdown[$funnel->open_mode]['leads'] += $leads;
                $thank_you_type_breakdown[$funnel->thank_you_type]['unique_clicks'] += $unique_clicks;
                $thank_you_type_breakdown[$funnel->thank_you_type]['leads'] += $leads;
            }

            $dashboard_funnel_analytics['contact_captures_30d'] = (int) ($dashboard_funnel_analytics['funnel_leads_30d'] ?? 0) + (int) ($dashboard_funnel_analytics['ai_chat_leads_30d'] ?? 0);
            $dashboard_funnel_analytics['conversion_rate_30d'] = $dashboard_funnel_analytics['unique_clicks_30d'] > 0 ? round(($dashboard_funnel_analytics['leads_30d'] / $dashboard_funnel_analytics['unique_clicks_30d']) * 100, 1) : 0;

            foreach($open_mode_breakdown as &$row) {
                $row['conversion_rate'] = $row['unique_clicks'] > 0 ? round(($row['leads'] / $row['unique_clicks']) * 100, 1) : 0;
            }
            unset($row);

            foreach($thank_you_type_breakdown as &$row) {
                $row['conversion_rate'] = $row['unique_clicks'] > 0 ? round(($row['leads'] / $row['unique_clicks']) * 100, 1) : 0;
            }
            unset($row);

            $rank_breakdown = static function(array $rows): ?array {
                $rows = array_values(array_filter($rows, static function($row) {
                    return $row['unique_clicks'] > 0 || $row['leads'] > 0;
                }));

                if(!$rows) {
                    return null;
                }

                usort($rows, static function($a, $b) {
                    return [$b['conversion_rate'], $b['leads'], $b['unique_clicks']] <=> [$a['conversion_rate'], $a['leads'], $a['unique_clicks']];
                });

                return $rows[0] ?? null;
            };

            $dashboard_funnel_analytics['best_open_mode'] = $rank_breakdown($open_mode_breakdown);
            $dashboard_funnel_analytics['best_thank_you_type'] = $rank_breakdown($thank_you_type_breakdown);

            if($dashboard_funnel_analytics['unique_clicks_30d'] <= 0) {
                $dashboard_funnel_analytics['status'] = 'warning';
                $dashboard_funnel_analytics['status_title'] = l('dashboard.funnel.status.no_traffic_title');
                $dashboard_funnel_analytics['status_description'] = l('dashboard.funnel.status.no_traffic_description');
                $dashboard_funnel_analytics['cta_label'] = l('dashboard.funnel.status.no_traffic_cta');
                $dashboard_funnel_analytics['cta_url'] = url('links?type=biolink');
            } elseif($dashboard_funnel_analytics['leads_30d'] <= 0) {
                $dashboard_funnel_analytics['status'] = 'danger';
                $dashboard_funnel_analytics['status_title'] = l('dashboard.funnel.status.no_leads_title');
                $dashboard_funnel_analytics['status_description'] = l('dashboard.funnel.status.no_leads_description');
                $dashboard_funnel_analytics['cta_label'] = l('dashboard.funnel.status.no_leads_cta');
                $dashboard_funnel_analytics['cta_url'] = url('funnels-analytics');
            } elseif($dashboard_funnel_analytics['conversion_rate_30d'] < 10) {
                $dashboard_funnel_analytics['status'] = 'warning';
                $dashboard_funnel_analytics['status_title'] = l('dashboard.funnel.status.improve_title');
                $dashboard_funnel_analytics['status_description'] = sprintf(l('dashboard.funnel.status.improve_description'), $dashboard_funnel_analytics['conversion_rate_30d']);
                $dashboard_funnel_analytics['cta_label'] = l('dashboard.funnel.status.improve_cta');
                $dashboard_funnel_analytics['cta_url'] = url('funnels-analytics');
            } else {
                $dashboard_funnel_analytics['status'] = 'good';
                $dashboard_funnel_analytics['status_title'] = l('dashboard.funnel.status.good_title');
                $dashboard_funnel_analytics['status_description'] = sprintf(l('dashboard.funnel.status.good_description'), $dashboard_funnel_analytics['leads_30d']);
                $dashboard_funnel_analytics['cta_label'] = l('dashboard.funnel.status.good_cta');
                $dashboard_funnel_analytics['cta_url'] = url('funnels-analytics');
            }
        }

        $dashboard_funnel_analytics['contact_captures_30d'] = (int) ($dashboard_funnel_analytics['funnel_leads_30d'] ?? $dashboard_funnel_analytics['leads_30d'] ?? 0) + (int) ($dashboard_funnel_analytics['ai_chat_leads_30d'] ?? 0);

        if($qualified_clicks_30d < $growth_active_threshold) {
            $hero_title = l('dashboard.hero.state.building.title');
            $hero_description = l('dashboard.hero.state.building.description');
            $next_focus = l('dashboard.hero.state.building.next_focus');
        } elseif($forever_registration_clicks_30d <= 0 && $qualified_clicks_30d > 0) {
            $hero_title = l('dashboard.hero.state.registration.title');
            $hero_description = l('dashboard.hero.state.registration.description');
            $next_focus = l('dashboard.hero.state.registration.next_focus');
        } elseif(($dashboard_funnel_analytics['contact_captures_30d'] ?? 0) > 0) {
            $hero_title = l('dashboard.hero.state.leads.title');
            $hero_description = l('dashboard.hero.state.leads.description');
            $next_focus = l('dashboard.hero.state.leads.next_focus');
        } else {
            $hero_title = l('dashboard.hero.state.active.title');
            $hero_description = l('dashboard.hero.state.active.description');
            $next_focus = l('dashboard.hero.state.active.next_focus');
        }

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
                /* Custom code: FC-2026-03-30: demo geo breakdown payload */
                'biolink_visits_countries_30d' => [
                    ['value' => 'HR', 'total' => 188],
                    ['value' => 'BA', 'total' => 67],
                    ['value' => 'RS', 'total' => 31],
                ],
                'biolink_visits_cities_30d' => [
                    ['value' => 'Zagreb', 'total' => 84, 'country_code' => 'HR'],
                    ['value' => 'Split', 'total' => 36, 'country_code' => 'HR'],
                    ['value' => 'Sarajevo', 'total' => 28, 'country_code' => 'BA'],
                ],
                'forever_shop_clicks_countries_30d' => [
                    ['value' => 'HR', 'total' => 42],
                    ['value' => 'BA', 'total' => 21],
                    ['value' => 'AL', 'total' => 15],
                ],
                'forever_shop_clicks_cities_30d' => [
                    ['value' => 'Zagreb', 'total' => 17, 'country_code' => 'HR'],
                    ['value' => 'Split', 'total' => 11, 'country_code' => 'HR'],
                    ['value' => 'Tirana', 'total' => 8, 'country_code' => 'AL'],
                ],
                'forever_registration_clicks_countries_30d' => [
                    ['value' => 'HR', 'total' => 9],
                    ['value' => 'BA', 'total' => 5],
                    ['value' => 'RS', 'total' => 3],
                ],
                'forever_registration_clicks_cities_30d' => [
                    ['value' => 'Zagreb', 'total' => 4, 'country_code' => 'HR'],
                    ['value' => 'Sarajevo', 'total' => 3, 'country_code' => 'BA'],
                    ['value' => 'Beograd', 'total' => 2, 'country_code' => 'RS'],
                ],
                /* /Custom code: FC-2026-03-30 */
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

            $dashboard_funnel_analytics = [
                'has_funnels' => true,
                'total_funnels' => 3,
                'active_funnels_30d' => 2,
                'unique_clicks_30d' => 74,
                'leads_30d' => 11,
                'funnel_leads_30d' => 11,
                'ai_chat_leads_30d' => 3,
                'contact_captures_30d' => 14,
                'conversion_rate_30d' => 14.9,
                'best_open_mode' => ['type' => 'page', 'leads' => 7, 'conversion_rate' => 18.4],
                'best_thank_you_type' => ['type' => 'file_download', 'leads' => 6, 'conversion_rate' => 16.7],
                'last_lead_datetime' => date('Y-m-d H:i:s'),
                'last_lead_display' => \Altum\Date::get(date('Y-m-d H:i:s'), 2),
                'status' => 'good',
                'status_title' => l('dashboard.funnel.status.good_title'),
                'status_description' => sprintf(l('dashboard.funnel.status.good_description'), 11),
                'cta_label' => l('dashboard.funnel.status.good_cta'),
                'cta_url' => url('funnels-analytics'),
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
            'dashboard_funnel_analytics' => $dashboard_funnel_analytics,
            'dashboard_support_summary' => $support_summary,
            /* /Custom code: FC-2026-03-05 */
        ];

        /* Set a nice success message */
        Response::json('', 'success', $data);

    }

}
