<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();

class PartnerWebinar extends Controller {
 public function index() {
  \Altum\Authentication::guard();
  $uid=(int)$this->user->user_id;
  if(!fcc_partner_enabled($uid)||(!fcc_webinar_enabled($uid)&&!fcc_webinar_standard_enabled($uid)))throw_404();
  header('Cache-Control: no-store, private');
  if($_SERVER['REQUEST_METHOD']==='GET'&&($_GET['view']??'')==='check') {
   \Altum\Router::$controller_settings['has_view']=false;header('Content-Type: application/json');
   $valid=fcc_webinar_personal_shareable($uid,(int)($_GET['session_id']??0),(int)($_GET['revision']??0),(int)($_GET['attempt']??0));
   echo json_encode(['ok'=>$valid,'message'=>$valid?'':'Webinar ili pozivnica je promijenjena. Osvježi prikaz i pregledaj poruku prije slanja.']);return;
  }
  if(($_GET['view']??'')==='manage'||(!empty($_GET['create'])&&fcc_mw_ready())){fcc_mw_page($this,$uid);return;}
  if(($_GET['view']??'')==='guests'){fcc_wl_member_page($this,$uid);return;}
  $channel=($_GET['type']??$_POST['invite_channel']??'')==='events'?'events':'webinar';
  $contextSid=(int)($_GET['session_id']??$_POST['session_id']??0);
  if($contextSid){$context=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[$contextSid]);if($context&&(empty($context['owner_user_id'])?fcc_event_access($uid,$context)['allowed']:(int)$context['owner_user_id']===$uid))$channel=fcc_event_channel($context);}
  require_once APP_PATH.'helpers/fcc_unified_invite.php';
  // Existing compose links now open the same editor. Keep record and management views.
  if($_SERVER['REQUEST_METHOD']==='GET'&&!in_array($_GET['view']??'',['mail','activity','events','edit','invite'],true)&&empty($_GET['create'])) {
   $legacyAttempt=null;
   if(isset($_GET['id'])){try{$legacyAttempt=fcc_webinar_attempt($uid,(int)$_GET['id']);}catch(\InvalidArgumentException $e){throw_404();}$contextSid=(int)$legacyAttempt['session_id'];}
   $target='partner/share?type='.$channel.($contextSid?'&session_id='.$contextSid:'').($legacyAttempt?'&invite_id='.(int)$legacyAttempt['id']:'');
   foreach(['contact_id','step_id'] as $field)if(!empty($_GET[$field]))$target.='&'.$field.'='.(int)$_GET[$field];
   redirect($target);
  }
  $error=null;$input=$_POST;
  if($_SERVER['REQUEST_METHOD']==='POST') {
   $json=in_array($_POST['action']??'',['event','outreach_confirm'],true);
   try {
    if(!is_string($_POST['token']??null)||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new \InvalidArgumentException(fcc_t('Sesija je istekla. Osvježi stranicu.'));
    $action=(string)($_POST['action']??'');
    if($action==='outreach_confirm') {
     $row=fcc_webinar_outreach_confirm($uid,$_POST);
     \Altum\Router::$controller_settings['has_view']=false;header('Content-Type: application/json');echo json_encode(['ok'=>true,'id'=>(int)$row['id']]);return;
    } elseif($action==='prepare') {
     $a=fcc_webinar_prepare($uid,$_POST);redirect('partner-webinar?id='.$a['id']);
    } elseif(in_array($action,['personal_save','personal_remove'],true)) {
     $id=(int)($_POST['session_id']??0);
     fcc_webinar_personal_change($uid,$id,(int)($_POST['revision']??0),$_POST,$action==='personal_remove');
     redirect('partner-webinar?view=edit&session_id='.$id.'&saved='.($action==='personal_remove'?'removed':'webinar'));
    } elseif($action==='event') {
     $a=fcc_webinar_record($uid,(int)($_POST['id']??0),(string)($_POST['kind']??''),!empty($_POST['confirmed']));
     \Altum\Router::$controller_settings['has_view']=false;header('Content-Type: application/json');echo json_encode(['ok'=>true]);return;
    } elseif($action==='sent') {
     fcc_webinar_record($uid,(int)($_POST['id']??0),'sent',!empty($_POST['confirmed']));redirect('partner-webinar?id='.(int)$_POST['id'].'&saved=sent');
    } elseif($action==='goal') {
     fcc_webinar_goal($uid,(int)($_POST['id']??0));redirect('partner-webinar?id='.(int)$_POST['id'].'&saved=goal');
    } elseif($action==='revoke') {
     fcc_webinar_require_pro($uid);$a=fcc_webinar_attempt($uid,(int)($_POST['id']??0));
     fcc_partner_query('UPDATE fcc_webinar_invite_attempts SET revoked_at=COALESCE(revoked_at,UTC_TIMESTAMP()) WHERE id=? AND user_id=?',[(int)$a['id'],$uid]);
     redirect('partner-webinar?type='.$channel.'&view=activity');
    } elseif($action==='run_local') {
     if(!fcc_webinar_local())throw_404();
     fcc_webinar_run_jobs(null,40,$uid);redirect('partner-webinar?view=mail');
    } elseif($action==='preview_mail') {
     if(!fcc_webinar_local())throw_404();
     $r=fcc_partner_one('SELECT * FROM fcc_webinar_registrations WHERE id=? AND user_id=? AND is_demo=1',[(int)($_POST['registration_id']??0),$uid]);
     if(!$r||!$r['email_verified_at']||!$r['reminder_opt_in']||$r['status']!=='registered')throw new \InvalidArgumentException(fcc_t('Najprije potvrdi email i odabrane podsjetnike za probnu prijavu.'));
     $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$r['session_id']]);
     if($s['status']!=='scheduled')throw new \InvalidArgumentException(fcc_t('Termin je otkazan.'));
     $kind=fcc_partner_choice($_POST['kind']??'',['reminder24'=>1,'reminder1'=>1]);
     $payload=fcc_webinar_mail_payload($r,$s,$kind);$payload['simulation']=true;
     fcc_webinar_job((int)$r['id'],'preview_'.$kind,(int)$s['revision'],gmdate('Y-m-d H:i:s'));
     fcc_partner_query("UPDATE fcc_webinar_jobs SET state='local_preview',payload_json=?,completed_at=UTC_TIMESTAMP() WHERE unique_key=?",[fcc_webinar_json($payload),'preview_'.$kind.':'.$r['id'].':'.$s['revision']]);
     redirect('partner-webinar?view=mail');
    } elseif($action==='session') {
     if(fcc_events_enabled())throw new \InvalidArgumentException(fcc_t('Termin uredi u administraciji Webinari i događaji.'));
     if(!fcc_webinar_local()||!\Altum\Authentication::is_admin())throw_404();
     $start=new \DateTimeImmutable((string)($_POST['starts_at']??''),new \DateTimeZone('Europe/Zagreb'));
     $end=new \DateTimeImmutable((string)($_POST['ends_at']??''),new \DateTimeZone('Europe/Zagreb'));
     fcc_webinar_change_session((int)($_POST['session_id']??0),(int)($_POST['revision']??0),(string)($_POST['status']??''),$start->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),$end->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
     redirect('partner-webinar?view=activity&saved=session');
    } else throw new \InvalidArgumentException(fcc_t('Radnja nije dostupna.'));
   } catch(\InvalidArgumentException $e) {
    $error=$e->getMessage();http_response_code(422);
    if($json){\Altum\Router::$controller_settings['has_view']=false;header('Content-Type: application/json');echo json_encode(['ok'=>false,'error'=>$error]);return;}
   } catch(\Throwable $e) {
    error_log('Webinar workspace: '.get_class($e));$error=fcc_t('Spremanje nije uspjelo. Pokušaj ponovno.');http_response_code(500);
    if($json){\Altum\Router::$controller_settings['has_view']=false;header('Content-Type: application/json');echo json_encode(['ok'=>false,'error'=>$error]);return;}
   }
  }
  $pro=fcc_webinar_pro($uid);$a=null;$s=null;
  if(isset($_GET['id'])) {
   try{$a=fcc_webinar_attempt($uid,(int)$_GET['id']);}catch(\InvalidArgumentException $e){throw_404();}
   $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$a['session_id']]);$channel=fcc_event_channel($s);
  }
  $view=in_array($_GET['view']??'',['mail','activity','events','edit','invite'],true)?$_GET['view']:'compose';
  if(in_array($_POST['action']??'',['personal_save','personal_remove'],true))$view='edit';
  $editSession=null;
  if($view==='edit') {
   try{$editSession=fcc_webinar_personal_owned($uid,(int)($_POST['session_id']??$_GET['session_id']??0));}catch(\InvalidArgumentException $e){throw_404();}
   $input=array_replace(fcc_webinar_personal_values($editSession),$input);
  }
  $categorySql=fcc_event_channel_sql($channel);
  $registrations=fcc_partner_rows("SELECT r.*,s.title,s.starts_at_utc,s.timezone,c.contact_permission FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id LEFT JOIN fcc_partner_contacts c ON c.id=r.contact_id AND c.user_id=r.user_id WHERE r.user_id=? AND r.status<>'erased' AND $categorySql".($a?' AND r.attempt_id=?':'')." ORDER BY r.id DESC LIMIT 50",$a?[$uid,(int)$a['id']]:[$uid]);
  $attempts=fcc_partner_rows('SELECT a.*,s.title,s.starts_at_utc,s.timezone,(SELECT COUNT(*) FROM fcc_webinar_registrations r WHERE r.attempt_id=a.id AND r.status<>\'erased\') registrations FROM fcc_webinar_invite_attempts a JOIN fcc_webinar_sessions s ON s.id=a.session_id WHERE a.user_id=? AND '. $categorySql .' ORDER BY a.id DESC LIMIT 30',[$uid]);
  $mail=$view==='mail'&&fcc_webinar_local()?fcc_partner_rows("SELECT j.*,r.email,r.attempt_id,r.email_verified_at,r.reminder_opt_in,r.status registration_status FROM fcc_webinar_jobs j JOIN fcc_webinar_registrations r ON r.id=j.registration_id WHERE r.user_id=? AND j.kind<>'project' ORDER BY j.id DESC LIMIT 60",[$uid]):[];
  $cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));
  $default=$cards?fcc_partner_one('SELECT * FROM fcc_webinar_invitations WHERE user_id=? AND card_id=?',[$uid,(int)$cards[0]['link_id']]):null;
  $selectedStep=null;$stepId=(int)($_GET['step_id']??$_POST['step_id']??0);
  if($stepId)$selectedStep=fcc_partner_one('SELECT * FROM fcc_partner_journey_steps WHERE id=? AND user_id=?',[$stepId,$uid]);
  $webinar=fcc_partner_webinar_invitation($cards,null,(int)($_GET['session_id']??$_POST['session_id']??0),$uid,$channel);
  if($webinar['starts_at']&&!fcc_events_enabled()) {
   $materialized=fcc_partner_one("SELECT starts_at_utc,timezone FROM fcc_webinar_sessions WHERE series_key='forever-business' AND occurrence_key=? AND status='scheduled'",[$webinar['starts_at']->format('Y-m-d')]);
   if($materialized)$webinar['starts_at']=(new \DateTimeImmutable($materialized['starts_at_utc'],new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone($materialized['timezone']));
  }
  $data=['error'=>$error,'values'=>$input,'view'=>$view,'pro'=>$pro,'attempt'=>$a,'session'=>$s,'default'=>$default,'registrations'=>$registrations,'attempts'=>$attempts,'mail'=>$mail,'cards'=>$cards,'webinar'=>$webinar,'personal_events'=>$channel==='webinar'?fcc_webinar_personal_list($uid):[],'personal_ready'=>$channel==='webinar'&&fcc_webinar_personal_ready(),'share_type'=>$channel,'form_key'=>bin2hex(random_bytes(16)),'step'=>$selectedStep,'contact_id'=>(int)($_GET['contact_id']??0),'contacts'=>fcc_partner_rows("SELECT id,name FROM fcc_partner_contacts WHERE user_id=? AND contact_permission<>'do_not_contact' ORDER BY name LIMIT 200",[$uid])];
  $data['edit_session']=$editSession;
  /* Custom code: FC-2026-10-15: explicit archive, current webinars expire at their end. */
  $data['events_archive']=($_GET['archive']??'')==='1';
  $data['managed_events']=$view==='events'?array_merge(fcc_mw_overview($uid,$data['events_archive']),fcc_webinar_personal_overview($uid,$data['events_archive'])):[];
  /* /Custom code: FC-2026-10-15 */
  $data['event_registrations']=$editSession?(int)fcc_partner_one("SELECT COUNT(*) n FROM fcc_webinar_registrations WHERE session_id=? AND user_id=? AND status='registered'",[(int)$editSession['id'],$uid])['n']:0;
  \Altum\Title::set(match($view){'edit'=>'Uredi webinar','events'=>'Moji webinari',default=>'Video pozivnica'});
  $template=new \Altum\View('partner/webinar-workspace',(array)$this);
  $this->add_view_content('content',$template->run($data));
 }
}
