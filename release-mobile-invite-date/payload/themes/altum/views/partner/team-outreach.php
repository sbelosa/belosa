<?php defined('ALTUMCODE') || die();
if(!$outreach['ready']): ?><p><?= htmlspecialchars(fcc_t('Priprema poruka uskoro će biti dostupna.'), ENT_QUOTES, 'UTF-8') ?></p><?php return; endif;
$ot=$outreach['target'];
$outreachToday=new DateTimeImmutable(fcc_partner_today(),new DateTimeZone('Europe/Zagreb'));
\Altum\Event::add_content('<script defer src="'.ASSETS_FULL_URL.'js/fcc-team-outreach-v2.js?v='.filemtime(ASSETS_PATH.'js/fcc-team-outreach-v2.js').'"></script>','head');
?>
<div class="ft-outreach" data-team-outreach data-endpoint="<?= url('partner/team') ?>" data-member="<?= $subject ?>" data-contact-stamp="<?= $h($ot['contact_stamp']) ?>" data-phone-available="<?= $ot['phone']?'1':'0' ?>">
 <?= $token ?>
 <p class="ft-outreach-lead"><?= htmlspecialchars(fcc_t('Pripremi poruku za'), ENT_QUOTES, 'UTF-8') ?> <strong><?= $h($p['name']) ?></strong><?= htmlspecialchars(fcc_t('. Odaberi razlog javljanja i prilagodi tekst svojem razgovoru.'), ENT_QUOTES, 'UTF-8') ?></p>
 <div class="ft-purpose" role="group" aria-label="<?= htmlspecialchars(fcc_t('Razlog javljanja'), ENT_QUOTES, 'UTF-8') ?>"><?php foreach(fcc_team_outreach_purposes() as $key=>$label): ?><button type="button" class="ft-purpose-button" data-purpose="<?= $key ?>" aria-pressed="<?= $key==='checkin'?'true':'false' ?>"><?= $h($label) ?></button><?php endforeach ?></div>
 <div class="ft-outreach-grid">
  <div class="ft-message-box">
   <label for="team-message"><?= htmlspecialchars(fcc_t('Tvoja poruka'), ENT_QUOTES, 'UTF-8') ?></label><textarea id="team-message" rows="8" maxlength="4000" data-team-message><?= $h($outreach['templates']['checkin']) ?></textarea>
   <div class="ft-outreach-actions"><button type="button" class="fp-button" data-team-open <?= !$ot['phone']?'disabled':'' ?>><i class="fab fa-whatsapp" aria-hidden="true"></i> <?= htmlspecialchars(fcc_t('Otvori WhatsApp'), ENT_QUOTES, 'UTF-8') ?></button><button type="button" class="fp-button fp-button-outline" data-team-copy><?= htmlspecialchars(fcc_t('Kopiraj poruku'), ENT_QUOTES, 'UTF-8') ?></button><button type="button" class="fp-text-button" data-team-save><?= htmlspecialchars(fcc_t('Spremi nacrt'), ENT_QUOTES, 'UTF-8') ?></button></div>
   <p class="fp-muted"><?= $ot['phone']?fcc_t('Otvara se broj ').$h($ot['phone']).' s cijelim upisanim tekstom.':fcc_t('WhatsApp će biti dostupan kada suradnik ima potvrđen poslovni broj.') ?> <?= htmlspecialchars(fcc_t('Poruku šalješ osobno.'), ENT_QUOTES, 'UTF-8') ?></p>
   <div class="ft-outreach-confirm" data-team-confirm-panel hidden role="region" aria-label="<?= $h(fcc_team_outreach_t('question')) ?>"><strong><?= $h(fcc_team_outreach_t('question')) ?></strong><p><b><?= $h($p['name']) ?></b> · <span data-team-draft-status></span></p><blockquote class="ft-confirm-text" data-team-confirm-text></blockquote><p><?= $h(fcc_team_outreach_t('confirm_hint')) ?></p><div class="ft-outreach-actions"><button type="button" class="fp-button" data-team-confirm><?= $h(fcc_team_outreach_t('confirm')) ?></button><button type="button" class="fp-button fp-button-outline" data-team-not_sent><?= $h(fcc_team_outreach_t('not_sent')) ?></button><button type="button" class="fp-text-button" data-team-later><?= $h(fcc_team_outreach_t('later')) ?></button></div><a class="fp-link" target="_blank" rel="noopener" data-team-continue hidden><?= htmlspecialchars(fcc_t('Nastavi u WhatsApp'), ENT_QUOTES, 'UTF-8') ?></a></div>
   <label class="ft-followup-label"><?= htmlspecialchars(fcc_t('Kada želiš provjeriti kako je prošlo?'), ENT_QUOTES, 'UTF-8') ?><input type="date" data-team-due min="<?= $h($outreachToday->format('Y-m-d')) ?>" value="<?= $h($outreachToday->modify('+3 days')->format('Y-m-d')) ?>"></label><small><?= $h(fcc_team_outreach_t('reminder_hint')) ?></small>
  </div>
  <aside class="ft-outreach-coach">
   <span class="fp-eyebrow"><?= htmlspecialchars(fcc_t('POMOĆ TVOJEG COACHA'), ENT_QUOTES, 'UTF-8') ?></span><h3><?= htmlspecialchars(fcc_t('Pronađi dobar početak.'), ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars(fcc_t('Opiši što znaš i kakvu podršku želiš ponuditi. Coach uzima u obzir zabilježeni napredak i dostupne CC podatke.'), ENT_QUOTES, 'UTF-8') ?></p>
   <label for="team-situation"><?= htmlspecialchars(fcc_t('Što želiš postići?'), ENT_QUOTES, 'UTF-8') ?></label><textarea id="team-situation" rows="4" maxlength="1500" data-team-situation placeholder="<?= htmlspecialchars(fcc_t('Npr. želim ponuditi pomoć s novim FCC-om i pozvati na sljedeći webinar.'), ENT_QUOTES, 'UTF-8') ?>"></textarea>
   <button type="button" class="fp-button fp-button-outline" data-team-ai><?= htmlspecialchars(fcc_t('Pripremi s Coachom'), ENT_QUOTES, 'UTF-8') ?></button>
   <div class="ft-coach-guidance" data-team-guidance hidden></div>
   <?php if($outreach['webinar']): ?><div class="ft-next-webinar"><small><?= htmlspecialchars(fcc_t('SLJEDEĆI TIMSKI WEBINAR'), ENT_QUOTES, 'UTF-8') ?></small><strong><?= $h($outreach['webinar']['title']) ?></strong><span><?= $h($outreach['webinar']['when']) ?></span><button type="button" class="fp-text-button" data-team-webinar><?= htmlspecialchars(fcc_t('Dodaj termin i poveznicu u poruku'), ENT_QUOTES, 'UTF-8') ?></button></div><?php else: ?><p class="fp-muted"><?= htmlspecialchars(fcc_t('Novi termin webinara još nije objavljen. Coach neće izmišljati datum.'), ENT_QUOTES, 'UTF-8') ?></p><?php endif ?>
  </aside>
 </div>
 <p class="ft-outreach-live" role="status" aria-live="polite" data-team-status></p>
 <noscript><p><?= htmlspecialchars(fcc_t('Za spremanje i otvaranje poruke uključi JavaScript. Tekst možeš označiti i osobno kopirati.'), ENT_QUOTES, 'UTF-8') ?></p></noscript>
 <section class="ft-outreach-history" aria-label="<?= $h(fcc_t('Tvoja javljanja ovom suradniku')) ?>">
  <p class="fp-muted"><?= $h(fcc_team_outreach_t('history_intro')) ?></p>
  <?php $groups=['preparations'=>array_filter($outreach['history'],fn($r)=>!$r['sent_self_reported_at']),'confirmed'=>array_filter($outreach['history'],fn($r)=>(bool)$r['sent_self_reported_at']),'removed_group'=>$outreach['removed']]; foreach($groups as $group=>$rows): ?>
  <details class="ft-history-group" data-team-group="<?= $group ?>" <?= $group!=='removed_group'?'open':'' ?>><summary><?= $h(fcc_team_outreach_t($group)) ?> <span data-team-count>(<?= count($rows) ?>)</span></summary>
   <?php if($group==='removed_group'): ?><p class="fp-muted"><?= $h(fcc_team_outreach_t('removed_hint')) ?></p><?php endif ?>
   <p class="fp-muted" data-team-empty <?= $rows?'hidden':'' ?>><?= $h(fcc_team_outreach_t('empty')) ?></p>
   <div data-team-history><?php foreach($rows as $row): require __DIR__.'/team-outreach-item.php'; endforeach ?></div>
  </details>
  <?php endforeach ?>
 </section>
 <script type="application/json" data-team-config><?= json_encode(['templates'=>$outreach['templates'],'webinar'=>$outreach['webinar'],'copy'=>fcc_team_outreach_copy(),'history'=>array_map('fcc_team_outreach_public',array_merge($outreach['history'],$outreach['removed']))],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
</div>
