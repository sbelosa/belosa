<?php
/* Custom code: FC-2026-08-01: regression checks for confirmed Stripe recovery */

$workspace_root = dirname(__DIR__);
$billing_path = $workspace_root . '/app/models/Billing.php';
$webhook_path = $workspace_root . '/app/controllers/WebhookStripe.php';
$account_plan_path = $workspace_root . '/app/controllers/AccountPlan.php';

$billing_source = file_get_contents($billing_path);
$webhook_source = file_get_contents($webhook_path);
$account_plan_source = file_get_contents($account_plan_path);

if($billing_source === false || $webhook_source === false || $account_plan_source === false) {
    fwrite(STDERR, "Could not load billing recovery sources.\n");
    exit(1);
}

$extract_method = static function(string $source, string $start, string $end): string {
    $start_position = strpos($source, $start);
    $end_position = $start_position === false ? false : strpos($source, $end, $start_position + strlen($start));

    if($start_position === false || $end_position === false) {
        return '';
    }

    return substr($source, $start_position, $end_position - $start_position);
};

$successful_payment_method = $extract_method(
    $billing_source,
    'public function handle_successful_payment(array $context): void',
    'public function sync_subscription_status(array $context): void'
);
$subscription_sync_method = $extract_method(
    $billing_source,
    'public function sync_subscription_status(array $context): void',
    'public function process_grace_periods(int $limit = 25): array'
);
$grace_period_method = $extract_method(
    $billing_source,
    'public function process_grace_periods(int $limit = 25): array',
    'public function get_user_billing_summary(int $user_id): array'
);

if(!defined('ALTUMCODE')) {
    define('ALTUMCODE', true);
}

if(!class_exists('Altum\\Models\\Model', false)) {
    eval('namespace Altum\\Models; class Model {}');
}

require_once $billing_path;

$billing_reflection = new ReflectionClass('Altum\\Models\\Billing');
$billing_instance = $billing_reflection->newInstanceWithoutConstructor();
$confirmation_method = $billing_reflection->getMethod('is_successful_payment_confirmed');
$confirmation_method->setAccessible(true);

$assertions = [
    'strict true is accepted as payment confirmation' => $confirmation_method->invoke($billing_instance, ['payment_confirmed' => true]) === true,
    'active subscription status is not payment confirmation' => $confirmation_method->invoke($billing_instance, ['stripe_status' => 'active']) === false,
    'truthy non-boolean confirmation is rejected' => $confirmation_method->invoke($billing_instance, ['payment_confirmed' => 1]) === false,
    'successful payment handler requires explicit confirmation' => str_contains($successful_payment_method, 'is_successful_payment_confirmed($context)'),
    'subscription status sync cannot recover payment' => !str_contains($subscription_sync_method, 'handle_successful_payment('),
    'grace-period cron verifies the paid invoice before recovery' => str_contains($grace_period_method, 'get_confirmed_paid_stripe_invoice_context($user, $extra)')
        && str_contains($grace_period_method, 'if($confirmed_payment_context)')
        && str_contains($grace_period_method, '] + $confirmed_payment_context)'),
    'invoice.paid webhook supplies confirmation' => str_contains($webhook_source, "'payment_confirmed' => true"),
    'verified manual retry supplies confirmation' => str_contains($account_plan_source, "'payment_confirmed' => true"),
];

$failed = [];

foreach($assertions as $description => $passed) {
    if(!$passed) {
        $failed[] = $description;
    }
}

if($failed) {
    fwrite(STDERR, "Billing recovery regression check failed:\n- " . implode("\n- ", $failed) . "\n");
    exit(1);
}

echo "Billing recovery guard checks passed.\n";

/* /Custom code: FC-2026-08-01 */
