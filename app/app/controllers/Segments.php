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
use Altum\Response;

defined('ALTUMCODE') || die();

class Segments extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['website_id', 'type'], ['name',], ['segment_id', 'name', 'datetime', 'last_datetime', 'total_subscribers']));
        $filters->set_default_order_by($this->user->preferences->segments_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `segments` WHERE `user_id` = {$this->user->user_id} {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('segments?' . $filters->get_get() . '&page=%d')));

        /* Get the segments list for the user */
        $segments = [];
        $segments_result = database()->query("SELECT * FROM `segments` WHERE `user_id` = {$this->user->user_id} {$filters->get_sql_where()} {$filters->get_sql_order_by()} {$paginator->get_sql_limit()}");
        while($row = $segments_result->fetch_object()) {
            $row->settings = json_decode($row->settings ?? '');
            $segments[] = $row;
        }

        /* Export handler */
        process_export_json($segments, ['segment_id', 'user_id', 'name', 'type', 'total_subscribers', 'settings', 'datetime', 'last_datetime',]);
        process_export_csv_new($segments, ['segment_id', 'user_id', 'name', 'type', 'total_subscribers', 'settings', 'datetime', 'last_datetime',], ['settings']);

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->user->user_id);

        /* Prepare the view */
        $data = [
            'segments' => $segments,
            'websites' => $websites,
            'total_segments' => $total_rows,
            'pagination' => $pagination,
            'filters' => $filters,
        ];

        $view = new \Altum\View('segments/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function get_segment_count() {

        if(!empty($_POST)) {
            redirect();
        }

        \Altum\Authentication::guard();

        $type = isset($_GET['type']) ? input_clean($_GET['type']) : 'all';
        $website_id = isset($_GET['website_id']) ? (int) $_GET['website_id'] : null;

        /* Get the website */
        $website = (new \Altum\Models\Website())->get_website_by_website_id($website_id);

        if(!$website || $website->user_id != $this->user->user_id) {
            Response::json('', 'success', ['count' => 0]);
        }

        /* Get settings from custom segments */
        if(is_numeric($type)) {
            $segment = (new \Altum\Models\Segment())->get_segment_by_segment_id($_GET['type']);

            if(!$segment || $website->website_id != $segment->website_id) {
                Response::json('', 'success', ['count' => 0]);
            }

            $type = $segment->type;

            /* Set the custom filters of the custom segment for processing */
            switch($type) {
                case 'custom':
                    $_GET['subscribers_ids'] = $segment->settings->subscribers_ids;
                    break;

                case 'filter':
                    if(isset($segment->settings->filters_subscribed_on_url)) $_GET['filters_subscribed_on_url'] = $segment->settings->filters_subscribed_on_url ?? '';
                    if(isset($segment->settings->filters_cities)) $_GET['filters_cities'] = $segment->settings->filters_cities ?? [];
                    if(isset($segment->settings->filters_countries)) $_GET['filters_countries'] = $segment->settings->filters_countries ?? [];
                    if(isset($segment->settings->filters_continents)) $_GET['filters_continents'] = $segment->settings->filters_continents ?? [];
                    if(isset($segment->settings->filters_device_type)) $_GET['filters_device_type'] = $segment->settings->filters_device_type ?? [];
                    if(isset($segment->settings->filters_languages)) $_GET['filters_languages'] = $segment->settings->filters_languages ?? [];
                    if(isset($segment->settings->filters_operating_systems)) $_GET['filters_operating_systems'] = $segment->settings->filters_operating_systems ?? [];
                    if(isset($segment->settings->filters_browsers)) $_GET['filters_browsers'] = $segment->settings->filters_browsers ?? [];
                    if(isset($segment->settings->filters_custom_parameters) && count($segment->settings->filters_custom_parameters)) {
                        foreach($segment->settings->filters_custom_parameters as $key => $custom_parameter) {
                            $_GET['filters_custom_parameter_key'][$key] = $custom_parameter->key;
                            $_GET['filters_custom_parameter_condition'][$key] = $custom_parameter->condition;
                            $_GET['filters_custom_parameter_value'][$key] = $custom_parameter->value;
                        }
                    }
                    break;
            }

        }

        switch($type) {
            case 'all':

                $count = db()->where('user_id', $this->user->user_id)->where('website_id', $website_id)->getValue('subscribers', 'COUNT(*)');

                break;

            case 'custom':

                if(empty($_GET['subscribers_ids'])) {
                    $count = 0;
                } else {
                    $count = db()->where('user_id', $this->user->user_id)->where('website_id', $_GET['website_id'])->where('subscriber_id', $_GET['subscribers_ids'], 'IN')->getValue('subscribers', 'COUNT(*)');
                }

                break;

            case 'filter':

                $query = db()->where('user_id', $this->user->user_id)->where('website_id', $website_id);

                $has_filters = false;

                /* Custom parameters */
                if(!isset($_GET['filters_custom_parameter_key'])) {
                    $_GET['filters_custom_parameter_key'] = [];
                    $_GET['filters_custom_parameter_condition'] = [];
                    $_GET['filters_custom_parameter_value'] = [];
                }

                $custom_parameters = [];

                foreach($_GET['filters_custom_parameter_key'] as $key => $value) {
                    if(empty(trim($value))) continue;
                    if($key >= 50) continue;

                    $custom_parameters[] = [
                        'key' => input_clean($value, 64),
                        'condition' => isset($_GET['filters_custom_parameter_condition'][$key]) && in_array($_GET['filters_custom_parameter_condition'][$key], ['exact', 'not_exact', 'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with', 'bigger_than', 'lower_than']) ? $_GET['filters_custom_parameter_condition'][$key] : 'exact',
                        'value' => input_clean($_GET['filters_custom_parameter_value'][$key], 512)
                    ];
                }

                if(count($custom_parameters)) {
                    $has_filters = true;

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
                if(!empty($_GET['filters_subscribed_on_url'])) {
                    $_GET['filters_subscribed_on_url'] = input_clean($_GET['filters_subscribed_on_url'], 2048);

                    $has_filters = true;
                    $query->where('subscribed_on_url', $_GET['filters_subscribed_on_url']);
                }

                /* Cities */
                if(!empty($_GET['filters_cities'])) {
                    $_GET['filters_cities'] = is_array($_GET['filters_cities']) ? $_GET['filters_cities'] : explode(',', $_GET['filters_cities']);

                    if(count($_GET['filters_cities'])) {
                        $_GET['filters_cities'] = array_map(function($city) {
                            return query_clean($city);
                        }, $_GET['filters_cities']);
                        $_GET['filters_cities'] = array_unique($_GET['filters_cities']);

                        $has_filters = true;
                        $query->where('city_name', $_GET['filters_cities'], 'IN');
                    }
                }

                /* Countries */
                if(isset($_GET['filters_countries'])) {
                    $_GET['filters_countries'] = array_filter($_GET['filters_countries'] ?? [], function($country) {
                        return array_key_exists($country, get_countries_array());
                    });

                    $has_filters = true;
                    $query->where('country_code', $_GET['filters_countries'], 'IN');
                }

                /* Continents */
                if(isset($_GET['filters_continents'])) {
                    $_GET['filters_continents'] = array_filter($_GET['filters_continents'] ?? [], function($country) {
                        return array_key_exists($country, get_continents_array());
                    });

                    $has_filters = true;
                    $query->where('continent_code', $_GET['filters_continents'], 'IN');
                }

                /* Device type */
                if(isset($_GET['filters_device_type'])) {
                    $_GET['filters_device_type'] = array_filter($_GET['filters_device_type'] ?? [], function($device_type) {
                        return in_array($device_type, ['desktop', 'tablet', 'mobile']);
                    });

                    $has_filters = true;
                    $query->where('device_type', $_GET['filters_device_type'], 'IN');
                }

                /* Languages */
                if(isset($_GET['filters_languages'])) {
                    $_GET['filters_languages'] = array_filter($_GET['filters_languages'], function($locale) {
                        return array_key_exists($locale, get_locale_languages_array());
                    });

                    $has_filters = true;
                    $query->where('browser_language', $_GET['filters_languages'], 'IN');
                }

                /* Filters operating systems */
                if(isset($_GET['filters_operating_systems'])) {
                    $_GET['filters_operating_systems'] = array_filter($_GET['filters_operating_systems'], function($os_name) {
                        return in_array($os_name, ['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS']);
                    });

                    $has_filters = true;
                    $query->where('os_name', $_GET['filters_operating_systems'], 'IN');
                }

                /* Filters browsers */
                if(isset($_GET['filters_browsers'])) {
                    $_GET['filters_browsers'] = array_filter($_GET['filters_browsers'], function($browser_name) {
                        return in_array($browser_name, ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet']);
                    });

                    $has_filters = true;
                    $query->where('browser_name', $_GET['filters_browsers'], 'IN');
                }

                $count = $has_filters ? $query->getValue('subscribers', 'COUNT(*)') : 0;

                break;

            default:
                $count = null;
                break;
        }

        Response::json('', 'success', ['count' => $count]);
    }

    public function bulk() {

        \Altum\Authentication::guard();

        /* Check for any errors */
        if(empty($_POST)) {
            redirect('segments');
        }

        if(empty($_POST['selected'])) {
            redirect('segments');
        }

        if(!isset($_POST['type'])) {
            redirect('segments');
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
                    if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.segments')) {
                        Alerts::add_error(l('global.info_message.team_no_access'));
                        redirect('segments');
                    }

                    foreach($_POST['selected'] as $segment_id) {
                        db()->where('segment_id', $segment_id)->where('user_id', $this->user->user_id)->delete('segments');

                        /* Clear the cache */
                        cache()->deleteItem('segments?user_id=' . $this->user->user_id);
                        cache()->deleteItem('segment?segment_id=' . $segment_id);
                    }

                    break;
            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('segments');
    }

    public function delete() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.segments')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('segments');
        }

        if(empty($_POST)) {
            redirect('segments');
        }

        $segment_id = (int) query_clean($_POST['segment_id']);

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!$segment = db()->where('segment_id', $segment_id)->where('user_id', $this->user->user_id)->getOne('segments', ['segment_id', 'name'])) {
            redirect('segments');
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Database query */
            db()->where('segment_id', $segment_id)->delete('segments');

            /* Set a nice success message */
            Alerts::add_success(sprintf(l('global.success_message.delete1'), '<strong>' . $segment->name . '</strong>'));

            /* Clear the cache */
            cache()->deleteItem('segments?user_id=' . $this->user->user_id);
            cache()->deleteItem('segment?segment_id=' . $segment_id);

            redirect('segments');
        }

        redirect('segments');
    }
}
