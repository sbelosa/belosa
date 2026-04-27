<?php defined('ALTUMCODE') || die() ?>

<?php $ui = $data->ui ?? []; ?>
<?php $sponsor = $data->sponsor ?? []; ?>
<?php $alternate_urls = is_array($data->alternate_urls ?? null) ? $data->alternate_urls : []; ?>

<?php if($alternate_urls): ?>
    <?php ob_start() ?>
    <?php foreach($alternate_urls as $hreflang => $href): ?>
        <link rel="alternate" hreflang="<?= e($hreflang) ?>" href="<?= e($href) ?>" />
    <?php endforeach ?>
    <?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
<?php endif ?>

<div class="container my-5 fcc-sponsor-profile-page">
    <a href="<?= $data->hub_url ?? url('recommended-sponsors') ?>" class="fcc-sponsor-profile__back">
        <i class="fas fa-fw fa-arrow-left mr-1"></i> <?= $ui['profile_back'] ?? '' ?>
    </a>

    <section class="fcc-sponsor-profile card border-0 shadow-sm mt-3">
        <div class="card-body p-4 p-lg-5">
            <div class="fcc-sponsor-profile__hero">
                <div class="fcc-sponsor-profile__identity">
                    <img
                        src="<?= $sponsor['display_image_url'] ?: $sponsor['default_image_url'] ?>"
                        alt="<?= $sponsor['name'] ?>"
                        class="fcc-sponsor-profile__avatar"
                        loading="lazy"
                    />

                    <div class="min-width-0">
                        <div class="fcc-sponsor-profile__eyebrow"><?= $ui['hub_title'] ?? '' ?></div>
                        <h1 class="mb-2"><?= $sponsor['name'] ?></h1>

                        <div class="fcc-sponsor-profile__meta">
                            <?php if(!empty($sponsor['public_market'])): ?>
                                <span class="fcc-sponsor-profile__pill"><?= $ui['profile_market_label'] ?>: <?= $sponsor['public_market'] ?></span>
                            <?php endif ?>
                            <span class="fcc-sponsor-profile__pill fcc-sponsor-profile__pill--accent"><?= $ui['profile_signal_label'] ?>: <?= nr($sponsor['growth_signal_30d']) ?></span>
                            <span class="fcc-sponsor-profile__pill"><?= $ui['profile_weekly_label'] ?>: <?= nr($sponsor['growth_signal_7d']) ?></span>
                        </div>

                        <?php if(!empty($sponsor['public_use_case'])): ?>
                            <p class="fcc-sponsor-profile__use-case mb-0">
                                <strong><?= $ui['profile_use_case_label'] ?>:</strong> <?= $sponsor['public_use_case'] ?>
                            </p>
                        <?php endif ?>

                        <?php if(!empty($sponsor['profile_intro'])): ?>
                            <p class="fcc-sponsor-profile__intro mb-0"><?= $sponsor['profile_intro'] ?></p>
                        <?php endif ?>
                    </div>
                </div>

                <div class="fcc-sponsor-profile__actions">
                    <a href="<?= $sponsor['app_url'] ?>" target="_blank" rel="nofollow noopener" class="btn btn-primary mb-2">
                        <?= $ui['profile_app_cta'] ?? '' ?>
                    </a>
                    <a href="<?= $data->hub_url ?? url('recommended-sponsors') ?>" class="btn btn-outline-primary">
                        <?= $ui['profile_hub_cta'] ?? '' ?>
                    </a>
                </div>
            </div>

            <div class="fcc-sponsor-profile__grid mt-4">
                <section class="fcc-sponsor-profile__card">
                    <div class="fcc-sponsor-profile__card-label"><?= $ui['profile_strength_title'] ?? '' ?></div>

                    <?php foreach(($ui['profile_strength_points'] ?? []) as $point): ?>
                        <div class="fcc-sponsor-profile__strength"><?= $point ?></div>
                    <?php endforeach ?>
                </section>

                <section class="fcc-sponsor-profile__card">
                    <div class="fcc-sponsor-profile__card-label"><?= $ui['profile_summary_label'] ?? '' ?></div>
                    <p class="mb-0 text-muted"><?= $sponsor['public_summary'] ?></p>
                </section>

                <?php if(!empty($sponsor['feature_labels'])): ?>
                    <section class="fcc-sponsor-profile__card">
                        <div class="fcc-sponsor-profile__card-label"><?= $ui['profile_features_label'] ?? '' ?></div>
                        <div class="fcc-sponsor-profile__tags">
                            <?php foreach($sponsor['feature_labels'] as $feature_label): ?>
                                <span class="fcc-sponsor-profile__tag"><?= $feature_label ?></span>
                            <?php endforeach ?>
                        </div>
                    </section>
                <?php endif ?>
            </div>
        </div>
    </section>

    <?php if(!empty($data->related_sponsors)): ?>
        <section class="mt-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0"><?= $ui['profile_related_title'] ?? '' ?></h2>
                <a href="<?= $data->hub_url ?? url('recommended-sponsors') ?>" class="small"><?= $ui['profile_back'] ?? '' ?></a>
            </div>

            <div class="row">
                <?php foreach($data->related_sponsors as $related): ?>
                    <div class="col-12 col-lg-4 mb-4">
                        <article class="fcc-sponsor-related card border-0 shadow-sm h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?= $related['display_image_url'] ?: $related['default_image_url'] ?>" alt="<?= $related['name'] ?>" class="fcc-sponsor-related__avatar rounded-circle mr-3" loading="lazy" />
                                    <div class="min-width-0">
                                        <div class="font-weight-bold text-truncate"><?= $related['name'] ?></div>
                                        <div class="small text-muted text-truncate"><?= $related['public_use_case'] ?></div>
                                    </div>
                                </div>

                                <div class="fcc-sponsor-related__meta mb-3">
                                    <span class="fcc-sponsor-profile__pill fcc-sponsor-profile__pill--accent"><?= $ui['profile_signal_label'] ?>: <?= nr($related['growth_signal_30d']) ?></span>
                                    <span class="fcc-sponsor-profile__pill"><?= $ui['profile_weekly_label'] ?>: <?= nr($related['growth_signal_7d']) ?></span>
                                </div>

                                <p class="small text-muted mb-3"><?= $related['public_summary'] ?></p>

                                <a href="<?= $related['profile_url'] ?>" class="btn btn-outline-primary mt-auto"><?= $ui['hub_profile_cta'] ?? '' ?></a>
                            </div>
                        </article>
                    </div>
                <?php endforeach ?>
            </div>
        </section>
    <?php endif ?>
