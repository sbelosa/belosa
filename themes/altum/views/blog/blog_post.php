<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-26: EN/HR widget titles */
$fcc_blog_categories_title = \Altum\Language::$code == 'hr' ? 'Kategorije' : 'Categories';
$fcc_blog_popular_title = \Altum\Language::$code == 'hr' ? 'Popularni postovi' : 'Popular posts';
/* /Custom code: FC-2026-02-26 */
?>

<!-- Custom code: FC-2026-02-26: FCC premium blog post layout -->
<div class="fcc-blog-page-bg">
<div class="container <?= settings()->content->blog_columns == 1 ? 'col-lg-8' : null ?> fcc-blog-wrap">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('blog') ?>"><?= l('blog.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <?php if($data->blog_posts_category): ?>
                    <li><a href="<?= url('blog/category/' . $data->blog_posts_category->url) ?>"><?= $data->blog_posts_category->title ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <?php endif ?>
                <li class="active" aria-current="page"><?= $data->blog_post->title ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <?php if(settings()->content->blog_search_widget_is_enabled): ?>
        <!-- Custom code: FC-2026-03-09: mobile search directly below menu/top section -->
        <div class="card mb-4 fcc-glass-card fcc-widget-card d-lg-none" style="position: relative; z-index: 30; overflow: visible;">
            <div class="card-body">
                <form action="<?= url('blog') ?>" method="get" role="form">
                    <div class="position-relative" data-fcc-blog-search style="z-index: 40;">
                        <div class="input-group">
                            <input type="search" name="search" class="form-control" value="<?= !empty($_GET['search']) ? input_clean($_GET['search']) : null ?>" placeholder="<?= l('global.search') ?>" aria-label="<?= l('global.search') ?>" autocomplete="off" data-fcc-blog-search-input />

                            <div class="input-group-append">
                                <button class="btn btn-outline-gray-300 text-dark" type="submit" data-toggle="tooltip" title="<?= l('global.submit') ?>"><i class="fas fa-fw fa-search"></i></button>
                            </div>
                        </div>
                        <div class="dropdown-menu w-100" data-fcc-blog-search-results style="z-index: 2050;"></div>
                    </div>
                </form>
            </div>
        </div>
        <!-- /Custom code: FC-2026-03-09 -->
    <?php endif ?>

    <div class="row fcc-blog-grid">
        <?php /* Custom code: FC-2026-03-09: mobile-first article ordering */ ?>
        <div class="<?= settings()->content->blog_columns == 1 ? 'col-12 mb-5' : 'col-12 col-lg-8 mb-lg-0' ?> fcc-blog-main-col order-1 order-lg-1">
        <?php /* /Custom code: FC-2026-03-09 */ ?>
            <div class="card fcc-glass-card fcc-article-card">
                <div class="card-body fcc-article-body">
                    <div class="fcc-article-hero">
                        <?php if($data->blog_post->image): ?>
                            <div class="fcc-hero-image-frame">
                                <img src="<?= \Altum\Uploads::get_full_url('blog') . $data->blog_post->image ?>" class="blog-post-image img-fluid w-100 rounded mb-3 fcc-hero-image" alt="<?= $data->blog_post->image_description ?>" loading="eager" decoding="async" />
                            </div>
                        <?php endif ?>

                        <h1 class="h3 mb-2 fcc-article-title"><?= $data->blog_post->title ?></h1>

                        <div class="fcc-article-meta">
                            <span class="fcc-meta-badge" data-toggle="tooltip" title="<?= sprintf(l('global.last_datetime_tooltip'), \Altum\Date::get($data->blog_post->last_datetime, 2)) ?>">
                                <?= sprintf(l('global.datetime_tooltip'), \Altum\Date::get($data->blog_post->datetime, 2)) ?>
                            </span>

                            <?php if($data->blog_posts_category): ?>
                                <span class="fcc-meta-badge"><?= $data->blog_posts_category->title ?></span>
                            <?php endif ?>

                            <?php if(settings()->content->blog_views_is_enabled): ?>
                                <span class="fcc-meta-badge"><?= sprintf(l('blog.total_views'), nr($data->blog_post->total_views)) ?></span>
                            <?php endif ?>

                            <?php $estimated_reading_time = string_estimate_reading_time($data->blog_post->content) ?>
                            <?php if($estimated_reading_time->minutes > 0 || $estimated_reading_time->seconds > 0): ?>
                                <span class="fcc-meta-badge">
                                    <?= $estimated_reading_time->minutes ? sprintf(l('blog.estimated_reading_time'), $estimated_reading_time->minutes . ' ' . l('global.date.minutes')) : null ?>
                                    <?= $estimated_reading_time->minutes == 0 && $estimated_reading_time->seconds ? sprintf(l('blog.estimated_reading_time'), $estimated_reading_time->seconds . ' ' . l('global.date.seconds')) : null ?>
                                </span>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="blog-post-content fcc-blog-post-content">
                        <p><?= $data->blog_post->description ?></p>

                        <!-- Custom code -->
                        <div class="ql-content">
                            <?= (new \Altum\Shortcodes)->display_shortcodes($data->blog_post->content, $data->referral ?? null) ?>                                
                        </div>

                        <?php if (!empty($data->referral)): ?>                            
                            <h2 class="mt-5 fcc-h2-accent"><?= sprintf(l('blog.more_info.heading')); ?></h2>
                            <?php $referral_full_url = url($data->referral); ?>
                            <p><?= '<a target="_blank" href="' . $referral_full_url . '">' . $referral_full_url . '</a>' ?></p>
                        <?php endif; ?>                
                        <!-- /Custom code -->
                    </div>

                    <?= include_view(THEME_PATH . 'views/blog/ratings.php', [
                        'blog_post' => $data->blog_post,
                    ]); ?>
                </div>
            </div>

            <?php
            /* Custom code: FC-2026-02-26: unified share referral URL */
            $share_referral_key = null;
            if(isset($data->referral) && $data->referral) {
                $share_referral_key = $data->referral;
            } elseif(isset($data->biolink->url) && $data->biolink->url) {
                $share_referral_key = $data->biolink->url;
            }

            $share_url = url(\Altum\Router::$original_request . ($share_referral_key ? '?ref=' . $share_referral_key : ''));
            /* /Custom code: FC-2026-02-26 */

            ?>
            <?php if(settings()->content->blog_share_is_enabled): ?>
                <div class="card mt-4 fcc-glass-card fcc-share-card">
                    <div class="card-body">
                        <?php
                        /* Custom code: FC-2026-03-09: place primary product CTA above main share block */
                        $blog_product_cta_url = $data->webshop_link ?: null;
                        $blog_contact_cta_url = !empty($data->referral) ? url($data->referral) : null;
                        $blog_cta_url = $blog_product_cta_url ?: $blog_contact_cta_url;
                        /* /Custom code: FC-2026-03-09 */
                        ?>
                        <?php if ($blog_cta_url): ?>
                            <a target="_blank" href="<?= $blog_cta_url ?>" class="mt-4 mb-4 btn btn-block btn-primary link-btn link-hover-animation link-btn-rounded animate__animated animate__ animate__false animate__delay-2s fcc-cta-btn fcc-cta-btn-primary">
                                <span data-icon="">
                                     <i class="fas fa-shopping-cart mr-1"></i>
                                </span>
                                <span data-name=""><?= $blog_product_cta_url ? (($data->blog_post->blog_post_id != 406 && $data->blog_post->blog_post_id != 407) ? sprintf(l('blog.buy_product')) : sprintf(l('blog.start_business'))) : sprintf(l('blog.more_info.heading')); ?>
                                </span>
                            </a>
                        <?php endif; ?>

                        <?php /* Custom code: FC-2026-03-09: share helper text by authentication state */ ?>
                        <?php $is_logged_user = is_logged_in(); ?>
                        <div class="mb-3 p-3 rounded position-relative fcc-share-helper" id="blog-share-referral-wrapper">
                            <div class="d-flex align-items-center justify-content-between flex-wrap fcc-share-helper-row">
                                <span class="small mb-2 mb-md-0 fcc-share-helper-text">
                                    <?= $is_logged_user ? l('blog.share_referral.helper_text') : 'Pošaljite ili podijelite ovaj članak sa prijateljima.' ?>
                                </span>

                                <?php if($is_logged_user): ?>
                                    <button
                                        type="button"
                                        id="blog-share-referral-toggle"
                                        class="btn btn-sm btn-gray-100 fcc-share-helper-action"
                                        aria-expanded="false"
                                        aria-controls="blog-share-referral-popup"
                                    >
                                        <i class="fas fa-fw fa-info-circle mr-1"></i>
                                        <?= l('blog.share_referral.learn_more') ?>
                                    </button>
                                <?php endif ?>
                            </div>

                            <?php if($is_logged_user): ?>
                                <div id="blog-share-referral-popup" class="d-none mt-3 p-3 rounded fcc-share-helper-popup">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="pr-2">
                                            <div class="small font-weight-bold mb-2 fcc-share-helper-popup-title">
                                                <?= l('blog.share_referral.modal_title') ?>
                                            </div>
                                            <div class="small fcc-share-helper-popup-text">
                                                <?= l('blog.share_referral.modal_text') ?>
                                            </div>
                                        </div>

                                        <button type="button" id="blog-share-referral-close" class="btn btn-sm btn-gray-100 fcc-share-helper-action" aria-label="<?= l('global.close') ?>">
                                            <i class="fas fa-fw fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endif ?>
                        </div>
                        <?php /* /Custom code: FC-2026-03-09 */ ?>

                        <div class="d-flex align-items-center flex-wrap gap-3">
                            <?= include_view(THEME_PATH . 'views/partials/share_buttons.php', ['url' => $share_url, 'class' => 'btn btn-gray-100', 'copy_to_clipboard' => true]) ?>
                        </div>
                    </div>
                </div>
            <?php endif ?>
            
           <?php if (\Altum\Authentication::is_pro() && $data->blog_posts_category && $data->blog_posts_category->show_share_links == 1): ?>
                <div class="d-flex justify-content-center align-items-center mt-5">
                    <hr class="w-100" style="border-color: #26282B;">

                    <span class="mx-4">
                        <svg class="svg-inline--fa fa-infinity fa-w-20 fa-fw" style="color: #26282B;" aria-hidden="true" focusable="false" data-prefix="fa" data-icon="infinity" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" data-fa-i2svg=""><path fill="currentColor" d="M471.1 96C405 96 353.3 137.3 320 174.6 286.7 137.3 235 96 168.9 96 75.8 96 0 167.8 0 256s75.8 160 168.9 160c66.1 0 117.8-41.3 151.1-78.6 33.3 37.3 85 78.6 151.1 78.6 93.1 0 168.9-71.8 168.9-160S564.2 96 471.1 96zM168.9 320c-40.2 0-72.9-28.7-72.9-64s32.7-64 72.9-64c38.2 0 73.4 36.1 94 64-20.4 27.6-55.9 64-94 64zm302.2 0c-38.2 0-73.4-36.1-94-64 20.4-27.6 55.9-64 94-64 40.2 0 72.9 28.7 72.9 64s-32.7 64-72.9 64z"></path></svg><!-- <i class="fa fa-infinity fa-fw" style="color: #26282B;"></i> Font Awesome fontawesome.com -->
                    </span>

                    <hr class="w-100" style="border-color: #26282B;">
                </div>
                <h6 class="mt-3 mb-4 fcc-widget-title"><?= sprintf(l('blog.more_info.share')) ?></h6>
                <div class="d-flex align-items-center justify-content-between flex-wrap mt-4">
                    <button type="button" id="copy-url" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3" style="color:#41aaa5" data-url="<?= $share_url ?>" data-toggle="tooltip" title="<?= l('blog.copy_url') ?>" onclick="copy_url()"><i class="fa fa-fw fa-sm fa-link"></i></button>
                    <input type="hidden" id="copy-url-copied" value="<?= l('blog.copy_url.copied') ?>" />

                    <a href="mailto:?body=<?= $share_url ?>" target="_blank" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3">
                        <i class="fa fa-fw fa-envelope"></i>
                    </a>

                    <button type="button" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3" style="color:#41aaa5" data-toggle="tooltip" title="<?= l('page.print') ?>" onclick="window.print()"><i class="fa fa-fw fa-sm fa-print"></i></button>

                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $share_url ?>" target="_blank" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3">
                        <i class="fab fa-fw fa-facebook"></i>
                    </a>
                    <a href="https://wa.me/?text=<?= $share_url ?>" class="btn btn-gray-100 mb-2 mb-md-0 mr-md-3">
                        <i class="fab fa-fw fa-whatsapp"></i>
                    </a>
                </div>
            <?php endif; ?>                        
         
            <?php if($data->blog_posts_category): ?>
                <?php /* Custom code: FC-2026-03-09: keep back button as secondary CTA */ ?>
                <a href="<?= SITE_URL . ($data->blog_posts_category->language ? \Altum\Language::$active_languages[$data->blog_posts_category->language] . '/' : null) . 'blog/category/' . $data->blog_posts_category->url ?>" class="mt-5 btn btn-block btn-primary link-btn link-hover-animation link-btn-rounded animate__animated animate__ animate__false animate__delay-2s fcc-cta-btn fcc-cta-btn-secondary">                        
                <?php /* /Custom code: FC-2026-03-09 */ ?>
                    <span data-icon="">
                        <svg class="svg-inline--fa fa-angle-double-left fa-w-14 mr-1" aria-hidden="true" focusable="false" data-prefix="fas" data-icon="angle-double-left" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" data-fa-i2svg=""><path fill="currentColor" d="M223.7 239l136-136c9.4-9.4 24.6-9.4 33.9 0l22.6 22.6c9.4 9.4 9.4 24.6 0 33.9L319.9 256l96.4 96.4c9.4 9.4 9.4 24.6 0 33.9L393.7 409c-9.4 9.4-24.6 9.4-33.9 0l-136-136c-9.5-9.4-9.5-24.6-.1-34zm-192 34l136 136c9.4 9.4 24.6 9.4 33.9 0l22.6-22.6c9.4-9.4 9.4-24.6 0-33.9L127.9 256l96.4-96.4c9.4-9.4 9.4-24.6 0-33.9L201.7 103c-9.4-9.4-24.6-9.4-33.9 0l-136 136c-9.5 9.4-9.5 24.6-.1 34z"></path></svg><!-- <i class="fas fa-angle-double-left mr-1"></i> Font Awesome fontawesome.com -->
                    </span>
                    <span data-name=""><?= sprintf(l('blog.back')); ?></span>
                </a>
            <?php endif ?>
            <!-- /Custom code -->
        </div>

        <?php if(settings()->content->blog_popular_widget_is_enabled || settings()->content->blog_categories_widget_is_enabled || settings()->content->blog_search_widget_is_enabled): ?>
            <?php /* Custom code: FC-2026-03-09: move sidebar widgets after article on mobile */ ?>
            <div class="<?= settings()->content->blog_columns == 1 ? 'col-12' : 'col-12 col-lg-4' ?> fcc-blog-sidebar-col order-2 order-lg-2">
            <?php /* /Custom code: FC-2026-03-09 */ ?>
                <div class="fcc-sidebar-sticky">
                <?php if(settings()->content->blog_search_widget_is_enabled): ?>
                    <div class="card mb-4 fcc-glass-card fcc-widget-card d-none d-lg-block" style="position: relative; z-index: 30; overflow: visible;">
                        <div class="card-body">
                            <form action="<?= url('blog') ?>" method="get" role="form">
                                <!-- Custom code: FC-2026-03-09: blog autocomplete-ready search input -->
                                <div class="position-relative" data-fcc-blog-search style="z-index: 40;">
                                    <div class="input-group">
                                        <input type="search" name="search" class="form-control" value="<?= !empty($_GET['search']) ? input_clean($_GET['search']) : null ?>" placeholder="<?= l('global.search') ?>" aria-label="<?= l('global.search') ?>" autocomplete="off" data-fcc-blog-search-input />

                                        <div class="input-group-append">
                                            <button class="btn btn-outline-gray-300 text-dark" type="submit" data-toggle="tooltip" title="<?= l('global.submit') ?>"><i class="fas fa-fw fa-search"></i></button>
                                        </div>
                                    </div>
                                    <div class="dropdown-menu w-100" data-fcc-blog-search-results style="z-index: 2050;"></div>
                                </div>
                                <!-- /Custom code: FC-2026-03-09 -->
                            </form>
                        </div>
                    </div>
                <?php endif ?>

                <?php if(settings()->content->blog_categories_widget_is_enabled && count($data->blog_posts_main_categories)): ?>
                     <?php if(count($data->blog_posts_main_categories)): ?>
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
                    "name": "<?= $data->blog_post->title ?>",
                    "item": "<?= SITE_URL . ($data->blog_post->language ? \Altum\Language::$active_languages[$data->blog_post->language] . '/' : null) . 'blog/' . $data->blog_post->url ?>"
                }
            ]
        }
