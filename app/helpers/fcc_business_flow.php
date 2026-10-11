<?php
defined('ALTUMCODE') || die();

function fcc_business_flow_ready(): bool {
 static $ready;return $ready??=!!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_business_followups'");
}
function fcc_business_preferences(int $uid): array {
 $u=fcc_partner_one('SELECT language FROM users WHERE user_id=?',[$uid]);$language=strtolower((string)($u['language']??''));$initial=fcc_locale($language);
 return array_replace(['language'=>$initial,'presentation_id'=>null,'reminders'=>1,'reminder_hour'=>9,'timezone'=>'Europe/Zagreb','paused_until'=>null,'version'=>0],fcc_partner_one('SELECT * FROM fcc_business_preferences WHERE user_id=?',[$uid])?:[]);
}
function fcc_business_preferences_save(int $uid,array $in): void {
 $lang=fcc_business_language($in['language']??'en');$hour=(int)($in['reminder_hour']??9);$pause=fcc_partner_date($in['paused_until']??'');
 if($hour<8||$hour>20)throw new InvalidArgumentException(fcc_t('Odaberi vrijeme između 8 i 20 sati.'));
 fcc_business_atomic($uid,function()use($uid,$in,$lang,$hour,$pause){
  $p=fcc_business_preferences($uid);if((int)$p['version']!==(int)($in['preferences_version']??0))throw new InvalidArgumentException(fcc_t('Postavke su promijenjene. Osvježi prikaz.'));
  fcc_partner_query('INSERT INTO fcc_business_preferences (user_id,language,reminders,reminder_hour,paused_until) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE language=VALUES(language),reminders=VALUES(reminders),reminder_hour=VALUES(reminder_hour),paused_until=VALUES(paused_until),version=version+1',[$uid,$lang,empty($in['reminders'])?0:1,$hour,$pause]);
 });
}
function fcc_business_default_presentation(int $uid): ?array {
 $p=fcc_business_preferences($uid);$row=$p['presentation_id']?fcc_partner_one("SELECT * FROM fcc_business_presentations WHERE id=? AND user_id=? AND status='active'",[(int)$p['presentation_id'],$uid]):null;
 return $row?:fcc_partner_one("SELECT * FROM fcc_business_presentations WHERE user_id=? AND status='active' ORDER BY updated_at DESC,id DESC LIMIT 1",[$uid]);
}
/** Persist only on the explicit preparation POST. The owner lock serialises double clicks. */
function fcc_business_quick_prepare(int $uid,array $in): array {
 fcc_business_require_pro($uid);$key=fcc_partner_text($in['request_key']??'',64);
 if(!preg_match('/^[a-f0-9]{32,64}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi obrazac.'));
 return fcc_business_atomic($uid,function()use($uid,$in,$key){
  $prior=fcc_partner_one('SELECT * FROM fcc_business_shares WHERE user_id=? AND request_key=?',[$uid,$key]);if($prior)return $prior;
  $p=!empty($in['presentation_id'])?fcc_business_owned($uid,(int)$in['presentation_id']):fcc_business_default_presentation($uid);
  $pref=fcc_business_preferences($uid);$language=fcc_business_language($in['language']??$p['language']??$pref['language']);
  if(!$p){
   $cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));$card=(int)($cards[0]['link_id']??0);
   if(!fcc_business_owner($uid,$card))throw new InvalidArgumentException(fcc_t('Najprije aktiviraj svoju FCC karticu.'));
   $assets=array_values(array_filter(fcc_business_assets(),fn($a)=>$a['language']===$language));$asset=(int)($assets[0]['id']??0);
   fcc_partner_query('INSERT INTO fcc_business_presentations (user_id,link_id,asset_id,language,created_at,updated_at) VALUES (?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())',[$uid,$card,$asset?:null,$language]);
   $p=fcc_business_owned($uid,(int)database()->insert_id);
  }
  $s=fcc_business_prepare_locked($uid,$p,$in,$key);
  fcc_partner_query('INSERT INTO fcc_business_preferences (user_id,language,presentation_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE language=VALUES(language),presentation_id=VALUES(presentation_id),version=version+1',[$uid,$p['language'],(int)$p['id']]);
  return $s;
 });
}
function fcc_business_topics(): array {return ['general'=>'Želim više informacija','work'=>'Kako izgleda rad','start'=>'Kako započeti','conversation'=>'Želim razgovor'];}
function fcc_business_topic(string $key,string $language='hr'): string {
 return fcc_business_translate($language,fcc_business_topics()[$key]??'Želim više informacija',['general'=>'I would like more information','work'=>'How does the work look?','start'=>'How can I get started?','conversation'=>'I would like a conversation'][$key]??'I would like more information');
}
/** Reconcile only this module's reply task, leaving unrelated tasks untouched. Called inside the owner transaction. */
function fcc_business_sync_reply_task(int $uid,int $cid): void {
 $c=fcc_partner_contact($uid,$cid);$key='business:reply:contact:'.$cid;
 $r=(!empty($c['archived_at'])||$c['contact_permission']==='do_not_contact')?null:fcc_partner_one("SELECT MIN(received_at) first_at FROM fcc_business_leads WHERE user_id=? AND contact_id=? AND status='received' AND needs_review=0 AND answered_at IS NULL",[$uid,$cid]);
 fcc_partner_query("UPDATE fcc_partner_tasks SET completed_at=UTC_TIMESTAMP(),version=version+1 WHERE user_id=? AND contact_id=? AND request_key LIKE 'business:reply:%' AND request_key<>? AND completed_at IS NULL",[$uid,$cid,$key]);
 if(!empty($r['first_at'])){
  $due=(new DateTimeImmutable($r['first_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('Y-m-d');
  fcc_partner_query('INSERT INTO fcc_partner_tasks (user_id,contact_id,title,due_date,request_key,created_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE due_date=VALUES(due_date),completed_at=NULL',[$uid,$cid,'Odgovori na upit o poslovnoj suradnji',$due,$key]);
 }else fcc_partner_query('UPDATE fcc_partner_tasks SET completed_at=UTC_TIMESTAMP(),version=version+1 WHERE user_id=? AND request_key=? AND completed_at IS NULL',[$uid,$key]);
}
function fcc_business_work(int $uid): array {
 if(!fcc_business_enabled($uid)||!fcc_business_flow_ready())return ['counts'=>['waiting'=>0,'due'=>0,'new'=>0,'joined'=>0,'review'=>0],'items'=>[]];
 $today=fcc_partner_today();
 $rows=fcc_partner_rows("SELECT c.id,c.name,c.contact_permission,c.business_stage,f.state,f.due_date,f.joined_at,MAX(r.id) through_id,MIN(CASE WHEN r.answered_at IS NULL AND r.needs_review=0 THEN r.received_at END) unanswered_at,MAX(r.needs_review) needs_review,MAX(r.received_at) received_at,MAX(r.is_demo) is_demo FROM fcc_business_leads r JOIN fcc_partner_contacts c ON c.user_id=r.user_id AND c.id=r.contact_id LEFT JOIN fcc_business_followups f ON f.user_id=c.user_id AND f.contact_id=c.id WHERE r.user_id=? AND r.status='received' AND c.archived_at IS NULL GROUP BY c.id,c.name,c.contact_permission,c.business_stage,f.state,f.due_date,f.joined_at ORDER BY MIN(CASE WHEN r.answered_at IS NULL AND r.needs_review=0 THEN r.received_at END) IS NULL,unanswered_at,f.due_date,c.id",[$uid]);
 $counts=['waiting'=>0,'due'=>0,'new'=>0,'joined'=>0,'review'=>0];$items=[];
 foreach($rows as $r){
  $open=$r['contact_permission']!=='do_not_contact'&&$r['state']!=='closed';
  $r['waiting']=$open&&!$r['needs_review']&&!empty($r['unanswered_at']);
  $r['due']=$open&&!$r['needs_review']&&$r['due_date']&&$r['due_date']<=$today&&in_array($r['state'],['followup','start','registered'],true);
  $counts['waiting']+=(int)$r['waiting'];$counts['due']+=(int)(bool)$r['due'];$counts['review']+=(int)(bool)$r['needs_review'];
  $counts['new']+=(int)($r['received_at']>=gmdate('Y-m-d H:i:s',time()-30*86400));$counts['joined']+=(int)(bool)$r['joined_at'];
  $r['url']=SITE_URL.'partner/contacts?id='.$r['id'];$items[]=$r;
 }
 return ['counts'=>$counts,'items'=>$items];
}
function fcc_business_apply_response(int $uid,int $cid,array $in): void {
 if(!empty(fcc_partner_contact($uid,$cid)['archived_at']))throw new InvalidArgumentException(fcc_rel_t('archived'));
 $through=(int)($in['business_through_id']??0);if(!$through)throw new InvalidArgumentException(fcc_t('Odaberi zaprimljeni upit.'));
 if(fcc_partner_one("SELECT id FROM fcc_business_leads WHERE user_id=? AND contact_id=? AND status='received' AND needs_review=1",[$uid,$cid]))throw new InvalidArgumentException(fcc_t('Najprije provjeri podudaranje podataka.'));
 $scope=fcc_partner_one("SELECT id FROM fcc_business_leads WHERE user_id=? AND contact_id=? AND id=? AND status='received'",[$uid,$cid,$through]);if(!$scope)throw new InvalidArgumentException(fcc_t('Upit je promijenjen. Osvježi kontakt.'));
 $state=fcc_partner_choice($in['outcome']??'waiting',array_fill_keys(['waiting','followup','start','registered','closed'],1));$due=fcc_partner_date($in['next_followup']??'');
 if(in_array($state,['followup','start'],true)&&(!$due||$due<fcc_partner_today()))throw new InvalidArgumentException(fcc_t('Odaberi današnji ili budući datum nastavka.'));
 if(in_array($state,['waiting','closed'],true))$due=null;
 if($state==='registered')$due=$due?:fcc_partner_today();
 if($state==='registered'&&empty($in['registration_confirmed']))throw new InvalidArgumentException(fcc_t('Potvrdi da je službeni postupak završen.'));
 $now=gmdate('Y-m-d H:i:s');$until=(new DateTimeImmutable(($due?:fcc_partner_today()).' 23:59:59',new DateTimeZone('Europe/Zagreb')))->modify('+90 days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
 fcc_partner_query("UPDATE fcc_business_leads SET answered_at=COALESCE(answered_at,?),retention_until=? WHERE user_id=? AND contact_id=? AND id<=? AND status='received' AND needs_review=0",[$now,$until,$uid,$cid,$through]);
 fcc_partner_query('INSERT INTO fcc_business_followups (user_id,contact_id,state,due_date,last_contact_at,joined_at,updated_at) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE state=VALUES(state),due_date=VALUES(due_date),last_contact_at=VALUES(last_contact_at),joined_at=COALESCE(joined_at,VALUES(joined_at)),updated_at=VALUES(updated_at)',[$uid,$cid,$state,$due,$now,$state==='registered'?$now:null,$now]);
 fcc_partner_query("UPDATE fcc_partner_tasks SET completed_at=?,version=version+1 WHERE user_id=? AND contact_id=? AND request_key IN (?,?) AND completed_at IS NULL",[$now,$uid,$cid,'business:followup:'.$cid,'business:onboarding:'.$cid]);
 if($due&&$state!=='closed')fcc_partner_query('INSERT INTO fcc_partner_tasks (user_id,contact_id,title,due_date,request_key,created_at) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE title=VALUES(title),due_date=VALUES(due_date),completed_at=NULL,version=version+1',[$uid,$cid,$state==='registered'?'Dogovori prve korake s novim suradnikom':($state==='start'?'Pomozi oko početka suradnje':'Dogovoreni nastavak poslovnog razgovora'),$due,($state==='registered'?'business:onboarding:':'business:followup:').$cid,$now]);
 if($state==='closed'){
  fcc_partner_query("UPDATE fcc_business_leads SET status='withdrawn',withdrawn_at=? WHERE user_id=? AND contact_id=? AND status='received' AND id<=?",[$now,$uid,$cid,$through]);
  fcc_partner_query("UPDATE fcc_business_jobs j JOIN fcc_business_leads r ON r.id=j.lead_id SET j.state='cancelled',j.payload_json=NULL WHERE r.user_id=? AND r.contact_id=? AND r.status='withdrawn' AND j.state<>'sending'",[$uid,$cid]);
 }
 fcc_business_sync_reply_task($uid,$cid);
 fcc_partner_query("UPDATE fcc_partner_notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()) WHERE user_id=? AND path=? AND event_key LIKE 'business:%'",[$uid,'partner/contacts?id='.$cid]);
}
function fcc_business_response(int $uid,array $in): int {
 $cid=(int)($in['contact_id']??0);$key=fcc_partner_text($in['request_key']??'',64);if(!preg_match('/^[a-f0-9]{32,64}$/D',$key))throw new InvalidArgumentException(fcc_t('Osvježi obrazac.'));
 return fcc_business_atomic($uid,function()use($uid,$cid,$key,$in){
  $c=fcc_partner_contact($uid,$cid);$prior=fcc_partner_one('SELECT contact_id FROM fcc_partner_activities WHERE user_id=? AND request_key=?',[$uid,'business:response:'.$key]);if($prior){if((int)$prior['contact_id']!==$cid)throw new InvalidArgumentException(fcc_t('Osvježi obrazac.'));return $cid;}
  if((int)$c['version']!==(int)($in['version']??0))throw new InvalidArgumentException(fcc_t('Kontakt je promijenjen. Osvježi prikaz.'));
  if($c['contact_permission']==='do_not_contact')throw new InvalidArgumentException(fcc_t('Za ovu osobu je javljanje zaustavljeno.'));
  if(fcc_partner_one("SELECT id FROM fcc_business_leads WHERE user_id=? AND contact_id=? AND status='received' AND needs_review=1",[$uid,$cid]))throw new InvalidArgumentException(fcc_t('Najprije provjeri podudaranje podataka.'));
  fcc_business_apply_response($uid,$cid,$in);$state=$in['outcome']??'waiting';$due=in_array($state,['followup','start','registered'],true)?fcc_partner_date($in['next_followup']??''):null;if($state==='registered')$due=$due?:fcc_partner_today();
  $stage=match($state){'registered'=>'registered','closed'=>'closed','followup','start'=>'followup',default=>$c['business_stage']==='none'?'interested':$c['business_stage']};
  fcc_partner_query('UPDATE fcc_partner_contacts SET business_stage=?,next_followup=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND id=?',[$stage,$due,$uid,$cid]);
  $label=['waiting'=>'Odgovor je osobno potvrđen. Čeka se odgovor.','followup'=>'Dogovoren je nastavak razgovora.','start'=>'Dogovorena je pomoć za početak.','registered'=>'Početak suradnje: osobna potvrda suradnika.','closed'=>'Osoba ne želi nastavak poslovnog razgovora.'][$state];
  fcc_partner_query("INSERT INTO fcc_partner_activities (user_id,contact_id,kind,note,request_key,created_at) VALUES (?,?,'contacted',?,?,UTC_TIMESTAMP())",[$uid,$cid,$label.' '.fcc_partner_text($in['activity_note']??'',500),'business:response:'.$key]);return $cid;
 });
}
function fcc_business_reply(array $r,string $language='hr'): string {
 if(fcc_new_locale($language)) {
  $topic=['start'=>'Rado ću ti pomoći razjasniti prve korake u tvojoj zemlji.','work'=>'Možemo zajedno proći kako izgleda svakodnevni rad.','conversation'=>'Možemo se dogovoriti za kratak razgovor.'][$r['topic']??'general']??'Rado ću odgovoriti na tvoje pitanje.';
  return fcc_t('Bok {name}! Hvala na interesu. {topic} Kada ti odgovara da se kratko čujemo?',['{name}'=>$r['name'],'{topic}'=>fcc_t($topic,[],$language)],$language);
 }

 if(fcc_locale($language)==='sr')return 'Zdravo '.$r['name'].'! Hvala na interesovanju. '.(['start'=>'Rado ću ti pomoći da razjasniš prve korake u svojoj zemlji.','work'=>'Možemo zajedno da prođemo kako izgleda svakodnevni rad.','conversation'=>'Možemo da se dogovorimo za kratak razgovor.'][$r['topic']??'general']??'Rado ću odgovoriti na tvoje pitanje.').' Kada ti odgovara da se kratko čujemo?';
 if(fcc_locale($language)==='de')return 'Hallo '.$r['name'].'! Danke für dein Interesse. '.(['start'=>'Ich helfe dir gerne dabei, die ersten Schritte in deinem Land zu klären.','work'=>'Wir können gemeinsam besprechen, wie die tägliche Arbeit aussieht.','conversation'=>'Wir können uns für ein kurzes Gespräch verabreden.'][$r['topic']??'general']??'Ich gehe gerne auf deine Frage ein.').' Wann passt es dir, kurz zu sprechen?';
 if($language==='en')return 'Hi '.$r['name'].'! Thank you for your interest. '.(['start'=>'I can help you check the first steps for your country.','work'=>'We can go through what the work involves.','conversation'=>'We can arrange a short conversation.'][''.$r['topic']]??'I will be happy to go through your question.').' What would be a convenient time to talk?';
 $topic=$r['topic']??'general';$text=match($topic){'start'=>'Mogu ti pomoći provjeriti prve korake'.(!empty($r['country'])?' za zemlju '.(fcc_business_countries()[$r['country']]??''):'').'.','work'=>'Možemo proći kako izgleda svakodnevni rad.','conversation'=>'Možemo dogovoriti kratak razgovor.',default=>!empty($r['question'])?'Pročitao/la sam tvoje pitanje i rado ću ga proći s tobom.':'Rado ću ti pojasniti mogućnosti suradnje.'};
 return 'Pozdrav '.$r['name'].'! Hvala na upitu. '.$text.' Kada ti odgovara da se čujemo?';
}
function fcc_business_webinars(array $s): array {
 if(empty($s['available'])||!fcc_events_enabled())return []; $out=[];$card=fcc_partner_one('SELECT url FROM links WHERE link_id=? AND user_id=?',[(int)$s['link_id'],(int)$s['user_id']]);
 foreach(fcc_event_list((int)$s['user_id'],null,true,'business') as $e){$d=fcc_event_definition($e);if($d['audience']!=='team'||!$d['guests'])continue;$e['url']=SITE_URL.'webinar-event/'.$e['id'].'?'.http_build_query(['ref'=>$card['url']]);$out[]=$e;if(count($out)===3)break;}return $out;
}
/** Same-browser handoff, never put contact details or private management tokens into a URL. */
function fcc_business_webinar_handoff(array $s,int $sessionId,?int $receipt): string {
 $event=null;foreach(fcc_business_webinars($s) as $e)if((int)$e['id']===$sessionId)$event=$e;
 if(!$event)throw new InvalidArgumentException(fcc_t('Termin više nije dostupan. Odaberi drugi termin.'));
 $r=$receipt?fcc_partner_one("SELECT id,name,email,phone,user_id,contact_id FROM fcc_business_leads WHERE id=? AND share_id=? AND user_id=? AND status='received'",[$receipt,(int)$s['id'],(int)$s['user_id']]):null;
 if(!$r)return $event['url'];
 $token=bin2hex(random_bytes(16));$all=$_SESSION['fcc_business_handoffs']??[];$all=array_slice($all,-9,null,true);$all[$token]=['until'=>time()+1800,'session_id'=>$sessionId,'lead'=>$r];$_SESSION['fcc_business_handoffs']=$all;
 return $event['url'].'&business_handoff='.$token;
}
function fcc_business_reminders(?DateTimeImmutable $now=null,int $onlyUid=0): int {
 if(!fcc_business_enabled()||!fcc_business_flow_ready())return 0;$now=$now??new DateTimeImmutable('now',new DateTimeZone('UTC'));$n=0;
 foreach(fcc_partner_rows("SELECT DISTINCT user_id FROM fcc_business_leads WHERE status='received'".($onlyUid?' AND user_id=?':''),$onlyUid?[$onlyUid]:[]) as $owner){$uid=(int)$owner['user_id'];if(!fcc_business_enabled($uid))continue;$p=fcc_business_preferences($uid);$at=$now->setTimezone(new DateTimeZone($p['timezone']));$date=$at->format('Y-m-d');$hour=(int)$at->format('G');
  if(!$p['reminders']||($p['paused_until']&&$p['paused_until']>=$date)||$hour<(int)$p['reminder_hour']||$hour>=21)continue;
  $work=fcc_business_work($uid);$item=null;foreach($work['items'] as $r)if($r['due']||($r['waiting']&&substr($r['unanswered_at'],0,10)<$date)){$item=$r;break;}
  if(!$item)continue;$key='business:daily:'.$date;if(fcc_partner_one('SELECT id FROM fcc_partner_notifications WHERE user_id=? AND event_key=?',[$uid,$key]))continue;
  fcc_partner_notify($uid,$key,'service','Tvoj sljedeći poslovni razgovor','Provjeri upit ili dogovoreni nastavak.','partner/contacts?id='.$item['id'],86400,$now->format('Y-m-d H:i:s'));$n++;
 }
 return $n;
}

function fcc_business_review(int $uid,array $in): void {
 $cid=(int)($in['contact_id']??0);$rid=(int)($in['lead_id']??0);
 fcc_business_atomic($uid,function()use($uid,$cid,$rid,$in){$c=fcc_partner_contact($uid,$cid);if((int)$c['version']!==(int)($in['version']??0)||empty($in['confirmed']))throw new InvalidArgumentException(fcc_t('Potvrdi provjeru podataka i osvježi kontakt ako je promijenjen.'));
  $r=fcc_partner_one("SELECT * FROM fcc_business_leads WHERE id=? AND user_id=? AND contact_id=? AND status='received'",[$rid,$uid,$cid]);if(!$r)throw new InvalidArgumentException(fcc_t('Upit nije pronađen.'));
  fcc_partner_query('UPDATE fcc_business_leads SET needs_review=0 WHERE id=?',[$rid]);fcc_business_sync_reply_task($uid,$cid);
 });
}
function fcc_business_onboarding(int $uid,array $in): void {
 $cid=(int)($in['contact_id']??0);fcc_business_atomic($uid,function()use($uid,$cid,$in){$c=fcc_partner_contact($uid,$cid);$f=fcc_partner_one('SELECT * FROM fcc_business_followups WHERE user_id=? AND contact_id=?',[$uid,$cid]);
  if(!$f||!$f['joined_at']||empty($in['confirmed'])||$c['contact_permission']==='do_not_contact')throw new InvalidArgumentException(fcc_t('Najprije potvrdi početak suradnje i dogovor s osobom.'));
  if($f['onboarding_at'])return;
  if($c['next_followup']===($f['due_date']??null))fcc_partner_query('UPDATE fcc_partner_contacts SET next_followup=NULL,version=version+1 WHERE id=? AND user_id=?',[$cid,$uid]);
  fcc_partner_query('UPDATE fcc_business_followups SET onboarding_at=UTC_TIMESTAMP(),due_date=NULL,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND contact_id=?',[$uid,$cid]);
  fcc_partner_query("UPDATE fcc_partner_tasks SET completed_at=UTC_TIMESTAMP() WHERE user_id=? AND request_key=? AND completed_at IS NULL",[$uid,'business:onboarding:'.$cid]);
  fcc_partner_query("INSERT IGNORE INTO fcc_partner_activities (user_id,contact_id,kind,note,request_key,created_at) VALUES (?,?,'contacted',?, ?,UTC_TIMESTAMP())",[$uid,$cid,'Prvi razgovor i početni koraci novog suradnika osobno su potvrđeni.','business:onboarding:'.$cid]);
 });
}

function fcc_business_handoff_context(string $key,int $uid,int $session): ?array {
 if(!preg_match('/^[a-f0-9]{32}$/D',$key))return null;$h=$_SESSION['fcc_business_handoffs'][$key]??null;
 if(!$h||$h['until']<time()||(int)$h['session_id']!==$session||(int)$h['lead']['user_id']!==$uid)return null;
 $r=fcc_partner_one("SELECT * FROM fcc_business_leads WHERE user_id=? AND id=? AND status='received' AND needs_review=0",[$uid,(int)$h['lead']['id']]);return $r?:null;
}
function fcc_business_handoff_contact(int $uid,int $session,array $input): int {
 $r=fcc_business_handoff_context((string)($input['_business_handoff']??''),$uid,$session);if(!$r||!$r['contact_id'])return 0;
 $c=fcc_partner_contact($uid,(int)$r['contact_id']);$name=mb_strtolower(trim((string)($input['name']??'')));$email=mb_strtolower(trim((string)($input['email']??'')));$phone=fcc_partner_contact_phone($input['phone']??'',true);
 foreach([$r,$c] as $row)if(mb_strtolower(trim($row['name']))!==$name||($row['email']!==''&&mb_strtolower($row['email'])!==$email)||($row['phone']!==''&&fcc_partner_contact_phone($row['phone'],true)!==$phone))return 0;
 if(fcc_partner_one('SELECT id FROM fcc_partner_contacts WHERE user_id=? AND id<>? AND (LOWER(email)=? OR phone=?)',[$uid,(int)$c['id'],$email,$phone]))return 0;
 fcc_partner_query('UPDATE fcc_partner_contacts SET email=?,phone=?,version=version+1,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND id=?',[$email,$phone,$uid,(int)$c['id']]);
 return (int)$c['id'];
}

function fcc_business_admin_readiness(): array {
 return ['assets'=>fcc_partner_one("SELECT COUNT(*) total,SUM(status='published' AND approved=1 AND is_demo=0) published,SUM(status='published' AND is_demo=1) demo FROM fcc_business_assets"),
 'offers'=>fcc_partner_one("SELECT COUNT(*) total,SUM(status='verified' AND verified_until>UTC_TIMESTAMP()) ready,SUM(status='verified' AND verified_until<DATE_ADD(UTC_TIMESTAMP(),INTERVAL 7 DAY)) review FROM fcc_business_market_offers"),
 'jobs'=>fcc_partner_rows("SELECT state,COUNT(*) n FROM fcc_business_jobs GROUP BY state"),
 'attention'=>fcc_partner_rows("SELECT j.id,j.kind,j.state,j.updated_at,r.user_id FROM fcc_business_jobs j JOIN fcc_business_leads r ON r.id=j.lead_id WHERE j.state IN ('failed','blocked','delivery_unknown') ORDER BY j.id DESC LIMIT 30"),
 'review_offers'=>fcc_partner_rows("SELECT id,title,country,verified_until FROM fcc_business_market_offers WHERE status='verified' AND verified_until<DATE_ADD(UTC_TIMESTAMP(),INTERVAL 7 DAY) ORDER BY verified_until LIMIT 30")];
}
function fcc_business_offer_preview(array $in,int $actor): array {
 $uid=(int)($in['preview_user_id']??0)?:$actor;$cards=array_values(array_filter(fcc_partner_cards($uid),fn($c)=>(int)$c['is_enabled']===1));$owner=fcc_business_owner($uid,(int)($cards[0]['link_id']??0));
 if(!$owner)throw new InvalidArgumentException(fcc_t('Račun nema aktivnu karticu za pregled.'));
 $country=fcc_partner_choice($in['country']??'',fcc_business_countries());$kind=fcc_partner_choice($in['destination_kind']??'',['starter'=>1,'enrollment'=>1]);$adapter=$kind==='starter'?'shop':'join_distrib_id';$url=fcc_partner_text($in['destination_url']??'',1500);
 $offer=['status'=>'verified','country'=>$country,'destination_kind'=>$kind,'destination_url'=>$url,'adapter'=>$adapter,'evidence'=>'Preview only, never persisted','verified_at'=>gmdate('Y-m-d H:i:s'),'verified_until'=>gmdate('Y-m-d H:i:s',time()+60)];
 $dest=fcc_business_destination(['owner'=>$owner,'available'=>true],$offer);if(!$dest)throw new InvalidArgumentException(fcc_t('Provjeri službenu poveznicu, zemlju i broj suradnika prije pregleda.'));
 return ['url'=>$dest,'name'=>$owner['name'],'fbo'=>$owner['fbo'],'country'=>fcc_business_countries()[$country]];
}

function fcc_business_languages(): array {return fcc_language_labels();}
function fcc_business_translate(string $language,string $hr,string $en): string {
 if($language==='hr')return $hr;if($language==='en')return $en;if(in_array($language,['sl','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'],true))return fcc_t($hr,[],$language);static $copy;$copy??=json_decode(file_get_contents(APP_PATH.'config/business-copy.json'),true,512,JSON_THROW_ON_ERROR);return $copy[$en][$language]??$en;
}
function fcc_business_country_label(string $country,string $language): string {
 if($language==='hr')return fcc_business_countries()[$country]??'';
 if($country==='xx')return fcc_business_translate($language,'Druga zemlja','Another country');
 if(class_exists('Locale'))return Locale::getDisplayRegion('und_'.strtoupper($country),fcc_intl_locale($language));
 return ['hr'=>'Croatia','si'=>'Slovenia','ba'=>'Bosnia and Herzegovina','rs'=>'Serbia','me'=>'Montenegro','xk'=>'Kosovo','al'=>'Albania','de'=>'Germany','at'=>'Austria','ch'=>'Switzerland','es'=>'Spain','fr'=>'France','it'=>'Italy','gb'=>'United Kingdom','ie'=>'Ireland','nl'=>'Netherlands','be'=>'Belgium','pl'=>'Poland','se'=>'Sweden','no'=>'Norway','dk'=>'Denmark','us'=>'United States','ca'=>'Canada','au'=>'Australia'][$country]??$country;
}

function fcc_business_admin_reminders(): int {
 if(!fcc_business_enabled()||!fcc_business_flow_ready())return 0;$h=fcc_business_admin_readiness();$issues=count($h['attention'])+count($h['review_offers']);if(!$issues)return 0;$n=0;
 foreach(fcc_partner_rows('SELECT user_id FROM users WHERE type=1 AND status=1') as $u){$uid=(int)$u['user_id'];if(!fcc_business_enabled($uid))continue;$key='business:admin:'.fcc_partner_today();if(fcc_partner_one('SELECT id FROM fcc_partner_notifications WHERE user_id=? AND event_key=?',[$uid,$key]))continue;fcc_partner_notify($uid,$key,'admin','Marketing plan: potrebna provjera','Provjeri ponude i poruke koje traže pozornost.','admin/marketing-plan?view=health',86400);$n++;}return $n;
}

/** Keep a deliberate CRM date or permission edit consistent with this module, without treating it as a sent reply. */
function fcc_business_contact_edited(int $uid,array $before,array $after): void {
 if(!fcc_business_enabled($uid)||!fcc_business_flow_ready())return;$cid=(int)$after['id'];
 $flow=fcc_partner_one('SELECT * FROM fcc_business_followups WHERE user_id=? AND contact_id=?',[$uid,$cid]);
 if($after['contact_permission']==='do_not_contact'||$after['business_stage']==='closed'){
  fcc_partner_query("UPDATE fcc_business_leads SET status='withdrawn',withdrawn_at=UTC_TIMESTAMP() WHERE user_id=? AND contact_id=? AND status='received'",[$uid,$cid]);
  fcc_partner_query("UPDATE fcc_business_jobs j JOIN fcc_business_leads r ON r.id=j.lead_id SET j.state='cancelled',j.payload_json=NULL WHERE r.user_id=? AND r.contact_id=? AND j.state<>'sending'",[$uid,$cid]);
  fcc_partner_query("UPDATE fcc_business_followups SET state='closed',due_date=NULL,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND contact_id=?",[$uid,$cid]);
  fcc_partner_query("UPDATE fcc_partner_tasks SET completed_at=UTC_TIMESTAMP(),version=version+1 WHERE user_id=? AND contact_id=? AND request_key LIKE 'business:%' AND completed_at IS NULL",[$uid,$cid]);
  return;
 }
 if(!$flow||$flow['state']==='closed'||$before['next_followup']===$after['next_followup'])return;
 $due=$after['next_followup'];$state=$due?($flow['state']==='registered'?'registered':($flow['state']==='start'?'start':'followup')):'waiting';
 fcc_partner_query('UPDATE fcc_business_followups SET state=?,due_date=?,updated_at=UTC_TIMESTAMP() WHERE user_id=? AND contact_id=?',[$state,$due,$uid,$cid]);
 fcc_partner_query("UPDATE fcc_partner_tasks SET completed_at=UTC_TIMESTAMP(),version=version+1 WHERE user_id=? AND contact_id=? AND request_key IN (?,?) AND completed_at IS NULL",[$uid,$cid,'business:followup:'.$cid,'business:onboarding:'.$cid]);
 if($due){
  fcc_partner_query('INSERT INTO fcc_partner_tasks (user_id,contact_id,title,due_date,request_key,created_at) VALUES (?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE title=VALUES(title),due_date=VALUES(due_date),completed_at=NULL,version=version+1',[$uid,$cid,$state==='registered'?'Dogovori prve korake s novim suradnikom':'Dogovoreni nastavak poslovnog razgovora',$due,($state==='registered'?'business:onboarding:':'business:followup:').$cid]);
  $until=(new DateTimeImmutable(max($due,fcc_partner_today()).' 23:59:59',new DateTimeZone('Europe/Zagreb')))->modify('+90 days')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
  fcc_partner_query("UPDATE fcc_business_leads SET retention_until=? WHERE user_id=? AND contact_id=? AND status='received'",[$until,$uid,$cid]);
 }
}
function fcc_business_message_templates(string $language): array {
 $text=[
 'first'=>['Bok! Šaljem ti kratku prezentaciju o Forever poslovanju. Možeš pogledati kako izgleda suradnja i koji su prvi koraci. Ako te nešto zanima, javi mi se pa možemo proći pitanja.','Hi! Here is a short presentation about the Forever business. You can learn how it works and explore the first steps. If you have any questions, let me know so we can go through them together.'],
 'agreed'=>['Bok! Prema dogovoru šaljem ti prezentaciju o Forever poslovanju. Što želiš da zajedno prođemo nakon što pogledaš?','Hi! As agreed, here is the presentation about the Forever business. What would you like us to go through after you have had a look?'],
 'interest'=>['Bok! Hvala na interesu. Ovdje možeš pogledati kako izgleda Forever poslovanje i koji su prvi koraci. Što bi ti bilo najkorisnije pojasniti?','Hi! Thank you for your interest. Here is an overview of the Forever business and the first steps. What would be most useful for you to clarify?']];
 return array_map(fn($t)=>fcc_business_translate($language,$t[0],$t[1]),$text);
}
