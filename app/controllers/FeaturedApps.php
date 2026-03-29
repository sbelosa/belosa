<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

namespace Altum\Controllers;

defined('ALTUMCODE') || die();

class FeaturedApps extends Controller {

    private function resolve_display_image_for_link(?int $link_id): ?string {
        if(!$link_id) {
            return null;
        }

        $hero_block = db()->where('link_id', $link_id)
            ->where('is_enabled', 1)
            ->where('type', ['header', 'avatar', 'image'], 'IN')
            ->orderBy('`order`', 'ASC')
            ->getOne('biolinks_blocks', ['type', 'settings']);

        if(!$hero_block) {
            return null;
        }

        $hero_block->settings = json_decode($hero_block->settings ?? '');

        if($hero_block->type === 'header' && !empty($hero_block->settings->avatar)) {
            return \Altum\Uploads::get_full_url('avatars') . $hero_block->settings->avatar;
        }

        if($hero_block->type === 'avatar' && !empty($hero_block->settings->image)) {
            return \Altum\Uploads::get_full_url('avatars') . $hero_block->settings->image;
        }

        if($hero_block->type === 'image' && !empty($hero_block->settings->image)) {
            return \Altum\Uploads::get_full_url('block_images') . $hero_block->settings->image;
        }

        return null;
    }

