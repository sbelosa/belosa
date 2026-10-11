<?php
defined('ALTUMCODE') || die();

/** Resolve the inviter from the stored attribution, never from submitted guest fields. */
function fcc_webinar_mail_owner(array $r): array {
 $u=fcc_partner_one('SELECT u.user_id,u.name,u.email,u.preferences,u.language,u.status,a.card_id FROM users u JOIN fcc_webinar_invite_attempts a ON a.user_id=u.user_id WHERE a.id=? AND u.user_id=?',[(int)$r['attempt_id'],(int)$r['user_id']]);
 if(!$u)return ['name'=>'','email'=>'','phone'=>'','profile_url'=>'','active'=>false];
 $phone=fc_get_user_default_whatsapp_phone((object)$u);$profile='';
 foreach(fcc_partner_cards((int)$u['user_id']) as $card)if((int)$card['link_id']===(int)$u['card_id']&&(int)$card['is_enabled']===1){$profile=$card['public_url'];break;}
 return ['language'=>fcc_locale($u['language']),'name'=>fcc_partner_text($u['name'],120),'email'=>filter_var($u['email'],FILTER_VALIDATE_EMAIL)?$u['email']:'','phone'=>$phone?'+' . $phone:'','profile_url'=>$profile,'active'=>(int)$u['status']===1];
}

function fcc_webinar_mail_payload(array $r,array $s,string $kind): array {
 $owner=fcc_webinar_mail_owner($r);$language=fcc_mw_locale($s,$kind==='owner_confirmation'?($owner['language']??'hr'):($r['language']??'hr'));
 if(fcc_event_offline($s))return fcc_seminar_mail($r,$s,$kind,$owner,$language);
 if(fcc_new_locale($language))return fcc_webinar_mail_payload_localized($r,$s,$kind,$owner,$language);
 if($language==='sr')return fcc_webinar_mail_payload_sr($r,$s,$kind,$owner);
 if($language==='de')return fcc_webinar_mail_payload_de($r,$s,$kind,$owner);
 if($language==='en')return fcc_webinar_mail_payload_en($r,$s,$kind,$owner);
 if($language==='sl')return fcc_webinar_mail_payload_sl($r,$s,$kind,$owner);
 $date=fcc_webinar_label_time($s,$language);
 if($kind==='owner_confirmation') {
  $contact=SITE_URL.'partner/contacts?id='.(int)$r['contact_id'];
  $text='Bok '.$owner['name']."!\n\nPreko tvoje pozivnice stigla je nova prijava.\n\n".$s['title']."\n".$date."\n\nIme: ".$r['name']."\nEmail: ".$r['email']."\nTelefon / WhatsApp: ".$r['phone']."\nPozivnica: ".$r['attempt_id']."\n";
  $text.="\n".fcc_wl_t(fcc_webinar_may_contact_registration($r)?'registration_contact':'no_permission',[],$language)."\n\nOtvori kontakt i prijavu: ".$contact."\n";
  if(in_array($s['status'],['cancelled','postponed'],true))$text.="\nAktualni status termina: ".($s['status']==='cancelled'?'otkazan':'odgođen').".\n";
  return ['subject'=>'Nova prijava na webinar: '.$s['title'],'text'=>$text,'to'=>$owner['email'],'reply_to'=>null,'recipient_kind'=>'owner','contact_url'=>$contact,'is_local'=>fcc_webinar_local()];
 }
 $manage=fcc_webinar_manage_url($r);$verify=SITE_URL.'webinar-invite/verify/'.fcc_webinar_unseal($r['verification_sealed']);
 $subject=match($kind){'confirmation'=>'Tvoja prijava na webinar','reminder24'=>'Webinar je sutra','reminder1'=>'Webinar počinje za jedan sat','cancelled'=>'Webinar je otkazan','postponed'=>'Webinar je odgođen',default=>'Promjena termina webinara'};
 $text='Bok '.$r['name']."!\n\n";
 if($kind==='confirmation')$text.="Tvoja prijava je zaprimljena.\n\n";
 $text.=$s['title']."\n";
 $text.=match($kind){'postponed'=>'Webinar je odgođen. Novi datum još nije potvrđen.','cancelled'=>'Ovaj termin webinara je otkazan.',default=>$date};
 $text.="\n\n";
 if(!in_array($kind,['cancelled','postponed'],true)) {
  $text.="Pristup webinaru: ".$s['access_url']."\nPridruži nam se u navedenom terminu.\n\n";
  $text.="Spremi u kalendar: ".$manage."?download=calendar\nGoogle kalendar: ".fcc_webinar_google_calendar(array_merge($r,$s))."\n\n";
 }
 $text.="Detalji i upravljanje prijavom: ".$manage."\n";
 if(!in_array($kind,['cancelled','postponed'],true)&&$r['reminder_opt_in']&&!$r['email_verified_at'])$text.="\nZa odabrane podsjetnike potvrdi svoju email adresu: ".$verify."\nPodsjetnike šaljemo dan prije i sat prije početka, ako do termina preostaje dovoljno vremena.\n";
 if($owner['name']!=='')$text.="\nPozvao/la te: ".$owner['name']."\n";
 if($owner['email']!=='')$text.='Email suradnika: '.$owner['email']."\n";
 if($owner['phone']!=='')$text.='Telefon / WhatsApp suradnika: '.$owner['phone']."\nWhatsApp suradnika: https://wa.me/".substr($owner['phone'],1)."\n";
 if($owner['profile_url']!=='')$text.='Kartica suradnika: '.$owner['profile_url']."\n";
 $text.="\nPrivatnost: ".fcc_legal_url('privacy')."\n";
 // Guest capabilities never appear in the separate owner notification.
 return ['subject'=>$subject,'text'=>$text,'to'=>$r['email'],'reply_to'=>$owner['email']?:null,'recipient_kind'=>'guest','manage_url'=>$manage,'verify_url'=>$verify,'is_local'=>fcc_webinar_local()];
}

