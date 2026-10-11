<?php
defined('ALTUMCODE') || die();
function fcc_business_countries(): array {return ['hr'=>'Hrvatska','si'=>'Slovenija','ba'=>'Bosna i Hercegovina','rs'=>'Srbija','me'=>'Crna Gora','xk'=>'Kosovo','al'=>'Albanija','de'=>'Njemačka','at'=>'Austrija','ch'=>'Švicarska','es'=>'Španjolska','fr'=>'Francuska','it'=>'Italija','gb'=>'Ujedinjeno Kraljevstvo','ie'=>'Irska','nl'=>'Nizozemska','be'=>'Belgija','pl'=>'Poljska','se'=>'Švedska','no'=>'Norveška','dk'=>'Danska','us'=>'SAD','ca'=>'Kanada','au'=>'Australija','xx'=>'Druga zemlja'];}
function fcc_business_offer_url_valid(string $url,string $country,string $adapter): bool {
 $p=parse_url($url);if(!$p||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port'])||isset($p['fragment'])||preg_match('/[\x00-\x20\\\\]/',$url))return false;
 if(!in_array(strtolower($p['host']??''),['foreverliving.com','www.foreverliving.com','www.flpshop.ba','www.foreveralbania.com'],true))return false;
 parse_str($p['query']??'',$q);
 foreach(array_keys($q) as $key)if(!in_array($key,['store','language'],true))return false;
 if(preg_match('~(?:%2e|%2f|%5c|\.\.)~i',$p['path']??''))return false;
 if($adapter==='shop')return class_exists('FccShopCatalog')&&FccShopCatalog::market($url)===$country&&preg_match('~/(products|proizvodi|produktet)/[^/]+/?$~D',$p['path']??'');
 if($adapter==='join_distrib_id'){
  $stores=['hr'=>'HRV','si'=>'SVN','de'=>'DEU','at'=>'AUT','rs'=>'SCG','es'=>'ESP','ch'=>'CHE','fr'=>'FRA','it'=>'ITA','gb'=>'GBR','ie'=>'IRL'];
  return ($p['host']==='foreverliving.com'||$p['host']==='www.foreverliving.com')&&isset($stores[$country])&&($q['store']??'')===$stores[$country]&&preg_match('~^/join/?$~D',$p['path']??'')&&is_string($q['language']??null)&&preg_match('/^[a-z]{2}-[a-z]{2}$/D',$q['language']);
 }
 return false;
}
function fcc_business_destination(array $s,array $offer): ?string {
 if(!$s['available']||$offer['status']!=='verified'||!in_array($offer['destination_kind'],['enrollment','starter'],true)||!$offer['verified_at']||!$offer['verified_until']||$offer['verified_at']>gmdate('Y-m-d H:i:s')||$offer['verified_until']<=gmdate('Y-m-d H:i:s')||trim($offer['evidence'])==='')return null;
 $market=$offer['country'];$fbo=$s['owner']['fbo']??'';
 if(isset($s['fbo_snapshot'])&&$s['fbo_snapshot']!==$fbo)return null;
 if(!preg_match('/^[0-9]{6,20}$/D',$fbo)||!isset(fcc_business_countries()[$market])||fcc_market_contact_only($market)||!fcc_business_offer_url_valid($offer['destination_url'],$market,$offer['adapter']))return null;
 if($offer['adapter']==='shop'&&$offer['destination_kind']==='starter')return \Altum\Link::build_forever_destination_url($offer['destination_url'],$fbo,$market)?:null;
 if($offer['adapter']==='join_distrib_id'&&$offer['destination_kind']==='enrollment')return \Altum\Link::append_query_parameters_to_url($offer['destination_url'],['distribID'=>$fbo]);
 return null;
}
function fcc_business_offers(array $s,string $country): array {
 if(!isset(fcc_business_countries()[$country]))return [];
 $rows=fcc_partner_rows("SELECT * FROM fcc_business_market_offers WHERE country=? AND status='verified' ORDER BY id LIMIT 10",[$country]);
 return array_values(array_filter($rows,fn($r)=>fcc_business_destination($s,$r)!==null));
}
/** Read the same published starter article used by the product library. */
function fcc_business_starter_article(): ?array {
  $post=fcc_partner_one("SELECT blog_post_id,title,url,image,webshop_links FROM blog_posts WHERE url='start-paket' AND is_published=1 ORDER BY blog_post_id LIMIT 1");
 if($post)$post['webshop_links']=json_encode(array_map(static fn($m)=>$m['business'],fcc_forever_markets()));
 return $post;
}
/** Exact market only. The presentation owner, never the viewer, supplies the referral. */
function fcc_business_starter_destination(array $s,array $post,string $country): ?string {
 $fbo=(string)($s['owner']['fbo']??'');
 if(empty($s['available'])||!isset(fcc_business_countries()[$country])||fcc_market_contact_only($country)
  ||!preg_match('/^[0-9]{6,20}$/D',$fbo)||isset($s['fbo_snapshot'])&&$s['fbo_snapshot']!==$fbo)return null;
 if(($post['url']??'')==='start-paket')return fcc_forever_market_destination($fbo,'business',$country);
 $links=json_decode((string)($post['webshop_links']??'{}'),true)?:[];
 $base=$links[$country]??null;if(!is_string($base)||$base==='')return null;
 $parts=parse_url($base);
 if(!$parts||isset($parts['query'])||isset($parts['fragment'])||!FccShopCatalog::clean($base)||FccShopCatalog::market($base)!==$country)return null;
 $join=in_array($parts['host'],['foreverliving.com','www.foreverliving.com'],true)
  &&preg_match('~^/join/[a-z]{3}/[a-z]{2}(?:-[a-z]{2,4}){1,2}/(?:welcome|fbo-enrollment)/?$~D',$parts['path']??'');
 if(!$join&&!fcc_market_product_allowed((object)$post,$country))return null;
 // The existing resolver retains each market's fboId/id convention and offer policy.
 $discount=$join?[]:\Altum\Link::get_main_biolink_discount_query_params((int)($s['owner']['uid']??0),$country);
 return \Altum\Link::build_forever_destination_url($base,$fbo,$country,$discount)?:null;
}
/** Market-specific public facts. Never turn an unverified foreign price into a local offer. */
function fcc_business_starter_details(array $post,string $country,?array $records=null,?int $now=null): array {
 $out=['price'=>null,'qualification'=>false,'once'=>false];$now??=time();
 if(!isset(fcc_business_countries()[$country])||fcc_market_contact_only($country))return $out;
 $base=(json_decode((string)($post['webshop_links']??'{}'),true)?:[])[$country]??'';
 if(!is_string($base)||!$base||FccShopCatalog::market($base)!==$country)return $out;
 $records??=json_decode((string)file_get_contents(APP_PATH.'config/fcc_business_starter_details.json'),true)?:[];
 $facts=$records[$country]??[];
 $reviewed=strtotime($facts['reviewed_at']??'')?:0;$until=strtotime($facts['review_until']??'')?:0;
 $valid=($facts['destination']??null)===$base&&$reviewed>0&&$reviewed<=$now&&$until>$now
  &&FccShopCatalog::market((string)($facts['source']??''))===$country;
 if($valid){$out['once']=!empty($facts['once_before_assistant_supervisor']);$out['qualification']=($facts['qualification_cc']??null)===2;}
 // Existing catalog prices are already checked against the exact country and product identity.
 if(!str_starts_with((string)parse_url($base,PHP_URL_PATH),'/join/')&&fcc_market_product_allowed((object)$post,$country)){
  $cached=FccShopCatalog::price($base,$country,true);
  if($cached)$out['price']=['amount'=>$cached['price'],'currency'=>$cached['currency'],'variant'=>$cached['name'],'checked_at'=>$cached['checked_at']];
 }
 // The public enrollment product page is rendered by JavaScript. Its reviewed snapshot expires
 // at the explicit review deadline, capped at 30 days. The displayed date is the actual review date.
 $price=$valid?($facts['price']??null):null;$checked=strtotime($price['checked_at']??'')?:0;
 if(!$out['price']&&$price&&$checked>0&&$checked<=$now&&$now-$checked<=30*86400
  &&is_numeric($price['amount']??null)&&(float)$price['amount']>0&&(float)$price['amount']<100000
  &&FccShopCatalog::currencyAllowed($country,(string)($price['currency']??''))
  &&FccShopCatalog::market((string)($price['source']??''))===$country
  &&trim((string)($price['variant']??''))!==''&&trim((string)($price['sku']??''))!=='')$out['price']=$price;
 return $out;
}
function fcc_business_starter(array $s,string $country): ?array {
 if(empty($s['available'])||!isset(fcc_business_countries()[$country])||!($post=fcc_business_starter_article()))return null;
 $destination=fcc_business_starter_destination($s,$post,$country);
 $file=basename((string)$post['image']);
 $image=$file!==''?\Altum\Uploads::get_full_url('blog').rawurlencode($file):null;
 $dimensions=$file!==''?(@getimagesize(UPLOADS_PATH.'blog/'.$file)?:[540,300]):[540,300];
 return ['title'=>'Start Your Journey','image'=>$image,'width'=>$dimensions[0],'height'=>$dimensions[1],
  'details'=>$destination?fcc_business_starter_details($post,$country):['price'=>null,'qualification'=>false,'once'=>false],
  'destination'=>$destination,'version'=>hash('sha256',fcc_business_json([(int)$post['blog_post_id'],$country,$destination])),
  'kind'=>$destination&&str_starts_with((string)parse_url($destination,PHP_URL_PATH),'/join/')?'enrollment':'starter'];
}
function fcc_business_asset_save(int $actor,int $id,array $in): int {
 $title=fcc_partner_text($in['title']??'',160);if(mb_strlen($title)<3)throw new InvalidArgumentException(fcc_t('Upiši naziv prezentacije.'));
 $desc=fcc_partner_text($in['description']??'',500);$lang=fcc_partner_choice($in['language']??'hr',array_fill_keys(['hr','en','sl','de','es','sr', 'sq', 'cnr', 'fr','sv','hu','pl','ro'],1));
 $url=fcc_partner_text($in['video_url']??'',1000);$video=$url!==''?fcc_webinar_video('custom',$url):['provider'=>'none','id'=>'','hash'=>''];
 $status=fcc_partner_choice($in['status']??'draft',['draft'=>1,'published'=>1,'withdrawn'=>1]);$note=fcc_partner_text($in['approval_note']??'',500);$approved=!empty($in['approved']);
 if($status==='published'&&(!$approved||$note===''))throw new InvalidArgumentException(fcc_t('Prije objave potvrdi prava korištenja i zapiši izvor odobrenja.'));
 $duration=max(0,min(86400,(int)($in['duration_seconds']??0)));$order=max(0,min(1000,(int)($in['sort_order']??10)));
 return fcc_business_atomic($actor,function()use($actor,$id,$in,$title,$desc,$lang,$video,$status,$note,$approved,$duration,$order){
  if($id){$old=fcc_partner_one('SELECT * FROM fcc_business_assets WHERE id=? FOR UPDATE',[$id]);if(!$old||(int)$old['version']!==(int)($in['version']??0))throw new InvalidArgumentException(fcc_t('Sadržaj je promijenjen. Osvježi prikaz.'));
   fcc_partner_query('UPDATE fcc_business_assets SET title=?,description=?,language=?,provider=?,video_id=?,video_hash=?,status=?,approval_note=?,approved=?,duration_seconds=?,sort_order=?,actor_user_id=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE id=?',[$title,$desc,$lang,$video['provider'],$video['id'],$video['hash'],$status,$note,$approved?1:0,$duration,$order,$actor,$id]);
  }else{fcc_partner_query('INSERT INTO fcc_business_assets (title,description,language,provider,video_id,video_hash,status,approval_note,approved,duration_seconds,sort_order,actor_user_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$title,$desc,$lang,$video['provider'],$video['id'],$video['hash'],$status,$note,$approved?1:0,$duration,$order,$actor]);$id=(int)database()->insert_id;}
  $purpose=fcc_partner_choice($in['purpose']??'intro',['intro'=>1,'work'=>1,'support'=>1]);$summary=fcc_partner_text($in['summary']??'',2000);$scope=fcc_partner_choice($in['approval_scope']??'internal',['internal'=>1,'company'=>1,'local_test'=>1]);
  $demo=$scope==='local_test';if($demo&&!fcc_business_local())throw new InvalidArgumentException(fcc_t('Probni sadržaj dostupan je samo lokalno.'));
  $recommended=!empty($in['recommended'])&&$status==='published';
  if($recommended)fcc_partner_query('UPDATE fcc_business_assets SET recommended=0 WHERE language=? AND id<>?',[$lang,$id]);
  fcc_partner_query('UPDATE fcc_business_assets SET purpose=?,recommended=?,summary=?,approval_scope=?,is_demo=? WHERE id=?',[$purpose,$recommended?1:0,$summary,$scope,$demo?1:0,$id]);
  return $id;
 });
}
function fcc_business_offer_save(int $actor,int $id,array $in): int {
 $country=fcc_partner_choice($in['country']??'',fcc_business_countries());$title=fcc_partner_text($in['title']??'',160);if(strlen($title)<3)throw new InvalidArgumentException(fcc_t('Upiši naziv ponude.'));
 $kind=fcc_partner_choice($in['destination_kind']??'contact',['contact'=>1,'starter'=>1,'enrollment'=>1]);$adapter=fcc_partner_choice($in['adapter']??($kind==='starter'?'shop':($kind==='enrollment'?'join_distrib_id':'none')),['none'=>1,'shop'=>1,'join_distrib_id'=>1]);$url=fcc_partner_text($in['destination_url']??'',1500);
 $state=fcc_partner_choice($in['status']??'draft',['draft'=>1,'verified'=>1,'unavailable'=>1]);$evidence=fcc_partner_text($in['evidence']??'',1000);$until=fcc_partner_date($in['verified_until']??'');
 if($kind!=='contact'&&!fcc_business_offer_url_valid($url,$country,$adapter))throw new InvalidArgumentException(fcc_t('Provjeri službenu HTTPS poveznicu, zemlju i način preporuke. U poveznici ne smije biti tuđi sponzorski broj.'));
 if($state==='verified'&&($kind==='contact'||!empty(fcc_market_contact_only($country))||$adapter==='none'||empty($in['sponsor_checked'])||strlen($evidence)<15||!$until||$until<=gmdate('Y-m-d')||$until>gmdate('Y-m-d',time()+90*86400)))throw new InvalidArgumentException(fcc_t('Potvrdi pregled sponzora u službenom postupku, upiši dokaz provjere i rok provjere do 90 dana.'));
 if($adapter==='shop'&&$kind!=='starter')throw new InvalidArgumentException(fcc_t('Trgovinska preporuka koristi se za određeni početni paket. Za registraciju odaberi službeni upisni postupak.'));
 if($adapter==='join_distrib_id'&&$kind!=='enrollment')throw new InvalidArgumentException(fcc_t('Upisni način preporuke koristi se samo za registraciju.'));
 return fcc_business_atomic($actor,function()use($actor,$id,$in,$country,$title,$kind,$adapter,$url,$state,$evidence,$until){
  $verified=$state==='verified'?gmdate('Y-m-d H:i:s'):null;$expires=$state==='verified'?$until.' 00:00:00':null;
  if($id){$old=fcc_partner_one('SELECT * FROM fcc_business_market_offers WHERE id=? FOR UPDATE',[$id]);if(!$old||(int)$old['version']!==(int)($in['version']??0))throw new InvalidArgumentException(fcc_t('Ponuda je izmijenjena. Osvježi prikaz.'));
   fcc_partner_query('UPDATE fcc_business_market_offers SET country=?,title=?,destination_kind=?,adapter=?,destination_url=?,status=?,evidence=?,verified_at=?,verified_until=?,actor_user_id=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE id=?',[$country,$title,$kind,$adapter,$url,$state,$evidence,$verified,$expires,$actor,$id]);
  }else{fcc_partner_query('INSERT INTO fcc_business_market_offers (country,title,destination_kind,adapter,destination_url,status,evidence,verified_at,verified_until,actor_user_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$country,$title,$kind,$adapter,$url,$state,$evidence,$verified,$expires,$actor]);$id=(int)database()->insert_id;}return $id;
 });
}
