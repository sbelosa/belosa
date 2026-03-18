<?php defined('ALTUMCODE') || die() ?>

<?php /* Custom code: FC-2026-03-18: admin live email automations index */ ?>
<?php if(settings()->main->breadcrumbs_is_enabled): ?>
    <nav aria-label="breadcrumb">
        <ol class="custom-breadcrumbs small">
            <li class="active" aria-current="page"><?= l('admin_automations.breadcrumb') ?></li>
        </ol>
    </nav>
<?php endif ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h1 class="h3 mb-2 mb-md-1 text-truncate"><i class="fas fa-fw fa-xs fa-wave-square text-primary-900 mr-2"></i> <?= l('admin_automations.header') ?></h1>
        <p class="text-muted mb-0"><?= l('admin_automations.subheader') ?></p>
    </div>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<div class="row mb-4">
    <div class="col-12 col-lg-4 mb-3 mb-lg-0">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase mb-2"><?= l('admin_automations.location_title') ?></div>
                <div class="font-weight-bold mb-2"><?= l('admin_automations.location_text') ?></div>
                <div class="small text-muted"><?= l('admin_automations.location_help') ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted small text-uppercase mb-2"><?= l('admin_automations.live_rules_title') ?></div>
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="font-weight-bold mb-1"><?= l('admin_automations.live_rules.enter_title') ?></div>
                        <div class="small text-muted"><?= l('admin_automations.live_rules.enter_text') ?></div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="font-weight-bold mb-1"><?= l('admin_automations.live_rules.exit_title') ?></div>
                        <div class="small text-muted"><?= l('admin_automations.live_rules.exit_text') ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="font-weight-bold mb-1"><?= l('admin_automations.live_rules.batch_title') ?></div>
                        <div class="small text-muted"><?= l('admin_automations.live_rules.batch_text') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive table-custom-container">
    <table class="table table-custom">
        <thead>
        <tr>
            <th><?= l('global.name') ?></th>
            <th><?= l('admin_automations.segment') ?></th>
            <th><?= l('admin_automations.live_users') ?></th>
            <th><?= l('admin_automations.active_enrollments') ?></th>
            <th><?= l('admin_automations.due_now') ?></th>
            <th><?= l('admin_automations.sent_emails') ?></th>
            <th><?= l('global.status') ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($data->automations as $automation): ?>
            <tr>
                <td class="text-nowrap">
                    <div class="font-weight-bold"><?= e($automation->name) ?></div>
                    <div class="small text-muted"><?= sprintf(l('admin_automations.steps_count'), nr($automation->steps_total)) ?></div>
                </td>
                <td class="text-nowrap">
                    <span class="badge badge-light"><?= l('admin_automations.segment.' . $automation->segment) ?></span>
                </td>
                <td class="text-nowrap"><?= nr($automation->segment_count) ?></td>
                <td class="text-nowrap">
                    <span class="badge badge-secondary"><?= nr($automation->active_enrollments) ?></span>
                    <div class="small text-muted mt-1"><?= sprintf(l('admin_automations.completed_exited'), nr($automation->completed_enrollments), nr($automation->exited_enrollments)) ?></div>
                </td>
                <td class="text-nowrap"><span class="badge badge-info"><?= nr($automation->due_enrollments) ?></span></td>
                <td class="text-nowrap"><span class="badge badge-light"><i class="fas fa-fw fa-envelope mr-1"></i> <?= nr($automation->total_sent_emails) ?></span></td>
                <td class="text-nowrap">
                    <?php if($automation->status == 'active'): ?>
                        <span class="badge badge-success"><i class="fas fa-fw fa-check mr-1"></i> <?= l('admin_automations.status.active') ?></span>
                    <?php else: ?>
                        <span class="badge badge-warning"><i class="fas fa-fw fa-pause mr-1"></i> <?= l('admin_automations.status.paused') ?></span>
                    <?php endif ?>
                </td>
                <td class="text-nowrap text-right"><a href="<?= url('admin/automation-update/' . $automation->automation_id) ?>" class="btn btn-sm btn-outline-primary"><?= l('admin_automations.configure') ?></a></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php /* /Custom code: FC-2026-03-18 */ ?>