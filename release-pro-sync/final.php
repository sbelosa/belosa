<?php
if(PHP_SAPI!=='cli')exit(1);
umask(0077);chdir(__DIR__);
if(is_file('pro-sync-final.done')||is_file('pro-sync-final.failed'))exit;
$lock=fopen('pro-sync-final.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
try {
 $root='/home/forevercardclub/public_html/';
 if(!function_exists('fcc_partner_one')){define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
 require $root.'app/init.php';\Altum\Cache::initialize();}
 if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
 $fbo=forever_business_user_fbo_sql_expression('preferences');
 $out=['at'=>gmdate('c'),'transfer_public_key'=>file_get_contents('gen-transfer-public.pem')];
 $out['users']=fcc_partner_rows("SELECT user_id,name,email,status,type,$fbo fbo_id,plan_id,plan_settings,plan_expiration_date,extra FROM users ORDER BY user_id");
 $out['metrics']=fcc_partner_rows("SELECT * FROM forever_business_metrics WHERE period_month=DATE_FORMAT(UTC_TIMESTAMP(),'%Y-%m-01')");
 $out['sync_accounts']=forever_business_get_registered_sync_accounts(gmdate('Y-m'));
 $out['sync_status']=forever_business_get_dashboard(1,true,'360000760944',gmdate('Y-m-01'))['summary'];
 $out['team']=fcc_partner_rows('SELECT * FROM fcc_team_members');
 $out['members']=fcc_partner_rows('SELECT fbo_id,name,parent_fbo_id,title,is_privacy_requested,is_in_current_structure FROM forever_business_members');
 $out['focus']=fcc_partner_rows('SELECT fbo_id,snapshot_date,sponsor_fbo_id,sponsor_name,source_import_id FROM forever_business_focus_metrics ORDER BY snapshot_date DESC');
 $out['imports']=fcc_partner_rows('SELECT import_id,report_kind,row_count,status,completed_at FROM forever_business_imports ORDER BY import_id DESC LIMIT 20');
 $out['rollout']=fcc_partner_rows('SELECT user_id,allowed FROM fcc_partner_rollout_access ORDER BY user_id');
 $out['runtime_hash']=hash_file('sha256','/home/forevercardclub/fcc-partner-runtime.php');
 $out['account_groups']=function_exists('fcc_team_accounts_ready')&&fcc_team_accounts_ready()?fcc_partner_rows('SELECT * FROM fcc_team_accounts'):[];
 $out['pro_checks']=['free_checked'=>0,'free_leaks'=>0,'paid_checked'=>0];
 $graph=fcc_team_graph();
 foreach($out['users'] as $u) {
  if((int)$u['status']!==1)continue;
  $uid=(int)$u['user_id'];
  if(!fcc_pro_has_access($uid)){
   $out['pro_checks']['free_checked']++;
   if(fcc_team_visible_members($uid,$graph)!==[]||fcc_team_coach_context($uid)!==[])$out['pro_checks']['free_leaks']++;
  }else{$out['pro_checks']['paid_checked']++;}
 }
 foreach([555,278] as $uid){$rows=fcc_team_visible_members($uid,$graph);$ids=array_column($rows,'user_id');sort($ids);$out['pro_checks']['owners'][$uid]=['active'=>fcc_pro_has_access($uid),'members'=>count($rows),'ids_hash'=>hash('sha256',json_encode($ids))];}
 $out['pro_checks']['guard']=!fcc_pro_access_for_user(['status'=>1,'plan_settings'=>'{}','plan_expiration_date'=>'2034-01-01 00:00:00']);
 $paths=['app/helpers/fcc_pro.php','app/helpers/forever_business.php','app/controllers/Partner.php','themes/altum/assets/js/fcc-pro.js','themes/altum/assets/css/fcc-pro.css','themes/altum/views/partner/pro-upgrade.php'];
 foreach($paths as $p)$out['files'][$p]=is_file($root.$p)?['sha256'=>hash_file('sha256',$root.$p)]:null;
 $plain=json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
 $key=random_bytes(32);$iv=random_bytes(12);$tag='';
 $cipher=openssl_encrypt(gzencode($plain),'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);
 if($cipher===false||!openssl_public_encrypt($key,$wrapped,file_get_contents('shared-client-public.pem'),OPENSSL_PKCS1_OAEP_PADDING))throw new RuntimeException('Encryption failed');
 $sealed=['key'=>base64_encode($wrapped),'iv'=>base64_encode($iv),'tag'=>base64_encode($tag),'data'=>base64_encode($cipher)];
 file_put_contents('pro-sync-final.sealed.json',json_encode($sealed));
 file_put_contents('pro-sync-final.done',json_encode(['at'=>$out['at'],'users'=>count($out['users']),'team_records'=>count($out['team'])]));
}catch(Throwable $e){file_put_contents('pro-sync-final.failed',json_encode(['error'=>$e->getMessage()]));}
