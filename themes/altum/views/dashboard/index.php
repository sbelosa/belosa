<?php defined('ALTUMCODE') || die() ?>

<?php ob_start() ?>
<style>
    .dashboard-funnel-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 1.35rem;
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, 0.1), transparent 28%),
            linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015));
    }

    .dashboard-funnel-card::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(135deg, rgba(73, 227, 207, 0.05), transparent 48%);
    }

    .dashboard-funnel-shell {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(18rem, 1fr);
        gap: 1rem;
        align-items: stretch;
    }

    .dashboard-funnel-side,
    .dashboard-funnel-metrics {
        min-width: 0;
    }

    .dashboard-funnel-kpis {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .8rem;
    }

    .dashboard-funnel-kpi {
        border-radius: 1rem;
        padding: .9rem 1rem;
        border: 1px solid rgba(255,255,255,.06);
        background: rgba(255,255,255,.03);
    }

    .dashboard-funnel-kpi-label {
        color: var(--gray-500);
        font-size: .74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        margin-bottom: .45rem;
    }

    .dashboard-funnel-kpi-value {
        color: #fff;
        font-size: 1.3rem;
        font-weight: 700;
        line-height: 1.15;
    }

    .dashboard-funnel-insights {
        display: flex;
        flex-direction: column;
        gap: .7rem;
        margin-top: .9rem;
    }

    .dashboard-funnel-insight {
        display: flex;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem .95rem;
        border-radius: 1rem;
        border: 1px solid rgba(255,255,255,.06);
        background: rgba(255,255,255,.03);
    }

    .dashboard-funnel-insight-value {
        color: #fff;
        font-weight: 700;
    }

    .dashboard-funnel-status-copy {
        max-width: 38rem;
    }

    .dashboard-funnel-status-copy .btn {
        border-radius: .9rem;
    }

    .dashboard-funnel-badge {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        border-radius: 999px;
        padding: .35rem .7rem;
        font-size: .76rem;
        font-weight: 700;
        margin-bottom: .8rem;
    }

    .dashboard-funnel-badge.is-good {
        color: #34d399;
        background: rgba(52, 211, 153, .12);
    }

    .dashboard-funnel-badge.is-warning {
        color: #fbbf24;
        background: rgba(251, 191, 36, .12);
    }

    .dashboard-funnel-badge.is-danger,
    .dashboard-funnel-badge.is-setup {
        color: #fb7185;
        background: rgba(251, 113, 133, .12);
    }

    .dashboard-modern-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 1.2rem;
        background:
            radial-gradient(circle at top right, rgba(73, 227, 207, 0.07), transparent 24%),
            linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.015));
        box-shadow: 0 18px 40px rgba(15, 23, 42, .12);
    }

    .dashboard-modern-card::before {
        content: '';
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(135deg, rgba(73, 227, 207, 0.04), transparent 52%);
    }

    .dashboard-modern-card > .card-body {
        position: relative;
        z-index: 1;
    }

    .dashboard-section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.1rem;
        padding: 0 0 .25rem;
    }

    .dashboard-section-heading .h5 {
        margin-bottom: 0;
    }

    .dashboard-onboarding-card .small + .small {
        margin-top: .35rem;
    }

    .dashboard-kpi-card .badge {
        border-radius: 999px;
        padding: .35rem .55rem;
    }

    .dashboard-chart-card {
        margin-top: 1rem;
    }

    .dashboard-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .dashboard-grid-tight {
        margin: 0 -.5rem;
        row-gap: 0;
    }

    .dashboard-grid-tight > [class*="col-"] {
        padding: .5rem;
    }

    .dashboard-modern-card .card-body {
        padding: 1rem 1.1rem;
    }

    .dashboard-funnel-card .card-body {
        padding: 1.1rem 1.15rem;
    }

    .dashboard-list-grid + .dashboard-list-grid {
        margin-top: 0;
    }

    @media (max-width: 991.98px) {
        .dashboard-funnel-shell,
        .dashboard-funnel-kpis {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .dashboard-modern-card .card-body,
        .dashboard-funnel-card .card-body {
            padding: .95rem 1rem;
        }

        .dashboard-stack {
            gap: .85rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php /* Custom code: FC-2026-03-05: dashboard demo mode banner */ ?>
    <?php if(isset($_GET['demo']) && $_GET['demo'] == '1'): ?>
        <div class="alert alert-info">
            <?= l('dashboard.forever_analytics.demo_banner') ?>
        </div>
    <?php endif ?>
    <?php /* /Custom code: FC-2026-03-05 */ ?>

    <?php if(!empty($data->needs_fcc_education)): ?>
        <!-- Custom code: FC-2026-02-24: FCC core education banner -->
        <div class="alert alert-warning d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between">
            <div class="mb-3 mb-md-0">
                <div class="font-weight-bold mb-1">
                    <?= l('fcc_education.banner_title') ?>
                </div>
                <div><?= l('fcc_education.banner_text') ?></div>
            </div>
            <a class="btn btn-dark" href="<?= url('fcc-education') ?>">
                <?= l('fcc_education.banner_button') ?>
            </a>
        </div>
        <!-- /Custom code: FC-2026-02-24 -->
    <?php endif ?>

    <div class="mb-5">
        <!-- Custom code: FC-2026-03-05: productivity-first forever dashboard section -->
        <div class="mt-4 dashboard-stack">
            <div class="dashboard-section-heading">
                <h2 class="h5 mb-2 mb-md-0"><i class="fas fa-fw fa-sm fa-chart-line text-primary mr-1"></i> <?= l('dashboard.forever_analytics.header') ?></h2>
                <small class="text-muted"><?= l('dashboard.forever_analytics.subheader') ?></small>
            </div>

            <div class="card dashboard-funnel-card border-0">
                <div class="card-body">
                    <div class="dashboard-funnel-shell">
                        <div class="dashboard-funnel-side">
                            <div id="dashboard_funnel_status_badge"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                            <div class="dashboard-funnel-status-copy">
                                <h3 class="h5 mb-2"><?= l('dashboard.funnel.header') ?></h3>
                                <div class="text-muted mb-2"><?= l('dashboard.funnel.subheader') ?></div>
                                <div class="font-weight-bold text-body mb-2" id="dashboard_funnel_status_title"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                <div class="text-muted mb-3" id="dashboard_funnel_status_description"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                <div class="d-flex flex-wrap align-items-center" style="gap: .75rem;">
                                    <a href="<?= url('funnels-analytics') ?>" class="btn btn-sm btn-outline-primary"><?= l('dashboard.funnel.analytics_cta') ?></a>
                                    <a href="<?= url('links?type=biolink') ?>" class="btn btn-sm btn-primary" id="dashboard_funnel_primary_cta"><?= l('dashboard.funnel.status.setup_cta') ?></a>
                                </div>
                            </div>
                        </div>

                        <div class="dashboard-funnel-metrics">
                            <div class="dashboard-funnel-kpis">
                                <div class="dashboard-funnel-kpi">
                                    <div class="dashboard-funnel-kpi-label"><?= l('dashboard.funnel.total_funnels') ?></div>
                                    <div class="dashboard-funnel-kpi-value" id="dashboard_funnel_total"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                </div>

                                <div class="dashboard-funnel-kpi">
                                    <div class="dashboard-funnel-kpi-label"><?= l('dashboard.funnel.leads_30d') ?></div>
                                    <div class="dashboard-funnel-kpi-value" id="dashboard_funnel_leads_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                </div>

                                <div class="dashboard-funnel-kpi">
                                    <div class="dashboard-funnel-kpi-label"><?= l('dashboard.funnel.unique_clicks_30d') ?></div>
                                    <div class="dashboard-funnel-kpi-value" id="dashboard_funnel_unique_clicks_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                </div>

                                <div class="dashboard-funnel-kpi">
                                    <div class="dashboard-funnel-kpi-label"><?= l('dashboard.funnel.conversion_rate_30d') ?></div>
                                    <div class="dashboard-funnel-kpi-value" id="dashboard_funnel_conversion_rate_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                </div>
                            </div>

                            <div class="dashboard-funnel-insights">
                                <div class="dashboard-funnel-insight">
                                    <div>
                                        <div class="dashboard-funnel-kpi-label mb-1"><?= l('dashboard.funnel.best_open_mode') ?></div>
                                        <div class="text-muted small"><?= l('dashboard.funnel.best_open_mode_help') ?></div>
                                    </div>
                                    <div class="dashboard-funnel-insight-value" id="dashboard_funnel_best_open_mode"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                </div>

                                <div class="dashboard-funnel-insight">
                                    <div>
                                        <div class="dashboard-funnel-kpi-label mb-1"><?= l('dashboard.funnel.best_thank_you_type') ?></div>
                                        <div class="text-muted small"><?= l('dashboard.funnel.best_thank_you_type_help') ?></div>
                                    </div>
                                    <div class="dashboard-funnel-insight-value" id="dashboard_funnel_best_thank_you_type"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                </div>

                                <div class="dashboard-funnel-insight">
                                    <div>
                                        <div class="dashboard-funnel-kpi-label mb-1"><?= l('dashboard.funnel.last_lead') ?></div>
                                        <div class="text-muted small"><?= l('dashboard.funnel.last_lead_help') ?></div>
                                    </div>
                                    <div class="dashboard-funnel-insight-value" id="dashboard_funnel_last_lead"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card dashboard-modern-card dashboard-onboarding-card border-0">
                <div class="card-body">
                    <div class="font-weight-bold mb-2"><?= l('dashboard.forever_analytics.onboarding_header') ?></div>
                    <div class="small text-muted"><?= l('dashboard.forever_analytics.onboarding_step_1') ?></div>
                    <div class="small text-muted"><?= l('dashboard.forever_analytics.onboarding_step_2') ?></div>
                    <div class="small text-muted"><?= l('dashboard.forever_analytics.onboarding_step_3') ?></div>
                </div>
            </div>

            <div class="row dashboard-grid-tight">
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="card h-100 dashboard-modern-card dashboard-kpi-card">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.kpi_biolink_visits_title') ?></small>
                            <div class="h5 mb-0" id="dashboard_biolink_visits_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                            <small class="text-muted d-block mt-1" id="dashboard_biolink_visits_delta"><span class="spinner-border spinner-border-sm" role="status"></span></small>
                            <small class="text-muted d-block mt-2"><?= l('dashboard.forever_analytics.kpi_biolink_visits_help') ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="card h-100 dashboard-modern-card dashboard-kpi-card">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.kpi_shop_clicks_title') ?></small>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="h5 mb-0" id="dashboard_forever_shop_clicks_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                <span id="dashboard_status_shop_clicks"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                            </div>
                            <small class="text-muted d-block mt-1" id="dashboard_shop_clicks_delta"><span class="spinner-border spinner-border-sm" role="status"></span></small>
                            <small class="text-muted d-block mt-2"><?= l('dashboard.forever_analytics.kpi_shop_clicks_help') ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="card h-100 dashboard-modern-card dashboard-kpi-card">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.kpi_shop_ctr_title') ?></small>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="h5 mb-0" id="dashboard_shop_ctr_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                <span id="dashboard_status_shop_ctr"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                            </div>
                            <small class="text-muted d-block mt-1" id="dashboard_shop_ctr_delta"><span class="spinner-border spinner-border-sm" role="status"></span></small>
                            <small class="text-muted d-block mt-2"><?= l('dashboard.forever_analytics.kpi_shop_ctr_help') ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl-3 p-2">
                    <div class="card h-100 dashboard-modern-card dashboard-kpi-card">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.kpi_shop_trend_title') ?></small>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="h5 mb-0" id="dashboard_shop_trend_value"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                <span id="dashboard_status_shop_trend"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                            </div>
                            <small class="text-muted d-block mt-2"><?= l('dashboard.forever_analytics.kpi_shop_trend_help') ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row dashboard-grid-tight">
                <div class="col-12 col-md-6 p-2">
                    <div class="card h-100 dashboard-modern-card dashboard-kpi-card">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.kpi_registration_clicks_title') ?></small>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="h5 mb-0" id="dashboard_forever_registration_clicks_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                <span id="dashboard_status_registration_clicks"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                            </div>
                            <small class="text-muted d-block mt-1" id="dashboard_registration_clicks_delta"><span class="spinner-border spinner-border-sm" role="status"></span></small>
                            <small class="text-muted d-block mt-2"><?= l('dashboard.forever_analytics.kpi_registration_clicks_help') ?></small>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 p-2">
                    <div class="card h-100 dashboard-modern-card dashboard-kpi-card">
                        <div class="card-body">
                            <small class="text-muted d-block mb-1"><?= l('dashboard.forever_analytics.kpi_registration_ctr_title') ?></small>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="h5 mb-0" id="dashboard_registration_ctr_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                                <span id="dashboard_status_registration_ctr"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                            </div>
                            <small class="text-muted d-block mt-1" id="dashboard_registration_ctr_delta"><span class="spinner-border spinner-border-sm" role="status"></span></small>
                            <small class="text-muted d-block mt-2"><?= l('dashboard.forever_analytics.kpi_registration_ctr_help') ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card dashboard-modern-card">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('dashboard.forever_analytics.action_header') ?></h3>
                    <small class="text-muted d-block mb-3"><?= l('dashboard.forever_analytics.action_subheader') ?></small>
                    <small class="text-muted d-block mb-3" id="dashboard_benchmark_note"><span class="spinner-border spinner-border-sm" role="status"></span></small>
                    <div id="dashboard_recommendations" class="small text-muted">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
            </div>

            <div class="row dashboard-grid-tight dashboard-list-grid">
                <div class="col-12 col-xl-6 p-2">
                    <div class="card h-100 dashboard-modern-card">
                        <div class="card-body">
                            <h3 class="h6 mb-3"><?= l('dashboard.forever_analytics.top_countries_30d') ?></h3>
                            <div id="dashboard_top_countries_30d" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                            <div id="dashboard_top_countries_30d_toggle" class="pt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6 p-2">
                    <div class="card h-100 dashboard-modern-card">
                        <div class="card-body">
                            <h3 class="h6 mb-3"><?= l('dashboard.forever_analytics.top_forever_pages_30d') ?></h3>
                            <div id="dashboard_top_forever_pages_30d" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                            <div id="dashboard_top_forever_pages_30d_toggle" class="pt-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row dashboard-grid-tight dashboard-list-grid">
                <div class="col-12 col-xl-6 p-2">
                    <div class="card h-100 dashboard-modern-card">
                        <div class="card-body">
                            <h3 class="h6 mb-3"><?= l('dashboard.forever_analytics.top_shop_sources_30d') ?></h3>
                            <div id="dashboard_top_shop_sources_30d" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                            <div id="dashboard_top_shop_sources_30d_toggle" class="pt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-6 p-2">
                    <div class="card h-100 dashboard-modern-card">
                        <div class="card-body">
                            <h3 class="h6 mb-3"><?= l('dashboard.forever_analytics.top_registration_sources_30d') ?></h3>
                            <div id="dashboard_top_registration_sources_30d" class="small text-muted">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </div>
                            <div id="dashboard_top_registration_sources_30d_toggle" class="pt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- /Custom code: FC-2026-03-05 -->

        <div class="card dashboard-modern-card dashboard-chart-card">
            <div class="card-body">
                <div class="chart-container d-none" id="pageviews_chart_container">
                    <canvas id="pageviews_chart"></canvas>
                </div>

                <div id="pageviews_chart_no_data" class="d-none">
                    <?= include_view(THEME_PATH . 'views/partials/no_chart_data.php', ['has_wrapper' => false]); ?>
                </div>

                <div id="pageviews_chart_loading" class="chart-container d-flex align-items-center justify-content-center">
                    <span class="spinner-border spinner-border-lg" role="status"></span>
                </div>

                <?php if(settings()->main->chart_cache): ?>
                <small class="text-muted d-none" id="pageviews_chart_help">
                    <span data-toggle="tooltip" title="<?= sprintf(l('global.chart_help'), settings()->main->chart_cache ?? 12, settings()->main->chart_days ?? 30) ?>"><i class="fas fa-fw fa-sm fa-info-circle mr-1"></i></span>
                    <span class="d-lg-none"><?= sprintf(l('global.chart_help'), settings()->main->chart_cache ?? 12, settings()->main->chart_days ?? 30) ?></span>
                </small>
                <?php endif ?>
            </div>
        </div>

        <?php require THEME_PATH . 'views/partials/js_chart_defaults.php' ?>
    </div>
</div>

<?php ob_start() ?>
    <script>
    'use strict';

        const funnel_open_mode_labels = {
            popup: <?= json_encode(l('biolink_lead_funnel.open_mode_popup')) ?>,
            page: <?= json_encode(l('biolink_lead_funnel.open_mode_page')) ?>,
        };

        const funnel_thank_you_labels = {
            message: <?= json_encode(l('biolink_lead_funnel.thank_you_type_message')) ?>,
            external_url: <?= json_encode(l('biolink_lead_funnel.thank_you_type_external_url')) ?>,
            biolink_redirect: <?= json_encode(l('biolink_lead_funnel.thank_you_type_biolink_redirect')) ?>,
            file_download: <?= json_encode(l('biolink_lead_funnel.thank_you_type_file_download')) ?>,
        };

        const dashboard_compact_state = {};
        const render_dashboard_compact_list = (container_selector, toggle_selector, items_html, visible_limit = 5) => {
            const container = document.querySelector(container_selector);
            const toggle_container = document.querySelector(toggle_selector);

            if(!container || !toggle_container) {
                return;
            }

            if(!Array.isArray(items_html) || !items_html.length) {
                container.innerHTML = `<span class="text-muted"><?= l('global.no_data') ?></span>`;
                toggle_container.innerHTML = '';
                return;
            }

            if(dashboard_compact_state[container_selector] === undefined) {
                dashboard_compact_state[container_selector] = false;
            }

            const is_expanded = dashboard_compact_state[container_selector];
            const should_toggle = items_html.length > visible_limit;
            const visible_items = should_toggle && !is_expanded ? items_html.slice(0, visible_limit) : items_html;

            container.innerHTML = visible_items.join('');

            if(!should_toggle) {
                toggle_container.innerHTML = '';
                return;
            }

            toggle_container.innerHTML = `
                <button type="button" class="btn btn-sm btn-outline-secondary" data-dashboard-compact-toggle="${container_selector}">
                    ${is_expanded ? '<?= l('dashboard.compact_list.show_less') ?>' : '<?= l('global.view_more') ?>'}
                </button>
            `;

            const toggle_button = toggle_container.querySelector('[data-dashboard-compact-toggle]');
            if(toggle_button) {
                toggle_button.addEventListener('click', () => {
                    dashboard_compact_state[container_selector] = !dashboard_compact_state[container_selector];
                    render_dashboard_compact_list(container_selector, toggle_selector, items_html, visible_limit);
                });
            }
        };
    
        (async function fetch_statistics() {
            /* Custom code: FC-2026-03-05: pass demo query flag to ajax endpoint */
            const dashboard_query_params = new URLSearchParams(window.location.search);
            const dashboard_stats_url = dashboard_query_params.get('demo') === '1' ? `${url}dashboard/get_stats_ajax?demo=1` : `${url}dashboard/get_stats_ajax`;
            /* /Custom code: FC-2026-03-05 */

            /* Send request to server */
            let response = await fetch(dashboard_stats_url, {
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

                /* update link_links_total */
                const link_links_total_element = document.querySelector('#link_links_total');
                if (link_links_total_element) {
                    link_links_total_element.innerHTML = data.details.link_links_total ? nr(data.details.link_links_total) : 0;
                }

                /* update file_links_total */
                const file_links_total_element = document.querySelector('#file_links_total');
                if (file_links_total_element) {
                    file_links_total_element.innerHTML = data.details.file_links_total ? nr(data.details.file_links_total) : 0;
                }

                /* update vcard_links_total */
                const vcard_links_total_element = document.querySelector('#vcard_links_total');
                if (vcard_links_total_element) {
                    vcard_links_total_element.innerHTML = data.details.vcard_links_total ? nr(data.details.vcard_links_total) : 0;
                }

                /* update biolink_links_total */
                const biolink_links_total_element = document.querySelector('#biolink_links_total');
                if (biolink_links_total_element) {
                    biolink_links_total_element.innerHTML = data.details.biolink_links_total ? nr(data.details.biolink_links_total) : 0;
                }

                /* update event_links_total */
                const event_links_total_element = document.querySelector('#event_links_total');
                if (event_links_total_element) {
                    event_links_total_element.innerHTML = data.details.event_links_total ? nr(data.details.event_links_total) : 0;
                }

                /* update static_links_total */
                const static_links_total_element = document.querySelector('#static_links_total');
                if (static_links_total_element) {
                    static_links_total_element.innerHTML = data.details.static_links_total ? nr(data.details.static_links_total) : 0;
                }

                /* Custom code: FC-2026-03-05: render productivity-first forever analytics */
                const dashboard_forever_analytics = data.details.dashboard_forever_analytics ?? {};
                const dashboard_funnel_analytics = data.details.dashboard_funnel_analytics ?? {};

                const set_metric = (selector, value) => {
                    const element = document.querySelector(selector);
                    if(element) {
                        element.innerHTML = nr(value ?? 0);
                    }
                };

                const set_delta = (selector, value, suffix = '%') => {
                    const element = document.querySelector(selector);
                    if(!element) {
                        return;
                    }

                    const numeric_value = Number(value ?? 0);
                    const sign = numeric_value > 0 ? '+' : '';
                    element.innerText = `${sign}${nr(numeric_value)}${suffix}`;
                };

                const render_status_badge = (selector, status) => {
                    const element = document.querySelector(selector);
                    if(!element) {
                        return;
                    }

                    const status_map = {
                        good: {
                            text: <?= json_encode(l('dashboard.forever_analytics.status.good')) ?>,
                            className: 'badge-success'
                        },
                        warning: {
                            text: <?= json_encode(l('dashboard.forever_analytics.status.warning')) ?>,
                            className: 'badge-warning'
                        },
                        danger: {
                            text: <?= json_encode(l('dashboard.forever_analytics.status.danger')) ?>,
                            className: 'badge-danger'
                        }
                    };

                    const selected_status = status_map[status] ?? status_map.warning;
                    element.innerHTML = `<span class="badge ${selected_status.className}">${selected_status.text}</span>`;
                };

                set_metric('#dashboard_biolink_visits_30d', dashboard_forever_analytics.biolink_visits_30d);
                set_metric('#dashboard_forever_shop_clicks_30d', dashboard_forever_analytics.forever_shop_clicks_30d);
                set_metric('#dashboard_forever_registration_clicks_30d', dashboard_forever_analytics.forever_registration_clicks_30d);

                const dashboard_shop_ctr_30d = document.querySelector('#dashboard_shop_ctr_30d');
                if(dashboard_shop_ctr_30d) {
                    dashboard_shop_ctr_30d.innerText = `${nr(Number(dashboard_forever_analytics.shop_ctr_30d ?? 0))}%`;
                }

                const dashboard_registration_ctr_30d = document.querySelector('#dashboard_registration_ctr_30d');
                if(dashboard_registration_ctr_30d) {
                    dashboard_registration_ctr_30d.innerText = `${nr(Number(dashboard_forever_analytics.registration_ctr_30d ?? 0))}%`;
                }

                set_delta('#dashboard_biolink_visits_delta', dashboard_forever_analytics.biolink_visits_delta_percent, '%');
                set_delta('#dashboard_shop_clicks_delta', dashboard_forever_analytics.shop_clicks_delta_percent, '%');
                set_delta('#dashboard_shop_ctr_delta', dashboard_forever_analytics.shop_ctr_delta_points, ' p.b.');
                set_delta('#dashboard_shop_trend_value', dashboard_forever_analytics.shop_clicks_delta_percent, '%');
                set_delta('#dashboard_registration_clicks_delta', dashboard_forever_analytics.registration_clicks_delta_percent, '%');
                set_delta('#dashboard_registration_ctr_delta', dashboard_forever_analytics.registration_ctr_delta_points, ' p.b.');

                render_status_badge('#dashboard_status_shop_clicks', dashboard_forever_analytics.status_shop_clicks);
                render_status_badge('#dashboard_status_shop_ctr', dashboard_forever_analytics.status_shop_ctr);
                render_status_badge('#dashboard_status_shop_trend', dashboard_forever_analytics.status_shop_trend);
                render_status_badge('#dashboard_status_registration_clicks', dashboard_forever_analytics.status_registration_clicks);
                render_status_badge('#dashboard_status_registration_ctr', dashboard_forever_analytics.status_registration_ctr);

                const dashboard_benchmark_note = document.querySelector('#dashboard_benchmark_note');
                if(dashboard_benchmark_note) {
                    dashboard_benchmark_note.innerText = dashboard_forever_analytics.benchmark_note ?? '';
                }

                const dashboard_recommendations = document.querySelector('#dashboard_recommendations');
                if(dashboard_recommendations) {
                    const recommendations_html = (dashboard_forever_analytics.recommendations ?? []).map(recommendation => {
                        const recommendation_status = recommendation.status ?? 'warning';
                        const recommendation_badge_class = recommendation_status === 'good' ? 'badge-success' : (recommendation_status === 'danger' ? 'badge-danger' : 'badge-warning');
                        const recommendation_label = recommendation_status === 'good' ? <?= json_encode(l('dashboard.forever_analytics.status.good')) ?> : (recommendation_status === 'danger' ? <?= json_encode(l('dashboard.forever_analytics.status.danger')) ?> : <?= json_encode(l('dashboard.forever_analytics.status.warning')) ?>);

                        return `
                            <div class="border rounded p-3 mb-2">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <strong>${recommendation.title ?? ''}</strong>
                                    <span class="badge ${recommendation_badge_class}">${recommendation_label}</span>
                                </div>
                                <div class="text-muted mb-2">${recommendation.description ?? ''}</div>
                                <a href="${recommendation.cta_url ?? '#'}" class="btn btn-sm btn-outline-primary">${recommendation.cta_label ?? '<?= l('global.view') ?>'}</a>
                            </div>
                        `;
                    });

                    dashboard_recommendations.innerHTML = recommendations_html.length ? recommendations_html.join('') : `<span class="text-muted"><?= l('global.no_data') ?></span>`;
                }

                const top_countries_html = (dashboard_forever_analytics.top_countries_30d ?? []).map(country => {
                    return `
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span>${country.country_code ? `${country.country_code}` : '-'}</span>
                            <strong>${nr(country.total ?? 0)}</strong>
                        </div>
                    `;
                });

                render_dashboard_compact_list('#dashboard_top_countries_30d', '#dashboard_top_countries_30d_toggle', top_countries_html, 5);

                const top_forever_pages_html = (dashboard_forever_analytics.top_forever_pages_30d ?? []).map(item => {
                    const page_url = item.url ? `${url}${item.url}` : null;
                    return `
                        <div class="border-bottom py-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-truncate mr-2" style="max-width: 85%;">${page_url ? `<a href="${page_url}" target="_blank" rel="noopener noreferrer">${item.url}</a>` : '<?= l('global.unknown') ?>'}</span>
                                <strong>${nr(item.total ?? 0)}</strong>
                            </div>
                        </div>
                    `;
                });

                render_dashboard_compact_list('#dashboard_top_forever_pages_30d', '#dashboard_top_forever_pages_30d_toggle', top_forever_pages_html, 5);

                const top_shop_sources_html = (dashboard_forever_analytics.top_shop_sources_30d ?? []).map(item => {
                    return `
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-truncate mr-2" style="max-width: 85%;">${item.source ? `${item.source}` : '<?= l('dashboard.forever_analytics.source_direct') ?>'}</span>
                            <strong>${nr(item.total ?? 0)}</strong>
                        </div>
                    `;
                });

                render_dashboard_compact_list('#dashboard_top_shop_sources_30d', '#dashboard_top_shop_sources_30d_toggle', top_shop_sources_html, 5);

                const top_registration_sources_html = (dashboard_forever_analytics.top_registration_sources_30d ?? []).map(item => {
                    return `
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-truncate mr-2" style="max-width: 85%;">${item.source ? `${item.source}` : '<?= l('dashboard.forever_analytics.source_direct') ?>'}</span>
                            <strong>${nr(item.total ?? 0)}</strong>
                        </div>
                    `;
                });

                render_dashboard_compact_list('#dashboard_top_registration_sources_30d', '#dashboard_top_registration_sources_30d_toggle', top_registration_sources_html, 5);
                /* /Custom code: FC-2026-03-05 */

                const dashboard_funnel_status_badge = document.querySelector('#dashboard_funnel_status_badge');
                if(dashboard_funnel_status_badge) {
                    const funnel_status_map = {
                        good: {
                            label: <?= json_encode(l('dashboard.forever_analytics.status.good')) ?>,
                            className: 'is-good',
                            icon: 'fa-circle-check'
                        },
                        warning: {
                            label: <?= json_encode(l('dashboard.forever_analytics.status.warning')) ?>,
                            className: 'is-warning',
                            icon: 'fa-triangle-exclamation'
                        },
                        danger: {
                            label: <?= json_encode(l('dashboard.forever_analytics.status.danger')) ?>,
                            className: 'is-danger',
                            icon: 'fa-bolt'
                        },
                        setup: {
                            label: <?= json_encode(l('dashboard.funnel.status.setup_badge')) ?>,
                            className: 'is-setup',
                            icon: 'fa-wand-magic-sparkles'
                        }
                    };

                    const selected_funnel_status = funnel_status_map[dashboard_funnel_analytics.status] ?? funnel_status_map.setup;
                    dashboard_funnel_status_badge.innerHTML = `<span class="dashboard-funnel-badge ${selected_funnel_status.className}"><i class="fas fa-fw ${selected_funnel_status.icon}"></i>${selected_funnel_status.label}</span>`;
                }

                const set_funnel_text = (selector, value) => {
                    const element = document.querySelector(selector);
                    if(element) {
                        element.innerText = value ?? '—';
                    }
                };

                set_funnel_text('#dashboard_funnel_status_title', dashboard_funnel_analytics.status_title);
                set_funnel_text('#dashboard_funnel_status_description', dashboard_funnel_analytics.status_description);
                set_funnel_text('#dashboard_funnel_total', `${nr(dashboard_funnel_analytics.active_funnels_30d ?? 0)} / ${nr(dashboard_funnel_analytics.total_funnels ?? 0)}`);
                set_funnel_text('#dashboard_funnel_leads_30d', nr(dashboard_funnel_analytics.leads_30d ?? 0));
                set_funnel_text('#dashboard_funnel_unique_clicks_30d', nr(dashboard_funnel_analytics.unique_clicks_30d ?? 0));
                set_funnel_text('#dashboard_funnel_conversion_rate_30d', `${nr(Number(dashboard_funnel_analytics.conversion_rate_30d ?? 0))}%`);
                set_funnel_text('#dashboard_funnel_best_open_mode', dashboard_funnel_analytics.best_open_mode?.type ? (funnel_open_mode_labels[dashboard_funnel_analytics.best_open_mode.type] ?? dashboard_funnel_analytics.best_open_mode.type) : '—');
                set_funnel_text('#dashboard_funnel_best_thank_you_type', dashboard_funnel_analytics.best_thank_you_type?.type ? (funnel_thank_you_labels[dashboard_funnel_analytics.best_thank_you_type.type] ?? dashboard_funnel_analytics.best_thank_you_type.type) : '—');
                set_funnel_text('#dashboard_funnel_last_lead', dashboard_funnel_analytics.last_lead_display ?? '—');

                const dashboard_funnel_primary_cta = document.querySelector('#dashboard_funnel_primary_cta');
                if(dashboard_funnel_primary_cta) {
                    dashboard_funnel_primary_cta.innerText = dashboard_funnel_analytics.cta_label ?? <?= json_encode(l('dashboard.funnel.status.setup_cta')) ?>;
                    dashboard_funnel_primary_cta.setAttribute('href', dashboard_funnel_analytics.cta_url ?? <?= json_encode(url('links?type=biolink')) ?>);
                }

                /* Remove loading */
                document.querySelector('#pageviews_chart_loading').classList.add('d-none');
                document.querySelector('#pageviews_chart_loading').classList.remove('d-flex');

                /* Chart */
                if(data.details.links_chart.is_empty) {
                    document.querySelector('#pageviews_chart_no_data').classList.remove('d-none');
                } else {
                    /* Display chart data */
                    document.querySelector('#pageviews_chart_container').classList.remove('d-none');
                    document.querySelector('#pageviews_chart_help') && document.querySelector('#pageviews_chart_help').classList.remove('d-none');

                    let css = window.getComputedStyle(document.body);
                    let pageviews_color = css.getPropertyValue('--primary');
                    let visitors_color = css.getPropertyValue('--gray-300');
                    let pageviews_color_gradient = null;
                    let visitors_color_gradient = null;

                    /* Chart */
                    let pageviews_chart = document.getElementById('pageviews_chart').getContext('2d');

                    /* Colors */
                    pageviews_color_gradient = pageviews_chart.createLinearGradient(0, 0, 0, 250);
                    pageviews_color_gradient.addColorStop(0, set_hex_opacity(pageviews_color, 0.6));
                    pageviews_color_gradient.addColorStop(1, set_hex_opacity(pageviews_color, 0.1));

                    visitors_color_gradient = pageviews_chart.createLinearGradient(0, 0, 0, 250);
                    visitors_color_gradient.addColorStop(0, set_hex_opacity(visitors_color, 0.6));
                    visitors_color_gradient.addColorStop(1, set_hex_opacity(visitors_color, 0.1));

                    new Chart(pageviews_chart, {
                        type: 'line',
                        data: {
                            labels: JSON.parse(data.details.links_chart.labels ?? '[]'),
                            datasets: [
                                {
                                    label: <?= json_encode(l('link.statistics.pageviews')) ?>,
                                    data: JSON.parse(data.details.links_chart.pageviews ?? '[]'),
                                    backgroundColor: pageviews_color_gradient,
                                    borderColor: pageviews_color,
                                    fill: true
                                },
                                {
                                    label: <?= json_encode(l('link.statistics.visitors')) ?>,
                                    data: JSON.parse(data.details.links_chart.visitors ?? '[]'),
                                    backgroundColor: visitors_color_gradient,
                                    borderColor: visitors_color,
                                    fill: true
                                }
                            ]
                        },
                        options: chart_options
                    });
                }
            }
        })();
    </script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
