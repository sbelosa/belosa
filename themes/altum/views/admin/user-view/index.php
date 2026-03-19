<?php defined('ALTUMCODE') || die() ?>

<?php if(settings()->main->breadcrumbs_is_enabled): ?>
<nav aria-label="breadcrumb">
    <ol class="custom-breadcrumbs small">
        <li>
            <a href="<?= url('admin/users') ?>"><?= l('admin_users.breadcrumb') ?></a><i class="fas fa-fw fa-angle-right"></i>
        </li>
        <li class="active" aria-current="page"><?= l('admin_user_view.breadcrumb') ?></li>
    </ol>
</nav>
<?php endif ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center text-truncate">
        <img src="<?= get_user_avatar($data->user->avatar, $data->user->email) ?>" class="user-avatar rounded-circle mr-3" alt="" />

        <h1 class="h3 mb-0 text-truncate"><?= $data->user->name ?></h1>
    </div>

    <?= include_view(THEME_PATH . 'views/admin/users/admin_user_dropdown_button.php', ['id' => $data->user->user_id, 'resource_name' => $data->user->name]) ?>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<?php //ALTUMCODE:DEMO if(DEMO) {$data->user->email = 'hidden@demo.com'; $data->user->name = $data->user->ip = $data->user->api_key = 'hidden on demo';} ?>

<?php
/* Custom code: FC-2026-03-17: billing risk state presentation */
$billing_state_badges = [
    'healthy' => 'success',
    'past_due' => 'warning',
    'past_due_critical' => 'danger',
    'access_revoked' => 'secondary',
    'recovered' => 'success',
];

$billing_event_icons = [
    'payment_failed' => 'fa-circle-exclamation text-warning',
    'grace_period_escalated' => 'fa-triangle-exclamation text-danger',
    'notification_sent' => 'fa-envelope text-primary',
    'subscription_status_changed' => 'fa-arrows-rotate text-info',
    'payment_recovered' => 'fa-heart-circle-check text-success',
    'payment_confirmed' => 'fa-circle-check text-success',
    'access_revoked' => 'fa-ban text-secondary',
];
/* /Custom code: FC-2026-03-17 */
?>

<!-- Custom code: FC-2026-03-04: admin user performance overview -->
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h2 class="h5 mb-2 mb-md-0"><i class="fas fa-fw fa-sm fa-chart-line text-primary mr-1"></i> <?= l('admin_user_view.analytics.header') ?></h2>
        <small class="text-muted"><?= l('admin_user_view.analytics.subheader') ?></small>
    </div>

    <div class="row mb-2">
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_user_view.analytics.track_clicks_total') ?></small>
                    <div class="h5 mb-0"><?= nr($data->user_analytics['track_clicks_total']) ?></div>
                    <small class="text-muted d-block mt-1"><?= l('admin_user_view.analytics.unique') ?>: <?= nr($data->user_analytics['track_clicks_unique_total']) ?></small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_user_view.analytics.track_clicks_30d') ?></small>
                    <div class="h5 mb-0"><?= nr($data->user_analytics['track_clicks_30d']) ?></div>
                    <small class="text-muted d-block mt-1"><?= l('admin_user_view.analytics.unique') ?>: <?= nr($data->user_analytics['track_clicks_unique_30d']) ?></small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_user_view.analytics.biolink_visits_total') ?></small>
                    <div class="h5 mb-0"><?= nr($data->user_analytics['biolink_visits_total']) ?></div>
                    <small class="text-muted d-block mt-1"><?= l('admin_user_view.analytics.last_30d') ?>: <?= nr($data->user_analytics['biolink_visits_30d']) ?></small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_user_view.analytics.revenue_total') ?></small>
                    <div class="h5 mb-0"><?= nr($data->user_analytics['revenue_total'], 2) ?> <?= settings()->payment->default_currency ?></div>
                    <small class="text-muted d-block mt-1"><?= l('admin_user_view.analytics.last_30d') ?>: <?= nr($data->user_analytics['revenue_30d'], 2) ?> <?= settings()->payment->default_currency ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-12 col-md-6 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_user_view.analytics.paid_payments_total') ?></small>
                    <div class="h5 mb-0"><?= nr($data->user_analytics['paid_payments_total']) ?></div>
                    <small class="text-muted d-block mt-1"><?= l('admin_user_view.analytics.last_30d') ?>: <?= nr($data->user_analytics['paid_payments_30d']) ?></small>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_user_view.analytics.failed_payments_30d') ?></small>
                    <div class="h5 mb-0"><?= nr($data->user_analytics['failed_payments_30d']) ?></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted d-block mb-1"><?= l('admin_user_view.analytics.current_plan') ?></small>
                    <div class="h5 mb-0"><?= $data->user->plan->name ?></div>
                    <small class="text-muted d-block mt-1"><?= l('admin_users.plan_expiration_date') ?>: <?= \Altum\Date::get($data->user->plan_expiration_date, 2) ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_user_view.analytics.top_countries') ?></h3>
                    <?php if(empty($data->user_analytics['top_countries'])): ?>
                        <div class="text-muted small"><?= l('global.no_data') ?></div>
                    <?php else: ?>
                        <?php foreach($data->user_analytics['top_countries'] as $country): ?>
                            <div class="d-flex justify-content-between border-bottom py-2 small">
                                <span><?= $country['country_code'] ? get_country_from_country_code($country['country_code']) . ' (' . $country['country_code'] . ')' : '-' ?></span>
                                <strong><?= nr($country['total']) ?></strong>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_user_view.analytics.top_links') ?></h3>
                    <?php if(empty($data->user_analytics['top_links'])): ?>
                        <div class="text-muted small"><?= l('global.no_data') ?></div>
                    <?php else: ?>
                        <?php foreach($data->user_analytics['top_links'] as $link): ?>
                            <div class="border-bottom py-2 small">
                                <div class="d-flex justify-content-between">
                                    <span class="text-truncate mr-2" style="max-width: 85%;">
                                        <?php if(!empty($link['url'])): ?>
                                            <a href="<?= $link['url'] ?>" target="_blank" rel="noopener noreferrer"><?= $link['url'] ?></a>
                                        <?php else: ?>
                                            <?= l('global.unknown') ?>
                                        <?php endif ?>
                                    </span>
                                    <strong><?= nr($link['total']) ?></strong>
                                </div>
                                <div class="text-muted"><?= $link['type'] ?: l('global.unknown') ?></div>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_user_view.analytics.recent_payments') ?></h3>
                    <?php if(empty($data->user_analytics['recent_payments'])): ?>
                        <div class="text-muted small"><?= l('global.no_data') ?></div>
                    <?php else: ?>
                        <?php foreach($data->user_analytics['recent_payments'] as $payment): ?>
                            <div class="border-bottom py-2 small">
                                <div class="d-flex justify-content-between">
                                    <span class="text-truncate mr-2"><?= $payment['processor'] ? l('pay.custom_plan.' . $payment['processor']) : l('global.unknown') ?></span>
                                    <strong><?= nr($payment['total_amount'], 2) ?> <?= $payment['currency'] ?></strong>
                                </div>
                                <div class="text-muted"><?= \Altum\Date::get($payment['datetime'], 2) ?> · <?= $payment['status'] ? l('admin_payments.status_' . $payment['status']) : l('global.unknown') ?></div>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-04 -->

