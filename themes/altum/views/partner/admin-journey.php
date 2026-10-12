<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: Moj put overview for administrators. */
if(!\Altum\Authentication::is_admin() || !function_exists('fcc_mp_admin_overview')) return;
try { $mpAdmin = fcc_mp_admin_overview(); } catch(\Throwable $e) { error_log('Moj put admin overview: '.get_class($e)); return; }
$mpText = static fn(string $text): string => htmlspecialchars(fcc_t($text), ENT_QUOTES, 'UTF-8');
?>
<section class="fp-panel" id="journey-overview">
 <div class="fp-section-title fp-wrap-heading"><h2><?= $mpText('Moj put: kako ga tim koristi') ?></h2><span class="fp-count"><?= (int)$mpAdmin['active_week'] ?></span></div>
 <p class="fp-muted"><?= htmlspecialchars(sprintf(fcc_t('Odobrenih suradnika: %d. Otvorilo FCC: %d. Napravilo barem jedan korak: %d. Aktivnih u zadnjih 7 dana: %d.'), $mpAdmin['approved'], $mpAdmin['entered'], $mpAdmin['started'], $mpAdmin['active_week']), ENT_QUOTES, 'UTF-8') ?></p>
 <h3 class="fp-spaced"><?= $mpText('Dovršeni koraci') ?></h3>
 <p><?php $mpParts = []; foreach($mpAdmin['buckets'] as $mpBucket => $mpCount) $mpParts[] = $mpBucket.': '.(int)$mpCount; echo $h(implode(' · ', $mpParts)); ?></p>
 <h3 class="fp-spaced"><?= $mpText('Odabrane staze') ?></h3>
 <p><?php $mpParts = []; foreach($mpAdmin['tracks'] as $mpKey => $mpCount) $mpParts[] = fcc_t(fcc_mp_tracks()[$mpKey]['name']).': '.(int)$mpCount; echo $h(implode(' · ', $mpParts)); ?></p>
 <h3 class="fp-spaced"><?= $mpText('Povratna informacija u zadnjih 7 dana') ?></h3>
 <p><?= htmlspecialchars(sprintf(fcc_t('Lako: %d · Teško: %d · Traže pomoć: %d · Udio vježbi: %d posto · Poznatih koraka: %d'), $mpAdmin['easy_week'], $mpAdmin['hard_week'], $mpAdmin['help_week'], $mpAdmin['practice_share'], $mpAdmin['known']), ENT_QUOTES, 'UTF-8') ?></p>
 <?php if($mpAdmin['stalled']): ?>
  <h3 class="fp-spaced"><?= $mpText('Gdje ljudi zastaju') ?></h3>
  <p class="fp-muted"><?= $mpText('Zadnji spremljeni korak suradnika koji tri dana ili više nisu nastavili.') ?></p>
  <ul class="fp-muted"><?php foreach($mpAdmin['stalled'] as $mpRow): ?><li><?= $h(fcc_t($mpRow['title'])) ?>: <?= (int)$mpRow['count'] ?></li><?php endforeach ?></ul>
 <?php endif ?>
 <details class="fp-spaced"><summary><?= $mpText('Prekretnice koje smijemo spomenuti') ?> (<?= count($mpAdmin['shared_milestones']) ?>)</summary>
  <?php if(!$mpAdmin['shared_milestones']): ?><p class="fp-muted"><?= $mpText('Zasad nema potvrđenih prekretnica u zadnjih 30 dana.') ?></p><?php endif ?>
  <ul><?php foreach($mpAdmin['shared_milestones'] as $mpRow): ?><li><?= $h($mpRow['name']) ?>: <?= $h(fcc_mp_milestone_label($mpRow['milestone'])) ?> · <?= $h(substr((string)$mpRow['created_at'], 0, 10)) ?></li><?php endforeach ?></ul>
 </details>
</section>
<?php /* /Custom code: FC-2026-10-12 */ ?>
