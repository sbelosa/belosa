<?php defined('ALTUMCODE') || die();
// Shared real-program/preview composer. No mutation while rendering the page.
$composerError='';$choices=[];
try{$choices=fcc_j90_message_options($composerUserId,$composerContent);}catch(InvalidArgumentException $e){$composerError=$e->getMessage();}
$articleMode=$composerContent['communication']['composer']['kind']==='article';
$selectedId=(int)($composerDraft['article_id']??0);$initial=$choices[$selectedId]??null;
$message=(string)($composerDraft['message']??$initial['message']??'');
if($initial)try{$message=fcc_j90_with_referral($message,$initial['referral_url']);}catch(InvalidArgumentException $e){$composerError=$e->getMessage();}
?>
<?php if($composerError): ?><p role="alert"><?= $h($composerError) ?></p><?php endif ?>
<?php if($choices): ?>
<form method="post" class="fp-form" data-j90-composer data-j90-auto-message data-j90-referral="<?= $h($initial['referral_url']??'') ?>" <?= $composerDemo?'onsubmit="return false"':'' ?>>
<?= $composerFormFields ?>
<?php if($articleMode): ?><label><?= htmlspecialchars(fcc_t('Proizvod ili članak'), ENT_QUOTES, 'UTF-8') ?><select name="article_id" required data-is-not-custom-select data-j90-article><option value=""><?= htmlspecialchars(fcc_t('Odaberi proizvod ili članak'), ENT_QUOTES, 'UTF-8') ?></option><?php foreach($choices as $id=>$choice): ?><option value="<?= $id ?>" <?= $selectedId===$id?'selected':'' ?> data-image="<?= $h($choice['image']) ?>" data-message="<?= $h($choice['message']) ?>" data-referral="<?= $h($choice['referral_url']) ?>"><?= $h($choice['title']) ?></option><?php endforeach ?></select></label><img class="j90-product" data-j90-image hidden alt="<?= htmlspecialchars(fcc_t('Odabrani proizvod ili članak'), ENT_QUOTES, 'UTF-8') ?>" width="88" height="88"><small><?= htmlspecialchars(fcc_t('Tvoja osobna poveznica uključuje se automatski.'), ENT_QUOTES, 'UTF-8') ?></small><?php endif ?>
<div data-j90-message-fields <?= !$initial&&$message===''?'hidden':'' ?>>
<label><?= htmlspecialchars(fcc_t('Tvoja poruka — slobodno je prilagodi'), ENT_QUOTES, 'UTF-8') ?><textarea name="message" rows="6" maxlength="3000" required><?= $h($message) ?></textarea></label>
<?php if(!$composerDemo): ?><details class="fj-history"><summary><?= htmlspecialchars(fcc_t('Zemlja osobe (neobvezno)'), ENT_QUOTES, 'UTF-8') ?></summary><label><?= htmlspecialchars(fcc_t('Zemlja osobe'), ENT_QUOTES, 'UTF-8') ?><input name="country" maxlength="60" placeholder="<?= htmlspecialchars(fcc_t('Npr. Hrvatska'), ENT_QUOTES, 'UTF-8') ?>" value="<?= $h($composerDraft['country']??'') ?>"></label></details><?php endif ?>
<div class="fp-actions"><button type="button" class="fp-button" data-j90-whatsapp <?= $articleMode&&!$initial?'disabled':'' ?>><?= htmlspecialchars(fcc_t('Otvori WhatsApp'), ENT_QUOTES, 'UTF-8') ?></button><?php if(!$composerDemo): ?><button class="fp-button fp-button-outline"><?= htmlspecialchars(fcc_t('Sačuvaj moju poruku'), ENT_QUOTES, 'UTF-8') ?></button><?php endif ?></div>
<p data-j90-message-status role="status"><?= htmlspecialchars(fcc_t('WhatsApp preuzima tekst iz ovog polja. Odaberi osobu, provjeri poruku i sam/a je pošalji.'), ENT_QUOTES, 'UTF-8') ?></p>
</div>
<noscript><p><?= htmlspecialchars(fcc_t('Uključi JavaScript za automatsko pripremanje poruke i otvaranje WhatsAppa.'), ENT_QUOTES, 'UTF-8') ?></p></noscript>
</form>
<?php endif ?>
