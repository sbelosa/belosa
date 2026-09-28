<?php
/** Focused task workspace. Drafts and task snapshots remain owned by the shared journey. */
defined('ALTUMCODE') || die();
$ui=fcc_jp_presentation((int)$n['position']);$mode=$ui['mode'];
$planning=in_array($mode,['planning','setup'],true);
$social=in_array($mode,['social','video'],true);$mentorTask=$mode==='mentor';$mentor=$n['mentor'];
?>
<section class="fp-panel jp-mission" id="pilot-step" data-pilot-mode="<?= $h($mode) ?>">
 <div class="jp-meta"><span>Korak <?= $n['position'] ?> od <?= FCC_JP_TOTAL ?></span><span><?= $h($ui['label']) ?></span></div>
 <h2><?= $h($ui['title']) ?></h2>
 <p class="jf-purpose"><?= $h($c['why']??'') ?></p>
 <?php if(function_exists('fcc_webinar_enabled'))require THEME_PATH.'views/partner/webinar-journey.php'; ?>
 <?php if(in_array($c['core']??'',['Retention','Development'],true))require THEME_PATH.'views/partner/team-support-guide.php'; ?>
 <ol class="jp-actions jp-today-actions"><?php foreach($c['actions'] as $x): ?><li><?= $h($x) ?></li><?php endforeach ?></ol>
<?php $taskTools=fcc_journey_task_tools($uid,$c,(int)($step['id']??0));if($taskTools): ?><div class="jf-tool-links"><?php foreach($taskTools as [$label,$href,$external]): ?><a class="fp-button fp-button-outline" href="<?= $h($href) ?>" target="_blank" rel="noopener noreferrer"><?= $h($label) ?> ↗</a><?php endforeach ?></div><?php endif ?>
<?php if((int)$c['day']===1)require THEME_PATH.'views/partner/journey-global-guide.php'; ?>
<?php if(!$step): ?>
 <form method="post"><?= $fields('open') ?><button class="fp-button">Otvori svoj korak →</button></form>
<?php else:
 $draft=$n['draft'];$choices=fcc_jp_choices($uid,$c);$default=$choices[(int)($draft['article_id']??0)]??[];
 $initial=$default['message']??($c['communication']['composer']['kind']==='article'?'':$c['communication']['example']);
 if(isset($ui['copy'])&&(!isset($draft['message'])||$draft['message']===$initial))$draft['message']=$ui['copy'];
 $draft+=['article_id'=>0,'message'=>$initial,'contact_id'=>0];
 $draft['message']=fcc_jp_display_message($c,$draft,$default+['message'=>$initial]);
 if($data->error)$draft=array_merge($draft,array_intersect_key($data->values,array_flip(['article_id','message','contact_id'])));
