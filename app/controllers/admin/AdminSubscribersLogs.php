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

class AdminSubscribersLogs extends Controller {

    public function index() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['user_id', 'website_id', 'campaign_id', 'subscriber_id', 'flow_id', 'personal_notification_id', 'rss_automation_id', 'type'], ['ip'], ['subscriber_log_id', 'datetime']));
        $filters->set_default_order_by($this->user->preferences->subscribers_logs_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `subscribers_logs` WHERE 1 = 1 {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('admin/subscribers-logs?' . $filters->get_get() . '&page=%d')));

        /* Get the subscribers list for the user */
        $subscribers_logs = [];
        $subscribers_logs_result = database()->query("
            SELECT
                `subscribers_logs`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar`, `websites`.`host`, `websites`.`path`
            FROM
                `subscribers_logs`
            LEFT JOIN
                `users` ON `subscribers_logs`.`user_id` = `users`.`user_id`
            LEFT JOIN
                `websites` ON `subscribers_logs`.`website_id` = `websites`.`website_id`
            WHERE
                1 = 1
                {$filters->get_sql_where('subscribers_logs')}
                {$filters->get_sql_order_by('subscribers_logs')}
            
            {$paginator->get_sql_limit()}
        ");
        while($row = $subscribers_logs_result->fetch_object()) $subscribers_logs[] = $row;

        /* Export handler */
        process_export_csv($subscribers_logs, ['subscriber_log_id', 'subscriber_id', 'campaign_id', 'flow_id', 'personal_notification_id', 'rss_automation_id', 'website_id', 'user_id', 'ip', 'type', 'datetime']);
        process_export_json($subscribers_logs, ['subscriber_log_id', 'subscriber_id', 'campaign_id', 'flow_id', 'personal_notification_id', 'rss_automation_id', 'website_id', 'user_id', 'ip', 'type', 'datetime']);

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Prepare the view */
        $data = [
            'subscribers_logs' => $subscribers_logs,
            'pagination' => $pagination,
            'filters' => $filters,
        ];

        $view = new \Altum\View('admin/subscribers-logs/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('admin/subscribers-logs');
        }

        if(empty($_POST['selected'])) {
            redirect('admin/subscribers-logs');
        }

        if(!isset($_POST['type'])) {
            redirect('admin/subscribers-logs');
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

                    foreach($_POST['selected'] as $subscriber_log_id) {
                        /* Database query */
                        db()->where('subscriber_log_id', $subscriber_log_id)->delete('subscribers_logs');
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('admin/subscribers-logs');
    }

    public function delete() {

        $subscriber_log_id = (isset($this->params[0])) ? (int) $this->params[0] : null;

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Database query */
            db()->where('subscriber_log_id', $subscriber_log_id)->delete('subscribers_logs');

            /* Set a nice success message */
            Alerts::add_success(l('global.success_message.delete2'));

        }

        redirect('admin/subscribers-logs');
    }
}
