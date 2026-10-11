<?php defined('ALTUMCODE') || die();$h='fcc_partner_h';$et='fcc_event_hub_t';$uid=(int)$this->user->user_id;
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-event-hub.css?v='.filemtime(ASSETS_PATH.'css/fcc-event-hub.css').'">','head'); ?>
<div class="fp-page fp-share-page fe-hub">
<?php require __DIR__.'/share-navigation.php'; ?>
<header class="fp-invite-heading"><span class="fp-eyebrow"><?= $h($et('UČIMO I RASTEMO ZAJEDNO')) ?></span><h1><?= $h($et('Seminari i događaji')) ?></h1><p><?= $h($et('Odaberi susret, prijavi svoj dolazak i pozovi nekoga da ti se pridruži.')) ?></p></header>
<nav class="fe-hub-links" aria-label="<?= $h($et('Događaji')) ?>"><a class="fp-button fp-button-outline" href="<?= url('partner-webinar?type=events&view=activity') ?>"><?= $h($et('Prijave mojih gostiju')) ?></a><a class="fp-link" href="<?= url('partner/webinars') ?>"><?= $h($et('Cijeli kalendar tima')) ?> →</a></nav>
<div class="fe-hub-grid">
<?php foreach($data->events as $s):$d=fcc_event_definition($s);$rsvp=fcc_event_rsvp($uid,(int)$s['id']);$date=(new DateTimeImmutable($s['starts_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb')); ?>
<article class="fp-panel fe-hub-card">
<?php if($d['cover']): ?><a class="fe-hub-cover" href="<?= url('partner/webinars/'.$s['id']) ?>"><img src="<?= $h(fcc_event_asset_url($d['cover'])) ?>" alt="<?= $h(fcc_webinar_title($s)) ?>" loading="lazy" width="320" height="420"></a><?php endif ?>
<div class="fe-hub-content"><span class="fp-eyebrow"><?= $h(fcc_event_types()[$d['kind']]) ?></span><h2><a href="<?= url('partner/webinars/'.$s['id']) ?>"><?= $h(fcc_webinar_title($s)) ?></a></h2>
<p class="fe-hub-date"><?= $h(fcc_event_label($s)) ?><?php if(empty($d['time_pending'])): ?><br><?= $h($et('Završetak')) ?> <?= $h((new DateTimeImmutable($s['ends_at_utc'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Europe/Zagreb'))->format('H:i')) ?><?php endif ?></p>
<p><?= $h($d['mode']==='in_person'?$d['location']:$et('Online događaj')) ?></p>
<?php if($s['status']!=='scheduled'): ?><p class="fe-rsvp-state"><?= $h(fcc_event_states()[$s['status']]) ?></p><?php elseif(($rsvp['state']??'')==='going'): ?><p class="fe-rsvp-state">✓ <?= $h($et('Tvoj dolazak je prijavljen')) ?></p><?php endif ?>
<div class="fe-hub-actions"><a class="fp-button" href="<?= url('partner/webinars/'.$s['id']) ?>"><?= $h($et(($rsvp['state']??'')==='going'?'Moja prijava i program':'Program i moja prijava')) ?></a>
<?php if(fcc_event_invitable($uid,$s)): ?><a class="fp-button fp-button-outline" href="<?= url(fcc_event_share_path($s)) ?>"><?= $h($et('Pozovi gosta')) ?></a><?php endif ?></div>
</div></article>
<?php endforeach ?>
<?php if(!$data->events): ?><section class="fp-panel"><h2><?= $h($et('Novi susreti uskoro')) ?></h2><p><?= $h($et('Ovdje će te čekati seminari, Dani uspjeha i drugi posebni događaji.')) ?></p></section><?php endif ?>
</div></div>
