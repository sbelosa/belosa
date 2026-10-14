<?php
defined('ALTUMCODE') || die();

/** One market/owner contract for old cards, factory cards and shared products. */
function fcc_forever_markets(): array {
    static $markets;
    return $markets ??= json_decode(file_get_contents(APP_PATH.'config/fcc_forever_markets.json'),true,512,JSON_THROW_ON_ERROR);
}

function fcc_forever_id($preferences): string {
    $p=is_string($preferences)?json_decode($preferences,true):(array)$preferences;
    $meta=(array)($p['meta']??[]);$id=trim((string)($meta['foreverId']??''));
    return preg_match('/^[0-9]{12}$/D',$id)?$id:'';
}

function fcc_forever_proxy_trusted(string $ip): bool {
    foreach(explode(',',(string)getenv('FCC_TRUSTED_PROXY_CIDRS')) as $cidr) {
        $parts=explode('/',trim($cidr));$network=@inet_pton($parts[0]);$address=@inet_pton($ip);
        if(!$network||!$address||strlen($network)!==strlen($address))continue;
        $bits=isset($parts[1])?(int)$parts[1]:strlen($network)*8;
        if($bits<0||$bits>strlen($network)*8)continue;
        $bytes=intdiv($bits,8);$remainder=$bits%8;
        if(substr($network,0,$bytes)===substr($address,0,$bytes)&&(!$remainder||((ord($network[$bytes])^ord($address[$bytes])) & (255 << (8-$remainder)))===0))return true;
    }
    return false;
}

function fcc_forever_country_code($value): ?string {
    return is_string($value)&&preg_match('/^[a-z]{2}$/iD',$value)&&!in_array(strtolower($value),['xx','zz'],true)?strtolower($value):null;
}

function fcc_forever_country_context(): array {
    static $detected;
    if($detected!==null)return $detected;
    $manual=fcc_forever_country_code($_GET['market']??null);
    if(isset($_GET['market'])&&(!$manual||!isset(fcc_forever_markets()[$manual])))return $detected=['country'=>null,'source'=>'invalid_choice'];
    if($manual&&array_key_exists($manual,fcc_forever_markets()))return $detected=['country'=>$manual,'source'=>'choice'];
    $saved=fcc_forever_country_code($_COOKIE['fcc_shop_country']??null);
    if($saved&&array_key_exists($saved,fcc_forever_markets()))return $detected=['country'=>$saved,'source'=>'saved'];
    $ip=(string)($_SERVER['REMOTE_ADDR']??'');
    if(fcc_forever_proxy_trusted($ip)) {
        $header=fcc_forever_country_code($_SERVER['HTTP_CF_IPCOUNTRY']??null);
        if($header)return $detected=['country'=>$header,'source'=>'proxy'];
        // A trusted edge must overwrite this header, not append untrusted client values.
        $candidate=$_SERVER['HTTP_CF_CONNECTING_IP']??$_SERVER['HTTP_X_REAL_IP']??'';
        if(filter_var($candidate,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))$ip=$candidate;
    }
    $native=fcc_forever_country_code($_SERVER['GEOIP_COUNTRY_CODE']??null);
    if($native)return $detected=['country'=>$native,'source'=>'server'];
    // Local development only: a private address has no location, so a test country stands in for geolocation.
    if(getenv('FCC_LOCAL')==='1' && ($local_country=fcc_forever_country_code(getenv('FCC_LOCAL_COUNTRY')?:null)) && array_key_exists($local_country,fcc_forever_markets()))return $detected=['country'=>$local_country,'source'=>'local_test'];
    if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return $detected=['country'=>null,'source'=>'unknown'];
    try {
        $reader=get_maxmind_reader_city();
        if($reader->metadata()->buildEpoch>=time()-60*86400) {
            $country=fcc_forever_country_code($reader->get($ip)['country']['iso_code']??null);
            if($country)return $detected=['country'=>$country,'source'=>'geo_database'];
        }
    } catch(Throwable $e) { /* Unknown country is handled by the country chooser. */ }
    $country=\Altum\Link::get_external_geo_country_code($ip);
    return $detected=['country'=>fcc_forever_country_code($country),'source'=>$country?'geo_service':'unknown'];
}

