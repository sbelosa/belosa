<?php
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_shop_catalog.php';
require_once __DIR__.'/fcc_market_policy.php';
require_once __DIR__.'/fcc_product_editorial.php';
require_once __DIR__.'/fcc_public_design.php';

/** The product library is the public design. Account permissions remain in fcc_partner_enabled(). */
function fcc_library_pilot_enabled(): bool {
    return true;
}
function fcc_library_pilot_config(): array {
    static $config;
    return $config ??= require APP_PATH.'config/fcc_library_pilot.php';
}
function fcc_library_pilot_locale(): string {
    // Full locale rollout is separate from country detection. Never read or set market here.
    return in_array(\Altum\Language::$code, ['hr','en','sl','de','sr', 'sq', 'cnr', 'fr', 'es'], true) ? \Altum\Language::$code : 'en';
}
function fcc_lp_t(string $key): string {
    $copy=fcc_library_pilot_config()['copy'];
    return fcc_t($copy[fcc_library_pilot_locale()][$key] ?? $copy[in_array(fcc_library_pilot_locale(),['sl','de','sr', 'sq', 'cnr', 'fr', 'es'],true)?'hr':'en'][$key] ?? $key);
}
function fcc_library_pilot_product(string $slug): ?array {
    $entry=fcc_library_pilot_config()['products'][$slug] ?? null;
    if(!$entry) {
        $post=fcc_library_product_index()[$slug]??null;
        if(!$post) return null;
        $hr=in_array(fcc_library_pilot_locale(),['hr','sl','de','sr', 'sq', 'cnr', 'fr', 'es'],true);
        $intro=trim(html_entity_decode(strip_tags((string)$post->description),ENT_QUOTES|ENT_HTML5,'UTF-8'));
        $intro=preg_replace('/\s*[—–]\s*|\s+-\s+/u',', ',$intro);
        return fcc_product_editorial_apply($slug,array_merge(['title'=>html_entity_decode($post->title,ENT_QUOTES|ENT_HTML5,'UTF-8'),'topic'=>$post->library_topic,'image_scale'=>1.0,
            'teaser'=>$intro,'intro'=>$intro,'format'=>fcc_lp_t($post->library_topic),
            'pack'=>'Forever Living Products',
            'editorial'=>true],fcc_library_article_editorial($post)));
    }
    $editorial=require APP_PATH.'config/fcc_library_editorial.php';
    return fcc_product_editorial_apply($slug,array_merge(['editorial'=>true],$entry, $entry[in_array(fcc_library_pilot_locale(),['sl','de','sr', 'sq', 'cnr', 'fr', 'es'],true)?'hr':fcc_library_pilot_locale()] ?? $entry['en'], $editorial[$slug][in_array(fcc_library_pilot_locale(),['sl','de','sr', 'sq', 'cnr', 'fr', 'es'],true)?'hr':fcc_library_pilot_locale()]??[]));
}
/** Present every article using the same editorial structure, without inventing product facts. */
function fcc_library_article_editorial(object $post): array {
    static $memo=[];
    $key=$post->blog_post_id.':'.fcc_library_pilot_locale();
    if(isset($memo[$key]))return $memo[$key];
    $hr=in_array(fcc_library_pilot_locale(),['hr','sl','de','sr', 'sq', 'cnr', 'fr', 'es'],true);
    $text=static function(string $html): string {
        $html=preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is','',$html);
        $html=preg_replace('~</li>~i','; ',$html);
        $html=preg_replace('~</(?:p|div|h[1-6]|section|span)>~i','$0 ',$html);
        $value=html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $value=preg_replace('/\s+/u',' ',trim($value));
        return preg_replace('/\s*[—–]\s*|\s+-\s+/u',', ',$value);
    };
    $excerpt=static function(string $value,int $limit=290): string {
        $sentences=preg_split('/(?<=[.!?])\s+(?=[\p{Lu}\d])/u',$value);
        $out='';foreach($sentences as $sentence){if($out && mb_strlen($out.' '.$sentence)>$limit)break;$out.=($out?' ':'').$sentence;if(mb_strlen($out)>=$limit)break;}
        return mb_strlen($out)>$limit+100?mb_substr($out,0,mb_strrpos(mb_substr($out,0,$limit),' ')).'…':$out;
    };
    $sections=fcc_library_pilot_sections($post->content);
    $features=[];$usage=[];$notes=[];$used=[];
    foreach($sections as $section) {
        $title=$text($section['title']);$body=$text($section['html']);
        if(!$body || preg_match('/\[(phone|name|email|referral)\]|Kako naručiti|How to order|So bestellst|Wie bestell|Jetzt bestellen|naruči proizvod|order online|nezavisni Forever|independent Forever/i',$title.' '.$body))continue;
        if(preg_match('/osetljiv|upozorenje|opomba|opozoril|občutljiv|napomen|upozoren|alergen|osjetljiv|nije preporu|warning|caution|allerg|important|safety|hinweis|warnung|vorsicht|wichtig|sicherheit/i',$title)) {$notes[]=$body;continue;}
        if(preg_match('/^Kako (uporab|se uporablja)|^Navodila|^Priporočena uporaba|^Način uporabe|^Kako (korist|se koristi)|^Način (primjen|uporab|priprem)|^Preporuč.*(uporab|korišten)|^Način upotrebe|^Preporučena upotreba|^Upotreba|^Dnevna up|^Jednostavna priprema|^Anwendung|^Verwendung|^Einnahme|^Zubereitung|^So verwendest|^So nutzt|^How to use|^Directions|^Suggested use|^How .*use|^How to prepare/i',$title)) {
            $usage[]=['title'=>$title,'body'=>$body];continue;
        }
        if(preg_match('/sestavin|vsebuje|Kaj dobiš|Kaj je v|embalaž|pakiranj|sastoj|formul|sadrži|Šta dobijaš|Šta je u|Što dobivaš|Što je u|baza|izvor|okus|tekstur|pakiranj|prakti|ingredient|formula|contain|flavour|texture|pack|format|source|zutaten|inhaltsstoff|enthält|geschmack|textur|packung/i',$title)) {
            if(preg_match('/detoks|detox|čišćenj[ea] organizma|alkaliz|liječi|cures?|treats? disease|sigurn[ie] rezultat|guaranteed result/i',$body))continue;
            $features[]=[$title,$excerpt($body)];$used[$title]=true;
        }
    }
    // Prefer exact factual sections over effect or outcome claims from legacy headings.
    $features=array_slice($features,0,3);
    if(count($features)<3)foreach($sections as $section){
        $title=$text($section['title']);$body=$text($section['html']);
        if(isset($used[$title])||!$body||preg_match('/\[|naru|order|napomen|warning|hinweis|warnung|bestell|heilung|heilt|djeluje|detox|čišćenje organizma|reset|liječi|cure|treat|rezultat|result/i',$title.' '.$body))continue;
        if(!preg_match('/Kdaj|Za koga|Kaj izbereš|razlikuje|rutino|Kada|Za koga|rutinu|rutini|razlikuje|prvi korak|Što biraš|When|Who|routine|different|first step/i',$title))continue;
        $features[]=[$title,$excerpt($body)];$used[$title]=true;if(count($features)===3)break;
    }
    $fallbacks=[
        [$hr?'Ukratko o proizvodu':'The product at a glance',$excerpt($text($post->description))],
        [$hr?'Ponuda u tvojoj zemlji':'Your country offer',$hr?'Odaberi zemlju u kojoj naručuješ. Uz cijenu vidiš točno pakiranje ili varijantu iz tamošnje Forever trgovine.':'Choose the country where you order. The price shows the exact pack or option in that country’s Forever shop.'],
        [$hr?'Tu smo za tvoja pitanja':'A person you can ask',$hr?'Ako se dvoumiš oko odabira, javi se osobi koja ti je poslala ovaj proizvod. Zajedno možete proći ono što te zanima.':'If you have a question, contact the person who shared this product with you and go through it together.'],
    ];
    foreach($fallbacks as $fallback){if(count($features)>=3)break;$features[]=$fallback;}
    $routine=[];$routine_seen=[];
    foreach($usage as $u){
        $sentences=preg_split('/(?<=[.!?])\s+(?=[\p{Lu}\d])/u',$u['body']);
        foreach($sentences as $sentence){
            $sentence=trim($sentence);$identity=mb_strtolower($sentence);
            if(mb_strlen($sentence)<20 || isset($routine_seen[$identity]))continue;
            $routine_seen[$identity]=true;
            $titles=$hr?['Primjena','U svakodnevnoj uporabi','Dobro je znati']:['Application','In everyday use','Good to know'];
            $routine[]=[$titles[count($routine)],$sentence];
            if(count($routine)>=3)break 2;
        }
    }
    if(!$routine)$routine[]=[$hr?'Uputa za tvoje pakiranje':'Your pack’s directions',$hr?'U službenoj ponudi otvori upute za ovaj proizvod. Tamo provjeri način primjene za pakiranje koje si odabrao/la.':'Open the official offer and read the directions for the exact pack you choose.'];
    if(count($routine)<3)$routine[]=[$hr?'Prema lokalnoj deklaraciji':'Follow your local label',$hr?'Sastav i uputa mogu se razlikovati po zemljama. Za uporabu vrijedi deklaracija na tvojem pakiranju.':'Ingredients and directions can differ between countries. Follow the label on your pack.'];
    $result=['editorial'=>true,'headline'=>$hr?'Upoznaj ga malo bolje.':'Get to know it a little better.',
        'highlights'=>$features,'routine_title'=>$hr?'Jednostavno u tvojoj rutini.':'A place in your everyday routine.',
        'routine'=>$routine,'notes'=>$notes?implode("\n\n",$notes):($hr?'Potpuni sastav, način uporabe i napomene pronađi na deklaraciji i u službenoj ponudi za svoju zemlju.':'Find the complete ingredients, directions and notes on the label and in the official offer for your country.')];
    static $overrides;
    $overrides??=require APP_PATH.'config/fcc_library_product_details.php';
    $copyLocale=in_array(fcc_library_pilot_locale(),['sl','de','sr', 'sq', 'cnr', 'fr', 'es'],true)?'hr':fcc_library_pilot_locale();
    if(isset($overrides[$post->url][$copyLocale]))$result=array_merge($result,$overrides[$post->url][$copyLocale]);
    return $memo[$key]=in_array(fcc_library_pilot_locale(),['sl','de','sr', 'sq', 'cnr', 'fr', 'es'],true)?fcc_localize_copy($result):$result;
}
function fcc_library_pilot_rendering(bool $mark=false): bool {
    static $rendering=false;
    if($mark) $rendering=true;
    return $rendering;
}
function fcc_library_image_frame(string $name): string {
    static $frames;
    $frames??=json_decode(file_get_contents(APP_PATH.'config/fcc_product_image_frames.json'),true);
    $f=$frames[$name]??null;
    return $f?'--fl-art-ratio:'.(float)$f['ratio'].';--fl-art-width:'.(float)$f['width'].'%;--fl-art-left:'.(float)$f['left'].'%;--fl-art-top:'.(float)$f['top'].'%;':'';
}
function fcc_library_pilot_assets(): void {
    fcc_public_assets();
    if(is_logged_in() && !headers_sent()) header('Cache-Control: private, no-store');
    fcc_library_pilot_rendering(true);
    static $done=false;
    if($done) return;
    $done=true;
    \Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-library-pilot.css?v='.filemtime(ASSETS_PATH.'css/fcc-library-pilot.css').'">', 'head');
    \Altum\Event::add_content('<script defer src="'.ASSETS_FULL_URL.'js/fcc-library-pilot.js?v='.filemtime(ASSETS_PATH.'js/fcc-library-pilot.js').'"></script>', 'javascript');
}
/** One catalog query per request, including new articles added through the normal admin. */
function fcc_library_product_index(): array {
    static $indexes=[];
    $language=match(fcc_library_pilot_locale()) {'hr'=>'Hrvatski','sl'=>'Slovenščina','de'=>'Deutsch','sr'=>'Srpski','sq'=>'Shqip','cnr'=>'Crnogorski','fr'=>'Français','es'=>'Español',default=>'english'};
    if(isset($indexes[$language])) return $indexes[$language];
    $categories=[];
    foreach(db()->get('blog_posts_categories',null,['blog_posts_category_id','blog_posts_parent_id','url']) as $category) {
        if(!$category->blog_posts_parent_id) continue;
        $key=basename($category->url);
        $topic=['napitci'=>'drinks','regulacija-tezine'=>'weight','dodaci-prehrani'=>'supplement','pcelinji-proizvodi'=>'bee','osobna-njega'=>'personal','njega-koze'=>'care'][$key]??null;
        if($topic) $categories[(int)$category->blog_posts_category_id]=$topic;
    }
    $posts=[];
    foreach(db()->where('language',$language)->where('is_published',1)->orderBy('title','ASC')->get('blog_posts') as $post) {
        if(!isset($categories[(int)$post->blog_posts_category_id]) || $post->url==='moje-iskustvo-c9-forever') continue;
        $post->library_topic=fcc_library_pilot_config()['products'][$post->url]['topic']??$categories[(int)$post->blog_posts_category_id];
        $posts[$post->url]=$post;
    }
    return $indexes[$language]=$posts;
}
function fcc_library_pilot_posts(): array {
    return array_values(fcc_library_product_index());
}
function fcc_library_pilot_url(string $path, ?string $ref=null, array $extra=[]): string {
    $params=$extra;
    if($ref!==null && $ref!=='') $params['ref']=$ref;
    return url($path).($params?'?'.http_build_query($params,'','&',PHP_QUERY_RFC3986):'');
}
function fcc_library_pilot_message(string $title,string $url): string {
    return sprintf(fcc_lp_t('message'),$title)."\n\n".$url;
}
/** Labels only. The controller and Link helper remain the sole routing authority. */
function fcc_library_pilot_offer_market(object $data): ?string {
    $market=\Altum\Link::resolve_forever_market_country_code($data->resolved_market_country_code??null);
    return $market;
}
function fcc_library_pilot_country_name(?string $country): string {
    $code=strtoupper((string)$country);
    if(!$code) return fcc_lp_t('market_unknown');
    if(class_exists('Locale')) {
        $name=\Locale::getDisplayRegion('und_'.$code,fcc_intl_locale(fcc_library_pilot_locale()));
        if($name) return $name;
    }
    $names=['hr'=>'Hrvatska','si'=>'Slovenija','de'=>'Njemačka','gb'=>'Ujedinjeno Kraljevstvo','us'=>'SAD','ba'=>'Bosna i Hercegovina','al'=>'Albanija','rs'=>'Srbija','at'=>'Austrija'];
    return fcc_library_pilot_locale()==='hr'?($names[strtolower($code)]??$code):$code;
}
/** Reformat existing body into disclosures without removing any original content. */
function fcc_library_pilot_sections(string $html): array {
    $html=preg_replace('~<(\/?)(h1)(\b[^>]*)>~i','<$1h2$3>',$html);
    if(!class_exists('DOMDocument')) return [['title'=>fcc_lp_t('details'),'html'=>$html]];
    $dom=new DOMDocument(); $old=libxml_use_internal_errors(true);
    $loaded=$dom->loadHTML('<?xml encoding="utf-8" ?><div id="fcc-pilot-source">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    $root=$loaded?(new DOMXPath($dom))->query('//*[@id="fcc-pilot-source"]')->item(0):null;
    $sections=[]; $current=['title'=>fcc_lp_t('details'),'html'=>''];
    if($root) {
        // Flatten layout containers around headings, retaining all leaf content in source order.
        // Lists, tables, links and media remain intact. No article or market data is rewritten.
        $visit=function($node) use (&$visit,&$sections,&$current,$dom) {
            if(in_array(strtolower($node->nodeName),['h2','h3']) && trim($node->textContent)!=='') {
                // Keep a child heading under an otherwise empty parent heading.
                if(trim($current['html'])==='' && $current['title']!==fcc_lp_t('details')) {
                    $current['html'].=$dom->saveHTML($node);
                    return;
                }
                if(trim($current['html'])!==''||$current['title']!==fcc_lp_t('details')) $sections[]=$current;
                $current=['title'=>trim($node->textContent),'html'=>'','id'=>$node->getAttribute('id')];
            } elseif($node instanceof DOMElement && in_array(strtolower($node->nodeName),['div','article','section'])
                && ($node->getElementsByTagName('h2')->length||$node->getElementsByTagName('h3')->length)) {
                foreach($node->childNodes as $child) $visit($child);
            } else $current['html'].=$dom->saveHTML($node);
        };
        foreach($root->childNodes as $node) $visit($node);
        if(trim($current['html'])!==''||$current['title']!==fcc_lp_t('details')) $sections[]=$current;
    }
    libxml_clear_errors();libxml_use_internal_errors($old);
    return $sections?:[['title'=>fcc_lp_t('details'),'html'=>$html]];
}
