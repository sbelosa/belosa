<?php
/* Custom code: FC-2026-10-12 */
/** Isolated regression test for real helper sources at argv[1].
 * No application bootstrap, real SQL, HTTP request, credential or source mutation.
 * Database state is an in memory double. Only disposable private tmp files are used.
 * is_uploaded_file/move_uploaded_file are replaced solely to simulate a valid HTTP upload.
 */
namespace Altum {
    class Plugin { public static function is_active($name): bool { return false; } }
    class Teams { public static function is_delegated(): bool { return false; } public static function has_access($permission): bool { return true; } }
}
namespace FCCCardTransactionTest {
    use InvalidArgumentException;
    use RuntimeException;
    if(PHP_SAPI !== 'cli') { fwrite(STDERR, "Usage: php editor_transaction_test.php SOURCE_ROOT\n"); exit(2); }
    $source = rtrim($argv[1] ?? dirname(__DIR__), '/');
    $temp = sys_get_temp_dir() . '/fcc-card-tx-test-' . bin2hex(random_bytes(8)) . '/';
    mkdir($temp . 'avatars/', 0700, true);
    mkdir($temp . 'block_images/', 0700, true);
    define('ALTUMCODE', 66);
    define('APP_PATH', $source . '/app/');
    define('UPLOADS_PATH', $temp);
    define('UPLOADS_URL_PATH', 'uploads/');
    define('SITE_URL', 'https://forevercard.club/');
    class MemoryDb {
        public array $tables = [];
        public ?array $snapshot = null;
        public string $failure = '';
        public int $starts = 0;
        public int $commits = 0;
        public int $rollbacks = 0;
        public array $filters = [];
        public function where($field, $value, $operator=null): self { $this->filters[trim($field, '`')] = $value; return $this; }
        public function orderBy($field, $order): self { return $this; }
        public function get($table, $limit=null, $columns=null): array {
            $rows = [];
            foreach($this->tables[$table] ?? [] as $row) {
                foreach($this->filters as $field=>$value) if(($row[$field] ?? null) != $value) continue 2;
                $rows[] = (object)$row;
            }
            $this->filters = [];
            return $rows;
        }
        public function getOne($table, $columns=null) { return $this->get($table)[0] ?? null; }
        public function getValue($table, $field) { $rows=$this->get($table); return $field === 'COUNT(*)' ? count($rows) : ($rows[0]->$field ?? null); }
        public function update($table, $values): bool {
            $filters = $this->filters;
            $this->filters = [];
            if($table === 'biolinks_blocks' && ($filters['biolink_block_id'] ?? 0) === 202 && $this->failure === 'heading_false') return false;
            if($table === 'links' && isset($values['last_datetime']) && $this->failure === 'timestamp_exception') throw new RuntimeException('Injected timestamp write exception');
            if($table === 'links' && isset($values['last_datetime']) && $this->failure === 'timestamp_false') return false;
            foreach($this->tables[$table] ?? [] as $index=>$row) {
                foreach($filters as $field=>$value) if(($row[$field] ?? null) != $value) continue 2;
                $this->tables[$table][$index] = array_replace($row, $values);
            }
            return true;
        }
        public function insert($table, $values): int { $this->tables[$table][]=$values; return 999; }
        public function startTransaction(): bool { if($this->snapshot !== null) throw new RuntimeException('Nested transaction'); $this->snapshot=$this->tables; ++$this->starts; return true; }
        public function begin_transaction($flags=0): bool { return $this->startTransaction(); }
        public function commit(): bool { $this->snapshot=null; ++$this->commits; return true; }
        public function rollback(): bool { if($this->snapshot !== null) $this->tables=$this->snapshot; $this->snapshot=null; ++$this->rollbacks; return true; }
        public function autocommit($enabled): bool { return true; }
    }
    class NullCache { public function deleteItem($key): void {} public function deleteItemsByTag($key): void {} }
    $memory = new MemoryDb();
    function db(): MemoryDb { return $GLOBALS['FCC_TX_DB']; }
    function database(): MemoryDb { return db(); }
    function cache(): NullCache { return new NullCache(); }
    function settings(): object { return (object)['links'=>(object)['image_size_limit'=>2,'avatar_size_limit'=>2,'blacklisted_domains'=>[],'google_safe_browsing_is_enabled'=>false]]; }
    function fcc_t($text, $replace=[], $language=null): string { return strtr($text, $replace); }
    function input_clean($text, $max=null): string { $text=htmlspecialchars(strip_tags(trim((string)$text)), ENT_QUOTES, 'UTF-8'); return $max ? mb_substr($text, 0, $max) : $text; }
    function get_url($text): string { return filter_var($text, FILTER_VALIDATE_URL) ? $text : ''; }
    function get_domain_from_url($text): string { return (string)parse_url($text, PHP_URL_HOST); }
    function is_uploaded_file($path): bool { return isset($GLOBALS['FCC_TX_UPLOADS'][$path]); }
    function move_uploaded_file($from, $to): bool { return is_uploaded_file($from) && rename($from, $to); }
    $GLOBALS['FCC_TX_DB'] = $memory;
    foreach(['fcc_card.php','fcc_card_editor.php'] as $filename) {
        $code = file_get_contents($source . '/app/helpers/' . $filename);
        if(!str_starts_with($code, '<?php')) throw new RuntimeException('Unexpected helper source');
        eval("namespace FCCCardTransactionTest; use \\InvalidArgumentException; use \\RuntimeException;\n" . substr($code, 5));
    }
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jA1sAAAAASUVORK5CYII=');
    $failures = 0;
    $assert = static function(string $case, array $evidence, bool $passed) use (&$failures): void {
        if(!$passed) ++$failures;
        echo json_encode(['case'=>$case, 'result'=>$passed ? 'PASS' : 'FAIL'] + $evidence, JSON_UNESCAPED_SLASHES) . "\n";
    };
    $reset = static function() use ($memory, $temp, $png): array {
        foreach(['avatars','block_images'] as $folder) foreach(glob($temp . $folder . '/*') ?: [] as $file) unlink($file);
        foreach(glob($temp . '*') ?: [] as $file) if(is_file($file)) unlink($file);
        $memory->tables = [
            'links'=>[
                ['link_id'=>100,'user_id'=>10,'type'=>'biolink','is_enabled'=>1,'domain_id'=>0,'url'=>'card-fixture','settings'=>json_encode(['language_code'=>'hr','fcc_card'=>'v2'])],
                ['link_id'=>101,'user_id'=>10,'type'=>'vcard','is_enabled'=>1,'domain_id'=>0,'url'=>'contact-fixture','settings'=>'{}'],
            ],
            'biolinks_blocks'=>[
                ['biolink_block_id'=>201,'link_id'=>100,'user_id'=>10,'type'=>'avatar','order'=>0,'is_enabled'=>1,'location_url'=>null,'settings'=>json_encode(['image'=>'old.png','use_default_avatar'=>false])],
                ['biolink_block_id'=>202,'link_id'=>100,'user_id'=>10,'type'=>'heading','order'=>1,'is_enabled'=>1,'location_url'=>null,'settings'=>json_encode(['text'=>'Original fixture'])],
                ['biolink_block_id'=>203,'link_id'=>100,'user_id'=>10,'type'=>'paragraph','order'=>2,'is_enabled'=>1,'location_url'=>null,'settings'=>json_encode(['text'=>'<p>Fixture intro</p>'])],
                ['biolink_block_id'=>204,'link_id'=>100,'user_id'=>10,'type'=>'image','order'=>3,'is_enabled'=>1,'location_url'=>null,'settings'=>json_encode(['image'=>'old.png'])],
            ],
            'domains'=>[],
        ];
        $memory->failure=''; $memory->filters=[]; $memory->snapshot=null;
        $memory->starts=0; $memory->commits=0; $memory->rollbacks=0;
        file_put_contents($temp . 'avatars/old.png', $png);
        file_put_contents($temp . 'block_images/old.png', $png);
        file_put_contents($temp . 'incoming.png', $png);
        $GLOBALS['FCC_TX_UPLOADS'] = [$temp . 'incoming.png'=>true];
        $_FILES = ['photo'=>['name'=>'replacement.png','error'=>UPLOAD_ERR_OK,'size'=>strlen($png),'tmp_name'=>$temp . 'incoming.png']];
        fcc_card_editor_files('reset');
        return $memory->tables;
    };
    $user = (object)['user_id'=>10,'language'=>'hr','plan_settings'=>(object)['enabled_biolink_blocks'=>(object)['image'=>true,'avatar'=>true],'biolink_blocks_limit'=>-1]];
    try {
        foreach(['heading_false','timestamp_exception','timestamp_false'] as $failure) {
            $before = $reset();
            $memory->failure = $failure;
            $profile = $failure === 'heading_false';
            $input = $profile ? ['action'=>'profile','name'=>'Updated fixture','role'=>'Fixture role','about'=>'Fixture intro'] : ['action'=>'save','block_id'=>204,'url'=>'https://example.test/photo','alt'=>'Fixture'];
            $rejected = false;
            try { fcc_card_editor_mutate($user, 100, $input); } catch(\Throwable $error) { $rejected=true; }
            $folder = $profile ? 'avatars/' : 'block_images/';
            $same = $memory->tables === $before;
            $old = is_file($temp . $folder . 'old.png');
            $new = count(glob($temp . $folder . '/*') ?: []) === 1;
            $assert($failure . '_after_photo_write', ['rejected'=>$rejected,'database_restored'=>$same,'old_media_exists'=>$old,'no_orphan_upload'=>$new,'transaction_closed'=>$memory->snapshot === null], $rejected && $same && $old && $new && $memory->snapshot === null);
        }
        foreach(['profile','save'] as $action) {
            $reset();
            $input = $action === 'profile' ? ['action'=>'profile','name'=>'Updated fixture','role'=>'Fixture role','about'=>'Fixture intro'] : ['action'=>'save','block_id'=>204,'url'=>'https://example.test/photo','alt'=>'Fixture'];
            $saved = true;
            try { fcc_card_editor_mutate($user, 100, $input); } catch(\Throwable $error) { $saved=false; }
            $folder = $action === 'profile' ? 'avatars/' : 'block_images/';
            $row = $memory->tables['biolinks_blocks'][$action === 'profile' ? 0 : 3];
            $image = json_decode($row['settings'])->image ?? '';
            $oldGone = !is_file($temp . $folder . 'old.png');
            $newExists = $image !== '' && $image !== 'old.png' && is_file($temp . $folder . $image);
            $assert($action . '_success', ['saved'=>$saved,'old_media_removed'=>$oldGone,'saved_media_exists'=>$newExists,'transaction_committed'=>$memory->commits > 0,'transaction_closed'=>$memory->snapshot === null], $saved && $oldGone && $newExists && $memory->commits > 0 && $memory->snapshot === null);
        }
        $before = $reset();
        $rejected = false;
        try { fcc_card_editor_mutate($user, 100, ['action'=>'save','block_id'=>204,'url'=>'https://invalid','alt'=>'Fixture']); } catch(InvalidArgumentException $error) { $rejected=true; }
        $assert('invalid_url_with_replacement', ['rejected'=>$rejected,'database_unchanged'=>$memory->tables === $before,'old_media_exists'=>is_file($temp . 'block_images/old.png'),'upload_not_moved'=>is_file($temp . 'incoming.png')], $rejected && $memory->tables === $before && is_file($temp . 'block_images/old.png') && is_file($temp . 'incoming.png'));
        $reset();
        $canonical = SITE_URL . 'contact-fixture';
        foreach([
            'external_same_slug'=>'https://external.example/contact-fixture',
            'unsafe_scheme'=>'javascript://forevercard.club/contact-fixture?%0Aalert(1)',
            'ftp_scheme'=>'ftp://forevercard.club/contact-fixture',
            'wrong_port'=>'https://forevercard.club:4444/contact-fixture',
            'canonical'=>$canonical,
        ] as $label=>$input) {
            $row=(object)['type'=>'link_save_contact','location_url'=>$input];
            $visible=fcc_card_fix_save_contact($row, 10);
            $assert('vcard_' . $label, ['visible'=>$visible,'canonical'=>$row->location_url === $canonical], $visible && $row->location_url === $canonical);
        }
        echo json_encode(['status'=>$failures ? 'FAIL' : 'PASS','failures'=>$failures,'real_database_used'=>false,'app_bootstrap_used'=>false,'source_sha256'=>['fcc_card.php'=>hash_file('sha256', $source . '/app/helpers/fcc_card.php'),'fcc_card_editor.php'=>hash_file('sha256', $source . '/app/helpers/fcc_card_editor.php')]], JSON_UNESCAPED_SLASHES) . "\n";
    } finally {
        foreach(['avatars','block_images'] as $folder) { foreach(glob($temp . $folder . '/*') ?: [] as $file) unlink($file); rmdir($temp . $folder); }
        foreach(glob($temp . '*') ?: [] as $file) if(is_file($file)) unlink($file);
        rmdir($temp);
    }
    exit($failures ? 1 : 0);
}
