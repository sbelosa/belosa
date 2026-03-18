<?php defined('ALTUMCODE') || die() ?>

<?php /* Custom code: FC-2026-03-18: admin live email automation update */ ?>
<?php if(settings()->main->breadcrumbs_is_enabled): ?>
    <nav aria-label="breadcrumb">
        <ol class="custom-breadcrumbs small">
            <li>
                <a href="<?= url('admin/automations') ?>"><?= l('admin_automations.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
            </li>
            <li class="active" aria-current="page"><?= l('admin_automation_update.breadcrumb') ?></li>
        </ol>
    </nav>
<?php endif ?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h1 class="h3 mb-2 mb-md-1 text-truncate"><i class="fas fa-fw fa-xs fa-wave-square text-primary-900 mr-2"></i> <?= l('admin_automation_update.header') ?></h1>
        <p class="text-muted mb-0"><?= l('admin_automation_update.subheader') ?></p>
    </div>
    <a href="<?= url('admin/automations') ?>" class="btn btn-gray-300 mt-3 mt-md-0"><?= l('admin_automation_update.back') ?></a>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<div class="row mb-4">
    <div class="col-6 col-xl-3 mb-3">
        <div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1"><?= l('admin_automations.live_users') ?></div><div class="h3 mb-0"><?= nr($data->stats['segment_count']) ?></div></div></div>
    </div>
    <div class="col-6 col-xl-3 mb-3">
        <div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1"><?= l('admin_automations.active_enrollments') ?></div><div class="h3 mb-0"><?= nr($data->stats['active_enrollments']) ?></div></div></div>
    </div>
    <div class="col-6 col-xl-3 mb-3">
        <div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1"><?= l('admin_automations.due_now') ?></div><div class="h3 mb-0"><?= nr($data->stats['due_enrollments']) ?></div></div></div>
    </div>
    <div class="col-6 col-xl-3 mb-3">
        <div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1"><?= l('admin_automations.sent_emails') ?></div><div class="h3 mb-0"><?= nr($data->automation->total_sent_emails) ?></div></div></div>
    </div>
</div>

<form action="" method="post" role="form">
    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

    <div class="card mb-4 <?= \Altum\Alerts::has_field_errors() ? 'border-danger' : null ?>">
        <div class="card-body">
            <div class="row">
                <div class="col-lg-5">
                    <div class="form-group">
                        <label for="name"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.name') ?></label>
                        <input type="text" id="name" name="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= e($data->automation->name) ?>" maxlength="128" required="required" />
                        <?= \Altum\Alerts::output_field_error('name') ?>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <label for="status"><i class="fas fa-fw fa-sm fa-toggle-on text-muted mr-1"></i> <?= l('global.status') ?></label>
                        <select id="status" name="status" class="custom-select">
                            <option value="active" <?= $data->automation->status == 'active' ? 'selected="selected"' : null ?>><?= l('admin_automations.status.active') ?></option>
                            <option value="paused" <?= $data->automation->status == 'paused' ? 'selected="selected"' : null ?>><?= l('admin_automations.status.paused') ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="batch_size"><i class="fas fa-fw fa-sm fa-layer-group text-muted mr-1"></i> <?= l('admin_automation_update.batch_size') ?></label>
                        <input type="number" min="1" max="200" id="batch_size" name="batch_size" class="form-control" value="<?= $data->automation->settings->batch_size ?>" />
                        <small class="form-text text-muted"><?= l('admin_automation_update.batch_size_help') ?></small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6 mb-3 mb-lg-0">
                    <div class="bg-gray-100 rounded p-3 h-100">
                        <div class="small text-uppercase text-muted mb-2"><?= l('admin_automations.segment') ?></div>
                        <div class="font-weight-bold mb-1"><?= l('admin_automations.segment.' . $data->automation->segment) ?></div>
                        <div class="small text-muted"><?= l('admin_automation_update.segment_help') ?></div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="bg-gray-100 rounded p-3 h-100">
                        <div class="small text-uppercase text-muted mb-2"><?= l('admin_automation_update.variables_title') ?></div>
                        <div class="small text-muted"><?= sprintf(l('global.variables'), '<code data-copy>' . implode('</code>, <code data-copy>', ['{{USER:NAME}}', '{{USER:EMAIL}}', '{{SALES_LINKS_PAGE}}', '{{USER:LOGIN_LINK}}', '{{WEBSITE_TITLE}}']) . '</code>') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php foreach($data->steps as $step): ?>
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0"><?= sprintf(l('admin_automation_update.step_header'), $step->step_order) ?></h2>
                    <span class="badge badge-light"><?= sprintf(l('admin_automation_update.step_delay_badge'), nr($step->delay_minutes)) ?></span>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-group">
                            <label for="subject_<?= $step->automation_step_id ?>"><i class="fas fa-fw fa-sm fa-heading text-muted mr-1"></i> <?= l('admin_broadcasts.subject') ?></label>
                            <input type="text" id="subject_<?= $step->automation_step_id ?>" name="subject_<?= $step->automation_step_id ?>" class="form-control <?= \Altum\Alerts::has_field_errors('subject_' . $step->automation_step_id) ? 'is-invalid' : null ?>" value="<?= isset($_POST['subject_' . $step->automation_step_id]) ? e($_POST['subject_' . $step->automation_step_id]) : e($step->subject) ?>" maxlength="128" required="required" />
                            <?= \Altum\Alerts::output_field_error('subject_' . $step->automation_step_id) ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label for="delay_minutes_<?= $step->automation_step_id ?>"><i class="fas fa-fw fa-sm fa-clock text-muted mr-1"></i> <?= l('admin_automation_update.delay_minutes') ?></label>
                            <input type="number" min="0" id="delay_minutes_<?= $step->automation_step_id ?>" name="delay_minutes_<?= $step->automation_step_id ?>" class="form-control" value="<?= isset($_POST['delay_minutes_' . $step->automation_step_id]) ? (int) $_POST['delay_minutes_' . $step->automation_step_id] : (int) $step->delay_minutes ?>" />
                            <small class="form-text text-muted"><?= $step->step_order == 1 ? l('admin_automation_update.delay_minutes_first_help') : l('admin_automation_update.delay_minutes_help') ?></small>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label for="content_<?= $step->automation_step_id ?>"><i class="fas fa-fw fa-sm fa-paragraph text-muted mr-1"></i> <?= l('admin_broadcasts.content') ?></label>
                    <textarea id="content_<?= $step->automation_step_id ?>" name="content_<?= $step->automation_step_id ?>" rows="8" class="form-control <?= \Altum\Alerts::has_field_errors('content_' . $step->automation_step_id) ? 'is-invalid' : null ?>" required="required"><?= isset($_POST['content_' . $step->automation_step_id]) ? e($_POST['content_' . $step->automation_step_id]) : e($step->content) ?></textarea>
                    <?= \Altum\Alerts::output_field_error('content_' . $step->automation_step_id) ?>
                    <small class="form-text text-muted"><?= l('admin_automation_update.content_help') ?></small>
                </div>
            </div>
        </div>
    <?php endforeach ?>

    <button type="submit" class="btn btn-lg btn-primary btn-block mb-4"><?= l('global.update') ?></button>
</form>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0"><?= l('admin_automation_update.activity_title') ?></h2>
            <div class="small text-muted"><?= sprintf(l('admin_automation_update.activity_meta'), nr($data->stats['completed_enrollments']), nr($data->stats['exited_enrollments'])) ?></div>
        </div>

        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th><?= l('global.datetime') ?></th>
                    <th><?= l('admin_automation_update.activity_action') ?></th>
                    <th><?= l('global.user') ?></th>
                    <th><?= l('global.details') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($data->logs as $log): ?>
                    <tr>
                        <td class="text-nowrap"><?= \Altum\Date::get($log->datetime, 2) ?></td>
                        <td class="text-nowrap"><span class="badge badge-light"><?= l('admin_automation_update.log_action.' . $log->action) ?></span></td>
                        <td class="text-nowrap"><?= $log->user_id ? '#' . $log->user_id : '-' ?></td>
                        <td class="small text-muted"><?= isset($log->details->message) ? e($log->details->message) : (isset($log->details->exit_reason) ? e($log->details->exit_reason) : '-') ?></td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->logs)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4"><?= l('admin_automation_update.activity_empty') ?></td>
                    </tr>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php /* /Custom code: FC-2026-03-18 */ ?>