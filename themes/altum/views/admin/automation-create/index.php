<?php defined('ALTUMCODE') || die() ?>

<?php $values = $data->values; ?>
<?php $active_steps_count = max(1, min(10, (int) ($values['active_steps_count'] ?? 1))); ?>
<?php $steps = $values['steps'] ?? []; ?>
<?php $automation_shortcodes = ['{{USER:NAME}}', '{{USER:EMAIL}}', '{{SALES_LINKS_PAGE}}', '{{FOREVER_CARD_APPLICATION_URL}}', '{{FCC_VIDEO_URL}}', '{{USER:LOGIN_LINK}}', '{{WEBSITE_TITLE}}']; ?>
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
        <h1 class="h3 mb-2"><i class="fas fa-fw fa-xs fa-plus-circle text-primary-900 mr-2"></i> <?= l('admin_automation_create.header') ?></h1>
        <p class="text-muted mb-0"><?= l('admin_automation_create.subheader') ?></p>
    </div>

    <a href="<?= url('admin/automations') ?>" class="btn btn-gray-300 mt-3 mt-lg-0"><?= l('admin_automation_create.back') ?></a>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<!-- Custom code: FC-2026-03-19: mail studio create layout -->
<div class="mail-studio-page">
    <div class="card mail-studio-hero mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-3 mb-lg-0">
                    <span class="mail-studio-eyebrow"><?= l('admin_automation_create.hero_eyebrow') ?></span>
                    <h2 class="mail-studio-title mb-2"><?= l('admin_automation_create.hero_title') ?></h2>
                    <p class="text-muted mb-0"><?= l('admin_automation_create.hero_text') ?></p>
                </div>
                <div class="col-lg-5">
                    <div class="mail-studio-hero__stats">
                        <div class="mail-studio-stat">
                            <span class="mail-studio-stat__label"><?= l('admin_automation_create.stat_segment') ?></span>
                            <strong class="mail-studio-stat__value">Live segment</strong>
                        </div>
                        <div class="mail-studio-stat">
                            <span class="mail-studio-stat__label"><?= l('admin_automation_create.stat_steps') ?></span>
                            <strong class="mail-studio-stat__value" id="active_steps_counter"><?= $active_steps_count ?></strong>
                        </div>
                        <div class="mail-studio-stat">
                            <span class="mail-studio-stat__label"><?= l('admin_automation_create.stat_mode') ?></span>
                            <strong class="mail-studio-stat__value"><?= l('admin_automation_create.stat_mode_value') ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<form action="" method="post" role="form">
    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

    <div class="card mb-4 mail-studio-card mail-studio-card--soft <?= \Altum\Alerts::has_field_errors() ? 'border-danger' : null ?>">
        <div class="card-body">
            <div class="mail-studio-section-heading">
                <div>
                    <span class="mail-studio-section-heading__eyebrow"><?= l('admin_automation_create.section_eyebrow') ?></span>
                    <h2 class="h4 mb-1"><?= l('admin_automation_create.section_title') ?></h2>
                    <p class="text-muted mb-0"><?= l('admin_automation_create.section_text') ?></p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="name"><?= l('admin_automation_create.name_label') ?></label>
                        <input type="text" id="name" name="name" class="form-control <?= \Altum\Alerts::has_field_errors('name') ? 'is-invalid' : null ?>" value="<?= e($values['name']) ?>" maxlength="128" required="required" />
                        <?= \Altum\Alerts::output_field_error('name') ?>
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group">
                        <label for="status"><?= l('admin_automation_create.status_label') ?></label>
                        <select id="status" name="status" class="custom-select">
                            <option value="paused" <?= $values['status'] == 'paused' ? 'selected="selected"' : null ?>><?= l('admin_automations.status.paused') ?></option>
                            <option value="active" <?= $values['status'] == 'active' ? 'selected="selected"' : null ?>><?= l('admin_automations.status.active') ?></option>
                        </select>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="form-group">
                        <label for="batch_size"><?= l('admin_automation_update.batch_size') ?></label>
                        <input type="number" min="1" max="200" id="batch_size" name="batch_size" class="form-control" value="<?= (int) $values['batch_size'] ?>" />
                    </div>
                </div>
                <div class="col-lg-3 d-flex align-items-end">
                    <div class="w-100 mail-studio-inline-stat text-center">
                        <div class="small text-uppercase text-muted mb-1"><?= l('admin_automation_create.steps_short') ?></div>
                        <div class="h4 mb-0"><?= $active_steps_count ?></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="segment"><?= l('admin_automation_create.segment_label') ?></label>
                        <select id="segment" name="segment" class="custom-select <?= \Altum\Alerts::has_field_errors('segment') ? 'is-invalid' : null ?>">
                            <?php foreach($data->segment_options as $segment_key => $segment_name): ?>
                                <option value="<?= $segment_key ?>" <?= $values['segment'] === $segment_key ? 'selected="selected"' : null ?>><?= e($segment_name) ?></option>
                            <?php endforeach ?>
                        </select>
                        <?= \Altum\Alerts::output_field_error('segment') ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="form-group">
                            <label for="segment_label"><?= l('admin_automation_create.segment_display_name') ?></label>
                        <input type="text" id="segment_label" name="segment_label" class="form-control" value="<?= e($values['segment_label']) ?>" maxlength="128" />
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="form-group">
                        <label for="video_url"><?= l('admin_automation_update.video_url') ?></label>
                        <input type="url" id="video_url" name="video_url" class="form-control" value="<?= e($values['video_url']) ?>" />
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-lg-6">
                    <div class="custom-control custom-switch">
                        <input id="reentry_is_enabled" name="reentry_is_enabled" type="checkbox" class="custom-control-input" <?= $values['reentry_is_enabled'] ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="reentry_is_enabled"><?= l('admin_automation_create.reentry') ?></label>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="custom-control custom-switch">
                        <input id="exit_when_condition_met" name="exit_when_condition_met" type="checkbox" class="custom-control-input" <?= $values['exit_when_condition_met'] ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="exit_when_condition_met"><?= l('admin_automation_create.auto_exit_condition') ?></label>
                    </div>
                </div>
            </div>

            <div class="form-group" data-plan-users>
                <label><?= l('admin_automation_create.plans') ?></label>
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <div class="custom-control custom-switch">
                            <input id="filters_plans_free" name="filters_plans[]" value="free" type="checkbox" class="custom-control-input" <?= in_array('free', $values['filters_plans']) ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="filters_plans_free"><?= settings()->plan_free->name ?></label>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="custom-control custom-switch">
                            <input id="filters_plans_custom" name="filters_plans[]" value="custom" type="checkbox" class="custom-control-input" <?= in_array('custom', $values['filters_plans']) ? 'checked="checked"' : null ?>>
                            <label class="custom-control-label" for="filters_plans_custom"><?= settings()->plan_custom->name ?></label>
                        </div>
                    </div>
                    <?php foreach($data->plans as $plan): ?>
                        <div class="col-md-3 mb-2">
                            <div class="custom-control custom-switch">
                                <input id="filters_plans_<?= $plan->plan_id ?>" name="filters_plans[]" value="<?= $plan->plan_id ?>" type="checkbox" class="custom-control-input" <?= in_array((string) $plan->plan_id, $values['filters_plans'], true) ? 'checked="checked"' : null ?>>
                                <label class="custom-control-label" for="filters_plans_<?= $plan->plan_id ?>"><?= e($plan->name) ?></label>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" id="active_steps_count" name="active_steps_count" value="<?= $active_steps_count ?>" />

    <div class="card mb-4 mail-studio-card mail-builder-shortcodes-card">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                <div>
                    <h2 class="h5 mb-1"><?= l('admin_mail_builder.shortcodes_title') ?></h2>
                    <p class="text-muted mb-0"><?= l('admin_automation_create.shortcodes_help') ?></p>
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

    <?php foreach($steps as $step): ?>
        <?php $step_delay = $get_delay_parts($step['delay_minutes']); ?>
        <div class="card mb-4 automation-step-card <?= \Altum\Alerts::has_field_errors('step_subject_' . $step['step_order']) || \Altum\Alerts::has_field_errors('step_content_' . $step['step_order']) ? 'border-danger' : null ?>" data-step-card data-step-order="<?= $step['step_order'] ?>" <?= $step['step_order'] <= $active_steps_count ? '' : 'style="display:none;"' ?>>
            <div class="card-body">
                <div class="automation-step-card__header mb-4">
                    <div>
                        <span class="automation-step-card__eyebrow"><?= sprintf(l('admin_automation_create.sequence_eyebrow'), $step['step_order']) ?></span>
                        <h2 class="h4 mb-1"><?= sprintf(l('admin_automation_update.step_header'), $step['step_order']) ?></h2>
                        <p class="text-muted mb-0"><?= l('admin_automation_create.step_intro') ?></p>
                    </div>
                    <span class="automation-step-card__badge"><?= sprintf(l('admin_automation_create.step_mail_badge'), $step['step_order']) ?></span>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="form-group">
                            <label for="step_subject_<?= $step['step_order'] ?>"><?= l('admin_broadcasts.subject') ?></label>
                            <input type="text" id="step_subject_<?= $step['step_order'] ?>" name="step_subject_<?= $step['step_order'] ?>" class="form-control <?= \Altum\Alerts::has_field_errors('step_subject_' . $step['step_order']) ? 'is-invalid' : null ?>" value="<?= e($step['subject']) ?>" maxlength="128" <?= $step['step_order'] === 1 ? 'required="required"' : '' ?> />
                            <?= \Altum\Alerts::output_field_error('step_subject_' . $step['step_order']) ?>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label><?= l('admin_mail_builder.delay_label') ?></label>
                            <div class="delay-input-group">
                                <div class="delay-input-group__value">
                                    <input type="number" min="0" class="form-control" data-delay-value data-target="step_delay_minutes_<?= $step['step_order'] ?>" value="<?= $step_delay['value'] ?>" />
                                </div>
                                <div class="delay-input-group__unit">
                                    <select class="custom-select" data-delay-unit data-target="step_delay_minutes_<?= $step['step_order'] ?>">
                                        <option value="minutes" <?= $step_delay['unit'] === 'minutes' ? 'selected="selected"' : null ?>><?= l('admin_mail_builder.delay_unit_minutes') ?></option>
                                        <option value="hours" <?= $step_delay['unit'] === 'hours' ? 'selected="selected"' : null ?>><?= l('admin_mail_builder.delay_unit_hours') ?></option>
                                        <option value="days" <?= $step_delay['unit'] === 'days' ? 'selected="selected"' : null ?>><?= l('admin_mail_builder.delay_unit_days') ?></option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" id="step_delay_minutes_<?= $step['step_order'] ?>" name="step_delay_minutes_<?= $step['step_order'] ?>" value="<?= (int) $step['delay_minutes'] ?>" />
                            <small class="form-text text-muted"><?= l('admin_automation_create.delay_help') ?></small>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label for="step_content_<?= $step['step_order'] ?>"><?= l('admin_broadcasts.content') ?></label>
                    <div class="mail-editor-shell">
                        <div class="mail-editor-shell__header">
                            <div class="mail-editor-shell__title"><?= l('admin_mail_builder.editor_title') ?></div>
                            <div class="mail-editor-shell__meta"><?= l('admin_automation_create.editor_meta') ?></div>
                        </div>
                        <div class="mail-editor-shell__canvas-meta">
                            <span class="mail-editor-shell__pill"><?= l('admin_mail_builder.pill_personalization') ?></span>
                            <span class="mail-editor-shell__pill"><?= l('admin_mail_builder.pill_primary_cta') ?></span>
                            <span class="mail-editor-shell__pill"><?= l('admin_mail_builder.pill_unsubscribe_footer') ?></span>
                        </div>
                        <div id="quill_step_<?= $step['step_order'] ?>" class="border rounded-bottom bg-transparent quill-email-editor" data-target="step_content_<?= $step['step_order'] ?>"></div>
                    </div>
                    <textarea id="step_content_<?= $step['step_order'] ?>" name="step_content_<?= $step['step_order'] ?>" class="form-control d-none <?= \Altum\Alerts::has_field_errors('step_content_' . $step['step_order']) ? 'is-invalid' : null ?>" <?= $step['step_order'] === 1 ? 'required="required"' : '' ?>><?= e($step['content']) ?></textarea>
                    <?= \Altum\Alerts::output_field_error('step_content_' . $step['step_order']) ?>
                </div>
            </div>
        </div>
    <?php endforeach ?>

    <div class="mail-studio-actions mb-4">
        <button type="button" class="btn btn-outline-primary mb-2 mb-md-0" id="add_step_button"><?= l('admin_automation_create.add_next_step') ?></button>
        <button type="button" class="btn btn-outline-secondary" id="remove_step_button"><?= l('admin_automation_create.remove_last_step') ?></button>
    </div>

    <div class="card mail-studio-submit-card">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
            <div class="mb-3 mb-lg-0">
                <h2 class="h5 mb-1"><?= l('admin_automation_create.ready_title') ?></h2>
                <p class="text-muted mb-0"><?= l('admin_automation_create.ready_text') ?></p>
            </div>
            <button type="submit" class="btn btn-lg btn-primary px-5"><?= l('admin_automation_create.submit') ?></button>
        </div>
    </div>
</form>
<!-- /Custom code: FC-2026-03-19 -->
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

    .mail-studio-stat,
    .mail-studio-inline-stat {
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

    let updateStepUi = () => {
        let visibleSteps = document.querySelectorAll('[data-step-card]:not([style*="display:none"])').length;
        document.querySelector('#active_steps_count').value = visibleSteps;
        document.querySelector('#active_steps_counter').innerText = visibleSteps;
        document.querySelector('#add_step_button').disabled = visibleSteps >= 10;
        document.querySelector('#remove_step_button').disabled = visibleSteps <= 1;
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

    document.querySelector('#add_step_button').addEventListener('click', () => {
        let nextCard = document.querySelector('[data-step-card][style*="display:none"]');

        if(nextCard) {
            nextCard.style.display = '';
            updateStepUi();
        }
    });

    document.querySelector('#remove_step_button').addEventListener('click', () => {
        let visibleCards = [...document.querySelectorAll('[data-step-card]')].filter(card => card.style.display !== 'none');

        if(visibleCards.length > 1) {
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
            updateStepUi();
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
    updateStepUi();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>