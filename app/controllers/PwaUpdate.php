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

namespace Altum\controllers;

use Altum\Alerts;

defined('ALTUMCODE') || die();

class PwaUpdate extends Controller {

    public function index() {

        if(!\Altum\Plugin::is_active('pwa') || !settings()->pwa->is_enabled) {
            redirect('not-found');
        }

        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.pwas')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('pwas');
        }

        $pwa_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$pwa = db()->where('pwa_id', $pwa_id)->where('user_id', $this->user->user_id)->getOne('pwas')) {
            redirect('pwas');
        }

        $pwa->settings = json_decode($pwa->settings ?? '');

        function mime_content_type_from_url($url) {
            $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            $mime_types = [
                'png' => 'image/png',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                'svg' => 'image/svg+xml'
            ];
            return $mime_types[$extension] ?? 'image/png';
        }

        if(!empty($_POST)) {
            $_POST['name'] = input_clean($_POST['name'], 30);
            $_POST['short_name'] = input_clean($_POST['short_name'], 30);
            $_POST['description'] = input_clean($_POST['description'], 300);
            $_POST['start_url'] = get_url($_POST['start_url']);
            $_POST['display'] = isset($_POST['display']) && in_array($_POST['display'], ['fullscreen', 'standalone', 'minimal-ui', 'browser']) ? $_POST['display'] : 'standalone';
            $_POST['lang'] = input_clean($_POST['lang'], 8);
            $_POST['dir'] = isset($_POST['dir']) && in_array($_POST['dir'], ['auto', 'ltr', 'rtl']) ? $_POST['dir'] : 'auto';
            $_POST['orientation'] = isset($_POST['orientation']) && in_array($_POST['orientation'], ['portrait', 'landscape', 'any']) ? $_POST['orientation'] : 'any';
            $_POST['orientation'] = input_clean($_POST['orientation'], 20);
            $_POST['background_color'] = !verify_hex_color($_POST['background_color']) ? '#000000' : $_POST['background_color'];
            $_POST['theme_color'] = !verify_hex_color($_POST['theme_color']) ? '#000000' : $_POST['theme_color'];
            $_POST['app_icon_url'] = get_url($_POST['app_icon_url']);
            $_POST['app_icon_maskable_url'] = get_url($_POST['app_icon_maskable_url']);
            $_POST['id'] = input_clean($_POST['id'], 200);
            $_POST['scope_url'] = get_url($_POST['scope_url']);

            /* Screenshot URLs */
            for($i = 1; $i <= 6; $i++) {
                $_POST['mobile_screenshot_url_' . $i] = get_url($_POST['mobile_screenshot_url_' . $i]);
            }
            for($i = 1; $i <= 6; $i++) {
                $_POST['desktop_screenshot_url_' . $i] = get_url($_POST['desktop_screenshot_url_' . $i]);
            }

            /* Shortcuts */
            $shortcuts = [];
            for($i = 1; $i <= 3; $i++) {
                $shortcuts[] = [
                    'name' => input_clean($_POST['shortcut_name_' . $i] ?? '', 20),
                    'description' => input_clean($_POST['shortcut_description_' . $i] ?? '', 60),
                    'url' => get_url($_POST['shortcut_url_' . $i]),
                    'icon_url' => get_url($_POST['shortcut_icon_url_' . $i]),
                ];
            }

            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

            /* Check for any errors */
            $required_fields = ['name', 'app_icon_url'];
            foreach($required_fields as $field) {
                if(!isset($_POST[$field]) || trim($_POST[$field]) === '') {
                    Alerts::add_field_error($field, l('global.error_message.empty_field'));
                }
            }

            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                /* Generate the manifest file */
                $manifest_data = [];

                /* Base fields */
                $base_fields = ['name', 'short_name', 'description', 'start_url', 'display', 'orientation', 'background_color', 'theme_color', 'id', 'lang', 'dir', 'scope_url'];
                foreach($base_fields as $field_name) {
                    if(!empty($_POST[$field_name])) {
                        $manifest_data[$field_name] = $_POST[$field_name];
                    } elseif($field_name == 'start_url') {
                        $manifest_data[$field_name] = '/';
                    }
                }

                /* Icons */
                $icons = [];
                if(!empty($_POST['app_icon_url'])) {
                    $icons[] = [
                        'src' => $_POST['app_icon_url'],
                        'type' => mime_content_type_from_url($_POST['app_icon_url']),
                        'purpose' => 'any',
                        'sizes' => '512x512',
                    ];
                }
                if(!empty($_POST['app_icon_maskable_url'])) {
                    $icons[] = [
                        'src' => $_POST['app_icon_maskable_url'],
                        'type' => mime_content_type_from_url($_POST['app_icon_maskable_url']),
                        'purpose' => 'maskable',
                        'sizes' => '512x512',
                    ];
                }
                if(!empty($icons)) {
                    $manifest_data['icons'] = $icons;
                }

                /* Screenshots */
                $screenshots = [];
                for($i = 1; $i <= 6; $i++) {
                    $url = $_POST['mobile_screenshot_url_' . $i];
                    if(!empty($url)) {
                        $screenshots[] = [
                            'src' => $url,
                            'type' => mime_content_type_from_url($url),
                            'platform' => 'mobile'
                        ];
                    }
                }
                for($i = 1; $i <= 8; $i++) {
                    $url = $_POST['desktop_screenshot_url_' . $i];
                    if(!empty($url)) {
                        $screenshots[] = [
                            'src' => $url,
                            'type' => mime_content_type_from_url($url),
                            'platform' => 'wide'
                        ];
                    }
                }
                if(!empty($screenshots)) {
                    $manifest_data['screenshots'] = $screenshots;
                }

                /* Shortcuts */
                $manifest_shortcuts = [];
                foreach($shortcuts as $shortcut) {
                    if(!empty($shortcut['name']) && !empty($shortcut['url']) && !empty($shortcut['icon_url'])) {
                        $manifest_shortcuts[] = [
                            'name' => $shortcut['name'],
                            'description' => $shortcut['description'],
                            'url' => $shortcut['url'],
                            'icons' => [[
                                'src' => $shortcut['icon_url'],
                                'type' => mime_content_type_from_url($shortcut['icon_url'])
                            ]]
                        ];
                    }
                }
                if(!empty($manifest_shortcuts)) {
                    $manifest_data['shortcuts'] = $manifest_shortcuts;
                }

                /* Final manifest JSON */
                $manifest = json_encode($manifest_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

                /* Settings */
                $settings = json_encode([
                    'short_name' => $_POST['short_name'],
                    'description' => $_POST['description'],
                    'start_url' => $_POST['start_url'],
                    'display' => $_POST['display'],
                    'orientation' => $_POST['orientation'],
                    'background_color' => $_POST['background_color'],
                    'theme_color' => $_POST['theme_color'],
                    'app_icon_url' => $_POST['app_icon_url'],
                    'app_icon_maskable_url' => $_POST['app_icon_maskable_url'],
                    'mobile_screenshot_urls' => array_map(fn($i) => $_POST['mobile_screenshot_url_' . $i], range(1, 6)),
                    'desktop_screenshot_urls' => array_map(fn($i) => $_POST['desktop_screenshot_url_' . $i], range(1, 6)),
                    'shortcuts' => $shortcuts,
                    'id' => $_POST['id'],
                    'dir' => $_POST['dir'],
                    'lang' => $_POST['lang'],
                    'scope_url' => $_POST['scope_url'],
                ]);

                /* Database query */
                db()->where('pwa_id', $pwa->pwa_id)->update('pwas', [
                    'name' => $_POST['name'],
                    'settings' => $settings,
                    'manifest' => $manifest,
                    'last_datetime' => get_date(),
                ]);

                /* Set a nice success message */
                Alerts::add_success(sprintf(l('global.success_message.update1'), '<strong>' . $_POST['name'] . '</strong>'));


                redirect('pwa-update/' . $pwa_id);
            }
        }

        /* Prepare the view */
        $data = [
            'pwa' => $pwa,
        ];

        $view = new \Altum\View(\Altum\Plugin::get('pwa')->path . 'views/pwa-update/index', (array) $this, true);

        $this->add_view_content('content', $view->run($data));

    }

}
