<?php
defined('ALTUMCODE') || die();

function fcc_webinar_personal_ready(): bool {
 static $ready;
 return $ready??=!!fcc_partner_one("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_webinar_sessions' AND COLUMN_NAME='owner_user_id'");
}
function fcc_webinar_personal_list(int $uid): array {
 if(!fcc_webinar_personal_ready())return [];
 return fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE owner_user_id=? AND status='scheduled' AND starts_at_utc>UTC_TIMESTAMP() ORDER BY starts_at_utc,id LIMIT 100",[$uid]);
}
function fcc_webinar_personal_owned(int $uid,int $id,bool $lock=false): array {
 $s=fcc_webinar_personal_ready()?fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND owner_user_id=? AND series_id IS NULL'.($lock?' FOR UPDATE':''),[$id,$uid]):null;
 if(!$s)throw new InvalidArgumentException(fcc_t('Vlastiti webinar nije pronađen.'));
 return $s;
}
function fcc_webinar_personal_overview(int $uid): array {
 if(!fcc_webinar_personal_ready())return [];
 return fcc_partner_rows("SELECT s.*,(SELECT COUNT(*) FROM fcc_webinar_registrations r WHERE r.session_id=s.id AND r.user_id=s.owner_user_id AND r.status='registered') registrations FROM fcc_webinar_sessions s WHERE s.owner_user_id=? AND s.series_id IS NULL ORDER BY (s.status='scheduled' AND s.starts_at_utc>UTC_TIMESTAMP()) DESC,s.starts_at_utc DESC,s.id DESC LIMIT 100",[$uid]);
}
function fcc_webinar_personal_values(array $s): array {
 $date=(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($s['timezone']));
 return ['custom_title'=>$s['title'],'custom_description'=>fcc_event_definition($s)['description']??'','custom_date'=>$date->format('Y-m-d'),'custom_time'=>$date->format('H:i'),'custom_timezone'=>$s['timezone'],'custom_access_url'=>$s['access_url'],'custom_duration'=>(int)((strtotime($s['ends_at_utc'].' UTC')-strtotime($s['starts_at_utc'].' UTC'))/60),'revision'=>(int)$s['revision']];
}
/** Cancellation is reversible. Keep stable invitation tokens, registrations and calendar identity. */
function fcc_webinar_personal_change(int $uid,int $id,int $revision,array $input,bool $remove=false,?DateTimeImmutable $now=null): array {
 $now=fcc_webinar_now($now);
 return fcc_webinar_atomic($uid,function()use($uid,$id,$revision,$input,$remove,$now) {
  $s=fcc_webinar_personal_owned($uid,$id,true);
  if(!fcc_webinar_enabled($uid)||(int)(fcc_partner_one('SELECT status FROM users WHERE user_id=?',[$uid])['status']??0)!==1)throw new InvalidArgumentException(fcc_t('Račun trenutačno nema pristup webinarima.'));
  if((int)$s['revision']!==$revision)throw new InvalidArgumentException(fcc_t('Webinar je u međuvremenu izmijenjen. Osvježi prikaz prije spremanja.'));
  if($s['starts_at_utc']<=$now->format('Y-m-d H:i:s'))throw new InvalidArgumentException(fcc_t('Započeti webinar ostaje u povijesti i više se ne može mijenjati. Za novi susret kreiraj novi webinar.'));
  if($remove) {
   if(($input['confirm_remove']??'')!=='1')throw new InvalidArgumentException(fcc_t('Potvrdi uklanjanje ovog webinara.'));
   if($s['status']==='cancelled')return $s;
   $next=$s;$next['status']='cancelled';
  } else {
   fcc_webinar_require_pro($uid);
   $d=fcc_webinar_personal_input($input);
   if($d['starts_at_utc']<=$now->format('Y-m-d H:i:s'))throw new InvalidArgumentException(fcc_t('Odaberi budući datum i vrijeme svojeg webinara.'));
   $definition=json_decode($s['event_json']??'{}',true)?:[];
   foreach(['title','description','date','time','duration','timezone'] as $field)$definition[$field]=$d[$field];
   if($d['starts_at_utc']!==$s['starts_at_utc'])$definition['previous_start']=$s['starts_at_utc'];
   $next=array_replace($s,['title'=>$d['title'],'starts_at_utc'=>$d['starts_at_utc'],'ends_at_utc'=>$d['ends_at_utc'],'timezone'=>$d['timezone'],'access_url'=>$d['access_url'],'event_json'=>fcc_webinar_json($definition),'status'=>'scheduled']);
   if(array_intersect_key($next,array_flip(['title','starts_at_utc','ends_at_utc','timezone','access_url','event_json','status']))===array_intersect_key($s,array_flip(['title','starts_at_utc','ends_at_utc','timezone','access_url','event_json','status'])))return $s;
  }
  $next['revision']=$revision+1;
  fcc_partner_query('UPDATE fcc_webinar_sessions SET title=?,starts_at_utc=?,ends_at_utc=?,timezone=?,access_url=?,event_json=?,status=?,revision=?,updated_at=UTC_TIMESTAMP() WHERE id=? AND owner_user_id=?',[$next['title'],$next['starts_at_utc'],$next['ends_at_utc'],$next['timezone'],$next['access_url'],$next['event_json'],$next['status'],$next['revision'],$id,$uid]);
  // The owner lock also serializes registration and email processing for this event.
  fcc_partner_query("UPDATE fcc_webinar_jobs j JOIN fcc_webinar_registrations r ON r.id=j.registration_id SET j.state='cancelled',j.updated_at=UTC_TIMESTAMP() WHERE r.session_id=? AND r.user_id=? AND j.state IN ('pending','processing') AND j.kind NOT IN ('project','owner_confirmation')",[$id,$uid]);
  foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_registrations WHERE session_id=? AND user_id=? AND status='registered'",[$id,$uid]) as $r) {
   // A pending initial confirmation still needs its email verification link after an edit.
   $kind=$remove?'cancelled':(!$r['email_verified_at']&&$r['reminder_opt_in']?'confirmation':'changed');
   fcc_webinar_job((int)$r['id'],$kind,$next['revision'],$now->format('Y-m-d H:i:s'));
   fcc_webinar_schedule($r,$next,$now);
  }
  if(!$remove)fcc_partner_query('UPDATE fcc_partner_tasks SET due_date=?,version=version+1 WHERE user_id=? AND request_key=? AND completed_at IS NULL',[(new DateTimeImmutable($next['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($next['timezone']))->format('Y-m-d'),$uid,($s['is_demo']?'webinar:demo-goal:':'webinar:goal:').$id]);
  return fcc_webinar_personal_owned($uid,$id);
 });
}
function fcc_webinar_personal_shareable(int $uid,int $sid,int $revision,int $attemptId): bool {
 try {
  $s=fcc_webinar_personal_owned($uid,$sid);$a=fcc_webinar_attempt($uid,$attemptId);
  $public=fcc_webinar_public_attempt(fcc_webinar_unseal($a['token_sealed']));
  return $public&&(int)$a['session_id']===$sid&&(int)$s['revision']===$revision&&fcc_webinar_can_register($public);
 }catch(InvalidArgumentException $e){return false;}
}
function fcc_webinar_access_url(string $url): string {
 $url=trim($url);$p=parse_url($url);$host=strtolower($p['host']??'');
 // This is a guest navigation link, never fetched or embedded by the server.
 if(strlen($url)>1000||!filter_var($url,FILTER_VALIDATE_URL)||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['port'])&&$p['port']!==443||preg_match('/[\x00-\x20\x7f<>\\\\]|%0[ad]/i',$url)||!str_contains($host,'.')||!filter_var($host,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME)||filter_var($host,FILTER_VALIDATE_IP)||preg_match('/\.(local|localhost|test|invalid|internal)$/D',$host)) {
  throw new InvalidArgumentException(fcc_t('Upiši potpunu HTTPS poveznicu za ulazak na Zoom, Google Meet ili drugi webinar servis.'));
 }
 return $url;
}
/** Normalize first; time availability is checked inside the creation transaction after retry lookup. */
function fcc_webinar_personal_input(array $input): array {
 $d=[];
 foreach(['title'=>180,'description'=>1600,'date'=>10,'time'=>5,'timezone'=>64,'access_url'=>1000] as $name=>$limit){
  $value=$input['custom_'.$name]??($name==='timezone'?'Europe/Zagreb':'');
  if(!is_string($value)||mb_strlen($value)>$limit)throw new InvalidArgumentException(fcc_t('Provjeri podatke svojeg webinara i duljinu teksta.'));
  $d[$name]=trim($value);
 }
 $d['title']=fcc_partner_text($d['title'],180);$d['description']=fcc_partner_text($d['description'],1600);
 if($d['title']==='')throw new InvalidArgumentException(fcc_t('Upiši naziv svojeg webinara.'));
 $d['access_url']=fcc_webinar_access_url($d['access_url']);
 if(!in_array($d['timezone'],DateTimeZone::listIdentifiers(),true))throw new InvalidArgumentException(fcc_t('Odaberi vremensku zonu.'));
 $d['duration']=filter_var($input['custom_duration']??60,FILTER_VALIDATE_INT);
 if($d['duration']===false||$d['duration']<5||$d['duration']>480)throw new InvalidArgumentException(fcc_t('Trajanje webinara može biti od 5 do 480 minuta.'));
 $zone=new DateTimeZone($d['timezone']);$value=$d['date'].' '.$d['time'];
 $date=DateTimeImmutable::createFromFormat('!Y-m-d H:i',$value,$zone);
 if(!$date||$date->format('Y-m-d H:i')!==$value)throw new InvalidArgumentException(fcc_t('Provjeri datum i vrijeme svojeg webinara.'));
 // A repeated DST hour needs an unambiguous time rather than PHP's implicit choice.
 foreach([-7200,-3600,-1800,1800,3600,7200] as $offset){
  if((new DateTimeImmutable('@'.($date->getTimestamp()+$offset)))->setTimezone($zone)->format('Y-m-d H:i')===$value)throw new InvalidArgumentException(fcc_t('Odabrani sat pojavljuje se dvaput zbog promjene sata. Odaberi drugo vrijeme.'));
 }
 $start=$date->setTimezone(new DateTimeZone('UTC'));
 $d['starts_at_utc']=$start->format('Y-m-d H:i:s');$d['ends_at_utc']=$start->modify('+'.$d['duration'].' minutes')->format('Y-m-d H:i:s');
 return $d;
}
/** Only called by prepare while holding the owner's transaction and idempotency lock. */
function fcc_webinar_personal_create(int $uid,array $d,int $demo): array {
 if(!fcc_webinar_personal_ready())throw new InvalidArgumentException(fcc_t('Vlastiti webinari još nisu dostupni.'));
 fcc_webinar_require_pro($uid);
 if($d['starts_at_utc']<=gmdate('Y-m-d H:i:s'))throw new InvalidArgumentException(fcc_t('Odaberi budući datum i vrijeme svojeg webinara.'));
 $key='personal-'.bin2hex(random_bytes(12));
 $definition=['kind'=>'other','mode'=>'online','title'=>$d['title'],'description'=>$d['description'],'date'=>$d['date'],'time'=>$d['time'],'duration'=>$d['duration'],'timezone'=>$d['timezone'],'audience'=>'selected','selected'=>[$uid],'guests'=>1,'announce'=>0,'reminders'=>[],'education'=>0];
 fcc_partner_query("INSERT INTO fcc_webinar_sessions (owner_user_id,series_key,occurrence_key,title,starts_at_utc,ends_at_utc,timezone,access_url,calendar_uid,event_json,is_demo,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())",[$uid,$key,$d['date'],$d['title'],$d['starts_at_utc'],$d['ends_at_utc'],$d['timezone'],$d['access_url'],$key.'@forevercard.club',fcc_webinar_json($definition),$demo]);
 return fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)database()->insert_id]);
}
