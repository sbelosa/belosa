<?php defined('ALTUMCODE') || die() ?>

<?php
$current_plan = $this->user->plan;
$current_plan_settings = $this->user->plan_settings ?? new \stdClass();
$suggested_plan = $data->suggested_plan ?? null;
$suggested_plan_settings = $suggested_plan->settings ?? null;
$billing_summary = (array) ($data->billing_summary ?? []);
$billing_state = (string) ($billing_summary['billing_state'] ?? 'healthy');
$billing_last_notification_stage = (string) ($billing_summary['last_notification_stage'] ?? '');
$billing_recovery = (array) ($data->billing_recovery ?? []);
$stripe_portal_available = !empty($data->stripe_portal_available);
$stripe_portal_url = $data->stripe_portal_url ?? url('account-plan/stripe_portal');
$stripe_payment_method_url = $data->stripe_payment_method_url ?? url('account-plan/stripe_payment_method');
$stripe_retry_payment_url = $data->stripe_retry_payment_url ?? url('account-plan/retry_stripe_payment' . \Altum\Csrf::get_url_query());
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
        'vip_funnel_core_is_enabled' => !empty($plan_settings->vip_funnel_core_is_enabled),
        'lead_funnel' => !empty($enabled_biolink_blocks['lead_funnel']),
        'link_forever_shop' => !empty($enabled_biolink_blocks['link_forever_shop']),
        'link_forever_webshop_reg' => !empty($enabled_biolink_blocks['link_forever_webshop_reg']),
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
    'vip_funnel_core_is_enabled' => l('global.plan_settings.vip_funnel_core_is_enabled'),
    'lead_funnel' => l('plan_features.forever.label.lead_funnel'),
    'funnels_analytics_is_enabled' => l('plan_features.forever.label.funnels_analytics_is_enabled'),
    'link_forever_shop' => l('plan_features.forever.label.link_forever_shop'),
    'link_forever_webshop_reg' => l('plan_features.forever.label.link_forever_webshop_reg'),
    'link_discount' => l('plan_features.forever.label.link_discount'),
    'link_save_contact' => l('plan_features.forever.label.link_save_contact'),
    'custom_html_whatsapp' => l('plan_features.forever.label.custom_html_whatsapp'),
];

