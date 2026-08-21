<?php defined('ALTUMCODE') || die() ?>
<?php
$dashboard = $data->dashboard;
$preview = $data->preview;
$format_cc = static fn($value) => number_format((float) $value, 3, ',', '.');
?>

<div class="container-fluid">
    <?= include_view(THEME_PATH . 'views/admin/leader-operating-system/partials/section_nav.php', ['active' => 'forever']) ?>
    <?= \Altum\Alerts::output_alerts() ?>

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
        <div><h1 class="h3 mb-1"><i class="fas fa-fw fa-file-import text-primary mr-2"></i><?= l('admin_leader_operating_system.forever.sync_title') ?></h1><p class="text-muted mb-0"><?= l('admin_leader_operating_system.forever.sync_subtitle') ?></p></div>
        <div class="mt-3 mt-lg-0">
            <a href="<?= url('admin/leader-operating-system-forever') ?>" class="btn btn-primary"><i class="fas fa-chart-line fa-sm mr-2"></i><?= l('admin_leader_operating_system.forever.open_analytics') ?></a>
            <a href="<?= url('forever-business') ?>" class="btn btn-outline-primary ml-1"><i class="fas fa-external-link-alt fa-sm mr-2"></i><?= l('admin_leader_operating_system.forever.open_member') ?></a>
        </div>
    </div>

    <div class="alert alert-info"><i class="fas fa-info-circle mr-2"></i><?= l('admin_leader_operating_system.forever.sync_note') ?></div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">1. Uvezi novi izvještaj</h2></div>
                <div class="card-body">
                    <div class="alert alert-light border"><strong>Najjednostavniji tok:</strong> učitaj Downline CSV → Focus Group XLSX → po potrebi 4 CC Active XLSX. Svaki izvještaj prvo se provjerava, a ista datoteka se ne može dvaput zbrojiti.</div>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                        <input type="hidden" name="preview_import" value="1" />
                        <div class="form-group"><label for="report_file">FLP360 izvještaj (.csv ili .xlsx)</label><input type="file" class="form-control-file" id="report_file" name="report_file" accept=".csv,.xlsx" required /></div>
                        <div class="form-row">
                            <div class="form-group col-md-4"><label for="root_fbo_id">Glavni Forever ID</label><input class="form-control" id="root_fbo_id" name="root_fbo_id" maxlength="12" value="<?= htmlspecialchars($data->default_root_fbo_id) ?>" /></div>
                            <div class="form-group col-md-5"><label for="root_name">Naziv glavnog tima</label><input class="form-control" id="root_name" name="root_name" maxlength="160" value="<?= htmlspecialchars($data->default_root_name) ?>" /></div>
                            <div class="form-group col-md-3"><label for="report_period">Mjesec izvještaja</label><input type="month" class="form-control" id="report_period" name="report_period" value="<?= htmlspecialchars(date('Y-m')) ?>" required /></div>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-search mr-2"></i>Provjeri prije importa</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-5 mb-4">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0">2. Sigurna potvrda</h2></div>
                <div class="card-body">
                    <?php if($preview): $ps = $preview['summary']; ?>
                        <dl class="row mb-3">
                            <dt class="col-6">Datoteka</dt><dd class="col-6 text-break"><?= htmlspecialchars($preview['original_name']) ?></dd>
                            <dt class="col-6">Vrsta</dt><dd class="col-6"><?= htmlspecialchars($ps['kind']) ?></dd>
                            <dt class="col-6">Redaka</dt><dd class="col-6"><?= nr($ps['rows']) ?></dd>
                            <dt class="col-6">Managera</dt><dd class="col-6"><?= nr($ps['managers']) ?></dd>
                            <dt class="col-6">Mjeseci</dt><dd class="col-6"><?= htmlspecialchars(implode(', ', $ps['periods'])) ?></dd>
                            <dt class="col-6">Najnoviji CC</dt><dd class="col-6"><?= $format_cc($ps['latest_personal_cc']) ?></dd>
                            <dt class="col-6">4 CC aktivnih</dt><dd class="col-6"><?= nr($ps['latest_4cc_active']) ?></dd>
                            <?php if(!empty($ps['focus_rows'])): ?><dt class="col-6">Focus prioriteta</dt><dd class="col-6"><?= nr($ps['focus_rows']) ?></dd><?php endif ?>
                        </dl>
                        <form method="post">
                            <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                            <input type="hidden" name="apply_import" value="1" />
                            <input type="hidden" name="preview_token" value="<?= htmlspecialchars($preview['token']) ?>" />
                            <button class="btn btn-success btn-block"><i class="fas fa-check mr-2"></i>Potvrdi i osvježi tim</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted">Ovdje će se prije upisa prikazati broj redaka, managera, mjeseci, CC zbroj i eventualne pogreške.</p>
                        <div class="small text-muted"><i class="fas fa-shield-alt mr-2"></i>Import je transakcijski: ako jedan ključni korak ne uspije, novi podaci se ne primjenjuju.</div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
    <?php if(!$data->self_only_mode): ?>
    <div class="card mb-4">
        <div class="card-header"><h2 class="h5 mb-0">Managerski pristup</h2></div>
        <div class="card-body">
            <p class="text-muted">Manager vidi samo vlastito podstablo. “Co-owner” omogućuje drugom FCC računu uvid u isto podstablo. Podudaranja su prijedlozi i nikada se ne aktiviraju bez potvrde.</p>
            <form method="post" class="mb-4" onsubmit="return confirm('Aktivirati managerski pristup samo FCC računima s potpuno jednakim Forever ID-em?');">
                <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                <input type="hidden" name="grant_exact_managers" value="1" />
                <button class="btn btn-success"><i class="fas fa-user-shield mr-2"></i>Aktiviraj sva točna Forever ID podudaranja</button>
                <div class="small text-muted mt-2">Ne aktivira prijedloge temeljene samo na imenu ili e-mailu.</div>
            </form>
            <form method="post" class="form-row align-items-end border rounded p-3 mb-4">
                <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                <input type="hidden" name="grant_access" value="1" />
                <input type="hidden" name="period" value="<?= htmlspecialchars(substr($dashboard['period'], 0, 7)) ?>" />
                <div class="form-group col-lg-5"><label>FCC korisnik</label><select name="user_id" class="form-control" required><option value="">Odaberi korisnika</option><?php foreach($data->users as $user): ?><option value="<?= (int) $user->user_id ?>"><?= htmlspecialchars($user->name . ' · ' . $user->email) ?></option><?php endforeach ?></select></div>
                <div class="form-group col-lg-4"><label>Forever manager / korijen</label><select name="fbo_id" class="form-control" required><option value="">Odaberi managera</option><?php foreach($data->managers as $manager): ?><option value="<?= htmlspecialchars($manager->fbo_id) ?>"><?= htmlspecialchars($manager->name . ' · ' . $manager->fbo_id) ?></option><?php endforeach ?></select></div>
                <div class="form-group col-lg-2"><label>Uloga</label><select name="access_role" class="form-control"><option value="manager">Manager</option><option value="co_owner">Co-owner</option></select></div>
                <div class="form-group col-lg-1"><button class="btn btn-primary btn-block" title="Dodijeli"><i class="fas fa-plus"></i></button></div>
            </form>

            <?php $suggestion_count = 0; foreach($data->suggestions as $suggestion) $suggestion_count += count($suggestion['candidates']); ?>
            <?php if($suggestion_count): ?>
                <h3 class="h6">Prijedlozi podudaranja</h3>
                <div class="table-responsive mb-4"><table class="table table-sm"><thead><tr><th>Forever manager</th><th>Predloženi FCC račun</th><th>Razlog</th><th></th></tr></thead><tbody>
                <?php foreach($data->suggestions as $suggestion): foreach($suggestion['candidates'] as $candidate): $already_active = in_array((int) $candidate['user']->user_id, $suggestion['active_user_ids'], true); ?>
                    <tr><td><?= htmlspecialchars($suggestion['manager']->name) ?><div class="small text-muted"><?= htmlspecialchars($suggestion['manager']->fbo_id) ?></div></td><td><?= htmlspecialchars($candidate['user']->name) ?><div class="small text-muted"><?= htmlspecialchars($candidate['user']->email) ?></div></td><td><span class="badge badge-light"><?= htmlspecialchars($candidate['reason']) ?></span></td><td><?php if($already_active): ?><span class="badge badge-success">Aktivno</span><?php else: ?><form method="post"><input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" /><input type="hidden" name="grant_access" value="1" /><input type="hidden" name="user_id" value="<?= (int) $candidate['user']->user_id ?>" /><input type="hidden" name="fbo_id" value="<?= htmlspecialchars($suggestion['manager']->fbo_id) ?>" /><input type="hidden" name="access_role" value="manager" /><button class="btn btn-sm btn-outline-primary">Potvrdi</button></form><?php endif ?></td></tr>
                <?php endforeach; endforeach ?>
                </tbody></table></div>
            <?php endif ?>

            <h3 class="h6">Aktivni i opozvani pristupi</h3>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>FCC korisnik</th><th>Tim</th><th>Uloga</th><th>Status</th><th></th></tr></thead><tbody>
            <?php if(empty($data->access)): ?><tr><td colspan="5" class="text-muted">Još nema dodijeljenih managerskih pristupa.</td></tr><?php endif ?>
            <?php foreach($data->access as $access): ?>
                <tr><td><?= htmlspecialchars($access->user_name ?: $access->user_email) ?></td><td><?= htmlspecialchars($access->member_name ?: $access->fbo_id) ?></td><td><?= htmlspecialchars($access->access_role) ?></td><td><span class="badge badge-<?= $access->status === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($access->status) ?></span></td><td><?php if($access->status === 'active'): ?><form method="post"><input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" /><input type="hidden" name="revoke_access" value="1" /><input type="hidden" name="access_id" value="<?= (int) $access->access_id ?>" /><button class="btn btn-sm btn-outline-danger">Opozovi</button></form><?php endif ?></td></tr>
            <?php endforeach ?>
            </tbody></table></div>
        </div>
    </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header"><h2 class="h5 mb-0">Privatnost pregleda</h2></div>
            <div class="card-body"><div class="alert alert-success mb-0"><strong>Individualni način je aktivan.</strong> Svaki suradnik vidi samo podatke svojeg Forever ID-a. Parametar u URL-u ni ranije dodijeljen managerski pristup ne može proširiti njegov pregled. Samo administratorski račun vidi cijelu strukturu i može mijenjati odabrano podstablo.</div></div>
        </div>
    <?php endif ?>

    <div class="card mb-4">
        <div class="card-header"><h2 class="h5 mb-0">Povijest importa</h2></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Vrijeme</th><th>Datoteka</th><th>Vrsta</th><th>Redaka</th><th>Razdoblje</th><th>Status</th></tr></thead><tbody>
        <?php if(empty($data->imports)): ?><tr><td colspan="6" class="text-muted">Nema uvezenih izvještaja.</td></tr><?php endif ?>
        <?php foreach($data->imports as $import): ?>
            <tr><td><?= htmlspecialchars($import->created_at) ?></td><td class="text-break"><?= htmlspecialchars($import->original_name) ?></td><td><?= htmlspecialchars($import->report_kind) ?></td><td><?= nr($import->row_count) ?></td><td><?= htmlspecialchars(($import->period_start ?: '—') . ' – ' . ($import->period_end ?: '—')) ?></td><td><span class="badge badge-<?= $import->status === 'completed' ? 'success' : ($import->status === 'failed' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($import->status) ?></span></td></tr>
        <?php endforeach ?>
        </tbody></table></div>
    </div>

    <div class="alert alert-info"><strong>FLP360 sinkronizacija:</strong> potvrđeni su službeni CSV/XLSX izvozi za Downline, Focus Group i 4 CC Active. Produkcija prima samo potpisane sinkronizacije, odbija duplikate i ne sprema FLP360 lozinku. Pristupni podaci ostaju u privatnoj datoteci na administratorskom računalu; ako FLP360 zatraži novu prijavu ili promijeni izvoz, sinkronizacija se zaustavlja i prijavljuje pogrešku.</div>
</div>
