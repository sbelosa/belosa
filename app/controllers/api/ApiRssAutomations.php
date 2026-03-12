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

class ApiRssAutomations extends Controller {
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
        $filters->set_default_order_by($this->api_user->preferences->rss_automations_default_order_by, $this->api_user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->api_user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);
        $filters->process();

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `rss_automations` WHERE `user_id` = {$this->api_user->user_id}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('api/rss-automations?' . $filters->get_get() . '&page=%d')));

        /* Get the data */
        $data = [];
        $data_result = database()->query("
            SELECT
                *
            FROM
                `rss_automations`
            WHERE
                `user_id` = {$this->api_user->user_id}
                {$filters->get_sql_where()}
                {$filters->get_sql_order_by()}
                  
            {$paginator->get_sql_limit()}
        ");


        while($row = $data_result->fetch_object()) {

            /* Prepare the data */
            $row = [
                'id' => (int) $row->rss_automation_id,
                'user_id' => (int) $row->user_id,
                'website_id' => (int) $row->website_id,
                'name' => $row->name,
                'rss_url' => $row->rss_url,
                'title' => $row->title,
                'description' => $row->description,
                'url' => $row->url,
                'image_url' => $row->image ? \Altum\Uploads::get_full_url('websites_rss_automations_images') . $row->image : null,
                'segment' => $row->segment,
                'settings' => json_decode($row->settings),
                'is_enabled' => (bool) $row->is_enabled,
                'total_campaigns' => (int) $row->total_campaigns,
                'total_sent_push_notifications' => (int) $row->total_sent_push_notifications,
                'total_displayed_push_notifications' => (int) $row->total_displayed_push_notifications,
                'total_clicked_push_notifications' => (int) $row->total_clicked_push_notifications,
                'total_closed_push_notifications' => (int) $row->total_closed_push_notifications,
                'last_check_datetime' => $row->last_check_datetime,
                'next_check_datetime' => $row->next_check_datetime,
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

        $rss_automation_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $rss_automation = db()->where('rss_automation_id', $rss_automation_id)->where('user_id', $this->api_user->user_id)->getOne('rss_automations');

        /* We haven't found the resource */
        if(!$rss_automation) {
            $this->return_404();
        }

        /* Prepare the data */
        $data = [
            'id' => (int) $rss_automation->rss_automation_id,
            'user_id' => (int) $rss_automation->user_id,
            'website_id' => (int) $rss_automation->website_id,
            'name' => $rss_automation->name,
            'rss_url' => $rss_automation->rss_url,
            'title' => $rss_automation->title,
            'description' => $rss_automation->description,
            'url' => $rss_automation->url,
            'image_url' => $rss_automation->image ? \Altum\Uploads::get_full_url('websites_rss_automations_images') . $rss_automation->image : null,
            'segment' => $rss_automation->segment,
            'settings' => json_decode($rss_automation->settings),
            'is_enabled' => (bool) $rss_automation->is_enabled,
            'total_campaigns' => (int) $rss_automation->total_campaigns,
            'total_sent_push_notifications' => (int) $rss_automation->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $rss_automation->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $rss_automation->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $rss_automation->total_closed_push_notifications,
            'last_check_datetime' => $rss_automation->last_check_datetime,
            'next_check_datetime' => $rss_automation->next_check_datetime,
            'last_datetime' => $rss_automation->last_datetime,
            'datetime' => $rss_automation->datetime
        ];

        Response::jsonapi_success($data);

    }

    private function post() {

        /* Check for any errors */
        $required_fields = ['website_id', 'name', 'title', 'description', 'rss_url'];
        foreach($required_fields as $field) {
            if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                $this->response_error(l('global.error_message.empty_fields'), 401);
                break 1;
            }
        }

        /* Check for the plan limit */
        $total_rows = db()->where('user_id', $this->api_user->user_id)->getValue('rss_automations', 'count(*)');
        if($this->api_user->plan_settings->rss_automations_limit != -1 && $total_rows >= $this->api_user->plan_settings->rss_automations_limit) {
            $this->response_error(l('global.info_message.plan_feature_limit'), 401);
        }

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->api_user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* RSS automation check intervals */
        $rss_automations_check_intervals = require APP_PATH . 'includes/rss_automations_check_intervals.php';

        /* Filter some of the variables */
        $_POST['name'] = input_clean($_POST['name'], 256);
        $_POST['title'] = input_clean($_POST['title'], 64);
        $_POST['description'] = input_clean($_POST['description'], 128);
        $_POST['url'] = get_url($_POST['url'] ?? '', 512);
        $_POST['website_id'] = array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : array_key_first($websites);
        $_POST['rss_url'] = get_url($_POST['rss_url'], 512);
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

        /* RSS */
        $_POST['check_interval_seconds'] = isset($_POST['check_interval_seconds']) && array_key_exists($_POST['check_interval_seconds'], $rss_automations_check_intervals) ? (int) $_POST['check_interval_seconds'] : array_key_last($rss_automations_check_intervals);
        $_POST['items_count'] = isset($_POST['items_count']) && in_array($_POST['items_count'], range(1, 100)) ? (int) $_POST['items_count'] : 1;
        $_POST['campaigns_delay'] = isset($_POST['campaigns_delay']) && in_array($_POST['campaigns_delay'], range(5, 1440)) ? (int) $_POST['campaigns_delay'] : 1;
        $_POST['unique_item_identifier'] = isset($_POST['unique_item_identifier']) && in_array($_POST['unique_item_identifier'], ['url', 'publication_date', 'id']) ? $_POST['unique_item_identifier'] : 'url';
        $_POST['use_rss_image'] = (int) isset($_POST['use_rss_image']);
        $_POST['disable_rss_automation_on_fail'] = (int) isset($_POST['disable_rss_automation_on_fail']);

        $rss_data = rss_feed_parse_url($_POST['rss_url']);

        if(!$rss_data) {
            $this->response_error(l('rss_automations.error_message.invalid_rss_url'));
        }

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload(null, 'websites_rss_automations_images', 'image', 'image_remove', settings()->websites->rss_automation_image_size_limit, 'json_error');

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

            /* Rss */
            'check_interval_seconds' => $_POST['check_interval_seconds'],
            'items_count' => $_POST['items_count'],
            'campaigns_delay' => $_POST['campaigns_delay'],
            'unique_item_identifier' => $_POST['unique_item_identifier'],
            'use_rss_image' => $_POST['use_rss_image'],
        ];

        /* Database query */
        $rss_automation_id = db()->insert('rss_automations', [
            'website_id' => $_POST['website_id'],
            'user_id' => $this->api_user->user_id,
            'rss_url' => $_POST['rss_url'],
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image' => $image,
            'segment' => $_POST['segment'],
            'settings' => json_encode($settings),
            'is_enabled' => $_POST['is_enabled'],
            'next_check_datetime' => get_date(),
            'datetime' => get_date(),
        ]);

        /* Prepare the data */
        $data = [
            'id' => $rss_automation_id,
            'user_id' => (int) $this->api_user->user_id,
            'website_id' => (int) $_POST['website_id'],
            'name' => $_POST['name'],
            'rss_url' => $_POST['rss_url'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_rss_automations_images') . $image : null,
            'segment' => $_POST['segment'],
            'settings' => $settings,
            'is_enabled' => (bool) $_POST['is_enabled'],
            'total_campaigns' => 0,
            'total_sent_push_notifications' => 0,
            'total_displayed_push_notifications' => 0,
            'total_clicked_push_notifications' => 0,
            'total_closed_push_notifications' => 0,
            'last_check_datetime' => null,
            'next_check_datetime' => get_date(),
            'last_datetime' => null,
            'datetime' => get_date(),
        ];

        Response::jsonapi_success($data, null, 201);

    }

    private function patch() {

        /* Check for the plan limit */
        $total_rows = db()->where('user_id', $this->api_user->user_id)->getValue('rss_automations', 'count(`rss_automation_id`)');

        if($this->api_user->plan_settings->rss_automations_limit != -1 && $total_rows > $this->api_user->plan_settings->rss_automations_limit) {
            $this->response_error(sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), $total_rows - $this->user->plan_settings->rss_automations_limit, mb_strtolower(l('rss_automations.title')), l('global.info_message.plan_upgrade')), 401);
        }

        $rss_automation_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $rss_automation = db()->where('rss_automation_id', $rss_automation_id)->where('user_id', $this->api_user->user_id)->getOne('rss_automations');

        /* We haven't found the resource */
        if(!$rss_automation) {
            $this->return_404();
        }

        $rss_automation->settings = json_decode($rss_automation->settings ?? '');

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

        /* RSS automation check intervals */
        $rss_automations_check_intervals = require APP_PATH . 'includes/rss_automations_check_intervals.php';

        /* Filter some of the variables */
        $_POST['name'] = input_clean($_POST['name'] ?? $rss_automation->name, 256);
        $_POST['title'] = input_clean($_POST['title'] ?? $rss_automation->title, 64);
        $_POST['description'] = input_clean($_POST['description'] ?? $rss_automation->description, 128);
        $_POST['url'] = get_url($_POST['url'] ?? $rss_automation->url, 512);
        $_POST['website_id'] = isset($_POST['website_id']) && array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : $rss_automation->website_id;
        $_POST['is_enabled'] = isset($_POST['is_enabled']) ? (int) (bool) $_POST['is_enabled'] : $rss_automation->is_enabled;
        $_POST['rss_url'] = get_url($_POST['rss_url'] ?? $rss_automation->rss_url, 512);

        /* Segment */
        $_POST['segment'] = isset($_POST['segment']) ? $_POST['segment'] : $rss_automation->segment;
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
        $_POST['ttl'] = isset($_POST['ttl']) && array_key_exists($_POST['ttl'], $notifications_ttl) ? (int) $_POST['ttl'] : $rss_automation->settings->ttl;
        $_POST['urgency'] = isset($_POST['urgency']) && in_array($_POST['urgency'], ['low', 'normal', 'high']) ? $_POST['urgency'] : $rss_automation->settings->urgency;
        $_POST['is_silent'] = (int) (bool) ($_POST['is_silent'] ?? $rss_automation->settings->is_silent);
        $_POST['is_auto_hide'] = (int) (bool) ($_POST['is_auto_hide'] ?? $rss_automation->settings->is_auto_hide);

        /* Buttons */
        $_POST['button_title_1'] = input_clean($_POST['button_title_1'] ?? $rss_automation->settings->button_title_1, 16);
        $_POST['button_url_1'] = get_url($_POST['button_url_1'] ?? $rss_automation->settings->button_url_1, 512);
        $_POST['button_title_2'] = input_clean($_POST['button_title_2'] ?? $rss_automation->settings->button_title_2, 16);
        $_POST['button_url_2'] = get_url($_POST['button_url_2'] ?? $rss_automation->settings->button_url_2, 512);

        /* UTM */
        $_POST['utm_medium'] = input_clean($_POST['utm_medium'] ?? $rss_automation->settings->utm->medium, 128);
        $_POST['utm_source'] = input_clean($_POST['utm_source'] ?? $rss_automation->settings->utm->source, 128);
        $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'] ?? $rss_automation->settings->utm->campaign, 128);

        /* RSS */
        $_POST['check_interval_seconds'] = isset($_POST['check_interval_seconds']) && array_key_exists($_POST['check_interval_seconds'], $rss_automations_check_intervals) ? (int) $_POST['check_interval_seconds'] : $rss_automation->settings->check_interval_seconds;
        $_POST['items_count'] = isset($_POST['items_count']) && in_array($_POST['items_count'], range(1, 100)) ? (int) $_POST['items_count'] : $rss_automation->settings->items_count;
        $_POST['campaigns_delay'] = isset($_POST['campaigns_delay']) && in_array($_POST['campaigns_delay'], range(5, 1440)) ? (int) $_POST['campaigns_delay'] : $rss_automation->settings->campaigns_delay;
        $_POST['unique_item_identifier'] = isset($_POST['unique_item_identifier']) && in_array($_POST['unique_item_identifier'], ['url', 'publication_date', 'id']) ? $_POST['unique_item_identifier'] : $rss_automation->settings->unique_item_identifier;
        $_POST['use_rss_image'] = (int) (bool) ($_POST['use_rss_image'] ?? $rss_automation->settings->use_rss_image);
        $_POST['disable_rss_automation_on_fail'] = (int) (bool) ($_POST['disable_rss_automation_on_fail'] ?? $rss_automation->settings->disable_rss_automation_on_fail);

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload($rss_automation->image, 'websites_rss_automations_images', 'image', 'image_remove', settings()->websites->rss_automation_image_size_limit, 'json_error');

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

            /* Rss */
            'check_interval_seconds' => $_POST['check_interval_seconds'],
            'items_count' => $_POST['items_count'],
            'campaigns_delay' => $_POST['campaigns_delay'],
            'unique_item_identifier' => $_POST['unique_item_identifier'],
            'use_rss_image' => $_POST['use_rss_image'],
        ];

        /* Database query */
        db()->where('rss_automation_id', $rss_automation->rss_automation_id)->update('rss_automations', [
            'website_id' => $_POST['website_id'],
            'user_id' => $this->api_user->user_id,
            'name' => $_POST['name'],
            'rss_url' => $_POST['rss_url'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image' => $image,
            'segment' => $_POST['segment'],
            'settings' => json_encode($settings),
            'is_enabled' => $_POST['is_enabled'],
            'next_check_datetime' => get_date(),
            'last_datetime' => get_date(),
        ]);


        /* Prepare the data */
        $data = [
            'id' => $rss_automation->rss_automation_id,
            'user_id' => (int) $this->api_user->user_id,
            'website_id' => (int) $_POST['website_id'],
            'name' => $_POST['name'],
            'rss_url' => $_POST['rss_url'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_rss_automations_images') . $image : null,
            'segment' => $_POST['segment'],
            'settings' => $settings,
            'is_enabled' => (bool) $_POST['is_enabled'],
            'total_campaigns' => (int) $rss_automation->total_campaigns,
            'total_sent_push_notifications' => (int) $rss_automation->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $rss_automation->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $rss_automation->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $rss_automation->total_closed_push_notifications,
            'last_check_datetime' => $rss_automation->last_check_datetime,
            'next_check_datetime' => $rss_automation->next_check_datetime,
            'last_datetime' => get_date(),
            'datetime' => $rss_automation->datetime,
        ];

        Response::jsonapi_success($data, null, 200);

    }

    private function delete() {

        $rss_automation_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $rss_automation = db()->where('rss_automation_id', $rss_automation_id)->where('user_id', $this->api_user->user_id)->getOne('rss_automations');

        /* We haven't found the resource */
        if(!$rss_automation) {
            $this->return_404();
        }

        /* Delete the resource */
        (new \Altum\Models\RssAutomation())->delete($rss_automation_id);

        http_response_code(200);
        die();

    }
}
