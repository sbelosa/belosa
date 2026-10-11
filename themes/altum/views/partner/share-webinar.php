<?php defined('ALTUMCODE') || die();
$hr=\Altum\Language::$code==='hr';
$h='fcc_partner_h';$eventChannel=($data->share_type??'webinar')==='events';$channel=$eventChannel?'events':'webinar'; $webinar=$data->webinar; $date=$webinar['starts_at'];
$invite=$data->invite??null;$attempt=$invite['attempt']??null;
$inviteSourceUrl=$webinar['url']??'';
$phone='';
if(empty($_GET['new'])&&$attempt&&!empty($attempt['recipient_contact_id'])) {
 try{$recipient=fcc_partner_contact((int)$this->user->user_id,(int)$attempt['recipient_contact_id']);if($recipient['contact_permission']!=='do_not_contact')$phone=preg_replace('/\D/','',fcc_partner_contact_phone($recipient['phone'],true));}catch(InvalidArgumentException $e){}
}
$eventCheck=!empty($webinar['session'])?url('partner/webinars/'.$webinar['session']['id'].'?check=1&revision='.$webinar['session']['revision'].($attempt?'&attempt='.$attempt['id']:'')):'';
if($attempt&&!empty($webinar['session']['owner_user_id']))$eventCheck=url('partner-webinar?view=check&session_id='.$webinar['session']['id'].'&revision='.$webinar['session']['revision'].'&attempt='.$attempt['id']);
$shareDestination=fcc_webinar_standard_share_destination($inviteSourceUrl);
$webinar['url']=$shareDestination['url'];
if(empty(fcc_event_definition($webinar['session']??[])['managed'])&&in_array(fcc_locale(),['de','sr','sq','cnr','fr','es','sv','hu','pl','ro'],true)) {
 $germanInvite=static fn(string $target)=>preg_replace('~(/)(?:(?:hr|sl|en|de|sr|sq|cnr|fr|es|sv|hu|pl|ro)/)*(webinar-event/)~', '$1'.fcc_locale().'/$2', $target, 1);
 $webinar['url']=$germanInvite($webinar['url']);
 $inviteSourceUrl=$germanInvite($inviteSourceUrl);
}
$copy=$hr?[
 'eyebrow'=>fcc_t('PODIJELI PRILIKU'),'title'=>fcc_t('Pozovi na webinar.'),'intro'=>fcc_t('Jedan poziv može otvoriti dobar razgovor.'),
 'event'=>fcc_t('Upoznaj Forever posao'),'online'=>fcc_t('ONLINE SUSRET'),'day'=>'Nedjelja','zone'=>fcc_t('Po zagrebačkom vremenu'),'open'=>fcc_t('Pogledaj prijavnu stranicu'),
 'message'=>fcc_t('Tvoja poruka'),'edit'=>fcc_t('Slobodno je prilagodi osobi kojoj se javljaš.'),
 'link'=>fcc_t('Tvoja poveznica za poziv'),'link_hint'=>fcc_t('Poveznica vodi na prijavu za odabrani webinar.'),
 'whatsapp'=>fcc_t('Otvori WhatsApp'),'share'=>fcc_t('Podijeli poziv'),'copied'=>fcc_t('Poziv je kopiran. Zalijepi ga u svoju poruku.'),
 'copy_error'=>fcc_t('Kopiranje nije uspjelo. Označi i kopiraj pripremljeni poziv ispod.'),
 'missing_title'=>fcc_t('Najprije pripremi svoju FCC karticu.'),'missing'=>fcc_t('Tako će osoba koju pozoveš imati tvoju poveznicu i moći ti se javiti.'),
 'card'=>fcc_t('Otvori moje kartice'),'unscheduled'=>fcc_t('Novi termin uskoro'),'unscheduled_help'=>fcc_t('Kad novi termin bude potvrđen, ovdje će te čekati pozivnica.'),
]:[
 'eyebrow'=>fcc_t('SHARE AN OPPORTUNITY'),'title'=>fcc_t('Invite someone along.'),'intro'=>fcc_t('One invitation can start a good conversation.'),
 'event'=>fcc_t('Discover the Forever business'),'online'=>fcc_t('ONLINE EVENT'),'day'=>'Sunday','zone'=>fcc_t('Zagreb time'),'open'=>fcc_t('View registration page'),
 'message'=>'Your message','edit'=>fcc_t('Make it your own before sharing.'),
 'link'=>fcc_t('Your invitation link'),'link_hint'=>fcc_t('The link opens registration for the selected webinar.'),
 'whatsapp'=>fcc_t('Open WhatsApp'),'share'=>fcc_t('Share invitation'),'copied'=>fcc_t('Invitation copied. Paste it into your message.'),
 'copy_error'=>fcc_t('Could not copy. Select and copy the prepared invitation below.'),
 'missing_title'=>fcc_t('Set up your FCC card first.'),'missing'=>fcc_t('Your guest will then have your personal link and a way to contact you.'),
 'card'=>fcc_t('Open my cards'),'unscheduled'=>'New date coming soon','unscheduled_help'=>fcc_t('Your invitation will be ready here when the new date is confirmed.'),
];
$message=$date?($hr
 ?fcc_t('Bok! U nedjelju, ').$date->format('j. n.').', u '.$date->format('H:i').fcc_t(' po zagrebačkom vremenu je online predstavljanje Forever posla. Ako razmišljaš o dodatnom poslu, ovo je prilika da vidiš kako izgleda. Želiš se pridružiti?')
 :fcc_t('Hi! There is an online introduction to the Forever business on Sunday, ').$date->format('j F').', at '.$date->format('H:i').fcc_t(' Zagreb time. If you are considering an extra source of work, this is a chance to see what it involves. Would you like to join?')):'';
