<?php defined('ALTUMCODE') || die();
$task=$j['journey90']['content'];$draft=$j['journey90']['draft'];
$choices=fcc_jp_choices($uid,$task);$articleId=(int)($refs['article_id']??$draft['article_id']??0);
if(!isset($choices[$articleId]))$articleId=0;
$contactId=(int)($refs['contact_id']??$draft['contact_id']??0);
$allowedContacts=array_filter($data->contacts,static fn($contact)=>$contact['contact_permission']!=='do_not_contact');
if(!in_array($contactId,array_map(static fn($contact)=>(int)$contact['id'],$allowedContacts),true))$contactId=0;
$recipient=fcc_journey_warm_recipient($uid,$task,$contactId);
?>
<link rel="stylesheet" href="<?= ASSETS_FULL_URL ?>css/fcc-journey-warm.css?v=<?= filemtime(THEME_PATH.'assets/css/fcc-journey-warm.css') ?>">
<div class="jw-coach-result" data-warm-step>
 <form method="post" class="fp-form" data-warm-form>
 <?= $fields('draft',['action'=>null]) ?>
 <input type="hidden" name="article_id" value="<?= $articleId ?>">
 <input type="hidden" name="contact_id" value="<?= $contactId ?>">
 <label for="warm-coach-message"><?= $h(fcc_journey_warm_label('Tvoja prilagođena poruka')) ?></label>
 <textarea id="warm-coach-message" name="message" rows="5" maxlength="3000" data-warm-message><?= $h($c['message']) ?></textarea>
 <div class="fp-actions">
 <?php if($recipient['kind']!=='none'): ?><button type="button" class="fp-button" data-warm-whatsapp data-phone="<?= $h($recipient['phone']) ?>"><?= $h(fcc_journey_warm_label($recipient['label'])) ?> →</button><?php endif ?>
 <button type="button" class="fp-button fp-button-outline" data-warm-copy><?= $h(fcc_journey_warm_label('Kopiraj poruku')) ?></button>
 <button type="submit" name="action" value="pilot_draft" class="jp-text-button"><?= $h(fcc_journey_warm_label('Spremi poruku i vrati se na zadatak')) ?> →</button>
 </div>
 <?php if($recipient['kind']!=='none'): ?><p class="jw-recipient"><?= $h(fcc_journey_warm_label($recipient['hint'])) ?><?php if($recipient['name']): ?> <strong><?= $h($recipient['name']) ?></strong><?php endif ?></p><?php endif ?>
 <p role="status" aria-live="polite" data-warm-status></p>
 <script type="application/json" data-warm-choices><?= json_encode([0=>$choices[$articleId]??[]],JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
 </form>
</div>
<script src="<?= ASSETS_FULL_URL ?>js/fcc-journey-warm.js?v=<?= filemtime(THEME_PATH.'assets/js/fcc-journey-warm.js') ?>" defer></script>
