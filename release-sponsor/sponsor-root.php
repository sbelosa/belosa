<?php
/** Private CLI: the user selects the canonical sponsor; reviewed IDs never enter the repository. */
if(PHP_SAPI !== 'cli') exit(1);
umask(0077); chdir(__DIR__);
if(count($argv)!==6 || !ctype_digit($argv[1]) || !preg_match('/^\d+(,\d+){0,11}$/D',$argv[2]) || !ctype_xdigit($argv[3]) || !ctype_xdigit($argv[4]) || !preg_match('/^[a-f0-9]{64}$/D',$argv[5])) exit(1);
$sid=(int)$argv[1]; $ids=array_map('intval',explode(',',$argv[2])); $versions=hexdec($argv[3]); $managers=hexdec($argv[4]); $expected=$argv[5];
if(count(array_unique($ids))!==count($ids) || in_array($sid,$ids,true)) exit(1);
$prefix='sponsor-root-'.substr(hash('sha256',implode('|',array_slice($argv,1))),0,16);
$lock=fopen('sponsor-links-global.lock','c'); if(!$lock || !flock($lock,LOCK_EX|LOCK_NB)) exit;
if(is_file($prefix.'.done') || is_file($prefix.'.failed')) exit;
$result=['at'=>gmdate('c'),'linked'=>[]];
try {
 define('ALTUMCODE',66); define('DEBUG',0); define('MYSQL_DEBUG',0); define('LOGGING',1); define('CACHE',0);
 require '/home/forevercardclub/public_html/app/init.php'; \Altum\Cache::initialize();
 if(DATABASE_NAME!=='forevercardclub_database' || !fcc_team_ready() || !fcc_team_admin($sid)) throw new RuntimeException('Selected administrator unavailable');
 $g=fcc_team_graph(); $root=$g[$sid]??null; $rootRecord=fcc_team_record($sid);
 $account=fcc_partner_one('SELECT email FROM users WHERE user_id=?',[$sid]);
 if(!$root || (int)$root['status']!==1 || !$root['current_fbo'] || !empty($root['is_privacy_requested'])) throw new RuntimeException('Selected sponsor unavailable');
 $identity=[$sid.':'.mb_strtolower(trim($account['email'])).':'.$root['current_fbo']];
 $byFbo=[]; foreach($g as $uid=>$u) if((int)$u['status']===1 && $u['current_fbo']) $byFbo[$u['current_fbo']][]=$uid;
 $records=[];
 foreach($ids as $index=>$uid) {
  $u=$g[$uid]??null; $records[$uid]=fcc_team_record($uid); $old=$records[$uid]; $version=($versions>>$index)&1; $manager=($managers>>$index)&1;
  if(!$u || (int)$u['status']!==1 || !empty($u['is_privacy_requested']) || ($byFbo[$u['current_fbo']]??[])!==[$uid]) throw new RuntimeException('Child account unavailable or ambiguous');
  if((int)($old['version']??0)!==$version || !empty($old['sponsor_user_id'])) throw new RuntimeException('Relationship already edited');
  $identity[]=$uid.':'.$u['current_fbo'].':'.$version.':'.$manager;
 }
 // Includes the live root email and Forever ID, so the selected account cannot silently change.
 if(!hash_equals($expected,hash('sha256',implode("\n",$identity)))) throw new RuntimeException('Reviewed identity changed');
 if($rootRecord && (!$root['manager'] || $rootRecord['fbo_id']!==$root['current_fbo'])) throw new RuntimeException('Root role needs review');
 if(!$rootRecord) fcc_team_admin_save($sid,$sid,['team_version'=>0,'team_sponsor_id'=>0,'team_is_manager'=>1,'team_reason'=>'Glavni sponzorski račun izričito odabran korisnikovom email adresom 27.09.2026. FLP menadžerska razina provjerena.']);
 foreach($ids as $index=>$uid) {
  $old=$records[$uid];
  fcc_team_admin_save($sid,$uid,[
   'team_version'=>($versions>>$index)&1,'team_sponsor_id'=>$sid,'team_is_manager'=>($managers>>$index)&1,
   'team_phone'=>$old['phone']??'','team_contact_confirmed'=>(int)($old['contact_confirmed']??0),
   'team_reason'=>'Izravni Sponsor ID provjeren u FLP360 Focus Group za rujan 2026., pregled 27.09.2026. Glavni FCC račun sponzora izričito potvrdio vlasnik.'
  ]);
  if(fcc_team_edge($uid,fcc_team_graph())!==$sid) throw new RuntimeException('Saved relationship verification failed');
  $result['linked'][]=$uid;
 }
 $g=fcc_team_graph(); $visible=0; $direct=0;
 foreach($g as $uid=>$u) {
  if(fcc_team_edge($uid,$g)===$sid) $direct++;
  if(in_array($sid,fcc_team_upline($uid,$g),true)) $visible++;
 }
 $result+=['status'=>'LINKED','count'=>count($result['linked']),'direct'=>$direct,'visible'=>$visible];
 file_put_contents($prefix.'.done',json_encode($result));
} catch(Throwable $e) { $result['error']=$e->getMessage(); file_put_contents($prefix.'.failed',json_encode($result)); }
