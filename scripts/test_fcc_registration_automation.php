<?php
/* Custom code: FC-2026-10-07: Offline safety tests with no network, real accounts or mail delivery. */
namespace Altum\Models {
    class User {
        public function delete($id) {
            $GLOBALS['deleted_users'][] = $id;
            if(isset($GLOBALS['real_database'])) {
                $GLOBALS['real_database']->query('DELETE FROM users WHERE user_id = ' . (int) $id);
                return;
            }
            unset($GLOBALS['fake_db']->tables['users'][$id]);
        }
    }
}
namespace {
    define('ALTUMCODE', true);
    define('DEBUG', false);
    define('SITE_URL', 'https://fcc.example/');

    final class RegistrationTestRows {
        private array $rows;
        public function __construct(array $rows) { $this->rows = array_values($rows); }
        public function fetch_object() { $row = array_shift($this->rows); return $row === null ? null : (object) $row; }
        public function fetch_assoc() { return array_shift($this->rows); }
    }
    final class RegistrationTestDb {
        public array $tables = [];
        public int $count = 0;
        private array $filters = [];
        private ?array $transaction_backup = null;
        public function where($key, $value, $operator = '=') { $this->filters[] = [$key, $value, $operator]; return $this; }
        public function orderBy(...$args) { return $this; }
        private function matches(array $row): bool {
            foreach($this->filters as [$key, $value, $operator]) {
                if($operator === 'IN' ? !in_array($row[$key] ?? null, $value, true) : ($row[$key] ?? null) != $value) return false;
            }
            return true;
        }
        public function getOne($table, $columns = null) {
            $found = null;
            foreach($this->tables[$table] ?? [] as $row) if($this->matches($row)) { $found = (object) $row; break; }
            $this->filters = [];
            return $found;
        }
        public function has($table): bool { return $this->getOne($table) !== null; }
        public function update($table, $values): bool {
            $this->count = 0;
            foreach($this->tables[$table] ?? [] as $id => $row) {
                if(!$this->matches($row)) continue;
                $this->tables[$table][$id] = array_merge($row, $values);
                $this->count++;
            }
            $this->filters = [];
            return true;
        }
        public function insert($table, $row) {
            $id = count($this->tables[$table] ?? []) + 1;
            if($table === 'fcc_registration_automation_audits') $row['audit_id'] = $id;
            $this->tables[$table][$id] = $row;
            $this->filters = [];
            return $id;
        }
        public function query($sql) {
            if(str_starts_with($sql, 'CREATE TABLE')) return true;
            if(str_contains($sql, 'GET_LOCK')) return new RegistrationTestRows([['acquired' => 1]]);
            if(str_contains($sql, 'RELEASE_LOCK')) return new RegistrationTestRows([]);
            if(str_contains($sql, ' FOR UPDATE')) {
                preg_match('/user_id = (\d+)/', $sql, $match);
                return new RegistrationTestRows(isset($this->tables['users'][(int) $match[1]]) ? [$this->tables['users'][(int) $match[1]]] : []);
            }
            if(str_contains($sql, 'SELECT user_id, name, status, type FROM users')) {
                preg_match("/= '([0-9]{12})' LIMIT 2/", $sql, $match);
                $rows = [];
                foreach($this->tables['users'] ?? [] as $row) {
                    $fbo = fcc_registration_automation_preferences($row['preferences'])->meta->foreverId ?? '';
                    if($row['status'] === 1 && $fbo === ($match[1] ?? '')) $rows[] = $row;
                }
                return new RegistrationTestRows(array_slice($rows, 0, 2));
            }
            if(str_contains($sql, 'FROM users u LEFT JOIN')) return new RegistrationTestRows($this->tables['users'] ?? []);
            if(str_contains($sql, 'SELECT * FROM fcc_registration_automation_audits')) {
                return new RegistrationTestRows(array_filter($this->tables['fcc_registration_automation_audits'] ?? [], static fn($row) =>
                    in_array($row['status'], ['processing', 'rejection_pending'], true)
                    || ($row['status'] === 'approved' && ($row['email_status'] !== 'sent' || in_array($row['admin_notification_status'], ['pending', 'failed'], true)))));
            }
            throw new \RuntimeException('Unhandled test query.');
        }
        public function real_escape_string($value) { return addslashes($value); }
        public function startTransaction() { $this->transaction_backup = $this->tables; }
        public function commit() { $this->transaction_backup = null; }
        public function rollback() { if($this->transaction_backup !== null) $this->tables = $this->transaction_backup; $this->transaction_backup = null; }
    }
    function db() { return $GLOBALS['real_db_builder'] ?? $GLOBALS['fake_db']; }
    function database() { return $GLOBALS['real_database'] ?? db(); }
    function get_date() { return gmdate('Y-m-d H:i:s'); }
    function fc_resolve_language_name($language) { return $language ?: 'Hrvatski#hr'; }
    function fc_get_user_main_biolink_id($id) { return 42; }
    function url($path) { return SITE_URL . $path; }
    function l($key, $language = null) { return $key; }
    function get_email_template($a, $subject, $b, $body) { return (object) compact('subject', 'body'); }
    function send_mail(...$arguments) {
        $GLOBALS['mail_calls'][] = $arguments;
        if(isset($GLOBALS['during_mail'])) { $callback = $GLOBALS['during_mail']; unset($GLOBALS['during_mail']); $callback(); }
        return (object) ['success' => $GLOBALS['mail_success']];
    }
    function settings() { return (object) ['webhooks' => (object) ['user_new' => '']]; }
    function cache() { return new class { public function deleteItemsByTag($tag) {} public function deleteItem($key) {} }; }
    function fcc_registration_automation_notify_approval($user, $context) {
        $GLOBALS['notification_audits'][$context['audit_id']] = true;
        return ['status' => 'queued'];
    }
    require dirname(__DIR__) . '/app/helpers/fcc_registration_automation.php';

