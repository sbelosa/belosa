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

    private function get_biolink_row_by_id(int $user_id, int $link_id, ?int $mapped_biolink_id = null): ?object {
        if($user_id <= 0 || $link_id <= 0) {
            return null;
        }

        $mapped_biolink_id_sql = $mapped_biolink_id && $mapped_biolink_id > 0 ? (int) $mapped_biolink_id : 'NULL';
        $result = database()->query("
            SELECT
                `links`.*,
                {$mapped_biolink_id_sql} AS `biolink_id`,
                `domains`.`scheme`,
                `domains`.`host`,
                `domains`.`link_id` AS `domain_link_id`
            FROM `links`
            LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id`
            WHERE `links`.`link_id` = {$link_id}
                AND `links`.`user_id` = {$user_id}
                AND `links`.`type` = 'biolink'
            LIMIT 1
        ");

        $biolink = $result ? $result->fetch_object() : null;

        if($biolink && isset($biolink->settings) && is_string($biolink->settings)) {
            $biolink->settings = json_decode($biolink->settings);
        }

        return $biolink ?: null;
    }

    private function ensure_featured_app_columns(): void {
        $required_columns = [
            'fcc_featured_opt_in' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_opt_in` TINYINT(1) NOT NULL DEFAULT 1",
            'fcc_featured_is_approved' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_is_approved` TINYINT(1) NOT NULL DEFAULT 1",
            'fcc_featured_public_market' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_public_market` VARCHAR(64) NULL DEFAULT NULL",
            'fcc_featured_public_use_case' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_public_use_case` VARCHAR(128) NULL DEFAULT NULL",
            'fcc_featured_public_summary' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_public_summary` VARCHAR(512) NULL DEFAULT NULL",
            'fcc_featured_profile_form' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_profile_form` MEDIUMTEXT NULL DEFAULT NULL",
            'fcc_featured_profile_generated' => "ALTER TABLE `links` ADD COLUMN `fcc_featured_profile_generated` MEDIUMTEXT NULL DEFAULT NULL",
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

        $mapped_biolink_id = (int) (fc_get_user_main_biolink_id($user_id) ?? 0);
        $biolink = null;

        if($mapped_biolink_id > 0) {
            $biolink = $this->get_biolink_row_by_id($user_id, $mapped_biolink_id, $mapped_biolink_id);
        }

        if(!$biolink) {
            $fallback_biolink_id_sql = $mapped_biolink_id > 0 ? $mapped_biolink_id : 'NULL';
            $fallback_result = database()->query("
                SELECT
                    `links`.*,
                    {$fallback_biolink_id_sql} AS `biolink_id`,
                    `domains`.`scheme`,
                    `domains`.`host`,
                    `domains`.`link_id` AS `domain_link_id`
                FROM `links`
                LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id`
                WHERE `links`.`user_id` = {$user_id}
                    AND `links`.`type` = 'biolink'
                ORDER BY
                    CASE WHEN `links`.`is_enabled` = 1 THEN 0 ELSE 1 END ASC,
                    `links`.`datetime` ASC,
                    `links`.`link_id` ASC
                LIMIT 1
            ");

            $biolink = $fallback_result ? $fallback_result->fetch_object() : null;
        }

        return $biolink ?: null;
    }

    private function get_stable_first_biolink(int $user_id): ?object {
        if($user_id <= 0) {
            return null;
        }

        $result = database()->query("
            SELECT
                `links`.*,
                NULL AS `biolink_id`,
                `domains`.`scheme`,
                `domains`.`host`,
                `domains`.`link_id` AS `domain_link_id`
            FROM `links`
            LEFT JOIN `domains` ON `links`.`domain_id` = `domains`.`domain_id`
            WHERE `links`.`user_id` = {$user_id}
                AND `links`.`type` = 'biolink'
            ORDER BY
                CASE WHEN `links`.`is_enabled` = 1 THEN 0 ELSE 1 END ASC,
                `links`.`datetime` ASC,
                `links`.`link_id` ASC
            LIMIT 1
        ");

        $biolink = $result ? $result->fetch_object() : null;

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

    private function get_featured_profile_form_limits(): array {
        return [
            'who_you_help' => 220,
            'what_you_help_with' => 220,
            'how_you_work' => 220,
            'background' => 220,
            'what_people_should_know' => 220,
        ];
    }

    private function get_featured_profile_form_values($source = null): array {
        $values = [];

        if(is_string($source)) {
            $decoded = json_decode($source, true);
            $source = is_array($decoded) ? $decoded : [];
        } elseif(is_object($source)) {
            $source = (array) $source;
        } elseif(!is_array($source)) {
            $source = [];
        }

        foreach($this->get_featured_profile_form_limits() as $field => $limit) {
            $values[$field] = input_clean(trim((string) ($source[$field] ?? '')), $limit);
        }

        return $values;
    }

    private function get_featured_profile_generated_payload($value): array {
        return fcc_featured_decode_json_payload($value);
    }

    private function get_featured_profile_builder_state(?object $main_biolink, array $signal_snapshot = []): array {
        $qualified_target = max(15, (int) ($signal_snapshot['qualified_target'] ?? 15));
        $growth_signal_30d = max(0, (int) ($signal_snapshot['growth_signal_30d'] ?? 0));
        $generated_payload = $this->get_featured_profile_generated_payload($main_biolink->fcc_featured_profile_generated ?? null);
        $generated_at = trim((string) ($generated_payload['generated_at'] ?? ''));
        $can_generate_now = $growth_signal_30d >= $qualified_target;
        $cooldown_days = 7;
        $next_generate_at = '';

        if($generated_at !== '') {
            try {
                $next_generate_date = new \DateTime($generated_at);
                $next_generate_date->modify('+' . $cooldown_days . ' days');
                $next_generate_at = $next_generate_date->format('Y-m-d H:i:s');

                if($next_generate_date > new \DateTime()) {
                    $can_generate_now = false;
                }
            } catch(\Throwable $exception) {
                $next_generate_at = '';
            }
        }

        return [
            'is_unlocked' => $growth_signal_30d >= $qualified_target,
            'growth_signal_30d' => $growth_signal_30d,
            'qualified_target' => $qualified_target,
            'missing_to_unlock' => max(0, $qualified_target - $growth_signal_30d),
            'cooldown_days' => $cooldown_days,
            'can_generate_now' => $can_generate_now,
            'generated_at' => $generated_at,
            'next_generate_at' => $next_generate_at,
            'generated_payload' => $generated_payload,
        ];
    }

    private function get_featured_profile_ai_credentials(): array {
        $personal_api_key = trim((string) ($this->user->preferences->openai_api_key ?? ''));
        $shared_api_key = trim((string) (settings()->aix->openai_api_key ?? settings()->main->openai_api_key ?? ''));
        $api_key = !empty($this->user->plan_settings->exclusive_personal_api_keys ?? false) ? $personal_api_key : $shared_api_key;

        return [
            'api_key' => $api_key,
            'model' => fc_get_resolved_openai_model(settings()->main->openai_model ?? ''),
            'needs_personal_key' => (bool) ($this->user->plan_settings->exclusive_personal_api_keys ?? false),
        ];
    }

    private function extract_featured_profile_json(string $content): ?array {
        $decoded = json_decode($content, true);

        if(is_array($decoded)) {
            return $decoded;
        }

        $json_start = strpos($content, '{');
        $json_end = strrpos($content, '}');

        if($json_start === false || $json_end === false || $json_end <= $json_start) {
            return null;
        }

        $json_candidate = substr($content, $json_start, $json_end - $json_start + 1);
        $decoded_candidate = json_decode($json_candidate, true);

        return is_array($decoded_candidate) ? $decoded_candidate : null;
    }

    private function sanitize_featured_profile_text(string $value, int $limit): string {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\n{3,}/u', "\n\n", $value) ?? $value;
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return input_clean($value, $limit);
    }

    private function build_featured_profile_fallback(object $main_biolink, array $form_values, array $feature_labels, array $signal_snapshot): array {
        $language = \Altum\Language::$code === 'hr' ? 'hr' : 'en';
        $name = trim((string) ($this->user->name ?? 'FCC partner'));
        $who = trim((string) ($form_values['who_you_help'] ?? ''));
        $what = trim((string) ($form_values['what_you_help_with'] ?? ''));
        $how = trim((string) ($form_values['how_you_work'] ?? ''));
        $background = trim((string) ($form_values['background'] ?? ''));
        $takeaway = trim((string) ($form_values['what_people_should_know'] ?? ''));
        $market = trim((string) ($main_biolink->fcc_featured_public_market ?? ''));
        $feature_text = !empty($feature_labels) ? implode(', ', array_slice($feature_labels, 0, 3)) : '';
        $growth_signal_30d = max(0, (int) ($signal_snapshot['growth_signal_30d'] ?? 0));

        if($language === 'hr') {
            $public_use_case = $what !== ''
                ? 'Glavna FCC aplikacija za ' . mb_strtolower($what)
                : (!empty($feature_text)
                    ? 'Glavna FCC aplikacija za ' . mb_strtolower($feature_text)
                    : 'Glavna FCC aplikacija za preporuke, kontakte i podršku novim suradnicima');

            $summary_parts = [];

            $summary_parts[] = $name . ' kroz FCC koristi glavnu aplikaciju za ' . ($what !== '' ? mb_strtolower($what) : 'jasan rad s preporukama i kontaktima') . '.';

            if($who !== '') {
                $summary_parts[] = 'Fokus je na podršci ' . mb_strtolower($who) . '.';
            }

            if($how !== '') {
                $summary_parts[] = 'Pristup radu temelji se na ' . mb_strtolower($how) . '.';
            }

            $public_summary = implode(' ', array_slice($summary_parts, 0, 3));

            $intro_parts = [];

            if($background !== '') {
                $intro_parts[] = $background;
            }

            $intro_parts[] = $name . ' koristi FCC kao praktičan sustav za preporuke, kontakte i jasne sljedeće korake.';

            if($takeaway !== '') {
                $intro_parts[] = $takeaway;
            }

            if($market !== '') {
                $intro_parts[] = 'Profil je posebno relevantan za tržište: ' . $market . '.';
            }

            if($growth_signal_30d >= 50) {
                $intro_parts[] = 'Rezultati u zadnjih 30 dana potvrđuju dosljedan rad kroz FCC i iskustvo koje može pomoći novim partnerima.';
            } else {
                $intro_parts[] = 'Aktivni signal u zadnjih 30 dana potvrđuje da je profil temeljen na stvarnom radu, a ne samo na općem opisu.';
            }

            $profile_intro = implode(' ', array_slice($intro_parts, 0, 4));
            $meta_description = $name . ' koristi FCC za ' . ($what !== '' ? mb_strtolower($what) : 'preporuke, kontakte i mentorsku podršku') . ' kroz jasan i praktičan sustav rada.';
        } else {
            $public_use_case = $what !== ''
                ? 'Main FCC app for ' . mb_strtolower($what)
                : (!empty($feature_text)
                    ? 'Main FCC app for ' . mb_strtolower($feature_text)
                    : 'Main FCC app for referrals, contacts, and support for new collaborators');

            $summary_parts = [];
            $summary_parts[] = $name . ' uses the main FCC app for ' . ($what !== '' ? mb_strtolower($what) : 'clear referral and contact workflows') . '.';

            if($who !== '') {
                $summary_parts[] = 'The focus is on helping ' . mb_strtolower($who) . '.';
            }

            if($how !== '') {
                $summary_parts[] = 'The working style is based on ' . mb_strtolower($how) . '.';
            }

            $public_summary = implode(' ', array_slice($summary_parts, 0, 3));

            $intro_parts = [];

            if($background !== '') {
                $intro_parts[] = $background;
            }

            $intro_parts[] = $name . ' uses FCC as a practical system for referrals, contacts, and clear next steps.';

            if($takeaway !== '') {
                $intro_parts[] = $takeaway;
            }

            if($market !== '') {
                $intro_parts[] = 'This profile is especially relevant for the following market: ' . $market . '.';
            }

            if($growth_signal_30d >= 50) {
                $intro_parts[] = 'The last 30 days confirm consistent work through FCC and experience that can help new partners move faster.';
            } else {
                $intro_parts[] = 'The active 30-day signal shows that this profile is based on real use of FCC, not generic promotion.';
            }

            $profile_intro = implode(' ', array_slice($intro_parts, 0, 4));
            $meta_description = $name . ' uses FCC for ' . ($what !== '' ? mb_strtolower($what) : 'referrals, contacts, and mentor support') . ' through a clear and practical workflow.';
        }

        return [
            'public_use_case' => $this->sanitize_featured_profile_text($public_use_case, 128),
            'public_summary' => $this->sanitize_featured_profile_text($public_summary, 420),
            'profile_intro' => $this->sanitize_featured_profile_text($profile_intro, 880),
            'meta_description' => $this->sanitize_featured_profile_text($meta_description, 180),
        ];
    }

    private function build_featured_profile_ai_input(object $main_biolink, array $form_values, array $feature_labels, array $signal_snapshot, array $ai_profile_values): array {
        return [
            'language' => \Altum\Language::$code === 'hr' ? 'hr' : 'en',
            'profile_goal' => 'Create an editorial FCC public profile for Featured Apps, recommended sponsors, schema, and search indexing.',
            'owner' => [
                'name' => (string) ($this->user->name ?? ''),
                'market' => trim((string) ($main_biolink->fcc_featured_public_market ?? '')),
            ],
            'main_app' => [
                'url' => (string) ($main_biolink->url ?? ''),
                'public_use_case_current' => trim((string) ($main_biolink->fcc_featured_public_use_case ?? '')),
                'public_summary_current' => trim((string) ($main_biolink->fcc_featured_public_summary ?? '')),
                'feature_labels' => array_values(array_slice($feature_labels, 0, 5)),
            ],
            'signal_snapshot' => [
                'growth_signal_30d' => (int) ($signal_snapshot['growth_signal_30d'] ?? 0),
                'growth_signal_7d' => (int) ($signal_snapshot['growth_signal_7d'] ?? 0),
                'qualified_target' => (int) ($signal_snapshot['qualified_target'] ?? 15),
                'top_target' => (int) ($signal_snapshot['top_target'] ?? 50),
            ],
            'profile_form' => $form_values,
            'leader_ai_profile' => $ai_profile_values,
        ];
    }

    private function generate_featured_profile_content(object $main_biolink, array $form_values, array $feature_labels, array $signal_snapshot, array $ai_profile_values): array {
        $fallback = $this->build_featured_profile_fallback($main_biolink, $form_values, $feature_labels, $signal_snapshot);
        $credentials = $this->get_featured_profile_ai_credentials();

        if($credentials['api_key'] === '') {
            if($credentials['needs_personal_key']) {
                throw new \Exception(sprintf(l('account_preferences.error_message.aix.openai_api_key'), '<a href="' . url('account-preferences') . '"><strong>' . l('account_preferences.menu') . '</strong></a>'));
            }

            throw new \Exception(\Altum\Language::$code === 'hr' ? 'OpenAI API ključ nije postavljen za generiranje FCC profila.' : 'The OpenAI API key is not configured for FCC profile generation.');
        }

        $language = \Altum\Language::$code === 'hr' ? 'hr' : 'en';
        $input_payload = $this->build_featured_profile_ai_input($main_biolink, $form_values, $feature_labels, $signal_snapshot, $ai_profile_values);
        $user_prompt = $language === 'hr'
            ? implode("\n\n", [
                'Vrati samo valjan JSON s ključevima: public_use_case, public_summary, profile_intro, meta_description.',
                'Tvoj zadatak je napisati urednički FCC javni profil za indeksaciju, featured-apps i recommended-sponsors stranice.',
                'Pravila:',
                '- Piši isključivo na hrvatskom.',
                '- Ton mora biti neutralan, urednički i people-first. Ovo nije reklama, oglas ni prodajni tekst.',
                '- Profil mora objasniti kako osoba koristi FCC u praksi, kome može pomoći i zašto može biti relevantna novim suradnicima.',
                '- Koristi samo ono što se može opravdati kroz input, stvarne FCC featuree i signal.',
                '- Nemoj koristiti tvrdnje poput najbolji, broj 1, vrhunski, garantira, sigurno, brzo obogaćivanje i slično.',
                '- Nemoj davati zaradne tvrdnje, medicinske tvrdnje ni hype jezik.',
                '- Nemoj koristiti emojije, uskličnike, hashtagove, navodnike ni keyword stuffing.',
                '- public_use_case neka bude kratka 1-linijska rečenica o tome kako glavna FCC aplikacija služi toj osobi u radu.',
                '- public_summary neka bude 2 do 3 rečenice i neka stane u oko 220 do 420 znakova.',
                '- profile_intro neka bude 3 do 5 rečenica za javni profil osobe. Mora zvučati jedinstveno, mirno i vjerodostojno.',
                '- meta_description neka bude prirodan SEO opis do 180 znakova.',
                '- Ako input sadrži osobne preferencije ili temu koju treba izbjeći, poštuj to.',
                '- Profil mora zvučati kao opis partnera s iskustvom u FCC sustavu, ne kao pohvala ni samopromocija.',
                'Input JSON: ' . json_encode($input_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            ])
            : implode("\n\n", [
                'Return only valid JSON with these keys: public_use_case, public_summary, profile_intro, meta_description.',
                'Your task is to write an editorial FCC public profile for indexing, featured-apps, and recommended-sponsors pages.',
                'Rules:',
                '- Write only in English.',
                '- The tone must be neutral, editorial, and people-first. This is not an ad or sales copy.',
                '- The profile must explain how the person uses FCC in practice, who they can help, and why they may be relevant to new collaborators.',
                '- Use only claims that can be supported by the input, real FCC features, and the signal snapshot.',
                '- Do not use phrases like best, number one, guaranteed, instant results, or similar hype.',
                '- Do not make income claims, medical claims, or use exaggerated language.',
                '- Do not use emojis, exclamation marks, hashtags, quotation marks, or keyword stuffing.',
                '- public_use_case should be a short one-line sentence about how the main FCC app serves this collaborator in practice.',
                '- public_summary should be 2 to 3 sentences and fit roughly within 220 to 420 characters.',
                '- profile_intro should be 3 to 5 sentences for the public profile page. It must feel unique, calm, and trustworthy.',
                '- meta_description should be a natural SEO description up to 180 characters.',
                '- Respect any personal preferences or boundaries from the input.',
                '- The profile should read like a description of a collaborator with real FCC experience, not self-promotion.',
                'Input JSON: ' . json_encode($input_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
            ]);

        try {
            \Unirest\Request::timeout(30);

            $response = \Unirest\Request::post(
                'https://api.openai.com/v1/chat/completions',
                [
                    'Authorization' => 'Bearer ' . get_random_line_from_text($credentials['api_key']),
                    'Content-Type' => 'application/json',
                ],
                \Unirest\Request\Body::json([
                    'model' => $credentials['model'],
                    'response_format' => [
                        'type' => 'json_object',
                    ],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $language === 'hr'
                                ? 'Piši isključivo na hrvatskom. Ti si urednik FCC javnih profila. Tvoj posao je napisati miran, jasan i vjerodostojan profil koji pomaže korisniku i tražilicama razumjeti kako osoba koristi FCC. Vrati samo valjan JSON bez markdowna i bez dodatnih ključeva.'
                                : 'Write only in English. You are the editor of FCC public profiles. Your task is to write calm, clear, trustworthy profile copy that helps users and search systems understand how this person uses FCC. Return only valid JSON with no markdown and no extra keys.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $user_prompt,
                        ],
                    ],
                    'max_completion_tokens' => 1200,
                ])
            );

            if($response->code >= 400) {
                throw new \Exception($response->body->error->message ?? 'OpenAI request failed.');
            }

            $content = trim((string) ($response->body->choices[0]->message->content ?? ''));

            if(substr($content, 0, 3) === '```') {
                $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
                $content = preg_replace('/\s*```$/', '', $content);
                $content = trim($content);
            }

            $decoded = $this->extract_featured_profile_json($content);
        } catch(\Throwable $exception) {
            $decoded = null;
        }

        $generated = [
            'public_use_case' => $this->sanitize_featured_profile_text((string) ($decoded['public_use_case'] ?? ''), 128),
            'public_summary' => $this->sanitize_featured_profile_text((string) ($decoded['public_summary'] ?? ''), 420),
            'profile_intro' => $this->sanitize_featured_profile_text((string) ($decoded['profile_intro'] ?? ''), 880),
            'meta_description' => $this->sanitize_featured_profile_text((string) ($decoded['meta_description'] ?? ''), 180),
        ];

        foreach($generated as $key => $value) {
            if($value === '') {
                $generated[$key] = $fallback[$key] ?? '';
            }
        }

        $generated['generated_at'] = get_date();
        $generated['model'] = !empty($decoded) ? $credentials['model'] : 'fallback_local';
        $generated['source_form'] = $form_values;
        $generated['signal_snapshot'] = [
            'growth_signal_30d' => (int) ($signal_snapshot['growth_signal_30d'] ?? 0),
            'growth_signal_7d' => (int) ($signal_snapshot['growth_signal_7d'] ?? 0),
        ];

        return $generated;
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

    private function get_app_review_contact_captures_30d(array $signals): int {
        return (int) ($signals['funnel_registrations_30d'] ?? 0) + (int) ($signals['ai_chat_leads_30d'] ?? 0);
    }

    private function calculate_app_review_weighted_signal_score(array $signals): int {
        return (int) (
            (int) ($signals['shop_contacts_30d'] ?? 0)
            + (int) ($signals['whatsapp_contacts_30d'] ?? 0)
            + (int) ($signals['product_clicks_30d'] ?? 0)
            + ($this->get_app_review_contact_captures_30d($signals) * 3)
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
            $app['ai_chat_leads_30d'] = 0;
            $app['contact_captures_30d'] = 0;
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

        $ai_chat_lead_counts = fcc_ai_get_chat_lead_counts_by_link_ids(array_keys($apps), $period_start_datetime);

        foreach($ai_chat_lead_counts as $link_id => $total) {
            if(isset($apps[$link_id])) {
                $apps[$link_id]['ai_chat_leads_30d'] += (int) $total;
            }
        }

        foreach($apps as &$app) {
            $app['contact_captures_30d'] = $this->get_app_review_contact_captures_30d($app);
            $app['weighted_signal_score'] = $this->calculate_app_review_weighted_signal_score($app);
        }
        unset($app);

        return $apps;
    }

    private function compare_app_review_signal_rows(array $a, array $b): int {
        return (($b['weighted_signal_score'] ?? 0) <=> ($a['weighted_signal_score'] ?? 0))
            ?: (($b['shop_contacts_30d'] ?? 0) <=> ($a['shop_contacts_30d'] ?? 0))
            ?: (($b['whatsapp_contacts_30d'] ?? 0) <=> ($a['whatsapp_contacts_30d'] ?? 0))
            ?: (($b['contact_captures_30d'] ?? $this->get_app_review_contact_captures_30d($b)) <=> ($a['contact_captures_30d'] ?? $this->get_app_review_contact_captures_30d($a)))
            ?: (($b['product_clicks_30d'] ?? 0) <=> ($a['product_clicks_30d'] ?? 0))
            ?: ((string) ($a['url'] ?? '') <=> (string) ($b['url'] ?? ''));
    }

    private function get_default_app_review_benchmark(): array {
        return [
            'shop_contacts_30d' => 18,
            'whatsapp_contacts_30d' => 10,
            'product_clicks_30d' => 8,
            'funnel_registrations_30d' => 4,
            'ai_chat_leads_30d' => 2,
            'contact_captures_30d' => 6,
            'weighted_signal_score' => 48,
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
            'ai_chat_leads_30d' => 0,
            'contact_captures_30d' => 0,
            'weighted_signal_score' => 0,
        ];

        foreach($top_benchmark_apps as $app) {
            $totals['shop_contacts_30d'] += (int) ($app['shop_contacts_30d'] ?? 0);
            $totals['whatsapp_contacts_30d'] += (int) ($app['whatsapp_contacts_30d'] ?? 0);
            $totals['product_clicks_30d'] += (int) ($app['product_clicks_30d'] ?? 0);
            $totals['funnel_registrations_30d'] += (int) ($app['funnel_registrations_30d'] ?? 0);
            $totals['ai_chat_leads_30d'] += (int) ($app['ai_chat_leads_30d'] ?? 0);
            $totals['contact_captures_30d'] += (int) ($app['contact_captures_30d'] ?? $this->get_app_review_contact_captures_30d($app));
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
                'ai_chat_leads_30d' => max(0, (int) round($totals['ai_chat_leads_30d'] / $count)),
                'contact_captures_30d' => max(1, (int) round($totals['contact_captures_30d'] / $count)),
                'weighted_signal_score' => max(1, (int) round($totals['weighted_signal_score'] / $count)),
            ],
            'peer_examples' => $peer_examples,
        ];
    }

    private function get_app_review_quality_payload(array $selected_app): array {
        $benchmark_payload = $this->get_app_review_benchmark_payload($selected_app);
        $benchmark = (array) ($benchmark_payload['benchmark'] ?? []);
        $performance = $selected_app;
        $performance_contact_captures = (int) ($performance['contact_captures_30d'] ?? $this->get_app_review_contact_captures_30d($performance));
        $benchmark_contact_captures = (int) ($benchmark['contact_captures_30d'] ?? $this->get_app_review_contact_captures_30d($benchmark));

        $ratios = [
            'shop_contacts_30d' => min(1.2, ((int) ($performance['shop_contacts_30d'] ?? 0)) / max(1, (int) ($benchmark['shop_contacts_30d'] ?? 1))),
            'whatsapp_contacts_30d' => min(1.2, ((int) ($performance['whatsapp_contacts_30d'] ?? 0)) / max(1, (int) ($benchmark['whatsapp_contacts_30d'] ?? 1))),
            'product_clicks_30d' => min(1.15, ((int) ($performance['product_clicks_30d'] ?? 0)) / max(1, (int) ($benchmark['product_clicks_30d'] ?? 1))),
            'contact_captures_30d' => min(1.25, $performance_contact_captures / max(1, $benchmark_contact_captures)),
        ];

        $score = (int) round(min(100,
            ($ratios['shop_contacts_30d'] * 25) +
            ($ratios['whatsapp_contacts_30d'] * 25) +
            ($ratios['product_clicks_30d'] * 20) +
            ($ratios['contact_captures_30d'] * 30)
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
                'ai_chat_leads_30d' => (int) ($performance['ai_chat_leads_30d'] ?? 0),
                'contact_captures_30d' => $performance_contact_captures,
            ],
            'peer_examples' => (array) ($benchmark_payload['peer_examples'] ?? []),
        ];
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
                Alerts::add_error(l('links.biolink_workspace.featured_profile.error.main_missing'));
            }

            if(!Alerts::has_errors()) {
                $featured_opt_in = (int) isset($_POST['fcc_featured_opt_in']);
                $featured_market = input_clean($_POST['fcc_featured_public_market'] ?? '', 64);
                $featured_use_case = input_clean($_POST['fcc_featured_public_use_case'] ?? '', 128);
                $featured_summary = input_clean($_POST['fcc_featured_public_summary'] ?? '', 420);
                $profile_form_values = $this->get_featured_profile_form_values($_POST);
                $signal_snapshot = fcc_ai_get_user_public_visibility_signal_snapshot((int) $this->user->user_id);
                $builder_state = $this->get_featured_profile_builder_state($main_biolink, $signal_snapshot);
                $generate_requested = isset($_POST['fcc_generate_featured_profile']);
                $stored_generated_payload = $this->get_featured_profile_generated_payload($main_biolink->fcc_featured_profile_generated ?? null);
                $update_data = [
                    'fcc_featured_opt_in' => $featured_opt_in,
                    'fcc_featured_public_market' => $featured_market ?: null,
                    'fcc_featured_profile_form' => json_encode($profile_form_values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                ];

                if($builder_state['is_unlocked']) {
                    $update_data['fcc_featured_public_use_case'] = $featured_use_case ?: null;
                    $update_data['fcc_featured_public_summary'] = $featured_summary ?: null;
                }

                if($generate_requested && !$builder_state['is_unlocked']) {
                    Alerts::add_error(l('links.biolink_workspace.featured_profile.error.unlock'));
                }

                if($generate_requested && $builder_state['is_unlocked'] && !$builder_state['can_generate_now']) {
                    Alerts::add_error(l('links.biolink_workspace.featured_profile.error.cooldown'));
                }

                if($generate_requested && !Alerts::has_errors()) {
                    $filled_fields = array_filter($profile_form_values, static function($value) {
                        return mb_strlen(trim((string) $value)) >= 8;
                    });

                    if(count($filled_fields) < 4) {
                        Alerts::add_error(l('links.biolink_workspace.featured_profile.error.min_fields'));
                    }
                }

                if($generate_requested && !Alerts::has_errors()) {
                    try {
                        $feature_labels = $this->get_case_study_feature_labels((int) $main_biolink->link_id);
                        $ai_profile_values = $this->get_ai_profile_values($this->user->preferences ?? null);
                        $generated_profile = $this->generate_featured_profile_content($main_biolink, $profile_form_values, $feature_labels, $signal_snapshot, $ai_profile_values);

                        $update_data['fcc_featured_public_use_case'] = $generated_profile['public_use_case'] ?: null;
                        $update_data['fcc_featured_public_summary'] = $generated_profile['public_summary'] ?: null;
                        $update_data['fcc_featured_profile_generated'] = json_encode($generated_profile, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

                        Alerts::add_success(l('links.biolink_workspace.featured_profile.success.generated'));
                    } catch(\Throwable $exception) {
                        Alerts::add_error($exception->getMessage());
                    }
                } else {
                    if(!empty($stored_generated_payload)) {
                        if($builder_state['is_unlocked']) {
                            $stored_generated_payload['public_use_case'] = $featured_use_case;
                            $stored_generated_payload['public_summary'] = $featured_summary;
                            $stored_generated_payload['manually_refined_at'] = get_date();
                            $update_data['fcc_featured_profile_generated'] = json_encode($stored_generated_payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                        }
                    }
                }

                db()->where('link_id', $main_biolink->link_id)->where('user_id', $this->user->user_id)->update('links', $update_data);
                fcc_featured_clear_public_cache((int) $this->user->user_id, (int) $main_biolink->link_id);

                if(!$generate_requested && !Alerts::has_errors()) {
                    Alerts::add_success(l('links.biolink_workspace.featured_profile.success.saved'));
                }
            }

            redirect('links?type=biolink' . ($generate_requested ? '&fcc_profile_modal=1' : ''));
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
        $filters->process();

        $is_biolink_listing = (($filters->filters['type'] ?? null) === 'biolink');

        if($is_biolink_listing && !$main_biolink) {
            $main_biolink = $this->get_stable_first_biolink($this->user->user_id);
        }

        $exclude_main_biolink_sql = ($is_biolink_listing && $main_biolink)
            ? " AND `link_id` != " . (int) $main_biolink->link_id
            : '';

        /* Prepare the paginator */
        $total_rows = database()->query("SELECT COUNT(*) AS `total` FROM `links` WHERE `user_id` = {$this->user->user_id} {$filters->get_sql_where()} {$exclude_main_biolink_sql}")->fetch_object()->total ?? 0;
        $links_sql_limit = '';
        $pagination = '';

        if(!$is_biolink_listing) {
            $paginator = (new \Altum\Paginator($total_rows, $filters->get_results_per_page(), $_GET['page'] ?? 1, url('links?' . $filters->get_get() . '&page=%d')));
            $links_sql_limit = $paginator->get_sql_limit();
            $pagination = (new \Altum\View('partials/pagination', (array) $this))->run(['paginator' => $paginator]);
        }

        /* Get domains */
        $domains = (new Domain())->get_available_domains_by_user($this->user);

        $ai_profile_values = $this->get_ai_profile_values($this->user->preferences ?? null);
        $has_ai_growth_plan_access = \Altum\Authentication::is_admin() || !empty($this->user->plan_settings->ai_growth_plan_is_enabled ?? false);
        $app_review_is_accessible = $has_ai_growth_plan_access && (\Altum\Authentication::is_admin() || $this->is_ai_profile_complete($ai_profile_values));
        $app_review_locked_reason = !$has_ai_growth_plan_access ? l('global.info_message.plan_feature_no_access') : ($app_review_is_accessible ? '' : l('ai_plan.app_review_locked_entry_tooltip'));
        $app_review_page_url = url('ai-plan?section=app_review');

        /* Get the links list for the project */
        /* Custom code */
        $main_biolink_id = (int) ($main_biolink->link_id ?? 0);
        $links_result = database()->query("
            SELECT 
                `links`.*,
                `users_vcards`.`vcard_id`
            FROM 
                `links`
            LEFT JOIN `users_vcards` ON `links`.`link_id` = `users_vcards`.`vcard_id`
            WHERE 
                `links`.`user_id` = {$this->user->user_id} 
                {$filters->get_sql_where()}
                {$exclude_main_biolink_sql}
                {$filters->get_sql_order_by()}
            {$links_sql_limit}
        ");
        /* /Custom code */

        /* Iterate over the links */
        $links = [];

        while($row = $links_result->fetch_object()) {
            $row->biolink_id = $main_biolink_id && (int) ($row->link_id ?? 0) === $main_biolink_id ? $main_biolink_id : null;
            $row->full_url = $row->domain_id && isset($domains[$row->domain_id]) ? $domains[$row->domain_id]->scheme . $domains[$row->domain_id]->host . '/' . ($domains[$row->domain_id]->link_id == $row->link_id ? null : $row->url) : SITE_URL . $row->url;
            $row->settings = json_decode($row->settings);
            $links[] = $row;
        }

        if($is_biolink_listing && !$main_biolink) {
            $main_biolink = $this->get_stable_first_biolink($this->user->user_id);

            if($main_biolink) {
                $links = array_values(array_filter($links, function($row) use ($main_biolink) {
                    return !((int) ($row->link_id ?? 0) === (int) ($main_biolink->link_id ?? 0) && ($row->type ?? '') === 'biolink');
                }));
            }
        }

        $biolink_apps = [];
        foreach($links as $row) {
            if(($row->type ?? '') !== 'biolink') {
                continue;
            }

            $biolink_apps[(int) $row->link_id] = [
                'link_id' => (int) $row->link_id,
                'user_id' => (int) $this->user->user_id,
                'url' => (string) ($row->url ?? ''),
                'public_url' => (string) ($row->full_url ?? ''),
            ];
        }

        if($is_biolink_listing && $main_biolink) {
            $main_biolink_public_url = $main_biolink->domain_id && isset($domains[$main_biolink->domain_id])
                ? $domains[$main_biolink->domain_id]->scheme . $domains[$main_biolink->domain_id]->host . '/' . ($domains[$main_biolink->domain_id]->link_id == $main_biolink->link_id ? null : $main_biolink->url)
                : SITE_URL . $main_biolink->url;

            $biolink_apps[(int) $main_biolink->link_id] = [
                'link_id' => (int) $main_biolink->link_id,
                'user_id' => (int) $this->user->user_id,
                'url' => (string) ($main_biolink->url ?? ''),
                'public_url' => (string) $main_biolink_public_url,
            ];
        }

        $biolink_apps = $this->enrich_app_review_signal_snapshots($biolink_apps, (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00'));

        foreach($links as &$row) {
            if(($row->type ?? '') !== 'biolink') {
                continue;
            }

            $link_id = (int) ($row->link_id ?? 0);
            $quality_payload = $this->get_app_review_quality_payload($biolink_apps[$link_id] ?? []);
            $row->app_review_quality_payload = $quality_payload;
            $row->app_review_page_url = $app_review_page_url . '?app_review_selected_link_id=' . $link_id;
        }
        unset($row);

        if($is_biolink_listing && !$main_biolink) {
            $resolved_main_biolink_id = (int) (fc_get_user_main_biolink_id($this->user->user_id, false) ?? 0);

            if($resolved_main_biolink_id > 0) {
                $main_biolink = $this->get_biolink_row_by_id($this->user->user_id, $resolved_main_biolink_id, $resolved_main_biolink_id);
            }

            if(!$main_biolink) {
                $main_biolink = $this->get_stable_first_biolink($this->user->user_id);
            }
        }

        $main_biolink_row = null;
        if($is_biolink_listing && $main_biolink) {
            $main_biolink_row = clone $main_biolink;
            $main_biolink_row->full_url = $main_biolink_row->domain_id && isset($domains[$main_biolink_row->domain_id])
                ? $domains[$main_biolink_row->domain_id]->scheme . $domains[$main_biolink_row->domain_id]->host . '/' . ($domains[$main_biolink_row->domain_id]->link_id == $main_biolink_row->link_id ? null : $main_biolink_row->url)
                : SITE_URL . $main_biolink_row->url;
            $main_biolink_row->settings = is_string($main_biolink_row->settings ?? null) ? json_decode($main_biolink_row->settings ?? '{}') : ($main_biolink_row->settings ?? new \stdClass());
            $main_biolink_row->app_review_quality_payload = $this->get_app_review_quality_payload($biolink_apps[(int) $main_biolink_row->link_id] ?? []);
            $main_biolink_row->app_review_page_url = $app_review_page_url . '?app_review_selected_link_id=' . (int) $main_biolink_row->link_id;
        }

        /* Export handler */
        $export_links = $links;
        if($main_biolink_row) {
            array_unshift($export_links, clone $main_biolink_row);
        }
        process_export_csv($export_links, ['link_id', 'user_id', 'project_id', 'pixels_ids', 'type', 'url', 'location_url', 'start_date', 'end_date', 'clicks', 'is_verified', 'is_enabled', 'last_datetime', 'datetime'], sprintf(l('links.title')));
        process_export_json($export_links, ['link_id', 'user_id', 'project_id', 'pixels_ids', 'type', 'url', 'location_url', 'settings', 'start_date', 'end_date', 'clicks', 'is_verified', 'is_enabled', 'last_datetime', 'datetime'], sprintf(l('links.title')));

        /* Create Link Modal */
        $view = new \Altum\View('links/create_link_modals', (array) $this);
        \Altum\Event::add_content($view->run(['domains' => $domains]), 'modals');

        /* Delete Modal */
        $view = new \Altum\View('links/link_delete_modal', (array) $this);
        \Altum\Event::add_content($view->run(), 'modals');

        /* Existing projects */
        $projects = (new \Altum\Models\Projects())->get_projects_by_user_id($this->user->user_id);

        $main_biolink_featured = null;
        $main_biolink_signal_snapshot = [];
        $main_biolink_featured_builder = [];
        if($main_biolink) {
            $main_biolink_signal_snapshot = fcc_ai_get_user_public_visibility_signal_snapshot((int) $this->user->user_id);
            $main_biolink_featured_builder = $this->get_featured_profile_builder_state($main_biolink, $main_biolink_signal_snapshot);
            $main_biolink_featured = [
                'link_id' => (int) $main_biolink->link_id,
                'opt_in' => (int) ($main_biolink->fcc_featured_opt_in ?? 1),
                'is_approved' => (int) ($main_biolink->fcc_featured_is_approved ?? 1),
                'public_market' => trim((string) ($main_biolink->fcc_featured_public_market ?? '')) ?: $this->get_default_public_market($this->user),
                'public_use_case' => trim((string) ($main_biolink->fcc_featured_public_use_case ?? '')),
                'public_summary' => trim((string) ($main_biolink->fcc_featured_public_summary ?? '')),
                'profile_form' => $this->get_featured_profile_form_values($main_biolink->fcc_featured_profile_form ?? null),
                'generated_profile' => $main_biolink_featured_builder['generated_payload'] ?? [],
                'profile_builder' => $main_biolink_featured_builder,
                'signal_snapshot' => $main_biolink_signal_snapshot,
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
            'main_biolink_row' => $main_biolink_row,
            'main_biolink_signal_snapshot' => $main_biolink_signal_snapshot,
            'main_biolink_featured_builder' => $main_biolink_featured_builder,
            'app_review_is_accessible' => $app_review_is_accessible,
            'app_review_locked_reason' => $app_review_locked_reason,
            'app_review_page_url' => $app_review_page_url,
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
                    $main_biolink_id = (int) (fc_get_user_main_biolink_id((int) $this->user->user_id) ?? 0);
                    /* /Custom code: FC-2026-02-24 */

                    foreach($_POST['selected'] as $link_id) {
                        if($link = db()->where('link_id', $link_id)->where('user_id', $this->user->user_id)->getOne('links', ['link_id'])) {
                            /* Custom code: FC-2026-02-24: lock main NFC biolink deletion */
                            if($main_biolink_id && $main_biolink_id === (int) $link->link_id) {
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
