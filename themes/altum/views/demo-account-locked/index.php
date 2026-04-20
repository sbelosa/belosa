<?php defined('ALTUMCODE') || die() ?>

<?php $lock = $data->lock ?? null; ?>
<?php if(!$lock): return; endif ?>

<div class="container">
    <style>
        .vip-demo-lock {
            color: #eef5ff;
        }

        .vip-demo-lock__hero,
        .vip-demo-lock__panel {
            border-radius: 1.35rem;
            border: 1px solid rgba(98, 197, 183, 0.22);
            background:
                radial-gradient(720px 260px at -10% -20%, rgba(245, 158, 11, 0.18), transparent 58%),
                radial-gradient(640px 260px at 105% 0%, rgba(45, 212, 191, 0.16), transparent 60%),
                linear-gradient(160deg, rgba(21, 30, 43, 0.98), rgba(9, 14, 23, 0.99));
            box-shadow: 0 26px 60px rgba(3, 9, 18, 0.24);
        }

        .vip-demo-lock__hero p,
        .vip-demo-lock__panel p,
        .vip-demo-lock__panel li {
            color: rgba(233, 242, 255, 0.84);
        }

        .vip-demo-lock__hero h1,
        .vip-demo-lock__hero h2,
        .vip-demo-lock__panel h1,
        .vip-demo-lock__panel h2,
        .vip-demo-lock__panel h3 {
            color: #f5fbff !important;
        }

        .vip-demo-lock .vip-demo-lock__btn-whatsapp {
            border-color: rgba(72, 255, 132, 0.82);
            color: #77f8a2;
            background: rgba(10, 24, 16, 0.42);
        }

        .vip-demo-lock .vip-demo-lock__btn-whatsapp:hover,
        .vip-demo-lock .vip-demo-lock__btn-whatsapp:focus {
            border-color: #77f8a2;
            color: #082014;
            background: #77f8a2;
            box-shadow: 0 10px 24px rgba(76, 248, 157, 0.22);
        }

        .vip-demo-lock .vip-demo-lock__btn-secondary {
            border-color: rgba(182, 201, 224, 0.28);
            color: #f2f7ff;
            background: rgba(255, 255, 255, 0.08);
        }

        .vip-demo-lock .vip-demo-lock__btn-secondary:hover,
        .vip-demo-lock .vip-demo-lock__btn-secondary:focus {
            border-color: rgba(112, 226, 214, 0.68);
            color: #f8fcff;
            background: rgba(91, 211, 197, 0.18);
            box-shadow: 0 12px 28px rgba(56, 189, 177, 0.18);
        }

        .vip-demo-lock__chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            padding: .46rem .82rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fbff;
            font-size: .8rem;
            font-weight: 700;
        }

        .vip-demo-lock__feature-list {
            margin: 0;
            padding-left: 1.1rem;
        }

        .vip-demo-lock__feature-list li + li {
            margin-top: .75rem;
        }
    </style>

    <div class="vip-demo-lock">
        <div class="vip-demo-lock__hero p-4 p-lg-5 mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start" style="gap: 1rem;">
                <div class="pr-lg-4">
                    <div class="small text-uppercase font-weight-bold mb-2" style="letter-spacing: .12em; color: #ffd27a;">
                        <?= htmlspecialchars((string) $lock->eyebrow, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <h1 class="h2 text-white mb-3"><?= htmlspecialchars((string) $lock->title, ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="mb-0" style="max-width: 880px;">
                        <?= htmlspecialchars((string) $lock->message, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>

                <div class="d-flex flex-column align-items-lg-end" style="gap: .75rem;">
                    <span class="vip-demo-lock__chip">
                        <i class="fas fa-fw fa-lock"></i>
                        <?= htmlspecialchars((string) $lock->badge, ENT_QUOTES, 'UTF-8') ?>
                    </span>

                    <?php if(!empty($lock->owner_name)): ?>
                        <span class="vip-demo-lock__chip">
                            <i class="fas fa-fw fa-user-tie"></i>
                            <?= htmlspecialchars((string) $lock->owner_name, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-7 mb-4 mb-lg-0">
                <div class="vip-demo-lock__panel p-4 h-100">
                    <div class="small text-uppercase font-weight-bold mb-3" style="letter-spacing: .1em; color: #ffd27a;">
                        <?= htmlspecialchars((string) $lock->offer_title, ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <?php if(!empty($lock->features) && is_array($lock->features)): ?>
                        <ul class="vip-demo-lock__feature-list">
                            <?php foreach($lock->features as $feature): ?>
                                <li><?= htmlspecialchars((string) $feature, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach ?>
                        </ul>
                    <?php endif ?>

                    <?php if(!empty($lock->footnote)): ?>
                        <p class="mt-4 mb-0"><?= htmlspecialchars((string) $lock->footnote, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif ?>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="vip-demo-lock__panel p-4 h-100">
                    <div class="small text-uppercase font-weight-bold mb-2" style="letter-spacing: .1em; color: #ffd27a;">
                        <?= htmlspecialchars((string) $lock->badge, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <h2 class="h4 text-white mb-3"><?= htmlspecialchars((string) $lock->offer_title, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p class="mb-4"><?= htmlspecialchars((string) $lock->banner_text, ENT_QUOTES, 'UTF-8') ?></p>

                    <div class="d-flex flex-wrap" style="gap: .75rem;">
                        <?php if(!empty($lock->whatsapp_url)): ?>
                            <a href="<?= htmlspecialchars((string) $lock->whatsapp_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-success vip-demo-lock__btn-whatsapp" target="_blank" rel="noopener noreferrer">
                                <i class="fab fa-whatsapp mr-1"></i><?= htmlspecialchars((string) $lock->whatsapp_label, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endif ?>

                        <?php if(!empty($lock->primary_url)): ?>
                            <a href="<?= htmlspecialchars((string) $lock->primary_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
                                <?= htmlspecialchars((string) $lock->primary_label, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endif ?>

                        <?php if(!empty($lock->secondary_url)): ?>
                            <a href="<?= htmlspecialchars((string) $lock->secondary_url, ENT_QUOTES, 'UTF-8') ?>" class="btn vip-demo-lock__btn-secondary" target="_blank" rel="noopener noreferrer">
                                <?= htmlspecialchars((string) $lock->secondary_label, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endif ?>

                        <?php if(!empty($lock->back_url)): ?>
                            <a href="<?= htmlspecialchars((string) $lock->back_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">
                                <?= htmlspecialchars((string) $lock->back_label, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
