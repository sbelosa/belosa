<?php defined('ALTUMCODE') || die() ?>

<?php
/* Custom code: FC-2026-03-17: admin billing risk investigation page */
$state_badges = [
    'past_due' => 'warning',
    'past_due_critical' => 'danger',
    'access_revoked' => 'secondary',
    'healthy' => 'success',
];
/* /Custom code: FC-2026-03-17 */
?>

<div class="d-flex flex-column flex-md-row justify-content-between mb-4">
    <div>
        <h1 class="h3 mb-2 mb-md-0 text-truncate"><i class="fas fa-fw fa-xs fa-triangle-exclamation text-primary-900 mr-2"></i> <?= l('admin_billing_risk.header') ?></h1>
        <p class="text-muted mb-0"><?= l('admin_billing_risk.subheader') ?></p>
    </div>

    <div class="mt-3 mt-md-0">
        <span class="badge badge-light"><?= sprintf(l('admin_billing_risk.total'), nr($data->total_rows)) ?></span>
    </div>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<!-- Custom code: FC-2026-03-17: filters for admin billing risk list -->
<form action="" method="get" class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-12 col-lg-4">
                <div class="form-group mb-lg-0">
                    <label for="billing_risk_search" class="small text-muted"><?= l('global.search') ?></label>
                    <input id="billing_risk_search" type="search" name="search" class="form-control" value="<?= $data->search ?>" placeholder="<?= l('admin_billing_risk.search_placeholder') ?>" />
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="form-group mb-lg-0">
                    <label for="billing_risk_state" class="small text-muted"><?= l('admin_billing_risk.state') ?></label>
                    <select id="billing_risk_state" name="state" class="custom-select">
                        <option value=""><?= l('global.all') ?></option>
                        <option value="past_due" <?= $data->state == 'past_due' ? 'selected="selected"' : null ?>><?= l('admin_billing_risk.state_past_due') ?></option>
                        <option value="past_due_critical" <?= $data->state == 'past_due_critical' ? 'selected="selected"' : null ?>><?= l('admin_billing_risk.state_past_due_critical') ?></option>
                        <option value="access_revoked" <?= $data->state == 'access_revoked' ? 'selected="selected"' : null ?>><?= l('admin_billing_risk.state_access_revoked') ?></option>
                    </select>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="form-group mb-lg-0">
                    <label for="billing_risk_processor" class="small text-muted"><?= l('pay.custom_plan.payment_processor') ?></label>
                    <select id="billing_risk_processor" name="processor" class="custom-select">
                        <option value=""><?= l('global.all') ?></option>
                        <?php foreach($data->payment_processors as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $data->processor == $key ? 'selected="selected"' : null ?>><?= l('pay.custom_plan.' . $key) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="col-12 col-lg-2 d-flex align-items-end">
                <div class="w-100 d-flex">
                    <button type="submit" class="btn btn-primary btn-block mr-2"><?= l('global.submit') ?></button>
                    <a href="<?= url('admin/billing-risk') ?>" class="btn btn-outline-secondary btn-block mt-0"><?= l('global.reset') ?></a>
                </div>
            </div>
        </div>
    </div>
</form>
<!-- /Custom code: FC-2026-03-17 -->

<?php if(empty($data->risk_users)): ?>
    <div class="alert alert-success mb-0"><?= l('admin_billing_risk.no_data') ?></div>
<?php else: ?>
    <!-- Custom code: FC-2026-03-17: billing risk investigation table -->
    <div class="table-responsive table-custom-container">
        <table class="table table-custom">
            <thead>
            <tr>
                <th><?= l('global.user') ?></th>
                <th><?= l('admin_billing_risk.state') ?></th>
                <th><?= l('admin_billing_risk.reason') ?></th>
                <th><?= l('admin_billing_risk.grace_until') ?></th>
                <th><?= l('admin_billing_risk.next_retry_at') ?></th>
                <th><?= l('admin_billing_risk.last_notification') ?></th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($data->risk_users as $row): ?>
                <?php $user = $row['user']; ?>
                <?php $summary = $row['summary']; ?>
                <?php $state = $summary['billing_state'] ?? 'healthy'; ?>
                <tr>
                    <td class="text-nowrap">
                        <div class="d-flex align-items-center">
                            <a href="<?= url('admin/user-view/' . $user->user_id) ?>">
                                <img src="<?= get_user_avatar($user->avatar, $user->email) ?>" referrerpolicy="no-referrer" loading="lazy" class="user-avatar rounded-circle mr-3" alt="" />
                            </a>

                            <div class="d-flex flex-column">
                                <div><a href="<?= url('admin/user-view/' . $user->user_id) ?>"><?= $user->name ?></a></div>
                                <span class="text-muted small"><?= $user->email ?></span>
                                <span class="text-muted small"><?= $user->payment_subscription_id ?: l('global.none') ?></span>
                            </div>
                        </div>
                    </td>

                    <td class="text-nowrap">
                        <span class="badge badge-<?= $state_badges[$state] ?? 'light' ?>"><?= l('admin_billing_risk.state_' . $state) ?></span>
                        <div class="small text-muted mt-1"><?= $summary['stripe_status'] ? l('admin_billing_risk.stripe_status') . ': ' . $summary['stripe_status'] : l('global.none') ?></div>
                    </td>

                    <td>
                        <div><?= $summary['last_failed_reason_text'] ?: l('global.unknown') ?></div>
                        <div class="small text-muted">
                            <?= $summary['last_failed_reason_code'] ?: l('global.none') ?>
                            <?php if(!empty($summary['last_failed_at'])): ?>
                                · <?= \Altum\Date::get($summary['last_failed_at'], 2) ?>
                            <?php endif ?>
                        </div>
                    </td>

                    <td class="text-nowrap">
                        <?= !empty($summary['grace_until']) ? \Altum\Date::get($summary['grace_until'], 2) : l('global.none') ?>
                    </td>

                    <td class="text-nowrap">
                        <?= !empty($summary['next_retry_at']) ? \Altum\Date::get($summary['next_retry_at'], 2) : l('global.none') ?>
                    </td>

                    <td class="text-nowrap">
                        <div><?= $summary['last_notification_stage'] ? l('admin_billing_risk.notification_' . $summary['last_notification_stage']) : l('global.none') ?></div>
                        <div class="small text-muted"><?= !empty($summary['last_notification_at']) ? \Altum\Date::get($summary['last_notification_at'], 2) : l('global.none') ?></div>
                    </td>

                    <td class="text-nowrap">
                        <a href="<?= url('admin/user-view/' . $user->user_id) ?>" class="btn btn-sm btn-outline-primary"><?= l('global.view') ?></a>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <!-- /Custom code: FC-2026-03-17 -->

    <?= $data->pagination ?>
<?php endif ?>