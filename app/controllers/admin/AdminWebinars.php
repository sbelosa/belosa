<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();
class AdminWebinars extends Controller {
 public function index() {
  \Altum\Authentication::guard();if(!\Altum\Authentication::is_admin()||!fcc_events_enabled())throw_404();
  header('Cache-Control: no-store, private');$uid=(int)$this->user->user_id;$error=null;$preview=null;
  $sid=(int)($_GET['session']??$_POST['session_id']??0);$id=(int)($_GET['series']??$_POST['series_id']??0);
  $s=$sid?fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL',[$sid]):null;if($sid&&!$s)throw_404();if($s)$id=(int)$s['series_id'];
  $series=$id?fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=?',[$id]):null;if($id&&!$series)throw_404();
  /* Custom code: FC-2026-10-15: admin removal supports managed events without changing their organizer. */
  if($series&&!empty($series['organizer_user_id'])&&!($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='remove_event')){
   $managedSession=$s?:fcc_partner_one("SELECT * FROM fcc_webinar_sessions WHERE series_id=? ORDER BY (status='scheduled' AND starts_at_utc>UTC_TIMESTAMP()) DESC,starts_at_utc LIMIT 1",[$id]);
   if($managedSession)redirect('partner-webinar?view=manage&session_id='.(int)$managedSession['id']);
  }
  $d=$s?fcc_event_definition($s):($series?fcc_event_definition($series):fcc_event_defaults((string)($_GET['kind']??'business')));
  if($s){$date=(new \DateTimeImmutable($s['starts_at_utc'],new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone('Europe/Zagreb'));$d['date']=$date->format('Y-m-d');$d['time']=$date->format('H:i');}
  $editing=isset($_GET['new'])||$s||$series;
  if($_SERVER['REQUEST_METHOD']==='POST'){
   try {
    if(!is_string($_POST['token']??null)||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new \InvalidArgumentException(fcc_t('Sesija je istekla. Osvježi stranicu.'));
    if(($_POST['action']??'')==='remove_event'){fcc_event_remove($uid,$sid,(int)($_POST['revision']??0),$_POST);redirect('admin/webinars?removed=1');}
    /* /Custom code: FC-2026-10-15 */
    $action=(string)($_POST['action']??'');if(in_array($action,['save_draft','save_publish'],true)){$_POST['publish_state']=$action==='save_publish'?'published':'draft';$action='save';}
    if(in_array($action,['preview','save','change','edit'],true)){
     $_POST=\fcc_seminar_upload($_POST,$_FILES);$d=fcc_event_validate($_POST,false);$editing=true;
     if($action==='preview'){
      $status=(string)($_POST['event_status']??'scheduled');$d=fcc_event_validate($_POST,$status==='scheduled');$occ=fcc_event_occurrences($d);$preview=['occurrences'=>array_slice($occ,0,3),'eligible'=>0,'pending'=>0,'devices'=>0,'before'=>0];
      $sample=$s?:['id'=>0,'status'=>'scheduled','starts_at_utc'=>$occ[0]['start']??gmdate('Y-m-d H:i:s')];$sample['event_json']=fcc_webinar_json($d);if($occ)$sample['starts_at_utc']=$occ[0]['start'];
      foreach(fcc_partner_rows('SELECT user_id FROM users WHERE status=1') as $member){$a=fcc_event_access((int)$member['user_id'],$sample);$preview['eligible']+=(int)$a['allowed'];$preview['pending']+=(int)($a['state']==='pending');if($s)$preview['before']+=(int)fcc_event_access((int)$member['user_id'],$s)['allowed'];if($a['allowed']){$p=fcc_partner_notification_preferences((int)$member['user_id']);if($p['push']&&!empty($p['categories']['webinar']))$preview['devices']+=(int)(fcc_partner_one('SELECT COUNT(*) n FROM fcc_partner_push_subscriptions WHERE user_id=? AND active=1',[(int)$member['user_id']])['n']??0);}}
     }elseif($action==='save'){$new=fcc_event_save_series($uid,$id,(int)($_POST['revision']??0),$_POST,(string)($_POST['publish_state']??'draft'));redirect('admin/webinars?series='.$new.'&saved=1');}
     elseif($action==='change'){fcc_event_change($uid,$sid,(int)($_POST['revision']??0),$_POST,(string)($_POST['event_status']??'scheduled'),(string)($_POST['scope']??'one'));redirect('admin/webinars?session='.$sid.'&saved=1');}
    }elseif($action==='override'){fcc_event_override($uid,$sid,(int)($_POST['member_id']??0),(string)($_POST['override_value']??''),(string)($_POST['reason']??''));redirect('admin/webinars?session='.$sid.'&saved=1');}
    elseif($action==='series_state'){
     $state=fcc_partner_choice($_POST['series_state']??'',['paused'=>1,'published'=>1]);
     fcc_event_transaction(function()use($id,$uid,$state){$row=fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=? FOR UPDATE',[$id]);if(!$row||(int)$row['revision']!==(int)($_POST['revision']??0))throw new \InvalidArgumentException(fcc_t('Serija je izmijenjena. Osvježi prikaz.'));fcc_partner_query('UPDATE fcc_webinar_series SET status=?,revision=revision+1,updated_at=UTC_TIMESTAMP() WHERE id=?',[$state,$id]);$row['status']=$state;if($state==='published')fcc_event_education_unique(fcc_event_definition($row),$id);fcc_event_materialize($row);fcc_event_audit($uid,'series_state',$id,null,['state'=>$state]);});redirect('admin/webinars?series='.$id.'&saved=1');
    }else throw new \InvalidArgumentException(fcc_t('Radnja nije dostupna.'));
   }catch(\InvalidArgumentException $e){$error=$e->getMessage();http_response_code(422);}
   catch(\Throwable $e){error_log('FCC event admin: '.$e->getMessage());$error=fcc_t('Spremanje nije uspjelo. Tvoj unos je sačuvan.');http_response_code(500);}
  }
  $filter=in_array($_GET['view']??'',['series','archive'],true)?$_GET['view']:'upcoming';
  /* Custom code: FC-2026-10-15: the active schedule ends exactly at the stored UTC end, including series views. */
  $sessions=fcc_partner_rows("SELECT * FROM fcc_webinar_sessions WHERE series_id IS NOT NULL ".($id?'AND series_id=? ':'').($filter==='archive'?"AND (ends_at_utc<=UTC_TIMESTAMP() OR status IN ('cancelled','postponed')) ":"AND status='scheduled' AND ends_at_utc>UTC_TIMESTAMP() ").'ORDER BY starts_at_utc,id LIMIT 200',$id?[$id]:[]);
  /* /Custom code: FC-2026-10-15 */
  $allSeries=fcc_partner_rows('SELECT * FROM fcc_webinar_series ORDER BY updated_at DESC LIMIT 100');
  $members=fcc_partner_rows('SELECT user_id,name FROM users WHERE status=1 ORDER BY name LIMIT 1000');
  $audit=$id?fcc_partner_rows('SELECT * FROM fcc_webinar_audit WHERE series_id=? ORDER BY id DESC LIMIT 15',[$id]):[];
  \Altum\Title::set('Webinari i događaji');$view=new \Altum\View('partner/events-admin',(array)$this);
  $this->add_view_content('content',$view->run(['series'=>$series,'session'=>$s,'definition'=>$d,'error'=>$error,'preview'=>$preview,'editing'=>$editing,'sessions'=>$sessions,'all_series'=>$allSeries,'filter'=>$filter,'members'=>$members,'audit'=>$audit,'member_rsvps'=>$s&&fcc_event_rsvp_ready()?fcc_partner_rows('SELECT r.*,u.name FROM fcc_event_member_rsvps r JOIN users u ON u.user_id=r.user_id WHERE r.session_id=? ORDER BY r.state,r.updated_at DESC',[$sid]):[]]));
 }
}