/** The same escaped content is used by the local preview and the configured mail transport. */
function fcc_webinar_mail_html(array $payload): string {
 $h=static fn($s)=>htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$lines=[];
 $labels=['Pristup webinaru'=>'Otvori webinar','Spremi u kalendar'=>'Spremi u kalendar','Google kalendar'=>'Google kalendar','Detalji i upravljanje prijavom'=>'Detalji i upravljanje prijavom','Za odabrane podsjetnike potvrdi svoju email adresu'=>'Potvrdi email adresu za podsjetnike','Otvori kontakt i prijavu'=>'Otvori prijavljeni kontakt','Kartica suradnika'=>'Kartica suradnika','WhatsApp suradnika'=>'Javi se suradniku na WhatsApp','Privatnost'=>'Politika privatnosti'];
 if(($payload['language']??'hr')==='sl')$labels=['Dostop do webinarja'=>'Odpri webinar','Shrani v koledar'=>'Shrani v koledar','Google koledar'=>'Google koledar','Podrobnosti in upravljanje prijave'=>'Upravljaj prijavo','Za izbrane opomnike potrdi svoj e-poštni naslov'=>'Potrdi e-poštni naslov','Odpri stik in prijavo'=>'Odpri prijavljeni stik','Kartica sodelavca'=>'Kartica sodelavca','WhatsApp sodelavca'=>'Piši sodelavcu na WhatsApp','Zasebnost'=>'Politika zasebnosti'];
 if(($payload['language']??'hr')==='en')$labels=['Webinar access'=>'Join the webinar','Save to calendar'=>'Save to calendar','Google Calendar'=>'Google Calendar','Registration details and management'=>'Manage your registration','Confirm your email for your chosen reminders'=>'Confirm your email','Open the contact and registration'=>'Open the registered contact','Your contact’s card'=>'View your contact’s card','Your contact’s WhatsApp'=>'Message your contact on WhatsApp','Privacy'=>'Privacy policy'];
 if(($payload['language']??'hr')==='de')$labels=['Zugang zum Webinar'=>'Am Webinar teilnehmen','Im Kalender speichern'=>'Im Kalender speichern','Google Kalender'=>'Google Kalender','Anmeldung ansehen und verwalten'=>'Anmeldung verwalten','Bestätige deine E-Mail-Adresse für die gewählten Erinnerungen'=>'E-Mail-Adresse bestätigen','Kontakt und Anmeldung öffnen'=>'Angemeldeten Kontakt öffnen','Karte deiner Kontaktperson'=>'Karte deiner Kontaktperson ansehen','WhatsApp deiner Kontaktperson'=>'Deiner Kontaktperson auf WhatsApp schreiben','Datenschutz'=>'Datenschutzerklärung'];
 if(($payload['language']??'hr')==='sr')$labels=['Pristup webinaru'=>'Pridruži se webinaru','Sačuvaj u kalendar'=>'Sačuvaj u kalendar','Google kalendar'=>'Google kalendar','Detalji i upravljanje prijavom'=>'Upravljaj prijavom','Potvrdi email adresu za izabrane podsetnike'=>'Potvrdi email adresu','Otvori kontakt i prijavu'=>'Otvori prijavljeni kontakt','Kartica saradnika'=>'Kartica saradnika','WhatsApp saradnika'=>'Javi se saradniku preko WhatsAppa','Privatnost'=>'Politika privatnosti'];
 if(fcc_new_locale($payload['language']??'hr')) {
  $labels=[];
  foreach(['Pristup webinaru'=>'Otvori webinar','Spremi u kalendar'=>'Spremi u kalendar','Google kalendar'=>'Google kalendar','Detalji i upravljanje prijavom'=>'Detalji i upravljanje prijavom','Potvrdi email adresu za podsjetnike'=>'Potvrdi email adresu za podsjetnike','Otvori kontakt i prijavu'=>'Otvori kontakt i prijavu','Kartica suradnika'=>'Kartica suradnika','WhatsApp suradnika'=>'Javi se suradniku na WhatsApp','Privatnost'=>'Politika privatnosti'] as $label=>$value)$labels[fcc_t($label,[],$payload['language'])]=fcc_t($value,[],$payload['language']);
 }
 foreach(explode("\n",$payload['text']) as $line) {
  if(preg_match('~^(.+): (https?://\S+)$~u',$line,$m)&&isset($labels[$m[1]])) {
   $primary=in_array($m[1],['Pristup webinaru','Otvori kontakt i prijavu','Dostop do webinarja','Odpri stik in prijavo','Webinar access','Open the contact and registration','Zugang zum Webinar','Kontakt und Anmeldung öffnen'],true);
   if(fcc_new_locale($payload['language']??'hr'))$primary=in_array($m[1],[fcc_t('Pristup webinaru',[],$payload['language']),fcc_t('Otvori kontakt i prijavu',[],$payload['language'])],true);
   $style=$primary?'display:inline-block;padding:12px 20px;margin:8px 0;background:#17634d;color:#ffffff;border-radius:10px;text-decoration:none;font-weight:600':'color:#17634d;text-decoration:underline';
   $lines[]='<a href="'.$h($m[2]).'" style="'.$style.'">'.$h($labels[$m[1]]).'</a>';
  }else $lines[]=$h($line);
 }
 return '<div style="color:#183b37;font-size:16px;line-height:1.6">'.implode("<br>\n",$lines).'</div>';
}

