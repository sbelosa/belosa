<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: the member's own order, waiting tasks and track change in the 90 step overview. */
if(!function_exists('fcc_mp_order')) return;
$mpNow = fcc_j90_now();
$mpTrack = fcc_mp_track($uid);
$mpDone = fcc_mp_done_days($n['rows']);
$mpCatalog = fcc_localize_copy(fcc_jp_catalog())['tasks'];
$mpUpcoming = array_slice(array_values(array_filter(fcc_mp_order($mpTrack), static function($day) use ($mpDone, $uid, $mpNow) {
 if(isset($mpDone[$day])) return false;
 $requirement = fcc_mp_task_requirement($day);
 return !in_array($requirement, FCC_MP_CONTEXTS, true) || fcc_mp_context_ready($uid, $requirement, $mpNow);
})), 0, 8);
$mpWaiting = fcc_mp_waiting($uid, $n['rows'], $mpNow);
?>
<section class="fp-panel mp-track-overview">
 <span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('TVOJ REDOSLIJED'), ENT_QUOTES, 'UTF-8') ?></span>
 <h2><?= $mpTrack ? $h(fcc_t(fcc_mp_tracks()[$mpTrack]['name'])) : htmlspecialchars(fcc_t('Zajednički početak'), ENT_QUOTES, 'UTF-8') ?></h2>
 <p><?= htmlspecialchars($mpTrack ? fcc_t(fcc_mp_tracks()[$mpTrack]['description']) : fcc_t('Prvih 15 koraka isti su za sve. Nakon njih biraš stazu: Koristim i preporučujem, Gradim dodatni prihod ili Gradim tim.'), ENT_QUOTES, 'UTF-8') ?></p>
 <?php if($mpUpcoming): ?>
  <h3 class="fp-spaced"><?= htmlspecialchars(fcc_t('Sljedeći koraci'), ENT_QUOTES, 'UTF-8') ?></h3>
  <ol class="mp-upcoming">
   <?php foreach($mpUpcoming as $mpDay): ?>
    <li><?= $h($mpCatalog[$mpDay - 1]['title'] ?? '') ?></li>
   <?php endforeach ?>
  </ol>
 <?php endif ?>
 <?php if($mpWaiting): ?>
  <h3 class="fp-spaced"><?= htmlspecialchars(fcc_t('Koraci koji čekaju pravi trenutak'), ENT_QUOTES, 'UTF-8') ?></h3>
  <ul class="mp-waiting"><?php foreach($mpWaiting as $mpRequirement => $mpCount): ?><li><?= htmlspecialchars(fcc_t(fcc_mp_requirement_labels()[$mpRequirement]), ENT_QUOTES, 'UTF-8') ?> (<?= (int)$mpCount ?>)</li><?php endforeach ?></ul>
 <?php endif ?>
</section>
<?php if($mpTrack || !empty($n['needs_track']) || count($n['rows']) >= count(FCC_MP_COMMON)) require THEME_PATH.'views/partner/journey-track-choice.php'; ?>
<?php /* /Custom code: FC-2026-10-12 */ ?>
