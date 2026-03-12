<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-03-08: user feedback tickets index -->
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
        <h1 class="h4 mb-0"><i class="fas fa-fw fa-comments mr-1"></i> <?= l('feedback_tickets.header') ?></h1>
    </div>

    <div class="alert alert-info">
        <div><?= l('feedback_tickets.intro') ?></div>
        <div class="small mt-1"><?= l('feedback_tickets.beginner_help') ?></div>
    </div>

    <div class="row">
        <div class="col-12 col-xl-5 mb-4 mb-xl-0">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 mb-3"><?= l('feedback_tickets.create_ticket') ?></h2>

                    <form action="" method="post" enctype="multipart/form-data" role="form">
                        <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                        <div class="form-group">
                            <label for="subject"><?= l('feedback_tickets.subject') ?></label>
                            <input type="text" id="subject" name="subject" class="form-control <?= \Altum\Alerts::has_field_errors('subject') ? 'is-invalid' : null ?>" maxlength="128" value="<?= $_POST['subject'] ?? '' ?>" required="required" />
                            <?= \Altum\Alerts::output_field_error('subject') ?>
                            <small class="form-text text-muted"><?= l('feedback_tickets.subject_help') ?></small>
                        </div>

                        <div class="form-group">
                            <label for="category"><?= l('feedback_tickets.category') ?></label>
                            <select id="category" name="category" class="custom-select">
                                <?php $selected_category = $_POST['category'] ?? 'other' ?>
                                <option value="change" <?= $selected_category == 'change' ? 'selected="selected"' : null ?>><?= l('feedback_tickets.category.change') ?></option>
                                <option value="add" <?= $selected_category == 'add' ? 'selected="selected"' : null ?>><?= l('feedback_tickets.category.add') ?></option>
                                <option value="bug" <?= $selected_category == 'bug' ? 'selected="selected"' : null ?>><?= l('feedback_tickets.category.bug') ?></option>
                                <option value="other" <?= $selected_category == 'other' ? 'selected="selected"' : null ?>><?= l('feedback_tickets.category.other') ?></option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message"><?= l('feedback_tickets.message') ?></label>
                            <textarea id="message" name="message" class="form-control <?= \Altum\Alerts::has_field_errors('message') ? 'is-invalid' : null ?>" rows="5" required="required"><?= $_POST['message'] ?? '' ?></textarea>
                            <?= \Altum\Alerts::output_field_error('message') ?>
                            <small class="form-text text-muted"><?= l('feedback_tickets.message_help') ?></small>
                        </div>

                        <div class="form-group">
                            <label for="screenshot"><?= l('feedback_tickets.screenshot_optional') ?></label>
                            <input type="file" id="screenshot" name="screenshot" class="form-control" accept="<?= \Altum\Uploads::get_whitelisted_file_extensions_accept('feedback_tickets') ?>" />
                            <small class="form-text text-muted"><?= l('feedback_tickets.allowed') ?>: <?= \Altum\Uploads::get_whitelisted_file_extensions_accept('feedback_tickets') ?></small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block"><?= l('feedback_tickets.create_ticket') ?></button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-7">
            <div class="card">
                <div class="card-body">
                    <h2 class="h6 mb-3"><?= l('feedback_tickets.your_tickets') ?></h2>

                    <?php if(count($data->feedback_tickets)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-custom">
                                <thead>
                                <tr>
                                    <th><?= l('feedback_tickets.subject') ?></th>
                                    <th><?= l('feedback_tickets.category') ?></th>
                                    <th><?= l('global.status') ?></th>
                                    <th class="text-nowrap"><?= l('feedback_tickets.updated') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach($data->feedback_tickets as $ticket): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('feedback-tickets/ticket/' . $ticket->feedback_ticket_id) ?>"><?= $ticket->subject ?></a>
                                        </td>
                                        <td class="text-capitalize\"><?= $feedback_categories[$ticket->category] ?? $ticket->category ?></td>
                                        <td>
                                            <?php $status_class = $ticket->status == 'open' ? 'info' : ($ticket->status == 'answered' ? 'success' : 'secondary') ?>
                                            <span class="badge badge-<?= $status_class ?> text-capitalize\"><?= $feedback_statuses[$ticket->status] ?? $ticket->status ?></span>
                                        </td>
                                        <td class="text-nowrap"><?= \Altum\Date::get($ticket->last_datetime, 2) ?></td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?= l('feedback_tickets.no_data') ?></p>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-08 -->
