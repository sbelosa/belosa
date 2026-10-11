<?php
/** Focused task workspace. Drafts and task snapshots remain owned by the shared journey. */
defined('ALTUMCODE') || die();
if(function_exists('fcc_journey_warm')&&fcc_journey_warm($c)){require THEME_PATH.'views/partner/journey-warm-step.php';return;}
$ui=fcc_localize_copy(fcc_jp_presentation((int)$n['position']));$mode=$ui['mode'];
$planning=in_array($mode,['planning','setup'],true);
$social=in_array($mode,['social','video'],true);$mentorTask=$mode==='mentor';$mentor=$n['mentor'];
?>
<section class="fp-panel jp-mission" id="pilot-step" data-pilot-mode="<?= $h($mode) ?>">
 <div class="jp-meta"><span><?= htmlspecialchars(fcc_t('Korak'), ENT_QUOTES, 'UTF-8') ?> <?= $n['position'] ?> <?= htmlspecialchars(fcc_t('od'), ENT_QUOTES, 'UTF-8') ?> <?= FCC_JP_TOTAL ?></span><span><?= $h($ui['label']) ?></span></div>
 <h2><?= $h($ui['title']) ?></h2>
 <p class="jf-purpose"><?= $h($c['why']??'') ?></p>
 <?php if(function_exists('fcc_webinar_enabled'))require THEME_PATH.'views/partner/webinar-journey.php'; ?>
 <?php if(in_array($c['core']??'',['Retention','Development'],true))require THEME_PATH.'views/partner/team-support-guide.php'; ?>
 <ol class="jp-actions jp-today-actions"><?php foreach($c['actions'] as $x): ?><li><?= $h($x) ?></li><?php endforeach ?></ol>
<?php $taskTools=fcc_journey_task_tools($uid,$c,(int)($step['id']??0));if($taskTools): ?><div class="jf-tool-links"><?php foreach($taskTools as [$label,$href,$external]): ?><a class="fp-button fp-button-outline" href="<?= $h($href) ?>" target="_blank" rel="noopener noreferrer"><?= $h(fcc_t($label)) ?> ↗</a><?php endforeach ?></div><?php endif ?>
<?php require THEME_PATH.'views/partner/journey-learning.php'; ?>
<?php if((int)$c['day']===1)require THEME_PATH.'views/partner/journey-global-guide.php'; ?>
<?php if(!$step): ?>
 <form method="post"><?= $fields('open') ?><button class="fp-button"><?= htmlspecialchars(fcc_t('Otvori svoj korak →'), ENT_QUOTES, 'UTF-8') ?></button></form>
<?php else:
 $draft=$n['draft'];$choices=fcc_jp_choices($uid,$c);$default=$choices[(int)($draft['article_id']??0)]??[];
 $initial=$default['message']??($c['communication']['composer']['kind']==='article'?'':$c['communication']['example']);
 if(isset($ui['copy'])&&(!isset($draft['message'])||$draft['message']===$initial))$draft['message']=$ui['copy'];
 $draft+=['article_id'=>0,'message'=>$initial,'contact_id'=>0];
 $draft['message']=fcc_jp_display_message($c,$draft,$default+['message'=>$initial]);
 if($data->error)$draft=array_merge($draft,array_intersect_key($data->values,array_flip(['article_id','message','contact_id'])));
