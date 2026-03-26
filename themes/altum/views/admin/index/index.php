<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-03-18: split safe and sensitive admin dashboard views */
$dashboard_mode = in_array(($data->dashboard_mode ?? 'main'), ['main', 'sensitive'], true) ? $data->dashboard_mode : 'main';
$is_sensitive_dashboard = $dashboard_mode === 'sensitive';
$dashboard_page_url = $data->dashboard_page_url ?? ($is_sensitive_dashboard ? url('admin/index/sensitive-dashboard') : url('admin'));
$dashboard_toggle_url = $data->dashboard_toggle_url ?? ($is_sensitive_dashboard ? url('admin') : url('admin/index/sensitive-dashboard'));
/* /Custom code: FC-2026-03-18 */
?>

<?php if($is_sensitive_dashboard): ?>
<!-- Custom code: FC-2026-03-18: private dashboard visual polish -->
<style>
    .private-dashboard-shell {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.15rem;
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.96) 0%, rgba(10, 15, 28, 0.98) 100%);
        box-shadow: 0 1.5rem 3rem rgba(2, 6, 23, 0.32);
        overflow: hidden;
    }

    .private-dashboard-shell .card-body {
        padding: 1.1rem;
    }

    .private-dashboard-shell,
    .private-dashboard-shell h1,
    .private-dashboard-shell h2,
    .private-dashboard-shell h3,
    .private-dashboard-shell h4,
    .private-dashboard-shell .h1,
    .private-dashboard-shell .h2,
    .private-dashboard-shell .h3,
    .private-dashboard-shell .h4,
    .private-dashboard-shell .h5,
    .private-dashboard-shell .h6 {
        color: #ecf3ff;
    }

    .private-dashboard-kpi {
        height: 100%;
        padding: 1rem 1.05rem;
        border-radius: 1rem;
        border: 1px solid rgba(96, 165, 250, 0.14);
        background: linear-gradient(180deg, rgba(20, 29, 47, 0.92) 0%, rgba(11, 19, 34, 0.94) 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
    }

    .private-dashboard-kpi .text-muted,
    .private-dashboard-panel .text-muted,
    .private-dashboard-list-item .text-muted,
    .private-dashboard-shell .text-muted,
    .private-dashboard-table .text-muted {
        color: rgba(191, 211, 238, 0.72) !important;
    }

    .private-dashboard-panel {
        height: 100%;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(11, 18, 32, 0.72);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
    }

    .private-dashboard-panel .card-body {
        padding: 1rem;
    }

    .private-dashboard-list-item {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 1rem;
        background: linear-gradient(180deg, rgba(18, 27, 45, 0.86) 0%, rgba(11, 19, 34, 0.92) 100%);
        padding: 0.72rem 0.85rem;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
    }

    .private-dashboard-list-item .font-weight-bold {
        line-height: 1.2;
    }

    .private-dashboard-microcopy {
        font-size: 0.78rem;
        line-height: 1.35;
    }

    .private-dashboard-stack > * + * {
        margin-top: 0.75rem;
    }

    .private-dashboard-section-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .private-dashboard-icon {
        width: 2.65rem;
        height: 2.65rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle at top, rgba(59, 130, 246, 0.22), rgba(37, 99, 235, 0.08));
        border: 1px solid rgba(96, 165, 250, 0.14);
        color: #7cc8ff;
        flex-shrink: 0;
    }

    .private-dashboard-alert {
        border-radius: 0.95rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(11, 18, 32, 0.72);
        color: #e5edf8;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
    }

    .private-dashboard-pill {
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(15, 23, 42, 0.72);
        color: #d8e7ff;
        padding: 0.45rem 0.75rem;
    }

    .private-dashboard-platform-grid .card,
    .private-dashboard-payments-shell,
    .private-dashboard-notifications-shell {
        border: 1px solid rgba(148, 163, 184, 0.14);
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.96) 0%, rgba(10, 15, 28, 0.98) 100%);
        box-shadow: 0 1rem 2.5rem rgba(2, 6, 23, 0.24);
    }

    .private-dashboard-platform-grid .card-body,
    .private-dashboard-payments-shell .card-body,
    .private-dashboard-notifications-shell .card-body {
        color: #ecf3ff;
    }

    .private-dashboard-platform-grid .bg-primary-100 {
        background: rgba(59, 130, 246, 0.12) !important;
    }

    .private-dashboard-platform-grid .text-primary,
    .private-dashboard-platform-grid .text-reset,
    .private-dashboard-shell a,
    .private-dashboard-notifications-shell a,
    .private-dashboard-payments-shell a {
        color: #7cc8ff !important;
    }

    .private-dashboard-table {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 1rem;
        background: rgba(9, 15, 28, 0.72);
        overflow: hidden;
    }

    .private-dashboard-table .table {
        margin-bottom: 0;
    }

    .private-dashboard-table .table thead th {
        border-bottom-color: rgba(148, 163, 184, 0.12);
        color: rgba(191, 211, 238, 0.8);
        background: rgba(255, 255, 255, 0.015);
    }

    .private-dashboard-table .table td,
    .private-dashboard-table .table th {
        border-top-color: rgba(148, 163, 184, 0.1);
        background: transparent;
    }

    .private-dashboard-shell .badge-light,
    .private-dashboard-notifications-shell .badge-light,
    .private-dashboard-payments-shell .badge-light {
        background: rgba(15, 23, 42, 0.82);
        color: #dceaff;
        border: 1px solid rgba(148, 163, 184, 0.16);
    }

    @media (max-width: 767.98px) {
        .private-dashboard-shell .card-body {
            padding: 0.95rem;
        }

        .private-dashboard-panel .card-body {
            padding: 1rem;
        }
    }
</style>
<!-- /Custom code: FC-2026-03-18 -->
<?php endif ?>

<!-- Custom code: FC-2026-03-18: remove admin welcome heading -->
<!-- /Custom code: FC-2026-03-18 -->

<!-- Custom code: FC-2026-03-18: dashboard mode switcher -->
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <?php if($is_sensitive_dashboard): ?>
            <h1 class="h3 mb-1"><?= l('admin_index.sensitive_dashboard.header') ?></h1>
            <p class="text-muted mb-0"><?= l('admin_index.sensitive_dashboard.subheader') ?></p>
        <?php endif ?>
    </div>

    <a href="<?= $dashboard_toggle_url ?>" class="btn btn-outline-secondary btn-sm ml-3">
        <?= $is_sensitive_dashboard ? l('admin_index.sensitive_dashboard.back_to_main') : l('admin_index.sensitive_dashboard.open') ?>
    </a>
</div>
<!-- /Custom code: FC-2026-03-18 -->

<!-- Custom code: FC-2026-03-18: shared realtime online users modal -->
<div class="modal fade" id="realtime_online_users_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= l('admin_index.analytics_phase1.realtime.online_users') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="realtime_online_users_list" class="small text-muted">
                    <span class="spinner-border spinner-border-sm" role="status"></span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-18 -->

<!-- Custom code: FC-2026-03-18: grouped admin dashboard layout -->
<div class="d-flex flex-column">
    <!-- Custom code: FC-2026-03-18: remove priority section intro -->
    <!-- /Custom code: FC-2026-03-18 -->

    <!-- Custom code: FC-2026-03-18: remove revenue section intro -->
    <!-- /Custom code: FC-2026-03-18 -->

    <!-- Custom code: FC-2026-03-18: remove forever section intro -->
    <!-- /Custom code: FC-2026-03-18 -->

    <?php if($is_sensitive_dashboard): ?>
        <div class="mb-3 mt-2" style="order: 31;">
            <div class="border-bottom pb-2">
                <h2 class="h4 mb-1"><?= l('admin_index.dashboard_sections.platform.header') ?></h2>
                <p class="text-muted mb-0"><?= l('admin_index.dashboard_sections.platform.subheader') ?></p>
            </div>
        </div>
    <?php endif ?>

<?php if(!$is_sensitive_dashboard): ?>
<!-- Custom code: FC-2026-03-18: active collaborators first dashboard section -->
<div class="mb-5" style="order: 11;">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end mb-3">
        <div>
            <h2 class="h4 mb-1"><?= l('admin_index.analytics_phase1.header') ?></h2>
            <p class="text-muted mb-0"><?= l('admin_index.analytics_phase1.subheader') ?></p>
        </div>

        <button type="button" class="btn btn-link p-0 font-weight-bold mt-3 mt-lg-0 text-left text-lg-right" data-toggle="modal" data-target="#realtime_online_users_modal">
            <small class="text-muted d-block"><?= l('admin_index.analytics_phase1.realtime.online_users') ?></small>
            <span class="h4 mb-0" id="realtime_online_users"><span class="spinner-border spinner-border-sm" role="status"></span></span>
        </button>
    </div>

    <div class="row mb-2">
        <div class="col-12 col-md-6 col-xl-4 p-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted d-block mb-2"><?= l('admin_index.analytics_phase1.realtime.online_users') ?></small>
                    <div class="h3 mb-0" id="realtime_online_users_card"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4 p-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted d-block mb-2"><?= l('admin_index.analytics_phase1.realtime.active_sessions') ?></small>
                    <div class="h3 mb-0" id="realtime_active_sessions"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4 p-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <small class="text-muted d-block mb-2"><?= l('admin_index.analytics_phase1.realtime.recent_logins_total') ?></small>
                    <div class="h3 mb-0" id="realtime_recent_logins_total"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-8 p-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="h6 mb-1"><?= l('admin_index.analytics_phase1.charts.users') ?></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.analytics_phase1.charts.users_subheader') ?></p>
                    <div class="chart-container" style="height: 260px;">
                        <canvas id="dashboard_users_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4 p-2">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_index.analytics_phase1.realtime.header') ?></h3>

                    <h4 class="h6 mb-2"><?= l('admin_index.analytics_phase1.realtime.recent_logins') ?></h4>
                    <div id="realtime_recent_logins" class="small text-muted mb-4">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>

                    <h4 class="h6 mb-2"><?= l('admin_index.analytics_phase1.realtime.online_collaborators') ?></h4>
                    <div id="realtime_online_collaborators" class="small text-muted">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
<!-- /Custom code: FC-2026-03-04 -->

<!-- Custom code: FC-2026-03-26: admin funnel analytics panel -->
<div class="mb-5 mt-4" style="order: 13;">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-filter text-primary-900 mr-2"></i> <?= l('admin_index.funnels_analytics.header') ?></h1>

        <div class="btn-group btn-group-sm" role="group" aria-label="<?= l('admin_index.funnels_analytics.period_label') ?>">
            <button type="button" class="btn btn-outline-primary" data-funnels-period="today"><?= l('admin_index.analytics_phase1.period.today') ?></button>
            <button type="button" class="btn btn-outline-primary" data-funnels-period="7d"><?= l('admin_index.analytics_phase1.period.7d') ?></button>
            <button type="button" class="btn btn-outline-primary active" data-funnels-period="30d"><?= l('admin_index.analytics_phase1.period.30d') ?></button>
            <button type="button" class="btn btn-outline-primary" data-funnels-period="90d"><?= l('admin_index.analytics_phase1.period.90d') ?></button>
        </div>
    </div>

    <p class="text-muted mb-3"><?= l('admin_index.funnels_analytics.subheader') ?></p>

    <div class="btn-group btn-group-sm mb-3 flex-wrap" role="group" aria-label="<?= l('admin_index.funnels_analytics.filter_label') ?>">
        <button type="button" class="btn btn-outline-secondary active" data-funnels-filter="all"><?= l('admin_index.funnels_analytics.filter_all') ?></button>
        <button type="button" class="btn btn-outline-secondary" data-funnels-filter="open_mode:popup"><?= l('biolink_lead_funnel.open_mode_popup') ?></button>
        <button type="button" class="btn btn-outline-secondary" data-funnels-filter="open_mode:page"><?= l('biolink_lead_funnel.open_mode_page') ?></button>
        <button type="button" class="btn btn-outline-secondary" data-funnels-filter="thank_you_type:message"><?= l('biolink_lead_funnel.thank_you_type_message') ?></button>
        <button type="button" class="btn btn-outline-secondary" data-funnels-filter="thank_you_type:external_url"><?= l('biolink_lead_funnel.thank_you_type_external_url') ?></button>
        <button type="button" class="btn btn-outline-secondary" data-funnels-filter="thank_you_type:biolink_redirect"><?= l('biolink_lead_funnel.thank_you_type_biolink_redirect') ?></button>
        <button type="button" class="btn btn-outline-secondary" data-funnels-filter="thank_you_type:file_download"><?= l('biolink_lead_funnel.thank_you_type_file_download') ?></button>
    </div>

    <div class="row mb-2">
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_total_funnels_label"><?= l('admin_index.funnels_analytics.total_funnels') ?></small><div class="h4 mb-0" id="funnels_total_funnels"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_active_funnels_label"><?= l('admin_index.funnels_analytics.active_funnels') ?></small><div class="h4 mb-0" id="funnels_active_funnels"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_active_collaborators_label"><?= l('admin_index.funnels_analytics.active_collaborators') ?></small><div class="h4 mb-0" id="funnels_active_collaborators"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_unique_clicks_label"><?= l('admin_index.funnels_analytics.unique_clicks') ?></small><div class="h4 mb-0" id="funnels_unique_clicks"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_leads_label"><?= l('admin_index.funnels_analytics.leads') ?></small><div class="h4 mb-0" id="funnels_leads"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_conversion_rate_label"><?= l('admin_index.funnels_analytics.conversion_rate') ?></small><div class="h4 mb-0" id="funnels_conversion_rate"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_flow_entry_points_label"><?= l('admin_index.funnels_analytics.flow_entry_points') ?></small><div class="h4 mb-0" id="funnels_flow_entry_points"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_flow_starts_label"><?= l('admin_index.funnels_analytics.flow_form_starts') ?></small><div class="h4 mb-0" id="funnels_flow_starts"><span class="spinner-border spinner-border-sm" role="status"></span></div><small class="text-muted d-block mt-1" id="funnels_flow_starts_meta"><?= l('admin_index.funnels_analytics.flow_entry_to_start') ?></small></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_flow_success_label"><?= l('admin_index.funnels_analytics.flow_submit_success') ?></small><div class="h4 mb-0" id="funnels_flow_success"><span class="spinner-border spinner-border-sm" role="status"></span></div><small class="text-muted d-block mt-1" id="funnels_flow_success_meta"><?= l('admin_index.funnels_analytics.flow_start_to_success') ?></small></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1" id="funnels_flow_cta_label"><?= l('admin_index.funnels_analytics.flow_cta_clicks') ?></small><div class="h4 mb-0" id="funnels_flow_cta"><span class="spinner-border spinner-border-sm" role="status"></span></div><small class="text-muted d-block mt-1" id="funnels_flow_cta_meta"><?= l('admin_index.funnels_analytics.flow_success_to_cta') ?></small></div></div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="funnels_flow_panel_label"><?= l('admin_index.funnels_analytics.flow_header') ?></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.funnels_analytics.flow_subheader') ?></p>
                    <div id="funnels_flow_summary" class="small text-muted"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="funnels_flow_opportunities_label"><?= l('admin_index.funnels_analytics.flow_opportunities_header') ?></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.funnels_analytics.flow_opportunities_subheader') ?></p>
                    <div id="funnels_flow_opportunities" class="small text-muted"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-1" id="funnels_chart_label"><?= l('admin_index.funnels_analytics.chart_header') ?> (<?= l('admin_index.analytics_phase1.period.30d') ?>)</h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.funnels_analytics.chart_subheader') ?></p>

                    <div class="chart-container" style="height: 280px;">
                        <canvas id="funnels_analytics_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="funnels_top_funnels_label"><?= l('admin_index.funnels_analytics.top_funnels_header') ?></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.funnels_analytics.top_funnels_subheader') ?></p>
                    <div id="funnels_top_funnels" class="small text-muted"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="funnels_top_collaborators_label"><?= l('admin_index.funnels_analytics.top_collaborators_header') ?></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.funnels_analytics.top_collaborators_subheader') ?></p>
                    <div id="funnels_top_collaborators" class="small text-muted"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="funnels_open_mode_label"><?= l('admin_index.funnels_analytics.open_mode_header') ?></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.funnels_analytics.open_mode_subheader') ?></p>
                    <div id="funnels_open_mode_breakdown" class="small text-muted"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="funnels_thank_you_label"><?= l('admin_index.funnels_analytics.thank_you_header') ?></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.funnels_analytics.thank_you_subheader') ?></p>
                    <div id="funnels_thank_you_breakdown" class="small text-muted"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="funnels_top_conversion_label"><?= l('admin_index.funnels_analytics.top_conversion_header') ?></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.funnels_analytics.top_conversion_subheader') ?></p>
                    <div id="funnels_top_conversion" class="small text-muted"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="funnels_opportunities_label"><?= l('admin_index.funnels_analytics.opportunities_header') ?></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.funnels_analytics.opportunities_subheader') ?></p>
                    <div id="funnels_opportunities" class="small text-muted"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-26 -->
