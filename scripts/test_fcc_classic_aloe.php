<?php
/* Custom code: FC-2026-10-12: regresija stvarnih klasičnih editor metoda i Aloe reverta.
 * Izvršavanje: php classic_editor_aloe_test.php SOURCE_ROOT.
 * Nema aplikacijskog bootstrapa, stvarne baze, datoteka korisnika ni mreže. */
namespace Altum { class Teams {static function is_delegated(){return false;} static function has_access($s){return true;}} class Response {static function json(...$a){throw new \FccTestResponse($a[1]??'success');}} }
namespace Altum\Controllers { class Controller {public $user;} }
namespace Altum\Models { class BiolinkBlock {function delete($id){\db()->where('biolink_block_id',$id)->delete('biolinks_blocks');}} }
namespace {
if(PHP_SAPI!=='cli')exit(1);
define('ALTUMCODE',66);
$root=realpath($argv[1]??dirname(__DIR__));if(!$root)throw new RuntimeException('SOURCE_ROOT missing');
define('APP_PATH',$root.'/app/');define('FCC_CARD_CORE_BLOCKS',['avatar']);
class FccTestResponse extends RuntimeException {}
class FccTestSql {
 function query($s){return true;} function begin_transaction(){$GLOBALS['begun_transactions']++;return true;} function commit(){return true;} function rollback(){return true;}
}
class FccTestCache {function deleteItem($s){} function deleteItems($s){} function deleteItemsByTag($s){} }
class FccTestBuilder {
 public $conditions=[];
 function where($column,$value){$this->conditions[$column]=$value;return $this;}
 function matches($row){foreach($this->conditions as $k=>$v)if(($row[$k]??null)!=$v)return false;return true;}
 function getOne($table,$columns=null){foreach($GLOBALS['f'][$table] as $row)if($this->matches($row))return (object)($columns?array_intersect_key($row,array_flip($columns)):$row);return null;}
 function update($table,$changes){
  if($table==='links'&&!empty($GLOBALS['fail_link_update']))return false;
  foreach($GLOBALS['f'][$table] as &$row)if($this->matches($row))$row=array_replace($row,$changes);unset($row);return true;
 }
 function delete($table){foreach($GLOBALS['f'][$table] as $id=>$row)if($this->matches($row))unset($GLOBALS['f'][$table][$id]);return true;}
}
function db(){return new FccTestBuilder;}
function database(){static $d;return $d??=new FccTestSql;}
function cache(){static $d;return $d??=new FccTestCache;}
function get_date(){return '2026-10-12 16:01:00';}
function l($s){return $s;}function url($s){return $s;}
function fcc_t($s,...$a){return $s;}
function fcc_card_settings($s){return json_decode($s)??(object)[];}
function fcc_card_clear_cache($s){}
function fcc_partner_notify(...$a){}
function fcc_partner_one($sql,$args=[]){
 if(str_contains($sql,'SELECT type,status FROM users'))return ['type'=>1,'status'=>1];
 if(str_contains($sql,'information_schema.TABLES'))return ['TABLE_NAME'=>'fcc_content_migration_log'];
 if(str_contains($sql,'FROM links WHERE link_id=? FOR UPDATE'))return $GLOBALS['f']['links'][$args[0]]??null;
 throw new RuntimeException('Unexpected helper SELECT');
}
function fcc_partner_rows($sql,$args=[]){
 if(str_contains($sql,'SELECT l.link_id'))return array_values(array_filter($GLOBALS['f']['links'],fn($r)=>$r['user_id']===101&&$r['last_datetime']===null));
 if(str_contains($sql,'SELECT * FROM fcc_content_migration_log'))return array_reverse(array_values(array_filter($GLOBALS['f']['logs'],fn($r)=>empty($r['reverted_at']))));
 if(str_contains($sql,'SELECT biolink_block_id,link_id'))return array_values(array_filter($GLOBALS['f']['biolinks_blocks'],fn($r)=>$r['link_id']===($args[0]??12)));
 if(str_contains($sql,'SELECT link_id,user_id,url,domain_id FROM links'))return [$GLOBALS['f']['links'][12]];
 throw new RuntimeException('Unexpected helper rows SELECT');
}
function fcc_partner_query($sql,$args=[]){
 if(str_starts_with($sql,'INSERT INTO fcc_content_migration_log'))$GLOBALS['f']['logs'][]=['id'=>count($GLOBALS['f']['logs'])+1,'migration_key'=>$args[0],'table_name'=>$args[1],'row_id'=>$args[2],'column_name'=>$args[3],'old_value'=>$args[4],'new_value'=>$args[5],'applied_by'=>$args[6],'applied_at'=>'2026-10-12 16:00:00','group_key'=>$args[7]??null,'reverted_at'=>null];
 elseif(preg_match('/UPDATE `([a-z_]+)` SET `([a-z_]+)`=\?/',$sql,$m))$GLOBALS['f'][$m[1]][$args[1]][$m[2]]=$args[0];
 elseif(str_contains($sql,'UPDATE fcc_content_migration_log SET reverted_by='))foreach($GLOBALS['f']['logs'] as &$r){if($r['id']===$args[1])$r['reverted_at']=get_date();}
 elseif(str_contains($sql,'UPDATE fcc_partner_notifications SET read_at=')){}
 else throw new RuntimeException('Unexpected helper write');
 return (object)['affected_rows'=>1];
}
require APP_PATH.'controllers/BiolinkBlockAjax.php';
require APP_PATH.'helpers/fcc_content_migration.php';
function fixture(){
 $GLOBALS['fail_link_update']=false;
 $GLOBALS['begun_transactions']=0;
 $GLOBALS['f']=['logs'=>[],
 'links'=>[
 12=>['link_id'=>12,'user_id'=>101,'url'=>'synthetic','name'=>'Fixture','domain_id'=>0,'settings'=>'{"fcc_card":"v1"}','last_datetime'=>null,'datetime'=>'2025-01-01 00:00:00','user_datetime'=>'2025-01-01 00:00:00','first_link_id'=>12],
 22=>['link_id'=>22,'user_id'=>202,'url'=>'foreign','name'=>'Fixture foreign','domain_id'=>0,'settings'=>'{"fcc_card":"v1"}','last_datetime'=>null],
 ],'biolinks_blocks'=>[
 20=>['biolink_block_id'=>20,'link_id'=>12,'user_id'=>101,'type'=>'avatar','settings'=>'{"plan_auto_disabled":true}','is_enabled'=>'0','datetime'=>'2025-01-01 00:00:00','last_datetime'=>null,'order'=>0],
 21=>['biolink_block_id'=>21,'link_id'=>12,'user_id'=>101,'type'=>'heading','settings'=>'{}','is_enabled'=>'1','datetime'=>'2025-01-01 00:00:00','last_datetime'=>null,'order'=>1],
 22=>['biolink_block_id'=>22,'link_id'=>12,'user_id'=>101,'type'=>'socials','settings'=>'{}','is_enabled'=>'1','datetime'=>'2025-01-01 00:00:00','last_datetime'=>null,'order'=>2],
 23=>['biolink_block_id'=>23,'link_id'=>12,'user_id'=>101,'type'=>'phone_collector','settings'=>'{}','is_enabled'=>'1','datetime'=>'2025-01-01 00:00:00','last_datetime'=>null,'order'=>5],
 24=>['biolink_block_id'=>24,'link_id'=>12,'user_id'=>101,'type'=>'vimeo','settings'=>'{}','is_enabled'=>'1','datetime'=>'2025-01-01 00:00:00','last_datetime'=>null,'order'=>4],
 25=>['biolink_block_id'=>25,'link_id'=>12,'user_id'=>101,'type'=>'link_forever_shop','settings'=>'{}','is_enabled'=>'1','datetime'=>'2025-01-01 00:00:00','last_datetime'=>null,'order'=>3],
 26=>['biolink_block_id'=>26,'link_id'=>12,'user_id'=>101,'type'=>'link_save_contact','settings'=>'{}','is_enabled'=>'1','datetime'=>'2025-01-01 00:00:00','last_datetime'=>null,'order'=>6],
 31=>['biolink_block_id'=>31,'link_id'=>22,'user_id'=>202,'type'=>'share','settings'=>'{}','is_enabled'=>'1','datetime'=>'2025-01-01 00:00:00','last_datetime'=>null,'order'=>1],
 ]];
}
function invoke($name,$post){
 $_POST=$post;$controller=new \Altum\Controllers\BiolinkBlockAjax;
 $controller->user=(object)['user_id'=>101];
 $method=new ReflectionMethod($controller,$name);$method->setAccessible(true);
 try{$method->invoke($controller);return 'returned';}catch(FccTestResponse $e){return $e->getMessage();}
}
$cases=[];
if(($argv[2]??'')==='foreign_delete'){
 fixture();$before=hash('sha256',json_encode($GLOBALS['f']));
 register_shutdown_function(function()use($before){echo json_encode(['case'=>'foreign_delete','pass'=>$before===hash('sha256',json_encode($GLOBALS['f']))]),"\n";});
 invoke('delete',['biolink_block_id'=>31]);exit;
}
foreach(['is_enabled_toggle','order','delete'] as $method){
 fixture();$foreign=hash('sha256',json_encode([$GLOBALS['f']['links'][22],$GLOBALS['f']['biolinks_blocks'][31]]));
 $apply=fcc_cm_apply(1,'aloe');
 $post=$method==='order'?['biolink_blocks'=>[['biolink_block_id'=>21,'order'=>9],['biolink_block_id'=>31,'order'=>8]]]:['biolink_block_id'=>21];
 $response=invoke($method,$post);$revert=fcc_cm_revert(1,'aloe');
 $kept=json_decode($GLOBALS['f']['links'][12]['settings'])->fcc_card==='v2';
 $marker=$method==='delete'?$GLOBALS['f']['links'][12]['last_datetime']!==null:$GLOBALS['f']['biolinks_blocks'][21]['last_datetime']!==null;
 $foreignSame=$foreign===hash('sha256',json_encode([$GLOBALS['f']['links'][22],$GLOBALS['f']['biolinks_blocks'][31]]));
 $cases[]=['case'=>$method,'pass'=>$apply===['applied'=>3,'skipped'=>0]&&$response==='success'&&$revert===['reverted'=>0,'kept'=>3]&&$kept&&$marker&&$foreignSame,'marker_recorded'=>$marker,'foreign_unchanged'=>$foreignSame,'revert'=>$revert];
}
foreach(['is_enabled_toggle','order'] as $method){
 fixture();$before=hash('sha256',json_encode($GLOBALS['f']));
 $post=$method==='order'?['biolink_blocks'=>[['biolink_block_id'=>31,'order'=>8]]]:['biolink_block_id'=>31];
 invoke($method,$post);$cases[]=['case'=>'foreign_'.$method,'pass'=>$before===hash('sha256',json_encode($GLOBALS['f']))];
}
fixture();$GLOBALS['fail_link_update']=true;$response=invoke('delete',['biolink_block_id'=>21]);
$cases[]=['case'=>'delete_marker_write_failure','pass'=>$response==='error'&&isset($GLOBALS['f']['biolinks_blocks'][21])];
$cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($root).' foreign_delete';exec($cmd,$output,$exit);
$foreign=json_decode(implode("\n",$output),true);$cases[]=['case'=>'foreign_delete','pass'=>$exit===0&&($foreign['pass']??false)];
fixture();$before=hash('sha256',json_encode($GLOBALS['f']));$guardCalled=false;$guardRejected=false;
try{fcc_cm_apply(1,'aloe',function(array $plan)use(&$guardCalled){$guardCalled=count($plan)===3;throw new RuntimeException('synthetic_internal_guard');});}catch(RuntimeException $error){$guardRejected=$error->getMessage()==='synthetic_internal_guard';}
$cases[]=['case'=>'internal_plan_guard_before_content_transaction','pass'=>$guardCalled&&$guardRejected&&$GLOBALS['begun_transactions']===0&&$before===hash('sha256',json_encode($GLOBALS['f']))];
echo json_encode(['isolated_no_database'=>true,'controller_sha256'=>hash_file('sha256',APP_PATH.'controllers/BiolinkBlockAjax.php'),'helper_sha256'=>hash_file('sha256',APP_PATH.'helpers/fcc_content_migration.php'),'cases'=>$cases],JSON_PRETTY_PRINT),"\n";
exit(count(array_filter($cases,fn($c)=>!$c['pass']))===0?0:1);
}
