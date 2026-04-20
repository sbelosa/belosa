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

function fc_get_resolved_openai_model($configured_model = null): string {
    $model = trim((string) ($configured_model ?? (settings()->main->openai_model ?? '')));

    if($model === '') {
        return 'gpt-5.4';
    }

    return match($model) {
        'gpt-5', 'gpt-5.1', 'gpt-5.2', 'gpt-5.2-chat-latest' => 'gpt-5.4',
        default => $model,
    };
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

function fc_blog_posts_has_shop_context_column(): bool {
    static $has_shop_context_column = null;

    if($has_shop_context_column === null) {
        $has_shop_context_column = (bool) count(db()->rawQuery("SHOW COLUMNS FROM `blog_posts` LIKE 'shop_context'"));
    }

    return $has_shop_context_column;
}

function fc_blog_shop_context_parse_list_text($value, int $limit = 8, int $item_limit = 220): array {
    $value = is_string($value) ? $value : '';
    $lines = preg_split('/\r\n|\r|\n/', $value ?: '') ?: [];
    $items = [];

    foreach($lines as $line) {
        $line = preg_replace('/^[\-\*\x{2022}\x{25CF}\x{25AA}\x{25E6}\s]+/u', '', trim((string) $line));
        $line = trim(input_clean((string) $line, $item_limit));

        if($line === '') {
            continue;
        }

        $items[] = $line;

        if(count($items) >= $limit) {
            break;
        }
    }

    return array_values(array_unique($items));
}

function fc_blog_shop_context_parse_pairs_text($value, string $left_key = 'label', string $right_key = 'value', int $limit = 8, int $left_limit = 120, int $right_limit = 220): array {
    $value = is_string($value) ? $value : '';
    $lines = preg_split('/\r\n|\r|\n/', $value ?: '') ?: [];
    $pairs = [];

    foreach($lines as $line) {
        $line = trim((string) $line);

        if($line === '' || !str_contains($line, '|')) {
            continue;
        }

        [$left, $right] = array_pad(explode('|', $line, 2), 2, '');

        $left = trim(input_clean($left, $left_limit));
        $right = trim(input_clean($right, $right_limit));

        if($left === '' || $right === '') {
            continue;
        }

        $pairs[] = [
            $left_key => $left,
            $right_key => $right,
        ];

        if(count($pairs) >= $limit) {
            break;
        }
    }

    return $pairs;
}

function fc_blog_shop_context_normalize($context): array {
    if(is_string($context)) {
        $decoded_context = json_decode($context, true);
        $context = is_array($decoded_context) ? $decoded_context : [];
    } elseif(is_object($context)) {
        $context = (array) $context;
    }

    if(!is_array($context)) {
        return [];
    }

    $normalized = [];
    $page_role = trim(input_clean((string) ($context['page_role'] ?? ''), 32));

    if(in_array($page_role, ['product', 'business_start'], true)) {
        $normalized['page_role'] = $page_role;
    }

    foreach([
        'trust_note' => 700,
        'decision_title' => 160,
        'checks_title' => 160,
        'action_title' => 160,
        'action_subtitle' => 700,
        'primary_cta_label' => 120,
        'secondary_cta_label' => 120,
        'related_eyebrow' => 80,
        'related_title' => 180,
        'meta_title' => 180,
        'meta_description' => 320,
        'meta_keywords' => 255,
    ] as $field => $max_length) {
        $value = trim(input_clean((string) ($context[$field] ?? ''), $max_length));

        if($value !== '') {
            $normalized[$field] = $value;
        }
    }

    $summary_cards = [];
    foreach((array) ($context['summary_cards'] ?? []) as $summary_card) {
        if(is_object($summary_card)) {
            $summary_card = (array) $summary_card;
        }

        $label = trim(input_clean((string) ($summary_card['label'] ?? ''), 120));
        $value = trim(input_clean((string) ($summary_card['value'] ?? ''), 220));

        if($label === '' || $value === '') {
            continue;
        }

        $summary_cards[] = [
            'label' => $label,
            'value' => $value,
        ];

        if(count($summary_cards) >= 6) {
            break;
        }
    }

    if($summary_cards) {
        $normalized['summary_cards'] = $summary_cards;
    }

    $ideal_for = [];
    foreach((array) ($context['ideal_for'] ?? []) as $item) {
        $item = trim(input_clean((string) $item, 240));

        if($item === '') {
            continue;
        }

        $ideal_for[] = $item;

        if(count($ideal_for) >= 6) {
            break;
        }
    }

    if($ideal_for) {
        $normalized['ideal_for'] = array_values(array_unique($ideal_for));
    }

    $quick_checks = [];
    foreach((array) ($context['quick_checks'] ?? []) as $item) {
        $item = trim(input_clean((string) $item, 240));

        if($item === '') {
            continue;
        }

        $quick_checks[] = $item;

        if(count($quick_checks) >= 8) {
            break;
        }
    }

    if($quick_checks) {
        $normalized['quick_checks'] = array_values(array_unique($quick_checks));
    }

    $faq_items = [];
    foreach((array) ($context['faq'] ?? []) as $faq_item) {
        if(is_object($faq_item)) {
            $faq_item = (array) $faq_item;
        }

        $question = trim(input_clean((string) ($faq_item['question'] ?? ''), 180));
        $answer = trim(input_clean((string) ($faq_item['answer'] ?? ''), 700));

        if($question === '' || $answer === '') {
            continue;
        }

        $faq_items[] = [
            'question' => $question,
            'answer' => $answer,
        ];

        if(count($faq_items) >= 10) {
            break;
        }
    }

    if($faq_items) {
        $normalized['faq'] = $faq_items;
    }

    return $normalized;
}

function fc_blog_shop_context_encode($context): ?string {
    $normalized = fc_blog_shop_context_normalize($context);

    return $normalized ? json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
}

function fc_blog_shop_context_to_form_data($context): array {
    $context = fc_blog_shop_context_normalize($context);

    return [
        'page_role' => $context['page_role'] ?? '',
        'trust_note' => $context['trust_note'] ?? '',
        'decision_title' => $context['decision_title'] ?? '',
        'checks_title' => $context['checks_title'] ?? '',
        'action_title' => $context['action_title'] ?? '',
        'action_subtitle' => $context['action_subtitle'] ?? '',
        'primary_cta_label' => $context['primary_cta_label'] ?? '',
        'secondary_cta_label' => $context['secondary_cta_label'] ?? '',
        'related_eyebrow' => $context['related_eyebrow'] ?? '',
        'related_title' => $context['related_title'] ?? '',
        'meta_title' => $context['meta_title'] ?? '',
        'meta_description' => $context['meta_description'] ?? '',
        'meta_keywords' => $context['meta_keywords'] ?? '',
        'summary_cards' => implode(PHP_EOL, array_map(static function($item) {
            return ($item['label'] ?? '') . ' | ' . ($item['value'] ?? '');
        }, $context['summary_cards'] ?? [])),
        'ideal_for' => implode(PHP_EOL, $context['ideal_for'] ?? []),
        'quick_checks' => implode(PHP_EOL, $context['quick_checks'] ?? []),
        'faq' => implode(PHP_EOL, array_map(static function($item) {
            return ($item['question'] ?? '') . ' | ' . ($item['answer'] ?? '');
        }, $context['faq'] ?? [])),
    ];
}

function fc_blog_posts_categories_has_shop_context_column(): bool {
    static $has_shop_context_column = null;

    if($has_shop_context_column === null) {
        $has_shop_context_column = (bool) count(db()->rawQuery("SHOW COLUMNS FROM `blog_posts_categories` LIKE 'shop_context'"));
    }

    return $has_shop_context_column;
}

function fc_blog_category_shop_context_normalize($context): array {
    if(is_string($context)) {
        $decoded_context = json_decode($context, true);
        $context = is_array($decoded_context) ? $decoded_context : [];
    } elseif(is_object($context)) {
        $context = (array) $context;
    }

    if(!is_array($context)) {
        return [];
    }

    $normalized = [];
    $page_role = trim(input_clean((string) ($context['page_role'] ?? ''), 32));

    if(in_array($page_role, ['shop_hub'], true)) {
        $normalized['page_role'] = $page_role;
    }

    foreach([
        'hero_badge' => 80,
        'hero_subtitle' => 700,
        'hero_note' => 700,
        'meta_title' => 180,
        'meta_description' => 320,
        'meta_keywords' => 255,
        'subcategories_title' => 120,
        'guide_title' => 160,
        'featured_title' => 160,
        'discovery_eyebrow' => 80,
        'discovery_title' => 180,
        'discovery_subtitle' => 700,
        'seo_title' => 180,
        'faq_title' => 160,
        'product_count_label' => 120,
        'shop_ready_count_label' => 120,
        'market_count_label' => 120,
    ] as $field => $max_length) {
        $value = trim(input_clean((string) ($context[$field] ?? ''), $max_length));

        if($value !== '') {
            $normalized[$field] = $value;
        }
    }

    $guide_items = [];
    foreach((array) ($context['guide_items'] ?? []) as $guide_item) {
        if(is_object($guide_item)) {
            $guide_item = (array) $guide_item;
        }

        $title = trim(input_clean((string) ($guide_item['title'] ?? ''), 160));
        $text = trim(input_clean((string) ($guide_item['text'] ?? ''), 420));

        if($title === '' || $text === '') {
            continue;
        }

        $guide_items[] = [
            'title' => $title,
            'text' => $text,
        ];

        if(count($guide_items) >= 6) {
            break;
        }
    }

    if($guide_items) {
        $normalized['guide_items'] = $guide_items;
    }

    $seo_paragraphs = [];
    foreach((array) ($context['seo_paragraphs'] ?? []) as $item) {
        $item = trim(input_clean((string) $item, 700));

        if($item === '') {
            continue;
        }

        $seo_paragraphs[] = $item;

        if(count($seo_paragraphs) >= 6) {
            break;
        }
    }

    if($seo_paragraphs) {
        $normalized['seo_paragraphs'] = array_values(array_unique($seo_paragraphs));
    }

    $faq_items = [];
    foreach((array) ($context['faq_items'] ?? []) as $faq_item) {
        if(is_object($faq_item)) {
            $faq_item = (array) $faq_item;
        }

        $question = trim(input_clean((string) ($faq_item['q'] ?? $faq_item['question'] ?? ''), 180));
        $answer = trim(input_clean((string) ($faq_item['a'] ?? $faq_item['answer'] ?? ''), 700));

        if($question === '' || $answer === '') {
            continue;
        }

        $faq_items[] = [
            'q' => $question,
            'a' => $answer,
        ];

        if(count($faq_items) >= 10) {
            break;
        }
    }

    if($faq_items) {
        $normalized['faq_items'] = $faq_items;
    }

    $featured_post_urls = [];
    foreach((array) ($context['featured_post_urls'] ?? []) as $item) {
        $item = input_clean(get_slug((string) $item), 256);

        if($item === '') {
            continue;
        }

        $featured_post_urls[] = $item;

        if(count($featured_post_urls) >= 8) {
            break;
        }
    }

    if($featured_post_urls) {
        $normalized['featured_post_urls'] = array_values(array_unique($featured_post_urls));
    }

    $filter_chips = [];
    foreach((array) ($context['filter_chips'] ?? []) as $filter_chip) {
        if(is_object($filter_chip)) {
            $filter_chip = (array) $filter_chip;
        }

        $label = trim(input_clean((string) ($filter_chip['label'] ?? ''), 80));
        $terms = trim(input_clean((string) ($filter_chip['terms'] ?? ''), 220));

        if($label === '' || $terms === '') {
            continue;
        }

        $filter_chips[] = [
            'label' => $label,
            'terms' => $terms,
        ];

        if(count($filter_chips) >= 5) {
            break;
        }
    }

    if($filter_chips) {
        $normalized['filter_chips'] = $filter_chips;
    }

    return $normalized;
}

function fc_blog_category_shop_context_encode($context): ?string {
    $normalized = fc_blog_category_shop_context_normalize($context);

    return $normalized ? json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
}

function fc_blog_category_shop_context_to_form_data($context): array {
    $context = fc_blog_category_shop_context_normalize($context);

    return [
        'page_role' => $context['page_role'] ?? '',
        'hero_badge' => $context['hero_badge'] ?? '',
        'hero_subtitle' => $context['hero_subtitle'] ?? '',
        'hero_note' => $context['hero_note'] ?? '',
        'meta_title' => $context['meta_title'] ?? '',
        'meta_description' => $context['meta_description'] ?? '',
        'meta_keywords' => $context['meta_keywords'] ?? '',
        'subcategories_title' => $context['subcategories_title'] ?? '',
        'guide_title' => $context['guide_title'] ?? '',
        'featured_title' => $context['featured_title'] ?? '',
        'discovery_eyebrow' => $context['discovery_eyebrow'] ?? '',
        'discovery_title' => $context['discovery_title'] ?? '',
        'discovery_subtitle' => $context['discovery_subtitle'] ?? '',
        'seo_title' => $context['seo_title'] ?? '',
        'faq_title' => $context['faq_title'] ?? '',
        'product_count_label' => $context['product_count_label'] ?? '',
        'shop_ready_count_label' => $context['shop_ready_count_label'] ?? '',
        'market_count_label' => $context['market_count_label'] ?? '',
        'guide_items' => implode(PHP_EOL, array_map(static function($item) {
            return ($item['title'] ?? '') . ' | ' . ($item['text'] ?? '');
        }, $context['guide_items'] ?? [])),
        'seo_paragraphs' => implode(PHP_EOL, $context['seo_paragraphs'] ?? []),
        'faq_items' => implode(PHP_EOL, array_map(static function($item) {
            return ($item['q'] ?? '') . ' | ' . ($item['a'] ?? '');
        }, $context['faq_items'] ?? [])),
        'featured_post_urls' => implode(PHP_EOL, $context['featured_post_urls'] ?? []),
        'filter_chips' => implode(PHP_EOL, array_map(static function($item) {
            return ($item['label'] ?? '') . ' | ' . ($item['terms'] ?? '');
        }, $context['filter_chips'] ?? [])),
    ];
}

function fc_blog_category_shop_context_completion($context): array {
    $context = fc_blog_category_shop_context_normalize($context);

    $checks = [
        ['key' => 'meta_title', 'label' => 'Meta naslov', 'filled' => !empty($context['meta_title'])],
        ['key' => 'meta_description', 'label' => 'Meta opis', 'filled' => !empty($context['meta_description'])],
        ['key' => 'hero_subtitle', 'label' => 'Hero podnaslov', 'filled' => !empty($context['hero_subtitle'])],
        ['key' => 'featured_post_urls', 'label' => 'Istaknuti proizvodi', 'filled' => !empty($context['featured_post_urls'])],
        ['key' => 'filter_chips', 'label' => 'Filter chipovi', 'filled' => !empty($context['filter_chips'])],
        ['key' => 'seo_paragraphs', 'label' => 'SEO odlomci', 'filled' => !empty($context['seo_paragraphs'])],
        ['key' => 'faq_items', 'label' => 'FAQ pitanja', 'filled' => !empty($context['faq_items'])],
    ];

    $filled_count = count(array_filter($checks, static function($item) {
        return !empty($item['filled']);
    }));

    return [
        'filled' => $filled_count,
        'total' => count($checks),
        'is_complete' => $filled_count === count($checks),
        'checks' => $checks,
    ];
}

function fc_blog_resolve_language_code($language = null): string {
    if(!$language) {
        return \Altum\Language::$code ?? 'en';
    }

    $normalized_language = mb_strtolower(trim((string) $language));

    if(in_array($normalized_language, ['hr', 'en'], true)) {
        return $normalized_language;
    }

    $resolved_language_name = fc_resolve_language_name($language);

    if($resolved_language_name && isset(\Altum\Language::$active_languages[$resolved_language_name])) {
        return \Altum\Language::$active_languages[$resolved_language_name];
    }

    return match($normalized_language) {
        'croatian', 'hrvatski' => 'hr',
        default => 'en',
    };
}

function fc_blog_plaintext_excerpt($value, int $limit = 320): string {
    $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value ?? '');
    $value = trim((string) $value);

    if($value === '') {
        return '';
    }

    return trim(input_clean($value, $limit));
}

