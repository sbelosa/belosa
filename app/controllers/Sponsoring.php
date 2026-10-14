<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-14: independent personal sponsoring presentation and downloadable PDF. */
class Sponsoring extends Controller {
    public function index() {
        \Altum\Router::$controller_settings['has_view']=false;
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Robots-Tag: noindex, nofollow');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: strict-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; base-uri 'none'; frame-ancestors 'self'; form-action 'self'");
        $code=is_string($this->params[0]??null)?strtolower($this->params[0]):'';
        $action=is_string($this->params[1]??null)?$this->params[1]:'';
        if(!in_array($action,['','pdf','interest','starter','action'],true) || isset($this->params[2])) throw_404();
        $method=(string)($_SERVER['REQUEST_METHOD']??'GET');
        if(!in_array($method,['GET','POST'],true) || ($action==='pdf'&&$method!=='GET') || (in_array($action,['interest','starter','action'],true)&&$method!=='POST') || ($action===''&&$method!=='GET')) {http_response_code(405);header('Allow: '.(in_array($action,['interest','starter','action'],true)?'POST':'GET'));return;}
        $share=fcc_sponsoring_share($code);if(!$share)throw_404();
        $language=fcc_sponsoring_locale($_GET['lang']??\Altum\Router::$language_code??$share['language']??'en');
        \Altum\Language::set_by_code($language);
        $country=is_string($_POST['country']??$_GET['country']??null)?($_POST['country']??$_GET['country']):'';
        $sp=fcc_sponsoring_model($share,$code,$language,$country);if(!$sp)throw_404();
        if($action==='pdf') {
            try {
                fcc_business_rate('sponsoring:pdf:ip:'.($_SERVER['REMOTE_ADDR']??''),30);
                fcc_business_rate('sponsoring:pdf:code:'.$code,200);
            }catch(\InvalidArgumentException $e){http_response_code(429);header('Retry-After: 3600');header('Content-Type: text/plain; charset=UTF-8');echo fcc_sponsoring_copy('pdf_limited',$language);return;}
            catch(\Throwable $e){error_log('FCC sponsoring PDF limiter: '.get_class($e));http_response_code(503);header('Content-Type: text/plain; charset=UTF-8');echo fcc_sponsoring_copy('pdf_unavailable',$language);return;}
            try {
                require_once APP_PATH.'helpers/fcc_sponsoring_pdf.php';
                $sp['sponsor']['avatar_path']=fcc_sponsoring_avatar_path($sp['sponsor']['avatar']);
                $pdf=fcc_sponsoring_pdf($sp);
                header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="fcc-business-'.$code.'.pdf"');header('Content-Length: '.strlen($pdf));
                echo $pdf;
            }catch(\Throwable $e){error_log('FCC sponsoring PDF: '.get_class($e));http_response_code(503);header('Content-Type: text/plain; charset=UTF-8');echo fcc_sponsoring_copy('pdf_unavailable',$language);}
            return;
        }
        $scope=hash('sha256',$code.':'.(int)$share['id']);
        $nonces=$_SESSION['fcc_sponsoring_nonces']??[];
        if(!is_array($nonces))$nonces=[];
        if(count($nonces)>20)$nonces=array_slice($nonces,-10,null,true);
        $nonce=$nonces[$scope]??null;
        if(!is_array($nonce)||(int)($nonce['at']??0)<time()-7200)$nonce=['value'=>bin2hex(random_bytes(24)),'at'=>time()];
        $nonces[$scope]=$nonce;$_SESSION['fcc_sponsoring_nonces']=$nonces;
        $sp['contact']['nonce']=$nonce['value'];
        $sp['values']=array_filter($_POST,static fn($v)=>is_string($v));
        $context=fcc_business_landing_context((int)($this->user->user_id??0));
        if($method==='POST') {
            try {
                if((int)($_SERVER['CONTENT_LENGTH']??0)>12000 || !is_string($_POST['nonce']??null) || !hash_equals($nonce['value'],$_POST['nonce']) || $nonce['at']<time()-7200) throw new \InvalidArgumentException(fcc_sponsoring_copy('form_expired',$language));
                $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
                if($origin!=='' && (strtolower((string)parse_url($origin,PHP_URL_HOST))!==strtolower((string)parse_url(SITE_URL,PHP_URL_HOST)) || parse_url($origin,PHP_URL_SCHEME)!==parse_url(SITE_URL,PHP_URL_SCHEME) || (parse_url($origin,PHP_URL_PORT)??null)!==(parse_url(SITE_URL,PHP_URL_PORT)??null))) throw new \InvalidArgumentException(fcc_sponsoring_copy('form_expired',$language));
                fcc_business_rate('sponsoring:ip:'.($_SERVER['REMOTE_ADDR']??''),60);
                if($action==='interest') {
                    $preview=fcc_business_landing_test_reason($share,$context)!=='';
                    $sp['result']=fcc_business_interest((string)$share['public_token'],$_POST,$preview,'sponsoring');
                    if(!empty($sp['result']['manage_token'])) $sp['result']['manage_url']=SITE_URL.'business-request/'.rawurlencode($sp['result']['manage_token']);
                    fcc_business_landing_lead($share,(int)$sp['result']['lead_id'],$context);
                } elseif($action==='starter') {
                    $fresh=fcc_sponsoring_share($code);$starter=$fresh?fcc_business_starter($fresh,$sp['country']):null;
                    if(empty($starter['destination']) || !is_string($_POST['starter_version']??null) || !hash_equals($starter['version'],$_POST['starter_version'])) throw new \InvalidArgumentException(fcc_sponsoring_copy('offer_changed',$language));
                    fcc_business_landing_observe($share,'outbound',$sp['country'],array_replace($context,['destination'=>$starter['destination']]));
                    header('Location: '.$starter['destination'],true,303);return;
                } elseif($action==='action') {
                    if(($_POST['kind']??'')!=='whatsapp')throw new \InvalidArgumentException('Invalid action');
                    fcc_business_landing_observe($share,'whatsapp',$sp['country'],$context);header('Content-Type: application/json');echo '{"ok":true}';return;
                }
            }catch(\InvalidArgumentException $e){$sp['error']=$e->getMessage();http_response_code(422);}
            catch(\Throwable $e){error_log('FCC sponsoring interest: '.get_class($e));$sp['error']=fcc_sponsoring_copy('form_unavailable',$language);http_response_code(503);}
            if($action==='action'){header('Content-Type: application/json');echo json_encode(['ok'=>false,'message'=>$sp['error']],JSON_UNESCAPED_UNICODE);return;}
        } else fcc_business_landing_observe($share,'view',$sp['country'],$context);
        require THEME_PATH.'views/partner/sponsoring-public.php';
    }
}
/* /Custom code: FC-2026-10-14 */
