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

class WebsiteSubscribeWidget extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.websites')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('websites');
        }

        $website_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$website = db()->where('website_id', $website_id)->where('user_id', $this->user->user_id)->getOne('websites')) {
            redirect('websites');
        }

        $website->widget = json_decode($website->widget ?? '');

        if(!empty($_POST)) {
            $_POST['is_enabled'] = (int) isset($_POST['is_enabled']);

            /* Initiate purifier */
            $purifier_config = \HTMLPurifier_Config::createDefault();
            $purifier_config->set('HTML.Allowed', 'span[style]');
            $purifier_config->set('CSS.AllowedProperties', 'color,font-weight,font-style,text-decoration,font-family,background-color,text-transform,margin,padding,text-align');
            $purifier = new \HTMLPurifier($purifier_config);

            /* Main */
            $_POST['title'] = $purifier->purify(mb_substr($_POST['title'], 0, 256));
            $_POST['description'] = $purifier->purify(mb_substr($_POST['description'], 0, 256));
            $_POST['subscribe_button'] = $purifier->purify(mb_substr($_POST['subscribe_button'], 0, 32));
            $_POST['close_button'] = $purifier->purify(mb_substr($_POST['close_button'], 0, 32));
            $_POST['image_url'] = get_url($_POST['image_url'], 512);
            $_POST['image_alt'] = input_clean($_POST['image_alt'], 100);

            /* Success */
            $_POST['subscribed_title'] = $purifier->purify(mb_substr($_POST['subscribed_title'], 0, 256));
            $_POST['subscribed_description'] = $purifier->purify(mb_substr($_POST['subscribed_description'], 0, 256));
            $_POST['subscribed_image_url'] = get_url($_POST['subscribed_image_url'], 512);
            $_POST['subscribed_image_alt'] = input_clean($_POST['subscribed_image_alt'], 100);
            $_POST['subscribed_success_url'] = get_url($_POST['subscribed_success_url'], 512);

            /* Permission denied */
            $_POST['permission_denied_title'] = $purifier->purify(mb_substr($_POST['permission_denied_title'], 0, 256));
            $_POST['permission_denied_description'] = $purifier->purify(mb_substr($_POST['permission_denied_description'], 0, 256));
            $_POST['permission_denied_refresh_button'] = $purifier->purify(mb_substr($_POST['permission_denied_refresh_button'], 0, 256));
            $_POST['permission_denied_close_button'] = $purifier->purify(mb_substr($_POST['permission_denied_close_button'], 0, 256));
            $_POST['permission_denied_image_url'] = get_url($_POST['permission_denied_image_url'], 512);
            $_POST['permission_denied_image_alt'] = input_clean($_POST['permission_denied_image_alt'], 100);

            /* Targeting */
            $_POST['display_continents'] = array_filter($_POST['display_continents'] ?? [], function($country) {
                return array_key_exists($country, get_continents_array());
            });

            $_POST['display_countries'] = array_filter($_POST['display_countries'] ?? [], function($country) {
                return array_key_exists($country, get_countries_array());
            });

            $_POST['display_devices'] = array_filter($_POST['display_devices'] ?? [], function($device_type) {
                return in_array($device_type, ['desktop', 'tablet', 'mobile']);
            });

            $_POST['display_languages'] = array_filter($_POST['display_languages'] ?? [], function($locale) {
                return array_key_exists($locale, get_locale_languages_array());
            });

            $_POST['display_operating_systems'] = array_filter($_POST['display_operating_systems'] ?? [], function($os_name) {
                return in_array($os_name, ['iOS', 'Android', 'Windows', 'OS X', 'Linux', 'Ubuntu', 'Chrome OS']);
            });

            $_POST['display_browsers'] = array_filter($_POST['display_browsers'] ?? [], function($browser_name) {
                return in_array($browser_name, ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Samsung Internet']);
            });

            $_POST['display_mobile'] = (int) isset($_POST['display_mobile']);
            $_POST['display_desktop'] = (int) isset($_POST['display_desktop']);

            /* Triggers */
            $_POST['trigger_all_pages'] = (int) isset($_POST['trigger_all_pages']);
            $_POST['display_trigger'] = in_array($_POST['display_trigger'], [
                'delay',
                'time_on_site',
                'pageviews',
                'inactivity',
                'exit_intent',
                'scroll',
                'click',
                'hover',
            ]) ? $_POST['display_trigger'] : 'delay';
            $_POST['display_trigger_value'] = in_array($_POST['display_trigger'], ['delay', 'time_on_site', 'pageviews', 'inactivity', 'exit_intent', 'scroll']) ? (int) $_POST['display_trigger_value'] : input_clean($_POST['display_trigger_value']);

            $_POST['display_delay_type_after_close'] = in_array($_POST['display_delay_type_after_close'], ['time_on_site', 'pageviews',]) ? $_POST['display_delay_type_after_close'] : 'delay';
            $_POST['display_delay_value_after_close'] = (int) $_POST['display_delay_value_after_close'];

            /* Go over the triggers and clean them */
            foreach($_POST['trigger_type'] as $key => $value) {
                $_POST['trigger_type'][$key] = in_array($value, ['exact', 'not_exact', 'contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with', 'page_contains']) ? query_clean($value) : 'exact';
            }

            foreach($_POST['trigger_value'] as $key => $value) {
                $_POST['trigger_value'][$key] = input_clean($value, 512);
            }

            /* Generate the trigger rules var */
            $triggers = [];

            foreach($_POST['trigger_type'] as $key => $value) {
                $triggers[] = [
                    'type' => $value,
                    'value' => $_POST['trigger_value'][$key]
                ];
            }

            $_POST['display_frequency'] = in_array($_POST['display_frequency'], [
                'all_time',
                'once_per_session',
                'once_per_browser',
            ]) ? $_POST['display_frequency'] : 'all_time';


            /* Display */
            $_POST['direction'] = in_array($_POST['direction'], ['rtl', 'ltr']) ? $_POST['direction'] : 'ltr';
            $_POST['display_duration'] = (int) $_POST['display_duration'];
            $_POST['display_position'] = in_array($_POST['display_position'], [
                'top_left',
                'top_center',
                'top_right',
                'middle_left',
                'middle_center',
                'middle_right',
                'bottom_left',
                'bottom_center',
                'bottom_right',
                'top',
                'bottom',
                'top_floating',
                'bottom_floating'
            ]) ? $_POST['display_position'] : 'bottom_left';
            $_POST['display_branding'] = (int) isset($_POST['display_branding']);

            /* Customize */
            $_POST['font'] = in_array($_POST['font'], [
                'inherit',
                'Arial',
                'Verdana',
                'Helvetica',
                'Tahoma',
                'Trebuchet MS',
                'Times New Roman',
                'Georgia',
                'Courier New',
                'Monaco',
                'Comic Sans MS',
                'Courier',
                'Impact',
                'Futura',
                'Luminari',
                'Baskerville',
                'Papyrus',
                'Brush Script MT',
            ]) ? $_POST['font'] : 'inherit';

            $_POST['display_shadow'] = (int) isset($_POST['display_shadow']);
            $_POST['border_width'] = (int) ($_POST['border_width'] >= 0 && $_POST['border_width'] <= 5 ? $_POST['border_width'] : 0);
            $_POST['border_radius'] = in_array($_POST['border_radius'], [
                'straight',
                'rounded',
                'highly_rounded',
            ]) ? $_POST['border_radius'] : 'rounded';

            $_POST['hover_animation'] = in_array($_POST['hover_animation'], [
                '',
                'fast_scale_up',
                'slow_scale_up',
                'fast_scale_down',
                'slow_scale_down',
            ]) ? $_POST['hover_animation'] : '';
            $_POST['on_animation'] = in_array($_POST['on_animation'], [
                'fadeIn',
                'slideInUp',
                'slideInDown',
                'zoomIn',
                'bounceIn',
            ]) ? $_POST['on_animation'] : 'fadeIn';
            $_POST['off_animation'] = in_array($_POST['off_animation'], [
                'fadeOut',
                'slideOutUp',
                'slideOutDown',
                'zoomOut',
                'bounceOut',
            ]) ? $_POST['off_animation'] : 'fadeOut';
            $_POST['animation'] = in_array($_POST['animation'], [
                '',
                'heartbeat',
                'bounce',
                'flash',
                'pulse',
            ]) ? $_POST['animation'] : '';
            $_POST['animation_interval'] = (int) $_POST['animation_interval'];

            /* Go over all the possible color inputs and make sure they comply */
            foreach($_POST as $key => $value) {
                if(string_ends_with('_color', $key) && !verify_hex_color($value)) {
                    /* Replace it with a plain black color */
                    $_POST[$key] = '#000000';
                }
            }

            $_POST['internal_padding'] = (int) ($_POST['internal_padding'] >= 5 && $_POST['internal_padding'] <= 25 ? $_POST['internal_padding'] : 12);

            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Check for any errors */
            $required_fields = ['is_enabled'];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                $widget = json_encode([
                    'is_enabled' => $_POST['is_enabled'],

                    /* Main */
                    'title' => $_POST['title'],
                    'description' => $_POST['description'],
                    'subscribe_button' => $_POST['subscribe_button'],
                    'close_button' => $_POST['close_button'],
                    'image_url' => $_POST['image_url'],
                    'image_alt' => $_POST['image_alt'],

                    /* Success */
                    'subscribed_title' => $_POST['subscribed_title'],
                    'subscribed_description' => $_POST['subscribed_description'],
                    'subscribed_image_url' => $_POST['subscribed_image_url'],
                    'subscribed_image_alt' => $_POST['subscribed_image_alt'],
                    'subscribed_success_url' => $_POST['subscribed_success_url'],

                    /* Permission denied */
                    'permission_denied_title' => $_POST['permission_denied_title'],
                    'permission_denied_description' => $_POST['permission_denied_description'],
                    'permission_denied_refresh_button' => $_POST['permission_denied_refresh_button'],
                    'permission_denied_close_button' => $_POST['permission_denied_close_button'],
                    'permission_denied_image_url' => $_POST['permission_denied_image_url'],
                    'permission_denied_image_alt' => $_POST['permission_denied_image_alt'],

                    /* Targeting */
                    'display_continents' => $_POST['display_continents'],
                    'display_countries' => $_POST['display_countries'],
                    'display_languages' => $_POST['display_languages'],
                    'display_operating_systems' => $_POST['display_operating_systems'],
                    'display_browsers' => $_POST['display_browsers'],
                    'display_mobile' => $_POST['display_mobile'],
                    'display_desktop' => $_POST['display_desktop'],

                    /* Triggers */
                    'trigger_all_pages' => $_POST['trigger_all_pages'],
                    'triggers' => $triggers,
                    'display_trigger' => $_POST['display_trigger'],
                    'display_trigger_value' => $_POST['display_trigger_value'],
                    'display_frequency' => $_POST['display_frequency'],
                    'display_delay_type_after_close' => $_POST['display_delay_type_after_close'],
                    'display_delay_value_after_close' => $_POST['display_delay_value_after_close'],

                    /* Display */
                    'direction' => $_POST['direction'],
                    'display_duration' => $_POST['display_duration'],
                    'display_position' => $_POST['display_position'],
                    'display_close_button' => $_POST['display_close_button'],
                    'display_branding' => $_POST['display_branding'],

                    /* Customize */
                    'font' => $_POST['font'],
                    'title_color' => $_POST['title_color'],
                    'description_color' => $_POST['description_color'],
                    'background_color' => $_POST['background_color'],
                    'subscribe_button_text_color' => $_POST['subscribe_button_text_color'],
                    'subscribe_button_background_color' => $_POST['subscribe_button_background_color'],
                    'close_button_text_color' => $_POST['close_button_text_color'],
                    'close_button_background_color' => $_POST['close_button_background_color'],
                    'border_color' => $_POST['border_color'],
                    'internal_padding' => $_POST['internal_padding'],
                    'display_shadow' => $_POST['display_shadow'],
                    'border_radius' => $_POST['border_radius'],
                    'border_width' => $_POST['border_width'],
                    'hover_animation' => $_POST['hover_animation'],
                    'on_animation' => $_POST['on_animation'],
                    'off_animation' => $_POST['off_animation'],
                    'animation' => $_POST['animation'],
                    'animation_interval' => $_POST['animation_interval'],
                ]);

                /* Database query */
                db()->where('website_id', $website->website_id)->update('websites', [
                    'widget' => $widget,
                    'last_datetime' => get_date(),
                ]);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.update1'), '<strong>' . $website->name . '</strong>'));

                /* Clear the cache */
                cache()->deleteItem('websites?user_id=' . $this->user->user_id);
                cache()->deleteItem('website?website_id=' . $website->website_id);
                cache()->deleteItem('website?pixel_key=' . md5($website->pixel_key));

                redirect('website-subscribe-widget/' . $website_id);
            }
        }

        /* Get available custom domains */
        $domains = (new \Altum\Models\Domain())->get_available_domains_by_user($this->user);

        /* Prepare the view */
        $data = [
            'domains' => $domains,
            'website' => $website,
        ];

        $view = new \Altum\View('website-subscribe-widget/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

}
