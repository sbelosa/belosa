<?php defined('ALTUMCODE') || die();
$removal=$data->card_removal??null;
if(!$removal) return;
?>
<section class="fp-panel fp-card-delete-confirm" aria-labelledby="card-delete-heading">
 <span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('ZAVRŠNA POTVRDA'), ENT_QUOTES, 'UTF-8') ?></span>
 <h2 id="card-delete-heading"><?= htmlspecialchars(fcc_t('Potvrdi brisanje kartice'), ENT_QUOTES, 'UTF-8') ?></h2>
 <p><strong><?= $h($removal['card']['title']) ?></strong><br><span class="fp-url"><?= $h($removal['card']['public_url']) ?></span></p>
 <p><?= htmlspecialchars(fcc_t('Brisanje ove kartice i njezina sadržaja nije moguće poništiti. Tvoja glavna NFC kartica ostaje sačuvana.'), ENT_QUOTES, 'UTF-8') ?></p>
 <form method="post" action="<?= url('partner/cards?remove='.(int)$removal['card']['link_id']) ?>" class="fp-form">
  <?= $token ?>
  <input type="hidden" name="action" value="card_delete">
  <input type="hidden" name="link_id" value="<?= (int)$removal['card']['link_id'] ?>">
  <input type="hidden" name="confirmation_token" value="<?= $h($removal['token']) ?>">
  <label for="card-delete-confirmation"><?= htmlspecialchars(fcc_t('Za potvrdu upiši UKLONI'), ENT_QUOTES, 'UTF-8') ?><input id="card-delete-confirmation" name="confirmation_text" autocomplete="off" autocapitalize="characters" spellcheck="false" required pattern="UKLONI" maxlength="6" placeholder="<?= htmlspecialchars(fcc_t('UKLONI'), ENT_QUOTES, 'UTF-8') ?>"></label>
  <button class="fp-button fp-button-danger" type="submit"><?= htmlspecialchars(fcc_t('Trajno ukloni karticu'), ENT_QUOTES, 'UTF-8') ?></button>
 </form>
 <form method="post" action="<?= url('partner/cards') ?>" class="fp-card-cancel"><?= $token ?><input type="hidden" name="action" value="card_delete_cancel"><button type="submit" class="fp-text-button"><?= htmlspecialchars(fcc_t('Odustani, zadrži karticu'), ENT_QUOTES, 'UTF-8') ?></button></form>
</section>
