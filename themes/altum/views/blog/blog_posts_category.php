<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-02-26: EN/HR widget titles and category hero labels */
$fcc_blog_categories_title = l('blog.categories');
$fcc_blog_popular_title = l('blog.popular');
$fcc_category_hero_badge = l('blog.category.badge');
$fcc_category_back_to_blog = l('blog.back');
$fcc_category_all_posts = l('blog.category.all_posts');
$fcc_blog_home_url = url('blog');
$fcc_is_forever_products_category = in_array((string) ($data->blog_posts_category->url ?? ''), ['forever-products', 'forever-proizvodi'], true);
$fcc_first_blog_post = !empty($data->blog_posts) ? array_values($data->blog_posts)[0] : null;
$fcc_first_blog_post_url = $fcc_first_blog_post ? SITE_URL . ($fcc_first_blog_post->language ? \Altum\Language::$active_languages[$fcc_first_blog_post->language] . '/' : null) . 'blog/' . $fcc_first_blog_post->url : null;
/* /Custom code: FC-2026-02-26 */
?>

<?php ob_start() ?>
<style>
    .fcc-referral-tour-target {
        scroll-margin-top: 6rem;
    }

    .fcc-referral-tour-target.is-active {
        position: relative !important;
        z-index: 2052 !important;
        box-shadow: 0 0 0 2px rgba(73, 227, 207, .95), 0 0 0 14px rgba(73, 227, 207, .16), 0 24px 72px rgba(2, 8, 23, .42) !important;
        border-radius: 1.35rem !important;
    }

    .fcc-referral-tour-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2050;
        display: none;
        pointer-events: none;
    }

    .fcc-referral-tour-backdrop.is-visible {
        display: block;
    }

    .fcc-referral-tour-backdrop-segment {
        position: fixed;
        background: rgba(2, 8, 23, .58);
        backdrop-filter: blur(3px);
        pointer-events: none;
    }

    .fcc-referral-tour-popover {
        position: fixed;
        z-index: 2055;
        width: min(24rem, calc(100vw - 2rem));
        display: none;
        border-radius: 1.2rem;
        border: 1px solid rgba(147, 197, 253, .22);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .18), transparent 30%),
            linear-gradient(180deg, rgba(25, 36, 58, .98), rgba(16, 24, 41, .97));
        box-shadow: 0 30px 80px rgba(2, 8, 23, .44), inset 0 1px 0 rgba(255,255,255,.05);
        padding: 1.05rem 1.05rem 1rem;
    }

    .fcc-referral-tour-popover.is-visible {
        display: block;
    }

    .fcc-referral-tour-progress {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .35rem .65rem;
        border-radius: 999px;
        background: rgba(73, 227, 207, .18);
        color: #e8fffb;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: .75rem;
        border: 1px solid rgba(73, 227, 207, .16);
    }

    .fcc-referral-tour-title {
        color: #f8fbff;
        font-size: 1.12rem;
        font-weight: 800;
        line-height: 1.3;
        margin-bottom: .45rem;
    }

    .fcc-referral-tour-text {
        color: rgba(236, 244, 255, .94);
        font-size: .94rem;
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .fcc-referral-tour-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .fcc-referral-tour-actions-main {
        display: flex;
        gap: .65rem;
        flex-wrap: wrap;
    }

    .fcc-referral-tour-actions .btn {
        border-radius: .85rem;
    }

    .fcc-referral-tour-actions .btn-link {
        color: rgba(226, 232, 240, .82) !important;
        text-decoration: none;
    }

    .fcc-referral-tour-actions .btn-outline-light {
        color: #ecf8ff !important;
        border-color: rgba(147, 197, 253, .28) !important;
        background: rgba(59, 130, 246, .12) !important;
    }

    @media (max-width: 767.98px) {
        .fcc-referral-tour-popover {
            left: 1rem !important;
            right: 1rem !important;
            width: auto;
            top: auto !important;
            bottom: 1rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

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
        <?php /* Custom code: FC-2026-03-09: mobile-first sidebar ordering */ ?>
        <div class="<?= settings()->content->blog_columns == 1 ? 'col-12 mb-5' : 'col-12 col-lg-8 mb-lg-0' ?> fcc-blog-main-col order-2 order-lg-1">
        <?php /* /Custom code: FC-2026-03-09 */ ?>
            <section class="card mb-4 fcc-glass-card fcc-blog-home-hero fcc-category-hero fcc-referral-tour-target" id="fcc_referral_tour_category_hero">
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
                        <?php foreach(array_values($data->blog_posts) as $blog_post_index => $blog_post): ?>
                            <div class="col-12 col-md-6 mb-3">
                                <article class="card h-100 fcc-glass-card fcc-post-card fcc-post-card-compact <?= $blog_post_index === 0 ? 'fcc-referral-tour-target' : null ?>" <?= $blog_post_index === 0 ? 'id="fcc_referral_tour_first_post"' : null ?>>
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
            <?php /* Custom code: FC-2026-03-09: mobile-first sidebar ordering */ ?>
            <div class="<?= settings()->content->blog_columns == 1 ? 'col-12' : 'col-12 col-lg-4' ?> fcc-blog-sidebar-col order-1 order-lg-2">
            <?php /* /Custom code: FC-2026-03-09 */ ?>
                <div class="fcc-sidebar-sticky" id="fcc-popular-widget">
                    <?php if(settings()->content->blog_search_widget_is_enabled): ?>
                        <div class="card mb-4 fcc-glass-card fcc-widget-card fcc-referral-tour-target" id="fcc_referral_tour_category_search" style="position: relative; z-index: 30; overflow: visible;">
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

<div class="fcc-referral-tour-backdrop" id="fcc_referral_tour_backdrop">
    <div class="fcc-referral-tour-backdrop-segment" data-segment="top"></div>
    <div class="fcc-referral-tour-backdrop-segment" data-segment="left"></div>
    <div class="fcc-referral-tour-backdrop-segment" data-segment="right"></div>
    <div class="fcc-referral-tour-backdrop-segment" data-segment="bottom"></div>
</div>
<div class="fcc-referral-tour-popover" id="fcc_referral_tour_popover" aria-live="polite">
    <div class="fcc-referral-tour-progress" id="fcc_referral_tour_progress">1 / 3</div>
    <div class="fcc-referral-tour-title" id="fcc_referral_tour_title"></div>
    <div class="fcc-referral-tour-text" id="fcc_referral_tour_text"></div>
    <div class="fcc-referral-tour-actions">
        <button type="button" class="btn btn-link text-muted px-0" id="fcc_referral_tour_skip"><?= l('dashboard.tour.skip') ?></button>
        <div class="fcc-referral-tour-actions-main">
            <button type="button" class="btn btn-outline-light" id="fcc_referral_tour_prev"><?= l('dashboard.tour.prev') ?></button>
            <button type="button" class="btn btn-primary" id="fcc_referral_tour_next"><?= l('dashboard.tour.next') ?></button>
        </div>
    </div>
</div>

<?php ob_start() ?>
<script>
(function() {
    'use strict';

    const storageKey = 'fcc_blog_referral_tour_v1';
    const isForeverProductsCategory = <?= json_encode($fcc_is_forever_products_category) ?>;
    const firstPostUrl = <?= json_encode($fcc_first_blog_post_url) ?>;
    const nextLabel = <?= json_encode(l('dashboard.tutorials.blog_referral.next_open_article')) ?>;
    const defaultNextLabel = <?= json_encode(l('dashboard.tour.next')) ?>;

    let activeStep = -1;
    let currentTarget = null;

    const setTourMode = isActive => {
        document.body.classList.toggle('fcc-tour-mode', !!isActive);

        if(typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent('fcc:tutorial:state', {
                detail: {active: !!isActive}
            }));
        }
    };

    const steps = [
        {
            selector: '#fcc_referral_tour_category_hero',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_1_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_1_text')) ?>,
        },
        {
            selector: '#fcc_referral_tour_category_search',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_2_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_2_text')) ?>,
        },
        {
            selector: '#fcc_referral_tour_first_post',
            title: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_3_title')) ?>,
            text: <?= json_encode(l('dashboard.tutorials.blog_referral.category_step_3_text')) ?>,
        }
    ];

    const getState = () => {
        try {
            const raw = localStorage.getItem(storageKey);
            return raw ? JSON.parse(raw) : null;
        } catch(error) {
            return null;
        }
    };

    const setState = (state) => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(state));
        } catch(error) {
            /* Ignore storage failures. */
        }
    };

    const clearState = () => {
        try {
            localStorage.removeItem(storageKey);
        } catch(error) {
            /* Ignore storage failures. */
        }
    };

    const clearHighlight = () => {
        if(currentTarget) {
            currentTarget.classList.remove('is-active');
        }

        currentTarget = null;
    };

    const updateBackdrop = (rect) => {
        const backdrop = document.getElementById('fcc_referral_tour_backdrop');
        if(!backdrop || !rect) {
            return;
        }

        const topSegment = backdrop.querySelector('[data-segment="top"]');
        const leftSegment = backdrop.querySelector('[data-segment="left"]');
        const rightSegment = backdrop.querySelector('[data-segment="right"]');
        const bottomSegment = backdrop.querySelector('[data-segment="bottom"]');

        if(!topSegment || !leftSegment || !rightSegment || !bottomSegment) {
            return;
        }

        const padding = 12;
        const top = Math.max(0, rect.top - padding);
        const left = Math.max(0, rect.left - padding);
        const right = Math.min(window.innerWidth, rect.right + padding);
        const bottom = Math.min(window.innerHeight, rect.bottom + padding);
        const spotlightHeight = Math.max(0, bottom - top);

        topSegment.style.top = '0px';
        topSegment.style.left = '0px';
        topSegment.style.width = '100vw';
        topSegment.style.height = `${top}px`;

        leftSegment.style.top = `${top}px`;
        leftSegment.style.left = '0px';
        leftSegment.style.width = `${left}px`;
        leftSegment.style.height = `${spotlightHeight}px`;

        rightSegment.style.top = `${top}px`;
        rightSegment.style.left = `${right}px`;
        rightSegment.style.width = `${Math.max(0, window.innerWidth - right)}px`;
        rightSegment.style.height = `${spotlightHeight}px`;

        bottomSegment.style.top = `${bottom}px`;
        bottomSegment.style.left = '0px';
        bottomSegment.style.width = '100vw';
        bottomSegment.style.height = `${Math.max(0, window.innerHeight - bottom)}px`;
    };

    const placePopover = () => {
        const popover = document.getElementById('fcc_referral_tour_popover');
        if(!popover || !currentTarget || !popover.classList.contains('is-visible')) {
            return;
        }

        const rect = currentTarget.getBoundingClientRect();
        const margin = 20;
        const popoverWidth = popover.offsetWidth || 360;
        const popoverHeight = popover.offsetHeight || 220;

        updateBackdrop(rect);

        let top = rect.bottom + 16;
        if(top + popoverHeight > window.innerHeight - margin) {
            top = Math.max(margin, rect.top - popoverHeight - 16);
        }

        let left = rect.left;
        if(left + popoverWidth > window.innerWidth - margin) {
            left = window.innerWidth - popoverWidth - margin;
        }

        if(left < margin) {
            left = margin;
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    };

    const endTour = (clear = false) => {
        const backdrop = document.getElementById('fcc_referral_tour_backdrop');
        const popover = document.getElementById('fcc_referral_tour_popover');

        clearHighlight();
        activeStep = -1;
        setTourMode(false);

        if(backdrop) {
            backdrop.classList.remove('is-visible');
        }

        if(popover) {
            popover.classList.remove('is-visible');
            popover.style.top = '';
            popover.style.left = '';
        }

        if(clear) {
            clearState();
        }
    };

    const renderStep = (index) => {
        if(index >= steps.length) {
            endTour();
            return;
        }

        const step = steps[index];
        const target = step ? document.querySelector(step.selector) : null;
        const popover = document.getElementById('fcc_referral_tour_popover');
        const backdrop = document.getElementById('fcc_referral_tour_backdrop');

        if(!step || !popover || !backdrop) {
            endTour(true);
            return;
        }

        if(!target) {
            renderStep(index + 1);
            return;
        }

        activeStep = index;
        clearHighlight();
        currentTarget = target;
        currentTarget.classList.add('is-active');
        setTourMode(true);
        currentTarget.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'});

        document.getElementById('fcc_referral_tour_title').textContent = step.title;
        document.getElementById('fcc_referral_tour_text').textContent = step.text;
        document.getElementById('fcc_referral_tour_progress').textContent = `${index + 1} / ${steps.length}`;

        const prevButton = document.getElementById('fcc_referral_tour_prev');
        const nextButton = document.getElementById('fcc_referral_tour_next');

        if(prevButton) {
            prevButton.style.display = index === 0 ? 'none' : 'inline-flex';
        }

        if(nextButton) {
            nextButton.textContent = index === steps.length - 1 && firstPostUrl ? nextLabel : defaultNextLabel;
        }

        backdrop.classList.add('is-visible');
        popover.classList.add('is-visible');

        setTimeout(placePopover, 140);
    };

    const initTutorial = () => {
        if(!isForeverProductsCategory) {
            return;
        }

        const state = getState();
        if(!state || state.flow !== 'blog_referral' || state.stage !== 'category') {
            return;
        }

        const skipButton = document.getElementById('fcc_referral_tour_skip');
        const prevButton = document.getElementById('fcc_referral_tour_prev');
        const nextButton = document.getElementById('fcc_referral_tour_next');

        if(skipButton) {
            skipButton.addEventListener('click', () => endTour(true));
        }

        if(prevButton) {
            prevButton.addEventListener('click', () => {
                if(activeStep > 0) {
                    renderStep(activeStep - 1);
                }
            });
        }

        if(nextButton) {
            nextButton.addEventListener('click', () => {
                if(activeStep >= steps.length - 1) {
                    if(firstPostUrl) {
                        setState({
                            flow: 'blog_referral',
                            stage: 'article',
                            started_at: state.started_at ?? Date.now()
                        });

                        window.location.href = firstPostUrl;
                        return;
                    }

                    endTour(true);
                    return;
                }

                renderStep(activeStep + 1);
            });
        }

        window.addEventListener('resize', placePopover);
        window.addEventListener('scroll', placePopover, {passive: true});

        setTimeout(() => renderStep(0), 420);
    };

    document.addEventListener('DOMContentLoaded', initTutorial);
})();
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
                "item": "<?= $data->blog_posts_category_url ?? (SITE_URL . ($data->blog_posts_category->language ? \Altum\Language::$active_languages[$data->blog_posts_category->language] . '/' : null) . 'blog/category/' . $data->blog_posts_category->url) ?>"
            }
        ]
    }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php ob_start() ?>
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "CollectionPage",
        "name": <?= json_encode($data->blog_posts_category->title) ?>,
        "description": <?= json_encode($data->blog_posts_category->description ?: $data->blog_posts_category->title) ?>,
        "url": <?= json_encode($data->blog_posts_category_url ?? (SITE_URL . ($data->blog_posts_category->language ? \Altum\Language::$active_languages[$data->blog_posts_category->language] . '/' : null) . 'blog/category/' . $data->blog_posts_category->url)) ?>,
        "hasPart": {
            "@type": "ItemList",
            "itemListElement": [
                <?php foreach(array_values($data->blog_posts ?? []) as $index => $blog_post): ?>
                {
                    "@type": "ListItem",
                    "position": <?= $index + 1 ?>,
                    "url": <?= json_encode(SITE_URL . ($blog_post->language ? \Altum\Language::$active_languages[$blog_post->language] . '/' : null) . 'blog/' . $blog_post->url) ?>,
                    "name": <?= json_encode($blog_post->title) ?>
                }<?= $index + 1 < count($data->blog_posts ?? []) ? ',' : null ?>
                <?php endforeach ?>
            ]
        }
    }
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
