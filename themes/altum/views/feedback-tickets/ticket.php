<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-03-08: user feedback ticket detail -->
<div class="container">
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

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h4 mb-0 text-truncate">#<?= $data->feedback_ticket->feedback_ticket_id ?> · <?= $data->feedback_ticket->subject ?></h1>
        <a href="<?= url('feedback-tickets') ?>" class="btn btn-sm btn-light"><?= l('feedback_tickets.back') ?></a>
    </div>

    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <div class="mb-2">
                    <span class="text-muted"><?= l('feedback_tickets.category_label') ?>:</span>
                    <strong class="text-capitalize"><?= $feedback_categories[$data->feedback_ticket->category] ?? $data->feedback_ticket->category ?></strong>
                </div>
                <div>
                    <?php $status_class = $data->feedback_ticket->status == 'open' ? 'info' : ($data->feedback_ticket->status == 'answered' ? 'success' : 'secondary') ?>
                    <span class="badge badge-<?= $status_class ?> text-capitalize"><?= $feedback_statuses[$data->feedback_ticket->status] ?? $data->feedback_ticket->status ?></span>
                </div>
            </div>

            <?php if($data->feedback_ticket->status != 'closed'): ?>
                <form action="" method="post" class="mt-3 mt-md-0">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                    <input type="hidden" name="action_type" value="close" />
                    <button type="submit" class="btn btn-outline-secondary btn-sm"><?= l('feedback_tickets.close_ticket') ?></button>
                </form>
            <?php endif ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3"><?= l('feedback_tickets.conversation') ?></h2>

            <?php foreach($data->messages as $message): ?>
                <div class="border rounded p-3 mb-3 <?= $message->is_admin_reply ? 'bg-gray-50' : null ?>">
                    <div class="d-flex justify-content-between mb-2">
                        <strong><?= $message->is_admin_reply ? l('feedback_tickets.author_admin') : l('feedback_tickets.author_you') ?></strong>
                        <small class="text-muted"><?= \Altum\Date::get($message->datetime, 2) ?></small>
                    </div>

                    <div class="mb-0"><?= nl2br($message->message) ?></div>

                    <?php if($message->attachment): ?>
                        <a href="<?= \Altum\Uploads::get_full_url('feedback_tickets') . $message->attachment ?>" target="_blank" class="d-inline-block mt-2"><?= l('feedback_tickets.view_attachment') ?></a>
                    <?php endif ?>
                </div>
            <?php endforeach ?>
        </div>
    </div>

    <?php if($data->feedback_ticket->status != 'closed'): ?>
        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-3"><?= l('feedback_tickets.reply') ?></h2>

                <form action="" method="post" enctype="multipart/form-data" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />
                    <input type="hidden" name="action_type" value="reply" />

                    <div class="form-group">
                        <label for="message"><?= l('feedback_tickets.message') ?></label>
                        <textarea id="message" name="message" class="form-control <?= \Altum\Alerts::has_field_errors('message') ? 'is-invalid' : null ?>" rows="5" required="required"><?= $_POST['message'] ?? '' ?></textarea>
                        <?= \Altum\Alerts::output_field_error('message') ?>
                    </div>

                    <div class="form-group">
                        <label for="attachment"><?= l('feedback_tickets.attachment_optional') ?></label>
                        <input type="file" id="attachment" name="attachment" class="form-control" accept="<?= \Altum\Uploads::get_whitelisted_file_extensions_accept('feedback_tickets') ?>" />
                    </div>

                    <button type="submit" class="btn btn-primary"><?= l('feedback_tickets.send_reply') ?></button>
                </form>
            </div>
        </div>
    <?php endif ?>
</div>
<!-- /Custom code: FC-2026-03-08 -->