<!-- Custom code: FC-2026-03-17: admin user billing risk panel -->
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h2 class="h5 mb-2 mb-md-0"><i class="fas fa-fw fa-sm fa-triangle-exclamation text-primary mr-1"></i> <?= l('admin_user_view.billing_risk.header') ?></h2>
        <small class="text-muted"><?= l('admin_user_view.billing_risk.subheader') ?></small>
    </div>

    <div class="row mb-2">
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_user_view.billing_risk.current_state') ?></small><div class="h5 mb-0"><span class="badge badge-<?= $billing_state_badges[$data->billing_summary['billing_state']] ?? 'light' ?>"><?= l('admin_billing_risk.state_' . $data->billing_summary['billing_state']) ?></span></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_user_view.billing_risk.stripe_status') ?></small><div class="h5 mb-0"><?= $data->billing_summary['stripe_status'] ?: l('global.none') ?></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_user_view.billing_risk.failed_attempts') ?></small><div class="h5 mb-0"><?= nr($data->billing_summary['failed_attempts']) ?></div></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_user_view.billing_risk.last_notification') ?></small><div class="h6 mb-0"><?= $data->billing_summary['last_notification_stage'] ? l('admin_billing_risk.notification_' . $data->billing_summary['last_notification_stage']) : l('global.none') ?></div><small class="text-muted d-block mt-1"><?= !empty($data->billing_summary['last_notification_at']) ? \Altum\Date::get($data->billing_summary['last_notification_at'], 2) : l('global.none') ?></small></div></div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12 col-xl-5 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_user_view.billing_risk.summary') ?></h3>
                    <div class="d-flex justify-content-between border-bottom py-2 small"><span><?= l('admin_billing_risk.reason') ?></span><strong class="text-right ml-3"><?= $data->billing_summary['last_failed_reason_text'] ?: l('global.none') ?></strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2 small"><span><?= l('admin_user_view.billing_risk.reason_code') ?></span><strong><?= $data->billing_summary['last_failed_reason_code'] ?: l('global.none') ?></strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2 small"><span><?= l('admin_user_view.billing_risk.last_failed_at') ?></span><strong><?= !empty($data->billing_summary['last_failed_at']) ? \Altum\Date::get($data->billing_summary['last_failed_at'], 2) : l('global.none') ?></strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2 small"><span><?= l('admin_billing_risk.grace_until') ?></span><strong><?= !empty($data->billing_summary['grace_until']) ? \Altum\Date::get($data->billing_summary['grace_until'], 2) : l('global.none') ?></strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2 small"><span><?= l('admin_billing_risk.next_retry_at') ?></span><strong><?= !empty($data->billing_summary['next_retry_at']) ? \Altum\Date::get($data->billing_summary['next_retry_at'], 2) : l('global.none') ?></strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2 small"><span><?= l('admin_user_view.billing_risk.current_period_end') ?></span><strong><?= !empty($data->billing_summary['current_period_end']) ? \Altum\Date::get($data->billing_summary['current_period_end'], 2) : l('global.none') ?></strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2 small"><span><?= l('admin_user_view.billing_risk.last_invoice_id') ?></span><strong><?= $data->billing_summary['last_invoice_id'] ?: l('global.none') ?></strong></div>
                    <div class="d-flex justify-content-between py-2 small"><span><?= l('admin_user_view.billing_risk.last_payment_intent_id') ?></span><strong><?= $data->billing_summary['last_payment_intent_id'] ?: l('global.none') ?></strong></div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7 p-2">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= l('admin_user_view.billing_risk.timeline') ?></h3>
                    <?php if(empty($data->billing_events)): ?>
                        <div class="text-muted small"><?= l('global.no_data') ?></div>
                    <?php else: ?>
                        <?php foreach($data->billing_events as $event): ?>
                            <div class="d-flex border-bottom py-3 small">
                                <div class="mr-3 mt-1 text-center" style="width: 24px;">
                                    <i class="fas <?= $billing_event_icons[$event['event_type']] ?? 'fa-clock text-muted' ?>"></i>
                                </div>
                                <div class="flex-fill">
                                    <div class="d-flex flex-column flex-lg-row justify-content-between">
                                        <strong><?= l('admin_user_view.billing_risk.event_type_' . $event['event_type']) ?></strong>
                                        <span class="text-muted"><?= !empty($event['occurred_at']) ? \Altum\Date::get($event['occurred_at'], 2) : l('global.none') ?></span>
                                    </div>
                                    <div class="text-muted mt-1"><?= $event['reason_text'] ?: ($event['notification_stage'] ? l('admin_billing_risk.notification_' . $event['notification_stage']) : l('global.none')) ?></div>
                                    <div class="text-muted mt-1">
                                        <?= l('admin_user_view.billing_risk.timeline_meta') ?>:
                                        <?= $event['stripe_status'] ?: l('global.none') ?>
                                        · <?= $event['stripe_invoice_id'] ?: l('global.none') ?>
                                        · <?= $event['stripe_subscription_id'] ?: l('global.none') ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-17 -->

