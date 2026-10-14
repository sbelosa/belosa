<?php
/** Public product facts only. Never request a personalised cart, checkout or member price. */
final class FccShopCatalog {
    private static array $requestSnapshots=[];
    const MARKETS=['mk'=>'mkd','hr'=>'hrv','si'=>'svn','rs'=>'scg','at'=>'aut','au'=>'aus','ca'=>'can','de'=>'deu','ie'=>'irl','lu'=>'lux','nl'=>'nld','no'=>'nor','pl'=>'pol','se'=>'swe','gb'=>'gbr','us'=>'usa','qa'=>'qat','ch'=>'che','ae'=>'are'];
    const HOSTS=['foreverliving.com','www.foreverliving.com','www.flpshop.ba','www.foreveralbania.com','www.flpshop.rs','flpshop.rs','webshop.forever.si'];
    const CURRENCIES=['mk'=>'MKD','hr'=>'EUR','si'=>'EUR','ba'=>'BAM','al'=>'ALL','rs'=>'RSD','at'=>'EUR','au'=>'AUD','ca'=>'CAD','de'=>'EUR','ie'=>'EUR','lu'=>'EUR','nl'=>'EUR','no'=>'NOK','pl'=>'PLN','se'=>'SEK','gb'=>'GBP','us'=>'USD','qa'=>'QAR','ch'=>'CHF','ae'=>'AED'];
    const TTL=21600;
    const LAST_PRICE_TTL=259200;
    public static function identities(): array {
        static $data;
        if(getenv('FCC_LOCAL')!=='1' && getenv('FCC_MARKET_POLICY_ENABLED')!=='1'
            && !(function_exists('fcc_library_pilot_enabled') && fcc_library_pilot_enabled())) return [];
        $file=dirname(__DIR__).'/config/fcc_product_identities.json';
        return $data ??= (is_file($file)?json_decode(file_get_contents($file),true):[]);
    }
    public static function identityText(string $value): string {
        $value=html_entity_decode(strip_tags($value),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $value=str_replace(['™','®','©'],'',$value);
        $value=Normalizer::normalize(mb_strtolower($value,'UTF-8'),Normalizer::FORM_KD);
        return preg_replace('/[^\p{L}\p{N}]/u','',preg_replace('/\p{Mn}/u','',$value));
    }
    /** Reviewed article family, exact market and variant. Similar SKU digits are insufficient. */
    public static function matchesIdentity(string $url,array $data): bool {
        $expected=self::identities()['offers'][self::clean($url)]??null;
        if(!$expected) return true;
        if(($data['market']??'')!==$expected['market']) return false;
        $familyCard=!empty($data['selection_required']) && empty($data['sku'])
            && in_array(parse_url($url,PHP_URL_HOST),['foreverliving.com','www.foreverliving.com'],true)
            && !preg_match('~/(\d+[A-Za-z]?)-~',(string)parse_url($url,PHP_URL_PATH));
        /* Custom code: FC-2026-10-14: observed schema family codes are exact aliases of the reviewed configurator. */
        $schemaFamily=!empty($expected['selection_required'])
            && in_array((string)($data['sku']??''),$expected['schema_skus']??[],true)
            && ($data['default_sku']??null)===$expected['sku'];
        if(!$familyCard && !$schemaFamily && strtolower(ltrim((string)($data['sku']??''),'0'))!==strtolower(ltrim($expected['sku'],'0'))) return false;
        /* /Custom code: FC-2026-10-14 */
        if(empty($data['name'])) return false;
        foreach(['name','heading'] as $field) {
            if(empty($data[$field]))continue;
            if(preg_match('/\b(tester|sample|promo)\b/iu',$data[$field]))return false;
            $text=self::identityText($data[$field]);$matched=false;
            if(isset($expected['names'])) {
                if(!in_array($text,$expected['names'],true))return false;
                continue;
            }
            foreach($expected['terms'] as $term)if($term!=='' && str_contains($text,$term)){$matched=true;break;}
            if(!$matched)return false;
        }
        return true;
    }
    public static function articleIdentityMatches(int $postId,string $market,string $url): bool {
        $posts=self::identities()['posts'];
        $identity=$posts[(string)$postId]??null;
        // Published articles may have different IDs after a catalogue import or translation.
        // Resolve the reviewed product slug in every supported language, with the same exact market URL.
        if(!$identity && function_exists('db')) {
            static $translated=[];
            if(!array_key_exists($postId,$translated)) {
                $translated[$postId]=null;
                $post=db()->where('blog_post_id',$postId)->where('is_published',1)->getOne('blog_posts',['url','language']);
                if($post && in_array($post->language,['Hrvatski','Slovenščina','english','English','Deutsch','Español','Srpski','Shqip','Crnogorski','Français','Svenska','Magyar','Polski','Română'],true)) {
                    foreach($posts as $candidate) {
                        if(($candidate['family']??null)===$post->url) {$translated[$postId]=$candidate;break;}
                    }
                }
            }
            $identity=$translated[$postId];
        }
        /* Custom code: FC-2026-10-14: an article cannot reuse another formula's reviewed offer. */
        $offer=self::identities()['offers'][self::clean($url)]??null;
        return ($identity['markets'][$market]??null)===self::clean($url)
            && $offer && $offer['family']===($identity['family']??null) && $offer['market']===$market;
        /* /Custom code: FC-2026-10-14 */
    }
    public static function currencyAllowed(string $market,string $currency): bool {
        // The official Albanian storefront also publishes its guest offers in EUR.
        return $currency===(self::CURRENCIES[$market]??null) || ($market==='al' && $currency==='EUR');
    }
    /* Custom code: FC-2026-10-14: one explicit local price with valid thousands grouping. */
    private static function publicAmount(string $raw,string $currency): ?string {
        if($currency==='')return null;
        $codes=implode('|',array_unique(array_values(self::CURRENCIES)));
        preg_match_all('/(?<![A-Za-z])(?:'.$codes.')(?![A-Za-z])/iu',$raw,$tags);
        $seen=array_map('strtoupper',$tags[0]);
        foreach(['US'=>'USD','CA'=>'CAD','AU'=>'AUD'] as $prefix=>$code)
            if(preg_match('/(?<![A-Za-z])'.$prefix.'\$/iu',$raw))$seen[]=$code;
        if(array_diff($seen,[$currency]))return null;
        $symbol=['EUR'=>'€','GBP'=>'£','BAM'=>'KM','NOK'=>'kr','SEK'=>'kr','PLN'=>'zł'][$currency]??$currency;
        if(!(in_array($currency,$seen,true) || str_contains($raw,$symbol)
            || ($currency==='CHF' && str_contains($raw,'Fr.'))
            || (in_array($currency,['USD','CAD','AUD'],true) && str_contains($raw,'$'))))return null;
        if(preg_match_all('/[0-9][0-9.,\s]*/u',$raw,$numbers)!==1)return null;
        $number=preg_replace('/^\s+|\s+$/u','',$numbers[0][0]);
        $space='[\x{20}\x{A0}\x{202F}]';
        if(preg_match('/^(?:[0-9]+|[0-9]{1,3}(?:'.$space.'[0-9]{3})+)$/u',$number)) {
            $amount=preg_replace('/\s/u','',$number);
        }elseif(preg_match('/^(?:[0-9]+|[0-9]{1,3}(?:'.$space.'[0-9]{3})+|[0-9]{1,3}(?:\.[0-9]{3})+),[0-9]{2}$/u',$number)) {
            $amount=str_replace(',','.',str_replace('.','',preg_replace('/\s/u','',$number)));
        }elseif(preg_match('/^(?:[0-9]+|[0-9]{1,3}(?:'.$space.'[0-9]{3})+|[0-9]{1,3}(?:,[0-9]{3})+)\.[0-9]{2}$/u',$number)) {
            $amount=str_replace(',','',preg_replace('/\s/u','',$number));
        }else return null;
        return is_numeric($amount) && (float)$amount>0 && (float)$amount<100000?$amount:null;
    }
    /* /Custom code: FC-2026-10-14 */
    public static function clean(string $url): string {
        $p=parse_url(html_entity_decode(trim($url),ENT_QUOTES|ENT_HTML5));
        if(!$p || strtolower($p['scheme']??'')!=='https' || !in_array(strtolower($p['host']??''),self::HOSTS,true) || isset($p['user']) || isset($p['pass']) || isset($p['port'])) return '';
        $path=$p['path']??'/';
        if(preg_match('~(?:^|/)\.\.?(/|$)|%2e|%2f|%5c|[\\\\\r\n]~i',$path)) return '';
        // Product identity is in the path on all supported stores. Remove all visitor parameters.
        return 'https://'.strtolower($p['host']).$path;
    }
    public static function market(string $url): ?string {
        $host=strtolower((string)parse_url($url,PHP_URL_HOST));
        if(in_array($host,['foreverliving.com','www.foreverliving.com'],true) && preg_match('~^/(?:shop|join)/([a-z]{3})/~i',(string)parse_url($url,PHP_URL_PATH),$m)) return array_search(strtolower($m[1]),self::MARKETS,true)?:null;
        return ['www.flpshop.ba'=>'ba','www.foreveralbania.com'=>'al','www.flpshop.rs'=>'rs','flpshop.rs'=>'rs','webshop.forever.si'=>'si'][$host]??null;
    }
    public static function directory(): string { return dirname(__DIR__,2).'/local/shop-catalog/'; }
    public static function key(string $url): string { return hash('sha256',self::clean($url)); }
    public static function cached(string $url): ?array {
        if(!self::clean($url)) return null;
        $key=self::key($url);
        if(PHP_SAPI!=='cli' && array_key_exists($key,self::$requestSnapshots))return self::$requestSnapshots[$key];
        $file=self::directory().$key.'.json';
        $data=is_file($file)?json_decode((string)file_get_contents($file),true):null;
        if(PHP_SAPI!=='cli')self::$requestSnapshots[$key]=$data;
        return $data;
    }
    public static function price(string $url,string $market,bool $allowLastConfirmed=false): ?array {
        $reviewed_identity=self::identities()['offers'][self::clean($url)]??[];
        if(($reviewed_identity['selection_required']??false)===true)return null;
        $d=self::cached($url);
        $checked=strtotime($d['price_checked_at']??$d['checked_at']??'1970-01-01');
        if(!$d || ($d['status']??'')!=='verified_product' || ($d['market']??'')!==$market || !self::matchesIdentity($url,$d) || !self::currencyAllowed($market,$d['currency']??'') || !is_numeric($d['price']??null) || (float)$d['price']<=0 || !$checked || time()-$checked>($allowLastConfirmed?self::LAST_PRICE_TTL:self::TTL) || $checked>time()+60) return null;
        $d['checked_at']=gmdate(DATE_ATOM,$checked);
        $d['is_last_confirmed']=time()-$checked>self::TTL;
        return $d;
    }
    /** Transport failures and incomplete renders are not evidence that an offer disappeared. */
    public static function record(string $url,array $data): void {
        if(($data['status']??'')==='verified_product' && !self::matchesIdentity($url,$data)) {
            $data['status']='article_identity_mismatch';$data['price']=null;$data['currency']=null;
        }
        $old=self::cached($url);
        if($old && strtotime($data['checked_at']??'1970-01-01')<strtotime($old['checked_at']??'1970-01-01')
            && ((($old['status']??'')==='verified_product' && (!empty($old['price']) || empty($data['price']))) || in_array($old['http']??0,[404,410],true)))return;
        $transient=in_array($data['status']??'',['fetch_failed','no_product_data','unverified'],true)
            || (($data['status']??'')==='http_error' && !in_array($data['http']??0,[404,410],true));
        if($old && ($old['status']??'')==='verified_product' && $transient) {
            $old['last_attempt_at']=$data['checked_at']??gmdate(DATE_ATOM);
            $old['last_attempt_status']=$data['status'];
            self::save($url,$old);return;
        }
        if(($data['status']??'')==='verified_product') {
            if(!empty($data['price'])) $data['price_checked_at']=$data['checked_at'];
            if(!empty($data['availability']))$data['availability_checked_at']=$data['checked_at'];
            if($old && ($old['status']??'')==='verified_product' && self::matchesIdentity($url,$old) && ($old['sku']??'')===($data['sku']??'')) {
                foreach(['image','pack','heading','catalog_source'] as $field) if(empty($data[$field]) && !empty($old[$field]))$data[$field]=$old[$field];
                $stockTime=$old['availability_checked_at']??$old['checked_at'];
                if(empty($data['availability']) && !empty($old['availability']) && time()-strtotime($stockTime)<=self::LAST_PRICE_TTL) {
                    $data['availability']=$old['availability'];$data['availability_checked_at']=$stockTime;
                }
                // An unpriced family card cannot refresh the time of a previously priced variant.
                if(empty($data['price']) && empty($data['selection_required']) && self::price($url,$data['market'],true)) {
                    foreach(['price','currency'] as $field)$data[$field]=$old[$field];
                    $data['price_checked_at']=$old['price_checked_at']??$old['checked_at'];
                }
            }
        }
        self::save($url,$data);
    }
    public static function refreshForVisitor(string $url): ?array {
        if(!self::clean($url))return null;
        $dir=self::directory().'refresh-locks/';if(!is_dir($dir))mkdir($dir,0775,true);
        $lock=fopen($dir.self::key($url).'.lock','c+');
        if(!$lock || !flock($lock,LOCK_EX|LOCK_NB)){if($lock)fclose($lock);return self::cached($url);}
        $last=(int)stream_get_contents($lock);
        if(time()-$last>=300 && !self::price($url,(string)self::market($url))) {
            // Bound public refresh work across different products as well as per URL.
            $slot=null;
            for($i=0;$i<2;$i++) {
                $candidate=fopen($dir.'slot-'.$i.'.lock','c+');
                if($candidate && flock($candidate,LOCK_EX|LOCK_NB)){$slot=$candidate;break;}
                if($candidate)fclose($candidate);
            }
            if($slot)try {
                rewind($lock);ftruncate($lock,0);fwrite($lock,(string)time());fflush($lock);
                self::record($url,self::fetch($url,5));
            } finally {flock($slot,LOCK_UN);fclose($slot);}
        }
        flock($lock,LOCK_UN);fclose($lock);return self::cached($url);
    }
    public static function save(string $url,array $data): void {
        $dir=self::directory();
        if(!is_dir($dir)) mkdir($dir,0775,true);
        if(!is_file($dir.'.htaccess')) file_put_contents($dir.'.htaccess',"Require all denied\n");
        $tmp=tempnam($dir,'.refresh-');
        file_put_contents($tmp,json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);
        chmod($tmp,0664);rename($tmp,$dir.self::key($url).'.json');
        unset(self::$requestSnapshots[self::key($url)]);
    }
    public static function saveListing(array $data): void {
        $url=$data['source'];$old=self::cached($url);
        if(!self::matchesIdentity($url,$data)) {self::record($url,$data);return;}
        if(($data['price_kind']??'')==='catalog' && $old && ($old['price_kind']??'')!=='catalog' && self::price($url,$data['market'])) {
            if(!empty($data['availability']) && strtotime($data['checked_at'])>=strtotime($old['availability_checked_at']??$old['checked_at'])) {
                $old['availability']=$data['availability'];$old['availability_checked_at']=$data['checked_at'];self::save($url,$old);
            }
            return;
        }
        // A family card cannot replace the verified price of its default variant.
        if(empty($data['price']) && $old && ($old['status']??'')==='verified_product' && self::matchesIdentity($url,$old) && !empty($old['price'])) {
            $old['last_offer_checked_at']=$data['checked_at'];
            if(!empty($data['availability']) && strtotime($data['checked_at'])>=strtotime($old['availability_checked_at']??$old['checked_at'])) {
                $old['availability']=$data['availability'];$old['availability_checked_at']=$data['checked_at'];
            }
            self::save($url,$old);return;
        }
        self::record($url,$data);
    }
    /** Exact product cards on a country catalog are also authoritative public offers. */
    public static function parseListing(string $html,string $url): array {
        $market=self::market($url);
        if(!$market || !in_array(parse_url($url,PHP_URL_HOST),['foreverliving.com','www.foreverliving.com'],true)) return [];
        $dom=new DOMDocument();$old=libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);libxml_clear_errors();libxml_use_internal_errors($old);
        $xp=new DOMXPath($dom);$result=[];
        $class=static fn($name)=>'contains(concat(" ",normalize-space(@class)," ")," '.$name.' ")';
        foreach($xp->query('//*['.$class('product_card-tile').']') as $card) {
            $a=$xp->query('.//a['.$class('product__image-container').'][@href]',$card)->item(0);
            $title=$xp->query('.//*['.$class('product-title').']',$card)->item(0);
            if(!$a || !$title) continue;
            $href=$a->getAttribute('href');if(str_starts_with($href,'/')) $href='https://foreverliving.com'.$href;
            $href=self::clean($href);
            if(!$href || self::market($href)!==$market || !str_contains($href,'/products/')) continue;
            $price_node=$xp->query('.//*['.$class('product-meta-price').']//*['.$class('price').']',$card);
            $price=null;$currency=self::CURRENCIES[$market]??'';
            if($price_node->length===1) {
                $node=$price_node->item(0)->cloneNode(true);
                foreach(iterator_to_array($node->getElementsByTagName('label')) as $label) $label->parentNode->removeChild($label);
                $raw=trim($node->textContent);
                /* Custom code: FC-2026-10-14: use the same guarded public amount as product detail pages. */
                $price=self::publicAmount($raw,$currency);
                /* /Custom code: FC-2026-10-14 */
            }
            preg_match('~/(\d+[A-Za-z]?)-~',urldecode($href),$sku);
            // A family card price is displayed as a catalog price, not a selected variant.
            $pack=$xp->query('.//*['.$class('item-quantity').']',$card)->item(0);
            $photo=$a->getElementsByTagName('img')->item(0);$photo=$photo?$photo->getAttribute('src'):'';
            if(parse_url($photo,PHP_URL_SCHEME)!=='https' || parse_url($photo,PHP_URL_HOST)!=='cdn.foreverliving.com')$photo='';
            $result[]=['source'=>$href,'final_url'=>$href,'catalog_source'=>$url,'evidence'=>'official_country_product_card','checked_at'=>gmdate(DATE_ATOM),'http'=>200,'market'=>$market,'status'=>'verified_product','name'=>trim($title->textContent),'sku'=>$sku[1]??'','pack'=>$pack?trim($pack->textContent):'','image'=>$photo,'price'=>$price,'currency'=>$price?$currency:null,'price_kind'=>empty($sku[1])?'catalog':'exact','selection_required'=>empty($sku[1])];
            if(preg_match('/Rasprodano|Razprodano|Out\s+of\s+stock|Sold\s+out|Ausverkauft|Nicht\s+auf\s+Lager|Brak\s+w\s+magazynie/iu',$card->textContent))
                $result[count($result)-1]['availability']='https://schema.org/OutOfStock';
        }
        return $result;
    }
    public static function fetchListing(string $url): array {
        $parts=parse_url($url);parse_str($parts['query']??'',$query);
        if(!self::clean($url) || !self::market($url) || !in_array($parts['host']??'',['foreverliving.com','www.foreverliving.com'],true) || array_diff(array_keys($query),['page','total'])) return [];
        $ch=curl_init($url);$body='';
        curl_setopt_array($ch,[CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>22,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_ENCODING=>'',CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_USERAGENT=>'Googlebot',CURLOPT_WRITEFUNCTION=>static function($ch,$chunk)use(&$body){if(strlen($body)+strlen($chunk)>6500000)return 0;$body.=$chunk;return strlen($chunk);}]);
        $ok=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
        return $http===200 && $body!==''?self::parseListing($body,$url):[];
    }
    public static function fetchListings(array $urls): array {
        $multi=curl_multi_init();$handles=[];$bodies=[];
        foreach(array_slice($urls,0,6) as $url) {
            $p=parse_url($url);parse_str($p['query']??'',$q);
            if(!self::clean($url) || !self::market($url) || !in_array($p['host'],['foreverliving.com','www.foreverliving.com'],true) || array_diff(array_keys($q),['page','total']))continue;
            $bodies[$url]='';$ch=curl_init($url);
            curl_setopt_array($ch,[CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>22,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_ENCODING=>'',CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_USERAGENT=>'Googlebot',CURLOPT_WRITEFUNCTION=>static function($ch,$chunk)use(&$bodies,$url){if(strlen($bodies[$url])+strlen($chunk)>6500000)return 0;$bodies[$url].=$chunk;return strlen($chunk);}]);
            $handles[$url]=$ch;curl_multi_add_handle($multi,$ch);
        }
        do {$status=curl_multi_exec($multi,$running);if($running)curl_multi_select($multi,1);}while($running && $status===CURLM_OK);
        $rows=[];
        foreach($handles as $url=>$ch) {$rows[$url]=curl_getinfo($ch,CURLINFO_RESPONSE_CODE)===200 && $bodies[$url]!==''?self::parseListing($bodies[$url],$url):[];curl_multi_remove_handle($multi,$ch);curl_close($ch);}
        curl_multi_close($multi);return $rows;
    }
    public static function requestRefresh(string $url): void {
        $url=self::clean($url);if(!$url || getenv('FCC_LOCAL')!=='1')return;
        $cached=self::cached($url);
        if($cached && !empty($cached['price']) && time()-strtotime($cached['checked_at']??'1970-01-01')<1800)return;
        $dir=self::directory().'queue/';if(!is_dir($dir))mkdir($dir,0775,true);
        $path=$dir.self::key($url).'.json';
        if(!is_file($path))file_put_contents($path,json_encode(['url'=>$url,'requested_at'=>gmdate(DATE_ATOM)]),LOCK_EX);
    }
    public static function parse(string $html,string $source,string $final,int $http): array {
        $out=['source'=>self::clean($source),'final_url'=>self::clean($final),'checked_at'=>gmdate(DATE_ATOM),'http'=>$http,'market'=>self::market($final),'status'=>'unverified','price'=>null,'currency'=>null];
        if($http!==200) { $out['status']='http_error';return $out; }
        if(!self::clean($final) || self::market($source)!==self::market($final)) { $out['status']='wrong_market';return $out; }
        $dom=new DOMDocument();$old=libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);libxml_clear_errors();libxml_use_internal_errors($old);
        $xp=new DOMXPath($dom);$products=[];
        $walk=function($value) use (&$walk,&$products) {
            if(!is_array($value)) return;
            if(in_array('Product',(array)($value['@type']??[]),true)) {$products[]=$value;return;}
            foreach($value as $child) if(is_array($child)) $walk($child);
        };
        foreach($xp->query('//script[@type="application/ld+json"]') as $node) $walk(json_decode($node->textContent,true));
        $h1=$xp->query('//h1')->item(0);$out['heading']=$h1?trim($h1->textContent):'';
        // Some official product pages omit JSON-LD but render a single labelled price.
        // Accept only the observed public product layout, with a matching item number.
        /* Custom code: FC-2026-10-14: reviewed family pages can expose their default item only in the public DOM. */
        $reviewed_identity=self::identities()['offers'][self::clean($source)]??[];
        $numbered=preg_match('~/(\d+[A-Za-z]?)-[^/]+/?$~',urldecode((string)parse_url($source,PHP_URL_PATH)),$wanted)===1;
        if(!$numbered && !empty($reviewed_identity['selection_required']) && preg_match('/^[0-9]+[A-Za-z]?$/D',$reviewed_identity['sku']??''))$wanted=[null,$reviewed_identity['sku']];
        if(count($products)===0 && isset($wanted[1])) {
        /* /Custom code: FC-2026-10-14 */
            $title=$xp->query('//h1[contains(concat(" ",normalize-space(@class)," ")," product-title ")]');
            $sku=$xp->query('//*[contains(concat(" ",normalize-space(@class)," ")," item-number ")]/span[last()]');
            $prices=$xp->query('//*[contains(concat(" ",normalize-space(@class)," ")," prod-price ")]/span[contains(concat(" ",normalize-space(@class)," ")," price ")]');
            /* Custom code: FC-2026-10-14: the retail label shares the price class, but contains no amount. */
            $amounts=array_values(array_filter(iterator_to_array($prices),static fn($node)=>preg_match('/[0-9]/u',$node->textContent)));
            if($title->length===1 && $sku->length===1 && strcasecmp(trim($sku->item(0)->textContent),$wanted[1])===0 && count($amounts)===1) {
                $raw=trim($amounts[0]->textContent);$cc=self::CURRENCIES[$out['market']]??'';
            /* /Custom code: FC-2026-10-14 */
                /* Custom code: FC-2026-10-14: country currency and unambiguous public amount. */
                $amount=self::publicAmount($raw,$cc);
                if($amount!==null) {
                /* /Custom code: FC-2026-10-14 */
                    $products[]=['@type'=>'Product','name'=>trim($title->item(0)->textContent),'sku'=>trim($sku->item(0)->textContent),'offers'=>['@type'=>'Offer','url'=>$final,'price'=>$amount,'priceCurrency'=>$cc]];
                    $out['evidence']='public_product_dom';
                }
            }
        }
        // Lists and pages with ambiguous product objects cannot supply a checkout price.
        if(count($products)!==1) {$out['status']=count($products)>1?'ambiguous_products':'no_product_data';return $out;}
        $p=$products[0];$out['name']=trim(strip_tags($p['name']??''));$out['sku']=(string)($p['sku']??$p['mpn']??'');
        $out['description']=trim(strip_tags($p['description']??''));
        if($out['name']==='' || !$h1) return $out;
        /* Custom code: FC-2026-10-14: a schema family alias also needs its exact visible default item. */
        if(!empty($reviewed_identity['selection_required']) && in_array($out['sku'],$reviewed_identity['schema_skus']??[],true)) {
            $visible=$xp->query('//*[contains(concat(" ",normalize-space(@class)," ")," item-number ")]/span[last()]');
            if($visible->length!==1 || trim($visible->item(0)->textContent)!==$reviewed_identity['sku']){$out['status']='sku_mismatch';return $out;}
            $out['default_sku']=$reviewed_identity['sku'];
        }
        /* /Custom code: FC-2026-10-14 */
        $path=urldecode((string)parse_url($source,PHP_URL_PATH));
        if(preg_match('~/(\d+[A-Za-z]?)-[^/]+/?$~',$path,$m) && strcasecmp($m[1],$out['sku'])!==0) {$out['status']='sku_mismatch';return $out;}
        $offers=$p['offers']??[];
        $selection=false;
        if(isset($offers[0])) {$selection=count($offers)!==1;$offers=$offers[0];}
        if(($offers['@type']??'')==='AggregateOffer')$selection=true;
        $offer_url=self::clean($offers['url']??$p['url']??'');
        if(!$offer_url || self::market($offer_url)!==self::market($source)) {$out['status']='offer_market_mismatch';return $out;}
        // URL or SKU must identify the requested product, not a related recommendation.
        if(strcasecmp(rtrim((string)parse_url($offer_url,PHP_URL_PATH),'/'),rtrim((string)parse_url($final,PHP_URL_PATH),'/'))!==0 && !(!empty($m[1]) && strcasecmp($m[1],$out['sku'])===0)) {$out['status']='offer_identity_mismatch';return $out;}
        $out['status']='verified_product';$out['availability']=$offers['availability']??null;
        // Reviewed family configurators must not present a selected variant price as a package price.
        if(!empty($reviewed_identity['selection_required']) && ($reviewed_identity['sku']??'')!==''
            && self::matchesIdentity($source,$out)) {
            $out['selection_required']=true;$out['price']=null;$out['currency']=null;return $out;
        }
        if($selection){$out['selection_required']=true;return $out;}
        $price=$offers['price']??null;$currency=strtoupper($offers['priceCurrency']??'');
        if(is_numeric($price) && (float)$price>0 && (float)$price<100000 && self::currencyAllowed($out['market']??'',$currency)) {$out['price']=(string)$price;$out['currency']=$currency;}
        return $out;
    }
    public static function fetch(string $url,int $timeout=22): array {
        $source=self::clean($url);$current=$source;$body='';$http=0;
        if(!$source) return ['source'=>$url,'status'=>'invalid_url','checked_at'=>gmdate(DATE_ATOM)];
        for($i=0;$i<4;$i++) {
            $location=null;$bytes=0;$body='';$enough=false;$ch=curl_init($current);
            curl_setopt_array($ch,[CURLOPT_CONNECTTIMEOUT=>min(6,$timeout),CURLOPT_TIMEOUT=>(getenv('FCC_SHOP_AUDIT_TIMEOUT')==='12'?12:$timeout),CURLOPT_FOLLOWLOCATION=>false,CURLOPT_ENCODING=>'',CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4,
                // The public crawler render exposes the same guest Product JSON-LD without a login.
                CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                CURLOPT_HEADERFUNCTION=>static function($ch,$line) use (&$location) {if(stripos($line,'Location:')===0) $location=trim(substr($line,9));return strlen($line);},
                CURLOPT_WRITEFUNCTION=>static function($ch,$chunk) use (&$body,&$bytes,&$enough) {$bytes+=strlen($chunk);if($bytes>6500000)return 0;$body.=$chunk; $schemaStart=strpos($body,'application/ld+json');$productPos=$schemaStart!==false?strpos($body,'"Product"',$schemaStart):false;if(str_contains($body,'</app-product-detail>') || (str_contains($body,'</h1>') && $productPos!==false && strpos($body,'</script>',$productPos)!==false)) {$enough=true;return 0;}return strlen($chunk);},
            ]);
            $ok=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_errno($ch);curl_close($ch);
            if($ok===false && !$enough && !($http===200 && $body!=='')) return ['source'=>$source,'final_url'=>$current,'checked_at'=>gmdate(DATE_ATOM),'status'=>'fetch_failed','http'=>$http,'error_code'=>$error,'price'=>null];
            if(in_array($http,[301,302,303,307,308],true) && $location) {
                if(str_starts_with($location,'/')) $location='https://'.parse_url($current,PHP_URL_HOST).$location;
                $next=self::clean($location);
                if(!$next || self::market($next)!==self::market($source)) return ['source'=>$source,'final_url'=>$next,'checked_at'=>gmdate(DATE_ATOM),'status'=>'wrong_market_redirect','http'=>$http,'price'=>null];
                $current=$next;continue;
            }
            break;
        }
        return self::parse($body,$source,$current,$http);
    }
    /** Bounded concurrency keeps the catalog refresh cycle shorter than the price TTL. */
    public static function fetchMany(array $urls,int $timeout=30): array {
        $multi=curl_multi_init();$handles=[];$bodies=[];$enough=[];$results=[];
        foreach(array_slice(array_unique($urls),0,12) as $url){
            if(!self::clean($url))continue;
            $bodies[$url]='';$enough[$url]=false;$ch=curl_init(self::clean($url));
            curl_setopt_array($ch,[CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>$timeout,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_ENCODING=>'',CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_IPRESOLVE=>CURL_IPRESOLVE_V4,
                CURLOPT_USERAGENT=>'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                CURLOPT_WRITEFUNCTION=>static function($ch,$chunk)use(&$bodies,&$enough,$url){
                    if(strlen($bodies[$url])+strlen($chunk)>6500000)return 0;
                    $bodies[$url].=$chunk;$body=$bodies[$url];$schema=strpos($body,'application/ld+json');$product=$schema!==false?strpos($body,'"Product"',$schema):false;
                    if(str_contains($body,'</app-product-detail>') || (str_contains($body,'</h1>') && $product!==false && strpos($body,'</script>',$product)!==false)){$enough[$url]=true;return 0;}
                    return strlen($chunk);
                }]);
            $handles[$url]=$ch;curl_multi_add_handle($multi,$ch);
        }
        do{$status=curl_multi_exec($multi,$running);if($running)curl_multi_select($multi,1);}while($running && $status===CURLM_OK);
        $completed=[];while($info=curl_multi_info_read($multi))$completed[spl_object_id($info['handle'])]=$info['result'];
        $redirects=[];
        foreach($handles as $url=>$ch){
            $http=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$errno=$completed[spl_object_id($ch)]??curl_errno($ch);
            if(in_array($http,[301,302,303,307,308],true))$redirects[]=$url;
            elseif(($errno && !$enough[$url] && !($http===200 && $bodies[$url]!=='')) || !$http)$results[$url]=['source'=>$url,'checked_at'=>gmdate(DATE_ATOM),'status'=>'fetch_failed','http'=>$http,'error_code'=>$errno,'price'=>null];
            else $results[$url]=self::parse($bodies[$url],$url,$url,$http);
            curl_multi_remove_handle($multi,$ch);curl_close($ch);
        }
        curl_multi_close($multi);
        foreach($redirects as $url)$results[$url]=self::fetch($url,5);
        return $results;
    }
}
