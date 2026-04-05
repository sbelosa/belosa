<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_stats_language_name = fc_resolve_language_name(\Altum\Language::$name ?? '');
$fcc_stats_language_code = mb_strtolower((string) (\Altum\Language::$code ?? ''));
$fcc_stats_is_hr = in_array(mb_strtolower((string) $fcc_stats_language_name), ['hrvatski', 'croatian', 'hr'], true) || str_starts_with($fcc_stats_language_code, 'hr');
$fcc_stats_is_main = !empty($data->is_main_biolink_app);
$fcc_stats_is_biolink = $data->link->type === 'biolink';

$fcc_stats_guide_label = $fcc_stats_is_hr ? 'Pokreni tutorijal' : 'Start tutorial';
$fcc_stats_extra_title = $fcc_stats_is_hr ? 'Dodatna FCC aplikacija' : 'Additional FCC app';
$fcc_stats_extra_text = $fcc_stats_is_hr
    ? 'Ovdje pratiš statistiku ove dodatne FCC aplikacije. AI analiza, javni prikaz i rast unutar FCC sustava ostaju vezani uz glavnu FCC aplikaciju.'
    : 'Here you are tracking the statistics of this additional FCC app. AI analysis, public showcase and growth inside FCC remain tied to the main FCC app.';
$fcc_stats_extra_cta = $fcc_stats_is_hr ? 'Otvori glavnu aplikaciju' : 'Open main app';

$fcc_tour_main_steps = [
    [
        'selector' => '#fcc_app_stats_tour_step_ai_block',
        'title' => $fcc_stats_is_hr ? 'AI analiza glavne aplikacije' : 'AI analysis for the main app',
        'text' => $fcc_stats_is_hr
            ? 'Ovdje vidiš kako glavna aplikacija trenutno stoji, što treba doraditi i gdje pokrećeš AI analizu povezanu s Tvojim planom rasta.'
            : 'Here you see how the main app is performing, what needs work and where you launch the AI analysis connected to your Growth Plan.',
    ],
    [
        'selector' => '#fcc_app_stats_tour_step_nav',
        'title' => $fcc_stats_is_hr ? 'Pregledi statistike' : 'Statistics views',
        'text' => $fcc_stats_is_hr
            ? 'Ovdje biraš pregled statistike, unose, izvore, države, uređaje, UTM-ove i ostale detalje za ovu aplikaciju.'
            : 'Here you choose overview, entries, sources, countries, devices, UTMs and other details for this app.',
    ],
    [
        'selector' => '#fcc_app_stats_tour_step_overview',
        'title' => $fcc_stats_is_hr ? 'Glavni rezultat aplikacije' : 'Main app result',
        'text' => $fcc_stats_is_hr
            ? 'U ovom dijelu pratiš ključne brojke i kretanje interesa za odabranu aplikaciju kroz odabrano razdoblje.'
            : 'In this section you track the key numbers and the movement of interest for the selected app across the chosen date range.',
    ],
    [
        'selector' => '#fcc_app_stats_tour_step_latest',
        'title' => $fcc_stats_is_hr ? 'Zadnje posjete i izvori' : 'Latest visits and sources',
        'text' => $fcc_stats_is_hr
            ? 'Ovdje vidiš zadnje posjete, odakle su došle i kada su se dogodile. To je korisno za praćenje kampanja i ponašanja posjetitelja.'
            : 'Here you see the latest visits, where they came from and when they happened. This is useful for tracking campaigns and visitor behaviour.',
    ],
];

