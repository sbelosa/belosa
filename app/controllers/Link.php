<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 *
 * 🌍 View all other existing AltumCode projects via https://altumcode.com/
 * 📧 Get in touch for support or general queries via https://altumcode.com/contact
 * 📤 Download the latest version via https://altumcode.com/downloads
 *
 * 🐦 X/Twitter: https://x.com/AltumCode
 * 📘 Facebook: https://facebook.com/altumcode
 * 📸 Instagram: https://instagram.com/altumcode
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Models\BiolinksThemes;
use Altum\Models\Domain;
use Altum\Title;

defined('ALTUMCODE') || die();

class Link extends Controller {
    public $link;

    private function normalize_whatsapp_phone($phone) {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    private function extract_user_phone_from_preferences($user) {
        if(!$user) {
            return '';
        }

        $preferences = is_string($user->preferences ?? null) ? json_decode($user->preferences ?? '{}') : ($user->preferences ?? (object) []);
        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        $meta = $preferences->meta ?? (object) [];
        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        return $this->normalize_whatsapp_phone($meta->phone ?? '');
    }

    private function get_default_whatsapp_phone_for_user($user) {
        if(!$user) {
            return '';
        }

        $referrer_phone = '';
        if(!empty($user->referred_by)) {
            $referrer_user = db()->where('user_id', (int) $user->referred_by)->getOne('users', ['preferences']);
            $referrer_phone = $this->extract_user_phone_from_preferences($referrer_user);
        }

        if($referrer_phone) {
            return $referrer_phone;
        }

        return $this->extract_user_phone_from_preferences($user);
    }

    private function get_ai_profile_values($preferences): array {
        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        $profile = $preferences->leader_ai_profile ?? null;

        if(is_array($profile)) {
            $profile = (object) $profile;
        }

        return [
            'primary_goal' => (string) ($profile->primary_goal ?? ''),
            'priority_offer' => (string) ($profile->priority_offer ?? ''),
            'active_channels' => is_array($profile->active_channels ?? null) ? array_values($profile->active_channels) : [],
            'available_time' => (string) ($profile->available_time ?? ''),
            'biggest_blocker' => (string) ($profile->biggest_blocker ?? ''),
            'communication_style' => (string) ($profile->communication_style ?? ''),
            'follow_up_readiness' => (string) ($profile->follow_up_readiness ?? ''),
            'weekly_change' => (string) ($profile->weekly_change ?? ''),
        ];
    }

    private function is_ai_profile_complete(array $values): bool {
        return (bool) ($values['primary_goal'] && $values['priority_offer'] && !empty($values['active_channels']) && $values['available_time'] && $values['biggest_blocker'] && $values['communication_style'] && $values['follow_up_readiness'] && $values['weekly_change']);
    }

    private function decode_biolink_block_settings($settings): \stdClass {
        if(is_string($settings)) {
            $settings = json_decode($settings ?? '{}');
        }

        if(is_array($settings)) {
            $settings = (object) $settings;
        }

        if(!$settings instanceof \stdClass) {
            $settings = new \stdClass();
        }

        return $settings;
    }

    private function is_app_review_whatsapp_socials_block(\stdClass $settings): bool {
        $socials = $settings->socials ?? null;

        if(is_object($socials)) {
            $socials = (array) $socials;
        }

        if(!is_array($socials)) {
            return false;
        }

        $whatsapp_value = trim((string) ($socials['whatsapp'] ?? ''));

        if($whatsapp_value === '') {
            return false;
        }

        foreach($socials as $social_key => $social_value) {
            if($social_key === 'whatsapp') {
                continue;
            }

            if(trim((string) $social_value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function is_app_review_whatsapp_block(string $type, \stdClass $settings): bool {
        if($type === 'custom_html_whatsapp') {
            return true;
        }

        if($type === 'socials') {
            return $this->is_app_review_whatsapp_socials_block($settings);
        }

        if($type !== 'link') {
            return false;
        }

        $location_url = trim((string) ($settings->location_url ?? ''));
        if($location_url === '') {
            return false;
        }

        return str_contains(mb_strtolower($location_url), 'wa.me')
            || str_contains(mb_strtolower($location_url), 'api.whatsapp.com');
    }

    private function get_app_review_signal_block_maps(array $link_ids): array {
        $signal_maps = [];

        foreach($link_ids as $link_id) {
            $link_id = (int) $link_id;

            if($link_id <= 0) {
                continue;
            }

            $signal_maps[$link_id] = [
                'shop_block_ids' => [],
                'whatsapp_block_ids' => [],
                'product_block_ids' => [],
                'funnel_block_ids' => [],
            ];
        }

        if(empty($signal_maps)) {
            return [];
        }

        $shop_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $relevant_types = array_unique(array_merge($shop_types, ['link_forever_product', 'lead_funnel', 'custom_html_whatsapp', 'socials', 'link']));
        $relevant_types_sql = "'" . implode("','", array_map(static function($type) {
            return str_replace("'", "\\'", (string) $type);
        }, $relevant_types)) . "'";
        $link_ids_sql = implode(',', array_map('intval', array_keys($signal_maps)));

        $blocks_result = database()->query("SELECT `biolink_block_id`, `link_id`, `type`, `settings`
            FROM `biolinks_blocks`
            WHERE `link_id` IN ({$link_ids_sql})
              AND `type` IN ({$relevant_types_sql})");

        while($row = $blocks_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);
            $block_id = (int) ($row->biolink_block_id ?? 0);
            $type = (string) ($row->type ?? '');

            if(!$link_id || !$block_id || !isset($signal_maps[$link_id])) {
                continue;
            }

            $settings = $this->decode_biolink_block_settings($row->settings ?? null);

            if(in_array($type, $shop_types, true)) {
                $signal_maps[$link_id]['shop_block_ids'][] = $block_id;
            }

            if($type === 'link_forever_product') {
                $signal_maps[$link_id]['product_block_ids'][] = $block_id;
            }

            if($type === 'lead_funnel') {
                $signal_maps[$link_id]['funnel_block_ids'][] = $block_id;
            }

            if($this->is_app_review_whatsapp_block($type, $settings)) {
                $signal_maps[$link_id]['whatsapp_block_ids'][] = $block_id;
            }
        }

        foreach($signal_maps as &$signal_map) {
            foreach(['shop_block_ids', 'whatsapp_block_ids', 'product_block_ids', 'funnel_block_ids'] as $signal_key) {
                $signal_map[$signal_key] = array_values(array_unique(array_map('intval', $signal_map[$signal_key])));
            }
        }
        unset($signal_map);

        return $signal_maps;
    }

    private function calculate_app_review_weighted_signal_score(array $signals): int {
        return (int) (
            (int) ($signals['shop_contacts_30d'] ?? 0)
            + (int) ($signals['whatsapp_contacts_30d'] ?? 0)
            + (int) ($signals['product_clicks_30d'] ?? 0)
            + ((int) ($signals['funnel_registrations_30d'] ?? 0) * 2)
        );
    }

    private function enrich_app_review_signal_snapshots(array $apps, string $period_start_datetime): array {
        if(empty($apps)) {
            return [];
        }

        $signal_maps = $this->get_app_review_signal_block_maps(array_keys($apps));
        $track_block_map = [];
        $funnel_block_map = [];

        foreach($apps as $link_id => &$app) {
            $signal_map = $signal_maps[(int) $link_id] ?? [
                'shop_block_ids' => [],
                'whatsapp_block_ids' => [],
                'product_block_ids' => [],
                'funnel_block_ids' => [],
            ];

            $app['shop_contacts_30d'] = 0;
            $app['whatsapp_contacts_30d'] = 0;
            $app['product_clicks_30d'] = 0;
            $app['funnel_registrations_30d'] = 0;
            $app['weighted_signal_score'] = 0;

            foreach(($signal_map['shop_block_ids'] ?? []) as $block_id) {
                $track_block_map[(int) $block_id]['shop_contacts_30d'][] = (int) $link_id;
            }

            foreach(($signal_map['whatsapp_block_ids'] ?? []) as $block_id) {
                $track_block_map[(int) $block_id]['whatsapp_contacts_30d'][] = (int) $link_id;
            }

            foreach(($signal_map['product_block_ids'] ?? []) as $block_id) {
                $track_block_map[(int) $block_id]['product_clicks_30d'][] = (int) $link_id;
            }

            foreach(($signal_map['funnel_block_ids'] ?? []) as $block_id) {
                $funnel_block_map[(int) $block_id][] = (int) $link_id;
            }
        }
        unset($app);

        if(!empty($track_block_map)) {
            $track_block_ids_sql = implode(',', array_map('intval', array_keys($track_block_map)));
            $track_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `track_links`
                WHERE `datetime` >= '{$period_start_datetime}'
                  AND `is_unique` = 1
                  AND `biolink_block_id` IN ({$track_block_ids_sql})
                GROUP BY `biolink_block_id`");

            while($row = $track_result->fetch_object()) {
                $block_id = (int) ($row->biolink_block_id ?? 0);
                $total = (int) ($row->total ?? 0);

                foreach(($track_block_map[$block_id] ?? []) as $signal_key => $link_ids) {
                    foreach((array) $link_ids as $link_id) {
                        if(isset($apps[$link_id])) {
                            $apps[$link_id][$signal_key] += $total;
                        }
                    }
                }
            }
        }

        if(!empty($funnel_block_map)) {
            $funnel_block_ids_sql = implode(',', array_map('intval', array_keys($funnel_block_map)));
            $funnel_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `data`
                WHERE `type` = 'lead_funnel'
                  AND `datetime` >= '{$period_start_datetime}'
                  AND `biolink_block_id` IN ({$funnel_block_ids_sql})
                GROUP BY `biolink_block_id`");

            while($row = $funnel_result->fetch_object()) {
                $block_id = (int) ($row->biolink_block_id ?? 0);
                $total = (int) ($row->total ?? 0);

                foreach((array) ($funnel_block_map[$block_id] ?? []) as $link_id) {
                    if(isset($apps[$link_id])) {
                        $apps[$link_id]['funnel_registrations_30d'] += $total;
                    }
                }
            }
        }

        foreach($apps as &$app) {
            $app['weighted_signal_score'] = $this->calculate_app_review_weighted_signal_score($app);
        }
        unset($app);

        return $apps;
    }

    private function compare_app_review_signal_rows(array $a, array $b): int {
        return (($b['weighted_signal_score'] ?? 0) <=> ($a['weighted_signal_score'] ?? 0))
            ?: (($b['shop_contacts_30d'] ?? 0) <=> ($a['shop_contacts_30d'] ?? 0))
            ?: (($b['whatsapp_contacts_30d'] ?? 0) <=> ($a['whatsapp_contacts_30d'] ?? 0))
            ?: (($b['funnel_registrations_30d'] ?? 0) <=> ($a['funnel_registrations_30d'] ?? 0))
            ?: (($b['product_clicks_30d'] ?? 0) <=> ($a['product_clicks_30d'] ?? 0))
            ?: ((string) ($a['url'] ?? '') <=> (string) ($b['url'] ?? ''));
    }

    private function get_default_app_review_benchmark(): array {
        return [
            'shop_contacts_30d' => 18,
            'whatsapp_contacts_30d' => 10,
            'product_clicks_30d' => 8,
            'funnel_registrations_30d' => 4,
            'weighted_signal_score' => 44,
        ];
    }

    private function get_app_review_benchmark_payload(array $selected_app = []): array {
        $period_30d_start = (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00');
        $now_datetime = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $shop_block_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $shop_block_types_sql = "'" . implode("','", $shop_block_types) . "'";
        $shop_condition = \Altum\Link::get_forever_shop_click_condition_sql('`tl`', '`bb`', $shop_block_types_sql);

        $qualified_users = [];
        $qualified_users_result = database()->query("SELECT `tl`.`user_id`, COUNT(*) AS `shop_contacts_30d`
            FROM `track_links` AS `tl`
            INNER JOIN `biolinks_blocks` AS `bb` ON `bb`.`biolink_block_id` = `tl`.`biolink_block_id`
            INNER JOIN `users` AS `u` ON `u`.`user_id` = `tl`.`user_id`
            WHERE `tl`.`datetime` >= '{$period_30d_start}'
              AND `tl`.`is_unique` = 1
              AND {$shop_condition}
              AND `u`.`status` = 1
              AND `u`.`plan_id` = '5'
              AND (`u`.`plan_expiration_date` IS NULL OR `u`.`plan_expiration_date` = '' OR `u`.`plan_expiration_date` >= '{$now_datetime}')
            GROUP BY `tl`.`user_id`
            HAVING `shop_contacts_30d` > 15");

        while($row = $qualified_users_result->fetch_object()) {
            $qualified_users[(int) ($row->user_id ?? 0)] = (int) ($row->shop_contacts_30d ?? 0);
        }

        if(empty($qualified_users)) {
            return [
                'benchmark' => $this->get_default_app_review_benchmark(),
                'peer_examples' => [],
            ];
        }

        $qualified_user_ids_sql = implode(',', array_map('intval', array_keys($qualified_users)));
        $users_biolinks_latest_sql = \Altum\Link::get_users_biolinks_latest_subquery('ub');
        $apps_result = database()->query("SELECT `ub`.`user_id`, `ub`.`biolink_id` AS `link_id`, `l`.`url`
            FROM {$users_biolinks_latest_sql}
            INNER JOIN `links` AS `l` ON `l`.`link_id` = `ub`.`biolink_id` AND `l`.`type` = 'biolink'
            WHERE `l`.`is_enabled` = 1
              AND `ub`.`user_id` IN ({$qualified_user_ids_sql})");

        $benchmark_apps = [];

        while($row = $apps_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);

            if(!$link_id) {
                continue;
            }

            $benchmark_apps[$link_id] = [
                'link_id' => $link_id,
                'user_id' => (int) ($row->user_id ?? 0),
                'url' => (string) ($row->url ?? ''),
                'public_url' => !empty($row->url) ? url((string) $row->url) : '',
            ];
        }

        if(empty($benchmark_apps)) {
            return [
                'benchmark' => $this->get_default_app_review_benchmark(),
                'peer_examples' => [],
            ];
        }

        $benchmark_apps = array_values($this->enrich_app_review_signal_snapshots($benchmark_apps, $period_30d_start));
        usort($benchmark_apps, fn(array $a, array $b): int => $this->compare_app_review_signal_rows($a, $b));

        if(empty($benchmark_apps)) {
            return [
                'benchmark' => $this->get_default_app_review_benchmark(),
                'peer_examples' => [],
            ];
        }

        $top_benchmark_apps = array_slice($benchmark_apps, 0, min(5, count($benchmark_apps)));
        $totals = [
            'shop_contacts_30d' => 0,
            'whatsapp_contacts_30d' => 0,
            'product_clicks_30d' => 0,
            'funnel_registrations_30d' => 0,
            'weighted_signal_score' => 0,
        ];

        foreach($top_benchmark_apps as $app) {
            $totals['shop_contacts_30d'] += (int) ($app['shop_contacts_30d'] ?? 0);
            $totals['whatsapp_contacts_30d'] += (int) ($app['whatsapp_contacts_30d'] ?? 0);
            $totals['product_clicks_30d'] += (int) ($app['product_clicks_30d'] ?? 0);
            $totals['funnel_registrations_30d'] += (int) ($app['funnel_registrations_30d'] ?? 0);
            $totals['weighted_signal_score'] += (int) ($app['weighted_signal_score'] ?? 0);
        }

        $count = max(1, count($top_benchmark_apps));
        $selected_performance = !empty($selected_app) ? $selected_app : ['weighted_signal_score' => 0];
        $peer_examples = [];

        foreach($benchmark_apps as $app) {
            if($this->compare_app_review_signal_rows($app, $selected_performance) <= 0) {
                continue;
            }

            $peer_examples[] = [
                'label' => (string) (($app['url'] ?? '') ?: '-'),
                'public_url' => (string) ($app['public_url'] ?? ''),
            ];

            if(count($peer_examples) >= 3) {
                break;
            }
        }

        return [
            'benchmark' => [
                'shop_contacts_30d' => max(1, (int) round($totals['shop_contacts_30d'] / $count)),
                'whatsapp_contacts_30d' => max(1, (int) round($totals['whatsapp_contacts_30d'] / $count)),
                'product_clicks_30d' => max(1, (int) round($totals['product_clicks_30d'] / $count)),
                'funnel_registrations_30d' => max(1, (int) round($totals['funnel_registrations_30d'] / $count)),
                'weighted_signal_score' => max(1, (int) round($totals['weighted_signal_score'] / $count)),
            ],
            'peer_examples' => $peer_examples,
        ];
    }

    private function get_app_review_quality_payload(array $selected_app): array {
        $benchmark_payload = $this->get_app_review_benchmark_payload($selected_app);
        $benchmark = (array) ($benchmark_payload['benchmark'] ?? []);
        $performance = $selected_app;

        $ratios = [
            'shop_contacts_30d' => min(1.2, ((int) ($performance['shop_contacts_30d'] ?? 0)) / max(1, (int) ($benchmark['shop_contacts_30d'] ?? 1))),
            'whatsapp_contacts_30d' => min(1.2, ((int) ($performance['whatsapp_contacts_30d'] ?? 0)) / max(1, (int) ($benchmark['whatsapp_contacts_30d'] ?? 1))),
            'product_clicks_30d' => min(1.15, ((int) ($performance['product_clicks_30d'] ?? 0)) / max(1, (int) ($benchmark['product_clicks_30d'] ?? 1))),
            'funnel_registrations_30d' => min(1.25, ((int) ($performance['funnel_registrations_30d'] ?? 0)) / max(1, (int) ($benchmark['funnel_registrations_30d'] ?? 1))),
        ];

        $score = (int) round(min(100,
            ($ratios['shop_contacts_30d'] * 25) +
            ($ratios['whatsapp_contacts_30d'] * 25) +
            ($ratios['product_clicks_30d'] * 20) +
            ($ratios['funnel_registrations_30d'] * 30)
        ));

        $level_key = $score >= 80 ? 'strong' : ($score >= 60 ? 'growing' : 'foundation');

        return [
            'score' => $score,
            'level_key' => $level_key,
            'level_label' => l('ai_plan.app_review_quality_level.' . $level_key),
            'summary' => l('ai_plan.app_review_quality_summary.' . $level_key),
            'performance' => [
                'shop_contacts_30d' => (int) ($performance['shop_contacts_30d'] ?? 0),
                'whatsapp_contacts_30d' => (int) ($performance['whatsapp_contacts_30d'] ?? 0),
                'product_clicks_30d' => (int) ($performance['product_clicks_30d'] ?? 0),
                'funnel_registrations_30d' => (int) ($performance['funnel_registrations_30d'] ?? 0),
            ],
            'peer_examples' => (array) ($benchmark_payload['peer_examples'] ?? []),
        ];
    }

    public function index() {

        \Altum\Authentication::guard();

        /* Custom code: FC-2026-03-19: self-heal link states before opening editor */
        (new \Altum\Models\User())->sync_links_with_plan($this->user->user_id);
        /* /Custom code: FC-2026-03-19 */

        $link_id = isset($this->params[0]) ? (int) $this->params[0] : null;
        $method = isset($this->params[1]) && in_array($this->params[1], ['settings', 'statistics', 'download']) ? $this->params[1] : 'settings';

        /* Make sure the link exists and is accessible to the user */
        if(!$this->link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->getOne('links')) {
            redirect('dashboard');
        }

        $biolink_blocks = require APP_PATH . 'includes/biolink_blocks.php';
        $links_types = require APP_PATH . 'includes/links_types.php';

        $this->link->settings = json_decode($this->link->settings ?? '');
        $this->link->pixels_ids = json_decode($this->link->pixels_ids ?? '[]');
        $this->link->email_reports = json_decode($this->link->email_reports ?? '[]');
        $ai_profile_values = $this->get_ai_profile_values($this->user->preferences ?? null);
        $has_ai_growth_plan_access = \Altum\Authentication::is_admin() || !empty($this->user->plan_settings->ai_growth_plan_is_enabled ?? false);
        $app_review_is_accessible = $has_ai_growth_plan_access && (\Altum\Authentication::is_admin() || $this->is_ai_profile_complete($ai_profile_values));
        $app_review_locked_reason = !$has_ai_growth_plan_access ? l('global.info_message.plan_feature_no_access') : ($app_review_is_accessible ? '' : l('ai_plan.app_review_locked_entry_tooltip'));
        $app_review_page_url = url('ai-plan?section=app_review');

        /* Check for the plan limit */
        $plan_limit = match($this->link->type) {
            'biolink' => 'biolinks_limit',
            'link' => 'links_limit',
            'file' => 'files_limit',
            'vcard' => 'vcards_limit',
            'event' => 'events_limit',
            'static' => 'static_limit',
        };
        /* Custom code: FC-2026-03-19: allow editing the protected default biolink/vcard after downgrade */
        $default_biolink_id = (int) (\Altum\Link::get_user_main_biolink_id((int) $this->user->user_id) ?? 0);
        $default_vcard_id = (int) (db()->where('user_id', $this->user->user_id)->getValue('users_vcards', 'vcard_id') ?? 0);
        $is_protected_default_link = ($this->link->type == 'biolink' && $default_biolink_id && (int) $this->link->link_id === $default_biolink_id)
            || ($this->link->type == 'vcard' && $default_vcard_id && (int) $this->link->link_id === $default_vcard_id);
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} AND `type` = '{$this->link->type}' AND `is_enabled` = 1")->fetch_object()->total ?? 0;
        if($this->user->plan_settings->{$plan_limit} != -1 && $total_rows > $this->user->plan_settings->{$plan_limit} && !$is_protected_default_link) {
            redirect('links?type=' . $this->link->type);
        }
        /* /Custom code: FC-2026-03-19 */

        /* Get the current domain if needed */
        $this->link->domain = $this->link->domain_id ? (new Domain())->get_domain_by_domain_id($this->link->domain_id) : null;

        /* Determine the actual full url */
        $this->link->full_url = $this->link->domain ? $this->link->domain->url . ($this->link->domain->link_id == $this->link->link_id ? null : $this->link->url) : SITE_URL . $this->link->url;

        /* Static links need the / for proper asset pathing */
        if($this->link->type == 'static') {
            $this->link->full_url .= '/';
        }

        $app_review_quality_payload = null;
        if($this->link->type === 'biolink') {
            $signal_snapshot = [
                (int) $this->link->link_id => [
                    'link_id' => (int) $this->link->link_id,
                    'user_id' => (int) $this->user->user_id,
                    'url' => (string) ($this->link->url ?? ''),
                    'public_url' => (string) ($this->link->full_url ?? ''),
                ],
            ];
            $signal_snapshot = $this->enrich_app_review_signal_snapshots($signal_snapshot, (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00'));
            $app_review_quality_payload = $this->get_app_review_quality_payload($signal_snapshot[(int) $this->link->link_id] ?? []);
        }

        /* Main FCC app context */
        $biolink_main_id = (int) (\Altum\Link::get_user_main_biolink_id((int) $this->user->user_id) ?? 0);
        $vcard_main = db()->where('user_id', $this->user->user_id)->getOne('users_vcards', ['vcard_id']);
        $is_main_biolink_app = $this->link->type === 'biolink' && $biolink_main_id && $biolink_main_id === (int) $this->link->link_id;
        $main_biolink_statistics_url = $biolink_main_id ? url('link/' . $biolink_main_id . '/statistics') : null;

        /* Set a custom title */
        Title::set(sprintf(l('link.title'), $this->link->url));

        /* Handle code for different parts of the page */
        switch($method) {
            case 'settings':

                /* Get available notification handlers */
                $notification_handlers = (new \Altum\Models\NotificationHandlers())->get_notification_handlers_by_user_id($this->user->user_id);

                if($this->link->type == 'biolink') {
					$default_whatsapp_phone = $this->get_default_whatsapp_phone_for_user($this->user);

                    /* Custom code: FC-2026-03-09: provide blog product options for Forever product biolink block */
                    $blog_products = [];
                    $blog_products_result = db()
                        ->where('is_published', 1)
                        ->orderBy('datetime', 'DESC')
                        ->get('blog_posts', 300, ['blog_post_id', 'title', 'description', 'url', 'image', 'language']);
                    $blog_products_index = [];
                    $app_language_code = $this->link->settings->language_code ?? \Altum\Language::$default_code;

                    foreach($blog_products_result as $blog_post) {
                        $translation_key = trim((string) ($blog_post->url ?? ''));

                        if($translation_key === '') {
                            continue;
                        }

                        $language_code = null;
                        if(!empty($blog_post->language) && isset(\Altum\Language::$active_languages[$blog_post->language])) {
                            $language_code = \Altum\Language::$active_languages[$blog_post->language];
                        }

                        $language_prefix = $language_code ? $language_code . '/' : null;
                        $product_row = (object) [
                            'blog_post_id' => (int) $blog_post->blog_post_id,
                            'title' => (string) $blog_post->title,
                            'description' => mb_substr(trim(strip_tags((string) ($blog_post->description ?? ''))), 0, 220),
                            'blog_url' => SITE_URL . $language_prefix . 'blog/' . $blog_post->url,
                            'image_url' => !empty($blog_post->image) ? \Altum\Uploads::get_full_url('blog') . $blog_post->image : null,
                            'translation_key' => $translation_key,
                            'language_code' => $language_code ?: \Altum\Language::$default_code,
                        ];

                        if(!isset($blog_products_index[$translation_key])) {
                            $blog_products_index[$translation_key] = [
                                'rows' => [],
                                'ordered_language_codes' => [],
                            ];
                        }

                        $blog_products_index[$translation_key]['rows'][$product_row->language_code] = $product_row;

                        if(!in_array($product_row->language_code, $blog_products_index[$translation_key]['ordered_language_codes'], true)) {
                            $blog_products_index[$translation_key]['ordered_language_codes'][] = $product_row->language_code;
                        }
                    }

                    foreach($blog_products_index as $translation_key => $product_group) {
                        $preferred_product = $product_group['rows'][$app_language_code]
                            ?? $product_group['rows']['hr']
                            ?? $product_group['rows']['en']
                            ?? reset($product_group['rows']);

                        if(!$preferred_product) {
                            continue;
                        }

                        $preferred_product->translation_key = $translation_key;
                        $preferred_product->available_language_codes = array_values($product_group['ordered_language_codes']);
                        $preferred_product->available_languages_label = implode(' / ', array_map(static function($code) {
                            return mb_strtoupper((string) $code);
                        }, $preferred_product->available_language_codes));

                        $blog_products[] = $preferred_product;
                    }
                    /* /Custom code: FC-2026-03-09 */

                    /* Get available themes */
                    $biolinks_themes = (new BiolinksThemes())->get_biolinks_themes();

                    /* Get the links available for the biolink */
                    $link_links_result = database()->query("SELECT * FROM `biolinks_blocks` WHERE `link_id` = {$this->link->link_id} ORDER BY `order` ASC");
                    /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
                    $user_biolinks = db()->where('user_id', $this->user->user_id)->where('type', 'biolink')->orderBy('url', 'ASC')->get('links', null, ['link_id', 'url']);
                    /* /Custom code: FC-2026-03-23 */

                    /* Add the modals for creating the links inside the biolink */
                    foreach($biolink_blocks as $key => $value) {

                        $data = [
                            'link' => $this->link,
                            'biolink_blocks' => $biolink_blocks,
							'default_whatsapp_phone' => $default_whatsapp_phone,
                            'blog_products' => $blog_products,
                            /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
                            'user_biolinks' => $user_biolinks,
                            /* /Custom code: FC-2026-03-23 */
                        ];

                        /* Custom code */
                        if ($key == 'link_save_contact') {
                            $vcard_block = db()->where('user_id', $this->user->user_id)->where('type', 'vcard')->orderBy('datetime', 'ASC')->getOne('links');

                            if ($vcard_block) {
                                $data['vcard_block'] = $vcard_block;
                            } else {
                                $data['vcard_block'] = null;
                            }                          
                        }
                        /* /Custom code */

                        /* Custom code: FC-2026-03-06: skip missing create modal files to avoid black screen */
                        $create_modal_path = THEME_PATH . 'views/link/settings/biolink_blocks/' . $key . '/' . $key . '_create_modal.php';

                        if(is_file($create_modal_path)) {
                            $view = new \Altum\View('link/settings/biolink_blocks/' . $key . '/' . $key . '_create_modal', (array) $this);
                            \Altum\Event::add_content($view->run($data), 'modals');
                        } else {
                            dil('[BiolinkEditor] Missing create modal for block type: ' . $key . ' path: ' . $create_modal_path);
                        }
                        /* /Custom code: FC-2026-03-06 */
                    }

                    $data = ['biolink_blocks' => $biolink_blocks,];
                    $view = new \Altum\View('link/settings/biolink_link_create_modal', (array) $this);
                    \Altum\Event::add_content($view->run($data), 'modals');

                    $data = ['biolinks_themes' => $biolinks_themes,];
                    $view = new \Altum\View('link/settings/biolink_themes_modal', (array) $this);
                    \Altum\Event::add_content($view->run($data), 'modals');
                }

                /* Get the available domains to use */
                $domains = (new Domain())->get_available_domains_by_user($this->user);

                /* Existing projects */
                $projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);

                /* Existing splash pages */
                $splash_pages = (new \Altum\Models\SplashPages())->get_splash_pages_by_user_id($this->user->user_id);

                /* Existing pixels */
                $pixels = (new \Altum\Models\Pixel())->get_pixels($this->user->user_id);

                /* Existing payment processors */
                if(\Altum\Plugin::is_active('payment-blocks')) {
                    $payment_processors = (new \Altum\Models\PaymentProcessor())->get_payment_processors_by_user_id($this->user->user_id);
                }

                /* Prepare variables for the view */
                $data = [
                    'link'              => $this->link,
                    'method'            => $method,
                    'link_links_result' => $link_links_result ?? null,
                    'domains'           => $domains,
                    'projects'          => $projects,
                    'splash_pages'      => $splash_pages,
                    'pixels'            => $pixels,
                    'payment_processors'=> $payment_processors ?? null,
                    'biolink_blocks'    => $biolink_blocks,
                    'biolinks_themes'   => $biolinks_themes ?? null,
                    'links_types'       => $links_types,
                    'notification_handlers' => $notification_handlers ?? null,
                    'blog_products'     => $blog_products ?? [],
                    /* Custom code: FC-2026-03-23: lead funnel block phase 1 */
                    'user_biolinks'     => $user_biolinks ?? [],
                    /* /Custom code: FC-2026-03-23 */
                     /* Custom code */
                    'vcard_main' => $vcard_main ?? null,
                    'biolink_main' => $biolink_main ?? null,
                    'is_main_biolink_app' => $is_main_biolink_app,
                    'main_biolink_statistics_url' => $main_biolink_statistics_url,
                    'app_review_quality_payload' => $app_review_quality_payload,
                    'app_review_page_url' => $app_review_page_url,
                    'app_review_is_accessible' => $app_review_is_accessible,
                    'app_review_locked_reason' => $app_review_locked_reason,
                     /* /Custom code */
                ];

                break;


            case 'statistics':

                if(!$this->user->plan_settings->statistics) {
                    Alerts::add_error(l('global.info_message.plan_feature_no_access'));
                    redirect('links');
                }

                $action = isset($this->params[2]) && in_array($this->params[2], ['reset']) ? $this->params[2] : null;

                if($action) {
                    switch($action) {
                        case 'reset':

                            if (empty($_POST)) {
            throw_404();
        }

                            /* Team checks */
                            if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.links')) {
                                Alerts::add_error(l('global.info_message.team_no_access'));
                                redirect('link/' . $this->link->link_id . '/statistics');
                            }

                            //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

                            if(!\Altum\Csrf::check()) {
                                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                                redirect('link/' . $this->link->link_id . '/statistics');
                            }

                            $datetime = \Altum\Date::get_start_end_dates_new($_POST['start_date'], $_POST['end_date']);

                            if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

                                /* Clear statistics data */
                                database()->query("DELETE FROM `track_links` WHERE `link_id` = {$this->link->link_id} AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')");

                                /* Set a nice success message */
                                Alerts::add_success(l('global.success_message.update2'));

                                redirect('link/' . $this->link->link_id . '/statistics');

                            }

                            redirect('link/' . $this->link->link_id . '/statistics');

                            break;
                    }
                }

                $type = isset($_GET['type']) && in_array($_GET['type'], ['overview', 'entries', 'referrer_host', 'referrer_path', 'continent_code', 'country', 'city_name', 'os', 'browser', 'device', 'language', 'utm_source', 'utm_medium', 'utm_campaign', 'hour']) ? input_clean($_GET['type']) : 'overview';

                $datetime = \Altum\Date::get_start_end_dates_new();

                /* Get data based on what statistics are needed */
                switch($type) {
                    case 'overview':

                        /* Get the required statistics */
                        $pageviews = [];
                        $pageviews_chart = [];
                        $latest_entries = [];
                        $totals = [
                            'pageviews' => 0,
                            'visitors' => 0,
                        ];

                        $convert_tz_sql = get_convert_tz_sql('`datetime`', $this->user->timezone);

                        $pageviews_result = database()->query("
                            SELECT
                                COUNT(`id`) AS `pageviews`,
                                SUM(`is_unique`) AS `visitors`,
                                DATE_FORMAT({$convert_tz_sql}, '{$datetime['query_date_format']}') AS `formatted_date`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND ({$convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `formatted_date`
                            ORDER BY
                                `formatted_date`
                        ");

                        /* Generate the raw chart data and save pageviews for later usage */
                        while($row = $pageviews_result->fetch_object()) {
                            $pageviews[] = $row;

                            $row->formatted_date = $datetime['process']($row->formatted_date, true);

                            $pageviews_chart[$row->formatted_date] = [
                                'pageviews' => $row->pageviews,
                                'visitors' => $row->visitors
                            ];

                            $totals['pageviews'] += $row->pageviews;
                            $totals['visitors'] += $row->visitors;
                        }

                        $pageviews_chart = get_chart_data($pageviews_chart);

                        $limit = $this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page;
                        $statistics_result = database()->query("
                            SELECT
                                `continent_code`,
                                `country_code`,
                                `city_name`,
                                `referrer_host`,
                                `device_type`,
                                `os_name`,
                                `browser_name`,
                                `browser_language`
                            FROM
                                `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                        ");

                        $latest_result = database()->query("
                            SELECT
                                *
                            FROM
                                `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            ORDER BY
                                `datetime` DESC
                            LIMIT {$limit}
                        ");

                        break;

                    case 'entries':

                        /* Prepare the filtering system */
                        $filters = (new \Altum\Filters([], [], ['datetime']));
                        $filters->set_default_order_by('id', $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
                        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

                        /* Prepare the paginator */
                        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `track_links` WHERE `link_id` = {$this->link->link_id} AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}') {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
                        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('link/' . $this->link->link_id . '/statistics?type=' . $type . '&start_date=' . $datetime['start_date'] . '&end_date=' . $datetime['end_date'] . $filters->get_get() . '&page=%d')));

                        $result = database()->query("
                            SELECT
                                *
                            FROM
                                `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            {$filters->get_sql_where()}
                            {$filters->get_sql_order_by()}
                            {$paginator->get_sql_limit()}
                        ");

                        break;

                    case 'referrer_host':
                    case 'continent_code':
                    case 'os':
                    case 'browser':
                    case 'device':
                    case 'language':

                        $columns = [
                            'referrer_host' => 'referrer_host',
                            'referrer_path' => 'referrer_path',
                            'continent_code' => 'continent_code',
                            'country' => 'country_code',
                            'city_name' => 'city_name',
                            'os' => 'os_name',
                            'browser' => 'browser_name',
                            'device' => 'device_type',
                            'language' => 'browser_language'
                        ];

                        $result = database()->query("
                            SELECT
                                `{$columns[$type]}`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `{$columns[$type]}`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'referrer_path':

                        $referrer_host = input_clean($_GET['referrer_host']);

                        $result = database()->query("
                            SELECT
                                `referrer_path`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND `referrer_host` = '{$referrer_host}'
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `referrer_path`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'country':

                        $continent_code = isset($_GET['continent_code']) ? input_clean($_GET['continent_code']) : null;

                        $result = database()->query("
                            SELECT
                                `country_code`,
                                " . ($continent_code ? "`continent_code`," : null) . "
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                " . ($continent_code ? "AND `continent_code` = '{$continent_code}'" : null) . "
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                " . ($continent_code ? "`continent_code`," : null) . "
                                `country_code`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'city_name':

                        $country_code = isset($_GET['country_code']) ? input_clean($_GET['country_code']) : null;

                        $result = database()->query("
                            SELECT
                                `city_name`,
                                `country_code`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                " . ($country_code ? "AND `country_code` = '{$country_code}'" : null) . "
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `country_code`,
                                `city_name`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'utm_source':

                        $result = database()->query("
                            SELECT
                                `utm_source`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                                AND `utm_source` IS NOT NULL
                            GROUP BY
                                `utm_source`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'utm_medium':

                        $utm_source = input_clean($_GET['utm_source']);

                        $result = database()->query("
                            SELECT
                                `utm_medium`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND `utm_source` = '{$utm_source}'
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `utm_medium`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'utm_campaign':

                        $utm_source = input_clean($_GET['utm_source']);
                        $utm_medium = input_clean($_GET['utm_medium']);

                        $result = database()->query("
                            SELECT
                                `utm_campaign`,
                                COUNT(*) AS `total`
                            FROM
                                 `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND `utm_source` = '{$utm_source}'
                                AND `utm_medium` = '{$utm_medium}'
                                AND (`datetime` BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `utm_campaign`
                            ORDER BY
                                `total` DESC
                            
                        ");

                        break;

                    case 'hour':

                        /* Get the timezone conversion SQL */
                        $convert_tz_sql = get_convert_tz_sql('`datetime`', $this->user->timezone);

                        /* Group by HOUR after timezone adjustment */
                        $result = database()->query("
                            SELECT 
                                HOUR({$convert_tz_sql}) AS `hour`,
                                COUNT(*) AS `total`
                            FROM
                                `track_links`
                            WHERE
                                `link_id` = {$this->link->link_id}
                                AND ({$convert_tz_sql} BETWEEN '{$datetime['query_start_date']}' AND '{$datetime['query_end_date']}')
                            GROUP BY
                                `hour`
                            ORDER BY
                                `total` DESC
                        ");

                        break;
                }

                switch($type) {
                    case 'overview':

                        $statistics_keys = [
                            'continent_code',
                            'country_code',
                            'city_name',
                            'referrer_host',
                            'device_type',
                            'os_name',
                            'browser_name',
                            'browser_language'
                        ];

                        $latest = [];
                        $statistics = [];
                        foreach($statistics_keys as $key) {
                            $statistics[$key] = [];
                            $statistics[$key . '_total_sum'] = 0;
                        }

                        $has_data = ($statistics_result->num_rows ?? 0) || ($latest_result->num_rows ?? 0);

                        while($row = $statistics_result->fetch_object()) {
                            foreach($statistics_keys as $key) {
                                $row->{$key} = $row->{$key} ?? '';
                                $statistics[$key][$row->{$key}] = isset($statistics[$key][$row->{$key}]) ? $statistics[$key][$row->{$key}] + 1 : 1;

                                $statistics[$key . '_total_sum']++;
                            }
                        }

                        foreach($statistics_keys as $key) {
                            arsort($statistics[$key]);
                        }

                        while($row = $latest_result->fetch_object()) {
                            $latest_entries[] = $row;
                        }

                        /* Prepare the statistics method View */
                        $data = [
                            'statistics' => $statistics,
                            'link' => $this->link,
                            'method' => $method,
                            'datetime' => $datetime,
                            'latest' => $latest_entries,
                            'pageviews' => $pageviews,
                            'pageviews_chart' => $pageviews_chart,
                            'totals' => $totals,
                            'url' => 'link/' . $this->link->link_id,
                        ];

                        break;

                    case 'entries':

                        /* Store all the results from the database */
                        $statistics = [];

                        while($row = $result->fetch_object()) {
                            $statistics[] = $row;
                        }

                        /* Prepare the pagination view */
                        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

                        /* Prepare the statistics method View */
                        $data = [
                            'type' => $type,
                            'rows' => $statistics,
                            'link' => $this->link,
                            'method' => $method,
                            'datetime' => $datetime,
                            'pagination' => $pagination,
                            'filters' => $filters,
                            'url' => 'link/' . $this->link->link_id,
                        ];

                        $has_data = count($statistics);

                        break;

                    case 'referrer_host':
                    case 'continent_code':
                    case 'country':
                    case 'city_name':
                    case 'os':
                    case 'browser':
                    case 'device':
                    case 'language':
                    case 'referrer_path':
                    case 'utm_source':
                    case 'utm_medium':
                    case 'utm_campaign':

                        /* Store all the results from the database */
                        $statistics = [];
                        $statistics_total_sum = 0;

                        while($row = $result->fetch_object()) {
                            $statistics[] = $row;

                            $statistics_total_sum += $row->total;
                        }

                        /* Prepare the statistics method View */
                        $data = [
                            'rows' => $statistics,
                            'total_sum' => $statistics_total_sum,
                            'link' => $this->link,
                            'method' => $method,
                            'datetime' => $datetime,
                            'type' => $type,
                            'url' => 'link/' . $this->link->link_id,

                            'referrer_host' => $referrer_host ?? null,
                            'continent_code' => $continent_code ?? null,
                            'country_code' => $country_code ?? null,
                            'utm_source' => $utm_source ?? null,
                            'utm_medium' => $utm_medium ?? null,
                        ];

                        $has_data = count($statistics);

                        break;

                    case 'hour':

                        $statistics = [];
                        $statistics_total_sum = 0;

                        while($row = $result->fetch_object()) {
                            $statistics[] = $row;
                            $statistics_total_sum += $row->total;
                        }

                        $data = [
                            'rows' => $statistics,
                            'total_sum' => $statistics_total_sum,
                            'link' => $this->link,
                            'method' => $method,
                            'datetime' => $datetime,
                            'type' => $type,
                            'url' => 'link/' . $this->link->link_id,
                        ];

                        $has_data = count($statistics);

                        break;
                }

                /* Export handler */
                process_export_csv($statistics);
                process_export_json($statistics);

                $view = new \Altum\View('link/statistics/statistics_' . $type, (array) $this);
                $this->add_view_content('statistics', $view->run($data));

                /* Prepare variables for the view */
                $data = [
                    'link' => $this->link,
                    'method' => $method,
                    'type' => $type,
                    'datetime' => $datetime,
                    'has_data' => $has_data,
                    'biolink_main' => $biolink_main ?? null,
                    'is_main_biolink_app' => $is_main_biolink_app,
                    'main_biolink_statistics_url' => $main_biolink_statistics_url,
                    'app_review_quality_payload' => $app_review_quality_payload,
                    'app_review_page_url' => $app_review_page_url,
                    'app_review_is_accessible' => $app_review_is_accessible,
                    'app_review_locked_reason' => $app_review_locked_reason,
                ];

                break;

            case 'download':

                /* Static links need the / for proper asset pathing */
                if($this->link->type == 'static') {
                    $this->link->full_url .= '/';

                    $full_requested_file = \Altum\Uploads::get_path('static') . $this->link->settings->static_folder . '/';

                    \Altum\Uploads::download_files_as_zip(['' => $full_requested_file], l('global.download'));

                    die();
                }

                break;
        }

        /* Delete Modal */
        $view = new \Altum\View('links/link_delete_modal', (array) $this);
        \Altum\Event::add_content($view->run(), 'modals');

        /* Delete Modal */
        $view = new \Altum\View('biolink-block/biolink_block_delete_modal', (array) $this);
        \Altum\Event::add_content($view->run(), 'modals');

        /* Prepare the method View */
        $view = new \Altum\View('link/' . $method, (array) $this);
        $this->add_view_content('method', $view->run($data));

        /* Prepare the view */
        $data = [
            'link' => $this->link,
            'method' => $method,
            'links_types' => $links_types,
            /* Custom code */
            'vcard_main' => $vcard_main ?? null,
            'biolink_main' => $biolink_main ?? null,
            'is_main_biolink_app' => $is_main_biolink_app,
            'main_biolink_statistics_url' => $main_biolink_statistics_url,
            'app_review_quality_payload' => $app_review_quality_payload,
            'app_review_page_url' => $app_review_page_url,
            'app_review_is_accessible' => $app_review_is_accessible,
            'app_review_locked_reason' => $app_review_locked_reason,
            /* /Custom code */
        ];

        $view = new \Altum\View('link/index', (array) $this);
        $this->add_view_content('content', $view->run($data));

    }

}
