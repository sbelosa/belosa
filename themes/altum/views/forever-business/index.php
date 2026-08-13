<?php defined('ALTUMCODE') || die() ?>
<?php
$dashboard = $data->dashboard;
$summary = $dashboard['summary'];
$period_label = !empty($dashboard['period']) ? (new DateTimeImmutable($dashboard['period']))->format('m/Y') : '—';
$format_cc = static fn($value) => number_format((float) $value, 3, ',', '.');
$format_percent = static fn($value) => number_format((float) $value, 1, ',', '.') . '%';
$official_change = static function($current, $previous): ?float {
    if($current === null || $current === '' || $previous === null || $previous === '') return null;
    $previous = (float) $previous;
    if(abs($previous) < .0000001) return abs((float) $current) < .0000001 ? 0.0 : null;
    return round((((float) $current - $previous) / $previous) * 100, 1);
};
$format_change = static function(float $value): string {
    if(abs($value) < .05) return '0,0%';
    return ($value > 0 ? '+' : '−') . number_format(abs($value), 1, ',', '.') . '%';
};
?>

<style>
    .forever-business-page .fb-card { border: 1px solid rgba(127, 127, 127, .17); border-radius: 1rem; }
    .forever-business-page .fb-hero { background: linear-gradient(135deg, rgba(16,31,24,.96), rgba(23,61,42,.94)); color: #fff !important; border: 1px solid rgba(122,225,178,.22); border-radius: 1.25rem; overflow: hidden; }
    .forever-business-page .fb-hero h1, .forever-business-page .fb-hero p, .forever-business-page .fb-hero span, .forever-business-page .fb-hero small, .forever-business-page .fb-hero strong { color: inherit !important; }
    .forever-business-page .fb-hero .progress { height: .75rem; background: rgba(255,255,255,.14); }
    .forever-business-page .fb-hero .progress-bar { background: #f6c900; }
    .forever-business-page .fb-sync { background: rgba(30, 136, 229, .08); border: 1px solid rgba(30, 136, 229, .22); color: inherit; border-radius: .85rem; }
    .forever-business-page .fb-progress-panel { background: rgba(127,127,127,.045); color: inherit; }
    .forever-business-page .fb-progress-panel .progress { height: .8rem; background: rgba(127,127,127,.18); }
    .forever-business-page .fb-progress-panel .progress-bar { background: linear-gradient(90deg, #43a66f, #7ed7a5); }
    .forever-business-page .fb-progress-panel .progress-bar.fb-rank-bar { background: linear-gradient(90deg, #1f78c8, #58b4e7); }
    .forever-business-page .fb-verified-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: .75rem; }
    .forever-business-page .fb-verified-item { padding: .9rem; border: 1px solid rgba(127,127,127,.15); border-radius: .8rem; background: rgba(127,127,127,.035); }
    .forever-business-page .fb-core { border-top: 4px solid #6ca646; }
    .forever-business-page .fb-core-recruitment { border-top-color: #6b4a3b; }
    .forever-business-page .fb-core-retention { border-top-color: #f2c300; }
    .forever-business-page .fb-core-productivity { border-top-color: #6ca646; }
    .forever-business-page .fb-core-development { border-top-color: #1558a6; }
    .forever-business-page .fb-action { background: linear-gradient(135deg, rgba(108,166,70,.13), rgba(246,201,0,.08)); }
    .forever-business-page .fb-table-wrap { max-height: 680px; overflow: auto; }
    .forever-business-page .fb-status-dot { width: .65rem; height: .65rem; border-radius: 50%; display: inline-block; margin-right: .4rem; }
    .forever-business-page .fb-official-current { font-weight: 700; white-space: nowrap; }
    .forever-business-page .fb-official-comparison { display: flex; flex-wrap: wrap; gap: .2rem .55rem; margin-top: .2rem; font-size: .78rem; white-space: nowrap; }
    .forever-business-page .fb-official-delta-up { color: #43b779; font-weight: 700; }
    .forever-business-page .fb-official-delta-down { color: #e06666; font-weight: 700; }
    .forever-business-page .fb-official-delta-flat { color: inherit; opacity: .65; font-weight: 700; }
    .forever-business-page .fb-sort-button { display: inline-flex; align-items: center; gap: .35rem; padding: 0; border: 0; background: transparent; color: inherit; font: inherit; font-weight: 700; text-align: left; cursor: pointer; }
    .forever-business-page .fb-sort-button:hover, .forever-business-page .fb-sort-button:focus { color: #f6c900; outline: none; }
    .forever-business-page .fb-sort-button[aria-pressed="true"] { color: #f6c900; }
    .forever-business-page .fb-sort-icon { min-width: .8rem; font-size: .75rem; opacity: .65; }
</style>

<div class="forever-business-page">
    <?= \Altum\Alerts::output_alerts() ?>

    <div class="fb-hero p-4 p-lg-5 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start">
            <div>
                <div class="text-uppercase small font-weight-bold text-warning mb-2">Forever napredak · <?= htmlspecialchars($period_label) ?></div>
                <h1 class="h2 mb-2"><?= $data->is_admin ? 'Cijela struktura. Jedan mjerljiv cilj.' : 'Tvoja aktivnost, pozicija i sljedeći korak.' ?></h1>
                <p class="mb-0 opacity-75">CC vrijednosti i statusi dolaze iz provjerenog FLP360 importa; FCC ih ne procjenjuje.</p>
            </div>
            <?php if(!empty($dashboard['periods'])): ?>
                <form method="get" class="mt-4 mt-lg-0 d-flex flex-column flex-sm-row">
                    <?php if(!empty($dashboard['access_roots'])): ?>
                        <select name="root" class="form-control mr-sm-2 mb-2 mb-sm-0" aria-label="Tim">
                            <option value="">Svi moji suradnici</option>
                            <?php foreach($dashboard['access_roots'] as $root): ?>
                                <option value="<?= htmlspecialchars($root->fbo_id) ?>" <?= $data->requested_root === (string) $root->fbo_id ? 'selected' : null ?>><?= htmlspecialchars($root->name ?: $root->fbo_id) ?></option>
                            <?php endforeach ?>
                        </select>
                    <?php endif ?>
                    <select name="period" class="form-control mr-sm-2 mb-2 mb-sm-0" aria-label="Mjesec">
                        <?php foreach($dashboard['periods'] as $period): ?>
                            <option value="<?= htmlspecialchars(substr($period, 0, 7)) ?>" <?= $dashboard['period'] === $period ? 'selected' : null ?>><?= htmlspecialchars((new DateTimeImmutable($period))->format('m/Y')) ?></option>
                        <?php endforeach ?>
                    </select>
                    <button class="btn btn-warning">Prikaži</button>
                </form>
            <?php endif ?>
        </div>

        <?php if($data->is_admin): ?>
        <div class="mt-4">
            <div class="d-flex justify-content-between mb-2">
                <span><strong><?= $format_cc($summary['goal_current_cc']) ?> CC</strong> od cilja 1.000 CC</span>
                <span><?= $format_percent($summary['goal_progress']) ?></span>
            </div>
            <div class="progress"><div class="progress-bar" style="width: <?= min(100, (float) $summary['goal_progress']) ?>%"></div></div>
            <small class="d-block mt-2 text-white-50">Do cilja nedostaje <?= $format_cc($summary['goal_gap_cc']) ?> CC. Izvor: <?= htmlspecialchars($summary['goal_metric_source']) ?><?= !$summary['goal_is_closed'] ? ' · mjesec još nije zatvoren' : '' ?>.</small>
            <?php if($summary['closed_6m_average_cc'] > 0): ?><small class="d-block mt-1 text-white-50">Prosjek zadnjih 6 zatvorenih mjeseci: <?= $format_cc($summary['closed_6m_average_cc']) ?> CC. Za cilj je potrebno približno <?= number_format($summary['goal_multiplier_from_average'], 2, ',', '.') ?>× taj prosjek.</small><?php endif ?>
        </div>
        <?php elseif($data->focus_member): ?>
            <?php $verified = $data->focus_member['verified_progress']; $activity_progress = min((float) $verified['personal_progress'], (float) $verified['regional_progress']); ?>
            <div class="mt-4">
                <div class="d-flex justify-content-between mb-2"><span><strong><?= !empty($verified['is_officially_active']) ? '4 CC aktivnost ostvarena' : 'Napredak prema 4 CC aktivnosti' ?></strong></span><span><?= $format_percent($activity_progress) ?></span></div>
                <div class="progress"><div class="progress-bar" style="width: <?= $activity_progress ?>%"></div></div>
                <small class="d-block mt-2 opacity-75">Uvjet: najmanje 1 osobni CC i 4 Total Active CC unutar iste FLP360 regije.</small>
            </div>
        <?php endif ?>
    </div>

    <div class="fb-sync px-3 py-2 mb-4">
        <i class="fas fa-fw fa-sync-alt mr-1"></i>
        <strong>Zadnja uspješna sinkronizacija:</strong>
        <?= !empty($dashboard['last_sync_at']) ? htmlspecialchars((new DateTimeImmutable($dashboard['last_sync_at']))->format('d.m.Y. H:i')) : 'još nije izvršena' ?>.
        <span class="text-muted">Promet nastao nakon tog vremena pojavit će se nakon sljedeće uspješne sinkronizacije.</span>
    </div>

    <?php if(empty($dashboard['members'])): ?>
        <div class="alert alert-info">Još nema povezanih podataka za tvoj Forever ID. Provjeri ID na FCC računu; podaci će se pojaviti nakon sljedećeg uspješnog FLP360 importa u kojem se ID nalazi.</div>
    <?php else: ?>
        <?php if(!$data->is_admin && $data->focus_member && empty($data->focus_member['is_in_current_structure'])): ?>
            <div class="alert alert-info"><strong>Tvoj osobni pregled je aktivan.</strong> Forever ID je povezan s FCC računom, ali bodovi još nisu pronađeni u posljednjem administratorskom izvještaju. Nakon sljedeće sinkronizacije prikaz će se automatski popuniti.</div>
        <?php endif ?>
        <?php if($data->is_admin && !empty($dashboard['official_four_core'])): ?>
            <?php $official = $dashboard['official_four_core']; ?>
            <div class="card fb-card mb-4">
                <div class="card-header bg-transparent"><h2 class="h5 mb-1">Službeni FLP360 4 Core · <?= htmlspecialchars((new DateTimeImmutable($official['period_month']))->format('m/Y')) ?></h2><div class="small text-muted">Glavni broj prikazuje odabrano razdoblje. Manji red prikazuje isti mjesec 2025. i izračunatu promjenu; crveno znači pad, a zeleno rast. “Mjesec” je zadnji prikazani zatvoreni mjesec, a YTD stanje u trenutku snimke.</div></div>
                <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Područje</th><th>Open Business · mjesec</th><th>Open Business · YTD</th><th>Downline · mjesec</th><th>Downline · YTD</th></tr></thead><tbody>
                    <?php foreach(['recruitment' => ['Recruitment', 'broj'], 'retention' => ['Retention', 'broj'], 'productivity' => ['Productivity', 'cc'], 'development' => ['Development', 'postotak']] as $metric_key => $metric_meta): ?>
                        <?php $format_official = static function($value) use ($format_cc, $metric_meta) { if($value === null || $value === '') return '—'; return $metric_meta[1] === 'cc' ? $format_cc($value) . ' CC' : ($metric_meta[1] === 'postotak' ? number_format((float) $value, 2, ',', '.') . '%' : nr($value)); }; ?>
                        <tr>
                            <th><?= htmlspecialchars($metric_meta[0]) ?></th>
                            <?php foreach([['open', 'month'], ['open', 'ytd'], ['downline', 'month'], ['downline', 'ytd']] as $official_cell): ?>
                                <?php
                                [$official_scope, $official_timeframe] = $official_cell;
                                $current_value = $official[$official_scope][$official_timeframe][$metric_key] ?? null;
                                $previous_value = $official['previous'][$official_scope][$official_timeframe][$metric_key] ?? null;
                                $change_value = $official_change($current_value, $previous_value);
                                $change_class = $change_value === null || abs($change_value) < .05 ? 'fb-official-delta-flat' : ($change_value > 0 ? 'fb-official-delta-up' : 'fb-official-delta-down');
                                $change_icon = $change_value === null || abs($change_value) < .05 ? '→' : ($change_value > 0 ? '▲' : '▼');
                                ?>
                                <td>
                                    <div class="fb-official-current"><?= $format_official($current_value) ?></div>
                                    <?php if($previous_value !== null && $previous_value !== ''): ?>
                                        <div class="fb-official-comparison text-muted">
                                            <span><?= htmlspecialchars((new DateTimeImmutable($official['comparison_period']))->format('Y')) ?>: <?= $format_official($previous_value) ?></span>
                                            <?php if($change_value !== null): ?><span class="<?= $change_class ?>"><?= $change_icon ?> <?= $format_change($change_value) ?></span><?php endif ?>
                                        </div>
                                    <?php endif ?>
                                </td>
                            <?php endforeach ?>
                        </tr>
                    <?php endforeach ?>
                </tbody></table></div>
            </div>
        <?php endif ?>

        <?php if(!$data->is_admin && $data->focus_member): ?>
            <?php
            $mine = $data->focus_member;
            $verified = $mine['verified_progress'];
            $rank = $verified['rank'];
            ?>
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <div class="card fb-card fb-progress-panel h-100"><div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div><div class="small text-uppercase text-muted">Mjesečna aktivnost</div><h2 class="h5 mb-0">4 CC aktivnost</h2></div>
                            <span class="badge badge-<?= !empty($verified['is_officially_active']) ? 'success' : 'warning' ?>"><?= !empty($verified['is_officially_active']) ? 'FLP360 potvrđeno' : 'Još nije ostvareno' ?></span>
                        </div>
                        <?php if(!empty($verified['has_activity_data'])): ?>
                            <div class="d-flex justify-content-between small mb-1"><span>Osobni CC · minimum 1</span><strong><?= $format_cc($verified['personal_cc']) ?> / 1,000</strong></div>
                            <div class="progress mb-3"><div class="progress-bar" style="width: <?= $verified['personal_progress'] ?>%"></div></div>
                            <div class="d-flex justify-content-between small mb-1"><span>Total Active CC · ista regija · minimum 4</span><strong><?= $format_cc($verified['total_active_cc']) ?> / 4,000</strong></div>
                            <div class="progress mb-3"><div class="progress-bar" style="width: <?= $verified['regional_progress'] ?>%"></div></div>
                            <div class="small text-muted">FCC koristi službeni FLP360 4CC Active status i ne zbraja aktivnosti iz različitih regija.</div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">Za odabrani mjesec još nema potvrđenih podataka o aktivnosti.</div>
                        <?php endif ?>
                    </div></div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card fb-card fb-progress-panel h-100"><div class="card-body p-4">
                        <div class="small text-uppercase text-muted">Put do sljedeće pozicije</div>
                        <h2 class="h5 mb-1"><?= htmlspecialchars($rank['current_title']) ?> <i class="fas fa-long-arrow-alt-right mx-1 text-muted"></i> <?= htmlspecialchars($rank['next_title']) ?></h2>
                        <div class="small text-muted mb-3"><?= $rank['mode'] === 'manager' ? 'Za managera se mjeri službeni Non-Manager CC u odabranom mjesecu.' : 'Napredak koristi službeni Total CC iz uvezenih kalendarskih mjeseci.' ?></div>
                        <?php foreach($rank['windows'] as $window): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($window['label']) ?> · <?= htmlspecialchars($window['metric']) ?></span><strong><?= $window['complete'] ? $format_cc($window['current']) . ' / ' . $format_cc($window['target']) : 'čeka potpune mjesece' ?></strong></div>
                                <div class="progress"><div class="progress-bar fb-rank-bar" style="width: <?= $window['progress'] ?>%"></div></div>
                                <div class="small mt-1 <?= $window['achieved'] ? 'text-success font-weight-bold' : 'text-muted' ?>"><?= !$window['complete'] ? 'Nedostaje jedan ili više uvoznih mjeseci za siguran izračun.' : ($window['achieved'] ? 'Uvjet je ostvaren prema uvezenim CC podacima.' : 'Nedostaje još ' . $format_cc($window['gap']) . ' CC.') ?></div>
                            </div>
                        <?php endforeach ?>
                        <?php if($rank['mode'] === 'manager' && !empty($rank['windows'][0]['complete']) && (float) $rank['windows'][0]['current'] >= 60): ?><div class="alert alert-success py-2 mb-0">Cilj 60 Non-Manager CC je ostvaren; aktiviran je sljedeći cilj od 100 CC.</div><?php endif ?>
                        <?php if($rank['mode'] !== 'manager'): ?><div class="small text-muted border-top pt-2">Pravila: Supervisor 10 CC u 1 mjesecu · Assistant Manager 60 CC u 2 mjeseca · Manager 120 CC u 2 ili 150 CC u 4 kalendarska mjeseca.</div><?php endif ?>
                    </div></div>
                </div>
            </div>

            <div class="card fb-card mb-4"><div class="card-header bg-transparent"><h2 class="h5 mb-0">Moji provjereni FLP360 podaci</h2></div><div class="card-body"><div class="fb-verified-grid">
                <div class="fb-verified-item"><div class="small text-muted">Trenutačna pozicija</div><strong><?= htmlspecialchars($mine['title'] ?: 'Bez statusa') ?></strong></div>
                <div class="fb-verified-item"><div class="small text-muted">Osobni CC</div><strong><?= isset($mine['personal_cc']) ? $format_cc($mine['personal_cc']) : '—' ?></strong></div>
                <div class="fb-verified-item"><div class="small text-muted">Total CC</div><strong><?= isset($mine['total_cc']) ? $format_cc($mine['total_cc']) : '—' ?></strong></div>
                <div class="fb-verified-item"><div class="small text-muted">Total Active CC</div><strong><?= isset($mine['total_active_cc']) ? $format_cc($mine['total_active_cc']) : '—' ?></strong></div>
                <?php if($rank['mode'] === 'manager'): ?><div class="fb-verified-item"><div class="small text-muted">Non-Manager CC</div><strong><?= isset($mine['non_manager_cc']) ? $format_cc($mine['non_manager_cc']) : '—' ?></strong></div><?php endif ?>
            </div></div></div>
        <?php else: ?>
            <h2 class="h5 mb-3">Provjereni podaci strukture</h2>
            <p class="small text-muted">Samo vrijednosti iz uvezenih FLP360 izvještaja; bez individualnih 4 Core procjena.</p>
            <div class="row">
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card h-100"><div class="card-body"><div class="text-muted small text-uppercase">Članovi u izvještaju</div><div class="h3 mb-1"><?= nr($summary['members']) ?></div></div></div></div>
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card h-100"><div class="card-body"><div class="text-muted small text-uppercase">S osobnim CC-om</div><div class="h3 mb-1"><?= nr($summary['personal_active']) ?></div></div></div></div>
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card h-100"><div class="card-body"><div class="text-muted small text-uppercase">Zbroj osobnih CC</div><div class="h3 mb-1"><?= $format_cc($summary['personal_cc']) ?></div></div></div></div>
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card h-100"><div class="card-body"><div class="text-muted small text-uppercase">FLP360 4CC Active</div><div class="h3 mb-1"><?= nr($summary['active_4cc']) ?></div></div></div></div>
            </div>
        <?php endif ?>

        <?php if(!$data->is_admin && $data->focus_member): $action = $data->focus_member['next_action']; ?>
            <div class="card fb-card fb-action mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex flex-wrap align-items-center mb-2"><span class="text-uppercase text-muted small mr-2">Sljedeći korak · <?= htmlspecialchars($action['core']) ?></span><?php if(!empty($action['sequence_total'])): ?><span class="badge badge-light">Korak <?= nr($action['sequence_position']) ?>/<?= nr($action['sequence_total']) ?></span><?php endif ?></div>
                            <h2 class="h4 mb-2"><?= htmlspecialchars($action['title']) ?></h2>
                            <p class="mb-2"><?= htmlspecialchars($action['instruction']) ?></p>
                            <?php if(!empty($action['checklist'])): ?><ol class="pl-3 mb-3"><?php foreach($action['checklist'] as $item): ?><li class="mb-1"><?= htmlspecialchars($item) ?></li><?php endforeach ?></ol><?php endif ?>
                            <div class="small font-weight-bold"><i class="fas fa-check-circle text-success mr-1"></i> <?= htmlspecialchars($action['success_definition']) ?></div>
                            <div class="small text-muted mt-2">Ovaj korak ostaje aktivan dok ga ne dovršiš. Nakon potvrde automatski će se prikazati tvoj sljedeći korak.</div>
                            <div class="small text-muted mt-1">Redovitim izvršavanjem koraka gradiš dobre poslovne navike, ostvaruješ više kvalitetnih kontakata i napreduješ prema svojoj sljedećoj razini.</div>
                        </div>
                        <?php if(!empty($action['can_complete'])): ?><div class="col-lg-4 mt-4 mt-lg-0">
                            <form method="post">
                                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                                <input type="hidden" name="record_outcome" value="1" />
                                <input type="hidden" name="fbo_id" value="<?= htmlspecialchars($data->focus_member['fbo_id']) ?>" />
                                <input type="hidden" name="core_key" value="<?= htmlspecialchars($action['core']) ?>" />
                                <input type="hidden" name="action_key" value="<?= htmlspecialchars($action['key']) ?>" />
                                <input type="hidden" name="root" value="<?= htmlspecialchars($data->requested_root) ?>" />
                                <input type="hidden" name="period" value="<?= htmlspecialchars(substr($dashboard['period'], 0, 7)) ?>" />
                                <label class="small" for="outcome_count">Koliko si stvarno napravio/la?</label>
                                <div class="input-group">
                                    <input type="number" min="0" max="999" name="outcome_count" id="outcome_count" class="form-control" value="<?= (int) $action['target'] ?>" />
                                    <div class="input-group-append"><button class="btn btn-success">Dovršeno — sljedeći korak</button></div>
                                </div>
                            </form>
                        </div><?php endif ?>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(!empty($dashboard['trend'])): ?>
            <?php $trend_max = $data->is_admin ? max(1000, ...array_map(static fn($row) => (float) $row['total_cc'], $dashboard['trend'])) : max(1, ...array_map(static fn($row) => (float) $row['total_cc'], $dashboard['trend'])); ?>
            <div class="card fb-card mb-4">
                <div class="card-header bg-transparent"><h2 class="h5 mb-1"><?= $data->is_admin ? 'Trend prema cilju 1.000 CC' : 'Moj ukupni CC trend' ?></h2><div class="small text-muted"><?= $data->is_admin ? (!empty($dashboard['official_total_cc']) ? 'Službeni FLP360 Total CC za GLOBAL. Otvoreni mjesec je jasno označen.' : 'Privremeni FCC zbroj osobnih CC; učitaj službeni FLP360 Total CC za točan timski cilj.') : 'Duljina crte prikazuje uvezeni Total CC. Zelena znači da je 4 CC aktivnost potvrđena, a crvena da aktivnost nije ostvarena.' ?></div></div>
                <div class="card-body">
                    <?php foreach($dashboard['trend'] as $trend_row): ?>
                        <?php
                        $has_activity_data = $data->is_admin || !empty($trend_row['has_activity_data']);
                        $is_active = $data->is_admin || !empty($trend_row['is_4cc_active']);
                        $bar_class = $data->is_admin || $is_active ? 'bg-success' : ($has_activity_data ? 'bg-danger' : 'bg-secondary');
                        $bar_width = round(((float) $trend_row['total_cc'] / $trend_max) * 100, 1);
                        ?>
                        <div class="row align-items-center mb-3">
                            <div class="col-3 col-md-2 small font-weight-bold"><?= htmlspecialchars((new DateTimeImmutable($trend_row['period_month']))->format('m/Y')) ?><?= empty($trend_row['is_closed']) ? ' *' : '' ?></div>
                            <div class="col-6 col-md-8"><div class="progress" style="height:.75rem"><div class="progress-bar <?= $bar_class ?>" role="progressbar" style="width: <?= $bar_width ?>%;<?= !$data->is_admin && $has_activity_data && $bar_width <= 0 ? 'min-width:.4rem;' : '' ?>" aria-valuenow="<?= (float) $trend_row['total_cc'] ?>" aria-valuemin="0" aria-valuemax="<?= $trend_max ?>"></div></div></div>
                            <div class="col-3 col-md-2 text-right small">
                                <div><?= $format_cc($trend_row['total_cc']) ?> CC</div>
                                <?php if(!$data->is_admin): ?><div class="<?= $has_activity_data ? ($is_active ? 'text-success' : 'text-danger') : 'text-muted' ?>"><?= !$has_activity_data ? 'Nema podataka' : ($is_active ? '4 CC aktivan/na' : 'Nije aktivan/na') ?></div><?php endif ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                    <div class="small text-muted"><?php if($data->is_admin): ?>* mjesec nije zatvoren. Skala je 1.000 CC kako bi napredak bio usporediv iz mjeseca u mjesec.<?php else: ?>* mjesec nije zatvoren. Skala se prilagođava najvećem prikazanom Total CC rezultatu. Siva crta znači da za taj mjesec nema uvezenih podataka o aktivnosti.<?php endif ?></div>
                    <?php if(!$data->is_admin): ?><div class="small text-muted mt-2">Bez potvrđene 4 CC aktivnosti bodovi iz strukture ne ulaze u obračun isplate.</div><?php endif ?>
                </div>
            </div>
        <?php endif ?>

        <?php if($dashboard['is_manager_view'] || $data->is_admin): ?>
            <div class="card fb-card mb-4">
                <div class="card-header bg-transparent d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div class="pr-md-4"><h2 class="h5 mb-1">Prioriteti i napredak tima</h2><div class="small text-muted"><?php if($summary['focus_members']): ?>Na popisu je svih <?= nr($summary['focus_members']) ?> suradnika iz posljednjeg FLP360 Focus Group izvještaja. Klikni naziv stupca za sortiranje.<?php else: ?>Focus Group još nije uvezen; popis se privremeno temelji na osobnom CC-u i FCC aktivnostima. Klikni naziv stupca za sortiranje.<?php endif ?></div></div>
                    <input type="search" id="fb-team-search" class="form-control mt-3 mt-md-0" style="max-width:280px" placeholder="Traži ime ili Forever ID" />
                </div>
                <div class="table-responsive fb-table-wrap">
                    <table class="table table-hover mb-0" id="fb-team-table">
                        <thead><tr>
                            <th aria-sort="none"><button type="button" class="fb-sort-button" data-sort-key="sortMember" data-sort-type="text" aria-pressed="false">Suradnik <span class="fb-sort-icon" aria-hidden="true">↕</span></button></th>
                            <th aria-sort="none"><button type="button" class="fb-sort-button" data-sort-key="sortStatus" data-sort-type="number" aria-pressed="false">FLP status <span class="fb-sort-icon" aria-hidden="true">↕</span></button></th>
                            <th aria-sort="none"><button type="button" class="fb-sort-button" data-sort-key="sortPersonalCc" data-sort-type="number" aria-pressed="false">Osobni CC <span class="fb-sort-icon" aria-hidden="true">↕</span></button></th>
                            <th aria-sort="none"><button type="button" class="fb-sort-button" data-sort-key="sortNeededCc" data-sort-type="number" aria-pressed="false">Do razine <span class="fb-sort-icon" aria-hidden="true">↕</span></button></th>
                            <th aria-sort="none"><button type="button" class="fb-sort-button" data-sort-key="sortLastPurchase" data-sort-type="text" aria-pressed="false">Zadnja kupnja <span class="fb-sort-icon" aria-hidden="true">↕</span></button></th>
                            <th aria-sort="none"><button type="button" class="fb-sort-button" data-sort-key="sortFcc7" data-sort-type="number" aria-pressed="false">FCC 7 dana <span class="fb-sort-icon" aria-hidden="true">↕</span></button></th>
                            <th aria-sort="none"><button type="button" class="fb-sort-button" data-sort-key="sortNextAction" data-sort-type="text" aria-pressed="false">Sljedeći korak <span class="fb-sort-icon" aria-hidden="true">↕</span></button></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach($data->priority_members as $member): ?>
                            <?php
                            $is_active = (float) ($member['personal_cc'] ?? 0) > 0;
                            $status_label = !empty($member['focus_is_active']) ? '4 CC aktivan' : (!empty($member['focus_previous_active']) ? 'Reaktivacija' : 'Fokus');
                            $status_order = !empty($member['focus_is_active']) ? 0 : (!empty($member['focus_previous_active']) ? 1 : 2);
                            $next_action_label = ($member['next_action']['core'] ?? '') . ': ' . ($member['next_action']['title'] ?? '');
                            ?>
                            <tr
                                data-search="<?= htmlspecialchars(mb_strtolower($member['name'] . ' ' . $member['fbo_id'])) ?>"
                                data-sort-member="<?= htmlspecialchars(mb_strtolower($member['name'] . ' ' . $member['fbo_id'])) ?>"
                                data-sort-status="<?= $status_order ?>"
                                data-sort-personal-cc="<?= htmlspecialchars((string) ((float) ($member['personal_cc'] ?? 0))) ?>"
                                data-sort-needed-cc="<?= !empty($member['next_level']) ? htmlspecialchars((string) ((float) ($member['needed_cc_next_level'] ?? 0))) : '' ?>"
                                data-sort-last-purchase="<?= htmlspecialchars((string) ($member['last_purchase_date'] ?? '')) ?>"
                                data-sort-fcc7="<?= (int) ($member['actions_done_7d'] ?? 0) ?>"
                                data-sort-next-action="<?= htmlspecialchars(mb_strtolower($next_action_label)) ?>"
                            >
                                <td><strong><?= htmlspecialchars($member['name']) ?></strong><div class="small text-muted"><?= htmlspecialchars($member['fbo_id']) ?> · <?= htmlspecialchars($member['title'] ?: 'Bez statusa') ?></div></td>
                                <td><span class="fb-status-dot <?= !empty($member['focus_is_active']) ? 'bg-success' : 'bg-warning' ?>"></span><?= htmlspecialchars($status_label) ?></td>
                                <td><?= $format_cc($member['personal_cc'] ?? 0) ?></td>
                                <td><?= !empty($member['next_level']) ? $format_cc($member['needed_cc_next_level'] ?? 0) . '<div class="small text-muted">' . htmlspecialchars($member['next_level']) . '</div>' : '—' ?></td>
                                <td><?= !empty($member['last_purchase_date']) ? htmlspecialchars((new DateTimeImmutable($member['last_purchase_date']))->format('d.m.Y.')) : '—' ?></td>
                                <td><?= nr($member['actions_done_7d'] ?? 0) ?></td>
                                <td><strong><?= htmlspecialchars($member['next_action']['core']) ?>:</strong> <?= htmlspecialchars($member['next_action']['title']) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif ?>
    <?php endif ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('fb-team-search');
    const table = document.getElementById('fb-team-table');
    if (!table) return;

    if (search) {
        search.addEventListener('input', function () {
            const value = this.value.trim().toLocaleLowerCase('hr');
            table.querySelectorAll('tbody tr').forEach(function (row) {
                row.style.display = !value || row.dataset.search.includes(value) ? '' : 'none';
            });
        });
    }

    const tbody = table.querySelector('tbody');
    const sortButtons = Array.from(table.querySelectorAll('.fb-sort-button'));
    const originalOrder = new Map(Array.from(tbody.rows).map(function (row, index) { return [row, index]; }));
    const collator = new Intl.Collator('hr', {numeric: true, sensitivity: 'base'});
    let activeKey = '';
    let activeDirection = 'asc';

    sortButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const key = button.dataset.sortKey;
            const type = button.dataset.sortType;
            activeDirection = activeKey === key && activeDirection === 'asc' ? 'desc' : 'asc';
            activeKey = key;

            const rows = Array.from(tbody.rows);
            rows.sort(function (left, right) {
                const leftValue = left.dataset[key] ?? '';
                const rightValue = right.dataset[key] ?? '';
                const leftMissing = leftValue === '';
                const rightMissing = rightValue === '';
                if (leftMissing || rightMissing) {
                    if (leftMissing !== rightMissing) return leftMissing ? 1 : -1;
                    return originalOrder.get(left) - originalOrder.get(right);
                }

                const comparison = type === 'number'
                    ? Number(leftValue) - Number(rightValue)
                    : collator.compare(leftValue, rightValue);
                if (comparison === 0) return originalOrder.get(left) - originalOrder.get(right);
                return activeDirection === 'asc' ? comparison : -comparison;
            });
            rows.forEach(function (row) { tbody.appendChild(row); });

            sortButtons.forEach(function (item) {
                const selected = item === button;
                item.setAttribute('aria-pressed', selected ? 'true' : 'false');
                item.closest('th').setAttribute('aria-sort', selected ? (activeDirection === 'asc' ? 'ascending' : 'descending') : 'none');
                item.querySelector('.fb-sort-icon').textContent = selected ? (activeDirection === 'asc' ? '▲' : '▼') : '↕';
            });
        });
    });
});
</script>
