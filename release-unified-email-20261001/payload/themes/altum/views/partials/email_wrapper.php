<?php defined('ALTUMCODE') || die();
require_once APP_PATH . 'helpers/fcc_email_design.php';
$email_copy = fc_email_design_copy($data->language);
$email_locale = \Altum\Language::$active_languages[$data->language] ?? 'en';
$email_direction = l('direction', $data->language) === 'rtl' ? 'rtl' : 'ltr';
$email_title = trim((string) ($data->title ?? '')) ?: settings()->main->title;
$email_preheader = mb_substr(preg_replace('/\s+/u', ' ', fc_email_plain_text($data->content)), 0, 160);
$email_site_url = fc_get_site_url();
$email_logo = !empty(settings()->main->logo_email) ? \Altum\Uploads::get_full_url('logo_email') . settings()->main->logo_email : '';
$email_link_style = 'color:#106653;text-decoration:underline;font-weight:600;';
?>
<!doctype html>
<html lang="<?= e($email_locale) ?>" dir="<?= $email_direction ?>" xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title><?= e($email_title) ?></title>
    <!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml><![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
        table, td { mso-table-lspace:0pt; mso-table-rspace:0pt; }
        img { border:0; -ms-interpolation-mode:bicubic; }
        a[x-apple-data-detectors] { color:inherit !important; font:inherit !important; }
        .fcc-email-content { overflow-wrap:anywhere; word-wrap:break-word; }
        .fcc-email-content img { max-width:100% !important; height:auto !important; }
        .fcc-email-content table { max-width:100% !important; }
        @media screen and (max-width:640px) {
            .fcc-email-gutter { padding:16px 10px !important; }
            .fcc-email-brand { padding:24px !important; }
            .fcc-email-hero { padding:26px 24px !important; }
            .fcc-email-title { font-size:26px !important; line-height:1.25 !important; }
            .fcc-email-content { padding:28px 24px !important; }
            .fcc-email-meta { padding:0 24px 26px !important; }
            .fcc-email-footer { padding:24px 14px !important; }
            .fcc-email-button { white-space:normal !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;width:100%;background-color:#edf3ef;color:#143d35;font-family:Arial,Helvetica,sans-serif;">
<div class="fcc-email-preheader" style="display:none;font-size:1px;line-height:1px;color:#edf3ef;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;"><?= e($email_preheader) ?></div>
<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#edf3ef" style="width:100%;table-layout:fixed;background-color:#edf3ef;">
    <tr>
        <td align="center" class="fcc-email-gutter" style="padding:36px 16px;direction:<?= $email_direction ?>;">
            <!--[if mso]><table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" class="fcc-email-shell" style="width:100%;max-width:640px;table-layout:fixed;margin:0 auto;">
                <tr><td>
                    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="width:100%;table-layout:fixed;background-color:#ffffff;border:1px solid #dbe6df;border-radius:22px;border-collapse:separate;">
                        <tr><td class="fcc-email-brand" style="padding:28px 36px;">
                            <a href="<?= e($email_site_url) ?>" style="color:#143d35;font-size:23px;font-weight:700;text-decoration:none;">
                                <?php if($email_logo): ?>
                                    <img src="<?= e($email_logo) ?>" width="190" alt="<?= e(settings()->main->title) ?>" style="display:block;width:190px;max-width:100%;height:auto;border:0;">
                                <?php else: ?><?= e(settings()->main->title) ?><?php endif ?>
                            </a>
                            <p style="margin:12px 0 0;color:#526b64;font-size:12px;line-height:1.5;"><?= e($email_copy['tagline']) ?></p>
                        </td></tr>
                        <tr><td class="fcc-email-hero" bgcolor="#143d35" style="padding:30px 36px;background-color:#143d35;">
                            <p style="margin:0 0 12px;color:#bed9ca;font-size:10px;font-weight:700;line-height:1.5;letter-spacing:2px;">FOREVER CARD CLUB</p>
                            <h1 class="fcc-email-title" style="margin:0;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:30px;font-weight:700;line-height:1.25;overflow-wrap:anywhere;"><?= e($email_title) ?></h1>
                        </td></tr>
                        <tr><td class="fcc-email-content" style="padding:34px 36px 30px;color:#143d35;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.7;text-align:<?= $email_direction === 'rtl' ? 'right' : 'left' ?>;overflow-wrap:anywhere;word-wrap:break-word;">
                            <?= $data->content ?>
                        </td></tr>
                        <?php if(!empty($data->unsubscribe_url) && !$data->is_system_email): ?>
                        <tr><td class="fcc-email-meta" style="padding:0 36px 26px;">
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td style="padding-top:20px;border-top:1px solid #dbe6df;color:#526b64;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.7;">
                                <?= e($email_copy['unsubscribe_text']) ?><br>
                                <a href="<?= e($data->unsubscribe_url) ?>" style="<?= $email_link_style ?>"><?= e($email_copy['unsubscribe_link']) ?></a>
                            </td></tr></table>
                        </td></tr>
                        <?php endif ?>
                        <?php if(!empty($data->anti_phishing_code)): ?>
                        <tr><td class="fcc-email-meta" style="padding:0 36px 26px;">
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td bgcolor="#edf3ef" style="padding:14px 18px;border-radius:10px;background-color:#edf3ef;color:#526b64;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.6;">
                                <?= sprintf(l('global.emails.anti_phishing_code', $data->language), '<strong style="color:#143d35;">' . e($data->anti_phishing_code) . '</strong>') ?>
                            </td></tr></table>
                        </td></tr>
                        <?php endif ?>
                    </table>
                </td></tr>
                <tr><td align="center" class="fcc-email-footer" style="padding:26px 28px;color:#526b64;font-family:Arial,Helvetica,sans-serif;font-size:12px;line-height:1.7;text-align:center;">
                    <p style="margin:0 0 12px;"><?= e($email_copy['help']) ?> <a href="mailto:info@forevercard.club" style="<?= $email_link_style ?>"><?= e($email_copy['contact']) ?></a></p>
                    <p style="margin:0 0 8px;">&copy; <?= date('Y') ?> <?= e(settings()->main->title) ?><br>
                        <a href="<?= e($email_site_url) ?>" style="<?= $email_link_style ?>"><?= e(rtrim(remove_url_protocol_from_url($email_site_url), '/')) ?></a>
                        &nbsp;&middot;&nbsp; <a href="<?= e(fcc_legal_url('privacy')) ?>" style="<?= $email_link_style ?>"><?= e($email_copy['privacy']) ?></a>
                    </p>
                    <?php if(!empty(settings()->smtp->company_details)): ?>
                        <p style="margin:12px 0 0;font-size:11px;line-height:1.6;"><?= nl2br(e(settings()->smtp->company_details)) ?></p>
                    <?php endif ?>
                    <?php if(!empty(settings()->smtp->display_socials)): ?>
                        <p style="margin:16px 0 0;">
                        <?php foreach(require APP_PATH . 'includes/admin_socials.php' as $key => $social): ?>
                            <?php if(!empty(settings()->socials->{$key})): ?>
                                <a href="<?= e(sprintf($social['format'], settings()->socials->{$key})) ?>" style="display:inline-block;margin:0 6px;text-decoration:none;"><img src="<?= e(ASSETS_FULL_URL . 'images/email/' . $key . '.png') ?>" width="20" height="20" alt="<?= e($social['name']) ?>" style="border:0;width:20px;height:20px;"></a>
                            <?php endif ?>
                        <?php endforeach ?>
                        </p>
                    <?php endif ?>
                </td></tr>
            </table>
            <!--[if mso]></td></tr></table><![endif]-->
        </td>
    </tr>
</table>
</body>
</html>
