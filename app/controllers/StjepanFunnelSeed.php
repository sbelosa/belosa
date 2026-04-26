<?php

namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class StjepanFunnelSeed extends Controller {

    private function output(array $payload, int $status_code = 200): void {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        die();
    }

    private function param(string $key, string $default = ''): string {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_array($value) ? $default : trim((string) $value);
    }

    private function require_access(): void {
        if(mb_strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->output(['status' => 'error', 'message' => 'POST required.'], 405);
        }

        if(!defined('FCC_OPS_READONLY_ENABLED') || !FCC_OPS_READONLY_ENABLED || trim((string) FCC_OPS_READONLY_KEY) === '') {
            $this->output(['status' => 'error', 'message' => 'Ops access is not enabled.'], 404);
        }

        if(!hash_equals((string) FCC_OPS_READONLY_KEY, $this->param('key'))) {
            $this->output(['status' => 'error', 'message' => 'Invalid key.'], 403);
        }

        if($this->param('confirm') !== 'seed-stjepan-funnel-2026-04-26') {
            $this->output(['status' => 'error', 'message' => 'Invalid confirmation token.'], 422);
        }
    }

    public function index() {
        $this->require_access();

        $target_email = 'info@forevercard.club';
        $user = db()
            ->where('email', $target_email)
            ->getOne('users', ['user_id', 'name', 'email', 'preferences']);

        if(!$user) {
            $this->output(['status' => 'error', 'message' => 'Target user not found.'], 404);
        }

        vip_funnel_ensure_runtime_schema();

        $preferences = vip_funnel_get_user_preferences($user);
        $preferences->vip_funnel_gate_exempt = true;

        if(!vip_funnel_save_user_preferences((int) $user->user_id, $preferences)) {
            $this->output(['status' => 'error', 'message' => 'Unable to enable funnel gate access.'], 422);
        }

        $user = db()
            ->where('user_id', (int) $user->user_id)
            ->getOne('users', ['user_id', 'name', 'email', 'preferences']);

        $payload = vip_funnel_get_stjepan_recruitment_payload($user, [
            'contact_email' => $target_email,
            'privacy_url' => SITE_URL . 'page/privacy-policy',
        ]);
        $existing_payload = vip_funnel_studio_load_from_database($user);
        $timestamp = date('Ymd_His');
        $backup_path = ROOT_PATH . 'tmp/vip_funnel_backup_user_' . (int) $user->user_id . '_' . $timestamp . '.json';

        if(!is_dir(dirname($backup_path))) {
            @mkdir(dirname($backup_path), 0775, true);
        }

        @file_put_contents($backup_path, vip_funnel_json_encode($existing_payload));

        if(!vip_funnel_studio_save_to_database($user, $payload)) {
            $this->output(['status' => 'error', 'message' => 'Unable to save funnel payload.'], 422);
        }

        $public_url = vip_funnel_get_public_funnel_url((int) $user->user_id, (string) ($payload['funnel']['slug'] ?? 'stjepan-online-posao'));

        $this->output([
            'status' => 'success',
            'user_id' => (int) $user->user_id,
            'email' => $target_email,
            'backup_path' => $backup_path,
            'public_url' => $public_url,
            'studio_url' => SITE_URL . 'vip-funnel-studio',
            'analytics_url' => SITE_URL . 'funnels-analytics',
            'contacts_url' => SITE_URL . 'data?type=lead_funnel',
        ]);
    }
}
