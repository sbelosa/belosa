<?php
/** Custom code: FC-2026-10-15: offline webinar removal and current-list regression guard. */
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);

$source=$argv[1]??dirname(__DIR__);
$output=$argv[2]??sys_get_temp_dir().'/fcc-webinar-lifecycle-'.getmypid().'.json';
$actual=[
 'app/helpers/fcc_webinar_events.php'=>['fcc_event_definition','fcc_event_now','fcc_event_transaction','fcc_event_admin','fcc_event_audit','fcc_event_materialize','fcc_event_list','fcc_event_slot','fcc_event_apply_change','fcc_event_remove','fcc_event_calendar'],
 'app/helpers/fcc_webinar_managed.php'=>['fcc_mw_overview'],
 'app/helpers/fcc_webinar_personal.php'=>['fcc_webinar_personal_overview'],
 'app/helpers/fcc_webinar_event_pages.php'=>['fcc_event_member_page'],
];
function actual_function(string $path,string $name):string {
 $tokens=token_get_all(file_get_contents($path));$count=count($tokens);
 for($i=0;$i<$count;$i++){
  if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;
  $j=$i+1;while($j<$count&&((is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE)||$tokens[$j]==='&'))$j++;
  if(!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING||$tokens[$j][1]!==$name)continue;
  $text='';$depth=0;$opened=false;
  for($k=$i;$k<$count;$k++){$token=$tokens[$k];$text.=is_array($token)?$token[1]:$token;if($token==='{'){$depth++;$opened=true;}elseif($token==='}'&&$opened&&--$depth===0)return $text;}
 }
 throw new RuntimeException('Missing required actual function '.$name);
}
$before=[];$functionHashes=[];foreach($actual as $file=>$names){$before[$file]=hash_file('sha256',$source.'/'.$file);foreach($names as $name){$code=actual_function($source.'/'.$file,$name);$functionHashes[$name]=hash('sha256',$code);eval($code);}}
$adminFile='app/controllers/admin/AdminWebinars.php';$before[$adminFile]=hash_file('sha256',$source.'/'.$adminFile);