</script>

<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BlogPosting",
        "headline": "<?= $data->blog_post->title ?>",
        "description": "<?= $data->blog_post->description ?>",
        "url": "<?= SITE_URL . ($data->blog_post->language ? \Altum\Language::$active_languages[$data->blog_post->language] . '/' : null) . 'blog/' . $data->blog_post->url ?>",
    <?php if($data->blog_post->image): ?>
        "image": "<?= \Altum\Uploads::get_full_url('blog') . $data->blog_post->image ?>",
        <?php endif ?>
    "author": {
        "@type": "Person",
        "name": "<?= settings()->main->title ?>",
            "url": "<?= SITE_URL ?>"
        },

    <?php if(settings()->content->blog_ratings_is_enabled && $data->blog_post->total_ratings > 0): ?>
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "<?= $data->blog_post->average_rating ?>",
            "reviewCount": "<?= $data->blog_post->total_ratings ?>",
            "itemReviewed" : {
                "@type": "Book",
                "name": "<?= $data->blog_post->title ?>"
            }
        },
        <?php endif ?>

    "publisher": {
        "@type": "Organization",
        "name": "<?= settings()->main->title ?>"
    <?php if(settings()->main->{'logo_' . \Altum\ThemeStyle::get()} != ''): ?>
            ,"logo": {
                "@type": "ImageObject",
                "url": "<?= settings()->main->{'logo_' . \Altum\ThemeStyle::get() . '_full_url'} ?>"
            }
            <?php endif ?>
    },
    "datePublished": "<?= (new \DateTime($data->blog_post->datetime))->format('Y-m-d\TH:i:sP') ?>",
        "dateModified": "<?= (new \DateTime($data->blog_post->last_datetime))->format('Y-m-d\TH:i:sP') ?>",
        "keywords": "<?= $data->blog_post->keywords ?>",
        "wordCount": "<?= str_word_count($data->blog_post->content ?? '') ?>",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "<?= SITE_URL . ($data->blog_post->language ? \Altum\Language::$active_languages[$data->blog_post->language] . '/' : null) ?>"
        }
    }
