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

class ApiFlows extends Controller {
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
        $filters->set_default_order_by($this->api_user->preferences->flows_default_order_by, $this->api_user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->api_user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);
        $filters->process();

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `flows` WHERE `user_id` = {$this->api_user->user_id}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('api/flows?' . $filters->get_get() . '&page=%d')));

        /* Get the data */
        $data = [];
        $data_result = database()->query("
            SELECT
                *
            FROM
                `flows`
            WHERE
                `user_id` = {$this->api_user->user_id}
                {$filters->get_sql_where()}
                {$filters->get_sql_order_by()}
                  
            {$paginator->get_sql_limit()}
        ");


        while($row = $data_result->fetch_object()) {

            /* Prepare the data */
            $row = [
                'id' => (int) $row->flow_id,
                'user_id' => (int) $row->user_id,
                'website_id' => (int) $row->website_id,
                'name' => $row->name,
                'title' => $row->title,
                'description' => $row->description,
                'url' => $row->url,
                'image_url' => $row->image ? \Altum\Uploads::get_full_url('websites_flows_images') . $row->image : null,
                'segment' => $row->segment,
                'settings' => json_decode($row->settings),
                'wait_time' => (int) $row->wait_time,
                'wait_time_type' => $row->wait_time_type,
                'is_enabled' => (bool) $row->is_enabled,
                'total_sent_push_notifications' => (int) $row->total_sent_push_notifications,
                'total_displayed_push_notifications' => (int) $row->total_displayed_push_notifications,
                'total_clicked_push_notifications' => (int) $row->total_clicked_push_notifications,
                'total_closed_push_notifications' => (int) $row->total_closed_push_notifications,
                'last_sent_datetime' => $row->last_sent_datetime,
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

        $flow_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $flow = db()->where('flow_id', $flow_id)->where('user_id', $this->api_user->user_id)->getOne('flows');

        /* We haven't found the resource */
        if(!$flow) {
            $this->return_404();
        }

        /* Prepare the data */
        $data = [
            'id' => (int) $flow->flow_id,
            'user_id' => (int) $flow->user_id,
            'website_id' => (int) $flow->website_id,
            'name' => $flow->name,
            'title' => $flow->title,
            'description' => $flow->description,
            'url' => $flow->url,
            'image_url' => $flow->image ? \Altum\Uploads::get_full_url('websites_flows_images') . $flow->image : null,
            'segment' => $flow->segment,
            'settings' => json_decode($flow->settings),
            'wait_time' => (int) $flow->wait_time,
            'wait_time_type' => $flow->wait_time_type,
            'is_enabled' => (bool) $flow->is_enabled,
            'total_sent_push_notifications' => (int) $flow->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $flow->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $flow->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $flow->total_closed_push_notifications,
            'last_sent_datetime' => $flow->last_sent_datetime,
            'last_datetime' => $flow->last_datetime,
            'datetime' => $flow->datetime
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
        $total_rows = db()->where('user_id', $this->api_user->user_id)->getValue('flows', 'count(*)');
        if($this->api_user->plan_settings->flows_limit != -1 && $total_rows >= $this->api_user->plan_settings->flows_limit) {
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
        $_POST['wait_time'] = (int) $_POST['wait_time'] ?? 1;
        $_POST['wait_time_type'] = isset($_POST['wait_time_type']) && in_array($_POST['wait_time_type'], ['minutes', 'hours', 'days']) ? $_POST['wait_time_type'] : 'days';
        $_POST['is_enabled'] = isset($_POST['is_enabled']) ? (int) (bool) $_POST['is_enabled'] : 1;

        if($_POST['wait_time'] < 1) $_POST['wait_time'] = 1;

        /* Max is 90 days of ahead scheduling */
        switch ($_POST['wait_time_type']) {
            case 'minutes':
                if($_POST['wait_time'] > 129600) $_POST['wait_time'] = 129600;
                break;

            case 'hours':
                if($_POST['wait_time'] > 2160) $_POST['wait_time'] = 2160;
                break;

            case 'days':
                if($_POST['wait_time'] > 90) $_POST['wait_time'] = 90;
                break;
        }

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

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload(null, 'websites_flows_images', 'image', 'image_remove', settings()->websites->flow_image_size_limit, 'json_error');

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
            ]
        ];

        /* Database query */
        $flow_id = db()->insert('flows', [
            'website_id' => $_POST['website_id'],
            'user_id' => $this->api_user->user_id,
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image' => $image,
            'segment' => $_POST['segment'],
            'settings' => json_encode($settings),
            'wait_time' => $_POST['wait_time'],
            'wait_time_type' => $_POST['wait_time_type'],
            'is_enabled' => (bool) $_POST['is_enabled'],
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('flows?user_id=' . $this->api_user->user_id);
        cache()->deleteItem('flows?website_id=' . $_POST['website_id']);

        /* Prepare the data */
        $data = [
            'id' => $flow_id,
            'user_id' => (int) $this->api_user->user_id,
            'website_id' => (int) $_POST['website_id'],
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_flows_images') . $image : null,
            'segment' => $_POST['segment'],
            'settings' => $settings,
            'wait_time' => $_POST['wait_time'],
            'wait_time_type' => $_POST['wait_time_type'],
            'is_enabled' => (bool) $_POST['is_enabled'],
            'total_sent_push_notifications' => 0,
            'total_displayed_push_notifications' => 0,
            'total_clicked_push_notifications' => 0,
            'total_closed_push_notifications' => 0,
            'last_sent_datetime' => null,
            'last_datetime' => null,
            'datetime' => get_date(),
        ];

        Response::jsonapi_success($data, null, 201);

    }

    private function patch() {

        /* Check for the plan limit */
        $total_rows = db()->where('user_id', $this->api_user->user_id)->getValue('flows', 'count(`flow_id`)');

        if($this->api_user->plan_settings->flows_limit != -1 && $total_rows > $this->api_user->plan_settings->flows_limit) {
            $this->response_error(sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), $total_rows - $this->user->plan_settings->flows_limit, mb_strtolower(l('flows.title')), l('global.info_message.plan_upgrade')), 401);
        }

        $flow_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $flow = db()->where('flow_id', $flow_id)->where('user_id', $this->api_user->user_id)->getOne('flows');

        /* We haven't found the resource */
        if(!$flow) {
            $this->return_404();
        }

        $flow->settings = json_decode($flow->settings ?? '');

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
        $_POST['name'] = input_clean($_POST['name'] ?? $flow->name, 256);
        $_POST['title'] = input_clean($_POST['title'] ?? $flow->title, 64);
        $_POST['description'] = input_clean($_POST['description'] ?? $flow->description, 128);
        $_POST['url'] = get_url($_POST['url'] ?? $flow->url, 512);
        $_POST['website_id'] = isset($_POST['website_id']) && array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : $flow->website_id;
        $_POST['wait_time'] = isset($_POST['wait_time']) ? (int) $_POST['wait_time'] : $flow->wait_time;
        $_POST['wait_time_type'] = isset($_POST['wait_time_type']) && in_array($_POST['wait_time_type'], ['minutes', 'hours', 'days']) ? $_POST['wait_time_type'] : $flow->wait_time_type;
        $_POST['is_enabled'] = isset($_POST['is_enabled']) ? (int) (bool) $_POST['is_enabled'] : $flow->is_enabled;
        $_POST['auto_apply_to_matching'] = isset($_POST['auto_apply_to_matching']) ? (int) (bool) $_POST['auto_apply_to_matching'] : 0;

        if($_POST['wait_time'] < 1) $_POST['wait_time'] = 1;

        /* Max is 90 days of ahead scheduling */
        switch ($_POST['wait_time_type']) {
            case 'minutes':
                if($_POST['wait_time'] > 129600) $_POST['wait_time'] = 129600;
                break;

            case 'hours':
                if($_POST['wait_time'] > 2160) $_POST['wait_time'] = 2160;
                break;

            case 'days':
                if($_POST['wait_time'] > 90) $_POST['wait_time'] = 90;
                break;
        }

        /* Segment */
        $_POST['segment'] = isset($_POST['segment']) ? $_POST['segment'] : $flow->segment;
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
        $_POST['ttl'] = isset($_POST['ttl']) && array_key_exists($_POST['ttl'], $notifications_ttl) ? (int) $_POST['ttl'] : $flow->settings->ttl;
        $_POST['urgency'] = isset($_POST['urgency']) && in_array($_POST['urgency'], ['low', 'normal', 'high']) ? $_POST['urgency'] : $flow->settings->urgency;
        $_POST['is_silent'] = (int) (bool) ($_POST['is_silent'] ?? $flow->settings->is_silent);
        $_POST['is_auto_hide'] = (int) (bool) ($_POST['is_auto_hide'] ?? $flow->settings->is_auto_hide);

        /* Buttons */
        $_POST['button_title_1'] = input_clean($_POST['button_title_1'] ?? $flow->settings->button_title_1, 16);
        $_POST['button_url_1'] = get_url($_POST['button_url_1'] ?? $flow->settings->button_url_1, 512);
        $_POST['button_title_2'] = input_clean($_POST['button_title_2'] ?? $flow->settings->button_title_2, 16);
        $_POST['button_url_2'] = get_url($_POST['button_url_2'] ?? $flow->settings->button_url_2, 512);

        /* UTM */
        $_POST['utm_medium'] = input_clean($_POST['utm_medium'] ?? $flow->settings->utm->medium, 128);
        $_POST['utm_source'] = input_clean($_POST['utm_source'] ?? $flow->settings->utm->source, 128);
        $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'] ?? $flow->settings->utm->campaign, 128);

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload($flow->image, 'websites_flows_images', 'image', 'image_remove', settings()->websites->flow_image_size_limit, 'json_error');

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
            ]
        ];

        /* Database query */
        db()->where('flow_id', $flow->flow_id)->update('flows', [
            'website_id' => $_POST['website_id'],
            'user_id' => $this->api_user->user_id,
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image' => $image,
            'segment' => $_POST['segment'],
            'settings' => json_encode($settings),
            'wait_time' => $_POST['wait_time'],
            'wait_time_type' => $_POST['wait_time_type'],
            'is_enabled' => $_POST['is_enabled'],
            'last_datetime' => get_date(),
        ]);

        /* Auto apply to existing resources */
        if($_POST['auto_apply_to_matching']) {
            db()->where('user_id', $this->api_user->user_id)->update('subscribers', [
                'has_flows_processed' => 0
            ]);
        }

        /* Clear the cache */
        cache()->deleteItem('flow?flow_id=' . $flow->flow_id);
        cache()->deleteItem('flows?user_id=' . $flow->user_id);
        cache()->deleteItem('flows?website_id=' . $flow->website_id);

        /* Prepare the data */
        $data = [
            'id' => $flow->flow_id,
            'user_id' => (int) $this->api_user->user_id,
            'website_id' => (int) $_POST['website_id'],
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_flows_images') . $image : null,
            'segment' => $_POST['segment'],
            'settings' => $settings,
            'wait_time' => $_POST['wait_time'],
            'wait_time_type' => $_POST['wait_time_type'],
            'is_enabled' => (bool) $_POST['is_enabled'],
            'total_sent_push_notifications' => (int) $flow->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $flow->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $flow->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $flow->total_closed_push_notifications,
            'last_sent_datetime' => $flow->last_sent_datetime,
            'last_datetime' => get_date(),
            'datetime' => $flow->datetime,
        ];

        Response::jsonapi_success($data, null, 200);

    }

    private function delete() {

        $flow_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $flow = db()->where('flow_id', $flow_id)->where('user_id', $this->api_user->user_id)->getOne('flows');

        /* We haven't found the resource */
        if(!$flow) {
            $this->return_404();
        }

        /* Delete the resource */
        (new \Altum\Models\Flow())->delete($flow_id);

        http_response_code(200);
        die();

    }
}
