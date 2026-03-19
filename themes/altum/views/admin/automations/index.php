<?php defined('ALTUMCODE') || die() ?>

<?php $summary = $data->hub['summary']; ?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
    <div>
        <h1 class="h3 mb-2"><i class="fas fa-fw fa-xs fa-envelope-open-text text-primary-900 mr-2"></i> <?= l('admin_automations.header') ?></h1>
        <p class="text-muted mb-0">Pregled svih mailova, automatizacija i Brevo analitike.</p>
    </div>

    <div class="d-flex flex-column flex-md-row mt-3 mt-lg-0">
        <a href="<?= url('admin/broadcast-create') ?>" class="btn btn-outline-primary mb-2 mb-md-0 mr-md-2">Novi mail</a>
        <a href="<?= url('admin/automation-create') ?>" class="btn btn-primary mb-2 mb-md-0 mr-md-2">Nova automatizacija</a>
        <a href="<?= url('admin/settings/smtp') ?>" class="btn btn-gray-300">Brevo postavke</a>
    </div>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<div class="row mb-4">
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Mailovi</div><div class="h3 mb-0"><?= nr($data->hub['totals']['broadcasts']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Automatizacije</div><div class="h3 mb-0"><?= nr($data->hub['totals']['automations']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Poslano</div><div class="h3 mb-0"><?= nr($summary['sent']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Isporučeno</div><div class="h3 mb-0"><?= nr($summary['delivered']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Otvoreno</div><div class="h3 mb-0"><?= nr($summary['opened']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Klik</div><div class="h3 mb-0"><?= nr($summary['clicked']) ?></div></div></div></div>
</div>

<div class="alert alert-light border mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
        <div>
            <strong>Ručno odjavljeni kontakti:</strong> <?= nr($summary['unsubscribed']) ?>
            <div class="text-muted small">Odjave su vidljive unutar pojedinog maila ili automatizacije, zajedno s email adresom i vremenom odjave.</div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-1">Mailovi</h2>
                <div class="small text-muted">Prikazano <?= nr(count($data->broadcasts)) ?> mailova<?php if(($data->broadcasts_total ?? 0) > count($data->broadcasts)): ?> od ukupno <?= nr($data->broadcasts_total) ?><?php endif ?>.</div>
            </div>
            <div class="d-flex align-items-center">
                <?php if(($data->broadcasts_total ?? 0) > ($data->broadcasts_default_display_limit ?? 5)): ?>
                    <?php if(!empty($data->show_all_broadcasts)): ?>
                        <a href="<?= url('admin/automations') ?>" class="btn btn-sm btn-outline-secondary mr-2">Sakrij starije</a>
                    <?php else: ?>
                        <a href="<?= url('admin/automations?show_all_broadcasts=1') ?>" class="btn btn-sm btn-outline-secondary mr-2">Prikaži sve</a>
                    <?php endif ?>
                <?php endif ?>

                <a href="<?= url('admin/broadcast-create') ?>" class="btn btn-sm btn-primary">Dodaj mail</a>
            </div>
        </div>

        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th>Naziv</th>
                    <th>Segment</th>
                    <th>Status</th>
                    <th>Poslano</th>
                    <th>Isporučeno</th>
                    <th>Otvoreno</th>
                    <th>Kliknuto</th>
                    <th>Odjavljeno</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($data->broadcasts as $broadcast): ?>
                    <?php $broadcast_summary = $broadcast->analytics['summary']; ?>
                    <?php $broadcast_link = in_array($broadcast->status, ['sent', 'processing']) ? url('admin/broadcast-view/' . $broadcast->broadcast_id) : url('admin/broadcast-update/' . $broadcast->broadcast_id); ?>
                    <tr>
                        <td class="text-nowrap"><div class="font-weight-bold"><a href="<?= $broadcast_link ?>"><?= e($broadcast->name) ?></a></div><div class="small text-muted"><?= e($broadcast->subject) ?></div></td>
                        <td class="text-nowrap"><span class="badge badge-light"><?= l('admin_broadcasts.segment.' . $broadcast->segment) ?></span></td>
                        <td class="text-nowrap"><span class="badge badge-light"><?php if($broadcast->status == 'draft'): ?>Skica<?php elseif($broadcast->status == 'processing'): ?>U tijeku<?php else: ?>Poslano<?php endif ?></span></td>
                        <td class="text-nowrap"><?= nr($broadcast_summary['sent']) ?></td>
                        <td class="text-nowrap"><?= nr($broadcast_summary['delivered']) ?></td>
                        <td class="text-nowrap"><?= nr($broadcast_summary['opened']) ?></td>
                        <td class="text-nowrap"><?= nr($broadcast_summary['clicked']) ?></td>
                        <td class="text-nowrap">
                            <?php if((int) ($broadcast_summary['unsubscribed'] ?? 0) > 0): ?>
                                <a href="#" data-toggle="modal" data-target="#broadcast_unsubscribed_modal_<?= $broadcast->broadcast_id ?>"><?= nr($broadcast_summary['unsubscribed']) ?></a>
                            <?php else: ?>
                                <?= nr($broadcast_summary['unsubscribed']) ?>
                            <?php endif ?>
                        </td>
                        <td class="text-nowrap text-right"><a href="<?= $broadcast_link ?>" class="btn btn-sm btn-outline-primary">Otvori</a></td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->broadcasts)): ?><tr><td colspan="9" class="text-center text-muted py-4">Još nema kreiranih mailova.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Automatizacije</h2>
            <a href="<?= url('admin/automation-create') ?>" class="btn btn-sm btn-primary">Dodaj automatizaciju</a>
        </div>

        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th>Naziv</th>
                    <th>Skupina</th>
                    <th>Živi korisnici</th>
                    <th>Aktivni</th>
                    <th>Poslano</th>
                    <th>Isporučeno</th>
                    <th>Otvoreno</th>
                    <th>Kliknuto</th>
                    <th>Odjavljeno</th>
                    <th>Goal</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($data->automations as $automation): ?>
                    <?php $automation_summary = $automation->analytics['summary']; ?>
                    <tr>
                        <td class="text-nowrap"><div class="font-weight-bold"><a href="<?= url('admin/automation-update/' . $automation->automation_id) ?>"><?= e($automation->name) ?></a></div><div class="small text-muted"><?= nr($automation->steps_total) ?> koraka</div></td>
                        <td class="text-nowrap"><span class="badge badge-light"><?= e($automation->segment_label) ?></span></td>
                        <td class="text-nowrap"><?= nr($automation->segment_count) ?></td>
                        <td class="text-nowrap"><?= nr($automation->active_enrollments) ?></td>
                        <td class="text-nowrap"><?= nr($automation_summary['sent']) ?></td>
                        <td class="text-nowrap"><?= nr($automation_summary['delivered']) ?></td>
                        <td class="text-nowrap"><?= nr($automation_summary['opened']) ?></td>
                        <td class="text-nowrap"><?= nr($automation_summary['clicked']) ?></td>
                        <td class="text-nowrap">
                            <?php if((int) ($automation_summary['unsubscribed'] ?? 0) > 0): ?>
                                <a href="#" data-toggle="modal" data-target="#automation_unsubscribed_modal_<?= $automation->automation_id ?>"><?= nr($automation_summary['unsubscribed']) ?></a>
                            <?php else: ?>
                                <?= nr($automation_summary['unsubscribed']) ?>
                            <?php endif ?>
                        </td>
                        <td class="text-nowrap"><?= nr($automation_summary['goal_completed']) ?></td>
                        <td class="text-nowrap text-right"><a href="<?= url('admin/automation-update/' . $automation->automation_id) ?>" class="btn btn-sm btn-outline-primary">Otvori</a></td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->automations)): ?><tr><td colspan="11" class="text-center text-muted py-4">Još nema automatizacija.</td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php foreach($data->broadcasts as $broadcast): ?>
    <?php if((int) (($broadcast->analytics['summary']['unsubscribed'] ?? 0)) > 0): ?>
        <div class="modal fade" id="broadcast_unsubscribed_modal_<?= $broadcast->broadcast_id ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Odjavljeni korisnici: <?= e($broadcast->name) ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="small text-muted mb-3">Prikazano zadnjih <?= nr($broadcast->unsubscribed_messages_limit) ?> odjava<?php if((int) ($broadcast->analytics['summary']['unsubscribed'] ?? 0) > (int) $broadcast->unsubscribed_messages_limit): ?> od ukupno <?= nr($broadcast->analytics['summary']['unsubscribed']) ?><?php endif ?>.</div>
                        <div class="table-responsive table-custom-container">
                            <table class="table table-custom mb-0">
                                <thead>
                                <tr>
                                    <th>Korisnik</th>
                                    <th>Email</th>
                                    <th>Vrijeme odjave</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach($broadcast->unsubscribed_messages as $message): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= e($message->user->name ?? ('#' . (int) ($message->user_id ?? 0))) ?></td>
                                        <td class="text-nowrap"><?= e($message->user->email ?? $message->recipient_email) ?></td>
                                        <td class="text-nowrap"><?= $message->unsubscribe_datetime ? \Altum\Date::get($message->unsubscribe_datetime, 2) : '-' ?></td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="<?= in_array($broadcast->status, ['sent', 'processing']) ? url('admin/broadcast-view/' . $broadcast->broadcast_id . '?status_filter=unsubscribed') : url('admin/broadcast-update/' . $broadcast->broadcast_id) ?>" class="btn btn-outline-primary">Otvori detalje</a>
                        <button type="button" class="btn btn-gray-300" data-dismiss="modal">Zatvori</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
<?php endforeach ?>

<?php foreach($data->automations as $automation): ?>
    <?php if((int) (($automation->analytics['summary']['unsubscribed'] ?? 0)) > 0): ?>
        <div class="modal fade" id="automation_unsubscribed_modal_<?= $automation->automation_id ?>" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Odjavljeni korisnici: <?= e($automation->name) ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="small text-muted mb-3">Prikazano zadnjih <?= nr($automation->unsubscribed_messages_limit) ?> odjava<?php if((int) ($automation->analytics['summary']['unsubscribed'] ?? 0) > (int) $automation->unsubscribed_messages_limit): ?> od ukupno <?= nr($automation->analytics['summary']['unsubscribed']) ?><?php endif ?>.</div>
                        <div class="table-responsive table-custom-container">
                            <table class="table table-custom mb-0">
                                <thead>
                                <tr>
                                    <th>Korisnik</th>
                                    <th>Email</th>
                                    <th>Vrijeme odjave</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach($automation->unsubscribed_messages as $message): ?>
                                    <tr>
                                        <td class="text-nowrap"><?= e($message->user->name ?? ('#' . (int) ($message->user_id ?? 0))) ?></td>
                                        <td class="text-nowrap"><?= e($message->user->email ?? $message->recipient_email) ?></td>
                                        <td class="text-nowrap"><?= $message->unsubscribe_datetime ? \Altum\Date::get($message->unsubscribe_datetime, 2) : '-' ?></td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="<?= url('admin/automation-update/' . $automation->automation_id . '?status_filter=unsubscribed') ?>" class="btn btn-outline-primary">Otvori detalje</a>
                        <button type="button" class="btn btn-gray-300" data-dismiss="modal">Zatvori</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
<?php endforeach ?>