<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="<?= htmlspecialchars(fcc_t('breadcrumb'), ENT_QUOTES, 'UTF-8') ?>">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('api-documentation') ?>"><?= l('api_documentation.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('api_documentation.notification_handlers') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 mb-4"><?= l('api_documentation.notification_handlers') ?></h1>

    <div class="accordion">
        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#read_all" aria-expanded="true" aria-controls="read_all">
                        <?= l('api_documentation.read_all') ?>
                    </a>
                </h3>
            </div>

            <div id="read_all" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-success mr-3"><?= htmlspecialchars(fcc_t('GET'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/notification-handlers/', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request GET \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars(fcc_t('api/notification-handlers/\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive table-custom-container mb-4">
                        <table class="table table-custom">
                            <thead>
                            <tr>
                                <th><?= l('api_documentation.parameters') ?></th>
                                <th><?= l('global.details') ?></th>
                                <th><?= l('global.description') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('page'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td><?= l('api_documentation.filters.page') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('results_per_page'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.filters.results_per_page'), '<code>' . implode('</code> , <code>', [10, 25, 50, 100, 250, 500, 1000]) . '</code>', 25) ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.response') ?></label>
                        <pre data-shiki="json">
{
    "data": [
        {
            "id": 1,
            "type": "email",
            "name": "Work email",
            "settings": {
                "email": "hey@example.com"
            },
            "is_enabled": true,
            "last_datetime": null,
            "datetime": "<?= get_date() ?>",
        }
    ],
    "meta": {
        "page": 1,
        "results_per_page": 25,
        "total": 1,
        "total_pages": 1
    },
    "links": {
        "first": "<?= SITE_URL ?>api/notification-handlers?page=1",
        "last": "<?= SITE_URL ?>api/notification-handlers?page=1",
        "next": null,
        "prev": null,
        "self": "<?= SITE_URL ?>api/notification-handlers?page=1"
    }
}</pre>
                        </div>
                    </div>
                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#read" aria-expanded="true" aria-controls="read">
                        <?= l('api_documentation.read') ?>
                    </a>
                </h3>
            </div>

            <div id="read" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-success mr-3"><?= htmlspecialchars(fcc_t('GET'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/notification-handlers/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{notification_handler_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request GET \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/notification-handlers/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{notification_handler_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.response') ?></label>
                        <pre data-shiki="json">
{
    "data": {
        "id": 1,
        "type": "email",
        "name": "Work email",
        "settings": {
            "email": "hey@example.com"
        },
        "is_enabled": true,
        "last_datetime": null,
        "datetime": "<?= get_date() ?>",
    }
}</pre>
                        </div>
                    </div>
                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#notification_handlers_create" aria-expanded="true" aria-controls="notification_handlers_create">
                        <?= l('api_documentation.create') ?>
                    </a>
                </h3>
            </div>

            <div id="notification_handlers_create" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/notification-handlers', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive table-custom-container mb-4">
                        <table class="table table-custom">
                            <thead>
                            <tr>
                                <th><?= l('api_documentation.parameters') ?></th>
                                <th><?= l('global.details') ?></th>
                                <th><?= l('global.description') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code> , <code>',  array_keys(require APP_PATH . 'includes/notification_handlers.php')) . '</code>') ?></td>
                            </tr>

                            <?php if(settings()->notification_handlers->email_is_enabled): ?>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('email'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = email</span>') ?> <?= l('notification_handlers.email') ?></td>
                            </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->webhook_is_enabled): ?>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('webhook'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = webhook</span>') ?> <?= l('notification_handlers.webhook') ?></td>
                            </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->slack_is_enabled): ?>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('slack'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = slack</span>') ?> <?= l('notification_handlers.slack') ?></td>
                            </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->discord_is_enabled): ?>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('discord'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = discord</span>') ?> <?= l('notification_handlers.discord') ?></td>
                            </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->telegram_is_enabled): ?>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('telegram'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = telegram</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('telegram_chat_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = telegram</span>') ?> <?= l('notification_handlers.telegram_chat_id') ?></td>
                            </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->microsoft_teams_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('microsoft_teams'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = microsoft_teams</span>') ?> <?= l('notification_handlers.microsoft_teams') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->google_chat_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('google_chat'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = google_chat</span>') ?> <?= l('notification_handlers.google_chat') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->x_is_enabled): ?>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('x_consumer_key'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = x</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('x_consumer_secret'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = x</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('x_access_token'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = x</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('x_access_token_secret'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = x</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                            </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->push_subscriber_id_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('push_subscriber_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = push_subscriber_id</span>') ?> <?= l('notification_handlers.push_subscriber_id') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->twilio_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('twilio'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = twilio</span>') ?> <?= l('notification_handlers.twilio') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->twilio_call_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('twilio_call'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = twilio_call</span>') ?> <?= l('notification_handlers.twilio') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->whatsapp_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('whatsapp'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = whatsapp</span>') ?> <?= l('notification_handlers.twilio') ?></td>
                                </tr>
                            <?php endif ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request POST \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars(fcc_t('api/notification-handlers\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'name='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Example'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'type='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('email'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'email='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('hello@example.com'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.response') ?></label>
                        <pre data-shiki="json">
{
    "data": {
        "id": 1
    }
}</pre>
                        </div>
                    </div>

                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#notification_handlers_update" aria-expanded="true" aria-controls="notification_handlers_update">
                        <?= l('api_documentation.update') ?>
                    </a>
                </h3>
            </div>

            <div id="notification_handlers_update" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/notification-handlers/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{notification_handler_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive table-custom-container mb-4">
                        <table class="table table-custom">
                            <thead>
                            <tr>
                                <th><?= l('api_documentation.parameters') ?></th>
                                <th><?= l('global.details') ?></th>
                                <th><?= l('global.description') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code> , <code>',  array_keys(require APP_PATH . 'includes/notification_handlers.php')) . '</code>') ?></td>
                            </tr>
                            <?php if(settings()->notification_handlers->email_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('email'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = email</span>') ?> <?= l('notification_handlers.email') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->webhook_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('webhook'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = webhook</span>') ?> <?= l('notification_handlers.webhook') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->slack_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('slack'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = slack</span>') ?> <?= l('notification_handlers.slack') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->discord_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('discord'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = discord</span>') ?> <?= l('notification_handlers.discord') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->telegram_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('telegram'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = telegram</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                                </tr>

                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('telegram_chat_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = telegram</span>') ?> <?= l('notification_handlers.telegram_chat_id') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->microsoft_teams_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('microsoft_teams'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = microsoft_teams</span>') ?> <?= l('notification_handlers.microsoft_teams') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->google_chat_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('google_chat'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = google_chat</span>') ?> <?= l('notification_handlers.google_chat') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->x_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('x_consumer_key'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = x</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                                </tr>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('x_consumer_secret'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = x</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                                </tr>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('x_access_token'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = x</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                                </tr>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('x_access_token_secret'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = x</span>') ?> <?= l('notification_handlers.telegram') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->push_subscriber_id_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('push_subscriber_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = push_subscriber_id</span>') ?> <?= l('notification_handlers.push_subscriber_id') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->twilio_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('twilio'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = twilio</span>') ?> <?= l('notification_handlers.twilio') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->twilio_call_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('twilio_call'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = twilio_call</span>') ?> <?= l('notification_handlers.twilio') ?></td>
                                </tr>
                            <?php endif ?>

                            <?php if(settings()->notification_handlers->whatsapp_is_enabled): ?>
                                <tr>
                                    <td><?= htmlspecialchars(fcc_t('whatsapp'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                        <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    </td>
                                    <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = whatsapp</span>') ?> <?= l('notification_handlers.twilio') ?></td>
                                </tr>
                            <?php endif ?>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('is_enabled'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request POST \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/notification-handlers/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{notification_handler_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'name='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Example new name'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'is_enabled='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary">1</span>' \<br />
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.response') ?></label>
                        <pre data-shiki="json">
{
    "data": {
        "id": 1
    }
}</pre>
                        </div>
                    </div>

                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#notification_handlers_delete" aria-expanded="true" aria-controls="notification_handlers_delete">
                        <?= l('api_documentation.delete') ?>
                    </a>
                </h3>
            </div>

            <div id="notification_handlers_delete" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-danger mr-3"><?= htmlspecialchars(fcc_t('DELETE'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/notification-handlers/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{notification_handler_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request DELETE \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/notification-handlers/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{notification_handler_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

    </div>
</div>

<?php require THEME_PATH . 'views/partials/shiki_highlighter.php' ?>

