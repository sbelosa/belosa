<?php
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require dirname(__DIR__,2).'/app/init.php';
if(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db')exit(1);
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage().' at '.$e->getLine().PHP_EOL);exit(1);});
Altum\Cache::initialize();Altum\Plugin::initialize();$checks=[];$a=function($ok,$name)use(&$checks){if(!$ok)throw new RuntimeException($name);$checks[]=$name;};
function top_fixture(string $table,array $values): int {
 foreach(fcc_partner_rows("SHOW COLUMNS FROM `$table`") as $c){if($c['Null']==='NO'&&$c['Default']===null&&$c['Extra']!=='auto_increment'&&!array_key_exists($c['Field'],$values))$values[$c['Field']]=str_contains($c['Type'],'int')?0:(str_contains($c['Type'],'date')?'2026-10-01 08:00:00':'');}
 $keys=implode(',',array_map(fn($k)=>"`$k`",array_keys($values)));fcc_partner_query("INSERT INTO `$table` ($keys) VALUES(".implode(',',array_fill(0,count($values),'?')).')',array_values($values));return (int)database()->insert_id;
}
fcc_partner_query("SELECT GET_LOCK('fcc_partner_notifications',10)");database()->begin_transaction();
try {
 foreach([2=>'999999999991',3=>'999999999991',4=>'999999999992'] as $uid=>$fbo){fcc_partner_query("UPDATE users SET status=1,preferences=JSON_SET(COALESCE(preferences,'{}'),'$.meta.foreverId',?) WHERE user_id=?",[$fbo,$uid]);fcc_partner_query('INSERT INTO fcc_top_profiles(user_id,visible) VALUES(?,?) ON DUPLICATE KEY UPDATE visible=VALUES(visible)',[$uid,$uid===3?0:1]);}
 $now=new DateTimeImmutable('2026-10-01 13:00:00',new DateTimeZone('Europe/Zagreb'));$before=fcc_top_dataset(7,'2026-10-01',$now);
 $b=top_fixture('fcc_cc_batches',['batch_key'=>hash('sha256','top-fixture'),'period_month'=>'2026-10-01','published_at'=>'2026-10-01 08:00:00','record_count'=>2]);
 foreach(['999999999991'=>4.567,'999999999992'=>2.123] as $fbo=>$value)top_fixture('fcc_cc_snapshots',['batch_id'=>$b,'fbo_id'=>$fbo,'period_month'=>'2026-10-01','personal_cc'=>$value,'total_cc'=>$value*2,'total_active_cc'=>$value,'is_4cc_active'=>1,'captured_at'=>'2026-10-01 08:00:00']);
 $contact=top_fixture('fcc_partner_contacts',['user_id'=>2,'name'=>'Synthetic Top test','created_at'=>'2026-09-30 08:00:00','updated_at'=>'2026-09-30 08:00:00']);
 $opp=top_fixture('fcc_partner_opportunities',['user_id'=>2,'contact_id'=>$contact,'kind'=>'product','article_id'=>42,'journey_step_id'=>null]);
 foreach(['a','b'] as $k){$aid=top_fixture('fcc_partner_activities',['user_id'=>2,'contact_id'=>$contact,'kind'=>'journey_sent','request_key'=>'top-qa-'.$k,'created_at'=>'2026-10-01 08:00:00']);top_fixture('fcc_partner_pilot_events',['user_id'=>2,'opportunity_id'=>$opp,'activity_id'=>$aid,'kind'=>'message_sent','request_key'=>'top-qa-'.$k,'created_at'=>'2026-10-01 08:00:00']);}
 $sid=top_fixture('fcc_webinar_sessions',['series_key'=>'top-qa','occurrence_key'=>'top-qa','title'=>'Synthetic only','starts_at_utc'=>'2026-10-05 10:00:00','ends_at_utc'=>'2026-10-05 11:00:00','access_url'=>'https://example.invalid','calendar_uid'=>'top-qa']);
 $attempts=[];
 foreach(['sent','duplicate','opened','demo','revoked'] as $k)$attempts[$k]=top_fixture('fcc_webinar_invite_attempts',['user_id'=>2,'session_id'=>$sid,'token_hash'=>hash('sha256',$k),'client_request_key'=>md5($k),'payload_hash'=>hash('sha256',$k),'recipient_contact_id'=>$contact,'is_demo'=>$k==='demo'?1:0,'revoked_at'=>$k==='revoked'?'2026-10-01 09:00:00':null,'whatsapp_requested_at'=>'2026-10-01 08:00:00','sent_self_reported_at'=>$k==='opened'?null:'2026-10-01 08:00:00']);
 foreach(['verified','unverified','demo','self','review','cancelled'] as $k)top_fixture('fcc_webinar_registrations',['user_id'=>2,'session_id'=>$sid,'attempt_id'=>$attempts['sent'],'name'=>'Synthetic only','email'=>'qa@example.invalid','phone'=>'','phone_key'=>hash('sha256','p'.$k),'email_key'=>hash('sha256','e'.$k),'management_hash'=>hash('sha256','m'.$k),'verification_hash'=>hash('sha256','v'.$k),'is_demo'=>$k==='demo'?1:0,'is_self'=>$k==='self'?1:0,'needs_review'=>$k==='review'?1:0,'status'=>$k==='cancelled'?'cancelled':'registered','email_verified_at'=>$k==='unverified'?null:'2026-10-01 08:00:00']);
 $d=fcc_top_dataset(7,'2026-10-01',$now);
 $a(($d['scores']['recommendations'][2]??0)===($before['scores']['recommendations'][2]??0)+1,'Duplicate recommendation for one contact and article counts once');
 $a(($d['scores']['invitations'][2]??0)===($before['scores']['invitations'][2]??0)+1,'Duplicate invites count once; drafts, demo and revoked excluded');
 $a(($d['scores']['registrations'][2]??0)===($before['scores']['registrations'][2]??0)+1,'Only verified guest counts; demo, self, review and cancellation excluded');
 $a(($d['scores']['conversations'][2]??0)===($before['scores']['conversations'][2]??0)+1,'Multiple contact actions count as one conversation');
 $a(($d['scores']['consistency'][2]??0)<=($before['scores']['consistency'][2]??0)+1&&($d['scores']['consistency'][2]??0)>0,'Multiple actions count one active day without MySQL timezone tables');
 $own2=fcc_top_own($d['lists']['personal_cc'],2);$own3=fcc_top_own($d['lists']['personal_cc'],3);
 $a($own2['entity']===$own3['entity']&&$own2['score']==4.567,'Shared Forever number counts exactly once');
 $a(!array_filter(fcc_top_public($d['lists']['personal_cc'],'personal_cc',2),fn($r)=>$r['own']),'Shared CC remains hidden until every linked account opts in');
 fcc_partner_query('UPDATE fcc_top_profiles SET visible=1 WHERE user_id=3');$d=fcc_top_dataset(7,'2026-10-01',$now);
 $a(count(array_filter(fcc_top_public($d['lists']['personal_cc'],'personal_cc',2),fn($r)=>$r['own']))===1,'Shared account appears once after all owners opt in');
 $empty=fcc_top_dataset(7,'2026-09-01',$now);$a(fcc_top_own($empty['lists']['personal_cc'],2)['score']===null,'CC months never leak into another month');
 $new=fcc_top_dataset(7,'2026-10-01',$now->modify('+8 days'));$a(($new['scores']['invitations'][2]??0)===0,'Events outside selected window are excluded');
 fcc_top_snapshot($d);fcc_top_snapshot($d);$a((int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_top_positions WHERE day=? AND category=? AND period_key=? AND entity_key=?',[$d['window']['day'],'personal_cc','2026-10-01',$own2['entity']])['n']===1,'Daily rank snapshot is idempotent');
 $baseline=fcc_top_profile(2);$a($baseline['weekly_notice']==0,'Visibility never subscribes notifications');

 $month=substr(fcc_partner_today(),0,7).'-01';$previous=(new DateTimeImmutable($month))->modify('-1 month')->format('Y-m-d');
 $page=fcc_top_page(1,30,'');$a($page['month']===$previous,'Default page uses previous month with completed CC activity');
 $page=fcc_top_page(1,30,$month);$a($page['month']===$month,'Explicit current month is preserved');
 fcc_partner_query('UPDATE fcc_top_profiles SET visible=0');
 $hidden=fcc_top_page(1,30,'2026-10-01');$ds=fcc_top_dataset(30,'2026-10-01');
 $a($hidden['cards']['personal_cc']['count']>=1,'Hidden accounts still contribute to positive CC count');
 foreach($hidden['cards'] as $cat=>$card){
  $a($card['count']===count($ds['lists'][$cat]),'Aggregate count matches source for '.$cat);
  $a($card['visible_count']===0&&$card['rows']===[],'Hidden names stay absent for '.$cat);
 }
 fcc_partner_query('UPDATE fcc_top_profiles SET visible=1 WHERE user_id IN (2,3,4)');
 $visible=fcc_top_page(1,30,'2026-10-01');
 $a($visible['cards']['personal_cc']['count']===$hidden['cards']['personal_cc']['count'],'Visibility cannot change result count');
 $a($visible['cards']['personal_cc']['visible_count']===1,'Shared Forever owners count as one visible account');
 $a(count($visible['cards']['personal_cc']['rows'])===1,'Opted in names populate ranking');
 foreach($visible['cards']['personal_cc']['rows'] as $row)$a($row['score']===null,'Visible CC amounts remain private');
}finally{database()->rollback();fcc_partner_query("SELECT RELEASE_LOCK('fcc_partner_notifications')");}
echo json_encode(['status'=>'passed','checks'=>count($checks),'details'=>$checks],JSON_PRETTY_PRINT).PHP_EOL;
