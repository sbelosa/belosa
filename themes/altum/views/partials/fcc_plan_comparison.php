<?php
defined('ALTUMCODE') || die();
require_once APP_PATH . 'helpers/fcc_plan_comparison.php';

$comparison = $data->comparison_model ?? fcc_plan_comparison_model($data->plans ?? null, $this->user ?? null);
$locale = (string) ($comparison['locale'] ?? 'en');
$text = static function(string $key, array $parameters = []) use ($locale): string {
    return fcc_plan_comparison_text($key, $parameters, $locale);
};
$escape = static function($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$beginner = $comparison['beginner'] ?? null;
$proPlan = $comparison['pro'] ?? null;
$activePro = !empty($comparison['active_pro']);
$subscription = !empty($comparison['has_subscription']);
$local = !empty($comparison['local']);
$currentPlanId = (string) ($comparison['current_plan_id'] ?? ($this->user->plan_id ?? ''));
$trialRequireCard = !empty($comparison['trial_require_card']);
$trialDays = max(0, (int) ($proPlan->trial_days ?? 0));
$trialEligible = !empty($comparison['trial_eligible']) && $trialDays > 0;
$prices = (array) ($comparison['prices'] ?? []);
$currencyCode = (string) ($comparison['currency'] ?? 'EUR');
$number = static function($amount) use ($locale): string {
    $decimalComma = in_array($locale, ['hr', 'sl', 'de', 'es', 'fr', 'pl', 'sr', 'sq', 'cnr', 'sv', 'hu', 'ro'], true);
    return number_format((float) $amount, 2, $decimalComma ? ',' : '.', $decimalComma ? '.' : ',');
};
$setting = static function($plan, string $key, $default = null) {
    if(!$plan || !isset($plan->settings)) return $default;
    $settings = is_string($plan->settings) ? json_decode($plan->settings) : (object) $plan->settings;
    if(!is_object($settings)) return $default;
    return property_exists($settings, $key) ? $settings->{$key} : $default;
};
$blocks = static function($plan) use ($setting): array {
    $list = $setting($plan, 'enabled_biolink_blocks', null);
    return $list === null ? [] : array_keys(array_filter((array) $list));
};
$blockValue = static function($plan, string $key) use ($setting) {
    $list = $setting($plan, 'enabled_biolink_blocks', null);
    return $list === null ? null : !empty(((array) $list)[$key]);
};
$value = static function($raw, bool $numeric = false) use ($text): string {
    if($raw === null) return $text('unknown');
    if($numeric) return (float) $raw === -1.0 ? $text('unlimited') : number_format((float) $raw, 0, '.', '');
    if(is_array($raw) || is_object($raw)) return implode(', ', array_map('strval', (array) $raw));
    return filter_var($raw, FILTER_VALIDATE_BOOLEAN) ? $text('included') : $text('not_included');
};
$beginnerPrices = $beginner->prices ?? null;
if(is_string($beginnerPrices)) $beginnerPrices = json_decode($beginnerPrices);
$beginnerPrice = null;
$beginnerDays = 0;
foreach(['monthly' => 30, 'quarterly' => 90, 'biannual' => 180, 'annual' => 365, 'lifetime' => 0] as $frequency => $days) {
    if(isset($beginnerPrices->{$frequency}->{$currencyCode}) && is_numeric($beginnerPrices->{$frequency}->{$currencyCode})) {
        $beginnerPrice = (float) $beginnerPrices->{$frequency}->{$currencyCode};
        $beginnerDays = $days;
        break;
    }
}
$primaryFrequency = isset($prices['monthly']) ? 'monthly' : array_key_first($prices);
$primaryPrice = $primaryFrequency !== null ? $prices[$primaryFrequency] : null;
$rows = ['coach', 'invite', 'webinar', 'team', 'products', 'plan'];
$details = [
    ['applications', 'biolinks_limit', 'number'],
    ['blocks_per_app', 'biolink_blocks_limit', 'number'],
    ['short_links', 'links_limit', 'number'],
    ['qr_codes', 'qr_codes_limit', 'number'],
    ['analytics_days', 'track_links_retention', 'number'],
    ['statistics', 'statistics', 'boolean'],
    ['public_ai', 'fcc_ai_is_enabled', 'legacy'],
    ['general_coach', 'fcc_coach_is_enabled', 'legacy'],
    ['growth_plan', 'ai_growth_plan_is_enabled', 'boolean'],
    ['funnel', 'vip_funnel_core_is_enabled', 'funnel'],
    ['funnel_analytics', 'lead_funnel', 'analytics'],
    ['password_protection', 'password', 'boolean'],
    ['custom_url', 'custom_url', 'boolean'],
    ['export', 'export', 'export'],
    ['shop_block', 'link_forever_shop', 'block'],
    ['registration_block', 'link_forever_webshop_reg', 'block'],
    ['product_block', 'link_forever_product', 'block'],
    ['contact_block', 'link_save_contact', 'block'],
    ['lead_block', 'lead_funnel', 'block'],
    ['whatsapp_block', 'custom_html_whatsapp', 'block'],
    ['discount_block', 'link_discount', 'block'],
    ['app_switcher_block', 'link_app_switcher', 'block'],
    ['home_screen_block', 'link_homescreen_android', 'block'],
    ['chatbot_block', 'custom_html_chatbot', 'block'],
    ['design_themes', 'biolinks_themes', 'count'],
    ['design_templates', 'biolinks_templates', 'count'],
    ['font_options', 'fonts', 'boolean'],
    ['seo', 'seo', 'boolean'],
    ['utm', 'utm', 'boolean'],
    ['custom_css', 'custom_css_is_enabled', 'boolean'],
    ['custom_js', 'custom_js_is_enabled', 'boolean'],
];
$detailValue = static function($plan, array $detail) use ($setting, $blockValue, $value, $text): string {
    [$label, $key, $type] = $detail;
    if($type === 'block') return $value($blockValue($plan, $key));
    if($type === 'analytics') {
        $lead = $blockValue($plan, 'lead_funnel');
        $statistics = $setting($plan, 'statistics');
        return $value($lead === null || $statistics === null ? null : $lead && $statistics);
    }
    $raw = $setting($plan, $key, $type === 'legacy' && $plan ? true : null);
    if($type === 'count') return $raw === null ? $text('unknown') : (string) count((array) $raw);
    if($type === 'funnel') return $value($raw || (string) ($plan->plan_id ?? '') === '5');
    if($type === 'export' && (is_array($raw) || is_object($raw))) {
        $formats = array_filter((array) $raw);
        return $formats ? implode(', ', array_map('strtoupper', array_keys($formats))) : $text('not_included');
    }
    return $value($raw, $type === 'number');
};
$blockLabel = static function(string $key): string {
    $translationKey = 'link.biolink.blocks.' . strtolower($key);
    $label = function_exists('l') ? (string) l($translationKey) : '';
    return $label !== '' && $label !== $translationKey ? strip_tags($label) : ucfirst(str_replace('_', ' ', $key));
};
?>
<link rel="stylesheet" href="<?= $escape(ASSETS_FULL_URL . 'css/fcc-plan-comparison.css?v=20261011a') ?>" media="screen,print">
<section class="fcc-pc" id="fcc-plan-features" aria-labelledby="fcc-pc-title">
    <header class="fcc-pc-heading">
        <span class="fcc-pc-eyebrow"><?= $escape($text('eyebrow')) ?></span>
        <h2 id="fcc-pc-title"><?= $escape($text('title')) ?></h2>
        <p><?= $escape($text('intro')) ?></p>
    </header>
    <?php if(!$beginner || !$proPlan): ?>
        <p class="fcc-pc-notice" role="status"><?= $escape($text('catalog_unavailable')) ?></p>
    <?php else: ?>
    <div class="fcc-pc-cards">
        <article class="fcc-pc-card fcc-pc-card-beginner" aria-labelledby="fcc-pc-beginner-title">
            <div class="fcc-pc-card-top"><span class="fcc-pc-plan-name">Beginner</span><?php if($currentPlanId === (string) $beginner->plan_id): ?><span class="fcc-pc-current"><?= $escape($text('current_plan')) ?></span><?php endif ?></div>
            <p class="fcc-pc-eyebrow"><?= $escape($text('beginner_kicker')) ?></p>
            <h3 id="fcc-pc-beginner-title"><?= $escape($text('beginner_title')) ?></h3>
            <p class="fcc-pc-card-intro"><?= $escape($text('beginner_intro')) ?></p>
            <p class="fcc-pc-model"><span><?= $escape($text('row_coach_title')) ?></span><strong><?= $escape($text('coach_basic_model')) ?></strong></p>
            <ol class="fcc-pc-benefits"><?php for($i = 1; $i <= 4; $i++): ?><li><?= $escape($text('beginner_item_' . $i)) ?></li><?php endfor ?></ol>
            <dl class="fcc-pc-card-metrics"><?php foreach([['applications', 'biolinks_limit'], ['blocks_per_app', 'biolink_blocks_limit']] as [$metricLabel, $metricKey]): ?><div><dt><?= $escape($text($metricLabel)) ?></dt><dd><?= $escape($value($setting($beginner, $metricKey), true)) ?></dd></div><?php endforeach ?></dl>
            <div class="fcc-pc-card-bottom">
                <?php if($beginnerPrice !== null): ?>
                    <p class="fcc-pc-price"><strong><?= $escape($number($beginnerPrice) . ' ' . $currencyCode) ?></strong><span><?= $escape((float) $beginnerPrice === 0.0 ? $text('free_price') : ($beginnerDays > 0 ? $text('price_period', ['days' => $beginnerDays]) : $text('one_time'))) ?></span></p>
                <?php else: ?><p class="fcc-pc-price-note"><?= $escape($text('price_unavailable')) ?></p><?php endif ?>
                <a class="fcc-pc-button fcc-pc-button-outline" href="<?= $escape(url('partner/cards')) ?>"><?= $escape($text('beginner_cta')) ?></a>
            </div>
        </article>
        <article class="fcc-pc-card fcc-pc-card-pro" aria-labelledby="fcc-pc-pro-title">
            <div class="fcc-pc-card-top"><span class="fcc-pc-plan-name">PRO</span><?php if($currentPlanId === (string) $proPlan->plan_id): ?><span class="fcc-pc-current"><?= $escape($text('current_plan')) ?></span><?php endif ?></div>
            <p class="fcc-pc-eyebrow"><?= $escape($text('pro_kicker')) ?></p>
            <h3 id="fcc-pc-pro-title"><?= $escape($text('pro_title')) ?></h3>
            <p class="fcc-pc-card-intro"><?= $escape($text('pro_intro')) ?></p>
            <p class="fcc-pc-model fcc-pc-model-pro"><span><?= $escape($text('row_coach_title')) ?></span><strong><?= $escape($text('coach_pro_model')) ?></strong></p>
            <ol class="fcc-pc-benefits"><?php for($i = 1; $i <= 4; $i++): ?><li><?= $escape($text('pro_item_' . $i)) ?></li><?php endfor ?></ol>
            <dl class="fcc-pc-card-metrics"><?php foreach([['applications', 'biolinks_limit'], ['blocks_per_app', 'biolink_blocks_limit']] as [$metricLabel, $metricKey]): ?><div><dt><?= $escape($text($metricLabel)) ?></dt><dd><?= $escape($value($setting($proPlan, $metricKey), true)) ?></dd></div><?php endforeach ?></dl>
            <div class="fcc-pc-card-bottom">
                <?php if($primaryPrice): ?>
                    <p class="fcc-pc-price"><strong><?= $escape($number($primaryPrice['amount']) . ' ' . $currencyCode) ?></strong><span><?= $escape(!empty($primaryPrice['days']) ? $text('price_period', ['days' => (int) $primaryPrice['days']]) : $text('one_time')) ?></span></p>
                    <?php if(count($prices) > 1): ?><dl class="fcc-pc-price-options" aria-label="<?= $escape($text('price_options')) ?>"><?php foreach($prices as $frequency => $price): if($frequency === $primaryFrequency) continue; ?><div><dt><?= $escape(!empty($price['days']) ? $text('price_period', ['days' => (int) $price['days']]) : $text('one_time')) ?></dt><dd><?= $escape($number($price['amount']) . ' ' . $currencyCode) ?></dd></div><?php endforeach ?></dl><?php endif ?>
                    <p class="fcc-pc-fine-print"><?= $escape($text('billing_choice')) ?></p>
                <?php else: ?><p class="fcc-pc-price-note"><?= $escape($text('price_unavailable')) ?></p><?php endif ?>
                <?php if($activePro): ?>
                    <a class="fcc-pc-button" href="<?= $escape(url('partner?view=coach')) ?>"><?= $escape($text('coach_cta')) ?></a>
                <?php elseif($subscription): ?>
                    <a class="fcc-pc-button" href="<?= $escape(url('account-plan')) ?>"><?= $escape($text('billing_cta')) ?></a>
                <?php elseif(!$local && !empty($comparison['payment_enabled'])): ?>
                    <a class="fcc-pc-button<?= $trialEligible ? ' fcc-pc-button-trial' : '' ?>" href="<?= $escape(url('pay/' . (int) $proPlan->plan_id)) ?>"><span><?= $escape($trialEligible ? $text('trial_cta', ['days' => $trialDays]) : $text('pro_cta')) ?></span></a>
                <?php else: ?>
                    <a class="fcc-pc-button" href="<?= $escape(url('feedback-tickets')) ?>"><?= $escape($text('support_cta')) ?></a>
                <?php endif ?>
                <p class="fcc-pc-fine-print"><?= $escape($local ? $text('local_short') : ($trialEligible && !$subscription && !$activePro ? ($trialRequireCard ? $text('trial_card') . ' ' . $text('trial_short', ['days' => $trialDays]) : $text('trial_without_card', ['days' => $trialDays])) : $text('renewal_short'))) ?></p>
            </div>
        </article>
    </div>
    <aside class="fcc-pc-model-note" aria-label="<?= $escape($text('coach_model_title')) ?>"><p><?= $escape($text('coach_model_note')) ?></p></aside>
    <section class="fcc-pc-shared" aria-labelledby="fcc-pc-shared-title">
        <div class="fcc-pc-section-heading"><h3 id="fcc-pc-shared-title"><?= $escape($text('shared_title')) ?></h3><p><?= $escape($text('shared_intro')) ?></p></div>
        <ol class="fcc-pc-shared-grid"><?php for($i = 1; $i <= 3; $i++): ?><li><span class="fcc-pc-step"><?= $i ?></span><h4><?= $escape($text('shared_' . $i . '_title')) ?></h4><p><?= $escape($text('shared_' . $i . '_text')) ?></p></li><?php endfor ?></ol>
    </section>
    <section class="fcc-pc-differences" aria-labelledby="fcc-pc-differences-title">
        <div class="fcc-pc-section-heading"><h3 id="fcc-pc-differences-title"><?= $escape($text('difference_title')) ?></h3><p><?= $escape($text('difference_intro')) ?></p></div>
        <div class="fcc-pc-table" role="table" aria-label="<?= $escape($text('difference_title')) ?>">
            <div class="fcc-pc-table-head" role="row"><span role="columnheader"><?= $escape($text('feature_header')) ?></span><span role="columnheader">Beginner</span><span role="columnheader">PRO</span></div>
            <?php foreach($rows as $row): ?>
            <div class="fcc-pc-table-row" role="row">
                <div class="fcc-pc-feature" role="rowheader"><h4><?= $escape($text('row_' . $row . '_title')) ?></h4><p><?= $escape($text('row_' . $row . '_help')) ?></p></div>
                <div class="fcc-pc-cell" role="cell"><span class="fcc-pc-mobile-label">Beginner</span><p><?= $escape($text('row_' . $row . '_beginner')) ?></p></div>
                <div class="fcc-pc-cell fcc-pc-cell-pro" role="cell"><span class="fcc-pc-mobile-label">PRO</span><p><?= $escape($text('row_' . $row . '_pro')) ?></p></div>
            </div>
            <?php endforeach ?>
            <div class="fcc-pc-table-row" role="row"><div class="fcc-pc-feature" role="rowheader"><h4><?= $escape($text('public_ai')) ?></h4><p><?= $escape($text('public_ai_help')) ?></p></div><div class="fcc-pc-cell" role="cell"><span class="fcc-pc-mobile-label">Beginner</span><p><?= $escape($value($setting($beginner, 'fcc_ai_is_enabled', true))) ?></p></div><div class="fcc-pc-cell fcc-pc-cell-pro" role="cell"><span class="fcc-pc-mobile-label">PRO</span><p><?= $escape($value($setting($proPlan, 'fcc_ai_is_enabled', true))) ?></p></div></div>
        </div>
        <p class="fcc-pc-fine-print fcc-pc-availability"><?= $escape($text('availability_note')) ?></p>
        <p class="fcc-pc-fine-print"><?= $escape($text('coach_model_note')) ?></p>
    </section>
    <details class="fcc-pc-details" id="fcc-pc-all-features">
        <summary><span><?= $escape($text('details_title')) ?></span></summary>
        <div class="fcc-pc-details-body">
            <p><?= $escape($text('details_intro')) ?></p>
            <div class="fcc-pc-table fcc-pc-technical" role="table" aria-label="<?= $escape($text('details_title')) ?>">
                <div class="fcc-pc-table-head" role="row"><span role="columnheader"><?= $escape($text('feature_header')) ?></span><span role="columnheader">Beginner</span><span role="columnheader">PRO</span></div>
                <?php foreach($details as $detail): ?>
                <div class="fcc-pc-table-row" role="row"><div class="fcc-pc-feature" role="rowheader"><?= $escape($text($detail[0])) ?></div><div class="fcc-pc-cell" role="cell"><span class="fcc-pc-mobile-label">Beginner</span><?= $escape($detailValue($beginner, $detail)) ?></div><div class="fcc-pc-cell fcc-pc-cell-pro" role="cell"><span class="fcc-pc-mobile-label">PRO</span><?= $escape($detailValue($proPlan, $detail)) ?></div></div>
                <?php endforeach ?>
            </div>
            <p class="fcc-pc-fine-print"><?= $escape($text('public_ai_help')) ?></p>
            <details class="fcc-pc-block-details"><summary><?= $escape($text('block_list_title')) ?></summary><p><?= $escape($text('block_list_intro')) ?></p><div class="fcc-pc-block-columns"><?php foreach(['Beginner' => $beginner, 'PRO' => $proPlan] as $planName => $blockPlan): $includedBlocks = $blocks($blockPlan); ?><section><h4><?= $escape($planName) ?></h4><?php if($includedBlocks): ?><ol><?php foreach($includedBlocks as $blockKey): ?><li><?= $escape($blockLabel($blockKey)) ?></li><?php endforeach ?></ol><?php else: ?><p><?= $escape($text('no_blocks')) ?></p><?php endif ?></section><?php endforeach ?></div></details>
        </div>
    </details>
    <section class="fcc-pc-cases" aria-labelledby="fcc-pc-cases-title">
        <div class="fcc-pc-section-heading"><h3 id="fcc-pc-cases-title"><?= $escape($text('cases_title')) ?></h3></div>
        <ol class="fcc-pc-case-grid"><?php for($i = 1; $i <= 3; $i++): ?><li><span class="fcc-pc-step"><?= $i ?></span><h4><?= $escape($text('case_' . $i . '_title')) ?></h4><p><?= $escape($text('case_' . $i . '_beginner')) ?></p><p class="fcc-pc-case-pro"><?= $escape($text('case_' . $i . '_pro')) ?></p></li><?php endfor ?></ol>
    </section>
    <section class="fcc-pc-conditions" aria-labelledby="fcc-pc-conditions-title">
        <div class="fcc-pc-section-heading"><h3 id="fcc-pc-conditions-title"><?= $escape($text('conditions_title')) ?></h3></div>
        <details class="fcc-pc-condition"><summary><?= $escape($text('coach_model_title')) ?></summary><div><p><?= $escape($text('coach_model_intro')) ?></p><p><?= $escape($text('coach_model_note')) ?></p></div></details>
        <?php foreach(['trial', 'ai', 'education', 'nfc'] as $condition): if($condition === 'trial' && $trialDays === 0) continue; if($condition === 'nfc' && (string) $proPlan->plan_id !== '5') continue; ?>
        <details class="fcc-pc-condition"<?= $condition === 'trial' ? ' open' : '' ?>><summary><?= $escape($text($condition . '_title')) ?></summary><div><p><?= $escape(($condition === 'trial' ? ($trialRequireCard ? $text('trial_card') . ' ' . $text('trial_text', ['days' => $trialDays]) : $text('trial_without_card', ['days' => $trialDays])) : $text($condition . '_text', ['days' => $trialDays]))) ?></p><?php if($condition === 'ai'): ?><p><?= $escape($text('ai_capacity')) ?></p><?php endif ?></div></details>
        <?php endforeach ?>
    </section>
    <?php endif ?>
</section>
