<?php
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_cc.php';
require_once __DIR__.'/fcc_push_delivery.php';

function fcc_partner_notification_rules(): array {
 return ['cc'=>[fcc_cc_t('my_points_events'),fcc_cc_t('hour_note')]] + fcc_localize_copy(['team'=>['Napredak mojih suradnika',fcc_push_setting_copy('team_help')], 'daily'=>['Moj dnevni korak','Jedan objedinjeni podsjetnik na program i dogovorena javljanja. Termin i pauzu uredi u Moj cilj.'], 'service'=>['Račun, pristup i rezultati','Postojeće obavijesti o odobrenju, paketu i pragovima klikova.'],
 'admin'=>['Administratorski red','Nova registracija, prva PRO aktivacija i NFC obrada iz postojećeg FCC-a.'],
 'followup'=>['Dogovorena javljanja','Jedan dnevni sažetak dospjelih kontakata; bez kontakata koji ne žele javljanje.'],
 'tasks'=>['Osobni zadaci','Jedan dnevni podsjetnik na otvorene dospjele zadatke.'],
 'education'=>['Edukacija 4 Core','Jedan podsjetnik dnevno ako je otvoren korak koji još nije odrađen.'],
 'webinar'=>['Webinari i događaji','Podsjetnici, promjene i otkazivanja termina dostupnih tvojem računu.']]);
}
function fcc_partner_notification_config(string $key, $default=null) {
 $r=fcc_partner_one('SELECT value FROM fcc_partner_notification_settings WHERE setting_key=?',[$key]);
 return $r?($key==='rules'?array_merge(array_fill_keys(array_keys(fcc_partner_notification_rules()),true),json_decode($r['value'],true)?:[]):json_decode($r['value'],true)):$default;
}
function fcc_partner_notification_config_save(string $key,$value): void {
 fcc_partner_query('INSERT INTO fcc_partner_notification_settings (setting_key,value,updated_at) VALUES (?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE value=VALUES(value),updated_at=UTC_TIMESTAMP()',[$key,json_encode($value)]);
}
function fcc_partner_notification_preferences(int $uid): array {
 $row=fcc_partner_one('SELECT preferences FROM users WHERE user_id=?',[$uid]);
 $p=json_decode($row['preferences']??'{}',true)['partner_notifications']??[];
 if(!isset($p['categories']['daily'])) $p['categories']['daily']=!isset($p['categories'])||!empty($p['categories']['education'])||!empty($p['categories']['followup'])||!empty($p['categories']['tasks']);
 return fcc_cc_preferences($p)+['push'=>array_key_exists('push',$p)?!empty($p['push']):fcc_partner_enabled($uid), 'sound'=>!array_key_exists('sound',$p)||!empty($p['sound']), 'categories'=>array_merge(array_fill_keys(array_keys(fcc_partner_notification_rules()),true),$p['categories']??[])];
}
function fcc_partner_notification_path(string $path): string {
 // Only same-site relative application URLs; no protocol-relative or backslash escape.
 if(preg_match('~^(?:partner(?:/|\?|$)|admin(?:/|$)|fcc-results(?:\?|$)|forever-business(?:\?|$)|account-plan(?:\?|$)|feedback-ticket(?:/|s|\?|$)|ai-plan(?:\?|$)|internal-notifications(?:\?|$))~',$path)&&!preg_match('/[\\\\\x00-\x20]/',$path)) return $path;
 return 'partner/notifications';
}
function fcc_partner_notify(int $uid,string $key,string $category,string $title,string $body,string $path,int $ttl=86400,?string $created=null): void {
 if(!fcc_partner_enabled($uid)||!isset(fcc_partner_notification_rules()[$category])) return;
 $created=$created??gmdate('Y-m-d H:i:s');
 $preferences=fcc_partner_notification_preferences($uid);if(empty($preferences['categories'][$category]))return;
 if(($event=fcc_cc_team_event_key($key))&&!fcc_cc_event_enabled($uid,$event,$created))return;
 $expires=gmdate('Y-m-d H:i:s',strtotime($created.' UTC')+$ttl);
 fcc_partner_query('INSERT IGNORE INTO fcc_partner_notifications (user_id,event_key,category,title,body,path,created_at,expires_at) VALUES (?,?,?,?,?,?,?,?)',[$uid,$key,$category,mb_substr(strip_tags($title),0,180),mb_substr(strip_tags($body),0,2000),fcc_partner_notification_path($path),$created,$expires]);
}
function fcc_partner_push_endpoint_valid(string $endpoint): bool {
 $p=parse_url($endpoint); if(!$p||($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||isset($p['fragment'])||(($p['port']??443)!==443)||strlen($endpoint)>2048) return false;
 $host=strtolower($p['host']??'');
 foreach(['fcm.googleapis.com','android.googleapis.com','updates.push.services.mozilla.com','push.apple.com','notify.windows.com'] as $allowed) {
  if($host===$allowed || (in_array($allowed,['push.apple.com','notify.windows.com'],true)&&str_ends_with($host,'.'.$allowed))) return true;
 }
 return false;
}
function fcc_partner_push_subscribe(int $uid,array $input): void {
 $sub=json_decode((string)($input['subscription']??''),true); $endpoint=$sub['endpoint']??'';
 if(!is_string($endpoint)||!fcc_partner_push_endpoint_valid($endpoint)||!preg_match('/^[A-Za-z0-9_-]{87}$/D',$sub['keys']['p256dh']??'')||!preg_match('/^[A-Za-z0-9_-]{22}$/D',$sub['keys']['auth']??'')) throw new InvalidArgumentException(fcc_t('Pretplata uređaja nije valjana.'));
 fcc_partner_query('INSERT INTO fcc_partner_push_subscriptions (user_id,endpoint_hash,endpoint,p256dh,auth_key,created_at,updated_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id),p256dh=VALUES(p256dh),auth_key=VALUES(auth_key),active=1,updated_at=UTC_TIMESTAMP()',[$uid,hash('sha256',$endpoint),$endpoint,$sub['keys']['p256dh'],$sub['keys']['auth']]);
 $p=fcc_partner_notification_preferences($uid);$p['push']=true;fcc_partner_save_preferences($uid,'partner_notifications',$p);
}
/** Automatic login sync must never reverse an explicit account opt-out. */
function fcc_partner_push_connect(int $uid,array $input): void {
 if(!fcc_partner_enabled($uid))throw new InvalidArgumentException(fcc_t('Tvoj račun nema pristup novom FCC-u.'));
 database()->begin_transaction();
 try {
  if(!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? AND status=1 FOR UPDATE',[$uid]))throw new InvalidArgumentException(fcc_t('Račun nije aktivan.'));
  if(($input['automatic']??'')==='1'&&!fcc_partner_notification_preferences($uid)['push'])throw new InvalidArgumentException(fcc_t('Obavijesti su isključene u tvojim postavkama.'));
  fcc_partner_push_subscribe($uid,$input);
  database()->commit();
 }catch(Throwable $e){database()->rollback();throw $e;}
}
function fcc_partner_notification_sync_user(array $u,DateTimeImmutable $now): void {
 $uid=(int)$u['user_id']; if(!fcc_partner_enabled($uid)) return;
 fcc_team_refresh_notifications($uid);
 fcc_cc_refresh_notifications($uid,$now);
 try{fcc_top_notify_user($uid,$now);}catch(Throwable $e){error_log('FCC Top notices: '.get_class($e));}
 $admin=(int)$u['type']>0;
 $source=fcc_partner_rows("SELECT internal_notification_id,title,description,url,datetime,for_who FROM internal_notifications WHERE (user_id=? OR user_id IS NULL) AND (for_who='user' OR (?=1 AND for_who='admin')) ORDER BY internal_notification_id DESC LIMIT 100",[$uid,$admin?1:0]);
 foreach($source as $n) {
  $path=(string)$n['url'];if(str_starts_with($path,SITE_URL))$path=substr($path,strlen(SITE_URL));
  fcc_partner_notify($uid,'legacy:'.$n['internal_notification_id'],$n['for_who']==='admin'?'admin':'service',$n['title'],$n['description'],$path,86400,$n['datetime']);
 }
 if($admin) {
  foreach(fcc_partner_rows("SELECT t.feedback_ticket_id,t.subject,MAX(m.feedback_ticket_message_id) message_id FROM feedback_tickets t JOIN feedback_ticket_messages m ON m.feedback_ticket_id=t.feedback_ticket_id AND m.is_admin_reply=0 WHERE t.status='open' GROUP BY t.feedback_ticket_id,t.subject ORDER BY message_id DESC LIMIT 100") as $ticket) {
   fcc_partner_notify($uid,'support:'.$ticket['feedback_ticket_id'].':'.$ticket['message_id'],'admin','Pitanje čeka tvoj odgovor',$ticket['subject'],'admin/feedback-tickets/ticket/'.$ticket['feedback_ticket_id'],604800);
  }
  fcc_partner_query("UPDATE fcc_partner_notifications n JOIN feedback_tickets t ON n.path=CONCAT('admin/feedback-tickets/ticket/',t.feedback_ticket_id) SET n.read_at=COALESCE(n.read_at,UTC_TIMESTAMP()) WHERE n.user_id=? AND n.event_key LIKE 'support:%' AND t.status<>'open'",[$uid]);
  foreach(fcc_partner_rows('SELECT user_id,name,datetime FROM users WHERE status=0 ORDER BY user_id DESC LIMIT 100') as $pending) fcc_partner_notify($uid,'approval:'.$pending['user_id'],'admin','Novi korisnik čeka provjeru',$pending['name'].' · Otvori LOS operativni red.','admin/leader-operating-system?tab=operations',86400,$pending['datetime']);
  foreach(fcc_partner_rows("SELECT user_id,name,JSON_UNQUOTE(JSON_EXTRACT(preferences,'$.meta.fcc_nfc_requested_at')) requested FROM users WHERE JSON_EXTRACT(preferences,'$.meta.fcc_nfc_required')=1 ORDER BY user_id DESC LIMIT 100") as $card) fcc_partner_notify($uid,'nfc:'.$card['user_id'],'admin','NFC kartica čeka obradu',$card['name'].' · Zahtjev iz prve PRO aktivacije.','admin/leader-operating-system?tab=operations',86400,$card['requested']?:null);
 }
 // A single daily summary per category. Inbox is available even without device permission.
 $date=$now->format('Y-m-d');$hour=(int)$now->format('G');
 if(fcc_journey_enabled($uid)) {
  $summary=fcc_journey_reminder($uid,$now);
  if($summary)fcc_partner_notify($uid,'daily:'.$date,'daily',$summary['title'],$summary['body'],$summary['path'],max(60,$now->setTime(21,0)->getTimestamp()-$now->getTimestamp()),$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
 } elseif($hour>=9 && $hour<21) {
  $due=(int)(fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_contacts WHERE user_id=? AND archived_at IS NULL AND next_followup<=? AND contact_permission<>'do_not_contact'",[$uid,$date])['n']??0);
  if($due) fcc_partner_notify($uid,'followup:'.$date,'followup','Vrijeme za dogovorena javljanja',"Dospjeli kontakti: $due. Otvori dogovore i odaberi sljedeći razgovor.",'partner/contacts?filter=due',43200);
  $tasks=(int)(fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_tasks t WHERE user_id=? AND due_date<=? AND completed_at IS NULL AND NOT EXISTS (SELECT 1 FROM fcc_partner_contacts c WHERE c.id=t.contact_id AND c.user_id=t.user_id AND c.archived_at IS NOT NULL)',[$uid,$date])['n']??0);
  if($tasks) fcc_partner_notify($uid,'tasks:'.$date,'tasks','Tvoj dnevni popis',"Otvoreni dospjeli zadaci: $tasks.",'partner',43200);
  $program=fcc_partner_program($uid);$action=$program['action']??[];
  if(!empty($program['state']['can_access_education'])&&!empty($action['can_complete'])) fcc_partner_notify($uid,'education:'.$date,'education','Tvoj 4 Core korak je spreman',(string)$action['title'],'partner/education',43200);
 }
 if(!(function_exists('fcc_events_enabled')&&fcc_events_enabled())&&$now->format('N')==='7'&&$hour===18&&!(fcc_journey_enabled($uid)&&fcc_partner_one("SELECT id FROM fcc_partner_notifications WHERE user_id=? AND event_key=? AND created_at>=?",[$uid,'daily:'.$date,$now->modify('-1 hour')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')]))) fcc_partner_notify($uid,'webinar:'.$date,'webinar','Webinar danas u 19:00','Otvori poziv i pridruži se u 19:00 Europe/Zagreb.','partner/share?type=webinar',max(60,$now->setTime(19,0)->getTimestamp()-$now->getTimestamp()),$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
}
function fcc_partner_push_public_key(): string { return (string)getenv('FCC_PUSH_PUBLIC_KEY'); }
function fcc_partner_push_transport_ready(): bool { return fcc_partner_push_public_key()!==''&&getenv('FCC_PUSH_PRIVATE_KEY')&&getenv('FCC_PUSH_SUBJECT'); }
/** Fail closed outside production or an explicitly enabled, unexpired private HTTPS pilot. */
function fcc_partner_push_mode_for(bool $local,bool $pilot,bool $send,bool $ready,string $site,array $access,int $now): string {
 if($local&&!$pilot) return 'local_inbox';
 if($pilot) {
  $origin=rtrim((string)($access['origin']??''),'/');
  if(!$local||!str_starts_with($origin,'https://')||rtrim($site,'/')!==$origin||((float)($access['expiresAt']??0))<=$now*1000) return 'pilot_expired';
 }
 if(!$send) return 'disabled';
 if(!$ready) return 'not_configured';
 return $pilot?'phone_pilot':'enabled';
}
function fcc_partner_push_mode(): string {
 $access=[];
 if(getenv('FCC_PHONE_PILOT')==='1') {
  $file=dirname(__DIR__,2).'/local/phone/access.json';
  if(is_readable($file)) $access=json_decode(file_get_contents($file),true)?:[];
 }
 return fcc_partner_push_mode_for(getenv('FCC_LOCAL')==='1',getenv('FCC_PHONE_PILOT')==='1',getenv('FCC_PUSH_SEND')==='1',fcc_partner_push_transport_ready()&&class_exists('\\Minishlink\\WebPush\\WebPush'),SITE_URL,$access,time());
}
function fcc_partner_push_mode_label(string $mode): string {
 return ['phone_pilot'=>'Stvarni push uključen za privatni HTTPS test','enabled'=>'Push transport uključen','local_inbox'=>'Lokalni inbox · slanje na uređaj samo u HTTPS testu','pilot_expired'=>'Privatni test je istekao ili adresa nije potvrđena','not_configured'=>'Push transport čeka konfiguraciju','disabled'=>'Push slanje isključeno'][$mode]??'Push slanje isključeno';
}
function fcc_partner_push_test(int $uid): void {
 $p=fcc_partner_notification_preferences($uid);
 if(!in_array(fcc_partner_push_mode(),['enabled','phone_pilot'],true)) throw new InvalidArgumentException(fcc_t('Otvori aktivni HTTPS test za provjeru obavijesti.'));
 if(!$p['push']||empty($p['categories']['service'])||empty(fcc_partner_notification_config('rules',array_fill_keys(array_keys(fcc_partner_notification_rules()),true))['service'])||!fcc_partner_one('SELECT id FROM fcc_partner_push_subscriptions WHERE user_id=? AND active=1',[$uid])) throw new InvalidArgumentException(fcc_t('Najprije uključi uređaj i kategoriju Račun, pristup i rezultati.'));
 // Self-service only; hourly key makes repeated taps idempotent.
 fcc_partner_notify($uid,'push-test:'.gmdate('Y-m-d-H'),'service','Tvoja probna obavijest','Ovo je provjera uređaja. Push red poštuje tvoje postavke i mirno vrijeme.','partner/notifications',3600);
}
function fcc_partner_push_deliver(DateTimeImmutable $now,?callable $transport=null): array {
 $rules=fcc_partner_notification_config('rules',array_fill_keys(array_keys(fcc_partner_notification_rules()),true));
 $now=$now->setTimezone(new DateTimeZone('Europe/Zagreb'));
 $stats=['sent'=>0,'simulated'=>0,'failed'=>0];$batches=[];
 fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='pending' WHERE status='sending' AND updated_at<DATE_SUB(UTC_TIMESTAMP(),INTERVAL 10 MINUTE)");
 $rows=fcc_partner_rows("SELECT d.*,n.user_id,n.category,n.created_at,n.expires_at,n.read_at,n.path,n.event_key,s.endpoint,s.p256dh,s.auth_key,s.active,s.user_id device_owner FROM fcc_partner_push_deliveries d JOIN fcc_partner_notifications n ON n.id=d.notification_id JOIN fcc_partner_push_subscriptions s ON s.id=d.subscription_id WHERE d.status='pending' AND d.available_at<=? ORDER BY CASE WHEN n.expires_at<=? THEN 0 WHEN n.category='webinar' THEN 1 WHEN n.category='service' THEN 2 ELSE 3 END,n.expires_at,d.id LIMIT 100",[$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')]);
 foreach($rows as $d) {
  // Local webinar events stay in the inbox even when another feature tests phone push.
  if(getenv('FCC_LOCAL')==='1'&&(str_starts_with($d['event_key']??'','webinar:')||str_starts_with($d['event_key']??'','business:')||str_starts_with($d['event_key']??'','team:'))) {
   fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code='local_webinar_inbox',updated_at=UTC_TIMESTAMP() WHERE id=?",[(int)$d['id']]);continue;
  }
  if(str_starts_with($d['event_key']??'','top:')){fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code='inbox_only' WHERE id=?",[(int)$d['id']]);continue;}
  $uid=(int)$d['user_id'];$p=fcc_partner_notification_preferences($uid);
  if(!fcc_cc_notification_allowed($uid,$d)){fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code='preference_or_cc_scope_changed',updated_at=UTC_TIMESTAMP() WHERE id=?",[(int)$d['id']]);continue;}
  if(!fcc_team_notification_allowed($uid,$d['event_key'])) {
   fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code='team_scope_changed',updated_at=UTC_TIMESTAMP() WHERE id=?",[(int)$d['id']]);continue;
  }
  if(strtotime($d['expires_at'].' UTC')<=$now->getTimestamp()){fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code='expired',updated_at=UTC_TIMESTAMP() WHERE id=?",[(int)$d['id']]);continue;}
  if(!fcc_team_notification_allowed($uid,$d['event_key'],true)){fcc_partner_query("UPDATE fcc_partner_push_deliveries SET available_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 MINUTE) WHERE id=?",[(int)$d['id']]);continue;}

  if(function_exists('fcc_event_delivery_allowed')&&!fcc_event_delivery_allowed($uid,$d['event_key'],$now)){fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code='event_changed',updated_at=UTC_TIMESTAMP() WHERE id=?",[(int)$d['id']]);continue;}
  $user=fcc_partner_one('SELECT status,type,language FROM users WHERE user_id=?',[$uid]);
  if(fcc_journey_enabled($uid)&&(in_array($d['category'],['followup','tasks','education'],true)||($d['category']==='daily'&&!fcc_journey_reminder($uid,$now)))) {
   fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code='superseded_or_done',updated_at=UTC_TIMESTAMP() WHERE id=?",[(int)$d['id']]);continue;
  }
  $expired=strtotime($d['expires_at'].' UTC')<=$now->getTimestamp();
  if(!$user||(int)$user['status']!==1||!fcc_partner_enabled($uid)||(int)$d['device_owner']!==$uid||!$d['active']||!$p['push']||empty($p['categories'][$d['category']])||empty($rules[$d['category']])||($d['category']==='admin'&&(int)$user['type']<=0)||$expired||$d['read_at']) {
   fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='skipped',last_code=?,updated_at=UTC_TIMESTAMP() WHERE id=?",[$expired?'expired':($d['read_at']?'already_read':'disabled'),(int)$d['id']]);continue;
  }
  if(str_starts_with($d['event_key'],'cc:')&&(int)$now->format('G')<$p['cc_hour']){fcc_partner_query('UPDATE fcc_partner_push_deliveries SET available_at=? WHERE id=?',[$now->setTime($p['cc_hour'],0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),(int)$d['id']]);continue;}
  if((int)$now->format('G')<8||(int)$now->format('G')>=21){$retry=((int)$now->format('G')<8?$now:$now->modify('+1 day'))->setTime(8,0)->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');fcc_partner_query('UPDATE fcc_partner_push_deliveries SET available_at=? WHERE id=?',[$retry,(int)$d['id']]);continue;}
  // Validate every item before grouping. A batch never crosses a subscription or account.
  $d['language']=$user['language']??'en';$d['sound']=$p['sound'];$d['cc_details']=$p['cc_details'];
  $batches[(int)$d['subscription_id']][]=$d;
 }
 foreach($batches as $batch) {
  $d=$batch[0];
  $retry=fcc_push_batch_due($batch,$now);
  if($retry>$now) {
   foreach($batch as $item)fcc_partner_query('UPDATE fcc_partner_push_deliveries SET available_at=? WHERE id=?',[$retry->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),(int)$item['id']]);
   continue;
  }
  // An inbox-only worker must never consume a real device's pending delivery.
  if(!$transport&&!in_array(fcc_partner_push_mode(),['enabled','phone_pilot'],true))continue;
  $ids=array_map(static fn($item)=>(int)$item['id'],$batch);$placeholders=implode(',',array_fill(0,count($ids),'?'));
  fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='sending',attempts=attempts+1,updated_at=UTC_TIMESTAMP() WHERE id IN ($placeholders)",$ids);
  $d['payload']=fcc_push_payload($batch);$d['batch_ids']=$ids;
  try {
   if($transport)$result=$transport($d);
   else {
    if(!fcc_partner_push_endpoint_valid($d['endpoint']))throw new RuntimeException(fcc_t('Invalid endpoint.'));
    $client=new \Minishlink\WebPush\WebPush(['VAPID'=>['subject'=>getenv('FCC_PUSH_SUBJECT'),'publicKey'=>fcc_partner_push_public_key(),'privateKey'=>getenv('FCC_PUSH_PRIVATE_KEY')]],[],10,['allow_redirects'=>false]);
    $expiry=min(array_map(static fn($item)=>strtotime($item['expires_at'].' UTC'),$batch));
    $report=$client->sendOneNotification(\Minishlink\WebPush\Subscription::create(['endpoint'=>$d['endpoint'],'keys'=>['p256dh'=>$d['p256dh'],'auth'=>$d['auth_key']]]),json_encode($d['payload'],JSON_UNESCAPED_UNICODE),['TTL'=>min(3600,max(1,$expiry-$now->getTimestamp()))]);
    $result=$report->isSuccess()?'sent':($report->isSubscriptionExpired()?'expired':'retry');
   }
  }catch(Throwable $e){$result='transport_error';}
  if($result==='expired')fcc_partner_query('UPDATE fcc_partner_push_subscriptions SET active=0,updated_at=UTC_TIMESTAMP() WHERE id=?',[(int)$d['subscription_id']]);
  $accepted=in_array($result,['sent','simulated'],true);
  foreach($batch as $item) {
   $status=$accepted?$result:($result==='expired'?'skipped':((int)$item['attempts']>=2?'failed':'pending'));
   fcc_partner_query('UPDATE fcc_partner_push_deliveries SET status=?,last_code=?,available_at=?,updated_at=? WHERE id=?',[$status,$accepted?$result.'_group:'.min($ids):$result,$now->modify('+15 minutes')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),(int)$item['id']]);
  }
  $stats[$accepted?$result:'failed']++;
 }
 return $stats;
}
function fcc_partner_notifications_run(?DateTimeImmutable $now=null): array {
 if(getenv('FCC_PARTNER_ENABLED')!=='1') return ['disabled'=>true];
 if(!(int)(fcc_partner_one("SELECT GET_LOCK('fcc_partner_notifications',0) acquired")['acquired']??0)) return ['busy'=>true];
 try {
  $now=($now??new DateTimeImmutable())->setTimezone(new DateTimeZone('Europe/Zagreb'));
  // Cursor ensures every active account is processed across bounded cron runs.
  $cursor=(int)fcc_partner_notification_config('user_cursor',0);
  $users=fcc_partner_rows('SELECT user_id,type FROM users WHERE status=1 AND user_id>? ORDER BY user_id LIMIT 100',[$cursor]);
  foreach($users as $u) fcc_partner_notification_sync_user($u,$now);
  fcc_partner_notification_config_save('user_cursor',count($users)===100?(int)end($users)['user_id']:0);
  fcc_partner_query("INSERT IGNORE INTO fcc_partner_push_deliveries (notification_id,subscription_id,available_at,updated_at) SELECT n.id,s.id,UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM fcc_partner_notifications n JOIN fcc_partner_push_subscriptions s ON s.user_id=n.user_id AND s.active=1 WHERE n.expires_at>UTC_TIMESTAMP() AND n.created_at>=s.created_at AND n.event_key NOT LIKE 'top:%'");
  $stats=fcc_partner_push_deliver($now);
  fcc_partner_notification_config_save(getenv('FCC_PHONE_PILOT')==='1'?'heartbeat_phone':'heartbeat',['at'=>gmdate('c'),'accounts'=>count($users),'transport'=>fcc_partner_push_mode(),'result'=>$stats]);
  return $stats;
 } finally { fcc_partner_query("SELECT RELEASE_LOCK('fcc_partner_notifications')"); }
}

function fcc_partner_push_detach(int $uid,string $hash): void {
 if(preg_match('/^[a-f0-9]{64}$/D',$hash)) fcc_partner_query('UPDATE fcc_partner_push_subscriptions SET active=0,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND endpoint_hash=?',[$uid,$hash]);
}

/** Translate only system notification templates. User names, subjects and event titles remain verbatim. */
function fcc_partner_notification_display(array $notice,?string $locale=null): array {
 if(str_starts_with($notice['event_key']??'','top:')){$kind=str_starts_with($notice['event_key'],'top:weekly:')?'weekly':'milestone';$notice['title']=fcc_top_t($kind.'_title',[],$locale);$notice['body']=fcc_top_t($kind.'_body',[],$locale);return $notice;}
 if(str_starts_with($notice['event_key']??'','cc:')){$items=fcc_cc_notice_items((int)$notice['user_id'],(int)$notice['id']);$notice['title']=fcc_cc_t('digest_title',[],$locale);$notice['body']=$items?fcc_cc_digest_text($items,$locale):fcc_cc_t('notification_removed',[],$locale);return $notice;}
 $locale=fcc_locale($locale);if(!in_array($locale,['sl','en','de','sr', 'sq', 'cnr', 'fr', 'es'],true))return $notice;
 $key=(string)($notice['event_key']??'');
 $title=(string)($notice['title']??'');$body=(string)($notice['body']??'');
 $notice['title']=fcc_t($title,[],$locale);
 if(str_starts_with($key,'support:'))return $notice;
 if(str_starts_with($key,'team:')&&preg_match('/^(.*) napreduje$/us',$title,$m))$notice['title']=$m[1].($locale==='de'?' macht Fortschritte':($locale==='en'?' is making progress':' napreduje'));
 if(fcc_new_locale($locale)) {
  if(str_starts_with($key,'team:')&&preg_match('/^(.*) napreduje$/us',$title,$m))$notice['title']=fcc_t('{name} napreduje',['{name}'=>$m[1]],$locale);
  $templates=[
   '~^Dospjeli kontakti: (\d+)\. Otvori dogovore i odaberi sljedeći razgovor\.$~u'=>'Dospjeli kontakti: {number}. Otvori dogovore i odaberi sljedeći razgovor.',
   '~^Otvoreni dospjeli zadaci: (\d+)\.$~u'=>'Otvoreni dospjeli zadaci: {number}.',
   '~^Otvori kontakt i pregledaj prijavu preko Pozivnice (\d+)\.$~u'=>'Otvori kontakt i pregledaj prijavu preko Pozivnice {number}.',
  ];
  foreach($templates as $pattern=>$source)if(preg_match($pattern,$body,$m)){$notice['body']=fcc_t($source,['{number}'=>$m[1]],$locale);return $notice;}
  if(str_starts_with($key,'daily:')) {
   $suffix=' Danas je webinar u 19:00 Europe/Zagreb; poziv je u odjeljku Podijeli.';$end='';
   if(str_ends_with($body,$suffix)){$body=substr($body,0,-strlen($suffix));$end=' '.fcc_t(trim($suffix),[],$locale);}
   $notice['body']=preg_match('/^Danas: (.*)\. Otvori svoju pripremu\.$/us',$body,$m)?fcc_t('Danas: {task}. Otvori svoju pripremu.',['{task}'=>fcc_t($m[1],[],$locale)],$locale).$end:fcc_t($body,[],$locale).$end;
   return $notice;
  }
 }
 if($locale==='sr') {
  $templates=[
   '~^Dospjeli kontakti: (\d+)\. Otvori dogovore i odaberi sljedeći razgovor\.$~u'=>fn($m)=>'Kontakti kojima je vreme da se javiš: '.$m[1].'. Otvori dogovore i izaberi sledeći razgovor.',
   '~^Otvoreni dospjeli zadaci: (\d+)\.$~u'=>fn($m)=>'Otvoreni zadaci kojima je istekao rok: '.$m[1].'.',
   '~^Otvori kontakt i pregledaj prijavu preko Pozivnice (\d+)\.$~u'=>fn($m)=>'Otvori kontakt i pogledaj prijavu preko pozivnice '.$m[1].'.',
  ];
  foreach($templates as $pattern=>$render)if(preg_match($pattern,$body,$m)){$notice['body']=$render($m);return $notice;}
  if(str_starts_with($key,'daily:')) {
   $suffix=' Danas je webinar u 19:00 Europe/Zagreb; poziv je u odjeljku Podijeli.';$end='';
   if(str_ends_with($body,$suffix)){$body=substr($body,0,-strlen($suffix));$end=' Danas je webinar u 19:00 Europe/Zagreb. Pozivnicu možeš da pronađeš u odeljku Podeli.';}
   $notice['body']=preg_match('/^Danas: (.*)\. Otvori svoju pripremu\.$/us',$body,$m)?'Danas: '.fcc_t($m[1],[],'sr').'. Otvori svoju pripremu.'.$end:fcc_t($body,[],'sr').$end;
   return $notice;
  }
 }
 $patterns=[
  '~^Dospjeli kontakti: (\d+)\. Otvori dogovore i odaberi sljedeći razgovor\.$~u'=>fn($m)=>$locale==='de'?'Kontakte für eine fällige Rückmeldung: '.$m[1].'. Öffne deine Vereinbarungen und wähle das nächste Gespräch.':($locale==='en'?'Contacts due for follow up: '.$m[1].'. Open your follow ups and choose your next conversation.':'Stiki, ki čakajo na dogovorjeno javljanje: '.$m[1].'. Odpri dogovore in izberi naslednji pogovor.'),
  '~^Otvoreni dospjeli zadaci: (\d+)\.$~u'=>fn($m)=>$locale==='de'?'Überfällige offene Aufgaben: '.$m[1].'.':($locale==='en'?'Overdue open tasks: '.$m[1].'.':'Odprte naloge z zapadlim rokom: '.$m[1].'.'),
  '~^Otvori kontakt i pregledaj prijavu preko Pozivnice (\d+)\.$~u'=>fn($m)=>$locale==='de'?'Öffne den Kontakt und sieh dir die Anmeldung über Einladung '.$m[1].' an.':($locale==='en'?'Open the contact and view the registration from invitation '.$m[1].'.':'Odpri stik in poglej prijavo prek povabila '.$m[1].'.'),
  '~^(Potvrđene prijave na webinar|Završeni osobni zadaci|Završeni koraci): (\d+)\. (.*)$~us'=>fn($m)=>fcc_t($m[1],[],$locale).': '.$m[2].'. '.fcc_t($m[3],[],$locale),
 ];
 foreach($patterns as $pattern=>$render)if(preg_match($pattern,$body,$m)){$notice['body']=$render($m);return $notice;}
 if(str_starts_with($key,'approval:')||str_starts_with($key,'nfc:')){
  $parts=explode(' · ',$body,2);$notice['body']=$parts[0].(isset($parts[1])?' · '.fcc_t($parts[1],[],$locale):'');return $notice;
 }
 if(str_starts_with($key,'daily:')){
  $suffix=' Danas je webinar u 19:00 Europe/Zagreb; poziv je u odjeljku Podijeli.';$end='';
  if(str_ends_with($body,$suffix)){$body=substr($body,0,-strlen($suffix));$end=$locale==='de'?' Heute findet um 19:00 Uhr (Europe/Zagreb) ein Webinar statt. Die Einladung findest du unter Teilen.':($locale==='en'?' There is a webinar today at 19:00 Europe/Zagreb. Find the invitation in Share.':' Danes je webinar ob 19:00 Europe/Zagreb. Povabilo najdeš v razdelku Deli.');}
  if(preg_match('/^Danas: (.*)\. Otvori svoju pripremu\.$/us',$body,$m))$body=($locale==='de'?'Heute: ':($locale==='en'?'Today: ':'Danes: ')).fcc_t($m[1],[],$locale).($locale==='de'?'. Öffne deine Vorbereitung.':($locale==='en'?'. Open your preparation.':'. Odpri svojo pripravo.'));
  else $body=fcc_t($body,[],$locale);
  $notice['body']=$body.$end;return $notice;
 }
 // Member-created webinar names and legacy free-form announcements are not translated automatically.
 if(str_starts_with($key,'webinar:calendar:'))return $notice;
 $notice['body']=fcc_t($body,[],$locale);return $notice;
}
