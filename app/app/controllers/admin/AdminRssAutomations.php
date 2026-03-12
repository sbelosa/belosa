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

class AdminRssAutomations extends Controller {

    public function index() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['user_id', 'website_id', 'segment'], ['name', 'title', 'description', 'url', 'rss_url'], ['rss_automation_id', 'name', 'title', 'last_check_datetime', 'next_check_datetime', 'datetime', 'last_datetime', 'total_push_notifications', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'total_campaigns']));
        $filters->set_default_order_by($this->user->preferences->rss_automations_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `rss_automations` WHERE 1 = 1 {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('admin/rss_automations?' . $filters->get_get() . '&page=%d')));

        /* Get the rss_automations list for the user */
        $rss_automations = [];
        $rss_automations_result = database()->query("
            SELECT
                `rss_automations`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar`, `websites`.`host`, `websites`.`path`
            FROM
                `rss_automations`
            LEFT JOIN
                `users` ON `rss_automations`.`user_id` = `users`.`user_id`
            LEFT JOIN
                `websites` ON `rss_automations`.`website_id` = `websites`.`website_id`
            WHERE
                1 = 1
                {$filters->get_sql_where('rss_automations')}
                {$filters->get_sql_order_by('rss_automations')}
            
            {$paginator->get_sql_limit()}
        ");
        while($row = $rss_automations_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $rss_automations[] = $row;
        }

        /* Export handler */
        process_export_json($rss_automations, ['rss_automation_id', 'website_id', 'user_id', 'name', 'rss_url', 'title', 'description', 'url', 'image', 'segment', 'settings', 'rss_last_entries', 'is_enabled', 'total_campaigns', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'last_check_datetime', 'next_check_datetime', 'datetime', 'last_datetime',]);
        process_export_csv_new($rss_automations, ['rss_automation_id', 'website_id', 'user_id', 'name', 'rss_url', 'title', 'description', 'url', 'image', 'segment', 'settings', 'is_enabled', 'total_campaigns', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'last_check_datetime', 'next_check_datetime', 'datetime', 'last_datetime',], ['settings']);

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Prepare the view */
        $data = [
            'rss_automations' => $rss_automations,
            'pagination' => $pagination,
            'filters' => $filters,
        ];

        $view = new \Altum\View('admin/rss-automations/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('admin/rss-automations');
        }

        if(empty($_POST['selected'])) {
            redirect('admin/rss-automations');
        }

        if(!isset($_POST['type'])) {
            redirect('admin/rss-automations');
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

                    foreach($_POST['selected'] as $rss_automation_id) {
                        (new \Altum\Models\RssAutomation())->delete($rss_automation_id);
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('admin/rss-automations');
    }

    public function delete() {

        $rss_automation_id = (isset($this->params[0])) ? (int) $this->params[0] : null;

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$rss_automation = db()->where('rss_automation_id', $rss_automation_id)->getOne('rss_automations', ['rss_automation_id', 'name'])) {
            redirect('admin/rss-automations');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            (new \Altum\Models\RssAutomation())->delete($rss_automation_id);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $rss_automation->name . '</strong>'));

        }

        redirect('admin/rss-automations');
    }
}