$premium_highlight_feature_labels = [
    'ai_growth_plan_is_enabled' => l('global.plan_settings.ai_growth_plan_is_enabled'),
    'vip_funnel_core_is_enabled' => l('global.plan_settings.vip_funnel_core_is_enabled'),
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

$retry_window_until_label = !empty($billing_summary['grace_until']) ? \Altum\Date::get($billing_summary['grace_until'], 2) : l('global.none');
$show_billing_recovery_notice = in_array($billing_state, ['past_due', 'past_due_critical'], true);
$billing_recovery_title = $billing_state === 'past_due_critical' ? l('account_plan.billing.recovery_critical_title') : l('account_plan.billing.recovery_title');
$billing_recovery_subtitle = sprintf(l('account_plan.billing.recovery_subtitle'), $retry_window_until_label);
$billing_recovery_amount = trim((string) ($billing_recovery['invoice_amount'] ?? ''));
$billing_recovery_hosted_invoice_url = trim((string) ($billing_recovery['hosted_invoice_url'] ?? ''));
$billing_recovery_can_retry_now = !empty($billing_recovery['can_retry_now']);
$show_billing_pause_notice = $billing_state === 'access_revoked'
    && !empty($billing_summary['grace_until'])
    && $billing_last_notification_stage !== 'revoked'
    && (
        str_starts_with((string) ($this->user->payment_subscription_id ?? ''), 'sub_')
        || (string) ($this->user->payment_processor ?? '') === 'stripe'
        || !empty($billing_summary['stripe_status'])
    );

/* Billing and plan access are separate: a downgraded account can still have a subscription. */
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$has_subscription = trim((string) ($this->user->payment_subscription_id ?? '')) !== '';
$is_free_plan = (string) $this->user->plan_id === 'free';
$is_trial = $has_subscription && ($billing_summary['stripe_status'] ?? '') === 'trialing';
$expiration_date = null;
try {
    if(!empty($this->user->plan_expiration_date)) {
        $expiration_date = new \DateTimeImmutable($this->user->plan_expiration_date);
    }
} catch(\Throwable $exception) {
    /* Missing or invalid dates must not imply a new renewal date. */
}
$is_lifetime = !$is_free_plan && !$has_subscription && $expiration_date && $expiration_date > (new \DateTimeImmutable())->modify('+10 years');
$has_paid_access = !$is_free_plan && $expiration_date && $expiration_date > new \DateTimeImmutable();
$expiration_label = $expiration_date ? \Altum\Date::get($this->user->plan_expiration_date, 2) : l('global.na');
$renewal_state = $has_subscription ? 'on' : 'off';
if($has_subscription && ($show_billing_recovery_notice || $show_billing_pause_notice || $billing_state === 'access_revoked')) {
    $renewal_state = 'attention';
} elseif($is_trial) {
    $renewal_state = 'trial';
} elseif($is_free_plan && !$has_subscription) {
    $renewal_state = 'free';
} elseif($is_lifetime) {
    $renewal_state = 'lifetime';
} elseif(!$has_subscription && !$is_free_plan && $expiration_date && !$has_paid_access) {
    $renewal_state = 'expired';
}
$renewal_label = l('account_plan.manage.state_' . $renewal_state);
$access_date_label = l('account_plan.manage.' . ($has_subscription && $renewal_state === 'on' ? 'renews_on' : ($is_trial ? 'trial_until' : 'access_until')));
$display_date = $show_billing_recovery_notice ? $retry_window_until_label : $expiration_label;
if($show_billing_recovery_notice) $access_date_label = l('account_plan.manage.payment_deadline');
$cancellation_note = $has_paid_access && !$is_lifetime
    ? sprintf(l('account_plan.manage.cancel_access_until'), rtrim($expiration_label, '.'))
    : l('account_plan.manage.cancel_no_access');
$subscription_amount = $has_subscription && (float) ($this->user->payment_total_amount ?? 0) > 0 && !empty($this->user->payment_currency)
    ? nr($this->user->payment_total_amount, 2) . ' ' . $this->user->payment_currency
    : null;

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
            'label' => l('global.plan_settings.vip_funnel_core_is_enabled'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'vip_funnel_core_is_enabled'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'vip_funnel_core_is_enabled'),
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
            'label' => l('plan_features.forever.label.link_forever_webshop_reg'),
            'type' => 'boolean',
            'current' => $plan_has_feature($current_plan_settings, 'link_forever_webshop_reg'),
            'suggested' => $plan_has_feature($suggested_plan_settings, 'link_forever_webshop_reg'),
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

    <header class="fcc-account-plan-heading">
        <div class="fcc-account-plan-eyebrow"><?= l('account_plan.manage.eyebrow') ?></div>
        <h1><?= l('account_plan.manage.title') ?></h1>
        <p><?= l('account_plan.manage.subtitle') ?></p>
    </header>

    <section class="fcc-account-plan-hero" aria-labelledby="fcc-current-plan-title">
        <div class="fcc-account-plan-hero__content">
            <div class="fcc-account-plan-eyebrow"><?= l('account_plan.manage.current_plan') ?></div>
            <div class="fcc-account-plan-plan-name">
                <h2 id="fcc-current-plan-title"><?= $escape($current_plan_name) ?></h2>
                <span class="fcc-account-plan-badge is-<?= $renewal_state ?>"><?= $renewal_label ?></span>
            </div>
            <p class="fcc-account-plan-subtitle"><?= $escape($current_plan_description) ?></p>

            <dl class="fcc-account-plan-facts">
                <div>
                    <dt><?= l('account_plan.manage.renewal') ?></dt>
                    <dd><?= l('account_plan.manage.' . ($has_subscription ? 'automatic' : 'not_automatic')) ?></dd>
                </div>
                <?php if(!$is_free_plan && !$is_lifetime): ?>
                    <div>
                        <dt><?= $access_date_label ?></dt>
                        <dd><?= $escape($display_date) ?></dd>
                    </div>
                <?php elseif($is_lifetime): ?>
                    <div><dt><?= l('account_plan.manage.access_until') ?></dt><dd><?= l('account_plan.manage.lifetime') ?></dd></div>
                <?php endif ?>
                <?php if($subscription_amount): ?>
                    <div><dt><?= l('account_plan.manage.amount') ?></dt><dd><?= $escape($subscription_amount) ?></dd></div>
                <?php endif ?>
            </dl>

            <div class="fcc-account-plan-actions">
                <?php if(settings()->payment->is_enabled && !$has_subscription && !$is_lifetime): ?>
                    <a href="<?= url($is_free_plan ? 'plan/upgrade' : 'plan/renew') ?>" class="btn fcc-account-plan-btn-primary">
                        <i class="fas fa-fw fa-arrow-up mr-1" aria-hidden="true"></i><?= l($is_free_plan ? 'account.plan.upgrade_plan' : 'account.plan.renew_plan') ?>
                    </a>
                <?php endif ?>
                <a href="#fcc-plan-features" class="fcc-account-plan-text-link" data-scroll-target="#fcc-plan-features"><?= l('account_plan.manage.view_features') ?> <i class="fas fa-arrow-down ml-1" aria-hidden="true"></i></a>
                <?php if($suggested_plan): ?>
                    <a href="#fcc-plan-compare" class="fcc-account-plan-text-link" data-scroll-target="#fcc-plan-compare"><?= l('account_plan.premium.goto_compare') ?></a>
                <?php endif ?>
            </div>
        </div>

        <div class="fcc-account-plan-management" aria-labelledby="fcc-manage-title">
            <h2 id="fcc-manage-title"><i class="fas fa-fw fa-credit-card mr-2" aria-hidden="true"></i><?= l('account_plan.manage.header') ?></h2>
            <p><?= l('account_plan.manage.' . ($has_subscription ? 'active_help' : ($is_free_plan ? 'no_subscription' : ($is_lifetime ? 'lifetime_help' : 'off_help')))) ?></p>
            <div class="fcc-account-plan-management-links">
                <?php if($stripe_portal_available): ?>
                    <a href="<?= $escape($stripe_payment_method_url) ?>" class="btn fcc-account-plan-btn-secondary"><?= l('account_plan.manage.update_card') ?></a>
                <?php endif ?>
                <a href="<?= url('account-payments') ?>" class="btn fcc-account-plan-btn-secondary"><?= l('account_plan.manage.payments') ?></a>
            </div>

            <?php if($has_subscription): ?>
                <details class="fcc-account-plan-cancellation" id="fcc-cancel-subscription">
                    <summary class="btn fcc-account-plan-btn-danger"><i class="fas fa-fw fa-ban mr-1" aria-hidden="true"></i><?= l('account_plan.cancel.cancel') ?></summary>
                    <div class="fcc-account-plan-confirmation">
                        <h3><?= l('account_plan.manage.confirm_title') ?></h3>
                        <p><?= $escape($cancellation_note) ?></p>
                        <form method="post" action="<?= url('account-plan/cancel_subscription') ?>" data-cancel-subscription-form>
                            <input type="hidden" name="token" value="<?= $escape(\Altum\Csrf::get()) ?>">
                            <button type="submit" class="btn fcc-account-plan-btn-danger"><?= l('account_plan.manage.confirm_cancel') ?></button>
                            <button type="button" class="btn fcc-account-plan-btn-secondary" data-keep-subscription hidden><?= l('account_plan.manage.keep') ?></button>
                        </form>
                    </div>
                </details>
            <?php else: ?>
                <div class="fcc-account-plan-no-renewal"><i class="fas fa-fw fa-check-circle mr-1" aria-hidden="true"></i><?= l('account_plan.manage.' . ($is_free_plan ? 'nothing_to_cancel' : 'no_future_renewal')) ?></div>
            <?php endif ?>
        </div>
    </section>

    <?php if($show_billing_recovery_notice): ?>
        <section id="billing-recovery" class="fcc-account-plan-billing-alert <?= $billing_state === 'past_due_critical' ? 'is-critical' : '' ?>">
            <div class="fcc-account-plan-billing-alert__icon">
                <i class="fas fa-fw fa-credit-card"></i>
            </div>

            <div class="fcc-account-plan-billing-alert__content">
                <div class="fcc-account-plan-eyebrow mb-2"><?= l('account_plan.billing.recovery_eyebrow') ?></div>
                <h2 class="fcc-account-plan-section-title"><?= $billing_recovery_title ?></h2>
                <p class="fcc-account-plan-section-subtitle mb-0"><?= $billing_recovery_subtitle ?></p>

                <?php if($billing_recovery_amount): ?>
                    <div class="fcc-account-plan-billing-alert__meta">
                        <span><i class="fas fa-fw fa-receipt mr-1"></i><?= sprintf(l('account_plan.billing.invoice_amount'), $billing_recovery_amount) ?></span>
                    </div>
                <?php endif ?>

                <div class="fcc-account-plan-billing-steps">
                    <div><span>1</span><?= l('account_plan.billing.step_update_card') ?></div>
                    <div><span>2</span><?= l('account_plan.billing.step_retry_payment') ?></div>
                    <div><span>3</span><?= l('account_plan.billing.step_confirmation') ?></div>
                </div>
            </div>

            <div class="fcc-account-plan-billing-alert__actions">
                <?php if($stripe_portal_available): ?>
                    <a href="<?= htmlspecialchars($stripe_payment_method_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-lg fcc-account-plan-btn-primary">
                        <i class="fas fa-fw fa-credit-card mr-1"></i><?= l('account_plan.billing.update_card_button') ?>
                    </a>
                <?php endif ?>

                <?php if($billing_recovery_can_retry_now): ?>
                    <a href="<?= htmlspecialchars($stripe_retry_payment_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-light btn-lg fcc-account-plan-btn-secondary" onclick='return confirm(<?= json_encode(l('account_plan.billing.retry_confirm')) ?>)'>
                        <i class="fas fa-fw fa-sync-alt mr-1"></i><?= l('account_plan.billing.retry_button') ?>
                    </a>
                <?php endif ?>

                <?php if($billing_recovery_hosted_invoice_url): ?>
                    <a href="<?= htmlspecialchars($billing_recovery_hosted_invoice_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-lg fcc-account-plan-btn-secondary">
                        <i class="fas fa-fw fa-external-link-alt mr-1"></i><?= l('account_plan.billing.open_invoice_button') ?>
                    </a>
                <?php endif ?>
            </div>
        </section>
    <?php endif ?>

    <?php if($show_billing_pause_notice): ?>
        <section class="fcc-account-plan-section">
            <div class="fcc-account-plan-section-header">
                <div>
                    <div class="fcc-account-plan-eyebrow"><i class="fas fa-fw fa-credit-card mr-1"></i><?= l('account_plan.billing.paused_eyebrow') ?></div>
                    <h2 class="fcc-account-plan-section-title"><?= l('account_plan.billing.paused_title') ?></h2>
                    <p class="fcc-account-plan-section-subtitle mb-0"><?= sprintf(l('account_plan.billing.paused_subtitle'), $retry_window_until_label) ?></p>
                </div>

                <?php if($stripe_portal_available): ?>
                    <a href="<?= htmlspecialchars($stripe_portal_url, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-light fcc-account-plan-btn-secondary">
                        <?= l('account_plan.billing.portal_button') ?>
                    </a>
                <?php endif ?>
            </div>
        </section>
    <?php endif ?>

    <div class="row mt-4" id="fcc-plan-features">
        <div class="col-12 <?= $suggested_plan ? 'col-xl-6' : 'col-xl-12' ?>">
            <section class="fcc-account-plan-card is-current">
                <div class="fcc-account-plan-eyebrow"><?= $escape($current_plan_name) ?></div>
                <h2 class="fcc-account-plan-card-title"><?= l('account_plan.manage.included') ?></h2>
                <p class="fcc-account-plan-card-description"><?= l('account_plan.manage.features_help') ?></p>
                <div class="fcc-account-plan-usage">
                    <span><strong><?= nr($current_premium_tools_count) ?></strong> <?= l('account_plan.premium.metric_tools') ?></span>
                    <span><strong><?= $get_limit_label($current_plan_settings->biolinks_limit ?? 0) ?></strong> <?= l('account_plan.premium.metric_apps') ?></span>
                </div>
                <div class="fcc-account-plan-card-section-title"><?= l('account_plan.premium.top_features') ?></div>
                <div class="fcc-account-plan-feature-pills">
                    <?php foreach($current_plan_highlights as $highlight): ?>
                        <span class="fcc-account-plan-feature-pill"><?= $highlight ?></span>
                    <?php endforeach ?>
                </div>
                <details class="fcc-account-plan-details">
                    <summary><?= l('account_plan.manage.all_features') ?></summary>
                    <div class="fcc-account-plan-details-content">
                        <?= (new \Altum\View('partials/plan_features'))->run(['plan_settings' => $current_plan_settings]) ?>
                    </div>
                </details>
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
        <details id="fcc-plan-compare" class="fcc-account-plan-section fcc-account-plan-comparison">
            <summary>
                <span class="fcc-account-plan-section-title"><?= l('account_plan.premium.compare_title') ?></span>
                <span class="fcc-account-plan-section-subtitle"><?= l('account_plan.manage.compare_help') ?></span>
            </summary>
            <div class="fcc-account-plan-table-wrap">
                <table class="fcc-account-plan-compare-table">
                    <thead><tr>
                        <th scope="col"><?= l('account_plan.premium.matrix_feature') ?></th>
                        <th scope="col"><?= $escape($current_plan_name) ?></th>
                        <th scope="col"><?= $escape($suggested_plan_name) ?></th>
                    </tr></thead>
                    <tbody>
                        <?php foreach($comparison_rows as $comparison_row): ?>
                            <tr>
                                <th scope="row"><?= $comparison_row['label'] ?></th>
                                <?php foreach(['current', 'suggested'] as $column): ?>
                                    <td>
                                        <?php if($comparison_row['type'] === 'boolean'): ?>
                                            <span class="fcc-account-plan-compare-badge <?= $comparison_row[$column] ? 'is-on' : 'is-off' ?>">
                                                <i class="fas fa-fw <?= $comparison_row[$column] ? 'fa-check' : 'fa-minus' ?>" aria-hidden="true"></i>
                                                <?= $comparison_row[$column] ? l('global.yes') : l('global.no') ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="fcc-account-plan-compare-value"><?= $comparison_row[$column] ?></span>
                                        <?php endif ?>
                                    </td>
                                <?php endforeach ?>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
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
        </details>
    <?php endif ?>
</div>

<style>
    .fcc-account-plan-page {
        --plan-text: #f1f5fb;
        --plan-muted: #b3c0d3;
        --plan-border: rgba(165, 189, 220, .18);
        padding-bottom: 3rem;
    }
    .fcc-account-plan-page [id] { scroll-margin-top: 2rem; }
    .fcc-account-plan-page .btn { white-space: normal; min-height: 44px; }
    .fcc-account-plan-page :is(a, button, summary):focus-visible { outline: 3px solid #5bc8bb; outline-offset: 4px; }
    .fcc-account-plan-page [hidden] { display: none !important; }
    .fcc-account-plan-heading { margin: .5rem 0 1.5rem; }
    .fcc-account-plan-heading h1 { font-size: clamp(1.8rem, 3vw, 2.4rem); font-weight: 750; letter-spacing: -.035em; margin: 0 0 .5rem; }
    .fcc-account-plan-heading p { margin: 0; color: var(--gray-600, #64748b); line-height: 1.6; }
    .fcc-account-plan-eyebrow { color: #86e2d4; font-size: .72rem; text-transform: uppercase; letter-spacing: .12em; font-weight: 750; margin-bottom: .75rem; }
    .fcc-account-plan-heading .fcc-account-plan-eyebrow { color: #168578; }
    .fcc-account-plan-hero,
    .fcc-account-plan-card,
    .fcc-account-plan-section,
    .fcc-account-plan-billing-alert {
        border: 1px solid var(--plan-border);
        border-radius: 22px;
        background: #131f32;
        color: var(--plan-text);
    }
    .fcc-account-plan-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(300px, 1fr);
        padding: 2rem;
        gap: 2rem;
        background: radial-gradient(ellipse at 0 0, rgba(53, 173, 158, .13), transparent 60%), #101c2e;
        box-shadow: 0 12px 32px rgba(9, 20, 38, .12);
    }
    .fcc-account-plan-hero__content { min-width: 0; align-self: center; }
    .fcc-account-plan-plan-name { display: flex; align-items: center; flex-wrap: wrap; gap: .65rem 1rem; margin-bottom: .75rem; }
    .fcc-account-plan-plan-name h2 { color: #fff; font-size: clamp(1.8rem, 3.4vw, 2.65rem); letter-spacing: -.04em; font-weight: 750; margin: 0; overflow-wrap: anywhere; }
    .fcc-account-plan-badge { display: inline-flex; padding: .4rem .65rem; border-radius: 8px; background: #27344a; color: #dbe4ef; font-size: .75rem; font-weight: 650; }
    .fcc-account-plan-badge.is-on, .fcc-account-plan-badge.is-lifetime { background: #183e3d; color: #a9eddd; }
    .fcc-account-plan-badge.is-trial { background: #203c57; color: #bae3ff; }
    .fcc-account-plan-badge.is-attention { background: #4b3a25; color: #ffe0a0; }
    .fcc-account-plan-subtitle, .fcc-account-plan-card-description, .fcc-account-plan-section-subtitle { color: var(--plan-muted); font-size: .94rem; line-height: 1.65; }
    .fcc-account-plan-subtitle { margin-bottom: 1.25rem; }
    .fcc-account-plan-facts { display: flex; flex-wrap: wrap; gap: 1.15rem 1.75rem; padding: 1.25rem 0; margin: 0; border-top: 1px solid var(--plan-border); }
    .fcc-account-plan-facts dt { color: var(--plan-muted); font-size: .77rem; font-weight: 400; margin-bottom: .3rem; }
    .fcc-account-plan-facts dd { color: var(--plan-text); margin: 0; font-size: .95rem; font-weight: 650; }
    .fcc-account-plan-actions { display: flex; align-items: center; flex-wrap: wrap; gap: .5rem 1.25rem; margin-top: .5rem; }
    .fcc-account-plan-text-link { display: inline-flex; align-items: center; min-height: 44px; color: #9de3da; font-size: .84rem; font-weight: 650; text-decoration: underline; text-underline-offset: 4px; }
    .fcc-account-plan-text-link:hover { color: #d5fff9; }
    .fcc-account-plan-management { padding: 1.4rem; background: rgba(255,255,255,.045); border: 1px solid var(--plan-border); border-radius: 16px; align-self: start; min-width: 0; }
    .fcc-account-plan-management h2 { color: #fff; font-size: 1.05rem; font-weight: 700; line-height: 1.4; margin-bottom: .65rem; }
    .fcc-account-plan-management p { color: var(--plan-muted); font-size: .86rem; line-height: 1.6; margin-bottom: 1rem; }
    .fcc-account-plan-management-links { display: flex; flex-wrap: wrap; gap: .55rem; }
    .fcc-account-plan-page .fcc-account-plan-btn-primary,
    .fcc-account-plan-page .fcc-account-plan-btn-secondary,
    .fcc-account-plan-page .fcc-account-plan-btn-danger { border-radius: 10px; padding: .7rem 1rem; font-size: .86rem; font-weight: 650; line-height: 1.45; box-shadow: none; }
    .fcc-account-plan-page .fcc-account-plan-btn-primary { background: #84e0d1; border: 1px solid #84e0d1; color: #092925; }
    .fcc-account-plan-page .fcc-account-plan-btn-primary:hover { background: #a5ecdf; color: #092925; }
    .fcc-account-plan-page .fcc-account-plan-btn-secondary { border: 1px solid #50617a; background: transparent; color: #e9f0f9; }
    .fcc-account-plan-page .fcc-account-plan-btn-secondary:hover { background: #304059; color: #fff; }
    .fcc-account-plan-page .fcc-account-plan-btn-danger { border: 1px solid #b86d78; background: rgba(228, 105, 122, .09); color: #ffbdc5; }
    .fcc-account-plan-page .fcc-account-plan-btn-danger:hover { background: rgba(228, 105, 122, .2); color: #ffe7eb; }
    .fcc-account-plan-cancellation { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--plan-border); }
    .fcc-account-plan-cancellation > summary { display: block; width: 100%; cursor: pointer; text-align: center; list-style: none; }
    .fcc-account-plan-cancellation > summary::-webkit-details-marker { display: none; }
    .fcc-account-plan-confirmation { margin-top: 1rem; }
    .fcc-account-plan-confirmation h3 { font-size: 1rem; color: #fff; margin-bottom: .6rem; }
    .fcc-account-plan-confirmation form { display: flex; flex-wrap: wrap; gap: .65rem; }
    .fcc-account-plan-confirmation form .btn { flex: 1 1 160px; }
    .fcc-account-plan-no-renewal { display: flex; align-items: baseline; color: #a8d5c9; font-size: .81rem; line-height: 1.55; margin-top: 1rem; }
    .fcc-account-plan-card { height: 100%; padding: 1.6rem; }
    .fcc-account-plan-card.is-recommended { background: #18283a; border-color: #3d665f; }
    .fcc-account-plan-card-title, .fcc-account-plan-section-title { color: #f5f8fd; font-size: 1.3rem; font-weight: 700; line-height: 1.3; margin-bottom: .6rem; letter-spacing: -.02em; }
    .fcc-account-plan-card-header, .fcc-account-plan-card-topline { display: flex; justify-content: space-between; align-items: start; gap: 1rem; }
    .fcc-account-plan-card-header > div:first-child { min-width: 0; }
    .fcc-account-plan-usage { display: flex; flex-wrap: wrap; gap: .7rem 1.5rem; color: var(--plan-muted); font-size: .83rem; padding: .75rem 0; }
    .fcc-account-plan-usage strong { color: #f5f8fd; font-size: 1.25rem; font-weight: 650; margin-right: .3rem; }
    .fcc-account-plan-card-section-title { margin: 1rem 0 .75rem; color: #bdc9db; font-size: .78rem; font-weight: 650; }
    .fcc-account-plan-feature-pills, .fcc-account-plan-price-chips { display: flex; flex-wrap: wrap; gap: .55rem; }
    .fcc-account-plan-feature-pill { padding: .55rem .7rem; background: rgba(255,255,255,.035); border: 1px solid var(--plan-border); border-radius: 8px; color: #e1eaf6; font-size: .81rem; }
    .fcc-account-plan-price-chip, .fcc-account-plan-discount-pill { padding: .4rem .6rem; background: #263c4a; border-radius: 6px; font-size: .75rem; color: #d5eee8; }
    .fcc-account-plan-discount-pill { margin-bottom: .75rem; }
    .fcc-account-plan-price-box { flex-shrink: 0; text-align: right; }
    .fcc-account-plan-price-main { font-size: 1.5rem; color: #fff; font-weight: 750; letter-spacing: -.03em; }
    .fcc-account-plan-price-label, .fcc-account-plan-price-overline { color: var(--plan-muted); font-size: .74rem; }
    .fcc-account-plan-cta-wrap { margin-top: 1.25rem; }
    .fcc-account-plan-trial-note { margin-top: .7rem; color: #c7dbd2; line-height: 1.5; text-align: center; font-size: .79rem; }
    .fcc-account-plan-details { margin-top: 1.25rem; border-top: 1px solid var(--plan-border); }
    .fcc-account-plan-details > summary { cursor: pointer; color: #c4d1e3; padding: 1rem 0 .3rem; font-size: .85rem; font-weight: 650; }
    .fcc-account-plan-details-content { padding-top: 1rem; }
    .fcc-account-plan-details-content ul { margin-bottom: 0; }
    .fcc-account-plan-details-content li { color: #d5deeb; }
    .fcc-account-plan-details-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.5rem; }
    .fcc-account-plan-details-plan-label { color: #a5e0d5; font-size: .9rem; font-weight: 700; margin-bottom: 1rem; }
    .fcc-account-plan-section { margin-top: 1.5rem; padding: 1.6rem; }
    .fcc-account-plan-comparison > summary { cursor: pointer; list-style: none; position: relative; padding-right: 2rem; }
    .fcc-account-plan-comparison > summary::-webkit-details-marker { display: none; }
    .fcc-account-plan-comparison > summary::after { content: '+'; position: absolute; right: 0; top: 0; color: #9de3da; font-size: 1.6rem; }
    .fcc-account-plan-comparison[open] > summary::after { content: '−'; }
    .fcc-account-plan-comparison > summary > span { display: block; }
    .fcc-account-plan-comparison > summary .fcc-account-plan-section-subtitle { margin-bottom: 0; font-size: .85rem; }
    .fcc-account-plan-table-wrap { margin-top: 1.25rem; overflow-x: auto; border: 1px solid var(--plan-border); border-radius: 12px; }
    .fcc-account-plan-compare-table { width: 100%; border-collapse: collapse; font-size: .86rem; table-layout: fixed; }
    .fcc-account-plan-compare-table th, .fcc-account-plan-compare-table td { padding: .85rem; border-bottom: 1px solid var(--plan-border); text-align: center; overflow-wrap: anywhere; }
    .fcc-account-plan-compare-table thead th { background: #24364b; color: #fff; font-size: .8rem; }
    .fcc-account-plan-compare-table th:first-child { width: 48%; text-align: left; }
    .fcc-account-plan-compare-table tbody th { font-weight: 500; color: #d4dfed; }
    .fcc-account-plan-compare-table tbody tr:last-child > * { border-bottom: 0; }
    .fcc-account-plan-compare-table td:last-child { background: rgba(119, 211, 184, .045); }
    .fcc-account-plan-compare-badge { display: inline-flex; align-items: center; gap: .3rem; }
    .fcc-account-plan-compare-badge.is-on { color: #a2e5cf; }
    .fcc-account-plan-compare-badge.is-off { color: #aab8cd; }
    .fcc-account-plan-compare-value { color: #c9e6ff; font-weight: 600; }
    .fcc-account-plan-billing-alert { display: grid; grid-template-columns: 40px minmax(0,1fr); grid-template-areas: "icon content" ". actions"; gap: 1rem; margin-top: 1.5rem; padding: 1.5rem; border-color: #816742; background: #302b24; }
    .fcc-account-plan-billing-alert.is-critical { background: #32242a; border-color: #98616c; }
    .fcc-account-plan-billing-alert__icon { grid-area: icon; color: #f4d49c; font-size: 1.5rem; }
    .fcc-account-plan-billing-alert__content { grid-area: content; min-width: 0; }
    .fcc-account-plan-billing-alert__actions { grid-area: actions; display: flex; flex-wrap: wrap; gap: .6rem; }
    .fcc-account-plan-billing-alert__meta { margin-top: .8rem; font-size: .85rem; color: #f4d49c; }
    .fcc-account-plan-billing-steps { display: flex; flex-wrap: wrap; gap: .7rem 1.1rem; margin-top: 1rem; font-size: .8rem; color: #dfd7cc; }
    .fcc-account-plan-billing-steps span { display: inline-flex; align-items: center; justify-content: center; width: 23px; height: 23px; margin-right: .4rem; border-radius: 50%; background: rgba(255,255,255,.09); color: #f4d49c; }
    @media (max-width: 1199.98px) {
        .fcc-account-plan-hero { gap: 1.25rem; padding: 1.5rem; grid-template-columns: minmax(0,1fr) minmax(285px,1fr); }
        .fcc-account-plan-facts { gap: 1rem; }
    }
    @media (max-width: 767.98px) {
        .fcc-account-plan-heading { margin: .25rem 0 1rem; }
        .fcc-account-plan-heading p { font-size: .9rem; }
        .fcc-account-plan-hero { grid-template-columns: 1fr; padding: 1.15rem; gap: 1rem; }
        .fcc-account-plan-hero, .fcc-account-plan-card, .fcc-account-plan-section, .fcc-account-plan-billing-alert { border-radius: 16px; }
        .fcc-account-plan-management { padding: 1rem; }
        .fcc-account-plan-subtitle { margin-bottom: .75rem; }
        .fcc-account-plan-facts { padding: 1rem 0 .5rem; gap: .85rem 1.4rem; }
        .fcc-account-plan-management-links > .btn { flex: 1 1 130px; }
        .fcc-account-plan-card, .fcc-account-plan-section, .fcc-account-plan-billing-alert { padding: 1.15rem; }
        .fcc-account-plan-card-title, .fcc-account-plan-section-title { font-size: 1.15rem; }
        .fcc-account-plan-details-grid { grid-template-columns: 1fr; }
        .fcc-account-plan-compare-table { font-size: .77rem; }
        .fcc-account-plan-compare-table th, .fcc-account-plan-compare-table td { padding: .75rem .4rem; }
        .fcc-account-plan-compare-table th:first-child { width: 44%; }
        .fcc-account-plan-compare-badge { flex-direction: column; gap: .1rem; }
        .fcc-account-plan-billing-alert { grid-template-columns: 1fr; grid-template-areas: "icon" "content" "actions"; }
        .fcc-account-plan-billing-alert__actions > .btn { width: 100%; }
    }
    @media (max-width: 359.98px) {
        .fcc-account-plan-card-header { flex-direction: column; }
        .fcc-account-plan-price-box { text-align: left; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const page = document.querySelector('.fcc-account-plan-page');
        if(!page) return;

        page.querySelectorAll('[data-scroll-target]').forEach(link => {
            link.addEventListener('click', event => {
                const target = page.querySelector(link.getAttribute('data-scroll-target'));
                if(!target) return;
                event.preventDefault();
                if(target.tagName === 'DETAILS') target.open = true;
                target.scrollIntoView({behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start'});
                window.history.replaceState({}, '', link.href);
            });
        });
        if(window.location.hash === '#fcc-plan-compare') {
            const comparison = page.querySelector('#fcc-plan-compare');
            if(comparison) comparison.open = true;
        }

        const cancellation = page.querySelector('#fcc-cancel-subscription');
        const keepButton = page.querySelector('[data-keep-subscription]');
        if(cancellation && keepButton) {
            keepButton.hidden = false;
            keepButton.addEventListener('click', () => {
                cancellation.open = false;
                cancellation.querySelector('summary').focus();
            });
        }
        page.querySelectorAll('[data-cancel-subscription-form]').forEach(form => {
            form.addEventListener('submit', event => {
                if(form.dataset.submitting) {
                    event.preventDefault();
                    return;
                }
                form.dataset.submitting = 'true';
                form.setAttribute('aria-busy', 'true');
                form.querySelector('button[type="submit"]').disabled = true;
            });
        });
        window.addEventListener('pageshow', () => {
            page.querySelectorAll('[data-cancel-subscription-form]').forEach(form => {
                delete form.dataset.submitting;
                form.removeAttribute('aria-busy');
                form.querySelector('button[type="submit"]').disabled = false;
            });
        });
    });
</script>
