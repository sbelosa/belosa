<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: one click content migration (Moj put instead of the 120 day plan) with backup and revert. */
if(!\Altum\Authentication::is_admin() || !function_exists('fcc_cm_status')) return;
try { $cmStatus = fcc_cm_status(); } catch(\Throwable $e) { error_log('Content migration status: '.get_class($e)); return; }
$cmResult = $_SESSION['fcc_cm_result'] ?? null; unset($_SESSION['fcc_cm_result']);
$cmToken = '<input type="hidden" name="token" value="'.fcc_partner_h(\Altum\Csrf::get()).'">';
$cmTableNames = ['blog_posts'=>fcc_t('članci'), 'pages'=>fcc_t('stranice'), 'vip_funnel_cards'=>fcc_t('kartice vodiča')];
?>
<section class="fp-panel" id="content-migration">
 <div class="fp-section-title fp-wrap-heading"><h2><?= htmlspecialchars(fcc_t('Ažuriranje sadržaja: Moj put'), ENT_QUOTES, 'UTF-8') ?></h2><span class="fp-count"><?= (int) $cmStatus['pending'] ?></span></div>
 <p class="fp-muted"><?= htmlspecialchars(fcc_t('Zamjenjuje 120-dnevni plan programom Moj put u objavljenim člancima na 13 jezika, na stranici Kako funkcionira sustav i u tvom vodiču. Prije svake izmjene sprema se stara vrijednost, pa se ažuriranje može vratiti.'), ENT_QUOTES, 'UTF-8') ?></p>
 <?php if($cmResult): ?>
  <p class="fp-notice" role="status"><?= htmlspecialchars($cmResult['action'] === 'content_migration_apply'
   ? sprintf(fcc_t('Primijenjeno izmjena: %d. Preskočeno jer je sadržaj u međuvremenu promijenjen: %d.'), (int) $cmResult['applied'], (int) $cmResult['skipped'])
   : sprintf(fcc_t('Vraćeno izmjena: %d. Zadržano jer je sadržaj kasnije uređivan: %d.'), (int) $cmResult['reverted'], (int) $cmResult['kept']), ENT_QUOTES, 'UTF-8') ?></p>
 <?php endif ?>
 <?php if($cmStatus['pending'] > 0): ?>
  <p><?= htmlspecialchars(fcc_t('Spremno za primjenu:'), ENT_QUOTES, 'UTF-8') ?> <?php $cmParts = []; foreach($cmStatus['pending_tables'] as $cmTable => $cmCount) $cmParts[] = (int) $cmCount.' '.($cmTableNames[$cmTable] ?? $cmTable); echo htmlspecialchars(implode(', ', $cmParts), ENT_QUOTES, 'UTF-8'); ?></p>
  <details class="fp-spaced"><summary><?= htmlspecialchars(fcc_t('Popis sadržaja'), ENT_QUOTES, 'UTF-8') ?> (<?= count($cmStatus['pending_labels']) ?>)</summary><ul class="fp-muted"><?php foreach($cmStatus['pending_labels'] as $cmLabel): ?><li><?= fcc_partner_h($cmLabel) ?></li><?php endforeach ?></ul></details>
  <form method="post" action="<?= url('partner/admin') ?>" class="fp-form"><?= $cmToken ?><input type="hidden" name="action" value="content_migration_apply">
   <label class="fp-check"><input type="checkbox" name="confirm" value="1" required> <?= htmlspecialchars(fcc_t('Pregledao/la sam popis i želim primijeniti ažuriranje.'), ENT_QUOTES, 'UTF-8') ?></label>
   <button class="fp-button"><?= htmlspecialchars(fcc_t('Primijeni ažuriranje'), ENT_QUOTES, 'UTF-8') ?></button>
  </form>
 <?php else: ?>
  <p><?= htmlspecialchars(fcc_t('Sav sadržaj već govori o programu Moj put.'), ENT_QUOTES, 'UTF-8') ?></p>
 <?php endif ?>
 <?php if($cmStatus['applied'] > 0): ?>
  <details class="fp-spaced"><summary><?= htmlspecialchars(fcc_t('Vrati prethodni sadržaj'), ENT_QUOTES, 'UTF-8') ?></summary>
   <p class="fp-muted"><?= htmlspecialchars(sprintf(fcc_t('Primijenjeno izmjena: %d. Vraćaju se samo vrijednosti koje nakon toga nitko nije uređivao.'), (int) $cmStatus['applied']), ENT_QUOTES, 'UTF-8') ?></p>
   <form method="post" action="<?= url('partner/admin') ?>" class="fp-form"><?= $cmToken ?><input type="hidden" name="action" value="content_migration_revert">
    <label class="fp-check"><input type="checkbox" name="confirm" value="1" required> <?= htmlspecialchars(fcc_t('Želim vratiti prethodni sadržaj.'), ENT_QUOTES, 'UTF-8') ?></label>
    <button class="fp-button fp-button-outline"><?= htmlspecialchars(fcc_t('Vrati'), ENT_QUOTES, 'UTF-8') ?></button>
   </form>
  </details>
 <?php endif ?>
</section>
<?php /* /Custom code: FC-2026-10-12 */ ?>