</div>

<?php ob_start() ?>
<style>
    .fcc-sponsor-profile__back {
        color: rgba(201, 255, 242, 0.88);
    }

    .fcc-sponsor-profile {
        background:
            radial-gradient(900px circle at 10% 10%, rgba(79, 227, 255, 0.11), transparent 40%),
            radial-gradient(720px circle at 100% 0%, rgba(104, 232, 188, 0.12), transparent 36%),
            linear-gradient(160deg, rgba(15, 18, 28, 0.96), rgba(10, 12, 20, 0.99));
        color: #f5f7ff;
        border-radius: 24px;
    }

    .fcc-sponsor-profile__hero {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(220px, 0.6fr);
        gap: 1.5rem;
        align-items: start;
    }

    .fcc-sponsor-profile__identity {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        min-width: 0;
    }

    .fcc-sponsor-profile__avatar {
        width: 88px;
        height: 88px;
        object-fit: cover;
        border-radius: 22px;
        box-shadow: 0 16px 32px rgba(0, 0, 0, 0.24);
    }

    .fcc-sponsor-profile__eyebrow,
    .fcc-sponsor-profile__card-label {
        margin-bottom: 0.65rem;
        font-size: 0.76rem;
        font-weight: 700;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: rgba(104, 232, 188, 0.86);
    }

    .fcc-sponsor-profile__meta,
    .fcc-sponsor-related__meta,
    .fcc-sponsor-profile__tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }

    .fcc-sponsor-profile__pill,
    .fcc-sponsor-profile__tag {
        display: inline-flex;
        align-items: center;
        min-height: 1.85rem;
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

    .fcc-sponsor-profile__pill--accent {
        background: rgba(255, 198, 0, 0.12);
        color: #ffe7a1;
    }

    .fcc-sponsor-profile__tag {
        background: rgba(104, 232, 188, 0.12);
        color: #c9fff2;
    }

    .fcc-sponsor-profile__use-case {
        margin-top: 0.85rem;
        color: rgba(238, 245, 252, 0.88);
        line-height: 1.55;
    }

    .fcc-sponsor-profile__intro {
        max-width: 60ch;
        margin-top: 1rem;
        color: rgba(221, 231, 241, 0.82);
        line-height: 1.72;
    }

    .fcc-sponsor-profile__actions {
        display: flex;
        flex-direction: column;
    }

    .fcc-sponsor-profile__grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .fcc-sponsor-profile__card,
    .fcc-sponsor-related {
        padding: 1.1rem 1.15rem;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.035);
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .fcc-sponsor-profile__strength {
        color: rgba(238, 245, 252, 0.9);
        line-height: 1.55;
    }

    .fcc-sponsor-profile__strength + .fcc-sponsor-profile__strength {
        margin-top: 0.65rem;
        padding-top: 0.65rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    .fcc-sponsor-related {
        background: linear-gradient(160deg, rgba(18, 24, 34, 0.96), rgba(10, 14, 20, 0.98));
        color: #f5f7ff;
    }

    .fcc-sponsor-related__avatar {
        width: 52px;
        height: 52px;
        object-fit: cover;
    }

    @media (max-width: 991px) {
        .fcc-sponsor-profile__hero,
        .fcc-sponsor-profile__grid {
            grid-template-columns: 1fr;
        }

        .fcc-sponsor-profile__actions {
            flex-direction: row;
            flex-wrap: wrap;
            gap: 0.75rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php ob_start() ?>
<script type="application/ld+json">
<?php
$profile_schema = [
    '@context' => 'https://schema.org',
    '@type' => 'ProfilePage',
    'name' => sprintf($ui['profile_title_format'] ?? '%s', $sponsor['name'] ?? ''),
    'description' => trim((string) ($sponsor['public_summary'] ?? '')) ?: ($ui['profile_description_fallback'] ?? ''),
    'url' => $sponsor['profile_url'] ?? '',
    'inLanguage' => \Altum\Language::$code,
    'mainEntity' => [
        '@type' => 'Person',
        'name' => $sponsor['name'] ?? '',
        'description' => trim((string) ($sponsor['public_summary'] ?? '')) ?: ($ui['profile_description_fallback'] ?? ''),
        'url' => $sponsor['profile_url'] ?? '',
        'image' => $sponsor['display_image_url'] ?: ($sponsor['default_image_url'] ?? ''),
        'worksFor' => [
            '@type' => 'Organization',
            'name' => 'Forever Card Club',
            'url' => url(),
        ],
        'affiliation' => [
            '@type' => 'Organization',
            'name' => 'Forever Card Club',
            'url' => url(),
        ],
        'knowsAbout' => array_values(array_filter(array_merge(
            !empty($sponsor['public_use_case']) ? [$sponsor['public_use_case']] : [],
            $sponsor['feature_labels'] ?? []
        ))),
    ],
    'breadcrumb' => [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => $ui['hub_title'] ?? 'Recommended FCC Sponsors',
                'item' => $data->hub_url ?? url('recommended-sponsors'),
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => $sponsor['name'] ?? '',
                'item' => $sponsor['profile_url'] ?? '',
            ],
        ],
    ],
];
?>
<?= json_encode($profile_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
