<?php
/* Custom code: FC-2026-03-19: Brevo email analytics webhook */
namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class WebhookBrevoEmail extends Controller {

    public function index() {
        header('Cache-Control: no-store');
        fc_ensure_email_automation_tables();

        if((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST')) {
            throw_404();
        }

        $headers = getallheaders();
        $payload = trim(@file_get_contents('php://input'));

        debug_log('[' . \Altum\Router::$controller . '] ' . print_r(['headers' => $headers, 'payload' => $payload], true));

        $configured_secret = fc_get_brevo_webhook_secret();
        $received_secret = $headers['X-FC-Brevo-Secret'] ?? $headers['x-fc-brevo-secret'] ?? ($_GET['secret'] ?? null);

        if(!$configured_secret || !$received_secret || !hash_equals($configured_secret, $received_secret)) {
            echo 'Invalid webhook secret';
            http_response_code(401);
            die();
        }

        if($payload === '') {
            echo 'Missing payload';
            http_response_code(400);
            die();
        }

        $data = json_decode($payload);

        if(!$data) {
            echo 'Invalid JSON payload';
            http_response_code(400);
            die();
        }

        $events = is_array($data) ? $data : [$data];
        $processed_events = 0;

        foreach($events as $event_payload) {
            if(!is_object($event_payload) || empty($event_payload->event)) {
                continue;
            }

            $event_type = fc_normalize_brevo_event_type((string) $event_payload->event);
            $message = fc_find_email_automation_message_for_brevo_event($event_payload);
            $is_new_event = fc_store_email_automation_message_event($message, $event_type, $event_payload);

            if(!$is_new_event) {
                continue;
            }

            if($message) {
                fc_apply_brevo_event_to_email_automation_message($message, $event_type, $event_payload);
                fc_log_email_automation_provider_event($message, $event_type, $event_payload);
            }

            $processed_events++;
        }

        echo 'Processed ' . $processed_events . ' events';
    }
}
/* /Custom code: FC-2026-03-19 */