<?php
defined('ALTUMCODE') || die();
function fcc_wl_ready(): bool {
 static $ready;return $ready??=(bool)fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_webinar_followups'");
}
function fcc_wl_t(string $key,array $values=[],?string $locale=null): string {
 static $copy;$copy??=json_decode(file_get_contents(APP_PATH.'config/webinar-lifecycle-copy.json'),true,512,JSON_THROW_ON_ERROR);
 $lang=$locale??fcc_locale();$text=$copy[$lang][$key]??fcc_t($copy['hr'][$key]??$copy['en'][$key]??$key,[],$lang);
 foreach($values as $k=>$v)$text=str_replace('{'.$k.'}',(string)$v,$text);return $text;
}
function fcc_wl_locale(int $uid): string {
 $language=fcc_partner_one('SELECT language FROM users WHERE user_id=?',[$uid])['language']??'english';
 return fcc_locale($language);
}
function fcc_wl_rows(int $uid,int $sid=0,int $cid=0,int $limit=200,int $offset=0): array {
 if(!fcc_wl_ready())return [];
 $params=[$uid];$where='r.user_id=?';if($sid){$where.=' AND r.session_id=?';$params[]=$sid;}if($cid){$where.=' AND r.contact_id=?';$params[]=$cid;}
 return fcc_partner_rows("SELECT r.*,s.title,s.starts_at_utc,s.ends_at_utc,s.timezone,s.status session_status,s.series_id,s.owner_user_id,s.event_json,s.access_url,s.revision,c.name contact_name,c.phone contact_phone,c.id contact_row_id,c.user_id contact_user_id,c.contact_permission,c.archived_at,c.source_kind contact_source_kind,c.source_id contact_source_id,c.version contact_version,c.created_at contact_created_at,c.updated_at contact_updated_at,COALESCE(g.attendance,'unknown') attendance,g.contacted_at,COALESCE(g.version,0) outcome_version FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id JOIN fcc_partner_contacts c ON c.id=r.contact_id AND c.user_id=r.user_id LEFT JOIN fcc_webinar_guest_states g ON g.registration_id=r.id AND g.user_id=r.user_id WHERE $where AND r.status<>'erased' AND (s.owner_user_id IS NULL OR s.owner_user_id=r.user_id) ORDER BY s.starts_at_utc DESC,r.id DESC LIMIT ".max(1,min(200,$limit))." OFFSET ".max(0,$offset),$params);
}
function fcc_wl_owned(int $uid,int $rid): array {
 $r=fcc_partner_one('SELECT r.*,c.id contact_row_id,c.user_id contact_user_id,c.contact_permission,c.archived_at,c.source_kind contact_source_kind,c.source_id contact_source_id,c.version contact_version,c.created_at contact_created_at,c.updated_at contact_updated_at FROM fcc_webinar_registrations r JOIN fcc_partner_contacts c ON c.id=r.contact_id AND c.user_id=r.user_id WHERE r.id=? AND r.user_id=? AND r.status<>\'erased\'',[$rid,$uid]);
 if(!$r)throw new InvalidArgumentException(fcc_wl_t('unavailable'));return $r;
}
function fcc_wl_may_contact(array $r): bool {
 return !empty($r['whatsapp_opt_in'])&&($r['contact_permission']??'do_not_contact')!=='do_not_contact'&&empty($r['archived_at'])&&empty($r['needs_review']);
}
/** Same published series only. A personal event may only lead to the same owner's identically titled event. */
function fcc_wl_next(int $uid,array $old,?DateTimeImmutable $now=null): ?array {
 $now=fcc_webinar_now($now);$params=[$now->format('Y-m-d H:i:s'),$old['starts_at_utc']];
 if(!empty($old['series_id'])){$where='series_id=? AND owner_user_id IS NULL';$params[]=(int)$old['series_id'];}
 elseif(!empty($old['owner_user_id'])){$where='owner_user_id=? AND title=?';$params[]=$uid;$params[]=$old['title'];}
 else return null;
 foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE status='scheduled' AND starts_at_utc>? AND starts_at_utc>? AND $where ORDER BY starts_at_utc,id LIMIT 50",$params) as $s){
  if(fcc_event_offline($s))continue;
  if(!empty($s['owner_user_id'])?((int)$s['owner_user_id']===$uid&&fcc_webinar_pro($uid,true)&&fcc_webinar_session_open($s,$now)):fcc_event_invitable($uid,$s,$now))return $s;
 }return null;
}
function fcc_wl_public_next(array $a): ?array {
 if(!fcc_wl_ready()||!empty($a['revoked_at']))return null;
 $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$a['session_id']]);if(!$s||fcc_event_offline($s))return null;
 $next=fcc_wl_next((int)$a['user_id'],$s);if(!$next||!empty($next['owner_user_id']))return null;
 $card=fcc_partner_one('SELECT url FROM links WHERE link_id=? AND user_id=? AND is_enabled=1',[(int)$a['card_id'],(int)$a['user_id']]);
 if(!$card||!fcc_webinar_standard_context((int)$next['id'],$card['url']))return null;
 return ['session'=>$next,'url'=>url('webinar-event/'.$next['id'].'?'.http_build_query(['ref'=>$card['url']]))];
}
function fcc_wl_outcome(int $uid,int $rid,int $version,string $attendance,bool $contacted,?DateTimeImmutable $now=null): void {
 if(!fcc_wl_ready()||!in_array($attendance,['unknown','attended','missed'],true))throw new InvalidArgumentException(fcc_wl_t('unavailable'));
 $now=fcc_webinar_now($now);
 fcc_webinar_atomic($uid,function()use($uid,$rid,$version,$attendance,$contacted,$now){
  $r=fcc_wl_owned($uid,$rid);$s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$r['session_id']]);
  if($r['status']!=='registered'||$s['status']!=='scheduled'||$s['ends_at_utc']>$now->format('Y-m-d H:i:s')||!empty($r['archived_at']))throw new InvalidArgumentException(fcc_wl_t('after_only'));
  $g=fcc_partner_one('SELECT * FROM fcc_webinar_guest_states WHERE registration_id=? FOR UPDATE',[$rid]);
  if((int)($g['version']??0)!==$version)throw new InvalidArgumentException(fcc_wl_t('stale'));
  if($contacted&&!fcc_webinar_may_contact_registration($r))throw new InvalidArgumentException(fcc_wl_t('no_permission'));
  $at=$now->format('Y-m-d H:i:s');$contactAt=$contacted?($g['contacted_at']??$at):null;if(!fcc_webinar_may_contact_registration($r))$contactAt=$g['contacted_at']??null;
  fcc_partner_query('INSERT INTO fcc_webinar_guest_states(registration_id,user_id,attendance,attendance_at,contacted_at,version,updated_at) VALUES(?,?,?,?,?,1,?) ON DUPLICATE KEY UPDATE attendance=VALUES(attendance),attendance_at=VALUES(attendance_at),contacted_at=VALUES(contacted_at),version=version+1,updated_at=VALUES(updated_at)',[$rid,$uid,$attendance,$attendance==='unknown'?null:$at,$contactAt,$at]);
  if($contacted)fcc_partner_query('UPDATE fcc_partner_tasks SET completed_at=COALESCE(completed_at,?),version=version+1 WHERE user_id=? AND request_key=? AND completed_at IS NULL',[$at,$uid,'webinar:followup:'.$rid]);
  $locale=fcc_wl_locale($uid);$note=fcc_wl_t('outcome_note',['title'=>$s['title'],'date'=>fcc_webinar_label_time($s,$locale),'attendance'=>fcc_wl_t($attendance,[],$locale),'conversation'=>fcc_wl_t($contactAt?'conversation_done':'conversation_pending',[],$locale)],$locale);
  fcc_partner_query("INSERT IGNORE INTO fcc_partner_activities(user_id,contact_id,kind,note,request_key,created_at) VALUES(?,?,'webinar_outcome',?,?,?)",[$uid,(int)$r['contact_id'],$note,'webinar:outcome:'.$rid.':'.($version+1),$at]);
 });
}
/** The returned capability grants only confirmation for one guest and one event, never access to their existing management token. */
function fcc_wl_return_prepare(int $uid,int $rid,?DateTimeImmutable $now=null,bool $guestInitiated=false): array {
 if(!fcc_wl_ready())throw new InvalidArgumentException(fcc_wl_t('unavailable'));
 $r=fcc_wl_owned($uid,$rid);if((!$guestInitiated&&!fcc_wl_may_contact($r))||!empty($r['archived_at'])||!empty($r['needs_review']))throw new InvalidArgumentException(fcc_wl_t('no_permission'));
 $old=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$r['session_id']]);$next=fcc_wl_next($uid,$old,$now);
 if(!$next)throw new InvalidArgumentException(fcc_wl_t('no_next'));
 $existing=fcc_partner_one('SELECT * FROM fcc_webinar_return_invites WHERE user_id=? AND contact_id=? AND session_id=?',[$uid,(int)$r['contact_id'],(int)$next['id']]);if($existing){if($guestInitiated)fcc_partner_query('UPDATE fcc_webinar_return_invites SET guest_requested=1 WHERE id=?',[(int)$existing['id']]);return $existing;}
 $source=fcc_webinar_attempt($uid,(int)$r['attempt_id']);$card=fcc_partner_one('SELECT url FROM links WHERE link_id=? AND user_id=? AND is_enabled=1',[(int)$source['card_id'],$uid]);if(!$card)throw new InvalidArgumentException(fcc_wl_t('unavailable'));
 if(empty($next['owner_user_id']))$a=fcc_webinar_standard_prepare((int)$next['id'],$card['url'],(int)$next['revision']);
 else $a=fcc_webinar_prepare($uid,['request_key'=>bin2hex(random_bytes(16)),'video_choice'=>'none','session_id'=>(int)$next['id'],'session_revision'=>(int)$next['revision'],'contact_id'=>(int)$r['contact_id']]);
 return fcc_webinar_atomic($uid,function()use($uid,$rid,$r,$next,$a,$guestInitiated){
  $fresh=fcc_wl_owned($uid,$rid);if((!$guestInitiated&&!fcc_wl_may_contact($fresh))||!empty($fresh['archived_at'])||!empty($fresh['needs_review']))throw new InvalidArgumentException(fcc_wl_t('no_permission'));
  $token=fcc_webinar_token();fcc_partner_query('INSERT IGNORE INTO fcc_webinar_return_invites(user_id,contact_id,source_registration_id,session_id,attempt_id,token_hash,token_sealed,guest_requested,created_at) VALUES(?,?,?,?,?,?,?,?,UTC_TIMESTAMP())',[$uid,(int)$r['contact_id'],$rid,(int)$next['id'],(int)$a['id'],hash('sha256',$token),fcc_webinar_seal($token),$guestInitiated?1:0]);
  return fcc_partner_one('SELECT * FROM fcc_webinar_return_invites WHERE user_id=? AND contact_id=? AND session_id=?',[$uid,(int)$r['contact_id'],(int)$next['id']]);
 });
}
function fcc_wl_return_url(array $i): string {return url('webinar-invite/return/'.fcc_webinar_unseal($i['token_sealed']));}
function fcc_wl_return_context(string $token,?DateTimeImmutable $now=null): ?array {
 if(!fcc_wl_ready()||!preg_match('/^[a-f0-9]{64}$/D',$token))return null;
 $i=fcc_partner_one('SELECT * FROM fcc_webinar_return_invites WHERE token_hash=?',[hash('sha256',$token)]);if(!$i)return null;
 try{$r=fcc_wl_owned((int)$i['user_id'],(int)$i['source_registration_id']);}catch(InvalidArgumentException $e){return null;}
 if((!$i['guest_requested']&&!fcc_wl_may_contact($r))||!empty($r['archived_at'])||!empty($r['needs_review'])||(int)$r['contact_id']!==(int)$i['contact_id'])return null;
 try{$a=fcc_webinar_attempt((int)$i['user_id'],(int)$i['attempt_id']);}catch(InvalidArgumentException $e){return null;}$public=fcc_webinar_public_attempt(fcc_webinar_unseal($a['token_sealed']));
 if(!$public||!fcc_webinar_can_register($public,$now))return null;
 return ['invite'=>$i,'source'=>$r,'attempt'=>$public];
}
function fcc_wl_reactivate(array $r,array $options,?DateTimeImmutable $now=null): void {
 $now=fcc_webinar_now($now);$a=fcc_webinar_attempt((int)$r['user_id'],(int)$r['attempt_id']);
 if(!fcc_webinar_can_register(fcc_webinar_public_attempt(fcc_webinar_unseal($a['token_sealed'])),$now))throw new InvalidArgumentException(fcc_wl_t('unavailable'));
 fcc_webinar_atomic((int)$r['user_id'],function()use($r,$options,$now){
  $r=fcc_wl_owned((int)$r['user_id'],(int)$r['id']);if($r['status']!=='cancelled')return;
  $a=fcc_webinar_attempt((int)$r['user_id'],(int)$r['attempt_id']);$fresh=fcc_webinar_public_attempt(fcc_webinar_unseal($a['token_sealed']));
  if(!fcc_webinar_can_register($fresh,$now)||(int)($options['session_revision']??0)!==(int)$fresh['revision'])throw new InvalidArgumentException(fcc_wl_t('stale'));
  $at=$now->format('Y-m-d H:i:s');
  fcc_partner_query("UPDATE fcc_webinar_registrations SET status='registered',cancelled_at=NULL,reminder_opt_in=?,whatsapp_opt_in=?,consent_at=?,consent_version=? WHERE id=?",[!empty($options['reminders'])?1:0,!empty($options['whatsapp_permission'])?1:0,$at,fcc_event_offline($fresh)?'fcc-seminar-interest-v2':fcc_webinar_config()['consent_version'],(int)$r['id']]);
  $r=fcc_partner_one('SELECT * FROM fcc_webinar_registrations WHERE id=?',[(int)$r['id']]);$s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$r['session_id']]);
  fcc_partner_query("UPDATE fcc_webinar_jobs SET state='pending',updated_at=?,next_attempt_at=NULL WHERE registration_id=? AND kind IN ('reminder24','reminder1') AND state='cancelled' AND scheduled_at_utc>? AND session_revision=?",[$at,(int)$r['id'],$at,(int)$s['revision']]);
  fcc_webinar_schedule($r,$s,$now);
  fcc_partner_notify((int)$r['user_id'],'webinar:reactivated:'.$r['id'].':'.$at,'service',fcc_wl_t('reactivated_title',[],fcc_wl_locale((int)$r['user_id'])),$r['name'].' · '.fcc_webinar_label_time($s),'partner/contacts?id='.$r['contact_id'],604800,$at);
 });
}
function fcc_wl_return_confirm(string $token,array $options,?DateTimeImmutable $now=null): array {
 $ctx=fcc_wl_return_context($token,$now);if(!$ctx)throw new InvalidArgumentException(fcc_wl_t('unavailable'));
 if((int)($options['session_revision']??0)!==(int)$ctx['attempt']['revision'])throw new InvalidArgumentException(fcc_wl_t('stale'));
 $r=$ctx['source'];$form=['name'=>$r['name'],'email'=>$r['email'],'phone'=>$r['phone'],'reminders'=>!empty($options['reminders']),'whatsapp_permission'=>!empty($options['whatsapp_permission']),'session_revision'=>$ctx['attempt']['revision']];
 $result=fcc_webinar_register(fcc_webinar_unseal($ctx['attempt']['token_sealed']),$form,$now,['return_token'=>$token]);
 $registered=fcc_partner_one('SELECT * FROM fcc_webinar_registrations WHERE id=?',[(int)$result['registration_id']]);
 if($registered['status']==='cancelled')fcc_wl_reactivate($registered,$options,$now);
 fcc_partner_query('UPDATE fcc_webinar_return_invites SET registration_id=?,confirmed_at=COALESCE(confirmed_at,UTC_TIMESTAMP()) WHERE id=?',[(int)$registered['id'],(int)$ctx['invite']['id']]);
 // This guest-only page can expose their new management capability after explicit confirmation.
 $result['manage_token']=fcc_webinar_unseal($registered['management_sealed']);return $result;
}
function fcc_wl_message(array $r,string $url='',?string $locale=null): string {
 $first=explode(' ',trim($r['contact_name']??$r['name']))[0];
 if($url!=='')return fcc_wl_t('invite_message',['name'=>$first,'date'=>fcc_webinar_label_time($r,$locale),'url'=>$url],$locale);
 $key=($r['ends_at_utc']??'')>gmdate('Y-m-d H:i:s')?'before_message':match($r['attendance']??'unknown'){'attended'=>'attended_message','missed'=>'missed_message',default=>'follow_message'};
 return fcc_wl_t($key,['name'=>$first,'date'=>fcc_webinar_label_time($r,$locale),'url'=>!empty($r['owner_user_id'])?$r['access_url']:forever_business_vip_webinar_url()],$locale);
}
/** One owner/event summary; names and outcomes remain confined to that owner's CRM. */
function fcc_wl_summary(int $uid,int $sid): array {
 if(!fcc_wl_ready())return ['rows'=>[],'pending'=>[],'total_count'=>0,'pending_count'=>0];
 $base=" FROM fcc_webinar_registrations r JOIN fcc_partner_contacts c ON c.id=r.contact_id AND c.user_id=r.user_id LEFT JOIN fcc_webinar_guest_states g ON g.registration_id=r.id AND g.user_id=r.user_id WHERE r.user_id=? AND r.session_id=? AND r.status='registered' AND r.is_self=0 AND r.needs_review=0 AND c.archived_at IS NULL".(fcc_webinar_local()?'':' AND r.is_demo=0');
 $pending=' AND '.fcc_webinar_registration_contact_sql().' AND g.contacted_at IS NULL';
 $select='SELECT r.id,c.name contact_name';
 return ['rows'=>fcc_partner_rows($select.$base.' ORDER BY r.id LIMIT 200',[$uid,$sid]),'pending'=>fcc_partner_rows($select.$base.$pending.' ORDER BY r.id LIMIT 200',[$uid,$sid]),'total_count'=>(int)fcc_partner_one('SELECT COUNT(*) n'.$base,[$uid,$sid])['n'],'pending_count'=>(int)fcc_partner_one('SELECT COUNT(*) n'.$base.$pending,[$uid,$sid])['n']];
}
function fcc_wl_notice(int $uid,array $s,string $kind): ?array {
 if(!fcc_wl_ready())return null;$summary=fcc_wl_summary($uid,(int)$s['id']);$rows=$kind==='after'?$summary['pending']:$summary['rows'];if(!$rows)return null;
 $count=$summary[$kind==='after'?'pending_count':'total_count'];$locale=fcc_mw_locale($s,fcc_wl_locale($uid));$names=implode(', ',array_slice(array_column($rows,'contact_name'),0,3));if($count>3)$names.='…';
 return ['title'=>fcc_wl_t($kind==='after'?'after_title':'before_title',[],$locale),'body'=>fcc_wl_t($kind==='after'?'after_body':'before_body',['count'=>$count,'names'=>$names,'date'=>fcc_webinar_label_time($s,$locale)],$locale),'path'=>'partner-webinar?view=guests&session_id='.(int)$s['id']];
}
function fcc_wl_quiet_after(DateTimeImmutable $at): DateTimeImmutable {
 $local=$at->setTimezone(new DateTimeZone('Europe/Zagreb'));$hour=(int)$local->format('G');
 if($hour>=21)$local=$local->modify('+1 day')->setTime(10,0);elseif($hour<8)$local=$local->setTime(10,0);
 return $local->setTimezone(new DateTimeZone('UTC'));
}
/** Keyset pagination keeps owners beyond the first batch from losing their reminders. */
function fcc_wl_event_pairs(DateTimeImmutable $now,?int $onlyUid=null,int $batch=500): Generator {
 $batch=max(1,min(500,$batch));$cursor=null;
 do {
  $params=[$now->modify('+1 hour')->format('Y-m-d H:i:s'),$now->modify('-2 days')->format('Y-m-d H:i:s'),$now->modify('-1 day')->format('Y-m-d H:i:s')];
  $where='';if($onlyUid){$where.=' AND r.user_id=?';$params[]=$onlyUid;}
  if($cursor){$where.=' AND (s.starts_at_utc,r.user_id,s.id)>(?,?,?)';array_push($params,$cursor['starts_at_utc'],$cursor['user_id'],$cursor['id']);}
  $rows=fcc_partner_rows("SELECT DISTINCT r.user_id,s.* FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id JOIN users u ON u.user_id=r.user_id AND u.status=1 WHERE r.status='registered' AND r.is_self=0 AND r.needs_review=0 AND s.status='scheduled' AND s.starts_at_utc<=? AND (s.ends_at_utc>? OR EXISTS(SELECT 1 FROM fcc_webinar_followups f WHERE f.user_id=r.user_id AND f.session_id=s.id AND f.snoozed=1 AND f.notified_at IS NULL AND f.due_at>=?))".$where." AND (s.owner_user_id IS NULL OR s.owner_user_id=r.user_id) ORDER BY s.starts_at_utc,r.user_id,s.id LIMIT ".$batch,$params);
  foreach($rows as $row){$cursor=$row;yield $row;}
 }while(count($rows)===$batch);
}
function fcc_wl_tick(?DateTimeImmutable $now=null,?int $onlyUid=null): int {
 if(!fcc_wl_ready())return 0;$now=fcc_webinar_now($now);$at=$now->format('Y-m-d H:i:s');$n=0;
 $pairs=fcc_wl_event_pairs($now,$onlyUid);
 foreach($pairs as $s){
  $uid=(int)$s['user_id'];if(fcc_event_offline($s)||!fcc_partner_enabled($uid)||(!fcc_webinar_local()&&$s['is_demo']))continue;
  $prefs=fcc_partner_one('SELECT muted FROM fcc_webinar_member_access WHERE user_id=? AND session_id=?',[$uid,(int)$s['id']]);if(!empty($prefs['muted']))continue;
  $start=new DateTimeImmutable($s['starts_at_utc'].' UTC');$end=new DateTimeImmutable($s['ends_at_utc'].' UTC');
  if($now<$start){
   if(!empty(fcc_event_definition($s)['managed'])&&!fcc_event_access($uid,$s,$now)['allowed'])continue;
   $notice=fcc_wl_notice($uid,$s,'before');if(!$notice)continue;
   $key='webinar:calendar:'.$s['id'].':'.$s['revision'].':reminder60';
   fcc_partner_notify($uid,$key,'webinar',$notice['title'],$notice['body'],$notice['path'],max(60,$start->getTimestamp()-$now->getTimestamp()),$at);
   // Enrich an existing reminder instead of creating a second notification.
   fcc_partner_query('UPDATE fcc_partner_notifications SET title=?,body=?,path=? WHERE user_id=? AND event_key=?',[$notice['title'],$notice['body'],$notice['path'],$uid,$key]);$n++;continue;
  }
  $due=fcc_wl_quiet_after($end->modify('+15 minutes'));
  fcc_partner_query('INSERT IGNORE INTO fcc_webinar_followups(user_id,session_id,due_at) VALUES(?,?,?)',[$uid,(int)$s['id'],$due->format('Y-m-d H:i:s')]);
  $f=fcc_partner_one('SELECT * FROM fcc_webinar_followups WHERE user_id=? AND session_id=?',[$uid,(int)$s['id']]);
  // Never release a stale backlog after a long outage.
  if($f['due_at']>$at||$f['due_at']<$now->modify('-12 hours')->format('Y-m-d H:i:s')||$f['notified_at'])continue;
  $quiet=fcc_wl_quiet_after($now);if($quiet>$now){fcc_partner_query('UPDATE fcc_webinar_followups SET due_at=? WHERE user_id=? AND session_id=? AND notified_at IS NULL',[$quiet->format('Y-m-d H:i:s'),$uid,(int)$s['id']]);continue;}
  $notice=fcc_wl_notice($uid,$s,'after');if(!$notice)continue;
  $key='webinar:guests:'.$s['id'].':'.$s['revision'].':followup:'.$f['snoozed'];
  fcc_partner_notify($uid,$key,'webinar',$notice['title'],$notice['body'],$notice['path'],43200,$at);
  fcc_partner_query('UPDATE fcc_webinar_followups SET notified_at=? WHERE user_id=? AND session_id=? AND due_at=?',[$at,$uid,(int)$s['id'],$f['due_at']]);$n++;
 }return $n;
}
function fcc_wl_snooze(int $uid,int $sid,?DateTimeImmutable $now=null): void {
 if(!fcc_wl_ready()||!fcc_wl_summary($uid,$sid)['pending'])throw new InvalidArgumentException(fcc_wl_t('unavailable'));
 $now=fcc_webinar_now($now);$s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[$sid]);if(!$s||$s['ends_at_utc']>$now->format('Y-m-d H:i:s'))throw new InvalidArgumentException(fcc_wl_t('after_only'));
 $due=$now->setTimezone(new DateTimeZone('Europe/Zagreb'))->modify('+1 day')->setTime(10,0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
 fcc_partner_query('INSERT INTO fcc_webinar_followups(user_id,session_id,due_at,snoozed) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE due_at=IF(snoozed=0,VALUES(due_at),due_at),notified_at=IF(snoozed=0,NULL,notified_at),snoozed=1',[$uid,$sid,$due]);
 fcc_partner_query("UPDATE fcc_partner_notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()),expires_at=UTC_TIMESTAMP() WHERE user_id=? AND event_key LIKE ?",[$uid,'webinar:guests:'.$sid.':%']);
}
function fcc_wl_delivery_allowed(int $uid,string $key,?DateTimeImmutable $now=null): bool {
 if(!preg_match('/^webinar:guests:(\d+):(\d+):followup:(\d+)$/D',$key,$m))return true;
 if(!fcc_wl_ready())return false;$s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$m[1]]);
 if(!$s||$s['status']!=='scheduled'||(int)$s['revision']!==(int)$m[2])return false;
 $f=fcc_partner_one('SELECT * FROM fcc_webinar_followups WHERE user_id=? AND session_id=?',[$uid,(int)$s['id']]);
 $muted=fcc_partner_one('SELECT muted FROM fcc_webinar_member_access WHERE user_id=? AND session_id=?',[$uid,(int)$s['id']]);
 return $f&&(int)$f['snoozed']===(int)$m[3]&&$f['due_at']<=fcc_webinar_now($now)->format('Y-m-d H:i:s')&&empty($muted['muted'])&&(bool)fcc_wl_summary($uid,(int)$s['id'])['pending'];
}
function fcc_wl_cleanup_registration(int $rid): void {
 if(!fcc_wl_ready())return;
 $r=fcc_partner_one('SELECT user_id,session_id FROM fcc_webinar_registrations WHERE id=?',[$rid]);
 if($r){fcc_partner_query('DELETE FROM fcc_partner_activities WHERE user_id=? AND request_key LIKE ?',[(int)$r['user_id'],'webinar:outcome:'.$rid.':%']);fcc_partner_query("UPDATE fcc_partner_notifications SET body='',read_at=COALESCE(read_at,UTC_TIMESTAMP()),expires_at=UTC_TIMESTAMP() WHERE user_id=? AND (event_key LIKE ? OR event_key LIKE ?)",[(int)$r['user_id'],'webinar:guests:'.$r['session_id'].':%','webinar:calendar:'.$r['session_id'].':%']);}
 fcc_partner_query('DELETE FROM fcc_webinar_guest_states WHERE registration_id=?',[$rid]);
 fcc_partner_query('DELETE FROM fcc_webinar_return_invites WHERE source_registration_id=? OR registration_id=?',[$rid,$rid]);
}
function fcc_wl_coach(int $uid): array {
 $out=[];foreach(fcc_wl_rows($uid) as $r){if($r['is_demo']||$r['is_self']||$r['needs_review']||$r['archived_at'])continue;
  $out[]=['contact_id'=>(int)$r['contact_id'],'name'=>$r['contact_name'],'event'=>$r['title'],'starts_at_utc'=>$r['starts_at_utc'],'registration'=>$r['status'],'attendance'=>$r['attendance'],'attendance_source'=>$r['attendance']==='unknown'?'unknown':'member_reported','conversation_member_confirmed'=>(bool)$r['contacted_at'],'may_contact'=>fcc_webinar_may_contact_registration($r)];if(count($out)>=30)break;
 }return ['history'=>$out,'instruction'=>'Prijava, dolazak i razgovor su odvojeni. unknown znači nepoznat dolazak, nikada nedolazak. Ishod je izričita bilješka suradnika, ne Zoom evidencija. Predloži jednu poruku prema poznatom ishodu i poštuj may_contact.'];
}