<?php endif ?>

<?php if($is_sensitive_dashboard): ?>
<!-- Custom code: FC-2026-03-18: private sales and subscriptions panel redesign -->
<div class="mb-5 mt-4" style="order: 23;">
    <div class="card private-dashboard-shell">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-3">
                <div>
                    <div class="private-dashboard-section-title mb-2">
                        <span class="private-dashboard-icon"><i class="fas fa-fw fa-credit-card"></i></span>
                        <h2 class="h3 mb-0 text-truncate"><?= l('admin_index.sales_subscriptions.header') ?></h2>
                    </div>
                    <p class="text-muted mb-0"><?= l('admin_index.sales_subscriptions.subheader') ?></p>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.recurring_revenue_current_month') ?></small><div class="h5 mb-0"><span id="sales_recurring_revenue_current_month"><span class="spinner-border spinner-border-sm" role="status"></span></span> <small><?= settings()->payment->default_currency ?></small></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.active_paid_subscriptions') ?></small><div class="h5 mb-0" id="sales_active_paid_subscriptions"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <!-- Custom code: FC-2026-03-18: total active Forever Pro collaborators KPI card -->
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.active_total_pro_collaborators') ?></small><div class="h5 mb-0" id="sales_active_total_pro_collaborators"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <!-- /Custom code: FC-2026-03-18 -->
                <!-- Custom code: FC-2026-03-18: cancelled subscriptions KPI card -->
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.cancelled_billing_30d') ?></small><div class="h5 mb-0" id="sales_cancelled_billing_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <!-- /Custom code: FC-2026-03-18 -->
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.new_subscriptions_30d') ?></small><div class="h5 mb-0" id="sales_new_subscriptions_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.cancelled_subscriptions_30d') ?></small><div class="h5 mb-0" id="sales_cancelled_subscriptions_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.failed_payments_30d') ?></small><div class="h5 mb-0" id="sales_failed_payments_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.plan_changes_30d') ?></small><div class="h5 mb-0" id="sales_plan_changes_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-12 col-xl-8 p-2">
                    <div class="private-dashboard-panel h-100">
                        <div class="card-body">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-3">
                                <div>
                                    <h3 class="h6 mb-1"><?= l('admin_index.sales_subscriptions.chart_header') ?></h3>
                                    <p class="small text-muted mb-0"><?= l('admin_index.sales_subscriptions.chart_subheader') ?></p>
                                </div>

                                <!-- Custom code: FC-2026-03-18: sales chart 30/60/90 period toggle -->
                                <div class="btn-group btn-group-sm mt-3 mt-md-0" role="group" aria-label="<?= l('admin_index.sales_subscriptions.chart_period_label') ?>">
                                    <button type="button" class="btn btn-outline-primary active" data-sales-subscriptions-period="30">30d</button>
                                    <button type="button" class="btn btn-outline-primary" data-sales-subscriptions-period="60">60d</button>
                                    <button type="button" class="btn btn-outline-primary" data-sales-subscriptions-period="90">90d</button>
                                </div>
                                <!-- /Custom code: FC-2026-03-18 -->
                            </div>
                            <div class="chart-container" style="height: 230px;">
                                <canvas id="sales_subscriptions_chart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-4 p-2">
                    <div class="private-dashboard-panel h-100">
                        <div class="card-body">
                            <h3 class="h6 mb-2"><?= l('admin_index.sales_subscriptions.at_risk_header') ?></h3>
                            <p class="small text-muted mb-3"><?= l('admin_index.sales_subscriptions.at_risk_subheader') ?></p>
                            <div id="sales_at_risk_trial_users" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-18 -->
<?php endif ?>

<?php if($is_sensitive_dashboard): ?>
<!-- Custom code: FC-2026-03-18: private action center panel redesign -->
<div class="mb-5 mt-4" style="order: 22;">
    <div class="card private-dashboard-shell">
        <div class="card-body">
            <div class="private-dashboard-section-title mb-2">
                <span class="private-dashboard-icon"><i class="fas fa-fw fa-bolt"></i></span>
                <h2 class="h3 mb-0 text-truncate"><?= l('admin_index.action_center.header') ?></h2>
            </div>

            <p class="text-muted mb-3"><?= l('admin_index.action_center.subheader') ?></p>

            <div class="row mb-2">
                <div class="col-12 col-md-6 p-2">
                    <div id="action_warning_trials" class="private-dashboard-alert mb-0">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
                <div class="col-12 col-md-6 p-2">
                    <div id="action_warning_collaborators" class="private-dashboard-alert mb-0">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-12 col-xl-6 p-2">
                    <div class="private-dashboard-panel h-100">
                        <div class="card-body">
                            <h3 class="h6 mb-2"><?= l('admin_index.action_center.urgent_trials_header') ?> <span class="text-muted" id="action_urgent_trials_count"></span></h3>
                            <p class="small text-muted mb-3"><?= l('admin_index.action_center.urgent_trials_subheader') ?></p>
                            <div id="action_urgent_trials" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6 p-2">
                    <div class="private-dashboard-panel h-100">
                        <div class="card-body">
                            <h3 class="h6 mb-2"><?= l('admin_index.action_center.collaborator_opportunities_header') ?> <span class="text-muted" id="action_collaborator_opportunities_count"></span></h3>
                            <p class="small text-muted mb-3"><?= l('admin_index.action_center.collaborator_opportunities_subheader') ?></p>
                            <div id="action_collaborator_opportunities" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-18 -->
<?php endif ?>

<?php if($is_sensitive_dashboard): ?>
<!-- Custom code: FC-2026-03-18: private billing risk panel redesign -->
<div class="mb-5 mt-2" style="order: 21;">
    <div class="card private-dashboard-shell">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-3">
                <div>
                    <div class="private-dashboard-section-title mb-2">
                        <span class="private-dashboard-icon"><i class="fas fa-fw fa-triangle-exclamation"></i></span>
                        <h2 class="h3 mb-0 text-truncate"><?= l('admin_index.billing_risk.header') ?></h2>
                    </div>
                    <p class="text-muted mb-0"><?= l('admin_index.billing_risk.subheader') ?></p>
                </div>
                <a href="<?= url('admin/billing-risk') ?>" class="btn btn-outline-primary btn-sm mt-3 mt-md-0"><?= l('admin_index.billing_risk.view_all') ?></a>
            </div>

            <div class="row mb-2">
                <div class="col-12 col-md-6 col-xl-2 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_billing_risk.state_past_due') ?></small><div class="h5 mb-0" id="billing_risk_past_due"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-2 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_billing_risk.state_past_due_critical') ?></small><div class="h5 mb-0" id="billing_risk_critical"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-2 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_billing_risk.state_access_revoked') ?></small><div class="h5 mb-0" id="billing_risk_revoked"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.billing_risk.expiring_24h') ?></small><div class="h5 mb-0" id="billing_risk_expiring"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="private-dashboard-kpi"><small class="text-muted d-block mb-1"><?= l('admin_index.billing_risk.recovered_7d') ?></small><div class="h5 mb-0" id="billing_risk_recovered"><span class="spinner-border spinner-border-sm" role="status"></span></div></div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-12 p-2">
                    <div class="private-dashboard-panel h-100">
                        <div class="card-body">
                            <h3 class="h6 mb-2"><?= l('admin_index.billing_risk.users_header') ?> <span class="text-muted" id="billing_risk_users_count"></span></h3>
                            <p class="small text-muted mb-3"><?= l('admin_index.billing_risk.users_subheader') ?></p>
                            <div id="billing_risk_users" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-18 -->
<?php endif ?>

<?php if(!$is_sensitive_dashboard): ?>
<!-- Custom code: FC-2026-03-04: phase 4 biolink traffic and collaborator analytics panel -->
<div class="mb-5 mt-4" style="order: 12;">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-globe-europe text-primary-900 mr-2"></i> <?= l('admin_index.biolink_analytics.header') ?></h1>

        <div class="btn-group btn-group-sm" role="group" aria-label="<?= l('admin_index.biolink_analytics.period_label') ?>">
            <button type="button" class="btn btn-outline-primary" data-biolink-period="today"><?= l('admin_index.analytics_phase1.period.today') ?></button>
            <button type="button" class="btn btn-outline-primary" data-biolink-period="7d"><?= l('admin_index.analytics_phase1.period.7d') ?></button>
            <button type="button" class="btn btn-outline-primary active" data-biolink-period="30d"><?= l('admin_index.analytics_phase1.period.30d') ?></button>
            <button type="button" class="btn btn-outline-primary" data-biolink-period="90d"><?= l('admin_index.analytics_phase1.period.90d') ?></button>
        </div>
    </div>

    <p class="text-muted mb-3"><?= l('admin_index.biolink_analytics.subheader') ?></p>

    <!-- Custom code: FC-2026-03-18: collaborator search and selected collaborator state -->
    <div class="row mb-2">
        <div class="col-12 col-xl-8 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <label for="biolink_collaborator_search" class="small text-muted d-block mb-2"><?= l('admin_index.biolink_analytics.search_label') ?></label>
                    <div id="biolink_collaborator_search_wrapper" class="position-relative">
                        <input type="search" id="biolink_collaborator_search" class="form-control" placeholder="<?= l('admin_index.biolink_analytics.search_placeholder') ?>" autocomplete="off" />
                        <div id="biolink_collaborator_search_results" class="position-absolute w-100 shadow-sm d-none overflow-auto" style="z-index: 20; top: calc(100% + .5rem); max-height: 320px;"></div>
                    </div>
                    <small class="text-muted d-block mt-2"><?= l('admin_index.biolink_analytics.search_hint') ?></small>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body d-flex flex-column flex-sm-row justify-content-between align-items-sm-center">
                    <div class="mb-3 mb-sm-0">
                        <small class="text-muted d-block mb-1"><?= l('admin_index.biolink_analytics.selection_label') ?></small>
                        <div class="font-weight-bold" id="biolink_selected_collaborator_label"><?= l('admin_index.biolink_analytics.selection_all') ?></div>
                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm" id="biolink_selection_reset" disabled="disabled"><?= l('global.reset') ?></button>
                </div>
            </div>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-18 -->

    <!-- Custom code: FC-2026-03-18: three responsive KPI cards driven by selected biolink period -->
    <div class="row mb-2">
        <div class="col-12 col-lg-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1" id="biolink_period_total_label"><?= l('admin_index.biolink_analytics.clicks_selected_period') ?> (<?= l('admin_index.analytics_phase1.period.30d') ?>)</small>
                    <div class="h4 mb-0" id="biolink_period_total_clicks"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1" id="biolink_period_shop_label"><?= l('admin_index.biolink_analytics.forever_shop_clicks_selected_period') ?> (<?= l('admin_index.analytics_phase1.period.30d') ?>)</small>
                    <div class="h4 mb-0" id="biolink_period_shop_clicks"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1" id="biolink_period_registration_label"><?= l('admin_index.biolink_analytics.forever_registration_clicks_selected_period') ?> (<?= l('admin_index.analytics_phase1.period.30d') ?>)</small>
                    <div class="h4 mb-0" id="biolink_period_registration_clicks"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-18 -->

    <!-- Custom code: FC-2026-03-18: selected-period biolink trend chart -->
    <div class="row mt-2">
        <div class="col-12 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-1" id="biolink_chart_label"><?= l('admin_index.biolink_analytics.chart_header') ?> (<?= l('admin_index.analytics_phase1.period.30d') ?>)</h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.biolink_analytics.chart_subheader') ?></p>

                    <div class="chart-container" style="height: 260px;">
                        <canvas id="biolink_analytics_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /Custom code: FC-2026-03-18 -->

    <div class="row mt-2">
        <div class="col-12 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3" id="biolink_top_countries_title"><span id="biolink_top_countries_label"><?= l('admin_index.biolink_analytics.top_countries_header') ?></span> <span class="text-muted" id="biolink_top_countries_count"></span></h3>
                    <div id="biolink_top_countries" class="small text-muted">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="biolink_leaderboard_title"><span id="biolink_leaderboard_label"><?= l('admin_index.biolink_analytics.leaderboard_header') ?></span> <span class="text-muted" id="biolink_leaderboard_count"></span></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.biolink_analytics.leaderboard_subheader') ?></p>
                    <div id="biolink_leaderboard" class="small text-muted">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2" id="biolink_user_details_title"><?= l('admin_index.biolink_analytics.user_details_default_title') ?></h3>
                    <div class="small">
                        <div class="d-flex justify-content-between border-bottom py-2"><span id="biolink_user_clicks_total_label"><?= l('admin_index.biolink_analytics.clicks_selected_period') ?></span><span id="biolink_user_clicks_total"><span class="spinner-border spinner-border-sm" role="status"></span></span></div>
                        <div class="d-flex justify-content-between border-bottom py-2"><span id="biolink_user_forever_shop_label"><?= l('admin_index.biolink_analytics.forever_shop_clicks_selected_period') ?></span><span id="biolink_user_forever_shop_clicks"><span class="spinner-border spinner-border-sm" role="status"></span></span></div>
                        <div class="d-flex justify-content-between border-bottom py-2"><span id="biolink_user_forever_registration_label"><?= l('admin_index.biolink_analytics.forever_registration_clicks_selected_period') ?></span><span id="biolink_user_forever_registration_clicks"><span class="spinner-border spinner-border-sm" role="status"></span></span></div>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted d-block mb-2"><?= l('admin_index.biolink_analytics.user_top_countries_header') ?> <span id="biolink_user_top_countries_count"></span></small>
                        <div id="biolink_user_top_countries" class="small text-muted">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2"><span id="biolink_top_shop_sources_label"><?= l('admin_index.biolink_analytics.top_shop_sources_header') ?></span> <span class="text-muted" id="biolink_top_shop_sources_count"></span></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.biolink_analytics.top_shop_sources_subheader') ?></p>
                    <div id="biolink_top_shop_sources" class="small text-muted">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-2"><span id="biolink_top_registration_sources_label"><?= l('admin_index.biolink_analytics.top_registration_sources_header') ?></span> <span class="text-muted" id="biolink_top_registration_sources_count"></span></h3>
                    <p class="small text-muted mb-3"><?= l('admin_index.biolink_analytics.top_registration_sources_subheader') ?></p>
                    <div id="biolink_top_registration_sources" class="small text-muted">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-04 -->
