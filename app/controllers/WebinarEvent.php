<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();
class WebinarEvent extends Controller {
 public function index(){
  \Altum\Router::$controller_settings['has_view']=false;
  $sessionId=(int)($this->params[0]??0);$ref=fcc_partner_text($_GET['ref']??'',128);
  $a=fcc_webinar_standard_context($sessionId,$ref);if(!$a)throw_404();fcc_mw_public_locale($a);
  header('Cache-Control: no-store, private');header('X-Robots-Tag: noindex, nofollow');header('Referrer-Policy: strict-origin');header('X-Content-Type-Options: nosniff');
  header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'none'; form-action 'self'");
  $handoffKey=is_string($_GET['business_handoff']??null)?$_GET['business_handoff']:'';
  $businessHandoff=fcc_business_handoff_context($handoffKey,(int)$a['user_id'],$sessionId);
  $webinarFormValues=$_SERVER['REQUEST_METHOD']==='GET'&&$businessHandoff?array_intersect_key($businessHandoff,array_flip(['name','email','phone'])):$_POST;
  $mode='invite';$error=null;$saved=false;$r=null;$success=false;$duplicate=false;
  $scope='standard:'.$a['user_id'].':'.$a['card_id'].':'.$sessionId;
  if(!isset($_SESSION['webinar_nonce']))$_SESSION['webinar_nonce']=[];
  if(count($_SESSION['webinar_nonce'])>20)$_SESSION['webinar_nonce']=array_slice($_SESSION['webinar_nonce'],-10,null,true);
  $nonce=$_SESSION['webinar_nonce'][$scope]??null;
  if(!$nonce||$nonce['at']<time()-7200)$nonce=['value'=>bin2hex(random_bytes(24)),'at'=>time()];
  $_SESSION['webinar_nonce'][$scope]=$nonce;
  if($_SERVER['REQUEST_METHOD']==='POST') {
   try {
    if((int)($_SERVER['CONTENT_LENGTH']??0)>12000)throw new \InvalidArgumentException(fcc_t('Obrazac je prevelik.'));
    if(!is_string($_POST['nonce']??null)||!hash_equals($nonce['value'],$_POST['nonce'])||$nonce['at']<time()-7200)throw new \InvalidArgumentException(fcc_t('Obrazac je istekao. Osvježi stranicu.'));
    $origin=parse_url($_SERVER['HTTP_ORIGIN']??'',PHP_URL_HOST);
    if($origin&&$origin!==parse_url(SITE_URL,PHP_URL_HOST)&&$origin!==parse_url('http://'.($_SERVER['HTTP_HOST']??''),PHP_URL_HOST))throw new \InvalidArgumentException(fcc_t('Osvježi obrazac prije prijave.'));
    fcc_webinar_rate_limit('ip:'.($_SERVER['REMOTE_ADDR']??''),80,3600);
    fcc_webinar_rate_limit('email:'.mb_strtolower(fcc_partner_text($_POST['email']??'',320)),8,3600);
    fcc_webinar_registration_input($_POST);
    $attempt=fcc_webinar_standard_prepare($sessionId,$ref,(int)($_POST['session_revision']??0));
    $registrationInput=$_POST;unset($registrationInput['_business_handoff']);if($businessHandoff)$registrationInput['_business_handoff']=$handoffKey;
    $result=fcc_webinar_register(fcc_webinar_unseal($attempt['token_sealed']),$registrationInput);$success=true;$duplicate=!$result['new'];
    if($result['new']){
     $r=fcc_webinar_registration_for_token($result['manage_token']);
     fcc_webinar_dispatch_registration($result,(int)$a['user_id']);
    }
   }catch(\InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}
   catch(\Throwable $e){error_log('Webinar standard form: '.get_class($e));$error=fcc_t('Prijava trenutačno nije uspjela. Pokušaj ponovno.');http_response_code(503);}
  }elseif($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET, POST');return;}
  require THEME_PATH.'views/partner/webinar-public.php';
 }
}
