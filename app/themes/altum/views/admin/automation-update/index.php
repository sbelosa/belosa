<?php defined('ALTUMCODE') || die() ?>

<?php /* Custom code: FC-2026-03-19: expose Brevo admin setup state in analytics view */ ?>
<?php $brevo_webhook_secret = fc_get_brevo_webhook_secret() ?>
<?php $brevo_settings_url = url('admin/settings/smtp') ?>
<?php /* /Custom code: FC-2026-03-19 */ ?>

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
    <!-- Custom code: FC-2026-03-19: quick actions for automation analytics and Brevo settings -->
    <div class="d-flex flex-column flex-md-row mt-3 mt-md-0">
        <a href="<?= $brevo_settings_url ?>" class="btn btn-outline-primary mb-2 mb-md-0 mr-md-2">Brevo postavke</a>
        <a href="<?= url('admin/automations') ?>" class="btn btn-gray-300"><?= l('admin_automation_update.back') ?></a>
    </div>
    <!-- /Custom code: FC-2026-03-19 -->
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

<?php /* Custom code: FC-2026-03-19: Brevo analytics overview */ ?>
<div class="row mb-4">
    <div class="col-6 col-xl-3 mb-3">
        <div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Delivered</div><div class="h3 mb-0"><?= nr($data->analytics['summary']['delivered']) ?></div><div class="small text-muted mt-2"><?= $data->analytics['rates']['delivery_rate'] ?>% delivery rate</div></div></div>
    </div>
    <div class="col-6 col-xl-3 mb-3">
        <div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Unique opens</div><div class="h3 mb-0"><?= nr($data->analytics['summary']['opened']) ?></div><div class="small text-muted mt-2"><?= $data->analytics['rates']['open_rate'] ?>% open rate</div></div></div>
    </div>
    <div class="col-6 col-xl-3 mb-3">
        <div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Unique clicks</div><div class="h3 mb-0"><?= nr($data->analytics['summary']['clicked']) ?></div><div class="small text-muted mt-2"><?= $data->analytics['rates']['click_rate'] ?>% click rate</div></div></div>
    </div>
    <div class="col-6 col-xl-3 mb-3">
        <div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Goal completed</div><div class="h3 mb-0"><?= nr($data->analytics['summary']['goal_completed']) ?></div><div class="small text-muted mt-2"><?= $data->analytics['rates']['goal_rate'] ?>% conversion rate</div></div></div>
    </div>
</div>

