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
use Altum\Models\Website;

defined('ALTUMCODE') || die();

class AdminWebsites extends Controller {

    public function index() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['user_id', 'domain_id', 'is_enabled'], ['host', 'path', 'name'], ['website_id', 'name', 'last_datetime', 'host', 'path', 'datetime', 'total_sent_campaigns', 'total_subscribers', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications']));
        $filters->set_default_order_by($this->user->preferences->websites_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `websites` WHERE 1 = 1 {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('admin/websites?' . $filters->get_get() . '&page=%d')));

        /* Get the websites list for the user */
        $websites = [];
        $websites_result = database()->query("
            SELECT
                `websites`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar`
            FROM
                `websites`
            LEFT JOIN
                `users` ON `websites`.`user_id` = `users`.`user_id`
            LEFT JOIN
                `domains` ON `websites`.`domain_id` = `domains`.`domain_id`
            WHERE
                1 = 1
                {$filters->get_sql_where('websites')}
                {$filters->get_sql_order_by('websites')}
            
            {$paginator->get_sql_limit()}
        ");
        while($row = $websites_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $row->branding = json_decode($row->branding ?? '');
            $row->keys = json_decode($row->keys ?? '');

            $websites[] = $row;
        }

        /* Export handler */
        process_export_csv_new($websites, ['website_id', 'user_id', 'domain_id', 'pixel_key', 'name', 'scheme', 'host', 'path', 'settings', 'branding', 'keys', 'total_sent_campaigns', 'total_subscribers', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'is_enabled', 'last_datetime', 'datetime'], ['settings', 'branding', 'keys'], sprintf(l('websites.title')));
        process_export_json($websites, ['website_id', 'user_id', 'domain_id', 'pixel_key', 'name', 'scheme', 'host', 'path', 'settings', 'branding', 'keys', 'total_sent_campaigns', 'total_subscribers', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'is_enabled', 'last_datetime', 'datetime'], sprintf(l('websites.title')));

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Prepare the view */
        $data = [
            'websites' => $websites,
            'pagination' => $pagination,
            'filters' => $filters,
        ];

        $view = new \Altum\View('admin/websites/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('admin/websites');
        }

        if(empty($_POST['selected'])) {
            redirect('admin/websites');
        }

        if(!isset($_POST['type'])) {
            redirect('admin/websites');
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

                    foreach($_POST['selected'] as $website_id) {
                        (new Website())->delete($website_id);
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('admin/websites');
    }

    public function delete() {

        $website_id = (isset($this->params[0])) ? (int) $this->params[0] : null;

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$website = db()->where('website_id', $website_id)->getOne('websites', ['website_id', 'name'])) {
            redirect('admin/websites');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            (new Website())->delete($website->website_id);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $website->name . '</strong>'));

        }

        redirect('admin/websites');
    }
}
