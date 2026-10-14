<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-14: member-owned business presentation, personal PDF and sharing tools. */
$locale=$data->sponsoring_locale;$t=static fn($key)=>fcc_sponsoring_copy($key,$locale);$h='fcc_partner_h';$sp=$data->sponsoring;
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-sponsoring-share.css?v='.filemtime(ASSETS_PATH.'css/fcc-sponsoring-share.css').'">','head');
\Altum\Event::add_content('<script defer src="'.ASSETS_FULL_URL.'js/fcc-sponsoring-share.js?v='.filemtime(ASSETS_PATH.'js/fcc-sponsoring-share.js').'"></script>','head');
?>
<div class="fp-page fs-share">
 <?php require THEME_PATH.'views/partner/share-navigation.php'; ?>
 <header class="fs-share-header"><h1><?= $h($t('workspace_title')) ?></h1><p><?= $h($t('workspace_lead')) ?></p></header>
 <?php if($data->error): ?><section class="fp-panel"><p role="status"><?= $h($data->error) ?></p><a class="fp-button" href="<?= $h($data->recovery_url) ?>"><?= $h($data->recovery_label) ?></a></section>
 <?php elseif($sp): ?>
 <div class="fs-share-layout"><section class="fp-panel fs-share-tools">
  <form method="get" class="fs-share-language"><input type="hidden" name="type" value="sponsoring"><label for="fs-share-lang"><?= $h($t('language')) ?></label><select name="lang" id="fs-share-lang" data-sponsoring-language><?php foreach(['en'=>'English','hr'=>'Hrvatski','sl'=>'Slovenščina','de'=>'Deutsch','es'=>'Español'] as $code=>$name): ?><option value="<?= $code ?>" <?= $locale===$code?'selected':'' ?>><?= $h($name) ?></option><?php endforeach ?></select><button class="fp-button fs-language-submit" type="submit"><?= $h($t('open_page')) ?></button></form>
  <label class="fs-share-url" for="fs-share-url"><?= $h($t('share_label')) ?><input id="fs-share-url" readonly value="<?= $h($sp['url']) ?>"></label>
  <div class="fs-share-actions"><button type="button" class="fp-button" data-sponsoring-copy="fs-share-url"><?= $h($t('copy_link')) ?></button><a class="fp-button fp-button-secondary" href="<?= $h($sp['url']) ?>" target="_blank" rel="noopener noreferrer"><?= $h($t('open_page')) ?></a><a class="fp-button fp-button-secondary" href="<?= $h($sp['pdf_url']) ?>"><?= $h($t('download_pdf')) ?></a></div>
  <p class="fs-share-note"><?= $h($t('pdf_note')) ?></p>
  <label class="fs-share-message" for="fs-share-message"><?= $h($t('message')) ?><textarea id="fs-share-message" rows="5"><?= $h(fcc_sponsoring_copy('first_message',$locale,['url'=>$sp['url']])) ?></textarea></label>
  <div class="fs-share-actions"><button type="button" class="fp-button fp-button-secondary" data-sponsoring-copy="fs-share-message"><?= $h($t('copy_message')) ?></button><a class="fp-button" href="https://wa.me/?text=<?= rawurlencode(fcc_sponsoring_copy('first_message',$locale,['url'=>$sp['url']])) ?>" data-sponsoring-message="fs-share-message" target="_blank" rel="noopener noreferrer"><?= $h($t('share_whatsapp')) ?></a></div>
  <p class="fs-copy-status" role="status" data-sponsoring-copy-status data-copied="<?= $h($t('copied')) ?>" data-manual="<?= $h($t('copy_manual')) ?>"></p>
 </section><aside class="fp-panel fs-share-owner"><h2><?= $h($t('public_details')) ?></h2><div class="fs-share-person"><?php if($sp['sponsor']['avatar']): ?><img src="<?= $h($sp['sponsor']['avatar']) ?>" width="72" height="72" alt="<?= $h($sp['sponsor']['name']) ?>"><?php else: ?><span><?= $h($sp['sponsor']['initials']) ?></span><?php endif ?><strong><?= $h($sp['sponsor']['name']) ?></strong></div><dl><dt><?= $h($t('forever_id')) ?></dt><dd><?= $h($sp['sponsor']['fbo']) ?></dd><?php if($sp['sponsor']['phone']): ?><dt><?= $h($t('phone')) ?></dt><dd>+<?= $h($sp['sponsor']['phone']) ?></dd><?php endif ?><dt><?= $h($t('card')) ?></dt><dd><a href="<?= $h($sp['sponsor']['card_url']) ?>" target="_blank" rel="noopener noreferrer"><?= $h($sp['sponsor']['card_url']) ?></a></dd></dl><p class="fs-share-note"><?= $h($t('privacy_note')) ?></p><a href="<?= url('partner/cards') ?>"><?= $h($t('edit_card')) ?></a><div class="fs-share-qr"><?= fcc_invite_qr_svg($sp['url']) ?><span><?= $h($t('qr')) ?></span></div></aside></div>
 <?php endif ?>
</div>
<?php /* /Custom code: FC-2026-10-14 */ ?>
