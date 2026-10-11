<?php defined('ALTUMCODE') || die();
require_once APP_PATH.'helpers/fcc_share_coach.php';
$scJourney=$shareCoachKind==='journey';$scSocial=$scJourney&&($c['ui']['mode']??'')==='social';
$scCopy=fcc_share_coach_copy();if($scJourney)$scCopy=array_replace($scCopy,fcc_journey_review_coach_copy($c));$scPro=fcc_pro_has_access((int)$this->user->user_id);$scSource=null;
if($scJourney&&$scPro)$scSource=['fingerprint'=>''];
elseif($scPro){try{$scSource=fcc_share_coach_source((int)$this->user->user_id,$shareCoachKind,$shareCoachId);}catch(Throwable $e){}}
\Altum\Event::add_content('<link rel="stylesheet" href="'.ASSETS_FULL_URL.'css/fcc-share-coach.css?v='.filemtime(ASSETS_PATH.'css/fcc-share-coach.css').'"><script defer src="'.ASSETS_FULL_URL.'js/fcc-share-coach.js?v='.filemtime(ASSETS_PATH.'js/fcc-share-coach.js').'"></script>','head');
$scH='fcc_partner_h';
?>
<details class="fp-share-coach" data-share-coach data-thread="<?= bin2hex(random_bytes(16)) ?>" data-kind="<?= $scH($shareCoachKind) ?>" data-id="<?= (int)$shareCoachId ?>" data-fingerprint="<?= $scH($scSource['fingerprint']??'') ?>" data-token="<?= $scH(\Altum\Csrf::get()) ?>" data-endpoint="<?= $scH(url('partner/share')) ?>" data-sc-copy="<?= $scH(json_encode($scCopy,JSON_UNESCAPED_UNICODE)) ?>">
 <summary><?= fcc_partner_icon('spark') ?><span><?= $scH($scCopy['title']) ?></span><?php if(!$scPro): ?><span class="fp-share-coach-badge">PRO</span><?php endif ?><span class="fp-share-coach-chevron" aria-hidden="true">⌄</span></summary>
 <div class="fp-share-coach-body">
 <?php if(!$scPro): ?><p><?= $scH($scCopy['pro']) ?></p><a class="fp-button fp-button-outline" href="<?= url(fcc_pro_upgrade_path('coach')) ?>"><?= $scH($scCopy['upgrade']) ?></a>
 <?php elseif(!$scSource): ?><p><?= $scH($scCopy['stale']) ?></p>
 <?php else: ?>
  <p><?= $scH($scCopy['intro']) ?></p>
  <div class="fp-share-coach-history" data-sc-history role="log" aria-live="polite"></div>
  <?php if($scSocial): ?><label class="fp-share-coach-label"><?= $scH($scCopy['network']) ?><select data-sc-network data-is-not-custom-select><option value="instagram">Instagram</option><option value="facebook">Facebook</option><option value="tiktok">TikTok</option><option value="linkedin">LinkedIn</option><option value="other"><?= $scH(fcc_t('Druga mreža')) ?></option></select></label><?php endif ?>
  <label class="fp-share-coach-label"><?= $scH($scCopy['situation']) ?><textarea data-sc-situation rows="3" maxlength="1800" placeholder="<?= $scH($scCopy['placeholder']) ?>"></textarea></label>
  <button class="fp-button fp-button-outline" type="button" data-sc-generate><?= $scH($scCopy['generate']) ?></button>
  <p class="fp-share-coach-status" data-sc-status role="status"></p>
  <div class="fp-share-coach-result" data-sc-result hidden>
   <label class="fp-share-coach-label"><?= $scH($scCopy['message']) ?><textarea data-sc-message rows="6" maxlength="3800"></textarea></label>
   <div class="fp-share-coach-actions"><?php if(!$scSocial&&(!$scJourney||$recipient['kind']!=='none')): ?><button type="button" class="fp-button" data-sc-send><i class="fab fa-whatsapp" aria-hidden="true"></i><?= $scH($scCopy['send']) ?></button><?php endif ?><?php if($scJourney): ?><button type="button" class="fp-button fp-button-outline" data-sc-copy-result><?= $scH($scCopy['copy']) ?></button><?php endif ?><button type="button" class="fp-text-button" data-sc-apply><?= $scH($scCopy['apply']) ?></button></div>
  </div>
 <?php endif ?>
 </div>
</details>
