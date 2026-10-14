<?php
/** Custom code: FC-2026-10-14: offline worker cache regression guard. */
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);

$source=$argv[1]??dirname(__DIR__);
$output=$argv[2]??sys_get_temp_dir().'/fcc-worker-cache-'.getmypid().'.json';
$relative=['app/helpers/fcc_team.php','app/helpers/fcc_partner_notifications.php','app/helpers/fcc_webinar_events.php'];
$before=[];foreach($relative as $file){if(!is_file($source.'/'.$file))throw new RuntimeException('Missing source.');$before[$file]=hash_file('sha256',$source.'/'.$file);}

function actual_function(string $path,string $name): string {
 $tokens=token_get_all(file_get_contents($path));$count=count($tokens);
 for($i=0;$i<$count;$i++){
  if(!is_array($tokens[$i])||$tokens[$i][0]!==T_FUNCTION)continue;
  $j=$i+1;while($j<$count&&(is_array($tokens[$j])&&$tokens[$j][0]===T_WHITESPACE||$tokens[$j]==='&'))$j++;
  if(!is_array($tokens[$j])||$tokens[$j][0]!==T_STRING||$tokens[$j][1]!==$name)continue;
  $text='';$depth=0;$opened=false;
  for($k=$i;$k<$count;$k++){
   $token=$tokens[$k];$text.=is_array($token)?$token[1]:$token;
   if($token==='{'){$depth++;$opened=true;}elseif($token==='}'&&$opened&&--$depth===0)return $text;
  }
 }
 throw new RuntimeException('Required actual function missing: '.$name);
}
$actual=[
 'app/helpers/fcc_team.php'=>['fcc_team_graph'],
 'app/helpers/fcc_partner_notifications.php'=>['fcc_partner_notifications_run'],
 'app/helpers/fcc_webinar_events.php'=>['fcc_event_definition','fcc_event_now','fcc_event_member','fcc_event_cc','fcc_event_access','fcc_event_job','fcc_event_quiet_due','fcc_event_reconcile_member','fcc_events_tick'],
];
$functionHashes=[];foreach($actual as $file=>$names)foreach($names as $name){$code=actual_function($source.'/'.$file,$name);$functionHashes[$name]=hash('sha256',$code);eval($code);}

