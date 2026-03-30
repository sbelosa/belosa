<?php defined('ALTUMCODE') || die() ?>

<?php
$fcc_market_selector_title = $data->title ?? (\Altum\Language::$code === 'hr' ? 'Odaberite tržište za Forever linkove' : 'Choose market for Forever links');
$fcc_market_selector_subtitle = $data->subtitle ?? (\Altum\Language::$code === 'hr' ? 'Ovaj odabir pomaže kada preglednik ili mreža ne pošalju pouzdanu državu posjetitelja.' : 'This helps when the browser or network does not provide a reliable visitor country.');
$fcc_market_selector_current = \Altum\Link::resolve_forever_market_country_code($data->current_market ?? null);
$fcc_market_selector_class = $data->class ?? '';
$fcc_market_selector_options = $data->options ?? [
    'hr' => \Altum\Language::$code === 'hr' ? 'Hrvatska' : 'Croatia',
    'ba' => \Altum\Language::$code === 'hr' ? 'BiH' : 'BiH',
    'rs' => \Altum\Language::$code === 'hr' ? 'Srbija' : 'Serbia',
];
$fcc_market_selector_cookie_name = \Altum\Link::get_forever_market_cookie_name();
?>

<div class="fcc-market-selector <?= $fcc_market_selector_class ?>" data-fcc-market-selector data-cookie-name="<?= $fcc_market_selector_cookie_name ?>">
    <div class="fcc-market-selector__copy">
        <div class="fcc-market-selector__title"><?= $fcc_market_selector_title ?></div>
        <div class="fcc-market-selector__subtitle"><?= $fcc_market_selector_subtitle ?></div>
    </div>

    <div class="fcc-market-selector__actions" role="group" aria-label="<?= $fcc_market_selector_title ?>">
        <?php foreach($fcc_market_selector_options as $fcc_market_code => $fcc_market_label): ?>
            <button
                type="button"
                class="fcc-market-selector__button <?= $fcc_market_selector_current === $fcc_market_code ? 'is-active' : null ?>"
                data-fcc-market-option="<?= $fcc_market_code ?>"
                aria-pressed="<?= $fcc_market_selector_current === $fcc_market_code ? 'true' : 'false' ?>"
            >
                <?= $fcc_market_label ?>
            </button>
        <?php endforeach ?>
    </div>
</div>

<?php ob_start() ?>
<style>
    .fcc-market-selector {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.8rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(91, 222, 198, 0.14);
        background: linear-gradient(180deg, rgba(10, 16, 26, 0.88), rgba(8, 12, 20, 0.94));
    }

    .fcc-market-selector__copy {
        min-width: 0;
    }

    .fcc-market-selector__title {
        color: #e9fcf8;
        font-size: 0.9rem;
        font-weight: 700;
        line-height: 1.25;
    }

    .fcc-market-selector__subtitle {
        margin-top: 0.2rem;
        color: rgba(224, 242, 255, 0.66);
        font-size: 0.76rem;
        line-height: 1.45;
    }

    .fcc-market-selector__actions {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        justify-content: flex-end;
    }

    .fcc-market-selector__button {
        border: 1px solid rgba(113, 228, 205, 0.14);
        background: rgba(255, 255, 255, 0.04);
        color: #c6f7ee;
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        font-size: 0.78rem;
        font-weight: 700;
        line-height: 1;
        transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }

    .fcc-market-selector__button:hover,
    .fcc-market-selector__button:focus {
        border-color: rgba(113, 228, 205, 0.36);
        background: rgba(113, 228, 205, 0.12);
        color: #effffc;
        outline: none;
        transform: translateY(-1px);
    }

    .fcc-market-selector__button.is-active {
        border-color: rgba(113, 228, 205, 0.42);
        background: linear-gradient(135deg, rgba(77, 217, 192, 0.2), rgba(79, 130, 255, 0.18));
        color: #ffffff;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.04);
    }

    @media (max-width: 991.98px) {
        .fcc-market-selector {
            flex-direction: column;
            align-items: stretch;
            gap: 0.75rem;
            padding: 0.75rem 0.9rem;
        }

        .fcc-market-selector__actions {
            justify-content: flex-start;
        }

        .fcc-market-selector__button {
            flex: 1 1 auto;
            min-width: calc(33.333% - 0.35rem);
            text-align: center;
        }
    }
</style>
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>

<?php ob_start() ?>
<script>
document.addEventListener('click', event => {
    const button = event.target.closest('[data-fcc-market-option]');

    if(!button) {
        return;
    }

    const selector = button.closest('[data-fcc-market-selector]');
    const cookieName = selector?.dataset.cookieName;
    const market = button.dataset.fccMarketOption;

    if(!selector || !cookieName || !market) {
        return;
    }

    document.cookie = `${cookieName}=${market}; path=/; max-age=${60 * 60 * 24 * 365}; SameSite=Lax`;
    window.location.reload();
});
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