$fcc_tour_extra_steps = [
    [
        'selector' => '#fcc_app_stats_tour_step_extra_info',
        'title' => $fcc_stats_is_hr ? 'Statistika dodatne aplikacije' : 'Additional app statistics',
        'text' => $fcc_stats_is_hr
            ? 'Ovdje pratiš rezultate ove dodatne FCC aplikacije. AI analiza i plan rasta ostaju vezani uz glavnu FCC aplikaciju.'
            : 'Here you track the results of this additional FCC app. AI analysis and the Growth Plan remain tied to the main FCC app.',
    ],
    [
        'selector' => '#fcc_app_stats_tour_step_nav',
        'title' => $fcc_stats_is_hr ? 'Pregledi statistike' : 'Statistics views',
        'text' => $fcc_stats_is_hr
            ? 'Ovdje biraš pregled statistike, unose, izvore, države, uređaje, UTM-ove i ostale detalje za ovu dodatnu aplikaciju.'
            : 'Here you choose overview, entries, sources, countries, devices, UTMs and other details for this additional app.',
    ],
    [
        'selector' => '#fcc_app_stats_tour_step_overview',
        'title' => $fcc_stats_is_hr ? 'Učinak ove aplikacije' : 'Performance of this app',
        'text' => $fcc_stats_is_hr
            ? 'U ovom dijelu pratiš kako se ova dodatna aplikacija ponaša kroz vrijeme i iz kojih izvora dolazi interes.'
            : 'In this section you track how this additional app performs over time and which sources bring interest.',
    ],
    [
        'selector' => '#fcc_app_stats_tour_step_latest',
        'title' => $fcc_stats_is_hr ? 'Zadnje posjete i izvori' : 'Latest visits and sources',
        'text' => $fcc_stats_is_hr
            ? 'Ovdje vidiš zadnje posjete, izvore i vrijeme posjeta za ovu dodatnu aplikaciju.'
            : 'Here you see the latest visits, sources and timestamps for this additional app.',
    ],
];

$fcc_stats_tour_steps = $fcc_stats_is_main ? $fcc_tour_main_steps : $fcc_tour_extra_steps;
$fcc_stats_storage_key = $fcc_stats_is_main ? 'fccTutorialSeenAppStatisticsMain' : 'fccTutorialSeenAppStatisticsExtra';
?>

