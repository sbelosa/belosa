<?php
defined('ALTUMCODE') || die();

/** Reuse an already authorized private preview; never start or extend public access. */
function fcc_webinar_standard_share_destination(string $url,?array $preview=null,?int $now=null): array {
 $original=['url'=>$url,'expires'=>0];
 if(!fcc_webinar_local()||!in_array(parse_url(SITE_URL,PHP_URL_HOST),['localhost','127.0.0.1'],true)||!str_starts_with($url,SITE_URL.'webinar-event/'))return $original;
 if($preview===null){
  $file=dirname(__DIR__,2).'/local/phone/access.json';
  $preview=is_readable($file)?(json_decode(file_get_contents($file),true)?:[]):[];
 }
 $origin=rtrim((string)($preview['origin']??''),'/');$expires=(int)($preview['expiresAt']??0);
 if($expires<=($now??time())*1000||!preg_match('~^https://[a-z0-9-]+\.trycloudflare\.com$~D',$origin))return $original;
 return ['url'=>$origin.'/'.substr($url,strlen(SITE_URL)),'expires'=>$expires];
}

function fcc_webinar_standard_enabled(int $uid,bool $published=false): bool {
 static $ready;
 if(!fcc_events_enabled()||getenv('FCC_WEBINAR_INVITES_ENABLED')==='0'||(!fcc_webinar_local()&&getenv('FCC_WEBINAR_INVITES_ENABLED')!=='1'))return false;
 $ready??=!!fcc_partner_one("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_webinar_invite_attempts' AND COLUMN_NAME='invite_kind'");
 return $ready&&($published?fcc_rollout_public_owner($uid):fcc_partner_enabled($uid))&&(int)(fcc_partner_one('SELECT status FROM users WHERE user_id=?',[$uid])['status']??0)===1;
}
function fcc_webinar_standard_key(int $uid,int $cardId,int $sessionId): string {
 return substr(fcc_webinar_hash('standard:'.$uid.':'.$cardId.':'.$sessionId),0,32);
}
/** Resolve the legacy calendar link without creating data during previews or crawler GETs. */
function fcc_webinar_standard_context(int $sessionId,string $ref): ?array {
 if(!fcc_events_enabled())return null;
 $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL',[$sessionId]);
 $card=fcc_partner_one("SELECT l.link_id,l.user_id,u.name FROM links l JOIN users u ON u.user_id=l.user_id WHERE l.url=? AND l.type='biolink' AND l.is_enabled=1 AND u.status=1 LIMIT 1",[$ref]);
 if(!$s||!$card||!empty($s['owner_user_id'])||$s['status']==='draft')return null;
 $uid=(int)$card['user_id'];$d=fcc_event_definition($s);
 if(!fcc_webinar_standard_enabled($uid,true)||!$d['guests']||!fcc_event_access($uid,$s)['allowed'])return null;
 $key=fcc_webinar_standard_key($uid,(int)$card['link_id'],$sessionId);
 $old=fcc_partner_one("SELECT * FROM fcc_webinar_invite_attempts WHERE user_id=? AND client_request_key=? AND invite_kind='standard'",[$uid,$key]);
 return array_merge($s,[
  'id'=>(int)($old['id']??0),'user_id'=>$uid,'card_id'=>(int)$card['link_id'],'session_id'=>$sessionId,
  'invite_kind'=>'standard','revoked_at'=>$old['revoked_at']??null,'owner_name'=>$card['name'],
  'session_status'=>$s['status'],'video_snapshot'=>fcc_webinar_json(fcc_webinar_video('none')),
  'intro_snapshot'=>'','is_demo'=>fcc_webinar_local()?1:0,
 ]);
}
/** One attribution record per member/card/occurrence, created only on a validated form POST. */
function fcc_webinar_standard_prepare(int $sessionId,string $ref,int $revision): array {
 $a=fcc_webinar_standard_context($sessionId,$ref);
 if(!$a)throw new InvalidArgumentException(fcc_t('Pozivnica trenutačno ne prima prijave.'));
 $uid=(int)$a['user_id'];
 return fcc_webinar_atomic($uid,function()use($uid,$sessionId,$ref,$revision){
  fcc_partner_one('SELECT id FROM fcc_webinar_sessions WHERE id=? FOR UPDATE',[$sessionId]);
  $a=fcc_webinar_standard_context($sessionId,$ref);
  if(!$a||(int)$a['user_id']!==$uid||!fcc_webinar_can_register($a))throw new InvalidArgumentException(fcc_t('Termin više ne prima prijave.'));
  if((int)$a['revision']!==$revision)throw new InvalidArgumentException(fcc_t('Termin je promijenjen. Osvježi pozivnicu i potvrdi novi datum prije prijave.'));
  if($a['id'])return fcc_webinar_attempt($uid,(int)$a['id']);
  $key=fcc_webinar_standard_key($uid,(int)$a['card_id'],$sessionId);$locale=fcc_mw_locale($a,'hr');
  // The video configuration belongs to the PRO editor. Never overwrite it for a standard registration.
  fcc_partner_query("INSERT IGNORE INTO fcc_webinar_invitations (user_id,card_id,video_json,short_intro,locale,created_at,updated_at) VALUES (?,?,?,'',?,UTC_TIMESTAMP(),UTC_TIMESTAMP())",[$uid,(int)$a['card_id'],$a['video_snapshot'],$locale]);
  $config=fcc_partner_one('SELECT id FROM fcc_webinar_invitations WHERE user_id=? AND card_id=?',[$uid,(int)$a['card_id']]);
  $token=fcc_webinar_token();
  fcc_partner_query("INSERT INTO fcc_webinar_invite_attempts (user_id,invitation_id,card_id,session_id,token_hash,token_sealed,client_request_key,payload_hash,video_snapshot,intro_snapshot,locale,is_demo,prepared_at,invite_kind) VALUES (?,?,?,?,?,?,?,?,?,'',?,?,UTC_TIMESTAMP(),'standard')",[$uid,(int)$config['id'],(int)$a['card_id'],$sessionId,hash('sha256',$token),fcc_webinar_seal($token),$key,hash('sha256',$key),$a['video_snapshot'],$locale,(int)$a['is_demo']]);
  return fcc_webinar_attempt($uid,(int)database()->insert_id);
 });
}
