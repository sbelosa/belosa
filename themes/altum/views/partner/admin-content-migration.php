<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: one click content migrations (Moj put instead of the 120 day plan, removal of the official website notice) with backup and revert. */
if(!\Altum\Authentication::is_admin() || !function_exists('fcc_cm_status')) return;
$cmResult = $_SESSION['fcc_cm_result'] ?? null; unset($_SESSION['fcc_cm_result']);
$cmToken = '<input type="hidden" name="token" value="'.fcc_partner_h(\Altum\Csrf::get()).'">';
$cmTableNames = ['blog_posts'=>fcc_t('članci'), 'pages'=>fcc_t('stranice'), 'vip_funnel_cards'=>fcc_t('kartice vodiča'), 'links'=>fcc_t('kartice'), 'biolinks_blocks'=>fcc_t('izmjene blokova')];
$cmSections = [
 'mojput' => ['id'=>'content-migration', 'title'=>fcc_t('Ažuriranje sadržaja: Moj put'), 'text'=>fcc_t('Zamjenjuje 120-dnevni plan programom Moj put u objavljenim člancima na 13 jezika, na stranici Kako funkcionira sustav i u tvom vodiču. Prije svake izmjene sprema se stara vrijednost, pa se ažuriranje može vratiti.'), 'done'=>fcc_t('Sav sadržaj već govori o programu Moj put.')],
 'aloe' => ['id'=>'content-migration-aloe', 'title'=>fcc_t('Nova kartica Aloe za netaknute kartice'), 'text'=>fcc_t('Prebacuje na novu karticu u stilu Aloe one kartice koje imaju samo osnovne blokove iz registracije i koje nitko nije uređivao. Blokovi, adresa, QR kod i preporuka ostaju isti, a osnovni blokovi koje je paket isključio ponovno se uključuju. Vlasnik dobiva obavijest i u uređivaču se može vratiti na staru karticu. Prije svake izmjene sprema se stara vrijednost, pa se prijelaz može vratiti za sve kartice koje vlasnik u međuvremenu nije mijenjao.'), 'done'=>fcc_t('Sve netaknute kartice već koriste novu karticu.')],
 'notice' => ['id'=>'content-migration-notice', 'title'=>fcc_t('Ažuriranje sadržaja: napomena o službenoj stranici'), 'text'=>fcc_t('Uklanja rečenicu da FCC nije službena stranica Forever Living Products iz članaka i stranica na 13 jezika. Rečenica da je FCC neovisan sustav i podaci o kupnji u službenom web shopu ostaju. Prije svake izmjene sprema se stara vrijednost, pa se ažuriranje može vratiti.'), 'done'=>fcc_t('Napomena je uklonjena iz svih članaka i stranica.')],
];
?>
<style>.fp-cm-form{display:grid!important;gap:12px;justify-items:start;margin-top:12px}.fp-cm-confirm{display:flex!important;flex-direction:row!important;gap:10px;align-items:flex-start!important;width:auto!important;max-width:640px;line-height:1.5;font-weight:600}.fp-cm-confirm input[type=checkbox]{margin:3px 0 0!important;flex:0 0 18px!important;width:18px!important;height:18px!important;min-height:0!important}</style>
<?php
foreach($cmSections as $cmMigration => $cmCopy):
 try { $cmStatus = fcc_cm_status($cmMigration); } catch(\Throwable $e) { error_log('Content migration status: '.get_class($e)); continue; }
 $cmMine = $cmResult && ($cmResult['migration'] ?? 'mojput') === $cmMigration;
