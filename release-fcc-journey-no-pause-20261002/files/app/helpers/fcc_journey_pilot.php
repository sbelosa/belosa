<?php
/** Shared web/PWA pilot. Existing cycles, contacts and daily outcomes stay authoritative. */
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_webinar_growth.php';
require_once __DIR__.'/fcc_journey_foundation.php';
require_once __DIR__.'/fcc_journey_warm.php';
const FCC_JP_VERSION='fcc90-2026-09-29-v8';
const FCC_JP_V7_VERSION='fcc90-2026-09-27-v7';
const FCC_JP_PREVIOUS_VERSION='fcc90-2026-09-25-v6';
const FCC_JP_LEGACY_VERSION='fcc90-2026-09-24-v5-pilot15';
const FCC_JP_TOTAL=90;
function fcc_jp_catalog(): array {
 static $c;
 if(!$c){$c=json_decode(file_get_contents(__DIR__.'/../config/curriculum/fcc90.hr.v8.json'),true,512,JSON_THROW_ON_ERROR);if($c['schema_version']!==3||$c['catalog_version']!==FCC_JP_VERSION||count($c['tasks'])!==FCC_JP_TOTAL)throw new RuntimeException(fcc_t('Invalid journey catalog'));}
 return fcc_journey_review_catalog($c);
}
/** Presentation can evolve without replacing pinned task content or progress. */
function fcc_jp_presentation(int $day): array {
 $task=fcc_jp_content($day)['ui']??null;
 if(!$task||array_diff(array_keys($task['done']),fcc_jp_allowed_evidence($day)))throw new RuntimeException(fcc_t('Invalid journey presentation'));
 return $task;
}
function fcc_jp_schema(): bool {
 static $ready;return $ready??=!!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_partner_pilot_events'");
}
function fcc_jp_enabled(int $uid): bool {
 return fcc_j90_enabled($uid)&&fcc_jp_schema()&&(getenv('FCC_JOURNEY_V5_ENABLED')==='1'||(getenv('FCC_LOCAL')==='1'&&getenv('FCC_JOURNEY_V5_ENABLED')!=='0'));
}
function fcc_jp_is_cycle(?array $cycle): bool {return in_array($cycle['catalog_version']??'',[FCC_JP_VERSION,FCC_JP_V7_VERSION,FCC_JP_PREVIOUS_VERSION,FCC_JP_LEGACY_VERSION],true);}
function fcc_jp_fast(int $uid): bool {
 if(getenv('FCC_LOCAL')!=='1'||DATABASE_NAME!=='fcc_partner_local')return false;
 $r=fcc_partner_one('SELECT email,type,preferences FROM users WHERE user_id=?',[$uid]);$p=json_decode($r['preferences']??'{}',true);
 $local_admin=$uid===3&&($r['email']??'')==='admin@fcc.test'&&(int)($r['type']??0)===1;
 return $local_admin || (!!($p['partner_v5_test']['fast']??false)&&preg_match('/^pilot15(?:-free|-qa-[a-f0-9]{8})?@fcc\.test$/D',$r['email']??''));
}
function fcc_jp_content(int $day): array {
 if($day<1||$day>FCC_JP_TOTAL)throw new InvalidArgumentException(fcc_t('Program sadrži 90 koraka.'));
 $c=fcc_jp_catalog();$t=$c['tasks'][$day-1];$t['catalog_version']=FCC_JP_VERSION;$t['policy_version']=$c['policy']['id'];
 $t['variant']=['level'=>1,'steps'=>$t['actions'],'criterion'=>$t['completion'],'allowed_output_statuses'=>['sent','performed','published'],'external_event_eligible'=>true,'advanced_case_eligible'=>false];
 return $t;
}
function fcc_jp_state(int $uid,array $access,?DateTimeImmutable $now=null): array {
 $now=fcc_j90_now($now);$cycle=fcc_j90_cycle($uid);$rows=fcc_j90_rows($uid,(int)$cycle['id']);$position=count($rows)+1;
 $paused=false; // Legacy pause dates no longer affect task availability.
 $fast=fcc_jp_fast($uid);$done=!$fast&&fcc_j90_daily_done($uid,$now->format('Y-m-d'));$webinar=fcc_j90_webinar($now);$webinar['is_today']=false; // Calendar events supplement the warm daily task, never replace it.
 $step=$position<=FCC_JP_TOTAL?fcc_partner_one('SELECT * FROM fcc_partner_journey_steps WHERE user_id=? AND cycle_id=? AND position=?',[$uid,(int)$cycle['id'],$position]):null;
 // Refresh only unfinished content. Completed snapshots and the owner's draft stay intact.
 $t=$position<=FCC_JP_TOTAL?fcc_jp_content($position):null;
 if($t&&fcc_journey_warm($t)){
  $saved=$step?json_decode($step['content_json'],true):null;
  $t=($saved['id']??'')===$t['id']?fcc_journey_warm_refresh($saved,$t,$uid,$now):fcc_journey_warm_context($uid,$t,$now);
 }
 if($step&&$t){
  $pinned=json_decode($step['content_json'],true);
  if(isset($pinned['material'])&&($pinned['id']??'')===($t['id']??'')){
   $t['material']=$pinned['material'];
   if(str_starts_with($t['material']['version']??'','base-'))$t['material']=array_merge($t['material'],['version'=>'base-'.FCC_JP_VERSION,'summary'=>implode(' ',$t['actions']),'example'=>$t['communication']['example']]);
  }
 }
 $draft=json_decode($step['draft_json']??'{}',true)?:[];
 $draft=fcc_journey_refresh_draft($step?json_decode($step['content_json'],true):null,$t,$draft);
 $enabled=fcc_jp_enabled($uid);$action=$t?['key'=>$t['id'],'core'=>$t['core'],'title'=>$t['title'],'instruction'=>implode(' ',$t['actions']),'success_definition'=>$t['completion'],'fallback'=>$t['alternative'],'message_example'=>$t['communication']['example'],'sequence_position'=>$position,'sequence_total'=>FCC_JP_TOTAL,'target'=>1,'quick_target'=>1,'track_label'=>'Moj put · 4 Core','can_complete'=>$enabled&&!$done&&!empty($access['can_access_education'])&&!!$step&&!$webinar['is_today'],'is_daily_complete'=>$done,'is_program_complete'=>false]:['key'=>'journey90_finished','title'=>'Tvojih 90 koraka je završeno','can_complete'=>false,'is_program_complete'=>true];
 if($t&&$webinar['is_today']&&!$done)$action=array_merge($action,['key'=>'vip26_sunday_'.$now->format('Ymd'),'title'=>'Nedjeljni webinar u '.$webinar['time_label'],'is_weekly_plan'=>true,'can_complete'=>$enabled&&!empty($access['can_access_education'])&&$webinar['can_record_outcome'],'is_waiting_for_event_completion'=>!$webinar['can_record_outcome']]);
 return ['pilot'=>true,'cycle'=>$cycle,'rows'=>$rows,'position'=>$position,'completed'=>count($rows),'published_total'=>FCC_JP_TOTAL,'step'=>$step,'content'=>$t,'draft'=>$draft,'enabled'=>$enabled,'paused'=>$paused,'daily_done'=>$done,'webinar'=>$webinar,'action'=>$action,'fast'=>$fast,'pilot_finished'=>$position>FCC_JP_TOTAL,'readiness'=>['proposed_level'=>null],
 'due'=>fcc_partner_rows("SELECT id,name,next_followup FROM fcc_partner_contacts WHERE user_id=? AND next_followup<=? AND contact_permission<>'do_not_contact' ORDER BY next_followup,id LIMIT 3",[$uid,$now->format('Y-m-d')]),
 'events'=>$step?fcc_partner_rows('SELECT * FROM fcc_partner_pilot_events WHERE user_id=? AND step_id=? ORDER BY id DESC LIMIT 20',[$uid,(int)$step['id']]):[],
 'mentor'=>fcc_jp_mentor($uid),'cc'=>fcc_j90_cc($uid,$access,$now),'capabilities'=>fcc_jp_capabilities(),'needs_help'=>false];
}
function fcc_jp_capabilities(): array {
 // This pilot deliberately has no order adapter. Never infer customers or SKUs from aggregate CC.
 return ['aggregate_cc'=>true,'order_headers'=>false,'order_lines'=>false,'buyer_identity'=>false,'delivery_status'=>false,'label'=>'CC podaci dostupni su zasebno. Narudžbe po proizvodu još nisu povezane; kupnju bilježimo samo kao tvoju informaciju.'];
}
function fcc_jp_mentor(int $uid): ?array {
 // Use the confirmed direct sponsor dynamically so reassignment revokes the old contact.
 if(function_exists('fcc_team_sponsor') && ($sponsor=fcc_team_sponsor($uid))) {
  return ['user_id'=>$uid,'mentor_user_id'=>$sponsor['user_id'],'display_name'=>$sponsor['name'],
   'phone'=>(int)$sponsor['user_id']===$uid?'':$sponsor['phone'],'contact_confirmed'=>!empty($sponsor['phone']),'source'=>'fcc_team','role'=>'sponsor'];
 }
 if(!fcc_jp_schema())return null;
 $row=fcc_partner_one('SELECT a.*,u.status mentor_status FROM fcc_partner_mentor_assignments a LEFT JOIN users u ON u.user_id=a.mentor_user_id WHERE a.user_id=?',[$uid]);
 if(!$row||($row['mentor_user_id']&&(int)$row['mentor_status']!==1)||!$row['contact_confirmed'])return null;
 return $row+['role'=>'mentor'];
}
function fcc_jp_opportunities(int $uid): array {
 return fcc_partner_rows('SELECT o.*,c.name,c.contact_permission,c.version contact_version FROM fcc_partner_opportunities o JOIN fcc_partner_contacts c ON c.id=o.contact_id AND c.user_id=o.user_id WHERE o.user_id=? ORDER BY o.updated_at DESC,o.id DESC LIMIT 60',[$uid]);
}
function fcc_jp_statuses(): array {return array_map('fcc_t',['open'=>'Otvoren razgovor','interested'=>'Zanima ga','needs_help'=>'Treba odgovor ili pomoć','later'=>'Dogovoreno javljanje','customer_reported'=>'Kupac navodi kupnju · nije potvrđeno uvozom','closed'=>'Zatvoreno / ne želi dalje']);}
function fcc_jp_allowed_evidence(int $day): array {
 return fcc_jp_content($day)['allowed_evidence'];
}
function fcc_jp_event_labels(): array {return array_map('fcc_t',['preparation_reported'=>'Pripremio/la sam sadržaj ili pozivnicu','message_sent'=>'Potvrdio sam slanje poruke','publication_reported'=>'Potvrdio sam objavu','conversation_reported'=>'Potvrdio sam razgovor ili pomoć','practice'=>'Odrađena praktična vježba','share_intent'=>'Otvoren alat za dijeljenje · nije potvrda slanja','asset_exported'=>'Slika pripremljena za preuzimanje','mentor_request_prepared'=>'Pripremljen upit mentoru · nije potvrda slanja']);}
function fcc_jp_choices(int $uid,array $t): array {
 $u=fcc_journey_user($uid);$cards=array_values(array_filter(fcc_partner_cards($uid),fn($x)=>(int)$x['is_enabled']===1));$ref=$cards[0]['url']??'';
 if(fcc_journey_warm($t)&&$t['communication']['composer']['kind']==='text'){
  $choice=fcc_journey_warm_text($uid,$t);$old=$t;$old['communication']['example']=$t['previous_example']??'';
  $choice['previous_message']=fcc_journey_warm_text($uid,$old)['message'];return [0=>$choice];
 }
 if($t['communication']['composer']['kind']==='text')return [0=>fcc_j90_source_message($t,null,'')+['title'=>'','image'=>'']];
 if(!$ref)return [];$choices=[];
 foreach(fcc_partner_rows("SELECT blog_post_id,title,url,image FROM blog_posts WHERE is_published=1 AND language=? AND TRIM(COALESCE(sku,''))<>'' ORDER BY title",[\Altum\Language::$name]) as $article){$choices[(int)$article['blog_post_id']]=fcc_j90_source_message($t,$article,$ref)+['title'=>html_entity_decode(strip_tags($article['title']),ENT_QUOTES,'UTF-8'),'image'=>$article['image']?\Altum\Uploads::get_full_url('blog').$article['image']:''];if(isset($t['previous_example'])){$old=$t;$old['communication']['composer']['template']=$t['previous_example'];$choices[(int)$article['blog_post_id']]['previous_message']=fcc_j90_source_message($old,$article,$ref)['message'];}}
 return $choices;
}
/** Empty or URL-only drafts from the old composer need the actual message, too. */
function fcc_jp_message_has_words(string $message): bool {
 return (bool)preg_match('/[\p{L}\p{N}]/u',preg_replace('~https?://[^\s<>]+~u','',$message));
}
/** Refresh a verbatim old default, never a user's custom draft. No database write on read. */
function fcc_jp_display_message(array $task,array $draft,array $source): string {
 $message=(string)($draft['message']??'');$next=(string)($source['message']??'');
 if(!fcc_jp_message_has_words($message))return $next;
 if(fcc_journey_warm($task)){
  if(isset($source['previous_message'])&&trim($message)===trim($source['previous_message']))return $next;
  if(!empty($task['previous_example'])&&trim($message)===trim(fcc_t($task['previous_example'])))return $next;
  return $message;
 }
 if(fcc_webinar_learning_task((int)$task['day'])){
  static $base,$baseUi;
  $base??=json_decode(file_get_contents(__DIR__.'/../config/curriculum/fcc90.hr.v6.json'),true);
  $baseUi??=json_decode(file_get_contents(__DIR__.'/../config/curriculum/journey90.presentation.json'),true);
  $previous=$base['tasks'][(int)$task['day']-1];
  $defaults=[$previous['communication']['example']??'',$previous['communication']['composer']['template']??'',$baseUi['tasks'][(string)$task['day']]['copy']??''];
  if(in_array(trim($message),array_map('trim',$defaults),true))return $next;
 }
 if((int)$task['day']>15)return $message;
 static $old,$oldUi;
 $old??=json_decode(file_get_contents(__DIR__.'/../config/curriculum/fcc90.hr.v5-pilot15.json'),true);
 $oldUi??=json_decode(file_get_contents(__DIR__.'/../config/curriculum/pilot15.presentation.json'),true);
 $oldTask=$old['tasks'][(int)$task['day']-1];
 $template=strtr($oldTask['communication']['composer']['template'],['{title}'=>$source['title']??'','{link}'=>$source['referral_url']??'']);
 $defaults=[$template,$oldUi['tasks'][(string)$task['day']]['copy']??''];
 if((int)$task['day']===12)$defaults[]='Danas malo bliže: '.($source['title']??'').'. U članku su namjena i način korištenja. Ako te zanima neki detalj, javi mi se.'."\n".($source['referral_url']??'');
 return in_array(trim($message),array_map('trim',$defaults),true)?$next:$message;
}
function fcc_jp_draft(int $uid,array $t,array $old,array $in): array {
 if(!array_key_exists('note',$in)&&array_key_exists('detail',$in))$in['note']=$in['detail'];
 $draft=$old;$alternative=fcc_partner_choice($in['path']??$draft['path']??'main',['main'=>1,'alternative'=>1]);
 $id=(int)($in['article_id']??$draft['article_id']??0);$choices=fcc_jp_choices($uid,$t);
 // An unfinished old task may retain a product selection after becoming webinar preparation.
 if(!empty($t['webinar_learning_version'])&&$t['communication']['composer']['kind']==='text')$id=0;
 if($id&&!isset($choices[$id]))throw new InvalidArgumentException(fcc_t('Odaberi dostupan objavljeni proizvod ili članak.'));
 $source=$choices[$id]??['message'=>'','article_id'=>0,'referral_url'=>'','title'=>'','image'=>''];
 $contact=(int)($in['contact_id']??$draft['contact_id']??0);
 if(in_array(($t['ui']['mode']??fcc_jp_presentation((int)$t['day'])['mode']),['setup','mentor','planning'],true))$contact=0;
 if(!empty($t['webinar_learning_version'])&&($t['ui']['mode']??fcc_jp_presentation((int)$t['day'])['mode'])==='planning')$contact=0;
 if($contact){$row=fcc_partner_contact($uid,$contact);if($row['contact_permission']==='do_not_contact')throw new InvalidArgumentException(fcc_t('Ta osoba ne želi javljanje. Odaberi drugu situaciju.'));}
 $message=fcc_partner_text($in['message']??$draft['message']??$source['message'],3000);
 if(!fcc_jp_message_has_words($message))$message=$source['message'];
 $message=fcc_j90_with_referral($message,$source['referral_url']);
 foreach(['headline'=>[90,$t['title']],'body'=>[420,''],'audience'=>[160,''],'need'=>[200,''],'note'=>[500,'']] as $key=>[$limit,$default])$draft[$key]=fcc_partner_text($in[$key]??$draft[$key]??$default,$limit);
 $draft=array_merge($draft,['path'=>$alternative,'article_id'=>$id,'contact_id'=>$contact,'message'=>$message,'referral_url'=>$source['referral_url'],'image'=>$source['image'],'product_title'=>$source['title'],'format'=>fcc_partner_choice($in['format']??$draft['format']??'story',['story'=>1,'post'=>1,'square'=>1]),'prepared_at'=>gmdate('c')]);
 return $draft;
}
function fcc_jp_event(int $uid,int $step,string $kind,string $detail,string $key,string $at,?int $opportunity=null,?int $activity=null): int {
 fcc_partner_query('INSERT INTO fcc_partner_pilot_events (user_id,step_id,opportunity_id,activity_id,kind,detail,request_key,created_at) VALUES (?,?,?,?,?,?,?,?)',[$uid,$step?:null,$opportunity,$activity,$kind,$detail,$key,$at]);return (int)database()->insert_id;
}
/** Called only inside the per-user mutation transaction. */
function fcc_jp_record_action(int $uid,array $step,array $t,array $draft,array $in,string $key,string $at): int {
     $kind=fcc_partner_choice($in['kind']??'',fcc_jp_event_labels());$detail=fcc_partner_text($in['detail']??'',500);
     if(empty($in['confirmed']))throw new InvalidArgumentException(fcc_t('Potvrdi što si stvarno napravio.'));
     if($kind==='practice'&&mb_strlen($detail)<15)throw new InvalidArgumentException(fcc_t('Dodaj kratak rezultat vježbe, npr. pripremljenu rečenicu.'));
     $contactId=(int)($draft['contact_id']??0);$contact=$contactId?fcc_partner_contact($uid,$contactId):null;
     $direct=in_array($kind,['message_sent','conversation_reported'],true);$real=in_array($kind,['message_sent','conversation_reported','publication_reported'],true);
     if($direct&&$contact&&$contact['contact_permission']==='do_not_contact')throw new InvalidArgumentException(fcc_t('Ova osoba ne želi javljanje.'));
     if($direct&&empty($in['appropriate']))throw new InvalidArgumentException(fcc_t('Potvrdi da je kontakt ili pomoć bila primjerena i stvarno odrađena.'));
     $opp=null;$activity=null;
     if($direct&&$contact){
      $old=fcc_partner_one('SELECT id FROM fcc_partner_opportunities WHERE user_id=? AND journey_step_id=? AND contact_id=?',[$uid,(int)$step['id'],$contactId]);
      if($old)$opp=(int)$old['id'];else {fcc_partner_query("INSERT INTO fcc_partner_opportunities (user_id,contact_id,journey_step_id,kind,article_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?)",[$uid,$contactId,(int)$step['id'],$t['core']==='Recruitment'?'business':'product',($draft['article_id']??0)?:null,$at,$at]);$opp=(int)database()->insert_id;}
     }
     if($real){fcc_partner_query('INSERT INTO fcc_partner_activities (user_id,contact_id,kind,note,request_key,created_at) VALUES (?,?,?,?,?,?)',[$uid,$direct?$contactId?:null:null,$kind==='message_sent'?'journey_sent':'journey_performed','Korisnička potvrda: '.$t['id'],$key,$at]);$activity=(int)database()->insert_id;}
     return fcc_jp_event($uid,(int)$step['id'],$kind,$detail,$key,$at,$opp,$activity);
}
function fcc_jp_complete_action(int $uid,array $s,array $cycle,array $step,array $t,array $access,array $in,string $day,string $at): int {
     if(!$s['action']['can_complete'])throw new InvalidArgumentException(fcc_t('Korak se sada ne može potvrditi.'));
     if(empty($in['self_check']))throw new InvalidArgumentException(fcc_t('Potvrdi kriterij ovog koraka.'));
     $event=fcc_partner_one('SELECT * FROM fcc_partner_pilot_events WHERE id=? AND user_id=? AND step_id=? FOR UPDATE',[(int)($in['evidence_id']??0),$uid,(int)$step['id']]);
     if(!$event||!in_array($event['kind'],['message_sent','publication_reported','conversation_reported','preparation_reported','practice'],true))throw new InvalidArgumentException(fcc_t('Prvo zabilježi izvršenu radnju ili konkretnu vježbu. Nacrt i otvaranje WhatsAppa nisu izvršenje.'));
     if(!in_array($event['kind'],fcc_jp_allowed_evidence($s['position']),true))throw new InvalidArgumentException(fcc_t('Ova vrsta radnje ne odgovara kriteriju današnjeg koraka.'));
     $mode=$event['kind']==='practice'?'practice':'applied';$output=match($event['kind']){'practice'=>'practised','message_sent'=>'sent','publication_reported'=>'published',default=>'performed'};
     if($event['activity_id']&&fcc_partner_one('SELECT outcome_id FROM forever_business_daily_outcomes WHERE interaction_id=?',[(int)$event['activity_id']]))throw new InvalidArgumentException(fcc_t('Ta radnja već je iskorištena.'));
     $resultType=match($event['kind']){'practice','preparation_reported'=>'training','publication_reported'=>'content',default=>$t['core']==='Recruitment'?'invitation':'recommendation'};
     fcc_partner_query("INSERT INTO forever_business_daily_outcomes (fbo_id,action_date,core_key,action_key,status,outcome_count,outcome_type,result_type,difficulty,sequence_position,note,recorded_by_user_id,completion_mode,journey_cycle_id,journey_step_id,learning_mode,output_status,interaction_id,created_at,updated_at) VALUES (?,?,?,?,'done',1,'journey90',?,'ok',?,?,?,'standard',?,?,?,?,?,?,?)",[$access['fbo_id'],$day,$t['core'],$t['id'],$resultType,$s['position'],$event['detail'],$uid,(int)$cycle['id'],(int)$step['id'],$mode,$output,$event['activity_id']?(int)$event['activity_id']:null,$at,$at]);$result=(int)database()->insert_id;
     fcc_partner_query('UPDATE fcc_partner_journey_steps SET content_json=? WHERE id=?',[json_encode($t,JSON_UNESCAPED_UNICODE),(int)$step['id']]);
     fcc_partner_query("UPDATE fcc_partner_journey_cycles SET version=version+1,status=?,completed_at=? WHERE id=?",[$s['position']===FCC_JP_TOTAL?'completed':'active',$s['position']===FCC_JP_TOTAL?$at:null,(int)$cycle['id']]);
     fcc_journey_event($uid,'step_completed','j90:'.$cycle['id'].':'.$step['id'],FCC_JP_VERSION,'pilot-done:'.$step['id']);
     return $result;
}
/** Same per-user lock, request ledger and transaction boundary as v4. */
function fcc_jp_mutate(int $uid,string $op,array $in,?DateTimeImmutable $now=null): int {
 if($now&&PHP_SAPI!=='cli')throw new InvalidArgumentException(fcc_t('Vrijeme određuje sustav.'));
 if(!fcc_jp_enabled($uid))throw new InvalidArgumentException(fcc_t('Pilot nije uključen. Povijest je sačuvana.'));
 if($op!=='opportunity')fcc_journey_require_team_group($uid);
 $access=fcc_j90_access($uid);$now=fcc_j90_now($now);$day=$now->format('Y-m-d');$at=$now->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
 $key=(string)($in['request_key']??'');if(!preg_match('/^[a-f0-9]{32}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi obrazac prije spremanja.'));
 $payload=$in;unset($payload['token'],$payload['request_key']);ksort($payload);$hash=hash('sha256',$op.json_encode($payload));
 database()->begin_transaction();
 try {
  if(!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? AND status=1 FOR UPDATE',[$uid]))throw new InvalidArgumentException(fcc_t('Račun nije aktivan.'));
  $saved=fcc_partner_one('SELECT * FROM fcc_partner_journey_requests WHERE user_id=? AND request_key=?',[$uid,$key]);
  if($saved){if($saved['operation']!=='pilot_'.$op||!hash_equals($hash,$saved['payload_hash']))throw new InvalidArgumentException(fcc_t('Zahtjev je već upotrijebljen s drugim sadržajem.'));database()->commit();return (int)$saved['result_id'];}
  if($op!=='opportunity'&&($in['catalog_version']??'')!==FCC_JP_VERSION)throw new InvalidArgumentException(fcc_t('Sadržaj programa je unaprijeđen. Osvježi stranicu prije spremanja koraka. Tvoj nacrt i napredak su sačuvani.'));
  $cycle=fcc_j90_cycle($uid);$result=0;
  if($op==='start') {
   if(empty($in['confirm']))throw new InvalidArgumentException(fcc_t('Potvrdi početak uz sačuvanu povijest.'));
   if(fcc_jp_is_cycle($cycle))throw new InvalidArgumentException(fcc_t('Pilot već postoji. Nastavi sa svojim korakom.'));
   if((int)($in['previous_cycle_id']??0)!==(int)($cycle['id']??0)||(int)($in['previous_cycle_version']??0)!==(int)($cycle['version']??0))throw new InvalidArgumentException(fcc_t('Program se promijenio. Ponovno pregledaj odabir početka.'));
   if($cycle&&$cycle['status']==='active')fcc_partner_query("UPDATE fcc_partner_journey_cycles SET status='archived',version=version+1 WHERE id=?",[(int)$cycle['id']]);
   fcc_partner_query('INSERT INTO fcc_partner_journey_cycles (user_id,catalog_version,policy_version,started_at) VALUES (?,?,?,?)',[$uid,FCC_JP_VERSION,fcc_jp_catalog()['policy']['id'],$at]);$result=(int)database()->insert_id;
   fcc_journey_event($uid,'cycle_started','cycle:'.$result,FCC_JP_VERSION,'pilot-start:'.$result);
  } elseif($op==='opportunity') {
   $oid=(int)($in['opportunity_id']??0);$o=fcc_partner_one('SELECT * FROM fcc_partner_opportunities WHERE id=? AND user_id=? FOR UPDATE',[$oid,$uid]);
   if(!$o||(int)$o['version']!==(int)($in['opportunity_version']??0))throw new InvalidArgumentException(fcc_t('Razgovor nije dostupan ili je promijenjen. Osvježi podatke.'));
   $contact=fcc_partner_contact($uid,(int)$o['contact_id']);
   if((int)$contact['version']!==(int)($in['contact_version']??0))throw new InvalidArgumentException(fcc_t('Dogovor je izmijenjen u Kontaktima. Osvježi prije spremanja.'));
   $status=fcc_partner_choice($in['status']??'',fcc_jp_statuses());$due=fcc_partner_date($in['next_followup']??'');
   if($contact['contact_permission']==='do_not_contact'&&$status!=='closed')throw new InvalidArgumentException(fcc_t('Ta osoba ne želi javljanje. Razgovor se može samo zatvoriti.'));
   if($status==='later'&&!$due)throw new InvalidArgumentException(fcc_t('Odaberi dogovoreni datum.'));
   if($due&&$due<$day)throw new InvalidArgumentException(fcc_t('Odaberi današnji ili budući dogovoreni datum.'));
   if($status==='closed')$due=null;
   if($due&&empty($in['agreed']))throw new InvalidArgumentException(fcc_t('Potvrdi da je javljanje dogovoreno.'));
   fcc_partner_query('UPDATE fcc_partner_opportunities SET status=?,next_followup=?,version=version+1,updated_at=? WHERE id=?',[$status,$due,$at,$oid]);
   // A closed opportunity must not erase a different pending follow-up.
   $next=fcc_partner_one("SELECT MIN(next_followup) due FROM fcc_partner_opportunities WHERE user_id=? AND contact_id=? AND status<>'closed'",[$uid,(int)$contact['id']])['due'];
   fcc_partner_query('UPDATE fcc_partner_contacts SET next_followup=?,version=version+1,updated_at=? WHERE id=? AND user_id=?',[$next,$at,(int)$contact['id'],$uid]);
   fcc_jp_event($uid,(int)$o['journey_step_id'],'outcome_updated',$status,$key,$at,$oid);$result=$oid;
  } else {
   $can_continue_legacy=($cycle['catalog_version']??'')===FCC_JP_LEGACY_VERSION&&($cycle['status']??'')==='pilot_complete';
   if(!fcc_jp_is_cycle($cycle)||($cycle['status']!=='active'&&!$can_continue_legacy))throw new InvalidArgumentException(fcc_t('Nema aktivnog programa.'));
   if((int)($in['cycle_id']??0)!==(int)$cycle['id']||(int)($in['cycle_version']??0)!==(int)$cycle['version'])throw new InvalidArgumentException(fcc_t('Program je promijenjen u drugom prozoru. Osvježi prikaz.'));
   $s=fcc_jp_state($uid,$access,$now);
   if($op==='open') {
    if($s['daily_done']||$s['webinar']['is_today'])throw new InvalidArgumentException(fcc_t('Za danas je predviđen spremljeni korak ili nedjeljni webinar.'));
    if($s['pilot_finished'])throw new InvalidArgumentException(fcc_t('Svih 90 koraka je završeno. Napredak je sačuvan.'));
    if($s['step'])$result=(int)$s['step']['id'];
    else {$t=$s['content'];$t['material']=fcc_journey_material(['key'=>$t['id'],'instruction'=>implode(' ',$t['actions']),'message_example'=>$t['communication']['example']],'Hrvatski');
     if(str_starts_with($t['material']['version'],'base-'))$t['material']['version']='base-'.FCC_JP_VERSION;
     fcc_partner_query('INSERT INTO fcc_partner_journey_steps (cycle_id,user_id,position,content_json,opened_at) VALUES (?,?,?,?,?)',[(int)$cycle['id'],$uid,$s['position'],json_encode($t,JSON_UNESCAPED_UNICODE),$at]);$result=(int)database()->insert_id;fcc_journey_event($uid,'started','j90:'.$cycle['id'].':'.$result,FCC_JP_VERSION,'pilot-open:'.$result);}
   } else {
    $step=$s['step'];if(!$step||(int)($in['step_id']??0)!==(int)$step['id'])throw new InvalidArgumentException(fcc_t('Ovaj korak nije tvoj aktualni korak.'));
    if((int)($in['draft_version']??-1)!==(int)$step['draft_version'])throw new InvalidArgumentException(fcc_t('Nacrt je promijenjen u drugom prozoru. Sačuvaj svoj tekst i osvježi prikaz.'));
    $t=$s['content'];$draft=$s['draft'];
    if($op==='draft'){
     $draft=fcc_jp_draft($uid,$t,$draft,$in);$draft['curriculum_task_id']=$t['id'];fcc_partner_query('UPDATE fcc_partner_journey_steps SET draft_json=?,draft_version=draft_version+1 WHERE id=?',[json_encode($draft,JSON_UNESCAPED_UNICODE),(int)$step['id']]);$result=(int)$step['id'];
     fcc_journey_event($uid,'draft_saved','j90:'.$cycle['id'].':'.$step['id'],FCC_JP_VERSION,'pilot-draft:'.$step['id'].':'.((int)$step['draft_version']+1));
    } elseif($op==='event') {
     if(fcc_journey_warm($t)&&($in['kind']??'')==='share_intent'){
      $draft=fcc_jp_draft($uid,$t,$draft,$in);
      $target=!empty($draft['contact_id'])?fcc_partner_contact($uid,(int)$draft['contact_id']):null;
      $recipient=fcc_journey_warm_recipient($uid,$t,(int)($draft['contact_id']??0));
      $in['detail']='Otvoren WhatsApp'.($recipient['kind']==='sponsor'?($recipient['phone']?' sponzoru':' za odabir sponzora'):($target?' za kontakt '.$target['name']:'' )).'. Slanje još nije potvrđeno.';
     }
     $result=fcc_jp_record_action($uid,$step,$t,$draft,$in,$key,$at);
    } elseif($op==='complete') {
     $result=fcc_jp_complete_action($uid,$s,$cycle,$step,$t,$access,$in,$day,$at);
    } elseif($op==='finish') {
     // One explicit confirmation saves the edited draft, evidence and outcome atomically.
     if(!$s['action']['can_complete'])throw new InvalidArgumentException(fcc_t('Ovaj korak trenutačno nije dostupan za završavanje.'));
     $kind=(string)($in['kind']??'');
     $ui=$t['ui'];
     if(($t['delivery_mode']??'')==='practice'&&$kind!=='practice')throw new InvalidArgumentException(fcc_t('Spremi rezultat svoje vježbe sa sponzorom.'));
     if($kind!=='practice'&&!isset($ui['done'][$kind]))throw new InvalidArgumentException(fcc_t('Odaberi radnju koja pripada ovom koraku.'));
     $draft=fcc_jp_draft($uid,$t,$draft,$in);$draft['curriculum_task_id']=$t['id'];
     if($kind!=='practice'&&$t['communication']['composer']['kind']==='article'&&!$draft['article_id'])throw new InvalidArgumentException(fcc_t('Odaberi proizvod uz koji si odradio/la ovaj korak.'));
     fcc_partner_query('UPDATE fcc_partner_journey_steps SET draft_json=?,draft_version=draft_version+1 WHERE id=?',[json_encode($draft,JSON_UNESCAPED_UNICODE),(int)$step['id']]);
     $evidence=fcc_jp_record_action($uid,$step,$t,$draft,array_merge($in,['confirmed'=>1,'appropriate'=>1]),$key,$at);
     $result=fcc_jp_complete_action($uid,$s,$cycle,$step,$t,$access,array_merge($in,['evidence_id'=>$evidence,'self_check'=>1]),$day,$at);
    } else throw new InvalidArgumentException(fcc_t('Radnja nije prepoznata.'));
   }
  }
  fcc_partner_query('INSERT INTO fcc_partner_journey_requests (user_id,request_key,payload_hash,result_id,operation,created_at) VALUES (?,?,?,?,?,?)',[$uid,$key,$hash,$result,'pilot_'.$op,$at]);
  database()->commit();return $result;
 }catch(Throwable $e){database()->rollback();throw $e;}
}
function fcc_jp_assign_mentor(int $actor,array $in): void {
 $admin=fcc_partner_one('SELECT type,status FROM users WHERE user_id=?',[$actor]);if(!$admin||(int)$admin['type']!==1||(int)$admin['status']!==1)throw new InvalidArgumentException(fcc_t('Mentora dodjeljuje administrator.'));
 if(!fcc_jp_enabled($actor))throw new InvalidArgumentException(fcc_t('Pilot nije uključen.'));
 $uid=(int)($in['user_id']??0);$mentor=(int)($in['mentor_user_id']??0);$name=fcc_partner_text($in['display_name']??'',120);$phone=preg_replace('/[ ()-]/','',fcc_partner_text($in['phone']??'',30));
 if(!fcc_partner_one('SELECT user_id FROM users WHERE user_id=? AND status=1',[$uid]))throw new InvalidArgumentException(fcc_t('Odaberi aktivnog suradnika.'));
 if($mentor){$m=fcc_partner_one('SELECT name FROM users WHERE user_id=? AND status=1',[$mentor]);if(!$m||$mentor===$uid)throw new InvalidArgumentException(fcc_t('Odaberi drugog aktivnog mentora.'));$name=$m['name'];}
 if($name==='')throw new InvalidArgumentException(fcc_t('Upiši ime mentora.'));
 if($phone!==''&&!preg_match('/^\+[1-9][0-9]{6,14}$/D',$phone))throw new InvalidArgumentException(fcc_t('Telefon upiši s međunarodnim pozivnim brojem.'));
 $confirmed=!empty($in['contact_confirmed']);if($phone!==''&&!$confirmed)throw new InvalidArgumentException(fcc_t('Potvrdi da mentor dopušta korištenje tog broja za pomoć.'));
 database()->begin_transaction();try {
  fcc_partner_query('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$uid]);$old=fcc_partner_one('SELECT version,phone FROM fcc_partner_mentor_assignments WHERE user_id=?',[$uid]);
  if((int)($old['version']??0)!==(int)($in['assignment_version']??0))throw new InvalidArgumentException(fcc_t('Dodjela je promijenjena. Osvježi pregled.'));
  $version=(int)($old['version']??0)+1;
  fcc_partner_query('INSERT INTO fcc_partner_mentor_assignments (user_id,mentor_user_id,display_name,phone,contact_confirmed,version,actor_user_id,updated_at) VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE mentor_user_id=VALUES(mentor_user_id),display_name=VALUES(display_name),phone=VALUES(phone),contact_confirmed=VALUES(contact_confirmed),version=VALUES(version),actor_user_id=VALUES(actor_user_id),updated_at=VALUES(updated_at)',[$uid,$mentor?:null,$name,$phone,$confirmed?1:0,$version,$actor]);
  fcc_partner_query('INSERT INTO fcc_partner_mentor_audit (user_id,actor_user_id,assignment_json,created_at) VALUES (?,?,?,UTC_TIMESTAMP())',[$uid,$actor,json_encode(['mentor_user_id'=>$mentor?:null,'display_name'=>$name,'contact_confirmed'=>$confirmed,'version'=>$version,'phone_changed'=>($old['phone']??'')!==$phone],JSON_UNESCAPED_UNICODE)]);database()->commit();
 }catch(Throwable $e){database()->rollback();throw $e;}
}
