<?php defined('ALTUMCODE') || die();
$share_hr=\Altum\Language::$code==='hr';
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-partner-share.css?v='.filemtime(ASSETS_PATH.'css/fcc-partner-share.css').'">','head');
?>
<?php /* Custom code: FC-2026-10-12: three clear sharing choices; webinars and events share one entry */
$share_uid=(int)($this->user->user_id??0);$share_type=$data->share_type??'products';
$share_business=fcc_business_enabled($share_uid);
$share_invite=$share_business&&function_exists('fcc_business_landing_access')&&fcc_business_landing_access($share_uid);
$share_events=in_array($share_type,['webinar','events'],true);
?>
<nav class="fp-share-navigation" aria-label="<?= $share_hr?fcc_t('Što želiš podijeliti?'):fcc_t('What would you like to share?') ?>">
 <?php if($share_business): ?><a href="<?= url('partner-marketing') ?>" <?= $share_type==='business'?'aria-current="page"':'' ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="m10 8 5 3-5 3z M8 21h8 M12 17v4"/></svg><span><?= $share_invite?fcc_business_landing_translate(fcc_locale(),'Pozivnica u tim'):($share_hr?fcc_t('Marketing plan'):fcc_t('Business presentation')) ?></span></a><?php endif ?>
 <a href="<?= url('partner/share') ?>" <?= $share_type==='products'?'aria-current="page"':'' ?>><?= fcc_partner_icon('card') ?><span><?= $share_hr?fcc_t('Proizvodi'):fcc_t('Products') ?></span></a>
 <a href="<?= url('partner/share?type=webinar') ?>" <?= $share_events?'aria-current="page"':'' ?>><?= fcc_partner_icon('people') ?><span><?= fcc_partner_h(fcc_t('Webinari i događaji')) ?></span></a>
 <?php /* Custom code: FC-2026-10-14: personal business presentation is an additional sharing choice. */ ?>
 <a href="<?= url('partner/share?type=sponsoring') ?>" <?= $share_type==='sponsoring'?'aria-current="page"':'' ?>><?= fcc_partner_icon('card') ?><span><?= fcc_partner_h(fcc_sponsoring_copy('share_label',fcc_sponsoring_locale(\Altum\Language::$code))) ?></span></a>
 <?php /* /Custom code: FC-2026-10-14 */ ?>
</nav>
<?php if($share_events): ?>
<nav class="fp-share-subnav" aria-label="<?= fcc_partner_h(fcc_t('Webinari i događaji')) ?>">
 <a href="<?= url('partner/share?type=webinar') ?>" <?= $share_type==='webinar'?'aria-current="page"':'' ?>><?= $share_hr?fcc_t('Poziv na webinar'):fcc_t('Webinar invitation') ?></a>
 <a href="<?= url('partner/share?type=events') ?>" <?= $share_type==='events'?'aria-current="page"':'' ?>><?= fcc_partner_h(fcc_event_hub_t('Seminari i događaji')) ?></a>
</nav>
<?php endif ?>
<?php /* /Custom code: FC-2026-10-12 */ ?>
