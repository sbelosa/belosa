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
use Altum\Models\Campaign;

defined('ALTUMCODE') || die();

class AdminCampaigns extends Controller {

    public function index() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['website_id', 'status', 'segment', 'user_id', 'rss_automation_id'], ['name', 'title', 'description', 'url',], ['campaign_id', 'name', 'title', 'datetime', 'last_sent_datetime', 'scheduled_datetime', 'last_datetime', 'total_push_notifications', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications']));
        $filters->set_default_order_by($this->user->preferences->campaigns_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `campaigns` WHERE 1 = 1 {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('admin/campaigns?' . $filters->get_get() . '&page=%d')));

        /* Get the campaigns list for the user */
        $campaigns = [];
        $campaigns_result = database()->query("
            SELECT
                `campaigns`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar`, `websites`.`host`, `websites`.`path`
            FROM
                `campaigns`
            LEFT JOIN
                `users` ON `campaigns`.`user_id` = `users`.`user_id`
            LEFT JOIN
                `websites` ON `campaigns`.`website_id` = `websites`.`website_id`
            WHERE
                1 = 1
                {$filters->get_sql_where('campaigns')}
                {$filters->get_sql_order_by('campaigns')}
            
            {$paginator->get_sql_limit()}
        ");
        while($row = $campaigns_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $campaigns[] = $row;
        }

        /* Export handler */
        process_export_json($campaigns, ['campaign_id', 'user_id', 'website_id', 'rss_automation_id', 'recurring_campaign_id', 'name', 'title', 'description', 'url', 'settings', 'segment', 'status', 'subscribers_ids', 'sent_subscribers_ids', 'total_push_notifications', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'scheduled_datetime', 'last_sent_datetime', 'datetime', 'last_datetime',]);
        process_export_csv_new($campaigns, ['campaign_id', 'user_id', 'website_id', 'rss_automation_id', 'recurring_campaign_id', 'name', 'title', 'description', 'url', 'settings', 'segment', 'status', 'subscribers_ids', 'sent_subscribers_ids', 'total_push_notifications', 'total_sent_push_notifications', 'total_displayed_push_notifications', 'total_clicked_push_notifications', 'total_closed_push_notifications', 'scheduled_datetime', 'last_sent_datetime', 'datetime', 'last_datetime',], ['settings']);

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Prepare the view */
        $data = [
            'campaigns' => $campaigns,
            'pagination' => $pagination,
            'filters' => $filters,
        ];

        $view = new \Altum\View('admin/campaigns/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('admin/campaigns');
        }

        if(empty($_POST['selected'])) {
            redirect('admin/campaigns');
        }

        if(!isset($_POST['type'])) {
            redirect('admin/campaigns');
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

                    foreach($_POST['selected'] as $campaign_id) {
                        (new Campaign())->delete($campaign_id);
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('admin/campaigns');
    }

    public function delete() {

        $campaign_id = (isset($this->params[0])) ? (int) $this->params[0] : null;

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        if(!\Altum\Csrf::check('global_token')) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$campaign = db()->where('campaign_id', $campaign_id)->getOne('campaigns', ['campaign_id', 'name'])) {
            redirect('admin/campaigns');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            (new Campaign())->delete($campaign_id);

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $campaign->name . '</strong>'));

        }

        redirect('admin/campaigns');
    }
}
