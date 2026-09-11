<?php
/* Custom code: FC-2026-09-11: ensure valid plans never perform live billing recovery on page views */

namespace {
    if(!defined('ALTUMCODE')) {
        define('ALTUMCODE', true);
    }
}

namespace Altum\Models {
    class Model {
    }

    require_once dirname(__DIR__) . '/app/models/User.php';

    final class UserPlanExpirationGuardTestModel extends User {
        public int $billing_protection_checks = 0;

        public function has_expired_plan_downgrade_protection($user): bool {
            $this->billing_protection_checks++;
            return true;
        }
    }

    $model = new UserPlanExpirationGuardTestModel();

    $future_user = (object) [
        'user_id' => 1,
        'plan_id' => 5,
        'plan_expiration_date' => '2036-01-01 00:00:00',
    ];
    $model->process_user_plan_expiration_by_user($future_user);

    if($model->billing_protection_checks !== 0) {
        fwrite(STDERR, "A non-expired plan invoked live billing recovery.\n");
        exit(1);
    }

    $free_user = (object) [
        'user_id' => 2,
        'plan_id' => 'free',
        'plan_expiration_date' => '2020-01-01 00:00:00',
    ];
    $model->process_user_plan_expiration_by_user($free_user);

    if($model->billing_protection_checks !== 0) {
        fwrite(STDERR, "A free plan invoked live billing recovery.\n");
        exit(1);
    }

    $expired_paid_user = (object) [
        'user_id' => 3,
        'plan_id' => 5,
        'plan_expiration_date' => '2020-01-01 00:00:00',
    ];
    $model->process_user_plan_expiration_by_user($expired_paid_user);

    if($model->billing_protection_checks !== 1) {
        fwrite(STDERR, "An expired paid plan did not retain live billing protection.\n");
        exit(1);
    }

    fwrite(STDOUT, "User plan-expiration Stripe guard checks passed.\n");
}

/* /Custom code: FC-2026-09-11 */
