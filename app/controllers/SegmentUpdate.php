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

class SegmentUpdate extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.segments')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('segments');
        }

        /* Check for the plan limit */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `segments` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total ?? 0;
        if($this->user->plan_settings->segments_limit != -1 && $total_rows > $this->user->plan_settings->segments_limit) {
            redirect('segments');
        }

        $segment_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$segment = db()->where('segment_id', $segment_id)->where('user_id', $this->user->user_id)->getOne('segments')) {
            redirect('segments');
        }

        $segment->settings = json_decode($segment->settings ?? '');

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->user->user_id);

        if(!empty($_POST)) {
            /* Filter some of the variables */
            $_POST['name'] = input_clean($_POST['name'], 256);
            $_POST['website_id'] = array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : array_key_first($websites);
            $_POST['type'] = isset($_POST['type']) && in_array($_POST['type'], ['custom', 'filter']) ? $_POST['type'] : 'filter';

            /* Subscribers ids */
            $_POST['subscribers_ids'] = trim($_POST['subscribers_ids'] ?? '');
            $_POST['subscribers_ids'] = array_filter(array_map('intval', explode(',', $_POST['subscribers_ids'])));
            $_POST['subscribers_ids'] = array_values(array_unique($_POST['subscribers_ids']));
            $_POST['subscribers_ids'] = $_POST['subscribers_ids'] ?: [0];

            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Check for any errors */
            $required_fields = ['name'];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                $settings = [];

                /* Get all the users needed */
                switch($_POST['type']) {

                    case 'custom':
                        $subscribers = db()->where('user_id', $this->user->user_id)->where('website_id', $_POST['website_id'])->where('subscriber_id', $_POST['subscribers_ids'], 'IN')->get('subscribers', null, ['subscriber_id']);
                        break;

                    case 'filter':

                        $query = db()->where('user_id', $this->user->user_id)->where('website_id', $_POST['website_id']);

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

                $settings['subscribers_ids'] = $_POST['type'] == 'custom' ? $subscribers_ids : [];

                /* Database query */
                db()->where('segment_id', $segment->segment_id)->update('segments', [
                    'website_id' => $_POST['website_id'],
                    'user_id' => $this->user->user_id,
                    'name' => $_POST['name'],
                    'type' => $_POST['type'],
                    'settings' => json_encode($settings),
                    'total_subscribers' => count($subscribers_ids),
                    'last_datetime' => get_date(),
                ]);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.update1'), '<strong>' . $_POST['name'] . '</strong>'));

                /* Clear the cache */
                cache()->deleteItem('segment?segment_id=' . $segment->segment_id);

                /* Refresh the page */
                redirect('segment-update/' . $segment_id);
            }
        }

        /* Prepare the view */
        $data = [
            'segment' => $segment,
            'websites' => $websites,
        ];

        $view = new \Altum\View('segment-update/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
