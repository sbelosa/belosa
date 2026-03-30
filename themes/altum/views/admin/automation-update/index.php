<?php defined('ALTUMCODE') || die() ?>

<?php $base_url = url('admin/automation-update/' . $data->automation->automation_id) ?>
<?php $segment = $data->automation->segment ?>
<?php $settings = $data->automation->settings ?>
<?php $automation_shortcodes = ['{{USER:NAME}}', '{{USER:EMAIL}}', '{{SALES_LINKS_PAGE}}', '{{FOREVER_CARD_APPLICATION_URL}}', '{{FCC_VIDEO_URL}}', '{{USER:LOGIN_LINK}}', '{{WEBSITE_TITLE}}']; ?>
<?php $automation_status_labels = [
    'sent' => l('admin_automation_update.summary_sent'),
    'delivered' => l('admin_automation_update.summary_delivered'),
    'opened' => l('admin_automation_update.summary_opened'),
    'clicked' => l('admin_automation_update.summary_clicked'),
    'goal_completed' => l('admin_automation_update.summary_goal_completed'),
    'unsubscribed' => l('admin_automation_update.summary_unsubscribed'),
    'all' => l('admin_automation_update.summary_all_records'),
]; ?>
<?php $get_delay_parts = static function($minutes) {
    $minutes = max(0, (int) $minutes);

    if($minutes !== 0 && $minutes % 1440 === 0) {
        return ['value' => (int) ($minutes / 1440), 'unit' => 'days'];
    }

    if($minutes !== 0 && $minutes % 60 === 0) {
        return ['value' => (int) ($minutes / 60), 'unit' => 'hours'];
    }

    return ['value' => $minutes, 'unit' => 'minutes'];
}; ?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
    <div>
        <h1 class="h3 mb-2"><i class="fas fa-fw fa-xs fa-wave-square text-primary-900 mr-2"></i> <?= e($data->automation->name) ?></h1>
        <p class="text-muted mb-0"><?= l('admin_automation_update.page_text') ?></p>
    </div>

    <div class="d-flex flex-column flex-md-row mt-3 mt-lg-0">
        <a href="<?= url('admin/settings/smtp') ?>" class="btn btn-outline-primary mb-2 mb-md-0 mr-md-2"><?= l('admin_automation_update.smtp_settings') ?></a>
        <a href="<?= url('admin/automations') ?>" class="btn btn-gray-300"><?= l('admin_automation_update.back') ?></a>
    </div>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<?php if(!empty($data->needs_webhook_attention)): ?>
    <div class="alert alert-warning mb-4">
        <div class="font-weight-bold mb-1"><?= l('admin_settings.smtp.brevo_webhook_warning_title') ?></div>
        <div><?= l('admin_settings.smtp.brevo_webhook_warning_body') ?></div>
    </div>
<?php endif ?>

