<?php
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_journey_review.php';

function fcc_journey_warm(array $task): bool { return (int)($task['content_version']??0)===8; }

/** The message update also runs before the separate language rollout. */
function fcc_journey_warm_label(string $text): string { return function_exists('fcc_t')?fcc_t($text):$text; }

/** Apply copy improvements only to an unfinished step, preserving its chosen situation. */
function fcc_journey_warm_refresh(array $saved,array $published,int $uid=0,?DateTimeImmutable $now=null): array {
 if(($saved['id']??'')!==($published['id']??''))return $saved;
 if(($saved['review_revision']??'')!==($published['review_revision']??'')){
  $next=$published;
  if($uid)$next=fcc_journey_warm_context($uid,$published,$now);
  if(($saved['delivery_mode']??'')==='practice'){foreach(['actions','steps','completion','communication','tool','ui','delivery_mode','variant'] as $key)if(isset($saved[$key]))$next[$key]=$saved[$key];}
  return array_replace($saved,$next);
 }
 // A calendar or plan change must not leave an unfinished invitation pointing at a missing tool.
 if($uid&&($saved['delivery_mode']??'')!=='practice'&&in_array($published['tool'],['webinar','business','coach'],true))return array_replace($saved,fcc_journey_warm_context($uid,$published,$now));
 if((int)$published['day']===2){
  $saved['actions'][0]=$saved['steps'][0]=$saved['variant']['steps'][0]=$published['actions'][0];
  $saved['beginner']=$published['beginner'];$saved['practice']=$published['practice'];
 }
 if(($saved['delivery_mode']??'applied')!=='practice'&&empty($saved['communication']['example'])&&!empty($published['communication']['example'])){
  $saved['communication']=$published['communication'];$saved['message_example']=$published['message_example'];
 }
 return $saved;
}

/** The destination is resolved from current relationships, never from a posted phone number. */
function fcc_journey_warm_recipient(int $uid,array $task,int $contactId=0): array {
 $mode=$task['ui']['mode']??'';
 $sponsor=($task['recipient']??'')==='sponsor'||($task['delivery_mode']??'')==='practice'||in_array((int)($task['day']??0),[1,2,15,24,30,45,89,90],true);
 $kind=$sponsor?'sponsor':(in_array($mode,['message','followup'],true)?'contact':'none');
 $target=null;
 if($sponsor)$target=fcc_jp_mentor($uid);
 elseif($kind==='contact'&&$contactId){$target=fcc_partner_contact($uid,$contactId);if($target['contact_permission']==='do_not_contact')$target=null;}
 $phone=fcc_partner_contact_phone((string)($target['phone']??''),true);
 return ['kind'=>$kind,'phone'=>ltrim($phone,'+'),'name'=>$target['display_name']??$target['name']??'',
  'label'=>fcc_t($phone&&$sponsor?'Otvori WhatsApp sponzoru':'Otvori WhatsApp'),
  'hint'=>fcc_t($sponsor?($phone?'Poruka se otvara tvojem sponzoru.':'U WhatsAppu odaberi svojeg sponzora.'):'Odaberi kontakt ovdje ili primatelja u WhatsAppu.')];
}

/** Saving before navigation keeps the user's exact preparation available to Coach. */
function fcc_journey_warm_coach_draft(int $uid,array $input): void {
 if(!fcc_pro_has_access($uid))throw new InvalidArgumentException(fcc_t('Prilagodba poruke uz Coacha dostupna je uz aktivni PRO.'));
 fcc_jp_mutate($uid,'draft',$input);
}

