<?php
if(PHP_SAPI!=='cli')exit(1);
umask(0077);chdir(__DIR__);
$lock=fopen('outreach.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))exit;
try {
 $root='/home/forevercardclub/public_html/';
 define('ALTUMCODE',66);define('DEBUG',0);define('MYSQL_DEBUG',0);define('LOGGING',1);define('CACHE',0);
 require $root.'app/init.php';\Altum\Cache::initialize();
 if(DATABASE_NAME!=='forevercardclub_database')throw new RuntimeException('Wrong database');
 if(!is_file('migration.done')) {
  $schema=file_get_contents('schema.sql');if(!str_starts_with($schema,'CREATE TABLE IF NOT EXISTS fcc_team_outreach ('))throw new RuntimeException('Unexpected schema');
  database()->query($schema);
  $columns=fcc_partner_rows("SHOW COLUMNS FROM fcc_team_outreach");
  if(count($columns)!==20)throw new RuntimeException('Unexpected column count: '.count($columns));
  file_put_contents('migration.done',json_encode(['at'=>gmdate('c'),'columns'=>count($columns),'schema_sha256'=>hash('sha256',$schema)]));
 }
 if(is_file('verify.enabled')&&!is_file('verification.done')) {
  if(!function_exists('fcc_team_outreach_page')||!fcc_team_outreach_ready())throw new RuntimeException('Feature unavailable');
  $graph=fcc_team_graph();$subject=176;$actor=555;
  $target=fcc_team_outreach_target($actor,$subject,$graph);$page=fcc_team_outreach_page($actor,$subject);
  if($target['user_id']!==fcc_team_primary($subject,$graph)||$target['phone']!==$graph[$subject]['phone']||!$target['phone'])throw new RuntimeException('Wrong contact target');
  $aliases=fcc_team_visible_members(278,$graph);$main=fcc_team_visible_members(555,$graph);
  $free=0;foreach(fcc_partner_rows('SELECT user_id FROM users WHERE status=1') as $u)if(!fcc_pro_has_access((int)$u['user_id'])){if(fcc_team_outreach_context((int)$u['user_id'])!==[])throw new RuntimeException('Free context leak');$free++;}
  $before=fcc_partner_one('SELECT COUNT(*) n FROM fcc_team_outreach');
  // Supply the URL normally initialized by the HTTP front controller.
  if(!defined('ASSETS_FULL_URL'))define('ASSETS_FULL_URL',SITE_URL.'themes/altum/assets/');
  // Read-only view render catches integration errors without changing any member or message.
  $h='fcc_partner_h';$uid=$actor;$data=(object)['team'=>fcc_team_page($actor,['member'=>$subject]),'form_key'=>str_repeat('0',32)];$token='';$request='';
  ob_start();require $root.'themes/altum/views/partner/team.php';$html=ob_get_clean();
  if(!str_contains($html,'data-team-outreach')||!str_contains($html,'Pripremi s Coachom')||!str_contains($html,'tel:'.$target['phone']))throw new RuntimeException('Rendered profile incomplete');
  $after=fcc_partner_one('SELECT COUNT(*) n FROM fcc_team_outreach');if($before['n']!==$after['n'])throw new RuntimeException('Read check wrote activity');
  $webinar=$page['webinar'];
  if(is_file('outreach.failed'))rename('outreach.failed','outreach.resolved');
  file_put_contents('verification.done',json_encode(['at'=>gmdate('c'),'contact_target_verified'=>true,'render_verified'=>true,'free_accounts_checked'=>$free,'main_members'=>count($main),'coowner_members'=>count($aliases),'webinar_available'=>(bool)$webinar,'webinar_date'=>$webinar['when']??null,'message_rows_changed'=>false]));
 }
}catch(Throwable $e){file_put_contents('outreach.failed',json_encode(['class'=>get_class($e),'message'=>$e->getMessage()]));}
