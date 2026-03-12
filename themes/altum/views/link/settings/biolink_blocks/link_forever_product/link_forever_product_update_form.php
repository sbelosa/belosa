<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-03-09: reuse base Forever Shop styling/options and extend with product picker UX -->
<?php require THEME_PATH . 'views/link/settings/biolink_blocks/link_forever_shop/link_forever_shop_update_form.php' ?>

<div class="card bg-gray-50 border-0 p-3 mb-3">
    <h6 class="mb-2"><i class="fas fa-fw fa-box-open text-muted mr-1"></i> <?= l('create_biolink_link_forever_product_modal.product_section') ?></h6>
    <p class="small text-muted mb-3"><?= l('create_biolink_link_forever_product_modal.product_section_help') ?></p>

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
                <option
                    value="<?= $blog_product->blog_post_id ?>"
                    data-title="<?= htmlspecialchars($blog_product->title, ENT_QUOTES) ?>"
                    data-description="<?= htmlspecialchars($blog_product->description ?? '', ENT_QUOTES) ?>"
                    data-url="<?= htmlspecialchars($blog_product->blog_url, ENT_QUOTES) ?>"
                    data-image-url="<?= htmlspecialchars($blog_product->image_url ?? '', ENT_QUOTES) ?>"
                    <?= (int) ($row->settings->product_blog_post_id ?? 0) === (int) $blog_product->blog_post_id ? 'selected="selected"' : null ?>
                >
                    <?= $blog_product->title ?>
                </option>
            <?php endforeach ?>
        </select>
        <small class="form-text text-muted"><?= l('create_biolink_link_forever_product_modal.product_help') ?></small>
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
<input type="hidden" name="product_image_url" value="<?= $row->settings->product_image_url ?? '' ?>" data-forever-product-image-url form="<?= 'update_biolink_block_' . $row->biolink_block_id ?>" />

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
    const descriptionInput = document.querySelector(`textarea[name="description"][form="${formId}"]`);
    const blogPostIdInput = document.querySelector(`input[name="product_blog_post_id"][form="${formId}"]`);
    const imageUrlInput = document.querySelector(`input[name="product_image_url"][form="${formId}"]`);
    const previewWrapper = selector.closest('.card')?.querySelector('[data-forever-product-preview-wrapper]');
    const previewImage = selector.closest('.card')?.querySelector('[data-forever-product-preview-image]');

    const applySelectedProduct = () => {
        const option = selector.options[selector.selectedIndex];
        if(!option || !option.value) {
            if(blogPostIdInput) blogPostIdInput.value = 0;
            if(imageUrlInput) imageUrlInput.value = '';
            if(previewWrapper) previewWrapper.style.display = 'none';
            return;
        }

        const selectedTitle = option.getAttribute('data-title') || '';
        const selectedDescription = option.getAttribute('data-description') || '';
        const selectedUrl = option.getAttribute('data-url') || '';
        const selectedImageUrl = option.getAttribute('data-image-url') || '';

        if(blogPostIdInput) blogPostIdInput.value = option.value;
        if(imageUrlInput) imageUrlInput.value = selectedImageUrl;
        if(locationInput) locationInput.value = selectedUrl;
        if(nameInput) nameInput.value = selectedTitle;
        if(descriptionInput) descriptionInput.value = selectedDescription;

        if(previewWrapper && previewImage) {
            if(selectedImageUrl) {
                previewImage.src = selectedImageUrl;
                previewWrapper.style.display = '';
            } else {
                previewImage.src = '';
                previewWrapper.style.display = 'none';
            }
        }
    };

    if(blockTypeInput) {
        blockTypeInput.value = 'link_forever_product';
    }

    if(iconInput) {
        iconInput.value = '';
        const iconGroup = iconInput.closest('.form-group');
        if(iconGroup) {
            iconGroup.style.display = 'none';
        }
    }

    selector.addEventListener('change', applySelectedProduct);

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