/* Explicit boundary substitutes. Every SQL operation is in-memory and allowlisted. */
function reset_fixture(): void {
 foreach(['fcc_team_graph_run','fcc_event_reconcile_users','fcc_event_reconcile_cc','fcc_mw_scope_series','fcc_mw_graph'] as $key)unset($GLOBALS[$key]);
 $GLOBALS['f']=['ready'=>true,'accounts_ready'=>false,'lock'=>true,'graph_reads'=>0,'account_reads'=>0,'raw_reads'=>[],'cc_reads'=>[],'auth_reads'=>0,'hierarchy_reads'=>0,'pref_reads'=>0,'push_calls'=>0,'job_calls'=>0,'wl_calls'=>0,'sync_calls'=>0,'release_calls'=>0,'writes'=>[],'config'=>[],'access'=>[],'jobs'=>[],'throw'=>null,'throw_at'=>'','rights'=>[1=>true,2=>true],'hierarchy'=>[],
  'users'=>[1=>['user_id'=>1,'status'=>1,'type'=>0,'preferences'=>['fbo'=>'001234567890']],2=>['user_id'=>2,'status'=>1,'type'=>0,'preferences'=>['fbo'=>'991234567890']]],
  'graph_rows'=>[['user_id'=>1,'name'=>'Synthetic','status'=>1,'type'=>0,'preferences'=>[],'fbo_id'=>'001234567890','is_manager'=>0]],'accounts'=>[],
  'metrics'=>[],'sessions'=>[],'delivery_allowed'=>null,'delivery_user'=>null,'delivery_cc'=>null,'delivery_graph'=>null,'mutate_before_delivery'=>false];
 putenv('FCC_PARTNER_ENABLED=1');putenv('FCC_PHONE_PILOT=0');
}
function counter(string $key,string|int $index):void{$GLOBALS['f'][$key][$index]=($GLOBALS['f'][$key][$index]??0)+1;}
function maybe_throw(string $at):void{if($GLOBALS['f']['throw_at']===$at)throw $GLOBALS['f']['throw'];}
function fcc_team_ready():bool{return $GLOBALS['f']['ready'];}
function fcc_team_accounts_ready():bool{return $GLOBALS['f']['accounts_ready'];}
function fcc_team_apply_accounts(array $graph,array $accounts):array{foreach($accounts as $row)$graph[$row['user_id']]['primary_user_id']=$row['primary_user_id'];return $graph;}
function fcc_team_fbo(array $record):string{return $record['fbo_id'];}
function forever_business_user_fbo_sql_expression(string $column):string{return $column;}
function forever_business_extract_user_fbo_id($preferences):string{return $preferences['fbo']??'';}
function forever_business_has_verified_four_cc_activity(array $metric):bool{
 if($metric['is_4cc_active']!==null)return (int)$metric['is_4cc_active']===1;
 return $metric['personal_cc']!==null&&$metric['total_active_cc']!==null&&(float)$metric['personal_cc']>=1&&(float)$metric['total_active_cc']>=4;
}
function fcc_rollout_public_owner(int $uid):bool{$GLOBALS['f']['auth_reads']++;return $GLOBALS['f']['rights'][$uid]??false;}
function fcc_mw_series(array $session):?array{return null;}
function fcc_mw_allowed(int $uid,array $series):bool{throw new LogicException('Unexpected managed scope.');}
function fcc_event_defaults(string $kind='business'):array{return ['team_root'=>'','audience'=>'team','selected'=>[],'announce'=>0,'reminders'=>[],'time_pending'=>false];}
function fcc_events_enabled():bool{return true;}
function fcc_partner_notification_config(string $key,$default=null){return $GLOBALS['f']['config'][$key]??$default;}
function fcc_partner_notification_config_save(string $key,$value):void{$GLOBALS['f']['config'][$key]=$value;}
function fcc_partner_push_mode():string{return 'offline';}
function fcc_event_transaction(callable $callback){return $callback();}
function fcc_event_materialize(array $series,DateTimeImmutable $now):void{throw new LogicException('Unexpected materialization.');}
function fcc_partner_rows(string $sql,array $args=[]):array{
 $f=&$GLOBALS['f'];
 if(str_starts_with($sql,'SELECT u.user_id,u.name,u.status,u.type,u.preferences,t.fbo_id')){$f['graph_reads']++;maybe_throw('graph');return $f['graph_rows'];}
 if($sql==='SELECT * FROM fcc_team_accounts WHERE active=1'){$f['account_reads']++;return $f['accounts'];}
 if(str_starts_with($sql,'SELECT user_id,type FROM users WHERE status=1')){maybe_throw('notification_users');return array_map(fn($u)=>['user_id'=>$u['user_id'],'type'=>$u['type']],array_values(array_filter($f['users'],fn($u)=>$u['status']===1&&$u['user_id']>(int)$args[0])));}
 if(str_starts_with($sql,"SELECT * FROM fcc_webinar_series WHERE status='published'"))return [];
 if(str_starts_with($sql,'SELECT * FROM fcc_webinar_sessions WHERE series_id IS NOT NULL'))return $f['sessions'];
 if(str_starts_with($sql,'SELECT user_id FROM users WHERE status=1'))return array_map(fn($u)=>['user_id'=>$u['user_id']],array_values(array_filter($f['users'],fn($u)=>$u['status']===1&&$u['user_id']>(int)$args[0])));
 throw new LogicException('Unstubbed rows query.');
}
function fcc_partner_one(string $sql,array $args=[]):?array{
 $f=&$GLOBALS['f'];
 if(str_starts_with($sql,"SELECT GET_LOCK('fcc_partner_notifications'"))return ['acquired'=>$f['lock']?1:0];
 if($sql==='SELECT user_id,status,type,preferences FROM users WHERE user_id=?'){counter('raw_reads',(int)$args[0]);maybe_throw('raw');return $f['users'][(int)$args[0]]??null;}
 if(str_starts_with($sql,'SELECT m.*,i.report_kind FROM forever_business_metrics')){$key=$args[0].'|'.$args[1];counter('cc_reads',$key);return $f['metrics'][$key]??null;}
 if(str_starts_with($sql,'SELECT descendant_fbo_id FROM forever_business_hierarchy')){$f['hierarchy_reads']++;return isset($f['hierarchy'][$args[0].'|'.$args[1]])?['descendant_fbo_id'=>$args[1]]:null;}
 if(str_starts_with($sql,'SELECT * FROM fcc_webinar_member_access')){$f['pref_reads']++;return $f['access'][$args[0].'|'.$args[1]]??null;}
 if(str_starts_with($sql,'SELECT state FROM fcc_webinar_member_access'))return $f['access'][$args[0].'|'.$args[1]]??null;
 throw new LogicException('Unstubbed one query.');
}
function fcc_partner_query(string $sql,array $args=[]):void{
 $f=&$GLOBALS['f'];
 if(str_starts_with($sql,"SELECT RELEASE_LOCK('fcc_partner_notifications'")){$f['release_calls']++;return;}
 if(str_starts_with($sql,'INSERT INTO fcc_webinar_member_access')){maybe_throw('reconcile');$key=$args[0].'|'.$args[1];$f['access'][$key]=array_replace($f['access'][$key]??[],['state'=>$args[2],'reason'=>$args[3],'checked_at'=>$args[4]]);$f['writes'][]=['access',$args];return;}
 if(str_starts_with($sql,'INSERT IGNORE INTO fcc_webinar_event_jobs')){$f['jobs'][$args[4]]=$args;$f['writes'][]=['job',$args];return;}
 if(str_starts_with($sql,'UPDATE fcc_webinar_sessions SET member_cursor=')){$f['writes'][]=['cursor',$args];return;}
 if(str_starts_with($sql,'INSERT IGNORE INTO fcc_partner_push_deliveries')){$f['writes'][]=['push_pairs',[]];return;}
 throw new LogicException('Unstubbed write query.');
}
function fcc_partner_notification_sync_user(array $user,DateTimeImmutable $now):void{$GLOBALS['f']['sync_calls']++;fcc_team_graph();fcc_team_graph();maybe_throw('sync');}
function fcc_partner_push_deliver(DateTimeImmutable $now):array{
 $f=&$GLOBALS['f'];$f['push_calls']++;$f['push_scope_absent']=!array_key_exists('fcc_team_graph_run',$GLOBALS);
 $f['graph_rows'][0]['status']=0;$f['delivery_graph']=fcc_team_graph();maybe_throw('push');return ['sent'=>0,'simulated'=>0,'failed'=>0];
}
function fcc_event_run_jobs(?DateTimeImmutable $now=null):array{
 $f=&$GLOBALS['f'];$f['job_calls']++;$f['jobs_scope_absent']=!array_key_exists('fcc_event_reconcile_users',$GLOBALS)&&!array_key_exists('fcc_event_reconcile_cc',$GLOBALS);
 if($f['mutate_before_delivery']){$f['users'][1]['status']=0;$f['metrics']['001234567890|2031-04-01']=metric(0);}
 if($f['sessions']){$f['delivery_user']=fcc_event_member(1,fcc_event_definition($f['sessions'][0]));$f['delivery_cc']=fcc_event_cc('001234567890','2031-04-01');$f['delivery_allowed']=fcc_event_access(1,$f['sessions'][0],$now)['allowed'];}
 maybe_throw('jobs');return ['inbox'=>0];
}
function fcc_wl_tick(?DateTimeImmutable $now=null):int{$GLOBALS['f']['wl_calls']++;$GLOBALS['f']['wl_scope_absent']=!array_key_exists('fcc_event_reconcile_users',$GLOBALS)&&!array_key_exists('fcc_event_reconcile_cc',$GLOBALS);maybe_throw('wl');return 0;}
function metric(?int $signal=1,?float $personal=1,?float $total=4,?int $import=1,string $kind='downline'):array{return ['source_import_id'=>$import,'report_kind'=>$kind,'is_4cc_active'=>$signal,'personal_cc'=>$personal,'total_active_cc'=>$total,'updated_at'=>'2031-04-14 10:00:00'];}
function session(int $id,string $root='',string $audience='current',array $extra=[]):array{return ['id'=>$id,'series_id'=>$id,'status'=>'scheduled','member_cursor'=>0,'revision'=>1,'starts_at_utc'=>'2031-04-15 17:00:00','ends_at_utc'=>'2031-04-15 18:00:00','event_json'=>json_encode(array_replace(['team_root'=>$root,'audience'=>$audience,'announce'=>1,'reminders'=>[]],$extra))];}

