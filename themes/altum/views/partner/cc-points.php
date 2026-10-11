<?php defined('ALTUMCODE') || die();
$ccPage=fcc_cc_page($uid,(string)($_GET['month']??''));$cc=$ccPage['metric'];$items=fcc_cc_recent_items($uid);
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-cc.css?v='.filemtime(ASSETS_PATH.'css/fcc-cc.css').'">','fcc_styles');
?>
<div class="fp-cc-page">
 <div class="fp-page-heading"><div><span class="fp-eyebrow">FLP360</span><h1><?= $h(fcc_cc_t('points')) ?></h1><p><?= $h(fcc_cc_t('points_description')) ?></p></div><a class="fp-button fp-button-outline" href="<?= url('partner/notifications#preferences') ?>"><?= $h(fcc_cc_t('notification_settings')) ?> →</a></div>
 <section class="fp-panel fp-cc-overview">
 <?php if($ccPage['periods']): ?><form method="get" class="fp-cc-month"><label for="cc-month"><?= $h(fcc_cc_t('month')) ?></label><select id="cc-month" name="month" data-is-not-custom-select><?php foreach($ccPage['periods'] as $period): ?><option value="<?= $h($period) ?>" <?= $period===$ccPage['period']?'selected':'' ?>><?= $h(substr($period,0,7)) ?></option><?php endforeach ?></select><button class="fp-button fp-button-outline"><?= $h(fcc_cc_t('show')) ?></button></form><?php endif ?>
 <?php if(!$cc): ?><p><?= $h(fcc_cc_t('no_data')) ?></p><a class="fp-button" href="<?= url('account') ?>"><?= $h(fcc_cc_t('settings')) ?></a>
 <?php else: ?>
 <div class="fp-cc-values fp-cc-three"><?php foreach(['personal'=>'personal_cc','active'=>'total_active_cc','total'=>'total_cc'] as $key=>$field): ?><div class="fp-cc-metric fp-cc-metric-<?= $h($key) ?>"><span><?= $h(fcc_cc_t($key)) ?></span><strong><?= $h(fcc_cc_number($cc[$field])) ?></strong></div><?php endforeach ?></div>
 <div class="fp-cc-activity"><div class="fp-section-title"><h2><?= $h(fcc_cc_t('activity')) ?></h2><?php if(!empty($cc['is_4cc_active'])): ?><span class="ft-status"><?= $h(fcc_cc_t('confirmed')) ?> ✓</span><?php endif ?></div>
 <div class="fp-rank-grid fp-cc-gates"><div><span><?= $h(fcc_rel_t('personal_gate')) ?></span><progress aria-label="<?= $h(fcc_rel_t('personal_gate')) ?>" max="1" value="<?= min(1,max(0,(float)$cc['personal_cc'])) ?>"></progress><strong><?= $h(fcc_cc_number($cc['personal_cc'])) ?> / 1</strong></div><div><span><?= $h(fcc_rel_t('active_gate')) ?></span><progress max="4" value="<?= min(4,max(0,(float)$cc['total_active_cc'])) ?>" aria-label="<?= $h(fcc_cc_t('activity')) ?>"></progress><strong><?= $h(fcc_cc_number($cc['total_active_cc'])) ?> / 4</strong></div></div>
 <?php if(empty($cc['is_4cc_active'])): ?><p><?= $h((float)$cc['total_active_cc']>=4?fcc_cc_t('await_activity'):fcc_cc_t('remaining',['{cc}'=>fcc_cc_number(max(0,4-(float)$cc['total_active_cc']))])) ?> <?= $h(fcc_cc_t('personal_condition',['{cc}'=>fcc_cc_number($cc['personal_cc'])])) ?></p><?php endif ?>
 </div>
 <?php if(fcc_cc_rank($cc['title']??'')>0): ?><p><?= $h(fcc_cc_t('position')) ?>: <strong><?= $h($cc['title']) ?></strong></p><?php endif ?>
 <p class="fp-muted fp-cc-timestamp"><?= $h(fcc_cc_t('updated',['{at}'=>fcc_cc_time($cc['updated_at'])])) ?> · Europe/Zagreb</p>
 <?php if($cc['stale']): ?><p class="fp-cc-note"><?= $h(fcc_cc_t('stale')) ?></p><?php endif ?>
 <small><?= $h(fcc_cc_t('source_note')) ?></small>
 <?php endif ?>
 </section>
 <div class="fp-cc-content-grid">
 <?php if($cc)require THEME_PATH.'views/partner/cc-progress.php'; ?>
 <section class="fp-panel" id="cc-updates"><div class="fp-section-title"><h2><?= $h(fcc_cc_t(fcc_pro_has_access($uid)?'digest_title':'my_points_events')) ?></h2><?php if(fcc_pro_has_access($uid)): ?><a class="fp-link" href="<?= url('partner/team') ?>"><?= $h(fcc_cc_t('open_team')) ?> →</a><?php endif ?></div>
 <?php if(!$items): ?><p class="fp-muted"><?= $h(fcc_cc_t('no_news')) ?></p><?php endif ?>
 <?php foreach($items as $item): ?><article class="fp-cc-update"><p><?= $h(fcc_cc_item_text($item)) ?></p><?php if(!$item['self']): ?><a class="fp-button fp-button-outline" href="<?= url('partner/team?member='.(int)$item['member'].'&cc_event='.(int)$item['id'].'#team-work') ?>"><?= $h(fcc_cc_t($item['kind']==='growth'?'contact':'congratulate')) ?> →</a><?php endif ?><small><?= $h(fcc_cc_time($item['occurred_at'])) ?></small></article><?php endforeach ?>
 </section>
 <section class="fp-panel"><h2><?= $h(fcc_cc_t('changes')) ?></h2><p class="fp-muted"><?= $h(fcc_cc_t('history_note')) ?></p>
 <?php if(count($ccPage['history'])<2): ?><p><?= $h(fcc_cc_t('no_history')) ?></p><?php endif ?>
 <?php foreach($ccPage['history'] as $row): ?><div class="fp-cc-history"><time><?= $h(fcc_cc_time($row['captured_at'])) ?></time><div><strong><?= $h(fcc_cc_number($row['personal_cc'])) ?> <?= $h(fcc_cc_t('personal')) ?></strong><small><?= $row['previous_id']?$h(fcc_cc_t('delta',['{cc}'=>fcc_cc_number((float)$row['personal_cc']-(float)$row['previous_personal'],true)])):$h(fcc_cc_t('baseline')) ?></small></div></div><?php endforeach ?>
 </section>
</div>
</div>