<!-- Custom code: FC-2026-03-19: mail studio update layout -->
<div class="mail-studio-page">
    <div class="card mail-studio-hero mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-3 mb-lg-0">
                    <span class="mail-studio-eyebrow"><?= l('admin_automation_update.hero_eyebrow') ?></span>
                    <h2 class="mail-studio-title mb-2"><?= l('admin_automation_update.hero_title') ?></h2>
                    <p class="text-muted mb-0"><?= l('admin_automation_update.hero_text') ?></p>
                </div>
                <div class="col-lg-5">
                    <div class="mail-studio-hero__stats">
                        <div class="mail-studio-stat">
                            <span class="mail-studio-stat__label"><?= l('admin_automation_update.status_label') ?></span>
                            <strong class="mail-studio-stat__value"><?= $data->automation->status == 'active' ? l('admin_automations.status.active') : l('admin_automations.status.paused') ?></strong>
                        </div>
                        <div class="mail-studio-stat">
                            <span class="mail-studio-stat__label"><?= l('admin_automation_create.stat_steps') ?></span>
                            <strong class="mail-studio-stat__value"><?= count($data->steps) ?></strong>
                        </div>
                        <div class="mail-studio-stat">
                            <span class="mail-studio-stat__label"><?= l('admin_automation_update.segment_label') ?></span>
                            <strong class="mail-studio-stat__value"><?= e($data->automation->segment_label ?: $data->automation->segment) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="row mb-4">
    <?php foreach(['sent', 'delivered', 'opened', 'clicked', 'goal_completed', 'unsubscribed'] as $status_key): ?>
        <div class="col-6 col-xl-2 mb-3">
            <a href="<?= $base_url . '?status_filter=' . $status_key ?>" class="card h-100 text-decoration-none <?= $data->status_filter === $status_key ? 'border-primary' : '' ?>">
                <div class="card-body">
                    <div class="text-muted small text-uppercase mb-1"><?= $automation_status_labels[$status_key] ?></div>
                    <div class="h3 mb-0 text-body"><?= nr($data->analytics['summary'][$status_key]) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach ?>
    <div class="col-6 col-xl-2 mb-3">
        <a href="<?= $base_url . '?status_filter=all' ?>" class="card h-100 text-decoration-none <?= $data->status_filter === 'all' ? 'border-primary' : '' ?>">
            <div class="card-body">
                <div class="text-muted small text-uppercase mb-1"><?= l('admin_automation_update.summary_all_records') ?></div>
                <div class="h3 mb-0 text-body"><?= nr($data->analytics['summary']['total'] ?? 0) ?></div>
            </div>
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="small text-muted"><?php if(($data->filtered_messages_total ?? 0) > $data->messages_display_limit): ?><?= sprintf(l('admin_automation_update.records_shown_total'), nr($data->messages_display_limit), nr($data->filtered_messages_total)) ?><?php else: ?><?= sprintf(l('admin_automation_update.records_shown'), nr($data->messages_display_limit)) ?><?php endif ?></div>
            <div class="small text-muted"><?= l('admin_automation_update.filter_label') ?>: <?= e($automation_status_labels[$data->status_filter] ?? $data->status_filter) ?></div>
        </div>
        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th><?= l('admin_automation_update.table_user') ?></th>
                    <th><?= l('admin_automation_update.table_step') ?></th>
                    <th><?= l('admin_automation_update.table_status') ?></th>
                    <th><?= l('admin_automation_update.table_sent') ?></th>
                    <th><?= l('admin_automation_update.table_delivered') ?></th>
                    <th><?= l('admin_automation_update.table_opened') ?></th>
                    <th><?= l('admin_automation_update.table_clicked') ?></th>
                    <th><?= l('admin_automation_update.table_unsubscribed') ?></th>
                    <th><?= l('admin_automation_update.table_message') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($data->filtered_messages as $message): ?>
                    <tr>
                        <td class="text-nowrap"><?php if($message->user): ?><div class="font-weight-bold"><?= e($message->user->name ?: ('#' . $message->user_id)) ?></div><div class="small text-muted"><?= e($message->user->email) ?></div><?php else: ?><div class="font-weight-bold">#<?= (int) $message->user_id ?></div><div class="small text-muted"><?= e($message->recipient_email) ?></div><?php endif ?></td>
                        <td class="text-nowrap">#<?= (int) $message->automation_step_id ?></td>
                        <td class="text-nowrap"><span class="badge badge-light"><?= e($automation_status_labels[$message->status] ?? ucwords(str_replace('_', ' ', $message->status))) ?></span></td>
                        <td class="text-nowrap"><?= \Altum\Date::get($message->sent_datetime, 2) ?></td>
                        <td class="text-nowrap"><?= $message->delivered_datetime ? \Altum\Date::get($message->delivered_datetime, 2) : '-' ?></td>
                        <td class="text-nowrap"><?= $message->first_open_datetime ? \Altum\Date::get($message->first_open_datetime, 2) : '-' ?></td>
                        <td class="text-nowrap"><?= $message->first_click_datetime ? \Altum\Date::get($message->first_click_datetime, 2) : '-' ?></td>
                        <td class="text-nowrap"><?= $message->unsubscribe_datetime ? \Altum\Date::get($message->unsubscribe_datetime, 2) : '-' ?></td>
                        <td class="text-nowrap small"><?php if($message->brevo_message_id): ?><code data-copy><?= e($message->brevo_message_id) ?></code><?php else: ?>-<?php endif ?></td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->filtered_messages)): ?><tr><td colspan="9" class="text-center text-muted py-4"><?= l('admin_automation_update.no_recipients') ?></td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th><?= l('admin_automation_update.table_step') ?></th>
                    <th><?= l('admin_automation_update.table_sent') ?></th>
                    <th><?= l('admin_automation_update.table_delivered') ?></th>
                    <th><?= l('admin_automation_update.table_opened') ?></th>
                    <th><?= l('admin_automation_update.table_clicked') ?></th>
                    <th><?= l('admin_automation_update.summary_goal_completed') ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($data->analytics['per_step'] as $step_analytics): ?>
                    <tr>
                        <td><div class="font-weight-bold"><?= sprintf(l('admin_automation_update.step_header'), (int) $step_analytics['step']->step_order) ?></div><div class="small text-muted"><?= e($step_analytics['step']->subject) ?></div></td>
                        <td><?= nr($step_analytics['sent']) ?></td>
                        <td><?= nr($step_analytics['delivered']) ?></td>
                        <td><?= nr($step_analytics['opened']) ?></td>
                        <td><?= nr($step_analytics['clicked']) ?></td>
                        <td><?= nr($step_analytics['goal_completed']) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<form action="" method="post" role="form">
    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
    <input type="hidden" id="active_new_steps_count" name="active_new_steps_count" value="<?= (int) $data->active_new_steps_count ?>" />

    <div class="card mb-4 mail-studio-card mail-builder-shortcodes-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                <div>
                        <h2 class="h5 mb-1"><?= l('admin_mail_builder.shortcodes_title') ?></h2>
                        <p class="text-muted mb-0"><?= l('admin_automation_update.shortcodes_help') ?></p>
                </div>
                    <div class="small text-muted mt-2 mt-lg-0"><?= l('admin_mail_builder.copy_hint') ?></div>
            </div>

            <div class="mail-shortcodes-grid">
                <?php foreach($automation_shortcodes as $shortcode): ?>
                    <code class="mail-shortcode-tag" data-copy><?= e($shortcode) ?></code>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <div class="card mb-4 mail-studio-card mail-studio-card--soft <?= \Altum\Alerts::has_field_errors() ? 'border-danger' : null ?>">
        <div class="card-body">
            <div class="mail-studio-section-heading">
                <div>
                    <span class="mail-studio-section-heading__eyebrow"><?= l('admin_automation_update.settings_eyebrow') ?></span>
                    <h2 class="h4 mb-1"><?= l('admin_automation_update.settings_title') ?></h2>
                    <p class="text-muted mb-0"><?= l('admin_automation_update.settings_text') ?></p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4"><div class="form-group"><label for="name"><?= l('admin_automation_update.name_label') ?></label><input type="text" id="name" name="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= e($data->automation->name) ?>" maxlength="128" required="required" /><?= \Altum\Alerts::output_field_error('name') ?></div></div>
                <div class="col-lg-2"><div class="form-group"><label for="status"><?= l('admin_automation_update.status_label') ?></label><select id="status" name="status" class="custom-select"><option value="active" <?= $data->automation->status == 'active' ? 'selected="selected"' : null ?>><?= l('admin_automations.status.active') ?></option><option value="paused" <?= $data->automation->status == 'paused' ? 'selected="selected"' : null ?>><?= l('admin_automations.status.paused') ?></option></select></div></div>
                <div class="col-lg-3"><div class="form-group"><label for="batch_size"><?= l('admin_automation_update.batch_size') ?></label><input type="number" min="1" max="200" id="batch_size" name="batch_size" class="form-control" value="<?= $settings->batch_size ?>" /></div></div>
                <div class="col-lg-3"><div class="form-group"><label for="video_url"><?= l('admin_automation_update.video_url') ?></label><input type="url" id="video_url" name="video_url" class="form-control" value="<?= e($data->video_url) ?>" /></div></div>
            </div>

            <div class="row">
                <div class="col-lg-4"><div class="form-group"><label for="segment"><?= l('admin_automation_update.segment_label') ?></label><select id="segment" name="segment" class="custom-select <?= \Altum\Alerts::has_field_errors('segment') ? 'is-invalid' : null ?>"><?php foreach($data->segment_options as $segment_key => $segment_name): ?><option value="<?= $segment_key ?>" <?= $segment === $segment_key ? 'selected="selected"' : null ?>><?= e($segment_name) ?></option><?php endforeach ?></select><?= \Altum\Alerts::output_field_error('segment') ?></div></div>
                <div class="col-lg-4"><div class="form-group"><label for="segment_label"><?= l('admin_automation_update.segment_display_name') ?></label><input type="text" id="segment_label" name="segment_label" class="form-control" value="<?= e($settings->segment_label ?: $data->automation->segment_label) ?>" maxlength="128" /></div></div>
                <div class="col-lg-4 d-flex align-items-center"><div class="custom-control custom-switch mr-4 mt-3"><input id="reentry_is_enabled" name="reentry_is_enabled" type="checkbox" class="custom-control-input" <?= $settings->reentry_is_enabled ? 'checked="checked"' : null ?>><label class="custom-control-label" for="reentry_is_enabled"><?= l('admin_automation_update.reentry') ?></label></div><div class="custom-control custom-switch mt-3"><input id="exit_when_condition_met" name="exit_when_condition_met" type="checkbox" class="custom-control-input" <?= $settings->exit_when_condition_met ? 'checked="checked"' : null ?>><label class="custom-control-label" for="exit_when_condition_met"><?= l('admin_automation_update.auto_exit') ?></label></div></div>
            </div>

            <div class="form-group" data-plan-users>
                <label><?= l('admin_automation_update.plans') ?></label>
                <div class="row">
                    <div class="col-md-3 mb-2"><div class="custom-control custom-switch"><input id="filters_plans_free" name="filters_plans[]" value="free" type="checkbox" class="custom-control-input" <?= in_array('free', $settings->filters_plans) ? 'checked="checked"' : null ?>><label class="custom-control-label" for="filters_plans_free"><?= settings()->plan_free->name ?></label></div></div>
                    <div class="col-md-3 mb-2"><div class="custom-control custom-switch"><input id="filters_plans_custom" name="filters_plans[]" value="custom" type="checkbox" class="custom-control-input" <?= in_array('custom', $settings->filters_plans) ? 'checked="checked"' : null ?>><label class="custom-control-label" for="filters_plans_custom"><?= settings()->plan_custom->name ?></label></div></div>
                    <?php foreach($data->plans as $plan): ?><div class="col-md-3 mb-2"><div class="custom-control custom-switch"><input id="filters_plans_<?= $plan->plan_id ?>" name="filters_plans[]" value="<?= $plan->plan_id ?>" type="checkbox" class="custom-control-input" <?= in_array((string) $plan->plan_id, $settings->filters_plans, true) ? 'checked="checked"' : null ?>><label class="custom-control-label" for="filters_plans_<?= $plan->plan_id ?>"><?= e($plan->name) ?></label></div></div><?php endforeach ?>
                </div>
            </div>
        </div>
    </div>

    <?php foreach($data->steps as $step): ?>
        <?php $step_delay_minutes_value = isset($_POST['delay_minutes_' . $step->automation_step_id]) ? (int) $_POST['delay_minutes_' . $step->automation_step_id] : (int) $step->delay_minutes; ?>
        <?php $step_delay = $get_delay_parts($step_delay_minutes_value); ?>
        <div class="card mb-4 automation-step-card">
            <div class="card-body">
                <div class="automation-step-card__header mb-4"><div><span class="automation-step-card__eyebrow"><?= l('admin_automation_update.existing_step_eyebrow') ?></span><h2 class="h4 mb-1"><?= sprintf(l('admin_automation_update.step_header'), (int) $step->step_order) ?></h2><p class="text-muted mb-0"><?= l('admin_automation_update.existing_step_intro') ?></p></div><span class="automation-step-card__badge"><?= sprintf(l('admin_automation_update.step_delay_badge'), nr($step_delay_minutes_value)) ?></span></div>
                <div class="row">
                    <div class="col-lg-8"><div class="form-group"><label for="subject_<?= $step->automation_step_id ?>"><?= l('admin_broadcasts.subject') ?></label><input type="text" id="subject_<?= $step->automation_step_id ?>" name="subject_<?= $step->automation_step_id ?>" class="form-control <?= \Altum\Alerts::has_field_errors('subject_' . $step->automation_step_id) ? 'is-invalid' : null ?>" value="<?= isset($_POST['subject_' . $step->automation_step_id]) ? e($_POST['subject_' . $step->automation_step_id]) : e($step->subject) ?>" maxlength="128" required="required" /><?= \Altum\Alerts::output_field_error('subject_' . $step->automation_step_id) ?></div></div>
                    <div class="col-lg-4"><div class="form-group"><label><?= l('admin_mail_builder.delay_label') ?></label><div class="delay-input-group"><div class="delay-input-group__value"><input type="number" min="0" class="form-control" data-delay-value data-target="delay_minutes_<?= $step->automation_step_id ?>" value="<?= $step_delay['value'] ?>" /></div><div class="delay-input-group__unit"><select class="custom-select" data-delay-unit data-target="delay_minutes_<?= $step->automation_step_id ?>"><option value="minutes" <?= $step_delay['unit'] === 'minutes' ? 'selected="selected"' : null ?>><?= l('admin_mail_builder.delay_unit_minutes') ?></option><option value="hours" <?= $step_delay['unit'] === 'hours' ? 'selected="selected"' : null ?>><?= l('admin_mail_builder.delay_unit_hours') ?></option><option value="days" <?= $step_delay['unit'] === 'days' ? 'selected="selected"' : null ?>><?= l('admin_mail_builder.delay_unit_days') ?></option></select></div></div><input type="hidden" id="delay_minutes_<?= $step->automation_step_id ?>" name="delay_minutes_<?= $step->automation_step_id ?>" value="<?= $step_delay_minutes_value ?>" /><small class="form-text text-muted"><?= l('admin_automation_update.delay_help') ?></small></div></div>
                </div>
                <?php $step_editor_content = isset($_POST['content_' . $step->automation_step_id]) ? $_POST['content_' . $step->automation_step_id] : bootstrap_to_quilljs($step->content) ?>
                <div class="form-group mb-0"><label for="content_<?= $step->automation_step_id ?>"><?= l('admin_broadcasts.content') ?></label><div class="mail-editor-shell"><div class="mail-editor-shell__header"><div class="mail-editor-shell__title"><?= l('admin_mail_builder.editor_title') ?></div><div class="mail-editor-shell__meta"><?= l('admin_automation_update.editor_meta') ?></div></div><div class="mail-editor-shell__canvas-meta"><span class="mail-editor-shell__pill"><?= l('admin_mail_builder.pill_personalization') ?></span><span class="mail-editor-shell__pill"><?= l('admin_mail_builder.pill_primary_cta') ?></span><span class="mail-editor-shell__pill"><?= l('admin_mail_builder.pill_unsubscribe_footer') ?></span></div><div id="quill_existing_<?= $step->automation_step_id ?>" class="border rounded-bottom bg-transparent quill-email-editor" data-target="content_<?= $step->automation_step_id ?>"></div></div><textarea id="content_<?= $step->automation_step_id ?>" name="content_<?= $step->automation_step_id ?>" class="form-control d-none <?= \Altum\Alerts::has_field_errors('content_' . $step->automation_step_id) ? 'is-invalid' : null ?>" required="required"><?= e($step_editor_content) ?></textarea><?= \Altum\Alerts::output_field_error('content_' . $step->automation_step_id) ?></div>
            </div>
        </div>
    <?php endforeach ?>

    <?php for($new_step_index = 1; $new_step_index <= 5; $new_step_index++): ?>
        <?php $new_step_delay_minutes_value = (int) ($_POST['new_delay_minutes_' . $new_step_index] ?? ($new_step_index * 1440)); ?>
        <?php $new_step_delay = $get_delay_parts($new_step_delay_minutes_value); ?>
        <div class="card mb-4 automation-step-card <?= \Altum\Alerts::has_field_errors('new_subject_' . $new_step_index) || \Altum\Alerts::has_field_errors('new_content_' . $new_step_index) ? 'border-danger' : null ?>" data-new-step-card data-new-step-index="<?= $new_step_index ?>" <?= $new_step_index <= (int) $data->active_new_steps_count ? '' : 'style="display:none;"' ?>>
            <div class="card-body">
                <div class="automation-step-card__header mb-4"><div><span class="automation-step-card__eyebrow"><?= l('admin_automation_update.new_step_eyebrow') ?></span><h2 class="h4 mb-1"><?= sprintf(l('admin_automation_update.new_step_title'), count($data->steps) + $new_step_index) ?></h2><p class="text-muted mb-0"><?= l('admin_automation_update.new_step_intro') ?></p></div><span class="automation-step-card__badge"><?= l('admin_automation_update.new_step_badge') ?></span></div>
                <div class="row">
                    <div class="col-lg-8"><div class="form-group"><label for="new_subject_<?= $new_step_index ?>"><?= l('admin_broadcasts.subject') ?></label><input type="text" id="new_subject_<?= $new_step_index ?>" name="new_subject_<?= $new_step_index ?>" class="form-control <?= \Altum\Alerts::has_field_errors('new_subject_' . $new_step_index) ? 'is-invalid' : null ?>" value="<?= e($_POST['new_subject_' . $new_step_index] ?? '') ?>" maxlength="128" /><?= \Altum\Alerts::output_field_error('new_subject_' . $new_step_index) ?></div></div>
                    <div class="col-lg-4"><div class="form-group"><label><?= l('admin_mail_builder.delay_label') ?></label><div class="delay-input-group"><div class="delay-input-group__value"><input type="number" min="0" class="form-control" data-delay-value data-target="new_delay_minutes_<?= $new_step_index ?>" value="<?= $new_step_delay['value'] ?>" /></div><div class="delay-input-group__unit"><select class="custom-select" data-delay-unit data-target="new_delay_minutes_<?= $new_step_index ?>"><option value="minutes" <?= $new_step_delay['unit'] === 'minutes' ? 'selected="selected"' : null ?>><?= l('admin_mail_builder.delay_unit_minutes') ?></option><option value="hours" <?= $new_step_delay['unit'] === 'hours' ? 'selected="selected"' : null ?>><?= l('admin_mail_builder.delay_unit_hours') ?></option><option value="days" <?= $new_step_delay['unit'] === 'days' ? 'selected="selected"' : null ?>><?= l('admin_mail_builder.delay_unit_days') ?></option></select></div></div><input type="hidden" id="new_delay_minutes_<?= $new_step_index ?>" name="new_delay_minutes_<?= $new_step_index ?>" value="<?= $new_step_delay_minutes_value ?>" /><small class="form-text text-muted"><?= l('admin_automation_update.new_delay_help') ?></small></div></div>
                </div>
                <div class="form-group mb-0"><label for="new_content_<?= $new_step_index ?>"><?= l('admin_broadcasts.content') ?></label><div class="mail-editor-shell"><div class="mail-editor-shell__header"><div class="mail-editor-shell__title"><?= l('admin_mail_builder.editor_title') ?></div><div class="mail-editor-shell__meta"><?= l('admin_automation_update.new_editor_meta') ?></div></div><div class="mail-editor-shell__canvas-meta"><span class="mail-editor-shell__pill"><?= l('admin_mail_builder.pill_personalization') ?></span><span class="mail-editor-shell__pill"><?= l('admin_mail_builder.pill_primary_cta') ?></span><span class="mail-editor-shell__pill"><?= l('admin_mail_builder.pill_unsubscribe_footer') ?></span></div><div id="quill_new_<?= $new_step_index ?>" class="border rounded-bottom bg-transparent quill-email-editor" data-target="new_content_<?= $new_step_index ?>"></div></div><textarea id="new_content_<?= $new_step_index ?>" name="new_content_<?= $new_step_index ?>" class="form-control d-none <?= \Altum\Alerts::has_field_errors('new_content_' . $new_step_index) ? 'is-invalid' : null ?>"><?= e($_POST['new_content_' . $new_step_index] ?? l('admin_automation_update.new_default_content')) ?></textarea><?= \Altum\Alerts::output_field_error('new_content_' . $new_step_index) ?></div>
            </div>
        </div>
    <?php endfor ?>

    <div class="mail-studio-actions mb-4">
        <button type="button" class="btn btn-outline-primary mb-2 mb-md-0" id="add_new_step_button"><?= l('admin_automation_update.add_next_step') ?></button>
        <button type="button" class="btn btn-outline-secondary" id="remove_new_step_button"><?= l('admin_automation_update.remove_last_new_step') ?></button>
    </div>

    <div class="card mb-4 mail-studio-card mail-studio-card--soft">
        <div class="card-body">
            <h2 class="h5 mb-3"><?= l('admin_automation_update.test_title') ?></h2>
            <div class="row align-items-end">
                <div class="col-lg-3"><div class="form-group"><label for="preview_step_id"><?= l('admin_automation_update.test_step') ?></label><select id="preview_step_id" name="preview_step_id" class="custom-select"><?php foreach($data->steps as $step): ?><option value="<?= $step->automation_step_id ?>" <?= $data->preview_step_id == $step->automation_step_id ? 'selected="selected"' : null ?>><?= sprintf(l('admin_automation_update.step_header'), $step->step_order) ?></option><?php endforeach ?></select></div></div>
                <div class="col-lg-6"><div class="form-group"><label for="preview_email"><?= l('admin_automation_update.test_email') ?></label><input type="email" id="preview_email" name="preview_email" class="form-control <?= \Altum\Alerts::has_field_errors('preview_email') ? 'is-invalid' : null ?>" value="<?= e($data->preview_email) ?>" /><?= \Altum\Alerts::output_field_error('preview_email') ?></div></div>
                <div class="col-lg-3"><button type="submit" name="preview" value="1" class="btn btn-outline-primary btn-block mb-2"><?= l('admin_automation_update.test_send') ?></button><button type="submit" name="preview_all" value="1" class="btn btn-outline-secondary btn-block"><?= l('admin_automation_update.test_send_all') ?></button></div>
            </div>
        </div>
    </div>

    <div class="card mail-studio-submit-card mb-4">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
            <div class="mb-3 mb-lg-0">
                <h2 class="h5 mb-1"><?= l('admin_automation_update.save_title') ?></h2>
                <p class="text-muted mb-0"><?= l('admin_automation_update.save_text') ?></p>
            </div>
            <button type="submit" class="btn btn-lg btn-primary px-5"><?= l('admin_automation_update.save_submit') ?></button>
        </div>
    </div>
