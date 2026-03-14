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

    /* Custom code: FC-2026-03-14: public featured FCC apps page */
    public function index() {

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

        $qualified_users_result = database()->query("SELECT `track_links`.`user_id`, `users`.`name`, `users`.`email`, `users`.`avatar`, COUNT(*) AS `shop_clicks` FROM `track_links` LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id` LEFT JOIN `users` ON `track_links`.`user_id` = `users`.`user_id` WHERE `track_links`.`datetime` >= '{$period_start_datetime}' AND `track_links`.`is_unique` = 1 AND `biolinks_blocks`.`type` IN ({$forever_shop_block_types_sql}) GROUP BY `track_links`.`user_id` HAVING `shop_clicks` >= {$min_qualified_clicks} ORDER BY `shop_clicks` DESC, `users`.`name` ASC");

        while($row = $qualified_users_result->fetch_object()) {
            $user_id = (int) ($row->user_id ?? 0);
            if(!$user_id) {
                continue;
            }

            $biolink_result = database()->query("SELECT `links`.`link_id`, `links`.`url`, `links`.`domain_id`, `domains`.`scheme`, `domains`.`host`, `domains`.`link_id` AS `domain_link_id` FROM `links` LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id` WHERE `links`.`user_id` = {$user_id} AND `links`.`type` = 'biolink' AND `links`.`is_enabled` = 1 ORDER BY `links`.`last_datetime` DESC, `links`.`datetime` DESC, `links`.`link_id` DESC LIMIT 1");
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
            ];
        }

        $view = new \Altum\View('featured-apps/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'featured_apps' => $featured_apps,
        ]));
    }
    /* /Custom code: FC-2026-03-14 */
}
