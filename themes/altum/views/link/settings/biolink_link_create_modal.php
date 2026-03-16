<?php defined('ALTUMCODE') || die() ?>

<?php /* Custom code: FC-2026-02-27: premium add-block modal styling */ ?>
<?php ob_start() ?>
<style>
    #biolink_link_create_modal .biolink-create-modal-content {
        border: 1px solid var(--gray-200);
        border-radius: 1rem;
        box-shadow: 0 .75rem 2rem rgba(0, 0, 0, 0.16);
        background: linear-gradient(180deg, #eef2f7, #e6ebf2) !important;
    }

    #biolink_link_create_modal .biolink-create-modal-content .modal-body {
        background: transparent !important;
    }

    #biolink_link_create_modal .biolink-create-search .form-control {
        border-radius: .7rem;
        border-color: #b8c4d4 !important;
        background: #f8fafc !important;
    }

    #biolink_link_create_modal .biolink-block-category-card {
        border: 0;
        border-radius: .8rem;
    }

    #biolink_link_create_modal .biolink-create-block-btn {
        position: relative;
        overflow: hidden;
        min-height: 72px;
        border: 1px solid #b9c5d4 !important;
        border-radius: .75rem;
        background: linear-gradient(135deg, #e9eef5, #dee6f0) !important;
        box-shadow: 0 .2rem .65rem rgba(17, 24, 39, 0.08) !important;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    #biolink_link_create_modal .biolink-create-block-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), transparent);
        opacity: .35;
    }

    #biolink_link_create_modal .biolink-create-block-btn:hover {
        transform: translateY(-1px);
        border-color: var(--primary) !important;
        box-shadow: 0 .35rem 1rem rgba(17, 24, 39, 0.14) !important;
    }

    #biolink_link_create_modal .biolink-create-block-btn.container-disabled .biolink-create-block-title,
    #biolink_link_create_modal .biolink-create-block-btn.container-disabled .biolink-create-block-subtitle {
        text-decoration: line-through;
    }

    #biolink_link_create_modal .biolink-create-block-title,
    #biolink_link_create_modal .biolink-create-block-btn,
    #biolink_link_create_modal .biolink-create-block-btn.btn-light {
        color: #1f2937 !important;
    }

    #biolink_link_create_modal .biolink-create-block-subtitle,
    #biolink_link_create_modal .biolink-create-block-btn .text-muted {
        color: #4b5563 !important;
    }

    .biolink-create-block-btn .biolink-create-block-meta {
        min-width: 0;
    }

    .biolink-create-block-btn .biolink-create-block-title,
    .biolink-create-block-btn .biolink-create-block-subtitle {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    body[data-theme-style='dark'] #biolink_link_create_modal .biolink-create-block-btn {
        background: linear-gradient(135deg, var(--gray-900), var(--gray-800)) !important;
        border-color: var(--gray-600) !important;
    }

    body[data-theme-style='dark'] #biolink_link_create_modal .biolink-create-block-btn:hover {
        border-color: var(--primary) !important;
        box-shadow: 0 .45rem 1.1rem rgba(0, 0, 0, 0.26) !important;
    }

    [data-theme-style='dark'] #biolink_link_create_modal .biolink-block-category-card {
        background: var(--card-dark-bg) !important;
    }

    body[data-theme-style='dark'] #biolink_link_create_modal .biolink-create-modal-content {
        background: var(--gray-900) !important;
        border-color: var(--gray-700) !important;
    }

    body[data-theme-style='dark'] #biolink_link_create_modal .biolink-create-search .form-control {
        background: var(--gray-900);
        border-color: var(--gray-600);
        color: var(--gray-100);
    }

    body[data-theme-style='dark'] #biolink_link_create_modal .biolink-create-block-subtitle {
        color: var(--gray-400) !important;
    }

    body[data-theme-style='dark'] #biolink_link_create_modal .biolink-create-block-title,
    body[data-theme-style='dark'] #biolink_link_create_modal .biolink-create-search .form-control::placeholder,
    body[data-theme-style='dark'] #biolink_link_create_modal .biolink-create-modal-content .modal-title {
        color: var(--gray-100) !important;
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
<?php /* /Custom code: FC-2026-02-27 */ ?>

