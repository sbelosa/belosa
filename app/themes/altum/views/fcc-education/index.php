<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-02-24: FCC core education page -->
<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php
    /* Custom code: FC-2026-02-24: FCC education settings */
    $education = $data->education_settings ?? null;
    $education = is_array($education) || is_object($education) ? (object) $education : null;
    $education_title = $education->title ?? l('fcc_education.title');
    $education_subtitle = $education->subtitle ?? l('fcc_education.subtitle');
    $education_cta_label = $education->cta_label ?? l('fcc_education.cta_complete');
    $education_videos = $data->education_videos ?? [];
    $education_videos = is_array($education_videos) || is_object($education_videos) ? (array) $education_videos : [];
    $video_count = $data->video_count ?? 0;
    $current_index = $data->current_index ?? 0;
    $current_video = $data->current_video ?? null;
    $current_video = is_array($current_video) || is_object($current_video) ? (object) $current_video : null;

    /* Custom code: FC-2026-02-27: FCC education premium labels */
    $fcc_is_hr_language = \Altum\Language::$code === 'hr';
    $fcc_education_eyebrow = $fcc_is_hr_language ? 'Forever Edukacija' : 'Forever Education';
    $fcc_education_stats_label_primary = $fcc_is_hr_language ? 'Koraci programa' : 'Program steps';
    $fcc_education_stats_label_secondary = $fcc_is_hr_language ? 'Trenutna lekcija' : 'Current lesson';
    $fcc_education_total_steps = (int) $video_count;
    $fcc_education_current_step = $video_count > 0 ? ((int) $current_index + 1) : 0;

    $fcc_sidebar_sections = $fcc_is_hr_language
        ? [
            [
                'title' => 'Forever pozicije',
                'items' => [
                    'Assistant Supervisor – 2 CC – 35% rab.',
                    'Supervisor – 10 CC (1 mj.) – 38% rab.',
                    'Assistant Manager – 60 CC (2 mj.) – 43% rab.',
                    'Manager – 120 CC (2 mj.) / 150 CC (4 mj.) – 48% rab.',
                ],
            ],
            [
                'title' => 'Službena Forever Living Products stranica',
                'link' => [
                    'label' => 'foreverliving.com',
                    'url' => 'https://foreverliving.com',
                ],
            ],
            [
                'title' => 'Auto program (36 mjeseci)',
                'items' => [
                    '400 € mjesečno',
                    '50 CC → 100 CC → 150 CC',
                    '',
                    '600 € mjesečno',
                    '75 CC → 150 CC → 225 CC',
                    '',
                    '800 € mjesečno',
                    '100 CC → 200 CC → 300 CC',
                ],
            ],
            [
                'title' => '',
                'columns' => [
                    [
                        'title' => 'Chairman’s Bonus',
                        'items' => [
                            '700 non-manager CC',
                            '150 novoupisani',
                            'Aktivan Auto program',
                            'Manager s 600 CC ukupno',
                        ],
                    ],
                    [
                        'title' => 'Eagle Manager',
                        'items' => [
                            '720 CC ukupno',
                            '100 CC novoupisani',
                            '3 Supervisora',
                        ],
                    ],
                ],
            ],
        ]
        : [
            [
                'title' => 'Forever positions',
                'items' => [
                    'Assistant Supervisor – 2 CC – 35% disc.',
                    'Supervisor – 10 CC (1 month) – 38% disc.',
                    'Assistant Manager – 60 CC (2 months) – 43% disc.',
                    'Manager – 120 CC (2 months) / 150 CC (4 months) – 48% disc.',
                ],
            ],
            [
                'title' => 'Official Forever Living Products website',
                'link' => [
                    'label' => 'foreverliving.com',
                    'url' => 'https://foreverliving.com',
                ],
            ],
            [
                'title' => 'Auto program (36 months)',
                'items' => [
                    '€400 monthly',
                    '50 CC → 100 CC → 150 CC',
                    '',
                    '€600 monthly',
                    '75 CC → 150 CC → 225 CC',
                    '',
                    '€800 monthly',
                    '100 CC → 200 CC → 300 CC',
                ],
            ],
            [
                'title' => '',
                'columns' => [
                    [
                        'title' => 'Chairman’s Bonus',
                        'items' => [
                            '700 non-manager CC',
                            '150 newly enrolled',
                            'Active Auto program',
                            'Manager with 600 CC total',
                        ],
                    ],
                    [
                        'title' => 'Eagle Manager',
                        'items' => [
                            '720 CC total',
                            '100 newly enrolled',
                            '3 Supervisors',
                        ],
                    ],
                ],
            ],
        ];
    /* /Custom code: FC-2026-02-27 */
    /* /Custom code: FC-2026-02-24 */
    ?>

    <!-- Custom code: FC-2026-02-27: FCC education premium hero -->
    <section class="fcc-education-hero mb-4">
        <div class="fcc-education-hero__text">
            <div class="fcc-education-eyebrow"><?= $fcc_education_eyebrow ?></div>
            <h1 class="fcc-education-title"><?= $education_title ?></h1>
            <p class="fcc-education-subtitle mb-0"><?= $education_subtitle ?></p>
        </div>

        <div class="fcc-education-hero__meta">
            <div class="fcc-education-stat">
                <div class="fcc-education-stat__value"><?= nr($fcc_education_total_steps) ?></div>
                <div class="fcc-education-stat__label"><?= $fcc_education_stats_label_primary ?></div>
            </div>
            <div class="fcc-education-stat">
                <div class="fcc-education-stat__value"><?= nr($fcc_education_current_step) ?></div>
                <div class="fcc-education-stat__label"><?= $fcc_education_stats_label_secondary ?></div>
            </div>
        </div>
    </section>
    <!-- /Custom code: FC-2026-02-27 -->

    <div class="row">
        <div class="col-12 col-lg-7 mb-4 mb-lg-0">
            <div class="card fcc-education-card">
                <div class="card-body">
                    <div class="mb-4">
                        <?php if($video_count > 0 && $current_video && !empty($current_video->vimeo_id)): ?>
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="font-weight-bold fcc-education-progress">
                                    <?= sprintf(l('fcc_education.progress_label'), ($current_index + 1), $video_count) ?>
                                </div>
                            </div>
                            <?php if(!empty($current_video->title)): ?>
                                <?php
                                $current_video_title = trim($current_video->title);
                                if(mb_strtolower($current_video_title) === 'video') {
                                    $current_video_title = $fcc_is_hr_language ? 'Forever Card Club video edukacija' : 'Forever Card Club video education';
                                }
                                ?>
                                <div class="font-weight-bold mb-3 fcc-education-video-title\"><?= $current_video_title ?></div>
                            <?php endif ?>
                            <div class="embed-responsive embed-responsive-16by9 mb-3 fcc-education-player-wrap">
                                <iframe
                                    class="embed-responsive-item"
                                    id="fcc-education-vimeo"
                                    src="https://player.vimeo.com/video/<?= $current_video->vimeo_id ?>"
                                    title="<?= $current_video->title ?? 'Vimeo video' ?>"
                                    frameborder="0"
                                    allow="autoplay; fullscreen; picture-in-picture"
                                    allowfullscreen
                                ></iframe>
                            </div>
                        <?php else: ?>
                            <div class="fcc-education-empty"><?= l('fcc_education.videos_html') ?></div>
                        <?php endif ?>
                    </div>

                    <?php if(!$data->is_completed): ?>
                        <form method="post" action="<?= url('fcc-education') ?>">
                            <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                            <input type="hidden" name="video_index" value="<?= (int) $current_index ?>" />
                            <button type="submit" class="btn btn-primary fcc-education-btn" id="fcc-education-next">
                                <?= $education_cta_label ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-success mb-3 fcc-education-success">
                            <?= l('fcc_education.completed_success') ?>
                        </div>
                        <?php if($video_count > 0): ?>
                            <!-- Custom code: FC-2026-02-25: review education videos -->
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <?php $base_url = url('fcc-education'); ?>
                                <?php $prev_index = $current_index > 0 ? $current_index - 1 : 0; ?>
                                <?php $next_index = $current_index < ($video_count - 1) ? $current_index + 1 : ($video_count - 1); ?>
                                <a class="btn btn-sm btn-light fcc-education-nav-btn" href="<?= $base_url . '?video=' . $prev_index ?>">&larr; <?= l('fcc_education.nav_previous') ?></a>
                                <a class="btn btn-sm btn-light fcc-education-nav-btn" href="<?= $base_url . '?video=' . $next_index ?>"><?= l('fcc_education.nav_next') ?> &rarr;</a>
                            </div>
                            <!-- /Custom code: FC-2026-02-25 -->
                        <?php endif ?>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card fcc-education-card h-100">
                <div class="card-body fcc-education-sidebar-body">
                    <h2 class="h6 mb-2 fcc-education-sidebar-title">Važne informacije</h2>

                    <div class="fcc-education-sidebar-content">
                        <?php foreach($fcc_sidebar_sections as $section): ?>
                            <section class="fcc-education-sidebar-section">
                                <?php if(!empty($section['title'])): ?>
                                    <div class="fcc-education-sidebar-heading"><?= $section['title'] ?></div>
                                <?php endif ?>

                                <?php if(!empty($section['columns'])): ?>
                                    <div class="fcc-education-sidebar-columns">
                                        <?php foreach($section['columns'] as $column): ?>
                                            <div class="fcc-education-sidebar-column">
                                                <div class="fcc-education-sidebar-heading"><?= $column['title'] ?></div>
                                                <ul class="fcc-education-sidebar-list mb-0">
                                                    <?php foreach(($column['items'] ?? []) as $item): ?>
                                                        <li><?= $item ?></li>
                                                    <?php endforeach ?>
                                                </ul>
                                            </div>
                                        <?php endforeach ?>
                                    </div>
                                    <?php continue; ?>
                                <?php endif ?>

                                <?php if(!empty($section['link'])): ?>
                                    <a href="<?= $section['link']['url'] ?>" class="fcc-education-sidebar-link" target="_blank" rel="noopener noreferrer"><?= $section['link']['label'] ?></a>
                                <?php endif ?>

                                <?php if(!empty($section['items'])): ?>
                                    <ul class="fcc-education-sidebar-list mb-0">
                                        <?php foreach($section['items'] as $item): ?>
                                            <?php if($item === ''): ?>
                                                <li class="fcc-education-sidebar-list-spacer" aria-hidden="true"></li>
                                            <?php else: ?>
                                                <?php
                                                $is_long_manager_line = str_starts_with($item, 'Manager – 120 CC');
                                                $item_class = $is_long_manager_line ? 'fcc-education-manager-line' : '';
                                                ?>
                                                <li class="<?= $item_class ?>\"><?= $item ?></li>
                                            <?php endif ?>
                                        <?php endforeach ?>
                                    </ul>
                                <?php endif ?>
                            </section>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-02-24 -->

