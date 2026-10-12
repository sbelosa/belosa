<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: after 90 steps the four habits continue as a weekly rhythm. */
if(!function_exists('fcc_mp_rhythm_week')) return;
$mpRhythm = fcc_mp_rhythm_week($uid);
?>
<section class="fp-panel mp-rhythm">
 <span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('MOJ RITAM'), ENT_QUOTES, 'UTF-8') ?></span>
 <h2><?= htmlspecialchars(fcc_t('Četiri navike ovaj tjedan'), ENT_QUOTES, 'UTF-8') ?></h2>
 <p><?= htmlspecialchars(fcc_t('Nastavi ono što ti je kroz 90 koraka najviše pomoglo. Svaku naviku označi kada je ovaj tjedan odradiš.'), ENT_QUOTES, 'UTF-8') ?></p>
 <ul class="mp-rhythm-list">
  <?php foreach(fcc_mp_rhythm_habits() as $mpHabit => $mpLabel): ?>
   <li><?php if(!empty($mpRhythm['done'][$mpHabit])): ?><span class="mp-rhythm-done">✓ <?= htmlspecialchars(fcc_t($mpLabel), ENT_QUOTES, 'UTF-8') ?></span><?php else: ?><form method="post"><?= $token ?><input type="hidden" name="action" value="mp_rhythm"><input type="hidden" name="habit" value="<?= $h($mpHabit) ?>"><button class="fp-button fp-button-outline"><?= htmlspecialchars(fcc_t($mpLabel), ENT_QUOTES, 'UTF-8') ?></button></form><?php endif ?></li>
  <?php endforeach ?>
 </ul>
</section>
<?php /* /Custom code: FC-2026-10-12 */ ?>
