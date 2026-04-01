<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-02-27: chatbot html block output -->
<?php
$chatbot_html = (string) ($data->link->settings->html ?? '');
$is_pets_chatbot = ($data->link->type ?? null) === 'custom_html_chatbot_pets';
$chatbot_icon_url = SITE_URL . ASSETS_URL_PATH . 'images/sovica.png';
$chatbot_icon_fallback_url = SITE_URL . UPLOADS_URL_PATH . 'ai-chat/sovica.png';
$chatbot_label = $is_pets_chatbot
    ? 'Chat podrška za kućne ljubimce'
    : 'Chat podrška';
$chatbot_close_label = 'Zatvori chat';
/* Custom code: FC-2026-03-09: chatbot header name and homepage link */
$chatbot_owner_name = trim((string) ($data->link->settings->title ?? ''));
if($chatbot_owner_name === '') {
    $chatbot_owner_name = 'Savjetnik';
}
$chatbot_human_name = 'Extreme Chat (' . $chatbot_owner_name . ')';
$chatbot_status_label = 'Online';
$chatbot_home_label = 'Naslovnica';
$chatbot_home_url = SITE_URL;
/* /Custom code: FC-2026-03-09 */

if(stripos($chatbot_html, 'zapier-interfaces-chatbot-embed') !== false) {
    $chatbot_html = preg_replace('/\bis-popup\s*=\s*(["\'])\s*true\s*\1/i', "is-popup='false'", $chatbot_html);

    if(stripos($chatbot_html, 'is-popup=') === false) {
        $chatbot_html = preg_replace('/<zapier-interfaces-chatbot-embed\b/i', "<zapier-interfaces-chatbot-embed is-popup='false'", $chatbot_html, 1);
    }

    $chatbot_html = preg_replace('/<script\s+async\s+type=(["\'])module\1/i', "<script type='module'", $chatbot_html);
}
?>
<style>
    /* Custom code: FC-2026-03-09: biolink AI popup fullscreen mobile layout fix */
    .fcc-biolink-chatbot-shell {
        position: fixed;
        right: 1rem;
        bottom: 4.85rem;
        width: min(420px, calc(100vw - 2rem));
        height: min(700px, calc(100vh - 6.25rem));
        min-height: 520px;
        display: flex;
        flex-direction: column;
        background: #05070c;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 1rem 2.5rem rgba(0, 0, 0, .38);
        transform: translateY(16px) scale(.98);
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: transform .2s ease, opacity .2s ease, visibility 0s linear .2s;
        z-index: 2147482000;
    }

    .fcc-biolink-chatbot-shell.is-open {
        transform: translateY(0) scale(1);
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transition: transform .2s ease, opacity .2s ease;
    }

    .fcc-biolink-chatbot-shell zapier-interfaces-chatbot-embed {
        display: block;
        width: 100%;
        flex: 1 1 auto;
        min-height: 0;
        height: auto;
        background: #05070c !important;
    }

    .fcc-biolink-chatbot-human-header {
        height: 72px;
        padding: .75rem .95rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .7rem;
        background: linear-gradient(180deg, rgba(16, 20, 30, .94), rgba(7, 10, 16, .9));
        border-bottom: 1px solid rgba(74, 144, 226, .42);
    }

    .fcc-biolink-chatbot-human-top-owl {
        width: 38px;
        height: 38px;
        object-fit: contain;
        border-radius: 50%;
        background: rgba(74, 144, 226, .12);
        padding: 4px;
        filter: drop-shadow(0 1px 3px rgba(0, 0, 0, .4));
    }

    .fcc-biolink-chatbot-human-name-row {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        gap: .2rem;
        color: #eef3ff;
        font-size: .9rem;
        font-weight: 700;
        letter-spacing: .14px;
        line-height: 1.05;
        flex: 1 1 auto;
        min-width: 0;
    }

    .fcc-biolink-chatbot-human-name-row > span:first-child {
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .fcc-biolink-chatbot-human-status {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .72rem;
        color: #9ed0ff;
        font-weight: 600;
        letter-spacing: .08px;
    }

    .fcc-biolink-chatbot-human-status::before {
        content: '';
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #47d16c;
        box-shadow: 0 0 0 3px rgba(71, 209, 108, .15);
    }

    .fcc-biolink-chatbot-home-link {
        margin-left: auto;
        flex: 0 0 auto;
        font-size: .72rem;
        line-height: 1;
        color: #cde3ff;
        text-decoration: none;
        border: 1px solid rgba(125, 184, 255, .35);
        border-radius: .6rem;
        padding: .36rem .58rem;
        background: rgba(74, 144, 226, .08);
        transition: color .15s ease, border-color .15s ease, background-color .15s ease;
    }

    .fcc-biolink-chatbot-home-link:hover,
    .fcc-biolink-chatbot-home-link:focus {
        color: #ffffff;
        border-color: rgba(125, 184, 255, .55);
        background: rgba(74, 144, 226, .16);
        text-decoration: none;
    }

    .fcc-biolink-chatbot-shell zapier-interfaces-chatbot-embed:not(:defined) {
        visibility: hidden;
    }

    .fcc-biolink-chatbot-loader {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #05070c;
        color: #d8dee8;
        font-size: .875rem;
        letter-spacing: .2px;
        z-index: 2;
    }

    .fcc-biolink-chatbot-shell.is-ready .fcc-biolink-chatbot-loader {
        display: none;
    }

    .fcc-biolink-chatbot-close-footer {
        display: none;
    }

    .fcc-biolink-chatbot-toggle {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 2147482001;
        border: 0;
        border-radius: 1.1rem;
        padding: .3rem .28rem .38rem;
        min-width: auto;
        font-weight: 700;
        color: #fff8e8;
        background: transparent;
        box-shadow: none;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .16rem;
        text-align: center;
        cursor: pointer;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        overflow: visible;
    }

    .fcc-biolink-chatbot-toggle::before {
        content: none;
    }

    .fcc-biolink-chatbot-toggle:hover::before {
        left: auto;
    }

    .fcc-biolink-chatbot-toggle .fcc-biolink-chatbot-toggle-icon {
        width: 66px;
        height: 66px;
        object-fit: contain;
        border-radius: 0;
        filter: none;
    }

    .fcc-biolink-chatbot-toggle .fcc-biolink-chatbot-toggle-pets-icon {
        width: 66px;
        height: 66px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.9rem;
        filter: drop-shadow(0 8px 20px rgba(95, 61, 196, .34));
    }

    .fcc-biolink-chatbot-toggle .fcc-biolink-chatbot-toggle-pets-icon .fa-circle {
        color: #5f3dc4;
    }

    .fcc-biolink-chatbot-toggle .fcc-biolink-chatbot-toggle-pets-icon .fa-paw {
        color: #fff;
        filter: none;
    }

    .fcc-biolink-chatbot-toggle i {
        font-size: 1rem;
        line-height: 1;
        color: #ffe4a6;
        filter: drop-shadow(0 1px 3px rgba(0, 0, 0, .35));
    }

    .fcc-biolink-chatbot-toggle .fcc-biolink-chatbot-toggle-label {
        letter-spacing: .12px;
        line-height: 1.16;
        font-size: .76rem;
        text-wrap: balance;
        max-width: 138px;
        text-shadow: 0 1px 3px rgba(0, 0, 0, .45);
    }

    @media (max-width: 576px) {
        body.fcc-biolink-chatbot-open {
            overflow: hidden;
            height: 100dvh;
        }

        .fcc-biolink-chatbot-shell {
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            width: 100vw;
            height: 100dvh;
            min-height: 100dvh;
            border-radius: 0;
            box-shadow: none;
            transform: translateY(100%);
        }

        .fcc-biolink-chatbot-toggle {
            right: .75rem;
            bottom: .75rem;
        }

        .fcc-biolink-chatbot-shell.is-open + .fcc-biolink-chatbot-toggle {
            display: none;
        }

        .fcc-biolink-chatbot-human-header {
            height: 70px;
            padding: .7rem .85rem;
            gap: .55rem;
        }

        .fcc-biolink-chatbot-home-link {
            padding: .34rem .52rem;
            font-size: .69rem;
        }

        .fcc-biolink-chatbot-close-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            border: 0;
            border-top: 1px solid rgba(255, 255, 255, .08);
            background: #05070c;
            color: #fff8e8;
            font-weight: 700;
            padding: .9rem 1rem calc(.9rem + env(safe-area-inset-bottom));
            letter-spacing: .12px;
        }
    }
    /* /Custom code: FC-2026-03-09 */
