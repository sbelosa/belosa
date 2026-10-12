<?php
/* Executable regression checks for the release review of 12 October 2026. The real helper sources run
 * in their own namespace against an in-memory database double and disposable temporary files. No
 * application bootstrap, real database, network access or application files are written. */

namespace Altum {
    class Teams { public static function is_delegated(): bool { return false; } public static function has_access($permission): bool { return true; } }
    class Plugin { public static function is_active($name): bool { return false; } }
}
namespace Altum\Models {
    class BiolinkBlock { public function delete($id): void { unset($GLOBALS['FIX']['biolinks_blocks'][(int) $id]); } }
}

namespace FccReleaseFixes {
    use InvalidArgumentException;
    use RuntimeException;

    if(PHP_SAPI !== 'cli') { http_response_code(404); exit; }

    $root = dirname(__DIR__);
    $temp = sys_get_temp_dir() . '/fcc-release-fixes-' . bin2hex(random_bytes(6)) . '/';
    mkdir($temp . 'block_images/', 0700, true);
    mkdir($temp . 'avatars/', 0700, true);
    define('ALTUMCODE', 66);
    define('APP_PATH', $root . '/app/');
    define('UPLOADS_PATH', $temp);
    define('UPLOADS_URL_PATH', 'uploads/');
    define('SITE_URL', 'https://forevercard.club/');

