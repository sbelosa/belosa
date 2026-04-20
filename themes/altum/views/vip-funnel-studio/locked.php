<?php defined('ALTUMCODE') || die() ?>

<?php $locked_copy = $data->access->locked_copy ?? vip_funnel_get_locked_copy('testing'); ?>
<?php $vip_funnel_rollout_label = l('vip_funnel.rollout.' . (($data->access->rollout_mode ?? 'testing_visible_locked'))); ?>

<div class="container">
    <style>
        .vip-funnel-locked .card {
            border-radius: 1.2rem;
            border: 1px solid rgba(148, 163, 184, 0.18);
            overflow: hidden;
            color: #eef4ff;
            background: linear-gradient(160deg, rgba(24, 33, 48, 0.97), rgba(10, 15, 23, 0.99));
        }

        .vip-funnel-locked .card h1,
        .vip-funnel-locked .card h2,
        .vip-funnel-locked .card h3,
        .vip-funnel-locked .card h4,
        .vip-funnel-locked .card h5,
        .vip-funnel-locked .card h6,
        .vip-funnel-locked .card .h1,
        .vip-funnel-locked .card .h2,
        .vip-funnel-locked .card .h3,
        .vip-funnel-locked .card .h4,
        .vip-funnel-locked .card .h5,
        .vip-funnel-locked .card .h6,
        .vip-funnel-locked .card .text-white {
            color: #eef4ff !important;
        }

        .vip-funnel-locked .card p,
        .vip-funnel-locked .card .text-muted,
        .vip-funnel-locked .card .small {
            color: rgba(206, 217, 231, 0.82) !important;
        }

        .vip-funnel-locked .vip-funnel-locked__hero {
            background:
                radial-gradient(700px 240px at -10% -30%, rgba(245, 158, 11, 0.18), transparent 60%),
                radial-gradient(640px 220px at 105% 0%, rgba(59, 130, 246, 0.16), transparent 56%),
                linear-gradient(160deg, rgba(20, 27, 39, 0.98), rgba(9, 14, 23, 0.99));
            color: #eef4ff;
        }

        .vip-funnel-locked .vip-funnel-chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .45rem .8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            font-weight: 700;
            font-size: .82rem;
            color: #f8fbff;
        }
    </style>

    <div class="vip-funnel-locked">
        <div class="card vip-funnel-locked__hero mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start" style="gap: 1rem;">
                    <div class="pr-lg-4">
                        <div class="small text-uppercase font-weight-bold mb-2" style="letter-spacing: .12em; color: #f9d27d;">
                            <?= l('vip_funnel.menu') ?>
                        </div>
                        <h1 class="h2 text-white mb-3"><?= $locked_copy->title ?></h1>
                        <p class="mb-0" style="max-width: 760px; color: rgba(238, 244, 255, 0.88);">
                            <?= $locked_copy->message ?>
                        </p>
                    </div>

                    <div>
                        <span class="vip-funnel-chip">
                            <i class="fas fa-fw fa-lock"></i>
                            <?= $locked_copy->badge ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8 mb-4 mb-lg-0">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3 text-white"><?= l('vip_funnel.locked.what_happens_next') ?></h2>
                        <p class="text-muted mb-4"><?= $locked_copy->footnote ?></p>

                        <div class="d-flex flex-wrap" style="gap: .75rem;">
                            <a href="<?= $data->dashboard_url ?>" class="btn btn-primary"><?= l('vip_funnel.locked.back_dashboard') ?></a>
                            <a href="<?= $data->account_plan_url ?>" class="btn btn-outline-secondary"><?= l('vip_funnel.locked.open_plan') ?></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-body p-4">
                        <div class="small text-uppercase font-weight-bold text-muted mb-2"><?= l('vip_funnel.locked.current_mode') ?></div>
                        <div class="mb-3 text-white"><?= $vip_funnel_rollout_label ?></div>

                        <div class="small text-uppercase font-weight-bold text-muted mb-2"><?= l('vip_funnel.locked.current_scope') ?></div>
                        <div class="mb-3 text-white"><?= l('vip_funnel.locked.current_scope_value') ?></div>

                        <div class="small text-uppercase font-weight-bold text-muted mb-2"><?= l('vip_funnel.locked.future_scope') ?></div>
                        <div class="text-white"><?= l('vip_funnel.locked.future_scope_value') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