<?php endif ?>

<?php if($is_sensitive_dashboard): ?>
<!-- Custom code: FC-2026-03-18: side by side trial and upcoming charges panels -->
<div class="mb-5 mt-4" style="order: 24;">
    <div class="row">
        <div class="col-12 col-xl-6 p-2">
            <div class="card private-dashboard-shell h-100">
                <div class="card-body">
                    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
                        <div>
                            <div class="private-dashboard-section-title mb-2">
                                <span class="private-dashboard-icon"><i class="fas fa-fw fa-hourglass-half"></i></span>
                                <h2 class="h3 mb-0 text-truncate"><?= l('admin_index.trial_monitoring.header') ?></h2>
                            </div>
                            <p class="text-muted mb-0"><?= l('admin_index.trial_monitoring.subheader') ?></p>
                        </div>

                        <div class="d-flex align-items-center mt-3 mt-md-0">
                            <span class="badge badge-primary mr-2"><?= sprintf(l('admin_index.trial_monitoring.total'), nr($data->active_trial_total)) ?></span>
                            <span class="badge badge-danger"><?= sprintf(l('admin_index.trial_monitoring.cancelled_total'), nr($data->active_trial_cancelled_total)) ?></span>
                        </div>
                    </div>

                    <form method="get" action="<?= $dashboard_page_url ?>" class="mb-3">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between">
                            <div class="d-flex align-items-center mb-2 mb-lg-0">
                                <label for="trial_filter" class="mr-2 mb-0 text-muted"><?= l('admin_index.trial_monitoring.filter_label') ?></label>
                                <select id="trial_filter" name="trial_filter" class="form-control form-control-sm" style="width: 240px;">
                                    <option value="attention" <?= $data->trial_filter == 'attention' ? 'selected="selected"' : null ?>><?= l('admin_index.trial_monitoring.filter.attention') ?></option>
                                    <option value="all" <?= $data->trial_filter == 'all' ? 'selected="selected"' : null ?>><?= l('admin_index.trial_monitoring.filter.all') ?></option>
                                    <option value="cancelled" <?= $data->trial_filter == 'cancelled' ? 'selected="selected"' : null ?>><?= l('admin_index.trial_monitoring.filter.cancelled') ?></option>
                                    <option value="active" <?= $data->trial_filter == 'active' ? 'selected="selected"' : null ?>><?= l('admin_index.trial_monitoring.filter.active') ?></option>
                                    <option value="no_subscription" <?= $data->trial_filter == 'no_subscription' ? 'selected="selected"' : null ?>><?= l('admin_index.trial_monitoring.filter.no_subscription') ?></option>
                                </select>

                                <button type="submit" class="btn btn-sm btn-outline-primary ml-2"><?= l('global.search') ?></button>
                                <a href="<?= $dashboard_page_url ?>" class="btn btn-sm btn-outline-secondary ml-2"><?= l('global.reset') ?></a>
                            </div>

                            <div class="d-flex align-items-center">
                                <small class="text-muted mr-2"><?= sprintf(l('admin_index.trial_monitoring.shown_total'), nr($data->active_trial_filtered_total)) ?></small>
                                <a href="<?= $dashboard_page_url . '?trial_filter=' . $data->trial_filter . '&trial_export=csv' ?>" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-fw fa-sm fa-file-csv mr-1"></i><?= l('admin_index.trial_monitoring.export_csv') ?>
                                </a>
                            </div>
                        </div>
                    </form>

                    <?php if(empty($data->active_trial_users_filtered)): ?>
                        <div class="alert alert-info mb-0"><?= l('admin_index.trial_monitoring.empty') ?></div>
                    <?php else: ?>
                        <div class="table-responsive table-custom-container private-dashboard-table mb-3">
                            <table class="table table-custom mb-0">
                                <thead>
                                <tr>
                                    <th><?= l('global.user') ?></th>
                                    <th><?= l('admin_users.plan_id') ?></th>
                                    <th><?= l('admin_index.trial_monitoring.trial_until') ?></th>
                                    <th><?= l('admin_index.trial_monitoring.billing_status') ?></th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody id="trial_monitoring_table_body">
                                <?php foreach($data->active_trial_users_filtered as $trial_user): ?>
                                    <tr class="trial-monitoring-row">
                                        <td class="text-nowrap">
                                            <div class="d-flex flex-column">
                                                <a href="<?= url('admin/user-view/' . $trial_user->user_id) ?>"><?= $trial_user->name ?></a>
                                                <small class="text-muted"><?= $trial_user->email ?></small>
                                            </div>
                                        </td>

                                        <td class="text-nowrap">
                                            <span class="badge badge-light"><?= $trial_user->plan_name ?></span>
                                        </td>

                                        <td class="text-nowrap">
                                            <?= \Altum\Date::get($trial_user->plan_expiration_date, 2) ?>
                                        </td>

                                        <td class="text-nowrap">
                                            <?php if($trial_user->is_cancelled_billing_during_trial): ?>
                                                <span class="badge badge-danger"><?= l('admin_index.trial_monitoring.billing_cancelled') ?></span>
                                            <?php elseif($trial_user->has_active_subscription): ?>
                                                <span class="badge badge-success"><?= l('admin_index.trial_monitoring.billing_active') ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-warning"><?= l('admin_index.trial_monitoring.billing_no_subscription') ?></span>
                                            <?php endif ?>
                                        </td>

                                        <td class="text-nowrap">
                                            <div class="d-flex justify-content-end">
                                                <a href="<?= url('admin/user-view/' . $trial_user->user_id) ?>" class="btn btn-sm btn-outline-primary"><?= l('global.view') ?></a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                        <div id="trial_monitoring_table_toggle"></div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-6 p-2">
            <!-- Custom code: FC-2026-03-18: upcoming charges section -->
            <div class="card private-dashboard-shell h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start mb-3">
                        <div>
                            <div class="private-dashboard-section-title mb-2">
                                <span class="private-dashboard-icon"><i class="fas fa-fw fa-calendar-check"></i></span>
                                <h2 class="h3 mb-0 text-truncate"><?= l('admin_index.upcoming_charges.header') ?></h2>
                            </div>
                            <p class="text-muted mb-0"><?= l('admin_index.upcoming_charges.subheader') ?></p>
                        </div>

                        <div class="mt-3 mt-md-0">
                            <span class="private-dashboard-pill"><?= sprintf(l('admin_index.upcoming_charges.total'), nr($data->upcoming_charge_total)) ?></span>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12 col-md-6 p-2">
                            <div class="private-dashboard-kpi">
                                <small class="text-muted d-block mb-1"><?= l('admin_index.upcoming_charges.next_7d') ?></small>
                                <div class="h4 mb-0"><?= nr($data->upcoming_charge_next_7d_total) ?></div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 p-2">
                            <div class="private-dashboard-kpi">
                                <small class="text-muted d-block mb-1"><?= l('admin_index.upcoming_charges.next_30d') ?></small>
                                <div class="h4 mb-0"><?= nr($data->upcoming_charge_next_30d_total) ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if(empty($data->upcoming_charge_users)): ?>
                        <div class="alert alert-info mb-0"><?= l('admin_index.upcoming_charges.empty') ?></div>
                    <?php else: ?>
                        <div id="upcoming_charges_list" class="private-dashboard-stack mt-1 flex-grow-1">
                            <?php foreach($data->upcoming_charge_users as $upcoming_charge_user): ?>
                                <div class="private-dashboard-list-item upcoming-charge-item">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start">
                                        <div class="pr-md-3">
                                            <div class="font-weight-bold mb-1"><a href="<?= url('admin/user-view/' . $upcoming_charge_user->user_id) ?>"><?= $upcoming_charge_user->name ?></a></div>
                                            <div class="small text-muted private-dashboard-microcopy mb-0"><?= $upcoming_charge_user->email ?> <span class="mx-1">&middot;</span> <?= $upcoming_charge_user->plan_name ?></div>
                                        </div>

                                        <div class="mt-2 mt-md-0 text-md-right">
                                            <span class="badge <?= $upcoming_charge_user->is_trial_charge ? 'badge-warning' : 'badge-primary' ?>">
                                                <?= $upcoming_charge_user->is_trial_charge ? l('admin_index.upcoming_charges.trial') : l('admin_index.upcoming_charges.subscription') ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mt-2">
                                        <div class="small text-muted private-dashboard-microcopy mb-2 mb-sm-0"><?= l('admin_index.upcoming_charges.charge_on') ?>: <?= \Altum\Date::get($upcoming_charge_user->plan_expiration_date, 2) ?></div>
                                        <span class="private-dashboard-pill"><?= sprintf(l('admin_index.upcoming_charges.days_left'), nr($upcoming_charge_user->days_until_charge)) ?></span>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <div id="upcoming_charges_toggle" class="mt-3"></div>
                    <?php endif ?>
                </div>
            </div>
            <!-- /Custom code: FC-2026-03-18 -->
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-18 -->
<?php endif ?>

