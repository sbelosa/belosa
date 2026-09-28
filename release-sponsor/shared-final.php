<?php
if(PHP_SAPI!=='cli')exit(1);
umask(0077);chdir(__DIR__);
if(is_file('shared-final.done')||is_file('shared-final.failed'))exit;
$lock=fopen('shared-final.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
try {
 $root='/home/forevercardclub/public_html/';
 if(!function_exists('fcc_partner_one')){define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
 require $root.'app/init.php';\Altum\Cache::initialize();}
 if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
 $fbo=forever_business_user_fbo_sql_expression('preferences');
 $out=['at'=>gmdate('c'),'transfer_public_key'=>file_get_contents('gen-transfer-public.pem')];
 $out['users']=fcc_partner_rows("SELECT user_id,name,email,status,type,$fbo fbo_id FROM users ORDER BY user_id");
 $out['team']=fcc_partner_rows('SELECT * FROM fcc_team_members');
 $out['members']=fcc_partner_rows('SELECT fbo_id,name,parent_fbo_id,title,is_privacy_requested,is_in_current_structure FROM forever_business_members');
 $out['focus']=fcc_partner_rows('SELECT fbo_id,snapshot_date,sponsor_fbo_id,sponsor_name,source_import_id FROM forever_business_focus_metrics ORDER BY snapshot_date DESC');
 $out['imports']=fcc_partner_rows('SELECT import_id,report_kind,row_count,status,completed_at FROM forever_business_imports ORDER BY import_id DESC LIMIT 20');
 $out['rollout']=fcc_partner_rows('SELECT user_id,allowed FROM fcc_partner_rollout_access ORDER BY user_id');
 $out['runtime_hash']=hash_file('sha256','/home/forevercardclub/fcc-partner-runtime.php');
 $out['account_groups']=function_exists('fcc_team_accounts_ready')&&fcc_team_accounts_ready()?fcc_partner_rows('SELECT * FROM fcc_team_accounts'):[];
 $paths=['app/helpers/fcc_team.php','app/helpers/fcc_team_accounts.php','app/helpers/fcc_team_insights.php','app/helpers/fcc_team_notifications.php','app/helpers/fcc_team_work.php','app/helpers/fcc_coach_mentor.php','app/config/fcc_coach_mentor.hr.txt','themes/altum/views/partner/team.php','themes/altum/assets/css/fcc-team.css','themes/altum/views/admin/partials/sponsor-fields.php','scripts/migrations/partner/020_team_shared_accounts.sql'];
 foreach($paths as $p)$out['files'][$p]=is_file($root.$p)?['sha256'=>hash_file('sha256',$root.$p),'base64'=>base64_encode(file_get_contents($root.$p))]:null;
 $out['release']=is_file('shared-release.done')?json_decode(file_get_contents('shared-release.done'),true):null;
 $out['failure']=is_file('shared-release.failed')?json_decode(file_get_contents('shared-release.failed'),true):null;
 $plain=json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
 $key=random_bytes(32);$iv=random_bytes(12);$tag='';
 $cipher=openssl_encrypt(gzencode($plain),'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);
 if($cipher===false||!openssl_public_encrypt($key,$wrapped,file_get_contents('shared-client-public.pem'),OPENSSL_PKCS1_OAEP_PADDING))throw new RuntimeException('Encryption failed');
 $sealed=['key'=>base64_encode($wrapped),'iv'=>base64_encode($iv),'tag'=>base64_encode($tag),'data'=>base64_encode($cipher)];
 file_put_contents('shared-final.sealed.json',json_encode($sealed));
 file_put_contents('shared-final.done',json_encode(['at'=>$out['at'],'users'=>count($out['users']),'team_records'=>count($out['team'])]));
}catch(Throwable $e){file_put_contents('shared-final.failed',json_encode(['error'=>$e->getMessage()]));}
