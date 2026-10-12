<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: celebrate a milestone once and ask before mentioning it to the team. */
$mpCelebration = function_exists('fcc_mp_celebration') ? fcc_mp_celebration($uid) : null;
if(!$mpCelebration) return;
?>
<section class="fp-panel mp-celebration" role="status">
 <span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('PREKRETNICA'), ENT_QUOTES, 'UTF-8') ?></span>
 <h2><?= htmlspecialchars(fcc_t('Bravo!'), ENT_QUOTES, 'UTF-8') ?> <?= $h($mpCelebration['label']) ?></h2>
 <p><?= htmlspecialchars(fcc_t('Tvoj sponzor je obaviješten. Smijemo li tvoj uspjeh spomenuti u timskoj grupi ili na webinaru? Bez tvoje potvrde to ne radimo.'), ENT_QUOTES, 'UTF-8') ?></p>
 <div class="fp-actions">
  <form method="post"><?= $token ?><input type="hidden" name="action" value="mp_celebration"><input type="hidden" name="milestone_id" value="<?= (int)$mpCelebration['id'] ?>"><input type="hidden" name="share" value="1"><button class="fp-button"><?= htmlspecialchars(fcc_t('Da, smijete me spomenuti'), ENT_QUOTES, 'UTF-8') ?></button></form>
  <form method="post"><?= $token ?><input type="hidden" name="action" value="mp_celebration"><input type="hidden" name="milestone_id" value="<?= (int)$mpCelebration['id'] ?>"><input type="hidden" name="share" value="0"><button class="fp-button fp-button-outline"><?= htmlspecialchars(fcc_t('Hvala, ne sada'), ENT_QUOTES, 'UTF-8') ?></button></form>
 </div>
</section>
<?php /* /Custom code: FC-2026-10-12 */ ?>
