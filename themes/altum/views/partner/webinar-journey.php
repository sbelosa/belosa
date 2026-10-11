<?php defined('ALTUMCODE') || die();
$wvLearning=fcc_webinar_learning_task((int)$n['position']);
if(!$wvLearning||(!fcc_webinar_enabled($uid)&&!fcc_webinar_standard_enabled($uid)))return;
$wvTool=fcc_webinar_learning_url($uid,(int)$n['position'],(int)($step['id']??0));
$wvCount=$step?fcc_partner_one("SELECT COUNT(*) total,SUM(r.is_demo=1) demo FROM fcc_webinar_registrations r JOIN fcc_webinar_invite_attempts a ON a.id=r.attempt_id AND a.user_id=r.user_id WHERE r.user_id=? AND a.journey_step_id=? AND r.status='registered'",[$uid,(int)$step['id']]):['total'=>0,'demo'=>0];
?>
<div class="jp-media-guide"><span class="jp-camera" aria-hidden="true"><?= fcc_partner_icon('people') ?></span><div><strong><?= $h(ucfirst($wvLearning['stage'])) ?></strong><p><?= $wvTool['tool']==='plan'?fcc_t('Kreni od jedne teme i pitanja svoje publike. Coach ili sponzor mogu ti pomoći složiti kratak susret.'):($wvTool['tool']==='activity'?fcc_t('Ovdje su tvoji gosti i dogovor o javljanju. Odaberi osobu kojoj možeš pomoći s idućim korakom.'):($wvTool['pro']?fcc_t('Pripremi pozivnicu ili nastavi sa svojim postojećim terminom. Prijave ćeš pronaći u Mojim prijavama.'):fcc_t('Standardna pozivnica dovoljna je za ovaj korak. Možeš pozvati ljude na postojeći FCC webinar.'))) ?></p>
<?php if($wvTool['tool']!=='plan'||$wvTool['pro']): ?><a class="fp-link" <?= $wvTool['tool']==='plan'?'data-webinar-coach':'' ?> href="<?= $h($wvTool['url']) ?>"><?= $h($wvTool['label']) ?> →</a><?php endif ?>
<?php if((int)$wvCount['total']): ?><p><?= htmlspecialchars(fcc_t('Prijave uz ovu pozivnicu:'), ENT_QUOTES, 'UTF-8') ?> <?= (int)$wvCount['total'] ?><?= (int)$wvCount['demo']?fcc_t(' · Probne: ').(int)$wvCount['demo']:'' ?>.</p><?php endif ?>
</div></div>
