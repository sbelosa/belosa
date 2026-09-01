<?php defined('ALTUMCODE') || die() ?>
<?php
$dashboard = $data->dashboard;
$summary = $dashboard['summary'];
$vip_program = isset($data->vip_program) && is_array($data->vip_program) ? $data->vip_program : [];
$show_vip_program = !empty($data->focus_member) || !empty($vip_program['is_admin_preview']);
$vip_result_type_options = forever_business_vip_result_type_options();
$vip_difficulty_options = forever_business_vip_difficulty_options();
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
    .forever-business-page .fb-vip-launch { position: relative; overflow: hidden; border: 1px solid rgba(246,201,0,.28); border-radius: 1.35rem; background: radial-gradient(circle at 88% 10%, rgba(246,201,0,.13), transparent 32%), linear-gradient(135deg, #101713 0%, #121b2b 58%, #0f1512 100%); color: #f8fafc; box-shadow: 0 18px 45px rgba(0,0,0,.16); }
    .forever-business-page .fb-vip-launch::before { content: ''; position: absolute; width: 16rem; height: 16rem; left: -8rem; bottom: -10rem; border-radius: 50%; background: rgba(83,190,133,.12); filter: blur(10px); pointer-events: none; }
    .forever-business-page .fb-vip-launch h2, .forever-business-page .fb-vip-launch h3, .forever-business-page .fb-vip-launch p, .forever-business-page .fb-vip-launch span, .forever-business-page .fb-vip-launch strong, .forever-business-page .fb-vip-launch small { color: inherit; }
    .forever-business-page .fb-vip-content { position: relative; z-index: 1; }
    .forever-business-page .fb-vip-eyebrow { display: inline-flex; align-items: center; gap: .45rem; color: #f6cf2e !important; font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
    .forever-business-page .fb-vip-status { display: inline-flex; align-items: center; gap: .4rem; padding: .45rem .75rem; border-radius: 999px; font-size: .78rem; font-weight: 800; }
    .forever-business-page .fb-vip-status-qualified, .forever-business-page .fb-vip-status-active { background: rgba(70,190,124,.16); border: 1px solid rgba(91,220,151,.32); color: #9cf0bd !important; }
    .forever-business-page .fb-vip-status-pending { background: rgba(246,201,0,.12); border: 1px solid rgba(246,201,0,.28); color: #ffe56f !important; }
    .forever-business-page .fb-vip-countdown { display: grid; grid-template-columns: repeat(4, minmax(72px, 1fr)); gap: .65rem; }
    .forever-business-page .fb-vip-time { padding: .85rem .5rem; border-radius: .85rem; text-align: center; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.1); }
    .forever-business-page .fb-vip-time strong { display: block; font-size: 1.55rem; line-height: 1.1; font-variant-numeric: tabular-nums; }
    .forever-business-page .fb-vip-time span { display: block; margin-top: .28rem; color: rgba(255,255,255,.57) !important; font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
    .forever-business-page .fb-vip-eligibility { padding: 1rem 1.1rem; border-radius: 1rem; background: rgba(255,255,255,.055); border: 1px solid rgba(255,255,255,.1); }
    .forever-business-page .fb-vip-progress { height: .58rem; overflow: hidden; border-radius: 999px; background: rgba(255,255,255,.11); }
    .forever-business-page .fb-vip-progress > span { display: block; height: 100%; border-radius: inherit; background: linear-gradient(90deg, #f0b90b, #ffe36b); transition: width .35s ease; }
    .forever-business-page .fb-vip-progress.is-complete > span { background: linear-gradient(90deg, #39a96b, #83e4aa); }
    .forever-business-page .fb-vip-conditions { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
    .forever-business-page .fb-vip-condition { display: flex; gap: .7rem; padding: .9rem; border-radius: .9rem; background: rgba(7,11,9,.32); border: 1px solid rgba(255,255,255,.08); }
    .forever-business-page .fb-vip-condition-icon { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 2rem; width: 2rem; height: 2rem; border-radius: .7rem; background: rgba(246,201,0,.13); color: #f7d742 !important; }
    .forever-business-page .fb-vip-condition.is-complete .fb-vip-condition-icon { background: rgba(70,190,124,.16); color: #8ceab1 !important; }
    .forever-business-page .fb-vip-preview { position: relative; min-height: 190px; overflow: hidden; border-radius: 1rem; border: 1px solid rgba(255,255,255,.1); background: rgba(255,255,255,.035); }
    .forever-business-page .fb-vip-preview-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; padding: 1rem; transition: filter .25s ease, opacity .25s ease; }
    .forever-business-page .fb-vip-preview.is-locked .fb-vip-preview-grid { filter: blur(5px); opacity: .45; pointer-events: none; user-select: none; }
    .forever-business-page .fb-vip-feature { min-height: 132px; padding: 1rem; border-radius: .9rem; background: linear-gradient(145deg, rgba(255,255,255,.08), rgba(255,255,255,.035)); border: 1px solid rgba(255,255,255,.09); }
    .forever-business-page .fb-vip-feature i { color: #f6cf2e; font-size: 1.15rem; }
    .forever-business-page .fb-vip-feature p { color: rgba(255,255,255,.61) !important; font-size: .78rem; line-height: 1.45; }
    .forever-business-page .fb-vip-lock { position: absolute; z-index: 2; inset: 0; display: flex; align-items: center; justify-content: center; padding: 1rem; text-align: center; background: linear-gradient(180deg, rgba(10,15,12,.2), rgba(10,15,12,.74)); }
    .forever-business-page .fb-vip-lock-inner { max-width: 410px; padding: 1rem 1.25rem; border-radius: 1rem; background: rgba(13,19,15,.9); border: 1px solid rgba(246,201,0,.24); box-shadow: 0 12px 32px rgba(0,0,0,.28); }
    .forever-business-page .fb-vip-lock-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.65rem; height: 2.65rem; margin-bottom: .65rem; border-radius: .9rem; color: #f7d742; background: rgba(246,201,0,.13); }
    .forever-business-page .fb-vip-note { color: rgba(255,255,255,.58) !important; }
    .forever-business-page .fb-vip-schedule { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .9rem; padding: 1rem 1.1rem; border-radius: 1rem; background: rgba(37,211,102,.08); border: 1px solid rgba(37,211,102,.23); }
    .forever-business-page .fb-vip-schedule-icon { display: inline-flex; align-items: center; justify-content: center; width: 2.6rem; height: 2.6rem; flex: 0 0 2.6rem; border-radius: .9rem; background: rgba(37,211,102,.14); color: #8cf0b0 !important; }
    .forever-business-page .fb-vip-whatsapp { color: #082b18 !important; background: #74e59e; border-color: #74e59e; font-weight: 800; }
    .forever-business-page .fb-vip-whatsapp:hover, .forever-business-page .fb-vip-whatsapp:focus { color: #071d11 !important; background: #91efb3; border-color: #91efb3; }
    .forever-business-page .fb-action-meta { display: flex; flex-wrap: wrap; gap: .45rem; }
    .forever-business-page .fb-action-meta .badge { padding: .45rem .65rem; border-radius: 999px; }
    .forever-business-page .fb-weekly-plan { padding: .85rem 1rem; border-radius: .9rem; background: rgba(246,201,0,.1); border: 1px solid rgba(246,201,0,.25); }
    .forever-business-page .fb-quick-step { padding: .75rem .85rem; border-radius: .8rem; background: rgba(30,136,229,.07); border: 1px solid rgba(30,136,229,.16); }
    .forever-business-page .fb-next-unlock { display: inline-flex; align-items: center; gap: .7rem; max-width: 100%; padding: .7rem .85rem; border: 1px solid rgba(21,88,166,.18); border-radius: .85rem; background: rgba(21,88,166,.065); color: inherit; }
    .forever-business-page .fb-next-unlock > i { color: #1558a6; }
    .forever-business-page .fb-next-unlock-copy { display: flex; flex-wrap: wrap; align-items: baseline; gap: .22rem .42rem; }
    .forever-business-page .fb-next-unlock-value { font-size: 1rem; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: .035em; white-space: nowrap; }
    .forever-business-page .fb-next-unlock-time { opacity: .68; white-space: nowrap; }
    .forever-business-page .fb-education-path { background: linear-gradient(135deg, rgba(67,166,111,.075), rgba(31,120,200,.045)); border-color: rgba(67,166,111,.22); }
    .forever-business-page .fb-education-current { display: inline-flex; align-items: center; padding: .45rem .7rem; border: 1px solid rgba(67,166,111,.22); border-radius: 999px; background: rgba(67,166,111,.09); font-size: .78rem; font-weight: 800; white-space: nowrap; }
    .forever-business-page .fb-education-metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .75rem; }
    .forever-business-page .fb-education-metric { padding: .8rem .9rem; border: 1px solid rgba(127,127,127,.15); border-radius: .8rem; background: rgba(255,255,255,.36); }
    .forever-business-page .fb-education-metric .progress { height: .42rem; background: rgba(127,127,127,.16); }
    .forever-business-page .fb-education-metric .progress-bar { background: linear-gradient(90deg, #43a66f, #7ed7a5); }
    .forever-business-page .fb-education-note { padding: .72rem .85rem; border-radius: .75rem; background: rgba(30,136,229,.055); border: 1px solid rgba(30,136,229,.13); }
    .forever-business-page .fb-education-details { border-top: 1px solid rgba(127,127,127,.16); }
    .forever-business-page .fb-education-details summary { cursor: pointer; font-weight: 800; }
    .forever-business-page .fb-education-details summary:focus { outline: 2px solid rgba(31,120,200,.35); outline-offset: 3px; border-radius: .25rem; }
    .forever-business-page .fb-education-guide { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .65rem; }
    .forever-business-page .fb-education-guide-item { padding: .75rem .8rem; border: 1px solid rgba(127,127,127,.14); border-radius: .75rem; background: rgba(127,127,127,.035); }
    .forever-business-page .fb-education-guide-item.is-current { border-color: rgba(67,166,111,.34); background: rgba(67,166,111,.075); }
    .forever-business-page .fb-education-guide-dot { display: inline-block; width: .48rem; height: .48rem; margin-right: .4rem; border-radius: 50%; background: rgba(127,127,127,.45); vertical-align: .08rem; }
    .forever-business-page .fb-education-guide-item.is-current .fb-education-guide-dot { background: #43a66f; box-shadow: 0 0 0 3px rgba(67,166,111,.13); }
    @media (max-width: 991.98px) {
        .forever-business-page .fb-vip-conditions { grid-template-columns: 1fr; }
        .forever-business-page .fb-vip-preview-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .forever-business-page .fb-education-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        .forever-business-page .fb-progress-row { margin-left: 0; margin-right: 0; }
        .forever-business-page .fb-vip-countdown { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .forever-business-page .fb-vip-preview-grid { grid-template-columns: 1fr; }
        .forever-business-page .fb-vip-feature { min-height: 0; }
        .forever-business-page .fb-education-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; }
        .forever-business-page .fb-education-metrics .fb-education-metric:last-child { grid-column: 1 / -1; }
        .forever-business-page .fb-education-guide { grid-template-columns: 1fr; }
        .forever-business-page .fb-education-current { white-space: normal; }
    }
    @media (max-width: 359.98px) {
        .forever-business-page .fb-education-metrics { grid-template-columns: 1fr; }
        .forever-business-page .fb-education-metrics .fb-education-metric:last-child { grid-column: auto; }
    }
</style>

<div class="forever-business-page">
    <?= \Altum\Alerts::output_alerts() ?>

    <div class="fb-hero p-4 p-lg-5 mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start">
            <div>
                <div class="text-uppercase small font-weight-bold text-warning mb-2">Forever napredak · <?= htmlspecialchars($period_label) ?></div>
                <h1 class="h2 mb-2"><?= $data->is_admin ? 'Tvoj Leader program i današnji korak.' : 'Tvoja aktivnost, pozicija i sljedeći korak.' ?></h1>
                <p class="mb-0 opacity-75"><?= $data->is_admin ? 'Projektni brojevi, cilj 1.000 CC i timske analize nalaze se u LOS-u; ovdje odrađuješ vlastiti Leader program.' : 'Ovdje pratiš svoje bodove, aktivnost i napredak prema sljedećoj poziciji.' ?></p>
            </div>
            <?php if(!$data->is_admin && !empty($dashboard['periods'])): ?>
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
            <div class="mt-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between p-3 rounded" style="background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.13)">
                <span class="mb-2 mb-md-0"><strong>Administratorska analitika je izdvojena.</strong> Tamo vidiš 1.000 CC, kvalificirane, upisane, aktivne, grafove i osobe kojima treba pomoć.</span>
                <a href="<?= url('admin/leader-operating-system-forever') ?>" class="btn btn-warning ml-md-3"><i class="fas fa-chart-line mr-1"></i> Otvori LOS · Moj Forever</a>
            </div>
        <?php endif ?>
        <?php if($data->focus_member): ?>
            <?php
            $verified = $data->focus_member['verified_progress'];
            $activity_is_effective = !empty($verified['is_4cc_active']);
            $activity_is_known = ($verified['activity_source'] ?? 'unknown') !== 'unknown';
            $activity_progress = $activity_is_effective
                ? 100.0
                : min((float) $verified['personal_progress'], (float) $verified['regional_progress']);
            ?>
            <div class="mt-4">
                <div class="d-flex justify-content-between mb-2"><span><strong><?= $activity_is_effective ? '4 CC aktivnost ostvarena' : ($activity_is_known ? 'Napredak prema 4 CC aktivnosti' : 'Provjera 4 CC aktivnosti čeka potpune podatke') ?></strong></span><span><?= $format_percent($activity_progress) ?></span></div>
                <div class="progress"><div class="progress-bar" style="width: <?= $activity_progress ?>%"></div></div>
                <?php if(!empty($verified['is_officially_active'])): ?>
                    <small class="d-block mt-2 opacity-75">Aktivnost je potvrđena službenim FLP360 4 CC Active signalom za odabrano razdoblje.</small>
                <?php elseif($activity_is_effective): ?>
                    <small class="d-block mt-2 opacity-75">Službeni signal nije dostupan; pomoćni izračun potvrđuje najmanje 1 Personal CC i ukupno 4 Total Active CC u poslovnoj regiji.</small>
                <?php elseif($activity_is_known): ?>
                    <small class="d-block mt-2 opacity-75">Za aktivnost su potrebna najmanje 4 Total Active CC u poslovnoj regiji, od čega najmanje 1 Personal CC.</small>
                <?php else: ?>
                    <small class="d-block mt-2 opacity-75">Nije dostupan službeni signal ni oba podatka potrebna za pomoćni izračun. Sustav zato ne prikazuje negativan status dok FLP360 provjera ne bude potpuna.</small>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>

    <?php
    $last_sync_display = forever_business_format_zagreb_datetime($dashboard['last_sync_at'] ?? null);
    $last_import_display = forever_business_format_zagreb_datetime($dashboard['last_data_import_at'] ?? null);
    ?>
    <div class="fb-sync px-3 py-2 mb-4">
        <i class="fas fa-fw fa-sync-alt mr-1"></i>
        <?php if($last_sync_display !== ''): ?>
            <strong>Podaci provjereni:</strong> <?= htmlspecialchars($last_sync_display) ?>.
            <?php if(!empty($dashboard['last_sync_was_duplicate']) && $last_import_display !== ''): ?>
                <span class="text-muted">Trenutačno su prikazani najnoviji dostupni bodovi, ažurirani <?= htmlspecialchars($last_import_display) ?>.</span>
            <?php else: ?>
                <span class="text-muted">Prikazani su najnoviji dostupni bodovi.</span>
            <?php endif ?>
            <span class="d-block text-muted">Ako si nakon toga ostvario/la novi promet, prikazat će se automatski nakon sljedećeg osvježavanja.</span>
        <?php else: ?>
            <strong>Podaci još nisu dostupni.</strong>
            <span class="d-block text-muted">Tvoji bodovi prikazat će se automatski nakon prvog osvježavanja.</span>
        <?php endif ?>
    </div>

    <?php if(empty($dashboard['members'])): ?>
        <div class="alert alert-info"><strong>Tvoji bodovi još nisu dostupni.</strong> Provjeri je li Forever ID na tvojem FCC računu ispravan. Podaci će se prikazati automatski nakon sljedećeg osvježavanja.</div>
    <?php else: ?>
        <?php if(!$data->is_admin && $data->focus_member && empty($data->focus_member['is_in_current_structure'])): ?>
            <div class="alert alert-info"><strong>Tvoj osobni pregled je spreman.</strong> Bodovi za odabrani mjesec još nisu dostupni i prikazat će se automatski nakon sljedećeg osvježavanja.</div>
        <?php endif ?>
        <?php if($data->focus_member): ?>
            <?php
            $mine = $data->focus_member;
            $verified = $mine['verified_progress'];
            $rank = $verified['rank'];
            $activity_status_known = ($verified['activity_source'] ?? 'unknown') !== 'unknown';
            ?>
            <div class="row fb-progress-row">
                <div class="col-lg-6 mb-4">
                    <div class="card fb-card fb-progress-panel h-100"><div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div><div class="small text-uppercase text-muted">Mjesečna aktivnost</div><h2 class="h5 mb-0">4 CC aktivnost</h2></div>
                            <span class="badge badge-<?= !empty($verified['is_4cc_active']) ? 'success' : ($activity_status_known ? 'warning' : 'secondary') ?>"><?= !empty($verified['is_4cc_active']) ? 'Aktivnost ostvarena' : ($activity_status_known ? 'Još nije ostvareno' : 'Čeka potpune podatke') ?></span>
                        </div>
                        <?php if(!empty($verified['has_activity_data'])): ?>
                            <?php if($verified['personal_cc'] !== null): ?>
                                <div class="d-flex justify-content-between small mb-1"><span>Osobni CC · potrebno najmanje 1</span><strong><?= $format_cc($verified['personal_cc']) ?> / 1,000</strong></div>
                                <div class="progress mb-3"><div class="progress-bar" style="width: <?= $verified['personal_progress'] ?>%"></div></div>
                            <?php endif ?>
                            <?php if($verified['total_active_cc'] !== null): ?>
                                <div class="d-flex justify-content-between small mb-1"><span>FLP360 Active CC u poslovnoj regiji</span><strong><?= $format_cc($verified['total_active_cc']) ?></strong></div>
                                <div class="progress mb-3"><div class="progress-bar" style="width: <?= $verified['regional_progress'] ?>%"></div></div>
                            <?php endif ?>
                            <?php if(($verified['activity_source'] ?? '') === 'official' && !empty($verified['is_officially_active'])): ?>
                                <div class="small text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i> FLP360 je potvrdio aktivnost za ovaj mjesec.</div>
                            <?php elseif(($verified['activity_source'] ?? '') === 'formula' && !empty($verified['is_4cc_active'])): ?>
                                <div class="small text-success font-weight-bold"><i class="fas fa-calculator mr-1"></i> Službeni signal za ovo razdoblje nije dostupan pa je primijenjen uključivi pomoćni izračun: najmanje 1 Personal CC i 4 Total Active CC.</div>
                            <?php elseif(!empty($verified['meets_activity_formula'])): ?>
                                <div class="small text-warning font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i> Prikazani zbrojevi dosežu pomoćni prag, ali dostupni službeni FLP360 4 CC Active signal nije pozitivan i FCC ga ne nadjačava.</div>
                            <?php elseif(!$activity_status_known): ?>
                                <div class="small text-info font-weight-bold"><i class="fas fa-hourglass-half mr-1"></i> FLP360 provjera još nije potpuna; ovaj status nije negativna potvrda aktivnosti.</div>
                            <?php endif ?>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">Podaci o aktivnosti za odabrani mjesec još nisu dostupni. Prikazat će se automatski nakon sljedećeg osvježavanja.</div>
                        <?php endif ?>
                    </div></div>
                </div>
                <div class="col-lg-6 mb-4">
                    <div class="card fb-card fb-progress-panel h-100"><div class="card-body p-4">
                        <div class="small text-uppercase text-muted">Put do sljedeće pozicije</div>
                        <h2 class="h5 mb-1"><?= htmlspecialchars($rank['current_title']) ?> <i class="fas fa-long-arrow-alt-right mx-1 text-muted"></i> <?= htmlspecialchars($rank['next_title']) ?></h2>
                        <div class="small text-muted mb-3"><?= $rank['mode'] === 'manager' ? 'Ovdje pratiš svoj Non-Manager CC i koliko ti nedostaje do sljedećeg cilja.' : 'Ovdje pratiš svoj ukupni CC i koliko ti nedostaje do sljedeće pozicije.' ?></div>
                        <?php foreach($rank['windows'] as $window): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1"><span><?= htmlspecialchars($window['label']) ?> · <?= htmlspecialchars($window['metric']) ?></span><strong><?= $window['complete'] ? $format_cc($window['current']) . ' / ' . $format_cc($window['target']) : 'podaci još nisu dostupni' ?></strong></div>
                                <div class="progress"><div class="progress-bar fb-rank-bar" style="width: <?= $window['progress'] ?>%"></div></div>
                                <div class="small mt-1 <?= $window['achieved'] ? 'text-success font-weight-bold' : 'text-muted' ?>"><?= !$window['complete'] ? 'Napredak će se prikazati kada budu dostupni svi potrebni mjeseci.' : ($window['achieved'] ? 'Uvjet je ostvaren.' : 'Nedostaje još ' . $format_cc($window['gap']) . ' CC.') ?></div>
                            </div>
                        <?php endforeach ?>
                        <?php if($rank['mode'] === 'manager' && !empty($rank['windows'][0]['complete']) && (float) $rank['windows'][0]['current'] >= 60): ?><div class="alert alert-success py-2 mb-0">Cilj 60 Non-Manager CC je ostvaren; aktiviran je sljedeći cilj od 100 CC.</div><?php endif ?>
                        <?php if($rank['mode'] !== 'manager'): ?><div class="small text-muted border-top pt-2">Pravila: Supervisor 10 CC u 1 mjesecu · Assistant Manager 60 CC u 2 mjeseca · Manager 120 CC u 2 ili 150 CC u 4 kalendarska mjeseca.</div><?php endif ?>
                    </div></div>
                </div>
            </div>

            <div class="card fb-card mb-4"><div class="card-header bg-transparent"><h2 class="h5 mb-0">Moji Forever podaci</h2></div><div class="card-body"><div class="fb-verified-grid">
                <div class="fb-verified-item"><div class="small text-muted">Trenutačna pozicija</div><strong><?= htmlspecialchars($mine['title'] ?: 'Bez statusa') ?></strong></div>
                <div class="fb-verified-item"><div class="small text-muted">Osobni CC</div><strong><?= isset($mine['personal_cc']) ? $format_cc($mine['personal_cc']) : '—' ?></strong></div>
                <div class="fb-verified-item"><div class="small text-muted">Total CC</div><strong><?= isset($mine['total_cc']) ? $format_cc($mine['total_cc']) : '—' ?></strong></div>
                <div class="fb-verified-item"><div class="small text-muted">Total Active CC</div><strong><?= isset($mine['total_active_cc']) ? $format_cc($mine['total_active_cc']) : '—' ?></strong></div>
                <?php if($rank['mode'] === 'manager'): ?><div class="fb-verified-item"><div class="small text-muted">Non-Manager CC</div><strong><?= isset($mine['non_manager_cc']) ? $format_cc($mine['non_manager_cc']) : '—' ?></strong></div><?php endif ?>
            </div></div></div>
        <?php else: ?>
            <?php if($data->is_admin): ?>
                <div class="alert alert-warning"><strong>Administratorski Forever ID nije pronađen u najnovijem izvještaju.</strong> Projektna analitika ostaje dostupna u <a href="<?= url('admin/leader-operating-system-forever') ?>" class="alert-link">LOS · Moj Forever</a>, a ovdje će se Leader program prikazati kada se Forever ID uskladi.</div>
            <?php endif ?>
        <?php endif ?>

        <?php if($show_vip_program): ?>
            <?php
            $vip_has_data = !empty($vip_program['has_data']);
            $vip_is_eligible = !empty($vip_program['is_eligible']);
            $vip_is_launched = !empty($vip_program['is_launched']);
            $vip_can_access = !empty($vip_program['can_access_education']);
            $vip_has_valid_linkage = !empty($vip_program['has_valid_linkage']);
            $vip_seconds = max(0, (int) ($vip_program['seconds_remaining'] ?? 0));
            $vip_days = intdiv($vip_seconds, 86400);
            $vip_hours = intdiv($vip_seconds % 86400, 3600);
            $vip_minutes = intdiv($vip_seconds % 3600, 60);
            $vip_seconds_part = $vip_seconds % 60;
            $vip_status_class = $vip_can_access ? 'active' : ($vip_is_eligible ? 'qualified' : 'pending');
            $vip_status_label = !$vip_has_valid_linkage
                ? (($vip_program['linkage_status'] ?? '') === 'duplicate' ? 'Forever ID je povezan više puta' : 'Forever ID nije valjano povezan')
                : ($vip_can_access ? 'Edukacija je aktivna' : ($vip_is_eligible ? 'Uvjet za pristup je trajno potvrđen' : 'Pristup još nije aktivan'));
            $vip_marketing_plan = is_array($vip_program['marketing_plan'] ?? null) ? $vip_program['marketing_plan'] : [];
            ?>

            <?php if(!empty($vip_program['is_admin_preview'])): ?>
                <div class="alert alert-warning mb-3">
                    <strong>Pretpregled prikaza za suradnike.</strong>
                    Ovaj prikaz koristi simulirane bodove i ne mijenja ničije podatke.
                    <span class="d-block mt-2">
                        <a href="<?= url('forever-business?vip_preview=pending') ?>" class="alert-link mr-3">Prikaži neispunjen uvjet</a>
                        <a href="<?= url('forever-business?vip_preview=qualified') ?>" class="alert-link mr-3">Prikaži ispunjen uvjet</a>
                        <a href="<?= url('forever-business?vip_preview=active') ?>" class="alert-link">Prikaži otvorenu edukaciju</a>
                    </span>
                </div>
            <?php endif ?>

            <section
                class="fb-vip-launch mb-4"
                <?php if(!$vip_is_launched): ?>data-fb-vip-countdown="<?= htmlspecialchars((string) ($vip_program['launch_at_iso'] ?? '')) ?>" data-fb-vip-server-now="<?= htmlspecialchars((string) ($vip_program['server_now_iso'] ?? '')) ?>"<?php endif ?>
                aria-labelledby="fb-vip-title"
            >
                <div class="fb-vip-content p-4 p-lg-5">
                    <div class="row align-items-start">
                        <div class="col-lg-7">
                            <div class="fb-vip-eyebrow mb-2"><i class="fas fa-star"></i> VIP 4 Core edukacija</div>
                            <h2 class="h3 mb-2" id="fb-vip-title"><?= $vip_can_access ? 'Tvoj vođeni program je otvoren.' : ($vip_is_launched ? 'Vođeni program je pokrenut.' : 'Tvoj vođeni program počinje 1. rujna.') ?></h2>
                            <p class="fb-vip-note mb-3"><?= $vip_can_access ? 'Otvori današnji korak i nastavi tempom koji možeš redovito održavati.' : 'Svaki dan dobivaš jedan jasan korak, praktične primjere i podršku kroz sva četiri područja 4 Corea.' ?></p>
                            <span class="fb-vip-status fb-vip-status-<?= $vip_status_class ?>"><i class="fas fa-<?= $vip_can_access || $vip_is_eligible ? 'check-circle' : 'lock' ?>"></i> <?= htmlspecialchars($vip_status_label) ?></span>
                        </div>
                        <div class="col-lg-5 mt-4 mt-lg-0">
                            <?php if(!$vip_is_launched): ?>
                                <div class="small font-weight-bold text-uppercase mb-2">Do početka edukacije</div>
                                <div class="fb-vip-countdown" aria-live="polite">
                                    <div class="fb-vip-time"><strong data-fb-vip-days><?= nr($vip_days) ?></strong><span>dana</span></div>
                                    <div class="fb-vip-time"><strong data-fb-vip-hours><?= str_pad((string) $vip_hours, 2, '0', STR_PAD_LEFT) ?></strong><span>sati</span></div>
                                    <div class="fb-vip-time"><strong data-fb-vip-minutes><?= str_pad((string) $vip_minutes, 2, '0', STR_PAD_LEFT) ?></strong><span>minuta</span></div>
                                    <div class="fb-vip-time"><strong data-fb-vip-seconds><?= str_pad((string) $vip_seconds_part, 2, '0', STR_PAD_LEFT) ?></strong><span>sekundi</span></div>
                                </div>
                            <?php else: ?>
                                <div class="fb-vip-eligibility h-100 d-flex align-items-center">
                                    <div><div class="small text-uppercase fb-vip-note mb-1">Početak programa</div><strong class="h5 mb-1 d-block">1. rujna 2026.</strong><span class="fb-vip-note small"><?= $vip_can_access ? 'Tvoj prvi korak nalazi se odmah ispod.' : 'Program je počeo, a tvoj se pristup otvara kada je uvjet potvrđen.' ?></span></div>
                                </div>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="fb-vip-eligibility mt-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end">
                            <div>
                                <div class="small text-uppercase fb-vip-note mb-1">Tvoj uvjet za pristup</div>
                                <?php if(!$vip_has_valid_linkage): ?>
                                    <strong><?= ($vip_program['linkage_status'] ?? '') === 'duplicate' ? 'Isti Forever ID povezan je s više aktivnih FCC računa.' : 'Forever ID još nije valjano povezan.' ?></strong>
                                    <div class="small fb-vip-note mt-1">Radi zaštite tvojih podataka pristup je privremeno zaustavljen. Javi se podršci kako bi ostao samo jedan točno povezan račun; trajni upis i dosadašnji zadaci ostaju sačuvani.</div>
                                <?php elseif($vip_is_eligible): ?>
                                    <strong>Uvjet je trajno potvrđen u <?= htmlspecialchars((string) ($vip_program['eligibility_period_label'] ?? '')) ?> s <?= $format_cc($vip_program['qualifying_personal_cc'] ?? $vip_program['personal_cc']) ?> osobnog CC.</strong>
                                    <div class="small fb-vip-note mt-1"><?= $vip_is_launched ? 'Pristup edukaciji je otvoren i kasniji slabiji mjesec ga neće zaključati.' : 'Ne trebaš ništa dodatno uključivati. Pristup će se otvoriti automatski 1. rujna.' ?></div>
                                <?php elseif(!$vip_has_data): ?>
                                    <strong>Čekamo najnoviji potvrđeni Personal CC.</strong>
                                    <div class="small fb-vip-note mt-1">Bodovi i automatski upis prikazat će se nakon sljedećeg uspješnog FLP360 osvježavanja.</div>
                                <?php elseif(($vip_program['status'] ?? '') === 'waiting_confirmation'): ?>
                                    <strong>Prag od 0,330 CC je dosegnut; čeka se završna potvrda izvora.</strong>
                                    <div class="small fb-vip-note mt-1">Ne trebaš ništa ponavljati. Sustav će trajno otvoriti program čim službena sinkronizacija potvrdi taj mjesec.</div>
                                <?php else: ?>
                                    <strong>Trenutačno imaš <?= $format_cc($vip_program['personal_cc']) ?> osobnog CC u <?= htmlspecialchars((string) ($vip_program['eligibility_period_label'] ?? 'zadnjem potvrđenom mjesecu')) ?>.</strong>
                                    <div class="small fb-vip-note mt-1">Za pristup nedostaje još <?= $format_cc($vip_program['gap_cc']) ?> osobnog CC. Uvjet se provjerava nakon svakog uspješnog osvježavanja podataka.</div>
                                <?php endif ?>
                            </div>
                            <div class="mt-3 mt-md-0 ml-md-4 text-md-right"><span class="small fb-vip-note">Napredak do 0,330 CC</span><strong class="d-block"><?= $vip_has_data ? $format_percent($vip_program['progress']) : '—' ?></strong></div>
                        </div>
                        <div class="fb-vip-progress mt-3 <?= $vip_is_eligible ? 'is-complete' : '' ?>"><span style="width: <?= min(100, (float) ($vip_program['progress'] ?? 0)) ?>%"></span></div>
                    </div>

                    <div class="fb-vip-conditions mt-3">
                        <div class="fb-vip-condition <?= $vip_is_eligible ? 'is-complete' : '' ?>">
                            <span class="fb-vip-condition-icon"><i class="fas fa-<?= $vip_is_eligible ? 'check' : 'chart-line' ?>"></i></span>
                            <div><strong class="d-block small">Najmanje 0,330 osobnog CC</strong><span class="fb-vip-note small">Prvi službeno potvrđeni mjesec od kolovoza 2026. nadalje trajno te upisuje u program.</span></div>
                        </div>
                        <div class="fb-vip-condition <?= $vip_has_valid_linkage ? 'is-complete' : '' ?>">
                            <span class="fb-vip-condition-icon"><i class="fas fa-<?= $vip_has_valid_linkage ? 'check' : 'link' ?>"></i></span>
                            <div>
                                <strong class="d-block small">Povezan Forever ID</strong>
                                <span class="fb-vip-note small"><?php if(!empty($vip_program['is_shared_linkage'])): ?>Odobreni računi koji dijele ovaj Forever ID prikazuju iste bodove i imaju pristup programu.<?php else: ?>Tvoj aktivni FCC račun sigurno povezuje bodove, trajni upis i napredak.<?php endif ?></span>
                            </div>
                        </div>
                        <div class="fb-vip-condition <?= $vip_is_launched ? 'is-complete' : '' ?>">
                            <span class="fb-vip-condition-icon"><i class="fas fa-<?= $vip_is_launched ? 'check' : 'calendar-alt' ?>"></i></span>
                            <div><strong class="d-block small">Početak 1. rujna</strong><span class="fb-vip-note small">Kvalificiranim se suradnicima program otvara automatski.</span></div>
                        </div>
                    </div>

                    <div class="small fb-vip-note mt-3"><i class="fas fa-info-circle mr-1"></i> Prag od 0,330 CC uvjet je za ovu dodatnu edukaciju. Službena Forever aktivnost i dalje zahtijeva ukupno 4 Active CC u istoj regiji, uključujući najmanje 1 osobni CC.</div>

                    <div class="fb-vip-schedule mt-4">
                        <div class="d-flex align-items-center">
                            <span class="fb-vip-schedule-icon mr-3"><i class="fas fa-video"></i></span>
                            <div>
                                <strong class="d-block">Marketing plan · svake nedjelje u 18:00</strong>
                                <span class="fb-vip-note small"><?= !empty($vip_marketing_plan['next_at_display']) ? 'Sljedeći termin: ' . htmlspecialchars($vip_marketing_plan['next_at_display']) . '.' : 'Poveznica i kratke upute objavljuju se u VIP WhatsApp grupi.' ?></span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-md-end">
                            <?php if(!empty($vip_marketing_plan['url'])): ?>
                                <a href="<?= htmlspecialchars($vip_marketing_plan['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary mr-2 mb-2"><i class="fas fa-video mr-1"></i> Otvori Marketing plan</a>
                            <?php endif ?>
                            <?php if($vip_is_eligible && $vip_has_valid_linkage && !empty($vip_program['whatsapp_group_url'])): ?>
                                <a href="<?= htmlspecialchars($vip_program['whatsapp_group_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn fb-vip-whatsapp mb-2"><i class="fab fa-whatsapp mr-1"></i> Pridruži se VIP grupi</a>
                            <?php else: ?>
                                <span class="small fb-vip-note mb-2"><i class="fas fa-lock mr-1"></i> Pristup VIP grupi otvara se nakon ispunjenog uvjeta.</span>
                            <?php endif ?>
                        </div>
                    </div>

                    <div class="fb-vip-preview mt-4 <?= $vip_can_access ? '' : 'is-locked' ?>">
                        <div class="fb-vip-preview-grid" <?= !$vip_can_access ? 'aria-hidden="true"' : '' ?>>
                            <div class="fb-vip-feature"><i class="fas fa-route mb-3"></i><strong class="d-block mb-2">30 koraka po razini</strong><p class="mb-0">Jedan korak dnevno, uz dodatni nedjeljni Marketing plan. Svaka nova razina kreće od svojeg 1. koraka.</p></div>
                            <div class="fb-vip-feature"><i class="fas fa-users mb-3"></i><strong class="d-block mb-2">Nedjeljni Marketing plan</strong><p class="mb-0">Svake nedjelje u 18:00 pozivaš osobe zainteresirane za poslovanje.</p></div>
                            <div class="fb-vip-feature"><i class="fas fa-comments mb-3"></i><strong class="d-block mb-2">VIP podrška</strong><p class="mb-0">Kratki podsjetnici, primjeri i podrška kada zapneš.</p></div>
                            <div class="fb-vip-feature"><i class="fas fa-chart-pie mb-3"></i><strong class="d-block mb-2">Tvoj 4 Core napredak</strong><p class="mb-0">Pratiš dovršene korake i samoprijavljene radnje; prihod, pozicija i CC rezultat nisu zajamčeni.</p></div>
                        </div>
                        <?php if(!$vip_can_access): ?>
                            <div class="fb-vip-lock">
                                <div class="fb-vip-lock-inner">
                                    <span class="fb-vip-lock-icon"><i class="fas fa-lock"></i></span>
                                    <strong class="d-block mb-1"><?= $vip_is_eligible ? 'Tvoje mjesto u programu je spremno.' : 'Sadržaj se otvara nakon ispunjenog uvjeta.' ?></strong>
                                    <span class="fb-vip-note small"><?= !$vip_has_valid_linkage ? 'Upis i napredak su sačuvani; potrebno je uskladiti povezani FCC račun.' : ($vip_is_eligible ? 'Edukacija počinje 1. rujna i otvorit će se automatski.' : 'Kada službeni FLP360 podatak potvrdi najmanje 0,330 CC, FCC će te trajno upisati i otvoriti program od 1. rujna nadalje.') ?></span>
                                </div>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </section>
        <?php endif ?>

        <?php
        $vip_education_path = !empty($vip_program['preview_education_path'])
            ? $vip_program['preview_education_path']
            : ($data->focus_member ? forever_business_get_vip_education_path($data->focus_member) : []);
        ?>
        <?php if(!empty($vip_program['can_access_education']) && !empty($vip_education_path)): ?>
            <?php
            $education_mode = (string) ($vip_education_path['mode'] ?? 'focus');
            $education_current_key = (string) ($vip_education_path['current_key'] ?? '');
            $education_guide_current_key = $education_current_key;
            ?>
            <section class="card fb-card fb-education-path mb-4" aria-labelledby="fb-education-path-title">
                <div class="card-body p-3 p-md-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start">
                        <div class="pr-md-4">
                            <div class="small text-uppercase text-muted font-weight-bold mb-1">Tvoj put kroz edukaciju</div>
                            <h2 class="h5 mb-1" id="fb-education-path-title"><?= htmlspecialchars((string) ($vip_education_path['headline'] ?? 'Tvoj edukacijski smjer')) ?></h2>
                            <p class="small text-muted mb-0"><?= htmlspecialchars((string) ($vip_education_path['summary'] ?? '')) ?></p>
                        </div>
                        <span class="fb-education-current mt-3 mt-md-0"><i class="fas fa-route mr-1" aria-hidden="true"></i> Trenutačno: <?= htmlspecialchars((string) ($vip_education_path['current_label'] ?? '')) ?></span>
                    </div>

                    <?php if($education_mode === 'personal'): ?>
                        <div class="mt-3">
                            <div class="d-flex flex-wrap justify-content-between small mb-1">
                                <span>Osobni CC u aktualnom mjesecu</span>
                                <strong><?= $format_cc($vip_education_path['personal_cc'] ?? 0) ?> / <?= $format_cc($vip_education_path['personal_target_cc'] ?? 1) ?></strong>
                            </div>
                            <div class="progress" style="height:.5rem" role="progressbar" aria-label="Napredak prema Aktivator smjeru" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (float) ($vip_education_path['personal_progress'] ?? 0) ?>">
                                <div class="progress-bar bg-success" style="width: <?= (float) ($vip_education_path['personal_progress'] ?? 0) ?>%"></div>
                            </div>
                            <div class="small text-muted mt-1">Do Aktivatora ti nedostaje još <?= $format_cc($vip_education_path['personal_gap_cc'] ?? 0) ?> osobnog CC.</div>
                        </div>
                    <?php elseif($education_mode === 'four_cc'): ?>
                        <div class="fb-education-metrics mt-3">
                            <div class="fb-education-metric">
                                <div class="small text-muted mb-1">Osobni CC ovog mjeseca</div>
                                <strong><?= $format_cc($vip_education_path['personal_cc'] ?? 0) ?> / <?= $format_cc($vip_education_path['personal_target_cc'] ?? 1) ?></strong>
                                <div class="progress mt-2" role="progressbar" aria-label="Osobni CC prema Builder smjeru" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (float) ($vip_education_path['personal_progress'] ?? 0) ?>"><div class="progress-bar" style="width: <?= (float) ($vip_education_path['personal_progress'] ?? 0) ?>%"></div></div>
                            </div>
                            <div class="fb-education-metric">
                                <div class="small text-muted mb-1">Total Active CC ovog mjeseca</div>
                                <strong><?= $format_cc($vip_education_path['total_active_cc'] ?? 0) ?> / <?= $format_cc($vip_education_path['total_active_target_cc'] ?? 4) ?></strong>
                                <div class="progress mt-2" role="progressbar" aria-label="Total Active CC prema Builder smjeru" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (float) ($vip_education_path['total_active_progress'] ?? 0) ?>"><div class="progress-bar" style="width: <?= (float) ($vip_education_path['total_active_progress'] ?? 0) ?>%"></div></div>
                            </div>
                            <div class="fb-education-metric">
                                <div class="small text-muted mb-1">FLP360 4 CC Active</div>
                                <?php if(($vip_education_path['official_activity_signal'] ?? null) === 1): ?>
                                    <strong class="text-success"><i class="fas fa-check-circle mr-1" aria-hidden="true"></i> Potvrđen</strong>
                                <?php elseif(($vip_education_path['official_activity_signal'] ?? null) === 0): ?>
                                    <strong class="text-warning"><i class="fas fa-hourglass-half mr-1" aria-hidden="true"></i> Još nije potvrđen</strong>
                                <?php else: ?>
                                    <strong class="text-muted"><i class="fas fa-sync-alt mr-1" aria-hidden="true"></i> Status još nije dostupan</strong>
                                <?php endif ?>
                                <div class="small text-muted mt-2">Kada je dostupan, službeni FLP360 status potvrđuje prelazak.</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="fb-education-note small mt-3"><i class="fas fa-check-circle text-success mr-1" aria-hidden="true"></i> <?= htmlspecialchars((string) ($vip_education_path['current_label'] ?? '')) ?> smjer je aktivan.</div>
                    <?php endif ?>

                    <?php if(!empty($vip_education_path['transition_note']) && !in_array($education_mode, ['builder_focus', 'leader_focus'], true)): ?>
                        <div class="fb-education-note small mt-3"><?= htmlspecialchars((string) $vip_education_path['transition_note']) ?></div>
                    <?php elseif($education_mode === 'builder_focus'): ?>
                        <div class="small text-muted mt-2"><?= htmlspecialchars((string) ($vip_education_path['transition_note'] ?? '')) ?></div>
                    <?php endif ?>

                    <details class="fb-education-details mt-3 pt-3">
                        <summary class="small">Kako napreduju edukacijski smjerovi?</summary>
                        <div class="small text-muted mt-3 mb-2">Program se prilagođava tvojim potvrđenim rezultatima. Kada prijeđeš u napredniji smjer, krećeš od njegova 1. koraka, a raniji rezultati ostaju spremljeni.</div>
                        <div class="fb-education-guide">
                            <?php foreach(($vip_education_path['guide'] ?? []) as $guide_item): ?>
                                <?php $guide_is_current = (string) ($guide_item['key'] ?? '') === $education_guide_current_key; ?>
                                <div class="fb-education-guide-item <?= $guide_is_current ? 'is-current' : '' ?>">
                                    <strong class="d-block small"><span class="fb-education-guide-dot" aria-hidden="true"></span><?= htmlspecialchars((string) ($guide_item['label'] ?? '')) ?><?= $guide_is_current ? ' · tvoj smjer' : '' ?></strong>
                                    <span class="small text-muted"><?= htmlspecialchars((string) ($guide_item['requirement'] ?? '')) ?></span>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <div class="small text-muted mt-3">Novi mjesec počinje od 0 CC, ali jednom ostvareni smjer Aktivator ili Builder ostaje tvoj. Leader ima posebne uvjete opisane iznad.</div>
                        <div class="small text-muted mt-2"><strong>Važno:</strong> Starter, Reaktivacija, Aktivator, Builder i Leader nazivi su smjerova unutar VIP 4 Core edukacije. Ne mijenjaju tvoju službenu Forever poziciju, koju određuju službeni Forever kriteriji i potvrđeni FLP360 podaci.</div>
                    </details>
                </div>
            </section>
        <?php endif ?>

        <?php
        $action = !empty($vip_program['preview_action'])
            ? $vip_program['preview_action']
            : ($data->focus_member ? ($data->focus_member['next_action'] ?? null) : null);
        ?>
        <?php if($action && !empty($vip_program['can_access_education'])): ?>
            <div class="card fb-card fb-action mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="fb-action-meta align-items-center mb-2">
                                <span class="text-uppercase text-muted small mr-1"><?= !empty($action['is_daily_complete']) ? 'Današnji korak' : 'Sljedeći korak' ?> · <?= htmlspecialchars($action['core']) ?></span>
                                <?php if(!empty($action['track_label'])): ?><span class="badge badge-info">Edukacijski smjer: <?= htmlspecialchars($action['track_label']) ?></span><?php endif ?>
                                <?php if(!empty($action['sequence_total']) && empty($action['is_daily_complete'])): ?><span class="badge badge-light">Korak <?= nr($action['sequence_position']) ?>/<?= nr($action['sequence_total']) ?></span><?php endif ?>
                                <?php if(!empty($action['is_weekly_plan'])): ?><span class="badge badge-warning">Nedjeljni prioritet</span><?php endif ?>
                            </div>
                            <h2 class="h4 mb-2"><?= htmlspecialchars($action['title']) ?></h2>
                            <?php if(!empty($action['track_has_advanced'])): ?><div class="alert alert-success py-2 mb-3"><i class="fas fa-level-up-alt mr-1"></i> Otvorila ti se nova edukacijska razina prema najnovijim potvrđenim FLP360 podacima. Krećeš od njezina 1. koraka, a sve što si već dovršio/la ostaje spremljeno.</div><?php endif ?>
                            <?php if(!empty($action['track_goal']) && empty($action['is_weekly_plan'])): ?><div class="small text-muted mb-3"><strong>Fokus tvojeg edukacijskog smjera:</strong> <?= htmlspecialchars($action['track_goal']) ?></div><?php endif ?>
                            <?php if(empty($action['is_daily_complete']) && empty($action['is_program_complete'])): ?><div class="small text-muted mb-3"><strong>Naš stil:</strong> javi se osobno, govori svojim riječima i slušaj više nego što objašnjavaš. Kada je tema proizvod ili posao, prirodno reci da si Forever suradnik i osloni se na aktualne informacije, bez obećavanja zdravstvenog rezultata ili zarade. Primjeri su inspiracija — prilagodi ih odnosu koji već imaš s osobom.</div><?php endif ?>
                            <?php if(!empty($action['is_weekly_plan'])): ?>
                                <div class="fb-weekly-plan mb-3"><strong><i class="fas fa-video mr-1"></i> Danas u 18:00</strong><span class="d-block small mt-1">Pridruži se nekoliko minuta ranije i pripremi popis svojih gostiju.</span><?php if(!empty($vip_marketing_plan['url'])): ?><a href="<?= htmlspecialchars($vip_marketing_plan['url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary mt-2">Otvori Marketing plan</a><?php endif ?></div>
                            <?php endif ?>
                            <?php if(!empty($action['instruction'])): ?><p class="mb-2"><?= htmlspecialchars($action['instruction']) ?></p><?php endif ?>
                            <?php if(!empty($action['checklist'])): ?><ol class="pl-3 mb-3"><?php foreach($action['checklist'] as $item): ?><li class="mb-1"><?= htmlspecialchars($item) ?></li><?php endforeach ?></ol><?php endif ?>
                            <div class="small font-weight-bold"><i class="fas fa-check-circle text-success mr-1"></i> <?= htmlspecialchars($action['success_definition']) ?></div>
                            <?php if(!empty($action['is_daily_complete']) && !empty($action['next_unlock_at_iso'])): ?>
                                <?php
                                $next_unlock_seconds = max(0, (int) ($action['seconds_until_next_unlock'] ?? 0));
                                $next_unlock_hours = (int) floor($next_unlock_seconds / 3600);
                                $next_unlock_minutes = (int) floor(($next_unlock_seconds % 3600) / 60);
                                $next_unlock_seconds_part = $next_unlock_seconds % 60;
                                $next_unlock_countdown = sprintf('%02d:%02d:%02d', $next_unlock_hours, $next_unlock_minutes, $next_unlock_seconds_part);
                                ?>
                                <div
                                    class="fb-next-unlock mt-3"
                                    data-fb-next-unlock-at="<?= htmlspecialchars((string) $action['next_unlock_at_iso']) ?>"
                                    data-fb-next-unlock-server-now="<?= htmlspecialchars((string) ($action['server_now_iso'] ?? '')) ?>"
                                    aria-label="Odbrojavanje do sljedećeg zadatka"
                                >
                                    <i class="fas fa-clock" aria-hidden="true"></i>
                                    <div class="fb-next-unlock-copy small">
                                        <span>Sljedeći zadatak otključava se za</span>
                                        <strong class="fb-next-unlock-value" data-fb-next-unlock-value role="timer" aria-live="off"><?= htmlspecialchars($next_unlock_countdown) ?></strong>
                                        <span class="fb-next-unlock-time">(u <?= htmlspecialchars((string) ($action['next_unlock_time_label'] ?? '00:00')) ?>)</span>
                                    </div>
                                </div>
                            <?php endif ?>
                            <?php if(!empty($action['message_example'])): ?><div class="alert alert-light small mt-3 mb-2"><strong>Ideja za tvoju poruku:</strong><br />“<?= htmlspecialchars($action['message_example']) ?>”<div class="text-muted mt-1">Napiši je onako kako bi je stvarno poslao/la toj osobi.</div></div><?php endif ?>
                            <?php if(!empty($action['fallback'])): ?><div class="alert alert-info small mt-2 mb-2"><strong>Lakša verzija ili druga mogućnost:</strong> <?= htmlspecialchars($action['fallback']) ?></div><?php endif ?>
                            <?php if(!empty($action['adaptive_note'])): ?><div class="alert alert-warning small mt-3 mb-2"><strong>Danas ideš još lakšim tempom:</strong> <?= htmlspecialchars($action['adaptive_note']) ?></div><?php endif ?>
                            <?php if(empty($action['is_daily_complete']) && empty($action['is_program_complete']) && (int) ($action['target'] ?? 0) > 1): ?><div class="fb-quick-step small mt-3"><strong>Treba ti kraći tempo?</strong> Napravi istu radnju u manjem opsegu: puni korak je <?= (int) $action['target'] ?>, a za održavanje ritma danas je dovoljno najmanje <?= (int) $action['quick_target'] ?>.</div><?php elseif(empty($action['is_daily_complete']) && empty($action['is_program_complete']) && !empty($action['fallback'])): ?><div class="fb-quick-step small mt-3"><strong>Jedan mali korak je dovoljan.</strong> Ako ga danas ne možeš odraditi, iskoristi ponuđenu mogućnost iznad ili se javi mentoru.</div><?php elseif(empty($action['is_daily_complete']) && empty($action['is_program_complete'])): ?><div class="fb-quick-step small mt-3"><strong>Jedan mali korak je dovoljan.</strong> Ako zapneš, javi se mentoru.</div><?php endif ?>
                            <div class="small text-muted mt-2"><?= !empty($action['is_daily_complete']) ? 'Za danas nema dodatnih zadataka. Novi korak otključava se u ponoć.' : 'Ovaj korak ostaje aktivan dok ga ne dovršiš. Nakon potvrde novi korak otvorit će se sljedećeg dana.' ?></div>
                            <div class="small text-muted mt-1">Redovitim izvršavanjem koraka gradiš dobre poslovne navike, ostvaruješ više kvalitetnih kontakata i napreduješ prema svojem sljedećem edukacijskom smjeru.</div>
                        </div>
                        <div class="col-lg-4 mt-4 mt-lg-0">
                            <?php if(!empty($vip_program['whatsapp_group_url'])): ?><a href="<?= htmlspecialchars($vip_program['whatsapp_group_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-block fb-vip-whatsapp mb-3"><i class="fab fa-whatsapp mr-1"></i> Otvori VIP WhatsApp grupu</a><?php endif ?>
                        <?php if(!empty($action['can_complete'])): ?>
                            <form method="post">
                                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                                <input type="hidden" name="record_outcome" value="1" />
                                <input type="hidden" name="fbo_id" value="<?= htmlspecialchars($data->focus_member['fbo_id'] ?? '') ?>" />
                                <input type="hidden" name="core_key" value="<?= htmlspecialchars($action['core']) ?>" />
                                <input type="hidden" name="action_key" value="<?= htmlspecialchars($action['key']) ?>" />
                                <input type="hidden" name="root" value="<?= htmlspecialchars($data->requested_root) ?>" />
                                <input type="hidden" name="period" value="<?= htmlspecialchars(substr($dashboard['period'], 0, 7)) ?>" />
                                <div class="form-group">
                                    <label class="small" for="result_type">Što si danas najviše radio/la?</label>
                                    <select name="result_type" id="result_type" class="form-control" required>
                                        <option value="">Odaberi vrstu radnje</option>
                                        <?php foreach($vip_result_type_options as $result_type_key => $result_type_label): ?>
                                            <?php if(!empty($action['allowed_result_types']) && !in_array($result_type_key, $action['allowed_result_types'], true)) continue; ?>
                                            <option value="<?= htmlspecialchars($result_type_key) ?>" <?= $result_type_key === ($action['expected_result_type'] ?? '') ? 'selected' : null ?>><?= htmlspecialchars($result_type_label) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="small" for="outcome_count">Koliko si radnji danas završio/la?</label>
                                    <input type="number" min="<?= max(1, (int) ($action['quick_target'] ?? 1)) ?>" max="999" step="1" required name="outcome_count" id="outcome_count" class="form-control" value="<?= max(1, (int) $action['target']) ?>" />
                                    <div class="small text-muted mt-1">Puni cilj: <?= max(1, (int) ($action['target'] ?? 1)) ?><?= (int) ($action['target'] ?? 1) > 1 ? ' · lakša verzija: ' . max(1, (int) ($action['quick_target'] ?? 1)) : '' ?>. Upiši koliko si danas stvarno napravio/la.</div>
                                </div>
                                <?php if(!empty($action['fallback']) && (int) ($action['target'] ?? 1) === (int) ($action['quick_target'] ?? 1)): ?>
                                    <div class="form-group">
                                        <label class="small" for="completion_variant">Koju si verziju danas završio/la?</label>
                                        <select name="completion_variant" id="completion_variant" class="form-control" required>
                                            <option value="" selected disabled>Odaberi što si danas napravio/la</option>
                                            <option value="standard">Puni korak iz glavne upute</option>
                                            <option value="quick">Lakšu verziju ponuđenu uz zadatak</option>
                                        </select>
                                        <div class="small text-muted mt-1">Obje verzije vrijede kao dovršen korak; odabir nam pomaže prilagoditi sljedeću podršku.</div>
                                    </div>
                                <?php endif ?>
                                <div class="form-group">
                                    <label class="small" for="difficulty">Koliko je korak bio zahtjevan?</label>
                                    <select name="difficulty" id="difficulty" class="form-control" required>
                                        <?php foreach($vip_difficulty_options as $difficulty_key => $difficulty_label): ?>
                                            <option value="<?= htmlspecialchars($difficulty_key) ?>" <?= $difficulty_key === 'normal' ? 'selected' : null ?>><?= htmlspecialchars($difficulty_label) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="small" for="note">Kratka bilješka <span class="text-muted">(neobavezno)</span></label>
                                    <textarea name="note" id="note" rows="2" maxlength="500" class="form-control" placeholder="Što je dobro prošlo ili koji je tvoj sljedeći korak?"></textarea>
                                    <div class="small text-muted mt-1">Za statistiku su dovoljni rezultat i sljedeći korak; imena, kontakti te privatni ili zdravstveni detalji ne trebaju se unositi.</div>
                                </div>
                                <button class="btn btn-success btn-block">Dovrši današnji korak</button>
                                <div class="small text-muted mt-2">Danas spremaš jedan VIP korak. Puni i lakši tempo prate se odvojeno kako bismo ti mogli dati bolju podršku.</div>
                            </form>
                        <?php elseif(!empty($action['is_preview'])): ?><div class="alert alert-info mb-0"><i class="fas fa-eye mr-1"></i> Ovo je sigurna pretpregledna verzija. Dovršavanje zadatka je isključeno.</div>
                        <?php elseif(!empty($action['is_daily_complete'])): ?><div class="alert alert-success mb-0"><i class="fas fa-check-circle mr-1"></i> Današnji zadatak je spremljen. Sljedeći se otključava u ponoć.</div>
                        <?php elseif(!empty($action['is_waiting_for_event_completion'])): ?><div class="alert alert-warning mb-0"><i class="fas fa-clock mr-1"></i> Pripremu možeš odraditi sada. Potvrda prisustva i follow-upa otvara se nakon završetka Marketing plana u <?= htmlspecialchars((string) ($action['marketing_plan']['completion_available_at_display'] ?? '19:30')) ?>.</div>
                        <?php elseif(!empty($action['is_program_complete'])): ?><div class="alert alert-success mb-0"><i class="fas fa-trophy mr-1"></i> Svih 30 koraka ove edukacijske razine uspješno je završeno.</div>
                        <?php else: ?><div class="alert alert-info mb-0"><i class="fas fa-info-circle mr-1"></i> Ovaj korak trenutačno nije dostupan za potvrdu. Osvježi stranicu ili se javi podršci ako se poruka ponovi.</div><?php endif ?>

                        <?php if(empty($action['is_preview']) && empty($action['is_daily_complete']) && empty($action['is_program_complete']) && (int) ($action['target'] ?? 0) > 0): ?>
                            <details class="mt-3">
                                <summary class="small font-weight-bold" style="cursor:pointer">Treba mi lakša verzija ili pomoć mentora</summary>
                                <form method="post" class="mt-3">
                                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                                    <input type="hidden" name="request_vip_help" value="1" />
                                    <input type="hidden" name="fbo_id" value="<?= htmlspecialchars($data->focus_member['fbo_id'] ?? '') ?>" />
                                    <input type="hidden" name="core_key" value="<?= htmlspecialchars($action['core']) ?>" />
                                    <input type="hidden" name="action_key" value="<?= htmlspecialchars($action['key']) ?>" />
                                    <input type="hidden" name="root" value="<?= htmlspecialchars($data->requested_root) ?>" />
                                    <input type="hidden" name="period" value="<?= htmlspecialchars(substr($dashboard['period'], 0, 7)) ?>" />
                                    <label class="small" for="help_note">Kratko napiši što bi ti pomoglo da nastaviš.</label>
                                    <textarea name="help_note" id="help_note" rows="2" minlength="3" maxlength="500" required class="form-control" placeholder="Primjer: danas nemam osobu za ovaj korak i trebam ideju za lakšu verziju."></textarea>
                                    <div class="small text-muted mt-1 mb-2">Dovoljno je opisati kakva ti pomoć treba; imena, kontakti te privatni ili zdravstveni detalji ne trebaju se unositi.</div>
                                    <button class="btn btn-outline-secondary btn-block">Pošalji upit mentoru</button>
                                </form>
                            </details>
                        <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?php if(!$data->is_admin && !empty($dashboard['trend'])): ?>
            <?php $trend_max = $data->is_admin ? max(1000, ...array_map(static fn($row) => (float) $row['total_cc'], $dashboard['trend'])) : max(1, ...array_map(static fn($row) => (float) $row['total_cc'], $dashboard['trend'])); ?>
            <div class="card fb-card mb-4">
                <div class="card-header bg-transparent"><h2 class="h5 mb-1"><?= $data->is_admin ? 'Global Total CC prema cilju 1.000 CC' : 'Moj ukupni CC trend' ?></h2><div class="small text-muted"><?= $data->is_admin ? (!empty($dashboard['official_total_cc']) ? 'Službeni FLP360 Global Total CC. To nije isto što i Total CC pojedinog tržišta; otvoreni mjesec je jasno označen.' : 'Privremeni FCC zbroj osobnih CC; učitaj službeni FLP360 Global Total CC za točan timski cilj.') : 'Duljina crte prikazuje tvoj ukupni CC. Zelena znači da je 4 CC aktivnost ostvarena, a crvena da još nije ostvarena.' ?></div></div>
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
                    <div class="small text-muted"><?php if($data->is_admin): ?>* mjesec nije zatvoren. Skala je 1.000 CC kako bi napredak bio usporediv iz mjeseca u mjesec.<?php else: ?>* mjesec nije zatvoren. Skala se prilagođava najvećem prikazanom Total CC rezultatu. Siva crta znači da podaci o aktivnosti za taj mjesec još nisu dostupni.<?php endif ?></div>
                    <?php if(!$data->is_admin): ?><div class="small text-muted mt-2">Bez potvrđene 4 CC aktivnosti bodovi iz strukture ne ulaze u obračun isplate.</div><?php endif ?>
                </div>
            </div>
        <?php endif ?>

        <?php if(!$data->is_admin && $dashboard['is_manager_view'] && empty($vip_program['is_admin_preview'])): ?>
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
                            <th aria-sort="none"><button type="button" class="fb-sort-button" data-sort-key="sortFcc7" data-sort-type="number" aria-pressed="false">VIP dani · 7 dana <span class="fb-sort-icon" aria-hidden="true">↕</span></button></th>
                            <th aria-sort="none"><button type="button" class="fb-sort-button" data-sort-key="sortNextAction" data-sort-type="text" aria-pressed="false">Sljedeći korak <span class="fb-sort-icon" aria-hidden="true">↕</span></button></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach($data->priority_members as $member): ?>
                            <?php
                            $verified_activity = $member['verified_progress'] ?? [];
                            $activity_source = (string) ($verified_activity['activity_source'] ?? 'unknown');
                            $is_effective_4cc_active = array_key_exists('is_4cc_active', $verified_activity)
                                ? !empty($verified_activity['is_4cc_active'])
                                : !empty($member['is_4cc_active']);
                            $focus_status_label = !empty($member['focus_is_active'])
                                ? 'Focus Group: ACTIVE'
                                : (!empty($member['focus_previous_active']) ? 'Focus Group: ranije aktivan/na' : '');
                            $status_label = $is_effective_4cc_active
                                ? ($activity_source === 'official' ? '4 CC Active · službeno' : '4 CC Active · pomoćni izračun')
                                : ($activity_source === 'official' ? '4 CC nije potvrđen · službeno' : '4 CC nije potvrđen');
                            $status_order = $is_effective_4cc_active
                                ? ($activity_source === 'official' ? 0 : 1)
                                : (!empty($member['focus_is_active']) ? 2 : (!empty($member['focus_previous_active']) ? 3 : 4));
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
                                <td><span class="fb-status-dot <?= $is_effective_4cc_active ? 'bg-success' : 'bg-warning' ?>"></span><?= htmlspecialchars($status_label) ?><?php if($focus_status_label !== ''): ?><div class="small text-muted ml-3"><?= htmlspecialchars($focus_status_label) ?></div><?php endif ?></td>
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
    const countdown = document.querySelector('[data-fb-vip-countdown]');
    if (countdown) {
        const launchAt = Date.parse(countdown.dataset.fbVipCountdown);
        const serverNowAtRender = Date.parse(countdown.dataset.fbVipServerNow);
        const clientNowAtRender = Date.now();
        const fields = {
            days: countdown.querySelector('[data-fb-vip-days]'),
            hours: countdown.querySelector('[data-fb-vip-hours]'),
            minutes: countdown.querySelector('[data-fb-vip-minutes]'),
            seconds: countdown.querySelector('[data-fb-vip-seconds]')
        };
        const pad = function (value) { return String(value).padStart(2, '0'); };
        const updateCountdown = function () {
            const estimatedServerNow = Number.isFinite(serverNowAtRender)
                ? serverNowAtRender + (Date.now() - clientNowAtRender)
                : Date.now();
            const remaining = Math.max(0, Math.floor((launchAt - estimatedServerNow) / 1000));
            const days = Math.floor(remaining / 86400);
            const hours = Math.floor((remaining % 86400) / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;
            if (fields.days) fields.days.textContent = String(days);
            if (fields.hours) fields.hours.textContent = pad(hours);
            if (fields.minutes) fields.minutes.textContent = pad(minutes);
            if (fields.seconds) fields.seconds.textContent = pad(seconds);
            if (remaining === 0 && countdown.dataset.fbVipExpired !== '1') {
                countdown.dataset.fbVipExpired = '1';
                const reloadKey = 'fcc-vip-launch-reload-' + String(launchAt);
                let reloadUrl = '';
                try {
                    const lastReloadAt = Number(window.sessionStorage.getItem(reloadKey) || 0);
                    if (Date.now() - lastReloadAt > 60000) {
                        window.sessionStorage.setItem(reloadKey, String(Date.now()));
                        reloadUrl = window.location.href;
                    }
                } catch (storageError) {
                    /* Some privacy modes block Web Storage. A one-time URL
                     * marker still refreshes the server state without a loop. */
                    const fallbackUrl = new URL(window.location.href);
                    const fallbackKey = 'vip_launch_refresh';
                    if (fallbackUrl.searchParams.get(fallbackKey) !== String(launchAt)) {
                        fallbackUrl.searchParams.set(fallbackKey, String(launchAt));
                        reloadUrl = fallbackUrl.toString();
                    }
                }
                if (reloadUrl) {
                    window.setTimeout(function () { window.location.replace(reloadUrl); }, 1200);
                }
            }
        };
        updateCountdown();
        window.setInterval(updateCountdown, 1000);
    }

    const nextUnlock = document.querySelector('[data-fb-next-unlock-at]');
    if (nextUnlock) {
        const unlockAt = Date.parse(nextUnlock.dataset.fbNextUnlockAt);
        const serverNowAtRender = Date.parse(nextUnlock.dataset.fbNextUnlockServerNow);
        const clientWallNowAtRender = Date.now();
        const hasMonotonicClock = window.performance && typeof window.performance.now === 'function';
        const monotonicNowAtRender = hasMonotonicClock ? window.performance.now() : NaN;
        const value = nextUnlock.querySelector('[data-fb-next-unlock-value]');
        let intervalId = null;
        let reloadScheduled = false;
        const pad = function (part) { return String(part).padStart(2, '0'); };
        const reloadAtUnlock = function () {
            if (reloadScheduled) return;
            reloadScheduled = true;
            if (intervalId !== null) window.clearInterval(intervalId);
            window.setTimeout(function () { window.location.reload(); }, 700);
        };
        const updateNextUnlock = function () {
            if (!Number.isFinite(unlockAt) || !Number.isFinite(serverNowAtRender)) {
                nextUnlock.hidden = true;
                if (intervalId !== null) window.clearInterval(intervalId);
                return;
            }
            /* Wall time advances while a laptop sleeps; monotonic time protects
             * the counter when the device clock is moved backwards. The server
             * timestamp remains the only authority for the actual boundary. */
            const wallElapsed = Math.max(0, Date.now() - clientWallNowAtRender);
            const monotonicElapsed = hasMonotonicClock
                ? Math.max(0, window.performance.now() - monotonicNowAtRender)
                : 0;
            const estimatedServerNow = serverNowAtRender + Math.max(wallElapsed, monotonicElapsed);
            const remaining = Math.max(0, Math.ceil((unlockAt - estimatedServerNow) / 1000));
            const hours = Math.floor(remaining / 3600);
            const minutes = Math.floor((remaining % 3600) / 60);
            const seconds = remaining % 60;
            if (value) {
                value.textContent = pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
                value.setAttribute('aria-label', hours + ' sati, ' + minutes + ' minuta i ' + seconds + ' sekundi');
            }
            if (remaining === 0) reloadAtUnlock();
        };

        updateNextUnlock();
        if (!nextUnlock.hidden && !reloadScheduled) {
            intervalId = window.setInterval(updateNextUnlock, 1000);
        }
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) updateNextUnlock();
        });
        window.addEventListener('pageshow', updateNextUnlock);
    }

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
