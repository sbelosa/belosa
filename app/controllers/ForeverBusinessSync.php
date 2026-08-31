<?php
/* Custom code: FC-2026-08-13: Narrow machine-to-machine FLP report ingestion */

namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class ForeverBusinessSync extends Controller {

    private const SYNC_KEY_SHA256 = 'b7bad195defe3fbcbf35ece3f9c5cc0cab685545327895a181965685ad28e6d3';
    private const ROOT_FBO_ID = '360000760944';
    private const MAX_FILE_BYTES = 15728640;

    private function output(array $payload, int $status_code = 200): void {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');
        ini_set('serialize_precision', '-1');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        die();
    }

    private function fail(string $code, string $message, int $status_code): void {
        $this->output(['status' => 'error', 'error' => ['code' => $code, 'message' => $message]], $status_code);
    }

    private function request_key(): string {
        $header_key = trim((string) ($_SERVER['HTTP_X_FCC_FOREVER_SYNC_KEY'] ?? ''));
        $authorization = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
        if($header_key !== '') return $header_key;
        if(preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) return trim((string) ($matches[1] ?? ''));
        return '';
    }

    private function ensure_access(): void {
        if(mb_strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $this->fail('method_not_allowed', 'Sinkronizacija prihvaća samo POST zahtjev.', 405);
        }
        $key = $this->request_key();
        if($key === '' || !hash_equals(self::SYNC_KEY_SHA256, hash('sha256', $key))) {
            $this->fail('invalid_key', 'Ključ za Forever sinkronizaciju nije ispravan.', 403);
        }
    }

    private function report_period(): string {
        $period = forever_business_period_from_label($_POST['report_period'] ?? '') ?: date('Y-m-01');
        $minimum_period = (new \DateTimeImmutable('first day of this month'))->modify('-3 years')->format('Y-m-01');
        $maximum_period = (new \DateTimeImmutable('first day of this month'))->modify('+1 month')->format('Y-m-01');
        if($period < $minimum_period || $period > $maximum_period) {
            $this->fail('invalid_period', 'Mjesec izvještaja je izvan dopuštenog raspona.', 422);
        }
        return $period;
    }

    private function non_negative_number(string $key): float {
        $value = $_POST[$key] ?? null;
        if($value === null || $value === '' || !is_numeric(str_replace(',', '.', (string) $value))) {
            $this->fail('invalid_metric', 'Nedostaje ili nije ispravna vrijednost: ' . $key . '.', 422);
        }
        return max(0, (float) str_replace(',', '.', (string) $value));
    }

    private function optional_non_negative_number(string $key): ?float {
        $value = $_POST[$key] ?? null;
        if($value === null || $value === '') return null;
        if(!is_numeric(str_replace(',', '.', (string) $value))) {
            $this->fail('invalid_metric', 'Nije ispravna vrijednost: ' . $key . '.', 422);
        }
        return max(0, (float) str_replace(',', '.', (string) $value));
    }

    public function index() {
        $this->ensure_access();
        forever_business_ensure_tables();
        forever_business_enforce_self_only_access();

        $period = $this->report_period();
        $metric = mb_strtolower(trim((string) ($_POST['metric'] ?? 'report')));

        if($metric === 'status') {
            $dashboard = forever_business_get_dashboard(1, true, self::ROOT_FBO_ID, $period);
            $summary = $dashboard['summary'];
            foreach(['personal_cc', 'average_personal_cc', 'goal_cc', 'goal_current_cc', 'goal_gap_cc', 'closed_6m_average_cc', 'latest_closed_cc'] as $key) {
                if(isset($summary[$key])) $summary[$key] = round((float) $summary[$key], 3);
            }
            $this->output([
                'status' => 'success',
                'metric' => 'status',
                'period' => $dashboard['period'],
                'summary' => $summary,
                'usage' => forever_business_get_usage_summary(),
                'official_four_core' => $dashboard['official_four_core'],
                'last_sync_at' => $dashboard['last_sync_at'],
                'last_sync_was_duplicate' => $dashboard['last_sync_was_duplicate'],
                'last_data_import_at' => $dashboard['last_data_import_at'],
            ]);
        }

        if($metric === 'account_audit') {
            $this->output([
                'status' => 'success',
                'metric' => 'account_audit',
                'generated_at' => get_date(),
                'summary' => forever_business_get_usage_summary(),
                'audit' => forever_business_get_account_audit_rows(),
            ]);
        }

        if($metric === 'fcc_accounts') {
            $accounts = forever_business_get_registered_sync_accounts($period);
            $this->output([
                'status' => 'success',
                'metric' => 'fcc_accounts',
                'period' => $period,
                'summary' => [
                    'unique_forever_ids' => count($accounts),
                    'active_account_links' => array_sum(array_column($accounts, 'active_link_count')),
                    'current_cc_confirmed' => count(array_filter($accounts, static fn($account) => ($account['metric_period'] ?? null) === $period && $account['personal_cc'] !== null)),
                    'vip_enrolled' => count(array_filter($accounts, static fn($account) => !empty($account['is_vip_enrolled']))),
                ],
                'accounts' => $accounts,
                'generated_at' => get_date(),
            ]);
        }

        /* One-time, exact-account reconciliation authorized on 2026-08-31.
         * Every identity and previous value must still match before any write. */
        if($metric === 'fcc_account_reconcile_20260831') {
            if(!hash_equals('apply_exact_seven_account_plan', trim((string) ($_POST['confirmation'] ?? '')))) {
                $this->fail('maintenance_not_confirmed', 'Jednokratno usklađivanje FCC računa nije potvrđeno.', 422);
            }

            $plan = [
                ['user_id' => 552, 'name' => 'Brigitte Berulec', 'old' => '360000400827', 'new' => '360001400827'],
                ['user_id' => 438, 'name' => 'Danijela Butković', 'old' => '360000400266', 'new' => '360001400266'],
                ['user_id' => 441, 'name' => 'Krešo Solar', 'old' => '360000140027', 'new' => '360001400273'],
                ['user_id' => 300, 'name' => 'Liljanka Tomašić', 'old' => '360000826945', 'new' => '360000826944'],
                ['user_id' => 344, 'name' => 'Miodrag Mišković', 'old' => '360000350460', 'new' => '360000950460'],
                ['user_id' => 231, 'name' => 'Maja Trbušić Hlad', 'old' => '360000888812', 'delete' => true],
                ['user_id' => 303, 'name' => 'Shemsije Musa', 'old' => '360000270807', 'delete' => true],
            ];
            $accounts = [];
            foreach($plan as $change) {
                $account = db()->where('user_id', $change['user_id'])->where('type', 0)->getOne('users', ['user_id', 'name', 'preferences']);
                if(!$account || !hash_equals($change['name'], (string) $account->name)) {
                    $this->fail('maintenance_identity_changed', 'Jedan od sedam FCC računa više ne odgovara odobrenom planu.', 409);
                }
                $preferences = json_decode((string) ($account->preferences ?? '{}'));
                if(is_array($preferences)) $preferences = (object) $preferences;
                if(!is_object($preferences)) $preferences = (object) [];
                $meta = $preferences->meta ?? (object) [];
                if(is_array($meta)) $meta = (object) $meta;
                if(!is_object($meta)) $meta = (object) [];
                $current_fbo_id = forever_business_normalize_fbo_id($meta->foreverId ?? $meta->forever_id ?? $meta->foreverID ?? '');
                if(!hash_equals($change['old'], $current_fbo_id)) {
                    $this->fail('maintenance_fbo_changed', 'Jedan od sedam FCC Forever ID-jeva više ne odgovara odobrenom planu.', 409);
                }
                $accounts[$change['user_id']] = [$account, $preferences, $meta];
            }

            $updated = [];
            $deleted = [];
            foreach($plan as $change) {
                [$account, $preferences, $meta] = $accounts[$change['user_id']];
                if(!empty($change['delete'])) {
                    (new \Altum\Models\User())->delete((int) $account->user_id);
                    if(db()->where('user_id', (int) $account->user_id)->has('users')) {
                        $this->fail('maintenance_delete_failed', 'Odobreni bivši FCC račun nije uklonjen.', 500);
                    }
                    $deleted[] = (int) $account->user_id;
                    continue;
                }

                $meta->foreverId = $change['new'];
                $preferences->meta = $meta;
                if(!db()->where('user_id', (int) $account->user_id)->where('type', 0)->update('users', [
                    'preferences' => json_encode($preferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ])) {
                    $this->fail('maintenance_update_failed', 'Odobreni FCC Forever ID nije spremljen.', 500);
                }
                cache()->deleteItemsByTag('user_id=' . (int) $account->user_id);
                $updated[] = ['user_id' => (int) $account->user_id, 'fbo_id' => $change['new']];
            }

            $this->output([
                'status' => 'success',
                'metric' => 'fcc_account_reconcile_20260831',
                'updated' => $updated,
                'deleted_user_ids' => $deleted,
                'completed_at' => get_date(),
            ]);
        }

        if($metric === 'total_cc') {
            $total_cc = $this->non_negative_number('total_cc');
            $is_closed = filter_var($_POST['is_closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            forever_business_upsert_total_cc_snapshot(
                self::ROOT_FBO_ID,
                $period,
                $total_cc,
                $is_closed,
                'GLOBAL',
                'FLP360 CC Summary · Global Total CC'
            );
            $this->output([
                'status' => 'success',
                'metric' => 'total_cc',
                'period' => $period,
                'total_cc' => round($total_cc, 3),
                'country_scope' => 'GLOBAL',
                'is_closed' => $is_closed,
                'synced_at' => get_date(),
            ]);
        }

        if($metric === 'member_cc') {
            $fbo_id = forever_business_normalize_fbo_id($_POST['fbo_id'] ?? '');
            if($fbo_id === '') {
                $this->fail('invalid_fbo_id', 'Live CC strojni unos nema valjan Forever ID.', 422);
            }
            $metrics = [
                'personal_cc' => $this->non_negative_number('personal_cc'),
                'total_cc' => $this->non_negative_number('total_cc'),
                'total_active_cc' => $this->non_negative_number('total_active_cc'),
            ];
            foreach(['non_manager_cc', 'leadership_cc', 'total_active_cc_ytd', 'non_manager_cc_ytd', 'leadership_cc_ytd'] as $key) {
                $metrics[$key] = $this->optional_non_negative_number($key);
            }
            if($fbo_id === self::ROOT_FBO_ID) {
                foreach(['non_manager_cc', 'leadership_cc', 'total_active_cc_ytd', 'non_manager_cc_ytd', 'leadership_cc_ytd'] as $key) {
                    if($metrics[$key] === null) $this->fail('invalid_metric', 'Nedostaje vrijednost glavnog FBO-a: ' . $key . '.', 422);
                }
            } elseif(forever_business_get_active_user_link_count_for_fbo($fbo_id) < 1) {
                $this->fail('invalid_fbo_id', 'Live CC strojni unos dopušten je samo za Forever ID aktivnog FCC računa.', 422);
            }
            $metrics['is_4cc_active'] = filter_var($_POST['is_4cc_active'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if($fbo_id === self::ROOT_FBO_ID) {
                forever_business_upsert_root_live_cc($fbo_id, $period, $metrics);
            } else {
                forever_business_upsert_registered_member_live_cc($fbo_id, $period, $metrics);
            }
            $this->output([
                'status' => 'success',
                'metric' => 'member_cc',
                'fbo_id' => $fbo_id,
                'period' => $period,
                'synced_at' => get_date(),
            ]);
        }

        if($metric === 'four_core') {
            $values = [];
            foreach(['open', 'downline'] as $scope) {
                foreach(['month', 'ytd'] as $timeframe) {
                    foreach(['recruitment', 'retention', 'productivity', 'development'] as $core) {
                        $key = implode('_', [$scope, $timeframe, $core]);
                        $values[$scope][$timeframe][$core] = $this->non_negative_number($key);
                    }
                }
            }
            forever_business_upsert_four_core_snapshot(self::ROOT_FBO_ID, $period, $values);
            $this->output([
                'status' => 'success',
                'metric' => 'four_core',
                'period' => $period,
                'synced_at' => get_date(),
            ]);
        }

        if($metric !== 'report') {
            $this->fail('unsupported_metric', 'Vrsta sinkronizacije nije podržana.', 422);
        }

        $file = $_FILES['report_file'] ?? null;
        if(!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            $this->fail('invalid_upload', 'Nedostaje ispravna CSV ili XLSX datoteka.', 422);
        }
        $size = (int) ($file['size'] ?? 0);
        if($size <= 0 || $size > self::MAX_FILE_BYTES) {
            $this->fail('invalid_size', 'Datoteka mora biti manja od 15 MB.', 422);
        }

        $original_name = mb_substr(basename((string) ($file['name'] ?? 'report')), 0, 255);
        $extension = mb_strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        if(!in_array($extension, ['csv', 'xlsx'], true)) {
            $this->fail('unsupported_type', 'Podržani su samo CSV i XLSX izvještaji.', 422);
        }

        try {
            $report = forever_business_parse_report((string) $file['tmp_name'], $original_name, self::ROOT_FBO_ID, 'Stjepan Beloša', $period);
            if(($report['kind'] ?? '') === 'focus_group' && empty($report['members'][self::ROOT_FBO_ID])) {
                throw new \RuntimeException('Focus Group ne sadrži očekivani glavni Forever ID.');
            }
            $result = forever_business_import_report($report, hash_file('sha256', (string) $file['tmp_name']), 1);
            $summary = $result['summary'] ?? [];
            if(isset($summary['latest_personal_cc'])) $summary['latest_personal_cc'] = round((float) $summary['latest_personal_cc'], 3);
            $this->output([
                'status' => 'success',
                'synced_at' => get_date(),
                'duplicate' => (bool) ($result['duplicate'] ?? false),
                'import_id' => (int) ($result['import_id'] ?? 0),
                'summary' => $summary,
            ]);
        } catch(\Throwable $exception) {
            $this->fail('import_failed', $exception->getMessage(), 422);
        }
    }
}

/* /Custom code: FC-2026-08-13 */
