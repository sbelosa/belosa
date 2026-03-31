<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-03-31: Leader Operating System overview shell -->
<style>
    .leader-os-shell {
        border: 1px solid rgba(148, 163, 184, 0.14);
        border-radius: 1.15rem;
        background: linear-gradient(180deg, rgba(17, 24, 39, 0.96) 0%, rgba(10, 15, 28, 0.98) 100%);
        box-shadow: 0 1.5rem 3rem rgba(2, 6, 23, 0.32);
        overflow: hidden;
    }

    .leader-os-shell,
    .leader-os-shell h1,
    .leader-os-shell h2,
    .leader-os-shell h3,
    .leader-os-shell .h1,
    .leader-os-shell .h2,
    .leader-os-shell .h3,
    .leader-os-shell .h4,
    .leader-os-shell .h5,
    .leader-os-shell .h6 {
        color: #ecf3ff;
    }

    .leader-os-shell .card-body {
        padding: 1.1rem;
    }

    .leader-os-kpi,
    .leader-os-panel {
        height: 100%;
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.12);
        background: rgba(11, 18, 32, 0.72);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
    }

    .leader-os-kpi {
        padding: 1rem 1.05rem;
    }

    .leader-os-panel {
        padding: 1rem;
    }

    .leader-os-shell .text-muted,
    .leader-os-panel .text-muted,
    .leader-os-kpi .text-muted {
        color: rgba(191, 211, 238, 0.72) !important;
    }

    .leader-os-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(96, 165, 250, 0.18);
        background: rgba(15, 23, 42, 0.72);
        color: #dceaff;
        font-size: 0.78rem;
        font-weight: 600;
    }

    .leader-os-inline-note {
        border-radius: 0.95rem;
        border: 1px solid rgba(125, 211, 252, 0.14);
        background: linear-gradient(90deg, rgba(8, 47, 73, 0.34) 0%, rgba(15, 23, 42, 0.18) 100%);
        color: #dceaff;
        padding: 0.8rem 0.95rem;
        font-size: 0.84rem;
        line-height: 1.45;
    }

    .leader-os-link {
        color: #7cc8ff !important;
    }

    .leader-os-action-button {
        border-color: rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .leader-os-action-button:hover,
    .leader-os-action-button:focus {
        border-color: rgba(124, 200, 255, 0.55);
        background: rgba(124, 200, 255, 0.18);
        color: #ffffff;
    }

    .leader-os-toolbar {
        display: grid;
        grid-template-columns: 1.4fr 0.9fr 0.9fr 0.9fr 0.9fr;
        gap: 0.85rem;
    }

    .leader-os-table {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 1rem;
        background: rgba(9, 15, 28, 0.72);
        overflow: hidden;
    }

    .leader-os-table .table {
        margin-bottom: 0;
    }

    .leader-os-table .table thead th {
        border-bottom-color: rgba(148, 163, 184, 0.12);
        color: rgba(191, 211, 238, 0.8);
        background: rgba(255, 255, 255, 0.015);
        white-space: nowrap;
    }

    .leader-os-table .table td,
    .leader-os-table .table th {
        border-top-color: rgba(148, 163, 184, 0.1);
        background: transparent;
        vertical-align: middle;
    }

    .leader-os-status-badge {
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 999px;
        padding: 0.32rem 0.6rem;
        display: inline-flex;
        align-items: center;
        font-size: 0.76rem;
        font-weight: 600;
        color: #e7f0ff;
        background: rgba(30, 41, 59, 0.72);
    }

    .leader-os-status-badge.status-success {
        background: rgba(22, 101, 52, 0.28);
        border-color: rgba(34, 197, 94, 0.28);
    }

    .leader-os-status-badge.status-warning {
        background: rgba(120, 53, 15, 0.28);
        border-color: rgba(251, 191, 36, 0.28);
    }

    .leader-os-status-badge.status-info {
        background: rgba(30, 64, 175, 0.28);
        border-color: rgba(96, 165, 250, 0.3);
    }

    .leader-os-status-badge.status-dark {
        background: rgba(31, 41, 55, 0.82);
        border-color: rgba(75, 85, 99, 0.35);
    }

    .leader-os-growth-positive {
        color: #86efac;
    }

    .leader-os-growth-negative {
        color: #fca5a5;
    }

    .leader-os-filter-chip {
        display: inline-flex;
        align-items: center;
        margin: 0 0.5rem 0.5rem 0;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        color: #dceaff;
        text-decoration: none;
        background: rgba(15, 23, 42, 0.72);
    }

    .leader-os-filter-chip.active {
        border-color: rgba(96, 165, 250, 0.36);
        background: rgba(30, 64, 175, 0.24);
        color: #ffffff;
    }

    .leader-os-sort-link {
        color: rgba(191, 211, 238, 0.8);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .leader-os-sort-link:hover {
        color: #ffffff;
        text-decoration: none;
    }

    .leader-os-sort-link.active {
        color: #ffffff;
    }

    .leader-os-sort-link.active::after {
        content: '↑';
        font-size: 0.75rem;
        color: #7cc8ff;
    }

    .leader-os-queue-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 0.85rem;
    }

    .leader-os-queue-card {
        border: 1px solid rgba(148, 163, 184, 0.12);
        border-radius: 1rem;
        background: rgba(9, 15, 28, 0.72);
        padding: 1rem;
    }

    .leader-os-queue-reason {
        color: rgba(191, 211, 238, 0.82);
        font-size: 0.88rem;
        line-height: 1.5;
    }

    .leader-os-queue-meta {
        display: grid;
        gap: 0.55rem;
        margin-top: 0.85rem;
    }

    /* Custom code: FC-2026-03-31: LOS overview AI usage badges */
    .leader-os-ai-usage {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        margin-top: 0.7rem;
    }

    .leader-os-ai-usage-main {
        font-size: 0.72rem;
        letter-spacing: 0.01em;
    }

    .leader-os-ai-usage-badge {
        font-size: 0.7rem;
        padding: 0.24rem 0.5rem;
    }

    .leader-os-anomaly-badge {
        font-size: 0.7rem;
        padding: 0.24rem 0.5rem;
    }
    /* /Custom code: FC-2026-03-31 */

    .leader-os-alert-list {
        display: grid;
        gap: 0.75rem;
    }

    .leader-os-alert-item {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.1);
    }

    .leader-os-alert-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .leader-os-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .leader-os-pagination-links {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    /* Custom code: FC-2026-03-31: Leader OS numeric pagination styling */
    .leader-os-page-link {
        min-width: 2.2rem;
        justify-content: center;
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.16);
        background: rgba(255, 255, 255, 0.04);
    }

    .leader-os-page-link:hover,
    .leader-os-page-link:focus {
        color: #ffffff;
        border-color: rgba(124, 200, 255, 0.55);
        background: rgba(124, 200, 255, 0.14);
    }

    .leader-os-page-link.active {
        background: rgba(30, 64, 175, 0.32);
        border-color: rgba(96, 165, 250, 0.45);
        color: #ffffff;
    }
    /* /Custom code: FC-2026-03-31 */

    @media (max-width: 767.98px) {
        .leader-os-shell .card-body {
            padding: 0.95rem;
        }

        .leader-os-toolbar {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
    <div>
        <h1 class="h3 mb-1"><?= l('admin_leader_operating_system.header') ?></h1>
        <p class="text-muted mb-0"><?= l('admin_leader_operating_system.subheader') ?></p>
    </div>

    <div class="mt-3 mt-lg-0 d-flex align-items-center">
        <span class="leader-os-pill mr-2"><?= l('admin_leader_operating_system.overview_badge') ?></span>

        <form action="<?= url('admin/leader-operating-system') ?>" method="get" class="mb-0">
            <input type="hidden" name="search" value="<?= input_clean($data->search_query) ?>" />
            <input type="hidden" name="status" value="<?= $data->selected_status ?>" />
            <input type="hidden" name="ai_status" value="<?= $data->selected_ai_status ?>" />
            <input type="hidden" name="anomaly_status" value="<?= $data->selected_anomaly_status ?>" />
            <input type="hidden" name="sort" value="<?= $data->selected_sort ?>" />
            <select name="period" class="custom-select custom-select-sm" onchange="this.form.submit()">
                <?php foreach($data->period_options as $period_option): ?>
                    <option value="<?= $period_option ?>" <?= $data->selected_period === $period_option ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.period_' . $period_option) ?></option>
                <?php endforeach ?>
            </select>
        </form>
    </div>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start mb-4">
            <div>
                <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_overview') ?></div>
                <h2 class="h4 mb-2"><?= l('admin_leader_operating_system.phase_title') ?></h2>
                <p class="text-muted mb-0"><?= l('admin_leader_operating_system.phase_description') ?></p>
            </div>

            <?php if(!empty($data->overview['rows'][0]['detail_url'] ?? null)): ?>
                <a href="<?= $data->overview['rows'][0]['detail_url'] ?>" class="btn btn-outline-light btn-sm mt-3 mt-lg-0 leader-os-link">
                    <?= l('admin_leader_operating_system.open_detail_demo') ?>
                </a>
            <?php endif ?>
        </div>

        <div class="row">
            <div class="col-12 col-lg-4 mb-3">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.placeholder_kpi_1') ?></div>
                    <div class="h2 mb-0"><?= nr($data->overview['totals']['all_collaborators']) ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-4 mb-3">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.kpi_qualified') ?></div>
                    <div class="h2 mb-0"><?= nr($data->overview['totals']['qualified']) ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-4 mb-3">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.kpi_rising') ?></div>
                    <div class="h2 mb-0"><?= nr($data->overview['totals']['rising']) ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.kpi_risk') ?></div>
                    <div class="h2 mb-0"><?= nr($data->overview['totals']['risk']) ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-3 mb-3 mb-lg-0">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.kpi_anomaly_high') ?></div>
                    <div class="h2 mb-0"><?= nr($data->overview['totals']['anomaly_high']) ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-3 mb-3 mb-lg-0">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.kpi_anomaly_watch') ?></div>
                    <div class="h2 mb-0"><?= nr($data->overview['totals']['anomaly_watch']) ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="leader-os-kpi">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.kpi_total_shop_clicks') ?></div>
                    <div class="h2 mb-0"><?= nr($data->overview['totals']['total_shop_clicks_period']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <form action="<?= url('admin/leader-operating-system') ?>" method="get">
            <input type="hidden" name="period" value="<?= $data->selected_period ?>" />

            <div class="leader-os-toolbar mb-3">
                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.search_label') ?></label>
                    <input type="search" name="search" class="form-control" value="<?= input_clean($data->search_query) ?>" placeholder="<?= l('admin_leader_operating_system.search_placeholder') ?>" />
                </div>

                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.filter_label') ?></label>
                    <select name="status" class="custom-select">
                        <?php foreach($data->status_options as $status_option): ?>
                            <option value="<?= $status_option ?>" <?= $data->selected_status === $status_option ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.filter.' . $status_option) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.ai_filter_label') ?></label>
                    <select name="ai_status" class="custom-select">
                        <?php foreach($data->ai_status_options as $ai_status_option): ?>
                            <option value="<?= $ai_status_option ?>" <?= $data->selected_ai_status === $ai_status_option ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.ai_filter.' . $ai_status_option) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.anomaly_filter_label') ?></label>
                    <select name="anomaly_status" class="custom-select">
                        <?php foreach($data->anomaly_status_options as $anomaly_status_option): ?>
                            <option value="<?= $anomaly_status_option ?>" <?= $data->selected_anomaly_status === $anomaly_status_option ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.anomaly_filter.' . $anomaly_status_option) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div>
                    <label class="small text-muted d-block mb-2"><?= l('admin_leader_operating_system.sort_label') ?></label>
                    <select name="sort" class="custom-select">
                        <?php foreach($data->sort_options as $sort_option): ?>
                            <option value="<?= $sort_option ?>" <?= $data->selected_sort === $sort_option ? 'selected="selected"' : null ?>><?= l('admin_leader_operating_system.sort.' . $sort_option) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap">
                <div>
                    <?php foreach($data->status_options as $status_option): ?>
                        <?php $chip_query = http_build_query(['period' => $data->selected_period, 'search' => $data->search_query, 'status' => $status_option, 'ai_status' => $data->selected_ai_status, 'anomaly_status' => $data->selected_anomaly_status, 'sort' => $data->selected_sort]); ?>
                        <a href="<?= url('admin/leader-operating-system?' . $chip_query) ?>" class="leader-os-filter-chip <?= $data->selected_status === $status_option ? 'active' : null ?>">
                            <?= l('admin_leader_operating_system.filter.' . $status_option) ?>
                        </a>
                    <?php endforeach ?>
                </div>

                <button type="submit" class="btn btn-sm mt-2 mt-lg-0 leader-os-action-button"><?= l('admin_leader_operating_system.apply_filters') ?></button>
            </div>

            <!-- Custom code: FC-2026-03-31: LOS overview helper note -->
            <div class="leader-os-inline-note mt-3"><?= l('admin_leader_operating_system.ai_filter_hint') ?></div>
            <!-- /Custom code: FC-2026-03-31 -->
        </form>
    </div>
</div>

<div class="card leader-os-shell mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_queue') ?></div>
                <h2 class="h4 mb-1"><?= l('admin_leader_operating_system.queue_title') ?></h2>
                <p class="text-muted mb-0"><?= l('admin_leader_operating_system.queue_text') ?></p>
            </div>
        </div>

        <?php if(empty($data->overview['queue_rows'])): ?>
            <div class="leader-os-panel mb-4">
                <p class="text-muted mb-0"><?= l('admin_leader_operating_system.queue_empty') ?></p>
            </div>
        <?php else: ?>
            <div class="leader-os-queue-grid mb-4">
                <?php foreach($data->overview['queue_rows'] as $queue_row): ?>
                    <div class="leader-os-queue-card">
                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.75rem;">
                            <div>
                                <div class="font-weight-bold"><?= $queue_row['name'] ?></div>
                                <div class="text-muted small"><?= $queue_row['email'] ?></div>
                                <!-- Custom code: FC-2026-03-31: LOS overview AI usage badges -->
                                <div class="leader-os-ai-usage">
                                    <span class="leader-os-status-badge leader-os-ai-usage-main <?= $queue_row['ai_usage_stage_class'] ?>"><?= htmlspecialchars((string) ($queue_row['ai_usage_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="leader-os-status-badge leader-os-anomaly-badge <?= $queue_row['anomaly_stage_class'] ?>"><?= htmlspecialchars((string) ($queue_row['anomaly_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php foreach(($queue_row['ai_usage_badges'] ?? []) as $ai_usage_badge): ?>
                                        <span class="leader-os-status-badge leader-os-ai-usage-badge <?= $ai_usage_badge['class'] ?>"><?= htmlspecialchars((string) ($ai_usage_badge['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach ?>
                                </div>
                                <!-- /Custom code: FC-2026-03-31 -->
                            </div>
                            <span class="leader-os-status-badge status-<?= $queue_row['status_class'] ?>"><?= $queue_row['status_label'] ?></span>
                        </div>

                        <div class="leader-os-queue-reason mb-2"><?= htmlspecialchars((string) ($queue_row['queue_reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>

                        <div class="leader-os-queue-meta">
                            <div class="text-muted small"><?= l('admin_leader_operating_system.queue_score') ?>: <strong class="text-white"><?= nr((int) ($queue_row['queue_priority_score'] ?? 0)) ?></strong></div>
                            <div class="text-muted small"><?= l('admin_leader_operating_system.queue_risk') ?>: <strong class="text-white"><?= nr((int) ($queue_row['risk_score'] ?? 0)) ?></strong></div>
                            <div class="text-muted small"><?= l('admin_leader_operating_system.queue_anomaly') ?>: <strong class="text-white"><?= nr((int) ($queue_row['anomaly_score'] ?? 0)) ?></strong></div>
                            <?php if(!empty($queue_row['mentor_next_action'])): ?>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.queue_next_action') ?>: <strong class="text-white"><?= htmlspecialchars((string) $queue_row['mentor_next_action'], ENT_QUOTES, 'UTF-8') ?></strong></div>
                            <?php endif ?>
                        </div>

                        <div class="mt-3 d-flex flex-column">
                            <a href="<?= $queue_row['detail_url'] ?>" class="leader-os-link"><?= l('admin_leader_operating_system.queue_open') ?></a>
                            <a href="<?= $queue_row['admin_user_url'] ?>" class="leader-os-link text-muted"><?= l('admin_index.biolink_qualified_watch.open_profile') ?></a>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
            <div>
                <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_roster') ?></div>
                <h2 class="h4 mb-1"><?= l('admin_leader_operating_system.roster_title') ?></h2>
                <p class="text-muted mb-0">
                    <?= sprintf(l('admin_leader_operating_system.results_total'), nr($data->overview['pagination']['total_results'])) ?>
                    ·
                    <?= sprintf(l('admin_leader_operating_system.results_range'), nr($data->overview['pagination']['from']), nr($data->overview['pagination']['to'])) ?>
                </p>
                <!-- Custom code: FC-2026-03-31: LOS overview roster helper -->
                <div class="leader-os-inline-note mt-3"><?= l('admin_leader_operating_system.roster_text') ?></div>
                <!-- /Custom code: FC-2026-03-31 -->
            </div>
        </div>

        <?php if(empty($data->overview['rows'])): ?>
            <div class="leader-os-panel">
                <p class="text-muted mb-0"><?= l('admin_leader_operating_system.empty') ?></p>
            </div>
        <?php else: ?>
            <?php
            $sort_header_query_base = [
                'period' => $data->selected_period,
                'search' => $data->search_query,
                'status' => $data->selected_status,
                'ai_status' => $data->selected_ai_status,
                'anomaly_status' => $data->selected_anomaly_status,
                'page' => 1,
            ];
            $get_sort_url = static function(string $sort_key) use ($sort_header_query_base) {
                return url('admin/leader-operating-system?' . http_build_query(array_merge($sort_header_query_base, ['sort' => $sort_key])));
            };
            $get_sort_class = static function(string $sort_key, string $selected_sort) {
                return $selected_sort === $sort_key ? 'active' : null;
            };
            ?>
            <div class="leader-os-table table-responsive">
                <table class="table table-hover align-items-center mb-0">
                    <thead>
                    <tr>
                        <th><?= l('admin_leader_operating_system.table.collaborator') ?></th>
                        <th><?= l('admin_leader_operating_system.table.status') ?></th>
                        <th><a href="<?= $get_sort_url('leader_os') ?>" class="leader-os-sort-link <?= $get_sort_class('leader_os', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.score') ?></a></th>
                        <th><a href="<?= $get_sort_url('shop_clicks') ?>" class="leader-os-sort-link <?= $get_sort_class('shop_clicks', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.shop_clicks') ?></a></th>
                        <th><a href="<?= $get_sort_url('growth') ?>" class="leader-os-sort-link <?= $get_sort_class('growth', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.growth') ?></a></th>
                        <th><a href="<?= $get_sort_url('registrations') ?>" class="leader-os-sort-link <?= $get_sort_class('registrations', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.registrations') ?></a></th>
                        <th><a href="<?= $get_sort_url('country') ?>" class="leader-os-sort-link <?= $get_sort_class('country', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.country') ?></a></th>
                        <th><a href="<?= $get_sort_url('source') ?>" class="leader-os-sort-link <?= $get_sort_class('source', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.source') ?></a></th>
                        <th><a href="<?= $get_sort_url('last_click') ?>" class="leader-os-sort-link <?= $get_sort_class('last_click', $data->selected_sort) ?>"><?= l('admin_leader_operating_system.table.last_click') ?></a></th>
                        <th><?= l('admin_leader_operating_system.table.actions') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($data->overview['rows'] as $row): ?>
                        <?php $growth_percent = $row['growth_percent']; ?>
                        <?php $growth_class = $growth_percent === null ? '' : ($growth_percent >= 0 ? 'leader-os-growth-positive' : 'leader-os-growth-negative'); ?>
                        <tr>
                            <td>
                                <div class="d-flex flex-column">
                                    <div class="font-weight-bold"><?= $row['name'] ?></div>
                                    <div class="text-muted small"><?= $row['email'] ?></div>
                                    <div class="text-muted small"><?= l('admin_index.biolink_qualified_watch.forever_id') ?>: <?= $row['forever_id'] ?></div>
                                    <!-- Custom code: FC-2026-03-31: LOS overview AI usage badges -->
                                    <div class="leader-os-ai-usage">
                                        <span class="leader-os-status-badge leader-os-ai-usage-main <?= $row['ai_usage_stage_class'] ?>"><?= htmlspecialchars((string) ($row['ai_usage_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="leader-os-status-badge leader-os-anomaly-badge <?= $row['anomaly_stage_class'] ?>"><?= htmlspecialchars((string) ($row['anomaly_stage_label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php foreach(($row['ai_usage_badges'] ?? []) as $ai_usage_badge): ?>
                                            <span class="leader-os-status-badge leader-os-ai-usage-badge <?= $ai_usage_badge['class'] ?>"><?= htmlspecialchars((string) ($ai_usage_badge['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endforeach ?>
                                    </div>
                                    <!-- /Custom code: FC-2026-03-31 -->
                                </div>
                            </td>
                            <td>
                                <div class="mb-1"><span class="leader-os-status-badge status-<?= $row['status_class'] ?>"><?= $row['status_label'] ?></span></div>
                                <?php if($row['qualified']): ?>
                                    <div class="text-muted small"><?= l('admin_leader_operating_system.qualified_hint') ?></div>
                                <?php endif ?>
                            </td>
                            <td>
                                <div class="font-weight-bold"><?= $row['leader_os_score'] ?></div>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.table.risk_score') ?>: <?= $row['risk_score'] ?></div>
                            </td>
                            <td>
                                <div class="font-weight-bold"><?= nr($row['forever_shop_clicks_period']) ?></div>
                                <div class="text-muted small">90d: <?= nr($row['forever_shop_clicks_90d']) ?></div>
                            </td>
                            <td>
                                <div class="font-weight-bold <?= $growth_class ?>">
                                    <?= $growth_percent === null ? l('admin_index.biolink_qualified_watch.growth_new') : ($growth_percent > 0 ? '+' : '') . nr($growth_percent) . '%' ?>
                                </div>
                                <div class="text-muted small"><?= l('admin_leader_operating_system.table.delta') ?>: <?= ($row['growth_difference'] > 0 ? '+' : '') . nr($row['growth_difference']) ?></div>
                            </td>
                            <td>
                                <div class="font-weight-bold"><?= nr($row['forever_registration_clicks_period']) ?></div>
                                <div class="text-muted small"><?= nr($row['registration_rate_percent']) ?>%</div>
                            </td>
                            <td><?= $row['strongest_country'] ?></td>
                            <td><?= $row['top_source_label'] ?></td>
                            <td><?= $row['last_click_at'] ? \Altum\Date::get($row['last_click_at'], 2) : '-' ?></td>
                            <td>
                                <div class="d-flex flex-column">
                                    <a href="<?= $row['detail_url'] ?>" class="leader-os-link"><?= l('admin_leader_operating_system.open_detail_demo') ?></a>
                                    <a href="<?= $row['admin_user_url'] ?>" class="leader-os-link text-muted"><?= l('admin_index.biolink_qualified_watch.open_profile') ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>

            <?php if(($data->overview['pagination']['total_pages'] ?? 1) > 1): ?>
                <?php
                $base_query = [
                    'period' => $data->selected_period,
                    'search' => $data->search_query,
                    'status' => $data->selected_status,
                    'ai_status' => $data->selected_ai_status,
                    'anomaly_status' => $data->selected_anomaly_status,
                    'sort' => $data->selected_sort,
                ];
                $previous_page = max(1, $data->overview['pagination']['page'] - 1);
                $next_page = min($data->overview['pagination']['total_pages'], $data->overview['pagination']['page'] + 1);
                $current_page = (int) $data->overview['pagination']['page'];
                $total_pages = (int) $data->overview['pagination']['total_pages'];
                $page_start = max(1, $current_page - 2);
                $page_end = min($total_pages, $current_page + 2);

                if($page_end - $page_start < 4) {
                    if($page_start === 1) {
                        $page_end = min($total_pages, $page_start + 4);
                    }

                    if($page_end === $total_pages) {
                        $page_start = max(1, $page_end - 4);
                    }
                }
                ?>
                <!-- Custom code: FC-2026-03-31: Leader OS numeric pagination -->
                <div class="leader-os-pagination">
                    <div class="text-muted small">
                        <?= sprintf(l('admin_leader_operating_system.pagination_summary'), nr($data->overview['pagination']['page']), nr($data->overview['pagination']['total_pages'])) ?>
                    </div>

                    <div class="leader-os-pagination-links">
                        <?php if($current_page <= 1): ?>
                            <span class="btn btn-outline-light btn-sm disabled"><?= l('admin_leader_operating_system.pagination_previous') ?></span>
                        <?php else: ?>
                            <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => $previous_page]))) ?>" class="btn btn-outline-light btn-sm">
                                <?= l('admin_leader_operating_system.pagination_previous') ?>
                            </a>
                        <?php endif ?>

                        <?php if($page_start > 1): ?>
                            <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => 1]))) ?>" class="btn btn-outline-light btn-sm leader-os-page-link">1</a>
                            <?php if($page_start > 2): ?>
                                <span class="btn btn-outline-light btn-sm disabled">...</span>
                            <?php endif ?>
                        <?php endif ?>

                        <?php for($page_number = $page_start; $page_number <= $page_end; $page_number++): ?>
                            <?php if($page_number === $current_page): ?>
                                <span class="btn btn-outline-light btn-sm leader-os-page-link active"><?= nr($page_number) ?></span>
                            <?php else: ?>
                                <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => $page_number]))) ?>" class="btn btn-outline-light btn-sm leader-os-page-link"><?= nr($page_number) ?></a>
                            <?php endif ?>
                        <?php endfor ?>

                        <?php if($page_end < $total_pages): ?>
                            <?php if($page_end < $total_pages - 1): ?>
                                <span class="btn btn-outline-light btn-sm disabled">...</span>
                            <?php endif ?>
                            <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => $total_pages]))) ?>" class="btn btn-outline-light btn-sm leader-os-page-link"><?= nr($total_pages) ?></a>
                        <?php endif ?>

                        <?php if($current_page >= $total_pages): ?>
                            <span class="btn btn-outline-light btn-sm disabled"><?= l('admin_leader_operating_system.pagination_next') ?></span>
                        <?php else: ?>
                            <a href="<?= url('admin/leader-operating-system?' . http_build_query(array_merge($base_query, ['page' => $next_page]))) ?>" class="btn btn-outline-light btn-sm">
                                <?= l('admin_leader_operating_system.pagination_next') ?>
                            </a>
                        <?php endif ?>
                    </div>
                </div>
                <!-- /Custom code: FC-2026-03-31 -->
            <?php endif ?>
        <?php endif ?>
    </div>
