<?php
/* Custom code: FC-2026-08-21: Admin-only read-only Moj Forever LOS controller */

namespace Altum\Controllers;

use Altum\Title;

defined('ALTUMCODE') || die();

class AdminLeaderOperatingSystemForever extends Controller {

    public function index() {
        require_once APP_PATH . 'helpers/fcc_los.php';
        fcc_los_admin((int)$this->user->user_id);
        $query = fcc_los_canonical_query($_GET, 'forever');
        if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            \Altum\Router::$controller_settings['has_view'] = false;
            echo 'Ovaj pregled podržava samo čitanje. Otvori LOS za nastavak.';
            return;
        }
        redirect('admin/leader-operating-system?' . http_build_query($query));
    }
}
