<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();

class WebinarInvite extends Controller {
 public function index() {
  \Altum\Router::$controller_settings['has_view']=false;
  header('Cache-Control: no-store, private');header('X-Robots-Tag: noindex, nofollow');
  header('Referrer-Policy: strict-origin');header('X-Content-Type-Options: nosniff');
  header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; media-src 'self'; connect-src 'self'; base-uri 'none'; frame-ancestors 'self'; form-action 'self'");
  $part=(string)($this->params[0]??'');$error=null;$saved=false;$a=null;$r=null;$success=false;$duplicate=false;
  if($part==='return'){fcc_wl_guest_page((string)($this->params[1]??''));return;}
  if($part==='demo-room') {
   if(!fcc_webinar_local())throw_404();
   $mode='room';require THEME_PATH.'views/partner/webinar-public.php';return;
  }
  if(in_array($part,['manage','verify'],true)) {
   $mode=$part;$secret=(string)($this->params[1]??'');$r=fcc_webinar_registration_for_token($secret,$mode==='verify');
   if(!$r)throw_404();
   $scope=$mode.':'.hash('sha256',$secret);
   if($mode==='manage'&&($_GET['download']??'')==='data') {
    header('Content-Type: application/json; charset=UTF-8');header('Content-Disposition: attachment; filename="moja-webinar-prijava.json"');
    echo json_encode(fcc_webinar_export($r),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);return;
   }
   if($mode==='manage'&&($_GET['download']??'')==='calendar') {
    header('Content-Type: text/calendar; charset=UTF-8');header('Content-Disposition: attachment; filename="fcc-webinar.ics"');
    echo fcc_webinar_calendar($r);return;
   }
  } else {
   $mode='invite';$secret=$part;$a=fcc_webinar_public_attempt($secret);
   if(!$a)throw_404();
   $scope='invite:'.hash('sha256',$secret);
  }
  fcc_mw_public_locale($a??$r??[]);
  if(!isset($_SESSION['webinar_nonce']))$_SESSION['webinar_nonce']=[];
  // A bounded per-session set avoids unbounded public URL scans filling the session.
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
    if(in_array($mode,['manage','verify'],true)) {
     if(($mode==='verify')!==((string)($_POST['action']??'')==='verify'))throw new \InvalidArgumentException(fcc_t('Radnja nije dostupna na ovoj poveznici.'));
     if(($_POST['action']??'')==='erase'&&empty($_POST['confirm_erase']))throw new \InvalidArgumentException(fcc_t('Potvrdi brisanje podataka ove prijave.'));
     if($mode==='manage'&&($_POST['action']??'')==='next'){ $invite=fcc_wl_return_prepare((int)$r['user_id'],(int)$r['id'],null,true);header('Location: '.fcc_wl_return_url($invite),true,303);return;}
     if($mode==='manage'&&($_POST['action']??'')==='reactivate'){fcc_wl_reactivate($r,$_POST);}else fcc_webinar_manage($secret,(string)($_POST['action']??''));$saved=true;$r=fcc_webinar_registration_for_token($secret,$mode==='verify');
     if(!$r){$mode='erased';}
    } else {
     fcc_webinar_rate_limit('email:'.mb_strtolower(fcc_partner_text($_POST['email']??'',320)),8,3600);
     $result=fcc_webinar_register($secret,$_POST);$success=true;$duplicate=!$result['new'];
     if($result['new']) {
      $r=fcc_webinar_registration_for_token($result['manage_token']);
      fcc_webinar_dispatch_registration($result,(int)$a['user_id']);
     }
    }
   } catch(\InvalidArgumentException $e) {$error=$e->getMessage();http_response_code(422);}
   catch(\Throwable $e) {error_log('Webinar public form: '.get_class($e));$error=fcc_t('Prijava trenutačno nije uspjela. Pokušaj ponovno.');http_response_code(503);}
  } elseif($_SERVER['REQUEST_METHOD']!=='GET') {http_response_code(405);header('Allow: GET, POST');return;}
  require THEME_PATH.'views/partner/webinar-public.php';
 }
}
