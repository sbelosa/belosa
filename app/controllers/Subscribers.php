<?php
/*
 * Copyright (c) 2025 AltumCode (https://altumcode.com/)
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

defined('ALTUMCODE') || die();

class Subscribers extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['user_id', 'website_id', 'device_type', 'country_code', 'continent_code'], ['ip', 'city_name', 'os_name', 'browser_name', 'browser_language', 'subscribed_on_url'], ['subscriber_id', 'last_sent_datetime', 'datetime', 'last_datetime', 'total_sent_push_notifications']));
        $filters->set_default_order_by($this->user->preferences->subscribers_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `subscribers` WHERE `user_id` = {$this->user->user_id} {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('subscribers?' . $filters->get_get() . '&page=%d')));

        /* Generate stats */
        $websites_stats = [
            'total_sent_push_notifications' => 0,
            'total_displayed_push_notifications' => 0,
            'total_clicked_push_notifications' => 0,
            'total_closed_push_notifications' => 0,
        ];

        /* Get the subscribers list for the user */
        $subscribers = [];
        $subscribers_result = database()->query("SELECT * FROM `subscribers` WHERE `user_id` = {$this->user->user_id} {$filters->get_sql_where()} {$filters->get_sql_order_by()} {$paginator->get_sql_limit()}");
        while($row = $subscribers_result->fetch_object()) {
            $websites_stats['total_sent_push_notifications'] += $row->total_sent_push_notifications;
            $websites_stats['total_displayed_push_notifications'] += $row->total_displayed_push_notifications;
            $websites_stats['total_clicked_push_notifications'] += $row->total_clicked_push_notifications;
            $websites_stats['total_closed_push_notifications'] += $row->total_closed_push_notifications;

            $row->processed_flows = json_decode($row->processed_flows ?? '[]');
            $row->custom_parameters = json_decode($row->custom_parameters ?? '');
            $row->keys = json_decode($row->keys ?? '');
            if($row->keys) {
                $row->p256dh = $row->keys->p256dh;
                $row->auth = $row->keys->auth;
            }

            $subscribers[] = $row;
        }

        /* Export handler */
        process_export_json($subscribers, ['subscriber_id', 'user_id', 'website_id', 'unique_endpoint_id', 'endpoint', 'p256dh', 'auth', 'ip', 'custom_parameters', 'has_flows_processed', 'processed_flows', 'city_name', 'country_code', 'continent_code', 'os_name', 'browser_name', 'browser_language', 'device_type', 'subscribed_on_url', 'total_sent_push_notifications', 'last_sent_datetime', 'datetime', 'last_datetime']);
        process_export_csv_new($subscribers, ['subscriber_id', 'user_id', 'website_id', 'unique_endpoint_id', 'endpoint', 'p256dh', 'auth', 'ip', 'custom_parameters', 'has_flows_processed', 'processed_flows', 'city_name', 'country_code', 'continent_code', 'os_name', 'browser_name', 'browser_language', 'device_type', 'subscribed_on_url', 'total_sent_push_notifications', 'last_sent_datetime', 'datetime', 'last_datetime'], ['custom_parameters', 'processed_flows']);

        /* Get statistics */
        if(count($subscribers) && !$filters->has_applied_filters) {
            $start_date_query = (new \DateTime())->modify('-' . (settings()->main->chart_days ?? 30) . ' day')->format('Y-m-d');
            $end_date_query = (new \DateTime())->modify('+1 day')->format('Y-m-d');

            $convert_tz_sql = get_convert_tz_sql('`datetime`', $this->user->timezone);

            $subscribers_result_query = "
                SELECT
                    COUNT(*) AS `total`,
                    DATE_FORMAT({$convert_tz_sql}, '%Y-%m-%d') AS `formatted_date`
                FROM
                    `subscribers`
                WHERE   
                    `user_id` = {$this->user->user_id} 
                    AND ({$convert_tz_sql} BETWEEN '{$start_date_query}' AND '{$end_date_query}')
                GROUP BY
                    `formatted_date`
                ORDER BY
                    `formatted_date`
            ";

            $subscribers_chart = \Altum\Cache::cache_function_result('subscribers_chart?user_id=' . $this->user->user_id, null, function() use ($subscribers_result_query) {
                $subscribers_chart= [];

                $subscribers_result = database()->query($subscribers_result_query);

                /* Generate the raw chart data and save logs for later usage */
                while($row = $subscribers_result->fetch_object()) {
                    $label = \Altum\Date::get($row->formatted_date, 5, \Altum\Date::$default_timezone);
                    $subscribers_chart[$label]['total'] = $row->total;
                }

                return $subscribers_chart;
            }, 60 * 60 * settings()->main->chart_cache ?? 12);

            $subscribers_chart = get_chart_data($subscribers_chart);
        }

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Get available custom domains */
        $domains = (new \Altum\Models\Domain())->get_available_domains_by_user($this->user);

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->user->user_id);

        /* Prepare the view */
        $data = [
            'subscribers' => $subscribers,
            'subscribers_chart' => $subscribers_chart ?? null,
            'websites' => $websites,
            'total_subscribers' => $total_rows,
            'domains' => $domains,
            'pagination' => $pagination,
            'filters' => $filters,
            'websites_stats' => $websites_stats,
        ];

        $view = new \Altum\View('subscribers/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        \Altum\Authentication::guard();

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('subscribers');
        }

        if(empty($_POST['selected'])) {
            redirect('subscribers');
        }

        if(!isset($_POST['type'])) {
            redirect('subscribers');
        }

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            set_time_limit(0);

            session_write_close();

            switch($_POST['type']) {
                case 'delete':

                    /* Team checks */
                    if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.subscribers')) {
                        Alerts::add_error(l('global.info_message.team_no_access'));
                        redirect('subscribers');
                    }

                    foreach($_POST['selected'] as $subscriber_id) {
                        if($subscriber = db()->where('subscriber_id', $subscriber_id)->where('user_id', $this->user->user_id)->getOne('subscribers', ['subscriber_id', 'website_id', 'ip'])) {

                            /* Update all previous logs */
                            db()->where('subscriber_id', $subscriber_id)->update('subscribers_logs', [
                                'ip' => preg_replace('/\d/', '*', $subscriber->ip)
                            ]);

                            /* Database query */
                            db()->where('subscriber_id', $subscriber_id)->delete('subscribers');

                            /* Update website statistics */
                            if(db()->count) {
                                db()->where('website_id', $subscriber->website_id)->update('websites', ['total_subscribers' => db()->dec()]);
                            }

                            /* Insert subscriber log */
                            db()->insert('subscribers_logs', [
                                'website_id' => $subscriber->website_id,
                                'user_id' => $this->user->user_id,
                                'ip' => preg_replace('/\d/', '*', $subscriber->ip),
                                'type' => 'manually_deleted',
                                'datetime' => get_date(),
                            ]);


                        }

                    }

                    /* Clear the cache */
                    cache()->deleteItem('subscribers_total?user_id=' . $this->user->user_id);
                    cache()->deleteItem('subscribers_dashboard?user_id=' . $this->user->user_id);

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('subscribers');
    }

    public function delete() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.subscribers')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('subscribers');
        }

        if(empty($_POST)) {
            redirect('subscribers');
        }

        $subscriber_id = (int) query_clean($_POST['subscriber_id']);

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$subscriber = db()->where('subscriber_id', $subscriber_id)->where('user_id', $this->user->user_id)->getOne('subscribers', ['subscriber_id', 'website_id', 'ip'])) {
            redirect('subscribers');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Update all previous logs */
            db()->where('subscriber_id', $subscriber_id)->update('subscribers_logs', [
                'ip' => preg_replace('/\d/', '*', $subscriber->ip)
            ]);

            /* Database query */
            db()->where('subscriber_id', $subscriber_id)->delete('subscribers');

            /* Update website statistics */
            if(db()->count) {
                db()->where('website_id', $subscriber->website_id)->update('websites', ['total_subscribers' => db()->dec()]);
            }

            /* Insert subscriber log */
            db()->insert('subscribers_logs', [
                'website_id' => $subscriber->website_id,
                'user_id' => $this->user->user_id,
                'ip' => preg_replace('/\d/', '*', $subscriber->ip),
                'type' => 'manually_deleted',
                'datetime' => get_date(),
            ]);

            /* Clear the cache */
            cache()->deleteItem('subscribers_total?user_id=' . $this->user->user_id);
            cache()->deleteItem('subscribers_dashboard?user_id=' . $this->user->user_id);

            /* Set a nice success message */
            Alerts::add_success(l('global.success_message.delete2'));

            redirect('subscribers');
        }

        redirect('subscribers');
    }
}