</form>
<!-- /Custom code: FC-2026-03-19 -->
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h5 mb-3"><?= l('admin_automation_update.activity_title') ?></h2>
        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
            <thead><tr><th><?= l('admin_automation_update.activity_time') ?></th><th><?= l('admin_automation_update.activity_action') ?></th><th><?= l('admin_automation_update.activity_user') ?></th><th><?= l('admin_automation_update.activity_details') ?></th></tr></thead>
                <tbody>
                <?php foreach($data->logs as $log): ?>
                    <tr>
                        <td class="text-nowrap"><?= \Altum\Date::get($log->datetime, 2) ?></td>
                        <td class="text-nowrap"><span class="badge badge-light"><?php $log_action_label = l('admin_automation_update.log_action.' . $log->action); echo strpos($log_action_label, 'admin_automation_update.log_action.') === 0 ? e(ucwords(str_replace('_', ' ', $log->action))) : $log_action_label; ?></span></td>
                        <td class="text-nowrap"><?php if($log->user): ?><div class="font-weight-bold"><?= e($log->user->name ?: ('#' . $log->user_id)) ?></div><div class="small text-muted"><?= e($log->user->email) ?></div><?php else: ?>-<?php endif ?></td>
                        <td class="small text-muted"><?= isset($log->details->message) ? e($log->details->message) : '-' ?></td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->logs)): ?><tr><td colspan="4" class="text-center text-muted py-4"><?= l('admin_automation_update.activity_empty') ?></td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php ob_start() ?>