if(!empty($webinar['session'])){
 $copy['event']=$webinar['session']['title'];
 if(fcc_event_definition($webinar['session'])['mode']==='in_person')$copy['online']=$hr?fcc_t('SUSRET UŽIVO'):fcc_t('IN PERSON EVENT');
 $copy['day']=$date?($hr?['','Ponedjeljak','Utorak','Srijeda',fcc_t('Četvrtak'),'Petak','Subota','Nedjelja'][(int)$date->format('N')]:$date->format('l')):'';
 $message=$date?($hr?fcc_t('Bok! Pozivam te na ').$copy['event'].', '.$date->format('d. m. Y.').', u '.$date->format('H:i').fcc_t(' po zagrebačkom vremenu. Želiš se pridružiti?'):fcc_t('Hi! Join us for ').$copy['event'].' on '.$date->format('j F Y').' at '.$date->format('H:i').fcc_t(' Zagreb time. Would you like to join?')):'';
}
if(fcc_locale()==='sl') {
 $copy['message']='Tvoje sporočilo';
 $copy['day']=$date?['','Ponedeljek','Torek','Sreda','Četrtek','Petek','Sobota','Nedelja'][(int)$date->format('N')]:'';
 $copy['event']=!empty($webinar['session'])?fcc_webinar_title($webinar['session']):fcc_t('Upoznaj Forever posao');
 $copy['unscheduled']='Nov termin bo objavljen kmalu';
 $message=$date?'Živjo! Vabim te na '.$copy['event'].', '.$date->format('d. m. Y.').' ob '.$date->format('H:i').' po zagrebškem času. Se nam želiš pridružiti?':'';
}
if(fcc_locale()==='de') {
 $copy['message']='Deine Nachricht';
 $copy['day']=$date?['','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag'][(int)$date->format('N')]:'';
 $copy['event']=!empty($webinar['session'])?fcc_webinar_title($webinar['session']):fcc_t('Upoznaj Forever posao');
 $copy['unscheduled']='Ein neuer Termin folgt in Kürze';
 $message=$date?'Hallo! Ich lade dich zu „'.$copy['event'].'“ am '.$date->format('d.m.Y').' um '.$date->format('H:i').' Uhr (Zeitzone Europe/Zagreb) ein. Möchtest du dabei sein?':'';
}
if(fcc_locale()==='sr') {
 $copy['message']='Tvoja poruka';
 $copy['day']=$date?['','Ponedeljak','Utorak','Sreda','Četvrtak','Petak','Subota','Nedelja'][(int)$date->format('N')]:'';
 $copy['event']=!empty($webinar['session'])?fcc_webinar_title($webinar['session']):fcc_t('Upoznaj Forever posao');
 $copy['unscheduled']='Novi termin uskoro';
 $message=$date?'Zdravo! Pozivam te na „'.$copy['event'].'“, '.$date->format('d. m. Y.').' u '.$date->format('H:i').' po zagrebačkom vremenu. Želiš li da se pridružiš?':'';
}
if(fcc_new_locale()) {
 $copy=fcc_localize_copy($copy);
 $copy['message']=fcc_t('Tvoja poruka');
 $copy['day']=$date?fcc_t(['','Ponedjeljak','Utorak','Srijeda','Četvrtak','Petak','Subota','Nedjelja'][(int)$date->format('N')]):'';
 $copy['event']=!empty($webinar['session'])?fcc_webinar_title($webinar['session']):fcc_t('Upoznaj Forever posao');
 $message=$date?fcc_t('Bok! Pozivam te na „{event}“, {date} u {time} (Europe/Zagreb). Želiš li se pridružiti?',['{event}'=>$copy['event'],'{date}'=>$date->format('d. m. Y.'),'{time}'=>$date->format('H:i')]):'';
}
if($eventChannel){$copy['title']=fcc_seminar_t('Pozovi na događaj.');$copy['event']=fcc_event_hub_t('Seminari i događaji');if(!empty($webinar['session']))$copy['event']=fcc_webinar_title($webinar['session']);}
if(!empty($webinar['session'])&&fcc_event_offline($webinar['session'])){
 $offlineDef=fcc_event_definition($webinar['session']);$copy['title']=fcc_seminar_t('Pozovi na događaj.');$copy['link_hint']=fcc_seminar_t('Tvoja poveznica povezuje prijavu gosta s tobom.');
 $message=fcc_seminar_t('Bok! Pozivam te na {event}.',['{event}'=>$copy['event']])."\n".fcc_seminar_date($webinar['session'])."\n".$offlineDef['location']."\n\n".fcc_seminar_t('Pogledaj program i prijavi interes. Možemo se dogovoriti za dolazak!');
}
if(!empty($webinar['session'])&&!empty(fcc_event_definition($webinar['session'])['managed'])){
 $locale=fcc_mw_locale($webinar['session']);$copy['zone']=$webinar['session']['timezone'];
 $message=strtr(fcc_mw_t('invitation',$locale),['{title}'=>$webinar['session']['title'],'{date}'=>fcc_webinar_label_time($webinar['session'],$locale)]);
}
$prepared=$data->values['message_draft']??$invite['draft']??($message."\n\n".($webinar['url']??''));
\Altum\Event::add_content('<script defer src="'.ASSETS_FULL_URL.'js/fcc-unified-invite.js?v='.filemtime(ASSETS_PATH.'js/fcc-unified-invite.js').'"></script>','head');
\Altum\Event::add_content('<script defer src="'.ASSETS_FULL_URL.'js/fcc-partner-share.js?v='.filemtime(ASSETS_PATH.'js/fcc-partner-share.js').'"></script>','head');
\Altum\Event::add_content('<script defer src="'.ASSETS_FULL_URL.'js/fcc-webinar-outreach.js?v='.filemtime(ASSETS_PATH.'js/fcc-webinar-outreach.js').'"></script>','head');
?>
<div class="fp-page fp-share-page fp-webinar-invite" data-webinar-invite data-invite-source-url="<?= $h($inviteSourceUrl) ?>" data-invite-expires="<?= (int)$shareDestination['expires'] ?>" data-event-check="<?= $h($eventCheck) ?>" data-phone="<?= $h($phone) ?>" data-invite-id="<?= (int)($attempt['id']??0) ?>" data-invite-endpoint="<?= url('partner-webinar') ?>" data-invite-csrf="<?= $h(\Altum\Csrf::get()) ?>" data-draft-replace="<?= $invite['draft']!==null||!empty($data->values['message_draft'])?'1':'0' ?>" data-invite-url="<?= $h($webinar['url']) ?>" data-draft-key="<?= $h((string)$this->user->user_id.':'.($date?$date->format('Y-m-d'):'').':'.($webinar['ref']??'').':'.\Altum\Language::$code.':'.($webinar['session']['id']??0).':'.($webinar['session']['revision']??0).($attempt?':'.$attempt['id']:'')) ?>" data-title="<?= $h($copy['event']) ?>" data-copy-ok="<?= $h($copy['copied']) ?>" data-copy-error="<?= $h($copy['copy_error']) ?>">
 <?php require THEME_PATH.'views/partner/share-navigation.php'; ?>
 <?php if($eventChannel): ?><a class="fp-link" href="<?= url('partner/share?type=events') ?>">← <?= $h(fcc_event_hub_t('Svi seminari i događaji')) ?></a><?php endif ?>
 <?php require THEME_PATH.'views/partner/invite-navigation.php'; \Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-webinar.css?v='.filemtime(ASSETS_PATH.'css/fcc-webinar.css').'">','head'); ?>
 <?php if(!empty($data->error)): ?><p class="fp-message fp-error" role="alert"><?= $h($data->error) ?></p><?php endif ?>
 <?php if(($_GET['saved']??'')==='invite'): ?><p class="fv-notice" role="status"><?= $h(fcc_invite_t('applied')) ?></p><?php endif ?>
 <header class="fp-invite-heading"><span class="fp-eyebrow"><?= $h($copy['eyebrow']) ?></span><h1><?= $h($copy['title']) ?></h1><p><?= $h($copy['intro']) ?></p></header>
 <?php if(!empty($webinar['events'])): ?>
 <form method="get" class="fp-form fp-invite-picker" data-invite-event-form>
  <input type="hidden" name="type" value="<?= $channel ?>">
  <span class="fp-invite-picker-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4m10-4v4M3 11h18m-13 5h2m4 0h2"/></svg></span>
  <div class="fp-invite-picker-field">
   <label for="invite-event-session"><?= $hr?fcc_t('Odaberi termin'):fcc_t('Choose an event') ?></label>
   <div class="fp-invite-picker-control">
    <select id="invite-event-session" name="session_id" data-is-not-custom-select>
     <?php foreach($webinar['events'] as $option): ?>
     <option value="<?= (int)$option['id'] ?>" <?= (int)($webinar['session']['id']??0)===(int)$option['id']?'selected':'' ?>><?= $h(fcc_webinar_title($option).' · '.fcc_event_label($option).(fcc_mw_caption($option)?' · '.fcc_mw_caption($option):'')) ?></option>
     <?php endforeach ?>
    </select>
    <svg class="fp-invite-picker-chevron" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 8 4 4 4-4"/></svg>
   </div>
  </div>
  <button type="submit" class="fp-button fp-button-outline" data-invite-event-submit><?= $hr?fcc_t('Prikaži poziv za odabrani termin'):fcc_t('Show invitation for this event') ?></button>
 </form>
 <?php endif ?>
 <div class="fp-invite-layout">
  <section class="fp-invite-event" aria-labelledby="webinar-event-title">
   <span class="fp-invite-online"><span aria-hidden="true"></span><?= $h($copy['online']) ?></span>
   <h2 id="webinar-event-title"><?= $h($copy['event']) ?></h2>
   <?php if($date): ?>
   <div class="fp-invite-schedule"><div class="fp-invite-calendar" aria-hidden="true"><span><?= $h(mb_strtoupper(mb_substr($copy['day'],0,3))) ?></span><strong><?= $date->format('d') ?></strong><small><?= $date->format('m / Y') ?></small></div><div><time datetime="<?= $h($date->format(DateTimeInterface::ATOM)) ?>"><?= $h($copy['day']) ?>, <?= $h($date->format(($hr||in_array(fcc_locale(),['sl','de','sr', 'sq', 'cnr', 'fr', 'es','sv','hu','pl','ro'],true))?'j. n.':'j M')) ?> <strong><?= !empty($offlineDef['time_pending'])?$h(fcc_seminar_t('Satnica uskoro')):$date->format('H:i') ?></strong></time><small><?= $h($copy['zone']) ?></small></div></div>
   <?php else: ?><p><?= $h($copy['unscheduled']) ?></p><?php endif ?>
   <?php if($webinar['url']): ?><a class="fp-invite-details" href="<?= $h($webinar['url']) ?>" target="_blank" rel="noopener noreferrer"><?= $h($copy['open']) ?> <?= fcc_partner_icon('arrow') ?></a><?php endif ?>
   <?php if($eventChannel&&!empty($webinar['session'])): ?><a class="fp-invite-details" href="<?= url('partner/webinars/'.$webinar['session']['id'].'#moja-prijava') ?>"><?= $h(fcc_event_hub_t('Prijavi i svoj dolazak')) ?> <?= fcc_partner_icon('arrow') ?></a><?php endif ?>
  </section>
  <section class="fp-invite-compose" aria-labelledby="webinar-message-label">
   <?php if($invite&&!$invite['available']): ?><p role="status"><?= $h(fcc_invite_t('unavailable')) ?></p><a class="fp-button fp-button-outline" href="<?= url('partner/share?type='.$channel) ?>"><?= $h(fcc_invite_t('prepare')) ?></a>
   <?php elseif(!$date): ?><h2 id="webinar-message-label"><?= $h($copy['unscheduled']) ?></h2><p><?= $h($copy['unscheduled_help']) ?></p>
   <?php elseif(!$webinar['url']&&!empty($webinar['session']['owner_user_id'])): ?><p><?= $h(fcc_invite_t('own_prepare')) ?></p><?php require THEME_PATH.'views/partner/invite-options.php'; ?>
   <?php elseif(!$webinar['url']): ?><h2 id="webinar-message-label"><?= $h($copy['missing_title']) ?></h2><p><?= $h($copy['missing']) ?></p><a class="fp-button" href="<?= url('partner/cards') ?>"><?= $h($copy['card']) ?></a>
   <?php else: ?>
   <?php if($invite)require THEME_PATH.'views/partner/invite-options.php'; ?>
   <label id="webinar-message-label" for="webinar-message"><?= $h($copy['message']) ?></label><p class="fp-invite-help" id="webinar-message-hint"><?= $h($copy['edit']) ?></p>
   <textarea id="webinar-message" rows="5" maxlength="4000" aria-describedby="webinar-message-hint" data-invite-message><?= $h($prepared) ?></textarea>
   <div class="fp-invite-link"><span><?= fcc_partner_icon('check') ?><?= $h($copy['link']) ?></span><small><?= $h($copy['link_hint']) ?></small></div>
   <div class="fp-invite-actions"><a class="fp-button" data-invite-whatsapp href="<?= $h('https://api.whatsapp.com/send?'.($phone?'phone='.rawurlencode($phone).'&':'').'text='.rawurlencode($prepared)) ?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp" aria-hidden="true"></i><?= $h($copy['whatsapp']) ?></a><button type="button" class="fp-button fp-button-outline" data-invite-share><?= fcc_partner_icon('share') ?><?= $h($copy['share']) ?></button></div>
   <?php require THEME_PATH.'views/partner/invite-outreach.php'; ?>
   <?php $shareCoachKind=$attempt?'video':'standard';$shareCoachId=(int)($attempt['id']??$webinar['session']['id']??0);require THEME_PATH.'views/partner/share-coach.php'; ?>
   <?php if($attempt): ?><a class="fp-invite-records" href="<?= url('partner-webinar?view=invite&id='.(int)$attempt['id']) ?>"><?= $h(fcc_invite_t('manage')) ?></a><?php endif ?>
   <p class="fp-invite-status" role="status" data-invite-status></p>
   <textarea class="fp-invite-fallback" aria-label="<?= $hr?fcc_t('Poziv za kopiranje'):fcc_t('Invitation to copy') ?>" data-invite-fallback readonly hidden></textarea>
   <?php endif ?>
  </section>
 </div>
</div>
