<?php defined('ALTUMCODE') || die();
$share_hr=\Altum\Language::$code==='hr';
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-partner-share.css?v='.filemtime(ASSETS_PATH.'css/fcc-partner-share.css').'">','head');
?>
<nav class="fp-share-navigation" aria-label="<?= $share_hr?fcc_t('Što želiš podijeliti?'):fcc_t('What would you like to share?') ?>">
 <a href="<?= url('partner/share') ?>" <?= ($data->share_type??'products')==='products'?'aria-current="page"':'' ?>><?= fcc_partner_icon('card') ?><span><?= $share_hr?fcc_t('Proizvodi'):fcc_t('Products') ?></span></a>
 <a href="<?= url('partner/share?type=webinar') ?>" <?= ($data->share_type??'')==='webinar'?'aria-current="page"':'' ?>><?= fcc_partner_icon('people') ?><span><?= $share_hr?fcc_t('Poziv na webinar'):fcc_t('Webinar invitation') ?></span></a>
 <a href="<?= url('partner/share?type=events') ?>" <?= ($data->share_type??'')==='events'?'aria-current="page"':'' ?>><?= fcc_partner_icon('book') ?><span><?= fcc_partner_h(fcc_event_hub_t('Seminari i događaji')) ?></span></a>
 <?php if(fcc_business_enabled((int)($this->user->user_id??0))): ?><a href="<?= url('partner-marketing') ?>" <?= ($data->share_type??'')==='business'?'aria-current="page"':'' ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="m10 8 5 3-5 3z M8 21h8 M12 17v4"/></svg><span><?= function_exists('fcc_business_landing_access')&&fcc_business_landing_access((int)($this->user->user_id??0))?fcc_business_landing_translate(fcc_locale(),'Poslovna prezentacija'):($share_hr?fcc_t('Marketing plan'):fcc_t('Business presentation')) ?></span></a><?php endif ?>
</nav>
