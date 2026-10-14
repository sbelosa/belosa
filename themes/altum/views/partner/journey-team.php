<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: every sponsor sees the Moj put progress of their direct members. */
$uid = (int)$this->user->user_id;
$mpOverview = fcc_mp_sponsor_overview($uid);
$mpText = static fn(string $text): string => htmlspecialchars(fcc_t($text), ENT_QUOTES, 'UTF-8');
$mpGroups = [
 'help' => ['Trebaju tvoju pomoć', 'Javili su da su zapeli ili im je korak bio težak. Javi se danas.'],
 'inactive' => ['Stoje tri dana ili više', 'Kratka poruka podrške često je dovoljna da nastave.'],
 'not_started' => ['Imaju program, a još nisu krenuli', 'Pomozi im napraviti prvi korak, traje oko 20 minuta.'],
 'not_entered' => ['Još nisu otvorili FCC', 'Pošalji im poveznicu na prvi korak.'],
 'progress' => ['Napreduju', 'Pohvali konkretan trud. To ljudima puno znači.'],
];
$mpVisible = 10;
$mpIdle = static function(?int $days): string {
 if($days === null) return fcc_t('Još nema spremljenog koraka');
 if($days === 0) return fcc_t('Zadnji korak: danas');
 if($days === 1) return fcc_t('Zadnji korak: jučer');
 return sprintf(fcc_t('Zadnji korak: prije %d dana'), $days);
};
?>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-journey-pilot.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-journey-pilot.css') ?>">
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-journey-tracks.css?v=<?= filemtime(ASSETS_PATH.'css/fcc-journey-tracks.css') ?>">
<script defer src="<?= ASSETS_FULL_URL ?>js/fcc-journey-tracks.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-journey-tracks.js') ?>"></script>
<div class="jp-page mp-team-page">
 <header class="jp-heading"><div><span class="fp-eyebrow"><?= $mpText('MOJ PUT · MOJI SURADNICI') ?></span><h1><?= $mpText('Napredak tvojih suradnika') ?></h1></div></header>
 <nav class="jp-tabs"><a href="<?= url('partner?view=step') ?>"><?= $mpText('Moj korak') ?></a><a href="<?= url('partner?view=team-progress') ?>" aria-current="page"><?= $mpText('Moji suradnici') ?></a></nav>
 <?php if(!$mpOverview['total']): ?>
  <section class="fp-panel"><h2><?= $mpText('Još nemaš izravnih suradnika u FCC-u.') ?></h2><p><?= $mpText('Kada se netko registrira uz tebe kao sponzora i FCC potvrdi vezu, ovdje ćeš vidjeti napredak te osobe.') ?></p><a class="fp-button" href="<?= url('partner-marketing') ?>"><?= $mpText('Otvori Pozivnicu u tim →') ?></a></section>
 <?php else: ?>
  <section class="fp-panel mp-team-totals">
   <div><strong><?= (int)$mpOverview['total'] ?></strong><span><?= $mpText('izravnih suradnika') ?></span></div>
   <div><strong><?= (int)$mpOverview['in_program'] ?></strong><span><?= $mpText('u programu Moj put') ?></span></div>
   <div><strong><?= (int)$mpOverview['active_week'] ?></strong><span><?= $mpText('aktivnih ovaj tjedan') ?></span></div>
   <div><strong><?= count($mpOverview['groups']['help']) ?></strong><span><?= $mpText('traži pomoć') ?></span></div>
  </section>
  <?php foreach($mpGroups as $mpKey => [$mpTitle, $mpHint]): if(!$mpOverview['groups'][$mpKey]) continue; ?>
   <section class="fp-panel mp-team-group mp-team-<?= $h($mpKey) ?>">
    <div class="fp-section-title"><h2><?= $mpText($mpTitle) ?></h2><span class="fp-count"><?= count($mpOverview['groups'][$mpKey]) ?></span></div>
    <p class="fp-muted"><?= $mpText($mpHint) ?></p>
    <?php /* The first ten stay visible, the rest opens on request, so long groups do not flood the page. */ ?>
    <?php foreach([array_slice($mpOverview['groups'][$mpKey], 0, $mpVisible), array_slice($mpOverview['groups'][$mpKey], $mpVisible)] as $mpPart => $mpMembers): if(!$mpMembers) continue; ?>
    <?php if($mpPart === 1): ?><details class="mp-team-more"><summary><span class="mp-team-more-open"><?= sprintf($mpText('Prikaži još %d'), count($mpMembers)) ?></span><span class="mp-team-more-close"><?= $mpText('Prikaži manje') ?></span></summary><?php endif ?>
    <ul class="mp-member-list">
     <?php foreach($mpMembers as $mpMember): $mpMessage = fcc_mp_member_message($mpKey, $mpMember); $mpPhone = $mpMember['phone'] !== '' ? ltrim(fcc_partner_contact_phone($mpMember['phone'], true), '+') : ''; ?>
      <li class="mp-member">
       <div class="mp-member-main">
        <strong><?= $h($mpMember['name']) ?></strong>
        <small><?php if($mpMember['cycle']): ?><?= sprintf($mpText('Dovršeno %d od 90'), (int)$mpMember['completed']) ?><?php if($mpMember['track']): ?> · <?= $mpText(fcc_mp_tracks()[$mpMember['track']]['name']) ?><?php endif ?> · <?= $h($mpIdle($mpMember['days_idle'])) ?><?php else: ?><?= $mpText('Moj put još nije otvoren') ?><?php endif ?></small>
        <?php if($mpMember['cycle']): ?><progress max="90" value="<?= (int)$mpMember['completed'] ?>" aria-label="<?= $mpText('Dovršeni koraci') ?>"></progress><?php endif ?>
        <?php if($mpKey === 'help' && ($mpMember['help_title'] || $mpMember['last_title'])): ?><small class="mp-member-step"><?= $mpText('Korak:') ?> <?= $h(fcc_t($mpMember['help_title'] ?: $mpMember['last_title'])) ?></small><?php elseif($mpMember['last_title']): ?><small class="mp-member-step"><?= $mpText('Zadnje:') ?> <?= $h(fcc_t($mpMember['last_title'])) ?></small><?php endif ?>
       </div>
       <div class="mp-member-actions">
        <?php if($mpPhone !== ''): ?><a class="fp-button" href="<?= $h('https://wa.me/'.$mpPhone.'?text='.rawurlencode($mpMessage)) ?>" target="_blank" rel="noopener"><?= $mpText('Javi se na WhatsApp') ?></a><?php endif ?>
        <button type="button" class="fp-button fp-button-outline" data-copy-value="<?= $h($mpMessage) ?>" data-copy-done="<?= $mpText('Kopirano') ?>"><?= $mpText('Kopiraj poruku') ?></button>
        <?php /* Custom code: FC-2026-10-14: only an explicit confirmation retires the visible help snapshot. */ if($mpKey==='help'&&!empty($mpMember['help_token'])): ?>
        <form method="post" action="<?= url('partner?view=team-progress') ?>" class="mp-help-resolution">
         <?= $token ?><input type="hidden" name="action" value="mp_help_resolve"><input type="hidden" name="member_id" value="<?= (int)$mpMember['user_id'] ?>"><input type="hidden" name="help_token" value="<?= $h($mpMember['help_token']) ?>">
         <?php $mpTitles=array_values(array_unique(array_filter(array_column($mpMember['help_requests'],'title')))); if(count($mpTitles)>1): ?><small><?= $h(fcc_mp_help_text('open')) ?> <?= $h(implode('; ',array_map('fcc_t',$mpTitles))) ?></small><?php endif ?>
         <small><?= $h(fcc_mp_help_text('hint')) ?></small>
         <button type="submit" class="fp-button fp-button-outline"><i class="fas fa-check" aria-hidden="true"></i> <?= $h(fcc_mp_help_text('resolve')) ?></button>
        </form>
        <?php endif; /* /Custom code: FC-2026-10-14 */ ?>
       </div>
      </li>
     <?php endforeach ?>
    </ul>
    <?php if($mpPart === 1): ?></details><?php endif ?>
    <?php endforeach ?>
   </section>
  <?php endforeach ?>
  <p class="fp-muted mp-team-privacy"><?= $mpText('Vidiš samo izravne suradnike s potvrđenom vezom u FCC-u. Ne prikazujemo sadržaj njihovih poruka ni bilježaka.') ?></p>
 <?php endif ?>
</div>
<?php /* /Custom code: FC-2026-10-12 */ ?>
