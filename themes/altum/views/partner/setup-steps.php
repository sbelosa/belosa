<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-13: "Postavi FCC" lives behind the bell instead of separate cards on every page.
 * Three short steps for a new member: the team group, notifications on this device and FCC on the home screen.
 * The browser decides which device steps apply here (fcc-partner-setup.js); finished steps show a tick and the
 * list disappears when nothing is left. Without JavaScript only the team step shows, as before. */
$setupUid=(int)($this->user->user_id??0);
if($setupUid<=0)return;
$setupTeamOpen=function_exists('fcc_journey_team_group_joined')&&fcc_jp_schema()&&!fcc_journey_team_group_joined($setupUid);
$setupT=static fn(string $text):string=>htmlspecialchars(fcc_t($text),ENT_QUOTES,'UTF-8');
?>
<section class="fp-panel fp-setup" id="fcc-setup" data-fcc-setup data-team-open="<?= $setupTeamOpen?'1':'0' ?>" aria-labelledby="fcc-setup-title" <?= $setupTeamOpen?'':'hidden' ?>>
 <div class="fp-setup-head"><h2 id="fcc-setup-title"><?= $setupT('Postavi FCC') ?></h2><span class="fp-setup-progress" data-setup-progress data-template="<?= $setupT('{done} od {total} gotovo') ?>"></span></div>
 <p class="fp-muted"><?= $setupT('Tri kratka koraka da ništa ne propustiš. Svaki napraviš jednom.') ?></p>
 <ol class="fp-setup-list">
  <li class="fp-setup-step" id="team-group" data-setup-step="team" data-state="<?= $setupTeamOpen?'open':'done' ?>">
   <span class="fp-setup-icon" aria-hidden="true"><?= fcc_partner_icon('people') ?></span>
   <div class="fp-setup-copy"><strong><?= $setupT('Timska WhatsApp grupa') ?></strong><span><?= $setupT('Timske najave i podrška na WhatsAppu.') ?></span></div>
   <div class="fp-setup-actions">
    <a class="fp-button" href="<?= fcc_partner_h(fcc_journey_team_group_url()) ?>" target="_blank" rel="noopener noreferrer"><?= $setupT('Pridruži se grupi ↗') ?></a>
    <form method="post" action="<?= url('partner') ?>"><input type="hidden" name="token" value="<?= fcc_partner_h(\Altum\Csrf::get()) ?>"><input type="hidden" name="action" value="team_group_joined"><input type="hidden" name="confirmed" value="1"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(16)) ?>"><button class="fp-text-button"><?= $setupT('Već sam član') ?></button></form>
   </div>
   <span class="fp-setup-done"><?= fcc_partner_icon('check') ?> <?= $setupT('Gotovo') ?></span>
  </li>
  <li class="fp-setup-step" data-setup-step="push" data-partner-push hidden>
   <span class="fp-setup-icon" aria-hidden="true"><?= fcc_partner_icon('bell') ?></span>
   <div class="fp-setup-copy"><strong><?= $setupT('Obavijesti na uređaju') ?></strong><span data-setup-push-copy data-copy="<?= $setupT('Dobij podsjetnik za webinar i obavijest o novom kontaktu.') ?>" data-ios-copy="<?= $setupT('Za obavijesti na iPhoneu dodaj FCC na početni zaslon i otvori njegovu ikonu.') ?>" data-blocked-copy="<?= $setupT('Dopuštenje za obavijesti je isključeno u pregledniku. Možeš ga promijeniti u postavkama ove stranice.') ?>"><?= $setupT('Dobij podsjetnik za webinar i obavijest o novom kontaktu.') ?></span><span class="fp-setup-status" role="status"></span></div>
   <div class="fp-setup-actions"><button type="button" class="fp-button fp-button-outline" data-push-enable><?= $setupT('Uključi obavijesti') ?></button></div>
   <span class="fp-setup-done"><?= fcc_partner_icon('check') ?> <?= $setupT('Gotovo') ?></span>
  </li>
  <li class="fp-setup-step" data-setup-step="install" hidden>
   <span class="fp-setup-icon" aria-hidden="true"><?= fcc_partner_icon('home') ?></span>
   <div class="fp-setup-copy"><strong><?= $setupT('Dodaj FCC na početni zaslon') ?></strong><span><?= $setupT('Jedan dodir do kartice, kontakata i današnjeg koraka.') ?></span></div>
   <div class="fp-setup-actions"><a class="fp-button fp-button-outline" data-setup-install href="<?= url('partner/install') ?>"><?= $setupT('Dodaj') ?></a></div>
   <span class="fp-setup-done"><?= fcc_partner_icon('check') ?> <?= $setupT('Gotovo') ?></span>
  </li>
 </ol>
</section>
<script src="<?= ASSETS_FULL_URL ?>js/fcc-partner-setup.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-partner-setup.js') ?>" defer></script>
<?php /* /Custom code: FC-2026-10-13 */ ?>
