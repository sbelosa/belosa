<?php defined('ALTUMCODE') || die();
fcc_library_pilot_assets();
$listing_category = $data->blog_posts_category ?? null;
$listing_search = trim((string)($_GET['search'] ?? ''));
$listing_categories = $data->blog_posts_direct_children ?? $data->blog_posts_main_categories ?? [];
fcc_public_listing_schema((array)$data->blog_posts, $listing_category);
foreach((array)($data->alternate_urls ?? []) as $lang=>$href) \Altum\Event::add_content('<link rel="alternate" hreflang="'.e(fcc_language_tag($lang)).'" href="'.e($href).'">','head');
?>
<div id="fl-library" class="fl-library fcw-article-list" data-fcc-public-list>
 <nav class="fcw-library-tabs" aria-label="<?= e(fcc_public_copy('library')) ?>"><a href="<?= e(fcc_public_url('blog')) ?>"><?= e(fcc_public_copy('products')) ?></a><a href="<?= e(fcc_public_url('blog',['view'=>'articles'])) ?>"<?= !$listing_category?' aria-current="page"':'' ?>><?= e(fcc_public_copy('articles')) ?></a><a href="<?= e(fcc_public_url('pages')) ?>"><?= e(fcc_public_copy('guides')) ?></a></nav>
 <header class="fl-page-heading"><span class="fl-kicker">Forever Card Club</span><h1><?= $listing_category ? e($listing_category->title) : e(fcc_public_copy('articles')) ?></h1><?php if(!empty($listing_category->description)): ?><p><?= e($listing_category->description) ?></p><?php else: ?><p><?= e(fcc_public_copy('articles_text')) ?></p><?php endif ?></header>
 <form class="fcw-search-form" method="get" action="<?= e(url('blog')) ?>"><input type="hidden" name="view" value="articles"><?php if(fcc_public_ref()): ?><input type="hidden" name="ref" value="<?= e(fcc_public_ref()) ?>"><?php endif ?><label class="sr-only" for="fcc-article-search"><?= e(fcc_public_copy('search')) ?></label><input id="fcc-article-search" name="search" type="search" maxlength="256" value="<?= e($listing_search) ?>" placeholder="<?= e(fcc_public_copy('search')) ?>"><button class="fl-primary" type="submit"><?= e(fcc_public_copy('search_button')) ?></button></form>
 <?php if($listing_categories): ?><nav class="fcw-category-links" aria-label="<?= e(fcc_public_copy('categories')) ?>"><?php foreach($listing_categories as $category): ?><a href="<?= e(fcc_public_content_url($category,'blog/category')) ?>"><?= e($category->title) ?></a><?php endforeach ?></nav><?php endif ?>
 <div class="fl-grid"><?php foreach($data->blog_posts as $article): $article_url=fcc_public_content_url($article,'blog'); ?>
  <article class="fl-product"><a class="fl-product-image" href="<?= e($article_url) ?>" tabindex="-1" aria-hidden="true"><?php if($article->image): ?><img src="<?= e(fcc_fast_image_url(\Altum\Uploads::get_full_url('blog').$article->image)) ?>" alt="" loading="lazy" width="480" height="360"><?php else: ?><span class="fcw-article-placeholder">FCC</span><?php endif ?></a><div class="fl-product-copy"><span class="fl-kicker"><?= e(\Altum\Date::get($article->datetime,2)) ?></span><h2><a href="<?= e($article_url) ?>"><?= e($article->title) ?></a></h2><p><?= e($article->description) ?></p><a class="fl-text-button" href="<?= e($article_url) ?>"><?= e(fcc_public_copy('read')) ?> <span aria-hidden="true">→</span></a></div></article>
 <?php endforeach ?></div>
 <?php if(!$data->blog_posts): ?><p class="fcw-empty" role="status"><?= e(fcc_public_copy('empty')) ?></p><?php endif ?>
 <div class="fcw-pagination"><?= $data->pagination ?></div>
</div>