</script>

<script>
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {
        const popupWrapper = document.getElementById('blog-share-referral-wrapper');
        const popupToggle = document.getElementById('blog-share-referral-toggle');
        const popupClose = document.getElementById('blog-share-referral-close');
        const popupPanel = document.getElementById('blog-share-referral-popup');

        if(!popupWrapper || !popupToggle || !popupPanel) {
            return;
        }

        const showPopup = () => {
            popupPanel.classList.remove('d-none');
            popupToggle.setAttribute('aria-expanded', 'true');
        };

        const hidePopup = () => {
            popupPanel.classList.add('d-none');
            popupToggle.setAttribute('aria-expanded', 'false');
        };

        popupToggle.addEventListener('click', (event) => {
            event.preventDefault();

            if(popupPanel.classList.contains('d-none')) {
                showPopup();
            } else {
                hidePopup();
            }
        });

        if(popupClose) {
            popupClose.addEventListener('click', (event) => {
                event.preventDefault();
                hidePopup();
            });
        }

        document.addEventListener('click', (event) => {
            if(!popupPanel.classList.contains('d-none') && !popupWrapper.contains(event.target)) {
                hidePopup();
            }
        });

        document.addEventListener('keydown', (event) => {
            if(event.key === 'Escape' && !popupPanel.classList.contains('d-none')) {
                hidePopup();
            }
        });
    });
