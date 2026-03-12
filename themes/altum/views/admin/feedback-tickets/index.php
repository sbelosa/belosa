<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-03-08: admin feedback tickets index -->
<div class="container-fluid">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php
    $feedback_statuses = [
        'open' => l('feedback_tickets.status.open'),
        'answered' => l('feedback_tickets.status.answered'),
        'closed' => l('feedback_tickets.status.closed'),
    ];

    $feedback_categories = [
        'change' => l('feedback_tickets.category.change'),
        'add' => l('feedback_tickets.category.add'),
        'bug' => l('feedback_tickets.category.bug'),
        'other' => l('feedback_tickets.category.other'),
    ];
    ?>

    <div class="mb-4 d-flex justify-content-between align-items-center">
        <h1 class="h4 m-0"><i class="fas fa-fw fa-comments mr-1"></i> <?= l('admin_feedback_tickets.header') ?></h1>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if(count($data->feedback_tickets)): ?>
                <div class="table-responsive table-custom-container">
                    <table class="table table-custom table-hover table-striped mb-0">
                        <thead>
                        <tr>
                            <th><?= l('admin_feedback_tickets.ticket_label') ?></th>
                            <th><?= l('global.user') ?></th>
                            <th><?= l('feedback_tickets.category') ?></th>
                            <th><?= l('global.status') ?></th>
                            <th class="text-nowrap"><?= l('feedback_tickets.updated') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach($data->feedback_tickets as $ticket): ?>
                            <tr>
                                <td>
                                    <a href="<?= url('admin/feedback-tickets/ticket/' . $ticket->feedback_ticket_id) ?>">#<?= $ticket->feedback_ticket_id ?> · <?= $ticket->subject ?></a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= get_user_avatar($ticket->user_avatar, $ticket->user_email) ?>" class="rounded-circle mr-2" style="width: 24px; height: 24px;" loading="lazy" />
                                        <!-- Custom code: FC-2026-03-08: open feedback ticket user profile in new tab -->
                                        <a href="<?= url('admin/user-view/' . $ticket->user_id) ?>" target="_blank" rel="noopener noreferrer"><?= $ticket->user_name ?></a>
                                        <!-- /Custom code: FC-2026-03-08 -->
                                    </div>
                                </td>
                                <td class="text-capitalize"><?= $feedback_categories[$ticket->category] ?? $ticket->category ?></td>
                                <td>
                                    <?php $status_class = $ticket->status == 'open' ? 'info' : ($ticket->status == 'answered' ? 'success' : 'secondary') ?>
                                    <span class="badge badge-<?= $status_class ?> text-capitalize"><?= $feedback_statuses[$ticket->status] ?? $ticket->status ?></span>
                                </td>
                                <td class="text-nowrap"><?= \Altum\Date::get($ticket->last_datetime, 2) ?></td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0"><?= l('admin_feedback_tickets.no_data') ?></p>
            <?php endif ?>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-08 -->
