<?php defined('ALTUMCODE') || die() ?>

<h1 class="h3 mb-4 text-truncate"><?= sprintf(l('admin_index.header'), $this->user->name) ?></h1>

<!-- Custom code: FC-2026-03-18: grouped admin dashboard layout -->
<div class="d-flex flex-column">
    <div class="mb-3" style="order: 10;">
        <div class="border-bottom pb-2">
            <h2 class="h4 mb-1"><?= l('admin_index.dashboard_sections.priority.header') ?></h2>
            <p class="text-muted mb-0"><?= l('admin_index.dashboard_sections.priority.subheader') ?></p>
        </div>
    </div>

    <div class="mb-3 mt-2" style="order: 20;">
        <div class="border-bottom pb-2">
            <h2 class="h4 mb-1"><?= l('admin_index.dashboard_sections.revenue.header') ?></h2>
            <p class="text-muted mb-0"><?= l('admin_index.dashboard_sections.revenue.subheader') ?></p>
        </div>
    </div>

    <div class="mb-3 mt-2" style="order: 30;">
        <div class="border-bottom pb-2">
            <h2 class="h4 mb-1"><?= l('admin_index.dashboard_sections.forever.header') ?></h2>
            <p class="text-muted mb-0"><?= l('admin_index.dashboard_sections.forever.subheader') ?></p>
        </div>
    </div>

    <div class="mb-3 mt-2" style="order: 40;">
        <div class="border-bottom pb-2">
            <h2 class="h4 mb-1"><?= l('admin_index.dashboard_sections.platform.header') ?></h2>
            <p class="text-muted mb-0"><?= l('admin_index.dashboard_sections.platform.subheader') ?></p>
        </div>
    </div>

<!-- Custom code: FC-2026-03-04: admin dashboard phase 1 analytics layout -->
<div class="mb-5" style="order: 41;">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
        <h2 class="h4 mb-3 mb-lg-0"><?= l('admin_index.analytics_phase1.header') ?></h2>

        <div class="btn-group btn-group-sm" role="group" aria-label="KPI period">
            <button type="button" class="btn btn-outline-primary active" data-kpi-period="today"><?= l('admin_index.analytics_phase1.period.today') ?></button>
            <button type="button" class="btn btn-outline-primary" data-kpi-period="7d"><?= l('admin_index.analytics_phase1.period.7d') ?></button>
            <button type="button" class="btn btn-outline-primary" data-kpi-period="30d"><?= l('admin_index.analytics_phase1.period.30d') ?></button>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_index.analytics_phase1.kpi.payments') ?></small>
                    <div class="h5 mb-0" id="kpi_payments_count"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_index.analytics_phase1.kpi.net_earnings') ?></small>
                    <div class="h5 mb-0"><span id="kpi_net_earnings"><span class="spinner-border spinner-border-sm" role="status"></span></span> <small><?= settings()->payment->default_currency ?></small></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_index.analytics_phase1.kpi.new_users') ?></small>
                    <div class="h5 mb-0" id="kpi_new_users"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <!-- Custom code: FC-2026-03-05: active pro KPI criteria tooltip -->
                    <small class="text-muted d-block mb-1">
                        <?= l('admin_index.analytics_phase1.kpi.active_pro') ?>
                        <span class="text-muted" data-toggle="tooltip" title="<?= l('admin_index.analytics_phase1.kpi.active_pro_tooltip') ?>">
                            <i class="fas fa-fw fa-xs fa-info-circle"></i>
                        </span>
                    </small>
                    <!-- /Custom code: FC-2026-03-05 -->
                    <div class="h5 mb-0" id="kpi_active_pro_packages"><span class="spinner-border spinner-border-sm" role="status"></span></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_index.analytics_phase1.kpi.churn') ?></small>
                    <div class="h5 mb-0"><span id="kpi_churn_rate"><span class="spinner-border spinner-border-sm" role="status"></span></span>%</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_index.analytics_phase1.kpi.arpu') ?></small>
                    <div class="h5 mb-0"><span id="kpi_arpu"><span class="spinner-border spinner-border-sm" role="status"></span></span> <small><?= settings()->payment->default_currency ?></small></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-8 p-2">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 mb-0"><?= l('admin_index.analytics_phase1.charts.revenue') ?></h3>
                    </div>
                    <div class="chart-container" style="height: 220px;">
                        <canvas id="dashboard_revenue_chart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_index.analytics_phase1.charts.users') ?></h3>
                    <div class="chart-container" style="height: 220px;">
                        <canvas id="dashboard_users_chart"></canvas>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-12 col-xl-4 p-2">
            <div class="card mb-3">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_index.analytics_phase1.realtime.header') ?></h3>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted"><?= l('admin_index.analytics_phase1.realtime.online_users') ?></span>
                        <strong id="realtime_online_users"><span class="spinner-border spinner-border-sm" role="status"></span></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted"><?= l('admin_index.analytics_phase1.realtime.active_sessions') ?></span>
                        <strong id="realtime_active_sessions"><span class="spinner-border spinner-border-sm" role="status"></span></strong>
                    </div>

                    <h4 class="h6 mt-4 mb-2"><?= l('admin_index.analytics_phase1.realtime.recent_logins') ?></h4>
                    <div id="realtime_recent_logins" class="small text-muted">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>

                    <h4 class="h6 mt-4 mb-2"><?= l('admin_index.analytics_phase1.realtime.online_collaborators') ?></h4>
                    <div id="realtime_online_collaborators" class="small text-muted">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_index.analytics_phase1.alerts.header') ?></h3>
                    <div class="mb-2" id="alert_failed_payments">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                    <div id="alert_churn_spike">
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-04 -->

