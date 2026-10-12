<?php
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-12: audited one click content migration with backup and revert.
 * The definition replaces the retired 120 day plan with the Moj put program in published
 * articles, pages and the owner guide. Every changed value is logged before it is written,
 * and a revert restores only values that nobody edited after the migration. */

function fcc_cm_definition(): array {
 static $definition;
 return $definition ??= json_decode((string)file_get_contents(APP_PATH.'config/fcc_content_mojput_2026_10_12.json'), true, 512, JSON_THROW_ON_ERROR);
}

function fcc_cm_ensure_table(): void {
 static $done = false;
 if($done) return;
 database()->query("CREATE TABLE IF NOT EXISTS `fcc_content_migration_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `migration_key` varchar(64) NOT NULL,
  `table_name` varchar(64) NOT NULL,
  `row_id` bigint unsigned NOT NULL,
  `column_name` varchar(64) NOT NULL,
  `old_value` longtext NULL,
  `new_value` longtext NULL,
  `applied_by` int unsigned NOT NULL,
  `applied_at` datetime NOT NULL,
  `reverted_by` int unsigned NULL,
  `reverted_at` datetime NULL,
  PRIMARY KEY (`id`),
  KEY `fcc_cm_key` (`migration_key`,`reverted_at`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 $done = true;
}

function fcc_cm_table_ready(): bool {
 return !!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_content_migration_log'");
}

function fcc_cm_primary_keys(): array {
 return ['blog_posts'=>'blog_post_id', 'pages'=>'page_id', 'vip_funnel_cards'=>'vip_funnel_card_id'];
}

/** Whitespace in the stored text may differ from the definition, so any run of whitespace matches. */
function fcc_cm_pattern_body(string $old): string {
 return preg_replace('~\s+~u', '\\s+', preg_quote(trim($old), '~'));
}

/** Content definitions are whole text nodes; keywords are whole comma separated items. */
function fcc_cm_replace(string $value, string $old, string $new, string $column): string {
 $body = fcc_cm_pattern_body($old);
 $pattern = $column === 'keywords'
  ? '~(^|,)(\s*)'.$body.'(\s*)(?=,|$)~u'
  : '~(?<=>)(\s*)'.$body.'(\s*)(?=<)~u';
 $result = preg_replace_callback($pattern, static function(array $m) use ($new, $column): string {
  return $column === 'keywords' ? $m[1].$m[2].$new.$m[3] : $m[1].$new.$m[2];
 }, $value);
 return is_string($result) ? $result : $value;
}

/** Builds the full list of pending changes against the current database. Nothing is written. */
function fcc_cm_plan(): array {
 $definition = fcc_cm_definition();
 $changes = [];
 $set = static function(string $table, int $id, string $column, string $old, string $new, string $label) use (&$changes): void {
  $key = $table.':'.$id.':'.$column;
  if(!isset($changes[$key])) $changes[$key] = ['table'=>$table, 'id'=>$id, 'column'=>$column, 'old'=>$old, 'new'=>$new, 'label'=>$label];
  else $changes[$key]['new'] = $new;
 };
 $planned = static function(string $table, int $id, string $column, string $current) use (&$changes): string {
  return $changes[$table.':'.$id.':'.$column]['new'] ?? $current;
 };

 // 1. The 120 day plan article becomes the Moj put article in every language.
 $article = $definition['article'];
 foreach($article['languages'] as $language => $copy) {
  $row = fcc_partner_one('SELECT blog_post_id,url,title,description,keywords,content FROM blog_posts WHERE url=? AND language=? LIMIT 1', [$article['old_url'], $language]);
  if(!$row || fcc_partner_one('SELECT blog_post_id FROM blog_posts WHERE url=? AND language=? LIMIT 1', [$article['new_url'], $language])) continue;
  foreach(['url'=>$article['new_url'], 'title'=>$copy['title'], 'description'=>$copy['description'], 'keywords'=>$copy['keywords'], 'content'=>$copy['content']] as $column => $value) {
   if((string)$row[$column] !== $value) $set('blog_posts', (int)$row['blog_post_id'], $column, (string)$row[$column], $value, $article['old_url'].' · '.$language);
  }
 }

 // 2. Short mentions in related articles and the public How it works page.
 $grouped = [];
 foreach($definition['replacements'] as $item) {
  $table = $item['url'] === 'how-it-works' ? 'pages' : 'blog_posts';
  $grouped[$table][$item['url']][$item['language']][$item['column']][] = $item;
 }
 foreach($grouped as $table => $urls) {
  $pk = fcc_cm_primary_keys()[$table];
  foreach($urls as $url => $languages) foreach($languages as $language => $columns) {
   $row = fcc_partner_one("SELECT `$pk` id,`content`,`keywords` FROM `$table` WHERE url=? AND language=? LIMIT 1", [$url, $language]);
   if(!$row) continue;
   foreach($columns as $column => $items) {
    if(!in_array($column, ['content', 'keywords'], true)) continue;
    // Longer phrases first, so a short title never rewrites part of a longer sentence.
    usort($items, static fn($a, $b) => mb_strlen($b['old']) <=> mb_strlen($a['old']));
    $original = (string)$row[$column];
    $value = $planned($table, (int)$row['id'], $column, $original);
    foreach($items as $item) $value = fcc_cm_replace($value, $item['old'], $item['new'], $column);
    if($value !== $original) $set($table, (int)$row['id'], $column, $original, $value, $url.' · '.$language);
   }
  }
 }

 // 3. Links to the old article address anywhere in articles and pages.
 foreach(['blog_posts', 'pages'] as $table) {
  $pk = fcc_cm_primary_keys()[$table];
  foreach($definition['links'] as $link) {
   foreach(fcc_partner_rows("SELECT `$pk` id,url,language,content FROM `$table` WHERE content LIKE ?", ['%'.$link['old'].'%']) as $row) {
    $original = (string)$row['content'];
    $value = preg_replace('~'.preg_quote($link['old'], '~').'(?![a-z0-9-])~', $link['new'], $planned($table, (int)$row['id'], 'content', $original));
    if(is_string($value) && $value !== $original) $set($table, (int)$row['id'], 'content', $original, $value, $row['url'].' · '.$row['language']);
   }
  }
 }

 // 4. The owner guide stores its blocks as JSON; only exact text values change.
 $funnel = $definition['funnel'];
 $guide = fcc_partner_one('SELECT vip_funnel_id FROM vip_funnels WHERE slug=? AND user_id=? LIMIT 1', [$funnel['slug'], (int)$funnel['user_id']]);
 if($guide) {
  foreach(fcc_partner_rows('SELECT vip_funnel_card_id id,title,settings FROM vip_funnel_cards WHERE vip_funnel_id=?', [(int)$guide['vip_funnel_id']]) as $row) {
   $original = (string)$row['settings'];
   $value = $original;
   foreach($funnel['pairs'] as $pair) {
    $value = str_replace($pair['old'], $pair['new'], $value);
    $escapedOld = substr(json_encode($pair['old']), 1, -1);
    if($escapedOld !== $pair['old']) $value = str_replace($escapedOld, substr(json_encode($pair['new']), 1, -1), $value);
   }
   if($value === $original) continue;
   json_decode($value);
   if(json_last_error() === JSON_ERROR_NONE) $set('vip_funnel_cards', (int)$row['id'], 'settings', $original, $value, $funnel['slug'].' · '.$row['title']);
  }
 }

 return array_values($changes);
}

function fcc_cm_status(): array {
 $definition = fcc_cm_definition();
 $plan = fcc_cm_plan();
 $tables = [];
 foreach($plan as $change) $tables[$change['table']] = ($tables[$change['table']] ?? 0) + 1;
 $applied = fcc_cm_table_ready() ? (int)(fcc_partner_one('SELECT COUNT(*) n FROM fcc_content_migration_log WHERE migration_key=? AND reverted_at IS NULL', [$definition['key']])['n'] ?? 0) : 0;
 $last = fcc_cm_table_ready() ? fcc_partner_one('SELECT MAX(applied_at) applied_at FROM fcc_content_migration_log WHERE migration_key=? AND reverted_at IS NULL', [$definition['key']]) : null;
 return ['key'=>$definition['key'], 'title'=>$definition['title'], 'pending'=>count($plan), 'pending_tables'=>$tables, 'pending_labels'=>array_values(array_unique(array_column($plan, 'label'))), 'applied'=>$applied, 'applied_at'=>$last['applied_at'] ?? null];
}

function fcc_cm_require_admin(int $actor): void {
 $user = fcc_partner_one('SELECT type,status FROM users WHERE user_id=?', [$actor]);
 if(!$user || (int)$user['type'] !== 1 || (int)$user['status'] !== 1) throw new InvalidArgumentException(fcc_t('Ovu radnju može izvršiti samo administrator.'));
}

function fcc_cm_clear_cache(): void {
 try {
  cache()->deleteItemsByTag('blog_posts');
  cache()->deleteItemsByTag('pages');
  cache()->deleteItems(['pages_hidden', 'pages_top', 'pages_bottom']);
  cache()->deleteItemsByTag('user_id='.(int)fcc_cm_definition()['funnel']['user_id']);
 } catch(Throwable $e) {
  error_log('Content migration cache: '.get_class($e));
 }
}

function fcc_cm_apply(int $actor): array {
 fcc_cm_require_admin($actor);
 fcc_cm_ensure_table();
 $definition = fcc_cm_definition();
 $keys = fcc_cm_primary_keys();
 $applied = 0; $skipped = 0;
 $plan = fcc_cm_plan();
 database()->begin_transaction();
 try {
  foreach($plan as $change) {
   $table = $change['table']; $column = $change['column'];
   if(!isset($keys[$table]) || !preg_match('/^[a-z_]+$/D', $column)) { $skipped++; continue; }
   $current = fcc_partner_one("SELECT `$column` v FROM `$table` WHERE `{$keys[$table]}`=? FOR UPDATE", [$change['id']]);
   if(!$current || (string)$current['v'] !== $change['old']) { $skipped++; continue; }
   fcc_partner_query('INSERT INTO fcc_content_migration_log (migration_key,table_name,row_id,column_name,old_value,new_value,applied_by,applied_at) VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP())', [$definition['key'], $table, $change['id'], $column, $change['old'], $change['new'], $actor]);
   fcc_partner_query("UPDATE `$table` SET `$column`=? WHERE `{$keys[$table]}`=?", [$change['new'], $change['id']]);
   $applied++;
  }
  database()->commit();
 } catch(Throwable $e) {
  database()->rollback();
  throw $e;
 }
 fcc_cm_clear_cache();
 return ['applied'=>$applied, 'skipped'=>$skipped];
}

function fcc_cm_revert(int $actor): array {
 fcc_cm_require_admin($actor);
 fcc_cm_ensure_table();
 $definition = fcc_cm_definition();
 $keys = fcc_cm_primary_keys();
 $reverted = 0; $kept = 0;
 database()->begin_transaction();
 try {
  foreach(fcc_partner_rows('SELECT * FROM fcc_content_migration_log WHERE migration_key=? AND reverted_at IS NULL ORDER BY id DESC', [$definition['key']]) as $log) {
   $table = $log['table_name']; $column = $log['column_name'];
   if(!isset($keys[$table]) || !preg_match('/^[a-z_]+$/D', $column)) { $kept++; continue; }
   $current = fcc_partner_one("SELECT `$column` v FROM `$table` WHERE `{$keys[$table]}`=? FOR UPDATE", [(int)$log['row_id']]);
   // A value edited after the migration stays as it is.
   if(!$current || (string)$current['v'] !== (string)$log['new_value']) { $kept++; continue; }
   fcc_partner_query("UPDATE `$table` SET `$column`=? WHERE `{$keys[$table]}`=?", [(string)$log['old_value'], (int)$log['row_id']]);
   fcc_partner_query('UPDATE fcc_content_migration_log SET reverted_by=?,reverted_at=UTC_TIMESTAMP() WHERE id=?', [$actor, (int)$log['id']]);
   $reverted++;
  }
  database()->commit();
 } catch(Throwable $e) {
  database()->rollback();
  throw $e;
 }
 fcc_cm_clear_cache();
 return ['reverted'=>$reverted, 'kept'=>$kept];
}
/* /Custom code: FC-2026-10-12 */
