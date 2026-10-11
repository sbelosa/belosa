<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_is_hr = \Altum\Language::$code === 'hr';
$fcc_forever_product_copy = $fcc_is_hr ? [
    'product_section' => fcc_t('Izvor proizvoda i jezik'),
    'product_section_help' => fcc_t('Odaberi proizvod, postavi kako blok prati jezik aplikacije i definira fallback kada prijevod ne postoji.'),
    'language_mode' => fcc_t('Način jezika'),
    'language_mode_app' => fcc_t('Prati jezik aplikacije'),
    'language_mode_manual' => fcc_t('Koristi ručno odabrani jezik'),
    'language_mode_help' => fcc_t('U modu praćenja aplikacije blok automatski pokušava otvoriti verziju proizvoda na jeziku aplikacije.'),
    'manual_language' => fcc_t('Ručni jezik proizvoda'),
    'manual_language_help' => fcc_t('Koristi se samo kada ne želiš pratiti jezik aplikacije.'),
    'fallback_language' => fcc_t('Fallback jezik'),
    'fallback_language_help' => fcc_t('Ako željeni jezik proizvoda ne postoji, blok prelazi na ovaj jezik.'),
    'fallback_none' => fcc_t('Bez fallbacka'),
    'app_language_note' => fcc_t('Jezik aplikacije: '),
    'selector_language_hint' => fcc_t('Dostupni jezici proizvoda: '),
] : [
    'product_section' => 'Product source and language',
    'product_section_help' => fcc_t('Choose the product, define how the block follows the app language, and set the fallback when a translation is missing.'),
    'language_mode' => 'Language mode',
    'language_mode_app' => fcc_t('Follow the app language'),
    'language_mode_manual' => 'Use a manually selected language',
    'language_mode_help' => fcc_t('When the block follows the app language it automatically tries to open the product in the app language first.'),
    'manual_language' => fcc_t('Manual product language'),
    'manual_language_help' => fcc_t('Used only when the block should not follow the app language.'),
    'fallback_language' => fcc_t('Fallback language'),
    'fallback_language_help' => fcc_t('If the preferred product language is missing, the block falls back to this language.'),
    'fallback_none' => fcc_t('No fallback'),
    'app_language_note' => fcc_t('App language: '),
    'selector_language_hint' => fcc_t('Available product languages: '),
];

$fcc_active_language_codes = array_values((array) \Altum\Language::$active_languages);
$fcc_app_language_code = $data->link->settings->language_code ?? \Altum\Language::$default_code;
$fcc_product_market = fcc_forever_country_code($_GET['market'] ?? null);
if(!$fcc_product_market || !isset(fcc_forever_markets()[$fcc_product_market])) $fcc_product_market = '';
$fcc_product_language_mode = in_array($row->settings->product_language_mode ?? 'app', ['app', 'manual'], true) ? $row->settings->product_language_mode : 'app';
$fcc_product_language_code = $row->settings->product_language_code ?? ($data->link->settings->language_code ?? \Altum\Language::$default_code);
$fcc_product_fallback_language_code = (string) ($row->settings->product_fallback_language_code ?? 'hr');
$fcc_product_translation_key = (string) ($row->settings->product_translation_key ?? '');
$fcc_selected_language_label = '';
foreach($data->blog_products ?? [] as $fcc_blog_product) {
    if(($fcc_blog_product->translation_key ?? '') === $fcc_product_translation_key) {
        $fcc_selected_language_label = (string) ($fcc_blog_product->available_languages_label ?? '');
        break;
    }
}
?>

<!-- Custom code: FC-2026-03-09: reuse base Forever Shop styling/options and extend with product picker UX -->
<?php require THEME_PATH . 'views/link/settings/biolink_blocks/link_forever_shop/link_forever_shop_update_form.php' ?>

