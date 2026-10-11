<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="<?= htmlspecialchars(fcc_t('breadcrumb'), ENT_QUOTES, 'UTF-8') ?>">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('api-documentation') ?>"><?= l('api_documentation.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('qr_codes.title') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 mb-4"><?= l('qr_codes.title') ?></h1>

    <div class="accordion">
        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#qr_codes_read_all" aria-expanded="true" aria-controls="qr_codes_read_all">
                        <?= l('api_documentation.read_all') ?>
                    </a>
                </h3>
            </div>

            <div id="qr_codes_read_all" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-success mr-3"><?= htmlspecialchars(fcc_t('GET'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/qr-codes/', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request GET \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars(fcc_t('api/qr-codes/\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
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
            "type": "url",
            "name": "Example name",
            "qr_code": "<?= SITE_URL ?>uploads/qr_code/example.svg",
            "qr_code_logo": null,
            "qr_code_background": null,
            "settings": {
                "foreground_type": "color",
                "foreground_color": "#000000",
                "background_color": "#ffffff",
                "custom_eyes_color": false,
                "qr_code_logo_size": 25,
                "size": 500,
                "margin": 0,
                "ecc": "L",
                "url": "https://example.com"
                ...
            },
            "embedded_data": "https://example.com",
            "last_datetime": null,
            "datetime": "<?= get_date() ?>",
        },
    ],
    "meta": {
        "page": 1,
        "results_per_page": 25,
        "total": 1,
        "total_pages": 1
    },
    "links": {
        "first": "<?= SITE_URL ?>api/qr-codes?page=1",
        "last": "<?= SITE_URL ?>api/qr-codes?page=1",
        "next": null,
        "prev": null,
        "self": "<?= SITE_URL ?>api/qr-codes?page=1"
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#qr_codes_read" aria-expanded="true" aria-controls="qr_codes_read">
                        <?= l('api_documentation.read') ?>
                    </a>
                </h3>
            </div>

            <div id="qr_codes_read" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-success mr-3"><?= htmlspecialchars(fcc_t('GET'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/qr-codes/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{qr_code_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request GET \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/qr-codes/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{qr_code_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
        "type": "url",
        "name": "Example name",
        "qr_code": "<?= SITE_URL ?>uploads/qr_code/example.svg",
        "qr_code_logo": null,
        "qr_code_background": null,
        "settings": {
            "foreground_type": "color",
            "foreground_color": "#000000",
            "background_color": "#ffffff",
            "custom_eyes_color": false,
            "qr_code_logo_size": 25,
            "size": 500,
            "margin": 0,
            "ecc": "L",
            "url": "https://example.com"
            ...
        },
        "embedded_data": "https://example.com",
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#qr_codes_create" aria-expanded="true" aria-controls="qr_codes_create">
                        <?= l('api_documentation.create') ?>
                    </a>
                </h3>
            </div>

            <div id="qr_codes_create" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/qr-codes', ENT_QUOTES, 'UTF-8') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('project_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', array_keys((require APP_PATH . 'includes/enabled_qr_codes.php')))) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('is_bulk'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = text</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('style'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>

                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('inner_eye_style'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['square', 'dot', 'rounded', 'diamond', 'flower', 'leaf',])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('outer_eye_style'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['square', 'circle', 'rounded', 'flower', 'leaf',])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['color', 'gradient'])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">foreground_type = color</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_gradient_style'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['vertical', 'horizontal', 'diagonal', 'inverse_diagonal', 'radial'])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_gradient_one'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">foreground_type = gradient</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_gradient_two'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">foreground_type = gradient</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('background_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('background_color_transparency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('custom_eyes_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('eyes_inner_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">custom_eyes_color = 1</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('eyes_outer_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">custom_eyes_color = 1</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_logo'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('api_documentation.file') ?></span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_logo_size'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>5-35</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_background'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('api_documentation.file') ?></span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_background_transparency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>0-100</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_foreground'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('api_documentation.file') ?></span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_foreground_transparency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>0-100</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', array_keys(require APP_PATH . 'includes/qr_codes_frames.php'))) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_text'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_text_size'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>-5 - 5</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_text_font'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', array_keys(require APP_PATH . 'includes/qr_codes_frames_text_fonts.php'))) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_custom_colors'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">frame_custom_colors = 1</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_text_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">frame_custom_colors = 1</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('size'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>50-2000</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('margin'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>0-25</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('ecc'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['L', 'M', 'Q', 'H'])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('text'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = text</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = url</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('phone'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = phone</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".sms_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('sms'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = sms</span>') ?></td>
                            </tr>
                            <tr class="collapse sms_collapse">
                                <td><?= htmlspecialchars(fcc_t('sms_body'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = sms</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".email_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('email'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = email</span>') ?></td>
                            </tr>
                            <tr class="collapse email_collapse">
                                <td><?= htmlspecialchars(fcc_t('email_subject'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = email</span>') ?></td>
                            </tr>
                            <tr class="collapse email_collapse">
                                <td><?= htmlspecialchars(fcc_t('email_body'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = email</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".whatsapp_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('whatsapp'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = whatsapp</span>') ?></td>
                            </tr>
                            <tr class="collapse whatsapp_collapse">
                                <td><?= htmlspecialchars(fcc_t('whatsapp_body'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = whatsapp</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('facetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = facetime</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".location_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('location'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse location_collapse">
                                <td><?= htmlspecialchars(fcc_t('location_latitude'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span></td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = location</span>') ?></td>
                            </tr>
                            <tr class="collapse location_collapse">
                                <td><?= htmlspecialchars(fcc_t('location_longitude'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span></td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = location</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".wifi_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('wifi'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse wifi_collapse">
                                <td><?= htmlspecialchars(fcc_t('wifi_ssid'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = wifi</span>') ?></td>
                            </tr>
                            <tr class="collapse wifi_collapse">
                                <td><?= htmlspecialchars(fcc_t('wifi_encryption'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['nopass', 'WEP', 'WPA/WPA2', 'H'])) ?> <?= htmlspecialchars(fcc_t('(type=wifi)'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <tr class="collapse wifi_collapse">
                                <td><?= htmlspecialchars(fcc_t('wifi_password'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = wifi</span>') ?></td>
                            </tr>
                            <tr class="collapse wifi_collapse">
                                <td><?= htmlspecialchars(fcc_t('wifi_is_hidden'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span></td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = wifi</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".event_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('event'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_location'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_note'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_timezone'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_start_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_end_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_first_alert_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_second_alert_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".crypto_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('crypto'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse crypto_collapse">
                                <td><?= htmlspecialchars(fcc_t('crypto_coin'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = crypto</span>') ?></td>
                            </tr>
                            <tr class="collapse crypto_collapse">
                                <td><?= htmlspecialchars(fcc_t('crypto_address'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = crypto</span>') ?></td>
                            </tr>
                            <tr class="collapse crypto_collapse">
                                <td><?= htmlspecialchars(fcc_t('crypto_amount'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = crypto</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".vcard_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('vcard'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_first_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_last_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_company'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_phone_number_label[index]'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_phone_number_value[index]'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_email'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_job_title'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_birthday'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_street'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_city'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_zip'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_region'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_country'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_note'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_social_label[index]'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_social_value[index]'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".paypal_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('paypal'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['buy_now', 'add_to_cart', 'donation'])) ?> <?= htmlspecialchars(fcc_t('(type=paypal)'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_email'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_title'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_currency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_price'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_thank_you_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_cancel_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>

                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".upi_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('upi'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_payee_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_payee_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_currency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_amount'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_transaction_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_transaction_note'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_transaction_reference'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_thank_you_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>

                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".epc_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('epc'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_iban'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_payee_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_currency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_amount'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_bic'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_remittance_reference'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_remittance_text'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('information'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>

                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".pix_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('pix'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_payee_key'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_payee_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_currency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_amount'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_city'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_transaction_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_description'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>

                            </tbody>
                        </table>
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
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request POST \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars(fcc_t('api/qr-codes\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'name='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('New York'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'type='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('text'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'text='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Hello!'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
}
</pre>
                        </div>
                    </div>

                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#qr_codes_update" aria-expanded="true" aria-controls="qr_codes_update">
                        <?= l('api_documentation.update') ?>
                    </a>
                </h3>
            </div>

            <div id="qr_codes_update" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/qr-codes/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{qr_code_id}'), ENT_QUOTES, 'UTF-8') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('project_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', array_keys((require APP_PATH . 'includes/enabled_qr_codes.php')))) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('style'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', array_keys(require APP_PATH . 'includes/qr_codes_styles.php'))) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('inner_eye_style'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['square', 'dot', 'rounded', 'diamond', 'flower', 'leaf',])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('outer_eye_style'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['square', 'circle', 'rounded', 'flower', 'leaf',])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['color', 'gradient'])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">foreground_type = color</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_gradient_style'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['vertical', 'horizontal', 'diagonal', 'inverse_diagonal', 'radial'])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_gradient_one'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">foreground_type = gradient</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('foreground_gradient_two'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">foreground_type = gradient</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('background_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('background_color_transparency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>0 - 100</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('custom_eyes_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('eyes_inner_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">custom_eyes_color = 1</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('eyes_outer_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">custom_eyes_color = 1</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_logo'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('api_documentation.file') ?></span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_logo_size'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span></td>
                                <td>5-35</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_background'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('api_documentation.file') ?></span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_background_transparency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>0-100</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_foreground'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('api_documentation.file') ?></span></td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('qr_code_foreground_transparency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>0-100</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', array_keys(require APP_PATH . 'includes/qr_codes_frames.php'))) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_text'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_text_size'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>-5 -> 5</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_text_font'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', array_keys(require APP_PATH . 'includes/qr_codes_frames_text_fonts.php'))) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_custom_colors'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">frame_custom_colors = 1</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('frame_text_color'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">frame_custom_colors = 1</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('size'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span></td>
                                <td>50-2000</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('margin'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span></td>
                                <td>0-25</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('ecc'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['L', 'M', 'Q', 'H'])) ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('text'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = text</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = url</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('phone'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = phone</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".sms_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('sms'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = sms</span>') ?></td>
                            </tr>
                            <tr class="collapse sms_collapse">
                                <td><?= htmlspecialchars(fcc_t('sms_body'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = sms</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".email_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('email'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = email</span>') ?></td>
                            </tr>
                            <tr class="collapse email_collapse">
                                <td><?= htmlspecialchars(fcc_t('email_subject'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = email</span>') ?></td>
                            </tr>
                            <tr class="collapse email_collapse">
                                <td><?= htmlspecialchars(fcc_t('email_body'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = email</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".whatsapp_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('whatsapp'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = whatsapp</span>') ?></td>
                            </tr>
                            <tr class="collapse whatsapp_collapse">
                                <td><?= htmlspecialchars(fcc_t('whatsapp_body'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = whatsapp</span>') ?></td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('facetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = facetime</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".location_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('location'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse location_collapse">
                                <td><?= htmlspecialchars(fcc_t('location_latitude'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span></td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = location</span>') ?></td>
                            </tr>
                            <tr class="collapse location_collapse">
                                <td><?= htmlspecialchars(fcc_t('location_longitude'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span></td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = location</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".wifi_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('wifi'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse wifi_collapse">
                                <td><?= htmlspecialchars(fcc_t('wifi_ssid'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = wifi</span>') ?></td>
                            </tr>
                            <tr class="collapse wifi_collapse">
                                <td><?= htmlspecialchars(fcc_t('wifi_encryption'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['nopass', 'WEP', 'WPA/WPA2', 'H'])) ?> <?= htmlspecialchars(fcc_t('(type=wifi)'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <tr class="collapse wifi_collapse">
                                <td><?= htmlspecialchars(fcc_t('wifi_password'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = wifi</span>') ?></td>
                            </tr>
                            <tr class="collapse wifi_collapse">
                                <td><?= htmlspecialchars(fcc_t('wifi_is_hidden'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span> <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-toggle-on mr-1"></i> <?= l('api_documentation.boolean') ?></span></td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = wifi</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".event_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('event'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_location'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_note'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_timezone'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_start_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_end_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_first_alert_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr class="collapse event_collapse">
                                <td><?= htmlspecialchars(fcc_t('event_second_alert_datetime'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = event</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".crypto_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('crypto'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse crypto_collapse">
                                <td><?= htmlspecialchars(fcc_t('crypto_coin'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = crypto</span>') ?></td>
                            </tr>
                            <tr class="collapse crypto_collapse">
                                <td><?= htmlspecialchars(fcc_t('crypto_address'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = crypto</span>') ?></td>
                            </tr>
                            <tr class="collapse crypto_collapse">
                                <td><?= htmlspecialchars(fcc_t('crypto_amount'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = crypto</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".vcard_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('vcard'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_first_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_last_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_company'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_phone_number_label[index]'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_phone_number_value[index]'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_email'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_job_title'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_birthday'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_street'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_city'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_zip'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_region'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_country'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_note'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_social_label[index]'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr class="collapse vcard_collapse">
                                <td><?= htmlspecialchars(fcc_t('vcard_social_value[index]'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = vcard</span>') ?></td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".paypal_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('paypal'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code>, <code>', ['buy_now', 'add_to_cart', 'donation'])) ?> <?= htmlspecialchars(fcc_t('(type=paypal)'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_email'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_title'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_currency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_price'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_thank_you_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>
                            <tr class="collapse paypal_collapse">
                                <td><?= htmlspecialchars(fcc_t('paypal_cancel_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = paypal</span>') ?></td>
                            </tr>

                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".upi_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('upi'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_payee_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_payee_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_currency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_amount'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_transaction_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_transaction_note'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_transaction_reference'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>
                            <tr class="collapse upi_collapse">
                                <td><?= htmlspecialchars(fcc_t('upi_thank_you_url'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = upi</span>') ?></td>
                            </tr>

                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".epc_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('epc'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_iban'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_payee_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_currency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_amount'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_bic'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_remittance_reference'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('epc_remittance_text'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>
                            <tr class="collapse epc_collapse">
                                <td><?= htmlspecialchars(fcc_t('information'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = epc</span>') ?></td>
                            </tr>

                            <tr>
                                <td>
                                    <a href="#" class="badge badge-light mr-1" data-toggle="collapse" data-target=".pix_collapse" data-tooltip title="<?= l('global.view') ?>" data-tooltip-hide-on-click>
                                        <i class="fas fa-fw fa-plus"></i>
                                    </a>
                                    <?= htmlspecialchars(fcc_t('pix'), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_payee_key'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_payee_name'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_currency'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_amount'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-percentage mr-1"></i> <?= l('api_documentation.float') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_city'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_transaction_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            <tr class="collapse pix_collapse">
                                <td><?= htmlspecialchars(fcc_t('pix_description'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.available_when'), '<span class="badge badge-light">type = pix</span>') ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request POST \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/qr-codes/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{qr_code_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'name='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Las Vegas'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
}
</pre>
                        </div>
                    </div>

                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#qr_codes_delete" aria-expanded="true" aria-controls="qr_codes_delete">
                        <?= l('api_documentation.delete') ?>
                    </a>
                </h3>
            </div>

            <div id="qr_codes_delete" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-danger mr-3"><?= htmlspecialchars(fcc_t('DELETE'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/qr-codes/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{qr_code_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request DELETE \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/qr-codes/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{qr_code_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

    </div>
</div>

<?php require THEME_PATH . 'views/partials/shiki_highlighter.php' ?>