<?php /* Custom code: FC-2026-03-19: admin per-user email activity panel */ ?>
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between mb-3">
        <h2 class="h5 mb-2 mb-md-0"><i class="fas fa-fw fa-sm fa-envelope-open-text text-primary mr-1"></i> <?= l('admin_user_view.email_activity.header') ?></h2>
        <small class="text-muted"><?= l('admin_user_view.email_activity.subheader') ?></small>
    </div>

    <div class="row mb-2">
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_user_view.email_activity.total_sent') ?></small><div class="h5 mb-0"><?= nr($data->email_activity['summary']['sent']) ?></div><small class="text-muted d-block mt-1"><?= l('admin_user_view.email_activity.broadcasts') ?>: <?= nr($data->email_activity['summary_by_type']['broadcast']) ?></small></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_user_view.email_activity.opened') ?></small><div class="h5 mb-0"><?= nr($data->email_activity['summary']['opened']) ?></div><small class="text-muted d-block mt-1"><?= l('admin_user_view.email_activity.open_rate') ?>: <?= nr($data->email_activity['rates']['open_rate']) ?>%</small></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_user_view.email_activity.clicked') ?></small><div class="h5 mb-0"><?= nr($data->email_activity['summary']['clicked']) ?></div><small class="text-muted d-block mt-1"><?= l('admin_user_view.email_activity.click_rate') ?>: <?= nr($data->email_activity['rates']['click_rate']) ?>%</small></div></div>
        </div>
        <div class="col-12 col-md-6 col-xl-3 p-2">
            <div class="card h-100"><div class="card-body"><small class="text-muted d-block mb-1"><?= l('admin_user_view.email_activity.unsubscribed') ?></small><div class="h5 mb-0"><?= nr($data->email_activity['summary']['unsubscribed']) ?></div><small class="text-muted d-block mt-1"><?= l('admin_user_view.email_activity.automations') ?>: <?= nr($data->email_activity['summary_by_type']['automation']) ?></small></div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive table-custom-container">
                <table class="table table-custom mb-0">
                    <thead>
                    <tr>
                        <th><?= l('admin_user_view.email_activity.type') ?></th>
                        <th><?= l('admin_user_view.email_activity.resource') ?></th>
                        <th><?= l('admin_user_view.email_activity.subject') ?></th>
                        <th><?= l('admin_user_view.email_activity.sent') ?></th>
                        <th><?= l('admin_user_view.email_activity.actions') ?></th>
                        <th><?= l('admin_user_view.email_activity.timeline') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($data->email_activity['recent_messages'] as $message): ?>
                        <?php $message_type = ($message->message_type ?? 'automation') === 'broadcast' ? 'broadcast' : 'automation'; ?>
                        <tr>
                            <td class="text-nowrap"><span class="badge badge-light"><?= l('admin_user_view.email_activity.type_' . $message_type) ?></span></td>
                            <td>
                                <?php if(!empty($message->resource_url)): ?>
                                    <a href="<?= $message->resource_url ?>"><?= e($message->resource_name) ?></a>
                                <?php else: ?>
                                    <?= e($message->resource_name) ?>
                                <?php endif ?>
                            </td>
                            <td>
                                <div class="font-weight-bold"><?= e($message->subject) ?></div>
                                <div class="small text-muted"><?= e($message->recipient_email) ?></div>
                            </td>
                            <td class="text-nowrap"><?= !empty($message->sent_datetime) ? 
                                \Altum\Date::get($message->sent_datetime, 2) : '-' ?></td>
                            <td>
                                <div class="d-flex flex-wrap">
                                    <span class="badge badge-light mr-1 mb-1"><?= e(str_replace('_', ' ', $message->status ?? 'sent')) ?></span>
                                    <?php if(!empty($message->delivered_datetime)): ?><span class="badge badge-success mr-1 mb-1"><?= l('admin_user_view.email_activity.action_delivered') ?></span><?php endif ?>
                                    <?php if(!empty($message->first_open_datetime)): ?><span class="badge badge-info mr-1 mb-1"><?= l('admin_user_view.email_activity.action_opened') ?></span><?php endif ?>
                                    <?php if(!empty($message->first_click_datetime)): ?><span class="badge badge-primary mr-1 mb-1"><?= l('admin_user_view.email_activity.action_clicked') ?></span><?php endif ?>
                                    <?php if(!empty($message->unsubscribe_datetime) || ($message->status ?? '') === 'unsubscribed'): ?><span class="badge badge-warning mr-1 mb-1"><?= l('admin_user_view.email_activity.action_unsubscribed') ?></span><?php endif ?>
                                </div>
                            </td>
                            <td class="small text-muted text-nowrap">
                                <div>D <?= !empty($message->delivered_datetime) ? \Altum\Date::get($message->delivered_datetime, 2) : '-' ?></div>
                                <div>O <?= !empty($message->first_open_datetime) ? \Altum\Date::get($message->first_open_datetime, 2) : '-' ?></div>
                                <div>C <?= !empty($message->first_click_datetime) ? \Altum\Date::get($message->first_click_datetime, 2) : '-' ?></div>
                                <div>U <?= !empty($message->unsubscribe_datetime) ? \Altum\Date::get($message->unsubscribe_datetime, 2) : '-' ?></div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <?php if(empty($data->email_activity['recent_messages'])): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4"><?= l('admin_user_view.email_activity.no_data') ?></td>
                        </tr>
                    <?php endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php /* /Custom code: FC-2026-03-19 */ ?>

