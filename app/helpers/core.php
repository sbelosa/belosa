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

function fc_get_user_main_biolink_id(int $user_id, bool $repair_mapping = true): int {
    if($user_id <= 0) {
        return 0;
    }

    if(method_exists('\Altum\Link', 'get_user_main_biolink_id')) {
        return (int) \Altum\Link::get_user_main_biolink_id($user_id, $repair_mapping);
    }

    $mapping_rows = db()
        ->where('user_id', $user_id)
        ->orderBy('id', 'DESC')
        ->get('users_biolinks', null, ['id', 'biolink_id']);

    $latest_mapping_id = (int) ($mapping_rows[0]->id ?? 0);
    $latest_mapped_biolink_id = (int) ($mapping_rows[0]->biolink_id ?? 0);
    $resolved_biolink_id = 0;

    foreach((array) $mapping_rows as $mapping_row) {
        $candidate_biolink_id = (int) ($mapping_row->biolink_id ?? 0);

        if($candidate_biolink_id <= 0) {
            continue;
        }

        $valid_biolink_id = (int) (db()
            ->where('link_id', $candidate_biolink_id)
            ->where('user_id', $user_id)
            ->where('type', 'biolink')
            ->getValue('links', 'link_id') ?? 0);

        if($valid_biolink_id > 0) {
            $resolved_biolink_id = $valid_biolink_id;
            break;
        }
    }

    if(!$resolved_biolink_id) {
        $fallback_biolink = db()
            ->where('user_id', $user_id)
            ->where('type', 'biolink')
            ->orderBy('is_enabled', 'DESC')
            ->orderBy('datetime', 'ASC')
            ->orderBy('link_id', 'ASC')
            ->getOne('links', ['link_id']);

        $resolved_biolink_id = (int) ($fallback_biolink->link_id ?? 0);
    }

    if($repair_mapping && $resolved_biolink_id > 0) {
        if($latest_mapping_id > 0) {
            if($latest_mapped_biolink_id !== $resolved_biolink_id) {
                db()->where('id', $latest_mapping_id)->update('users_biolinks', [
                    'biolink_id' => $resolved_biolink_id,
                ]);
            }
        } else {
            db()->insert('users_biolinks', [
                'user_id' => $user_id,
                'biolink_id' => $resolved_biolink_id,
            ]);
        }
    }

    return $resolved_biolink_id;
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

/* Custom code: FC-2026-03-24: force Croatian translations for outbound emails */
function fc_resolve_email_language_name($language = null) {
    $language = fc_resolve_language_name($language);

    if($language === null || $language === '') {
        return 'Hrvatski';
    }

    $normalized_language = mb_strtolower(trim((string) $language));

    if(in_array($normalized_language, ['english', 'en', 'croatian', 'hrvatski', 'hr'], true)) {
        return 'Hrvatski';
    }

    return $language;
}
/* /Custom code: FC-2026-03-24 */

/* Custom code: FC-2026-03-30: localized forever products category helpers */
function fc_translate_blog_category_fields($blog_posts_category, $target_language, $model, $api_key) {
    $fields = [
        'title' => (string) ($blog_posts_category->title ?? ''),
        'description' => (string) ($blog_posts_category->description ?? ''),
    ];

    $source_language = !empty($blog_posts_category->language) ? $blog_posts_category->language : 'Croatian';

    $response = \Unirest\Request::post(
        'https://api.openai.com/v1/chat/completions',
        [
            'Authorization' => 'Bearer ' . get_random_line_from_text($api_key),
            'Content-Type' => 'application/json',
        ],
        \Unirest\Request\Body::json([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional website translator. Return JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => implode("\n\n", [
                        'Translate the provided blog category fields from ' . $source_language . ' to ' . $target_language . '.',
                        'Return only a valid JSON object with these exact keys: title, description.',
                        'Keep category meaning and SEO intent natural in English.',
                        'Do not add explanations, markdown, or extra keys.',
                        'Input JSON:' . json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ])
                ],
            ],
        ])
    );

    if($response->code >= 400) {
        throw new \Exception($response->body->error->message ?? 'OpenAI request failed.');
    }

    $content = trim((string) ($response->body->choices[0]->message->content ?? ''));

    if(substr($content, 0, 3) === '```') {
        $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content);
    }

    $translated_fields = json_decode($content, true);

    if(!is_array($translated_fields)) {
        throw new \Exception('OpenAI did not return valid JSON for the category translation.');
    }

    return [
        'title' => input_clean(trim((string) ($translated_fields['title'] ?? '')), 256),
        'description' => input_clean(trim((string) ($translated_fields['description'] ?? '')), 256),
    ];
}