/** No global CC/BCC: verification and management links belong only to the guest. */
function fcc_webinar_send_mail(array $payload,?callable $sender=null): string {
 if(!filter_var($payload['to']??'',FILTER_VALIDATE_EMAIL))return 'failed';
 if($sender===null){
  if(!cache()){\Altum\Cache::initialize();\Altum\Plugin::initialize();}
  if(!\Altum\Language::$languages)\Altum\Language::initialize();
  $sender='send_mail';
 }
 try {$result=$sender($payload['to'],$payload['subject'],fcc_webinar_mail_html($payload),[
  'is_system_email'=>true,'is_broadcast'=>false,'language'=>fcc_language_name($payload['language']??'hr')??'Hrvatski','return_transport_result'=>true,
  'skip_default_copies'=>true,'text_content'=>$payload['text'],
 ],$payload['reply_to']??null);}catch(Throwable $e){return 'delivery_unknown';}
 if($result===true||(is_object($result)&&!empty($result->success)))return 'sent';
 // The core transport returns false when SMTP has no host, before delivery is attempted.
 if($result===false)return 'pending';
 if(is_object($result)&&($result->transport??'')==='brevo_api'&&empty($result->fallback_used)) {
  $code=(int)($result->status_code??0);
  if($code===429)return 'pending';
  if($code>=400&&$code<500)return 'failed';
 }
 // An ambiguous timeout/SMTP failure must not blindly send a second copy.
 return 'delivery_unknown';
}

