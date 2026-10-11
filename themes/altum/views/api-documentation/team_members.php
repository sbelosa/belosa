<?php defined('ALTUMCODE') || die() ?>

<?php $teams_access = require APP_PATH . 'includes/teams_access.php'; ?>

<div class="container">
<?php if(settings()->main->breadcrumbs_is_enabled): ?>
    <nav aria-label="<?= htmlspecialchars(fcc_t('breadcrumb'), ENT_QUOTES, 'UTF-8') ?>">
        <ol class="custom-breadcrumbs small">
            <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
            <li><a href="<?= url('api-documentation') ?>"><?= l('api_documentation.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
            <li class="active" aria-current="page"><?= l('api_documentation.team_members') ?></li>
        </ol>
    </nav>
<?php endif ?>

    <h1 class="h4 mb-4"><?= l('api_documentation.team_members') ?></h1>

    <div class="accordion">
        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#teams_read" aria-expanded="true" aria-controls="teams_read">
                        <?= l('api_documentation.read') ?>
                    </a>
                </h3>
            </div>

            <div id="teams_read" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-success mr-3"><?= htmlspecialchars(fcc_t('GET'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/team-members/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{team_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request GET \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/team-members/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{team_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.response') ?></label>
                        <pre data-shiki="json">
{
    "data": [
        {
            "id": 1,
            "team_id": 1,
            "user_email": "hello@example.com",
            "access": {
                "read": true,
                "create": true,
                "update": true,
                "delete": false
            },
            "status": 1,
            "datetime": "<?= get_date() ?>",
            "last_datetime": null
        }
    ]
}
</pre>
                        </div>
                    </div>
                </div>
            </div>


        <div class="card">
            <div class="card-header bg-white p-3 position-relative">
                <h3 class="h6 m-0">
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#teams_create" aria-expanded="true" aria-controls="teams_create">
                        <?= l('api_documentation.create') ?>
                    </a>
                </h3>
            </div>

            <div id="teams_create" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/teams', ENT_QUOTES, 'UTF-8') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('team_id'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-hashtag mr-1"></i> <?= l('api_documentation.int') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('user_email'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-danger"><i class="fas fa-fw fa-sm fa-asterisk mr-1"></i> <?= l('api_documentation.required') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                </td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><?= htmlspecialchars(fcc_t('access'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-list mr-1"></i> <?= l('api_documentation.array') ?></span>
                                </td>
                                <td>
                                    <?php foreach($teams_access as $key => $value): ?>
                                        <?= '<code>' . implode('</code> , <code>',  array_keys($teams_access[$key])) . '</code>' . '<br />' ?>
                                    <?php endforeach ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request POST \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars(fcc_t('api/team-members\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'team_id='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary">1</span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'user_email='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('hello@example.com'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#teams_update" aria-expanded="true" aria-controls="teams_update">
                        <?= l('api_documentation.update') ?>
                    </a>
                </h3>
            </div>

            <div id="teams_update" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-info mr-3"><?= htmlspecialchars(fcc_t('POST'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/team-members/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{team_member_id}'), ENT_QUOTES, 'UTF-8') ?></span>
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
                                <td><?= htmlspecialchars(fcc_t('access'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="badge badge-info"><i class="fas fa-fw fa-sm fa-circle-notch mr-1"></i> <?= l('api_documentation.optional') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-signature mr-1"></i> <?= l('api_documentation.string') ?></span>
                                    <span class="badge badge-secondary"><i class="fas fa-fw fa-sm fa-list mr-1"></i> <?= l('api_documentation.array') ?></span>
                                </td>
                                <td>
                                    <?php foreach($teams_access as $key => $value): ?>
                                        <?= '<code>' . implode('</code> , <code>',  array_keys($teams_access[$key])) . '</code>' . '<br />' ?>
                                    <?php endforeach ?>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request POST \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/team-members/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{team_member_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Content-Type: multipart/form-data\' \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--form \'access[]='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('create'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--form \'access[]='), ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('update'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
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
                    <a href="#" class="stretched-link" data-toggle="collapse" data-target="#teams_delete" aria-expanded="true" aria-controls="teams_delete">
                        <?= l('api_documentation.delete') ?>
                    </a>
                </h3>
            </div>

            <div id="teams_delete" class="collapse">
                <div class="card-body">

                    <div class="form-group mb-4">
                        <label><?= l('api_documentation.endpoint') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <span class="badge badge-danger mr-3"><?= htmlspecialchars(fcc_t('DELETE'), ENT_QUOTES, 'UTF-8') ?></span> <span class="text-muted"><?= SITE_URL ?><?= htmlspecialchars('api/team-members/', ENT_QUOTES, 'UTF-8') ?></span><span class="text-primary"><?= htmlspecialchars(fcc_t('{team_member_id}'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= l('api_documentation.example') ?></label>
                        <div class="card bg-gray-100 border-0">
                            <div class="card-body">
                                <?= htmlspecialchars(fcc_t('curl --request DELETE \\'), ENT_QUOTES, 'UTF-8') ?><br />
                                <?= htmlspecialchars(fcc_t('--url \''), ENT_QUOTES, 'UTF-8') ?><?= SITE_URL ?><?= htmlspecialchars('api/team-members/', ENT_QUOTES, 'UTF-8') ?><span class="text-primary"><?= htmlspecialchars(fcc_t('{team_member_id}'), ENT_QUOTES, 'UTF-8') ?></span>' \<br />
                                <?= htmlspecialchars(fcc_t('--header \'Authorization: Bearer'), ENT_QUOTES, 'UTF-8') ?> <span class="text-primary" <?= is_logged_in() ? 'data-toggle="tooltip" title="' . l('api_documentation.api_key') . '"' : null ?>><?= is_logged_in() ? $this->user->api_key : '{api_key}' ?></span>' \<br />
                            </div>
                        </div>
                    </div>

                </div>
            </div>

    </div>
</div>

<?php require THEME_PATH . 'views/partials/shiki_highlighter.php' ?>