<!-- Custom code: FC-2026-03-04: phase 2 sales and subscriptions panel -->
<div class="mb-5 mt-4" style="order: 21;">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-credit-card text-primary-900 mr-2"></i> <?= l('admin_index.sales_subscriptions.header') ?></h1>
    </div>

    <p class="text-muted mb-3"><?= l('admin_index.sales_subscriptions.subheader') ?></p>

    <div class="row mb-2">
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.recurring_revenue_current_month') ?></small><div class="h5 mb-0"><span id="sales_recurring_revenue_current_month"><span class="spinner-border spinner-border-sm" role="status"></span></span> <small><?= settings()->payment->default_currency ?></small></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.active_paid_subscriptions') ?></small><div class="h5 mb-0" id="sales_active_paid_subscriptions"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.new_subscriptions_30d') ?></small><div class="h5 mb-0" id="sales_new_subscriptions_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.cancelled_subscriptions_30d') ?></small><div class="h5 mb-0" id="sales_cancelled_subscriptions_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.failed_payments_30d') ?></small><div class="h5 mb-0" id="sales_failed_payments_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_index.sales_subscriptions.plan_changes_30d') ?></small><div class="h5 mb-0" id="sales_plan_changes_30d"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-8 p-2">
            <div class="card">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_index.sales_subscriptions.chart_header') ?></h3>
                    <div class="chart-container" style="height: 230px;">
                        <canvas id="sales_subscriptions_chart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4 p-2">
            <div class="card">
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
<!-- /Custom code: FC-2026-03-04 -->

<!-- Custom code: FC-2026-03-04: phase 5 action center panel -->
<div class="mb-5 mt-4" style="order: 11;">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-bolt text-primary-900 mr-2"></i> <?= l('admin_index.action_center.header') ?></h1>
    </div>

    <p class="text-muted mb-3"><?= l('admin_index.action_center.subheader') ?></p>

    <div class="row mb-2">
        <div class="col-12 col-md-6 p-2">
            <div id="action_warning_trials" class="alert alert-light mb-0">
                <span class="spinner-border spinner-border-sm" role="status"></span>
            </div>
        </div>
        <div class="col-12 col-md-6 p-2">
            <div id="action_warning_collaborators" class="alert alert-light mb-0">
                <span class="spinner-border spinner-border-sm" role="status"></span>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-6 p-2">
            <div class="card h-100">
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
            <div class="card h-100">
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
<!-- /Custom code: FC-2026-03-04 -->

<!-- Custom code: FC-2026-03-17: billing risk dashboard panel -->
<div class="mb-5 mt-4" style="order: 12;">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-triangle-exclamation text-primary-900 mr-2"></i> <?= l('admin_index.billing_risk.header') ?></h1>
        <a href="<?= url('admin/billing-risk') ?>" class="btn btn-outline-primary btn-sm"><?= l('admin_index.billing_risk.view_all') ?></a>
    </div>

    <p class="text-muted mb-3"><?= l('admin_index.billing_risk.subheader') ?></p>

    <div class="row mb-2">
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_billing_risk.state_past_due') ?></small><div class="h5 mb-0" id="billing_risk_past_due"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_billing_risk.state_past_due_critical') ?></small><div class="h5 mb-0" id="billing_risk_critical"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-2 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_billing_risk.state_access_revoked') ?></small><div class="h5 mb-0" id="billing_risk_revoked"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_index.billing_risk.expiring_24h') ?></small><div class="h5 mb-0" id="billing_risk_expiring"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_index.billing_risk.recovered_7d') ?></small><div class="h5 mb-0" id="billing_risk_recovered"><span class="spinner-border spinner-border-sm" role="status"></span></div></div></div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 p-2">
            <div class="card h-100">
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
<!-- /Custom code: FC-2026-03-17 -->

