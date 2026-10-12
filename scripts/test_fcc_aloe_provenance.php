<?php
/* Custom code: FC-2026-10-12: independent historical factory provenance regression.
 * Run: php aloe_provenance_test.php SOURCE_ROOT.
 * Loads actual helpers and frozen JSON only. Database adapters below use synthetic memory.
 * No app bootstrap, real database, cache, network, user files or production operations. */
if(PHP_SAPI !== 'cli') exit(1);
$source = realpath($argv[1] ?? dirname(__DIR__));
if(!$source || !is_file($source.'/app/helpers/fcc_content_migration.php')) throw new RuntimeException('SOURCE_ROOT missing');
define('ALTUMCODE', 66);
define('APP_PATH', $source.'/app/');
require APP_PATH.'helpers/fcc_card.php';
require APP_PATH.'helpers/fcc_content_migration.php';

/* Expected shapes are independent of the production JSON and helper implementation. */
$shapes = [
 'G1' => [['avatar','heading','socials','link','link','email_collector','phone_collector','vimeo'], [0,1,2,4,7,6,5,3]],
 'G2' => [['avatar','heading','socials','phone_collector','vimeo','link_forever_shop','link_save_contact'], [0,1,2,5,4,3,6]],
 'G3' => [['avatar','heading','socials','vimeo','link_forever_shop','link_save_contact','link_discount','link_homescreen_android'], [0,1,2,4,6,3,5,7]],
 'G4' => [['avatar','heading','socials','phone_collector','vimeo','link_forever_shop','link_save_contact','custom_html_chatbot'], [0,1,2,5,4,3,6,7]],
 'G5' => [['avatar','heading','socials','vimeo','link_save_contact','custom_html_chatbot','custom_html_whatsapp','link_homescreen_android','link_forever_shop'], [0,1,2,8,3,4,5,6,7]],
 'G6' => [['avatar','heading','socials','vimeo','custom_html_chatbot','custom_html_whatsapp','link_homescreen_android','link_forever_shop'], [0,1,5,2,7,3,4,6]],
 'G7' => [['avatar','heading','socials','vimeo','link_save_contact','custom_html_chatbot','custom_html_whatsapp','link_homescreen_android','link_forever_shop','link_forever_webshop_reg'], [0,1,2,8,3,4,5,6,7,9]],
];
$shapes['IMPORT_2023_07_07'] = $shapes['G1'];
$dates = ['G1'=>'2023-09-13 12:00:00','G2'=>'2024-09-13 12:00:00','G3'=>'2025-03-13 12:00:00','G4'=>'2025-09-13 12:00:00','G5'=>'2026-06-13 12:00:00','G6'=>'2026-07-13 12:00:00','G7'=>'2026-10-13 12:00:00','IMPORT_2023_07_07'=>'2023-07-05 12:00:00'];

function aloe_fixture(string $generation = 'G1'): array {
 global $shapes, $dates;
 [$types, $displayPositions] = $shapes[$generation];
 $registered = $dates[$generation];
 $created = $generation === 'IMPORT_2023_07_07' ? '2023-07-07 06:08:34' : $registered;
 $link = ['link_id'=>70,'first_link_id'=>70,'user_id'=>8,'datetime'=>$created,'user_datetime'=>$registered,'last_datetime'=>null,'settings'=>'{"fcc_card":"v1"}','url'=>'synthetic','name'=>'Fixture'];
 $blocks = [];
 foreach($types as $position=>$type) {
  $blocks[] = ['biolink_block_id'=>1000+$position*3,'link_id'=>70,'type'=>$type,'order'=>array_search($position,$displayPositions,true),'settings'=>'{}','is_enabled'=>1,'datetime'=>$created,'last_datetime'=>null];
 }
 return ['link'=>$link,'blocks'=>$blocks];
}

function aloe_extra(array $fixture, string $type = 'link_forever_shop', string $at = '2026-10-02 11:08:04', int $orderOffset = 0, string $settings = '{"factory_shop":true}'): array {
 $fixture['blocks'][] = ['biolink_block_id'=>9000+count($fixture['blocks']),'link_id'=>70,'type'=>$type,'order'=>count($fixture['blocks'])+5+$orderOffset,'settings'=>$settings,'is_enabled'=>1,'datetime'=>$at,'last_datetime'=>null];
 return $fixture;
}

$cases = [];
function aloe_check(string $name, bool $pass, array $evidence = []): void {
 $GLOBALS['cases'][] = ['case'=>$name,'pass'=>$pass]+$evidence;
}
function aloe_expect(string $name, array $fixture, bool $expected): void {
 $actual = fcc_cm_aloe_untouched($fixture['link'],$fixture['blocks']);
 aloe_check($name,$actual === $expected,['expected'=>$expected,'actual'=>$actual]);
}

