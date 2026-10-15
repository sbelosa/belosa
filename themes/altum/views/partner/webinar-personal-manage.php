<?php defined('ALTUMCODE') || die();
$registrationLabel=static function(int $count): string {
 if(fcc_locale()!=='pl')return fcc_t('prijava');
 if($count===1)return 'rejestracja';
 $last=$count%10;$lastTwo=$count%100;
 return $last>=2&&$last<=4&&($lastTwo<12||$lastTwo>14)?'rejestracje':'rejestracji';
}; ?>
<?php if($data->view==='events'): ?>
<!-- Custom code: FC-2026-10-15: current webinars and history have separate lists. -->
<section class="fp-panel fv-personal-overview" <?= !$data->events_archive?'data-current-events-now="'.gmdate('Y-m-d\TH:i:s\Z').'"':'' ?>>
 <nav class="fv-actions" aria-label="<?= $h(fcc_t('Moji webinari')) ?>"><a class="fp-button <?= $data->events_archive?'fp-button-outline':'' ?>" href="<?= $base ?>&amp;view=events" <?= !$data->events_archive?'aria-current="page"':'' ?>><?= $h(fcc_event_manage_t('current')) ?></a><a class="fp-button <?= !$data->events_archive?'fp-button-outline':'' ?>" href="<?= $base ?>&amp;view=events&amp;archive=1" <?= $data->events_archive?'aria-current="page"':'' ?>><?= $h(fcc_event_manage_t('archive')) ?></a></nav>
 <p class="fp-muted"><?= $h(fcc_event_manage_t('archive_help')) ?></p>
 <div class="fv-panel-heading"><div><span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('MOJI DOGAĐAJI'), ENT_QUOTES, 'UTF-8') ?></span><h2><?= htmlspecialchars(fcc_t('Sve za tvoje webinare.'), ENT_QUOTES, 'UTF-8') ?></h2><p class="fp-muted"><?= htmlspecialchars(fcc_t('Uredi detalje, pripremi pozivnicu ili ukloni webinar koji se neće održati.'), ENT_QUOTES, 'UTF-8') ?></p></div><a class="fp-button fp-button-outline" href="<?= $base ?>&amp;create=1"><?= htmlspecialchars(fcc_t('Kreiraj svoj webinar ili termin'), ENT_QUOTES, 'UTF-8') ?></a></div>
 <?php if(!$data->managed_events): ?><div class="fv-empty"><p><?= htmlspecialchars(($data->events_archive?fcc_event_manage_t('archive_empty'):fcc_event_manage_t('empty')), ENT_QUOTES, 'UTF-8') ?></p></div><?php endif ?>
 <?php foreach($data->managed_events as $event): $upcoming=$event['starts_at_utc']>gmdate('Y-m-d H:i:s');$open=$event['status']==='scheduled'&&$upcoming;$editUrl=$base.'&view='.(!empty($event['organizer_user_id'])?'manage':'edit').'&session_id='.(int)$event['id']; ?>
 <article class="fv-personal-row" data-event-end="<?= $h(str_replace(' ','T',$event['ends_at_utc']).'Z') ?>">
  <div><span class="fv-badge"><?= $event['status']==='cancelled'?fcc_t('Uklonjen'):($upcoming?fcc_t('Nadolazeći'):($event['status']==='scheduled'&&$event['ends_at_utc']>gmdate('Y-m-d H:i:s')?fcc_event_manage_t('ongoing'):fcc_t('Prošli webinar'))) ?></span><h3><?= $h($event['title']) ?></h3><p><?= $h(fcc_webinar_label_time($event)) ?></p><?php if(!empty($event['organizer_user_id'])): ?><small><?= $h(fcc_mw_t($event['audience_scope'])) ?></small> <?php endif ?><small><?= (int)$event['registrations'] ?> <?= htmlspecialchars($registrationLabel((int)$event['registrations']), ENT_QUOTES, 'UTF-8') ?></small></div>
  <div class="fv-actions"><?php if($open&&$data->pro): ?><a class="fp-button fp-button-outline" href="<?= $base ?>&amp;session_id=<?= (int)$event['id'] ?>"><?= htmlspecialchars(fcc_t('Pripremi poziv'), ENT_QUOTES, 'UTF-8') ?></a><?php endif ?><a class="fp-button fp-button-outline" href="<?= $h($editUrl) ?>"><?= $upcoming?fcc_t('Uredi webinar'):fcc_t('Pogledaj detalje') ?></a><?php if($event['status']!=='cancelled'&&$upcoming): ?><a class="fp-text-button fv-remove-link" href="<?= $h($editUrl.'&remove=1') ?>#fv-remove-webinar"><?= htmlspecialchars(fcc_t('Ukloni'), ENT_QUOTES, 'UTF-8') ?></a><?php endif ?></div>
 </article>
 <?php endforeach ?>
</section>
<?php if(!$data->events_archive): ?><script src="<?= ASSETS_FULL_URL ?>js/fcc-event-expiry.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-event-expiry.js') ?>" defer></script><?php endif ?>
<!-- /Custom code: FC-2026-10-15 -->
<?php else:
 $event=$data->edit_session;$upcoming=$event['starts_at_utc']>gmdate('Y-m-d H:i:s');$cancelled=$event['status']==='cancelled';
 $creatingOwn=true;$editingOwn=true;$ownValue=fn($key,$fallback='')=>$h($data->values['custom_'.$key]??$fallback);