<div class="card bg-gray-50 border-0 p-3 mb-3">
    <h6 class="mb-2"><i class="fas fa-fw fa-box-open text-muted mr-1"></i> <?= $fcc_forever_product_copy['product_section'] ?></h6>
    <p class="small text-muted mb-3"><?= $fcc_forever_product_copy['product_section_help'] ?></p>

    <div class="form-group mb-3">
        <label for="<?= 'link_forever_product_selector_' . $row->biolink_block_id ?>"><?= l('create_biolink_link_forever_product_modal.product') ?></label>
        <select
            id="<?= 'link_forever_product_selector_' . $row->biolink_block_id ?>"
            class="custom-select"
            data-is-not-custom-select
            data-forever-product-selector
            data-form-id="<?= 'update_biolink_block_' . $row->biolink_block_id ?>"
        >
            <option value=""><?= l('create_biolink_link_forever_product_modal.product_placeholder') ?></option>
            <?php foreach($data->blog_products ?? [] as $blog_product): ?>
                <?php $is_selected_product = $fcc_product_translation_key !== ''
                    ? $fcc_product_translation_key === (string) ($blog_product->translation_key ?? '')
                    : (int) ($row->settings->product_blog_post_id ?? 0) === (int) $blog_product->blog_post_id ?>
                <?php foreach((array) ($blog_product->translations ?? []) as $fcc_variant) {
                    $fcc_variant = (object) $fcc_variant;
                    if($fcc_product_translation_key === '' && !empty($row->settings->product_blog_post_id)
                        && (int) $row->settings->product_blog_post_id === (int) ($fcc_variant->blog_post_id ?? 0)) $is_selected_product = true;
                } ?>
                <option
                    value="<?= $blog_product->blog_post_id ?>"
                    data-title="<?= htmlspecialchars($blog_product->title, ENT_QUOTES) ?>"
                    data-description="<?= htmlspecialchars($blog_product->description ?? '', ENT_QUOTES) ?>"
                    data-url="<?= htmlspecialchars($blog_product->blog_url, ENT_QUOTES) ?>"
                    data-image-url="<?= htmlspecialchars($blog_product->image_url ?? '', ENT_QUOTES) ?>"
                    data-translation-key="<?= htmlspecialchars($blog_product->translation_key ?? '', ENT_QUOTES) ?>"
                    data-language-code="<?= htmlspecialchars($blog_product->language_code ?? '', ENT_QUOTES) ?>"
                    data-language-label="<?= htmlspecialchars($blog_product->available_languages_label ?? '', ENT_QUOTES) ?>"
                    data-translations="<?= htmlspecialchars(json_encode($blog_product->translations ?? new \stdClass(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"
                    <?= $is_selected_product ? 'selected="selected"' : null ?>
                >
                    <?= $blog_product->title ?><?= !empty($blog_product->available_languages_label) ? ' [' . $blog_product->available_languages_label . ']' : '' ?>
                </option>
            <?php endforeach ?>
        </select>
        <small class="form-text text-muted"><?= l('create_biolink_link_forever_product_modal.product_help') ?></small>
        <small class="form-text text-muted<?= $fcc_selected_language_label === '' ? ' d-none' : '' ?>" data-forever-product-language-hint>
            <?= $fcc_selected_language_label !== '' ? $fcc_forever_product_copy['selector_language_hint'] . htmlspecialchars($fcc_selected_language_label, ENT_QUOTES, 'UTF-8') : '' ?>
        </small>
    </div>

    <div class="row mb-3">
        <div class="col-12 col-lg-6">
            <div class="form-group mb-lg-0">
                <label for="<?= 'link_forever_product_language_mode_' . $row->biolink_block_id ?>"><?= $fcc_forever_product_copy['language_mode'] ?></label>
                <select
                    id="<?= 'link_forever_product_language_mode_' . $row->biolink_block_id ?>"
                    name="product_language_mode"
                    class="custom-select"
                    form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>"
                    data-forever-product-language-mode
                    data-form-id="<?= 'update_biolink_block_' . $row->biolink_block_id ?>"
                >
                    <option value="app" <?= $fcc_product_language_mode === 'app' ? 'selected="selected"' : null ?>><?= $fcc_forever_product_copy['language_mode_app'] ?></option>
                    <option value="manual" <?= $fcc_product_language_mode === 'manual' ? 'selected="selected"' : null ?>><?= $fcc_forever_product_copy['language_mode_manual'] ?></option>
                </select>
                <small class="form-text text-muted">
                    <?= $fcc_forever_product_copy['language_mode_help'] ?>
                    <span class="d-block mt-1"><?= $fcc_forever_product_copy['app_language_note'] ?><strong><?= mb_strtoupper((string) ($data->link->settings->language_code ?? \Altum\Language::$default_code)) ?></strong></span>
                </small>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="form-group mb-0" data-forever-product-manual-language-wrapper style="<?= $fcc_product_language_mode === 'manual' ? '' : 'display:none;' ?>">
                <label for="<?= 'link_forever_product_language_code_' . $row->biolink_block_id ?>"><?= $fcc_forever_product_copy['manual_language'] ?></label>
                <select
                    id="<?= 'link_forever_product_language_code_' . $row->biolink_block_id ?>"
                    name="product_language_code"
                    class="custom-select"
                    form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>"
                    data-forever-product-language-code
                >
                    <?php foreach($fcc_active_language_codes as $language_code): ?>
                        <option value="<?= $language_code ?>" <?= $language_code === $fcc_product_language_code ? 'selected="selected"' : null ?>><?= mb_strtoupper($language_code) ?></option>
                    <?php endforeach ?>
                </select>
                <small class="form-text text-muted"><?= $fcc_forever_product_copy['manual_language_help'] ?></small>
            </div>
        </div>
    </div>

    <div class="form-group mb-3">
        <label for="<?= 'link_forever_product_fallback_language_code_' . $row->biolink_block_id ?>"><?= $fcc_forever_product_copy['fallback_language'] ?></label>
        <select
            id="<?= 'link_forever_product_fallback_language_code_' . $row->biolink_block_id ?>"
            name="product_fallback_language_code"
            class="custom-select"
            form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>"
            data-forever-product-fallback-language-code
        >
            <option value="hr" <?= $fcc_product_fallback_language_code === 'hr' ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('HR'), ENT_QUOTES, 'UTF-8') ?></option>
            <?php foreach($fcc_active_language_codes as $language_code): ?>
                <?php if($language_code === 'hr') continue ?>
                <option value="<?= $language_code ?>" <?= $language_code === $fcc_product_fallback_language_code ? 'selected="selected"' : null ?>><?= mb_strtoupper($language_code) ?></option>
            <?php endforeach ?>
            <option value="" <?= $fcc_product_fallback_language_code === '' ? 'selected="selected"' : null ?>><?= $fcc_forever_product_copy['fallback_none'] ?></option>
        </select>
        <small class="form-text text-muted"><?= $fcc_forever_product_copy['fallback_language_help'] ?></small>
    </div>

    <div data-forever-product-preview-wrapper class="mb-1" style="<?= !empty($row->settings->product_image_url ?? null) ? '' : 'display:none;' ?>">
        <img
            src="<?= $row->settings->product_image_url ?? '' ?>"
            alt=""
            data-forever-product-preview-image
            style="max-width: 88px; border-radius: 10px; border: 1px solid #d8dde6;"
        />
    </div>

    <div class="form-group mt-3 mb-1">
        <label for="<?= 'link_forever_product_description_' . $row->biolink_block_id ?>"><i class="fas fa-fw fa-align-left fa-sm text-muted mr-1"></i> <?= l('create_biolink_link_forever_product_modal.description') ?></label>
        <textarea id="<?= 'link_forever_product_description_' . $row->biolink_block_id ?>" name="description" class="form-control" rows="2" maxlength="220" form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" data-forever-product-description><?= $row->settings->description ?? '' ?></textarea>
        <small class="form-text text-muted"><?= l('create_biolink_link_forever_product_modal.description_help') ?></small>
    </div>
