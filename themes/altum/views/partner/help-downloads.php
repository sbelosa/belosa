<?php defined('ALTUMCODE') || die(); ?>
  <section class="fh-downloads"><h2><?= $h($t('presentation')) ?></h2><p><?= $h($t('language')) ?></p><div class="fh-download-grid">
   <?php foreach(fcc_language_labels() as $code=>$name): if(empty(fcc_help_downloads()[$code]))continue; ?><a class="fp-button <?= $code===$locale?'':'fp-button-secondary' ?>" href="<?= url('partner/help?'.http_build_query(['asset'=>'presentation','locale'=>$code])) ?>" download><?= $h($t('download')) ?>: <?= $h($name) ?></a><?php endforeach ?>
  </div><?php if(!empty(fcc_help_downloads()['bundle'])): ?><p><a class="fp-link" href="<?= url('partner/help?asset=bundle') ?>" download><?= $h($t('all_downloads')) ?></a></p><?php endif ?></section>
