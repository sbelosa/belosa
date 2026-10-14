<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: one click feedback after a saved step. */
if(!function_exists('fcc_mp_feedback_given') || fcc_mp_feedback_given($uid)) return;
?>
<?php /* Custom code: FC-2026-10-14: a replay-safe key distinguishes each new feedback submission. */ ?>
<form method="post" class="mp-feedback"><?= $token.$request ?><input type="hidden" name="action" value="mp_feedback">
<?php /* /Custom code: FC-2026-10-14 */ ?>
 <strong><?= htmlspecialchars(fcc_t('Kako je prošao današnji korak?'), ENT_QUOTES, 'UTF-8') ?></strong>
 <div class="fp-actions">
  <button class="fp-button fp-button-outline" name="answer" value="easy"><?= htmlspecialchars(fcc_t('Lako'), ENT_QUOTES, 'UTF-8') ?></button>
  <button class="fp-button fp-button-outline" name="answer" value="hard"><?= htmlspecialchars(fcc_t('Teško'), ENT_QUOTES, 'UTF-8') ?></button>
  <button class="fp-button fp-button-outline" name="answer" value="help"><?= htmlspecialchars(fcc_t('Trebam pomoć'), ENT_QUOTES, 'UTF-8') ?></button>
 </div>
 <small><?= htmlspecialchars(fcc_t('Odgovor Trebam pomoć javlja tvojem sponzoru.'), ENT_QUOTES, 'UTF-8') ?></small>
</form>
<?php /* /Custom code: FC-2026-10-12 */ ?>