function fc_blog_meta_truncate($value, int $limit): string {
    $value = trim((string) $value);

    if($value === '') {
        return '';
    }

    return string_truncate($value, $limit, '');
}

function fc_blog_extract_title_stem(string $title, int $limit = 120): string {
    $title = fc_blog_plaintext_excerpt($title, 256);

    if($title === '') {
        return '';
    }

    foreach([' – ', ' — ', ' - ', ': '] as $separator) {
        if(!str_contains($title, $separator)) {
            continue;
        }

        [$candidate] = explode($separator, $title, 2);
        $candidate = trim($candidate);

        if($candidate !== '' && mb_strlen($candidate) >= 8) {
            return trim(input_clean($candidate, $limit));
        }
    }

    return trim(input_clean($title, $limit));
}

function fc_blog_build_keywords_string(array $candidates, int $limit = 255): string {
    $keywords = [];
    $seen = [];
    $current_length = 0;

    $append_keyword = static function($keyword) use (&$keywords, &$seen, &$current_length, $limit): void {
        $keyword = trim(input_clean((string) $keyword, 120));

        if($keyword === '') {
            return;
        }

        $normalized_keyword = mb_strtolower($keyword);

        if(isset($seen[$normalized_keyword])) {
            return;
        }

        $candidate_length = ($keywords ? 2 : 0) + mb_strlen($keyword);

        if(($current_length + $candidate_length) > $limit) {
            return;
        }

        $keywords[] = $keyword;
        $seen[$normalized_keyword] = true;
        $current_length += $candidate_length;
    };

    foreach($candidates as $candidate) {
        if(is_array($candidate)) {
            foreach($candidate as $nested_candidate) {
                foreach(preg_split('/[\r\n,;]+/u', (string) $nested_candidate) ?: [] as $keyword_part) {
                    $append_keyword($keyword_part);
                }
            }

            continue;
        }

        foreach(preg_split('/[\r\n,;]+/u', (string) $candidate) ?: [] as $keyword_part) {
            $append_keyword($keyword_part);
        }
    }

    return implode(', ', $keywords);
}