    $failures = 0;
    function check(bool $ok, string $message): void { global $failures; if(!$ok) { $failures++; fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL); } }

    /* ---------- Shared stand-ins ---------- */
    function fcc_t($text, $vars = [], $locale = null): string { return strtr((string) $text, (array) $vars); }
    function input_clean($text, $max = null): string { $text = htmlspecialchars(strip_tags(trim((string) $text)), ENT_QUOTES, 'UTF-8'); return $max ? mb_substr($text, 0, $max) : $text; }
    function get_url($text): string { return filter_var($text, FILTER_VALIDATE_URL) ? (string) $text : ''; }
    function get_domain_from_url($text): string { return (string) parse_url((string) $text, PHP_URL_HOST); }
    function settings(): object { return (object) ['links' => (object) ['image_size_limit' => 2, 'avatar_size_limit' => 2, 'blacklisted_domains' => [], 'google_safe_browsing_is_enabled' => false]]; }
    function is_uploaded_file($path): bool { return isset($GLOBALS['UPLOADED'][$path]); }
    function move_uploaded_file($from, $to): bool { return is_uploaded_file($from) && rename($from, $to); }
    final class Cache { public function deleteItem($key): void {} public function deleteItems($keys): void {} public function deleteItemsByTag($tag): void {} }
    function cache(): Cache { static $cache; return $cache ??= new Cache(); }
    final class Database { public function query($sql): bool { return true; } public function begin_transaction(): bool { return true; } public function commit(): bool { return true; } public function rollback(): bool { return true; } }
    function database(): Database { static $db; return $db ??= new Database(); }
    function fcc_partner_notify(int $uid, string $key, string $category, string $title, string $body, string $path, int $ttl = 86400, ?string $created = null): void { $GLOBALS['FIX']['notices'][$key] = $uid; }
    function fcc_partner_choice($value, array $choices): string { if(!is_string($value) || !isset($choices[$value])) throw new InvalidArgumentException('choice'); return $value; }

    /* Query builder double for the card editor. */
    final class MemoryDb {
        private array $where = [];
        public function where($key, $value, $op = '=') { $this->where[] = [$key, $value, $op]; return $this; }
        public function orderBy($key, $direction = 'ASC') { return $this; }
        private function rows(string $table): array {
            $out = [];
            foreach($GLOBALS['FIX'][$table] ?? [] as $row) {
                foreach($this->where as [$key, $value, $op]) {
                    $have = $row[$key] ?? null;
                    if($op === 'IN' ? !in_array($have, (array) $value) : $have != $value) continue 2;
                }
                $out[] = $row;
            }
            return $out;
        }
        public function getOne($table, $columns = null) { $rows = $this->rows($table); return $rows ? (object) $rows[0] : null; }
        public function get($table, $limit = null, $columns = null) { return array_map(static fn($row) => (object) $row, $this->rows($table)); }
        public function getValue($table, $column) { return count($this->rows($table)); }
        public function update($table, $values) {
            if(!empty($GLOBALS['FAIL_UPDATE'][$table])) return false;
            $key = $table === 'links' ? 'link_id' : 'biolink_block_id';
            foreach($this->rows($table) as $row) foreach($values as $column => $value) $GLOBALS['FIX'][$table][$row[$key]][$column] = $value;
            return true;
        }
        public function insert($table, $values) { $id = max(array_keys($GLOBALS['FIX'][$table] ?: [0])) + 1; $GLOBALS['FIX'][$table][$id] = $values + ['biolink_block_id' => $id]; return $id; }
    }
    function db(): MemoryDb { return new MemoryDb(); }

    /* SQL double for the migration and Moj put helpers: only the statements these functions issue. */
    function fcc_partner_one(string $sql, array $args = []): ?array {
        $f = &$GLOBALS['FIX'];
        if(str_contains($sql, 'SELECT type,status FROM users')) return ['type' => 1, 'status' => 1];
        if(str_contains($sql, 'SELECT link_id,settings,last_datetime FROM links WHERE link_id=? FOR UPDATE')) {
            $row = $f['links'][(int) $args[0]] ?? null;
            return $row ? ['link_id' => (string) $row['link_id'], 'settings' => $row['settings'], 'last_datetime' => $row['last_datetime']] : null;
        }
        if(str_contains($sql, 'FROM forever_business_daily_outcomes')) {
            $rows = array_filter($f['outcomes'], static fn($row) => $row['recorded_by_user_id'] === $args[0] && $row['action_date'] === $args[1] && $row['status'] === 'done');
            if(str_contains($sql, "completion_mode<>'known'")) $rows = array_filter($rows, static fn($row) => $row['completion_mode'] !== 'known');
            if(str_contains($sql, "difficulty IN ('easy','hard')")) $rows = array_filter($rows, static fn($row) => in_array($row['difficulty'], ['easy', 'hard'], true));
            usort($rows, static fn($a, $b) => $b['outcome_id'] <=> $a['outcome_id']);
            return $rows ? $rows[0] + ['journey_step_id' => 0, 'content_json' => '{}'] : null;
        }
        throw new RuntimeException('Unexpected single row query: ' . $sql);
    }
    function fcc_partner_rows(string $sql, array $args = []): array {
        $f = &$GLOBALS['FIX'];
        $ids = preg_match('/IN \(([\d,]+)\)/', $sql, $m) ? array_map('intval', explode(',', $m[1])) : [];
        if(str_contains($sql, 'FROM biolinks_blocks WHERE link_id=? FOR UPDATE')) return array_values(array_filter($f['biolinks_blocks'], static fn($b) => (int) $b['link_id'] === (int) $args[0]));
        if(str_contains($sql, 'SELECT * FROM fcc_content_migration_log')) return array_reverse(array_values(array_filter($f['log'], static fn($l) => $l['reverted_at'] === null)));
        if(str_contains($sql, 'SELECT biolink_block_id,link_id FROM biolinks_blocks WHERE biolink_block_id IN')) return array_values(array_filter($f['biolinks_blocks'], static fn($b) => in_array((int) $b['biolink_block_id'], $ids, true)));
        if(str_contains($sql, 'SELECT link_id FROM biolinks_blocks WHERE biolink_block_id IN')) return array_values(array_map(static fn($b) => ['link_id' => $b['link_id']], array_filter($f['biolinks_blocks'], static fn($b) => in_array((int) $b['biolink_block_id'], $ids, true))));
        if(str_contains($sql, 'SELECT link_id,user_id,url,domain_id FROM links WHERE link_id IN')) return array_values(array_filter($f['links'], static fn($l) => in_array((int) $l['link_id'], $ids, true)));
        if(str_contains($sql, 'FROM links l JOIN users u')) return array_values(array_map(static fn($l) => $l + ['name' => 'Test'], array_filter($f['links'], static fn($l) => $l['last_datetime'] === null)));
        if(str_contains($sql, 'FROM biolinks_blocks WHERE link_id IN')) return array_values(array_filter($f['biolinks_blocks'], static fn($b) => in_array((int) $b['link_id'], $ids, true)));
        throw new RuntimeException('Unexpected rows query: ' . $sql);
    }
    function fcc_partner_query(string $sql, array $args = []): object {
        $f = &$GLOBALS['FIX'];
        if(str_contains($sql, 'INSERT INTO fcc_content_migration_log')) {
            $id = count($f['log']) + 1;
            $f['log'][$id] = ['id' => $id, 'migration_key' => $args[0], 'table_name' => $args[1], 'row_id' => $args[2], 'column_name' => $args[3], 'old_value' => $args[4], 'new_value' => $args[5], 'applied_at' => $GLOBALS['NOW'], 'group_key' => $args[7] ?? null, 'reverted_at' => null];
        } elseif(preg_match('/^UPDATE `(links|biolinks_blocks)` SET `([a-z_]+)`=\? WHERE `([a-z_]+)`=\?$/', $sql, $m)) {
            if(!isset($f[$m[1]][(int) $args[1]])) throw new RuntimeException('Missing row');
            $f[$m[1]][(int) $args[1]][$m[2]] = (string) $args[0];
        } elseif(str_contains($sql, 'UPDATE fcc_content_migration_log SET reverted_by=?')) {
            $f['log'][(int) $args[1]]['reverted_at'] = $GLOBALS['NOW'];
        } elseif(str_contains($sql, 'UPDATE fcc_partner_notifications SET read_at')) {
            $f['withdrawn'][] = $args[1];
        } elseif(str_contains($sql, 'UPDATE forever_business_daily_outcomes SET difficulty=?')) {
            foreach($f['outcomes'] as &$row) if($row['outcome_id'] === $args[2]) { $row['difficulty'] = $args[0]; $row['needs_help'] = $args[1]; }
            unset($row);
        } else {
            throw new RuntimeException('Unexpected write: ' . $sql);
        }
        return (object) ['affected_rows' => 1];
    }

    foreach(['fcc_card.php', 'fcc_card_editor.php', 'fcc_content_migration.php', 'fcc_journey_tracks.php'] as $file) {
        $code = (string) file_get_contents(APP_PATH . 'helpers/' . $file);
        if(!str_starts_with($code, '<?php')) throw new RuntimeException('Unexpected source header in ' . $file);
        // Global classes the sources name without a leading backslash resolve as in the application.
        eval('namespace FccReleaseFixes; use InvalidArgumentException, RuntimeException, Throwable, Exception, DateTime, DateTimeImmutable, DateTimeZone, JsonException;' . substr($code, 5));
    }

    try {
        /* ---------- 1. The Aloe switch goes back only as a whole card ---------- */
        $classic = '{"theme":"x"}';
        $card = static fn(int $id) => ['link_id' => $id, 'user_id' => 100 + $id, 'url' => 'card' . $id, 'domain_id' => 0, 'type' => 'biolink', 'is_enabled' => 1, 'settings' => $classic, 'last_datetime' => null];
        $block = static fn(int $id, int $link, string $type, array $settings = [], string $enabled = '1') => ['biolink_block_id' => $id, 'link_id' => $link, 'user_id' => 100 + $link, 'type' => $type, 'settings' => json_encode($settings ?: ['text' => 'x']), 'is_enabled' => $enabled, 'datetime' => '2025-01-01 10:00:00', 'last_datetime' => null];
        $fresh = static function() use ($card, $block): void {
            $GLOBALS['FIX'] = ['links' => [1 => $card(1), 2 => $card(2)], 'biolinks_blocks' => [
                11 => $block(11, 1, 'heading'), 12 => $block(12, 1, 'link_save_contact', ['plan_auto_disabled' => true], '0'),
                21 => $block(21, 2, 'heading'),
            ], 'log' => [], 'notices' => [], 'withdrawn' => [], 'outcomes' => []];
            $GLOBALS['NOW'] = '2026-10-13 07:00:00';
        };
        $snapshot = static fn() => json_encode([$GLOBALS['FIX']['links'], $GLOBALS['FIX']['biolinks_blocks']]);

        $fresh(); $before = $snapshot();
        $applied = fcc_cm_apply(1, 'aloe');
        check($applied === ['applied' => 4, 'skipped' => 0], 'apply switches two cards and re-enables one block: ' . json_encode($applied));
        check(fcc_card_is_v2((object) $GLOBALS['FIX']['links'][1]) && $GLOBALS['FIX']['biolinks_blocks'][12]['is_enabled'] === '1', 'card 1 is new and its core block is on');
        check(count(array_filter($GLOBALS['FIX']['log'], static fn($l) => $l['group_key'] === 'link:1')) === 3, 'log rows carry their card');
        check(count($GLOBALS['FIX']['notices']) === 2, 'both owners get one notice');
        $again = fcc_cm_apply(1, 'aloe');
        check($again === ['applied' => 0, 'skipped' => 0], 'a second apply changes nothing');
        $reverted = fcc_cm_revert(1, 'aloe');
        check($reverted === ['reverted' => 4, 'kept' => 0] && $snapshot() === $before, 'an untouched revert restores every value exactly');
        check(count($GLOBALS['FIX']['withdrawn']) === 2, 'the notices are withdrawn');
        check(fcc_cm_revert(1, 'aloe') === ['reverted' => 0, 'kept' => 0], 'a second revert changes nothing');

        // Owner edits a block in the new editor: the block and the card get a change date.
        $fresh(); fcc_cm_apply(1, 'aloe');
        $GLOBALS['FIX']['biolinks_blocks'][11]['settings'] = '{"text":"Owner"}';
        $GLOBALS['FIX']['biolinks_blocks'][11]['last_datetime'] = '2026-10-13 08:00:00';
        $GLOBALS['FIX']['links'][1]['last_datetime'] = '2026-10-13 08:00:00';
        $reverted = fcc_cm_revert(1, 'aloe');
        check($reverted === ['reverted' => 1, 'kept' => 3], 'the edited card stays whole, the other card goes back: ' . json_encode($reverted));
        check(fcc_card_is_v2((object) $GLOBALS['FIX']['links'][1]) && $GLOBALS['FIX']['biolinks_blocks'][12]['is_enabled'] === '1', 'nothing of the edited card is reverted');

        // Owner changes the style and a block: no partial revert that would hide an edited block.
        $fresh(); fcc_cm_apply(1, 'aloe');
        $GLOBALS['FIX']['links'][1]['settings'] = json_encode(['fcc_card' => 'v2', 'fcc_card_theme' => 'smaragd']);
        $GLOBALS['FIX']['biolinks_blocks'][12]['settings'] = '{"plan_auto_disabled":false,"name":"Owner"}';
        fcc_cm_revert(1, 'aloe');
        check($GLOBALS['FIX']['biolinks_blocks'][12]['is_enabled'] === '1', 'an edited block is never switched off by a revert');

        // A block added later with the classic editor (no card change date) keeps the card.
        $fresh(); fcc_cm_apply(1, 'aloe');
        $GLOBALS['FIX']['biolinks_blocks'][13] = $block(13, 1, 'link') + [];
        $GLOBALS['FIX']['biolinks_blocks'][13]['datetime'] = '2026-10-13 09:00:00';
        $reverted = fcc_cm_revert(1, 'aloe');
        check($reverted['kept'] === 3 && fcc_card_is_v2((object) $GLOBALS['FIX']['links'][1]), 'a card with a block added after the switch stays');

        // A plan that went stale before apply skips the whole card.
        $fresh(); $plan = fcc_cm_aloe_plan();
        $GLOBALS['FIX']['biolinks_blocks'][12]['last_datetime'] = '2026-10-13 06:59:00';
        $result = fcc_cm_aloe_apply_rows($plan, 'card-aloe-2026-10-12', 1);
        check($result['applied'] === 1 && $result['skipped'] === 3 && !fcc_card_is_v2((object) $GLOBALS['FIX']['links'][1]), 'apply skips a card changed after the plan');

        /* ---------- 2. Photos: the old file goes only after a saved change ---------- */
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jA1sAAAAASUVORK5CYII=');
        $photoCase = static function(string $url, bool $failDb) use ($temp, $png): array {
            file_put_contents($temp . 'block_images/old.png', $png);
            $upload = $temp . 'upload-' . bin2hex(random_bytes(4)) . '.png';
            file_put_contents($upload, $png);
            $GLOBALS['UPLOADED'] = [$upload => true];
            $_FILES = ['photo' => ['name' => 'new.png', 'error' => UPLOAD_ERR_OK, 'size' => strlen($png), 'tmp_name' => $upload]];
            $GLOBALS['FAIL_UPDATE'] = ['biolinks_blocks' => $failDb];
            $GLOBALS['FIX'] = ['links' => [5 => ['link_id' => 5, 'user_id' => 10, 'type' => 'biolink', 'url' => 'photo-card', 'domain_id' => 0, 'settings' => '{}', 'last_datetime' => null]],
                'biolinks_blocks' => [50 => ['biolink_block_id' => 50, 'link_id' => 5, 'user_id' => 10, 'type' => 'image', 'settings' => '{"image":"old.png"}', 'location_url' => null, 'is_enabled' => 1]]];
            $error = '';
            try { fcc_card_editor_mutate((object) ['user_id' => 10, 'plan_settings' => (object) []], 5, ['action' => 'save', 'block_id' => 50, 'url' => $url, 'alt' => 'Photo']); }
            catch(\Throwable $e) { $error = get_class($e); }
            $files = array_map('basename', glob($temp . 'block_images/*') ?: []);
            $stored = json_decode($GLOBALS['FIX']['biolinks_blocks'][50]['settings'], true)['image'] ?? '';
            foreach(glob($temp . 'block_images/*') ?: [] as $path) unlink($path);
            return ['error' => $error, 'files' => $files, 'stored' => $stored];
        };
        $ok = $photoCase('https://www.example.com', false);
        check($ok['error'] === '' && $ok['stored'] !== 'old.png' && $ok['files'] === [$ok['stored']], 'success: the new photo is stored and the old one deleted: ' . json_encode($ok));
        $refused = $photoCase('https://invalid', false);
        check($refused['error'] === 'InvalidArgumentException' && $refused['files'] === ['old.png'] && $refused['stored'] === 'old.png', 'refused form keeps the old photo and leaves no new file: ' . json_encode($refused));
        $dbFail = $photoCase('https://www.example.com', true);
        check($dbFail['error'] === 'RuntimeException' && $dbFail['files'] === ['old.png'] && $dbFail['stored'] === 'old.png', 'failed write keeps the old photo and removes the new one: ' . json_encode($dbFail));
        $GLOBALS['FAIL_UPDATE'] = [];

        /* ---------- 3. Moj put feedback never rates a step confirmed as known ---------- */
        $today = (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Zagreb')))->format('Y-m-d');
        $GLOBALS['FIX'] = ['outcomes' => [
            ['outcome_id' => 101, 'recorded_by_user_id' => 7, 'action_date' => $today, 'status' => 'done', 'completion_mode' => 'standard', 'difficulty' => 'ok', 'needs_help' => 0],
            ['outcome_id' => 102, 'recorded_by_user_id' => 7, 'action_date' => $today, 'status' => 'done', 'completion_mode' => 'known', 'difficulty' => 'hard', 'needs_help' => 0],
        ]];
        check(fcc_mp_feedback_given(7) === false, 'a known step does not count as rated');
        fcc_mp_feedback(7, ['answer' => 'easy']);
        check($GLOBALS['FIX']['outcomes'][0]['difficulty'] === 'easy' && $GLOBALS['FIX']['outcomes'][1]['difficulty'] === 'hard', 'feedback rates the worked step, not the newer known one');
        check(fcc_mp_feedback_given(7) === true, 'the worked step now counts as rated');

        /* ---------- 4. Save contact uses only the owner's own internal vCard ---------- */
        $GLOBALS['FIX'] = ['links' => [
            1 => ['link_id' => 1, 'user_id' => 10, 'type' => 'vcard', 'is_enabled' => 1, 'url' => 'own-contact', 'domain_id' => 0],
            2 => ['link_id' => 2, 'user_id' => 20, 'type' => 'vcard', 'is_enabled' => 1, 'url' => 'foreign-contact', 'domain_id' => 0],
            3 => ['link_id' => 3, 'user_id' => 30, 'type' => 'vcard', 'is_enabled' => 0, 'url' => 'switched-off', 'domain_id' => 0],
        ], 'domains' => []];
        foreach([
            [SITE_URL . 'own-contact', 10, true, SITE_URL . 'own-contact'],
            ['https://www.forevercard.club/own-contact', 10, true, 'https://www.forevercard.club/own-contact'],
            ['https://external.example/own-contact', 10, true, SITE_URL . 'own-contact'],
            [SITE_URL . 'foreign-contact', 10, true, SITE_URL . 'own-contact'],
            [SITE_URL . 'unknown-page', 10, true, SITE_URL . 'own-contact'],
            ['', 10, true, SITE_URL . 'own-contact'],
            [SITE_URL . 'switched-off', 30, false, SITE_URL . 'switched-off'],
            [SITE_URL . 'foreign-contact', 30, false, SITE_URL . 'foreign-contact'],
        ] as [$url, $owner, $visible, $shown]) {
            $row = (object) ['type' => 'link_save_contact', 'location_url' => $url];
            $result = fcc_card_fix_save_contact($row, $owner);
            check($result === $visible && ($visible ? $row->location_url === $shown : true), 'save contact for ' . ($url ?: '(empty)') . ' owner ' . $owner . ': ' . var_export($result, true) . ' ' . $row->location_url);
        }

        /* ---------- 5 and 6. Deploy order, exclusions and TLS ---------- */
        $deploy = (string) file_get_contents($root . '/.github/workflows/deploy-cpanel-ftp.yml');
        $order = array_map(static fn($name) => strpos($deploy, '- name: ' . $name . "\n"), ['Deploy new helpers before app/init.php', 'Deploy app/init.php before controllers', 'Deploy app/', 'Deploy themes/', 'Publish verified deployment marker']);
        check(!in_array(false, $order, true) && $order === array_values(array_unique($order)) && $order == array_values((static function($o) { sort($o); return $o; })($order)), 'new helpers, then app/init.php, then app/, themes and the marker');
        check(str_contains($deploy, '--diff-filter=A "${BEFORE_SHA}" HEAD -- app/helpers') && str_contains($deploy, 'fetch-depth: 0'), 'new helpers come from the previous main commit');
        check(substr_count($deploy, 'state-name: .ftp-deploy-sync-state-release-') === 2 && substr_count($deploy, '${{ github.run_id }}-${{ github.run_attempt }}.json') === 2, 'each run keeps its own prerequisite state, so nothing is deleted later');
        foreach(['**/*.mmdb', 'languages/cache/**', 'config/deployment.php', '**/.git*'] as $pattern) check(str_contains($deploy, "            " . $pattern . "\n"), 'app deploy excludes ' . $pattern);
        $geolite = (string) file_get_contents($root . '/.github/workflows/refresh-geolite.yml');
        check(str_contains($geolite, '--ssl-reqd') && !preg_match('/curl(?:(?!--ssl-reqd)[^\n])*\\\\\n\s*--upload-file/', $geolite), 'GeoLite upload requires TLS');
        check(str_contains($geolite, "group: deploy-cpanel-ftp-production") && str_contains($deploy, "group: deploy-cpanel-ftp-production"), 'GeoLite and deploy share one concurrency group');
        check(str_contains((string) file_get_contents(APP_PATH . 'helpers/fcc_journey_pilot.php'), "if(function_exists('fcc_mp_after_complete'))fcc_mp_after_complete(\$uid,(int)\$s['completed']+1);"), 'changed helper tolerates a missing new helper during upload');
    } finally {
        foreach(['block_images/', 'avatars/', ''] as $folder) foreach(glob($temp . $folder . '*') ?: [] as $path) if(is_file($path)) unlink($path);
        rmdir($temp . 'block_images/'); rmdir($temp . 'avatars/'); rmdir($temp);
    }

    if($failures) { fwrite(STDERR, $failures . ' release fix check(s) failed.' . PHP_EOL); exit(1); }
    echo 'Release fix checks passed.' . PHP_EOL;
}