function fcc_forever_remember_country(): void {
    $country=fcc_forever_country_code($_GET['market']??null);
    if($country&&isset(fcc_forever_markets()[$country])&&!headers_sent())
        setcookie('fcc_shop_country',$country,['expires'=>time()+30*86400,'path'=>'/','secure'=>str_starts_with(SITE_URL,'https://'),'httponly'=>true,'samesite'=>'Lax']);
}

function fcc_forever_destination_market(string $url): ?string {
    $p=parse_url($url);if(!$p||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port'])||preg_match('/[\x00-\x20\\\\]/',$url)||preg_match('~(?:%2e|%2f|%5c|\.\.)~i',$p['path']??''))return null;
    $host=strtolower($p['host']??'');
    if(in_array($host,['www.flpshop.ba','flpshop.ba'],true))return 'ba';
    if(in_array($host,['www.foreveralbania.com','foreveralbania.com'],true))return 'al';
    if(in_array($host,['www.flpshop.rs','flpshop.rs'],true))return 'rs';
    if($host==='webshop.forever.si')return 'si';
    if(!in_array($host,['foreverliving.com','www.foreverliving.com'],true))return null;
    $codes=['mkd'=>'mk','hrv'=>'hr','deu'=>'de','svn'=>'si','scg'=>'rs','aut'=>'at','aus'=>'au','can'=>'ca','irl'=>'ie','lux'=>'lu','nld'=>'nl','nor'=>'no','pol'=>'pl','swe'=>'se','gbr'=>'gb','usa'=>'us','qat'=>'qa','che'=>'ch','are'=>'ae','esp'=>'es','fra'=>'fr','ita'=>'it','bel'=>'be','dnk'=>'dk'];
    return preg_match('~^/(?:shop|join)/([a-z]{3})/~i',$p['path']??'',$m)?($codes[strtolower($m[1])]??null):null;
}

function fcc_forever_campaign(string $decoded,string $fbo,?string $market): array {
    if(!$market||in_array($market,['ba','al'],true)||fcc_forever_destination_market($decoded)!==$market)return [];
    parse_str((string)parse_url($decoded,PHP_URL_QUERY),$q);
    if(!is_string($q['fboId']??null)||!hash_equals($fbo,$q['fboId']))return [];
    return array_filter(array_intersect_key($q,array_flip(['discountConfigType','uniqueExtRefID','referralUuid'])),static fn($v)=>is_string($v)&&strlen($v)<=200);
}

function fcc_forever_explicit_market(): ?string {
    $market = fcc_forever_country_code($_GET['market'] ?? null);
    return $market && isset(fcc_forever_markets()[$market]) ? $market : null;
}

function fcc_forever_entry_url(string $ref,string $kind='shop',?int $block=null): string {
    $query = $block ? ['block'=>$block] : ['ref'=>$ref,'kind'=>$kind];
    if(array_key_exists('market', $_GET)) $query['market'] = fcc_forever_explicit_market() ?? '';
    return url('forever-shop?'.http_build_query($query));
}

function fcc_forever_market_destination(string $fbo,string $kind,?string $market,array $campaign=[]): ?string {
    if(!$market||!preg_match('/^[0-9]{12}$/D',$fbo)||fcc_market_contact_only($market))return null;
    $base=fcc_forever_markets()[$market][$kind==='business'?'business':'shop']??null;
    if(!$base||fcc_forever_destination_market($base)!==$market)return null;
    return \Altum\Link::build_forever_destination_url($base,$fbo,$market,$kind==='business'?[]:$campaign)?:null;
}

function fcc_forever_copy(string $key,?string $language=null): string {
    static $copy;
    $copy??=require APP_PATH.'config/fcc_forever_routing_copy.php';
    $language=function_exists('fcc_locale')?fcc_locale($language):(function_exists('fcc_library_pilot_locale')?fcc_library_pilot_locale():'en');
    return $copy[$language][$key]??(isset($copy['hr'][$key])&&function_exists('fcc_t')?fcc_t($copy['hr'][$key],[],$language):($copy['en'][$key]??$key));
}

function fcc_forever_public_ref(): string {
    $candidate=$_GET['ref']??$_COOKIE['referral']??'';
    $ref=is_string($candidate)?trim($candidate):'';
    if(isset($_GET['ref']))return $ref!==''&&fcc_market_referral_owner($ref)?$ref:'';
    if($ref!==''&&fcc_market_referral_owner($ref))return $ref;
    if(\Altum\Authentication::check())return \Altum\Referral::own_slug()??'';
    $owner=(new \Altum\Referral())->get_referral();
    if(!$owner)return '';
    $id=\Altum\Link::get_user_main_biolink_id((int)$owner->user_id,false);
    return (string)(db()->where('link_id',$id)->where('is_enabled',1)->getValue('links','url')??'');
}

/** A legacy URL may redirect to a current URL, but it must still be the reviewed product/variant. */
function fcc_forever_product_identity_allows(object $post,?string $market,string $base): bool {
    if(!$market||fcc_forever_destination_market($base)!==$market)return false;
    if(($post->url??'')==='start-paket')return true;
    static $families;
    if($families===null){$families=[];foreach(FccShopCatalog::identities()['posts']??[] as $identity)$families[$identity['family']]=$identity;}
    $identity=$families[$post->url??'']??null;
    // Editorial pages and custom non-catalog offers keep their existing policy.
    if(!$identity)return true;
    $expected=$identity['markets'][$market]??null;
    if(!$expected)return false;
    /* Custom code: FC-2026-10-14: reject a reviewed URL belonging to another formula or country. */
    $offer=FccShopCatalog::identities()['offers'][$expected]??null;
    if(!$offer || ($offer['family']??null)!==($identity['family']??null) || ($offer['market']??null)!==$market)return false;
    /* /Custom code: FC-2026-10-14 */
    $snapshot=FccShopCatalog::cached($base);
    if(($snapshot['status']??'')==='verified_product')return FccShopCatalog::matchesIdentity($expected,$snapshot);
    /* Custom code: FC-2026-10-14: a confirmed wrong redirect or product cannot pass URL equality. */
    if(in_array($snapshot['status']??'',['article_identity_mismatch','category','not_found','wrong_market','wrong_market_redirect','sku_mismatch','offer_identity_mismatch','offer_market_mismatch'],true)||in_array($snapshot['http']??0,[404,410],true))return false;
    /* /Custom code: FC-2026-10-14 */
    // A missing price cache does not remove a reviewed purchasing link.
    if(FccShopCatalog::clean($base)===FccShopCatalog::clean($expected))return true;
    // Titan has both flat and category-prefixed URLs for the same country/SKU.
    if(in_array(strtolower((string)parse_url($base,PHP_URL_HOST)),['foreverliving.com','www.foreverliving.com'],true)
        &&preg_match('~/([0-9]+[a-z]?)-~i',(string)parse_url($base,PHP_URL_PATH),$sku)
        &&isset(FccShopCatalog::identities()['offers'][$expected]['sku'])
        &&strtolower(ltrim($sku[1],'0'))===strtolower(ltrim(FccShopCatalog::identities()['offers'][$expected]['sku'],'0')))return true;
    FccShopCatalog::requestRefresh($base);
    return false;
}

require_once __DIR__ . '/fcc_product_blocks.php';
require_once __DIR__ . '/fcc_owned_destinations.php';
