<?php defined('ALTUMCODE') || die() ?>

<p class="text-muted"><?= l('admin_settings.health.help') ?></p>

<div class="table-responsive table-custom-container">
    <table class="table table-custom">
        <thead>
        <tr>
            <th><?= htmlspecialchars(fcc_t('Requirement'), ENT_QUOTES, 'UTF-8') ?></th>
            <th><?= htmlspecialchars(fcc_t('Status'), ENT_QUOTES, 'UTF-8') ?></th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('PHP Version'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted">8.3 - 8.5</div>
            </td>
            <td><?= PHP_VERSION ?></td>
            <td class="text-right">
                <?php if(version_compare(PHP_VERSION, '8.3.0', '>=') && version_compare(PHP_VERSION, '8.6', '<')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('OpenSSL'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Extension'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= extension_loaded('openssl') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(extension_loaded('openssl')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('mbstring'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Extension'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= extension_loaded('mbstring') && function_exists('mb_get_info') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(extension_loaded('mbstring') && function_exists('mb_get_info')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('GD'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Extension'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= extension_loaded('gd') && function_exists('gd_info') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(extension_loaded('gd') && function_exists('gd_info')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('MySQLi'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Extension'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= function_exists('mysqli_connect') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(function_exists('mysqli_connect')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('cURL'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Extension'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= function_exists('curl_version') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(function_exists('curl_version')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('INTL'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Extension'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= extension_loaded('intl') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(extension_loaded('intl')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('set_time_limit()'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Function'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= function_exists('set_time_limit') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(function_exists('set_time_limit')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('iconv()'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Function'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= function_exists('iconv') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(function_exists('iconv')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('get_headers()'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Function'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= function_exists('get_headers') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(function_exists('get_headers')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('mime_content_type()'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('Function'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= function_exists('mime_content_type') ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(function_exists('mime_content_type')): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>

        <tr>
            <td>
                <?= htmlspecialchars(fcc_t('allow_url_fopen'), ENT_QUOTES, 'UTF-8') ?>
                <div class="small text-muted"><?= htmlspecialchars(fcc_t('INI Configuration'), ENT_QUOTES, 'UTF-8') ?></div>
            </td>
            <td><?= filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN) ? fcc_t('Enabled') : fcc_t('Not Enabled') ?></td>
            <td class="text-right">
                <?php if(filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)): ?>
                    ✅
                <?php else: ?>
                    ❌
                <?php endif ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>
