<?php
defined('ALTUMCODE') || die();

/** Reversible local rollout policy. Price refresh cannot approve a disabled offer. */
function fcc_market_policy_enabled(): bool {
    return getenv('FCC_MARKET_POLICY_ENABLED') === '1'
        || (getenv('FCC_LOCAL') === '1' && getenv('FCC_PARTNER_ENABLED') === '1');
}
function fcc_market_policy_config(): array {
    static $config;
    return $config ??= json_decode(file_get_contents(APP_PATH.'config/fcc_market_policy.json'), true);
}
function fcc_market_contact_only(?string $market): bool {
    return (fcc_market_policy_enabled() || (function_exists('fcc_library_pilot_enabled') && fcc_library_pilot_enabled())) && in_array(strtolower((string)$market), fcc_market_policy_config()['disabled_markets'], true);
}
/** The shared destination builder also covers advisor cards and older product buttons. */
function fcc_market_destination_allowed(string $url, ?string $market): bool {
    if(fcc_market_contact_only($market) || fcc_market_contact_only(FccShopCatalog::market($url))) return false;
    if(!fcc_market_policy_enabled()) return true;
    $parts=parse_url(html_entity_decode($url,ENT_QUOTES|ENT_HTML5,'UTF-8'));
    $identity=strtolower((string)($parts['scheme']??'')).'://'.strtolower((string)($parts['host']??'')).($parts['path']??'');
    $known_product=isset(fcc_market_policy_config()['approved_products'][$identity])
        || in_array($identity,fcc_market_policy_config()['disabled_product_urls']??[],true);
    $path=(string)parse_url($url,PHP_URL_PATH);
    if(!$known_product && !preg_match('~/(products|proizvodi|produktet)/.+~',$path)) return true;
    $clean=FccShopCatalog::clean($url);$country=FccShopCatalog::market($url);
    if(!$clean || !$country || $country!==strtolower((string)$market) || (fcc_market_policy_config()['approved_products'][$clean]??null)!==$country) return false;
    $snapshot=FccShopCatalog::cached($clean);
    return isset(FccShopCatalog::identities()['offers'][$clean]) && ($snapshot['status']??'')==='verified_product' && ($snapshot['market']??'')===$country && FccShopCatalog::matchesIdentity($clean,$snapshot);
}
function fcc_market_product_allowed(object $post, ?string $market): bool {
    if(fcc_market_contact_only($market)) return false;
    if(!fcc_market_policy_enabled()) return true;
    $market=strtolower((string)$market);
    if(fcc_market_contact_only($market)) return false;
    $base=((array)json_decode($post->webshop_links??'{}',true))[$market]??'';
    $clean=FccShopCatalog::clean($base);
    if(!$clean || FccShopCatalog::market($clean)!==$market || (fcc_market_policy_config()['approved_products'][$clean]??null)!==$market) return false;
    $snapshot=FccShopCatalog::cached($clean);
    return FccShopCatalog::articleIdentityMatches((int)($post->blog_post_id??0),$market,$clean)
        && ($snapshot['status']??'')==='verified_product' && ($snapshot['market']??'')===$market && FccShopCatalog::matchesIdentity($clean,$snapshot);
}
function fcc_market_post_topic(object $post): ?string {
    static $topics;
    if($topics===null) {
        $topics=[];
        $map=['napitci'=>'drinks','regulacija-tezine'=>'weight','dodaci-prehrani'=>'supplement','pcelinji-proizvodi'=>'bee','osobna-njega'=>'personal','njega-koze'=>'care'];
        foreach(db()->get('blog_posts_categories',null,['blog_posts_category_id','url']) as $c) {
            if(isset($map[basename($c->url)])) $topics[(int)$c->blog_posts_category_id]=$map[basename($c->url)];
        }
    }
    return $topics[(int)($post->blog_posts_category_id??0)]??null;
}
function fcc_market_category_base(object $post, ?string $market): ?array {
    if(!fcc_market_policy_enabled() || fcc_market_contact_only($market) || fcc_market_product_allowed($post,$market)) return null;
    $topic=fcc_market_post_topic($post);
    if(!$topic) return null;
    $links=fcc_market_policy_config()['categories'][$market??'']??[];
    $base=$links[$topic]??$links['all']??null;
    if(!$base || !FccShopCatalog::clean($base) || FccShopCatalog::market($base)!==$market) return null;
    return ['url'=>$base,'topic'=>isset($links[$topic])?$topic:'all'];
}
function fcc_market_referral_owner(string $ref): ?object {
    if($ref==='') return null;
    $link=db()->where('url',$ref)->where('type','biolink')->where('is_enabled',1)->getOne('links',['link_id','user_id']);
    if(!$link) return null;
    return db()->where('user_id',$link->user_id)->where('status',1)->getOne('users',['user_id','name','preferences']) ?: null;
}
function fcc_market_category_destination(object $post, string $market, string $ref): ?string {
    $category=fcc_market_category_base($post,$market);
    if(!$category || !($owner=fcc_market_referral_owner($ref))) return null;
    $prefs=is_string($owner->preferences)?json_decode($owner->preferences,true):(array)$owner->preferences;
    $forever_id=$prefs['meta']['foreverId']??'';
    // Never publish an unattributed alternative when the owner has no Forever ID.
    if(!is_scalar($forever_id) || trim((string)$forever_id)==='') return null;
    $discount=\Altum\Link::get_main_biolink_discount_query_params((int)$owner->user_id,$market);
    return \Altum\Link::build_forever_destination_url($category['url'],(string)$forever_id,$market,$discount) ?: null;
}
function fcc_market_contact(object $post, string $ref, ?string $market=null): ?array {
    $owner=fcc_market_referral_owner($ref);
    if(!$owner) return null;
    // This is the same public profile number used to populate the owner's WhatsApp block.
    // Billing numbers, inviter numbers and legacy shortcode defaults are never used.
    $phone=fc_get_user_default_whatsapp_phone($owner);
    $title=trim(html_entity_decode(strip_tags($post->title),ENT_QUOTES|ENT_HTML5,'UTF-8'));
    $title=function_exists('fcc_product_editorial_profiles')?(fcc_product_editorial_profiles()[$post->url]['title']??$title):$title;
    $title=preg_replace('/\s*[—–]\s*|\s+-\s+/u',', ',$title);
    $locale=fcc_library_pilot_locale();
    $message=match($locale) {
        'hr'=>'Pozdrav! Zanima me '.$title.'. Možete li mi reći više o proizvodu i kako ga mogu naručiti? Javite mi se kad budete u mogućnosti. Hvala!',
        'sl'=>'Pozdravljeni! Zanima me '.$title.'. Mi lahko poveste več o izdelku in kako ga lahko naročim? Oglasite se, ko boste utegnili. Hvala!',
        'sr'=>'Zdravo! Zanima me '.$title.'. Možete li da mi kažete više o proizvodu i kako mogu da ga poručim? Javite mi se kada budete u mogućnosti. Hvala!',
        'de'=>'Hallo! Ich interessiere mich für '.$title.'. Kannst du mir mehr über das Produkt und die Bestellung sagen? Melde dich bitte, wenn du Zeit hast. Vielen Dank!',
        default=>'Hello! I’m interested in '.$title.'. Could you tell me more about the product and how to order it? Please get in touch when you have a moment. Thank you!',
    };
    if(fcc_new_locale($locale)) $message=fcc_t('Pozdrav! Zanima me {product}. Možete li mi reći više o proizvodu i kako ga mogu naručiti? Javite mi se kad budete u mogućnosti. Hvala!',['{product}'=>$title],$locale);
    if($market && fcc_new_locale($locale)) $message.=' '.fcc_t('Moja zemlja: {country}.',['{country}'=>fcc_library_pilot_country_name($market)],$locale);
    elseif($market) $message.=match($locale) {'hr'=>' Moja zemlja: ','sl'=>' Moja država: ','sr'=>' Moja zemlja: ','de'=>' Mein Land: ',default=>' My country: '}.fcc_library_pilot_country_name($market).'.';
    return ['name'=>$owner->name,'profile'=>url($ref),'phone'=>$phone,
        'whatsapp'=>$phone?'https://wa.me/'.$phone.'?text='.rawurlencode($message):null,
        'tel'=>$phone?'tel:+'.$phone:null];
}
/** Remove obsolete commerce links from legacy article HTML without changing stored content. */
function fcc_market_article_content(string $html, object $post, ?string $market, string $ref): string {
    if(!fcc_market_policy_enabled()) return $html;
    $allowed=fcc_market_product_allowed($post,$market);
    $destination=$allowed?\Altum\Link::get_product_webshop_link($ref,$post->blog_post_id,$market):null;
    $tracked=$destination?url('blog-click?'.http_build_query(['blog_post_id'=>$post->blog_post_id,'market'=>$market,'ref'=>$ref,'destination'=>$destination])):null;
    return preg_replace_callback('~<a\b([^>]*?)href\s*=\s*([\'\"])(.*?)\2([^>]*)>(.*?)</a>~is',static function($m) use($tracked) {
        $href=html_entity_decode($m[3],ENT_QUOTES|ENT_HTML5,'UTF-8');
        $host=strtolower((string)parse_url($href,PHP_URL_HOST));
        $commerce=preg_match('~(^|\.)(foreverliving\.com|flpshop\.ba|flpshop\.rs|foreveralbania\.com|thealoeveraco\.shop)$~',$host)
            || str_contains($href,'webshop.forever.si') || str_contains($href,'blog-click');
        if(!$commerce) return $m[0];
        if(!$tracked) return '<span class="fl-muted">'.$m[5].'</span>';
        return '<a href="'.htmlspecialchars($tracked,ENT_QUOTES,'UTF-8').'" target="_blank" rel="noopener noreferrer">'.$m[5].'</a>';
    },$html);
}