<?php ob_start() ?>
<!-- Custom code: FC-2026-02-27: FCC education premium styles -->
<style>
    .fcc-education-hero {
        background: radial-gradient(1200px circle at 10% 20%, rgba(79, 227, 255, 0.12), transparent 45%),
            radial-gradient(900px circle at 90% 10%, rgba(255, 198, 0, 0.1), transparent 40%),
            linear-gradient(180deg, rgba(13, 18, 28, 0.9), rgba(10, 12, 20, 0.9));
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 22px;
        padding: 28px 32px;
        display: flex;
        align-items: stretch;
        justify-content: space-between;
        gap: 24px;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45);
    }

    .fcc-education-hero__text {
        max-width: 640px;
    }

    .fcc-education-eyebrow {
        text-transform: uppercase;
        letter-spacing: 0.24em;
        font-size: 0.7rem;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 12px;
    }

    .fcc-education-title {
        font-size: clamp(2rem, 3vw, 2.6rem);
        font-weight: 600;
        color: #f5f7ff;
        margin-bottom: 12px;
    }

    .fcc-education-subtitle {
        font-size: 1rem;
        color: rgba(219, 225, 238, 0.78);
        line-height: 1.6;
    }

    .fcc-education-hero__meta {
        display: grid;
        grid-auto-rows: minmax(0, auto);
        gap: 12px;
        min-width: 190px;
    }

    .fcc-education-stat {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 16px;
        padding: 16px;
    }

    .fcc-education-stat__value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 6px;
    }

    .fcc-education-stat__label {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.6);
    }

    .fcc-education-card {
        background: linear-gradient(160deg, rgba(22, 28, 40, 0.95), rgba(12, 16, 24, 0.98));
        border: 1px solid rgba(255, 255, 255, 0.06);
        border-radius: 18px;
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.35);
    }

    .fcc-education-progress,
    .fcc-education-video-title,
    .fcc-education-sidebar-title {
        color: #f3f6ff;
    }

    .fcc-education-sidebar-text {
        color: rgba(210, 218, 234, 0.78) !important;
        line-height: 1.6;
    }

    .fcc-education-sidebar-content {
        color: rgba(210, 218, 234, 0.88);
        line-height: 1.42;
        font-size: 0.95rem;
    }

    .fcc-education-sidebar-body {
        padding: 1rem 1.05rem;
    }

    .fcc-education-sidebar-section {
        padding-bottom: 14px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .fcc-education-sidebar-section + .fcc-education-sidebar-section {
        margin-top: 14px;
    }

    .fcc-education-sidebar-section:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .fcc-education-sidebar-heading {
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .fcc-education-sidebar-list {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }

    .fcc-education-sidebar-list li {
        margin-bottom: 4px;
        font-size: 0.92rem;
    }

    .fcc-education-sidebar-list li:last-child {
        margin-bottom: 0;
    }

    .fcc-education-sidebar-columns {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
    }

    .fcc-education-sidebar-column + .fcc-education-sidebar-column {
        border-left: 1px solid rgba(255, 255, 255, 0.1);
        padding-left: 12px;
    }

    .fcc-education-sidebar-column .fcc-education-sidebar-heading {
        margin-bottom: 8px;
    }

    .fcc-education-sidebar-list-spacer {
        height: 6px;
        margin-bottom: 0 !important;
    }

    .fcc-education-sidebar-link {
        color: rgba(210, 218, 234, 0.95);
        text-decoration: underline;
        text-underline-offset: 2px;
        font-size: 0.92rem;
    }

    .fcc-education-sidebar-link:hover {
        color: #fff;
    }

    .fcc-education-player-wrap {
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.4);
    }

    .fcc-education-btn {
        border-radius: 12px;
        padding: 0.65rem 1.1rem;
    }

    .fcc-education-nav-btn {
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.04);
        color: #f3f6ff;
    }

    .fcc-education-nav-btn:hover {
        background: rgba(255, 198, 0, 0.14);
        color: #fff;
    }

    .fcc-education-success {
        border-radius: 12px;
    }

    .fcc-education-empty {
        color: rgba(210, 218, 234, 0.82);
    }

    @media (max-width: 991.98px) {
        .fcc-education-hero {
            padding: 24px;
            flex-direction: column;
        }

        .fcc-education-hero__meta {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            min-width: 0;
        }
    }

    @media (min-width: 992px) {
        .fcc-education-sidebar-body {
            padding: 0.85rem 0.85rem;
        }

        .fcc-education-sidebar-content {
            font-size: 0.91rem;
            line-height: 1.36;
        }

        .fcc-education-sidebar-title {
            margin-bottom: 0.45rem !important;
        }

        .fcc-education-sidebar-heading {
            font-size: 0.9rem;
            margin-bottom: 7px;
        }

        .fcc-education-sidebar-list li,
        .fcc-education-sidebar-link {
            font-size: 0.88rem;
        }

        .fcc-education-manager-line {
            font-size: 0.88rem !important;
            white-space: nowrap;
            line-height: 1.36;
            letter-spacing: normal;
        }

        .fcc-education-sidebar-section {
            padding-bottom: 12px;
        }

        .fcc-education-sidebar-section + .fcc-education-sidebar-section {
            margin-top: 12px;
        }

        .fcc-education-sidebar-list-spacer {
            height: 5px;
        }
    }

    @media (max-width: 575.98px) {
        .fcc-education-hero {
            padding: 20px;
            border-radius: 18px;
        }

        .fcc-education-hero__meta {
            grid-template-columns: 1fr;
        }

        .fcc-education-sidebar-columns {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        .fcc-education-sidebar-column + .fcc-education-sidebar-column {
            border-left: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-left: 0;
            padding-top: 10px;
        }
    }
</style>
<!-- /Custom code: FC-2026-02-27 -->
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
