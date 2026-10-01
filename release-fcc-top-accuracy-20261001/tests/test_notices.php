<?php
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require dirname(__DIR__,2).'/app/init.php';
if(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db')exit(1);
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage().' at '.$e->getLine().PHP_EOL);exit(1);});Altum\Cache::initialize();Altum\Plugin::initialize();
$checks=[];$a=function($ok,$name)use(&$checks){if(!$ok)throw new RuntimeException($name);$checks[]=$name;};
fcc_partner_query("SELECT GET_LOCK('fcc_partner_notifications',10)");database()->begin_transaction();
try{
 $uid=2;$now=new DateTimeImmutable('2026-10-05 12:00:00',new DateTimeZone('Europe/Zagreb'));
 fcc_partner_query("UPDATE users SET status=1,extra='{}',plan_settings=JSON_SET(COALESCE(plan_settings,'{}'),'$.ai_growth_plan_is_enabled',true),plan_expiration_date='2030-01-01 00:00:00' WHERE user_id=?",[$uid]);
 fcc_partner_query("INSERT INTO fcc_top_profiles(user_id,visible,weekly_notice,milestone_notice) VALUES(?,0,1,1) ON DUPLICATE KEY UPDATE visible=0,weekly_notice=1,milestone_notice=1",[$uid]);
 fcc_partner_query('DELETE FROM fcc_top_state WHERE user_id=?',[$uid]);
 fcc_partner_query("DELETE FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'top:%'",[$uid]);
 $noticeUsers=fcc_partner_rows('SELECT user_id FROM users WHERE status=1 ORDER BY user_id LIMIT 12');
 foreach($noticeUsers as $nu){$nuid=(int)$nu['user_id'];fcc_partner_query("INSERT INTO fcc_partner_journey_cycles(user_id,catalog_version,policy_version,started_at,status) VALUES(?,?,?,'2026-09-25 08:00:00','completed')",[$nuid,FCC_JP_VERSION,'qa']);$cycle=(int)database()->insert_id;
 for($i=0;$i<($nuid===$uid?25:1);$i++){$key='top-notice-test-'.$nuid.'-'.$i;fcc_partner_query("INSERT INTO fcc_partner_journey_steps(cycle_id,user_id,position,content_json,opened_at) VALUES(?,?,?,?,'2026-10-01 08:00:00')",[$cycle,$nuid,$i+1,json_encode(['id'=>$key])]);$step=(int)database()->insert_id;fcc_partner_query("INSERT INTO forever_business_daily_outcomes(fbo_id,action_date,core_key,action_key,status,recorded_by_user_id,journey_cycle_id,journey_step_id,sequence_position,outcome_type,created_at,updated_at) VALUES('999999999991','2026-10-01','Development',?,'done',?,?,?,?,'journey90','2026-10-01 08:00:00','2026-10-01 08:00:00')",[$key,$nuid,$cycle,$step,$i+1]);}}

 fcc_top_notify_user($uid,$now);
 $a((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'top:weekly:%'",[$uid])['n']===1,'Opted in PRO receives weekly inbox reminder');
 $a((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'top:milestone:%'",[$uid])['n']===0,'First measurement never claims an achievement');
 fcc_top_notify_user($uid,$now);
 $a((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'top:%'",[$uid])['n']===1,'Repeated worker tick is idempotent');
 fcc_partner_query("UPDATE fcc_top_state SET checked_day='2026-10-04',ranks_json=? WHERE user_id=?",[json_encode(array_fill_keys(array_map(fn($cat)=>in_array($cat,['education','consistency'],true)?$cat.'_v2':$cat,fcc_top_categories()),999)),$uid]);
 fcc_top_notify_user($uid,$now);
 $n=(int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'top:%'",[$uid])['n'];
 $a((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'top:%' AND path LIKE '%partner/top?month=2026-10-01'",[$uid])['n']===2,'Notifications link to their measured month');
 $a($n===2,'Top 10 entry creates exactly one milestone alongside the weekly notice');
 fcc_partner_query("UPDATE fcc_top_state SET checked_day='2026-10-04',ranks_json=? WHERE user_id=?",[json_encode(array_fill_keys(array_map(fn($cat)=>in_array($cat,['education','consistency'],true)?$cat.'_v2':$cat,fcc_top_categories()),999)),$uid]);fcc_top_notify_user($uid,$now);
 $a((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'top:%'",[$uid])['n']===2,'Repeated Top 10 entry cannot exceed two notices per week');
 fcc_partner_query("UPDATE fcc_top_profiles SET weekly_notice=0,milestone_notice=0 WHERE user_id=?",[$uid]);
 fcc_top_notify_user($uid,$now->modify('+7 days'));
 $a((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'top:%'",[$uid])['n']===$n,'Notification opt out prevents future notices');
 fcc_partner_query("UPDATE users SET plan_settings=JSON_SET(plan_settings,'$.ai_growth_plan_is_enabled',false) WHERE user_id=?",[$uid]);
 $denied=false;try{fcc_top_page($uid,30,'2026-10-01');}catch(RuntimeException $e){$denied=$e->getMessage()==='PRO required';}$a($denied,'Free account cannot obtain ranking view model');
 fcc_partner_query('UPDATE fcc_top_profiles SET weekly_notice=1,milestone_notice=1 WHERE user_id=?',[$uid]);fcc_top_notify_user($uid,$now->modify('+7 days'));
 $a((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=? AND event_key LIKE 'top:%'",[$uid])['n']===$n,'PRO expiry stops Top reminders');
 $d=fcc_top_dataset(30,'2026-10-01',$now);$a(isset($d['users'][$uid]),'Free account still contributes to statistics');
}finally{database()->rollback();fcc_partner_query("SELECT RELEASE_LOCK('fcc_partner_notifications')");}
echo json_encode(['status'=>'passed','checks'=>count($checks),'details'=>$checks],JSON_PRETTY_PRINT).PHP_EOL;
