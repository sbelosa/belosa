<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="<?= htmlspecialchars(fcc_t('breadcrumb'), ENT_QUOTES, 'UTF-8') ?>">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li><a href="<?= url('api-documentation') ?>"><?= l('api_documentation.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('pixels.title') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <h1 class="h4 mb-4"><?= l('pixels.title') ?></h1>

    <div class="accordion">
        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#pixels_read_all" aria-expanded="true" aria-controls="pixels_read_all">
                        <?= l('api_documentation.read_all') ?>
                    </a>
                </h3>
            </div>

            <div id="pixels_read_all" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-success mr-3"><?= htmlspecialchars(fcc_t('GET'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/pixels/', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request GET \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars(fcc_t('api/pixels/\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
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
            "type": "facebook",
            "name": "Fb Ads",
            "pixel": "1234567",
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
        "first": "<?= SITE_URL ?>api/pixels?page=1",
        "last": "<?= SITE_URL ?>api/pixels?page=1",
        "next": null,
        "prev": null,
        "self": "<?= SITE_URL ?>api/pixels?page=1"
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#pixels_read" aria-expanded="true" aria-controls="pixels_read">
                        <?= l('api_documentation.read') ?>
                    </a>
                </h3>
            </div>

            <div id="pixels_read" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-success mr-3"><?= htmlspecialchars(fcc_t('GET'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/pixels/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{pixel_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request GET \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/pixels/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{pixel_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
        "type": "facebook",
        "name": "Fb Ads",
        "pixel": "1234567",
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#pixels_create" aria-expanded="true" aria-controls="pixels_create">
                        <?= l('api_documentation.create') ?>
                    </a>
                </h3>
            </div>

            <div id="pixels_create" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/pixels', ENT_QUOTES, 'UTF-8') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code> , <code>',  array_keys(require APP_PATH . 'includes/pixels.php')) . '</code>') ?></td>
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
                                <td><?= htmlspecialchars(fcc_t('pixel'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
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
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars(fcc_t('api/pixels\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'type='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('facebook'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'name='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Facebook'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'pixel='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary">12345678</span>' \<br />
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#pixels_update" aria-expanded="true" aria-controls="pixels_update">
                        <?= l('api_documentation.update') ?>
                    </a>
                </h3>
            </div>

            <div id="pixels_update" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/pixels/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{pixel_id}'), ENT_QUOTES, 'UTF-8') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('type'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td><?= sprintf(l('api_documentation.allowed_values'), '<code>' . implode('</code> , <code>',  array_keys(require APP_PATH . 'includes/pixels.php')) . '</code>') ?></td>
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
                                <td><?= htmlspecialchars(fcc_t('pixel'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
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
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/pixels/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{pixel_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'name='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('Facebook New Name'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#pixels_delete" aria-expanded="true" aria-controls="pixels_delete">
                        <?= l('api_documentation.delete') ?>
                    </a>
                </h3>
            </div>

            <div id="pixels_delete" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-danger mr-3"><?= htmlspecialchars(fcc_t('DELETE'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/pixels/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{pixel_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request DELETE \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/pixels/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{pixel_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

    </div>
</div>

<?php require THEME_PATH . 'views/partials/shiki_highlighter.php' ?>
