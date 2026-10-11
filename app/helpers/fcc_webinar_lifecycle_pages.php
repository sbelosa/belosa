<?php
defined('ALTUMCODE') || die();
function fcc_wl_member_page($controller,int $uid): void {
 if(!fcc_wl_ready())throw_404();
 $sid=(int)($_GET['session_id']??0);$cid=(int)($_GET['contact_id']??0);$error=null;$prepared=null;
 if($cid)fcc_partner_contact($uid,$cid);
 if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
   if(!is_string($_POST['token']??null)||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new InvalidArgumentException(fcc_wl_t('stale'));
   $action=(string)($_POST['lifecycle_action']??'');
   if($action==='outcome')fcc_wl_outcome($uid,(int)($_POST['registration_id']??0),(int)($_POST['version']??0),(string)($_POST['attendance']??''),!empty($_POST['contacted']));
   elseif($action==='snooze')fcc_wl_snooze($uid,$sid);
   elseif($action==='invite'){$prepared=fcc_wl_return_prepare($uid,(int)($_POST['registration_id']??0));}
   elseif($action==='sent'){
    $i=fcc_partner_one('SELECT * FROM fcc_webinar_return_invites WHERE id=? AND user_id=?',[(int)($_POST['return_id']??0),$uid]);if(!$i)throw new InvalidArgumentException(fcc_wl_t('unavailable'));
    require_once APP_PATH.'helpers/fcc_webinar_outreach.php';
    fcc_webinar_outreach_confirm($uid,['request_key'=>substr(hash('sha256','return:'.$i['id']),0,32),'session_id'=>$i['session_id'],'attempt_id'=>$i['attempt_id'],'contact_id'=>$i['contact_id'],'confirmed'=>'1','channel'=>'manual']);
   }else throw new InvalidArgumentException(fcc_wl_t('unavailable'));
   redirect('partner-webinar?view=guests'.($sid?'&session_id='.$sid:'').($cid?'&contact_id='.$cid:'').($prepared?'&return_id='.$prepared['id']:'').'&saved=1');
  }catch(InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}
 }
 if(isset($_GET['return_id'])){
  $prepared=fcc_partner_one('SELECT i.*,s.title,s.starts_at_utc,s.timezone,s.ends_at_utc,c.name,c.phone FROM fcc_webinar_return_invites i JOIN fcc_webinar_sessions s ON s.id=i.session_id JOIN fcc_partner_contacts c ON c.id=i.contact_id AND c.user_id=i.user_id WHERE i.id=? AND i.user_id=?',[(int)$_GET['return_id'],$uid]);
  if(!$prepared)throw_404();
  if(!fcc_wl_return_context(fcc_webinar_unseal($prepared['token_sealed'])))$prepared=null;
 }
 $page=max(0,min(10000,(int)($_GET['page']??0)));$rows=fcc_wl_rows($uid,$sid,$cid,51,$page*50);$more=count($rows)>50;$rows=array_slice($rows,0,50);$session=$sid&&$rows?fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[$sid]):null;
 $followup=$sid?fcc_partner_one('SELECT * FROM fcc_webinar_followups WHERE user_id=? AND session_id=?',[$uid,$sid]):null;
 \Altum\Title::set(fcc_wl_t('guests'));
 $view=new \Altum\View('partner/webinar-guests',(array)$controller);
 $controller->add_view_content('content',$view->run(['page'=>$page,'more'=>$more,'rows'=>$rows,'error'=>$error,'prepared'=>$prepared,'session'=>$session,'followup'=>$followup,'sid'=>$sid,'cid'=>$cid]));
}
function fcc_wl_guest_page(string $secret): void {
 $ctx=fcc_wl_return_context($secret);$error=null;$result=null;$r=null;
 header('Referrer-Policy: no-referrer');header('Cache-Control: no-store, private');header('X-Robots-Tag: noindex, nofollow');
 if(!$ctx){http_response_code(410);require THEME_PATH.'views/partner/webinar-return.php';return;}
 fcc_mw_public_locale($ctx['attempt']);
 if($_SERVER['REQUEST_METHOD']==='GET'&&!empty($ctx['invite']['confirmed_at'])&&!empty($ctx['invite']['registration_id'])){
  $existing=fcc_partner_one("SELECT * FROM fcc_webinar_registrations WHERE id=? AND user_id=? AND contact_id=? AND status<>'erased'",[(int)$ctx['invite']['registration_id'],(int)$ctx['invite']['user_id'],(int)$ctx['invite']['contact_id']]);
  if($existing){$result=['new'=>false];$r=fcc_webinar_registration_for_token(fcc_webinar_unseal($existing['management_sealed']));require THEME_PATH.'views/partner/webinar-return.php';return;}
 }
 $scope='return:'.hash('sha256',$secret);
 if(!isset($_SESSION['webinar_nonce']))$_SESSION['webinar_nonce']=[];
 if(count($_SESSION['webinar_nonce'])>20)$_SESSION['webinar_nonce']=array_slice($_SESSION['webinar_nonce'],-10,null,true);
 $nonce=$_SESSION['webinar_nonce'][$scope]??null;if(!$nonce||$nonce['at']<time()-7200)$nonce=['value'=>bin2hex(random_bytes(24)),'at'=>time()];$_SESSION['webinar_nonce'][$scope]=$nonce;
 if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
   if(!is_string($_POST['nonce']??null)||!hash_equals($nonce['value'],$_POST['nonce'])||$nonce['at']<time()-7200)throw new InvalidArgumentException(fcc_wl_t('stale'));
   if((int)($_SERVER['CONTENT_LENGTH']??0)>4000)throw new InvalidArgumentException(fcc_wl_t('unavailable'));
   fcc_webinar_rate_limit('return:'.hash('sha256',$secret),15,3600);
   $result=fcc_wl_return_confirm($secret,$_POST);$r=fcc_webinar_registration_for_token($result['manage_token']);
   if($result['new'])fcc_webinar_dispatch_registration($result,(int)$ctx['invite']['user_id']);
  }catch(InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}
 }elseif($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);header('Allow: GET, POST');return;}
 require THEME_PATH.'views/partner/webinar-return.php';
}
