<?php defined('ALTUMCODE') || die(); ?>
<div class="fp-editor-header">
 <a class="fp-link" href="<?= url('partner/cards') ?>"><?= htmlspecialchars(fcc_t('← Moje kartice'), ENT_QUOTES, 'UTF-8') ?></a>
 <div class="fp-editor-heading"><div><h1><?= htmlspecialchars(fcc_t('Uredi karticu'), ENT_QUOTES, 'UTF-8') ?></h1><p class="fp-editor-card-name"><?= fcc_partner_h(($data->link->settings->seo->title ?? '') ?: $data->link->url) ?></p></div><button type="button" class="fp-button fp-button-outline" data-share-url="<?= fcc_partner_h($data->link->full_url) ?>" data-share-title="<?= htmlspecialchars(fcc_t('Moja FCC kartica'), ENT_QUOTES, 'UTF-8') ?>"><?= fcc_partner_icon('share') ?> <?= htmlspecialchars(fcc_t('Podijeli'), ENT_QUOTES, 'UTF-8') ?></button></div>
 <?php if(fc_get_user_main_biolink_id((int)$data->link->user_id, false) === (int)$data->link->link_id): ?><span class="fp-editor-protected"><?= htmlspecialchars(fcc_t('Glavna NFC kartica · adresa je trajno zaštićena'), ENT_QUOTES, 'UTF-8') ?></span><?php endif ?>
 <nav class="fp-editor-tabs" aria-label="<?= htmlspecialchars(fcc_t('Uređivanje kartice'), ENT_QUOTES, 'UTF-8') ?>">
  <?php foreach(fcc_localize_copy(['blocks'=>fcc_t('Sadržaj'),'design'=>'Izgled','settings'=>'Postavke','preview'=>'Pregled']) as $key=>$label): ?><button type="button" data-editor-tab="<?= $key ?>" aria-pressed="<?= $key==='blocks'?'true':'false' ?>"><?= $label ?></button><?php endforeach ?>
 </nav>
 <div class="fp-editor-context"><p data-editor-description><?= htmlspecialchars(fcc_t('Dodirni blok za uređivanje. Povuci ručicu za promjenu redoslijeda.'), ENT_QUOTES, 'UTF-8') ?></p><button class="fp-button" type="button" data-editor-add data-toggle="modal" data-target="#biolink_link_create_modal"><?= fcc_partner_icon('plus') ?> <?= htmlspecialchars(fcc_t('Dodaj blok'), ENT_QUOTES, 'UTF-8') ?></button></div>
</div>
