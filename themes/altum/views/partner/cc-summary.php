<?php defined('ALTUMCODE') || die();
$ccPage=fcc_cc_page($uid);$cc=$ccPage['metric'];$ccSnapshot=$ccPage['history'][0]??null;
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-cc.css?v='.filemtime(ASSETS_PATH.'css/fcc-cc.css').'">','fcc_styles');
?>
<section class="fp-panel fp-cc-summary">
 <div class="fp-section-title"><h2><?= $h(fcc_cc_t($ccPage['period']===substr(fcc_partner_today(),0,7).'-01'?'month_result':'points')) ?></h2><a class="fp-link" href="<?= url('partner/points') ?>"><?= $h(fcc_cc_t('all_points')) ?> →</a></div>
 <?php if($cc): ?>
 <div class="fp-cc-values"><div><span><?= $h(fcc_cc_t('personal')) ?></span><strong><?= $h(fcc_cc_number($cc['personal_cc'])) ?></strong><?php if($ccSnapshot&&$ccSnapshot['previous_id']&&(float)$ccSnapshot['personal_cc']===(float)$cc['personal_cc']&&!$cc['stale']): ?><small><?= $h(fcc_cc_t('delta',['{cc}'=>fcc_cc_number((float)$ccSnapshot['personal_cc']-(float)$ccSnapshot['previous_personal'],true)])) ?></small><?php endif ?></div><div><span><?= $h(fcc_cc_t('active')) ?></span><strong><?= $h(fcc_cc_number($cc['total_active_cc'])) ?> <small>/ 4</small></strong><small><?= $h(!empty($cc['is_4cc_active'])?fcc_cc_t('confirmed'):((float)$cc['total_active_cc']>=4?fcc_cc_t('await_activity'):fcc_cc_t('remaining',['{cc}'=>fcc_cc_number(max(0,4-(float)$cc['total_active_cc']))]))) ?></small></div></div>
 <p class="fp-muted"><?= $h(fcc_cc_t('updated',['{at}'=>fcc_cc_time($cc['updated_at'])])) ?> · <?= $h(substr($ccPage['period'],0,7)) ?><?php if($cc['stale']): ?>. <?= $h(fcc_cc_t('stale')) ?><?php endif ?></p>
 <?php else: ?><p><?= $h(fcc_cc_t('no_data')) ?></p><?php endif ?>
</section>

<?php $ccNews=array_slice(fcc_cc_recent_items($uid),0,2);if($ccNews): ?>
<section class="fp-panel"><div class="fp-section-title"><h2><?= $h(fcc_cc_t('my_points_events')) ?></h2><a class="fp-link" href="<?= url('partner/points#cc-updates') ?>"><?= $h(fcc_cc_t('all_points')) ?> →</a></div>
<?php foreach($ccNews as $item): ?><article class="fp-cc-update"><p><?= $h(fcc_cc_item_text($item)) ?></p><?php if(!$item['self']): ?><a class="fp-button fp-button-outline" href="<?= url('partner/team?member='.(int)$item['member'].'&cc_event='.(int)$item['id'].'#team-work') ?>"><?= $h(fcc_cc_t($item['kind']==='growth'?'contact':'congratulate')) ?> →</a><?php endif ?></article><?php endforeach ?>
</section><?php endif ?>
