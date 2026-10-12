<?php
namespace Altum\Controllers;
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-12: forevercard.club/poziv/<code> opens the member's current Pozivnica u tim. */
class BusinessInvite extends Controller {
    public function index() {
        \Altum\Router::$controller_settings['has_view'] = false;
        header('Cache-Control: no-store, private');
        header('X-Robots-Tag: noindex, nofollow');
        $code = strtolower((string) ($this->params[0] ?? ''));
        $country = is_string($_GET['country'] ?? null) ? strtolower($_GET['country']) : '';
        $destination = fcc_business_enabled() && fcc_invite_code_valid($code) ? fcc_invite_public_destination($code, $country) : null;
        if(!$destination) throw_404();
        header('Location: ' . $destination, true, 302);
    }
}
/* /Custom code: FC-2026-10-12 */
