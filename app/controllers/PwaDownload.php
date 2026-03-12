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

class PwaDownload extends Controller {

    public function index() {

        \Altum\Authentication::guard();

        $pwa_id = isset($this->params[0]) ? (int) $this->params[0] : null;

        if(!$pwa = db()->where('pwa_id', $pwa_id)->where('user_id', $this->user->user_id)->getOne('pwas')) {
            redirect('pwas');
        }

        $settings = json_decode($pwa->settings ?? '', true);

        /* Define the allowed field names */
        $field_names = [
            'short_name', 'description', 'start_url', 'display', 'orientation',
            'background_color', 'theme_color', 'app_icon_url', 'app_icon_maskable_url',
            'id', 'dir', 'lang', 'scope'
        ];

        /* Helper for MIME types */
        function get_image_type_from_url($url) {
            /* Get file extension */
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

        /* Build the manifest array */
        $manifest_json = [];

        $manifest_json['name'] = $pwa->name;

        foreach($field_names as $field_name) {
            $field_value = $settings[$field_name] ?? null;
            if($field_name == 'start_url' && empty($field_value)) {
                $manifest_json[$field_name] = '/';
            } elseif(!empty($field_value) && !in_array($field_name, ['app_icon_url', 'app_icon_maskable_url'])) {
                $manifest_json[$field_name] = $field_value;
            }
        }

        /* Icons */
        $icons = [];
        if(!empty($settings['app_icon_url'])) {
            $icons[] = [
                'src' => $settings['app_icon_url'],
                'type' => get_image_type_from_url($settings['app_icon_url']),
                'purpose' => 'any'
            ];
        }
        if(!empty($settings['app_icon_maskable_url'])) {
            $icons[] = [
                'src' => $settings['app_icon_maskable_url'],
                'type' => get_image_type_from_url($settings['app_icon_maskable_url']),
                'purpose' => 'maskable'
            ];
        }
        if(count($icons) > 0) {
            $manifest_json['icons'] = $icons;
        }

        /* Screenshots */
        $screenshots = [];
        for($i = 1; $i <= 6; $i++) {
            $mobile_screenshot_url = $settings['mobile_screenshot_url_' . $i] ?? null;
            if(!empty($mobile_screenshot_url)) {
                $screenshots[] = [
                    'src' => $mobile_screenshot_url,
                    'type' => get_image_type_from_url($mobile_screenshot_url),
                    'platform' => 'mobile'
                ];
            }
        }
        for($i = 1; $i <= 6; $i++) {
            $desktop_screenshot_url = $settings['desktop_screenshot_url_' . $i] ?? null;
            if(!empty($desktop_screenshot_url)) {
                $screenshots[] = [
                    'src' => $desktop_screenshot_url,
                    'type' => get_image_type_from_url($desktop_screenshot_url),
                    'platform' => 'wide'
                ];
            }
        }
        if(count($screenshots) > 0) {
            $manifest_json['screenshots'] = $screenshots;
        }

        /* Shortcuts */
        $shortcuts = [];
        for($i = 1; $i <= 3; $i++) {
            $shortcut_name = $settings['shortcut_name_' . $i] ?? null;
            $shortcut_description = $settings['shortcut_description_' . $i] ?? null;
            $shortcut_url = $settings['shortcut_url_' . $i] ?? null;
            $shortcut_icon_url = $settings['shortcut_icon_url_' . $i] ?? null;

            if($shortcut_name && $shortcut_url && $shortcut_icon_url) {
                $shortcuts[] = [
                    'name' => $shortcut_name,
                    'description' => $shortcut_description,
                    'url' => $shortcut_url,
                    'icons' => [[
                        'src' => $shortcut_icon_url,
                        'type' => get_image_type_from_url($shortcut_icon_url)
                    ]]
                ];
            }
        }
        if(count($shortcuts) > 0) {
            $manifest_json['shortcuts'] = $shortcuts;
        }

        /* Output manifest as downloadable file */
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename=manifest.json');
        echo json_encode($manifest_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        die();

    }

}
