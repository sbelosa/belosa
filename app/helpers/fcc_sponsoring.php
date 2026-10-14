<?php
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-14: a personal business story alongside existing team invitations. */
function fcc_sponsoring_locale($locale = null): string {
    $locale = is_string($locale) ? strtolower($locale) : '';
    return in_array($locale, ['en','hr','sl','de','es'], true) ? $locale : 'en';
}

function fcc_sponsoring_url(string $code, string $locale = 'en', string $action = '', string $country = ''): string {
    if(!fcc_invite_code_valid($code) || !in_array($action, ['', 'pdf','interest','starter','action'], true)) return '';
    $query = ['lang'=>fcc_sponsoring_locale($locale)];
    if($country !== '' && isset(fcc_business_countries()[$country])) $query['country'] = $country;
    return SITE_URL.'suradnja/'.$code.($action !== '' ? '/'.$action : '').'?'.http_build_query($query);
}

function fcc_sponsoring_text($value, int $limit = 240): string {
    $value=is_scalar($value)?(string)$value:'';
    $value=preg_replace('~<\s*/?\s*(?:p|div|br|li|h[1-6])\b[^>]*>~i',' ',$value);
    return mb_substr(trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($value), ENT_QUOTES|ENT_HTML5, 'UTF-8'))), 0, $limit);
}

function fcc_sponsoring_safe_url($value, bool $sameSite = false): string {
    if(!is_string($value) || strlen($value)>2048 || preg_match('/[\x00-\x20\x7f]/', $value)) return '';
    $p = parse_url($value); $site = parse_url(SITE_URL);
    if(!$p || !in_array($p['scheme'] ?? '', ['https','http'], true) || empty($p['host']) || isset($p['user']) || isset($p['pass'])) return '';
    if($sameSite && (strtolower($p['host']) !== strtolower($site['host'] ?? '') || ($p['port'] ?? null) !== ($site['port'] ?? null))) return '';
    if($sameSite && ($p['scheme'] ?? '') !== ($site['scheme'] ?? '')) return '';
    return $value;
}

/** Public projections never bypass passwords, expired schedules or audience filters. */
function fcc_sponsoring_visible(array $row, ?int $now = null): bool {
    if(empty($row['is_enabled'])) return false;
    $settings = json_decode((string)($row['settings'] ?? '{}'), true);
    if(!is_array($settings) || !empty($settings['password'])) return false;
    if(!empty($settings['schedule'])) {
        $start = strtotime((string)($row['start_date'] ?? ''));
        $end = strtotime((string)($row['end_date'] ?? ''));
        $now ??= time();
        if(!$start || !$end || $now < $start || $now > $end) return false;
    }
    foreach($settings as $key=>$value) {
        if(in_array($key,['display_continents','display_countries','display_cities','display_devices','display_languages','display_operating_systems','display_browsers'],true) && !empty($value) && $value !== 'all' && $value !== ['all']) return false;
    }
    return true;
}

/** Read only: never creates an invite or repairs a share during anonymous or PDF requests. */
function fcc_sponsoring_share(string $code): ?array {
    if(!fcc_invite_code_valid($code) || !fcc_business_enabled() || !fcc_invite_ready()) return null;
    $invite = fcc_partner_one('SELECT user_id,share_id FROM fcc_business_invites WHERE code=?', [$code]);
    if(!$invite || empty($invite['share_id'])) return null;
    $row = fcc_partner_one('SELECT public_token FROM fcc_business_shares WHERE id=? AND user_id=?', [(int)$invite['share_id'], (int)$invite['user_id']]);
    $share = $row ? fcc_business_public((string)$row['public_token']) : null;
    if(!$share || empty($share['available']) || !fcc_business_landing_public_allowed($share, 0)) return null;
    return $share;
}

