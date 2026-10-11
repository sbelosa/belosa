<?php
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_los_telemetry.php';
function fcc_rel_t(string $key): string {
 static $copy; $copy??=json_decode(file_get_contents(__DIR__.'/../config/reliability-locales.json'),true);
 return $copy[fcc_locale()][$key]??fcc_t($copy['hr'][$key]??$copy['en'][$key]??$key);
}
function fcc_rel_ready(): bool {
 static $ready;return $ready??=(bool)fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_member_usage'");
}
function fcc_rel_contact_archive(int $uid,int $id,bool $restore): void {
 fcc_partner_contact($uid,$id);
 fcc_partner_query('UPDATE fcc_partner_contacts SET archived_at='.($restore?'NULL':'COALESCE(archived_at,UTC_TIMESTAMP())').',version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND id=?',[$uid,$id]);
}
function fcc_rel_notice_open(int $uid,int $id): string {
 $n=fcc_partner_one('SELECT path FROM fcc_partner_notifications WHERE user_id=? AND id=?',[$uid,$id]);
 if(!$n)throw new InvalidArgumentException(fcc_t('Obavijest nije pronađena.'));
 fcc_partner_query('UPDATE fcc_partner_notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE user_id=? AND id=?',[$uid,$id]);
 return fcc_partner_notification_path((string)$n['path']);
}
function fcc_rel_unread(int $uid): int {
 return (int)(fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_notifications WHERE user_id=? AND read_at IS NULL',[$uid])['n']??0);
}
/** First party module totals only. Never store URLs, contact content or impersonated usage. */
function fcc_rel_usage(int $uid,string $module,bool $action=false,bool $heartbeat=false): void {
 if(!fcc_rel_ready()||$uid<=0||session_get('admin_user_id')||\Altum\Teams::is_delegated())return;
 $modules=['today','contacts','share','cards','more','plan','education','team','notifications','settings','install','admin','preview','curriculum','webinars','business','coach','events','points','other'];
 if(!in_array($module,$modules,true))$module='other';
 $day=fcc_partner_today();
 fcc_partner_query('INSERT INTO fcc_member_presence(user_id,last_seen,module) VALUES(?,UTC_TIMESTAMP(),?) ON DUPLICATE KEY UPDATE last_seen=VALUES(last_seen),module=VALUES(module)',[$uid,$module]);
 fcc_partner_query('INSERT INTO fcc_member_usage(user_id,day,module,views,actions,last_seen) VALUES(?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE views=views+VALUES(views),actions=actions+VALUES(actions),last_seen=VALUES(last_seen)',[$uid,$day,$module,$action||$heartbeat?0:1,$action?1:0]);
}
function fcc_rel_monitor(): array {
 if(!\Altum\Authentication::is_admin())throw new RuntimeException('Administrator required.');
 $now=new DateTimeImmutable('now',new DateTimeZone('Europe/Zagreb'));$from=$now->modify('-29 days')->format('Y-m-d');
 $counts=[];foreach(['online'=>5*60,'active24'=>86400,'active7'=>7*86400,'active30'=>30*86400] as $k=>$s)$counts[$k]=(int)(fcc_partner_one('SELECT COUNT(*) n FROM fcc_member_presence p JOIN users u ON u.user_id=p.user_id WHERE u.status=1 AND p.last_seen>=?', [gmdate('Y-m-d H:i:s',time()-$s)])['n']??0);
 $daily=fcc_partner_rows('SELECT day,COUNT(DISTINCT user_id) accounts,SUM(views) views,SUM(actions) actions FROM fcc_member_usage WHERE day>=? GROUP BY day ORDER BY day',[$from]);
 $map=array_column($daily,null,'day');$days=[];for($i=29;$i>=0;$i--){$d=$now->modify('-'.$i.' days')->format('Y-m-d');$days[]=$map[$d]??['day'=>$d,'accounts'=>0,'views'=>0,'actions'=>0];}
 return ['counts'=>$counts,'daily'=>$days,'modules'=>fcc_partner_rows('SELECT module,COUNT(DISTINCT user_id) accounts,SUM(views) views,SUM(actions) actions FROM fcc_member_usage WHERE day>=? GROUP BY module ORDER BY views DESC',[$from]),'online'=>fcc_partner_rows('SELECT u.user_id,u.name,p.last_seen,p.module FROM fcc_member_presence p JOIN users u ON u.user_id=p.user_id WHERE u.status=1 AND p.last_seen>=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 5 MINUTE) ORDER BY p.last_seen DESC LIMIT 100')];
}
function fcc_rel_avatar(int $uid): ?string {
 $id=fc_get_user_main_biolink_id($uid);if(!$id)return null;
 foreach(fcc_partner_rows("SELECT type,settings FROM biolinks_blocks WHERE link_id=? AND user_id=? AND is_enabled=1 AND type IN ('header','avatar') ORDER BY `order`,biolink_block_id",[$id,$uid]) as $b){
  $s=json_decode($b['settings']??'{}',true)?:[];$file=$b['type']==='header'?($s['avatar']??''):($s['image']??'');
  if(is_string($file)&&$file!==''&&basename($file)===$file)return \Altum\Uploads::get_full_url('avatars').rawurlencode($file);
 }
 return null;
}
