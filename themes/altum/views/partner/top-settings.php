<?php
 defined('ALTUMCODE') || die();
 if(!fcc_top_ready())return;
 $topPrefs=fcc_top_profile($uid);$tt='fcc_top_t';
 \Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-top.css?v='.filemtime(ASSETS_PATH.'css/fcc-top.css').'">','head');
?>
<section id="fcc-top-settings" class="fp-panel ft-settings">
 <header class="ft-settings-heading"><div><span class="fp-eyebrow"><?= $h($tt('title')) ?></span><h2><?= $h($tt('settings')) ?></h2><p><?= $h($tt('settings_intro')) ?></p></div><a class="fp-button fp-button-outline" href="<?= url('partner/top') ?>"><?= $h($tt('view_rankings')) ?> →</a></header>
 <form method="post" class="fp-form ft-settings-form">
  <?= $token ?><input type="hidden" name="action" value="top_settings"><input type="hidden" name="version" value="<?= (int)$topPrefs['version'] ?>">
  <div class="ft-settings-grid"><div class="ft-settings-options">
   <fieldset class="ft-setting-group"><legend><?= $h($tt('my_visibility')) ?></legend><p class="ft-setting-intro"><?= $h($tt('visibility_context')) ?></p>
    <label class="ft-setting-toggle"><input type="checkbox" name="visible" value="1" aria-describedby="ft-visible-help" <?= $topPrefs['visible']?'checked':'' ?>><span><strong><?= $h($tt('visible')) ?></strong><small id="ft-visible-help"><?= $h($tt('visible_help')) ?></small></span></label>
   </fieldset>
   <fieldset class="ft-setting-group"><legend><?= $h($tt('my_notifications')) ?></legend>
    <?php foreach(['weekly_notice'=>'weekly_help','milestone_notice'=>'milestone_help'] as $field=>$help): ?>
    <label class="ft-setting-toggle"><input type="checkbox" name="<?= $field ?>" value="1" aria-describedby="ft-<?= $field ?>-help" <?= $topPrefs[$field]?'checked':'' ?>><span><strong><?= $h($tt($field)) ?></strong><small id="ft-<?= $field ?>-help"><?= $h($tt($help)) ?></small></span></label>
    <?php endforeach ?><p class="ft-setting-note"><?= $h($tt('notice_help')) ?></p>
   </fieldset>
  </div><fieldset class="ft-settings-goals"><legend><?= $h($tt('goals')) ?></legend><p><?= $h($tt('goals_help')) ?></p>
   <?php foreach(['education'] as $key): ?><label class="ft-goal-input"><span><?= $h($tt($key)) ?></span><input type="number" name="<?= $key ?>_goal" min="1" max="1000" required value="<?= (int)$topPrefs[$key.'_goal'] ?>"></label><?php endforeach ?>
  </fieldset></div>
  <footer class="ft-settings-footer"><button class="fp-button" type="submit"><?= $h($tt('save')) ?></button><span><?= $h($tt('saved_everywhere')) ?></span></footer>
 </form>
</section>