function fcc_webinar_worker_enabled(): bool {
 return fcc_webinar_schema()&&getenv('FCC_WEBINAR_INVITES_ENABLED')!=='0'&&(fcc_webinar_local()||getenv('FCC_WEBINAR_INVITES_ENABLED')==='1');
}

/** Scoped immediate attempt; the durable worker handles any later retry. */
function fcc_webinar_dispatch_registration(array $result,int $uid): void {
 if(empty($result['new']))return;
 try {fcc_webinar_run_jobs(null,5,$uid,(int)$result['registration_id']);}
 catch(Throwable $e){error_log('Webinar confirmation remains queued for retry.');}
}


/** Recipient locale is stored with the guest registration; the owner uses their account locale. */
function fcc_webinar_mail_payload_sl(array $r,array $s,string $kind,array $owner): array {
 $date=fcc_webinar_label_time($s,'sl');
 if($kind==='owner_confirmation'){
  $contact=SITE_URL.'sl/partner/contacts?id='.(int)$r['contact_id'];
  $text='Živjo '.$owner['name']."!\n\nPrek tvojega povabila je prispela nova prijava.\n\n".$s['title']."\n".$date."\n\nIme: ".$r['name']."\nE-pošta: ".$r['email']."\nTelefon / WhatsApp: ".$r['phone']."\nPovabilo: ".$r['attempt_id']."\n";
  $text.="\n".fcc_wl_t(fcc_webinar_may_contact_registration($r)?'registration_contact':'no_permission',[],'sl')."\n\nOdpri stik in prijavo: ".$contact."\n";
  if(in_array($s['status'],['cancelled','postponed'],true))$text.="\nTrenutno stanje termina: ".($s['status']==='cancelled'?'odpovedan':'prestavljen').".\n";
  return ['language'=>'sl','subject'=>'Nova prijava na webinar: '.$s['title'],'text'=>$text,'to'=>$owner['email'],'reply_to'=>null,'recipient_kind'=>'owner','contact_url'=>$contact,'is_local'=>fcc_webinar_local()];
 }
 $manage=str_replace(SITE_URL,SITE_URL.'sl/',fcc_webinar_manage_url($r));$verify=SITE_URL.'sl/webinar-invite/verify/'.fcc_webinar_unseal($r['verification_sealed']);
 $subject=match($kind){'confirmation'=>'Tvoja prijava na webinar','reminder24'=>'Webinar je jutri','reminder1'=>'Webinar se začne čez eno uro','cancelled'=>'Webinar je odpovedan','postponed'=>'Webinar je prestavljen',default=>'Sprememba termina webinarja'};
 $text='Živjo '.$r['name']."!\n\n";
 if($kind==='confirmation')$text.="Tvoja prijava je prejeta.\n\n";
 $text.=$s['title']."\n";
 $text.=match($kind){'postponed'=>'Webinar je prestavljen. Nov datum še ni potrjen.','cancelled'=>'Ta termin webinarja je odpovedan.',default=>$date};$text.="\n\n";
 if(!in_array($kind,['cancelled','postponed'],true))$text.='Dostop do webinarja: '.$s['access_url']."\nPridruži se nam ob navedenem terminu.\n\nShrani v koledar: ".$manage."?download=calendar\nGoogle koledar: ".fcc_webinar_google_calendar(array_merge($r,$s))."\n\n";
 $text.='Podrobnosti in upravljanje prijave: '.$manage."\n";
 if(!in_array($kind,['cancelled','postponed'],true)&&$r['reminder_opt_in']&&!$r['email_verified_at'])$text.="\nZa izbrane opomnike potrdi svoj e-poštni naslov: ".$verify."\nOpomnike pošiljamo dan prej in eno uro pred začetkom, če je do termina še dovolj časa.\n";
 if($owner['name']!=='')$text.="\nPovabil/a te je: ".$owner['name']."\n";
 if($owner['email']!=='')$text.='E-pošta sodelavca: '.$owner['email']."\n";
 if($owner['phone']!=='')$text.='Telefon / WhatsApp sodelavca: '.$owner['phone']."\nWhatsApp sodelavca: https://wa.me/".substr($owner['phone'],1)."\n";
 if($owner['profile_url']!=='')$text.='Kartica sodelavca: '.$owner['profile_url']."\n";
 $text.="\nZasebnost: ".SITE_URL.'sl/legal/privacy'."\n";
 return ['language'=>'sl','subject'=>$subject,'text'=>$text,'to'=>$r['email'],'reply_to'=>$owner['email']?:null,'recipient_kind'=>'guest','manage_url'=>$manage,'verify_url'=>$verify,'is_local'=>fcc_webinar_local()];
}

