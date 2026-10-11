<?php
/** One invitation composer; existing tokens remain immutable. */
defined('ALTUMCODE') || die();
require_once __DIR__.'/fcc_webinar_outreach.php';
function fcc_invite_t(string $key): string {
 $copy=[
  'prepare'=>['Pripremi poziv','Prepare invitation','Pripravi povabilo','Einladung vorbereiten','Preparar invitación'],
  'options'=>['Dodaj video i osobni uvod','Add a video and personal introduction','Dodaj video in osebni uvod','Video und persönliche Einleitung hinzufügen','Añadir vídeo y presentación personal'],
  'edit'=>['Uredi video i osobni uvod','Edit video and personal introduction','Uredi video in osebni uvod','Video und persönliche Einleitung bearbeiten','Editar vídeo y presentación personal'],
  'help'=>['Po želji obogati prijavnu stranicu. Tekst tvoje poruke ostaje sačuvan.','Personalise the registration page. Your message text will be kept.','Prilagodi prijavno stran. Besedilo tvojega sporočila ostane shranjeno.','Gestalte die Anmeldeseite persönlich. Dein Nachrichtentext bleibt erhalten.','Personaliza la página de inscripción. Se conservará el texto de tu mensaje.'],
  'free'=>['S PRO paketom možeš dodati video, osobni uvod i odabrati kontakt. Pozivnicu bez tih dodataka možeš poslati odmah.','With PRO you can add a video, a personal introduction and choose a contact. You can send the basic invitation right away.','S paketom PRO lahko dodaš video, osebni uvod in izbereš stik. Osnovno povabilo lahko pošlješ takoj.','Mit PRO kannst du ein Video, eine persönliche Einleitung und einen Kontakt hinzufügen. Die einfache Einladung kannst du sofort senden.','Con PRO puedes añadir un vídeo, una presentación personal y elegir un contacto. Puedes enviar la invitación básica ahora.'],
  'apply'=>['Primijeni na pozivnicu','Apply to invitation','Uporabi za povabilo','Auf Einladung anwenden','Aplicar a la invitación'],
  'applied'=>['Pozivnica je ažurirana. Tvoj tekst i nova poveznica spremni su za slanje.','Invitation updated. Your text and the new link are ready to share.','Povabilo je posodobljeno. Tvoje besedilo in nova povezava sta pripravljena za pošiljanje.','Einladung aktualisiert. Dein Text und der neue Link sind bereit zum Teilen.','Invitación actualizada. Tu texto y el nuevo enlace están listos para compartir.'],
  'manage'=>['Evidencija ove pozivnice','Invitation records','Evidenca tega povabila','Einladungsübersicht','Registro de esta invitación'],
  'unavailable'=>['Ova pozivnica više nije dostupna za slanje. Odaberi aktualni termin i pripremi novi poziv.','This invitation is no longer available to share. Choose an upcoming event and prepare a new invitation.','Tega povabila ni več mogoče poslati. Izberi prihodnji termin in pripravi novo povabilo.','Diese Einladung kann nicht mehr geteilt werden. Wähle einen aktuellen Termin und erstelle eine neue Einladung.','Esta invitación ya no está disponible. Elige un próximo evento y prepara una nueva invitación.'],
  'own_prepare'=>['Primijeni dodatke da pripremiš prijavnu stranicu svojeg webinara.','Apply your options to prepare the registration page for your webinar.','Uporabi dodatke in pripravi prijavno stran svojega webinarja.','Wende die Optionen an, um die Anmeldeseite für dein Webinar vorzubereiten.','Aplica tus opciones para preparar la página de inscripción de tu webinar.'],
  'upgrade'=>['Pogledaj PRO mogućnosti','Explore PRO features','Poglej možnosti PRO','PRO Funktionen ansehen','Ver funciones PRO'],
 ];
 $sr=[
 'prepare'=>'Pripremi pozivnicu',
 'options'=>'Dodaj video i lični uvod',
 'edit'=>'Uredi video i lični uvod',
 'help'=>'Po želji dopuni stranicu za prijavu. Tekst tvoje poruke ostaje sačuvan.',
 'free'=>'Uz PRO paket možeš da dodaš video, lični uvod i izabereš kontakt. Pozivnicu bez tih dodataka možeš odmah da pošalješ.',
 'apply'=>'Primeni na pozivnici',
 'applied'=>'Pozivnica je ažurirana. Tvoj tekst i novi link spremni su za slanje.',
 'manage'=>'Evidencija ove pozivnice',
 'unavailable'=>'Ova pozivnica više nije dostupna za slanje. Izaberi predstojeći termin i pripremi novi poziv.',
 'own_prepare'=>'Primeni dodatke da pripremiš stranicu za prijavu na svoj vebinar.',
 'upgrade'=>'Pogledaj PRO mogućnosti',
 ];
 $index=['hr'=>0,'en'=>1,'sl'=>2,'de'=>3,'es'=>4][fcc_locale()]??1;
 if(in_array(fcc_locale(),['sq','cnr','fr','es','sv','hu','pl','ro'],true))return fcc_t($copy[$key][0]??$key);
 return fcc_locale()==='sr'?($sr[$key]??$key):($copy[$key][$index]??$key);
}
function fcc_invite_path(array $s,?array $a=null): string {
 return 'partner/share?type='.fcc_event_channel($s).'&session_id='.(int)$s['id'].($a?'&invite_id='.(int)$a['id']:'');
}
function fcc_invite_data(int $uid,array $data): array {
 $channel=$data['share_type'];$w=$data['webinar'];$pro=fcc_webinar_pro($uid);$a=null;
 $sid=(int)($_GET['session_id']??0);$id=(int)($_GET['invite_id']??0);
 if($id){$a=fcc_webinar_attempt($uid,$id);$sid=(int)$a['session_id'];}
 $personal=$channel==='webinar'&&$pro?fcc_webinar_personal_list($uid):[];
 $w['events']=array_merge($w['events']??[],$personal);
 usort($w['events'],static fn($x,$y)=>strcmp($x['starts_at_utc'],$y['starts_at_utc']));
 if($sid){
  $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[$sid]);
  if(!$s||(!empty($s['owner_user_id'])?(int)$s['owner_user_id']!==$uid:!fcc_event_access($uid,$s)['allowed']))throw new InvalidArgumentException('Unavailable invitation');
  fcc_mw_remember($uid,$s);
  if(fcc_event_channel($s)!==$channel)redirect(fcc_invite_path($s,$a));
  if(!empty($s['owner_user_id'])) {
   if(!$pro&&!$a)throw new InvalidArgumentException('Unavailable invitation');
   $w['session']=$s;$w['url']='';
   $w['starts_at']=(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($s['timezone']));
   if(!$a)$a=fcc_partner_one('SELECT * FROM fcc_webinar_invite_attempts WHERE user_id=? AND session_id=? AND revoked_at IS NULL ORDER BY id DESC LIMIT 1',[$uid,$sid]);
  }
 }
 if($a){
  $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$a['session_id']]);
  $w['session']=$s;$w['starts_at']=(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone($s['timezone']));
  $w['url']=fcc_webinar_url($a);
 }
 $draft=null;$handoff=(string)($_GET['draft']??'');
 if(preg_match('/^[a-f0-9]{32}$/D',$handoff)) {
  $stored=$_SESSION['fcc_invite_drafts'][$handoff]??null;
  if($stored&&(int)$stored['uid']===$uid&&(int)$stored['attempt']===(int)($a['id']??0)&&$stored['expires']>=time())$draft=$stored['text'];
  unset($_SESSION['fcc_invite_drafts'][$handoff]);
 }
 $available=!$a||($pro&&fcc_webinar_can_register(fcc_webinar_public_attempt(fcc_webinar_unseal($a['token_sealed']))));
 $data['webinar']=$w;
 $data['invite']=['pro'=>$pro,'attempt'=>$a,'available'=>$available,'draft'=>$draft,'personal_ready'=>$channel==='webinar'&&fcc_webinar_personal_ready(),
  'contacts'=>fcc_partner_rows("SELECT id,name FROM fcc_partner_contacts WHERE user_id=? AND contact_permission<>'do_not_contact' AND archived_at IS NULL ORDER BY name LIMIT 200",[$uid])];
 return $data;
}
function fcc_invite_customize(int $uid,array $input): string {
 fcc_webinar_require_pro($uid);
 $sid=(int)($input['session_id']??0);$old=null;
 if(!$sid||(int)($input['session_revision']??0)<1)throw new InvalidArgumentException(fcc_t('Odaberi webinar za svoju pozivnicu.'));
 if(!empty($input['source_attempt'])){$old=fcc_webinar_attempt($uid,(int)$input['source_attempt']);if((int)$old['session_id']!==$sid)throw new InvalidArgumentException(fcc_t('Termin je promijenjen. Osvježi prikaz.'));}
 // Only expose the existing preparation service's supported fields.
 $allowed=array_intersect_key($input,array_flip(['request_key','session_id','session_revision','invite_channel','video_choice','video_url','intro','contact_id','step_id']));
 $a=fcc_webinar_prepare($uid,$allowed);
 $s=fcc_partner_one('SELECT * FROM fcc_webinar_sessions WHERE id=?',[(int)$a['session_id']]);
 $target=fcc_invite_path($s,$a);
 $text=trim((string)($input['message_draft']??''));
 if($text!=='') {
  $text=mb_substr($text,0,4000);$new=fcc_webinar_url($a);
  $cards=array_values(array_filter(fcc_partner_cards($uid),static fn($c)=>(int)$c['is_enabled']===1));
  $source=$old?fcc_webinar_url($old):url('webinar-event/'.$sid.'?ref='.rawurlencode((string)($cards[0]['url']??'')));
  $source=fcc_webinar_standard_share_destination($source)['url'];
  $text=str_replace($source,$new,$text);
  if(!str_contains($text,$new))$text.="\n\n".$new;
  foreach(($_SESSION['fcc_invite_drafts']??[]) as $key=>$d)if($d['expires']<time())unset($_SESSION['fcc_invite_drafts'][$key]);
  $_SESSION['fcc_invite_drafts']=array_slice($_SESSION['fcc_invite_drafts']??[],-9,null,true);
  $handoff=bin2hex(random_bytes(16));
  $_SESSION['fcc_invite_drafts'][$handoff]=['uid'=>$uid,'attempt'=>(int)$a['id'],'text'=>$text,'expires'=>time()+600];
  $target.='&draft='.$handoff;
 }
 return $target.'&saved=invite';
}