/** Only fields already chosen for a public card are projected, never login email or private account data. */
function fcc_sponsoring_profile(array $share, array $card, array $blocks): ?array {
    if(!fcc_sponsoring_visible($card) || (int)$card['user_id'] !== (int)$share['user_id']) return null;
    $fbo = trim((string)($share['owner']['fbo'] ?? ''));
    if(!preg_match('/^[0-9]{12}$/D', $fbo)) return null;
    $profile = ['name'=>fcc_sponsoring_text($share['owner']['name'] ?? '',120), 'avatar'=>'', 'headline'=>'', 'phone'=>'', 'fbo'=>$fbo, 'card_url'=>fcc_sponsoring_safe_url($share['owner']['profile'] ?? ''), 'shop_url'=>'', 'initials'=>''];
    $socials = []; $shopBlock = null;
    $formats = require APP_PATH.'includes/biolink_socials.php';
    foreach($blocks as $block) {
        if((int)$block['user_id'] !== (int)$share['user_id'] || (int)$block['link_id'] !== (int)$card['link_id'] || !fcc_sponsoring_visible($block)) continue;
        $s = json_decode((string)$block['settings'], true) ?: [];
        $type = (string)$block['type'];
        if($type === 'heading' && !isset($hasName) && ($name=fcc_sponsoring_text($s['text'] ?? '',120)) !== '' && $profile['name']!=='' && mb_stripos($name,$profile['name'])!==false) { $profile['name']=$name; $hasName=true; }
        if($type === 'paragraph' && $profile['headline'] === '') $profile['headline']=fcc_sponsoring_text($s['text'] ?? '',240);
        if(in_array($type,['avatar','header'],true) && $profile['avatar'] === '') {
            $file = (string)($type === 'header' ? ($s['avatar'] ?? '') : ($s['image'] ?? ''));
            if(is_upload_file_available('avatars',$file)) $profile['avatar']=fcc_sponsoring_safe_url(\Altum\Uploads::get_full_url('avatars').rawurlencode($file),true);
        }
        if($type === 'custom_html_whatsapp' && $profile['phone'] === '') {
            $typed=trim((string)($s['phone'] ?? ''));
            // The saved v2 card already publishes its profile fallback through this visible block.
            if($typed==='' && (json_decode((string)$card['settings'],true)['fcc_card']??'')==='v2') $typed=(string)($share['owner']['phone']??'');
            $phone = fc_normalize_whatsapp_phone($typed, ['HR','SI','DE','ES','US']);
            if(is_string($phone) && preg_match('/^[1-9][0-9]{7,14}$/D',ltrim($phone,'+'))) $profile['phone']=ltrim($phone,'+');
        }
        if($type === 'socials') foreach((array)($s['socials'] ?? []) as $key=>$value) {
            if(!is_string($value) || !isset($formats[$key]['format']) || !in_array($key,['instagram','facebook','linkedin','youtube','tiktok','website','whatsapp'],true)) continue;
            $url=fcc_sponsoring_safe_url(sprintf($formats[$key]['format'],$value));
            if($url !== '') $socials[$key]=['name'=>ucfirst($key),'url'=>$url];
            if($key==='whatsapp' && $profile['phone']==='') {
                $phone=fc_normalize_whatsapp_phone($value,['HR','SI','DE','ES','US']);
                if(is_string($phone) && preg_match('/^[1-9][0-9]{7,14}$/D',ltrim($phone,'+'))) $profile['phone']=ltrim($phone,'+');
            }
        }
        if($shopBlock === null && in_array($type,['link_forever_webshop_reg','link_discount'],true)) $shopBlock=(int)$block['biolink_block_id'];
    }
    if($profile['name'] === '' || $profile['card_url'] === '') return null;
    $words=preg_split('/\s+/u',$profile['name']);
    $profile['initials']=mb_strtoupper(implode('',array_map(static fn($w)=>mb_substr($w,0,1),array_slice($words,0,2))));
    $resolved=fcc_market_referral_owner((string)$card['url']);
    $unambiguous=$resolved && (int)$resolved->user_id===(int)$share['user_id'];
    if($shopBlock !== null || $unambiguous) $profile['shop_url']=fcc_sponsoring_safe_url(fcc_forever_entry_url((string)$card['url'],'shop',$shopBlock));
    return ['sponsor'=>$profile, 'socials'=>array_values($socials), 'ref'=>$unambiguous?(string)$card['url']:''];
}