$checks=[];$scenarios=0;
function check(bool $ok,string $label):void{$GLOBALS['checks'][]=['label'=>$label,'pass'=>$ok];}
function scenario(string $name,callable $callback):void{$GLOBALS['scenarios']++;reset_fixture();try{$callback();}catch(Throwable $e){check(false,$name.'_unexpected_exception_'.get_class($e));}}
function same_scope(string $key,bool $present,$value,string $label):void{check(array_key_exists($key,$GLOBALS)===$present&&(!$present||$GLOBALS[$key]===$value),$label);}
function seed_prior(string $mode):array{
 $prior=$mode==='array'?['fcc_team_graph_run'=>[1=>['status'=>9]],'fcc_event_reconcile_users'=>[1=>['user_id'=>1,'status'=>9,'type'=>1,'preferences'=>['fbo'=>'000000000001']]],'fcc_event_reconcile_cc'=>['001234567890|2031-04-01'=>['state'=>'eligible','source'=>'stale','at'=>null]]]:['fcc_team_graph_run'=>null,'fcc_event_reconcile_users'=>null,'fcc_event_reconcile_cc'=>null];
 if($mode!=='absent')foreach($prior as $key=>$value)$GLOBALS[$key]=$value;
 return $prior;
}
$now=new DateTimeImmutable('2031-04-14 10:00:00',new DateTimeZone('UTC'));
scenario('graph_plain',function(){fcc_team_graph();fcc_team_graph();check($GLOBALS['f']['graph_reads']===2,'graph_without_scope_reads_each_call');});
scenario('graph_lazy',function(){
 $GLOBALS['fcc_team_graph_run']=null;$GLOBALS['f']['accounts_ready']=true;$GLOBALS['f']['accounts']=[['user_id'=>1,'primary_user_id'=>2]];
 $a=fcc_team_graph();$b=fcc_team_graph();check($GLOBALS['f']['graph_reads']===1,'lazy_graph_built_once');check($GLOBALS['f']['account_reads']===1,'accounts_applied_once');check($a===$b&&$a[1]['primary_user_id']===2,'graph_reuses_completed_account_mapping');
});
scenario('graph_unready',function(){$GLOBALS['fcc_team_graph_run']=null;$GLOBALS['f']['ready']=false;check(fcc_team_graph()===[]&&fcc_team_graph()===[],'unready_graph_empty');check($GLOBALS['fcc_team_graph_run']===[]&&$GLOBALS['f']['graph_reads']===0,'empty_graph_is_memoized');});
scenario('graph_throw',function(){$GLOBALS['fcc_team_graph_run']=null;$e=new RuntimeException('SYNTHETIC');$GLOBALS['f']['throw']=$e;$GLOBALS['f']['throw_at']='graph';try{fcc_team_graph();check(false,'graph_throw_occurs');}catch(Throwable $caught){check($caught===$e&&$GLOBALS['fcc_team_graph_run']===null,'graph_failure_does_not_publish_partial_cache');}});
scenario('member_decisions',function(){
 $GLOBALS['fcc_event_reconcile_users']=[];$a=fcc_event_member(1,['team_root'=>'']);$b=fcc_event_member(1,['team_root'=>'999999999999']);
 check($a!==null&&$b===null,'member_root_decision_is_not_cached_by_uid');check($GLOBALS['f']['raw_reads'][1]===1,'raw_member_shared_once');check($GLOBALS['f']['auth_reads']===2&&$GLOBALS['f']['hierarchy_reads']===1,'authorization_and_hierarchy_stay_fresh');
 $GLOBALS['f']['rights'][1]=false;check(fcc_event_member(1,['team_root'=>''])===null,'new_rollout_denial_wins_cached_raw_user');
 fcc_event_member(99,['team_root'=>'']);fcc_event_member(99,['team_root'=>'']);check($GLOBALS['f']['raw_reads'][99]===1&&array_key_exists(99,$GLOBALS['fcc_event_reconcile_users'])&&$GLOBALS['fcc_event_reconcile_users'][99]===null,'missing_raw_member_null_is_cached');
});
scenario('session_overrides',function()use($now){
 $GLOBALS['fcc_event_reconcile_users']=[];$s1=session(10,'','team');$s2=session(11,'','team');$GLOBALS['f']['access']['11|1']=['override_value'=>'exclude'];
 check(fcc_event_access(1,$s1,$now)['allowed']&&!fcc_event_access(1,$s2,$now)['allowed'],'different_sessions_keep_override_decisions');
 $GLOBALS['f']['access']['10|1']=['override_value'=>'exclude'];check(!fcc_event_access(1,$s1,$now)['allowed'],'new_override_read_after_raw_memo');check($GLOBALS['f']['raw_reads'][1]===1&&$GLOBALS['f']['pref_reads']===3,'raw_facts_reused_but_session_preferences_reloaded');
});
scenario('cc_pairs',function(){
 $GLOBALS['fcc_event_reconcile_cc']=[];$f=&$GLOBALS['f'];$f['metrics']['001234567890|2031-04-01']=metric(1);$f['metrics']['991234567890|2031-04-01']=metric(0);$f['metrics']['001234567890|2031-03-01']=metric(null,null,4);$f['metrics']['001234567890|2031-02-01']=metric(1,1,4,2,'unknown');
 check(fcc_event_cc('001234567890','2031-04-01')['state']==='eligible','cc_primary_pair_eligible');check(fcc_event_cc('991234567890','2031-04-01')['state']==='ineligible','cc_different_fbo_separate');check(fcc_event_cc('001234567890','2031-03-01')['state']==='pending','cc_different_month_separate');
 check(fcc_event_cc('001234567890','2031-02-01')['source']==='unverified_import','cc_unverified_import_preserved');
 $a=fcc_event_cc('000000000001','2031-04-01');$b=fcc_event_cc('000000000001','2031-04-01');check($a===$b&&$a===['state'=>'pending','source'=>'unknown','at'=>null],'missing_cc_null_at_preserved');check($f['cc_reads']['000000000001|2031-04-01']===1,'missing_cc_pair_memoized');
 $f['metrics']['001234567890|2031-04-01']=metric(0);check(fcc_event_cc('001234567890','2031-04-01')['state']==='eligible','cc_snapshot_stable_only_in_scope');unset($GLOBALS['fcc_event_reconcile_cc']);check(fcc_event_cc('001234567890','2031-04-01')['state']==='ineligible','cc_is_fresh_after_scope');
});
foreach(['absent','null','array'] as $mode){
 scenario('notifications_'.$mode,function()use($mode,$now){
  $prior=seed_prior($mode);$result=fcc_partner_notifications_run($now);$f=$GLOBALS['f'];check($result===['sent'=>0,'simulated'=>0,'failed'=>0],$mode.'_notification_result');check($f['sync_calls']===2&&$f['graph_reads']===2,$mode.'_sync_graph_once_plus_fresh_delivery_graph');check($f['push_scope_absent']&&$f['delivery_graph'][1]['status']===0,$mode.'_delivery_does_not_reuse_prior_graph');check($f['release_calls']===1,$mode.'_notification_lock_released');same_scope('fcc_team_graph_run',$mode!=='absent',$prior['fcc_team_graph_run'],$mode.'_notification_prior_restored');
 });
 foreach(['sync','push','notification_users'] as $at)scenario('notifications_'.$mode.'_'.$at,function()use($mode,$at,$now){$prior=seed_prior($mode);$e=new RuntimeException('SYNTHETIC');$GLOBALS['f']['throw']=$e;$GLOBALS['f']['throw_at']=$at;try{fcc_partner_notifications_run($now);check(false,$mode.'_'.$at.'_throw_occurs');}catch(Throwable $caught){check($caught===$e,$mode.'_'.$at.'_throw_identity');}same_scope('fcc_team_graph_run',$mode!=='absent',$prior['fcc_team_graph_run'],$mode.'_'.$at.'_prior_restored');check($GLOBALS['f']['release_calls']===1,$mode.'_'.$at.'_lock_released');check($GLOBALS['f']['push_calls']===($at==='push'?1:0),$mode.'_'.$at.'_no_unexpected_delivery');});
 scenario('calendar_'.$mode,function()use($mode,$now){
  $prior=seed_prior($mode);$f=&$GLOBALS['f'];$f['sessions']=[session(10),session(11)];$f['metrics']['001234567890|2031-04-01']=metric(1);$f['metrics']['991234567890|2031-04-01']=metric(1);$f['mutate_before_delivery']=true;$result=fcc_events_tick($now);
  check($result===['inbox'=>0,'errors'=>0],$mode.'_calendar_result');check($f['jobs_scope_absent']&&$f['wl_scope_absent'],$mode.'_calendar_facts_unset_before_jobs_and_lifecycle');check($f['delivery_user']===null&&!$f['delivery_allowed']&&$f['delivery_cc']['state']==='ineligible',$mode.'_delivery_sees_new_status_and_cc');check($f['raw_reads'][1]===3&&$f['raw_reads'][2]===1,$mode.'_calendar_raw_reads_once_per_uid_then_fresh_delivery');check($f['cc_reads']['001234567890|2031-04-01']===2&&$f['cc_reads']['991234567890|2031-04-01']===1,$mode.'_calendar_cc_reads_once_per_pair_then_fresh_delivery');check(count($f['jobs'])===4&&count(array_filter($f['writes'],fn($w)=>$w[0]==='access'))===4,$mode.'_calendar_preserves_each_reconcile_write_and_job_key');
  foreach(['fcc_event_reconcile_users','fcc_event_reconcile_cc'] as $key)same_scope($key,$mode!=='absent',$prior[$key],$mode.'_'.$key.'_restored');
 });
 foreach(['reconcile','jobs','wl'] as $at)scenario('calendar_'.$mode.'_'.$at,function()use($mode,$at,$now){$prior=seed_prior($mode);$GLOBALS['f']['sessions']=[session(10)];$e=new RuntimeException('SYNTHETIC');$GLOBALS['f']['throw']=$e;$GLOBALS['f']['throw_at']=$at;try{fcc_events_tick($now);check(false,$mode.'_'.$at.'_throw_occurs');}catch(Throwable $caught){check($caught===$e,$mode.'_'.$at.'_throw_identity');}foreach(['fcc_event_reconcile_users','fcc_event_reconcile_cc'] as $key)same_scope($key,$mode!=='absent',$prior[$key],$mode.'_'.$at.'_'.$key.'_restored');check($GLOBALS['f']['job_calls']===($at==='reconcile'?0:1),$mode.'_'.$at.'_job_boundary');check($GLOBALS['f']['wl_calls']===($at==='wl'?1:0),$mode.'_'.$at.'_lifecycle_boundary');if($at!=='reconcile')check($GLOBALS['f']['jobs_scope_absent'],$mode.'_'.$at.'_jobs_no_inherited_cache');});
}
scenario('notification_busy',function()use($now){$prior=seed_prior('array');$GLOBALS['f']['lock']=false;check(fcc_partner_notifications_run($now)===['busy'=>true],'busy_unchanged');same_scope('fcc_team_graph_run',true,$prior['fcc_team_graph_run'],'busy_does_not_touch_prior');check($GLOBALS['f']['release_calls']===0&&$GLOBALS['f']['sync_calls']===0,'busy_no_work_or_unowned_release');});
scenario('notification_disabled',function()use($now){$prior=seed_prior('array');putenv('FCC_PARTNER_ENABLED=0');check(fcc_partner_notifications_run($now)===['disabled'=>true],'disabled_unchanged');same_scope('fcc_team_graph_run',true,$prior['fcc_team_graph_run'],'disabled_does_not_touch_prior');check($GLOBALS['f']['release_calls']===0&&$GLOBALS['f']['sync_calls']===0,'disabled_no_work_or_release');});

