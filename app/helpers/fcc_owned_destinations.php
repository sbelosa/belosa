<?php
defined('ALTUMCODE') || die();

/** Keep attribution repairs scoped to FCC commerce pages and official commerce destinations. */
function fcc_forever_owned_destination_recognized(string $url): bool {
    $parts = parse_url(html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if(!$parts || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)
        || isset($parts['user']) || isset($parts['pass']) || preg_match('/[\\x00-\\x20\\\\\\\\]/', $url)) return false;
    $site = parse_url(SITE_URL);
    $host = strtolower($parts['host'] ?? '');
    $internal = in_array($host, array_unique([strtolower($site['host'] ?? ''), 'forevercard.club', 'www.forevercard.club']), true);
    if(isset($parts['port']) && (!$internal || $host !== strtolower($site['host'] ?? '') || ($parts['port'] ?? null) !== ($site['port'] ?? null))) return false;
    $path = $parts['path'] ?? '/';
    if(preg_match('~(?:%2e|%2f|%5c|\\.\\.)~i', $path)) return false;
    if($internal) {
        $base_path = rtrim($site['path'] ?? '/', '/');
        if($base_path !== '' && str_starts_with($path, $base_path . '/')) $path = substr($path, strlen($base_path));
        $codes = array_unique(array_values((array) \Altum\Language::$active_languages));
        $path = preg_replace('~^/(?:' . implode('|', array_map(static fn($code) => preg_quote((string) $code, '~'), $codes)) . ')/~', '/', $path);
        return preg_match('~^/blog/[^/]+/?$~', $path) === 1 || in_array($path, ['/blog-click', '/forever-shop'], true);
    }
    $official_hosts = ['foreverliving.com', 'www.foreverliving.com', 'flpshop.ba', 'www.flpshop.ba', 'flpshop.rs', 'www.flpshop.rs', 'foreveralbania.com', 'www.foreveralbania.com', 'webshop.forever.si', 'thealoeveraco.shop', 'foreverlivingproducts.com', 'www.foreverlivingproducts.com'];
    if(!in_array($host, $official_hosts, true) || isset($parts['port'])) return false;
    return fcc_forever_destination_market('https://' . $host . $path) !== null
        || preg_match('~^/(?:shop|join)/[a-z]{3}/~i', $path) === 1
        || in_array($host, ['thealoeveraco.shop', 'foreverlivingproducts.com', 'www.foreverlivingproducts.com'], true);
}

