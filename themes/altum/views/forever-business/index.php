<?php defined('ALTUMCODE') || die() ?>
<?php
$dashboard = $data->dashboard;
$summary = $dashboard['summary'];
$period_label = !empty($dashboard['period']) ? (new DateTimeImmutable($dashboard['period']))->format('m/Y') : '—';
$format_cc = static fn($value) => number_format((float) $value, 3, ',', '.');
$format_percent = static fn($value) => number_format((float) $value, 1, ',', '.') . '%';
?>

<style>
    .forever-business-page .fb-card { border: 1px solid rgba(127, 127, 127, .17); border-radius: 1rem; }
    .forever-business-page .fb-hero { background: linear-gradient(135deg, #101f18, #173d2a); color: #fff; border-radius: 1.25rem; overflow: hidden; }
    .forever-business-page .fb-hero .progress { height: .75rem; background: rgba(255,255,255,.14); }
    .forever-business-page .fb-hero .progress-bar { background: #f6c900; }
    .forever-business-page .fb-core { border-top: 4px solid #6ca646; }
    .forever-business-page .fb-core-recruitment { border-top-color: #6b4a3b; }
    .forever-business-page .fb-core-retention { border-top-color: #f2c300; }
    .forever-business-page .fb-core-productivity { border-top-color: #6ca646; }
    .forever-business-page .fb-core-development { border-top-color: #1558a6; }
    .forever-business-page .fb-action { background: linear-gradient(135deg, rgba(108,166,70,.13), rgba(246,201,0,.08)); }
    .forever-business-page .fb-table-wrap { max-height: 680px; overflow: auto; }
    .forever-business-page .fb-status-dot { width: .65rem; height: .65rem; border-radius: 50%; display: inline-block; margin-right: .4rem; }
</style>

<div class="forever-business-page">
    <?= \Altum\Alerts::output_alerts() ?>

    <div class="fb-hero p-4 p-lg-5 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start">
            <div>
                <div class="text-uppercase small font-weight-bold text-warning mb-2">Forever 4 Core · <?= htmlspecialchars($period_label) ?></div>
                <h1 class="h2 text-white mb-2">Jedan fokus danas. Jedan mjerljiv rezultat.</h1>
                <p class="mb-0 text-white-50">Bodovi dolaze iz provjerenog FLP360 izvještaja, a dnevne aktivnosti iz FCC-a.</p>
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
            <?php $my_cc = (float) ($data->focus_member['personal_cc'] ?? 0); $my_progress = min(100, round(($my_cc / 4) * 100, 1)); ?>
            <div class="mt-4">
                <div class="d-flex justify-content-between mb-2"><span><strong><?= $format_cc($my_cc) ?> osobnih CC</strong> od 4 CC aktivnosti</span><span><?= $format_percent($my_progress) ?></span></div>
                <div class="progress"><div class="progress-bar" style="width: <?= $my_progress ?>%"></div></div>
                <small class="d-block mt-2 text-white-50">Vidiš samo svoje podatke. Zadnja sinkronizacija: <?= !empty($dashboard['last_sync_at']) ? htmlspecialchars((new DateTimeImmutable($dashboard['last_sync_at']))->format('d.m.Y. H:i')) : 'još nije izvršena' ?>.</small>
            </div>
        <?php endif ?>
    </div>

    <?php if(empty($dashboard['members'])): ?>
        <div class="alert alert-info">Još nema povezanih podataka za tvoj Forever ID. Administrator treba uvesti izvještaj ili ti dodijeliti managerski pristup.</div>
    <?php else: ?>
        <?php if(!$data->is_admin && $data->focus_member && empty($data->focus_member['is_in_current_structure'])): ?>
            <div class="alert alert-info"><strong>Tvoj osobni pregled je aktivan.</strong> Forever ID je povezan s FCC računom, ali bodovi još nisu pronađeni u posljednjem administratorskom izvještaju. Nakon sljedeće sinkronizacije prikaz će se automatski popuniti.</div>
        <?php endif ?>
        <?php if(!empty($dashboard['official_four_core'])): ?>
            <?php $official = $dashboard['official_four_core']; ?>
            <div class="card fb-card mb-4">
                <div class="card-header bg-transparent"><h2 class="h5 mb-1">Službeni FLP360 4 Core · <?= htmlspecialchars((new DateTimeImmutable($official['period_month']))->format('m/Y')) ?></h2><div class="small text-muted">Izvorne FLP360 definicije. “Mjesec” je zadnji prikazani zatvoreni mjesec, a YTD stanje u trenutku snimke.</div></div>
                <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Područje</th><th>Open Business · mjesec</th><th>Open Business · YTD</th><th>Downline · mjesec</th><th>Downline · YTD</th></tr></thead><tbody>
                    <?php foreach(['recruitment' => ['Recruitment', 'broj'], 'retention' => ['Retention', 'broj'], 'productivity' => ['Productivity', 'cc'], 'development' => ['Development', 'postotak']] as $metric_key => $metric_meta): ?>
                        <?php $format_official = static function($value) use ($format_cc, $metric_meta) { if($value === null || $value === '') return '—'; return $metric_meta[1] === 'cc' ? $format_cc($value) . ' CC' : ($metric_meta[1] === 'postotak' ? number_format((float) $value, 2, ',', '.') . '%' : nr($value)); }; ?>
                        <tr><th><?= htmlspecialchars($metric_meta[0]) ?></th><td><?= $format_official($official['open']['month'][$metric_key] ?? null) ?></td><td><?= $format_official($official['open']['ytd'][$metric_key] ?? null) ?></td><td><?= $format_official($official['downline']['month'][$metric_key] ?? null) ?></td><td><?= $format_official($official['downline']['ytd'][$metric_key] ?? null) ?></td></tr>
                    <?php endforeach ?>
                </tbody></table></div>
            </div>
        <?php endif ?>

        <?php if(!$data->is_admin && $data->focus_member): ?>
            <?php
            $mine = $data->focus_member;
            $mine_cc = (float) ($mine['personal_cc'] ?? 0);
            $mine_previous_cc = (float) ($mine['previous_personal_cc'] ?? 0);
            $has_focus_snapshot = !empty($mine['focus_snapshot_date']);
            $retention_title = $mine_previous_cc > 0 && $mine_cc > 0 ? 'Aktivan oba mjeseca' : ($mine_previous_cc > 0 ? 'Vrijeme za povratak' : ($mine_cc > 0 ? 'Novi početak' : 'Pokreni aktivnost'));
            ?>
            <h2 class="h5 mb-1">Moj 4 Core</h2>
            <p class="small text-muted mb-3">Četiri osobna pokazatelja i samo jedan sljedeći korak — bez prikaza drugih suradnika.</p>
            <div class="row">
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card fb-core fb-core-recruitment h-100"><div class="card-body"><div class="text-muted small text-uppercase">Recruitment</div><div class="h3 mb-1"><?= $has_focus_snapshot ? nr($mine['new_recruits'] ?? 0) : '—' ?></div><div class="small text-muted"><?= $has_focus_snapshot ? 'novih upisa u FLP Focus izvještaju' : 'čeka sljedeći Focus Group import' ?></div></div></div></div>
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card fb-core fb-core-retention h-100"><div class="card-body"><div class="text-muted small text-uppercase">Retention</div><div class="h5 mb-1"><?= htmlspecialchars($retention_title) ?></div><div class="small text-muted">prošli mjesec <?= $format_cc($mine_previous_cc) ?> · sada <?= $format_cc($mine_cc) ?> CC</div></div></div></div>
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card fb-core fb-core-productivity h-100"><div class="card-body"><div class="text-muted small text-uppercase">Productivity</div><div class="h3 mb-1"><?= $format_cc($mine_cc) ?> CC</div><div class="small text-muted"><?= $mine_cc >= 4 ? 'osobni cilj 4 CC ostvaren' : 'još ' . $format_cc(4 - $mine_cc) . ' CC do aktivnosti' ?></div></div></div></div>
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card fb-core fb-core-development h-100"><div class="card-body"><div class="text-muted small text-uppercase">Development</div><div class="h5 mb-1"><?= !empty($mine['is_4cc_active']) ? '4 CC aktivan/na' : 'U izgradnji' ?></div><div class="small text-muted"><?= !empty($mine['next_level']) ? 'sljedeća razina: ' . htmlspecialchars($mine['next_level']) : htmlspecialchars($mine['title'] ?: 'osobni razvoj') ?></div></div></div></div>
            </div>
        <?php else: ?>
            <h2 class="h5 mb-3">FCC operativni signali tima</h2>
            <p class="small text-muted">Ovi pokazatelji služe za dnevno vođenje i nisu zamjena za službene FLP360 4 Core formule.</p>
            <div class="row">
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card fb-core fb-core-recruitment h-100"><div class="card-body"><div class="text-muted small text-uppercase">Novi upisi</div><div class="h3 mb-1"><?= nr($summary['recruited']) ?></div><div class="small text-muted">po datumu učlanjenja u strukturu</div></div></div></div>
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card fb-core fb-core-retention h-100"><div class="card-body"><div class="text-muted small text-uppercase">Ponovljena CC aktivnost</div><div class="h3 mb-1"><?= $format_percent($summary['retention_rate']) ?></div><div class="small text-muted"><?= nr($summary['retained']) ?> s osobnim CC-om oba mjeseca</div></div></div></div>
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card fb-core fb-core-productivity h-100"><div class="card-body"><div class="text-muted small text-uppercase">CC po aktivnom članu</div><div class="h3 mb-1"><?= $format_cc($summary['average_personal_cc']) ?></div><div class="small text-muted">operativni prosjek, nije CC po narudžbi</div></div></div></div>
                <div class="col-md-6 col-xl-3 mb-4"><div class="card fb-card fb-core fb-core-development h-100"><div class="card-body"><div class="text-muted small text-uppercase">4 CC članovi</div><div class="h3 mb-1"><?= $format_percent($summary['development_rate']) ?></div><div class="small text-muted"><?= nr($summary['active_4cc']) ?> članova s 4 CC u strukturi</div></div></div></div>
            </div>
        <?php endif ?>

        <?php if($data->focus_member): $action = $data->focus_member['next_action']; ?>
            <div class="card fb-card fb-action mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="text-uppercase text-muted small mb-2">Danas · <?= htmlspecialchars($action['core']) ?></div>
                            <h2 class="h4 mb-2"><?= htmlspecialchars($action['title']) ?></h2>
                            <p class="mb-0"><?= htmlspecialchars($action['instruction']) ?></p>
                        </div>
                        <div class="col-lg-4 mt-4 mt-lg-0">
                            <form method="post">
                                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                                <input type="hidden" name="record_outcome" value="1" />
                                <input type="hidden" name="fbo_id" value="<?= htmlspecialchars($data->focus_member['fbo_id']) ?>" />
                                <input type="hidden" name="core_key" value="<?= htmlspecialchars($action['core']) ?>" />
                                <input type="hidden" name="action_key" value="<?= htmlspecialchars($action['key']) ?>" />
                                <input type="hidden" name="root" value="<?= htmlspecialchars($data->requested_root) ?>" />
                                <input type="hidden" name="period" value="<?= htmlspecialchars(substr($dashboard['period'], 0, 7)) ?>" />
                                <label class="small" for="outcome_count">Koliko si napravio/la?</label>
                                <div class="input-group">
                                    <input type="number" min="0" max="999" name="outcome_count" id="outcome_count" class="form-control" value="<?= (int) $action['target'] ?>" />
                                    <div class="input-group-append"><button class="btn btn-success">Označi izvršeno</button></div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(!empty($dashboard['trend'])): ?>
            <?php $trend_max = $data->is_admin ? max(1000, ...array_map(static fn($row) => (float) $row['total_cc'], $dashboard['trend'])) : max(4, ...array_map(static fn($row) => (float) $row['total_cc'], $dashboard['trend'])); ?>
            <div class="card fb-card mb-4">
                <div class="card-header bg-transparent"><h2 class="h5 mb-1"><?= $data->is_admin ? 'Trend prema cilju 1.000 CC' : 'Moj osobni CC trend' ?></h2><div class="small text-muted"><?= $data->is_admin ? (!empty($dashboard['official_total_cc']) ? 'Službeni FLP360 Total CC za GLOBAL. Otvoreni mjesec je jasno označen.' : 'Privremeni FCC zbroj osobnih CC; učitaj službeni FLP360 Total CC za točan timski cilj.') : 'Svakim novim importom bodovi se automatski osvježavaju prema tvojem Forever ID-u.' ?></div></div>
                <div class="card-body">
                    <?php foreach($dashboard['trend'] as $trend_row): ?>
                        <div class="row align-items-center mb-3">
                            <div class="col-3 col-md-2 small font-weight-bold"><?= htmlspecialchars((new DateTimeImmutable($trend_row['period_month']))->format('m/Y')) ?><?= empty($trend_row['is_closed']) ? ' *' : '' ?></div>
                            <div class="col-6 col-md-8"><div class="progress" style="height:.75rem"><div class="progress-bar bg-success" role="progressbar" style="width: <?= round(((float) $trend_row['total_cc'] / $trend_max) * 100, 1) ?>%" aria-valuenow="<?= (float) $trend_row['total_cc'] ?>" aria-valuemin="0" aria-valuemax="<?= $trend_max ?>"></div></div></div>
                            <div class="col-3 col-md-2 text-right small"><?= $format_cc($trend_row['total_cc']) ?> CC</div>
                        </div>
                    <?php endforeach ?>
                    <div class="small text-muted">* mjesec nije zatvoren. Skala je <?= $data->is_admin ? '1.000 CC' : '4 CC' ?> kako bi napredak bio usporediv iz mjeseca u mjesec.</div>
                </div>
            </div>
        <?php endif ?>

        <?php if($dashboard['is_manager_view'] || $data->is_admin): ?>
            <div class="card fb-card mb-4">
                <div class="card-header bg-transparent d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div><h2 class="h5 mb-1">Moj tim — prioriteti</h2><div class="small text-muted"><?php if($summary['focus_members']): ?>Prikazano je <?= nr($summary['focus_members']) ?> suradnika iz posljednjeg FLP360 Focus Group izvještaja.<?php else: ?>Focus Group još nije uvezen; prioriteti se privremeno temelje na osobnom CC-u i FCC aktivnostima.<?php endif ?></div></div>
                    <input type="search" id="fb-team-search" class="form-control mt-3 mt-md-0" style="max-width:280px" placeholder="Traži ime ili Forever ID" />
                </div>
                <div class="table-responsive fb-table-wrap">
                    <table class="table table-hover mb-0" id="fb-team-table">
                        <thead><tr><th>Suradnik</th><th>FLP status</th><th>Osobni CC</th><th>Do razine</th><th>Zadnja kupnja</th><th>FCC 7 dana</th><th>Sljedeći korak</th></tr></thead>
                        <tbody>
                        <?php foreach($data->priority_members as $member): ?>
                            <?php $is_active = (float) ($member['personal_cc'] ?? 0) > 0; ?>
                            <tr data-search="<?= htmlspecialchars(mb_strtolower($member['name'] . ' ' . $member['fbo_id'])) ?>">
                                <td><strong><?= htmlspecialchars($member['name']) ?></strong><div class="small text-muted"><?= htmlspecialchars($member['fbo_id']) ?> · <?= htmlspecialchars($member['title'] ?: 'Bez statusa') ?></div></td>
                                <td><span class="fb-status-dot <?= !empty($member['focus_is_active']) ? 'bg-success' : 'bg-warning' ?>"></span><?= !empty($member['focus_is_active']) ? '4 CC aktivan' : (!empty($member['focus_previous_active']) ? 'Reaktivacija' : 'Fokus') ?></td>
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
                <?php if($summary['focus_members'] > 100): ?><div class="card-footer small text-muted">Prikazano je prvih 100 prioriteta od <?= nr($summary['focus_members']) ?> Focus Group suradnika.</div><?php endif ?>
            </div>
        <?php endif ?>
    <?php endif ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('fb-team-search');
    if (!search) return;
    search.addEventListener('input', function () {
        const value = this.value.trim().toLocaleLowerCase('hr');
        document.querySelectorAll('#fb-team-table tbody tr').forEach(function (row) {
            row.style.display = !value || row.dataset.search.includes(value) ? '' : 'none';
        });
    });
});
</script>