/** English templates preserve personal names, event titles and access links verbatim. */
function fcc_webinar_mail_payload_en(array $r,array $s,string $kind,array $owner): array {
 $date=fcc_webinar_label_time($s,'en');
 if($kind==='owner_confirmation'){
  $contact=SITE_URL.'en/partner/contacts?id='.(int)$r['contact_id'];
  $text='Hi '.$owner['name']."!\n\nSomeone has registered through your invitation.\n\n".$s['title']."\n".$date."\n\nName: ".$r['name']."\nEmail: ".$r['email']."\nPhone / WhatsApp: ".$r['phone']."\nInvitation: ".$r['attempt_id']."\n";
  $text.="\n".fcc_wl_t(fcc_webinar_may_contact_registration($r)?'registration_contact':'no_permission',[],'en')."\n\nOpen the contact and registration: ".$contact."\n";
  if(in_array($s['status'],['cancelled','postponed'],true))$text.="\nCurrent event status: ".$s['status'].".\n";
  return ['language'=>'en','subject'=>'New webinar registration: '.$s['title'],'text'=>$text,'to'=>$owner['email'],'reply_to'=>null,'recipient_kind'=>'owner','contact_url'=>$contact,'is_local'=>fcc_webinar_local()];
 }
 $manage=str_replace(SITE_URL,SITE_URL.'en/',fcc_webinar_manage_url($r));$verify=SITE_URL.'en/webinar-invite/verify/'.fcc_webinar_unseal($r['verification_sealed']);
 $subject=match($kind){'confirmation'=>'Your webinar registration','reminder24'=>'Your webinar is tomorrow','reminder1'=>'Your webinar starts in one hour','cancelled'=>'Your webinar has been cancelled','postponed'=>'Your webinar has been postponed',default=>'Your webinar schedule has changed'};
 $text='Hi '.$r['name']."!\n\n";
 if($kind==='confirmation')$text.="Your registration has been received.\n\n";
 $text.=$s['title']."\n";
 $text.=match($kind){'postponed'=>'The webinar has been postponed. A new date has not yet been confirmed.','cancelled'=>'This webinar session has been cancelled.',default=>$date};$text.="\n\n";
 if(!in_array($kind,['cancelled','postponed'],true))$text.='Webinar access: '.$s['access_url']."\nJoin us at the scheduled time.\n\nSave to calendar: ".$manage."?download=calendar\nGoogle Calendar: ".fcc_webinar_google_calendar(array_merge($r,$s,['language'=>'en']))."\n\n";
 $text.='Registration details and management: '.$manage."\n";
 if(!in_array($kind,['cancelled','postponed'],true)&&$r['reminder_opt_in']&&!$r['email_verified_at'])$text.="\nConfirm your email for your chosen reminders: ".$verify."\nWe send reminders one day and one hour before the start, if there is enough time before the webinar.\n";
 if($owner['name']!=='')$text.="\nInvited by: ".$owner['name']."\n";
 if($owner['email']!=='')$text.='Your contact’s email: '.$owner['email']."\n";
 if($owner['phone']!=='')$text.='Your contact’s phone / WhatsApp: '.$owner['phone']."\nYour contact’s WhatsApp: https://wa.me/".substr($owner['phone'],1)."\n";
 if($owner['profile_url']!=='')$text.='Your contact’s card: '.$owner['profile_url']."\n";
 $text.="\nPrivacy: ".SITE_URL.'en/legal/privacy'."\n";
 return ['language'=>'en','subject'=>$subject,'text'=>$text,'to'=>$r['email'],'reply_to'=>$owner['email']?:null,'recipient_kind'=>'guest','manage_url'=>$manage,'verify_url'=>$verify,'is_local'=>fcc_webinar_local()];
}

