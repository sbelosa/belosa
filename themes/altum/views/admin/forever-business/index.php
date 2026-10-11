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
                <div class="card-header"><h2 class="h5 mb-0"><?= htmlspecialchars(fcc_t('1. Uvezi novi izvještaj'), ENT_QUOTES, 'UTF-8') ?></h2></div>
                <div class="card-body">
                    <div class="alert alert-light border"><strong><?= htmlspecialchars(fcc_t('Najjednostavniji tok:'), ENT_QUOTES, 'UTF-8') ?></strong> <?= htmlspecialchars(fcc_t('učitaj Downline CSV → Focus Group XLSX → po potrebi 4 CC Active XLSX. Svaki izvještaj prvo se provjerava, a ista datoteka se ne može dvaput zbrojiti.'), ENT_QUOTES, 'UTF-8') ?></div>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                        <input type="hidden" name="preview_import" value="1" />
                        <div class="form-group"><label for="report_file"><?= htmlspecialchars(fcc_t('FLP360 izvještaj (.csv ili .xlsx)'), ENT_QUOTES, 'UTF-8') ?></label><input type="file" class="form-control-file" id="report_file" name="report_file" accept=".csv,.xlsx" required /></div>
                        <div class="form-row">
                            <div class="form-group col-md-4"><label for="root_fbo_id"><?= htmlspecialchars(fcc_t('Glavni Forever ID'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" id="root_fbo_id" name="root_fbo_id" maxlength="12" value="<?= htmlspecialchars($data->default_root_fbo_id) ?>" /></div>
                            <div class="form-group col-md-5"><label for="root_name"><?= htmlspecialchars(fcc_t('Naziv glavnog tima'), ENT_QUOTES, 'UTF-8') ?></label><input class="form-control" id="root_name" name="root_name" maxlength="160" value="<?= htmlspecialchars($data->default_root_name) ?>" /></div>
                            <div class="form-group col-md-3"><label for="report_period"><?= htmlspecialchars(fcc_t('Mjesec izvještaja'), ENT_QUOTES, 'UTF-8') ?></label><input type="month" class="form-control" id="report_period" name="report_period" value="<?= htmlspecialchars(date('Y-m')) ?>" required /></div>
                        </div>
                        <button class="btn btn-primary"><i class="fas fa-search mr-2"></i><?= htmlspecialchars(fcc_t('Provjeri prije importa'), ENT_QUOTES, 'UTF-8') ?></button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-xl-5 mb-4">
            <div class="card h-100">
                <div class="card-header"><h2 class="h5 mb-0"><?= htmlspecialchars(fcc_t('2. Sigurna potvrda'), ENT_QUOTES, 'UTF-8') ?></h2></div>
                <div class="card-body">
                    <?php if($preview): $ps = $preview['summary']; ?>
                        <dl class="row mb-3">
                            <dt class="col-6"><?= htmlspecialchars(fcc_t('Datoteka'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-6 text-break"><?= htmlspecialchars($preview['original_name']) ?></dd>
                            <dt class="col-6"><?= htmlspecialchars(fcc_t('Vrsta'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-6"><?= htmlspecialchars($ps['kind']) ?></dd>
                            <dt class="col-6"><?= htmlspecialchars(fcc_t('Redaka'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-6"><?= nr($ps['rows']) ?></dd>
                            <dt class="col-6"><?= htmlspecialchars(fcc_t('Managera'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-6"><?= nr($ps['managers']) ?></dd>
                            <dt class="col-6"><?= htmlspecialchars(fcc_t('Mjeseci'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-6"><?= htmlspecialchars(implode(', ', $ps['periods'])) ?></dd>
                            <dt class="col-6"><?= htmlspecialchars(fcc_t('Najnoviji CC'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-6"><?= $format_cc($ps['latest_personal_cc']) ?></dd>
                            <dt class="col-6"><?= htmlspecialchars(fcc_t('4 CC aktivnih'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-6"><?= nr($ps['latest_4cc_active']) ?></dd>
                            <?php if(!empty($ps['focus_rows'])): ?><dt class="col-6"><?= htmlspecialchars(fcc_t('Focus prioriteta'), ENT_QUOTES, 'UTF-8') ?></dt><dd class="col-6"><?= nr($ps['focus_rows']) ?></dd><?php endif ?>
                        </dl>
                        <form method="post">
                            <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                            <input type="hidden" name="apply_import" value="1" />
                            <input type="hidden" name="preview_token" value="<?= htmlspecialchars($preview['token']) ?>" />
                            <button class="btn btn-success btn-block"><i class="fas fa-check mr-2"></i><?= htmlspecialchars(fcc_t('Potvrdi i osvježi tim'), ENT_QUOTES, 'UTF-8') ?></button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted"><?= htmlspecialchars(fcc_t('Ovdje će se prije upisa prikazati broj redaka, managera, mjeseci, CC zbroj i eventualne pogreške.'), ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="small text-muted"><i class="fas fa-shield-alt mr-2"></i><?= htmlspecialchars(fcc_t('Import je transakcijski: ako jedan ključni korak ne uspije, novi podaci se ne primjenjuju.'), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
    <?php if(!$data->self_only_mode): ?>
    <div class="card mb-4">
        <div class="card-header"><h2 class="h5 mb-0"><?= htmlspecialchars(fcc_t('Managerski pristup'), ENT_QUOTES, 'UTF-8') ?></h2></div>
        <div class="card-body">
            <p class="text-muted"><?= htmlspecialchars(fcc_t('Manager vidi samo vlastito podstablo. “Co-owner” omogućuje drugom FCC računu uvid u isto podstablo. Podudaranja su prijedlozi i nikada se ne aktiviraju bez potvrde.'), ENT_QUOTES, 'UTF-8') ?></p>
            <form method="post" class="mb-4" onsubmit="return confirm('Aktivirati managerski pristup samo FCC računima s potpuno jednakim Forever ID-em?');">
                <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                <input type="hidden" name="grant_exact_managers" value="1" />
                <button class="btn btn-success"><i class="fas fa-user-shield mr-2"></i><?= htmlspecialchars(fcc_t('Aktiviraj sva točna Forever ID podudaranja'), ENT_QUOTES, 'UTF-8') ?></button>
                <div class="small text-muted mt-2"><?= htmlspecialchars(fcc_t('Ne aktivira prijedloge temeljene samo na imenu ili e-mailu.'), ENT_QUOTES, 'UTF-8') ?></div>
            </form>
            <form method="post" class="form-row align-items-end border rounded p-3 mb-4">
                <input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" />
                <input type="hidden" name="grant_access" value="1" />
                <input type="hidden" name="period" value="<?= htmlspecialchars(substr($dashboard['period'], 0, 7)) ?>" />
                <div class="form-group col-lg-5"><label><?= htmlspecialchars(fcc_t('FCC korisnik'), ENT_QUOTES, 'UTF-8') ?></label><select name="user_id" class="form-control" required><option value=""><?= htmlspecialchars(fcc_t('Odaberi korisnika'), ENT_QUOTES, 'UTF-8') ?></option><?php foreach($data->users as $user): ?><option value="<?= (int) $user->user_id ?>"><?= htmlspecialchars($user->name . ' · ' . $user->email) ?></option><?php endforeach ?></select></div>
                <div class="form-group col-lg-4"><label><?= htmlspecialchars(fcc_t('Forever manager / korijen'), ENT_QUOTES, 'UTF-8') ?></label><select name="fbo_id" class="form-control" required><option value=""><?= htmlspecialchars(fcc_t('Odaberi managera'), ENT_QUOTES, 'UTF-8') ?></option><?php foreach($data->managers as $manager): ?><option value="<?= htmlspecialchars($manager->fbo_id) ?>"><?= htmlspecialchars($manager->name . ' · ' . $manager->fbo_id) ?></option><?php endforeach ?></select></div>
                <div class="form-group col-lg-2"><label><?= htmlspecialchars(fcc_t('Uloga'), ENT_QUOTES, 'UTF-8') ?></label><select name="access_role" class="form-control"><option value="manager"><?= htmlspecialchars(fcc_t('Manager'), ENT_QUOTES, 'UTF-8') ?></option><option value="co_owner"><?= htmlspecialchars(fcc_t('Co-owner'), ENT_QUOTES, 'UTF-8') ?></option></select></div>
                <div class="form-group col-lg-1"><button class="btn btn-primary btn-block" title="<?= htmlspecialchars(fcc_t('Dodijeli'), ENT_QUOTES, 'UTF-8') ?>"><i class="fas fa-plus"></i></button></div>
            </form>

            <?php $suggestion_count = 0; foreach($data->suggestions as $suggestion) $suggestion_count += count($suggestion['candidates']); ?>
            <?php if($suggestion_count): ?>
                <h3 class="h6"><?= htmlspecialchars(fcc_t('Prijedlozi podudaranja'), ENT_QUOTES, 'UTF-8') ?></h3>
                <div class="table-responsive mb-4"><table class="table table-sm"><thead><tr><th><?= htmlspecialchars(fcc_t('Forever manager'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Predloženi FCC račun'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Razlog'), ENT_QUOTES, 'UTF-8') ?></th><th></th></tr></thead><tbody>
                <?php foreach($data->suggestions as $suggestion): foreach($suggestion['candidates'] as $candidate): $already_active = in_array((int) $candidate['user']->user_id, $suggestion['active_user_ids'], true); ?>
                    <tr><td><?= htmlspecialchars($suggestion['manager']->name) ?><div class="small text-muted"><?= htmlspecialchars($suggestion['manager']->fbo_id) ?></div></td><td><?= htmlspecialchars($candidate['user']->name) ?><div class="small text-muted"><?= htmlspecialchars($candidate['user']->email) ?></div></td><td><span class="badge badge-light"><?= htmlspecialchars($candidate['reason']) ?></span></td><td><?php if($already_active): ?><span class="badge badge-success"><?= htmlspecialchars(fcc_t('Aktivno'), ENT_QUOTES, 'UTF-8') ?></span><?php else: ?><form method="post"><input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" /><input type="hidden" name="grant_access" value="1" /><input type="hidden" name="user_id" value="<?= (int) $candidate['user']->user_id ?>" /><input type="hidden" name="fbo_id" value="<?= htmlspecialchars($suggestion['manager']->fbo_id) ?>" /><input type="hidden" name="access_role" value="manager" /><button class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(fcc_t('Potvrdi'), ENT_QUOTES, 'UTF-8') ?></button></form><?php endif ?></td></tr>
                <?php endforeach; endforeach ?>
                </tbody></table></div>
            <?php endif ?>

            <h3 class="h6"><?= htmlspecialchars(fcc_t('Aktivni i opozvani pristupi'), ENT_QUOTES, 'UTF-8') ?></h3>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th><?= htmlspecialchars(fcc_t('FCC korisnik'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Tim'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Uloga'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Status'), ENT_QUOTES, 'UTF-8') ?></th><th></th></tr></thead><tbody>
            <?php if(empty($data->access)): ?><tr><td colspan="5" class="text-muted"><?= htmlspecialchars(fcc_t('Još nema dodijeljenih managerskih pristupa.'), ENT_QUOTES, 'UTF-8') ?></td></tr><?php endif ?>
            <?php foreach($data->access as $access): ?>
                <tr><td><?= htmlspecialchars($access->user_name ?: $access->user_email) ?></td><td><?= htmlspecialchars($access->member_name ?: $access->fbo_id) ?></td><td><?= htmlspecialchars($access->access_role) ?></td><td><span class="badge badge-<?= $access->status === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($access->status) ?></span></td><td><?php if($access->status === 'active'): ?><form method="post"><input type="hidden" name="global_token" value="<?= \Altum\Csrf::get('global_token') ?>" /><input type="hidden" name="revoke_access" value="1" /><input type="hidden" name="access_id" value="<?= (int) $access->access_id ?>" /><button class="btn btn-sm btn-outline-danger"><?= htmlspecialchars(fcc_t('Opozovi'), ENT_QUOTES, 'UTF-8') ?></button></form><?php endif ?></td></tr>
            <?php endforeach ?>
            </tbody></table></div>
        </div>
    </div>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header"><h2 class="h5 mb-0"><?= htmlspecialchars(fcc_t('Privatnost pregleda'), ENT_QUOTES, 'UTF-8') ?></h2></div>
            <div class="card-body"><div class="alert alert-success mb-0"><strong><?= htmlspecialchars(fcc_t('Individualni način je aktivan.'), ENT_QUOTES, 'UTF-8') ?></strong> <?= htmlspecialchars(fcc_t('Svaki suradnik vidi samo podatke svojeg Forever ID-a. Parametar u URL-u ni ranije dodijeljen managerski pristup ne može proširiti njegov pregled. Samo administratorski račun vidi cijelu strukturu i može mijenjati odabrano podstablo.'), ENT_QUOTES, 'UTF-8') ?></div></div>
        </div>
    <?php endif ?>

    <div class="card mb-4">
        <div class="card-header"><h2 class="h5 mb-0"><?= htmlspecialchars(fcc_t('Povijest importa'), ENT_QUOTES, 'UTF-8') ?></h2></div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th><?= htmlspecialchars(fcc_t('Vrijeme'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Datoteka'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Vrsta'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Redaka'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Razdoblje'), ENT_QUOTES, 'UTF-8') ?></th><th><?= htmlspecialchars(fcc_t('Status'), ENT_QUOTES, 'UTF-8') ?></th></tr></thead><tbody>
        <?php if(empty($data->imports)): ?><tr><td colspan="6" class="text-muted"><?= htmlspecialchars(fcc_t('Nema uvezenih izvještaja.'), ENT_QUOTES, 'UTF-8') ?></td></tr><?php endif ?>
        <?php foreach($data->imports as $import): ?>
            <tr><td><?= htmlspecialchars($import->created_at) ?></td><td class="text-break"><?= htmlspecialchars($import->original_name) ?></td><td><?= htmlspecialchars($import->report_kind) ?></td><td><?= nr($import->row_count) ?></td><td><?= htmlspecialchars(($import->period_start ?: '—') . ' – ' . ($import->period_end ?: '—')) ?></td><td><span class="badge badge-<?= $import->status === 'completed' ? 'success' : ($import->status === 'failed' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($import->status) ?></span></td></tr>
        <?php endforeach ?>
        </tbody></table></div>
    </div>

    <div class="alert alert-info"><strong><?= htmlspecialchars(fcc_t('FLP360 sinkronizacija:'), ENT_QUOTES, 'UTF-8') ?></strong> <?= htmlspecialchars(fcc_t('potvrđeni su službeni CSV/XLSX izvozi za Downline, Focus Group i 4 CC Active. Produkcija prima samo potpisane sinkronizacije, odbija duplikate i ne sprema FLP360 lozinku. Pristupni podaci ostaju u privatnoj datoteci na administratorskom računalu; ako FLP360 zatraži novu prijavu ili promijeni izvoz, sinkronizacija se zaustavlja i prijavljuje pogrešku.'), ENT_QUOTES, 'UTF-8') ?></div>
</div>
