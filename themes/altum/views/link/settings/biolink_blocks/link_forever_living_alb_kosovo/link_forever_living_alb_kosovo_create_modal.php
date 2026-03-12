<?php defined('ALTUMCODE') || die() ?>
<!-- Custom code: FC-2026-03-05: Albania/Kosovo Forever Living create modal -->
<div class="modal fade" id="create_biolink_link_forever_living_alb_kosovo" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" data-toggle="modal" data-target="#biolink_link_create_modal" data-dismiss="modal" class="btn btn-sm btn-link"><i class="fa fa-fw fa-chevron-circle-left text-muted"></i></button>
                <h5 class="modal-title"><?= l('create_biolink_link_modal.header') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form name="create_biolink_link_forever_living_alb_kosovo" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                    <input type="hidden" name="request_type" value="create" />
                    <input type="hidden" name="link_id" value="<?= $data->link->link_id ?>" />
                    <input type="hidden" name="block_type" value="link_forever_living_alb_kosovo" />
                    <input type="hidden" name="location_url" value="https://www.foreveralbania.com/" />

                    <div class="notification-container"></div>

                    <div class="form-group">
                        <?= l('create_biolink_link_forever_living_alb_kosovo_modal.info') ?>
                    </div>

                    <div class="form-group">
                        <label for="link_name"><i class="fa fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('biolink_link.name') ?></label>
                        <input id="link_name" type="text" name="name" maxlength="128" class="form-control" value="<?= l('create_biolink_link_modal.input.forever_living_albania_kosovo') ?>" required="required" />
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('global.submit') ?></button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- /Custom code: FC-2026-03-05 -->