</div>

<input type="hidden" name="block_type" value="link_forever_product" form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" />
<input type="hidden" name="product_blog_post_id" value="<?= (int) ($row->settings->product_blog_post_id ?? 0) ?>" data-forever-product-blog-post-id form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" />
<input type="hidden" name="product_translation_key" value="<?= htmlspecialchars($fcc_product_translation_key, ENT_QUOTES, 'UTF-8') ?>" data-forever-product-translation-key form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" />
<input type="hidden" name="product_image_url" value="<?= $row->settings->product_image_url ?? '' ?>" data-forever-product-image-url form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" />
<input type="hidden" name="product_name_mode" value="<?= fcc_product_saved_text_mode($row->settings, 'name', (string) ($row->settings->product_translation_key ?? '')) ?>" data-forever-product-name-mode form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" />
<input type="hidden" name="product_description_mode" value="<?= fcc_product_saved_text_mode($row->settings, 'description', (string) ($row->settings->product_translation_key ?? '')) ?>" data-forever-product-description-mode form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" />

<?php ob_start() ?>
<script>
'use strict';

(() => {
    const selector = document.getElementById(<?= json_encode('link_forever_product_selector_' . $row->biolink_block_id) ?>);
    if(!selector) return;

    const formId = selector.getAttribute('data-form-id');
    const form = document.getElementById(formId);
    if(!form) return;

    const nameInput = form.querySelector('input[name="name"]');
    const locationInput = form.querySelector('input[name="location_url"]');
    const blockTypeInput = form.querySelector('input[name="block_type"]');
    const iconInput = form.querySelector('input[name="icon"]');
    const languageMode = document.querySelector(`[data-forever-product-language-mode][form="${formId}"]`);
    const manualLanguage = document.querySelector(`[data-forever-product-language-code][form="${formId}"]`);
    const fallbackLanguage = document.querySelector(`[data-forever-product-fallback-language-code][form="${formId}"]`);
    const nameModeInput = document.querySelector(`[data-forever-product-name-mode][form="${formId}"]`);
    const descriptionModeInput = document.querySelector(`[data-forever-product-description-mode][form="${formId}"]`);
    const manualLanguageWrapper = selector.closest('.card')?.querySelector('[data-forever-product-manual-language-wrapper]');
    const descriptionInput = document.querySelector(`textarea[name="description"][form="${formId}"]`);
    const blogPostIdInput = document.querySelector(`input[name="product_blog_post_id"][form="${formId}"]`);
    const translationKeyInput = document.querySelector(`input[name="product_translation_key"][form="${formId}"]`);
    const imageUrlInput = document.querySelector(`input[name="product_image_url"][form="${formId}"]`);
    const previewWrapper = selector.closest('.card')?.querySelector('[data-forever-product-preview-wrapper]');
    const previewImage = selector.closest('.card')?.querySelector('[data-forever-product-preview-image]');
    const languageHint = selector.closest('.card')?.querySelector('[data-forever-product-language-hint]');
    const appLanguage = <?= json_encode((string) $fcc_app_language_code) ?>;
    const selectedMarket = <?= json_encode((string) $fcc_product_market) ?>;
    const normalizeText = value => String(value || '').replace(/\s+/g, ' ').trim();
    const inputText = (value, element) => {
        const text = String(value || '');
        const limit = Number(element?.maxLength);
        return limit > 0 ? Array.from(text).slice(0, limit).join('') : text;
    };
    const translationsFor = option => {
        if(!option || !option.value) return {};
        let translations = {};
        try {
            const parsed = JSON.parse(option.getAttribute('data-translations') || '{}');
            if(parsed && typeof parsed === 'object' && !Array.isArray(parsed)) translations = parsed;
        } catch(error) {
            translations = {};
        }
        const preferredCode = option.getAttribute('data-language-code') || appLanguage;
        if(preferredCode && !translations[preferredCode]) {
            translations[preferredCode] = {
                blog_post_id: option.value,
                title: option.getAttribute('data-title') || '',
                description: option.getAttribute('data-description') || '',
                blog_url: option.getAttribute('data-url') || '',
                image_url: option.getAttribute('data-image-url') || '',
                language_code: preferredCode
            };
        }
        return translations;
    };
    let currentTranslations = translationsFor(selector.options[selector.selectedIndex]);
    const matchesAutomaticText = (element, key) => {
        const value = normalizeText(element?.value);
        return value === '' || Object.values(currentTranslations).some(variant => variant && normalizeText(inputText(variant[key], element)) === value);
    };
    const initializeTextMode = (element, modeInput, key) => {
        if(!element || !modeInput) return;
        if(!['auto', 'custom'].includes(modeInput.value)) {
            modeInput.value = matchesAutomaticText(element, key) ? 'auto' : 'custom';
        }
        const trackTextEdit = () => {
            modeInput.value = matchesAutomaticText(element, key) ? 'auto' : 'custom';
        };
        element.addEventListener('input', trackTextEdit);
        element.addEventListener('change', trackTextEdit);
    };
    initializeTextMode(nameInput, nameModeInput, 'title');
    initializeTextMode(descriptionInput, descriptionModeInput, 'description');

    const syncLanguageMode = () => {
        if(manualLanguageWrapper && languageMode) {
            manualLanguageWrapper.style.display = languageMode.value === 'manual' ? '' : 'none';
        }
    };
    const productUrl = value => {
        if(!value || !selectedMarket) return value || '';
        try {
            const url = new URL(value, window.location.href);
            url.searchParams.set('market', selectedMarket);
            return url.href;
        } catch(error) {
            return value;
        }
    };
    const applySelectedProduct = () => {
        const option = selector.options[selector.selectedIndex];
        currentTranslations = translationsFor(option);
        const targetLanguage = languageMode?.value === 'manual' ? manualLanguage?.value : appLanguage;
        const fallbackCode = fallbackLanguage?.value || '';
        const variant = currentTranslations[targetLanguage] || (fallbackCode ? currentTranslations[fallbackCode] : null);
        const translationKey = option?.value ? option.getAttribute('data-translation-key') || '' : '';
        const languageLabel = option?.value ? option.getAttribute('data-language-label') || Object.keys(currentTranslations).map(code => code.toUpperCase()).join(' / ') : '';

        if(blogPostIdInput) blogPostIdInput.value = variant?.blog_post_id || 0;
        if(translationKeyInput) translationKeyInput.value = translationKey;
        if(locationInput) locationInput.value = productUrl(variant?.blog_url || '');
        if(imageUrlInput) imageUrlInput.value = variant?.image_url || '';
        if(nameInput && nameModeInput?.value === 'auto') nameInput.value = inputText(variant?.title, nameInput);
        if(descriptionInput && descriptionModeInput?.value === 'auto') descriptionInput.value = inputText(variant?.description, descriptionInput);

        if(languageHint) {
            languageHint.textContent = languageLabel ? <?= json_encode($fcc_forever_product_copy['selector_language_hint']) ?> + languageLabel : '';
            languageHint.classList.toggle('d-none', !languageLabel);
        }
        if(previewWrapper && previewImage) {
            previewImage.src = variant?.image_url || '';
            previewWrapper.style.display = variant?.image_url ? '' : 'none';
        }
    };

    selector.addEventListener('change', applySelectedProduct);
    languageMode?.addEventListener('change', () => {
        syncLanguageMode();
        applySelectedProduct();
    });
    manualLanguage?.addEventListener('change', applySelectedProduct);
    fallbackLanguage?.addEventListener('change', applySelectedProduct);
    syncLanguageMode();
    if(selector.options[selector.selectedIndex]?.value) applySelectedProduct();

    if(blockTypeInput) blockTypeInput.value = 'link_forever_product';
    if(iconInput) {
        iconInput.value = '';
        const iconGroup = iconInput.closest('.form-group');
        if(iconGroup) iconGroup.style.display = 'none';
    }

    const parentModal = $(selector).closest('.modal');

    $(selector).select2({
        dropdownParent: parentModal.length ? parentModal : null,
        placeholder: <?= json_encode(l('create_biolink_link_forever_product_modal.product_placeholder')) ?>,
        width: '100%',
        minimumResultsForSearch: 0,
        dir: <?= json_encode(l('direction')) ?>
    });
})();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<!-- /Custom code: FC-2026-03-09 -->
