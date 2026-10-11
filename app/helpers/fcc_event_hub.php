<?php
defined('ALTUMCODE') || die();
function fcc_event_hub_t(string $text,array $replace=[],?string $locale=null): string {
 static $copy; $copy??=json_decode(file_get_contents(APP_PATH.'config/event-hub-locales.json'),true);
 $value=$copy[$text][fcc_locale($locale)]??fcc_t($text,[],$locale);
 return $replace?strtr($value,$replace):$value;
}
/** The same classification serves calendars, standard invites, videos and guest history. */
function fcc_event_channel(array $s): string {
 $d=fcc_event_definition($s);
 return $d['mode']==='in_person'||(empty($s['owner_user_id'])&&in_array($d['kind'],['seminar','other'],true))?'events':'webinar';
}
function fcc_event_channel_list(array $events,string $channel): array {
 return array_values(array_filter($events,fn($s)=>fcc_event_channel($s)===$channel));
}
function fcc_event_channel_sql(string $channel): string {
 $json="IF(JSON_VALID(s.event_json),s.event_json,'{}')";
 $event="(COALESCE(JSON_UNQUOTE(JSON_EXTRACT($json,'$.mode')),'online')='in_person' OR (COALESCE(s.owner_user_id,0)=0 AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT($json,'$.kind')),'business') IN ('seminar','other')))";
 return $channel==='events'?$event:'NOT '.$event;
}
function fcc_event_share_path(array $s): string {return 'partner/share?type='.fcc_event_channel($s).'&session_id='.(int)$s['id'];}
function fcc_event_rsvp_ready(): bool {static $ready;return $ready??=(bool)fcc_partner_one("SELECT 1 n FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_event_member_rsvps'");}
function fcc_event_rsvp(int $uid,int $sid): ?array {return fcc_event_rsvp_ready()?fcc_partner_one('SELECT * FROM fcc_event_member_rsvps WHERE session_id=? AND user_id=?',[$sid,$uid]):null;}
function fcc_event_rsvp_open(array $s,?DateTimeImmutable $now=null): bool {
 return fcc_event_channel($s)==='events'&&$s['status']==='scheduled'&&strtotime($s['starts_at_utc'].' UTC')>fcc_event_now($now)->getTimestamp();
}
/** Personal attendance intent never creates a CRM lead or counts as a ticket purchase. */
function fcc_event_rsvp_save(int $uid,int $sid,int $revision,string $state,?DateTimeImmutable $now=null): void {
 if(!fcc_event_rsvp_ready()||!in_array($state,['going','cancelled'],true))throw new InvalidArgumentException(fcc_event_hub_t('Prijava trenutačno nije dostupna. Osvježi stranicu.'));
 fcc_event_transaction(function()use($uid,$sid,$revision,$state,$now){
  $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL FOR UPDATE',[$sid]);
  if(!$s||fcc_event_channel($s)!=='events'||!fcc_event_access($uid,$s,$now)['allowed'])throw new InvalidArgumentException(fcc_event_hub_t('Ovaj događaj nije dostupan tvojem računu.'));
  if((int)$s['revision']!==$revision)throw new InvalidArgumentException(fcc_t('Termin je promijenjen. Osvježi prikaz.'));
  if($state==='going'&&!fcc_event_rsvp_open($s,$now))throw new InvalidArgumentException(fcc_event_hub_t('Prijave za ovaj događaj su zatvorene.'));
  $old=fcc_event_rsvp($uid,$sid);if(($old['state']??'')===$state||(!$old&&$state==='cancelled'))return;
  fcc_partner_query('INSERT INTO fcc_event_member_rsvps(session_id,user_id,state,session_revision,created_at,updated_at) VALUES (?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE state=VALUES(state),session_revision=VALUES(session_revision),updated_at=UTC_TIMESTAMP()',[$sid,$uid,$state,$revision]);
  if($state==='going')fcc_event_reconcile_member($s,$uid,$now);
 });
}
