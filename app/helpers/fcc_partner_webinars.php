<?php
/** Webinar module. All guest attribution is resolved from server-owned invitation records. */
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_webinar_mail.php';

/** Translate shared catalogue titles only; member-created event names remain verbatim. */
function fcc_webinar_title(array $session, ?string $locale=null): string {
 return empty($session['owner_user_id'])&&empty(fcc_event_definition($session)['managed'])?fcc_t((string)($session['title']??''),[],$locale):(string)($session['title']??'');
}
function fcc_webinar_config(): array {
 static $c; return $c??=require APP_PATH.'config/fcc_webinar.php';
}
function fcc_webinar_local(): bool { return getenv('FCC_LOCAL')==='1' && DATABASE_NAME==='fcc_partner_local' && DATABASE_SERVER==='db'; }
function fcc_webinar_schema(): bool {
 static $ready; return $ready??=!!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_webinar_jobs'");
}
function fcc_webinar_enabled(int $uid=0,bool $published=false): bool {
 if(getenv('FCC_WEBINAR_INVITES_ENABLED')==='0'||!fcc_webinar_schema())return false;
 if(fcc_rollout_managed())return (fcc_webinar_local()||getenv('FCC_WEBINAR_INVITES_ENABLED')==='1')&&(!$uid||($published?fcc_rollout_public_owner($uid):fcc_partner_enabled($uid)));
 if(fcc_webinar_local()) {
  if(!$uid)return true;
  $u=fcc_partner_one('SELECT email FROM users WHERE user_id=?',[$uid]);
  return in_array($uid,fcc_webinar_config()['pilot_ids'],true)||preg_match('/^webinar-qa-[a-f0-9]+@fcc\.test$/D',$u['email']??'')===1;
 }
 if(getenv('FCC_WEBINAR_INVITES_ENABLED')!=='1')return false;
 return $uid>0 && in_array($uid,array_map('intval',explode(',',(string)getenv('FCC_WEBINAR_PILOT_IDS'))),true);
}
function fcc_webinar_pro(int $uid,bool $published=false): bool {
 if(!fcc_webinar_enabled($uid,$published))return false;
 $r=fcc_partner_one('SELECT status,plan_settings,plan_expiration_date,extra FROM users WHERE user_id=?',[$uid]);
 if(!$r||(int)$r['status']!==1)return false;
 $p=json_decode($r['plan_settings']??'{}');$extra=json_decode($r['extra']??'{}');
 if(empty($p->ai_growth_plan_is_enabled)||!empty($extra->billing_access_revoked_at))return false;
 return fcc_ai_is_plan_expiration_active((string)($r['plan_expiration_date']??''))||fcc_ai_is_billing_grace_active($extra);
}
function fcc_webinar_require_pro(int $uid): void {
 if(!fcc_webinar_pro($uid))throw new InvalidArgumentException(fcc_t('Video pozivnice dostupne su uz aktivni PRO paket.'));
}
function fcc_webinar_now(?DateTimeImmutable $now=null): DateTimeImmutable {
 // Controllers never bind this internal clock argument to request data.
 return ($now??new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('UTC'));
}
function fcc_webinar_key(): string {
 static $key;
 if($key)return $key;
 if(fcc_webinar_local())$key=@file_get_contents(dirname(APP_PATH).'/local/webinar.key');
 else $key=base64_decode((string)getenv('FCC_WEBINAR_SECRET'),true);
 if(!is_string($key)||strlen($key)!==32)throw new RuntimeException(fcc_t('Webinar secret is not configured.'));
 return $key;
}
function fcc_webinar_seal(string $text): string {
 $iv=random_bytes(12);$tag='';$encrypted=openssl_encrypt($text,'aes-256-gcm',fcc_webinar_key(),OPENSSL_RAW_DATA,$iv,$tag,'fcc-webinar-v1');
 if($encrypted===false)throw new RuntimeException(fcc_t('Cannot protect invitation.'));
 return base64_encode($iv.$tag.$encrypted);
}
function fcc_webinar_unseal(string $sealed): string {
 $raw=base64_decode($sealed,true);
 if($raw===false||strlen($raw)<29)throw new RuntimeException(fcc_t('Invalid invitation.'));
 $value=openssl_decrypt(substr($raw,28),'aes-256-gcm',fcc_webinar_key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16),'fcc-webinar-v1');
 if($value===false)throw new RuntimeException(fcc_t('Invalid invitation.'));
 return $value;
}
function fcc_webinar_hash(string $value): string {return hash_hmac('sha256',$value,fcc_webinar_key());}
function fcc_webinar_token(): string {return bin2hex(random_bytes(32));}
function fcc_webinar_json(array $value): string {return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
function fcc_webinar_url(array $attempt): string {$s=fcc_partner_one('SELECT event_json FROM fcc_webinar_sessions WHERE id=?',[(int)$attempt['session_id']]);return fcc_mw_url($s??[],'webinar-invite/'.fcc_webinar_unseal($attempt['token_sealed']));}
function fcc_webinar_manage_url(array $r): string {return SITE_URL.'webinar-invite/manage/'.fcc_webinar_unseal($r['management_sealed']);}
function fcc_webinar_atomic(int $uid,callable $fn) {
 $db=database();$db->begin_transaction();
 try {
  if(!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$uid]))throw new InvalidArgumentException(fcc_t('Račun nije dostupan.'));
  $result=$fn();$db->commit();return $result;
 } catch(Throwable $e) {$db->rollback();throw $e;}
}
function fcc_webinar_video(string $choice,string $input=''): array {
 if($choice==='none')return ['choice'=>'none','provider'=>'none','title'=>'Poziv na webinar','id'=>'','hash'=>''];
 if($choice!=='custom') {
  $video=fcc_webinar_config()['official_videos'][$choice]??null;
  if(!$video)throw new InvalidArgumentException(fcc_t('Odaberi video.'));
  if(($video['provider']??'')==='demo'&&!fcc_webinar_local())throw new InvalidArgumentException(fcc_t('Službeni video još nije objavljen.'));
  return $video+['choice'=>$choice];
 }
 $p=parse_url(trim($input));
 if(!$p||!in_array($p['scheme']??'',['http','https'],true)||isset($p['user'])||isset($p['pass'])||isset($p['port'])||strlen($input)>1000)throw new InvalidArgumentException(fcc_t('Upiši YouTube ili Vimeo poveznicu.'));
 $host=strtolower($p['host']??'');$path=$p['path']??'';$query=[];parse_str($p['query']??'',$query);
 $id='';
 if($host==='youtu.be')$id=trim($path,'/');
 elseif(in_array($host,['youtube.com','www.youtube.com','m.youtube.com','www.youtube-nocookie.com','youtube-nocookie.com'],true)) {
  if($path==='/watch')$id=is_string($query['v']??null)?$query['v']:'';
  elseif(preg_match('~^/(?:embed|shorts|live)/([A-Za-z0-9_-]{11})/?$~D',$path,$m))$id=$m[1];
 }
 if(preg_match('/^[A-Za-z0-9_-]{11}$/D',$id))return ['choice'=>'custom','title'=>'Moj video','provider'=>'youtube','id'=>$id,'hash'=>''];
 if(in_array($host,['vimeo.com','www.vimeo.com','player.vimeo.com'],true)&&preg_match('~^/(?:video/)?([0-9]{5,12})(?:/([a-fA-F0-9]{6,64}))?/?$~D',$path,$m)) {
  $hash=$m[2]??($query['h']??'');
  if(!is_string($hash)||($hash!==''&&!preg_match('/^[a-fA-F0-9]{6,64}$/D',$hash)))throw new InvalidArgumentException(fcc_t('Vimeo poveznica nije valjana.'));
  return ['choice'=>'custom','title'=>'Moj video','provider'=>'vimeo','id'=>$m[1],'hash'=>$hash];
 }
 throw new InvalidArgumentException(fcc_t('Podržane su izravne YouTube i Vimeo poveznice na video.'));
}
function fcc_webinar_video_url(array $v,bool $embed=false): string {
 if(($v['provider']??'')==='youtube'&&preg_match('/^[A-Za-z0-9_-]{11}$/D',$v['id']??''))return $embed?'https://www.youtube-nocookie.com/embed/'.$v['id'].'?rel=0&playsinline=1':'https://www.youtube.com/watch?v='.$v['id'];
 if(($v['provider']??'')==='vimeo'&&preg_match('/^[0-9]{5,12}$/D',$v['id']??''))return ($embed?'https://player.vimeo.com/video/':'https://vimeo.com/').$v['id'].(!empty($v['hash'])?($embed?'?h=':'/').rawurlencode($v['hash']):'');
 return '';
}
function fcc_webinar_session(int $uid,?DateTimeImmutable $now=null,int $sessionId=0): array {
 if($sessionId&&fcc_webinar_personal_ready()) {
  $personal=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[$sessionId]);
  if(!empty($personal['owner_user_id'])) {
   if((int)$personal['owner_user_id']!==$uid||!fcc_webinar_session_open($personal,$now))throw new InvalidArgumentException(fcc_t('Ovaj webinar nije dostupan za tvoju pozivnicu.'));
   return $personal;
  }
 }
 if(function_exists('fcc_events_enabled')&&fcc_events_enabled()){
  $list=fcc_event_list($uid,$now,true);if(!$sessionId)$list=fcc_event_channel_list($list,'webinar');foreach($list as $s)if(!$sessionId||(int)$s['id']===$sessionId)return $s;
  throw new InvalidArgumentException(fcc_t('Nema dostupnog objavljenog termina za pozivanje.'));
 }
 $cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));
 if(!$cards)throw new InvalidArgumentException(fcc_t('Najprije aktiviraj svoju FCC karticu.'));
 $event=fcc_partner_webinar_invitation($cards,$now);
 if(!$event['starts_at'])throw new InvalidArgumentException(fcc_t('Novi termin još nije potvrđen.'));
 $start=$event['starts_at']->setTimezone(new DateTimeZone('UTC'));$date=$event['starts_at']->format('Y-m-d');
 $demo=fcc_webinar_local();
 // A short local session is explicitly a fixture, not an assertion about the real event duration.
 $duration=$demo?30:(int)getenv('FCC_WEBINAR_DURATION_MINUTES');
 if($duration<5||$duration>480)throw new InvalidArgumentException(fcc_t('Trajanje webinara još nije potvrđeno.'));
 $access=forever_business_vip_webinar_url();
 if(!filter_var($access,FILTER_VALIDATE_URL))throw new InvalidArgumentException(fcc_t('Poveznica webinara još nije potvrđena.'));
 fcc_partner_query("INSERT IGNORE INTO fcc_webinar_sessions (series_key,occurrence_key,title,starts_at_utc,ends_at_utc,timezone,access_url,calendar_uid,is_demo,created_at,updated_at) VALUES ('forever-business',?,'Upoznaj Forever posao',?,?,'Europe/Zagreb',?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())",[$date,$start->format('Y-m-d H:i:s'),$start->modify('+'.$duration.' minutes')->format('Y-m-d H:i:s'),$access,'fcc-webinar-'.$date.'@forevercard.club',$demo?1:0]);
 $session=fcc_partner_one("SELECT * FROM fcc_webinar_sessions WHERE series_key='forever-business' AND occurrence_key=?",[$date]);
 if($session['status']!=='scheduled')throw new InvalidArgumentException(fcc_t('Ovaj termin trenutačno nije otvoren.'));
 if($sessionId&&(int)$session['id']!==$sessionId)throw new InvalidArgumentException(fcc_t('Odabrani webinar nije dostupan.'));
 return $session;
}
function fcc_webinar_session_open(array $s,?DateTimeImmutable $now=null): bool {
 if($s['status']!=='scheduled'||$s['starts_at_utc']<=fcc_webinar_now($now)->format('Y-m-d H:i:s'))return false;
 if(!empty($s['owner_user_id'])||!empty($s['series_id'])&&fcc_events_enabled())return true;
 return fcc_partner_notification_config('journey90_webinar_'.$s['occurrence_key'],null)!=='cancelled';
}
function fcc_webinar_attempt(int $uid,int $id): array {
 $a=fcc_partner_one('SELECT * FROM fcc_webinar_invite_attempts WHERE id=? AND user_id=?',[$id,$uid]);
 if(!$a)throw new InvalidArgumentException(fcc_t('Pozivnica nije pronađena.'));
 return $a;
}
function fcc_webinar_public_attempt(string $token,bool $lock=false): ?array {
 if(!preg_match('/^[a-f0-9]{64}$/D',$token))return null;
 return fcc_partner_one("SELECT a.*,s.title,s.starts_at_utc,s.ends_at_utc,s.timezone,s.status session_status,s.occurrence_key,s.revision,s.access_url,s.event_json,u.name owner_name FROM fcc_webinar_invite_attempts a JOIN fcc_webinar_sessions s ON s.id=a.session_id JOIN users u ON u.user_id=a.user_id WHERE a.token_hash=?".($lock?" FOR UPDATE":""), [hash('sha256',$token)]);
}
function fcc_webinar_can_register(array $a,?DateTimeImmutable $now=null): bool {
 $standard=($a['invite_kind']??'video')==='standard';
 if(!empty($a['revoked_at'])||!($standard?fcc_webinar_standard_enabled((int)$a['user_id'],true):fcc_webinar_pro((int)$a['user_id'],true)))return false;
 if(!fcc_partner_one('SELECT link_id FROM links WHERE link_id=? AND user_id=? AND is_enabled=1',[(int)$a['card_id'],(int)$a['user_id']]))return false;
 $session=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$a['session_id']]);
 if(!$session)return false;
 if($standard&&(empty($session['series_id'])||!empty($session['owner_user_id'])))return false;
 if(!empty($session['owner_user_id']))return (int)$session['owner_user_id']===(int)$a['user_id']&&fcc_webinar_session_open($session,$now);
 if(!empty($session['series_id'])&&fcc_events_enabled())return fcc_event_invitable((int)$a['user_id'],$session,$now);
 return fcc_webinar_session_open(['status'=>$a['session_status'],'starts_at_utc'=>$a['starts_at_utc'],'occurrence_key'=>$a['occurrence_key']],$now);
}
function fcc_webinar_prepare(int $uid,array $input): array {
 fcc_webinar_require_pro($uid);
 $key=(string)($input['request_key']??'');
 if(!preg_match('/^[a-f0-9]{32}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi obrazac prije pripreme.'));
 $video=fcc_webinar_video(fcc_partner_text($input['video_choice']??'short',20),fcc_partner_text($input['video_url']??'',1000));
 $intro=fcc_partner_text($input['intro']??'',400);$contact=(int)($input['contact_id']??0);$stepId=(int)($input['step_id']??0);
 $demo=fcc_webinar_local()?1:0;
 // Internal integration tests may exercise real evidence on isolated synthetic accounts only.
 if(PHP_SAPI==='cli'&&isset($input['_qa_real'])&&preg_match('/^webinar-qa-[a-f0-9]+@fcc\.test$/D',fcc_partner_one('SELECT email FROM users WHERE user_id=?',[$uid])['email']))$demo=0;
 $sessionId=(int)($input['session_id']??0);$custom=null;
 if(($input['webinar_mode']??'existing')==='custom')$input['webinar_choice']='custom';
 elseif(isset($input['webinar_mode'])&&$input['webinar_mode']!=='existing')throw new InvalidArgumentException(fcc_t('Odaberi postojeći webinar ili kreiraj svoj.'));
 if(isset($input['webinar_choice'])) {
  $choice=$input['webinar_choice'];
  if($choice==='default')$sessionId=(int)($input['default_session_id']??0);
  elseif($choice==='custom'){$sessionId=0;$custom=fcc_webinar_personal_input($input);}
  elseif(is_string($choice)&&preg_match('/^session:([1-9][0-9]*)$/D',$choice,$match))$sessionId=(int)$match[1];
  else throw new InvalidArgumentException(fcc_t('Odaberi webinar za svoju pozivnicu.'));
 }
 if($custom&&!in_array($video['choice'],['custom','none'],true))throw new InvalidArgumentException(fcc_t('Za svoj webinar odaberi vlastiti video ili pozivnicu bez videa.'));
 $expectedRevision=$custom?0:(int)($input['session_revision']??($input['session_revisions'][$sessionId]??0));
 // Preserve the original hash for existing requests; own event details are part of new request identity.
 $channel=isset($input['invite_channel'])?fcc_partner_choice($input['invite_channel'],['webinar'=>1,'events'=>1]):null;
 $payload=[$video,$intro,$contact,$stepId,$demo,$sessionId];if($custom)$payload[]=$custom;
 $hash=hash('sha256',fcc_webinar_json($payload));
 return fcc_webinar_atomic($uid,function()use($uid,$key,$video,$intro,$contact,$stepId,$demo,$hash,$sessionId,$expectedRevision,$custom,$channel) {
  fcc_webinar_require_pro($uid);
  $old=fcc_partner_one('SELECT * FROM fcc_webinar_invite_attempts WHERE user_id=? AND client_request_key=?',[$uid,$key]);
  if($old) {if(!hash_equals($old['payload_hash'],$hash))throw new InvalidArgumentException(fcc_t('Pozivnica je već pripremljena s drugim sadržajem. Odaberi novu pozivnicu.'));return $old;}
  if($contact&&fcc_partner_contact($uid,$contact)['contact_permission']==='do_not_contact')throw new InvalidArgumentException(fcc_t('Ova osoba ne želi javljanje.'));
  $step=null;
  if($stepId) {
   $step=fcc_partner_one("SELECT s.* FROM fcc_partner_journey_steps s JOIN fcc_partner_journey_cycles c ON c.id=s.cycle_id AND c.user_id=s.user_id WHERE s.id=? AND s.user_id=? AND c.status='active'",[$stepId,$uid]);
   if(!$step||!fcc_webinar_learning_task((int)$step['position']))throw new InvalidArgumentException(fcc_t('Ovaj korak nije povezan s webinar pozivnicom.'));
  }
  $cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));$card=$cards[0]??null;
  if(!$card)throw new InvalidArgumentException(fcc_t('Najprije aktiviraj svoju FCC karticu.'));
  $s=$custom?fcc_webinar_personal_create($uid,$custom,$demo):fcc_webinar_session($uid,null,$sessionId);
  $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? FOR UPDATE',[(int)$s['id']]);
  if($channel!==null&&fcc_event_channel($s)!==$channel)throw new InvalidArgumentException(fcc_event_hub_t('Odaberi događaj iz otvorene rubrike.'));
  if(fcc_event_channel($s)==='events'&&!in_array($video['choice'],['custom','none'],true))throw new InvalidArgumentException(fcc_event_hub_t('Za ovaj događaj dodaj svoj video ili odaberi pozivnicu bez videa.'));
  if(!empty($s['owner_user_id'])&&(int)$s['owner_user_id']!==$uid)throw new InvalidArgumentException(fcc_t('Ovaj webinar ne pripada tvojem računu.'));
  if(!empty($s['owner_user_id'])&&!in_array($video['choice'],['custom','none'],true))throw new InvalidArgumentException(fcc_t('Za svoj webinar odaberi vlastiti video ili pozivnicu bez videa.'));
  if($expectedRevision&&(int)$s['revision']!==$expectedRevision)throw new InvalidArgumentException(fcc_t('Termin je promijenjen. Osvježi prikaz.'));
  if(!empty($s['series_id'])&&fcc_events_enabled()&&!fcc_event_invitable($uid,$s))throw new InvalidArgumentException(fcc_t('Termin nije otvoren za nove prijave.'));
  if(!fcc_webinar_session_open($s))throw new InvalidArgumentException(fcc_t('Termin nije otvoren za nove prijave.'));
  $recent=fcc_partner_one('SELECT COUNT(*) n FROM fcc_webinar_invite_attempts WHERE user_id=? AND prepared_at>UTC_TIMESTAMP()-INTERVAL 1 HOUR',[$uid]);
  if((int)$recent['n']>=100)throw new InvalidArgumentException(fcc_t('Pripremljeno je mnogo pozivnica. Nastavi malo kasnije.'));
  $locale=fcc_mw_locale($s,(string)(fcc_partner_one('SELECT language FROM users WHERE user_id=?',[$uid])['language']??'english'));
  fcc_partner_query('INSERT INTO fcc_webinar_invitations (user_id,card_id,video_json,short_intro,locale,created_at,updated_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE video_json=VALUES(video_json),short_intro=VALUES(short_intro),version=version+1,updated_at=UTC_TIMESTAMP()',[$uid,(int)$card['link_id'],fcc_webinar_json($video),$intro,$locale]);
  $config=fcc_partner_one('SELECT id FROM fcc_webinar_invitations WHERE user_id=? AND card_id=?',[$uid,(int)$card['link_id']]);
  $token=fcc_webinar_token();
  $content=$step?json_decode($step['content_json'],true):[];
  fcc_partner_query('INSERT INTO fcc_webinar_invite_attempts (user_id,invitation_id,card_id,session_id,token_hash,token_sealed,client_request_key,payload_hash,recipient_contact_id,journey_cycle_id,journey_step_id,task_key,video_snapshot,intro_snapshot,locale,is_demo,prepared_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())',[$uid,(int)$config['id'],(int)$card['link_id'],(int)$s['id'],hash('sha256',$token),fcc_webinar_seal($token),$key,$hash,$contact?:null,$step?(int)$step['cycle_id']:null,$stepId?:null,$content['id']??null,fcc_webinar_json($video),$intro,$locale,$demo]);
  return fcc_webinar_attempt($uid,(int)database()->insert_id);
 });
}
function fcc_webinar_record(int $uid,int $id,string $kind,bool $confirmed=false): array {
 $allowed=['whatsapp'=>'whatsapp_requested_at','copy'=>'copied_at','share'=>'share_requested_at','sent'=>'sent_self_reported_at'];
 if(!isset($allowed[$kind]))throw new InvalidArgumentException(fcc_t('Radnja nije dostupna.'));
 return fcc_webinar_atomic($uid,function()use($uid,$id,$kind,$confirmed,$allowed) {
  fcc_webinar_require_pro($uid);$a=fcc_webinar_attempt($uid,$id);
  if($a['revoked_at'])throw new InvalidArgumentException(fcc_t('Pozivnica je povučena.'));
  if($kind==='sent'&&!$confirmed)throw new InvalidArgumentException(fcc_t('Potvrdi da si osobno poslao primjerenu poruku.'));
  if($a['recipient_contact_id']&&fcc_partner_contact($uid,(int)$a['recipient_contact_id'])['contact_permission']==='do_not_contact')throw new InvalidArgumentException(fcc_t('Osoba ne želi kontakt.'));
  $field=$allowed[$kind];
  fcc_partner_query("UPDATE fcc_webinar_invite_attempts SET $field=COALESCE($field,UTC_TIMESTAMP()) WHERE id=? AND user_id=?",[$id,$uid]);
  if($kind==='sent'&&!$a['sent_self_reported_at']) {
   $key='webinar:sent:'.$id;
   fcc_partner_query("INSERT IGNORE INTO fcc_partner_activities (user_id,contact_id,kind,note,request_key,created_at) VALUES (?,?,?, ?,?,UTC_TIMESTAMP())",[$uid,$a['recipient_contact_id']?(int)$a['recipient_contact_id']:null,$a['is_demo']?'webinar_demo_sent':'journey_sent',($a['is_demo']?'Probno slanje: ':'Slanje potvrdio suradnik: ').'Pozivnica '.$id,$key]);
   $activity=(int)(fcc_partner_one('SELECT id FROM fcc_partner_activities WHERE user_id=? AND request_key=?',[$uid,$key])['id']??0);
   if($a['journey_step_id']) {
    $eventKind=$a['is_demo']?'webinar_demo_sent':'message_sent';
    fcc_partner_query('INSERT IGNORE INTO fcc_partner_pilot_events (user_id,step_id,activity_id,kind,source,detail,request_key,created_at) VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP())',[$uid,(int)$a['journey_step_id'],$activity?:null,$eventKind,'self_reported','Pozivnica '.$id.': korisnička potvrda slanja.',$key]);
    $event=fcc_partner_one('SELECT id FROM fcc_partner_pilot_events WHERE user_id=? AND request_key=?',[$uid,$key]);
    fcc_partner_query('UPDATE fcc_webinar_invite_attempts SET sent_evidence_id=? WHERE id=?',[(int)$event['id'],$id]);
   }
  }
  return fcc_webinar_attempt($uid,$id);
 });
}
function fcc_webinar_job(int $rid,string $kind,int $revision,string $at): void {
 $key=$kind.':'.$rid.':'.$revision;
 fcc_partner_query("INSERT IGNORE INTO fcc_webinar_jobs (registration_id,kind,session_revision,scheduled_at_utc,unique_key,created_at,updated_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())",[$rid,$kind,$revision,$at,$key]);
}
function fcc_webinar_schedule(array $r,array $s,DateTimeImmutable $now): void {
 if(!$r['reminder_opt_in']||!$r['email_verified_at']||$r['status']!=='registered'||$r['email_failed_at']||$s['status']!=='scheduled')return;
 $start=new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC'));
 if(!empty(fcc_event_definition($s)['time_pending']))$start=$start->setTimezone(new DateTimeZone('Europe/Zagreb'))->setTime(9,0)->setTimezone(new DateTimeZone('UTC'));
 foreach(['reminder24'=>24,'reminder1'=>1] as $kind=>$hours) {
  if(!empty(fcc_event_definition($s)['time_pending'])&&$kind==='reminder1')continue;
  $at=$start->modify('-'.$hours.' hours');
  if($at>$now)fcc_webinar_job((int)$r['id'],$kind,(int)$s['revision'],$at->format('Y-m-d H:i:s'));
 }
}
/** A submitted registration supports a personal reply about that event, never a general contact grant. */
function fcc_webinar_may_contact_registration(array $r,?array $contact=null): bool {
 if(($r['status']??'')!=='registered'||!empty($r['needs_review'])||(int)($r['id']??0)<=0||(int)($r['user_id']??0)<=0||(int)($r['session_id']??0)<=0||(int)($r['contact_id']??0)<=0)return false;
 if($contact===null){
  $joined=['contact_row_id','contact_user_id','contact_permission','archived_at','contact_source_kind','contact_source_id','contact_version','contact_created_at','contact_updated_at'];
  if(count(array_intersect($joined,array_keys($r)))===count($joined)){
   $contact=['id'=>$r['contact_row_id'],'user_id'=>$r['contact_user_id'],'contact_permission'=>$r['contact_permission'],'archived_at'=>$r['archived_at'],'source_kind'=>$r['contact_source_kind'],'source_id'=>$r['contact_source_id'],'version'=>$r['contact_version'],'created_at'=>$r['contact_created_at'],'updated_at'=>$r['contact_updated_at']];
  }else{
   $contact=fcc_partner_one('SELECT id,user_id,contact_permission,archived_at,source_kind,source_id,version,created_at,updated_at FROM fcc_partner_contacts WHERE id=? AND user_id=?',[(int)$r['contact_id'],(int)$r['user_id']]);
  }
 }
 if(!$contact||(int)($contact['id']??0)!==(int)$r['contact_id']||(int)($contact['user_id']??0)!==(int)$r['user_id']||!empty($contact['archived_at']))return false;
 if(($contact['contact_permission']??'do_not_contact')!=='do_not_contact')return true;
 // Only the unchanged contact created by this exact registration used the old automatic block.
 return empty($r['whatsapp_opt_in'])&&($contact['source_kind']??'')==='webinar'&&(int)($contact['source_id']??0)===(int)$r['id']&&(int)($contact['version']??0)===1&&!empty($contact['created_at'])&&$contact['created_at']===($contact['updated_at']??null);
}
/** The same contact rule is evaluated by SQL for unbounded owner/event summary counts. */
function fcc_webinar_registration_contact_sql(): string {
 return "(r.status='registered' AND r.needs_review=0 AND c.archived_at IS NULL AND (c.contact_permission<>'do_not_contact' OR (r.whatsapp_opt_in=0 AND c.source_kind='webinar' AND c.source_id=r.id AND c.version=1 AND c.created_at=c.updated_at)))";
}
function fcc_webinar_registration_input(array $input): array {
 if(!empty($input['website']))throw new InvalidArgumentException(fcc_t('Prijava nije prihvaćena.'));
 $name=fcc_partner_text($input['name']??'',120);$email=mb_strtolower(fcc_partner_text($input['email']??'',320));
 $phone=fcc_partner_contact_phone(fcc_partner_text($input['phone']??'',40),true);
 if(mb_strlen($name)<2||!filter_var($email,FILTER_VALIDATE_EMAIL)||!preg_match('/^\+[1-9][0-9]{7,14}$/D',$phone))throw new InvalidArgumentException(fcc_t('Provjeri ime, email i telefon s pozivnim brojem države.'));
 return [$name,$email,$phone];
}
function fcc_webinar_register(string $token,array $input,?DateTimeImmutable $now=null,array $identity=[]): array {
 $now=fcc_webinar_now($now);$a=fcc_webinar_public_attempt($token);
 if(!$a||!fcc_webinar_can_register($a,$now))throw new InvalidArgumentException(fcc_t('Pozivnica trenutačno ne prima prijave.'));
 [$name,$email,$phone]=fcc_webinar_registration_input($input);
 $offline=fcc_event_offline($a);$uid=(int)$a['user_id'];$emailKey=fcc_webinar_hash('email:'.$email);$phoneKey=fcc_webinar_hash('phone:'.$phone);
 $result=fcc_webinar_atomic($uid,function()use($uid,$a,$token,$input,$name,$email,$phone,$emailKey,$phoneKey,$now,$offline,$identity) {
  $fresh=fcc_webinar_public_attempt($token,true);
  if(!$fresh||!fcc_webinar_can_register($fresh,$now))throw new InvalidArgumentException(fcc_t('Termin više ne prima prijave.'));
  if(isset($input['session_revision'])&&(int)$input['session_revision']!==(int)$fresh['revision'])throw new InvalidArgumentException(fcc_t('Termin je promijenjen. Osvježi pozivnicu i potvrdi novi datum prije prijave.'));
  $returnContext=null;
  if(!empty($identity['return_token'])){
   $returnContext=fcc_wl_return_context($identity['return_token'],$now);
   if(!$returnContext||(int)$returnContext['invite']['user_id']!==$uid||(int)$returnContext['invite']['session_id']!==(int)$a['session_id']||$returnContext['source']['email']!==$email||$returnContext['source']['phone']!==$phone)throw new InvalidArgumentException(fcc_wl_t('unavailable'));
  }
  $matches=fcc_partner_rows('SELECT * FROM fcc_webinar_registrations WHERE user_id=? AND session_id=? AND (email_key=? OR phone_key=?) FOR UPDATE',[$uid,(int)$a['session_id'],$emailKey,$phoneKey]);
  if($matches) {
   if($returnContext&&(count($matches)!==1||(int)$matches[0]['contact_id']!==(int)$returnContext['invite']['contact_id']))throw new InvalidArgumentException(fcc_wl_t('review'));
   if(count($matches)!==1||!hash_equals($matches[0]['email_key'],$emailKey)||!hash_equals($matches[0]['phone_key'],$phoneKey))throw new InvalidArgumentException(fcc_t('Podaci se ne podudaraju. Provjeri unos ili se javi osobi koja te pozvala.'));
   return ['new'=>false,'registration_id'=>(int)$matches[0]['id'],'cancelled'=>$matches[0]['status']==='cancelled'];
  }
  $count=fcc_partner_one('SELECT COUNT(*) n FROM fcc_webinar_registrations WHERE attempt_id=? AND received_at>?',[(int)$a['id'],$now->modify('-1 hour')->format('Y-m-d H:i:s')]);
  if((int)$count['n']>=30)throw new InvalidArgumentException(fcc_t('Pokušaj ponovno malo kasnije.'));
  $owner=fcc_partner_one('SELECT email FROM users WHERE user_id=?',[$uid]);$isSelf=mb_strtolower($owner['email'])===$email;
  $manage=fcc_webinar_token();$verify=fcc_webinar_token();$at=$now->format('Y-m-d H:i:s');
  fcc_partner_query('INSERT INTO fcc_webinar_registrations (user_id,session_id,attempt_id,name,email,phone,phone_key,email_key,management_hash,management_sealed,verification_hash,verification_sealed,reminder_opt_in,whatsapp_opt_in,consent_version,consent_at,is_demo,is_self,received_at,language) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',[$uid,(int)$a['session_id'],(int)$a['id'],$name,$email,$phone,$phoneKey,$emailKey,hash('sha256',$manage),fcc_webinar_seal($manage),hash('sha256',$verify),fcc_webinar_seal($verify),empty($input['reminders'])?0:1,empty($input['whatsapp_permission'])?0:1,$offline?'fcc-seminar-interest-v2':fcc_webinar_config()['consent_version'],$at,(int)$a['is_demo'],$isSelf?1:0,$at,fcc_mw_locale($a)]);
  $rid=(int)database()->insert_id;
  // Exact compatible identity only. Conflicts create a separate reviewable contact, never overwrite an old one.
  $candidates=fcc_partner_rows('SELECT * FROM fcc_partner_contacts WHERE user_id=? AND (LOWER(email)=? OR phone=?) LIMIT 5 FOR UPDATE',[$uid,$email,$phone]);
  $exact=array_values(array_filter($candidates,fn($c)=>mb_strtolower($c['email'])===$email&&fcc_partner_contact_phone($c['phone'],true)===$phone));
  $review=count($candidates)>0&&count($exact)!==1;
  $cid=count($exact)===1?(int)$exact[0]['id']:0;
  if($returnContext){$cid=(int)$returnContext['invite']['contact_id'];$review=false;}
  if(!$cid&&function_exists('fcc_business_handoff_contact')&&!empty($input['_business_handoff'])){$bridged=fcc_business_handoff_contact($uid,(int)$a['session_id'],$input);if($bridged){$cid=$bridged;$review=false;}}
  if(!$cid) {
   fcc_partner_query("INSERT INTO fcc_partner_contacts (user_id,name,email,phone,note,contact_permission,source_kind,source_id,created_at,updated_at) VALUES (?,?,?,?,?,?,'webinar',?,?,?)",[$uid,$name,$email,$phone,($a['is_demo']?'Probni kontakt. ':'').($offline?'Prijava interesa za događaj: '.$a['title'].'. Pozivnica ':'Prijava za webinar preko Pozivnice ').$a['id'].($review?' Provjeri podudaranje s postojećim kontaktom.':''),'requested',$rid,$at,$at]);$cid=(int)database()->insert_id;
  }
  fcc_partner_query("INSERT INTO fcc_partner_contact_sources (user_id,source_kind,source_id,contact_id,created_at) VALUES (?,'webinar',?,?,?)",[$uid,$rid,$cid,$at]);
  fcc_partner_query('UPDATE fcc_webinar_registrations SET contact_id=?,needs_review=? WHERE id=?',[$cid,$review?1:0,$rid]);
  if($returnContext&&!empty($returnContext['source']['email_verified_at'])){fcc_partner_query('UPDATE fcc_webinar_registrations SET email_verified_at=? WHERE id=?',[$returnContext['source']['email_verified_at'],$rid]);fcc_webinar_schedule(fcc_partner_one('SELECT * FROM fcc_webinar_registrations WHERE id=?',[$rid]),fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$a['session_id']]),$now);}
  fcc_webinar_job($rid,'project',0,$at);fcc_webinar_job($rid,'confirmation',0,$at);
  if(!$isSelf)fcc_webinar_job($rid,'owner_confirmation',0,$at);
  return ['new'=>true,'registration_id'=>$rid,'manage_token'=>$manage];
 });
 // Internal projection is cheap and retryable. Email delivery is owned by the worker.
 try {fcc_webinar_project((int)$result['registration_id']);}catch(Throwable $e){error_log('Webinar projection queued for retry.');}
 return $result;
}
function fcc_webinar_project(int $rid): void {
 $r=fcc_partner_one('SELECT * FROM fcc_webinar_registrations WHERE id=?',[$rid]);if(!$r)return;
 fcc_webinar_atomic((int)$r['user_id'],function()use($rid,$r) {
  $r=fcc_partner_one('SELECT * FROM fcc_webinar_registrations WHERE id=? FOR UPDATE',[$rid]);
  if(!$r||$r['status']!=='registered'||!$r['contact_id'])return;
  $uid=(int)$r['user_id'];$cid=(int)$r['contact_id'];$a=fcc_webinar_attempt($uid,(int)$r['attempt_id']);
  $contact=fcc_partner_one('SELECT * FROM fcc_partner_contacts WHERE id=? AND user_id=?',[$cid,$uid]);
  if(!$contact)return; // Deleted contacts are never recreated by retries.
  $session=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$r['session_id']]);$offline=$session&&fcc_event_offline($session);
  $key='webinar:registration:'.$rid;$demo=(bool)$r['is_demo'];$eligible=!$demo&&!$r['is_self']&&!$r['needs_review'];
  fcc_partner_query("INSERT IGNORE INTO fcc_partner_activities (user_id,contact_id,kind,note,request_key,created_at) VALUES (?,?,'webinar_registration',?,?,?)",[$uid,$cid,($demo?'Probna prijava preko Pozivnice ':'Prijava preko Pozivnice ').$a['id'],$key,$r['received_at']]);
  $activity=(int)fcc_partner_one('SELECT id FROM fcc_partner_activities WHERE user_id=? AND request_key=?',[$uid,$key])['id'];
  $eventKind=$eligible?'webinar_registration_received':'webinar_demo_registration';
  fcc_partner_query('INSERT IGNORE INTO fcc_partner_pilot_events (user_id,step_id,activity_id,kind,source,detail,request_key,created_at) VALUES (?,?,?,?,?,?,?,?)',[$uid,$a['journey_step_id']?(int)$a['journey_step_id']:null,$activity,$eventKind,$eligible?'server_verified':'excluded_result','Pozivnica '.$a['id'].': zaprimljena prijava'.($eligible?'':', bez poslovnog rezultata').'.',$key,$r['received_at']]);
  $event=fcc_partner_one('SELECT id FROM fcc_partner_pilot_events WHERE user_id=? AND request_key=?',[$uid,$key]);
  fcc_partner_query('UPDATE fcc_webinar_registrations SET evidence_id=? WHERE id=?',[(int)$event['id'],$rid]);
  fcc_partner_notify($uid,$key,'service',$offline?fcc_seminar_t('Nova prijava interesa'):fcc_t($demo?'Probna prijava na webinar':'Nova prijava na webinar',[],fcc_mw_locale($session,fcc_wl_locale($uid))),$r['name'].' · '.fcc_webinar_label_time($session,fcc_mw_locale($session,fcc_wl_locale($uid))),'partner/contacts?id='.$cid,604800,$r['received_at']);
  if(fcc_webinar_may_contact_registration($r,$contact)) {
   fcc_partner_query('INSERT IGNORE INTO fcc_partner_tasks (user_id,contact_id,title,due_date,request_key,created_at) VALUES (?,?,?,?,?,?)',[$uid,$cid,($demo?'Proba: ':'').'Pregledaj prijavu i dogovor o javljanju',(new DateTimeImmutable($r['received_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d'),'webinar:followup:'.$rid,$r['received_at']]);
  }
  if(!$offline&&($eligible||$demo)&&!$r['is_self']&&!$r['needs_review']) {
   $goalKey=($demo?'webinar:demo-goal:':'webinar:goal:').$r['session_id'];
   fcc_partner_query('UPDATE fcc_partner_tasks SET completed_at=COALESCE(completed_at,?),version=version+1 WHERE user_id=? AND request_key=? AND completed_at IS NULL',[$r['received_at'],$uid,$goalKey]);
  }
  fcc_partner_query("UPDATE fcc_webinar_jobs SET state='done',completed_at=UTC_TIMESTAMP(),updated_at=UTC_TIMESTAMP() WHERE registration_id=? AND kind='project'",[$rid]);
 });
}
function fcc_webinar_goal(int $uid,int $attemptId): int {
 return fcc_webinar_atomic($uid,function()use($uid,$attemptId) {
  fcc_webinar_require_pro($uid);$a=fcc_webinar_attempt($uid,$attemptId);$s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$a['session_id']]);
  $key=($a['is_demo']?'webinar:demo-goal:':'webinar:goal:').$s['id'];
  $done=fcc_partner_one("SELECT received_at FROM fcc_webinar_registrations WHERE user_id=? AND session_id=? AND is_demo=? AND status='registered' AND is_self=0 AND needs_review=0 ORDER BY id LIMIT 1",[$uid,(int)$s['id'],(int)$a['is_demo']]);
  fcc_partner_query('INSERT IGNORE INTO fcc_partner_tasks (user_id,title,due_date,completed_at,request_key,created_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP())',[$uid,($a['is_demo']?'Probni cilj: ':'').'Jedna prijava na webinar',(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($s['timezone']))->format('Y-m-d'),$done['received_at']??null,$key]);
  return (int)fcc_partner_one('SELECT id FROM fcc_partner_tasks WHERE user_id=? AND request_key=?',[$uid,$key])['id'];
 });
}
function fcc_webinar_registration_for_token(string $token,bool $verify=false): ?array {
 if(!preg_match('/^[a-f0-9]{64}$/D',$token))return null;
 return fcc_partner_one('SELECT r.*,s.title,s.starts_at_utc,s.ends_at_utc,s.timezone,s.status session_status,s.revision,s.access_url,s.event_json,s.occurrence_key FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id WHERE r.'.($verify?'verification_hash':'management_hash').'=?',[hash('sha256',$token)]);
}
function fcc_webinar_manage(string $token,string $action,?DateTimeImmutable $now=null): void {
 $now=fcc_webinar_now($now);$r=fcc_webinar_registration_for_token($token,$action==='verify');
 if(!$r)throw new InvalidArgumentException(fcc_t('Poveznica nije dostupna.'));
 fcc_webinar_atomic((int)$r['user_id'],function()use($r,$token,$action,$now) {
  $r=fcc_webinar_registration_for_token($token,$action==='verify');
  if(!$r)throw new InvalidArgumentException(fcc_t('Poveznica više nije dostupna.'));
  if(!in_array($action,['verify','unsubscribe','cancel','erase'],true))throw new InvalidArgumentException(fcc_t('Radnja nije dostupna.'));
  if($action==='erase') { fcc_webinar_erase_record($r); return; }
  if($action==='verify') {
   if($r['status']!=='registered')throw new InvalidArgumentException(fcc_t('Prijava je otkazana.'));
   fcc_partner_query('UPDATE fcc_webinar_registrations SET email_verified_at=COALESCE(email_verified_at,?) WHERE id=?',[$now->format('Y-m-d H:i:s'),(int)$r['id']]);
   $fresh=fcc_partner_one('SELECT * FROM fcc_webinar_registrations WHERE id=?',[(int)$r['id']]);$s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$r['session_id']]);fcc_webinar_schedule($fresh,$s,$now);
  } else {
   fcc_partner_query("UPDATE fcc_webinar_registrations SET reminder_opt_in=0".($action==='cancel'?",status='cancelled',cancelled_at=UTC_TIMESTAMP()":'').' WHERE id=?',[(int)$r['id']]);
   $kinds=$action==='cancel'?"kind<>'project'":"kind IN ('reminder24','reminder1','preview_reminder24','preview_reminder1')";
   fcc_partner_query("UPDATE fcc_webinar_jobs SET state='cancelled',updated_at=UTC_TIMESTAMP() WHERE registration_id=? AND state IN ('pending','processing') AND ".$kinds,[(int)$r['id']]);
  }
 });
}
function fcc_webinar_calendar(array $r): string {
 $escape=static fn($s)=>str_replace(["\\","\r","\n",";",","],["\\\\",'',"\\n","\\;","\\,"],(string)$s);
 $dt=static fn($s)=>(new DateTimeImmutable($s,new DateTimeZone('UTC')))->format('Ymd\THis\Z');
 $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$r['session_id']]);
 if(fcc_event_offline($s))return fcc_seminar_calendar($s,fcc_webinar_manage_url($r),$r['status']!=='registered');
 $lines=['BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//FCC//Webinar//HR','CALSCALE:GREGORIAN','METHOD:PUBLISH','BEGIN:VEVENT','UID:'.$s['calendar_uid'],'DTSTAMP:'.gmdate('Ymd\THis\Z'),'DTSTART:'.$dt($s['starts_at_utc']),'DTEND:'.$dt($s['ends_at_utc']),'SEQUENCE:'.$s['revision'],'SUMMARY:'.$escape($s['title']),'DESCRIPTION:'.$escape(fcc_t('Pristup webinaru: ',[],$r['language']??'hr').$s['access_url']."\n".fcc_t('Aktualni detalji: ',[],$r['language']??'hr').fcc_webinar_manage_url($r)),'LOCATION:'.$escape($s['access_url']),'URL:'.$s['access_url'],'STATUS:'.(in_array($s['status'],['cancelled','postponed'],true)||$r['status']!=='registered'?'CANCELLED':'CONFIRMED'),'END:VEVENT','END:VCALENDAR'];
 $folded=[];foreach($lines as $line){$first=true;while(strlen($line)>($first?75:74)){$limit=$first?75:74;$chunk=mb_strcut($line,0,$limit,'UTF-8');$folded[]=($first?'':' ').$chunk;$line=substr($line,strlen($chunk));$first=false;}$folded[]=($first?'':' ').$line;}
 return implode("\r\n",$folded)."\r\n";
}
function fcc_webinar_google_calendar(array $r): string {
 $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)($r['session_id']??0)]);if($s&&fcc_event_offline($s))return fcc_seminar_google($s,fcc_webinar_manage_url($r));
 $dt=fn($s)=>(new DateTimeImmutable($s,new DateTimeZone('UTC')))->format('Ymd\THis\Z');
 return 'https://calendar.google.com/calendar/render?'.http_build_query(['action'=>'TEMPLATE','text'=>$r['title'],'dates'=>$dt($r['starts_at_utc']).'/'.$dt($r['ends_at_utc']),'ctz'=>$r['timezone'],'details'=>fcc_t('Pristup webinaru: ',[],$r['language']??fcc_locale()).$r['access_url']."\n".fcc_t('Aktualni detalji: ',[],$r['language']??fcc_locale()).fcc_webinar_manage_url($r),'location'=>$r['access_url']],'','&',PHP_QUERY_RFC3986);
}
function fcc_webinar_label_time(array $s, ?string $language=null): string {
 if(!empty($s['event_json'])&&fcc_event_offline($s))return fcc_seminar_date($s,$language);
 if(fcc_new_locale($language)){ $dt=(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($s['timezone']));return fcc_t('{date} u {time}',['{date}'=>$dt->format('d. m. Y.'),'{time}'=>$dt->format('H:i')],$language).' · '.$s['timezone']; }
 $format=match(fcc_locale($language)){'sl'=>'d. m. Y \o\b H:i','en'=>'d M Y H:i','de'=>'d.m.Y \u\m H:i',default=>'d. m. Y. \u H:i'};
 return (new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($s['timezone']))->format($format).' · '.$s['timezone'];
}
function fcc_webinar_contact_message(array $s,string $name,?DateTimeImmutable $now=null): string {
 if(($s['session_status']??$s['status']??'')!=='scheduled'||$s['ends_at_utc']<=fcc_webinar_now($now)->format('Y-m-d H:i:s'))return '';
 if(!empty($s['event_json'])&&fcc_event_offline($s))return fcc_seminar_message($s,$name);
 // Shared FCC events always use the canonical room; personal events retain their own access code.
 try {$access=!empty($s['owner_user_id'])?fcc_webinar_access_url($s['access_url']):forever_business_vip_webinar_url();}
 catch(InvalidArgumentException $e){return '';}
 $first=preg_split('/\s+/u',trim($name),2)[0]??'';
 if(fcc_new_locale())return fcc_t('Bok {name}! Hvala na prijavi za webinar „{event}“.',['{name}'=>$first,'{event}'=>fcc_webinar_title($s)])."\n\n".fcc_t('Termin: ').fcc_webinar_label_time($s)."\n\n".fcc_t('Poveznica za uključivanje: ')."\n".$access."\n\n".fcc_t('Sačuvaj ovu poruku da ti poveznica bude pri ruci kad webinar počne. Trebaš li pomoć oko spajanja?');
 if(fcc_locale()==='sr')return ($first!==''?'Zdravo '.$first.'!':'Zdravo!').' Hvala na prijavi za webinar „'.fcc_webinar_title($s).'“. 😊'."\n\nTermin: ".fcc_webinar_label_time($s,'sr').".\n\nOvde možeš da se pridružiš:\n".$access."\n\nSačuvaj ovu poruku da bi ti link bio pri ruci kada počnemo. Da li ti treba pomoć da se uključiš?";
 if(fcc_locale()==='de')return ($first!==''?'Hallo '.$first.'!':'Hallo!').' Danke für deine Anmeldung zum Webinar „'.$s['title'].'“. 😊'."\n\nTermin: ".fcc_webinar_label_time($s,'de').".\n\nHier kannst du teilnehmen:\n".$access."\n\nSpeichere diese Nachricht, damit du den Link zum Start griffbereit hast. Brauchst du Hilfe beim Beitreten?";
 if(fcc_locale()==='en')return ($first!==''?'Hi '.$first.'!':'Hi!').' Thanks for registering for the webinar “'.$s['title'].'”. 😊'."\n\nWhen: ".fcc_webinar_label_time($s,'en').".\n\nJoin here:\n".$access."\n\nSave this message so you have the link ready when the webinar starts. Do you need any help joining?";
 if(fcc_locale()==='sl')return ($first!==''?'Živjo '.$first.'!':'Živjo!').' Hvala za prijavo na webinar »'.$s['title'].'«. 😊'
  ."\n\nTermin: ".fcc_webinar_label_time($s,'sl').".\n\nPovezava za pridružitev:\n".$access
  ."\n\nShrani to sporočilo, da bo povezava pri roki, ko se webinar začne. Potrebuješ pomoč pri povezovanju?";
 $greeting=$first!==''?'Bok '.$first.'!':'Bok!';
 $time=str_replace(' · Europe/Zagreb',' po zagrebačkom vremenu',fcc_webinar_label_time($s));
 return $greeting.' Hvala ti na prijavi za webinar „'.$s['title'].'”. 😊'
  ."\n\nTermin: ".$time.".\n\nPoveznica za uključivanje:\n".$access
  ."\n\nSačuvaj ovu poruku da ti poveznica bude pri ruci kad webinar počne. Trebaš li pomoć oko spajanja?";
}
function fcc_webinar_coach_context(int $uid): ?array {
 if(!fcc_webinar_enabled($uid,true)&&!fcc_webinar_standard_enabled($uid,true))return null;
 $growth=fcc_webinar_growth_context($uid);
 $counts=fcc_partner_one("SELECT COUNT(*) registrations,SUM(is_demo=1) demo,SUM(is_demo=0 AND is_self=0 AND needs_review=0) eligible FROM fcc_webinar_registrations WHERE user_id=? AND status='registered'",[$uid]);
 $recent=fcc_partner_rows("SELECT a.id invitation_number,a.journey_step_id,s.title,s.starts_at_utc,s.timezone,r.*,c.id contact_row_id,c.user_id contact_user_id,c.contact_permission,c.archived_at,c.source_kind contact_source_kind,c.source_id contact_source_id,c.version contact_version,c.created_at contact_created_at,c.updated_at contact_updated_at FROM fcc_webinar_registrations r JOIN fcc_webinar_invite_attempts a ON a.id=r.attempt_id AND a.user_id=r.user_id JOIN fcc_webinar_sessions s ON s.id=r.session_id JOIN fcc_partner_contacts c ON c.id=r.contact_id AND c.user_id=r.user_id WHERE r.user_id=? AND r.status='registered' ORDER BY r.id DESC LIMIT 5",[$uid]);
 $safe=[];
 foreach($recent as $r)$safe[]=['invitation_number'=>(int)$r['invitation_number'],'original_step_id'=>$r['journey_step_id']?(int)$r['journey_step_id']:null,'event'=>$r['title'],'starts_at_utc'=>$r['starts_at_utc'],'timezone'=>$r['timezone'],'demo'=>(bool)$r['is_demo'],'email_verified'=>(bool)$r['email_verified_at'],'may_contact_on_whatsapp'=>fcc_webinar_may_contact_registration($r),'evidence'=>'registration_received'];
 return ['guest_lifecycle'=>fcc_wl_coach($uid),'growth'=>$growth,'received'=>(int)$counts['registrations'],'eligible'=>(int)$counts['eligible'],'demo'=>(int)$counts['demo'],'recent'=>$safe,'share_url'=>SITE_URL.'partner-webinar','contact_location'=>SITE_URL.'partner/contacts','instruction'=>'Prijava potvrđuje zaprimljen obrazac. Ne potvrđuje gledanje, slanje ni sudjelovanje. Probne prijave nisu poslovni rezultat.'];
}
function fcc_webinar_run_jobs(?DateTimeImmutable $now=null,int $limit=40,?int $onlyUid=null,?int $onlyRegistration=null,?callable $sender=null): array {
 $now=fcc_webinar_now($now);$at=$now->format('Y-m-d H:i:s');$stats=['processed'=>0,'local_mail'=>0,'sent_mail'=>0,'unknown'=>0,'blocked'=>0,'cancelled'=>0,'errors'=>0];
 if(!fcc_webinar_worker_enabled())return $stats;
 fcc_webinar_sync_cancellations();
 // A crash after committing a send intent cannot be safely retried without provider reconciliation.
 $staleParams=[$at,$at];if($onlyUid)$staleParams[]=$onlyUid;if($onlyRegistration)$staleParams[]=$onlyRegistration;
 fcc_partner_query("UPDATE fcc_webinar_jobs j JOIN fcc_webinar_registrations r ON r.id=j.registration_id SET j.state='delivery_unknown',j.last_error_code='interrupted_delivery',j.lease_until=NULL,j.updated_at=? WHERE j.state='sending' AND j.lease_until<?".($onlyUid?' AND r.user_id=?':'').($onlyRegistration?' AND r.id=?':''),$staleParams);
 $params=[$at,$at,$at];if($onlyUid)$params[]=$onlyUid;if($onlyRegistration)$params[]=$onlyRegistration;
 $jobs=fcc_partner_rows("SELECT j.id,j.registration_id,r.user_id FROM fcc_webinar_jobs j JOIN fcc_webinar_registrations r ON r.id=j.registration_id WHERE (j.state='pending' OR (j.state='processing' AND j.lease_until<?)) AND j.scheduled_at_utc<=? AND (j.next_attempt_at IS NULL OR j.next_attempt_at<=?)".($onlyUid?' AND r.user_id=?':'').($onlyRegistration?' AND r.id=?':'')." ORDER BY j.id LIMIT ".max(1,min(100,$limit)),$params);
 foreach($jobs as $j) {
  try {
   $did=fcc_partner_query("UPDATE fcc_webinar_jobs SET state='processing',lease_until=?,attempts=attempts+1,updated_at=? WHERE id=? AND (state='pending' OR (state='processing' AND lease_until<?))",[$now->modify('+3 minutes')->format('Y-m-d H:i:s'),$at,(int)$j['id'],$at])->affected_rows;
   if(!$did)continue;
   if(fcc_partner_one('SELECT kind FROM fcc_webinar_jobs WHERE id=?',[(int)$j['id']])['kind']==='project') {fcc_webinar_project((int)$j['registration_id']);$stats['processed']++;continue;}
   $delivery=fcc_webinar_atomic((int)$j['user_id'],function()use($j,$now,$at,&$stats) {
    $job=fcc_partner_one('SELECT * FROM fcc_webinar_jobs WHERE id=? FOR UPDATE',[(int)$j['id']]);
    $r=fcc_partner_one('SELECT * FROM fcc_webinar_registrations WHERE id=? FOR UPDATE',[(int)$job['registration_id']]);
    $s=$r?fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$r['session_id']]):null;
    $reminder=str_starts_with($job['kind'],'reminder');$ownerCopy=$job['kind']==='owner_confirmation';
    $owner=$r?fcc_webinar_mail_owner($r):null;
    $cancel=!$r||!$s||$r['status']!=='registered'||(!$ownerCopy&&$r['email_failed_at'])||empty($owner['active'])||!fcc_partner_one('SELECT id FROM fcc_partner_contacts WHERE id=? AND user_id=?',[(int)($r['contact_id']??0),(int)$j['user_id']]);
    if($job['state']!=='processing')return;
    if(!$cancel&&!$ownerCopy&&in_array($s['status'],['cancelled','postponed'],true)&&$job['kind']!==$s['status'])$cancel=true;
    if(!$cancel&&in_array($job['kind'],['confirmation','owner_confirmation'],true)&&$s['ends_at_utc']<=$at)$cancel=true;
    if(!$cancel&&$reminder)$cancel=!$r['email_verified_at']||!$r['reminder_opt_in']||$s['status']!=='scheduled'||(int)$s['revision']!==(int)$job['session_revision']||$s['starts_at_utc']<=$at||($r['last_email_at']&&$r['last_email_at']>$now->modify('-2 hours')->format('Y-m-d H:i:s'));
    if(!$cancel&&in_array($job['kind'],['changed','cancelled','postponed'],true))$cancel=(int)$s['revision']!==(int)$job['session_revision'];
    if(!$cancel&&$ownerCopy)$cancel=$owner['email']===''||mb_strtolower($owner['email'])===mb_strtolower($r['email']);
    if($cancel){fcc_partner_query("UPDATE fcc_webinar_jobs SET state='cancelled',updated_at=? WHERE id=?",[$at,(int)$job['id']]);$stats['cancelled']++;return;}
    $payload=fcc_webinar_mail_payload($r,$s,$job['kind']);
    if(fcc_webinar_local())$state='local_captured';
    else {
     // A production deployment must not send synthetic registrations or insecure capability links.
     if($r['is_demo']||$s['is_demo']||!str_starts_with(SITE_URL,'https://')||str_starts_with($job['kind'],'preview_')) {
      fcc_partner_query("UPDATE fcc_webinar_jobs SET state='blocked',last_error_code='unsafe_mail_environment',lease_until=NULL,updated_at=? WHERE id=?",[$at,(int)$job['id']]);$stats['blocked']++;return;
     }
     fcc_partner_query("UPDATE fcc_webinar_jobs SET state='sending',updated_at=? WHERE id=?",[$at,(int)$job['id']]);
     return ['payload'=>$payload,'owner_copy'=>$ownerCopy,'attempts'=>(int)$job['attempts']];
    }
    fcc_partner_query("UPDATE fcc_webinar_jobs SET state=?,payload_json=?,completed_at=?,updated_at=?,lease_until=NULL,last_error_code=NULL WHERE id=?",[$state,$state==='local_captured'?fcc_webinar_json($payload):null,$at,$at,(int)$job['id']]);
    if(!$ownerCopy)fcc_partner_query('UPDATE fcc_webinar_registrations SET last_email_at=? WHERE id=?',[$at,(int)$r['id']]);
    $stats[$state==='sent'?'sent_mail':'local_mail']++;
   });
   if($delivery) {
    // The registration and send intent are committed before any external request.
    $state=fcc_webinar_send_mail($delivery['payload'],$sender);
    if($state==='pending'&&$delivery['attempts']>=5)$state='failed';
    $retry=$state==='pending'?$now->modify('+'.min(60,2**$delivery['attempts']).' minutes')->format('Y-m-d H:i:s'):null;
    fcc_partner_query("UPDATE fcc_webinar_jobs SET state=?,next_attempt_at=?,last_error_code=?,completed_at=?,lease_until=NULL,updated_at=? WHERE id=? AND state='sending'",[$state,$retry,$state==='sent'?null:'mail_'.$state,$state==='sent'?$at:null,$at,(int)$j['id']]);
    if($state==='sent') {
     if(!$delivery['owner_copy'])fcc_partner_query('UPDATE fcc_webinar_registrations SET last_email_at=? WHERE id=?',[$at,(int)$j['registration_id']]);
     $stats['sent_mail']++;
    }elseif($state==='delivery_unknown')$stats['unknown']++;
    else $stats['errors']++;
   }
   $stats['processed']++;
  } catch(Throwable $e) {
   $job=fcc_partner_one('SELECT attempts,state FROM fcc_webinar_jobs WHERE id=?',[(int)$j['id']]);
   if(in_array($job['state'],['sending','sent','delivery_unknown'],true)){$stats['errors']++;error_log('Webinar delivery requires reconciliation.');continue;}
   fcc_partner_query('UPDATE fcc_webinar_jobs SET state=?,next_attempt_at=?,last_error_code=?,updated_at=? WHERE id=?',[(int)$job['attempts']>=5?'failed':'pending',$now->modify('+'.min(60,2**(int)$job['attempts']).' minutes')->format('Y-m-d H:i:s'),'processing_error',$at,(int)$j['id']]);$stats['errors']++;
   error_log('Webinar job failed: '.get_class($e));
  }
 }
 return $stats;
}
function fcc_webinar_change_session(int $sid,int $revision,string $status,?string $starts=null,?string $ends=null): void {
 if(fcc_partner_one('SELECT id FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL',[$sid]))throw new InvalidArgumentException(fcc_t('Ovaj termin uređuje se u administraciji Webinari i događaji.'));
 if(!in_array($status,['scheduled','cancelled'],true))throw new InvalidArgumentException(fcc_t('Status nije valjan.'));
 $db=database();$db->begin_transaction();
 try {
  $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? FOR UPDATE',[$sid]);
  if(!$s||(int)$s['revision']!==$revision)throw new InvalidArgumentException(fcc_t('Termin je izmijenjen. Osvježi prikaz.'));
  $starts=$starts??$s['starts_at_utc'];$ends=$ends??$s['ends_at_utc'];
  $parse=static function($v){$d=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$v,new DateTimeZone('UTC'));if(!$d||$d->format('Y-m-d H:i:s')!==$v)throw new InvalidArgumentException(fcc_t('Termin nije valjan.'));return $d;};
  if($parse($ends)<=$parse($starts))throw new InvalidArgumentException(fcc_t('Završetak mora biti nakon početka.'));
  fcc_partner_query('UPDATE fcc_webinar_sessions SET starts_at_utc=?,ends_at_utc=?,status=?,revision=revision+1,updated_at=UTC_TIMESTAMP() WHERE id=?',[$starts,$ends,$status,$sid]);
  fcc_partner_query("UPDATE fcc_webinar_jobs j JOIN fcc_webinar_registrations r ON r.id=j.registration_id SET j.state='cancelled',j.updated_at=UTC_TIMESTAMP() WHERE r.session_id=? AND j.kind IN ('reminder24','reminder1','changed','cancelled') AND j.state IN ('pending','processing')",[$sid]);
  if($s['series_key']==='forever-business'&&preg_match('/^\\d{4}-\\d{2}-\\d{2}$/D',$s['occurrence_key']))fcc_partner_notification_config_save('journey90_webinar_'.$s['occurrence_key'],$status);
  $next=array_merge($s,['starts_at_utc'=>$starts,'ends_at_utc'=>$ends,'status'=>$status,'revision'=>$revision+1]);
  foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_registrations WHERE session_id=? AND status='registered'",[$sid]) as $r) {
   fcc_webinar_job((int)$r['id'],$status==='cancelled'?'cancelled':'changed',$revision+1,gmdate('Y-m-d H:i:s'));
   fcc_webinar_schedule($r,$next,new DateTimeImmutable('now',new DateTimeZone('UTC')));
  }
  $db->commit();
 }catch(Throwable $e){$db->rollback();throw $e;}
}
function fcc_webinar_sync_cancellations(): void {
 if(function_exists('fcc_events_enabled')&&fcc_events_enabled())return;
 foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE status='scheduled' AND ends_at_utc>UTC_TIMESTAMP()-INTERVAL 1 DAY") as $s) {
  if(empty($s['owner_user_id'])&&fcc_partner_notification_config('journey90_webinar_'.$s['occurrence_key'],null)==='cancelled') {
   try{fcc_webinar_change_session((int)$s['id'],(int)$s['revision'],'cancelled');}catch(InvalidArgumentException $e){/* Another worker already handled this revision. */}
  }
 }
}
function fcc_webinar_rate_limit(string $scope,int $limit,int $seconds): void {
 $dir=sys_get_temp_dir().'/fcc-webinar-rate';if(!is_dir($dir))@mkdir($dir,0700);
 $path=$dir.'/'.fcc_webinar_hash($scope);$fp=fopen($path,'c+');
 if(!$fp||!flock($fp,LOCK_EX))throw new RuntimeException(fcc_t('Rate limiter unavailable.'));
 try {
  $data=json_decode(stream_get_contents($fp),true)?:['at'=>time(),'n'=>0];
  if($data['at']<time()-$seconds)$data=['at'=>time(),'n'=>0];
  $data['n']++;rewind($fp);ftruncate($fp,0);fwrite($fp,json_encode($data));fflush($fp);
  if($data['n']>$limit)throw new InvalidArgumentException(fcc_t('Pričekaj malo prije ponovnog pokušaja.'));
 }finally{flock($fp,LOCK_UN);fclose($fp);}
}
/** Called within the owner's transaction. Only this module's data is erased. */
function fcc_webinar_erase_record(array $r): void {
 fcc_wl_cleanup_registration((int)$r['id']);
 $rid=(int)$r['id'];$uid=(int)$r['user_id'];$cid=(int)$r['contact_id'];
 fcc_partner_query("UPDATE fcc_webinar_jobs SET state='cancelled',payload_json=NULL,last_error_code=NULL,updated_at=UTC_TIMESTAMP() WHERE registration_id=?",[$rid]);
 fcc_partner_query("UPDATE fcc_partner_tasks SET completed_at=COALESCE(completed_at,UTC_TIMESTAMP()),version=version+1 WHERE user_id=? AND request_key=?",[$uid,'webinar:followup:'.$rid]);
 $otherSources=(int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_contact_sources WHERE user_id=? AND contact_id=? AND NOT (source_kind='webinar' AND source_id=?)",[$uid,$cid,$rid])['n'];
 $otherActivity=(int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_activities WHERE user_id=? AND contact_id=? AND request_key<>?",[$uid,$cid,'webinar:registration:'.$rid])['n'];
 if(!$otherSources&&!$otherActivity) {
  fcc_partner_query("DELETE FROM fcc_partner_contacts WHERE id=? AND user_id=? AND source_kind='webinar' AND source_id=?",[$cid,$uid,$rid]);
 }
 fcc_partner_query("DELETE FROM fcc_partner_contact_sources WHERE user_id=? AND source_kind='webinar' AND source_id=?",[$uid,$rid]);
 fcc_partner_query("DELETE FROM fcc_partner_activities WHERE user_id=? AND request_key=?",[$uid,'webinar:registration:'.$rid]);
 fcc_partner_query("UPDATE fcc_partner_notifications SET body='',read_at=COALESCE(read_at,UTC_TIMESTAMP()),path='partner/contacts' WHERE user_id=? AND event_key=?",[$uid,'webinar:registration:'.$rid]);
 fcc_partner_query("UPDATE fcc_webinar_registrations SET status='erased',name='',email='',phone='',contact_id=NULL,phone_key=?,email_key=?,management_hash=?,management_sealed='',verification_hash=?,verification_sealed='',reminder_opt_in=0,whatsapp_opt_in=0,cancelled_at=UTC_TIMESTAMP() WHERE id=?",[fcc_webinar_token(),fcc_webinar_token(),fcc_webinar_token(),fcc_webinar_token(),$rid]);
}
function fcc_webinar_export(array $r): array {
 return ['webinar_outcome'=>fcc_wl_ready()?fcc_partner_one('SELECT attendance,attendance_at,contacted_at FROM fcc_webinar_guest_states WHERE registration_id=?',[(int)$r['id']]):null,'name'=>$r['name'],'email'=>$r['email'],'phone'=>$r['phone'],'event'=>$r['title'],'starts_at_utc'=>$r['starts_at_utc'],'timezone'=>$r['timezone'],'status'=>$r['status'],'reminder_opt_in'=>(bool)$r['reminder_opt_in'],'whatsapp_opt_in'=>(bool)$r['whatsapp_opt_in'],'consent_version'=>$r['consent_version'],'received_at'=>$r['received_at']];
}

/** Called only inside the owner's deletion transaction. Shared sessions belong to the team. */
function fcc_webinar_delete_user_data(int $uid): void {
 if(fcc_wl_ready()){foreach(['fcc_webinar_guest_states','fcc_webinar_return_invites','fcc_webinar_followups'] as $table)fcc_partner_query('DELETE FROM '.$table.' WHERE user_id=?',[$uid]);}
 if(function_exists('fcc_webinar_outreach_ready')&&fcc_webinar_outreach_ready())fcc_partner_query('DELETE FROM fcc_webinar_outreach WHERE user_id=?',[$uid]);
 if(fcc_event_rsvp_ready())fcc_partner_query('DELETE FROM fcc_event_member_rsvps WHERE user_id=?',[$uid]);
 if(function_exists('fcc_events_ready')&&fcc_events_ready())foreach(['fcc_webinar_event_jobs','fcc_webinar_member_access'] as $table)fcc_partner_query('DELETE FROM '.$table.' WHERE user_id=?',[$uid]);
 foreach(fcc_partner_rows("SELECT * FROM fcc_webinar_registrations WHERE user_id=? AND status<>'erased' FOR UPDATE",[$uid]) as $r)fcc_webinar_erase_record($r);
 fcc_partner_query('DELETE j FROM fcc_webinar_jobs j JOIN fcc_webinar_registrations r ON r.id=j.registration_id WHERE r.user_id=?',[$uid]);
 foreach(['fcc_webinar_registrations','fcc_webinar_invite_attempts','fcc_webinar_invitations'] as $table)fcc_partner_query('DELETE FROM '.$table.' WHERE user_id=?',[$uid]);
 if(fcc_webinar_personal_ready())fcc_partner_query('DELETE FROM fcc_webinar_sessions WHERE owner_user_id=?',[$uid]);
}

/** Bounded cleanup, serialized with registration and mail operations for each owner. */
function fcc_webinar_apply_retention(?DateTimeImmutable $now=null, int $limit=100, ?int $onlyUid=null): int {
 if(!fcc_webinar_schema())return 0;
 $days=(int)fcc_legal_config()['webinar_retention_days'];
 if($days<1)throw new RuntimeException(fcc_t('Webinar retention must be a positive number of days.'));
 $cutoff=fcc_webinar_now($now)->modify('-'.$days.' days')->format('Y-m-d H:i:s');
 $rows=fcc_partner_rows("SELECT r.id,r.user_id FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id WHERE r.status<>'erased' AND s.ends_at_utc<=?".($onlyUid?' AND r.user_id=?':'')." ORDER BY s.ends_at_utc,r.id LIMIT ".max(1,min(500,$limit)), $onlyUid?[$cutoff,$onlyUid]:[$cutoff]);
 $count=0;
 foreach($rows as $row) {
  $count+=fcc_webinar_atomic((int)$row['user_id'],function()use($row,$cutoff) {
   $r=fcc_partner_one('SELECT r.*,s.ends_at_utc FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id WHERE r.id=? FOR UPDATE',[(int)$row['id']]);
   if(!$r||$r['status']==='erased'||$r['ends_at_utc']>$cutoff)return 0;
   fcc_webinar_erase_record($r);
   return 1;
  });
 }
 return $count;
}
