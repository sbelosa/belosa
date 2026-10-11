<?php
defined('ALTUMCODE') || die();

function fcc_plan_comparison_text(string $key, array $params = [], ?string $locale = null): string {
    static $copy;
    if($copy === null) {
        $copy = require APP_PATH . 'config/fcc_plan_comparison_copy.php';
        $other = APP_PATH . 'config/fcc_plan_comparison_locales.php';
        if(is_file($other)) $copy = array_replace($copy, require $other);
    }
    $locale = $locale ?? (function_exists('fcc_locale') ? fcc_locale() : 'en');
    $value = (string) ($copy[$locale][$key] ?? $copy['en'][$key] ?? $key);
    $replace = [];
    foreach($params as $name=>$replacement) $replace['{' . $name . '}'] = (string) $replacement;
    return strtr($value, $replace);
}

/** Read catalogue data only. No account, billing or catalogue writes. */
function fcc_plan_comparison_model($plans = null, $user = null, array $accountState = []): array {
    $plans = $plans ?? (new \Altum\Models\Plan())->get_plans();
    $plans = is_array($plans) ? $plans : (array) $plans;
    $enabled = static function($plan): bool {
        return is_object($plan) && (int) ($plan->status ?? 0) === 1;
    };
    $growth = static function($plan): bool {
        return in_array($plan->settings->ai_growth_plan_is_enabled ?? false, [true, 1, '1'], true);
    };
    $beginner = $pro = null;
    foreach($plans as $plan) {
        if(!$enabled($plan)) continue;
        if((string) ($plan->plan_id ?? '') === '2' && !$growth($plan)) $beginner = $plan;
        if((string) ($plan->plan_id ?? '') === '5' && $growth($plan)) $pro = $plan;
    }
    foreach($plans as $plan) {
        if(!$enabled($plan)) continue;
        if(!$pro && $growth($plan)) $pro = $plan;
        if(!$beginner && !$growth($plan) && preg_match('/beginner/i', (string) ($plan->name ?? ''))) $beginner = $plan;
    }
    $code = (string) currency();
    $prices = [];
    foreach(['monthly'=>30, 'quarterly'=>90, 'biannual'=>180, 'annual'=>365, 'lifetime'=>0] as $frequency=>$days) {
        $raw = $pro->prices->{$frequency}->{$code} ?? null;
        if(is_numeric($raw) && is_finite((float) $raw) && (float) $raw > 0) {
            $prices[$frequency] = ['amount'=>(float) $raw, 'days'=>$days];
        }
    }
    $extra = is_string($user->extra ?? null) ? json_decode($user->extra) : ($user->extra ?? (object) []);
    $extra = is_object($extra) ? $extra : (object) [];
    $ended = in_array((string) ($extra->billing_stripe_status ?? ''), ['canceled', 'incomplete_expired'], true);
    $subscription = !$ended && trim((string) ($user->payment_subscription_id ?? '')) !== '';
    $active = $user && function_exists('fcc_pro_access_for_user') ? fcc_pro_access_for_user((array) $user) : false;
    if(array_key_exists('active_pro', $accountState)) $active = (bool) $accountState['active_pro'];
    if(array_key_exists('has_subscription', $accountState)) $subscription = (bool) $accountState['has_subscription'];
    $payment = !empty(settings()->payment->is_enabled);
    return [
        'beginner'=>$beginner, 'pro'=>$pro, 'currency'=>$code, 'prices'=>$prices,
        'active_pro'=>$active, 'has_subscription'=>$subscription,
        'current_plan_id'=>(string) ($user->plan_id ?? ''),
        'trial_eligible'=>$user && $pro && !$active && !$subscription && $payment && empty($user->plan_trial_done) && (int) ($pro->trial_days ?? 0) > 0,
        'payment_enabled'=>$payment, 'local'=>getenv('FCC_LOCAL') === '1',
        'locale'=>function_exists('fcc_locale') ? fcc_locale() : 'en',
        'trial_require_card'=>!empty(settings()->payment->trial_require_card),
    ];
}
