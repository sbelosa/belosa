<?php defined('ALTUMCODE') || die() ?>
<?php
$analytics = $data->analytics;
$kpis = $analytics['kpis'] ?? [];
$global = $analytics['global'] ?? [];
$data_quality = $analytics['data_quality'] ?? [];
$email_queue = $analytics['email_queue'] ?? [];
$charts = $analytics['charts'] ?? [];
$format_cc = static fn($value): string => number_format((float) $value, 3, ',', '.');
$format_number = static fn($value): string => number_format((float) $value, 0, ',', '.');
$format_date = static function($value): string {
    if(!$value) return '—';
    try {
        return (new DateTimeImmutable(substr((string) $value, 0, 10)))->format('d.m.Y.');
    } catch(Throwable $exception) {
        return '—';
    }
};
$format_period = static function($value): string {
    if(!$value) return '—';
    try {
        return (new DateTimeImmutable((string) $value))->format('m/Y');
    } catch(Throwable $exception) {
        return '—';
    }
};
$comparison = static function(array $metric, bool $is_total = false): string {
    $current = $is_total ? (float) ($metric['total'] ?? 0) : (float) ($metric['current'] ?? 0);
    $change = (float) ($metric['change'] ?? 0);
    $prefix = $change > 0 ? '+' : '';
    return sprintf(
        l('admin_leader_operating_system.forever.kpi_comparison'),
        number_format($current, 0, ',', '.'),
        $prefix . number_format($change, 0, ',', '.')
    );
};
$stall_labels = [
    'needs_help' => l('admin_leader_operating_system.forever.needs_help'),
    'no_start_3d' => l('admin_leader_operating_system.forever.no_start_3d'),
    'inactive_7d' => l('admin_leader_operating_system.forever.inactive_7d'),
];
$qualification_source_labels = [
    'downline' => l('admin_leader_operating_system.forever.qualification.downline'),
    'four_cc_active' => l('admin_leader_operating_system.forever.qualification.four_cc_active'),
    'member_cc' => l('admin_leader_operating_system.forever.qualification.member_cc'),
    'legacy_august_backfill' => l('admin_leader_operating_system.forever.qualification.legacy'),
];
$result_type_keys = ['contact', 'conversation', 'invitation', 'follow_up', 'customer_checkin', 'recommendation', 'order', 'new_partner', 'content', 'planning', 'training', 'coaching', 'onboarding', 'event', 'no_response', 'other'];
$result_type_labels = [];
foreach($result_type_keys as $result_type_key) {
    $result_type_labels[$result_type_key] = l('admin_leader_operating_system.forever.result_type.' . $result_type_key);
}
$difficulty_labels = [
    'easy' => l('admin_leader_operating_system.forever.difficulty.easy'),
    'normal' => l('admin_leader_operating_system.forever.difficulty.normal'),
    'hard' => l('admin_leader_operating_system.forever.difficulty.hard'),
    'unspecified' => 'Nije evidentirano',
];
$completion_mode_labels = [
    'standard' => 'Puni korak',
    'quick' => 'Brzi korak',
    'unspecified' => 'Nije evidentirano',
];
$outcome_dimension_labels = [
    'result_type' => $result_type_labels,
    'core' => [
        'Recruitment' => 'Recruitment',
        'Retention' => 'Retention',
        'Productivity' => 'Productivity',
        'Development' => 'Development',
    ],
    'track' => [
        'starter' => l('admin_leader_operating_system.forever.track.starter'),
        'activator' => l('admin_leader_operating_system.forever.track.activator'),
        'builder' => l('admin_leader_operating_system.forever.track.builder'),
        'leader' => l('admin_leader_operating_system.forever.track.leader'),
        'reactivation' => l('admin_leader_operating_system.forever.track.reactivation'),
        'other' => l('admin_leader_operating_system.forever.track.other'),
    ],
    'difficulty' => $difficulty_labels,
    'completion_mode' => $completion_mode_labels,
];
$outcome_dimension_titles = [
    'result_type' => l('admin_leader_operating_system.forever.outcomes.result_type'),
    'core' => l('admin_leader_operating_system.forever.outcomes.core'),
    'track' => l('admin_leader_operating_system.forever.outcomes.track'),
    'difficulty' => 'Zahtjevnost dovršenih koraka',
    'completion_mode' => 'Način dovršavanja',
];
$global_metric_label = !empty($global['is_official_snapshot'])
    ? l('admin_leader_operating_system.forever.global_metric_official')
    : l('admin_leader_operating_system.forever.global_metric_fallback');
