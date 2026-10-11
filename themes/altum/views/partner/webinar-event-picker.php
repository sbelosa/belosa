<?php defined('ALTUMCODE') || die();
$shared=$data->webinar['events']??[];$defaultEvent=$shared[0]??null;
$eventChoice=$data->values['webinar_choice']??(!empty($_GET['session_id'])?'session:'.(int)$_GET['session_id']:'default');
$creatingOwn=(!$_POST&&($_GET['create']??'')==='1'&&$data->personal_ready)||($data->values['webinar_mode']??'')==='custom'||$eventChoice==='custom';
if($eventChoice==='custom')$eventChoice='default';
$personalSelection=$creatingOwn||$eventChannel;$selectedPersonalId=0;
foreach($data->personal_events as $event)if($eventChoice==='session:'.$event['id']){$personalSelection=true;$selectedPersonalId=(int)$event['id'];}
$otherShared=array_values(array_filter($shared,fn($event)=>(int)$event['id']!==(int)($defaultEvent['id']??0)));
if($eventChoice==='session:'.($defaultEvent['id']??0))$eventChoice='default';
$defaultTitle=$defaultEvent['title']??($eventChannel?fcc_event_hub_t('Odaberi događaj'):fcc_t('FCC webinar'));
$defaultDate=$defaultEvent?fcc_webinar_label_time($defaultEvent):($data->webinar['starts_at']?$data->webinar['starts_at']->format('d. m. Y. \u H:i').fcc_t(' · Europe/Zagreb'):fcc_t('Novi termin još nije objavljen.'));
$defaultDescription=$defaultEvent?(fcc_event_definition($defaultEvent)['description']??''):'';
$ownValue=fn($key,$fallback='')=>$h($data->values['custom_'.$key]??$fallback);
?>
<input type="hidden" name="default_session_id" value="<?= (int)($defaultEvent['id']??0) ?>">
<input type="hidden" name="webinar_mode" value="<?= $creatingOwn?'custom':'existing' ?>" data-webinar-mode>
<div class="fv-existing-event" data-existing-event <?= $creatingOwn?'hidden':'' ?>>
<label for="fv-event-choice"><?= htmlspecialchars(($eventChannel?fcc_event_hub_t('Koji događaj želiš podijeliti?'):fcc_t('Koji webinar želiš podijeliti?')), ENT_QUOTES, 'UTF-8') ?></label>
<select id="fv-event-choice" name="webinar_choice" data-webinar-choice data-is-not-custom-select>
 <option value="default" data-event-channel="<?= $channel ?>" data-title="<?= $h($defaultTitle) ?>" data-date="<?= $h($defaultDate) ?>" data-description="<?= $h($defaultDescription) ?>" data-available="<?= ($defaultEvent||$data->webinar['starts_at'])?'1':'0' ?>" <?= $eventChoice==='default'?'selected':'' ?>><?= htmlspecialchars(($eventChannel?fcc_event_hub_t('Događaj'):fcc_t('Zadani FCC webinar')), ENT_QUOTES, 'UTF-8') ?><?= $defaultEvent?' · '.$h($defaultEvent['title']):'' ?></option>
 <?php if($otherShared): ?><optgroup label="<?= $h($eventChannel?fcc_event_hub_t('Seminari i događaji'):fcc_t('Dostupni FCC webinari')) ?>"><?php foreach($otherShared as $option): ?>
 <option value="session:<?= (int)$option['id'] ?>" data-event-channel="<?= $channel ?>" data-title="<?= $h(fcc_webinar_title($option)) ?>" data-date="<?= $h(fcc_webinar_label_time($option)) ?>" data-description="<?= $h(fcc_event_definition($option)['description']??'') ?>" data-available="1" <?= $eventChoice==='session:'.$option['id']?'selected':'' ?>><?= $h(fcc_webinar_title($option).' · '.fcc_webinar_label_time($option)) ?></option>
 <?php endforeach ?></optgroup><?php endif ?>
 <?php if($data->personal_events): ?><optgroup label="Moji spremljeni webinari"><?php foreach($data->personal_events as $option): ?>
 <option value="session:<?= (int)$option['id'] ?>" data-event-channel="<?= $channel ?>" data-title="<?= $h(fcc_webinar_title($option)) ?>" data-date="<?= $h(fcc_webinar_label_time($option)) ?>" data-description="<?= $h(fcc_event_definition($option)['description']??'') ?>" data-personal="1" data-manage-url="<?= $h($base.'&view=edit&session_id='.(int)$option['id']) ?>" data-available="1" <?= $eventChoice==='session:'.$option['id']?'selected':'' ?>><?= $h(fcc_webinar_title($option).' · '.fcc_webinar_label_time($option)) ?></option>
 <?php endforeach ?></optgroup><?php endif ?>
</select>
<?php foreach(array_merge($shared,$data->personal_events) as $option): ?><input type="hidden" name="session_revisions[<?= (int)$option['id'] ?>]" value="<?= (int)$option['revision'] ?>"><?php endforeach ?>
<?php if($defaultEvent): ?><input type="hidden" name="session_revisions[0]" value="<?= (int)$defaultEvent['revision'] ?>"><?php endif ?>
<p class="fv-event-summary" data-event-summary><?= $h($defaultDate) ?></p>
<div class="fv-actions fv-event-manage-actions" data-personal-actions <?= !$selectedPersonalId?'hidden':'' ?>><a class="fp-button fp-button-outline" data-personal-edit-link href="<?= $h($base.'&view=edit&session_id='.$selectedPersonalId) ?>"><?= htmlspecialchars(fcc_t('Uredi webinar'), ENT_QUOTES, 'UTF-8') ?></a><a class="fp-text-button fv-remove-link" data-personal-remove-link href="<?= $h($base.'&view=edit&session_id='.$selectedPersonalId.'&remove=1') ?>#fv-remove-webinar"><?= htmlspecialchars(fcc_t('Ukloni webinar'), ENT_QUOTES, 'UTF-8') ?></a></div>
<?php if($data->personal_ready): ?><button type="button" class="fp-button fp-button-outline fv-create-webinar" data-create-webinar aria-controls="fv-webinar-setup" aria-expanded="false"><?= fcc_partner_icon('plus') ?> <?= htmlspecialchars(fcc_t('Kreiraj svoj webinar ili termin'), ENT_QUOTES, 'UTF-8') ?></button><?php endif ?>
</div>
