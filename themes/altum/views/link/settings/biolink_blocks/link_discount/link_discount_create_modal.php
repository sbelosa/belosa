<?php defined('ALTUMCODE') || die() ?>
<?php
$fcc_discount_tutorial_copy = \Altum\Language::$code === 'hr'
    ? [
        'note' => 'Prije aktivacije ovog bloka zalijepi link izrađen u Forever Link Builderu na Foreverliving.com stranici. Klikovi na ovaj blok ulaze u 15+ / 30d signal za Istaknute aplikacije i pomažu graditi 50+ / 30d status preporučenog sponzora za naslovnicu.',
        'video' => 'Pogledaj video uputu'
    ]
    : [
        'note' => 'Before activating this block, paste the link created in Forever Link Builder on the Foreverliving.com website. Clicks on this block count toward the 15+ / 30d Featured Apps signal and help build the 50+ / 30d recommended sponsor status for the homepage.',
        'video' => 'Watch the video guide'
    ];
?>
<!-- Custom code -->
<div class="modal fade" id="create_biolink_link_discount" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" data-toggle="modal" data-target="#biolink_link_discount_create_modal" data-dismiss="modal" class="btn btn-sm btn-link"><i class="fa fa-fw fa-chevron-circle-left text-muted"></i></button>
                <h5 class="modal-title"><?= l('create_biolink_link_modal.header') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form name="create_biolink_link_discount" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                    <input type="hidden" name="request_type" value="create" />
                    <input type="hidden" name="link_id" value="<?= $data->link->link_id ?>" />
                    <input type="hidden" name="block_type" value="link_discount" />

                    <div class="notification-container"></div>

                    <div class="form-group">
                        <?= l('create_biolink_disount_link_modal.info') ?>
                    </div>

                    <div class="alert alert-primary">
                        <i class="fas fa-fw fa-info-circle mr-2"></i>
                        <?= $fcc_discount_tutorial_copy['note'] ?>
                        <a href="https://www.youtube.com/watch?v=8tBJiDu1EWc" target="_blank" rel="noopener noreferrer" class="alert-link d-inline-block ml-1">
                            <?= $fcc_discount_tutorial_copy['video'] ?>
                        </a>
                    </div>

                    <div class="form-group">
                        <label for="link_location_url"><i class="fa fa-fw fa-link fa-sm text-muted mr-1"></i> <?= l('create_biolink_link_modal.input.location_url') ?></label>
                        <input id="link_location_url" type="text" class="form-control" name="location_url" required="required" maxlength="2048">
                    </div>


                    <div class="form-group">
                        <label for="link_name"><i class="fa fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('create_biolink_link_modal.input.name') ?></label>
                        <input id="link_name" type="text" name="name" maxlength="128" class="form-control" value="<?= l('create_biolink_link_modal.input.discount') ?>" required="required" />
                    </div>

                    <!-- Custom code: FC-2026-02-26: require apply to all products choice on discount block creation -->
                    <div class="form-group">
                        <label><i class="fa fa-fw fa-tags fa-sm text-muted mr-1"></i> <?= l('create_biolink_link_modal.input.apply_to_all_products') ?></label>
                        <div class="row btn-group-toggle m-n2" data-toggle="buttons">
                            <div class="p-2 col-6">
                                <label class="btn btn-light btn-block font-size-small mb-0 text-truncate">
                                    <input type="radio" name="apply_to_all_products" value="1" class="custom-control-input" required="required" />
                                    <?= l('global.yes') ?>
                                </label>
                            </div>

                            <div class="p-2 col-6">
                                <label class="btn btn-light btn-block font-size-small mb-0 text-truncate">
                                    <input type="radio" name="apply_to_all_products" value="0" class="custom-control-input" required="required" />
                                    <?= l('global.no') ?>
                                </label>
                            </div>
                        </div>
                        <small class="form-text text-muted"><?= l('create_biolink_link_modal.input.apply_to_all_products_help') ?></small>
                    </div>
                    <!-- /Custom code: FC-2026-02-26 -->

                    <div class="text-center mt-4">
                        <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('global.submit') ?></button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
<!-- /Custom code -->
