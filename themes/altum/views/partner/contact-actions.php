<?php defined('ALTUMCODE') || die();
$saved=$data->contact;
if(!$saved) return;
$wa=fcc_partner_contact_whatsapp($saved);
?>
<div class="fp-contact-quick" data-contact-quick>
 <div class="fp-contact-quick-buttons">
  <button type="submit" form="fp-contact-edit" formaction="<?= url('partner/contacts?id='.(int)$saved['id'].'&download=vcard') ?>" formmethod="post" class="fp-button" data-contact-vcard><i class="fas fa-address-book" aria-hidden="true"></i> <?= htmlspecialchars(fcc_t('Spremi kontakt'), ENT_QUOTES, 'UTF-8') ?></button>
  <?php if($wa&&!$data->error): ?><a class="fp-button fp-button-outline" href="<?= $h($wa) ?>" target="_blank" rel="noopener noreferrer" data-contact-whatsapp><i class="fab fa-whatsapp" aria-hidden="true"></i> <?= htmlspecialchars(fcc_t('Pošalji WhatsApp poruku'), ENT_QUOTES, 'UTF-8') ?></a><?php endif ?>
 </div>
 <p class="fp-muted"><?= htmlspecialchars(fcc_t('U imenik se prenose upisani ime, email, telefon i napomena. Spremanje potvrdi na svojem uređaju.'), ENT_QUOTES, 'UTF-8') ?></p>
 <small class="fp-muted"><?= htmlspecialchars(fcc_t('Za Google kontakte na računalu uvezi preuzetu datoteku kontakta.'), ENT_QUOTES, 'UTF-8') ?></small>
 <?php if($saved['contact_permission']==='do_not_contact'): ?><p class="fp-muted"><?= htmlspecialchars(fcc_t('Ova osoba ne želi da joj se javljaš.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
</div>
