<?php defined('ALTUMCODE') || die(); ?>
<dialog class="fl-dialog" id="fl-composer" aria-labelledby="fl-composer-title"
 data-copy-ok="<?= e(fcc_lp_t('copied')) ?>" data-copy-error="<?= e(fcc_lp_t('copy_error')) ?>"
 data-reset-confirm="<?= e(fcc_lp_t('new_message_confirm')) ?>">
 <div class="fl-dialog-head"><span class="fl-kicker"><?= e(fcc_lp_t('share_short')) ?></span><button type="button" class="fl-icon-button" data-fl-close aria-label="<?= e(fcc_lp_t('close')) ?>">×</button></div>
 <h2 id="fl-composer-title"><?= e(fcc_lp_t('composer')) ?></h2>
 <div class="fl-selected"><img data-fl-image alt="" width="120" height="86"><strong data-fl-title></strong></div>
 <label for="fl-message"><?= e(fcc_lp_t('your_message')) ?></label><textarea id="fl-message" rows="6" maxlength="6000"></textarea>
 <p class="fl-muted fl-small"><?= e(fcc_lp_t(!empty($lp_personal_share)?'own_ref':(fcc_ui_theme_is_zivi()?'visitor_ref_neutral':'visitor_ref'))) ?></p>
 <div class="fl-actions"><a class="fl-primary" data-fl-whatsapp href="https://wa.me/" target="_blank" rel="noopener noreferrer"><?= e(fcc_lp_t('whatsapp')) ?> <?= fcc_partner_icon('arrow') ?></a><button class="fl-secondary" data-fl-copy type="button"><?= e(fcc_lp_t('copy')) ?></button></div>
 <?php if(fcc_ui_theme_is_zivi() && is_logged_in() && !empty($lp_personal_share)): $fl_hr=fcc_library_pilot_locale()==='hr'; $fl_t=static fn(string $hr,string $en)=>$fl_hr?fcc_t($hr):fcc_t($en); ?>
 <?php /* Živi sloj: more places to share, only for signed-in collaborators sharing their own referral link. */ ?>
 <div class="fl-share-networks" data-fl-networks
  data-link-copied="<?= e($fl_t('Poveznica s tvojom preporukom je kopirana.','Your referral link has been copied.')) ?>"
  data-messenger-copied="<?= e($fl_t('Poveznica je kopirana. Zalijepi je u razgovor u Messengeru.','The link has been copied. Paste it into your Messenger chat.')) ?>"
  data-facebook-hint="<?= e($fl_t('Facebook prikazuje samo poveznicu. Tekst poruke je kopiran, pa ga možeš zalijepiti u objavu.','Facebook shows only the link. Your message has been copied so you can paste it into the post.')) ?>"
  data-copy-error="<?= e(fcc_lp_t('copy_error')) ?>">
  <span><?= e($fl_t('Ili podijeli na','Or share on')) ?></span>
  <div class="fl-share-grid">
   <a href="#" data-fl-net="facebook" target="_blank" rel="noopener noreferrer"><i><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.6-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.9 1.4-3.9 4v2.2H8v3h2.6V21z"/></svg></i>Facebook</a>
   <a href="#" data-fl-net="messenger" rel="noopener noreferrer"><i><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3C7 3 3 6.7 3 11.4c0 2.6 1.3 5 3.3 6.5V21l3-1.7c.9.3 1.8.4 2.7.4 5 0 9-3.7 9-8.3S17 3 12 3zm.9 11.2-2.3-2.4-4.4 2.4 4.8-5.1 2.3 2.4 4.4-2.4z"/></svg></i>Messenger</a>
   <a href="#" data-fl-net="viber" rel="noopener noreferrer"><i><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 3c4.6 0 8 2.6 8 7.4 0 4.2-2.8 7-7.6 7.3L9 20.6v-3.2C5.9 16.4 4 14 4 10.4 4 5.6 7.4 3 12 3zm-2.6 4.2c-.3 0-.7.2-.9.5-.4.5-.5 1.2-.2 1.9.8 1.9 2.2 3.4 4.1 4.3.7.3 1.4.2 1.9-.2.3-.3.4-.7.2-1l-.8-.9c-.3-.3-.7-.3-1 0l-.4.4c-.9-.4-1.6-1.1-2-2l.4-.4c.3-.3.3-.7 0-1l-.9-.8a.6.6 0 0 0-.4-.2z"/></svg></i>Viber</a>
   <a href="#" data-fl-net="telegram" target="_blank" rel="noopener noreferrer"><i><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20.7 4.3 3.8 10.8c-1.1.4-1.1 1.1-.2 1.4l4.3 1.3 1.7 5.1c.2.6.4.8.8.8s.6-.2.9-.5l2.1-2 4.4 3.2c.8.4 1.4.2 1.6-.8l2.9-13.6c.3-1.2-.5-1.8-1.6-1.4zM9.4 14.2l7.9-5-6 5.9-.3 3.2z"/></svg></i>Telegram</a>
   <a href="#" data-fl-net="email"><i><svg viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><rect x="3.5" y="5.5" width="17" height="13" rx="2"/><path d="m4 7 8 6 8-6"/></g></svg></i>E-mail</a>
   <button type="button" data-fl-net="link"><i><svg viewBox="0 0 24 24" aria-hidden="true"><g fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/></g></svg></i><?= e($fl_t('Kopiraj poveznicu','Copy link')) ?></button>
   <button type="button" data-fl-net="more" hidden><i><svg viewBox="0 0 24 24" aria-hidden="true"><g fill="currentColor"><circle cx="6" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="18" cy="12" r="1.8"/></g></svg></i><?= e($fl_t('Više','More')) ?></button>
  </div>
 </div>
 <?php endif ?>
 <button class="fl-text-button fl-reset" data-fl-reset type="button"><?= e(fcc_lp_t('new_message')) ?></button>
 <p class="fl-small" data-fl-status role="status"></p>
</dialog>
