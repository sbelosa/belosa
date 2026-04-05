<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_links_is_hr = \Altum\Language::$code === 'hr';
$fcc_links_type = (string) ($data->filters->filters['type'] ?? '');
$fcc_is_biolink_links = $fcc_links_type === 'biolink';
$fcc_is_short_links = $fcc_links_type === 'link';
$fcc_main_featured = $data->main_biolink_featured ?? null;
$fcc_main_auto_summary = $data->main_biolink_auto_summary ?? '';
$fcc_main_biolink_row = $data->main_biolink_row ?? null;
$fcc_additional_links = $fcc_is_biolink_links ? ($data->links ?? []) : [];
$fcc_table_links = $fcc_is_biolink_links ? $fcc_additional_links : ($data->links ?? []);
$fcc_has_renderable_links = $fcc_is_biolink_links ? (!empty($fcc_main_biolink_row) || !empty($fcc_additional_links)) : !empty($fcc_table_links);

if($fcc_is_biolink_links && !$fcc_main_biolink_row && !empty($this->user->user_id)) {
    $fcc_resolved_main_biolink_id = (int) (fc_get_user_main_biolink_id((int) $this->user->user_id, false) ?? 0);

    if(!$fcc_resolved_main_biolink_id) {
        $fcc_resolved_main_biolink_id = (int) (db()
            ->where('user_id', (int) $this->user->user_id)
            ->where('type', 'biolink')
            ->orderBy('is_enabled', 'DESC')
            ->orderBy('datetime', 'ASC')
            ->orderBy('link_id', 'ASC')
            ->getValue('links', 'link_id') ?? 0);
    }

    if($fcc_resolved_main_biolink_id > 0) {
        $fcc_main_biolink_row = db()
            ->where('link_id', $fcc_resolved_main_biolink_id)
            ->where('user_id', (int) $this->user->user_id)
            ->where('type', 'biolink')
            ->getOne('links');

        if($fcc_main_biolink_row) {
            $fcc_main_biolink_row->settings = is_string($fcc_main_biolink_row->settings ?? null) ? json_decode($fcc_main_biolink_row->settings ?? '{}') : ($fcc_main_biolink_row->settings ?? new \stdClass());
            $fcc_main_biolink_row->full_url = $fcc_main_biolink_row->domain_id && !empty($data->domains[$fcc_main_biolink_row->domain_id])
                ? $data->domains[$fcc_main_biolink_row->domain_id]->scheme . $data->domains[$fcc_main_biolink_row->domain_id]->host . '/' . ($data->domains[$fcc_main_biolink_row->domain_id]->link_id == $fcc_main_biolink_row->link_id ? null : $fcc_main_biolink_row->url)
                : SITE_URL . $fcc_main_biolink_row->url;
            $fcc_main_biolink_row->app_review_page_url = $data->app_review_page_url . '?app_review_selected_link_id=' . (int) $fcc_main_biolink_row->link_id;
        }
    }
}

if($fcc_is_biolink_links && !$fcc_main_biolink_row && !empty($data->links)) {
    $fcc_fallback_main_candidate = null;

    foreach($data->links as $fcc_candidate_row) {
        if(($fcc_candidate_row->type ?? '') !== 'biolink') {
            continue;
        }

        if((int) ($fcc_candidate_row->biolink_id ?? 0) === (int) ($fcc_candidate_row->link_id ?? 0)) {
            $fcc_fallback_main_candidate = $fcc_candidate_row;
            break;
        }
    }

    if($fcc_fallback_main_candidate) {
        $fcc_main_biolink_row = clone $fcc_fallback_main_candidate;
    }
}

if($fcc_is_biolink_links && $fcc_main_biolink_row && !empty($fcc_additional_links)) {
    $fcc_additional_links = array_values(array_filter($fcc_additional_links, function($fcc_row) use ($fcc_main_biolink_row) {
        return !((int) ($fcc_row->link_id ?? 0) === (int) ($fcc_main_biolink_row->link_id ?? 0) && ($fcc_row->type ?? '') === 'biolink');
    }));
}

if($fcc_is_biolink_links && $fcc_main_biolink_row && !$fcc_main_featured) {
    $fcc_main_featured = [
        'link_id' => (int) ($fcc_main_biolink_row->link_id ?? 0),
        'opt_in' => (int) ($fcc_main_biolink_row->fcc_featured_opt_in ?? 1),
        'is_approved' => (int) ($fcc_main_biolink_row->fcc_featured_is_approved ?? 1),
        'public_market' => trim((string) ($fcc_main_biolink_row->fcc_featured_public_market ?? '')),
        'public_summary' => trim((string) ($fcc_main_biolink_row->fcc_featured_public_summary ?? '')),
        'feature_labels' => [],
    ];
}

$fcc_main_header = $fcc_links_is_hr ? 'Javni prikaz glavne Forever Card Aplikacije' : 'Public showcase of the main Forever Card App';
$fcc_main_subheader = $fcc_links_is_hr ? 'Ove postavke vrijede samo za glavnu aplikaciju koju si dobio pri registraciji i aktivaciji pristupa. Ostale aplikacije ne ulaze u ovaj javni prikaz.' : 'These settings apply only to the main app assigned when your access was activated. Other apps are not included in this public showcase.';
$fcc_main_toggle = $fcc_links_is_hr ? 'Dopuštam javni prikaz glavne Forever Card Aplikacije na naslovnici Forever Card Cluba' : 'I allow public display of the main Forever Card App on the Forever Card Club homepage';
$fcc_main_market = $fcc_links_is_hr ? 'Javno tržište / država' : 'Public market / country';
$fcc_main_summary = $fcc_links_is_hr ? 'Kratki javni opis' : 'Short public summary';
$fcc_main_summary_help = $fcc_links_is_hr ? 'Opis je opcionalan. Ako ga ne upišeš, FCC će automatski složiti kratak sažetak na temelju aktivnih blokova tvoje glavne aplikacije.' : 'This summary is optional. If you leave it empty, FCC will automatically generate a short public summary based on the active blocks in your main app.';
$fcc_main_detected = $fcc_links_is_hr ? 'FCC je automatski prepoznao' : 'FCC automatically detected';
$fcc_main_status_on = $fcc_links_is_hr ? 'Javni prikaz je uključen.' : 'Public showcase is enabled.';
$fcc_main_status_off = $fcc_links_is_hr ? 'Javni prikaz je trenutno isključen.' : 'Public showcase is currently turned off.';
$fcc_main_admin_hidden = $fcc_links_is_hr ? 'Admin je trenutno isključio javni prikaz ove aplikacije.' : 'Admin has currently hidden this app from the public showcase.';
$fcc_main_submit = $fcc_links_is_hr ? 'Spremi javni prikaz' : 'Save public showcase settings';
$fcc_main_visible_tags_limit = 3;
$fcc_workspace_guide_title = $fcc_links_is_hr ? 'Uredi, stvaraj i vodi svoje FCC aplikacije na jednom mjestu' : 'Create, edit and manage your FCC apps in one place';
$fcc_workspace_guide_text = $fcc_links_is_hr ? 'Ovdje izrađuješ nove aplikacije, biraš predloške, vodiš glavnu aplikaciju povezanu s Tvojim planom rasta i uređuješ dodatne verzije za posebne potrebe.' : 'Create new apps, choose templates, manage the main app connected to your Growth Plan and edit extra versions for specific use cases.';
$fcc_main_workspace_title = $fcc_links_is_hr ? 'Glavna FCC aplikacija' : 'Main FCC app';
$fcc_main_workspace_text = $fcc_links_is_hr ? 'Ovo je tvoja glavna aplikacija povezana s javnim prikazom, AI analizom i rastom unutar FCC sustava.' : 'This is your main app connected to the public showcase, AI review and growth inside the FCC system.';
$fcc_secondary_apps_title = $fcc_links_is_hr ? 'Dodatne FCC aplikacije' : 'Additional FCC apps';
$fcc_secondary_apps_text = $fcc_links_is_hr ? 'Ovdje su sve dodatne aplikacije koje koristiš za posebne ponude, tržišta ili testiranje. AI analiza ostaje vezana samo uz glavnu aplikaciju.' : 'These are your additional apps for specific offers, markets or testing. AI review remains available only for the main app.';
$fcc_main_actions_label = $fcc_links_is_hr ? 'Glavne akcije' : 'Main actions';
$fcc_main_signals_label = $fcc_links_is_hr ? 'Signal aplikacije' : 'App signal';
$fcc_main_edit = $fcc_links_is_hr ? 'Uredi glavnu aplikaciju' : 'Edit main app';
$fcc_main_stats = $fcc_links_is_hr ? 'Statistika aplikacije' : 'App statistics';
$fcc_main_copy = $fcc_links_is_hr ? 'Kopiraj link aplikacije' : 'Copy app link';
$fcc_main_quality = $fcc_links_is_hr ? 'Kvaliteta aplikacije' : 'App quality';
$fcc_main_public_preview = $fcc_links_is_hr ? 'Pregled javnog prikaza' : 'Public showcase preview';
$fcc_secondary_header = $fcc_links_is_hr ? 'Sve dodatne aplikacije' : 'All additional apps';
$fcc_apps_tour_launch = l('dashboard.tour.launch');
$fcc_short_links_guide_title = l('links.short_links.guide_title');
$fcc_short_links_guide_text = l('links.short_links.guide_text');
$fcc_short_links_total_label = l('links.short_links.total_label');
$fcc_short_links_empty = l('links.short_links.empty');

$fcc_tour_storage_key = null;
$fcc_tour_steps = [];

if($fcc_links_type === 'biolink') {
    $fcc_tour_storage_key = 'fcc_biolink_apps_tour_seen_v1';
    $fcc_tour_steps = [
        [
            'selector' => '#fcc_apps_tour_step_create',
            'title' => $fcc_links_is_hr ? 'Nova FCC aplikacija' : 'New FCC app',
            'text' => $fcc_links_is_hr ? 'Ovdje kreiraš novu FCC aplikaciju za tržište, ponudu, kampanju ili posebnu namjenu. Kreni odavde kada želiš novu verziju svoje prezentacije.' : 'Create a new FCC app here for a market, offer, campaign or a special use case. Start here whenever you want a new version of your presentation.'
        ],
        [
            'selector' => '#fcc_apps_tour_step_templates',
            'title' => $fcc_links_is_hr ? 'FCC Predlošci' : 'FCC Templates',
            'text' => $fcc_links_is_hr ? 'Ovdje biraš gotove FCC predloške. Oni su unaprijed složeni i automatski povlače podatke koje si unio prilikom registracije na FCC, pa možeš krenuti brže.' : 'Choose ready-made FCC templates here. They come pre-assembled and automatically use the data you entered during FCC registration so you can start faster.'
        ],
        [
            'selector' => '#fcc_apps_tour_step_main_app',
            'title' => $fcc_links_is_hr ? 'Glavna FCC aplikacija' : 'Main FCC app',
            'text' => $fcc_links_is_hr ? 'Ovo je tvoja glavna aplikacija. Ovdje uređuješ njezine postavke, a iznad odmah vidiš kvalitetu aplikacije i ključne blokove koji grade rezultat.' : 'This is your main app. Here you manage its core settings and immediately see the app quality and the key blocks that drive results.'
        ],
        [
            'selector' => '#fcc_apps_tour_step_stats',
            'title' => $fcc_links_is_hr ? 'Statistika aplikacije' : 'App statistics',
            'text' => $fcc_links_is_hr ? 'Ovdje otvaraš detaljnu statistiku glavne FCC aplikacije i pratiš što stvarno donosi klikove, interes i daljnji napredak.' : 'Open the detailed statistics of your main FCC app here and see what actually brings clicks, interest and progress.'
        ],
        [
            'selector' => '#fcc_apps_tour_step_copy_link',
            'title' => $fcc_links_is_hr ? 'Kopiraj glavni link aplikacije' : 'Copy the main app link',
            'text' => $fcc_links_is_hr ? 'Ovdje kopiraš link glavne FCC aplikacije za dijeljenje. Taj link u sebi nosi sve elemente potrebne za praćenje interesa i tvoju preporuku prema Forever webshopovima.' : 'Copy the main FCC app link here for sharing. This link includes the elements needed for tracking interest and your recommendation toward Forever webshops.'
        ],
        [
            'selector' => '#fcc_apps_tour_step_public_showcase',
            'title' => $fcc_links_is_hr ? 'Javni prikaz i rast aplikacije' : 'Public showcase and app growth',
            'text' => $fcc_links_is_hr ? 'Ovdje uređuješ javni prikaz glavne aplikacije. Nakon ostvarenih 15 kvalificiranih klikova glavna aplikacija može biti izlistana na naslovnici Forever Card Cluba, uzeta u partnera Forever Card Cluba te prijavljena u indekse umjetnih inteligencija i tražilica kao partner Forever Living Productsa.' : 'Here you manage the public showcase of the main app. After reaching 15 qualified clicks, your main app can be listed on the Forever Card Club homepage, featured as a Forever Card Club partner, and submitted to AI/search indexes as a Forever Living Products partner.'
        ],
        [
            'selector' => '#fcc_apps_tour_step_extra_apps',
            'title' => $fcc_links_is_hr ? 'Dodatne FCC aplikacije' : 'Additional FCC apps',
            'text' => $fcc_links_is_hr ? 'Ovdje će se nalaziti sve dodatne FCC aplikacije koje izradiš za posebne potrebe, tržišta i scenarije, bez miješanja s glavnom aplikacijom.' : 'This is where all extra FCC apps you create for special needs, markets and scenarios will appear, separate from your main app.'
        ],
    ];
} elseif($fcc_is_short_links) {
    $fcc_tour_storage_key = 'fcc_short_links_tour_seen_v1';
    $fcc_tour_steps = [
        [
            'selector' => '#fcc_short_links_tour_step_create',
            'title' => l('links.short_links.tour.create_title'),
            'text' => l('links.short_links.tour.create_text'),
        ],
        [
            'selector' => '#fcc_short_links_tour_step_bulk_create',
            'title' => l('links.short_links.tour.bulk_title'),
            'text' => l('links.short_links.tour.bulk_text'),
        ],
        [
            'selector' => '#fcc_short_links_tour_step_list',
            'title' => l('links.short_links.tour.list_title'),
            'text' => l('links.short_links.tour.list_text'),
        ],
        [
            'selector' => '#fcc_short_links_tour_step_row',
            'title' => l('links.short_links.tour.row_title'),
            'text' => l('links.short_links.tour.row_text'),
        ],
        [
            'selector' => '#fcc_short_links_tour_step_actions',
            'title' => l('links.short_links.tour.actions_title'),
            'text' => l('links.short_links.tour.actions_text'),
        ],
    ];
}
?>

