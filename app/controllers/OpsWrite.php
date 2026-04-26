<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class OpsWrite extends Controller {

    private function json_flags(): int {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        if(defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }

        if(defined('JSON_PARTIAL_OUTPUT_ON_ERROR')) {
            $flags |= JSON_PARTIAL_OUTPUT_ON_ERROR;
        }

        if(($this->get_param_string('pretty') === '1')) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return $flags;
    }

    private function output(array $payload, int $status_code = 200): void {
        http_response_code($status_code);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        echo json_encode($payload, $this->json_flags());
        die();
    }

    private function respond_success(string $action, array $data, array $meta = []): void {
        $this->output([
            'status' => 'success',
            'action' => $action,
            'generated_at' => get_date(),
            'data' => $data,
            'meta' => array_merge([
                'site_url' => SITE_URL,
                'database_name' => defined('DATABASE_NAME') ? DATABASE_NAME : '',
            ], $meta),
        ]);
    }

    private function respond_error(string $code, string $message, int $status_code = 400, array $details = []): void {
        $this->output([
            'status' => 'error',
            'generated_at' => get_date(),
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status_code);
    }

    private function get_body_params(): array {
        static $params = null;

        if($params !== null) {
            return $params;
        }

        $params = [];
        $content_type = mb_strtolower(trim((string) ($_SERVER['CONTENT_TYPE'] ?? '')));

        if(str_contains($content_type, 'application/json')) {
            $raw_body = file_get_contents('php://input');
            $decoded = json_decode($raw_body ?: '[]', true);

            if(is_array($decoded)) {
                $params = $decoded;
            }
        }

        return $params;
    }

    private function get_param_string(string $key, string $default = ''): string {
        $body_params = $this->get_body_params();
        $value = $body_params[$key] ?? $_POST[$key] ?? $_GET[$key] ?? $default;

        if(is_array($value)) {
            return $default;
        }

        return trim((string) $value);
    }

    private function get_authorization_bearer(): string {
        $header = trim((string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));

        if($header !== '' && preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim((string) ($matches[1] ?? ''));
        }

        return '';
    }

    private function get_request_key(): string {
        foreach([
            $this->get_param_string('key'),
            trim((string) ($_SERVER['HTTP_X_FCC_OPS_KEY'] ?? '')),
            $this->get_authorization_bearer(),
        ] as $candidate) {
            if($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    private function ensure_access(): void {
        $request_method = mb_strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if($request_method !== 'POST') {
            $this->respond_error('method_not_allowed', 'Ops write endpoint requires POST.', 405, [
                'allowed_methods' => ['POST'],
            ]);
        }

        if(!defined('FCC_OPS_WRITE_ENABLED') || !FCC_OPS_WRITE_ENABLED || trim((string) FCC_OPS_WRITE_KEY) === '') {
            $this->respond_error('ops_write_disabled', 'Write ops endpoint is not enabled.', 404);
        }

        $provided_key = $this->get_request_key();
        if($provided_key === '' || !hash_equals((string) FCC_OPS_WRITE_KEY, $provided_key)) {
            $this->respond_error('invalid_key', 'Write ops key is invalid.', 403);
        }
    }

    private function get_client_identity(): array {
        $forwarded_for = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        $client_ip = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));

        if($client_ip === '' && $forwarded_for !== '') {
            $parts = array_map('trim', explode(',', $forwarded_for));
            $client_ip = trim((string) ($parts[0] ?? ''));
        }

        if($client_ip === '') {
            $client_ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        }

        return [
            'ip' => $client_ip,
            'forwarded_for' => $forwarded_for,
            'user_agent' => trim((string) ($_SERVER['HTTP_USER_AGENT'] ?? '')),
        ];
    }

    private function excerpt(string $value, int $limit = 220): string {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        if(mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max(1, $limit - 1))) . '…';
    }

    private function normalize_sql(string $sql): string {
        $sql = str_replace("\r", '', trim($sql));

        if($sql === '') {
            $this->respond_error('missing_sql', 'SQL is required.', 422);
        }

        if(mb_strlen($sql) > 100000) {
            $this->respond_error('sql_too_large', 'SQL payload is too large.', 422, [
                'max_length' => 100000,
            ]);
        }

        if(str_contains($sql, "\0")) {
            $this->respond_error('invalid_sql', 'SQL contains invalid control bytes.', 422);
        }

        $sql = preg_replace('/;\s*$/u', '', $sql) ?? $sql;

        if(str_contains($sql, ';')) {
            $this->respond_error('multiple_statements_not_allowed', 'Only one SQL statement is allowed per request.', 422);
        }

        if(preg_match('/\/\*|--\s|#/u', $sql)) {
            $this->respond_error('comments_not_allowed', 'Inline SQL comments are not allowed in the write endpoint.', 422);
        }

        return trim($sql);
    }

    private function get_statement_type(string $sql): string {
        if(preg_match('/^\s*([a-z]+)/iu', $sql, $matches)) {
            return mb_strtoupper((string) ($matches[1] ?? ''));
        }

        return '';
    }

    private function resolve_action(string $sql): string {
        $requested_action = mb_strtolower($this->get_param_string('action', 'auto'));
        $statement_type = $this->get_statement_type($sql);

        if($requested_action === 'auto' || $requested_action === '') {
            if(in_array($statement_type, ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'], true)) {
                return 'query';
            }

            if(in_array($statement_type, ['INSERT', 'UPDATE', 'DELETE', 'REPLACE'], true)) {
                return 'execute';
            }

            $this->respond_error('unsupported_statement', 'SQL statement type is not supported by ops-write auto mode.', 422, [
                'statement_type' => $statement_type,
                'allowed_query_types' => ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'],
                'allowed_execute_types' => ['INSERT', 'UPDATE', 'DELETE', 'REPLACE'],
            ]);
        }

        if(!in_array($requested_action, ['query', 'execute'], true)) {
            $this->respond_error('invalid_action', 'Write ops action is invalid.', 422, [
                'allowed_actions' => ['query', 'execute', 'auto'],
            ]);
        }

        return $requested_action;
    }

    private function ensure_allowed_statement(string $action, string $sql): string {
        $statement_type = $this->get_statement_type($sql);

        $forbidden_patterns = [
            '/\bDROP\b/iu',
            '/\bTRUNCATE\b/iu',
            '/\bALTER\b/iu',
            '/\bRENAME\b/iu',
            '/\bGRANT\b/iu',
            '/\bREVOKE\b/iu',
            '/\bLOCK\b/iu',
            '/\bUNLOCK\b/iu',
            '/\bHANDLER\b/iu',
            '/\bBENCHMARK\s*\(/iu',
            '/\bSLEEP\s*\(/iu',
            '/\bLOAD_FILE\s*\(/iu',
            '/\bLOAD\s+DATA\b/iu',
            '/\bINTO\s+OUTFILE\b/iu',
            '/\bINTO\s+DUMPFILE\b/iu',
            '/\bCREATE\s+USER\b/iu',
            '/\bCREATE\s+PROCEDURE\b/iu',
            '/\bCREATE\s+FUNCTION\b/iu',
            '/\bCREATE\s+TRIGGER\b/iu',
            '/\bCALL\b/iu',
            '/\bPREPARE\b/iu',
            '/\bEXECUTE\b/iu',
            '/\bDEALLOCATE\b/iu',
            '/\bSTART\s+TRANSACTION\b/iu',
            '/\bCOMMIT\b/iu',
            '/\bROLLBACK\b/iu',
        ];

        foreach($forbidden_patterns as $pattern) {
            if(preg_match($pattern, $sql)) {
                $this->respond_error('forbidden_sql', 'SQL contains a forbidden operation for ops-write.', 422, [
                    'statement_type' => $statement_type,
                ]);
            }
        }

        if($action === 'query' && !in_array($statement_type, ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'], true)) {
            $this->respond_error('unsupported_query_statement', 'Ops-write query mode only allows SELECT/SHOW/DESCRIBE/EXPLAIN.', 422, [
                'statement_type' => $statement_type,
            ]);
        }

        if($action === 'execute' && !in_array($statement_type, ['INSERT', 'UPDATE', 'DELETE', 'REPLACE'], true)) {
            $this->respond_error('unsupported_execute_statement', 'Ops-write execute mode only allows INSERT/UPDATE/DELETE/REPLACE.', 422, [
                'statement_type' => $statement_type,
            ]);
        }

        if(in_array($statement_type, ['UPDATE', 'DELETE'], true) && !preg_match('/\bWHERE\b|\bLIMIT\b/iu', $sql)) {
            $this->respond_error('unsafe_write_statement', 'UPDATE and DELETE statements require WHERE or LIMIT in ops-write.', 422, [
                'statement_type' => $statement_type,
            ]);
        }

        return $statement_type;
    }

    private function write_audit_log(array $entry): void {
        $path = trim((string) (defined('FCC_OPS_WRITE_AUDIT_LOG_PATH') ? FCC_OPS_WRITE_AUDIT_LOG_PATH : ''));
        $encoded = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if($encoded === false || $path === '') {
            return;
        }

        $directory = dirname($path);
        if(!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        if(@file_put_contents($path, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            error_log('[FCC Ops Write] ' . $encoded);
        }
    }

    private function execute_statement(string $action, string $sql, string $statement_type): array {
        $started_at = microtime(true);
        $label = $this->get_param_string('label');
        $identity = $this->get_client_identity();
        $result = database()->query($sql);
        $duration_ms = (int) round((microtime(true) - $started_at) * 1000);

        if($result === false) {
            $entry = [
                'logged_at' => get_date(),
                'status' => 'error',
                'action' => $action,
                'statement_type' => $statement_type,
                'label' => $label,
                'sql_hash' => hash('sha256', $sql),
                'sql_preview' => $this->excerpt($sql, 280),
                'client' => $identity,
                'duration_ms' => $duration_ms,
                'error' => database()->error,
                'error_code' => database()->errno,
            ];
            $this->write_audit_log($entry);

            $this->respond_error('sql_execution_failed', 'SQL execution failed.', 422, [
                'statement_type' => $statement_type,
                'error_code' => database()->errno,
                'database_error' => database()->error,
            ]);
        }

        $payload = [
            'statement_type' => $statement_type,
            'duration_ms' => $duration_ms,
            'sql_hash' => hash('sha256', $sql),
            'sql_preview' => $this->excerpt($sql, 280),
            'label' => $label !== '' ? $label : null,
        ];

        if($result instanceof \mysqli_result) {
            $rows = [];
            $max_rows = 200;

            while(($row = $result->fetch_assoc()) && count($rows) < $max_rows) {
                $rows[] = $row;
            }

            $payload['columns'] = array_map(static function($field) {
                return (string) ($field->name ?? '');
            }, $result->fetch_fields() ?: []);
            $payload['rows'] = $rows;
            $payload['rows_returned'] = count($rows);
            $payload['result_was_truncated'] = $result->num_rows > count($rows);
            $result->free();
        } else {
            $payload['affected_rows'] = database()->affected_rows;
            $payload['insert_id'] = database()->insert_id ?: null;
            $payload['warning_count'] = database()->warning_count;
        }

        $entry = [
            'logged_at' => get_date(),
            'status' => 'success',
            'action' => $action,
            'statement_type' => $statement_type,
            'label' => $label,
            'sql_hash' => $payload['sql_hash'],
            'sql_preview' => $payload['sql_preview'],
            'client' => $identity,
            'duration_ms' => $duration_ms,
            'result' => [
                'rows_returned' => $payload['rows_returned'] ?? null,
                'affected_rows' => $payload['affected_rows'] ?? null,
                'insert_id' => $payload['insert_id'] ?? null,
                'warning_count' => $payload['warning_count'] ?? null,
            ],
        ];
        $this->write_audit_log($entry);

        return $payload;
    }

    public function index() {
        $this->ensure_access();

        $sql = $this->normalize_sql($this->get_param_string('sql'));
        $action = $this->resolve_action($sql);
        $statement_type = $this->ensure_allowed_statement($action, $sql);
        $payload = $this->execute_statement($action, $sql, $statement_type);

        $this->respond_success($action, $payload, [
            'allowed_query_types' => ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'],
            'allowed_execute_types' => ['INSERT', 'UPDATE', 'DELETE', 'REPLACE'],
        ]);
    }

}
