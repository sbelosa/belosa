<?php defined('ALTUMCODE') || die() ?>

<?php $banner = $data->banner ?? null; ?>
<?php if(!$banner): return; endif ?>

<style>
    .vip-demo-banner {
        position: relative;
        overflow: hidden;
        border-radius: 1.35rem;
        border: 1px solid rgba(90, 196, 181, 0.26);
        background:
            radial-gradient(720px 220px at -10% -20%, rgba(245, 158, 11, 0.16), transparent 58%),
            radial-gradient(620px 240px at 110% 0%, rgba(45, 212, 191, 0.18), transparent 60%),
            linear-gradient(160deg, rgba(18, 27, 40, 0.98), rgba(8, 14, 23, 0.99));
        box-shadow: 0 28px 60px rgba(3, 9, 18, 0.24);
        color: #f4fbff;
    }

    .vip-demo-banner__chip {
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

    .vip-demo-banner__list {
        display: flex;
        flex-wrap: wrap;
        gap: .6rem;
        margin: 1rem 0 0;
        padding: 0;
        list-style: none;
    }

    .vip-demo-banner__list li {
        padding: .5rem .8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: rgba(239, 247, 255, 0.9);
        font-size: .82rem;
        font-weight: 700;
    }

    .vip-demo-banner h1,
    .vip-demo-banner h2,
    .vip-demo-banner h3,
    .vip-demo-banner .h1,
    .vip-demo-banner .h2,
    .vip-demo-banner .h3,
    .vip-demo-banner .h4 {
        color: #f5fbff !important;
    }

    .vip-demo-banner .vip-demo-banner__btn-whatsapp {
        border-color: rgba(72, 255, 132, 0.82);
        color: #77f8a2;
        background: rgba(10, 24, 16, 0.42);
    }

    .vip-demo-banner .vip-demo-banner__btn-whatsapp:hover,
    .vip-demo-banner .vip-demo-banner__btn-whatsapp:focus {
        border-color: #77f8a2;
        color: #082014;
        background: #77f8a2;
        box-shadow: 0 10px 24px rgba(76, 248, 157, 0.22);
    }

    .vip-demo-banner .vip-demo-banner__btn-secondary {
        border-color: rgba(182, 201, 224, 0.28);
        color: #f2f7ff;
        background: rgba(255, 255, 255, 0.08);
    }

    .vip-demo-banner .vip-demo-banner__btn-secondary:hover,
    .vip-demo-banner .vip-demo-banner__btn-secondary:focus {
        border-color: rgba(112, 226, 214, 0.68);
        color: #f8fcff;
        background: rgba(91, 211, 197, 0.18);
        box-shadow: 0 12px 28px rgba(56, 189, 177, 0.18);
    }
</style>

<div class="vip-demo-banner p-4 p-lg-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start" style="gap: 1.1rem;">
        <div class="pr-lg-4">
            <div class="small text-uppercase font-weight-bold mb-2" style="letter-spacing: .12em; color: #ffd27a;">
                <?= htmlspecialchars((string) $banner->eyebrow, ENT_QUOTES, 'UTF-8') ?>
            </div>

            <h2 class="h4 mb-3 text-white"><?= htmlspecialchars((string) $banner->title, ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="mb-0" style="max-width: 900px; color: rgba(234, 243, 255, 0.86);">
                <?= htmlspecialchars((string) $banner->message, ENT_QUOTES, 'UTF-8') ?>
            </p>

        </div>

        <div class="d-flex flex-column align-items-lg-end" style="gap: .75rem; min-width: min(100%, 320px);">
            <span class="vip-demo-banner__chip">
                <i class="fas fa-fw fa-lock"></i>
                <?= htmlspecialchars((string) $banner->badge, ENT_QUOTES, 'UTF-8') ?>
            </span>

            <div class="d-flex flex-wrap justify-content-lg-end" style="gap: .75rem;">
                <?php if(!empty($banner->whatsapp_url)): ?>
                    <a href="<?= htmlspecialchars((string) $banner->whatsapp_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-success vip-demo-banner__btn-whatsapp" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-whatsapp mr-1"></i><?= htmlspecialchars((string) $banner->whatsapp_label, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif ?>

                <?php if(!empty($banner->primary_url)): ?>
                    <a href="<?= htmlspecialchars((string) $banner->primary_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">
                        <?= htmlspecialchars((string) $banner->primary_label, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif ?>

                <?php if(!empty($banner->secondary_url)): ?>
                    <a href="<?= htmlspecialchars((string) $banner->secondary_url, ENT_QUOTES, 'UTF-8') ?>" class="btn vip-demo-banner__btn-secondary" target="_blank" rel="noopener noreferrer">
                        <?= htmlspecialchars((string) $banner->secondary_label, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
