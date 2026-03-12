<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-26: EN/HR widget titles */
$fcc_blog_categories_title = \Altum\Language::$code == 'hr' ? 'Kategorije' : 'Categories';
$fcc_blog_popular_title = \Altum\Language::$code == 'hr' ? 'Popularni postovi' : 'Popular posts';
$fcc_blog_hero_badge = \Altum\Language::$code == 'hr' ? 'Digitalni vodiči' : 'Digital guides';
$fcc_blog_hero_title = \Altum\Language::$code == 'hr' ? 'Blog koji vodi kroz Forever Card Club sustav' : 'A blog that guides users through the Forever Card Club system';
$fcc_blog_hero_subtitle = \Altum\Language::$code == 'hr' ? 'Jasni koraci, konkretni savjeti i aktualne objave na jednom mjestu.' : 'Clear steps, practical advice, and latest updates in one place.';
$fcc_blog_cta_latest = \Altum\Language::$code == 'hr' ? 'Najnoviji članci' : 'Latest articles';
$fcc_blog_cta_popular = \Altum\Language::$code == 'hr' ? 'Popularni postovi' : 'Popular posts';
$fcc_blog_quick_topics = \Altum\Language::$code == 'hr' ? 'Brze teme' : 'Quick topics';
$fcc_blog_featured_title = \Altum\Language::$code == 'hr' ? 'Izdvojeno' : 'Featured';
$fcc_blog_latest_title = \Altum\Language::$code == 'hr' ? 'Sve objave' : 'All posts';
$fcc_blog_read_more = \Altum\Language::$code == 'hr' ? 'Pročitaj više' : 'Read more';
$fcc_blog_home_url = url('blog');
/* /Custom code: FC-2026-02-26 */
?>

