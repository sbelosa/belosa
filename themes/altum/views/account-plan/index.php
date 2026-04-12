<?php defined('ALTUMCODE') || die() ?>

<?php
$current_plan = $this->user->plan;
$current_plan_settings = $this->user->plan_settings ?? new \stdClass();
$suggested_plan = $data->suggested_plan ?? null;
$suggested_plan_settings = $suggested_plan->settings ?? null;
$active_paid_plans = $data->active_paid_plans ?? [];
$selected_currency = settings()->payment->currencies->{currency()} ?? null;

$get_plan_translation_value = static function($plan, string $field): string {
    if(!$plan) {
        return '';
    }

    $translation = $plan->translations->{\Altum\Language::$name} ?? null;

    if(is_object($translation) && isset($translation->{$field}) && trim((string) $translation->{$field}) !== '') {
        return (string) $translation->{$field};
    }

    return trim((string) ($plan->{$field} ?? ''));
};

$normalize_plan_description = static function(string $description): string {
    $description = trim($description);

    if($description === 'For Offline Forever Business + Physical Forever Business Club Card') {
        return 'For Offline Forever Business';
    }

    return $description;
};

$format_currency_amount = static function(float $amount) use ($selected_currency): string {
    if(!$selected_currency) {
        return nr($amount, 2);
    }

    $formatted_amount = nr($amount, ($selected_currency->currency_decimals ?? 2));
    $currency_label = ($selected_currency->display_as ?? 'currency_symbol') === 'currency_code'
        ? currency()
        : ($selected_currency->symbol ?? currency());

    if(($selected_currency->currency_placement ?? 'left') === 'right') {
        return $formatted_amount . ' ' . $currency_label;
    }

    return (($selected_currency->display_as ?? 'currency_symbol') === 'currency_code')
        ? $currency_label . ' ' . $formatted_amount
        : $currency_label . $formatted_amount;
};

$get_plan_price_variants = static function($plan) use ($selected_currency, $format_currency_amount, $get_plan_translation_value): array {
    $variants = [];

    if($plan && $selected_currency && isset($plan->prices) && is_object($plan->prices)) {
        foreach(['monthly', 'quarterly', 'biannual', 'annual', 'lifetime'] as $frequency) {
            $amount = (float) ($plan->prices->{$frequency}->{currency()} ?? 0);

            if($amount <= 0) {
                continue;
            }

            $variants[] = [
                'label' => l('plan.custom_plan.' . $frequency),
                'amount' => $format_currency_amount($amount),
                'frequency' => $frequency,
            ];
        }
    }

    if(empty($variants)) {
        $fallback_price = $get_plan_translation_value($plan, 'price');

        if($fallback_price !== '') {
            $variants[] = [
                'label' => '',
                'amount' => $fallback_price,
                'frequency' => 'custom',
            ];
        }
    }

    return $variants;
};

$get_limit_label = static function($value): string {
    $value = (int) $value;

    if($value === -1) {
        return l('global.unlimited');
    }

    if($value <= 0) {
        return l('global.none');
    }

    return nr($value);
};

$plan_has_feature = static function($plan_settings, string $feature_key): bool {
    $enabled_biolink_blocks = (array) ($plan_settings->enabled_biolink_blocks ?? []);

    return match($feature_key) {
        'ai_growth_plan_is_enabled' => !empty($plan_settings->ai_growth_plan_is_enabled),
        'fcc_ai_is_enabled' => !empty($plan_settings->fcc_ai_is_enabled),
        'fcc_coach_is_enabled' => !empty($plan_settings->fcc_coach_is_enabled),
        'lead_funnel' => !empty($enabled_biolink_blocks['lead_funnel']),
        'link_forever_shop' => !empty($enabled_biolink_blocks['link_forever_shop']),
        'link_forever_product' => !empty($enabled_biolink_blocks['link_forever_product']),
        'link_discount' => !empty($enabled_biolink_blocks['link_discount']),
        'link_save_contact' => !empty($enabled_biolink_blocks['link_save_contact']),
        'custom_html_whatsapp' => !empty($enabled_biolink_blocks['custom_html_whatsapp']),
        'funnels_analytics_is_enabled' => !empty($plan_settings->funnels_analytics_is_enabled) && !empty($enabled_biolink_blocks['lead_funnel']),
        default => false,
    };
};

$premium_feature_labels = [
    'ai_growth_plan_is_enabled' => l('global.plan_settings.ai_growth_plan_is_enabled'),
    'fcc_ai_is_enabled' => l('global.plan_settings.fcc_ai_is_enabled'),
    'fcc_coach_is_enabled' => l('global.plan_settings.fcc_coach_is_enabled'),
    'lead_funnel' => l('plan_features.forever.label.lead_funnel'),
    'funnels_analytics_is_enabled' => l('plan_features.forever.label.funnels_analytics_is_enabled'),
    'link_forever_shop' => l('plan_features.forever.label.link_forever_shop'),
    'link_discount' => l('plan_features.forever.label.link_discount'),
    'link_save_contact' => l('plan_features.forever.label.link_save_contact'),
    'custom_html_whatsapp' => l('plan_features.forever.label.custom_html_whatsapp'),
];

$premium_highlight_feature_labels = [
    'ai_growth_plan_is_enabled' => l('global.plan_settings.ai_growth_plan_is_enabled'),
    'lead_funnel' => l('plan_features.forever.label.lead_funnel'),
    'fcc_ai_is_enabled' => l('global.plan_settings.fcc_ai_is_enabled'),
    'custom_html_whatsapp' => l('plan_features.forever.label.custom_html_whatsapp'),
];