/** Choose a real, owned situation, or a useful rehearsal before the step is opened. */
function fcc_journey_warm_context(int $uid,array $task,?DateTimeImmutable $now=null): array {
 $now=fcc_j90_now($now);$requirement=$task['context_requirement']??'';$practice=false;
 if($requirement==='customer')$practice=!fcc_partner_one("SELECT id FROM fcc_partner_contacts WHERE user_id=? AND sales_stage='customer' AND contact_permission<>'do_not_contact' LIMIT 1",[$uid]);
 /* Custom code: FC-2026-10-12: any sponsor with a direct member works with the real team; PRO is no longer required. */
 if($requirement==='team')$practice=function_exists('fcc_mp_has_team')?!fcc_mp_has_team($uid):(!fcc_pro_has_access($uid)||!fcc_team_ready()||!fcc_team_visible_members($uid,fcc_team_graph()));
 /* /Custom code: FC-2026-10-12 */
 if($requirement==='guest')$practice=!fcc_partner_one("SELECT r.id FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id WHERE r.user_id=? AND r.status='registered' AND r.is_demo=0 AND r.is_self=0 AND r.needs_review=0 AND s.status='scheduled' AND s.ends_at_utc>? LIMIT 1",[$uid,$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')]);
 if($requirement==='event')$practice=!fcc_partner_one("SELECT id FROM fcc_webinar_sessions WHERE owner_user_id=? AND status='scheduled' AND starts_at_utc<=? AND ends_at_utc>=? LIMIT 1",[$uid,$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),$now->modify('-7 days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s')]);
 if($practice){
  $task['actions']=$task['steps']=$task['beginner']['actions'];$task['completion']=$task['beginner']['completion'];
  $task['delivery_mode']='practice';$task['ui']['mode']='mentor';$task['ui']['done']=['practice'=>'Uvježbano sa sponzorom'];
  $task['communication']=['example'=>'Bok! Danas želim uvježbati: '.mb_strtolower(mb_substr($task['title'],0,1)).mb_substr($task['title'],1).'. Kada možemo kratko proći jedan primjer?','composer'=>['kind'=>'text','template'=>'']];
  $task['communication']['composer']['template']=$task['communication']['example'];$task['tool']='mentor';
 }else $task['delivery_mode']='applied';
 /* Custom code: FC-2026-10-12: with the Team invitation open, every member shares the business presentation. */
 if($task['tool']==='business'&&!fcc_business_pro($uid)&&!(function_exists('fcc_business_landing_access')&&fcc_business_landing_access($uid))){
 /* /Custom code: FC-2026-10-12 */
  $task['tool']='webinar';$task['actions']=array_map(static fn($s)=>str_ireplace(['marketing plana','marketing plan'],['predstavljanja','standardni poziv na predstavljanje'],$s),$task['actions']);
 }
 if($task['tool']==='webinar'&&!fcc_journey_warm_event($uid,$now)){
  $first=stripos($task['actions'][0],'webinar')!==false?'Odaberi osobe kojima želiš osobno predstaviti Forever posao.':$task['actions'][0];
  $task['actions']=[$first,'Pripremi kratak osobni poziv na razgovor o Forever poslu.','Dogovori kada ćete se čuti i spremi podsjetnik u Kontaktima.'];
  $task['completion']='Pripremio/la si osobne pozive i znaš s kim želiš nastaviti razgovor.';
  $task['tool']='contacts';$task['invite_message']=true;
  $task['communication']['example']=$task['communication']['composer']['template']='Hej! Ako te zanima kako izgleda posao s Foreverom, možemo kratko razgovarati. Bi li ti to odgovaralo?';
 }
 if($task['tool']==='coach'&&!fcc_pro_has_access($uid)){
  $task['tool']='mentor';$task['recipient']='sponsor';
 }
 // A past conversation does not establish attendance. Ask first, at the actual next conversation.
 if(in_array((int)$task['day'],[14,72],true)){
  $task['actions'][0]='Javi se osobi kojoj si poslao/la poziv ili predstavljanje i pitaj je li stigla pogledati.';
  $task['actions'][1]='Saslušaj što joj je bilo zanimljivo ili dogovorite kada joj odgovara pogledati.';
  $task['actions'][2]='Odgovori na jedno pitanje i spremi dogovor za sljedeće javljanje u Kontaktima.';
  if(empty($task['review_revision']))$task['communication']['example']=$task['communication']['composer']['template']='Bok! Jesi li stigao pogledati predstavljanje? Rado ću s tobom proći ono što te zanima. Kada ti odgovara da se kratko čujemo?';
 }
 $task['variant']['steps']=$task['actions'];$task['variant']['criterion']=$task['completion'];
 $task['variant']['delivery_mode']=$task['delivery_mode'];
 return $task;
}

/** Current calendar and referral sources only; never persist or create an invite during a read. */
function fcc_journey_warm_event(int $uid,?DateTimeImmutable $now=null): ?array {
 if(!fcc_events_enabled())return null;
 foreach(fcc_event_list($uid,$now,true) as $event){
  $d=fcc_event_definition($event);
  if(empty($d['guests'])||($d['kind']??'')!=='business')continue;
  return $event;
 }
 return null;
}
function fcc_journey_warm_tools(int $uid,array $task,int $step=0): array {
 $tool=$task['tool']??'';$pro=fcc_pro_has_access($uid);$event=null;
 $map=[
 'goal'=>['Odaberi cilj i podsjetnik','partner?view=goal'],
 'today'=>['Otvori svoj dnevni pregled','partner'],
 'mentor'=>['Poveži se sa sponzorom','partner?view=materials#mentor'],
 'education'=>['Pronađi svoju edukaciju','partner?view=materials#business-learning'],
 'coach'=>[$pro?'Otvori svojeg Coacha':'Prođi temu sa sponzorom',$pro?'partner?view=coach':'partner?view=materials#mentor'],
 /* Custom code: FC-2026-10-12 */ 'team'=>$pro?['Otvori svoj tim','partner/team']:(function_exists('fcc_mp_has_team')&&fcc_mp_has_team($uid)?['Pogledaj napredak svojih suradnika','partner?view=team-progress']:['Prođi primjer sa sponzorom','partner?view=materials#mentor']), /* /Custom code: FC-2026-10-12 */
 'registrations'=>['Pronađi svojeg gosta','partner-webinar?view=activity'],
 'events'=>[$pro?'Pripremi svoj susret':'Dogovori susret sa sponzorom',$pro?'partner-webinar?view=events':'partner?view=materials#mentor'],
 'webinar'=>['Pripremi osobni poziv','partner/share?type=webinar'],
 ];
 if($tool==='webinar'){
  $event=fcc_journey_warm_event($uid);
  if($event)$map['webinar'][1].='&session_id='.(int)$event['id'];
  else $map['webinar']=['Pripremi predstavljanje sa sponzorom','partner?view=materials#mentor'];
 }
 if(isset($map[$tool]))return [[$map[$tool][0],SITE_URL.$map[$tool][1],false]];
 return [];
}

/** A saved draft takes precedence. Personal event dates are composed in their own owner-checked tool. */
function fcc_journey_warm_text(int $uid,array $task): array {
 $message=fcc_t($task['communication']['example']??'');$link='';
 $cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));
 if(in_array((int)$task['day'],[2,5,37,65,80],true)&&$cards)$link=$cards[0]['public_url'];
 if(($task['tool']??'')==='webinar'){
  $event=fcc_journey_warm_event($uid);
  if($event&&$cards&&fcc_webinar_standard_context((int)$event['id'],$cards[0]['url'])){
   $link=SITE_URL.'webinar-event/'.(int)$event['id'].'?'.http_build_query(['ref'=>$cards[0]['url']]);
   $message=strtr($message,['{event}'=>$event['title'].' '.fcc_webinar_label_time($event,fcc_locale()),'{link}'=>$link]);
  }else $message=fcc_t('Hej! Ako te zanima kako izgleda posao s Foreverom, možemo kratko razgovarati. Bi li ti to odgovaralo?');
 }
 if(fcc_locale()==='en'&&preg_match('/^Bok! Danas želim uvježbati: (.*)\. Kada možemo kratko proći jedan primjer\?$/us',$message,$m)){$topic=mb_strtoupper(mb_substr($m[1],0,1)).mb_substr($m[1],1);$message='Hi! Today I would like to practise: '.fcc_t($topic).'. When could we go through a quick example together?';}
 if(fcc_new_locale()&&preg_match('/^Bok! Danas želim uvježbati: (.*)\. Kada možemo kratko proći jedan primjer\?$/us',$message,$m)){$topic=mb_strtoupper(mb_substr($m[1],0,1)).mb_substr($m[1],1);$message=fcc_t('Bok! Danas želim uvježbati: {topic}. Kada možemo kratko proći jedan primjer?',['{topic}'=>fcc_t($topic)]);}
 if(fcc_locale()==='sr'&&preg_match('/^Bok! Danas želim uvježbati: (.*)\. Kada možemo kratko proći jedan primjer\?$/us',$message,$m)){$topic=mb_strtoupper(mb_substr($m[1],0,1)).mb_substr($m[1],1);$message='Zdravo! Danas želim da uvežbam: '.fcc_t($topic).'. Kada možemo kratko da prođemo jedan primer?';}
 if(fcc_locale()==='de'&&preg_match('/^Bok! Danas želim uvježbati: (.*)\. Kada možemo kratko proći jedan primjer\?$/us',$message,$m)){$topic=mb_strtoupper(mb_substr($m[1],0,1)).mb_substr($m[1],1);$message='Hallo! Heute möchte ich Folgendes üben: '.fcc_t($topic).'. Wann können wir gemeinsam ein kurzes Beispiel durchgehen?';}
 if(fcc_locale()==='sl'&&preg_match('/^Bok! Danas želim uvježbati: (.*)\. Kada možemo kratko proći jedan primjer\?$/us',$message,$m)){$topic=mb_strtoupper(mb_substr($m[1],0,1)).mb_substr($m[1],1);$message='Živjo! Danes želim vaditi: '.fcc_t($topic).'. Kdaj lahko skupaj na kratko pregledava en primer?';}
 $message=str_replace(['[kartica]','[ime]'],[$link,''], $message);
 $message=str_replace('Bok !','Bok!', $message);
 if($link&&$message!==''&&!str_contains($message,$link))$message.="\n\n".$link;
 return ['message'=>$message,'article_id'=>0,'referral_url'=>$link,'title'=>'','image'=>''];
}
