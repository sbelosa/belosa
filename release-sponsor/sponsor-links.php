<?php
/** Private CLI only: reviewed FCC user-ID pairs arrive through hosting, never the public repo. */
if(PHP_SAPI!=='cli')exit(1);umask(0077);chdir(__DIR__);
$encoded=$argv[1]??'';$expected=$argv[2]??'';
if(!preg_match('/^[a-f0-9]{64}$/D',$expected))exit(1);
$plan=json_decode(gzdecode(base64_decode($encoded,true)),true,512,JSON_THROW_ON_ERROR);
if(!is_array($plan)||count($plan)>15)exit(1);
$key=substr(hash('sha256',$encoded.$expected),0,16);$prefix='sponsor-links-'.$key;
$lock=fopen($prefix.'.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
if(is_file($prefix.'.done')||is_file($prefix.'.failed'))exit;
$result=['at'=>gmdate('c'),'linked'=>[]];
try {
 define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);require '/home/forevercardclub/public_html/app/init.php';\Altum\Cache::initialize();
 if(DATABASE_NAME!=='forevercardclub_database'||!fcc_team_ready())throw new RuntimeException('Production module is not ready');
 $g=fcc_team_graph();$byFbo=[];$identities=[];
 foreach($g as $uid=>$u)if((int)$u['status']===1&&$u['current_fbo'])$byFbo[$u['current_fbo']][]=$uid;
 foreach($plan as [$uid,$sid]){
  if(!is_int($uid)||!is_int($sid)||$uid===$sid||!isset($g[$uid],$g[$sid]))throw new RuntimeException('Invalid pair');
  if(($byFbo[$g[$uid]['current_fbo']]??[])!==[$uid]||($byFbo[$g[$sid]['current_fbo']]??[])!==[$sid])throw new RuntimeException('Ambiguous account');
  if(!empty($g[$uid]['is_privacy_requested'])||!empty($g[$sid]['is_privacy_requested']))throw new RuntimeException('Privacy restricted');
  if(fcc_team_record($uid))throw new RuntimeException('Relationship was already edited');
  $identities[]=$uid.':'.$g[$uid]['current_fbo'].':'.$sid.':'.$g[$sid]['current_fbo'];
 }
 if(!hash_equals($expected,hash('sha256',implode("\n",$identities))))throw new RuntimeException('Reviewed identities changed');
 foreach($plan as [$uid,$sid]){
  fcc_team_admin_save(1,$uid,['team_version'=>0,'team_sponsor_id'=>$sid,'team_is_manager'=>0,'team_reason'=>'Izravni Sponsor ID provjeren u FLP360 Focus Group za rujan 2026., pregled 27.09.2026.']);
  if(fcc_team_edge($uid,fcc_team_graph())!==$sid)throw new RuntimeException('Verification failed');
  $result['linked'][]=[$uid,$sid];
 }
 // A confirmed sponsor also needs a contact record to enter their own business phone.
 foreach(array_unique(array_column($plan,1)) as $sid)if(!fcc_team_record((int)$sid)) {
  fcc_team_admin_save(1,(int)$sid,['team_version'=>0,'team_sponsor_id'=>0,'team_is_manager'=>!empty($g[$sid]['manager'])?1:0,'team_reason'=>'FCC račun izravnog sponzora provjeren kroz potvrđeno povezivanje 27.09.2026. Poslovni telefon potvrđuje vlasnik računa.']);
 }
 $result['count']=count($result['linked']);$result['status']='LINKED';file_put_contents($prefix.'.done',json_encode($result));
}catch(Throwable $e){$result['error']=$e->getMessage();file_put_contents($prefix.'.failed',json_encode($result));}
