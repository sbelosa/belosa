<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="breadcrumb">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url() ?>"><?= l('index.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('help.breadcrumb') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="row">
        <div class="col-12 col-lg-4 mb-4 mb-lg-0">
            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a href="<?= url('help') ?>" class="nav-link <?= $data->page == 'introduction' ? 'active' : null ?>">
                                <i class="fas fa-fw fa-sm fa-file mr-1"></i> <?= l('help.introduction.menu') ?>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= url('help/platforms-browsers-support') ?>" class="nav-link <?= $data->page == 'platforms_browsers_support' ? 'active' : null ?>">
                                <i class="fas fa-fw fa-sm fa-desktop mr-1"></i> <?= l('help.platforms_browsers_support.menu') ?>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= url('help/custom-parameters') ?>" class="nav-link <?= $data->page == 'custom_parameters' ? 'active' : null ?>">
                                <i class="fas fa-fw fa-sm fa-fingerprint mr-1"></i> <?= l('help.custom_parameters.menu') ?>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= url('help/custom-button') ?>" class="nav-link <?= $data->page == 'custom_button' ? 'active' : null ?>">
                                <i class="fas fa-fw fa-sm fa-code mr-1"></i> <?= l('help.custom_button.menu') ?>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="<?= url('help/faq') ?>" class="nav-link <?= $data->page == 'faq' ? 'active' : null ?>">
                                <i class="fas fa-fw fa-sm fa-question-circle mr-1"></i> <?= l('help.faq.menu') ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col col-lg-8">
            <div class="card">
                <div class="card-body">
                    <?= $this->views['page'] ?>
                </div>
            </div>
        </div>
    </div>
</div>