<div class="card mb-4 border-primary">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-column flex-lg-row">
            <div class="mb-3 mb-lg-0">
                <h2 class="h5 mb-2">Brevo webhook setup</h2>
                <div class="text-muted small mb-2">Webhook URL</div>
                <div><code data-copy><?= url('webhook-brevo-email') ?></code></div>
                <div class="text-muted small mt-3 mb-2">Authentication method in Brevo</div>
                <div class="small">No authentication</div>
                <div class="text-muted small mt-3 mb-2">Tracked events</div>
                <div class="small">sent, delivered, deferred, opened, click, softBounce, hardBounce, blocked, invalid, spam, unsubscribed</div>
            </div>
            <div class="bg-gray-100 rounded p-3">
                <div class="text-muted small text-uppercase mb-2">Server config</div>
                <div class="small mb-1"><code data-copy>BREVO_API_KEY</code></div>
                <div class="small mb-1">Header name: <code data-copy>X-FC-Brevo-Secret</code></div>
                <div class="small mb-1">Header value: <?php if($brevo_webhook_secret !== ''): ?><code data-copy><?= e($brevo_webhook_secret) ?></code><?php else: ?><span class="text-warning">nije postavljeno</span><?php endif ?></div>
                <div class="small text-muted">Webhook mora poslati isti header i istu tajnu vrijednost. Uredi u <a href="<?= $brevo_settings_url ?>">Admin Settings &raquo; SMTP</a>.</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Brevo funnel by step</h2>
            <div class="small text-muted">CTOR <?= $data->analytics['rates']['click_to_open_rate'] ?>%</div>
        </div>

        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th>Step</th>
                    <th>Sent</th>
                    <th>Delivered</th>
                    <th>Opened</th>
                    <th>Clicked</th>
                    <th>Goal completed</th>
                    <th>Rates</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($data->analytics['per_step'] as $step_analytics): ?>
                    <tr>
                        <td class="text-nowrap">
                            <div class="font-weight-bold">Step <?= (int) $step_analytics['step']->step_order ?></div>
                            <div class="small text-muted"><?= e($step_analytics['step']->subject) ?></div>
                        </td>
                        <td class="text-nowrap"><?= nr($step_analytics['sent']) ?></td>
                        <td class="text-nowrap"><?= nr($step_analytics['delivered']) ?></td>
                        <td class="text-nowrap"><?= nr($step_analytics['opened']) ?></td>
                        <td class="text-nowrap"><?= nr($step_analytics['clicked']) ?></td>
                        <td class="text-nowrap"><?= nr($step_analytics['goal_completed']) ?></td>
                        <td class="text-nowrap small">
                            <div>D <?= $step_analytics['rates']['delivery_rate'] ?>%</div>
                            <div>O <?= $step_analytics['rates']['open_rate'] ?>%</div>
                            <div>C <?= $step_analytics['rates']['click_rate'] ?>%</div>
                            <div>Goal <?= $step_analytics['rates']['goal_rate'] ?>%</div>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->analytics['per_step'])): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Još nema poslanih automation poruka za Brevo analitiku.</td>
                    </tr>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Recent message statuses</h2>
            <div class="small text-muted">Posljednjih 10 poruka</div>
        </div>

        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th><?= l('global.user') ?></th>
                    <th>Step</th>
                    <th>Status</th>
                    <th>Brevo message ID</th>
                    <th>Timeline</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($data->analytics['recent_messages'] as $message): ?>
                    <tr>
                        <td class="text-nowrap">
                            <?php if($message->user): ?>
                                <div class="font-weight-bold"><?= e($message->user->name ?: ('#' . $message->user_id)) ?></div>
                                <div class="small text-muted"><?= e($message->user->email) ?></div>
                            <?php else: ?>
                                <div class="font-weight-bold">#<?= (int) $message->user_id ?></div>
                                <div class="small text-muted"><?= e($message->recipient_email) ?></div>
                            <?php endif ?>
                        </td>
                        <td class="text-nowrap">#<?= (int) $message->automation_step_id ?></td>
                        <td class="text-nowrap"><span class="badge badge-light"><?= e(str_replace('_', ' ', $message->status)) ?></span></td>
                        <td class="text-nowrap small"><?php if($message->brevo_message_id): ?><code data-copy><?= e($message->brevo_message_id) ?></code><?php else: ?>-<?php endif ?></td>
                        <td class="small text-muted text-nowrap">
                            <div>Sent <?= \Altum\Date::get($message->sent_datetime, 2) ?></div>
                            <div>Last <?= $message->last_event_datetime ? \Altum\Date::get($message->last_event_datetime, 2) : '-' ?></div>
                            <div>Open <?= $message->first_open_datetime ? \Altum\Date::get($message->first_open_datetime, 2) : '-' ?></div>
                            <div>Click <?= $message->first_click_datetime ? \Altum\Date::get($message->first_click_datetime, 2) : '-' ?></div>
                        </td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->analytics['recent_messages'])): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Još nema spremljenih Brevo poruka za ovu automatizaciju.</td>
                    </tr>
                <?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php /* /Custom code: FC-2026-03-19 */ ?>

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
                        <td class="text-nowrap"><span class="badge badge-light"><?php $log_action_label = l('admin_automation_update.log_action.' . $log->action); echo strpos($log_action_label, 'admin_automation_update.log_action.') === 0 ? e(ucwords(str_replace('_', ' ', $log->action))) : $log_action_label; ?></span></td>
                        <td class="text-nowrap">
                            <!-- Custom code: FC-2026-03-18: show user name and email in activity list -->
                            <?php if($log->user): ?>
                                <div class="font-weight-bold"><?= e($log->user->name ?: ('#' . $log->user_id)) ?></div>
                                <div class="small text-muted"><?= e($log->user->email) ?></div>
                            <?php elseif($log->user_id): ?>
                                <div class="font-weight-bold">#<?= $log->user_id ?></div>
                            <?php else: ?>
                                -
                            <?php endif ?>
                            <!-- /Custom code: FC-2026-03-18 -->
                        </td>
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