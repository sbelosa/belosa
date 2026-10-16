<?php defined('ALTUMCODE') || die() ?>

<div class="modal fade" id="create_biolink_custom_html" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" data-toggle="modal" data-target="#biolink_link_create_modal" data-dismiss="modal" class="btn btn-sm btn-link"><i class="fas fa-fw fa-chevron-circle-left text-muted"></i></button>
                <h5 class="modal-title"><?= l('biolink_custom_html.header') ?></h5>
                <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form name="create_biolink_custom_html" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                    <input type="hidden" name="request_type" value="create" />
                    <input type="hidden" name="link_id" value="<?= $data->link->link_id ?>" />
                    <input type="hidden" name="block_type" value="custom_html" />

                    <div class="notification-container"></div>

                    <div class="form-group">
                        <label for="custom_html_html"><i class="fas fa-fw fa-code fa-sm text-muted mr-1"></i> <?= l('biolink_custom_html.html') ?></label>
                        <!-- Custom code: FC-2026-10-16: preserve HTML source and validate Unicode character limits. -->
                        <textarea id="custom_html_html" name="html" class="form-control" data-limit-message="<?= htmlspecialchars(\Altum\Helpers\FccCustomHtml::message('length'), ENT_QUOTES, 'UTF-8') ?>" oninput="this.nextElementSibling.querySelector('[data-html-count]').textContent = Array.from(this.value).length.toLocaleString(); this.setCustomValidity(Array.from(this.value).length > <?= \Altum\Helpers\FccCustomHtml::MAX_CHARACTERS ?> ? this.dataset.limitMessage : '')"></textarea>
                        <small class="form-text text-muted"><span data-html-count aria-live="polite">0</span> / 50.000. <?= htmlspecialchars(\Altum\Helpers\FccCustomHtml::message('hint'), ENT_QUOTES, 'UTF-8') ?></small>
                        <!-- /Custom code: FC-2026-10-16 -->
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
