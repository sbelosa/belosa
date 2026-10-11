<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="<?= htmlspecialchars(fcc_t('breadcrumb'), ENT_QUOTES, 'UTF-8') ?>">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('api-documentation') ?>"><?= l('api_documentation.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('personal_notifications.title') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 mb-4"><?= l('personal_notifications.title') ?></h1>

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
                                <span class="badge badge-success mr-3"><?= htmlspecialchars(fcc_t('GET'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/personal-notifications/', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request GET \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars(fcc_t('api/personal-notifications/\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
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
            "user_id": 1,
            "website_id": 1,
            "subscriber_id": 1,
            "name": "Example",
            "title": "Sample message",
            "description": "This is coming from the API.",
            "url": "https://example.com",
            "image_url": null,
            "settings": {
                "is_scheduled": 0,
                "ttl": 2419200,
                "urgency": "normal",
                "is_silent": 0,
                "is_auto_hide": 0,
                "button_title_1": "",
                "button_url_1": "",
                "button_title_2": "",
                "button_url_2": ""
            },
            "is_sent": 1,
            "is_displayed": 1,
            "is_clicked": 0,
            "is_closed": 0,
            "status": "sent",
            "scheduled_datetime": "2024-08-02 17:00:43",
            "sent_datetime": "2024-08-02 17:00:43",
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
        "first": "<?= SITE_URL ?>api/personal-notifications?page=1",
        "last": "<?= SITE_URL ?>api/personal-notifications?page=1",
        "next": null,
        "prev": null,
        "self": "<?= SITE_URL ?>api/personal-notifications?page=1"
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
                                <span class="badge badge-success mr-3"><?= htmlspecialchars(fcc_t('GET'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/personal-notifications/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{personal_notification_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request GET \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/personal-notifications/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{personal_notification_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
        "user_id": 1,
        "website_id": 1,
        "subscriber_id": 1,
        "name": "Example",
        "title": "Sample message",
        "description": "This is coming from the API.",
        "url": "https://example.com",
        "image_url": null,
        "settings": {
            "is_scheduled": 0,
            "ttl": 2419200,
            "urgency": "normal",
            "is_silent": 0,
            "is_auto_hide": 0,
            "button_title_1": "",
            "button_url_1": "",
            "button_title_2": "",
            "button_url_2": ""
        },
        "is_sent": 1,
        "is_displayed": 1,
        "is_clicked": 0,
        "is_closed": 0,
        "status": "sent",
        "scheduled_datetime": "2024-08-02 17:00:43",
        "sent_datetime": "2024-08-02 17:00:43",
        "last_datetime": null,
        "datetime": "<?= get_date() ?>",
    }
}
</pre>
                        </div>
                    </div>
                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#personal_notifications_create" aria-expanded="true" aria-controls="personal_notifications_create">
                        <?= l('api_documentation.create') ?>
                    </a>
                </h3>
            </div>

            <div id="personal_notifications_create" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/personal-notifications', ENT_QUOTES, 'UTF-8') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('website_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('subscriber_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('title'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('description'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('image'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('api_documentation.file') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('button_title_1'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('button_url_1'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('button_title_2'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('button_url_2'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('is_scheduled'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('scheduled_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('is_silent'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('is_auto_hide'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('ttl'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code> , <code>',  array_keys(require APP_PATH . 'includes/notifications_ttl.php')) . '</code>') ?></td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('utm_source'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('utm_medium'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('utm_campaign'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('save'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td><?= l('personal_notifications.save') ?></td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('send'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td><?= l('personal_notifications.send') ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request POST \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars(fcc_t('api/personal-notifications\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'name='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Example name'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'website_id='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary">1</span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'subscriber_id='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary">1</span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'title='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Example title'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'description='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Example description'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#personal_notifications_update" aria-expanded="true" aria-controls="personal_notifications_update">
                        <?= l('api_documentation.update') ?>
                    </a>
                </h3>
            </div>

            <div id="personal_notifications_update" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/personal-notifications/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{personal_notification_id}'), ENT_QUOTES, 'UTF-8') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('website_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('subscriber_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('title'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('description'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('image'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('api_documentation.file') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('button_title_1'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('button_url_1'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('button_title_2'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('button_url_2'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('is_scheduled'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('scheduled_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('is_silent'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('is_auto_hide'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('ttl'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code> , <code>',  array_keys(require APP_PATH . 'includes/notifications_ttl.php')) . '</code>') ?></td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('utm_source'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('utm_medium'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('utm_campaign'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('save'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td><?= l('personal_notifications.save') ?></td>
                            </tr>

                            <tr>
                                <td><?= htmlspecialchars(fcc_t('send'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td><?= l('personal_notifications.send') ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request POST \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/personal-notifications/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{personal_notification_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'name='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Example name'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'website_id='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary">1</span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'subscriber_id='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary">1</span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'title='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Example title'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'description='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Example description'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#personal_notifications_delete" aria-expanded="true" aria-controls="personal_notifications_delete">
                        <?= l('api_documentation.delete') ?>
                    </a>
                </h3>
            </div>

            <div id="personal_notifications_delete" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-danger mr-3"><?= htmlspecialchars(fcc_t('DELETE'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/personal-notifications/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{personal_notification_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request DELETE \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/personal-notifications/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{personal_notification_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

    </div>
</div>

<?php require THEME_PATH . 'views/partials/shiki_highlighter.php' ?>

