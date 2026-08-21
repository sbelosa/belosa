<?php
/* Custom code: FC-2026-08-21: Admin-only read-only Moj Forever LOS controller */

namespace Altum\Controllers;

use Altum\Title;

defined('ALTUMCODE') || die();

class AdminLeaderOperatingSystemForever extends Controller {

    public function index() {
        if(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            die('Method Not Allowed');
        }

        $window_days = (int) ($_GET['window'] ?? 30);
        if(!in_array($window_days, [7, 14, 30, 60], true)) {
            $window_days = 30;
        }

        $period = (string) ($_GET['period'] ?? '');
        $period = preg_match('/^20\d{2}-(0[1-9]|1[0-2])-01$/', $period) ? $period : '';
        $analytics = forever_business_get_los_admin_analytics(
            (int) $this->user->user_id,
            $window_days,
            $period
        );

        Title::set(l('admin_leader_operating_system.forever.title'));

        $view = new \Altum\View('admin/leader-operating-system/forever', (array) $this);
        $this->add_view_content('content', $view->run([
            'analytics' => $analytics,
            'window_options' => [7, 14, 30, 60],
        ]));
    }
}

/* /Custom code: FC-2026-08-21 */