<!-- Custom code: FC-2026-03-04: phase 4 biolink traffic and collaborator analytics panel -->
<div class="mb-5 mt-4" style="order: 33;">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-globe-europe text-primary-900 mr-2"></i> <?= l('admin_index.biolink_analytics.header') ?></h1>

        <div class="btn-group btn-group-sm" role="group" aria-label="<?= l('admin_index.biolink_analytics.period_label') ?>">
            <button type="button" class="btn btn-outline-primary" data-biolink-period="today"><?= l('admin_index.analytics_phase1.period.today') ?></button>
            <button type="button" class="btn btn-outline-primary" data-biolink-period="7d"><?= l('admin_index.analytics_phase1.period.7d') ?></button>
            <button type="button" class="btn btn-outline-primary active" data-biolink-period="30d"><?= l('admin_index.analytics_phase1.period.30d') ?></button>
        </div>
    </div>

    <p class="text-muted mb-3"><?= l('admin_index.biolink_analytics.subheader') ?></p>

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
</div>
<!-- /Custom code: FC-2026-03-04 -->

<!-- Custom code: FC-2026-03-04: trial users monitoring panel -->
<div class="mb-5 mt-4" style="order: 13;">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-hourglass-half text-primary-900 mr-2"></i> <?= l('admin_index.trial_monitoring.header') ?></h1>

        <div class="d-flex align-items-center">
            <span class="badge badge-primary mr-2"><?= sprintf(l('admin_index.trial_monitoring.total'), nr($data->active_trial_total)) ?></span>
            <span class="badge badge-danger"><?= sprintf(l('admin_index.trial_monitoring.cancelled_total'), nr($data->active_trial_cancelled_total)) ?></span>
        </div>
    </div>

    <p class="text-muted mb-3"><?= l('admin_index.trial_monitoring.subheader') ?></p>

    <form method="get" action="<?= url('admin') ?>" class="mb-3">
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
                <a href="<?= url('admin') ?>" class="btn btn-sm btn-outline-secondary ml-2"><?= l('global.reset') ?></a>
            </div>

            <div class="d-flex align-items-center">
                <small class="text-muted mr-2"><?= sprintf(l('admin_index.trial_monitoring.shown_total'), nr($data->active_trial_filtered_total)) ?></small>
                <a href="<?= url('admin?trial_filter=' . $data->trial_filter . '&trial_export=csv') ?>" class="btn btn-sm btn-outline-success">
                    <i class="fas fa-fw fa-sm fa-file-csv mr-1"></i><?= l('admin_index.trial_monitoring.export_csv') ?>
                </a>
            </div>
        </div>
    </form>

    <?php if(empty($data->active_trial_users_filtered)): ?>
        <div class="alert alert-info mb-0"><?= l('admin_index.trial_monitoring.empty') ?></div>
    <?php else: ?>
        <!-- Custom code: FC-2026-03-18: compact and prioritized trial monitoring rows -->
        <div class="table-responsive table-custom-container mb-3">
            <table class="table table-custom">
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
        <div id="trial_monitoring_table_toggle" class="mb-3"></div>
        <!-- /Custom code: FC-2026-03-18 -->
    <?php endif ?>
</div>
<!-- /Custom code: FC-2026-03-04 -->

<div class="mb-5 row justify-content-between" style="order: 42;">
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

