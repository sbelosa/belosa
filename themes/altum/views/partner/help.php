<?php defined('ALTUMCODE') || die();
$uid=(int)$this->user->user_id;$locale=$data->locale;$h='fcc_partner_h';$t=fn($k)=>fcc_help_t($k,$locale);$article=$data->article;$state=$data->state;
$form=fn($action,$id)=>'<input type="hidden" name="token" value="'.$h(\Altum\Csrf::get()).'"><input type="hidden" name="topic" value="'.$h($id).'"><input type="hidden" name="action" value="'.$h($action).'">';
?>
<div class="fh-page" data-fcc-help-page data-fcc-help-coach-access="<?= fcc_pro_has_access($uid)&&fcc_ai_user_has_coach_access($this->user)?'1':'0' ?>" data-fcc-help-locale="<?= $h($locale) ?>" data-fcc-help-anchor="<?= $h($data->anchor) ?>">
 <header class="fh-heading">
  <div><p class="fh-eyebrow">Forever Card Club</p><h1><?= $h($article?$article['title']:$t('title')) ?></h1><p class="fh-lead"><?= $h($article?$article['summary']:$t('subtitle')) ?></p></div>
  <?php if($article): ?><a class="fp-button fp-button-secondary" href="<?= fcc_help_url() ?>"><?= $h($t('index')) ?></a><?php else: ?><a class="fp-button fp-button-secondary" href="<?= fcc_help_url('start') ?>"><?= $h($t('beginner')) ?></a><?php endif ?>
 </header>
 <?php if($data->error): ?><p class="fp-message fp-error" role="alert"><?= $h($data->error) ?></p><?php endif ?>
 <div class="fh-status" aria-live="polite" role="status" data-fcc-help-status data-copied="<?= $h($t('copied')) ?>" data-copy-error="<?= $h($t('copy_error')) ?>" data-coach-unavailable="<?= $h($t('coach_unavailable')) ?>" data-coach-draft="<?= $h($t('coach_draft')) ?>"></div>
 <?php if(!$article): ?>
 <form method="get" class="fh-search" role="search">
  <label for="fh-query"><?= $h($t('search')) ?></label><div><input type="search" name="q" id="fh-query" maxlength="180" value="<?= $h($data->q) ?>" placeholder="<?= $h($t('search_placeholder')) ?>"><button class="fp-button" type="submit"><?= $h($t('search_button')) ?></button></div>
  <?php if($data->category): ?><input type="hidden" name="category" value="<?= $h($data->category) ?>"><?php endif ?>
 </form>
 <nav class="fh-categories" aria-label="<?= $h($t('all')) ?>">
  <a <?= $data->category===''?'aria-current="page"':'' ?> href="<?= url('partner/help'.($data->q!==''?'?q='.rawurlencode($data->q):'')) ?>"><?= $h($t('all')) ?></a>
  <?php foreach(array_unique(array_column(fcc_help_manifest(),'category')) as $category): ?><a <?= $data->category===$category?'aria-current="page"':'' ?> href="<?= url('partner/help?'.http_build_query(['category'=>$category,'q'=>$data->q])) ?>"><?= $h($t('category_'.$category)) ?></a><?php endforeach ?>
 </nav>
 <p class="fh-progress"><?= $h($t('progress')) ?>: <strong><?= count($state['read']) ?> / <?= count(fcc_help_manifest()) ?></strong></p>
 <?php if($data->q===''&&$data->category===''&&$data->suggestion): $s=$data->suggestion; ?>
 <section class="fh-suggestion" aria-label="<?= $h($t('learn_next')) ?>">
  <div><p class="fh-eyebrow"><?= $h($t('learn_next')) ?></p><h2><a href="<?= fcc_help_url($s['id']) ?>"><?= $h($s['title']) ?></a></h2><p><?= $h($t('suggestion_reason')) ?></p></div>
  <div class="fh-actions"><a class="fp-button" href="<?= fcc_help_url($s['id']) ?>"><?= $h($s['title']) ?></a><form method="post"><?= $form('later',$s['id']) ?><button class="fp-text-button"><?= $h($t('later')) ?></button></form><form method="post"><?= $form('dismiss',$s['id']) ?><button class="fp-text-button"><?= $h($t('dismiss')) ?></button></form></div>
 </section>
 <?php endif ?>
 <?php if(!$data->articles): ?><p class="fh-empty"><?= $h($t('no_results')) ?> <a href="<?= fcc_help_url() ?>"><?= $h($t('clear')) ?></a></p><?php endif ?>
 <div class="fh-grid">
  <?php foreach($data->articles as $item): ?><article class="fh-card">
   <p class="fh-eyebrow"><?= $h($t('category_'.$item['category'])) ?> <span><?= (int)$item['minutes'] ?> <?= $h($t('minutes')) ?></span></p><h2><a href="<?= fcc_help_url($item['id']) ?>"><?= $h($item['title']) ?></a></h2><p><?= $h($item['summary']) ?></p>
   <?php if(isset($state['read'][$item['id']])): ?><span class="fh-read"><?= fcc_partner_icon('check') ?> <?= $h($t('read')) ?></span><?php endif ?>
  </article><?php endforeach ?>
 </div>
 <?php if($state['dismissed']||$state['later']): ?><form method="post" class="fh-restore"><?= $form('restore','') ?><button class="fp-text-button"><?= $h($t('restore')) ?></button></form><?php endif ?>
 <p class="fh-footer"><a href="<?= url('feedback-tickets') ?>"><?= $h($t('support')) ?></a><?php if(\Altum\Authentication::is_admin()): ?> <a href="<?= url('partner/help?review=1') ?>"><?= $h($t('review')) ?></a><?php endif ?></p>
 <?php else: ?>
 <p class="fh-meta"><?= (int)$article['minutes'] ?> <?= $h($t('minutes')) ?>. <?= $h($t('verified')) ?>: <?= $h(fcc_help_meta()['checked_at']) ?>.</p>
 <?php if($article['content_locale']!==$locale): ?><p class="fp-message" role="note"><?= $h($t('fallback')) ?></p><?php endif ?>
 <div class="fh-layout"><nav class="fh-toc" aria-label="<?= $h($t('contents')) ?>"><h2><?= $h($t('contents')) ?></h2><?php foreach($article['sections'] as $section): ?><a href="<?= $h(fcc_help_url($article['id'],$section['id'])) ?>"><?= $h($section['title']) ?></a><?php endforeach ?><a href="<?= $h(fcc_help_url($article['id']).'#fh-screens') ?>"><?= $h($t('screenshot')) ?></a></nav>
 <article class="fh-article" lang="<?= $h(fcc_language_tag($article['content_locale'])) ?>">
  <?php foreach($article['sections'] as $section): ?>
  <section class="fh-section" id="<?= $h($section['id']) ?>">
   <div class="fh-section-heading"><h2><?= $h($section['title']) ?></h2><button class="fh-copy-section" type="button" data-fcc-help-copy="<?= $h(fcc_help_url($article['id'],$section['id'])) ?>" aria-label="<?= $h($t('share').' : '.$section['title']) ?>"><?= $h($t('share')) ?></button></div>
   <?php foreach($section['paragraphs']??[] as $paragraph): ?><p><?= $h($paragraph) ?></p><?php endforeach ?>
   <?php if(!empty($section['steps'])): ?><ol class="fh-steps"><?php foreach($section['steps'] as $step): ?><li><?= $h($step) ?></li><?php endforeach ?></ol><?php endif ?>
   <?php if($article['id']==='presentation'&&$section['id']==='downloads')require THEME_PATH.'views/partner/help-downloads.php'; ?>
   <?php foreach($section['questions']??[] as $question): ?><details class="fh-faq"><summary><?= $h($question['question']) ?></summary><p><?= $h($question['answer']) ?></p></details><?php endforeach ?>
   <button class="fp-button fp-button-secondary fh-coach-button" type="button" data-fcc-help-coach="<?= $h($article['id']) ?>" data-fcc-help-section="<?= $h($section['id']) ?>" data-fcc-help-prompt="<?= $h(strtr($t('coach_prompt'),['{title}'=>$article['title'],'{section}'=>$section['title']])) ?>"><?= $h($t('coach')) ?></button>
  </section>
  <?php endforeach ?>
  <section id="fh-screens" class="fh-section fh-screens"><h2><?= $h($t('screenshot')) ?></h2><p class="fh-small"><?= $h($t('screenshot_note')) ?></p>
   <?php foreach(fcc_help_screens($article['id'],$locale) as $screen): ?><details class="fh-screen" <?= $screen['device']==='mobile'?'open':'' ?>><summary><?= $h($t($screen['device'])) ?></summary><figure><div class="fh-screen-frame fh-screen-<?= $h($screen['device']) ?>"><img src="<?= $h($screen['url']) ?>" width="<?= (int)$screen['width'] ?>" height="<?= (int)$screen['height'] ?>" alt="<?= $h($article['title'].' : '.$t($screen['device'])) ?>" loading="lazy">
   <?php foreach($screen['points'] as $i=>$point): ?><span aria-hidden="true" class="fh-marker" style="left:<?= (float)$point['x'] ?>%;top:<?= (float)$point['y'] ?>%"> <?= $i+1 ?></span><?php endforeach ?></div><figcaption><ol><?php foreach($screen['points'] as $point): ?><li><?= $h($point['label']) ?></li><?php endforeach ?></ol></figcaption></figure></details><?php endforeach ?>
  </section>
  <section class="fh-complete"><div class="fh-actions">
   <form method="post"><?= $form(isset($state['read'][$article['id']])?'unread':'read',$article['id']) ?><button class="fp-button" aria-pressed="<?= isset($state['read'][$article['id']])?'true':'false' ?>"><?= $h($t(isset($state['read'][$article['id']])?'unread':'mark_read')) ?></button></form>
   <a class="fp-button fp-button-secondary" href="<?= url($article['route']) ?>"><?= $h($t('open_tool')) ?></a><button class="fp-text-button" type="button" data-fcc-help-print><?= $h($t('print')) ?></button>
  </div><?php if($article['pro']): ?><p class="fh-small"><?= $h($t('pro_tool')) ?></p><?php endif ?>
  <fieldset><legend><?= $h($t('helpful')) ?></legend><div class="fh-actions"><?php foreach(['helpful_yes'=>'yes','helpful_no'=>'no'] as $action=>$label): ?><form method="post"><?= $form($action,$article['id']) ?><button class="fp-button fp-button-secondary" aria-pressed="<?= array_key_exists($article['id'],$state['helpful'])&&$state['helpful'][$article['id']]===($label==='yes')?'true':'false' ?>"><?= $h($t($label)) ?></button></form><?php endforeach ?></div></fieldset></section>
  <?php $ids=array_keys(fcc_help_manifest());$position=array_search($article['id'],$ids,true); ?><nav class="fh-article-navigation"><?php if($position>0): ?><a href="<?= fcc_help_url($ids[$position-1]) ?>"><?= $h($t('previous')) ?></a><?php endif ?><?php if($position<count($ids)-1): ?><a href="<?= fcc_help_url($ids[$position+1]) ?>"><?= $h($t('next')) ?></a><?php endif ?></nav>
 </article></div>
 <?php endif ?>
 <?php if($data->review): ?><section class="fh-review"><h2><?= $h($t('review_title')) ?></h2><div class="fh-table-wrap"><table><thead><tr><th><?= $h($t('title')) ?></th><?php foreach(fcc_help_meta()['languages'] as $code): ?><th><?= $h($code) ?></th><?php endforeach ?></tr></thead><tbody><?php foreach(fcc_help_manifest() as $id=>$meta): ?><tr><th><?= $h($id) ?></th><?php foreach(fcc_help_meta()['languages'] as $code): $a=fcc_help_article($id,$code); ?><td><?= $h($t($a&&$a['content_locale']===$code?'yes':'no')) ?></td><?php endforeach ?></tr><?php endforeach ?></tbody></table></div></section><?php endif ?>
</div>
<script defer src="<?= ASSETS_FULL_URL ?>js/fcc-help-center.js?v=<?= filemtime(ASSETS_PATH.'js/fcc-help-center.js') ?>"></script>