</script>

<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php ob_start() ?>
<script>
/* Custom code: FC-2026-03-09: blog autocomplete UI */
(function() {
    if(window.FCCBlogSearchAutocompleteInitialized) {
        return;
    }

    window.FCCBlogSearchAutocompleteInitialized = true;

    const initAutocomplete = (wrapper) => {
        const input = wrapper.querySelector('[data-fcc-blog-search-input]');
        const resultsMenu = wrapper.querySelector('[data-fcc-blog-search-results]');

        if(!input || !resultsMenu) {
            return;
        }

        let debounceTimeout = null;
        let suggestions = [];
        let activeIndex = -1;

        const hideResults = () => {
            resultsMenu.classList.remove('show');
            resultsMenu.innerHTML = '';
            suggestions = [];
            activeIndex = -1;
        };

        const escapeHtml = (value) => {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        };

        const renderResults = () => {
            if(!suggestions.length) {
                hideResults();
                return;
            }

            resultsMenu.innerHTML = suggestions.map((item, index) => `
                <a href="${item.url}" class="dropdown-item d-flex align-items-start py-2 ${index === activeIndex ? 'active' : ''}" data-fcc-suggestion-index="${index}">
                    ${item.image ? `<img src="${item.image}" alt="${escapeHtml(item.image_description || item.title)}" class="rounded mr-2" style="width: 40px; height: 40px; object-fit: cover;">` : '<span class="rounded mr-2 bg-gray-100" style="width: 40px; height: 40px;"></span>'}
                    <span class="d-flex flex-column text-truncate" style="min-width: 0;">
                        <span class="font-weight-bold text-truncate">${escapeHtml(item.title)}</span>
                        <small class="text-muted text-truncate">${escapeHtml(item.matched_term || '')}</small>
                    </span>
                </a>
            `).join('');

            resultsMenu.classList.add('show');
        };

        const setActive = (newIndex) => {
            if(!suggestions.length) {
                return;
            }

            activeIndex = newIndex;
            if(activeIndex < 0) activeIndex = suggestions.length - 1;
            if(activeIndex >= suggestions.length) activeIndex = 0;

            renderResults();
        };

        const requestSuggestions = async (query) => {
            try {
                const response = await fetch(`${url}blog/suggestions_ajax?search=${encodeURIComponent(query)}`);
                const data = await response.json();

                if(!response.ok || data.status !== 'success') {
                    hideResults();
                    return;
                }

                suggestions = data.details?.results ?? [];
                activeIndex = -1;
                renderResults();
            } catch(error) {
                hideResults();
            }
        };

        input.addEventListener('input', () => {
            const query = input.value.trim();

            if(debounceTimeout) {
                clearTimeout(debounceTimeout);
            }

            if(query.length < 3) {
                hideResults();
                return;
            }

            debounceTimeout = setTimeout(() => requestSuggestions(query), 250);
        });

        input.addEventListener('keydown', (event) => {
            if(!resultsMenu.classList.contains('show')) {
                return;
            }

            if(event.key === 'ArrowDown') {
                event.preventDefault();
                setActive(activeIndex + 1);
            }

            if(event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(activeIndex - 1);
            }

            if(event.key === 'Enter' && activeIndex >= 0 && suggestions[activeIndex]) {
                event.preventDefault();
                window.location.href = suggestions[activeIndex].url;
            }

            if(event.key === 'Escape') {
                hideResults();
            }
        });

        resultsMenu.addEventListener('click', (event) => {
            const item = event.target.closest('[data-fcc-suggestion-index]');
            if(!item) {
                return;
            }

            const itemIndex = parseInt(item.getAttribute('data-fcc-suggestion-index'));
            if(Number.isInteger(itemIndex) && suggestions[itemIndex]) {
                window.location.href = suggestions[itemIndex].url;
            }
        });

        document.addEventListener('click', (event) => {
            if(!wrapper.contains(event.target)) {
                hideResults();
            }
        });
    };

    document.querySelectorAll('[data-fcc-blog-search]').forEach(initAutocomplete);
})();
/* /Custom code: FC-2026-03-09 */
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
