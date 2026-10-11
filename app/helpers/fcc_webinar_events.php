<?php
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_event_hub.php';
require_once __DIR__.'/fcc_seminars.php';
require_once __DIR__.'/fcc_webinar_managed.php';

function fcc_events_ready(): bool {
 static $ready;
 return $ready??=!!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_webinar_series'");
}
function fcc_events_enabled(): bool {
 return (getenv('FCC_PARTNER_ENABLED')==='1'||(fcc_rollout_managed()&&getenv('FCC_WEBINAR_INVITES_ENABLED')==='1'))&&fcc_events_ready()&&fcc_partner_notification_config('webinar_calendar_active',false)===true;
}
function fcc_event_types(): array {return array_map('fcc_t',['business'=>'Poslovna prilika','team'=>'Timski webinar','vip'=>'VIP webinar','seminar'=>'Seminar ili Dan uspjeha','other'=>'Važan događaj']);}
function fcc_event_audiences(): array {return array_map('fcc_t',['team'=>'Cijeli FCC tim','previous'=>'4 CC aktivan prethodni mjesec','current'=>'4 CC aktivan ovaj mjesec','both'=>'4 CC aktivan oba mjeseca','either'=>'4 CC aktivan barem jedan mjesec','selected'=>'Odabrani članovi']);}
function fcc_event_states(): array {return array_map('fcc_t',['draft'=>'Nacrt','scheduled'=>'Objavljen','postponed'=>'Novi termin uskoro','cancelled'=>'Otkazan']);}
function fcc_event_defaults(string $kind='business'): array {
 if(!isset(fcc_event_types()[$kind]))$kind='business';
 return fcc_seminar_defaults()+['kind'=>$kind,'title'=>fcc_event_types()[$kind],'description'=>'','mode'=>$kind==='seminar'?'in_person':'online','location'=>'','date'=>'','time'=>'19:00','duration'=>60,'repeat'=>'once','until'=>'','audience'=>$kind==='vip'?'previous':'team','selected'=>[],'team_root'=>'','guests'=>$kind==='business'?1:0,'reminders'=>$kind==='business'?[60]:($kind==='other'?[]:($kind==='seminar'?[10080,1440]:[1440,60])),'announce'=>0,'join_minutes'=>15,'close_minutes'=>0,'education'=>0];
}
function fcc_event_definition(array $s): array {return array_replace(fcc_event_defaults(),json_decode($s['event_json']??$s['definition_json']??'{}',true)?:[]);}
function fcc_event_now(?DateTimeImmutable $now=null): DateTimeImmutable {return ($now??new DateTimeImmutable())->setTimezone(new DateTimeZone('UTC'));}
function fcc_event_local_time(string $date,string $time,string $timezone='Europe/Zagreb'): DateTimeImmutable {
 $value=$date.' '.$time;$zone=new DateTimeZone($timezone);
 $d=DateTimeImmutable::createFromFormat('!Y-m-d H:i',$value,$zone);
 if(!$d||$d->format('Y-m-d H:i')!==$value)throw new InvalidArgumentException(fcc_t('Datum ili vrijeme nije valjano. Provjeri i promjenu sata.'));
 // Reject repeated wall-clock hours: administrators choose an unambiguous time.
 $matches=0;$ts=$d->getTimestamp();foreach([-3600,0,3600] as $offset)if((new DateTimeImmutable('@'.($ts+$offset)))->setTimezone($zone)->format('Y-m-d H:i')===$value)$matches++;
 if($matches!==1)throw new InvalidArgumentException(fcc_t('Odabrani sat pojavljuje se dvaput zbog promjene sata. Odaberi vrijeme izvan tog razdoblja.'));
 return $d;
}
function fcc_event_validate(array $in,bool $publishing=false,?DateTimeImmutable $now=null): array {
 $d=array_replace(fcc_event_defaults(fcc_partner_text($in['kind']??'business',20)),fcc_seminar_validate($in));
 $d['timezone']=$in['timezone']??'Europe/Zagreb';
 if(!is_string($d['timezone'])||!in_array($d['timezone'],DateTimeZone::listIdentifiers(),true))throw new InvalidArgumentException(fcc_t('Odaberi vremensku zonu.'));
 if(isset($in['access_url']))$d['access_url']=fcc_webinar_access_url($in['access_url']);
 if(isset($in['language']))$d['language']=fcc_partner_choice($in['language'],fcc_mw_languages());
 if($d['time_pending']){$in['time']='00:00';$in['duration']=1440;$in['reminders']=array_values(array_intersect((array)($in['reminders']??[]),[1440,10080]));}
 foreach(['title'=>180,'description'=>1600,'location'=>400,'date'=>10,'time'=>5,'until'=>10,'team_root'=>12] as $k=>$limit)$d[$k]=fcc_partner_text($in[$k]??$d[$k],$limit);
 if($d['title']==='')throw new InvalidArgumentException(fcc_t('Upiši naziv događaja.'));
 foreach(['kind'=>fcc_event_types(),'mode'=>['online'=>1,'in_person'=>1],'repeat'=>['once'=>1,'weekly'=>1],'audience'=>fcc_event_audiences()] as $k=>$options)$d[$k]=fcc_partner_choice($in[$k]??$d[$k],$options);
 foreach(['duration'=>[5,1440],'join_minutes'=>[0,120],'close_minutes'=>[0,10080]] as $k=>[$min,$max]){$d[$k]=filter_var($in[$k]??$d[$k],FILTER_VALIDATE_INT);if($d[$k]===false||$d[$k]<$min||$d[$k]>$max)throw new InvalidArgumentException(fcc_t('Provjeri trajanje i vremenske postavke.'));}
 foreach(['guests','announce','education'] as $k)$d[$k]=empty($in[$k])?0:1;
 if($d['kind']!=='business')$d['education']=0;
 if(in_array($d['kind'],['team','vip'],true))$d['guests']=0;
 $d['reminders']=array_values(array_unique(array_map('intval',(array)($in['reminders']??[]))));
 foreach($d['reminders'] as $m)if(!in_array($m,[15,60,1440,10080],true))throw new InvalidArgumentException(fcc_t('Odaberi ponuđene podsjetnike.'));
 rsort($d['reminders']);$d['selected']=array_values(array_unique(array_filter(array_map('intval',(array)($in['selected']??[])),fn($v)=>$v>0)));
 if($d['audience']==='selected'&&!$d['selected'])throw new InvalidArgumentException(fcc_t('Odaberi barem jednog člana.'));
 if($d['team_root']!==''&&!fcc_partner_one('SELECT fbo_id FROM forever_business_members WHERE fbo_id=?',[$d['team_root']]))throw new InvalidArgumentException(fcc_t('Odabrani tim nije pronađen.'));
 if($d['education']&&($d['audience']!=='team'||$d['team_root']!==''||!$d['guests']))throw new InvalidArgumentException(fcc_t('Događaj povezan s edukacijom mora biti dostupan cijelom timu i gostima.'));
 if($d['until']!==''&&!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$d['until']))throw new InvalidArgumentException(fcc_t('Provjeri datum kraja ponavljanja.'));
 if($d['until']!=='')fcc_event_local_time($d['until'],'12:00');
 if($d['mode']==='in_person'&&$d['location']==='')throw new InvalidArgumentException(fcc_t('Upiši mjesto održavanja.'));
 if($d['date']!==''){
  $start=fcc_event_local_time($d['date'],$d['time'],$d['timezone']??'Europe/Zagreb');
  if($publishing&&$start<=fcc_event_now($now))throw new InvalidArgumentException(fcc_t('Za objavu odaberi budući termin.'));
  if($d['until']!==''&&(fcc_partner_date($d['until'])<$d['date']))throw new InvalidArgumentException(fcc_t('Kraj ponavljanja mora biti nakon početnog datuma.'));
 }elseif($publishing)throw new InvalidArgumentException(fcc_t('Prije objave odaberi datum.'));
 return $d;
}
function fcc_event_occurrences(array $d,?DateTimeImmutable $now=null): array {
 if(!$d['date'])return [];
 $now=fcc_event_now($now);$first=fcc_event_local_time($d['date'],$d['time'],$d['timezone']??'Europe/Zagreb');$last=$now->setTimezone(new DateTimeZone($d['timezone']??'Europe/Zagreb'))->modify('+90 days')->setTime(23,59);
 $last=$first>$last?$first:$last;
 $rows=[];$date=$first->format('Y-m-d');$i=0;
 // Advancing calendar dates, not UTC seconds, preserves the Zagreb wall clock.
 if($d['repeat']==='weekly'&&$first<$now){$weeks=max(0,(int)floor(($now->getTimestamp()-$first->getTimestamp())/604800)-1);$date=$first->modify('+'.$weeks.' weeks')->format('Y-m-d');}
 do {
  $start=fcc_event_local_time($date,$d['time'],$d['timezone']??'Europe/Zagreb');
  if(($d['until']!==''&&$date>$d['until'])||($d['repeat']==='weekly'&&$start>$last))break;
  if($start>$now||$d['repeat']==='once')$rows[]=['key'=>$date,'index'=>(int)($d['first_index']??0)+(int)round((int)$first->setTime(0,0)->diff($start->setTime(0,0))->format('%r%a')/7),'start'=>$start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),'end'=>$start->modify('+'.$d['duration'].' minutes')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
  if($d['repeat']!=='weekly')break;
  $date=$start->modify('+1 week')->format('Y-m-d');
 }while(++$i<16);
 return $rows;
}
function fcc_event_transaction(callable $fn) {
 if(!(int)(fcc_partner_one("SELECT GET_LOCK('fcc_webinar_calendar',5) ok")['ok']??0))throw new InvalidArgumentException(fcc_t('Raspored se upravo ažurira. Pokušaj ponovno.'));
 $db=database();$db->begin_transaction();
 try{$result=$fn();$db->commit();return $result;}catch(Throwable $e){$db->rollback();throw $e;}finally{fcc_partner_query("SELECT RELEASE_LOCK('fcc_webinar_calendar')");}
}
function fcc_event_admin(int $uid): void {
 if(!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? AND status=1 AND type=1',[$uid]))throw new InvalidArgumentException(fcc_t('Samo administrator može uređivati događaje.'));
}
function fcc_event_audit(int $actor,string $action,?int $series,?int $session,array $detail): void {
 fcc_partner_query('INSERT INTO fcc_webinar_audit (actor_id,action,series_id,session_id,detail_json,created_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP())',[$actor,$action,$series,$session,fcc_webinar_json($detail)]);
}
function fcc_event_collision(string $start,string $end,int $exclude=0,?string $room=null): void {
 $room=fcc_mw_room($room??forever_business_vip_webinar_url());
 foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE status='scheduled' AND starts_at_utc<? AND ends_at_utc>? AND id<>? AND (JSON_UNQUOTE(JSON_EXTRACT(event_json,'$.mode'))='online' OR series_id IS NULL)",[$end,$start,$exclude]) as $r){
  $d=fcc_event_definition($r);$existing=!empty($d['managed'])||!empty($r['owner_user_id'])?$r['access_url']:forever_business_vip_webinar_url();
  if(fcc_mw_room($existing)===$room)throw new InvalidArgumentException(fcc_mw_t('collision'));
 }
}
function fcc_event_materialize(array $series,?DateTimeImmutable $now=null): void {
 $series=fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=? FOR UPDATE',[(int)$series['id']]);
 if(!$series)return;
 if($series['status']!=='published')return;
 $d=fcc_event_definition($series);
 foreach(fcc_event_occurrences($d,$now) as $o){
  if(fcc_partner_one('SELECT id FROM fcc_webinar_sessions WHERE series_key=? AND occurrence_key=?',[$series['series_key'],$o['key']]))continue;
  if($d['mode']==='online')fcc_event_collision($o['start'],$o['end'],0,!empty($d['managed'])?$d['access_url']:null);
  $occurrenceDefinition=$d;$occurrenceDefinition['slot_index']=$o['index'];
  fcc_partner_query("INSERT INTO fcc_webinar_sessions (series_id,series_key,occurrence_key,title,starts_at_utc,ends_at_utc,timezone,access_url,status,calendar_uid,event_json,is_demo,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,'scheduled',?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())",[(int)$series['id'],$series['series_key'],$o['key'],$d['title'],$o['start'],$o['end'],$d['timezone']??'Europe/Zagreb',!empty($d['managed'])?$d['access_url']:forever_business_vip_webinar_url(),'fcc-event-'.bin2hex(random_bytes(12)).'@forevercard.club',fcc_webinar_json($occurrenceDefinition),fcc_webinar_local()?1:0]);
 }
}
function fcc_event_education_unique(array $d,int $id=0): void {
 if(!$d['education'])return;
 if(fcc_partner_one("SELECT id FROM fcc_webinar_series WHERE id<>? AND status='published' AND JSON_EXTRACT(definition_json,'$.education')=1 LIMIT 1",[$id]))throw new InvalidArgumentException(fcc_t('Raspored edukacije već ima poslovni webinar. Uredi postojeći ili isključi povezivanje s edukacijom.'));
}
function fcc_event_save_series(int $actor,int $id,int $revision,array $input,string $state='draft',?DateTimeImmutable $now=null): int {
 fcc_event_admin($actor);$state=fcc_partner_choice($state,['draft'=>1,'published'=>1]);$d=fcc_event_validate($input,$state==='published',$now);
 return fcc_event_transaction(function()use($actor,$id,$revision,$d,$state,$now){
  if($state==='published')fcc_event_education_unique($d,$id);
  $old=$id?fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=? FOR UPDATE',[$id]):null;
  if($id&&(!$old||(int)$old['revision']!==$revision))throw new InvalidArgumentException(fcc_t('Događaj je izmijenjen. Osvježi pregled.'));
  if($old&&fcc_partner_one('SELECT id FROM fcc_webinar_sessions WHERE series_id=? LIMIT 1',[$id]))throw new InvalidArgumentException(fcc_t('Objavljenu seriju uredi kroz odabrani termin.'));
  if($id)fcc_partner_query('UPDATE fcc_webinar_series SET title=?,definition_json=?,status=?,revision=revision+1,updated_at=UTC_TIMESTAMP() WHERE id=?',[$d['title'],fcc_webinar_json($d),$state,$id]);
  else{fcc_partner_query('INSERT INTO fcc_webinar_series (series_key,title,definition_json,status,created_by,created_at,updated_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',['event-'.bin2hex(random_bytes(10)),$d['title'],fcc_webinar_json($d),$state,$actor]);$id=(int)database()->insert_id;}
  $series=fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=?',[$id]);fcc_event_materialize($series,$now);fcc_event_audit($actor,$state,$id,null,['definition'=>$d]);return $id;
 });
}
function fcc_event_member(int $uid,array $d): ?array {
 $u=fcc_partner_one('SELECT user_id,status,type,preferences FROM users WHERE user_id=?',[$uid]);
 if(!$u||(int)$u['status']!==1||!fcc_rollout_public_owner($uid))return null;
 $u['fbo_id']=forever_business_extract_user_fbo_id($u['preferences']);
 // FCC status=1 is the existing administrator approved account boundary.
 if($d['team_root']!==''&&(int)$u['type']!==1&&$u['fbo_id']!==$d['team_root']&&!fcc_partner_one('SELECT descendant_fbo_id FROM forever_business_hierarchy WHERE ancestor_fbo_id=? AND descendant_fbo_id=?',[$d['team_root'],$u['fbo_id']]))return null;
 return $u;
}
function fcc_event_cc(string $fbo,string $period): array {
 $m=fcc_partner_one("SELECT m.*,i.report_kind FROM forever_business_metrics m LEFT JOIN forever_business_imports i ON i.import_id=m.source_import_id WHERE m.fbo_id=? AND m.period_month=?",[$fbo,$period]);
 if(!$m)return ['state'=>'pending','source'=>'unknown','at'=>null];
 if($m['source_import_id']!==null&&!in_array($m['report_kind'],['downline','four_cc_active'],true))return ['state'=>'pending','source'=>'unverified_import','at'=>$m['updated_at']];
 $known=$m['is_4cc_active']!==null||($m['personal_cc']!==null&&$m['total_active_cc']!==null);
 return ['state'=>!$known?'pending':(forever_business_has_verified_four_cc_activity($m)?'eligible':'ineligible'),'source'=>$m['is_4cc_active']!==null?'official':($known?'formula':'unknown'),'at'=>$m['updated_at']];
}
function fcc_event_access(int $uid,array $s,?DateTimeImmutable $now=null): array {
 $d=fcc_event_definition($s);$u=fcc_event_member($uid,$d);$out=['allowed'=>false,'state'=>'ineligible','reason'=>'account','periods'=>[],'checked_at'=>null];
 if(!$u)return $out;
 $seriesKey=(int)($s['series_id']??0);
 if(!array_key_exists($seriesKey,$GLOBALS['fcc_mw_scope_series']??[]))$GLOBALS['fcc_mw_scope_series'][$seriesKey]=fcc_mw_series($s);
 $managed=$GLOBALS['fcc_mw_scope_series'][$seriesKey];if($managed&&!fcc_mw_allowed($uid,$managed))return array_replace($out,['reason'=>'managed_scope']);
 if($s['status']==='draft')return array_replace($out,['reason'=>'draft']);
 $pref=fcc_partner_one('SELECT * FROM fcc_webinar_member_access WHERE session_id=? AND user_id=?',[(int)$s['id'],$uid]);
 if(($pref['override_value']??'')==='exclude')return array_replace($out,['reason'=>'excluded']);
 if((int)$u['type']===1||($pref['override_value']??'')==='include')return array_replace($out,['allowed'=>true,'state'=>'eligible','reason'=>(int)$u['type']===1?'admin':'included']);
 if($d['audience']==='team')return array_replace($out,['allowed'=>true,'state'=>'eligible','reason'=>'team']);
 if($d['audience']==='selected')return array_replace($out,['allowed'=>in_array($uid,$d['selected'],true),'state'=>in_array($uid,$d['selected'],true)?'eligible':'ineligible','reason'=>'selected']);
 $month=(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->modify('first day of this month')->setTime(0,0);
 $periods=match($d['audience']){'previous'=>[$month->modify('-1 month')->format('Y-m-d')],'current'=>[$month->format('Y-m-d')],default=>[$month->modify('-1 month')->format('Y-m-d'),$month->format('Y-m-d')]};
 $checks=[];foreach($periods as $p)$checks[$p]=fcc_event_cc($u['fbo_id'],$p);
 $states=array_column($checks,'state');$eligible=$d['audience']==='either'?in_array('eligible',$states,true):count(array_filter($states,fn($v)=>$v==='eligible'))===count($states);
 $pending=!$eligible&&in_array('pending',$states,true)&&($d['audience']==='either'||!in_array('ineligible',$states,true));
 return ['allowed'=>$eligible,'state'=>$eligible?'eligible':($pending?'pending':'ineligible'),'reason'=>$d['audience'],'periods'=>$checks,'checked_at'=>min(array_filter(array_column($checks,'at'))?:[null])];
}
function fcc_event_list(int $uid,?DateTimeImmutable $now=null,bool $invitable=false,string $kind=''): array {
 if(!fcc_events_enabled())return [];$now=fcc_event_now($now);$out=[];
 $visibility='';
 if(fcc_mw_ready()){
  $graph=fcc_mw_graph();$path=array_merge([fcc_team_primary($uid,$graph)],fcc_team_upline($uid,$graph,false));$organizers=[];
  foreach($graph as $id=>$record)if(in_array(fcc_team_primary((int)$id,$graph),$path,true))$organizers[]=(int)$id;
  $visibility=' AND series_id IN (SELECT id FROM fcc_webinar_series WHERE organizer_user_id IS NULL OR organizer_user_id='.(int)$uid." OR (audience_scope='team' AND organizer_user_id IN (".implode(',',$organizers?:[0]).')))';
 }
 foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE series_id IS NOT NULL AND status<>'draft' AND (ends_at_utc>? OR (status='postponed' AND updated_at>?)) ".$visibility." ORDER BY starts_at_utc,id LIMIT 500",[$now->format('Y-m-d H:i:s'),$now->modify('-90 days')->format('Y-m-d H:i:s')]) as $s){
  $d=fcc_event_definition($s);if($kind!==''&&$d['kind']!==$kind)continue;
  if(!fcc_event_access($uid,$s,$now)['allowed'])continue;
  if($invitable&&!fcc_event_invitable($uid,$s,$now))continue;$s['title']=fcc_webinar_title($s);$out[]=$s;
 }return $out;
}
function fcc_event_invitable(int $uid,array $s,?DateTimeImmutable $now=null): bool {
 $d=fcc_event_definition($s);return $s['status']==='scheduled'&&!empty($d['guests'])&&fcc_event_access($uid,$s,$now)['allowed']&&strtotime($s[!empty($d['time_pending'])?'ends_at_utc':'starts_at_utc'].' UTC')-$d['close_minutes']*60>fcc_event_now($now)->getTimestamp();
}
function fcc_event_joinable(array $s,?DateTimeImmutable $now=null): bool {
 $d=fcc_event_definition($s);$ts=fcc_event_now($now)->getTimestamp();return $s['status']==='scheduled'&&$d['mode']==='online'&&$ts>=strtotime($s['starts_at_utc'].' UTC')-$d['join_minutes']*60&&$ts<strtotime($s['ends_at_utc'].' UTC');
}
function fcc_event_label(array $s): string {return $s['status']==='postponed'?fcc_t('Novi termin uskoro'):fcc_webinar_label_time($s);}
function fcc_event_job(array $s,int $uid,string $kind,DateTimeImmutable $due,DateTimeImmutable $expires,?string $key=null,?DateTimeImmutable $now=null): void {
 $now=fcc_event_now($now);if($due>$expires||$expires<=$now)return;
 $key=$key??'event:'.$s['id'].':'.$uid.':'.$kind.':'.$s['revision'];
 fcc_partner_query("INSERT IGNORE INTO fcc_webinar_event_jobs (session_id,user_id,kind,revision,unique_key,due_at,expires_at,state,created_at,updated_at) VALUES (?,?,?,?,?,?,?,'pending',?,?)",[(int)$s['id'],$uid,$kind,(int)$s['revision'],$key,$due->format('Y-m-d H:i:s'),$expires->format('Y-m-d H:i:s'),$now->format('Y-m-d H:i:s'),$now->format('Y-m-d H:i:s')]);
}
function fcc_event_quiet_due(DateTimeImmutable $date): DateTimeImmutable {
 $local=$date->setTimezone(new DateTimeZone('Europe/Zagreb'));$h=(int)$local->format('G');
 if($h<8)$local=$local->modify('-1 day')->setTime(20,59);
 elseif($h>=21)$local=$local->setTime(20,59);
 return $local->setTimezone(new DateTimeZone('UTC'));
}
function fcc_event_reconcile_member(array $s,int $uid,?DateTimeImmutable $now=null): void {
 $now=fcc_event_now($now);$a=fcc_event_access($uid,$s,$now);$d=fcc_event_definition($s);
 $prior=fcc_partner_one('SELECT state FROM fcc_webinar_member_access WHERE session_id=? AND user_id=?',[(int)$s['id'],$uid]);
 fcc_partner_query('INSERT INTO fcc_webinar_member_access (session_id,user_id,state,reason,checked_at) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE state=VALUES(state),reason=VALUES(reason),checked_at=VALUES(checked_at)',[(int)$s['id'],$uid,$a['state'],$a['reason'],$now->format('Y-m-d H:i:s')]);
 if(!$a['allowed']||$s['status']!=='scheduled')return;
 $start=new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC'));$end=new DateTimeImmutable($s['ends_at_utc'],new DateTimeZone('UTC'));
 if(!empty($d['time_pending']))$start=$start->setTimezone(new DateTimeZone('Europe/Zagreb'))->setTime(9,0)->setTimezone(new DateTimeZone('UTC'));
 if($d['announce'])fcc_event_job($s,$uid,'published',$now,$end,'series:'.$s['series_id'].':'.$uid.':published',$now);
 $remaining=$start->getTimestamp()-$now->getTimestamp();
 if(($prior['state']??'')!=='eligible'&&$d['reminders']&&$remaining>0&&$remaining<900&&!$d['announce'])fcc_event_job($s,$uid,'available',$now,$start,null,$now);
 $times=[];foreach($d['reminders'] as $m){$due=fcc_event_quiet_due($start->modify('-'.$m.' minutes'));if($due<$now||isset($times[$due->getTimestamp()]))continue;$times[$due->getTimestamp()]=true;fcc_event_job($s,$uid,'reminder'.$m,$due,$start,null,$now);}
}
function fcc_event_slot(array $s): int {
 $d=fcc_event_definition($s);return (int)($d['slot_index']??0);
}
function fcc_event_apply_change(array $s,array $d,string $status,int $actor,string $reason,?DateTimeImmutable $now=null): void {
 $now=fcc_event_now($now);$start=$s['starts_at_utc'];$end=$s['ends_at_utc'];
 if($status==='scheduled'){$date=fcc_event_local_time($d['date'],$d['time'],$d['timezone']??'Europe/Zagreb');$start=$date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');$end=$date->modify('+'.$d['duration'].' minutes')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');if($d['mode']==='online')fcc_event_collision($start,$end,(int)$s['id'],!empty($d['managed'])?$d['access_url']:null);}
 $d['date']=(new DateTimeImmutable($start,new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($d['timezone']??$s['timezone']))->format('Y-m-d');
 $d['slot_index']=fcc_event_slot($s);$d['previous_start']=$s['starts_at_utc'];$d['change_reason']=$reason;
 fcc_partner_query('UPDATE fcc_webinar_sessions SET title=?,starts_at_utc=?,ends_at_utc=?,timezone=?,access_url=?,status=?,event_json=?,revision=revision+1,is_exception=1,member_cursor=0,updated_at=UTC_TIMESTAMP() WHERE id=?',[$d['title'],$start,$end,$d['timezone']??$s['timezone'],!empty($d['managed'])?$d['access_url']:$s['access_url'],$status,fcc_webinar_json($d),(int)$s['id']]);
 fcc_partner_query("UPDATE fcc_webinar_event_jobs SET state='cancelled',updated_at=UTC_TIMESTAMP() WHERE session_id=? AND state='pending'",[(int)$s['id']]);
 // Invalidate pending device deliveries; already delivered notifications cannot be recalled.
 fcc_partner_query("UPDATE fcc_partner_push_deliveries p JOIN fcc_partner_notifications n ON n.id=p.notification_id SET p.status='skipped',p.last_code='event_changed' WHERE n.event_key LIKE ? AND p.status IN ('pending','sending')",['webinar:calendar:'.$s['id'].':%']);
 fcc_partner_query("UPDATE fcc_webinar_jobs j JOIN fcc_webinar_registrations r ON r.id=j.registration_id SET j.state='cancelled',j.updated_at=UTC_TIMESTAMP() WHERE r.session_id=? AND j.state IN ('pending','processing') AND j.kind NOT IN ('project','owner_confirmation')",[(int)$s['id']]);
 $next=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$s['id']]);
 foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_registrations WHERE session_id=? AND status='registered'",[(int)$s['id']]) as $r){fcc_webinar_job((int)$r['id'],$status==='scheduled'?(!$r['email_verified_at']&&$r['reminder_opt_in']?'confirmation':'changed'):$status,(int)$next['revision'],$now->format('Y-m-d H:i:s'));fcc_webinar_schedule($r,$next,$now);}
 foreach(fcc_partner_rows("SELECT user_id FROM fcc_webinar_member_access WHERE session_id=? AND state='eligible'",[(int)$s['id']]) as $member)fcc_event_job($next,(int)$member['user_id'],$status==='scheduled'?'changed':$status,$now,$now->modify('+7 days'),null,$now);
 fcc_event_audit($actor,'change',(int)$s['series_id'],(int)$s['id'],['before'=>['start'=>$s['starts_at_utc'],'status'=>$s['status'],'revision'=>$s['revision']],'after'=>['start'=>$start,'status'=>$status,'revision'=>$next['revision']],'reason'=>$reason]);
}
function fcc_event_change(int $actor,int $sid,int $revision,array $input,string $status,string $scope='one',?DateTimeImmutable $now=null): void {
 $existing=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[$sid]);$managed=$existing?fcc_mw_series($existing):null;
 if($managed)fcc_mw_require_owner($actor,$existing,$status!=='scheduled');else fcc_event_admin($actor);
 $status=fcc_partner_choice($status,['scheduled'=>1,'postponed'=>1,'cancelled'=>1]);$scope=fcc_partner_choice($scope,['one'=>1,'future'=>1]);$now=fcc_event_now($now);
 $d=$managed?($status==='scheduled'?fcc_mw_input($input,(int)$managed['organizer_user_id'],$managed['audience_scope'],$now):fcc_event_definition($existing)):fcc_event_validate($input,$status==='scheduled',$now);$reason=fcc_partner_text($input['reason']??'',400);
 if($status!=='scheduled'&&$reason==='')throw new InvalidArgumentException(fcc_t('Ukratko napiši razlog promjene.'));
 fcc_event_transaction(function()use($actor,$sid,$revision,$d,$status,$scope,$now,$reason,$input){
  $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL FOR UPDATE',[$sid]);
  if(!$s||(int)$s['revision']!==$revision)throw new InvalidArgumentException(fcc_t('Termin je izmijenjen. Osvježi prikaz.'));
  if($s['starts_at_utc']<=$now->format('Y-m-d H:i:s')&&$s['status']==='scheduled')throw new InvalidArgumentException(fcc_t('Započeti termin ostaje u povijesti. Izradi novi događaj.'));
  if($status==='scheduled')fcc_event_education_unique($d,(int)$s['series_id']);
  if($scope==='one'){fcc_event_apply_change($s,$d,$status,$actor,$reason,$now);return;}
  $series=fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=? FOR UPDATE',[(int)$s['series_id']]);
  if(isset($input['series_revision'])&&(int)$series['revision']!==(int)$input['series_revision'])throw new InvalidArgumentException(fcc_t('Serija je izmijenjena. Osvježi prikaz.'));
  $selectedIndex=fcc_event_slot($s);
  $slots=fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE series_id=? AND (starts_at_utc>? OR id=?) FOR UPDATE",[(int)$s['series_id'],$now->format('Y-m-d H:i:s'),$sid]);
  $slots=array_values(array_filter($slots,fn($t)=>fcc_event_slot($t)>=$selectedIndex));
  usort($slots,fn($a,$b)=>fcc_event_slot($a)<=>fcc_event_slot($b));
  $targets=array_values(array_filter($slots,fn($t)=>$status!=='scheduled'||!(int)$t['is_exception']||(int)$t['id']===$sid));
  // Take selected future rows out of conflict checks until their new dates are assigned.
  foreach($targets as $t)fcc_partner_query("UPDATE fcc_webinar_sessions SET status='draft' WHERE id=?",[(int)$t['id']]);
  $base=(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($d['timezone']??'Europe/Zagreb'));
  $target=fcc_event_local_time($status==='scheduled'?($d['date']?:$base->format('Y-m-d')):$base->format('Y-m-d'),$d['time'],$d['timezone']??'Europe/Zagreb');
  foreach($targets as $t){$td=$d;$days=(fcc_event_slot($t)-$selectedIndex)*7;$td['date']=$target->modify('+'.$days.' days')->format('Y-m-d');$targetStatus=$status;if($d['until']!==''&&$td['date']>$d['until'])$targetStatus='cancelled';if($d['repeat']==='once'&&(int)$t['id']!==$sid)$targetStatus='cancelled';fcc_event_apply_change($t,$td,$targetStatus,$actor,$reason,$now);if($targetStatus==='scheduled')fcc_partner_query('UPDATE fcc_webinar_sessions SET is_exception=0 WHERE id=?',[(int)$t['id']]);}
  // The new series anchor begins after all materialized slots, so stable old keys cannot collide.
  $future=$d;$nextDate=$target;
  if($d['repeat']==='weekly'&&$slots){$future['first_index']=fcc_event_slot(end($slots))+1;$nextDate=$target->modify('+'.(($future['first_index']-$selectedIndex)*7).' days');}
  // A new generation key avoids collisions with immutable keys from the previous schedule.
  $generation='event-'.bin2hex(random_bytes(10));
  $future['date']=$nextDate->format('Y-m-d');
  fcc_partner_query('UPDATE fcc_webinar_series SET series_key=?,title=?,definition_json=?,status=?,revision=revision+1,updated_at=UTC_TIMESTAMP() WHERE id=?',[$generation,$d['title'],fcc_webinar_json($future),$status==='scheduled'&&$d['repeat']==='weekly'?'published':'paused',(int)$s['series_id']]);
  fcc_event_audit($actor,'future',(int)$s['series_id'],$sid,['status'=>$status,'definition'=>$d,'count'=>count($targets)]);
  fcc_event_materialize(fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=?',[(int)$s['series_id']]),$now);
 });
}
function fcc_event_override(int $actor,int $sid,int $uid,string $value,string $reason): void {
 fcc_event_admin($actor);$value=fcc_partner_choice($value,[''=>1,'include'=>1,'exclude'=>1]);$reason=fcc_partner_text($reason,400);if($value!==''&&$reason==='')throw new InvalidArgumentException(fcc_t('Napiši razlog iznimke.'));
 $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL',[$sid]);if(!$s||!fcc_event_member($uid,fcc_event_definition($s)))throw new InvalidArgumentException(fcc_t('Član nije u odabranoj publici tima.'));
 fcc_event_transaction(function()use($actor,$sid,$uid,$value,$reason,$s){fcc_partner_query("INSERT INTO fcc_webinar_member_access (session_id,user_id,state,reason,checked_at,override_value,override_reason) VALUES (?,?,'pending','manual',UTC_TIMESTAMP(),?,?) ON DUPLICATE KEY UPDATE override_value=VALUES(override_value),override_reason=VALUES(override_reason)",[$sid,$uid,$value,$reason]);fcc_event_reconcile_member($s,$uid);fcc_event_audit($actor,'override',(int)$s['series_id'],$sid,['user_id'=>$uid,'value'=>$value,'reason'=>$reason]);});
}
function fcc_event_delivery_allowed(int $uid,string $key,?DateTimeImmutable $now=null): bool {
 if(!fcc_wl_delivery_allowed($uid,$key,$now))return false;
 if(preg_match('/^webinar:calendar:(\d+):(\d+):reminder60$/D',$key,$wm)){
  $personal=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND owner_user_id=?',[(int)$wm[1],$uid]);
  if($personal)return $personal['status']==='scheduled'&&(int)$personal['revision']===(int)$wm[2]&&$personal['starts_at_utc']>fcc_webinar_now($now)->format('Y-m-d H:i:s')&&(bool)fcc_wl_summary($uid,(int)$wm[1])['rows'];
 }
 if(!preg_match('/^webinar:calendar:(\d+):(\d+):/', $key,$m))return true;
 if(!fcc_events_enabled())return false;
 $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$m[1]]);if(!$s||(int)$s['revision']!==(int)$m[2])return false;
 if(str_contains($key,':reminder')&&(fcc_event_rsvp($uid,(int)$s['id'])['state']??'')==='cancelled')return false;
 /* Custom code: FC-2026-10-11: Team event reminders go only to members who confirmed attendance or registered their own guests. Everyone still sees the next event on Today. */
 if(str_contains($key,':reminder')&&(fcc_event_rsvp($uid,(int)$s['id'])['state']??'')!=='going'&&!(function_exists('fcc_wl_summary')&&fcc_wl_summary($uid,(int)$s['id'])['total_count']>0))return false;
 /* /Custom code: FC-2026-10-11 */
 $p=fcc_partner_one('SELECT muted FROM fcc_webinar_member_access WHERE session_id=? AND user_id=?',[(int)$s['id'],$uid]);return empty($p['muted'])&&fcc_event_access($uid,$s,$now)['allowed'];
}
function fcc_event_run_jobs(?DateTimeImmutable $now=null,int $limit=100,?int $onlySession=null): array {
 if(!fcc_events_enabled())return ['disabled'=>true];$now=fcc_event_now($now);$at=$now->format('Y-m-d H:i:s');$count=0;
 return fcc_event_transaction(function()use($now,$at,$limit,$onlySession,&$count){
  foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_event_jobs WHERE state='pending' AND due_at<=? ".($onlySession?'AND session_id='.(int)$onlySession.' ':'')."ORDER BY CASE WHEN kind IN ('changed','cancelled','postponed') THEN 0 ELSE 1 END,due_at,id LIMIT ".max(1,min(500,$limit)).' FOR UPDATE',[$at]) as $j){
   $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$j['session_id']]);$key='webinar:calendar:'.$j['session_id'].':'.$j['revision'].':'.$j['kind'];
   $change=in_array($j['kind'],['changed','cancelled','postponed'],true);
   if(!$s||$j['expires_at']<=$at||!fcc_event_delivery_allowed((int)$j['user_id'],$key,$now)||(!$change&&($s['status']!=='scheduled'||$s['starts_at_utc']<=$at))){fcc_partner_query("UPDATE fcc_webinar_event_jobs SET state='skipped',updated_at=? WHERE id=?",[$at,(int)$j['id']]);continue;}
   $title=match($j['kind']){'published','available'=>'Novi događaj u tvojem rasporedu','changed'=>'Termin događaja je promijenjen','cancelled'=>'Događaj je otkazan','postponed'=>'Događaj je odgođen',default=>'Podsjetnik na događaj'};
   $locale=fcc_mw_locale($s,fcc_wl_locale((int)$j['user_id']));$title=fcc_t($title,[],$locale);
   $guestNotice=$j['kind']==='reminder60'?fcc_wl_notice((int)$j['user_id'],$s,'before'):null;
   fcc_partner_notify((int)$j['user_id'],$key,'webinar',$guestNotice['title']??$title,$guestNotice['body']??($s['title'].' · '.fcc_webinar_label_time($s,$locale)),$guestNotice['path']??('partner/webinars/'.(int)$s['id']),max(60,strtotime($j['expires_at'].' UTC')-$now->getTimestamp()),$at);
   fcc_partner_query("UPDATE fcc_webinar_event_jobs SET state='delivered',updated_at=? WHERE id=?",[$at,(int)$j['id']]);$count++;
  }return ['inbox'=>$count];
 });
}
function fcc_events_tick(?DateTimeImmutable $now=null): array {
 if(!fcc_events_enabled())return ['disabled'=>true];$now=fcc_event_now($now);$errors=0;
 $seriesCursor=(int)fcc_partner_notification_config('webinar_series_cursor',0);$seriesBatch=fcc_partner_rows("SELECT * FROM fcc_webinar_series WHERE status='published' AND id>? ORDER BY id LIMIT 100",[$seriesCursor]);
 fcc_partner_notification_config_save('webinar_series_cursor',count($seriesBatch)===100?(int)end($seriesBatch)['id']:0);
 foreach($seriesBatch as $series){try{fcc_event_transaction(fn()=>fcc_event_materialize($series,$now));}catch(Throwable $e){$errors++;error_log('FCC calendar materialization: '.$series['id'].' '.get_class($e));}}
 $cursor=(int)fcc_partner_notification_config('webinar_calendar_cursor',0);
 $sessions=fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE series_id IS NOT NULL AND status='scheduled' AND ends_at_utc>? AND id>? ORDER BY id LIMIT 20",[$now->format('Y-m-d H:i:s'),$cursor]);
 foreach($sessions as $s){$users=fcc_partner_rows('SELECT user_id FROM users WHERE status=1 AND user_id>? ORDER BY user_id LIMIT 200',[(int)$s['member_cursor']]);foreach($users as $u)fcc_event_reconcile_member($s,(int)$u['user_id'],$now);fcc_partner_query('UPDATE fcc_webinar_sessions SET member_cursor=? WHERE id=?',[count($users)===200?(int)end($users)['user_id']:0,(int)$s['id']]);}
 fcc_partner_notification_config_save('webinar_calendar_cursor',count($sessions)===20?(int)end($sessions)['id']:0);
 $result=fcc_event_run_jobs($now);fcc_wl_tick($now);fcc_partner_notification_config_save('webinar_calendar_heartbeat',['at'=>$now->format('c'),'errors'=>$errors,'sessions'=>count($sessions)]);return $result+['errors'=>$errors];
}
function fcc_event_calendar(int $uid,array $s): string {
 if(!fcc_event_access($uid,$s)['allowed'])throw new InvalidArgumentException(fcc_t('Termin nije dostupan.'));
 $escape=fn($v)=>str_replace(["\\","\r","\n",';',','],["\\\\",'',"\\n",'\\;','\\,'],(string)$v);
 $d=fcc_event_definition($s);if($d['mode']==='in_person')return fcc_seminar_calendar($s,url('partner/webinars/'.$s['id']));$state=in_array($s['status'],['cancelled','postponed'],true)?'CANCELLED':'CONFIRMED';
 $lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//FCC//Events//HR','CALSCALE:GREGORIAN','BEGIN:VEVENT','UID:'.$s['calendar_uid'],'SEQUENCE:'.$s['revision'],'DTSTAMP:'.gmdate('Ymd\THis\Z'),'DTSTART:'.gmdate('Ymd\THis\Z',strtotime($s['starts_at_utc'].' UTC')),'DTEND:'.gmdate('Ymd\THis\Z',strtotime($s['ends_at_utc'].' UTC')),'SUMMARY:'.$escape($s['title']),'DESCRIPTION:'.$escape($d['description']."\n".url('partner/webinars/'.$s['id'])),'LOCATION:'.$escape($d['mode']==='online'?'Online, FCC':$d['location']),'URL:'.url('partner/webinars/'.$s['id']),'STATUS:'.$state,'END:VEVENT','END:VCALENDAR'];
 $folded=[];foreach($lines as $line){$first=true;while(strlen($line)>($first?75:74)){$chunk=mb_strcut($line,0,$first?75:74,'UTF-8');$folded[]=($first?'':' ').$chunk;$line=substr($line,strlen($chunk));$first=false;}$folded[]=($first?'':' ').$line;}return implode("\r\n",$folded)."\r\n";
}
function fcc_event_marketing_state(?DateTimeInterface $now=null): array {
 $now=fcc_event_now($now?DateTimeImmutable::createFromInterface($now):null);$s=null;
 foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE series_id IS NOT NULL AND status='scheduled' AND ends_at_utc>? ORDER BY starts_at_utc LIMIT 200",[$now->format('Y-m-d H:i:s')]) as $candidate){$d=fcc_event_definition($candidate);if($d['kind']==='business'&&!empty($d['education'])){$s=$candidate;break;}}
 $out=['weekday'=>0,'weekday_label'=>'','time_label'=>'','timezone'=>'Europe/Zagreb','first_at_iso'=>null,'is_today'=>false,'is_live_window'=>false,'can_record_outcome'=>false,'completion_available_at_iso'=>null,'completion_available_at_display'=>'','next_at_iso'=>null,'next_at_display'=>'Novi termin uskoro','url'=>forever_business_vip_webinar_url(),'session_id'=>null,'cancelled'=>!$s];
 // Keep the completed event available today for the existing reflection task.
 if(!$s||substr($s['starts_at_utc'],0,10)!==$now->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d')){
  $day=$now->setTimezone(new DateTimeZone('Europe/Zagreb'))->setTime(0,0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE series_id IS NOT NULL AND status='scheduled' AND starts_at_utc>=? AND starts_at_utc<=? ORDER BY starts_at_utc DESC",[$day,$now->format('Y-m-d H:i:s')]) as $candidate){$d=fcc_event_definition($candidate);if($d['kind']==='business'&&!empty($d['education'])){$s=$candidate;break;}}
 }
 if(!$s)return $out;
 $start=(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'));$end=(new DateTimeImmutable($s['ends_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'));$today=$start->format('Y-m-d')===$now->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d');
 return array_replace($out,['weekday'=>(int)$start->format('N'),'weekday_label'=>$start->format('d. m.'),'time_label'=>$start->format('H:i'),'first_at_iso'=>$start->format(DateTimeInterface::ATOM),'is_today'=>$today,'is_live_window'=>$now>=$start&&$now<$end,'can_record_outcome'=>$today&&$now>=$end,'completion_available_at_iso'=>$end->format(DateTimeInterface::ATOM),'completion_available_at_display'=>$end->format('H:i'),'next_at_iso'=>$start->format(DateTimeInterface::ATOM),'next_at_display'=>$start->format('d. m. Y. \u H:i'),'session_id'=>(int)$s['id'],'cancelled'=>false]);
}
