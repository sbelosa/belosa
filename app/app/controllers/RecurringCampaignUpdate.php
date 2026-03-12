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

class RecurringCampaignUpdate extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.recurring_campaign')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('flows');
        }

        /* Check for the plan limit */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `recurring_campaigns` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total ?? 0;
        if($this->user->plan_settings->recurring_campaigns_limit != -1 && $total_rows > $this->user->plan_settings->recurring_campaigns_limit) {
            redirect('recurring-campaigns');
        }

        $recurring_campaign_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$recurring_campaign = db()->where('recurring_campaign_id', $recurring_campaign_id)->where('user_id', $this->user->user_id)->getOne('recurring_campaigns')) {
            redirect('recurring-campaigns');
        }

        $recurring_campaign->settings = json_decode($recurring_campaign->settings ?? '');

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

            /* Recurring settings */
            $_POST['frequency'] = isset($_POST['frequency']) && in_array($_POST['frequency'], ['daily', 'weekly', 'monthly']) ? $_POST['frequency'] : 'monthly';
            $_POST['time'] = preg_match('/^(2[0-3]|[01]?[0-9]):[0-5][0-9]$/', $_POST['time']) ? $_POST['time'] : '00:00';
            $_POST['week_days'] = array_map('intval', array_filter($_POST['week_days'] ?? [], fn($key) => in_array($key, range(1, 7))));
            $_POST['month_days'] = array_map('intval', array_filter($_POST['month_days'] ?? [], fn($key) => in_array($key, range(1, 31))));

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

            /* Uploaded image */
            $image = \Altum\Uploads::process_upload($recurring_campaign->image, 'websites_recurring_campaigns_images', 'image', 'image_remove', settings()->websites->recurring_campaign_image_size_limit);

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

                    /* Recurring settings */
                    'frequency' => $_POST['frequency'],
                    'time' => $_POST['time'],
                    'week_days' => $_POST['week_days'],
                    'month_days' => $_POST['month_days'],
                ];

                /* Calculate the next run */
                $next_run_datetime = get_next_run_datetime($_POST['frequency'], $_POST['time'], $_POST['week_days'], $_POST['month_days'], $this->user->timezone, '-15 minutes');

                /* Database query */
                db()->where('recurring_campaign_id', $recurring_campaign->recurring_campaign_id)->update('recurring_campaigns', [
                    'website_id' => $_POST['website_id'],
                    'user_id' => $this->user->user_id,
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

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.update1'), '<strong>' . $_POST['name'] . '</strong>'));

                /* Refresh the page */
                redirect('recurring-campaign-update/' . $recurring_campaign_id);
            }
        }

        /* Prepare the view */
        $data = [
            'recurring_campaign' => $recurring_campaign,
            'websites' => $websites,
            'segments' => $segments,
            'notifications_ttl' => $notifications_ttl,
        ];

        $view = new \Altum\View('recurring-campaign-update/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