<div class="row">
    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="form-group">
                    <label for="user_id" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-fingerprint text-muted mr-1"></i> <?= l('admin_users.user_id') ?></label>
                    <input id="user_id" type="text" class="form-control-plaintext" value="<?= $data->user->user_id ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="type" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-user text-muted mr-1"></i> <?= l('admin_users.type') ?></label>
                    <input id="type" type="text" class="form-control-plaintext" value="<?= $data->user->type ? l('admin_users.type_admin') : l('admin_users.type_user') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="status" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-circle-dot text-muted mr-1"></i> <?= l('global.status') ?></label>
                    <input id="status" type="text" class="form-control-plaintext" value="<?php if($data->user->status == 1) echo l('admin_users.status_active'); elseif($data->user->status == 0) echo l('admin_users.status_unconfirmed'); elseif($data->user->status == 2) echo l('admin_users.status_disabled') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="email" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-envelope text-muted mr-1"></i> <?= l('global.email') ?></label>
                    <input id="email" type="text" class="form-control-plaintext" value="<?= $data->user->email ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="name" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-signature text-muted mr-1"></i> <?= l('global.name') ?></label>
                    <input id="name" type="text" class="form-control-plaintext" value="<?= $data->user->name ?>" readonly />
                </div>

                <!-- Custom code -->
                <div class="form-group">
                    <label for="forever_id" class="font-weight-bold"><i class="fa fa-fw fa-sm fa-id-card text-muted mr-1"></i> <?= l('admin_users.main.forever_id') ?></label>
                    <input id="forever_id" type="text" class="form-control-plaintext" value="<?= isset($data->user_meta->foreverId) ? $data->user_meta->foreverId : '' ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="phone" class="font-weight-bold"><i class="fa fa-fw fa-sm fa-phone text-muted mr-1"></i> <?= l('admin_users.main.phone') ?></label>
                    <input id="phone" type="text" class="form-control-plaintext" value="<?= isset($data->user_meta->phone) ? $data->user_meta->phone : '' ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="address" class="font-weight-bold"><i class="fa fa-fw fa-sm fa-map-marker-alt text-muted mr-1"></i> <?= l('admin_users.main.address') ?></label>
                    <input id="address" type="text" class="form-control-plaintext" value="<?= isset($data->user_meta->address) ? $data->user_meta->address : '' ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="zip" class="font-weight-bold"><i class="fa fa-fw fa-sm fa-map-marker-alt text-muted mr-1"></i> <?= l('admin_users.main.zip') ?></label>
                    <input id="zip" type="text" class="form-control-plaintext" value="<?= isset($data->user_meta->zip) ? $data->user_meta->zip : '' ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="city" class="font-weight-bold"><i class="fa fa-fw fa-sm fa-map-marker-alt text-muted mr-1"></i> <?= l('admin_users.main.city') ?></label>
                    <input id="city" type="text" class="form-control-plaintext" value="<?= isset($data->user_meta->city) ? $data->user_meta->city : '' ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="country" class="font-weight-bold"><i class="fa fa-fw fa-sm fa-map-marker-alt text-muted mr-1"></i> <?= l('admin_users.main.country') ?></label>
                    <input id="country" type="text" class="form-control-plaintext" value="<?= isset($data->user_meta->country) ? $data->user_meta->country : '' ?>" readonly />
                </div>
                <!-- /Custom code -->

                <div class="form-group">
                    <label for="language" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-language text-muted mr-1"></i> <?= l('global.language') ?></label>
                    <input id="language" type="text" class="form-control-plaintext" value="<?= $data->user->language ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="timezone" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-clock text-muted mr-1"></i> <?= l('admin_users.timezone') ?></label>
                    <input id="timezone" type="text" class="form-control-plaintext" value="<?= $data->user->timezone ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="twofa_is_enabled" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-lock text-muted mr-1"></i> <?= l('admin_users.twofa_is_enabled') ?></label>
                    <input id="twofa_is_enabled" type="text" class="form-control-plaintext" value="<?= $data->user->twofa_secret ? l('global.yes') : l('global.no') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="anti_phishing_code" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-user-shield text-muted mr-1"></i> <?= l('admin_users.anti_phishing_code') ?></label>
                    <input id="anti_phishing_code" type="text" class="form-control-plaintext" value="<?= $data->user->anti_phishing_code ? l('global.yes') : l('global.no') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="user_deletion_reminder" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-user-minus text-muted mr-1"></i> <?= l('admin_users.user_deletion_reminder') ?></label>
                    <input id="user_deletion_reminder" type="text" class="form-control-plaintext" value="<?= $data->user->user_deletion_reminder ? l('global.yes') : l('global.no') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="is_newsletter_subscribed" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-mail-bulk text-muted mr-1"></i> <?= l('admin_users.is_newsletter_subscribed') ?></label>
                    <input id="is_newsletter_subscribed" type="text" class="form-control-plaintext" value="<?= $data->user->is_newsletter_subscribed ? l('global.yes') : l('global.no') ?>" readonly />
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold"><i class="fas fa-fw fa-sm fa-box-open text-muted mr-1"></i> <?= l('admin_users.plan_id') ?></label>
                    <div>
                        <a href="<?= url('admin/plan-update/' . $data->user->plan->plan_id) ?>"><?= $data->user->plan->name ?></a>
                    </div>
                </div>

                <div class="form-group">
                    <label for="plan_expiration_date" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-calendar text-muted mr-1"></i> <?= l('admin_users.plan_expiration_date') ?></label>
                    <input id="plan_expiration_date" type="text" class="form-control-plaintext" value="<?= \Altum\Date::get($data->user->plan_expiration_date, 1) ?>" readonly />
                </div>

                <?php if(in_array(settings()->license->type, ['SPECIAL', 'Extended License', 'extended'])): ?>
                    <div class="form-group">
                        <label for="payment_processor" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-money-check-alt text-muted mr-1"></i> <?= l('admin_users.payment_processor') ?></label>
                        <input id="payment_processor" type="text" class="form-control-plaintext" value="<?= $data->user->payment_processor ? l('pay.custom_plan.' . $data->user->payment_processor) : l('global.none') ?>" readonly />
                    </div>

                    <div class="form-group">
                        <label for="payment_total_amount" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-money-bill-alt text-muted mr-1"></i> <?= l('admin_users.payment_total_amount') ?></label>
                        <input id="payment_total_amount" type="text" class="form-control-plaintext" value="<?= $data->user->payment_total_amount ? nr($data->user->payment_total_amount, 2) . ' ' . $data->user->payment_currency : l('global.none') ?>" readonly />
                    </div>

                    <div class="form-group">
                        <label for="payment_subscription_id" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-hand-holding-usd text-muted mr-1"></i> <?= l('admin_users.payment_subscription_id') ?></label>
                        <input id="payment_subscription_id" type="text" class="form-control-plaintext" value="<?= $data->user->payment_subscription_id ?: l('global.none') ?>" readonly />
                    </div>

                    <div class="form-group">
                        <label for="plan_trial_done" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-calendar-minus text-muted mr-1"></i> <?= l('admin_users.plan_trial_done') ?></label>
                        <input id="plan_trial_done" type="text" class="form-control-plaintext" value="<?= $data->user->plan_trial_done ? l('global.yes') : l('global.no') ?>" readonly />
                    </div>

                    <div class="form-group">
                        <label for="plan_expiry_reminder" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-business-time text-muted mr-1"></i> <?= l('admin_users.plan_expiry_reminder') ?></label>
                        <input id="plan_expiry_reminder" type="text" class="form-control-plaintext" value="<?= $data->user->plan_expiry_reminder ? l('global.yes') : l('global.no') ?>" readonly />
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="form-group">
                    <label for="source" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-sign-in-alt text-muted mr-1"></i> <?= l('admin_users.source') ?></label>
                    <input id="source" type="text" class="form-control-plaintext" value="<?= l('admin_users.source.' .  $data->user->source) ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="ip" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-network-wired text-muted mr-1"></i> <?= l('global.ip') ?></label>
                    <input id="ip" type="text" class="form-control-plaintext" value="<?= $data->user->ip ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="continent_code" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-globe-europe text-muted mr-1"></i> <?= l('global.continent') ?></label>
                    <input id="continent_code" type="text" class="form-control-plaintext" value="<?= $data->user->continent_code ? get_continent_from_continent_code($data->user->continent_code) : l('global.unknown') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="country" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-flag text-muted mr-1"></i> <?= l('global.country') ?></label>
                    <input id="country" type="text" class="form-control-plaintext" value="<?= $data->user->country ? get_country_from_country_code($data->user->country) : l('global.unknown') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="city_name" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-city text-muted mr-1"></i> <?= l('global.city') ?></label>
                    <input id="city_name" type="text" class="form-control-plaintext" value="<?= $data->user->city_name ?? l('global.unknown') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="device_type" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-laptop text-muted mr-1"></i> <?= l('global.device') ?></label>
                    <input id="device_type" type="text" class="form-control-plaintext" value="<?= $data->user->device_type ? l('global.device.' . $data->user->device_type) : l('global.unknown') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="os_name" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-server text-muted mr-1"></i> <?= l('global.os_name') ?></label>
                    <input id="os_name" type="text" class="form-control-plaintext" value="<?= $data->user->os_name ?? l('global.unknown') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="browser_name" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-window-restore text-muted mr-1"></i> <?= l('global.browser_name') ?></label>
                    <input id="browser_name" type="text" class="form-control-plaintext" value="<?= $data->user->browser_name ?? l('global.unknown') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="browser_language" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-language text-muted mr-1"></i> <?= l('global.browser_language') ?></label>
                    <input id="browser_language" type="text" class="form-control-plaintext" value="<?= $data->user->browser_language ?? l('global.unknown') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="last_activity" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-history text-muted mr-1"></i> <?= l('admin_users.last_activity') ?></label>
                    <input id="last_activity" type="text" class="form-control-plaintext" value="<?= $data->user->last_activity ? \Altum\Date::get($data->user->last_activity, 1) : l('global.na') ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="total_logins" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-calendar-alt text-muted mr-1"></i> <?= l('admin_users.total_logins') ?></label>
                    <input id="total_logins" type="text" class="form-control-plaintext" value="<?= $data->user->total_logins ?>" readonly />
                </div>

                <div class="form-group">
                    <label for="api_key" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-laptop-code text-muted mr-1"></i> <?= l('admin_users.api_key') ?></label>
                    <input id="api_key" type="text" class="form-control-plaintext" value="<?= $data->user->api_key ?>" readonly />
                </div>

                <?php if(\Altum\Plugin::is_active('affiliate')): ?>
                    <div class="form-group">
                        <label for="referral_key" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-users text-muted mr-1"></i> <?= l('admin_users.referral_key') ?></label>
                        <input id="referral_key" type="text" class="form-control-plaintext" value="<?= $data->user->referral_key ?>" readonly />
                    </div>

                    <div class="form-group">
                        <label for="referred_by" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-user-plus text-muted mr-1"></i> <?= l('admin_users.referred_by') ?></label>
                        <?php if($data->user->referred_by): ?>
                            <div id="referred_by">
                                <a href="<?= url('admin/user-view/' . $data->user->referred_by) ?>"><?= $data->user->referred_by ?></a>
                            </div>
                        <?php else: ?>
                            <input id="referred_by" type="text" class="form-control-plaintext" value="<?= l('global.none') ?>" readonly />
                        <?php endif ?>
                    </div>

                    <div class="form-group">
                        <label for="referred_by_has_converted" class="font-weight-bold"><i class="fas fa-fw fa-sm fa-dollar-sign text-muted mr-1"></i> <?= l('admin_users.referred_by_has_converted') ?></label>
                        <input id="referred_by_has_converted" type="text" class="form-control-plaintext" value="<?= $data->user->referred_by_has_converted ? l('global.yes') : l('global.no') ?>" readonly />
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<?php if(in_array(settings()->license->type, ['Extended License', 'extended']) && settings()->payment->is_enabled && settings()->payment->taxes_and_billing_is_enabled): ?>
    <div class="accordion">
        <div class="card">
            <div class="card-body p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#billing" aria-expanded="true" aria-controls="billing">
                        <?= l('admin_user_view.billing') ?>
                    </a>
                </h3>
            </div>

            <div id="billing" class="collapse">
                <div class="card-body">

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="billing_type" class="font-weight-bold"><?= l('account.billing.type') ?></label>
                                <input id="billing_type" type="text" class="form-control-plaintext" value="<?= l('account.billing.type_' . $data->user->billing->type) ?>" readonly />
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label for="billing_name" class="font-weight-bold"><?= l('account.billing.name') ?></label>
                                <input id="billing_name" type="text" name="billing_name" class="form-control-plaintext" value="<?= $data->user->billing->name ?>" readonly />
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label for="billing_address" class="font-weight-bold"><?= l('account.billing.address') ?></label>
                                <input id="billing_address" type="text" name="billing_address" class="form-control-plaintext" value="<?= $data->user->billing->address ?>" readonly />
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="form-group">
                                <label for="billing_city" class="font-weight-bold"><?= l('global.city') ?></label>
                                <input id="billing_city" type="text" name="billing_city" class="form-control-plaintext" value="<?= $data->user->billing->city ?>" readonly />
                            </div>
                        </div>

                        <div class="col-12 col-lg-4">
                            <div class="form-group">
                                <label for="billing_county" class="font-weight-bold"><?= l('account.billing.county') ?></label>
                                <input id="billing_county" type="text" name="billing_county" class="form-control-plaintext" value="<?= $data->user->billing->county ?>" readonly />
                            </div>
                        </div>

                        <div class="col-12 col-lg-2">
                            <div class="form-group">
                                <label for="billing_zip" class="font-weight-bold"><?= l('account.billing.zip') ?></label>
                                <input id="billing_zip" type="text" name="billing_zip" class="form-control-plaintext" value="<?= $data->user->billing->zip ?>" readonly />
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label for="billing_country" class="font-weight-bold"><?= l('global.country') ?></label>
                                <input id="billing_country" type="text" class="form-control-plaintext" value="<?= $data->user->billing->country ?>" readonly />
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label for="billing_phone" class="font-weight-bold"><?= l('account.billing.phone') ?></label>
                                <input id="billing_phone" type="text" name="billing_phone" class="form-control-plaintext" value="<?= $data->user->billing->phone ?>" readonly />
                            </div>
                        </div>

                        <div class="col-12" id="billing_tax_id_container">
                            <div class="form-group">
                                <label for="billing_tax_id" class="font-weight-bold"><?= !empty(settings()->business->tax_type) ? settings()->business->tax_type : l('account.billing.tax_id') ?></label>
                                <input id="billing_tax_id" type="text" name="billing_tax_id" class="form-control-plaintext" value="<?= $data->user->billing->tax_id ?>" readonly />
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
<?php endif ?>