$get_plan_highlights = static function($plan_settings) use ($premium_highlight_feature_labels, $plan_has_feature, $get_limit_label): array {
    $highlights = [];

    foreach($premium_highlight_feature_labels as $feature_key => $feature_label) {
        if($plan_has_feature($plan_settings, $feature_key)) {
            $highlights[] = $feature_label;
        }
    }

    if(!empty($plan_settings->biolinks_limit)) {
        $highlights[] = sprintf('%s: %s', l('account_plan.premium.metric_apps'), $get_limit_label($plan_settings->biolinks_limit));
    }

    if(!empty($plan_settings->biolink_blocks_limit)) {
        $highlights[] = sprintf('%s: %s', l('account_plan.premium.metric_blocks'), $get_limit_label($plan_settings->biolink_blocks_limit));
    }

    return array_slice(array_values(array_unique($highlights)), 0, 6);
};

$get_unlocks = static function($current_settings, $target_settings) use ($premium_highlight_feature_labels, $plan_has_feature, $get_limit_label): array {
    $unlocks = [];

    foreach($premium_highlight_feature_labels as $feature_key => $feature_label) {
        if(!$plan_has_feature($current_settings, $feature_key) && $plan_has_feature($target_settings, $feature_key)) {
            $unlocks[] = $feature_label;
        }
    }

    if((int) ($target_settings->biolinks_limit ?? 0) > (int) ($current_settings->biolinks_limit ?? 0)) {
        $unlocks[] = sprintf('%s: %s', l('account_plan.premium.metric_apps'), $get_limit_label($target_settings->biolinks_limit ?? 0));
    }

    if((int) ($target_settings->biolink_blocks_limit ?? 0) > (int) ($current_settings->biolink_blocks_limit ?? 0)) {
        $unlocks[] = sprintf('%s: %s', l('account_plan.premium.metric_blocks'), $get_limit_label($target_settings->biolink_blocks_limit ?? 0));
    }

    return array_slice(array_values(array_unique($unlocks)), 0, 6);
};

$count_premium_tools = static function($plan_settings) use ($plan_has_feature, $premium_feature_labels): int {
    $count = 0;

    foreach(array_keys($premium_feature_labels) as $feature_key) {
        if($plan_has_feature($plan_settings, $feature_key)) {
            $count++;
        }
    }

    return $count;
};

$current_plan_name = $get_plan_translation_value($current_plan, 'name');
$current_plan_description = $normalize_plan_description($get_plan_translation_value($current_plan, 'description'));
$current_plan_prices = $get_plan_price_variants($current_plan);
$current_plan_highlights = $get_plan_highlights($current_plan_settings);
$current_premium_tools_count = $count_premium_tools($current_plan_settings);

$suggested_plan_name = $suggested_plan ? $get_plan_translation_value($suggested_plan, 'name') : '';
$suggested_plan_description = $suggested_plan ? $normalize_plan_description($get_plan_translation_value($suggested_plan, 'description')) : '';
$suggested_plan_prices = $suggested_plan ? $get_plan_price_variants($suggested_plan) : [];
$suggested_plan_highlights = $suggested_plan ? $get_plan_highlights($suggested_plan_settings) : [];
$suggested_plan_unlocks = $suggested_plan ? $get_unlocks($current_plan_settings, $suggested_plan_settings) : [];
$suggested_plan_trial_days = $suggested_plan ? (int) ($suggested_plan->trial_days ?? 0) : 0;
$can_start_suggested_plan_trial = $suggested_plan && $suggested_plan_trial_days > 0 && empty($this->user->plan_trial_done);
$suggested_plan_url = $suggested_plan ? url('pay/' . $suggested_plan->plan_id . (!empty($data->suggested_plan_code->code) ? '?code=' . $data->suggested_plan_code->code : '')) : '';
$suggested_plan_cta_label = '';

if($suggested_plan) {
    if($can_start_suggested_plan_trial) {
        $suggested_plan_cta_label = sprintf(l('account_plan.premium.trial_cta'), $suggested_plan_trial_days);
    } elseif($data->suggested_plan_code) {
        $suggested_plan_cta_label = sprintf(l('account_plan.upgrade.discount_button'), $data->suggested_plan_code->discount . '%');
    } else {
        $suggested_plan_cta_label = l('plans.choose');
    }
}

$current_plan_status_text = l('account_plan.premium.status_free');
$current_plan_status_icon = 'fa-star';

if($this->user->plan_id != 'free') {
    try {
        $expiration_date = new \DateTime($this->user->plan_expiration_date);
        $is_lifetime = $expiration_date > (new \DateTime())->modify('+10 years');

        if($is_lifetime) {
            $current_plan_status_text = l('account_plan.plan.lifetime');
            $current_plan_status_icon = 'fa-infinity';
        } elseif($this->user->payment_subscription_id) {
            $current_plan_status_text = sprintf(
                l('account_plan.plan.renews'),
                '<strong>' . \Altum\Date::get($this->user->plan_expiration_date, 2) . '</strong>',
                l('pay.custom_plan.' . $this->user->payment_processor),
                nr($this->user->payment_total_amount, 2),
                $this->user->payment_currency
            );
            $current_plan_status_icon = 'fa-rotate';
        } else {
            $current_plan_status_text = sprintf(l('account_plan.plan.expires'), '<strong>' . \Altum\Date::get($this->user->plan_expiration_date, 2) . '</strong>');
            $current_plan_status_icon = 'fa-hourglass-end';
        }
    } catch(\Throwable $exception) {
        $current_plan_status_text = l('global.active');
        $current_plan_status_icon = 'fa-check-circle';
    }
}