<div class="mb-5" style="order: 43;">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-4">
        <h1 class="h3 mb-3 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-users text-primary-900 mr-2"></i> <?= l('admin_index.users') ?></h1>

        <div>
            <span class="badge badge-success" data-toggle="tooltip" title="<?= l('admin_index.active_users_tooltip') ?>">
                <i class="fas fa-xs fa-fw fa-circle fa-fade mr-1"></i>
                <span id="active_users" data-translation="<?= l('admin_index.active_users') ?>"><?= l('global.loading') ?></span>
            </span>
        </div>
    </div>

    <?php $result = database()->query("SELECT * FROM `users` ORDER BY `user_id` DESC LIMIT 5"); ?>
    <div class="table-responsive table-custom-container">
        <table class="table table-custom">
            <thead>
            <tr>
                <th><?= l('global.user') ?></th>
                <th><?= l('global.status') ?></th>
                <th><?= l('admin_users.plan_id') ?></th>
                <th><?= l('global.details') ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php while($row = $result->fetch_object()): ?>
                <?php //ALTUMCODE:DEMO if(DEMO) {$row->email = 'hidden@demo.com'; $row->name = 'hidden on demo';} ?>
                <?php if(!isset($data->plans[$row->plan_id])) $data->plans[$row->plan_id] = (new \Altum\Models\Plan())->get_plan_by_id($row->plan_id) ?>
                <tr>
                    <td class="text-nowrap">
                        <div class="d-flex">
                            <a href="<?= url('admin/user-view/' . $row->user_id) ?>">
                                <img src="<?= get_user_avatar($row->avatar, $row->email) ?>" class="user-avatar rounded-circle mr-3" alt="" />
                            </a>

                            <div class="d-flex flex-column">
                                <div>
                                    <a href="<?= url('admin/user-view/' . $row->user_id) ?>" <?= $row->type == 1 ? 'class="font-weight-bold" data-toggle="tooltip" title="' . l('admin_users.type_admin') . '"' : null ?>><?= $row->name ?></a>
                                </div>

                                <span class="small text-muted"><?= $row->email ?></span>
                            </div>
                        </div>
                    </td>
                    <td class="text-nowrap">
                        <?php if($row->status == 0): ?>
                            <a href="<?= url('admin/users?status=0') ?>" class="badge badge-warning"><i class="fas fa-fw fa-sm fa-eye-slash mr-1"></i> <?= l('admin_users.status_unconfirmed') ?></a>
                        <?php elseif($row->status == 1): ?>
                            <a href="<?= url('admin/users?status=1') ?>" class="badge badge-success"><i class="fas fa-fw fa-sm fa-check mr-1"></i> <?= l('admin_users.status_active') ?></a>
                        <?php elseif($row->status == 2): ?>
                            <a href="<?= url('admin/users?status=2') ?>" class="badge badge-light"><i class="fas fa-fw fa-sm fa-times mr-1"></i> <?= l('admin_users.status_disabled') ?></a>
                        <?php endif ?>
                    </td>
                    <td class="text-nowrap">
                        <div class="d-flex flex-column">
                            <div>
                                <a href="<?= url('admin/plan-update/' . $row->plan_id) ?>" class="badge badge-light"><?= $data->plans[$row->plan_id]->name ?></a>
                            </div>

                            <?php if($row->plan_id != 'free'): ?>
                                <div>
                                    <small class="text-muted" data-toggle="tooltip" title="<?= l('admin_users.plan_expiration_date') ?>"><?= \Altum\Date::get($row->plan_expiration_date, 1) ?></small>
                                </div>
                            <?php endif ?>
                        </div>
                    </td>
                    <td class="text-nowrap">
                        <div class="d-flex align-items-center">
                            <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= l('admin_users.datetime') . '<br />' . \Altum\Date::get($row->datetime, 2) . '<br /><small>' . \Altum\Date::get($row->datetime, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->datetime) . ')</small>' ?>">
                                <i class="fas fa-fw fa-calendar text-muted"></i>
                            </span>

                            <a href="<?= url('admin/users?source=' . $row->source) ?>" class="mr-2" data-toggle="tooltip" title="<?= l('admin_users.source.' . $row->source) ?>">
                                <i class="fas fa-fw fa-sign-in-alt text-muted"></i>
                            </a>

                            <span class="mr-2" data-toggle="tooltip" data-html="true" title="<?= l('admin_users.last_activity') . '<br />' . \Altum\Date::get($row->last_activity, 2) . '<br /><small>' . \Altum\Date::get($row->last_activity, 3) . '</small>' . '<br /><small>(' . \Altum\Date::get_timeago($row->last_activity) . ')</small>' ?>">
                                <i class="fas fa-fw fa-history text-muted"></i>
                            </span>

                            <span class="mr-2" data-toggle="tooltip" title="<?= sprintf(l('admin_users.table.total_logins'), nr($row->total_logins)) ?>">
                                <i class="fas fa-fw fa-user-clock text-muted"></i>
                            </span>

                            <a href="<?= url('admin/users?continent_code=' . $row->continent_code) ?>" class="mr-2" data-toggle="tooltip" title="<?= get_continent_from_continent_code($row->continent_code ?? l('global.unknown')) ?>">
                                <i class="fas fa-fw fa-globe-europe text-muted"></i>
                            </a>

                            <a href="<?= url('admin/users?country=' . $row->country) ?>">
                                <?php if($row->country): ?>
                                    <img src="<?= ASSETS_FULL_URL . 'images/countries/' . mb_strtolower($row->country) . '.svg' ?>" class="icon-favicon mr-2" data-toggle="tooltip" title="<?= get_country_from_country_code($row->country) ?>" />
                                <?php else: ?>
                                    <span class="mr-2" data-toggle="tooltip" title="<?= l('global.unknown') ?>">
                                    <i class="fas fa-fw fa-flag text-muted"></i>
                                </span>
                                <?php endif ?>
                            </a>

                            <a href="<?= url('admin/users?city_name=' . $row->city_name) ?>" class="mr-2" data-toggle="tooltip" title="<?= $row->city_name ?? l('global.unknown') ?>">
                                <i class="fas fa-fw fa-city text-muted"></i>
                            </a>
                        </div>
                    </td>
                    <td>
                        <div class="d-flex justify-content-end">
                            <?= include_view(THEME_PATH . 'views/admin/users/admin_user_dropdown_button.php', ['id' => $row->user_id, 'resource_name' => $row->name]) ?>
                        </div>
                    </td>
                </tr>
            <?php endwhile ?>

            <tr>
                <td colspan="5">
                    <a href="<?= url('admin/users') ?>" class="text-muted text-decoration-none small">
                        <i class="fas fa-angle-right fa-sm fa-fw mr-1"></i> <?= l('global.view_more') ?>
                    </a>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</div>

