<?php defined('ALTUMCODE') || die(); ?>
<input type="hidden" name="events_version" value="1">
<fieldset class="fp-cc-preferences"><legend><?= $h(fcc_cc_t('my_points_events')) ?></legend>
 <p><?= $h(fcc_cc_t('notification_description')) ?></p>
 <?php foreach(['cc_personal','cc_activity_self','cc_rank_self'] as $key): ?><label class="fp-check-label"><input type="checkbox" name="events[]" value="<?= $key ?>" <?= $np['events'][$key]?'checked':'' ?>> <?= $h(fcc_cc_t($key)) ?></label><?php endforeach ?>
</fieldset>
<fieldset class="fp-cc-preferences"><legend><?= $h(fcc_cc_t('team_events')) ?></legend>
 <?php if(!fcc_pro_has_access($uid)): ?><p><?= $h(fcc_cc_t('team_pro')) ?> <a href="<?= url(fcc_pro_upgrade_path('team')) ?>">PRO</a></p><?php endif ?>
 <?php foreach(['team_steps','team_tasks','team_registrations','cc_team','cc_activity_team','cc_rank_team'] as $key): ?><label class="fp-check-label"><input type="checkbox" name="events[]" value="<?= $key ?>" <?= $np['events'][$key]?'checked':'' ?>> <?= $h(fcc_cc_t($key)) ?></label><?php endforeach ?>
</fieldset>
<label><?= $h(fcc_cc_t('cc_hour')) ?><select name="cc_hour" data-is-not-custom-select><?php for($hour=8;$hour<=20;$hour++): ?><option value="<?= $hour ?>" <?= $np['cc_hour']===$hour?'selected':'' ?>><?= sprintf('%02d:00',$hour) ?></option><?php endfor ?></select><small><?= $h(fcc_cc_t('hour_note')) ?></small></label>
<label class="fp-check-label"><input type="checkbox" name="cc_details" value="1" <?= $np['cc_details']?'checked':'' ?>><span><?= $h(fcc_cc_t('details_push')) ?><small><?= $h(fcc_cc_t('details_note')) ?></small></span></label>
<?php \Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-cc.css?v='.filemtime(ASSETS_PATH.'css/fcc-cc.css').'">','fcc_styles'); ?>
