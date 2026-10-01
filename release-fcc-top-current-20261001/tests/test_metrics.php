<?php
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require dirname(__DIR__,2).'/app/init.php';
if(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db')exit(1);
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage().' at '.$e->getLine().PHP_EOL);exit(1);});
Altum\Cache::initialize();Altum\Plugin::initialize();$checks=[];$a=function($ok,$name)use(&$checks){if(!$ok)throw new RuntimeException($name);$checks[]=$name;};
function top_fixture(string $table,array $values): int {
 foreach(fcc_partner_rows("SHOW COLUMNS FROM `$table`") as $c){if($c['Null']==='NO'&&$c['Default']===null&&$c['Extra']!=='auto_increment'&&!array_key_exists($c['Field'],$values))$values[$c['Field']]=str_contains($c['Type'],'int')?0:(str_contains($c['Type'],'date')?'2026-10-01 08:00:00':'');}
 $keys=implode(',',array_map(fn($k)=>"`$k`",array_keys($values)));$stmt=database()->prepare("INSERT INTO `$table` ($keys) VALUES(".implode(',',array_fill(0,count($values),'?')).')');$params=array_values($values);$types=str_repeat('s',count($params));$stmt->bind_param($types,...$params);if(!$stmt->execute())throw new RuntimeException($table.': '.$stmt->error);return (int)database()->insert_id;
}
fcc_partner_query("SELECT GET_LOCK('fcc_partner_notifications',10)");database()->begin_transaction();
try {
 foreach([2=>'999999999991',3=>'999999999991',4=>'999999999992'] as $uid=>$fbo){fcc_partner_query("UPDATE users SET status=1,preferences=JSON_SET(COALESCE(preferences,'{}'),'$.meta.foreverId',?) WHERE user_id=?",[$fbo,$uid]);fcc_partner_query('INSERT INTO fcc_top_profiles(user_id,visible,version) VALUES(?,?,1) ON DUPLICATE KEY UPDATE visible=VALUES(visible),version=1',[$uid,$uid===3?0:1]);}
 $now=new DateTimeImmutable('2026-10-01 13:00:00',new DateTimeZone('Europe/Zagreb'));$before=fcc_top_dataset(7,'2026-10-01',$now);
 $b=top_fixture('fcc_cc_batches',['batch_key'=>hash('sha256','top-fixture'),'period_month'=>'2026-10-01','published_at'=>'2026-10-01 08:00:00','record_count'=>2]);
 foreach(['999999999991'=>4.567,'999999999992'=>2.123] as $fbo=>$value)top_fixture('fcc_cc_snapshots',['batch_id'=>$b,'fbo_id'=>$fbo,'period_month'=>'2026-10-01','personal_cc'=>$value/2,'total_cc'=>$value*2,'total_active_cc'=>$value,'is_4cc_active'=>1,'captured_at'=>'2026-10-01 08:00:00']);
 $contact=top_fixture('fcc_partner_contacts',['user_id'=>2,'name'=>'Synthetic Top test','created_at'=>'2026-09-30 08:00:00','updated_at'=>'2026-09-30 08:00:00']);
 $opp=top_fixture('fcc_partner_opportunities',['user_id'=>2,'contact_id'=>$contact,'kind'=>'product','article_id'=>42,'journey_step_id'=>null]);
 foreach(['a','b'] as $k){$aid=top_fixture('fcc_partner_activities',['user_id'=>2,'contact_id'=>$contact,'kind'=>'journey_sent','request_key'=>'top-qa-'.$k,'created_at'=>'2026-10-01 08:00:00']);top_fixture('fcc_partner_pilot_events',['user_id'=>2,'opportunity_id'=>$opp,'activity_id'=>$aid,'kind'=>'message_sent','request_key'=>'top-qa-'.$k,'created_at'=>'2026-10-01 08:00:00']);}
 $sid=top_fixture('fcc_webinar_sessions',['series_key'=>'top-qa','occurrence_key'=>'top-qa','title'=>'Synthetic only','starts_at_utc'=>'2026-10-05 10:00:00','ends_at_utc'=>'2026-10-05 11:00:00','access_url'=>'https://example.invalid','calendar_uid'=>'top-qa']);
 $attempts=[];
 foreach(['sent','duplicate','opened','demo','revoked'] as $k)$attempts[$k]=top_fixture('fcc_webinar_invite_attempts',['user_id'=>2,'session_id'=>$sid,'token_hash'=>hash('sha256',$k),'client_request_key'=>md5($k),'payload_hash'=>hash('sha256',$k),'recipient_contact_id'=>$contact,'is_demo'=>$k==='demo'?1:0,'revoked_at'=>$k==='revoked'?'2026-10-01 09:00:00':null,'whatsapp_requested_at'=>'2026-10-01 08:00:00','sent_self_reported_at'=>$k==='opened'?null:'2026-10-01 08:00:00']);
 foreach(['verified','unverified','demo','self','review','cancelled'] as $k)top_fixture('fcc_webinar_registrations',['user_id'=>2,'session_id'=>$sid,'attempt_id'=>$attempts['sent'],'name'=>'Synthetic only','email'=>'qa@example.invalid','phone'=>'','phone_key'=>hash('sha256','p'.$k),'email_key'=>hash('sha256','e'.$k),'management_hash'=>hash('sha256','m'.$k),'verification_hash'=>hash('sha256','v'.$k),'is_demo'=>$k==='demo'?1:0,'is_self'=>$k==='self'?1:0,'needs_review'=>$k==='review'?1:0,'status'=>$k==='cancelled'?'cancelled':'registered','email_verified_at'=>$k==='unverified'?null:'2026-10-01 08:00:00']);
 $d=fcc_top_dataset(7,'2026-10-01',$now);
 $a(($d['scores']['registrations'][2]??0)===($before['scores']['registrations'][2]??0)+1,'Only verified guest counts; demo, self, review and cancellation excluded');
 $a(($d['scores']['conversations'][2]??0)===($before['scores']['conversations'][2]??0)+1,'Multiple contact actions count as one conversation');
 $a(($d['scores']['consistency'][2]??0)<=($before['scores']['consistency'][2]??0)+1&&($d['scores']['consistency'][2]??0)>0,'Multiple actions count one active day without MySQL timezone tables');
 $own2=fcc_top_own($d['lists']['total_active_cc'],2);$own3=fcc_top_own($d['lists']['total_active_cc'],3);
 $a($own2['entity']===$own3['entity']&&$own2['score']==4.567,'Shared Forever number counts exactly once');
 $a((float)$own2['score']===(float)$d['cc']['999999999991']['total_active_cc']&&(float)$own2['score']!==(float)$d['cc']['999999999991']['total_cc'],'Monthly ranking uses Total Active CC rather than Total CC');
 $a((float)$own2['score']!==(float)$d['cc']['999999999991']['personal_cc'],'Monthly activity is not replaced by personal CC');
 $a(!array_filter(fcc_top_public($d['lists']['total_active_cc'],'total_active_cc',2),fn($r)=>$r['own']),'Shared CC remains hidden until every linked account opts in');
 fcc_partner_query('UPDATE fcc_top_profiles SET visible=1 WHERE user_id=3');$d=fcc_top_dataset(7,'2026-10-01',$now);
 $a(count(array_filter(fcc_top_public($d['lists']['total_active_cc'],'total_active_cc',2),fn($r)=>$r['own']))===1,'Shared account appears once after all owners opt in');
 $empty=fcc_top_dataset(7,'2026-09-01',$now);$a(fcc_top_own($empty['lists']['total_active_cc'],2)['score']===null,'CC months never leak into another month');
 $new=fcc_top_dataset(7,'2026-10-01',$now->modify('+8 days'));$a(($new['scores']['registrations'][2]??0)===0,'Events outside selected window are excluded');
 fcc_top_snapshot($d);fcc_top_snapshot($d);$a((int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_top_positions WHERE day=? AND category=? AND period_key=? AND entity_key=?',[$d['window']['day'],'total_active_cc','2026-10-01',$own2['entity']])['n']===1,'Daily rank snapshot is idempotent');
 $baseline=fcc_top_profile(2);$a($baseline['weekly_notice']==0,'Visibility never subscribes notifications');

 $month=substr(fcc_partner_today(),0,7).'-01';$previous=(new DateTimeImmutable($month))->modify('-1 month')->format('Y-m-d');
 $page=fcc_top_page(1,30,'');$a($page['month']===$month,'Default page uses current calendar month');
 $page=fcc_top_page(1,30,$month);$a($page['month']===$month,'Explicit current month is preserved');
 fcc_partner_query('INSERT INTO fcc_top_profiles(user_id,visible,version) SELECT user_id,0,1 FROM users WHERE status=1 ON DUPLICATE KEY UPDATE visible=0,version=1');
 $hidden=fcc_top_page(1,30,'2026-10-01');$ds=fcc_top_dataset(30,'2026-10-01');
 $a($hidden['cards']['total_active_cc']['count']>=1,'Hidden accounts still contribute to positive CC count');
 foreach($hidden['cards'] as $cat=>$card){
  $a($card['count']===count($ds['lists'][$cat]),'Aggregate count matches source for '.$cat);
  $a($card['visible_count']===0&&$card['rows']===[],'Hidden names stay absent for '.$cat);
 }
 fcc_partner_query('UPDATE fcc_top_profiles SET visible=1 WHERE user_id IN (2,3,4)');
 $visible=fcc_top_page(1,30,'2026-10-01');
 $a($visible['cards']['total_active_cc']['count']===$hidden['cards']['total_active_cc']['count'],'Visibility cannot change result count');
 $a($visible['cards']['total_active_cc']['visible_count']===1,'Shared Forever owners count as one visible account');
 $a(count($visible['cards']['total_active_cc']['rows'])===1,'Opted in names populate ranking');
 foreach($visible['cards']['total_active_cc']['rows'] as $row)$a($row['score']===null,'Visible CC amounts remain private');

 // Users without a saved choice participate automatically. An explicit opt out remains effective.
 fcc_partner_query('DELETE FROM fcc_top_profiles WHERE user_id IN (2,3)');
 $profile=fcc_top_profile(2);$auto=fcc_top_dataset(30,'2026-10-01');
 $a($profile['visible']===1&&!$profile['weekly_notice']&&!$profile['milestone_notice'],'Unsaved profile defaults visible without subscribing notifications');
 $a($auto['users'][2]['visible']&&$auto['users'][3]['visible'],'Missing profiles participate automatically');
 fcc_partner_query('INSERT INTO fcc_top_profiles(user_id,visible,version) VALUES(2,0,0)');
 $auto=fcc_top_dataset(30,'2026-10-01');
 $a(fcc_top_profile(2)['visible']===1&&$auto['users'][2]['visible'],'Untouched legacy default becomes visible');
 fcc_partner_query('UPDATE fcc_top_profiles SET version=1 WHERE user_id=2');
 $optout=fcc_top_dataset(30,'2026-10-01');
 $a(!fcc_top_profile(2)['visible']&&!$optout['users'][2]['visible'],'Saved opt out stays hidden');
 $a(count($optout['lists']['total_active_cc'])===count($auto['lists']['total_active_cc']),'Opt out leaves aggregate CC counts unchanged');
 $a(!array_filter(fcc_top_public($optout['lists']['total_active_cc'],'total_active_cc',2),fn($r)=>$r['own']),'Shared Forever account respects one owner opting out');
 fcc_partner_query('DELETE FROM fcc_top_profiles WHERE user_id=2');
 fcc_partner_query("INSERT INTO forever_business_members(fbo_id,name,is_privacy_requested,created_at,updated_at) VALUES('999999999991','Synthetic privacy fixture',1,UTC_TIMESTAMP(),UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE is_privacy_requested=1");
 $private=fcc_top_dataset(30,'2026-10-01');
 $a(!$private['users'][2]['visible']&&!$private['users'][3]['visible'],'Existing privacy request overrides default visibility');
 // Current month starts from its own verified snapshot, including an exact zero.
 fcc_partner_query("UPDATE users SET preferences=JSON_SET(COALESCE(preferences,'{}'),'$.meta.foreverId','999999999991') WHERE user_id=1");
 $oldBatch=top_fixture('fcc_cc_batches',['batch_key'=>hash('sha256','top-previous-month-fixture'),'period_month'=>'2026-09-01','published_at'=>'2026-09-30 08:00:00','record_count'=>1]);
 top_fixture('fcc_cc_snapshots',['batch_id'=>$oldBatch,'fbo_id'=>'999999999991','period_month'=>'2026-09-01','personal_cc'=>17.777,'total_cc'=>123.456,'total_active_cc'=>17.777,'is_4cc_active'=>1,'captured_at'=>'2026-09-30 08:00:00']);
 $zeroBatch=top_fixture('fcc_cc_batches',['batch_key'=>hash('sha256','top-zero-month-fixture'),'period_month'=>'2026-10-01','published_at'=>'2026-10-01 10:00:00','record_count'=>1]);
 top_fixture('fcc_cc_snapshots',['batch_id'=>$zeroBatch,'fbo_id'=>'999999999991','period_month'=>'2026-10-01','personal_cc'=>0,'total_cc'=>0,'total_active_cc'=>0,'is_4cc_active'=>0,'captured_at'=>'2026-10-01 10:00:00']);
 $currentPage=fcc_top_page(1,30,'');$archive=fcc_top_page(1,30,'2026-09-01');
 $a(array_keys($currentPage['cards'])===['total_active_cc','registrations','conversations','education','consistency'],'Page excludes personal CC, sent product links and invitation confirmations');
 $a(array_keys($currentPage['weekly'])===['education'],'Weekly goals exclude unmeasurable sharing metrics');
 $a(!isset($private['scores']['recommendations'])&&!isset($private['scores']['invitations']),'Retired sharing metrics are no longer calculated');
 $a($currentPage['month']==='2026-10-01'&&(float)$currentPage['cards']['total_active_cc']['own']['score']===0.0&&(float)$currentPage['cards']['total_active_cc']['own']['score']===0.0,'Current month zero never falls back to previous CC');
 $a((float)$archive['cards']['total_active_cc']['own']['score']===17.777,'Previous month remains available only when selected');
 $next=fcc_top_dataset(30,'',new DateTimeImmutable('2026-10-31 23:01:00',new DateTimeZone('UTC')));
 $a($next['month']==='2026-11-01'&&$next['months'][1]==='2026-10-01','Month rollover follows Zagreb calendar midnight');
 $a(fcc_top_own($next['lists']['total_active_cc'],1)['score']===null,'Missing new month data never reuses old month points');
 $year=fcc_top_dataset(30,'',new DateTimeImmutable('2026-12-31 23:01:00',new DateTimeZone('UTC')));
 $a($year['month']==='2027-01-01'&&$year['months'][1]==='2026-12-01','Year rollover selects January and preserves December archive');

 // A new monthly metric starts a new notification baseline, without false achievements.
 $notificationUsers=fcc_partner_rows('SELECT user_id FROM users WHERE status=1 ORDER BY user_id LIMIT 10');
 $noticeBatch=top_fixture('fcc_cc_batches',['batch_key'=>hash('sha256','top-active-notice-fixture'),'period_month'=>'2026-10-01','published_at'=>'2026-10-01 11:00:00','record_count'=>10]);
 foreach($notificationUsers as $index=>$u){$uid=(int)$u['user_id'];$fbo=(string)(600000000000+$uid);fcc_partner_query("UPDATE users SET preferences=JSON_SET(COALESCE(preferences,'{}'),'$.meta.foreverId',?) WHERE user_id=?",[$fbo,$uid]);top_fixture('fcc_cc_snapshots',['batch_id'=>$noticeBatch,'fbo_id'=>$fbo,'period_month'=>'2026-10-01','personal_cc'=>0.5,'total_cc'=>200,'total_active_cc'=>100-$index,'is_4cc_active'=>1,'captured_at'=>'2026-10-01 11:00:00']);}
 fcc_partner_query('INSERT INTO fcc_top_profiles(user_id,visible,weekly_notice,milestone_notice,version) VALUES(1,1,0,1,1) ON DUPLICATE KEY UPDATE weekly_notice=0,milestone_notice=1');
 fcc_partner_query("DELETE FROM fcc_partner_notifications WHERE user_id=1 AND event_key LIKE 'top:%'");
 $noticeDataset=fcc_top_dataset(30,'2026-10-01',$now);$oldRanks=['total_cc'=>1];
 foreach($noticeDataset['lists'] as $category=>$rows)if($category!=='total_active_cc')$oldRanks[$category]=count($rows)>=10?fcc_top_own($rows,1)['position']:null;
 fcc_partner_query("INSERT INTO fcc_top_state(user_id,checked_day,month_key,ranks_json) VALUES(1,'2026-09-30','2026-10-01',?) ON DUPLICATE KEY UPDATE checked_day=VALUES(checked_day),month_key=VALUES(month_key),ranks_json=VALUES(ranks_json)",[json_encode($oldRanks)]);
 fcc_top_notify_user(1,$now);
 $a((int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=1 AND event_key LIKE 'top:milestone:%'")['n']===0,'Changing from Total CC to monthly activity does not announce a false Top 10 achievement');
 $newState=json_decode(fcc_partner_one('SELECT ranks_json FROM fcc_top_state WHERE user_id=1')['ranks_json'],true);
 $a(($newState['total_active_cc']??null)===1&&!array_key_exists('total_cc',$newState),'Monthly activity receives its own notification baseline');
}finally{database()->rollback();fcc_partner_query("SELECT RELEASE_LOCK('fcc_partner_notifications')");}
echo json_encode(['status'=>'passed','checks'=>count($checks),'details'=>$checks],JSON_PRETTY_PRINT).PHP_EOL;
