<?php defined('ALTUMCODE') || die();
if(!fcc_webinar_outreach_ready()||empty($webinar['session']['id']))return;
$woSid=(int)$webinar['session']['id'];
$woContact=empty($_GET['new'])?(int)($attempt['recipient_contact_id']??$_GET['contact_id']??0):0;
$woSummary=fcc_webinar_outreach_summary((int)$this->user->user_id,$woSid);
$woBase=(string)$this->user->user_id.':'.($date?$date->format('Y-m-d'):'').':'.($webinar['ref']??'').':'.\Altum\Language::$code.':'.$woSid.':'.($webinar['session']['revision']??0);
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-webinar-outreach.css?v='.filemtime(ASSETS_PATH.'css/fcc-webinar-outreach.css').'">','head');
?>
<div class="fp-outreach" data-invite-outreach data-outreach-copy="<?= $h(json_encode(fcc_wo_copy(),JSON_UNESCAPED_UNICODE)) ?>" data-session="<?= $woSid ?>" data-user="<?= (int)$this->user->user_id ?>" data-base-key="<?= $h($woBase) ?>" data-new-url="<?= $h(url('partner/share?type='.$channel.'&session_id='.$woSid.'&new=1')) ?>">
 <div class="fp-outreach-shortcuts"><button type="button" class="fp-link" data-outreach-manual><?= $h(fcc_wo_t('already')) ?></button><button type="button" class="fp-button fp-button-outline" data-outreach-new><?= $h(fcc_wo_t('new')) ?></button></div>
 <section class="fp-outreach-confirm" data-outreach-panel hidden tabindex="-1" aria-labelledby="outreach-title">
  <h3 id="outreach-title"><?= $h(fcc_wo_t('title')) ?></h3><p><?= $h(fcc_wo_t('hint')) ?></p>
  <form class="fp-form" data-outreach-form>
   <label for="outreach-contact"><?= $h(fcc_wo_t('recipient')) ?></label>
   <select id="outreach-contact" data-outreach-contact data-is-not-custom-select><option value="0"><?= $h(fcc_wo_t('other')) ?></option><?php foreach($invite['contacts']??[] as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $woContact===(int)$c['id']?'selected':'' ?>><?= $h($c['name']) ?></option><?php endforeach ?></select>
   <label data-outreach-name-wrap for="outreach-name"><?= $h(fcc_wo_t('name')) ?><input id="outreach-name" data-outreach-name type="text" maxlength="160" autocomplete="off"></label>
   <small><?= $h(fcc_wo_t('manual_help')) ?></small>
   <div class="fp-outreach-buttons"><button type="submit" class="fp-button" data-is-ajax data-outreach-confirm><?= $h(fcc_wo_t('sent')) ?></button><button type="button" class="fp-button fp-button-outline" data-outreach-cancel><?= $h(fcc_wo_t('not_sent')) ?></button></div>
  </form>
  <p role="status" aria-live="polite" data-outreach-status></p>
 </section>
 <?php if(($_GET['saved']??'')==='outreach'): ?><p class="fv-notice" role="status"><?= $h(fcc_wo_t('saved')) ?></p><?php endif ?>
 <details class="fp-outreach-summary" id="invite-outreach" <?= ($_GET['saved']??'')==='outreach'?'open':'' ?>><summary><?= $h(fcc_wo_t('summary')) ?> <span><?= $h(fcc_wo_t('invited')) ?>: <?= $woSummary['invited_count'] ?> · <?= $h(fcc_wo_t('registered')) ?>: <?= $woSummary['registered_count'] ?></span></summary>
  <p><?= $h(fcc_wo_t('matching')) ?></p>
  <?php foreach(['invited'=>'no_invites','registered'=>'no_registrations'] as $list=>$empty): ?><h4><?= $h(fcc_wo_t($list)) ?>: <?= (int)$woSummary[$list.'_count'] ?></h4><?php if(!$woSummary[$list]): ?><p><?= $h(fcc_wo_t($empty)) ?></p><?php else: ?><ol><?php foreach($woSummary[$list] as $person): ?><li><?php if($person['contact_url']): ?><a href="<?= $h($person['contact_url']) ?>"><?= $h($person['name']) ?></a><?php else: ?><?= $h($person['name']) ?><?php endif ?></li><?php endforeach ?></ol><?php endif ?><?php endforeach ?>
 </details>
</div>