function fc_blog_get_category_slug_key($category): string {
    if(is_array($category)) {
        $category = (object) $category;
    }

    if(!is_object($category)) {
        return '';
    }

    $url = trim((string) ($category->url ?? ''));

    if($url !== '') {
        $segments = array_values(array_filter(explode('/', trim($url, '/'))));

        if($segments) {
            return (string) end($segments);
        }
    }

    $title = trim((string) ($category->title ?? ''));

    return $title !== '' ? get_slug($title) : '';
}

function fc_blog_get_category_intent_bundle($category, ?string $language_code = null): array {
    if(is_array($category)) {
        $category = (object) $category;
    }

    $category_language = is_object($category) ? ($category->language ?? null) : null;
    $category_title = is_object($category) ? trim((string) ($category->title ?? '')) : '';
    $language_code = fc_blog_resolve_language_code($language_code ?? $category_language);
    $slug_key = fc_blog_get_category_slug_key($category);

    $intent_map = [
        'hr' => [
            'forever-proizvodi' => [
                'category_phrase' => 'Forever Living Products proizvodi',
                'default_meta_title' => 'Forever Living Products | Proizvodi, kategorije i vodiči',
                'default_meta_description' => 'Pregledajte Forever Living Products proizvode po kategorijama, usporedite proizvode i otvorite vodič koji najviše odgovara vašem cilju, rutini i načinu korištenja.',
                'product_title_suffix' => 'Forever Living Products',
                'product_description_suffix' => 'Saznajte čemu služi, za koga je i kako odabrati pravi Forever proizvod.',
                'keywords' => ['Forever Living Products', 'Forever proizvodi', 'Forever Living Products proizvodi', 'Forever vodiči za proizvode'],
            ],
            'napitci' => [
                'category_phrase' => 'Forever Living Products napitci',
                'default_meta_title' => 'Forever Living Products napitci | Aloe vera napitci',
                'default_meta_description' => 'Pregledajte Forever Living Products napitke, usporedite aloe vera napitke i otvorite vodič za proizvod koji najbolje odgovara probavi, energiji ili svakodnevnoj rutini.',
                'product_title_suffix' => 'Forever Living Products napitci',
                'product_description_suffix' => 'Saznajte što ga izdvaja, kome odgovara i kako se uklapa u svakodnevnu rutinu napitaka.',
                'keywords' => ['Forever Living Products napitci', 'Forever napitci', 'aloe vera napitci', 'Forever Aloe Vera Gel', 'Forever Freedom'],
            ],
            'regulacija-tezine' => [
                'category_phrase' => 'Forever Living Products regulacija težine',
                'default_meta_title' => 'Forever Living Products regulacija težine | Programi i proizvodi',
                'default_meta_description' => 'Pregledajte Forever Living Products proizvode za regulaciju težine, programe i dodatke koji pomažu pri kontroli apetita, rutini i aktivnom načinu života.',
                'product_title_suffix' => 'Forever Living Products regulacija težine',
                'product_description_suffix' => 'Saznajte kada se proizvod najčešće razmatra, komu može odgovarati i kako se uklapa u plan regulacije težine.',
                'keywords' => ['Forever Living Products regulacija težine', 'Forever regulacija težine', 'C9 Forever', 'Forever F15', 'Forever DX4'],
            ],
            'dodaci-prehrani' => [
                'category_phrase' => 'Forever Living Products dodaci prehrani',
                'default_meta_title' => 'Forever Living Products dodaci prehrani | Vitamini i formule',
                'default_meta_description' => 'Pregledajte Forever Living Products dodatke prehrani, usporedite vitamine, minerale, probiotike i druge formule te otvorite vodič za proizvod koji najviše odgovara vašem cilju.',
                'product_title_suffix' => 'Forever Living Products dodaci prehrani',
                'product_description_suffix' => 'Saznajte što proizvod sadrži, za koga je najzanimljiviji i kako ga lakše usporediti s drugim formulama.',
                'keywords' => ['Forever Living Products dodaci prehrani', 'Forever dodaci prehrani', 'Forever vitamini', 'Forever probiotici', 'Forever suplementi'],
            ],
            'pcelinji-proizvodi' => [
                'category_phrase' => 'Forever Living Products pčelinji proizvodi',
                'default_meta_title' => 'Forever Living Products pčelinji proizvodi | Propolis, pelud i med',
                'default_meta_description' => 'Pregledajte Forever Living Products pčelinje proizvode, uključujući propolis, pelud, med i matičnu mliječ, te otvorite vodič za proizvod koji najbolje odgovara vašoj rutini.',
                'product_title_suffix' => 'Forever Living Products pčelinji proizvodi',
                'product_description_suffix' => 'Saznajte što proizvod sadrži, kako se razlikuje od drugih pčelinjih proizvoda i kome bi mogao odgovarati.',
                'keywords' => ['Forever Living Products pčelinji proizvodi', 'Forever propolis', 'Forever bee pollen', 'Forever med', 'Forever royal jelly'],
            ],
            'osobna-njega' => [
                'category_phrase' => 'Forever Living Products osobna njega',
                'default_meta_title' => 'Forever Living Products osobna njega | Dnevna njega i higijena',
                'default_meta_description' => 'Pregledajte Forever Living Products proizvode za osobnu njegu, od higijene i njege tijela do kose, usana i zaštite, te pronađite vodič za svakodnevnu rutinu.',
                'product_title_suffix' => 'Forever Living Products osobna njega',
                'product_description_suffix' => 'Saznajte kako se proizvod koristi, kome može odgovarati i gdje se uklapa u svakodnevnu njegu tijela, kose ili higijenu.',
                'keywords' => ['Forever Living Products osobna njega', 'Forever osobna njega', 'Forever body care', 'Forever hair care', 'Forever oral care'],
            ],
            'njega-koze' => [
                'category_phrase' => 'Forever Living Products njega kože',
                'default_meta_title' => 'Forever Living Products njega kože | Kreme, serumi i maske',
                'default_meta_description' => 'Pregledajte Forever Living Products njegu kože, serume, kreme, losione i maske te otvorite vodič za proizvod koji najbolje odgovara tipu kože i rutini.',
                'product_title_suffix' => 'Forever Living Products njega kože',
                'product_description_suffix' => 'Saznajte za koji tip kože se proizvod najčešće razmatra, što ga izdvaja i kako se uklapa u skincare rutinu.',
                'keywords' => ['Forever Living Products njega kože', 'Forever skincare', 'Forever serumi', 'Forever kreme', 'Forever skin care'],
            ],
            'forever-card-club' => [
                'category_phrase' => 'Forever Card Club suradnja',
                'default_meta_title' => 'Forever Card Club | Suradnja, alati i vodiči',
                'default_meta_description' => 'Saznajte kako funkcionira Forever Card Club, Start paket, registracija, edukacija, AI preporuke i digitalni alati za poslovnu suradnju u Foreveru.',
                'product_title_suffix' => 'Forever Card Club i suradnja',
                'product_description_suffix' => 'Saznajte kako ova stranica pomaže pri početku suradnje, registraciji ili korištenju FCC alata.',
                'keywords' => ['Forever Card Club', 'Forever suradnja', 'Start paket', 'Forever registracija', 'Forever poslovna suradnja'],
            ],
        ],
        'en' => [
            'forever-proizvodi' => [
                'category_phrase' => 'Forever Living Products',
                'default_meta_title' => 'Forever Living Products | Categories, guides and discovery',
                'default_meta_description' => 'Browse Forever Living Products by category, compare products, and open the guide that best fits a visitor goal, routine, or product interest.',
                'product_title_suffix' => 'Forever Living Products',
                'product_description_suffix' => 'See what the product is for, who may find it relevant, and how to choose the right Forever product.',
                'keywords' => ['Forever Living Products', 'Forever products', 'Forever Living Products guides', 'Forever product recommendations'],
            ],
            'napitci' => [
                'category_phrase' => 'Forever Living Products beverages',
                'default_meta_title' => 'Forever Living Products beverages | Aloe drinks and guides',
                'default_meta_description' => 'Browse Forever Living Products beverages, compare aloe drinks, and open the guide that best fits digestion support, energy, or an everyday drink routine.',
                'product_title_suffix' => 'Forever Living Products beverages',
                'product_description_suffix' => 'See what makes the product different, who may like it, and how it can fit an everyday beverage routine.',
                'keywords' => ['Forever Living Products beverages', 'Forever drinks', 'aloe drinks', 'Forever Aloe Vera Gel', 'Forever Freedom'],
            ],
            'regulacija-tezine' => [
                'category_phrase' => 'Forever Living Products weight management',
                'default_meta_title' => 'Forever Living Products weight management | Programs and products',
                'default_meta_description' => 'Browse Forever Living Products weight management products, programs, and supplements that support appetite control, routine, and an active lifestyle.',
                'product_title_suffix' => 'Forever Living Products weight management',
                'product_description_suffix' => 'See when the product is most often considered, who may find it useful, and how it can fit a weight management plan.',
                'keywords' => ['Forever Living Products weight management', 'Forever weight management', 'C9 Forever', 'Forever F15', 'Forever DX4'],
            ],
            'dodaci-prehrani' => [
                'category_phrase' => 'Forever Living Products dietary supplements',
                'default_meta_title' => 'Forever Living Products dietary supplements | Vitamins and formulas',
                'default_meta_description' => 'Browse Forever Living Products dietary supplements, compare vitamins, minerals, probiotics, and targeted formulas, and open the guide that best matches a visitor goal.',
                'product_title_suffix' => 'Forever Living Products dietary supplements',
                'product_description_suffix' => 'See what the product contains, who may find it most relevant, and how it compares with other formulas.',
                'keywords' => ['Forever Living Products dietary supplements', 'Forever supplements', 'Forever vitamins', 'Forever probiotics', 'Forever formulas'],
            ],
            'pcelinji-proizvodi' => [
                'category_phrase' => 'Forever Living Products bee products',
                'default_meta_title' => 'Forever Living Products bee products | Propolis, pollen and honey',
                'default_meta_description' => 'Browse Forever Living Products bee products, including propolis, bee pollen, honey, and royal jelly, and open the guide that best fits a visitor routine.',
                'product_title_suffix' => 'Forever Living Products bee products',
                'product_description_suffix' => 'See what the product contains, how it differs from other bee products, and who may find it relevant.',
                'keywords' => ['Forever Living Products bee products', 'Forever propolis', 'Forever bee pollen', 'Forever honey', 'Forever royal jelly'],
            ],
            'osobna-njega' => [
                'category_phrase' => 'Forever Living Products personal care',
                'default_meta_title' => 'Forever Living Products personal care | Daily care and hygiene',
                'default_meta_description' => 'Browse Forever Living Products personal care items, from hygiene and body care to hair, lips, and protection, and find the guide that fits an everyday routine.',
                'product_title_suffix' => 'Forever Living Products personal care',
                'product_description_suffix' => 'See how the product is used, who it may fit, and where it belongs in a daily body, hair, or hygiene routine.',
                'keywords' => ['Forever Living Products personal care', 'Forever personal care', 'Forever body care', 'Forever hair care', 'Forever oral care'],
            ],
            'njega-koze' => [
                'category_phrase' => 'Forever Living Products skincare',
                'default_meta_title' => 'Forever Living Products skincare | Creams, serums and masks',
                'default_meta_description' => 'Browse Forever Living Products skincare, including serums, creams, lotions, and masks, and open the guide that best matches a skin type or routine.',
                'product_title_suffix' => 'Forever Living Products skincare',
                'product_description_suffix' => 'See which skin concerns or routines the product is most often associated with, what makes it different, and how it can fit a skincare routine.',
                'keywords' => ['Forever Living Products skincare', 'Forever skin care', 'Forever serums', 'Forever creams', 'Forever skincare products'],
            ],
            'forever-card-club' => [
                'category_phrase' => 'Forever Card Club business',
                'default_meta_title' => 'Forever Card Club | Business, tools and guides',
                'default_meta_description' => 'Learn how Forever Card Club works, including the start package, sign-up flow, training, AI recommendations, and digital tools for a Forever business partnership.',
                'product_title_suffix' => 'Forever Card Club and business',
                'product_description_suffix' => 'See how this page supports partnership start, sign-up, or the use of FCC tools.',
                'keywords' => ['Forever Card Club', 'Forever business', 'Start package', 'Forever sign up', 'Forever business partnership'],
            ],
        ],
    ];

    $bundle = $intent_map[$language_code][$slug_key] ?? [];

    if(!$bundle) {
        return [
            'category_phrase' => $category_title,
            'default_meta_title' => $category_title,
            'default_meta_description' => '',
            'product_title_suffix' => $category_title ?: settings()->main->title,
            'product_description_suffix' => $language_code === 'hr'
                ? 'Saznajte više o proizvodu i pogledajte ključne informacije prije odabira.'
                : 'Learn more about the product and review the key details before choosing.',
            'keywords' => array_filter([$category_title]),
        ];
    }

    return $bundle;
}

