<?php defined('ALTUMCODE') || die(); ?>
<?php if($card['is_nfc']): ?>
 <div class="fp-card-protected"><strong><?= htmlspecialchars(fcc_t('Glavna NFC kartica'), ENT_QUOTES, 'UTF-8') ?></strong><p><?= htmlspecialchars(fcc_t('Povezana je s tvojom fizičkom karticom. Sadržaj možeš uređivati, a adresa je trajno zaštićena.'), ENT_QUOTES, 'UTF-8') ?></p></div>
<?php elseif(!\Altum\Teams::is_delegated() || \Altum\Teams::has_access('delete.links')): ?>
 <details class="fp-card-remove">
  <summary><?= htmlspecialchars(fcc_t('Ukloni ovu karticu'), ENT_QUOTES, 'UTF-8') ?></summary>
  <form method="post" action="<?= url('partner/cards') ?>" class="fp-card-remove-form">
   <?= $token ?>
   <input type="hidden" name="action" value="card_delete_prepare">
   <input type="hidden" name="link_id" value="<?= (int)$card['link_id'] ?>">
   <strong><?= htmlspecialchars(fcc_t('Želiš ukloniti ovu karticu?'), ENT_QUOTES, 'UTF-8') ?></strong>
   <p><?= htmlspecialchars(fcc_t('Uklonit će se kartica „'), ENT_QUOTES, 'UTF-8') ?><?= $h($card['title']) ?><?= htmlspecialchars(fcc_t('” i njezin sadržaj. Poveznice i QR kodovi koji vode na nju više neće otvarati ovu karticu.'), ENT_QUOTES, 'UTF-8') ?></p>
   <small><?= htmlspecialchars(fcc_t('U sljedećem koraku još jednom potvrđuješ brisanje.'), ENT_QUOTES, 'UTF-8') ?></small>
   <button class="fp-button fp-button-outline" type="submit"><?= htmlspecialchars(fcc_t('Da, nastavi'), ENT_QUOTES, 'UTF-8') ?></button>
  </form>
 </details>
<?php endif ?>