$definition = fcc_cm_aloe_provenance();
foreach($shapes as $generation=>[$types,$positions]) {
 $actual = $definition['generations'][$generation] ?? [];
 $expectedDisplay = array_map(static fn($position)=>$types[$position],$positions);
 aloe_check('frozen_shape_'.$generation,($actual['creation_types'] ?? null) === $types && ($actual['display_creation_positions'] ?? null) === $positions && ($actual['display_types'] ?? null) === $expectedDisplay);
}
foreach(['G1','G2','G4','G5','IMPORT_2023_07_07'] as $generation) {
 $fixture = aloe_fixture($generation);
 aloe_expect('complete_factory_'.$generation,$fixture,true);
 $fixture['blocks'] = array_reverse($fixture['blocks']);
 aloe_expect('unordered_query_results_'.$generation,$fixture,true);
 foreach(array_keys($fixture['blocks']) as $missing) {
  $deleted = $fixture;
  array_splice($deleted['blocks'],$missing,1);
  aloe_expect('missing_block_'.$generation.'_'.$missing,$deleted,false);
 }
}

foreach(['G3','G6','G7'] as $generation) aloe_expect('unproven_generation_'.$generation,aloe_fixture($generation),false);
foreach(['2024-06-13 12:00:00','2024-07-13 12:00:00','2025-02-13 12:00:00','2025-04-13 12:00:00','2026-02-13 12:00:00','2026-04-13 12:00:00','2026-07-13 12:00:00','2026-10-13 12:00:00','2022-09-13 12:00:00'] as $date) {
 $fixture = aloe_fixture('G1');
 $fixture['link']['datetime'] = $fixture['link']['user_datetime'] = $date;
 foreach($fixture['blocks'] as &$block) $block['datetime'] = $date;
 unset($block);
 aloe_expect('unknown_or_transition_month_'.substr($date,0,7),$fixture,false);
}

$fixture = aloe_fixture('G5');
/* Removing save contact can resemble the later G6 template. It is still an owner edit. */
array_splice($fixture['blocks'],4,1);
aloe_expect('neighbor_generation_after_save_contact_deleted',$fixture,false);
$fixture = aloe_fixture('G5');
$fixture['blocks'] = array_slice($fixture['blocks'],0,3);
foreach($fixture['blocks'] as &$block) $block['datetime'] = '2026-07-17 12:00:00';
unset($block);
aloe_expect('later_copied_first_three_blocks',$fixture,false);

$fixture = aloe_fixture('G1');
[$fixture['blocks'][0]['order'],$fixture['blocks'][1]['order']] = [$fixture['blocks'][1]['order'],$fixture['blocks'][0]['order']];
aloe_expect('owner_changed_display_order',$fixture,false);
$fixture = aloe_fixture('G1');
[$fixture['blocks'][3]['order'],$fixture['blocks'][4]['order']] = [$fixture['blocks'][4]['order'],$fixture['blocks'][3]['order']];
/* Display type strings remain equal, so this specifically checks creation ordinal. */
aloe_expect('duplicate_plain_links_swapped',$fixture,false);
$fixture = aloe_fixture('G1');
[$fixture['blocks'][0]['biolink_block_id'],$fixture['blocks'][1]['biolink_block_id']] = [$fixture['blocks'][1]['biolink_block_id'],$fixture['blocks'][0]['biolink_block_id']];
aloe_expect('wrong_factory_creation_order',$fixture,false);

foreach(['link_marker','block_marker','owner_disabled','owner_disabled_false_plan_marker','second_card','delayed_card','delayed_template_block','empty_blocks'] as $kind) {
 $fixture = aloe_fixture('G2');
 if($kind === 'link_marker') $fixture['link']['last_datetime'] = '2026-10-11 09:00:00';
 if($kind === 'block_marker') $fixture['blocks'][0]['last_datetime'] = '2026-10-11 09:00:00';
 if($kind === 'owner_disabled' || $kind === 'owner_disabled_false_plan_marker') $fixture['blocks'][0]['is_enabled'] = 0;
 if($kind === 'owner_disabled_false_plan_marker') $fixture['blocks'][0]['settings'] = '{"plan_auto_disabled":false}';
 if($kind === 'second_card') $fixture['link']['first_link_id'] = 69;
 if($kind === 'delayed_card') $fixture['link']['datetime'] = '2024-09-13 12:10:01';
 if($kind === 'delayed_template_block') $fixture['blocks'][0]['datetime'] = '2024-09-13 12:10:01';
 if($kind === 'empty_blocks') $fixture['blocks'] = [];
 aloe_expect($kind,$fixture,false);
}
$fixture = aloe_fixture('G2');
$fixture['blocks'][0]['is_enabled'] = 0;
$fixture['blocks'][0]['settings'] = '{"plan_auto_disabled":true}';
aloe_expect('package_disabled_core_factory_block',$fixture,true);
$fixture = aloe_fixture('G2');
$fixture['link']['datetime'] = '2024-09-13 12:10:00';
$fixture['blocks'][0]['datetime'] = '2024-09-13 12:10:00';
aloe_expect('registration_window_inclusive_600_seconds',$fixture,true);

