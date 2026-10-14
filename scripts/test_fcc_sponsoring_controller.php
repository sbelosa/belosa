<?php
/* Custom code: FC-2026-10-14: exercise the public sponsoring controller without network or database. */
namespace Altum { class Router {public static array $controller_settings=[];public static string $language_code='hr';} class Language {public static string $code=''; public static function set_by_code($x): void {self::$code=$x;}} }
namespace Altum\Controllers { class Controller {public array $params=[];public object $user;} }
namespace {
if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
$fixtureDir=sys_get_temp_dir().'/fcc-sponsoring-controller-'.bin2hex(random_bytes(8)).'/';
mkdir($fixtureDir.'views/partner',0700,true);
file_put_contents($fixtureDir.'views/partner/sponsoring-public.php','<?php $GLOBALS["last_sp"]=$sp; echo "rendered";');
register_shutdown_function(static function()use($fixtureDir){unlink($fixtureDir.'views/partner/sponsoring-public.php');rmdir($fixtureDir.'views/partner');rmdir($fixtureDir.'views');rmdir($fixtureDir);});
define('ALTUMCODE',true);define('SITE_URL','https://forevercard.club/');define('APP_PATH',dirname(__DIR__).'/app/');define('THEME_PATH',$fixtureDir);
class Route404 extends Exception {}
function throw_404(){throw new Route404();}
function fcc_sponsoring_share($code){$GLOBALS['calls'][]=['share',$code];return $code==='abcdefgh'?$GLOBALS['fixture']:null;}
function fcc_sponsoring_locale($x){return in_array($x,['en','hr','sl','de','es'],true)?$x:'en';}
function fcc_sponsoring_model($s,$code,$lang,$country){$GLOBALS['calls'][]=['model',$country]; if(!empty($GLOBALS['model_null']))return null;return ['sponsor'=>['avatar'=>''],'contact'=>[],'country'=>$country,'error'=>null,'result'=>null];}
function fcc_business_landing_context($uid){return ['viewer_uid'=>$uid];}
function fcc_business_landing_test_reason($s,$ctx){return $GLOBALS['test_reason']??'';}
function fcc_sponsoring_copy($key,$lang){return $key;}
function fcc_business_rate(...$args){$GLOBALS['calls'][]=['rate',...$args];if(!empty($GLOBALS['rate_block']))throw new InvalidArgumentException('blocked');}
function fcc_business_interest(...$args){$GLOBALS['calls'][]=['interest',...$args]; return ['new'=>true,'lead_id'=>78,'manage_token'=>str_repeat('b',64)];}
function fcc_business_landing_lead(...$args){$GLOBALS['calls'][]=['lead',...$args];}
function fcc_business_starter(...$args){$GLOBALS['calls'][]=['starter',...$args];return ['destination'=>'https://foreverliving.com/shop/hrv/hr-hr?fboId=123456789012','version'=>'version-current'];}
function fcc_business_landing_observe(...$args){$GLOBALS['calls'][]=['observe',...$args];}
require APP_PATH.'controllers/Sponsoring.php';
$GLOBALS['fixture']=['id'=>51,'public_token'=>str_repeat('a',64),'language'=>'hr','user_id'=>9];
$checks=[];
function check($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label.' '.json_encode($GLOBALS['r']??[]));$GLOBALS['checks'][]=$label;}
function calls($kind){return array_values(array_filter($GLOBALS['calls'],fn($c)=>$c[0]===$kind));}
function runCase($params,$method='GET',$post=[],$server=[],$session=null){
 $GLOBALS['calls']=[];$GLOBALS['last_sp']=null; \Altum\Router::$controller_settings=[];
 $_GET=['lang'=>'hr'];$_POST=$post;$_SERVER=['REQUEST_METHOD'=>$method,'CONTENT_LENGTH'=>100,'REMOTE_ADDR'=>'192.0.2.1']+$server;
 foreach($server as $k=>$v)$_SERVER[$k]=$v;
 $_SESSION=$session??['fcc_sponsoring_nonces'=>[hash('sha256','abcdefgh:51')=>['value'=>'nonce-current','at'=>time()]]];
 $c=new \Altum\Controllers\Sponsoring();$c->params=$params;$c->user=(object)['user_id'=>0];http_response_code(200);ob_start();$error='';try{$c->index();}catch(Route404 $e){$error='404';}catch(Throwable $e){$error=get_class($e).':'.$e->getMessage();}$output=ob_get_clean();return ['status'=>http_response_code(),'error'=>$error,'output'=>$output,'sp'=>$GLOBALS['last_sp']];
}
$r=runCase(['abcdefgh','unknown']);check($r['error']==='404'&&!calls('share'),'Unknown action rejected before projection');
$r=runCase(['abcdefgh','','extra']);check($r['error']==='404'&&!calls('share'),'Extra route segment rejected');
foreach([['','HEAD'],['','POST'],['pdf','POST'],['interest','GET'],['starter','GET'],['action','GET'],['interest','PUT']] as [$action,$method]){$r=runCase(['abcdefgh',$action],$method);check($r['status']===405&&!calls('share'),"Method boundary $method $action");}
$r=runCase(['bad']);check($r['error']==='404','Invalid invite rejected');
$GLOBALS['model_null']=true;$r=runCase(['abcdefgh']);check($r['error']==='404','Unavailable public profile rejected');unset($GLOBALS['model_null']);
$r=runCase(['abcdefgh']);check($r['status']===200&&$r['output']==='rendered'&&count(calls('observe'))===1,'GET observes and renders public projection');
$r=runCase(['abcdefgh','interest'],'POST',[]);check($r['status']===422&&!calls('interest'),'Missing nonce rejected');
$old=['fcc_sponsoring_nonces'=>[hash('sha256','abcdefgh:51')=>['value'=>'nonce-current','at'=>time()-7201]]];$r=runCase(['abcdefgh','interest'],'POST',['nonce'=>'nonce-current'],[],$old);check($r['status']===422&&!calls('interest'),'Expired nonce rejected');
$other=['fcc_sponsoring_nonces'=>[hash('sha256','ijklmnop:51')=>['value'=>'nonce-current','at'=>time()]]];$r=runCase(['abcdefgh','interest'],'POST',['nonce'=>'nonce-current'],[],$other);check($r['status']===422&&!calls('interest'),'Nonce from different invite rejected');
$r=runCase(['abcdefgh','interest'],'POST',['nonce'=>'nonce-current'],['CONTENT_LENGTH'=>12001]);check($r['status']===422&&!calls('interest'),'Oversized form rejected');
foreach(['https://evil.example','http://forevercard.club','https://forevercard.club:444','null','https://forevercard.club.evil.example'] as $origin){$r=runCase(['abcdefgh','interest'],'POST',['nonce'=>'nonce-current'],['HTTP_ORIGIN'=>$origin]);check($r['status']===422&&!calls('interest'),'Cross origin rejected '.$origin);}
$post=['nonce'=>'nonce-current','country'=>'hr','trustedSource'=>'legacy','source'=>'legacy','preview'=>'1'];
$r=runCase(['abcdefgh','interest'],'POST',$post,['HTTP_ORIGIN'=>'https://forevercard.club']);$interest=calls('interest');check($r['status']===200&&count($interest)===1,'Same origin valid nonce reaches service');check($interest[0][1]===str_repeat('a',64)&&$interest[0][3]===false&&$interest[0][4]==='sponsoring','Service receives server share token and immutable source, untrusted preview ignored');check(count(calls('lead'))===1,'Saved lead gets immutable landing annotation');check(($r['sp']['result']['manage_url']??'')===SITE_URL.'business-request/'.str_repeat('b',64),'Receipt uses existing private request management route');
$GLOBALS['test_reason']='owner';$r=runCase(['abcdefgh','interest'],'POST',['nonce'=>'nonce-current']);check(calls('interest')[0][3]===true,'Owner preview determined by server context');unset($GLOBALS['test_reason']);
$r=runCase(['abcdefgh','starter'],'POST',['nonce'=>'nonce-current','country'=>'hr','starter_version'=>'old']);check($r['status']===422&&!calls('observe'),'Stale starter version rejected before outbound tracking');
$r=runCase(['abcdefgh','starter'],'POST',['nonce'=>'nonce-current','country'=>'hr','starter_version'=>'version-current','destination'=>'https://evil.example']);$obs=calls('observe');check($r['error']===''&&$r['output']===''&&count($obs)===1&&$obs[0][2]==='outbound','Valid starter returns before public renderer');check(($obs[0][4]['destination']??'')==='https://foreverliving.com/shop/hrv/hr-hr?fboId=123456789012','Starter destination comes from current server resolver');
$GLOBALS['rate_block']=true;$r=runCase(['abcdefgh','pdf']);check($r['status']===429&&$r['output']==='pdf_limited','PDF generation is stopped by the IP limiter');check(calls('rate')[0][1]==='sponsoring:pdf:ip:192.0.2.1'&&calls('rate')[0][2]===30,'PDF limiter uses server remote address and hourly limit');unset($GLOBALS['rate_block']);
$r=runCase(['abcdefgh','action'],'POST',['nonce'=>'nonce-current','kind'=>'other']);check($r['status']===422&&!calls('observe'),'Unknown tracked action rejected');
$r=runCase(['abcdefgh','action'],'POST',['nonce'=>'nonce-current','kind'=>'whatsapp']);check($r['output']==='{"ok":true}'&&calls('observe')[0][2]==='whatsapp','WhatsApp action returns JSON and tracks fixed kind');
echo json_encode(['pass'=>count($GLOBALS['checks']),'checks'=>$GLOBALS['checks']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
}
