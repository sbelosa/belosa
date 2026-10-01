<?php
if(PHP_SAPI!=='cli')exit(1);umask(0077);set_time_limit(120);
$root='/home/forevercardclub/public_html/';$stage='__STAGE__';$changed=[];$locked=false;
try {
 chmod($stage,0700);
 $manifest=json_decode(file_get_contents($stage.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
 foreach($manifest as $f){$p=$f['path'];if(str_contains($p,'..')||str_starts_with($p,'/'))throw new RuntimeException('Invalid path');if((is_file($root.$p)?hash_file('sha256',$root.$p):null)!==$f['before'])throw new RuntimeException('Concurrent production change: '.$p);if(hash_file('sha256',$stage.'/files/'.$p)!==$f['sha256'])throw new RuntimeException('Staging mismatch');if(str_ends_with($p,'.php')){exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($stage.'/files/'.$p).' 2>&1',$out,$code);if($code)throw new RuntimeException('Syntax failure: '.$p);}}
 define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require $root.'app/init.php';Altum\Cache::initialize();
 if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
 $locked=(bool)(fcc_partner_one("SELECT GET_LOCK('fcc_partner_notifications',15) acquired")['acquired']??0);if(!$locked)throw new RuntimeException('Notification worker busy');
 foreach($manifest as $f){$saved=$stage.'/before/'.$f['path'];if(!is_dir(dirname($saved)))mkdir(dirname($saved),0700,true);if($f['before']!==null&&(!copy($root.$f['path'],$saved)||hash_file('sha256',$saved)!==$f['before']))throw new RuntimeException('Backup failed');}
 // Additive, feature specific tables only. Existing data and preferences are untouched.
 foreach(explode(';',file_get_contents($stage.'/schema.sql')) as $sql)if(trim($sql))fcc_partner_query($sql);
 foreach($manifest as $i=>$f){$p=$f['path'];$live=$root.$p;$ready=$stage.'/ready-'.$i;if((is_file($live)?hash_file('sha256',$live):null)!==$f['before'])throw new RuntimeException('Concurrent source edit');if(!copy($stage.'/files/'.$p,$ready)||!chmod($ready,0644)||!rename($ready,$live))throw new RuntimeException('Publication failed');$changed[]=$f;if(hash_file('sha256',$live)!==$f['sha256'])throw new RuntimeException('Published hash mismatch');if(function_exists('opcache_invalidate'))opcache_invalidate($live,true);}
 require_once $root.'app/helpers/fcc_top.php';
 $start=microtime(true);$month=substr(fcc_partner_today(),0,7).'-01';$d=fcc_top_dataset(30,$month);$prev=fcc_top_dataset(30,(new DateTimeImmutable($month))->modify('-1 month')->format('Y-m-d'));
 foreach([$d,$prev] as $dataset){if(!$dataset['sync'])throw new RuntimeException('Verified CC source missing');foreach($dataset['lists'] as $cat=>$rows){$safe=fcc_top_public($rows,$cat,0);if(in_array($cat,['personal_cc','total_cc'],true))foreach($safe as $r)if($r['score']!==null)throw new RuntimeException('CC privacy failed');}}
 $result=['status'=>'published','at_utc'=>gmdate('c'),'files'=>count($changed),'source_backup'=>$stage.'/before','feature_tables'=>['fcc_top_profiles','fcc_top_positions','fcc_top_state'],'users_counted'=>$d['accounts'],'cc_month'=>$month,'cc_sync'=>$d['sync'],'previous_month_sync'=>$prev['sync'],'current_categories'=>array_map('count',$d['lists']),'previous_categories'=>array_map('count',$prev['lists']),'calculation_seconds'=>round(microtime(true)-$start,3),'profiles_opted_in'=>(int)fcc_partner_one('SELECT COUNT(*) n FROM fcc_top_profiles WHERE visible=1')['n'],'existing_data_unchanged'=>true];
 file_put_contents($stage.'/published.json',json_encode($result,JSON_PRETTY_PRINT));file_put_contents(__DIR__.'/result.json',json_encode($result,JSON_PRETTY_PRINT));
}catch(Throwable $e){foreach(array_reverse($changed) as $i=>$f){$target=$root.$f['path'];if($f['before']===null){if(is_file($target))unlink($target);}else{$restore=$stage.'/rollback-'.$i;copy($stage.'/before/'.$f['path'],$restore);chmod($restore,0644);rename($restore,$target);}if(function_exists('opcache_invalidate'))opcache_invalidate($target,true);}file_put_contents(__DIR__.'/result.json',json_encode(['status'=>'error','error'=>$e->getMessage(),'restored_files'=>count($changed)]));exit(1);
}finally{if($locked)fcc_partner_query("SELECT RELEASE_LOCK('fcc_partner_notifications')");}
