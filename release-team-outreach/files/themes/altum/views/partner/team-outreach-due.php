<?php defined('ALTUMCODE') || die();
$teamDue=fcc_team_outreach_history($uid,0,true);
if(!$teamDue)return;
?>
<section class="fp-panel ft-due-panel"><div class="fp-section-title"><h2>Provjeri razgovor s timom</h2><span class="fp-muted">Tvoji podsjetnici</span></div><p>Najprije provjeri što je dogovoreno i je li prethodna poruka poslana.</p>
<?php foreach(array_slice($teamDue,0,($section??'')==='team'?30:3) as $reminder): ?><p><a class="fp-link" href="<?= url('partner/team?member='.(int)$reminder['member_user_id'].'#team-work') ?>"><?= $h($reminder['member_name']) ?> →</a><br><small><?= $h($reminder['status_label']) ?> · <?= $h($reminder['due_date']) ?></small></p><?php endforeach ?>
<?php if(count($teamDue)>3&&($section??'')!=='team'): ?><a class="fp-link" href="<?= url('partner/team#team-work') ?>">Pregledaj javljanja u timu →</a><?php endif ?></section>
