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

namespace Altum\Models;

defined('ALTUMCODE') || die();

class Funnels extends Model {

    private static $has_funnels_table = null;
    private static $has_funnel_steps_table = null;
    private static $has_funnel_contacts_table = null;
    private static $has_funnel_automations_table = null;

    public function has_funnels_table(): bool {
        if(self::$has_funnels_table !== null) {
            return self::$has_funnels_table;
        }

        try {
            $result = database()->query("SHOW TABLES LIKE 'funnels'");
            self::$has_funnels_table = $result && $result->num_rows;
        } catch(\Throwable $exception) {
            self::$has_funnels_table = false;
        }

        return self::$has_funnels_table;
    }

    public function has_funnel_steps_table(): bool {
        if(self::$has_funnel_steps_table !== null) {
            return self::$has_funnel_steps_table;
        }

        try {
            $result = database()->query("SHOW TABLES LIKE 'funnel_steps'");
            self::$has_funnel_steps_table = $result && $result->num_rows;
        } catch(\Throwable $exception) {
            self::$has_funnel_steps_table = false;
        }

        return self::$has_funnel_steps_table;
    }

    public function has_funnel_contacts_table(): bool {
        if(self::$has_funnel_contacts_table !== null) {
            return self::$has_funnel_contacts_table;
        }

        try {
            $contacts_result = database()->query("SHOW TABLES LIKE 'funnel_contacts'");
            $events_result = database()->query("SHOW TABLES LIKE 'funnel_events'");
            self::$has_funnel_contacts_table = ($contacts_result && $contacts_result->num_rows) && ($events_result && $events_result->num_rows);
        } catch(\Throwable $exception) {
            self::$has_funnel_contacts_table = false;
        }

        return self::$has_funnel_contacts_table;
    }

    public function has_funnel_automations_table(): bool {
        if(self::$has_funnel_automations_table !== null) {
            return self::$has_funnel_automations_table;
        }

        try {
            $automations_result = database()->query("SHOW TABLES LIKE 'funnel_automations'");
            self::$has_funnel_automations_table = ($automations_result && $automations_result->num_rows) && $this->has_funnel_contacts_table();
        } catch(\Throwable $exception) {
            self::$has_funnel_automations_table = false;
        }

        return self::$has_funnel_automations_table;
    }

    public function get_projects_by_user_id($user_id): array {
        if(!settings()->links->projects_is_enabled) {
            return [];
        }

        return (new \Altum\Models\Projects())->get_projects_by_user_id($user_id);
    }

    public function get_funnel_public_url(int $funnel_id): string {
        return url('funnel/' . $funnel_id);
    }

    public function get_pipeline_statuses(): array {
        return [
            'new_lead',
            'contacted',
            'follow_up',
            'presentation_booked',
            'joined',
            'customer',
        ];
    }

    public function get_funnel_automation_triggers(): array {
        return [
            'lead_created',
            'status_changed',
        ];
    }

    public function get_funnel_automation_actions(): array {
        return [
            'create_follow_up',
        ];
    }

    public function get_funnel_automation_delay_units(): array {
        return [
            'minutes',
            'hours',
            'days',
        ];
    }

    public function get_funnel_step_types(): array {
        return [
            'intro',
            'content',
            'cta',
            'social_proof',
            'booking',
            'follow_up',
        ];
    }

    public function get_available_funnel_link_resources_by_user_id(int $user_id): array {
        if(!$this->has_funnel_steps_table()) {
            return [];
        }

        $resources = [];

        $result = database()->query("SELECT `link_id`, `type`, `url`, `location_url` FROM `links` WHERE `user_id` = {$user_id} AND `is_enabled` = 1 AND `type` IN ('biolink', 'event') ORDER BY `type` ASC, `url` ASC");

        while($row = $result->fetch_object()) {
            $row->resource_label = $row->type == 'biolink'
                ? sprintf('%s: %s', l('funnels.steps_source_biolink'), $row->url)
                : sprintf('%s: %s', l('funnels.steps_source_event'), $row->url);

            $resources[$row->link_id] = $row;
        }

        return $resources;
    }

    public function get_available_funnel_splash_pages_by_user_id(int $user_id): array {
        if(!$this->has_funnel_steps_table()) {
            return [];
        }

        $resources = [];

        $result = database()->query("SELECT `splash_page_id`, `name`, `title` FROM `splash_pages` WHERE `user_id` = {$user_id} ORDER BY `name` ASC");

        while($row = $result->fetch_object()) {
            $row->resource_label = sprintf('%s: %s', l('funnels.steps_source_splash_page'), $row->name);
            $resources[$row->splash_page_id] = $row;
        }

        return $resources;
    }

    public function get_funnel_steps_by_funnel_id(int $funnel_id, int $user_id): array {
        if(!$this->has_funnel_steps_table()) {
            return [];
        }

        $funnel_steps = [];

        $result = database()->query("SELECT * FROM `funnel_steps` WHERE `funnel_id` = {$funnel_id} AND `user_id` = {$user_id} ORDER BY `order` ASC, `funnel_step_id` ASC");

        while($row = $result->fetch_object()) {
            $funnel_steps[] = $row;
        }

        return $funnel_steps;
    }