?>
 <?php if(!empty($draft['previous_task_draft']['message'])): ?><details class="jf-previous"><summary><?= htmlspecialchars(fcc_t('Tvoj nacrt iz prethodne verzije koraka'), ENT_QUOTES, 'UTF-8') ?></summary><p><?= htmlspecialchars(fcc_t('Sačuvan je za pregled. Za aktualni zadatak pripremi odgovarajuću novu poruku.'), ENT_QUOTES, 'UTF-8') ?></p><pre><?= $h($draft['previous_task_draft']['message']) ?></pre></details><?php endif ?>
 <?php if(!empty($j['material']['video_id'])): ?><div class="jp-video"><iframe src="https://player.vimeo.com/video/<?= rawurlencode($j['material']['video_id']) ?>" title="<?= htmlspecialchars(fcc_t('Video uz korak'), ENT_QUOTES, 'UTF-8') ?>" allow="fullscreen; picture-in-picture" allowfullscreen></iframe></div><?php endif ?>
 <div class="jp-workspace" data-pilot-workspace data-pilot-mode="<?= $h($mode) ?>" <?= $j['pro']&&($_GET['coach']??'')==='1'?'data-open-coach':'' ?>>
 <form method="post" class="fp-form" data-pilot-draft id="pilot-compose">
 <?= $fields('draft',['action'=>null]) ?>
 <?php if($c['communication']['composer']['kind']==='article'): ?>
 <label class="jp-product-choice"><?= htmlspecialchars(fcc_t('Koji proizvod želiš pokazati?'), ENT_QUOTES, 'UTF-8') ?><select name="article_id" data-pilot-article data-is-not-custom-select><option value=""><?= htmlspecialchars(fcc_t('Odaberi proizvod'), ENT_QUOTES, 'UTF-8') ?></option><?php foreach($choices as $id=>$choice): ?><option value="<?= $id ?>" <?= (int)$draft['article_id']===$id?'selected':'' ?>><?= $h($choice['title']) ?></option><?php endforeach ?></select></label>
 <div class="jp-product" data-pilot-product></div>
 <?php endif ?>
 <?php if(isset($ui['media_tip'])): ?><div class="jp-media-guide"><span class="jp-camera" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7h4l2-3h6l2 3h4v13H3z"/><circle cx="12" cy="13" r="4"/></svg></span><div><strong><?= $h($ui['media_title']) ?></strong><p><?= $h($ui['media_tip']) ?></p></div></div><?php endif ?>
 <?php if($mode==='followup'): ?><a class="jp-context-link" href="<?= url('partner?view=progress') ?>"><?= htmlspecialchars(fcc_t('Pogledaj svoje otvorene dogovore →'), ENT_QUOTES, 'UTF-8') ?></a><?php endif ?>
 
 <?php if($mentorTask): ?><div class="jp-mentor-target"><?php if($mentor&&!empty($mentor['phone'])): ?><strong><?= $h($mentor['display_name']) ?></strong><span><?= ($mentor['role']??'mentor')==='sponsor'?fcc_t('Tvoj sponzor'):fcc_t('Dogovoreni mentor') ?> · <?= $h($mentor['phone']) ?></span><?php else: ?><strong><?= htmlspecialchars(fcc_t('Potvrđeni kontakt sponzora ili mentora još nije povezan'), ENT_QUOTES, 'UTF-8') ?></strong><span><?= htmlspecialchars(fcc_t('Poruku možeš kopirati i osobno poslati svojem sponzoru. Za povezivanje potvrđenog broja otvori podršku.'), ENT_QUOTES, 'UTF-8') ?></span><?php endif ?></div><?php endif ?>
 <section class="jp-composer" aria-label="<?= $planning?fcc_t('Tvoja priprema'):($social?fcc_t('Tekst za tvoju objavu'):fcc_t('Tvoja poruka')) ?>">
 <div class="jp-composer-heading"><strong><?= $planning?fcc_t('Tvoja priprema'):($social?fcc_t('Tekst uz tvoju objavu'):fcc_t('Tvoja poruka')) ?></strong><button type="button" class="jp-text-button" data-pilot-edit aria-expanded="false" aria-controls="pilot-editor"><?= htmlspecialchars(fcc_t('Uredi tekst'), ENT_QUOTES, 'UTF-8') ?></button></div>
 <p class="jp-message-preview" data-pilot-preview><?= $h($draft['message']?:fcc_t('Odaberi proizvod pa će se ovdje pojaviti prijedlog teksta.')) ?></p>
 <div id="pilot-editor" hidden><label class="jp-sr-only" for="pilot-message"><?= htmlspecialchars(fcc_t('Uredi svoju poruku'), ENT_QUOTES, 'UTF-8') ?></label><textarea id="pilot-message" name="message" data-pilot-message rows="5" maxlength="3000"><?= $h($draft['message']) ?></textarea><div class="jp-editor-actions"><button type="button" class="jp-text-button" data-pilot-edit-done><?= htmlspecialchars(fcc_t('Gotovo s uređivanjem'), ENT_QUOTES, 'UTF-8') ?></button><button type="submit" name="action" value="pilot_draft" class="jp-text-button" data-pilot-save><?= htmlspecialchars(fcc_t('Spremi nacrt za kasnije'), ENT_QUOTES, 'UTF-8') ?></button></div></div>
 <div class="jp-composer-actions">
 <?php if($mentorTask): ?>
  <?php if($mentor&&!empty($mentor['phone'])): ?><button type="button" class="fp-button" data-pilot-whatsapp data-phone="<?= $h(preg_replace('/\D/','',$mentor['phone'])) ?>"><?= $h($ui['primary']) ?> →</button><?php else: ?><a class="fp-button" data-pilot-support href="<?= url('feedback-tickets') ?>"><?= htmlspecialchars(fcc_t('Otvori podršku →'), ENT_QUOTES, 'UTF-8') ?></a><?php endif ?>
  <button type="button" class="jp-text-button" data-pilot-copy><?= htmlspecialchars(fcc_t('Kopiraj poruku'), ENT_QUOTES, 'UTF-8') ?></button>
 <?php elseif($social||$planning): ?>
  <button type="button" class="fp-button" data-pilot-copy><?= $h($ui['primary']) ?></button>
 <?php elseif($j['pro']): ?>
  <button type="button" class="fp-button" data-pilot-whatsapp><?= $h($ui['primary']) ?> →</button><button type="button" class="jp-text-button" data-pilot-copy><?= htmlspecialchars(fcc_t('Kopiraj tekst'), ENT_QUOTES, 'UTF-8') ?></button>
 <?php else: ?><button type="button" class="fp-button" data-pilot-copy><?= htmlspecialchars(fcc_t('Kopiraj poruku'), ENT_QUOTES, 'UTF-8') ?></button><?php endif ?>
 </div>
 <p class="jp-caption"><?= $planning?fcc_t('Uredi pripremu prema svojoj temi i spremi je za nastavak.'):($social?fcc_t('Dodaj tekst svojoj fotografiji ili snimci u društvenoj mreži.'):fcc_t('Pregledaj poruku i prilagodi je osobi prije slanja.')) ?><?php if($c['communication']['composer']['kind']==='article'): ?> <?= htmlspecialchars(fcc_t('Tvoja poveznica je već uključena.'), ENT_QUOTES, 'UTF-8') ?><?php endif ?></p>
 <p class="jp-live" data-pilot-status role="status" aria-live="polite"></p>
 </section>
 <?php if(in_array((int)$n['position'],[2,3,4],true)&&$data->cards): $ownCard=array_values(array_filter($data->cards,fn($card)=>(int)$card['is_enabled']===1))[0]??null;if($ownCard): ?><div class="jp-profile-link"><span><?= htmlspecialchars(fcc_t('Još dodaj ovu poveznicu u opis profila.'), ENT_QUOTES, 'UTF-8') ?></span><div><code><?= $h($ownCard['public_url']) ?></code><button type="button" class="jp-text-button" data-copy="<?= $h($ownCard['public_url']) ?>"><?= htmlspecialchars(fcc_t('Kopiraj poveznicu'), ENT_QUOTES, 'UTF-8') ?></button></div></div><?php endif;endif ?>
 <div class="jp-finish-bar"><span><?= htmlspecialchars(fcc_t('Gotov/a s današnjom radnjom?'), ENT_QUOTES, 'UTF-8') ?></span><button type="button" class="fp-button fp-button-outline" data-pilot-finish><?= htmlspecialchars(fcc_t('Označi dovršeno ✓'), ENT_QUOTES, 'UTF-8') ?></button></div>
 <details class="jp-help jp-help-main" <?= ($_GET['coach']??'')==='1'?'open':'' ?>><summary><?= htmlspecialchars(fcc_t('Trebaš pomoć?'), ENT_QUOTES, 'UTF-8') ?></summary>
  <h3><?= htmlspecialchars(fcc_t('Ako danas nemaš takvu priliku'), ENT_QUOTES, 'UTF-8') ?></h3><p><?= $h($c['alternative']) ?></p>
  <h3><?= htmlspecialchars(fcc_t('Prvo želiš uvježbati?'), ENT_QUOTES, 'UTF-8') ?></h3><p><?= $h($c['practice']) ?></p><button type="button" class="fp-button fp-button-outline" data-pilot-practice><?= htmlspecialchars(fcc_t('Zabilježi odrađenu vježbu'), ENT_QUOTES, 'UTF-8') ?></button>
  <details class="jp-help"><summary><?= htmlspecialchars(fcc_t('Želiš probati nešto zahtjevnije?'), ENT_QUOTES, 'UTF-8') ?></summary><p><?= $h($c['advanced_challenge']) ?></p></details>
  <div class="jp-support"><a <?= $j['pro']?'data-pilot-coach':'' ?> href="<?= url('partner?view=coach') ?>"><?= $j['pro']?fcc_t('Pitaj svojeg AI Coacha'):fcc_t('Pogledaj PRO Coacha') ?> →</a>
  <?php if(!$mentorTask): ?><?php if($mentor&&!empty($mentor['phone'])): ?><button type="button" class="jp-text-button" data-pilot-mentor="<?= $h(preg_replace('/\D/','',$mentor['phone'])) ?>" data-mentor-message="<?= $h(fcc_t('Bok! ').$c['mentor_question'].fcc_t(' Kada ti odgovara da to kratko prođemo?')) ?>"><?= ($mentor['role']??'mentor')==='sponsor'?fcc_t('Pitaj sponzora'):fcc_t('Pitaj mentora') ?> · <?= $h($mentor['display_name']) ?></button><?php else: ?><a data-pilot-support href="<?= url('feedback-tickets') ?>"><?= htmlspecialchars(fcc_t('Zatraži pomoć →'), ENT_QUOTES, 'UTF-8') ?></a><?php endif ?><?php endif ?>
  </div>
 </details>
 <dialog class="jp-dialog" data-pilot-dialog aria-labelledby="pilot-confirm-title">
 <div class="jp-dialog-top"><h3 id="pilot-confirm-title"><?= htmlspecialchars(fcc_t('Potvrdi svoj korak'), ENT_QUOTES, 'UTF-8') ?></h3><button type="button" class="jp-dialog-close" data-pilot-close aria-label="<?= htmlspecialchars(fcc_t('Zatvori potvrdu'), ENT_QUOTES, 'UTF-8') ?>">×</button></div>
 <fieldset disabled data-pilot-confirm-fields>
 <legend class="jp-sr-only"><?= htmlspecialchars(fcc_t('Što si napravio/la?'), ENT_QUOTES, 'UTF-8') ?></legend>
 <p class="jp-caption" data-pilot-criterion><?= $h($c['completion']) ?></p>
 <div data-pilot-real-options><?php foreach($ui['done'] as $kind=>$label): ?><label class="jp-choice"><input type="radio" name="kind" value="<?= $kind ?>"> <span><?= $h(fcc_t($label)) ?></span></label><?php endforeach ?></div>
 <label class="jp-choice" data-pilot-practice-option hidden><input type="radio" name="kind" value="practice"> <span><?= htmlspecialchars(fcc_t('Odradio/la sam konkretnu vježbu'), ENT_QUOTES, 'UTF-8') ?></span></label>
 <p class="jp-caption" data-pilot-practice-tip hidden><?= $h($c['practice']) ?> <?= htmlspecialchars(fcc_t('Vježba se bilježi zasebno od objave ili razgovora.'), ENT_QUOTES, 'UTF-8') ?></p>
 <label data-pilot-detail-label hidden><?= htmlspecialchars(fcc_t('Što si pripremio/la u vježbi?'), ENT_QUOTES, 'UTF-8') ?><textarea name="detail" rows="3" maxlength="500" data-pilot-detail placeholder="<?= htmlspecialchars(fcc_t('Upiši svoj pripremljeni uvod, pitanje ili opis snimljenog pokušaja.'), ENT_QUOTES, 'UTF-8') ?>"><?= $h($data->error?($data->values['detail']??''):'') ?></textarea></label>
 <?php if(!$social&&!$mentorTask&&!$planning): ?><details class="jp-help" data-pilot-contact><summary><?= htmlspecialchars(fcc_t('Poveži s kontaktom (neobvezno)'), ENT_QUOTES, 'UTF-8') ?></summary><label><?= htmlspecialchars(fcc_t('Osoba iz mojih kontakata'), ENT_QUOTES, 'UTF-8') ?><select name="contact_id" data-is-not-custom-select><option value="0"><?= htmlspecialchars(fcc_t('Bez povezivanja'), ENT_QUOTES, 'UTF-8') ?></option><?php foreach($data->contacts as $contact):if($contact['contact_permission']==='do_not_contact')continue; ?><option value="<?= (int)$contact['id'] ?>" <?= (int)$draft['contact_id']===(int)$contact['id']?'selected':'' ?>><?= $h($contact['name']) ?></option><?php endforeach ?></select></label><small><?= htmlspecialchars(fcc_t('Ovdje možeš povezati jedan razgovor za nastavak. Nije potrebno unositi osobu da završiš korak.'), ENT_QUOTES, 'UTF-8') ?></small></details><?php endif ?>
 <button class="fp-button" type="submit" name="action" value="pilot_finish" data-pilot-confirm><?= htmlspecialchars(fcc_t('Spremi dovršeni korak →'), ENT_QUOTES, 'UTF-8') ?></button>
 <p class="jp-caption"><?= htmlspecialchars(fcc_t('Odaberi što si napravio/la.'), ENT_QUOTES, 'UTF-8') ?></p>
 </fieldset></dialog>
 </form>
 <script type="application/json" data-pilot-choices><?= json_encode($choices,JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
 </div>
<?php endif ?>
</section>
<script src="<?= ASSETS_FULL_URL ?>js/fcc-journey-pilot.js?v=<?= filemtime(THEME_PATH.'assets/js/fcc-journey-pilot.js') ?>" defer></script>
