<?php defined('ALTUMCODE') || die();
$groupUid=(int)($this->user->user_id??0);
if(!$groupUid)return;
if(!session_has('admin_user_id')&&!\Altum\Teams::is_delegated())fcc_journey_team_welcome($groupUid);
$groupPath=trim(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH),'/');
$groupDetails=$groupPath==='partner/notifications'&&($_GET['welcome']??'')==='team';
if(fcc_journey_team_group_joined($groupUid)||(!$groupDetails&&($groupPath!=='partner'||fcc_journey_team_group_dismissed($groupUid))))return;
?>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-journey-foundation.css?v=<?= filemtime(THEME_PATH.'assets/css/fcc-journey-foundation.css') ?>">
<aside class="jf-team" id="team-group" aria-label="<?= htmlspecialchars(fcc_t('Timska WhatsApp grupa'), ENT_QUOTES, 'UTF-8') ?>">
 <span class="jf-team-icon" aria-hidden="true"><?= fcc_partner_icon('people') ?></span>
 <div class="jf-team-copy"><strong><?= htmlspecialchars(fcc_t('Team Extreme Suradnici'), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars(fcc_t('Timske najave i podrška na WhatsAppu.'), ENT_QUOTES, 'UTF-8') ?></span></div>
 <div class="jf-team-actions">
  <a class="fp-button fp-button-outline" href="<?= fcc_partner_h(fcc_journey_team_group_url()) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars(fcc_t('Pridruži se grupi ↗'), ENT_QUOTES, 'UTF-8') ?></a>
  <form method="post" action="<?= url('partner') ?>"><input type="hidden" name="token" value="<?= fcc_partner_h(\Altum\Csrf::get()) ?>"><input type="hidden" name="action" value="team_group_joined"><input type="hidden" name="confirmed" value="1"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(16)) ?>"><button class="fp-text-button"><?= htmlspecialchars(fcc_t('Već sam član'), ENT_QUOTES, 'UTF-8') ?></button></form>
  <?php if(!$groupDetails): ?><form class="jf-team-dismiss" method="post" action="<?= url('partner') ?>"><input type="hidden" name="token" value="<?= fcc_partner_h(\Altum\Csrf::get()) ?>"><input type="hidden" name="action" value="team_group_dismiss"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(16)) ?>"><button class="fp-text-button jf-team-later" aria-label="<?= htmlspecialchars(fcc_t('Kasnije'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(fcc_t('Kasnije'), ENT_QUOTES, 'UTF-8') ?>">×</button></form><?php endif ?>
 </div>
</aside>
