<?php defined('ALTUMCODE') || die();
$deviceUid=(int)($this->user->user_id??0);if(!$deviceUid)return;
$deviceDelegated=session_has('admin_user_id')||\Altum\Teams::is_delegated();
$deviceReady=!$deviceDelegated&&in_array(fcc_partner_push_mode(),['enabled','phone_pilot'],true);
$devicePreferences=fcc_partner_notification_preferences($deviceUid);
$deviceHome=trim(parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH),'/')==='partner';
?>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-partner-device.css?v=<?= filemtime(THEME_PATH.'assets/css/fcc-partner-device.css') ?>">
<div data-fcc-device data-delegated="<?= $deviceDelegated?'1':'0' ?>" data-user="<?= $deviceUid ?>" data-endpoint="<?= url('partner/notifications') ?>" data-token="<?= fcc_partner_h(\Altum\Csrf::get()) ?>" data-enabled="<?= $devicePreferences['push']?'1':'0' ?>" data-ready="<?= $deviceReady?'1':'0' ?>" data-public-key="<?= fcc_partner_h($deviceReady?fcc_partner_push_public_key():'') ?>" data-prompt="<?= $deviceHome?'1':'0' ?>">
 <aside class="fp-device-prompt" data-device-prompt hidden aria-label="<?= htmlspecialchars(fcc_t('Obavijesti na uređaju'), ENT_QUOTES, 'UTF-8') ?>">
  <span class="fp-device-icon" aria-hidden="true"><?= fcc_partner_icon('bell') ?></span>
  <div><strong><?= htmlspecialchars(fcc_t('Važne novosti na vrijeme'), ENT_QUOTES, 'UTF-8') ?></strong><span data-device-copy><?= htmlspecialchars(fcc_t('Dobij podsjetnik za webinar i obavijest o novom kontaktu.'), ENT_QUOTES, 'UTF-8') ?></span><span role="status" data-device-status></span></div>
  <button type="button" class="fp-button fp-button-outline" data-device-enable><?= htmlspecialchars(fcc_t('Uključi obavijesti'), ENT_QUOTES, 'UTF-8') ?></button>
  <a class="fp-button fp-button-outline" data-device-install href="<?= url('partner/install') ?>" hidden><?= htmlspecialchars(fcc_t('Dodaj FCC na mobitel'), ENT_QUOTES, 'UTF-8') ?></a>
  <button type="button" class="fp-text-button" data-device-later><?= htmlspecialchars(fcc_t('Kasnije'), ENT_QUOTES, 'UTF-8') ?></button>
 </aside>
</div>
<script src="<?= ASSETS_FULL_URL ?>js/fcc-partner-push.js?v=<?= filemtime(THEME_PATH.'assets/js/fcc-partner-push.js') ?>" defer></script>