$visible_plans = [
    (string) ($this->user->plan_id ?? 'current') => $current_plan,
];

foreach($active_paid_plans as $plan_id => $plan) {
    if(!isset($visible_plans[(string) $plan_id])) {
        $visible_plans[(string) $plan_id] = $plan;
    }
}

$comparison_rows = [];

if($suggested_plan) {
    $comparison_rows = [
        [
            'label' => l('global.plan_settings.ai_growth_plan_is_enabled'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'ai_growth_plan_is_enabled'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'ai_growth_plan_is_enabled'),
        ],
        [
            'label' => l('global.plan_settings.fcc_ai_is_enabled'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'fcc_ai_is_enabled'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'fcc_ai_is_enabled'),
        ],
        [
            'label' => l('global.plan_settings.fcc_coach_is_enabled'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'fcc_coach_is_enabled'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'fcc_coach_is_enabled'),
        ],
        [
            'label' => l('plan_features.forever.label.lead_funnel'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'lead_funnel'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'lead_funnel'),
        ],
        [
            'label' => l('plan_features.forever.label.funnels_analytics_is_enabled'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'funnels_analytics_is_enabled'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'funnels_analytics_is_enabled'),
        ],
        [
            'label' => l('plan_features.forever.label.link_forever_shop'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'link_forever_shop'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'link_forever_shop'),
        ],
        [
            'label' => l('plan_features.forever.label.link_forever_product'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'link_forever_product'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'link_forever_product'),
        ],
        [
            'label' => l('plan_features.forever.label.link_discount'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'link_discount'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'link_discount'),
        ],
        [
            'label' => l('plan_features.forever.label.link_save_contact'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'link_save_contact'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'link_save_contact'),
        ],
        [
            'label' => l('account_plan.premium.metric_apps'),
            'type' => 'value',
            'current' => $get_limit_label($current_plan_settings->biolinks_limit ?? 0),
            'suggested' => $get_limit_label($suggested_plan_settings->biolinks_limit ?? 0),
        ],
        [
            'label' => l('account_plan.premium.metric_blocks'),
            'type' => 'value',
            'current' => $get_limit_label($current_plan_settings->biolink_blocks_limit ?? 0),
            'suggested' => $get_limit_label($suggested_plan_settings->biolink_blocks_limit ?? 0),
        ],
    ];
}
?>

