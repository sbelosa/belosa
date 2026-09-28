<?php
if(PHP_SAPI!=='cli')exit(1);
umask(0077);chdir(__DIR__);
if(is_file('pro-sync-audit.done')||is_file('pro-sync-audit.failed'))exit;
$lock=fopen('pro-sync-audit.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
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
 $paths=['app/init.php', 'app/controllers/Partner.php', 'app/controllers/ForeverBusinessSync.php', 'app/helpers/forever_business.php', 'app/helpers/fcc_ai.php', 'app/helpers/fcc_partner.php', 'app/helpers/fcc_partner_journey.php', 'app/helpers/fcc_partner_webinars.php', 'app/helpers/fcc_business.php', 'app/helpers/fcc_team.php', 'app/helpers/fcc_team_accounts.php', 'app/helpers/fcc_team_insights.php', 'app/helpers/fcc_team_work.php', 'app/helpers/fcc_team_notifications.php', 'themes/altum/views/app_wrapper.php', 'themes/altum/views/partner/navigation.php', 'themes/altum/views/partner/index.php', 'themes/altum/views/partner/tools.php', 'themes/altum/views/partner/package.php', 'themes/altum/views/partials/app_sidebar.php', 'themes/altum/views/partner/share-tabs.php', 'themes/altum/views/partner/journey-nav.php', 'themes/altum/views/partner/webinar-workspace.php', 'themes/altum/views/partner/business-workspace.php', 'themes/altum/views/partner/journey-coach.php', 'app/helpers/fcc_partner_tools.php'];
 foreach($paths as $p)$out['files'][$p]=is_file($root.$p)?['sha256'=>hash_file('sha256',$root.$p),'base64'=>base64_encode(file_get_contents($root.$p))]:null;
 $out['old_release']=is_file('shared-release.done')?json_decode(file_get_contents('shared-release.done'),true):null;
 $out['failure']=is_file('shared-release.failed')?json_decode(file_get_contents('shared-release.failed'),true):null;
 $plain=json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
 $key=random_bytes(32);$iv=random_bytes(12);$tag='';
 $cipher=openssl_encrypt(gzencode($plain),'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag);
 if($cipher===false||!openssl_public_encrypt($key,$wrapped,file_get_contents('shared-client-public.pem'),OPENSSL_PKCS1_OAEP_PADDING))throw new RuntimeException('Encryption failed');
 $sealed=['key'=>base64_encode($wrapped),'iv'=>base64_encode($iv),'tag'=>base64_encode($tag),'data'=>base64_encode($cipher)];
 file_put_contents('pro-sync-audit.sealed.json',json_encode($sealed));
 file_put_contents('pro-sync-audit.done',json_encode(['at'=>$out['at'],'users'=>count($out['users']),'team_records'=>count($out['team'])]));
}catch(Throwable $e){file_put_contents('pro-sync-audit.failed',json_encode(['error'=>$e->getMessage()]));}
