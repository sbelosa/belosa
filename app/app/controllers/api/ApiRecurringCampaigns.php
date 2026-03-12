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

use Altum\Response;
use Altum\Traits\Apiable;

defined('ALTUMCODE') || die();

class ApiRecurringCampaigns extends Controller {
    use Apiable;

    public function index() {

        $this->verify_request();

        /* Decide what to continue with */
        switch($_SERVER['REQUEST_METHOD']) {
            case 'GET':

                /* Detect if we only need an object, or the whole list */
                if(isset($this->params[0])) {
                    $this->get();
                } else {
                    $this->get_all();
                }

                break;

            case 'POST':

                /* Detect what method to use */
                if(isset($this->params[0])) {
                    $this->patch();
                } else {
                    $this->post();
                }

                break;

            case 'DELETE':
                $this->delete();
                break;
        }

        $this->return_404();
    }

    private function get_all() {

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters([], [], []));
        $filters->set_default_order_by($this->api_user->preferences->recurring_campaigns_default_order_by, $this->api_user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->api_user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);
        $filters->process();

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `recurring_campaigns` WHERE `user_id` = {$this->api_user->user_id}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('api/recurring-campaigns?' . $filters->get_get() . '&page=%d')));

        /* Get the data */
        $data = [];
        $data_result = database()->query("
            SELECT
                *
            FROM
                `recurring_campaigns`
            WHERE
                `user_id` = {$this->api_user->user_id}
                {$filters->get_sql_where()}
                {$filters->get_sql_order_by()}
                  
            {$paginator->get_sql_limit()}
        ");


        while($row = $data_result->fetch_object()) {

            /* Prepare the data */
            $row = [
                'id' => (int) $row->recurring_campaign_id,
                'user_id' => (int) $row->user_id,
                'website_id' => (int) $row->website_id,
                'name' => $row->name,
                'title' => $row->title,
                'description' => $row->description,
                'url' => $row->url,
                'image_url' => $row->image ? \Altum\Uploads::get_full_url('websites_recurring_campaigns_images') . $row->image : null,
                'segment' => $row->segment,
                'settings' => json_decode($row->settings),
                'is_enabled' => (bool) $row->is_enabled,
                'total_campaigns' => (int) $row->total_campaigns,
                'total_sent_push_notifications' => (int) $row->total_sent_push_notifications,
                'total_displayed_push_notifications' => (int) $row->total_displayed_push_notifications,
                'total_clicked_push_notifications' => (int) $row->total_clicked_push_notifications,
                'total_closed_push_notifications' => (int) $row->total_closed_push_notifications,
                'last_run_datetime' => $row->last_run_datetime,
                'next_run_datetime' => $row->next_run_datetime,
                'last_datetime' => $row->last_datetime,
                'datetime' => $row->datetime
            ];

            $data[] = $row;
        }

        /* Prepare the data */
        $meta = [
            'page' => $_GET['page'] ?? 1,
            'total_pages' => $paginator->getNumPages(),
            'results_per_page' => $filters->get_results_per_page(),
            'total_results' => (int) $total_rows,
        ];

        /* Prepare the pagination links */
        $others = ['links' => [
            'first' => $paginator->getPageUrl(1),
            'last' => $paginator->getNumPages() ? $paginator->getPageUrl($paginator->getNumPages()) : null,
            'next' => $paginator->getNextUrl(),
            'prev' => $paginator->getPrevUrl(),
            'self' => $paginator->getPageUrl($_GET['page'] ?? 1)
        ]];

        Response::jsonapi_success($data, $meta, 200, $others);
    }

    private function get() {

        $recurring_campaign_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $recurring_campaign = db()->where('recurring_campaign_id', $recurring_campaign_id)->where('user_id', $this->api_user->user_id)->getOne('recurring_campaigns');

        /* We haven't found the resource */
        if(!$recurring_campaign) {
            $this->return_404();
        }

        /* Prepare the data */
        $data = [
            'id' => (int) $recurring_campaign->recurring_campaign_id,
            'user_id' => (int) $recurring_campaign->user_id,
            'website_id' => (int) $recurring_campaign->website_id,
            'name' => $recurring_campaign->name,
            'title' => $recurring_campaign->title,
            'description' => $recurring_campaign->description,
            'url' => $recurring_campaign->url,
            'image_url' => $recurring_campaign->image ? \Altum\Uploads::get_full_url('websites_recurring_campaigns_images') . $recurring_campaign->image : null,
            'segment' => $recurring_campaign->segment,
            'settings' => json_decode($recurring_campaign->settings),
            'is_enabled' => (bool) $recurring_campaign->is_enabled,
            'total_campaigns' => (int) $recurring_campaign->total_campaigns,
            'total_sent_push_notifications' => (int) $recurring_campaign->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $recurring_campaign->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $recurring_campaign->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $recurring_campaign->total_closed_push_notifications,
            'last_run_datetime' => $recurring_campaign->last_run_datetime,
            'next_run_datetime' => $recurring_campaign->next_run_datetime,
            'last_datetime' => $recurring_campaign->last_datetime,
            'datetime' => $recurring_campaign->datetime
        ];

        Response::jsonapi_success($data);

    }

    private function post() {

        /* Check for any errors */
        $required_fields = ['website_id', 'name', 'title', 'description'];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $this->response_error(l('global.error_message.empty_fields'), 401);
                break 1;
            }
        }

        /* Check for the plan limit */
        $total_rows = db()->where('user_id', $this->api_user->user_id)->getValue('recurring_campaigns', 'count(*)');
        if($this->api_user->plan_settings->recurring_campaigns_limit != -1 && $total_rows >= $this->api_user->plan_settings->recurring_campaigns_limit) {
            $this->response_error(l('global.info_message.plan_feature_limit'), 401);
        }

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->api_user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* Filter some of the variables */
        $_POST['name'] = input_clean($_POST['name'], 256);
        $_POST['title'] = input_clean($_POST['title'], 64);
        $_POST['description'] = input_clean($_POST['description'], 128);
        $_POST['url'] = get_url($_POST['url'] ?? '', 512);
        $_POST['website_id'] = array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : array_key_first($websites);
        $_POST['is_enabled'] = isset($_POST['is_enabled']) ? (int) (bool) $_POST['is_enabled'] : 1;

        /* Segment */
        $_POST['segment'] = $_POST['segment'] ?? null;
        if(is_numeric($_POST['segment'])) {

            /* Get settings from custom segments */
            $segment = (new \Altum\Models\Segment())->get_segment_by_segment_id($_POST['segment']);

            if(!$segment || $_POST['website_id'] != $segment->website_id) {
                $_POST['segment'] = 'all';
            }

        } else {
            $_POST['segment'] = in_array($_POST['segment'], ['all']) ? input_clean($_POST['segment']) : 'all';
        }

        /* Advanced */
        $_POST['ttl'] = isset($_POST['ttl']) && array_key_exists($_POST['ttl'], $notifications_ttl) ? (int) $_POST['ttl'] : array_key_last($notifications_ttl);
        $_POST['urgency'] = isset($_POST['urgency']) && in_array($_POST['urgency'], ['low', 'normal', 'high']) ? $_POST['urgency'] : 'normal';
        $_POST['is_silent'] = (int) isset($_POST['is_silent']);
        $_POST['is_auto_hide'] = (int) isset($_POST['is_auto_hide']);

        /* Buttons */
        $_POST['button_title_1'] = input_clean($_POST['button_title_1'] ?? null, 16);
        $_POST['button_url_1'] = get_url($_POST['button_url_1'] ?? null, 512);
        $_POST['button_title_2'] = input_clean($_POST['button_title_2'] ?? null, 16);
        $_POST['button_url_2'] = get_url($_POST['button_url_2'] ?? null, 512);

        /* UTM */
        $_POST['utm_medium'] = input_clean($_POST['utm_medium'] ?? '', 128);
        $_POST['utm_source'] = input_clean($_POST['utm_source'] ?? '', 128);
        $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'] ?? '', 128);

        /* Recurring settings */
        $_POST['frequency'] = isset($_POST['frequency']) && in_array($_POST['frequency'], ['daily', 'weekly', 'monthly']) ? $_POST['frequency'] : 'monthly';
        $_POST['time'] = preg_match('/^(2[0-3]|[01]?[0-9]):[0-5][0-9]$/', $_POST['time'] ?? '12:00') ? $_POST['time'] : '00:00';
        $_POST['week_days'] = array_map('intval', array_filter($_POST['week_days'] ?? [], fn($key) => in_array($key, range(1, 7))));
        $_POST['month_days'] = array_map('intval', array_filter($_POST['month_days'] ?? [], fn($key) => in_array($key, range(1, 31))));

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload(null, 'websites_recurring_campaigns_images', 'image', 'image_remove', settings()->websites->recurring_campaign_image_size_limit, 'json_error');

        $settings = [
            /* Advanced */
            'ttl' => $_POST['ttl'],
            'urgency' => $_POST['urgency'],
            'is_silent' => $_POST['is_silent'],
            'is_auto_hide' => $_POST['is_auto_hide'],

            /* Buttons */
            'button_title_1' => $_POST['button_title_1'],
            'button_url_1' => $_POST['button_url_1'],
            'button_title_2' => $_POST['button_title_2'],
            'button_url_2' => $_POST['button_url_2'],

            /* UTM */
            'utm' => [
                'source' => $_POST['utm_source'],
                'medium' => $_POST['utm_medium'],
                'campaign' => $_POST['utm_campaign'],
            ],

            /* Recurring settings */
            'frequency' => $_POST['frequency'],
            'time' => $_POST['time'],
            'week_days' => $_POST['week_days'],
            'month_days' => $_POST['month_days'],
        ];

        /* Calculate the next run */
        $next_run_datetime = get_next_run_datetime($_POST['frequency'], $_POST['time'], $_POST['week_days'], $_POST['month_days'], $this->api_user->timezone, '-15 minutes');

        /* Database query */
        $recurring_campaign_id = db()->insert('recurring_campaigns', [
            'website_id' => $_POST['website_id'],
            'user_id' => $this->api_user->user_id,
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image' => $image,
            'segment' => $_POST['segment'],
            'settings' => json_encode($settings),
            'is_enabled' => $_POST['is_enabled'],
            'next_run_datetime' => $next_run_datetime,
            'datetime' => get_date(),
        ]);

        /* Prepare the data */
        $data = [
            'id' => $recurring_campaign_id,
            'user_id' => (int) $this->api_user->user_id,
            'website_id' => (int) $_POST['website_id'],
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_recurring_campaigns_images') . $image : null,
            'segment' => $_POST['segment'],
            'settings' => $settings,
            'is_enabled' => (bool) $_POST['is_enabled'],
            'total_campaigns' => 0,
            'total_sent_push_notifications' => 0,
            'total_displayed_push_notifications' => 0,
            'total_clicked_push_notifications' => 0,
            'total_closed_push_notifications' => 0,
            'last_run_datetime' => null,
            'next_run_datetime' => $next_run_datetime,
            'last_datetime' => null,
            'datetime' => get_date(),
        ];

        Response::jsonapi_success($data, null, 201);

    }

    private function patch() {

        /* Check for the plan limit */
        $total_rows = db()->where('user_id', $this->api_user->user_id)->getValue('recurring_campaigns', 'count(`recurring_campaign_id`)');

        if($this->api_user->plan_settings->recurring_campaigns_limit != -1 && $total_rows > $this->api_user->plan_settings->recurring_campaigns_limit) {
            $this->response_error(sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), $total_rows - $this->user->plan_settings->recurring_campaigns_limit, mb_strtolower(l('recurring_campaigns.title')), l('global.info_message.plan_upgrade')), 401);
        }

        $recurring_campaign_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $recurring_campaign = db()->where('recurring_campaign_id', $recurring_campaign_id)->where('user_id', $this->api_user->user_id)->getOne('recurring_campaigns');

        /* We haven't found the resource */
        if(!$recurring_campaign) {
            $this->return_404();
        }

        $recurring_campaign->settings = json_decode($recurring_campaign->settings ?? '');

        /* Check for any errors */
        $required_fields = [];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $this->response_error(l('global.error_message.empty_fields'), 401);
                break 1;
            }
        }

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->api_user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* Filter some of the variables */
        $_POST['name'] = input_clean($_POST['name'] ?? $recurring_campaign->name, 256);
        $_POST['title'] = input_clean($_POST['title'] ?? $recurring_campaign->title, 64);
        $_POST['description'] = input_clean($_POST['description'] ?? $recurring_campaign->description, 128);
        $_POST['url'] = get_url($_POST['url'] ?? $recurring_campaign->url, 512);
        $_POST['website_id'] = isset($_POST['website_id']) && array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : $recurring_campaign->website_id;
        $_POST['is_enabled'] = isset($_POST['is_enabled']) ? (int) (bool) $_POST['is_enabled'] : $recurring_campaign->is_enabled;

        /* Segment */
        $_POST['segment'] = isset($_POST['segment']) ? $_POST['segment'] : $recurring_campaign->segment;
        if(is_numeric($_POST['segment'])) {

            /* Get settings from custom segments */
            $segment = (new \Altum\Models\Segment())->get_segment_by_segment_id($_POST['segment']);

            if(!$segment || $_POST['website_id'] != $segment->website_id) {
                $_POST['segment'] = 'all';
            }

        } else {
            $_POST['segment'] = in_array($_POST['segment'], ['all']) ? input_clean($_POST['segment']) : 'all';
        }

        /* Advanced */
        $_POST['ttl'] = isset($_POST['ttl']) && array_key_exists($_POST['ttl'], $notifications_ttl) ? (int) $_POST['ttl'] : $recurring_campaign->settings->ttl;
        $_POST['urgency'] = isset($_POST['urgency']) && in_array($_POST['urgency'], ['low', 'normal', 'high']) ? $_POST['urgency'] : $recurring_campaign->settings->urgency;
        $_POST['is_silent'] = (int) (bool) ($_POST['is_silent'] ?? $recurring_campaign->settings->is_silent);
        $_POST['is_auto_hide'] = (int) (bool) ($_POST['is_auto_hide'] ?? $recurring_campaign->settings->is_auto_hide);

        /* Buttons */
        $_POST['button_title_1'] = input_clean($_POST['button_title_1'] ?? $recurring_campaign->settings->button_title_1, 16);
        $_POST['button_url_1'] = get_url($_POST['button_url_1'] ?? $recurring_campaign->settings->button_url_1, 512);
        $_POST['button_title_2'] = input_clean($_POST['button_title_2'] ?? $recurring_campaign->settings->button_title_2, 16);
        $_POST['button_url_2'] = get_url($_POST['button_url_2'] ?? $recurring_campaign->settings->button_url_2, 512);

        /* UTM */
        $_POST['utm_medium'] = input_clean($_POST['utm_medium'] ?? $recurring_campaign->settings->utm->medium, 128);
        $_POST['utm_source'] = input_clean($_POST['utm_source'] ?? $recurring_campaign->settings->utm->source, 128);
        $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'] ?? $recurring_campaign->settings->utm->campaign, 128);

        /* Recurring settings */
        $_POST['frequency'] = isset($_POST['frequency']) && in_array($_POST['frequency'], ['daily', 'weekly', 'monthly']) ? $_POST['frequency'] : $recurring_campaign->settings->frequency;
        $_POST['time'] = isset($_POST['time']) && preg_match('/^(2[0-3]|[01]?[0-9]):[0-5][0-9]$/', $_POST['time']) ? $_POST['time'] : $recurring_campaign->settings->time;
        $_POST['week_days'] = array_map('intval', array_filter($_POST['week_days'] ?? $recurring_campaign->settings->week_days, fn($key) => in_array($key, range(1, 7))));
        $_POST['month_days'] = array_map('intval', array_filter($_POST['month_days'] ?? $recurring_campaign->settings->month_days, fn($key) => in_array($key, range(1, 31))));

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload($recurring_campaign->image, 'websites_recurring_campaigns_images', 'image', 'image_remove', settings()->websites->recurring_campaign_image_size_limit, 'json_error');

        $settings = [
            /* Advanced */
            'ttl' => $_POST['ttl'],
            'urgency' => $_POST['urgency'],
            'is_silent' => $_POST['is_silent'],
            'is_auto_hide' => $_POST['is_auto_hide'],

            /* Buttons */
            'button_title_1' => $_POST['button_title_1'],
            'button_url_1' => $_POST['button_url_1'],
            'button_title_2' => $_POST['button_title_2'],
            'button_url_2' => $_POST['button_url_2'],

            /* UTM */
            'utm' => [
                'source' => $_POST['utm_source'],
                'medium' => $_POST['utm_medium'],
                'campaign' => $_POST['utm_campaign'],
            ],

            /* Recurring settings */
            'frequency' => $_POST['frequency'],
            'time' => $_POST['time'],
            'week_days' => $_POST['week_days'],
            'month_days' => $_POST['month_days'],
        ];

        /* Calculate the next run */
        $next_run_datetime = get_next_run_datetime($_POST['frequency'], $_POST['time'], $_POST['week_days'], $_POST['month_days'], $this->api_user->timezone, '-15 minutes');

        /* Database query */
        db()->where('recurring_campaign_id', $recurring_campaign->recurring_campaign_id)->update('recurring_campaigns', [
            'website_id' => $_POST['website_id'],
            'user_id' => $this->api_user->user_id,
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image' => $image,
            'segment' => $_POST['segment'],
            'settings' => json_encode($settings),
            'is_enabled' => $_POST['is_enabled'],
            'next_run_datetime' => $next_run_datetime,
            'last_datetime' => get_date(),
        ]);

        /* Prepare the data */
        $data = [
            'id' => $recurring_campaign->recurring_campaign_id,
            'user_id' => (int) $this->api_user->user_id,
            'website_id' => (int) $_POST['website_id'],
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_recurring_campaigns_images') . $image : null,
            'segment' => $_POST['segment'],
            'settings' => $settings,
            'is_enabled' => (bool) $_POST['is_enabled'],
            'total_campaigns' => (int) $recurring_campaign->total_campaigns,
            'total_sent_push_notifications' => (int) $recurring_campaign->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $recurring_campaign->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $recurring_campaign->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $recurring_campaign->total_closed_push_notifications,
            'last_run_datetime' => $recurring_campaign->last_run_datetime,
            'next_run_datetime' => $next_run_datetime,
            'last_datetime' => get_date(),
            'datetime' => $recurring_campaign->datetime,
        ];

        Response::jsonapi_success($data, null, 200);

    }

    private function delete() {

        $recurring_campaign_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $recurring_campaign = db()->where('recurring_campaign_id', $recurring_campaign_id)->where('user_id', $this->api_user->user_id)->getOne('recurring_campaigns');

        /* We haven't found the resource */
        if(!$recurring_campaign) {
            $this->return_404();
        }

        /* Delete the resource */
        (new \Altum\Models\RecurringCampaign())->delete($recurring_campaign_id);

        http_response_code(200);
        die();

    }
}