/* Explicit boundary substitutes. Database queries are allowlisted and never leave memory. */
eval('namespace Altum; class Csrf {public static function get(): string {return "synthetic-csrf";}} class Authentication {public static function guard(): void {} public static function is_admin(): bool {return $GLOBALS["f"]["admins"][$GLOBALS["auth_uid"]]??false;}} class Title {public static function set(string $v): void {}} class Router {public static array $controller_settings=[];} class View {public function __construct(...$args){} public function run(array $data): string {$GLOBALS["render"]=$data;return "";}}');
final class FixtureRedirect extends RuntimeException {}
final class FixtureController {public object $user;public function __construct(int $uid){$this->user=(object)['user_id'=>$uid];}public function add_view_content(string $name,string $content):void{}}
eval('namespace Altum\\Controllers; class Controller {public object $user;public function __construct(int $uid){$this->user=(object)["user_id"=>$uid];}public function add_view_content(string $name,string $content):void{}}');
defined('ALTUMCODE')||define('ALTUMCODE',true);$adminCode=file_get_contents($source.'/'.$adminFile);eval(substr($adminCode,5));
final class FixtureDatabase {
 private ?array $snapshot=null;
 public function begin_transaction():void{$GLOBALS['db_stats']['begin']++;$this->snapshot=$GLOBALS['f'];}
 public function commit():void{$GLOBALS['db_stats']['commit']++;$this->snapshot=null;}
 public function rollback():void{$GLOBALS['db_stats']['rollback']++;if($this->snapshot!==null)$GLOBALS['f']=$this->snapshot;$this->snapshot=null;}
}
function database():FixtureDatabase {static $db;return $db??=new FixtureDatabase();}
function fcc_t(string $copy,array $replacements=[],?string $locale=null):string{return $copy;}
function fcc_event_manage_t(string $key):string{return 'Synthetic admin removal';}
function fcc_webinar_json(array $data):string{return json_encode($data,JSON_THROW_ON_ERROR);}
function fcc_partner_choice($value,array $options):string {if(!is_string($value)||!isset($options[$value]))throw new InvalidArgumentException('Invalid choice');return $value;}
function fcc_event_defaults(string $kind='business'):array{return ['kind'=>'business','title'=>'Synthetic event','description'=>'Synthetic description','timezone'=>'Europe/Zagreb','mode'=>'online','audience'=>'team','team_root'=>'','selected'=>[],'repeat'=>'weekly','duration'=>60,'reminders'=>[],'education'=>0,'announce'=>0];}
function fcc_events_enabled():bool{return $GLOBALS['f']['enabled'];}
function fcc_mw_ready():bool{return $GLOBALS['f']['managed_ready'];}
function fcc_webinar_personal_ready():bool{return $GLOBALS['f']['personal_ready'];}
function fcc_event_access(int $uid,array $session,?DateTimeImmutable $now=null):array{return ['allowed'=>$GLOBALS['f']['can_read']];}
function fcc_event_invitable(int $uid,array $session,?DateTimeImmutable $now=null):bool{return !empty($session['guests']);}
function fcc_webinar_title(array $session):string{return $session['title'];}
function fcc_event_occurrences(array $definition,?DateTimeImmutable $now=null):array{return $GLOBALS['f']['occurrences'];}
function fcc_event_collision(...$args):void {throw new LogicException('Unexpected collision lookup or event creation');}
function forever_business_vip_webinar_url():string{return 'https://forevercard.club/vip-edukacija';}
function fcc_webinar_local():bool{return true;}
function fcc_webinar_job(int $registration,string $kind,int $revision,string $at):void {$GLOBALS['f']['notices'][]=[$registration,$kind,$revision,$at];}
function fcc_webinar_schedule(array $registration,array $session,?DateTimeImmutable $now=null):void {$GLOBALS['f']['reschedules'][]=[$registration['id'],$session['id'],$session['status']];}
function fcc_event_job(array $session,int $uid,string $kind,DateTimeImmutable $due,DateTimeImmutable $expires,?string $key=null,?DateTimeImmutable $now=null):void {$GLOBALS['f']['member_notices'][]=[$session['id'],$uid,$kind,$session['revision']];}
function fcc_event_types():array{return ['business'=>'Synthetic event'];}
function fcc_event_rsvp_ready():bool{return false;}
function url(string $path):string{return 'https://forevercard.club/'.$path;}
function redirect(string $path):void {if(Fiber::getCurrent()!==null){Fiber::suspend($path);return;}throw new FixtureRedirect($path);}
function throw_404():void{throw new RuntimeException('Synthetic 404');}
function current_row(array $row):bool{return $row['status']==='scheduled'&&$row['ends_at_utc']>$GLOBALS['f']['db_now'];}
function ordered_rows(array $rows,bool $descending=false):array {usort($rows,fn($a,$b)=>($descending?-1:1)*strcmp($a['starts_at_utc'],$b['starts_at_utc'])?:($a['id']<=>$b['id']));return $rows;}
function fcc_partner_one(string $sql,array $args=[]):?array {
 $f=&$GLOBALS['f'];$GLOBALS['reads'][]=[$sql,$args];
 if($sql==='SELECT user_id FROM users WHERE user_id=? AND status=1 AND type=1')return !empty($f['admins'][$args[0]])?['user_id'=>$args[0]]:null;
 if($sql==="SELECT GET_LOCK('fcc_webinar_calendar',5) ok")return ['ok'=>$f['lock']?1:0];
 if(in_array($sql,['SELECT * FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL FOR UPDATE','SELECT * FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL'],true)){$s=$f['sessions'][$args[0]]??null;return $s&&$s['series_id']!==null?$s:null;}
 if(in_array($sql,['SELECT * FROM fcc_webinar_series WHERE id=? FOR UPDATE','SELECT * FROM fcc_webinar_series WHERE id=?'],true))return $f['series'][$args[0]]??null;
 if($sql==='SELECT * FROM fcc_webinar_sessions WHERE id=?')return $f['sessions'][$args[0]]??null;
 if($sql==='SELECT id FROM fcc_webinar_sessions WHERE series_key=? AND occurrence_key=?'){foreach($f['sessions'] as $s)if($s['series_key']===$args[0]&&$s['occurrence_key']===$args[1])return ['id'=>$s['id']];return null;}
 if($sql==='SELECT * FROM fcc_webinar_member_access WHERE session_id=? AND user_id=?')return null;
 throw new LogicException('Unallowlisted one query: '.$sql);
}
function fcc_partner_rows(string $sql,array $args=[]):array {
 $f=&$GLOBALS['f'];$GLOBALS['reads'][]=[$sql,$args];
 if($sql==='SELECT * FROM fcc_webinar_sessions WHERE series_id=? AND (ends_at_utc>? OR id=?) FOR UPDATE')return array_values(array_filter($f['sessions'],fn($s)=>$s['series_id']===$args[0]&&($s['ends_at_utc']>$args[1]||$s['id']===$args[2])));
 if($sql==="SELECT * FROM fcc_webinar_registrations WHERE session_id=? AND status='registered'")return array_values(array_filter($f['registrations'],fn($r)=>$r['session_id']===$args[0]&&$r['status']==='registered'));
 if($sql==="SELECT user_id FROM fcc_webinar_member_access WHERE session_id=? AND state='eligible'")return array_values(array_filter($f['access'],fn($r)=>$r['session_id']===$args[0]&&$r['state']==='eligible'));
 if($sql==='SELECT * FROM fcc_webinar_series ORDER BY updated_at DESC LIMIT 100')return array_values($f['series']);
 if($sql==='SELECT user_id,name FROM users WHERE status=1 ORDER BY name LIMIT 1000')return [];
 if($sql==='SELECT * FROM fcc_webinar_audit WHERE series_id=? ORDER BY id DESC LIMIT 15')return [];
 if(str_starts_with($sql,'SELECT * FROM fcc_webinar_sessions WHERE series_id IS NOT NULL')){
  if(str_contains($sql,"status='scheduled' AND ends_at_utc>UTC_TIMESTAMP()"))return ordered_rows(array_values(array_filter($f['sessions'],fn($s)=>$s['series_id']!==null&&(!$args||$s['series_id']===$args[0])&&current_row($s))));
  if(!str_contains($sql,"status='scheduled' AND ends_at_utc>?")||count($args)!==1)throw new LogicException('Current event query must use the strict UTC end boundary');
  return ordered_rows(array_values(array_filter($f['sessions'],fn($s)=>$s['series_id']!==null&&$s['status']==='scheduled'&&$s['ends_at_utc']>$args[0])));
 }
 if(str_starts_with($sql,'SELECT s.*,w.audience_scope,w.organizer_user_id,')){
  $archive=str_contains($sql,"NOT (x.status='scheduled' AND x.ends_at_utc>UTC_TIMESTAMP())");
  if(!str_contains($sql,"x.status='scheduled' AND x.ends_at_utc>UTC_TIMESTAMP()")||str_contains($sql,'COALESCE')||!str_contains($sql,'x.id LIMIT 1'))throw new LogicException('Managed overview must choose an active or archived row deterministically');
  $rows=[];foreach($f['series'] as $series){if($series['organizer_user_id']!==$args[0])continue;$sessions=array_filter($f['sessions'],fn($s)=>$s['series_id']===$series['id']&&(current_row($s)!==$archive));$sessions=ordered_rows(array_values($sessions),$archive);if($sessions)$rows[]=$sessions[0];}return ordered_rows($rows,$archive);
 }
 if(str_starts_with($sql,'SELECT s.*,(SELECT COUNT(*) FROM fcc_webinar_registrations')){
  $archive=str_contains($sql,"NOT (s.status='scheduled' AND s.ends_at_utc>UTC_TIMESTAMP())");
  if(!str_contains($sql,"s.status='scheduled' AND s.ends_at_utc>UTC_TIMESTAMP()")||!str_contains($sql,'s.series_id IS NULL AND '))throw new LogicException('Personal overview must filter scheduled rows through their UTC end');
  return ordered_rows(array_values(array_filter($f['sessions'],fn($s)=>$s['owner_user_id']===$args[0]&&$s['series_id']===null&&(current_row($s)!==$archive))),$archive);
 }
 throw new LogicException('Unallowlisted rows query: '.$sql);
}
function fcc_partner_query(string $sql,array $args=[]):void {
 $f=&$GLOBALS['f'];$GLOBALS['operations'][]=[$sql,$args];
 if($sql==="SELECT RELEASE_LOCK('fcc_webinar_calendar')"){$GLOBALS['db_stats']['release']++;return;}
 $GLOBALS['write_count']++;if($GLOBALS['throw_after']!==null&&$GLOBALS['write_count']===$GLOBALS['throw_after'])throw new RuntimeException('Synthetic storage failure');
 if($sql==='UPDATE fcc_webinar_sessions SET title=?,starts_at_utc=?,ends_at_utc=?,timezone=?,access_url=?,status=?,event_json=?,revision=revision+1,is_exception=1,member_cursor=0,updated_at=UTC_TIMESTAMP() WHERE id=?'){$s=&$f['sessions'][$args[7]];foreach(['title','starts_at_utc','ends_at_utc','timezone','access_url','status','event_json'] as $i=>$key)$s[$key]=$args[$i];$s['revision']++;$s['is_exception']=1;$s['member_cursor']=0;return;}
 if($sql==="UPDATE fcc_webinar_event_jobs SET state='cancelled',updated_at=UTC_TIMESTAMP() WHERE session_id=? AND state='pending'"){foreach($f['event_jobs'] as &$j)if($j['session_id']===$args[0]&&$j['state']==='pending')$j['state']='cancelled';return;}
 if($sql==="UPDATE fcc_partner_push_deliveries p JOIN fcc_partner_notifications n ON n.id=p.notification_id SET p.status='skipped',p.last_code='event_changed' WHERE n.event_key LIKE ? AND p.status IN ('pending','sending')"){$prefix=substr($args[0],0,-1);foreach($f['push'] as &$p)if(str_starts_with($p['event_key'],$prefix)&&in_array($p['status'],['pending','sending'],true)){$p['status']='skipped';$p['last_code']='event_changed';}return;}
 if($sql==="UPDATE fcc_webinar_jobs j JOIN fcc_webinar_registrations r ON r.id=j.registration_id SET j.state='cancelled',j.updated_at=UTC_TIMESTAMP() WHERE r.session_id=? AND j.state IN ('pending','processing') AND j.kind NOT IN ('project','owner_confirmation')"){foreach($f['guest_jobs'] as &$j)if($f['registrations'][$j['registration_id']]['session_id']===$args[0]&&in_array($j['state'],['pending','processing'],true)&&!in_array($j['kind'],['project','owner_confirmation'],true))$j['state']='cancelled';return;}
 if($sql==="UPDATE fcc_webinar_series SET status='paused',revision=revision+1,updated_at=UTC_TIMESTAMP() WHERE id=?"){$f['series'][$args[0]]['status']='paused';$f['series'][$args[0]]['revision']++;return;}
 if($sql==='INSERT INTO fcc_webinar_audit (actor_id,action,series_id,session_id,detail_json,created_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP())'){$f['audit'][]=$args;return;}
 throw new LogicException('Unallowlisted write query: '.$sql);
}
function session_row(int $id,int $slot=0,?int $series=10,string $start='2031-04-20 17:00:00',string $end='2031-04-20 18:00:00',string $status='scheduled'):array {
 return ['id'=>$id,'series_id'=>$series,'owner_user_id'=>$series===null?7:null,'series_key'=>'synthetic-series','occurrence_key'=>'slot-'.$slot,'title'=>'Synthetic '.$id,'starts_at_utc'=>$start,'ends_at_utc'=>$end,'timezone'=>'Europe/Zagreb','access_url'=>'https://forevercard.club/vip-edukacija','status'=>$status,'calendar_uid'=>'synthetic-'.$id.'@forevercard.club','revision'=>2,'is_exception'=>0,'member_cursor'=>27,'event_json'=>fcc_webinar_json(['kind'=>'business','slot_index'=>$slot,'title'=>'Synthetic '.$id,'description'=>'Synthetic description','date'=>substr($start,0,10),'timezone'=>'Europe/Zagreb','mode'=>'online','repeat'=>'weekly'])];
}
function reset_fixture():void {
 $GLOBALS['f']=['admins'=>[1=>true,2=>false],'lock'=>true,'enabled'=>true,'managed_ready'=>false,'personal_ready'=>true,'can_read'=>true,'db_now'=>'2031-04-15 17:00:00','sessions'=>[1=>session_row(1),2=>session_row(2,1,10,'2031-04-27 17:00:00','2031-04-27 18:00:00')],
  'series'=>[10=>['id'=>10,'series_key'=>'synthetic-series','revision'=>3,'status'=>'published','organizer_user_id'=>7,'definition_json'=>fcc_webinar_json(['repeat'=>'weekly','mode'=>'online'])]],
  'registrations'=>[101=>['id'=>101,'session_id'=>1,'status'=>'registered','email_verified_at'=>null,'reminder_opt_in'=>1,'token_hash'=>'synthetic-token-101'],102=>['id'=>102,'session_id'=>2,'status'=>'registered','email_verified_at'=>'2031-04-01 10:00:00','reminder_opt_in'=>1,'token_hash'=>'synthetic-token-102'],103=>['id'=>103,'session_id'=>1,'status'=>'cancelled','email_verified_at'=>null,'reminder_opt_in'=>0,'token_hash'=>'synthetic-token-103']],
  'access'=>[['session_id'=>1,'user_id'=>7,'state'=>'eligible'],['session_id'=>2,'user_id'=>8,'state'=>'eligible']],
  'event_jobs'=>[['session_id'=>1,'state'=>'pending'],['session_id'=>1,'state'=>'delivered'],['session_id'=>2,'state'=>'pending']],
  'push'=>[['event_key'=>'webinar:calendar:1:2:reminder60','status'=>'pending'],['event_key'=>'webinar:calendar:1:2:published','status'=>'sending'],['event_key'=>'webinar:calendar:1:2:published','status'=>'sent'],['event_key'=>'webinar:calendar:2:2:reminder60','status'=>'pending']],
  'guest_jobs'=>[['registration_id'=>101,'kind'=>'reminder60','state'=>'pending'],['registration_id'=>101,'kind'=>'confirmation','state'=>'processing'],['registration_id'=>101,'kind'=>'project','state'=>'pending'],['registration_id'=>101,'kind'=>'owner_confirmation','state'=>'pending'],['registration_id'=>101,'kind'=>'reminder60','state'=>'sent'],['registration_id'=>102,'kind'=>'reminder60','state'=>'pending']],
  'notices'=>[],'member_notices'=>[],'reschedules'=>[],'audit'=>[],'occurrences'=>[['key'=>'slot-0','index'=>0,'start'=>'2031-04-20 17:00:00','end'=>'2031-04-20 18:00:00']]];
 $GLOBALS['db_stats']=['begin'=>0,'commit'=>0,'rollback'=>0,'release'=>0];$GLOBALS['reads']=[];$GLOBALS['operations']=[];$GLOBALS['write_count']=0;$GLOBALS['throw_after']=null;$GLOBALS['render']=null;$GLOBALS['auth_uid']=1;$_GET=[];$_POST=[];$_SERVER['REQUEST_METHOD']='GET';http_response_code(200);
}
function check(bool $pass,string $label):void {$GLOBALS['checks'][]=['pass'=>$pass,'label'=>$label];}
function scenario(string $label,callable $fn):void {$GLOBALS['scenarios']++;reset_fixture();try{$fn();}catch(Throwable $e){check(false,$label.'_unexpected_'.get_class($e).'_'.$e->getMessage());}}
function rejects(callable $fn,string $label):void {try{$fn();check(false,$label.'_must_reject');}catch(InvalidArgumentException $e){check(true,$label.'_rejected');}}
function no_change(array $before,string $label):void {check($before===$GLOBALS['f'],$label.'_state_unchanged');}
function rows_ids(array $rows):array{return array_map(fn($s)=>(int)$s['id'],$rows);}
function remove_input(array $extra=[]):array {return $extra+['confirm_remove'=>'1','remove_scope'=>'one'];}
$checks=[];$scenarios=0;$now=new DateTimeImmutable('2031-04-15 17:00:00',new DateTimeZone('UTC'));

