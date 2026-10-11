<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();
class AdminMarketingPlan extends Controller {
 public function index() {
  \Altum\Authentication::guard();if(!\Altum\Authentication::is_admin()||!fcc_business_enabled((int)$this->user->user_id))throw_404();header('Cache-Control: no-store, private');
  $actor=(int)$this->user->user_id;
  if(($_GET['view']??'')==='results'&&fcc_business_landing_access($actor)){$stats=fcc_business_landing_stats($actor,(int)($_GET['owner']??0),(string)($_GET['from']??''),(string)($_GET['to']??''));\Altum\Title::set('Rezultati poslovnih prezentacija');$v=new \Altum\View('partner/business-landing-stats',(array)$this);$this->add_view_content('content',$v->run(['stats'=>$stats]));return;}
  $error=null;$preview=null;$health=($_GET['view']??'')==='health';$kind=($_GET['view']??$_POST['kind']??'')==='offers'?'offers':'assets';$id=(int)($_GET['id']??$_POST['id']??0);$table=$kind==='offers'?'fcc_business_market_offers':'fcc_business_assets';
  $row=$id?fcc_partner_one('SELECT * FROM '.$table.' WHERE id=?',[$id]):null;if($id&&!$row)throw_404();
  if($_SERVER['REQUEST_METHOD']==='POST'){
   try{if(!is_string($_POST['token']??null)||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new \InvalidArgumentException(fcc_t('Sesija je istekla. Osvježi stranicu.'));
    if(($_POST['action']??'')==='preview_offer'){$preview=fcc_business_offer_preview($_POST,(int)$this->user->user_id);}else{
    $new=$kind==='offers'?fcc_business_offer_save((int)$this->user->user_id,$id,$_POST):fcc_business_asset_save((int)$this->user->user_id,$id,$_POST);redirect('admin/marketing-plan?view='.$kind.'&id='.$new.'&saved=1');
    }
   }catch(\InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}catch(\Throwable $e){error_log('Business admin: '.$e->getMessage());$error=fcc_t('Spremanje nije uspjelo.');http_response_code(500);}
  }
  $values=array_replace($row?:[],$_POST);if(!isset($values['video_url'])&&$kind==='assets'&&$row)$values['video_url']=fcc_webinar_video_url(['provider'=>$row['provider'],'id'=>$row['video_id'],'hash'=>$row['video_hash']]);
  \Altum\Title::set('Marketing plan');$v=new \Altum\View('partner/business-admin',(array)$this);$this->add_view_content('content',$v->run(['kind'=>$kind,'row'=>$row,'values'=>$values,'error'=>$error,'preview'=>$preview,'health'=>$health,'readiness'=>fcc_business_admin_readiness(),'rows'=>fcc_partner_rows('SELECT * FROM '.$table.' ORDER BY id DESC LIMIT 100')]));
 }
}
