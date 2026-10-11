<?php
defined('ALTUMCODE') || die();
require_once APP_PATH . 'helpers/fcc_plan_comparison.php';

$locale = (string) ($data->locale ?? 'en');
$text = static function(string $key, array $parameters = []) use ($locale): string {
    return fcc_plan_comparison_text($key, $parameters, $locale);
};
$escape = static function($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); };
$rawSettings = $data->plan_settings ?? null;
if(is_string($rawSettings)) $rawSettings = json_decode($rawSettings);
$planSettings = is_object($rawSettings) || is_array($rawSettings) ? (array) $rawSettings : [];
$planId = (string) ($data->plan_id ?? '');
$planName = trim((string) ($data->plan_name ?? ''));
$activePro = !empty($data->active_pro);
$setting = static function(string $key) use ($planSettings) {
    return array_key_exists($key, $planSettings) ? $planSettings[$key] : null;
};
$truth = static function($value): ?bool {
    if($value === null) return null;
    if(is_array($value) || is_object($value)) return count(array_filter((array) $value)) > 0;
    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
};
$enabledBlocksRaw = $setting('enabled_biolink_blocks');
$blockSettings = is_array($enabledBlocksRaw) || is_object($enabledBlocksRaw) ? (array) $enabledBlocksRaw : null;
$blockValue = static function(string $key) use ($blockSettings, $truth): ?bool {
    return $blockSettings === null ? null : $truth($blockSettings[$key] ?? false);
};
$enabledBlocks = $blockSettings === null ? null : array_keys(array_filter($blockSettings, static function($enabled) use ($truth): bool { return $truth($enabled) === true; }));
$displayValue = static function($raw, string $type = 'boolean') use ($text, $truth): array {
    if($raw === null) return ['label' => $text('unknown'), 'state' => 'unknown'];
    if($type === 'number') {
        if(!is_numeric($raw)) return ['label' => $text('unknown'), 'state' => 'unknown'];
        return ['label' => (float) $raw === -1.0 ? $text('unlimited') : number_format((float) $raw, 0, '.', ''), 'state' => (float) $raw === 0.0 ? 'off' : 'on'];
    }
    if($type === 'count') {
        if(!is_array($raw) && !is_object($raw)) return ['label' => $text('unknown'), 'state' => 'unknown'];
        $count = count((array) $raw);
        return ['label' => (string) $count, 'state' => $count > 0 ? 'on' : 'off'];
    }
    if($type === 'export' && (is_array($raw) || is_object($raw))) {
        $formats = array_keys(array_filter((array) $raw, static function($enabled) use ($truth): bool { return $truth($enabled) === true; }));
        return ['label' => $formats ? implode(', ', array_map('strtoupper', $formats)) : $text('not_included'), 'state' => $formats ? 'on' : 'off'];
    }
    $enabled = $truth($raw);
    return ['label' => $text($enabled === null ? 'unknown' : ($enabled ? 'included' : 'not_included')), 'state' => $enabled === null ? 'unknown' : ($enabled ? 'on' : 'off')];
};
$detailValue = static function(array $detail) use ($setting, $blockValue, $displayValue, $planId, $truth): array {
    [$label, $key, $type] = $detail;
    if($type === 'block') return $displayValue($blockValue($key));
    if($type === 'analytics') {
        $lead = $blockValue('lead_funnel');
        $statistics = $truth($setting('statistics'));
        return $displayValue($lead === null || $statistics === null ? null : $lead && $statistics);
    }
    if($type === 'funnel') {
        $funnel = $truth($setting($key));
        return $displayValue($planId === '5' ? true : $funnel);
    }
    return $displayValue($setting($key), $type);
};
$metrics = [
    ['applications', 'biolinks_limit', 'number'],
    ['blocks_per_app', 'biolink_blocks_limit', 'number'],
    ['qr_codes', 'qr_codes_limit', 'number'],
    ['analytics_days', 'track_links_retention', 'number'],
];
$groups = [
    'current_settings_apps' => [
        ['short_links', 'links_limit', 'number'],
        ['shop_block', 'link_forever_shop', 'block'],
        ['registration_block', 'link_forever_webshop_reg', 'block'],
        ['product_block', 'link_forever_product', 'block'],
        ['contact_block', 'link_save_contact', 'block'],
        ['lead_block', 'lead_funnel', 'block'],
        ['whatsapp_block', 'custom_html_whatsapp', 'block'],
        ['discount_block', 'link_discount', 'block'],
    ],
    'current_settings_ai' => [
        ['general_coach', 'fcc_coach_is_enabled', 'boolean'],
        ['public_ai', 'fcc_ai_is_enabled', 'boolean'],
        ['growth_plan', 'ai_growth_plan_is_enabled', 'boolean'],
        ['chatbot_block', 'custom_html_chatbot', 'block'],
    ],
    'current_settings_tools' => [
        ['statistics', 'statistics', 'boolean'],
        ['funnel', 'vip_funnel_core_is_enabled', 'funnel'],
        ['funnel_analytics', 'lead_funnel', 'analytics'],
        ['password_protection', 'password', 'boolean'],
        ['custom_url', 'custom_url', 'boolean'],
        ['export', 'export', 'export'],
        ['app_switcher_block', 'link_app_switcher', 'block'],
        ['home_screen_block', 'link_homescreen_android', 'block'],
    ],
    'current_settings_design' => [
        ['design_themes', 'biolinks_themes', 'count'],
        ['design_templates', 'biolinks_templates', 'count'],
        ['font_options', 'fonts', 'boolean'],
        ['seo', 'seo', 'boolean'],
        ['utm', 'utm', 'boolean'],
        ['custom_css', 'custom_css_is_enabled', 'boolean'],
        ['custom_js', 'custom_js_is_enabled', 'boolean'],
    ],
];
$blockLabels = [
    'link_forever_shop' => 'shop_block', 'link_forever_webshop_reg' => 'registration_block',
    'link_forever_product' => 'product_block', 'link_save_contact' => 'contact_block',
    'lead_funnel' => 'lead_block', 'custom_html_whatsapp' => 'whatsapp_block',
    'link_discount' => 'discount_block', 'link_app_switcher' => 'app_switcher_block',
    'link_homescreen_android' => 'home_screen_block', 'custom_html_chatbot' => 'chatbot_block',
];
$blockLabel = static function(string $key) use ($text, $blockLabels): string {
    if(isset($blockLabels[$key])) return $text($blockLabels[$key]);
    $translationKey = 'link.biolink.blocks.' . strtolower($key);
    $label = function_exists('l') ? (string) l($translationKey) : '';
    return $label !== '' && $label !== $translationKey ? strip_tags($label) : ucfirst(str_replace('_', ' ', $key));
};
$coachEnabled = $truth($setting('fcc_coach_is_enabled')) === true;
$coachModelKey = $coachEnabled && $activePro ? 'coach_pro_model' : ($coachEnabled && $planId !== '5' && $truth($setting('ai_growth_plan_is_enabled')) === false ? 'coach_basic_model' : null);
$platformSettings = function_exists('settings') ? settings() : null;
$platformGroup = static function(string $group) use ($platformSettings): array {
    return (array) (((array) $platformSettings)[$group] ?? []);
};
$gate = static function(string $group, string $key) use ($platformGroup): bool {
    return !empty($platformGroup($group)[$key]);
};
$pluginActive = static function(string $name): bool {
    return class_exists('Altum\\Plugin') && \Altum\Plugin::is_active($name);
};
$configuredFeatureVisibility = (array) ($platformGroup('payment')['plan_features'] ?? []);
$hiddenFeatures = [
    'files_limit', 'vcards_limit', 'static_limit', 'domains_limit', 'notification_handlers_limit',
    'projects_limit', 'splash_pages_limit', 'removable_branding', 'custom_branding',
    'branded_button_is_enabled', 'email_reports_is_enabled',
];
$featureVisible = static function(string $key) use ($configuredFeatureVisibility, $hiddenFeatures, $truth): bool {
    if(in_array($key, $hiddenFeatures, true)) return false;
    return !array_key_exists($key, $configuredFeatureVisibility) || $truth($configuredFeatureVisibility[$key]) === true;
};
$advancedLabel = static function(string $key, string $type = 'boolean', ?string $translationKey = null) use ($blockLabel): string {
    if(strpos($key, 'enabled_biolink_block__') === 0) return $blockLabel(substr($key, strlen('enabled_biolink_block__')));
    $translationKey = $translationKey ?? 'global.plan_settings.' . $key;
    $label = function_exists('l') ? (string) l($translationKey) : '';
    if($label === '' || $label === $translationKey) return ucfirst(str_replace('_', ' ', $key));
    if(in_array($type, ['number', 'count', 'bytes', 'percentage'], true)) $label = sprintf($label, '');
    return trim(strip_tags($label));
};
$advancedFeatures = [];
$addAdvanced = static function(string $key, string $type = 'boolean', bool $enabled = true, ?string $translationKey = null, $rawOverride = null, bool $hasOverride = false) use (&$advancedFeatures, $featureVisible, $advancedLabel, $setting, $blockValue, $displayValue, $locale, $text): void {
    if(!$enabled || !$featureVisible($key)) return;
    $raw = $hasOverride ? $rawOverride : (strpos($key, 'enabled_biolink_block__') === 0 ? $blockValue(substr($key, strlen('enabled_biolink_block__'))) : $setting($key));
    if($type === 'text') {
        $value = ['label' => $raw === null || $raw === '' ? $text('unknown') : (string) $raw, 'state' => $raw === null || $raw === '' ? 'unknown' : 'on'];
    } else {
        $value = $displayValue($raw, in_array($type, ['bytes', 'percentage'], true) ? 'number' : $type);
        if(is_numeric($raw) && $type === 'bytes' && (float) $raw !== -1.0) $value['label'] = function_exists('get_formatted_bytes') ? strip_tags(get_formatted_bytes((float) $raw * 1000 * 1000)) : $value['label'] . ' MB';
        if(is_numeric($raw) && $type === 'percentage') {
            $decimalComma = in_array($locale, ['hr', 'sl', 'de', 'es', 'fr', 'pl', 'sr', 'sq', 'cnr', 'sv', 'hu', 'ro'], true);
            $value['label'] = rtrim(rtrim(number_format((float) $raw, 2, $decimalComma ? ',' : '.', ''), '0'), $decimalComma ? ',' : '.') . '%';
        }
    }
    $advancedFeatures[] = ['key' => $key, 'label' => $advancedLabel($key, $type, $translationKey), 'value' => $value];
};
foreach(['deep_links', 'temporary_url_is_enabled', 'cloaking_is_enabled', 'app_linking_is_enabled', 'targeting_is_enabled', 'sensitive_content', 'no_ads'] as $key) $addAdvanced($key);
foreach(['dofollow_is_enabled', 'leap_link'] as $key) $addAdvanced($key, 'boolean', $gate('links', 'biolinks_is_enabled'));
foreach(['link_back', 'custom_html_chatbot_pets', 'vip_funnel_hub'] as $key) $addAdvanced('enabled_biolink_block__' . $key, 'boolean', $gate('links', 'biolinks_is_enabled'));
$addAdvanced('events_limit', 'number', $gate('links', 'events_is_enabled'));
$addAdvanced('pixels_limit', 'number', $gate('links', 'pixels_is_enabled'));
$addAdvanced('links_bulk_limit', 'number', $gate('links', 'shortener_is_enabled'));
$addAdvanced('qr_codes_bulk_limit', 'number', $gate('codes', 'qr_codes_is_enabled'));
$addAdvanced('payment_processors_limit', 'number', $gate('links', 'biolinks_is_enabled') && $pluginActive('payment-blocks'));
$addAdvanced('signatures_limit', 'number', $pluginActive('email-signatures') && $gate('signatures', 'is_enabled'));
$addAdvanced('teams_limit', 'number', $pluginActive('teams'));
if($featureVisible('teams_limit')) $addAdvanced('team_members_limit', 'number', $pluginActive('teams'));
$addAdvanced('affiliate_commission_percentage', 'percentage', $pluginActive('affiliate') && $gate('affiliate', 'is_enabled'));
$addAdvanced('additional_domains', 'count', $gate('links', 'additional_domains_is_enabled'));
if($gate('links', 'splash_page_is_enabled') && $featureVisible('force_splash_page_on_link')) {
    $forcedSplashSettings = array_filter($planSettings, static function($key): bool { return strpos((string) $key, 'force_splash_page_on_') === 0; }, ARRAY_FILTER_USE_KEY);
    $noForcedSplash = $forcedSplashSettings ? !in_array(true, array_map($truth, $forcedSplashSettings), true) : null;
    $addAdvanced('force_splash_page_on_link', 'boolean', true, 'global.plan_settings.no_forced_splash_page', $noForcedSplash, true);
}
$addAdvanced('api_is_enabled', 'boolean', $gate('main', 'api_is_enabled'));
$addAdvanced('white_labeling_is_enabled', 'boolean', $gate('main', 'white_labeling_is_enabled'));
$addAdvanced('custom_pwa_is_enabled', 'boolean', $pluginActive('pwa') && $gate('pwa', 'is_enabled'));
if($pluginActive('aix')) {
    foreach(['documents_per_month_limit', 'words_per_month_limit'] as $key) $addAdvanced($key, 'number', $gate('aix', 'documents_is_enabled'));
    $addAdvanced('images_per_month_limit', 'number', $gate('aix', 'images_is_enabled'));
    $addAdvanced('transcriptions_per_month_limit', 'number', $gate('aix', 'transcriptions_is_enabled'));
    $addAdvanced('transcriptions_file_size_limit', 'bytes', $gate('aix', 'transcriptions_is_enabled'));
    foreach(['chats_per_month_limit', 'chat_messages_per_chat_limit'] as $key) $addAdvanced($key, 'number', $gate('aix', 'chats_is_enabled'));
    if($gate('aix', 'documents_is_enabled') && $featureVisible('documents_model') && method_exists('Altum\\Plugin', 'get')) {
        $modelFile = \Altum\Plugin::get('aix')->path . 'includes/ai_text_models.php';
        if(is_file($modelFile)) {
            $documentModels = require $modelFile;
            $modelId = $setting('documents_model');
            if(is_scalar($modelId) && isset($documentModels[$modelId]['name'])) $addAdvanced('documents_model', 'text', true, 'documents.model', strip_tags((string) $documentModels[$modelId]['name']), true);
        }
    }
}
?>
<section class="fcc-pc fcc-pc-account" id="fcc-current-plan-settings" aria-labelledby="fcc-current-plan-title">
    <header class="fcc-pc-account-heading">
        <div class="fcc-pc-account-heading-copy">
            <span class="fcc-pc-eyebrow"><?= $escape($text('current_plan')) ?></span>
            <h2 id="fcc-current-plan-title"><?= $escape($text('current_settings_title')) ?></h2>
            <p><?= $escape($text('current_settings_intro')) ?></p>
        </div>
        <?php if($planName !== ''): ?><span class="fcc-pc-account-plan"><?= $escape($planName) ?></span><?php endif ?>
    </header>
    <dl class="fcc-pc-account-metrics">
        <?php foreach($metrics as $metric): $metricValue = $detailValue($metric); ?>
        <div class="fcc-pc-account-metric fcc-pc-account-metric-<?= $escape($metricValue['state']) ?>">
            <dt><?= $escape($text($metric[0])) ?></dt>
            <dd<?= !is_numeric($metricValue['label']) ? ' class="fcc-pc-account-metric-text"' : '' ?>><?= $escape($metricValue['label']) ?></dd>
        </div>
        <?php endforeach ?>
    </dl>
    <?php if(!$planSettings): ?><p class="fcc-pc-account-note" role="status"><?= $escape($text('unknown')) ?></p><?php endif ?>
    <div class="fcc-pc-account-groups">
        <?php foreach($groups as $groupKey => $details): ?>
        <section class="fcc-pc-account-group" aria-labelledby="fcc-<?= $escape($groupKey) ?>">
            <h3 id="fcc-<?= $escape($groupKey) ?>"><?= $escape($text($groupKey)) ?></h3>
            <dl class="fcc-pc-account-features">
                <?php foreach($details as $detail): $featureValue = $detailValue($detail); ?>
                <div class="fcc-pc-account-feature fcc-pc-account-feature-<?= $escape($featureValue['state']) ?>">
                    <dt><?= $escape($text($detail[0])) ?><?php if($detail[0] === 'general_coach' && $coachModelKey !== null): ?><span class="fcc-pc-account-model"><?= $escape($text($coachModelKey)) ?></span><?php endif ?></dt>
                    <dd>
                        <svg class="fcc-pc-account-status" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7"/><?php if($featureValue['state'] === 'on'): ?><path d="m4.5 8 2.2 2.2 4.8-4.8"/><?php elseif($featureValue['state'] === 'off'): ?><path d="m5.5 5.5 5 5m0-5-5 5"/><?php else: ?><path d="M6.1 6a2 2 0 0 1 3.8.8c0 1.3-1.9 1.3-1.9 2.5M8 11.5h.01"/><?php endif ?></svg>
                        <span><?= $escape($featureValue['label']) ?></span>
                    </dd>
                </div>
                <?php endforeach ?>
            </dl>
            <?php if($groupKey === 'current_settings_ai' && $coachModelKey === 'coach_pro_model'): ?><p class="fcc-pc-account-note"><?= $escape($text('coach_model_note')) ?></p><?php endif ?>
        </section>
        <?php endforeach ?>
    </div>
    <details class="fcc-pc-account-blocks">
        <summary><span class="fcc-pc-account-blocks-title"><?= $escape($text('current_settings_blocks')) ?></span><span class="fcc-pc-account-block-count"><?= $escape($enabledBlocks === null ? $text('unknown') : count($enabledBlocks)) ?></span><svg class="fcc-pc-account-chevron" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m5 7.5 5 5 5-5"/></svg></summary>
        <div class="fcc-pc-account-blocks-body">
            <?php if($enabledBlocks): ?><ul class="fcc-pc-account-block-chips"><?php foreach($enabledBlocks as $blockKey): ?><li><?= $escape($blockLabel($blockKey)) ?></li><?php endforeach ?></ul><?php else: ?><p class="fcc-pc-account-note"><?= $escape($enabledBlocks === null ? $text('unknown') : $text('no_blocks')) ?></p><?php endif ?>
        </div>
    </details>
    <?php if($advancedFeatures): ?>
    <details class="fcc-pc-account-blocks fcc-pc-account-advanced">
        <summary><span class="fcc-pc-account-blocks-title"><?= $escape($text('current_settings_advanced')) ?></span><svg class="fcc-pc-account-chevron" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="m5 7.5 5 5 5-5"/></svg></summary>
        <div class="fcc-pc-account-blocks-body">
            <dl class="fcc-pc-account-features fcc-pc-account-advanced-features">
                <?php foreach($advancedFeatures as $advancedFeature): $featureValue = $advancedFeature['value']; ?>
                <div class="fcc-pc-account-feature fcc-pc-account-feature-<?= $escape($featureValue['state']) ?>" data-plan-setting="<?= $escape($advancedFeature['key']) ?>">
                    <dt><?= $escape($advancedFeature['label']) ?></dt>
                    <dd>
                        <svg class="fcc-pc-account-status" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="7"/><?php if($featureValue['state'] === 'on'): ?><path d="m4.5 8 2.2 2.2 4.8-4.8"/><?php elseif($featureValue['state'] === 'off'): ?><path d="m5.5 5.5 5 5m0-5-5 5"/><?php else: ?><path d="M6.1 6a2 2 0 0 1 3.8.8c0 1.3-1.9 1.3-1.9 2.5M8 11.5h.01"/><?php endif ?></svg>
                        <span><?= $escape($featureValue['label']) ?></span>
                    </dd>
                </div>
                <?php endforeach ?>
            </dl>
        </div>
    </details>
    <?php endif ?>
    <p class="fcc-pc-account-availability"><?= $escape($text('availability_note')) ?></p>
</section>
