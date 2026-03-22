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

defined('ALTUMCODE') || die();

function settings() {
    if(!\Altum\Settings::$settings) {
        \Altum\Settings::initialize();
    }

    return \Altum\Settings::$settings;
}

function get_settings_custom_head_js($key = 'head_js') {
    $head_js = settings()->custom->{$key};

    /* Dynamic variables processing */
    $replacers = [
        '{{WEBSITE_TITLE}}' => settings()->main->title,
        '{{USER:NAME}}' => is_logged_in() ? \Altum\Authentication::$user->name : '',
        '{{USER:EMAIL}}' => is_logged_in() ? \Altum\Authentication::$user->email : '',
        '{{USER:CONTINENT_NAME}}' => is_logged_in() ? get_continent_from_continent_code(\Altum\Authentication::$user->continent_code) : '',
        '{{USER:COUNTRY_NAME}}' => is_logged_in() ? get_country_from_country_code(\Altum\Authentication::$user->country) : '',
        '{{USER:CITY_NAME}}' => is_logged_in() ? \Altum\Authentication::$user->city_name : '',
        '{{USER:DEVICE_TYPE}}' => is_logged_in() ? l('global.device.' . \Altum\Authentication::$user->device_type) : '',
        '{{USER:OS_NAME}}' => is_logged_in() ? \Altum\Authentication::$user->os_name : '',
        '{{USER:BROWSER_NAME}}' => is_logged_in() ? \Altum\Authentication::$user->browser_name : '',
        '{{USER:BROWSER_LANGUAGE}}' => is_logged_in() ? get_language_from_locale(\Altum\Authentication::$user->browser_language) : '',
        '{{USER:USER_ID}}' => json_encode(is_logged_in() ? \Altum\Authentication::$user->user_id : ''),
        '{{USER:PLAN_ID}}' => json_encode(is_logged_in() ? \Altum\Authentication::$user->plan_id : ''),
    ];

    $head_js = str_replace(
        array_keys($replacers),
        array_values($replacers),
        $head_js
    );

    return $head_js;
}

function db() {
    if(!\Altum\Database::$db) {
        \Altum\Database::initialize();
    }

    return \Altum\Database::$db;
}

function database() {
    if(!\Altum\Database::$database) {
        \Altum\Database::initialize();
    }

    return \Altum\Database::$database;
}

/* Custom code: FC-2026-03-22: normalize legacy language aliases */
function fc_resolve_language_name($language = null) {
    if(!$language) {
        return $language;
    }

    static $language_aliases = [
        'croatian' => 'Hrvatski',
        'hrvatski' => 'Hrvatski',
        'english' => 'english',
    ];

    $normalized_language = trim((string) $language);
    $normalized_language_key = mb_strtolower($normalized_language);

    return $language_aliases[$normalized_language_key] ?? $normalized_language;
}
/* /Custom code: FC-2026-03-22 */

function language($language = null) {
    /* Custom code: FC-2026-03-22: normalize legacy language aliases */
    return \Altum\Language::get(fc_resolve_language_name($language));
    /* /Custom code: FC-2026-03-22 */
}

function l($key, $language = null, $null_coalesce = false) {
    /* Custom code: FC-2026-02-24: language fallback */
    /* Custom code: FC-2026-03-22: normalize legacy language aliases */
    $language = fc_resolve_language_name($language);
    /* /Custom code: FC-2026-03-22 */

    $current_language = \Altum\Language::get($language);
    if(isset($current_language[$key])) {
        return $current_language[$key];
    }

    $main_language = \Altum\Language::get(\Altum\Language::$main_name);
    if(isset($main_language[$key])) {
        return $main_language[$key];
    }

    /* Fallback to original language files if cache is stale. */
    $language_name = $language ?? \Altum\Language::$name ?? \Altum\Language::$default_name ?? \Altum\Language::$main_name;
    $language_candidates = array_unique(array_filter([$language_name, \Altum\Language::$main_name]));

    foreach($language_candidates as $candidate_name) {
        if(!isset(\Altum\Language::$languages[$candidate_name])) {
            continue;
        }

        $candidate_code = \Altum\Language::$languages[$candidate_name]['code'] ?? null;
        if(!$candidate_code) {
            continue;
        }

        $candidate_path = APP_PATH . 'languages/' . $candidate_name . '#' . $candidate_code . '.php';
        if(!file_exists($candidate_path)) {
            continue;
        }

        $candidate_language = require $candidate_path;
        if(isset($candidate_language[$key])) {
            return $candidate_language[$key];
        }
    }
    /* /Custom code: FC-2026-02-24 */

    return $null_coalesce ? null : $key;
}

function currency() {
    if(!\Altum\Currency::$currency) {
        \Altum\Currency::initialize();
    }

    return \Altum\Currency::$currency;
}

function cache($adapter = 'adapter') {
    return \Altum\Cache::${$adapter};
}

function get_date($format = 'Y-m-d H:i:s') {
    return date($format);
}

function is_logged_in() {
    return \Altum\Authentication::check();
}

function user() {
    return \Altum\Authentication::$user;
}

function throw_404() {
    throw new \Altum\NotFoundException();
}