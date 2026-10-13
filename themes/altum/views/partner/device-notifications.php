<?php defined('ALTUMCODE') || die();
$deviceUid=(int)($this->user->user_id??0);if(!$deviceUid)return;
$deviceDelegated=session_has('admin_user_id')||\Altum\Teams::is_delegated();
$deviceReady=!$deviceDelegated&&in_array(fcc_partner_push_mode(),['enabled','phone_pilot'],true);
$devicePreferences=fcc_partner_notification_preferences($deviceUid);
$deviceHome=trim(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH),'/')==='partner';
?>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-device.css?v=<?= filemtime(THEME_PATH.'assets/css/fcc-partner-device.css') ?>">
<div data-fcc-device data-delegated="<?= $deviceDelegated?'1':'0' ?>" data-user="<?= $deviceUid ?>" data-endpoint="<?= url('partner/notifications') ?>" data-token="<?= fcc_partner_h(\Altum\Csrf::get()) ?>" data-enabled="<?= $devicePreferences['push']?'1':'0' ?>" data-ready="<?= $deviceReady?'1':'0' ?>" data-public-key="<?= fcc_partner_h($deviceReady?fcc_partner_push_public_key():'') ?>" data-prompt="0"><?php /* Custom code: FC-2026-10-13: the notifications step lives in Postavi FCC behind the bell; this element only reconnects a device. */ ?>
 <aside class="fp-device-prompt" data-device-prompt hidden aria-label="<?= htmlspecialchars(fcc_t('Obavijesti na uređaju'), ENT_QUOTES, 'UTF-8') ?>">
  <span class="fp-device-icon" aria-hidden="true"><?= fcc_partner_icon('bell') ?></span>
  <div><strong><?= htmlspecialchars(fcc_t('Važne novosti na vrijeme'), ENT_QUOTES, 'UTF-8') ?></strong><span data-device-copy><?= htmlspecialchars(fcc_t('Dobij podsjetnik za webinar i obavijest o novom kontaktu.'), ENT_QUOTES, 'UTF-8') ?></span><span role="status" data-device-status></span></div>
  <button type="button" class="fp-button fp-button-outline" data-device-enable><?= htmlspecialchars(fcc_t('Uključi obavijesti'), ENT_QUOTES, 'UTF-8') ?></button>
  <a class="fp-button fp-button-outline" data-device-install href="<?= url('partner/install') ?>" hidden><?= htmlspecialchars(fcc_t('Dodaj FCC na mobitel'), ENT_QUOTES, 'UTF-8') ?></a>
  <button type="button" class="fp-text-button" data-device-later><?= htmlspecialchars(fcc_t('Kasnije'), ENT_QUOTES, 'UTF-8') ?></button>
 </aside>
</div>
<script src="<?= ASSETS_FULL_URL ?>js/fcc-partner-push.js?v=<?= filemtime(THEME_PATH.'assets/js/fcc-partner-push.js') ?>" defer></script>
<?php if($deviceHome&&!$deviceDelegated): /* Custom code: FC-2026-10-13: one quiet line on Danas while a Postavi FCC step is open; the steps are behind the bell. */ ?>
<aside class="fp-setup-strip" data-fcc-setup-strip data-user="<?= $deviceUid ?>" data-team-open="<?= function_exists('fcc_journey_team_group_joined')&&fcc_jp_schema()&&!fcc_journey_team_group_joined($deviceUid)?'1':'0' ?>" hidden aria-label="<?= htmlspecialchars(fcc_t('Postavi FCC'), ENT_QUOTES, 'UTF-8') ?>">
 <span class="fp-setup-strip-icon" aria-hidden="true"><?= fcc_partner_icon('spark') ?></span>
 <a class="fp-setup-strip-link" href="<?= url('partner/notifications#fcc-setup') ?>"><span class="fp-setup-strip-text"><strong><?= htmlspecialchars(fcc_t('Postavi FCC'), ENT_QUOTES, 'UTF-8') ?></strong><span data-setup-strip-count data-template="<?= htmlspecialchars(fcc_t('Još {n} od {total} koraka'), ENT_QUOTES, 'UTF-8') ?>"></span></span></a>
 <button type="button" class="fp-setup-strip-later" data-setup-strip-later aria-label="<?= htmlspecialchars(fcc_t('Sakrij na tjedan dana'), ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(fcc_t('Sakrij na tjedan dana'), ENT_QUOTES, 'UTF-8') ?>">×</button>
</aside>
<script src="<?= ASSETS_FULL_URL ?>js/fcc-partner-setup.js?v=<?= filemtime(THEME_PATH.'assets/js/fcc-partner-setup.js') ?>" defer></script>
<?php endif /* /Custom code: FC-2026-10-13 */ ?>