?>
<section class="fp-panel" id="<?= $cmCopy['id'] ?>">
 <div class="fp-section-title fp-wrap-heading"><h2><?= htmlspecialchars($cmCopy['title'], ENT_QUOTES, 'UTF-8') ?></h2><span class="fp-count"><?= (int) $cmStatus['pending'] ?></span></div>
 <p class="fp-muted"><?= htmlspecialchars($cmCopy['text'], ENT_QUOTES, 'UTF-8') ?></p>
 <?php if($cmMine): ?>
  <p class="fp-notice" role="status"><?= htmlspecialchars($cmResult['action'] === 'content_migration_apply'
   ? sprintf(fcc_t('Primijenjeno izmjena: %d. Preskočeno jer je sadržaj u međuvremenu promijenjen: %d.'), (int) $cmResult['applied'], (int) $cmResult['skipped'])
   : sprintf(fcc_t('Vraćeno izmjena: %d. Zadržano jer je sadržaj kasnije uređivan: %d.'), (int) $cmResult['reverted'], (int) $cmResult['kept']), ENT_QUOTES, 'UTF-8') ?></p>
 <?php endif ?>
 <?php if($cmStatus['pending'] > 0): ?>
  <p><?= htmlspecialchars(fcc_t('Spremno za primjenu:'), ENT_QUOTES, 'UTF-8') ?> <?php $cmParts = []; foreach($cmStatus['pending_tables'] as $cmTable => $cmCount) $cmParts[] = (int) $cmCount.' '.($cmTableNames[$cmTable] ?? $cmTable); echo htmlspecialchars(implode(', ', $cmParts), ENT_QUOTES, 'UTF-8'); ?></p>
  <details class="fp-spaced"><summary><?= htmlspecialchars(fcc_t('Popis sadržaja'), ENT_QUOTES, 'UTF-8') ?> (<?= count($cmStatus['pending_labels']) ?>)</summary><ul class="fp-muted"><?php foreach($cmStatus['pending_labels'] as $cmLabel): ?><li><?= fcc_partner_h($cmLabel) ?></li><?php endforeach ?></ul></details>
  <form method="post" action="<?= url('partner/admin') ?>" class="fp-form fp-cm-form"><?= $cmToken ?><input type="hidden" name="action" value="content_migration_apply"><input type="hidden" name="migration" value="<?= $cmMigration ?>">
   <label class="fp-cm-confirm"><input type="checkbox" name="confirm" value="1" required> <?= htmlspecialchars(fcc_t('Pregledao/la sam popis i želim primijeniti ažuriranje.'), ENT_QUOTES, 'UTF-8') ?></label>
   <button class="fp-button"><?= htmlspecialchars(fcc_t('Primijeni ažuriranje'), ENT_QUOTES, 'UTF-8') ?></button>
  </form>
 <?php else: ?>
  <p><?= htmlspecialchars($cmCopy['done'], ENT_QUOTES, 'UTF-8') ?></p>
 <?php endif ?>
 <?php if($cmStatus['applied'] > 0): ?>
  <details class="fp-spaced"><summary><?= htmlspecialchars(fcc_t('Vrati prethodni sadržaj'), ENT_QUOTES, 'UTF-8') ?></summary>
   <p class="fp-muted"><?= htmlspecialchars(sprintf(fcc_t('Primijenjeno izmjena: %d. Vraćaju se samo vrijednosti koje nakon toga nitko nije uređivao.'), (int) $cmStatus['applied']), ENT_QUOTES, 'UTF-8') ?></p>
   <form method="post" action="<?= url('partner/admin') ?>" class="fp-form fp-cm-form"><?= $cmToken ?><input type="hidden" name="action" value="content_migration_revert"><input type="hidden" name="migration" value="<?= $cmMigration ?>">
    <label class="fp-cm-confirm"><input type="checkbox" name="confirm" value="1" required> <?= htmlspecialchars(fcc_t('Želim vratiti prethodni sadržaj.'), ENT_QUOTES, 'UTF-8') ?></label>
    <button class="fp-button fp-button-outline"><?= htmlspecialchars(fcc_t('Vrati'), ENT_QUOTES, 'UTF-8') ?></button>
   </form>
  </details>
 <?php endif ?>
</section>
<?php endforeach ?>
<?php /* /Custom code: FC-2026-10-12 */ ?>