function fc_build_blog_post_public_bundle($blog_post, $category = null, $shop_context = null, ?string $language_code = null): array {
    if(is_array($category)) {
        $category = (object) $category;
    }

    $shop_context = fc_blog_shop_context_normalize($shop_context);
    $language_code = fc_blog_resolve_language_code($language_code ?? ($blog_post->language ?? null));
    $is_croatian = $language_code === 'hr';
    $title = trim((string) ($blog_post->title ?? ''));
    $title_stem = fc_blog_extract_title_stem($title);
    $plain_description = fc_blog_plaintext_excerpt($blog_post->description ?? '', 320);
    $content_excerpt = fc_blog_plaintext_excerpt($blog_post->content ?? '', 320);
    $category_title = is_object($category) ? trim((string) ($category->title ?? '')) : '';
    $category_intent = fc_blog_get_category_intent_bundle($category, $language_code);
    $page_role = (string) ($shop_context['page_role'] ?? '');
    $is_business_start = $page_role === 'business_start' || trim((string) ($blog_post->url ?? '')) === 'start-paket';
    $is_business_context = $is_business_start || fc_blog_get_category_slug_key($category) === 'forever-card-club';
    $default_description = $plain_description ?: $content_excerpt;

    $meta_title = trim((string) ($shop_context['meta_title'] ?? ''));

    if($meta_title === '') {
        if($is_business_start) {
            $meta_title = $is_croatian
                ? 'Start paket | Forever suradnja, registracija i 30% popusta'
                : 'Start Your Journey | Forever sign-up, starter pack and 30% discount';
        } elseif($is_business_context) {
            $meta_title = sprintf(
                '%s | %s',
                $title_stem ?: $title,
                $is_croatian ? 'Forever Card Club i suradnja' : 'Forever Card Club and business'
            );
        } elseif(!empty($category_intent['product_title_suffix'])) {
            $meta_title = sprintf('%s | %s', $title_stem ?: $title, $category_intent['product_title_suffix']);
        } elseif($category_title !== '') {
            $meta_title = sprintf('%s | %s', $title_stem ?: $title, $category_title);
        } else {
            $meta_title = sprintf('%s | %s', $title_stem ?: $title, settings()->main->title);
        }
    }

    $meta_description = trim((string) ($shop_context['meta_description'] ?? ''));

    if($meta_description === '') {
        if($is_business_start) {
            $meta_description = $is_croatian
                ? 'Start paket je glavni korak za registraciju u Forever, aktivaciju Forever ID-a, početak suradnje i put prema 30% popusta uz FCC benefite.'
                : 'The Start Package is the main step for Forever sign-up, Forever ID activation, business partnership, and the path toward a 30% discount with FCC benefits.';
        } else {
            $description_suffix = trim((string) ($category_intent['product_description_suffix'] ?? ''));

            if($default_description !== '' && $description_suffix !== '' && mb_strlen($default_description) < 125) {
                $meta_description = trim($default_description . ' ' . $description_suffix);
            } else {
                $meta_description = $default_description ?: $description_suffix;
            }

            if($meta_description === '') {
                $meta_description = $is_croatian
                    ? sprintf('Saznajte više o proizvodu %s, pogledajte ključne informacije i otvorite vodič za lakši odabir.', $title_stem ?: $title)
                    : sprintf('Learn more about %s, review the key details, and open the guide for an easier product decision.', $title_stem ?: $title);
            }
        }
    }

    $meta_keywords = trim((string) ($shop_context['meta_keywords'] ?? ''));

    if($meta_keywords === '') {
        $keyword_candidates = array_merge(
            [$title_stem, $title, trim((string) ($blog_post->sku ?? '')), $category_title, $category_intent['category_phrase'] ?? ''],
            preg_split('/[\r\n,;]+/u', (string) ($blog_post->keywords ?? '')) ?: [],
            preg_split('/[\r\n,;]+/u', (string) ($blog_post->search_aliases ?? '')) ?: [],
            $category_intent['keywords'] ?? []
        );

        if($is_business_start) {
            $keyword_candidates = array_merge($keyword_candidates, $is_croatian
                ? ['Start paket', 'Forever suradnja', 'Forever registracija', '30% popusta', 'Forever ID']
                : ['Start package', 'Forever sign up', 'Forever business', '30% discount', 'Forever ID']
            );
        }

        $meta_keywords = fc_blog_build_keywords_string($keyword_candidates, 255);
    }

    return [
        'meta_title' => fc_blog_meta_truncate($meta_title, 68),
        'meta_description' => fc_blog_meta_truncate($meta_description, 160),
        'meta_keywords' => fc_blog_meta_truncate($meta_keywords, 255),
        'schema_description' => fc_blog_meta_truncate($meta_description ?: ($default_description ?: $plain_description), 160),
        'title_stem' => $title_stem ?: $title,
    ];
}

