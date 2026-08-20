<?php defined('ALTUMCODE') || die() ?>
<?php $vip_funnel_options = function_exists('vip_funnel_get_user_funnel_select_options') ? vip_funnel_get_user_funnel_select_options((int) ($data->link->user_id ?? $this->user->user_id ?? 0)) : []; ?>

<div class="modal fade" id="create_biolink_vip_funnel_hub" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" data-toggle="modal" data-target="#biolink_link_create_modal" data-dismiss="modal" class="btn btn-sm btn-link"><i class="fas fa-fw fa-chevron-circle-left text-muted"></i></button>
                <h5 class="modal-title"><?= l('biolink_vip_funnel_hub.header') ?></h5>
                <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form name="create_biolink_vip_funnel_hub" method="post" role="form">
                    <input type="hidden" name="token" value="<?= \Altum\Csrf::get() ?>" required="required" />
                    <input type="hidden" name="request_type" value="create" />
                    <input type="hidden" name="link_id" value="<?= $data->link->link_id ?>" />
                    <input type="hidden" name="block_type" value="vip_funnel_hub" />

                    <div class="notification-container"></div>

                    <div class="form-group">
                        <label for="vip_funnel_hub_name"><i class="fas fa-fw fa-signature fa-sm text-muted mr-1"></i> <?= l('biolink_link.name') ?></label>
                        <input id="vip_funnel_hub_name" type="text" name="name" maxlength="128" class="form-control" required="required" />
                    </div>

                    <div class="form-group">
                        <label for="vip_funnel_hub_vip_funnel_id"><i class="fas fa-fw fa-diagram-project fa-sm text-muted mr-1"></i> Funnel koji se otvara</label>
                        <?php /* Custom code: FC-2026-08-20: require an exact VIP Funnel hub target */ ?>
                        <select id="vip_funnel_hub_vip_funnel_id" name="vip_funnel_id" class="custom-select" required="required">
                            <option value="" selected="selected" disabled="disabled">Odaberi točan funnel</option>
                            <?php foreach($vip_funnel_options as $option): ?>
                                <option value="<?= (int) $option['id'] ?>">
                                    <?= htmlspecialchars((string) $option['name'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars(mb_strtoupper((string) ($option['status'] ?? '')), ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars(mb_strtoupper((string) ($option['visibility_mode'] ?? '')), ENT_QUOTES, 'UTF-8') ?> — #<?= (int) $option['id'] ?> /<?= htmlspecialchars((string) $option['slug'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <small class="form-text text-muted">Blok otvara isključivo ovdje odabrani funnel; nema automatskog odabira najstarijeg funnel-a.</small>
                        <?php /* /Custom code: FC-2026-08-20 */ ?>
                    </div>

                    <p class="small text-muted mb-0">
                        <i class="fas fa-fw fa-sm fa-circle-info mr-1"></i>
                        <?= l('biolink_vip_funnel_hub.subheader') ?>
                    </p>

                    <div class="text-center mt-4">
                        <button type="submit" name="submit" class="btn btn-block btn-primary" data-is-ajax><?= l('link.biolink.create_block') ?></button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
