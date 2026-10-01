<?php
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require dirname(__DIR__).'/app/init.php';
if(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db')throw new RuntimeException('Local database required.');
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage().' at '.$e->getFile().':'.$e->getLine().PHP_EOL);exit(1);});
\Altum\Cache::initialize();\Altum\Plugin::initialize();
$checks=[];$assert=function($ok,$name)use(&$checks){if(!$ok)throw new RuntimeException($name);$checks[]=$name;};
foreach(['https://fcm.googleapis.com/fcm/send/test','https://web.push.apple.com/QATEST','https://updates.push.services.mozilla.com/wpush/v2/test'] as $endpoint)$assert(fcc_partner_push_endpoint_valid($endpoint),'Accept known push service');
foreach(['http://fcm.googleapis.com/send','https://127.0.0.1/','https://fcm.googleapis.com.evil.test/x','https://evilpush.apple.com/x','https://fcm.googleapis.com:8443/x','https://u:p@fcm.googleapis.com/x','https://fcm.googleapis.com/x#fragment'] as $endpoint)$assert(!fcc_partner_push_endpoint_valid($endpoint),'Reject invalid push target '.$endpoint);
$assert(fcc_partner_notification_path('https://evil.test/')==='partner/notifications'&&fcc_partner_notification_path('//evil.test/')==='partner/notifications','Notification destinations are local only');
$time=time();$access=['origin'=>'https://private.example.test','expiresAt'=>($time+3600)*1000];
$mode=static fn($local,$pilot,$send,$ready,$site,$a)=>fcc_partner_push_mode_for($local,$pilot,$send,$ready,$site,$a,$time);
$assert($mode(true,false,true,true,'http://localhost:8095/',[])==='local_inbox','Ordinary local worker never sends real push even with send flag');
$assert($mode(true,true,true,true,$access['origin'].'/',$access)==='phone_pilot','Explicit valid HTTPS phone pilot can send real push');
$assert($mode(true,true,false,true,$access['origin'],$access)==='disabled','Pilot respects disabled transport');
$assert($mode(true,true,true,false,$access['origin'],$access)==='not_configured','Pilot requires complete credentials and provider library');
$assert($mode(true,true,true,true,'https://different.example.test',$access)==='pilot_expired','Pilot origin mismatch blocks outgoing push');
$assert($mode(true,true,true,true,$access['origin'],array_merge($access,['expiresAt'=>($time-1)*1000]))==='pilot_expired','Expired private access blocks outgoing push');
$assert($mode(true,true,true,true,'http://localhost',['origin'=>'http://localhost','expiresAt'=>($time+1)*1000])==='pilot_expired','Non-HTTPS pilot blocks outgoing push');
$assert($mode(false,false,false,true,'https://forevercard.club/',[])==='disabled'&&$mode(false,false,true,true,'https://forevercard.club/',[])==='enabled','Production needs explicit separate send switch');
$uid=2;$key='qa:'.bin2hex(random_bytes(6));$now=new DateTimeImmutable('now',new DateTimeZone('Europe/Zagreb'));$noon=$now->modify('+1 day')->setTime(12,0);
// Acquire the same lock as the background worker; all fixture writes roll back.
fcc_partner_query("SELECT GET_LOCK('fcc_partner_notifications',10)");database()->begin_transaction();
try {
 // Keep this transactional fixture independent of other local devices and queues.
 fcc_partner_query('UPDATE fcc_partner_push_deliveries SET available_at=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 20 YEAR) WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id<>?)',[$uid]);
 fcc_partner_query('DELETE FROM fcc_partner_push_deliveries WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id=?)',[$uid]);
 fcc_partner_query('DELETE FROM fcc_partner_push_subscriptions WHERE user_id=?',[$uid]);
 fcc_partner_query('DELETE FROM fcc_partner_notifications WHERE user_id=?',[$uid]);
 fcc_partner_notification_config_save('rules',array_fill_keys(array_keys(fcc_partner_notification_rules()),true));
 $subscription=['endpoint'=>'https://fcm.googleapis.com/fcm/send/'.$key,'keys'=>['p256dh'=>str_repeat('A',87),'auth'=>str_repeat('A',22)]];
 fcc_partner_push_subscribe($uid,['subscription'=>json_encode($subscription)]);
 fcc_partner_push_subscribe($uid,['subscription'=>json_encode($subscription)]);
 $assert((int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_push_subscriptions WHERE user_id=?',[$uid])['n']===1,'Repeated device subscription is idempotent');
 fcc_partner_notify($uid,$key,'service','QA','Generic test','partner',172800);
 fcc_partner_notify($uid,$key,'service','QA','Generic test','partner',172800);
 $assert((int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=?',[$uid])['n']===1,'Repeated source event creates one inbox item');
 fcc_partner_query('INSERT INTO fcc_partner_push_deliveries (notification_id,subscription_id,available_at,updated_at) SELECT n.id,s.id,UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM fcc_partner_notifications n JOIN fcc_partner_push_subscriptions s ON n.user_id=s.user_id WHERE n.user_id=?',[$uid]);
 $calls=0;$sender=function($d)use(&$calls){$calls++;return 'sent';};
 fcc_partner_push_deliver($noon);
 $assert(fcc_partner_one('SELECT status FROM fcc_partner_push_deliveries WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id=?)',[$uid])['status']==='pending','Inbox-only worker leaves real pending deliveries for HTTPS worker');

 fcc_partner_push_deliver($noon->setTime(23,0),$sender);$assert($calls===0,'Quiet hours defer transport');
 $assert(strtotime(fcc_partner_one('SELECT available_at FROM fcc_partner_push_deliveries WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id=?)',[$uid])['available_at'].' UTC')>$noon->setTime(23,0)->getTimestamp(),'Quiet deliveries move out of the active queue');
 // Resume the fixture at its next eligible delivery attempt.
 fcc_partner_query("UPDATE fcc_partner_push_deliveries SET available_at=UTC_TIMESTAMP() WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id=?)",[$uid]);
 fcc_partner_push_deliver($noon,$sender);fcc_partner_push_deliver($noon,$sender);$assert($calls===1,'An accepted delivery is not resent');
 fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='pending',available_at=UTC_TIMESTAMP() WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id=?)",[$uid]);
 fcc_partner_query('UPDATE fcc_partner_notifications SET read_at=UTC_TIMESTAMP() WHERE user_id=?',[$uid]);
 fcc_partner_push_deliver($noon,$sender);$assert($calls===1,'Already-read inbox messages do not trigger stale push');
 fcc_partner_query('UPDATE fcc_partner_notifications SET read_at=NULL WHERE user_id=?',[$uid]);
 fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='pending',available_at=UTC_TIMESTAMP() WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id=?)",[$uid]);
 $p=fcc_partner_notification_preferences($uid);$p['push']=false;fcc_partner_save_preferences($uid,'partner_notifications',$p);
 fcc_partner_push_deliver($noon,$sender);$assert($calls===1,'Opt-out is checked immediately before sending');
 $p['push']=true;fcc_partner_save_preferences($uid,'partner_notifications',$p);
 fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='pending',available_at=UTC_TIMESTAMP(),attempts=0 WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id=?)",[$uid]);
 $expired=function($d){return 'expired';};fcc_partner_push_deliver($noon,$expired);
 $assert((int)fcc_partner_one('SELECT active FROM fcc_partner_push_subscriptions WHERE user_id=?',[$uid])['active']===0,'Expired provider subscription is disabled');
 fcc_partner_query('UPDATE fcc_partner_push_subscriptions SET active=1,user_id=1 WHERE user_id=?',[$uid]);
 fcc_partner_query("UPDATE fcc_partner_push_deliveries SET status='pending',available_at=UTC_TIMESTAMP() WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id=?)",[$uid]);
 fcc_partner_push_deliver($noon,$sender);$assert($calls===1,'A device reassigned to another account never receives the former account event');
 $assert((int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=1 AND event_key=?',[$key])['n']===0,'Inbox event ownership stays private');

 // Exercise the existing payment hook itself; no payment provider or mail call.
 $original=fcc_partner_one('SELECT * FROM users WHERE user_id=?',[$uid]);
 $prefs=json_decode($original['preferences'],true)?:[];
 foreach(['fcc_nfc_requested_at','fcc_nfc_sent_at','fcc_nfc_required','card_status'] as $field)unset($prefs['meta'][$field]);
 fcc_partner_query('UPDATE users SET preferences=? WHERE user_id=?',[json_encode($prefs),$uid]);
 $user=(object)fcc_partner_one('SELECT * FROM users WHERE user_id=?',[$uid]);
 $payment=new \Altum\Models\Payments();$hook=new ReflectionMethod($payment,'queue_nfc_card_fulfillment');
 $hook->invoke($payment,$user,(object)['plan_id'=>'5']);
 $first=fcc_partner_one('SELECT preferences FROM users WHERE user_id=?',[$uid]);
 $user=(object)fcc_partner_one('SELECT * FROM users WHERE user_id=?',[$uid]);
 $hook->invoke($payment,$user,(object)['plan_id'=>'5']);
 $again=fcc_partner_one('SELECT preferences FROM users WHERE user_id=?',[$uid]);
 $assert($first===$again&&(json_decode($first['preferences'],true)['meta']['fcc_nfc_required']??0)===1,'Canonical first PRO hook queues NFC once and preserves it on renewal');
 // Original FCC admin/private messages must be bridged without changing them.
 fcc_partner_query("INSERT INTO internal_notifications (user_id,for_who,from_who,icon,title,description,url,datetime) VALUES (NULL,'admin','system','fas fa-bell',?,'Test only','admin/leader-operating-system?tab=operations',UTC_TIMESTAMP())",[$key]);
 $source=(int)database()->insert_id;
 fcc_partner_notification_sync_user(['user_id'=>2,'type'=>0],$noon->setTime(22,0));
 fcc_partner_notification_sync_user(['user_id'=>3,'type'=>1],$noon->setTime(22,0));
 $assert(!fcc_partner_one('SELECT id FROM fcc_partner_notifications WHERE user_id=2 AND event_key=?',['legacy:'.$source])&&fcc_partner_one('SELECT id FROM fcc_partner_notifications WHERE user_id=3 AND event_key=?',['legacy:'.$source]),'Existing admin messages stay admin-only in the new inbox');
 $hash=hash('sha256',$subscription['endpoint']);fcc_partner_push_detach(2,$hash);
 $assert((int)fcc_partner_one('SELECT active FROM fcc_partner_push_subscriptions WHERE endpoint_hash=?',[$hash])['active']===1,'Logout cannot disable another account device');
 fcc_partner_push_detach(1,$hash);
 $assert((int)fcc_partner_one('SELECT active FROM fcc_partner_push_subscriptions WHERE endpoint_hash=?',[$hash])['active']===0,'Logout disables the owned device');
 // Retry and daily cap use the actual delivery state machine with a fake transport.
 fcc_partner_query('DELETE FROM fcc_partner_push_deliveries WHERE notification_id IN (SELECT id FROM fcc_partner_notifications WHERE user_id=?)',[$uid]);
 fcc_partner_query('DELETE FROM fcc_partner_notifications WHERE user_id=?',[$uid]);
 fcc_partner_query('UPDATE fcc_partner_push_subscriptions SET user_id=2,active=1 WHERE endpoint_hash=?',[$hash]);
 fcc_partner_notify($uid,$key.':retry','service','QA retry','Test','partner',172800);
 $event=(int)fcc_partner_one('SELECT id FROM fcc_partner_notifications WHERE user_id=? AND event_key=?',[$uid,$key.':retry'])['id'];
 $device=(int)fcc_partner_one('SELECT id FROM fcc_partner_push_subscriptions WHERE endpoint_hash=?',[$hash])['id'];
 fcc_partner_query('INSERT INTO fcc_partner_push_deliveries (notification_id,subscription_id,available_at,updated_at) VALUES (?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$event,$device]);
 for($i=0;$i<3;$i++){fcc_partner_query('UPDATE fcc_partner_push_deliveries SET available_at=UTC_TIMESTAMP() WHERE notification_id=?',[$event]);fcc_partner_push_deliver($noon,static fn($d)=>'retry');}
 $delivery=fcc_partner_one('SELECT status,attempts FROM fcc_partner_push_deliveries WHERE notification_id=?',[$event]);
 $assert($delivery['status']==='failed'&&(int)$delivery['attempts']===3,'Transport failures stop after three attempts');
 for($i=0;$i<5;$i++){
  fcc_partner_notify($uid,$key.':cap'.$i,'service','QA cap','Test','partner',172800);
  $event=(int)fcc_partner_one('SELECT id FROM fcc_partner_notifications WHERE user_id=? AND event_key=?',[$uid,$key.':cap'.$i])['id'];
  fcc_partner_query('INSERT INTO fcc_partner_push_deliveries (notification_id,subscription_id,available_at,updated_at) VALUES (?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$event,$device]);
 }
 $calls=0;fcc_partner_push_deliver($noon,$sender);$assert($calls===1,'Five simultaneous messages use one push delivery');
 $assert((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_push_deliveries WHERE subscription_id=? AND status='sent'",[$device])['n']===5,'Grouped delivery preserves all five inbox events and records acceptance');

 // Scheduling regression: no daily cap, one request per device, and explicit sound preference.
 $add=function($suffix,$category='service',$eventKey=null,$ttl=345600)use($uid,$key,$device){
  $eventKey=$eventKey??$key.':timing:'.$suffix;
  fcc_partner_notify($uid,$eventKey,$category,'PRIVATE NAME','PRIVATE CONTENT','partner', $ttl);
  $id=(int)fcc_partner_one('SELECT id FROM fcc_partner_notifications WHERE user_id=? AND event_key=?',[$uid,$eventKey])['id'];
  fcc_partner_query('INSERT INTO fcc_partner_push_deliveries (notification_id,subscription_id,available_at,updated_at) VALUES (?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$id,$device]);return $id;
 };
 $event=$add('after-five');$lastPayload=null;
 $record=function($d)use(&$calls,&$lastPayload){$calls++;$lastPayload=$d['payload'];return 'sent';};
 fcc_partner_push_deliver($noon->modify('+1 minute'),$record);
 $pending=fcc_partner_one('SELECT status,available_at FROM fcc_partner_push_deliveries WHERE notification_id=?',[$event]);
 $assert($calls===1&&$pending['status']==='pending'&&strtotime($pending['available_at'].' UTC')===$noon->modify('+2 minutes')->getTimestamp(),'Burst cooldown is two minutes, never tomorrow at eight');
 fcc_partner_push_deliver($noon->modify('+2 minutes'),$record);
 $assert($calls===2&&fcc_partner_one('SELECT status FROM fcc_partner_push_deliveries WHERE notification_id=?',[$event])['status']==='sent','Sixth daily event still arrives during the day');
 $assert($lastPayload['kind']==='service'&&!str_contains(json_encode($lastPayload),'PRIVATE'),'Payload contains a public type, never stored private text');
 $assert($lastPayload['sound']===true,'Existing accounts default to normal device sound');
 $test=$add('test','service','push-test:'.$key);
 fcc_partner_push_deliver($noon->modify('+2 minutes'),$record);
 $assert($calls===3&&$lastPayload['kind']==='test','Explicit device test bypasses ordinary cooldown');
 $item=['subscription_id'=>$device,'event_key'=>'webinar:calendar:999:1:reminder60','category'=>'webinar','expires_at'=>$noon->modify('+1 hour')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')];
 $assert(fcc_push_batch_due([$item],$noon->modify('+2 minutes'))==$noon->modify('+2 minutes'),'Scheduled event reminder bypasses ordinary cooldown');
 $item['event_key']='daily:'.$noon->format('Y-m-d');$item['category']='daily';
 $assert(fcc_push_batch_due([$item],$noon->modify('+2 minutes'))==$noon->modify('+2 minutes'),'Daily reminder keeps its scheduled time');
 $item['event_key']='cc:qa';$item['category']='cc';
 $assert(fcc_push_batch_due([$item],$noon->modify('+2 minutes'))==$noon->modify('+2 minutes'),'CC summary keeps its chosen hour');
 $p=fcc_partner_notification_preferences($uid);$input=['push'=>1,'categories'=>array_keys(fcc_partner_notification_rules()),'sound_version'=>1];
 fcc_cc_preferences_save($uid,$input);
 $assert(fcc_partner_notification_preferences($uid)['sound']===false,'Sound can be disabled separately from push');
 unset($input['sound_version']);fcc_cc_preferences_save($uid,$input);
 $assert(fcc_partner_notification_preferences($uid)['sound']===false,'Older settings forms preserve the saved sound preference');
 $event=$add('muted','service','push-test:muted:'.$key);fcc_partner_push_deliver($noon->modify('+3 minutes'),$record);
 $assert($lastPayload['sound']===false,'Silent preference is carried to the actual transport payload');
 // Ten night events become one morning request, all inbox items retained.
 for($i=0;$i<10;$i++)$add('night'.$i);
 $beforeCalls=$calls;fcc_partner_push_deliver($noon->setTime(23,0),$record);
 $assert($calls===$beforeCalls,'Night backlog emits no pushes');
 fcc_partner_push_deliver($noon->modify('+1 day')->setTime(8,0),$record);
 // Extend fixture lifetime for the explicit overnight clock before the next test.
 $assert($calls===$beforeCalls+1&&$lastPayload['kind']==='summary'&&$lastPayload['counts']['service']===10,'Ten overnight items arrive as one morning summary');
 $assert((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_push_deliveries WHERE subscription_id=? AND status='pending'",[$device])['n']===0,'Morning summary leaves no duplicate backlog');
 foreach(['business:lead:1'=>'contact_lead','business:daily:2026-10-01'=>'followup','webinar:registration:1'=>'guest_registration','onboarding:team-group:v1'=>'team_group'] as $keyName=>$kind)$assert(fcc_push_kind(['event_key'=>$keyName,'category'=>'service'])===$kind,'Contact and onboarding updates have a correct public label: '.$kind);

} finally {database()->rollback();fcc_partner_query("SELECT RELEASE_LOCK('fcc_partner_notifications')");cache()->deleteItemsByTag('user_id='.$uid);}
echo json_encode(['status'=>'PASS','checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
