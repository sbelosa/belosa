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
            /* Custom code: FC-2026-03-22: log webhook auth failures without exposing the secret value */
            debug_log('[' . \\Altum\\Router::$controller . '] Brevo webhook authentication failed: ' . json_encode([
                'configured_secret_present' => (int) ($configured_secret !== ''),
                'received_secret_present' => (int) (!empty($received_secret)),
                'header_names' => array_keys((array) $headers),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            /* /Custom code: FC-2026-03-22 */
            echo 'Invalid webhook secret';
            http_response_code(401);
            die();
        }

        if($payload === '') {
            /* Custom code: FC-2026-03-22: log empty Brevo webhook payloads */
            debug_log('[' . \\Altum\\Router::$controller . '] Brevo webhook rejected empty payload');
            /* /Custom code: FC-2026-03-22 */
            echo 'Missing payload';
            http_response_code(400);
            die();
        }

        $data = json_decode($payload);

        if(!$data) {
            /* Custom code: FC-2026-03-22: log invalid Brevo webhook JSON payloads */
            debug_log('[' . \\Altum\\Router::$controller . '] Brevo webhook invalid JSON: ' . json_encode([
                'json_error' => function_exists('json_last_error_msg') ? json_last_error_msg() : 'Unknown JSON error',
                'payload_preview' => mb_substr($payload, 0, 1000),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            /* /Custom code: FC-2026-03-22 */
            echo 'Invalid JSON payload';
            http_response_code(400);
            die();
        }

        $events = is_array($data) ? $data : [$data];
        $processed_events = 0;
        /* Custom code: FC-2026-03-22: collect webhook processing diagnostics for live troubleshooting */
        $diagnostics = [
            'received_events' => count($events),
            'processed_events' => 0,
            'matched_events' => 0,
            'unmatched_events' => 0,
            'duplicate_events' => 0,
            'skipped_events' => 0,
            'applied_events' => 0,
        ];
        /* /Custom code: FC-2026-03-22 */

        foreach($events as $event_payload) {
            if(!is_object($event_payload) || empty($event_payload->event)) {
                /* Custom code: FC-2026-03-22: log skipped payloads missing the event marker */
                $diagnostics['skipped_events']++;
                debug_log('[' . \\Altum\\Router::$controller . '] Brevo webhook skipped payload without event: ' . json_encode([
                    'payload' => $event_payload,
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                /* /Custom code: FC-2026-03-22 */
                continue;
            }

            $event_type = fc_normalize_brevo_event_type((string) $event_payload->event);
            $message = fc_find_email_automation_message_for_brevo_event($event_payload);
            /* Custom code: FC-2026-03-22: build a structured debug context for every Brevo event */
            $event_debug_context = fc_get_brevo_webhook_debug_context($event_payload, $event_type, $message);
            $event_debug_context['event_hash'] = fc_get_brevo_event_hash($event_payload, $event_type);
            /* /Custom code: FC-2026-03-22 */

            if(!$message) {
                /* Custom code: FC-2026-03-22: log unmatched events with extracted matching context */
                $diagnostics['unmatched_events']++;
                debug_log('[' . 
                    \\Altum\\Router::$controller .
                    '] Unmatched Brevo event: ' . json_encode($event_debug_context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                );
                /* /Custom code: FC-2026-03-22 */
            } else {
                /* Custom code: FC-2026-03-22: count matched events before persistence */
                $diagnostics['matched_events']++;
                /* /Custom code: FC-2026-03-22 */
            }

            $is_new_event = fc_store_email_automation_message_event($message, $event_type, $event_payload);

            if(!$is_new_event) {
                /* Custom code: FC-2026-03-22: log duplicate events to separate retries from parser failures */
                $diagnostics['duplicate_events']++;
                debug_log('[' . \\Altum\\Router::$controller . '] Duplicate Brevo event ignored: ' . json_encode($event_debug_context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                /* /Custom code: FC-2026-03-22 */
                continue;
            }

            if($message) {
                fc_apply_brevo_event_to_email_automation_message($message, $event_type, $event_payload);
                fc_log_email_automation_provider_event($message, $event_type, $event_payload);
                /* Custom code: FC-2026-03-22: log successfully applied message updates */
                $diagnostics['applied_events']++;
                debug_log('[' . \\Altum\\Router::$controller . '] Applied Brevo event: ' . json_encode($event_debug_context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                /* /Custom code: FC-2026-03-22 */
            }

            $processed_events++;
            /* Custom code: FC-2026-03-22: track persisted events for final summary */
            $diagnostics['processed_events'] = $processed_events;
            /* /Custom code: FC-2026-03-22 */
        }

        /* Custom code: FC-2026-03-22: write a concise end-of-request webhook summary */
        debug_log('[' . \\Altum\\Router::$controller . '] Brevo webhook summary: ' . json_encode($diagnostics, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        /* /Custom code: FC-2026-03-22 */

        echo 'Processed ' . $processed_events . ' events';
    }
}
/* /Custom code: FC-2026-03-19 */