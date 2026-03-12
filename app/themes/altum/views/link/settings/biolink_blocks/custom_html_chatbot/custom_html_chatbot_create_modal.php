<?php defined('ALTUMCODE') || die() ?>
<!-- Custom code -->
<div class="modal fade" id="create_biolink_custom_html_chatbot" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" data-toggle="modal" data-target="#biolink_link_create_modal" data-dismiss="modal" class="btn btn-sm btn-link"><i class="fa fa-fw fa-chevron-circle-left text-muted"></i></button>
                <h5 class="modal-title"><?= l('create_biolink_custom_html_chatbot_modal.header') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form name="create_biolink_custom_html_chatbot" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                    <input type="hidden" name="request_type" value="create" />
                    <input type="hidden" name="link_id" value="<?= $data->link->link_id ?>" />
                    <input type="hidden" name="block_type" value="custom_html_chatbot" />

                    <div class="notification-container"></div>

                    <?php if (\Altum\Authentication::is_admin()): ?>
                        <div class="form-group">
                            <label for="custom_html_chatbot_html"><i class="fa fa-fw fa-code fa-sm text-muted mr-1"></i> <?= l('create_biolink_custom_html_chatbot_modal.html') ?></label>
                            <textarea id="custom_html_chatbot_html" name="html" class="form-control" maxlength="<?= $data->biolink_blocks['custom_html_chatbot']['max_length'] ?>">
                                <script async type='module' src='https://interfaces.zapier.com/assets/web-components/zapier-interfaces/zapier-interfaces.esm.js'></script>
                                <zapier-interfaces-chatbot-embed is-popup='true' chatbot-id='cm8g6mg77000qyrrw89x3vadl'></zapier-interfaces-chatbot-embed>
                            </textarea>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('global.submit') ?></button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- /Custom code -->
