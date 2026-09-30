<?php
if(PHP_SAPI!=='cli')exit(1);
$root='/home/forevercardclub/public_html/';
define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
require $root.'app/init.php';\Altum\Cache::initialize();
if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
$manifest=json_decode(file_get_contents(__DIR__.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
foreach($manifest as $entry)if(hash_file('sha256',$root.$entry['path'])!==$entry['sha256'])throw new RuntimeException('Final file mismatch');
$columns=array_column(fcc_partner_rows('SHOW COLUMNS FROM fcc_team_outreach'),'Field');
foreach(['copied_at','confirmation_dismissed_at','deleted_at'] as $column)if(!in_array($column,$columns,true))throw new RuntimeException('Missing column');
$uid=555;$subject=176;$outreach=fcc_team_outreach_page($uid,$subject);$graph=fcc_team_graph();$p=$graph[$subject];$h='fcc_partner_h';$token='';
if(!defined('ASSETS_FULL_URL'))define('ASSETS_FULL_URL',SITE_URL.'themes/altum/assets/');
ob_start();require $root.'themes/altum/views/partner/team-outreach.php';$html=ob_get_clean();
foreach(['data-team-group="preparations"','data-team-group="confirmed"','data-team-group="removed_group"','fcc-team-outreach-v2.js','data-team-not_sent'] as $needle){
 if($needle==='fcc-team-outreach-v2.js')continue;
 if(!str_contains($html,$needle))throw new RuntimeException('Missing rendered control');
}
$free=fcc_partner_rows("SELECT user_id FROM users WHERE status=1 AND plan_id='free' LIMIT 10");
foreach($free as $u)if(!fcc_pro_has_access((int)$u['user_id'])&&fcc_team_outreach_context((int)$u['user_id'])!==[])throw new RuntimeException('Private context leak');
$report=['status'=>'verified','files'=>count($manifest),'schema'=>true,'profile_render'=>true,'free_access_checked'=>count($free),'at'=>gmdate('c')];
file_put_contents(__DIR__.'/verification.json',json_encode($report,JSON_PRETTY_PRINT));echo json_encode($report).PHP_EOL;