    function check(bool $condition, string $description): void {
        if(!$condition) throw new \RuntimeException('FAIL: ' . $description);
        $GLOBALS['assertion_count']++;
    }
    function fresh_user(): object {
        return (object) ['user_id' => 10, 'type' => 0, 'status' => 0, 'source' => 'direct', 'name' => 'Ana Test',
            'email' => 'ana@example.test', 'datetime' => '2026-10-07 08:00:00', 'country' => 'HR',
            'preferences' => json_encode(['meta' => ['foreverId' => '360000000010', 'fcc_access_requested_at' => '2026-10-07 08:00:00', 'fcc_registration_verification_status' => 'pending']])];
    }
    function reset_test_db(): object {
        $user = fresh_user();
        $GLOBALS['fake_db'] = new RegistrationTestDb();
        db()->tables = ['users' => [10 => (array) $user, 20 => ['user_id' => 20, 'name' => 'Sponsor', 'status' => 1, 'type' => 0,
            'preferences' => json_encode(['meta' => ['foreverId' => '360000000020']])]],
            'links' => [42 => ['link_id' => 42, 'user_id' => 10, 'type' => 'biolink', 'url' => 'ana']],
            'users_vcards' => [1 => ['user_id' => 10, 'vcard_id' => 43]],
            'fcc_registration_automation_audits' => [1 => ['audit_id' => 1, 'user_id' => 10, 'status' => 'processing',
                'registered_at' => $user->datetime, 'fbo_id' => '360000000010', 'user_snapshot_hash' => fcc_registration_automation_snapshot($user),
                'decision_result' => 'approve', 'reason' => '', 'email_status' => 'pending', 'admin_notification_status' => 'pending',
                'sponsor_user_id' => 20, 'sponsor_fbo_id' => '360000000020', 'attempts' => 0, 'updated_at' => get_date()]]];
        $GLOBALS['mail_calls'] = [];
        $GLOBALS['mail_success'] = true;
        $GLOBALS['deleted_users'] = [];
        $GLOBALS['notification_audits'] = [];
        return $user;
    }
    $GLOBALS['assertion_count'] = 0;
    if(!getenv('FCC_REGISTRATION_TEST_DB_HOST')) {
    $user = reset_test_db();
    $now = time();
    $sponsor = ['user_id' => 20, 'fbo_id' => '360000000020', 'name' => 'Sponsor', 'status' => 1, 'type' => 0];
    $decision = ['user_id' => 10, 'fbo_id' => '360000000010', 'registered_at' => $user->datetime, 'result' => 'approve',
        'evidence' => ['source' => 'flp360', 'checked_at' => gmdate('c', $now), 'exact_fbo_id' => '360000000010',
            'flp_name' => 'Ana Test', 'flp_email' => 'ANA@example.test', 'sponsor_fbo_id' => '360000000020',
            'root_fbo_id' => '360000760944', 'id_exists' => true, 'in_root_structure' => true, 'authoritative_structure' => true]];
    check(fcc_registration_automation_validate($user, $decision, [$sponsor], $now)['status'] === 'approved', 'Verified owner, exact ID, root membership and unique active sponsor approve.');
    foreach(['fcc_access_approved_at', 'fcc_access_deactivated_at', 'fcc_access_rejected_at', 'fcc_access_approval_email_sent_at'] as $marker) {
        $candidate = fresh_user(); $p = json_decode($candidate->preferences); $p->meta->$marker = '2026-10-06 00:00:00'; $candidate->preferences = json_encode($p);
        check(!fcc_registration_automation_is_pending($candidate), 'Previously handled account is excluded: ' . $marker);
    }
    $legacy = fresh_user(); $legacy->preferences = json_encode(['meta' => ['foreverId' => '360000000010']]);
    check(!fcc_registration_automation_is_pending($legacy), 'Old status zero account without an explicit request is excluded.');
    foreach(['status' => 2, 'type' => 1, 'source' => 'admin_create'] as $field => $value) {
        $candidate = fresh_user(); $candidate->$field = $value;
        check(fcc_registration_automation_validate($candidate, $decision, [$sponsor], $now)['status'] === 'unchanged', 'Nonregistration account is unchanged: ' . $field);
    }
    foreach(['', 'someone@example.test'] as $value) {
        $changed = $decision; $changed['evidence']['flp_email'] = $value;
        check(fcc_registration_automation_validate($user, $changed, [$sponsor], $now)['status'] === 'manual_review', 'Owner email mismatch cannot approve.');
    }
    $name_only = $decision; unset($name_only['evidence']['flp_email']);
    check(fcc_registration_automation_validate($user, $name_only, [$sponsor], $now)['status'] === 'manual_review', 'Exact name alone is not sufficient proof of ownership.');
    foreach([[], [$sponsor, $sponsor]] as $sponsors) check(fcc_registration_automation_validate($user, $decision, $sponsors, $now)['status'] === 'manual_review', 'Missing or ambiguous FCC sponsor stays manual.');
    foreach(['in_root_structure' => null, 'id_exists' => null, 'authoritative_structure' => false,
        'exact_fbo_id' => '360000000999', 'root_fbo_id' => '360000000999', 'checked_at' => gmdate('c', $now - 1801)] as $field => $value) {
        $changed = $decision; $changed['evidence'][$field] = $value;
        check(fcc_registration_automation_validate($user, $changed, [$sponsor], $now)['status'] === 'retry', 'Incomplete or stale proof cannot approve: ' . $field);
    }
    $changed = $decision; $changed['registered_at'] = '2026-10-06 08:00:00';
    check(fcc_registration_automation_validate($user, $changed, [$sponsor], $now)['status'] === 'manual_review', 'An altered registration snapshot cannot be decided.');
    $invalid = $decision; $invalid['result'] = 'invalid_id'; $invalid['evidence']['id_exists'] = false;
    check(fcc_registration_automation_validate($user, $invalid, [], $now)['status'] === 'retry', 'A missing ID without authoritative not found proof cannot reject.');
    $invalid['evidence']['authoritative_not_found'] = true;
    check(fcc_registration_automation_validate($user, $invalid, [], $now)['reason'] === 'invalid_id', 'Explicit authoritative invalid ID permits correct rejection reason.');
    $outside = $decision; $outside['result'] = 'valid_id_not_team'; $outside['evidence']['in_root_structure'] = false;
    check(fcc_registration_automation_validate($user, $outside, [], $now)['reason'] === 'valid_id_not_team', 'Exact known ID with authoritative exclusion selects the not team template.');
    $outside['evidence']['authoritative_structure'] = false;
    check(fcc_registration_automation_validate($user, $outside, [], $now)['status'] === 'retry', 'Absence from an incomplete or old structure never rejects.');

    $before = db()->tables;
    $dry = fcc_registration_automation_apply_decisions([$decision], true);
    check($dry['results'][0]['status'] === 'approved' && db()->tables === $before && count($GLOBALS['mail_calls']) === 0, 'Dry run predicts the decision without writes, mail or notifications.');
    check($dry['summary']['checked'] === 1 && $dry['summary']['approved'] === 1 && $dry['summary']['rejected'] === 0, 'Machine response includes safe aggregate decision counts.');
    $safe = fcc_registration_automation_safe_evidence($decision['evidence']);
    check(!isset($safe['flp_email'], $safe['flp_name']) && strlen($safe['flp_email_sha256']) === 64, 'Audit evidence does not store raw FLP names or email addresses.');

    $user = reset_test_db(); $GLOBALS['mail_success'] = false;
    $reject = fcc_registration_automation_reject($user, 'invalid_id', 1);
    check($reject['status'] === 'retry' && isset(db()->tables['users'][10]) && !$GLOBALS['deleted_users'], 'Failed rejection mail preserves the account.');
    $GLOBALS['mail_success'] = true;
    $reject = fcc_registration_automation_reject($user, 'invalid_id', 1);
    check($reject['status'] === 'rejected' && !isset(db()->tables['users'][10]) && count($GLOBALS['deleted_users']) === 1, 'Successful rejection mail precedes the single account deletion.');
    check($GLOBALS['mail_calls'][1][3]['return_transport_result'] === true, 'Rejection checks the actual transport result.');
    $user = reset_test_db();
    $GLOBALS['during_mail'] = static function() { db()->tables['users'][10]['status'] = 1; };
    $reject = fcc_registration_automation_reject($user, 'invalid_id', 1);
    check($reject['status'] === 'unchanged' && !$GLOBALS['deleted_users'] && db()->tables['users'][10]['status'] === 1, 'A concurrent manual approval is never deleted after rejection mail.');
    $user = reset_test_db();
    fcc_registration_automation_reject($user, 'invalid_id', 1, 'sent');
    check(count($GLOBALS['mail_calls']) === 0 && count($GLOBALS['deleted_users']) === 1, 'A durable sent rejection receipt allows retrying deletion without sending duplicate mail.');

    $user = reset_test_db();
    $approved = fcc_registration_automation_approve($user, $sponsor, 1);
    $stored = json_decode(db()->tables['users'][10]['preferences'])->meta;
    check($approved['status'] === 'approved' && db()->tables['users'][10]['status'] === 1 && $stored->fcc_sponsor_user_id === 20 && $stored->fcc_sponsor_fbo_id === $sponsor['fbo_id'], 'Approval activates the user and persists the verified sponsor without changing referral attribution.');
    check(!empty($stored->fcc_access_approval_email_sent_at) && $approved['notification']['status'] === 'queued', 'Approval records successful existing email and queues admin notification.');
    fcc_registration_automation_approve((object) db()->tables['users'][10], $sponsor, 1);
    check(count($GLOBALS['mail_calls']) === 1 && count($GLOBALS['notification_audits']) === 1, 'Approval retry preserves the original email receipt and stable admin event identity.');
    $user = reset_test_db(); $GLOBALS['mail_success'] = false;
    $approved = fcc_registration_automation_approve($user, $sponsor, 1);
    check($approved['email_status'] === 'pending' && db()->tables['users'][10]['status'] === 1 && empty(json_decode(db()->tables['users'][10]['preferences'])->meta->fcc_access_approval_email_sent_at), 'Failed approval email stays durably retryable without pretending it was sent.');
    $GLOBALS['mail_success'] = true;
    fcc_registration_automation_approve((object) db()->tables['users'][10], $sponsor, 1);
    check(!empty(json_decode(db()->tables['users'][10]['preferences'])->meta->fcc_access_approval_email_sent_at), 'A failed approval email can later complete successfully.');
    $user = reset_test_db(); db()->tables['users'][10]['status'] = 2;
    $failed = false;
    try { fcc_registration_automation_approve($user, $sponsor, 1); } catch(\RuntimeException $exception) { $failed = true; }
    check($failed && db()->tables['users'][10]['status'] === 2 && !$GLOBALS['mail_calls'], 'An optimistic approval cannot reactivate a concurrently disabled account.');

    $user = reset_test_db();
    $applied = fcc_registration_automation_apply_decisions([$decision]);
    check($applied['results'][0]['status'] === 'approved' && db()->tables['fcc_registration_automation_audits'][1]['status'] === 'approved', 'Applying a verified decision commits both activation and its durable audit.');
    $repeated = fcc_registration_automation_apply_decisions([$decision]);
    check($repeated['results'][0]['status'] === 'unchanged' && count($GLOBALS['mail_calls']) === 1 && db()->tables['fcc_registration_automation_audits'][1]['attempts'] === 1, 'Repeated committed approval does not send another email or alter its decision audit.');
    $user = reset_test_db(); $GLOBALS['mail_success'] = false;
    $applied = fcc_registration_automation_apply_decisions([$invalid]);
    check($applied['results'][0]['status'] === 'retry' && db()->tables['fcc_registration_automation_audits'][1]['status'] === 'rejection_pending' && isset(db()->tables['users'][10]), 'Applying an invalid decision with unavailable mail keeps a durable rejection outbox and the account.');
    $GLOBALS['mail_success'] = true;
    fcc_registration_automation_retry_notifications();
    check(db()->tables['fcc_registration_automation_audits'][1]['status'] === 'rejected' && !isset(db()->tables['users'][10]), 'A later outbox drain can deliver rejection and complete deletion.');
    $mail_count = count($GLOBALS['mail_calls']);
    fcc_registration_automation_apply_decisions([$invalid]);
    check(count($GLOBALS['mail_calls']) === $mail_count, 'A repeated rejected decision cannot send mail to a deleted account.');
    $user = reset_test_db(); $GLOBALS['mail_success'] = false;
    fcc_registration_automation_apply_decisions([$decision]);
    $GLOBALS['mail_success'] = true;
    fcc_registration_automation_retry_notifications();
    check(db()->tables['fcc_registration_automation_audits'][1]['email_status'] === 'sent', 'Approval email retries through the durable audit without rechecking FLP.');
    $user = reset_test_db(); $GLOBALS['mail_success'] = false;
    fcc_registration_automation_apply_decisions([$invalid]);
    db()->tables['users'][10]['email'] = 'new@example.test';
    $GLOBALS['mail_success'] = true;
    $mail_count = count($GLOBALS['mail_calls']);
    fcc_registration_automation_retry_notifications();
    check(count($GLOBALS['mail_calls']) === $mail_count && db()->tables['fcc_registration_automation_audits'][1]['status'] === 'superseded' && isset(db()->tables['users'][10]), 'Changed recipient snapshot invalidates a preserved rejection and never deletes the account.');

    $source = file_get_contents(dirname(__DIR__) . '/app/controllers/Register.php');
    check(str_contains($source, "\$_POST['meta']['fcc_access_requested_at'] = get_date()"), 'Only the registration route explicitly tags new verification requests.');
    echo 'FCC registration automation safety checks passed: ' . $GLOBALS['assertion_count'] . PHP_EOL;
    } else {
        /* This opt-in test requires a disposable database called exactly the
           fixed test name. It cannot run against the application's database. */
        require dirname(__DIR__) . '/app/helpers/MysqliDb.php';
        $GLOBALS['real_database'] = new \mysqli(getenv('FCC_REGISTRATION_TEST_DB_HOST'), 'root', 'offline-only');
        database()->query('CREATE DATABASE IF NOT EXISTS fcc_registration_automation_offline_test');
        database()->select_db('fcc_registration_automation_offline_test');
        $GLOBALS['real_db_builder'] = new \Altum\Helpers\MysqliDb(database());
        db()->returnType = 'object';
        foreach(['users', 'links', 'users_vcards', 'fcc_registration_automation_audits'] as $table) database()->query('DROP TABLE IF EXISTS ' . $table);
        database()->query("CREATE TABLE users (user_id INT UNSIGNED PRIMARY KEY, type TINYINT NOT NULL, status TINYINT NOT NULL,
            source VARCHAR(32), name VARCHAR(128), email VARCHAR(255), country VARCHAR(8), datetime DATETIME,
            preferences LONGTEXT, language VARCHAR(64), anti_phishing_code VARCHAR(32)) ENGINE=InnoDB");
        database()->query('CREATE TABLE links (link_id INT PRIMARY KEY, user_id INT, type VARCHAR(32), url VARCHAR(128)) ENGINE=InnoDB');
        database()->query('CREATE TABLE users_vcards (user_id INT PRIMARY KEY, vcard_id INT) ENGINE=InnoDB');
        $user = fresh_user();
        db()->insert('users', (array) $user);
        $legacy = (array) fresh_user(); $legacy['user_id'] = 11; $legacy['preferences'] = json_encode(['meta' => ['foreverId' => '360000000011']]);
        db()->insert('users', $legacy);
        $sponsor = ['user_id' => 20, 'name' => 'Sponsor', 'status' => 1, 'type' => 0,
            'preferences' => json_encode(['meta' => ['foreverId' => '360000000020']])];
        db()->insert('users', $sponsor);
        db()->insert('links', ['link_id' => 42, 'user_id' => 10, 'type' => 'biolink', 'url' => 'ana']);
        db()->insert('users_vcards', ['user_id' => 10, 'vcard_id' => 43]);
        $GLOBALS['mail_calls'] = []; $GLOBALS['mail_success'] = true; $GLOBALS['deleted_users'] = []; $GLOBALS['notification_audits'] = [];
        $pending = fcc_registration_automation_pending();
        check(count($pending['accounts']) === 1 && $pending['accounts'][0]['user_id'] === 10, 'MariaDB pending query excludes old disabled accounts and active sponsors.');
        $decision = ['user_id' => 10, 'fbo_id' => '360000000010', 'registered_at' => $user->datetime, 'result' => 'approve',
            'evidence' => ['source' => 'flp360', 'checked_at' => gmdate('c'), 'exact_fbo_id' => '360000000010',
                'flp_email' => 'ana@example.test', 'sponsor_fbo_id' => '360000000020', 'root_fbo_id' => '360000760944',
                'id_exists' => true, 'in_root_structure' => true, 'authoritative_structure' => true]];
        $dry = fcc_registration_automation_apply_decisions([$decision], true);
        check($dry['results'][0]['status'] === 'approved' && count($GLOBALS['mail_calls']) === 0
            && (int) db()->where('user_id', 10)->getValue('users', 'status') === 0, 'MariaDB dry run validates eligibility without account mutations or mail.');
        $applied = fcc_registration_automation_apply_decisions([$decision]);
        check($applied['results'][0]['status'] === 'approved', 'MariaDB activation, audit insertion and named lock succeed.');
        $stored = db()->where('user_id', 10)->getOne('users');
        $meta = json_decode($stored->preferences)->meta;
        check((int) $stored->status === 1 && (int) $meta->fcc_sponsor_user_id === 20 && !empty($meta->fcc_access_approval_email_sent_at), 'MariaDB activation persists verified sponsor and mail receipt.');
        fcc_registration_automation_apply_decisions([$decision]);
        check(count($GLOBALS['mail_calls']) === 1 && (int) db()->getValue('fcc_registration_automation_audits', 'COUNT(*)') === 1, 'MariaDB repeated decision has one audit and one email.');
        check(count(fcc_registration_automation_pending()['accounts']) === 0, 'Approved account leaves the MariaDB pending queue.');
        $rejected_user = (array) fresh_user(); $rejected_user['user_id'] = 30;
        $rejected_user['preferences'] = json_encode(['meta' => ['foreverId' => '360000000030',
            'fcc_access_requested_at' => $user->datetime, 'fcc_registration_verification_status' => 'pending']]);
        db()->insert('users', $rejected_user);
        $negative = ['user_id' => 30, 'fbo_id' => '360000000030', 'registered_at' => $user->datetime, 'result' => 'invalid_id',
            'evidence' => ['source' => 'flp360', 'checked_at' => gmdate('c'), 'exact_fbo_id' => '360000000030',
                'root_fbo_id' => '360000760944', 'id_exists' => false, 'authoritative_not_found' => true]];
        $GLOBALS['mail_success'] = false;
        fcc_registration_automation_apply_decisions([$negative]);
        check(db()->where('user_id', 30)->has('users') && db()->where('user_id', 30)->getValue('fcc_registration_automation_audits', 'status') === 'rejection_pending', 'MariaDB rejection failure retains account and durable outbox.');
        $GLOBALS['mail_success'] = true;
        fcc_registration_automation_retry_notifications();
        check(!db()->where('user_id', 30)->has('users') && db()->where('user_id', 30)->getValue('fcc_registration_automation_audits', 'status') === 'rejected', 'MariaDB rejection drain holds the account lock and completes deletion after sent mail.');
        $mail_count = count($GLOBALS['mail_calls']);
        fcc_registration_automation_apply_decisions([$negative]);
        check(count($GLOBALS['mail_calls']) === $mail_count, 'MariaDB deleted registration is idempotent on later decisions.');
        $status = fcc_registration_automation_status();
        check(count($status['audit_groups']) === 2 && !str_contains(json_encode($status), 'ana@example.test'), 'MariaDB diagnostics expose counts without recipients.');
        $changed_user = $rejected_user; $changed_user['user_id'] = 31;
        $changed_user['preferences'] = json_encode(['meta' => ['foreverId' => '360000000031',
            'fcc_access_requested_at' => $user->datetime, 'fcc_registration_verification_status' => 'pending']]);
        db()->insert('users', $changed_user);
        $old_audit = (array) db()->where('user_id', 30)->getOne('fcc_registration_automation_audits');
        unset($old_audit['audit_id']);
        $old_audit['user_id'] = 31; $old_audit['status'] = 'manual_review';
        foreach(['360000000032', '360000000033'] as $old_fbo) { $old_audit['fbo_id'] = $old_fbo; db()->insert('fcc_registration_automation_audits', $old_audit); }
        $pending = fcc_registration_automation_pending();
        check(count($pending['accounts']) === 1 && $pending['accounts'][0]['user_id'] === 31, 'Changing a pending Forever ID does not duplicate the queue through older audit rows.');
        database()->query('DROP DATABASE fcc_registration_automation_offline_test');
        echo 'FCC registration MariaDB integration checks passed: ' . $GLOBALS['assertion_count'] . PHP_EOL;
    }
}
/* /Custom code: FC-2026-10-07 */
