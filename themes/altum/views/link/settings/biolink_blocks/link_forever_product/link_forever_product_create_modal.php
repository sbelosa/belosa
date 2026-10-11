<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_is_hr = \Altum\Language::$code === 'hr';
$fcc_forever_product_copy = $fcc_is_hr ? [
    'language_section' => fcc_t('Jezik sadržaja proizvoda'),
    'language_section_help' => fcc_t('Odredi treba li ovaj blok pratiti jezik aplikacije ili koristiti točno odabrani jezik proizvoda.'),
    'language_mode' => fcc_t('Način jezika'),
    'language_mode_app' => fcc_t('Prati jezik aplikacije'),
    'language_mode_manual' => fcc_t('Odaberi jezik proizvoda ručno'),
    'language_mode_help' => fcc_t('Ako aplikacija radi na EN, blok će pokušati povući EN verziju proizvoda. Ako ne postoji, koristi se fallback.'),
    'manual_language' => fcc_t('Ručni jezik proizvoda'),
    'manual_language_help' => fcc_t('Koristi se kada želiš da ovaj blok uvijek vodi na određeni jezik proizvoda bez obzira na jezik aplikacije.'),
    'fallback_language' => fcc_t('Fallback jezik'),
    'fallback_language_help' => fcc_t('Ako traženi jezik proizvoda ne postoji, blok automatski prelazi na odabrani fallback.'),
    'fallback_none' => fcc_t('Bez fallbacka'),
    'app_language_note' => fcc_t('Trenutni jezik ove aplikacije: '),
    'selector_language_hint' => fcc_t('Dostupni jezici proizvoda: '),
] : [
    'language_section' => 'Product content language',
    'language_section_help' => fcc_t('Choose whether this block should follow the app language or always use a specific product language.'),
    'language_mode' => 'Language mode',
    'language_mode_app' => fcc_t('Follow the app language'),
    'language_mode_manual' => fcc_t('Choose the product language manually'),
    'language_mode_help' => fcc_t('If the app runs in EN, the block will try to load the EN product version. When it is missing, the fallback is used.'),
    'manual_language' => fcc_t('Manual product language'),
    'manual_language_help' => fcc_t('Use this when the block should always open one product language regardless of the app language.'),
    'fallback_language' => fcc_t('Fallback language'),
    'fallback_language_help' => fcc_t('If the requested product language is missing, the block automatically falls back to the selected language.'),
    'fallback_none' => fcc_t('No fallback'),
    'app_language_note' => fcc_t('Current app language: '),
    'selector_language_hint' => fcc_t('Available product languages: '),
];

$fcc_active_language_codes = array_values((array) \Altum\Language::$active_languages);
$fcc_app_language_code = $data->link->settings->language_code ?? \Altum\Language::$default_code;
$fcc_product_market = fcc_forever_country_code($_GET['market'] ?? null);
if(!$fcc_product_market || !isset(fcc_forever_markets()[$fcc_product_market])) $fcc_product_market = '';
?>

