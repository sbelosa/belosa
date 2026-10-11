<?php defined('ALTUMCODE') || die(); ?>
<dialog class="fl-dialog" id="fl-composer" aria-labelledby="fl-composer-title"
 data-copy-ok="<?= e(fcc_lp_t('copied')) ?>" data-copy-error="<?= e(fcc_lp_t('copy_error')) ?>"
 data-reset-confirm="<?= e(fcc_lp_t('new_message_confirm')) ?>">
 <div class="fl-dialog-head"><span class="fl-kicker"><?= e(fcc_lp_t('share_short')) ?></span><button type="button" class="fl-icon-button" data-fl-close aria-label="<?= e(fcc_lp_t('close')) ?>">×</button></div>
 <h2 id="fl-composer-title"><?= e(fcc_lp_t('composer')) ?></h2>
 <div class="fl-selected"><img data-fl-image alt="" width="120" height="86"><strong data-fl-title></strong></div>
 <label for="fl-message"><?= e(fcc_lp_t('your_message')) ?></label><textarea id="fl-message" rows="6" maxlength="6000"></textarea>
 <p class="fl-muted fl-small"><?= e(fcc_lp_t(!empty($lp_personal_share)?'own_ref':'visitor_ref')) ?></p>
 <div class="fl-actions"><a class="fl-primary" data-fl-whatsapp href="https://wa.me/" target="_blank" rel="noopener noreferrer"><?= e(fcc_lp_t('whatsapp')) ?> <?= fcc_partner_icon('arrow') ?></a><button class="fl-secondary" data-fl-copy type="button"><?= e(fcc_lp_t('copy')) ?></button></div>
 <button class="fl-text-button fl-reset" data-fl-reset type="button"><?= e(fcc_lp_t('new_message')) ?></button>
 <p class="fl-small" data-fl-status role="status"></p>
</dialog>
