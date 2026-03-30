<?php defined('ALTUMCODE') || die() ?>

<?php $summary = $data->hub['summary']; ?>

<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
    <div>
        <h1 class="h3 mb-2"><i class="fas fa-fw fa-xs fa-envelope-open-text text-primary-900 mr-2"></i> <?= l('admin_automations.header') ?></h1>
        <p class="text-muted mb-0"><?= l('admin_automations.hub_subheader') ?></p>
    </div>

    <div class="d-flex flex-column flex-md-row mt-3 mt-lg-0">
        <a href="<?= url('admin/broadcast-create') ?>" class="btn btn-outline-primary mb-2 mb-md-0 mr-md-2"><?= l('admin_automations.hub_new_mail') ?></a>
        <a href="<?= url('admin/automation-create') ?>" class="btn btn-primary mb-2 mb-md-0 mr-md-2"><?= l('admin_automations.hub_new_automation') ?></a>
        <a href="<?= url('admin/settings/smtp') ?>" class="btn btn-gray-300"><?= l('admin_automations.hub_smtp_settings') ?></a>
    </div>
</div>

<?= \Altum\Alerts::output_alerts() ?>

<div class="row mb-4">
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1"><?= l('admin_automations.hub_card_emails') ?></div><div class="h3 mb-0"><?= nr($data->hub['totals']['broadcasts']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1"><?= l('admin_automations.hub_card_automations') ?></div><div class="h3 mb-0"><?= nr($data->hub['totals']['automations']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Poslano</div><div class="h3 mb-0"><?= nr($summary['sent']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Isporučeno</div><div class="h3 mb-0"><?= nr($summary['delivered']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1">Otvoreno</div><div class="h3 mb-0"><?= nr($summary['opened']) ?></div></div></div></div>
    <div class="col-6 col-xl-2 mb-3"><div class="card h-100"><div class="card-body"><div class="text-muted small text-uppercase mb-1"><?= l('admin_automations.hub_card_clicks') ?></div><div class="h3 mb-0"><?= nr($summary['clicked']) ?></div></div></div></div>
</div>

<div class="alert alert-light border mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center">
        <div>
            <strong><?= l('admin_automations.hub_unsubscribed_title') ?>:</strong> <?= nr($summary['unsubscribed']) ?>
            <div class="text-muted small"><?= l('admin_automations.hub_unsubscribed_text') ?></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h5 mb-1"><?= l('admin_automations.hub_broadcasts_title') ?></h2>
                <div class="small text-muted"><?php if(($data->broadcasts_total ?? 0) > count($data->broadcasts)): ?><?= sprintf(l('admin_automations.hub_broadcasts_shown_total'), nr(count($data->broadcasts)), nr($data->broadcasts_total)) ?><?php else: ?><?= sprintf(l('admin_automations.hub_broadcasts_shown'), nr(count($data->broadcasts))) ?><?php endif ?></div>
            </div>
            <div class="d-flex align-items-center">
                <?php if(($data->broadcasts_total ?? 0) > ($data->broadcasts_default_display_limit ?? 5)): ?>
                    <?php if(!empty($data->show_all_broadcasts)): ?>
                        <a href="<?= url('admin/automations') ?>" class="btn btn-sm btn-outline-secondary mr-2"><?= l('admin_automations.hub_hide_older') ?></a>
                    <?php else: ?>
                        <a href="<?= url('admin/automations?show_all_broadcasts=1') ?>" class="btn btn-sm btn-outline-secondary mr-2"><?= l('admin_automations.hub_show_all') ?></a>
                    <?php endif ?>
                <?php endif ?>

                <a href="<?= url('admin/broadcast-create') ?>" class="btn btn-sm btn-primary"><?= l('admin_automations.hub_add_mail') ?></a>
            </div>
        </div>

        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th><?= l('admin_automations.hub_table_name') ?></th>
                    <th><?= l('admin_automations.hub_table_segment') ?></th>
                    <th><?= l('admin_automations.hub_table_status') ?></th>
                    <th><?= l('admin_automation_update.table_sent') ?></th>
                    <th><?= l('admin_automation_update.table_delivered') ?></th>
                    <th><?= l('admin_automation_update.table_opened') ?></th>
                    <th><?= l('admin_automation_update.table_clicked') ?></th>
                    <th><?= l('admin_automation_update.table_unsubscribed') ?></th>
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
                        <td class="text-nowrap"><span class="badge badge-light"><?php if($broadcast->status == 'draft'): ?><?= l('admin_broadcasts.status.draft') ?><?php elseif($broadcast->status == 'processing'): ?><?= l('admin_broadcasts.status.processing') ?><?php else: ?><?= l('admin_broadcasts.status.sent') ?><?php endif ?></span></td>
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
                        <td class="text-nowrap text-right"><a href="<?= $broadcast_link ?>" class="btn btn-sm btn-outline-primary"><?= l('admin_automations.hub_open') ?></a></td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->broadcasts)): ?><tr><td colspan="9" class="text-center text-muted py-4"><?= l('admin_automations.hub_no_mails') ?></td></tr><?php endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0"><?= l('admin_automations.hub_automations_title') ?></h2>
            <a href="<?= url('admin/automation-create') ?>" class="btn btn-sm btn-primary"><?= l('admin_automations.hub_add_automation') ?></a>
        </div>

        <div class="table-responsive table-custom-container">
            <table class="table table-custom mb-0">
                <thead>
                <tr>
                    <th><?= l('admin_automations.hub_table_name') ?></th>
                    <th><?= l('admin_automation_update.segment_label') ?></th>
                    <th><?= l('admin_automations.hub_table_live_users') ?></th>
                    <th><?= l('admin_automations.hub_table_active') ?></th>
                    <th><?= l('admin_automation_update.table_sent') ?></th>
                    <th><?= l('admin_automation_update.table_delivered') ?></th>
                    <th><?= l('admin_automation_update.table_opened') ?></th>
                    <th><?= l('admin_automation_update.table_clicked') ?></th>
                    <th><?= l('admin_automation_update.table_unsubscribed') ?></th>
                    <th><?= l('admin_automation_update.summary_goal_completed') ?></th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($data->automations as $automation): ?>
                    <?php $automation_summary = $automation->analytics['summary']; ?>
                    <tr>
                        <td class="text-nowrap"><div class="font-weight-bold"><a href="<?= url('admin/automation-update/' . $automation->automation_id) ?>"><?= e($automation->name) ?></a></div><div class="small text-muted"><?= sprintf(l('admin_automations.hub_steps_count'), nr($automation->steps_total)) ?></div></td>
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
                        <td class="text-nowrap text-right"><a href="<?= url('admin/automation-update/' . $automation->automation_id) ?>" class="btn btn-sm btn-outline-primary"><?= l('admin_automations.hub_open') ?></a></td>
                    </tr>
                <?php endforeach ?>
                <?php if(empty($data->automations)): ?><tr><td colspan="11" class="text-center text-muted py-4"><?= l('admin_automations.hub_no_automations') ?></td></tr><?php endif ?>
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
                        <h5 class="modal-title"><?= sprintf(l('admin_automations.hub_unsubscribed_modal_title'), e($broadcast->name)) ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="small text-muted mb-3"><?= sprintf(l('admin_automations.hub_unsubscribed_modal_text'), nr($broadcast->unsubscribed_messages_limit), ((int) ($broadcast->unsubscribed_unique_total ?? 0) > (int) $broadcast->unsubscribed_messages_limit) ? sprintf(l('admin_automations.hub_unsubscribed_modal_total_suffix'), nr($broadcast->unsubscribed_unique_total)) : '', nr($broadcast->analytics['summary']['unsubscribed'] ?? 0)) ?></div>
                        <div class="table-responsive table-custom-container">
                            <table class="table table-custom mb-0">
                                <thead>
                                <tr>
                                    <th><?= l('admin_automations.hub_unsubscribed_modal_user') ?></th>
                                    <th><?= l('admin_automations.hub_unsubscribed_modal_email') ?></th>
                                    <th><?= l('admin_automations.hub_unsubscribed_modal_time') ?></th>
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
                        <a href="<?= in_array($broadcast->status, ['sent', 'processing']) ? url('admin/broadcast-view/' . $broadcast->broadcast_id . '?status_filter=unsubscribed') : url('admin/broadcast-update/' . $broadcast->broadcast_id) ?>" class="btn btn-outline-primary"><?= l('admin_automations.hub_open_details') ?></a>
                        <button type="button" class="btn btn-gray-300" data-dismiss="modal"><?= l('admin_automations.hub_close') ?></button>
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
                        <h5 class="modal-title"><?= sprintf(l('admin_automations.hub_unsubscribed_modal_title'), e($automation->name)) ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="small text-muted mb-3"><?= sprintf(l('admin_automations.hub_unsubscribed_modal_text'), nr($automation->unsubscribed_messages_limit), ((int) ($automation->unsubscribed_unique_total ?? 0) > (int) $automation->unsubscribed_messages_limit) ? sprintf(l('admin_automations.hub_unsubscribed_modal_total_suffix'), nr($automation->unsubscribed_unique_total)) : '', nr($automation->analytics['summary']['unsubscribed'] ?? 0)) ?></div>
                        <div class="table-responsive table-custom-container">
                            <table class="table table-custom mb-0">
                                <thead>
                                <tr>
                                    <th><?= l('admin_automations.hub_unsubscribed_modal_user') ?></th>
                                    <th><?= l('admin_automations.hub_unsubscribed_modal_email') ?></th>
                                    <th><?= l('admin_automations.hub_unsubscribed_modal_time') ?></th>
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
                        <a href="<?= url('admin/automation-update/' . $automation->automation_id . '?status_filter=unsubscribed') ?>" class="btn btn-outline-primary"><?= l('admin_automations.hub_open_details') ?></a>
                        <button type="button" class="btn btn-gray-300" data-dismiss="modal"><?= l('admin_automations.hub_close') ?></button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>
<?php endforeach ?>