<?php if(settings()->internal_notifications->admins_is_enabled): ?>
    <?php if($data->internal_notifications): ?>
        <div style="order: 44;">
        <h1 class="h3 mb-4"><i class="fas fa-fw fa-xs fa-bell text-primary-900 mr-2"></i> <?= l('admin_index.admins_notifications') ?></h1>

        <div class="card mb-5">
            <div class="card-body py-2">
                <div>
                    <?php foreach($data->internal_notifications as $notification): ?>
                        <?php //ALTUMCODE:DEMO if(DEMO) {$notification->title = $notification->description = 'hidden on demo';} ?>

                        <div class="bg-gray-100 p-3 my-3 rounded <?= $notification->is_read ? null : 'border border-info' ?> position-relative">
                            <div class="d-flex align-items-center">
                                <div class="p-3 bg-gray-50 mr-3 rounded">
                                    <i class="<?= $notification->icon ?> fa-fw fa-lg text-primary-900"></i>
                                </div>

                                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between flex-fill">
                                    <div class="d-flex flex-column">
                                        <div class="font-weight-bold mb-1">
                                            <?php if($notification->url): ?>
                                                <a href="<?= $notification->url ?>" class="stretched-link text-decoration-none text-body"><?= $notification->title ?></a>
                                            <?php else: ?>
                                                <?= $notification->title ?>
                                            <?php endif ?>
                                        </div>

                                        <small class="text-muted"><?= $notification->description ?></small>
                                    </div>

                                    <div>
                                        <small class="text-muted" data-toggle="tooltip" title="<?= \Altum\Date::get($notification->datetime, 1) ?>"><?= \Altum\Date::get_timeago($notification->datetime) ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        </div>
    <?php endif ?>
<?php endif ?>


<?php if(in_array(settings()->license->type, ['SPECIAL', 'Extended License', 'extended'])): ?>
    <?php $result = database()->query("SELECT `payments`.*, `users`.`name` AS `user_name`, `users`.`email` AS `user_email`, `users`.`avatar` AS `user_avatar` FROM `payments` LEFT JOIN `users` ON `payments`.`user_id` = `users`.`user_id` ORDER BY `id` DESC LIMIT 5"); ?>

    <?php if($result->num_rows): ?>
        <div class="mb-5" style="order: 22;">
            <h1 class="h3 mb-4"><i class="fas fa-fw fa-xs fa-credit-card text-primary-900 mr-2"></i> <?= l('admin_index.payments') ?></h1>

            <div class="table-responsive table-custom-container">
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
    <?php endif ?>
<?php endif ?>

<div class="mb-5 mt-4" style="order: 31;">
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