</div>

<div class="card leader-os-shell">
    <div class="card-body">
        <div class="text-uppercase small text-muted mb-2"><?= l('admin_leader_operating_system.section_next') ?></div>
        <div class="row">
            <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.alert_group_manual_follow_up') ?></div>
                    <div class="h2 mb-2"><?= nr((int) ($data->overview['alerts']['totals']['manual_follow_up'] ?? 0)) ?></div>
                    <div><?= l('admin_leader_operating_system.placeholder_next_1') ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-4 mb-3 mb-lg-0">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.alert_group_weekly_signal') ?></div>
                    <div class="h2 mb-2"><?= nr((int) ($data->overview['alerts']['totals']['weekly_signal_gaps'] ?? 0)) ?></div>
                    <div><?= l('admin_leader_operating_system.placeholder_next_2') ?></div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="leader-os-panel h-100">
                    <div class="text-muted small mb-1"><?= l('admin_leader_operating_system.alert_group_execution_risk') ?></div>
                    <div class="h2 mb-2"><?= nr((int) ($data->overview['alerts']['totals']['execution_or_risk'] ?? 0)) ?></div>
                    <div><?= l('admin_leader_operating_system.placeholder_next_3') ?></div>
                </div>
            </div>
        </div>

        <div class="leader-os-panel mt-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                <div>
                    <div class="font-weight-bold mb-1"><?= l('admin_leader_operating_system.alert_title') ?></div>
                    <div class="text-muted small"><?= l('admin_leader_operating_system.alert_text') ?></div>
                </div>
            </div>

            <?php if(empty($data->overview['alerts']['rows'])): ?>
                <div class="text-muted small mb-0"><?= l('admin_leader_operating_system.alert_empty') ?></div>
            <?php else: ?>
                <div class="leader-os-alert-list">
                    <?php foreach($data->overview['alerts']['rows'] as $alert_row): ?>
                        <div class="leader-os-alert-item">
                            <div>
                                <div class="font-weight-bold"><?= htmlspecialchars((string) ($alert_row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="text-muted small"><?= htmlspecialchars((string) ($alert_row['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <a href="<?= $alert_row['detail_url'] ?>" class="leader-os-link"><?= l('admin_leader_operating_system.queue_open') ?></a>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-31 -->