$after=[];foreach($relative as $file)$after[$file]=hash_file('sha256',$source.'/'.$file);
check($before===$after,'all_source_hashes_stable_before_and_after');
$failed=array_values(array_filter($checks,fn($c)=>!$c['pass']));$report=['status'=>$failed?'FAIL':'PASS','checks'=>count($checks),'passed'=>count($checks)-count($failed),'failed'=>count($failed),'scenarios'=>$scenarios,'source_sha256_before'=>$before,'source_sha256_after'=>$after,'actual_function_sha256'=>$functionHashes,'details'=>$checks,'limits'=>['actual_named_functions_extracted_without_bootstrap'=>true,'boundary_stubs_in_memory_only'=>true,'sql_semantics_not_executed'=>true,'managed_scope_graph_boundary_stub'=>true,'db_calls'=>0,'api_calls'=>0,'network_calls'=>0]];
$json=json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;umask(0077);if(file_put_contents($output,$json)===false)throw new RuntimeException('Cannot save proof.');chmod($output,0600);
echo json_encode(['status'=>$report['status'],'checks'=>$report['checks'],'failed'=>$report['failed'],'scenarios'=>$scenarios,'report_sha256'=>hash('sha256',$json)],JSON_THROW_ON_ERROR).PHP_EOL;exit($failed?1:0);