<div class="container fcc-account-plan-page">
    <?= \Altum\Alerts::output_alerts() ?>

    <?= $this->views['account_header_menu'] ?>

    <section class="fcc-account-plan-hero">
        <div class="fcc-account-plan-hero__content">
            <div class="fcc-account-plan-eyebrow"><?= l('account_plan.premium.eyebrow') ?></div>
            <h1 class="fcc-account-plan-title"><?= sprintf(l('account_plan.header'), $current_plan_name) ?></h1>
            <p class="fcc-account-plan-subtitle"><?= l('account_plan.premium.subheader') ?></p>

            <div class="fcc-account-plan-badges">
                <span class="fcc-account-plan-badge is-active"><?= l('account_plan.premium.current_badge') ?>: <?= $current_plan_name ?></span>
                <?php if($suggested_plan): ?>
                    <span class="fcc-account-plan-badge is-recommended"><?= l('account_plan.premium.recommended_badge') ?>: <?= $suggested_plan_name ?></span>
                <?php endif ?>
                <span class="fcc-account-plan-badge">
                    <i class="fas fa-fw <?= $current_plan_status_icon ?> mr-1"></i>
                    <?= strip_tags($current_plan_status_text) ?>
                </span>
            </div>

            <?php if(!empty($visible_plans)): ?>
                <div class="fcc-account-plan-rail-label"><?= l('account_plan.premium.available_plans') ?></div>
                <div class="fcc-account-plan-rail">
                    <?php foreach($visible_plans as $plan_id => $plan): ?>
                        <?php $plan_name = $get_plan_translation_value($plan, 'name'); ?>
                        <div class="fcc-account-plan-rail-pill <?= (string) $plan_id === (string) $this->user->plan_id ? 'is-current' : '' ?> <?= $suggested_plan && (string) $plan_id === (string) $suggested_plan->plan_id ? 'is-recommended' : '' ?>">
                            <?= $plan_name ?>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <div class="fcc-account-plan-actions">
                <?php if(settings()->payment->is_enabled): ?>
                    <?php if($this->user->plan_id == 'free'): ?>
                        <a href="<?= url('plan/upgrade') ?>" class="btn btn-primary btn-lg fcc-account-plan-btn-primary">
                            <i class="fas fa-fw fa-arrow-up mr-1"></i> <?= l('account.plan.upgrade_plan') ?>
                        </a>
                    <?php else: ?>
                        <a href="<?= url('plan/renew') ?>" class="btn btn-primary btn-lg fcc-account-plan-btn-primary">
                            <i class="fas fa-fw fa-sync-alt mr-1"></i> <?= l('account.plan.renew_plan') ?>
                        </a>
                    <?php endif ?>
                <?php endif ?>

                <?php if($suggested_plan): ?>
                    <a href="<?= htmlspecialchars(url('account-plan') . '#fcc-plan-compare', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-light btn-lg fcc-account-plan-btn-secondary" data-scroll-target="#fcc-plan-compare">
                        <i class="fas fa-fw fa-layer-group mr-1"></i> <?= l('account_plan.premium.goto_compare') ?>
                    </a>
                <?php endif ?>
            </div>
        </div>

        <div class="fcc-account-plan-hero__metrics">
            <div class="fcc-account-plan-metric-card">
                <div class="fcc-account-plan-metric-label"><?= l('account_plan.premium.metric_tools') ?></div>
                <div class="fcc-account-plan-metric-value"><?= nr($current_premium_tools_count) ?></div>
                <div class="fcc-account-plan-metric-note"><?= l('plan_features.forever.group') ?></div>
            </div>

            <div class="fcc-account-plan-metric-card">
                <div class="fcc-account-plan-metric-label"><?= l('account_plan.premium.metric_ai') ?></div>
                <div class="fcc-account-plan-metric-value"><?= $plan_has_feature($current_plan_settings, 'ai_growth_plan_is_enabled') ? l('global.active') : l('global.no') ?></div>
                <div class="fcc-account-plan-metric-note"><?= l('global.plan_settings.ai_growth_plan_is_enabled') ?></div>
            </div>

            <div class="fcc-account-plan-metric-card">
                <div class="fcc-account-plan-metric-label"><?= l('account_plan.premium.metric_apps') ?></div>
                <div class="fcc-account-plan-metric-value"><?= $get_limit_label($current_plan_settings->biolinks_limit ?? 0) ?></div>
                <div class="fcc-account-plan-metric-note">FCC</div>
            </div>

            <div class="fcc-account-plan-metric-card">
                <div class="fcc-account-plan-metric-label"><?= l('account_plan.premium.metric_blocks') ?></div>
                <div class="fcc-account-plan-metric-value"><?= $get_limit_label($current_plan_settings->biolink_blocks_limit ?? 0) ?></div>
                <div class="fcc-account-plan-metric-note"><?= l('account_plan.premium.metric_blocks') ?></div>
            </div>
        </div>
    </section>

    <div class="row mt-4">
        <div class="col-12 <?= $suggested_plan ? 'col-xl-6' : 'col-xl-12' ?>">
            <section class="fcc-account-plan-card is-current">
                <div class="fcc-account-plan-card-header">
                    <div>
                        <h2 class="fcc-account-plan-card-title"><?= $current_plan_name ?></h2>
                        <p class="fcc-account-plan-card-description"><?= $current_plan_description ?: ($this->user->plan_id === 'free' ? l('account_plan.premium.status_free') : strip_tags($current_plan_status_text)) ?></p>
                    </div>

                    <?php if(!empty($current_plan_prices[0])): ?>
                        <div class="fcc-account-plan-price-box">
                            <div class="fcc-account-plan-price-main"><?= $current_plan_prices[0]['amount'] ?></div>
                            <?php if(!empty($current_plan_prices[0]['label'])): ?>
                                <div class="fcc-account-plan-price-label"><?= $current_plan_prices[0]['label'] ?></div>
                            <?php endif ?>
                        </div>
                    <?php endif ?>
                </div>

                <div class="fcc-account-plan-status-line">
                    <i class="fas fa-fw <?= $current_plan_status_icon ?>"></i>
                    <span><?= $current_plan_status_text ?></span>
                </div>

                <?php if(count($current_plan_prices) > 1): ?>
                    <div class="fcc-account-plan-price-chips">
                        <?php foreach(array_slice($current_plan_prices, 1) as $price_variant): ?>
                            <span class="fcc-account-plan-price-chip"><?= $price_variant['label'] ?>: <?= $price_variant['amount'] ?></span>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <div class="fcc-account-plan-card-section-title"><?= l('account_plan.premium.top_features') ?></div>
                <div class="fcc-account-plan-feature-pills">
                    <?php foreach($current_plan_highlights as $highlight): ?>
                        <span class="fcc-account-plan-feature-pill"><?= $highlight ?></span>
                    <?php endforeach ?>
                </div>

            </section>
        </div>

        <?php if($suggested_plan): ?>
            <div class="col-12 col-xl-6 mt-4 mt-xl-0">
                <section class="fcc-account-plan-card is-recommended">
                    <?php if($data->suggested_plan_code): ?>
                    <div class="fcc-account-plan-card-topline justify-content-end">
                        <?php if($data->suggested_plan_code): ?>
                            <span class="fcc-account-plan-discount-pill"><?= sprintf(l('account_plan.upgrade.header_discount'), $data->suggested_plan_code->discount . '%') ?></span>
                        <?php endif ?>
                    </div>
                    <?php endif ?>

                    <div class="fcc-account-plan-card-header">
                        <div>
                            <h2 class="fcc-account-plan-card-title"><?= $suggested_plan_name ?></h2>
                            <p class="fcc-account-plan-card-description"><?= $suggested_plan_description ?: sprintf(l('account_plan.upgrade.subheader'), '<strong>' . $suggested_plan_name . '</strong>') ?></p>
                        </div>

                        <?php if(!empty($suggested_plan_prices[0])): ?>
                            <div class="fcc-account-plan-price-box is-recommended">
                                <div class="fcc-account-plan-price-overline"><?= l('account_plan.premium.from') ?></div>
                                <div class="fcc-account-plan-price-main"><?= $suggested_plan_prices[0]['amount'] ?></div>
                                <?php if(!empty($suggested_plan_prices[0]['label'])): ?>
                                    <div class="fcc-account-plan-price-label"><?= $suggested_plan_prices[0]['label'] ?></div>
                                <?php endif ?>
                            </div>
                        <?php endif ?>
                    </div>

                    <?php if(count($suggested_plan_prices) > 1): ?>
                        <div class="fcc-account-plan-price-chips">
                            <?php foreach(array_slice($suggested_plan_prices, 1) as $price_variant): ?>
                                <span class="fcc-account-plan-price-chip is-recommended"><?= $price_variant['label'] ?>: <?= $price_variant['amount'] ?></span>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>

                    <div class="fcc-account-plan-card-section-title"><?= l('account_plan.premium.unlocks_title') ?></div>
                    <div class="fcc-account-plan-feature-pills">
                        <?php foreach(($suggested_plan_unlocks ?: $suggested_plan_highlights) as $highlight): ?>
                            <span class="fcc-account-plan-feature-pill is-recommended"><?= $highlight ?></span>
                        <?php endforeach ?>
                    </div>

                    <?php if(settings()->payment->is_enabled): ?>
                        <div class="fcc-account-plan-cta-wrap">
                            <a href="<?= $suggested_plan_url ?>" class="btn btn-primary btn-lg btn-block fcc-account-plan-btn-primary">
                                <?= $suggested_plan_cta_label ?> <i class="fas fa-fw fa-arrow-right ml-1"></i>
                            </a>

                            <?php if($can_start_suggested_plan_trial): ?>
                                <div class="fcc-account-plan-trial-note">
                                    <i class="fas fa-fw fa-gift mr-1"></i> <?= l('account_plan.premium.trial_note') ?>
                                </div>
                            <?php endif ?>
                        </div>
                    <?php endif ?>
                </section>
            </div>
        <?php endif ?>
    </div>

    <?php if($suggested_plan && !empty($comparison_rows)): ?>
        <section id="fcc-plan-compare" class="fcc-account-plan-section">
            <div class="fcc-account-plan-section-header">
                <div>
                    <h2 class="fcc-account-plan-section-title"><?= l('account_plan.premium.compare_title') ?></h2>
                    <p class="fcc-account-plan-section-subtitle"><?= l('account_plan.premium.compare_subtitle') ?></p>
                </div>
            </div>

            <div class="fcc-account-plan-compare-table">
                <div class="fcc-account-plan-compare-head fcc-account-plan-compare-feature"><?= l('account_plan.premium.matrix_feature') ?></div>
                <div class="fcc-account-plan-compare-head"><?= $current_plan_name ?></div>
                <div class="fcc-account-plan-compare-head is-recommended"><?= $suggested_plan_name ?></div>

                <?php foreach($comparison_rows as $comparison_row): ?>
                    <div class="fcc-account-plan-compare-cell fcc-account-plan-compare-feature-name"><?= $comparison_row['label'] ?></div>

                    <div class="fcc-account-plan-compare-cell">
                        <?php if($comparison_row['type'] === 'boolean'): ?>
                            <span class="fcc-account-plan-compare-badge <?= $comparison_row['current'] ? 'is-on' : 'is-off' ?>">
                                <i class="fas fa-fw <?= $comparison_row['current'] ? 'fa-check-circle' : 'fa-times-circle' ?> mr-1"></i>
                                <?= $comparison_row['current'] ? l('global.yes') : l('global.no') ?>
                            </span>
                        <?php else: ?>
                            <span class="fcc-account-plan-compare-value"><?= $comparison_row['current'] ?></span>
                        <?php endif ?>
                    </div>

                    <div class="fcc-account-plan-compare-cell is-recommended">
                        <?php if($comparison_row['type'] === 'boolean'): ?>
                            <span class="fcc-account-plan-compare-badge <?= $comparison_row['suggested'] ? 'is-on' : 'is-off' ?>">
                                <i class="fas fa-fw <?= $comparison_row['suggested'] ? 'fa-check-circle' : 'fa-times-circle' ?> mr-1"></i>
                                <?= $comparison_row['suggested'] ? l('global.yes') : l('global.no') ?>
                            </span>
                        <?php else: ?>
                            <span class="fcc-account-plan-compare-value"><?= $comparison_row['suggested'] ?></span>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            </div>

            <details class="fcc-account-plan-details fcc-account-plan-details--comparison">
                <summary><?= l('account_plan.premium.all_features') ?></summary>
                <div class="fcc-account-plan-details-content">
                    <div class="fcc-account-plan-details-grid">
                        <div class="fcc-account-plan-details-column">
                            <div class="fcc-account-plan-details-plan-label"><?= $current_plan_name ?></div>
                            <?= (new \Altum\View('partials/plan_features'))->run(['plan_settings' => $current_plan_settings]) ?>
                        </div>

                        <div class="fcc-account-plan-details-column is-recommended">
                            <div class="fcc-account-plan-details-plan-label is-recommended"><?= $suggested_plan_name ?></div>
                            <?= (new \Altum\View('partials/plan_features'))->run(['plan_settings' => $suggested_plan_settings]) ?>
                        </div>
                    </div>
                </div>
            </details>
        </section>
    <?php elseif(settings()->payment->is_enabled): ?>
        <section class="fcc-account-plan-section">
            <div class="fcc-account-plan-empty-state">
                <i class="fas fa-fw fa-crown"></i>
                <div>
                    <h2 class="fcc-account-plan-section-title mb-2"><?= l('account_plan.premium.recommended_plan_title') ?></h2>
                    <p class="fcc-account-plan-section-subtitle mb-0"><?= l('account_plan.premium.no_recommendation') ?></p>
                </div>
            </div>
        </section>
    <?php endif ?>

    <?php if($this->user->plan_id != 'free' && $this->user->payment_subscription_id): ?>
        <section class="fcc-account-plan-cancel-card">
            <div>
                <div class="fcc-account-plan-eyebrow"><?= l('account_plan.cancel.header') ?></div>
                <h2 class="fcc-account-plan-section-title"><?= l('account_plan.cancel.header') ?></h2>
                <p class="fcc-account-plan-section-subtitle mb-0"><?= l('account_plan.cancel.subheader') ?></p>
            </div>

            <a href="<?= url('account-plan/cancel_subscription' . \Altum\Csrf::get_url_query()) ?>" class="btn btn-outline-light fcc-account-plan-btn-secondary" onclick='return confirm(<?= json_encode(l('account_plan.cancel.confirm_message')) ?>)'>
                <?= l('account_plan.cancel.cancel') ?>
            </a>
        </section>
    <?php endif ?>
