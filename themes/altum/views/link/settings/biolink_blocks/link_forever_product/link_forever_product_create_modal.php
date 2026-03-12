<?php defined('ALTUMCODE') || die() ?>

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
                    <input type="hidden" name="product_image_url" value="" data-forever-product-image-url />

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
                                >
                                    <?= $blog_product->title ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <small class="form-text text-muted"><?= l('create_biolink_link_forever_product_modal.product_help') ?></small>
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
    const imageUrlInput = modal.querySelector('[data-forever-product-image-url]');
    const descriptionInput = modal.querySelector('[data-forever-product-description]');
    const previewWrapper = modal.querySelector('[data-forever-product-preview-wrapper]');
    const previewImage = modal.querySelector('[data-forever-product-preview-image]');

    if(!selector || !nameInput || !locationInput || !blogPostIdInput || !imageUrlInput || !descriptionInput) return;

    const applySelectedProduct = () => {
        const option = selector.options[selector.selectedIndex];
        if(!option || !option.value) {
            blogPostIdInput.value = 0;
            locationInput.value = '';
            imageUrlInput.value = '';
            if(previewWrapper) previewWrapper.style.display = 'none';
            return;
        }

        const selectedTitle = option.getAttribute('data-title') || '';
        const selectedDescription = option.getAttribute('data-description') || '';
        const selectedUrl = option.getAttribute('data-url') || '';
        const selectedImageUrl = option.getAttribute('data-image-url') || '';

        blogPostIdInput.value = option.value;
        locationInput.value = selectedUrl;
        imageUrlInput.value = selectedImageUrl;

        nameInput.value = selectedTitle;
        descriptionInput.value = selectedDescription;

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

    selector.addEventListener('change', applySelectedProduct);

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
