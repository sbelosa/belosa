<?php defined('ALTUMCODE') || die(); $state=fcc_team_outreach_state($row); ?>
<article class="ft-outreach-item" data-outreach-id="<?= (int)$row['id'] ?>" tabindex="-1">
 <div class="fp-section-title"><strong><?= $h(fcc_t(fcc_team_outreach_purposes()[$row['purpose']])) ?></strong><span class="ft-status ft-status-<?= $state ?>"><?= $h(fcc_team_outreach_status($row)) ?></span></div>
 <small><?= $h($row['created_at']) ?> UTC<?= $row['source']==='ai'?fcc_t(' · Pripremljeno s Coachom'):'' ?></small>
 <details><summary><?= $h(fcc_t('Pogledaj tekst')) ?></summary><p class="ft-history-text"><?= nl2br($h($row['message'])) ?></p><?php if($row['guidance']): ?><p class="fp-muted"><?= $h($row['guidance']) ?></p><?php endif ?></details>
 <?php if($row['outcome']): ?><p><?= $h($row['outcome']) ?></p><?php endif ?>
 <?php if($row['closed_at']): ?><p class="fp-muted"><?= $h(fcc_team_outreach_t('closed')) ?></p><?php endif ?>
 <div class="ft-outreach-actions">
 <?php if($state==='removed'): ?>
  <button type="button" class="fp-button fp-button-outline" data-history-operation="restore"><?= $h(fcc_team_outreach_t('restore')) ?></button>
 <?php else: ?>
  <?php if(!$row['sent_self_reported_at']&&!$row['closed_at']): ?><button type="button" class="fp-text-button" data-team-resume><?= $h(fcc_t('Uredi i nastavi')) ?></button><?php endif ?>
  <?php if($state==='pending'&&!$row['closed_at']): ?><button type="button" class="fp-button fp-button-outline" data-team-check><?= $h(fcc_team_outreach_t('check')) ?></button><?php endif ?>
  <?php if($row['sent_self_reported_at']): ?><button type="button" class="fp-text-button" data-history-operation="unconfirm"><?= $h(fcc_team_outreach_t('unconfirm')) ?></button><?php endif ?>
  <button type="button" class="fp-text-button" data-history-operation="remove"><?= $h(fcc_team_outreach_t('remove')) ?></button>
 <?php endif ?>
 </div>
 <?php if($state!=='removed'&&!$row['closed_at']): ?><details><summary><?= $row['due_date']?$h(fcc_team_outreach_reminder($row)).': '.$h($row['due_date']):fcc_t('Dogovori podsjetnik ili završi praćenje') ?></summary><div class="ft-history-followup"><label><?= $h(fcc_t('Sljedeća provjera')) ?><input type="date" data-history-due value="<?= $h($row['due_date']??'') ?>"></label><button type="button" class="fp-button fp-button-outline" data-history-operation="followup"><?= $h(fcc_t('Spremi podsjetnik')) ?></button><label><?= $h(fcc_t('Ishod razgovora, ako ga znaš')) ?><textarea rows="2" maxlength="1000" data-history-outcome></textarea></label><button type="button" class="fp-text-button" data-history-operation="close"><?= $h(fcc_t('Završi praćenje')) ?></button></div></details><?php endif ?>
</article>
