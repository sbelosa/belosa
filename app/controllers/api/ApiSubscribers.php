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

class ApiSubscribers extends Controller {
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
        $filters->set_default_order_by($this->api_user->preferences->subscribers_default_order_by, $this->api_user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->api_user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);
        $filters->process();

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `subscribers` WHERE `user_id` = {$this->api_user->user_id}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('api/subscribers?' . $filters->get_get() . '&page=%d')));

        /* Get the data */
        $data = [];
        $data_result = database()->query("
            SELECT
                *
            FROM
                `subscribers`
            WHERE
                `user_id` = {$this->api_user->user_id}
                {$filters->get_sql_where()}
                {$filters->get_sql_order_by()}
                  
            {$paginator->get_sql_limit()}
        ");


        while($row = $data_result->fetch_object()) {

            /* Prepare the data */
            $row = [
                'id' => (int) $row->subscriber_id,
                'user_id' => (int) $row->user_id,
                'website_id' => (int) $row->website_id,
                'unique_endpoint_id' => $row->unique_endpoint_id,
                'endpoint' => json_decode($row->endpoint),
                'ip' => $row->ip,
                'custom_parameters' => json_decode($row->custom_parameters ?? ''),
                'has_flows_processed' => (bool) $row->has_flows_processed,
                'processed_flows' => json_decode($row->processed_flows ?? '[]'),
                'city_name' => $row->city_name,
                'country_code' => $row->country_code,
                'continent_code' => $row->continent_code,
                'os_name' => $row->os_name,
                'browser_name' => $row->browser_name,
                'browser_language' => $row->browser_language,
                'device_type' => $row->device_type,
                'subscribed_on_url' => $row->subscribed_on_url,
                'total_sent_push_notifications' => (int) $row->total_sent_push_notifications,
                'total_displayed_push_notifications' => (int) $row->total_displayed_push_notifications,
                'total_clicked_push_notifications' => (int) $row->total_clicked_push_notifications,
                'total_closed_push_notifications' => (int) $row->total_closed_push_notifications,
                'last_sent_datetime' => $row->last_sent_datetime,
                'last_datetime' => $row->last_datetime,
                'datetime' => $row->datetime,
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

        $subscriber_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $subscriber = db()->where('subscriber_id', $subscriber_id)->where('user_id', $this->api_user->user_id)->getOne('subscribers');

        /* We haven't found the resource */
        if(!$subscriber) {
            $this->return_404();
        }

        /* Prepare the data */
        $data = [
            'id' => (int) $subscriber->subscriber_id,
            'user_id' => (int) $subscriber->user_id,
            'website_id' => (int) $subscriber->website_id,
            'unique_endpoint_id' => $subscriber->unique_endpoint_id,
            'endpoint' => json_decode($subscriber->endpoint),
            'ip' => $subscriber->ip,
            'custom_parameters' => json_decode($subscriber->custom_parameters ?? ''),
            'has_flows_processed' => (bool) $subscriber->has_flows_processed,
            'processed_flows' => json_decode($subscriber->processed_flows ?? '[]'),
            'city_name' => $subscriber->city_name,
            'country_code' => $subscriber->country_code,
            'continent_code' => $subscriber->continent_code,
            'os_name' => $subscriber->os_name,
            'browser_name' => $subscriber->browser_name,
            'browser_language' => $subscriber->browser_language,
            'device_type' => $subscriber->device_type,
            'subscribed_on_url' => $subscriber->subscribed_on_url,
            'total_sent_push_notifications' => (int) $subscriber->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $subscriber->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $subscriber->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $subscriber->total_closed_push_notifications,
            'last_sent_datetime' => $subscriber->last_sent_datetime,
            'last_datetime' => $subscriber->last_datetime,
            'datetime' => $subscriber->datetime,
        ];

        Response::jsonapi_success($data);

    }

    private function patch() {

        /* Check for the plan limit */
        $total_rows = db()->where('user_id', $this->api_user->user_id)->getValue('subscribers', 'count(`subscriber_id`)');

        if($this->api_user->plan_settings->subscribers_limit != -1 && $total_rows > $this->api_user->plan_settings->subscribers_limit) {
            $this->response_error(sprintf(settings()->payment->is_enabled ? l('global.info_message.plan_feature_limit_removal_with_upgrade') : l('global.info_message.plan_feature_limit_removal'), $total_rows - $this->user->plan_settings->subscribers_limit, mb_strtolower(l('subscribers.title')), l('global.info_message.plan_upgrade')), 401);
        }

        $subscriber_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $subscriber = db()->where('subscriber_id', $subscriber_id)->where('user_id', $this->api_user->user_id)->getOne('subscribers');

        /* We haven't found the resource */
        if(!$subscriber) {
            $this->return_404();
        }
        $subscriber->custom_parameters = json_decode($subscriber->custom_parameters ?? '');

        $custom_parameters = [];

        /* Filter some of the variables */
        if(!isset($_POST['custom_parameter_key'])) {
            $_POST['custom_parameter_key'] = [];
            $_POST['custom_parameter_value'] = [];
            $custom_parameters = $subscriber->custom_parameters;
        }

        $i = 0;
        foreach($_POST['custom_parameter_key'] as $key => $value) {
            if(empty(trim($value))) continue;

            $custom_parameter_key = input_clean($value, 64);
            $custom_parameter_value = input_clean($_POST['custom_parameter_value'][$key], 512);

            $custom_parameters[$custom_parameter_key] = $custom_parameter_value;

            if($i++ >= 20) {
                break;
            }
        }

        /* Database query */
        db()->where('subscriber_id', $subscriber->subscriber_id)->update('subscribers', [
            'custom_parameters' => json_encode($custom_parameters),
            'last_datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('subscriber?subscriber_id=' . $subscriber->subscriber_id);

        /* Prepare the data */
        $data = [
            'id' => $subscriber->subscriber_id,
            'user_id' => (int) $subscriber->user_id,
            'website_id' => (int) $subscriber->website_id,
            'unique_endpoint_id' => $subscriber->unique_endpoint_id,
            'endpoint' => json_decode($subscriber->endpoint),
            'ip' => $subscriber->ip,
            'custom_parameters' => $custom_parameters,
            'city_name' => $subscriber->city_name,
            'country_code' => $subscriber->country_code,
            'continent_code' => $subscriber->continent_code,
            'os_name' => $subscriber->os_name,
            'browser_name' => $subscriber->browser_name,
            'browser_language' => $subscriber->browser_language,
            'device_type' => $subscriber->device_type,
            'subscribed_on_url' => $subscriber->subscribed_on_url,
            'total_sent_push_notifications' => (int) $subscriber->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $subscriber->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $subscriber->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $subscriber->total_closed_push_notifications,
            'last_sent_datetime' => $subscriber->last_sent_datetime,
            'last_datetime' => $subscriber->last_datetime,
            'datetime' => $subscriber->datetime,
        ];

        Response::jsonapi_success($data, null, 200);

    }

    private function delete() {

        $subscriber_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $subscriber = db()->where('subscriber_id', $subscriber_id)->where('user_id', $this->api_user->user_id)->getOne('subscribers');

        /* We haven't found the resource */
        if(!$subscriber) {
            $this->return_404();
        }

        /* Update all previous logs */
        db()->where('subscriber_id', $subscriber_id)->update('subscribers_logs', [
            'ip' => preg_replace('/\d/', '*', $subscriber->ip)
        ]);

        /* Database query */
        db()->where('subscriber_id', $subscriber_id)->delete('subscribers');

        /* Update website statistics */
        if(db()->count) {
            db()->where('website_id', $subscriber->website_id)->update('websites', ['total_subscribers' => db()->dec()]);
        }

        /* Insert subscriber log */
        db()->insert('subscribers_logs', [
            'website_id' => $subscriber->website_id,
            'user_id' => $this->api_user->user_id,
            'ip' => preg_replace('/\d/', '*', $subscriber->ip),
            'type' => 'manually_deleted',
            'datetime' => get_date(),
        ]);

        /* Clear the cache */
        cache()->deleteItem('subscribers_total?user_id=' . $this->api_user->user_id);
        cache()->deleteItem('subscribers_dashboard?user_id=' . $this->api_user->user_id);

        http_response_code(200);
        die();

    }
}
