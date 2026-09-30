<?php
if(PHP_SAPI!=='cli')exit(1);
umask(0077);
$release=__DIR__;$parent=dirname($release);$root='/home/forevercardclub/public_html/';
$lock=fopen($release.'/install.lock','c');
if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))return;
$worker=$parent.'/partner-worker.php';$original=$parent.'/partner-worker.outreach-20260930.before.php';
$switched=[];$result=['status'=>'stopped','at'=>gmdate('c')];
try {
 if(is_file($release.'/result.json')){ $result=json_decode(file_get_contents($release.'/result.json'),true); return; }
 $manifest=json_decode(file_get_contents($release.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
 foreach($manifest as $i=>$e){
  $target=$root.$e['path'];$payload=$release.'/files/'.$e['path'];
  if(hash_file('sha256',$payload)!==$e['sha256'])throw new RuntimeException('Payload checksum: '.$e['path']);
  $hash=is_file($target)?hash_file('sha256',$target):null;
  if($hash!==$e['before'])throw new RuntimeException('Concurrent production edit: '.$e['path']);
  if(str_ends_with($target,'.php')){exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($payload).' 2>&1',$output,$code);if($code!==0)throw new RuntimeException('PHP lint: '.$e['path']);}
  if($hash!==null){$backup=$release.'/before-'.$i;if(!copy($target,$backup)||hash_file('sha256',$backup)!==$hash)throw new RuntimeException('Backup failed');}
 }
 define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
 require $root.'app/init.php';\Altum\Cache::initialize();
 if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
 $before=fcc_partner_one('SELECT COUNT(*) n,COALESCE(SUM(sent_self_reported_at IS NOT NULL),0) sent FROM fcc_team_outreach');
 $columns=array_column(fcc_partner_rows('SHOW COLUMNS FROM fcc_team_outreach'),'Field');
 foreach(['copied_at','confirmation_dismissed_at','deleted_at'] as $column)if(!in_array($column,$columns,true))database()->query('ALTER TABLE fcc_team_outreach ADD COLUMN '.$column.' DATETIME NULL');
 foreach($manifest as $i=>$e){
  $target=$root.$e['path'];$hash=is_file($target)?hash_file('sha256',$target):null;
  if($hash!==$e['before'])throw new RuntimeException('Concurrent edit before switch: '.$e['path']);
  $stage=$target.'.outreach-20260930-tmp';if(!copy($release.'/files/'.$e['path'],$stage))throw new RuntimeException('Staging failed');chmod($stage,0644);
  if(!rename($stage,$target))throw new RuntimeException('File switch failed');$switched[$i]=$e;
  if(function_exists('opcache_invalidate'))opcache_invalidate($target,true);
 }
 exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($release.'/verify.php').' 2>&1',$verify,$code);
 if($code!==0||!is_file($release.'/verification.json'))throw new RuntimeException('Read only verification failed: '.implode(' ',array_slice($verify,-2)));
 $after=fcc_partner_one('SELECT COUNT(*) n,COALESCE(SUM(sent_self_reported_at IS NOT NULL),0) sent FROM fcc_team_outreach');
 $result=['status'=>'installed','at'=>gmdate('c'),'files'=>count($manifest),'schema_columns_added'=>3,'outreach_before'=>$before,'outreach_after'=>$after,'verification'=>json_decode(file_get_contents($release.'/verification.json'),true)];
}catch(Throwable $e){
 foreach(array_reverse($switched,true) as $i=>$entry){$target=$root.$entry['path'];if(hash_file('sha256',$target)!==$entry['sha256'])continue;
  if($entry['before']===null)unlink($target);else{$stage=$target.'.rollback';copy($release.'/before-'.$i,$stage);chmod($stage,0644);rename($stage,$target);}
  if(function_exists('opcache_invalidate'))opcache_invalidate($target,true);
 }
 $result['reason']=$e->getMessage();$result['files_rolled_back']=count($switched);
}finally{
 // Restore the exact worker only while it is still our guarded wrapper.
 $wrapperHash=trim(file_get_contents($release.'/wrapper.sha256'));
 if(is_file($original)&&hash_file('sha256',$worker)===$wrapperHash){$tmp=$worker.'.restore';copy($original,$tmp);chmod($tmp,0644);rename($tmp,$worker);$result['worker_restored']=hash_file('sha256',$worker)===hash_file('sha256',$original);}
 file_put_contents($release.'/result.json',json_encode($result,JSON_PRETTY_PRINT));
}
