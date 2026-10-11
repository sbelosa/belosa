<?php defined('ALTUMCODE') || die();$et='fcc_event_hub_t';$rsvp=fcc_event_rsvp($uid,(int)$s['id']);$going=($rsvp['state']??'')==='going'; ?>
<section class="fp-panel fe-rsvp" id="moja-prijava" aria-labelledby="fe-rsvp-title">
<div><span class="fp-eyebrow"><?= $h($et('MOJ DOLAZAK')) ?></span><h2 id="fe-rsvp-title"><?= $h($et($going?'Tvoj dolazak je prijavljen':'Vidimo se na događaju?')) ?></h2><p><?= $h($et($going?'Tvoja prijava je spremljena na tvojem FCC računu.':'Prijavi se svojim FCC računom jednim klikom.')) ?></p>
<?php if($d['ticket_url']): ?><p class="fp-muted"><?= $h($et('Za ulazak na događaj kupi i ulaznicu na stranici organizatora.')) ?></p><?php endif ?></div>
<div class="fe-rsvp-controls">
<?php if(fcc_event_rsvp_ready()&&($going||fcc_event_rsvp_open($s))): ?><form method="post"><input type="hidden" name="token" value="<?= $h(\Altum\Csrf::get()) ?>"><input type="hidden" name="action" value="rsvp"><input type="hidden" name="revision" value="<?= (int)$s['revision'] ?>"><input type="hidden" name="rsvp" value="<?= $going?'cancelled':'going' ?>"><button class="fp-button <?= $going?'fp-button-outline':'' ?>"><?= $h($et($going?'Otkaži moj dolazak':'Prijavi moj dolazak')) ?></button></form><?php elseif(!$going): ?><p><?= $h($et('Prijave za ovaj događaj su zatvorene.')) ?></p><?php endif ?>
<?php if($d['ticket_url']&&fcc_event_rsvp_open($s)): ?><a class="fp-button fp-button-outline" href="<?= $h($d['ticket_url']) ?>" target="_blank" rel="noopener noreferrer"><?= $h(fcc_seminar_t('Kupi ulaznicu')) ?></a><?php endif ?>
</div></section>
