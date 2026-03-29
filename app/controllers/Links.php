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
use Altum\Models\Domain;

defined('ALTUMCODE') || die();

class Links extends Controller {

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

    private function get_main_biolink(int $user_id): ?object {
        if($user_id <= 0) {
            return null;
        }

        $result = database()->query("SELECT `links`.*, `users_biolinks`.`biolink_id`, `domains`.`scheme`, `domains`.`host`, `domains`.`link_id` AS `domain_link_id` FROM `links` LEFT JOIN `users_biolinks` ON `links`.`link_id` = `users_biolinks`.`biolink_id` LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id` WHERE `links`.`user_id` = {$user_id} AND `links`.`type` = 'biolink' AND `users_biolinks`.`biolink_id` IS NOT NULL ORDER BY `links`.`datetime` ASC, `links`.`link_id` ASC LIMIT 1");
        $biolink = $result ? $result->fetch_object() : null;

        if(!$biolink) {
            $fallback_result = database()->query("SELECT `links`.*, NULL AS `biolink_id`, `domains`.`scheme`, `domains`.`host`, `domains`.`link_id` AS `domain_link_id` FROM `links` LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id` WHERE `links`.`user_id` = {$user_id} AND `links`.`type` = 'biolink' ORDER BY `links`.`datetime` ASC, `links`.`link_id` ASC LIMIT 1");
            $biolink = $fallback_result ? $fallback_result->fetch_object() : null;
        }

        if($biolink && isset($biolink->settings) && is_string($biolink->settings)) {
            $biolink->settings = json_decode($biolink->settings);
        }

        return $biolink ?: null;
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
                ['label' => 'Pametni preporučni linkovi', 'types' => ['link_discount', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo']],
                ['label' => 'AI asistenti', 'types' => ['custom_html_chatbot', 'custom_html_chatbot_pets']],
                ['label' => 'FCC Funnel', 'types' => ['lead_funnel']],
                ['label' => 'Prikupljanje kontakata', 'types' => ['contact_collector', 'email_collector', 'phone_collector', 'appointment_calendar']],
                ['label' => 'Kontakt i spremanje kontakta', 'types' => ['link_save_contact', 'custom_html_whatsapp']],
            ]
            : [
                ['label' => 'Smart referral links', 'types' => ['link_discount', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo']],
                ['label' => 'AI assistants', 'types' => ['custom_html_chatbot', 'custom_html_chatbot_pets']],
                ['label' => 'FCC Funnel', 'types' => ['lead_funnel']],
                ['label' => 'Lead capture', 'types' => ['contact_collector', 'email_collector', 'phone_collector', 'appointment_calendar']],
                ['label' => 'Contact actions', 'types' => ['link_save_contact', 'custom_html_whatsapp']],
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

    private function get_default_public_market(object $user): string {
        $preferences = is_string($user->preferences ?? null) ? json_decode($user->preferences ?? '{}') : ($user->preferences ?? (object) []);
        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        $meta = $preferences->meta ?? (object) [];
        if(is_array($meta)) {
            $meta = (object) $meta;
        }

        $billing = is_string($user->billing ?? null) ? json_decode($user->billing ?? '{}') : ($user->billing ?? (object) []);
        if(is_array($billing)) {
            $billing = (object) $billing;
        }

        $candidates = [
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

    private function get_auto_featured_summary(array $feature_labels): string {
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

    public function index() {

        \Altum\Authentication::guard();
        $this->ensure_featured_app_columns();

        /* Custom code: FC-2026-03-19: self-heal link states after plan downgrades */
        (new \Altum\Models\User())->sync_links_with_plan($this->user->user_id);
        /* /Custom code: FC-2026-03-19 */

        $main_biolink = $this->get_main_biolink($this->user->user_id);

        if(!empty($_POST['fcc_main_biolink_featured_settings'])) {
            if(!\Altum\Csrf::check()) {
                Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            }

            if(!$main_biolink) {
                Alerts::add_error(\Altum\Language::$code === 'hr' ? 'Glavna Forever Card Aplikacija nije pronađena.' : 'The main Forever Card App could not be found.');
            }

            if(!Alerts::has_errors()) {
                $featured_opt_in = (int) isset($_POST['fcc_featured_opt_in']);
                $featured_market = input_clean($_POST['fcc_featured_public_market'] ?? '', 64);
                $featured_summary = input_clean($_POST['fcc_featured_public_summary'] ?? '', 220);

                db()->where('link_id', $main_biolink->link_id)->where('user_id', $this->user->user_id)->update('links', [
                    'fcc_featured_opt_in' => $featured_opt_in,
                    'fcc_featured_public_market' => $featured_market ?: null,
                    'fcc_featured_public_summary' => $featured_summary ?: null,
                ]);

                Alerts::add_success(\Altum\Language::$code === 'hr' ? 'Postavke javnog prikaza glavne Forever Card Aplikacije su spremljene.' : 'Public display settings for the main Forever Card App have been saved.');
            }

            redirect('links?type=biolink');
        }

        /* Check for the plan limit */
        $total_links = [];
        $total_links_result = database()->query("SELECT COUNT(`type`) AS `total`, `type` FROM `links` WHERE `user_id` = {$this->user->user_id} GROUP BY `type`");
        while($row = $total_links_result->fetch_object()) {
            if(isset($_GET['type']) && $_GET['type'] == $row->type) {
                $total_links[$row->type] = $row->total;
            }

            if(!isset($_GET['type'])) {
                $total_links[$row->type] = $row->total;
            }
        }

        /* Prepare the filtering system */
        $filters = (new \Altum\Filters(['is_enabled', 'type', 'project_id', 'domain_id', 'pixels_ids'], ['url', 'location_url'], ['link_id', 'last_datetime', 'datetime', 'clicks', 'url'], [], ['pixels_ids' => 'json_contains']));
        $filters->set_default_order_by($this->user->preferences->links_default_order_by, $this->user->preferences->default_order_type ?? settings()->main->default_order_type);
        $filters->set_default_results_per_page($this->user->preferences->default_results_per_page ?? settings()->main->default_results_per_page);

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id}  {$filters->get_sql_where()}")->fetch_object()->total ?? 0;
        $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('links?' . $filters->get_get() . '&page=%d')));

        /* Get domains */
        $domains = (new Domain())->get_available_domains_by_user($this->user);

        /* Get the links list for the project */
        /* Custom code */
        $links_result = database()->query("
            SELECT 
                *,
                users_biolinks.biolink_id
            FROM 
                `links`
            LEFT JOIN `users_biolinks` ON `links`.`link_id` = `users_biolinks`.`biolink_id`
            LEFT JOIN `users_vcards` ON `links`.`link_id` = `users_vcards`.`vcard_id`
            WHERE 
                `links`.`user_id` = {$this->user->user_id} 
                {$filters->get_sql_where()}
                {$filters->get_sql_order_by()}
            {$paginator->get_sql_limit()}
        ");
        /* /Custom code */

        /* Iterate over the links */
        $links = [];

        while($row = $links_result->fetch_object()) {
            $row->full_url = $row->domain_id && isset($domains[$row->domain_id]) ? $domains[$row->domain_id]->scheme . $domains[$row->domain_id]->host . '/' . ($domains[$row->domain_id]->link_id == $row->link_id ? null : $row->url) : SITE_URL . $row->url;
            $row->settings = json_decode($row->settings);
            $links[] = $row;
        }

        /* Export handler */
        process_export_csv($links, ['link_id', 'user_id', 'project_id', 'pixels_ids', 'type', 'url', 'location_url', 'start_date', 'end_date', 'clicks', 'is_verified', 'is_enabled', 'last_datetime', 'datetime'], sprintf(l('links.title')));
        process_export_json($links, ['link_id', 'user_id', 'project_id', 'pixels_ids', 'type', 'url', 'location_url', 'settings', 'start_date', 'end_date', 'clicks', 'is_verified', 'is_enabled', 'last_datetime', 'datetime'], sprintf(l('links.title')));

        /* Prepare the pagination view */
        $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);

        /* Create Link Modal */
        $view = new \Altum\View('links/create_link_modals', (array) $this);
        \Altum\Event::add_content($view->run(['domains' => $domains]), 'modals');

        /* Delete Modal */
        $view = new \Altum\View('links/link_delete_modal', (array) $this);
        \Altum\Event::add_content($view->run(), 'modals');

        /* Existing projects */
        $projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);

        $main_biolink_featured = null;
        if($main_biolink) {
            $main_biolink_featured = [
                'link_id' => (int) $main_biolink->link_id,
                'opt_in' => (int) ($main_biolink->fcc_featured_opt_in ?? 1),
                'is_approved' => (int) ($main_biolink->fcc_featured_is_approved ?? 1),
                'public_market' => trim((string) ($main_biolink->fcc_featured_public_market ?? '')) ?: $this->get_default_public_market($this->user),
                'public_summary' => trim((string) ($main_biolink->fcc_featured_public_summary ?? '')),
                'feature_labels' => $this->get_case_study_feature_labels((int) $main_biolink->link_id),
            ];
        }

        /* Prepare the Links Content View */
        $data = [
            'links'             => $links,
            'pagination'        => $pagination,
            'filters'           => $filters,
            'projects'          => $projects,
            'domains'           => $domains,
            'links_types'       => require APP_PATH . 'includes/links_types.php',
            'main_biolink_featured' => $main_biolink_featured,
            'main_biolink_auto_summary' => $main_biolink_featured ? $this->get_auto_featured_summary($main_biolink_featured['feature_labels']) : null,
        ];
        $view = new \Altum\View('links/links_content', (array) $this);
        $this->add_view_content('links_content', $view->run($data));

        /* Prepare the view */
        $data = [
            'total_links'=> $total_links,
        ];

        $view = new \Altum\View('links/index', (array) $this);

        $this->add_view_content('content', $view->run($data));

    }

    public function bulk() {

        \Altum\Authentication::guard();

        //ALTUMCODE:DEMO if(DEMO) Alerts::add_error('This command is blocked on the demo.');

        /* Check for any errors */
        if (empty($_POST)) {
            throw_404();
        }

        if(empty($_POST['selected'])) {
            redirect('links');
        }

        if(!isset($_POST['type'])) {
            redirect('links');
        }

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            set_time_limit(0);

            session_write_close();

            $_POST['selected'] = is_array($_POST['selected']) ? array_unique(array_map('intval', $_POST['selected'])) : [];

            switch($_POST['type']) {
                case 'delete':

                    /* Team checks */
                    if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('delete.links')) {
                        Alerts::add_error(l('global.info_message.team_no_access'));
                        redirect('links');
                    }

                    /* Custom code: FC-2026-02-24: lock main NFC biolink deletion */
                    $main_biolink = db()->where('user_id', $this->user->user_id)->getOne('users_biolinks', ['biolink_id']);
                    /* /Custom code: FC-2026-02-24 */

                    foreach($_POST['selected'] as $link_id) {
                        if($link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->getOne('links', ['link_id'])) {
                            /* Custom code: FC-2026-02-24: lock main NFC biolink deletion */
                            if($main_biolink && (int) $main_biolink->biolink_id === (int) $link->link_id) {
                                Alerts::add_error(l('link_delete_modal.error_message.main_biolink_locked'));
                                continue;
                            }
                            /* /Custom code: FC-2026-02-24 */
                            /* Delete the resource */
                            (new \Altum\Models\Link())->delete($link->link_id);
                        }
                    }

                    break;

            }

            session_start();

            /* Set a nice success message */
            Alerts::add_success(l('bulk_delete_modal.success_message'));

        }

        redirect('links');
    }

    public function reset() {
        \Altum\Authentication::guard();

        /* Team checks */
        if(\Altum\Teams::is_delegated() && !\Altum\Teams::has_access('update.links')) {
            Alerts::add_error(l('global.info_message.team_no_access'));
            redirect('links');
        }

        if (empty($_POST)) {
            throw_404();
        }

        $link_id = (int) $_POST['link_id'];

        //ALTUMCODE:DEMO if(DEMO) if($this->user->user_id == 1) Alerts::add_error('Please create an account on the demo to test out this function.');

        if(!\Altum\Csrf::check()) {
            Alerts::add_error(l('global.error_message.invalid_csrf_token'));
            redirect('links');
        }

        /* Make sure the link id is created by the logged in user */
        if(!$link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->getOne('links', ['link_id'])) {
            throw_404();
        }

        if(!Alerts::has_field_errors() && !Alerts::has_errors()) {

            /* Reset data */
            db()->where('link_id', $link_id)->update('links', [
                'clicks' => 0,
            ]);

            /* Remove data */
            db()->where('link_id', $link_id)->delete('track_links');

            /* Clear the cache */
            cache()->deleteItem('link?link_id=' . $link->link_id);
            cache()->deleteItemsByTag('link_id=' . $link->link_id);
            cache()->deleteItem('links?user_id=' . $this->user->user_id);

            /* Set a nice success message */
            Alerts::add_success(l('global.success_message.update2'));

            redirect('links');

        }

        redirect('links');
    }

}
