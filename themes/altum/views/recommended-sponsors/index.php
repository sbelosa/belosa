<?php defined('ALTUMCODE') || die() ?>

<?php $ui = $data->ui ?? []; ?>

<div class="container my-5 fcc-sponsors-page">
    <section class="fcc-sponsors-hero mb-4">
        <div class="fcc-sponsors-hero__content">
            <div class="fcc-sponsors-eyebrow"><?= $ui['hub_eyebrow'] ?? 'Recommended sponsors' ?></div>
            <h1 class="h2 mb-2"><?= $ui['hub_title'] ?? 'Recommended sponsors' ?></h1>
            <p class="text-muted mb-0"><?= $ui['hub_description'] ?? '' ?></p>
        </div>

        <div class="fcc-sponsors-note">
            <strong class="fcc-sponsors-note__title"><?= $ui['hub_note_title'] ?? '' ?></strong>
            <div class="fcc-sponsors-note__list">
                <?php foreach(($ui['hub_note_points'] ?? []) as $point): ?>
                    <div class="fcc-sponsors-note__item">
                        <span class="fcc-sponsors-note__bullet" aria-hidden="true"></span>
                        <span><?= $point ?></span>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </section>

    <?php if(empty($data->sponsors)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body py-4 text-center text-muted">
                <?= $ui['hub_empty'] ?? '' ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach($data->sponsors as $sponsor): ?>
                <div class="col-12 col-lg-6 mb-4">
                    <article class="fcc-sponsor-card card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <img
                                    src="<?= $sponsor['display_image_url'] ?: $sponsor['default_image_url'] ?>"
                                    alt="<?= $sponsor['name'] ?>"
                                    class="rounded-circle mr-3 fcc-sponsor-card__avatar"
                                    loading="lazy"
                                    data-default-image="<?= htmlspecialchars($sponsor['default_image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-avatar-image="<?= htmlspecialchars($sponsor['generated_avatar_url'], ENT_QUOTES, 'UTF-8') ?>"
                                    onerror="if(!this.dataset.fallbackStep){this.dataset.fallbackStep='default';this.src=this.dataset.defaultImage;return;}if(this.dataset.fallbackStep==='default'){this.dataset.fallbackStep='avatar';this.src=this.dataset.avatarImage;return;}this.onerror=null;"
                                />
                                <div class="min-width-0">
                                    <div class="font-weight-bold text-truncate"><?= $sponsor['name'] ?></div>
                                    <div class="small text-muted"><?= $ui['hub_use_case_label'] ?? '' ?>: <?= $sponsor['public_use_case'] ?></div>
                                </div>
                            </div>

                            <div class="fcc-sponsor-card__meta mb-3">
                                <?php if(!empty($sponsor['public_market'])): ?>
                                    <span class="fcc-sponsor-pill"><?= $ui['hub_market_label'] ?>: <?= $sponsor['public_market'] ?></span>
                                <?php endif ?>
                                <span class="fcc-sponsor-pill fcc-sponsor-pill--accent"><?= $ui['hub_signal_label'] ?>: <?= nr($sponsor['growth_signal_30d']) ?></span>
                                <span class="fcc-sponsor-pill"><?= $ui['hub_weekly_label'] ?>: <?= nr($sponsor['growth_signal_7d']) ?></span>
                                <span class="fcc-sponsor-pill fcc-sponsor-pill--success"><?= $ui['hub_sales_link_ready'] ?></span>
                            </div>

                            <p class="text-muted mb-3 fcc-sponsor-card__summary"><?= $sponsor['public_summary'] ?></p>

                            <?php if(!empty($sponsor['feature_labels'])): ?>
                                <div class="fcc-sponsor-section mb-3">
                                    <div class="fcc-sponsor-section__label"><?= $ui['profile_features_label'] ?? '' ?></div>
                                    <div class="fcc-sponsor-tags">
                                        <?php foreach(array_slice($sponsor['feature_labels'], 0, 4) as $feature_label): ?>
                                            <span class="fcc-sponsor-tag"><?= $feature_label ?></span>
                                        <?php endforeach ?>
                                    </div>
                                </div>
                            <?php endif ?>

                            <div class="small text-muted mb-3"><?= $ui['hub_footer_note'] ?? '' ?></div>

                            <div class="d-flex flex-column flex-sm-row mt-auto">
                                <a href="<?= $sponsor['profile_url'] ?>" class="btn btn-primary mr-sm-2 mb-2 mb-sm-0">
                                    <?= $ui['hub_profile_cta'] ?? '' ?>
                                </a>
                                <a href="<?= $sponsor['app_url'] ?>" target="_blank" rel="nofollow noopener" class="btn btn-outline-primary">
                                    <?= $ui['hub_app_cta'] ?? '' ?>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<?php ob_start() ?>
<style>
    .fcc-sponsors-page .fcc-sponsors-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.24fr) minmax(320px, 0.9fr);
        gap: 2rem;
        align-items: start;
        padding: 2rem;
        border-radius: 22px;
        background:
            radial-gradient(900px circle at 10% 10%, rgba(79, 227, 255, 0.12), transparent 42%),
            radial-gradient(720px circle at 100% 0%, rgba(104, 232, 188, 0.12), transparent 38%),
            linear-gradient(160deg, rgba(15, 18, 28, 0.96), rgba(10, 12, 20, 0.99));
        border: 1px solid rgba(255, 255, 255, 0.06);
        box-shadow: 0 20px 46px rgba(0, 0, 0, 0.28);
    }

    .fcc-sponsors-page .fcc-sponsors-eyebrow {
        margin-bottom: 0.7rem;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.22em;
        text-transform: uppercase;
        color: rgba(104, 232, 188, 0.9);
    }

    .fcc-sponsors-page .fcc-sponsors-hero__content {
        max-width: 46rem;
        padding: 0.35rem 0.5rem 0.45rem 0;
    }

    .fcc-sponsors-page .fcc-sponsors-hero__content h1 {
        max-width: 15ch;
        margin-bottom: 1.15rem !important;
        font-size: clamp(2.2rem, 4.1vw, 3.65rem);
        line-height: 0.98;
        letter-spacing: -0.035em;
        color: #f5fbff;
        text-wrap: balance;
    }

    .fcc-sponsors-page .fcc-sponsors-hero__content p {
        max-width: 56ch;
        margin-bottom: 0;
        font-size: 1.02rem;
        line-height: 1.78;
        color: rgba(221, 231, 241, 0.78) !important;
    }

    .fcc-sponsors-page .fcc-sponsors-note {
        align-self: start;
        padding: 1.25rem 1.2rem;
        border-radius: 18px;
        background: rgba(104, 232, 188, 0.08);
        border: 1px solid rgba(104, 232, 188, 0.14);
        color: rgba(238, 245, 252, 0.88);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .fcc-sponsors-page .fcc-sponsors-note__title {
        display: block;
        margin-bottom: 0.95rem;
        font-size: 1.02rem;
        line-height: 1.35;
        color: #f5fbff;
    }

    .fcc-sponsors-page .fcc-sponsors-note__list {
        display: grid;
        gap: 0.85rem;
    }

    .fcc-sponsors-page .fcc-sponsors-note__item {
        display: grid;
        grid-template-columns: 0.7rem minmax(0, 1fr);
        gap: 0.7rem;
        align-items: start;
        padding-top: 0.05rem;
        font-size: 0.99rem;
        line-height: 1.65;
    }

    .fcc-sponsors-page .fcc-sponsors-note__item + .fcc-sponsors-note__item {
        padding-top: 0.95rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .fcc-sponsors-page .fcc-sponsors-note__bullet {
        width: 0.5rem;
        height: 0.5rem;
        margin-top: 0.45rem;
        border-radius: 999px;
        background: linear-gradient(135deg, rgba(120, 255, 211, 0.95), rgba(79, 227, 255, 0.92));
        box-shadow: 0 0 0 4px rgba(104, 232, 188, 0.1);
    }

    .fcc-sponsor-card {
        background: linear-gradient(160deg, rgba(18, 24, 34, 0.96), rgba(10, 14, 20, 0.98));
        color: #f5f7ff;
        border-radius: 20px;
        overflow: hidden;
    }

    .fcc-sponsor-card .card-body {
        min-width: 0;
    }

    .fcc-sponsor-card__avatar {
        width: 62px;
        height: 62px;
        object-fit: cover;
    }

    .fcc-sponsor-card__summary {
        line-height: 1.55;
        overflow-wrap: anywhere;
    }

    .fcc-sponsor-card__meta,
    .fcc-sponsor-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }

    .fcc-sponsor-pill,
    .fcc-sponsor-tag {
        display: inline-flex;
        align-items: center;
        min-height: 1.8rem;
        padding: 0.28rem 0.62rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.05);
        color: rgba(240, 244, 251, 0.9);
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .fcc-sponsor-pill--accent {
        background: rgba(255, 198, 0, 0.12);
        color: #ffe7a1;
    }

    .fcc-sponsor-pill--success,
    .fcc-sponsor-tag {
        background: rgba(104, 232, 188, 0.12);
        color: #c9fff2;
    }

    .fcc-sponsor-section__label {
        margin-bottom: 0.45rem;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.55);
    }

    @media (max-width: 991px) {
        .fcc-sponsors-page .fcc-sponsors-hero {
            grid-template-columns: 1fr;
            gap: 1.25rem;
            padding: 1.35rem;
        }

        .fcc-sponsors-page .fcc-sponsors-hero__content {
            max-width: none;
            padding-right: 0;
        }

        .fcc-sponsors-page .fcc-sponsors-hero__content h1,
        .fcc-sponsors-page .fcc-sponsors-hero__content p {
            max-width: none;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php ob_start() ?>
<script type="application/ld+json">
<?php
$item_list = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $ui['hub_title'] ?? 'Recommended FCC Sponsors',
    'description' => $ui['hub_description'] ?? '',
    'url' => $data->hub_url ?? url('recommended-sponsors'),
    'mainEntity' => [
        '@type' => 'ItemList',
        'itemListElement' => [],
    ],
];

foreach(($data->sponsors ?? []) as $index => $sponsor) {
    $item_list['mainEntity']['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => $sponsor['profile_url'],
        'name' => $sponsor['name'],
    ];
}
?>
<?= json_encode($item_list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