<?php ob_start() ?>
<style>
    .fcc-app-stats-guide-rail {
        display: flex;
        justify-content: flex-end;
        margin: 0 0 .72rem;
    }

    .fcc-app-stats-guide-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .48rem;
        padding: .68rem .98rem;
        min-height: 2.7rem;
        border-radius: .95rem;
        border: 1px solid rgba(111, 244, 228, .28);
        background: linear-gradient(135deg, rgba(42, 215, 199, .14) 0%, rgba(29, 122, 209, .12) 100%);
        color: #eefdfb;
        font-size: .86rem;
        font-weight: 750;
        line-height: 1.1;
        text-decoration: none !important;
        box-shadow: 0 12px 24px rgba(4, 14, 25, .14), inset 0 1px 0 rgba(255,255,255,.06);
        transition: all .18s ease;
    }

    .fcc-app-stats-guide-trigger i {
        color: #8cf6e9;
        font-size: .92em;
    }

    .fcc-app-stats-guide-trigger:hover,
    .fcc-app-stats-guide-trigger:focus {
        color: #ffffff;
        border-color: rgba(111, 244, 228, .42);
        background: linear-gradient(135deg, rgba(44, 214, 199, .2) 0%, rgba(41, 126, 212, .18) 100%);
        box-shadow: 0 16px 30px rgba(63, 215, 199, .12);
        transform: translateY(-1px);
        outline: none;
    }

    .fcc-app-stats-info-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 1.35rem;
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, 0.09), transparent 24%),
            linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015));
        box-shadow: 0 18px 36px rgba(10, 18, 30, .12);
        margin-bottom: 1.2rem;
    }

    .fcc-app-stats-info-card::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(135deg, rgba(73, 227, 207, .04), transparent 52%);
    }

    .fcc-app-stats-info-card > .card-body {
        position: relative;
        z-index: 1;
    }

    .fcc-app-stats-info-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .4rem .78rem;
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .04em;
        color: #9ef1e7;
        background: rgba(127,227,217,.08);
        border: 1px solid rgba(127,227,217,.18);
        margin-bottom: .9rem;
    }

    .fcc-app-stats-info-title {
        font-size: 1.4rem;
        line-height: 1.15;
        color: #f8fbff;
        margin-bottom: .55rem;
    }

    .fcc-app-stats-info-text {
        color: rgba(226, 232, 240, .8);
        font-size: 1rem;
        line-height: 1.7;
        margin-bottom: 0;
        max-width: 48rem;
    }

    .fcc-app-stats-tour-overlay {
        position: fixed;
        inset: 0;
        background: rgba(2, 8, 20, .72);
        backdrop-filter: blur(4px);
        z-index: 3000;
        display: none;
    }

    .fcc-app-stats-tour-overlay.is-active {
        display: block;
    }

    .fcc-app-stats-tour-highlight {
        position: relative;
        z-index: 3002 !important;
        border-radius: 1.4rem;
        box-shadow: 0 0 0 3px rgba(82, 242, 223, .85), 0 0 0 16px rgba(82, 242, 223, .08);
    }

    .fcc-app-stats-tour-card {
        position: fixed;
        width: min(27rem, calc(100vw - 2rem));
        padding: 1.15rem 1.15rem 1rem;
        border-radius: 1.3rem;
        border: 1px solid rgba(111, 244, 228, .22);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .12), transparent 28%),
            linear-gradient(180deg, rgba(20, 31, 50, .96), rgba(12, 18, 32, .98));
        box-shadow: 0 20px 45px rgba(0, 0, 0, .34);
        color: #f7fbff;
        z-index: 3003;
        display: none;
        left: 1rem;
        bottom: 1rem;
    }

    .fcc-app-stats-tour-card.is-active {
        display: block;
    }

    .fcc-app-stats-tour-step {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .25rem .55rem;
        font-size: .8rem;
        font-weight: 800;
        color: #dffcf7;
        background: rgba(111, 244, 228, .14);
        border: 1px solid rgba(111, 244, 228, .22);
        margin-bottom: .85rem;
    }

    .fcc-app-stats-tour-card h3 {
        font-size: 1.45rem;
        line-height: 1.15;
        margin-bottom: .65rem;
        color: #ffffff;
    }

    .fcc-app-stats-tour-card p {
        color: rgba(226, 232, 240, .88);
        font-size: 1rem;
        line-height: 1.7;
        margin-bottom: 1.1rem;
    }

    .fcc-app-stats-tour-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
    }

    .fcc-app-stats-tour-dismiss {
        border: 0;
        background: transparent;
        color: rgba(226, 232, 240, .72);
        font-size: 1rem;
        font-weight: 700;
        padding: 0;
    }

    .fcc-app-stats-tour-nav {
        display: inline-flex;
        align-items: center;
        gap: .65rem;
    }

    .fcc-app-stats-tour-btn {
        border: 0;
        border-radius: .95rem;
        min-height: 2.8rem;
        padding: 0 .95rem;
        font-weight: 800;
        font-size: .95rem;
        transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
    }

    .fcc-app-stats-tour-btn--secondary {
        background: rgba(255,255,255,.06);
        color: #eef6fb;
    }

    .fcc-app-stats-tour-btn--primary {
        background: linear-gradient(135deg, #43e8d4 0%, #2fb8e8 100%);
        color: #07131a;
        box-shadow: 0 14px 24px rgba(67, 232, 212, .2);
    }

    .fcc-app-stats-tour-btn:hover,
    .fcc-app-stats-tour-btn:focus {
        transform: translateY(-1px);
        outline: none;
    }

    @media (max-width: 991.98px) {
        .fcc-app-stats-guide-rail {
            margin-bottom: .85rem;
        }

        .fcc-app-stats-tour-card {
            left: 1rem;
            right: 1rem;
            bottom: 1rem;
            width: auto;
            max-width: none;
        }
    }

    .fcc-app-stats-page-shell {
        display: flex;
        flex-direction: column;
        gap: 1.15rem;
    }

    .fcc-app-stats-nav-panel {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 1.45rem;
        background:
            radial-gradient(circle at top right, rgba(59, 203, 192, .10), transparent 25%),
            linear-gradient(180deg, rgba(18, 24, 39, .96), rgba(9, 13, 23, .99));
        box-shadow: 0 18px 40px rgba(0, 0, 0, .16);
        padding: 1rem;
    }

    .fcc-app-stats-nav-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: .85rem;
    }

    .fcc-app-stats-nav-title {
        display: flex;
        flex-direction: column;
        gap: .2rem;
    }

    .fcc-app-stats-nav-title h2 {
        margin: 0;
        color: #f5fbff;
        font-size: 1.85rem;
        line-height: 1.05;
        font-weight: 800;
    }

    .fcc-app-stats-nav-title p {
        margin: 0;
        color: rgba(226, 232, 240, .72);
        font-size: .96rem;
        line-height: 1.5;
        max-width: 42rem;
    }

    .fcc-app-stats-nav-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: .75rem;
    }

    .fcc-app-stats-nav-grid .btn-custom {
        min-height: 3rem;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,.06);
        background: rgba(10, 15, 25, .74);
        color: rgba(231, 241, 255, .78);
        font-weight: 700;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
    }

    .fcc-app-stats-nav-grid .btn-custom.active,
    .fcc-app-stats-nav-grid .btn-custom:hover,
    .fcc-app-stats-nav-grid .btn-custom:focus {
        color: #f8feff;
        border-color: rgba(84, 229, 212, .28);
        background: linear-gradient(135deg, rgba(36, 174, 164, .18) 0%, rgba(22, 80, 139, .16) 100%);
        box-shadow: 0 12px 30px rgba(18, 30, 50, .16);
    }

    .fcc-app-overview-shell {
        display: flex;
        flex-direction: column;
        gap: 1.2rem;
    }

    .fcc-app-overview-intro {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 1.35rem;
        background:
            radial-gradient(circle at top right, rgba(55, 197, 189, .1), transparent 28%),
            linear-gradient(180deg, rgba(18, 24, 39, .96), rgba(10, 15, 25, .99));
        padding: 1.2rem 1.25rem;
        box-shadow: 0 18px 36px rgba(0, 0, 0, .12);
    }

    .fcc-app-overview-intro-eyebrow {
        color: rgba(203, 221, 242, .72);
        font-size: .74rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .45rem;
    }

    .fcc-app-overview-intro h3 {
        margin: 0 0 .45rem;
        color: #f8fdff;
        font-size: 1.3rem;
        line-height: 1.15;
        font-weight: 800;
    }

    .fcc-app-overview-intro p {
        margin: 0;
        color: rgba(226, 232, 240, .76);
        font-size: .97rem;
        line-height: 1.6;
        max-width: 44rem;
    }

    .fcc-app-overview-intro-badge {
        flex-shrink: 0;
        min-width: 12rem;
        border-radius: 1.1rem;
        padding: .85rem 1rem;
        border: 1px solid rgba(111, 244, 228, .14);
        background: rgba(16, 27, 41, .82);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.03);
    }

    .fcc-app-overview-intro-badge span {
        display: block;
        color: rgba(206, 232, 247, .68);
        font-size: .76rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        margin-bottom: .35rem;
    }

    .fcc-app-overview-intro-badge strong {
        display: block;
        color: #f8fdff;
        font-size: 1.8rem;
        line-height: 1;
        font-weight: 850;
    }

    .fcc-app-overview-kpis {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .fcc-app-overview-kpi {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 1.25rem;
        background:
            radial-gradient(circle at top right, rgba(57, 202, 193, .08), transparent 28%),
            linear-gradient(180deg, rgba(19, 26, 40, .94), rgba(10, 15, 26, .98));
        padding: 1.15rem 1.2rem;
        box-shadow: 0 18px 36px rgba(0, 0, 0, .12);
    }

    .fcc-app-overview-kpi-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        font-weight: 800;
        color: rgba(206, 232, 247, .72);
        margin-bottom: .6rem;
    }

    .fcc-app-overview-kpi-value {
        margin: 0;
        color: #f8fdff;
        font-size: 2rem;
        line-height: 1;
        font-weight: 850;
    }

    .fcc-app-overview-kpi-value--compact {
        font-size: 1.35rem;
        line-height: 1.2;
    }

    .fcc-app-overview-kpi-note {
        margin-top: .45rem;
        color: rgba(226, 232, 240, .74);
        font-size: .94rem;
        line-height: 1.45;
    }

    .fcc-app-overview-chart-card,
    .fcc-app-insight-card,
    .fcc-app-latest-card,
    .fcc-app-preview-strip {
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 1.35rem;
        background:
            radial-gradient(circle at top right, rgba(55, 197, 189, .08), transparent 24%),
            linear-gradient(180deg, rgba(18, 24, 39, .96), rgba(10, 15, 25, .98));
        box-shadow: 0 18px 36px rgba(0, 0, 0, .12);
    }

    .fcc-app-overview-chart-card .card-body,
    .fcc-app-insight-card .card-body,
    .fcc-app-latest-card .card-body {
        padding: 1.15rem 1.2rem;
    }

    .fcc-app-overview-chart-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: .85rem;
    }

    .fcc-app-overview-chart-head h3 {
        margin: 0;
        color: #f8fdff;
        font-size: 1.2rem;
        font-weight: 800;
    }

    .fcc-app-overview-chart-head p {
        margin: .35rem 0 0;
        color: rgba(226, 232, 240, .74);
        font-size: .95rem;
        line-height: 1.5;
        max-width: 40rem;
    }

    .fcc-app-preview-strip {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .95rem 1.1rem;
        color: rgba(226, 232, 240, .78);
    }

    .fcc-app-preview-strip strong {
        color: #f8fdff;
    }

    .fcc-app-insight-card h3,
    .fcc-app-latest-card h3 {
        color: #f8fdff;
        font-size: 1.08rem;
        font-weight: 800;
        margin-bottom: .2rem;
    }

    .fcc-app-insight-card .fcc-app-insight-subtitle,
    .fcc-app-latest-card .fcc-app-insight-subtitle {
        color: rgba(226, 232, 240, .62);
        font-size: .92rem;
        margin-bottom: .9rem;
    }

    .fcc-app-insight-card .progress {
        height: 7px !important;
        border-radius: 999px;
        background: rgba(255,255,255,.08);
    }

    .fcc-app-latest-card .table-custom-container {
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 1rem;
        overflow: hidden;
    }

    .fcc-app-latest-card .table-custom thead th {
        background: rgba(255,255,255,.03);
        border-bottom-color: rgba(255,255,255,.06);
        color: rgba(225, 235, 245, .78);
    }

    .fcc-app-latest-card .table-custom tbody td {
        border-top-color: rgba(255,255,255,.05);
    }

    .fcc-app-stats-empty {
        border: 1px dashed rgba(255,255,255,.12);
        border-radius: 1.2rem;
        padding: 1rem 1.1rem;
        color: rgba(226, 232, 240, .72);
        background: rgba(255,255,255,.02);
    }

    @media (max-width: 1199.98px) {
        .fcc-app-stats-nav-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .fcc-app-overview-kpis,
        .fcc-app-stats-nav-grid {
            grid-template-columns: 1fr;
        }

        .fcc-app-overview-intro,
        .fcc-app-preview-strip,
        .fcc-app-stats-nav-header {
            flex-direction: column;
            align-items: stretch;
        }

        .fcc-app-stats-nav-title h2 {
            font-size: 1.55rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
<div class="fcc-app-stats-page-shell">
<?php if($fcc_stats_is_biolink && !$fcc_stats_is_main): ?>
    <div id="fcc_app_stats_tour_step_extra_info" class="fcc-app-stats-info-card">
        <div class="card-body p-4">
            <div class="fcc-app-stats-info-badge"><?= $fcc_stats_extra_title ?></div>
            <h3 class="fcc-app-stats-info-title"><?= $fcc_stats_extra_title ?></h3>
            <p class="fcc-app-stats-info-text"><?= $fcc_stats_extra_text ?></p>
            <?php if(!empty($data->main_biolink_statistics_url)): ?>
                <div class="mt-3">
                    <a href="<?= htmlspecialchars((string) $data->main_biolink_statistics_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary" style="border-radius:1rem; min-height:3rem; font-weight:800;"><?= $fcc_stats_extra_cta ?></a>
                </div>
            <?php endif ?>
        </div>
    </div>
<?php endif ?>

<div class="fcc-app-stats-nav-panel" id="fcc_app_stats_tour_step_nav">
    <div class="fcc-app-stats-nav-header">
        <div class="fcc-app-stats-nav-title">
            <h2><?= l('link.statistics.header') ?></h2>
            <p><?= $fcc_stats_is_hr ? 'Prati ključne brojke, kretanje interesa i izvore prometa za odabranu FCC aplikaciju.' : 'Track key numbers, movement of interest and traffic sources for the selected FCC app.' ?></p>
        </div>

        <div class="d-flex align-items-center col-auto p-0">
            <div data-toggle="tooltip" title="<?= l('statistics_reset_modal.header') ?>">
                <button
                        type="button"
                        class="btn btn-link text-secondary"
                        data-toggle="modal"
                        data-target="#link_statistics_reset_modal"
                        aria-label="<?= l('statistics_reset_modal.header') ?>"
                        data-link-id="<?= $data->link->link_id ?>"
                        data-start-date="<?= $data->datetime['start_date'] ?>"
                        data-end-date="<?= $data->datetime['end_date'] ?>"
                >
                    <i class="fas fa-fw fa-sm fa-eraser"></i>
                </button>
            </div>

            <button
                    id="daterangepicker"
                    type="button"
                    class="btn btn-sm btn-light"
                    data-min-date="<?= \Altum\Date::get($data->link->datetime, 4) ?>"
                    data-max-date="<?= \Altum\Date::get('', 4) ?>"
            >
                <i class="fas fa-fw fa-calendar mr-lg-1"></i>
                <span class="d-none d-lg-inline-block">
                    <?php if($data->datetime['start_date'] == $data->datetime['end_date']): ?>
                        <?= \Altum\Date::get($data->datetime['start_date'], 6, \Altum\Date::$default_timezone) ?>
                    <?php else: ?>
                        <?= \Altum\Date::get($data->datetime['start_date'], 6, \Altum\Date::$default_timezone) . ' - ' . \Altum\Date::get($data->datetime['end_date'], 6, \Altum\Date::$default_timezone) ?>
                    <?php endif ?>
                </span>
                <i class="fas fa-fw fa-caret-down d-none d-lg-inline-block ml-lg-1"></i>
            </button>
        </div>
    </div>

<div class="fcc-app-stats-nav-grid">
    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'overview' ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=overview&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-list mr-1"></i>
            <?= l('link.statistics.overview') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'entries' ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=entries&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-chart-bar mr-1"></i>
            <?= l('link.statistics.entries') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'continent_code' ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=continent_code&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-globe-europe mr-1"></i>
            <?= l('global.continent') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'country' ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=country&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-globe mr-1"></i>
            <?= l('global.countries') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'city_name' ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=city_name&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-city mr-1"></i>
            <?= l('global.cities') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= in_array($data->type, ['referrer_host', 'referrer_path']) ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=referrer_host&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-random mr-1"></i>
            <?= l('link.statistics.referrer_host') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'device' ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=device&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-laptop mr-1"></i>
            <?= l('link.statistics.device') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'os' ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=os&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-server mr-1"></i>
            <?= l('link.statistics.os') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'browser' ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=browser&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-window-restore mr-1"></i>
            <?= l('link.statistics.browser') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= $data->type == 'language' ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=language&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-language mr-1"></i>
            <?= l('link.statistics.language') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= in_array($data->type, ['utm_source', 'utm_medium', 'utm_campaign']) ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=utm_source&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-link mr-1"></i>
            <?= l('link.statistics.utms') ?>
        </a>
    </div>

    <div class="p-1 p-lg-2 text-truncate">
        <a class="btn btn-block btn-custom text-truncate <?= in_array($data->type, ['hour']) ? 'active' : null ?>" href="<?= url((isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/' . $data->method . '?type=hour&start_date=' . $data->datetime['start_date'] . '&end_date=' . $data->datetime['end_date']) ?>">
            <i class="fas fa-fw fa-sm fa-clock mr-1"></i>
            <?= l('link.statistics.hour') ?>
        </a>
    </div>
</div>
</div>

<?php if(!$data->has_data): ?>

    <div class="fcc-app-stats-empty">
        <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
            'filters_get' => $data->filters->get ?? [],
            'name' => 'link.statistics',
            'has_secondary_text' => true,
        ]); ?>
    </div>

