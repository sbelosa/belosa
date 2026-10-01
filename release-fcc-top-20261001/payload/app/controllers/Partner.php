<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();

class Partner extends Controller {
 public function index() {
  $section=(string)($this->params[0]??'today');
  $allowed=['today','contacts','share','cards','more','plan','education','team','notifications','settings','install','admin','preview','curriculum','webinars','points','top'];
  if(!in_array($section,$allowed,true)) throw_404();
  // Desktop device viewer is local tooling, never a production route.
  if($section==='preview') {
   if(getenv('FCC_LOCAL')!=='1'||SITE_URL!=='http://localhost:8095/') throw_404();
   \Altum\Router::$controller_settings['has_view']=false;
   require THEME_PATH.'views/partner/device-preview.php';
   return;
  }
  if($section==='install'&&getenv('FCC_PARTNER_ENABLED')==='1') { \Altum\Router::$controller_settings['has_view']=false; require THEME_PATH.'views/partner/install.php'; return; }
  \Altum\Authentication::guard();
  if(!fcc_partner_enabled()) {
   if(getenv('FCC_PARTNER_ENABLED')!=='1') throw_404();
   \Altum\Alerts::add_info(fcc_t('Tvoj račun koristi postojeći FCC radni prostor.'));
   redirect('dashboard?classic=1');
  }
  header('Cache-Control: no-store, private');
  if($section==='curriculum') {
   if(getenv('FCC_LOCAL')!=='1'||!\Altum\Authentication::is_admin()) throw_404();
   if(($_GET['version']??'')!=='v4'&&fcc_jp_enabled((int)$this->user->user_id)) {
    $review_day=max(1,min(FCC_JP_TOTAL,(int)($_GET['demo']??1)));
    redirect('partner?view=pilot&day='.$review_day.'#journey-day-'.$review_day);
   }
   \Altum\Router::$controller_settings['has_view']=false;
   if($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405); header('Allow: GET'); return; }
   header('Content-Type: text/html; charset=UTF-8');
   if(isset($_GET['demo']))require THEME_PATH.'views/partner/journey90-demo.php';
   else readfile(dirname(__DIR__,2).'/docs/partner/curriculum/MOJ_PUT_90_PREGLED.html');
   return;
  }
  $uid=(int)$this->user->user_id;
  // Contact import pilot retired. Old tabs must never exchange Google codes or run imports.
  if($section==='contacts') {
   foreach(['fcc_cw_oauth','fcc_cw_preview','fcc_cw_chats','fcc_cw_result','fcc_cw_oauth_error'] as $key)unset($_SESSION[$key]);
   if(($this->params[1]??'')==='google-callback'||isset($_GET['workspace'])||str_starts_with((string)($_POST['action']??''),'cw_')) {
    header('Referrer-Policy: no-referrer');
    if($_SERVER['REQUEST_METHOD']==='GET')redirect('partner/contacts');
    \Altum\Router::$controller_settings['has_view']=false;http_response_code(410);return;
   }
  }
  if($section==='share'&&$_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='share_coach') {
   \Altum\Router::$controller_settings['has_view']=false;
   header('Content-Type: application/json; charset=UTF-8');
   require_once APP_PATH.'helpers/fcc_share_coach.php';
   try {
    if(!isset($_POST['token'])||!is_string($_POST['token'])||!hash_equals(\Altum\Csrf::get(),$_POST['token'])){http_response_code(403);echo json_encode(['ok'=>false,'message'=>fcc_share_coach_t('session')]);return;}
    if(!fcc_pro_has_access($uid)){http_response_code(403);echo json_encode(['ok'=>false,'message'=>fcc_share_coach_t('pro')]);return;}
    echo json_encode(['ok'=>true]+fcc_share_coach_reply($uid,$_POST),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
   }catch(\InvalidArgumentException $e){http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
   catch(\Throwable $e){error_log('Share coach: '.get_class($e));http_response_code(503);echo json_encode(['ok'=>false,'message'=>fcc_share_coach_t('error')],JSON_UNESCAPED_UNICODE);}
   return;
  }
  if($section==='team'&&$_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='team_outreach') {
   \Altum\Router::$controller_settings['has_view']=false;
   header('Content-Type: application/json; charset=UTF-8');
   try {
    if(!isset($_POST['token'])||!is_string($_POST['token'])||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new \InvalidArgumentException(fcc_t('Sesija je istekla. Osvježi stranicu.'));
    if(!fcc_pro_has_access($uid)){http_response_code(403);echo json_encode(['ok'=>false,'message'=>fcc_t('Za timske poruke potreban je aktivan PRO paket.'),'upgrade_url'=>url(fcc_pro_upgrade_path('team'))]);return;}
    echo json_encode(['ok'=>true]+fcc_team_outreach_mutate($uid,$_POST),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
   } catch(\InvalidArgumentException $e){http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
   catch(\Throwable $e){error_log('Team outreach: '.get_class($e));http_response_code(500);echo json_encode(['ok'=>false,'message'=>fcc_t('Spremanje nije uspjelo. Tekst ostaje u obrascu. Pokušaj ponovno.')]);}
   return;
  }
  if($section==='top'&&!fcc_pro_has_access($uid)) redirect(fcc_pro_upgrade_path());
  if($section==='team'&&!fcc_pro_has_access($uid)) redirect(fcc_pro_upgrade_path('team'));
  fcc_rollout_enter($uid);
  if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='push_connect') {
   \Altum\Router::$controller_settings['has_view']=false;
   header('Content-Type: application/json');
   try {
    if(!isset($_POST['token'])||!is_string($_POST['token'])||!hash_equals(\Altum\Csrf::get(),$_POST['token']))throw new \InvalidArgumentException(fcc_t('Sesija je istekla. Osvježi stranicu.'));
    if(session_has('admin_user_id')||\Altum\Teams::is_delegated())throw new \InvalidArgumentException(fcc_t('Za povezivanje uređaja vrati se na vlastiti račun.'));
    if((int)($_POST['user_id']??0)!==$uid)throw new \InvalidArgumentException(fcc_t('Prijavljeni račun se promijenio. Osvježi stranicu.'));
    if(!in_array(fcc_partner_push_mode(),['enabled','phone_pilot'],true))throw new \InvalidArgumentException(fcc_t('Obavijesti na uređaju trenutačno nisu dostupne.'));
    fcc_partner_push_connect($uid,$_POST);
    $subscription=json_decode($_POST['subscription'],true);
    setcookie('fcc_partner_device',hash('sha256',$subscription['endpoint']),['expires'=>time()+31536000,'path'=>'/','secure'=>str_starts_with(SITE_URL,'https://'),'httponly'=>true,'samesite'=>'Lax']);
    // A just-connected device may receive its one welcome even if the inbox item predates it.
    fcc_partner_query("INSERT IGNORE INTO fcc_partner_push_deliveries (notification_id,subscription_id,available_at,updated_at) SELECT n.id,s.id,UTC_TIMESTAMP(),UTC_TIMESTAMP() FROM fcc_partner_notifications n JOIN fcc_partner_push_subscriptions s ON s.user_id=n.user_id AND s.endpoint_hash=? AND s.active=1 WHERE n.user_id=? AND n.event_key='onboarding:team-group:v1' AND n.read_at IS NULL AND n.expires_at>UTC_TIMESTAMP() AND NOT EXISTS (SELECT 1 FROM fcc_partner_push_deliveries d WHERE d.notification_id=n.id)",[hash('sha256',$subscription['endpoint']),$uid]);
    echo json_encode(['ok'=>true]);
   }catch(\InvalidArgumentException $e){http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);}
   catch(\Throwable $e){error_log('Partner device connection failed: '.get_class($e));http_response_code(500);echo json_encode(['ok'=>false,'message'=>fcc_t('Povezivanje nije uspjelo. Pokušaj ponovno.')]);}
   return;
  }
  fcc_team_refresh_notifications($uid);
  if($section==='webinars'){fcc_event_member_page($this,array_slice($this->params,1));return;}
  $contact_export_error=null;
  if($section==='contacts' && ($_GET['download']??'')==='vcard') {
   if(!in_array($_SERVER['REQUEST_METHOD'],['GET','POST'],true)) { \Altum\Router::$controller_settings['has_view']=false; http_response_code(405); header('Allow: GET, POST'); return; }
   try { $contact=fcc_partner_contact($uid,(int)($_GET['id']??0)); } catch(\InvalidArgumentException $e) { throw_404(); }
   try {
    if($_SERVER['REQUEST_METHOD']==='POST') {
     if(!isset($_POST['token'])||!is_string($_POST['token'])||!hash_equals(\Altum\Csrf::get(),$_POST['token'])) throw new \InvalidArgumentException(fcc_t('Sesija je istekla. Osvježi stranicu i pokušaj ponovno.'));
     // Read the visible draft without changing the CRM record or its version.
     $contact=fcc_partner_contact_export_fields($_POST);
    }
    \Altum\Router::$controller_settings['has_view']=false;
    header('Content-Type: text/vcard; charset=UTF-8');
    header('Content-Disposition: attachment; filename="fcc-kontakt.vcf"');
    header('X-Content-Type-Options: nosniff');
    echo fcc_partner_contact_vcard($contact);
    return;
   } catch(\InvalidArgumentException $e) { $contact_export_error=$e->getMessage(); http_response_code(422); }
  }
  if(fcc_journey_enabled($uid)&&in_array($section,['education','plan'],true)) {
   if($_SERVER['REQUEST_METHOD']==='POST') { \Altum\Alerts::add_info(fcc_t('Plan i edukacija sada su dio cjeline Moj put. Nastavi na današnjem koraku.')); redirect('partner'); }
   redirect('partner?view='.($section==='education'?'today':(isset($_GET['plan_id'])?'history&plan_id='.(int)$_GET['plan_id']:'goal')));
  }
  if($section==='admin'&&!\Altum\Authentication::is_admin()) throw_404();
  if(in_array($section,['today','contacts','notifications'],true)) fcc_partner_sync_leads($uid);
  $error=$contact_export_error; $values=$_POST;
  if($_SERVER['REQUEST_METHOD']==='POST' && $contact_export_error===null) {
   try {
    if(!isset($_POST['token'])||!is_string($_POST['token'])||!hash_equals(\Altum\Csrf::get(),$_POST['token'])) throw new \InvalidArgumentException(fcc_t('Sesija je istekla. Osvježi stranicu i pokušaj ponovno.'));
    $target=$this->mutate($uid,$section,$_POST);
    try{fcc_rel_usage($uid,$section,true);}catch(\Throwable $ignored){}
    if(!in_array($_POST['action']??'',['pilot_open','card_delete_prepare','card_delete_cancel'],true)) {
     $message=match($_POST['action']??'') {
      'pilot_draft'=>'Tvoj nacrt je spremljen.',
      'pilot_finish'=>'Korak je spremljen. Tvoj napredak je ažuriran.',
      'card_delete'=>'Kartica je uklonjena.',
      default=>'Spremljeno. Podaci su ažurirani na webu i u aplikaciji.'
     };
     \Altum\Alerts::add_success(fcc_t($message));
    }
    redirect($target);
   } catch(\InvalidArgumentException $e) { $error=$e->getMessage(); }
   catch(\Throwable $e) { error_log('Partner mutation: '.get_class($e)); $error=fcc_t('Spremanje nije uspjelo. Tvoj unos je sačuvan u obrascu; pokušaj ponovno.'); }
  }
  if($section==='admin'&&isset($_GET['usage_csv'])){
   if(!\Altum\Authentication::is_admin())throw_404();
   $m=fcc_rel_monitor();header('Content-Type: text/csv; charset=UTF-8');header('Content-Disposition: attachment; filename="fcc-usage.csv"');$f=fopen('php://output','w');fwrite($f,"\xEF\xBB\xBF");fputcsv($f,['day','active_accounts','views','saved_actions']);foreach($m['daily'] as $d)fputcsv($f,[$d['day'],$d['accounts'],$d['views'],$d['actions']]);fclose($f);exit;
  }
  $data=['section'=>$section,'error'=>$error,'values'=>$values,'today'=>fcc_partner_today(),'cards'=>fcc_partner_cards($uid),'form_key'=>bin2hex(random_bytes(16))];
  if(in_array($section,['today','contacts','notifications'],true)) {
   $search=fcc_partner_text($_GET['q']??'',100); $page=max(1,min(10000,(int)($_GET['page']??1))); $offset=($page-1)*40;
   $archived=$section==='contacts'&&($_GET['archived']??'')==='1';
   $where='user_id=? AND archived_at IS '.($archived?'NOT NULL':'NULL'); $params=[$uid];
   $data['business_work']=fcc_business_work($uid);
   if($search!=='') { $where.=' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)'; for($i=0;$i<3;$i++) $params[]='%'.$search.'%'; }
   if(($_GET['filter']??'')==='due') {$where.=" AND next_followup<=? AND contact_permission<>'do_not_contact'"; $params[]=fcc_partner_today();}
   if($section==='contacts'&&($_GET['source']??'')==='business'&&fcc_business_enabled($uid)){$where.=" AND EXISTS (SELECT 1 FROM fcc_partner_contact_sources bs WHERE bs.user_id=fcc_partner_contacts.user_id AND bs.contact_id=fcc_partner_contacts.id AND bs.source_kind='business_presentation')";}
   if($section==='contacts'&&($_GET['source']??'')==='business'&&in_array($_GET['business_filter']??'',['waiting','due','joined','review'],true)){
    $filter=$_GET['business_filter'];$ids=[];foreach($data['business_work']['items'] as $item)if(match($filter){'waiting'=>$item['waiting'],'due'=>$item['due'],'joined'=>(bool)$item['joined_at'],'review'=>(bool)$item['needs_review']})$ids[]=(int)$item['id'];
    $where.=' AND id IN ('.implode(',',$ids?:[0]).')';
   }
   $data['contacts']=fcc_partner_rows("SELECT * FROM fcc_partner_contacts WHERE $where ORDER BY COALESCE(next_followup,'9999-12-31'),updated_at DESC LIMIT 41 OFFSET $offset",$params);
   $data['more_contacts']=count($data['contacts'])>40; $data['contacts']=array_slice($data['contacts'],0,40); $data['page']=$page; $data['search']=$search;
   $data['contact']=null;
   if(isset($_GET['id'])) { try { $data['contact']=fcc_partner_contact($uid,(int)$_GET['id']); } catch(\InvalidArgumentException $e) { throw_404(); } }
   $data['activities']=$data['contact']?fcc_partner_rows('SELECT * FROM fcc_partner_activities WHERE user_id=? AND contact_id=? ORDER BY id DESC LIMIT 20',[$uid,(int)$data['contact']['id']]):[];
   $data['tasks']=fcc_partner_rows('SELECT * FROM fcc_partner_tasks t WHERE user_id=? AND completed_at IS NULL AND NOT EXISTS (SELECT 1 FROM fcc_partner_contacts c WHERE c.id=t.contact_id AND c.user_id=t.user_id AND c.archived_at IS NOT NULL) ORDER BY due_date,id LIMIT 50',[$uid]);
   $data['counts']=fcc_partner_one("SELECT COUNT(*) total,SUM(next_followup<=? AND contact_permission<>'do_not_contact') due FROM fcc_partner_contacts WHERE user_id=? AND archived_at IS NULL",[fcc_partner_today(),$uid]);
  }
  if(in_array($section,['today','team','education'],true)) $data['program']=fcc_partner_program($uid);
  if($section==='team') {
   try { $data['team']=fcc_team_page($uid,$_GET); } catch(\InvalidArgumentException $e) { throw_404(); }
  }
  if($section==='today') $data['sponsor']=fcc_team_sponsor($uid);
  if(in_array($section,['today','plan'],true)) {
   $order=$section==='today'?"(status='active') DESC,id DESC":'id DESC';
   $data['plan']=fcc_partner_one("SELECT * FROM fcc_partner_plans WHERE user_id=? AND status IN ('draft','active') ORDER BY $order LIMIT 1",[$uid]);
   if($section==='plan'&&isset($_GET['plan_id'])) {
    $data['plan']=fcc_partner_one("SELECT * FROM fcc_partner_plans WHERE user_id=? AND id=? AND status IN ('draft','active','archived')",[$uid,(int)$_GET['plan_id']]);
    if(!$data['plan']) throw_404();
   }
   $data['plan_history']=fcc_partner_rows("SELECT id,month,status,created_at FROM fcc_partner_plans WHERE user_id=? AND status IN ('draft','active','archived') ORDER BY id DESC LIMIT 12",[$uid]);
  }
  if($section==='share') {
   $data['cards']=array_values(array_filter($data['cards'],static fn($card)=>(int)$card['is_enabled']===1));
   $data['share_type']=in_array($_GET['type']??'',['webinar','events'],true)?$_GET['type']:'products';
   $shareSid=(int)($_GET['session_id']??0);
   if($shareSid&&in_array($data['share_type'],['webinar','events'],true)) {
    $shareEvent=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND series_id IS NOT NULL',[$shareSid]);
    if(!$shareEvent){$shareEvent=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=? AND owner_user_id=?',[$shareSid,$uid]);if(!$shareEvent)throw_404();}
    elseif(!fcc_event_access($uid,$shareEvent)['allowed'])throw_404();
    if(fcc_event_channel($shareEvent)!==$data['share_type'])redirect(fcc_event_share_path($shareEvent));
   }
   if($data['share_type']==='events'&&!$shareSid)$data['events']=fcc_event_channel_list(fcc_event_list($uid),'events');
   if(in_array($data['share_type'],['webinar','events'],true)) {
    $data['webinar']=fcc_partner_webinar_invitation($data['cards'],null,$shareSid,$uid,$data['share_type']);
    require_once APP_PATH.'helpers/fcc_unified_invite.php';
    try{$data=fcc_invite_data($uid,$data);}catch(\InvalidArgumentException $e){throw_404();}
   } else {
    $q=fcc_partner_text($_GET['q']??'',80);
    $data['content']=fcc_partner_rows("SELECT blog_post_id,title,url,description,sku,image,image_description FROM blog_posts WHERE is_published=1 AND language=? AND (title LIKE ? OR sku=?) ORDER BY blog_post_id DESC LIMIT 30",[(string)\Altum\Language::$name,'%'.$q.'%',$q]);
   }
  }
  if($section==='cards') {
   $data['card_removal']=null;
   $pending=$_SESSION['fcc_card_removal'][$uid]??[];
   if(isset($_GET['remove']) && (int)$_GET['remove']===($pending['link_id']??0) && ($pending['expires']??0)>=time()) {
    try { $data['card_removal']=['card'=>fcc_partner_removable_card($uid,(int)$_GET['remove']),'token'=>$pending['token']]; }
    catch(\InvalidArgumentException $e) { unset($_SESSION['fcc_card_removal'][$uid]); $data['error']=$e->getMessage(); }
   }
   $domains=(new \Altum\Models\Domain())->get_available_domains_by_user($this->user);
   $modal=new \Altum\View('links/create_link_modals',(array)$this);
   \Altum\Event::add_content($modal->run(['domains'=>$domains]),'modals');
  }
  if($section==='notifications') {
   $before=max(1,(int)($_GET['before']??PHP_INT_MAX));
   $data['inbox']=fcc_partner_rows('SELECT * FROM fcc_partner_notifications WHERE user_id=? AND id<? AND (category<>\'admin\' OR ?=1) ORDER BY id DESC LIMIT 40',[$uid,$before,\Altum\Authentication::is_admin()?1:0]);
   $data['inbox']=array_values(array_filter($data['inbox'],fn($n)=>fcc_cc_notification_allowed($uid,$n)));
   $data['notification_preferences']=fcc_partner_notification_preferences($uid);
  }
  if($section==='top') {
   $data['top']=null;
   try {if(fcc_top_ready())$data['top']=fcc_top_page($uid,(int)($_GET['days']??30),(string)($_GET['month']??''));}
   catch(\Throwable $e){error_log('FCC Top overview: '.get_class($e).': '.$e->getMessage());http_response_code(503);}
  }
  if($section==='admin') $data['admin']=fcc_partner_admin_overview();
  if(fcc_journey_enabled($uid)&&$section==='today') {
   $data['journey']=fcc_journey_state($uid);
   if(!empty($data['journey']['journey90'])) {
    $from=(new \DateTimeImmutable(fcc_partner_today(),new \DateTimeZone('Europe/Zagreb')))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $data['journey90_activities']=fcc_partner_rows("SELECT a.id,a.created_at,c.name contact_name FROM fcc_partner_activities a LEFT JOIN fcc_partner_contacts c ON c.id=a.contact_id AND c.user_id=a.user_id WHERE a.user_id=? AND a.created_at>=? AND a.kind IN ('contacted','journey_sent','journey_performed') AND (c.contact_permission IS NULL OR c.contact_permission<>'do_not_contact') AND NOT EXISTS (SELECT 1 FROM forever_business_daily_outcomes o WHERE o.interaction_id=a.id) ORDER BY a.id DESC LIMIT 30",[$uid,$from]);
   }
   $data['journey_view']=in_array($_GET['view']??'',['goal','materials','progress','history','review','coach','step','pilot'],true)?$_GET['view']:'today';
   $data['journey_plans']=fcc_partner_rows("SELECT * FROM fcc_partner_plans WHERE user_id=? AND status IN ('draft','active','archived') ORDER BY id DESC LIMIT 50",[$uid]);
   if(isset($_GET['plan_id'])) {
    $selected=fcc_partner_one('SELECT * FROM fcc_partner_plans WHERE user_id=? AND id=?',[$uid,(int)$_GET['plan_id']]);if(!$selected)throw_404();
    $data['journey_plans']=[$selected];
   }
   $data['journey_reviews']=fcc_partner_rows('SELECT * FROM fcc_partner_journey_reviews WHERE user_id=? ORDER BY week_start DESC LIMIT 30',[$uid]);
   $data['journey_advice']=fcc_partner_rows("SELECT id,action_key,purpose,content,model,created_at FROM fcc_partner_journey_coach WHERE user_id=? AND status='ready' ORDER BY id DESC LIMIT 30",[$uid]);
   $data['journey_articles']=fcc_partner_rows('SELECT blog_post_id,title,image FROM blog_posts WHERE is_published=1 AND language=? ORDER BY title LIMIT 250',[(string)\Altum\Language::$name]);
   if($data['journey_view']==='history') {
    $before=max(1,(int)($_GET['before_outcome']??PHP_INT_MAX));
    $legacyFilter=fcc_j90_schema()?' AND journey_cycle_id IS NULL':'';
    $data['journey_old_steps']=fcc_partner_rows("SELECT outcome_id,action_date,action_key,core_key,sequence_position,note,result_type,completion_mode FROM forever_business_daily_outcomes WHERE recorded_by_user_id=? AND outcome_id<? AND status='done'$legacyFilter ORDER BY outcome_id DESC LIMIT 31",[$uid,$before]);
    $data['journey_more_steps']=count($data['journey_old_steps'])>30;
    $data['journey_old_steps']=array_slice($data['journey_old_steps'],0,30);
    $historyPage=max(1,min(10000,(int)($_GET['history_page']??1)));$historyOffset=($historyPage-1)*20;
    $data['journey_chat_page']=$historyPage;
    $data['journey_chats']=fcc_partner_rows("SELECT fcc_ai_conversation_id,datetime FROM fcc_ai_conversations WHERE user_id=? AND assistant_type='coach' AND scope='internal_coach' ORDER BY fcc_ai_conversation_id DESC LIMIT 21 OFFSET $historyOffset",[$uid]);
    $data['journey_more_chats']=count($data['journey_chats'])>20;$data['journey_chats']=array_slice($data['journey_chats'],0,20);
    $data['journey_chat_messages']=[];
    if(isset($_GET['conversation'])) {
     $chat=fcc_partner_one("SELECT fcc_ai_conversation_id FROM fcc_ai_conversations WHERE fcc_ai_conversation_id=? AND user_id=? AND assistant_type='coach' AND scope='internal_coach'",[(int)$_GET['conversation'],$uid]);
     if(!$chat)throw_404();
     $data['journey_chat_messages']=fcc_partner_rows("SELECT role,content,datetime FROM fcc_ai_messages WHERE fcc_ai_conversation_id=? AND role IN ('user','assistant') AND COALESCE(message_type,'chat')='chat' ORDER BY fcc_ai_message_id",[(int)$chat['fcc_ai_conversation_id']]);
    }
   }
   if(isset($_GET['advice'])) {
    $advice=fcc_partner_one("SELECT * FROM fcc_partner_journey_coach WHERE user_id=? AND id=? AND status='ready'",[$uid,(int)$_GET['advice']]);
    if(!$advice)throw_404(); $data['journey']['coach']=$advice;
   }
  }
  \Altum\Title::set('FCC Partner');
  $view=new \Altum\View('partner/index',(array)$this);
  $this->add_view_content('content',$view->run($data));
 }

 public function state() {
  \Altum\Authentication::guard();
  if(!fcc_partner_enabled()) throw_404();
  \Altum\Router::$controller_settings['has_view']=false;
  header('Content-Type: application/json');header('Cache-Control: no-store, private');
  fcc_team_refresh_notifications((int)$this->user->user_id);
  $uid=(int)$this->user->user_id;
  try{fcc_rel_usage($uid,(string)($_GET['module']??'today'),false,true);}catch(\Throwable $ignored){}
  echo json_encode(['revision'=>fcc_partner_revision($uid),'unread'=>fcc_rel_unread($uid)]);
 }

 private function mutate(int $uid,string $section,array $input): string {
  $action=(string)($input['action']??'');
  if($action==='top_settings'){if($section!=='settings')throw new \InvalidArgumentException(fcc_top_t('conflict'));fcc_top_save($uid,$input);return 'partner/settings#fcc-top-settings';}
  if($section==='share'&&$action==='invite_customize'){require_once APP_PATH.'helpers/fcc_unified_invite.php';return fcc_invite_customize($uid,$input);}
  if(in_array($action,['team_work','team_contact'],true)) {
   fcc_pro_require($uid);
   if($section!=='team') throw new \InvalidArgumentException(fcc_t('Otvori Moj tim za ovu radnju.'));
   if($action==='team_contact') { fcc_team_contact_save($uid,$input); return 'partner/team'; }
   $member=fcc_team_work_mutate($uid,$input); return $member===$uid?'partner/team#team-work':'partner/team?member='.$member.'#team-work';
  }

  if($action==='team_group_dismiss'){fcc_journey_team_group_dismiss($uid,$input);return 'partner';}
  if($action==='team_group_joined'){fcc_journey_team_group_confirm($uid,$input);return fcc_jp_is_cycle(fcc_j90_cycle($uid))?'partner?view=step':'partner?view=pilot';}
  if(in_array($action,['card_delete_prepare','card_delete','card_delete_cancel'],true)) {
   if($section!=='cards') throw new \InvalidArgumentException(fcc_t('Uklanjanje kartica dostupno je u rubrici Moje kartice.'));
   if($action==='card_delete_prepare') {
    fcc_partner_prepare_card_removal($uid,(int)($input['link_id']??0));
    return 'partner/cards?remove='.(int)$input['link_id'];
   }
   if($action==='card_delete_cancel') unset($_SESSION['fcc_card_removal'][$uid]);
   else fcc_partner_remove_card($uid,$input);
   return 'partner/cards';
  }
  if($action==='coach_feedback_resolve') {
   if(!\Altum\Authentication::is_admin()||$section!=='admin') throw new \InvalidArgumentException(fcc_t('Ova radnja dostupna je administratoru.'));
   fcc_ai_mark_feedback_resolved_by_admin((int)($input['feedback_id']??0),$uid);
   return 'partner/admin?coach=1';
  }
  if($action==='pilot_mentor_assign') {
   fcc_jp_assign_mentor($uid,$input);return 'partner/admin?pilot=1&pilot_user='.(int)($input['user_id']??0);
  }
  if(str_starts_with($action,'pilot_')) {
   if($action==='pilot_message_coach') {
    fcc_journey_warm_coach_draft($uid,$input);return 'partner?view=coach&purpose=message&compose=1';
   }
   $op=substr($action,6);fcc_jp_mutate($uid,$op,$input);
   if($op==='draft'&&($input['navigate']??'')==='support') {
    $state=fcc_jp_state($uid,fcc_j90_access($uid));
    return 'feedback-tickets?journey_step='.(int)$state['position'].'#fcc-support-create';
   }
   if($op==='draft'&&($input['navigate']??'')==='coach') return 'partner?view=step&coach=1';
   return 'partner'.($op==='opportunity'?'?view=progress':(in_array($op,['draft','event','open'],true)?'?view=step':''));
  }
  if(str_starts_with($action,'journey90_')) {
   $op=substr($action,10);
   if($op==='webinar_status') {
    if(!\Altum\Authentication::is_admin())throw new \InvalidArgumentException(fcc_t('Samo administrator potvrđuje status događaja.'));
    if(fcc_events_enabled())throw new \InvalidArgumentException(fcc_t('Termin uredi u administraciji Webinari i događaji.'));
    $date=fcc_partner_date($input['event_date']??'');
    if(!$date||(new \DateTimeImmutable($date))->format('N')!=='7')throw new \InvalidArgumentException(fcc_t('Odaberi nedjelju.'));
    $status=fcc_partner_choice($input['event_status']??'',['scheduled'=>1,'cancelled'=>1]);
    fcc_partner_notification_config_save('journey90_webinar_'.$date,$status);
    fcc_journey_event($uid,'webinar_status',$date,'1','webinar-status:'.bin2hex(random_bytes(8)));
    return 'partner/admin?journey=1';
   }
   fcc_j90_mutate($uid,$op,$input);
   return 'partner'.(in_array($op,['draft','compose','open'],true)?'?view=step#journey90-step':'');
  }
  if(str_starts_with($action,'journey_')) {
   if(!fcc_journey_enabled($uid))throw new \InvalidArgumentException(fcc_t('Moj put nije uključen.'));
   if($action==='journey_profile') {fcc_journey_save_profile($uid,$input);return 'partner?view=goal';}
   if($action==='journey_review') {fcc_journey_review_save($uid,$input);return 'partner?view=review';}
   if($action==='journey_coach') {$id=fcc_journey_coach_generate($uid,$input);return 'partner?view=coach&advice='.$id;}
   if($action==='journey_helpful') {
    $helpful=fcc_partner_choice($input['helpful']??'',['1'=>1,'0'=>0]);
    fcc_partner_query('UPDATE fcc_partner_journey_coach SET helpful=? WHERE user_id=? AND id=?',[(int)$helpful,$uid,(int)($input['id']??0)]);return 'partner?view=coach';
   }
   if($action==='journey_lesson') {fcc_journey_lesson_save($uid,$input);return 'partner/admin?journey=1&task='.rawurlencode($input['action_key']).'&lang='.rawurlencode($input['language']);}
   if($action==='journey_start') {
    $s=fcc_journey_state($uid);if(($input['action_key']??'')!==$s['key'])throw new \InvalidArgumentException(fcc_t('Zadatak se promijenio. Osvježi prikaz.'));
    fcc_journey_event($uid,'started',$s['key'],$s['material']['version'],'start:'.$s['key']);return 'partner?view=step';
   }
   throw new \InvalidArgumentException(fcc_t('Radnja nije prepoznata.'));
  }
  if(fcc_journey_enabled($uid)&&in_array($action,['plan_generate','plan_activate'],true)) throw new \InvalidArgumentException(fcc_t('Mjesečni plan zamijenjen je jednim programom. Cilj uredi u Moj put → Moj cilj.'));
  if($action==='notification_rules') {
   if(!\Altum\Authentication::is_admin()) throw new \InvalidArgumentException(fcc_t('Samo administrator upravlja pravilima.'));
   $rules=[];foreach(fcc_partner_notification_rules() as $key=>$rule)$rules[$key]=in_array($key,(array)($input['rules']??[]),true);
   fcc_partner_notification_config_save('rules',$rules);return 'partner/admin';
  }
  if($action==='push_subscribe') {
   fcc_partner_push_subscribe($uid,$input);
   $subscription=json_decode($input['subscription'],true);
   setcookie('fcc_partner_device',hash('sha256',$subscription['endpoint']),['expires'=>time()+31536000,'path'=>'/','secure'=>str_starts_with(SITE_URL,'https://'),'httponly'=>true,'samesite'=>'Lax']);
   return 'partner/notifications';
  }
  if($action==='push_test') {
   fcc_partner_push_test($uid);return 'partner/notifications';
  }
  if($action==='notification_preferences') {
   fcc_cc_preferences_save($uid,$input);return 'partner/notifications#preferences';
  }
  if($action==='push_disable') {
   fcc_partner_query('UPDATE fcc_partner_push_subscriptions SET active=0,updated_at=UTC_TIMESTAMP() WHERE user_id=?',[$uid]);
   $p=fcc_partner_notification_preferences($uid);$p['push']=false;fcc_partner_save_preferences($uid,'partner_notifications',$p);return 'partner/notifications';
  }
  if($action==='notification_open')return fcc_rel_notice_open($uid,(int)($input['id']??0));
  if(in_array($action,['contact_archive','contact_restore'],true)){fcc_rel_contact_archive($uid,(int)($input['id']??0),$action==='contact_restore');return 'partner/contacts?archived='.($action==='contact_archive'?'1':'0');}
  if(in_array($action,['notification_read','notifications_read'],true)) {
   $sql='UPDATE fcc_partner_notifications SET read_at=UTC_TIMESTAMP() WHERE user_id=? AND read_at IS NULL';$params=[$uid];
   if($action==='notification_read'){$sql.=' AND id=?';$params[]=(int)($input['id']??0);}
   fcc_partner_query($sql,$params);return 'partner/notifications';
  }

  if($action==='local_package') {
   if(getenv('FCC_LOCAL')!=='1'||!in_array($this->user->email,['suradnik@fcc.test','drugi@fcc.test','admin@fcc.test'],true)) throw new \InvalidArgumentException(fcc_t('Testni paket dostupan je samo lokalnim testnim računima.'));
   $mode=(string)($input['package']??'');
   if(!in_array($mode,['pro','free','cancel'],true)) throw new \InvalidArgumentException(fcc_t('Odaberi testni paket.'));
   if($mode==='cancel') {
    if((string)$this->user->plan_id==='free') throw new \InvalidArgumentException(fcc_t('Free nema automatsku obnovu. Najprije aktiviraj testni PRO.'));
    fcc_partner_save_preferences($uid,'partner_demo_subscription',['cancel_at_period_end'=>true]);
   } else {
    $plan=$mode==='pro'?fcc_partner_one("SELECT plan_id,settings FROM plans WHERE JSON_EXTRACT(additional_settings,'$.fcc_partner_local')=true LIMIT 1"):null;
    $settings=$plan?json_decode($plan['settings'],true):(array)settings()->plan_free->settings;
    if($mode==='pro'&&!$plan) throw new \InvalidArgumentException(fcc_t('Pokreni lokalne postavke PRO paketa.'));
    fcc_partner_query("UPDATE users SET plan_id=?,plan_settings=?,plan_expiration_date=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 30 DAY) WHERE user_id=?",[$plan['plan_id']??'free',json_encode($settings),$uid]);
    fcc_partner_save_preferences($uid,'partner_demo_subscription',['cancel_at_period_end'=>false]);
    (new \Altum\Models\User())->sync_links_with_plan($uid);
   }
   cache()->deleteItemsByTag('user_id='.$uid);
   return 'account-plan';
  }
  if($action==='contact_save') return 'partner/contacts?id='.fcc_partner_contact_save($uid,$input);
  if($action==='contact_activity') {
   $contact=fcc_partner_contact($uid,(int)($input['contact_id']??0));
   if(!empty($contact['archived_at']))throw new \InvalidArgumentException(fcc_rel_t('archived'));
   if($contact['contact_permission']==='do_not_contact') throw new \InvalidArgumentException(fcc_t('Ovaj kontakt je označen za prestanak javljanja.'));
   $key=$this->requestKey($input); $next=fcc_partner_date($input['next_followup']??'');
   database()->begin_transaction();
   try {
    $existing=fcc_partner_one('SELECT id FROM fcc_partner_activities WHERE user_id=? AND request_key=?',[$uid,$key]);
    if(!$existing) {
     fcc_partner_query("INSERT INTO fcc_partner_activities (user_id,contact_id,kind,note,request_key,created_at) VALUES (?,?,'contacted',?,?,UTC_TIMESTAMP())",[$uid,(int)$contact['id'],fcc_partner_text($input['activity_note']??'',500),$key]);
     $updated=fcc_partner_query('UPDATE fcc_partner_contacts SET next_followup=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND id=? AND version=?',[$next,$uid,(int)$contact['id'],(int)($input['version']??0)]);
     if($updated->affected_rows!==1) throw new \InvalidArgumentException(fcc_t('Kontakt je u međuvremenu promijenjen. Osvježi podatke.'));
     if(!empty($input['business_through_id']))fcc_business_apply_response($uid,(int)$contact['id'],array_replace($input,['outcome'=>$next?'followup':'waiting']));
    }
    database()->commit();
   } catch(\Throwable $e) {database()->rollback();throw $e;}
   return 'partner/contacts?id='.$contact['id'];
  }
  if($action==='task_add') {
   $title=fcc_partner_text($input['title']??'',200); if($title==='') throw new \InvalidArgumentException(fcc_t('Upiši naziv zadatka.'));
   $due=fcc_partner_date($input['due_date']??'')??fcc_partner_today(); $key=$this->requestKey($input);
   fcc_partner_query('INSERT INTO fcc_partner_tasks (user_id,title,due_date,request_key,created_at) VALUES (?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE id=id',[$uid,$title,$due,$key]);
   return 'partner';
  }
  if($action==='task_complete') {
   $task=fcc_partner_one('SELECT * FROM fcc_partner_tasks WHERE user_id=? AND id=?',[$uid,(int)($input['id']??0)]);
   if(!$task) throw new \InvalidArgumentException(fcc_t('Zadatak nije pronađen.'));
   if(!empty($task['contact_id'])&&preg_match('/^business:(reply|followup|onboarding):/',$task['request_key']))return 'partner/contacts?id='.(int)$task['contact_id'];
   fcc_partner_query('UPDATE fcc_partner_tasks SET completed_at=UTC_TIMESTAMP(),version=version+1 WHERE user_id=? AND id=? AND completed_at IS NULL',[$uid,(int)$task['id']]); return 'partner';
  }
  if($action==='preferences') {
   $minutes=(int)($input['minutes']??20); if(!in_array($minutes,[10,20,45],true)) throw new \InvalidArgumentException(fcc_t('Odaberi raspoloživo vrijeme.'));
   fcc_partner_save_preferences($uid,'partner',['minutes'=>$minutes,'onboarding_done'=>true]); return 'partner';
  }
  if($action==='plan_generate') { require_once APP_PATH.'helpers/fcc_partner_ai.php'; fcc_partner_generate_plan($this->user,$input); return 'partner/plan'; }
  if($action==='plan_activate') { fcc_partner_activate_plan($uid,(int)($input['id']??0)); return 'partner/plan'; }
  throw new \InvalidArgumentException(fcc_t('Radnja nije prepoznata.'));
 }
 private function requestKey(array $input): string { $key=fcc_partner_text($input['request_key']??'',64); if(!preg_match('/^[a-f0-9]{32}$/',$key)) throw new \InvalidArgumentException(fcc_t('Osvježi obrazac.')); return $key; }
}
