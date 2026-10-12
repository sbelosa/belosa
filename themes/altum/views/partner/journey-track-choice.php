<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: choose or change the Moj put track. Used after the shared first 15 tasks and in the overview. */
$mpCurrent = function_exists('fcc_mp_track') ? fcc_mp_track($uid) : null;
$mpSelected = $mpCurrent ?? fcc_mp_suggested_track($uid);
$mpChange = $mpCurrent !== null;
$mpText = static fn(string $text): string => htmlspecialchars(fcc_t($text), ENT_QUOTES, 'UTF-8');
?>
<section class="fp-panel mp-tracks" id="mp-track">
 <span class="fp-eyebrow"><?= $mpText($mpChange ? 'TVOJA STAZA' : 'ODABERI SVOJU STAZU') ?></span>
 <h2><?= $mpText($mpChange ? 'Želiš li promijeniti smjer?' : 'Zajednički početak je iza tebe. Kamo dalje?') ?></h2>
 <p><?= $mpText('Odaberi stazu koja odgovara onome što želiš. Svih 90 koraka ostaje isto, mijenja se redoslijed. Stazu možeš promijeniti kad god želiš, a napredak ostaje sačuvan.') ?></p>
 <?php if(!$mpChange && array_diff(FCC_MP_COMMON, array_keys(fcc_mp_done_days($n['rows'] ?? [])))): ?><p class="mp-note"><?= $mpText('Zajednički korak koji čeka pravi trenutak, primjerice prvog gosta ili kupca, otvorit će se sam kada se to dogodi.') ?></p><?php endif ?>
 <form method="post" class="mp-track-form"><?= $token ?><input type="hidden" name="action" value="mp_track">
  <?php foreach(fcc_mp_tracks() as $mpKey => $mpTrack): ?>
   <label class="mp-track">
    <input type="radio" name="track" value="<?= $h($mpKey) ?>" required <?= $mpKey === $mpSelected ? 'checked' : '' ?>>
    <span>
     <strong><?= $mpText($mpTrack['name']) ?><?php if($mpKey === $mpCurrent): ?> <em><?= $mpText('Tvoja staza') ?></em><?php elseif(!$mpChange && $mpKey === $mpSelected): ?> <em><?= $mpText('Preporučeno za tebe') ?></em><?php endif ?></strong>
     <small><?= $mpText($mpTrack['description']) ?></small>
    </span>
   </label>
  <?php endforeach ?>
  <button class="fp-button"><?= $mpText($mpChange ? 'Spremi promjenu staze' : 'Spremi stazu i nastavi →') ?></button>
 </form>
</section>
<?php /* /Custom code: FC-2026-10-12 */ ?>
