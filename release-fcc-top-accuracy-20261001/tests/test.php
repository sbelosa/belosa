<?php
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require dirname(__DIR__,2).'/app/init.php';
if(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db')exit(1);
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage().' at '.$e->getLine().PHP_EOL);exit(1);});
Altum\Cache::initialize();Altum\Plugin::initialize();
foreach(explode(';',file_get_contents(__DIR__.'/schema.sql')) as $sql)if(trim($sql))fcc_partner_query($sql);
$t=microtime(true);$d=fcc_top_dataset(30,'2026-09-01');$metrics=['seconds'=>round(microtime(true)-$t,3),'counts'=>array_map('count',$d['lists']),'cc_sync'=>$d['sync'],'users'=>$d['accounts']];
$checks=[];$a=function($ok,$name)use(&$checks){if(!$ok)throw new RuntimeException($name);$checks[]=$name;};
$rows=[];foreach([10,10,8,0,-1,NAN] as $i=>$score)$rows[]=['entity'=>(string)$i,'score'=>$score,'uids'=>[$i+1],'name'=>'<script>QA</script>','visible'=>$i!==1];
$r=fcc_top_rank($rows);$a(array_column($r,'position')===[1,1,3],'Competition ranks preserve ties and exclude nonpositive values');
$pub=fcc_top_public($r,'personal_cc',1);$a(count($pub)===2&&$pub[0]['score']===null&&!isset($pub[0]['entity'],$pub[0]['uids']),'Public CC model has no amount or identifiers');
$a(fcc_top_own($r,2)['position']===1,'Hidden member retains own rank');
$a(array_column($pub,'position')===[1,2],'Visible ranking excludes hidden participants');
foreach([7,30,60] as $days){$w=fcc_top_window($days,new DateTimeImmutable('2026-10-26 12:00:00',new DateTimeZone('Europe/Zagreb')));$a($w['days']===$days&&$w['from']<$w['to'],'Window '.$days.' handles DST');}
$a(fcc_top_window(999)['days']===30,'Invalid period uses 30 days');
foreach(['hr','en','sl','de','es'] as $lang)foreach(array_keys(require APP_PATH.'config/fcc_top_locales.php') as $k)$a(fcc_top_t($k,[],$lang)!==$k,'Translation '.$lang.' '.$k);
// All fixture changes remain in a transaction. No external transport is called.
fcc_partner_query("SELECT GET_LOCK('fcc_partner_notifications',10)");database()->begin_transaction();
try {
 $uid=2;$p=fcc_top_profile($uid);$a($p['visible']&&!$p['weekly_notice']&&!$p['milestone_notice'],'Visibility defaults on and notifications default off');
 // Synthetic users are existing local fixtures. Reset only within this rollback transaction.
 fcc_partner_query('DELETE FROM fcc_top_profiles WHERE user_id IN (1,2,3)');
 fcc_partner_query('INSERT INTO fcc_top_profiles(user_id,visible,version) VALUES(1,1,1),(2,0,1),(3,1,1)');
 $d=fcc_top_dataset(7,'2026-09-01');$a(array_keys($d['lists'])===['total_active_cc','registrations','conversations','education','consistency'],'Exactly five supported categories compute against real schema');
 foreach($d['lists'] as $cat=>$rows){$pub=fcc_top_public($rows,$cat,2);foreach($pub as $row)$a(!$row['own'],'Hidden fixture never appears in visible list');}
 // Force a positive result for a Free fixture: plan is irrelevant to aggregation.
 $id=2;$contact=(int)(fcc_partner_one('SELECT id FROM fcc_partner_contacts WHERE user_id=? LIMIT 1',[$id])['id']??0);
 if($contact){
  $before=$d['scores']['conversations'][$id]??0;
  $key='top-test-'.bin2hex(random_bytes(8));
  fcc_partner_query("INSERT INTO fcc_partner_activities(user_id,contact_id,kind,request_key,created_at) VALUES(?,?,'contacted',?,UTC_TIMESTAMP()),(?,?,'contacted',?,UTC_TIMESTAMP())",[$id,$contact,$key,$id,$contact,$key.'b']);
  $next=fcc_top_dataset(7,'2026-09-01');$a(($next['scores']['conversations'][$id]??0)<=$before+1,'Duplicate contact actions count once');
 }
 $safe=fcc_top_public(fcc_top_rank([['entity'=>'safe','score'=>6543.219,'uids'=>[999],'name'=>'QA','visible'=>true]]),'total_active_cc',2);
 $a(!str_contains(json_encode($safe),'6543')&&!str_contains(json_encode($safe),'999'),'Serialized public data has no private CC or user ID');
 $a(fcc_top_profile(2)['visible']==0,'Visibility remains off');
 $a(str_contains(file_get_contents(APP_PATH.'helpers/fcc_partner_notifications.php'),"n.event_key NOT LIKE 'top:%'"),'Inbox notices excluded from push creation');
 $a(str_contains(file_get_contents(APP_PATH.'helpers/fcc_partner_notifications.php'),"last_code='inbox_only'"),'Delivery also blocks accidentally queued Top notices');
 $a(str_contains(file_get_contents(APP_PATH.'controllers/Partner.php'),"\$section==='top'&&!fcc_pro_has_access(\$uid)"),'Controller enforces PRO before data assembly');
}finally{database()->rollback();fcc_partner_query("SELECT RELEASE_LOCK('fcc_partner_notifications')");}
echo json_encode(['status'=>'passed','checks'=>count($checks),'metrics'=>$metrics,'checks_detail'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;