/** German copy preserves all personal values and capability URLs. */
function fcc_webinar_mail_payload_de(array $r,array $s,string $kind,array $owner): array {
 $date=fcc_webinar_label_time($s,'de');
 if($kind==='owner_confirmation'){
  $contact=SITE_URL.'de/partner/contacts?id='.(int)$r['contact_id'];
  $text='Hallo '.$owner['name']."!\n\nÜber deine Einladung ist eine neue Anmeldung eingegangen.\n\n".$s['title']."\n".$date."\n\nName: ".$r['name']."\nE-Mail: ".$r['email']."\nTelefon / WhatsApp: ".$r['phone']."\nEinladung: ".$r['attempt_id']."\n";
  $text.="\n".fcc_wl_t(fcc_webinar_may_contact_registration($r)?'registration_contact':'no_permission',[],'de')."\n\nKontakt und Anmeldung öffnen: ".$contact."\n";
  if(in_array($s['status'],['cancelled','postponed'],true))$text.="\nAktueller Terminstatus: ".($s['status']==='cancelled'?'abgesagt':'verschoben').".\n";
  return ['language'=>'de','subject'=>'Neue Anmeldung zum Webinar: '.$s['title'],'text'=>$text,'to'=>$owner['email'],'reply_to'=>null,'recipient_kind'=>'owner','contact_url'=>$contact,'is_local'=>fcc_webinar_local()];
 }
 $manage=str_replace(SITE_URL,SITE_URL.'de/',fcc_webinar_manage_url($r));$verify=SITE_URL.'de/webinar-invite/verify/'.fcc_webinar_unseal($r['verification_sealed']);
 $subject=match($kind){'confirmation'=>'Deine Anmeldung zum Webinar','reminder24'=>'Dein Webinar findet morgen statt','reminder1'=>'Dein Webinar beginnt in einer Stunde','cancelled'=>'Dein Webinar wurde abgesagt','postponed'=>'Dein Webinar wurde verschoben',default=>'Der Termin deines Webinars hat sich geändert'};
 $text='Hallo '.$r['name']."!\n\n";
 if($kind==='confirmation')$text.="Deine Anmeldung ist eingegangen.\n\n";
 $text.=$s['title']."\n";
 $text.=match($kind){'postponed'=>'Das Webinar wurde verschoben. Ein neuer Termin steht noch nicht fest.','cancelled'=>'Dieser Termin wurde abgesagt.',default=>$date};$text.="\n\n";
 if(!in_array($kind,['cancelled','postponed'],true))$text.='Zugang zum Webinar: '.$s['access_url']."\nSei zum angegebenen Termin dabei.\n\nIm Kalender speichern: ".$manage."?download=calendar\nGoogle Kalender: ".fcc_webinar_google_calendar(array_merge($r,$s,['language'=>'de']))."\n\n";
 $text.='Anmeldung ansehen und verwalten: '.$manage."\n";
 if(!in_array($kind,['cancelled','postponed'],true)&&$r['reminder_opt_in']&&!$r['email_verified_at'])$text.="\nBestätige deine E-Mail-Adresse für die gewählten Erinnerungen: ".$verify."\nWir erinnern dich einen Tag und eine Stunde vor Beginn, sofern bis zum Webinar noch ausreichend Zeit bleibt.\n";
 if($owner['name']!=='')$text.="\nEingeladen von: ".$owner['name']."\n";
 if($owner['email']!=='')$text.='E-Mail deiner Kontaktperson: '.$owner['email']."\n";
 if($owner['phone']!=='')$text.='Telefon / WhatsApp deiner Kontaktperson: '.$owner['phone']."\nWhatsApp deiner Kontaktperson: https://wa.me/".substr($owner['phone'],1)."\n";
 if($owner['profile_url']!=='')$text.='Karte deiner Kontaktperson: '.$owner['profile_url']."\n";
 $text.="\nDatenschutz: ".SITE_URL.'de/legal/privacy'."\n";
 return ['language'=>'de','subject'=>$subject,'text'=>$text,'to'=>$r['email'],'reply_to'=>$owner['email']?:null,'recipient_kind'=>'guest','manage_url'=>$manage,'verify_url'=>$verify,'is_local'=>fcc_webinar_local()];
}

