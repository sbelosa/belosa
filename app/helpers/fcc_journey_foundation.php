<?php
defined('ALTUMCODE') || die();

function fcc_journey_team_group_url(): string {
 return 'https://chat.whatsapp.com/DE0VgNFj4Nm4LMQFdwGgzw?mode=gi_t';
}
function fcc_journey_team_group_joined(int $uid): bool {
 return $uid>0 && fcc_jp_schema() && (bool)fcc_partner_one("SELECT id FROM fcc_partner_pilot_events WHERE user_id=? AND kind='team_group_joined' LIMIT 1",[$uid]);
}
/** Membership is a member's explicit statement, never inferred from opening WhatsApp. */
function fcc_journey_team_group_confirm(int $uid,array $in): void {
 if(!fcc_partner_enabled($uid)||!fcc_jp_schema())throw new InvalidArgumentException(fcc_t('Timska priprema sada nije dostupna.'));
 if(($in['confirmed']??'')!=='1')throw new InvalidArgumentException(fcc_t('Potvrdi članstvo tek nakon pridruživanja grupi.'));
 $key=(string)($in['request_key']??'');if(!preg_match('/^[a-f0-9]{32}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi obrazac i pokušaj ponovno.'));
 database()->begin_transaction();
 try {
  if(!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? AND status=1 FOR UPDATE',[$uid]))throw new InvalidArgumentException(fcc_t('Račun nije aktivan.'));
  if(!fcc_journey_team_group_joined($uid))fcc_jp_event($uid,0,'team_group_joined','Korisnik je potvrdio članstvo u Team Extreme Suradnici.',$key,get_date());
  fcc_partner_query("UPDATE fcc_partner_notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE user_id=? AND event_key='onboarding:team-group:v1'",[$uid]);
  database()->commit();
 }catch(Throwable $e){database()->rollback();throw $e;}
}
/** Team membership is encouraged without blocking education while approval is pending. */
function fcc_journey_require_team_group(int $uid): void {}
function fcc_journey_team_group_dismissed(int $uid): bool {
 return (bool)fcc_partner_one("SELECT id FROM fcc_partner_pilot_events WHERE user_id=? AND kind='team_group_dismissed' LIMIT 1",[$uid]);
}
function fcc_journey_team_group_dismiss(int $uid,array $in): void {
 if(!fcc_partner_enabled($uid)||!fcc_jp_schema())throw new InvalidArgumentException(fcc_t('Osvježi stranicu i pokušaj ponovno.'));
 $key=(string)($in['request_key']??'');if(!preg_match('/^[a-f0-9]{32}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi stranicu i pokušaj ponovno.'));
 database()->begin_transaction();
 try {
  if(!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? AND status=1 FOR UPDATE',[$uid]))throw new InvalidArgumentException(fcc_t('Račun nije aktivan.'));
  if(!fcc_journey_team_group_dismissed($uid))fcc_jp_event($uid,0,'team_group_dismissed','Poziv u timsku grupu ostaje u Obavijestima.',$key,get_date());
  database()->commit();
 }catch(Throwable $e){database()->rollback();throw $e;}
}
function fcc_journey_team_welcome(int $uid): void {
 if(!fcc_partner_enabled($uid)||!fcc_jp_schema()||fcc_journey_team_group_joined($uid))return;
 fcc_partner_notify($uid,'onboarding:team-group:v1','service','Poveži se s timom','Pridruži se grupi Team Extreme Suradnici za najave, podršku i timske informacije.','partner/notifications?welcome=team#team-group',7*86400);
}
/** A changed task must not silently reuse a previous task's message or selected recipient. */
function fcc_journey_refresh_draft(?array $pinned,?array $task,array $draft): array {
 if(!$pinned||!$task||($pinned['id']??'')===($task['id']??'')||($draft['curriculum_task_id']??'')===($task['id']??''))return $draft;
 $previous=$draft;unset($previous['previous_task_draft']);
 return ['previous_task_draft'=>$previous,'curriculum_task_id'=>$task['id']];
}
function fcc_journey_task_tools(int $uid,array $task,int $step=0): array {
 $tool=$task['tool']??'';
 if(fcc_journey_warm($task)&&($links=fcc_journey_warm_tools($uid,$task,$step)))return $links;
 if($tool==='webinar')return [];
 $map=[
  'global'=>['Otvori My Profile na Foreverliving.com','https://foreverliving.com/account/hrv/en-gb/my-profile',true],
  'cards'=>['Otvori moje FCC kartice',SITE_URL.'partner/cards',false],
  'contacts'=>['Otvori moje Kontakte',SITE_URL.'partner/contacts',false],
  'products'=>['Pronađi proizvod i provjeri detalje',SITE_URL.'partner/share',false],
  'business'=>['Otvori Marketing plan',SITE_URL.'partner-marketing',false],
  'progress'=>['Pregledaj moj napredak',SITE_URL.'partner?view=progress',false],
 ];
 $links=isset($map[$tool])?[$map[$tool]]:[];
 if($tool==='business'&&$step&&fcc_business_pro($uid))$links=[['Otvori Marketing plan',SITE_URL.'partner-marketing?step_id='.$step,false]];
 if($tool==='business'&&!fcc_business_pro($uid))$links=[['Otvori standardnu pozivnicu za predstavljanje',SITE_URL.'partner/share?type=webinar',false]];
 return $links;
}
