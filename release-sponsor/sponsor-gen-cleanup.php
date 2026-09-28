<?php
/** Remove only this task's temporary cron entry, retaining a private recovery copy. */
if(PHP_SAPI!=='cli')exit(1);umask(0077);chdir(__DIR__);
$prefix='sponsor-gen-cleanup';
if(is_file($prefix.'.done')||is_file($prefix.'.failed'))exit;
$lock=fopen($prefix.'.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
try {
 if(!is_file('sponsor-gen-links.done'))throw new RuntimeException('Linking has not completed');
 $bin='/usr/bin/crontab';
 if(!is_executable($bin)||!function_exists('proc_open'))throw new RuntimeException('Cron CLI unavailable');
 $run=function(array $args): string {
  $p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
  if(!is_resource($p))throw new RuntimeException('Cron command unavailable');
  fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
  if(proc_close($p)!==0)throw new RuntimeException('Cron command failed');
  return $out;
 };
 $before=$run([$bin,'-l']);
 $command='/usr/local/apps/php83/bin/php /home/forevercardclub/fcc-release-20260927/sponsor-gen-inventory.php >/dev/null';
 $lines=explode("\n",$before);$kept=[];$removed=0;
 foreach($lines as $line){
  if(preg_match('/^\s*(?:\S+\s+){5}'.preg_quote($command,'/').'\s*$/D',$line)){$removed++;continue;}
  $kept[]=$line;
 }
 if($removed>1)throw new RuntimeException('Unexpected duplicate temporary jobs');
 $after=implode("\n",$kept);
 if($removed===1){
  file_put_contents($prefix.'.before',$before);chmod($prefix.'.before',0600);
  $tmp=$prefix.'.new';file_put_contents($tmp,$after);chmod($tmp,0600);
  try{$run([$bin,__DIR__.'/'.$tmp]);}finally{unlink($tmp);}
 }
 $actual=$run([$bin,'-l']);
 if(trim($actual)!==trim($after))throw new RuntimeException('Cron content verification failed');
 $jobs=array_values(array_filter(explode("\n",$actual),fn($line)=>trim($line)!==''&&!str_starts_with(ltrim($line),'#')));
 file_put_contents($prefix.'.done',json_encode(['status'=>'CLEANED','at'=>gmdate('c'),'removed'=>$removed,'remaining_jobs'=>count($jobs),'other_commands_preserved'=>true]));
}catch(Throwable $e){file_put_contents($prefix.'.failed',json_encode(['status'=>'FAILED','error'=>$e->getMessage()]));}
