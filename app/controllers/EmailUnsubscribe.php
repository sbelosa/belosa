<?php
/* Custom code: FC-2026-03-19: public one-click unsubscribe endpoint */
namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class EmailUnsubscribe extends Controller {

    public function index() {
        header('Cache-Control: no-store');

        $context = [
            'message_type' => in_array($_GET['type'] ?? '', ['broadcast', 'automation'], true) ? (string) $_GET['type'] : 'broadcast',
            'broadcast_id' => (int) ($_GET['broadcast_id'] ?? 0),
            'automation_id' => (int) ($_GET['automation_id'] ?? 0),
            'automation_enrollment_id' => (int) ($_GET['automation_enrollment_id'] ?? 0),
            'automation_step_id' => (int) ($_GET['automation_step_id'] ?? 0),
            'user_id' => (int) ($_GET['user_id'] ?? 0),
            'recipient_email' => trim((string) ($_GET['email'] ?? '')),
        ];

        $signature = trim((string) ($_GET['signature'] ?? ''));

        if($signature === '' || !hash_equals(fc_generate_email_unsubscribe_signature($context), $signature)) {
            http_response_code(403);
            echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Unsubscribe</title></head><body style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;background:#f6f7fb;color:#111827;padding:32px;"><div style="max-width:560px;margin:0 auto;background:#fff;border-radius:18px;padding:32px;box-shadow:0 24px 60px rgba(15,23,42,.08)"><h1 style="margin:0 0 12px;font-size:28px;">Link više nije važeći</h1><p style="margin:0;color:#4b5563;line-height:1.6;">Poveznica za odjavu nije valjana ili je promijenjena. Ako i dalje želiš odjavu, javi se podršci ili koristi novu poveznicu iz zadnjeg emaila.</p></div></body></html>';
            return;
        }

        /* Custom code: FC-2026-03-19: prevent unsubscribe page from failing hard on schema/runtime issues */
        try {
            $result = fc_process_email_unsubscribe($context);
        }
        catch(\Throwable $exception) {
            error_log('Email unsubscribe failed: ' . $exception->getMessage());

            http_response_code(200);
            echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Unsubscribe</title></head><body style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;background:linear-gradient(180deg,#f8fafc 0%,#eef2ff 100%);color:#111827;padding:32px;"><div style="max-width:640px;margin:0 auto;background:#fff;border-radius:24px;padding:36px;box-shadow:0 24px 70px rgba(15,23,42,.1)"><div style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#fff7ed;color:#9a3412;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Email preference</div><h1 style="margin:18px 0 12px;font-size:32px;line-height:1.15;">Odjavu trenutno nismo mogli dovršiti</h1><p style="margin:0;color:#4b5563;line-height:1.7;">Poveznica je otvorena, ali je došlo do tehničke greške tijekom obrade odjave. Pokušaj ponovno kroz isti email za nekoliko minuta ili nam javi email adresu kako bismo odjavu ručno dovršili.</p></div></body></html>';
            return;
        }
        /* /Custom code: FC-2026-03-19 */

        $recipient_email = $context['recipient_email'] ?: ($result['user']->email ?? '');
        $resource_label = $context['message_type'] === 'automation' ? 'automatizacije' : 'mail kampanje';
        $headline = $result['already_unsubscribed'] ? 'Već si odjavljen/a' : 'Odjava je potvrđena';
        $description = $result['already_unsubscribed']
            ? 'Ovaj kontakt je već ranije odjavljen iz email obavijesti. Nisu potrebne dodatne radnje.'
            : 'Od sada više nećeš primati slične email obavijesti. Informacija o odjavi spremljena je i vidljiva u admin analitici.';

        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Unsubscribe</title></head><body style="font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;background:linear-gradient(180deg,#f8fafc 0%,#eef2ff 100%);color:#111827;padding:32px;"><div style="max-width:640px;margin:0 auto;background:#fff;border-radius:24px;padding:36px;box-shadow:0 24px 70px rgba(15,23,42,.1)"><div style="display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;background:#ecfdf5;color:#065f46;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">Email preference</div><h1 style="margin:18px 0 12px;font-size:32px;line-height:1.15;">' . $headline . '</h1><p style="margin:0 0 18px;color:#4b5563;line-height:1.7;">' . $description . '</p><div style="padding:18px 20px;border-radius:18px;background:#f8fafc;border:1px solid #e5e7eb;"><div style="font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:#6b7280;margin-bottom:8px;">Odjavljeni kontakt</div><div style="font-size:18px;font-weight:700;">' . htmlspecialchars($recipient_email ?: 'Nepoznati email', ENT_QUOTES, 'UTF-8') . '</div><div style="margin-top:8px;color:#4b5563;">Izvor: ' . htmlspecialchars($resource_label, ENT_QUOTES, 'UTF-8') . ($context['message_type'] === 'automation' && $context['automation_step_id'] ? ' • korak #' . (int) $context['automation_step_id'] : '') . ($context['message_type'] === 'broadcast' && $context['broadcast_id'] ? ' • kampanja #' . (int) $context['broadcast_id'] : '') . '</div></div><p style="margin:18px 0 0;color:#6b7280;line-height:1.6;">Ako se predomisliš, pretplatu možeš ponovno uključiti u svom korisničkom računu ili ručno kroz admin.</p></div></body></html>';
    }
}
/* /Custom code: FC-2026-03-19 */