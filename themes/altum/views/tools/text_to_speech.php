<?php defined('ALTUMCODE') || die() ?>

<div class="container">
    <?= \Altum\Alerts::output_alerts() ?>

    <?php if(settings()->main->breadcrumbs_is_enabled): ?>
        <nav aria-label="<?= htmlspecialchars(fcc_t('breadcrumb'), ENT_QUOTES, 'UTF-8') ?>">
            <ol class="custom-breadcrumbs small">
                <li><a href="<?= url('tools') ?>"><?= l('tools.breadcrumb') ?></a> <i class="fas fa-fw fa-angle-right"></i></li>
                <li class="active" aria-current="page"><?= l('tools.text_to_speech.name') ?></li>
            </ol>
        </nav>
    <?php endif ?>

    <div class="row mb-4">
        <div class="col-12 col-lg d-flex align-items-center mb-3 mb-lg-0 text-truncate">
            <h1 class="h4 m-0 text-truncate"><?= l('tools.text_to_speech.name') ?></h1>

            <div class="ml-2">
                <span data-toggle="tooltip" title="<?= l('tools.text_to_speech.description') ?>">
                    <i class="fas fa-fw fa-info-circle text-muted"></i>
                </span>
            </div>
        </div>

        <?= $this->views['ratings'] ?>
    </div>

    <div class="card">
        <div class="card-body">

            <form action="" method="post" role="form">
                <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" />

                <div class="form-group" data-character-counter="textarea">
                    <label for="text" class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-fw fa-paragraph fa-sm text-muted mr-1"></i> <?= l('tools.text') ?></span>
                        <small class="text-muted" data-character-counter-wrapper></small>
                    </label>
                    <textarea id="text" name="text" maxlength="200" class="form-control <?= \Altum\Alerts::has_field_errors('text') ? 'is-invalid' : null ?>" required="required"><?= $data->values['text'] ?></textarea>
                    <?= \Altum\Alerts::output_field_error('text') ?>
                </div>

                <div class="form-group">
                    <label for="language_code"><i class="fas fa-fw fa-language fa-sm text-muted mr-1"></i> <?= l('tools.text_to_speech.language_code') ?></label>
                    <select id="language_code" name="language_code" class="form-control <?= \Altum\Alerts::has_field_errors('language_code') ? 'is-invalid' : null ?>">
                        <option value="af" <?= $data->values['language_code'] == 'af' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('af – Afrikaans'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="sq" <?= $data->values['language_code'] == 'sq' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('sq – Albanian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="am" <?= $data->values['language_code'] == 'am' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('am – Amharic'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ar" <?= $data->values['language_code'] == 'ar' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ar – Arabic'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="eu" <?= $data->values['language_code'] == 'eu' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('eu – Basque'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="bn" <?= $data->values['language_code'] == 'bn' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('bn – Bengali'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="bs" <?= $data->values['language_code'] == 'bs' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('bs – Bosnian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="bg" <?= $data->values['language_code'] == 'bg' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('bg – Bulgarian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="yue" <?= $data->values['language_code'] == 'yue' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('yue – Cantonese'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ca" <?= $data->values['language_code'] == 'ca' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ca – Catalan'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="zh- <?= $data->values['language_code'] == 'zh' ? 'selected="selected"' : null ?>CN"><?= htmlspecialchars(fcc_t('zh-CN – Chinese (Simplified)'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="zh- <?= $data->values['language_code'] == 'zh' ? 'selected="selected"' : null ?>TW"><?= htmlspecialchars(fcc_t('zh-TW – Chinese (Traditional)'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="hr" <?= $data->values['language_code'] == 'hr' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('hr – Croatian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="cs" <?= $data->values['language_code'] == 'cs' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('cs – Czech'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="da" <?= $data->values['language_code'] == 'da' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('da – Danish'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="nl" <?= $data->values['language_code'] == 'nl' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('nl – Dutch'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="en" <?= $data->values['language_code'] == 'en' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('en – English'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="et" <?= $data->values['language_code'] == 'et' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('et – Estonian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="tl" <?= $data->values['language_code'] == 'tl' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('tl – Filipino'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="fi" <?= $data->values['language_code'] == 'fi' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('fi – Finnish'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="fr" <?= $data->values['language_code'] == 'fr' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('fr – French'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="fr- <?= $data->values['language_code'] == 'fr' ? 'selected="selected"' : null ?>CA"><?= htmlspecialchars(fcc_t('fr-CA – French (Canada)'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="gl" <?= $data->values['language_code'] == 'gl' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('gl – Galician'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="de" <?= $data->values['language_code'] == 'de' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('de – German'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="el" <?= $data->values['language_code'] == 'el' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('el – Greek'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="gu" <?= $data->values['language_code'] == 'gu' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('gu – Gujarati'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ha" <?= $data->values['language_code'] == 'ha' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ha – Hausa'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="he" <?= $data->values['language_code'] == 'he' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('he – Hebrew'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="hi" <?= $data->values['language_code'] == 'hi' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('hi – Hindi'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="hu" <?= $data->values['language_code'] == 'hu' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('hu – Hungarian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="is" <?= $data->values['language_code'] == 'is' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('is – Icelandic'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="id" <?= $data->values['language_code'] == 'id' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('id – Indonesian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="it" <?= $data->values['language_code'] == 'it' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('it – Italian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ja" <?= $data->values['language_code'] == 'ja' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ja – Japanese'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="jv" <?= $data->values['language_code'] == 'jv' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('jv – Javanese'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="kn" <?= $data->values['language_code'] == 'kn' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('kn – Kannada'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="km" <?= $data->values['language_code'] == 'km' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('km – Khmer'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ko" <?= $data->values['language_code'] == 'ko' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ko – Korean'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="la" <?= $data->values['language_code'] == 'la' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('la – Latin'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="lv" <?= $data->values['language_code'] == 'lv' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('lv – Latvian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ml" <?= $data->values['language_code'] == 'ml' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ml – Malayalam'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="mr" <?= $data->values['language_code'] == 'mr' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('mr – Marathi'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="my" <?= $data->values['language_code'] == 'my' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('my – Myanmar (Burmese)'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ne" <?= $data->values['language_code'] == 'ne' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ne – Nepali'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="nb" <?= $data->values['language_code'] == 'nb' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('nb – Norwegian (Bokmål)'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="pl" <?= $data->values['language_code'] == 'pl' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('pl – Polish'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="pt- <?= $data->values['language_code'] == 'pt' ? 'selected="selected"' : null ?>BR"><?= htmlspecialchars(fcc_t('pt-BR – Portuguese (Brazil)'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="pt- <?= $data->values['language_code'] == 'pt' ? 'selected="selected"' : null ?>PT"><?= htmlspecialchars(fcc_t('pt-PT – Portuguese (Portugal)'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="pa" <?= $data->values['language_code'] == 'pa' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('pa – Punjabi (Gurmukhi)'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ro" <?= $data->values['language_code'] == 'ro' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ro – Romanian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ru" <?= $data->values['language_code'] == 'ru' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ru – Russian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="sr" <?= $data->values['language_code'] == 'sr' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('sr – Serbian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="si" <?= $data->values['language_code'] == 'si' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('si – Sinhala'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="sk" <?= $data->values['language_code'] == 'sk' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('sk – Slovak'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="es" <?= $data->values['language_code'] == 'es' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('es – Spanish'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="su" <?= $data->values['language_code'] == 'su' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('su – Sundanese'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="sw" <?= $data->values['language_code'] == 'sw' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('sw – Swahili'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="sv" <?= $data->values['language_code'] == 'sv' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('sv – Swedish'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ta" <?= $data->values['language_code'] == 'ta' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ta – Tamil'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="te" <?= $data->values['language_code'] == 'te' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('te – Telugu'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="th" <?= $data->values['language_code'] == 'th' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('th – Thai'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="tr" <?= $data->values['language_code'] == 'tr' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('tr – Turkish'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="uk" <?= $data->values['language_code'] == 'uk' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('uk – Ukrainian'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="ur" <?= $data->values['language_code'] == 'ur' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('ur – Urdu'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="vi" <?= $data->values['language_code'] == 'vi' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('vi – Vietnamese'), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="cy" <?= $data->values['language_code'] == 'cy' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('cy – Welsh'), ENT_QUOTES, 'UTF-8') ?></option>
                    </select>
                    <?= \Altum\Alerts::output_field_error('language_code') ?>
                </div>

                <button type="submit" name="submit" class="btn btn-block btn-primary"><?= l('global.submit') ?></button>
            </form>

        </div>
    </div>

    <?php if(isset($data->result)): ?>
        <div class="mt-4">
            <div class="card">
                <div class="card-body">
                    <audio class="w-100" controls>
                        <source src="<?= url('tools/text-to-speech?text=' . $data->values['text'] . '&language_code=' . $data->values['language_code']) ?>" type="audio/mp3">
                    </audio>
                </div>
            </div>
        </div>
    <?php endif ?>

    <?= $this->views['extra_content'] ?>

    <?= $this->views['similar_tools'] ?>

    <?= $this->views['popular_tools'] ?>
</div>

<?php include_view(THEME_PATH . 'views/partials/clipboard_js.php') ?>

