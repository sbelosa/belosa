<?php
defined('ALTUMCODE') || die();

/** Load complete translation families before selecting a language. */
function fcc_product_catalog_from_rows(array $posts): array {
    $catalog = [];
    foreach($posts as $post) {
        $slug = trim((string) ($post->url ?? ''));
        $code = \Altum\Language::$active_languages[$post->language ?? ''] ?? null;
        if($slug === '' || !$code) continue;
        $entry = (object) [
            'blog_post_id' => (int) $post->blog_post_id,
            'title' => (string) $post->title,
            'description' => mb_substr(trim(strip_tags((string) ($post->description ?? ''))), 0, 220),
            'blog_url' => SITE_URL . $code . '/blog/' . $slug,
            'image_url' => !empty($post->image) ? \Altum\Uploads::get_full_url('blog') . $post->image : null,
            'language_code' => $code,
            'translation_key' => $slug,
            'webshop_links' => $post->webshop_links ?? '{}',
        ];
        if(!isset($catalog[$slug][$code]) || $entry->blog_post_id > $catalog[$slug][$code]->blog_post_id) $catalog[$slug][$code] = $entry;
    }
    foreach($catalog as &$translations) ksort($translations);
    unset($translations);
    return $catalog;
}

function fcc_product_catalog(): array {
    static $catalog;
    return $catalog ??= fcc_product_catalog_from_rows((array) db()->where('is_published', 1)->get('blog_posts', null, ['blog_post_id', 'title', 'description', 'url', 'image', 'language', 'webshop_links']));
}

function fcc_product_resolve(string $slug, string $target, string $fallback = '', ?array $catalog = null): ?object {
    $translations = ($catalog ?? fcc_product_catalog())[trim($slug)] ?? [];
    foreach(array_unique([$target, $fallback]) as $code) if($code !== '' && isset($translations[$code])) return clone $translations[$code];
    return null;
}

function fcc_product_picker_catalog(string $language, string $ref): array {
    $products = [];
    foreach(fcc_product_catalog() as $slug => $translations) {
        $entry = fcc_product_resolve($slug, $language, 'hr') ?? fcc_product_resolve($slug, 'en');
        if(!$entry) continue;
        $entry->blog_url = fcc_product_owned_url($entry->blog_url, $ref);
        $entry->available_language_codes = array_keys($translations);
        $entry->available_languages_label = implode(' / ', array_map('strtoupper', array_keys($translations)));
        $entry->translations = [];
        foreach($translations as $code => $translation) {
            $variant = clone $translation;
            unset($variant->webshop_links);
            $variant->blog_url = fcc_product_owned_url($variant->blog_url, $ref);
            $entry->translations[$code] = $variant;
        }
        $products[] = $entry;
    }
    usort($products, static fn($a, $b) => strcasecmp($a->title, $b->title));
    return $products;
}

/** Explicit market choices travel with the click. Detected countries do not. */
function fcc_product_owned_url(string $url, string $ref): string {
    $parts = parse_url($url);
    $site = parse_url(SITE_URL);
    if(!$parts || strtolower($parts['host'] ?? '') !== strtolower($site['host'] ?? '') || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])) return '';
    $query = [];
    parse_str($parts['query'] ?? '', $query);
    $query['ref'] = $ref;
    if(array_key_exists('market', $_GET)) $query['market'] = fcc_forever_explicit_market() ?? '';
    $base = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . ($parts['path'] ?? '/');
    return $base . '?' . http_build_query($query) . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
}