/** Recognized product and shop links always belong to the card that publishes them. */
function fcc_forever_owned_destination(string $url, string $owner_ref, $preferences = null): string {
    if(!fcc_forever_owned_destination_recognized($url)) return $url;
    $parts = parse_url(html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $host = strtolower($parts['host'] ?? '');
    $site = parse_url(SITE_URL);
    $site_host = strtolower($site['host'] ?? '');
    $internal = in_array($host, array_unique([$site_host, 'forevercard.club', 'www.forevercard.club']), true);
    if(isset($parts['port']) && (!$internal || $host !== $site_host || ($parts['port'] ?? null) !== ($site['port'] ?? null))) return $url;
    $path = $parts['path'] ?? '/';
    if(preg_match('~(?:%2e|%2f|%5c|\.\.)~i', $path)) return $url;
    $query = [];
    parse_str($parts['query'] ?? '', $query);

    if($internal) {
        $base_path = rtrim($site['path'] ?? '/', '/');
        if($base_path !== '' && str_starts_with($path, $base_path . '/')) $path = substr($path, strlen($base_path));
        $language_codes = array_unique(array_values((array) \Altum\Language::$active_languages));
        $locale_prefix = '~^/(?:' . implode('|', array_map(static fn($code) => preg_quote((string) $code, '~'), $language_codes)) . ')/~';
        $action_path = preg_replace($locale_prefix, '/', $path);
        if(preg_match('~^/blog/[^/]+/?$~', $action_path) || $action_path === '/blog-click') {
            unset($query['ref'], $query['fboId'], $query['id'], $query['distribID'], $query['block'], $query['destination']);
            $local = rtrim(SITE_URL, '/') . $path . ($query ? '?' . http_build_query($query) : '') . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
            return fcc_product_owned_url($local, $owner_ref);
        }
        if($action_path === '/forever-shop') {
            $kind = ($query['kind'] ?? '') === 'business' ? 'business' : 'shop';
            $preserved_block = null;
            if(!empty($query['block'])) {
                $block = db()->where('biolink_block_id', (int) $query['block'])->where('is_enabled', 1)->getOne('biolinks_blocks', ['biolink_block_id', 'link_id', 'user_id', 'type']);
                if(($block->type ?? '') === 'link_forever_shop') $kind = 'business';
                $owner = fcc_market_referral_owner($owner_ref);
                if($owner && $block && (int) $block->user_id === (int) $owner->user_id && in_array($block->type, ['link_forever_shop', 'link_forever_webshop_reg', 'link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo'], true)) {
                    $card = db()->where('link_id', (int) $block->link_id)->where('user_id', (int) $owner->user_id)->where('url', $owner_ref)->where('type', 'biolink')->where('is_enabled', 1)->getOne('links', ['link_id']);
                    if($card) $preserved_block = (int) $block->biolink_block_id;
                }
            }
            $target = fcc_forever_entry_url($owner_ref, $kind, $preserved_block);
            if(!array_key_exists('market', $_GET) && isset($query['market']) && is_string($query['market'])) $target .= '&market=' . rawurlencode($query['market']);
            return $target;
        }
        return $url;
    }

    $official_hosts = ['foreverliving.com', 'www.foreverliving.com', 'flpshop.ba', 'www.flpshop.ba', 'flpshop.rs', 'www.flpshop.rs', 'foreveralbania.com', 'www.foreveralbania.com', 'webshop.forever.si', 'thealoeveraco.shop', 'foreverlivingproducts.com', 'www.foreverlivingproducts.com'];
    if(!in_array($host, $official_hosts, true) || isset($parts['port'])) return $url;
    $canonical = 'https://' . $host . $path;
    if(fcc_forever_destination_market($canonical) === null && !preg_match('~^/(?:shop|join)/[a-z]{3}/~i', $path) && !in_array($host, ['thealoeveraco.shop', 'foreverlivingproducts.com', 'www.foreverlivingproducts.com'], true)) return $url;

    // Exact reviewed URLs retain product identity while the next controller selects the visitor market.
    static $families, $reviewed_skus;
    if($families === null) {
        $families = [];
        $reviewed_skus = [];
        $reviewed = [];
        $add = static function(string $base, string $slug) use (&$families, &$reviewed): void {
            $clean = FccShopCatalog::clean($base);
            if($clean === '') return;
            if(isset($reviewed[$clean])) return;
            if(!array_key_exists($clean, $families)) $families[$clean] = $slug;
            elseif($families[$clean] !== $slug) $families[$clean] = '';
        };
        foreach(FccShopCatalog::identities()['posts'] ?? [] as $identity) foreach($identity['markets'] ?? [] as $market => $base) {
            $slug = (string) ($identity['family'] ?? '');
            $add((string) $base, $slug);
            $reviewed_host = strtolower((string) parse_url((string) $base, PHP_URL_HOST));
            if(in_array($reviewed_host, ['foreverliving.com', 'www.foreverliving.com'], true) && fcc_forever_destination_market((string) $base) === $market
                && preg_match('~/([0-9]+[a-z]?)-[^/]+/?$~i', (string) parse_url((string) $base, PHP_URL_PATH), $sku)) {
                $key = $market . '|' . strtolower(ltrim($sku[1], '0'));
                if(!array_key_exists($key, $reviewed_skus)) $reviewed_skus[$key] = $slug;
                elseif($reviewed_skus[$key] !== $slug) $reviewed_skus[$key] = '';
            }
        }
        $reviewed = array_filter($families, static fn($family) => $family !== '');
        foreach(fcc_product_catalog() as $slug => $translations) foreach($translations as $translation) foreach((array) json_decode((string) $translation->webshop_links) as $base) if(is_string($base)) $add($base, $slug);
    }
    $slug = $families[FccShopCatalog::clean($canonical)] ?? '';
    if($slug === '' && in_array($host, ['foreverliving.com', 'www.foreverliving.com'], true)
        && ($market = fcc_forever_destination_market($canonical)) !== null && preg_match('~/([0-9]+[a-z]?)-[^/]+/?$~i', $path, $sku))
        $slug = $reviewed_skus[$market . '|' . strtolower(ltrim($sku[1], '0'))] ?? '';
    $target = (string) (\Altum\Language::$code ?? \Altum\Language::$default_code ?? 'en');
    $product = $slug !== '' ? (fcc_product_resolve($slug, $target, 'hr') ?? fcc_product_resolve($slug, 'en')) : null;
    if($product) return fcc_product_owned_url(url('blog-click?blog_post_id=' . (int) $product->blog_post_id), $owner_ref);
    $kind = str_starts_with($path, '/join/') ? 'business' : 'shop';
    return fcc_forever_entry_url($owner_ref, $kind);
}
