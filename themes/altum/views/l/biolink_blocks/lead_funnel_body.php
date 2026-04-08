<?php defined('ALTUMCODE') || die() ?>

<?php /* Custom code: FC-2026-03-23: shared lead funnel body for popup and page */ ?>

<?php ob_start() ?>
<style>
    @font-face {font-family: 'Scriptorama'; src: url('<?= ASSETS_FULL_URL . 'css/fonts/scriptorama_tradeshow_jf_regular-webfont.woff2' ?>') format('woff2'), url('<?= ASSETS_FULL_URL . 'css/fonts/scriptorama_tradeshow_jf_regular-webfont.woff' ?>') format('woff'); font-weight: normal; font-style: normal;}
    @font-face {font-family: 'Helvetica Neue Medium'; src: url('<?= ASSETS_FULL_URL . 'css/fonts/helveticaneuemedium-webfont.woff2' ?>') format('woff2'), url('<?= ASSETS_FULL_URL . 'css/fonts/helveticaneuemedium-webfont.woff' ?>') format('woff'); font-weight: normal; font-style: normal;}
    @font-face {font-family: 'Helvetica Neue LT'; src: url('<?= ASSETS_FULL_URL . 'css/fonts/helveticaneuelt.woff2' ?>') format('woff2'), url('<?= ASSETS_FULL_URL . 'css/fonts/helveticaneuelt.woff' ?>') format('woff'); font-weight: normal; font-style: normal;}
    [data-lead-funnel-container] {background: var(--lead-funnel-background-color, #ffffff); color: var(--lead-funnel-text-color, #212529); position: relative; overflow: hidden;}
    [data-lead-funnel-container]::before {content: ''; position: absolute; inset: 0; background:
        radial-gradient(circle at top right, rgba(255,255,255,.18), transparent 32%),
        radial-gradient(circle at bottom left, rgba(255,255,255,.08), transparent 28%);
        pointer-events: none;}
    [data-lead-funnel-container] .modal-header {position: relative; z-index: 1; padding: 1.2rem 1.35rem .4rem; border-bottom: 0; background: transparent;}
    [data-lead-funnel-container] .modal-title {font-size: 1.45rem; line-height: 1.15; font-weight: 900; letter-spacing: -.02em; max-width: calc(100% - 3.5rem);}
    [data-lead-funnel-container] .modal-body {position: relative; z-index: 1; padding: .45rem 1.35rem 1.35rem;}
    [data-lead-funnel-container] .close {display: inline-flex; align-items: center; justify-content: center; width: 2.25rem; height: 2.25rem; border-radius: 999px; color: var(--lead-funnel-text-color, #212529); text-shadow: none; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.18); opacity: 1; transition: transform .2s ease, background-color .2s ease;}
    [data-lead-funnel-container] .close:hover {background: rgba(255,255,255,.26); transform: scale(1.04);}
    .lead-funnel-stack {display: grid; gap: 1rem;}
    .lead-funnel-intro-card,
    .lead-funnel-video-card,
    .lead-funnel-form-card,
    .lead-funnel-thank-you-card {position: relative; overflow: hidden; border-radius: 1.45rem; border: 1px solid rgba(255,255,255,.18); background: linear-gradient(180deg, rgba(255,255,255,.16), rgba(255,255,255,.08)); box-shadow: 0 1.25rem 3rem rgba(15, 23, 42, 0.16), inset 0 1px 0 rgba(255,255,255,.12);}
    .lead-funnel-intro-card::after,
    .lead-funnel-form-card::after,
    .lead-funnel-thank-you-card::after {content: ''; position: absolute; right: -3rem; top: -3rem; width: 9rem; height: 9rem; border-radius: 999px; background: radial-gradient(circle, rgba(255,255,255,.24), transparent 68%); pointer-events: none;}
    .lead-funnel-intro-card {padding: 1rem 1.1rem;}
    .lead-funnel-video-card {padding: .7rem;}
    .lead-funnel-form-card,
    .lead-funnel-thank-you-card {padding: 1rem;}
    .lead-funnel-section-label {display: inline-flex; align-items: center; gap: .45rem; margin-bottom: .65rem; padding: .4rem .72rem; border-radius: 999px; border: 1px solid rgba(255,255,255,.18); background: rgba(15,23,42,.12); color: var(--lead-funnel-text-color, #212529); font-size: .72rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase;}
    .lead-funnel-section-label i {font-size: .78rem; opacity: .92;}
    .lead-funnel-video-wrapper {position: relative; width: 100%; padding-top: 56.25%; overflow: hidden; border-radius: 1.15rem; background: #000; box-shadow: 0 1rem 2.5rem rgba(15, 23, 42, 0.16);}
    .lead-funnel-video-wrapper iframe {position: absolute; inset: 0; width: 100%; height: 100%; border: 0;}
    .lead-funnel-video-caption {margin-top: .8rem; color: var(--lead-funnel-text-color, #212529); opacity: .78; font-size: .93rem; line-height: 1.55;}
    .lead-funnel-supporting-text {color: var(--lead-funnel-text-color, #212529); opacity: .84; font-size: .98rem; line-height: 1.7;}
    .lead-funnel-submit-button,
    .lead-funnel-thank-you-button {background: var(--lead-funnel-button-background-color, #007bff); border-color: transparent; color: var(--lead-funnel-button-text-color, #ffffff); border-radius: 1rem; min-height: 3.8rem; font-size: 1.02rem; font-weight: 900; letter-spacing: .01em; box-shadow: 0 1rem 2.2rem rgba(0,0,0,.16);}
    .lead-funnel-submit-button:hover,
    .lead-funnel-submit-button:focus,
    .lead-funnel-thank-you-button:hover,
    .lead-funnel-thank-you-button:focus {background: var(--lead-funnel-button-background-color, #007bff); border-color: var(--lead-funnel-button-background-color, #007bff); color: var(--lead-funnel-button-text-color, #ffffff); filter: brightness(0.95);}
    .lead-funnel-submit-button {margin-top: .2rem;}
    .lead-funnel-submit-helper {margin-top: .85rem; color: var(--lead-funnel-text-color, #212529); opacity: .64; font-size: .85rem; text-align: center;}
    .lead-funnel-thank-you {display: none; text-align: center; padding: 0;}
    .lead-funnel-thank-you.is-visible {display: block;}
    .lead-funnel-page-card {background: var(--lead-funnel-background-color, #ffffff); color: var(--lead-funnel-text-color, #212529); border-radius: 1.85rem; border: 1px solid rgba(255,255,255,.16); box-shadow: 0 1.75rem 4rem rgba(0,0,0,0.14); overflow: hidden; backdrop-filter: blur(12px); position: relative;}
    .lead-funnel-page-header {padding: 1.35rem 1.35rem 0;}
    .lead-funnel-page-body {padding: 1.15rem 1.35rem 1.35rem;}
    .lead-funnel-page-back-link {color: var(--lead-funnel-text-color, #212529); opacity: .75;}
    .lead-funnel-page-back-link:hover {color: var(--lead-funnel-text-color, #212529); opacity: 1;}
    .lead-funnel-page-card h1,
    .lead-funnel-page-card h2,
    .lead-funnel-page-card h3,
    .lead-funnel-page-card h4,
    .lead-funnel-page-card h5,
    .lead-funnel-page-card h6,
    .lead-funnel-page-card label {color: var(--lead-funnel-text-color, #212529);}
    [data-lead-funnel-container] .form-group {margin-bottom: .9rem;}
    [data-lead-funnel-container] .input-group {align-items: stretch;}
    [data-lead-funnel-container] .input-group-text,
    [data-lead-funnel-container] .form-control,
    [data-lead-funnel-container] .custom-select {min-height: 3.35rem;}
    [data-lead-funnel-container] .form-control,
    [data-lead-funnel-container] .custom-select {border: 0; border-radius: 1rem; background: rgba(255,255,255,.97); color: var(--lead-funnel-text-color, #212529); box-shadow: 0 .5rem 1.4rem rgba(15, 23, 42, .08);}
    [data-lead-funnel-container] .form-control::placeholder {color: rgba(15, 23, 42, .52);}
    [data-lead-funnel-container] .input-group-text {width: 3.15rem; justify-content: center; border: 0; border-radius: 1rem 0 0 1rem; background: rgba(255,255,255,.92); color: #c9ad58;}
    [data-lead-funnel-container] .input-group .form-control {border-radius: 0 1rem 1rem 0;}
    [data-lead-funnel-container] textarea.form-control {min-height: 7rem; padding-top: .95rem; padding-bottom: .95rem;}
    .fcc-contact-capture {margin-bottom: 1rem; padding: .8rem; border-radius: 1.3rem; background: linear-gradient(180deg, rgba(255,255,255,.16), rgba(255,255,255,.08)); border: 1px solid rgba(255,255,255,.18); backdrop-filter: blur(10px); box-shadow: inset 0 1px 0 rgba(255,255,255,.1);}
    .fcc-contact-capture .custom-select,
    .fcc-contact-capture .form-control {border-radius: 1rem; min-height: 3.2rem; box-shadow: none;}
    .fcc-contact-capture .custom-select {padding-left: 1rem; padding-right: 2.4rem; font-weight: 700; font-size: .9rem;}
    .fcc-contact-capture .input-group-text {border-radius: 1rem 0 0 1rem;}
    .fcc-contact-capture .input-group .form-control {border-radius: 0 1rem 1rem 0;}
    .fcc-contact-capture-row + .fcc-contact-capture-row {margin-top: .7rem;}
    .fcc-contact-capture-channels {display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .45rem;}
    .fcc-contact-capture-radio {position: absolute; opacity: 0; pointer-events: none;}
    .fcc-contact-capture-chip {display: inline-flex; align-items: center; justify-content: center; width: 100%; min-width: 0; min-height: 2.9rem; padding: .45rem .3rem; border-radius: 999px; border: 1px solid rgba(255,255,255,.26); background: rgba(255,255,255,.08); color: var(--lead-funnel-text-color, #212529); font-size: .84rem; font-weight: 800; cursor: pointer; transition: all .2s ease; white-space: nowrap;}
    .fcc-contact-capture-radio:checked + .fcc-contact-capture-chip {background: var(--lead-funnel-button-background-color, #007bff); border-color: var(--lead-funnel-button-background-color, #007bff); color: var(--lead-funnel-button-text-color, #ffffff); box-shadow: 0 .5rem 1.2rem rgba(0,0,0,.16);}
    .fcc-contact-capture-radio:focus + .fcc-contact-capture-chip {outline: 0; box-shadow: 0 0 0 .2rem rgba(0,123,255,.18);}
    [data-lead-funnel-container] .custom-control-label {line-height: 1.55;}
    [data-lead-funnel-container] a {color: inherit;}
    @media (max-width: 575.98px) {
        [data-lead-funnel-container] .modal-header {padding: 1rem 1rem .35rem;}
        [data-lead-funnel-container] .modal-title {font-size: 1.28rem;}
        [data-lead-funnel-container] .modal-body {padding: .35rem 1rem 1rem;}
        .lead-funnel-page-header {padding: 1.15rem 1.1rem 0;}
        .lead-funnel-page-body {padding: 1rem 1.1rem 1.15rem;}
        .lead-funnel-page-card h1 {font-size: 2rem;}
        .lead-funnel-supporting-text {font-size: .95rem;}
        .lead-funnel-intro-card,
        .lead-funnel-form-card,
        .lead-funnel-thank-you-card {padding: .95rem;}
        .lead-funnel-video-card {padding: .55rem;}
    }
    [data-lead-funnel-container] .ql-content p:last-child,
    [data-lead-funnel-container] .ql-content ul:last-child,
    [data-lead-funnel-container] .ql-content ol:last-child {margin-bottom: 0;}
    [data-lead-funnel-container] .ql-content li[data-list="bullet"] {list-style-type: disc;}
    [data-lead-funnel-container] .ql-content .ql-font-segoe-ui {font-family: 'Segoe UI', sans-serif !important;}
    [data-lead-funnel-container] .ql-content .ql-font-roboto {font-family: 'Roboto', sans-serif !important;}
    [data-lead-funnel-container] .ql-content .ql-font-scriptorama {font-family: 'Scriptorama', sans-serif !important;}
    [data-lead-funnel-container] .ql-content .ql-font-helvetica-neue-medium {font-family: 'Helvetica Neue Medium', sans-serif !important;}
    [data-lead-funnel-container] .ql-content .ql-font-helvetica-neue-lt {font-family: 'Helvetica Neue LT', sans-serif !important;}
    [data-lead-funnel-container] .ql-content .ql-size-small {font-size: .75em !important;}
    [data-lead-funnel-container] .ql-content .ql-size-large {font-size: 1.5em !important;}
    [data-lead-funnel-container] .ql-content .ql-size-huge {font-size: 2.5em !important;}
    [data-lead-funnel-container] .ql-content .ql-align-center {text-align: center !important;}
    [data-lead-funnel-container] .ql-content .ql-align-right {text-align: right !important;}
    [data-lead-funnel-container] .ql-content .ql-align-justify {text-align: justify !important;}
    [data-lead-funnel-container] .ql-content .ql-color-white,
    [data-lead-funnel-container] .ql-content [style*="color: rgb(255, 255, 255)"],
    [data-lead-funnel-container] .ql-content [style*="color:rgb(255,255,255)"],
    [data-lead-funnel-container] .ql-content [style*="color: #ffffff"],
    [data-lead-funnel-container] .ql-content [style*="color:#ffffff"],
    [data-lead-funnel-container] .ql-content [style*="color: #fff"],
    [data-lead-funnel-container] .ql-content [style*="color:#fff"],
    [data-lead-funnel-container] .ql-content [style*="color: white"],
    [data-lead-funnel-container] .ql-content [style*="color:white"] {color: var(--lead-funnel-text-color, #212529) !important;}
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php
/* Custom code: FC-2026-03-23: lead funnel formatted content rendering */
$lead_funnel_render_rich_text = function($content) {
    $content = (string) $content;

    $class_style_map = [
        'ql-align-center' => 'text-align:center',
        'ql-align-right' => 'text-align:right',
        'ql-align-justify' => 'text-align:justify',
        'text-center' => 'text-align:center',
        'text-right' => 'text-align:right',
        'text-justify' => 'text-align:justify',
        'ql-size-small' => 'font-size:0.75em',
        'ql-size-large' => 'font-size:1.5em',
        'ql-size-huge' => 'font-size:2.5em',
        'small' => 'font-size:0.75em',
        'h4' => 'font-size:1.5em',
        'h3' => 'font-size:2.5em',
        'ql-font-segoe-ui' => 'font-family:\'Segoe UI\',sans-serif',
        'ql-font-roboto' => 'font-family:\'Roboto\',sans-serif',
        'ql-font-scriptorama' => 'font-family:\'Scriptorama\',sans-serif',
        'ql-font-helvetica-neue-medium' => 'font-family:\'Helvetica Neue Medium\',sans-serif',
        'ql-font-helvetica-neue-lt' => 'font-family:\'Helvetica Neue LT\',sans-serif',
    ];

    return preg_replace_callback('/<([a-z0-9]+)([^>]*)class="([^"]*)"([^>]*)>/i', function($matches) use ($class_style_map) {
        $tag = $matches[1];
        $before = $matches[2];
        $class_attribute = $matches[3];
        $after = $matches[4];

        $styles = [];

        foreach($class_style_map as $class_name => $style_rule) {
            if(strpos(' ' . $class_attribute . ' ', ' ' . $class_name . ' ') !== false) {
                $styles[] = $style_rule;
            }
        }

        if(empty($styles)) {
            return $matches[0];
        }

        $attributes = $before . 'class="' . $class_attribute . '"' . $after;

        if(preg_match('/style="([^"]*)"/i', $attributes, $style_matches)) {
            $merged_styles = rtrim(trim($style_matches[1]), ';');
            if($merged_styles !== '') {
                $merged_styles .= ';';
            }

            $merged_styles .= implode(';', $styles);
            $attributes = preg_replace('/style="[^"]*"/i', 'style="' . $merged_styles . '"', $attributes, 1);
        } else {
            $attributes .= ' style="' . implode(';', $styles) . '"';
        }

        return '<' . $tag . $attributes . '>';
    }, $content);
};

$popup_subtitle = trim($lead_funnel_render_rich_text($data->link->settings->popup_subtitle ?? ''));
$description = trim($lead_funnel_render_rich_text($data->link->settings->description ?? ''));
$agreement_text = trim($lead_funnel_render_rich_text($data->link->settings->agreement_text ?? ''));
$thank_you_title = trim($lead_funnel_render_rich_text($data->link->settings->thank_you_title ?? ''));
$thank_you_text = trim($lead_funnel_render_rich_text($data->link->settings->thank_you_text ?? ''));
$popup_subtitle_has_html = strip_tags($popup_subtitle) !== $popup_subtitle;
$description_has_html = strip_tags($description) !== $description;
$agreement_text_has_html = strip_tags($agreement_text) !== $agreement_text;
$thank_you_title_has_html = strip_tags($thank_you_title) !== $thank_you_title;
$thank_you_text_has_html = strip_tags($thank_you_text) !== $thank_you_text;
$contact_country_options = get_contact_phone_country_options_array();
$contact_channel_options = [
    'whatsapp' => 'WhatsApp',
    'viber' => 'Viber',
    'sms' => 'SMS',
];
/* /Custom code: FC-2026-03-23 */
?>

<div class="notification-container mb-3" data-lead-funnel-notification-container></div>

<form id="<?= 'lead_funnel_form_' . $data->link->biolink_block_id ?>" method="post" role="form">
    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
    <input type="hidden" name="biolink_block_id" value="<?= $data->link->biolink_block_id ?>" />

    <div class="lead-funnel-stack">
        <?php if(!empty($popup_subtitle)): ?>
            <div class="lead-funnel-intro-card" data-lead-funnel-popup-subtitle>
                <div class="lead-funnel-section-label"><i class="fas fa-bolt"></i><span><?= l('biolink_lead_funnel.section_intro') ?></span></div>
                <?php if($popup_subtitle_has_html): ?>
                    <div class="lead-funnel-supporting-text ql-content"><?= $popup_subtitle ?></div>
                <?php else: ?>
                    <div class="lead-funnel-supporting-text"><?= nl2br($popup_subtitle) ?></div>
                <?php endif ?>
            </div>
        <?php else: ?>
            <div data-lead-funnel-popup-subtitle style="display:none;"></div>
        <?php endif ?>

        <?php if(!empty($data->embed_url)): ?>
            <div class="lead-funnel-video-card">
                <div class="lead-funnel-video-wrapper">
                    <iframe src="<?= $data->embed_url ?>" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                </div>

                <?php if(!empty($description)): ?>
                    <?php if($description_has_html): ?>
                        <div class="lead-funnel-video-caption ql-content" data-lead-funnel-popup-description><?= $description ?></div>
                    <?php else: ?>
                        <div class="lead-funnel-video-caption" data-lead-funnel-popup-description><?= nl2br($description) ?></div>
                    <?php endif ?>
                <?php else: ?>
                    <div data-lead-funnel-popup-description style="display:none;"></div>
                <?php endif ?>
            </div>
        <?php else: ?>
            <?php if($description_has_html): ?>
                <div class="lead-funnel-intro-card" data-lead-funnel-popup-description style="<?= !empty($description) ? null : 'display:none;' ?>">
                    <div class="lead-funnel-section-label"><i class="fas fa-layer-group"></i><span><?= l('biolink_lead_funnel.section_benefits') ?></span></div>
                    <div class="lead-funnel-supporting-text ql-content"><?= $description ?></div>
                </div>
            <?php else: ?>
                <div class="lead-funnel-intro-card" data-lead-funnel-popup-description style="<?= !empty($description) ? null : 'display:none;' ?>">
                    <div class="lead-funnel-section-label"><i class="fas fa-layer-group"></i><span><?= l('biolink_lead_funnel.section_benefits') ?></span></div>
                    <div class="lead-funnel-supporting-text"><?= nl2br($description) ?></div>
                </div>
            <?php endif ?>
        <?php endif ?>

        <div class="lead-funnel-form-card">
            <div class="lead-funnel-section-label"><i class="fas fa-paper-plane"></i><span><?= l('biolink_lead_funnel.section_form') ?></span></div>

            <?php if(!empty($data->link->settings->show_name)): ?>
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-gray-50"><i class="fas fa-fw fa-signature"></i></div>
                        </div>
                        <input type="text" class="form-control" name="name" maxlength="64" <?= !empty($data->link->settings->require_name) ? 'required="required"' : null ?> placeholder="<?= $data->link->settings->name_placeholder ?>" aria-label="<?= $data->link->settings->name_placeholder ?>" />
                    </div>
                </div>
            <?php endif ?>

            <?php if(!empty($data->link->settings->show_email)): ?>
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text bg-gray-50"><i class="fas fa-fw fa-envelope"></i></div>
                        </div>
                        <input type="email" class="form-control" name="email" maxlength="320" <?= !empty($data->link->settings->require_email) ? 'required="required"' : null ?> placeholder="<?= $data->link->settings->email_placeholder ?>" aria-label="<?= $data->link->settings->email_placeholder ?>" />
                    </div>
                </div>
            <?php endif ?>

            <?php if(!empty($data->link->settings->show_phone)): ?>
                <div class="fcc-contact-capture">
                    <div class="fcc-contact-capture-row">
                        <select class="custom-select" name="phone_country_code" required="required" aria-label="Država i pozivni broj">
                            <?php foreach($contact_country_options as $country_code => $country_label): ?>
                                <option value="<?= $country_code ?>" <?= $country_code == 'HR' ? 'selected="selected"' : null ?>><?= $country_label ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="fcc-contact-capture-row">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <div class="input-group-text"><i class="fas fa-fw fa-phone-square-alt"></i></div>
                            </div>
                            <input type="tel" inputmode="tel" class="form-control" name="phone" maxlength="32" <?= !empty($data->link->settings->require_phone) ? 'required="required"' : null ?> placeholder="<?= $data->link->settings->phone_placeholder ?>" aria-label="<?= $data->link->settings->phone_placeholder ?>" />
                        </div>
                    </div>
                    <div class="fcc-contact-capture-row">
                        <div class="fcc-contact-capture-channels" role="radiogroup" aria-label="Najbrži kanal kontakta">
                            <?php foreach($contact_channel_options as $channel_key => $channel_label): ?>
                                <label class="mb-0">
                                    <input type="radio" class="fcc-contact-capture-radio" name="preferred_contact_channel" value="<?= $channel_key ?>" <?= $channel_key == 'whatsapp' ? 'checked="checked"' : null ?> />
                                    <span class="fcc-contact-capture-chip"><?= $channel_label ?></span>
                                </label>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <?php if(!empty($data->link->settings->show_message)): ?>
                <div class="form-group">
                    <textarea class="form-control" name="message" maxlength="512" <?= !empty($data->link->settings->require_message) ? 'required="required"' : null ?> placeholder="<?= $data->link->settings->message_placeholder ?>" aria-label="<?= $data->link->settings->message_placeholder ?>"></textarea>
                </div>
            <?php endif ?>

            <?php if($data->link->settings->show_agreement): ?>
                <div class="custom-control custom-switch mb-3">
                    <input type="checkbox" id="<?= 'lead_funnel_agreement_' . $data->link->biolink_block_id ?>" name="agreement" class="custom-control-input" required="required" />
                    <label class="custom-control-label font-weight-normal" for="<?= 'lead_funnel_agreement_' . $data->link->biolink_block_id ?>">
                        <span data-lead-funnel-agreement-text class="<?= $agreement_text_has_html ? 'ql-content' : '' ?>"><?= $agreement_text_has_html ? $agreement_text : nl2br($agreement_text) ?></span>
                        <?php if(!empty($data->link->settings->agreement_url)): ?>
                            <a href="<?= $data->link->settings->agreement_url ?>" target="_blank" rel="nofollow noreferrer"><i class="<?= 'fas fa-fw fa-sm fa-external-link-alt' ?>"></i></a>
                        <?php endif ?>
                    </label>
                </div>
            <?php endif ?>

            <?php if(settings()->captcha->biolink_is_enabled && settings()->captcha->type != 'basic'): ?>
                <div class="form-group">
                    <?php (new \Altum\Captcha())->display() ?>
                </div>
            <?php endif ?>

            <div class="text-center mt-4">
                <button type="submit" name="submit" class="btn btn-block btn-lg lead-funnel-submit-button" data-is-ajax data-lead-funnel-submit-button><?= $data->link->settings->button_text ?></button>
                <div class="lead-funnel-submit-helper"><?= l('biolink_lead_funnel.form_helper') ?></div>
            </div>
        </div>
    </div>
</form>

<div class="lead-funnel-thank-you" data-thank-you-screen>
    <div class="lead-funnel-thank-you-card">
        <div class="lead-funnel-section-label"><i class="fas fa-check-circle"></i><span><?= l('biolink_lead_funnel.section_success') ?></span></div>

        <?php if(!empty($thank_you_title)): ?>
            <div class="mb-3 <?= $thank_you_title_has_html ? 'ql-content' : '' ?>" data-lead-funnel-thank-you-title style="color: var(--lead-funnel-text-color); font-size: 1.5rem; font-weight: 700;"><?= $thank_you_title_has_html ? $thank_you_title : nl2br($thank_you_title) ?></div>
        <?php endif ?>

        <?php if(!empty($thank_you_text)): ?>
            <?php if($thank_you_text_has_html): ?>
                <div class="lead-funnel-supporting-text ql-content mb-4" data-lead-funnel-thank-you-text><?= $thank_you_text ?></div>
            <?php else: ?>
                <div class="lead-funnel-supporting-text mb-4" data-lead-funnel-thank-you-text><?= nl2br($thank_you_text) ?></div>
            <?php endif ?>
        <?php endif ?>

        <a href="#" class="btn lead-funnel-thank-you-button d-none" data-thank-you-button><?= $data->link->settings->thank_you_button_text ?? l('biolink_lead_funnel.thank_you_button_text_default') ?></a>
    </div>
</div>

<?php if(!\Altum\Event::exists_content_type_key('javascript', 'lead_funnel')): ?>
    <?php ob_start() ?>
    <script>
        'use strict';

        const trackLeadFunnelEvent = ({biolinkBlockId, eventType, source = '', context = ''}) => {
            if(!biolinkBlockId || !eventType) {
                return;
            }

            $.ajax({
                type: 'POST',
                url: `${site_url}l/link/lead_funnel_event`,
                data: {
                    biolink_block_id: biolinkBlockId,
                    event_type: eventType,
                    source: source,
                    context: context
                },
            });
        };

        $('form[id^="lead_funnel_form_"]').on('submit', event => {
            let form = $(event.currentTarget);
            let notification_container = form.closest('[data-lead-funnel-container]').find('[data-lead-funnel-notification-container]')[0];
            notification_container.innerHTML = '';

            if(form.data('isSubmitting')) {
                event.preventDefault();
                return;
            }

            form.data('isSubmitting', true);
            pause_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));

            $.ajax({
                type: 'POST',
                url: `${site_url}l/link/lead_funnel`,
                data: form.serialize(),
                dataType: 'json',
                success: data => {
                    let details = data.details || {};
                    enable_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));

                    if(data.status == 'error') {
                        form.data('isSubmitting', false);
                        display_notifications(data.message, 'error', notification_container);
                    }

                    else if(data.status == 'success') {
                        notification_container.innerHTML = '';
                        form.find('button[type="submit"]').attr('disabled', 'disabled');

                        let container = form.closest('[data-lead-funnel-container]');
                        let thank_you_screen = container.find('[data-thank-you-screen]');
                        let thank_you_button = container.find('[data-thank-you-button]');
                        let biolinkBlockId = form.find('input[name="biolink_block_id"]').val();

                        if(details.thank_you_type === 'file_download' && details.download_url) {
                            thank_you_button
                                .removeClass('d-none')
                                .attr('href', details.download_url)
                                .attr('target', '_blank')
                                .attr('rel', 'noopener noreferrer')
                                .attr('data-biolink-block-id', biolinkBlockId)
                                .text(details.thank_you_button_text || <?= json_encode(l('biolink_lead_funnel.thank_you_button_text_default')) ?>);

                            form.hide();
                            thank_you_screen.addClass('is-visible');

                            setTimeout(() => {
                                trackLeadFunnelEvent({
                                    biolinkBlockId: biolinkBlockId,
                                    eventType: 'thank_you_cta_click',
                                    source: 'auto_download',
                                    context: 'file_download'
                                });

                                let download_trigger = document.createElement('a');
                                download_trigger.href = details.download_url;
                                download_trigger.target = '_blank';
                                download_trigger.rel = 'noopener noreferrer';
                                download_trigger.click();
                            }, 300);
                        }

                        else if((details.thank_you_type === 'external_url' || details.thank_you_type === 'biolink_redirect') && details.redirect_url) {
                            setTimeout(() => { window.location.assign(details.redirect_url); }, 900);
                        }

                        else {
                            form.hide();
                            thank_you_screen.addClass('is-visible');
                        }
                    }

                    try {
                        grecaptcha.reset();
                        hcaptcha.reset();
                        turnstile.reset();
                    } catch (error) {}
                },
                error: () => {
                    form.data('isSubmitting', false);
                    enable_submit_button(event.currentTarget.querySelector('[type="submit"][name="submit"]'));
                    display_notifications(<?= json_encode(l('global.error_message.basic')) ?>, 'error', notification_container);
                },
            });

            event.preventDefault();
        });

        $(document).on('click', '[data-thank-you-button]', event => {
            let button = $(event.currentTarget);
            let biolinkBlockId = button.attr('data-biolink-block-id');

            if(!biolinkBlockId) {
                return;
            }

            trackLeadFunnelEvent({
                biolinkBlockId: biolinkBlockId,
                eventType: 'thank_you_cta_click',
                source: 'manual_click',
                context: 'file_download'
            });
        });
    </script>
    <?php \Altum\Event::add_content(ob_get_clean(), 'javascript', 'lead_funnel') ?>
<?php endif ?>
<?php /* /Custom code: FC-2026-03-23 */ ?>