function fcc_product_text_key(string $text): string {
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(stripslashes($text)), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

/** Recognize legacy picker text in every translation, preserving custom copy. */
function fcc_product_text_mode(string $value, string $field, string $slug, ?object $previous = null): string {
    $key = fcc_product_text_key($value);
    $mode = 'product_' . $field . '_mode';
    if($previous && ($previous->$mode ?? '') === 'custom' && $key === fcc_product_text_key((string) ($previous->$field ?? ''))) return 'custom';
    $snapshot = 'product_' . $field . '_auto_value';
    if($previous && ($previous->$mode ?? '') === 'auto' && property_exists($previous, $snapshot)) {
        if($key === fcc_product_text_key((string) $previous->$snapshot)) return 'auto';
        if($key === fcc_product_text_key((string) ($previous->$field ?? ''))) return 'custom';
    }
    if($key === '') return 'auto';
    foreach(fcc_product_catalog()[$slug] ?? [] as $translation) {
        if($key === fcc_product_text_key((string) $translation->{$field === 'name' ? 'title' : 'description'})) return 'auto';
    }
    return 'custom';
}

/** Other editors can change copy without updating product metadata. */
function fcc_product_saved_text_mode(object $settings, string $field, string $slug): string {
    $mode = 'product_' . $field . '_mode';
    $snapshot = 'product_' . $field . '_auto_value';
    if(($settings->$mode ?? '') === 'custom') return 'custom';
    if(($settings->$mode ?? '') === 'auto' && property_exists($settings, $snapshot)) return fcc_product_text_key((string) ($settings->$field ?? '')) === fcc_product_text_key((string) $settings->$snapshot) ? 'auto' : 'custom';
    if(trim($slug) === '' && (int) ($settings->product_blog_post_id ?? 0) > 0) {
        static $legacy_slugs = [];
        $id = (int) $settings->product_blog_post_id;
        $slug = $legacy_slugs[$id] ??= (string) (db()->where('blog_post_id', $id)->where('is_published', 1)->getValue('blog_posts', 'url') ?? '');
    }
    return fcc_product_text_mode((string) ($settings->$field ?? ''), $field, $slug, $settings);
}

function fcc_product_normalize_submission(array $input, object $link, ?object $previous = null): ?array {
    $slug = trim((string) ($input['product_translation_key'] ?? ''));
    if($slug === '' && !empty($input['product_blog_post_id'])) $slug = (string) (db()->where('blog_post_id', (int) $input['product_blog_post_id'])->where('is_published', 1)->getValue('blog_posts', 'url') ?? '');
    $app = (string) ($link->settings->language_code ?? \Altum\Language::$default_code ?? 'en');
    $target = ($input['product_language_mode'] ?? 'app') === 'manual' ? (string) ($input['product_language_code'] ?? $app) : $app;
    $entry = fcc_product_resolve($slug, $target, (string) ($input['product_fallback_language_code'] ?? 'hr'));
    if(!$entry) return null;
    foreach(['name' => 'title', 'description' => 'description'] as $field => $source) {
        $mode = fcc_product_text_mode((string) ($input[$field] ?? ''), $field, $slug, $previous);
        $input['product_' . $field . '_mode'] = $mode;
        if($mode === 'auto') $input[$field] = query_clean($entry->$source);
        $input['product_' . $field . '_auto_value'] = $mode === 'auto' ? (string) $input[$field] : '';
    }
    $input['product_translation_key'] = $slug;
    $input['product_blog_post_id'] = $entry->blog_post_id;
    $input['product_image_url'] = $entry->image_url ?? '';
    $input['location_url'] = fcc_product_owned_url($entry->blog_url, (string) $link->url);
    return $input;
}

function fcc_product_render_block(object $row, object $card): bool {
    $slug = trim((string) ($row->settings->product_translation_key ?? ''));
    if($slug === '' && !empty($row->settings->product_blog_post_id)) $slug = (string) (db()->where('blog_post_id', (int) $row->settings->product_blog_post_id)->getValue('blog_posts', 'url') ?? '');
    $app = (string) ($card->settings->language_code ?? \Altum\Language::$default_code ?? 'en');
    $target = ($row->settings->product_language_mode ?? 'app') === 'manual' ? (string) ($row->settings->product_language_code ?? $app) : $app;
    $entry = fcc_product_resolve($slug, $target, (string) ($row->settings->product_fallback_language_code ?? 'hr'));
    if(!$entry) return false;
    foreach(['name' => 'title', 'description' => 'description'] as $field => $source) {
        if(fcc_product_saved_text_mode($row->settings, $field, $slug) === 'auto') $row->settings->$field = input_clean($entry->$source);
    }
    $row->settings->product_translation_key = $slug;
    $row->settings->product_blog_post_id = $entry->blog_post_id;
    $row->settings->product_image_url = $entry->image_url;
    $row->location_url = fcc_product_owned_url($entry->blog_url, (string) $card->url);
    return $row->location_url !== '';
}