    public function get_funnel_step(int $funnel_step_id, int $funnel_id, int $user_id) {
        if(!$this->has_funnel_steps_table()) {
            return null;
        }

        return db()->where('funnel_step_id', $funnel_step_id)->where('funnel_id', $funnel_id)->where('user_id', $user_id)->getOne('funnel_steps');
    }

    public function get_next_funnel_step_order(int $funnel_id, int $user_id): int {
        if(!$this->has_funnel_steps_table()) {
            return 1;
        }

        $order = database()->query("SELECT COALESCE(MAX(`order`), 0) AS `order` FROM `funnel_steps` WHERE `funnel_id` = {$funnel_id} AND `user_id` = {$user_id}")->fetch_object()->order ?? 0;

        return (int) $order + 1;
    }

    public function normalize_funnel_steps_order(int $funnel_id, int $user_id): void {
        if(!$this->has_funnel_steps_table()) {
            return;
        }

        $result = database()->query("SELECT `funnel_step_id` FROM `funnel_steps` WHERE `funnel_id` = {$funnel_id} AND `user_id` = {$user_id} ORDER BY `order` ASC, `funnel_step_id` ASC");

        $order = 1;

        while($row = $result->fetch_object()) {
            db()->where('funnel_step_id', $row->funnel_step_id)->update('funnel_steps', [
                'order' => $order,
            ]);

            $order++;
        }
    }

    public function move_funnel_step(int $funnel_step_id, int $funnel_id, int $user_id, string $direction): bool {
        if(!$this->has_funnel_steps_table()) {
            return false;
        }

        $steps = $this->get_funnel_steps_by_funnel_id($funnel_id, $user_id);

        foreach($steps as $index => $step) {
            if((int) $step->funnel_step_id !== $funnel_step_id) {
                continue;
            }

            $swap_index = $direction === 'up' ? $index - 1 : $index + 1;

            if(!isset($steps[$swap_index])) {
                return false;
            }

            $current_order = (int) $step->order;
            $swap_order = (int) $steps[$swap_index]->order;

            db()->where('funnel_step_id', $step->funnel_step_id)->update('funnel_steps', ['order' => $swap_order]);
            db()->where('funnel_step_id', $steps[$swap_index]->funnel_step_id)->update('funnel_steps', ['order' => $current_order]);

            $this->normalize_funnel_steps_order($funnel_id, $user_id);

            return true;
        }

        return false;
    }

    public function prepare_funnel_step(object $funnel_step, array $link_resources = [], array $splash_page_resources = []): object {
        $funnel_step->source_name = null;
        $funnel_step->destination = null;

        if(in_array($funnel_step->source_type, ['biolink', 'event']) && isset($link_resources[$funnel_step->source_id])) {
            $funnel_step->source_name = $link_resources[$funnel_step->source_id]->resource_label;
            $funnel_step->destination = $funnel_step->button_url ?: url($link_resources[$funnel_step->source_id]->url);
        }

        if($funnel_step->source_type === 'splash_page' && isset($splash_page_resources[$funnel_step->source_id])) {
            $funnel_step->source_name = $splash_page_resources[$funnel_step->source_id]->resource_label;
            $funnel_step->destination = $funnel_step->button_url ?: null;
        }

        if($funnel_step->source_type === 'external_url') {
            $funnel_step->source_name = $funnel_step->button_url;
            $funnel_step->destination = $funnel_step->button_url;
        }

        if(!$funnel_step->destination && $funnel_step->button_url) {
            $funnel_step->destination = $funnel_step->button_url;
        }

        return $funnel_step;
    }

    public function get_funnels_by_user_id(int $user_id): array {
        if(!$this->has_funnels_table()) {
            return [];
        }

        $funnels = [];
        $result = database()->query("SELECT `funnel_id`, `name`, `status` FROM `funnels` WHERE `user_id` = {$user_id} ORDER BY `name` ASC");

        while($row = $result->fetch_object()) {
            $funnels[$row->funnel_id] = $row;
        }

        return $funnels;
    }

    public function get_funnel_contact_count_by_funnel_id(int $funnel_id, int $user_id): int {
        if(!$this->has_funnel_contacts_table()) {
            return 0;
        }

        return (int) database()->query("SELECT COUNT(*) AS `total` FROM `funnel_contacts` WHERE `funnel_id` = {$funnel_id} AND `user_id` = {$user_id}")->fetch_object()->total;
    }

    public function get_funnel_contacts_stats_by_funnel_id(int $funnel_id, int $user_id): array {
        if(!$this->has_funnel_contacts_table()) {
            return ['total' => 0, 'joined' => 0, 'customers' => 0];
        }

        $result = database()->query("SELECT COUNT(*) AS `total`, SUM(CASE WHEN `status` = 'joined' THEN 1 ELSE 0 END) AS `joined`, SUM(CASE WHEN `status` = 'customer' THEN 1 ELSE 0 END) AS `customers` FROM `funnel_contacts` WHERE `funnel_id` = {$funnel_id} AND `user_id` = {$user_id}")->fetch_object();

        return [
            'total' => (int) ($result->total ?? 0),
            'joined' => (int) ($result->joined ?? 0),
            'customers' => (int) ($result->customers ?? 0),
        ];
    }

