<?php defined('ALTUMCODE') || die();
/* Custom code: FC-2026-10-12: say exactly why the step is not open yet and what to do, instead of one generic notice. */
$mpState = $j['program']['state'] ?? [];
$mpText = static fn(string $text): string => htmlspecialchars(fcc_t($text), ENT_QUOTES, 'UTF-8');
if(!empty($j['intro'])) {
 $mpAccess = ['Najprije pogledaj kratki uvod', 'Prije prvog koraka Mojeg puta pogledaj uvodni video. Kada ga pogledaš, tvoj prvi korak otvara se sam.', 'Pogledaj uvod →', url('partner?view=materials')];
} elseif(empty($mpState['is_launched'])) {
 $mpAccess = ['Moj put se uskoro otvara', 'Program još nije pokrenut za tvoj račun. Kada se otvori, ovdje te čeka tvoj prvi korak, a sav dosadašnji napredak ostaje sačuvan.', '', ''];
} elseif(empty($mpState['has_valid_linkage']) && (int)($mpState['active_link_count'] ?? 0) > 1) {
 $mpAccess = ['Tvoj Forever ID koristi još jedan račun', 'Isti Forever ID upisan je na više FCC računa, pa ne znamo kojem pripada tvoj program. Javi se podršci i povezat ćemo ga s pravim računom.', 'Javi se podršci →', url('feedback-tickets?stuck='.rawurlencode('Moj put: Forever ID'))];
} elseif(empty($mpState['has_valid_linkage'])) {
 $mpAccess = ['Upiši svoj Forever ID', 'Moj put otvara se kada je na tvojoj FCC kartici upisan tvoj Forever ID. Upiši ga na Mojoj kartici, a tvoj korak otvorit će se sam. Ako je broj već upisan, javi se podršci.', 'Otvori Moju karticu →', url('partner/cards')];
} else {
 $mpAccess = ['Moj put još nije uključen za tvoj račun', 'Tvoj napredak je sačuvan. Javi se podršci i provjerit ćemo zašto se tvoj korak ne otvara.', 'Javi se podršci →', url('feedback-tickets?stuck='.rawurlencode('Moj put: pristup'))];
}
?>
<section class="fp-panel mp-access">
 <span class="fp-eyebrow"><?= $mpText('MOJ PUT') ?></span>
 <h2><?= $mpText($mpAccess[0]) ?></h2>
 <p><?= $mpText($mpAccess[1]) ?></p>
 <?php if($mpAccess[2] !== ''): ?><a class="fp-button" href="<?= htmlspecialchars($mpAccess[3], ENT_QUOTES, 'UTF-8') ?>"><?= $mpText($mpAccess[2]) ?></a><?php endif ?>
</section>
<?php /* /Custom code: FC-2026-10-12 */ ?>
