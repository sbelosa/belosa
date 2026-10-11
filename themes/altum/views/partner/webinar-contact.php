<?php defined('ALTUMCODE') || die();
$wvPersonal=fcc_webinar_personal_ready();
$wvRows=fcc_webinar_enabled((int)$this->user->user_id)?fcc_partner_rows('SELECT r.*,s.title,s.starts_at_utc,s.ends_at_utc,s.timezone,s.access_url,s.event_json,s.status session_status,'.($wvPersonal?'s.owner_user_id':'NULL').' owner_user_id FROM fcc_webinar_registrations r JOIN fcc_webinar_sessions s ON s.id=r.session_id WHERE r.user_id=? AND r.contact_id=?'.($wvPersonal?' AND (s.owner_user_id IS NULL OR s.owner_user_id=r.user_id)':'').fcc_t(' ORDER BY r.id DESC LIMIT 10'),[(int)$this->user->user_id,(int)$contact['id']]):[];
if(!$wvRows)return;
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-webinar.css?v='.filemtime(ASSETS_PATH.'css/fcc-webinar.css').'"><script defer src="'.ASSETS_FULL_URL.'js/fcc-webinar.js?v='.filemtime(ASSETS_PATH.'js/fcc-webinar.js').'"></script>','head');
?>
<details class="fv-details" open><summary><?= htmlspecialchars(fcc_seminar_t('Prijave na webinare i događaje'), ENT_QUOTES, 'UTF-8') ?></summary>
<?php if(fcc_wl_ready()): ?><p><a class="fp-button fp-button-outline" href="<?= url('partner-webinar?view=guests&contact_id='.(int)$contact['id']) ?>"><?= $h(fcc_wl_t('guests')) ?></a></p><?php endif ?>
<?php foreach($wvRows as $wv): $wvMessage=fcc_webinar_contact_message($wv,(string)$contact['name']); ?><div class="fv-notice"><strong><?= $h($wv['title']) ?></strong><p><?= $h(fcc_webinar_label_time($wv)) ?><br><?= htmlspecialchars(fcc_t('Pozivnica'), ENT_QUOTES, 'UTF-8') ?> <?= (int)$wv['attempt_id'] ?> · <?= $wv['is_demo']?fcc_t('Probna prijava'):fcc_t('Zaprimljen obrazac') ?><br><?= $h($wv['reminder_opt_in']?($wv['email_verified_at']?fcc_wl_t('reminders_on'):fcc_wl_t('verify_pending')):fcc_wl_t('reminders_off')) ?><?= $wv['status']==='cancelled'?fcc_t(' · Prijava otkazana'):'' ?></p><?php if($wv['status']==='registered'&&$wv['session_status']!=='cancelled'&&!$wv['is_demo']&&!$wv['is_self']&&!$wv['needs_review']): ?><p><?= $h(fcc_wl_t('registration_counted')) ?></p><?php endif ?><a href="<?= url('partner-webinar?id='.(int)$wv['attempt_id']) ?>"><?= htmlspecialchars(fcc_t('Pogledaj pozivnicu i rezultat →'), ENT_QUOTES, 'UTF-8') ?></a>
<?php if(fcc_webinar_may_contact_registration($wv,$contact)): ?>
 <p><?= $h(fcc_wl_t('registration_contact')) ?></p>
 <?php if($wvMessage!==''): ?>
 <div class="fv-contact-welcome" data-webinar-followup data-phone="<?= $h(preg_replace('/\D/','',fcc_partner_contact_phone($contact['phone'],true))) ?>">
  <label for="webinar-followup-<?= (int)$wv['id'] ?>"><?= htmlspecialchars(fcc_t('Prva poruka nakon prijave'), ENT_QUOTES, 'UTF-8') ?></label>
  <p id="webinar-followup-help-<?= (int)$wv['id'] ?>"><?= htmlspecialchars(fcc_t('Termin i poveznica već su u poruci. Po želji je prilagodi prije slanja.'), ENT_QUOTES, 'UTF-8') ?></p>
  <textarea class="form-control" id="webinar-followup-<?= (int)$wv['id'] ?>" aria-describedby="webinar-followup-help-<?= (int)$wv['id'] ?>" rows="12" maxlength="3000"><?= $h($wvMessage) ?></textarea>
  <div class="fv-actions"><a class="fp-button fp-button-outline" data-followup-whatsapp target="_blank" rel="noopener noreferrer" href="<?= $h('https://wa.me/'.preg_replace('/\D/','',fcc_partner_contact_phone($contact['phone'],true)).'?text='.rawurlencode($wvMessage)) ?>"><?= htmlspecialchars(fcc_t('Otvori poruku u WhatsAppu'), ENT_QUOTES, 'UTF-8') ?></a></div>
  <small><?= htmlspecialchars(fcc_t('Poruku pregledaš i šalješ osobno.'), ENT_QUOTES, 'UTF-8') ?></small>
 </div>
 <?php else: ?><p><?= htmlspecialchars(fcc_t('Za ovaj webinar trenutačno nema dostupnog termina ili poveznice za pristup.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
<?php else: ?><p><?= $h(fcc_wl_t('no_permission')) ?></p><?php endif ?>
<?php if($wv['needs_review']): ?><p><?= htmlspecialchars(fcc_t('Podaci se djelomično podudaraju s ranijim kontaktom. Provjeri ih prije spajanja.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
</div><?php endforeach ?></details>
