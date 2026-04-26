<?php defined('ALTUMCODE') || die() ?>

<?php $vip_demo_days = max(1, min(3, (int) (($data->access->settings->default_demo_days ?? 3)))); ?>
<?php $dashboard = is_array($data->dashboard ?? null) ? $data->dashboard : vip_funnel_demo_get_dashboard($this->user ?? null); ?>
<?php $request_form = array_merge($dashboard['default_request_form'] ?? vip_funnel_demo_get_request_form_defaults($this->user ?? null), is_array($data->request_form ?? null) ? $data->request_form : []); ?>
<?php $owner_options = $dashboard['owner_options'] ?? vip_funnel_demo_get_owner_options($this->user ?? null); ?>
<?php $interest_options = $dashboard['interest_options'] ?? vip_funnel_demo_get_interest_options(); ?>
<?php $readiness_options = $dashboard['readiness_options'] ?? vip_funnel_demo_get_readiness_options(); ?>
<?php $metrics = $dashboard['metrics'] ?? ['requests' => 0, 'active' => 0, 'expiring' => 0, 'expired' => 0, 'archived' => 0]; ?>

<?php
$vip_demo_action_labels = [
    'approve' => l('vip_funnel.demo.action.approve'),
    'reject' => l('vip_funnel.demo.action.reject'),
    'activate' => l('vip_funnel.demo.action.activate'),
    'pause' => l('vip_funnel.demo.action.pause'),
    'extend_2' => l('vip_funnel.demo.action.extend_2'),
    'extend_5' => l('vip_funnel.demo.action.extend_5'),
    'close' => l('vip_funnel.demo.action.close'),
    'convert' => l('vip_funnel.demo.action.convert'),
];

