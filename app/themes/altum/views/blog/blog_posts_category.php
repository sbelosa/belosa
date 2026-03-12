<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-26: EN/HR widget titles and category hero labels */
$fcc_blog_categories_title = \Altum\Language::$code == 'hr' ? 'Kategorije' : 'Categories';
$fcc_blog_popular_title = \Altum\Language::$code == 'hr' ? 'Popularni postovi' : 'Popular posts';
$fcc_category_hero_badge = \Altum\Language::$code == 'hr' ? 'Blog kategorija' : 'Blog category';
$fcc_category_back_to_blog = \Altum\Language::$code == 'hr' ? 'Povratak na blog' : 'Back to blog';
$fcc_category_all_posts = \Altum\Language::$code == 'hr' ? 'Objave u kategoriji' : 'Posts in this category';
$fcc_blog_home_url = url('blog');
/* /Custom code: FC-2026-02-26 */
?>

<!-- Custom code: FC-2026-02-26: FCC premium blog category layout -->
<div class="fcc-blog-page-bg">
<div class="container <?= settings()->content->blog_columns == 1 ? 'col-lg-8' : null ?> fcc-blog-wrap">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('blog') ?>"><?= l('blog.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= $data->blog_posts_category->title ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="row mt-4 fcc-blog-grid">
        <div class="<?= settings()->content->blog_columns == 1 ? 'col-12 mb-5' : 'col-12 col-lg-8 mb-lg-0' ?> fcc-blog-main-col">
            <section class="card mb-4 fcc-glass-card fcc-blog-home-hero fcc-category-hero">
                <div class="card-body">
                    <span class="fcc-hero-badge"><?= $fcc_category_hero_badge ?></span>
                    <h1 class="fcc-home-hero-title mt-3 mb-2"><?= $data->blog_posts_category->title ?></h1>
                    <?php if(!empty($data->blog_posts_category->description)): ?>
                        <p class="fcc-home-hero-subtitle mb-4"><?= $data->blog_posts_category->description ?></p>
                    <?php endif ?>
                    <a href="<?= $fcc_blog_home_url ?>" class="btn fcc-home-hero-btn-secondary">
                        <?= $fcc_category_back_to_blog ?>
                    </a>
                </div>
            </section>

            <?php if (!empty($data->blog_posts)): ?>
                <section id="fcc-category-posts" class="mb-2">
                    <h3 class="fcc-section-title mb-3"><?= $fcc_category_all_posts ?></h3>
                    <div class="row fcc-latest-grid">
                        <?php foreach($data->blog_posts as $blog_post): ?>
                            <div class="col-12 col-md-6 mb-3">
                                <article class="card h-100 fcc-glass-card fcc-post-card fcc-post-card-compact">
                                    <div class="card-body fcc-post-card-body">
                                        <?php if($blog_post->image): ?>
                                            <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>">
                                                <img src="<?= \Altum\Uploads::get_full_url('blog') . $blog_post->image ?>" class="blog-post-image img-fluid w-100 rounded mb-3 fcc-post-thumb" alt="<?= $blog_post->image_description ?>" loading="lazy" decoding="async" />
                                            </a>
                                        <?php endif ?>

                                        <a href="<?= SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url ?>" class="text-decoration-none">
                                            <h2 class="h5 mb-1 fcc-post-title"><?= $blog_post->title ?></h2>
                                        </a>

                                        <p class="small text-muted fcc-post-meta">
                                            <span data-toggle="tooltip" title="<?= sprintf(l('global.last_datetime_tooltip'), \Altum\Date::get($blog_post->last_datetime, 2)) ?>">
                                                <?= sprintf(l('global.datetime_tooltip'), \Altum\Date::get($blog_post->datetime, 2)) ?>
                                            </span>

                                            <?php if(settings()->content->blog_views_is_enabled): ?>
                                                <span> • <?= sprintf(l('blog.total_views'), nr($blog_post->total_views)) ?></span>
                                            <?php endif ?>
                                        </p>

                                        <p class="m-0 fcc-post-desc"><?= $blog_post->description ?></p>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach ?>
                    </div>
                </section>

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
                <div class="fcc-sidebar-sticky" id="fcc-popular-widget">
                    <?php if(settings()->content->blog_search_widget_is_enabled): ?>
                        <div class="card mb-4 fcc-glass-card fcc-widget-card">
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

                    <?php if(settings()->content->blog_categories_widget_is_enabled && count($data->blog_posts_main_categories)): ?>
                        <div class="card mb-4 fcc-glass-card fcc-widget-card">
                            <div class="card-body">
                                <h3 class="h6 mb-3 orange fcc-widget-title"><?= $fcc_blog_categories_title ?></h3>

                                <ul class="list-style-none m-0 categories-menu fcc-widget-list">
                                    <?php foreach($data->blog_posts_main_categories as $blog_post_main_category): ?>
                                        <li class="mb-2 fcc-widget-link-item <?= $data->blog_posts_category->title == $blog_post_main_category->title ? 'menu-active' : null ?>">
                                            <a href="<?= SITE_URL . ($blog_post_main_category->language ? \Altum\Language::$active_languages[$blog_post_main_category->language] . '/' : null) . 'blog/category/' . $blog_post_main_category->url ?>"><?= $blog_post_main_category->title ?></a>
                                        </li>

                                        <?php foreach($data->blog_posts_parents as $blog_post_parent_category): ?>
                                            <?php if ($blog_post_parent_category->blog_posts_parent_id == $blog_post_main_category->blog_posts_category_id): ?>
                                                <ul class="m-0">
                                                    <li class="mb-2 fcc-widget-link-item <?= $data->blog_posts_category->title == $blog_post_parent_category->title ? 'menu-active' : null ?>">
                                                        <a href="<?= SITE_URL . ($blog_post_parent_category->language ? \Altum\Language::$active_languages[$blog_post_parent_category->language] . '/' : null) . 'blog/category/' . $blog_post_parent_category->url ?>"><?= $blog_post_parent_category->title ?></a>
                                                    </li>

                                                    <?php foreach($data->blog_posts_subcategories as $blog_post_subcategory): ?>
                                                        <?php if ($blog_post_subcategory->blog_posts_parent_id == $blog_post_parent_category->blog_posts_category_id): ?>
                                                            <ul class="m-0">
                                                                <li class="mb-2 fcc-widget-link-item <?= $data->blog_posts_category->title == $blog_post_subcategory->title ? 'menu-active' : null ?>">
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
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "<?= $data->blog_posts_category->title ?>",
                "item": "<?= SITE_URL . ($data->blog_posts_category->language ? \Altum\Language::$active_languages[$data->blog_posts_category->language] . '/' : null) . 'blog/' . $data->blog_posts_category->url ?>"
            }
        ]
    }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
