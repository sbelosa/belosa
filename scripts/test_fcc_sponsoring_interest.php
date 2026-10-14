<?php
/* Custom code: FC-2026-10-14: verify actual lead transaction source and mail queue behavior. */
namespace Altum {class Language {public static string $code='hr';}}
namespace {
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
define('ALTUMCODE',true);define('APP_PATH',dirname(__DIR__).'/app/');define('SITE_URL','https://forevercard.club/');define('DATABASE_NAME','isolated_review');define('DATABASE_SERVER','none');
putenv('FCC_BUSINESS_ENABLED=1');putenv('FCC_BUSINESS_SECRET='.base64_encode(str_repeat('x',32)));
class ReviewDb {public int $insert_id=200;public bool $transaction=false;public function begin_transaction(){$this->transaction=true;$GLOBALS['log'][]=['BEGIN'];}public function commit(){$GLOBALS['log'][]=['COMMIT'];$this->transaction=false;}public function rollback(){$GLOBALS['log'][]=['ROLLBACK'];$this->transaction=false;}}
$GLOBALS['db']=new ReviewDb();
function database(){return $GLOBALS['db'];}
function fcc_rollout_managed(){return true;}
function fcc_partner_enabled($uid=0){return true;}
function fcc_rollout_public_owner($uid){return true;}
function fcc_partner_cards($uid){return [['link_id'=>5,'public_url'=>'https://forevercard.club/example','is_enabled'=>1,'url'=>'example']];}
function fc_get_user_default_whatsapp_phone($u){return '385991234567';}
function fcc_t($s){return $s;}
function fcc_locale(){return 'hr';}
function fcc_partner_text($value,$limit){if(!is_scalar($value)&&$value!==null)throw new InvalidArgumentException('Bad value');return mb_substr(trim(strip_tags((string)$value)),0,$limit);}
function fcc_partner_choice($value,$choices){if(!is_string($value)||!array_key_exists($value,$choices))throw new InvalidArgumentException('Bad choice');return $value;}
function fcc_partner_contact_phone($s,$strict=true){return $s;}
function fcc_business_topics(){return ['general'=>'General','work'=>'Work','start'=>'Start','conversation'=>'Conversation'];}
function fcc_business_countries(){return ['hr'=>'Hrvatska','de'=>'Njemačka'];}
function fcc_business_sync_reply_task($uid,$cid){$GLOBALS['log'][]=['reply',$uid,$cid];}
function fcc_partner_contact($uid,$cid){return ['id'=>$cid,'contact_permission'=>'requested'];}
function fcc_partner_notify(...$args){$GLOBALS['log'][]=['notify',...$args];}
function fcc_partner_one($sql,$params=[]){
 $GLOBALS['log'][]=['one',$sql,$params];
 if(str_contains($sql,'information_schema.TABLES'))return ['TABLE_NAME'=>'fcc_business_jobs'];
 if(str_starts_with($sql,'SELECT s.*,p.link_id'))return ['id'=>23,'user_id'=>9,'public_token'=>str_repeat('a',64),'presentation_id'=>11,'link_id'=>5,'asset_id'=>0,'intro'=>'Public introduction','language'=>'hr','presentation_status'=>'active','current_version'=>1,'revoked_at'=>null,'is_demo'=>0,'journey_step_id'=>null,'fbo_snapshot'=>'123456789012'];
 if(str_starts_with($sql,'SELECT u.user_id,u.name'))return ['user_id'=>9,'name'=>'Owner','email'=>'private-login@example.test','preferences'=>json_encode(['meta'=>['foreverId'=>'123456789012']]),'url'=>'example'];
 if(str_starts_with($sql,'SELECT user_id FROM users'))return ['user_id'=>9];
 if(str_starts_with($sql,'SELECT id FROM fcc_business_leads'))return null;
 if(str_starts_with($sql,'SELECT COUNT(*) n FROM fcc_business_leads'))return ['n'=>0];
 throw new RuntimeException('Unhandled one query '.$sql);
}
function fcc_partner_rows($sql,$params=[]){$GLOBALS['log'][]=['rows',$sql,$params];if(str_starts_with($sql,'SELECT * FROM fcc_partner_contacts'))return [];throw new RuntimeException('Unhandled rows query '.$sql);}
function fcc_partner_query($sql,$params=[]){
 $GLOBALS['log'][]=['query',$sql,$params,$GLOBALS['db']->transaction];
 if(str_starts_with($sql,'INSERT INTO fcc_business_leads')||str_starts_with($sql,'INSERT INTO fcc_partner_contacts'))$GLOBALS['db']->insert_id++;
 return (object)['affected_rows'=>1];
}
require APP_PATH.'helpers/fcc_business_presentations.php';
$checks=[];
function ck($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label);$GLOBALS['checks'][]=$label;}
function runSource($source=null,$spoof='',$preview=false){
 $GLOBALS['log']=[];$GLOBALS['db']->insert_id=200;
 $form=['name'=>'New Person','channel'=>'email','email'=>'guest@example.test','phone'=>'','country'=>'hr','question'=>'How do I start?','topic'=>'start','request_key'=>str_repeat('b',32),'content_version'=>1,'contact_requested'=>'1','trustedSource'=>$spoof,'source'=>$spoof];
 $result=$source===null?fcc_business_interest(str_repeat('a',64),$form,$preview):fcc_business_interest(str_repeat('a',64),$form,$preview,$source);
 $queries=array_values(array_filter($GLOBALS['log'],fn($e)=>$e[0]==='query'));
 $jobs=array_values(array_filter($queries,fn($e)=>str_starts_with($e[1],'INSERT IGNORE INTO fcc_business_jobs')));
 $snapshot=array_values(array_filter($queries,fn($e)=>str_starts_with($e[1],'UPDATE fcc_business_leads SET topic=')));
 return ['result'=>$result,'jobs'=>$jobs,'kinds'=>array_column(array_column($jobs,2),1),'snapshot'=>json_decode($snapshot[0][2][2]??'{}',true),'queries'=>$queries];
}
$r=runSource();ck($r['kinds']===['guest','owner'],'Legacy default keeps guest and owner confirmation jobs');ck(!isset($r['snapshot']['source']),'Legacy snapshot has no new source marker');ck($r['result']['new']===true&&strlen($r['result']['manage_token'])===64,'Actual service saves and returns private management token');ck(array_reduce($r['jobs'],fn($a,$j)=>$a&&$j[3],true),'Legacy jobs are inserted inside owner transaction');
$r=runSource('sponsoring');ck($r['kinds']===['owner'],'Sponsoring queues owner only');ck(($r['snapshot']['source']??'')==='sponsoring','Sponsoring source marker saved in content snapshot');ck(array_reduce($r['jobs'],fn($a,$j)=>$a&&$j[3],true),'Sponsoring job selection occurs inside owner transaction');ck(count(array_filter($GLOBALS['log'],fn($e)=>$e[0]==='notify'))===1,'Sponsoring preserves owner workspace notification');ck(count(array_filter($GLOBALS['log'],fn($e)=>$e[0]==='reply'))===1,'Sponsoring preserves reply task synchronization');ck(count(array_filter($r['queries'],fn($e)=>str_starts_with($e[1],'INSERT INTO fcc_partner_contacts')))===1,'Sponsoring creates requested CRM contact');ck($GLOBALS['log'][count($GLOBALS['log'])-1][0]==='COMMIT','Sponsoring commits after queue and CRM records');
$r=runSource(null,'sponsoring');ck($r['kinds']===['guest','owner']&&!isset($r['snapshot']['source']),'Guest supplied source cannot turn legacy endpoint into sponsoring');
$r=runSource('sponsoring','legacy');ck($r['kinds']===['owner']&&($r['snapshot']['source']??'')==='sponsoring','Guest supplied legacy source cannot override server source');
$r=runSource('sponsoring','',true);ck($r['jobs']===[]&&!empty($r['result']['preview']),'Trusted preview never queues mail');ck(($r['snapshot']['source']??'')==='sponsoring','Trusted preview retains immutable source marker');ck(count(array_filter($GLOBALS['log'],fn($e)=>$e[0]==='notify'))===0,'Trusted preview does not notify owner');
$GLOBALS['log']=[];$rejected=false;try{runSource('other');}catch(InvalidArgumentException $e){$rejected=true;}ck($rejected&&$GLOBALS['log']===[],'Unsupported server source fails before DB reads or writes');
echo json_encode(['pass'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
}
