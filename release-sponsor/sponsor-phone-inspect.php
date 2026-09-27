<?php
/** Private read-only source and registration contact snapshot. */
if(PHP_SAPI!=='cli')exit(1);umask(0077);chdir(__DIR__);
$lock=fopen('sponsor-phone-inspect.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
if(is_file('sponsor-phone-inspect.json')||is_file('sponsor-phone-inspect.failed'))exit;
try {
 $root='/home/forevercardclub/public_html/';
 define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
 require $root.'app/init.php';\Altum\Cache::initialize();
 if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
 $out=['at'=>gmdate('c'),'files'=>[]];
 foreach(['app/helpers/fcc_team.php','app/helpers/fcc_team_work.php','themes/altum/views/partner/team.php','themes/altum/views/admin/partials/sponsor-fields.php','themes/altum/assets/css/fcc-team.css'] as $p)$out['files'][$p]=['sha256'=>hash_file('sha256',$root.$p),'content'=>base64_encode(file_get_contents($root.$p))];
 $out['contacts']=fcc_partner_rows("SELECT u.user_id,u.name,u.status,JSON_EXTRACT(u.preferences,'$.meta') meta,t.* FROM users u JOIN fcc_team_members t ON t.user_id=u.user_id ORDER BY u.user_id");
 $out['owner_contact_edits']=fcc_partner_rows("SELECT user_id,after_json FROM fcc_team_audit WHERE user_id=actor_user_id ORDER BY id");
 $out['rollout']=fcc_partner_rows('SELECT user_id,allowed FROM fcc_partner_rollout_access ORDER BY user_id');
 file_put_contents('sponsor-phone-inspect.json',json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
}catch(Throwable $e){file_put_contents('sponsor-phone-inspect.failed',$e->getMessage());}