$render_demo_card = static function($demo) use ($vip_demo_action_labels) {
    ob_start();
    ?>
    <div class="vip-demo-card">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3" style="gap: .75rem;">
            <div>
                <span class="vip-demo-status-badge vip-demo-status-badge--<?= htmlspecialchars((string) $demo->status, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars((string) $demo->status_label, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <div class="small text-muted"><?= htmlspecialchars((string) $demo->owner_label, ENT_QUOTES, 'UTF-8') ?></div>
        </div>

        <h3 class="h5 text-white mb-2"><?= htmlspecialchars((string) $demo->lead_name, ENT_QUOTES, 'UTF-8') ?></h3>

        <div class="small text-muted mb-3">
            <?php if(!empty($demo->lead_email)): ?>
                <div><a href="mailto:<?= htmlspecialchars((string) $demo->lead_email, ENT_QUOTES, 'UTF-8') ?>" class="text-reset"><?= htmlspecialchars((string) $demo->lead_email, ENT_QUOTES, 'UTF-8') ?></a></div>
            <?php else: ?>
                <div><?= l('vip_funnel.demo.contact_missing') ?></div>
            <?php endif ?>

            <?php if(!empty($demo->lead_phone)): ?>
                <div><a href="tel:<?= htmlspecialchars((string) $demo->lead_phone, ENT_QUOTES, 'UTF-8') ?>" class="text-reset"><?= htmlspecialchars((string) $demo->lead_phone, ENT_QUOTES, 'UTF-8') ?></a></div>
            <?php endif ?>

            <?php if(!empty($demo->requested_demo_login_email) && $demo->requested_demo_login_email !== $demo->lead_email): ?>
                <div><?= htmlspecialchars((string) $demo->requested_demo_login_email, ENT_QUOTES, 'UTF-8') ?> <span class="text-muted">(<?= l('vip_funnel.demo.label.login_email') ?>)</span></div>
            <?php endif ?>
        </div>

        <div class="vip-demo-meta-list mb-3">
            <div>
                <span><?= l('vip_funnel.demo.label.source') ?></span>
                <strong><?= htmlspecialchars((string) ($demo->source ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div>
                <span><?= l('vip_funnel.demo.label.interest') ?></span>
                <strong><?= htmlspecialchars((string) ($demo->interest_label ?? $demo->interest_type ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div>
                <span><?= l('vip_funnel.demo.label.readiness') ?></span>
                <strong><?= htmlspecialchars((string) ($demo->business_readiness_label ?? $demo->business_readiness ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div>
                <span><?= l('vip_funnel.demo.label.requested') ?></span>
                <strong><?= htmlspecialchars((string) ($demo->datetime_display ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <?php if(!empty($demo->approved_at_display)): ?>
                <div>
                    <span><?= l('vip_funnel.demo.label.approved') ?></span>
                    <strong><?= htmlspecialchars((string) $demo->approved_at_display, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endif ?>
            <?php if(!empty($demo->expires_at_display)): ?>
                <div>
                    <span><?= l('vip_funnel.demo.label.expires') ?></span>
                    <strong><?= htmlspecialchars((string) $demo->expires_at_display, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endif ?>
            <?php if(!empty($demo->forever_id)): ?>
                <div>
                    <span><?= l('global.forerverId') ?></span>
                    <strong><?= htmlspecialchars((string) $demo->forever_id, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            <?php endif ?>
        </div>

        <div class="small text-muted mb-3">
            <?= l('vip_funnel.demo.label.provisioning') ?>:
            <span class="vip-demo-copy-strong"><?= l('vip_funnel.demo.provisioning.' . ($demo->provisioning_status ?? 'pending')) ?></span>
        </div>

        <?php if(!empty($demo->workspace_url) || !empty($demo->login_email) || !empty($demo->temporary_password) || !empty($demo->reset_password_url)): ?>
            <div class="vip-demo-note mb-3">
                <div class="small text-uppercase font-weight-bold text-muted mb-2"><?= l('vip_funnel.demo.credentials_title') ?></div>
                <div class="vip-demo-meta-list mb-0">
                    <?php if(!empty($demo->workspace_url)): ?>
                        <div>
                            <span><?= l('vip_funnel.demo.label.workspace') ?></span>
                            <strong><a href="<?= htmlspecialchars((string) $demo->workspace_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="text-reset"><?= l('vip_funnel.demo.link.open_workspace') ?></a></strong>
                        </div>
                    <?php endif ?>

                    <?php if(!empty($demo->login_email)): ?>
                        <div>
                            <span><?= l('vip_funnel.demo.label.login_email') ?></span>
                            <strong><?= htmlspecialchars((string) $demo->login_email, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    <?php endif ?>

                    <?php if(!empty($demo->temporary_password)): ?>
                        <div>
                            <span><?= l('vip_funnel.demo.label.password') ?></span>
                            <strong class="vip-demo-secret"><?= htmlspecialchars((string) $demo->temporary_password, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    <?php endif ?>

                    <?php if(!empty($demo->reset_password_url)): ?>
                        <div>
                            <span><?= l('vip_funnel.demo.label.reset_link') ?></span>
                            <strong><a href="<?= htmlspecialchars((string) $demo->reset_password_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="text-reset"><?= l('vip_funnel.demo.link.reset_password') ?></a></strong>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        <?php endif ?>

        <?php if(!empty($demo->lead_notes)): ?>
            <div class="vip-demo-note mb-3">
                <div class="small text-uppercase font-weight-bold text-muted mb-2"><?= l('vip_funnel.demo.label.notes') ?></div>
                <div><?= nl2br(htmlspecialchars((string) $demo->lead_notes, ENT_QUOTES, 'UTF-8')) ?></div>
            </div>
        <?php endif ?>

        <?php if(!empty($demo->available_actions)): ?>
            <div class="vip-demo-actions">
                <?php foreach($demo->available_actions as $action): ?>
                    <form method="post">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                        <input type="hidden" name="vip_demo_account_id" value="<?= (int) $demo->vip_demo_account_id ?>" />
                        <input type="hidden" name="vip_demo_action" value="<?= htmlspecialchars((string) $action, ENT_QUOTES, 'UTF-8') ?>" />
                        <button type="submit" class="btn btn-sm <?= in_array($action, ['approve', 'activate', 'convert'], true) ? 'btn-vip-primary' : 'btn-outline-light' ?>">
                            <?= htmlspecialchars((string) ($vip_demo_action_labels[$action] ?? $action), ENT_QUOTES, 'UTF-8') ?>
                        </button>
                    </form>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
    <?php
    return ob_get_clean();
};
?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <style>
        .vip-demo-shell .card,
        .vip-demo-card {
            border-radius: 1.2rem;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: linear-gradient(160deg, rgba(24, 33, 48, 0.97), rgba(10, 15, 23, 0.99));
            color: #eef4ff;
            overflow: hidden;
        }

        .vip-demo-shell .card h1,
        .vip-demo-shell .card h2,
        .vip-demo-shell .card h3,
        .vip-demo-shell .card h4,
        .vip-demo-shell .card h5,
        .vip-demo-shell .card h6,
        .vip-demo-shell .vip-demo-card h1,
        .vip-demo-shell .vip-demo-card h2,
        .vip-demo-shell .vip-demo-card h3,
        .vip-demo-shell .vip-demo-card h4,
        .vip-demo-shell .vip-demo-card h5,
        .vip-demo-shell .vip-demo-card h6,
        .vip-demo-shell .text-white {
            color: #eef4ff !important;
        }

        .vip-demo-shell .text-muted,
        .vip-demo-shell .small {
            color: rgba(206, 217, 231, 0.8) !important;
        }

        .vip-demo-shell .form-control,
        .vip-demo-shell .custom-select {
            background: rgba(15, 23, 35, 0.84);
            border-color: rgba(148, 163, 184, 0.24);
            color: #eef4ff;
        }

        .vip-demo-shell .form-control:focus,
        .vip-demo-shell .custom-select:focus {
            background: rgba(15, 23, 35, 0.94);
            border-color: rgba(103, 216, 201, 0.52);
            color: #eef4ff;
            box-shadow: 0 0 0 .18rem rgba(103, 216, 201, 0.16);
        }

        .vip-demo-shell .form-control::placeholder {
            color: rgba(206, 217, 231, 0.52);
        }

        .vip-demo-shell .btn-outline-light {
            color: #f8fbff !important;
            border-color: rgba(248, 251, 255, 0.34) !important;
            background: rgba(255, 255, 255, 0.05);
            font-weight: 600;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        }

        .vip-demo-shell .btn-outline-light:hover,
        .vip-demo-shell .btn-outline-light:focus {
            color: #0f172a !important;
            background: #f8fbff !important;
            border-color: #f8fbff !important;
        }

        .vip-demo-shell .btn-vip-primary {
            color: #0f172a;
            background: linear-gradient(135deg, #67d8c9, #9beadf);
            border-color: transparent;
            font-weight: 700;
        }

        .vip-demo-shell .btn-vip-primary:hover,
        .vip-demo-shell .btn-vip-primary:focus {
            color: #0f172a;
            background: linear-gradient(135deg, #74e4d5, #b1f2ea);
        }

        .vip-demo-shell .vip-demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .vip-demo-shell .vip-demo-status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
        }

        .vip-demo-shell .vip-demo-status-tile {
            padding: 1rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.03);
        }

        .vip-demo-shell .vip-demo-status-number {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: .45rem;
            color: #eef4ff;
        }

        .vip-demo-shell .vip-demo-section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(290px, 1fr));
            gap: 1rem;
        }

        .vip-demo-shell .vip-demo-card {
            padding: 1.1rem;
            min-height: 100%;
        }

        .vip-demo-shell .vip-demo-status-badge {
            display: inline-flex;
            align-items: center;
            padding: .35rem .7rem;
            border-radius: 999px;
            font-size: .78rem;
            font-weight: 700;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .vip-demo-shell .vip-demo-status-badge--requested {
            background: rgba(244, 182, 63, 0.16);
            color: #ffe3a1;
        }

        .vip-demo-shell .vip-demo-status-badge--approved,
        .vip-demo-shell .vip-demo-status-badge--active {
            background: rgba(103, 216, 201, 0.16);
            color: #a8f2e6;
        }

        .vip-demo-shell .vip-demo-status-badge--paused,
        .vip-demo-shell .vip-demo-status-badge--rejected,
        .vip-demo-shell .vip-demo-status-badge--closed {
            background: rgba(148, 163, 184, 0.16);
            color: #d5deea;
        }

        .vip-demo-shell .vip-demo-status-badge--expiring,
        .vip-demo-shell .vip-demo-status-badge--expired {
            background: rgba(248, 113, 113, 0.16);
            color: #ffc7c7;
        }

        .vip-demo-shell .vip-demo-status-badge--converted {
            background: rgba(96, 165, 250, 0.16);
            color: #d1e6ff;
        }

        .vip-demo-shell .vip-demo-meta-list {
            display: grid;
            gap: .55rem;
        }

        .vip-demo-shell .vip-demo-meta-list > div {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: .45rem;
        }

        .vip-demo-shell .vip-demo-meta-list span {
            color: rgba(206, 217, 231, 0.75);
        }

        .vip-demo-shell .vip-demo-meta-list strong {
            color: #eef4ff;
            text-align: right;
        }

        .vip-demo-shell .vip-demo-note {
            padding: .85rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .vip-demo-shell .vip-demo-actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
        }

        .vip-demo-shell .vip-demo-actions form {
            margin: 0;
        }

        .vip-demo-shell .vip-demo-copy-strong {
            color: rgba(238, 244, 255, 0.92);
        }

        .vip-demo-shell .vip-demo-secret {
            font-family: "SFMono-Regular", Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            letter-spacing: .03em;
        }

        .vip-demo-shell .vip-demo-event-list {
            display: grid;
            gap: .85rem;
        }

        .vip-demo-shell .vip-demo-event {
            padding: .9rem;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .vip-demo-shell .vip-demo-empty {
            padding: 1rem;
            border-radius: 1rem;
            border: 1px dashed rgba(255, 255, 255, 0.14);
            color: rgba(206, 217, 231, 0.78);
            text-align: center;
            background: rgba(255, 255, 255, 0.02);
        }
    </style>

    <div class="vip-demo-shell">
        <div class="card mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap: 1rem;">
                    <div>
                        <div class="small text-uppercase font-weight-bold mb-2" style="letter-spacing: .12em; color: #8be9d6;">
                            <?= l('vip_funnel.demo.eyebrow') ?>
                        </div>
                        <h1 class="h2 text-white mb-3"><?= l('vip_funnel.demo.header') ?></h1>
                        <p class="mb-0" style="max-width: 760px;"><?= sprintf(l('vip_funnel.demo.subheader'), $vip_demo_days) ?></p>
                    </div>

                    <a href="<?= $data->studio_url ?>" class="btn btn-outline-light"><?= l('vip_funnel.demo.back_to_studio') ?></a>
                </div>
            </div>
        </div>

        <div class="vip-demo-grid mb-4">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 text-white"><?= l('vip_funnel.demo.card_micro_title') ?></h2>
                    <p class="text-muted mb-0"><?= l('vip_funnel.demo.card_micro_text') ?></p>
                </div>
            </div>

            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 text-white"><?= l('vip_funnel.demo.card_sandbox_title') ?></h2>
                    <p class="text-muted mb-0"><?= sprintf(l('vip_funnel.demo.card_sandbox_text'), $vip_demo_days) ?></p>
                </div>
            </div>

            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 text-white"><?= l('vip_funnel.demo.card_control_title') ?></h2>
                    <p class="text-muted mb-0"><?= l('vip_funnel.demo.card_control_text') ?></p>
                </div>
            </div>
        </div>

        <?php if(!$dashboard['schema_ready']): ?>
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h4 text-white mb-2"><?= l('vip_funnel.demo.schema_title') ?></h2>
                    <p class="text-muted mb-0"><?= l('vip_funnel.demo.schema_text') ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap: .75rem;">
                        <div>
                            <h2 class="h5 text-white mb-1"><?= l('vip_funnel.demo.metrics_title') ?></h2>
                            <p class="text-muted mb-0"><?= l('vip_funnel.demo.metrics_text') ?></p>
                        </div>
                        <div class="small text-muted"><?= sprintf(l('vip_funnel.demo.metrics_days'), $vip_demo_days) ?></div>
                    </div>

                    <div class="vip-demo-status-grid">
                        <div class="vip-demo-status-tile">
                            <div class="vip-demo-status-number"><?= (int) ($metrics['requests'] ?? 0) ?></div>
                            <div class="small text-uppercase font-weight-bold"><?= l('vip_funnel.demo.metric.requests') ?></div>
                        </div>
                        <div class="vip-demo-status-tile">
                            <div class="vip-demo-status-number"><?= (int) ($metrics['active'] ?? 0) ?></div>
                            <div class="small text-uppercase font-weight-bold"><?= l('vip_funnel.demo.metric.active') ?></div>
                        </div>
                        <div class="vip-demo-status-tile">
                            <div class="vip-demo-status-number"><?= (int) ($metrics['expiring'] ?? 0) ?></div>
                            <div class="small text-uppercase font-weight-bold"><?= l('vip_funnel.demo.metric.expiring') ?></div>
                        </div>
                        <div class="vip-demo-status-tile">
                            <div class="vip-demo-status-number"><?= (int) ($metrics['expired'] ?? 0) ?></div>
                            <div class="small text-uppercase font-weight-bold"><?= l('vip_funnel.demo.metric.expired') ?></div>
                        </div>
                        <div class="vip-demo-status-tile">
                            <div class="vip-demo-status-number"><?= (int) ($metrics['archived'] ?? 0) ?></div>
                            <div class="small text-uppercase font-weight-bold"><?= l('vip_funnel.demo.metric.archived') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12 col-xl-5 mb-4 mb-xl-0">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-3" style="gap: .75rem;">
                                <div>
                                    <h2 class="h5 text-white mb-1"><?= l('vip_funnel.demo.request_title') ?></h2>
                                    <p class="text-muted mb-0"><?= l('vip_funnel.demo.request_text') ?></p>
                                </div>
                                <span class="badge badge-pill badge-light"><?= sprintf(l('vip_funnel.demo.request_days_badge'), $vip_demo_days) ?></span>
                            </div>

                            <form method="post">
                                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                                <div class="form-group">
                                    <label for="vip-demo-lead-name"><?= l('vip_funnel.demo.form.lead_name') ?></label>
                                    <input id="vip-demo-lead-name" type="text" name="lead_name" class="form-control" maxlength="120" value="<?= htmlspecialchars((string) ($request_form['lead_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                </div>

                                <div class="form-group">
                                    <label for="vip-demo-lead-email"><?= l('vip_funnel.demo.form.lead_email') ?></label>
                                    <input id="vip-demo-lead-email" type="email" name="lead_email" class="form-control" maxlength="320" value="<?= htmlspecialchars((string) ($request_form['lead_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                </div>

                                <div class="form-group">
                                    <label for="vip-demo-login-email"><?= l('vip_funnel.demo.form.demo_login_email') ?></label>
                                    <input id="vip-demo-login-email" type="email" name="demo_login_email" class="form-control" maxlength="320" value="<?= htmlspecialchars((string) ($request_form['demo_login_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                    <small class="form-text text-muted"><?= l('vip_funnel.demo.form.demo_login_email_help') ?></small>
                                </div>

                                <div class="form-group">
                                    <label for="vip-demo-lead-phone"><?= l('vip_funnel.demo.form.lead_phone') ?></label>
                                    <input id="vip-demo-lead-phone" type="text" name="lead_phone" class="form-control" maxlength="32" value="<?= htmlspecialchars((string) ($request_form['lead_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                </div>

                                <div class="form-group">
                                    <label for="vip-demo-forever-id"><?= l('vip_funnel.demo.form.forever_id') ?></label>
                                    <input id="vip-demo-forever-id" type="text" name="forever_id" class="form-control" maxlength="12" value="<?= htmlspecialchars((string) ($request_form['forever_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                    <small class="form-text text-muted"><?= l('vip_funnel.demo.form.forever_id_help') ?></small>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-12 col-md-6">
                                        <label for="vip-demo-interest"><?= l('vip_funnel.demo.form.interest_type') ?></label>
                                        <select id="vip-demo-interest" name="interest_type" class="custom-select">
                                            <?php foreach($interest_options as $value => $label): ?>
                                                <option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>" <?= ($request_form['interest_type'] ?? 'demo') === $value ? 'selected="selected"' : null ?>>
                                                    <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-12 col-md-6">
                                        <label for="vip-demo-readiness"><?= l('vip_funnel.demo.form.business_readiness') ?></label>
                                        <select id="vip-demo-readiness" name="business_readiness" class="custom-select">
                                            <?php foreach($readiness_options as $value => $label): ?>
                                                <option value="<?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?>" <?= ($request_form['business_readiness'] ?? 'curious') === $value ? 'selected="selected"' : null ?>>
                                                    <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group col-12 col-md-6">
                                        <label for="vip-demo-owner"><?= l('vip_funnel.demo.form.owner_user_id') ?></label>
                                        <select id="vip-demo-owner" name="owner_user_id" class="custom-select">
                                            <?php foreach($owner_options as $value => $label): ?>
                                                <option value="<?= (int) $value ?>" <?= (int) ($request_form['owner_user_id'] ?? 0) === (int) $value ? 'selected="selected"' : null ?>>
                                                    <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-12 col-md-6">
                                        <label for="vip-demo-source"><?= l('vip_funnel.demo.form.source') ?></label>
                                        <input id="vip-demo-source" type="text" name="source" class="form-control" maxlength="64" value="<?= htmlspecialchars((string) ($request_form['source'] ?? 'manual_pilot'), ENT_QUOTES, 'UTF-8') ?>" />
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="vip-demo-product-goal"><?= l('vip_funnel.demo.form.product_goal') ?></label>
                                    <input id="vip-demo-product-goal" type="text" name="product_goal" class="form-control" maxlength="120" value="<?= htmlspecialchars((string) ($request_form['product_goal'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" />
                                </div>

                                <div class="form-group mb-4">
                                    <label for="vip-demo-notes"><?= l('vip_funnel.demo.form.notes') ?></label>
                                    <textarea id="vip-demo-notes" name="notes" rows="4" class="form-control" maxlength="800"><?= htmlspecialchars((string) ($request_form['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                </div>

                                <button type="submit" name="create_vip_demo_request" value="1" class="btn btn-vip-primary">
                                    <?= l('vip_funnel.demo.form.submit') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-xl-7">
                    <div class="card h-100">
                        <div class="card-body p-4">
                            <h2 class="h5 text-white mb-1"><?= l('vip_funnel.demo.actions_panel_title') ?></h2>
                            <p class="text-muted mb-4"><?= l('vip_funnel.demo.actions_panel_text') ?></p>

                            <div class="vip-demo-grid mb-4">
                                <div class="vip-demo-note">
                                    <div class="small text-uppercase font-weight-bold text-muted mb-2"><?= l('vip_funnel.demo.lifecycle_title') ?></div>
                                    <div class="vip-demo-copy-strong"><?= l('vip_funnel.demo.lifecycle_text') ?></div>
                                </div>

                                <div class="vip-demo-note">
                                    <div class="small text-uppercase font-weight-bold text-muted mb-2"><?= l('vip_funnel.demo.guided_sandbox_title') ?></div>
                                    <div class="vip-demo-copy-strong"><?= l('vip_funnel.demo.guided_sandbox_text') ?></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3" style="gap: .75rem;">
                                <div>
                                    <h3 class="h6 text-white mb-1"><?= l('vip_funnel.demo.events_title') ?></h3>
                                    <p class="text-muted mb-0"><?= l('vip_funnel.demo.events_text') ?></p>
                                </div>
                                <div class="small text-muted"><?= l('vip_funnel.demo.events_limit') ?></div>
                            </div>

                            <?php if(!empty($dashboard['events'])): ?>
                                <div class="vip-demo-event-list">
                                    <?php foreach($dashboard['events'] as $event): ?>
                                        <div class="vip-demo-event">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-2" style="gap: .75rem;">
                                                <div class="vip-demo-copy-strong"><?= htmlspecialchars((string) ($event->event_label ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars((string) ($event->datetime_timeago ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            </div>
                                            <div class="small text-muted mb-1"><?= htmlspecialchars((string) ($event->lead_name ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                            <?php if(!empty($event->actor_name)): ?>
                                                <div class="small text-muted"><?= sprintf(l('vip_funnel.demo.events_actor'), htmlspecialchars((string) $event->actor_name, ENT_QUOTES, 'UTF-8')) ?></div>
                                            <?php endif ?>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                            <?php else: ?>
                                <div class="vip-demo-empty"><?= l('vip_funnel.demo.empty_events') ?></div>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php
            $sections = [
                'requests' => [
                    'title' => l('vip_funnel.demo.requests_title'),
                    'text' => l('vip_funnel.demo.requests_text'),
                    'items' => $dashboard['requests'] ?? [],
                    'empty' => l('vip_funnel.demo.empty_requests'),
                ],
                'active' => [
                    'title' => l('vip_funnel.demo.active_title'),
                    'text' => l('vip_funnel.demo.active_text'),
                    'items' => $dashboard['active'] ?? [],
                    'empty' => l('vip_funnel.demo.empty_active'),
                ],
                'expiring' => [
                    'title' => l('vip_funnel.demo.expiring_title'),
                    'text' => l('vip_funnel.demo.expiring_text'),
                    'items' => $dashboard['expiring'] ?? [],
                    'empty' => l('vip_funnel.demo.empty_expiring'),
                ],
                'expired' => [
                    'title' => l('vip_funnel.demo.expired_title'),
                    'text' => l('vip_funnel.demo.expired_text'),
                    'items' => $dashboard['expired'] ?? [],
                    'empty' => l('vip_funnel.demo.empty_expired'),
                ],
            ];
            ?>

            <?php foreach($sections as $section): ?>
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3" style="gap: .75rem;">
                            <div>
                                <h2 class="h5 text-white mb-1"><?= $section['title'] ?></h2>
                                <p class="text-muted mb-0"><?= $section['text'] ?></p>
                            </div>
                            <div class="small text-muted"><?= count($section['items']) ?> <?= l('vip_funnel.demo.items_count') ?></div>
                        </div>

                        <?php if(!empty($section['items'])): ?>
                            <div class="vip-demo-section-grid">
                                <?php foreach($section['items'] as $demo): ?>
                                    <?= $render_demo_card($demo) ?>
                                <?php endforeach ?>
                            </div>
                        <?php else: ?>
                            <div class="vip-demo-empty"><?= $section['empty'] ?></div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        <?php endif ?>
    </div>
</div>
