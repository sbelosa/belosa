<?php
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require dirname(__DIR__).'/app/init.php';
if(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db')exit(1);
Altum\Cache::initialize();Altum\Plugin::initialize();
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage().' at '.$e->getFile().':'.$e->getLine().PHP_EOL);exit(1);});
$checks=[];$assert=function($ok,$label)use(&$checks){if(!$ok)throw new RuntimeException($label);$checks[]=$label;};
$reject=function($fn,$label)use($assert){try{$fn();}catch(InvalidArgumentException $e){$assert(true,$label);return;}$assert(false,$label);};
$uid=2;$other=1;
if(fcc_j90_cycle($uid)||fcc_j90_cycle($other))throw new RuntimeException('Fixture accounts already have cycles; use a fresh isolated fixture, never reset user progress.');
$max=[];foreach(['fcc_partner_journey_events','fcc_partner_journey_requests','fcc_partner_journey_steps','fcc_partner_journey_cycles','fcc_partner_journey_level_events','fcc_partner_activities','fcc_partner_journey_coach','fcc_partner_contacts'] as $table)$max[$table]=(int)fcc_partner_one("SELECT COALESCE(MAX(id),0) n FROM $table")['n'];
$testLesson=0;$originalPrefs=fcc_partner_one('SELECT preferences FROM users WHERE user_id=?',[$uid])['preferences'];
$maxOutcome=(int)fcc_partner_one('SELECT COALESCE(MAX(outcome_id),0) n FROM forever_business_daily_outcomes')['n'];
$at=new DateTimeImmutable('2030-01-02 15:00',new DateTimeZone('Europe/Zagreb'));
$req=fn($more=[])=>$more+['request_key'=>bin2hex(random_bytes(16))];
$state=function()use($uid,&$at){return fcc_j90_state($uid,forever_business_get_vip_program_state($uid),$at);};
$input=function($s,$extra=[])use($req){return $req($extra+['cycle_id'=>$s['cycle']['id'],'cycle_version'=>$s['cycle']['version'],'step_id'=>$s['step']['id']??0,'draft_version'=>$s['step']['draft_version']??0]);};
try {
 $catalog=fcc_j90_catalog();$assert(count($catalog['tasks'])===90,'90 published tasks, schema 2');
 $assert(count(array_unique(array_column(array_merge(...array_column($catalog['tasks'],'help_cards')),'title')))===90,'90 task-specific help headings');
 foreach($catalog['tasks'] as $t) {
  $assert(count($t['variants'])===4&&count(array_unique(array_map(fn($v)=>json_encode($v['steps']),$t['variants'])))===4,'Distinct concrete level actions: '.$t['position']);
  $assert(!str_contains(json_encode($t,JSON_UNESCAPED_UNICODE),'Ukratko zapiši što je prilagođeno.'),'No repeated generic level instruction: '.$t['position']);
 }
 $start=$req(['confirm'=>1]);$id=fcc_j90_mutate($uid,'start',$start,$at);$same=fcc_j90_mutate($uid,'start',$start,$at);
 $assert($id===$same,'Start retry is idempotent');
 $reject(fn()=>fcc_j90_mutate($uid,'start',$start+['unexpected'=>'changed'],$at),'Same request id with changed payload rejected');
 $reject(fn()=>fcc_j90_mutate($uid,'start',$req(['confirm'=>1]),$at),'Existing cycle cannot be reset silently');
 $s=$state();$reject(fn()=>fcc_j90_mutate($uid,'open',$input($s,['level'=>4]),$at),'Cannot forge an unaccepted level');
 $open=$input($s);fcc_j90_mutate($uid,'open',$open,$at);$s=$state();$first=$s['step'];$assert($s['position']===1&&$first,'First step pins immutable content and material');
 fcc_partner_query("INSERT INTO fcc_partner_journey_lessons (action_key,language,summary,example,published,actor_user_id,created_at) VALUES (?,'Hrvatski','Test nova lekcija','Test primjer',1,3,UTC_TIMESTAMP())",[$s['content']['id']]);$testLesson=(int)database()->insert_id;
 $assert($state()['step']['content_json']===$first['content_json'],'Published lesson cannot rewrite an opened snapshot');
 fcc_partner_query('DELETE FROM fcc_partner_journey_lessons WHERE id=?',[$testLesson]);$testLesson=0;
 $prefs=json_decode($originalPrefs,true)?:[];$prefs['partner_journey']['paused_until']=$at->format('Y-m-d');fcc_partner_query('UPDATE users SET preferences=? WHERE user_id=?',[json_encode($prefs),$uid]);
 $assert(!$state()['paused']&&$state()['action']['can_complete'],'Retired pause leaves current task available');
 fcc_j90_mutate($uid,'draft',$input($s,['note'=>'May save with retired pause']),$at);$s=$state();$assert($s['draft']['note']==='May save with retired pause','Retired pause allows draft saves');
 fcc_partner_query('UPDATE users SET preferences=? WHERE user_id=?',[$originalPrefs,$uid]);
 $reject(fn()=>fcc_j90_compose($uid,fcc_j90_content(13),[]),'Free cannot compose WhatsApp by direct call');
 $article=fcc_partner_one("SELECT blog_post_id FROM blog_posts WHERE language='Hrvatski' AND is_published=1 LIMIT 1");
 $message=fcc_j90_compose($other,fcc_j90_content(13),['article_id'=>$article['blog_post_id'],'ref'=>'attacker','url'=>'https://evil.invalid']);
 $assert(str_contains($message['message'],'ref=ana-demo')&&!str_contains($message['message'],'attacker'),'PRO referral comes from owner, ignoring forged ref and URL');
 $assert(str_starts_with(fcc_j90_whatsapp_url($message['message']),'https://wa.me/?text=')&&!str_contains(fcc_j90_whatsapp_url($message['message']),'phone='),'WhatsApp chooser has no fixed recipient');
 $reject(fn()=>fcc_j90_whatsapp_url('Bok [ime]'),'Unresolved message placeholders cannot open WhatsApp');
 $reject(fn()=>fcc_j90_compose($other,fcc_j90_content(13),['article_id'=>99999999]),'Unpublished or missing source rejected');
 $options=fcc_j90_message_options($other,fcc_j90_content(13));
 $assert(count($options)>0&&count(array_filter($options,fn($o)=>str_contains($o['referral_url'],'ref=ana-demo')))===count($options),'All automatic article choices use signed-in partner referral');
 $reject(fn()=>fcc_j90_message_options($uid,fcc_j90_content(13)),'Free cannot read premium automatic message choices');
 $own=$message['referral_url'];$edited='Bok! Moj tekst, čćžšđ i 🙂. ++';
 $assert(fcc_j90_with_referral($edited,$own)===$edited."\n".$own,'Edited unsaved message keeps prose and gets owner link');
 $forged=str_replace('ref=ana-demo','ref=other-person',$own);
 $assert(fcc_j90_with_referral('Moja izmjena '.$forged,$own)==='Moja izmjena '.$own,'Saved message corrects selected article referral');
 $assert(fcc_j90_with_referral('Moja izmjena '.$own,$own)==='Moja izmjena '.$own,'Correct own link is included once');
 $assert(fcc_j90_with_referral($edited,'')===$edited,'Conversation without article keeps exact message');
 $reject(fn()=>fcc_j90_with_referral(str_repeat('x',3000),$own),'Automatic referral never silently truncates long message');
 $text=fcc_j90_compose($other,fcc_j90_content(5),['article_id'=>$article['blog_post_id']]);$assert($text['article_id']===0&&!str_contains($text['message'],'http'),'Conversation task needs no product or unnecessary link');
 $draft=$input($s,['message'=>'Moja vlastita kratka poruka.','note'=>'Priprema']);fcc_j90_mutate($uid,'draft',$draft,$at);
 $reject(fn()=>fcc_j90_mutate($uid,'draft',$req(array_merge($draft,['request_key'=>bin2hex(random_bytes(16)),'note'=>'Stari nacrt'])),$at),'Stale draft cannot overwrite another device');
 $s=$state();$reject(fn()=>fcc_j90_mutate($uid,'complete',$input($s,['learning_mode'=>'practice','output_status'=>'sent','difficulty'=>'ok','self_check'=>1]),$at),'Practice cannot count as sent');
 $complete=$input($s,['learning_mode'=>'practice','output_status'=>'practised','difficulty'=>'ok','self_check'=>1]);$oid=fcc_j90_mutate($uid,'complete',$complete,$at);
 $assert($oid===fcc_j90_mutate($uid,'complete',$complete,$at),'Completion retry creates one outcome');
 $s=$state();$assert($s['completed']===1&&$s['readiness']['counts']['external']===0,'Practice advances order without applied or external credit');
 $reject(fn()=>fcc_j90_mutate($uid,'open',$input($s),$at),'Second regular step in Zagreb day rejected');
 $assert(!forever_business_record_daily_outcome($uid,'360999990002',['360999990002'],['action_key'=>'vip26_starter_d01','core_key'=>'Development','outcome_count'=>1,'result_type'=>'training','difficulty'=>'ok','completion_mode'=>'standard','outcome_type'=>'starter'],$at),'Legacy endpoint cannot bypass active new cycle');
 // Simulate separate working days, never mutate the server clock or real user history.
 for($i=2;$i<=90;$i++) {
  do{$at=$at->modify('+1 day');}while($at->format('N')==='7');
  $s=$state();if($s['readiness']['proposed_level']){fcc_j90_mutate($uid,'level',$input($s,['level'=>$s['readiness']['proposed_level']]),$at);$s=$state();}
  fcc_j90_mutate($uid,'open',$input($s),$at);$s=$state();$variant=$s['content']['variant'];
  if($i===3){$reject(fn()=>fcc_j90_mutate($uid,'complete',$input($s,['step_id'=>$first['id'],'learning_mode'=>'applied','output_status'=>'prepared','difficulty'=>'ok','self_check'=>1]),$at),'Old snapshot cannot complete current step');}
  $external=$variant['external_event_eligible'];$advanced=$external&&$variant['advanced_case_eligible'];
  $finish=$input($s,['learning_mode'=>'applied','output_status'=>$external?'sent':'prepared','difficulty'=>$i===17?'hard':'ok','self_check'=>1,'contact_permission'=>1,'note'=>$advanced?'Razjasnio sam pitanje, obrazložio svoj izbor i provjerio ga prema aktualnoj uputi.':'']);
  if($i===5){
   $utc=$at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
   fcc_partner_query("INSERT INTO fcc_partner_activities (user_id,contact_id,kind,note,request_key,created_at) VALUES (1,NULL,'contacted','Synthetic foreign interaction',?,?)",[bin2hex(random_bytes(16)),$utc]);$foreign=(int)database()->insert_id;
   $reject(fn()=>fcc_j90_mutate($uid,'complete',$finish+['interaction_id'=>$foreign],$at),'Foreign interaction cannot be claimed');
   fcc_partner_query("INSERT INTO fcc_partner_contacts (user_id,name,contact_permission,created_at,updated_at) VALUES (?,'Synthetic blocked contact','do_not_contact',?,?)",[$uid,$utc,$utc]);$blocked=(int)database()->insert_id;
   $reject(fn()=>fcc_j90_mutate($uid,'complete',$finish+['contact_id'=>$blocked],$at),'Do-not-contact record cannot be counted as communication');
  }
  if($i===6){
   $used=fcc_partner_one('SELECT interaction_id FROM forever_business_daily_outcomes WHERE journey_cycle_id=? AND sequence_position=5',[$id]);
   // Put the fixture evidence on today to isolate the one-use rule from the date rule.
   fcc_partner_query('UPDATE fcc_partner_activities SET created_at=? WHERE id=?',[$at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),$used['interaction_id']]);
   $reject(fn()=>fcc_j90_mutate($uid,'complete',$finish+['interaction_id'=>$used['interaction_id']],$at),'One communication cannot support two completed steps');
  }
  if($advanced)$finish+=['advanced_case'=>1,'source_checked'=>1];
  fcc_j90_mutate($uid,'complete',$finish,$at);
 }
 $s=$state();$assert($s['completed']===90&&$s['cycle']['status']==='completed'&&$s['action']['is_program_complete'],'All 90 steps complete in order without 91/90');
 $assert((int)$s['cycle']['accepted_level']===4,'Skills policy v2 reaches level 4 without CC hard gate or team prerequisite');
 $assert(count(fcc_partner_rows('SELECT * FROM fcc_partner_journey_level_events WHERE cycle_id=?',[$id]))===3,'Three explicit level acceptances audited');
 $reject(fn()=>fcc_j90_mutate($uid,'open',$input($s),$at),'Finished program cannot silently reopen step 91');
 putenv('FCC_JOURNEY90_ENABLED=0');$blocked=fcc_j90_state($uid,forever_business_get_vip_program_state($uid),$at);
 $assert(!$blocked['enabled']&&$blocked['completed']===90,'Feature rollback preserves complete history');
 $reject(fn()=>fcc_j90_mutate($uid,'start',$req(['confirm'=>1]),$at),'Disabled feature rejects new writes');putenv('FCC_JOURNEY90_ENABLED');
 $aStart=fcc_j90_mutate($other,'start',$req(['confirm'=>1]),$at);
 $aState=fcc_j90_state($other,forever_business_get_vip_program_state($other),$at);
 $reject(fn()=>fcc_j90_mutate($other,'open',$input($s),$at),'Foreign cycle cannot be used');
 fcc_j90_mutate($other,'open',$input($aState),$at);$aState=fcc_j90_state($other,forever_business_get_vip_program_state($other),$at);
 // Sunday shares the same daily lock, does not consume sequence position.
 $sunday=$at->modify('next sunday')->setTime(19,30);
 $aState=fcc_j90_state($other,forever_business_get_vip_program_state($other),$sunday);
 $web=$input($aState,['learning_mode'=>'practice','self_check'=>1,'note'=>'Jedna korisna ideja iz materijala.']);
 $reject(fn()=>fcc_j90_mutate($other,'webinar',$web,$sunday),'Sunday completion blocked before 20:30');
 $sunday=$sunday->setTime(20,31);fcc_j90_mutate($other,'webinar',$web,$sunday);
 $aState=fcc_j90_state($other,forever_business_get_vip_program_state($other),$sunday);
 $assert($aState['completed']===0&&$aState['daily_done'],'Sunday completion uses day but not one of 90 positions');
 $reject(fn()=>fcc_j90_mutate($other,'complete',$input($aState,['learning_mode'=>'practice','output_status'=>'practised','self_check'=>1,'difficulty'=>'ok']),$sunday),'Sunday and regular step cannot both complete');
 echo json_encode(['status'=>'PASS','checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
} finally {
 putenv('FCC_JOURNEY90_ENABLED');
 fcc_partner_query('UPDATE users SET preferences=? WHERE user_id=?',[$originalPrefs,$uid]);
 if($testLesson)fcc_partner_query('DELETE FROM fcc_partner_journey_lessons WHERE id=?',[$testLesson]);
 fcc_partner_query('DELETE FROM forever_business_daily_outcomes WHERE outcome_id>? AND recorded_by_user_id IN (1,2)',[$maxOutcome]);
 foreach($max as $table=>$id)fcc_partner_query("DELETE FROM $table WHERE id>? AND user_id IN (1,2)",[$id]);
}