<div class="mb-5 mt-4" style="order: 32;">
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
    let dashboard_charts = {
        revenue: null,
        users: null,
        sales_subscriptions: null,
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
        document.querySelector('#realtime_online_users').innerText = nr(realtime.online_users ?? 0);
        document.querySelector('#realtime_active_sessions').innerText = nr(realtime.active_sessions ?? 0);

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

        document.querySelector('#realtime_recent_logins').innerHTML = recent_logins_html || `<span class="text-muted"><?= l('global.no_data') ?></span>`;

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

        document.querySelector('#realtime_online_collaborators').innerHTML = online_collaborators_html || `<span class="text-muted"><?= l('global.no_data') ?></span>`;
    };

    const render_alerts = alerts => {
        const failed_payments_class = alerts.failed_payments_warning ? 'alert alert-warning mb-2' : 'alert alert-success mb-2';
        const churn_class = alerts.churn_spike ? 'alert alert-danger mb-0' : 'alert alert-success mb-0';

        document.querySelector('#alert_failed_payments').className = failed_payments_class;
        document.querySelector('#alert_failed_payments').innerText = `<?= l('admin_index.analytics_phase1.alerts.failed_payments_7d') ?>: ${nr(alerts.failed_payments_7d ?? 0)}`;

        document.querySelector('#alert_churn_spike').className = churn_class;
        document.querySelector('#alert_churn_spike').innerText = alerts.churn_spike
            ? `<?= l('admin_index.analytics_phase1.alerts.churn_spike_on') ?> (${nr(alerts.churn_7d ?? 0)}%)`
            : `<?= l('admin_index.analytics_phase1.alerts.churn_spike_off') ?> (${nr(alerts.churn_7d ?? 0)}%)`;
    };

    const render_charts = charts => {
        const revenue_chart_context = document.getElementById('dashboard_revenue_chart').getContext('2d');
        const users_chart_context = document.getElementById('dashboard_users_chart').getContext('2d');

        if(!dashboard_charts.revenue) {
            dashboard_charts.revenue = new Chart(revenue_chart_context, {
                type: 'line',
                data: {
                    labels: charts.labels ?? [],
                    datasets: [{
                        label: '<?= l('admin_index.analytics_phase1.charts.revenue_dataset') ?>',
                        data: charts.revenue_series ?? [],
                        borderColor: chart_css.getPropertyValue('--primary'),
                        backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--primary'), 0.08),
                        fill: true
                    }]
                },
                options: chart_options
            });
        } else {
            dashboard_charts.revenue.data.labels = charts.labels ?? [];
            dashboard_charts.revenue.data.datasets[0].data = charts.revenue_series ?? [];
            dashboard_charts.revenue.update();
        }

        if(!dashboard_charts.users) {
            dashboard_charts.users = new Chart(users_chart_context, {
                type: 'line',
                data: {
                    labels: charts.labels ?? [],
                    datasets: [{
                        label: '<?= l('admin_index.analytics_phase1.charts.new_users_dataset') ?>',
                        data: charts.new_users_series ?? [],
                        borderColor: chart_css.getPropertyValue('--success'),
                        backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--success'), 0.08),
                        fill: true
                    }, {
                        label: '<?= l('admin_index.analytics_phase1.charts.active_users_dataset') ?>',
                        data: charts.active_users_series ?? [],
                        borderColor: chart_css.getPropertyValue('--info'),
                        backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--info'), 0.08),
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
            dashboard_charts.users.data.labels = charts.labels ?? [];
            dashboard_charts.users.data.datasets[0].data = charts.new_users_series ?? [];
            dashboard_charts.users.data.datasets[1].data = charts.active_users_series ?? [];
            dashboard_charts.users.update();
        }

    };

    const render_sales_subscriptions = (sales_subscriptions, charts) => {
        document.querySelector('#sales_recurring_revenue_current_month').innerText = nr(sales_subscriptions.recurring_revenue_current_month ?? 0);
        document.querySelector('#sales_active_paid_subscriptions').innerText = nr(sales_subscriptions.active_paid_subscriptions ?? 0);
        document.querySelector('#sales_new_subscriptions_30d').innerText = nr(sales_subscriptions.new_subscriptions_30d ?? 0);
        document.querySelector('#sales_cancelled_subscriptions_30d').innerText = nr(sales_subscriptions.cancelled_subscriptions_30d ?? 0);
        document.querySelector('#sales_failed_payments_30d').innerText = nr(sales_subscriptions.failed_payments_30d ?? 0);
        document.querySelector('#sales_plan_changes_30d').innerText = nr(sales_subscriptions.plan_changes_30d ?? 0);

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

        document.querySelector('#sales_at_risk_trial_users').innerHTML = at_risk_html || `<span class="text-muted"><?= l('global.no_data') ?></span>`;

        const sales_subscriptions_chart_context = document.getElementById('sales_subscriptions_chart').getContext('2d');
        if(!dashboard_charts.sales_subscriptions) {
            dashboard_charts.sales_subscriptions = new Chart(sales_subscriptions_chart_context, {
                type: 'line',
                data: {
                    labels: charts.labels ?? [],
                    datasets: [{
                        label: '<?= l('admin_index.sales_subscriptions.chart_new_subscriptions') ?>',
                        data: charts.new_subscriptions_series ?? [],
                        borderColor: chart_css.getPropertyValue('--success'),
                        backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--success'), 0.08),
                        fill: true
                    }, {
                        label: '<?= l('admin_index.sales_subscriptions.chart_cancelled_subscriptions') ?>',
                        data: charts.cancelled_subscriptions_series ?? [],
                        borderColor: chart_css.getPropertyValue('--danger'),
                        backgroundColor: set_hex_opacity(chart_css.getPropertyValue('--danger'), 0.08),
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
            dashboard_charts.sales_subscriptions.data.labels = charts.labels ?? [];
            dashboard_charts.sales_subscriptions.data.datasets[0].data = charts.new_subscriptions_series ?? [];
            dashboard_charts.sales_subscriptions.data.datasets[1].data = charts.cancelled_subscriptions_series ?? [];
            dashboard_charts.sales_subscriptions.update();
        }
    };

    /* Custom code: FC-2026-03-04: phase 5 action center rendering */
    const render_action_center = action_center => {
        const warnings_map = {};
        (action_center.warnings ?? []).forEach(item => {
            warnings_map[item.key] = item;
        });

        const trials_warning = warnings_map.trials_expiring_without_subscription || {is_active: false, value: 0};
        const collaborators_warning = warnings_map.high_collaborator_clicks_no_billing || {is_active: false, value: 0};

        document.querySelector('#action_warning_trials').className = trials_warning.is_active ? 'alert alert-danger mb-0' : 'alert alert-success mb-0';
        document.querySelector('#action_warning_trials').innerText = trials_warning.is_active
            ? `<?= l('admin_index.action_center.warning_trials_active') ?> (${nr(trials_warning.value ?? 0)})`
            : `<?= l('admin_index.action_center.warning_trials_stable') ?> (${nr(trials_warning.value ?? 0)})`;

        document.querySelector('#action_warning_collaborators').className = collaborators_warning.is_active ? 'alert alert-warning mb-0' : 'alert alert-success mb-0';
        document.querySelector('#action_warning_collaborators').innerText = collaborators_warning.is_active
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

        document.querySelector('#action_urgent_trials_count').innerText = `(${nr(urgent_trials_items.length)})`;
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

        document.querySelector('#action_collaborator_opportunities_count').innerText = `(${nr(collaborator_opportunities_items.length)})`;
        render_compact_list('#action_collaborator_opportunities', collaborator_opportunities_items, 5);
    };
    /* /Custom code: FC-2026-03-04 */

    /* Custom code: FC-2026-03-17: billing risk dashboard rendering */
    const render_billing_risk = billing_risk => {
        const counts = billing_risk.counts ?? {};
        const state_badges = {
            past_due: 'warning',
            past_due_critical: 'danger',
            access_revoked: 'secondary',
            healthy: 'success',
        };

        document.querySelector('#billing_risk_past_due').innerText = nr(counts.past_due ?? 0);
        document.querySelector('#billing_risk_critical').innerText = nr(counts.past_due_critical ?? 0);
        document.querySelector('#billing_risk_revoked').innerText = nr(counts.access_revoked ?? 0);
        document.querySelector('#billing_risk_expiring').innerText = nr(counts.expiring_24h ?? 0);
        document.querySelector('#billing_risk_recovered').innerText = nr(counts.recovered_7d ?? 0);

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

        document.querySelector('#billing_risk_users_count').innerText = `(${nr(users.length)})`;
        render_compact_list('#billing_risk_users', items, 7);
    };
    /* /Custom code: FC-2026-03-17 */

    /* Custom code: FC-2026-03-04: phase 4 biolink analytics rendering */
    const period_labels = {
        today: '<?= l('admin_index.analytics_phase1.period.today') ?>',
        '7d': '<?= l('admin_index.analytics_phase1.period.7d') ?>',
        '30d': '<?= l('admin_index.analytics_phase1.period.30d') ?>',
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

    const render_biolink_period = () => {
        const period_data = biolink_analytics_payload.periods?.[biolink_analytics_period] || {};
        const selected_period_label = period_labels[biolink_analytics_period] || period_labels['30d'];

        document.querySelector('#biolink_period_total_label').innerText = `<?= l('admin_index.biolink_analytics.clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_period_shop_label').innerText = `<?= l('admin_index.biolink_analytics.forever_shop_clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_period_registration_label').innerText = `<?= l('admin_index.biolink_analytics.forever_registration_clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_period_total_clicks').innerText = nr(period_data.clicks_total ?? 0);
        document.querySelector('#biolink_period_shop_clicks').innerText = nr(period_data.forever_shop_clicks ?? 0);
        document.querySelector('#biolink_period_registration_clicks').innerText = nr(period_data.forever_registration_clicks ?? 0);

        document.querySelector('#biolink_top_countries_label').innerText = `<?= l('admin_index.biolink_analytics.top_countries_header') ?> (${selected_period_label})`;
        document.querySelector('#biolink_leaderboard_label').innerText = `<?= l('admin_index.biolink_analytics.leaderboard_header') ?> (${selected_period_label})`;
        document.querySelector('#biolink_user_clicks_total_label').innerText = `<?= l('admin_index.biolink_analytics.clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_user_forever_shop_label').innerText = `<?= l('admin_index.biolink_analytics.forever_shop_clicks_selected_period') ?> (${selected_period_label})`;
        document.querySelector('#biolink_user_forever_registration_label').innerText = `<?= l('admin_index.biolink_analytics.forever_registration_clicks_selected_period') ?> (${selected_period_label})`;

        const top_countries_items = (period_data.top_countries ?? []).map(country => `
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

                    document.querySelector('#biolink_user_details_title').innerText = user_name
                        ? `<?= l('admin_index.biolink_analytics.user_details_for') ?> ${user_name}`
                        : `<?= l('admin_index.biolink_analytics.user_details_default_title') ?>`;

                    render_biolink_user_details(user_id);
                });
            });
        });

        if((period_data.leaderboard ?? []).length) {
            const first_user = period_data.leaderboard[0];
            document.querySelector('#biolink_user_details_title').innerText = `<?= l('admin_index.biolink_analytics.user_details_for') ?> ${first_user.name ?? ''}`;
            render_biolink_user_details(String(first_user.user_id ?? ''));
        } else {
            document.querySelector('#biolink_user_details_title').innerText = `<?= l('admin_index.biolink_analytics.user_details_default_title') ?>`;
            render_biolink_user_details(null);
        }
    };

    const render_biolink_analytics = biolink_analytics => {
        biolink_analytics_payload = biolink_analytics ?? {};

        render_biolink_period();
    };
    /* /Custom code: FC-2026-03-04 */

    document.querySelectorAll('[data-kpi-period]').forEach(button => {
        button.addEventListener('click', event => {
            dashboard_kpi_period = event.currentTarget.getAttribute('data-kpi-period');

            document.querySelectorAll('[data-kpi-period]').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');

            render_kpi(dashboard_kpi_period);
        });
    });

    document.querySelectorAll('[data-biolink-period]').forEach(button => {
        button.addEventListener('click', event => {
            biolink_analytics_period = event.currentTarget.getAttribute('data-biolink-period');

            document.querySelectorAll('[data-biolink-period]').forEach(item => item.classList.remove('active'));
            event.currentTarget.classList.add('active');

            render_biolink_period();
        });
    });

    init_compact_table_rows('#fcc_pending_table_body .fcc-pending-row', '#fcc_pending_table_toggle', 5);
    init_compact_table_rows('#sales_link_missing_table_body .sales-link-missing-row', '#sales_link_missing_table_toggle', 5);
    init_compact_table_rows('#trial_monitoring_table_body .trial-monitoring-row', '#trial_monitoring_table_toggle', 5);
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
            document.querySelector('#biolink_links').innerHTML = data.details.biolink_links ? nr(data.details.biolink_links) : 0;
            document.querySelector('#biolink_links_current_month').innerHTML = data.details.biolink_links_current_month ? nr(data.details.biolink_links_current_month) : 0;

            document.querySelector('#shortened_links').innerHTML = data.details.shortened_links ? nr(data.details.shortened_links) : 0;
            document.querySelector('#shortened_links_current_month').innerHTML = data.details.shortened_links_current_month ? nr(data.details.shortened_links_current_month) : 0;

            document.querySelector('#track_links').innerHTML = data.details.track_links ? nr(data.details.track_links) : 0;
            document.querySelector('#track_links_current_month').innerHTML = data.details.track_links_current_month ? nr(data.details.track_links_current_month) : 0;

            document.querySelector('#qr_codes').innerHTML = data.details.qr_codes ? nr(data.details.qr_codes) : 0;
            document.querySelector('#qr_codes_current_month').innerHTML = data.details.qr_codes_current_month ? nr(data.details.qr_codes_current_month) : 0;

            document.querySelector('#domains').innerHTML = data.details.domains ? nr(data.details.domains) : 0;
            document.querySelector('#domains_current_month').innerHTML = data.details.domains_current_month ? nr(data.details.domains_current_month) : 0;

            document.querySelector('#payments_total_amount').innerHTML = data.details.payments_total_amount ? nr(data.details.payments_total_amount) : 0;
            document.querySelector('#users_current_month').innerHTML = data.details.users_current_month ? nr(data.details.users_current_month) : 0;

            document.querySelector('#users').innerHTML = data.details.users ? nr(data.details.users) : 0;
            document.querySelector('#payments_current_month').innerHTML = data.details.payments_current_month ? nr(data.details.payments_current_month) : 0;

            document.querySelector('#payments').innerHTML = data.details.payments ? nr(data.details.payments) : 0;
            document.querySelector('#payments_amount_current_month').innerHTML = data.details.payments_amount_current_month ? nr(data.details.payments_amount_current_month) : 0;

            let active_users = data.details.active_users ? nr(data.details.active_users) : 0;
            document.querySelector('#active_users').innerHTML = document.querySelector('#active_users').getAttribute('data-translation').replace('%s', active_users);

            /* Custom code: FC-2026-03-04: bind phase 1 analytics payload */
            if(data.details.admin_analytics) {
                dashboard_kpi_payload = data.details.admin_analytics.kpi ?? {};
                render_kpi(dashboard_kpi_period);

                render_realtime(data.details.admin_analytics.realtime ?? {});
                render_alerts(data.details.admin_analytics.alerts ?? {});
                render_charts(data.details.admin_analytics.charts ?? {});
                render_sales_subscriptions(data.details.admin_analytics.sales_subscriptions ?? {}, data.details.admin_analytics.charts ?? {});
                render_action_center(data.details.admin_analytics.action_center ?? {});
                render_billing_risk(data.details.admin_analytics.billing_risk ?? {});
                render_biolink_analytics(data.details.admin_analytics.biolink_analytics ?? {});
            }
            /* /Custom code: FC-2026-03-04 */

        }
    })();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
