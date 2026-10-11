<?php
defined('ALTUMCODE') || die();

/** Private, versioned instructions. Display language never selects a shop market. */
function fcc_help_meta(): array {
 static $meta;
 return $meta??=json_decode(file_get_contents(APP_PATH.'config/fcc_help_center/manifest.json'),true,512,JSON_THROW_ON_ERROR);
}
function fcc_help_enabled(): bool {
 return fcc_partner_enabled()&&(getenv('FCC_LOCAL')==='1'||!empty(fcc_help_meta()['enabled']));
}
function fcc_help_manifest(): array {return fcc_help_meta()['articles'];}
function fcc_help_locale(): string {return fcc_locale();}
function fcc_help_catalog(string $locale): array {
 static $cache=[];$locale=fcc_locale($locale);if(isset($cache[$locale]))return $cache[$locale];
 $path=APP_PATH.'config/fcc_help_center/'.$locale.'.json';$data=is_file($path)?json_decode(file_get_contents($path),true):null;
 if(!$data||($data['source_version']??'')!==fcc_help_meta()['version']){
  if($locale==='en')return $cache[$locale]=['locale'=>'en','source_version'=>'','articles'=>[]];
  $data=fcc_help_catalog('en');
 }
 $data['requested_locale']=$locale;return $cache[$locale]=$data;
}
function fcc_help_article(string $id,string $locale): ?array {
 $meta=fcc_help_manifest()[$id]??null;if(!$meta||$meta['status']!=='published'||($meta['version']??'')!==fcc_help_meta()['version']||($meta['source_version']??'')!==fcc_help_meta()['version'])return null;
 $catalog=fcc_help_catalog($locale);$content=$catalog['articles'][$id]??null;
 if(!$content||!is_string($content['title']??null)||empty($content['sections']))return null;
 return $meta+$content+['locale'=>$catalog['locale'],'content_locale'=>$catalog['locale'],'requested_locale'=>fcc_locale($locale)];
}
function fcc_help_ui_fallback(string $locale): array {
 $source=json_decode(file_get_contents(APP_PATH.'config/fcc_help_center/ui-hr.json'),true,512,JSON_THROW_ON_ERROR);
 return array_map(static fn($text)=>fcc_t($text,[],$locale),$source);
}
function fcc_help_t(string $key,?string $locale=null,array $replace=[]): string {
 static $cache=[];$locale=fcc_locale($locale);if(!isset($cache[$locale])){
  $path=APP_PATH.'config/fcc_help_center/ui-'.$locale.'.json';
  $cache[$locale]=is_file($path)?json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR):fcc_help_ui_fallback($locale);
 }
 $value=$cache[$locale][$key]??$key;return strtr($value,$replace);
}
function fcc_help_url(string $id='',string $anchor=''): string {
 $params=[];if($id!==''){
  $article=fcc_help_article($id,fcc_help_locale());if(!$article)return url('partner/help');$params['topic']=$id;
  if($anchor!==''&&in_array($anchor,array_column($article['sections'],'id'),true))$params['section']=$anchor;else$anchor='';
 }
 return url('partner/help'.($params?'?'.http_build_query($params):'')).($anchor!==''?'#'.$anchor:'');
}
function fcc_help_normalize(string $text): string {
 $text=mb_strtolower($text,'UTF-8');$text=strtr($text,['č'=>'c','ć'=>'c','š'=>'s','ž'=>'z','đ'=>'d','ë'=>'e','ç'=>'c','ß'=>'ss','ñ'=>'n','ä'=>'a','ö'=>'o','ü'=>'u','é'=>'e','è'=>'e','ê'=>'e','à'=>'a','á'=>'a','í'=>'i','ó'=>'o','ú'=>'u','ù'=>'u','å'=>'a','ő'=>'o','ű'=>'u','ł'=>'l','ą'=>'a','ę'=>'e','ó'=>'o','ń'=>'n','ś'=>'s','ź'=>'z','ż'=>'z','ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ş'=>'s','ț'=>'t','ţ'=>'t']);
 return trim(preg_replace('/[^\p{L}\p{N}]+/u',' ',$text));
}
function fcc_help_search(string $query,string $locale,string $category=''): array {
 $query=mb_substr(fcc_help_normalize($query),0,180);$tokens=array_filter(explode(' ',$query));$out=[];
 foreach(fcc_help_manifest() as $id=>$meta){
  if($category!==''&&$category!==$meta['category'])continue;$article=fcc_help_article($id,$locale);if(!$article)continue;
  $plain=fcc_help_normalize(json_encode($article,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
  $title=fcc_help_normalize($article['title'].' '.implode(' ',$article['keywords']??[]));$score=0;$matched=true;
  foreach($tokens as $token){if(!str_contains($plain,$token)){$matched=false;break;}$score+=str_contains($title,$token)?10:1;}
  if($matched){$article['search_score']=$score;$out[]=$article;}
 }
 if($tokens)usort($out,fn($a,$b)=>$b['search_score']<=>$a['search_score']);return $out;
}
function fcc_help_state(int $uid): array {
 $p=json_decode(fcc_partner_one('SELECT preferences FROM users WHERE user_id=?',[$uid])['preferences']??'{}',true)?:[];
 $state=is_array($p['help_center']??null)?$p['help_center']:[];$out=[];
 foreach(['read','helpful','dismissed','later'] as $key)$out[$key]=is_array($state[$key]??null)?array_intersect_key($state[$key],fcc_help_manifest()):[];
 return $out;
}
function fcc_help_update(int $uid,string $id,string $action): void {
 if($action!=='restore'&&!fcc_help_article($id,fcc_help_locale()))throw new InvalidArgumentException('Unknown guide');
 if(!in_array($action,['read','unread','helpful_yes','helpful_no','dismiss','later','restore'],true))throw new InvalidArgumentException('Unknown guide action');
 database()->begin_transaction();try{
  $row=fcc_partner_one('SELECT preferences FROM users WHERE user_id=? AND status=1 FOR UPDATE',[$uid]);if(!$row)throw new InvalidArgumentException('Account unavailable');
  $prefs=json_decode($row['preferences']??'{}',true)?:[];$state=is_array($prefs['help_center']??null)?$prefs['help_center']:[];
  foreach(['read','helpful','dismissed','later'] as $key)$state[$key]=is_array($state[$key]??null)?array_intersect_key($state[$key],fcc_help_manifest()):[];
  $at=gmdate('c');switch($action){
   case 'read':$state['read'][$id]=$at;break;
   case 'unread':unset($state['read'][$id]);break;
   case 'helpful_yes':$state['helpful'][$id]=true;break;
   case 'helpful_no':$state['helpful'][$id]=false;break;
   case 'dismiss':$state['dismissed'][$id]=$at;break;
   case 'later':$state['later'][$id]=gmdate('c',time()+86400);break;
   case 'restore':$state['dismissed']=[];$state['later']=[];break;
  }
  $prefs['help_center']=$state;fcc_partner_query('UPDATE users SET preferences=? WHERE user_id=?',[json_encode($prefs,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$uid]);database()->commit();
  cache()->deleteItemsByTag('user_id='.$uid);cache()->deleteItem('user?user_id='.$uid);
 }catch(Throwable $e){database()->rollback();throw $e;}
}
function fcc_help_next(int $uid,string $locale): ?array {
 $state=fcc_help_state($uid);foreach(['start','daily','card','products','webinar-invite','contacts','notifications','presentation'] as $id){
  if(isset($state['read'][$id])||isset($state['dismissed'][$id])||strtotime($state['later'][$id]??'1970-01-01')>time())continue;
  if($a=fcc_help_article($id,$locale))return $a;
 }return null;
}
function fcc_help_context_topic(string $controller,string $section='',string $type='',string $view=''): ?string {
 return match($controller){
  'partner'=>match($section){'today'=>$view==='materials'?'presentation':'daily','contacts'=>'contacts','cards'=>'card','share'=>in_array($type,['webinar','events'],true)?'webinar-invite':'products','team'=>'team-outreach','notifications'=>'notifications','settings'=>'support','points','top'=>'results','more'=>'start','webinars'=>'webinar-host',default=>null},
  'link','links'=>'card','blog'=>'products','partner-webinar'=>'webinar-host','partner-marketing'=>'business','fcc-results'=>'results','account-plan','account-payments'=>'plan','feedback-tickets','feedback-ticket'=>'support','qr-codes','domains','vip-funnel-studio'=>'advanced',default=>null,
 };
}
function fcc_help_downloads(): array {
 $path=APP_PATH.'storage/fcc-help/presentations/manifest.json';if(!is_file($path))return [];
 $manifest=json_decode(file_get_contents($path),true);return is_array($manifest)?$manifest:[];
}
function fcc_help_asset(array $query): void {
 $type=$query['asset']??'';$locale=$query['locale']??fcc_help_locale();if(!is_string($locale)||!in_array($locale,fcc_help_meta()['languages'],true))throw_404();
 $storage=APP_PATH.'storage/fcc-help/';$filename='';$mime='';$disposition='inline';
 if($type==='shot'){
  $key=$query['key']??'';$device=$query['device']??'';
  $screens=json_decode(@file_get_contents($storage.'screenshots/manifest.json')?:'{}',true)?:[];
  if(!is_string($key)||!is_string($device)||!isset($screens[$locale][$key][$device]))throw_404();
  $image=$screens[$locale][$key][$device];$filename=$storage.'screenshots/'.$locale.'/'.$key.'-'.$device.'.jpg';$mime='image/jpeg';$expected=$image['sha256'];
 }elseif($type==='presentation'){
  $files=fcc_help_downloads();$item=$files[$locale]??null;if(!$item)throw_404();
  $filename=$storage.'presentations/fcc-team-'.$locale.'.pptx';$mime='application/vnd.openxmlformats-officedocument.presentationml.presentation';$disposition='attachment';$expected=$item['sha256'];
 }elseif($type==='bundle'){
  $files=fcc_help_downloads();$item=$files['bundle']??null;if(!$item)throw_404();
  $filename=$storage.'presentations/fcc-team-all-languages.zip';$mime='application/zip';$disposition='attachment';$expected=$item['sha256'];
 }else throw_404();
 if(!is_file($filename)||hash_file('sha256',$filename)!==$expected)throw_404();
 \Altum\Router::$controller_settings['has_view']=false;
 header('Content-Type: '.$mime);header('Content-Length: '.filesize($filename));header('Content-Disposition: '.$disposition.'; filename="'.basename($filename).'"');header('X-Content-Type-Options: nosniff');
 if(($_SERVER['REQUEST_METHOD']??'GET')!=='HEAD')readfile($filename);
}
function fcc_help_screens(string $id,string $locale): array {
 $a=fcc_help_article($id,$locale);if(!$a)return [];$key=$a['screen'];
 $file=APP_PATH.'storage/fcc-help/screenshots/manifest.json';$screens=is_file($file)?json_decode(file_get_contents($file),true):[];$out=[];
 foreach(['mobile','desktop'] as $device){
  $shot=$screens[$locale][$key][$device]??null;if(!$shot)continue;
  $shot['url']=url('partner/help?'.http_build_query(['asset'=>'shot','key'=>$key,'device'=>$device,'locale'=>$locale]));$shot['device']=$device;$out[]=$shot;
 }return $out;
}
function fcc_help_page($controller): void {
 if(!fcc_help_enabled())throw_404();$uid=(int)$controller->user->user_id;$method=$_SERVER['REQUEST_METHOD']??'GET';
 if(!in_array($method,['GET','HEAD','POST'],true)){http_response_code(405);header('Allow: GET, HEAD, POST');\Altum\Router::$controller_settings['has_view']=false;return;}
 if(isset($_GET['asset'])){if(!in_array($method,['GET','HEAD'],true)){http_response_code(405);\Altum\Router::$controller_settings['has_view']=false;return;}fcc_help_asset($_GET);return;}
 $topic=$_GET['topic']??'';if(!is_string($topic))throw_404();$locale=fcc_help_locale();$article=$topic!==''?fcc_help_article($topic,$locale):null;if($topic!==''&&!$article)throw_404();
 $section=$_GET['section']??'';if(!is_string($section)||($section!==''&&(!$article||!in_array($section,array_column($article['sections'],'id'),true))))throw_404();
 if(isset($_GET['review'])&&!\Altum\Authentication::is_admin())throw_404();$error=null;
 if($method==='POST'){
  if(!is_string($_POST['token']??null)||!hash_equals(\Altum\Csrf::get(),$_POST['token'])){http_response_code(403);$error=fcc_t('Sesija je istekla. Osvježi stranicu.');}
  else{try{
   if(session_has('admin_user_id')||\Altum\Teams::is_delegated())throw new InvalidArgumentException(fcc_t('Vrati se na vlastiti račun.'));
   fcc_help_update($uid,is_string($_POST['topic']??null)?$_POST['topic']:'',is_string($_POST['action']??null)?$_POST['action']:'');
   redirect('partner/help'.($topic!==''?'?topic='.rawurlencode($topic):''));
  }catch(InvalidArgumentException $e){http_response_code(422);$error=fcc_t('Spremanje nije uspjelo. Pokušaj ponovno.');}}
 }
 $q=fcc_partner_text(is_string($_GET['q']??null)?$_GET['q']:'',180);$category=is_string($_GET['category']??null)?$_GET['category']:'';
 if($category!==''&&!in_array($category,array_column(fcc_help_manifest(),'category'),true))throw_404();
 \Altum\Title::set($article?$article['title']:fcc_help_t('title'));
 $data=['section'=>'help','article'=>$article,'articles'=>fcc_help_search($q,$locale,$category),'locale'=>$locale,'state'=>fcc_help_state($uid),'suggestion'=>fcc_help_next($uid,$locale),'q'=>$q,'category'=>$category,'error'=>$error,'anchor'=>$section,'review'=>isset($_GET['review'])];
 $view=new \Altum\View('partner/help',(array)$controller);$controller->add_view_content('content',$view->run($data));
}
