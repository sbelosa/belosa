<?php
defined('ALTUMCODE') || die();
function fcc_event_member_page($controller,array $params): void {
 if(!fcc_events_enabled())throw_404();$uid=(int)$controller->user->user_id;$sid=(int)($params[0]??0);
 $s=$sid?fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL',[$sid]):null;
 header('Cache-Control: no-store, private');header('X-Robots-Tag: noindex, nofollow');
 if($sid&&(!$s||!fcc_event_access($uid,$s)['allowed']))throw_404();
 if(isset($_GET['check'])){
  \Altum\Router::$controller_settings['has_view']=false;header('Content-Type: application/json');$valid=$s&&fcc_event_invitable($uid,$s)&&(int)($_GET['revision']??0)===(int)$s['revision'];
  if(isset($_GET['attempt'])){try{$a=fcc_webinar_attempt($uid,(int)$_GET['attempt']);$valid=$valid&&(int)$a['session_id']===$sid&&fcc_webinar_can_register(fcc_webinar_public_attempt(fcc_webinar_unseal($a['token_sealed'])));}catch(Throwable $e){$valid=false;}}
  echo json_encode(['ok'=>$valid,'message'=>$valid?'':'Termin ili pozivnica je promijenjena. Osvježi prikaz i pregledaj poruku prije slanja.']);return;
 }
 if($s&&($_GET['download']??'')==='calendar'){
  \Altum\Router::$controller_settings['has_view']=false;header('Content-Type: text/calendar; charset=UTF-8');header('Content-Disposition: attachment; filename="fcc-dogadaj.ics"');echo fcc_event_calendar($uid,$s);return;
 }
 if($s&&($params[1]??'')==='join'){
  if(fcc_event_joinable($s)){header('Location: '.fcc_mw_join_url($s));\Altum\Router::$controller_settings['has_view']=false;return;}
  redirect('partner/webinars/'.$sid.'?join=unavailable');
 }
 $error=null;
 if($_SERVER['REQUEST_METHOD']==='POST'){
  try{if(!$s||!is_string($_POST['token']??null)||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new InvalidArgumentException(fcc_t('Osvježi prikaz prije spremanja.'));if(($_POST['action']??'')==='rsvp') {fcc_event_rsvp_save($uid,$sid,(int)($_POST['revision']??0),(string)($_POST['rsvp']??''));redirect('partner/webinars/'.$sid.'?saved=rsvp#moja-prijava');}
   if(($_POST['action']??'')!=='mute')throw new InvalidArgumentException(fcc_t('Radnja nije dostupna.'));fcc_event_reconcile_member($s,$uid);fcc_partner_query('UPDATE fcc_webinar_member_access SET muted=? WHERE session_id=? AND user_id=?',[empty($_POST['muted'])?0:1,$sid,$uid]);redirect('partner/webinars/'.$sid.'?saved=1');}catch(InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}
 }
 $kind=isset(fcc_event_types()[$_GET['kind']??''])?$_GET['kind']:'';$events=$s?[]:fcc_event_list($uid,null,false,$kind);
 $prefs=$s?fcc_partner_one('SELECT * FROM fcc_webinar_member_access WHERE session_id=? AND user_id=?',[$sid,$uid]):null;
 \Altum\Title::set('Webinari i događaji');$view=new \Altum\View('partner/events',(array)$controller);$controller->add_view_content('content',$view->run(['session'=>$s,'events'=>$events,'kind'=>$kind,'preferences'=>$prefs,'error'=>$error]));
}
