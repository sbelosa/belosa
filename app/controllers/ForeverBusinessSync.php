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

    public function index() {
        $this->ensure_access();
        forever_business_ensure_tables();

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
            ]);
        }

        if($metric === 'total_cc') {
            $total_cc = $this->non_negative_number('total_cc');
            $is_closed = filter_var($_POST['is_closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            forever_business_upsert_total_cc_snapshot(self::ROOT_FBO_ID, $period, $total_cc, $is_closed);
            $this->output([
                'status' => 'success',
                'metric' => 'total_cc',
                'period' => $period,
                'total_cc' => round($total_cc, 3),
                'is_closed' => $is_closed,
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
            /* Only exact active FCC-user/FBO matches receive a manager scope.
               Running this after duplicates also repairs access without altering CC data. */
            $manager_accesses = forever_business_grant_exact_manager_accesses(1);
            $summary = $result['summary'] ?? [];
            if(isset($summary['latest_personal_cc'])) $summary['latest_personal_cc'] = round((float) $summary['latest_personal_cc'], 3);
            $this->output([
                'status' => 'success',
                'synced_at' => get_date(),
                'duplicate' => (bool) ($result['duplicate'] ?? false),
                'import_id' => (int) ($result['import_id'] ?? 0),
                'manager_accesses_confirmed' => $manager_accesses,
                'summary' => $summary,
            ]);
        } catch(\Throwable $exception) {
            $this->fail('import_failed', $exception->getMessage(), 422);
        }
    }
}

/* /Custom code: FC-2026-08-13 */
