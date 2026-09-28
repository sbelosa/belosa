<?php
/** Reviewed CLI deployment and explicit shared-account approval. Never web accessible. */
if(PHP_SAPI!=='cli')exit(1);umask(0077);chdir(__DIR__);
if(is_file('shared-release.done')||is_file('shared-release.failed'))exit;
$lock=fopen('shared-release.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
$root='/home/forevercardclub/public_html/';$phase='preflight';$published=[];$graphLocked=false;$result=[];
try {
 $manifest=json_decode(file_get_contents('shared-manifest.json'),true,512,JSON_THROW_ON_ERROR);
 $sealed=json_decode(file_get_contents('shared-plan.sealed.json'),true,512,JSON_THROW_ON_ERROR);
 if(!openssl_private_decrypt(base64_decode($sealed['key']),$key,file_get_contents('gen-transfer-private.pem'),OPENSSL_PKCS1_OAEP_PADDING))throw new RuntimeException('Cannot open reviewed plan');
 $plain=openssl_decrypt(base64_decode($sealed['data']),'aes-256-gcm',$key,OPENSSL_RAW_DATA,base64_decode($sealed['iv']),base64_decode($sealed['tag']));
 if($plain===false||!hash_equals($manifest['plan_sha256'],hash('sha256',$plain)))throw new RuntimeException('Reviewed plan checksum differs');
 $plan=json_decode($plain,true,512,JSON_THROW_ON_ERROR);$actor=(int)$plan['actor_id'];$coowner=(int)$plan['coowner_id'];unset($plain,$key);
 if(count($plan['groups'])!==20||count($plan['grant_ids'])!==37||count($plan['links'])!==186)throw new RuntimeException('Reviewed scope differs');
 $zip=new ZipArchive();if($zip->open('shared-payload.zip')!==true)throw new RuntimeException('Cannot open package');
 foreach($manifest['files'] as $p=>$h) {
  if(!preg_match('~^(app/(helpers|config)/|themes/altum/(views/|assets/(css|js)/)|scripts/migrations/partner/)[a-zA-Z0-9_./-]+$~D',$p)||str_contains($p,'..'))throw new RuntimeException('Unexpected package path');
  $actual=is_file($root.$p)?hash_file('sha256',$root.$p):null;
  if($actual!==$h['before'])throw new RuntimeException('Concurrent production file edit: '.$p);
  $bytes=$zip->getFromName($p);if($bytes===false||hash('sha256',$bytes)!==$h['after'])throw new RuntimeException('Package checksum differs: '.$p);
  $target=__DIR__.'/shared-stage/'.$p;if(!is_dir(dirname($target)))mkdir(dirname($target),0700,true);file_put_contents($target,$bytes);
  if(str_ends_with($p,'.php')) {exec(escapeshellarg(PHP_BINARY).' -l '.escapeshellarg($target).' 2>&1',$lint,$code);if($code)throw new RuntimeException('PHP syntax validation failed: '.$p);}
 }
 define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
 require_once $root.'config.php';
 if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
 mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new mysqli(DATABASE_SERVER,DATABASE_USERNAME,DATABASE_PASSWORD,DATABASE_NAME);$db->set_charset('utf8mb4');
 if(!(int)$db->query("SELECT GET_LOCK('fcc_partner_shared_release',10)")->fetch_row()[0])throw new RuntimeException('Another release is running');
 $backup=new ZipArchive();if($backup->open('shared-before.zip',ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('Cannot create independent rollback archive');
 foreach($manifest['files'] as $p=>$h)if($h['before']!==null)$backup->addFile($root.$p,$p);if(!$backup->close())throw new RuntimeException('Cannot save rollback archive');
 if(!hash_equals($plan['runtime_hash'],hash_file('sha256','/home/forevercardclub/fcc-partner-runtime.php')))throw new RuntimeException('Runtime changed during review');
 $phase='additive_schema';
 $migration='020_team_shared_accounts.sql';$path='scripts/migrations/partner/'.$migration;$hash=$manifest['files'][$path]['after'];
 if(!(int)$db->query("SELECT GET_LOCK('fcc_partner_schema_migration',10)")->fetch_row()[0])throw new RuntimeException('Schema is being changed');
 try {
  $q=$db->prepare('SELECT checksum FROM fcc_partner_migrations WHERE version=?');$q->bind_param('s',$migration);$q->execute();$old=$q->get_result()->fetch_assoc();
  if($old&&$old['checksum']!==$hash)throw new RuntimeException('Applied migration differs');
  if(!$old){$db->multi_query($zip->getFromName($path));do{if($r=$db->store_result())$r->free();}while($db->more_results()&&$db->next_result());$q=$db->prepare('INSERT INTO fcc_partner_migrations(version,checksum,applied_at) VALUES(?,?,UTC_TIMESTAMP())');$q->bind_param('ss',$migration,$hash);$q->execute();}
 }finally{$db->query("SELECT RELEASE_LOCK('fcc_partner_schema_migration')");}
 // Publish helpers before their consumer. Existing accounts continue with the old scope until approval.
 $phase='publishing';$paths=array_keys($manifest['files']);usort($paths,fn($a,$b)=>($a==='app/helpers/fcc_team.php'?1:(str_starts_with($a,'app/helpers/fcc_team_')?0:2))<=>($b==='app/helpers/fcc_team.php'?1:(str_starts_with($b,'app/helpers/fcc_team_')?0:2)));
 foreach($paths as $p){if(!is_dir(dirname($root.$p)))mkdir(dirname($root.$p),0755,true);chmod(__DIR__.'/shared-stage/'.$p,0644);if(!rename(__DIR__.'/shared-stage/'.$p,$root.$p))throw new RuntimeException('Cannot publish '.$p);$published[]=$p;if(function_exists('opcache_invalidate'))opcache_invalidate($root.$p,true);}
 require $root.'app/init.php';\Altum\Cache::initialize();
 if(!(int)(fcc_partner_one("SELECT GET_LOCK('fcc_team_graph',10) ok")['ok']??0))throw new RuntimeException('A manual team edit is running');$graphLocked=true;
 $identity=static function(int $id):string{$u=fcc_partner_one('SELECT email,preferences FROM users WHERE user_id=?',[$id]);return hash('sha256',$id.':'.strtolower(trim($u['email']??'')).':'.fcc_team_fbo($u??[]));};
 if(!fcc_team_admin($actor)||!hash_equals($plan['actor_identity'],$identity($actor)))throw new RuntimeException('Administrator identity changed');
 $usersBefore=fcc_partner_rows('SELECT user_id,name,email,status,type,preferences FROM users ORDER BY user_id');
 $teamBefore=fcc_partner_rows('SELECT * FROM fcc_team_members');$rolloutBefore=fcc_partner_rows('SELECT * FROM fcc_partner_rollout_access ORDER BY user_id');
 if(fcc_partner_rows('SELECT * FROM fcc_team_accounts'))throw new RuntimeException('Shared account approvals already exist and need a fresh review');
 foreach($plan['expected'] as $id=>$expected) {
  if(!hash_equals($expected['identity'],$identity((int)$id)))throw new RuntimeException('Reviewed identity changed: '.$id);
  $current=fcc_team_record((int)$id);$old=$expected['team'];
  if(($current===null)!==($old===null)||($current&&(int)$current['version']!==(int)$old['version']))throw new RuntimeException('Manual team edit detected: '.$id);
  if($current)foreach(['fbo_id','sponsor_user_id','sponsor_fbo_id','phone','contact_confirmed','is_manager'] as $field)if((string)$current[$field]!== (string)$old[$field])throw new RuntimeException('Reviewed field changed: '.$id);
 }
 file_put_contents('shared-data-before.json',json_encode(['at'=>gmdate('c'),'users'=>$usersBefore,'team'=>$teamBefore,'rollout'=>$rolloutBefore],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
 $phase='applying';$result=['started_at'=>gmdate('c'),'seeds'=>[],'groups'=>[],'links'=>[]];
 $progress=static function()use(&$result){file_put_contents('shared-progress.json',json_encode($result,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));};
 foreach($plan['seeds'] as $s) {
  $uid=(int)$s['uid'];$u=fcc_partner_one('SELECT preferences FROM users WHERE user_id=?',[$uid]);$phone=fcc_team_registration_phone($u);
  if(fcc_team_record($uid))throw new RuntimeException('Seed changed after review');
  fcc_team_admin_save($actor,$uid,['team_version'=>0,'team_sponsor_id'=>0,'team_is_manager'=>$s['manager'],'team_phone'=>$phone,'team_contact_confirmed'=>$phone?1:0,'team_reason'=>'Priprema potvrđenog zajedničkog računa i FLP linije, 28.09.2026.']);$result['seeds'][]=$uid;$progress();
 }
 foreach($plan['groups'] as $g){fcc_team_accounts_save($actor,(int)$g['primary'],$g['accounts'],$g['fbo']);$result['groups'][]=['primary'=>$g['primary'],'accounts'=>$g['accounts']];$progress();}
 foreach($plan['links'] as $link) {
  $uid=(int)$link['uid'];$old=fcc_team_record($uid);
  $reason=$link['source']==='approved_shared'?'Zajednički račun i glavni kontakt potvrđeni korisničkim popisom, 28.09.2026.':($link['source']==='canonical_existing'?'Postojeći Forever sponzor sačuvan, prikaz potvrđenog glavnog FCC računa, 28.09.2026.':'Izravni sponzor potvrđen FLP360 prikazom Gen i ranije pregledanim izvorima, glavni računi potvrđeni 28.09.2026.');
  fcc_team_admin_save($actor,$uid,['team_version'=>(int)$old['version'],'team_sponsor_id'=>(int)$link['sid'],'team_is_manager'=>$old['is_manager'],'team_phone'=>$old['phone'],'team_contact_confirmed'=>$old['contact_confirmed'],'team_reason'=>$reason]);$result['links'][]=$link;$progress();
 }
 $grants=[];foreach($plan['grant_ids'] as $uid)if(!fcc_rollout_allowed((int)$uid))$grants[]=(int)$uid;
 if($grants){$preview=fcc_rollout_preview('grant',$grants);$control=fcc_partner_one('SELECT revision FROM fcc_partner_rollout_control WHERE id=1');fcc_rollout_change($actor,['action'=>'grant','ids'=>$grants,'request_key'=>bin2hex(random_bytes(16)),'confirmation'=>'POTVRDI','revision'=>(int)$control['revision'],'fingerprint'=>$preview['fingerprint']]);}
 $result['workspace_grants']=$grants;
 $phase='verification';$graph=fcc_team_graph();
 foreach($plan['groups'] as $g)foreach($g['accounts'] as $id)if(fcc_team_primary((int)$id,$graph)!==(int)$g['primary']||!fcc_partner_enabled((int)$id))throw new RuntimeException('Approved shared account verification failed');
 foreach($plan['links'] as $l)if(fcc_team_edge((int)$l['uid'],$graph)!==(int)$l['sid'])throw new RuntimeException('Reviewed link verification failed');
 $planned=array_column($plan['links'],null,'uid');
 foreach($teamBefore as $old){$now=fcc_team_record((int)$old['user_id']);foreach(['phone','contact_confirmed','is_manager'] as $k)if((string)$now[$k]!== (string)$old[$k])throw new RuntimeException('Existing contact or role changed');if(!isset($planned[$old['user_id']])&&$now!=$old)throw new RuntimeException('Unplanned team edit');}
 if(fcc_partner_rows('SELECT user_id,name,email,status,type,preferences FROM users ORDER BY user_id')!==$usersBefore)throw new RuntimeException('Account settings changed during release');
 foreach($rolloutBefore as $old)if(!in_array((int)$old['user_id'],$grants,true)&&fcc_partner_one('SELECT * FROM fcc_partner_rollout_access WHERE user_id=?',[(int)$old['user_id']])!=$old)throw new RuntimeException('Unplanned workspace change');
 if(!hash_equals($plan['runtime_hash'],hash_file('sha256','/home/forevercardclub/fcc-partner-runtime.php')))throw new RuntimeException('Runtime changed');
 if(fcc_team_visible_members($actor,$graph)!==fcc_team_visible_members($coowner,$graph))throw new RuntimeException('Primary and coowner structure differs');
 foreach($manifest['files'] as $p=>$h)if(hash_file('sha256',$root.$p)!==$h['after'])throw new RuntimeException('Published checksum differs');
 $result['status']='SHARED_TEAM_PUBLISHED';$result['completed_at']=gmdate('c');$result['visible_primary']=count(fcc_team_visible_members($actor,$graph));$result['visible_coowner']=count(fcc_team_visible_members($coowner,$graph));
 file_put_contents('shared-release.done',json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
 $phase='snapshot';
 require __DIR__.'/shared-final.php';
}catch(Throwable $e){
 if($published&&!in_array($phase,['applying','verification','snapshot'],true)){$backup=new ZipArchive();if($backup->open('shared-before.zip')===true)foreach(array_reverse($published) as $p){if($manifest['files'][$p]['before']===null){unlink($root.$p);}else{$tmp=__DIR__.'/shared-restore.tmp';file_put_contents($tmp,$backup->getFromName($p));chmod($tmp,0644);rename($tmp,$root.$p);}}}
 file_put_contents('shared-release.failed',json_encode(['phase'=>$phase,'error'=>$e->getMessage(),'file'=>$e->getFile(),'line'=>$e->getLine(),'progress'=>$result],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
 try{require __DIR__.'/shared-final.php';}catch(Throwable $snapshotError){file_put_contents('shared-final.failed','Private snapshot requires review');}
}finally{if($graphLocked)fcc_partner_query("SELECT RELEASE_LOCK('fcc_team_graph')");if(isset($db))$db->query("SELECT RELEASE_LOCK('fcc_partner_shared_release')");}
