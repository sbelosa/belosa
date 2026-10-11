<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-11: Administration of the 4CC WhatsApp group: invitation link, members to remove, qualified members not yet in the group. */
if(!\Altum\Authentication::is_admin() || !function_exists('fcc_ccv_group_admin_rows')) return;
$cc4Rows = fcc_ccv_group_admin_rows();
$cc4Url = fcc_ccv_group_url();
$cc4Token = '<input type="hidden" name="token" value="'.fcc_partner_h(\Altum\Csrf::get()).'">';
?>
<section class="fp-panel" id="cc4-group">
 <div class="fp-section-title fp-wrap-heading"><h2><?= htmlspecialchars(fcc_t('4CC WhatsApp grupa'), ENT_QUOTES, 'UTF-8') ?></h2><span class="fp-count"><?= (int) $cc4Rows['members'] ?></span></div>
 <p class="fp-muted"><?= htmlspecialchars(fcc_t('Poziv vide svi suradnici s 4CC aktivnošću potvrđenom u FLP360 u ovom ili prošlom mjesecu, neovisno o paketu. Članstvo potvrđuju sami. Tko izgubi kvalifikaciju, pojavljuje se na popisu za uklanjanje, a poziv mu se ponovno prikazuje kad opet ostvari uvjet.'), ENT_QUOTES, 'UTF-8') ?></p>
 <form method="post" action="<?= url('partner/admin') ?>" class="fp-form"><?= $cc4Token ?><input type="hidden" name="action" value="cc4_group_settings">
  <label><?= htmlspecialchars(fcc_t('Poveznica za pozivnicu u grupu'), ENT_QUOTES, 'UTF-8') ?><input type="url" name="cc4_group_url" maxlength="300" placeholder="https://chat.whatsapp.com/..." value="<?= fcc_partner_h($cc4Url) ?>"></label>
  <small class="fp-muted"><?= htmlspecialchars(fcc_t('Prazno polje isključuje poziv.'), ENT_QUOTES, 'UTF-8') ?></small>
  <button class="fp-button fp-button-outline"><?= htmlspecialchars(fcc_t('Spremi poveznicu'), ENT_QUOTES, 'UTF-8') ?></button>
 </form>
 <h3 class="fp-spaced"><?= htmlspecialchars(fcc_t('Ukloni iz grupe'), ENT_QUOTES, 'UTF-8') ?> (<?= count($cc4Rows['remove']) ?>)</h3>
 <?php if(!$cc4Rows['remove']): ?><p class="fp-muted"><?= htmlspecialchars(fcc_t('Svi označeni članovi i dalje su kvalificirani.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
 <?php foreach($cc4Rows['remove'] as $cc4Member): ?><form method="post" action="<?= url('partner/admin') ?>" class="fp-task-row"><?= $cc4Token ?><input type="hidden" name="action" value="cc4_group_removed"><input type="hidden" name="member_id" value="<?= (int) $cc4Member['user_id'] ?>"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(16)) ?>"><span><?= fcc_partner_h($cc4Member['name']) ?></span><button class="fp-text-button"><?= htmlspecialchars(fcc_t('Uklonjen/a iz grupe'), ENT_QUOTES, 'UTF-8') ?></button></form><?php endforeach ?>
 <details class="fp-spaced"><summary><?= htmlspecialchars(fcc_t('Kvalificirani, još nisu potvrdili članstvo'), ENT_QUOTES, 'UTF-8') ?> (<?= count($cc4Rows['invited']) ?>)</summary>
  <ul class="fp-muted"><?php foreach($cc4Rows['invited'] as $cc4Member): ?><li><?= fcc_partner_h($cc4Member['name']) ?></li><?php endforeach ?></ul>
 </details>
</section>
<?php /* /Custom code: FC-2026-10-11 */ ?>