    private function ensure_featured_app_columns(): void {
        $required_columns = [
            'fcc_featured_opt_in' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_opt_in` TINYINT(1) NOT NULL DEFAULT 1",
            'fcc_featured_is_approved' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_is_approved` TINYINT(1) NOT NULL DEFAULT 1",
            'fcc_featured_public_market' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_public_market` VARCHAR(64) NULL DEFAULT NULL",
            'fcc_featured_public_use_case' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_public_use_case` VARCHAR(128) NULL DEFAULT NULL",
            'fcc_featured_public_summary' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_public_summary` VARCHAR(512) NULL DEFAULT NULL",
        ];

        foreach($required_columns as $column => $query) {
            $column_result = db()->rawQuery("SHOW COLUMNS FROM `links` LIKE '{$column}'");

            if(empty($column_result)) {
                db()->rawQuery($query);
            }
        }
    }

    private function get_default_public_market(object $row): string {
        $preferences = is_string($row->preferences ?? null) ? json_decode($row->preferences ?? '{}') : ($row->preferences ?? (object) []);
        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        $meta = $preferences->meta ?? (object) [];
        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        $billing = is_string($row->billing ?? null) ? json_decode($row->billing ?? '{}') : ($row->billing ?? (object) []);
        if(is_array($billing)) {
            $billing = (object) $billing;
        }

        $candidates = [
            trim((string) ($row->fcc_featured_public_market ?? '')),
            trim((string) ($meta->country ?? '')),
            trim((string) ($billing->country ?? '')),
        ];

        foreach($candidates as $candidate) {
            if($candidate === '') {
                continue;
            }

            if(strlen($candidate) === 2) {
                $countries = get_countries_array();
                if(isset($countries[$candidate])) {
                    return $countries[$candidate];
                }
            }

            return $candidate;
        }

        return '';
    }

    private function get_public_summary(string $stored_summary, array $feature_labels): string {
        $stored_summary = trim($stored_summary);
        if($stored_summary !== '') {
            return $stored_summary;
        }

        $feature_labels = array_values(array_filter(array_map('trim', $feature_labels)));

        if(empty($feature_labels)) {
            return \Altum\Language::$code === 'hr'
                ? 'Glavna Forever Card Aplikacija povezuje predstavljanje, preporuke i kontakt u jednom jasnom poslovnom toku.'
                : 'The main Forever Card App connects presentation, referrals, and contact actions inside one clear business flow.';
        }

        $top_labels = array_slice($feature_labels, 0, 3);

        return \Altum\Language::$code === 'hr'
            ? 'Glavna Forever Card Aplikacija koristi ' . implode(', ', $top_labels) . ' kao dio svakodnevnog Forever poslovanja.'
            : 'The main Forever Card App uses ' . implode(', ', $top_labels) . ' as part of the everyday Forever workflow.';
    }

    private function get_case_study_feature_labels(int $link_id): array {
        $labels = [];
        $block_types_result = database()->query("SELECT `type` FROM `biolinks_blocks` WHERE `link_id` = {$link_id} AND `is_enabled` = 1");

        if(!$block_types_result) {
            return $labels;
        }

        $available_types = [];

        while($row = $block_types_result->fetch_object()) {
            $available_types[(string) $row->type] = true;
        }

        $map = \Altum\Language::$code === 'hr'
            ? [
                'smart_links' => [
                    'label' => 'Pametni preporučni linkovi',
                    'types' => ['link_discount', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'],
                ],
                'ai' => [
                    'label' => 'AI asistenti',
                    'types' => ['custom_html_chatbot', 'custom_html_chatbot_pets'],
                ],
                'funnel' => [
                    'label' => 'FCC Funnel',
                    'types' => ['lead_funnel'],
                ],
                'lead_capture' => [
                    'label' => 'Prikupljanje kontakata',
                    'types' => ['contact_collector', 'email_collector', 'phone_collector', 'appointment_calendar'],
                ],
                'contact' => [
                    'label' => 'Kontakt i spremanje kontakta',
                    'types' => ['link_save_contact', 'custom_html_whatsapp'],
                ],
            ]
            : [
                'smart_links' => [
                    'label' => 'Smart referral links',
                    'types' => ['link_discount', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'],
                ],
                'ai' => [
                    'label' => 'AI assistants',
                    'types' => ['custom_html_chatbot', 'custom_html_chatbot_pets'],
                ],
                'funnel' => [
                    'label' => 'FCC Funnel',
                    'types' => ['lead_funnel'],
                ],
                'lead_capture' => [
                    'label' => 'Lead capture',
                    'types' => ['contact_collector', 'email_collector', 'phone_collector', 'appointment_calendar'],
                ],
                'contact' => [
                    'label' => 'Contact actions',
                    'types' => ['link_save_contact', 'custom_html_whatsapp'],
                ],
            ];

        foreach($map as $group) {
            foreach($group['types'] as $type) {
                if(isset($available_types[$type])) {
                    $labels[] = $group['label'];
                    break;
                }
            }
        }

        return array_slice($labels, 0, 5);
    }

    /* Custom code: FC-2026-03-14: public featured FCC apps page */
    public function index() {
        $this->ensure_featured_app_columns();

        $min_qualified_clicks = 15;
        $period_days = 30;
        $period_start_datetime = (new \DateTime())->modify('-' . ($period_days - 1) . ' days')->format('Y-m-d 00:00:00');

        $forever_shop_block_types = [
            'link_discount',
            'link_forever_living_bih',
            'link_forever_living_alb_kosovo',
            'link_forever_living_albania_kosovo',
        ];
        $forever_shop_block_types_sql = "'" . implode("','", $forever_shop_block_types) . "'";

        $featured_apps = [];
        $seen_featured_user_ids = [];
        $seen_featured_link_ids = [];

        $qualified_apps_result = database()->query("
            SELECT
                `main_link`.`link_id`,
                `main_link`.`user_id`,
                `main_link`.`url`,
                `main_link`.`domain_id`,
                `main_link`.`fcc_featured_public_market`,
                `main_link`.`fcc_featured_public_summary`,
                `domains`.`scheme`,
                `domains`.`host`,
                `domains`.`link_id` AS `domain_link_id`,
                `users`.`name`,
                `users`.`email`,
                `users`.`avatar`,
                `users`.`preferences`,
                `users`.`billing`,
                COUNT(`track_links`.`id`) AS `shop_clicks`
            FROM `users_biolinks`
            INNER JOIN `links` AS `main_link` ON `main_link`.`link_id` = `users_biolinks`.`biolink_id`
            INNER JOIN `users` ON `users`.`user_id` = `users_biolinks`.`user_id`
            LEFT JOIN `domains` ON `main_link`.`domain_id` = `domains`.`domain_id`
            LEFT JOIN `links` AS `all_biolinks` ON `all_biolinks`.`user_id` = `users_biolinks`.`user_id` AND `all_biolinks`.`type` = 'biolink' AND `all_biolinks`.`is_enabled` = 1
            LEFT JOIN `biolinks_blocks` ON `biolinks_blocks`.`link_id` = `all_biolinks`.`link_id` AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql})
            LEFT JOIN `track_links` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` AND `track_links`.`datetime` >= '{$period_start_datetime}' AND `track_links`.`is_unique` = 1
            WHERE `main_link`.`type` = 'biolink' AND `main_link`.`is_enabled` = 1 AND `main_link`.`fcc_featured_opt_in` = 1 AND `main_link`.`fcc_featured_is_approved` = 1
            GROUP BY
                `main_link`.`link_id`,
                `main_link`.`user_id`,
                `main_link`.`url`,
                `main_link`.`domain_id`,
                `main_link`.`fcc_featured_public_market`,
                `main_link`.`fcc_featured_public_summary`,
                `domains`.`scheme`,
                `domains`.`host`,
                `domains`.`link_id`,
                `users`.`name`,
                `users`.`email`,
                `users`.`avatar`,
                `users`.`preferences`,
                `users`.`billing`
            HAVING `shop_clicks` >= {$min_qualified_clicks}
            ORDER BY `shop_clicks` DESC, `users`.`name` ASC
        ");

        while($row = $qualified_apps_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);
            $user_id = (int) ($row->user_id ?? 0);

            if(
                !$link_id
                || !$user_id
                || empty($row->url)
                || isset($seen_featured_link_ids[$link_id])
                || isset($seen_featured_user_ids[$user_id])
            ) {
                continue;
            }

            $seen_featured_link_ids[$link_id] = true;
            $seen_featured_user_ids[$user_id] = true;

            $has_custom_domain = !empty($row->domain_id) && !empty($row->host) && !empty($row->scheme);
            $app_url = $has_custom_domain
                ? $row->scheme . $row->host . ((int) $row->domain_link_id === $link_id ? '' : '/' . $row->url)
                : SITE_URL . $row->url;

            $feature_labels = $this->get_case_study_feature_labels($link_id);

            $featured_apps[] = [
                'user_id' => $user_id,
                'name' => (string) ($row->name ?? l('global.unknown')),
                'email' => (string) ($row->email ?? ''),
                'avatar' => (string) ($row->avatar ?? ''),
                'display_image_url' => $this->resolve_display_image_for_link($link_id),
                'default_image_url' => SITE_URL . 'uploads/logo/forever.png',
                'generated_avatar_url' => get_user_avatar(null, (string) ($row->email ?? ($row->name ?? ''))),
                'app_url' => $app_url,
                'shop_clicks' => (int) ($row->shop_clicks ?? 0),
                'public_market' => $this->get_default_public_market($row),
                'public_summary' => $this->get_public_summary((string) ($row->fcc_featured_public_summary ?? ''), $feature_labels),
                'feature_labels' => $feature_labels,
            ];
        }

        $view = new \Altum\View('featured-apps/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'featured_apps' => $featured_apps,
            'min_qualified_clicks' => $min_qualified_clicks,
            'period_days' => $period_days,
        ]));
    }
    /* /Custom code: FC-2026-03-14 */
}