</div>

<style>
    .fcc-account-plan-page {
        padding-bottom: 3rem;
    }

    .fcc-account-plan-hero,
    .fcc-account-plan-card,
    .fcc-account-plan-section,
    .fcc-account-plan-cancel-card {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(110, 142, 196, 0.18);
        background:
            radial-gradient(circle at top right, rgba(72, 230, 210, 0.13), transparent 26%),
            radial-gradient(circle at bottom left, rgba(76, 132, 255, 0.12), transparent 30%),
            linear-gradient(180deg, rgba(14, 24, 45, 0.98), rgba(10, 17, 32, 0.98));
        box-shadow: 0 26px 70px rgba(3, 10, 24, 0.36);
        color: #ecf7ff;
    }

    .fcc-account-plan-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(300px, .95fr);
        gap: 1.5rem;
        padding: 2rem;
    }

    .fcc-account-plan-hero::before,
    .fcc-account-plan-card::before,
    .fcc-account-plan-section::before,
    .fcc-account-plan-cancel-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.035), transparent 26%);
        pointer-events: none;
    }

    .fcc-account-plan-eyebrow,
    .fcc-account-plan-rail-label {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        font-size: .76rem;
        text-transform: uppercase;
        letter-spacing: .16em;
        font-weight: 800;
        color: #8ceee2;
        margin-bottom: .95rem;
    }

    .fcc-account-plan-title {
        font-size: clamp(2rem, 4.2vw, 3.35rem);
        line-height: .98;
        letter-spacing: -.05em;
        margin-bottom: 1rem;
        color: #f7fbff;
    }

    .fcc-account-plan-subtitle,
    .fcc-account-plan-section-subtitle,
    .fcc-account-plan-card-description,
    .fcc-account-plan-metric-note,
    .fcc-account-plan-status-line,
    .fcc-account-plan-price-label {
        color: rgba(219, 232, 247, 0.78);
    }

    .fcc-account-plan-subtitle {
        max-width: 62ch;
        font-size: 1.04rem;
        line-height: 1.7;
        margin-bottom: 1.25rem;
    }

    .fcc-account-plan-badges,
    .fcc-account-plan-rail,
    .fcc-account-plan-actions,
    .fcc-account-plan-price-chips,
    .fcc-account-plan-feature-pills {
        display: flex;
        flex-wrap: wrap;
        gap: .75rem;
    }

    .fcc-account-plan-badge,
    .fcc-account-plan-rail-pill,
    .fcc-account-plan-price-chip,
    .fcc-account-plan-feature-pill,
    .fcc-account-plan-discount-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        padding: .72rem 1rem;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(153, 183, 225, 0.18);
        color: #f3fbff;
        font-size: .92rem;
        line-height: 1.2;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .fcc-account-plan-badge.is-active,
    .fcc-account-plan-rail-pill.is-current {
        background: linear-gradient(135deg, rgba(80, 219, 203, 0.22), rgba(78, 129, 255, 0.14));
        border-color: rgba(124, 232, 220, 0.4);
    }

    .fcc-account-plan-badge.is-recommended,
    .fcc-account-plan-rail-pill.is-recommended,
    .fcc-account-plan-price-chip.is-recommended,
    .fcc-account-plan-feature-pill.is-recommended,
    .fcc-account-plan-discount-pill {
        background: linear-gradient(135deg, rgba(255, 214, 110, 0.18), rgba(81, 226, 209, 0.18));
        border-color: rgba(255, 225, 138, 0.28);
    }

    .fcc-account-plan-rail-pill {
        font-weight: 700;
        padding: .68rem .95rem;
    }

    .fcc-account-plan-actions {
        margin-top: 1.45rem;
    }

    .fcc-account-plan-btn-primary,
    .fcc-account-plan-btn-secondary {
        border-radius: 18px;
        padding: .95rem 1.35rem;
        font-weight: 800;
        letter-spacing: -.01em;
    }

    .fcc-account-plan-btn-primary {
        border: 0;
        color: #062322;
        background: linear-gradient(135deg, #62f2df 0%, #4fd7cb 40%, #7ad0ff 100%);
        box-shadow: 0 18px 32px rgba(79, 215, 203, 0.22);
    }

    .fcc-account-plan-btn-primary:hover,
    .fcc-account-plan-btn-primary:focus {
        color: #04191c;
        transform: translateY(-1px);
        box-shadow: 0 22px 38px rgba(79, 215, 203, 0.28);
    }

    .fcc-account-plan-btn-secondary {
        border-color: rgba(181, 213, 248, 0.22);
        background: rgba(255,255,255,0.04);
        color: #eff9ff;
    }

    .fcc-account-plan-btn-secondary:hover,
    .fcc-account-plan-btn-secondary:focus {
        background: rgba(255,255,255,0.08);
        color: #fff;
        border-color: rgba(181, 213, 248, 0.34);
    }

    .fcc-account-plan-hero__metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .fcc-account-plan-metric-card {
        border-radius: 22px;
        padding: 1.2rem;
        background: linear-gradient(180deg, rgba(255,255,255,0.06), rgba(255,255,255,0.025));
        border: 1px solid rgba(161, 192, 235, 0.12);
        min-height: 148px;
    }

    .fcc-account-plan-metric-label {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .14em;
        color: rgba(178, 208, 239, 0.72);
        margin-bottom: .8rem;
        font-weight: 700;
    }

    .fcc-account-plan-metric-value {
        font-size: clamp(1.45rem, 3vw, 2.3rem);
        line-height: 1;
        font-weight: 900;
        letter-spacing: -.05em;
        margin-bottom: .55rem;
        color: #ffffff;
    }

    .fcc-account-plan-card {
        height: 100%;
        padding: 1.6rem;
    }

    .fcc-account-plan-card.is-recommended {
        border-color: rgba(125, 239, 224, 0.28);
        box-shadow: 0 30px 80px rgba(3, 12, 28, 0.42), 0 0 0 1px rgba(125, 239, 224, 0.08);
    }

    .fcc-account-plan-card-topline,
    .fcc-account-plan-card-header,
    .fcc-account-plan-cancel-card,
    .fcc-account-plan-empty-state {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
    }

    .fcc-account-plan-card-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .55rem .9rem;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(163, 192, 234, 0.16);
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .14em;
        font-weight: 800;
        color: #ddf8f3;
    }

    .fcc-account-plan-card-badge.is-recommended {
        background: linear-gradient(135deg, rgba(255, 214, 110, 0.22), rgba(79, 215, 203, 0.14));
        border-color: rgba(255, 225, 138, 0.28);
        color: #fff4c6;
    }

    .fcc-account-plan-card-title,
    .fcc-account-plan-section-title {
        font-size: clamp(1.45rem, 3vw, 2.15rem);
        line-height: 1.04;
        letter-spacing: -.04em;
        margin-bottom: .55rem;
        color: #f8fbff;
    }

    .fcc-account-plan-price-box {
        min-width: 118px;
        text-align: right;
        padding: .8rem .85rem;
        border-radius: 18px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(163, 192, 234, 0.14);
    }

    .fcc-account-plan-price-box.is-recommended {
        background: linear-gradient(180deg, rgba(255, 232, 168, 0.14), rgba(85, 221, 207, 0.08));
        border-color: rgba(255, 225, 138, 0.22);
    }

    .fcc-account-plan-price-overline {
        font-size: .66rem;
        text-transform: uppercase;
        letter-spacing: .14em;
        color: rgba(255, 242, 194, 0.78);
        margin-bottom: .15rem;
        font-weight: 800;
    }

    .fcc-account-plan-price-main {
        font-size: clamp(1.25rem, 2.2vw, 1.95rem);
        line-height: .98;
        font-weight: 900;
        letter-spacing: -.05em;
        color: #ffffff;
    }

    .fcc-account-plan-price-box .fcc-account-plan-price-main {
        display: inline-block;
        max-width: 100%;
        word-break: break-word;
    }

    .fcc-account-plan-status-line {
        display: flex;
        align-items: center;
        gap: .75rem;
        margin: 1rem 0 1.15rem;
        padding: .95rem 1rem;
        border-radius: 18px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(163, 192, 234, 0.12);
        font-size: .95rem;
    }

    .fcc-account-plan-status-line strong {
        color: #fff;
    }

    .fcc-account-plan-card-section-title {
        margin: 1.2rem 0 .9rem;
        font-size: .92rem;
        text-transform: uppercase;
        letter-spacing: .14em;
        color: rgba(179, 210, 239, 0.76);
        font-weight: 800;
    }

    .fcc-account-plan-feature-pill {
        justify-content: flex-start;
        text-align: left;
        padding: .8rem 1rem;
    }

    .fcc-account-plan-cta-wrap {
        margin-top: 1.2rem;
    }

    .fcc-account-plan-trial-note {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: .35rem;
        margin-top: .85rem;
        padding: .8rem .95rem;
        border-radius: 16px;
        border: 1px solid rgba(255, 225, 138, 0.18);
        background: linear-gradient(135deg, rgba(255, 214, 110, 0.12), rgba(79, 215, 203, 0.08));
        color: #fff2c8;
        font-size: .92rem;
        text-align: center;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04);
    }

    .fcc-account-plan-details {
        margin-top: 1.3rem;
        border-radius: 20px;
        border: 1px solid rgba(163, 192, 234, 0.12);
        background: rgba(3, 11, 24, 0.18);
        overflow: hidden;
    }

    .fcc-account-plan-details summary {
        list-style: none;
        cursor: pointer;
        padding: 1rem 1.1rem;
        font-weight: 700;
        color: #eef8ff;
    }

    .fcc-account-plan-details summary::-webkit-details-marker {
        display: none;
    }

    .fcc-account-plan-details-content {
        padding: 0 1.1rem 1.1rem;
    }

    .fcc-account-plan-details--comparison {
        margin-top: 1.2rem;
    }

    .fcc-account-plan-details-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
    }

    .fcc-account-plan-details-column {
        border-radius: 18px;
        padding: 1rem;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(163, 192, 234, 0.1);
    }

    .fcc-account-plan-details-column.is-recommended {
        background: linear-gradient(180deg, rgba(255, 232, 168, 0.08), rgba(79, 215, 203, 0.04));
        border-color: rgba(255, 225, 138, 0.18);
    }

    .fcc-account-plan-details-plan-label {
        display: inline-flex;
        align-items: center;
        margin-bottom: 1rem;
        border-radius: 999px;
        padding: .55rem .85rem;
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(163, 192, 234, 0.14);
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .14em;
        font-weight: 800;
        color: #eef9ff;
    }

    .fcc-account-plan-details-plan-label.is-recommended {
        background: linear-gradient(135deg, rgba(255, 214, 110, 0.18), rgba(79, 215, 203, 0.12));
        border-color: rgba(255, 225, 138, 0.22);
        color: #fff3cb;
    }

    .fcc-account-plan-details-content ul.list-style-none {
        margin-bottom: 0;
    }

    .fcc-account-plan-details-content ul.list-style-none li {
        color: rgba(228, 239, 251, 0.9);
    }

    .fcc-account-plan-section {
        margin-top: 1.75rem;
        padding: 1.6rem;
    }

    .fcc-account-plan-compare-table {
        margin-top: 1.25rem;
        display: grid;
        grid-template-columns: minmax(220px, 1.2fr) repeat(2, minmax(0, 1fr));
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid rgba(163, 192, 234, 0.12);
    }

    .fcc-account-plan-compare-head,
    .fcc-account-plan-compare-cell {
        padding: 1rem 1.05rem;
        background: rgba(255,255,255,0.03);
        border-bottom: 1px solid rgba(163, 192, 234, 0.08);
        border-right: 1px solid rgba(163, 192, 234, 0.08);
    }

    .fcc-account-plan-compare-head {
        font-size: .8rem;
        text-transform: uppercase;
        letter-spacing: .14em;
        font-weight: 800;
        color: rgba(191, 218, 246, 0.76);
        background: rgba(255,255,255,0.05);
    }

    .fcc-account-plan-compare-head.is-recommended,
    .fcc-account-plan-compare-cell.is-recommended {
        background: linear-gradient(180deg, rgba(255, 232, 168, 0.08), rgba(79, 215, 203, 0.04));
    }

    .fcc-account-plan-compare-feature,
    .fcc-account-plan-compare-feature-name {
        font-weight: 700;
        color: #f1f8ff;
    }

    .fcc-account-plan-compare-badge,
    .fcc-account-plan-compare-value {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 92px;
        border-radius: 999px;
        padding: .55rem .85rem;
        font-weight: 700;
    }

    .fcc-account-plan-compare-badge.is-on {
        background: rgba(89, 232, 203, 0.12);
        color: #b9fff0;
        border: 1px solid rgba(89, 232, 203, 0.18);
    }

    .fcc-account-plan-compare-badge.is-off {
        background: rgba(255,255,255,0.05);
        color: rgba(207, 222, 239, 0.72);
        border: 1px solid rgba(163, 192, 234, 0.12);
    }

    .fcc-account-plan-compare-value {
        background: rgba(118, 202, 255, 0.08);
        border: 1px solid rgba(118, 202, 255, 0.16);
        color: #d8f2ff;
    }

    .fcc-account-plan-empty-state,
    .fcc-account-plan-cancel-card {
        padding: 1.6rem;
    }

    .fcc-account-plan-empty-state i,
    .fcc-account-plan-cancel-card i {
        font-size: 1.5rem;
        color: #8ceee2;
    }

    .fcc-account-plan-cancel-card {
        margin-top: 1.75rem;
        align-items: center;
    }

    @media (max-width: 1199.98px) {
        .fcc-account-plan-hero {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 991.98px) {
        .fcc-account-plan-compare-table {
            grid-template-columns: 1fr;
        }

        .fcc-account-plan-compare-head.fcc-account-plan-compare-feature {
            display: none;
        }

        .fcc-account-plan-compare-head {
            border-right: 0;
        }

        .fcc-account-plan-compare-cell {
            border-right: 0;
        }

        .fcc-account-plan-compare-feature-name {
            padding-bottom: .35rem;
            border-top: 1px solid rgba(163, 192, 234, 0.1);
        }
    }

    @media (max-width: 767.98px) {
        .fcc-account-plan-hero,
        .fcc-account-plan-card,
        .fcc-account-plan-section,
        .fcc-account-plan-cancel-card {
            padding: 1.25rem;
            border-radius: 24px;
        }

        .fcc-account-plan-card-header,
        .fcc-account-plan-empty-state,
        .fcc-account-plan-cancel-card {
            flex-direction: column;
        }

        .fcc-account-plan-details-grid {
            grid-template-columns: 1fr;
        }

        .fcc-account-plan-price-box {
            width: 100%;
            text-align: left;
        }

        .fcc-account-plan-hero__metrics {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const scrollLinks = document.querySelectorAll('[data-scroll-target]');

        scrollLinks.forEach(link => {
            link.addEventListener('click', event => {
                const targetSelector = link.getAttribute('data-scroll-target');
                const targetElement = targetSelector ? document.querySelector(targetSelector) : null;

                if(!targetElement) {
                    return;
                }

                const currentUrl = new URL(window.location.href);
                const targetUrl = new URL(link.href, window.location.origin);

                if(currentUrl.pathname !== targetUrl.pathname) {
                    return;
                }

                event.preventDefault();
                targetElement.scrollIntoView({behavior: 'smooth', block: 'start'});
                window.history.replaceState({}, '', targetUrl.toString());
            });
        });
    });
</script>
