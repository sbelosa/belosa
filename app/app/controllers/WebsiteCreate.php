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

class WebsiteCreate extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('create.websites')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('websites');
        }

        /* Check for the plan limit */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `websites` WHERE `user_id` = {$this->user->user_id}")->fetch_object()->total ?? 0;

        if($this->user->plan_settings->websites_limit != -1 && $total_rows >= $this->user->plan_settings->websites_limit) {
            Alerts::add_error(l('global.info_message.plan_feature_limit') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
            redirect('websites');
        }

        /* Get available notification handlers */
        $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

        if(!empty($_POST)) {
            $_POST['name'] = input_clean($_POST['name'], 256);
            $_POST['scheme'] = in_array($_POST['scheme'], ['https://', 'http://']) ? query_clean($_POST['scheme']) : 'https://';
            $_POST['host'] = str_replace(' ', '', mb_strtolower(input_clean($_POST['host'], 128)));
            $_POST['host'] = string_starts_with('http://', $_POST['host']) || string_starts_with('https://', $_POST['host']) ? parse_url($_POST['host'], PHP_URL_HOST) : $_POST['host'];
            $is_enabled = 1;

            /* Get available custom domains */
            $domain_id = null;
            if(isset($_POST['domain_id'])) {
                $domain = (new \Altum\Models\Domain())->get_domain_by_domain_id($_POST['domain_id']);

                if($domain && $domain->user_id == $this->user->user_id) {
                    $domain_id = $domain->domain_id;
                }
            }

            /* Notifications processing */
            $_POST['notifications'] = array_map(
                function($notification_handler_id) {
                    return (int) $notification_handler_id;
                },
                array_filter($_POST['notifications'] ?? [], function($notification_handler_id) use($notification_handlers) {
                    return array_key_exists($notification_handler_id, $notification_handlers);
                })
            );
            if($this->user->plan_settings->active_notification_handlers_per_resource_limit != -1) {
                $_POST['notifications'] = array_slice($_POST['notifications'], 0, $this->user->plan_settings->active_notification_handlers_per_resource_limit);
            }

            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Check for any errors */
            $required_fields = ['name', 'host'];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(in_array(get_domain_from_url($_POST['host']), settings()->websites->blacklisted_domains)) {
                Alerts::add_field_error('host', l('websites.error_message.blacklisted_domain'));
            }

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                /* Domain checking */
                $host = $_POST['host'];
                $path = null;

                if(function_exists('idn_to_utf8')) {
                    $path = parse_url($_POST['scheme'] . idn_to_utf8($host), PHP_URL_PATH);
                    $host = parse_url($_POST['scheme'] . idn_to_utf8($host), PHP_URL_HOST);
                }

                if(function_exists('idn_to_ascii')) {
                    $host = idn_to_ascii($host);
                }

                /* Generate a unique pixel key for the website */
                $pixel_key = string_generate(16);
                while(db()->where('pixel_key', $pixel_key)->getOne('websites', ['pixel_key'])) {
                    $pixel_key = string_generate(16);
                }

                /* Generate the unique VAPID keys */
                $vapid_keys = \Minishlink\WebPush\VAPID::createVapidKeys();

                $keys = json_encode([
                    'public_key' => $vapid_keys['publicKey'],
                    'private_key' => $vapid_keys['privateKey'],
                ]);

                $settings = json_encode([
                    'icon' => null,
                    'ip_storage_is_enabled' => false,
                    'branding_name' => null,
                    'branding_url' => null,
                ]);

                $widget = json_encode([
                    'is_enabled' => true,

                    /* Subscribe */
                    'title' => l('pixel.widget.title'),
                    'description' => l('pixel.widget.description'),
                    'subscribe_button' => l('pixel.widget.subscribe_button'),
                    'close_button' => l('pixel.widget.close_button'),
                    'image_url' => ASSETS_FULL_URL . 'images/pixel/bell.svg',
                    'image_alt' => '',

                    /* Subscribed */
                    'subscribed_title' => l('pixel.widget.subscribed_title'),
                    'subscribed_description' => l('pixel.widget.subscribed_description'),
                    'subscribed_image_url' => ASSETS_FULL_URL . 'images/pixel/check-circle.svg',
                    'subscribed_image_alt' => '',
                    'subscribed_success_url' => '',

                    /* Permission denied */
                    'permission_denied_title' => l('pixel.widget.permission_denied_title'),
                    'permission_denied_description' => l('pixel.widget.permission_denied_description'),
                    'permission_denied_refresh_button' => l('pixel.widget.permission_denied_refresh_button'),
                    'permission_denied_close_button' => l('pixel.widget.permission_denied_close_button'),
                    'permission_denied_image_url' => ASSETS_FULL_URL . 'images/pixel/sad.svg',
                    'permission_denied_image_alt' => '',

                    /* Targeting */
                    'display_continents' => [],
                    'display_countries' => [],
                    'display_languages' => [],
                    'display_operating_systems' => [],
                    'display_browsers' => [],
                    'display_mobile' => true,
                    'display_desktop' => true,

                    /* Triggers */
                    'trigger_all_pages' => true,
                    'triggers' => [],
                    'display_trigger' => 'delay',
                    'display_trigger_value' => 5,
                    'display_frequency' => 'all_time',
                    'display_delay_type_after_close' => 'time_on_site',
                    'display_delay_value_after_close' => 60 * 60,

                    /* Display */
                    'direction' => l('direction'),
                    'display_duration' => -1,
                    'display_position' => 'top_center',
                    'display_branding' => true,

                    /* Customize */
                    'font' => 'inherit',
                    'title_color' => '#000000',
                    'description_color' => '#61717f',
                    'background_color' => '#ffffff',
                    'subscribe_button_text_color' => '#ffffff',
                    'subscribe_button_background_color' => '#000000',
                    'close_button_text_color' => '#4c5461',
                    'close_button_background_color' => '#f1f2f4',
                    'border_color' => '#000000',
                    'internal_padding' => 12,
                    'display_shadow' => true,
                    'border_radius' => 'highly_rounded',
                    'border_width' => 0,
                    'hover_animation' => 'slow_scale_up',
                    'on_animation' => '',
                    'off_animation' => '',
                    'animation' => '',
                    'animation_interval' => 5,
                ]);

                $button = json_encode([
                    'is_enabled' => false,

                    /* Subscribe */
                    'title' => l('pixel.button.title'),
                    'description' => l('pixel.button.description'),
                    'image_url' => ASSETS_FULL_URL . 'images/pixel/bell.svg',
                    'image_alt' => '',

                    /* Subscribed */
                    'subscribed_title' => l('pixel.button.subscribed_title'),
                    'subscribed_description' => l('pixel.button.subscribed_description'),
                    'subscribed_image_url' => ASSETS_FULL_URL . 'images/pixel/check-circle.svg',
                    'subscribed_image_alt' => '',
                    'subscribed_success_url' => '',

                    /* Unsubscribe */
                    'unsubscribe_title' => l('pixel.button.unsubscribe_title'),
                    'unsubscribe_description' => l('pixel.button.unsubscribe_description'),
                    'unsubscribe_image_url' => ASSETS_FULL_URL . 'images/pixel/minus-circle.svg',
                    'unsubscribe_image_alt' => '',
                    'unsubscribe_success_url' => '',

                    /* Unsubscribed */
                    'unsubscribed_title' => l('pixel.button.unsubscribed_title'),
                    'unsubscribed_description' => l('pixel.button.unsubscribed_description'),
                    'unsubscribed_image_url' => ASSETS_FULL_URL . 'images/pixel/minus-circle.svg',
                    'unsubscribed_image_alt' => '',
                    'unsubscribed_success_url' => '',

                    /* Permission denied */
                    'permission_denied_title' => l('pixel.button.permission_denied_title'),
                    'permission_denied_description' => l('pixel.button.permission_denied_description'),
                    'permission_denied_image_url' => ASSETS_FULL_URL . 'images/pixel/sad.svg',
                    'permission_denied_image_alt' => '',

                    /* Targeting */
                    'display_continents' => [],
                    'display_countries' => [],
                    'display_languages' => [],
                    'display_operating_systems' => [],
                    'display_browsers' => [],
                    'display_mobile' => true,
                    'display_desktop' => true,

                    /* Triggers */
                    'trigger_all_pages' => true,
                    'triggers' => [],

                    /* Display */
                    'direction' => l('direction'),
                    'display_branding' => true,

                    /* Customize */
                    'font' => 'inherit',
                    'title_color' => '#000000',
                    'description_color' => '#61717f',
                    'background_color' => '#f5f6f7',
                    'border_color' => '#000000',
                    'internal_padding' => 12,
                    'display_shadow' => false,
                    'border_radius' => 'highly_rounded',
                    'border_width' => 0,
                    'hover_animation' => 'slow_scale_up',
                ]);

                $notifications = json_encode([]);

                /* Database query */
                $website_id = db()->insert('websites', [
                    'user_id' => $this->user->user_id,
                    'name' => $_POST['name'],
                    'domain_id' => $domain_id,
                    'pixel_key' => $pixel_key,
                    'scheme' => $_POST['scheme'],
                    'host' => $host,
                    'path' => $path,
                    'settings' => $settings,
                    'widget' => $widget,
                    'button' => $button,
                    'notifications' => $notifications,
                    'keys' => $keys,
                    'is_enabled' => $is_enabled,
                    'datetime' => get_date(),
                ]);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.create1'), '<strong>' . $_POST['name'] . '</strong>'));

                /* Clear the cache */
                cache()->deleteItem('websites?user_id=' . $this->user->user_id);
                cache()->deleteItem('websites_total?user_id=' . $this->user->user_id);

                redirect('website/' . $website_id . '?install');
            }

        }

        $values = [
            'name' => $_POST['name'] ?? '',
            'scheme' => $_POST['scheme'] ?? '',
            'host' => $_POST['host'] ?? '',
        ];

        /* Prepare the view */
        $data = [
            'notification_handlers' => $notification_handlers,
            'values' => $values
        ];

        $view = new \Altum\View('website-create/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