scenario('admin_only',function()use($now){$before=$GLOBALS['f'];rejects(fn()=>fcc_event_remove(2,1,2,remove_input(),$now),'member_cannot_remove');no_change($before,'non_admin');check($GLOBALS['db_stats']['begin']===0&&$GLOBALS['write_count']===0,'admin_authorization_before_transaction_and_writes');});
foreach([null,'',1,true,[]] as $confirm)scenario('explicit_confirmation',function()use($now,$confirm){$before=$GLOBALS['f'];rejects(fn()=>fcc_event_remove(1,1,2,['confirm_remove'=>$confirm],$now),'explicit_string_confirmation_required');no_change($before,'missing_confirmation');check($GLOBALS['db_stats']['begin']===0,'confirmation_checked_before_lock');});
scenario('invalid_scope',function()use($now){rejects(fn()=>fcc_event_remove(1,1,2,remove_input(['remove_scope'=>'all']),$now),'invalid_scope');check($GLOBALS['write_count']===0,'invalid_scope_no_writes');});
scenario('busy_lock',function()use($now){$GLOBALS['f']['lock']=false;rejects(fn()=>fcc_event_remove(1,1,2,remove_input(),$now),'busy_lock');check($GLOBALS['db_stats']===['begin'=>0,'commit'=>0,'rollback'=>0,'release'=>0],'busy_lock_not_released_or_used');});
foreach(['stale','missing_session','personal_session','missing_series'] as $case)scenario($case,function()use($now,$case){if($case==='missing_session')unset($GLOBALS['f']['sessions'][1]);if($case==='personal_session')$GLOBALS['f']['sessions'][1]['series_id']=null;if($case==='missing_series')unset($GLOBALS['f']['series'][10]);$before=$GLOBALS['f'];rejects(fn()=>fcc_event_remove(1,1,$case==='stale'?1:2,remove_input(),$now),$case);no_change($before,$case);check($GLOBALS['db_stats']['rollback']===1&&$GLOBALS['db_stats']['release']===1&&$GLOBALS['write_count']===0,$case.'_rollback_and_release_without_writes');});
foreach([null,2,4] as $revision)scenario('future_series_revision',function()use($now,$revision){$before=$GLOBALS['f'];rejects(fn()=>fcc_event_remove(1,1,2,remove_input(['remove_scope'=>'future','series_revision'=>$revision]),$now),'future_requires_current_series_revision');no_change($before,'stale_series');check($GLOBALS['write_count']===0,'stale_series_no_writes');});
scenario('one_occurrence',function()use($now){
 $before=$GLOBALS['f'];fcc_event_remove(1,1,2,remove_input(),$now);$f=$GLOBALS['f'];
 check($f['sessions'][1]['status']==='cancelled'&&$f['sessions'][1]['revision']===3,'one_occurrence_cancelled_and_revision_advanced');
 foreach(['id','series_id','series_key','occurrence_key','calendar_uid','starts_at_utc','ends_at_utc','access_url'] as $key)check($f['sessions'][1][$key]===$before['sessions'][1][$key],'one_occurrence_preserves_'.$key);
 check($f['sessions'][2]===$before['sessions'][2]&&$f['series']===$before['series'],'other_occurrence_and_series_unchanged');
 check($f['registrations']===$before['registrations']&&$f['access']===$before['access'],'registrations_tokens_and_access_history_preserved');
 check(array_column($f['event_jobs'],'state')===['cancelled','delivered','pending'],'only_pending_target_event_jobs_cancelled');
 check(array_column($f['push'],'status')===['skipped','skipped','sent','pending'],'pending_and_sending_target_pushes_invalidated');
 check(array_column($f['guest_jobs'],'state')===['cancelled','cancelled','pending','pending','sent','pending'],'guest_reminders_cancelled_preserving_project_owner_and_sent_jobs');
 check($f['notices']===[[101,'cancelled',3,'2031-04-15 17:00:00']]&&$f['member_notices']===[[1,7,'cancelled',3]],'future_cancellation_notices_only_registered_and_eligible_people');
 check($GLOBALS['db_stats']===['begin'=>1,'commit'=>1,'rollback'=>0,'release'=>1],'successful_removal_commit_and_release');
 check(count($f['audit'])===2&&$f['audit'][0][1]==='change'&&$f['audit'][1][1]==='remove','change_and_admin_removal_audited');
 check(!array_filter($GLOBALS['operations'],fn($q)=>preg_match('/^DELETE\b/i',$q[0])),'no_session_registration_or_history_hard_delete');
 $calendar=fcc_event_calendar(1,$f['sessions'][1]);check(str_contains($calendar,'STATUS:CANCELLED')&&str_contains($calendar,'UID:'.$before['sessions'][1]['calendar_uid'])&&str_contains($calendar,'SEQUENCE:3'),'calendar_cancellation_preserves_uid_and_updates_sequence');
 $count=count($f['sessions']);fcc_event_materialize($f['series'][10],$now);check(count($GLOBALS['f']['sessions'])===$count&&$GLOBALS['f']['sessions'][1]['status']==='cancelled','single_removed_occurrence_not_regenerated_from_stable_occurrence_key');
});
scenario('ongoing_remove',function()use($now){$GLOBALS['f']['sessions'][1]['starts_at_utc']='2031-04-15 16:30:00';$GLOBALS['f']['sessions'][1]['ends_at_utc']='2031-04-15 17:30:00';fcc_event_remove(1,1,2,remove_input(),$now);check($GLOBALS['f']['sessions'][1]['status']==='cancelled'&&count($GLOBALS['f']['notices'])===1,'admin_can_remove_ongoing_event_with_cancellation_notice');});
foreach(['2031-04-15 17:00:00','2031-04-15 16:59:59'] as $end)scenario('ended_remove',function()use($now,$end){$GLOBALS['f']['sessions'][1]['starts_at_utc']='2031-04-15 16:00:00';$GLOBALS['f']['sessions'][1]['ends_at_utc']=$end;fcc_event_remove(1,1,2,remove_input(),$now);$f=$GLOBALS['f'];check($f['sessions'][1]['status']==='cancelled','ended_event_can_be_archived_by_admin');check($f['notices']===[]&&$f['member_notices']===[]&&$f['reschedules']===[],'ended_removal_no_new_notifications_or_rescheduling');check($f['event_jobs'][0]['state']==='cancelled'&&$f['push'][0]['status']==='skipped','ended_removal_invalidates_pending_delivery');});
scenario('future_scope',function()use($now){
 $f=&$GLOBALS['f'];$f['sessions'][0]=session_row(0,-1,10,'2031-04-20 15:00:00','2031-04-20 16:00:00');$f['sessions'][3]=session_row(3,2,10,'2031-05-04 17:00:00','2031-05-04 18:00:00','cancelled');$f['sessions'][4]=session_row(4,3,10,'2031-04-01 15:00:00','2031-04-01 16:00:00');$f['sessions'][5]=session_row(5,2,11);$before=$f;
 fcc_event_remove(1,1,2,remove_input(['remove_scope'=>'future','series_revision'=>'3']),$now);$f=$GLOBALS['f'];check($f['series'][10]['status']==='paused'&&$f['series'][10]['revision']===4,'future_removal_pauses_recurrence_and_advances_series_revision');check($f['sessions'][1]['status']==='cancelled'&&$f['sessions'][2]['status']==='cancelled','future_removal_cancels_selected_and_later_current_slots');foreach([0,3,4,5] as $id)check($f['sessions'][$id]===$before['sessions'][$id],'future_scope_preserves_outside_or_already_cancelled_'.$id);
 check($f['registrations']===$before['registrations'],'future_scope_preserves_registrations');$reads=count($GLOBALS['reads']);fcc_event_materialize($f['series'][10],$now);check(count($GLOBALS['reads'])===$reads+1,'paused_series_materialization_stops_before_occurrence_generation');
});
scenario('cancelled_idempotency',function()use($now){$GLOBALS['f']['sessions'][1]['status']='cancelled';$before=$GLOBALS['f']['sessions'][1];fcc_event_remove(1,1,2,remove_input(),$now);check($GLOBALS['f']['sessions'][1]===$before&&$GLOBALS['f']['notices']===[]&&$GLOBALS['f']['member_notices']===[],'already_cancelled_session_not_revised_or_notified_again');});
foreach([2,5,7] as $failure)scenario('rollback_storage_failure',function()use($now,$failure){$before=$GLOBALS['f'];$GLOBALS['throw_after']=$failure;try{fcc_event_remove(1,1,2,remove_input(['remove_scope'=>'future','series_revision'=>'3']),$now);check(false,'storage_failure_must_throw');}catch(RuntimeException $e){check($e->getMessage()==='Synthetic storage failure','storage_failure_propagated');}no_change($before,'failed_multiwrite_removal');check($GLOBALS['db_stats']===['begin'=>1,'commit'=>0,'rollback'=>1,'release'=>1],'failed_removal_rolls_back_and_releases_lock');});