function fc_get_or_create_blog_category_translation($source_category, $target_language, $api_key = null, $model = 'gpt-4o') {
    if(!$source_category) {
        return null;
    }

    if(empty($source_category->language) || $source_category->language === $target_language) {
        return $source_category;
    }

    $target_category = db()->where('url', $source_category->url)->where('language', $target_language)->getOne('blog_posts_categories');

    if($target_category) {
        return $target_category;
    }

    if(!$api_key) {
        return null;
    }

    $target_parent_category = null;
    if(!empty($source_category->blog_posts_parent_id)) {
        $source_parent_category = db()->where('blog_posts_category_id', $source_category->blog_posts_parent_id)->getOne('blog_posts_categories');
        $target_parent_category = fc_get_or_create_blog_category_translation($source_parent_category, $target_language, $api_key, $model);
    }

    $translated_fields = fc_translate_blog_category_fields($source_category, $target_language, $model, $api_key);

    $blog_posts_category_data = [
        'blog_posts_parent_id' => $target_parent_category->blog_posts_category_id ?? null,
        'url' => $source_category->url,
        'title' => $translated_fields['title'] ?: $source_category->title,
        'description' => $translated_fields['description'] ?: $source_category->description,
        'language' => $target_language,
        'order' => (int) ($source_category->order ?? 0),
        'datetime' => get_date(),
        'last_datetime' => get_date(),
        'visibility' => $source_category->visibility ?? 'public',
        'show_share_links' => $source_category->show_share_links ?? 0,
    ];

    static $blog_posts_categories_has_style_column = null;
    if($blog_posts_categories_has_style_column === null) {
        $blog_posts_categories_has_style_column = (bool) count(db()->rawQuery("SHOW COLUMNS FROM `blog_posts_categories` LIKE 'style'"));
    }

    if($blog_posts_categories_has_style_column) {
        $blog_posts_category_data['style'] = $source_category->style ?? null;
    }

    db()->insert('blog_posts_categories', $blog_posts_category_data);
    $blog_posts_category_id = db()->getInsertId();

    cache()->deleteItemsByTag('blog_posts_categories');

    return db()->where('blog_posts_category_id', $blog_posts_category_id)->getOne('blog_posts_categories');
}

function fc_get_forever_products_blog_category_url($language = null) {
    $language = fc_resolve_language_name($language ?? \Altum\Language::$name);

    static $cached_urls = [];

    if(array_key_exists($language, $cached_urls)) {
        return $cached_urls[$language];
    }

    $candidate_urls = ['forever-products', 'forever-proizvodi'];

    foreach($candidate_urls as $candidate_url) {
        $blog_posts_category = db()
            ->where('blog_posts_parent_id', null, 'IS')
            ->where('url', $candidate_url)
            ->where('language', $language)
            ->getOne('blog_posts_categories', ['url']);

        if($blog_posts_category) {
            return $cached_urls[$language] = url('blog/category/' . $blog_posts_category->url);
        }
    }

    foreach($candidate_urls as $candidate_url) {
        $blog_posts_category = db()
            ->where('blog_posts_parent_id', null, 'IS')
            ->where('url', $candidate_url)
            ->getOne('blog_posts_categories', ['url']);

        if($blog_posts_category) {
            return $cached_urls[$language] = SITE_URL . ((\Altum\Language::$active_languages[$language] ?? null) ? \Altum\Language::$active_languages[$language] . '/' : null) . 'blog/category/' . $blog_posts_category->url;
        }
    }

    return $cached_urls[$language] = url('blog');
}
/* /Custom code: FC-2026-03-30 */

