<?php defined('ALTUMCODE') || die() ?>

<div class="modal fade" id="create_biolink_paragraph" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" data-toggle="modal" data-target="#biolink_link_create_modal" data-dismiss="modal" class="btn btn-sm btn-link"><i class="fas fa-fw fa-chevron-circle-left text-muted"></i></button>
                <h5 class="modal-title"><?= l('biolink_paragraph.header') ?></h5>
                <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form name="create_biolink_text" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                    <input type="hidden" name="request_type" value="create" />
                    <input type="hidden" name="link_id" value="<?= $data->link->link_id ?>" />
                    <input type="hidden" name="block_type" value="paragraph" />

                    <div class="notification-container"></div>

                    <div class="form-group">
                        <label for="paragraph_text"><i class="fas fa-fw fa-paragraph fa-sm text-muted mr-1"></i> <?= l('biolink_link.text') ?></label>
                        <textarea id="paragraph_text" class="form-control quilljs" name="text" maxlength="10000" data-paragraph-rich-text><p class="ql-align-center">&nbsp;</p></textarea>
                    </div>

                    <p class="small text-muted"><i class="fas fa-fw fa-sm fa-circle-info mr-1"></i> <?= l('link.create_info') ?></p>

                    <div class="text-center mt-4">
                        <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('link.biolink.create_block') ?></button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>


<?php ob_start() ?>
<link href="<?= ASSETS_FULL_URL . 'css/libraries/quill.snow.css?v=' . PRODUCT_CODE ?>" rel="stylesheet" media="screen,print">
<?php \Altum\Event::add_content(ob_get_clean(), 'head', 'quilljs') ?>

<?php ob_start() ?>
<script src="<?= ASSETS_FULL_URL . 'js/libraries/quill.min.js?v=' . PRODUCT_CODE ?>"></script>

<script>
    'use strict';
    window.initSharedQuillEditors = window.initSharedQuillEditors || (root => {
        if(typeof Quill === 'undefined') {
            return;
        }

        const size_whitelist = ['10px', '12px', '14px', '16px', '18px', '20px', '22px', '24px'];
        const Size = Quill.import('attributors/style/size');
        Size.whitelist = size_whitelist;
        Quill.register(Size, true);

        if(!document.getElementById('shared-quill-editor-sizes')) {
            const style = document.createElement('style');
            style.id = 'shared-quill-editor-sizes';
            style.textContent = `
                .ql-snow .ql-picker.ql-size { width: 82px; }
                .ql-snow .ql-picker.ql-size .ql-picker-label::before,
                .ql-snow .ql-picker.ql-size .ql-picker-item::before { content: '16 px'; }
                .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="10px"]::before,
                .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="10px"]::before { content: '10 px'; }
                .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="12px"]::before,
                .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="12px"]::before { content: '12 px'; }
                .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="14px"]::before,
                .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="14px"]::before { content: '14 px'; }
                .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="16px"]::before,
                .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="16px"]::before { content: '16 px'; }
                .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="18px"]::before,
                .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="18px"]::before { content: '18 px'; }
                .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="20px"]::before,
                .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="20px"]::before { content: '20 px'; }
                .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="22px"]::before,
                .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="22px"]::before { content: '22 px'; }
                .ql-snow .ql-picker.ql-size .ql-picker-label[data-value="24px"]::before,
                .ql-snow .ql-picker.ql-size .ql-picker-item[data-value="24px"]::before { content: '24 px'; }
            `;
            document.head.appendChild(style);
        }

        (root || document).querySelectorAll('textarea.quilljs').forEach(textarea_element => {
            if(textarea_element.dataset.quilljsInitialized) {
                return;
            }

            textarea_element.dataset.quilljsInitialized = 'true';
            textarea_element.style.display = 'none';

            const quill_container = document.createElement('div');
            quill_container.style.resize = 'vertical';
            quill_container.style.overflow = 'auto';

            textarea_element.parentNode.insertBefore(quill_container, textarea_element.nextSibling);

            const quill_editor = new Quill(quill_container, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ size: size_whitelist }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ color: [] }, { background: [] }],
                        [{ align: [] }],
                        ['link'],
                        ['clean']
                    ]
                }
            });

            textarea_element.sharedQuillEditor = quill_editor;
            quill_editor.root.innerHTML = textarea_element.value || '';

            const sync_content = () => {
                textarea_element.value = quill_editor.root.innerHTML;
                textarea_element.dispatchEvent(new Event('input', {bubbles: true}));
                textarea_element.dispatchEvent(new Event('change', {bubbles: true}));
            };

            textarea_element.sharedQuillSyncContent = sync_content;
            quill_editor.on('text-change', sync_content);
            textarea_element.closest('form')?.addEventListener('submit', sync_content);
            sync_content();
        });
    });

    window.initSharedQuillEditors(document);
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript', 'quilljs') ?>
