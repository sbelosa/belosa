<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-11: Invitation to the 4CC WhatsApp group for PRO members with confirmed 4CC activity. */
$cc4Uid = (int) ($this->user->user_id ?? 0);
if(!$cc4Uid || !function_exists('fcc_ccv_group_state')) return;
try { $cc4State = fcc_ccv_group_state($cc4Uid); } catch(\Throwable $exception) { return; }
if(empty($cc4State['show_invitation'])) return;
?>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-journey-foundation.css?v=<?= filemtime(THEME_PATH.'assets/css/fcc-journey-foundation.css') ?>">
<aside class="jf-team fp-cc4-group" id="cc4-group" aria-label="<?= htmlspecialchars(fcc_t('4CC WhatsApp grupa'), ENT_QUOTES, 'UTF-8') ?>">
 <span class="jf-team-icon" aria-hidden="true"><?= fcc_partner_icon('people') ?></span>
 <div class="jf-team-copy"><strong><?= htmlspecialchars(fcc_t('Ostvario/la si 4CC. Dobrodošao/la u 4CC grupu.'), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars(fcc_t('Uz PRO i potvrđenu 4CC aktivnost pridruži se grupi suradnika s 4CC na WhatsAppu.'), ENT_QUOTES, 'UTF-8') ?></span></div>
 <div class="jf-team-actions">
  <a class="fp-button fp-button-outline" href="<?= fcc_partner_h($cc4State['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(fcc_t('Pridruži se grupi ↗'), ENT_QUOTES, 'UTF-8') ?></a>
  <form method="post" action="<?= url('partner') ?>"><input type="hidden" name="token" value="<?= fcc_partner_h(\Altum\Csrf::get()) ?>"><input type="hidden" name="action" value="cc4_group_joined"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(16)) ?>"><button class="fp-text-button"><?= htmlspecialchars(fcc_t('Već sam član'), ENT_QUOTES, 'UTF-8') ?></button></form>
 </div>
</aside>
<?php /* /Custom code: FC-2026-10-11 */ ?>