<?php if($is_sensitive_dashboard): ?>
<div class="mb-5 row justify-content-between private-dashboard-platform-grid" style="order: 32;">
    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <div class="row">
                    <div class="col text-truncate">
                        <small class="text-muted font-weight-bold"><?= l('admin_index.biolink_links') ?></small>
                    </div>

                    <div class="col-auto">
                        <span class="p-2 bg-primary-100 rounded">
                            <i class="fas fa-fw fa-sm fa-hashtag text-primary"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-2 text-break">
                    <a href="<?= url('admin/links?type=biolink') ?>" class="stretched-link text-reset text-decoration-none">
                        <span class="h4" id="biolink_links">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </a>

                    <div class="mt-1 small text-muted">
                        <span id="biolink_links_current_month">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <?= mb_strtolower(l('global.date.this_month')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <div class="row">
                    <div class="col text-truncate">
                        <small class="text-muted font-weight-bold"><?= l('admin_index.shortened_links') ?></small>
                    </div>

                    <div class="col-auto">
                        <span class="p-2 bg-primary-100 rounded">
                            <i class="fas fa-fw fa-sm fa-link text-primary"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-2 text-break">
                    <a href="<?= url('admin/links?type=link') ?>" class="stretched-link text-reset text-decoration-none">
                        <span class="h4" id="shortened_links">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </a>

                    <div class="mt-1 small text-muted">
                        <span id="shortened_links_current_month">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <?= mb_strtolower(l('global.date.this_month')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <div class="row">
                    <div class="col text-truncate">
                        <small class="text-muted font-weight-bold"><?= l('admin_index.track_links') ?></small>
                    </div>

                    <div class="col-auto">
                        <span class="p-2 bg-primary-100 rounded">
                            <i class="fas fa-fw fa-sm fa-chart-bar text-primary"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-2 text-break">
                    <a href="<?= url('admin/statistics/track_links') ?>" class="stretched-link text-reset text-decoration-none">
                        <span class="h4" id="track_links">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </a>

                    <div class="mt-1 small text-muted">
                        <span id="track_links_current_month">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <?= mb_strtolower(l('global.date.this_month')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <div class="row">
                    <div class="col text-truncate">
                        <small class="text-muted font-weight-bold"><?= l('admin_qr_codes.menu') ?></small>
                    </div>

                    <div class="col-auto">
                        <span class="p-2 bg-primary-100 rounded">
                            <i class="fas fa-fw fa-sm fa-qrcode text-primary"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-2 text-break">
                    <a href="<?= url('admin/qr-codes') ?>" class="stretched-link text-reset text-decoration-none">
                        <span class="h4" id="qr_codes">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </a>

                    <div class="mt-1 small text-muted">
                        <span id="qr_codes_current_month">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <?= mb_strtolower(l('global.date.this_month')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <div class="row">
                    <div class="col text-truncate">
                        <small class="text-muted font-weight-bold"><?= l('admin_domains.menu') ?></small>
                    </div>

                    <div class="col-auto">
                        <span class="p-2 bg-primary-100 rounded">
                            <i class="fas fa-fw fa-sm fa-globe text-primary"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-2 text-break">
                    <a href="<?= url('admin/domains') ?>" class="stretched-link text-reset text-decoration-none">
                        <span class="h4" id="domains">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </a>

                    <div class="mt-1 small text-muted">
                        <span id="domains_current_month">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <?= mb_strtolower(l('global.date.this_month')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <div class="row">
                    <div class="col text-truncate">
                        <small class="text-muted font-weight-bold"><?= l('admin_users.menu') ?></small>
                    </div>

                    <div class="col-auto">
                        <span class="p-2 bg-primary-100 rounded">
                            <i class="fas fa-fw fa-sm fa-users text-primary"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-2 text-break">
                    <a href="<?= url('admin/users') ?>" class="stretched-link text-reset text-decoration-none">
                        <span class="h4" id="users">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </a>

                    <div class="mt-1 small text-muted">
                        <span id="users_current_month">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <?= mb_strtolower(l('global.date.this_month')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <div class="row">
                    <div class="col text-truncate">
                        <small class="text-muted font-weight-bold"><?= l('admin_payments.menu') ?></small>
                    </div>

                    <div class="col-auto">
                    <span class="p-2 bg-primary-100 rounded">
                        <i class="fas fa-fw fa-sm fa-funnel-dollar text-primary"></i>
                    </span>
                    </div>
                </div>

                <div class="mt-2 text-break">
                    <a href="<?= in_array(settings()->license->type, ['Extended License', 'extended']) ? url('admin/payments') : url('admin/settings/payment') ?>" class="stretched-link text-reset text-decoration-none">
                        <span class="h4" id="payments">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </a>

                    <div class="mt-1 small text-muted">
                        <span id="payments_current_month">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <?= mb_strtolower(l('global.date.this_month')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <div class="row">
                    <div class="col text-truncate">
                        <small class="text-muted font-weight-bold"><?= l('admin_index.payments_total_amount') ?></small>
                    </div>

                    <div class="col-auto">
                        <span class="p-2 bg-primary-100 rounded">
                            <i class="fas fa-fw fa-sm fa-credit-card text-primary"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-2 text-break">
                    <a href="<?= in_array(settings()->license->type, ['Extended License', 'extended']) ? url('admin/payments') : url('admin/settings/payment') ?>" class="stretched-link text-reset text-decoration-none">
                        <span class="h4" id="payments_total_amount">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <small><?= settings()->payment->default_currency ?></small>
                    </a>

                    <div class="mt-1 small text-muted">
                        <span id="payments_amount_current_month">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                        <?= settings()->payment->default_currency ?> <?= mb_strtolower(l('global.date.this_month')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

<?php if($is_sensitive_dashboard): ?>
<?php if(settings()->internal_notifications->admins_is_enabled): ?>
    <?php if($data->internal_notifications): ?>
        <!-- Custom code: FC-2026-03-18: redesigned private admin notifications section -->
        <div class="mb-5" style="order: 33;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
                <div>
                    <h1 class="h3 mb-1"><i class="fas fa-fw fa-xs fa-bell text-primary-900 mr-2"></i> <?= l('admin_index.admins_notifications') ?></h1>
                    <p class="text-muted mb-0"><?= \Altum\Date::get_timeago($data->internal_notifications[0]->datetime) ?> · <?= nr(count($data->internal_notifications)) ?></p>
                </div>

                <div class="mt-3 mt-md-0">
                    <span class="private-dashboard-pill"><?= nr(count($data->internal_notifications)) ?></span>
                </div>
            </div>

            <div class="card border-0 shadow-sm overflow-hidden private-dashboard-notifications-shell">
                <div class="card-body p-3 p-lg-4">
                    <div class="d-flex flex-column">
                        <?php foreach($data->internal_notifications as $notification): ?>
                            <?php //ALTUMCODE:DEMO if(DEMO) {$notification->title = $notification->description = 'hidden on demo';} ?>

                            <div class="private-dashboard-list-item mb-3 <?= !$notification->is_read ? 'border-info' : null ?> position-relative">
                                <div class="card-body py-3 px-3 px-lg-4">
                                    <div class="d-flex align-items-start">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle mr-3 flex-shrink-0" style="width: 3rem; height: 3rem; background: rgba(13, 110, 253, 0.08);">
                                            <i class="<?= $notification->icon ?> fa-fw fa-lg text-primary-900"></i>
                                        </div>

                                        <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-lg-between flex-fill min-width-0">
                                            <div class="d-flex flex-column pr-lg-4 min-width-0">
                                                <div class="d-flex align-items-center flex-wrap mb-1">
                                                    <div class="font-weight-bold mr-2 text-break">
                                                        <?php if($notification->url): ?>
                                                            <a href="<?= $notification->url ?>" class="stretched-link text-decoration-none"><?= $notification->title ?></a>
                                                        <?php else: ?>
                                                            <?= $notification->title ?>
                                                        <?php endif ?>
                                                    </div>

                                                    <?php if(!$notification->is_read): ?>
                                                        <span class="badge badge-info"><i class="fas fa-circle fa-xs"></i></span>
                                                    <?php endif ?>
                                                </div>

                                                <small class="text-muted text-break"><?= $notification->description ?></small>
                                            </div>

                                            <div class="mt-3 mt-lg-0 text-lg-right flex-shrink-0">
                                                <div class="small font-weight-bold"><?= \Altum\Date::get_timeago($notification->datetime) ?></div>
                                                <div class="small text-muted" data-toggle="tooltip" title="<?= \Altum\Date::get($notification->datetime, 1) ?>"><?= \Altum\Date::get($notification->datetime, 2) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Custom code: FC-2026-03-18 -->
    <?php endif ?>
<?php endif ?>
<?php endif ?>


<?php if($is_sensitive_dashboard): ?>
<?php if(in_array(settings()->license->type, ['SPECIAL', 'Extended License', 'extended'])): ?>
    <?php $result = database()->query("SELECT `payments`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar` FROM `payments` LEFT JOIN `users` ON `payments`.`user_id` = `users`.`user_id` ORDER BY `id` DESC LIMIT 5"); ?>

    <?php if($result->num_rows): ?>
        <div class="mb-5" style="order: 34;">
            <h1 class="h3 mb-4"><i class="fas fa-fw fa-xs fa-credit-card text-primary-900 mr-2"></i> <?= l('admin_index.payments') ?></h1>

            <div class="card private-dashboard-payments-shell border-0 shadow-sm overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive table-custom-container private-dashboard-table">
                <table class="table table-custom">
                    <thead>
                    <tr>
                        <th><?= l('global.user') ?></th>
                        <th><?= l('admin_payments.plan') ?></th>
                        <th><?= l('admin_payments.total_amount') ?></th>
                        <th><?= l('global.type') ?></th>
                        <th></th>
                        <th></th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while($row = $result->fetch_object()): ?>
                        <?php //ALTUMCODE:DEMO if(DEMO) {$row->email = $row->user_email = 'hidden@demo.com'; $row->user_name = $row->name = 'hidden on demo';} ?>
                        <?php $row->taxes_ids = json_decode($row->taxes_ids ?? ''); ?>

                        <tr>
                            <td class="text-nowrap">
                                <div class="d-flex align-items-center">
                                    <?php if($row->user_name || $row->user_email): ?>
                                        <a href="<?= url('admin/user-view/' . $row->user_id) ?>">
                                            <img src="<?= get_user_avatar($row->user_avatar, $row->user_email) ?>" referrerpolicy="no-referrer" loading="lazy" class="user-avatar rounded-circle mr-3" alt="" />
                                        </a>

                                        <div class="d-flex flex-column">
                                            <div>
                                                <a href="<?= url('admin/user-view/' . $row->user_id) ?>"><?= $row->user_name ?></a>
                                            </div>

                                            <span class="text-muted small"><?= $row->user_email ?></span>
                                        </div>
                                    <?php else: ?>
                                        <img src="<?= get_user_avatar($row->user_avatar, $row->user_email) ?>" referrerpolicy="no-referrer" loading="lazy" class="user-avatar rounded-circle mr-3" alt="" />

                                        <div class="text-muted">
                                            <?= l('global.unknown') ?>
                                        </div>
                                    <?php endif ?>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <?php if(isset($data->plans[$row->plan_id])): ?>
                                    <a href="<?= url('admin/plan-update/' . $row->plan_id) ?>" class="badge badge-light">
                                        <?= $data->plans[$row->plan_id]->name ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge badge-light"><?= $row->plan->name ?? l('global.unknown') ?></span>
                                <?php endif ?>
                            </td>

                            <td class="text-nowrap">
                                <span class="badge badge-success"><?= nr($row->total_amount, 2) . ' ' . $row->currency ?></span>
                            </td>

                            <td class="text-nowrap">
                                <div class="d-flex flex-column">
                                    <span><?= l('pay.custom_plan.' . $row->type . '_type') ?></span>

                                    <div>
                                        <span class="small text-muted"><?= l('pay.custom_plan.' . $row->frequency) ?></span>
                                    </div>
                                </div>
                            </td>

                            <td class="text-nowrap">
                                <a href="<?= url('admin/payments?processor=' . $row->processor) ?>" class="badge badge-light">
                                    <i class="<?= $data->payment_processors[$row->processor]['icon'] ?> fa-fw mr-1" style="--brand-color: <?= $data->payment_processors[$row->processor]['color'] ?>;--brand-color-dark: <?= $data->payment_processors[$row->processor]['dark_color'] ?>; color: var(--brand-color)" data-custom-colors></i>
                                    <?= l('pay.custom_plan.' . $row->processor) ?>
                                </a>
                            </td>

                            <td class="text-nowrap">
                                <span class="mr-2 <?= $row->code ? null : 'opacity-0' ?>" data-toggle="tooltip" title="<?= $row->code ? $row->code . ' (-' . nr($row->discount_amount, 2) . ' ' . $row->currency . ')' : null ?>">
                                    <i class="fas fa-fw fa-sm fa-tag text-muted"></i>
                                </span>

                                <?php
                                $taxes_html = null;
                                if(count($row->taxes_ids ?? [])) {
                                    $taxes_html = l('admin_taxes.menu') . ': ';
                                    foreach($row->taxes_ids as $tax_id) {
                                        $taxes_html .= '<a href=\'' . url('admin/tax-update/' . $tax_id) . '\' target=\'_blank\' class=\'mr-1\'>' . $tax_id . '</a>';
                                    }
                                }
                                ?>
                                <a href="#" onclick="return false;" class="mr-2 text-decoration-none <?= $taxes_html ? null : 'opacity-0' ?>" data-toggle="popover" data-placement="top" data-container="body" data-html="true" data-content="<?= $taxes_html ?>">
                                    <i class="fas fa-fw fa-sm fa-paperclip text-muted"></i>
                                </a>

                                <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= sprintf(l('global.datetime_tooltip'), '<br />' . \Altum\Date::get($row->datetime, 2) . '<br /><small>' . \Altum\Date::get($row->datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->datetime) . ')</small>') ?>">
                                    <i class="fas fa-fw fa-calendar text-muted"></i>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex justify-content-end">
                                    <?= include_view(THEME_PATH . 'views/admin/payments/admin_payment_dropdown_button.php', [
                                        'id' => $row->id,
                                        'payment_proof' => $row->payment_proof,
                                        'processor' => $row->processor,
                                        'status' => $row->status
                                    ]) ?>
                                </div>
                            </td>
                        </tr>

                    <?php endwhile ?>

                    <tr>
                        <td colspan="6">
                            <a href="<?= url('admin/payments') ?>" class="text-muted text-decoration-none small">
                                <i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?>
                            </a>
                        </td>
                    </tr>
                    </tbody>
                </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
<?php endif ?>
<?php endif ?>

<?php if(!$is_sensitive_dashboard): ?>
<div class="row align-items-start" style="order: 13;">
    <div class="col-12 col-xl-6 p-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between mb-4">
                    <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-store text-primary-900 mr-2"></i> <?= l('admin_index.sales_link_missing.header') ?></h1>

                    <div>
                        <span class="badge badge-warning">
                            <?= sprintf(l('admin_index.sales_link_missing.total'), nr($data->missing_sales_link_total)) ?>
                        </span>
                    </div>
                </div>

                <p class="text-muted mb-3"><?= l('admin_index.sales_link_missing.subheader') ?></p>

                <form method="get" action="<?= url('admin') ?>" class="mb-3">
                    <input type="hidden" name="sales_page" value="1" />
                    <div class="input-group">
                        <input
                            type="search"
                            name="sales_search"
                            class="form-control"
                            value="<?= $data->missing_sales_link_search ?>"
                            placeholder="<?= l('admin_index.sales_link_missing.search_placeholder') ?>"
                        />
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-outline-primary"><?= l('global.search') ?></button>
                            <a href="<?= url('admin') ?>" class="btn btn-outline-secondary"><?= l('global.reset') ?></a>
                        </div>
                    </div>
                </form>

                <?php if($data->missing_sales_link_total == 0 && empty($data->missing_sales_link_search)): ?>
                    <div class="alert alert-success mb-0"><?= l('admin_index.sales_link_missing.empty') ?></div>
                <?php elseif(empty($data->missing_sales_link_users)): ?>
                    <div class="alert alert-info mb-0"><?= l('admin_index.sales_link_missing.no_results') ?></div>
                <?php else: ?>
                    <div class="table-responsive table-custom-container mb-3" id="sales_link_missing_table_container">
                        <table class="table table-custom">
                            <thead>
                            <tr>
                                <th><?= l('global.user') ?></th>
                                <th><?= l('admin_index.sales_link_missing.forever_id') ?></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody id="sales_link_missing_table_body">
                            <?php foreach($data->missing_sales_link_users as $pending_user): ?>
                                <tr class="sales-link-missing-row">
                                    <td class="text-nowrap">
                                        <a href="<?= url('admin/user-view/' . $pending_user->user_id) ?>"><?= $pending_user->name ?></a>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge badge-light"><?= $pending_user->forever_id ?></span>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="d-flex justify-content-end">
                                            <a href="<?= url('admin/user-view/' . $pending_user->user_id) ?>" class="btn btn-sm btn-outline-primary"><?= l('global.view') ?></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="sales_link_missing_table_toggle" class="mb-3"></div>

                    <?= include_view(THEME_PATH . 'views/partials/admin_pagination.php', ['paginator' => $data->missing_sales_link_paginator]) ?>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6 p-2">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between mb-4">
                    <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-graduation-cap text-primary-900 mr-2"></i> <?= l('admin_index.fcc_pending.header') ?></h1>

                    <div>
                        <span class="badge badge-warning">
                            <?= sprintf(l('admin_index.fcc_pending.total'), nr($data->fcc_pending_education_total)) ?>
                        </span>
                    </div>
                </div>

                <p class="text-muted mb-3"><?= l('admin_index.fcc_pending.subheader') ?></p>

                <form method="get" action="<?= url('admin') ?>" class="mb-3">
                    <input type="hidden" name="fcc_page" value="1" />
                    <div class="input-group">
                        <input
                            type="search"
                            name="fcc_search"
                            class="form-control"
                            value="<?= $data->fcc_pending_education_search ?>"
                            placeholder="<?= l('admin_index.fcc_pending.search_placeholder') ?>"
                        />
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-outline-primary"><?= l('global.search') ?></button>
                            <a href="<?= url('admin') ?>" class="btn btn-outline-secondary"><?= l('global.reset') ?></a>
                        </div>
                    </div>
                </form>

                <?php if($data->fcc_pending_education_total == 0 && empty($data->fcc_pending_education_search)): ?>
                    <div class="alert alert-success mb-0"><?= l('admin_index.fcc_pending.empty') ?></div>
                <?php elseif(empty($data->fcc_pending_education_users)): ?>
                    <div class="alert alert-info mb-0"><?= l('admin_index.fcc_pending.no_results') ?></div>
                <?php else: ?>
                    <div class="table-responsive table-custom-container mb-3" id="fcc_pending_table_container">
                        <table class="table table-custom">
                            <thead>
                            <tr>
                                <th><?= l('global.user') ?></th>
                                <th><?= l('admin_index.fcc_pending.forever_id') ?></th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody id="fcc_pending_table_body">
                            <?php foreach($data->fcc_pending_education_users as $pending_user): ?>
                                <tr class="fcc-pending-row">
                                    <td class="text-nowrap">
                                        <a href="<?= url('admin/user-view/' . $pending_user->user_id) ?>"><?= $pending_user->name ?></a>
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge badge-light"><?= $pending_user->forever_id ?></span>
                                    </td>
                                    <td class="text-nowrap">
                                        <div class="d-flex justify-content-end">
                                            <a href="<?= url('admin/user-view/' . $pending_user->user_id) ?>" class="btn btn-sm btn-outline-primary"><?= l('global.view') ?></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>

                    <div id="fcc_pending_table_toggle" class="mb-3"></div>

                    <?= include_view(THEME_PATH . 'views/partials/admin_pagination.php', ['paginator' => $data->fcc_pending_education_paginator]) ?>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
<?php endif ?>

</div>
<!-- /Custom code: FC-2026-03-18 -->

<?php require THEME_PATH . 'views/partials/js_chart_defaults.php' ?>

<?php ob_start() ?>
<script>
    'use strict';

    /* Custom code: FC-2026-03-04: admin dashboard phase 1 analytics rendering */
    let dashboard_kpi_period = 'today';
    let dashboard_kpi_payload = {};
    let biolink_analytics_payload = {};
    let biolink_analytics_period = '30d';
    let funnels_analytics_payload = {};
    let funnels_analytics_period = '30d';
    let funnels_analytics_filter = 'all';
    let sales_subscriptions_chart_payload = {};
    let sales_subscriptions_chart_period = 30;
    let biolink_selected_collaborator = null;
    let biolink_search_timeout = null;
    let biolink_search_request_id = 0;
    const biolink_collaborator_cache = {};
    let dashboard_charts = {
        revenue: null,
        users: null,
        sales_subscriptions: null,
        biolink_analytics: null,
        funnels_analytics: null,
    };

    const escape_html = value => {
        if(value === null || value === undefined) {
            return '';
        }

        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    };

    const compact_lists_state = {};
    const render_compact_list = (container_selector, items_html, visible_limit = 5, on_after_render = null) => {
        const container = document.querySelector(container_selector);
        if(!container) {
            return;
        }

        if(!Array.isArray(items_html) || !items_html.length) {
            container.innerHTML = `<span class="text-muted"><?= l('global.no_data') ?></span>`;
            return;
        }

        if(compact_lists_state[container_selector] === undefined) {
            compact_lists_state[container_selector] = false;
        }

        const is_expanded = compact_lists_state[container_selector];
        const should_toggle = items_html.length > visible_limit;
        const visible_items = should_toggle && !is_expanded ? items_html.slice(0, visible_limit) : items_html;

        const content_html = visible_items.join('');
        const toggle_html = should_toggle ? `
            <div class="pt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-compact-toggle="${escape_html(container_selector)}">
                    ${is_expanded ? '<?= l('admin_index.compact_list.show_less') ?>' : '<?= l('global.view_more') ?>'}
                </button>
            </div>
        ` : '';

        container.innerHTML = content_html + toggle_html;

        if(typeof on_after_render === 'function') {
            on_after_render();
        }

        const toggle_button = container.querySelector('[data-compact-toggle]');
        if(toggle_button) {
            toggle_button.addEventListener('click', () => {
                compact_lists_state[container_selector] = !compact_lists_state[container_selector];
                render_compact_list(container_selector, items_html, visible_limit, on_after_render);
            });
        }
    };

    const compact_tables_state = {};
    const init_compact_table_rows = (rows_selector, toggle_selector, visible_limit = 5) => {
        const rows = [...document.querySelectorAll(rows_selector)];
        const toggle_container = document.querySelector(toggle_selector);

        if(!rows.length || !toggle_container) {
            return;
        }

        if(rows.length <= visible_limit) {
            toggle_container.innerHTML = '';
            return;
        }

        if(compact_tables_state[rows_selector] === undefined) {
            compact_tables_state[rows_selector] = false;
        }

        const is_expanded = compact_tables_state[rows_selector];
        rows.forEach((row, index) => {
            row.classList.toggle('d-none', !is_expanded && index >= visible_limit);
        });

        toggle_container.innerHTML = `
            <button type="button" class="btn btn-sm btn-outline-secondary" data-table-toggle="${escape_html(rows_selector)}">
                ${is_expanded ? '<?= l('admin_index.compact_list.show_less') ?>' : '<?= l('global.view_more') ?>'}
            </button>
        `;

        const toggle_button = toggle_container.querySelector('[data-table-toggle]');
        if(toggle_button) {
            toggle_button.addEventListener('click', () => {
                compact_tables_state[rows_selector] = !compact_tables_state[rows_selector];
                init_compact_table_rows(rows_selector, toggle_selector, visible_limit);
            });
        }
    };

    const compact_items_state = {};
    const init_compact_items = (items_selector, toggle_selector, visible_limit = 4) => {
        const items = [...document.querySelectorAll(items_selector)];
        const toggle_container = document.querySelector(toggle_selector);

        if(!items.length || !toggle_container) {
            return;
        }

        if(items.length <= visible_limit) {
            toggle_container.innerHTML = '';
            return;
        }

        if(compact_items_state[items_selector] === undefined) {
            compact_items_state[items_selector] = false;
        }

        const is_expanded = compact_items_state[items_selector];
        items.forEach((item, index) => {
            item.classList.toggle('d-none', !is_expanded && index >= visible_limit);
        });

        toggle_container.innerHTML = `
            <button type="button" class="btn btn-sm btn-outline-secondary" data-items-toggle="${escape_html(items_selector)}">
                ${is_expanded ? '<?= l('admin_index.compact_list.show_less') ?>' : '<?= l('global.view_more') ?>'}
            </button>
        `;

        const toggle_button = toggle_container.querySelector('[data-items-toggle]');
        if(toggle_button) {
            toggle_button.addEventListener('click', () => {
                compact_items_state[items_selector] = !compact_items_state[items_selector];
                init_compact_items(items_selector, toggle_selector, visible_limit);
            });
        }
    };

    /* Custom code: FC-2026-03-18: guard dashboard rendering across split views */
    const get_element = selector => document.querySelector(selector);
    const set_text_if_present = (selector, value) => {
        const element = get_element(selector);

        if(element) {
            element.innerText = value;
        }

        return element;
    };

    const set_html_if_present = (selector, value) => {
        const element = get_element(selector);

        if(element) {
            element.innerHTML = value;
        }

        return element;
    };
    /* /Custom code: FC-2026-03-18 */

    const render_kpi = period => {
        if(!dashboard_kpi_payload[period]) {
            return;
        }

        const metric = dashboard_kpi_payload[period];

        document.querySelector('#kpi_payments_count').innerText = nr(metric.payments_count ?? 0);
        document.querySelector('#kpi_net_earnings').innerText = nr(metric.net_earnings ?? 0);
        document.querySelector('#kpi_new_users').innerText = nr(metric.new_users ?? 0);
        document.querySelector('#kpi_active_pro_packages').innerText = nr(metric.active_pro_packages ?? 0);
        document.querySelector('#kpi_churn_rate').innerText = nr(metric.churn_rate ?? 0);
        document.querySelector('#kpi_arpu').innerText = nr(metric.arpu ?? 0);
    };

    const render_realtime = realtime => {
        if(!get_element('#realtime_online_users') && !get_element('#realtime_online_users_card')) {
            return;
        }

        set_text_if_present('#realtime_online_users', nr(realtime.online_users ?? 0));
        set_text_if_present('#realtime_online_users_card', nr(realtime.online_users ?? 0));
        set_text_if_present('#realtime_active_sessions', nr(realtime.active_sessions ?? 0));
        set_text_if_present('#realtime_recent_logins_total', nr(realtime.recent_logins_total ?? 0));

        /* Custom code: FC-2026-03-08: render online users modal list */
        const online_users_list_html = (realtime.online_users_list ?? []).map(online_user => {
            const last_activity_date = online_user.last_activity ? new Date(online_user.last_activity.replace(' ', 'T')) : null;
            const last_activity_label = last_activity_date && !isNaN(last_activity_date) ? last_activity_date.toLocaleString() : '-';

            return `
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span><a href="${url}admin/user-view/${online_user.user_id}">${escape_html(online_user.name)}</a></span>
                    <span class="text-muted">${escape_html(last_activity_label)}</span>
                </div>
            `;
        }).join('');

        set_html_if_present('#realtime_online_users_list', online_users_list_html || `<span class="text-muted"><?= l('global.no_data') ?></span>`);
        /* /Custom code: FC-2026-03-08 */

        const recent_logins_html = (realtime.recent_logins ?? []).map(login => {
            const login_date = login.datetime ? new Date(login.datetime.replace(' ', 'T')) : null;
            const login_date_label = login_date && !isNaN(login_date) ? login_date.toLocaleString() : '-';

            return `
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span><a href="${url}admin/user-view/${login.user_id}">${escape_html(login.name)}</a></span>
                    <span class="text-muted">${escape_html(login_date_label)}</span>
                </div>
            `;
        }).join('');

        set_html_if_present('#realtime_recent_logins', recent_logins_html || `<span class="text-muted"><?= l('global.no_data') ?></span>`);

        const online_collaborators_html = (realtime.online_collaborators ?? []).map(collaborator => {
            const last_activity_date = collaborator.last_activity ? new Date(collaborator.last_activity.replace(' ', 'T')) : null;
            const last_activity_label = last_activity_date && !isNaN(last_activity_date) ? last_activity_date.toLocaleString() : '-';

            return `
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span><a href="${url}admin/user-view/${collaborator.user_id}">${escape_html(collaborator.name)}</a></span>
                    <span class="text-muted">${escape_html(last_activity_label)}</span>
                </div>
            `;
        }).join('');

        set_html_if_present('#realtime_online_collaborators', online_collaborators_html || `<span class="text-muted"><?= l('global.no_data') ?></span>`);
    };

    const render_charts = charts => {
        const users_chart_canvas = document.getElementById('dashboard_users_chart');
        if(!users_chart_canvas) {
            return;
        }

        const users_chart_context = users_chart_canvas.getContext('2d');

        if(!dashboard_charts.users) {
            dashboard_charts.users = new Chart(users_chart_context, {
                type: 'line',
                data: {
                    labels: charts.labels ?? [],
                    datasets: [{
                        label: '<?= l('admin_index.analytics_phase1.charts.active_users_dataset') ?>',
                        data: charts.active_users_series ?? [],
                        borderColor: chart_css.getPropertyValue('--info'),
                        backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--info'), 0.08),
                        fill: true,
                        tension: 0.35
                    }]
                },
                options: {
                    ...chart_options,
                    plugins: {
                        ...chart_options.plugins,
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        } else {
            dashboard_charts.users.data.labels = charts.labels ?? [];
            dashboard_charts.users.data.datasets[0].data = charts.active_users_series ?? [];
            dashboard_charts.users.update();
        }

    };

    /* Custom code: FC-2026-03-18: sales subscriptions chart period slicing */
    const get_sales_subscriptions_chart_slice = () => {
        const period = Number(sales_subscriptions_chart_period) || 30;

        return {
            labels: (sales_subscriptions_chart_payload.labels ?? []).slice(-period),
            active_pro_packages_series: (sales_subscriptions_chart_payload.active_pro_packages_series ?? []).slice(-period),
            active_trials_series: (sales_subscriptions_chart_payload.active_trials_series ?? []).slice(-period),
        };
    };

    const update_sales_subscriptions_chart = () => {
        const sales_subscriptions_chart_canvas = document.getElementById('sales_subscriptions_chart');
        if(!sales_subscriptions_chart_canvas) {
            return;
        }

        const chart_data = get_sales_subscriptions_chart_slice();
        const sales_subscriptions_chart_context = sales_subscriptions_chart_canvas.getContext('2d');

        if(!dashboard_charts.sales_subscriptions) {
            dashboard_charts.sales_subscriptions = new Chart(sales_subscriptions_chart_context, {
                type: 'line',
                data: {
                    labels: chart_data.labels,
                    datasets: [{
                        label: '<?= l('admin_index.sales_subscriptions.chart_active_pro_packages') ?>',
                        data: chart_data.active_pro_packages_series,
                        borderColor: chart_css.getPropertyValue('--success'),
                        backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--success'), 0.08),
                        fill: true
                    }, {
                        label: '<?= l('admin_index.sales_subscriptions.chart_active_trials') ?>',
                        data: chart_data.active_trials_series,
                        borderColor: chart_css.getPropertyValue('--primary'),
                        backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--primary'), 0.08),
                        fill: true
                    }]
                },
                options: {
                    ...chart_options,
                    plugins: {
                        ...chart_options.plugins,
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        } else {
            dashboard_charts.sales_subscriptions.data.labels = chart_data.labels;
            dashboard_charts.sales_subscriptions.data.datasets[0].data = chart_data.active_pro_packages_series;
            dashboard_charts.sales_subscriptions.data.datasets[1].data = chart_data.active_trials_series;
            dashboard_charts.sales_subscriptions.update();
        }
    };
    /* /Custom code: FC-2026-03-18 */

    const render_sales_subscriptions = sales_subscriptions => {
        if(!get_element('#sales_recurring_revenue_current_month')) {
            return;
        }

        set_text_if_present('#sales_recurring_revenue_current_month', nr(sales_subscriptions.recurring_revenue_current_month ?? 0));
        set_text_if_present('#sales_active_paid_subscriptions', nr(sales_subscriptions.active_paid_subscriptions ?? 0));
        set_text_if_present('#sales_active_total_pro_collaborators', nr(sales_subscriptions.active_total_pro_collaborators ?? 0));
        set_text_if_present('#sales_cancelled_billing_30d', nr(sales_subscriptions.cancelled_billing_30d ?? 0));
        set_text_if_present('#sales_new_subscriptions_30d', nr(sales_subscriptions.new_subscriptions_30d ?? 0));
        set_text_if_present('#sales_cancelled_subscriptions_30d', nr(sales_subscriptions.cancelled_subscriptions_30d ?? 0));
        set_text_if_present('#sales_failed_payments_30d', nr(sales_subscriptions.failed_payments_30d ?? 0));
        set_text_if_present('#sales_plan_changes_30d', nr(sales_subscriptions.plan_changes_30d ?? 0));
        sales_subscriptions_chart_payload = sales_subscriptions.chart ?? {};

        const at_risk_html = (sales_subscriptions.at_risk_trial_users ?? []).map(user => {
            const plan_expiration_date = user.plan_expiration_date ? new Date(user.plan_expiration_date.replace(' ', 'T')) : null;
            const plan_expiration_label = plan_expiration_date && !isNaN(plan_expiration_date) ? plan_expiration_date.toLocaleDateString() : '-';

            return `
                <div class="border-bottom py-2">
                    <div><a href="${url}admin/user-view/${user.user_id}">${escape_html(user.name)}</a></div>
                    <div class="text-muted">${escape_html(user.email)}</div>
                    <div class="text-muted"><?= l('admin_index.sales_subscriptions.at_risk_until') ?>: ${escape_html(plan_expiration_label)}</div>
                </div>
            `;
        }).join('');

        set_html_if_present('#sales_at_risk_trial_users', at_risk_html || `<span class="text-muted"><?= l('global.no_data') ?></span>`);

        update_sales_subscriptions_chart();
    };

    /* Custom code: FC-2026-03-04: phase 5 action center rendering */
    const render_action_center = action_center => {
        if(!get_element('#action_warning_trials')) {
            return;
        }

        const warnings_map = {};
        (action_center.warnings ?? []).forEach(item => {
            warnings_map[item.key] = item;
        });

        const trials_warning = warnings_map.trials_expiring_without_subscription || {is_active: false, value: 0};
        const collaborators_warning = warnings_map.high_collaborator_clicks_no_billing || {is_active: false, value: 0};

        get_element('#action_warning_trials').className = trials_warning.is_active ? 'alert alert-danger mb-0' : 'alert alert-success mb-0';
        get_element('#action_warning_trials').innerText = trials_warning.is_active
            ? `<?= l('admin_index.action_center.warning_trials_active') ?> (${nr(trials_warning.value ?? 0)})`
            : `<?= l('admin_index.action_center.warning_trials_stable') ?> (${nr(trials_warning.value ?? 0)})`;

        get_element('#action_warning_collaborators').className = collaborators_warning.is_active ? 'alert alert-warning mb-0' : 'alert alert-success mb-0';
        get_element('#action_warning_collaborators').innerText = collaborators_warning.is_active
            ? `<?= l('admin_index.action_center.warning_collaborators_active') ?> (${nr(collaborators_warning.value ?? 0)})`
            : `<?= l('admin_index.action_center.warning_collaborators_stable') ?> (${nr(collaborators_warning.value ?? 0)})`;

        const urgent_trials_items = (action_center.urgent_trial_actions ?? []).map(user => {
            const plan_expiration_date = user.plan_expiration_date ? new Date(user.plan_expiration_date.replace(' ', 'T')) : null;
            const plan_expiration_label = plan_expiration_date && !isNaN(plan_expiration_date) ? plan_expiration_date.toLocaleDateString() : '-';
            const days_left_label = user.days_left !== null && user.days_left !== undefined
                ? `<?= l('admin_index.action_center.days_left_prefix') ?> ${nr(user.days_left)}`
                : '-';

            return `
                <div class="border-bottom py-2">
                    <div><a href="${url}admin/user-view/${user.user_id}">${escape_html(user.name)}</a></div>
                    <div class="text-muted">${escape_html(user.email || '')}</div>
                    <div class="text-muted"><?= l('admin_index.action_center.trial_ends_label') ?>: ${escape_html(plan_expiration_label)} · ${escape_html(days_left_label)}</div>
                </div>
            `;
        });

        set_text_if_present('#action_urgent_trials_count', `(${nr(urgent_trials_items.length)})`);
        render_compact_list('#action_urgent_trials', urgent_trials_items, 5);

        const collaborator_opportunities_items = (action_center.collaborator_opportunities ?? []).map(item => {
            const billing_status_label = item.billing_state === 'cancelled_during_trial'
                ? `<?= l('admin_index.action_center.billing_cancelled_during_trial') ?>`
                : `<?= l('admin_index.action_center.billing_no_active_subscription') ?>`;

            return `
                <div class="border-bottom py-2">
                    <div><a href="${url}admin/user-view/${item.user_id}">${escape_html(item.name)}</a></div>
                    <div class="text-muted">${escape_html(item.email || '')}</div>
                    <div class="text-muted"><?= l('admin_index.action_center.forever_clicks_30d_label') ?>: ${nr(item.forever_clicks_30d ?? 0)} · ${billing_status_label}</div>
                </div>
            `;
        });

        set_text_if_present('#action_collaborator_opportunities_count', `(${nr(collaborator_opportunities_items.length)})`);
        render_compact_list('#action_collaborator_opportunities', collaborator_opportunities_items, 5);
    };
    /* /Custom code: FC-2026-03-04 */

    /* Custom code: FC-2026-03-17: billing risk dashboard rendering */
    const render_billing_risk = billing_risk => {
        if(!get_element('#billing_risk_past_due')) {
            return;
        }

        const counts = billing_risk.counts ?? {};
        const state_badges = {
            past_due: 'warning',
            past_due_critical: 'danger',
            access_revoked: 'secondary',
            healthy: 'success',
        };

        set_text_if_present('#billing_risk_past_due', nr(counts.past_due ?? 0));
        set_text_if_present('#billing_risk_critical', nr(counts.past_due_critical ?? 0));
        set_text_if_present('#billing_risk_revoked', nr(counts.access_revoked ?? 0));
        set_text_if_present('#billing_risk_expiring', nr(counts.expiring_24h ?? 0));
        set_text_if_present('#billing_risk_recovered', nr(counts.recovered_7d ?? 0));

        const users = billing_risk.risk_users ?? [];
        const items = users.map(item => {
            const state_label = {
                past_due: `<?= l('admin_billing_risk.state_past_due') ?>`,
                past_due_critical: `<?= l('admin_billing_risk.state_past_due_critical') ?>`,
                access_revoked: `<?= l('admin_billing_risk.state_access_revoked') ?>`,
                healthy: `<?= l('admin_billing_risk.state_healthy') ?>`,
            };

            const notification_label = {
                warning_first: `<?= l('admin_billing_risk.notification_warning_first') ?>`,
                warning_second: `<?= l('admin_billing_risk.notification_warning_second') ?>`,
                recovered: `<?= l('admin_billing_risk.notification_recovered') ?>`,
                revoked: `<?= l('admin_billing_risk.notification_revoked') ?>`,
            };

            return `
                <div class="border-bottom py-2">
                    <div class="d-flex flex-column flex-lg-row justify-content-between">
                        <div class="mr-3">
                            <div><a href="${url}admin/user-view/${item.user_id}">${escape_html(item.name || '<?= l('global.unknown') ?>')}</a></div>
                            <div class="text-muted">${escape_html(item.email || '')}</div>
                            <div class="text-muted">${escape_html(item.last_failed_reason_text || '<?= l('global.none') ?>')}</div>
                        </div>
                        <div class="text-lg-right mt-2 mt-lg-0">
                            <div><span class="badge badge-${state_badges[item.billing_state] || 'light'}">${state_label[item.billing_state] || item.billing_state || '<?= l('global.unknown') ?>'}</span></div>
                            <div class="text-muted mt-1"><?= l('admin_billing_risk.grace_until') ?>: ${escape_html(item.grace_until || '<?= l('global.none') ?>')}</div>
                            <div class="text-muted"><?= l('admin_billing_risk.last_notification') ?>: ${escape_html(notification_label[item.last_notification_stage] || '<?= l('global.none') ?>')}</div>
                        </div>
                    </div>
                </div>
            `;
        });

        set_text_if_present('#billing_risk_users_count', `(${nr(users.length)})`);
        render_compact_list('#billing_risk_users', items, 7);
    };
    /* /Custom code: FC-2026-03-17 */

    /* Custom code: FC-2026-03-04: phase 4 biolink analytics rendering */
    const period_labels = {
        today: '<?= l('admin_index.analytics_phase1.period.today') ?>',
        '7d': '<?= l('admin_index.analytics_phase1.period.7d') ?>',
        '30d': '<?= l('admin_index.analytics_phase1.period.30d') ?>',
        '90d': '<?= l('admin_index.analytics_phase1.period.90d') ?>',
    };

    const render_biolink_selected_collaborator_state = () => {
        const selected_label = document.querySelector('#biolink_selected_collaborator_label');
        const reset_button = document.querySelector('#biolink_selection_reset');

        if(!selected_label || !reset_button) {
            return;
        }

        if(biolink_selected_collaborator) {
            selected_label.innerText = biolink_selected_collaborator.name || '<?= l('global.unknown') ?>';
            reset_button.removeAttribute('disabled');
        } else {
            selected_label.innerText = '<?= l('admin_index.biolink_analytics.selection_all') ?>';
            reset_button.setAttribute('disabled', 'disabled');
        }
    };

    const render_biolink_user_details = user_id => {
        const period_data = biolink_analytics_payload.periods?.[biolink_analytics_period] || {};
        const user_details_map = period_data.user_details || {};

        if(!user_id || !user_details_map[user_id]) {
            document.querySelector('#biolink_user_clicks_total').innerText = '-';
            document.querySelector('#biolink_user_forever_shop_clicks').innerText = '-';
            document.querySelector('#biolink_user_forever_registration_clicks').innerText = '-';
            document.querySelector('#biolink_user_top_countries_count').innerText = '(0)';
            document.querySelector('#biolink_user_top_countries').innerHTML = `<span class="text-muted"><?= l('global.no_data') ?></span>`;
            return;
        }

        const user_details = user_details_map[user_id];
        document.querySelector('#biolink_user_clicks_total').innerText = nr(user_details.clicks_total ?? 0);
        document.querySelector('#biolink_user_forever_shop_clicks').innerText = nr(user_details.forever_shop_clicks ?? 0);
        document.querySelector('#biolink_user_forever_registration_clicks').innerText = nr(user_details.forever_registration_clicks ?? 0);

        const user_top_countries_items = (user_details.top_countries ?? []).map(country => `
            <div class="d-flex justify-content-between border-bottom py-1">
                <span>${escape_html(country.country_code || '-')}</span>
                <span>${nr(country.total ?? 0)}</span>
            </div>
        `);

        document.querySelector('#biolink_user_top_countries_count').innerText = `(${nr(user_top_countries_items.length)})`;
        render_compact_list('#biolink_user_top_countries', user_top_countries_items, 5);
    };

    const render_biolink_collaborator_details = collaborator => {
        const period_data = collaborator?.periods?.[biolink_analytics_period] || {};

        document.querySelector('#biolink_user_details_title').innerText = collaborator?.name
            ? `<?= l('admin_index.biolink_analytics.user_details_for') ?> ${collaborator.name}`
            : `<?= l('admin_index.biolink_analytics.user_details_default_title') ?>`;

        document.querySelector('#biolink_user_clicks_total').innerText = nr(period_data.clicks_total ?? 0);
        document.querySelector('#biolink_user_forever_shop_clicks').innerText = nr(period_data.forever_shop_clicks ?? 0);
        document.querySelector('#biolink_user_forever_registration_clicks').innerText = nr(period_data.forever_registration_clicks ?? 0);

        const user_top_countries_items = (period_data.top_countries ?? []).map(country => `
            <div class="d-flex justify-content-between border-bottom py-1">
                <span>${escape_html(country.country_code || '-')}</span>
                <span>${nr(country.total ?? 0)}</span>
            </div>
        `);

        document.querySelector('#biolink_user_top_countries_count').innerText = `(${nr(user_top_countries_items.length)})`;
        render_compact_list('#biolink_user_top_countries', user_top_countries_items, 5);
    };

    const render_biolink_chart = (chart_data, selected_period_label, collaborator_name = null) => {
        const biolink_chart_context = document.getElementById('biolink_analytics_chart')?.getContext('2d');
        if(!biolink_chart_context) {
            return;
        }

        document.querySelector('#biolink_chart_label').innerText = collaborator_name
            ? `<?= l('admin_index.biolink_analytics.chart_header') ?> · ${collaborator_name} (${selected_period_label})`
            : `<?= l('admin_index.biolink_analytics.chart_header') ?> (${selected_period_label})`;

        const biolink_chart_datasets = [{
            label: '<?= l('admin_index.biolink_analytics.clicks_selected_period') ?>',
            data: chart_data.clicks_total_series ?? [],
            borderColor: chart_css.getPropertyValue('--primary'),
            backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--primary'), 0.08),
            fill: true,
            tension: 0.35
        }, {
            label: '<?= l('admin_index.biolink_analytics.forever_shop_clicks_selected_period') ?>',
            data: chart_data.forever_shop_clicks_series ?? [],
            borderColor: chart_css.getPropertyValue('--success'),
            backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--success'), 0.08),
            fill: true,
            tension: 0.35
        }, {
            label: '<?= l('admin_index.biolink_analytics.forever_registration_clicks_selected_period') ?>',
            data: chart_data.forever_registration_clicks_series ?? [],
            borderColor: chart_css.getPropertyValue('--warning'),
            backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--warning'), 0.08),
            fill: true,
            tension: 0.35
        }];

        if(!dashboard_charts.biolink_analytics) {
            dashboard_charts.biolink_analytics = new Chart(biolink_chart_context, {
                type: 'line',
                data: {
                    labels: chart_data.labels ?? [],
                    datasets: biolink_chart_datasets,
                },
                options: {
                    ...chart_options,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        ...chart_options.plugins,
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        } else {
            dashboard_charts.biolink_analytics.data.labels = chart_data.labels ?? [];
            dashboard_charts.biolink_analytics.data.datasets = biolink_chart_datasets;
            dashboard_charts.biolink_analytics.update();
        }
    };

    const render_biolink_period = () => {
        if(!get_element('#biolink_period_total_clicks')) {
            return;
        }

        const period_data = biolink_analytics_payload.periods?.[biolink_analytics_period] || {};
        const selected_period_label = period_labels[biolink_analytics_period] || period_labels['30d'];
        const selected_collaborator_period_data = biolink_selected_collaborator?.periods?.[biolink_analytics_period] || null;

        document.querySelector('#biolink_period_total_label').innerText = `<?= l('admin_index.biolink_analytics.clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_period_shop_label').innerText = `<?= l('admin_index.biolink_analytics.forever_shop_clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_period_registration_label').innerText = `<?= l('admin_index.biolink_analytics.forever_registration_clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_period_total_clicks').innerText = nr(period_data.clicks_total ?? 0);
        document.querySelector('#biolink_period_shop_clicks').innerText = nr(period_data.forever_shop_clicks ?? 0);
        document.querySelector('#biolink_period_registration_clicks').innerText = nr(period_data.forever_registration_clicks ?? 0);
        render_biolink_selected_collaborator_state();

        document.querySelector('#biolink_top_countries_label').innerText = `<?= l('admin_index.biolink_analytics.top_countries_header') ?> (${selected_period_label})`;
        document.querySelector('#biolink_leaderboard_label').innerText = `<?= l('admin_index.biolink_analytics.leaderboard_header') ?> (${selected_period_label})`;
        document.querySelector('#biolink_user_clicks_total_label').innerText = `<?= l('admin_index.biolink_analytics.clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_user_forever_shop_label').innerText = `<?= l('admin_index.biolink_analytics.forever_shop_clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_user_forever_registration_label').innerText = `<?= l('admin_index.biolink_analytics.forever_registration_clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_top_shop_sources_label').innerText = `<?= l('admin_index.biolink_analytics.top_shop_sources_header') ?> (${selected_period_label})`;
        document.querySelector('#biolink_top_registration_sources_label').innerText = `<?= l('admin_index.biolink_analytics.top_registration_sources_header') ?> (${selected_period_label})`;

        document.querySelector('#biolink_top_countries_label').innerText = biolink_selected_collaborator
            ? `<?= l('admin_index.biolink_analytics.user_top_countries_header') ?> (${selected_period_label})`
            : `<?= l('admin_index.biolink_analytics.top_countries_header') ?> (${selected_period_label})`;

        const top_countries_items = ((selected_collaborator_period_data?.top_countries ?? period_data.top_countries) || []).map(country => `
            <div class="d-flex justify-content-between border-bottom py-2">
                <span>${escape_html(country.country_code || '-')}</span>
                <span>${nr(country.total ?? 0)}</span>
            </div>
        `);
        document.querySelector('#biolink_top_countries_count').innerText = `(${nr(top_countries_items.length)})`;
        render_compact_list('#biolink_top_countries', top_countries_items, 5);

        const leaderboard_items = (period_data.leaderboard ?? []).map((entry, index) => `
            <button type="button" class="btn btn-link p-0 d-flex justify-content-between border-bottom py-2 text-left w-100 biolink-leaderboard-user" data-user-id="${entry.user_id}" data-user-name="${escape_html(entry.name || '')}">
                <span>${index + 1}. ${escape_html(entry.name || '<?= l('global.unknown') ?>')}</span>
                <span>${nr(entry.total ?? 0)}</span>
            </button>
        `);

        document.querySelector('#biolink_leaderboard_count').innerText = `(${nr(leaderboard_items.length)})`;
        render_compact_list('#biolink_leaderboard', leaderboard_items, 5, () => {
            document.querySelectorAll('.biolink-leaderboard-user').forEach(button => {
                button.addEventListener('click', event => {
                    const user_id = event.currentTarget.getAttribute('data-user-id');
                    const user_name = event.currentTarget.getAttribute('data-user-name') || '';

                    const search_input = document.querySelector('#biolink_collaborator_search');
                    if(search_input) {
                        search_input.value = user_name;
                    }

                    load_biolink_collaborator(user_id, user_name);
                });
            });
        });

        if(biolink_selected_collaborator) {
            render_biolink_collaborator_details(biolink_selected_collaborator);
            render_biolink_chart(biolink_selected_collaborator.periods?.[biolink_analytics_period]?.chart ?? {}, selected_period_label, biolink_selected_collaborator.name || null);
        } else if((period_data.leaderboard ?? []).length) {
            const first_user = period_data.leaderboard[0];
            document.querySelector('#biolink_user_details_title').innerText = `<?= l('admin_index.biolink_analytics.user_details_for') ?> ${first_user.name ?? ''}`;
            render_biolink_user_details(String(first_user.user_id ?? ''));
            render_biolink_chart(period_data.chart ?? {}, selected_period_label);
        } else {
            document.querySelector('#biolink_user_details_title').innerText = `<?= l('admin_index.biolink_analytics.user_details_default_title') ?>`;
            render_biolink_user_details(null);
            render_biolink_chart(period_data.chart ?? {}, selected_period_label);
        }

        const top_shop_sources_items = (period_data.top_shop_sources ?? []).map(item => `
            <div class="d-flex justify-content-between border-bottom py-2">
                <span class="text-truncate mr-2" style="max-width: 80%;">${escape_html(item.source || '(direct)')}</span>
                <span>${nr(item.total ?? 0)}</span>
            </div>
        `);

        document.querySelector('#biolink_top_shop_sources_count').innerText = `(${nr(top_shop_sources_items.length)})`;
        render_compact_list('#biolink_top_shop_sources', top_shop_sources_items, 5);

        const top_registration_sources_items = (period_data.top_registration_sources ?? []).map(item => `
            <div class="d-flex justify-content-between border-bottom py-2">
                <span class="text-truncate mr-2" style="max-width: 80%;">${escape_html(item.source || '(direct)')}</span>
                <span>${nr(item.total ?? 0)}</span>
            </div>
        `);

        document.querySelector('#biolink_top_registration_sources_count').innerText = `(${nr(top_registration_sources_items.length)})`;
        render_compact_list('#biolink_top_registration_sources', top_registration_sources_items, 5);
    };

    const render_biolink_analytics = biolink_analytics => {
        if(!get_element('#biolink_period_total_clicks')) {
            return;
        }

        biolink_analytics_payload = biolink_analytics ?? {};

        render_biolink_period();
    };

    /* Custom code: FC-2026-03-26: funnels analytics rendering */
    const funnel_open_mode_labels = {
        popup: '<?= l('biolink_lead_funnel.open_mode_popup') ?>',
        page: '<?= l('biolink_lead_funnel.open_mode_page') ?>',
    };

    const funnel_thank_you_type_labels = {
        message: '<?= l('biolink_lead_funnel.thank_you_type_message') ?>',
        external_url: '<?= l('biolink_lead_funnel.thank_you_type_external_url') ?>',
        biolink_redirect: '<?= l('biolink_lead_funnel.thank_you_type_biolink_redirect') ?>',
        file_download: '<?= l('biolink_lead_funnel.thank_you_type_file_download') ?>',
    };

    const render_funnels_chart = (chart_data, selected_period_label) => {
        const funnels_chart_context = document.getElementById('funnels_analytics_chart')?.getContext('2d');
        if(!funnels_chart_context) {
            return;
        }

        document.querySelector('#funnels_chart_label').innerText = `<?= l('admin_index.funnels_analytics.chart_header') ?> (${selected_period_label})`;

        const funnels_chart_datasets = [{
            label: '<?= l('admin_index.funnels_analytics.unique_clicks') ?>',
            data: chart_data.unique_clicks_series ?? [],
            borderColor: chart_css.getPropertyValue('--primary'),
            backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--primary'), 0.08),
            fill: true,
            tension: 0.35,
            yAxisID: 'y'
        }, {
            label: '<?= l('admin_index.funnels_analytics.leads') ?>',
            data: chart_data.leads_series ?? [],
            borderColor: chart_css.getPropertyValue('--success'),
            backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--success'), 0.08),
            fill: true,
            tension: 0.35,
            yAxisID: 'y'
        }, {
            label: '<?= l('admin_index.funnels_analytics.conversion_rate') ?>',
            data: chart_data.conversion_rate_series ?? [],
            borderColor: chart_css.getPropertyValue('--warning'),
            backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--warning'), 0.08),
            fill: false,
            tension: 0.35,
            yAxisID: 'y1'
        }];

        if(!dashboard_charts.funnels_analytics) {
            dashboard_charts.funnels_analytics = new Chart(funnels_chart_context, {
                type: 'line',
                data: {
                    labels: chart_data.labels ?? [],
                    datasets: funnels_chart_datasets,
                },
                options: {
                    ...chart_options,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => nr(value)
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            grid: {
                                drawOnChartArea: false,
                            },
                            ticks: {
                                callback: value => `${nr(value)}%`
                            }
                        }
                    },
                    plugins: {
                        ...chart_options.plugins,
                        legend: {
                            display: true,
                            position: 'bottom'
                        }
                    }
                }
            });
        } else {
            dashboard_charts.funnels_analytics.data.labels = chart_data.labels ?? [];
            dashboard_charts.funnels_analytics.data.datasets = funnels_chart_datasets;
            dashboard_charts.funnels_analytics.update();
        }
    };

    const render_funnels_breakdown = (container_selector, rows, type_labels) => {
        const items = (rows ?? []).map(row => `
            <div class="border-bottom py-2">
                <div class="d-flex justify-content-between">
                    <strong>${escape_html(type_labels[row.type] || row.type || '<?= l('global.unknown') ?>')}</strong>
                    <span>${nr(row.conversion_rate ?? 0)}%</span>
                </div>
                <div class="text-muted mt-1">
                    <?= l('admin_index.funnels_analytics.breakdown_meta') ?>:
                    ${nr(row.funnels_count ?? 0)} ·
                    <?= l('admin_index.funnels_analytics.unique_clicks') ?>: ${nr(row.unique_clicks ?? 0)} ·
                    <?= l('admin_index.funnels_analytics.leads') ?>: ${nr(row.leads ?? 0)}
                </div>
            </div>
        `);

        render_compact_list(container_selector, items, 5);
    };

    const render_funnels_flow_summary = flow => {
        const items = [
            `<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong><?= l('admin_index.funnels_analytics.flow_views') ?></strong><span>${nr(flow.views ?? 0)}</span></div><div class="text-muted mt-1"><?= l('admin_index.funnels_analytics.flow_views_subheader') ?></div></div>`,
            `<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong><?= l('admin_index.funnels_analytics.flow_opens') ?></strong><span>${nr(flow.opens ?? 0)}</span></div><div class="text-muted mt-1"><?= l('admin_index.funnels_analytics.flow_opens_subheader') ?></div></div>`,
            `<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong><?= l('admin_index.funnels_analytics.flow_form_starts') ?></strong><span>${nr(flow.form_starts ?? 0)}</span></div><div class="text-muted mt-1"><?= l('admin_index.funnels_analytics.flow_entry_to_start') ?>: ${nr(flow.entry_to_start_rate ?? 0)}%</div></div>`,
            `<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong><?= l('admin_index.funnels_analytics.flow_submit_attempts') ?></strong><span>${nr(flow.submit_attempts ?? 0)}</span></div><div class="text-muted mt-1"><?= l('admin_index.funnels_analytics.flow_submit_errors') ?>: ${nr(flow.submit_errors ?? 0)}</div></div>`,
            `<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong><?= l('admin_index.funnels_analytics.flow_submit_success') ?></strong><span>${nr(flow.submit_success ?? 0)}</span></div><div class="text-muted mt-1"><?= l('admin_index.funnels_analytics.flow_start_to_success') ?>: ${nr(flow.start_to_success_rate ?? 0)}%</div></div>`,
            `<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong><?= l('admin_index.funnels_analytics.flow_thank_you_views') ?></strong><span>${nr(flow.thank_you_views ?? 0)}</span></div><div class="text-muted mt-1"><?= l('admin_index.funnels_analytics.flow_success_to_thank_you') ?>: ${nr(flow.success_to_thank_you_rate ?? 0)}%</div></div>`,
            `<div class="border-bottom py-2"><div class="d-flex justify-content-between"><strong><?= l('admin_index.funnels_analytics.flow_cta_clicks') ?></strong><span>${nr(flow.cta_clicks ?? 0)}</span></div><div class="text-muted mt-1"><?= l('admin_index.funnels_analytics.flow_success_to_cta') ?>: ${nr(flow.success_to_cta_rate ?? 0)}%</div></div>`
        ];

        render_compact_list('#funnels_flow_summary', items, 7);
    };

    const render_funnels_period = () => {
        if(!get_element('#funnels_total_funnels')) {
            return;
        }

        const selected_filter_payload = funnels_analytics_payload.filters?.[funnels_analytics_filter] || {};
        const period_data = selected_filter_payload.periods?.[funnels_analytics_period] || funnels_analytics_payload.periods?.[funnels_analytics_period] || {};
        const selected_period_label = period_labels[funnels_analytics_period] || period_labels['30d'];
        const flow = period_data.flow ?? {};

        document.querySelector('#funnels_total_funnels_label').innerText = `<?= l('admin_index.funnels_analytics.total_funnels') ?> (${selected_period_label})`;
        document.querySelector('#funnels_active_funnels_label').innerText = `<?= l('admin_index.funnels_analytics.active_funnels') ?> (${selected_period_label})`;
        document.querySelector('#funnels_active_collaborators_label').innerText = `<?= l('admin_index.funnels_analytics.active_collaborators') ?> (${selected_period_label})`;
        document.querySelector('#funnels_unique_clicks_label').innerText = `<?= l('admin_index.funnels_analytics.unique_clicks') ?> (${selected_period_label})`;
        document.querySelector('#funnels_leads_label').innerText = `<?= l('admin_index.funnels_analytics.leads') ?> (${selected_period_label})`;
        document.querySelector('#funnels_conversion_rate_label').innerText = `<?= l('admin_index.funnels_analytics.conversion_rate') ?> (${selected_period_label})`;
        document.querySelector('#funnels_flow_entry_points_label').innerText = `<?= l('admin_index.funnels_analytics.flow_entry_points') ?> (${selected_period_label})`;
        document.querySelector('#funnels_flow_starts_label').innerText = `<?= l('admin_index.funnels_analytics.flow_form_starts') ?> (${selected_period_label})`;
        document.querySelector('#funnels_flow_success_label').innerText = `<?= l('admin_index.funnels_analytics.flow_submit_success') ?> (${selected_period_label})`;
        document.querySelector('#funnels_flow_cta_label').innerText = `<?= l('admin_index.funnels_analytics.flow_cta_clicks') ?> (${selected_period_label})`;

        document.querySelector('#funnels_total_funnels').innerText = nr(period_data.total_funnels ?? 0);
        document.querySelector('#funnels_active_funnels').innerText = nr(period_data.active_funnels ?? 0);
        document.querySelector('#funnels_active_collaborators').innerText = nr(period_data.active_collaborators ?? 0);
        document.querySelector('#funnels_unique_clicks').innerText = nr(period_data.unique_clicks ?? 0);
        document.querySelector('#funnels_leads').innerText = nr(period_data.leads ?? 0);
        document.querySelector('#funnels_conversion_rate').innerText = `${nr(period_data.conversion_rate ?? 0)}%`;
        document.querySelector('#funnels_flow_entry_points').innerText = nr(flow.entry_points ?? 0);
        document.querySelector('#funnels_flow_starts').innerText = nr(flow.form_starts ?? 0);
        document.querySelector('#funnels_flow_success').innerText = nr(flow.submit_success ?? 0);
        document.querySelector('#funnels_flow_cta').innerText = nr(flow.cta_clicks ?? 0);
        document.querySelector('#funnels_flow_starts_meta').innerText = `<?= l('admin_index.funnels_analytics.flow_entry_to_start') ?>: ${nr(flow.entry_to_start_rate ?? 0)}%`;
        document.querySelector('#funnels_flow_success_meta').innerText = `<?= l('admin_index.funnels_analytics.flow_start_to_success') ?>: ${nr(flow.start_to_success_rate ?? 0)}%`;
        document.querySelector('#funnels_flow_cta_meta').innerText = `<?= l('admin_index.funnels_analytics.flow_success_to_cta') ?>: ${nr(flow.success_to_cta_rate ?? 0)}%`;

        document.querySelector('#funnels_top_funnels_label').innerText = `<?= l('admin_index.funnels_analytics.top_funnels_header') ?> (${selected_period_label})`;
        document.querySelector('#funnels_top_collaborators_label').innerText = `<?= l('admin_index.funnels_analytics.top_collaborators_header') ?> (${selected_period_label})`;
        document.querySelector('#funnels_open_mode_label').innerText = `<?= l('admin_index.funnels_analytics.open_mode_header') ?> (${selected_period_label})`;
        document.querySelector('#funnels_thank_you_label').innerText = `<?= l('admin_index.funnels_analytics.thank_you_header') ?> (${selected_period_label})`;
        document.querySelector('#funnels_top_conversion_label').innerText = `<?= l('admin_index.funnels_analytics.top_conversion_header') ?> (${selected_period_label})`;
        document.querySelector('#funnels_opportunities_label').innerText = `<?= l('admin_index.funnels_analytics.opportunities_header') ?> (${selected_period_label})`;
        document.querySelector('#funnels_flow_panel_label').innerText = `<?= l('admin_index.funnels_analytics.flow_header') ?> (${selected_period_label})`;
        document.querySelector('#funnels_flow_opportunities_label').innerText = `<?= l('admin_index.funnels_analytics.flow_opportunities_header') ?> (${selected_period_label})`;

        const top_funnels_items = (period_data.top_funnels ?? []).map((item, index) => `
            <div class="border-bottom py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pr-3">
                        <div>${index + 1}. <a href="${item.analytics_url}">${escape_html(item.name || '<?= l('global.unknown') ?>')}</a></div>
                        <div class="text-muted"><a href="${item.admin_user_url}">${escape_html(item.user_name || '<?= l('global.unknown') ?>')}</a> · ${escape_html(item.biolink_url || '')}</div>
                        <div class="text-muted">${escape_html(funnel_open_mode_labels[item.open_mode] || item.open_mode || '')} · ${escape_html(funnel_thank_you_type_labels[item.thank_you_type] || item.thank_you_type || '')}</div>
                    </div>
                    <div class="text-right">
                        <div><strong>${nr(item.leads ?? 0)}</strong></div>
                        <div class="text-muted"><?= l('admin_index.funnels_analytics.unique_clicks') ?>: ${nr(item.unique_clicks ?? 0)}</div>
                        <div class="text-muted">${nr(item.conversion_rate ?? 0)}%</div>
                    </div>
                </div>
            </div>
        `);
        render_compact_list('#funnels_top_funnels', top_funnels_items, 5);

        const top_collaborators_items = (period_data.top_collaborators ?? []).map((item, index) => `
            <div class="border-bottom py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pr-3">
                        <div>${index + 1}. <a href="${item.admin_user_url}">${escape_html(item.name || '<?= l('global.unknown') ?>')}</a></div>
                        <div class="text-muted"><?= l('admin_index.funnels_analytics.breakdown_meta') ?>: ${nr(item.funnels_count ?? 0)} / <?= l('admin_index.funnels_analytics.active_funnels_short') ?> ${nr(item.active_funnels ?? 0)}</div>
                    </div>
                    <div class="text-right">
                        <div><strong>${nr(item.leads ?? 0)}</strong></div>
                        <div class="text-muted"><?= l('admin_index.funnels_analytics.unique_clicks') ?>: ${nr(item.unique_clicks ?? 0)}</div>
                        <div class="text-muted">${nr(item.conversion_rate ?? 0)}%</div>
                    </div>
                </div>
            </div>
        `);
        render_compact_list('#funnels_top_collaborators', top_collaborators_items, 5);

        render_funnels_breakdown('#funnels_open_mode_breakdown', period_data.open_mode_breakdown ?? [], funnel_open_mode_labels);
        render_funnels_breakdown('#funnels_thank_you_breakdown', period_data.thank_you_type_breakdown ?? [], funnel_thank_you_type_labels);

        const top_conversion_items = (period_data.top_funnels_by_conversion ?? []).map((item, index) => `
            <div class="border-bottom py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pr-3">
                        <div>${index + 1}. <a href="${item.analytics_url}">${escape_html(item.name || '<?= l('global.unknown') ?>')}</a></div>
                        <div class="text-muted"><a href="${item.admin_user_url}">${escape_html(item.user_name || '<?= l('global.unknown') ?>')}</a></div>
                    </div>
                    <div class="text-right">
                        <div><strong>${nr(item.conversion_rate ?? 0)}%</strong></div>
                        <div class="text-muted"><?= l('admin_index.funnels_analytics.unique_clicks') ?>: ${nr(item.unique_clicks ?? 0)}</div>
                        <div class="text-muted"><?= l('admin_index.funnels_analytics.leads') ?>: ${nr(item.leads ?? 0)}</div>
                    </div>
                </div>
            </div>
        `);
        render_compact_list('#funnels_top_conversion', top_conversion_items, 5);

        const opportunities_items = (period_data.opportunities ?? []).map((item, index) => `
            <div class="border-bottom py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pr-3">
                        <div>${index + 1}. <a href="${item.analytics_url}">${escape_html(item.name || '<?= l('global.unknown') ?>')}</a></div>
                        <div class="text-muted"><a href="${item.admin_user_url}">${escape_html(item.user_name || '<?= l('global.unknown') ?>')}</a></div>
                    </div>
                    <div class="text-right">
                        <div><strong>${nr(item.unique_clicks ?? 0)}</strong></div>
                        <div class="text-muted"><?= l('admin_index.funnels_analytics.zero_leads') ?></div>
                    </div>
                </div>
            </div>
        `);
        render_compact_list('#funnels_opportunities', opportunities_items, 5);
        render_funnels_flow_summary(flow);

        const flow_opportunities_items = (period_data.flow_opportunities ?? []).map((item, index) => `
            <div class="border-bottom py-2">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="pr-3">
                        <div>${index + 1}. <a href="${item.analytics_url}">${escape_html(item.name || '<?= l('global.unknown') ?>')}</a></div>
                        <div class="text-muted"><a href="${item.admin_user_url}">${escape_html(item.user_name || '<?= l('global.unknown') ?>')}</a></div>
                        <div class="text-muted"><?= l('admin_index.funnels_analytics.flow_weakest_stage') ?>: ${escape_html({
                            entry_to_start: '<?= l('admin_index.funnels_analytics.flow_entry_to_start') ?>',
                            start_to_success: '<?= l('admin_index.funnels_analytics.flow_start_to_success') ?>',
                            success_to_cta: '<?= l('admin_index.funnels_analytics.flow_success_to_cta') ?>'
                        }[item.weakest_stage] || item.weakest_stage || '')}</div>
                    </div>
                    <div class="text-right">
                        <div><strong>${nr(item.weakest_rate ?? 0)}%</strong></div>
                        <div class="text-muted"><?= l('admin_index.funnels_analytics.flow_entry_points') ?>: ${nr(item.entry_points ?? 0)}</div>
                        <div class="text-muted"><?= l('admin_index.funnels_analytics.flow_submit_success') ?>: ${nr(item.submit_success ?? 0)}</div>
                    </div>
                </div>
            </div>
        `);
        render_compact_list('#funnels_flow_opportunities', flow_opportunities_items, 5);

        render_funnels_chart(period_data.chart ?? {}, selected_period_label);
    };

    const render_funnels_analytics = funnels_analytics => {
        if(!get_element('#funnels_total_funnels')) {
            return;
        }

        funnels_analytics_payload = funnels_analytics ?? {};
        render_funnels_period();
    };
    /* /Custom code: FC-2026-03-26 */

    const hide_biolink_search_results = () => {
        const results_container = document.querySelector('#biolink_collaborator_search_results');
        if(!results_container) {
            return;
        }

        results_container.classList.add('d-none');
        results_container.innerHTML = '';
    };

    const render_biolink_search_results = results => {
        const results_container = document.querySelector('#biolink_collaborator_search_results');
        if(!results_container) {
            return;
        }

        if(!Array.isArray(results) || !results.length) {
            results_container.classList.remove('d-none');
            results_container.innerHTML = `<div class="card border-0"><div class="card-body small text-muted py-3 px-3"><?= l('admin_index.biolink_analytics.search_no_results') ?></div></div>`;
            return;
        }

        results_container.classList.remove('d-none');
        results_container.innerHTML = `
            <div class="card border-0 overflow-hidden">
                <div class="list-group list-group-flush">
                    ${results.map(result => `
                        <button type="button" class="list-group-item list-group-item-action biolink-search-result py-3 px-3" data-user-id="${result.user_id}" data-user-name="${escape_html(result.name || '')}">
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="pr-3 text-left">
                                    <div class="font-weight-bold mb-1">${escape_html(result.name || '<?= l('global.unknown') ?>')}</div>
                                    <div class="small text-muted text-break">${escape_html(result.email || '')}</div>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="fas fa-angle-right"></i>
                                </div>
                            </div>
                        </button>
                    `).join('')}
                </div>
            </div>
        `;

        results_container.querySelectorAll('.biolink-search-result').forEach(button => {
            button.addEventListener('click', event => {
                const user_id = event.currentTarget.getAttribute('data-user-id');
                const user_name = event.currentTarget.getAttribute('data-user-name') || '';
                const search_input = document.querySelector('#biolink_collaborator_search');
                if(search_input) {
                    search_input.value = user_name;
                }

                hide_biolink_search_results();
                load_biolink_collaborator(user_id, user_name);
            });
        });
    };

    const search_biolink_collaborators = async query => {
        const request_id = ++biolink_search_request_id;
        const results_container = document.querySelector('#biolink_collaborator_search_results');
        if(results_container) {
            results_container.classList.remove('d-none');
            results_container.innerHTML = `<div class="list-group-item small text-muted"><?= l('global.loading') ?></div>`;
        }

        const response = await fetch(`${url}admin/index/search_biolink_collaborators_ajax?query=${encodeURIComponent(query)}`, {
            method: 'get',
        });

        let data = null;
        try {
            data = await response.json();
        } catch(error) {
            hide_biolink_search_results();
            return;
        }

        if(request_id !== biolink_search_request_id) {
            return;
        }

        if(!response.ok || data?.status !== 'success') {
            hide_biolink_search_results();
            return;
        }

        render_biolink_search_results(data.details?.results ?? []);
    };

    const load_biolink_collaborator = async (user_id, fallback_name = '') => {
        const collaborator_id = parseInt(user_id, 10);
        if(!collaborator_id) {
            return;
        }

        if(biolink_collaborator_cache[collaborator_id]) {
            biolink_selected_collaborator = biolink_collaborator_cache[collaborator_id];
            render_biolink_period();
            return;
        }

        biolink_selected_collaborator = {
            name: fallback_name || '<?= l('global.loading') ?>',
            periods: {},
        };
        render_biolink_selected_collaborator_state();

        const response = await fetch(`${url}admin/index/get_biolink_collaborator_stats_ajax?user_id=${collaborator_id}`, {
            method: 'get',
        });

        let data = null;
        try {
            data = await response.json();
        } catch(error) {
            biolink_selected_collaborator = null;
            render_biolink_period();
            return;
        }

        if(!response.ok || data?.status !== 'success') {
            biolink_selected_collaborator = null;
            render_biolink_period();
            return;
        }

        const collaborator = data.details?.collaborator ?? null;
        if(!collaborator) {
            biolink_selected_collaborator = null;
            render_biolink_period();
            return;
        }

        biolink_collaborator_cache[collaborator_id] = collaborator;
        biolink_selected_collaborator = collaborator;
        render_biolink_period();
    };
    /* /Custom code: FC-2026-03-04 */

    document.querySelectorAll('[data-biolink-period]').forEach(button => {
        button.addEventListener('click', event => {
            biolink_analytics_period = event.currentTarget.getAttribute('data-biolink-period');

            document.querySelectorAll('[data-biolink-period]').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');

            render_biolink_period();
        });
    });

    document.querySelectorAll('[data-funnels-period]').forEach(button => {
        button.addEventListener('click', event => {
            funnels_analytics_period = event.currentTarget.getAttribute('data-funnels-period');

            document.querySelectorAll('[data-funnels-period]').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');

            render_funnels_period();
        });
    });

    document.querySelectorAll('[data-funnels-filter]').forEach(button => {
        button.addEventListener('click', event => {
            funnels_analytics_filter = event.currentTarget.getAttribute('data-funnels-filter');

            document.querySelectorAll('[data-funnels-filter]').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');

            render_funnels_period();
        });
    });

    /* Custom code: FC-2026-03-18: sales subscriptions chart period buttons */
    document.querySelectorAll('[data-sales-subscriptions-period]').forEach(button => {
        button.addEventListener('click', event => {
            sales_subscriptions_chart_period = Number(event.currentTarget.getAttribute('data-sales-subscriptions-period') || 30);

            document.querySelectorAll('[data-sales-subscriptions-period]').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');

            update_sales_subscriptions_chart();
        });
    });
    /* /Custom code: FC-2026-03-18 */

    document.querySelector('#biolink_selection_reset')?.addEventListener('click', () => {
        biolink_selected_collaborator = null;

        const search_input = document.querySelector('#biolink_collaborator_search');
        if(search_input) {
            search_input.value = '';
        }

        hide_biolink_search_results();
        render_biolink_period();
    });

    document.querySelector('#biolink_collaborator_search')?.addEventListener('input', event => {
        const query = event.currentTarget.value.trim();

        clearTimeout(biolink_search_timeout);
        if(query.length < 2) {
            hide_biolink_search_results();
            return;
        }

        biolink_search_timeout = setTimeout(() => {
            search_biolink_collaborators(query);
        }, 250);
    });

    document.addEventListener('click', event => {
        const search_wrapper = document.querySelector('#biolink_collaborator_search_wrapper');
        if(search_wrapper && !search_wrapper.contains(event.target)) {
            hide_biolink_search_results();
        }
    });

    init_compact_table_rows('#fcc_pending_table_body .fcc-pending-row', '#fcc_pending_table_toggle', 5);
    init_compact_table_rows('#sales_link_missing_table_body .sales-link-missing-row', '#sales_link_missing_table_toggle', 5);
    init_compact_table_rows('#trial_monitoring_table_body .trial-monitoring-row', '#trial_monitoring_table_toggle', 5);
    init_compact_items('#upcoming_charges_list .upcoming-charge-item', '#upcoming_charges_toggle', 4);
    /* /Custom code: FC-2026-03-04 */

    (async function fetch_statistics() {
        /* Send request to server */
        let response = await fetch(`${url}admin/index/get_stats_ajax`, {
            method: 'get',
        });

        let data = null;
        try {
            data = await response.json();
        } catch (error) {
            /* :)  */
        }

        if(!response.ok) {
            /* :)  */
        }

        if(data.status == 'error') {
            /* :)  */
        } else if(data.status == 'success') {
            set_html_if_present('#biolink_links', data.details.biolink_links ? nr(data.details.biolink_links) : 0);
            set_html_if_present('#biolink_links_current_month', data.details.biolink_links_current_month ? nr(data.details.biolink_links_current_month) : 0);

            set_html_if_present('#shortened_links', data.details.shortened_links ? nr(data.details.shortened_links) : 0);
            set_html_if_present('#shortened_links_current_month', data.details.shortened_links_current_month ? nr(data.details.shortened_links_current_month) : 0);

            set_html_if_present('#track_links', data.details.track_links ? nr(data.details.track_links) : 0);
            set_html_if_present('#track_links_current_month', data.details.track_links_current_month ? nr(data.details.track_links_current_month) : 0);

            set_html_if_present('#qr_codes', data.details.qr_codes ? nr(data.details.qr_codes) : 0);
            set_html_if_present('#qr_codes_current_month', data.details.qr_codes_current_month ? nr(data.details.qr_codes_current_month) : 0);

            set_html_if_present('#domains', data.details.domains ? nr(data.details.domains) : 0);
            set_html_if_present('#domains_current_month', data.details.domains_current_month ? nr(data.details.domains_current_month) : 0);

            set_html_if_present('#payments_total_amount', data.details.payments_total_amount ? nr(data.details.payments_total_amount) : 0);
            set_html_if_present('#users_current_month', data.details.users_current_month ? nr(data.details.users_current_month) : 0);

            set_html_if_present('#users', data.details.users ? nr(data.details.users) : 0);
            set_html_if_present('#payments_current_month', data.details.payments_current_month ? nr(data.details.payments_current_month) : 0);

            set_html_if_present('#payments', data.details.payments ? nr(data.details.payments) : 0);
            set_html_if_present('#payments_amount_current_month', data.details.payments_amount_current_month ? nr(data.details.payments_amount_current_month) : 0);

            let active_users = data.details.active_users ? nr(data.details.active_users) : 0;
            const active_users_element = get_element('#active_users');
            if(active_users_element) {
                active_users_element.innerHTML = active_users_element.getAttribute('data-translation').replace('%s', active_users);
            }

            /* Custom code: FC-2026-03-04: bind phase 1 analytics payload */
            if(data.details.admin_analytics) {
                render_realtime(data.details.admin_analytics.realtime ?? {});
                render_charts(data.details.admin_analytics.charts ?? {});
                render_sales_subscriptions(data.details.admin_analytics.sales_subscriptions ?? {});
                render_action_center(data.details.admin_analytics.action_center ?? {});
                render_billing_risk(data.details.admin_analytics.billing_risk ?? {});
                render_biolink_analytics(data.details.admin_analytics.biolink_analytics ?? {});
                render_funnels_analytics(data.details.admin_analytics.funnels_analytics ?? {});
            }
            /* /Custom code: FC-2026-03-04 */

        }
    })();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
