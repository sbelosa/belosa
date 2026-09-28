<?php
/** Private, read-only sponsor inventory. No credentials or raw contacts are exported. */
if(PHP_SAPI!=='cli')exit(1);umask(0077);chdir(__DIR__);
if(is_file('sponsor-gen-inventory-v3.done')||is_file('sponsor-gen-inventory-v3.failed'))exit;
$lock=fopen('sponsor-gen-inventory-v3.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
try {
 if(!defined('ALTUMCODE')){define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
 require '/home/forevercardclub/public_html/app/init.php';\Altum\Cache::initialize();}
 if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
 $fbo=forever_business_user_fbo_sql_expression('preferences');
 if(!is_file('gen-transfer-private.pem')){
  $key=openssl_pkey_new(['private_key_bits'=>3072,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
  if(!$key||!openssl_pkey_export($key,$private))throw new RuntimeException('Private transfer key creation failed');
  file_put_contents('gen-transfer-private.pem',$private);chmod('gen-transfer-private.pem',0600);
  file_put_contents('gen-transfer-public.pem',openssl_pkey_get_details($key)['key']);
 }
 $out=['at'=>gmdate('c'),'transfer_public_key'=>file_get_contents('gen-transfer-public.pem')];
 $out['users']=fcc_partner_rows("SELECT user_id,name,email,status,type,$fbo fbo_id FROM users ORDER BY user_id");
 $out['team']=fcc_partner_rows('SELECT user_id,fbo_id,sponsor_user_id,sponsor_fbo_id,is_manager,version,relationship_version,source,reason,linked_at,phone,contact_confirmed FROM fcc_team_members');
 $out['members']=fcc_partner_rows('SELECT fbo_id,name,parent_fbo_id,title,is_privacy_requested,is_in_current_structure FROM forever_business_members');
 $out['focus']=fcc_partner_rows('SELECT fbo_id,snapshot_date,sponsor_fbo_id,sponsor_name,source_import_id FROM forever_business_focus_metrics ORDER BY snapshot_date DESC');
 $out['research']=is_file('sponsor-research.done')?json_decode(file_get_contents('sponsor-research.done'),true):null;
 $out['imports']=fcc_partner_rows('SELECT import_id,report_kind,row_count,status,completed_at FROM forever_business_imports ORDER BY import_id DESC LIMIT 20');
 file_put_contents('sponsor-gen-inventory-v3.json',json_encode($out,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
 file_put_contents('sponsor-gen-inventory-v3.done',json_encode(['at'=>$out['at'],'users'=>count($out['users']),'team_records'=>count($out['team'])]));
}catch(Throwable $e){file_put_contents('sponsor-gen-inventory-v3.failed',$e->getMessage());}
