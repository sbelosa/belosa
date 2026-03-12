<?php defined('ALTUMCODE') || die() ?>

<div class="modal fade" id="website_install_code_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="modal-title">
                        <i class="fas fa-fw fa-sm fa-code text-dark mr-2"></i>
                        <?= l('website_install_code_modal.header') ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <h3 class="h6 mt-5"><?= l('website_install_code_modal.first.header') ?></h3>
                <p id="file_download_text" class="text-muted"></p>

                <div class="mt-4">
                    <a href="" id="file_download_url" target="_blank" class="btn btn-lg btn-block btn-primary"><?= l('global.download') ?></a>
                </div>

                <h3 class="h6 mt-5"><?= l('website_install_code_modal.second.header') ?></h3>
                <p class="text-muted mb-3"><?= l('website_install_code_modal.second.subheader') ?></p>

                <pre id="pixel_key_html" class="pre-custom rounded"></pre>

                <div class="mt-4">
                    <button type="button" class="btn btn-lg btn-block btn-primary" data-clipboard-target="#pixel_key_html" data-copied="<?= l('global.clipboard_copied') ?>"><?= l('global.clipboard_copy') ?></button>
                </div>
            </div>

        </div>
    </div>
</div>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL ?>js/libraries/clipboard.min.js?v=<?= PRODUCT_CODE ?>"></script>

<script>
    'use strict';
    
    /* On modal show */
    $('#website_install_code_modal').on('show.bs.modal', event => {
        let website_id = $(event.relatedTarget).data('website-id');
        let pixel_key = $(event.relatedTarget).data('pixel-key');
        let base_url = $(event.relatedTarget).data('base-url');
        let file_name = $(event.relatedTarget).data('file-name');
        let host = $(event.relatedTarget).data('host');
        let path = $(event.relatedTarget).data('path');

        /* Display the dynamic file download */
        let file_download_text_default = <?= json_encode(l('website_install_code_modal.first.subheader')) ?>;
        let file_download_text_element = event.currentTarget.querySelector('#file_download_text');
        file_download_text_element.innerHTML = file_download_text_default.replace('%1$s', file_name);
        file_download_text_element.innerHTML = file_download_text_element.innerHTML.replace('%2$s', host + path + '/' + file_name);

        event.currentTarget.querySelector('#file_download_url').href = `${site_url}website-sw-code/${website_id}`;

        /* Prepare and display the pixel code */
        let pixel_key_html = `&lt;!-- Pixel Code - ${base_url} --&gt;
&lt;script defer src="${base_url}pixel/${pixel_key}"&gt;&lt;/script&gt;
&lt;!-- END Pixel Code --&gt;`;

        $(event.currentTarget).find('pre').html(pixel_key_html);

        new ClipboardJS('[data-clipboard-target]');

        /* Handle on click button */
        let copy_button = $(event.currentTarget).find('[data-clipboard-target]');
        let initial_text = copy_button.text();

        copy_button.on('click', () => {
            copy_button.text(copy_button.data('copied'));

            setTimeout(() => {
                copy_button.text(initial_text);
            }, 2500);
        });
    });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
