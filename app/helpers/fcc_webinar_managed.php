<?php
defined('ALTUMCODE') || die();

/** Managed series have a separate organizer. Invitation and CRM ownership never change. */
function fcc_mw_ready(): bool {
 static $ready;
 return $ready??=!!fcc_partner_one("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_webinar_series' AND COLUMN_NAME='organizer_user_id'");
}
function fcc_mw_series(array $s): ?array {
 if(empty($s['series_id'])||!fcc_mw_ready())return null;
 return fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=? AND organizer_user_id IS NOT NULL',[(int)$s['series_id']]);
}
function fcc_mw_graph(): array {return $GLOBALS['fcc_mw_graph']??=fcc_team_graph();}
function fcc_mw_scope_allowed(int $uid,int $organizer,string $scope,?array $graph=null): bool {
 $graph=$graph??fcc_mw_graph();
 if(!isset($graph[$uid],$graph[$organizer])||(int)$graph[$uid]['status']!==1||(int)$graph[$organizer]['status']!==1)return false;
 if($uid===$organizer)return true;
 if($scope!=='team')return false;
 $root=fcc_team_primary($organizer,$graph);$member=fcc_team_primary($uid,$graph);
 if($root<1||$member<1||!isset($graph[$root],$graph[$member])||(int)$graph[$root]['status']!==1||(int)$graph[$member]['status']!==1)return false;
 if(!empty($graph[$uid]['is_privacy_requested'])||!empty($graph[$organizer]['is_privacy_requested'])||!empty($graph[$root]['is_privacy_requested'])||!empty($graph[$member]['is_privacy_requested']))return false;
 return $root===$member||in_array($root,fcc_team_upline($uid,$graph,false),true);
}
function fcc_mw_allowed(int $uid,array $series): bool {
 // Partner access always follows the organizer's audience, including administrators.
 return fcc_mw_scope_allowed($uid,(int)$series['organizer_user_id'],$series['audience_scope']);
}
function fcc_mw_require_owner(int $uid,array $s,bool $cancel=false): array {
 $series=fcc_mw_series($s);
 if(!$series||((int)$series['organizer_user_id']!==$uid&&!fcc_team_admin($uid))||!fcc_webinar_standard_enabled($uid))throw new InvalidArgumentException(fcc_mw_t('unavailable'));
 if(!$cancel)fcc_webinar_require_pro($uid);
 return $series;
}
function fcc_mw_locale(array $s,?string $fallback=null): string {
 $d=json_decode($s['event_json']??$s['definition_json']??'{}',true)?:[];
 return !empty($d['managed'])?fcc_locale($d['language']??'en'):fcc_locale($fallback);
}
function fcc_mw_public_locale(array $s): void {
 $d=json_decode($s['event_json']??'{}',true)?:[];
 if(!empty($d['managed']))\Altum\Language::set_by_code(fcc_mw_locale($s));
}
function fcc_mw_url(array $s,string $path): string {
 $d=json_decode($s['event_json']??'{}',true)?:[];
 return !empty($d['managed'])?SITE_URL.fcc_mw_locale($s).'/'.ltrim($path,'/'):url($path);
}
function fcc_mw_join_url(array $s): string {
 return !empty(fcc_event_definition($s)['managed'])?fcc_webinar_access_url($s['access_url']):forever_business_vip_webinar_url();
}
/** Same Zoom meeting with a different password still uses the same room. */
function fcc_mw_room(string $url): string {
 if(rtrim($url,'/')===rtrim(forever_business_vip_webinar_url(),'/')){
  $shared=fcc_partner_one("SELECT l.* FROM links l JOIN users u ON u.user_id=l.user_id WHERE l.url='vip-edukacija' AND l.type='link' AND l.domain_id=0 AND u.type=1 AND u.status=1 LIMIT 1");
  if($shared&&function_exists('fcc_team_webinar_destination')){$target=fcc_team_webinar_destination($shared,['type'=>1,'status'=>1]);if($target)$url=$target;}
 }
 $p=parse_url($url);$host=strtolower($p['host']??'');$path=rtrim($p['path']??'','/');
 if(($host==='zoom.us'||str_ends_with($host,'.zoom.us'))&&preg_match('~^/(?:j|wc)/([0-9]+)~',$path,$m))return 'zoom:'.$m[1];
 return $host.$path;
}
function fcc_mw_languages(): array {return fcc_language_labels();}
function fcc_mw_t(string $key,?string $locale=null): string {
 static $copy;
 $copy??=json_decode(file_get_contents(APP_PATH.'config/managed-webinar-copy.json'),true);
 return $copy[fcc_locale($locale)][$key]??fcc_t($copy['hr'][$key]??$copy['en'][$key]??$key,[],$locale);
}
function fcc_mw_input(array $input,int $organizer,string $scope,?DateTimeImmutable $now=null): array {
 $p=fcc_webinar_personal_input($input);
 $d=fcc_event_defaults('business');
 foreach(['title','description','date','time','duration','timezone','access_url'] as $k)$d[$k]=$p[$k];
 $d['repeat']=fcc_partner_choice($input['repeat']??'once',['once'=>1,'weekly'=>1]);
 $d['until']=fcc_partner_text($input['until']??'',10);
 $d['language']=fcc_partner_choice($input['language']??'en',fcc_mw_languages());
 $d['timezone']=$p['timezone'];$d['access_url']=$p['access_url'];
 $d['education']=0;$d['announce']=empty($input['announce'])?0:1;$d['reminders']=[60];$d['team_root']='';$d['audience']='team';$d['guests']=1;
 $d=fcc_event_validate($d,true,$now)+['language'=>$d['language'],'timezone'=>$p['timezone'],'access_url'=>$p['access_url']];
 $d['managed']=1;$d['organizer_user_id']=$organizer;$d['audience_scope']=$scope;
 return $d;
}
function fcc_mw_create(int $uid,array $input,?DateTimeImmutable $now=null): array {
 if(!fcc_mw_ready()||!fcc_webinar_standard_enabled($uid))throw new InvalidArgumentException(fcc_mw_t('unavailable'));
 fcc_webinar_require_pro($uid);$scope=fcc_partner_choice($input['audience_scope']??'personal',['personal'=>1,'team'=>1]);
 $key=$input['request_key']??'';if(!is_string($key)||!preg_match('/^[a-f0-9]{32}$/D',$key))throw new InvalidArgumentException(fcc_mw_t('stale'));
 $d=fcc_mw_input($input,$uid,$scope,$now);
 return fcc_event_transaction(function()use($uid,$scope,$key,$d,$now){
  $old=fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE organizer_user_id=? AND request_key=? FOR UPDATE',[$uid,$key]);
  if($old){if(fcc_event_definition($old)!==fcc_event_definition(['definition_json'=>fcc_webinar_json($d)]))throw new InvalidArgumentException(fcc_mw_t('stale'));return fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE series_id=? ORDER BY starts_at_utc LIMIT 1',[(int)$old['id']]);}
  if((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_webinar_series WHERE organizer_user_id=? AND status='published'",[$uid])['n']>=100)throw new InvalidArgumentException(fcc_mw_t('limit'));
  fcc_partner_query("INSERT INTO fcc_webinar_series (series_key,title,definition_json,status,created_by,organizer_user_id,audience_scope,request_key,created_at,updated_at) VALUES (?,?,?,'published',?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())",['member-'.bin2hex(random_bytes(10)),$d['title'],fcc_webinar_json($d),$uid,$uid,$scope,$key]);
  $id=(int)database()->insert_id;$series=fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=?',[$id]);fcc_event_materialize($series,$now);fcc_event_audit($uid,'managed_published',$id,null,['scope'=>$scope]);
  return fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE series_id=? ORDER BY starts_at_utc LIMIT 1',[$id]);
 });
}
function fcc_mw_overview(int $uid): array {
 if(!fcc_mw_ready())return [];
 return fcc_partner_rows("SELECT s.*,w.audience_scope,w.organizer_user_id,(SELECT COUNT(*) FROM fcc_webinar_registrations r WHERE r.session_id=s.id AND r.status='registered') registrations FROM fcc_webinar_series w JOIN fcc_webinar_sessions s ON s.series_id=w.id WHERE w.organizer_user_id=? AND s.id=COALESCE((SELECT x.id FROM fcc_webinar_sessions x WHERE x.series_id=w.id AND x.status='scheduled' AND x.starts_at_utc>UTC_TIMESTAMP() ORDER BY x.starts_at_utc LIMIT 1),(SELECT x.id FROM fcc_webinar_sessions x WHERE x.series_id=w.id ORDER BY x.starts_at_utc DESC LIMIT 1)) ORDER BY s.starts_at_utc LIMIT 100",[$uid]);
}
function fcc_mw_preferred(int $uid,array $events,int $explicit=0): ?array {
 if(!$events)return null;
 if($explicit)foreach($events as $s)if((int)$s['id']===$explicit)return $s;
 if($explicit)return null;
 $stored=fcc_mw_ready()?fcc_partner_one('SELECT series_id FROM fcc_webinar_preferences WHERE user_id=?',[$uid]):null;
 if($stored)foreach($events as $s)if((int)($s['series_id']??0)===(int)$stored['series_id'])return $s;
 $graph=fcc_mw_graph();$path=array_merge([fcc_team_primary($uid,$graph)],fcc_team_upline($uid,$graph,false));$best=null;$rank=PHP_INT_MAX;
 foreach($events as $s){$d=fcc_event_definition($s);if(empty($d['managed']))continue;$n=array_search(fcc_team_primary((int)$d['organizer_user_id'],$graph),$path,true);if($n!==false&&$n<$rank){$best=$s;$rank=$n;}}
 return $best??$events[0];
}
function fcc_mw_remember(int $uid,array $s): void {
 if(fcc_mw_ready()&&!empty($s['series_id'])&&fcc_event_access($uid,$s)['allowed'])fcc_partner_query('INSERT INTO fcc_webinar_preferences (user_id,series_id) VALUES (?,?) ON DUPLICATE KEY UPDATE series_id=VALUES(series_id)',[$uid,(int)$s['series_id']]);
}
function fcc_mw_caption(array $s): string {
 $series=fcc_mw_series($s);if(!$series)return '';
 $u=fcc_partner_one('SELECT name FROM users WHERE user_id=?',[(int)$series['organizer_user_id']]);
 return ($u['name']??'').' · '.(fcc_mw_languages()[fcc_mw_locale($s)]??'English');
}
function fcc_mw_page($controller,int $uid): void {
 if(!fcc_mw_ready())throw_404();$sid=(int)($_POST['session_id']??$_GET['session_id']??0);$s=null;$series=null;$error=null;
 if($sid){$s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[$sid]);if(!$s)throw_404();try{$series=fcc_mw_require_owner($uid,$s,true);}catch(InvalidArgumentException $e){throw_404();}}
 $d=$s?fcc_event_definition($s):[];
 $zone=fcc_partner_one('SELECT timezone FROM users WHERE user_id=?',[$uid])['timezone']??'Europe/Zagreb';if(!in_array($zone,DateTimeZone::listIdentifiers(),true))$zone='Europe/Zagreb';if(fcc_locale()==='sl')$zone='Europe/Ljubljana';
 $values=$s?fcc_webinar_personal_values($s)+$d:['custom_timezone'=>$zone,'custom_time'=>'20:00','custom_duration'=>60,'language'=>fcc_locale(),'repeat'=>'once','audience_scope'=>'personal','request_key'=>bin2hex(random_bytes(16))];
 $values=array_replace($values,$_POST);
 if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
   if(!is_string($_POST['token']??null)||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new InvalidArgumentException(fcc_mw_t('stale'));
   $action=$_POST['action']??'';
   if(!in_array($action,['managed_save','managed_cancel'],true))throw new InvalidArgumentException(fcc_mw_t('unavailable'));
   if($action==='managed_cancel'&&($_POST['confirm_cancel']??'')!=='1')throw new InvalidArgumentException(fcc_mw_t('confirm'));
   if($sid){fcc_event_change($uid,$sid,(int)($_POST['revision']??0),$_POST,$action==='managed_cancel'?'cancelled':'scheduled',$_POST['scope']??'one');}
   else{if($action!=='managed_save')throw new InvalidArgumentException(fcc_mw_t('unavailable'));$s=fcc_mw_create($uid,$_POST);$sid=(int)$s['id'];}
   redirect('partner-webinar?view=manage&session_id='.$sid.'&saved=1');
  }catch(InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}catch(Throwable $e){error_log('Managed webinar: '.get_class($e).' '.$e->getMessage());$error=fcc_mw_t('failed');http_response_code(500);}
 }
 $slots=$series?fcc_partner_rows('SELECT id,title,starts_at_utc,timezone,status FROM fcc_webinar_sessions WHERE series_id=? ORDER BY starts_at_utc LIMIT 100',[(int)$series['id']]):[];
 $count=$s?(int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_webinar_registrations WHERE session_id=? AND status='registered'",[$sid])['n']:0;
 \Altum\Title::set(fcc_mw_t('heading'));$view=new \Altum\View('partner/webinar-managed',(array)$controller);
 $controller->add_view_content('content',$view->run(['session'=>$s,'series'=>$series,'slots'=>$slots,'values'=>$values,'error'=>$error,'count'=>$count,'pro'=>fcc_webinar_pro($uid)]));
}
