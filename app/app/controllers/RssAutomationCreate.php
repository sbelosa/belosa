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

class RssAutomationCreate extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.rss_automations')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('rss-automations');
        }

        /* Check for the plan limit */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `rss_automations` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total ?? 0;
        if($this->user->plan_settings->rss_automations_limit != -1 && $total_rows >= $this->user->plan_settings->rss_automations_limit) {
            Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
            redirect('rss-automations');
        }

        /* Get available websites */
        $websites = (new \Altum\Models\Website())->get_websites_by_user_id($this->user->user_id);

        /* Get available segments */
        $segments = (new \Altum\Models\Segment())->get_segments_by_user_id($this->user->user_id);

        /* TTL */
        $notifications_ttl = require APP_PATH . 'includes/notifications_ttl.php';

        /* RSS automation check intervals */
        $rss_automations_check_intervals = require APP_PATH . 'includes/rss_automations_check_intervals.php';

        if(!empty($_POST)) {
            /* Filter some of the variables */
            $_POST['name'] = input_clean($_POST['name'], 256);
            $_POST['title'] = input_clean($_POST['title'], 64);
            $_POST['description'] = input_clean($_POST['description'], 128);
            $_POST['url'] = input_clean($_POST['url'], 512);
            $_POST['website_id'] = array_key_exists($_POST['website_id'], $websites) ? (int) $_POST['website_id'] : array_key_first($websites);
            $_POST['rss_url'] = get_url($_POST['rss_url'], 512);
            $_POST['is_enabled'] = (int) isset($_POST['is_enabled']);

            /* Segment */
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
            $_POST['button_title_1'] = input_clean($_POST['button_title_1'], 16);
            $_POST['button_url_1'] = get_url($_POST['button_url_1'], 512);
            $_POST['button_title_2'] = input_clean($_POST['button_title_2'], 16);
            $_POST['button_url_2'] = get_url($_POST['button_url_2'], 512);

            /* UTM */
            $_POST['utm_medium'] = input_clean($_POST['utm_medium'], 128);
            $_POST['utm_source'] = input_clean($_POST['utm_source'], 128);
            $_POST['utm_campaign'] = input_clean($_POST['utm_campaign'], 128);

            /* RSS */
            $_POST['check_interval_seconds'] = array_key_exists($_POST['check_interval_seconds'], $rss_automations_check_intervals) ? (int) $_POST['check_interval_seconds'] : array_key_last($rss_automations_check_intervals);
            $_POST['items_count'] = isset($_POST['items_count']) && in_array($_POST['items_count'], range(1, 100)) ? (int) $_POST['items_count'] : 1;
            $_POST['campaigns_delay'] = isset($_POST['campaigns_delay']) && in_array($_POST['campaigns_delay'], range(5, 1440)) ? (int) $_POST['campaigns_delay'] : 1;
            $_POST['unique_item_identifier'] = in_array($_POST['unique_item_identifier'], ['url', 'publication_date', 'id']) ? $_POST['unique_item_identifier'] : 'url';
            $_POST['use_rss_image'] = (int) isset($_POST['use_rss_image']);
            $_POST['disable_rss_automation_on_fail'] = (int) isset($_POST['disable_rss_automation_on_fail']);

            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Check for any errors */
            $required_fields = ['rss_url', 'name', 'title', 'description'];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            $rss_data = rss_feed_parse_url($_POST['rss_url']);

            if(!$rss_data) {
                Alerts::add_error(l('rss_automations.error_message.invalid_rss_url'));
            }

            /* Uploaded image */
            $image = \Altum\Uploads::process_upload(null, 'websites_rss_automations_images', 'image', 'image_remove', settings()->websites->rss_automation_image_size_limit);

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

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
                    'disable_rss_automation_on_fail' => $_POST['disable_rss_automation_on_fail'],
                ];

                /* Database query */
                $rss_automation_id = db()->insert('rss_automations', [
                    'website_id' => $_POST['website_id'],
                    'user_id' => $this->user->user_id,
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

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.create1'), '<strong>' . $_POST['name'] . '</strong>'));

                redirect('rss-automations');
            }

        }

        $values = [
            'website_id' => $_POST['website_id'] ?? $_GET['website_id'] ?? array_key_first($websites),
            'rss_url' => $_POST['rss_url'] ?? null,
            'name' => $_POST['name'] ?? null,
            'title' => $_POST['title'] ?? null,
            'description' => $_POST['description'] ?? null,
            'url' => $_POST['url'] ?? null,
            'ttl' => $_POST['ttl'] ?? array_key_last($notifications_ttl),
            'is_silent' => $_POST['is_silent'] ?? null,
            'is_auto_hide' => $_POST['is_auto_hide'] ?? null,
            'urgency' => $_POST['urgency'] ?? 'normal',
            'segment' => $_POST['segment'] ?? 'all',
            'button_title_1' => $_POST['button_title_1'] ?? null,
            'button_url_1' => $_POST['button_url_1'] ?? null,
            'button_title_2' => $_POST['button_title_2'] ?? null,
            'button_url_2' => $_POST['button_url_2'] ?? null,
            'is_enabled' => $_POST['is_enabled'] ?? true,
            'check_interval_seconds' => $_POST['check_interval_seconds'] ?? array_key_last($rss_automations_check_intervals),
            'items_count' => $_POST['items_count'] ?? 1,
            'campaigns_delay' => $_POST['campaigns_delay'] ?? 15,
            'unique_item_identifier' => $_POST['unique_item_identifier'] ?? 'url',
            'use_rss_image' => $_POST['use_rss_image'] ?? true,
            'disable_rss_automation_on_fail' => $_POST['disable_rss_automation_on_fail'] ?? true,

            /* UTM */
            'utm' => [
                'source' => $_POST['utm_source'] ?? '',
                'medium' => $_POST['utm_medium'] ?? '',
                'campaign' => $_POST['utm_campaign'] ?? '',
            ]
        ];

        /* Prepare the view */
        $data = [
            'values' => $values,
            'websites' => $websites,
            'notifications_ttl' => $notifications_ttl,
            'segments' => $segments,
            'rss_automations_check_intervals' => $rss_automations_check_intervals,
        ];

        $view = new \Altum\View('rss-automation-create/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
