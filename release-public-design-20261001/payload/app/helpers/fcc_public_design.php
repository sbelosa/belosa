<?php
defined('ALTUMCODE') || die();

/** Public presentation is independent of account access and rollout permissions. */
function fcc_public_copy(string $key): string {
    static $copy;
    $copy ??= require APP_PATH.'config/fcc_public_design.php';
    $locale = function_exists('fcc_locale') ? fcc_locale() : 'en';
    return $copy[$locale][$key] ?? $copy['en'][$key] ?? $key;
}

function fcc_public_ref(): ?string {
    $ref = query_clean((string)($_GET['ref'] ?? $_COOKIE['referral'] ?? $_COOKIE['referred_by'] ?? ''));
    return $ref !== '' ? $ref : null;
}

function fcc_public_url(string $path = '', array $params = []): string {
    if($path === '' && is_logged_in()) $params['view'] = 'public';
    return fcc_library_pilot_url($path, fcc_public_ref(), $params);
}

function fcc_public_content_url(object $item, string $prefix): string {
    $language = \Altum\Language::$active_languages[$item->language ?? ''] ?? '';
    $href = SITE_URL.($language ? $language.'/' : '').$prefix.'/'.$item->url;
    $params = array_filter(['ref'=>fcc_public_ref()]);
    return $href.($params ? '?'.http_build_query($params) : '');
}

/** Preserve the archive, search and referring member through every results page. */
function fcc_public_pagination_query(string $filters, bool $archive = false): string {
    parse_str($filters, $params);
    unset($params['page'], $params['design']);
    if($archive) $params['view'] = ($_GET['view'] ?? '') === 'public' ? 'public' : 'articles';
    if(!empty($_GET['search'])) $params['search'] = mb_substr(trim(query_clean((string)$_GET['search'])),0,256);
    if(fcc_public_ref()) $params['ref'] = fcc_public_ref();
    return http_build_query($params,'','&',PHP_QUERY_RFC3986);
}

function fcc_public_assets(): void {
    if(is_logged_in() && !headers_sent()) header('Cache-Control: private, no-store');
    static $done=false;
    if($done)return;
    $done=true;
    \Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-public.css?v='.filemtime(ASSETS_PATH.'css/fcc-public.css').'">','head');
}

/** Keep search engine context alongside the visible public archive. Never include personal referral parameters. */
function fcc_public_listing_schema(array $posts, ?object $category = null, bool $products = false): void {
    $canonical = \Altum\Meta::$canonical ?: url('blog');
    $name = $category->title ?? fcc_public_copy($products ? 'products' : 'articles');
    $crumbs = [
        ['@type'=>'ListItem','position'=>1,'name'=>fcc_public_copy('home'),'item'=>url()],
        ['@type'=>'ListItem','position'=>2,'name'=>fcc_public_copy('library'),'item'=>url('blog')],
    ];
    if($category) $crumbs[] = ['@type'=>'ListItem','position'=>3,'name'=>$category->title,'item'=>$canonical];
    $items = [];
    foreach($posts as $post) {
        $language = \Altum\Language::$active_languages[$post->language ?? ''] ?? '';
        $items[] = ['@type'=>'ListItem','position'=>count($items)+1,'name'=>$products ? (fcc_library_pilot_product($post->url)['title'] ?? $post->title) : $post->title,'url'=>SITE_URL.($language ? $language.'/' : '').'blog/'.$post->url];
    }
    $schema = ['@context'=>'https://schema.org','@graph'=>[
        ['@type'=>'BreadcrumbList','itemListElement'=>$crumbs],
        ['@type'=>'CollectionPage','name'=>$name,'url'=>$canonical,'inLanguage'=>fcc_language_tag(),'mainEntity'=>['@type'=>'ItemList','numberOfItems'=>count($items),'itemListElement'=>$items]],
    ]];
    \Altum\Event::add_content('<script type="application/ld+json">'.json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP).'</script>','head');
}
