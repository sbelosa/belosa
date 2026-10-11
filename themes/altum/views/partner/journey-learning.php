<?php defined('ALTUMCODE') || die();
$learn=fcc_coach_learning_task((string)($c['id']??''));
if(!$learn)return;
$learn=fcc_localize_copy($learn);
?>
<details class="fl-step-learning">
 <summary><?= htmlspecialchars(fcc_t('Razumij ovaj korak uz tim'), ENT_QUOTES, 'UTF-8') ?></summary>
 <h3><?= $h($learn['concept']) ?></h3><p><?= $h($learn['explanation']) ?></p>
 <div class="fl-question"><strong><?= $learn['channel']==='team'?fcc_t('Pitanje za timsku grupu'):fcc_t('Pitanje za sponzora') ?></strong><p><?= $h($learn['question']) ?></p><button type="button" class="jp-text-button" data-copy="<?= $h(fcc_t('Bok! ').$learn['question']) ?>"><?= htmlspecialchars(fcc_t('Kopiraj pitanje'), ENT_QUOTES, 'UTF-8') ?></button></div>
 <div class="fp-actions"><a class="fp-button fp-button-outline" href="<?= $h($learn['channel']==='team'?$learn['team_url']:SITE_URL.'partner?view=materials&topic='.$learn['topic_id'].'#mentor') ?>"><?= $learn['channel']==='team'?fcc_t('Otvori timsku grupu'):fcc_t('Poveži se sa sponzorom') ?></a><a class="fp-link" data-education-coach="<?= $h($learn['coach_question']) ?>" href="<?= url('partner?view=coach') ?>"><?= htmlspecialchars(fcc_t('Objasni mi uz primjer'), ENT_QUOTES, 'UTF-8') ?></a></div>
 <small><?= htmlspecialchars(fcc_t('Neobvezna pomoć uz tvoj korak. Pitaj, uvježbaj objašnjenje svojim riječima i primijeni ono što razumiješ.'), ENT_QUOTES, 'UTF-8') ?></small>
</details>
