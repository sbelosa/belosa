<?php
if(PHP_SAPI!=='cli'||getenv('FCC_LOCAL')!=='1')exit(1);
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require dirname(__DIR__,2).'/app/init.php';
if(DATABASE_NAME!=='fcc_partner_local'||DATABASE_SERVER!=='db')exit(1);
Altum\Cache::initialize();Altum\Plugin::initialize();$checks=[];
set_exception_handler(function($e){fwrite(STDERR,$e->getMessage().' at '.$e->getLine().PHP_EOL);exit(1);});
$a=function($ok,$label)use(&$checks){if(!$ok)throw new RuntimeException($label);$checks[]=$label;};
function qa_insert(string $table,array $values): int {
 foreach(fcc_partner_rows("SHOW COLUMNS FROM `$table`") as $c)if($c['Null']==='NO'&&$c['Default']===null&&$c['Extra']!=='auto_increment'&&!array_key_exists($c['Field'],$values))$values[$c['Field']]=str_contains($c['Type'],'int')?0:(str_contains($c['Type'],'date')?'2026-10-01 08:00:00':'');
 $keys=implode(',',array_map(fn($k)=>"`$k`",array_keys($values)));$q=database()->prepare("INSERT INTO `$table` ($keys) VALUES(".implode(',',array_fill(0,count($values),'?')).')');$p=array_values($values);$q->bind_param(str_repeat('s',count($p)),...$p);if(!$q->execute())throw new RuntimeException($table.': '.$q->error);return (int)database()->insert_id;
}
fcc_partner_query("SELECT GET_LOCK('fcc_partner_notifications',10)");database()->begin_transaction();
try {
 $uid=2;$now=new DateTimeImmutable('2026-10-01 23:30:00',new DateTimeZone('Europe/Zagreb'));
 foreach(['forever_business_daily_outcomes'=>'recorded_by_user_id','fcc_partner_pilot_events'=>'user_id','fcc_partner_journey_events'=>'user_id','fcc_partner_activities'=>'user_id'] as $table=>$field)fcc_partner_query("DELETE FROM `$table` WHERE `$field`=?",[$uid]);
 $dataset=fn()=>fcc_top_dataset(30,'2026-10-01',$now);$score=fn($d,$cat)=>(int)($d['scores'][$cat][$uid]??0);
 $a($score($dataset(),'education')===0,'Empty new education starts at zero');
 $outcome=function($cycle,$step,$position,$key,$at='2026-10-01 08:00:00',$extra=[])use($uid){return qa_insert('forever_business_daily_outcomes',array_merge(['fbo_id'=>'999999999992','action_date'=>substr($at,0,10),'core_key'=>'Development','action_key'=>$key,'status'=>'done','recorded_by_user_id'=>$uid,'journey_cycle_id'=>$cycle?:null,'journey_step_id'=>$step?:null,'sequence_position'=>$position,'outcome_type'=>$cycle?'journey90':'starter','created_at'=>$at,'updated_at'=>$at],$extra));};
 $outcome(0,0,1,'legacy-done');$a($score($dataset(),'education')===0,'Old education completion is excluded');$a($score($dataset(),'consistency')===0,'Old education does not inflate new work days');
 $cycles=[];foreach([FCC_JP_PREVIOUS_VERSION,FCC_JP_V7_VERSION,FCC_JP_VERSION,'legacy-60','fcc90-2026-09-24-v4'] as $v)$cycles[$v]=qa_insert('fcc_partner_journey_cycles',['user_id'=>$uid,'catalog_version'=>$v,'policy_version'=>'qa','started_at'=>'2026-09-20 08:00:00','status'=>'completed']);
 $step=function($cycle,$pos,$key,$owner=2){return qa_insert('fcc_partner_journey_steps',['cycle_id'=>$cycle,'user_id'=>$owner,'position'=>$pos,'content_json'=>json_encode(['id'=>$key]),'opened_at'=>'2026-10-01 07:00:00']);};
 $expected=0;foreach([FCC_JP_PREVIOUS_VERSION,FCC_JP_V7_VERSION,FCC_JP_VERSION] as $v){$c=$cycles[$v];$key='new-'.(++$expected);$id=$step($c,1,$key);$outcome($c,$id,1,$key);$a($score($dataset(),'education')===$expected,'Compatible current program progress counts for '.$v);}
 $d=$dataset();$a($score($d,'consistency')===1,'Several completed steps on one day count as one work day');
 $duplicateRejected=false;try{$outcome($c,$id,1,$key,'2026-10-01 09:00:00',['action_date'=>'2026-09-30']);}catch(RuntimeException $e){$duplicateRejected=str_contains($e->getMessage(),'journey_single_step');}$a($duplicateRejected&&$score($dataset(),'education')===3,'Duplicate completion is rejected without adding a step');
 foreach(['legacy-60','fcc90-2026-09-24-v4'] as $v){$old=$step($cycles[$v],1,$v);$outcome($cycles[$v],$old,1,$v);}$a($score($dataset(),'education')===3,'Earlier distinct curricula are excluded even with linked steps');
 $bad=$step($c,2,'unfinished');$outcome($c,$bad,2,'unfinished','2026-10-01 08:00:00',['status'=>'pending']);$a($score($dataset(),'education')===3,'Unfinished new step excluded');
 $badContent=$step($c,7,'original-content');$outcome($c,$badContent,7,'mismatched-content');$a($score($dataset(),'education')===3,'Outcome must match saved task identity');
 $other=$step($c,3,'wrong-owner',3);$outcome($c,$other,3,'wrong-owner');$a($score($dataset(),'education')===3,'Cross-account step linkage excluded');
 $wrongCycle=$cycles[FCC_JP_V7_VERSION];$badCycleStep=$step($c,8,'bad-cycle');$outcome($wrongCycle,$badCycleStep,8,'bad-cycle');$a($score($dataset(),'education')===3,'Mismatched cycle linkage excluded');
 $outside=$step($c,4,'outside-window');$outcome($c,$outside,4,'outside-window','2026-08-31 21:59:59');$a($score($dataset(),'education')===3,'Completed steps outside window excluded');
 $future=$step($c,5,'future-step');$outcome($c,$future,5,'future-step','2026-10-02 08:00:00');$a($score($dataset(),'education')===3,'Future completion excluded');
 $badpos=$step($c,91,'position-91');$outcome($c,$badpos,91,'position-91');$a($score($dataset(),'education')===3,'Program cannot gain a 91st task');
 $prep=$step($c,6,'saved-preparation');
 qa_insert('fcc_partner_journey_events',['user_id'=>$uid,'event_key'=>'qa-draft','event_type'=>'draft_saved','action_key'=>'j90:'.$c.':'.$prep,'content_version'=>FCC_JP_VERSION,'created_at'=>'2026-09-29 22:30:00']);
 $a($score($dataset(),'consistency')===2,'Saved preparation counts on its Zagreb date');$a($score($dataset(),'education')===3,'Saving preparation is not a completed step');
 qa_insert('fcc_partner_pilot_events',['user_id'=>$uid,'step_id'=>$prep,'kind'=>'preparation_reported','source'=>'self_reported','request_key'=>'qa-prep','created_at'=>'2026-09-30 10:00:00']);$a($score($dataset(),'consistency')===2,'Preparation and draft on the same Zagreb day count once');
 qa_insert('fcc_partner_pilot_events',['user_id'=>$uid,'step_id'=>$prep,'kind'=>'practice','source'=>'self_reported','request_key'=>'qa-practice','created_at'=>'2026-09-28 18:00:00']);$a($score($dataset(),'consistency')===3,'Recorded practice adds a work day');
 qa_insert('fcc_partner_pilot_events',['user_id'=>$uid,'step_id'=>$prep,'kind'=>'share_intent','source'=>'self_reported','request_key'=>'qa-link','created_at'=>'2026-09-27 18:00:00']);
 qa_insert('fcc_partner_journey_events',['user_id'=>$uid,'event_key'=>'qa-open','event_type'=>'started','action_key'=>'j90:'.$c.':'.$prep,'content_version'=>FCC_JP_VERSION,'created_at'=>'2026-09-26 18:00:00']);$a($score($dataset(),'consistency')===3,'Opening links and steps alone does not count');
 qa_insert('fcc_partner_pilot_events',['user_id'=>$uid,'step_id'=>$prep,'kind'=>'preparation_reported','source'=>'excluded_result','request_key'=>'qa-demo','created_at'=>'2026-09-25 18:00:00']);$a($score($dataset(),'consistency')===3,'Excluded demo result does not count');
 $contact=qa_insert('fcc_partner_contacts',['user_id'=>$uid,'name'=>'Synthetic contact','created_at'=>'2026-09-20 08:00:00','updated_at'=>'2026-09-20 08:00:00']);
 foreach(['first','repeat'] as $suffix)qa_insert('fcc_partner_activities',['user_id'=>$uid,'contact_id'=>$contact,'kind'=>'contacted','request_key'=>'qa-contact-'.$suffix,'created_at'=>'2026-09-24 18:00:00']);
 $d=$dataset();$a($score($d,'consistency')===4&&$score($d,'conversations')===1,'Repeated contact action counts one day and one communication');
 qa_insert('fcc_partner_activities',['user_id'=>$uid,'kind'=>'journey_performed','request_key'=>'qa-publication','created_at'=>'2026-09-23 18:00:00']);$a($score($dataset(),'consistency')===5,'Recorded publication without a contact counts as work');
 qa_insert('fcc_partner_activities',['user_id'=>$uid,'contact_id'=>$contact,'kind'=>'webinar_registration','request_key'=>'qa-guest','created_at'=>'2026-09-22 18:00:00']);$a($score($dataset(),'consistency')===5,'Passive guest registration does not create owner work day');
 $a($score(fcc_top_dataset(7,'2026-10-01',$now),'consistency')===3,'Seven day window excludes older work days');
 $a(fcc_top_period_key('education',$d)==='v2:30'&&fcc_top_period_key('consistency',$d)==='v2:30','Revised metrics do not compare ranks against old definitions');
 foreach([0,3,9,10,15] as $positive){$rows=[];for($i=0;$i<20;$i++)$rows[]=['entity'=>(string)$i,'uids'=>[$i+1],'name'=>'Synthetic '.$i,'visible'=>true,'score'=>$i<$positive?$positive-$i:0];$public=fcc_top_public($rows,'total_active_cc',0);$a(count($public)===min(10,$positive),'CC list shows only positive posted records, never pads to ten: '.$positive);foreach($public as $r)$a($r['score']===null,'Other user CC amount remains private');}
}finally{database()->rollback();fcc_partner_query("SELECT RELEASE_LOCK('fcc_partner_notifications')");}
echo json_encode(['status'=>'passed','checks'=>count($checks),'details'=>$checks],JSON_PRETTY_PRINT).PHP_EOL;
