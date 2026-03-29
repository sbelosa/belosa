<?php defined('ALTUMCODE') || die() ?>

<?php $fcc_is_hr = \Altum\Language::$code === 'hr'; ?>
<?php $featured_app_visible_tags_limit = 2; ?>

<div class="container my-5 featured-apps-page">
    <section class="featured-apps-hero mb-4">
        <div>
            <div class="featured-apps-eyebrow"><?= l('featured_apps.header') ?></div>
            <h1 class="h2 mb-2"><?= l('featured_apps.header') ?></h1>
            <p class="text-muted mb-0"><?= l('featured_apps.subheader') ?></p>
        </div>

        <div class="featured-apps-note">
            <strong class="d-block mb-1"><?= l('featured_apps.notice_title') ?></strong>
            <span><?= sprintf(l('featured_apps.notice_text'), nr($data->min_qualified_clicks), nr($data->period_days)) ?></span>
        </div>
    </section>

    <?php if(empty($data->featured_apps)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body py-4 text-center text-muted">
                <?= l('featured_apps.empty') ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach($data->featured_apps as $app): ?>
                <div class="col-12 col-lg-6 mb-4">
                    <article class="featured-app-card card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <img
                                    src="<?= $app['display_image_url'] ?: $app['default_image_url'] ?>"
                                    alt="<?= $app['name'] ?>"
                                    class="rounded-circle mr-3 featured-app-card__avatar"
                                    loading="lazy"
                                    data-default-image="<?= htmlspecialchars($app['default_image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-avatar-image="<?= htmlspecialchars($app['generated_avatar_url'], ENT_QUOTES, 'UTF-8') ?>"
                                    onerror="if(!this.dataset.fallbackStep){this.dataset.fallbackStep='default';this.src=this.dataset.defaultImage;return;}if(this.dataset.fallbackStep==='default'){this.dataset.fallbackStep='avatar';this.src=this.dataset.avatarImage;return;}this.onerror=null;"
                                />
                                <div class="min-width-0">
                                    <div class="font-weight-bold text-truncate"><?= $app['name'] ?></div>
                                    <div class="small text-muted"><?= l('featured_apps.card_label') ?></div>
                                </div>
                            </div>

                            <div class="featured-app-card__meta mb-3">
                                <?php if(!empty($app['public_market'])): ?>
                                    <span class="featured-app-pill"><?= l('featured_apps.market') ?>: <?= $app['public_market'] ?></span>
                                <?php endif ?>

                                <span class="featured-app-pill featured-app-pill--accent"><?= l('featured_apps.performance') ?>: <?= nr($app['shop_clicks']) ?></span>
                            </div>

                            <?php if(!empty($app['public_summary'])): ?>
                                <div class="featured-app-section mb-3">
                                    <div class="featured-app-section__label"><?= l('featured_apps.case_study_label') ?></div>
                                    <p class="mb-0 text-muted featured-app-card__summary"><?= $app['public_summary'] ?></p>
                                </div>
                            <?php endif ?>

                            <?php if(!empty($app['feature_labels'])): ?>
                                <div class="featured-app-section mb-3">
                                    <div class="featured-app-section__label"><?= l('featured_apps.block_usage') ?></div>
                                    <div class="featured-app-tags">
                                        <?php $visible_feature_labels = array_slice($app['feature_labels'], 0, $featured_app_visible_tags_limit); ?>
                                        <?php $remaining_feature_labels = array_slice($app['feature_labels'], $featured_app_visible_tags_limit); ?>

                                        <?php foreach($visible_feature_labels as $feature_label): ?>
                                            <span class="featured-app-tag"><?= $feature_label ?></span>
                                        <?php endforeach ?>

                                        <?php if(!empty($remaining_feature_labels)): ?>
                                            <details class="featured-app-more">
                                                <summary class="featured-app-tag featured-app-tag--more">
                                                    <?= $fcc_is_hr ? '+ još ' . count($remaining_feature_labels) : '+ ' . count($remaining_feature_labels) . ' more' ?>
                                                </summary>

                                                <div class="featured-app-more__panel">
                                                    <?php foreach($remaining_feature_labels as $feature_label): ?>
                                                        <div class="featured-app-more__item"><?= $feature_label ?></div>
                                                    <?php endforeach ?>
                                                </div>
                                            </details>
                                        <?php endif ?>
                                    </div>
                                </div>
                            <?php endif ?>

                            <div class="small text-muted mb-3"><?= l('featured_apps.official_note') ?></div>

                            <a href="<?= $app['app_url'] ?>" target="_blank" rel="nofollow noopener" class="btn btn-primary mt-auto">
                                <?= l('featured_apps.view_app') ?>
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<?php ob_start() ?>
<style>
    .featured-apps-page .featured-apps-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.85fr);
        gap: 1.5rem;
        padding: 1.75rem;
        border-radius: 22px;
        background:
            radial-gradient(900px circle at 10% 15%, rgba(79, 227, 255, 0.12), transparent 45%),
            radial-gradient(700px circle at 100% 0%, rgba(104, 232, 188, 0.1), transparent 40%),
            linear-gradient(160deg, rgba(15, 18, 28, 0.96), rgba(10, 12, 20, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.06);
        box-shadow: 0 20px 46px rgba(0, 0, 0, 0.28);
    }

    .featured-apps-page .featured-apps-eyebrow {
        margin-bottom: 0.7rem;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: rgba(104, 232, 188, 0.9);
    }

    .featured-apps-page .featured-apps-note {
        align-self: start;
        padding: 1rem 1.05rem;
        border-radius: 16px;
        background: rgba(104, 232, 188, 0.08);
        border: 1px solid rgba(104, 232, 188, 0.14);
        color: rgba(238, 245, 252, 0.88);
        line-height: 1.6;
    }

    .featured-app-card {
        background: linear-gradient(160deg, rgba(18, 24, 34, 0.96), rgba(10, 14, 20, 0.98));
        color: #f5f7ff;
    }

    .featured-app-card__avatar {
        width: 56px;
        height: 56px;
        object-fit: cover;
    }

    .featured-app-card__summary {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 4;
        overflow: hidden;
    }

    .featured-app-card__meta,
    .featured-app-tags {
        display: flex;
        gap: 0.35rem;
    }

    .featured-app-pill,
    .featured-app-tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 1.75rem;
        padding: 0.28rem 0.55rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.05);
        color: rgba(240, 244, 251, 0.88);
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }

    .featured-app-pill--accent {
        background: rgba(255, 198, 0, 0.1);
        color: #ffe9a7;
    }

    .featured-app-tag {
        background: rgba(104, 232, 188, 0.1);
        color: #c9fff2;
    }

    .featured-app-tag--more {
        background: rgba(255, 255, 255, 0.06);
        color: rgba(240, 244, 251, 0.82);
        cursor: pointer;
        border: 0;
    }

    .featured-app-more {
        position: relative;
    }

    .featured-app-more summary {
        list-style: none;
    }

    .featured-app-more summary::-webkit-details-marker {
        display: none;
    }

    .featured-app-more__panel {
        position: absolute;
        top: calc(100% + 0.45rem);
        right: 0;
        z-index: 20;
        min-width: 180px;
        padding: 0.7rem 0.75rem;
        border-radius: 14px;
        background: rgba(10, 14, 20, 0.98);
        border: 1px solid rgba(127, 227, 217, 0.14);
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.28);
    }

    .featured-app-more__item {
        font-size: 0.78rem;
        color: #eef7fb;
        line-height: 1.35;
    }

    .featured-app-more__item + .featured-app-more__item {
        margin-top: 0.35rem;
    }

    .featured-app-section__label {
        margin-bottom: 0.45rem;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.55);
    }

    @media (max-width: 991px) {
        .featured-apps-page .featured-apps-hero {
            grid-template-columns: 1fr;
        }

        .featured-apps-page .featured-app-tags {
            flex-wrap: wrap;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