    public function get_funnel_contact(int $funnel_contact_id, int $user_id) {
        if(!$this->has_funnel_contacts_table()) {
            return null;
        }

        return db()->where('funnel_contact_id', $funnel_contact_id)->where('user_id', $user_id)->getOne('funnel_contacts');
    }

    public function get_funnel_contact_events(int $funnel_contact_id, int $user_id, int $limit = 50): array {
        if(!$this->has_funnel_contacts_table()) {
            return [];
        }

        return db()->where('funnel_contact_id', $funnel_contact_id)->where('user_id', $user_id)->orderBy('funnel_event_id', 'DESC')->get('funnel_events', $limit);
    }

    public function add_funnel_event(array $data): void {
        if(!$this->has_funnel_contacts_table()) {
            return;
        }

        db()->insert('funnel_events', [
            'funnel_contact_id' => $data['funnel_contact_id'],
            'funnel_id' => $data['funnel_id'],
            'funnel_step_id' => $data['funnel_step_id'] ?? null,
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'note' => $data['note'] ?? null,
            'payload' => $data['payload'] ?? null,
            'datetime' => get_date(),
        ]);
    }

    public function get_funnel_automations_by_funnel_id(int $funnel_id, int $user_id): array {
        if(!$this->has_funnel_automations_table()) {
            return [];
        }

        $automations = [];
        $result = database()->query("SELECT * FROM `funnel_automations` WHERE `funnel_id` = {$funnel_id} AND `user_id` = {$user_id} ORDER BY `is_enabled` DESC, `funnel_automation_id` DESC");

        while($row = $result->fetch_object()) {
            $automations[] = $row;
        }

        return $automations;
    }

    public function get_funnel_automation(int $funnel_automation_id, int $funnel_id, int $user_id) {
        if(!$this->has_funnel_automations_table()) {
            return null;
        }

        return db()->where('funnel_automation_id', $funnel_automation_id)->where('funnel_id', $funnel_id)->where('user_id', $user_id)->getOne('funnel_automations');
    }

    private function render_automation_template(?string $value, array $context = []): ?string {
        if($value === null || $value === '') {
            return $value;
        }

        $replacements = [];

        foreach($context as $key => $replacement) {
            $replacements['{{' . $key . '}}'] = $replacement ?? '';
        }

        return trim(str_replace(array_keys($replacements), array_values($replacements), $value));
    }

    private function get_automation_due_datetime(int $delay_value, string $delay_unit): string {
        $delay_value = max(0, $delay_value);
        $delay_unit = in_array($delay_unit, $this->get_funnel_automation_delay_units()) ? $delay_unit : 'days';

        $date = new \DateTime(get_date());

        if($delay_value > 0) {
            $date->modify('+' . $delay_value . ' ' . $delay_unit);
        }

        return $date->format('Y-m-d H:i:s');
    }

    public function run_funnel_automations(int $funnel_id, int $user_id, int $funnel_contact_id, ?int $funnel_step_id, string $trigger_type, array $context = []): void {
        if(!$this->has_funnel_automations_table()) {
            return;
        }

        if(!in_array($trigger_type, $this->get_funnel_automation_triggers())) {
            return;
        }

        $automations = $this->get_funnel_automations_by_funnel_id($funnel_id, $user_id);

        foreach($automations as $automation) {
            if(!$automation->is_enabled || $automation->trigger_type !== $trigger_type) {
                continue;
            }

            if($trigger_type === 'status_changed' && $automation->trigger_status && ($context['to_status'] ?? null) !== $automation->trigger_status) {
                continue;
            }

            if($automation->action_type !== 'create_follow_up') {
                continue;
            }

            $due_datetime = $this->get_automation_due_datetime((int) $automation->action_delay_value, $automation->action_delay_unit);
            $title = $this->render_automation_template($automation->action_title, $context) ?: $automation->name;
            $note = $this->render_automation_template($automation->action_note, $context);

            $this->add_funnel_event([
                'funnel_contact_id' => $funnel_contact_id,
                'funnel_id' => $funnel_id,
                'funnel_step_id' => $funnel_step_id,
                'user_id' => $user_id,
                'type' => 'automation_follow_up',
                'title' => sprintf(l('funnel_contacts.event_automation_follow_up'), $title),
                'note' => $note,
                'payload' => json_encode([
                    'automation_id' => (int) $automation->funnel_automation_id,
                    'automation_name' => $automation->name,
                    'trigger_type' => $trigger_type,
                    'due_datetime' => $due_datetime,
                    'delay_value' => (int) $automation->action_delay_value,
                    'delay_unit' => $automation->action_delay_unit,
                    'to_status' => $context['to_status'] ?? null,
                ]),
            ]);
        }
    }

}