</style>

<div id="fcc-biolink-chatbot-shell" class="fcc-biolink-chatbot-shell<?= $is_pets_chatbot ? '' : ' has-human-header' ?>" aria-hidden="true">
    <div class="fcc-biolink-chatbot-loader">Učitavam chat...</div>
    <?php if(!$is_pets_chatbot): ?>
        <div class="fcc-biolink-chatbot-human-header" aria-hidden="true">
            <img src="<?= $chatbot_icon_url ?>" alt="" class="fcc-biolink-chatbot-human-top-owl" loading="eager" decoding="async" onerror="this.onerror=null;this.src='<?= $chatbot_icon_fallback_url ?>';" />
            <span class="fcc-biolink-chatbot-human-name-row">
                <span><?= htmlspecialchars($chatbot_human_name, ENT_QUOTES) ?></span>
                <span class="fcc-biolink-chatbot-human-status"><?= $chatbot_status_label ?></span>
            </span>
            <a href="<?= $chatbot_home_url ?>" class="fcc-biolink-chatbot-home-link" target="_top" rel="noopener"><?= $chatbot_home_label ?></a>
        </div>
    <?php endif ?>
    <?= $chatbot_html ?>
    <!-- Custom code: FC-2026-03-09: mobile in-popup close button -->
    <button type="button" class="fcc-biolink-chatbot-close-footer" data-chatbot-close-footer><?= $chatbot_close_label ?></button>
    <!-- /Custom code: FC-2026-03-09 -->