function fc_build_blog_category_indexing_bundle($category): array {
    $current_category_id = (int) ($category->blog_posts_category_id ?? 0);
    $current_url = trim((string) ($category->url ?? ''));
    $current_title = trim((string) ($category->title ?? ''));
    $current_language = fc_resolve_language_name($category->language ?? null);

    $defaults = [
        'same_url_category_ids' => $current_category_id ? [$current_category_id] : [],
        'should_index' => true,
        'preferred_url' => $current_url,
        'title_cluster_size' => 1,
        'current_total_posts' => 0,
        'preferred_total_posts' => 0,
    ];

    if($current_category_id <= 0 || $current_url === '' || $current_title === '') {
        return $defaults;
    }

    static $clusters = [];
    $cache_key = md5(($current_language ?? 'null') . '|' . mb_strtolower($current_title));

    if(!isset($clusters[$cache_key])) {
        $escaped_title = db()->escape(mb_strtolower($current_title));
        $language_condition = $current_language
            ? "`c`.`language` = '" . db()->escape($current_language) . "'"
            : "`c`.`language` IS NULL";

        $rows = db()->rawQuery("
            SELECT
                `c`.`blog_posts_category_id`,
                `c`.`url`,
                COUNT(`p`.`blog_post_id`) AS `published_posts`
            FROM `blog_posts_categories` AS `c`
            LEFT JOIN `blog_posts` AS `p`
                ON `p`.`blog_posts_category_id` = `c`.`blog_posts_category_id`
               AND `p`.`is_published` = 1
            WHERE {$language_condition}
              AND LOWER(COALESCE(`c`.`title`, '')) = '{$escaped_title}'
            GROUP BY `c`.`blog_posts_category_id`, `c`.`url`
        ");

        $url_clusters = [];

        foreach($rows as $row) {
            $row = (object) $row;
            $row_url = trim((string) ($row->url ?? ''));

            if($row_url === '') {
                continue;
            }

            if(!isset($url_clusters[$row_url])) {
                $url_clusters[$row_url] = [
                    'url' => $row_url,
                    'category_ids' => [],
                    'total_posts' => 0,
                    'depth' => substr_count(trim($row_url, '/'), '/'),
                    'min_id' => PHP_INT_MAX,
                ];
            }

            $row_category_id = (int) ($row->blog_posts_category_id ?? 0);

            if($row_category_id > 0) {
                $url_clusters[$row_url]['category_ids'][] = $row_category_id;
                $url_clusters[$row_url]['min_id'] = min($url_clusters[$row_url]['min_id'], $row_category_id);
            }

            $url_clusters[$row_url]['total_posts'] += (int) ($row->published_posts ?? 0);
        }

        uasort($url_clusters, static function($a, $b) {
            $posts_comparison = ($b['total_posts'] ?? 0) <=> ($a['total_posts'] ?? 0);

            if($posts_comparison !== 0) {
                return $posts_comparison;
            }

            $depth_comparison = ($a['depth'] ?? 0) <=> ($b['depth'] ?? 0);

            if($depth_comparison !== 0) {
                return $depth_comparison;
            }

            $length_comparison = mb_strlen((string) ($a['url'] ?? '')) <=> mb_strlen((string) ($b['url'] ?? ''));

            if($length_comparison !== 0) {
                return $length_comparison;
            }

            return ($a['min_id'] ?? PHP_INT_MAX) <=> ($b['min_id'] ?? PHP_INT_MAX);
        });

        $clusters[$cache_key] = [
            'url_clusters' => $url_clusters,
            'preferred' => $url_clusters ? reset($url_clusters) : null,
        ];
    }

    $cluster_bundle = $clusters[$cache_key];
    $current_cluster = $cluster_bundle['url_clusters'][$current_url] ?? null;
    $preferred_cluster = $cluster_bundle['preferred'] ?? null;

    if(!$current_cluster) {
        return $defaults;
    }

    return [
        'same_url_category_ids' => array_values(array_unique(array_filter(array_map('intval', $current_cluster['category_ids'] ?? [])))) ?: [$current_category_id],
        'should_index' => !$preferred_cluster || (($preferred_cluster['url'] ?? $current_url) === $current_url),
        'preferred_url' => (string) ($preferred_cluster['url'] ?? $current_url),
        'title_cluster_size' => count($cluster_bundle['url_clusters'] ?? []),
        'current_total_posts' => (int) ($current_cluster['total_posts'] ?? 0),
        'preferred_total_posts' => (int) ($preferred_cluster['total_posts'] ?? 0),
    ];
}

function fc_build_blog_category_public_bundle($category, array $posts = [], array $child_categories = [], $shop_context = null, ?string $language_code = null): array {
    $shop_context = fc_blog_category_shop_context_normalize($shop_context);
    $language_code = fc_blog_resolve_language_code($language_code ?? ($category->language ?? null));
    $is_croatian = $language_code === 'hr';
    $category_title = trim((string) ($category->title ?? ''));
    $category_description = trim(html_entity_decode(strip_tags((string) ($category->description ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $is_shop_hub = (($shop_context['page_role'] ?? '') === 'shop_hub');
    $posts_count = count($posts);
    $posts_count_label = (string) $posts_count;
    $category_intent = fc_blog_get_category_intent_bundle($category, $language_code);

    $child_titles = array_values(array_filter(array_map(static function($child_category) {
        return trim((string) ($child_category->title ?? ''));
    }, $child_categories)));

    $post_titles = array_values(array_filter(array_map(static function($blog_post) {
        return trim((string) ($blog_post->title ?? ''));
    }, $posts)));

    $child_titles_snippet = implode(', ', array_slice($child_titles, 0, 3));
    $public_subtitle = trim((string) ($shop_context['hero_subtitle'] ?? ''));

    if($public_subtitle === '') {
        if($category_description !== '') {
            $public_subtitle = $category_description;
        } elseif(!empty($category_intent['default_meta_description'])) {
            $public_subtitle = (string) $category_intent['default_meta_description'];
        } elseif($is_shop_hub) {
            $public_subtitle = $is_croatian
                ? sprintf('Pregledajte proizvode u kategoriji %s, usporedite ključne informacije i otvorite vodič koji vam najviše odgovara.', $category_title)
                : sprintf('Browse products in %s, compare the key details, and open the guide that fits you best.', $category_title);
        } elseif($posts_count > 0 && $child_titles_snippet !== '') {
            $public_subtitle = $is_croatian
                ? sprintf('U kategoriji %s možete pregledati %s vodiča i brzo otvoriti povezane podkategorije poput %s.', $category_title, $posts_count_label, $child_titles_snippet)
                : sprintf('In %s you can explore %s guides and quickly jump into related categories such as %s.', $category_title, $posts_count_label, $child_titles_snippet);
        } elseif($posts_count > 0) {
            $public_subtitle = $is_croatian
                ? sprintf('Pregledajte %s vodiča u kategoriji %s i otvorite ono što vas najviše zanima.', $posts_count_label, $category_title)
                : sprintf('Browse %s guides in %s and open what matters most to you.', $posts_count_label, $category_title);
        } else {
            $public_subtitle = $is_croatian
                ? sprintf('Pregledajte kategoriju %s i pronađite sadržaj koji vam najviše odgovara.', $category_title)
                : sprintf('Explore %s and find the content that fits you best.', $category_title);
        }
    }

    $meta_title = trim((string) ($shop_context['meta_title'] ?? ''));

    if($meta_title === '') {
        if(!empty($category_intent['default_meta_title'])) {
            $meta_title = (string) $category_intent['default_meta_title'];
        } elseif($is_shop_hub) {
            $meta_title = $is_croatian
                ? sprintf('%s | Forever proizvodi i vodiči za odabir', $category_title)
                : sprintf('%s | Forever products and buying guides', $category_title);
        } elseif($posts_count > 0) {
            $meta_title = $is_croatian
                ? sprintf('%s | Vodiči i preporuke', $category_title)
                : sprintf('%s | Guides and recommendations', $category_title);
        } else {
            $meta_title = $is_croatian
                ? sprintf('%s | Blog kategorija', $category_title)
                : sprintf('%s | Blog category', $category_title);
        }
    }

    $meta_description = trim((string) ($shop_context['meta_description'] ?? ''));

    if($meta_description === '') {
        $meta_description = (string) ($category_intent['default_meta_description'] ?? '');

        if($meta_description === '') {
            $meta_description = $category_description ?: trim((string) ($shop_context['seo_paragraphs'][0] ?? '')) ?: $public_subtitle;
        }
    }

    $meta_keywords = trim((string) ($shop_context['meta_keywords'] ?? ''));

    if($meta_keywords === '') {
        $keyword_candidates = array_filter(array_merge(
            [$category_title, $category_intent['category_phrase'] ?? ''],
            $category_intent['keywords'] ?? [],
            array_map(static function($item) {
                return trim((string) ($item['label'] ?? ''));
            }, $shop_context['filter_chips'] ?? []),
            array_slice($child_titles, 0, 4),
            array_slice($post_titles, 0, 4)
        ));

        $meta_keywords = fc_blog_build_keywords_string($keyword_candidates, 255);
    }

    $subcategories_title = trim((string) ($shop_context['subcategories_title'] ?? ''));
    if($subcategories_title === '') {
        $subcategories_title = $is_croatian ? 'Povezane podkategorije' : 'Related categories';
    }

    return [
        'meta_title' => fc_blog_meta_truncate($meta_title, 68),
        'meta_description' => fc_blog_meta_truncate($meta_description, 160),
        'meta_keywords' => fc_blog_meta_truncate($meta_keywords, 255),
        'public_subtitle' => fc_blog_meta_truncate($public_subtitle, 240),
        'subcategories_title' => $subcategories_title,
    ];
}

/* Custom code: FC-2026-04-10: selected FCC pages use /pages/{slug} as public URL */
function fc_pages_route_internal_page_slugs(): array {
    static $slugs = [
        'who-created-forever-card-club',
        'stjepan-belosa',
        'snjezana-belosa',
    ];

    return $slugs;
}

function fc_internal_page_uses_pages_route($slug): bool {
    $slug = trim((string) $slug);

    if($slug === '') {
        return false;
    }

    return in_array($slug, fc_pages_route_internal_page_slugs(), true);
}

function fc_get_internal_page_url($slug, $language = null): string {
    $slug = ltrim(trim((string) $slug), '/');
    $language = fc_resolve_language_name($language);
    $language_prefix = null;

    if($language && isset(\Altum\Language::$active_languages[$language])) {
        $language_prefix = \Altum\Language::$active_languages[$language] . '/';
    }

    $route_segment = fc_internal_page_uses_pages_route($slug) ? 'pages' : 'page';

    return SITE_URL . ($language_prefix ?? '') . $route_segment . '/' . $slug;
}
/* /Custom code: FC-2026-04-10 */

/* Custom code: FC-2026-04-10: keep localized page categories grouped by shared slug */
function fc_get_pages_category_cluster($category_url, $language = null): array {
    static $clusters = [];

    $category_url = trim((string) $category_url);
    $language = fc_resolve_language_name($language);

    if($category_url === '') {
        return [
            'category' => null,
            'ids' => [],
            'rows' => [],
        ];
    }

    $cache_key = $category_url . '|' . ($language ?? '');

    if(isset($clusters[$cache_key])) {
        return $clusters[$cache_key];
    }

    $rows = \Altum\Cache::cache_function_result('pages_category_cluster?hash=' . md5($category_url), 'pages_categories', function() use ($category_url) {
        return db()
            ->where('url', $category_url)
            ->orderBy('pages_category_id', 'ASC')
            ->get('pages_categories') ?? [];
    });

    $rows = is_array($rows) ? $rows : [];
    $preferred_category = null;
    $fallback_category = null;
    $ids = [];

    foreach($rows as $row) {
        $row_language = fc_resolve_language_name($row->language ?? null);
        $row_id = (int) ($row->pages_category_id ?? 0);

        if($row_id > 0) {
            $ids[] = $row_id;
        }

        if($preferred_category === null && $language && $row_language === $language) {
            $preferred_category = $row;
        }

        if($fallback_category === null && empty($row_language)) {
            $fallback_category = $row;
        }
    }

    return $clusters[$cache_key] = [
        'category' => $preferred_category ?? $fallback_category ?? ($rows[0] ?? null),
        'ids' => array_values(array_unique($ids)),
        'rows' => $rows,
    ];
}
/* /Custom code: FC-2026-04-10 */

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
        ->orderBy('id', 'ASC')
        ->get('users_biolinks', null, ['id', 'biolink_id']);

    $primary_mapping_id = (int) ($mapping_rows[0]->id ?? 0);
    $primary_mapped_biolink_id = (int) ($mapping_rows[0]->biolink_id ?? 0);
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
        if($primary_mapping_id > 0) {
            if($primary_mapped_biolink_id !== $resolved_biolink_id) {
                db()->where('id', $primary_mapping_id)->update('users_biolinks', [
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
