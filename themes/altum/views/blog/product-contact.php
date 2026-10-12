<?php defined('ALTUMCODE') || die();
$product_contact=fcc_market_contact($post,(string)($data->referral??$ref??''),$market??null);
if($product_contact): ?>
<aside class="fl-distributor" data-fl-distributor aria-label="<?= e(fcc_lp_t('contact_person')) ?>">
 <div class="fl-contact-identity"><span class="fl-person-mark<?= !empty($product_contact['avatar'])?' has-photo':'' ?>" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($product_contact['name'],0,1))) ?><?php /* Custom code: FC-2026-10-12 */ if(!empty($product_contact['avatar'])): ?><img src="<?= e($product_contact['avatar']) ?>" alt="" width="46" height="46" loading="lazy" decoding="async" onerror="this.parentNode.classList.remove('has-photo');this.remove()"><?php endif /* /Custom code: FC-2026-10-12 */ ?></span><div><span class="fl-kicker"><?= e(fcc_lp_t('contact_person')) ?></span><strong><?= e($product_contact['name']) ?></strong><a data-fl-contact-profile href="<?= e($product_contact['profile']) ?>"><?= e(fcc_library_pilot_locale()==='hr'?fcc_t('Upoznaj suradnika'):fcc_t('Meet your distributor')) ?> ↗</a></div></div>
 <div class="fl-contact-actions">
 <?php if($product_contact['whatsapp']): ?>
 <a class="fl-contact-whatsapp" data-fl-contact-whatsapp href="<?= e($product_contact['whatsapp']) ?>" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp" aria-hidden="true"></i> <?= e(fcc_lp_t('contact_whatsapp')) ?></a>
 <a class="fl-text-button" data-fl-contact-phone href="<?= e($product_contact['tel']) ?>"><?= e(fcc_lp_t('contact_call')) ?></a>
 <?php else: ?>
 <p class="fl-small fl-muted"><?= e(fcc_lp_t('contact_missing_phone')) ?></p>
 <a class="fl-text-button" href="<?= e($product_contact['profile']) ?>"><?= e(fcc_lp_t('contact_profile')) ?> →</a>
 <?php endif ?>
 </div>
</aside>
<?php endif ?>
