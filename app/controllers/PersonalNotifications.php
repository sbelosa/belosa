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
use Altum\Models\PersonalNotification;

defined('ALTUMCODE') || die();

class PersonalNotifications extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['website_id', 'type'], ['name', 'title', 'description', 'url',], ['personal_notification_id', 'name', 'title', 'sent_datetime', 'datetime', 'last_datetime',]));
        $filters->set_default_order_by($this->user->preferences->personal_notifications_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `personal_notifications` WHERE `user_id` = {$this->user->user_id} {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('personal-notifications?' . $filters->get_get() . '&page=%d')));

        /* Generate stats */
        $websites_stats = [
            'total_sent_push_notifications' => 0,
            'total_displayed_push_notifications' => 0,
            'total_clicked_push_notifications' => 0,
            'total_closed_push_notifications' => 0,
        ];

        /* Get the personal_notifications list for the user */
        $personal_notifications = [];
        $personal_notifications_result = database()->query("SELECT `personal_notifications`.*, `subscribers`.`ip` FROM `personal_notifications` LEFT JOIN `subscribers` ON `personal_notifications`.`subscriber_id` = `subscribers`.`subscriber_id` WHERE `personal_notifications`.`user_id` = {$this->user->user_id} {$filters->get_sql_where('personal_notifications')} {$filters->get_sql_order_by('personal_notifications')} {$paginator->get_sql_limit()}");
        while($row = $personal_notifications_result->fetch_object()) {
            $websites_stats['total_sent_push_notifications'] += $row->is_sent ? 1 : 0;
            $websites_stats['total_displayed_push_notifications'] += $row->is_displayed ? 1 : 0;
            $websites_stats['total_clicked_push_notifications'] += $row->is_clicked ? 1 : 0;
            $websites_stats['total_closed_push_notifications'] += $row->is_closed ? 1 : 0;

            $row->settings = json_decode($row->settings);
            $personal_notifications[] = $row;
        }

        /* Export handler */
        process_export_json($personal_notifications, ['personal_notification_id', 'website_id', 'user_id', 'name', 'title', 'description', 'url', 'image', 'settings', 'status', 'is_sent', 'is_displayed', 'is_clicked', 'is_closed', 'scheduled_datetime', 'sent_datetime', 'datetime', 'last_datetime',]);
        process_export_csv_new($personal_notifications, ['personal_notification_id', 'website_id', 'user_id', 'name', 'title', 'description', 'url', 'image', 'settings', 'status', 'is_sent', 'is_displayed', 'is_clicked', 'is_closed', 'scheduled_datetime', 'sent_datetime', 'datetime', 'last_datetime',], ['settings']);

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->user->user_id);

        /* Get statistics */
        if(count($personal_notifications) && !$filters->has_applied_filters) {
            $start_date_query = (new \DateTime())->modify('-' . (settings()->main->chart_days ?? 30) . ' day')->format('Y-m-d');
            $end_date_query = (new \DateTime())->modify('+1 day')->format('Y-m-d');

            $convert_tz_sql = get_convert_tz_sql('`sent_datetime`', $this->user->timezone);

            $personal_notifications_result_query = "
                SELECT
                    COUNT(*) AS `total`,
                    DATE_FORMAT({$convert_tz_sql}, '%Y-%m-%d') AS `formatted_date`
                FROM
                    `personal_notifications`
                WHERE   
                    `user_id` = {$this->user->user_id} 
                    AND ({$convert_tz_sql} BETWEEN '{$start_date_query}' AND '{$end_date_query}')
                GROUP BY
                    `formatted_date`
                ORDER BY
                    `formatted_date`
            ";

            $personal_notifications_chart = \Altum\Cache::cache_function_result('personal_notifications_chart?user_id=' . $this->user->user_id, null, function() use ($personal_notifications_result_query) {
                $personal_notifications_chart= [];

                $personal_notifications_result = database()->query($personal_notifications_result_query);

                /* Generate the raw chart data and save logs for later usage */
                while($row = $personal_notifications_result->fetch_object()) {
                    $label = \Altum\Date::get($row->formatted_date, 5, \Altum\Date::$default_timezone);
                    $personal_notifications_chart[$label]['push_notification_sent'] = $row->total;
                }

                return $personal_notifications_chart;
            }, 60 * 60 * settings()->main->chart_cache ?? 12);

            $personal_notifications_chart = get_chart_data($personal_notifications_chart);
        }

        /* Prepare the view */
        $data = [
            'personal_notifications_chart' => $personal_notifications_chart ?? null,
            'personal_notifications' => $personal_notifications,
            'websites' => $websites,
            'total_personal_notifications' => $total_rows,
            'pagination' => $pagination,
            'filters' => $filters,
            'websites_stats' => $websites_stats,
        ];

        $view = new \Altum\View('personal-notifications/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function duplicate() {
        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.personal_notifications')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('personal-notifications');
        }

        if(empty($_POST)) {
            redirect('personal-notifications');
        }

        /* Check for the plan limit */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `personal_notifications` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total ?? 0;
        if($this->user->plan_settings->personal_notifications_limit != -1 && $total_rows >= $this->user->plan_settings->personal_notifications_limit) {
            Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
            redirect('personal-notifications');
        }

        $personal_notification_id = (int) $_POST['personal_notification_id'];

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');
        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('personal-notifications');
        }

        /* Verify the main resource */
        if(!$personal_notification = db()->where('personal_notification_id', $personal_notification_id)->where('user_id', $this->user->user_id)->getOne('personal_notifications')) {
            redirect('personal-notifications');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Duplicate the files */
            $image = \Altum\Uploads::copy_uploaded_file($personal_notification->image, \Altum\Uploads::get_path('websites_personal_notifications_images'), \Altum\Uploads::get_path('websites_personal_notifications_images'));

            /* Insert to database */
            $personal_notification_id = db()->insert('personal_notifications', [
                'website_id' => $personal_notification->website_id,
                'subscriber_id' => $personal_notification->subscriber_id,
                'user_id' => $this->user->user_id,
                'name' => string_truncate($personal_notification->name . ' - ' . l('global.duplicated'), 64, null),
                'title' => $personal_notification->title,
                'description' => $personal_notification->description,
                'url' => $personal_notification->url,
                'image' => $image,
                'settings' => $personal_notification->settings,
                'status' => 'draft',
                'scheduled_datetime' => $personal_notification->scheduled_datetime,
                'datetime' => get_date(),
            ]);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.create1'), '<strong>' . input_clean($personal_notification->name) . '</strong>'));

            /* Redirect */
            redirect('personal-notification-update/' . $personal_notification_id);

        }

        redirect('personal-notifications');
    }

    public function bulk() {

        \Altum\Authentication::guard();

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('personal-notifications');
        }

        if(empty($_POST['selected'])) {
            redirect('personal-notifications');
        }

        if(!isset($_POST['type'])) {
            redirect('personal-notifications');
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
                    if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.personal_notifications')) {
                        Alerts::add_error(l('global.info_message.team_no_access'));
                        redirect('personal-notifications');
                    }

                    foreach($_POST['selected'] as $personal_notification_id) {
                        if($personal_notification = db()->where('personal_notification_id', $personal_notification_id)->where('user_id', $this->user->user_id)->getOne('personal_notifications', ['personal_notification_id'])) {
                            (new PersonalNotification())->delete($personal_notification_id);
                        }
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('personal-notifications');
    }

    public function delete() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.personal_notifications')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('personal-notifications');
        }

        if(empty($_POST)) {
            redirect('personal-notifications');
        }

        $personal_notification_id = (int) query_clean($_POST['personal_notification_id']);

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$personal_notification = db()->where('personal_notification_id', $personal_notification_id)->where('user_id', $this->user->user_id)->getOne('personal_notifications', ['personal_notification_id', 'name'])) {
            redirect('personal-notifications');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            (new PersonalNotification())->delete($personal_notification_id);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $personal_notification->name . '</strong>'));

            redirect('personal-notifications');
        }

        redirect('personal-notifications');
    }
}
