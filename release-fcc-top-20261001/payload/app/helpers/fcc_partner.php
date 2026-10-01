<?php
defined('ALTUMCODE') || die();
require_once __DIR__ . '/fcc_partner_tools.php';
require_once __DIR__.'/fcc_top.php';
require_once __DIR__.'/fcc_reliability.php';
require_once __DIR__ . '/fcc_partner_rollout.php';

/** A global flag alone must never enroll the whole production team. */
function fcc_partner_enabled(?int $user_id=null): bool {
 if(getenv('FCC_PARTNER_ENABLED')!=='1') return false;
 if(fcc_rollout_managed()) {
  $uid=$user_id??(int)(\Altum\Authentication::$user_id??0);
  // Render one consistent access snapshot per GET. Mutations and fresh
  // authorization checks still use fcc_rollout_allowed directly.
  if(($_SERVER['REQUEST_METHOD']??'')!=='GET')return fcc_rollout_allowed($uid);
  static $access=[];
  return $access[$uid]??=fcc_rollout_allowed($uid);
 }
 if(getenv('FCC_LOCAL')==='1'||getenv('FCC_PARTNER_ROLLOUT')==='all') return true;
 if(!in_array(getenv('FCC_PARTNER_ROLLOUT'),[false,'','pilot'],true)) return false;
 $user_id=$user_id??(int)(\Altum\Authentication::$user_id??0);
 if($user_id<=0) return false;
 $ids=array_filter(array_map('trim',explode(',',(string)getenv('FCC_PARTNER_PILOT_USER_IDS'))),static fn($id)=>ctype_digit($id)&&(int)$id>0);
 return in_array($user_id,array_map('intval',$ids),true);
}
function fcc_partner_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function fcc_partner_query(string $sql,array $params=[]): mysqli_stmt {
 $stmt=database()->prepare($sql);
 if(!$stmt) throw new RuntimeException(fcc_t('Partner storage is unavailable. Run the reviewed migration.'));
 if($params) { $types=''; foreach($params as $p) $types.=is_int($p)?'i':'s'; $stmt->bind_param($types,...$params); }
 if(!$stmt->execute()) throw new RuntimeException(fcc_t('Partner storage operation failed.'));
 return $stmt;
}
function fcc_partner_rows(string $sql,array $params=[]): array { return fcc_partner_query($sql,$params)->get_result()->fetch_all(MYSQLI_ASSOC); }
function fcc_partner_one(string $sql,array $params=[]): ?array { return fcc_partner_rows($sql,$params)[0]??null; }
function fcc_partner_today(): string { return (new DateTimeImmutable('now',new DateTimeZone('Europe/Zagreb')))->format('Y-m-d'); }
function fcc_partner_text($value,int $limit): string { if(!is_scalar($value)&&$value!==null) throw new InvalidArgumentException(fcc_t('Provjeri unesene podatke.')); return mb_substr(trim(strip_tags((string)$value)),0,$limit); }
function fcc_partner_date($value): ?string {
 $value=fcc_partner_text($value,40); if($value==='') return null;
 $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
 if(!$d||$d->format('Y-m-d')!==$value) throw new InvalidArgumentException(fcc_t('Upiši ispravan datum.')); return $value;
}
function fcc_partner_choice($value,array $choices): string { if(!is_string($value)||!array_key_exists($value,$choices)) throw new InvalidArgumentException(fcc_t('Odaberi jednu od ponuđenih mogućnosti.')); return $value; }
function fcc_partner_sales(): array { return array_map('fcc_t',['new'=>'Novi upit','conversation'=>'Razgovor','recommendation'=>'Preporuka poslana','customer'=>'Kupac · ručna potvrda','later'=>'Kasnije','closed'=>'Zatvoreno']); }
function fcc_partner_business(): array { return array_map('fcc_t',['none'=>'Bez poslovnog interesa','interested'=>'Zanima ga posao','presentation'=>'Prezentacija','followup'=>'Dogovoreni nastavak','registered'=>'Registracija · ručna bilješka','closed'=>'Zatvoreno']); }
function fcc_partner_goals(): array { return array_map('fcc_t',['product_sales'=>'Prodaja proizvoda','recruitment'=>'Razvoj tima','customer_activation'=>'Briga o kupcima','brand_building'=>'Vidljivost i sadržaj']); }
function fcc_partner_contact(int $uid,int $id): array {
 $row=fcc_partner_one('SELECT * FROM fcc_partner_contacts WHERE user_id=? AND id=?',[$uid,$id]);
 if(!$row) throw new InvalidArgumentException(fcc_t('Kontakt nije pronađen.')); return $row;
}
function fcc_partner_contact_save(int $uid,array $input): int {
 if(!empty($input['id'])&&!empty(fcc_partner_contact($uid,(int)$input['id'])['archived_at']))throw new InvalidArgumentException(fcc_rel_t('archived'));
 $name=fcc_partner_text($input['name']??'',120); if($name==='') throw new InvalidArgumentException(fcc_t('Upiši ime kontakta.'));
 $email=mb_strtolower(fcc_partner_text($input['email']??'',320)); if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException(fcc_t('Provjeri e-mail adresu.'));
 $phoneInput=fcc_partner_text($input['phone']??'',40);
 $phone=fcc_partner_contact_phone($phoneInput,true);
 if($phoneInput!==''&&$phone==='') throw new InvalidArgumentException(fcc_t('Telefon upiši s pozivnim brojem države, npr. +385991234567.'));
 $note=fcc_partner_text($input['note']??'',2000);
 $sales=fcc_partner_choice($input['sales_stage']??'new',fcc_partner_sales()); $business=fcc_partner_choice($input['business_stage']??'none',fcc_partner_business());
 $permission=fcc_partner_choice($input['contact_permission']??'requested',['requested'=>1,'existing'=>1,'do_not_contact'=>1,'unconfirmed'=>1]);
 $due=fcc_partner_date($input['next_followup']??'');
 if(in_array($permission,['do_not_contact','unconfirmed'],true)) $due=null;
 $id=(int)($input['id']??0);
 if($id) {
  database()->begin_transaction();
  try {
   fcc_partner_query('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$uid]);
   $before=fcc_partner_contact($uid,$id);
  $stmt=fcc_partner_query('UPDATE fcc_partner_contacts SET name=?,email=?,phone=?,note=?,sales_stage=?,business_stage=?,contact_permission=?,next_followup=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND id=? AND version=?',[$name,$email,$phone,$note,$sales,$business,$permission,$due,$uid,$id,(int)($input['version']??0)]);
  if($stmt->affected_rows!==1) throw new InvalidArgumentException(fcc_t('Kontakt je promijenjen u drugom prozoru. Osvježi podatke prije ponovnog spremanja.'));
   if(function_exists('fcc_business_contact_edited'))fcc_business_contact_edited($uid,$before,fcc_partner_contact($uid,$id));
   database()->commit();
  } catch(Throwable $e){database()->rollback();throw $e;}

 } else {
  $key=fcc_partner_text($input['request_key']??'',64); if(!preg_match('/^[a-f0-9]{32}$/',$key)) throw new InvalidArgumentException(fcc_t('Osvježi obrazac i pokušaj ponovno.'));
  // A source id tied to a submitted random form key prevents accidental resubmission.
  $source=(int)hexdec(substr($key,0,14));
  database()->begin_transaction();
  try {
   fcc_partner_query('SELECT user_id FROM users WHERE user_id=? FOR UPDATE',[$uid]);
   $prior=fcc_partner_one("SELECT id FROM fcc_partner_contacts WHERE user_id=? AND source_kind='manual' AND source_id=?",[$uid,$source]);
   if($prior) { database()->commit(); return (int)$prior['id']; }
   if(($email!==''&&fcc_partner_one('SELECT id FROM fcc_partner_contacts WHERE user_id=? AND email=?',[$uid,$email]))||($phone!==''&&fcc_partner_one('SELECT id FROM fcc_partner_contacts WHERE user_id=? AND phone=?',[$uid,$phone]))) throw new InvalidArgumentException(fcc_t('Kontakt s tim e-mailom ili telefonom već postoji u tvojim kontaktima. Pronađi ga pretragom.'));
   fcc_partner_query("INSERT INTO fcc_partner_contacts (user_id,name,email,phone,note,sales_stage,business_stage,contact_permission,next_followup,source_kind,source_id,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,'manual',?,UTC_TIMESTAMP(),UTC_TIMESTAMP())",[$uid,$name,$email,$phone,$note,$sales,$business,$permission,$due,$source]);
   $id=(int)database()->insert_id;
   database()->commit();
  } catch(Throwable $e) { database()->rollback(); throw $e; }
 }
 return $id;
}
function fcc_partner_save_preferences(int $uid,string $key,array $value): void {
 if(!in_array($key,['partner','leader_ai_profile','partner_notifications'],true)&&!($key==='partner_demo_subscription'&&getenv('FCC_LOCAL')==='1')) throw new InvalidArgumentException(fcc_t('Unsupported preference.'));
 fcc_partner_query("UPDATE users SET preferences=JSON_MERGE_PATCH(COALESCE(NULLIF(preferences,''),'{}'), ?) WHERE user_id=?",[json_encode([$key=>$value],JSON_UNESCAPED_UNICODE),$uid]);
 cache()->deleteItemsByTag('user_id='.$uid); cache()->deleteItem('user?user_id='.$uid);
}

/** Merge only changed preference sections, rejecting a concurrent edit of the same section. */
function fcc_partner_merge_preferences(int $uid,array $before,array $after): array {
 database()->begin_transaction();
 try {
  $row=fcc_partner_one('SELECT preferences FROM users WHERE user_id=? FOR UPDATE',[$uid]);
  if(!$row) throw new InvalidArgumentException(fcc_t('Korisnik nije pronađen.'));
  $current=json_decode($row['preferences']??'{}',true)?:[];
  foreach(array_unique(array_merge(array_keys($before),array_keys($after))) as $key) {
   if(($before[$key]??null)===($after[$key]??null)) continue;
   if(($current[$key]??null)!==($before[$key]??null)&&($current[$key]??null)!==($after[$key]??null)) throw new InvalidArgumentException(fcc_t('Ovaj dio plana promijenjen je u drugom prozoru. Osvježi pregled prije spremanja.'));
   if(array_key_exists($key,$after)) $current[$key]=$after[$key]; else unset($current[$key]);
  }
  fcc_partner_query('UPDATE users SET preferences=? WHERE user_id=?',[json_encode($current,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),$uid]);
  database()->commit();
  cache()->deleteItemsByTag('user_id='.$uid);cache()->deleteItem('user?user_id='.$uid);
  return $current;
 } catch(Throwable $e) {database()->rollback();throw $e;}
}
function fcc_partner_program(int $uid): array {
 static $programs=[];
 $read_request=($_SERVER['REQUEST_METHOD']??'')==='GET';
 if($read_request&&isset($programs[$uid]))return $programs[$uid];
 $state=forever_business_get_vip_program_state($uid);
 $dashboard=forever_business_get_dashboard($uid,false,'','');
 $member=null; foreach($dashboard['members']??[] as $m) if(($m['fbo_id']??'')===($state['fbo_id']??'')) { $member=$m; break; }
 $j90=function_exists('fcc_j90_state')?fcc_j90_state($uid,$state):null;
 $program=['state'=>$state,'member'=>$member,'action'=>$j90?$j90['action']:($member['next_action']??null),'journey90'=>$j90];
 if($read_request)$programs[$uid]=$program;
 return $program;
}
function fcc_partner_cards(int $uid): array {
 $cards=fcc_partner_rows("SELECT l.link_id,l.url,l.settings,l.is_enabled,l.clicks,d.scheme,d.host,d.link_id domain_link_id FROM links l LEFT JOIN domains d ON d.domain_id=l.domain_id AND (d.user_id=l.user_id OR d.user_id IS NULL) WHERE l.user_id=? AND l.type='biolink' ORDER BY l.link_id",[$uid]);
 $main=fc_get_user_main_biolink_id($uid);
 if($main) usort($cards,static fn($a,$b)=>((int)$b['link_id']===$main)<=>((int)$a['link_id']===$main));
 foreach($cards as &$card) { $card['is_nfc']=(int)$card['link_id']===$main; $settings=json_decode($card['settings']??'{}',true)?:[]; $card['title']=($settings['seo']['title']??'')?:$card['url']; $card['public_url']=!empty($card['host'])?$card['scheme'].$card['host'].'/'.((int)$card['domain_link_id']===(int)$card['link_id']?'':$card['url']):SITE_URL.$card['url']; unset($card['settings']); }
 return $cards;
}

function fcc_partner_removable_card(int $uid, int $link_id): array {
 if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.links')) throw new InvalidArgumentException(fcc_t('Nemaš dopuštenje za uklanjanje kartica.'));
 foreach(fcc_partner_cards($uid) as $card) {
  if((int)$card['link_id']!==$link_id) continue;
  if($card['is_nfc']) throw new InvalidArgumentException(fcc_t('Glavna NFC kartica je zaštićena. Nije moguće ukloniti karticu ni promijeniti njezinu adresu.'));
  return $card;
 }
 throw new InvalidArgumentException(fcc_t('Kartica nije pronađena na tvojem računu.'));
}

function fcc_partner_prepare_card_removal(int $uid, int $link_id): void {
 $card=fcc_partner_removable_card($uid,$link_id);
 $_SESSION['fcc_card_removal'][$uid]=['link_id'=>$link_id,'url'=>$card['public_url'],'token'=>bin2hex(random_bytes(32)),'expires'=>time()+600];
}

function fcc_partner_remove_card(int $uid, array $input): void {
 $link_id=(int)($input['link_id']??0);
 $card=fcc_partner_removable_card($uid,$link_id);
 $pending=$_SESSION['fcc_card_removal'][$uid]??[];
 if(($pending['link_id']??0)!==$link_id || ($pending['expires']??0)<time()
  || !is_string($input['confirmation_token']??null)
  || !hash_equals((string)($pending['token']??''),$input['confirmation_token'])
  || ($pending['url']??'')!==$card['public_url']) throw new InvalidArgumentException(fcc_t('Potvrda je istekla ili se kartica promijenila. Ponovno odaberi karticu za uklanjanje.'));
 if(trim((string)($input['confirmation_text']??''))!=='UKLONI') throw new InvalidArgumentException(fcc_t('Za konačnu potvrdu upiši UKLONI.'));
 unset($_SESSION['fcc_card_removal'][$uid]);
 if(!(new \Altum\Models\Link())->delete($link_id)) throw new InvalidArgumentException(fcc_t('Karticu nije moguće ukloniti. Osvježi popis kartica.'));
}
/** Keep invitations on the canonical webinar route with the signed-in owner's referral. */
function fcc_partner_webinar_invitation(array $cards, ?DateTimeImmutable $now=null, int $sessionId=0, ?int $uid=null, string $channel='webinar'): array {
 if(function_exists('fcc_events_enabled')&&fcc_events_enabled()) {
  $cards=array_values(array_filter($cards,static fn($c)=>(int)$c['is_enabled']===1));$card=$cards[0]??null;
  $uid=$uid??($card?(int)(fcc_partner_one('SELECT user_id FROM links WHERE link_id=?',[(int)$card['link_id']])['user_id']??0):(int)(\Altum\Authentication::$user_id??0));
  $events=fcc_event_channel_list(fcc_event_list($uid,$now,true),$channel);$session=null;foreach($events as $item)if(!$sessionId||(int)$item['id']===$sessionId){$session=$item;break;}
  $ref=(string)($card['url']??'');
  return ['starts_at'=>$session?(new DateTimeImmutable($session['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb')):null,'ref'=>$ref,'url'=>$session&&$ref!==''?url('webinar-event/'.$session['id'].'?'.http_build_query(['ref'=>$ref])):null,'session'=>$session,'events'=>$events];
 }
 if($channel==='events')return ['starts_at'=>null,'ref'=>'','url'=>null,'session'=>null,'events'=>[]];
 $now=$now??new DateTimeImmutable('now',new DateTimeZone('Europe/Zagreb'));
 $schedule=forever_business_get_marketing_plan_state($now);
 $next=new DateTimeImmutable($schedule['next_at_iso']);
 // Use the same admin cancellation setting as the daily programme.
 for($i=0;$i<12;$i++) {
  if(fcc_partner_notification_config('journey90_webinar_'.$next->format('Y-m-d'),null)!=='cancelled') break;
  $next=$next->modify('+1 week');
 }
 $available=$i<12;
 $cards=array_values(array_filter($cards,static fn($card)=>(int)$card['is_enabled']===1));
 $ref=(string)($cards[0]['url']??'');
 return ['starts_at'=>$available?$next:null,'ref'=>$ref,'url'=>$ref!==''?\Altum\Link::append_query_parameters_to_url(forever_business_vip_webinar_url(),['ref'=>$ref]):null];
}
function fcc_partner_icon(string $name): string {
 $paths=['chart'=>'M4 20h16 M7 16V9 M12 16V4 M17 16v-5','link'=>'M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-2 2 M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l2-2','home'=>'M3 10 12 3l9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z','people'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M16 3a4 4 0 0 1 0 8 M22 21v-2a4 4 0 0 0-3-3.87 M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0','share'=>'M18 8 12 2 6 8 M12 2v14 M4 13v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7','card'=>'M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2 M6 9h4 M6 14h12','more'=>'M5 12h.01 M12 12h.01 M19 12h.01','arrow'=>'M5 12h14 M13 6l6 6-6 6','check'=>'m5 12 4 4L19 6','spark'=>'m12 3 2.6 6.4L21 12l-6.4 2.6L12 21l-2.6-6.4L3 12l6.4-2.6z','book'=>'M12 5v16 M3 3h5a4 4 0 0 1 4 2 4 4 0 0 1 4-2h5v16h-5a4 4 0 0 0-4 2 4 4 0 0 0-4-2H3z','bell'=>'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9 M10 21h4','plus'=>'M12 5v14 M5 12h14','settings'=>'M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M12 2v3 M12 19v3 M2 12h3 M19 12h3 M5 5l2 2 M17 17l2 2 M5 19l2-2 M17 7l2-2'];
 return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="'.($paths[$name]??$paths['arrow']).'"/></svg>';
}

/* FCC public forms and public AI already persist contact requests in `data`.
 * Only approved contact types enter CRM. Operational rows and conversation text never do. */
function fcc_partner_sync_leads(int $uid): void {
 $lock='fcc_partner_leads_'.$uid;
 if(empty(fcc_partner_one('SELECT GET_LOCK(?,0) acquired',[$lock])['acquired'])) return;
 try {
  $rows=fcc_partner_rows("SELECT d.datum_id,d.type,d.data,d.datetime FROM data d LEFT JOIN fcc_partner_contact_sources s ON s.user_id=d.user_id AND s.source_kind='data' AND s.source_id=d.datum_id WHERE d.user_id=? AND d.type IN ('lead_funnel','ai_chat_lead','contact_form','email_collector') AND s.source_id IS NULL ORDER BY d.datum_id LIMIT 100",[$uid]);
  foreach($rows as $row) {
   $payload=json_decode($row['data']??'{}',true)?:[];
   $name=fcc_partner_text($payload['name']??$payload['full_name']??trim(($payload['first_name']??'').' '.($payload['last_name']??'')),120);
   $email=fcc_partner_text($payload['email']??'',320); if(!filter_var($email,FILTER_VALIDATE_EMAIL)) $email='';
   $phone=preg_replace('/[^+0-9]/','',fcc_partner_text($payload['phone_e164']??$payload['phone']??$payload['whatsapp']??'',40));
   if($name===''&&$email===''&&$phone==='') {
    // A processed empty source must not block all later requests in the next batch.
    fcc_partner_query("INSERT IGNORE INTO fcc_partner_contact_sources (user_id,source_kind,source_id,contact_id,created_at) VALUES (?,'data',?,0,UTC_TIMESTAMP())",[$uid,(int)$row['datum_id']]);
    continue;
   }
   $name=$name?:($email?:$phone);
   $match=$email!==''?fcc_partner_rows('SELECT id,email,phone FROM fcc_partner_contacts WHERE user_id=? AND email=? LIMIT 2',[$uid,$email]):[];
   // Reuse an unambiguous e-mail match only; conflicting phone data needs a human review.
   $id=count($match)===1&&($phone===''||$match[0]['phone']===''||$match[0]['phone']===$phone)?(int)$match[0]['id']:0;
   database()->begin_transaction();
   try {
    if(!$id) {
     fcc_partner_query("INSERT INTO fcc_partner_contacts (user_id,name,email,phone,note,source_kind,source_id,created_at,updated_at) VALUES (?,?,?,?,'','data',?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)",[$uid,$name,$email,$phone,(int)$row['datum_id'],$row['datetime']]);
     $id=(int)database()->insert_id;
    }
    fcc_partner_query("INSERT INTO fcc_partner_contact_sources (user_id,source_kind,source_id,contact_id,created_at) VALUES (?,'data',?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE contact_id=contact_id",[$uid,(int)$row['datum_id'],$id]);
    database()->commit();
   } catch(Throwable $e) {database()->rollback();throw $e;}
  }
 } finally {fcc_partner_query('SELECT RELEASE_LOCK(?)',[$lock]);}
}

function fcc_partner_revision(int $uid): string {
 $user=fcc_partner_one('SELECT preferences FROM users WHERE user_id=?',[$uid]);
 $cards=fcc_partner_rows("SELECT link_id,settings,is_enabled,last_datetime FROM links WHERE user_id=? AND type='biolink' ORDER BY link_id",[$uid]);
 $blocks=fcc_partner_rows('SELECT biolink_block_id,settings,location_url,`order`,is_enabled FROM biolinks_blocks WHERE user_id=? ORDER BY biolink_block_id',[$uid]);
 $counts=[];
 if(fcc_team_ready()) {
  $counts[]=fcc_partner_one('SELECT MAX(id) v FROM fcc_team_audit');
  $counts[]=fcc_partner_one('SELECT COUNT(*) n,SUM(event_count) v FROM fcc_team_notices WHERE sponsor_user_id=?',[$uid]);
  $counts[]=fcc_partner_one('SELECT COUNT(*) n,MAX(updated_at) v,SUM(version) versions FROM fcc_team_work WHERE member_user_id=? OR collaborator_user_id=?',[$uid,$uid]);
 }

 foreach(['fcc_partner_contacts'=>'SUM(version)','fcc_partner_tasks'=>'SUM(version)','fcc_partner_plans'=>'MAX(updated_at)','data'=>'MAX(datum_id)'] as $table=>$expr) $counts[]=fcc_partner_one("SELECT COUNT(*) n,$expr v FROM $table WHERE user_id=?",[$uid]);
 $counts[]=fcc_partner_one('SELECT COUNT(*) n,MAX(outcome_id) v FROM forever_business_daily_outcomes WHERE recorded_by_user_id=?',[$uid]);
 if(function_exists('fcc_journey_enabled')&&fcc_journey_enabled($uid)) { if(function_exists('fcc_j90_schema')&&fcc_j90_schema()) { $counts[]=fcc_partner_rows('SELECT id,version,status,accepted_level FROM fcc_partner_journey_cycles WHERE user_id=?',[$uid]);$counts[]=fcc_partner_rows('SELECT id,draft_version FROM fcc_partner_journey_steps WHERE user_id=?',[$uid]); } foreach(['fcc_partner_journey_reviews','fcc_partner_journey_coach'] as $table)$counts[]=fcc_partner_one("SELECT COUNT(*) n,MAX(updated_at) v FROM $table WHERE user_id=?",[$uid]); $counts[]=fcc_partner_one('SELECT MAX(id) v FROM fcc_partner_journey_lessons WHERE published=1'); }
 if(function_exists('fcc_jp_schema')&&fcc_jp_schema())foreach(['fcc_partner_opportunities'=>'SUM(version)','fcc_partner_pilot_events'=>'MAX(id)','fcc_partner_mentor_assignments'=>'MAX(version)'] as $table=>$expr)$counts[]=fcc_partner_one("SELECT COUNT(*) n,$expr v FROM $table WHERE user_id=?",[$uid]);
 return hash('sha256',json_encode([$user,$cards,$blocks,$counts]));
}

function fcc_partner_activate_plan(int $uid,int $id): void {
   database()->begin_transaction();
   try {
    fcc_partner_query("SELECT user_id FROM users WHERE user_id=? FOR UPDATE",[$uid]);
    $plan=fcc_partner_one("SELECT * FROM fcc_partner_plans WHERE user_id=? AND id=? AND status IN ('draft','active') FOR UPDATE",[$uid,$id]);
    if(!$plan) throw new \InvalidArgumentException(fcc_t('Nacrt plana više nije dostupan.'));
    if($plan['status']==='active') { database()->commit(); return; }
    fcc_partner_query("UPDATE fcc_partner_plans SET status='archived',updated_at=UTC_TIMESTAMP() WHERE user_id=? AND status='active'",[$uid]);
    fcc_partner_query("UPDATE fcc_partner_plans SET status='active',updated_at=UTC_TIMESTAMP() WHERE user_id=? AND id=? AND status='draft'",[$uid,(int)$plan['id']]);
    $time=(int)$plan['minutes'].'m_daily';
    $patch=json_encode(['leader_ai_profile'=>['primary_goal'=>$plan['goal'],'available_time'=>$time,'updated_at'=>date('Y-m-d H:i:s')],'partner'=>['monthly_plan_id'=>(int)$plan['id'],'minutes'=>(int)$plan['minutes']]],JSON_UNESCAPED_UNICODE);
    fcc_partner_query("UPDATE users SET preferences=JSON_MERGE_PATCH(COALESCE(NULLIF(preferences,''),'{}'),?) WHERE user_id=?",[$patch,$uid]);
    database()->commit(); cache()->deleteItemsByTag('user_id='.$uid);cache()->deleteItem('user?user_id='.$uid);
   } catch(\Throwable $e) {database()->rollback();throw $e;}
}

/** Admin aggregates always derive from canonical FCC tables and require admin identity. */
function fcc_partner_admin_overview(): array {
 if(!\Altum\Authentication::is_admin()) throw new RuntimeException(fcc_t('Administrator required.'));
 return [
  'users'=>fcc_partner_one("SELECT COUNT(*) total,SUM(status=0) pending,SUM(status=1) active,SUM(status=1 AND plan_expiration_date>NOW() AND LOWER(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(plan_settings,'$.ai_growth_plan_is_enabled')),'')) IN ('true','1')) paid FROM users"),
  'content'=>fcc_partner_one("SELECT COUNT(*) total,SUM(is_published=1) published FROM blog_posts"),
  'cards'=>fcc_partner_one("SELECT COUNT(*) total,SUM(is_enabled=1) active FROM links WHERE type='biolink'"),
  'support'=>fcc_partner_one("SELECT COUNT(*) total,SUM(status<>'closed') open,SUM(status='open') waiting FROM feedback_tickets"),
  'waiting_support'=>fcc_partner_rows("SELECT t.feedback_ticket_id,t.subject,t.category,t.last_datetime,u.name FROM feedback_tickets t LEFT JOIN users u ON u.user_id=t.user_id WHERE t.status='open' ORDER BY t.last_datetime ASC,t.feedback_ticket_id ASC LIMIT 8"),
  'nfc'=>fcc_partner_one("SELECT COUNT(*) n FROM users WHERE JSON_EXTRACT(preferences,'$.meta.fcc_nfc_required')=1"),
  'recent'=>fcc_partner_rows("SELECT user_id,name,email,status,plan_id,datetime FROM users ORDER BY user_id DESC LIMIT 8"),
 ];
}
function fcc_partner_admin_groups(): array {
 return [
 ['Operativa i automatizacije','Isti postojeći LOS red: odobravanje, glavna kartica, QR, adresa i slanje NFC kartice.',[['Odobrenja i NFC kartice','admin/leader-operating-system?tab=operations'],['Automatizacije e-pošte','admin/automations'],['Obavijesti administratoru','partner/notifications'],['Raspored i stanje crona','admin/settings/cron']]],
 ['Korisnici i pristup','Odobrenja, osobni podaci, prijava u korisnički račun i sigurnost.',[['Korisnici i odobravanje','admin/users'],['Događaji i zapisi','admin/logs'],['Pitanja suradnika','admin/feedback-tickets']]],
 ['Naplata i paketi','Pretplate, uplate, problemi s naplatom i prava paketa.',[['Uplate i računi','admin/payments'],['Naplata koja traži pažnju','admin/billing-risk'],['Paketi i pogodnosti','admin/plans'],['Kodovi i popusti','admin/codes']]],
 ['Tim, LOS i edukacija','Struktura, ostvarenje uvjeta, uvozi Forever podataka i planovi.',[['Leader Operating System','admin/leader-operating-system'],['4 Core napredak i pomoć','admin/leader-operating-system-forever'],['Kvalifikacije i analitika klikova','admin/leader-operating-system?tab=analytics'],['Forever program i uvozi','admin/forever-business'],['Moji osobni planovi','ai-plan']]],
 ['Kartice i sadržaj','Postojeće kartice, predlošci i blog. Javni URL-ovi ostaju sačuvani.',[['Kartice suradnika','admin/links'],['Tvornički predlošci','admin/biolinks-templates'],['Blog i proizvodi','admin/blog-posts'],['Stranice','admin/pages']]],
 ['Sustav i pregled','Postavke, automatizacije i pregled rada cijelog FCC-a.',[['Statistika sustava','admin/statistics'],['Postavke','admin/settings'],['Potpuna administracija','admin']]],
 ];
}

function fcc_partner_install_email_content(string $language='',int $recipient_user_id=0): string {
 if($language==='')$language=\Altum\Language::$name??'Hrvatski';
 // Enrollment belongs to the recipient, never the admin sending the approval.
 if(!fcc_partner_enabled($recipient_user_id)) return '';
 $link=htmlspecialchars(SITE_URL.fcc_locale($language).'/partner/install',ENT_QUOTES,'UTF-8');
 if(fcc_new_locale($language))return fcc_localize_html(str_replace(SITE_URL.'hr/partner/install',SITE_URL.fcc_locale($language).'/partner/install',fcc_partner_install_email_content('hr',$recipient_user_id)),$language);
 if(fcc_locale($language)==='sr')return '<div style="margin-top:24px;padding:20px;background:#edf6f2;border-radius:12px;color:#183b37"><h2 style="font-size:20px">FCC na tvom telefonu</h2><p>Instaliraj FCC Partner sa našeg sajta. Prijavi se istom email adresom i lozinkom koje si izabrao/la pri registraciji na FCC. Ako nalog čeka odobrenje, prijavi se nakon odobrenja.</p><p><a style="color:#126854;font-weight:bold" href="'.$link.'">Instaliraj aplikaciju i pogledaj uputstva →</a></p><p>iPhone: Safari → Podeli → Dodaj na početni ekran. Android: Chrome → Instaliraj aplikaciju.</p></div>';
 if(fcc_locale($language)==='de')return '<div style="margin-top:24px;padding:20px;background:#edf6f2;border-radius:12px;color:#183b37"><h2 style="font-size:20px">FCC auf deinem Smartphone</h2><p>Installiere FCC Partner über unsere Website. Melde dich mit derselben E-Mail-Adresse und demselben Passwort an, die du bei der Registrierung bei FCC verwendet hast. Wenn dein Konto noch auf Freigabe wartet, melde dich nach der Freigabe an.</p><p><a style="color:#126854;font-weight:bold" href="'.$link.'">App installieren und Anleitung ansehen →</a></p><p>iPhone: Safari → Teilen → Zum Home-Bildschirm. Android: Chrome → App installieren.</p></div>';
 if(fcc_locale($language)==='sl')return '<div style="margin-top:24px;padding:20px;background:#edf6f2;border-radius:12px;color:#183b37"><h2 style="font-size:20px">FCC na tvojem telefonu</h2><p>Namesti FCC Partner z naše spletne strani. Prijavi se z istim e-poštnim naslovom in geslom, ki si ju izbral/a ob registraciji v FCC. Če račun čaka na odobritev, se prijavi po odobritvi.</p><p><a style="color:#126854;font-weight:bold" href="'.$link.'">Namesti aplikacijo in preberi navodila →</a></p><p>iPhone: Safari → Deli → Dodaj na začetni zaslon. Android: Chrome → Namesti aplikacijo.</p></div>';
 $english=fcc_locale($language)==='en';
 return '<div style="margin-top:24px;padding:20px;background:#edf6f2;border-radius:12px;color:#183b37"><h2 style="font-size:20px">'.($english?'FCC on your phone':'FCC na tvojem mobitelu').'</h2><p>'.($english?'Install FCC Partner from our website. Sign in with the same email and password you registered on FCC. If your account is awaiting approval, sign in after it is approved.':'Instaliraj FCC Partner s naše stranice. Prijavi se istim e-mailom i lozinkom koje si odabrao/la pri registraciji na FCC webu. Ako račun čeka odobrenje, prijavi se nakon odobrenja.').'</p><p><a style="color:#126854;font-weight:bold" href="'.$link.'">'.($english?'Installation instructions':'Preuzmi aplikaciju i pogledaj upute').' →</a></p><p>'.($english?'iPhone: Safari → Share → Add to Home Screen. Android: Chrome → Install app.':'iPhone: Safari → Podijeli → Dodaj na početni zaslon. Android: Chrome → Instaliraj aplikaciju.').'</p></div>';
}

/** Registration confirmation; account approval and email verification stay unchanged. */
function fcc_partner_send_registration_receipt(string $email,string $name): void {
 if(!fcc_partner_enabled()) return;
 $language=\Altum\Language::$name??'Hrvatski';
 $safe=htmlspecialchars($name,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
 $body='<p>Pozdrav '.$safe.',</p><p>Tvoja registracija na Forever Card Club je zaprimljena. Nakon odobrenja računa prijavljuješ se istim podacima na webu i u aplikaciji. Lozinku nikada ne šaljemo e-mailom.</p>'.fcc_partner_install_email_content();
 $subject='Tvoja FCC registracija i aplikacija';
 if(fcc_locale($language)==='en'){$subject='Your FCC registration and app';$body='<p>Hello '.$safe.',</p><p>Your registration with Forever Card Club has been received. Once your account is approved, sign in to the website and app with the same details. We never send your password by email.</p>'.fcc_partner_install_email_content($language);}
 if(fcc_locale($language)==='sl'){$subject='Tvoja registracija in aplikacija FCC';$body='<p>Živjo '.$safe.',</p><p>Tvoja registracija v Forever Card Club je prejeta. Po odobritvi računa se na spletu in v aplikaciji prijaviš z istimi podatki. Gesla nikoli ne pošiljamo po e-pošti.</p>'.fcc_partner_install_email_content($language);}
 if(fcc_locale($language)==='sr'){$subject='Tvoja FCC registracija i aplikacija';$body='<p>Zdravo '.$safe.',</p><p>Tvoja registracija na Forever Card Club je primljena. Nakon odobrenja naloga prijavi se istim podacima na sajtu i u aplikaciji. Lozinku nikada ne šaljemo emailom.</p>'.fcc_partner_install_email_content($language);}
 if(fcc_locale($language)==='de'){$subject='Deine FCC Registrierung und App';$body='<p>Hallo '.$safe.',</p><p>Deine Registrierung bei Forever Card Club ist eingegangen. Sobald dein Konto freigegeben ist, kannst du dich auf der Website und in der App mit denselben Zugangsdaten anmelden. Dein Passwort senden wir niemals per E-Mail.</p>'.fcc_partner_install_email_content($language);}
 try {
  if(fcc_new_locale($language)){$subject=fcc_t('Tvoja FCC registracija i aplikacija',[],$language);$body='<p>'.htmlspecialchars(fcc_t('Bok {name}!',['{name}'=>$name],$language),ENT_QUOTES,'UTF-8').'</p><p>'.htmlspecialchars(fcc_t('Tvoja registracija na Forever Card Club je zaprimljena. Nakon odobrenja računa prijavljuješ se istim podacima na webu i u aplikaciji. Lozinku nikada ne šaljemo e-mailom.',[],$language),ENT_QUOTES,'UTF-8').'</p>'.fcc_partner_install_email_content($language);}
  $sent=send_mail($email,$subject,$body,['is_system_email'=>true,'language'=>$language,'return_transport_result'=>true]);
  if(!(is_object($sent)?!empty($sent->success):(bool)$sent)) error_log('FCC Partner registration receipt was not delivered; approval notice includes the install link again.');
 } catch(Throwable $e) {error_log('FCC Partner registration receipt unavailable: '.get_class($e));}
}

/** vCard can preserve a local number. WhatsApp requires an explicit country code. */
function fcc_partner_contact_phone(string $phone,bool $international=false): string {
 if(preg_match('/[\x00-\x1F\x7F]/',$phone)) return '';
 $phone=preg_replace('/[\p{Z}().-]/u','',trim($phone));
 if($phone===null) return '';
 if(preg_match('/^00[1-9][0-9]{6,14}$/D',$phone)) $phone='+'.substr($phone,2);
 if(preg_match('/^\+[1-9][0-9]{6,14}$/D',$phone)) return $phone;
 return !$international&&preg_match('/^[0-9]{7,15}$/D',$phone) ? $phone : '';
}

/** Export only the four visible contact fields. No activity history or AI context. */
function fcc_partner_contact_export_fields(array $input): array {
 $contact=[];
 foreach(['name'=>120,'email'=>320,'phone'=>40,'note'=>2000] as $field=>$limit) $contact[$field]=fcc_partner_text($input[$field]??'',$limit);
 if($contact['name']==='') throw new InvalidArgumentException(fcc_t('Upiši ime kontakta.'));
 if($contact['email']!==''&&!filter_var($contact['email'],FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException(fcc_t('Provjeri email adresu kontakta.'));
 if($contact['phone']!==''&&fcc_partner_contact_phone($contact['phone'])==='') throw new InvalidArgumentException(fcc_t('Provjeri broj telefona kontakta.'));
 return $contact;
}

/** Notes are included at the owner's explicit export action, not sent to another person. */
function fcc_partner_contact_vcard(array $contact): string {
 $escape=static function(string $s): string {
  $s=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/','',$s);
  return str_replace(["\r\n","\r","\n",';',','],['\\n','\\n','\\n','\\;','\\,'],str_replace('\\','\\\\',$s));
 };
 $lines=['BEGIN:VCARD','VERSION:3.0','FN:'.$escape((string)($contact['name']??'')),'N:;'.$escape((string)($contact['name']??'')).';;;'];
 $phone=fcc_partner_contact_phone((string)($contact['phone']??''));
 if($phone!=='') $lines[]='TEL;TYPE=CELL:'.$phone;
 $email=(string)($contact['email']??'');
 if(filter_var($email,FILTER_VALIDATE_EMAIL)) $lines[]='EMAIL;TYPE=INTERNET:'.$escape($email);
 $note=trim((string)($contact['note']??''));
 if($note!=='') $lines[]='NOTE:'.$escape($note);
 $lines[]='END:VCARD';
 // Fold by UTF-8 bytes without splitting a multibyte character (RFC 2425).
 foreach($lines as &$line) {
  $parts=[];$limit=75;
  while(strlen($line)>$limit) { $part=mb_strcut($line,0,$limit,'UTF-8');$parts[]=$part;$line=substr($line,strlen($part));$limit=74; }
  $parts[]=$line;$line=implode("\r\n ",$parts);
 } unset($line);
 return implode("\r\n",$lines)."\r\n";
}
function fcc_partner_contact_whatsapp(array $contact): string {
 if(in_array($contact['contact_permission']??'',['do_not_contact','unconfirmed'],true)) return '';
 $phone=fcc_partner_contact_phone((string)($contact['phone']??''),true);
 return $phone!=='' ? 'https://wa.me/'.substr($phone,1) : '';
}
