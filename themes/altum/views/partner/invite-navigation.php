<?php defined('ALTUMCODE') || die();
require_once APP_PATH.'helpers/fcc_unified_invite.php';
$inviteView=$data->view??'compose';$inviteManage=in_array($inviteView,['events','edit'],true); ?>
<nav class="fv-tabs" aria-label="<?= fcc_partner_h(fcc_t('Webinar alati')) ?>">
 <a href="<?= url('partner/share?type='.$channel) ?>" <?= $inviteView==='compose'?'aria-current="page"':'' ?>><?= fcc_partner_h(fcc_invite_t('prepare')) ?></a>
 <a href="<?= url('partner-webinar?type='.$channel.'&view=activity') ?>" <?= $inviteView==='activity'?'aria-current="page"':'' ?>><?= fcc_partner_h(fcc_event_hub_t('Prijave mojih gostiju')) ?></a>
 <?php if($channel==='webinar'&&fcc_webinar_personal_ready()): ?><a href="<?= url('partner-webinar?view=events') ?>" <?= $inviteManage?'aria-current="page"':'' ?>><?= fcc_partner_h(fcc_t('Moji webinari')) ?></a><?php endif ?>
</nav>
