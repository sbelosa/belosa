<!-- Custom code -->

<?php defined('ALTUMCODE') || die() ?>

<?php if (isset($data->qr_code)) {
        ob_clean();        
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($data->qr_code) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($data->qr_code));
        flush();
        readfile($data->qr_code);        
        die();
} ?>

<div class="d-flex flex-column flex-md-row justify-content-between mb-4">
    <h1 class="h3 mb-3 mb-md-0"><i class="fa fa-fw fa-xs fa-qrcode text-primary-900 mr-2"></i> <?= l('admin_qr_codes.header') ?></h1>       
</div>

<?= \Altum\Alerts::output_alerts() ?>

<!-- /Custom code -->