<?php else: ?>

    <div id="fcc_app_stats_tour_step_overview">
        <?= $this->views['statistics'] ?>
    </div>

<?php endif ?>
</div>

<div class="fcc-app-stats-tour-overlay" id="fcc_app_stats_tour_overlay"></div>

<div class="fcc-app-stats-tour-card" id="fcc_app_stats_tour_card" aria-live="polite">
    <div class="fcc-app-stats-tour-step" id="fcc_app_stats_tour_step_counter">1 / 4</div>
    <h3 id="fcc_app_stats_tour_title"></h3>
    <p id="fcc_app_stats_tour_text"></p>
    <div class="fcc-app-stats-tour-actions">
        <button type="button" class="fcc-app-stats-tour-dismiss" id="fcc_app_stats_tour_skip"><?= $fcc_stats_is_hr ? 'Preskoči' : 'Skip' ?></button>
        <div class="fcc-app-stats-tour-nav">
            <button type="button" class="fcc-app-stats-tour-btn fcc-app-stats-tour-btn--secondary" id="fcc_app_stats_tour_prev"><?= $fcc_stats_is_hr ? 'Natrag' : 'Back' ?></button>
            <button type="button" class="fcc-app-stats-tour-btn fcc-app-stats-tour-btn--primary" id="fcc_app_stats_tour_next"><?= $fcc_stats_is_hr ? 'Dalje' : 'Next' ?></button>
        </div>
    </div>
