<?php defined('ALTUMCODE') || die() ?>

<h1 class="h4"><?= l('help.platforms_browsers_support.header') ?></h1>
<p><?= l('help.platforms_browsers_support.p1') ?></p>

<div class="table-responsive table-custom-container mt-4">
    <table class="table table-custom">
        <tbody>
        <tr>
            <td></td>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/os/windows.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Windows'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Windows'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/os/apple.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('MacOS'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('MacOS'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/os/chromeos.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('ChromeOS'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('ChromeOS'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/os/ubuntu.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Ubuntu'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Ubuntu'), ENT_QUOTES, 'UTF-8') ?>" />
                <img src="<?= ASSETS_FULL_URL . 'images/os/linux.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Linux'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Linux'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/os/android.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Android'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Android'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/os/ios.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('iOS & iPadOS'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('iOS & iPadOS'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
        </tr>

        <tr>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/browsers/chrome.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Chrome'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Chrome'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
        </tr>

        <tr>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/browsers/safari.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Safari'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Safari'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td><span><i class="fas fa-fw fa-sm fa-minus-circle text-muted"></i></span> <span class="text-muted"><?= l('global.none') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-minus-circle text-muted"></i></span> <span class="text-muted"><?= l('global.none') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-minus-circle text-muted"></i></span> <span class="text-muted"><?= l('global.none') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-minus-circle text-muted"></i></span> <span class="text-muted"><?= l('global.none') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
        </tr>

        <tr>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/browsers/edge.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Edge'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Edge'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-times-circle text-danger"></i></span> <span class="text-muted"><?= l('global.no') ?></span></td>
        </tr>

        <tr>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/browsers/firefox.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Firefox'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Firefox'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-times-circle text-danger"></i></span> <span class="text-muted"><?= l('global.no') ?></span></td>
        </tr>

        <tr>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/browsers/samsung.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Samsung Internet'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Samsung Internet'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td><span><i class="fas fa-fw fa-sm fa-minus-circle text-muted"></i></span> <span class="text-muted"><?= l('global.none') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-minus-circle text-muted"></i></span> <span class="text-muted"><?= l('global.none') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-minus-circle text-muted"></i></span> <span class="text-muted"><?= l('global.none') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-minus-circle text-muted"></i></span> <span class="text-muted"><?= l('global.none') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-times-circle text-danger"></i></span> <span class="text-muted"><?= l('global.no') ?></td>
        </tr>

        <tr>
            <td>
                <img src="<?= ASSETS_FULL_URL . 'images/browsers/opera.svg' ?>" class="icon-favicon" loading="lazy" data-toggle="tooltip" title="<?= htmlspecialchars(fcc_t('Opera'), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars(fcc_t('Opera'), ENT_QUOTES, 'UTF-8') ?>" />
            </td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-check-circle text-success"></i></span> <span class="text-muted"><?= l('global.yes') ?></span></td>
            <td><span><i class="fas fa-fw fa-sm fa-times-circle text-danger"></i></span> <span class="text-muted"><?= l('global.no') ?></td>
        </tr>
        </tbody>
    </table>
</div>

<div class="mt-4">
    <div class="alert alert-info" role="alert"><i class="fas fa-fw fa-sm fa-mobile mr-2"></i> <?= l('help.platforms_browsers_support.info1') ?></div>
    <div class="alert alert-info" role="alert"><i class="fas fa-fw fa-sm fa-times-circle mr-2"></i> <?= l('help.platforms_browsers_support.info2') ?></div>
</div>
