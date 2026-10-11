<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();
class BusinessPresentation extends Controller {
 public function index() {
  if(!fcc_business_enabled())throw_404();\Altum\Router::$controller_settings['has_view']=false;
  header('Cache-Control: no-store, private');header('X-Robots-Tag: noindex, nofollow');header('Referrer-Policy: strict-origin');header('X-Content-Type-Options: nosniff');
  header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; frame-src https://www.youtube-nocookie.com https://player.vimeo.com; connect-src 'self'; base-uri 'none'; frame-ancestors 'self'; form-action 'self' https://foreverliving.com https://www.foreverliving.com https://www.flpshop.ba https://www.foreveralbania.com");
  $secret=(string)($this->params[0]??'');$action=(string)($this->params[1]??'');$private=\Altum\Router::$controller_key==='business-request';if($private)header('Referrer-Policy: no-referrer');
  $s=$private?null:fcc_business_public($secret);$r=$private?fcc_business_private($secret):null;if($private?!$r:!$s)throw_404();$guestLanguage=in_array(\Altum\Router::$language_code??'', ['hr','sl','en','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'],true)?\Altum\Router::$language_code:($s['language']??$r['language']??'hr');\Altum\Language::set_by_code($guestLanguage);
  $viewer=(int)($this->user->user_id??0);$landing=$s&&fcc_business_landing_is_new($s);if($landing&&!fcc_business_landing_public_allowed($s,$viewer))throw_404();
  $landingContext=$landing?fcc_business_landing_context($viewer):[];$landingPreview=$landing&&fcc_business_landing_test_reason($s,$landingContext)!=='';
  $scope=($private?'private:':'public:').hash('sha256',$secret);$nonces=$_SESSION['fcc_business_nonces']??[];
  if(count($nonces)>20)$nonces=array_slice($nonces,-10,null,true);
  $nonce=$nonces[$scope]??null;if(!$nonce||$nonce['at']<time()-7200)$nonce=['value'=>bin2hex(random_bytes(24)),'at'=>time()];$nonces[$scope]=$nonce;$_SESSION['fcc_business_nonces']=$nonces;
  $error=null;$result=null;$saved=false;$erased=false;$country=is_string($_POST['country']??$_GET['country']??'')?($_POST['country']??$_GET['country']??''):'';
  if(!isset(fcc_business_countries()[$country]))$country='';if($landing&&$country===''&&isset(fcc_business_countries()[$s['landing_country']??'']))$country=$s['landing_country'];
  if($landing&&$_SERVER['REQUEST_METHOD']==='GET'&&$action==='')fcc_business_landing_observe($s,'view',$country,$landingContext);
  if($private&&($_GET['download']??'')==='data'){header('Content-Type: application/json; charset=UTF-8');header('Content-Disposition: attachment; filename="moj-poslovni-upit.json"');echo fcc_business_json(array_intersect_key($r,array_flip(['name','email','phone','channel','country','question','status','received_at','consent_version'])));return;}
  if($_SERVER['REQUEST_METHOD']==='POST'){
   try{
    if((int)($_SERVER['CONTENT_LENGTH']??0)>12000||!is_string($_POST['nonce']??null)||!hash_equals($nonce['value'],$_POST['nonce'])||$nonce['at']<time()-7200)throw new \InvalidArgumentException(fcc_t('Obrazac je istekao. Osvježi stranicu.'));
    $origin=parse_url($_SERVER['HTTP_ORIGIN']??'',PHP_URL_HOST);if($origin&&$origin!==parse_url(SITE_URL,PHP_URL_HOST)&&$origin!==parse_url('http://'.($_SERVER['HTTP_HOST']??''),PHP_URL_HOST))throw new \InvalidArgumentException(fcc_t('Osvježi stranicu.'));
    fcc_business_rate('ip:'.($_SERVER['REMOTE_ADDR']??''),100);
    if($private){$op=(string)($_POST['action']??'');if(!in_array($op,['withdraw','erase'],true))throw new \InvalidArgumentException(fcc_t('Radnja nije dostupna.'));if($op==='erase'&&empty($_POST['confirm_erase']))throw new \InvalidArgumentException(fcc_t('Potvrdi uklanjanje podataka upita.'));fcc_business_withdraw((int)$r['id'],$op==='erase');$saved=true;$erased=$op==='erase';$r=fcc_partner_one('SELECT * FROM fcc_business_leads WHERE id=?',[(int)$r['id']]);}
    elseif(!$s['available'])throw new \InvalidArgumentException(fcc_t('Prezentacija više nije dostupna.'));
    elseif($action==='interest'){$result=fcc_business_interest($secret,$_POST,$landingPreview);if($landing)fcc_business_landing_lead($s,(int)$result['lead_id'],$landingContext);if($result['new']&&!$landingPreview){$_SESSION['fcc_business_receipts'][$scope]=['id'=>(int)$result['lead_id'],'until'=>time()+1800];try{fcc_business_run_jobs((int)$result['lead_id'],2);}catch(\Throwable $e){error_log('Business confirmation queued.');}}}
    elseif($action==='webinar'){
     if($landing)throw new \InvalidArgumentException(fcc_t('Odaberi početak u Foreveru ili izravni razgovor.'));
     $receipt=$_SESSION['fcc_business_receipts'][$scope]??[];$rid=($receipt['until']??0)>time()?(int)$receipt['id']:null;
     $destination=fcc_business_webinar_handoff($s,(int)($_POST['session_id']??0),$rid);header('Location: '.$destination,true,303);return;
    }
    elseif($action==='starter'){
     $fresh=fcc_business_public($secret);$starter=$fresh?fcc_business_starter($fresh,$country):null;
     if(empty($starter['destination'])||!is_string($_POST['starter_version']??null)||!hash_equals($starter['version'],$_POST['starter_version']))throw new \InvalidArgumentException(fcc_t('Ponuda je izmijenjena ili nije dostupna. Ponovno odaberi zemlju ili zatraži pomoć.'));
     if(!$landing)fcc_partner_query('UPDATE fcc_business_shares SET destination_clicks=destination_clicks+1 WHERE id=?',[(int)$s['id']]);if($landing)fcc_business_landing_observe($s,'outbound',$country,array_replace($landingContext,['destination'=>$starter['destination']]));header('Location: '.$starter['destination'],true,303);return;
    }
    elseif($action==='continue'){
     $offer=fcc_partner_one('SELECT * FROM fcc_business_market_offers WHERE id=? AND country=?',[(int)($_POST['offer_id']??0),$country]);$fresh=fcc_business_public($secret);$dest=$offer&&$fresh?fcc_business_destination($fresh,$offer):null;
     if(!$dest||(int)($offer['version']??0)!==(int)($_POST['offer_version']??0))throw new \InvalidArgumentException(fcc_t('Ponuda je izmijenjena ili nije dostupna. Ponovno odaberi zemlju ili zatraži pomoć.'));
     if(!$landing)fcc_partner_query('UPDATE fcc_business_shares SET destination_clicks=destination_clicks+1 WHERE id=?',[(int)$s['id']]);if($landing)fcc_business_landing_observe($s,'outbound',$country,array_replace($landingContext,['destination'=>$dest]));header('Location: '.$dest,true,303);return;
    }elseif($action==='action'){
     if(($_POST['kind']??'')!=='whatsapp')throw new \InvalidArgumentException(fcc_t('Nevaljana radnja.'));$last=$_SESSION['fcc_business_click'][$scope]??0;
     if(!$landing&&$last<time()-20){fcc_partner_query('UPDATE fcc_business_shares SET guest_whatsapp_clicks=guest_whatsapp_clicks+1 WHERE id=?',[(int)$s['id']]);$_SESSION['fcc_business_click'][$scope]=time();}
     if($landing)fcc_business_landing_observe($s,'whatsapp',$country,$landingContext);header('Content-Type: application/json');echo '{"ok":true}';return;
    }else throw new \InvalidArgumentException(fcc_t('Radnja nije dostupna.'));
   }catch(\InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}
   catch(\Throwable $e){error_log('Business public: '.get_class($e).': '.$e->getMessage());$error=fcc_t('Upit nije moguće spremiti. Pokušaj ponovno.');http_response_code(500);}
  }
  $starter=$s&&$s['available']&&$country?fcc_business_starter($s,$country):null;
  $offers=$s&&$s['available']&&$country?fcc_business_offers($s,$country):[];$values=$_POST;$webinars=$s&&$s['available']?fcc_business_webinars($s):[];
  require THEME_PATH.'views/partner/'.($landing?'business-public.php':'business-public-legacy.php');
 }
}