scenario('current_event_list',function()use($now){$f=&$GLOBALS['f'];$f['sessions']=[1=>session_row(1,0,10,'2031-04-15 16:30:00','2031-04-15 17:30:00'),2=>session_row(2,1,10,'2031-04-15 16:00:00','2031-04-15 17:00:00'),3=>session_row(3,2,10,'2031-04-20 17:00:00','2031-04-20 18:00:00','cancelled'),4=>session_row(4,3,10,'2031-04-20 17:00:00','2031-04-20 18:00:00','postponed'),5=>session_row(5,4,10,'2031-04-20 17:00:00','2031-04-20 18:00:00','draft'),6=>session_row(6,5,10),7=>session_row(7,6,null)];check(rows_ids(fcc_event_list(7,$now))===[1,6],'active_list_includes_ongoing_and_future_excludes_end_boundary_and_nonpublished');$f['can_read']=false;check(fcc_event_list(7,$now)===[],'active_list_still_enforces_member_access');});
scenario('timezone_list_boundary',function(){$GLOBALS['f']['sessions']=[1=>session_row(1,0,10,'2031-10-26 00:30:00','2031-10-26 01:30:00')];check(rows_ids(fcc_event_list(7,new DateTimeImmutable('2031-10-26T02:15:00+01:00')))===[1],'offset_aware_now_normalized_to_utc_during_repeated_hour');check(fcc_event_list(7,new DateTimeImmutable('2031-10-26T02:30:00+01:00'))===[],'event_disappears_exactly_at_utc_end_during_repeated_hour');});
scenario('personal_overview',function(){ $f=&$GLOBALS['f'];$f['sessions']=[1=>session_row(1,0,null,'2031-04-15 16:00:00','2031-04-15 18:00:00'),2=>session_row(2,1,null,'2031-04-15 16:00:00','2031-04-15 17:00:00'),3=>session_row(3,2,null,'2031-04-20 17:00:00','2031-04-20 18:00:00','cancelled'),4=>session_row(4,3,null),5=>session_row(5,4,10)];$f['sessions'][6]=session_row(6,5,null);$f['sessions'][6]['owner_user_id']=8;check(rows_ids(fcc_webinar_personal_overview(7))===[1,4],'personal_current_overview_ongoing_and_future_only');check(rows_ids(fcc_webinar_personal_overview(7,true))===[3,2],'personal_archive_keeps_ended_and_cancelled_only_for_owner');$f['personal_ready']=false;check(fcc_webinar_personal_overview(7)===[],'personal_not_ready_returns_empty');});
scenario('managed_overview',function(){ $f=&$GLOBALS['f'];$f['managed_ready']=true;$f['sessions']=[1=>session_row(1,0,10,'2031-04-15 16:00:00','2031-04-15 18:00:00'),2=>session_row(2,1,10),3=>session_row(3,2,11,'2031-04-15 16:00:00','2031-04-15 17:00:00'),4=>session_row(4,3,12,'2031-04-21 17:00:00','2031-04-21 18:00:00','cancelled'),5=>session_row(5,4,13)];foreach([11,12,13] as $id)$f['series'][$id]=array_replace($f['series'][10],['id'=>$id]);$f['series'][13]['organizer_user_id']=8;check(rows_ids(fcc_mw_overview(7))===[1],'managed_current_overview_prefers_ongoing_and_excludes_ended_only_series');check(rows_ids(fcc_mw_overview(7,true))===[4,3],'managed_archive_is_explicit_and_owner_scoped');$f['managed_ready']=false;check(fcc_mw_overview(7)===[],'managed_not_ready_returns_empty');});