<!-- Custom code: FC-2026-02-26: FCC premium blog index layout -->
<div class="fcc-blog-page-bg">
<div class="container <?= settings()->content->blog_columns == 1 ? 'col-lg-8' : null ?> fcc-blog-wrap">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <?php if(!empty($_GET['search'])): ?>
                    <li><a href="<?= url('blog') ?>"><?= l('blog.breadcrumb') ?></a></li>
                <?php else: ?>
                    <li class="active" aria-current="page"><?= l('blog.breadcrumb') ?></li>
                <?php endif ?>
            </ol>
        </nav>
    <?php endif ?>

    <div class="d-flex align-items-center fcc-blog-list-header">
        <?php if(!empty($_GET['search'])): ?>
            <h1 class="h3 m-0 fcc-blog-index-title"><?= sprintf(l('blog.header_search'), input_clean($_GET['search'])) ?></h1>
        <?php else: ?>
            <h1 class="h3 m-0 fcc-blog-index-title"><?= l('blog.header') ?></h1>

            <div class="ml-2">
                <span data-toggle="tooltip" title="<?= l('blog.subheader') ?>">
                    <i class="fas fa-fw fa-info-circle text-muted"></i>
                </span>

                <a href="<?= SITE_URL . 'blog/feed' ?>" target="_blank" data-toggle="tooltip" title="<?= l('blog.rss') ?>">
                    <i class="fas fa-fw fa-rss text-muted"></i>
                </a>
            </div>
        <?php endif ?>
    </div>

    <div class="row mt-4 fcc-blog-grid">
        <div class="<?= settings()->content->blog_columns == 1 ? 'col-12 mb-5' : 'col-12 col-lg-8 mb-lg-0' ?> fcc-blog-main-col">
            <?php if (!empty($data->blog_posts)): ?>
                <?php if(empty($_GET['search'])): ?>
                    <?php
                    /* Custom code: FC-2026-02-26: blog homepage structured layout */
                    $fcc_blog_posts = [];
                    foreach($data->blog_posts as $fcc_blog_post_item) {
                        $fcc_blog_posts[] = $fcc_blog_post_item;
                    }

                    $fcc_primary_post = $fcc_blog_posts[0] ?? null;
                    $fcc_featured_posts = array_slice($fcc_blog_posts, 1, 2);

                    $fcc_featured_ids = [];
                    if($fcc_primary_post && isset($fcc_primary_post->blog_post_id)) {
                        $fcc_featured_ids[] = $fcc_primary_post->blog_post_id;
                    }

                    foreach($fcc_featured_posts as $fcc_featured_post_item) {
                        if(isset($fcc_featured_post_item->blog_post_id)) {
                            $fcc_featured_ids[] = $fcc_featured_post_item->blog_post_id;
                        }
                    }

                    $fcc_latest_posts = array_values(array_filter($fcc_blog_posts, function($fcc_post_item) use ($fcc_featured_ids) {
                        return !in_array($fcc_post_item->blog_post_id, $fcc_featured_ids);
                    }));
                    /* /Custom code: FC-2026-02-26 */
                    ?>

                    <section class="card mb-4 fcc-glass-card fcc-blog-home-hero">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-12 col-lg-7">
                                    <span class="fcc-hero-badge"><?= $fcc_blog_hero_badge ?></span>
                                    <h2 class="fcc-home-hero-title mt-3 mb-2"><?= $fcc_blog_hero_title ?></h2>
                                    <p class="fcc-home-hero-subtitle mb-4"><?= $fcc_blog_hero_subtitle ?></p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="<?= $fcc_blog_home_url . '#fcc-latest-posts' ?>" class="btn fcc-home-hero-btn-primary"><?= $fcc_blog_cta_latest ?></a>
                                        <a href="<?= $fcc_blog_home_url . '#fcc-popular-widget' ?>" class="btn fcc-home-hero-btn-secondary"><?= $fcc_blog_cta_popular ?></a>
                                    </div>
                                </div>
                                <?php if($fcc_primary_post): ?>
                                    <div class="col-12 col-lg-5 mt-4 mt-lg-0">
                                        <a href="<?= SITE_URL . ($fcc_primary_post->language ? \Altum\Language::$active_languages[$fcc_primary_post->language] . '/' : null) . 'blog/' . $fcc_primary_post->url ?>" class="text-decoration-none">
                                            <article class="fcc-home-hero-featured">
                                                <?php if($fcc_primary_post->image): ?>
                                                    <img src="<?= \Altum\Uploads::get_full_url('blog') . $fcc_primary_post->image ?>" class="fcc-home-hero-featured-img" alt="<?= $fcc_primary_post->image_description ?>" loading="eager" decoding="async" />
                                                <?php endif ?>
                                                <h3 class="fcc-home-hero-featured-title"><?= $fcc_primary_post->title ?></h3>
                                            </article>
                                        </a>
                                    </div>
                                <?php endif ?>
                            </div>
                        </div>
                    </section>

                    <?php if(settings()->content->blog_categories_widget_is_enabled && count($data->blog_posts_main_categories)): ?>
                        <section class="card mb-4 fcc-glass-card fcc-topics-card">
                            <div class="card-body">
                                <h3 class="fcc-section-title mb-3"><?= $fcc_blog_quick_topics ?></h3>
                                <div class="fcc-topic-chips">
                                    <?php foreach(array_slice($data->blog_posts_main_categories, 0, 8) as $blog_post_main_category): ?>
                                        <a href="<?= SITE_URL . ($blog_post_main_category->language ? \Altum\Language::$active_languages[$blog_post_main_category->language] . '/' : null) . 'blog/category/' . $blog_post_main_category->url ?>" class="fcc-topic-chip">
                                            <?= $blog_post_main_category->title ?>
                                        </a>
                                    <?php endforeach ?>
                                </div>
                            </div>
                        </section>
                    <?php endif ?>

                    <?php if(!empty($fcc_featured_posts)): ?>
                        <section class="mb-4">
                            <h3 class="fcc-section-title mb-3"><?= $fcc_blog_featured_title ?></h3>
                            <div class="row fcc-featured-grid">
                                <?php foreach($fcc_featured_posts as $blog_post): ?>
                                    <div class="col-12 col-md-6 mb-3">
                                        <article class="card h-100 fcc-glass-card fcc-post-card fcc-post-card-compact">
                                            <div class="card-body fcc-post-card-body">
                                                <?php if($blog_post->image): ?>
                                                    <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>">
                                                        <img src="<?= \Altum\Uploads::get_full_url('blog') . $blog_post->image ?>" class="blog-post-image img-fluid w-100 rounded mb-3 fcc-post-thumb" alt="<?= $blog_post->image_description ?>" loading="lazy" decoding="async" />
                                                    </a>
                                                <?php endif ?>

                                                <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="text-decoration-none">
                                                    <h4 class="h5 mb-1 fcc-post-title"><?= $blog_post->title ?></h4>
                                                </a>

                                                <p class="small text-muted fcc-post-meta mb-2">
                                                    <span><?= sprintf(l('global.datetime_tooltip'), \Altum\Date::get($blog_post->datetime, 2)) ?></span>
                                                </p>

                                                <p class="m-0 fcc-post-desc"><?= $blog_post->description ?></p>
                                            </div>
                                        </article>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </section>
                    <?php endif ?>

                    <section id="fcc-latest-posts" class="mb-2">
                        <h3 class="fcc-section-title mb-3"><?= $fcc_blog_latest_title ?></h3>
                        <div class="row fcc-latest-grid">
                            <?php foreach($fcc_latest_posts as $blog_post): ?>
                                <div class="col-12 col-md-6 mb-3">
                                    <article class="card h-100 fcc-glass-card fcc-post-card fcc-post-card-compact">
                                        <div class="card-body fcc-post-card-body">
                                            <?php if($blog_post->image): ?>
                                                <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>">
                                                    <img src="<?= \Altum\Uploads::get_full_url('blog') . $blog_post->image ?>" class="blog-post-image img-fluid w-100 rounded mb-3 fcc-post-thumb" alt="<?= $blog_post->image_description ?>" loading="lazy" decoding="async" />
                                                </a>
                                            <?php endif ?>

                                            <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="text-decoration-none">
                                                <h4 class="h5 mb-1 fcc-post-title"><?= $blog_post->title ?></h4>
                                            </a>

                                            <p class="small text-muted fcc-post-meta">
                                                <span><?= sprintf(l('global.datetime_tooltip'), \Altum\Date::get($blog_post->datetime, 2)) ?></span>

                                                <?php if($blog_post->blog_posts_category_id && isset($data->blog_posts_categories[$blog_post->blog_posts_category_id])): ?>
                                                    • <a href="<?= SITE_URL . ($data->blog_posts_categories[$blog_post->blog_posts_category_id]->language ? \Altum\Language::$active_languages[$data->blog_posts_categories[$blog_post->blog_posts_category_id]->language] . '/' : null) . 'blog/category/' . $data->blog_posts_categories[$blog_post->blog_posts_category_id]->url ?>" class="text-muted"><?= $data->blog_posts_categories[$blog_post->blog_posts_category_id]->title ?></a>
                                                <?php endif ?>
                                            </p>

                                            <p class="m-0 fcc-post-desc"><?= $blog_post->description ?></p>
                                            <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="fcc-read-more-link mt-3 d-inline-flex align-items-center">
                                                <?= $fcc_blog_read_more ?>
                                                <i class="fas fa-fw fa-arrow-right ml-1"></i>
                                            </a>
                                        </div>
                                    </article>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </section>

                <?php else: ?>
                    <?php foreach($data->blog_posts as $blog_post): ?>
                        <div class="card mb-4 fcc-glass-card fcc-post-card">
                            <div class="card-body fcc-post-card-body">
                                <?php if($blog_post->image): ?>
                                    <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>">
                                        <img src="<?= \Altum\Uploads::get_full_url('blog') . $blog_post->image ?>" class="blog-post-image img-fluid w-100 rounded mb-3 fcc-post-thumb" alt="<?= $blog_post->image_description ?>" loading="lazy" decoding="async" />
                                    </a>
                                <?php endif ?>

                                <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="text-decoration-none">
                                    <h2 class="h4 mb-1 fcc-post-title"><?= $blog_post->title ?></h2>
                                </a>

                                <p class="small text-muted fcc-post-meta">
                                    <span data-toggle="tooltip" title="<?= sprintf(l('global.last_datetime_tooltip'), \Altum\Date::get($blog_post->last_datetime, 2)) ?>"><?= sprintf(l('global.datetime_tooltip'), \Altum\Date::get($blog_post->datetime, 2)) ?></span>

                                    <?php if($blog_post->blog_posts_category_id && isset($data->blog_posts_categories[$blog_post->blog_posts_category_id])): ?>
                                        • <a href="<?= SITE_URL . ($data->blog_posts_categories[$blog_post->blog_posts_category_id]->language ? \Altum\Language::$active_languages[$data->blog_posts_categories[$blog_post->blog_posts_category_id]->language] . '/' : null) . 'blog/category/' . $data->blog_posts_categories[$blog_post->blog_posts_category_id]->url ?>" class="text-muted"><?= $data->blog_posts_categories[$blog_post->blog_posts_category_id]->title ?></a>
                                    <?php endif ?>

                                    <?php if(settings()->content->blog_views_is_enabled): ?>
                                        <span> • <?= sprintf(l('blog.total_views'), nr($blog_post->total_views)) ?></span>
                                    <?php endif ?>
                                </p>

                                <p class="m-0 fcc-post-desc"><?= $blog_post->description ?></p>
                            </div>
                        </div>
                    <?php endforeach ?>
                <?php endif ?>

                <div class="mt-3 fcc-pagination-wrap"><?= $data->pagination ?></div>
            <?php else: ?>
                <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
                    'filters_get' => $data->filters->get ?? [],
                    'name' => 'blog',
                    'has_secondary_text' => true,
                ]); ?>
            <?php endif ?>
        </div>

        <?php if(settings()->content->blog_popular_widget_is_enabled || settings()->content->blog_categories_widget_is_enabled || settings()->content->blog_search_widget_is_enabled): ?>
            <div class="<?= settings()->content->blog_columns == 1 ? 'col-12' : 'col-12 col-lg-4' ?> fcc-blog-sidebar-col">
                <div class="fcc-sidebar-sticky">
                <?php if(settings()->content->blog_search_widget_is_enabled): ?>
                    <div id="fcc-popular-widget" class="card mb-4 fcc-glass-card fcc-widget-card">
                        <div class="card-body">
                            <form action="<?= url('blog') ?>" method="get" role="form">
                                <input type="hidden" name="search_by" value="title" />

                                <div class="input-group">
                                    <input type="search" name="search" class="form-control" value="<?= !empty($_GET['search']) ? input_clean($_GET['search']) : null ?>" placeholder="<?= l('global.search') ?>" aria-label="<?= l('global.search') ?>" />

                                    <div class="input-group-append">
                                        <button class="btn btn-outline-gray-300 text-dark" type="submit" data-toggle="tooltip" title="<?= l('global.submit') ?>"><i class="fas fa-fw fa-search"></i></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif ?>

                <!-- Custom code -->
                <?php if(settings()->content->blog_categories_widget_is_enabled && count($data->blog_posts_main_categories)): ?>
                     <div class="card mb-4 fcc-glass-card fcc-widget-card">
                    <div class="card-body">
                        <h3 class="h6 mb-3 orange fcc-widget-title"><?= $fcc_blog_categories_title ?></h3>

                        <ul class="list-style-none m-0 categories-menu fcc-widget-list">
                            <?php foreach($data->blog_posts_main_categories as $blog_post_main_category): ?>                                
                                <li class="mb-2 fcc-widget-link-item">
                                    <a href="<?= SITE_URL . ($blog_post_main_category->language ? \Altum\Language::$active_languages[$blog_post_main_category->language] . '/' : null) . 'blog/category/' . $blog_post_main_category->url ?>"><?= $blog_post_main_category->title ?></a>
                                </li>         
                                <?php foreach($data->blog_posts_parents as $blog_post_parent_category): ?>                                        
                                    <?php if ($blog_post_parent_category->blog_posts_parent_id == $blog_post_main_category->blog_posts_category_id): ?> 
                                        <ul class="m-0">
                                            <li class="mb-2 fcc-widget-link-item">
                                                <a href="<?= SITE_URL . ($blog_post_parent_category->language ? \Altum\Language::$active_languages[$blog_post_parent_category->language] . '/' : null) . 'blog/category/' . $blog_post_parent_category->url ?>"><?= $blog_post_parent_category->title ?></a>
                                            </li>
                                            <?php foreach($data->blog_posts_subcategories as $blog_post_subcategory): ?>                                        
                                                <?php if ($blog_post_subcategory->blog_posts_parent_id == $blog_post_parent_category->blog_posts_category_id): ?> 
                                                    <ul class="m-0">
                                                        <li class="mb-2 fcc-widget-link-item">
                                                            <a href="<?= SITE_URL . ($blog_post_subcategory->language ? \Altum\Language::$active_languages[$blog_post_subcategory->language] . '/' : null) . 'blog/category/' . $blog_post_subcategory->url ?>"><?= $blog_post_subcategory->title ?></a>
                                                        </li>
                                                    </ul>
                                                <?php endif; ?>  
                                            <?php endforeach ?>
                                        </ul>                                       
                                    <?php endif; ?>  
                                <?php endforeach ?>                                                                                                                                        
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
                <?php endif ?>
                <!-- /Custom code -->

                <?php if(settings()->content->blog_popular_widget_is_enabled && count($data->blog_posts_popular)): ?>
                    <div class="card mb-4 fcc-glass-card fcc-widget-card">
                        <div class="card-body">
                            <h3 class="h6 mb-3 fcc-widget-title"><?= $fcc_blog_popular_title ?></h3>

                            <ul class="list-style-none m-0 fcc-widget-list">
                                <?php foreach($data->blog_posts_popular as $blog_post): ?>
                                    <li class="mb-3 d-flex align-items-center fcc-popular-item">
                                        <?php if(!empty($blog_post->image)): ?>
                                            <img src="<?= \Altum\Uploads::get_full_url('blog') . $blog_post->image ?>" alt="<?= $blog_post->title ?>" class="fcc-popular-thumb mr-3" loading="lazy" decoding="async" />
                                        <?php else: ?>
                                            <span class="fcc-popular-badge mr-3"></span>
                                        <?php endif ?>

                                        <div class="fcc-popular-item-content">
                                            <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="font-size-small fcc-popular-link"><?= $blog_post->title ?></a>
                                            <div class="small fcc-popular-meta">
                                                <?php if($blog_post->blog_posts_category_id && isset($data->blog_posts_categories[$blog_post->blog_posts_category_id])): ?>
                                                    <a href="<?= SITE_URL . ($data->blog_posts_categories[$blog_post->blog_posts_category_id]->language ? \Altum\Language::$active_languages[$data->blog_posts_categories[$blog_post->blog_posts_category_id]->language] . '/' : null) . 'blog/category/' . $data->blog_posts_categories[$blog_post->blog_posts_category_id]->url ?>" class="text-muted"><?= $data->blog_posts_categories[$blog_post->blog_posts_category_id]->title ?></a>

                                                    <?php if(settings()->content->blog_views_is_enabled): ?>
                                                        <span class="text-muted"> • </span>
                                                    <?php endif ?>
                                                <?php endif ?>

                                                <?php if(settings()->content->blog_views_is_enabled): ?>
                                                    <span class="text-muted"><?= sprintf(l('blog.total_views'), nr($blog_post->total_views)) ?></span>
                                                <?php endif ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </div>
                <?php endif ?>
            </div>
                </div>
        <?php endif ?>
    </div>
</div>
</div>
<!-- /Custom code: FC-2026-02-26 -->

<?php ob_start() ?>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "<?= l('index.title') ?>",
                    "item": "<?= url() ?>"
                },
                {
                    "@type": "ListItem",
                    "position": 2,
                    "name": "<?= l('blog.title') ?>",
                    "item": "<?= url('blog') ?>"
                }
            ]
        }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
