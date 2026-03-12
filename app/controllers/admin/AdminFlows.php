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
use Altum\Models\Flow;

defined('ALTUMCODE') || die();

class AdminFlows extends Controller {

    public function index() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['website_id', 'segment', 'user_id'], ['name', 'title', 'description', 'url',], ['flow_id', 'name', 'title', 'last_sent_datetime', 'datetime', 'last_datetime']));
        $filters->set_default_order_by($this->user->preferences->flows_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `flows` WHERE 1 = 1 {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('admin/flows?' . $filters->get_get() . '&page=%d')));

        /* Get the flows list for the user */
        $flows = [];
        $flows_result = database()->query("
            SELECT
                `flows`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar`, `websites`.`host`, `websites`.`path`
            FROM
                `flows`
            LEFT JOIN
                `users` ON `flows`.`user_id` = `users`.`user_id`
            LEFT JOIN
                `websites` ON `flows`.`website_id` = `websites`.`website_id`
            WHERE
                1 = 1
                {$filters->get_sql_where('flows')}
                {$filters->get_sql_order_by('flows')}
            
            {$paginator->get_sql_limit()}
        ");
        while($row = $flows_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $flows[] = $row;
        }

        /* Export handler */
        process_export_json($flows, ['flow_id', 'website_id', 'user_id', 'name', 'title', 'description', 'url', 'image', 'segment', 'settings', 'is_enabled', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'last_sent_datetime', 'datetime', 'last_datetime',]);
        process_export_csv_new($flows, ['flow_id', 'website_id', 'user_id', 'name', 'title', 'description', 'url', 'image', 'segment', 'settings', 'is_enabled', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'last_sent_datetime', 'datetime', 'last_datetime',], ['settings']);

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Prepare the view */
        $data = [
            'flows' => $flows,
            'pagination' => $pagination,
            'filters' => $filters,
        ];

        $view = new \Altum\View('admin/flows/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('admin/flows');
        }

        if(empty($_POST['selected'])) {
            redirect('admin/flows');
        }

        if(!isset($_POST['type'])) {
            redirect('admin/flows');
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

                    foreach($_POST['selected'] as $flow_id) {
                        (new Flow())->delete($flow_id);
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('admin/flows');
    }

    public function delete() {

        $flow_id = (isset($this->params[0])) ? (int) $this->params[0] : null;

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$flow = db()->where('flow_id', $flow_id)->getOne('flows', ['flow_id', 'name'])) {
            redirect('admin/flows');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            (new Flow())->delete($flow_id);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $flow->name . '</strong>'));

        }

        redirect('admin/flows');
    }
}