foreach([null,'invalid',[]] as $token)scenario('member_page_csrf',function()use($token){$before=$GLOBALS['f'];$_SERVER['REQUEST_METHOD']='POST';$_POST=['action'=>'remove_event','revision'=>2,'token'=>$token]+remove_input();fcc_event_member_page(new FixtureController(1),[1]);no_change($before,'invalid_csrf');check(http_response_code()===422&&$GLOBALS['write_count']===0&&$GLOBALS['db_stats']['begin']===0,'member_page_rejects_missing_invalid_or_array_csrf_before_removal');});
scenario('member_page_nonadmin',function(){ $before=$GLOBALS['f'];$_SERVER['REQUEST_METHOD']='POST';$_POST=['action'=>'remove_event','revision'=>2,'token'=>'synthetic-csrf']+remove_input();fcc_event_member_page(new FixtureController(2),[1]);no_change($before,'member_page_nonadmin');check(http_response_code()===422&&$GLOBALS['write_count']===0,'valid_csrf_does_not_allow_member_admin_removal');});
scenario('member_page_valid_remove',function(){ $_SERVER['REQUEST_METHOD']='POST';$_POST=['action'=>'remove_event','revision'=>2,'token'=>'synthetic-csrf']+remove_input();try{fcc_event_member_page(new FixtureController(1),[1]);check(false,'valid_remove_redirect_expected');}catch(FixtureRedirect $e){check($e->getMessage()==='partner/webinars?removed=1'&&$GLOBALS['f']['sessions'][1]['status']==='cancelled','valid_admin_remove_reaches_actual_mutation_and_redirect');}});
scenario('get_never_removes',function(){ $before=$GLOBALS['f'];$_GET=['action'=>'remove_event','confirm_remove'=>'1'];fcc_event_member_page(new FixtureController(1),[1]);no_change($before,'get_request');check($GLOBALS['write_count']===0,'get_request_cannot_remove_webinar');});
foreach([null,'invalid',[]] as $token)scenario('admin_controller_csrf',function()use($token){$before=$GLOBALS['f'];$_SERVER['REQUEST_METHOD']='POST';$_POST=['action'=>'remove_event','session_id'=>1,'revision'=>2,'token'=>$token]+remove_input();(new \Altum\Controllers\AdminWebinars(1))->index();no_change($before,'admin_invalid_csrf');check(http_response_code()===422&&$GLOBALS['write_count']===0&&$GLOBALS['db_stats']['begin']===0,'actual_admin_controller_rejects_missing_invalid_or_array_csrf_before_removal');});
scenario('admin_controller_nonadmin',function(){$GLOBALS['auth_uid']=2;$before=$GLOBALS['f'];$_SERVER['REQUEST_METHOD']='POST';$_POST=['action'=>'remove_event','session_id'=>1,'revision'=>2,'token'=>'synthetic-csrf']+remove_input();try{(new \Altum\Controllers\AdminWebinars(2))->index();check(false,'admin_controller_member_must_reject');}catch(RuntimeException $e){check($e->getMessage()==='Synthetic 404','actual_admin_controller_denies_member_before_event_lookup');}no_change($before,'admin_route_nonadmin');check($GLOBALS['reads']===[]&&$GLOBALS['write_count']===0,'admin_route_authorization_before_reads_and_writes');});
scenario('admin_controller_valid_remove',function(){$_SERVER['REQUEST_METHOD']='POST';$_POST=['action'=>'remove_event','session_id'=>1,'revision'=>2,'token'=>'synthetic-csrf']+remove_input();$fiber=new Fiber(fn()=>(new \Altum\Controllers\AdminWebinars(1))->index());$redirect=$fiber->start();check($fiber->isSuspended()&&$redirect==='admin/webinars?removed=1'&&$GLOBALS['f']['sessions'][1]['status']==='cancelled','actual_admin_controller_valid_post_mutates_and_redirects');});

