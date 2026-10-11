<?php defined('ALTUMCODE') || die();
$proUid=(int)$this->user->user_id;
$proConfig=['active'=>fcc_pro_has_access($proUid),'base'=>SITE_URL,'features'=>fcc_localize_copy(fcc_pro_features())];
?>
<script type="application/json" id="fcc-pro-config"><?= json_encode($proConfig,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
<dialog class="fcc-pro-dialog" id="fcc-pro-dialog" aria-labelledby="fcc-pro-title" aria-describedby="fcc-pro-description">
 <button class="fcc-pro-close" type="button" data-pro-close aria-label="<?= htmlspecialchars(fcc_t('Zatvori'), ENT_QUOTES, 'UTF-8') ?>">×</button>
 <span class="fcc-pro-badge"><?= htmlspecialchars(fcc_t('PRO'), ENT_QUOTES, 'UTF-8') ?></span>
 <p class="fcc-pro-eyebrow"><?= htmlspecialchars(fcc_t('VIŠE PODRŠKE ZA TVOJ RAD'), ENT_QUOTES, 'UTF-8') ?></p>
 <h2 id="fcc-pro-title"><?= htmlspecialchars(fcc_t('Otključaj više mogućnosti.'), ENT_QUOTES, 'UTF-8') ?></h2>
 <p id="fcc-pro-description"></p>
 <div class="fcc-pro-included"><span aria-hidden="true">✓</span> <?= htmlspecialchars(fcc_t('Dostupno uz aktivni PRO paket.'), ENT_QUOTES, 'UTF-8') ?></div>
 <a class="fcc-pro-action" data-pro-activate href="<?= url('account-plan') ?>"><?= htmlspecialchars(fcc_t('Pogledaj i aktiviraj PRO'), ENT_QUOTES, 'UTF-8') ?> <span aria-hidden="true">→</span></a>
 <button class="fcc-pro-later" type="button" data-pro-close><?= htmlspecialchars(fcc_t('Nastavi s Free paketom'), ENT_QUOTES, 'UTF-8') ?></button>
 <p class="fcc-pro-note"><?= htmlspecialchars(fcc_t('Na sljedećoj stranici možeš pregledati cijenu i uvjete. Otvaranjem ovog prozora ništa se ne naplaćuje.'), ENT_QUOTES, 'UTF-8') ?></p>
</dialog>