/** Serbian Latin copy preserves personal data and capability URLs. */
function fcc_webinar_mail_payload_sr(array $r,array $s,string $kind,array $owner): array {
 $date=fcc_webinar_label_time($s,'sr');
 if($kind==='owner_confirmation'){
  $contact=SITE_URL.'sr/partner/contacts?id='.(int)$r['contact_id'];
  $text='Zdravo '.$owner['name']."!\n\nStigla je nova prijava preko tvoje pozivnice.\n\n".$s['title']."\n".$date."\n\nIme: ".$r['name']."\nEmail: ".$r['email']."\nTelefon / WhatsApp: ".$r['phone']."\nPozivnica: ".$r['attempt_id']."\n";
  $text.="\n".fcc_wl_t(fcc_webinar_may_contact_registration($r)?'registration_contact':'no_permission',[],'sr')."\n\nOtvori kontakt i prijavu: ".$contact."\n";
  if(in_array($s['status'],['cancelled','postponed'],true))$text.="\nTrenutni status termina: ".($s['status']==='cancelled'?'otkazan':'odložen').".\n";
  return ['language'=>'sr','subject'=>'Nova prijava na webinar: '.$s['title'],'text'=>$text,'to'=>$owner['email'],'reply_to'=>null,'recipient_kind'=>'owner','contact_url'=>$contact,'is_local'=>fcc_webinar_local()];
 }
 $manage=str_replace(SITE_URL,SITE_URL.'sr/',fcc_webinar_manage_url($r));$verify=SITE_URL.'sr/webinar-invite/verify/'.fcc_webinar_unseal($r['verification_sealed']);
 $subject=match($kind){'confirmation'=>'Tvoja prijava na webinar','reminder24'=>'Webinar je sutra','reminder1'=>'Webinar počinje za jedan sat','cancelled'=>'Webinar je otkazan','postponed'=>'Webinar je odložen',default=>'Termin webinara je promenjen'};
 $text='Zdravo '.$r['name']."!\n\n";
 if($kind==='confirmation')$text.="Tvoja prijava je primljena.\n\n";
 $text.=$s['title']."\n";
 $text.=match($kind){'postponed'=>'Webinar je odložen. Novi datum još nije potvrđen.','cancelled'=>'Ovaj termin webinara je otkazan.',default=>$date};$text.="\n\n";
 if(!in_array($kind,['cancelled','postponed'],true))$text.='Pristup webinaru: '.$s['access_url']."\nPridruži nam se u navedenom terminu.\n\nSačuvaj u kalendar: ".$manage."?download=calendar\nGoogle kalendar: ".fcc_webinar_google_calendar(array_merge($r,$s,['language'=>'sr']))."\n\n";
 $text.='Detalji i upravljanje prijavom: '.$manage."\n";
 if(!in_array($kind,['cancelled','postponed'],true)&&$r['reminder_opt_in']&&!$r['email_verified_at'])$text.="\nPotvrdi email adresu za izabrane podsetnike: ".$verify."\nPodsetnike šaljemo dan pre i sat pre početka, ako do webinara preostaje dovoljno vremena.\n";
 if($owner['name']!=='')$text.="\nPozvao/la te: ".$owner['name']."\n";
 if($owner['email']!=='')$text.='Email saradnika: '.$owner['email']."\n";
 if($owner['phone']!=='')$text.='Telefon / WhatsApp saradnika: '.$owner['phone']."\nWhatsApp saradnika: https://wa.me/".substr($owner['phone'],1)."\n";
 if($owner['profile_url']!=='')$text.='Kartica saradnika: '.$owner['profile_url']."\n";
 $text.="\nPrivatnost: ".SITE_URL.'sr/legal/privacy'."\n";
 return ['language'=>'sr','subject'=>$subject,'text'=>$text,'to'=>$r['email'],'reply_to'=>$owner['email']?:null,'recipient_kind'=>'guest','manage_url'=>$manage,'verify_url'=>$verify,'is_local'=>fcc_webinar_local()];
}


