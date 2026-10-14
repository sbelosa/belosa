<?php
defined('ALTUMCODE') || die();

function fcc_business_local(): bool {return getenv('FCC_LOCAL')==='1'&&DATABASE_NAME==='fcc_partner_local'&&DATABASE_SERVER==='db';}
function fcc_business_ready(): bool {
 static $ready;return $ready??=!!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_business_jobs'");
}
function fcc_business_enabled(int $uid=0): bool {
 if(getenv('FCC_BUSINESS_ENABLED')==='0'||!fcc_business_ready())return false;
 if(fcc_rollout_managed())return (fcc_business_local()||getenv('FCC_BUSINESS_ENABLED')==='1')&&(!$uid||fcc_partner_enabled($uid));
 if(fcc_business_local())return !$uid||fcc_partner_enabled($uid);
 if(getenv('FCC_BUSINESS_ENABLED')!=='1')return false;
 return !$uid||(fcc_partner_enabled($uid)&&(getenv('FCC_BUSINESS_ROLLOUT')==='all'||in_array($uid,array_map('intval',explode(',',(string)getenv('FCC_BUSINESS_PILOT_IDS'))),true)));
}
function fcc_business_pro(int $uid): bool {
 if(!fcc_business_enabled($uid))return false;
 $u=fcc_partner_one('SELECT status,plan_settings,plan_expiration_date,extra FROM users WHERE user_id=?',[$uid]);if(!$u||(int)$u['status']!==1)return false;
 $p=json_decode($u['plan_settings']??'{}');$e=json_decode($u['extra']??'{}');
 return !empty($p->ai_growth_plan_is_enabled)&&empty($e->billing_access_revoked_at)&&(fcc_ai_is_plan_expiration_active((string)$u['plan_expiration_date'])||fcc_ai_is_billing_grace_active($e));
}
function fcc_business_require_pro(int $uid): void {if(!fcc_business_pro($uid))throw new InvalidArgumentException(fcc_t('Za pripremu prezentacije potreban je aktivni PRO paket.'));}
function fcc_business_json(array $value): string {return json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
function fcc_business_key(): string {
 $key=fcc_business_local()?@file_get_contents(dirname(APP_PATH).'/local/business.key'):base64_decode((string)getenv('FCC_BUSINESS_SECRET'),true);
 if(!is_string($key)||strlen($key)!==32)throw new RuntimeException(fcc_t('Business key is not configured.'));return $key;
}
function fcc_business_seal(string $value): string {$iv=random_bytes(12);$tag='';$s=openssl_encrypt($value,'aes-256-gcm',fcc_business_key(),OPENSSL_RAW_DATA,$iv,$tag,'fcc-business-v1');if($s===false)throw new RuntimeException(fcc_t('Cannot protect request.'));return base64_encode($iv.$tag.$s);}
function fcc_business_unseal(string $value): string {$raw=base64_decode($value,true);if($raw===false||strlen($raw)<29)throw new RuntimeException(fcc_t('Invalid request.'));$v=openssl_decrypt(substr($raw,28),'aes-256-gcm',fcc_business_key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16),'fcc-business-v1');if($v===false)throw new RuntimeException(fcc_t('Invalid request.'));return $v;}
function fcc_business_atomic(int $uid,callable $fn) {
 database()->begin_transaction();try{if(!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$uid]))throw new InvalidArgumentException(fcc_t('Račun nije dostupan.'));$result=$fn();database()->commit();return $result;}catch(Throwable $e){database()->rollback();throw $e;}
}
function fcc_business_language($input): string {return fcc_partner_choice($input,fcc_business_languages());}
function fcc_business_assets(bool $admin=false): array {return fcc_partner_rows('SELECT * FROM fcc_business_assets'.($admin?'':" WHERE status='published' AND approved=1".(fcc_business_local()?'':' AND is_demo=0')).' ORDER BY recommended DESC,sort_order,id');}
function fcc_business_owner(int $uid,int $linkId): ?array {
 if(!fcc_business_enabled()||!(fcc_rollout_managed()?fcc_rollout_public_owner($uid):fcc_business_enabled($uid)))return null;
 $u=fcc_partner_one("SELECT u.user_id,u.name,u.email,u.preferences,l.url FROM users u JOIN links l ON l.user_id=u.user_id WHERE u.user_id=? AND l.link_id=? AND l.type='biolink' AND l.is_enabled=1 AND u.status=1",[$uid,$linkId]);
 if(!$u)return null;$obj=(object)$u;$obj->preferences=json_decode($u['preferences']?:'{}');
 $cards=fcc_partner_cards($uid);$profile='';foreach($cards as $c)if((int)$c['link_id']===$linkId)$profile=$c['public_url'];
 return ['uid'=>$uid,'name'=>$u['name'],'email'=>filter_var($u['email'],FILTER_VALIDATE_EMAIL)?$u['email']:'','phone'=>fc_get_user_default_whatsapp_phone($obj),'fbo'=>trim((string)($obj->preferences->meta->foreverId??'')),'profile'=>$profile];
}
function fcc_business_owned(int $uid,int $id): array {$p=fcc_partner_one('SELECT * FROM fcc_business_presentations WHERE user_id=? AND id=?',[$uid,$id]);if(!$p)throw new InvalidArgumentException(fcc_t('Prezentacija nije pronađena.'));return $p;}
function fcc_business_save(int $uid,int $id,array $in): int {
 fcc_business_require_pro($uid);$language=fcc_business_language($in['language']??'hr');$intro=fcc_partner_text($in['intro']??'',240);$assetId=(int)($in['asset_id']??0);
 if($assetId&&!array_filter(fcc_business_assets(),fn($a)=>(int)$a['id']===$assetId))throw new InvalidArgumentException(fcc_t('Odaberi dostupnu prezentaciju.'));
 return fcc_business_atomic($uid,function()use($uid,$id,$in,$language,$intro,$assetId){
  fcc_business_require_pro($uid);
  if($id){$p=fcc_business_owned($uid,$id);if((int)$p['version']!==(int)($in['version']??0))throw new InvalidArgumentException(fcc_t('Prezentacija je izmijenjena. Osvježi prikaz.'));$card=(int)$p['link_id'];}
  else {$cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));$card=(int)($cards[0]['link_id']??0);}
  if(!fcc_business_owner($uid,$card))throw new InvalidArgumentException(fcc_t('Najprije aktiviraj svoju FCC karticu.'));
  if($id)fcc_partner_query('UPDATE fcc_business_presentations SET asset_id=?,intro=?,language=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE id=? AND user_id=?',[$assetId?:null,$intro,$language,$id,$uid]);
  else {fcc_partner_query('INSERT INTO fcc_business_presentations (user_id,link_id,asset_id,intro,language,created_at,updated_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$uid,$card,$assetId?:null,$intro,$language]);$id=(int)database()->insert_id;}
  fcc_partner_query('INSERT INTO fcc_business_preferences (user_id,language,presentation_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE language=VALUES(language),presentation_id=VALUES(presentation_id),version=version+1',[$uid,$language,$id]);
  return $id;
 });
}
function fcc_business_state(int $uid,int $id,int $version,string $state): void {
 if(!in_array($state,['active','inactive'],true))throw new InvalidArgumentException(fcc_t('Nevaljana radnja.'));
 if($state==='active')fcc_business_require_pro($uid);
 fcc_business_atomic($uid,function()use($uid,$id,$version,$state){$p=fcc_business_owned($uid,$id);if((int)$p['version']!==$version)throw new InvalidArgumentException(fcc_t('Osvježi prikaz prije promjene.'));fcc_partner_query('UPDATE fcc_business_presentations SET status=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE id=? AND user_id=?',[$state,$id,$uid]);});
}
function fcc_business_prepare(int $uid,int $id,array $in): array {
 fcc_business_require_pro($uid);$key=fcc_partner_text($in['request_key']??'',64);if(!preg_match('/^[a-f0-9]{32,64}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi obrazac.'));
 return fcc_business_atomic($uid,fn()=>fcc_business_prepare_locked($uid,fcc_business_owned($uid,$id),$in,$key));
}
function fcc_business_prepare_locked(int $uid,array $p,array $in,string $key): array {
 fcc_business_require_pro($uid);$owner=fcc_business_owner($uid,(int)$p['link_id']);
 if($p['status']!=='active'||!$owner)throw new InvalidArgumentException(fcc_t('Prezentacija trenutačno nije dostupna.'));
 $old=fcc_partner_one('SELECT * FROM fcc_business_shares WHERE user_id=? AND request_key=?',[$uid,$key]);if($old){if((int)$old['presentation_id']!==(int)$p['id'])throw new InvalidArgumentException(fcc_t('Osvježi obrazac.'));return $old;}
 $stepId=(int)($in['step_id']??0);$step=$stepId?fcc_partner_one('SELECT id,cycle_id FROM fcc_partner_journey_steps WHERE id=? AND user_id=?',[$stepId,$uid]):null;
 if($stepId&&!$step)throw new InvalidArgumentException(fcc_t('Korak nije pronađen.'));
 fcc_partner_query('INSERT INTO fcc_business_shares (presentation_id,user_id,public_token,request_key,presentation_version,journey_step_id,journey_cycle_id,is_demo,fbo_snapshot,created_at) VALUES (?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())',[(int)$p['id'],$uid,bin2hex(random_bytes(32)),$key,(int)$p['version'],$step?(int)$step['id']:null,$step?(int)$step['cycle_id']:null,fcc_business_local()?1:0,$owner['fbo']]);
 return fcc_partner_one('SELECT * FROM fcc_business_shares WHERE id=?',[(int)database()->insert_id]);
}
function fcc_business_share(int $uid,int $id): array {$s=fcc_partner_one('SELECT * FROM fcc_business_shares WHERE id=? AND user_id=?',[$id,$uid]);if(!$s)throw new InvalidArgumentException(fcc_t('Poveznica nije pronađena.'));return $s;}
function fcc_business_public(string $token): ?array {
 if(!fcc_business_enabled()||!preg_match('/^[a-f0-9]{64}$/D',$token))return null;
 $s=fcc_partner_one('SELECT s.*,p.link_id,p.asset_id,p.intro,p.language,p.status presentation_status,p.version current_version FROM fcc_business_shares s JOIN fcc_business_presentations p ON p.id=s.presentation_id AND p.user_id=s.user_id WHERE s.public_token=?',[$token]);
 if(!$s)return null;$s['owner']=fcc_business_owner((int)$s['user_id'],(int)$s['link_id']);
 $s['available']=$s['owner']&&$s['presentation_status']==='active'&&!$s['revoked_at'];
 $s['asset']=$s['asset_id']?fcc_partner_one("SELECT * FROM fcc_business_assets WHERE id=? AND status='published' AND approved=1",[(int)$s['asset_id']]):null;
 if($s['asset']&&!fcc_business_local()&&!empty($s['asset']['is_demo']))$s['asset']=null;
 return $s;
}
function fcc_business_share_destination(array $share, ?string $locale = null): array {
 $base=SITE_URL;$expires=0;
 // Reuse the existing private preview and its expiration, without extending access.
 if(fcc_business_local()&&function_exists('fcc_webinar_standard_share_destination')){$preview=fcc_webinar_standard_share_destination(SITE_URL.'webinar-event/1');$base=preg_replace('~webinar-event/1(?:\?.*)?$~','',$preview['url']);$expires=(int)$preview['expires'];}
 // A locale travels with the invitation; it never changes the referral or market.
 $language=fcc_locale($locale);$prefix=in_array($language,['hr','sl','en','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'],true)?$language.'/':'';
 return ['url'=>rtrim($base,'/').'/'.$prefix.'business/'.$share['public_token'],'expires'=>$expires];
}
function fcc_business_url(array $share): string {return fcc_business_share_destination($share)['url'];}
function fcc_business_record(int $uid,int $sid,string $kind,bool $confirmed=false): void {
 $columns=['whatsapp'=>'whatsapp_opened_at','copy'=>'copied_at','sent'=>'sent_at'];if(!isset($columns[$kind]))throw new InvalidArgumentException(fcc_t('Nevaljana radnja.'));
 fcc_business_atomic($uid,function()use($uid,$sid,$kind,$confirmed,$columns){
  $s=fcc_business_share($uid,$sid);$p=fcc_business_public($s['public_token']);if(!$p||!$p['available'])throw new InvalidArgumentException(fcc_t('Prezentacija nije dostupna.'));
  if($kind==='sent'&&!$confirmed)throw new InvalidArgumentException(fcc_t('Potvrdi osobno slanje poruke.'));
  fcc_partner_query('UPDATE fcc_business_shares SET '.$columns[$kind].'=COALESCE('.$columns[$kind].',UTC_TIMESTAMP()) WHERE id=? AND user_id=?',[$sid,$uid]);
  if($kind==='sent')fcc_partner_query('INSERT IGNORE INTO fcc_partner_pilot_events (user_id,step_id,kind,source,detail,request_key,created_at) VALUES (?,?,?,?,?,?,UTC_TIMESTAMP())',[$uid,$s['journey_step_id']?(int)$s['journey_step_id']:null,$s['is_demo']?'business_demo_sent':'business_sent',$s['is_demo']?'excluded_result':'self_reported','Osobna potvrda dijeljenja poslovne prezentacije.','business:sent:'.$sid]);
 });
}
function fcc_business_rate(string $scope,int $limit=40,int $seconds=3600): void {
 $hash=hash_hmac('sha256',$scope,fcc_business_key());$window=(int)(floor(time()/$seconds)*$seconds);
 fcc_partner_query('INSERT INTO fcc_business_rate_limits (scope_hash,window_start,hits) VALUES (?,?,1) ON DUPLICATE KEY UPDATE hits=IF(window_start=VALUES(window_start),hits+1,1),window_start=VALUES(window_start)',[$hash,$window]);
 if((int)fcc_partner_one('SELECT hits FROM fcc_business_rate_limits WHERE scope_hash=?',[$hash])['hits']>$limit)throw new InvalidArgumentException(fcc_t('Previše pokušaja. Pokušaj ponovno kasnije.'));
}
/* Custom code: FC-2026-10-14: server-only source protects private login email in guest receipts. */
function fcc_business_interest(string $token,array $in,bool $trustedPreview=false,string $trustedSource=''): array {
 if(!in_array($trustedSource,['','sponsoring'],true))throw new InvalidArgumentException('Invalid source');
 $s=fcc_business_public($token);if(!$s||!$s['available'])throw new InvalidArgumentException(fcc_t('Prezentacija više ne prima upite.'));
 if(!empty($in['website']))throw new InvalidArgumentException(fcc_t('Upit nije prihvaćen.'));
 $name=fcc_partner_text($in['name']??'',120);$channel=fcc_partner_choice($in['channel']??'',['whatsapp'=>1,'email'=>1]);
 $email=mb_strtolower(fcc_partner_text($in['email']??'',320));$rawPhone=fcc_partner_text($in['phone']??'',40);$phone=$rawPhone?fcc_partner_contact_phone($rawPhone,true):'';
 if(mb_strlen($name)<2||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||($rawPhone!==''&&!preg_match('/^\+[1-9][0-9]{7,14}$/D',$phone))||($channel==='email'&&$email==='')||($channel==='whatsapp'&&$phone===''))throw new InvalidArgumentException(fcc_t('Provjeri ime i kontakt za odabrani način javljanja.'));
 if(empty($in['contact_requested']))throw new InvalidArgumentException(fcc_t('Potvrdi da želiš osobni odgovor suradnika.'));
 $country=fcc_partner_text($in['country']??'',2);if($country!==''&&!isset(fcc_business_countries()[$country]))throw new InvalidArgumentException(fcc_t('Odaberi zemlju s popisa.'));
 $question=fcc_partner_text($in['question']??'',1000);$topic=fcc_partner_choice($in['topic']??'general',fcc_business_topics());$questionHash=hash('sha256',$question.'|'.$topic.'|'.$country);$request=fcc_partner_text($in['request_key']??'',64);if(!preg_match('/^[a-f0-9]{32,64}$/D',$request))throw new InvalidArgumentException(fcc_t('Osvježi obrazac.'));
 $request=hash('sha256',$token.':'.$request);$identity=hash_hmac('sha256',mb_strtolower($name).'|'.$email.'|'.$phone.'|'.$channel,fcc_business_key());$uid=(int)$s['user_id'];
 return fcc_business_atomic($uid,function()use($uid,$s,$in,$token,$name,$email,$phone,$channel,$country,$question,$topic,$questionHash,$request,$identity,$trustedPreview,$trustedSource){
  $fresh=fcc_business_public($token);if(!$fresh||!$fresh['available'])throw new InvalidArgumentException(fcc_t('Prezentacija više ne prima upite.'));
  $old=fcc_partner_one("SELECT id FROM fcc_business_leads WHERE user_id=? AND (request_key=? OR (share_id=? AND identity_key=? AND status='received' AND (question_hash=? OR (question_hash='' AND question=?)) AND received_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 24 HOUR))) LIMIT 1",[$uid,$request,(int)$s['id'],$identity,$questionHash,$question]);if($old)return ['new'=>false,'lead_id'=>(int)$old['id']];
  if((int)($in['content_version']??0)!==(int)$fresh['current_version'])throw new InvalidArgumentException(fcc_t('Prezentacija je izmijenjena. Osvježi stranicu prije slanja.'));
  if((int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_business_leads WHERE user_id=? AND received_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 HOUR)',[$uid])['n']>=40)throw new InvalidArgumentException(fcc_t('Pokušaj ponovno kasnije ili se javi suradniku izravno.'));
  $manage=bin2hex(random_bytes(32));
  fcc_partner_query('INSERT INTO fcc_business_leads (user_id,presentation_id,share_id,request_key,identity_key,name,email,phone,channel,country,question,content_version,consent_version,consent_at,is_demo,management_hash,management_sealed,received_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP(),?,?,?,UTC_TIMESTAMP())',[$uid,(int)$s['presentation_id'],(int)$s['id'],$request,$identity,$name,$email,$phone,$channel,$country,$question,(int)$fresh['current_version'],'business-2026-09-26.1',(int)$s['is_demo'],hash('sha256',$manage),fcc_business_seal($manage)]);$rid=(int)database()->insert_id;
  $snapshot=['presentation_version'=>(int)$fresh['current_version'],'asset_id'=>$fresh['asset']['id']??null,'asset_version'=>$fresh['asset']['version']??null,'title'=>$fresh['asset']['title']??'Upoznaj Forever posao','intro'=>$fresh['intro'],'language'=>fcc_locale()];
  if($trustedSource==='sponsoring')$snapshot['source']='sponsoring';
  fcc_partner_query('UPDATE fcc_business_leads SET topic=?,question_hash=?,retention_until=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 90 DAY),content_snapshot=? WHERE id=?',[$topic,$questionHash,fcc_business_json($snapshot),$rid]);
  if($trustedPreview){fcc_partner_query('UPDATE fcc_business_leads SET is_demo=1,landing_is_test=1 WHERE id=?',[$rid]);return ['new'=>true,'lead_id'=>$rid,'manage_token'=>$manage,'preview'=>true];}
  $candidates=fcc_partner_rows("SELECT * FROM fcc_partner_contacts WHERE user_id=? AND ((?<>'' AND LOWER(email)=?) OR (?<>'' AND phone=?)) LIMIT 10 FOR UPDATE",[$uid,$email,$email,$phone,$phone]);
  $exact=array_values(array_filter($candidates,fn($c)=>mb_strtolower(trim($c['name']))===mb_strtolower($name)&&(!$email||!$c['email']||mb_strtolower($c['email'])===$email)&&(!$phone||!$c['phone']||fcc_partner_contact_phone($c['phone'],true)===$phone)));
  $self=($email!==''&&$email===mb_strtolower($fresh['owner']['email']))||($phone!==''&&ltrim($phone,'+')===ltrim($fresh['owner']['phone'],'+'));
  $review=$self||(count($candidates)>0&&(count($candidates)!==1||count($exact)!==1));$cid=!$review&&count($exact)===1?(int)$exact[0]['id']:0;
  if(!$cid){fcc_partner_query("INSERT INTO fcc_partner_contacts (user_id,name,email,phone,note,business_stage,contact_permission,source_kind,source_id,created_at,updated_at) VALUES (?,?,?,?,?,'interested','requested','business_presentation',?,UTC_TIMESTAMP(),UTC_TIMESTAMP())",[$uid,$name,$email,$phone,($s['is_demo']?'Probni upit. ':'').'Zatražen odgovor o poslovnoj suradnji.'.($review?' Provjeri podudaranje s postojećim kontaktom.':''),$rid]);$cid=(int)database()->insert_id;}
  fcc_partner_query('UPDATE fcc_business_leads SET contact_id=?,needs_review=? WHERE id=?',[$cid,$review?1:0,$rid]);
  if(!$review&&fcc_partner_contact($uid,$cid)['contact_permission']!=='do_not_contact'){
   fcc_partner_query("UPDATE fcc_business_followups SET state='waiting',due_date=NULL,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND contact_id=? AND state='closed'",[$uid,$cid]);
   fcc_partner_query("UPDATE fcc_partner_contacts SET business_stage='interested',version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND id=? AND business_stage='closed'",[$uid,$cid]);
  }
  fcc_partner_query("INSERT INTO fcc_partner_contact_sources (user_id,source_kind,source_id,contact_id,created_at) VALUES (?,'business_presentation',?,?,UTC_TIMESTAMP())",[$uid,$rid,$cid]);
  fcc_partner_query("INSERT IGNORE INTO fcc_partner_activities (user_id,contact_id,kind,note,request_key,created_at) VALUES (?,?,'business_interest',?,?,UTC_TIMESTAMP())",[$uid,$cid,'Marketing plan: zatražen odgovor putem '.($channel==='email'?'emaila.':'WhatsAppa.'),'business:lead:'.$rid]);
  fcc_partner_query('INSERT IGNORE INTO fcc_partner_pilot_events (user_id,step_id,kind,source,detail,request_key,created_at) VALUES (?,?,?,?,?,?,UTC_TIMESTAMP())',[$uid,$s['journey_step_id']?(int)$s['journey_step_id']:null,($s['is_demo']||$review)?'business_demo_interest':'business_interest',($s['is_demo']||$review)?'excluded_result':'server_verified','Zaprimljen upit o poslovnoj suradnji.','business:lead:'.$rid]);
  fcc_business_sync_reply_task($uid,$cid);
  fcc_partner_notify($uid,'business:lead:'.$rid,'service',$s['is_demo']?'Probni upit o poslovnoj suradnji':'Novi upit o poslovnoj suradnji','Otvori kontakt i pogledaj zatraženi način javljanja.','partner/contacts?id='.$cid,604800);
  foreach(($trustedSource==='sponsoring'?['owner']:($email!==''?['guest','owner']:['owner'])) as $kind)if($kind!=='owner'||$email!==mb_strtolower($fresh['owner']['email']))fcc_partner_query('INSERT IGNORE INTO fcc_business_jobs (lead_id,kind,due_at,updated_at) VALUES (?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$rid,$kind]);
  return ['new'=>true,'lead_id'=>$rid,'manage_token'=>$manage];
 });
}
/* /Custom code: FC-2026-10-14 */
function fcc_business_private(string $token): ?array {if(!preg_match('/^[a-f0-9]{64}$/D',$token))return null;return fcc_partner_one("SELECT r.*,p.language FROM fcc_business_leads r JOIN fcc_business_presentations p ON p.id=r.presentation_id WHERE r.management_hash=? AND r.status<>'erased'",[hash('sha256',$token)]);}
function fcc_business_withdraw(int $rid,bool $erase=false): void {
 $r=fcc_partner_one('SELECT * FROM fcc_business_leads WHERE id=?',[$rid]);if(!$r)return;
 fcc_business_atomic((int)$r['user_id'],function()use($r,$rid,$erase){
  $r=fcc_partner_one('SELECT * FROM fcc_business_leads WHERE id=? FOR UPDATE',[$rid]);if(!$r||$r['status']==='erased')return;
  $uid=(int)$r['user_id'];$cid=(int)$r['contact_id'];
  fcc_partner_query("UPDATE fcc_business_leads SET status=?,withdrawn_at=UTC_TIMESTAMP() WHERE id=?",[$erase?'erased':'withdrawn',$rid]);
  fcc_partner_query("UPDATE fcc_business_jobs SET state='cancelled',payload_json=NULL,updated_at=UTC_TIMESTAMP() WHERE lead_id=? AND state<>'sending'",[$rid]);
  fcc_partner_query('DELETE FROM fcc_partner_tasks WHERE user_id=? AND request_key=? AND completed_at IS NULL',[$uid,'business:reply:'.$rid]);
  fcc_partner_query('UPDATE fcc_partner_notifications SET read_at=UTC_TIMESTAMP(),expires_at=UTC_TIMESTAMP() WHERE user_id=? AND event_key=?',[$uid,'business:lead:'.$rid]);
  $otherSources=(int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_contact_sources cs WHERE cs.user_id=? AND cs.contact_id=? AND NOT(cs.source_kind='business_presentation' AND cs.source_id=?) AND (cs.source_kind<>'business_presentation' OR EXISTS (SELECT 1 FROM fcc_business_leads active_lead WHERE active_lead.id=cs.source_id AND active_lead.status='received'))",[$uid,$cid,$rid])['n'];
  if(!$otherSources)fcc_partner_query("UPDATE fcc_partner_contacts SET contact_permission='do_not_contact',next_followup=NULL,version=version+1,updated_at=UTC_TIMESTAMP() WHERE id=? AND user_id=? AND source_kind='business_presentation' AND contact_permission='requested'",[$cid,$uid]);
  $active=fcc_partner_one("SELECT id FROM fcc_business_leads WHERE user_id=? AND contact_id=? AND status='received'",[$uid,$cid]);
  if(!$active){
   fcc_partner_query("UPDATE fcc_business_followups SET state='closed',due_date=NULL,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND contact_id=?",[$uid,$cid]);
   fcc_partner_query("UPDATE fcc_partner_tasks SET completed_at=UTC_TIMESTAMP() WHERE user_id=? AND contact_id=? AND request_key LIKE 'business:%' AND completed_at IS NULL",[$uid,$cid]);
  }
  if(fcc_partner_one('SELECT id FROM fcc_partner_contacts WHERE user_id=? AND id=?',[$uid,$cid]))fcc_business_sync_reply_task($uid,$cid);
  if($erase){
   fcc_partner_query("DELETE FROM fcc_partner_contact_sources WHERE user_id=? AND source_kind='business_presentation' AND source_id=?",[$uid,$rid]);
   fcc_partner_query('DELETE FROM fcc_partner_activities WHERE user_id=? AND request_key=?',[$uid,'business:lead:'.$rid]);
   fcc_partner_query("UPDATE fcc_business_leads SET name='',email='',phone='',question='',content_snapshot=NULL,question_hash='',identity_key=?,management_hash=NULL,management_sealed=NULL,contact_id=NULL WHERE id=?",[hash('sha256',random_bytes(32)),$rid]);
   $sources=(int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_contact_sources WHERE user_id=? AND contact_id=?',[$uid,$cid])['n'];
   $history=(int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_activities WHERE user_id=? AND contact_id=?',[$uid,$cid])['n'];
   if(!$sources&&!$history)fcc_partner_query("DELETE FROM fcc_partner_tasks WHERE user_id=? AND contact_id=? AND request_key LIKE 'business:%'",[$uid,$cid]);
   $tasks=(int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_tasks WHERE user_id=? AND contact_id=?',[$uid,$cid])['n'];
   if(!$sources&&!$history&&!$tasks)fcc_partner_query("DELETE FROM fcc_partner_contacts WHERE id=? AND user_id=? AND source_kind='business_presentation' AND source_id=? AND version<=2",[$cid,$uid,$rid]);
  }
 });
}
