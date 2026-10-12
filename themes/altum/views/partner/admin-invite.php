<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: administration of the Pozivnica u tim: release to all members, results and Start pack review dates. */
if(!\Altum\Authentication::is_admin() || !function_exists('fcc_invite_admin_summary') || !function_exists('fcc_business_landing_release')) return;
$inviteRelease = fcc_business_landing_release();
$inviteSummary = fcc_invite_admin_summary();
$inviteReview = fcc_invite_starter_review_rows();
$inviteToken = '<input type="hidden" name="token" value="'.fcc_partner_h(\Altum\Csrf::get()).'">';
$inviteText = static fn(string $text): string => htmlspecialchars(fcc_t($text), ENT_QUOTES, 'UTF-8');
?>
<section class="fp-panel" id="invite-admin">
 <div class="fp-section-title fp-wrap-heading"><h2><?= $inviteText('Pozivnica u tim') ?></h2><span class="fp-count"><?= (int) $inviteSummary['invites'] ?></span></div>
 <?php if($inviteRelease === 'all'): ?>
  <p><?= $inviteText('Pozivnica je otvorena svim suradnicima s aktivnom karticom i Forever ID-jem.') ?></p>
 <?php else: ?>
  <p><?= $inviteText('Nove prezentacije trenutačno vide samo administratori. Suradnici još ne mogu slati Pozivnicu u tim, a posjetitelji koji nisu administratori dobivaju grešku.') ?></p>
 <?php endif ?>
 <p class="fp-muted"><?= htmlspecialchars(sprintf(fcc_t('Pripremljenih pozivnica: %d. Registracija preko pozivnica: %d, od toga potvrđenih u timu: %d.'), (int) $inviteSummary['invites'], (int) $inviteSummary['registrations'], (int) $inviteSummary['approved']), ENT_QUOTES, 'UTF-8') ?></p>
 <form method="post" action="<?= url('partner/admin') ?>" class="fp-form"><?= $inviteToken ?><input type="hidden" name="action" value="business_release"><input type="hidden" name="mode" value="<?= $inviteRelease === 'all' ? 'admin' : 'all' ?>">
  <label class="fp-check"><input type="checkbox" name="confirm" value="1" required> <?= $inviteRelease === 'all' ? $inviteText('Želim pozivnicu vratiti samo na administratorski pregled.') : $inviteText('Pregledao/la sam pozivnicu i želim je otvoriti svim suradnicima.') ?></label>
  <button class="fp-button<?= $inviteRelease === 'all' ? ' fp-button-outline' : '' ?>"><?= $inviteRelease === 'all' ? $inviteText('Vrati na administratorski pregled') : $inviteText('Otvori svim suradnicima') ?></button>
 </form>
 <h3 class="fp-spaced"><?= $inviteText('Provjereni podaci o Start paketu') ?></h3>
 <p class="fp-muted"><?= $inviteText('Cijena, 2 CC i uvjet do Assistant Supervisora prikazuju se samo dok je provjera službene stranice mlađa od 30 dana. Kada se rok približi, zatraži novu provjeru od Claudea ili Codexa.') ?></p>
 <ul class="fp-muted">
  <?php foreach($inviteReview as $inviteRow): ?>
   <li><strong><?= fcc_partner_h(fcc_business_country_label($inviteRow['country'], 'hr')) ?></strong>: <?php
    if($inviteRow['days_left'] < 0) echo $inviteText('podaci se više ne prikazuju, potrebna je nova provjera');
    else echo htmlspecialchars(sprintf(fcc_t('prikazuje se do %s (još %d dana)'), gmdate('d.m.Y.', $inviteRow['visible_until']), (int) $inviteRow['days_left']), ENT_QUOTES, 'UTF-8');
   ?></li>
  <?php endforeach ?>
 </ul>
</section>
<?php /* /Custom code: FC-2026-10-12 */ ?>