?>
<div class="fv-current-heading"><a class="fp-link" href="<?= $base ?>&amp;view=events"><?= htmlspecialchars(fcc_t('← Moji webinari'), ENT_QUOTES, 'UTF-8') ?></a><span class="fv-badge"><?= $cancelled?fcc_t('Uklonjen webinar'):($upcoming?fcc_t('Nadolazeći webinar'):fcc_t('Prošli webinar')) ?></span></div>
<div class="fp-invite-layout fv-personal-editor">
 <section class="fp-invite-event fv-intro-card"><span class="fp-invite-online"><span></span><?= htmlspecialchars(fcc_t('MOJ WEBINAR'), ENT_QUOTES, 'UTF-8') ?></span><h2><?= $h($event['title']) ?></h2><div class="fv-event-time"><strong><?= $h(fcc_webinar_label_time($event)) ?></strong><small><?= (int)$data->event_registrations ?> <?= htmlspecialchars($registrationLabel((int)$data->event_registrations), ENT_QUOTES, 'UTF-8') ?></small></div>
  <p><?= $cancelled?fcc_t('Webinar je uklonjen iz novih poziva. Ranije poveznice prikazuju da je otkazan.'):fcc_t('Promjene vrijede za sve pozivnice ovog webinara. Poveznice i prijave ostaju sačuvane, a podsjetnici se usklađuju s novim terminom.') ?></p>
  <?php if(!$cancelled&&$upcoming&&$data->pro): ?><a class="fp-button fp-button-light" href="<?= $base ?>&amp;session_id=<?= (int)$event['id'] ?>"><?= htmlspecialchars(fcc_t('Pripremi pozivnicu'), ENT_QUOTES, 'UTF-8') ?> <?= fcc_partner_icon('arrow') ?></a><?php endif ?>
 </section>
 <section class="fp-invite-compose fv-config">
  <span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('DETALJI WEBINARA'), ENT_QUOTES, 'UTF-8') ?></span><h2><?= $cancelled?fcc_t('Ponovno otvori webinar.'):fcc_t('Uredi svoj webinar.') ?></h2>
  <?php if(!$upcoming): ?><p class="fp-muted"><?= htmlspecialchars(fcc_t('Započeti webinar ostaje u povijesti. Za sljedeći susret kreiraj novi webinar.'), ENT_QUOTES, 'UTF-8') ?></p><?php elseif(!$data->pro): ?><p class="fp-muted"><?= htmlspecialchars(fcc_t('Za uređivanje i ponovno otvaranje potreban je aktivni PRO paket. Webinar i dalje možeš ukloniti.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
  <form method="post" action="<?= $base ?>&amp;view=edit&amp;session_id=<?= (int)$event['id'] ?>" class="fp-form" data-personal-edit>
   <?= $token ?><input type="hidden" name="action" value="personal_save"><input type="hidden" name="session_id" value="<?= (int)$event['id'] ?>"><input type="hidden" name="revision" value="<?= (int)($data->values['revision']??$event['revision']) ?>">
   <fieldset class="fv-edit-fields" <?= !$upcoming||!$data->pro?'disabled':'' ?>><?php require THEME_PATH.'views/partner/webinar-personal-fields.php'; ?></fieldset>
   <div class="fv-actions"><?php if($upcoming&&$data->pro): ?><button class="fp-button"><?= $cancelled?fcc_t('Ponovno otvori webinar'):fcc_t('Spremi promjene') ?></button><?php endif ?><a class="fp-button fp-button-outline" href="<?= $base ?>&amp;view=events"><?= htmlspecialchars(fcc_t('Odustani'), ENT_QUOTES, 'UTF-8') ?></a></div>
  </form>
  <?php if(!$cancelled&&$upcoming): ?><details id="fv-remove-webinar" class="fv-details fv-remove-webinar" <?= isset($_GET['remove'])||($_POST['action']??'')==='personal_remove'?'open':'' ?>><summary><?= htmlspecialchars(fcc_t('Ukloni webinar'), ENT_QUOTES, 'UTF-8') ?></summary><p><?= htmlspecialchars(fcc_t('Webinar će biti otkazan i uklonjen iz izbora za nove pozivnice. Prijave i kontakti ostaju sačuvani. Ranije poveznice prikazivat će otkazivanje, a podsjetnici za dolazak prestat će.'), ENT_QUOTES, 'UTF-8') ?></p>
   <form method="post" action="<?= $base ?>&amp;view=edit&amp;session_id=<?= (int)$event['id'] ?>#fv-remove-webinar" class="fp-form">
    <?= $token ?><input type="hidden" name="action" value="personal_remove"><input type="hidden" name="session_id" value="<?= (int)$event['id'] ?>"><input type="hidden" name="revision" value="<?= (int)$event['revision'] ?>">
    <label class="fv-check"><input type="checkbox" name="confirm_remove" value="1" required><span><?= htmlspecialchars(fcc_t('Želim ukloniti webinar „'), ENT_QUOTES, 'UTF-8') ?><?= $h($event['title']) ?>”.</span></label><button class="fp-button fp-button-outline fv-remove-link"><?= htmlspecialchars(fcc_t('Ukloni webinar'), ENT_QUOTES, 'UTF-8') ?></button>
   </form>
  </details><?php endif ?>
 </section>
</div>
<?php endif ?>