foreach(['2026-10-02 11:08:04','2026-10-02 11:08:07'] as $at) {
 aloe_expect('system_factory_batch_boundary_'.substr($at,-2),aloe_extra(aloe_fixture('G1'),'link_forever_shop',$at),true);
}
$fixture = aloe_extra(aloe_extra(aloe_fixture('G1'),'link_forever_shop','2026-10-02 11:08:04'),'link_forever_webshop_reg','2026-10-02 11:08:07');
aloe_expect('both_system_factory_extensions_at_end',$fixture,true);
$fixture['blocks'] = array_reverse($fixture['blocks']);
aloe_expect('both_extensions_unordered_query_results',$fixture,true);
foreach(['2026-10-02 11:08:03','2026-10-02 11:08:08','2026-10-02 11:09:04','2026-10-03 11:08:04','2026-10-01 11:08:04'] as $at) {
 aloe_expect('unproven_batch_'.str_replace([' ',':'],'_',$at),aloe_extra(aloe_fixture('G1'),'link_forever_shop',$at),false);
}
foreach(['{}','{"factory_shop":false}'] as $index=>$settings) aloe_expect('missing_or_false_factory_marker_'.$index,aloe_extra(aloe_fixture('G1'),'link_forever_shop','2026-10-02 11:08:04',0,$settings),false);
aloe_expect('unapproved_batch_block_type',aloe_extra(aloe_fixture('G1'),'link_discount'),false);
aloe_expect('duplicate_existing_template_type',aloe_extra(aloe_fixture('G2'),'link_forever_shop'),false);
$fixture = aloe_extra(aloe_fixture('G1'));
$fixture['blocks'][count($fixture['blocks'])-1]['order'] = 3;
aloe_expect('system_extension_moved_inside_template',$fixture,false);
$fixture = aloe_extra(aloe_extra(aloe_fixture('G1')),'link_forever_shop','2026-10-02 11:08:07');
aloe_expect('duplicate_system_extension_type',$fixture,false);
$fixture = aloe_extra(aloe_extra(aloe_fixture('G1')),'link_forever_webshop_reg','2026-10-02 11:08:07');
$last = count($fixture['blocks'])-1;
$fixture['blocks'][$last]['order'] = $fixture['blocks'][$last-1]['order'];
aloe_expect('duplicate_system_extension_order',$fixture,false);
$fixture = aloe_extra(aloe_extra(aloe_fixture('G1')),'link_forever_webshop_reg','2026-10-02 11:08:07');
$last = count($fixture['blocks'])-1;
[$fixture['blocks'][$last]['order'],$fixture['blocks'][$last-1]['order']] = [$fixture['blocks'][$last-1]['order'],$fixture['blocks'][$last]['order']];
aloe_expect('swapped_system_extension_display_order',$fixture,false);

foreach(['2023-07-04 00:00:00','2023-07-06 23:59:59'] as $date) {
 $fixture = aloe_fixture('IMPORT_2023_07_07');
 $fixture['link']['user_datetime'] = $date;
 aloe_expect('import_window_accept_'.str_replace([' ',':'],'_',$date),$fixture,true);
}
foreach(['2023-07-03 23:59:59','2023-07-07 00:00:00'] as $date) {
 $fixture = aloe_fixture('IMPORT_2023_07_07');
 $fixture['link']['user_datetime'] = $date;
 aloe_expect('import_window_reject_'.str_replace([' ',':'],'_',$date),$fixture,false);
}
$fixture = aloe_fixture('IMPORT_2023_07_07');
$fixture['blocks'][0]['datetime'] = '2023-07-07 06:08:35';
aloe_expect('import_requires_exact_batch_second',$fixture,false);