<style>
    .fcc-links-shell {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .fcc-links-shell.is-short-links {
        position: relative;
    }

    .fcc-links-shell.is-short-links::before {
        content: '';
        position: absolute;
        inset: 5.2rem 0 auto;
        height: 34rem;
        border-radius: 34px;
        background:
            radial-gradient(circle at 16% 16%, rgba(70, 220, 214, 0.06) 0%, rgba(70, 220, 214, 0) 38%),
            radial-gradient(circle at 82% 12%, rgba(61, 118, 255, 0.06) 0%, rgba(61, 118, 255, 0) 32%),
            radial-gradient(circle at 48% 0%, rgba(34, 185, 129, 0.03) 0%, rgba(34, 185, 129, 0) 24%),
            linear-gradient(180deg, rgba(12, 19, 33, 0.58) 0%, rgba(16, 16, 18, 0) 100%);
        pointer-events: none;
        z-index: 0;
    }

    .fcc-links-shell.is-short-links > * {
        position: relative;
        z-index: 1;
    }

    .fcc-links-shell.is-biolink {
        position: relative;
    }

    .fcc-links-shell.is-biolink::before {
        content: '';
        position: absolute;
        inset: 5.2rem 0 auto;
        height: 38rem;
        border-radius: 34px;
        background:
            radial-gradient(circle at 14% 16%, rgba(70, 220, 214, 0.06) 0%, rgba(70, 220, 214, 0) 40%),
            radial-gradient(circle at 82% 10%, rgba(61, 118, 255, 0.06) 0%, rgba(61, 118, 255, 0) 34%),
            radial-gradient(circle at 46% 0%, rgba(34, 185, 129, 0.03) 0%, rgba(34, 185, 129, 0) 26%),
            linear-gradient(180deg, rgba(12, 19, 33, 0.62) 0%, rgba(16, 16, 18, 0) 100%);
        pointer-events: none;
        z-index: 0;
    }

    .fcc-links-shell.is-biolink > * {
        position: relative;
        z-index: 1;
    }

    .fcc-links-header {
        background: linear-gradient(180deg, rgba(19, 27, 29, 0.92) 0%, rgba(15, 21, 23, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.08);
        border-radius: 22px;
        padding: 1.35rem 1.4rem;
        box-shadow: 0 18px 40px rgba(0, 0, 0, 0.18);
    }

    .fcc-links-shell.is-short-links .fcc-links-header {
        background:
            radial-gradient(circle at top left, rgba(71, 224, 213, 0.05) 0%, rgba(71, 224, 213, 0) 32%),
            radial-gradient(circle at top right, rgba(69, 120, 255, 0.05) 0%, rgba(69, 120, 255, 0) 30%),
            linear-gradient(180deg, rgba(16, 26, 45, 0.97) 0%, rgba(11, 16, 28, 0.99) 100%);
        border-color: rgba(101, 220, 232, 0.08);
        box-shadow: 0 24px 50px rgba(4, 10, 24, 0.30);
    }

    .fcc-links-shell.is-biolink .fcc-links-header {
        background:
            radial-gradient(circle at top left, rgba(71, 224, 213, 0.05) 0%, rgba(71, 224, 213, 0) 32%),
            radial-gradient(circle at top right, rgba(69, 120, 255, 0.05) 0%, rgba(69, 120, 255, 0) 30%),
            linear-gradient(180deg, rgba(16, 26, 45, 0.97) 0%, rgba(11, 16, 28, 0.99) 100%);
        border-color: rgba(101, 220, 232, 0.08);
        box-shadow: 0 24px 50px rgba(4, 10, 24, 0.30);
    }

    .fcc-links-heading {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        min-width: 0;
    }

    .fcc-links-heading-icon {
        width: 2.85rem;
        height: 2.85rem;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(191, 246, 239, 0.18) 0%, rgba(142, 233, 222, 0.3) 100%);
        color: #9ef1e7;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
    }

    .fcc-links-shell.is-biolink .fcc-links-heading-icon {
        background: linear-gradient(135deg, rgba(76, 226, 214, 0.18) 0%, rgba(48, 105, 235, 0.26) 100%);
        color: #9ff4ec;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.10), 0 16px 30px rgba(9, 20, 46, 0.28);
    }

    .fcc-links-heading-copy {
        min-width: 0;
    }

    .fcc-links-heading-copy h1 {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: #f5fbfb;
    }

    .fcc-links-shell.is-short-links .fcc-links-heading-copy h1 {
        color: #f4f8ff;
    }

    .fcc-links-shell.is-biolink .fcc-links-heading-copy h1 {
        color: #f4f8ff;
    }

    .fcc-links-heading-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .7rem;
    }

    .fcc-links-toolbar {
        gap: 0.75rem;
    }

    .fcc-links-action-btn {
        border-radius: 14px;
        min-height: 2.75rem;
        font-weight: 600;
        box-shadow: none !important;
    }

    .fcc-links-action-btn.btn-primary {
        background: linear-gradient(135deg, #3fd7c7 0%, #6de9dd 100%);
        border-color: transparent;
        color: #082826;
    }

    .fcc-links-action-btn.btn-primary:hover {
        color: #041b19;
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(63, 215, 199, 0.2) !important;
    }

    .fcc-links-action-btn.btn-outline-primary {
        border-color: rgba(127, 227, 217, 0.34);
        color: #97eee4;
        background: rgba(127, 227, 217, 0.03);
    }

    .fcc-links-action-btn.btn-outline-primary:hover {
        color: #d9fffb;
        border-color: rgba(127, 227, 217, 0.48);
        background: rgba(127, 227, 217, 0.1);
    }

    .fcc-links-action-btn.btn-light,
    .fcc-links-action-btn.btn-dark {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.06);
        color: #d5e1e2;
    }

    .fcc-links-action-btn.btn-light:hover,
    .fcc-links-action-btn.btn-dark:hover {
        background: rgba(127, 227, 217, 0.1);
        border-color: rgba(127, 227, 217, 0.16);
        color: #f5fbfb;
    }

    .fcc-links-table-card {
        background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.07);
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 20px 44px rgba(0, 0, 0, 0.16);
    }

    .fcc-links-shell.is-biolink .fcc-links-table-card {
        background:
            radial-gradient(circle at top center, rgba(72, 220, 214, 0.05) 0%, rgba(72, 220, 214, 0) 24%),
            linear-gradient(180deg, rgba(16, 23, 39, 0.99) 0%, rgba(10, 14, 24, 0.99) 100%);
        border-color: rgba(90, 201, 230, 0.07);
        box-shadow: 0 26px 52px rgba(4, 10, 24, 0.28);
    }

    .fcc-links-table-card .table-custom-container {
        border: 0;
        background: transparent;
    }

    .fcc-links-table-card .table-custom {
        margin-bottom: 0;
    }

    .fcc-links-table-card .table-custom thead th {
        background: rgba(255, 255, 255, 0.02);
        color: #e9f7f5;
        border-bottom-color: rgba(127, 227, 217, 0.08);
    }

    .fcc-links-shell.is-biolink .fcc-links-table-card .table-custom thead th {
        background: rgba(80, 150, 255, 0.02);
        color: #e7f2ff;
        border-bottom-color: rgba(95, 205, 231, 0.08);
    }

    .fcc-links-table-card .table-custom td {
        border-top-color: rgba(255, 255, 255, 0.04);
        vertical-align: middle;
    }

    .fcc-links-shell.is-biolink .fcc-links-table-card .table-custom td {
        border-top-color: rgba(95, 205, 231, 0.05);
    }

    .fcc-links-table-card .table-custom tbody tr {
        transition: background 0.2s ease;
    }

    .fcc-links-table-card .table-custom tbody tr:hover {
        background: rgba(127, 227, 217, 0.035);
    }

    .fcc-links-shell.is-biolink .fcc-links-table-card .table-custom tbody tr:hover {
        background: rgba(73, 200, 218, 0.02);
    }

    .fcc-links-table-card .badge.badge-light {
        background: rgba(255, 255, 255, 0.08);
        color: #d6e5e4;
        border: 1px solid rgba(255, 255, 255, 0.04);
    }

    .fcc-links-shell.is-biolink .fcc-links-table-card .badge.badge-light {
        background: rgba(97, 178, 255, 0.08);
        color: #d7e9ff;
        border-color: rgba(97, 178, 255, 0.08);
    }

    .fcc-links-table-card .btn.btn-link.text-secondary {
        color: #92aaab !important;
    }

    .fcc-links-table-card .btn.btn-link.text-secondary:hover {
        color: #d8fffb !important;
    }

    .fcc-links-pagination {
        margin-top: 1rem;
        padding: 0 0.35rem;
    }

    .fcc-links-app-quality-inline {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem;
        margin-top: 0.45rem;
    }

    .fcc-links-app-quality-score,
    .fcc-links-app-quality-level {
        display: inline-flex;
        align-items: center;
        min-height: 1.75rem;
        border-radius: 999px;
        padding: 0.2rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 700;
    }

    .fcc-links-app-quality-score {
        background: rgba(63, 215, 199, 0.14);
        color: #aef7ef;
        border: 1px solid rgba(63, 215, 199, 0.28);
    }

    .fcc-links-app-quality-level {
        background: rgba(79, 116, 255, 0.12);
        color: #d8e2ff;
        border: 1px solid rgba(79, 116, 255, 0.18);
    }

    .fcc-links-app-quality-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.45rem;
    }

    .fcc-links-app-quality-stat {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        min-height: 1.5rem;
        padding: 0.1rem 0.5rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.05);
        color: #c5d3d5;
        font-size: 0.68rem;
        font-weight: 600;
    }

    .fcc-links-app-quality-cta {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 1.85rem;
        border-radius: 999px;
        padding: 0.24rem 0.78rem;
        border: 1px solid rgba(127, 227, 217, 0.22);
        background: rgba(127, 227, 217, 0.06);
        color: #9ef1e7;
        font-size: 0.72rem;
        font-weight: 700;
        text-decoration: none !important;
        transition: all 0.2s ease;
    }

    .fcc-links-app-quality-cta:hover {
        color: #e3fffb;
        border-color: rgba(127, 227, 217, 0.34);
        background: rgba(127, 227, 217, 0.12);
    }

    .fcc-links-app-quality-cta.is-disabled {
        opacity: 0.55;
        filter: saturate(0.75);
        cursor: not-allowed;
    }

    .fcc-links-empty {
        background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.07);
        border-radius: 22px;
        padding: 0.25rem;
    }

    .fcc-links-shell.is-biolink .fcc-links-empty {
        background:
            radial-gradient(circle at top center, rgba(73, 200, 218, 0.025) 0%, rgba(73, 200, 218, 0) 28%),
            linear-gradient(180deg, rgba(16, 23, 39, 0.98) 0%, rgba(10, 14, 24, 0.98) 100%);
        border-color: rgba(90, 201, 230, 0.07);
    }

    .fcc-main-app-featured-card {
        background: linear-gradient(180deg, rgba(19, 21, 24, 0.98) 0%, rgba(16, 18, 20, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.07);
        border-radius: 22px;
        padding: 1.35rem 1.4rem;
        box-shadow: 0 20px 44px rgba(0, 0, 0, 0.16);
    }

    .fcc-links-shell.is-biolink .fcc-main-app-featured-card {
        background:
            radial-gradient(circle at top left, rgba(34, 185, 129, 0.06) 0%, rgba(34, 185, 129, 0) 28%),
            radial-gradient(circle at 88% 10%, rgba(64, 118, 255, 0.06) 0%, rgba(64, 118, 255, 0) 24%),
            linear-gradient(180deg, rgba(15, 22, 37, 0.98) 0%, rgba(10, 14, 24, 0.98) 100%);
        border-color: rgba(90, 201, 230, 0.07);
        box-shadow: 0 22px 48px rgba(4, 10, 24, 0.26);
    }

    .fcc-main-app-featured-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .fcc-main-app-featured-pill,
    .fcc-main-app-featured-tag {
        display: inline-flex;
        align-items: center;
        min-height: 2rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .fcc-main-app-featured-pill {
        background: rgba(255, 255, 255, 0.05);
        color: rgba(240, 244, 251, 0.88);
    }

    .fcc-links-shell.is-biolink .fcc-main-app-featured-pill {
        background: rgba(77, 201, 220, 0.08);
        color: #c7f6ef;
    }

    .fcc-main-app-featured-tag {
        background: rgba(104, 232, 188, 0.1);
        color: #c9fff2;
    }

    .fcc-links-shell.is-biolink .fcc-main-app-featured-tag {
        background: rgba(33, 176, 128, 0.14);
        color: #c9fff0;
    }

    .fcc-main-app-featured-status {
        border-radius: 16px;
        padding: 0.85rem 1rem;
        background: rgba(127, 227, 217, 0.05);
        border: 1px solid rgba(127, 227, 217, 0.08);
        color: #d8f8f3;
    }

    .fcc-links-shell.is-biolink .fcc-main-app-featured-status {
        background: rgba(76, 201, 220, 0.04);
        border-color: rgba(90, 201, 230, 0.06);
        color: #dfeeff;
    }

    .fcc-main-app-featured-status.is-muted {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.06);
        color: #d5e1e2;
    }

    .fcc-links-shell.is-biolink .fcc-main-app-featured-status.is-muted {
        background: rgba(255, 255, 255, 0.025);
        border-color: rgba(107, 145, 221, 0.07);
        color: #aebacf;
    }

    .fcc-main-app-featured-preview {
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 4;
        overflow: hidden;
    }

    .fcc-links-main-workspace {
        background: linear-gradient(180deg, rgba(19, 29, 31, 0.95) 0%, rgba(14, 19, 22, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.1);
        border-radius: 24px;
        padding: 1.25rem 1.35rem;
        box-shadow: 0 20px 42px rgba(0, 0, 0, 0.16);
    }

    .fcc-links-shell.is-biolink .fcc-links-main-workspace {
        background:
            radial-gradient(circle at top left, rgba(72, 220, 214, 0.04) 0%, rgba(72, 220, 214, 0) 28%),
            radial-gradient(circle at 88% 14%, rgba(61, 118, 255, 0.05) 0%, rgba(61, 118, 255, 0) 24%),
            linear-gradient(180deg, rgba(14, 22, 40, 0.98) 0%, rgba(8, 13, 24, 0.99) 100%);
        border-color: rgba(90, 201, 230, 0.08);
        box-shadow: 0 28px 56px rgba(4, 10, 24, 0.30);
    }

    .fcc-links-main-intro {
        margin-bottom: 1.1rem;
        padding: 0.35rem 0 1.15rem;
        border-bottom: 1px solid rgba(127, 227, 217, 0.08);
    }

    .fcc-links-shell.is-biolink .fcc-links-main-intro {
        border-bottom-color: rgba(90, 201, 230, 0.05);
    }

    .fcc-links-main-intro-head {
        position: relative;
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        align-items: flex-start;
        gap: 0;
        padding-right: 0;
    }

    .fcc-links-guide-copy h2,
    .fcc-links-main-copy h2 {
        margin: 0 0 0.35rem;
        color: #f5fbfb;
        font-size: 1.15rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .fcc-links-guide-copy p,
    .fcc-links-main-copy p {
        margin: 0;
        color: #a9bdc1;
        line-height: 1.65;
    }

    .fcc-links-shell.is-biolink .fcc-links-guide-copy p,
    .fcc-links-shell.is-biolink .fcc-links-main-copy p {
        color: #b7c8dd;
    }

    .fcc-links-main-intro .fcc-links-guide-copy {
        max-width: 74rem;
    }

    .fcc-links-main-intro .fcc-links-guide-copy.is-hero {
        min-width: 0;
        max-width: min(100%, 68rem);
    }

    .fcc-links-main-intro .fcc-links-guide-copy h2 {
        font-size: clamp(2rem, 3vw, 3.2rem);
        line-height: 1.02;
        letter-spacing: -0.065em;
        margin-bottom: 0.9rem;
        font-weight: 900;
        max-width: 14ch;
        color: #f8fcfd;
        text-wrap: balance;
    }

    .fcc-links-shell.is-biolink .fcc-links-main-intro .fcc-links-guide-copy h2 {
        color: #f7fbff;
    }

    .fcc-links-main-intro .fcc-links-guide-copy p {
        font-size: 1.14rem;
        line-height: 1.72;
        max-width: 74ch;
        color: #d2e4e8;
        text-wrap: pretty;
    }

    .fcc-links-shell.is-biolink .fcc-links-main-intro .fcc-links-guide-copy p {
        color: #c8d8ea;
    }

    .fcc-links-guide-pills {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
        margin-top: 1.2rem;
    }

    .fcc-links-guide-pill {
        padding: 1rem 1.05rem;
        border-radius: 18px;
        border: 1px solid rgba(127, 227, 217, 0.12);
        background: linear-gradient(180deg, rgba(14, 22, 27, 0.92) 0%, rgba(11, 18, 22, 0.96) 100%);
        min-height: 100%;
    }

    .fcc-links-shell.is-biolink .fcc-links-guide-pill {
        border-color: rgba(84, 166, 255, 0.08);
        background:
            radial-gradient(circle at top left, rgba(40, 194, 146, 0.05) 0%, rgba(40, 194, 146, 0) 28%),
            linear-gradient(180deg, rgba(16, 23, 39, 0.95) 0%, rgba(10, 14, 24, 0.98) 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .fcc-links-guide-pill strong {
        display: block;
        margin-bottom: 0.35rem;
        color: #f4fbfb;
        font-size: 0.96rem;
        font-weight: 700;
    }

    .fcc-links-shell.is-biolink .fcc-links-guide-pill strong {
        color: #f1f8ff;
    }

    .fcc-links-guide-pill span {
        color: #9db3b8;
        font-size: 0.9rem;
        line-height: 1.62;
    }

    .fcc-links-shell.is-biolink .fcc-links-guide-pill span {
        color: #adc2d9;
    }

    .fcc-short-links-guide {
        background: linear-gradient(180deg, rgba(19, 29, 31, 0.95) 0%, rgba(14, 19, 22, 0.98) 100%);
        border: 1px solid rgba(127, 227, 217, 0.1);
        border-radius: 24px;
        padding: 1.3rem 1.35rem;
        box-shadow: 0 20px 42px rgba(0, 0, 0, 0.16);
    }

    .fcc-links-shell.is-short-links .fcc-short-links-guide {
        background:
            radial-gradient(circle at top left, rgba(72, 220, 214, 0.04) 0%, rgba(72, 220, 214, 0) 28%),
            radial-gradient(circle at 88% 14%, rgba(61, 118, 255, 0.05) 0%, rgba(61, 118, 255, 0) 24%),
            linear-gradient(180deg, rgba(14, 22, 40, 0.98) 0%, rgba(8, 13, 24, 0.99) 100%);
        border-color: rgba(90, 201, 230, 0.08);
        box-shadow: 0 28px 56px rgba(4, 10, 24, 0.30);
    }

    .fcc-short-links-guide-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .fcc-short-links-guide-copy h2 {
        margin: 0 0 0.55rem;
        color: #f8fcfd;
        font-size: clamp(1.75rem, 3vw, 2.8rem);
        line-height: 1.02;
        letter-spacing: -0.055em;
        font-weight: 900;
        max-width: 14ch;
    }

    .fcc-links-shell.is-short-links .fcc-short-links-guide-copy h2 {
        color: #f7fbff;
    }

    .fcc-short-links-guide-copy p {
        margin: 0;
        max-width: 72ch;
        color: #d2e4e8;
        line-height: 1.72;
        font-size: 1.04rem;
    }

    .fcc-links-shell.is-short-links .fcc-short-links-guide-copy p {
        color: #c8d8ea;
    }

    .fcc-short-links-guide-total {
        display: inline-flex;
        align-items: center;
        min-height: 2.15rem;
        border-radius: 999px;
        padding: 0.38rem 0.82rem;
        background: rgba(63, 215, 199, 0.12);
        border: 1px solid rgba(63, 215, 199, 0.2);
        color: #b8faf3;
        font-size: 0.78rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .fcc-links-shell.is-short-links .fcc-short-links-guide-total {
        background: rgba(72, 220, 214, 0.06);
        border-color: rgba(72, 220, 214, 0.12);
        color: #c9fcf5;
    }

    .fcc-short-links-guide-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.85rem;
    }

    .fcc-short-links-guide-card {
        padding: 1rem 1.05rem;
        border-radius: 18px;
        border: 1px solid rgba(127, 227, 217, 0.12);
        background: linear-gradient(180deg, rgba(14, 22, 27, 0.92) 0%, rgba(11, 18, 22, 0.96) 100%);
        min-height: 100%;
    }

    .fcc-links-shell.is-short-links .fcc-short-links-guide-card {
        border-color: rgba(84, 166, 255, 0.08);
        background:
            radial-gradient(circle at top left, rgba(40, 194, 146, 0.05) 0%, rgba(40, 194, 146, 0) 28%),
            linear-gradient(180deg, rgba(16, 23, 39, 0.95) 0%, rgba(10, 14, 24, 0.98) 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03);
    }

    .fcc-short-links-guide-card strong {
        display: block;
        margin-bottom: 0.35rem;
        color: #f4fbfb;
        font-size: 0.96rem;
        font-weight: 700;
    }

    .fcc-links-shell.is-short-links .fcc-short-links-guide-card strong {
        color: #f1f8ff;
    }

    .fcc-short-links-guide-card span {
        color: #9db3b8;
        font-size: 0.9rem;
        line-height: 1.62;
    }

    .fcc-links-shell.is-short-links .fcc-short-links-guide-card span {
        color: #adc2d9;
    }

    .fcc-page-guide-rail {
        display: flex;
        justify-content: flex-end;
        margin: 0 0 .72rem;
    }

    .fcc-page-guide-trigger {
        display: inline-flex;
        align-items: center;
        gap: .48rem;
        justify-content: center;
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

    .fcc-page-guide-trigger i {
        color: #8cf6e9;
        font-size: .92em;
    }

    .fcc-page-guide-trigger:hover,
    .fcc-page-guide-trigger:focus {
        color: #ffffff;
        border-color: rgba(111, 244, 228, .42);
        background: linear-gradient(135deg, rgba(44, 214, 199, .2) 0%, rgba(41, 126, 212, .18) 100%);
        box-shadow: 0 16px 30px rgba(63, 215, 199, .12);
        transform: translateY(-1px);
        outline: none;
    }

    .fcc-links-shell.is-biolink .fcc-page-guide-trigger {
        border-color: rgba(92, 201, 231, 0.24);
        background: linear-gradient(135deg, rgba(15, 54, 73, 0.92) 0%, rgba(12, 42, 66, 0.98) 100%);
        color: #effcff;
        box-shadow: 0 12px 24px rgba(4, 14, 25, 0.18), inset 0 1px 0 rgba(255,255,255,.05);
    }

    .fcc-links-shell.is-biolink .fcc-page-guide-trigger i {
        color: #87f3e6;
    }

    .fcc-links-shell.is-biolink .fcc-page-guide-trigger:hover,
    .fcc-links-shell.is-biolink .fcc-page-guide-trigger:focus {
        border-color: rgba(92, 201, 231, 0.36);
        background: linear-gradient(135deg, rgba(18, 62, 83, 0.94) 0%, rgba(14, 48, 74, 0.99) 100%);
        box-shadow: 0 18px 32px rgba(5, 24, 39, 0.24);
    }

    .fcc-links-main-card {
        border-radius: 22px;
        border: 1px solid rgba(127, 227, 217, 0.12);
        background: linear-gradient(180deg, rgba(15, 21, 30, 0.98) 0%, rgba(11, 16, 24, 0.98) 100%);
        padding: 1.2rem;
    }

    .fcc-links-shell.is-biolink .fcc-links-main-card {
        border-color: rgba(84, 166, 255, 0.07);
        background:
            radial-gradient(circle at top left, rgba(72, 220, 214, 0.04) 0%, rgba(72, 220, 214, 0) 30%),
            linear-gradient(180deg, rgba(16, 23, 39, 0.98) 0%, rgba(10, 14, 24, 0.99) 100%);
    }

    .fcc-links-main-card-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .fcc-links-main-app-name {
        color: #f7fbfb;
        font-size: 1.1rem;
        font-weight: 800;
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }

    .fcc-links-shell.is-biolink .fcc-links-main-app-name {
        color: #f7fbff;
    }

    .fcc-links-main-app-url {
        color: #8fb1b5;
        font-size: 0.85rem;
        line-height: 1.45;
        word-break: break-word;
    }

    .fcc-links-shell.is-biolink .fcc-links-main-app-url {
        color: #9fb2ca;
    }

    .fcc-links-main-badges,
    .fcc-links-main-metrics,
    .fcc-links-main-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
    }

    .fcc-links-main-badges {
        margin-bottom: 0.9rem;
    }

    .fcc-links-main-badge,
    .fcc-links-main-metric {
        display: inline-flex;
        align-items: center;
        min-height: 1.95rem;
        border-radius: 999px;
        padding: 0.28rem 0.78rem;
        font-size: 0.74rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .fcc-links-main-badge {
        background: rgba(63, 215, 199, 0.12);
        border: 1px solid rgba(63, 215, 199, 0.22);
        color: #b8faf3;
    }

    .fcc-links-shell.is-biolink .fcc-links-main-badge {
        background: rgba(72, 220, 214, 0.09);
        border-color: rgba(72, 220, 214, 0.16);
        color: #c9fcf5;
    }

    .fcc-links-main-badge.is-primary {
        background: rgba(79, 116, 255, 0.14);
        border-color: rgba(79, 116, 255, 0.2);
        color: #d9e3ff;
    }

    .fcc-links-main-metric {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.07);
        color: #d8e8e9;
    }

    .fcc-links-main-actions .fcc-links-action-btn {
        min-height: 2.6rem;
    }

    .fcc-links-main-actions .fcc-links-action-btn.btn-outline-primary {
        background: rgba(127, 227, 217, 0.05);
    }

    .fcc-links-section-title {
        margin: 0 0 0.4rem;
        color: #f3fbfb;
        font-size: 1rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .fcc-links-section-copy {
        margin: 0 0 0.95rem;
        color: #9eb5b8;
        font-size: 0.9rem;
        line-height: 1.6;
    }

    .fcc-links-main-featured-divider {
        margin: 1.15rem 0 1rem;
        border-top: 1px solid rgba(127, 227, 217, 0.08);
    }

    .fcc-links-main-featured-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .fcc-links-secondary-header {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.15rem 1.3rem 0;
    }

    .fcc-tour-target {
        scroll-margin-top: 6rem;
    }

    .fcc-tour-active-ancestor {
        position: relative !important;
        z-index: 2051 !important;
        overflow: visible !important;
    }

    .fcc-tour-active-target {
        position: relative !important;
        z-index: 2052 !important;
        isolation: isolate;
        transform: translateZ(0);
        filter: brightness(1.06) saturate(1.04);
        box-shadow: 0 0 0 2px rgba(73, 227, 207, .98), 0 0 0 10px rgba(112, 244, 228, .18), 0 18px 54px rgba(7, 19, 38, .34) !important;
        border-radius: 1.35rem !important;
    }

    .fcc-tour-backdrop {
        position: fixed;
        inset: 0;
        z-index: 2050;
        display: none;
        pointer-events: none;
    }

    .fcc-tour-backdrop.is-visible {
        display: block;
    }

    .fcc-tour-backdrop-segment {
        position: fixed;
        background: rgba(2, 8, 23, .58);
        backdrop-filter: blur(3px);
        pointer-events: none;
    }

    .fcc-tour-popover {
        position: fixed;
        z-index: 2055;
        width: min(25rem, calc(100vw - 2rem));
        display: none;
        border-radius: 1.2rem;
        border: 1px solid rgba(147, 197, 253, .22);
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, .18), transparent 30%),
            linear-gradient(180deg, rgba(25, 36, 58, .98), rgba(16, 24, 41, .97));
        box-shadow: 0 30px 80px rgba(2, 8, 23, .44), inset 0 1px 0 rgba(255,255,255,.05);
        padding: 1.05rem 1.05rem 1rem;
    }

    .fcc-tour-popover.is-visible {
        display: block;
    }

    .fcc-tour-progress {
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

    .fcc-tour-title {
        color: #f8fbff;
        font-size: 1.12rem;
        font-weight: 800;
        line-height: 1.3;
        margin-bottom: .45rem;
    }

    .fcc-tour-text {
        color: rgba(236, 244, 255, .94);
        font-size: .94rem;
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .fcc-tour-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .fcc-tour-actions-main {
        display: flex;
        gap: .65rem;
        flex-wrap: wrap;
    }

    .fcc-tour-actions .btn {
        border-radius: .85rem;
    }

    .fcc-tour-actions .btn-link {
        color: rgba(226, 232, 240, .82) !important;
        text-decoration: none;
    }

    .fcc-tour-actions .btn-link:hover,
    .fcc-tour-actions .btn-link:focus {
        color: #ffffff !important;
        text-decoration: none;
    }

    .fcc-tour-actions .btn-outline-light {
        color: #ecf8ff !important;
        border-color: rgba(147, 197, 253, .28) !important;
        background: rgba(59, 130, 246, .12) !important;
    }

    .fcc-tour-actions .btn-outline-light:hover,
    .fcc-tour-actions .btn-outline-light:focus {
        color: #ffffff !important;
        border-color: rgba(147, 197, 253, .48) !important;
        background: rgba(59, 130, 246, .2) !important;
    }

    @media (max-width: 1199px) {
        .fcc-links-guide-pills {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .fcc-short-links-guide-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .fcc-links-main-intro-head {
            grid-template-columns: 1fr;
        }

        .fcc-links-main-intro .fcc-links-guide-copy h2 {
            font-size: clamp(1.85rem, 4.8vw, 2.8rem);
            max-width: 100%;
        }

        .fcc-links-main-intro .fcc-links-guide-copy p {
            font-size: 1.02rem;
            max-width: 100%;
        }
    }

    @media (max-width: 767px) {
        .fcc-links-guide-pills {
            grid-template-columns: 1fr;
        }

        .fcc-short-links-guide-head {
            flex-direction: column;
            align-items: flex-start;
        }

        .fcc-short-links-guide-grid {
            grid-template-columns: 1fr;
        }

        .fcc-page-guide-rail {
            justify-content: stretch;
            margin-bottom: .72rem;
        }

        .fcc-page-guide-trigger {
            width: 100%;
            justify-content: center;
            text-align: center;
            min-height: 2.8rem;
        }

        .fcc-links-heading-row {
            align-items: flex-start;
        }

        .fcc-links-main-intro {
            padding-top: 0.05rem;
        }

        .fcc-links-main-intro .fcc-links-guide-copy h2 {
            font-size: clamp(1.7rem, 7vw, 2.2rem);
            line-height: 1.01;
            margin-bottom: 0.75rem;
        }

        .fcc-links-main-intro .fcc-links-guide-copy p {
            font-size: 0.98rem;
            line-height: 1.65;
        }

        .fcc-links-main-card-top,
        .fcc-links-secondary-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .fcc-tour-popover {
            left: 1rem !important;
            right: 1rem !important;
            width: auto;
            top: auto !important;
            bottom: 1rem;
        }
    }
</style>

<div class="fcc-links-shell <?= $fcc_is_biolink_links ? 'is-biolink' : null ?> <?= $fcc_is_short_links ? 'is-short-links' : null ?>">
<?php if(in_array($fcc_links_type, ['biolink', 'link'], true)): ?>
    <div class="fcc-page-guide-rail">
        <button type="button" class="fcc-page-guide-trigger" data-fcc-start-tour>
            <i class="fas fa-fw fa-route"></i>
            <span><?= $fcc_apps_tour_launch ?></span>
        </button>
    </div>
<?php endif ?>

<div class="fcc-links-header">
<div class="row align-items-center">
    <div class="col-12 col-lg mb-4 mb-lg-0 text-truncate">
        <div class="fcc-links-heading">
            <div class="fcc-links-heading-icon">
                <i class="fas fa-fw <?= isset($data->filters->filters['type']) ? $data->links_types[$data->filters->filters['type']]['icon'] : $data->links_types['link']['icon'] ?>"></i>
            </div>

            <div class="fcc-links-heading-copy">
                <div class="fcc-links-heading-row">
                    <h1 class="text-truncate">
                        <?= isset($data->filters->filters['type']) ? l('links.menu.' . $data->filters->filters['type']) : l('links.header') ?>
                        <span class="ml-1" data-toggle="tooltip" title="<?= l('links.subheader') ?>">
                            <i class="fas fa-fw fa-info-circle text-muted"></i>
                        </span>
                    </h1>

                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-auto d-flex flex-wrap fcc-links-toolbar d-print-none">
        <?php if(isset($data->filters->filters['type'])): ?>
            <?php
            $fcc_create_button_id = $fcc_links_type === 'biolink' ? 'fcc_apps_tour_step_create' : ($fcc_is_short_links ? 'fcc_short_links_tour_step_create' : null);
            $fcc_create_button_is_tour_target = in_array($fcc_links_type, ['biolink', 'link'], true);
            ?>
            <div>
                <button type="button" data-toggle="modal" data-target="<?= '#create_' . $data->filters->filters['type'] ?>" class="btn btn-primary fcc-links-action-btn <?= $fcc_create_button_is_tour_target ? 'fcc-tour-target' : null ?>" <?= $fcc_create_button_id ? 'id="' . $fcc_create_button_id . '"' : null ?>>
                    <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('link.' . $data->filters->filters['type'] . '.name') ?>
                </button>
            </div>

            <?php if(settings()->links->shortener_is_enabled && $data->filters->filters['type'] == 'link'): ?>
                <div>
                    <a href="<?= url('link-create') ?>" class="btn btn-outline-primary fcc-links-action-btn fcc-tour-target" id="fcc_short_links_tour_step_bulk_create" data-toggle="tooltip" title="<?= l('link_create.menu') ?>">
                        <i class="fas fa-fw fa-upload fa-sm"></i>
                    </a>
                </div>
            <?php endif ?>

            <?php if(settings()->links->biolinks_templates_is_enabled && $data->filters->filters['type'] == 'biolink'): ?>
                <div>
                    <a href="<?= url('biolinks-templates') ?>" class="btn btn-outline-primary fcc-links-action-btn fcc-tour-target" id="fcc_apps_tour_step_templates">
                        <i class="fas fa-fw fa-moon fa-sm mr-1"></i> <?= l('biolinks_templates.menu') ?>
                    </a>
                </div>
            <?php endif ?>
        <?php else: ?>
            <div>
                <?php
                $enabled_links = [];
                if(settings()->links->biolinks_is_enabled) $enabled_links[] = 'biolink';
                if(settings()->links->shortener_is_enabled) $enabled_links[] = 'link';
                if(settings()->links->files_is_enabled) $enabled_links[] = 'file';
                if(settings()->links->vcards_is_enabled) $enabled_links[] = 'vcard';
                if(settings()->links->events_is_enabled) $enabled_links[] = 'event';
                if(settings()->links->static_is_enabled) $enabled_links[] = 'static';
                ?>

                <?php if(count($enabled_links) > 1): ?>

                    <div class="dropdown">
                    <button type="button" data-toggle="dropdown" data-boundary="viewport" class="btn btn-primary dropdown-toggle dropdown-toggle-simple fcc-links-action-btn">
                        <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('links.create') ?>
                    </button>

                    <div class="dropdown-menu dropdown-menu-right">
                        <?php if(settings()->links->biolinks_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_biolink">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['biolink']['color'] ?>"></i>

                                <?= l('link.biolink.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->shortener_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_link">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['link']['color'] ?>"></i>

                                <?= l('link.link.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->files_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_file">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['file']['color'] ?>"></i>

                                <?= l('link.file.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->vcards_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_vcard">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['vcard']['color'] ?>"></i>

                                <?= l('link.vcard.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->events_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_event">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['event']['color'] ?>"></i>

                                <?= l('link.event.name') ?>
                            </a>
                        <?php endif ?>

                        <?php if(settings()->links->static_is_enabled): ?>
                            <a href="#" class="dropdown-item" data-toggle="modal" data-target="#create_static">
                                <i class="fas fa-fw fa-circle fa-sm mr-2" style="color: <?= $data->links_types['static']['color'] ?>"></i>

                                <?= l('link.static.name') ?>
                            </a>
                        <?php endif ?>
                    </div>
                </div>

                <?php elseif(count($enabled_links) == 1): ?>

                    <div>
                        <button type="button" data-toggle="modal" data-target="<?= '#create_' . reset($enabled_links) ?>" class="btn btn-primary fcc-links-action-btn">
                            <i class="fas fa-fw fa-plus-circle fa-sm mr-1"></i> <?= l('link.' . reset($enabled_links) . '.name') ?>
                        </button>
                    </div>

                <?php endif ?>
            </div>
        <?php endif ?>

        <div>
            <div class="dropdown">
                <button type="button" class="btn btn-light dropdown-toggle-simple fcc-links-action-btn <?= !empty($data->links) ? null : 'disabled' ?>" data-toggle="dropdown" data-boundary="viewport" data-tooltip title="<?= l('global.export') ?>" data-tooltip-hide-on-click>
                    <i class="fas fa-fw fa-sm fa-download"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-right d-print-none">
                    <a href="<?= url('links?' . $data->filters->get_get() . '&export=csv')  ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->csv ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->csv ? null : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-file-csv mr-2"></i> <?= sprintf(l('global.export_to'), 'CSV') ?>
                    </a>
                    <a href="<?= url('links?' . $data->filters->get_get() . '&export=json') ?>" target="_blank" class="dropdown-item <?= $this->user->plan_settings->export->json ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->json ? null : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-file-code mr-2"></i> <?= sprintf(l('global.export_to'), 'JSON') ?>
                    </a>
                    <a href="#" class="dropdown-item <?= $this->user->plan_settings->export->pdf ? null : 'disabled pointer-events-all' ?>" <?= $this->user->plan_settings->export->pdf ? 'onclick="event.preventDefault(); window.print();"' : get_plan_feature_disabled_info() ?>>
                        <i class="fas fa-fw fa-sm fa-file-pdf mr-2"></i> <?= sprintf(l('global.export_to'), 'PDF') ?>
                    </a>
                </div>
            </div>
        </div>

        <div>
            <div class="dropdown">
                <button type="button" class="btn <?= $data->filters->has_applied_filters ? 'btn-dark' : 'btn-light' ?> filters-button dropdown-toggle-simple fcc-links-action-btn <?= !empty($data->links) || $data->filters->has_applied_filters ? null : 'disabled' ?>" <?= $fcc_is_short_links ? 'id="fcc_short_links_tour_step_filters"' : null ?> data-toggle="dropdown" data-boundary="viewport" data-tooltip data-html="true" title="<?= l('global.filters.tooltip') ?>" data-tooltip-hide-on-click>
                    <i class="fas fa-fw fa-sm fa-filter"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-right filters-dropdown">
                    <div class="dropdown-header d-flex justify-content-between">
                        <span class="h6 m-0"><?= l('global.filters.header') ?></span>

                        <?php if($data->filters->has_applied_filters): ?>
                            <a href="<?= url(\Altum\Router::$original_request) ?>" class="text-muted"><?= l('global.filters.reset') ?></a>
                        <?php endif ?>
                    </div>

                    <div class="dropdown-divider"></div>

                    <form action="<?= url('links') ?>" method="get" role="form">
                        <div class="form-group px-4">
                            <label for="filters_search" class="small"><?= l('global.filters.search') ?></label>
                            <input type="search" name="search" id="filters_search" class="form-control form-control-sm" value="<?= $data->filters->search ?>" />
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_search_by" class="small"><?= l('global.filters.search_by') ?></label>
                            <select name="search_by" id="filters_search_by" class="custom-select custom-select-sm">
                                <option value="url" <?= $data->filters->search_by == 'url' ? 'selected="selected"' : null ?>><?= l('links.filters.url') ?></option>
                                <option value="location_url" <?= $data->filters->search_by == 'location_url' ? 'selected="selected"' : null ?>><?= l('links.filters.location_url') ?></option>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_is_enabled" class="small"><?= l('global.status') ?></label>
                            <select name="is_enabled" id="filters_is_enabled" class="custom-select custom-select-sm">
                                <option value=""><?= l('global.all') ?></option>
                                <option value="1" <?= isset($data->filters->filters['is_enabled']) && $data->filters->filters['is_enabled'] == '1' ? 'selected="selected"' : null ?>><?= l('global.active') ?></option>
                                <option value="0" <?= isset($data->filters->filters['is_enabled']) && $data->filters->filters['is_enabled'] == '0' ? 'selected="selected"' : null ?>><?= l('global.disabled') ?></option>
                            </select>
                        </div>

                        <?php if(settings()->links->projects_is_enabled): ?>
                        <div class="form-group px-4">
                            <div class="d-flex justify-content-between">
                                <label for="filters_project_id" class="small"><?= l('projects.project_id') ?></label>
                                <a href="<?= url('projects') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('global.create') ?></a>
                            </div>
                            <select name="project_id" id="filters_project_id" class="custom-select custom-select-sm">
                                <option value=""><?= l('global.all') ?></option>
                                <?php foreach($data->projects as $row): ?>
                                    <option value="<?= $row->project_id ?>" <?= isset($data->filters->filters['project_id']) && $data->filters->filters['project_id'] == $row->project_id ? 'selected="selected"' : null ?>><?= $row->name ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <?php endif ?>

                        <?php if(settings()->links->domains_is_enabled): ?>
                            <div class="form-group px-4">
                                <div class="d-flex justify-content-between">
                                    <label for="filters_domain_id" class="small"><?= l('domains.domain_id') ?></label>
                                    <a href="<?= url('domain-create') ?>" target="_blank" class="small mb-2"><i class="fas fa-fw fa-sm fa-plus mr-1"></i> <?= l('global.create') ?></a>
                                </div>
                                <select name="domain_id" id="filters_domain_id" class="custom-select custom-select-sm">
                                    <option value=""><?= l('global.all') ?></option>
                                    <?php foreach($data->domains as $domain_id => $domain): ?>
                                        <option value="<?= $domain_id ?>" <?= isset($data->filters->filters['domain_id']) && $data->filters->filters['domain_id'] == $domain_id ? 'selected="selected"' : null ?>><?= $domain->host ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        <?php endif ?>

                        <div class="form-group px-4">
                            <label for="filters_type" class="small"><?= l('global.type') ?></label>
                            <select name="type" id="filters_type" class="custom-select custom-select-sm">
                                <option value=""><?= l('global.all') ?></option>
                                <?php if(settings()->links->biolinks_is_enabled): ?>
                                    <option value="biolink" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'biolink' ? 'selected="selected"' : null ?>><?= l('links.menu.biolink') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->shortener_is_enabled): ?>
                                    <option value="link" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'link' ? 'selected="selected"' : null ?>><?= l('links.menu.link') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->files_is_enabled): ?>
                                    <option value="file" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'file' ? 'selected="selected"' : null ?>><?= l('links.menu.file') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->vcards_is_enabled): ?>
                                    <option value="vcard" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'vcard' ? 'selected="selected"' : null ?>><?= l('links.menu.vcard') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->events_is_enabled): ?>
                                    <option value="event" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'event' ? 'selected="selected"' : null ?>><?= l('links.menu.event') ?></option>
                                <?php endif ?>

                                <?php if(settings()->links->static_is_enabled): ?>
                                    <option value="static" <?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'static' ? 'selected="selected"' : null ?>><?= l('links.menu.static') ?></option>
                                <?php endif ?>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_order_by" class="small"><?= l('global.filters.order_by') ?></label>
                            <select name="order_by" id="filters_order_by" class="custom-select custom-select-sm">
                                <option value="link_id" <?= $data->filters->order_by == 'link_id' ? 'selected="selected"' : null ?>><?= l('global.id') ?></option>
                                <option value="datetime" <?= $data->filters->order_by == 'datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_datetime') ?></option>
                                <option value="last_datetime" <?= $data->filters->order_by == 'last_datetime' ? 'selected="selected"' : null ?>><?= l('global.filters.order_by_last_datetime') ?></option>
                                <option value="clicks" <?= $data->filters->order_by == 'clicks' ? 'selected="selected"' : null ?>><?= l('links.filters.order_by_clicks') ?></option>
                                <option value="url" <?= $data->filters->order_by == 'url' ? 'selected="selected"' : null ?>><?= l('links.filters.url') ?></option>
                            </select>
                        </div>

                        <div class="form-group px-4">
                            <label for="filters_order_type" class="small"><?= l('global.filters.order_type') ?></label>
                            <select name="order_type" id="filters_order_type" class="custom-select custom-select-sm">
                                <option value="ASC" <?= $data->filters->order_type == 'ASC' ? 'selected="selected"' : null ?>><?= l('global.filters.order_type_asc') ?></option>
                                <option value="DESC" <?= $data->filters->order_type == 'DESC' ? 'selected="selected"' : null ?>><?= l('global.filters.order_type_desc') ?></option>
                            </select>
                        </div>

                        <?php if(!$fcc_is_biolink_links): ?>
                            <div class="form-group px-4">
                                <label for="filters_results_per_page" class="small"><?= l('global.filters.results_per_page') ?></label>
                                <select name="results_per_page" id="filters_results_per_page" class="custom-select custom-select-sm">
                                    <?php foreach($data->filters->allowed_results_per_page as $key): ?>
                                        <option value="<?= $key ?>" <?= $data->filters->results_per_page == $key ? 'selected="selected"' : null ?>><?= $key ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                        <?php endif ?>

                        <div class="form-group px-4 mt-4">
                            <button type="submit" name="submit" class="btn btn-sm btn-primary btn-block"><?= l('global.submit') ?></button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div>
            <button id="bulk_enable" type="button" class="btn btn-light fcc-links-action-btn" data-toggle="tooltip" title="<?= l('global.bulk_actions') ?>"><i class="fas fa-fw fa-sm fa-list"></i></button>

            <div id="bulk_group" class="btn-group d-none" role="group">
                <div class="btn-group dropdown" role="group">
                    <button id="bulk_actions" type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false">
                        <?= l('global.bulk_actions') ?> <span id="bulk_counter" class="d-none"></span>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="bulk_actions">
                        <a href="#" class="dropdown-item" data-toggle="modal" data-target="#bulk_delete_modal"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>
                    </div>
                </div>

                <button id="bulk_disable" type="button" class="btn btn-secondary" data-toggle="tooltip" title="<?= l('global.close') ?>"><i class="fas fa-fw fa-times"></i></button>
            </div>
        </div>
</div>
</div>
</div>

<?php if($fcc_is_short_links): ?>
    <div class="fcc-short-links-guide">
        <div class="fcc-short-links-guide-head">
            <div class="fcc-short-links-guide-copy">
                <h2><?= $fcc_short_links_guide_title ?></h2>
                <p><?= $fcc_short_links_guide_text ?></p>
            </div>
            <div class="fcc-short-links-guide-total"><?= $fcc_short_links_total_label ?> <?= nr(count($data->links ?? [])) ?></div>
        </div>

        <div class="fcc-short-links-guide-grid">
            <div class="fcc-short-links-guide-card">
                <strong><?= l('links.short_links.guide_card_1_title') ?></strong>
                <span><?= l('links.short_links.guide_card_1_text') ?></span>
            </div>
            <div class="fcc-short-links-guide-card">
                <strong><?= l('links.short_links.guide_card_2_title') ?></strong>
                <span><?= l('links.short_links.guide_card_2_text') ?></span>
            </div>
            <div class="fcc-short-links-guide-card">
                <strong><?= l('links.short_links.guide_card_3_title') ?></strong>
                <span><?= l('links.short_links.guide_card_3_text') ?></span>
            </div>
            <div class="fcc-short-links-guide-card">
                <strong><?= l('links.short_links.guide_card_4_title') ?></strong>
                <span><?= l('links.short_links.guide_card_4_text') ?></span>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if(isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'biolink' && !$fcc_main_biolink_row): ?>
    <div class="fcc-links-guide-strip">
        <div class="fcc-links-main-intro-head">
            <div class="fcc-links-guide-copy is-hero">
                <h2><?= $fcc_workspace_guide_title ?></h2>
                <p><?= $fcc_workspace_guide_text ?></p>
            </div>
        </div>

        <div class="fcc-links-guide-pills">
            <div class="fcc-links-guide-pill">
                <strong><?= $fcc_links_is_hr ? 'Nova aplikacija' : 'New app' ?></strong>
                <span><?= $fcc_links_is_hr ? 'Kreiraj novu FCC aplikaciju za tržište, ponudu ili posebnu kampanju.' : 'Create a new FCC app for a market, offer or special campaign.' ?></span>
            </div>
            <div class="fcc-links-guide-pill">
                <strong><?= $fcc_links_is_hr ? 'Glavna aplikacija' : 'Main app' ?></strong>
                <span><?= $fcc_links_is_hr ? 'Samo glavna aplikacija vodi javni prikaz i AI analizu povezanu s planom rasta.' : 'Only the main app controls the public showcase and AI review connected to the growth plan.' ?></span>
            </div>
            <div class="fcc-links-guide-pill">
                <strong><?= $fcc_links_is_hr ? 'Predlošci' : 'Templates' ?></strong>
                <span><?= $fcc_links_is_hr ? 'Brže započni novu aplikaciju iz FCC predloška i prilagodi je svojoj ponudi.' : 'Start faster from an FCC template and adapt it to your own offer.' ?></span>
            </div>
            <div class="fcc-links-guide-pill">
                <strong><?= $fcc_links_is_hr ? 'Dodatne verzije' : 'Extra versions' ?></strong>
                <span><?= $fcc_links_is_hr ? 'Dodatne aplikacije koristi za posebne scenarije, bez miješanja s glavnom aplikacijom.' : 'Use extra apps for specific scenarios without mixing them with your main app.' ?></span>
            </div>
        </div>
    </div>
<?php endif ?>

<?php if(isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'biolink' && $fcc_main_biolink_row): ?>
    <div class="fcc-links-main-workspace">
        <div class="fcc-links-main-intro">
            <div class="fcc-links-main-intro-head">
                <div class="fcc-links-guide-copy is-hero">
                    <h2><?= $fcc_workspace_guide_title ?></h2>
                    <p><?= $fcc_workspace_guide_text ?></p>
                </div>
            </div>

            <div class="fcc-links-guide-pills">
                <div class="fcc-links-guide-pill">
                    <strong><?= $fcc_links_is_hr ? 'Nova aplikacija' : 'New app' ?></strong>
                    <span><?= $fcc_links_is_hr ? 'Kreiraj novu FCC aplikaciju za tržište, ponudu ili posebnu kampanju.' : 'Create a new FCC app for a market, offer or special campaign.' ?></span>
                </div>
                <div class="fcc-links-guide-pill">
                    <strong><?= $fcc_links_is_hr ? 'Glavna aplikacija' : 'Main app' ?></strong>
                    <span><?= $fcc_links_is_hr ? 'Samo glavna aplikacija vodi javni prikaz i AI analizu povezanu s planom rasta.' : 'Only the main app controls the public showcase and AI review connected to the growth plan.' ?></span>
                </div>
                <div class="fcc-links-guide-pill">
                    <strong><?= $fcc_links_is_hr ? 'Predlošci' : 'Templates' ?></strong>
                    <span><?= $fcc_links_is_hr ? 'Brže započni novu aplikaciju iz FCC predloška i prilagodi je svojoj ponudi.' : 'Start faster from an FCC template and adapt it to your own offer.' ?></span>
                </div>
                <div class="fcc-links-guide-pill">
                    <strong><?= $fcc_links_is_hr ? 'Dodatne verzije' : 'Extra versions' ?></strong>
                    <span><?= $fcc_links_is_hr ? 'Dodatne aplikacije koristi za posebne scenarije, bez miješanja s glavnom aplikacijom.' : 'Use extra apps for specific scenarios without mixing them with your main app.' ?></span>
                </div>
            </div>
        </div>
        <div class="fcc-links-main-featured-divider"></div>

        <div class="fcc-links-main-copy mb-3">
            <h2><?= $fcc_main_workspace_title ?></h2>
            <p class="fcc-links-section-copy mb-0"><?= $fcc_main_workspace_text ?></p>
        </div>

        <div class="fcc-links-main-featured-stack">
            <div class="fcc-links-main-card fcc-tour-target" id="fcc_apps_tour_step_main_app">
                <div class="fcc-links-main-card-top">
                    <div>
                        <div class="fcc-links-main-app-name"><?= htmlspecialchars((string) $fcc_main_biolink_row->url, ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="fcc-links-main-app-url"><?= htmlspecialchars(remove_url_protocol_from_url((string) $fcc_main_biolink_row->full_url), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="fcc-links-main-badges">
                        <span class="fcc-links-main-badge is-primary"><?= $fcc_links_is_hr ? 'Glavna aplikacija' : 'Main app' ?></span>
                        <?php if(!empty($fcc_main_featured['opt_in'])): ?>
                            <span class="fcc-links-main-badge"><?= $fcc_links_is_hr ? 'Javni prikaz uključen' : 'Public showcase on' ?></span>
                        <?php endif ?>
                    </div>
                </div>

                <?php if(!empty($fcc_main_biolink_row->app_review_quality_payload)): ?>
                    <?php $fcc_main_quality_payload = $fcc_main_biolink_row->app_review_quality_payload; ?>
                    <div class="fcc-links-main-metrics mb-3">
                        <span class="fcc-links-main-metric"><?= $fcc_main_quality ?> <?= nr((int) ($fcc_main_quality_payload['score'] ?? 0)) ?></span>
                        <span class="fcc-links-main-metric"><?= l('links.app_review_metric_shop_short') ?> <?= nr((int) (($fcc_main_quality_payload['performance']['shop_contacts_30d'] ?? 0))) ?></span>
                        <span class="fcc-links-main-metric"><?= l('links.app_review_metric_whatsapp_short') ?> <?= nr((int) (($fcc_main_quality_payload['performance']['whatsapp_contacts_30d'] ?? 0))) ?></span>
                        <span class="fcc-links-main-metric"><?= l('links.app_review_metric_products_short') ?> <?= nr((int) (($fcc_main_quality_payload['performance']['product_clicks_30d'] ?? 0))) ?></span>
                        <span class="fcc-links-main-metric"><?= l('links.app_review_metric_funnel_short') ?> <?= nr((int) (($fcc_main_quality_payload['performance']['funnel_registrations_30d'] ?? 0))) ?></span>
                    </div>
                <?php endif ?>

                <div class="fcc-links-main-actions">
                    <a href="<?= url('link/' . $fcc_main_biolink_row->link_id) ?>" class="btn btn-primary fcc-links-action-btn"><?= $fcc_main_edit ?></a>
                    <a href="<?= url('link/' . $fcc_main_biolink_row->link_id . '/statistics') ?>" class="btn btn-outline-primary fcc-links-action-btn fcc-tour-target" id="fcc_apps_tour_step_stats"><?= $fcc_main_stats ?></a>
                    <button
                        type="button"
                        class="btn btn-outline-primary fcc-links-action-btn fcc-tour-target"
                        id="fcc_apps_tour_step_copy_link"
                        title="<?= l('global.clipboard_copy') ?>"
                        aria-label="<?= l('global.clipboard_copy') ?>"
                        data-copy="<?= l('global.clipboard_copy') ?>"
                        data-copied="<?= l('global.clipboard_copied') ?>"
                        data-clipboard-text="<?= htmlspecialchars((string) $fcc_main_biolink_row->full_url, ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <i class="fas fa-fw fa-copy fa-sm mr-1"></i> <?= $fcc_main_copy ?>
                    </button>
                    <?php if($data->app_review_is_accessible): ?>
                        <a href="<?= htmlspecialchars((string) ($fcc_main_biolink_row->app_review_page_url ?? ($data->app_review_page_url . '?app_review_selected_link_id=' . (int) $fcc_main_biolink_row->link_id)), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary fcc-links-action-btn"><?= l('ai_plan.app_review_menu') ?></a>
                    <?php endif ?>
                </div>
            </div>

            <?php if($fcc_main_featured): ?>
                <div class="fcc-main-app-featured-card fcc-tour-target" id="fcc_apps_tour_step_public_showcase">
                    <h3 class="fcc-links-section-title"><?= $fcc_main_header ?></h3>
                    <p class="fcc-links-section-copy"><?= $fcc_main_subheader ?></p>

                    <form action="<?= url('links?type=biolink') ?>" method="post" role="form">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                        <input type="hidden" name="fcc_main_biolink_featured_settings" value="1" />

                        <div class="form-group custom-control custom-switch mb-3">
                            <input id="fcc_featured_opt_in" name="fcc_featured_opt_in" type="checkbox" class="custom-control-input" <?= !empty($fcc_main_featured['opt_in']) ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="fcc_featured_opt_in"><?= $fcc_main_toggle ?></label>
                        </div>

                        <div class="form-group">
                            <label for="fcc_featured_public_market"><i class="fas fa-fw fa-sm fa-globe-europe text-muted mr-1"></i> <?= $fcc_main_market ?></label>
                            <input type="text" id="fcc_featured_public_market" name="fcc_featured_public_market" class="form-control" value="<?= $_POST['fcc_featured_public_market'] ?? ($fcc_main_featured['public_market'] ?? '') ?>" maxlength="64" />
                        </div>

                        <div class="form-group mb-3">
                            <label for="fcc_featured_public_summary"><i class="fas fa-fw fa-sm fa-align-left text-muted mr-1"></i> <?= $fcc_main_summary ?></label>
                            <textarea id="fcc_featured_public_summary" name="fcc_featured_public_summary" class="form-control" rows="4" maxlength="220"><?= $_POST['fcc_featured_public_summary'] ?? ($fcc_main_featured['public_summary'] ?? '') ?></textarea>
                            <small class="form-text text-muted"><?= $fcc_main_summary_help ?></small>
                        </div>

                        <div class="fcc-main-app-featured-status <?= empty($fcc_main_featured['opt_in']) ? 'is-muted' : null ?>">
                            <div class="font-weight-bold mb-2"><?= !empty($fcc_main_featured['opt_in']) ? $fcc_main_status_on : $fcc_main_status_off ?></div>

                            <?php if(empty($fcc_main_featured['is_approved'])): ?>
                                <div class="small text-muted mb-3"><?= $fcc_main_admin_hidden ?></div>
                            <?php endif ?>

                            <?php if(!empty($fcc_main_featured['public_market'])): ?>
                                <div class="fcc-main-app-featured-meta mb-3">
                                    <span class="fcc-main-app-featured-pill"><?= $fcc_main_market ?>: <?= $fcc_main_featured['public_market'] ?></span>
                                </div>
                            <?php endif ?>

                            <?php if(!empty($fcc_main_featured['feature_labels'])): ?>
                                <div class="small text-uppercase text-muted font-weight-bold mb-2"><?= $fcc_main_detected ?></div>
                                <div class="fcc-main-app-featured-meta mb-3">
                                    <?php $fcc_main_visible_tags = array_slice($fcc_main_featured['feature_labels'], 0, $fcc_main_visible_tags_limit); ?>
                                    <?php $fcc_main_remaining_tags = max(0, count($fcc_main_featured['feature_labels']) - count($fcc_main_visible_tags)); ?>

                                    <?php foreach($fcc_main_visible_tags as $feature_label): ?>
                                        <span class="fcc-main-app-featured-tag"><?= $feature_label ?></span>
                                    <?php endforeach ?>

                                    <?php if($fcc_main_remaining_tags > 0): ?>
                                        <span class="fcc-main-app-featured-pill"><?= $fcc_links_is_hr ? '+ još ' . $fcc_main_remaining_tags : '+ ' . $fcc_main_remaining_tags . ' more' ?></span>
                                    <?php endif ?>
                                </div>
                            <?php endif ?>

                            <div class="small text-muted mb-3 fcc-main-app-featured-preview">
                                <?= !empty(trim((string) ($fcc_main_featured['public_summary'] ?? ''))) ? $fcc_main_featured['public_summary'] : $fcc_main_auto_summary ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary fcc-links-action-btn mt-3"><?= $fcc_main_submit ?></button>
                    </form>
                </div>
            <?php endif ?>
        </div>
    </div>
<?php endif ?>

<?php if ($fcc_has_renderable_links): ?>

    <form id="table" action="<?= SITE_URL . 'links/bulk' ?>" method="post" role="form">
        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
        <input type="hidden" name="type" value="" data-bulk-type />
        <input type="hidden" name="original_request" value="<?= base64_encode(\Altum\Router::$original_request) ?>" />
        <input type="hidden" name="original_request_query" value="<?= base64_encode(\Altum\Router::$original_request_query) ?>" />

        <div class="fcc-links-table-card" <?= $fcc_is_short_links ? 'id="fcc_short_links_tour_step_list"' : null ?>>
        <?php if(isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'biolink'): ?>
            <div class="fcc-links-secondary-header fcc-tour-target" id="fcc_apps_tour_step_extra_apps">
                <div>
                    <h2 class="fcc-links-section-title mb-1"><?= $fcc_secondary_apps_title ?></h2>
                    <p class="fcc-links-section-copy mb-0"><?= $fcc_secondary_apps_text ?></p>
                </div>
                <div class="text-muted small"><?= $fcc_links_is_hr ? 'Ukupno: ' : 'Total: ' ?><?= nr(count($fcc_additional_links)) ?></div>
            </div>
        <?php endif ?>
        <?php if(!empty($fcc_table_links)): ?>
        <div class="table-responsive table-custom-container">
            <table class="table table-custom">
                <thead>
                <tr>
                    <th data-bulk-table class="d-none">
                        <div class="custom-control custom-checkbox">
                            <input id="bulk_select_all" type="checkbox" class="custom-control-input" />
                            <label class="custom-control-label" for="bulk_select_all"></label>
                        </div>
                    </th>
                    <th><?= isset($data->filters->filters['type']) && $data->filters->filters['type'] == 'biolink' ? $fcc_secondary_header : l('link.link') ?></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>

                <?php $fcc_short_link_row_index = 0; ?>
                <?php foreach($fcc_table_links as $row): ?>
                    <!-- Custom code: FC-2026-02-24: lock main NFC biolink deletion -->
                    <?php $is_main_biolink = $row->biolink_id && (int) $row->biolink_id === (int) $row->link_id; ?>
                    <!-- /Custom code: FC-2026-02-24 -->
                    <tr <?= $fcc_is_short_links && $fcc_short_link_row_index === 0 ? 'id="fcc_short_links_tour_step_row"' : null ?>>
                        <td data-bulk-table class="d-none">
                            <div class="custom-control custom-checkbox">
                                <!-- Custom code: FC-2026-02-24: lock main NFC biolink deletion -->
                                <input id="selected_link_id_<?= $row->link_id ?>" type="checkbox" class="custom-control-input" name="selected[]" value="<?= $row->link_id ?>" <?= $is_main_biolink ? 'disabled="disabled"' : null ?> title="<?= $is_main_biolink ? l('link_delete_modal.error_message.main_biolink_locked') : '' ?>" />
                                <!-- /Custom code: FC-2026-02-24 -->
                                <label class="custom-control-label" for="selected_link_id_<?= $row->link_id ?>"></label>
                            </div>
                        </td>

                        <td class="text-nowrap">
                            <div class="d-flex align-items-center">

                                <?php if($row->type == 'biolink' && $row->settings->favicon): ?>
                                    <img src="<?= \Altum\Uploads::get_full_url('favicons') . $row->settings->favicon ?>" class="link-type-icon justify-content-center mr-3 d-flex align-items-center rounded-pill" data-toggle="tooltip" title="<?= l('link.' . $row->type . '.name') ?>" loading="lazy" />
                                <?php else: ?>
                                <div class="link-type-icon justify-content-center mr-3 d-flex align-items-center rounded-pill" style="background-color: <?= $data->links_types[$row->type]['color'] ?>" data-toggle="tooltip" title="<?= l('link.' . $row->type . '.name') ?>">
                                    <i class="<?= $data->links_types[$row->type]['icon'] ?> text-white"></i>
                                </div>
                                <?php endif ?>

                                <div class="d-flex flex-column min-width-0">
                                    <div class="d-inline-block text-truncate">
                                        <a href="<?= url('link/' . $row->link_id) ?>" class="font-weight-500"><?= $row->url ?></a>
                                        <?php if($row->type == 'biolink' && $row->is_verified): ?>
                                            <span data-toggle="tooltip" title="<?= l('link.biolink.verified') ?>"><i class="fas fa-fw fa-xs fa-check-circle" style="color: #0086ff"></i></span>
                                        <?php endif ?>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <span class="d-inline-block text-truncate small">

                                        <?php if(!empty($row->location_url)): ?>
                                            <img referrerpolicy="no-referrer" src="<?= get_favicon_url_from_domain(parse_url($row->location_url, PHP_URL_HOST)) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />
                                            <a href="<?= $row->location_url ?>" class="text-muted" title="<?= remove_url_protocol_from_url($row->location_url) ?>" target="_blank" rel="noreferrer"><?= string_truncate(remove_url_protocol_from_url($row->location_url), 32) ?></a>
                                        <?php else: ?>
                                            <img src="<?= isset($row->settings->favicon) && $row->settings->favicon ? \Altum\Uploads::get_full_url('favicons') . $row->settings->favicon : get_favicon_url_from_domain(parse_url($row->full_url, PHP_URL_HOST)) ?>" class="img-fluid icon-favicon-small mr-1" loading="lazy" />
                                            <a href="<?= $row->full_url ?>" class="text-muted" title="<?= remove_url_protocol_from_url($row->full_url) ?>" target="_blank" rel="noreferrer"><?= string_truncate(remove_url_protocol_from_url($row->full_url), 32) ?></a>
                                        <?php endif ?>

                                        </span>
                                    </div>

                                    <?php if($row->type === 'biolink' && !empty($row->app_review_quality_payload)): ?>
                                        <?php $quality = $row->app_review_quality_payload; ?>
                                        <div class="fcc-links-app-quality-inline">
                                            <span class="fcc-links-app-quality-score"><?= l('links.app_review_quality_short') ?> <?= nr((int) ($quality['score'] ?? 0)) ?></span>
                                            <span class="fcc-links-app-quality-level"><?= htmlspecialchars((string) ($quality['level_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if($is_main_biolink && $data->app_review_is_accessible): ?>
                                                <a href="<?= htmlspecialchars((string) ($row->app_review_page_url ?? ($data->app_review_page_url . '?app_review_selected_link_id=' . (int) $row->link_id)), ENT_QUOTES, 'UTF-8') ?>" class="fcc-links-app-quality-cta"><?= l('ai_plan.app_review_menu') ?></a>
                                            <?php endif ?>
                                        </div>
                                        <div class="fcc-links-app-quality-meta">
                                            <span class="fcc-links-app-quality-stat"><?= l('links.app_review_metric_shop_short') ?> <?= nr((int) (($quality['performance']['shop_contacts_30d'] ?? 0))) ?></span>
                                            <span class="fcc-links-app-quality-stat"><?= l('links.app_review_metric_whatsapp_short') ?> <?= nr((int) (($quality['performance']['whatsapp_contacts_30d'] ?? 0))) ?></span>
                                            <span class="fcc-links-app-quality-stat"><?= l('links.app_review_metric_products_short') ?> <?= nr((int) (($quality['performance']['product_clicks_30d'] ?? 0))) ?></span>
                                            <span class="fcc-links-app-quality-stat"><?= l('links.app_review_metric_funnel_short') ?> <?= nr((int) (($quality['performance']['funnel_registrations_30d'] ?? 0))) ?></span>
                                        </div>
                                    <?php endif ?>
                                </div>

                            </div>
                        </td>

                        <td class="text-nowrap">
                            <?php if(settings()->links->projects_is_enabled): ?>
                            <div class="mx-2">
                                <?php if($row->project_id && isset($data->projects[$row->project_id])): ?>
                                    <a href="<?= url('links?project_id=' . $row->project_id) ?>" class="text-decoration-none" data-toggle="tooltip" title="<?= l('projects.project_id') ?>">
                                        <span class="badge badge-light" style="color: <?= $data->projects[$row->project_id]->color ?> !important;">
                                            <?= $data->projects[$row->project_id]->name ?>
                                        </span>
                                    </a>
                                <?php endif ?>
                            </div>
                            <?php endif ?>

                            <div class="mx-2">
                                <a href="<?= url('link/' . $row->link_id . '/statistics') ?>">
                                    <span data-toggle="tooltip" title="<?= l('links.clicks') ?>"><span class="badge badge-light"><i class="fas fa-fw fa-sm fa-chart-bar mr-1"></i> <?= nr($row->clicks) ?></span></span>
                                </a>
                            </div>
                        </td>

                        <td class="text-nowrap text-muted">
                            <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.datetime_tooltip'), '<br />' . \Altum\Date::get($row->datetime, 2) . '<br /><small>' . \Altum\Date::get($row->datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->datetime) . ')</small>') ?>">
                                <i class="fas fa-fw fa-calendar text-muted"></i>
                            </span>

                            <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.last_datetime_tooltip'), ($row->last_datetime ? '<br />' . \Altum\Date::get($row->last_datetime, 2) . '<br /><small>' . \Altum\Date::get($row->last_datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->last_datetime) . ')</small>' : '<br />' . l('global.na'))) ?>">
                                <i class="fas fa-fw fa-history text-muted"></i>
                            </span>
                        </td>

                        <td class="text-nowrap">
                            <div class="d-flex align-items-center justify-content-end" <?= $fcc_is_short_links && $fcc_short_link_row_index === 0 ? 'id="fcc_short_links_tour_step_actions"' : null ?>>

                                <div class="custom-control custom-switch" data-toggle="tooltip" title="<?= l('links.is_enabled_tooltip') ?>">
                                    <input
                                            type="checkbox"
                                            class="custom-control-input"
                                            id="link_is_enabled_<?= $row->link_id ?>"
                                            data-row-id="<?= $row->link_id ?>"
                                            onchange="ajax_call_helper(event, 'link-ajax', 'is_enabled_toggle')"
                                        <?= $row->is_enabled ? 'checked="checked"' : null ?>
                                    >
                                    <label class="custom-control-label" for="link_is_enabled_<?= $row->link_id ?>"></label>
                                </div>

                                <button
                                        id="url_copy"
                                        type="button"
                                        class="btn btn-link text-secondary"
                                        data-toggle="tooltip"
                                        title="<?= l('global.clipboard_copy') ?>"
                                        aria-label="<?= l('global.clipboard_copy') ?>"
                                        data-copy="<?= l('global.clipboard_copy') ?>"
                                        data-copied="<?= l('global.clipboard_copied') ?>"
                                        data-clipboard-text="<?= $row->full_url ?>"
                                >
                                    <i class="fas fa-fw fa-sm fa-copy"></i>
                                </button>

                                <div class="dropdown">
                                    <button type="button" class="btn btn-link text-secondary dropdown-toggle dropdown-toggle-simple" data-toggle="dropdown" data-boundary="viewport">
                                        <i class="fas fa-fw fa-ellipsis-v"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a href="<?= url('link/' . $row->link_id) ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-pencil-alt mr-2"></i> <?= l('global.edit') ?></a>
                                        <a href="<?= url('link/' . $row->link_id . '/statistics') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-chart-bar mr-2"></i> <?= l('link.statistics.link') ?></a>
                                        <?php if(settings()->codes->qr_codes_is_enabled): ?>
                                            <a href="<?= url('qr-code-create?name=' . $row->url . '&project_id=' . $row->project_id . '&type=url&url=' . $row->full_url . '&link_id=' . $row->link_id . '&url_dynamic=1') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-qrcode mr-2"></i> <?= l('qr_codes.create') ?></a>
                                        <?php endif ?>

                                        <?php if($row->type == 'static'): ?>
                                            <a href="<?= url('link/' . $row->link_id . '/download') ?>" class="dropdown-item"><i class="fas fa-fw fa-sm fa-download mr-2"></i> <?= l('global.download') ?></a>
                                        <?php endif ?>

                                        <a href="#" data-toggle="modal" data-target="#link_duplicate_modal" class="dropdown-item" data-link-id="<?= $row->link_id ?>"><i class="fas fa-fw fa-sm fa-clone mr-2"></i> <?= l('global.duplicate') ?></a>                                        
                                        <!-- Custom code -->                                        
                                        <?php if (($row->link_id && !$row->biolink_id) && ($row->link_id && !$row->vcard_id)): ?>
                                            <a href="#" data-toggle="modal" data-target="#link_reset_modal" class="dropdown-item" data-link-id="<?= $row->link_id ?>"><i class="fas fa-fw fa-sm fa-redo mr-2"></i> <?= l('global.reset') ?></a>                                            
                                            <a href="#" data-toggle="modal" data-target="#link_delete_modal" class="dropdown-item" data-link-id="<?= $row->link_id ?>"><i class="fas fa-fw fa-sm fa-trash-alt mr-2"></i> <?= l('global.delete') ?></a>                                            
                                        <?php endif; ?>
                                        <!-- /Custom code -->
                                    </div>
                                </div>

                            </div>
                        </td>
                    </tr>
                    <?php $fcc_short_link_row_index++; ?>
                <?php endforeach ?>

                </tbody>
            </table>
        </div>
        <?php else: ?>
            <div class="p-4 text-center text-muted">
                <?= $fcc_is_short_links ? $fcc_short_links_empty : ($fcc_links_is_hr ? 'Trenutno nema dodatnih FCC aplikacija. Glavna aplikacija je spremna gore, a novu možeš dodati kroz gumb Nova FCC Aplikacija.' : 'There are no additional FCC apps yet. Your main app is ready above, and you can create a new one with the New FCC App button.') ?>
            </div>
        <?php endif ?>
        </div>
    </form>

    <?php if(!$fcc_is_biolink_links && !empty($data->pagination)): ?>
        <div class="fcc-links-pagination"><?= $data->pagination ?></div>
    <?php endif ?>

<?php else: ?>

    <div class="fcc-links-empty">
    <?php if($fcc_is_short_links): ?>
        <div class="p-4 text-center">
            <h2 class="h5 mb-2 text-white"><?= l('links.short_links.empty_title') ?></h2>
            <p class="text-muted mb-0"><?= l('links.short_links.empty_text') ?></p>
        </div>
    <?php else: ?>
        <?= include_view(THEME_PATH . 'views/partials/no_data.php', [
            'filters_get' => $data->filters->get ?? [],
            'name' => 'links',
            'has_secondary_text' => false,
        ]); ?>
    <?php endif ?>
    </div>

<?php endif ?>
</div>

<?php if(!empty($fcc_tour_steps)): ?>
    <div class="fcc-tour-backdrop" id="fcc_apps_tour_backdrop"></div>
    <div class="fcc-tour-popover" id="fcc_apps_tour_popover" aria-live="polite">
        <div class="fcc-tour-progress" id="fcc_apps_tour_progress">1 / <?= count($fcc_tour_steps) ?></div>
        <div class="fcc-tour-title" id="fcc_apps_tour_title"></div>
        <div class="fcc-tour-text" id="fcc_apps_tour_text"></div>
        <div class="fcc-tour-actions">
            <button type="button" class="btn btn-link text-muted px-0" id="fcc_apps_tour_skip"><?= l('dashboard.tour.skip') ?></button>
            <div class="fcc-tour-actions-main">
                <button type="button" class="btn btn-outline-light" id="fcc_apps_tour_prev"><?= l('dashboard.tour.prev') ?></button>
                <button type="button" class="btn btn-primary" id="fcc_apps_tour_next"><?= l('dashboard.tour.next') ?></button>
            </div>
        </div>
    </div>
<?php endif ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/duplicate_modal.php', ['modal_id' => 'link_duplicate_modal', 'resource_id' => 'link_id', 'path' => 'link-ajax/duplicate']), 'modals'); ?>
<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>

<?php require THEME_PATH . 'views/partials/js_bulk.php' ?>
<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/bulk_delete_modal.php'), 'modals'); ?>

<?php \Altum\Event::add_content(include_view(THEME_PATH . 'views/partials/x_reset_modal.php', ['modal_id' => 'link_reset_modal', 'resource_id' => 'link_id', 'path' => 'links/reset']), 'modals'); ?>

<?php if(!empty($fcc_tour_steps)): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const fccTourStorageKey = <?= json_encode($fcc_tour_storage_key) ?>;
    const fccTourSteps = <?= json_encode($fcc_tour_steps, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    const backdrop = document.getElementById('fcc_apps_tour_backdrop');
    const popover = document.getElementById('fcc_apps_tour_popover');
    const title = document.getElementById('fcc_apps_tour_title');
    const text = document.getElementById('fcc_apps_tour_text');
    const progress = document.getElementById('fcc_apps_tour_progress');
    const prevButton = document.getElementById('fcc_apps_tour_prev');
    const nextButton = document.getElementById('fcc_apps_tour_next');
    const skipButton = document.getElementById('fcc_apps_tour_skip');

    if(!backdrop || !popover || !title || !text || !progress || !prevButton || !nextButton || !skipButton || !Array.isArray(fccTourSteps) || !fccTourSteps.length) {
        return;
    }

    let activeStep = -1;
    let currentTarget = null;
    let elevatedAncestors = [];
    let backdropSegments = [];

    const setTourMode = isActive => {
        document.body.classList.toggle('fcc-tour-mode', !!isActive);

        if(typeof window.CustomEvent === 'function') {
            window.dispatchEvent(new CustomEvent('fcc:tutorial:state', {
                detail: {active: !!isActive}
            }));
        }
    };

    const ensureBackdropSegments = () => {
        if(backdropSegments.length) {
            return backdropSegments;
        }

        backdropSegments = Array.from({length: 4}, () => {
            const segment = document.createElement('div');
            segment.className = 'fcc-tour-backdrop-segment';
            backdrop.appendChild(segment);
            return segment;
        });

        return backdropSegments;
    };

    const getElevatedAncestors = target => {
        const ancestors = [];
        let node = target?.parentElement ?? null;

        while(node && node !== document.body) {
            const computedStyle = window.getComputedStyle(node);
            const hasClippingOverflow = ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflow) || ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflowX) || ['hidden', 'clip', 'auto', 'scroll'].includes(computedStyle.overflowY);
            const shouldElevate = hasClippingOverflow;

            if(shouldElevate) {
                ancestors.push(node);
            }

            node = node.parentElement;
        }

        return ancestors;
    };

    const clearHighlight = () => {
        if(currentTarget) {
            currentTarget.classList.remove('fcc-tour-active-target');
        }

        elevatedAncestors.forEach(node => node.classList.remove('fcc-tour-active-ancestor'));
        elevatedAncestors = [];

        currentTarget = null;
    };

    const placePopover = () => {
        if(!currentTarget || !popover.classList.contains('is-visible')) {
            return;
        }

        const rect = currentTarget.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const popoverWidth = popover.offsetWidth;
        const popoverHeight = popover.offsetHeight;
        const spacing = 18;

        let top = rect.bottom + spacing;
        let left = rect.left;

        if(top + popoverHeight > viewportHeight - spacing) {
            top = Math.max(spacing, rect.top - popoverHeight - spacing);
        }

        if(left + popoverWidth > viewportWidth - spacing) {
            left = Math.max(spacing, viewportWidth - popoverWidth - spacing);
        }

        if(left < spacing) {
            left = spacing;
        }

        popover.style.top = `${top}px`;
        popover.style.left = `${left}px`;
    };

    const updateBackdropSpotlight = () => {
        if(!currentTarget || !backdrop.classList.contains('is-visible')) {
            return;
        }

        const segments = ensureBackdropSegments();
        const rect = currentTarget.getBoundingClientRect();
        const padding = 10;
        const top = Math.max(0, rect.top - padding);
        const left = Math.max(0, rect.left - padding);
        const right = Math.min(window.innerWidth, rect.right + padding);
        const bottom = Math.min(window.innerHeight, rect.bottom + padding);
        const holeWidth = Math.max(0, right - left);
        const holeHeight = Math.max(0, bottom - top);

        Object.assign(segments[0].style, {top: '0px', left: '0px', width: '100vw', height: `${top}px`});
        Object.assign(segments[1].style, {top: `${top}px`, left: '0px', width: `${left}px`, height: `${holeHeight}px`});
        Object.assign(segments[2].style, {top: `${top}px`, left: `${right}px`, width: `${Math.max(0, window.innerWidth - right)}px`, height: `${holeHeight}px`});
        Object.assign(segments[3].style, {top: `${bottom}px`, left: '0px', width: '100vw', height: `${Math.max(0, window.innerHeight - bottom)}px`});
    };

    const endTour = (completed = false) => {
        clearHighlight();
        activeStep = -1;
        setTourMode(false);
        backdrop.classList.remove('is-visible');
        popover.classList.remove('is-visible');
        if(completed) {
            localStorage.setItem(fccTourStorageKey, '1');
        }
    };

    const renderStep = (index) => {
        const step = fccTourSteps[index];
        if(!step) {
            endTour(false);
            return;
        }

        const target = document.querySelector(step.selector);
        if(!target) {
            if(index >= fccTourSteps.length - 1) {
                endTour(true);
                return;
            }
            renderStep(index + 1);
            return;
        }

        activeStep = index;
        clearHighlight();
        currentTarget = target;
        elevatedAncestors = getElevatedAncestors(currentTarget);
        elevatedAncestors.forEach(node => node.classList.add('fcc-tour-active-ancestor'));
        currentTarget.classList.add('fcc-tour-active-target');
        currentTarget.scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'});

        title.textContent = step.title || '';
        text.textContent = step.text || '';
        progress.textContent = `${index + 1} / ${fccTourSteps.length}`;
        prevButton.style.visibility = index === 0 ? 'hidden' : 'visible';
        nextButton.textContent = index === fccTourSteps.length - 1 ? <?= json_encode(l('dashboard.tour.finish')) ?> : <?= json_encode(l('dashboard.tour.next')) ?>;

        backdrop.classList.add('is-visible');
        popover.classList.add('is-visible');

        updateBackdropSpotlight();
        setTimeout(placePopover, 140);
    };

    const startTour = ({markAutoSeen = false} = {}) => {
        if(markAutoSeen) {
            localStorage.setItem(fccTourStorageKey, '1');
        }

        setTourMode(true);
        renderStep(0);
    };

    document.querySelectorAll('[data-fcc-start-tour]').forEach(button => {
        button.addEventListener('click', startTour);
    });

    skipButton.addEventListener('click', () => endTour(false));
    prevButton.addEventListener('click', () => {
        if(activeStep > 0) {
            renderStep(activeStep - 1);
        }
    });
    nextButton.addEventListener('click', () => {
        if(activeStep >= fccTourSteps.length - 1) {
            endTour(true);
            return;
        }

        renderStep(activeStep + 1);
    });

    const syncOverlay = () => {
        placePopover();
        updateBackdropSpotlight();
    };

    window.addEventListener('resize', syncOverlay);
    window.addEventListener('scroll', syncOverlay, {passive: true});

    const hasSeenTour = localStorage.getItem(fccTourStorageKey);
    if(!hasSeenTour) {
        setTimeout(() => startTour({markAutoSeen: true}), 500);
    }
});
</script>
<?php endif ?>
