<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-03-14: public featured FCC apps page -->
<div class="container my-5">
    <div class="text-center mb-4">
        <h1 class="h3 mb-2"><?= l('featured_apps.header') ?></h1>
        <p class="text-muted mb-0"><?= l('featured_apps.subheader') ?></p>
    </div>

    <?php if(empty($data->featured_apps)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body py-4 text-center text-muted">
                <?= l('featured_apps.empty') ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach($data->featured_apps as $app): ?>
                <div class="col-12 col-md-6 col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?= get_user_avatar($app['avatar'], $app['email']) ?>" alt="<?= $app['name'] ?>" class="rounded-circle mr-3" style="width: 48px; height: 48px; object-fit: cover;" loading="lazy" />
                                <div>
                                    <div class="font-weight-bold text-truncate" style="max-width: 210px;"><?= $app['name'] ?></div>
                                    <div class="small text-muted"><?= l('featured_apps.card_label') ?></div>
                                </div>
                            </div>

                            <a href="<?= $app['app_url'] ?>" target="_blank" rel="nofollow noopener" class="btn btn-primary mt-auto">
                                <?= l('featured_apps.view_app') ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<!-- /Custom code: FC-2026-03-14 -->
