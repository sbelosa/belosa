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
use Altum\Date;

defined('ALTUMCODE') || die();

class CampaignUpdate extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.campaigns')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('campaigns');
        }

        $campaign_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$campaign = db()->where('campaign_id', $campaign_id)->where('user_id', $this->user->user_id)->getOne('campaigns')) {
            redirect('campaigns');
        }

        if($campaign->status == 'processing') {
            Alerts::add_error(l('campaigns.error_message.processing'));
            redirect('campaigns');
        }

        $campaign->subscribers_ids = implode(',', json_decode($campaign->subscribers_ids));
        $campaign->settings = json_decode($campaign->settings ?? '');

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->user->user_id);

        /* Get available segments */
        $segments = (new \Altum\Models\Segment())->get_segments_by_user_id($this->user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        if(!empty($_POST)) {
            /* Filter some of the variables */
            $_POST['name'] = input_clean($_POST['name'], 256);
            $_POST['title'] = input_clean($_POST['title'], 64);
            $_POST['description'] = input_clean($_POST['description'], 128);
            $_POST['url'] = input_clean($_POST['url'], 512);
            $_POST['website_id'] = array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : array_key_first($websites);

            /* Segment */
            $segment_type = null;
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
                (new \DateTime($_POST['scheduled_datetime'], new \DateTimeZone($this->user->timezone)))->setTimezone(new \DateTimeZone(\Altum\Date::$default_timezone))->format('Y-m-d H:i:s')
                : get_date();

            /* Advanced */
            $_POST['ttl'] = isset($_POST['ttl']) && array_key_exists($_POST['ttl'], $notifications_ttl) ? (int) $_POST['ttl'] : array_key_last($notifications_ttl);
            $_POST['urgency'] = isset($_POST['urgency']) && in_array($_POST['urgency'], ['low', 'normal', 'high']) ? $_POST['urgency'] : 'normal';
            $_POST['is_silent'] = (int) isset($_POST['is_silent']);
            $_POST['is_auto_hide'] = (int) isset($_POST['is_auto_hide']);

            /* Buttons */
            $_POST['button_title_1'] = input_clean($_POST['button_title_1'], 16);
            $_POST['button_url_1'] = get_url($_POST['button_url_1'], 512);
            $_POST['button_title_2'] = input_clean($_POST['button_title_2'], 16);
            $_POST['button_url_2'] = get_url($_POST['button_url_2'], 512);

            /* Subscribers ids */
            $_POST['subscribers_ids'] = trim($_POST['subscribers_ids'] ?? '');
            $_POST['subscribers_ids'] = array_filter(array_map('intval', explode(',', $_POST['subscribers_ids'])));
            $_POST['subscribers_ids'] = array_values(array_unique($_POST['subscribers_ids']));
            $_POST['subscribers_ids'] = $_POST['subscribers_ids'] ?: [0];

            /* UTM */
            $_POST['utm_medium'] = input_clean($_POST['utm_medium'], 128);
            $_POST['utm_source'] = input_clean($_POST['utm_source'], 128);
            $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'], 128);

            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Check for any errors */
            $required_fields = ['name', 'title', 'description'];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

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
                    $subscribers = db()->where('user_id', $this->user->user_id)->where('website_id', $_POST['website_id'])->get('subscribers', null, ['subscriber_id', 'user_id']);
                    break;

                case 'custom':
                    $subscribers = db()->where('user_id', $this->user->user_id)->where('website_id', $_POST['website_id'])->where('subscriber_id', $_POST['subscribers_ids'], 'IN')->get('subscribers', null, ['subscriber_id']);
                    break;

                case 'filter':

                    $query = db()->where('user_id', $this->user->user_id)->where('website_id', $_POST['website_id']);

                    $has_filters = false;

                    /* Custom parameters */
                    if(!isset($_POST['filters_custom_parameter_key'])) {
                        $_POST['filters_custom_parameter_key'] = [];
                        $_POST['filters_custom_parameter_value'] = [];
                    }

                    $custom_parameters = [];

                    foreach($_POST['filters_custom_parameter_key'] as $key => $value) {
                        if(empty(trim($value))) continue;
                        if($key >= 50) continue;

                        $custom_parameters[] = [
                            'key' => input_clean($value, 64),
                            'value' => input_clean($_POST['filters_custom_parameter_value'][$key], 512)
                        ];
                    }

                    if(count($custom_parameters)) {
                        $has_filters = true;
                        $settings['filters_custom_parameters'] = $custom_parameters;

                        foreach($custom_parameters as $custom_parameter) {
                            $key = $custom_parameter['key'];
                            $value = $custom_parameter['value'];

                            $query->where("JSON_EXTRACT(`custom_parameters`, '$.{$key}') = '$value'");
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
                    $sent_push_notifications_current_month = db()->where('user_id', $this->user->user_id)->getValue('users', '`pusher_sent_push_notifications_current_month`');
                    if($this->user->plan_settings->sent_push_notifications_per_month_limit != -1 && $sent_push_notifications_current_month + count($subscribers_ids) >= $this->user->plan_settings->sent_push_notifications_per_month_limit) {
                        Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
                    }
                }
            }

            /* Uploaded image */
            $image = \Altum\Uploads::process_upload($campaign->image, 'websites_campaigns_images', 'image', 'image_remove', settings()->websites->campaign_image_size_limit);

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

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

                /* Clear the cache */
                cache()->deleteItem('campaigns_dashboard?user_id=' . $this->user->user_id);

                if(isset($_POST['save'])) {
                    /* Set a nice success message */
                    Alerts::add_success(sprintf(l('campaigns.success_message.save'), '<strong>' . $_POST['name'] . '</strong>'));
                } else {
                    /* Update the total website sent campaigns */
                    db()->where('website_id', $_POST['website_id'])->update('websites', [
                        'total_sent_campaigns' => db()->inc()
                    ]);

                    /* Set a nice success message */
                    if($_POST['is_scheduled']) {
                        Alerts::add_success(sprintf(l('campaigns.success_message.scheduled'), '<strong>' . $_POST['name'] . '</strong>', '<strong>' . \Altum\Date::get_time_until($_POST['scheduled_datetime']) . '</strong>'));
                    } else {
                        Alerts::add_success(sprintf(l('campaigns.success_message.send'), '<strong>' . $_POST['name'] . '</strong>'));
                    }

                    redirect('campaigns');
                }

                /* Refresh the page */
                redirect('campaign-update/' . $campaign_id);
            }
        }

        /* Prepare the view */
        $data = [
            'campaign' => $campaign,
            'websites' => $websites,
            'segments' => $segments,
            'notifications_ttl' => $notifications_ttl,
        ];

        $view = new \Altum\View('campaign-update/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
