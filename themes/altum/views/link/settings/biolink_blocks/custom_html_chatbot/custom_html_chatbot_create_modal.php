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
                    <input type="hidden" name="html" value="" />

                    <div class="notification-container"></div>

                    <div class="alert alert-info mb-0">
                        <div class="font-weight-bold mb-1">FCC AI popup</div>
                        <div class="small">
                            Ovaj blok više ne koristi vanjski embed kod. Nakon spremanja na aplikaciji se automatski prikazuje novi
                            <strong>ChatExtreme AI za ljude</strong> popup. Jezik, ton i model podešavaš kasnije u
                            <strong>FCC AI</strong> sučelju.
                        </div>
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