<div class="modal fade" id="biolink_link_create_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <!-- Custom code: FC-2026-02-27: force modal visual consistency -->
        <div class="modal-content biolink-create-modal-content" style="background: linear-gradient(180deg, #eef2f7, #e6ebf2); border-color: #b9c5d4;">

            <div class="modal-body">
                <div class="d-flex justify-content-between mb-3">
                    <h5 class="modal-title">
                        <i class="fas fa-fw fa-sm fa-circle-plus text-primary mr-2"></i>
						<?= l('biolink_link_create.header') ?>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" title="<?= l('global.close') ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <form action="" method="get" role="form" id="search" class="biolink-create-search">
                    <div class="form-group">
                        <input type="search" name="search" class="form-control form-control-lg" value="" placeholder="<?= l('global.filters.search') ?>" aria-label="<?= l('global.filters.search') ?>" style="background: #f8fafc; border-color: #b8c4d4;" />
                    </div>
                </form>

                <?php foreach(require APP_PATH . 'includes/biolink_blocks_categories.php' as $biolink_block_category_key => $biolink_block_category): ?>
                    <?php $enabled_blocks_html = ''; ?>

					<?php foreach(require APP_PATH . 'includes/enabled_biolink_blocks.php' as $key => $value): ?>

                        <?php /* Custom code: FC-2026-03-16: hide deprecated Forever regional blocks from create modal */ ?>
                        <?php if(in_array($key, ['link_forever_living_bih', 'link_forever_living_alb_kosovo'])) continue ?>
                        <?php /* /Custom code: FC-2026-03-16 */ ?>

						<?php if($value['category'] != $biolink_block_category_key) continue ?>

                        <?php ob_start() ?>
                        <?php /* Custom code: FC-2026-03-06: mark unavailable blocks safely based on plan */ ?>
                        <?php $enabled_biolink_blocks = (object) ($this->user->plan_settings->enabled_biolink_blocks ?? []); ?>
                        <?php $is_block_enabled_for_plan = (bool) ($enabled_biolink_blocks->{$key} ?? false); ?>
                        <?php /* Custom code: FC-2026-02-27: premium block option cards */ ?>
                        <div class="col-12 col-lg-6 p-3" data-block-category="<?= $value['category'] ?>" data-block-id="<?= $key ?>" data-block-name="<?= l('link.biolink.blocks.' . $key) ?>" <?= $is_block_enabled_for_plan ? null : get_plan_feature_disabled_info() ?>>
                            <button
                                    type="button"
                                    data-dismiss="modal"
                                    data-toggle="modal"
                                    data-target="#create_biolink_<?= $key ?>"
                                    data-tooltip
                                    title="<?= l('biolink_' . $key . '.subheader') ?>"
                                class="btn btn-light btn-block btn-lg text-left d-flex align-items-center biolink-create-block-btn <?= $is_block_enabled_for_plan ? null : 'container-disabled' ?>"
                                style="border-color:#b9c5d4;background:linear-gradient(135deg,#e9eef5,#dee6f0);color:#1f2937;"
                            >
                                <?php if($key == 'custom_html_chatbot'): ?>
                                    <img
                                        src="<?= SITE_URL . 'themes/altum/assets/images/sovica.png' ?>"
                                        alt=""
                                        class="mr-2"
                                        style="width: 28px; height: 28px; object-fit: contain;"
                                        onerror="this.onerror=null;this.src='<?= SITE_URL . UPLOADS_URL_PATH . 'ai-chat/sovica.png' ?>';"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                <?php elseif($key == 'custom_html_chatbot_pets'): ?>
                                    <span class="fa-stack mr-2" style="font-size: 1.12rem;">
                                        <i class="fas fa-circle fa-stack-2x" style="color: #5f3dc4"></i>
                                        <i class="fas fa-paw fa-stack-1x fa-inverse"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="fa-stack fa-stack-small mr-2">
                                        <i class="fas fa-circle fa-stack-2x" style="color: <?= $data->biolink_blocks[$key]['color'] ?>"></i>
                                        <i class="<?= $data->biolink_blocks[$key]['icon'] ?> fa-stack-1x fa-inverse"></i>
                                    </span>
                                <?php endif ?>

								<span class="biolink-create-block-meta d-flex flex-column">
									<span class="font-weight-500 biolink-create-block-title"><?= l('link.biolink.blocks.' . $key) ?></span>
									<small class="text-muted biolink-create-block-subtitle"><?= l('biolink_' . $key . '.subheader') ?></small>
								</span>
                            </button>
                        </div>
						<?php /* /Custom code: FC-2026-02-27 */ ?>
							<?php $enabled_blocks_html .= ob_get_clean(); ?>
                        <?php /* /Custom code: FC-2026-03-06 */ ?>
					<?php endforeach ?>

					<?php if($enabled_blocks_html): ?>
                        <div class="mb-4" data-category="<?= $biolink_block_category_key ?>">
                            <div class="biolink-block-category-card card border-0 mb-3" style="--card-dark-bg: <?= $biolink_block_category['dark_background'] ?>;background: <?= $biolink_block_category['background_color'] ?>; color: <?= $biolink_block_category['color'] ?>;">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="h6"><?= l('biolink_link_create.' . $biolink_block_category_key) ?></span>
                                        <p class="small mb-0"><?= l('biolink_link_create.' . $biolink_block_category_key . '_subheader') ?></p>
                                    </div>

                                    <div>
                                        <i class="fas fa-fw fa-lg <?= $biolink_block_category['icon'] ?>"></i>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
								<?= $enabled_blocks_html ?>
                            </div>
                        </div>
					<?php endif ?>
				<?php endforeach ?>
            </div>
        </div>
        <!-- /Custom code: FC-2026-02-27 -->
    </div>
</div>

<?php ob_start() ?>
<script>
    'use strict';

    /* On modal show */
    $('#biolink_link_create_modal').on('shown.bs.modal', event => {
        document.querySelector('#biolink_link_create_modal input[name="search"]').focus();
    });

    document.querySelector('#search').addEventListener('submit', event => {
        event.preventDefault();
    });

    let blocks = [];
    document.querySelectorAll('[data-block-id]').forEach(element => blocks.push({
        id: element.getAttribute('data-block-id'),
        name: element.getAttribute('data-block-name').toLowerCase(),
        category: element.getAttribute('data-block-category').toLowerCase(),
    }));

    ['keyup', 'change', 'search'].forEach(event_key => document.querySelector('#biolink_link_create_modal input').addEventListener(event_key, event => {
        let string = event.currentTarget.value.toLowerCase();

        /* Hide header sections */
        document.querySelectorAll('[data-category]').forEach(element => {
            if(string.length) {
                element.classList.add('d-none');
            } else {
                element.classList.remove('d-none');
            }
        });

        for(let block of blocks) {
            if(block.name.includes(string)) {
                document.querySelector(`[data-block-id="${block.id}"]`).classList.remove('d-none');
                document.querySelector(`[data-category="${block.category}"]`).classList.remove('d-none');
            } else {
                document.querySelector(`[data-block-id="${block.id}"]`).classList.add('d-none');
            }
        }
    }));
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
