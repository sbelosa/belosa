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

class Websites extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['user_id', 'domain_id', 'is_enabled'], ['host', 'path', 'name'], ['website_id', 'name', 'last_datetime','datetime', 'host', 'path', 'total_sent_campaigns', 'total_subscribers', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications']));
        $filters->set_default_order_by($this->user->preferences->websites_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `websites` WHERE `user_id` = {$this->user->user_id} {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('websites?' . $filters->get_get() . '&page=%d')));

        /* Generate stats */
        $websites_stats = [
            'total_sent_push_notifications' => 0,
            'total_displayed_push_notifications' => 0,
            'total_clicked_push_notifications' => 0,
            'total_closed_push_notifications' => 0,
        ];

        /* Get the websites list for the user */
        $websites = [];
        $websites_result = database()->query("SELECT * FROM `websites` WHERE `user_id` = {$this->user->user_id} {$filters->get_sql_where()} {$filters->get_sql_order_by()} {$paginator->get_sql_limit()}");
        while($row = $websites_result->fetch_object()) {
            $websites_stats['total_sent_push_notifications'] += $row->total_sent_push_notifications;
            $websites_stats['total_displayed_push_notifications'] += $row->total_displayed_push_notifications;
            $websites_stats['total_clicked_push_notifications'] += $row->total_clicked_push_notifications;
            $websites_stats['total_closed_push_notifications'] += $row->total_closed_push_notifications;

            $row->settings = json_decode($row->settings ?? '');
            $row->keys = json_decode($row->keys ?? '');
            $row->notifications = json_decode($row->notifications ?? '');

            $websites[] = $row;
        }

        /* Export handler */
        process_export_csv_new($websites, ['website_id', 'user_id', 'domain_id', 'pixel_key', 'name', 'scheme', 'host', 'path', 'settings', 'notifications', 'keys', 'total_sent_campaigns', 'total_subscribers', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'is_enabled', 'last_datetime', 'datetime'], ['settings', 'notifications', 'keys'], sprintf(l('websites.title')));
        process_export_json($websites, ['website_id', 'user_id', 'domain_id', 'pixel_key', 'name', 'scheme', 'host', 'path', 'settings', 'notifications', 'keys', 'total_sent_campaigns', 'total_subscribers', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'is_enabled', 'last_datetime', 'datetime'], sprintf(l('websites.title')));

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Get available custom domains */
        $domains = (new \Altum\Models\Domain())->get_available_domains_by_user($this->user);

        /* Prepare the view */
        $data = [
            'websites' => $websites,
            'websites_stats' => $websites_stats,
            'total_websites' => $total_rows,
            'domains' => $domains,
            'pagination' => $pagination,
            'filters' => $filters,
        ];

        $view = new \Altum\View('websites/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        \Altum\Authentication::guard();

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('websites');
        }

        if(empty($_POST['selected'])) {
            redirect('websites');
        }

        if(!isset($_POST['type'])) {
            redirect('websites');
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
                    if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.websites')) {
                        Alerts::add_error(l('global.info_message.team_no_access'));
                        redirect('websites');
                    }

                    foreach($_POST['selected'] as $website_id) {
                        if($website = db()->where('website_id', $website_id)->where('user_id', $this->user->user_id)->getOne('websites', ['website_id'])) {

                            (new Website())->delete($website_id);

                        }
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('websites');
    }

    public function reset() {
        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.websites')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('websites');
        }

        if(empty($_POST)) {
            redirect('websites');
        }

        $website_id = (int) query_clean($_POST['website_id']);

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('websites');
        }

        /* Make sure the link id is created by the logged in user */
        if(!$website = db()->where('website_id', $website_id)->where('user_id', $this->user->user_id)->getOne('websites', ['website_id'])) {
            redirect('websites');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Reset data */
            db()->where('website_id', $website_id)->update('websites', [
                'total_sent_campaigns' => 0,
                'total_sent_push_notifications' => 0,
                'total_displayed_push_notifications' => 0,
                'total_clicked_push_notifications' => 0,
                'total_closed_push_notifications' => 0,
            ]);

            /* Clear the cache */
            cache()->deleteItem('websites?user_id=' . $this->user->user_id);
            cache()->deleteItem('website?website_id=' . $website->website_id);
            cache()->deleteItem('website?pixel_key=' . md5($website->pixel_key));

            /* Set a nice success message */
            Alerts::add_success(l('global.success_message.update2'));

            redirect('websites');

        }

        redirect('websites');
    }

    public function delete() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.websites')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('websites');
        }

        if(empty($_POST)) {
            redirect('websites');
        }

        $website_id = (int) query_clean($_POST['website_id']);

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$website = db()->where('website_id', $website_id)->where('user_id', $this->user->user_id)->getOne('websites', ['website_id', 'host'])) {
            redirect('websites');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            (new Website())->delete($website_id);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $website->host . '</strong>'));

            redirect('websites');
        }

        redirect('websites');
    }
}
