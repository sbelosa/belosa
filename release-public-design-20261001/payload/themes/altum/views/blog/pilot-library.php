<?php defined('ALTUMCODE') || die();
fcc_library_pilot_assets();
$lp_share_context=\Altum\Router::$controller_key==='partner';
$lp_uid=(int)(\Altum\Authentication::$user_id??0);
$lp_cards=$lp_uid?array_values(array_filter(fcc_partner_cards($lp_uid),static fn($c)=>(int)$c['is_enabled']===1)):[];
$lp_incoming=query_clean((string)($_GET['ref']??$_COOKIE['referral']??$_COOKIE['referred_by']??''));
$lp_own_ref=$lp_cards[0]['url']??null;
$lp_personal_share=$lp_uid && !empty($lp_own_ref);
$lp_ref=$lp_share_context?$lp_own_ref:$lp_incoming;
$lp_posts=fcc_library_pilot_posts();
if(!$lp_share_context) fcc_public_listing_schema($lp_posts, null, true);
$lp_all_url=fcc_library_pilot_url('blog',$lp_ref,['view'=>'articles']);
foreach((array)($data->alternate_urls??[]) as $lang=>$href) \Altum\Event::add_content('<link rel="alternate" hreflang="'.e(fcc_language_tag($lang)).'" href="'.e($href).'">','head');
?>
<div id="fl-library" class="fl-library fl-discovery<?= $lp_share_context?' fp-share-page':'' ?>" data-fl-library data-locale="<?= e(fcc_library_pilot_locale()) ?>">
 <?php if($lp_share_context): require THEME_PATH.'views/partner/share-navigation.php'; else: ?>
 <nav class="fcw-library-tabs" aria-label="<?= e(fcc_public_copy('library')) ?>"><a href="<?= e(fcc_public_url('blog')) ?>" aria-current="page"><?= e(fcc_public_copy('products')) ?></a><a href="<?= e($lp_all_url) ?>"><?= e(fcc_public_copy('articles')) ?></a><a href="<?= e(fcc_public_url('pages')) ?>"><?= e(fcc_public_copy('guides')) ?></a></nav>
 <?php endif ?>
 <header class="fl-page-heading"><span class="fl-kicker"><?= e(fcc_lp_t('eyebrow')) ?></span><h1><?= e(fcc_lp_t($lp_share_context?'share':'title')) ?></h1><p><?= e(fcc_lp_t($lp_share_context?'share_intro':'intro')) ?></p></header>
 <div class="fl-search"><label class="sr-only" for="fl-search"><?= e(fcc_lp_t('search')) ?></label><input id="fl-search" type="search" placeholder="<?= e(fcc_lp_t('search')) ?>" value="<?= e((string)($_GET['q']??'')) ?>" autocomplete="off"><span aria-hidden="true">⌕</span></div>
 <div class="fl-filters" role="group" aria-label="<?= e(fcc_lp_t('topics')) ?>"><?php foreach(['all','drinks','supplement','weight','care','personal','bee'] as $topic): ?><button type="button" class="fl-filter" data-fl-filter="<?= $topic ?>" aria-pressed="<?= $topic==='all'?'true':'false' ?>"><?= e(fcc_lp_t($topic)) ?></button><?php endforeach ?></div>
 <div class="fl-grid">
 <?php foreach($lp_posts as $post):
  $item=fcc_library_pilot_product($post->url);$photo=fcc_product_editorial_image($post);$image=$photo['url'];
  $read_url=fcc_library_pilot_url('blog/'.$post->url,$lp_ref,$lp_share_context?['from'=>'share']:[]);
  $share_url=fcc_library_pilot_url('blog/'.$post->url,$lp_uid?$lp_own_ref:$lp_ref);
  $message=fcc_library_pilot_message($item['title'],$share_url);
 ?>
 <article class="fl-product" data-fl-product="<?= (int)$post->blog_post_id ?>" data-fl-topic="<?= e($item['topic']) ?>" data-fl-search="<?= e($item['title'].' '.fcc_lp_t($item['topic']).' '.$post->sku.' '.($post->search_aliases??'').' '.$item['format']) ?>">
  <a class="fl-product-image fl-topic-<?= e($item['topic']) ?>" href="<?= e($read_url) ?>" aria-label="<?= e($item['title']) ?>"><img src="<?= e($image) ?>" alt="<?= e($item['title']) ?>" width="<?= (int)$photo['width'] ?>" height="<?= (int)$photo['height'] ?>" style="--fl-photo-scale:<?= (float)$item['image_scale'] ?>" loading="lazy" decoding="async"></a>
  <div class="fl-product-copy"><span class="fl-kicker"><?= e(fcc_lp_t($item['topic'])) ?></span><h2><a href="<?= e($read_url) ?>"><?= e($item['title']) ?></a></h2><p><?= e($item['teaser']) ?></p>
   <?php if($lp_share_context && $lp_own_ref): ?><a class="fl-text-button" href="<?= e('https://wa.me/?text='.rawurlencode($message)) ?>" target="_blank" rel="noopener noreferrer" data-fl-compose data-fl-id="<?= (int)$post->blog_post_id ?>" data-fl-title="<?= e($item['title']) ?>" data-fl-image="<?= e($image) ?>" data-fl-url="<?= e($share_url) ?>" data-fl-message="<?= e($message) ?>"><?= e(fcc_lp_t('prepare')) ?> <?= fcc_partner_icon('arrow') ?></a>
   <?php else: ?><a class="fl-text-button" href="<?= e($read_url) ?>"><?= e(fcc_lp_t('read')) ?> <?= fcc_partner_icon('arrow') ?></a><?php endif ?>
  </div>
 </article>
 <?php endforeach ?>
 </div>
 <div class="fl-empty" hidden role="status"><p><?= e(fcc_lp_t('empty')) ?></p><button type="button" class="fl-secondary" data-fl-clear><?= e(fcc_lp_t('reset')) ?></button></div>
 <div class="fl-library-footer"><a class="fl-text-button" href="<?= e($lp_all_url) ?>"><?= e(fcc_lp_t('all_content')) ?> <?= fcc_partner_icon('arrow') ?></a></div>
 <?php if($lp_share_context): ?>
 <div class="fl-shortcuts">
 <?php if($lp_cards): $card=$lp_cards[0]; ?><details><summary><?= e(fcc_lp_t('my_card')) ?></summary><p><?= e($card['title']) ?></p><a class="fl-text-button" href="<?= e($card['public_url']) ?>"><?= e(fcc_lp_t('my_card')) ?> <?= fcc_partner_icon('arrow') ?></a><button type="button" class="fl-secondary" data-share-url="<?= e($card['public_url']) ?>" data-share-title="<?= e($card['title']) ?>"><?= e(fcc_lp_t('share_short')) ?></button></details>
 <?php else: ?><div><p><?= e(fcc_lp_t('no_card')) ?></p><a class="fl-text-button" href="<?= url('partner/cards') ?>"><?= e(fcc_lp_t('choose_card')) ?></a></div><?php endif ?>
 </div>
 <?php endif ?>
 <?php require THEME_PATH.'views/blog/pilot-composer.php'; ?>
</div>
