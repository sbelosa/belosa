<?php defined('ALTUMCODE') || die() ?>
<?php $default_whatsapp_phone = $data->default_whatsapp_phone ?? ''; ?>
<!-- Custom code -->
<div class="modal fade" id="create_biolink_custom_html_whatsapp" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" data-toggle="modal" data-target="#biolink_link_create_modal" data-dismiss="modal" class="btn btn-sm btn-link"><i class="fa fa-fw fa-chevron-circle-left text-muted"></i></button>
                <h5 class="modal-title"><?= l('create_biolink_custom_html_whatsapp_modal.header') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form name="create_biolink_custom_html_whatsapp" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                    <input type="hidden" name="request_type" value="create" />
                    <input type="hidden" name="link_id" value="<?= $data->link->link_id ?>" />
                    <input type="hidden" name="block_type" value="custom_html_whatsapp" />

                    <div class="notification-container"></div>

                    <div class="form-group">
                        <label for="custom_html_whatsapp_title"><i class="fa fa-fw fa-heading fa-sm text-muted mr-1"></i> <?= l('link.biolink.blocks.heading') ?></label>
                        <input id="custom_html_whatsapp_title" name="title" value="<?= l('create_biolink_custom_html_whatsapp_modal.button') ?>" class="form-control" maxlength="256" required="required">
                    </div>
                    <div class="form-group">
                        <label for="custom_html_whatsapp_phone"><i class="fa fa-fw fa-mobile fa-sm text-muted mr-1"></i> <?= l('account.billing.phone') ?></label>
                        <input id="custom_html_whatsapp_phone" name="phone" value="<?= $default_whatsapp_phone ?>" class="form-control" required="required" maxlength="256" placeholder="385911234567">
                        <small class="form-text text-muted"><?= l('create_biolink_custom_html_whatsapp_modal.phone_help') ?></small>
                    </div>
                    <div class="form-group">
                        <label for="custom_html_whatsapp_message"><i class="fa fa-fw fa-comment fa-sm text-muted mr-1"></i> <?= l('create_biolink_custom_html_whatsapp_modal.html') ?></label>
                        <textarea id="custom_html_whatsapp_message" name="message" class="form-control"  required="required" maxlength="<?= $data->biolink_blocks['custom_html_whatsapp']['max_length'] ?>"><?= l('create_biolink_custom_html_whatsapp_modal.message') ?></textarea>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('global.submit') ?></button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- /Custom code -->
