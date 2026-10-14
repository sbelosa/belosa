<?php
/** Actual script fixture, fake top-level bootstrap and helpers, no DB/API/network. */
declare(strict_types=1);
if(PHP_SAPI!=='cli')exit(1);
$package=realpath($argv[1]??__DIR__);$baseline=realpath($argv[2]??__DIR__.'/fixtures/fcc_partner_worker_baseline_20261014.php');
$output=$argv[3]??sys_get_temp_dir().'/fcc-worker-lock-'.getmypid().'.json';
if(!$package||!$baseline||hash_file('sha256',$baseline)!=='827dcfaeaa0b12d9bc4bbc35a14ac39aa0b91c30f249fb1786f4ee18756eba4d')throw new RuntimeException('Pinned baseline missing or changed.');
$candidate=$package.'/partner-notifications.php';$before=['baseline'=>hash_file('sha256',$baseline),'candidate'=>hash_file('sha256',$candidate),'fixture'=>hash_file('sha256',__FILE__)];
$root=sys_get_temp_dir().'/fcc-whole-cycle-fixture-'.getmypid().'-'.bin2hex(random_bytes(4));umask(0077);mkdir($root,0700,true);
$checks=[];$scenarioCount=0;
$check=static function(string $name,bool $ok)use(&$checks):void{$checks[]=['label'=>$name,'pass'=>$ok];};
$normalize=static function(string $text,string $app):string{
 $text=preg_replace('/^\[[^\]\r\n]*\]\s*/m','',$text);$text=str_replace($app,'FIXTURE_APP',$text);
 return preg_replace_callback('~(FIXTURE_APP/(?:app/init\.php|scripts/partner-notifications\.php))(?::([0-9]+)|\(([0-9]+)\)| on line ([0-9]+))~',static function(array $m):string{return $m[1].(($m[2]??'')!==''?':LINE':(($m[3]??'')!==''?'(LINE)':' on line LINE'));},$text);
};
$diag='RuntimeException: status 401 port 80 in /fixture/app/init.php:3' . "\n#0 /fixture/scripts/partner-notifications.php(4): require()";
$check('known_locations_only_normalized',$normalize($diag,'/fixture')===$normalize(str_replace([':3','(4)'],[':9','(11)'],$diag),'/fixture'));
foreach(['401'=>'403','80'=>'81','RuntimeException'=>'LogicException','require()'=>'call()'] as $from=>$to)$check('normalizer_preserves_'.$from,$normalize($diag,'/fixture')!==$normalize(str_replace((string)$from,$to,$diag),'/fixture'));
$init=<<<'PHP'
<?php
$worker_bootstrap_scope_sentinel=73;
define('DATABASE_NAME',getenv('FIXTURE_DB_NAME')?:'fcc_partner_local');define('DATABASE_SERVER',getenv('FIXTURE_DB_SERVER')?:'db');
function db(){throw new LogicException('FORBIDDEN_DB');}function database(){throw new LogicException('FORBIDDEN_DB');}
function hit(string $name):array {
 if(($GLOBALS['worker_bootstrap_scope_sentinel']??null)!==73)throw new LogicException('BOOTSTRAP_SCOPE_CHANGED');
 file_put_contents(getenv('FIXTURE_TRACE'),$name."\n",FILE_APPEND);
 if(getenv('FIXTURE_THROW')===$name)throw new RuntimeException('PRIVATE_PRIMARY_ERROR_SENTINEL status 401 port 80');
 return ['sent'=>2,'failed'=>0,'fixture'=>'PRIVATE_RESULT_SENTINEL'];
}
if(getenv('FIXTURE_THROW')==='bootstrap')throw new RuntimeException('PRIVATE_PRIMARY_ERROR_SENTINEL status 401 port 80');
function enabled(string $name):bool{hit($name.'_enabled');return !in_array($name,explode(',',getenv('FIXTURE_DISABLED')?:''),true);}
function fcc_business_enabled(){return enabled('business');}function fcc_webinar_worker_enabled(){return enabled('webinar');}function fcc_events_enabled(){return enabled('calendar');}
function fcc_business_retention(){return hit('business_retention');}function fcc_business_run_jobs(){return hit('business_jobs');}function fcc_business_reminders(){return hit('business_reminders');}function fcc_business_admin_reminders(){return hit('business_admin_reminders');}
function fcc_webinar_apply_retention(){return hit('webinar_retention');}function fcc_webinar_run_jobs(){return hit('webinar_jobs');}function fcc_events_tick(){return hit('calendar');}function fcc_partner_notifications_run(){return hit('notifications');}
function fcc_partner_one(string $sql,array $args=[]):?array {
 if($sql!=='SELECT GET_LOCK(?,0) acquired'||count($args)!==1||$args[0]!=='fcc_partner_background:'.substr(hash('sha256',(string)DATABASE_NAME),0,40)||strlen($args[0])>64)throw new LogicException('LOCK_CONTRACT_CHANGED');
 hit('acquire');$mode=getenv('FIXTURE_LOCK_MODE');if($mode===false||$mode==='')$mode='1';
 if($mode==='throw')throw new RuntimeException('PRIVATE_ACQUIRE_ERROR_SENTINEL');
 if($mode==='null')return ['acquired'=>null];if($mode==='missing')return null;if($mode==='invalid')return ['acquired'=>'PRIVATE_LOCK_SENTINEL'];
 return ['acquired'=>str_starts_with($mode,'string')?substr($mode,6):(int)$mode];
}
function fcc_partner_query(string $sql,array $args=[]):void {
 if($sql!=='SELECT RELEASE_LOCK(?)'||count($args)!==1||$args[0]!=='fcc_partner_background:'.substr(hash('sha256',(string)DATABASE_NAME),0,40))throw new LogicException('RELEASE_CONTRACT_CHANGED');
 hit('release');if(getenv('FIXTURE_RELEASE_ERROR')==='1')throw new LogicException('PRIVATE_RELEASE_ERROR_SENTINEL status 503 port 443');
}
PHP;
$run=static function(string $name,string $variant,array $options)use($root,$init,$baseline,$candidate,$normalize):array{
 $app=$root.'/'.$name.'/'.$variant;mkdir($app.'/scripts',0700,true);mkdir($app.'/app',0700,true);file_put_contents($app.'/app/init.php',$init);copy($variant==='baseline'?$baseline:$candidate,$app.'/scripts/partner-notifications.php');
 $env=getenv();foreach(['FCC_LOCAL'=>'1','FIXTURE_TRACE'=>$app.'/trace.txt','FIXTURE_THROW'=>'','FIXTURE_DISABLED'=>'','FIXTURE_LOCK_MODE'=>'1','FIXTURE_RELEASE_ERROR'=>'0','FIXTURE_DB_NAME'=>'fcc_partner_local','FIXTURE_DB_SERVER'=>'db'] as $key=>$value)$env[$key]=$value;foreach($options as $key=>$value)$env[$key]=$value;
 $pipes=[];$proc=proc_open([PHP_BINARY,'-d','display_errors=1','-d','log_errors=1','-d','error_log='.$app.'/errors.txt',$app.'/scripts/partner-notifications.php'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$app,$env);if(!is_resource($proc))throw new RuntimeException('Cannot launch isolated fixture.');
 fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);fclose($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[2]);$exit=proc_close($proc);
 $trace=is_file($app.'/trace.txt')?file($app.'/trace.txt',FILE_IGNORE_NEW_LINES):[];$errors=is_file($app.'/errors.txt')?file_get_contents($app.'/errors.txt'):'';
 return ['stdout'=>$normalize($stdout,$app),'stderr'=>$normalize($stderr,$app),'errors'=>$normalize($errors,$app),'trace'=>$trace,'exit'=>$exit];
};
$scenarios=[['normal',[]],['business_disabled',['FIXTURE_DISABLED'=>'business']],['webinar_disabled',['FIXTURE_DISABLED'=>'webinar']],['calendar_disabled',['FIXTURE_DISABLED'=>'calendar']],['all_disabled',['FIXTURE_DISABLED'=>'business,webinar,calendar']]];
foreach(['business_retention','business_jobs','business_reminders','business_admin_reminders','webinar_retention','webinar_jobs','calendar','notifications','bootstrap','business_enabled','webinar_enabled','calendar_enabled'] as $phase)$scenarios[]=['throw_'.$phase,['FIXTURE_THROW'=>$phase]];
foreach($scenarios as [$name,$options]){
 $scenarioCount++;$a=$run($name,'baseline',$options);$b=$run($name,'candidate',$options);$businessTrace=array_values(array_filter($b['trace'],fn($v)=>!in_array($v,['acquire','release'],true)));
 foreach(['stdout','stderr','errors','exit'] as $field)$check($name.'_'.$field.'_unchanged',$a[$field]===$b[$field]);
 $check($name.'_business_call_order_unchanged',$a['trace']===$businessTrace);
 $boot=($options['FIXTURE_THROW']??'')==='bootstrap';$check($name.'_acquisition_once',count(array_keys($b['trace'],'acquire',true))===($boot?0:1));$check($name.'_release_once',count(array_keys($b['trace'],'release',true))===($boot?0:1));
 if(!$boot)$check($name.'_lock_bounds_entire_cycle',reset($b['trace'])==='acquire'&&end($b['trace'])==='release');
 $check($name.'_bootstrap_top_level',!str_contains($b['errors'],'BOOTSTRAP_SCOPE_CHANGED'));
 $throw=$options['FIXTURE_THROW']??'';$expected=$throw==='notifications'?1:(in_array($throw,['bootstrap','business_enabled','webinar_enabled','calendar_enabled'],true)?255:0);$check($name.'_expected_exit',$b['exit']===$expected);
}
foreach(['string1','0','string0','null','missing','invalid','throw'] as $mode){
 $scenarioCount++;$b=$run('lock_'.$mode,'candidate',['FIXTURE_LOCK_MODE'=>$mode]);$busy=in_array($mode,['0','string0'],true);$good=$mode==='string1';
 $check($mode.'_lock_exit',$b['exit']===($good||$busy?0:255));$check($mode.'_single_acquire',count(array_keys($b['trace'],'acquire',true))===1);$check($mode.'_release_only_when_acquired',count(array_keys($b['trace'],'release',true))===($good?1:0));
 $check($mode.'_no_processing_without_acquired_lock',$good||$b['trace']===['acquire']);
 if($busy)$check($mode.'_busy_json_exact',$b['stdout']==="{\"busy\":true}\n");
 if(in_array($mode,['null','missing','invalid'],true))$check($mode.'_invalid_lock_fails_closed_bounded_message',str_contains($b['errors'],'FCC background lock unavailable.')&&!str_contains($b['errors'],'PRIVATE_LOCK_SENTINEL'));
}
foreach(['normal'=>'','notifications'=>'notifications','enabled'=>'business_enabled'] as $name=>$phase){
 $scenarioCount++;$a=$run('release_error_'.$name,'baseline',['FIXTURE_THROW'=>$phase]);$b=$run('release_error_'.$name,'candidate',['FIXTURE_THROW'=>$phase,'FIXTURE_RELEASE_ERROR'=>'1']);
 $trimmed=str_replace("Partner background lock release: LogicException\n",'',$b['errors']);
 $check($name.'_release_failure_preserves_original_stdout',$a['stdout']===$b['stdout']);$check($name.'_release_failure_preserves_original_stderr',$a['stderr']===$b['stderr']);$check($name.'_release_failure_preserves_original_error',$a['errors']===$trimmed);$check($name.'_release_failure_preserves_exit',$a['exit']===$b['exit']);
 $check($name.'_release_failure_bounded_class_only',substr_count($b['errors'],'Partner background lock release: LogicException')===1&&!str_contains($b['errors'],'PRIVATE_RELEASE_ERROR_SENTINEL'));$check($name.'_release_attempt_once',count(array_keys($b['trace'],'release',true))===1);
}
foreach(['wrong_database'=>['FIXTURE_DB_NAME'=>'fcc_wrong_local_fixture'],'wrong_server'=>['FIXTURE_DB_SERVER'=>'wrong_local_fixture']] as $name=>$options){
 $scenarioCount++;$a=$run($name,'baseline',$options);$b=$run($name,'candidate',$options);foreach(['stdout','stderr','errors','exit','trace'] as $field)$check($name.'_'.$field.'_guard_unchanged',$a[$field]===$b[$field]);$check($name.'_no_lock_before_local_guard',$b['trace']===[]&&$b['exit']===255);
}
$after=['baseline'=>hash_file('sha256',$baseline),'candidate'=>hash_file('sha256',$candidate),'fixture'=>hash_file('sha256',__FILE__)];$check('all_sources_unchanged',$before===$after);
$failed=array_values(array_filter($checks,fn($c)=>!$c['pass']));$report=['status'=>$failed?'FAIL':'PASS','checks'=>count($checks),'passed'=>count($checks)-count($failed),'failed'=>count($failed),'scenarios'=>$scenarioCount,'source_sha256_before'=>$before,'source_sha256_after'=>$after,'details'=>$checks,'limits'=>['actual_baseline_and_candidate_script'=>true,'fake_top_level_init_and_helpers'=>true,'subprocesses_cli_only'=>true,'db_calls'=>0,'api_calls'=>0,'network_calls'=>0,'temporary_fixture_root_private'=>true]];
$json=json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;file_put_contents($output,$json);chmod($output,0600);echo json_encode(['status'=>$report['status'],'checks'=>$report['checks'],'failed'=>$report['failed'],'scenarios'=>$scenarioCount,'report_sha256'=>hash('sha256',$json)],JSON_THROW_ON_ERROR).PHP_EOL;exit($failed?1:0);
