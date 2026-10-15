<?php
/* Custom code: FC-2026-10-15: explicit, accessible admin confirmation for a stored occurrence. */
defined('ALTUMCODE') || die();
if($s&&\Altum\Authentication::is_admin()&&$s['status']!=='cancelled'):
 $removeSeries=fcc_partner_one('SELECT * FROM fcc_webinar_series WHERE id=?',[(int)$s['series_id']]);
 $removeTitle=fcc_event_manage_t(fcc_event_definition($s)['mode']==='online'?'remove_webinar':'remove_event');
?>
<details class="fe-remove" id="fe-remove-event" <?= (($_GET['remove']??'')==='1'||($_POST['action']??'')==='remove_event')?'open':'' ?>>
 <summary><?= $h($removeTitle) ?></summary>
 <div class="fe-remove-body">
  <h3><?= $h(fcc_event_manage_t('heading')) ?></h3>
  <p><strong><?= $h($s['title']) ?></strong><br><?= $h(fcc_event_label($s)) ?></p>
  <p><?= $h(fcc_event_manage_t('help')) ?></p>
  <form method="post" action="<?= url('admin/webinars?session='.(int)$s['id']) ?>#fe-remove-event" class="fe-form">
   <input type="hidden" name="token" value="<?= $h(\Altum\Csrf::get()) ?>">
   <input type="hidden" name="action" value="remove_event">
   <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
   <input type="hidden" name="revision" value="<?= (int)$s['revision'] ?>">
   <input type="hidden" name="series_revision" value="<?= (int)($removeSeries['revision']??0) ?>">
   <?php if((fcc_event_definition($removeSeries??[])['repeat']??'once')==='weekly'): ?>
   <label><?= $h(fcc_event_manage_t('scope')) ?><select name="remove_scope" data-is-not-custom-select aria-describedby="fe-remove-repeat-help"><option value="one"><?= $h(fcc_event_manage_t('one')) ?></option><option value="future"><?= $h(fcc_event_manage_t('future')) ?></option></select></label>
   <small id="fe-remove-repeat-help"><?= $h(fcc_event_manage_t('repeat_help')) ?></small>
   <?php else: ?><input type="hidden" name="remove_scope" value="one"><?php endif ?>
   <label class="fe-check"><input type="checkbox" name="confirm_remove" value="1" required><span><?= $h(fcc_event_manage_t('confirm')) ?></span></label>
   <button type="submit" class="fp-button fp-button-outline fe-remove-button"><?= $h(fcc_event_manage_t('submit')) ?></button>
  </form>
 </div>
</details>
<?php endif; /* /Custom code: FC-2026-10-15 */ ?>