<div class="my-5 row justify-content-between">
    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('links.menu.biolink') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->biolink_links) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/links?type=biolink&user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-link mr-1"></i> <?= l('links.menu.link') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->shortened_links) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/links?type=link&user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-calendar mr-1"></i> <?= l('links.menu.event') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->event_links) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/links?type=event&user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('links.menu.file') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->file_links) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/links?type=file&user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-id-card mr-1"></i> <?= l('links.menu.vcard') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->vcard_links) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/links?type=vcard&user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-code mr-1"></i> <?= l('links.menu.static') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->static_links) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/links?type=static&user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-project-diagram mr-1"></i> <?= l('admin_projects.menu') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->projects) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/projects?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-droplet mr-1"></i> <?= l('admin_splash_pages.menu') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->splash_pages) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/splash-pages?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-adjust mr-1"></i> <?= l('admin_pixels.menu') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->pixels) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/pixels?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-qrcode mr-1"></i> <?= l('admin_qr_codes.menu') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->qr_codes) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/qr-codes?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <?php if(\Altum\Plugin::is_active('email-signatures')): ?>
        <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <small class="text-muted"><i class="fas fa-fw fa-sm fa-file-signature mr-1"></i> <?= l('admin_signatures.menu') ?></small>

                    <div class="mt-3"><span class="h4"><?= nr($data->signatures) ?></span></div>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/signatures?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?php if(\Altum\Plugin::is_active('aix')): ?>
        <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <small class="text-muted"><i class="fas fa-fw fa-sm fa-robot mr-1"></i> <?= l('admin_documents.menu') ?></small>

                    <div class="mt-3"><span class="h4"><?= nr($data->documents) ?></span></div>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/documents?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <small class="text-muted"><i class="fas fa-fw fa-sm fa-icons mr-1"></i> <?= l('admin_images.menu') ?></small>

                    <div class="mt-3"><span class="h4"><?= nr($data->images) ?></span></div>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/images?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <small class="text-muted"><i class="fas fa-fw fa-sm fa-microphone-alt mr-1"></i> <?= l('admin_transcriptions.menu') ?></small>

                    <div class="mt-3"><span class="h4"><?= nr($data->transcriptions) ?></span></div>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/transcriptions?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <small class="text-muted"><i class="fas fa-fw fa-sm fa-comments mr-1"></i> <?= l('admin_chats.menu') ?></small>

                    <div class="mt-3"><span class="h4"><?= nr($data->chats) ?></span></div>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/chats?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <small class="text-muted"><i class="fas fa-fw fa-sm fa-voicemail mr-1"></i> <?= l('admin_syntheses.menu') ?></small>

                    <div class="mt-3"><span class="h4"><?= nr($data->syntheses) ?></span></div>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/syntheses?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif ?>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-globe mr-1"></i> <?= l('admin_domains.menu') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->domains) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/domains?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body text-truncate">
                <small class="text-muted"><i class="fas fa-fw fa-sm fa-funnel-dollar mr-1"></i> <?= l('admin_payments.menu') ?></small>

                <div class="mt-3"><span class="h4"><?= nr($data->payments) ?></span></div>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/payments?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="my-5 row justify-content-between">
    <div class="col-12 col-sm-6 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <span class="text-muted"><i class="fas fa-fw fa-sm fa-scroll mr-1"></i> <?= l('admin_users_logs.menu') ?></span>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/users-logs?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <span class="text-muted"><i class="fas fa-fw fa-sm fa-bell mr-1"></i> <?= l('admin_internal_notifications.menu') ?></span>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/internal-notifications?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>

    <?php if(\Altum\Plugin::is_active('push-notifications')): ?>
    <div class="col-12 col-sm-6 p-3 position-relative">
        <div class="card d-flex flex-row h-100 overflow-hidden">
            <div class="card-body">
                <span class="text-muted"><i class="fas fa-fw fa-sm fa-user-check mr-1"></i> <?= l('admin_push_subscribers.menu') ?></span>
            </div>

            <div class="pr-4 d-flex flex-column justify-content-center">
                <a href="<?= url('admin/push-subscribers?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                    <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                </a>
            </div>
        </div>
    </div>
    <?php endif ?>

    <?php if(in_array(settings()->license->type, ['SPECIAL', 'Extended License', 'extended'])): ?>
        <div class="col-12 col-sm-6 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <span class="text-muted"><i class="fas fa-fw fa-sm fa-tags mr-1"></i> <?= l('admin_redeemed_codes.menu') ?></span>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/redeemed-codes?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?php if(\Altum\Plugin::is_active('teams')): ?>
        <div class="col-12 col-sm-6 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <span class="text-muted"><i class="fas fa-fw fa-sm fa-user-shield mr-1"></i> <?= l('admin_teams.menu') ?></span>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/teams?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <span class="text-muted"><i class="fas fa-fw fa-sm fa-user-tag mr-1"></i> <?= l('admin_teams_member.menu') ?></span>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/team-members?user_id=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?php if(\Altum\Plugin::is_active('affiliate')): ?>
        <div class="col-12 col-sm-6 p-3 position-relative">
            <div class="card d-flex flex-row h-100 overflow-hidden">
                <div class="card-body">
                    <span class="text-muted"><i class="fas fa-fw fa-sm fa-wallet mr-1"></i> <?= l('admin_user_view.referred_by') ?></span>
                </div>

                <div class="pr-4 d-flex flex-column justify-content-center">
                    <a href="<?= url('admin/users?referred_by=' . $data->user->user_id) ?>" class="stretched-link">
                        <i class="fas fa-fw fa-angle-right text-gray-500"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endif ?>
</div>
