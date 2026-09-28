<?php defined('ALTUMCODE') || die();
$proUid=(int)$this->user->user_id;
$proConfig=['active'=>fcc_pro_has_access($proUid),'base'=>SITE_URL,'features'=>fcc_pro_features()];
?>
<script type="application/json" id="fcc-pro-config"><?= json_encode($proConfig,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
<dialog class="fcc-pro-dialog" id="fcc-pro-dialog" aria-labelledby="fcc-pro-title" aria-describedby="fcc-pro-description">
 <button class="fcc-pro-close" type="button" data-pro-close aria-label="Zatvori">×</button>
 <span class="fcc-pro-badge">PRO</span>
 <p class="fcc-pro-eyebrow">VIŠE PODRŠKE ZA TVOJ RAD</p>
 <h2 id="fcc-pro-title">Otključaj više mogućnosti.</h2>
 <p id="fcc-pro-description"></p>
 <div class="fcc-pro-included"><span aria-hidden="true">✓</span> Dostupno uz aktivni PRO paket.</div>
 <a class="fcc-pro-action" data-pro-activate href="<?= url('account-plan') ?>">Pogledaj i aktiviraj PRO <span aria-hidden="true">→</span></a>
 <button class="fcc-pro-later" type="button" data-pro-close>Nastavi s Free paketom</button>
 <p class="fcc-pro-note">Na sljedećoj stranici možeš pregledati cijenu i uvjete. Otvaranjem ovog prozora ništa se ne naplaćuje.</p>
</dialog>
