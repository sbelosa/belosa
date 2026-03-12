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

use Altum\Date;
use Altum\Response;
use Altum\Traits\Apiable;

defined('ALTUMCODE') || die();

class ApiCampaigns extends Controller {
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
        $filters->set_default_order_by($this->api_user->preferences->campaigns_default_order_by, $this->api_user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->api_user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);
        $filters->process();

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `campaigns` WHERE `user_id` = {$this->api_user->user_id}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('api/campaigns?' . $filters->get_get() . '&page=%d')));

        /* Get the data */
        $data = [];
        $data_result = database()->query("
            SELECT
                *
            FROM
                `campaigns`
            WHERE
                `user_id` = {$this->api_user->user_id}
                {$filters->get_sql_where()}
                {$filters->get_sql_order_by()}
                  
            {$paginator->get_sql_limit()}
        ");


        while($row = $data_result->fetch_object()) {

            /* Prepare the data */
            $row = [
                'id' => (int) $row->campaign_id,
                'user_id' => (int) $row->user_id,
                'website_id' => (int) $row->website_id,
                'rss_automation_id' => (int) $row->rss_automation_id,
                'recurring_campaign_id' => (int) $row->recurring_campaign_id,
                'name' => $row->name,
                'title' => $row->title,
                'description' => $row->description,
                'url' => $row->url,
                'image_url' => $row->image ? \Altum\Uploads::get_full_url('websites_campaigns_images') . $row->image : null,
                'segment' => $row->segment,
                'settings' => json_decode($row->settings),
                'subscribers_ids' => json_decode($row->subscribers_ids),
                'sent_subscribers_ids' => json_decode($row->sent_subscribers_ids),
                'total_push_notifications' => (int) $row->total_push_notifications,
                'total_sent_push_notifications' => (int) $row->total_sent_push_notifications,
                'total_displayed_push_notifications' => (int) $row->total_displayed_push_notifications,
                'total_clicked_push_notifications' => (int) $row->total_clicked_push_notifications,
                'total_closed_push_notifications' => (int) $row->total_closed_push_notifications,
                'status' => $row->status,
                'scheduled_datetime' => $row->scheduled_datetime,
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

        $campaign_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $campaign = db()->where('campaign_id', $campaign_id)->where('user_id', $this->api_user->user_id)->getOne('campaigns');

        /* We haven't found the resource */
        if(!$campaign) {
            $this->return_404();
        }

        /* Prepare the data */
        $data = [
            'id' => (int) $campaign->campaign_id,
            'user_id' => (int) $campaign->user_id,
            'website_id' => (int) $campaign->website_id,
            'rss_automation_id' => (int) $campaign->rss_automation_id,
            'recurring_campaign_id' => (int) $campaign->recurring_campaign_id,
            'name' => $campaign->name,
            'title' => $campaign->title,
            'description' => $campaign->description,
            'url' => $campaign->url,
            'image_url' => $campaign->image ? \Altum\Uploads::get_full_url('websites_campaigns_images') . $campaign->image : null,
            'segment' => $campaign->segment,
            'settings' => json_decode($campaign->settings),
            'subscribers_ids' => json_decode($campaign->subscribers_ids),
            'sent_subscribers_ids' => json_decode($campaign->sent_subscribers_ids),
            'total_push_notifications' => (int) $campaign->total_push_notifications,
            'total_sent_push_notifications' => (int) $campaign->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $campaign->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $campaign->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $campaign->total_closed_push_notifications,
            'status' => $campaign->status,
            'scheduled_datetime' => $campaign->scheduled_datetime,
            'last_sent_datetime' => $campaign->last_sent_datetime,
            'last_datetime' => $campaign->last_datetime,
            'datetime' => $campaign->datetime,
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
        $campaigns_current_month = db()->where('user_id', $this->api_user->user_id)->getValue('users', '`pusher_campaigns_current_month`');
        if($this->api_user->plan_settings->campaigns_per_month_limit != -1 && $campaigns_current_month >= $this->api_user->plan_settings->campaigns_per_month_limit) {
            $this->response_error(l('global.info_message.plan_feature_limit'), 401);
        }

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->api_user->user_id);

        /* Get available segments */
        $segments = (new \Altum\Models\Segment())->get_segments_by_user_id($this->api_user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* Filter some of the variables */
        $_POST['name'] = input_clean($_POST['name'], 256);
        $_POST['title'] = input_clean($_POST['title'], 64);
        $_POST['description'] = input_clean($_POST['description'], 128);
        $_POST['url'] = get_url($_POST['url'] ?? '', 512);
        $_POST['website_id'] = array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : array_key_first($websites);

        /* Segment */
        $segment_type = null;
        $_POST['segment'] = $_POST['segment'] ?? null;
        if(is_numeric($_POST['segment'])) {

            /* Get settings from custom segments */
            $segment = $segments[$_POST['segment']];

            if(!$segment || $_POST['website_id'] != $segment->website_id) {
                $_POST['segment'] = 'all';
            }

            switch($segment->type) {
                case 'custom':

                    $segment_type = 'custom';
                    $_POST['subscribers_ids'] = implode(',', $segment->settings->subscribers_ids);

                    break;

                case 'filter':

                    $segment_type = 'filter';

                    if(isset($segment->settings->filters_subscribed_on_url)) $_POST['filters_subscribed_on_url'] = $segment->settings->filters_subscribed_on_url ?? '';
                    if(isset($segment->settings->filters_cities)) $_POST['filters_cities'] = $segment->settings->filters_cities ?? [];
                    if(isset($segment->settings->filters_countries)) $_POST['filters_countries'] = $segment->settings->filters_countries ?? [];
                    if(isset($segment->settings->filters_continents)) $_POST['filters_continents'] = $segment->settings->filters_continents ?? [];
                    if(isset($segment->settings->filters_device_type)) $_POST['filters_device_type'] = $segment->settings->filters_device_type ?? [];
                    if(isset($segment->settings->filters_languages)) $_POST['filters_languages'] = $segment->settings->filters_languages ?? [];
                    if(isset($segment->settings->filters_operating_systems)) $_POST['filters_operating_systems'] = $segment->settings->filters_operating_systems ?? [];
                    if(isset($segment->settings->filters_browsers)) $_POST['filters_browsers'] = $segment->settings->filters_browsers ?? [];
                    if(isset($segment->settings->filters_custom_parameters) && count($segment->settings->filters_custom_parameters)) {
                        foreach($segment->settings->filters_custom_parameters as $key => $custom_parameter) {
                            $_POST['filters_custom_parameter_key'][$key] = $custom_parameter->key;
                            $_POST['filters_custom_parameter_condition'][$key] = $custom_parameter->condition;
                            $_POST['filters_custom_parameter_value'][$key] = $custom_parameter->value;
                        }
                    }

                    break;
            }

        } else {
            $_POST['segment'] = in_array($_POST['segment'], ['all', 'custom', 'filter']) ? input_clean($_POST['segment']) : 'all';
            $segment_type = $_POST['segment'];
        }

        /* Scheduling */
        $_POST['is_scheduled'] = (int) isset($_POST['is_scheduled']);
        $_POST['scheduled_datetime'] = $_POST['is_scheduled'] && !empty($_POST['scheduled_datetime']) && Date::validate($_POST['scheduled_datetime'], 'Y-m-d H:i:s') ?
            (new \DateTime($_POST['scheduled_datetime'], new \DateTimeZone($this->api_user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s')
            : get_date();

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

        /* Subscribers ids */
        $_POST['subscribers_ids'] = trim($_POST['subscribers_ids'] ?? '');
        $_POST['subscribers_ids'] = array_filter(array_map('intval', explode(',', $_POST['subscribers_ids'])));
        $_POST['subscribers_ids'] = array_values(array_unique($_POST['subscribers_ids']));
        $_POST['subscribers_ids'] = $_POST['subscribers_ids'] ?: [0];

        $settings = [
            /* Scheduling */
            'is_scheduled' => $_POST['is_scheduled'],

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

        /* Get all the users needed */
        switch($segment_type) {
            case 'all':
                $subscribers = db()->where('user_id', $this->api_user->user_id)->where('website_id', $_POST['website_id'])->get('subscribers', null, ['subscriber_id', 'user_id']);
                break;

            case 'custom':
                $subscribers = db()->where('user_id', $this->api_user->user_id)->where('website_id', $_POST['website_id'])->where('subscriber_id', $_POST['subscribers_ids'], 'IN')->get('subscribers', null, ['subscriber_id']);
                break;

            case 'filter':

                $query = db()->where('user_id', $this->api_user->user_id)->where('website_id', $_POST['website_id']);

                $has_filters = false;

                /* Custom parameters */
                if(!isset($_POST['filters_custom_parameter_key'])) {
                    $_POST['filters_custom_parameter_key'] = [];
                    $_POST['filters_custom_parameter_condition'] = [];
                    $_POST['filters_custom_parameter_value'] = [];
                }

                $custom_parameters = [];

                foreach($_POST['filters_custom_parameter_key'] as $key => $value) {
                    if(empty(trim($value))) continue;
                    if($key >= 50) continue;

                    $custom_parameters[] = [
                        'key' => input_clean($value, 64),
                        'condition' => isset($_POST['filters_custom_parameter_condition'][$key]) && in_array($_POST['filters_custom_parameter_condition'][$key], ['exact', 'not_exact', 'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with', 'bigger_than', 'lower_than']) ? $_POST['filters_custom_parameter_condition'][$key] : 'exact',
                        'value' => input_clean($_POST['filters_custom_parameter_value'][$key], 512)
                    ];
                }

                if(count($custom_parameters)) {
                    $has_filters = true;
                    $settings['filters_custom_parameters'] = $custom_parameters;

                    foreach($custom_parameters as $custom_parameter) {
                        $key = $custom_parameter['key'];
                        $condition = $custom_parameter['condition'];
                        $value = $custom_parameter['value'];

                        /* reference JSON value once; unquote JSON for string ops, cast for numeric ops */
                        $json_value_expression = 'JSON_UNQUOTE(JSON_EXTRACT(`custom_parameters`, \'$."'.$key.'"\'))';
                        $numeric_expression = 'CAST('.$json_value_expression.' AS DECIMAL(65,10))';

                        switch($condition) {
                            case 'exact':
                                $query->where($json_value_expression.' = \''.$value.'\'');
                                break;

                            case 'not_exact':
                                $query->where($json_value_expression.' != \''.$value.'\'');
                                break;

                            case 'contains':
                                $query->where($json_value_expression.' LIKE \'%'.$value.'%\'');
                                break;

                            case 'not_contains':
                                $query->where($json_value_expression.' NOT LIKE \'%'.$value.'%\'');
                                break;

                            case 'starts_with':
                                $query->where($json_value_expression.' LIKE \''.$value.'%\'');
                                break;

                            case 'not_starts_with':
                                $query->where($json_value_expression.' NOT LIKE \''.$value.'%\'');
                                break;

                            case 'ends_with':
                                $query->where($json_value_expression.' LIKE \'%'.$value.'\'');
                                break;

                            case 'not_ends_with':
                                $query->where($json_value_expression.' NOT LIKE \'%'.$value.'\'');
                                break;

                            case 'bigger_than':
                                $query->where($numeric_expression.' > '.(is_numeric($value) ? $value : '0'));
                                break;

                            case 'lower_than':
                                $query->where($numeric_expression.' < '.(is_numeric($value) ? $value : '0'));
                                break;
                        }
                    }
                }

                /* Subscribed on URL */
                if(!empty($_POST['filters_subscribed_on_url'])) {
                    $_POST['filters_subscribed_on_url'] = input_clean($_POST['filters_subscribed_on_url'], 2048);

                    $has_filters = true;
                    $query->where('subscribed_on_url', $_POST['filters_subscribed_on_url']);
                    $settings['filters_subscribed_on_url'] = $_POST['filters_subscribed_on_url'];
                }

                /* Cities */
                if(!empty($_POST['filters_cities'])) {
                    $_POST['filters_cities'] = explode(',', $_POST['filters_cities']);
                    $_POST['filters_cities'] = array_filter(array_unique($_POST['filters_cities']));

                    if(count($_POST['filters_cities'])) {
                        $_POST['filters_cities'] = array_map(function($city) {
                            return query_clean($city);
                        }, $_POST['filters_cities']);

                        $has_filters = true;
                        $query->where('city_name', $_POST['filters_cities'], 'IN');
                        $settings['filters_cities'] = $_POST['filters_cities'];
                    }
                }

                /* Countries */
                if(isset($_POST['filters_countries'])) {
                    $_POST['filters_countries'] = array_filter($_POST['filters_countries'] ?? [], function($country) {
                        return array_key_exists($country, get_countries_array());
                    });

                    $has_filters = true;
                    $query->where('country_code', $_POST['filters_countries'], 'IN');
                    $settings['filters_countries'] = $_POST['filters_countries'];
                }

                /* Continents */
                if(isset($_POST['filters_continents'])) {
                    $_POST['filters_continents'] = array_filter($_POST['filters_continents'] ?? [], function($country) {
                        return array_key_exists($country, get_continents_array());
                    });

                    $has_filters = true;
                    $query->where('continent_code', $_POST['filters_continents'], 'IN');
                    $settings['filters_continents'] = $_POST['filters_continents'];
                }

                /* Device type */
                if(isset($_POST['filters_device_type'])) {
                    $_POST['filters_device_type'] = array_filter($_POST['filters_device_type'] ?? [], function($device_type) {
                        return in_array($device_type, ['desktop', 'tablet', 'mobile']);
                    });

                    $has_filters = true;
                    $query->where('device_type', $_POST['filters_device_type'], 'IN');
                    $settings['filters_device_type'] = $_POST['filters_device_type'];
                }

                /* Languages */
                if(isset($_POST['filters_languages'])) {
                    $_POST['filters_languages'] = array_filter($_POST['filters_languages'], function($locale) {
                        return array_key_exists($locale, get_locale_languages_array());
                    });

                    $has_filters = true;
                    $query->where('browser_language', $_POST['filters_languages'], 'IN');
                    $settings['filters_languages'] = $_POST['filters_languages'];
                }

                /* Filters operating systems */
                if(isset($_POST['filters_operating_systems'])) {
                    $_POST['filters_operating_systems'] = array_filter($_POST['filters_operating_systems'], function($os_name) {
                        return in_array($os_name, ['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS']);
                    });

                    $has_filters = true;
                    $query->where('os_name', $_POST['filters_operating_systems'], 'IN');
                    $settings['filters_operating_systems'] = $_POST['filters_operating_systems'];
                }

                /* Filters browsers */
                if(isset($_POST['filters_browsers'])) {
                    $_POST['filters_browsers'] = array_filter($_POST['filters_browsers'], function($browser_name) {
                        return in_array($browser_name, ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet']);
                    });

                    $has_filters = true;
                    $query->where('browser_name', $_POST['filters_browsers'], 'IN');
                    $settings['filters_browsers'] = $_POST['filters_browsers'];
                }

                $subscribers = $has_filters ? $query->get('subscribers', null, ['subscriber_id']) : [];

                db()->reset();

                break;
        }

        $subscribers_ids = array_column($subscribers, 'subscriber_id');

        /* Free memory */
        unset($subscribers);

        $status = $_POST['is_scheduled'] && $_POST['scheduled_datetime'] ? 'scheduled' : 'processing';

        if(isset($_POST['save'])) {
            $status = 'draft';
        }

        if($status != 'draft') {
            /* Check for the plan limit */
            $sent_push_notifications_current_month = db()->where('user_id', $this->api_user->user_id)->getValue('users', '`pusher_sent_push_notifications_current_month`');
            if($this->api_user->plan_settings->sent_push_notifications_per_month_limit != -1 && $sent_push_notifications_current_month + count($subscribers_ids) >= $this->api_user->plan_settings->sent_push_notifications_per_month_limit) {
                $this->response_error(l('global.info_message.plan_feature_limit'), 401);
            }
        }

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload(null, 'websites_campaigns_images', 'image', 'image_remove', settings()->websites->campaign_image_size_limit, 'json_error');

        /* Database query */
        $campaign_id = db()->insert('campaigns', [
            'website_id' => $_POST['website_id'],
            'user_id' => $this->api_user->user_id,
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image' => $image,
            'segment' => $_POST['segment'],
            'settings' => json_encode($settings),
            'subscribers_ids' => json_encode($subscribers_ids),
            'sent_subscribers_ids' => '[]',
            'total_push_notifications' => count($subscribers_ids),
            'status' => $status,
            'scheduled_datetime' => $_POST['scheduled_datetime'],
            'datetime' => get_date(),
        ]);

        /* Database query */
        db()->where('user_id', $this->api_user->user_id)->update('users', [
            'pusher_campaigns_current_month' => db()->inc()
        ]);

        if(!isset($_POST['save'])) {
            /* Update the total website sent campaigns */
            db()->where('website_id', $_POST['website_id'])->update('websites', [
                'total_sent_campaigns' => db()->inc()
            ]);
        }

        /* Clear the cache */
        cache()->deleteItem('campaigns?user_id=' . $this->api_user->user_id);
        cache()->deleteItem('campaigns_total?user_id=' . $this->api_user->user_id);
        cache()->deleteItem('campaigns_dashboard?user_id=' . $this->api_user->user_id);

        /* Prepare the data */
        $data = [
            'id' => $campaign_id,
            'user_id' => (int) $this->api_user->user_id,
            'website_id' => (int) $_POST['website_id'],
            'rss_automation_id' => null,
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_campaigns_images') . $image : null,
            'segment' => $_POST['segment'],
            'settings' => $settings,
            'subscribers_ids' => $subscribers_ids,
            'sent_subscribers_ids' => [],
            'total_push_notifications' => count($subscribers_ids),
            'total_sent_push_notifications' => 0,
            'total_displayed_push_notifications' => 0,
            'total_clicked_push_notifications' => 0,
            'total_closed_push_notifications' => 0,
            'status' => $status,
            'scheduled_datetime' => $_POST['scheduled_datetime'],
            'last_sent_datetime' => null,
            'last_datetime' => null,
            'datetime' => get_date(),
        ];

        Response::jsonapi_success($data, null, 201);

    }

    private function patch() {

        $campaign_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $campaign = db()->where('campaign_id', $campaign_id)->where('user_id', $this->api_user->user_id)->getOne('campaigns');

        /* We haven't found the resource */
        if(!$campaign) {
            $this->return_404();
        }

        $campaign->settings = json_decode($campaign->settings ?? '');

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

        /* Get available segments */
        $segments = (new \Altum\Models\Segment())->get_segments_by_user_id($this->api_user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* Filter some of the variables */
        $_POST['name'] = input_clean($_POST['name'] ?? $campaign->name, 256);
        $_POST['title'] = input_clean($_POST['title'] ?? $campaign->title, 64);
        $_POST['description'] = input_clean($_POST['description'] ?? $campaign->description, 128);
        $_POST['url'] = get_url($_POST['url'] ?? $campaign->url, 512);
        $_POST['website_id'] = isset($_POST['website_id']) && array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : $campaign->website_id;

        /* Segment */
        $segment_type = $_POST['segment'] ?? $campaign->segment;
        if(is_numeric($_POST['segment'])) {

            /* Get settings from custom segments */
            $segment = $segments[$_POST['segment']];

            if(!$segment || $_POST['website_id'] != $segment->website_id) {
                $_POST['segment'] = 'all';
            }

            switch($segment->type) {
                case 'custom':

                    $segment_type = 'custom';
                    $_POST['subscribers_ids'] = implode(',', $segment->settings->subscribers_ids);

                    break;

                case 'filter':

                    $segment_type = 'filter';

                    if(isset($segment->settings->filters_subscribed_on_url)) $_POST['filters_subscribed_on_url'] = $segment->settings->filters_subscribed_on_url ?? '';
                    if(isset($segment->settings->filters_cities)) $_POST['filters_cities'] = $segment->settings->filters_cities ?? [];
                    if(isset($segment->settings->filters_countries)) $_POST['filters_countries'] = $segment->settings->filters_countries ?? [];
                    if(isset($segment->settings->filters_continents)) $_POST['filters_continents'] = $segment->settings->filters_continents ?? [];
                    if(isset($segment->settings->filters_device_type)) $_POST['filters_device_type'] = $segment->settings->filters_device_type ?? [];
                    if(isset($segment->settings->filters_languages)) $_POST['filters_languages'] = $segment->settings->filters_languages ?? [];
                    if(isset($segment->settings->filters_operating_systems)) $_POST['filters_operating_systems'] = $segment->settings->filters_operating_systems ?? [];
                    if(isset($segment->settings->filters_browsers)) $_POST['filters_browsers'] = $segment->settings->filters_browsers ?? [];
                    if(isset($segment->settings->filters_custom_parameters) && count($segment->settings->filters_custom_parameters)) {
                        foreach($segment->settings->filters_custom_parameters as $key => $custom_parameter) {
                            $_POST['filters_custom_parameter_key'][$key] = $custom_parameter->key;
                            $_POST['filters_custom_parameter_condition'][$key] = $custom_parameter->condition;
                            $_POST['filters_custom_parameter_value'][$key] = $custom_parameter->value;
                        }
                    }

                    break;
            }

        } else {
            $_POST['segment'] = in_array($_POST['segment'], ['all', 'custom', 'filter']) ? input_clean($_POST['segment']) : 'all';
            $segment_type = $_POST['segment'];
        }

        /* Scheduling */
        $_POST['is_scheduled'] = (int) (bool) ($_POST['is_scheduled'] ?? $campaign->settings->is_scheduled);
        $_POST['scheduled_datetime'] = $_POST['is_scheduled'] && !empty($_POST['scheduled_datetime']) && Date::validate($_POST['scheduled_datetime'], 'Y-m-d H:i:s') ?
            (new \DateTime($_POST['scheduled_datetime'], new \DateTimeZone($this->api_user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s')
            : $campaign->scheduled_datetime;

        /* Advanced */
        $_POST['ttl'] = isset($_POST['ttl']) && array_key_exists($_POST['ttl'], $notifications_ttl) ? (int) $_POST['ttl'] : $campaign->settings->ttl;
        $_POST['urgency'] = isset($_POST['urgency']) && in_array($_POST['urgency'], ['low', 'normal', 'high']) ? $_POST['urgency'] : $campaign->settings->urgency;
        $_POST['is_silent'] = (int) (bool) ($_POST['is_silent'] ?? $campaign->settings->is_silent);
        $_POST['is_auto_hide'] = (int) (bool) ($_POST['is_auto_hide'] ?? $campaign->settings->is_auto_hide);

        /* Buttons */
        $_POST['button_title_1'] = input_clean($_POST['button_title_1'] ?? $campaign->settings->button_title_1, 16);
        $_POST['button_url_1'] = get_url($_POST['button_url_1'] ?? $campaign->settings->button_url_1, 512);
        $_POST['button_title_2'] = input_clean($_POST['button_title_2'] ?? $campaign->settings->button_title_2, 16);
        $_POST['button_url_2'] = get_url($_POST['button_url_2'] ?? $campaign->settings->button_url_2, 512);

        /* UTM */
        $_POST['utm_medium'] = input_clean($_POST['utm_medium'] ?? $campaign->settings->utm->medium, 128);
        $_POST['utm_source'] = input_clean($_POST['utm_source'] ?? $campaign->settings->utm->source, 128);
        $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'] ?? $campaign->settings->utm->campaign, 128);

        /* Subscribers ids */
        $_POST['subscribers_ids'] = trim($_POST['subscribers_ids'] ?? $campaign->subscribers_ids);
        $_POST['subscribers_ids'] = array_filter(array_map('intval', explode(',', $_POST['subscribers_ids'])));
        $_POST['subscribers_ids'] = array_values(array_unique($_POST['subscribers_ids']));
        $_POST['subscribers_ids'] = $_POST['subscribers_ids'] ?: [0];

        $settings = [
            /* Scheduling */
            'is_scheduled' => $_POST['is_scheduled'],

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

        /* Get all the users needed */
        switch($segment_type) {
            case 'all':
                $subscribers = db()->where('user_id', $this->api_user->user_id)->where('website_id', $_POST['website_id'])->get('subscribers', null, ['subscriber_id', 'user_id']);
                break;

            case 'custom':
                $subscribers = db()->where('user_id', $this->api_user->user_id)->where('website_id', $_POST['website_id'])->where('subscriber_id', $_POST['subscribers_ids'], 'IN')->get('subscribers', null, ['subscriber_id']);
                break;

            case 'filter':

                $query = db()->where('user_id', $this->api_user->user_id)->where('website_id', $_POST['website_id']);

                $has_filters = false;

                /* Custom parameters */
                if(!isset($_POST['filters_custom_parameter_key'])) {
                    $_POST['filters_custom_parameter_key'] = [];
                    $_POST['filters_custom_parameter_condition'] = [];
                    $_POST['filters_custom_parameter_value'] = [];
                }

                $custom_parameters = [];

                foreach($_POST['filters_custom_parameter_key'] as $key => $value) {
                    if(empty(trim($value))) continue;
                    if($key >= 50) continue;

                    $custom_parameters[] = [
                        'key' => input_clean($value, 64),
                        'condition' => isset($_POST['filters_custom_parameter_condition'][$key]) && in_array($_POST['filters_custom_parameter_condition'][$key], ['exact', 'not_exact', 'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with', 'bigger_than', 'lower_than']) ? $_POST['filters_custom_parameter_condition'][$key] : 'exact',
                        'value' => input_clean($_POST['filters_custom_parameter_value'][$key], 512)
                    ];
                }

                if(count($custom_parameters)) {
                    $has_filters = true;
                    $settings['filters_custom_parameters'] = $custom_parameters;

                    foreach($custom_parameters as $custom_parameter) {
                        $key = $custom_parameter['key'];
                        $condition = $custom_parameter['condition'];
                        $value = $custom_parameter['value'];

                        /* reference JSON value once; unquote JSON for string ops, cast for numeric ops */
                        $json_value_expression = 'JSON_UNQUOTE(JSON_EXTRACT(`custom_parameters`, \'$."'.$key.'"\'))';
                        $numeric_expression = 'CAST('.$json_value_expression.' AS DECIMAL(65,10))';

                        switch($condition) {
                            case 'exact':
                                $query->where($json_value_expression.' = \''.$value.'\'');
                                break;

                            case 'not_exact':
                                $query->where($json_value_expression.' != \''.$value.'\'');
                                break;

                            case 'contains':
                                $query->where($json_value_expression.' LIKE \'%'.$value.'%\'');
                                break;

                            case 'not_contains':
                                $query->where($json_value_expression.' NOT LIKE \'%'.$value.'%\'');
                                break;

                            case 'starts_with':
                                $query->where($json_value_expression.' LIKE \''.$value.'%\'');
                                break;

                            case 'not_starts_with':
                                $query->where($json_value_expression.' NOT LIKE \''.$value.'%\'');
                                break;

                            case 'ends_with':
                                $query->where($json_value_expression.' LIKE \'%'.$value.'\'');
                                break;

                            case 'not_ends_with':
                                $query->where($json_value_expression.' NOT LIKE \'%'.$value.'\'');
                                break;

                            case 'bigger_than':
                                $query->where($numeric_expression.' > '.(is_numeric($value) ? $value : '0'));
                                break;

                            case 'lower_than':
                                $query->where($numeric_expression.' < '.(is_numeric($value) ? $value : '0'));
                                break;
                        }
                    }
                }

                /* Subscribed on URL */
                if(!empty($_POST['filters_subscribed_on_url'])) {
                    $_POST['filters_subscribed_on_url'] = input_clean($_POST['filters_subscribed_on_url'], 2048);

                    $has_filters = true;
                    $query->where('subscribed_on_url', $_POST['filters_subscribed_on_url']);
                    $settings['filters_subscribed_on_url'] = $_POST['filters_subscribed_on_url'];
                }

                /* Cities */
                if(!empty($_POST['filters_cities'])) {
                    $_POST['filters_cities'] = explode(',', $_POST['filters_cities']);
                    $_POST['filters_cities'] = array_filter(array_unique($_POST['filters_cities']));

                    if(count($_POST['filters_cities'])) {
                        $_POST['filters_cities'] = array_map(function($city) {
                            return query_clean($city);
                        }, $_POST['filters_cities']);

                        $has_filters = true;
                        $query->where('city_name', $_POST['filters_cities'], 'IN');
                        $settings['filters_cities'] = $_POST['filters_cities'];
                    }
                }

                /* Countries */
                if(isset($_POST['filters_countries'])) {
                    $_POST['filters_countries'] = array_filter($_POST['filters_countries'] ?? [], function($country) {
                        return array_key_exists($country, get_countries_array());
                    });

                    $has_filters = true;
                    $query->where('country_code', $_POST['filters_countries'], 'IN');
                    $settings['filters_countries'] = $_POST['filters_countries'];
                }

                /* Continents */
                if(isset($_POST['filters_continents'])) {
                    $_POST['filters_continents'] = array_filter($_POST['filters_continents'] ?? [], function($country) {
                        return array_key_exists($country, get_continents_array());
                    });

                    $has_filters = true;
                    $query->where('continent_code', $_POST['filters_continents'], 'IN');
                    $settings['filters_continents'] = $_POST['filters_continents'];
                }

                /* Device type */
                if(isset($_POST['filters_device_type'])) {
                    $_POST['filters_device_type'] = array_filter($_POST['filters_device_type'] ?? [], function($device_type) {
                        return in_array($device_type, ['desktop', 'tablet', 'mobile']);
                    });

                    $has_filters = true;
                    $query->where('device_type', $_POST['filters_device_type'], 'IN');
                    $settings['filters_device_type'] = $_POST['filters_device_type'];
                }

                /* Languages */
                if(isset($_POST['filters_languages'])) {
                    $_POST['filters_languages'] = array_filter($_POST['filters_languages'], function($locale) {
                        return array_key_exists($locale, get_locale_languages_array());
                    });

                    $has_filters = true;
                    $query->where('browser_language', $_POST['filters_languages'], 'IN');
                    $settings['filters_languages'] = $_POST['filters_languages'];
                }

                /* Filters operating systems */
                if(isset($_POST['filters_operating_systems'])) {
                    $_POST['filters_operating_systems'] = array_filter($_POST['filters_operating_systems'], function($os_name) {
                        return in_array($os_name, ['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS']);
                    });

                    $has_filters = true;
                    $query->where('os_name', $_POST['filters_operating_systems'], 'IN');
                    $settings['filters_operating_systems'] = $_POST['filters_operating_systems'];
                }

                /* Filters browsers */
                if(isset($_POST['filters_browsers'])) {
                    $_POST['filters_browsers'] = array_filter($_POST['filters_browsers'], function($browser_name) {
                        return in_array($browser_name, ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet']);
                    });

                    $has_filters = true;
                    $query->where('browser_name', $_POST['filters_browsers'], 'IN');
                    $settings['filters_browsers'] = $_POST['filters_browsers'];
                }

                $subscribers = $has_filters ? $query->get('subscribers', null, ['subscriber_id']) : [];

                db()->reset();

                break;
        }

        $subscribers_ids = array_column($subscribers, 'subscriber_id');

        /* Free memory */
        unset($subscribers);

        if($campaign->status != 'sent') {
            $status = $_POST['is_scheduled'] && $_POST['scheduled_datetime'] ? 'scheduled' : 'processing';
            if(isset($_POST['save'])) {
                $status = 'draft';
            }

            if($status != 'draft') {
                /* Check for the plan limit */
                $sent_push_notifications_current_month = db()->where('user_id', $this->api_user->user_id)->getValue('users', '`pusher_sent_push_notifications_current_month`');
                if($this->api_user->plan_settings->sent_push_notifications_per_month_limit != -1 && $sent_push_notifications_current_month + count($subscribers_ids) >= $this->api_user->plan_settings->sent_push_notifications_per_month_limit) {
                    $this->response_error(l('global.info_message.plan_feature_limit'), 401);
                }
            }
        }

        /* Uploaded image */
        $image = \Altum\Uploads::process_upload($campaign->image, 'websites_campaigns_images', 'image', 'image_remove', settings()->websites->campaign_image_size_limit, 'json_error');

        if($campaign->status == 'sent') {
            /* Database query */
            db()->where('campaign_id', $campaign->campaign_id)->update('campaigns', [
                'name' => $_POST['name'],
                'last_datetime' => get_date(),
            ]);
        }

        else {
            /* Database query */
            db()->where('campaign_id', $campaign->campaign_id)->update('campaigns', [
                'website_id' => $_POST['website_id'],
                'name' => $_POST['name'],
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'url' => $_POST['url'],
                'image' => $image,
                'segment' => $_POST['segment'],
                'settings' => json_encode($settings),
                'subscribers_ids' => json_encode($subscribers_ids),
                'sent_subscribers_ids' => '[]',
                'total_push_notifications' => count($subscribers_ids),
                'status' => $status,
                'scheduled_datetime' => $_POST['scheduled_datetime'],
                'last_datetime' => get_date(),
            ]);
        }

        if(!isset($_POST['save'])) {
            /* Update the total website sent campaigns */
            db()->where('website_id', $_POST['website_id'])->update('websites', [
                'total_sent_campaigns' => db()->inc()
            ]);
        }

        /* Clear the cache */
        cache()->deleteItem('campaigns_dashboard?user_id=' . $this->api_user->user_id);

        /* Prepare the data */
        $data = [
            'id' => $campaign->campaign_id,
            'user_id' => (int) $campaign->user_id,
            'website_id' => (int) $_POST['website_id'],
            'rss_automation_id' => null,
            'recurring_campaign_id' => null,
            'name' => $_POST['name'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'url' => $_POST['url'],
            'image_url' => $image ? \Altum\Uploads::get_full_url('websites_campaigns_images') . $image : null,
            'segment' => $_POST['segment'],
            'settings' => $settings,
            'subscribers_ids' => $subscribers_ids,
            'sent_subscribers_ids' => [],
            'total_push_notifications' => count($subscribers_ids),
            'total_sent_push_notifications' => (int) $campaign->total_sent_push_notifications,
            'total_displayed_push_notifications' => (int) $campaign->total_displayed_push_notifications,
            'total_clicked_push_notifications' => (int) $campaign->total_clicked_push_notifications,
            'total_closed_push_notifications' => (int) $campaign->total_closed_push_notifications,
            'status' => $status,
            'scheduled_datetime' => $_POST['scheduled_datetime'],
            'last_sent_datetime' => $campaign->last_sent_datetime,
            'last_datetime' => get_date(),
            'datetime' => $campaign->datetime,
        ];

        Response::jsonapi_success($data, null, 200);

    }

    private function delete() {

        $campaign_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        /* Try to get details about the resource id */
        $campaign = db()->where('campaign_id', $campaign_id)->where('user_id', $this->api_user->user_id)->getOne('campaigns');

        /* We haven't found the resource */
        if(!$campaign) {
            $this->return_404();
        }

        /* Delete the resource */
        (new \Altum\Models\Campaign())->delete($campaign_id);

        http_response_code(200);
        die();

    }
}
