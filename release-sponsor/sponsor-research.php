<?php
/** Private CLI: decrypt a reviewed plan, preserve existing links, and audit each new relationship. */
if(PHP_SAPI!=='cli')exit(1);umask(0077);chdir(__DIR__);
if(count($argv)!==2||!preg_match('/^[a-f0-9]{64}$/D',$argv[1]))exit(1);
$lock=fopen('sponsor-links-global.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
$prefix='sponsor-research';if(is_file($prefix.'.done')||is_file($prefix.'.failed'))exit;
$result=['at'=>gmdate('c'),'linked'=>[],'skipped'=>[]];
try {
 $sealed=json_decode(file_get_contents('sponsor-research.sealed.json'),true,512,JSON_THROW_ON_ERROR);
 $plain=openssl_decrypt(base64_decode($sealed['data']),'aes-256-gcm',hex2bin($argv[1]),OPENSSL_RAW_DATA,base64_decode($sealed['iv']),base64_decode($sealed['tag']));
 if($plain===false)throw new RuntimeException('Plan authentication failed');
 $plan=json_decode($plain,true,512,JSON_THROW_ON_ERROR);
 if(!is_array($plan['pairs']??null)||count($plan['pairs'])>719)throw new RuntimeException('Invalid reviewed plan');
 define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
 require '/home/forevercardclub/public_html/app/init.php';\Altum\Cache::initialize();
 if(DATABASE_NAME!=='forevercardclub_database'||!fcc_team_ready()||!fcc_team_admin(555))throw new RuntimeException('Wrong production context');
 $identity=function(int $uid): string {
  $u=fcc_partner_one('SELECT email,preferences FROM users WHERE user_id=?',[$uid]);
  return hash('sha256',$uid.':'.mb_strtolower(trim($u['email']??'')).':'.fcc_team_fbo($u??[]));
 };
 if(!hash_equals($plan['actor_identity'],$identity(555)))throw new RuntimeException('Administrator identity changed');
 $g=fcc_team_graph();$byFbo=[];
 foreach($g as $uid=>$u)if((int)$u['status']===1&&$u['current_fbo'])$byFbo[$u['current_fbo']][]=$uid;
 $seen=[];$approved=[];
 foreach($plan['pairs'] as $p){
  $uid=$p['uid'];$sid=$p['sid'];
  if(!is_int($uid)||!is_int($sid)||$uid===$sid||isset($seen[$uid])||!isset($g[$uid],$g[$sid]))throw new RuntimeException('Invalid pair');
  $seen[$uid]=true;$u=$g[$uid];$s=$g[$sid];$old=fcc_team_record($uid);
  if((int)$u['status']!==1||(int)$s['status']!==1||!empty($u['is_privacy_requested'])||!empty($s['is_privacy_requested'])){$result['skipped'][]=[$uid,'status_or_privacy'];continue;}
  if(($byFbo[$u['current_fbo']]??[])!==[$uid]||($sid!==555&&($byFbo[$s['current_fbo']]??[])!==[$sid])){$result['skipped'][]=[$uid,'ambiguous'];continue;}
  if($u['current_fbo']!==$p['fbo']||$s['current_fbo']!==$p['sponsor_fbo']||!hash_equals($p['identity'],$identity($uid))||!hash_equals($p['sponsor_identity'],$identity($sid)))throw new RuntimeException('Reviewed identity changed');
  if((int)($old['version']??0)!==$p['version']||!empty($old['sponsor_user_id'])){$result['skipped'][]=[$uid,'already_edited'];continue;}
  if(!in_array($p['manager'],[0,1],true)||!in_array($p['source'],['focus','tree'],true))throw new RuntimeException('Invalid evidence');
  $approved[]=$p;
 }
 foreach($approved as $p){
  $uid=$p['uid'];$old=fcc_team_record($uid);
  if((int)($old['version']??0)!==$p['version']||!empty($old['sponsor_user_id'])){$result['skipped'][]=[$uid,'concurrently_edited'];continue;}
  fcc_team_admin_save(555,$uid,[
   'team_version'=>$p['version'],'team_sponsor_id'=>$p['sid'],'team_is_manager'=>$p['manager'],
   'team_phone'=>$old['phone']??'','team_contact_confirmed'=>(int)($old['contact_confirmed']??0),
   'team_reason'=>$p['source']==='focus'?'Izravni Sponsor ID provjeren u FLP360 Focus Group, pregled 27.09.2026. Jedinstveni FCC računi potvrđeni.':'Izravna sponzorska grana provjerena u FLP360 Downline prikazu za rujan 2026., pregled 27.09.2026. Uvažena menadžerska granica i jedinstveni FCC računi.'
  ]);
  if(fcc_team_edge($uid,fcc_team_graph())!==$p['sid'])throw new RuntimeException('Saved edge not effective');
  $result['linked'][]=[$uid,$p['sid']];
 }
 foreach($plan['sponsor_roles'] as $sid=>$manager){
  $sid=(int)$sid;
  if(fcc_team_record($sid))continue;
  if(!in_array($sid,array_column($result['linked'],1),true))continue;
  fcc_team_admin_save(555,$sid,['team_version'=>0,'team_sponsor_id'=>0,'team_is_manager'=>$manager,'team_reason'=>'FCC račun i menadžerska razina sponzora provjereni u FLP360 Downline prikazu 27.09.2026. Poslovni telefon potvrđuje vlasnik računa.']);
 }
 $result['status']='LINKED';$result['count']=count($result['linked']);
 file_put_contents($prefix.'.done',json_encode($result));
 require __DIR__.'/sponsor-final-inventory.php';
}catch(Throwable $e){$result['error']=$e->getMessage();file_put_contents($prefix.'.failed',json_encode($result));}
