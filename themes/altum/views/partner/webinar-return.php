<?php defined('ALTUMCODE') || die();$h='fcc_partner_h';$t='fcc_wl_t'; ?>
<!doctype html><html lang="<?= fcc_language_tag() ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?= $h($t('confirm')) ?> · FCC</title><link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-webinar.css"></head>
<body class="fv-public"><main class="fv-public-shell"><header class="fv-public-brand"><span><img src="<?= SITE_URL ?>partner-assets/fcc-icon.png" width="40" height="40" alt=""><strong>Forever Card Club</strong></span></header><section class="fv-guest-card fv-single-card">
<?php if(!$ctx): ?><h1><?= $h($t('unavailable')) ?></h1><p><?= $h($t('expired_help')) ?></p>
<?php elseif($result): ?><h1><?= $h($t($r['status']==='cancelled'?'cancelled':'confirmed')) ?></h1><p><?= $h(fcc_webinar_label_time($r)) ?></p><p><?= $h($t('one_contact')) ?></p><a class="fv-button" href="<?= $h(fcc_webinar_manage_url($r)) ?>"><?= $h($t('manage')) ?></a><?php if($r['reminder_opt_in']&&!$r['email_verified_at']): ?><p class="fv-notice"><?= $h($t('verify_help')) ?></p><?php endif ?>
<?php else: ?><span class="fv-eyebrow"><?= $h($ctx['attempt']['owner_name']) ?></span><h1><?= $h($t('confirm')) ?></h1><p><?= $h($ctx['attempt']['title']) ?></p><div class="fv-guest-date"><strong><?= $h(fcc_webinar_label_time($ctx['attempt'])) ?></strong></div><p><?= $h($t('return_help')) ?></p>
<?php if($error): ?><p class="fv-error" role="alert"><?= $h($error) ?></p><?php endif ?>
<form method="post" class="fv-public-form"><input type="hidden" name="nonce" value="<?= $h($nonce['value']) ?>"><input type="hidden" name="session_revision" value="<?= (int)$ctx['attempt']['revision'] ?>">
<label class="fv-check"><input type="checkbox" name="reminders" value="1" <?= !empty($_POST['reminders'])?'checked':'' ?>><span><?= $h($t('guest_reminders')) ?></span></label>
<p class="fv-notice"><?= $h($t('registration_contact_notice',['owner'=>$ctx['attempt']['owner_name']])) ?></p>
<button class="fv-button"><?= $h($t('confirm')) ?></button></form><p class="fv-fine-print"><?= $h($t('private_link')) ?> <a href="<?= $h(fcc_legal_url('privacy')) ?>"><?= $h(fcc_t('Politika privatnosti')) ?></a></p>
<?php endif ?></section></main></body></html>
