<?php
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require dirname(__DIR__).'/app/init.php';
if(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db')exit(1);
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage().' at '.$e->getFile().':'.$e->getLine().PHP_EOL);exit(1);});
\Altum\Cache::initialize();\Altum\Plugin::initialize();
$checks=[];$assert=function($ok,$name)use(&$checks){if(!$ok)throw new RuntimeException($name);$checks[]=$name;};
$reject=function($fn,$name)use($assert){try{$fn();}catch(InvalidArgumentException $e){$assert(true,$name);return;}$assert(false,$name);};
$uids=[1,2,3];$saved=fcc_partner_rows('SELECT user_id,preferences FROM users WHERE user_id IN (1,2,3)');
$max=[];foreach(['fcc_partner_journey_reviews','fcc_partner_journey_coach','fcc_partner_journey_lessons','fcc_partner_journey_events'] as $t)$max[$t]=(int)(fcc_partner_one("SELECT COALESCE(MAX(id),0) n FROM $t")['n']);
$outcome=0;$testTask=0;$testNotificationIds=[];
try {
 $ana=fcc_journey_state(1);$marko=fcc_journey_state(2);
 $assert($ana['pro']&&!$marko['pro'],'Server uses actual Free / PRO entitlement');
 $assert($ana['planned_total']===90&&$ana['published_total']===60,'90-slot program reserves unpublished extension without fabricating tasks');
 $assert(!empty($ana['material']['example'])&&!empty($marko['material']['example']),'Usable basic example exists for Free and PRO');
 $u=clone $ana['user'];$u->plan_expiration_date='2020-01-01';$u->extra=new stdClass();
 $assert(!fcc_journey_pro($u),'Expired PRO without grace cannot generate');
 $input=['action_key'=>$ana['key'],'purpose'=>'message','request_key'=>bin2hex(random_bytes(16))];
 $reject(fn()=>fcc_journey_coach_generate(2,['action_key'=>$marko['key']]+$input),'Free direct generation is rejected before transport');
 $reject(fn()=>fcc_journey_coach_context(1,['action_key'=>'someone-else']+$input),'Stale or forged task is rejected');
 $reject(fn()=>fcc_journey_coach_context(1,$input+['contact_id'=>999999999]),'Unknown or foreign contact cannot enter prompt');
 $reject(fn()=>fcc_journey_coach_context(1,$input+['parent_id'=>999999999]),'Unknown or foreign advice cannot enter prompt');
 $packet=fcc_journey_coach_context(1,$input);
 $json=json_encode($packet['context']);
 $assert(!str_contains($json,'suradnik@')&&!str_contains($json,'1000')&&!str_contains($json,'fbo_id'),'Prompt has no contact identifiers or admin target');
 $calls=0;$mock=function($messages)use(&$calls){$calls++;return ['success'=>true,'model'=>'offline-test','content'=>json_encode(['guidance'=>'Pripremi jednu kratku preporuku prema zadatku.','message'=>'Šaljem informacije koje si tražio/la: [FCC_LINK]','next_step'=>'Pregledaj primjer i zabilježi stvarni ishod.','question'=>''])];};
 $id=fcc_journey_coach_generate(1,$input,$mock);$same=fcc_journey_coach_generate(1,$input,$mock);
 $cached=fcc_journey_coach_generate(1,array_merge($input,['request_key'=>bin2hex(random_bytes(16))]),$mock);
 $assert($id===$same&&$same===$cached&&$calls===1,'Retries and identical context reuse one generated response');
 $advice=fcc_partner_one('SELECT * FROM fcc_partner_journey_coach WHERE id=?',[$id]);
 $assert(str_contains($advice['content'],str_replace('/','\\/',$packet['link']))||str_contains($advice['content'],$packet['link']),'Server attaches owned referral URL');
 $article=fcc_partner_one("SELECT blog_post_id FROM blog_posts WHERE language='Hrvatski' AND is_published=1 ORDER BY blog_post_id DESC LIMIT 1");
 if($article) {
  $articleInput=array_merge($input,['article_id'=>(int)$article['blog_post_id'],'request_key'=>bin2hex(random_bytes(16))]);
  $articleAdvice=fcc_journey_coach_generate(1,$articleInput,$mock);
  $stored=fcc_partner_one('SELECT content,context_refs FROM fcc_partner_journey_coach WHERE id=?',[$articleAdvice]);
  $refs=json_decode($stored['context_refs'],true);
  $assert($refs['article_id']===(int)$article['blog_post_id']&&str_contains($stored['content'],'ref=ana-demo'),'Article choice and owned referral survive saved advice');
  $follow=fcc_journey_coach_context(1,array_merge($articleInput,['parent_id'=>$articleAdvice,'question'=>'Skrati poruku']));
  $assert(!empty($follow['context']['previous_advice'])&&!empty($follow['context']['article']),'Follow-up combines prior answer with selected source');
 }
 $p=fcc_journey_profile(fcc_journey_user(1));
 $profile=['goal'=>'recruitment','minutes'=>10,'channel'=>'whatsapp','reminder_hour'=>18,'paused_until'=>'','reminders'=>1,'version'=>$p['version']];
 fcc_journey_save_profile(1,$profile);$u=fcc_journey_user(1);
 $assert($u->preferences->partner_journey->goal==='recruitment'&&$u->preferences->leader_ai_profile->primary_goal==='recruitment','One goal updates legacy Coach context too');
 $reject(fn()=>fcc_journey_save_profile(1,$profile),'Concurrent profile edit is rejected');
 $beforeOutcomes=(int)fcc_partner_one('SELECT COUNT(*) n FROM forever_business_daily_outcomes WHERE recorded_by_user_id=1')['n'];
 $newInput=array_merge($input,['request_key'=>bin2hex(random_bytes(16)),'question'=>'Nova situacija']);
 $reject(fn()=>fcc_journey_coach_generate(1,$newInput,fn()=>['success'=>false]),'Provider failure keeps a usable base program');
 $assert((int)fcc_partner_one('SELECT COUNT(*) n FROM forever_business_daily_outcomes WHERE recorded_by_user_id=1')['n']===$beforeOutcomes,'AI cannot mark a task as completed');
 try{fcc_journey_coach_validate(json_encode(['guidance'=>'text','message'=>'https://evil.example','next_step'=>'x','question'=>'']),'');$assert(false,'Reject model URL');}catch(RuntimeException $e){$assert(true,'Reject model-invented URLs');}
 $key=forever_business_vip_action_key('starter',37);$last=fcc_partner_one('SELECT MAX(id) id FROM fcc_partner_journey_lessons WHERE action_key=? AND language=?',[$key,'Hrvatski']);
 $lesson=['action_key'=>$key,'language'=>'Hrvatski','summary'=>'Testni sažetak','example'=>'Testni primjer','video_id'=>'','version'=>(int)($last['id']??0)];
 $reject(fn()=>fcc_journey_lesson_save(1,$lesson),'Member cannot edit or publish curriculum');
 $prior=fcc_journey_material(['key'=>$key]+forever_business_get_vip_task_catalog()['starter'][37],'Hrvatski');
 fcc_journey_lesson_save(3,$lesson);
 $draft=fcc_journey_material(['key'=>$key]+forever_business_get_vip_task_catalog()['starter'][37],'Hrvatski');
 $assert($prior===$draft,'Admin draft never replaces published material');
 $last=fcc_partner_one('SELECT MAX(id) id FROM fcc_partner_journey_lessons WHERE action_key=? AND language=?',[$key,'Hrvatski']);
 fcc_journey_lesson_save(3,array_merge($lesson,['publish'=>1,'version'=>(int)$last['id']]));
 $published=fcc_journey_material(['key'=>$key]+forever_business_get_vip_task_catalog()['starter'][37],'Hrvatski');
 $assert($published['example']==='Testni primjer','Publishing a revision changes the matching lesson');
 $reject(fn()=>fcc_journey_lesson_save(3,$lesson),'Stale lesson revision is rejected');
 fcc_partner_query("INSERT INTO forever_business_daily_outcomes (fbo_id,action_date,core_key,action_key,status,outcome_count,result_type,difficulty,recorded_by_user_id,completion_mode,created_at,updated_at) VALUES ('360999990001',?,'Development','qa_journey_review','done',1,'training','hard',1,'quick',UTC_TIMESTAMP(),UTC_TIMESTAMP())",[fcc_partner_today()]);$outcome=(int)database()->insert_id;
 $week=fcc_journey_week();$existing=fcc_partner_one('SELECT id FROM fcc_partner_journey_reviews WHERE user_id=1 AND week_start=?',[$week]);
 if(!$existing) {
  $review=['week'=>$week,'helped'=>'Primjer je jasan','obstacle'=>'Treba mi vježba','version'=>0];fcc_journey_review_save(1,$review);fcc_journey_review_save(1,$review);
  $assert((int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_journey_reviews WHERE user_id=1 AND week_start=?',[$week])['n']===1,'Double submit records one weekly reflection');
  $reject(fn()=>fcc_journey_review_save(1,array_merge($review,['obstacle'=>'Zastarjeli unos'])),'Conflicting reflection cannot overwrite newer answer');
 }
 $assert(fcc_journey_stats(1)['practice']>=1&&fcc_journey_stats(1)['hard']>=1,'Review distinguishes training and reported difficulty');
 $future=(new DateTimeImmutable('now',new DateTimeZone('Europe/Zagreb')))->setTime(18,0);
 $p=fcc_journey_profile(fcc_journey_user(1));fcc_journey_save_profile(1,array_merge($profile,['version'=>$p['version'],'paused_until'=>$future->format('Y-m-d')]));
 $assert(fcc_journey_profile(fcc_journey_user(1))['paused_until']==='','Retired pause cannot be saved through profile form');
 $p=fcc_journey_profile(fcc_journey_user(2));fcc_journey_save_profile(2,array_merge($profile,['version'=>$p['version']]));
 $np=fcc_partner_notification_preferences(2);$np['categories']=array_fill_keys(array_keys(fcc_partner_notification_rules()),true);fcc_partner_save_preferences(2,'partner_notifications',$np);
 $sunday=new DateTimeImmutable('2035-07-01 18:00:00',new DateTimeZone('Europe/Zagreb'));
 fcc_partner_query('INSERT INTO fcc_partner_tasks (user_id,title,due_date,request_key,created_at) VALUES (2,?,?,?,UTC_TIMESTAMP())',['QA journey reminder',$sunday->format('Y-m-d'),bin2hex(random_bytes(16))]);$testTask=(int)database()->insert_id;
 $summary=fcc_journey_reminder(2,$sunday);
 $assert($summary&&str_contains($summary['body'],'19:00'),'Daily summary includes opted-in Sunday webinar');
 $maxNotification=(int)fcc_partner_one('SELECT COALESCE(MAX(id),0) n FROM fcc_partner_notifications')['n'];
 fcc_partner_notification_sync_user(['user_id'=>2,'type'=>0],$sunday);fcc_partner_notification_sync_user(['user_id'=>2,'type'=>0],$sunday);
 $testNotificationIds=array_column(fcc_partner_rows('SELECT id FROM fcc_partner_notifications WHERE user_id=2 AND id>?',[$maxNotification]),'id');
 $assert((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=2 AND event_key IN ('daily:2035-07-01','webinar:2035-07-01')")['n']===1,'Daily plus webinar coalesce and retries create no duplicate');
 $np['categories']['daily']=false;fcc_partner_save_preferences(2,'partner_notifications',$np);
 $assert(fcc_journey_reminder(2,$sunday)===null,'Daily opt-out suppresses fresh reminder');
 $assert(fcc_partner_notification_path('partner?view=step')==='partner?view=step'&&fcc_partner_notification_path('//evil.test')==='partner/notifications','Deep link allowlist supports app views without external redirects');
 $assert(fcc_journey_week(new DateTimeImmutable('2026-10-25 02:30:00',new DateTimeZone('Europe/Zagreb')))==='2026-10-19','Reflection week respects Zagreb DST transition');
 echo json_encode(['status'=>'PASS','checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
} finally {
 foreach($saved as $u) {fcc_partner_query('UPDATE users SET preferences=? WHERE user_id=?',[$u['preferences'],(int)$u['user_id']]);cache()->deleteItemsByTag('user_id='.$u['user_id']);}
 if($outcome)fcc_partner_query('DELETE FROM forever_business_daily_outcomes WHERE outcome_id=? AND action_key=?',[$outcome,'qa_journey_review']);
 if($testTask)fcc_partner_query('DELETE FROM fcc_partner_tasks WHERE id=? AND user_id=2',[$testTask]);
 foreach($testNotificationIds as $id)fcc_partner_query('DELETE FROM fcc_partner_notifications WHERE id=? AND user_id=2',[$id]);
 foreach($max as $table=>$id) {
  $where=$table==='fcc_partner_journey_lessons'?"actor_user_id=3 AND summary='Testni sažetak'":($table==='fcc_partner_journey_events'?"user_id=1 AND (event_type='coach_ready' OR event_type='review_saved')":'user_id=1');
  fcc_partner_query("DELETE FROM $table WHERE id>? AND $where",[$id]);
 }
}