</div>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/moment.min.js?v=' . PRODUCT_CODE ?>"></script>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/daterangepicker.min.js?v=' . PRODUCT_CODE ?>"></script>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/moment-timezone-with-data-10-year-range.min.js?v=' . PRODUCT_CODE ?>"></script>

<script>
    'use strict';

moment.tz.setDefault(<?= json_encode($this->user->timezone) ?>);

    /* Daterangepicker */
    $('#daterangepicker').daterangepicker({
        startDate: <?= json_encode($data->datetime['start_date']) ?>,
        endDate: <?= json_encode($data->datetime['end_date']) ?>,
        minDate: $('#daterangepicker').data('min-date'),
        maxDate: $('#daterangepicker').data('max-date'),
        ranges: {
            <?= json_encode(l('global.date.today')) ?>: [moment(), moment()],
            <?= json_encode(l('global.date.yesterday')) ?>: [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            <?= json_encode(l('global.date.this_week')) ?>: [moment().startOf('week'), moment().endOf('week')],
            <?= json_encode(l('global.date.last_30_days')) ?>: [moment().subtract(29, 'days'), moment()],
            <?= json_encode(l('global.date.this_month')) ?>: [moment().startOf('month'), moment().endOf('month')],
            <?= json_encode(l('global.date.last_month')) ?>: [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
            <?= json_encode(l('global.date.this_year')) ?>: [moment().startOf('year'), moment()],
            <?= json_encode(l('global.date.last_year')) ?>: [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
            <?= json_encode(l('global.date.all_time')) ?>: [moment($('#daterangepicker').data('min-date')), moment()]
        },
        alwaysShowCalendars: true,
        linkedCalendars: false,
        singleCalendar: true,
        locale: <?= json_encode(require APP_PATH . 'includes/daterangepicker_translations.php') ?>,
    }, (start, end, label) => {

        <?php
        parse_str(\Altum\Router::$original_request_query, $original_request_query_array);
        $modified_request_query_array = array_diff_key($original_request_query_array, ['start_date' => '', 'end_date' => '']);
        ?>

        redirect(`<?= url(\Altum\Router::$original_request . '?' . http_build_query($modified_request_query_array)) ?>&start_date=${start.format('YYYY-MM-DD')}&end_date=${end.format('YYYY-MM-DD')}`, true);

    });

    (() => {
        const storageKey = <?= json_encode($fcc_stats_storage_key) ?>;
        const steps = <?= json_encode($fcc_stats_tour_steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const launchButton = document.getElementById('fcc_app_stats_start_tour');
        const overlay = document.getElementById('fcc_app_stats_tour_overlay');
        const card = document.getElementById('fcc_app_stats_tour_card');
        const title = document.getElementById('fcc_app_stats_tour_title');
        const text = document.getElementById('fcc_app_stats_tour_text');
        const counter = document.getElementById('fcc_app_stats_tour_step_counter');
        const prevButton = document.getElementById('fcc_app_stats_tour_prev');
        const nextButton = document.getElementById('fcc_app_stats_tour_next');
        const skipButton = document.getElementById('fcc_app_stats_tour_skip');
        const validSteps = steps.filter(step => step.selector && document.querySelector(step.selector));
        let activeIndex = 0;
        let activeTarget = null;
        const desktopBreakpoint = 991.98;
        const viewportPadding = 18;

        const setTourMode = isActive => {
            document.body.classList.toggle('fcc-tour-mode', !!isActive);

            if(typeof window.CustomEvent === 'function') {
                window.dispatchEvent(new CustomEvent('fcc:tutorial:state', {
                    detail: {active: !!isActive}
                }));
            }
        };

        if(!launchButton || !overlay || !card || !title || !text || !counter || !prevButton || !nextButton || !skipButton || !validSteps.length) {
            return;
        }

        const resetCardPosition = () => {
            card.style.left = '';
            card.style.top = '';
            card.style.right = '';
            card.style.bottom = '';
        };

        const positionCardNearTarget = () => {
            if(window.innerWidth <= desktopBreakpoint || !activeTarget) {
                resetCardPosition();
                return;
            }

            const targetRect = activeTarget.getBoundingClientRect();
            const cardRect = card.getBoundingClientRect();
            const cardWidth = Math.min(cardRect.width || 432, window.innerWidth - viewportPadding * 2);
            const cardHeight = cardRect.height || 280;
            const gap = 18;

            let left = targetRect.left;
            let top = targetRect.bottom + gap;

            if(left + cardWidth > window.innerWidth - viewportPadding) {
                left = window.innerWidth - viewportPadding - cardWidth;
            }

            if(left < viewportPadding) {
                left = viewportPadding;
            }

            if(top + cardHeight > window.innerHeight - viewportPadding) {
                top = targetRect.top - cardHeight - gap;
            }

            if(top < viewportPadding) {
                top = viewportPadding;
            }

            card.style.left = `${Math.round(left)}px`;
            card.style.top = `${Math.round(top)}px`;
            card.style.right = 'auto';
            card.style.bottom = 'auto';
        };

        const clearActiveTarget = () => {
            if(activeTarget) {
                activeTarget.classList.remove('fcc-app-stats-tour-highlight');
                activeTarget = null;
            }
        };

        const closeTour = () => {
            clearActiveTarget();
            overlay.classList.remove('is-active');
            card.classList.remove('is-active');
            document.body.classList.remove('fcc-app-stats-tour-open');
            setTourMode(false);
            resetCardPosition();
        };

        const renderStep = () => {
            const step = validSteps[activeIndex];
            const target = document.querySelector(step.selector);

            if(!target) {
                return;
            }

            clearActiveTarget();
            activeTarget = target;
            activeTarget.classList.add('fcc-app-stats-tour-highlight');
            activeTarget.scrollIntoView({behavior: 'smooth', block: 'center'});

            counter.textContent = `${activeIndex + 1} / ${validSteps.length}`;
            title.textContent = step.title;
            text.textContent = step.text;
            prevButton.style.visibility = activeIndex === 0 ? 'hidden' : 'visible';
            nextButton.textContent = activeIndex === validSteps.length - 1 ? <?= json_encode($fcc_stats_is_hr ? 'Završi' : 'Finish') ?> : <?= json_encode($fcc_stats_is_hr ? 'Dalje' : 'Next') ?>;

            requestAnimationFrame(() => {
                positionCardNearTarget();
                window.setTimeout(positionCardNearTarget, 260);
            });
        };

        const openTour = () => {
            overlay.classList.add('is-active');
            card.classList.add('is-active');
            document.body.classList.add('fcc-app-stats-tour-open');
            setTourMode(true);
            activeIndex = 0;
            renderStep();
        };

        launchButton.addEventListener('click', openTour);
        skipButton.addEventListener('click', () => {
            localStorage.setItem(storageKey, '1');
            closeTour();
        });

        overlay.addEventListener('click', () => {
            localStorage.setItem(storageKey, '1');
            closeTour();
        });

        prevButton.addEventListener('click', () => {
            if(activeIndex === 0) return;
            activeIndex--;
            renderStep();
        });

        nextButton.addEventListener('click', () => {
            if(activeIndex >= validSteps.length - 1) {
                localStorage.setItem(storageKey, '1');
                closeTour();
                return;
            }

            activeIndex++;
            renderStep();
        });

        window.addEventListener('keydown', event => {
            if(!card.classList.contains('is-active')) return;
            if(event.key === 'Escape') {
                localStorage.setItem(storageKey, '1');
                closeTour();
            }
        });

        window.addEventListener('resize', positionCardNearTarget);

        if(!localStorage.getItem(storageKey)) {
            window.setTimeout(openTour, 420);
        }
    })();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/statistics_reset_modal.php', ['modal_id' => 'link_statistics_reset_modal', 'resource_id' => 'link_id', 'path' => (isset($data->link->biolink_block_id) ? 'biolink-block/' . $data->link->biolink_block_id : 'link/' . $data->link->link_id) . '/statistics/reset']), 'modals'); ?>
