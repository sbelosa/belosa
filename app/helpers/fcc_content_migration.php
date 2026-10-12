<?php
defined('ALTUMCODE') || die();

/* Custom code: FC-2026-10-12: audited one click content migration with backup and revert.
 * The definition replaces the retired 120 day plan with the Moj put program in published
 * articles, pages and the owner guide. Every changed value is logged before it is written,
 * and a revert restores only values that nobody edited after the migration.
 * The second definition removes the sentence that FCC is not an official Forever Living
 * Products website from pages and articles, while the statement of independence stays.
 * The third moves cards that still have only the factory blocks and were never edited to
 * the new card in the Aloe style. Blocks stay, the owner gets a notice and can switch back. */

function fcc_cm_migrations(): array {
 return ['mojput'=>'fcc_content_mojput_2026_10_12.json', 'notice'=>'fcc_content_notice_2026_10_12.json', 'aloe'=>''];
}

function fcc_cm_definition(string $migration = 'mojput'): array {
 static $definitions = [];
 $file = fcc_cm_migrations()[$migration] ?? null;
 if($file === null) throw new InvalidArgumentException('Unknown content migration.');
 if($file === '') return ['key'=>'card-aloe-2026-10-12', 'title'=>'Nova kartica Aloe za netaknute kartice'];
 return $definitions[$migration] ??= json_decode((string)file_get_contents(APP_PATH.'config/'.$file), true, 512, JSON_THROW_ON_ERROR);
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
  `group_key` varchar(64) NULL,
  `reverted_by` int unsigned NULL,
  `reverted_at` datetime NULL,
  PRIMARY KEY (`id`),
  KEY `fcc_cm_key` (`migration_key`,`reverted_at`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
 // Tables made before the card switch existed get the card column too (MariaDB syntax, as in production).
 database()->query("ALTER TABLE `fcc_content_migration_log` ADD COLUMN IF NOT EXISTS `group_key` varchar(64) NULL AFTER `applied_at`");
 $done = true;
}

function fcc_cm_table_ready(): bool {
 return !!fcc_partner_one("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fcc_content_migration_log'");
}

function fcc_cm_primary_keys(): array {
 return ['blog_posts'=>'blog_post_id', 'pages'=>'page_id', 'vip_funnel_cards'=>'vip_funnel_card_id', 'links'=>'link_id', 'biolinks_blocks'=>'biolink_block_id'];
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

/** The longest word of a sentence; a text without it cannot contain the sentence. Keeps the plan fast. */
function fcc_cm_notice_anchor(string $text): string {
 static $anchors = [];
 if(isset($anchors[$text])) return $anchors[$text];
 $words = preg_split('~\s+~u', trim($text)) ?: [''];
 usort($words, static fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
 return $anchors[$text] = (string)$words[0];
}

/** The FAQ answer about the official tool goes as a whole, longer sentences keep their first clause,
 * standalone notice sentences go with the space before them. Facts about the official shop stay.
 * Every pattern starts with literal text, so the regular expression engine can skip quickly. */
function fcc_cm_notice_clean(string $value, array $definition): string {
 $original = $value;
 foreach($definition['questions'] as $question) {
  if(!str_contains($value, fcc_cm_notice_anchor($question))) continue;
  $value = preg_replace('~<h3>\s*'.fcc_cm_pattern_body($question).'\s*</h3>\s*<p>[^<]*</p>\s*~u', '', $value) ?? $value;
 }
 foreach($definition['replace'] as $item) {
  if(!str_contains($value, fcc_cm_notice_anchor($item['old']))) continue;
  $value = preg_replace('~(?<![^\s>])'.fcc_cm_pattern_body($item['old']).'~u', addcslashes($item['new'], '\\$'), $value) ?? $value;
 }
 foreach($definition['remove'] as $sentence) {
  if(!str_contains($value, fcc_cm_notice_anchor($sentence))) continue;
  if(!preg_match_all('~(?<![^\s>])'.fcc_cm_pattern_body($sentence).'~u', $value, $found, PREG_OFFSET_CAPTURE)) continue;
  // Work from the end so earlier offsets stay valid. The space before the sentence goes, unless it
  // separates two pieces of text, in which case one run of space stays.
  foreach(array_reverse($found[0]) as [$match, $offset]) {
   $start = $offset; $end = $offset + strlen($match);
   while($start > 0 && ctype_space($value[$start - 1])) $start--;
   while($end < strlen($value) && ctype_space($value[$end])) $end++;
   $before = substr($value, $start, $offset - $start);
   $after = substr($value, $offset + strlen($match), $end - $offset - strlen($match));
   $value = substr($value, 0, $start).($before !== '' && $after !== '' ? $before : '').substr($value, $end);
  }
 }
 // A paragraph that held only the notice disappears instead of staying empty.
 if(preg_match_all('~<p>\s*</p>~', $value) > preg_match_all('~<p>\s*</p>~', $original)) $value = preg_replace('~<p>\s*</p>\s*~', '', $value) ?? $value;
 return $value;
}

function fcc_cm_notice_plan(): array {
 $definition = fcc_cm_definition('notice');
 $changes = [];
 foreach(['pages', 'blog_posts'] as $table) {
  $pk = fcc_cm_primary_keys()[$table];
  foreach(fcc_partner_rows("SELECT `$pk` id,url,language,content FROM `$table`") as $row) {
   $original = (string)$row['content'];
   $value = fcc_cm_notice_clean($original, $definition);
   if($value !== $original) $changes[] = ['table'=>$table, 'id'=>(int)$row['id'], 'column'=>'content', 'old'=>$original, 'new'=>$value, 'label'=>$row['url'].' · '.$row['language']];
  }
 }
 return $changes;
}

/** Block types a card gets from the registration template or from later system updates. */
function fcc_cm_aloe_basic_types(): array {
 return array_merge(FCC_CARD_CORE_BLOCKS, ['link_forever_webshop_reg', 'link_forever_shop', 'custom_html_chatbot', 'vimeo', 'phone_collector', 'email_collector', 'link_homescreen_android', 'link_homescreen_ios', 'share']);
}

/* Custom code: FC-2026-10-12: frozen historical factory generations protect owner edits that
 * the old classic editor did not timestamp. Unknown months and shapes stay with their owner. */
function fcc_cm_aloe_provenance(): array {
 static $definition;
 if($definition !== null) return $definition;
 $path = APP_PATH.'config/fcc_card_template_generations_2026_10_12.json';
 if(!is_file($path)) return $definition = [];
 $decoded = json_decode((string)file_get_contents($path), true);
 return $definition = is_array($decoded) ? $decoded : [];
}

/** UTC database dates must be valid, never interpreted in the server's local timezone. */
function fcc_cm_aloe_timestamp($value): ?int {
 if(!is_string($value)) return null;
 $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('UTC'));
 return $date && $date->format('Y-m-d H:i:s') === $value ? $date->getTimestamp() : null;
}

/** First registration card, exact documented generation and display order, no owner changes.
 * An explicit system batch is the only permitted source of later factory blocks. */
function fcc_cm_aloe_untouched(array $link, array $blocks): bool {
 $definition = fcc_cm_aloe_provenance();
 $created = fcc_cm_aloe_timestamp($link['datetime'] ?? null);
 $registered = fcc_cm_aloe_timestamp($link['user_datetime'] ?? null);
 if(!$blocks || $created === null || $registered === null
  || (int)($link['first_link_id'] ?? 0) !== (int)$link['link_id'] || ($link['last_datetime'] ?? null) !== null) return false;
 $import = $definition['import'] ?? [];
 $isImport = ($link['datetime'] ?? '') === ($import['card_datetime'] ?? null)
  && (string)$link['user_datetime'] >= ($import['user_from'] ?? '') && (string)$link['user_datetime'] < ($import['user_before'] ?? '');
 if(!$isImport && abs($created - $registered) > 600) return false;
 $month = substr((string)$link['user_datetime'], 0, 7);
 $generation = $definition['generations'][$isImport ? ($import['generation'] ?? '') : ($definition['months'][$month] ?? '')] ?? null;
 if(!is_array($generation) || empty($generation['creation_types']) || empty($generation['display_creation_positions'])) return false;
 $template = []; $extensions = [];
 foreach($blocks as $block) {
  $at = fcc_cm_aloe_timestamp($block['datetime'] ?? null);
  if($at === null || ($block['last_datetime'] ?? null) !== null) return false;
  $settings = fcc_card_settings((string)$block['settings']);
  if((int)$block['is_enabled'] === 0 && empty($settings->plan_auto_disabled)) return false;
  $minute = substr((string)$block['datetime'], 0, 16);
  $batch = $definition['extension_batches'][$minute] ?? [];
  $fromImport = $isImport && (string)$block['datetime'] === ($import['card_datetime'] ?? null);
  if((!$isImport && abs($at - $registered) <= 600) || $fromImport) $template[] = $block;
  elseif(in_array($block['type'], $batch['types'] ?? [], true)
   && (string)$block['datetime'] >= ($batch['from'] ?? '') && (string)$block['datetime'] <= ($batch['through'] ?? '')
   && !empty($settings->factory_shop)) $extensions[] = $block;
  else return false;
 }
 usort($template, static fn($a, $b) => (int)$a['biolink_block_id'] <=> (int)$b['biolink_block_id']);
 if(array_column($template, 'type') !== $generation['creation_types']) return false;
 foreach($template as $position => &$block) $block['creation_position'] = $position;
 unset($block);
 usort($template, static fn($a, $b) => [(int)$a['order'], (int)$a['biolink_block_id']] <=> [(int)$b['order'], (int)$b['biolink_block_id']]);
 if(array_column($template, 'creation_position') !== $generation['display_creation_positions']) return false;
 $last = max(array_column($template, 'order'));
 $seen = array_column($template, 'type');
 usort($extensions, static fn($a, $b) => (int)$a['biolink_block_id'] <=> (int)$b['biolink_block_id']);
 foreach($extensions as $block) {
  if((int)$block['order'] <= (int)$last || in_array($block['type'], $seen, true)) return false;
  $seen[] = $block['type'];
  $last = (int)$block['order'];
 }
 return true;
}

/** Only enabled cards of active collaborators with proven historical factory structure. */
function fcc_cm_aloe_plan(): array {
 $links = fcc_partner_rows("SELECT l.link_id,l.user_id,l.url,l.settings,l.datetime,l.last_datetime,u.datetime user_datetime,u.name,(SELECT MIN(x.link_id) FROM links x WHERE x.user_id=l.user_id AND x.type='biolink') first_link_id FROM links l JOIN users u ON u.user_id=l.user_id WHERE l.type='biolink' AND l.is_enabled=1 AND l.last_datetime IS NULL AND u.status=1 AND u.type=0 AND u.plan_id<>'vip_demo'");
 if(!$links) return [];
 $blocks = [];
 foreach(array_chunk(array_column($links, 'link_id'), 500) as $chunk) {
  foreach(fcc_partner_rows('SELECT biolink_block_id,link_id,type,`order`,settings,is_enabled,datetime,last_datetime FROM biolinks_blocks WHERE link_id IN ('.implode(',', array_map('intval', $chunk)).')') as $block) $blocks[(int)$block['link_id']][] = $block;
 }
 $changes = [];
 foreach($links as $link) {
  $settings = fcc_card_settings((string)$link['settings']);
  $own = $blocks[(int)$link['link_id']] ?? [];
  if(($settings->fcc_card ?? '') === 'v2' || !$own) continue;
  if(!fcc_cm_aloe_untouched($link, $own)) continue;
  $label = $link['url'].' · '.$link['name'];
  $settings->fcc_card = 'v2';
  $settings->fcc_card_theme = 'aloe';
  $history = is_array($settings->fcc_card_history ?? null) ? $settings->fcc_card_history : [];
  $history[] = ['from'=>'v1', 'to'=>'v2', 'at'=>gmdate('c'), 'by'=>'fcc'];
  $settings->fcc_card_history = array_slice($history, -20);
  $card = (int)$link['link_id'];
  $changes[] = ['table'=>'links', 'id'=>$card, 'column'=>'settings', 'old'=>(string)$link['settings'], 'new'=>json_encode($settings), 'label'=>$label, 'card'=>$card];
  // Core blocks that a package change switched off come back, as when the owner switches.
  foreach($own as $block) {
   if((int)$block['is_enabled'] !== 0 || !in_array($block['type'], FCC_CARD_CORE_BLOCKS, true)) continue;
   $blockSettings = fcc_card_settings((string)$block['settings']);
   if(empty($blockSettings->plan_auto_disabled)) continue;
   $blockSettings->plan_auto_disabled = false;
   $changes[] = ['table'=>'biolinks_blocks', 'id'=>(int)$block['biolink_block_id'], 'column'=>'settings', 'old'=>(string)$block['settings'], 'new'=>json_encode($blockSettings), 'label'=>$label, 'card'=>$card];
   $changes[] = ['table'=>'biolinks_blocks', 'id'=>(int)$block['biolink_block_id'], 'column'=>'is_enabled', 'old'=>(string)$block['is_enabled'], 'new'=>'1', 'label'=>$label, 'card'=>$card];
  }
 }
 return $changes;
}

/** Card owners hear about the new look once, with the way back; a revert withdraws the notice. */
function fcc_cm_aloe_after(string $action, array $linkIds): void {
 $linkIds = array_values(array_unique(array_map('intval', $linkIds)));
 foreach(array_chunk($linkIds, 500) as $chunk) {
  foreach(fcc_partner_rows('SELECT link_id,user_id,url,domain_id FROM links WHERE link_id IN ('.implode(',', $chunk).')') as $link) {
   try { fcc_card_clear_cache((object)$link); } catch(Throwable $e) { error_log('Card cache: '.get_class($e)); }
   $key = 'card-aloe:'.(int)$link['link_id'].':';
   if($action === 'apply') {
    try { fcc_partner_notify((int)$link['user_id'], $key.gmdate('YmdHis'), 'service', 'Tvoja kartica ima novi izgled', 'Kartica sada koristi novi stil Aloe. Blokovi, adresa, QR kod i preporuka ostali su isti. Ako ti se više sviđa stari izgled, otvori Uredi karticu, zatim Izgled, i odaberi Stara kartica.', 'partner/cards', 14 * 86400); } catch(Throwable $e) { error_log('Card notice: '.get_class($e)); }
   } else {
    fcc_partner_query('UPDATE fcc_partner_notifications SET read_at=COALESCE(read_at,UTC_TIMESTAMP()),expires_at=LEAST(expires_at,UTC_TIMESTAMP()) WHERE user_id=? AND event_key LIKE ?', [(int)$link['user_id'], $key.'%']);
   }
  }
 }
}

function fcc_cm_plan_for(string $migration): array {
 if($migration === 'aloe') return fcc_cm_aloe_plan();
 return $migration === 'notice' ? fcc_cm_notice_plan() : fcc_cm_plan();
}

/** A card and all its blocks, locked for the rest of the transaction. Null when the owner has touched
 * the card: the card or any block carries a change date, or a block was added after $since (UTC). */
function fcc_cm_aloe_lock_card(int $linkId, ?string $since = null): ?array {
 $link = fcc_partner_one("SELECT link_id,user_id,settings,last_datetime,datetime,(SELECT datetime FROM users WHERE users.user_id=links.user_id) user_datetime,(SELECT MIN(x.link_id) FROM links x WHERE x.user_id=links.user_id AND x.type='biolink') first_link_id FROM links WHERE link_id=? FOR UPDATE", [$linkId]);
 if(!$link || $link['last_datetime'] !== null) return null;
 $blocks = [];
 foreach(fcc_partner_rows('SELECT biolink_block_id,link_id,type,`order`,settings,is_enabled,datetime,last_datetime FROM biolinks_blocks WHERE link_id=? FOR UPDATE', [$linkId]) as $block) {
  if($block['last_datetime'] !== null) return null;
  if($since !== null && (string)$block['datetime'] >= $since) return null;
  $blocks[(int)$block['biolink_block_id']] = $block;
 }
 if($since === null && !fcc_cm_aloe_untouched($link, array_values($blocks))) return null;
 return ['link'=>$link, 'blocks'=>$blocks];
}

/** The current value of one logged or planned row inside a locked card, null when the row is gone. */
function fcc_cm_aloe_value(array $card, string $table, int $id, string $column): ?string {
 // The switch only ever writes these three columns; anything else is never touched.
 if(!in_array($table.'.'.$column, ['links.settings', 'biolinks_blocks.settings', 'biolinks_blocks.is_enabled'], true)) return null;
 if($table === 'links') return (int)$card['link']['link_id'] === $id && array_key_exists($column, $card['link']) ? (string)$card['link'][$column] : null;
 return isset($card['blocks'][$id]) && array_key_exists($column, $card['blocks'][$id]) ? (string)$card['blocks'][$id][$column] : null;
}

/** Applies the switch card by card: every planned row of a card must still hold its old value. */
function fcc_cm_aloe_apply_rows(array $plan, string $migrationKey, int $actor): array {
 $applied = 0; $skipped = 0; $done = [];
 $cards = [];
 foreach($plan as $change) $cards[(int)($change['card'] ?? 0)][] = $change;
 foreach($cards as $linkId => $changes) {
  $card = $linkId > 0 ? fcc_cm_aloe_lock_card($linkId) : null;
  $unchanged = $card !== null;
  foreach($changes as $change) if($unchanged && fcc_cm_aloe_value($card, $change['table'], (int)$change['id'], $change['column']) !== $change['old']) $unchanged = false;
  if(!$unchanged) { $skipped += count($changes); continue; }
  foreach($changes as $change) {
   fcc_partner_query('INSERT INTO fcc_content_migration_log (migration_key,table_name,row_id,column_name,old_value,new_value,applied_by,applied_at,group_key) VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP(),?)', [$migrationKey, $change['table'], $change['id'], $change['column'], $change['old'], $change['new'], $actor, 'link:'.$linkId]);
   fcc_partner_query("UPDATE `{$change['table']}` SET `{$change['column']}`=? WHERE `".fcc_cm_primary_keys()[$change['table']]."`=?", [$change['new'], $change['id']]);
   $applied++; $done[] = $change;
  }
 }
 return ['applied'=>$applied, 'skipped'=>$skipped, 'done'=>$done];
}

/** Reverts a card only as a whole: the card and every block must be exactly as the switch left them and
 * nothing may have been added since. Otherwise the owner has worked with the new card and it stays. */
function fcc_cm_aloe_revert_rows(array $logs, int $actor): array {
 $reverted = 0; $kept = 0; $undone = [];
 $blockCards = [];
 $unknown = array_values(array_unique(array_map(static fn($log) => (int)$log['row_id'], array_filter($logs, static fn($log) => empty($log['group_key']) && $log['table_name'] === 'biolinks_blocks'))));
 foreach(array_chunk($unknown, 500) as $chunk) foreach(fcc_partner_rows('SELECT biolink_block_id,link_id FROM biolinks_blocks WHERE biolink_block_id IN ('.implode(',', $chunk).')') as $row) $blockCards[(int)$row['biolink_block_id']] = (int)$row['link_id'];
 $cards = [];
 foreach($logs as $log) {
  if(!empty($log['group_key']) && preg_match('/^link:(\d+)$/D', (string)$log['group_key'], $m)) $linkId = (int)$m[1];
  elseif($log['table_name'] === 'links') $linkId = (int)$log['row_id'];
  else $linkId = $blockCards[(int)$log['row_id']] ?? 0;
  $cards[$linkId][] = $log;
 }
 foreach($cards as $linkId => $cardLogs) {
  $since = min(array_map(static fn($log) => (string)$log['applied_at'], $cardLogs));
  $card = $linkId > 0 ? fcc_cm_aloe_lock_card($linkId, $since) : null;
  $untouched = $card !== null;
  foreach($cardLogs as $log) if($untouched && fcc_cm_aloe_value($card, $log['table_name'], (int)$log['row_id'], $log['column_name']) !== (string)$log['new_value']) $untouched = false;
  if(!$untouched) { $kept += count($cardLogs); continue; }
  foreach($cardLogs as $log) {
   fcc_partner_query("UPDATE `{$log['table_name']}` SET `{$log['column_name']}`=? WHERE `".fcc_cm_primary_keys()[$log['table_name']]."`=?", [(string)$log['old_value'], (int)$log['row_id']]);
   fcc_partner_query('UPDATE fcc_content_migration_log SET reverted_by=?,reverted_at=UTC_TIMESTAMP() WHERE id=?', [$actor, (int)$log['id']]);
   $reverted++; $undone[] = $log;
  }
 }
 return ['reverted'=>$reverted, 'kept'=>$kept, 'undone'=>$undone];
}

/** Link ids behind applied changes or reverted log rows. */
function fcc_cm_touched_links(array $rows): array {
 $links = []; $blocks = [];
 foreach($rows as $row) {
  $table = (string)($row['table_name'] ?? $row['table'] ?? ''); $id = (int)(isset($row['table_name']) ? $row['row_id'] : ($row['id'] ?? 0));
  if($table === 'links') $links[] = $id; elseif($table === 'biolinks_blocks') $blocks[] = $id;
 }
 foreach(array_chunk(array_values(array_unique($blocks)), 500) as $chunk) foreach(fcc_partner_rows('SELECT link_id FROM biolinks_blocks WHERE biolink_block_id IN ('.implode(',', $chunk).')') as $row) $links[] = (int)$row['link_id'];
 return $links;
}

/** The administration shows a status that is at most an hour old; apply and revert always use a fresh plan. */
function fcc_cm_status(string $migration = 'mojput'): array {
 $item = null;
 try { $item = cache()->getItem('fcc_cm_status_'.$migration); if($item->isHit()) return $item->get(); } catch(Throwable $e) { $item = null; }
 $status = fcc_cm_status_fresh($migration);
 try { if($item) cache()->save($item->set($status)->expiresAfter(3600)->addTag('fcc_cm')); } catch(Throwable $e) {}
 return $status;
}

function fcc_cm_status_forget(): void {
 try { cache()->deleteItemsByTag('fcc_cm'); } catch(Throwable $e) {}
}

function fcc_cm_status_fresh(string $migration): array {
 $definition = fcc_cm_definition($migration);
 $plan = fcc_cm_plan_for($migration);
 $tables = [];
 foreach($plan as $change) $tables[$change['table']] = ($tables[$change['table']] ?? 0) + 1;
 $applied = fcc_cm_table_ready() ? (int)(fcc_partner_one('SELECT COUNT(*) n FROM fcc_content_migration_log WHERE migration_key=? AND reverted_at IS NULL', [$definition['key']])['n'] ?? 0) : 0;
 $last = fcc_cm_table_ready() ? fcc_partner_one('SELECT MAX(applied_at) applied_at FROM fcc_content_migration_log WHERE migration_key=? AND reverted_at IS NULL', [$definition['key']]) : null;
 return ['migration'=>$migration, 'key'=>$definition['key'], 'title'=>$definition['title'], 'pending'=>count($plan), 'pending_tables'=>$tables, 'pending_labels'=>array_values(array_unique(array_column($plan, 'label'))), 'applied'=>$applied, 'applied_at'=>$last['applied_at'] ?? null];
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

function fcc_cm_apply(int $actor, string $migration = 'mojput', ?callable $review_plan = null): array {
 fcc_cm_require_admin($actor);
 fcc_cm_ensure_table();
 $definition = fcc_cm_definition($migration);
 $keys = fcc_cm_primary_keys();
 $applied = 0; $skipped = 0; $done = [];
 $plan = fcc_cm_plan_for($migration);
 // Custom code: FC-2026-10-12: a CLI release may validate the exact internal plan before any content write.
 if($review_plan !== null) $review_plan($plan);
 // /Custom code: FC-2026-10-12
 database()->begin_transaction();
 try {
  if($migration === 'aloe') {
   ['applied'=>$applied, 'skipped'=>$skipped, 'done'=>$done] = fcc_cm_aloe_apply_rows($plan, $definition['key'], $actor);
   $plan = [];
  }
  foreach($plan as $change) {
   $table = $change['table']; $column = $change['column'];
   if(!isset($keys[$table]) || !preg_match('/^[a-z_]+$/D', $column)) { $skipped++; continue; }
   $current = fcc_partner_one("SELECT `$column` v FROM `$table` WHERE `{$keys[$table]}`=? FOR UPDATE", [$change['id']]);
   if(!$current || (string)$current['v'] !== $change['old']) { $skipped++; continue; }
   fcc_partner_query('INSERT INTO fcc_content_migration_log (migration_key,table_name,row_id,column_name,old_value,new_value,applied_by,applied_at) VALUES (?,?,?,?,?,?,?,UTC_TIMESTAMP())', [$definition['key'], $table, $change['id'], $column, $change['old'], $change['new'], $actor]);
   fcc_partner_query("UPDATE `$table` SET `$column`=? WHERE `{$keys[$table]}`=?", [$change['new'], $change['id']]);
   $applied++; $done[] = $change;
  }
  database()->commit();
 } catch(Throwable $e) {
  database()->rollback();
  throw $e;
 }
 if($migration === 'aloe') fcc_cm_aloe_after('apply', array_column(array_filter($done, static fn($c) => $c['table'] === 'links'), 'id'));
 else fcc_cm_clear_cache();
 fcc_cm_status_forget();
 return ['applied'=>$applied, 'skipped'=>$skipped];
}

function fcc_cm_revert(int $actor, string $migration = 'mojput'): array {
 fcc_cm_require_admin($actor);
 fcc_cm_ensure_table();
 $definition = fcc_cm_definition($migration);
 $keys = fcc_cm_primary_keys();
 $reverted = 0; $kept = 0; $undone = [];
 database()->begin_transaction();
 try {
  $logs = fcc_partner_rows('SELECT * FROM fcc_content_migration_log WHERE migration_key=? AND reverted_at IS NULL ORDER BY id DESC', [$definition['key']]);
  if($migration === 'aloe') {
   ['reverted'=>$reverted, 'kept'=>$kept, 'undone'=>$undone] = fcc_cm_aloe_revert_rows($logs, $actor);
   $logs = [];
  }
  foreach($logs as $log) {
   $table = $log['table_name']; $column = $log['column_name'];
   if(!isset($keys[$table]) || !preg_match('/^[a-z_]+$/D', $column)) { $kept++; continue; }
   $current = fcc_partner_one("SELECT `$column` v FROM `$table` WHERE `{$keys[$table]}`=? FOR UPDATE", [(int)$log['row_id']]);
   // A value edited after the migration stays as it is.
   if(!$current || (string)$current['v'] !== (string)$log['new_value']) { $kept++; continue; }
   fcc_partner_query("UPDATE `$table` SET `$column`=? WHERE `{$keys[$table]}`=?", [(string)$log['old_value'], (int)$log['row_id']]);
   fcc_partner_query('UPDATE fcc_content_migration_log SET reverted_by=?,reverted_at=UTC_TIMESTAMP() WHERE id=?', [$actor, (int)$log['id']]);
   $reverted++; $undone[] = $log;
  }
  database()->commit();
 } catch(Throwable $e) {
  database()->rollback();
  throw $e;
 }
 if($migration === 'aloe') fcc_cm_aloe_after('revert', fcc_cm_touched_links($undone));
 else fcc_cm_clear_cache();
 fcc_cm_status_forget();
 return ['reverted'=>$reverted, 'kept'=>$kept];
}
/* /Custom code: FC-2026-10-12 */