<!-- Custom code: FC-2026-03-09: create modal for Forever product picker block -->
<div class="modal fade" id="create_biolink_link_forever_product" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" data-toggle="modal" data-target="#biolink_link_create_modal" data-dismiss="modal" class="btn btn-sm btn-link"><i class="fa fa-fw fa-chevron-circle-left text-muted"></i></button>
                <h5 class="modal-title"><?= l('create_biolink_link_forever_product_modal.header') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form name="create_biolink_link_forever_product" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                    <input type="hidden" name="request_type" value="create" />
                    <input type="hidden" name="link_id" value="<?= $data->link->link_id ?>" />
                    <input type="hidden" name="block_type" value="link_forever_product" />
                    <input type="hidden" name="location_url" value="" data-forever-product-location-url />
                    <input type="hidden" name="product_blog_post_id" value="0" data-forever-product-blog-post-id />
                    <input type="hidden" name="product_translation_key" value="" data-forever-product-translation-key />
                    <input type="hidden" name="product_image_url" value="" data-forever-product-image-url />
                    <input type="hidden" name="product_name_mode" value="auto" data-forever-product-name-mode />
                    <input type="hidden" name="product_description_mode" value="auto" data-forever-product-description-mode />

                    <div class="notification-container"></div>

                    <div class="alert alert-info mb-3">
                        <i class="fas fa-fw fa-circle-info mr-1"></i>
                        <?= l('create_biolink_link_forever_product_modal.info') ?>
                    </div>

                    <div class="form-group">
                        <label for="forever_product_selector"><i class="fas fa-fw fa-box-open fa-sm text-muted mr-1"></i> <?= l('create_biolink_link_forever_product_modal.product') ?></label>
                        <select id="forever_product_selector" class="custom-select" required="required" data-forever-product-selector data-is-not-custom-select>
                            <option value=""><?= l('create_biolink_link_forever_product_modal.product_placeholder') ?></option>
                            <?php foreach($data->blog_products ?? [] as $blog_product): ?>
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
                                >
                                    <?= $blog_product->title ?><?= !empty($blog_product->available_languages_label) ? ' [' . $blog_product->available_languages_label . ']' : '' ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <small class="form-text text-muted"><?= l('create_biolink_link_forever_product_modal.product_help') ?></small>
                        <small class="form-text text-muted d-none" data-forever-product-language-hint></small>
                    </div>

                    <div class="card bg-gray-50 border-0 p-3 mb-3">
                        <h6 class="mb-2"><i class="fas fa-fw fa-language text-muted mr-1"></i> <?= $fcc_forever_product_copy['language_section'] ?></h6>
                        <p class="small text-muted mb-3"><?= $fcc_forever_product_copy['language_section_help'] ?></p>

                        <div class="form-group">
                            <label for="forever_product_language_mode"><?= $fcc_forever_product_copy['language_mode'] ?></label>
                            <select id="forever_product_language_mode" name="product_language_mode" class="custom-select" data-forever-product-language-mode>
                                <option value="app"><?= $fcc_forever_product_copy['language_mode_app'] ?></option>
                                <option value="manual"><?= $fcc_forever_product_copy['language_mode_manual'] ?></option>
                            </select>
                            <small class="form-text text-muted">
                                <?= $fcc_forever_product_copy['language_mode_help'] ?>
                                <span class="d-block mt-1"><?= $fcc_forever_product_copy['app_language_note'] ?><strong><?= mb_strtoupper($fcc_app_language_code) ?></strong></span>
                            </small>
                        </div>

                        <div class="row" data-forever-product-manual-language-wrapper style="display:none;">
                            <div class="col-12 col-lg-6">
                                <div class="form-group mb-lg-0">
                                    <label for="forever_product_language_code"><?= $fcc_forever_product_copy['manual_language'] ?></label>
                                    <select id="forever_product_language_code" name="product_language_code" class="custom-select" data-forever-product-language-code>
                                        <?php foreach($fcc_active_language_codes as $language_code): ?>
                                            <option value="<?= $language_code ?>" <?= $language_code === $fcc_app_language_code ? 'selected="selected"' : null ?>><?= mb_strtoupper($language_code) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                    <small class="form-text text-muted"><?= $fcc_forever_product_copy['manual_language_help'] ?></small>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="form-group mb-0">
                                    <label for="forever_product_fallback_language_code"><?= $fcc_forever_product_copy['fallback_language'] ?></label>
                                    <select id="forever_product_fallback_language_code" name="product_fallback_language_code" class="custom-select" data-forever-product-fallback-language-code>
                                        <option value="hr" <?= 'hr' === $fcc_app_language_code ? 'selected="selected"' : null ?>><?= htmlspecialchars(fcc_t('HR'), ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php foreach($fcc_active_language_codes as $language_code): ?>
                                            <?php if($language_code === 'hr') continue ?>
                                            <option value="<?= $language_code ?>"><?= mb_strtoupper($language_code) ?></option>
                                        <?php endforeach ?>
                                        <option value=""><?= $fcc_forever_product_copy['fallback_none'] ?></option>
                                    </select>
                                    <small class="form-text text-muted"><?= $fcc_forever_product_copy['fallback_language_help'] ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="forever_product_name"><i class="fa fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('biolink_link.name') ?></label>
                        <input id="forever_product_name" type="text" name="name" maxlength="128" class="form-control" value="" required="required" data-forever-product-name />
                        <small class="form-text text-muted"><?= l('create_biolink_link_forever_product_modal.name_help') ?></small>
                    </div>

                    <div class="form-group">
                        <label for="forever_product_description"><i class="fas fa-fw fa-align-left fa-sm text-muted mr-1"></i> <?= l('create_biolink_link_forever_product_modal.description') ?></label>
                        <textarea id="forever_product_description" name="description" class="form-control" rows="2" maxlength="220" data-forever-product-description></textarea>
                        <small class="form-text text-muted"><?= l('create_biolink_link_forever_product_modal.description_help') ?></small>
                    </div>

                    <div class="form-group mb-0" data-forever-product-preview-wrapper style="display:none;">
                        <label class="d-block"><i class="fas fa-fw fa-image fa-sm text-muted mr-1"></i> <?= l('biolink_link.image') ?></label>
                        <img src="" alt="" data-forever-product-preview-image style="max-width: 88px; border-radius: 10px; border: 1px solid #d8dde6;" />
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('global.submit') ?></button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<?php ob_start() ?>
<script>
'use strict';

(() => {
    const modal = document.getElementById('create_biolink_link_forever_product');
    if(!modal) return;

    const selector = modal.querySelector('[data-forever-product-selector]');
    const nameInput = modal.querySelector('[data-forever-product-name]');
    const locationInput = modal.querySelector('[data-forever-product-location-url]');
    const blogPostIdInput = modal.querySelector('[data-forever-product-blog-post-id]');
    const translationKeyInput = modal.querySelector('[data-forever-product-translation-key]');
    const imageUrlInput = modal.querySelector('[data-forever-product-image-url]');
    const descriptionInput = modal.querySelector('[data-forever-product-description]');
    const previewWrapper = modal.querySelector('[data-forever-product-preview-wrapper]');
    const previewImage = modal.querySelector('[data-forever-product-preview-image]');
    const languageMode = modal.querySelector('[data-forever-product-language-mode]');
    const manualLanguage = modal.querySelector('[data-forever-product-language-code]');
    const fallbackLanguage = modal.querySelector('[data-forever-product-fallback-language-code]');
    const nameModeInput = modal.querySelector('[data-forever-product-name-mode]');
    const descriptionModeInput = modal.querySelector('[data-forever-product-description-mode]');
    const manualLanguageWrapper = modal.querySelector('[data-forever-product-manual-language-wrapper]');
    const languageHint = modal.querySelector('[data-forever-product-language-hint]');

    if(!selector || !nameInput || !locationInput || !blogPostIdInput || !translationKeyInput || !imageUrlInput || !descriptionInput || !languageMode || !manualLanguage || !fallbackLanguage || !nameModeInput || !descriptionModeInput) return;

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

    const initializeProductSelector = () => {
        if($(selector).hasClass('select2-hidden-accessible')) {
            return;
        }

        $(selector).select2({
            dropdownParent: $(modal),
            placeholder: <?= json_encode(l('create_biolink_link_forever_product_modal.product_placeholder')) ?>,
            width: '100%',
            minimumResultsForSearch: 0,
            dir: <?= json_encode(l('direction')) ?>
        });
    };

    modal.addEventListener('shown.bs.modal', initializeProductSelector);
})();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<!-- /Custom code: FC-2026-03-09 -->
