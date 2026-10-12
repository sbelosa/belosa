<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();
class PartnerMarketing extends Controller {
 public function index() {
  \Altum\Authentication::guard();$uid=(int)$this->user->user_id;if(!fcc_business_enabled($uid))throw_404();header('Cache-Control: no-store, private');
  $error=null;$input=$_POST;$id=(int)($_POST['presentation_id']??$_GET['id']??0);$shareId=(int)($_GET['share']??0);$share=null;$p=null;
  if($_SERVER['REQUEST_METHOD']==='POST'){
   try{
    if(!is_string($_POST['token']??null)||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new \InvalidArgumentException(fcc_t('Sesija je istekla. Osvježi stranicu.'));
    $action=(string)($_POST['action']??'');
    if($action==='landing_prepare'){$share=fcc_business_landing_prepare($uid,$_POST);redirect('partner-marketing?share='.$share['id']);}
    /* Custom code: FC-2026-10-12: PRO personal video and introduction for the Pozivnica u tim */
    elseif($action==='invite_personal'){fcc_invite_personal_save($uid,$_POST);redirect('partner-marketing?saved=1#invite-personal');}
    /* /Custom code: FC-2026-10-12 */
    elseif($action==='quick_prepare'){$share=fcc_business_quick_prepare($uid,$_POST);redirect('partner-marketing?share='.$share['id']);}
    elseif($action==='response'){$cid=fcc_business_response($uid,$_POST);redirect('partner/contacts?id='.$cid);}
    elseif($action==='preferences'){fcc_business_preferences_save($uid,$_POST);redirect('partner-marketing?saved=1');}
    elseif($action==='review'){fcc_business_review($uid,$_POST);redirect('partner/contacts?id='.(int)$_POST['contact_id']);}
    elseif($action==='onboarding'){fcc_business_onboarding($uid,$_POST);redirect('partner/contacts?id='.(int)$_POST['contact_id']);}
    elseif($action==='save'){$id=fcc_business_save($uid,$id,$_POST);redirect('partner-marketing?id='.$id.'&saved=1'.(!empty($_POST['step_id'])?'&step_id='.(int)$_POST['step_id']:''));}
    elseif($action==='prepare'){$share=fcc_business_prepare($uid,$id,$_POST);redirect('partner-marketing?share='.$share['id']);}
    elseif($action==='state'){fcc_business_state($uid,$id,(int)($_POST['version']??0),(string)($_POST['status']??''));redirect('partner-marketing?saved=1');}
    elseif(in_array($action,['whatsapp','copy','sent'],true)){fcc_business_record($uid,(int)($_POST['share_id']??0),$action,!empty($_POST['confirmed']));if($action!=='sent'){$this->json(['ok'=>true]);return;}redirect('partner-marketing?share='.(int)$_POST['share_id'].'&saved=1');}
    elseif($action==='run_local'&&fcc_business_local()){foreach(fcc_partner_rows("SELECT id FROM fcc_business_leads WHERE user_id=? AND status='received' ORDER BY id DESC LIMIT 20",[$uid]) as $r)fcc_business_run_jobs((int)$r['id']);redirect('partner-marketing?view=mail');}
    else throw new \InvalidArgumentException(fcc_t('Radnja nije dostupna.'));
   }catch(\InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}
   catch(\Throwable $e){error_log('Business workspace: '.get_class($e).': '.$e->getMessage());$error=fcc_t('Spremanje nije uspjelo. Tvoj unos je sačuvan.');http_response_code(500);}
   if($error&&in_array($_POST['action']??'',['response','review','onboarding'],true)&&is_string($_POST['token']??null)&&hash_equals(\Altum\Csrf::get(),$_POST['token'])&&fcc_partner_one('SELECT id FROM fcc_partner_contacts WHERE user_id=? AND id=?',[$uid,(int)($_POST['contact_id']??0)])){
    $_SESSION['fcc_business_action_error']=['uid'=>$uid,'cid'=>(int)$_POST['contact_id'],'error'=>$error,'values'=>array_intersect_key($_POST,array_flip(['outcome','next_followup','activity_note']))];redirect('partner/contacts?id='.(int)$_POST['contact_id'].'#business-response');
   }
   if(in_array($_POST['action']??'',['whatsapp','copy'],true)){$this->json(['ok'=>false,'error'=>$error]);return;}
  }
  try{if($shareId){$share=fcc_business_share($uid,$shareId);$id=(int)$share['presentation_id'];}if($id)$p=fcc_business_owned($uid,$id);elseif(empty($_GET['new']))$p=fcc_business_default_presentation($uid);}catch(\InvalidArgumentException $e){throw_404();}
  $cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));$owner=fcc_business_owner($uid,(int)($p['link_id']??$cards[0]['link_id']??0));
  $presentations=fcc_partner_rows('SELECT p.*,a.title,a.provider,(SELECT COUNT(*) FROM fcc_business_leads r WHERE r.presentation_id=p.id AND r.status=\'received\') inquiries FROM fcc_business_presentations p LEFT JOIN fcc_business_assets a ON a.id=p.asset_id WHERE p.user_id=? ORDER BY p.updated_at DESC LIMIT 50',[$uid]);
  $view=($_GET['view']??'')==='mail'&&fcc_business_local()?'mail':'compose';
  $mail=$view==='mail'?fcc_partner_rows('SELECT j.* FROM fcc_business_jobs j JOIN fcc_business_leads r ON r.id=j.lead_id WHERE r.user_id=? ORDER BY j.id DESC LIMIT 60',[$uid]):[];
  $landing=fcc_business_landing_access($uid)&&$view==='compose';if($landing&&$share&&!fcc_business_landing_is_new($share)){$share=null;$p=null;}
  /* Custom code: FC-2026-10-12: the member's permanent Pozivnica u tim */
  $invite=null;$inviteError='';if($landing){try{$invite=fcc_invite_for_user($uid);}catch(\InvalidArgumentException $e){$inviteError=$e->getMessage();}}
  /* /Custom code: FC-2026-10-12 */
  \Altum\Title::set($landing?fcc_business_landing_translate(fcc_locale(),'Poslovna prezentacija'):'Marketing plan');$v=new \Altum\View($landing?'partner/business-landing-workspace':'partner/business-workspace',(array)$this);
  $this->add_view_content('content',$v->run(['error'=>$error,'values'=>$input,'presentation'=>$p,'share'=>$share,'owner'=>$owner,'assets'=>fcc_business_assets(),'presentations'=>$presentations,'pro'=>fcc_business_pro($uid),'share_type'=>'business','view'=>$view,'mail'=>$mail,'step_id'=>(int)($_GET['step_id']??0),'preferences'=>fcc_business_preferences($uid),'work'=>fcc_business_work($uid),'editing'=>isset($_GET['id'])||isset($_GET['new']),'landing_access'=>$landing,'landing_stats'=>$landing?fcc_business_landing_stats($uid,$uid):null,'invite'=>$invite,'invite_error'=>$inviteError,'invite_registrations'=>$landing?fcc_invite_registrations($uid):null,'invite_personal'=>$invite?fcc_invite_personal_values($uid,$invite['share']):null,'saved'=>!empty($_GET['saved'])]));
 }
 private function json(array $value): void {\Altum\Router::$controller_settings['has_view']=false;header('Content-Type: application/json');echo fcc_business_json($value);}
}
