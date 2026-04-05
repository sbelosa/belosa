<?php defined('ALTUMCODE') || die() ?>

<?php if(empty($data->share_url) || !is_logged_in()): ?>
    <?php return; ?>
<?php endif ?>

<?php
$fcc_share_id = $data->id ?? ('fcc-share-' . md5($data->share_url));
$fcc_share_text = $data->text ?? 'Prijavljeni ste! Podijelite ovu stranicu s vašom preporukom.';
$fcc_share_learn_more = $data->learn_more ?? l('blog.share_referral.learn_more');
$fcc_share_popup_title = $data->popup_title ?? l('blog.share_referral.modal_title');
$fcc_share_popup_text = $data->popup_text ?? 'Kada podijelite ovaj clanak putem ove forme, vas link za preporuku automatski se dodaje. Ako netko preko tog linka naruci proizvode ili posjeti Forever Card Club, prikazat ce se vasi kontakt podaci, a sustav Forever Living Products Web Shopa automatski ce zabiljeziti vasu preporuku.';
$fcc_share_spacing_class = !empty($data->flush_top) ? 'fcc-referral-share-banner--flush-top' : null;
?>

<section class="fcc-referral-share-banner mb-3 <?= $fcc_share_spacing_class ?>" id="<?= $fcc_share_id ?>">
    <div class="fcc-referral-share-banner__inner">
        <div class="fcc-referral-share-banner__content">
            <div class="fcc-referral-share-banner__note">
                <span class="fcc-referral-share-banner__pill">FCC</span>
                <span class="fcc-referral-share-banner__text"><?= $fcc_share_text ?></span>
            </div>

            <div class="fcc-referral-share-banner__actions">
                <button
                    type="button"
                    class="btn btn-sm btn-gray-100 fcc-referral-share-banner__toggle"
                    data-fcc-share-toggle
                    data-target="#<?= $fcc_share_id ?>-details"
                    aria-expanded="false"
                    aria-controls="<?= $fcc_share_id ?>-details"
                >
                    <i class="fas fa-fw fa-info-circle mr-1"></i>
                    <?= $fcc_share_learn_more ?>
                </button>

                <div class="fcc-referral-share-banner__buttons">
                    <?= include_view(THEME_PATH . 'views/partials/share_buttons.php', ['url' => $data->share_url, 'class' => 'btn btn-gray-100 btn-sm', 'copy_to_clipboard' => true, 'tracking_context' => 'referral_share']) ?>
                </div>
            </div>
        </div>

        <div id="<?= $fcc_share_id ?>-details" class="fcc-referral-share-banner__details d-none">
            <div class="fcc-referral-share-banner__details-title"><?= $fcc_share_popup_title ?></div>
            <div class="fcc-referral-share-banner__details-text"><?= $fcc_share_popup_text ?></div>
        </div>
    </div>
</section>

<?php ob_start() ?>
<style>
    .fcc-referral-share-banner {
        position: relative;
        z-index: 25;
    }

    .fcc-referral-share-banner--flush-top {
        margin-top: -1rem;
    }

    .fcc-referral-share-banner__inner {
        padding: 0.95rem 1.15rem;
        border-radius: 18px;
        border: 1px solid rgba(73, 206, 188, 0.16);
        background:
            radial-gradient(120% 180% at 0% 0%, rgba(74, 208, 189, 0.1), transparent 38%),
            linear-gradient(135deg, rgba(16, 18, 29, 0.92), rgba(11, 13, 21, 0.88));
        box-shadow: 0 14px 38px rgba(0, 0, 0, 0.22);
        backdrop-filter: blur(14px);
    }

    .fcc-referral-share-banner__content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .fcc-referral-share-banner__note {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .fcc-referral-share-banner__pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.34rem 0.62rem;
        border-radius: 999px;
        background: linear-gradient(135deg, rgba(75, 210, 190, 0.24), rgba(71, 120, 255, 0.16));
        color: #9bf8ec;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        flex-shrink: 0;
        box-shadow: inset 0 0 0 1px rgba(155, 248, 236, 0.08);
    }

    .fcc-referral-share-banner__text {
        color: rgba(241, 246, 251, 0.9);
        font-size: 0.98rem;
        font-weight: 500;
        line-height: 1.5;
    }

    .fcc-referral-share-banner__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
        flex-shrink: 0;
    }

    .fcc-referral-share-banner__buttons .btn {
        margin: 0 0.3rem 0 0;
        border-radius: 999px;
    }

    .fcc-referral-share-banner__details {
        margin-top: 0.85rem;
        padding: 0.85rem 0.95rem;
        border-radius: 12px;
        border: 1px solid rgba(73, 206, 188, 0.12);
        background: rgba(255, 255, 255, 0.035);
    }

    .fcc-referral-share-banner__details-title {
        margin-bottom: 0.25rem;
        color: #d9fffa;
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .fcc-referral-share-banner__details-text {
        color: rgba(230, 236, 242, 0.82);
        font-size: 0.88rem;
        line-height: 1.55;
    }

    @media (max-width: 991px) {
        .fcc-referral-share-banner--flush-top {
            margin-top: -0.55rem;
        }

        .fcc-referral-share-banner__content {
            flex-direction: column;
            align-items: stretch;
        }

        .fcc-referral-share-banner__actions {
            justify-content: flex-start;
        }
    }

    @media (max-width: 575px) {
        .fcc-referral-share-banner__inner {
            padding: 0.9rem 0.9rem;
        }

        .fcc-referral-share-banner__note {
            align-items: flex-start;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head', 'fcc_referral_share_banner_css'); ?>

<?php ob_start() ?>
<script>
    'use strict';

    document.querySelectorAll('[data-fcc-share-toggle]').forEach(button => {
        if(button.dataset.fccShareBound) {
            return;
        }

        button.dataset.fccShareBound = 'true';

        button.addEventListener('click', () => {
            const target = document.querySelector(button.getAttribute('data-target'));

            if(!target) {
                return;
            }

            const isHidden = target.classList.contains('d-none');
            target.classList.toggle('d-none', !isHidden);
            button.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
        });
    });
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript', 'fcc_referral_share_banner_js'); ?>