<link href="<?= ASSETS_FULL_URL . 'css/libraries/quill.snow.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<style>
    .mail-studio-page {
        display: grid;
        gap: 1.5rem;
    }

    .mail-studio-hero,
    .mail-studio-card,
    .automation-step-card,
    .mail-studio-submit-card {
        border: 1px solid rgba(83, 110, 255, 0.14);
        box-shadow: 0 18px 45px rgba(6, 18, 56, 0.12);
        border-radius: 1.1rem;
        overflow: hidden;
    }

    .mail-studio-hero {
        background: radial-gradient(circle at top left, rgba(88, 128, 255, 0.22), transparent 38%), linear-gradient(135deg, rgba(13, 18, 32, 0.96), rgba(19, 30, 52, 0.92));
    }

    .mail-studio-eyebrow,
    .mail-studio-section-heading__eyebrow,
    .automation-step-card__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .35rem .7rem;
        border-radius: 999px;
        background: rgba(92, 126, 255, 0.14);
        color: #8fb1ff;
        font-size: .74rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        margin-bottom: .85rem;
    }

    .mail-studio-title {
        font-size: 1.9rem;
        line-height: 1.15;
        color: #f5f7ff;
        max-width: 14ch;
    }

    .mail-studio-hero .text-muted {
        color: rgba(226, 232, 255, 0.7) !important;
    }

    .mail-studio-hero__stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
    }

    .mail-studio-stat {
        padding: 1rem;
        border-radius: .95rem;
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: rgba(255, 255, 255, 0.06);
        backdrop-filter: blur(12px);
    }

    .mail-studio-stat__label {
        display: block;
        margin-bottom: .35rem;
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: rgba(226, 232, 255, 0.62);
    }

    .mail-studio-stat__value {
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
    }

    .mail-studio-card--soft {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
    }

    .mail-studio-section-heading {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(127, 145, 197, 0.16);
    }

    .mail-builder-shortcodes-card {
        background: linear-gradient(135deg, rgba(17, 27, 46, 0.95), rgba(14, 17, 27, 0.96));
    }

    .mail-shortcodes-grid {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .mail-shortcode-tag {
        padding: .7rem .9rem;
        border-radius: .8rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(120, 147, 255, 0.24);
        color: #dfe7ff;
        font-size: .82rem;
        cursor: pointer;
        transition: all .2s ease;
    }

    .mail-shortcode-tag:hover {
        transform: translateY(-1px);
        border-color: rgba(151, 176, 255, 0.48);
        background: rgba(92, 126, 255, 0.16);
    }

    .mail-editor-shell {
        position: relative;
        border-radius: 1.15rem;
        overflow: hidden;
        border: 1px solid rgba(96, 119, 199, 0.2);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.03), 0 24px 55px rgba(4, 13, 38, 0.28);
        background: radial-gradient(circle at top, rgba(118, 149, 255, 0.1), transparent 34%), linear-gradient(180deg, #101827 0%, #0b1220 100%);
    }

    .mail-editor-shell__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 1rem;
        padding: 1rem 1.1rem;
        background: linear-gradient(135deg, rgba(9, 18, 36, 0.96), rgba(27, 43, 78, 0.92));
        border-bottom: 1px solid rgba(96, 119, 199, 0.16);
    }

    .mail-editor-shell__title {
        font-weight: 700;
        color: #f4f7ff;
    }

    .mail-editor-shell__meta {
        font-size: .85rem;
        color: rgba(220, 228, 255, 0.72);
        margin-top: .15rem;
    }

    .mail-editor-shell__canvas-meta {
        display: flex;
        flex-wrap: wrap;
        gap: .55rem;
        padding: .9rem 1.1rem;
        background: rgba(11, 20, 36, 0.92);
        border-bottom: 1px solid rgba(96, 119, 199, 0.18);
    }

    .mail-editor-shell__pill {
        display: inline-flex;
        align-items: center;
        padding: .45rem .75rem;
        border-radius: 999px;
        border: 1px solid rgba(120, 145, 214, 0.18);
        background: rgba(255, 255, 255, 0.04);
        color: #c9d7f8;
        font-size: .76rem;
        font-weight: 600;
        letter-spacing: .01em;
    }

    .automation-step-card {
        background: linear-gradient(180deg, rgba(14, 21, 36, 0.96), rgba(14, 18, 28, 0.98));
    }

    .automation-step-card__header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
    }

    .automation-step-card__badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: .45rem .8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        color: #dce5ff;
        font-size: .8rem;
        font-weight: 600;
    }

    .delay-input-group {
        display: grid;
        grid-template-columns: 112px minmax(0, 1fr);
        gap: .75rem;
    }

    .mail-studio-actions {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .mail-studio-submit-card {
        background: linear-gradient(135deg, rgba(14, 18, 30, 0.98), rgba(18, 31, 56, 0.96));
    }

    .quill-email-editor {
        background: transparent;
    }

    .quill-email-editor .ql-toolbar.ql-snow {
        position: sticky;
        top: 0;
        z-index: 2;
        border: 0;
        border-bottom: 1px solid rgba(96, 119, 199, 0.18);
        padding: .85rem 1rem;
        background: rgba(10, 18, 33, 0.96);
        backdrop-filter: blur(12px);
    }

    .quill-email-editor .ql-toolbar.ql-snow button,
    .quill-email-editor .ql-toolbar.ql-snow .ql-picker {
        color: #dbe7ff;
    }

    .quill-email-editor .ql-toolbar.ql-snow .ql-stroke {
        stroke: #dbe7ff;
    }

    .quill-email-editor .ql-toolbar.ql-snow .ql-fill {
        fill: #dbe7ff;
    }

    .quill-email-editor .ql-toolbar.ql-snow .ql-picker-options {
        background: #162036;
        border-color: rgba(96, 119, 199, 0.24);
        color: #dbe7ff;
    }

    .quill-email-editor .ql-editor {
        min-height: 20rem;
        width: min(100%, 980px);
        max-width: none;
        margin: 0 auto;
        padding: 2rem 2rem 2.5rem;
        font-size: 1rem;
        line-height: 1.8;
        background: linear-gradient(180deg, rgba(14, 22, 36, 0.98), rgba(11, 17, 28, 0.98));
        color: #e6eeff;
        border: 1px solid rgba(96, 119, 199, 0.2);
        border-radius: 1.15rem;
        box-shadow: 0 30px 70px rgba(2, 6, 18, 0.34);
    }

    .quill-email-editor .ql-editor a {
        color: #8eb5ff;
    }

    .quill-email-editor .ql-editor.ql-blank::before {
        left: 2rem;
        right: 2rem;
        color: #7083ab;
        font-style: normal;
    }

    .quill-email-editor .ql-container.ql-snow {
        border: 0;
        padding: 1.25rem clamp(1rem, 3vw, 2rem) 2.25rem;
        background: linear-gradient(90deg, rgba(91, 117, 201, 0.05) 0, rgba(91, 117, 201, 0.05) 1px, transparent 1px, transparent 100%), linear-gradient(180deg, rgba(9, 16, 29, 0.98), rgba(13, 20, 35, 0.92));
    }

    @media (max-width: 991.98px) {
        .mail-studio-hero__stats {
            grid-template-columns: 1fr;
        }

        .mail-editor-shell__header {
            flex-direction: column;
            align-items: flex-start;
        }

        .delay-input-group {
            grid-template-columns: 1fr;
        }

        .automation-step-card__header,
        .mail-studio-actions {
            flex-direction: column;
        }

        .mail-editor-shell__canvas-meta {
            padding: .8rem .9rem;
        }

        .quill-email-editor .ql-container.ql-snow {
            padding: .85rem .75rem 1.25rem;
        }

        .quill-email-editor .ql-editor {
            width: 100%;
            min-height: 16rem;
            padding: 1.35rem 1rem 1.6rem;
        }

        .quill-email-editor .ql-editor.ql-blank::before {
            left: 1rem;
            right: 1rem;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/quill.min.js?v=' . PRODUCT_CODE ?>"></script>
<script>
    'use strict';

    let editors = {};

    let initializeQuillEditor = element => {
        if(element.dataset.initialized) {
            return;
        }

        let target = document.getElementById(element.dataset.target);
        let quill = new Quill('#' + element.id, {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ header: [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ color: [] }, { background: [] }],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    [{ align: [] }],
                    ['link', 'blockquote', 'code-block'],
                    ['clean']
                ]
            }
        });

        quill.root.innerHTML = target.value || '<p></p>';
        editors[target.id] = quill;
        element.dataset.initialized = 'true';
    };

    document.querySelectorAll('.quill-email-editor').forEach(initializeQuillEditor);

    let segmentHandler = () => {
        let segment = document.querySelector('#segment').value;
        document.querySelectorAll('[data-plan-users]').forEach(element => {
            element.style.display = segment === 'plan_users' ? '' : 'none';
        });
    };

    let updateNewStepsUi = () => {
        let visibleNewSteps = [...document.querySelectorAll('[data-new-step-card]')].filter(card => card.style.display !== 'none').length;
        document.querySelector('#active_new_steps_count').value = visibleNewSteps;
        document.querySelector('#add_new_step_button').disabled = visibleNewSteps >= 5;
        document.querySelector('#remove_new_step_button').disabled = visibleNewSteps <= 0;
    };

    let syncDelayField = targetId => {
        let hiddenInput = document.getElementById(targetId);
        let valueInput = document.querySelector('[data-delay-value][data-target="' + targetId + '"]');
        let unitInput = document.querySelector('[data-delay-unit][data-target="' + targetId + '"]');
        let value = Math.max(0, parseInt(valueInput.value || 0));
        let multiplier = {minutes: 1, hours: 60, days: 1440}[unitInput.value] || 1;

        hiddenInput.value = value * multiplier;
    };

    document.querySelectorAll('[data-delay-value], [data-delay-unit]').forEach(element => {
        element.addEventListener('input', () => syncDelayField(element.dataset.target));
        element.addEventListener('change', () => syncDelayField(element.dataset.target));
        syncDelayField(element.dataset.target);
    });

    document.querySelector('#add_new_step_button').addEventListener('click', () => {
        let nextCard = document.querySelector('[data-new-step-card][style*="display:none"]');

        if(nextCard) {
            nextCard.style.display = '';
            updateNewStepsUi();
        }
    });

    document.querySelector('#remove_new_step_button').addEventListener('click', () => {
        let visibleCards = [...document.querySelectorAll('[data-new-step-card]')].filter(card => card.style.display !== 'none');

        if(visibleCards.length) {
            let card = visibleCards[visibleCards.length - 1];
            let textarea = card.querySelector('textarea');
            let input = card.querySelector('input[type="text"]');
            let quill = editors[textarea.id];

            if(quill) {
                quill.root.innerHTML = '<p></p>';
            }

            textarea.value = '';
            input.value = '';
            card.querySelector('input[type="number"]').value = 0;
            card.style.display = 'none';
            updateNewStepsUi();
        }
    });

    document.querySelector('form').addEventListener('submit', () => {
        Object.keys(editors).forEach(targetId => {
            document.getElementById(targetId).value = editors[targetId].root.innerHTML;
        });

        document.querySelectorAll('[data-delay-value]').forEach(element => syncDelayField(element.dataset.target));
    });

    segmentHandler();
    document.querySelector('#segment').addEventListener('change', segmentHandler);
    updateNewStepsUi();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>