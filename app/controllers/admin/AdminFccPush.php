<?php
/* Custom code: FC-2026-10-07: FCC owner opt in for independent Web Push. */

namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class AdminFccPush extends Controller {
    public function access_status() {
        $owner = $this->user;
        if($_SERVER['REQUEST_METHOD'] !== 'GET' || !is_object($owner) || (int) ($owner->type ?? 0) !== 1) throw_404();
        require_once APP_PATH . 'helpers/fcc_registration_notifications.php';
        header('Cache-Control: no-store');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['owner_account' => (int) ($owner->user_id ?? 0) === 1,
            'active_account' => (int) ($owner->status ?? 0) === 1,
            'admin_preview_marker' => session_has('admin_user_id'),
            'preview_owner' => (int) session_get('admin_user_id') === 1,
            'session_owner' => (int) session_get('user_id') === 1,
            'setup_allowed' => fcc_registration_admin_push_owner($owner)]);
        exit;
    }

    public function index() {
        require_once APP_PATH . 'helpers/fcc_registration_notifications.php';
        $owner = $this->user;
        if(!is_object($owner) || !fcc_registration_admin_push_owner($owner)) throw_404();
        header('Cache-Control: no-store');
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            if(!\Altum\Csrf::check()) {
                http_response_code(403);
                \Altum\Response::json('Provjera sigurnosti nije uspjela. Osvježite stranicu.', 'error');
            }
            try {
                $action = (string) ($_POST['action'] ?? '');
                if($action === 'subscribe') {
                    $encoded = (string) ($_POST['subscription'] ?? '');
                    if(strlen($encoded) > 8192) throw new \InvalidArgumentException('Subscription is too large.');
                    $subscription = json_decode($encoded, true, 8, JSON_THROW_ON_ERROR);
                    if(!is_array($subscription)) throw new \InvalidArgumentException('Invalid subscription.');
                    $details = fcc_registration_admin_push_subscribe($this->user, $subscription);
                    $details['delivery'] = fcc_registration_process_admin_notifications();
                } elseif($action === 'unsubscribe') {
                    $details = fcc_registration_admin_push_unsubscribe($this->user, (string) ($_POST['endpoint'] ?? ''));
                } elseif($action === 'status') {
                    $details = fcc_registration_admin_push_subscription_status($this->user, (string) ($_POST['endpoint'] ?? ''));
                } elseif($action === 'test') {
                    $details = ['test' => fcc_registration_notify_admin_test((string) ($_POST['request_id'] ?? '')),
                        'delivery' => fcc_registration_process_admin_notifications()];
                    $details['test'] = fcc_registration_admin_notification_status($details['test']['notification_id']);
                } else {
                    throw new \InvalidArgumentException('Unknown owner push action.');
                }
                \Altum\Response::json('', 'success', $details);
            } catch(\InvalidArgumentException | \JsonException $exception) {
                http_response_code(422);
                \Altum\Response::json('Podaci za obavijesti nisu ispravni. Osvježite stranicu i pokušajte ponovno.', 'error');
            } catch(\Throwable $exception) {
                error_log('FCC owner push request failed.');
                http_response_code(503);
                \Altum\Response::json('Obavijesti trenutačno nisu dostupne. Pokušajte ponovno kasnije.', 'error');
            }
        }
        if($_SERVER['REQUEST_METHOD'] !== 'GET') throw_404();
        \Altum\Title::set('FCC obavijesti');
        $configuration = fcc_registration_admin_push_runtime_ready() ? fcc_registration_admin_push_configuration(true) : null;
        $data = ['public_key' => $configuration->public_key ?? '',
            'subscriber_count' => count(fcc_registration_admin_push_subscribers())];
        $view = new \Altum\View('admin/fcc-push/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }
}

/* /Custom code: FC-2026-10-07 */
