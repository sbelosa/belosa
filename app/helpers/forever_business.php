<?php
/* Custom code: FC-2026-08-13: Forever business import, hierarchy and access layer */

defined('ALTUMCODE') || die();

function forever_business_ensure_tables(): void {
    static $ready = false;

    if($ready) {
        return;
    }

    $queries = [
        "CREATE TABLE IF NOT EXISTS `forever_business_imports` (
            `import_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `source_type` VARCHAR(16) NOT NULL,
            `report_kind` VARCHAR(32) NOT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `file_sha256` CHAR(64) NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'processing',
            `root_fbo_id` CHAR(12) NULL,
            `row_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `period_start` DATE NULL,
            `period_end` DATE NULL,
            `warning_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `summary_json` MEDIUMTEXT NULL,
            `imported_by_user_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `completed_at` DATETIME NULL,
            PRIMARY KEY (`import_id`),
            UNIQUE KEY `forever_business_import_sha_uq` (`file_sha256`),
            KEY `forever_business_import_status_idx` (`status`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_members` (
            `fbo_id` CHAR(12) NOT NULL,
            `name` VARCHAR(160) NOT NULL,
            `title` VARCHAR(96) NULL,
            `generation` SMALLINT UNSIGNED NULL,
            `country_code` VARCHAR(8) NULL,
            `sponsor_date` DATE NULL,
            `parent_fbo_id` CHAR(12) NULL,
            `tree_sequence` VARCHAR(64) NULL,
            `is_manager` TINYINT(1) NOT NULL DEFAULT 0,
            `is_privacy_requested` TINYINT(1) NOT NULL DEFAULT 0,
            `is_in_current_structure` TINYINT(1) NOT NULL DEFAULT 1,
            `email_hash` CHAR(64) NULL,
            `phone_hash` CHAR(64) NULL,
            `first_seen_import_id` BIGINT UNSIGNED NULL,
            `last_seen_import_id` BIGINT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`fbo_id`),
            KEY `forever_business_member_parent_idx` (`parent_fbo_id`),
            KEY `forever_business_member_manager_idx` (`is_manager`, `is_in_current_structure`),
            KEY `forever_business_member_email_hash_idx` (`email_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_metrics` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_month` DATE NOT NULL,
            `personal_cc` DECIMAL(12,3) NULL,
            `total_cc` DECIMAL(12,3) NULL,
            `total_active_cc` DECIMAL(12,3) NULL,
            `non_manager_cc` DECIMAL(12,3) NULL,
            `leadership_cc` DECIMAL(12,3) NULL,
            `is_4cc_active` TINYINT(1) NOT NULL DEFAULT 0,
            `source_import_id` BIGINT UNSIGNED NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`fbo_id`, `period_month`),
            KEY `forever_business_metrics_period_idx` (`period_month`, `is_4cc_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_yearly_metrics` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_year` SMALLINT UNSIGNED NOT NULL,
            `total_active_cc_ytd` DECIMAL(12,3) NULL,
            `non_manager_cc_ytd` DECIMAL(12,3) NULL,
            `leadership_cc_ytd` DECIMAL(12,3) NULL,
            `source_import_id` BIGINT UNSIGNED NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`fbo_id`, `period_year`),
            KEY `forever_business_yearly_period_idx` (`period_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_focus_metrics` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_month` DATE NOT NULL,
            `snapshot_date` DATE NOT NULL,
            `next_level` VARCHAR(96) NULL,
            `enrollment_date` DATE NULL,
            `last_purchase_date` DATE NULL,
            `is_active` TINYINT(1) NOT NULL DEFAULT 0,
            `was_active_previous_month` TINYINT(1) NOT NULL DEFAULT 0,
            `open_group_cc_2m` DECIMAL(12,3) NULL,
            `needed_cc_next_level` DECIMAL(12,3) NULL,
            `personal_cc` DECIMAL(12,3) NULL,
            `new_recruits` INT UNSIGNED NOT NULL DEFAULT 0,
            `sponsor_fbo_id` CHAR(12) NULL,
            `sponsor_name` VARCHAR(160) NULL,
            `source_import_id` BIGINT UNSIGNED NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`fbo_id`, `period_month`),
            KEY `forever_business_focus_period_idx` (`period_month`, `is_active`, `needed_cc_next_level`),
            KEY `forever_business_focus_sponsor_idx` (`sponsor_fbo_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_four_core_snapshots` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_month` DATE NOT NULL,
            `business_scope` VARCHAR(16) NOT NULL,
            `timeframe` VARCHAR(16) NOT NULL,
            `recruitment` DECIMAL(12,3) NULL,
            `retention` DECIMAL(12,3) NULL,
            `productivity` DECIMAL(12,3) NULL,
            `development` DECIMAL(12,3) NULL,
            `captured_at` DATETIME NOT NULL,
            `source_note` VARCHAR(255) NULL,
            PRIMARY KEY (`fbo_id`, `period_month`, `business_scope`, `timeframe`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_total_cc_snapshots` (
            `fbo_id` CHAR(12) NOT NULL,
            `period_month` DATE NOT NULL,
            `country_scope` VARCHAR(32) NOT NULL DEFAULT 'GLOBAL',
            `total_cc` DECIMAL(12,3) NOT NULL,
            `is_closed` TINYINT(1) NOT NULL DEFAULT 0,
            `captured_at` DATETIME NOT NULL,
            `source_note` VARCHAR(255) NULL,
            PRIMARY KEY (`fbo_id`, `period_month`, `country_scope`),
            KEY `forever_business_total_cc_period_idx` (`period_month`, `is_closed`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_hierarchy` (
            `ancestor_fbo_id` CHAR(12) NOT NULL,
            `descendant_fbo_id` CHAR(12) NOT NULL,
            `depth` SMALLINT UNSIGNED NOT NULL,
            `source_import_id` BIGINT UNSIGNED NULL,
            PRIMARY KEY (`ancestor_fbo_id`, `descendant_fbo_id`),
            KEY `forever_business_hierarchy_descendant_idx` (`descendant_fbo_id`, `depth`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_access` (
            `access_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT UNSIGNED NOT NULL,
            `fbo_id` CHAR(12) NOT NULL,
            `access_role` VARCHAR(24) NOT NULL DEFAULT 'manager',
            `status` VARCHAR(16) NOT NULL DEFAULT 'active',
            `granted_by_user_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`access_id`),
            UNIQUE KEY `forever_business_access_user_fbo_uq` (`user_id`, `fbo_id`),
            KEY `forever_business_access_fbo_idx` (`fbo_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_daily_outcomes` (
            `outcome_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `fbo_id` CHAR(12) NOT NULL,
            `action_date` DATE NOT NULL,
            `core_key` VARCHAR(24) NOT NULL,
            `action_key` VARCHAR(48) NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT 'done',
            `outcome_count` INT UNSIGNED NOT NULL DEFAULT 0,
            `outcome_type` VARCHAR(32) NULL,
            `note` VARCHAR(500) NULL,
            `recorded_by_user_id` INT UNSIGNED NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`outcome_id`),
            UNIQUE KEY `forever_business_outcome_daily_uq` (`fbo_id`, `action_date`, `action_key`),
            KEY `forever_business_outcome_date_idx` (`action_date`, `core_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `forever_business_page_visits` (
            `user_id` INT UNSIGNED NOT NULL,
            `visit_date` DATE NOT NULL,
            `visit_count` INT UNSIGNED NOT NULL DEFAULT 1,
            `last_visit_at` DATETIME NOT NULL,
            PRIMARY KEY (`user_id`, `visit_date`),
            KEY `forever_business_visit_date_idx` (`visit_date`, `last_visit_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];

    foreach($queries as $query) {
        db()->rawQuery($query);
    }

    $ready = true;
}

function forever_business_normalize_fbo_id($value): string {
    $digits = preg_replace('/\D+/', '', trim((string) $value));
    return strlen($digits) === 12 ? $digits : '';
}

function forever_business_normalize_header($value): string {
    $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
    $value = preg_replace('/\s+/', ' ', trim($value));
    return mb_strtoupper($value);
}

function forever_business_number($value): float {
    if($value === null || $value === '') {
        return 0.0;
    }

    $value = trim((string) $value);
    $value = str_replace([' ', ','], ['', '.'], $value);
    return is_numeric($value) ? (float) $value : 0.0;
}

function forever_business_contact_hash($value): ?string {
    $value = mb_strtolower(trim((string) $value));

    if($value === '' || $value === 'privacy requested') {
        return null;
    }

    $salt = defined('LOS_PRIVACY_HASH_SALT') ? LOS_PRIVACY_HASH_SALT : ROOT_PATH;
    return hash_hmac('sha256', $value, $salt);
}

function forever_business_phone_hash($value): ?string {
    $value = preg_replace('/\D+/', '', trim((string) $value));
    return $value === '' ? null : forever_business_contact_hash($value);
}

function forever_business_parse_date($value): ?string {
    $value = trim((string) $value);

    if($value === '') {
        return null;
    }

    foreach(['d-m-Y', 'Y-m-d', 'd.m.Y', 'm/d/Y'] as $format) {
        $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
        if($date instanceof \DateTimeImmutable) {
            return $date->format('Y-m-d');
        }
    }

    return null;
}

function forever_business_period_from_label($value): ?string {
    $value = mb_strtoupper(trim((string) $value));
    if(preg_match('/^(20\d{2})-(0[1-9]|1[0-2])(?:-(?:0[1-9]|[12]\d|3[01]))?$/', $value, $iso_matches)) {
        return sprintf('%04d-%02d-01', (int) $iso_matches[1], (int) $iso_matches[2]);
    }
    $months = [
        'JAN' => 1, 'FEB' => 2, 'MAR' => 3, 'APR' => 4,
        'MAY' => 5, 'JUN' => 6, 'JUL' => 7, 'AUG' => 8,
        'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DEC' => 12,
    ];

    if(!preg_match('/\b(' . implode('|', array_keys($months)) . ')\b\s*(?:-|\/|\s)*\s*(20\d{2})\b/', $value, $matches)) {
        return null;
    }

    return sprintf('%04d-%02d-01', (int) $matches[2], $months[$matches[1]]);
}

function forever_business_read_csv(string $path): array {
    $handle = fopen($path, 'rb');

    if(!$handle) {
        throw new \RuntimeException('CSV datoteku nije moguće otvoriti.');
    }

    $sample = fgets($handle);
    rewind($handle);
    $delimiters = [',' => substr_count((string) $sample, ','), ';' => substr_count((string) $sample, ';'), "\t" => substr_count((string) $sample, "\t")];
    arsort($delimiters);
    $delimiter = (string) array_key_first($delimiters);
    $rows = [];

    while(($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
        $clean = [];
        foreach($row as $value) {
            $value = (string) $value;
            if(!mb_check_encoding($value, 'UTF-8')) {
                $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1250');
            }
            $clean[] = trim($value);
        }
        if(!empty(array_filter($clean, static fn($value) => $value !== ''))) {
            $rows[] = $clean;
        }
    }

    fclose($handle);
    return $rows;
}

function forever_business_xlsx_column_index(string $reference): int {
    preg_match('/^[A-Z]+/i', $reference, $matches);
    $letters = mb_strtoupper($matches[0] ?? 'A');
    $index = 0;

    for($i = 0, $length = strlen($letters); $i < $length; $i++) {
        $index = ($index * 26) + (ord($letters[$i]) - 64);
    }

    return max(0, $index - 1);
}

function forever_business_read_xlsx(string $path): array {
    $zip = new \ZipArchive();

    if($zip->open($path) !== true) {
        throw new \RuntimeException('Excel datoteku nije moguće otvoriti.');
    }

    $shared = [];
    $shared_xml_raw = $zip->getFromName('xl/sharedStrings.xml');
    if($shared_xml_raw !== false) {
        $shared_xml = simplexml_load_string($shared_xml_raw);
        if($shared_xml) {
            $shared_xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach($shared_xml->xpath('//m:si') ?: [] as $item) {
                $parts = [];
                foreach($item->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                    $parts[] = (string) $text;
                }
                $shared[] = implode('', $parts);
            }
        }
    }

    $workbook_raw = $zip->getFromName('xl/workbook.xml');
    $relations_raw = $zip->getFromName('xl/_rels/workbook.xml.rels');

    if($workbook_raw === false || $relations_raw === false) {
        $zip->close();
        throw new \RuntimeException('Excel struktura nije podržana.');
    }

    $workbook = simplexml_load_string($workbook_raw);
    $relations = simplexml_load_string($relations_raw);
    $workbook->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $relation_map = [];

    $relations->registerXPathNamespace('p', 'http://schemas.openxmlformats.org/package/2006/relationships');
    foreach($relations->xpath('//p:Relationship') ?: [] as $relation) {
        $relation_map[(string) $relation['Id']] = (string) $relation['Target'];
    }

    $sheets = $workbook->xpath('//m:sheets/m:sheet') ?: [];
    if(empty($sheets)) {
        $zip->close();
        throw new \RuntimeException('Excel nema radni list.');
    }

    $relationship_attributes = $sheets[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $relationship_id = (string) ($relationship_attributes['id'] ?? '');
    $target = $relation_map[$relationship_id] ?? '';

    if($target === '') {
        $zip->close();
        throw new \RuntimeException('Excel radni list nije pronađen.');
    }

    $sheet_path = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/' . ltrim($target, '/');
    $sheet_path = str_replace('xl/../', '', $sheet_path);
    $sheet_raw = $zip->getFromName($sheet_path);
    $zip->close();

    if($sheet_raw === false) {
        throw new \RuntimeException('Excel sadržaj nije pronađen.');
    }

    $sheet = simplexml_load_string($sheet_raw);
    $sheet->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $rows = [];

    foreach($sheet->xpath('//m:sheetData/m:row') ?: [] as $row_node) {
        $row = [];
        foreach($row_node->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->c as $cell) {
            $cell_attributes = $cell->attributes();
            $reference = (string) ($cell_attributes['r'] ?? 'A1');
            $index = forever_business_xlsx_column_index($reference);
            $type = (string) ($cell_attributes['t'] ?? '');
            $value = '';

            if($type === 'inlineStr') {
                $parts = [];
                foreach($cell->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                    $parts[] = (string) $text;
                }
                $value = implode('', $parts);
            } else {
                $cell_children = $cell->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $raw_value = (string) ($cell_children->v ?? '');
                $value = $type === 's' ? ($shared[(int) $raw_value] ?? '') : $raw_value;
            }

            $row[$index] = trim((string) $value);
        }

        if(!empty($row)) {
            ksort($row);
            $width = max(array_keys($row)) + 1;
            $normalized = array_fill(0, $width, '');
            foreach($row as $index => $value) {
                $normalized[$index] = $value;
            }
            $rows[] = $normalized;
        }
    }

    return $rows;
}

function forever_business_read_report_file(string $path, string $original_name): array {
    $extension = mb_strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    if($extension === 'csv') {
        return forever_business_read_csv($path);
    }

    if($extension === 'xlsx') {
        return forever_business_read_xlsx($path);
    }

    throw new \RuntimeException('Podržani su CSV i XLSX izvještaji. PDF ide u ručnu provjeru prije importa.');
}

function forever_business_parse_report(string $path, string $original_name, string $root_fbo_id = '', string $root_name = '', string $report_period = ''): array {
    $rows = forever_business_read_report_file($path, $original_name);

    if(count($rows) < 2) {
        throw new \RuntimeException('Izvještaj nema podatkovne retke.');
    }

    $headers = array_map('forever_business_normalize_header', $rows[0]);
    $header_map = [];
    foreach($headers as $index => $header) {
        $header_map[$header] = $index;
    }

    $is_full = isset($header_map['FBO ID'], $header_map['TREESEQUENCE'], $header_map['NAME'], $header_map['TITLE'], $header_map['GENERATION']);
    $is_four_cc = isset($header_map['FBO ID'], $header_map['FBO NAME'], $header_map['PERSONAL CC'], $header_map['TOTAL ACTIVE CC'], $header_map['SELECTED MONTH/YEAR']);
    $is_focus_group = isset($header_map['FBO ID'], $header_map['FBO NAME'], $header_map['CURRENT LEVEL'], $header_map['NEXT LEVEL'], $header_map['LAST PURCHASE DATE'], $header_map['NEEDED CC FOR NEXT LEVEL']);

    if(!$is_full && !$is_four_cc && !$is_focus_group) {
        throw new \RuntimeException('Struktura izvještaja nije prepoznata. Očekuje se FLP360 downline CSV, 4 CC Active XLSX ili Focus Group XLSX.');
    }

    $kind = $is_full ? 'downline' : ($is_four_cc ? 'four_cc_active' : 'focus_group');

    $report = [
        'kind' => $kind,
        'source_type' => mb_strtolower(pathinfo($original_name, PATHINFO_EXTENSION)),
        'original_name' => mb_substr(basename($original_name), 0, 255),
        'root_fbo_id' => forever_business_normalize_fbo_id($root_fbo_id),
        'root_name' => trim($root_name),
        'members' => [],
        'metrics' => [],
        'yearly_metrics' => [],
        'focus_metrics' => [],
        'parent_map' => [],
        'periods' => [],
        'warnings' => [],
        'errors' => [],
    ];

    if($is_full) {
        if($report['root_fbo_id'] === '') {
            $report['errors'][] = 'Za downline izvještaj nedostaje glavni Forever ID.';
        }

        $metric_columns = [];
        $yearly_metric_columns = [];
        foreach($headers as $index => $header) {
            if(preg_match('/^(4CC ACTIVE|PERSONAL CC|TOTAL CC|TOTAL ACTIVE CC|NON MANAGER CC|LEADERSHIP CC) - ([A-Z]{3}) - (20\d{2})$/', $header, $matches)) {
                $period = forever_business_period_from_label($matches[2] . ' ' . $matches[3]);
                if($period) {
                    $metric_columns[] = ['index' => $index, 'metric' => $matches[1], 'period' => $period];
                    $report['periods'][$period] = true;
                }
            }
            if(preg_match('/^(TOTAL ACTIVE CC|NON MANAGER CC|LEADERSHIP CC) YTD - (20\d{2})$/', $header, $matches)) {
                $yearly_metric_columns[] = ['index' => $index, 'metric' => $matches[1], 'year' => (int) $matches[2]];
            }
        }

        if(empty($metric_columns)) {
            $report['errors'][] = 'Nisu pronađeni mjesečni CC stupci.';
        }

        $seen_ids = [];
        $stack = [];
        if($report['root_fbo_id'] !== '') {
            $stack[0] = $report['root_fbo_id'];
        }

        foreach(array_slice($rows, 1) as $row_number => $row) {
            $fbo_id = forever_business_normalize_fbo_id($row[$header_map['FBO ID']] ?? '');
            if($fbo_id === '') {
                $report['errors'][] = 'Redak ' . ($row_number + 2) . ': neispravan FBO ID.';
                continue;
            }
            if(isset($seen_ids[$fbo_id])) {
                $report['errors'][] = 'Duplikat FBO ID-a ' . $fbo_id . '.';
                continue;
            }
            $seen_ids[$fbo_id] = true;

            $generation = (int) ($row[$header_map['GENERATION']] ?? 0);
            if($generation < 1 || ($generation > 1 && empty($stack[$generation - 1]))) {
                $report['errors'][] = 'Redak ' . ($row_number + 2) . ': hijerarhija nema roditelja za generaciju ' . $generation . '.';
                continue;
            }

            $parent_fbo_id = $generation === 1 ? $report['root_fbo_id'] : ($stack[$generation - 1] ?? '');
            $stack[$generation] = $fbo_id;
            foreach(array_keys($stack) as $depth) {
                if($depth > $generation) unset($stack[$depth]);
            }

            $title = trim((string) ($row[$header_map['TITLE']] ?? ''));
            $name = trim((string) ($row[$header_map['NAME']] ?? ''));
            $report['members'][$fbo_id] = [
                'fbo_id' => $fbo_id,
                'name' => $name !== '' ? mb_substr($name, 0, 160) : 'Bez imena',
                'title' => mb_substr($title, 0, 96),
                'generation' => $generation,
                'country_code' => mb_substr(trim((string) ($row[$header_map['COUNTRY']] ?? '')), 0, 8),
                'sponsor_date' => forever_business_parse_date($row[$header_map['SPONSOR DATE']] ?? ''),
                'parent_fbo_id' => $parent_fbo_id,
                'tree_sequence' => mb_substr(trim((string) ($row[$header_map['TREESEQUENCE']] ?? '')), 0, 64),
                'is_manager' => stripos($title, 'manager') !== false ? 1 : 0,
                'is_privacy_requested' => stripos($name, 'privacy requested') !== false ? 1 : 0,
                'email_hash' => forever_business_contact_hash($row[$header_map['EMAIL']] ?? ''),
                'phone_hash' => forever_business_phone_hash($row[$header_map['PHONE']] ?? ''),
            ];
            $report['parent_map'][$fbo_id] = $parent_fbo_id;

            foreach($metric_columns as $column) {
                $key = $fbo_id . '|' . $column['period'];
                if(!isset($report['metrics'][$key])) {
                    $report['metrics'][$key] = [
                        'fbo_id' => $fbo_id,
                        'period_month' => $column['period'],
                        'personal_cc' => null,
                        'total_cc' => null,
                        'total_active_cc' => null,
                        'non_manager_cc' => null,
                        'leadership_cc' => null,
                        'is_4cc_active' => 0,
                    ];
                }
                $value = $row[$column['index']] ?? '';
                $field_map = [
                    'PERSONAL CC' => 'personal_cc',
                    'TOTAL CC' => 'total_cc',
                    'TOTAL ACTIVE CC' => 'total_active_cc',
                    'NON MANAGER CC' => 'non_manager_cc',
                    'LEADERSHIP CC' => 'leadership_cc',
                ];
                if($column['metric'] === '4CC ACTIVE') {
                    $report['metrics'][$key]['is_4cc_active'] = mb_strtoupper(trim((string) $value)) === 'Y' ? 1 : 0;
                } elseif(isset($field_map[$column['metric']])) {
                    $report['metrics'][$key][$field_map[$column['metric']]] = forever_business_number($value);
                }
            }

            foreach($yearly_metric_columns as $column) {
                $key = $fbo_id . '|' . $column['year'];
                if(!isset($report['yearly_metrics'][$key])) {
                    $report['yearly_metrics'][$key] = [
                        'fbo_id' => $fbo_id,
                        'period_year' => $column['year'],
                        'total_active_cc_ytd' => null,
                        'non_manager_cc_ytd' => null,
                        'leadership_cc_ytd' => null,
                    ];
                }
                $field_map = [
                    'TOTAL ACTIVE CC' => 'total_active_cc_ytd',
                    'NON MANAGER CC' => 'non_manager_cc_ytd',
                    'LEADERSHIP CC' => 'leadership_cc_ytd',
                ];
                $report['yearly_metrics'][$key][$field_map[$column['metric']]] = forever_business_number($row[$column['index']] ?? 0);
            }
        }
    } elseif($is_four_cc) {
        $seen_ids = [];
        foreach(array_slice($rows, 1) as $row_number => $row) {
            $fbo_id = forever_business_normalize_fbo_id($row[$header_map['FBO ID']] ?? '');
            $period = forever_business_period_from_label($row[$header_map['SELECTED MONTH/YEAR']] ?? '');
            if($fbo_id === '' || !$period) {
                $report['errors'][] = 'Redak ' . ($row_number + 2) . ': neispravan FBO ID ili mjesec.';
                continue;
            }
            if(isset($seen_ids[$fbo_id])) {
                $report['errors'][] = 'Duplikat FBO ID-a ' . $fbo_id . '.';
                continue;
            }
            $seen_ids[$fbo_id] = true;
            $title = trim((string) ($row[$header_map['LEVEL']] ?? ''));
            $name = trim((string) ($row[$header_map['FBO NAME']] ?? ''));
            $report['periods'][$period] = true;
            $report['members'][$fbo_id] = [
                'fbo_id' => $fbo_id,
                'name' => $name !== '' ? mb_substr($name, 0, 160) : 'Bez imena',
                'title' => mb_substr($title, 0, 96),
                'generation' => null,
                'country_code' => mb_substr(trim((string) ($row[$header_map['HOME COUNTRY']] ?? '')), 0, 8),
                'sponsor_date' => null,
                'parent_fbo_id' => null,
                'tree_sequence' => null,
                'is_manager' => stripos($title, 'manager') !== false ? 1 : 0,
                'is_privacy_requested' => stripos($name, 'privacy requested') !== false ? 1 : 0,
                'email_hash' => null,
                'phone_hash' => null,
            ];
            $report['metrics'][$fbo_id . '|' . $period] = [
                'fbo_id' => $fbo_id,
                'period_month' => $period,
                'personal_cc' => forever_business_number($row[$header_map['PERSONAL CC']] ?? 0),
                'total_cc' => null,
                'total_active_cc' => forever_business_number($row[$header_map['TOTAL ACTIVE CC']] ?? 0),
                'non_manager_cc' => null,
                'leadership_cc' => null,
                'is_4cc_active' => 1,
            ];
        }
    } else {
        $period = forever_business_period_from_label($report_period);
        if(!$period) {
            $report['errors'][] = 'Za Focus Group izvještaj odaberi mjesec na koji se izvještaj odnosi.';
        }

        $seen_ids = [];
        foreach(array_slice($rows, 1) as $row_number => $row) {
            $fbo_id = forever_business_normalize_fbo_id($row[$header_map['FBO ID']] ?? '');
            if($fbo_id === '') {
                $report['errors'][] = 'Redak ' . ($row_number + 2) . ': neispravan FBO ID.';
                continue;
            }
            if(isset($seen_ids[$fbo_id])) {
                $report['errors'][] = 'Duplikat FBO ID-a ' . $fbo_id . '.';
                continue;
            }
            $seen_ids[$fbo_id] = true;

            $title = trim((string) ($row[$header_map['CURRENT LEVEL']] ?? ''));
            $name = trim((string) ($row[$header_map['FBO NAME']] ?? ''));
            $sponsor_fbo_id = forever_business_normalize_fbo_id($row[$header_map['SPONSOR ID']] ?? '');
            $personal_cc = forever_business_number($row[$header_map['PERSONAL CC']] ?? 0);
            $is_active = mb_strtoupper(trim((string) ($row[$header_map['ACTIVE']] ?? ''))) === 'YES' ? 1 : 0;
            if($period) $report['periods'][$period] = true;

            $report['members'][$fbo_id] = [
                'fbo_id' => $fbo_id,
                'name' => $name !== '' ? mb_substr($name, 0, 160) : 'Bez imena',
                'title' => mb_substr($title, 0, 96),
                'generation' => isset($header_map['GEN']) ? max(0, (int) ($row[$header_map['GEN']] ?? 0)) : null,
                'country_code' => null,
                'sponsor_date' => forever_business_parse_date($row[$header_map['ENROLLMENT DATE']] ?? ''),
                'parent_fbo_id' => $sponsor_fbo_id ?: null,
                'tree_sequence' => null,
                'is_manager' => stripos($title, 'manager') !== false ? 1 : 0,
                'is_privacy_requested' => stripos($name, 'privacy requested') !== false ? 1 : 0,
                'email_hash' => null,
                'phone_hash' => null,
            ];

            if($period) {
                $report['metrics'][$fbo_id . '|' . $period] = [
                    'fbo_id' => $fbo_id,
                    'period_month' => $period,
                    'personal_cc' => $personal_cc,
                    'total_cc' => null,
                    'total_active_cc' => null,
                    'non_manager_cc' => null,
                    'leadership_cc' => null,
                    'is_4cc_active' => $is_active,
                ];
                $report['focus_metrics'][$fbo_id . '|' . $period] = [
                    'fbo_id' => $fbo_id,
                    'period_month' => $period,
                    'snapshot_date' => date('Y-m-d'),
                    'next_level' => mb_substr(trim((string) ($row[$header_map['NEXT LEVEL']] ?? '')), 0, 96),
                    'enrollment_date' => forever_business_parse_date($row[$header_map['ENROLLMENT DATE']] ?? ''),
                    'last_purchase_date' => forever_business_parse_date($row[$header_map['LAST PURCHASE DATE']] ?? ''),
                    'is_active' => $is_active,
                    'was_active_previous_month' => mb_strtoupper(trim((string) ($row[$header_map['PREVIOUS MONTH ACTIVE']] ?? ''))) === 'YES' ? 1 : 0,
                    'open_group_cc_2m' => forever_business_number($row[$header_map['2 MONTHS OPEN GROUP CC']] ?? 0),
                    'needed_cc_next_level' => forever_business_number($row[$header_map['NEEDED CC FOR NEXT LEVEL']] ?? 0),
                    'personal_cc' => $personal_cc,
                    'new_recruits' => max(0, (int) forever_business_number($row[$header_map['NEW RECRUITS']] ?? 0)),
                    'sponsor_fbo_id' => $sponsor_fbo_id ?: null,
                    'sponsor_name' => mb_substr(trim((string) ($row[$header_map['SPONSOR NAME']] ?? '')), 0, 160),
                ];
            }
        }
    }

    $report['periods'] = array_keys($report['periods']);
    sort($report['periods']);
    $latest_period = !empty($report['periods']) ? end($report['periods']) : null;
    $latest_metrics = array_values(array_filter($report['metrics'], static fn($metric) => $metric['period_month'] === $latest_period));
    $report['summary'] = [
        'kind' => $report['kind'],
        'rows' => count($report['members']),
        'managers' => count(array_filter($report['members'], static fn($member) => !empty($member['is_manager']))),
        'periods' => $report['periods'],
        'latest_period' => $latest_period,
        'latest_personal_cc' => array_sum(array_map(static fn($metric) => (float) ($metric['personal_cc'] ?? 0), $latest_metrics)),
        'latest_personal_active' => count(array_filter($latest_metrics, static fn($metric) => (float) ($metric['personal_cc'] ?? 0) > 0)),
        'latest_4cc_active' => count(array_filter($latest_metrics, static fn($metric) => !empty($metric['is_4cc_active']))),
        'yearly_rows' => count($report['yearly_metrics']),
        'focus_rows' => count($report['focus_metrics']),
    ];

    return $report;
}

function forever_business_upsert_member(array $member, int $import_id, bool $preserve_hierarchy = false): void {
    $now = get_date();
    $data = [
        'fbo_id' => $member['fbo_id'],
        'name' => $member['name'],
        'title' => $member['title'] ?: null,
        'generation' => $member['generation'],
        'country_code' => $member['country_code'] ?: null,
        'sponsor_date' => $member['sponsor_date'],
        'parent_fbo_id' => $member['parent_fbo_id'] ?: null,
        'tree_sequence' => $member['tree_sequence'] ?: null,
        'is_manager' => (int) $member['is_manager'],
        'is_privacy_requested' => (int) $member['is_privacy_requested'],
        'is_in_current_structure' => 1,
        'email_hash' => $member['email_hash'],
        'phone_hash' => $member['phone_hash'],
        'first_seen_import_id' => $import_id,
        'last_seen_import_id' => $import_id,
        'created_at' => $now,
        'updated_at' => $now,
    ];

    $update_columns = ['name', 'title', 'country_code', 'is_manager', 'is_privacy_requested', 'is_in_current_structure', 'last_seen_import_id', 'updated_at'];
    if(!$preserve_hierarchy) {
        array_push($update_columns, 'generation', 'sponsor_date', 'parent_fbo_id', 'tree_sequence', 'email_hash', 'phone_hash');
    }

    db()->onDuplicate($update_columns)->insert('forever_business_members', $data);
}

function forever_business_import_report(array $report, string $file_sha256, int $user_id): array {
    forever_business_ensure_tables();

    if(!empty($report['errors'])) {
        throw new \RuntimeException(implode(' ', array_slice($report['errors'], 0, 5)));
    }

    /* Focus Group exports do not carry a period in their filename or rows. Include the
       confirmed report period in the idempotency key so the same export can be used for
       a corrected month without weakening duplicate protection inside that month. */
    $dedupe_sha256 = $report['kind'] === 'focus_group'
        ? hash('sha256', $file_sha256 . '|' . implode(',', $report['periods']))
        : $file_sha256;

    $existing = db()->where('file_sha256', $dedupe_sha256)->getOne('forever_business_imports');
    if($existing && $existing->status === 'completed') {
        return ['duplicate' => true, 'import_id' => (int) $existing->import_id, 'summary' => json_decode($existing->summary_json ?? '{}', true) ?: []];
    }
    if($existing) {
        db()->where('import_id', $existing->import_id)->delete('forever_business_imports');
    }

    $periods = $report['periods'];
    $import_id = db()->insert('forever_business_imports', [
        'source_type' => $report['source_type'],
        'report_kind' => $report['kind'],
        'original_name' => $report['original_name'],
        'file_sha256' => $dedupe_sha256,
        'status' => 'processing',
        'root_fbo_id' => $report['root_fbo_id'] ?: null,
        'row_count' => count($report['members']),
        'period_start' => !empty($periods) ? reset($periods) : null,
        'period_end' => !empty($periods) ? end($periods) : null,
        'warning_count' => count($report['warnings']),
        'summary_json' => json_encode($report['summary'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'imported_by_user_id' => $user_id,
        'created_at' => get_date(),
        'completed_at' => null,
    ]);

    db()->startTransaction();

    try {
        if($report['kind'] === 'downline') {
            db()->update('forever_business_members', ['is_in_current_structure' => 0, 'updated_at' => get_date()]);

            $root_id = $report['root_fbo_id'];
            forever_business_upsert_member([
                'fbo_id' => $root_id,
                'name' => $report['root_name'] !== '' ? mb_substr($report['root_name'], 0, 160) : 'Glavni tim',
                'title' => 'Root Manager',
                'generation' => 0,
                'country_code' => null,
                'sponsor_date' => null,
                'parent_fbo_id' => null,
                'tree_sequence' => '0',
                'is_manager' => 1,
                'is_privacy_requested' => 0,
                'email_hash' => null,
                'phone_hash' => null,
            ], $import_id, false);
        }

        foreach($report['members'] as $member) {
            forever_business_upsert_member($member, $import_id, $report['kind'] !== 'downline');
        }

        foreach($report['metrics'] as $metric) {
            $metric_update_columns = $report['kind'] === 'four_cc_active'
                ? ['personal_cc', 'total_active_cc', 'is_4cc_active', 'source_import_id', 'updated_at']
                : ($report['kind'] === 'focus_group'
                    ? ['personal_cc', 'is_4cc_active', 'source_import_id', 'updated_at']
                    : ['personal_cc', 'total_cc', 'total_active_cc', 'non_manager_cc', 'leadership_cc', 'is_4cc_active', 'source_import_id', 'updated_at']);
            db()->onDuplicate($metric_update_columns)->insert('forever_business_metrics', [
                'fbo_id' => $metric['fbo_id'],
                'period_month' => $metric['period_month'],
                'personal_cc' => $metric['personal_cc'],
                'total_cc' => $metric['total_cc'],
                'total_active_cc' => $metric['total_active_cc'],
                'non_manager_cc' => $metric['non_manager_cc'],
                'leadership_cc' => $metric['leadership_cc'],
                'is_4cc_active' => (int) $metric['is_4cc_active'],
                'source_import_id' => $import_id,
                'updated_at' => get_date(),
            ]);
        }

        foreach($report['yearly_metrics'] as $metric) {
            db()->onDuplicate([
                'total_active_cc_ytd', 'non_manager_cc_ytd', 'leadership_cc_ytd', 'source_import_id', 'updated_at',
            ])->insert('forever_business_yearly_metrics', [
                'fbo_id' => $metric['fbo_id'],
                'period_year' => $metric['period_year'],
                'total_active_cc_ytd' => $metric['total_active_cc_ytd'],
                'non_manager_cc_ytd' => $metric['non_manager_cc_ytd'],
                'leadership_cc_ytd' => $metric['leadership_cc_ytd'],
                'source_import_id' => $import_id,
                'updated_at' => get_date(),
            ]);
        }

        foreach($report['focus_metrics'] as $metric) {
            db()->onDuplicate([
                'snapshot_date', 'next_level', 'enrollment_date', 'last_purchase_date', 'is_active', 'was_active_previous_month',
                'open_group_cc_2m', 'needed_cc_next_level', 'personal_cc', 'new_recruits', 'sponsor_fbo_id', 'sponsor_name',
                'source_import_id', 'updated_at',
            ])->insert('forever_business_focus_metrics', [
                'fbo_id' => $metric['fbo_id'],
                'period_month' => $metric['period_month'],
                'snapshot_date' => $metric['snapshot_date'],
                'next_level' => $metric['next_level'] ?: null,
                'enrollment_date' => $metric['enrollment_date'],
                'last_purchase_date' => $metric['last_purchase_date'],
                'is_active' => (int) $metric['is_active'],
                'was_active_previous_month' => (int) $metric['was_active_previous_month'],
                'open_group_cc_2m' => $metric['open_group_cc_2m'],
                'needed_cc_next_level' => $metric['needed_cc_next_level'],
                'personal_cc' => $metric['personal_cc'],
                'new_recruits' => (int) $metric['new_recruits'],
                'sponsor_fbo_id' => $metric['sponsor_fbo_id'],
                'sponsor_name' => $metric['sponsor_name'] ?: null,
                'source_import_id' => $import_id,
                'updated_at' => get_date(),
            ]);
        }

        if($report['kind'] === 'downline') {
            db()->delete('forever_business_hierarchy');
            $parent_map = $report['parent_map'];
            $root_id = $report['root_fbo_id'];
            $parent_map[$root_id] = null;

            foreach(array_keys($parent_map) as $descendant_id) {
                $current_id = $descendant_id;
                $depth = 0;
                $visited = [];

                while($current_id && !isset($visited[$current_id])) {
                    $visited[$current_id] = true;
                    db()->insert('forever_business_hierarchy', [
                        'ancestor_fbo_id' => $current_id,
                        'descendant_fbo_id' => $descendant_id,
                        'depth' => $depth,
                        'source_import_id' => $import_id,
                    ]);
                    $current_id = $parent_map[$current_id] ?? null;
                    $depth++;
                }
            }
        }

        db()->where('import_id', $import_id)->update('forever_business_imports', [
            'status' => 'completed',
            'completed_at' => get_date(),
        ]);
        db()->commit();
    } catch(\Throwable $exception) {
        db()->rollback();
        db()->where('import_id', $import_id)->update('forever_business_imports', [
            'status' => 'failed',
            'summary_json' => json_encode(['error' => mb_substr($exception->getMessage(), 0, 500)], JSON_UNESCAPED_UNICODE),
            'completed_at' => get_date(),
        ]);
        throw $exception;
    }

    try {
        forever_business_provision_fcc_members();
    } catch(\Throwable $exception) {
        error_log('Forever FCC placeholder provisioning failed after import: ' . $exception->getMessage());
    }

    return ['duplicate' => false, 'import_id' => (int) $import_id, 'summary' => $report['summary']];
}

function forever_business_extract_user_fbo_id($preferences): string {
    if(is_string($preferences)) {
        $preferences = json_decode($preferences ?: '{}');
    }
    if(is_array($preferences)) {
        $preferences = (object) $preferences;
    }
    $meta = is_object($preferences) ? ($preferences->meta ?? (object) []) : (object) [];
    if(is_array($meta)) {
        $meta = (object) $meta;
    }
    return forever_business_normalize_fbo_id($meta->foreverId ?? $meta->forever_id ?? $meta->foreverID ?? '');
}

function forever_business_get_periods(): array {
    forever_business_ensure_tables();
    $rows = database()->query("SELECT period_month FROM forever_business_metrics UNION SELECT period_month FROM forever_business_total_cc_snapshots ORDER BY period_month DESC");
    $periods = [];
    while($rows && $row = $rows->fetch_assoc()) {
        $periods[] = (string) $row['period_month'];
    }
    return $periods;
}

function forever_business_get_user_access_roots(int $user_id): array {
    forever_business_ensure_tables();
    return db()->join('forever_business_members m', 'm.fbo_id = a.fbo_id', 'LEFT')
        ->where('a.user_id', $user_id)
        ->where('a.status', 'active')
        ->orderBy('m.name', 'ASC')
        ->get('forever_business_access a', null, ['a.access_id', 'a.fbo_id', 'a.access_role', 'm.name', 'm.title']) ?? [];
}

function forever_business_get_scope_ids(int $user_id, bool $is_admin, string $requested_root = ''): array {
    forever_business_ensure_tables();
    $requested_root = forever_business_normalize_fbo_id($requested_root);

    if($is_admin) {
        if($requested_root !== '') {
            $rows = db()->where('ancestor_fbo_id', $requested_root)->get('forever_business_hierarchy', null, ['descendant_fbo_id']) ?? [];
            return !empty($rows) ? array_values(array_unique(array_map(static fn($row) => (string) $row->descendant_fbo_id, $rows))) : [$requested_root];
        }
        $rows = db()->where('is_in_current_structure', 1)->get('forever_business_members', null, ['fbo_id']) ?? [];
        return array_values(array_unique(array_map(static fn($row) => (string) $row->fbo_id, $rows)));
    }

    /* Privacy contract: every non-admin account is permanently scoped to the
       Forever ID stored in that account. Query parameters and legacy manager
       access records must never expand this scope. */
    $user = db()->where('user_id', $user_id)->getOne('users', ['preferences']);
    $own_fbo_id = $user ? forever_business_extract_user_fbo_id($user->preferences ?? null) : '';
    return $own_fbo_id !== '' ? [$own_fbo_id] : [];
}

function forever_business_safe_id_list(array $ids): string {
    $ids = array_values(array_unique(array_filter(array_map('forever_business_normalize_fbo_id', $ids))));
    return empty($ids) ? "''" : "'" . implode("','", $ids) . "'";
}

function forever_business_provision_fcc_members(): int {
    forever_business_ensure_tables();
    $now = database()->real_escape_string(get_date());
    $result = database()->query("INSERT IGNORE INTO forever_business_members
        (fbo_id, name, title, generation, country_code, sponsor_date, parent_fbo_id, tree_sequence,
         is_manager, is_privacy_requested, is_in_current_structure, email_hash, phone_hash,
         first_seen_import_id, last_seen_import_id, created_at, updated_at)
        SELECT
            REPLACE(JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverId')), '-', ''),
            LEFT(name, 160), 'FCC suradnik', NULL, NULL, NULL, NULL, NULL,
            0, 0, 0, NULL, NULL, NULL, NULL, '{$now}', '{$now}'
        FROM users
        WHERE type = 0 AND status = 1
          AND REPLACE(JSON_UNQUOTE(JSON_EXTRACT(preferences, '$.meta.foreverId')), '-', '') REGEXP '^[0-9]{12}$'");
    return $result ? max(0, (int) database()->affected_rows) : 0;
}

function forever_business_has_verified_four_cc_activity(array $member): bool {
    return isset($member['personal_cc'], $member['total_active_cc'])
        && !empty($member['is_4cc_active'])
        && (float) $member['personal_cc'] >= 1.0
        && (float) $member['total_active_cc'] >= 4.0;
}

function forever_business_get_verified_progress(array $member): array {
    $personal_cc = isset($member['personal_cc']) ? (float) $member['personal_cc'] : null;
    $total_active_cc = isset($member['total_active_cc']) ? (float) $member['total_active_cc'] : null;
    $has_activity_data = $personal_cc !== null && $total_active_cc !== null;
    $meets_activity_formula = $has_activity_data && $personal_cc >= 1 && $total_active_cc >= 4;
    $is_officially_active = forever_business_has_verified_four_cc_activity($member);

    $current_total_cc = isset($member['total_cc']) ? (float) $member['total_cc'] : null;
    $previous_total_cc = isset($member['previous_total_cc']) ? (float) $member['previous_total_cc'] : null;
    $two_months_ago_total_cc = isset($member['two_months_ago_total_cc']) ? (float) $member['two_months_ago_total_cc'] : null;
    $three_months_ago_total_cc = isset($member['three_months_ago_total_cc']) ? (float) $member['three_months_ago_total_cc'] : null;
    $title = trim((string) ($member['title'] ?? ''));
    $title_key = mb_strtolower($title);
    $is_assistant_manager = str_contains($title_key, 'assistant manager');
    $is_unrecognized_manager = str_contains($title_key, 'unrecognized manager');
    $is_manager_candidate = $is_assistant_manager || $is_unrecognized_manager;
    $is_full_manager = !$is_manager_candidate && str_contains($title_key, 'manager');
    $windows = [];
    $next_title = 'Supervisor';
    $rank_mode = 'rank';

    $make_window = static function(string $label, ?float $current, float $target, bool $complete, string $metric): array {
        $current = $complete ? max(0, (float) $current) : null;
        return [
            'label' => $label,
            'metric' => $metric,
            'current' => $current,
            'target' => $target,
            'gap' => $complete ? max(0, round($target - $current, 3)) : null,
            'progress' => $complete ? min(100, round(($current / $target) * 100, 1)) : 0,
            'complete' => $complete,
            'achieved' => $complete && $current >= $target,
        ];
    };

    if($is_full_manager) {
        $rank_mode = 'manager';
        $non_manager_cc = isset($member['non_manager_cc']) ? (float) $member['non_manager_cc'] : null;
        $target = $non_manager_cc !== null && $non_manager_cc >= 60 ? 100.0 : 60.0;
        $next_title = $target === 100.0 ? '100 CC Non-Manager cilj' : '60 CC Non-Manager cilj';
        $windows[] = $make_window('Ovaj mjesec', $non_manager_cc, $target, $non_manager_cc !== null, 'Non-Manager CC');
    } elseif($is_manager_candidate) {
        $next_title = $is_unrecognized_manager ? 'Recognized Manager' : 'Manager';
        $two_complete = $current_total_cc !== null && $previous_total_cc !== null;
        $four_complete = $two_complete && $two_months_ago_total_cc !== null && $three_months_ago_total_cc !== null;
        $two_total = $two_complete ? $current_total_cc + $previous_total_cc : null;
        $four_total = $four_complete ? $current_total_cc + $previous_total_cc + $two_months_ago_total_cc + $three_months_ago_total_cc : null;
        $windows[] = $make_window('Put A · 2 kalendarska mjeseca', $two_total, 120.0, $two_complete, 'Total CC');
        $windows[] = $make_window('Put B · 4 kalendarska mjeseca', $four_total, 150.0, $four_complete, 'Total CC');
    } elseif($title_key === 'supervisor') {
        $next_title = 'Assistant Manager';
        $complete = $current_total_cc !== null && $previous_total_cc !== null;
        $windows[] = $make_window('2 kalendarska mjeseca', $complete ? $current_total_cc + $previous_total_cc : null, 60.0, $complete, 'Total CC');
    } else {
        $next_title = 'Supervisor';
        $windows[] = $make_window('Ovaj mjesec', $current_total_cc, 10.0, $current_total_cc !== null, 'Total CC');
    }

    return [
        'has_activity_data' => $has_activity_data,
        'personal_cc' => $personal_cc,
        'total_active_cc' => $total_active_cc,
        'personal_progress' => $personal_cc !== null ? min(100, round(($personal_cc / 1) * 100, 1)) : 0,
        'regional_progress' => $total_active_cc !== null ? min(100, round(($total_active_cc / 4) * 100, 1)) : 0,
        'personal_gap' => $personal_cc !== null ? max(0, round(1 - $personal_cc, 3)) : null,
        'regional_gap' => $total_active_cc !== null ? max(0, round(4 - $total_active_cc, 3)) : null,
        'meets_activity_formula' => $meets_activity_formula,
        'is_officially_active' => $is_officially_active,
        'activity_source_consistent' => ((bool) !empty($member['is_4cc_active'])) === $meets_activity_formula,
        'rank' => [
            'mode' => $rank_mode,
            'current_title' => $title ?: 'Bez statusa',
            'next_title' => $next_title,
            'windows' => $windows,
        ],
    ];
}

function forever_business_get_action(array $member, ?array $metric, int $completed_total = 0): array {
    $progress = $member['verified_progress'] ?? forever_business_get_verified_progress($member);
    $is_manager = ($progress['rank']['mode'] ?? '') === 'manager';

    if(empty($progress['has_activity_data'])) {
        return [
            'core' => 'Productivity',
            'key' => 'wait_for_verified_data',
            'title' => 'Pričekaj potvrđenu FLP360 sinkronizaciju',
            'instruction' => 'Tvoj Forever ID je povezan, ali za odabrani mjesec još nema službenih CC podataka.',
            'checklist' => ['Provjeri je li Forever ID na FCC računu ispravan.', 'Pogledaj vrijeme zadnje uspješne sinkronizacije.', 'Ako si nakon tog vremena napravio/la promet, pričekaj sljedeću sinkronizaciju.'],
            'success_definition' => 'Zadatak će se pojaviti čim stignu potvrđeni podaci.',
            'target' => 0,
            'can_complete' => false,
        ];
    }

    if(empty($progress['is_officially_active'])) {
        $steps = [
            [
                'core' => 'Productivity', 'slug' => 'prepare_warm_list', 'title' => 'Pripremi listu od 10 stvarnih potreba', 'target' => 10,
                'instruction' => 'Odaberi deset osoba kojima bi jedan konkretan Forever proizvod mogao riješiti stvarnu potrebu.',
                'checklist' => ['Uz svako ime zapiši samo jednu potrebu.', 'Odaberi jedan relevantan proizvod ili rutinu.', 'Označi pet osoba kojima se javljaš prvo.'],
                'success_definition' => 'Gotovo je kada imaš 10 imena, 10 potreba i prvih 5 prioriteta.',
            ],
            [
                'core' => 'Recruitment', 'slug' => 'send_personal_messages', 'title' => 'Pošalji 5 osobnih poruka', 'target' => 5,
                'instruction' => 'Javi se prvim osobama s liste bez masovne ili copy-paste poruke.',
                'checklist' => ['Počni pitanjem o njihovoj potrebi.', 'Predloži samo jedno rješenje.', 'Dogovori jasan sljedeći korak: poziv, preporuku ili probu.'],
                'success_definition' => 'Gotovo je kada je poslano 5 osobnih poruka i zabilježen odgovor ili termin za follow-up.',
            ],
            [
                'core' => 'Productivity', 'slug' => 'complete_warm_followups', 'title' => 'Dovrši 5 toplih follow-upova', 'target' => 5,
                'instruction' => 'Nastavi razgovore koji su već otvoreni i pomogni osobi donijeti jednostavnu odluku.',
                'checklist' => ['Podsjeti se što je osoba rekla.', 'Odgovori na jednu glavnu prepreku.', 'Ponudi samo jedan konkretan sljedeći korak.'],
                'success_definition' => 'Gotovo je nakon 5 dovršenih follow-upova, bez obzira je li rezultat kupnja ili jasno „ne sada”.',
            ],
            [
                'core' => 'Retention', 'slug' => 'care_for_customers', 'title' => 'Provjeri iskustvo 3 postojeća kupca', 'target' => 3,
                'instruction' => 'Pomozi postojećim kupcima da pravilno koriste proizvod i prepoznaju treba li im nastavak.',
                'checklist' => ['Pitaj kako koriste proizvod.', 'Provjeri što im odgovara, a što ne.', 'Predloži nastavak samo ako odgovara njihovoj potrebi.'],
                'success_definition' => 'Gotovo je kada si dobio/la povratnu informaciju od 3 kupca i zapisao/la njihov sljedeći korak.',
            ],
        ];
    } elseif($is_manager) {
        $steps = [
            [
                'core' => 'Development', 'slug' => 'coach_two_people', 'title' => 'Vodi 2 suradnika kroz jedan konkretan korak', 'target' => 2,
                'instruction' => 'Odaberi dvije osobe koje imaju najveći ostvariv pomak prema aktivnosti ili Non-Manager CC-u.',
                'checklist' => ['Provjeri njihove potvrđene CC podatke.', 'Dogovorite jedan zadatak koji mogu završiti danas.', 'Odredi kada ćete provjeriti rezultat.'],
                'success_definition' => 'Gotovo je kada obje osobe imaju zapisan zadatak, rok i dogovorenu provjeru.',
            ],
            [
                'core' => 'Recruitment', 'slug' => 'business_invitations', 'title' => 'Dogovori 2 poziva na poslovnu prezentaciju', 'target' => 2,
                'instruction' => 'Pokreni osobne razgovore s ljudima koji traže dodatni prihod ili razvoj vlastitog posla.',
                'checklist' => ['Pitaj što žele promijeniti.', 'Objasni zašto misliš da bi trebali čuti plan.', 'Dogovori točan termin Zooma ili osobnog razgovora.'],
                'success_definition' => 'Gotovo je kada dvije osobe potvrde termin prezentacije.',
            ],
            [
                'core' => 'Productivity', 'slug' => 'team_followup_block', 'title' => 'Odradi timski blok od 5 follow-upova', 'target' => 5,
                'instruction' => 'Zajedno sa suradnikom dovrši pet najtoplijih razgovora koji mogu donijeti promet ili novog partnera.',
                'checklist' => ['Odaberite 5 postojećih razgovora.', 'Za svaki definirajte jednu prepreku.', 'Pošaljite personalizirani sljedeći korak.'],
                'success_definition' => 'Gotovo je kada je svih 5 razgovora ažurirano jasnim ishodom.',
            ],
            [
                'core' => 'Development', 'slug' => 'recognize_and_duplicate', 'title' => 'Prepoznaj rezultat i dupliciraj što radi', 'target' => 2,
                'instruction' => 'Pronađi jedan dobar rezultat u timu i pretvori ga u jednostavan korak koji još netko može ponoviti.',
                'checklist' => ['Pohvali osobu konkretno.', 'Zapiši što je točno napravila.', 'Podijeli taj korak s još dvije osobe.'],
                'success_definition' => 'Gotovo je kada je primjer podijeljen s dvije osobe i svaka zna što ponavlja.',
            ],
        ];
    } else {
        $steps = [
            [
                'core' => 'Recruitment', 'slug' => 'new_conversations', 'title' => 'Otvori 5 novih osobnih razgovora', 'target' => 5,
                'instruction' => 'Poveži se s pet osoba kroz stvarnu potrebu, bez slanja iste poruke svima.',
                'checklist' => ['Postavi jedno otvoreno pitanje.', 'Saslušaj prije preporuke.', 'Zapiši dogovoreni sljedeći korak.'],
                'success_definition' => 'Gotovo je kada imaš 5 novih razgovora i barem jedan dogovoreni nastavak.',
            ],
            [
                'core' => 'Productivity', 'slug' => 'story_and_replies', 'title' => 'Objavi 1 iskustveni story i odgovori zainteresiranima', 'target' => 1,
                'instruction' => 'Objavi kratko stvarno iskustvo: problem, što si koristio/la i što se promijenilo.',
                'checklist' => ['Bez zdravstvenih ili zaradnih obećanja.', 'Dodaj jednostavno pitanje ili poziv na poruku.', 'Svakoj reakciji odgovori osobno.'],
                'success_definition' => 'Gotovo je kada je story objavljen i svaka pristigla reakcija dobila osoban odgovor.',
            ],
            [
                'core' => 'Productivity', 'slug' => 'warm_followups', 'title' => 'Dovrši 5 toplih follow-upova', 'target' => 5,
                'instruction' => 'Vrati se ljudima koji već znaju za proizvod ili priliku i pomozi im odabrati sljedeći korak.',
                'checklist' => ['Podsjeti se njihove potrebe.', 'Odgovori na glavnu prepreku.', 'Ponudi jedan jasan izbor, bez pritiska.'],
                'success_definition' => 'Gotovo je kada svih 5 razgovora ima zabilježen jasan ishod.',
            ],
            [
                'core' => 'Retention', 'slug' => 'customer_checkins', 'title' => 'Napravi 3 korisnička check-ina', 'target' => 3,
                'instruction' => 'Provjeri iskustvo postojećih kupaca i pomogni im koristiti proizvod dosljedno.',
                'checklist' => ['Pitaj što im najbolje odgovara.', 'Provjeri imaju li poteškoću.', 'Dogovori nastavak ili datum nove provjere.'],
                'success_definition' => 'Gotovo je nakon 3 stvarna razgovora i zapisanog sljedećeg kontakta.',
            ],
            [
                'core' => 'Recruitment', 'slug' => 'invite_to_plan', 'title' => 'Pozovi 2 osobe da pogledaju poslovni plan', 'target' => 2,
                'instruction' => 'Pozovi osobe kojima bi odgovarao dodatni prihod, fleksibilnost ili rad s ljudima.',
                'checklist' => ['Pitaj što žele postići.', 'Najavi kratko i iskreno što će čuti.', 'Dogovori točan termin prezentacije.'],
                'success_definition' => 'Gotovo je kada dvije osobe imaju potvrđen termin.',
            ],
        ];
    }

    $step_count = count($steps);
    $step_index = $completed_total % $step_count;
    $cycle = intdiv($completed_total, $step_count) + 1;
    $action = $steps[$step_index];
    $action['key'] = mb_substr('growth_' . $action['slug'] . '_' . $cycle, 0, 48);
    $action['can_complete'] = true;
    $action['sequence_position'] = $step_index + 1;
    $action['sequence_total'] = $step_count;
    return $action;
}

function forever_business_upsert_four_core_snapshot(string $fbo_id, string $period, array $values, string $source_note = 'FLP360 4 Core Summary'): void {
    forever_business_ensure_tables();
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || $period === '') {
        throw new \InvalidArgumentException('Neispravan Forever ID ili razdoblje 4 Core snimke.');
    }

    foreach(['open' => 'open', 'downline' => 'downline'] as $scope_key => $business_scope) {
        foreach(['month', 'ytd'] as $timeframe) {
            $row = $values[$scope_key][$timeframe] ?? null;
            if(!is_array($row)) continue;
            db()->onDuplicate(['recruitment', 'retention', 'productivity', 'development', 'captured_at', 'source_note'])->insert('forever_business_four_core_snapshots', [
                'fbo_id' => $fbo_id,
                'period_month' => $period,
                'business_scope' => $business_scope,
                'timeframe' => $timeframe,
                'recruitment' => isset($row['recruitment']) ? forever_business_number($row['recruitment']) : null,
                'retention' => isset($row['retention']) ? forever_business_number($row['retention']) : null,
                'productivity' => isset($row['productivity']) ? forever_business_number($row['productivity']) : null,
                'development' => isset($row['development']) ? forever_business_number($row['development']) : null,
                'captured_at' => get_date(),
                'source_note' => mb_substr($source_note, 0, 255),
            ]);
        }
    }
}

function forever_business_get_four_core_snapshot(string $fbo_id, string $period): array {
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || $period === '') return [];

    $snapshot_period_row = db()->where('fbo_id', $fbo_id)->where('period_month', $period, '<=')->orderBy('period_month', 'DESC')->getOne('forever_business_four_core_snapshots', ['period_month']);
    if(!$snapshot_period_row) return [];
    $snapshot_period = (string) $snapshot_period_row->period_month;
    $rows = db()->where('fbo_id', $fbo_id)->where('period_month', $snapshot_period)->get('forever_business_four_core_snapshots') ?? [];
    $result = ['period_month' => $snapshot_period];
    foreach($rows as $row) {
        $result[$row->business_scope][$row->timeframe] = [
            'recruitment' => $row->recruitment,
            'retention' => $row->retention,
            'productivity' => $row->productivity,
            'development' => $row->development,
            'captured_at' => $row->captured_at,
            'source_note' => $row->source_note,
        ];
    }
    return $result;
}

function forever_business_upsert_total_cc_snapshot(string $fbo_id, string $period, float $total_cc, bool $is_closed, string $country_scope = 'GLOBAL', string $source_note = 'FLP360 Trends · Total CC'): void {
    forever_business_ensure_tables();
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    $country_scope = mb_strtoupper(trim($country_scope));
    if($fbo_id === '' || $period === '' || $country_scope === '') {
        throw new \InvalidArgumentException('Neispravan Forever ID, razdoblje ili tržište Total CC snimke.');
    }

    db()->onDuplicate(['total_cc', 'is_closed', 'captured_at', 'source_note'])->insert('forever_business_total_cc_snapshots', [
        'fbo_id' => $fbo_id,
        'period_month' => $period,
        'country_scope' => mb_substr($country_scope, 0, 32),
        'total_cc' => max(0, $total_cc),
        'is_closed' => (int) $is_closed,
        'captured_at' => get_date(),
        'source_note' => mb_substr($source_note, 0, 255),
    ]);
}

function forever_business_get_total_cc_snapshot(string $fbo_id, string $period, string $country_scope = 'GLOBAL'): ?array {
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || $period === '') return null;

    $row = db()->where('fbo_id', $fbo_id)
        ->where('period_month', $period)
        ->where('country_scope', mb_strtoupper(trim($country_scope)))
        ->getOne('forever_business_total_cc_snapshots');
    return $row ? (array) $row : null;
}

function forever_business_get_total_cc_trend(string $fbo_id, string $period, string $country_scope = 'GLOBAL', int $limit = 8): array {
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $period = forever_business_period_from_label($period) ?: '';
    if($fbo_id === '' || $period === '') return [];

    $rows = db()->where('fbo_id', $fbo_id)
        ->where('period_month', $period, '<=')
        ->where('country_scope', mb_strtoupper(trim($country_scope)))
        ->orderBy('period_month', 'DESC')
        ->get('forever_business_total_cc_snapshots', max(1, min(24, $limit))) ?? [];
    return array_reverse(array_map(static fn($row) => (array) $row, $rows));
}

function forever_business_get_dashboard(int $user_id, bool $is_admin, string $requested_root = '', string $period = ''): array {
    forever_business_ensure_tables();
    $periods = forever_business_get_periods();
    $period = in_array($period, $periods, true) ? $period : ($periods[0] ?? date('Y-m-01'));
    $previous_period = (new \DateTimeImmutable($period))->modify('-1 month')->format('Y-m-01');
    $two_months_ago_period = (new \DateTimeImmutable($period))->modify('-2 months')->format('Y-m-01');
    $three_months_ago_period = (new \DateTimeImmutable($period))->modify('-3 months')->format('Y-m-01');
    $scope_ids = forever_business_get_scope_ids($user_id, $is_admin, $requested_root);
    $id_list = forever_business_safe_id_list($scope_ids);
    $members = [];
    $dashboard_root = forever_business_normalize_fbo_id($requested_root);
    if($dashboard_root === '') {
        $dashboard_user = db()->where('user_id', $user_id)->getOne('users', ['preferences']);
        $dashboard_root = $dashboard_user ? forever_business_extract_user_fbo_id($dashboard_user->preferences ?? null) : '';
    }

    if(!empty($scope_ids)) {
        $query = "
            SELECT m.*,
                   cur.personal_cc, cur.total_cc, cur.total_active_cc, cur.non_manager_cc, cur.leadership_cc, cur.is_4cc_active,
                   prev.personal_cc AS previous_personal_cc, prev.total_cc AS previous_total_cc,
                   prev2.total_cc AS two_months_ago_total_cc,
                   prev3.total_cc AS three_months_ago_total_cc,
                   focus.snapshot_date AS focus_snapshot_date, focus.next_level, focus.last_purchase_date,
                   focus.is_active AS focus_is_active, focus.was_active_previous_month AS focus_previous_active,
                   focus.open_group_cc_2m, focus.needed_cc_next_level, focus.new_recruits,
                   COALESCE(outcomes.actions_done, 0) AS actions_done_7d,
                   COALESCE(outcomes.outcomes_total, 0) AS outcomes_total_7d,
                   COALESCE(outcomes.actions_done_total, 0) AS actions_done_total,
                   outcomes.last_action_at
            FROM forever_business_members m
            LEFT JOIN forever_business_metrics cur ON cur.fbo_id = m.fbo_id AND cur.period_month = '{$period}'
            LEFT JOIN forever_business_metrics prev ON prev.fbo_id = m.fbo_id AND prev.period_month = '{$previous_period}'
            LEFT JOIN forever_business_metrics prev2 ON prev2.fbo_id = m.fbo_id AND prev2.period_month = '{$two_months_ago_period}'
            LEFT JOIN forever_business_metrics prev3 ON prev3.fbo_id = m.fbo_id AND prev3.period_month = '{$three_months_ago_period}'
            LEFT JOIN forever_business_focus_metrics focus ON focus.fbo_id = m.fbo_id AND focus.period_month = '{$period}'
            LEFT JOIN (
                SELECT fbo_id,
                       SUM(action_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status = 'done') AS actions_done,
                       SUM(IF(action_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND status = 'done', outcome_count, 0)) AS outcomes_total,
                       SUM(status = 'done') AS actions_done_total,
                       MAX(IF(status = 'done', updated_at, NULL)) AS last_action_at
                FROM forever_business_daily_outcomes
                GROUP BY fbo_id
            ) outcomes ON outcomes.fbo_id = m.fbo_id
            WHERE m.fbo_id IN ({$id_list})
            ORDER BY COALESCE(cur.personal_cc, 0) DESC, m.name ASC
        ";
        $result = database()->query($query);
        while($result && $row = $result->fetch_assoc()) {
            $metric = [
                'personal_cc' => $row['personal_cc'],
                'total_cc' => $row['total_cc'],
                'total_active_cc' => $row['total_active_cc'],
                'non_manager_cc' => $row['non_manager_cc'],
                'leadership_cc' => $row['leadership_cc'],
                'is_4cc_active' => $row['is_4cc_active'],
            ];
            $row['verified_progress'] = forever_business_get_verified_progress($row);
            $row['next_action'] = forever_business_get_action($row, $metric, (int) ($row['actions_done_total'] ?? 0));
            $members[] = $row;
        }
    }

    $summary = [
        'members' => count($members),
        'personal_cc' => array_sum(array_map(static fn($row) => (float) ($row['personal_cc'] ?? 0), $members)),
        'personal_active' => count(array_filter($members, static fn($row) => (float) ($row['personal_cc'] ?? 0) > 0)),
        'active_4cc' => count(array_filter($members, static fn($row) => !empty($row['is_4cc_active']))),
        'zero_cc' => count(array_filter($members, static fn($row) => (float) ($row['personal_cc'] ?? 0) <= 0)),
        'managers' => count(array_filter($members, static fn($row) => !empty($row['is_manager']))),
        'focus_members' => count(array_filter($members, static fn($row) => !empty($row['focus_snapshot_date']))),
    ];
    $period_end = (new \DateTimeImmutable($period))->modify('last day of this month')->format('Y-m-d');
    $summary['recruited'] = count(array_filter($members, static fn($row) => !empty($row['sponsor_date']) && $row['sponsor_date'] >= $period && $row['sponsor_date'] <= $period_end));
    $previous_active = count(array_filter($members, static fn($row) => (float) ($row['previous_personal_cc'] ?? 0) > 0));
    $retained = count(array_filter($members, static fn($row) => (float) ($row['previous_personal_cc'] ?? 0) > 0 && (float) ($row['personal_cc'] ?? 0) > 0));
    $summary['retained'] = $retained;
    $summary['retention_rate'] = $previous_active > 0 ? round(($retained / $previous_active) * 100, 1) : 0.0;
    $summary['average_personal_cc'] = $summary['personal_active'] > 0 ? round($summary['personal_cc'] / $summary['personal_active'], 3) : 0.0;
    $summary['development_rate'] = $summary['members'] > 0 ? round(($summary['active_4cc'] / $summary['members']) * 100, 1) : 0.0;
    $official_total_cc = forever_business_get_total_cc_snapshot($dashboard_root, $period);
    $goal_current_cc = $official_total_cc ? (float) $official_total_cc['total_cc'] : (float) $summary['personal_cc'];
    $summary['goal_cc'] = 1000.0;
    $summary['goal_current_cc'] = $goal_current_cc;
    $summary['goal_metric_source'] = $official_total_cc ? 'FLP360 Total CC · ' . $official_total_cc['country_scope'] : 'FCC zbroj osobnih CC';
    $summary['goal_is_closed'] = $official_total_cc ? (bool) $official_total_cc['is_closed'] : false;
    $summary['goal_gap_cc'] = max(0, round($summary['goal_cc'] - $goal_current_cc, 3));
    $summary['goal_progress'] = min(100, round(($goal_current_cc / $summary['goal_cc']) * 100, 1));

    $trend_periods = array_reverse(array_slice(array_values(array_filter($periods, static fn($trend_period) => $trend_period <= $period)), 0, 8));
    if(!$is_admin) {
        /* A collaborator's chart must use their imported Total CC, while activity colour
         * remains tied to the official same-region 4 CC flag plus the 1 personal CC gate. */
        $metric_rows = [];
        if($dashboard_root !== '') {
            $rows = db()->where('fbo_id', $dashboard_root)
                ->where('period_month', $period, '<=')
                ->orderBy('period_month', 'DESC')
                ->get('forever_business_metrics', 8, ['period_month', 'total_cc', 'personal_cc', 'total_active_cc', 'is_4cc_active']) ?? [];
            foreach($rows as $row) {
                $row = (array) $row;
                $metric_rows[$row['period_month']] = $row;
            }
        }

        $trend = [];
        foreach($trend_periods as $trend_period) {
            $row = $metric_rows[$trend_period] ?? null;
            $has_activity_data = $row !== null
                && $row['personal_cc'] !== null
                && $row['total_active_cc'] !== null
                && array_key_exists('is_4cc_active', $row)
                && $row['is_4cc_active'] !== null;
            $is_verified_active = $has_activity_data && forever_business_has_verified_four_cc_activity($row);
            $trend[] = [
                'period_month' => $trend_period,
                'total_cc' => (float) ($row['total_cc'] ?? 0),
                'is_closed' => $trend_period < date('Y-m-01') ? 1 : 0,
                'country_scope' => 'FCC',
                'has_activity_data' => $has_activity_data,
                'is_4cc_active' => $is_verified_active,
            ];
        }
    } else {
        $trend = forever_business_get_total_cc_trend($dashboard_root, $period);
        if(empty($trend)) {
            foreach($trend_periods as $trend_period) {
                $sum = 0.0;
                if(!empty($scope_ids)) {
                    $row = database()->query("SELECT COALESCE(SUM(personal_cc), 0) AS total FROM forever_business_metrics WHERE period_month = '{$trend_period}' AND fbo_id IN ({$id_list})")->fetch_assoc();
                    $sum = (float) ($row['total'] ?? 0);
                }
                $trend[] = ['period_month' => $trend_period, 'total_cc' => $sum, 'is_closed' => $trend_period < date('Y-m-01') ? 1 : 0, 'country_scope' => 'FCC'];
            }
        }
    }
    $closed_trend = array_values(array_filter($trend, static fn($row) => !empty($row['is_closed'])));
    $closed_six = array_slice($closed_trend, -6);
    $closed_six_values = array_map(static fn($row) => (float) $row['total_cc'], $closed_six);
    $summary['closed_6m_average_cc'] = !empty($closed_six_values) ? round(array_sum($closed_six_values) / count($closed_six_values), 3) : 0.0;
    $summary['latest_closed_cc'] = !empty($closed_trend) ? (float) end($closed_trend)['total_cc'] : 0.0;
    $summary['goal_multiplier_from_average'] = $summary['closed_6m_average_cc'] > 0 ? round($summary['goal_cc'] / $summary['closed_6m_average_cc'], 2) : 0.0;

    $access_roots = $is_admin
        ? db()->where('is_manager', 1)->where('is_in_current_structure', 1)->orderBy('generation', 'ASC')->orderBy('name', 'ASC')->get('forever_business_members', null, ['fbo_id', 'name', 'title']) ?? []
        : [];

    $official_four_core = forever_business_get_four_core_snapshot($dashboard_root, $period);
    $last_sync = db()->where('status', 'completed')->orderBy('completed_at', 'DESC')->getOne('forever_business_imports', ['completed_at']);

    return [
        'period' => $period,
        'previous_period' => $previous_period,
        'periods' => $periods,
        'scope_ids' => $scope_ids,
        'access_roots' => $access_roots,
        'members' => $members,
        'summary' => $summary,
        'trend' => $trend,
        'is_manager_view' => count($scope_ids) > 1,
        'official_four_core' => $official_four_core,
        'official_total_cc' => $official_total_cc,
        'last_sync_at' => $last_sync->completed_at ?? null,
    ];
}

function forever_business_record_daily_outcome(int $user_id, string $fbo_id, array $scope_ids, array $input): bool {
    forever_business_ensure_tables();
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    if($fbo_id === '' || !in_array($fbo_id, $scope_ids, true)) {
        return false;
    }

    $allowed_cores = ['Recruitment', 'Retention', 'Productivity', 'Development'];
    $core = trim((string) ($input['core_key'] ?? ''));
    if(!in_array($core, $allowed_cores, true)) {
        return false;
    }

    $action_key = preg_replace('/[^a-z0-9_\-]/i', '', (string) ($input['action_key'] ?? ''));
    if($action_key === '') {
        return false;
    }

    db()->onDuplicate(['status', 'outcome_count', 'outcome_type', 'note', 'recorded_by_user_id', 'updated_at'])->insert('forever_business_daily_outcomes', [
        'fbo_id' => $fbo_id,
        'action_date' => date('Y-m-d'),
        'core_key' => $core,
        'action_key' => mb_substr($action_key, 0, 48),
        'status' => 'done',
        'outcome_count' => max(0, min(999, (int) ($input['outcome_count'] ?? 0))),
        'outcome_type' => mb_substr(input_clean($input['outcome_type'] ?? '', 32), 0, 32) ?: null,
        'note' => mb_substr(input_clean($input['note'] ?? '', 500), 0, 500) ?: null,
        'recorded_by_user_id' => $user_id,
        'created_at' => get_date(),
        'updated_at' => get_date(),
    ]);

    return true;
}

function forever_business_record_page_visit(int $user_id): void {
    forever_business_ensure_tables();
    if($user_id <= 0) return;
    $today = date('Y-m-d');
    $now = get_date();
    database()->query("INSERT INTO forever_business_page_visits (user_id, visit_date, visit_count, last_visit_at)
        VALUES ({$user_id}, '{$today}', 1, '{$now}')
        ON DUPLICATE KEY UPDATE visit_count = visit_count + 1, last_visit_at = VALUES(last_visit_at)");
}

function forever_business_get_usage_summary(): array {
    forever_business_ensure_tables();
    $since_30d = (new \DateTimeImmutable())->modify('-30 days')->format('Y-m-d H:i:s');
    $since_180d = (new \DateTimeImmutable())->modify('-180 days')->format('Y-m-d H:i:s');
    $since_7d_date = (new \DateTimeImmutable())->modify('-6 days')->format('Y-m-d');
    $since_30d_date = (new \DateTimeImmutable())->modify('-29 days')->format('Y-m-d');

    $accounts_result = database()->query("SELECT
        COUNT(*) AS regular_accounts,
        SUM(status = 1) AS enabled_accounts,
        SUM(last_activity >= '{$since_30d}') AS active_30d,
        SUM(last_activity >= '{$since_180d}') AS active_180d,
        SUM(last_activity IS NULL) AS never_active
        FROM users WHERE type = 0");
    $accounts = $accounts_result ? $accounts_result->fetch_assoc() : [];

    $profile_accounts_result = database()->query("SELECT
        COUNT(*) AS accounts_with_valid_fbo_id,
        SUM(u.status = 1) AS enabled_with_valid_fbo_id,
        SUM(m.fbo_id IS NOT NULL) AS accounts_with_personal_profile,
        SUM(u.status = 1 AND m.fbo_id IS NOT NULL) AS enabled_with_personal_profile,
        SUM(m.fbo_id IS NOT NULL AND m.is_in_current_structure = 0) AS waiting_profile_accounts
        FROM users u
        LEFT JOIN forever_business_members m
            ON m.fbo_id = REPLACE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.foreverId')), '-', '')
        WHERE u.type = 0
          AND REPLACE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.foreverId')), '-', '') REGEXP '^[0-9]{12}$'");
    $profile_accounts = $profile_accounts_result ? $profile_accounts_result->fetch_assoc() : [];

    $team_result = database()->query("SELECT
        COUNT(*) AS matched_team_accounts,
        SUM(u.last_activity >= '{$since_30d}') AS matched_active_30d,
        SUM(u.last_activity >= '{$since_180d}') AS matched_active_180d
        FROM users u
        INNER JOIN forever_business_members m
            ON m.fbo_id = REPLACE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.foreverId')), '-', '')
            AND m.is_in_current_structure = 1
        WHERE u.type = 0");
    $team = $team_result ? $team_result->fetch_assoc() : [];

    $managers_result = database()->query("SELECT
        COUNT(*) AS imported_managers,
        SUM(EXISTS(
            SELECT 1 FROM users u
            WHERE REPLACE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.foreverId')), '-', '') = m.fbo_id
        )) AS managers_with_fcc_account
        FROM forever_business_members m
        WHERE m.is_manager = 1 AND m.is_in_current_structure = 1");
    $managers = $managers_result ? $managers_result->fetch_assoc() : [];

    $visits_result = database()->query("SELECT
        COUNT(DISTINCT IF(visit_date >= '{$since_7d_date}', user_id, NULL)) AS four_core_users_7d,
        COUNT(DISTINCT IF(visit_date >= '{$since_30d_date}', user_id, NULL)) AS four_core_users_30d,
        COALESCE(SUM(IF(visit_date >= '{$since_30d_date}', visit_count, 0)), 0) AS four_core_visits_30d
        FROM forever_business_page_visits");
    $visits = $visits_result ? $visits_result->fetch_assoc() : [];

    $result = array_merge($accounts, $profile_accounts, $team, $managers, $visits);
    return array_map(static fn($value) => (int) ($value ?? 0), $result);
}

function forever_business_grant_access(int $user_id, string $fbo_id, string $role, int $granted_by_user_id): bool {
    forever_business_ensure_tables();
    $fbo_id = forever_business_normalize_fbo_id($fbo_id);
    $role = in_array($role, ['manager', 'co_owner'], true) ? $role : 'manager';
    $user = db()->where('user_id', $user_id)->getOne('users', ['user_id']);
    $member = $fbo_id !== '' ? db()->where('fbo_id', $fbo_id)->where('is_manager', 1)->getOne('forever_business_members', ['fbo_id']) : null;

    if(!$user || !$member) {
        return false;
    }

    db()->onDuplicate(['access_role', 'status', 'granted_by_user_id', 'updated_at'])->insert('forever_business_access', [
        'user_id' => $user_id,
        'fbo_id' => $fbo_id,
        'access_role' => $role,
        'status' => 'active',
        'granted_by_user_id' => $granted_by_user_id,
        'created_at' => get_date(),
        'updated_at' => get_date(),
    ]);

    return true;
}

function forever_business_revoke_access(int $access_id): bool {
    forever_business_ensure_tables();
    return (bool) db()->where('access_id', $access_id)->update('forever_business_access', ['status' => 'revoked', 'updated_at' => get_date()]);
}

function forever_business_grant_exact_manager_accesses(int $granted_by_user_id): int {
    forever_business_ensure_tables();
    $result = database()->query("SELECT DISTINCT u.user_id, m.fbo_id
        FROM users u
        INNER JOIN forever_business_members m
            ON m.fbo_id = REPLACE(JSON_UNQUOTE(JSON_EXTRACT(u.preferences, '$.meta.foreverId')), '-', '')
        WHERE u.type = 0 AND u.status = 1 AND m.is_manager = 1 AND m.is_in_current_structure = 1");
    $granted = 0;
    while($result && $row = $result->fetch_assoc()) {
        if(forever_business_grant_access((int) $row['user_id'], (string) $row['fbo_id'], 'manager', $granted_by_user_id)) {
            $granted++;
        }
    }
    return $granted;
}

function forever_business_get_admin_data(int $user_id, string $period = ''): array {
    forever_business_ensure_tables();
    $dashboard = forever_business_get_dashboard($user_id, true, '', $period);
    $imports = db()->orderBy('import_id', 'DESC')->get('forever_business_imports', 20) ?? [];
    $access = db()->join('users u', 'u.user_id = a.user_id', 'LEFT')
        ->join('forever_business_members m', 'm.fbo_id = a.fbo_id', 'LEFT')
        ->orderBy('a.updated_at', 'DESC')
        ->get('forever_business_access a', null, ['a.*', 'u.name AS user_name', 'u.email AS user_email', 'm.name AS member_name', 'm.title AS member_title']) ?? [];
    $managers = db()->where('is_manager', 1)->where('is_in_current_structure', 1)->orderBy('generation', 'ASC')->orderBy('name', 'ASC')->get('forever_business_members') ?? [];
    $users = db()->where('status', 1)->orderBy('name', 'ASC')->get('users', null, ['user_id', 'name', 'email', 'preferences']) ?? [];
    $users_by_fbo = [];
    $users_by_email_hash = [];

    foreach($users as $user) {
        $fbo_id = forever_business_extract_user_fbo_id($user->preferences ?? null);
        if($fbo_id !== '') {
            $users_by_fbo[$fbo_id][] = $user;
        }
        $email_hash = forever_business_contact_hash($user->email ?? '');
        if($email_hash) {
            $users_by_email_hash[$email_hash][] = $user;
        }
    }

    $active_access_keys = [];
    foreach($access as $access_row) {
        if($access_row->status === 'active') {
            $active_access_keys[$access_row->user_id . '|' . $access_row->fbo_id] = true;
        }
    }

    $suggestions = [];
    foreach($managers as $manager) {
        $candidates = [];
        foreach($users_by_fbo[$manager->fbo_id] ?? [] as $candidate) {
            $candidates[(int) $candidate->user_id] = ['user' => $candidate, 'reason' => 'Forever ID'];
        }
        foreach(!empty($manager->email_hash) ? ($users_by_email_hash[$manager->email_hash] ?? []) : [] as $candidate) {
            if(!isset($candidates[(int) $candidate->user_id])) {
                $candidates[(int) $candidate->user_id] = ['user' => $candidate, 'reason' => 'e-mail'];
            }
        }
        $suggestions[] = [
            'manager' => $manager,
            'candidates' => array_values($candidates),
            'active_user_ids' => array_values(array_filter(array_map(static function($candidate) use ($active_access_keys, $manager) {
                $id = (int) $candidate['user']->user_id;
                return isset($active_access_keys[$id . '|' . $manager->fbo_id]) ? $id : null;
            }, array_values($candidates)))),
        ];
    }

    return [
        'dashboard' => $dashboard,
        'usage' => forever_business_get_usage_summary(),
        'self_only_mode' => true,
        'imports' => $imports,
        'access' => $access,
        'managers' => $managers,
        'users' => $users,
        'suggestions' => $suggestions,
    ];
}

/* /Custom code: FC-2026-08-13 */