?>
 <?php if(!empty($draft['previous_task_draft']['message'])): ?><details class="jf-previous"><summary>Tvoj nacrt iz prethodne verzije koraka</summary><p>Sačuvan je za pregled. Za aktualni zadatak pripremi odgovarajuću novu poruku.</p><pre><?= $h($draft['previous_task_draft']['message']) ?></pre></details><?php endif ?>
 <?php if(!empty($j['material']['video_id'])): ?><div class="jp-video"><iframe src="https://player.vimeo.com/video/<?= rawurlencode($j['material']['video_id']) ?>" title="Video uz korak" allow="fullscreen; picture-in-picture" allowfullscreen></iframe></div><?php endif ?>
 <div class="jp-workspace" data-pilot-workspace data-pilot-mode="<?= $h($mode) ?>" <?= $j['pro']&&($_GET['coach']??'')==='1'?'data-open-coach':'' ?>>
 <form method="post" class="fp-form" data-pilot-draft id="pilot-compose">
 <?= $fields('draft',['action'=>null]) ?>
 <?php if($c['communication']['composer']['kind']==='article'): ?>
 <label class="jp-product-choice">Koji proizvod želiš pokazati?<select name="article_id" data-pilot-article data-is-not-custom-select><option value="">Odaberi proizvod</option><?php foreach($choices as $id=>$choice): ?><option value="<?= $id ?>" <?= (int)$draft['article_id']===$id?'selected':'' ?>><?= $h($choice['title']) ?></option><?php endforeach ?></select></label>
 <div class="jp-product" data-pilot-product></div>
 <?php endif ?>
 <?php if(isset($ui['media_tip'])): ?><div class="jp-media-guide"><span class="jp-camera" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7h4l2-3h6l2 3h4v13H3z"/><circle cx="12" cy="13" r="4"/></svg></span><div><strong><?= $h($ui['media_title']) ?></strong><p><?= $h($ui['media_tip']) ?></p></div></div><?php endif ?>
 <?php if($mode==='followup'): ?><a class="jp-context-link" href="<?= url('partner?view=progress') ?>">Pogledaj svoje otvorene dogovore →</a><?php endif ?>
 
 <?php if($mentorTask): ?><div class="jp-mentor-target"><?php if($mentor&&!empty($mentor['phone'])): ?><strong><?= $h($mentor['display_name']) ?></strong><span><?= ($mentor['role']??'mentor')==='sponsor'?'Tvoj sponzor':'Dogovoreni mentor' ?> · <?= $h($mentor['phone']) ?></span><?php else: ?><strong>Potvrđeni kontakt sponzora ili mentora još nije povezan</strong><span>Poruku možeš kopirati i osobno poslati svojem sponzoru. Za povezivanje potvrđenog broja otvori podršku.</span><?php endif ?></div><?php endif ?>
 <section class="jp-composer" aria-label="<?= $planning?'Tvoja priprema':($social?'Tekst za tvoju objavu':'Tvoja poruka') ?>">
 <div class="jp-composer-heading"><strong><?= $planning?'Tvoja priprema':($social?'Tekst uz tvoju objavu':'Tvoja poruka') ?></strong><button type="button" class="jp-text-button" data-pilot-edit aria-expanded="false" aria-controls="pilot-editor">Uredi tekst</button></div>
 <p class="jp-message-preview" data-pilot-preview><?= $h($draft['message']?:'Odaberi proizvod pa će se ovdje pojaviti prijedlog teksta.') ?></p>
 <div id="pilot-editor" hidden><label class="jp-sr-only" for="pilot-message">Uredi svoju poruku</label><textarea id="pilot-message" name="message" data-pilot-message rows="5" maxlength="3000"><?= $h($draft['message']) ?></textarea><div class="jp-editor-actions"><button type="button" class="jp-text-button" data-pilot-edit-done>Gotovo s uređivanjem</button><button type="submit" name="action" value="pilot_draft" class="jp-text-button" data-pilot-save>Spremi nacrt za kasnije</button></div></div>
 <div class="jp-composer-actions">
 <?php if($mentorTask): ?>
  <?php if($mentor&&!empty($mentor['phone'])): ?><button type="button" class="fp-button" data-pilot-whatsapp data-phone="<?= $h(preg_replace('/\D/','',$mentor['phone'])) ?>"><?= $h($ui['primary']) ?> →</button><?php else: ?><a class="fp-button" data-pilot-support href="<?= url('feedback-tickets') ?>">Otvori podršku →</a><?php endif ?>
  <button type="button" class="jp-text-button" data-pilot-copy>Kopiraj poruku</button>
 <?php elseif($social||$planning): ?>
  <button type="button" class="fp-button" data-pilot-copy><?= $h($ui['primary']) ?></button>
 <?php elseif($j['pro']): ?>
  <button type="button" class="fp-button" data-pilot-whatsapp><?= $h($ui['primary']) ?> →</button><button type="button" class="jp-text-button" data-pilot-copy>Kopiraj tekst</button>
 <?php else: ?><button type="button" class="fp-button" data-pilot-copy>Kopiraj poruku</button><?php endif ?>
 </div>
 <p class="jp-caption"><?= $planning?'Uredi pripremu prema svojoj temi i spremi je za nastavak.':($social?'Dodaj tekst svojoj fotografiji ili snimci u društvenoj mreži.':'Pregledaj poruku i prilagodi je osobi prije slanja.') ?><?php if($c['communication']['composer']['kind']==='article'): ?> Tvoja poveznica je već uključena.<?php endif ?></p>
 <p class="jp-live" data-pilot-status role="status" aria-live="polite"></p>
 </section>
 <?php if(in_array((int)$n['position'],[2,3,4],true)&&$data->cards): $ownCard=array_values(array_filter($data->cards,fn($card)=>(int)$card['is_enabled']===1))[0]??null;if($ownCard): ?><div class="jp-profile-link"><span>Još dodaj ovu poveznicu u opis profila.</span><div><code><?= $h($ownCard['public_url']) ?></code><button type="button" class="jp-text-button" data-copy="<?= $h($ownCard['public_url']) ?>">Kopiraj poveznicu</button></div></div><?php endif;endif ?>
 <div class="jp-finish-bar"><span>Gotov/a s današnjom radnjom?</span><button type="button" class="fp-button fp-button-outline" data-pilot-finish>Označi dovršeno ✓</button></div>
 <details class="jp-help jp-help-main" <?= ($_GET['coach']??'')==='1'?'open':'' ?>><summary>Trebaš pomoć?</summary>
  <h3>Ako danas nemaš takvu priliku</h3><p><?= $h($c['alternative']) ?></p>
  <h3>Prvo želiš uvježbati?</h3><p><?= $h($c['practice']) ?></p><button type="button" class="fp-button fp-button-outline" data-pilot-practice>Zabilježi odrađenu vježbu</button>
  <details class="jp-help"><summary>Želiš probati nešto zahtjevnije?</summary><p><?= $h($c['advanced_challenge']) ?></p></details>
  <div class="jp-support"><a <?= $j['pro']?'data-pilot-coach':'' ?> href="<?= url('partner?view=coach') ?>"><?= $j['pro']?'Pitaj svojeg AI Coacha':'Pogledaj PRO Coacha' ?> →</a>
  <?php if(!$mentorTask): ?><?php if($mentor&&!empty($mentor['phone'])): ?><button type="button" class="jp-text-button" data-pilot-mentor="<?= $h(preg_replace('/\D/','',$mentor['phone'])) ?>" data-mentor-message="<?= $h('Bok! '.$c['mentor_question'].' Kada ti odgovara da to kratko prođemo?') ?>"><?= ($mentor['role']??'mentor')==='sponsor'?'Pitaj sponzora':'Pitaj mentora' ?> · <?= $h($mentor['display_name']) ?></button><?php else: ?><a data-pilot-support href="<?= url('feedback-tickets') ?>">Zatraži pomoć →</a><?php endif ?><?php endif ?>
  </div>
 </details>
 <dialog class="jp-dialog" data-pilot-dialog aria-labelledby="pilot-confirm-title">
 <div class="jp-dialog-top"><h3 id="pilot-confirm-title">Potvrdi svoj korak</h3><button type="button" class="jp-dialog-close" data-pilot-close aria-label="Zatvori potvrdu">×</button></div>
 <fieldset disabled data-pilot-confirm-fields>
 <legend class="jp-sr-only">Što si napravio/la?</legend>
 <p class="jp-caption" data-pilot-criterion><?= $h($c['completion']) ?></p>
 <div data-pilot-real-options><?php foreach($ui['done'] as $kind=>$label): ?><label class="jp-choice"><input type="radio" name="kind" value="<?= $kind ?>"> <span><?= $h($label) ?></span></label><?php endforeach ?></div>
 <label class="jp-choice" data-pilot-practice-option hidden><input type="radio" name="kind" value="practice"> <span>Odradio/la sam konkretnu vježbu</span></label>
 <p class="jp-caption" data-pilot-practice-tip hidden><?= $h($c['practice']) ?> Vježba se bilježi zasebno od objave ili razgovora.</p>
 <label data-pilot-detail-label hidden>Što si pripremio/la u vježbi?<textarea name="detail" rows="3" maxlength="500" data-pilot-detail placeholder="Upiši svoj pripremljeni uvod, pitanje ili opis snimljenog pokušaja."><?= $h($data->error?($data->values['detail']??''):'') ?></textarea></label>
 <?php if(!$social&&!$mentorTask&&!$planning): ?><details class="jp-help" data-pilot-contact><summary>Poveži s kontaktom (neobvezno)</summary><label>Osoba iz mojih kontakata<select name="contact_id" data-is-not-custom-select><option value="0">Bez povezivanja</option><?php foreach($data->contacts as $contact):if($contact['contact_permission']==='do_not_contact')continue; ?><option value="<?= (int)$contact['id'] ?>" <?= (int)$draft['contact_id']===(int)$contact['id']?'selected':'' ?>><?= $h($contact['name']) ?></option><?php endforeach ?></select></label><small>Ovdje možeš povezati jedan razgovor za nastavak. Nije potrebno unositi osobu da završiš korak.</small></details><?php endif ?>
 <button class="fp-button" type="submit" name="action" value="pilot_finish" data-pilot-confirm>Spremi dovršeni korak →</button>
 <p class="jp-caption">Odaberi što si napravio/la.</p>
 </fieldset></dialog>
 </form>
 <script type="application/json" data-pilot-choices><?= json_encode($choices,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
 </div>
<?php endif ?>
</section>
<script src="<?= ASSETS_FULL_URL ?>js/fcc-journey-pilot.js?v=<?= filemtime(THEME_PATH.'assets/js/fcc-journey-pilot.js') ?>" defer></script>