/* Custom code: FC-2026-03-30: localized pages category helpers */
function fc_translate_pages_category_fields($pages_category, $target_language, $model, $api_key) {
    $fields = [
        'title' => (string) ($pages_category->title ?? ''),
        'description' => (string) ($pages_category->description ?? ''),
    ];

    $source_language = !empty($pages_category->language) ? $pages_category->language : 'Croatian';

    $response = \Unirest\Request::post(
        'https://api.openai.com/v1/chat/completions',
        [
            'Authorization' => 'Bearer ' . get_random_line_from_text($api_key),
            'Content-Type' => 'application/json',
        ],
        \Unirest\Request\Body::json([
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional website translator. Return JSON only.'
                ],
                [
                    'role' => 'user',
                    'content' => implode("\n\n", [
                        'Translate the provided page category fields from ' . $source_language . ' to ' . $target_language . '.',
                        'Return only a valid JSON object with these exact keys: title, description.',
                        'Keep the category meaning and navigation intent natural in English.',
                        'Do not add explanations, markdown, or extra keys.',
                        'Input JSON:' . json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ])
                ],
            ],
        ])
    );

    if($response->code >= 400) {
        throw new \Exception($response->body->error->message ?? 'OpenAI request failed.');
    }

    $content = trim((string) ($response->body->choices[0]->message->content ?? ''));

    if(substr($content, 0, 3) === '```') {
        $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);
        $content = trim($content);
    }

    $translated_fields = json_decode($content, true);

    if(!is_array($translated_fields)) {
        throw new \Exception('OpenAI did not return valid JSON for the pages category translation.');
    }

    return [
        'title' => input_clean(trim((string) ($translated_fields['title'] ?? '')), 256),
        'description' => input_clean(trim((string) ($translated_fields['description'] ?? '')), 256),
    ];
}

function fc_get_or_create_pages_category_translation($source_category, $target_language, $api_key = null, $model = 'gpt-4o') {
    if(!$source_category) {
        return null;
    }

    if(empty($source_category->language) || $source_category->language === $target_language) {
        return $source_category;
    }

    $target_category = db()->where('url', $source_category->url)->where('language', $target_language)->getOne('pages_categories');

    if($target_category) {
        return $target_category;
    }

    if(!$api_key) {
        return null;
    }

    $translated_fields = fc_translate_pages_category_fields($source_category, $target_language, $model, $api_key);

    db()->insert('pages_categories', [
        'url' => $source_category->url,
        'title' => $translated_fields['title'] ?: $source_category->title,
        'description' => $translated_fields['description'] ?: $source_category->description,
        'icon' => $source_category->icon,
        'order' => (int) ($source_category->order ?? 0),
        'language' => $target_language,
        'datetime' => get_date(),
        'last_datetime' => get_date(),
    ]);

    $pages_category_id = db()->getInsertId();

    cache()->deleteItemsByTag('pages_categories');

    return db()->where('pages_category_id', $pages_category_id)->getOne('pages_categories');
}
/* /Custom code: FC-2026-03-30 */

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

    /* Custom code: FC-2026-03-24: force Croatian translations for outbound emails */
    if(str_starts_with((string) $key, 'global.emails.')) {
        $language = fc_resolve_email_language_name($language);
    }
    /* /Custom code: FC-2026-03-24 */

    $current_language = \Altum\Language::get($language);
    if(isset($current_language[$key])) {
        return $current_language[$key];
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

    $main_language = \Altum\Language::get(\Altum\Language::$main_name);
    if(isset($main_language[$key])) {
        return $main_language[$key];
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