function fcc_sponsoring_products(string $ref, string $locale): array {
    if($ref === '') return [];
    $products=[];
    foreach(['forever-aloe-vera-gel','aloe-vera-gelly','aloe-ever-shield','ever-shield'] as $slug) {
        $p=fcc_product_resolve($slug,$locale,'en');
        if(!$p) continue;
        $url=fcc_sponsoring_safe_url(fcc_product_owned_url($p->blog_url,$ref),true);
        $image=fcc_sponsoring_safe_url((string)($p->image_url ?? ''),true);
        if($url === '' || $image === '') continue;
        $products[]=['name'=>fcc_sponsoring_text($p->title,100),'url'=>$url,'image'=>$image];
        if(count($products)===3) break;
    }
    if(count($products)<3) foreach(fcc_product_catalog() as $translations) {
        $p=$translations[$locale] ?? $translations['en'] ?? null;
        if(!$p || empty($p->image_url)) continue;
        $url=fcc_sponsoring_safe_url(fcc_product_owned_url($p->blog_url,$ref),true);
        $image=fcc_sponsoring_safe_url((string)$p->image_url,true);
        if($url==='' || $image==='' || in_array($url,array_column($products,'url'),true)) continue;
        $products[]=['name'=>fcc_sponsoring_text($p->title,100),'url'=>$url,'image'=>$image];
        if(count($products)===3) break;
    }
    return $products;
}

function fcc_sponsoring_model(array $share,string $code,string $locale,string $country=''): ?array {
    $locale=fcc_sponsoring_locale($locale);
    if(!isset(fcc_business_countries()[$country])) $country='';
    $card=fcc_partner_one("SELECT link_id,user_id,url,settings,is_enabled,start_date,end_date FROM links WHERE link_id=? AND user_id=? AND type='biolink'",[(int)$share['link_id'],(int)$share['user_id']]);
    if(!$card) return null;
    $blocks=fcc_partner_rows('SELECT biolink_block_id,user_id,link_id,type,settings,is_enabled,start_date,end_date FROM biolinks_blocks WHERE link_id=? AND user_id=? AND is_enabled=1 ORDER BY `order`,biolink_block_id',[(int)$share['link_id'],(int)$share['user_id']]);
    $projection=fcc_sponsoring_profile($share,$card,$blocks);
    if(!$projection) return null;
    $countries=[];foreach(fcc_business_countries() as $key=>$value) if($key!=='xx') $countries[$key]=fcc_business_country_label($key,$locale);
    $url=fcc_sponsoring_url($code,$locale,'',$country);
    $message=fcc_sponsoring_copy('contact_message',$locale);
    $phone=$projection['sponsor']['phone'];
    return ['locale'=>$locale,'code'=>$code,'url'=>$url,'pdf_url'=>fcc_sponsoring_url($code,$locale,'pdf',$country),'country'=>$country,'countries'=>$countries,
        'registration_url'=>fcc_invite_register_url((int)$share['user_id']),
        'sponsor'=>$projection['sponsor'], 'card'=>['products'=>fcc_sponsoring_products($projection['ref'],$locale),'socials'=>$projection['socials']],
        'contact'=>['whatsapp_url'=>$phone!==''?'https://wa.me/'.$phone.'?text='.rawurlencode($message):'','form_action'=>fcc_sponsoring_url($code,$locale,'interest',$country),'nonce'=>'','content_version'=>(int)$share['current_version'],'request_key'=>bin2hex(random_bytes(16))],
        'starter'=>$country!==''?fcc_business_starter($share,$country):null,'starter_action'=>fcc_sponsoring_url($code,$locale,'starter',$country),
        'error'=>null,'result'=>null];
}

/** Only a same-site local avatar file is made available to the PDF renderer, never a URL fetch. */
function fcc_sponsoring_avatar_path(string $url): string {
    if(fcc_sponsoring_safe_url($url,true)==='') return '';
    $path=rawurldecode((string)parse_url($url,PHP_URL_PATH));
    $baseUrl=(string)parse_url(\Altum\Uploads::get_full_url('avatars'),PHP_URL_PATH);
    if(!str_starts_with($path,$baseUrl)) return '';
    $file=substr($path,strlen($baseUrl));
    if(!preg_match('/^[a-zA-Z0-9_.-]+\.(png|jpe?g|webp)$/Di',$file)) return '';
    $base=realpath(\Altum\Uploads::get_full_path('avatars'));$real=$base?realpath($base.DIRECTORY_SEPARATOR.$file):false;
    return $real && str_starts_with($real,$base.DIRECTORY_SEPARATOR) && is_file($real) && filesize($real)<=8*1024*1024 ? $real : '';
}
/* /Custom code: FC-2026-10-14 */
