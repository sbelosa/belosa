<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: compact sponsor panel on Today, only for sponsors with direct members. */
if(!function_exists('fcc_mp_has_team') || !fcc_mp_has_team($uid)) return;
$mpOverview = fcc_mp_sponsor_overview($uid);
$mpAttention = count($mpOverview['groups']['help']) + count($mpOverview['groups']['inactive']) + count($mpOverview['groups']['not_started']);
?>
<section class="fp-panel mp-team-summary">
 <div>
  <span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('TVOJI SURADNICI NA MOJEM PUTU'), ENT_QUOTES, 'UTF-8') ?></span>
  <strong><?= $mpAttention ? htmlspecialchars(sprintf(fcc_t('Suradnici kojima je danas potrebna tvoja poruka: %d.'), $mpAttention), ENT_QUOTES, 'UTF-8') : htmlspecialchars(fcc_t('Tvoji suradnici napreduju.'), ENT_QUOTES, 'UTF-8') ?></strong>
  <small><?= htmlspecialchars(sprintf(fcc_t('Izravnih suradnika: %d · u programu: %d · aktivnih ovaj tjedan: %d'), (int)$mpOverview['total'], (int)$mpOverview['in_program'], (int)$mpOverview['active_week']), ENT_QUOTES, 'UTF-8') ?></small>
 </div>
 <a class="fp-button<?= $mpAttention ? '' : ' fp-button-outline' ?>" href="<?= url('partner?view=team-progress') ?>"><?= htmlspecialchars(fcc_t('Otvori napredak suradnika →'), ENT_QUOTES, 'UTF-8') ?></a>
</section>
<?php /* /Custom code: FC-2026-10-12 */ ?>
