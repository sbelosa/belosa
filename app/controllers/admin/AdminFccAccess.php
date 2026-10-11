<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();
class AdminFccAccess extends Controller {
 public function index() {
  \Altum\Authentication::guard('admin');fcc_rollout_admin((int)$this->user->user_id);
  header('Cache-Control: no-store, private');$error=null;$preview=null;$ready=fcc_rollout_ready();
  $extra=trim((string)($_POST['extra_emails']??''));$extras=$extra===''?[]:preg_split('/[\s,;]+/u',$extra,-1,PREG_SPLIT_NO_EMPTY);
  if($_SERVER['REQUEST_METHOD']==='POST'){
   try {
    if(!is_string($_POST['token']??null)||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new \InvalidArgumentException(fcc_t('Sesija je istekla. Osvježi stranicu.'));
    if(!$ready)throw new \InvalidArgumentException(fcc_t('Najprije primijeni migraciju pristupa.'));
    if(($_POST['stage']??'')==='confirm'){
     $pending=$_SESSION['fcc_rollout_pending']??null;
     if(!$pending||$pending['actor']!==(int)$this->user->user_id||$pending['expires']<time()||!hash_equals($pending['input']['request_key'],(string)($_POST['request_key']??'')))throw new \InvalidArgumentException(fcc_t('Pregled je istekao. Ponovno odaberi račune.'));
     $n=fcc_rollout_change((int)$this->user->user_id,$pending['input']+['confirmation'=>(string)($_POST['confirmation']??'')]);unset($_SESSION['fcc_rollout_pending']);
     \Altum\Alerts::add_success('Pristup je spremljen za '.$n.' računa.');redirect('admin/fcc-access');
    }
    $action=(string)($_POST['action']??'');$preview=fcc_rollout_preview($action,(array)($_POST['ids']??[]),$extras);$preview['action']=$action;
    $input=['action'=>$action,'ids'=>$preview['ids'],'extra'=>$extras,'revision'=>(int)fcc_rollout_control()['revision'],'fingerprint'=>$preview['fingerprint'],'request_key'=>bin2hex(random_bytes(16))];
    $_SESSION['fcc_rollout_pending']=['actor'=>(int)$this->user->user_id,'expires'=>time()+900,'input'=>$input];$preview['key']=$input['request_key'];
   }catch(\InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}catch(\Throwable $e){error_log('FCC access admin: '.$e->getMessage());$error=fcc_t('Pristup nije spremljen. Pokušaj ponovno.');http_response_code(500);}
  }
  $q=fcc_partner_text($_GET['q']??'',150);$filter=(string)($_GET['filter']??'active');if(!in_array($filter,['active','new','old','all'],true))$filter='active';
  $rows=[];$count=0;$audit=[];$plan=null;$stats=['active'=>0,'new'=>0];$page=max(1,(int)($_GET['page']??1));
  if($ready){
   $plan=fcc_rollout_seed_plan(fcc_rollout_users(),$extras);$where='1=1';$params=[];
   if($filter!=='all')$where.=' AND u.status=1';
   $access=fcc_rollout_access_sql();
   if($filter==='new')$where.=' AND '.$access;
   if($filter==='old')$where.=' AND NOT '.$access;
   if($q!==''){$where.=' AND (u.name LIKE ? OR u.email LIKE ? OR CAST(u.user_id AS CHAR)=?)';$params=['%'.$q.'%','%'.$q.'%',$q];}
   $join=' FROM users u LEFT JOIN fcc_partner_rollout_access a ON a.user_id=u.user_id';
   $count=(int)fcc_partner_one('SELECT COUNT(*) n'.$join.' WHERE '.$where,$params)['n'];
   $page=min($page,max(1,(int)ceil($count/50)));$rows=fcc_partner_rows('SELECT u.user_id,u.name,u.email,u.type,u.status,u.plan_id,'.$access.' allowed,a.first_entered_at,a.education_cycle_id'.$join.' WHERE '.$where.' ORDER BY u.user_id LIMIT 50 OFFSET '.(($page-1)*50),$params);
   $stats=fcc_partner_one('SELECT COUNT(*) active,COALESCE(SUM('.$access.'),0) new'.$join.' WHERE u.status=1');
   $audit=fcc_partner_rows('SELECT a.action,a.affected_count,a.created_at,u.name actor FROM fcc_partner_rollout_audit a LEFT JOIN users u ON u.user_id=a.actor_user_id ORDER BY a.id DESC LIMIT 15');
  }
  \Altum\Title::set('Pristup novom FCC-u');$v=new \Altum\View('partner/rollout-admin',(array)$this);
  $this->add_view_content('content',$v->run(compact('ready','error','preview','plan','extra','rows','q','filter','count','page','audit','stats')));
 }
}