/** Translate fixed mail copy before interpolation; never translate guest data or access tokens. */
function fcc_webinar_mail_payload_localized(array $r,array $s,string $kind,array $owner,string $language): array {
 $t=static fn(string $source)=>fcc_t($source,[],$language);
 $date=fcc_webinar_label_time($s,$language);
 $name=$kind==='owner_confirmation'?$owner['name']:$r['name'];
 $text=fcc_t('Bok {name}!',['{name}'=>$name],$language)."\n\n";
 $eventTitle=fcc_webinar_title($s,$language);
 if($kind==='owner_confirmation') {
  $contact=SITE_URL.$language.'/partner/contacts?id='.(int)$r['contact_id'];
  $text.=$t('Preko tvoje pozivnice stigla je nova prijava.')."\n\n".$eventTitle."\n".$date."\n\n";
  foreach(['Ime'=>$r['name'],'Email'=>$r['email'],'Telefon / WhatsApp'=>$r['phone'],'Pozivnica'=>$r['attempt_id']] as $label=>$value)$text.=$t($label).': '.$value."\n";
  $text.="\n".fcc_wl_t(fcc_webinar_may_contact_registration($r)?'registration_contact':'no_permission',[],$language)."\n\n".$t('Otvori kontakt i prijavu').': '.$contact."\n";
  if(in_array($s['status'],['cancelled','postponed'],true))$text.=$t('Aktualni status termina').': '.$t($s['status']==='cancelled'?'Otkazano':'Odgođeno')."\n";
  return ['language'=>$language,'subject'=>$t('Nova prijava na webinar').': '.$eventTitle,'text'=>$text,'to'=>$owner['email'],'reply_to'=>null,'recipient_kind'=>'owner','contact_url'=>$contact,'is_local'=>fcc_webinar_local()];
 }
 $manage=str_replace(SITE_URL,SITE_URL.$language.'/',fcc_webinar_manage_url($r));
 $verify=SITE_URL.$language.'/webinar-invite/verify/'.fcc_webinar_unseal($r['verification_sealed']);
 $subject=$t(match($kind){'confirmation'=>'Tvoja prijava na webinar','reminder24'=>'Webinar je sutra','reminder1'=>'Webinar počinje za jedan sat','cancelled'=>'Webinar je otkazan','postponed'=>'Webinar je odgođen',default=>'Promjena termina webinara'});
 if($kind==='confirmation')$text.=$t('Tvoja prijava je zaprimljena.')."\n\n";
 $text.=$eventTitle."\n".match($kind){'postponed'=>$t('Webinar je odgođen. Novi datum još nije potvrđen.'),'cancelled'=>$t('Ovaj termin webinara je otkazan.'),default=>$date}."\n\n";
 if(!in_array($kind,['cancelled','postponed'],true)) {
  $text.=$t('Pristup webinaru').': '.$s['access_url']."\n".$t('Pridruži nam se u navedenom terminu.')."\n\n";
  $text.=$t('Spremi u kalendar').': '.$manage."?download=calendar\n".$t('Google kalendar').': '.fcc_webinar_google_calendar(array_merge($r,$s,['language'=>$language]))."\n\n";
 }
 $text.=$t('Detalji i upravljanje prijavom').': '.$manage."\n";
 if(!in_array($kind,['cancelled','postponed'],true)&&$r['reminder_opt_in']&&!$r['email_verified_at'])$text.="\n".$t('Potvrdi email adresu za podsjetnike').': '.$verify."\n".$t('Podsjetnike šaljemo dan prije i sat prije početka, ako do termina preostaje dovoljno vremena.')."\n";
 foreach(['Pozvao/la te'=>$owner['name'],'Email suradnika'=>$owner['email'],'Telefon / WhatsApp suradnika'=>$owner['phone'],'Kartica suradnika'=>$owner['profile_url']] as $label=>$value)if($value!=='')$text.=$t($label).': '.$value."\n";
 if($owner['phone']!=='')$text.=$t('WhatsApp suradnika').': https://wa.me/'.substr($owner['phone'],1)."\n";
 $text.="\n".$t('Privatnost').': '.SITE_URL.$language.'/legal/privacy';
 return ['language'=>$language,'subject'=>$subject,'text'=>$text,'to'=>$r['email'],'reply_to'=>$owner['email']?:null,'recipient_kind'=>'guest','manage_url'=>$manage,'verify_url'=>$verify,'is_local'=>fcc_webinar_local()];
}
