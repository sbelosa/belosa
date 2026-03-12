<?php defined('ALTUMCODE') || die() ?>

<!-- Custom code: FC-2026-02-27: chatbot html block output -->
<?php
$chatbot_html = (string) ($data->link->settings->html ?? '');
$is_pets_chatbot = ($data->link->type ?? null) === 'custom_html_chatbot_pets';
$chatbot_icon_url = SITE_URL . 'themes/altum/assets/images/sovica.png';
$chatbot_icon_fallback_url = SITE_URL . UPLOADS_URL_PATH . 'ai-chat/sovica.png';
$chatbot_label = $is_pets_chatbot
    ? 'Ai savjetnik za kućne ljubimce'
    : 'Ai Savjetnik za Forever proizvode';
$chatbot_close_label = 'Zatvori AI';
$chatbot_human_name = 'Extreme Chat Ai';
$chatbot_home_label = 'Povratak na naslovnicu';
$chatbot_home_url = SITE_URL;

if(stripos($chatbot_html, 'zapier-interfaces-chatbot-embed') !== false) {
    $chatbot_html = preg_replace('/\bis-popup\s*=\s*(["\'])\s*true\s*\1/i', "is-popup='false'", $chatbot_html);

    if(stripos($chatbot_html, 'is-popup=') === false) {
        $chatbot_html = preg_replace('/<zapier-interfaces-chatbot-embed\b/i', "<zapier-interfaces-chatbot-embed is-popup='false'", $chatbot_html, 1);
    }

    $chatbot_html = preg_replace('/<script\s+async\s+type=(["\'])module\1/i', "<script type='module'", $chatbot_html);
}
?>
<style>
    .fcc-biolink-chatbot-shell {
        position: fixed;
        right: 1rem;
        bottom: 4.85rem;
        width: min(420px, calc(100vw - 2rem));
        height: min(700px, calc(100vh - 6.25rem));
        min-height: 520px;
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
        height: 100%;
        background: #05070c !important;
    }

    .fcc-biolink-chatbot-shell.has-human-header zapier-interfaces-chatbot-embed {
        height: calc(100% - 74px);
    }

    .fcc-biolink-chatbot-human-header {
        height: 74px;
        padding: .45rem .85rem .4rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: .18rem;
        background: linear-gradient(180deg, rgba(16, 20, 30, .94), rgba(7, 10, 16, .9));
        border-bottom: 1px solid rgba(74, 144, 226, .42);
    }

    .fcc-biolink-chatbot-human-top-owl {
        width: 30px;
        height: 30px;
        object-fit: contain;
        filter: drop-shadow(0 1px 3px rgba(0, 0, 0, .4));
    }

    .fcc-biolink-chatbot-human-name-row {
        display: inline-flex;
        align-items: center;
        gap: .33rem;
        color: #eef3ff;
        font-size: .82rem;
        font-weight: 700;
        letter-spacing: .14px;
        line-height: 1;
    }

    .fcc-biolink-chatbot-home-link {
        margin-top: .18rem;
        font-size: .69rem;
        font-weight: 700;
        color: #4a90e2;
        text-decoration: none;
        line-height: 1;
        border-bottom: 1px solid rgba(74, 144, 226, .35);
        transition: color .15s ease, border-color .15s ease;
    }

    .fcc-biolink-chatbot-home-link:hover,
    .fcc-biolink-chatbot-home-link:focus {
        color: #7db8ff;
        border-color: rgba(125, 184, 255, .55);
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
        .fcc-biolink-chatbot-shell {
            right: .75rem;
            left: .75rem;
            width: auto;
            bottom: 4.75rem;
            height: min(75vh, 640px);
            min-height: 460px;
        }

        .fcc-biolink-chatbot-toggle {
            right: .75rem;
            bottom: .75rem;
        }

        .fcc-biolink-chatbot-human-header {
            height: 70px;
        }

        .fcc-biolink-chatbot-shell.has-human-header zapier-interfaces-chatbot-embed {
            height: calc(100% - 70px);
        }
    }
</style>

<div id="fcc-biolink-chatbot-shell" class="fcc-biolink-chatbot-shell<?= $is_pets_chatbot ? '' : ' has-human-header' ?>" aria-hidden="true">
    <div class="fcc-biolink-chatbot-loader">Učitavam chat...</div>
    <?php if(!$is_pets_chatbot): ?>
        <div class="fcc-biolink-chatbot-human-header" aria-hidden="true">
            <img src="<?= $chatbot_icon_url ?>" alt="" class="fcc-biolink-chatbot-human-top-owl" loading="eager" decoding="async" onerror="this.onerror=null;this.src='<?= $chatbot_icon_fallback_url ?>';" />
            <span class="fcc-biolink-chatbot-human-name-row">
                <span><?= $chatbot_human_name ?></span>
            </span>
            <a href="<?= $chatbot_home_url ?>" class="fcc-biolink-chatbot-home-link" target="_top" rel="noopener"><?= $chatbot_home_label ?></a>
        </div>
    <?php endif ?>
    <?= $chatbot_html ?>
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

        if(!shell || !toggle) return;

        const setOpen = isOpen => {
            shell.classList.toggle('is-open', isOpen);
            shell.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggle.setAttribute('aria-label', isOpen ? closeLabel : defaultLabel);
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
        document.addEventListener('keydown', event => {
            if(event.key === 'Escape') setOpen(false);
        });
    })();
</script>
<!-- /Custom code: FC-2026-02-27 -->
