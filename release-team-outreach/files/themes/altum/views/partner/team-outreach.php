<?php defined('ALTUMCODE') || die();
if(!$outreach['ready']): ?><p>Priprema poruka uskoro će biti dostupna.</p><?php return; endif;
$ot=$outreach['target'];
\Altum\Event::add_content('<script defer src="'.ASSETS_FULL_URL.'js/fcc-team-outreach.js?v='.filemtime(ASSETS_PATH.'js/fcc-team-outreach.js').'"></script>','head');
?>
<div class="ft-outreach" data-team-outreach data-endpoint="<?= url('partner/team') ?>" data-member="<?= $subject ?>" data-contact-stamp="<?= $h($ot['contact_stamp']) ?>" data-phone-available="<?= $ot['phone']?'1':'0' ?>">
 <?= $token ?>
 <p class="ft-outreach-lead">Pripremi poruku za <strong><?= $h($p['name']) ?></strong>. Odaberi razlog javljanja i prilagodi tekst svojem razgovoru.</p>
 <div class="ft-purpose" role="group" aria-label="Razlog javljanja"><?php foreach(fcc_team_outreach_purposes() as $key=>$label): ?><button type="button" class="ft-purpose-button" data-purpose="<?= $key ?>" aria-pressed="<?= $key==='checkin'?'true':'false' ?>"><?= $h($label) ?></button><?php endforeach ?></div>
 <div class="ft-outreach-grid">
  <div class="ft-message-box">
   <label for="team-message">Tvoja poruka</label><textarea id="team-message" rows="8" maxlength="4000" data-team-message><?= $h($outreach['templates']['checkin']) ?></textarea>
   <div class="ft-outreach-actions"><button type="button" class="fp-button" data-team-open <?= !$ot['phone']?'disabled':'' ?>><i class="fab fa-whatsapp" aria-hidden="true"></i> Otvori WhatsApp</button><button type="button" class="fp-button fp-button-outline" data-team-copy>Kopiraj poruku</button><button type="button" class="fp-text-button" data-team-save>Spremi nacrt</button></div>
   <p class="fp-muted"><?= $ot['phone']?'Otvara se broj '.$h($ot['phone']).' s cijelim upisanim tekstom.':'WhatsApp će biti dostupan kada suradnik ima potvrđen poslovni broj.' ?> Poruku šalješ osobno.</p>
   <div class="ft-outreach-confirm" data-team-confirm-panel hidden><strong data-team-draft-status></strong><p>Sam klik ne potvrđuje da je poruka poslana. Potvrdi tek nakon stvarnog slanja u WhatsAppu.</p><button type="button" class="fp-button fp-button-outline" data-team-confirm>Potvrđujem da sam poslao/la</button><a class="fp-link" target="_blank" rel="noopener" data-team-continue hidden>Nastavi u WhatsApp</a></div>
   <label class="ft-followup-label">Kada želiš provjeriti kako je prošlo?<input type="date" data-team-due min="<?= $h(fcc_partner_today()) ?>"></label><small>Podsjetnik se pojavljuje u rubrici Danas nakon otvaranja WhatsAppa. Coach provjerava status prije novog prijedloga.</small>
  </div>
  <aside class="ft-outreach-coach">
   <span class="fp-eyebrow">POMOĆ TVOJEG COACHA</span><h3>Pronađi dobar početak.</h3><p>Opiši što znaš i kakvu podršku želiš ponuditi. Coach uzima u obzir zabilježeni napredak i dostupne CC podatke.</p>
   <label for="team-situation">Što želiš postići?</label><textarea id="team-situation" rows="4" maxlength="1500" data-team-situation placeholder="Npr. želim ponuditi pomoć s novim FCC-om i pozvati na sljedeći webinar."></textarea>
   <button type="button" class="fp-button fp-button-outline" data-team-ai>Pripremi s Coachom</button>
   <div class="ft-coach-guidance" data-team-guidance hidden></div>
   <?php if($outreach['webinar']): ?><div class="ft-next-webinar"><small>SLJEDEĆI TIMSKI WEBINAR</small><strong><?= $h($outreach['webinar']['title']) ?></strong><span><?= $h($outreach['webinar']['when']) ?></span><button type="button" class="fp-text-button" data-team-webinar>Dodaj termin i poveznicu u poruku</button></div><?php else: ?><p class="fp-muted">Novi termin webinara još nije objavljen. Coach neće izmišljati datum.</p><?php endif ?>
  </aside>
 </div>
 <p class="ft-outreach-live" role="status" aria-live="polite" data-team-status></p>
 <noscript><p>Za spremanje i otvaranje poruke uključi JavaScript. Tekst možeš označiti i osobno kopirati.</p></noscript>
 <details class="ft-outreach-history" <?= $outreach['history']?'open':'' ?>><summary>Tvoja javljanja ovom suradniku <span>(<?= count($outreach['history']) ?>)</span></summary>
  <p class="fp-muted">Ovdje su tvoje pripreme, otvaranja WhatsAppa i osobne potvrde slanja. Zajednički dogovori i bilješke nalaze se ispod.</p>
  <div data-team-history><?php foreach($outreach['history'] as $row): ?><article class="ft-outreach-item" data-outreach-id="<?= (int)$row['id'] ?>" data-outreach-version="<?= (int)$row['version'] ?>" data-request-key="<?= $h($row['request_key']) ?>">
   <div class="fp-section-title"><strong><?= $h(fcc_team_outreach_purposes()[$row['purpose']]) ?></strong><span class="ft-status"><?= $h($row['status_label']) ?></span></div><small><?= $h($row['created_at']) ?> UTC<?= $row['source']==='ai'?' · Pripremljeno s Coachom':'' ?></small>
   <details><summary>Pogledaj tekst</summary><p class="ft-history-text"><?= nl2br($h($row['message'])) ?></p><?php if($row['guidance']): ?><p class="fp-muted"><?= $h($row['guidance']) ?></p><?php endif ?></details>
   <?php if($row['outcome']): ?><p><?= $h($row['outcome']) ?></p><?php endif ?>
   <?php if(!$row['closed_at']): ?><div class="ft-outreach-actions">
    <?php if(!$row['sent_self_reported_at']): ?><button type="button" class="fp-text-button" data-team-resume="<?= $h(json_encode(fcc_team_outreach_public($row),JSON_UNESCAPED_UNICODE)) ?>">Uredi i nastavi</button><?php endif ?>
    <?php if($row['opened_at']&&!$row['sent_self_reported_at']): ?><button type="button" class="fp-button fp-button-outline" data-history-operation="confirm">Potvrđujem slanje ove poruke</button><?php endif ?>
   </div><details><summary><?= $row['due_date']?'Provjeri ponovno: '.$h($row['due_date']):'Dogovori podsjetnik ili završi praćenje' ?></summary><div class="ft-history-followup"><label>Sljedeća provjera<input type="date" data-history-due value="<?= $h($row['due_date']??'') ?>"></label><button type="button" class="fp-button fp-button-outline" data-history-operation="followup">Spremi podsjetnik</button><label>Ishod razgovora, ako ga znaš<textarea rows="2" maxlength="1000" data-history-outcome placeholder="Npr. dogovorili smo zajednički pregled FCC-a."></textarea></label><button type="button" class="fp-text-button" data-history-operation="close">Završi praćenje</button></div></details><?php endif ?>
  </article><?php endforeach ?></div>
 </details>
 <script type="application/json" data-team-config><?= json_encode(['templates'=>$outreach['templates'],'webinar'=>$outreach['webinar']],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?></script>
</div>
