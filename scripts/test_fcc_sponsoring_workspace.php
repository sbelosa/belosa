<?php
/* Custom code: FC-2026-10-14: actual authenticated sharing controller boundaries. */
namespace Altum {
 class Router {public static array $controller_settings=['has_view'=>true];}
 class Authentication {public static bool $logged=true;public static function guard(): void {if(!self::$logged)throw new \RuntimeException('login_redirect');}public static function is_admin(): bool {return false;}}
 class Language {public static string $code='hr';}
 class Title {public static function set($s): void {$GLOBALS['title']=$s;}}
 class View {public function __construct($path,$vars){$GLOBALS['view_path']=$path;}public function run($vars): string {$GLOBALS['view_data']=$vars;return 'workspace_fixture';}}
}
namespace Altum\Controllers {
 class Controller {public array $params=['share'];public object $user;public function __construct(){$this->user=(object)['user_id'=>37];}public function add_view_content($key,$content):void{$GLOBALS['rendered'][$key]=$content;}}
 function header(string $value,bool $replace=true,int $code=0):void {$key=strtolower(explode(':',$value,2)[0]);$GLOBALS['headers'][$key]=$value;if($code)$GLOBALS['status']=$code;}
 function http_response_code(?int $code=null):int {if($code!==null)$GLOBALS['status']=$code;return $GLOBALS['status'];}
}
namespace {
 if(PHP_SAPI!=='cli') { http_response_code(404); exit; }
 define('ALTUMCODE',true);define('SITE_URL','https://forevercard.club/');
 function fcc_partner_enabled(){return true;}
 function fcc_t($s){return $s;}
 function url($s){return SITE_URL.$s;}
 function throw_404():void {throw new RuntimeException('404');}
 function fcc_sponsoring_locale($s=null):string {return is_string($s)&&in_array(strtolower($s),['en','hr','sl','de','es'],true)?strtolower($s):'en';}
 function fcc_sponsoring_copy($key,$locale){return $locale.':'.$key;}
 function fcc_business_landing_access($uid){$GLOBALS['calls'][]=['access',$uid];return !empty($GLOBALS['scenario']['access']);}
 function fcc_partner_one($sql,$params=[]){$GLOBALS['calls'][]=['identity',$params];if($sql!=='SELECT preferences FROM users WHERE user_id=?'||$params!==[37])throw new RuntimeException('unexpected_database_access');return ['preferences'=>json_encode(['meta'=>['foreverId'=>$GLOBALS['scenario']['fbo']]])];}
 function fcc_forever_id($prefs){$a=json_decode($prefs,true);$id=trim((string)($a['meta']['foreverId']??''));return preg_match('/^[0-9]{12}$/D',$id)?$id:'';}
 function fcc_invite_for_user($uid){$GLOBALS['calls'][]=['invite',$uid];if(!empty($GLOBALS['scenario']['invite_error']))throw new InvalidArgumentException($GLOBALS['scenario']['invite_error']);return ['code'=>'abcdefgh'];}
 function fcc_sponsoring_share($code){$GLOBALS['calls'][]=['share',$code];return !empty($GLOBALS['scenario']['share'])?['id'=>51,'user_id'=>37]:null;}
 function fcc_sponsoring_model($share,$code,$locale){$GLOBALS['calls'][]=['model',$share['user_id'],$code,$locale];return !empty($GLOBALS['scenario']['model'])?['url'=>SITE_URL.'suradnja/'.$code.'?lang='.$locale,'pdf_url'=>SITE_URL.'suradnja/'.$code.'/pdf?lang='.$locale,'sponsor'=>['name'=>'Owner Public','fbo'=>'123456789012']]:null;}
 function fcc_rollout_enter($uid):void {throw new RuntimeException('unexpected_workspace_write');}
 function fcc_team_refresh_notifications($uid):void {throw new RuntimeException('unexpected_notification_job');}
 function fcc_partner_query(...$args):never {throw new RuntimeException('unexpected_database_write');}
 function fcc_los_event(...$args):never {throw new RuntimeException('unexpected_audit_write');}
 function db():never {throw new RuntimeException('unexpected_database_access');}
 $source=dirname(__DIR__);require $source.'/app/controllers/Partner.php';
 $checks=[];function ck($ok,$label){if(!$ok)throw new RuntimeException('FAIL '.$label.' '.json_encode($GLOBALS['current']??[]));$GLOBALS['checks'][]=$label;}
 function hits($kind){return array_values(array_filter($GLOBALS['calls'],fn($x)=>$x[0]===$kind));}
 function invoke($scenario=[],$get=[],$method='GET',$logged=true){
  $GLOBALS['scenario']=$scenario+['access'=>true,'fbo'=>'123456789012','share'=>true,'model'=>true];$GLOBALS['calls']=[];$GLOBALS['headers']=[];$GLOBALS['status']=200;$GLOBALS['rendered']=[];$GLOBALS['view_data']=null;$GLOBALS['view_path']=null;
  \Altum\Authentication::$logged=$logged;\Altum\Router::$controller_settings=['has_view'=>true];$_GET=$get+['type'=>'sponsoring'];$_POST=['action'=>'notification_rules','token'=>'malicious_fixture'];$_SERVER['REQUEST_METHOD']=$method;$error=null;ob_start();try{(new \Altum\Controllers\Partner())->index();}catch(Throwable $e){$error=get_class($e).':'.$e->getMessage();}$output=ob_get_clean();
  return $GLOBALS['current']=['error'=>$error,'output'=>$output,'status'=>$GLOBALS['status'],'data'=>$GLOBALS['view_data'],'headers'=>$GLOBALS['headers'],'view'=>$GLOBALS['view_path'],'has_view'=>\Altum\Router::$controller_settings['has_view']];
 }
 $r=invoke([],[],'GET',false);ck($r['error']==='RuntimeException:login_redirect'&&$GLOBALS['calls']===[],'Anonymous workspace guarded before access or identity reads');
 foreach(['POST','PUT','DELETE','HEAD','OPTIONS'] as $method){$r=invoke([],[],$method);ck($r['error']===null&&$r['status']===405&&!$r['has_view']&&$GLOBALS['calls']===[],"$method rejected before invite preparation or jobs");ck(($r['headers']['allow']??'')==='Allow: GET',"$method publishes GET only");}
 $r=invoke(['fbo'=>'']);ck($r['error']===null&&$r['data']['error']==='hr:forever_id_required','Missing Forever ID gives exact member recovery reason');ck($r['data']['recovery_url']===SITE_URL.'account'&&$r['data']['recovery_label']==='hr:account_settings','Missing Forever ID links to account settings');ck(hits('invite')===[]&&hits('share')===[]&&hits('model')===[],'Missing Forever ID does not create an invite');
 $r=invoke(['fbo'=>'123']);ck($r['data']['error']==='hr:forever_id_required'&&hits('invite')===[],'Invalid Forever ID does not prepare an invite');
 $r=invoke(['access'=>false]);ck($r['data']['error']==='hr:workspace_unavailable','Unavailable business preserves availability reason');ck(hits('identity')===[]&&hits('invite')===[],'Unavailable business stops before identity and preparation');
 $r=invoke(['model'=>false]);ck($r['data']['error']==='hr:profile_required'&&$r['data']['recovery_url']===SITE_URL.'partner/cards','Unavailable public card model links to card recovery');
 $r=invoke(['share'=>false]);ck($r['data']['error']==='hr:profile_required'&&hits('model')===[],'Missing public share does not build a model');
 $r=invoke(['invite_error'=>'Card not active']);ck($r['data']['error']==='Card not active','Invite recovery preserves precise service reason');
 $r=invoke([],['lang'=>'de','user_id'=>'999','owner_uid'=>'999','code'=>'zzzzzzzz']);ck($r['error']===null&&$r['status']===200&&$r['view']==='partner/sponsoring-workspace','Valid member receives sponsoring workspace');ck(hits('access')===[['access',37]]&&hits('invite')===[['invite',37]]&&hits('model')===[['model',37,'abcdefgh','de']],'Authenticated owner and prepared code override submitted user IDs and code');ck($r['data']['sponsoring']['url']===SITE_URL.'suradnja/abcdefgh?lang=de'&&$r['data']['sponsoring']['pdf_url']===SITE_URL.'suradnja/abcdefgh/pdf?lang=de','Workspace carries owner page and PDF in selected language');ck(($r['headers']['cache-control']??'')==='Cache-Control: no-store, private','Workspace response remains private and uncached');ck(($GLOBALS['rendered']['content']??'')==='workspace_fixture','Workspace attaches only its own rendered view');
 $r=invoke([],['lang'=>'unsupported']);ck($r['data']['sponsoring_locale']==='en'&&hits('model')[0][3]==='en','Unsupported selected language falls back to English');
 $r=invoke();ck($r['data']['sponsoring_locale']==='hr','Absent selection uses current interface language');
 echo json_encode(['status'=>'PASS','checks'=>count($checks),'details'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
}