?>

<style>
    .los-forever-kpi { border: 0; box-shadow: 0 .3rem 1.5rem rgba(26, 37, 57, .07); }
    .los-forever-kpi .kpi-label { color: var(--gray-600); font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
    .los-forever-kpi .kpi-value { font-size: 1.8rem; font-weight: 700; line-height: 1.2; }
    .los-forever-chart { min-height: 290px; position: relative; }
    .los-forever-funnel { display: grid; gap: .65rem; grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .los-forever-funnel-step { background: var(--gray-100); border-radius: .75rem; padding: .85rem; text-align: center; }
    .los-forever-funnel-value { font-size: 1.45rem; font-weight: 700; }
    .los-forever-member-table th { white-space: nowrap; }
    .los-forever-member-table td { vertical-align: middle; }
    @media (max-width: 991px) { .los-forever-funnel { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (max-width: 575px) { .los-forever-funnel { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
</style>

<div class="container-fluid">
    <?= include_view(THEME_PATH . 'views/admin/leader-operating-system/partials/section_nav.php', ['active' => 'forever']) ?>
    <?= \Altum\Alerts::output_alerts() ?>

    <div class="d-flex flex-column flex-xl-row justify-content-between align-items-xl-center mb-4">
        <div>
            <h1 class="h3 mb-1"><i class="fas fa-fw fa-seedling text-primary mr-2"></i><?= l('admin_leader_operating_system.forever.title') ?></h1>
            <p class="text-muted mb-0"><?= l('admin_leader_operating_system.forever.subtitle') ?></p>
        </div>
        <div class="d-flex flex-column flex-sm-row align-items-sm-center mt-3 mt-xl-0">
            <form method="get" action="<?= url('admin/leader-operating-system-forever') ?>" class="form-inline mb-2 mb-sm-0 mr-sm-2">
                <label class="sr-only" for="los_forever_period"><?= l('admin_leader_operating_system.forever.period') ?></label>
                <select id="los_forever_period" name="period" class="custom-select custom-select-sm mr-2" onchange="this.form.submit()">
                    <?php foreach($analytics['periods'] ?? [] as $period): ?>
                        <option value="<?= htmlspecialchars($period) ?>" <?= $period === ($analytics['period'] ?? '') ? 'selected="selected"' : null ?>><?= htmlspecialchars(date('m/Y', strtotime($period))) ?></option>
                    <?php endforeach ?>
                </select>
                <label class="sr-only" for="los_forever_window"><?= l('admin_leader_operating_system.forever.window') ?></label>
                <select id="los_forever_window" name="window" class="custom-select custom-select-sm" onchange="this.form.submit()">
                    <?php foreach($data->window_options as $window): ?>
                        <option value="<?= (int) $window ?>" <?= (int) $window === (int) ($analytics['window_days'] ?? 30) ? 'selected="selected"' : null ?>><?= sprintf(l('admin_leader_operating_system.forever.days'), (int) $window) ?></option>
                    <?php endforeach ?>
                </select>
            </form>
            <a href="<?= url('admin/forever-business') ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-fw fa-file-import mr-1"></i><?= l('admin_leader_operating_system.forever.sync') ?>
            </a>
        </div>
    </div>

    <?php foreach($analytics['warnings'] ?? [] as $warning):
        $warning_key = is_array($warning) ? (string) ($warning['key'] ?? '') : '';
        $warning_params = is_array($warning) ? (array) ($warning['params'] ?? []) : [];
        $warning_text = $warning_key !== ''
            ? vsprintf(l('admin_leader_operating_system.forever.warning.' . $warning_key), $warning_params)
            : (string) $warning;
    ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i><?= htmlspecialchars($warning_text) ?></div>
    <?php endforeach ?>

    <?php if(empty($kpis)): ?>
        <div class="alert alert-info"><?= l('admin_leader_operating_system.forever.data_unavailable') ?></div>
</div>
<?php return; endif ?>

    <div class="row">
        <?php
        $kpi_cards = [
            ['qualified', l('admin_leader_operating_system.forever.kpi.qualified'), 'fa-check-circle', false],
            ['enrolled', l('admin_leader_operating_system.forever.kpi.enrolled'), 'fa-user-plus', true],
            ['started', l('admin_leader_operating_system.forever.kpi.started'), 'fa-play-circle', true],
            ['completed', l('admin_leader_operating_system.forever.kpi.completed'), 'fa-flag-checkered', true],
            ['active', l('admin_leader_operating_system.forever.kpi.active'), 'fa-bolt', false],
            ['tasks', l('admin_leader_operating_system.forever.kpi.tasks'), 'fa-tasks', false],
            ['standard_tasks', 'Puni koraci', 'fa-check-double', false],
            ['quick_tasks', 'Brzi koraci', 'fa-stopwatch', false],
            ['hard_tasks', 'Označeno kao teško', 'fa-mountain', false],
            ['open_help', 'Otvoreni zahtjevi za pomoć', 'fa-hands-helping', false],
            ['official_four_cc', l('admin_leader_operating_system.forever.kpi.official_four_cc'), 'fa-shield-alt', false],
            ['effective_four_cc', l('admin_leader_operating_system.forever.kpi.effective_four_cc'), 'fa-calculator', false],
        ];
        ?>
        <?php foreach($kpi_cards as [$key, $label, $icon, $is_total]): $metric = $kpis[$key] ?? []; ?>
            <div class="col-sm-6 col-xl-3 mb-4">
                <div class="card los-forever-kpi h-100"><div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="kpi-label"><?= $label ?></div><i class="fas <?= $icon ?> text-primary"></i>
                    </div>
                    <div class="kpi-value mt-2"><?= $format_number($is_total ? ($metric['total'] ?? 0) : ($metric['current'] ?? 0)) ?></div>
                    <div class="small text-muted"><?= $key === 'open_help' ? 'Trenutačno otvoreno; nije rang-lista.' : htmlspecialchars($comparison($metric, $is_total)) ?></div>
                </div></div>
            </div>
        <?php endforeach ?>
    </div>
    <div class="small text-muted mb-4"><?= l('admin_leader_operating_system.forever.comparison_note') ?></div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="h5 mb-0"><?= l('admin_leader_operating_system.forever.global_title') ?></h2>
            <div>
                <span class="badge badge-<?= !empty($global['is_closed']) ? 'success' : 'warning' ?>"><?= !empty($global['is_closed']) ? l('admin_leader_operating_system.forever.closed') : l('admin_leader_operating_system.forever.open') ?></span>
                <span class="badge badge-<?= !empty($global['is_official_snapshot']) ? 'primary' : 'secondary' ?> ml-1"><?= !empty($global['is_official_snapshot']) ? l('admin_leader_operating_system.forever.official_source') : l('admin_leader_operating_system.forever.estimated_source') ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 mb-3 mb-md-0"><div class="small text-muted"><?= $global_metric_label ?></div><div class="h3 mb-0"><?= $format_cc($global['total_cc'] ?? 0) ?></div><div class="small text-muted"><?php if(($global['change_percent'] ?? null) !== null): ?><?= ((float) $global['change_percent'] >= 0 ? '+' : '') . number_format((float) $global['change_percent'], 1, ',', '.') ?>% <?= l('admin_leader_operating_system.forever.vs_previous_month') ?><?php else: ?><?= l('admin_leader_operating_system.forever.no_previous_month') ?><?php endif ?></div></div>
                <div class="col-md-3 mb-3 mb-md-0"><div class="small text-muted"><?= l('admin_leader_operating_system.forever.goal_gap') ?></div><div class="h3 mb-0"><?= $format_cc($global['gap_cc'] ?? 1000) ?></div></div>
                <div class="col-md-3 mb-3 mb-md-0"><div class="small text-muted"><?= sprintf(l('admin_leader_operating_system.forever.six_month_average'), (int) ($global['closed_sample_count'] ?? 0)) ?></div><div class="h3 mb-0"><?= $format_cc($global['closed_six_average_cc'] ?? 0) ?></div></div>
                <div class="col-md-3"><div class="small text-muted"><?= l('admin_leader_operating_system.forever.multiplier') ?></div><div class="h3 mb-0"><?= isset($global['multiplier_to_goal']) ? number_format((float) $global['multiplier_to_goal'], 2, ',', '.') . '×' : '—' ?></div></div>
            </div>
            <div class="progress mt-3" style="height: .65rem"><div class="progress-bar" role="progressbar" style="width: <?= min(100, max(0, (float) ($global['progress_percent'] ?? 0))) ?>%" aria-valuenow="<?= (float) ($global['progress_percent'] ?? 0) ?>" aria-valuemin="0" aria-valuemax="100"></div></div>
            <div class="small text-muted mt-2"><?php if(!empty($global['period'])): ?><?= l('admin_leader_operating_system.forever.data_period') ?> <?= $format_period($global['period']) ?> · <?php endif ?><?= htmlspecialchars(!empty($global['source_key']) ? l('admin_leader_operating_system.forever.source.' . $global['source_key']) : (string) ($global['source'] ?? '')) ?></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h2 class="h5 mb-0"><?= l('admin_leader_operating_system.forever.linkage_title') ?></h2></div>
        <div class="card-body">
            <div class="los-forever-funnel">
                <?php foreach($analytics['linkage_funnel'] ?? [] as $step): ?>
                    <div class="los-forever-funnel-step">
                        <div class="small text-uppercase text-muted"><?= l('admin_leader_operating_system.forever.funnel.' . $step['key']) ?></div>
                        <div class="los-forever-funnel-value"><?= $format_number($step['value']) ?></div>
                    </div>
                <?php endforeach ?>
            </div>
            <div class="small text-muted mt-3"><?= l('admin_leader_operating_system.forever.linkage_note') ?></div>
            <div class="row mt-3">
                <div class="col-md-3 mb-2 mb-md-0"><div class="alert alert-light mb-0 py-2"><strong><?= $format_number($data_quality['missing_linkage'] ?? 0) ?></strong> <?= l('admin_leader_operating_system.forever.missing_linkage') ?></div></div>
                <div class="col-md-3 mb-2 mb-md-0"><div class="alert alert-info mb-0 py-2"><strong><?= $format_number($data_quality['shared_fbo_ids'] ?? 0) ?></strong> Forever ID-a zajednički koristi <strong><?= $format_number($data_quality['shared_accounts'] ?? 0) ?></strong> aktivnih FCC računa; to je dopušteno i svaki račun ima vlastiti napredak.</div></div>
                <div class="col-md-3 mb-2 mb-md-0"><div class="alert alert-light mb-0 py-2"><strong><?= $format_number($data_quality['enrolled_without_active_linkage'] ?? 0) ?></strong> <?= l('admin_leader_operating_system.forever.enrolled_linkage_issue') ?></div></div>
                <div class="col-md-3"><div class="alert alert-light mb-0 py-2"><strong><?= $format_number($data_quality['enrolled_outside_current_structure'] ?? 0) ?></strong> <?= l('admin_leader_operating_system.forever.enrolled_outside_structure') ?></div></div>
            </div>
            <?php if(!empty($data_quality['started_without_enrollment'])): ?>
                <div class="alert alert-warning mt-3 mb-0"><strong><?= $format_number($data_quality['started_without_enrollment']) ?></strong> <?= l('admin_leader_operating_system.forever.started_without_enrollment') ?></div>
            <?php endif ?>
            <?php if(!empty($data_quality['outcome_fbo_mismatch_accounts'])): ?>
                <div class="alert alert-warning mt-3 mb-0"><strong><?= $format_number($data_quality['outcome_fbo_mismatch_accounts']) ?></strong> FCC računa ima povijesni VIP zapis pod drugim Forever ID-em od trenutačno povezanog. Napredak ostaje vezan uz račun, ali vezu treba administrativno provjeriti.</div>
            <?php endif ?>
        </div>
    </div>

    <?php if(!empty($email_queue)): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">VIP e-mail red slanja</h2>
                <span class="badge badge-secondary">Ukupno <?= $format_number($email_queue['total'] ?? 0) ?></span>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 col-md mb-3 mb-md-0"><div class="small text-muted">Čeka</div><div class="h4 mb-0"><?= $format_number($email_queue['pending'] ?? 0) ?></div></div>
                    <div class="col-6 col-md mb-3 mb-md-0"><div class="small text-muted">Šalje se</div><div class="h4 mb-0"><?= $format_number($email_queue['sending'] ?? 0) ?></div></div>
                    <div class="col-6 col-md mb-3 mb-md-0"><div class="small text-muted">Ponovni pokušaj</div><div class="h4 mb-0"><?= $format_number($email_queue['retryable_failed'] ?? 0) ?></div></div>
                    <div class="col-6 col-md mb-3 mb-md-0"><div class="small text-muted">Iscrpljeni pokušaji</div><div class="h4 mb-0 text-danger"><?= $format_number($email_queue['exhausted_failed'] ?? 0) ?></div></div>
                    <div class="col-12 col-md"><div class="small text-muted">Transport prihvatio</div><div class="h4 mb-0 text-success"><?= $format_number($email_queue['accepted'] ?? 0) ?></div></div>
                </div>
                <div class="alert alert-light small mt-3 mb-0"><strong>Važno:</strong> status “transport prihvatio” znači da je pružatelj prihvatio poruku za slanje. To nije potvrda dostave u inbox, otvaranja ni čitanja. “Iscrpljeni pokušaji” su trajno neuspjeli zapisi nakon pet pokušaja i traže provjeru.</div>
            </div>
        </div>
    <?php endif ?>

    <div class="row">
        <div class="col-xl-7 mb-4"><div class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0">Dnevno dovršeni koraci · puni i brzi</h2></div>
            <div class="card-body los-forever-chart"><canvas id="los_forever_daily_chart"></canvas></div>
        </div></div>
        <div class="col-xl-5 mb-4"><div class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0"><?= !empty($global['is_official_snapshot']) ? l('admin_leader_operating_system.forever.cc_chart') : l('admin_leader_operating_system.forever.cc_chart_fallback') ?></h2></div>
            <div class="card-body los-forever-chart"><?php if(empty($charts['cc']['labels'])): ?><div class="text-muted small"><?= l('admin_leader_operating_system.forever.no_data') ?></div><?php else: ?><canvas id="los_forever_cc_chart"></canvas><?php endif ?></div>
        </div></div>
    </div>

    <div class="row">
        <?php foreach(['result_type', 'core', 'track', 'difficulty', 'completion_mode'] as $dimension): ?>
            <div class="col-md-6 col-xl mb-4"><div class="card h-100">
                <div class="card-header"><h2 class="h6 mb-0"><?= htmlspecialchars($outcome_dimension_titles[$dimension] ?? $dimension) ?></h2></div>
                <div class="card-body">
                    <?php if(empty($charts['outcomes'][$dimension])): ?><div class="text-muted small"><?= l('admin_leader_operating_system.forever.no_data') ?></div><?php endif ?>
                    <?php foreach($charts['outcomes'][$dimension] ?? [] as $row): ?>
                        <div class="d-flex justify-content-between border-bottom py-2"><span class="text-break"><?= htmlspecialchars($outcome_dimension_labels[$dimension][$row['key']] ?? $row['key']) ?></span><strong><?= $format_number($row['value']) ?></strong></div>
                    <?php endforeach ?>
                </div>
            </div></div>
        <?php endforeach ?>
    </div>

    <div class="row">
        <div class="col-xl-4 mb-4"><div class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0"><?= l('admin_leader_operating_system.forever.top_performers') ?></h2></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?= l('admin_leader_operating_system.forever.member') ?></th><th><?= l('admin_leader_operating_system.forever.tasks') ?></th><th>Puni</th><th>Brzi</th></tr></thead><tbody>
                <?php if(empty($analytics['top_performers'])): ?><tr><td colspan="4" class="text-muted"><?= l('admin_leader_operating_system.forever.no_data') ?></td></tr><?php endif ?>
                <?php foreach($analytics['top_performers'] ?? [] as $member): ?><tr><td><?= htmlspecialchars($member['name']) ?><div class="small text-muted"><?= htmlspecialchars($member['title']) ?></div><?php if($member['result_type']): ?><div class="small text-muted"><?= l('admin_leader_operating_system.forever.latest_result_type') ?> <?= htmlspecialchars($outcome_dimension_labels['result_type'][$member['result_type']] ?? $member['result_type']) ?></div><?php endif ?></td><td><?= $format_number($member['tasks']) ?></td><td><?= $format_number($member['standard_tasks']) ?></td><td><?= $format_number($member['quick_tasks']) ?></td></tr><?php endforeach ?>
            </tbody></table></div>
            <div class="card-footer small text-muted"><?= l('admin_leader_operating_system.forever.results_not_cc') ?></div>
        </div></div>
        <div class="col-xl-4 mb-4"><div class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0">Način dovršavanja po polazniku</h2></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?= l('admin_leader_operating_system.forever.member') ?></th><th>Puni</th><th>Brzi</th><th>Teško</th></tr></thead><tbody>
                <?php if(empty($analytics['top_results'])): ?><tr><td colspan="4" class="text-muted"><?= l('admin_leader_operating_system.forever.no_data') ?></td></tr><?php endif ?>
                <?php foreach($analytics['top_results'] ?? [] as $member): ?><tr><td><?= htmlspecialchars($member['name']) ?><div class="small text-muted"><?= $format_number($member['tasks']) ?> ukupno</div></td><td><?= $format_number($member['standard_tasks']) ?></td><td><?= $format_number($member['quick_tasks']) ?></td><td><?= $format_number($member['hard_tasks']) ?></td></tr><?php endforeach ?>
            </tbody></table></div>
            <div class="card-footer small text-muted">Ovdje se uspoređuju dovršeni koraci. Sirove količine kontakata, poruka, objava i treninga namjerno se ne rangiraju zajedno.</div>
        </div></div>
        <div class="col-xl-4 mb-4"><div class="card h-100">
            <div class="card-header"><h2 class="h5 mb-0"><?= l('admin_leader_operating_system.forever.attention_queue') ?></h2></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?= l('admin_leader_operating_system.forever.member') ?></th><th><?= l('admin_leader_operating_system.forever.signal') ?></th><th><?= l('admin_leader_operating_system.forever.last_task') ?></th></tr></thead><tbody>
                <?php if(empty($analytics['attention_queue'])): ?><tr><td colspan="3" class="text-muted"><?= l('admin_leader_operating_system.forever.no_attention') ?></td></tr><?php endif ?>
                <?php foreach($analytics['attention_queue'] ?? [] as $member): ?><tr><td><?= htmlspecialchars($member['name']) ?><div class="small text-muted"><?= htmlspecialchars($member['fbo_id']) ?><?= !empty($member['user_id']) ? ' · FCC #' . (int) $member['user_id'] : '' ?></div></td><td><span class="badge badge-<?= $member['stall_state'] === 'needs_help' ? 'danger' : 'warning' ?>"><?= htmlspecialchars($stall_labels[$member['stall_state']] ?? $member['stall_state']) ?></span><?php if((int) ($member['open_help_count'] ?? 0) > 1): ?><span class="badge badge-light ml-1"><?= (int) $member['open_help_count'] ?> otvorena</span><?php endif ?><?php if($member['difficulty']): ?><div class="small text-muted mt-1"><?= htmlspecialchars($difficulty_labels[$member['difficulty']] ?? $member['difficulty']) ?></div><?php endif ?><?php if(!empty($member['help_note'])): ?><div class="small border-left border-danger pl-2 mt-2 text-break"><?= nl2br(htmlspecialchars($member['help_note'])) ?></div><?php endif ?></td><td><?= $format_date($member['stall_state'] === 'needs_help' ? ($member['help_requested_at'] ?? null) : $member['last_task_date']) ?></td></tr><?php endforeach ?>
            </tbody></table></div>
            <div class="card-footer small text-muted">Otvorena pomoć dolazi iz zasebnog zahtjeva polaznika. Bilješka je vidljiva samo administratoru, prikazuje se escaped i ne dovršava zadatak.</div>
        </div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <h2 class="h5 mb-2 mb-md-0"><?= l('admin_leader_operating_system.forever.members_title') ?></h2>
            <input type="search" id="los_forever_member_search" class="form-control form-control-sm" style="max-width: 18rem" placeholder="<?= l('admin_leader_operating_system.forever.search') ?>" />
        </div>
        <div class="table-responsive"><table class="table table-sm los-forever-member-table mb-0" id="los_forever_member_table"><thead><tr>
            <th><?= l('admin_leader_operating_system.forever.member') ?></th><th><?= l('admin_leader_operating_system.forever.status') ?></th><th>CC</th><th>4 CC</th><th><?= l('admin_leader_operating_system.forever.tasks') ?></th><th>Puni / brzi</th><th>Zahtjevnost</th><th><?= l('admin_leader_operating_system.forever.last_task_track') ?></th><th><?= l('admin_leader_operating_system.forever.last_task') ?></th>
        </tr></thead><tbody>
            <?php if(empty($analytics['members'])): ?><tr><td colspan="9" class="text-muted"><?= l('admin_leader_operating_system.forever.no_data') ?></td></tr><?php endif ?>
            <?php foreach($analytics['members'] ?? [] as $member): $member_effective_four_cc = forever_business_los_effective_four_cc($member); ?>
                <tr>
                    <td><?= htmlspecialchars($member['name']) ?><div class="small text-muted"><?= htmlspecialchars($member['fbo_id'] . (!empty($member['user_id']) ? ' · FCC #' . (int) $member['user_id'] : '') . ($member['title'] ? ' · ' . $member['title'] : '')) ?></div></td>
                    <td>
                        <?php if($member['stall_state']): ?><span class="badge badge-<?= $member['stall_state'] === 'needs_help' ? 'danger' : 'warning' ?>"><?= htmlspecialchars($stall_labels[$member['stall_state']] ?? $member['stall_state']) ?></span><?php elseif((int) $member['vip_steps_completed'] >= 30): ?><span class="badge badge-primary"><?= l('admin_leader_operating_system.forever.completed') ?></span><?php elseif($member['tasks'] > 0): ?><span class="badge badge-success"><?= l('admin_leader_operating_system.forever.active') ?></span><?php elseif($member['is_enrolled']): ?><span class="badge badge-info"><?= l('admin_leader_operating_system.forever.enrolled') ?></span><?php else: ?><span class="badge badge-light"><?= l('admin_leader_operating_system.forever.not_enrolled') ?></span><?php endif ?>
                        <?php if((int) $member['linked_accounts'] > 1): ?><div class="mt-1"><span class="badge badge-info">Zajednički Forever ID · <?= (int) $member['linked_accounts'] ?> FCC računa</span></div><?php elseif((int) $member['linked_accounts'] === 0): ?><div class="mt-1"><span class="badge badge-danger"><?= l('admin_leader_operating_system.forever.link_missing') ?></span></div><?php endif ?>
                        <?php if(!empty($member['has_outcome_fbo_mismatch'])): ?><div class="mt-1"><span class="badge badge-warning">Povijesni zapis pod drugim ID-em</span></div><?php endif ?>
                        <?php if(empty($member['is_in_current_structure'])): ?><div class="mt-1"><span class="badge badge-secondary"><?= l('admin_leader_operating_system.forever.outside_structure') ?></span></div><?php endif ?>
                        <?php if($member['is_enrolled'] && $member['qualification_source']): ?><div class="small text-muted mt-1"><?= htmlspecialchars($qualification_source_labels[$member['qualification_source']] ?? $member['qualification_source']) ?> · <?= $format_cc($member['qualifying_personal_cc'] ?? 0) ?> CC · <?= $format_period($member['qualifying_period'] ?? null) ?></div><?php endif ?>
                    </td>
                    <td><?= $member['personal_cc'] === null ? '—' : $format_cc($member['personal_cc']) ?></td>
                    <td>
                        <?php if(empty($member['has_complete_activity_verification'])): ?>
                            <span class="badge badge-light"><?= l('admin_leader_operating_system.forever.waiting_data') ?></span>
                        <?php else: ?>
                            <span class="badge badge-<?= $member_effective_four_cc ? 'success' : 'secondary' ?>"><?= $member_effective_four_cc ? l('global.yes') : l('global.no') ?></span>
                            <?php if($member['is_4cc_active'] === null): ?><div class="small text-muted mt-1"><?= $member['personal_cc'] !== null && $member['total_active_cc'] !== null ? l('admin_leader_operating_system.forever.formula') : l('admin_leader_operating_system.forever.incomplete_activity_data') ?></div><?php endif ?>
                        <?php endif ?>
                    </td>
                    <td><?= $format_number($member['tasks']) ?></td><td><?= $format_number($member['standard_tasks']) ?> / <?= $format_number($member['quick_tasks']) ?></td><td><?= $member['difficulty'] ? htmlspecialchars($difficulty_labels[$member['difficulty']] ?? $member['difficulty']) : '—' ?><?= !empty($member['needs_help']) ? '<div class="small text-danger">Otvorena pomoć</div>' : '' ?></td><td><?= htmlspecialchars($outcome_dimension_labels['track'][$member['track']] ?? ($member['track'] ?: '—')) ?></td><td><?= $format_date($member['last_task_date']) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody></table></div>
        <div class="card-footer small text-muted"><?= l('admin_leader_operating_system.forever.privacy_note') ?></div>
    </div>
</div>

<?php require THEME_PATH . 'views/partials/js_chart_defaults.php' ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const chartPayload = <?= json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if(typeof Chart !== 'undefined') {
        const dailyCanvas = document.getElementById('los_forever_daily_chart');
        if(dailyCanvas) new Chart(dailyCanvas, {
            type: 'line',
            data: { labels: chartPayload.daily.labels || [], datasets: [
                { label: <?= json_encode(l('admin_leader_operating_system.forever.tasks')) ?>, data: chartPayload.daily.tasks || [], borderColor: '#5b6df8', backgroundColor: 'rgba(91,109,248,.12)', fill: true },
                { label: 'Puni koraci', data: chartPayload.daily.standard || [], borderColor: '#16a085', backgroundColor: 'rgba(22,160,133,.08)', fill: false },
                { label: 'Brzi koraci', data: chartPayload.daily.quick || [], borderColor: '#f39c12', backgroundColor: 'rgba(243,156,18,.08)', fill: false }
            ]}, options: {...chart_options, plugins: {...chart_options.plugins, legend: {display: true}}}
        });
        const ccCanvas = document.getElementById('los_forever_cc_chart');
        if(ccCanvas) new Chart(ccCanvas, {
            type: 'bar',
            data: { labels: chartPayload.cc.labels || [], datasets: [
                { label: <?= json_encode($global_metric_label, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, data: chartPayload.cc.values || [], backgroundColor: '#5b6df8' },
                { type: 'line', label: <?= json_encode(l('admin_leader_operating_system.forever.goal_line'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, data: (chartPayload.cc.labels || []).map(function () { return 1000; }), borderColor: '#f39c12', borderDash: [6, 4], fill: false, pointRadius: 0 }
            ]},
            options: {...chart_options, plugins: {...chart_options.plugins, legend: {display: true}}}
        });
    }

    const search = document.getElementById('los_forever_member_search');
    const table = document.getElementById('los_forever_member_table');
    if(search && table) search.addEventListener('input', function () {
        const needle = this.value.toLocaleLowerCase();
        table.querySelectorAll('tbody tr').forEach(function (row) {
            row.style.display = row.textContent.toLocaleLowerCase().includes(needle) ? '' : 'none';
        });
    });
});
</script>