</div>

<button type="button" id="fcc-biolink-chatbot-toggle" class="fcc-biolink-chatbot-toggle" aria-controls="fcc-biolink-chatbot-shell" aria-expanded="false" aria-label="<?= $chatbot_label ?>">
    <?php if($is_pets_chatbot): ?>
        <span class="fa-stack fcc-biolink-chatbot-toggle-pets-icon" aria-hidden="true">
            <i class="fas fa-circle fa-stack-2x"></i>
            <i class="fas fa-paw fa-stack-1x fa-inverse"></i>
        </span>
    <?php else: ?>
        <img src="<?= $chatbot_icon_url ?>" alt="" class="fcc-biolink-chatbot-toggle-icon" loading="eager" decoding="async" aria-hidden="true" onerror="this.onerror=null;this.src='<?= $chatbot_icon_fallback_url ?>';" />
    <?php endif ?>
    <span class="fcc-biolink-chatbot-toggle-label"><?= $chatbot_label ?></span>
</button>

<script>
    (() => {
        const shell = document.getElementById('fcc-biolink-chatbot-shell');
        const toggle = document.getElementById('fcc-biolink-chatbot-toggle');
        const iconUrl = <?= json_encode($chatbot_icon_url) ?>;
        const iconFallbackUrl = <?= json_encode($chatbot_icon_fallback_url) ?>;
        const isPetsChatbot = <?= json_encode($is_pets_chatbot) ?>;
        const defaultLabel = <?= json_encode($chatbot_label) ?>;
        const closeLabel = <?= json_encode($chatbot_close_label) ?>;
        const closeFooterButton = shell.querySelector('[data-chatbot-close-footer]');
        const mobileMedia = window.matchMedia('(max-width: 576px)');

        if(!shell || !toggle) return;

        const syncBodyLock = isOpen => {
            document.body.classList.toggle('fcc-biolink-chatbot-open', isOpen && mobileMedia.matches);
        };

        const setOpen = isOpen => {
            shell.classList.toggle('is-open', isOpen);
            shell.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.setAttribute('aria-label', isOpen ? closeLabel : defaultLabel);
            syncBodyLock(isOpen);
            toggle.innerHTML = isOpen
                ? `<i class='fas fa-times' aria-hidden='true'></i><span class='fcc-biolink-chatbot-toggle-label'>${closeLabel}</span>`
                : (isPetsChatbot
                    ? `<span class='fa-stack fcc-biolink-chatbot-toggle-pets-icon' aria-hidden='true'><i class='fas fa-circle fa-stack-2x'></i><i class='fas fa-paw fa-stack-1x fa-inverse'></i></span><span class='fcc-biolink-chatbot-toggle-label'>${defaultLabel}</span>`
                    : `<img src='${iconUrl}' alt='' class='fcc-biolink-chatbot-toggle-icon' loading='eager' decoding='async' aria-hidden='true' onerror="this.onerror=null;this.src='${iconFallbackUrl}';" /><span class='fcc-biolink-chatbot-toggle-label'>${defaultLabel}</span>`);
        };

        const markReady = () => shell.classList.add('is-ready');

        if(window.customElements && customElements.get('zapier-interfaces-chatbot-embed')) {
            markReady();
        } else if(window.customElements && customElements.whenDefined) {
            customElements.whenDefined('zapier-interfaces-chatbot-embed').then(markReady).catch(() => {});
        }

        toggle.addEventListener('click', () => setOpen(!shell.classList.contains('is-open')));
        if(closeFooterButton) {
            closeFooterButton.addEventListener('click', () => setOpen(false));
        }

        if(mobileMedia.addEventListener) {
            mobileMedia.addEventListener('change', () => syncBodyLock(shell.classList.contains('is-open')));
        } else if(mobileMedia.addListener) {
            mobileMedia.addListener(() => syncBodyLock(shell.classList.contains('is-open')));
        }

        document.addEventListener('keydown', event => {
            if(event.key === 'Escape') setOpen(false);
        });
    })();
</script>
<!-- /Custom code: FC-2026-02-27 -->