foreach(['2024-02-30 12:00:00','2023-02-29 12:00:00','2024-13-01 00:00:00','2024-01-01 24:00:00','2024-1-01 00:00:00','2024-01-01 00:00:00 UTC','',null,1704067200] as $index=>$date) {
 aloe_check('invalid_timestamp_'.$index,fcc_cm_aloe_timestamp($date) === null);
}
foreach(['datetime','user_datetime'] as $field) {
 $fixture = aloe_fixture('G1');
 $fixture['link'][$field] = '2023-09-31 12:00:00';
 aloe_expect('invalid_link_timestamp_'.$field,$fixture,false);
}
$fixture = aloe_fixture('G1');
$fixture['blocks'][0]['datetime'] = '2023-09-31 12:00:00';
aloe_expect('invalid_block_timestamp',$fixture,false);
$originalTimezone = date_default_timezone_get();
foreach(['UTC','Europe/Zagreb','Pacific/Honolulu','Asia/Tokyo'] as $timezone) {
 date_default_timezone_set($timezone);
 aloe_check('timestamp_uses_utc_'.str_replace('/','_',$timezone),fcc_cm_aloe_timestamp('2024-02-29 00:00:00') === 1709164800);
 aloe_expect('fixture_independent_of_timezone_'.str_replace('/','_',$timezone),aloe_fixture('G2'),true);
}
date_default_timezone_set($originalTimezone);

/* The actual apply guard must re-read the locked state, not trust a previous preview. */
function fcc_partner_one(string $sql, array $args = []): ?array {
 if(!str_contains($sql,'FROM links WHERE link_id=? FOR UPDATE')) throw new RuntimeException('Unexpected synthetic SELECT');
 $GLOBALS['locks']++;
 return $GLOBALS['memory']['link'];
}
function fcc_partner_rows(string $sql, array $args = []): array {
 if(str_contains($sql,'SELECT l.link_id')) return [$GLOBALS['memory']['link']];
 if(str_contains($sql,'FROM biolinks_blocks WHERE link_id')) {
  if(str_contains($sql,'FOR UPDATE')) $GLOBALS['locks']++;
  return $GLOBALS['memory']['blocks'];
 }
 throw new RuntimeException('Unexpected synthetic rows SELECT');
}
function fcc_partner_query(string $sql, array $args = []) {
 if(str_starts_with($sql,'INSERT INTO fcc_content_migration_log')) $GLOBALS['writes'][] = 'log';
 elseif(str_starts_with($sql,'UPDATE `links` SET `settings`=?')) {
  $GLOBALS['writes'][] = 'settings';
  $GLOBALS['memory']['link']['settings'] = $args[0];
 } else throw new RuntimeException('Unexpected synthetic write');
 return (object)['affected_rows'=>1];
}
foreach(['unchanged','added_block','deleted_save_contact','changed_display_order','owner_disabled','changed_old_value'] as $race) {
 $GLOBALS['memory'] = aloe_fixture('G5');
 $GLOBALS['writes'] = []; $GLOBALS['locks'] = 0;
 $plan = fcc_cm_aloe_plan();
 if($race === 'added_block') $GLOBALS['memory'] = aloe_extra($GLOBALS['memory'],'paragraph','2026-10-12 17:00:00');
 if($race === 'deleted_save_contact') array_splice($GLOBALS['memory']['blocks'],4,1);
 if($race === 'changed_display_order') {
  [$GLOBALS['memory']['blocks'][0]['order'],$GLOBALS['memory']['blocks'][1]['order']] = [$GLOBALS['memory']['blocks'][1]['order'],$GLOBALS['memory']['blocks'][0]['order']];
 }
 if($race === 'owner_disabled') $GLOBALS['memory']['blocks'][0]['is_enabled'] = 0;
 if($race === 'changed_old_value') $GLOBALS['memory']['link']['settings'] = '{"fcc_card":"v1","owner_choice":true}';
 $before = hash('sha256',json_encode($GLOBALS['memory']));
 $result = fcc_cm_aloe_apply_rows($plan,'synthetic-test',1);
 $expectedApplied = $race === 'unchanged' ? 1 : 0;
 $state = fcc_card_settings($GLOBALS['memory']['link']['settings']);
 $unchanged = $before === hash('sha256',json_encode($GLOBALS['memory']));
 aloe_check('preapply_lock_recheck_'.$race,count($plan) === 1 && $GLOBALS['locks'] === 2 && $result['applied'] === $expectedApplied && $result['skipped'] === 1-$expectedApplied && count($GLOBALS['writes']) === 2*$expectedApplied && ($expectedApplied ? ($state->fcc_card ?? '') === 'v2' : $unchanged),['applied'=>$result['applied'],'skipped'=>$result['skipped'],'write_count'=>count($GLOBALS['writes'])]);
}

$failed = array_values(array_filter($cases,static fn($case)=>!$case['pass']));
echo json_encode(['isolated_no_database'=>true,'helper_sha256'=>hash_file('sha256',APP_PATH.'helpers/fcc_content_migration.php'),'card_helper_sha256'=>hash_file('sha256',APP_PATH.'helpers/fcc_card.php'),'definition_sha256'=>hash_file('sha256',APP_PATH.'config/fcc_card_template_generations_2026_10_12.json'),'count'=>count($cases),'failed'=>count($failed),'cases'=>$cases],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES),"\n";
exit($failed ? 1 : 0);