$after=[];foreach(array_keys($before) as $file)$after[$file]=hash_file('sha256',$source.'/'.$file);check($before===$after,'source_hashes_stable_during_fixture');
$failed=array_values(array_filter($checks,fn($c)=>!$c['pass']));$report=['status'=>$failed?'FAIL':'PASS','checks'=>count($checks),'passed'=>count($checks)-count($failed),'failed'=>count($failed),'scenarios'=>$scenarios,'source_sha256_before'=>$before,'source_sha256_after'=>$after,'actual_function_sha256'=>$functionHashes,'actual_admin_controller_sha256'=>hash('sha256',$adminCode),'details'=>$checks,'limits'=>['actual_named_functions_extracted_without_bootstrap'=>true,'actual_admin_controller_loaded_without_bootstrap'=>true,'db_boundary_adapter_allowlisted_in_memory'=>true,'query_predicates_checked_but_sql_engine_not_executed'=>true,'external_delivery_boundaries_recorded_without_sending'=>true,'member_access_boundary_stubbed'=>true,'production_db_calls'=>0,'api_calls'=>0,'network_calls'=>0]];
$json=json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;umask(0077);if(file_put_contents($output,$json)===false)throw new RuntimeException('Cannot save proof');chmod($output,0600);echo json_encode(['status'=>$report['status'],'checks'=>$report['checks'],'failed'=>$report['failed'],'scenarios'=>$scenarios,'report_sha256'=>hash('sha256',$json)],JSON_THROW_ON_ERROR).PHP_EOL;exit($failed?1:0);
