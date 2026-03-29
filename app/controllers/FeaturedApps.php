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

    private function ensure_featured_app_columns(): void {
        $required_columns = [
            'fcc_featured_opt_in' => "ALTER TABLE `users` ADD COLUMN `fcc_featured_opt_in` TINYINT(1) NOT NULL DEFAULT 1",
            'fcc_featured_is_approved' => "ALTER TABLE `users` ADD COLUMN `fcc_featured_is_approved` TINYINT(1) NOT NULL DEFAULT 1",
            'fcc_featured_public_market' => "ALTER TABLE `users` ADD COLUMN `fcc_featured_public_market` VARCHAR(64) NULL DEFAULT NULL",
            'fcc_featured_public_use_case' => "ALTER TABLE `users` ADD COLUMN `fcc_featured_public_use_case` VARCHAR(128) NULL DEFAULT NULL",
            'fcc_featured_public_summary' => "ALTER TABLE `users` ADD COLUMN `fcc_featured_public_summary` VARCHAR(512) NULL DEFAULT NULL",
        ];

        foreach($required_columns as $column => $query) {
            $column_result = db()->rawQuery("SHOW COLUMNS FROM `users` LIKE '{$column}'");

            if(empty($column_result)) {
                db()->rawQuery($query);
            }
        }
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
                    'types' => ['link_discount', 'link_forever_shop', 'link_forever_product', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'],
                ],
                'ai' => [
                    'label' => 'AI asistenti',
                    'types' => ['custom_html_chatbot', 'custom_html_chatbot_pets'],
                ],
                'lead_capture' => [
                    'label' => 'Prikupljanje kontakata',
                    'types' => ['lead_funnel', 'contact_collector', 'email_collector', 'phone_collector'],
                ],
                'contact' => [
                    'label' => 'Kontakt i spremanje kontakta',
                    'types' => ['link_save_contact', 'custom_html_whatsapp'],
                ],
                'presentation' => [
                    'label' => 'Predstavljanje aplikacije',
                    'types' => ['socials', 'avatar', 'heading', 'paragraph', 'image', 'image_slider', 'modal_text', 'cta'],
                ],
            ]
            : [
                'smart_links' => [
                    'label' => 'Smart referral links',
                    'types' => ['link_discount', 'link_forever_shop', 'link_forever_product', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'],
                ],
                'ai' => [
                    'label' => 'AI assistants',
                    'types' => ['custom_html_chatbot', 'custom_html_chatbot_pets'],
                ],
                'lead_capture' => [
                    'label' => 'Lead capture',
                    'types' => ['lead_funnel', 'contact_collector', 'email_collector', 'phone_collector'],
                ],
                'contact' => [
                    'label' => 'Contact actions',
                    'types' => ['link_save_contact', 'custom_html_whatsapp'],
                ],
                'presentation' => [
                    'label' => 'App presentation',
                    'types' => ['socials', 'avatar', 'heading', 'paragraph', 'image', 'image_slider', 'modal_text', 'cta'],
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

        $qualified_users_result = database()->query("SELECT `track_links`.`user_id`, `users`.`name`, `users`.`email`, `users`.`avatar`, `users`.`fcc_featured_public_market`, `users`.`fcc_featured_public_use_case`, `users`.`fcc_featured_public_summary`, COUNT(*) AS `shop_clicks` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) AND `users`.`fcc_featured_opt_in` = 1 AND `users`.`fcc_featured_is_approved` = 1 GROUP BY `track_links`.`user_id` HAVING `shop_clicks` >= {$min_qualified_clicks} ORDER BY `shop_clicks` DESC, `users`.`name` ASC");

        while($row = $qualified_users_result->fetch_object()) {
            $user_id = (int) ($row->user_id ?? 0);
            if(!$user_id) {
                continue;
            }

            /* Custom code: FC-2026-03-18: featured apps should use locked main biolink only */
            $biolink_result = database()->query("SELECT `links`.`link_id`, `links`.`url`, `links`.`domain_id`, `domains`.`scheme`, `domains`.`host`, `domains`.`link_id` AS `domain_link_id` FROM `links` LEFT JOIN `users_biolinks` ON `links`.`link_id` = `users_biolinks`.`biolink_id` LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id` WHERE `links`.`user_id` = {$user_id} AND `links`.`type` = 'biolink' AND `links`.`is_enabled` = 1 AND `users_biolinks`.`biolink_id` IS NOT NULL ORDER BY `links`.`datetime` ASC, `links`.`link_id` ASC LIMIT 1");
            /* /Custom code: FC-2026-03-18 */
            $biolink = $biolink_result ? $biolink_result->fetch_object() : null;

            if(!$biolink || empty($biolink->url)) {
                continue;
            }

            $has_custom_domain = !empty($biolink->domain_id) && !empty($biolink->host) && !empty($biolink->scheme);
            $app_url = $has_custom_domain
                ? $biolink->scheme . $biolink->host . ((int) $biolink->domain_link_id === (int) $biolink->link_id ? '' : '/' . $biolink->url)
                : SITE_URL . $biolink->url;

            $featured_apps[] = [
                'user_id' => $user_id,
                'name' => (string) ($row->name ?? l('global.unknown')),
                'email' => (string) ($row->email ?? ''),
                'avatar' => (string) ($row->avatar ?? ''),
                'app_url' => $app_url,
                'shop_clicks' => (int) ($row->shop_clicks ?? 0),
                'public_market' => trim((string) ($row->fcc_featured_public_market ?? '')),
                'public_use_case' => trim((string) ($row->fcc_featured_public_use_case ?? '')),
                'public_summary' => trim((string) ($row->fcc_featured_public_summary ?? '')),
                'feature_labels' => $this->get_case_study_feature_labels((int) $biolink->link_id),
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
