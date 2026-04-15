<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Unirest\Request;
use Unirest\Request\Body;

defined('ALTUMCODE') || die();

/* Custom code: FC-2026-03-31: User AI plan phase 1-3 controller */
class AiPlan extends Controller {

    private function normalize_ai_need_value(?string $value): string {
        $value = input_clean($value ?? '', 64);

        if($value === 'follow_up_script') {
            return 'coaching_ideas';
        }

        return $value;
    }

    private function get_weekly_form_options(): array {
        return [
            'weekly_priority' => ['sales_push', 'recruitment_push', 'content_consistency', 'funnel_build', 'follow_up', 'clarity'],
            'content_commitment' => ['stories_daily', 'reels_three', 'posts_three', 'live_or_offline', 'dm_follow_up', 'mixed_light'],
            'follow_up_volume' => ['contacts_0', 'contacts_3', 'contacts_5', 'contacts_10_plus'],
            'ai_need' => ['content_plan', 'channel_direction', 'offer_direction', 'funnel_direction', 'coaching_ideas', 'clarity_plan'],
            'weekly_energy' => ['low', 'medium', 'high'],
        ];
    }

    private function get_preferences_object($preferences = null) {
        if($preferences === null) {
            $preferences = $this->user->preferences ?? new \stdClass();
        }

        if(is_string($preferences)) {
            $preferences = json_decode($preferences ?? '{}');
        }

        if(is_array($preferences)) {
            $preferences = (object) $preferences;
        }

        if(!$preferences instanceof \stdClass) {
            $preferences = (object) $preferences;
        }

        return $preferences;
    }

    private function normalize_json_to_array($value): array {
        if(is_string($value)) {
            $value = json_decode($value, true);
        } elseif(is_object($value)) {
            $value = json_decode(json_encode($value), true);
        }

        return is_array($value) ? $value : [];
    }

    private function get_form_options(): array {
        return [
            'primary_goal' => ['product_sales', 'recruitment', 'brand_building', 'customer_activation', 'testing_new_angle'],
            'priority_offer' => ['single_product', 'product_category', 'business_opportunity', 'personal_brand', 'mixed_offer'],
            'active_channels' => ['instagram_story', 'instagram_reel', 'facebook_profile', 'facebook_group', 'tiktok', 'whatsapp', 'messenger', 'email', 'offline'],
            'available_time' => ['15m_daily', '30m_daily', '60m_daily', 'three_posts_weekly', 'story_only', 'follow_up_only'],
            'biggest_blocker' => ['no_traffic', 'no_clicks', 'no_leads', 'no_sales', 'no_content_ideas', 'follow_up_unclear', 'limited_time', 'low_confidence'],
            'communication_style' => ['educational', 'personal_story', 'testimonial', 'direct_sales', 'soft_brand', 'recruitment_focus'],
            'follow_up_readiness' => ['dm_ready', 'whatsapp_ready', 'email_ready', 'inbound_only', 'no_follow_up'],
            'weekly_change' => ['new_product', 'new_focus', 'new_audience', 'less_time', 'more_time', 'channel_shift', 'no_change'],
        ];
    }

    private function get_default_values(): array {
        return [
            'primary_goal' => '',
            'priority_offer' => '',
            'active_channels' => [],
            'available_time' => '',
            'biggest_blocker' => '',
            'communication_style' => '',
            'follow_up_readiness' => '',
            'weekly_change' => '',
            'audience_focus' => '',
            'product_focus' => '',
            'visual_tone_preference' => '',
            'notes' => '',
            'updated_at' => null,
        ];
    }

    private function get_default_weekly_values(): array {
        return [
            'weekly_priority' => '',
            'content_commitment' => '',
            'follow_up_volume' => '',
            'ai_need' => '',
            'weekly_energy' => '',
            'weekly_context' => '',
            'adaptive_answer' => '',
            'adaptive_question_key' => '',
            'submitted_at' => null,
        ];
    }

    private function is_active_pro_user(): bool {
        return fcc_ai_user_has_active_growth_pro($this->user);
    }

    private function get_ai_growth_access_settings($preferences): array {
        $access = $preferences->leader_ai_access ?? null;

        if(is_array($access)) {
            $access = (object) $access;
        }

        if(!$access instanceof \stdClass) {
            $access = new \stdClass();
        }

        $has_existing_app_review = !empty($preferences->leader_ai_app_reviews) && is_array($preferences->leader_ai_app_reviews) && !empty($preferences->leader_ai_app_reviews[0]);
        $has_existing_weekly_plan = !empty($preferences->leader_ai_weekly_plans) && is_array($preferences->leader_ai_weekly_plans) && !empty($preferences->leader_ai_weekly_plans[0]);

        return [
            'starter_app_review_used' => min(1, max(0, (int) ($access->starter_app_review_used ?? ($has_existing_app_review ? 1 : 0)))),
            'starter_weekly_plan_used' => min(1, max(0, (int) ($access->starter_weekly_plan_used ?? ($has_existing_weekly_plan ? 1 : 0)))),
            'manual_tier' => (string) ($access->manual_tier ?? ''),
            'manual_note' => (string) ($access->manual_note ?? ''),
            'manual_unlocked_at' => $access->manual_unlocked_at ?? null,
        ];
    }

    private function set_ai_growth_access_settings(\stdClass $preferences, array $settings): \stdClass {
        $preferences->leader_ai_access = (object) [
            'starter_app_review_used' => min(1, max(0, (int) ($settings['starter_app_review_used'] ?? 0))),
            'starter_weekly_plan_used' => min(1, max(0, (int) ($settings['starter_weekly_plan_used'] ?? 0))),
            'manual_tier' => (string) ($settings['manual_tier'] ?? ''),
            'manual_note' => (string) ($settings['manual_note'] ?? ''),
            'manual_unlocked_at' => $settings['manual_unlocked_at'] ?? null,
        ];

        return $preferences;
    }

    private function get_active_manual_ai_tier(array $access_settings): string {
        return fcc_ai_get_active_manual_ai_tier((object) [
            'manual_tier' => (string) ($access_settings['manual_tier'] ?? ''),
            'manual_unlocked_at' => (string) ($access_settings['manual_unlocked_at'] ?? ''),
        ]);
    }

    private function consume_ai_growth_starter_credit(\stdClass $preferences, string $credit_key): \stdClass {
        $settings = $this->get_ai_growth_access_settings($preferences);

        if($credit_key === 'app_review') {
            $settings['starter_app_review_used'] = 1;
        }

        if($credit_key === 'weekly_plan') {
            $settings['starter_weekly_plan_used'] = 1;
        }

        return $this->set_ai_growth_access_settings($preferences, $settings);
    }

    private function get_saved_values($preferences): array {
        $values = $this->get_default_values();
        $profile = $preferences->leader_ai_profile ?? null;

        if(is_array($profile)) {
            $profile = (object) $profile;
        }

        if(!$profile) {
            return $values;
        }

        $values['primary_goal'] = (string) ($profile->primary_goal ?? '');
        $values['priority_offer'] = (string) ($profile->priority_offer ?? '');
        $values['active_channels'] = is_array($profile->active_channels ?? null) ? array_values($profile->active_channels) : [];
        $values['available_time'] = (string) ($profile->available_time ?? '');
        $values['biggest_blocker'] = (string) ($profile->biggest_blocker ?? '');
        $values['communication_style'] = (string) ($profile->communication_style ?? '');
        $values['follow_up_readiness'] = (string) ($profile->follow_up_readiness ?? '');
        $values['weekly_change'] = (string) ($profile->weekly_change ?? '');
        $values['audience_focus'] = (string) ($profile->audience_focus ?? '');
        $values['product_focus'] = (string) ($profile->product_focus ?? '');
        $values['visual_tone_preference'] = (string) ($profile->visual_tone_preference ?? '');
        $values['notes'] = (string) ($profile->notes ?? '');
        $values['updated_at'] = $profile->updated_at ?? null;

        return $values;
    }

    private function get_saved_weekly_checkins($preferences): array {
        $checkins = $preferences->leader_ai_weekly_checkins ?? [];

        if(is_object($checkins)) {
            $checkins = (array) $checkins;
        }

        if(!is_array($checkins)) {
            return [];
        }

        $normalized = [];

        foreach($checkins as $checkin) {
            if(is_object($checkin)) {
                $checkin = (array) $checkin;
            }

            if(!is_array($checkin)) {
                continue;
            }

            $normalized[] = [
                'weekly_priority' => (string) ($checkin['weekly_priority'] ?? ''),
                'content_commitment' => (string) ($checkin['content_commitment'] ?? ''),
                'follow_up_volume' => (string) ($checkin['follow_up_volume'] ?? ''),
                'ai_need' => $this->normalize_ai_need_value((string) ($checkin['ai_need'] ?? '')),
                'weekly_energy' => (string) ($checkin['weekly_energy'] ?? ''),
                'weekly_context' => (string) ($checkin['weekly_context'] ?? ''),
                'adaptive_answer' => (string) ($checkin['adaptive_answer'] ?? ''),
                'adaptive_question_key' => (string) ($checkin['adaptive_question_key'] ?? ''),
                'submitted_at' => $checkin['submitted_at'] ?? null,
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_latest_weekly_checkin(array $weekly_checkins): ?array {
        return $weekly_checkins[0] ?? null;
    }

    private function get_saved_weekly_plans($preferences): array {
        $plans = $preferences->leader_ai_weekly_plans ?? [];

        if(is_object($plans)) {
            $plans = (array) $plans;
        }

        if(!is_array($plans)) {
            return [];
        }

        $normalized = [];

        foreach($plans as $plan) {
            if(is_object($plan)) {
                $plan = (array) $plan;
            }

            if(!is_array($plan)) {
                continue;
            }

            $daily_plan = [];
            foreach(($plan['daily_plan'] ?? []) as $day_plan) {
                if(is_object($day_plan)) {
                    $day_plan = (array) $day_plan;
                }

                if(!is_array($day_plan)) {
                    continue;
                }

                $tasks = [];
                foreach(($day_plan['tasks'] ?? []) as $task) {
                    if(!is_scalar($task)) {
                        continue;
                    }

                    $task = trim((string) $task);
                    if($task !== '') {
                        $tasks[] = $task;
                    }
                }

                $daily_plan[] = [
                    'day' => (string) ($day_plan['day'] ?? ''),
                    'title' => (string) ($day_plan['title'] ?? ''),
                    'tasks' => array_slice($tasks, 0, 4),
                ];
            }

            $normalized[] = [
                'checkin_submitted_at' => $plan['checkin_submitted_at'] ?? null,
                'generated_at' => $plan['generated_at'] ?? null,
                'model' => (string) ($plan['model'] ?? ''),
                'headline' => (string) ($plan['headline'] ?? ''),
                'summary' => (string) ($plan['summary'] ?? ''),
                'focus' => (string) ($plan['focus'] ?? ''),
                'coach_intro' => (string) ($plan['coach_intro'] ?? ''),
                'brutal_truth' => (string) ($plan['brutal_truth'] ?? ''),
                'power_move' => (string) ($plan['power_move'] ?? ''),
                'why_this_week' => (string) ($plan['why_this_week'] ?? ''),
                'encouragement' => (string) ($plan['encouragement'] ?? ''),
                'priority_channels' => array_values(array_filter((array) ($plan['priority_channels'] ?? []), 'is_scalar')),
                'content_ideas' => array_values(array_filter((array) ($plan['content_ideas'] ?? []), 'is_scalar')),
                'coach_ideas' => array_values(array_filter((array) ($plan['coach_ideas'] ?? (!empty($plan['follow_up_script']) ? [$plan['follow_up_script']] : [])), 'is_scalar')),
                'do_not_do' => array_values(array_filter((array) ($plan['do_not_do'] ?? []), 'is_scalar')),
                'daily_plan' => $daily_plan,
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['checkin_submitted_at'] ?? $b['generated_at'] ?? ''), (string) ($a['checkin_submitted_at'] ?? $a['generated_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_latest_weekly_plan(array $weekly_plans, ?array $latest_weekly_checkin): ?array {
        if(!$latest_weekly_checkin) {
            return $weekly_plans[0] ?? null;
        }

        $submitted_at = (string) ($latest_weekly_checkin['submitted_at'] ?? '');

        foreach($weekly_plans as $plan) {
            if((string) ($plan['checkin_submitted_at'] ?? '') === $submitted_at) {
                return $plan;
            }
        }

        return null;
    }

    private function get_weekly_plan_by_generated_at(array $weekly_plans, string $generated_at): ?array {
        if($generated_at === '') {
            return null;
        }

        foreach($weekly_plans as $weekly_plan) {
            if((string) ($weekly_plan['generated_at'] ?? '') === $generated_at) {
                return $weekly_plan;
            }
        }

        return null;
    }

    private function get_saved_weekly_outcomes($preferences): array {
        $outcomes = $preferences->leader_ai_weekly_outcomes ?? [];

        if(is_object($outcomes)) {
            $outcomes = (array) $outcomes;
        }

        if(!is_array($outcomes)) {
            return [];
        }

        $normalized = [];

        foreach($outcomes as $outcome) {
            if(is_object($outcome)) {
                $outcome = (array) $outcome;
            }

            if(!is_array($outcome)) {
                continue;
            }

            $normalized[] = [
                'checkin_submitted_at' => $outcome['checkin_submitted_at'] ?? null,
                'plan_generated_at' => $outcome['plan_generated_at'] ?? null,
                'selected_link_id' => max(0, (int) ($outcome['selected_link_id'] ?? 0)),
                'app_review_generated_at' => $outcome['app_review_generated_at'] ?? null,
                'app_review_review_key' => (string) ($outcome['app_review_review_key'] ?? ($outcome['app_review_generated_at'] ?? '')),
                'completion_level' => (string) ($outcome['completion_level'] ?? ''),
                'best_response' => (string) ($outcome['best_response'] ?? ''),
                'main_blocker_now' => (string) ($outcome['main_blocker_now'] ?? ''),
                'biggest_lesson' => (string) ($outcome['biggest_lesson'] ?? ''),
                'next_adjustment' => (string) ($outcome['next_adjustment'] ?? ''),
                'palette_feedback' => $this->normalize_palette_feedback_choice($outcome['palette_feedback'] ?? ''),
                'palette_feedback_note' => (string) ($outcome['palette_feedback_note'] ?? ''),
                'palette_decision' => $this->get_palette_feedback_decision($outcome['palette_feedback'] ?? ''),
                'submitted_at' => $outcome['submitted_at'] ?? null,
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['checkin_submitted_at'] ?? $b['submitted_at'] ?? ''), (string) ($a['checkin_submitted_at'] ?? $a['submitted_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_latest_weekly_outcome(array $weekly_outcomes, ?array $latest_weekly_checkin): ?array {
        if(!$latest_weekly_checkin) {
            return $weekly_outcomes[0] ?? null;
        }

        $submitted_at = (string) ($latest_weekly_checkin['submitted_at'] ?? '');

        foreach($weekly_outcomes as $weekly_outcome) {
            if((string) ($weekly_outcome['checkin_submitted_at'] ?? '') === $submitted_at) {
                return $weekly_outcome;
            }
        }

        return null;
    }

    private function get_weekly_outcome_for_plan(array $weekly_outcomes, ?array $weekly_plan): ?array {
        if(!$weekly_plan) {
            return null;
        }

        $submitted_at = (string) ($weekly_plan['checkin_submitted_at'] ?? '');
        if($submitted_at !== '') {
            $outcome = $this->get_weekly_outcome_by_checkin($weekly_outcomes, $submitted_at);
            if($outcome) {
                return $outcome;
            }
        }

        $generated_at = (string) ($weekly_plan['generated_at'] ?? '');
        if($generated_at === '') {
            return null;
        }

        foreach($weekly_outcomes as $weekly_outcome) {
            if((string) ($weekly_outcome['plan_generated_at'] ?? '') === $generated_at) {
                return $weekly_outcome;
            }
        }

        return null;
    }

    private function get_latest_plan_missing_outcome(array $weekly_plans, array $weekly_outcomes): ?array {
        foreach($weekly_plans as $weekly_plan) {
            if(!$this->get_weekly_outcome_for_plan($weekly_outcomes, $weekly_plan)) {
                return $weekly_plan;
            }
        }

        return null;
    }

    private function get_completion_level_label(?string $completion_level): string {
        $completion_level = (string) ($completion_level ?? '');

        return $completion_level !== '' ? l('ai_plan.option.completion_level.' . $completion_level) : '';
    }

    private function get_weekly_plan_by_checkin(array $weekly_plans, string $submitted_at): ?array {
        foreach($weekly_plans as $weekly_plan) {
            if((string) ($weekly_plan['checkin_submitted_at'] ?? '') === $submitted_at) {
                return $weekly_plan;
            }
        }

        return null;
    }

    private function get_weekly_outcome_by_checkin(array $weekly_outcomes, string $submitted_at): ?array {
        foreach($weekly_outcomes as $weekly_outcome) {
            if((string) ($weekly_outcome['checkin_submitted_at'] ?? '') === $submitted_at) {
                return $weekly_outcome;
            }
        }

        return null;
    }

    private function get_previous_weekly_cycle_context(array $weekly_checkins, array $weekly_plans, array $weekly_outcomes, ?array $current_weekly_checkin): ?array {
        if(!$current_weekly_checkin) {
            return null;
        }

        $current_submitted_at = (string) ($current_weekly_checkin['submitted_at'] ?? '');
        $previous_weekly_checkin = null;

        foreach($weekly_checkins as $index => $weekly_checkin) {
            if((string) ($weekly_checkin['submitted_at'] ?? '') !== $current_submitted_at) {
                continue;
            }

            $previous_weekly_checkin = $weekly_checkins[$index + 1] ?? null;
            break;
        }

        if(!$previous_weekly_checkin) {
            return null;
        }

        $previous_submitted_at = (string) ($previous_weekly_checkin['submitted_at'] ?? '');

        if($previous_submitted_at === '') {
            return null;
        }

        return [
            'checkin' => $previous_weekly_checkin,
            'plan' => $this->get_weekly_plan_by_checkin($weekly_plans, $previous_submitted_at),
            'outcome' => $this->get_weekly_outcome_by_checkin($weekly_outcomes, $previous_submitted_at),
        ];
    }

    private function get_feedback_loop_payload(?array $previous_cycle_context): array {
        $outcome = $previous_cycle_context['outcome'] ?? null;
        $plan = $previous_cycle_context['plan'] ?? null;

        if(!$outcome || !is_array($outcome)) {
            return [
                'has_feedback' => false,
            ];
        }

        return [
            'has_feedback' => true,
            'previous_focus' => (string) ($plan['focus'] ?? $plan['headline'] ?? ''),
            'completion_level' => $this->get_completion_level_label($outcome['completion_level'] ?? ''),
            'best_response' => (string) ($outcome['best_response'] ?? ''),
            'main_blocker_now' => (string) ($outcome['main_blocker_now'] ?? ''),
            'next_adjustment' => (string) ($outcome['next_adjustment'] ?? ''),
            'palette_feedback' => $this->get_palette_feedback_label($outcome['palette_feedback'] ?? ''),
            'palette_feedback_note' => (string) ($outcome['palette_feedback_note'] ?? ''),
            'palette_decision' => (string) ($outcome['palette_decision'] ?? $this->get_palette_feedback_decision($outcome['palette_feedback'] ?? '')),
        ];
    }

    private function normalize_palette_feedback_choice($value): string {
        $value = trim((string) $value);

        return in_array($value, ['love_keep', 'good_refine', 'new_direction', 'not_applied'], true) ? $value : '';
    }

    private function get_palette_feedback_label(?string $value): string {
        $value = $this->normalize_palette_feedback_choice($value);

        return $value !== '' ? l('ai_plan.option.palette_feedback.' . $value) : '';
    }

    private function get_palette_feedback_decision(?string $value): string {
        return match($this->normalize_palette_feedback_choice($value)) {
            'love_keep' => 'keep',
            'good_refine' => 'refine',
            'new_direction' => 'replace',
            'not_applied' => 'hold',
            default => '',
        };
    }

    private function get_latest_palette_feedback_for_app(array $weekly_outcomes, int $selected_link_id = 0, string $app_review_review_key = '', string $app_review_generated_at = ''): ?array {
        $selected_link_id = max(0, $selected_link_id);
        $app_review_review_key = trim($app_review_review_key);
        $app_review_generated_at = trim($app_review_generated_at);

        foreach($weekly_outcomes as $weekly_outcome) {
            if(!is_array($weekly_outcome)) {
                continue;
            }

            $palette_feedback = $this->normalize_palette_feedback_choice($weekly_outcome['palette_feedback'] ?? '');

            if($palette_feedback === '') {
                continue;
            }

            if(
                $app_review_review_key !== ''
                && (string) ($weekly_outcome['app_review_review_key'] ?? '') === $app_review_review_key
            ) {
                return $weekly_outcome;
            }

            if(
                $app_review_generated_at !== ''
                && (string) ($weekly_outcome['app_review_generated_at'] ?? '') === $app_review_generated_at
            ) {
                return $weekly_outcome;
            }

            if(
                $selected_link_id > 0
                && (int) ($weekly_outcome['selected_link_id'] ?? 0) === $selected_link_id
            ) {
                return $weekly_outcome;
            }
        }

        return null;
    }

    private function get_mentor_ai_guidance($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $coaching = $preferences->leader_ai_admin_coaching ?? null;

        if(is_array($coaching)) {
            $coaching = (object) $coaching;
        }

        if(!$coaching instanceof \stdClass) {
            return [
                'has_guidance' => false,
                'guidance' => '',
            ];
        }

        $guidance = trim((string) ($coaching->ai_guidance ?? ''));

        return [
            'has_guidance' => $guidance !== '',
            'guidance' => $guidance,
        ];
    }

    private function get_internal_coach_context_payload(int $user_id): array {
        $payload = [
            'has_recent_activity' => false,
            'last_touch_at' => null,
            'conversation_count' => 0,
            'recent_topics' => [],
            'recent_challenges' => [],
            'recent_user_messages' => [],
            'recent_assistant_guidance' => [],
            'summary' => '',
        ];

        if($user_id <= 0) {
            return $payload;
        }

        fcc_ai_ensure_tables();

        $coach_conversation_total = (int) db()
            ->where('user_id', $user_id)
            ->where('assistant_type', 'coach')
            ->where('scope', 'internal_coach')
            ->getValue('fcc_ai_conversations', 'COUNT(*)');

        $payload['conversation_count'] = $coach_conversation_total;

        $insights_result = database()->query("
            SELECT
                `primary_topic_label`,
                `summary`,
                `core_issue`,
                COALESCE(`last_datetime`, `datetime`) AS `activity_at`
            FROM `fcc_ai_conversation_insights`
            WHERE `user_id` = {$user_id}
              AND COALESCE(`assistant_type`, '') = 'coach'
              AND COALESCE(`scope`, '') = 'internal_coach'
            ORDER BY COALESCE(`last_datetime`, `datetime`) DESC
            LIMIT 4
        ");

        if($insights_result) {
            while($row = $insights_result->fetch_object()) {
                if(empty($payload['last_touch_at']) && !empty($row->activity_at)) {
                    $payload['last_touch_at'] = (string) $row->activity_at;
                }

                $topic_label = trim((string) ($row->primary_topic_label ?? ''));
                if($topic_label !== '' && !in_array($topic_label, $payload['recent_topics'], true)) {
                    $payload['recent_topics'][] = $topic_label;
                }

                $core_issue = trim((string) ($row->core_issue ?? ''));
                if($core_issue !== '' && !in_array($core_issue, $payload['recent_challenges'], true)) {
                    $payload['recent_challenges'][] = fcc_ai_excerpt($core_issue, 180);
                }
            }
        }

        $messages_result = database()->query("
            SELECT
                `fcc_ai_messages`.`role`,
                `fcc_ai_messages`.`content`
            FROM `fcc_ai_messages`
            INNER JOIN `fcc_ai_conversations`
                ON `fcc_ai_conversations`.`fcc_ai_conversation_id` = `fcc_ai_messages`.`fcc_ai_conversation_id`
            WHERE `fcc_ai_conversations`.`user_id` = {$user_id}
              AND COALESCE(`fcc_ai_conversations`.`assistant_type`, '') = 'coach'
              AND COALESCE(`fcc_ai_conversations`.`scope`, '') = 'internal_coach'
              AND COALESCE(`fcc_ai_messages`.`message_type`, 'chat') = 'chat'
              AND `fcc_ai_messages`.`role` IN ('user', 'assistant')
            ORDER BY `fcc_ai_messages`.`fcc_ai_message_id` DESC
            LIMIT 12
        ");

        if($messages_result) {
            while($row = $messages_result->fetch_object()) {
                $content = trim((string) ($row->content ?? ''));
                $role = trim((string) ($row->role ?? ''));

                if($content === '' || !in_array($role, ['user', 'assistant'], true)) {
                    continue;
                }

                $excerpt = fcc_ai_excerpt($content, 180);

                if($role === 'user' && !in_array($excerpt, $payload['recent_user_messages'], true)) {
                    $payload['recent_user_messages'][] = $excerpt;
                }

                if($role === 'assistant' && !in_array($excerpt, $payload['recent_assistant_guidance'], true)) {
                    $payload['recent_assistant_guidance'][] = $excerpt;
                }
            }
        }

        $payload['recent_topics'] = array_slice($payload['recent_topics'], 0, 3);
        $payload['recent_challenges'] = array_slice($payload['recent_challenges'], 0, 3);
        $payload['recent_user_messages'] = array_slice($payload['recent_user_messages'], 0, 3);
        $payload['recent_assistant_guidance'] = array_slice($payload['recent_assistant_guidance'], 0, 3);
        $payload['has_recent_activity'] = !empty($payload['last_touch_at']) || $coach_conversation_total > 0;

        $summary_parts = [];

        if($payload['has_recent_activity']) {
            $summary_parts[] = 'Coach je aktivan i ima sažetak prethodne komunikacije.';
        }

        if(!empty($payload['recent_topics'])) {
            $summary_parts[] = 'Zadnje teme: ' . implode(', ', $payload['recent_topics']) . '.';
        }

        if(!empty($payload['recent_challenges'])) {
            $summary_parts[] = 'Zadnje prepoznate blokade: ' . implode(' | ', $payload['recent_challenges']) . '.';
        }

        if(!empty($payload['recent_assistant_guidance'])) {
            $summary_parts[] = 'Zadnji smjer koji je coach dao: ' . $payload['recent_assistant_guidance'][0] . '.';
        }

        $payload['summary'] = trim(implode(' ', $summary_parts));

        return $payload;
    }

    private function upsert_weekly_plan(array $weekly_plans, array $new_plan): array {
        $checkin_submitted_at = (string) ($new_plan['checkin_submitted_at'] ?? '');
        $updated_plans = [];

        foreach($weekly_plans as $weekly_plan) {
            if((string) ($weekly_plan['checkin_submitted_at'] ?? '') === $checkin_submitted_at) {
                continue;
            }

            $updated_plans[] = $weekly_plan;
        }

        array_unshift($updated_plans, $new_plan);

        return array_slice($updated_plans, 0, 12);
    }

    private function build_recovery_weekly_plan(array $values, array $weekly_checkin, array $analytics_payload, array $app_structure_payload, ?array $previous_cycle_context = null, array $mentor_guidance = [], ?array $latest_app_review = null, array $coach_context = [], string $model = 'fallback_recovery'): array {
        $recovery_plan = $this->build_emergency_weekly_ai_plan($values, $weekly_checkin, $analytics_payload, $app_structure_payload, $previous_cycle_context, $mentor_guidance, $latest_app_review, $coach_context);
        $recovery_plan['checkin_submitted_at'] = (string) ($weekly_checkin['submitted_at'] ?? get_date());
        $recovery_plan['generated_at'] = get_date();
        $recovery_plan['model'] = $model;

        return $recovery_plan;
    }

    private function get_saved_app_reviews($preferences): array {
        $reviews = $preferences->leader_ai_app_reviews ?? [];

        if(is_object($reviews)) {
            $reviews = (array) $reviews;
        }

        if(!is_array($reviews)) {
            return [];
        }

        $normalized = [];

        foreach($reviews as $review) {
            if(is_object($review)) {
                $review = (array) $review;
            }

            if(!is_array($review)) {
                continue;
            }

            $performance_snapshot = $review['performance_snapshot'] ?? [];

            if(is_object($performance_snapshot)) {
                $performance_snapshot = (array) $performance_snapshot;
            }

            if(!is_array($performance_snapshot)) {
                $performance_snapshot = [];
            }

            $color_palette = $this->normalize_app_review_color_palette($review['color_palette'] ?? []);
            $raw_primary_block_plan = $review['primary_block_plan'] ?? [];
            if($raw_primary_block_plan instanceof \stdClass) {
                $raw_primary_block_plan = (array) $raw_primary_block_plan;
            }
            if(!is_array($raw_primary_block_plan)) {
                $raw_primary_block_plan = [];
            }
            $primary_action_fallback = [
                'block_id' => (int) ($raw_primary_block_plan['block_id'] ?? 0),
                'type' => (string) ($raw_primary_block_plan['block_type'] ?? ''),
                'label' => (string) ($raw_primary_block_plan['label'] ?? ''),
            ];
            $theme_pack = $this->normalize_app_review_theme_pack($review['theme_pack'] ?? [], $color_palette);
            $color_palette = $this->sync_app_review_color_palette_with_theme_pack($color_palette, $theme_pack);
            $block_attribution_snapshot = $this->normalize_app_review_block_attribution_payload($review['block_attribution_snapshot'] ?? []);
            $layout_actions = $this->enforce_app_review_signal_safe_layout_actions(
                $this->normalize_app_review_layout_actions($review['layout_actions'] ?? []),
                $block_attribution_snapshot
            );
            $signal_protection_summary = $this->normalize_app_review_signal_protection_summary($review['signal_protection_summary'] ?? []);
            if(empty($signal_protection_summary['has_items']) && !empty($block_attribution_snapshot['has_blocks'])) {
                $signal_protection_summary = $this->build_app_review_signal_protection_summary($block_attribution_snapshot, $layout_actions);
            }
            $missing_block_recommendations = $this->normalize_app_review_missing_block_recommendations($review['missing_block_recommendations'] ?? []);
            $final_block_plan = $this->normalize_ai_final_block_plan($review['final_block_plan'] ?? []);

            if(empty($final_block_plan)) {
                $final_block_plan = $this->build_app_review_final_block_plan(
                    $block_attribution_snapshot,
                    $layout_actions,
                    $this->normalize_app_review_visible_list(array_values(array_filter((array) ($review['ideal_block_order'] ?? []), 'is_scalar'))),
                    $missing_block_recommendations
                );
            }

            $normalized[] = [
                'generated_at' => $review['generated_at'] ?? null,
                'review_key' => (string) ($review['review_key'] ?? ($review['generated_at'] ?? '')),
                'model' => (string) ($review['model'] ?? ''),
                'selected_link_id' => (int) ($review['selected_link_id'] ?? 0),
                'selected_app_url' => (string) ($review['selected_app_url'] ?? ''),
                'selected_app_name' => (string) ($review['selected_app_name'] ?? ''),
                'request_context' => (string) ($review['request_context'] ?? ''),
                'goal_type' => (string) ($review['goal_type'] ?? ''),
                'growth_stage' => (string) ($review['growth_stage'] ?? ''),
                'analysis_mode' => in_array((string) ($review['analysis_mode'] ?? 'initial'), ['initial', 'evolution'], true) ? (string) ($review['analysis_mode'] ?? 'initial') : 'initial',
                'quality_score' => (int) ($review['quality_score'] ?? 0),
                'quality_level' => (string) ($review['quality_level'] ?? 'foundation'),
                'performance_snapshot' => [
                    'shop_contacts_30d' => (int) (($performance_snapshot['shop_contacts_30d'] ?? 0)),
                    'whatsapp_contacts_30d' => (int) (($performance_snapshot['whatsapp_contacts_30d'] ?? 0)),
                    'product_clicks_30d' => (int) (($performance_snapshot['product_clicks_30d'] ?? 0)),
                    'funnel_registrations_30d' => (int) (($performance_snapshot['funnel_registrations_30d'] ?? 0)),
                    'weighted_signal_score' => (int) (($performance_snapshot['weighted_signal_score'] ?? 0)),
                ],
                'headline' => $this->normalize_app_review_channel_copy((string) ($review['headline'] ?? '')),
                'summary' => $this->normalize_app_review_channel_copy((string) ($review['summary'] ?? '')),
                'biggest_bottleneck' => $this->normalize_app_review_channel_copy((string) ($review['biggest_bottleneck'] ?? '')),
                'top_recommendation' => $this->normalize_app_review_channel_copy((string) ($review['top_recommendation'] ?? '')),
                'weekly_focus' => $this->normalize_app_review_channel_copy((string) ($review['weekly_focus'] ?? '')),
                'first_move' => $this->normalize_app_review_channel_copy((string) ($review['first_move'] ?? '')),
                'next_move' => $this->normalize_app_review_channel_copy((string) ($review['next_move'] ?? '')),
                'do_not_touch' => $this->normalize_app_review_channel_copy((string) ($review['do_not_touch'] ?? '')),
                'priority_actions' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['priority_actions'] ?? []), 'is_scalar'))),
                'ideal_block_order' => $this->normalize_app_review_visible_list(array_values(array_filter((array) ($review['ideal_block_order'] ?? []), 'is_scalar'))),
                'design_notes' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['design_notes'] ?? []), 'is_scalar'))),
                'keep_doing' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['keep_doing'] ?? []), 'is_scalar'))),
                'funnel_blueprint' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['funnel_blueprint'] ?? []), 'is_scalar'))),
                'color_palette' => $color_palette,
                'trust_builders' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['trust_builders'] ?? []), 'is_scalar'))),
                'theme_pack' => $theme_pack,
                'primary_block_plan' => $this->normalize_app_review_primary_block_plan($raw_primary_block_plan, $primary_action_fallback),
                'block_patch_pack' => $this->normalize_app_review_block_patch_pack($review['block_patch_pack'] ?? []),
                'copy_suggestions' => $this->normalize_app_review_copy_suggestions($review['copy_suggestions'] ?? []),
                'layout_actions' => $layout_actions,
                'missing_block_recommendations' => $missing_block_recommendations,
                'block_attribution_snapshot' => $block_attribution_snapshot,
                'signal_protection_summary' => $signal_protection_summary,
                'final_block_plan' => $final_block_plan,
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['generated_at'] ?? ''), (string) ($a['generated_at'] ?? ''));
        });

        return $normalized;
    }

    private function get_latest_app_review(array $app_reviews): ?array {
        return $app_reviews[0] ?? null;
    }

    private function get_latest_app_review_for_link(array $app_reviews, int $selected_link_id = 0): ?array {
        if(!$selected_link_id) {
            return $this->get_latest_app_review($app_reviews);
        }

        foreach($app_reviews as $app_review) {
            if((int) ($app_review['selected_link_id'] ?? 0) === $selected_link_id) {
                return $app_review;
            }
        }

        return null;
    }

    private function get_app_reviews_for_link(array $app_reviews, int $selected_link_id = 0): array {
        if(!$selected_link_id) {
            return $app_reviews;
        }

        return array_values(array_filter($app_reviews, static function($app_review) use ($selected_link_id) {
            return (int) ($app_review['selected_link_id'] ?? 0) === $selected_link_id;
        }));
    }

    private function get_app_review_for_link_by_generated_at(array $app_reviews, int $selected_link_id, string $generated_at): ?array {
        if($generated_at === '') {
            return null;
        }

        foreach($app_reviews as $app_review) {
            if(
                (int) ($app_review['selected_link_id'] ?? 0) === $selected_link_id
                && (string) ($app_review['generated_at'] ?? '') === $generated_at
            ) {
                return $app_review;
            }
        }

        return null;
    }

    private function upsert_app_review(array $app_reviews, array $new_review): array {
        $generated_at = (string) ($new_review['generated_at'] ?? '');
        $updated_reviews = [];

        foreach($app_reviews as $app_review) {
            if((string) ($app_review['generated_at'] ?? '') === $generated_at) {
                continue;
            }

            $updated_reviews[] = $app_review;
        }

        array_unshift($updated_reviews, $new_review);

        return array_slice($updated_reviews, 0, 12);
    }

    private function get_app_review_job_status($preferences): array {
        $job = $preferences->leader_ai_app_review_job ?? null;

        if(is_object($job)) {
            $job = (array) $job;
        }

        if(!is_array($job)) {
            return [
                'status' => 'idle',
                'job_id' => '',
                'started_at' => null,
                'completed_at' => null,
                'selected_link_id' => 0,
                'error_message' => '',
            ];
        }

        return [
            'status' => in_array((string) ($job['status'] ?? 'idle'), ['idle', 'pending', 'completed', 'failed'], true) ? (string) ($job['status'] ?? 'idle') : 'idle',
            'job_id' => (string) ($job['job_id'] ?? ''),
            'started_at' => $job['started_at'] ?? null,
            'completed_at' => $job['completed_at'] ?? null,
            'selected_link_id' => (int) ($job['selected_link_id'] ?? 0),
            'error_message' => (string) ($job['error_message'] ?? ''),
        ];
    }

    private function set_app_review_job_status(\stdClass $preferences, array $job_status): \stdClass {
        $preferences->leader_ai_app_review_job = (object) [
            'status' => (string) ($job_status['status'] ?? 'idle'),
            'job_id' => (string) ($job_status['job_id'] ?? ''),
            'started_at' => $job_status['started_at'] ?? null,
            'completed_at' => $job_status['completed_at'] ?? null,
            'selected_link_id' => (int) ($job_status['selected_link_id'] ?? 0),
            'error_message' => (string) ($job_status['error_message'] ?? ''),
        ];

        return $preferences;
    }

    private function encode_ai_plan_preferences_for_storage(\stdClass $preferences): ?string {
        $encoded = json_encode($preferences, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);

        return is_string($encoded) ? $encoded : null;
    }

    private function limit_ai_plan_storage_list(\stdClass $preferences, string $key, int $limit): \stdClass {
        $list = $preferences->{$key} ?? [];

        if($list instanceof \stdClass) {
            $list = (array) $list;
        }

        if(!is_array($list)) {
            return $preferences;
        }

        $preferences->{$key} = array_values(array_slice($list, 0, max(0, $limit)));

        return $preferences;
    }

    private function compact_app_review_copy_suggestions_for_storage($value, int $limit = 3): array {
        $items = $this->normalize_app_review_copy_suggestions($value);
        $compacted = [];

        foreach(array_slice($items, 0, max(0, $limit)) as $item) {
            $compacted[] = [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'block_type' => (string) ($item['block_type'] ?? ''),
                'field' => (string) ($item['field'] ?? ''),
                'label' => (string) ($item['label'] ?? ''),
                'value' => (string) ($item['value'] ?? ''),
                'reason' => (string) ($item['reason'] ?? ''),
            ];
        }

        return $compacted;
    }

    private function compact_app_review_layout_actions_for_storage($value, int $limit = 3): array {
        $items = $this->normalize_app_review_layout_actions($value);
        $compacted = [];

        foreach(array_slice($items, 0, max(0, $limit)) as $item) {
            $compacted[] = [
                'action' => (string) ($item['action'] ?? ''),
                'block_id' => (int) ($item['block_id'] ?? 0),
                'block_type' => (string) ($item['block_type'] ?? ''),
                'label' => (string) ($item['label'] ?? ''),
                'why' => (string) ($item['why'] ?? ''),
            ];
        }

        return $compacted;
    }

    private function compact_app_review_block_patch_pack_for_storage($value, int $limit = 3, int $settings_limit = 3): array {
        $items = $this->normalize_app_review_block_patch_pack($value);
        $compacted = [];

        foreach(array_slice($items, 0, max(0, $limit)) as $item) {
            $settings = [];

            foreach(array_slice((array) ($item['settings'] ?? []), 0, max(0, $settings_limit), true) as $setting_key => $setting_value) {
                if(!is_scalar($setting_value)) {
                    continue;
                }

                $settings[(string) $setting_key] = (string) $setting_value;
            }

            $compacted[] = [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'block_type' => (string) ($item['block_type'] ?? ''),
                'reason' => (string) ($item['reason'] ?? ''),
                'settings' => $settings,
            ];
        }

        return $compacted;
    }

    private function compact_app_review_block_attribution_for_storage($value, bool $aggressive = false): array {
        $payload = $this->normalize_app_review_block_attribution_payload($value);
        $top_limit = $aggressive ? 2 : 3;
        $risk_limit = $aggressive ? 2 : 3;
        $all_limit = $aggressive ? 4 : 6;

        $map_row = static function(array $item): array {
            return [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'position' => (int) ($item['position'] ?? 0),
                'type' => (string) ($item['type'] ?? ''),
                'label' => (string) ($item['label'] ?? ''),
                'role' => (string) ($item['role'] ?? ''),
                'signal_score' => (int) ($item['signal_score'] ?? 0),
                'status' => (string) ($item['status'] ?? ''),
                'reason' => (string) ($item['reason'] ?? ''),
            ];
        };

        $top_signal_blocks = array_map($map_row, array_slice((array) ($payload['top_signal_blocks'] ?? []), 0, $top_limit));
        $focus_risk_blocks = array_map($map_row, array_slice((array) ($payload['focus_risk_blocks'] ?? []), 0, $risk_limit));

        $all_blocks_seed = !empty($payload['all_blocks'])
            ? array_slice((array) $payload['all_blocks'], 0, $all_limit)
            : array_slice(array_merge($top_signal_blocks, $focus_risk_blocks), 0, $all_limit);

        return [
            'summary' => [
                'tracked_blocks' => (int) (($payload['summary']['tracked_blocks'] ?? 0)),
                'signal_blocks' => (int) (($payload['summary']['signal_blocks'] ?? 0)),
                'focus_risk_blocks' => (int) (($payload['summary']['focus_risk_blocks'] ?? 0)),
                'zero_signal_blocks' => (int) (($payload['summary']['zero_signal_blocks'] ?? 0)),
            ],
            'top_signal_blocks' => $top_signal_blocks,
            'focus_risk_blocks' => $focus_risk_blocks,
            'all_blocks' => array_map($map_row, $all_blocks_seed),
        ];
    }

    private function compact_app_review_signal_protection_for_storage($value, bool $aggressive = false): array {
        $payload = $this->normalize_app_review_signal_protection_summary($value);
        $item_limit = $aggressive ? 2 : 3;

        $map_row = static function(array $item): array {
            return [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'label' => (string) ($item['label'] ?? ''),
                'status' => (string) ($item['status'] ?? ''),
                'planned_action' => (string) ($item['planned_action'] ?? ''),
                'reason' => (string) ($item['reason'] ?? ''),
            ];
        };

        return [
            'summary' => (string) ($payload['summary'] ?? ''),
            'protected_block_ids' => array_slice(array_values((array) ($payload['protected_block_ids'] ?? [])), 0, $aggressive ? 4 : 6),
            'kept_signal_blocks' => array_map($map_row, array_slice((array) ($payload['kept_signal_blocks'] ?? []), 0, $item_limit)),
            'repositioned_focus_blocks' => array_map($map_row, array_slice((array) ($payload['repositioned_focus_blocks'] ?? []), 0, $item_limit)),
        ];
    }

    private function compact_single_app_review_for_storage($review, bool $aggressive = false): array {
        $normalized_reviews = $this->get_saved_app_reviews((object) [
            'leader_ai_app_reviews' => [$review],
        ]);

        $normalized_review = $normalized_reviews[0] ?? [];

        if(empty($normalized_review)) {
            return [];
        }

        $list_limit = $aggressive ? 2 : 3;
        $order_limit = $aggressive ? 4 : 6;

        return [
            'generated_at' => $normalized_review['generated_at'] ?? null,
            'review_key' => (string) ($normalized_review['review_key'] ?? ($normalized_review['generated_at'] ?? '')),
            'model' => (string) ($normalized_review['model'] ?? ''),
            'selected_link_id' => (int) ($normalized_review['selected_link_id'] ?? 0),
            'selected_app_url' => (string) ($normalized_review['selected_app_url'] ?? ''),
            'selected_app_name' => (string) ($normalized_review['selected_app_name'] ?? ''),
            'request_context' => (string) ($normalized_review['request_context'] ?? ''),
            'goal_type' => (string) ($normalized_review['goal_type'] ?? ''),
            'growth_stage' => (string) ($normalized_review['growth_stage'] ?? ''),
            'analysis_mode' => (string) ($normalized_review['analysis_mode'] ?? 'initial'),
            'quality_score' => (int) ($normalized_review['quality_score'] ?? 0),
            'quality_level' => (string) ($normalized_review['quality_level'] ?? 'foundation'),
            'performance_snapshot' => $this->normalize_app_review_performance_snapshot($normalized_review['performance_snapshot'] ?? []),
            'headline' => (string) ($normalized_review['headline'] ?? ''),
            'summary' => (string) ($normalized_review['summary'] ?? ''),
            'biggest_bottleneck' => (string) ($normalized_review['biggest_bottleneck'] ?? ''),
            'top_recommendation' => (string) ($normalized_review['top_recommendation'] ?? ''),
            'weekly_focus' => (string) ($normalized_review['weekly_focus'] ?? ''),
            'first_move' => (string) ($normalized_review['first_move'] ?? ''),
            'next_move' => (string) ($normalized_review['next_move'] ?? ''),
            'do_not_touch' => (string) ($normalized_review['do_not_touch'] ?? ''),
            'priority_actions' => array_slice((array) ($normalized_review['priority_actions'] ?? []), 0, $list_limit),
            'ideal_block_order' => array_slice((array) ($normalized_review['ideal_block_order'] ?? []), 0, $order_limit),
            'design_notes' => array_slice((array) ($normalized_review['design_notes'] ?? []), 0, $list_limit),
            'keep_doing' => array_slice((array) ($normalized_review['keep_doing'] ?? []), 0, $list_limit),
            'funnel_blueprint' => array_slice((array) ($normalized_review['funnel_blueprint'] ?? []), 0, $list_limit),
            'color_palette' => $this->normalize_app_review_color_palette($normalized_review['color_palette'] ?? []),
            'trust_builders' => array_slice((array) ($normalized_review['trust_builders'] ?? []), 0, $list_limit),
            'theme_pack' => $this->normalize_app_review_theme_pack($normalized_review['theme_pack'] ?? [], (array) ($normalized_review['color_palette'] ?? [])),
            'primary_block_plan' => $this->normalize_app_review_primary_block_plan($normalized_review['primary_block_plan'] ?? []),
            'block_patch_pack' => $this->compact_app_review_block_patch_pack_for_storage($normalized_review['block_patch_pack'] ?? [], $aggressive ? 2 : 3, $aggressive ? 2 : 3),
            'copy_suggestions' => $this->compact_app_review_copy_suggestions_for_storage($normalized_review['copy_suggestions'] ?? [], $aggressive ? 2 : 3),
            'layout_actions' => $this->compact_app_review_layout_actions_for_storage($normalized_review['layout_actions'] ?? [], $aggressive ? 2 : 3),
            'missing_block_recommendations' => $this->normalize_app_review_missing_block_recommendations($normalized_review['missing_block_recommendations'] ?? []),
            'block_attribution_snapshot' => $this->compact_app_review_block_attribution_for_storage($normalized_review['block_attribution_snapshot'] ?? [], $aggressive),
            'signal_protection_summary' => $this->compact_app_review_signal_protection_for_storage($normalized_review['signal_protection_summary'] ?? [], $aggressive),
            'final_block_plan' => $this->normalize_ai_final_block_plan($normalized_review['final_block_plan'] ?? []),
        ];
    }

    private function compact_ai_plan_app_reviews_for_storage(\stdClass $preferences, bool $compact_latest = false, bool $aggressive = false): \stdClass {
        $app_reviews = $preferences->leader_ai_app_reviews ?? [];

        if($app_reviews instanceof \stdClass) {
            $app_reviews = (array) $app_reviews;
        }

        if(!is_array($app_reviews) || empty($app_reviews)) {
            return $preferences;
        }

        $compacted_reviews = [];

        foreach(array_values($app_reviews) as $index => $review) {
            if($index === 0 && !$compact_latest) {
                $compacted_reviews[] = $review;
                continue;
            }

            $compacted_reviews[] = $this->compact_single_app_review_for_storage($review, $aggressive);
        }

        $preferences->leader_ai_app_reviews = $compacted_reviews;

        return $preferences;
    }

    private function trim_ai_plan_preferences_for_storage(\stdClass $preferences, int $target_bytes = 60000, bool $force_aggressive = false): \stdClass {
        $preferences = $this->get_preferences_object($preferences);
        $encoded = $this->encode_ai_plan_preferences_for_storage($preferences);

        if($encoded !== null && strlen($encoded) <= $target_bytes) {
            return $preferences;
        }

        $preferences = $this->compact_ai_plan_app_reviews_for_storage($preferences, false, false);
        $encoded = $this->encode_ai_plan_preferences_for_storage($preferences);

        if($encoded !== null && strlen($encoded) <= $target_bytes) {
            return $preferences;
        }

        $preferences = $this->compact_ai_plan_app_reviews_for_storage($preferences, false, true);
        $encoded = $this->encode_ai_plan_preferences_for_storage($preferences);

        if($encoded !== null && strlen($encoded) <= $target_bytes) {
            return $preferences;
        }

        if($force_aggressive) {
            $preferences = $this->compact_ai_plan_app_reviews_for_storage($preferences, true, true);
            $encoded = $this->encode_ai_plan_preferences_for_storage($preferences);

            if($encoded !== null && strlen($encoded) <= $target_bytes) {
                return $preferences;
            }
        } else {
            $preferences = $this->compact_ai_plan_app_reviews_for_storage($preferences, true, false);
            $encoded = $this->encode_ai_plan_preferences_for_storage($preferences);

            if($encoded !== null && strlen($encoded) <= $target_bytes) {
                return $preferences;
            }
        }

        $trim_steps = [
            ['leader_ai_theme_library', 10],
            ['leader_ai_app_reviews', 10],
            ['leader_ai_weekly_plans', 10],
            ['leader_ai_weekly_outcomes', 10],
            ['leader_ai_weekly_checkins', 10],
            ['leader_ai_theme_library', 8],
            ['leader_ai_app_reviews', 8],
            ['leader_ai_weekly_plans', 8],
            ['leader_ai_weekly_outcomes', 8],
            ['leader_ai_weekly_checkins', 8],
            ['leader_ai_theme_library', 6],
            ['leader_ai_app_reviews', 6],
            ['leader_ai_weekly_plans', 6],
            ['leader_ai_weekly_outcomes', 6],
            ['leader_ai_weekly_checkins', 6],
            ['leader_ai_theme_library', 4],
            ['leader_ai_app_reviews', 4],
            ['leader_ai_weekly_plans', 4],
            ['leader_ai_weekly_outcomes', 4],
            ['leader_ai_weekly_checkins', 4],
            ['leader_ai_theme_library', 3],
            ['leader_ai_app_reviews', 3],
            ['leader_ai_weekly_plans', 3],
            ['leader_ai_weekly_outcomes', 3],
            ['leader_ai_weekly_checkins', 3],
        ];

        foreach($trim_steps as [$key, $limit]) {
            $preferences = $this->limit_ai_plan_storage_list($preferences, $key, $limit);
            $preferences = $this->compact_ai_plan_app_reviews_for_storage($preferences, $force_aggressive || $limit <= 4, $force_aggressive || $limit <= 6);
            $encoded = $this->encode_ai_plan_preferences_for_storage($preferences);

            if($encoded !== null && strlen($encoded) <= $target_bytes) {
                return $preferences;
            }
        }

        return $preferences;
    }

    private function persist_ai_plan_preferences(\stdClass $preferences): bool {
        $preferences = $this->trim_ai_plan_preferences_for_storage($preferences);
        $encoded = $this->encode_ai_plan_preferences_for_storage($preferences);

        if($encoded === null) {
            \Altum\Logger::users($this->user->user_id, 'ai_plan.preferences_encode_failed');
            return false;
        }

        $result = db()->where('user_id', $this->user->user_id)->update('users', [
            'preferences' => $encoded,
        ]);

        if(!$result || !empty(database()->error)) {
            $preferences = $this->trim_ai_plan_preferences_for_storage($preferences, 50000, true);
            $encoded = $this->encode_ai_plan_preferences_for_storage($preferences);

            if($encoded === null) {
                \Altum\Logger::users($this->user->user_id, 'ai_plan.preferences_encode_failed_retry');
                return false;
            }

            $result = db()->where('user_id', $this->user->user_id)->update('users', [
                'preferences' => $encoded,
            ]);

            if(!$result || !empty(database()->error)) {
                \Altum\Logger::users($this->user->user_id, 'ai_plan.preferences_persist_failed');
                return false;
            }
        }

        cache()->deleteItemsByTag('user_id=' . $this->user->user_id);
        cache()->deleteItem('user?user_id=' . $this->user->user_id);

        return true;
    }

    private function get_saved_ai_theme_library($preferences): array {
        $preferences = $this->get_preferences_object($preferences);
        $library = $preferences->leader_ai_theme_library ?? [];

        if($library instanceof \stdClass) {
            $library = (array) $library;
        }

        if(!is_array($library)) {
            return [];
        }

        $normalized = [];

        foreach($library as $entry) {
            if($entry instanceof \stdClass) {
                $entry = (array) $entry;
            }

            if(!is_array($entry)) {
                continue;
            }

            $theme_pack = $this->normalize_app_review_theme_pack($entry['theme_pack'] ?? []);

            $normalized[] = [
                'theme_key' => $this->sanitize_ai_string($entry['theme_key'] ?? '', 64),
                'name' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($entry['name'] ?? $theme_pack['name'] ?? '', 120)),
                'summary' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($entry['summary'] ?? $theme_pack['summary'] ?? '', 220)),
                'generated_at' => $entry['generated_at'] ?? null,
                'selected_link_id' => (int) ($entry['selected_link_id'] ?? 0),
                'selected_app_name' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($entry['selected_app_name'] ?? '', 120)),
                'goal_type' => $this->sanitize_ai_string($entry['goal_type'] ?? '', 32),
                'theme_pack' => $theme_pack,
            ];
        }

        return array_values(array_filter($normalized, static function(array $entry): bool {
            return (string) ($entry['theme_key'] ?? '') !== '' && !empty($entry['theme_pack']);
        }));
    }

    private function upsert_ai_theme_library(array $library, array $new_entry): array {
        $theme_key = (string) ($new_entry['theme_key'] ?? '');

        if($theme_key === '') {
            return $library;
        }

        $updated_library = [];

        foreach($library as $entry) {
            if((string) ($entry['theme_key'] ?? '') === $theme_key) {
                continue;
            }

            $updated_library[] = $entry;
        }

        array_unshift($updated_library, $new_entry);

        return array_slice($updated_library, 0, 12);
    }

    private function get_default_app_review_performance_snapshot(): array {
        return [
            'shop_contacts_30d' => 0,
            'whatsapp_contacts_30d' => 0,
            'product_clicks_30d' => 0,
            'funnel_registrations_30d' => 0,
            'weighted_signal_score' => 0,
        ];
    }

    private function normalize_app_review_performance_snapshot($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            $value = [];
        }

        return [
            'shop_contacts_30d' => max(0, (int) ($value['shop_contacts_30d'] ?? 0)),
            'whatsapp_contacts_30d' => max(0, (int) ($value['whatsapp_contacts_30d'] ?? 0)),
            'product_clicks_30d' => max(0, (int) ($value['product_clicks_30d'] ?? 0)),
            'funnel_registrations_30d' => max(0, (int) ($value['funnel_registrations_30d'] ?? 0)),
            'weighted_signal_score' => max(0, (int) ($value['weighted_signal_score'] ?? 0)),
        ];
    }

    private function get_app_review_performance_delta(array $before, array $after): array {
        $before = $this->normalize_app_review_performance_snapshot($before);
        $after = $this->normalize_app_review_performance_snapshot($after);
        $metric_labels = [
            'shop_contacts_30d' => 'shop',
            'whatsapp_contacts_30d' => 'whatsapp',
            'product_clicks_30d' => 'blog_products',
            'funnel_registrations_30d' => 'funnel_contacts',
            'weighted_signal_score' => 'total_signal',
        ];
        $delta = [];

        foreach($metric_labels as $metric_key => $label) {
            $previous_value = (int) ($before[$metric_key] ?? 0);
            $current_value = (int) ($after[$metric_key] ?? 0);
            $change = $current_value - $previous_value;

            $delta[] = [
                'metric' => $label,
                'previous' => $previous_value,
                'current' => $current_value,
                'delta' => $change,
                'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'same'),
            ];
        }

        return $delta;
    }

    private function get_app_review_block_attribution_role(string $type, \stdClass $settings): string {
        $shop_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $video_types = ['youtube', 'video', 'tiktok_video', 'vimeo', 'twitter_video', 'vk_video'];

        if($type === 'lead_funnel') {
            return 'lead_capture';
        }

        if($this->is_app_review_whatsapp_block($type, $settings)) {
            return 'whatsapp';
        }

        if($type === 'link_forever_shop') {
            return 'cta';
        }

        if(in_array($type, $shop_types, true)) {
            return 'shop';
        }

        if($type === 'link_forever_product') {
            return 'product';
        }

        if(in_array($type, ['link_app_switcher', 'link_save_contact', 'contact_collector', 'email_collector', 'link'], true)) {
            return 'cta';
        }

        if(in_array($type, $video_types, true)) {
            return 'video';
        }

        if(in_array($type, ['heading', 'paragraph', 'image', 'avatar', 'review'], true)) {
            return 'trust_content';
        }

        if($type === 'socials') {
            return 'social_contact';
        }

        return 'supporting';
    }

    private function is_app_review_block_focus_sensitive_role(string $role): bool {
        return in_array($role, ['lead_capture', 'whatsapp', 'shop', 'product', 'cta', 'social_contact', 'video'], true);
    }

    private function get_app_review_block_focus_cost_score(string $role, int $position): int {
        $score = match(true) {
            $position <= 1 => 5,
            $position <= 3 => 4,
            $position <= 5 => 3,
            $position <= 8 => 2,
            default => 1,
        };

        if(in_array($role, ['lead_capture', 'whatsapp', 'shop', 'product', 'cta'], true)) {
            $score += 2;
        } elseif($role === 'video') {
            $score += 1;
        }

        return $score;
    }

    private function get_app_review_block_attribution_reason(string $status, int $position, int $signal_score, int $unique_clicks, int $funnel_leads): string {
        return match($status) {
            'high_signal' => $funnel_leads > 0
                ? 'Ovaj blok trenutno nosi najjaci signal i vec dovodi prijave, zato ga vrijedi zadrzati jasno vidljivim.'
                : 'Ovaj blok trenutno donosi najvise klika i vrijedi ga zadrzati visoko u fokusu aplikacije.',
            'contributing' => 'Blok vec donosi signal pa ga vrijedi zadrzati i dodatno izbrusiti kroz tekst ili naglasak.',
            'critical_focus_risk' => 'Blok je vrlo visoko postavljen, ali u zadnjih 30 dana nema signal pa vjerojatno uzima fokus bez rezultata.',
            'focus_risk' => 'Blok je rano u aplikaciji, ali zasad nema mjerljiv rezultat pa je kandidat za spustanje ili jasniji tekst.',
            'supporting' => $position <= 4
                ? 'Blok vise gradi dojam i povjerenje nego klik, pa treba ostati kratak i ne smije gurati glavni korak u drugi plan.'
                : 'Blok trenutno sluzi vise kao podrzka i povjerenje nego kao izravan izvor signala.',
            default => $signal_score > 0 || $unique_clicks > 0 || $funnel_leads > 0
                ? 'Blok ima slabiji signal i vrijedi ga pratiti prije vecih promjena.'
                : 'Blok je aktivan, ali trenutno nema mjerljiv doprinos rezultatu.',
        };
    }

    private function is_app_review_trust_anchor_block(string $type, string $role): bool {
        return $role === 'trust_content';
    }

    private function build_app_review_block_attribution_row(array $block, int $position, int $unique_clicks, int $funnel_leads): array {
        $type = (string) ($block['type'] ?? '');
        $settings = $this->decode_biolink_block_settings($block['settings'] ?? null);
        $role = $this->get_app_review_block_attribution_role($type, $settings);
        $signal_score = max(0, $unique_clicks + ($funnel_leads * 2));
        $focus_cost_score = $this->get_app_review_block_focus_cost_score($role, $position);
        $is_focus_sensitive = $this->is_app_review_block_focus_sensitive_role($role);

        if($signal_score >= 8 || $funnel_leads >= 2 || ($is_focus_sensitive && $unique_clicks >= 4)) {
            $status = 'high_signal';
        } elseif($signal_score >= 2 || $unique_clicks >= 2) {
            $status = 'contributing';
        } elseif($signal_score === 0 && $this->is_app_review_trust_anchor_block($type, $role)) {
            $status = 'supporting';
        } elseif($signal_score === 0 && $focus_cost_score >= 6 && $position <= 3 && $is_focus_sensitive) {
            $status = 'critical_focus_risk';
        } elseif($signal_score === 0 && $focus_cost_score >= 5 && $position <= 5) {
            $status = 'focus_risk';
        } elseif($signal_score === 0 && in_array($role, ['trust_content', 'video'], true)) {
            $status = 'supporting';
        } else {
            $status = 'low_signal';
        }

        $action_hint = match($status) {
            'high_signal' => 'keep_or_emphasize',
            'contributing' => 'keep_and_refine',
            'critical_focus_risk' => 'move_down_or_hide',
            'focus_risk' => 'rewrite_or_move_down',
            'supporting' => 'keep_supporting',
            default => 'test_or_reduce',
        };

        return [
            'block_id' => (int) ($block['block_id'] ?? 0),
            'position' => $position,
            'type' => $this->sanitize_ai_string($type, 64),
            'label' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string(($block['label'] ?? '') ?: $type, 160)),
            'role' => $role,
            'unique_clicks_30d' => max(0, $unique_clicks),
            'funnel_leads_30d' => max(0, $funnel_leads),
            'signal_score' => $signal_score,
            'focus_cost_score' => $focus_cost_score,
            'status' => $status,
            'action_hint' => $action_hint,
            'reason' => $this->get_app_review_block_attribution_reason($status, $position, $signal_score, $unique_clicks, $funnel_leads),
        ];
    }

    private function normalize_app_review_block_attribution_payload($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [
                'has_blocks' => false,
                'summary' => [
                    'tracked_blocks' => 0,
                    'signal_blocks' => 0,
                    'focus_risk_blocks' => 0,
                    'zero_signal_blocks' => 0,
                ],
                'top_signal_blocks' => [],
                'focus_risk_blocks' => [],
                'all_blocks' => [],
            ];
        }

        $normalize_row = function($item): ?array {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(!is_array($item)) {
                return null;
            }

            $row = [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'position' => max(0, (int) ($item['position'] ?? 0)),
                'type' => $this->sanitize_ai_string($item['type'] ?? '', 64),
                'label' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($item['label'] ?? '', 160)),
                'role' => $this->sanitize_ai_string($item['role'] ?? '', 64),
                'unique_clicks_30d' => max(0, (int) ($item['unique_clicks_30d'] ?? 0)),
                'funnel_leads_30d' => max(0, (int) ($item['funnel_leads_30d'] ?? 0)),
                'signal_score' => max(0, (int) ($item['signal_score'] ?? 0)),
                'focus_cost_score' => max(0, (int) ($item['focus_cost_score'] ?? 0)),
                'status' => $this->sanitize_ai_string($item['status'] ?? '', 48),
                'action_hint' => $this->sanitize_ai_string($item['action_hint'] ?? '', 64),
                'reason' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($item['reason'] ?? '', 220)),
            ];

            if(
                (int) ($row['signal_score'] ?? 0) === 0
                && in_array((string) ($row['status'] ?? ''), ['focus_risk', 'critical_focus_risk'], true)
                && $this->is_app_review_trust_anchor_block((string) ($row['type'] ?? ''), (string) ($row['role'] ?? ''))
            ) {
                $row['status'] = 'supporting';
                $row['action_hint'] = 'keep_supporting';
                $row['reason'] = $this->get_app_review_block_attribution_reason(
                    'supporting',
                    (int) ($row['position'] ?? 0),
                    (int) ($row['signal_score'] ?? 0),
                    (int) ($row['unique_clicks_30d'] ?? 0),
                    (int) ($row['funnel_leads_30d'] ?? 0)
                );
            }

            return $row;
        };

        $top_signal_blocks = [];
        foreach((array) ($value['top_signal_blocks'] ?? []) as $item) {
            $row = $normalize_row($item);
            if($row) {
                $top_signal_blocks[] = $row;
            }
            if(count($top_signal_blocks) >= 4) {
                break;
            }
        }

        $focus_risk_blocks = [];
        foreach((array) ($value['focus_risk_blocks'] ?? []) as $item) {
            $row = $normalize_row($item);
            if($row) {
                $focus_risk_blocks[] = $row;
            }
            if(count($focus_risk_blocks) >= 4) {
                break;
            }
        }

        $all_blocks = [];
        foreach((array) ($value['all_blocks'] ?? []) as $item) {
            $row = $normalize_row($item);
            if($row) {
                $all_blocks[] = $row;
            }
            if(count($all_blocks) >= 18) {
                break;
            }
        }

        $summary = is_array($value['summary'] ?? null) ? (array) $value['summary'] : [];

        return [
            'has_blocks' => !empty($all_blocks),
            'summary' => [
                'tracked_blocks' => max(0, (int) ($summary['tracked_blocks'] ?? count($all_blocks))),
                'signal_blocks' => max(0, (int) ($summary['signal_blocks'] ?? count(array_filter($all_blocks, static fn($item): bool => (int) ($item['signal_score'] ?? 0) > 0)))),
                'focus_risk_blocks' => count($focus_risk_blocks),
                'zero_signal_blocks' => max(0, (int) ($summary['zero_signal_blocks'] ?? count(array_filter($all_blocks, static fn($item): bool => (int) ($item['signal_score'] ?? 0) === 0)))),
            ],
            'top_signal_blocks' => $top_signal_blocks,
            'focus_risk_blocks' => $focus_risk_blocks,
            'all_blocks' => $all_blocks,
        ];
    }

    private function normalize_app_review_signal_protection_summary($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            $value = [];
        }

        $normalize_row = function($item): ?array {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(!is_array($item)) {
                return null;
            }

            return [
                'block_id' => max(0, (int) ($item['block_id'] ?? 0)),
                'label' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($item['label'] ?? '', 160)),
                'status' => $this->sanitize_ai_string($item['status'] ?? '', 48),
                'planned_action' => $this->sanitize_ai_string($item['planned_action'] ?? '', 64),
                'reason' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($item['reason'] ?? '', 220)),
            ];
        };

        $build_rows = function(array $items) use ($normalize_row): array {
            $rows = [];
            foreach($items as $item) {
                $row = $normalize_row($item);
                if($row) {
                    $rows[] = $row;
                }
                if(count($rows) >= 4) {
                    break;
                }
            }
            return $rows;
        };

        $protected_block_ids = array_values(array_unique(array_filter(array_map(static function($item): int {
            return max(0, (int) $item);
        }, (array) ($value['protected_block_ids'] ?? [])))));

        $kept_signal_blocks = $build_rows((array) ($value['kept_signal_blocks'] ?? []));
        $repositioned_focus_blocks = $build_rows((array) ($value['repositioned_focus_blocks'] ?? []));
        $summary = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($value['summary'] ?? '', 320));

        return [
            'has_items' => $summary !== '' || !empty($kept_signal_blocks) || !empty($repositioned_focus_blocks),
            'summary' => $summary,
            'protected_block_ids' => $protected_block_ids,
            'kept_signal_blocks' => $kept_signal_blocks,
            'repositioned_focus_blocks' => $repositioned_focus_blocks,
        ];
    }

    private function build_app_review_signal_protection_summary(array $block_attribution_payload, array $layout_actions = []): array {
        $block_attribution_payload = $this->normalize_app_review_block_attribution_payload($block_attribution_payload);
        $layout_actions = $this->normalize_app_review_layout_actions($layout_actions);
        $action_map = [];

        foreach($layout_actions as $action) {
            $block_id = (int) ($action['block_id'] ?? 0);
            if($block_id <= 0 || isset($action_map[$block_id])) {
                continue;
            }

            $action_map[$block_id] = [
                'action' => (string) ($action['action'] ?? ''),
                'why' => (string) ($action['why'] ?? ''),
            ];
        }

        $protected_block_ids = [];
        $kept_signal_blocks = [];
        $repositioned_focus_blocks = [];

        foreach((array) ($block_attribution_payload['all_blocks'] ?? []) as $block) {
            $block_id = (int) ($block['block_id'] ?? 0);
            if($block_id <= 0) {
                continue;
            }

            $status = (string) ($block['status'] ?? '');
            $label = $this->normalize_app_review_channel_copy((string) (($block['label'] ?? '') ?: ($block['type'] ?? 'Blok')));
            $planned_action = (string) (($action_map[$block_id]['action'] ?? ''));

            if(in_array($status, ['high_signal', 'contributing'], true)) {
                $protected_block_ids[] = $block_id;
                if(in_array($planned_action, ['hide_for_now', 'consider_remove'], true)) {
                    $planned_action = 'keep';
                }

                $reason = $status === 'high_signal'
                    ? 'Ovaj blok ostaje aktivan jer vec donosi mjerljiv signal i ne treba ga gasiti.'
                    : 'Ovaj blok vec doprinosi rezultatu pa ga vrijedi doraditi ili bolje pozicionirati, a ne ukloniti.';

                if(in_array($planned_action, ['move_up', 'move_down', 'keep_top', 'keep_after_primary'], true)) {
                    $reason .= ' U planu se po potrebi samo preslaguje kako bi jos jasnije podrzao glavni cilj.';
                }

                $kept_signal_blocks[] = [
                    'block_id' => $block_id,
                    'label' => $label,
                    'status' => $status,
                    'planned_action' => $planned_action !== '' ? $planned_action : 'keep',
                    'reason' => $reason,
                ];
                continue;
            }

            if(!in_array($status, ['focus_risk', 'critical_focus_risk'], true) || !in_array($planned_action, ['move_down', 'consider_remove', 'hide_for_now'], true)) {
                continue;
            }

            $reason = $planned_action === 'move_down'
                ? 'Ovaj blok ide nize jer prerano uzima paznju bez mjerljivog rezultata.'
                : 'Ovaj blok trenutno nema dokazani rezultat pa vise nije prioritet u vrhu aplikacije.';

            $repositioned_focus_blocks[] = [
                'block_id' => $block_id,
                'label' => $label,
                'status' => $status,
                'planned_action' => $planned_action,
                'reason' => $reason,
            ];
        }

        $kept_signal_blocks = array_slice($kept_signal_blocks, 0, 4);
        $repositioned_focus_blocks = array_slice($repositioned_focus_blocks, 0, 4);
        $protected_block_ids = array_values(array_unique($protected_block_ids));

        $summary = match(true) {
            !empty($kept_signal_blocks) && !empty($repositioned_focus_blocks) => 'U novom planu zadrzani su blokovi koji vec donose signal, a nize su pomaknuti oni koji prerano uzimaju paznju bez rezultata.',
            !empty($kept_signal_blocks) => 'U novom planu zadrzani su blokovi koji vec donose signal kako se ne bi pokvarilo ono sto vec radi.',
            !empty($repositioned_focus_blocks) => 'U novom planu nize su pomaknuti blokovi koji prerano uzimaju fokus bez rezultata kako bi glavni korak bio jasniji.',
            default => '',
        };

        return $this->normalize_app_review_signal_protection_summary([
            'summary' => $summary,
            'protected_block_ids' => $protected_block_ids,
            'kept_signal_blocks' => $kept_signal_blocks,
            'repositioned_focus_blocks' => $repositioned_focus_blocks,
        ]);
    }

    private function enforce_app_review_signal_safe_layout_actions(array $layout_actions, array $block_attribution_payload): array {
        $layout_actions = $this->normalize_app_review_layout_actions($layout_actions);
        $block_attribution_payload = $this->normalize_app_review_block_attribution_payload($block_attribution_payload);
        $protected_signal_ids = [];

        foreach((array) ($block_attribution_payload['all_blocks'] ?? []) as $block) {
            $block_id = (int) ($block['block_id'] ?? 0);
            $status = (string) ($block['status'] ?? '');

            if($block_id > 0 && in_array($status, ['high_signal', 'contributing'], true)) {
                $protected_signal_ids[$block_id] = true;
            }
        }

        if(empty($protected_signal_ids)) {
            return $layout_actions;
        }

        return array_values(array_filter($layout_actions, static function($action) use ($protected_signal_ids): bool {
            $block_id = (int) ($action['block_id'] ?? 0);
            $action_key = (string) ($action['action'] ?? '');

            if($block_id <= 0 || !isset($protected_signal_ids[$block_id])) {
                return true;
            }

            return !in_array($action_key, ['hide_for_now', 'consider_remove'], true);
        }));
    }

    private function normalize_app_review_block_delta_summary($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            $value = [];
        }

        $normalize_delta_row = function($item): ?array {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(!is_array($item)) {
                return null;
            }

            return [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'label' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($item['label'] ?? '', 160)),
                'type' => $this->sanitize_ai_string($item['type'] ?? '', 64),
                'previous_signal' => max(0, (int) ($item['previous_signal'] ?? 0)),
                'current_signal' => max(0, (int) ($item['current_signal'] ?? 0)),
                'delta_signal' => (int) ($item['delta_signal'] ?? 0),
                'direction' => in_array((string) ($item['direction'] ?? 'same'), ['up', 'down', 'same'], true) ? (string) ($item['direction'] ?? 'same') : 'same',
            ];
        };

        $build_delta_rows = function(array $items) use ($normalize_delta_row): array {
            $rows = [];
            foreach($items as $item) {
                $row = $normalize_delta_row($item);
                if($row) {
                    $rows[] = $row;
                }
                if(count($rows) >= 4) {
                    break;
                }
            }
            return $rows;
        };

        return [
            'top_gainers' => $build_delta_rows((array) ($value['top_gainers'] ?? [])),
            'top_decliners' => $build_delta_rows((array) ($value['top_decliners'] ?? [])),
            'current_top_blocks' => $this->normalize_app_review_block_attribution_payload([
                'top_signal_blocks' => (array) ($value['current_top_blocks'] ?? []),
            ])['top_signal_blocks'],
            'focus_risk_blocks' => $this->normalize_app_review_block_attribution_payload([
                'focus_risk_blocks' => (array) ($value['focus_risk_blocks'] ?? []),
            ])['focus_risk_blocks'],
        ];
    }

    private function get_app_review_block_delta_summary(array $before_payload, array $after_payload): array {
        $before_payload = $this->normalize_app_review_block_attribution_payload($before_payload);
        $after_payload = $this->normalize_app_review_block_attribution_payload($after_payload);
        $before_rows = [];
        $after_rows = [];

        foreach((array) ($before_payload['all_blocks'] ?? []) as $row) {
            $before_rows[(int) ($row['block_id'] ?? 0)] = $row;
        }

        foreach((array) ($after_payload['all_blocks'] ?? []) as $row) {
            $after_rows[(int) ($row['block_id'] ?? 0)] = $row;
        }

        $delta_rows = [];
        foreach(array_unique(array_merge(array_keys($before_rows), array_keys($after_rows))) as $block_id) {
            $block_id = (int) $block_id;

            if($block_id <= 0) {
                continue;
            }

            $before_row = $before_rows[$block_id] ?? [];
            $after_row = $after_rows[$block_id] ?? [];
            $previous_signal = (int) ($before_row['signal_score'] ?? 0);
            $current_signal = (int) ($after_row['signal_score'] ?? 0);
            $delta_signal = $current_signal - $previous_signal;

            if($delta_signal === 0) {
                continue;
            }

            $delta_rows[] = [
                'block_id' => $block_id,
                'label' => (string) (($after_row['label'] ?? '') ?: ($before_row['label'] ?? '')),
                'type' => (string) (($after_row['type'] ?? '') ?: ($before_row['type'] ?? '')),
                'previous_signal' => $previous_signal,
                'current_signal' => $current_signal,
                'delta_signal' => $delta_signal,
                'direction' => $delta_signal > 0 ? 'up' : 'down',
            ];
        }

        usort($delta_rows, static function($a, $b) {
            return abs((int) ($b['delta_signal'] ?? 0)) <=> abs((int) ($a['delta_signal'] ?? 0));
        });

        $top_gainers = array_values(array_slice(array_filter($delta_rows, static fn($item): bool => (int) ($item['delta_signal'] ?? 0) > 0), 0, 4));
        $top_decliners = array_values(array_slice(array_filter($delta_rows, static fn($item): bool => (int) ($item['delta_signal'] ?? 0) < 0), 0, 4));

        return [
            'top_gainers' => $top_gainers,
            'top_decliners' => $top_decliners,
            'current_top_blocks' => (array) ($after_payload['top_signal_blocks'] ?? []),
            'focus_risk_blocks' => (array) ($after_payload['focus_risk_blocks'] ?? []),
        ];
    }

    private function has_app_review_evolution_window_elapsed(string $recommended_at, int $days, ?string $reference_datetime = null): bool {
        if($recommended_at === '' || $days < 1) {
            return false;
        }

        try {
            $recommended_datetime = new \DateTimeImmutable($recommended_at);
            $reference = $reference_datetime ? new \DateTimeImmutable($reference_datetime) : new \DateTimeImmutable();

            return $recommended_datetime->add(new \DateInterval('P' . $days . 'D')) <= $reference;
        } catch(\Throwable $exception) {
            return false;
        }
    }

    private function get_app_review_evolution_window_status(string $recommended_at, ?string $measured_at, int $days): string {
        if(!empty($measured_at)) {
            return 'measured';
        }

        return $this->has_app_review_evolution_window_elapsed($recommended_at, $days) ? 'ready' : 'pending';
    }

    private function normalize_app_review_evolution_delta_items($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach($value as $item) {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(!is_array($item) || empty($item['metric'])) {
                continue;
            }

            $normalized[] = [
                'metric' => $this->sanitize_ai_string($item['metric'] ?? '', 48),
                'previous' => (int) ($item['previous'] ?? 0),
                'current' => (int) ($item['current'] ?? 0),
                'delta' => (int) ($item['delta'] ?? 0),
                'direction' => in_array((string) ($item['direction'] ?? 'same'), ['up', 'down', 'same'], true) ? (string) ($item['direction'] ?? 'same') : 'same',
            ];
        }

        return array_slice($normalized, 0, 5);
    }

    private function normalize_app_review_evolution_measurement($value, string $recommended_at = '', int $days = 7): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            $value = [];
        }

        $measured_at = !empty($value['measured_at']) ? (string) $value['measured_at'] : null;

        return [
            'status' => $this->get_app_review_evolution_window_status($recommended_at, $measured_at, $days),
            'measured_at' => $measured_at,
            'performance' => $this->normalize_app_review_performance_snapshot($value['performance'] ?? []),
            'delta' => $this->normalize_app_review_evolution_delta_items($value['delta'] ?? []),
            'block_summary' => $this->normalize_app_review_block_delta_summary($value['block_summary'] ?? []),
        ];
    }

    private function normalize_app_review_evolution_memory($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach($value as $item) {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(!is_array($item)) {
                continue;
            }

            $recommended_at = (string) ($item['recommended_at'] ?? '');
            $recommended = $item['recommended'] ?? [];
            $applied = $item['applied'] ?? [];

            if($recommended instanceof \stdClass) {
                $recommended = (array) $recommended;
            }

            if($applied instanceof \stdClass) {
                $applied = (array) $applied;
            }

            $normalized[] = [
                'review_key' => $this->sanitize_ai_string($item['review_key'] ?? '', 64),
                'recommended_at' => $recommended_at ?: null,
                'analysis_mode' => in_array((string) ($item['analysis_mode'] ?? 'initial'), ['initial', 'evolution'], true) ? (string) ($item['analysis_mode'] ?? 'initial') : 'initial',
                'quality_score' => max(0, (int) ($item['quality_score'] ?? 0)),
                'quality_level' => $this->sanitize_ai_string($item['quality_level'] ?? 'foundation', 24),
                'performance_before' => $this->normalize_app_review_performance_snapshot($item['performance_before'] ?? []),
                'block_attribution_before' => $this->normalize_app_review_block_attribution_payload($item['block_attribution_before'] ?? []),
                'recommended' => [
                    'headline' => $this->normalize_app_review_channel_copy((string) ($recommended['headline'] ?? '')),
                    'summary' => $this->normalize_app_review_channel_copy((string) ($recommended['summary'] ?? '')),
                    'top_recommendation' => $this->normalize_app_review_channel_copy((string) ($recommended['top_recommendation'] ?? '')),
                    'first_move' => $this->normalize_app_review_channel_copy((string) ($recommended['first_move'] ?? '')),
                    'next_move' => $this->normalize_app_review_channel_copy((string) ($recommended['next_move'] ?? '')),
                    'theme_name' => $this->normalize_app_review_channel_copy((string) ($recommended['theme_name'] ?? '')),
                    'theme_summary' => $this->normalize_app_review_channel_copy((string) ($recommended['theme_summary'] ?? '')),
                    'primary_block' => $this->normalize_app_review_primary_block_plan($recommended['primary_block'] ?? []),
                    'layout_actions' => $this->normalize_app_review_layout_actions($recommended['layout_actions'] ?? []),
                ],
                'applied' => [
                    'theme_applied_at' => !empty($applied['theme_applied_at']) ? (string) $applied['theme_applied_at'] : null,
                    'primary_applied_at' => !empty($applied['primary_applied_at']) ? (string) $applied['primary_applied_at'] : null,
                    'layout_applied_at' => !empty($applied['layout_applied_at']) ? (string) $applied['layout_applied_at'] : null,
                    'layout_reverted_at' => !empty($applied['layout_reverted_at']) ? (string) $applied['layout_reverted_at'] : null,
                    'theme_key' => $this->sanitize_ai_string($applied['theme_key'] ?? '', 64),
                    'layout_summary' => [
                        'reordered_blocks' => max(0, (int) (($applied['layout_summary']['reordered_blocks'] ?? 0))),
                        'hidden_blocks' => max(0, (int) (($applied['layout_summary']['hidden_blocks'] ?? 0))),
                        'updated_blocks' => max(0, (int) (($applied['layout_summary']['updated_blocks'] ?? 0))),
                    ],
                    'layout_rollback_summary' => [
                        'restored_blocks' => max(0, (int) (($applied['layout_rollback_summary']['restored_blocks'] ?? 0))),
                        're_enabled_blocks' => max(0, (int) (($applied['layout_rollback_summary']['re_enabled_blocks'] ?? 0))),
                    ],
                ],
                'evaluation_7d' => $this->normalize_app_review_evolution_measurement($item['evaluation_7d'] ?? [], $recommended_at, 7),
                'evaluation_30d' => $this->normalize_app_review_evolution_measurement($item['evaluation_30d'] ?? [], $recommended_at, 30),
            ];
        }

        usort($normalized, static function($a, $b) {
            return strcmp((string) ($b['recommended_at'] ?? ''), (string) ($a['recommended_at'] ?? ''));
        });

        return array_slice($normalized, 0, 12);
    }

    private function refresh_app_review_evolution_memory(array $cycles, array $current_performance, ?string $current_datetime = null, array $current_block_attribution = []): array {
        $cycles = $this->normalize_app_review_evolution_memory($cycles);
        $current_performance = $this->normalize_app_review_performance_snapshot($current_performance);
        $current_block_attribution = $this->normalize_app_review_block_attribution_payload($current_block_attribution);
        $current_datetime = $current_datetime ?: get_date();

        foreach($cycles as &$cycle) {
            $recommended_at = (string) ($cycle['recommended_at'] ?? '');

            if(
                empty($cycle['evaluation_7d']['measured_at'])
                && $this->has_app_review_evolution_window_elapsed($recommended_at, 7, $current_datetime)
            ) {
                $cycle['evaluation_7d'] = [
                    'status' => 'measured',
                    'measured_at' => $current_datetime,
                    'performance' => $current_performance,
                    'delta' => $this->get_app_review_performance_delta((array) ($cycle['performance_before'] ?? []), $current_performance),
                    'block_summary' => $this->get_app_review_block_delta_summary((array) ($cycle['block_attribution_before'] ?? []), $current_block_attribution),
                ];
            }

            if(
                empty($cycle['evaluation_30d']['measured_at'])
                && $this->has_app_review_evolution_window_elapsed($recommended_at, 30, $current_datetime)
            ) {
                $cycle['evaluation_30d'] = [
                    'status' => 'measured',
                    'measured_at' => $current_datetime,
                    'performance' => $current_performance,
                    'delta' => $this->get_app_review_performance_delta((array) ($cycle['performance_before'] ?? []), $current_performance),
                    'block_summary' => $this->get_app_review_block_delta_summary((array) ($cycle['block_attribution_before'] ?? []), $current_block_attribution),
                ];
            }
        }
        unset($cycle);

        return $cycles;
    }

    private function build_app_review_evolution_cycle(array $review): array {
        $generated_at = (string) ($review['generated_at'] ?? get_date());

        return [
            'review_key' => $generated_at !== '' ? $generated_at : uniqid('app_review_', true),
            'recommended_at' => $generated_at ?: null,
            'analysis_mode' => in_array((string) ($review['analysis_mode'] ?? 'initial'), ['initial', 'evolution'], true) ? (string) ($review['analysis_mode'] ?? 'initial') : 'initial',
            'quality_score' => max(0, (int) ($review['quality_score'] ?? 0)),
            'quality_level' => $this->sanitize_ai_string($review['quality_level'] ?? 'foundation', 24),
            'performance_before' => $this->normalize_app_review_performance_snapshot($review['performance_snapshot'] ?? []),
            'block_attribution_before' => $this->normalize_app_review_block_attribution_payload($review['block_attribution_snapshot'] ?? []),
            'recommended' => [
                'headline' => (string) ($review['headline'] ?? ''),
                'summary' => (string) ($review['summary'] ?? ''),
                'top_recommendation' => (string) ($review['top_recommendation'] ?? ''),
                'first_move' => (string) ($review['first_move'] ?? ''),
                'next_move' => (string) ($review['next_move'] ?? ''),
                'theme_name' => (string) (($review['theme_pack']['name'] ?? '') ?: ''),
                'theme_summary' => (string) (($review['theme_pack']['summary'] ?? '') ?: ''),
                'primary_block' => $this->normalize_app_review_primary_block_plan($review['primary_block_plan'] ?? []),
                'layout_actions' => $this->normalize_app_review_layout_actions($review['layout_actions'] ?? []),
            ],
            'applied' => [
                'theme_applied_at' => null,
                'primary_applied_at' => null,
                'layout_applied_at' => null,
                'layout_reverted_at' => null,
                'theme_key' => '',
                'layout_summary' => [
                    'reordered_blocks' => 0,
                    'hidden_blocks' => 0,
                    'updated_blocks' => 0,
                ],
                'layout_rollback_summary' => [
                    'restored_blocks' => 0,
                    're_enabled_blocks' => 0,
                ],
            ],
            'evaluation_7d' => [
                'measured_at' => null,
                'performance' => $this->get_default_app_review_performance_snapshot(),
                'delta' => [],
                'block_summary' => [],
            ],
            'evaluation_30d' => [
                'measured_at' => null,
                'performance' => $this->get_default_app_review_performance_snapshot(),
                'delta' => [],
                'block_summary' => [],
            ],
        ];
    }

    private function upsert_app_review_evolution_cycle(array $cycles, array $new_cycle): array {
        $review_key = (string) ($new_cycle['review_key'] ?? '');

        if($review_key === '') {
            return $cycles;
        }

        $updated = [];

        foreach($cycles as $cycle) {
            if((string) ($cycle['review_key'] ?? '') === $review_key) {
                continue;
            }

            $updated[] = $cycle;
        }

        array_unshift($updated, $new_cycle);

        return array_slice($updated, 0, 12);
    }

    private function get_link_additional_by_id(int $selected_link_id): array {
        if($selected_link_id <= 0) {
            return [];
        }

        $link = db()->where('link_id', $selected_link_id)->where('user_id', $this->user->user_id)->getOne('links', ['additional']);

        return $link ? $this->normalize_json_to_array($link->additional ?? null) : [];
    }

    private function get_link_ai_editor_snapshot_by_id(int $selected_link_id): array {
        if($selected_link_id <= 0) {
            return [
                'additional' => [],
                'last_datetime' => null,
            ];
        }

        $link = db()->where('link_id', $selected_link_id)->where('user_id', $this->user->user_id)->getOne('links', ['additional', 'last_datetime']);

        return [
            'additional' => $link ? $this->normalize_json_to_array($link->additional ?? null) : [],
            'last_datetime' => !empty($link->last_datetime) ? (string) $link->last_datetime : null,
        ];
    }

    private function get_ai_bundle_freshness_payload(array $additional, ?string $last_datetime = null, ?string $fallback_recommended_at = null): array {
        $apply_state = $this->normalize_json_to_array($additional['fcc_ai_theme_apply_state'] ?? []);
        $review_summary = $this->normalize_json_to_array($additional['fcc_ai_review_summary'] ?? []);
        $recommended_at = trim((string) ($apply_state['recommended_at'] ?? ($review_summary['generated_at'] ?? ($fallback_recommended_at ?? ''))));
        $last_datetime = trim((string) ($last_datetime ?? ''));
        $reference_points = array_filter([
            $recommended_at,
            trim((string) ($apply_state['applied_at'] ?? '')),
            trim((string) ($apply_state['theme_applied_at'] ?? '')),
            trim((string) ($apply_state['primary_applied_at'] ?? '')),
            trim((string) ($apply_state['layout_applied_at'] ?? '')),
            trim((string) ($apply_state['layout_reverted_at'] ?? '')),
        ]);
        $reference_at = '';

        foreach($reference_points as $candidate) {
            if($reference_at === '' || strcmp($candidate, $reference_at) > 0) {
                $reference_at = $candidate;
            }
        }

        $is_stale = false;

        if($last_datetime !== '' && $reference_at !== '') {
            try {
                $is_stale = (new \DateTimeImmutable($last_datetime)) > (new \DateTimeImmutable($reference_at));
            } catch(\Throwable $exception) {
                $is_stale = strcmp($last_datetime, $reference_at) > 0;
            }
        }

        return [
            'is_stale' => $is_stale,
            'status' => $is_stale ? 'evolved' : 'aligned',
            'has_changes_since_recommendation' => $is_stale,
            'recommended_at' => $recommended_at !== '' ? $recommended_at : null,
            'last_changed_at' => $last_datetime !== '' ? $last_datetime : null,
            'notice_level' => $is_stale ? 'info' : '',
            'analysis_mode_hint' => $is_stale ? 'evolution_on_current_live_app' : 'standard_continuation',
            'message' => $is_stale ? l('link.settings.ai_bundle_stale_notice') : '',
        ];
    }

    private function get_weekly_ai_focus_app_context(array $app_structure_payload, ?array $latest_app_review = null, ?array $previous_plan = null): array {
        $focus_app = $this->get_weekly_plan_focus_app($app_structure_payload, $latest_app_review);

        if(!$focus_app) {
            return [
                'link_id' => 0,
                'name' => '',
                'url' => '',
                'public_url' => '',
                'selection_source' => 'none',
                'selection_reason' => '',
                'current_structure' => $this->get_weekly_ai_app_structure_snapshot(null),
                'visual_context' => [
                    'scope' => 'none',
                    'primary_visual_url' => '',
                    'visual_segments' => $this->get_default_app_review_visual_segments(),
                ],
                'latest_app_review' => [
                    'exists' => !empty($latest_app_review),
                    'matches_focus_app' => false,
                    'generated_at' => !empty($latest_app_review['generated_at']) ? (string) $latest_app_review['generated_at'] : null,
                    'headline' => '',
                    'top_recommendation' => '',
                    'first_move' => '',
                    'quality_score' => 0,
                    'quality_level' => '',
                ],
                'current_state_against_latest_review' => $this->get_ai_bundle_freshness_payload([], null, !empty($latest_app_review['generated_at']) ? (string) $latest_app_review['generated_at'] : null),
                'current_state_against_previous_plan' => $this->get_ai_bundle_freshness_payload([], null, !empty($previous_plan['generated_at']) ? (string) $previous_plan['generated_at'] : null),
            ];
        }

        $focus_link_id = (int) ($focus_app['link_id'] ?? 0);
        $editor_snapshot = $this->get_link_ai_editor_snapshot_by_id($focus_link_id);
        $last_changed_at = (string) (($focus_app['last_datetime'] ?? '') ?: ($editor_snapshot['last_datetime'] ?? '') ?: ($focus_app['datetime'] ?? ''));
        $latest_review_matches_focus_app = $focus_link_id > 0 && (int) ($latest_app_review['selected_link_id'] ?? 0) === $focus_link_id;
        $selection_source = 'main_app';
        $selection_reason = 'Weekly plan koristi glavnu FCC aplikaciju kao bazu.';

        if($latest_review_matches_focus_app) {
            $selection_source = 'latest_app_review';
            $selection_reason = 'Weekly plan prati aplikaciju koja je zadnja bila analizirana kroz AI app review.';
        } elseif(
            $focus_link_id > 0
            && (int) ($focus_app['link_id'] ?? 0) !== (int) (($app_structure_payload['main_app']['link_id'] ?? 0))
        ) {
            $selection_source = 'latest_updated_app';
            $selection_reason = 'Weekly plan koristi trenutno najaktivniju aplikaciju jer je novija od kanonske glavne aplikacije.';
        }

        return [
            'link_id' => $focus_link_id,
            'name' => (string) (($focus_app['name'] ?? '') ?: ($focus_app['url'] ?? '')),
            'url' => (string) ($focus_app['url'] ?? ''),
            'public_url' => (string) ($focus_app['public_url'] ?? ''),
            'selection_source' => $selection_source,
            'selection_reason' => $selection_reason,
            'current_structure' => $this->get_weekly_ai_app_structure_snapshot($focus_app),
            'visual_context' => [
                'scope' => (string) ($focus_app['primary_visual_scope'] ?? 'none'),
                'primary_visual_url' => (string) ($focus_app['primary_visual_url'] ?? ''),
                'visual_segments' => (array) ($focus_app['visual_segments'] ?? $this->get_default_app_review_visual_segments()),
            ],
            'latest_app_review' => [
                'exists' => !empty($latest_app_review),
                'matches_focus_app' => $latest_review_matches_focus_app,
                'generated_at' => !empty($latest_app_review['generated_at']) ? (string) $latest_app_review['generated_at'] : null,
                'headline' => $latest_review_matches_focus_app ? (string) ($latest_app_review['headline'] ?? '') : '',
                'top_recommendation' => $latest_review_matches_focus_app ? (string) ($latest_app_review['top_recommendation'] ?? '') : '',
                'first_move' => $latest_review_matches_focus_app ? (string) ($latest_app_review['first_move'] ?? '') : '',
                'quality_score' => $latest_review_matches_focus_app ? (int) ($latest_app_review['quality_score'] ?? 0) : 0,
                'quality_level' => $latest_review_matches_focus_app ? (string) ($latest_app_review['quality_level'] ?? '') : '',
            ],
            'current_state_against_latest_review' => $this->get_ai_bundle_freshness_payload(
                (array) ($editor_snapshot['additional'] ?? []),
                $last_changed_at !== '' ? $last_changed_at : null,
                !empty($latest_app_review['generated_at']) ? (string) $latest_app_review['generated_at'] : null
            ),
            'current_state_against_previous_plan' => $this->get_ai_bundle_freshness_payload(
                (array) ($editor_snapshot['additional'] ?? []),
                $last_changed_at !== '' ? $last_changed_at : null,
                !empty($previous_plan['generated_at']) ? (string) $previous_plan['generated_at'] : null
            ),
        ];
    }

    private function get_app_review_editor_actions_payload(int $selected_link_id, ?array $review = null): array {
        $payload = [
            'link_id' => max(0, $selected_link_id),
            'can_apply_blocks' => false,
            'can_apply_colors' => false,
            'can_restore' => false,
            'has_any' => false,
            'freshness' => [
                'is_stale' => false,
                'recommended_at' => null,
                'last_changed_at' => null,
                'message' => '',
            ],
        ];

        if($selected_link_id <= 0) {
            return $payload;
        }

        $link_snapshot = $this->get_link_ai_editor_snapshot_by_id($selected_link_id);
        $additional = $link_snapshot['additional'];

        $theme_pack = $this->normalize_app_review_theme_pack($additional['fcc_ai_theme_pack'] ?? []);
        if(empty($theme_pack) && !empty($review)) {
            $theme_pack = $this->normalize_app_review_theme_pack($review['theme_pack'] ?? [], (array) ($review['color_palette'] ?? []));
        }

        $copy_suggestions = $this->normalize_app_review_copy_suggestions($additional['fcc_ai_copy_suggestions'] ?? []);
        if(empty($copy_suggestions) && !empty($review)) {
            $copy_suggestions = $this->normalize_app_review_copy_suggestions($review['copy_suggestions'] ?? []);
        }

        $layout_actions = $this->normalize_app_review_layout_actions($additional['fcc_ai_layout_actions'] ?? []);
        if(empty($layout_actions) && !empty($review)) {
            $layout_actions = $this->normalize_app_review_layout_actions($review['layout_actions'] ?? []);
        }

        $missing_block_recommendations = $this->normalize_app_review_missing_block_recommendations($additional['fcc_ai_missing_block_recommendations'] ?? []);
        if(empty($missing_block_recommendations) && !empty($review)) {
            $missing_block_recommendations = $this->normalize_app_review_missing_block_recommendations($review['missing_block_recommendations'] ?? []);
        }

        $ideal_block_order = $this->normalize_app_review_visible_list((array) ($additional['fcc_ai_ideal_block_order'] ?? []));
        if(empty($ideal_block_order) && !empty($review)) {
            $ideal_block_order = $this->normalize_app_review_visible_list((array) ($review['ideal_block_order'] ?? []));
        }

        $final_block_plan = $this->normalize_ai_final_block_plan($additional['fcc_ai_final_block_plan'] ?? []);
        if(empty($final_block_plan) && !empty($review)) {
            $final_block_plan = $this->normalize_ai_final_block_plan($review['final_block_plan'] ?? []);
        }
        if(empty($final_block_plan) && !empty($review)) {
            $final_block_plan = $this->build_app_review_final_block_plan(
                (array) ($review['block_attribution_snapshot'] ?? []),
                (array) ($review['layout_actions'] ?? []),
                (array) ($review['ideal_block_order'] ?? []),
                (array) ($review['missing_block_recommendations'] ?? [])
            );
        }

        $bundle_backup = $this->normalize_json_to_array($additional['fcc_ai_bundle_backup'] ?? []);

        $payload['can_apply_blocks'] = !empty($copy_suggestions) || !empty($layout_actions) || !empty($missing_block_recommendations) || !empty($ideal_block_order) || !empty($final_block_plan);
        $payload['can_apply_colors'] = !empty($theme_pack);
        $payload['can_restore'] = !empty($bundle_backup['captured_at']);
        $payload['has_any'] = $payload['can_apply_blocks'] || $payload['can_apply_colors'] || $payload['can_restore'];
        $payload['freshness'] = $this->get_ai_bundle_freshness_payload(
            $additional,
            (string) ($link_snapshot['last_datetime'] ?? ''),
            (string) ($review['generated_at'] ?? '')
        );

        return $payload;
    }

    private function summarize_app_review_evolution_delta(array $delta): string {
        foreach($delta as $item) {
            if((string) ($item['metric'] ?? '') !== 'total_signal') {
                continue;
            }

            $change = (int) ($item['delta'] ?? 0);

            if($change > 0) {
                return sprintf(l('link.settings.ai_evolution_result_positive'), nr($change));
            }

            if($change < 0) {
                return sprintf(l('link.settings.ai_evolution_result_negative'), nr(abs($change)));
            }

            return l('link.settings.ai_evolution_result_same');
        }

        return l('link.settings.ai_evolution_result_same');
    }

    private function get_app_review_display_evolution_payload(int $selected_link_id, array $current_performance = [], array $current_block_attribution = []): array {
        $additional = $this->get_link_additional_by_id($selected_link_id);
        $original_memory = $this->normalize_app_review_evolution_memory($additional['fcc_ai_evolution_memory'] ?? []);
        $memory = $this->refresh_app_review_evolution_memory(
            $original_memory,
            $current_performance,
            null,
            $current_block_attribution
        );
        $active_cycle = $memory[0] ?? null;

        if(json_encode($memory) !== json_encode($original_memory)) {
            $additional['fcc_ai_evolution_memory'] = $memory;

            db()->where('link_id', $selected_link_id)->where('user_id', $this->user->user_id)->update('links', [
                'additional' => json_encode($additional),
            ]);

            cache()->deleteItemsByTag('link_id=' . $selected_link_id);
            cache()->deleteItem('link?link_id=' . $selected_link_id);
        }

        if(!$active_cycle) {
            return [
                'has_memory' => false,
                'active_cycle' => null,
                'display_measurements' => [],
                'recent_measured_cycles' => [],
            ];
        }

        foreach(['evaluation_7d', 'evaluation_30d'] as $measurement_key) {
            $active_cycle[$measurement_key]['summary'] = $this->summarize_app_review_evolution_delta((array) ($active_cycle[$measurement_key]['delta'] ?? []));
        }

        $display_measurements = [];
        foreach(['evaluation_7d', 'evaluation_30d'] as $measurement_key) {
            $active_measurement = is_array($active_cycle[$measurement_key] ?? null) ? $active_cycle[$measurement_key] : [];
            $active_measurement['summary'] = $this->summarize_app_review_evolution_delta((array) ($active_measurement['delta'] ?? []));
            $active_measurement['source_recommended_at'] = $active_cycle['recommended_at'] ?? null;
            $active_measurement['is_from_active_cycle'] = true;
            $active_measurement['source_note'] = '';

            if(!empty($active_measurement['measured_at'])) {
                $display_measurements[$measurement_key] = $active_measurement;
                continue;
            }

            $fallback_measurement = $active_measurement;

            foreach($memory as $cycle) {
                $measurement = is_array($cycle[$measurement_key] ?? null) ? $cycle[$measurement_key] : [];

                if(empty($measurement['measured_at'])) {
                    continue;
                }

                $measurement['summary'] = $this->summarize_app_review_evolution_delta((array) ($measurement['delta'] ?? []));
                $measurement['source_recommended_at'] = $cycle['recommended_at'] ?? null;
                $measurement['is_from_active_cycle'] = ((string) ($cycle['review_key'] ?? '')) === ((string) ($active_cycle['review_key'] ?? ''));
                $measurement['source_note'] = $measurement['is_from_active_cycle']
                    ? ''
                    : 'Prikazan je zadnji izmjereni rezultat prethodnog AI ciklusa dok nova analiza još čeka svoje mjerenje.';
                $fallback_measurement = $measurement;
                break;
            }

            $display_measurements[$measurement_key] = $fallback_measurement;
        }

        $recent_measured_cycles = [];

        foreach($memory as $cycle) {
            if(empty($cycle['evaluation_7d']['measured_at']) && empty($cycle['evaluation_30d']['measured_at'])) {
                continue;
            }

            $recent_measured_cycles[] = [
                'recommended_at' => $cycle['recommended_at'] ?? null,
                'headline' => (string) ($cycle['recommended']['headline'] ?? ''),
                'summary_7d' => $this->summarize_app_review_evolution_delta((array) ($cycle['evaluation_7d']['delta'] ?? [])),
                'summary_30d' => $this->summarize_app_review_evolution_delta((array) ($cycle['evaluation_30d']['delta'] ?? [])),
                'evaluation_7d' => $cycle['evaluation_7d'],
                'evaluation_30d' => $cycle['evaluation_30d'],
            ];

            if(count($recent_measured_cycles) >= 3) {
                break;
            }
        }

        return [
            'has_memory' => true,
            'active_cycle' => $active_cycle,
            'display_measurements' => $display_measurements,
            'recent_measured_cycles' => $recent_measured_cycles,
        ];
    }

    private function sync_app_review_assets_to_editor(int $selected_link_id, array $review, \stdClass $preferences): \stdClass {
        if($selected_link_id <= 0) {
            return $preferences;
        }

        $theme_pack = $this->normalize_app_review_theme_pack($review['theme_pack'] ?? [], (array) ($review['color_palette'] ?? []));
        $theme_key = 'app_' . $selected_link_id;

        $theme_library_entry = [
            'theme_key' => $theme_key,
            'name' => (string) ($theme_pack['name'] ?? 'AI preporučena tema'),
            'summary' => (string) ($theme_pack['summary'] ?? ''),
            'generated_at' => $review['generated_at'] ?? null,
            'selected_link_id' => $selected_link_id,
            'selected_app_name' => (string) ($review['selected_app_name'] ?? ''),
            'goal_type' => (string) ($review['goal_type'] ?? ''),
            'theme_pack' => $theme_pack,
        ];

        $preferences->leader_ai_theme_library = $this->upsert_ai_theme_library(
            $this->get_saved_ai_theme_library($preferences),
            $theme_library_entry
        );

        $link = db()->where('link_id', $selected_link_id)->where('user_id', $this->user->user_id)->getOne('links', ['link_id', 'additional']);

        if(!$link) {
            return $preferences;
        }

        $additional = $this->normalize_json_to_array($link->additional ?? null);
        $evolution_memory = $this->refresh_app_review_evolution_memory(
            $this->normalize_app_review_evolution_memory($additional['fcc_ai_evolution_memory'] ?? []),
            (array) ($review['performance_snapshot'] ?? []),
            (string) ($review['generated_at'] ?? get_date()),
            (array) ($review['block_attribution_snapshot'] ?? [])
        );
        $evolution_cycle = $this->build_app_review_evolution_cycle($review);
        $evolution_memory = $this->upsert_app_review_evolution_cycle($evolution_memory, $evolution_cycle);
        $signal_protection_summary = $this->normalize_app_review_signal_protection_summary(
            $review['signal_protection_summary'] ?? $this->build_app_review_signal_protection_summary(
                (array) ($review['block_attribution_snapshot'] ?? []),
                (array) ($review['layout_actions'] ?? [])
            )
        );
        $final_block_plan = $this->normalize_ai_final_block_plan($review['final_block_plan'] ?? []);

        if(empty($final_block_plan)) {
            $final_block_plan = $this->build_app_review_final_block_plan(
                (array) ($review['block_attribution_snapshot'] ?? []),
                (array) ($review['layout_actions'] ?? []),
                (array) ($review['ideal_block_order'] ?? []),
                (array) ($review['missing_block_recommendations'] ?? [])
            );
        }

        $additional['fcc_ai_theme_pack'] = $theme_pack;
        $additional['fcc_ai_primary_block_plan'] = $this->normalize_app_review_primary_block_plan($review['primary_block_plan'] ?? []);
        $additional['fcc_ai_block_patch_pack'] = $this->normalize_app_review_block_patch_pack($review['block_patch_pack'] ?? []);
        $additional['fcc_ai_copy_suggestions'] = $this->normalize_app_review_copy_suggestions($review['copy_suggestions'] ?? []);
        $additional['fcc_ai_layout_actions'] = $this->normalize_app_review_layout_actions($review['layout_actions'] ?? []);
        $additional['fcc_ai_missing_block_recommendations'] = $this->normalize_app_review_missing_block_recommendations($review['missing_block_recommendations'] ?? []);
        $additional['fcc_ai_ideal_block_order'] = $this->normalize_app_review_visible_list((array) ($review['ideal_block_order'] ?? []));
        $additional['fcc_ai_final_block_plan'] = $final_block_plan;
        $additional['fcc_ai_core_block_policy'] = $this->normalize_json_to_array($review['fcc_core_block_policy'] ?? []);
        $additional['fcc_ai_signal_protection_summary'] = $signal_protection_summary;
        $additional['fcc_ai_evolution_memory'] = $evolution_memory;
        $additional['fcc_ai_theme_library_key'] = $theme_key;
        $additional['fcc_ai_review_summary'] = [
            'generated_at' => $review['generated_at'] ?? null,
            'review_key' => (string) ($review['review_key'] ?? ($review['generated_at'] ?? '')),
            'analysis_mode' => in_array((string) ($review['analysis_mode'] ?? 'initial'), ['initial', 'evolution'], true) ? (string) ($review['analysis_mode'] ?? 'initial') : 'initial',
            'headline' => (string) ($review['headline'] ?? ''),
            'summary' => (string) ($review['summary'] ?? ''),
            'selected_app_name' => (string) ($review['selected_app_name'] ?? ''),
            'goal_type' => (string) ($review['goal_type'] ?? ''),
            'ideal_block_order' => $this->normalize_app_review_visible_list((array) ($review['ideal_block_order'] ?? [])),
            'signal_protection_summary' => $signal_protection_summary,
            'theme_ready' => true,
        ];
        $additional['fcc_ai_theme_apply_state'] = array_merge([
            'recommended_at' => null,
            'applied_at' => null,
            'last_applied_theme_key' => '',
            'layout_applied_at' => null,
            'active_review_key' => '',
        ], (array) ($additional['fcc_ai_theme_apply_state'] ?? []));
        $additional['fcc_ai_theme_apply_state']['recommended_at'] = $review['generated_at'] ?? null;
        $additional['fcc_ai_theme_apply_state']['active_review_key'] = (string) ($evolution_cycle['review_key'] ?? '');

        db()->where('link_id', $selected_link_id)->where('user_id', $this->user->user_id)->update('links', [
            'additional' => json_encode($additional),
        ]);

        cache()->deleteItemsByTag('link_id=' . $selected_link_id);
        cache()->deleteItem('link?link_id=' . $selected_link_id);

        return $preferences;
    }

    private function get_weekly_cooldown_payload(?array $latest_weekly_checkin, int $days = 7): array {
        if($days < 1) {
            return [
                'is_locked' => false,
                'next_checkin_at' => null,
            ];
        }

        if(empty($latest_weekly_checkin['submitted_at'])) {
            return [
                'is_locked' => false,
                'next_checkin_at' => null,
            ];
        }

        try {
            $submitted_at = new \DateTimeImmutable((string) $latest_weekly_checkin['submitted_at']);
            $next_checkin_at = $submitted_at->add(new \DateInterval('P' . $days . 'D'));
            $now = new \DateTimeImmutable();

            return [
                'is_locked' => $next_checkin_at > $now,
                'next_checkin_at' => $next_checkin_at->format('Y-m-d H:i:s'),
            ];
        } catch(\Throwable $exception) {
            return [
                'is_locked' => false,
                'next_checkin_at' => null,
            ];
        }
    }

    private function get_ai_growth_signal_window_payload(array $selected_app, int $days): array {
        $days = max(1, $days);
        $link_id = (int) ($selected_app['link_id'] ?? 0);

        if(!$link_id) {
            return [
                'growth_signal' => 0,
                'shop_contacts' => 0,
                'whatsapp_contacts' => 0,
                'funnel_registrations' => 0,
                'ai_chat_leads' => 0,
            ];
        }

        $period_start_datetime = (new \DateTimeImmutable())->sub(new \DateInterval('P' . ($days - 1) . 'D'))->format('Y-m-d 00:00:00');
        $tracked_blocks = [
            'shop' => [],
            'whatsapp' => [],
            'funnel' => [],
        ];
        $blocks_result = database()->query("SELECT `biolink_block_id`, `type`, `settings`
            FROM `biolinks_blocks`
            WHERE `link_id` = {$link_id}
              AND `is_enabled` = 1");

        while($blocks_result && $row = $blocks_result->fetch_object()) {
            $block_id = (int) ($row->biolink_block_id ?? 0);
            $type = trim((string) ($row->type ?? ''));
            $settings = fcc_ai_decode_biolink_block_settings($row->settings ?? null);

            if($block_id <= 0 || $type === '') {
                continue;
            }

            if(in_array($type, ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo', 'link_forever_shop'], true)) {
                $tracked_blocks['shop'][] = $block_id;
            }

            if($type === 'lead_funnel') {
                $tracked_blocks['funnel'][] = $block_id;
            }

            if($this->is_app_review_whatsapp_block($type, $settings)) {
                $tracked_blocks['whatsapp'][] = $block_id;
            }
        }

        $shop_contacts = 0;
        $whatsapp_contacts = 0;
        $funnel_registrations = 0;

        $tracked_click_blocks = array_values(array_unique(array_merge($tracked_blocks['shop'], $tracked_blocks['whatsapp'])));
        if(!empty($tracked_click_blocks)) {
            $block_ids_sql = implode(',', array_map('intval', $tracked_click_blocks));
            $track_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `track_links`
                WHERE `datetime` >= '{$period_start_datetime}'
                  AND `is_unique` = 1
                  AND `biolink_block_id` IN ({$block_ids_sql})
                GROUP BY `biolink_block_id`");

            while($track_result && $track_row = $track_result->fetch_object()) {
                $block_id = (int) ($track_row->biolink_block_id ?? 0);
                $total = (int) ($track_row->total ?? 0);

                if(in_array($block_id, $tracked_blocks['shop'], true)) {
                    $shop_contacts += $total;
                }

                if(in_array($block_id, $tracked_blocks['whatsapp'], true)) {
                    $whatsapp_contacts += $total;
                }
            }
        }

        if(!empty($tracked_blocks['funnel'])) {
            $funnel_ids_sql = implode(',', array_map('intval', $tracked_blocks['funnel']));
            $funnel_result = database()->query("SELECT COUNT(*) AS `total`
                FROM `data`
                WHERE `type` = 'lead_funnel'
                  AND `datetime` >= '{$period_start_datetime}'
                  AND `biolink_block_id` IN ({$funnel_ids_sql})");
            $funnel_registrations = (int) ($funnel_result ? ($funnel_result->fetch_object()->total ?? 0) : 0);
        }

        $ai_chat_leads = (int) (fcc_ai_get_chat_lead_counts_by_link_ids([$link_id], $period_start_datetime)[$link_id] ?? 0);

        return [
            'growth_signal' => $shop_contacts + $whatsapp_contacts + $funnel_registrations + $ai_chat_leads,
            'shop_contacts' => $shop_contacts,
            'whatsapp_contacts' => $whatsapp_contacts,
            'funnel_registrations' => $funnel_registrations,
            'ai_chat_leads' => $ai_chat_leads,
        ];
    }

    private function get_ai_growth_signal_payload(array $app_structure_payload, int $current_clicks_30d): array {
        $main_app = $this->get_main_app_for_review($app_structure_payload) ?? [];
        $performance = $this->get_app_review_performance_snapshot($main_app);
        $signal_7d = $this->get_ai_growth_signal_window_payload($main_app, 7);
        $shop_contacts = (int) ($performance['shop_contacts_30d'] ?? $current_clicks_30d);
        $whatsapp_contacts = (int) ($performance['whatsapp_contacts_30d'] ?? 0);
        $funnel_registrations = (int) ($performance['funnel_registrations_30d'] ?? 0);
        $ai_chat_leads = (int) ($performance['ai_chat_leads_30d'] ?? 0);
        $growth_signal_30d = $shop_contacts + $whatsapp_contacts + $funnel_registrations + $ai_chat_leads;

        return [
            'growth_signal_30d' => $growth_signal_30d,
            'growth_signal_7d' => (int) ($signal_7d['growth_signal'] ?? 0),
            'shop_contacts_30d' => $shop_contacts,
            'shop_contacts_7d' => (int) ($signal_7d['shop_contacts'] ?? 0),
            'whatsapp_contacts_30d' => $whatsapp_contacts,
            'whatsapp_contacts_7d' => (int) ($signal_7d['whatsapp_contacts'] ?? 0),
            'funnel_registrations_30d' => $funnel_registrations,
            'funnel_registrations_7d' => (int) ($signal_7d['funnel_registrations'] ?? 0),
            'ai_chat_leads_30d' => $ai_chat_leads,
            'ai_chat_leads_7d' => (int) ($signal_7d['ai_chat_leads'] ?? 0),
            'main_app_performance' => $performance,
        ];
    }

    private function get_ai_growth_access_payload($preferences, array $app_structure_payload, int $current_clicks_30d): array {
        $multi_app_mode_enabled = (bool) (settings()->links->fcc_ai_multi_app_reviews_is_enabled ?? false);

        if(\Altum\Authentication::is_admin()) {
            return [
                'tier' => 'admin',
                'is_admin_testing' => true,
                'is_pro' => true,
                'coach_mode' => 'vip',
                'growth_signal_30d' => 999,
                'growth_signal_7d' => 999,
                'is_signal_qualified' => true,
                'is_top_performer' => true,
                'qualified_target' => 15,
                'top_target' => 50,
                'weekly_check_target' => 15,
                'is_weekly_check_passed' => true,
                'signal_breakdown' => [
                    'shop_contacts_30d' => 999,
                    'shop_contacts_7d' => 999,
                    'whatsapp_contacts_30d' => 999,
                    'whatsapp_contacts_7d' => 999,
                    'funnel_registrations_30d' => 999,
                    'funnel_registrations_7d' => 999,
                    'ai_chat_leads_30d' => 999,
                    'ai_chat_leads_7d' => 999,
                ],
                'starter' => [
                    'app_review_used' => 0,
                    'weekly_plan_used' => 0,
                    'app_review_remaining' => 1,
                    'weekly_plan_remaining' => 1,
                    'app_review_available' => true,
                    'weekly_plan_available' => true,
                    'has_any_available' => true,
                ],
                'weekly' => [
                    'has_access' => true,
                    'uses_starter_credit' => false,
                    'is_recurring' => true,
                    'cooldown_days' => 7,
                ],
                'app_review' => [
                    'has_access' => true,
                    'can_generate' => true,
                    'uses_starter_credit' => false,
                    'is_recurring' => true,
                    'cooldown_days' => 0,
                    'can_select_any_app' => $multi_app_mode_enabled,
                    'multi_app_mode_enabled' => $multi_app_mode_enabled,
                    'is_phase_one_main_app_mode' => !$multi_app_mode_enabled,
                    'plan_label_key' => 'ai_plan.app_review_plan_admin',
                ],
            ];
        }

        $is_pro = $this->is_active_pro_user();
        $access_settings = $this->get_ai_growth_access_settings($preferences);
        $signal_payload = $this->get_ai_growth_signal_payload($app_structure_payload, $current_clicks_30d);
        $growth_signal_30d = (int) ($signal_payload['growth_signal_30d'] ?? 0);
        $growth_signal_7d = (int) ($signal_payload['growth_signal_7d'] ?? 0);
        $manual_tier = $this->get_active_manual_ai_tier($access_settings);
        $has_active_signal = $growth_signal_30d >= 15 || in_array($manual_tier, ['qualified', 'top'], true);
        $has_top_signal = $growth_signal_30d >= 50 || $manual_tier === 'top';
        $has_weekly_check = $growth_signal_7d >= 15;
        $starter_app_review_used = !empty($access_settings['starter_app_review_used']);
        $starter_weekly_plan_used = !empty($access_settings['starter_weekly_plan_used']);
        $starter_app_review_available = $is_pro && !$has_active_signal && !$starter_app_review_used;
        $starter_weekly_plan_available = $is_pro && !$has_active_signal && !$starter_weekly_plan_used;

        $tier = 'beginner';
        if($is_pro) {
            $tier = $has_top_signal ? 'top' : ($has_active_signal ? 'qualified' : 'pro');
        }

        $has_recurring_weekly = $is_pro && $has_active_signal;
        $has_recurring_app_review = $is_pro && $has_active_signal;
        $has_intro_weekly = $starter_weekly_plan_available && !$has_recurring_weekly;
        $has_intro_app_review = $starter_app_review_available && !$has_recurring_app_review;
        $app_review_cooldown_days = $has_recurring_app_review ? 7 : 0;

        return [
            'tier' => $tier,
            'is_admin_testing' => false,
            'is_pro' => $is_pro,
            'coach_mode' => $is_pro ? 'vip' : 'beginner',
            'growth_signal_30d' => $growth_signal_30d,
            'growth_signal_7d' => $growth_signal_7d,
            'is_signal_qualified' => $has_active_signal,
            'is_top_performer' => $has_top_signal,
            'is_weekly_check_passed' => $has_weekly_check,
            'qualified_target' => 15,
            'top_target' => 50,
            'weekly_check_target' => 15,
            'signal_breakdown' => [
                'shop_contacts_30d' => (int) ($signal_payload['shop_contacts_30d'] ?? 0),
                'shop_contacts_7d' => (int) ($signal_payload['shop_contacts_7d'] ?? 0),
                'whatsapp_contacts_30d' => (int) ($signal_payload['whatsapp_contacts_30d'] ?? 0),
                'whatsapp_contacts_7d' => (int) ($signal_payload['whatsapp_contacts_7d'] ?? 0),
                'funnel_registrations_30d' => (int) ($signal_payload['funnel_registrations_30d'] ?? 0),
                'funnel_registrations_7d' => (int) ($signal_payload['funnel_registrations_7d'] ?? 0),
                'ai_chat_leads_30d' => (int) ($signal_payload['ai_chat_leads_30d'] ?? 0),
                'ai_chat_leads_7d' => (int) ($signal_payload['ai_chat_leads_7d'] ?? 0),
            ],
            'starter' => [
                'app_review_used' => $starter_app_review_used ? 1 : 0,
                'weekly_plan_used' => $starter_weekly_plan_used ? 1 : 0,
                'app_review_remaining' => $has_intro_app_review ? 1 : 0,
                'weekly_plan_remaining' => $has_intro_weekly ? 1 : 0,
                'app_review_available' => $has_intro_app_review,
                'weekly_plan_available' => $has_intro_weekly,
                'has_any_available' => $has_intro_app_review || $has_intro_weekly,
            ],
            'weekly' => [
                'has_access' => $has_recurring_weekly || $has_intro_weekly,
                'uses_starter_credit' => $has_intro_weekly,
                'is_recurring' => $has_recurring_weekly,
                'cooldown_days' => $has_recurring_weekly ? 7 : 0,
            ],
            'app_review' => [
                'has_access' => $has_recurring_app_review || $has_intro_app_review,
                'can_generate' => $has_recurring_app_review || $has_intro_app_review,
                'uses_starter_credit' => $has_intro_app_review,
                'is_recurring' => $has_recurring_app_review,
                'cooldown_days' => $app_review_cooldown_days,
                'can_select_any_app' => $is_pro && $has_recurring_app_review && $multi_app_mode_enabled,
                'multi_app_mode_enabled' => $multi_app_mode_enabled,
                'is_phase_one_main_app_mode' => !$multi_app_mode_enabled || !$has_recurring_app_review,
                'plan_label_key' => !$is_pro ? 'ai_plan.app_review_plan_beginner' : 'ai_plan.app_review_plan_pro',
            ],
        ];
    }

    private function get_cooldown_payload_by_days(?string $submitted_at, int $days): array {
        if(empty($submitted_at) || $days < 1) {
            return [
                'is_locked' => false,
                'next_checkin_at' => null,
            ];
        }

        try {
            $submitted_at_object = new \DateTimeImmutable($submitted_at);
            $next_checkin_at = $submitted_at_object->add(new \DateInterval('P' . $days . 'D'));
            $now = new \DateTimeImmutable();

            return [
                'is_locked' => $next_checkin_at > $now,
                'next_checkin_at' => $next_checkin_at->format('Y-m-d H:i:s'),
            ];
        } catch(\Throwable $exception) {
            return [
                'is_locked' => false,
                'next_checkin_at' => null,
            ];
        }
    }

    private function get_app_review_plan_access_payload(): array {
        $multi_app_mode_enabled = (bool) (settings()->links->fcc_ai_multi_app_reviews_is_enabled ?? false);

        if(\Altum\Authentication::is_admin()) {
            return [
                'is_admin_testing' => true,
                'is_pro_daily' => true,
                'cooldown_days' => 0,
                'can_select_any_app' => $multi_app_mode_enabled,
                'multi_app_mode_enabled' => $multi_app_mode_enabled,
                'is_phase_one_main_app_mode' => !$multi_app_mode_enabled,
                'plan_label_key' => 'ai_plan.app_review_plan_admin',
            ];
        }

        $has_ai_growth_plan_access = $this->is_active_pro_user();

        return [
            'is_admin_testing' => false,
            'is_pro_daily' => $has_ai_growth_plan_access,
            'cooldown_days' => 0,
            'can_select_any_app' => $has_ai_growth_plan_access && $multi_app_mode_enabled,
            'multi_app_mode_enabled' => $multi_app_mode_enabled,
            'is_phase_one_main_app_mode' => !$multi_app_mode_enabled,
            'plan_label_key' => $has_ai_growth_plan_access ? 'ai_plan.app_review_plan_pro' : 'ai_plan.app_review_plan_beginner',
        ];
    }

    private function get_default_app_summary(int $link_id = 0): array {
        return [
            'link_id' => $link_id,
            'name' => '',
            'url' => '',
            'public_url' => '',
            'is_enabled' => true,
            'datetime' => null,
            'last_datetime' => null,
            'total_blocks' => 0,
            'forever_blocks' => 0,
            'funnel_blocks' => 0,
            'social_blocks' => 0,
            'content_blocks' => 0,
        ];
    }

    private function get_main_app(array $apps, int $main_biolink_id = 0): ?array {
        if(empty($apps)) {
            return null;
        }

        /* Custom code: FC-2026-03-31: use the protected default biolink as the canonical main app */
        if($main_biolink_id > 0 && isset($apps[$main_biolink_id])) {
            return $apps[$main_biolink_id];
        }
        /* /Custom code: FC-2026-03-31 */

        $apps_for_sorting = array_values($apps);

        usort($apps_for_sorting, static function($a, $b) {
            $score_a = (($a['total_blocks'] ?? 0) * 100) + (($a['forever_blocks'] ?? 0) * 10) + (($a['funnel_blocks'] ?? 0) * 5) + (($a['social_blocks'] ?? 0) * 2);
            $score_b = (($b['total_blocks'] ?? 0) * 100) + (($b['forever_blocks'] ?? 0) * 10) + (($b['funnel_blocks'] ?? 0) * 5) + (($b['social_blocks'] ?? 0) * 2);

            if($score_a !== $score_b) {
                return $score_b <=> $score_a;
            }

            $last_datetime_a = (string) ($a['last_datetime'] ?? '');
            $last_datetime_b = (string) ($b['last_datetime'] ?? '');

            if($last_datetime_a !== $last_datetime_b) {
                return strcmp($last_datetime_b, $last_datetime_a);
            }

            $datetime_a = (string) ($a['datetime'] ?? '');
            $datetime_b = (string) ($b['datetime'] ?? '');

            if($datetime_a !== $datetime_b) {
                return strcmp($datetime_a, $datetime_b);
            }

            return ((int) ($a['link_id'] ?? 0)) <=> ((int) ($b['link_id'] ?? 0));
        });

        return $apps_for_sorting[0] ?? null;
    }

    private function get_selected_app(array $app_structure_payload, int $selected_link_id = 0): ?array {
        $apps = $app_structure_payload['apps'] ?? [];

        if($selected_link_id && isset($apps[$selected_link_id])) {
            return $apps[$selected_link_id];
        }

        if(!empty($app_structure_payload['main_app']) && is_array($app_structure_payload['main_app'])) {
            return $app_structure_payload['main_app'];
        }

        return null;
    }

    private function get_main_app_for_review(array $app_structure_payload): ?array {
        return $this->get_selected_app($app_structure_payload, (int) ($app_structure_payload['top_app_link_id'] ?? 0))
            ?? $this->get_selected_app($app_structure_payload);
    }

    private function get_latest_updated_app(array $app_structure_payload): ?array {
        $apps = array_values(array_filter((array) ($app_structure_payload['apps'] ?? []), 'is_array'));

        if(empty($apps)) {
            return null;
        }

        usort($apps, static function($a, $b) {
            $activity_a = (string) (($a['last_datetime'] ?? '') ?: ($a['datetime'] ?? ''));
            $activity_b = (string) (($b['last_datetime'] ?? '') ?: ($b['datetime'] ?? ''));

            if($activity_a !== $activity_b) {
                return strcmp($activity_b, $activity_a);
            }

            return ((int) ($b['link_id'] ?? 0)) <=> ((int) ($a['link_id'] ?? 0));
        });

        return $apps[0] ?? null;
    }

    private function get_weekly_plan_focus_app(array $app_structure_payload, ?array $latest_app_review = null): ?array {
        $latest_review_selected_link_id = (int) ($latest_app_review['selected_link_id'] ?? 0);

        if($latest_review_selected_link_id > 0) {
            $review_selected_app = $this->get_selected_app($app_structure_payload, $latest_review_selected_link_id);

            if($review_selected_app) {
                return $review_selected_app;
            }
        }

        $main_app = $this->get_main_app_for_review($app_structure_payload);
        $latest_updated_app = $this->get_latest_updated_app($app_structure_payload);

        if(!$main_app) {
            return $latest_updated_app;
        }

        if(
            $latest_updated_app
            && (int) ($latest_updated_app['link_id'] ?? 0) !== (int) ($main_app['link_id'] ?? 0)
        ) {
            $latest_updated_at = (string) (($latest_updated_app['last_datetime'] ?? '') ?: ($latest_updated_app['datetime'] ?? ''));
            $main_app_updated_at = (string) (($main_app['last_datetime'] ?? '') ?: ($main_app['datetime'] ?? ''));

            if($latest_updated_at !== '' && ($main_app_updated_at === '' || strcmp($latest_updated_at, $main_app_updated_at) > 0)) {
                return $latest_updated_app;
            }
        }

        return $main_app;
    }

    private function get_app_review_evolution_payload(array $current_performance, ?array $previous_review = null, array $evolution_memory = [], ?string $current_datetime = null, array $current_block_attribution = []): array {
        $default_snapshot = $this->get_default_app_review_performance_snapshot();
        $current_snapshot = $this->normalize_app_review_performance_snapshot($current_performance);
        $evolution_memory = $this->refresh_app_review_evolution_memory($evolution_memory, $current_snapshot, $current_datetime, $current_block_attribution);
        $latest_cycle = $evolution_memory[0] ?? null;

        if(!$previous_review) {
            return [
                'has_previous_review' => false,
                'analysis_mode' => 'initial',
                'previous_generated_at' => null,
                'previous_quality_score' => 0,
                'previous_quality_level' => 'foundation',
                'previous_summary' => '',
                'previous_top_recommendation' => '',
                'previous_first_move' => '',
                'previous_snapshot' => $default_snapshot,
                'current_snapshot' => $current_snapshot,
                'changes' => [],
                'tracked_cycles' => count($evolution_memory),
                'latest_cycle' => $latest_cycle,
                'recent_measured_cycles' => array_values(array_slice(array_filter($evolution_memory, static function($cycle) {
                    return !empty($cycle['evaluation_7d']['measured_at']) || !empty($cycle['evaluation_30d']['measured_at']);
                }), 0, 3)),
            ];
        }

        $previous_snapshot = $this->normalize_app_review_performance_snapshot($previous_review['performance_snapshot'] ?? []);
        $changes = $this->get_app_review_performance_delta($previous_snapshot, $current_snapshot);

        return [
            'has_previous_review' => true,
            'analysis_mode' => 'evolution',
            'previous_generated_at' => $previous_review['generated_at'] ?? null,
            'previous_quality_score' => (int) ($previous_review['quality_score'] ?? 0),
            'previous_quality_level' => (string) ($previous_review['quality_level'] ?? 'foundation'),
            'previous_summary' => (string) ($previous_review['summary'] ?? ''),
            'previous_top_recommendation' => (string) ($previous_review['top_recommendation'] ?? ''),
            'previous_first_move' => (string) ($previous_review['first_move'] ?? ''),
            'previous_snapshot' => $previous_snapshot,
            'current_snapshot' => $current_snapshot,
            'changes' => $changes,
            'tracked_cycles' => count($evolution_memory),
            'latest_cycle' => $latest_cycle,
            'recent_measured_cycles' => array_values(array_slice(array_filter($evolution_memory, static function($cycle) {
                return !empty($cycle['evaluation_7d']['measured_at']) || !empty($cycle['evaluation_30d']['measured_at']);
            }), 0, 3)),
        ];
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

    private function get_app_review_text_excerpt(?string $value, int $limit = 160): string {
        $value = html_entity_decode(strip_tags((string) ($value ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value ?? '');
        $value = trim((string) $value);

        if($value === '') {
            return '';
        }

        if(mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, max(1, $limit - 1))) . '…';
    }

    private function sanitize_utf8_for_json($value) {
        if(is_array($value)) {
            foreach($value as $key => $item) {
                $value[$key] = $this->sanitize_utf8_for_json($item);
            }

            return $value;
        }

        if($value instanceof \stdClass) {
            foreach(get_object_vars($value) as $key => $item) {
                $value->{$key} = $this->sanitize_utf8_for_json($item);
            }

            return $value;
        }

        if(is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        return $value;
    }

    private function get_app_review_visual_url_from_block(string $type, \stdClass $settings): string {
        $image_name = trim((string) ($settings->image ?? ''));

        if($image_name === '') {
            return '';
        }

        return match($type) {
            'image' => \Altum\Uploads::get_full_url('block_images') . $image_name,
            'avatar' => \Altum\Uploads::get_full_url('avatars') . $image_name,
            default => '',
        };
    }

    private function get_app_review_block_preview_label(string $type, \stdClass $settings): string {
        $direct_candidates = [
            'heading',
            'title',
            'text',
            'content',
            'description',
            'paragraph',
            'name',
            'button_text',
            'label',
            'subheading',
            'message',
        ];

        foreach($direct_candidates as $candidate) {
            $value = $this->get_app_review_text_excerpt((string) ($settings->{$candidate} ?? ''), 140);

            if($value !== '') {
                return $value;
            }
        }

        return match($type) {
            'avatar' => 'Profilna fotografija',
            'image' => 'Glavna fotografija',
            'heading' => 'Naslovna poruka',
            'paragraph', 'markdown' => 'Opis i objašnjenje',
            'youtube', 'video', 'tiktok_video', 'vimeo', 'twitter_video', 'vk_video' => 'Video sadržaj',
            'socials' => 'Društvene mreže i kontakti',
            'custom_html_whatsapp' => 'WhatsApp kontakt',
            'lead_funnel' => $this->get_sales_step_name($type, $settings),
            'link_forever_shop',
            'link_forever_product',
            'link_forever_living_bih',
            'link_forever_living_alb_kosovo',
            'link_forever_living_albania_kosovo',
            'link_discount',
            'link_app_switcher' => $this->get_sales_step_name($type, $settings),
            'link' => 'Link ili sljedeći korak',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    private function model_supports_image_input(string $model): bool {
        $model = mb_strtolower(trim($model));

        if($model === '') {
            return false;
        }

        return str_contains($model, '4o')
            || str_contains($model, '4.1')
            || str_contains($model, 'gpt-5')
            || str_starts_with($model, 'o4')
            || str_starts_with($model, 'o3');
    }

    private function get_app_review_color_signal($value): array {
        if(!is_scalar($value)) {
            return [];
        }

        $value = trim((string) $value);

        if($value === '') {
            return [];
        }

        $rgb = null;

        if(preg_match('/#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})\b/', $value, $matches)) {
            $hex = strtoupper((string) $matches[1]);

            if(strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            } elseif(strlen($hex) === 8) {
                $hex = substr($hex, 0, 6);
            }

            if(strlen($hex) === 6) {
                $rgb = [
                    'r' => hexdec(substr($hex, 0, 2)),
                    'g' => hexdec(substr($hex, 2, 2)),
                    'b' => hexdec(substr($hex, 4, 2)),
                ];
            }
        } elseif(preg_match('/^rgba?\(([^)]+)\)$/i', $value, $matches)) {
            $parts = preg_split('/\s*,\s*/', trim((string) $matches[1])) ?: [];

            if(count($parts) >= 3) {
                $rgb = [
                    'r' => (int) max(0, min(255, (float) $parts[0])),
                    'g' => (int) max(0, min(255, (float) $parts[1])),
                    'b' => (int) max(0, min(255, (float) $parts[2])),
                ];
            }
        }

        if(!$rgb) {
            return [];
        }

        $r = (float) $rgb['r'];
        $g = (float) $rgb['g'];
        $b = (float) $rgb['b'];
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;
        $lightness_ratio = (($max + $min) / 2) / 255;
        $saturation_denominator = 255 - abs($max + $min - 255);
        $saturation = ($delta === 0 || $saturation_denominator <= 0) ? 0 : $delta / $saturation_denominator;

        if($saturation < 0.12) {
            $family = 'neutral';
        } else {
            if($delta == 0) {
                $hue = 0;
            } elseif($max === $r) {
                $hue = fmod((($g - $b) / $delta), 6);
            } elseif($max === $g) {
                $hue = (($b - $r) / $delta) + 2;
            } else {
                $hue = (($r - $g) / $delta) + 4;
            }

            $hue = (int) round($hue * 60);

            if($hue < 0) {
                $hue += 360;
            }

            $family = match(true) {
                $hue < 15 || $hue >= 345 => 'red',
                $hue < 40 => 'orange',
                $hue < 65 => 'yellow',
                $hue < 95 => 'lime',
                $hue < 155 => 'green',
                $hue < 190 => 'teal',
                $hue < 240 => 'blue',
                $hue < 285 => 'purple',
                $hue < 345 => 'pink',
                default => 'neutral',
            };
        }

        $lightness = match(true) {
            $lightness_ratio <= 0.16 => 'very_dark',
            $lightness_ratio <= 0.34 => 'dark',
            $lightness_ratio <= 0.68 => 'mid',
            $lightness_ratio <= 0.84 => 'light',
            default => 'very_light',
        };

        $intensity = match(true) {
            $saturation < 0.12 => 'neutral',
            $saturation < 0.30 => 'soft',
            $saturation < 0.56 => 'balanced',
            default => 'strong',
        };

        return [
            'family' => $family,
            'lightness' => $lightness,
            'intensity' => $intensity,
        ];
    }

    private function get_app_review_palette_anchor_risk(array $signals): string {
        $families = [];
        $strong_count = 0;

        foreach($signals as $signal) {
            if(!is_array($signal) || empty($signal)) {
                continue;
            }

            $family = (string) ($signal['family'] ?? '');
            $intensity = (string) ($signal['intensity'] ?? '');

            if($family !== '' && $family !== 'neutral') {
                $families[$family] = true;
            }

            if($intensity === 'strong') {
                $strong_count++;
            }
        }

        $family_count = count($families);

        if($family_count >= 3 || $strong_count >= 2) {
            return 'high';
        }

        if($family_count >= 2 || $strong_count >= 1) {
            return 'medium';
        }

        return 'low';
    }

    private function get_app_review_visual_profile(\stdClass $settings): array {
        $background_type = (string) ($settings->background_type ?? '');
        $background_value = trim((string) ($settings->background ?? ''));
        $background_summary = '';

        if($background_value !== '' && str_ends_with(mb_strtolower($background_value), '.mp4')) {
            $background_summary = 'Video pozadina';
        } else {
            $background_summary = match($background_type) {
                'image' => 'Fotografija u pozadini',
                'gradient' => 'Gradijent pozadina',
                'color' => 'Jednobojna pozadina',
                '', 'preset' => $background_value !== '' ? 'Odabrana pozadina iz kolekcije' : 'Zadana pozadina',
                default => $background_value !== '' ? 'Posebna pozadina aplikacije' : 'Zadana pozadina',
            };
        }

        $background_signal = $this->get_app_review_color_signal(
            $background_type === 'color'
                ? $background_value
                : ((string) ($settings->background_color_one ?? ''))
        );
        $gradient_start_signal = $this->get_app_review_color_signal((string) ($settings->background_color_one ?? ''));
        $gradient_end_signal = $this->get_app_review_color_signal((string) ($settings->background_color_two ?? ''));
        $text_signal = $this->get_app_review_color_signal((string) ($settings->text_color ?? ''));

        return [
            'background_type' => $background_type !== '' ? $background_type : 'default',
            'background_summary' => $background_summary,
            'background_signal' => $background_signal,
            'gradient_start_signal' => $gradient_start_signal,
            'gradient_end_signal' => $gradient_end_signal,
            'text_signal' => $text_signal,
            'font' => (string) ($settings->font ?? ''),
            'block_spacing' => (string) ($settings->block_spacing ?? ''),
            'width' => (string) ($settings->width ?? ''),
            'background_blur' => (int) ($settings->background_blur ?? 0),
            'background_brightness' => (int) ($settings->background_brightness ?? 100),
            'palette_anchor_risk' => $this->get_app_review_palette_anchor_risk([$background_signal, $gradient_start_signal, $gradient_end_signal, $text_signal]),
            'current_visual_role' => 'diagnostic_only',
        ];
    }

    private function get_app_review_block_style_profile(\stdClass $settings): array {
        $has_custom_text_color = trim((string) ($settings->text_color ?? '')) !== '';
        $has_custom_background = trim((string) ($settings->background_color ?? '')) !== '';
        $has_custom_border = trim((string) ($settings->border_color ?? '')) !== '';
        $has_shadow = trim((string) ($settings->border_shadow_style ?? '')) !== '' || trim((string) ($settings->border_shadow_color ?? '')) !== '';

        if(!$has_custom_text_color && !$has_custom_background && !$has_custom_border && !$has_shadow) {
            return [];
        }

        $styled_surface_count = ($has_custom_text_color ? 1 : 0) + ($has_custom_background ? 1 : 0) + ($has_custom_border ? 1 : 0) + ($has_shadow ? 1 : 0);

        return [
            'has_custom_text_color' => $has_custom_text_color,
            'has_custom_background' => $has_custom_background,
            'has_custom_border' => $has_custom_border,
            'uses_shadow' => $has_shadow,
            'text_signal' => $this->get_app_review_color_signal((string) ($settings->text_color ?? '')),
            'background_signal' => $this->get_app_review_color_signal((string) ($settings->background_color ?? '')),
            'border_signal' => $this->get_app_review_color_signal((string) ($settings->border_color ?? '')),
            'shadow_signal' => $this->get_app_review_color_signal((string) ($settings->border_shadow_color ?? '')),
            'emphasis_strength' => $styled_surface_count >= 3 ? 'strong' : ($styled_surface_count >= 2 ? 'balanced' : 'soft'),
            'style_complexity' => $styled_surface_count >= 4 ? 'busy' : ($styled_surface_count >= 2 ? 'controlled' : 'minimal'),
            'current_visual_role' => 'diagnostic_only',
        ];
    }

    private function get_app_review_segment_key(int $position, int $total_blocks): string {
        if($total_blocks <= 6) {
            return 'hero';
        }

        $hero_limit = min(6, $total_blocks);
        $bottom_start = max($hero_limit + 1, $total_blocks - 5);

        if($position <= $hero_limit) {
            return 'hero';
        }

        if($position >= $bottom_start) {
            return 'bottom';
        }

        return 'middle';
    }

    private function get_app_review_segment_title(string $segment_key): string {
        return match($segment_key) {
            'hero' => 'Prvi ekran aplikacije',
            'middle' => 'Srednji dio aplikacije',
            'bottom' => 'Donji dio aplikacije',
            default => 'Pregled aplikacije',
        };
    }

    private function get_app_review_block_visual_url(string $type, \stdClass $settings): string {
        $storage_map = [
            'image' => ['path' => 'block_images', 'key' => 'image'],
            'avatar' => ['path' => 'avatars', 'key' => 'image'],
            'link' => ['path' => 'block_thumbnail_images', 'key' => 'image'],
            'big_link' => ['path' => 'block_thumbnail_images', 'key' => 'image'],
            'email_collector' => ['path' => 'block_thumbnail_images', 'key' => 'image'],
            'lead_funnel' => ['path' => 'block_thumbnail_images', 'key' => 'image'],
            'vcard' => ['path' => 'block_thumbnail_images', 'key' => 'image'],
            'review' => ['path' => 'block_images', 'key' => 'image'],
            'header' => ['path' => 'avatars', 'key' => 'avatar'],
        ];

        if(!isset($storage_map[$type])) {
            return '';
        }

        $image_name = trim((string) ($settings->{$storage_map[$type]['key']} ?? ''));

        if($image_name === '') {
            return '';
        }

        return \Altum\Uploads::get_full_url($storage_map[$type]['path']) . $image_name;
    }

    private function get_default_app_review_visual_segments(): array {
        return [
            'hero' => [
                'title' => $this->get_app_review_segment_title('hero'),
                'primary_visual_url' => '',
                'items' => [],
            ],
            'middle' => [
                'title' => $this->get_app_review_segment_title('middle'),
                'primary_visual_url' => '',
                'items' => [],
            ],
            'bottom' => [
                'title' => $this->get_app_review_segment_title('bottom'),
                'primary_visual_url' => '',
                'items' => [],
            ],
        ];
    }

    private function get_app_review_chromium_binary(): string {
        static $resolved_binary = null;

        if($resolved_binary !== null) {
            return $resolved_binary;
        }

        foreach(['/usr/bin/chromium', '/usr/bin/chromium-browser', '/usr/bin/google-chrome'] as $absolute_binary) {
            if(is_file($absolute_binary) && is_executable($absolute_binary)) {
                $resolved_binary = $absolute_binary;
                return $resolved_binary;
            }
        }

        foreach(['chromium', 'chromium-browser', 'google-chrome'] as $binary) {
            $resolved = trim((string) @shell_exec('command -v ' . $binary . ' 2>/dev/null'));

            if($resolved !== '') {
                $resolved_binary = $resolved;
                return $resolved_binary;
            }
        }

        $resolved_binary = '';
        return $resolved_binary;
    }

    private function cleanup_app_review_snapshot_directory(string $directory): void {
        if($directory === '' || !is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if(!is_array($items)) {
            @rmdir($directory);
            return;
        }

        foreach($items as $item) {
            if($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if(is_dir($path)) {
                $this->cleanup_app_review_snapshot_directory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }

    private function purge_stale_app_review_snapshot_directories(int $max_age_seconds = 7200): void {
        $temp_root = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

        if($temp_root === '' || !is_dir($temp_root)) {
            return;
        }

        $entries = @scandir($temp_root);

        if(!is_array($entries)) {
            return;
        }

        $cutoff_timestamp = time() - max(300, $max_age_seconds);

        foreach($entries as $entry) {
            if($entry === '.' || $entry === '..' || !str_starts_with($entry, 'fcc-ai-app-review-')) {
                continue;
            }

            $path = $temp_root . DIRECTORY_SEPARATOR . $entry;

            if(!is_dir($path)) {
                continue;
            }

            $modified_at = @filemtime($path);

            if($modified_at !== false && $modified_at > $cutoff_timestamp) {
                continue;
            }

            $this->cleanup_app_review_snapshot_directory($path);
        }
    }

    private function get_app_review_png_data_url(string $png_path, int $y_offset = 0, ?int $crop_height = null): string {
        if(!is_file($png_path) || !function_exists('imagecreatefrompng')) {
            return '';
        }

        $source = @imagecreatefrompng($png_path);

        if(!$source) {
            return '';
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if($width < 80 || $height < 120) {
            imagedestroy($source);
            return '';
        }

        $y_offset = max(0, min($height - 1, $y_offset));
        $crop_height = $crop_height === null ? $height : max(120, min($height - $y_offset, $crop_height));

        $target = imagecreatetruecolor($width, $crop_height);

        if(!$target) {
            imagedestroy($source);
            return '';
        }

        imagealphablending($target, true);
        imagesavealpha($target, true);
        imagecopy($target, $source, 0, 0, 0, $y_offset, $width, $crop_height);

        ob_start();
        imagepng($target, null, 6);
        $image_binary = (string) ob_get_clean();

        imagedestroy($target);
        imagedestroy($source);

        if($image_binary === '') {
            return '';
        }

        return 'data:image/png;base64,' . base64_encode($image_binary);
    }

    private function capture_app_review_live_visual_payload(string $public_url): array {
        $default_payload = [
            'scope' => 'none',
            'visual_type' => '',
            'primary_visual_url' => '',
            'visual_segments' => $this->get_default_app_review_visual_segments(),
        ];

        if($public_url === '' || !filter_var($public_url, FILTER_VALIDATE_URL)) {
            return $default_payload;
        }

        $chromium_binary = $this->get_app_review_chromium_binary();

        if($chromium_binary === '') {
            return $default_payload;
        }

        $this->purge_stale_app_review_snapshot_directories();

        $workspace = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'fcc-ai-app-review-' . bin2hex(random_bytes(6));
        $profile_directory = $workspace . DIRECTORY_SEPARATOR . 'chrome-profile';
        $screenshot_path = $workspace . DIRECTORY_SEPARATOR . 'app-full.png';
        $capture_url = $public_url . (str_contains($public_url, '?') ? '&' : '?') . 'fcc_ai_review_snapshot=1';

        if(!@mkdir($workspace, 0775, true) && !is_dir($workspace)) {
            return $default_payload;
        }

        try {
            @mkdir($profile_directory, 0775, true);

            $command_parts = [
                'timeout',
                '20s',
                $chromium_binary,
                '--headless',
                '--no-sandbox',
                '--disable-gpu',
                '--disable-dev-shm-usage',
                '--hide-scrollbars',
                '--run-all-compositor-stages-before-draw',
                '--virtual-time-budget=7000',
                '--force-device-scale-factor=1',
                '--default-background-color=000000',
                '--window-size=430,7600',
                '--user-data-dir=' . $profile_directory,
                '--screenshot=' . $screenshot_path,
                $capture_url,
            ];

            $command = implode(' ', array_map('escapeshellarg', $command_parts)) . ' 2>/dev/null';
            @shell_exec($command);

            if(!is_file($screenshot_path) || filesize($screenshot_path) < 4096) {
                return $default_payload;
            }

            $image_size = @getimagesize($screenshot_path);

            if(!is_array($image_size) || (int) ($image_size[0] ?? 0) < 100 || (int) ($image_size[1] ?? 0) < 200) {
                return $default_payload;
            }

            $height = (int) $image_size[1];
            $base_crop_height = min(1800, max(960, (int) floor($height / ($height >= 4200 ? 3 : 2))));
            $segment_specs = [
                'hero' => [
                    'title' => $this->get_app_review_segment_title('hero'),
                    'y' => 0,
                    'height' => min($base_crop_height, $height),
                ],
                'middle' => [
                    'title' => $this->get_app_review_segment_title('middle'),
                    'y' => max(0, (int) floor(($height - $base_crop_height) / 2)),
                    'height' => min($base_crop_height, $height),
                ],
                'bottom' => [
                    'title' => $this->get_app_review_segment_title('bottom'),
                    'y' => max(0, $height - $base_crop_height),
                    'height' => min($base_crop_height, $height),
                ],
            ];

            $visual_segments = $this->get_default_app_review_visual_segments();
            $seen_offsets = [];

            foreach($segment_specs as $segment_key => $spec) {
                $offset_key = ((int) $spec['y']) . ':' . ((int) $spec['height']);

                if(isset($seen_offsets[$offset_key])) {
                    continue;
                }

                $data_url = $this->get_app_review_png_data_url($screenshot_path, (int) $spec['y'], (int) $spec['height']);

                if($data_url === '') {
                    continue;
                }

                $visual_segments[$segment_key]['title'] = (string) ($spec['title'] ?? $this->get_app_review_segment_title($segment_key));
                $visual_segments[$segment_key]['primary_visual_url'] = $data_url;
                $seen_offsets[$offset_key] = true;
            }

            $primary_visual_url = (string) ($visual_segments['hero']['primary_visual_url'] ?? '');

            if($primary_visual_url === '') {
                foreach(['middle', 'bottom'] as $fallback_segment_key) {
                    if(!empty($visual_segments[$fallback_segment_key]['primary_visual_url'])) {
                        $primary_visual_url = (string) $visual_segments[$fallback_segment_key]['primary_visual_url'];
                        break;
                    }
                }
            }

            if($primary_visual_url === '') {
                return $default_payload;
            }

            return [
                'scope' => 'rendered_live_app',
                'visual_type' => 'rendered_live_app',
                'primary_visual_url' => $primary_visual_url,
                'visual_segments' => $visual_segments,
            ];
        } finally {
            $this->cleanup_app_review_snapshot_directory($workspace);
        }
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

    private function get_app_review_preview_image_map(array $link_ids): array {
        $preview_images = [];

        $link_ids = array_values(array_filter(array_map('intval', $link_ids)));

        if(empty($link_ids)) {
            return $preview_images;
        }

        $link_ids_sql = implode(',', $link_ids);
        $blocks_result = database()->query("SELECT `link_id`, `biolink_block_id`, `type`, `settings`
            FROM `biolinks_blocks`
            WHERE `link_id` IN ({$link_ids_sql})
              AND `type` IN ('image', 'avatar')
            ORDER BY `biolink_block_id` ASC");

        if(!$blocks_result) {
            return $preview_images;
        }

        while($row = $blocks_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);

            if($link_id <= 0) {
                continue;
            }

            $settings = $this->decode_biolink_block_settings($row->settings ?? null);
            $type = (string) ($row->type ?? '');
            $image_name = trim((string) ($settings->image ?? ''));

            if($image_name === '') {
                continue;
            }

            $candidate = null;
            $priority = 99;

            if($type === 'image') {
                $candidate = \Altum\Uploads::get_full_url('block_images') . $image_name;
                $priority = 1;
            } elseif($type === 'avatar') {
                $candidate = \Altum\Uploads::get_full_url('avatars') . $image_name;
                $priority = 3;
            }

            if(!$candidate) {
                continue;
            }

            if(!isset($preview_images[$link_id]) || $priority < (int) ($preview_images[$link_id]['priority'] ?? 99)) {
                $preview_images[$link_id] = [
                    'url' => $candidate,
                    'priority' => $priority,
                ];
            }
        }

        foreach($preview_images as $link_id => $payload) {
            $preview_images[$link_id] = (string) ($payload['url'] ?? '');
        }

        return $preview_images;
    }

    private function get_app_review_contact_captures_30d(array $signals): int {
        return (int) ($signals['funnel_registrations_30d'] ?? 0) + (int) ($signals['ai_chat_leads_30d'] ?? 0);
    }

    private function calculate_app_review_weighted_signal_score(array $signals): int {
        $shop_contacts = (int) ($signals['shop_contacts_30d'] ?? 0);
        $whatsapp_contacts = (int) ($signals['whatsapp_contacts_30d'] ?? 0);
        $product_clicks = (int) ($signals['product_clicks_30d'] ?? 0);
        $contact_captures = $this->get_app_review_contact_captures_30d($signals);

        return $shop_contacts + $whatsapp_contacts + $product_clicks + ($contact_captures * 2);
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
            $app['signal_map'] = $signal_map;

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

    private function get_app_review_block_attribution_payload(array $selected_app): array {
        $link_id = (int) ($selected_app['link_id'] ?? 0);

        if($link_id <= 0) {
            return $this->normalize_app_review_block_attribution_payload([]);
        }

        $period_30d_start = (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00');
        $blocks_result = database()->query("SELECT `biolink_block_id`, `type`, `settings`, `order`
            FROM `biolinks_blocks`
            WHERE `user_id` = {$this->user->user_id}
              AND `link_id` = {$link_id}
              AND `is_enabled` = 1
            ORDER BY `order` ASC, `biolink_block_id` ASC");

        $blocks = [];
        $block_ids = [];

        if($blocks_result) {
            while($row = $blocks_result->fetch_object()) {
                $block_id = (int) ($row->biolink_block_id ?? 0);

                if($block_id <= 0) {
                    continue;
                }

                $settings = $this->decode_biolink_block_settings($row->settings ?? null);
                $blocks[] = [
                    'block_id' => $block_id,
                    'type' => (string) ($row->type ?? ''),
                    'settings' => $settings,
                    'order' => (int) ($row->order ?? 0),
                    'label' => $this->get_app_review_block_preview_label((string) ($row->type ?? ''), $settings),
                ];
                $block_ids[] = $block_id;
            }
        }

        if(empty($blocks)) {
            return $this->normalize_app_review_block_attribution_payload([]);
        }

        $clicks_per_block = [];
        $leads_per_block = [];
        $block_ids_sql = implode(',', array_map('intval', $block_ids));

        $clicks_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
            FROM `track_links`
            WHERE `datetime` >= '{$period_30d_start}'
              AND `is_unique` = 1
              AND `biolink_block_id` IN ({$block_ids_sql})
            GROUP BY `biolink_block_id`");

        if($clicks_result) {
            while($row = $clicks_result->fetch_object()) {
                $clicks_per_block[(int) ($row->biolink_block_id ?? 0)] = (int) ($row->total ?? 0);
            }
        }

        $funnel_ids = array_values(array_filter(array_map(static function($block): int {
            return (string) ($block['type'] ?? '') === 'lead_funnel' ? (int) ($block['block_id'] ?? 0) : 0;
        }, $blocks)));

        if(!empty($funnel_ids)) {
            $funnel_ids_sql = implode(',', array_map('intval', $funnel_ids));
            $leads_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total`
                FROM `data`
                WHERE `type` = 'lead_funnel'
                  AND `datetime` >= '{$period_30d_start}'
                  AND `biolink_block_id` IN ({$funnel_ids_sql})
                GROUP BY `biolink_block_id`");

            if($leads_result) {
                while($row = $leads_result->fetch_object()) {
                    $leads_per_block[(int) ($row->biolink_block_id ?? 0)] = (int) ($row->total ?? 0);
                }
            }
        }

        $rows = [];
        foreach($blocks as $index => $block) {
            $block_id = (int) ($block['block_id'] ?? 0);
            $rows[] = $this->build_app_review_block_attribution_row(
                $block,
                $index + 1,
                (int) ($clicks_per_block[$block_id] ?? 0),
                (int) ($leads_per_block[$block_id] ?? 0)
            );
        }

        $top_signal_blocks = array_values(array_slice(array_filter($rows, static fn($row): bool => (int) ($row['signal_score'] ?? 0) > 0), 0, count($rows)));
        usort($top_signal_blocks, static function($a, $b) {
            return (($b['signal_score'] ?? 0) <=> ($a['signal_score'] ?? 0))
                ?: (($a['position'] ?? 0) <=> ($b['position'] ?? 0));
        });

        $focus_risk_blocks = array_values(array_filter($rows, static function($row): bool {
            return in_array((string) ($row['status'] ?? ''), ['critical_focus_risk', 'focus_risk'], true);
        }));
        usort($focus_risk_blocks, static function($a, $b) {
            return (($b['focus_cost_score'] ?? 0) <=> ($a['focus_cost_score'] ?? 0))
                ?: (($a['position'] ?? 0) <=> ($b['position'] ?? 0));
        });

        return $this->normalize_app_review_block_attribution_payload([
            'summary' => [
                'tracked_blocks' => count($rows),
                'signal_blocks' => count(array_filter($rows, static fn($row): bool => (int) ($row['signal_score'] ?? 0) > 0)),
                'focus_risk_blocks' => count($focus_risk_blocks),
                'zero_signal_blocks' => count(array_filter($rows, static fn($row): bool => (int) ($row['signal_score'] ?? 0) === 0)),
            ],
            'top_signal_blocks' => array_slice($top_signal_blocks, 0, 4),
            'focus_risk_blocks' => array_slice($focus_risk_blocks, 0, 4),
            'all_blocks' => $rows,
        ]);
    }

    /* Custom code: FC-2026-03-31: normalize app review DM wording to WhatsApp wording */
    private function normalize_app_review_channel_copy(string $value): string {
        if($value === '') {
            return '';
        }

        $patterns = [
            '/\b[Pp]ošalji DM\b/u' => 'Pošalji poruku na WhatsApp',
            '/\b[Pp]osalji DM\b/u' => 'Pošalji poruku na WhatsApp',
            '/\b[Pp]ostavi DM kao\b/u' => 'Postavi WhatsApp poruku kao',
            '/\b[Dd]M s ključnom riječi\b/u' => 'WhatsApp poruka s ključnom riječi',
            '/\b[Dd]M s kljucnom rijeci\b/u' => 'WhatsApp poruka s ključnom riječi',
            '/\b[Ss]end a DM\b/u' => 'Send a WhatsApp message',
            '/\b[Dd]irect message\b/u' => 'WhatsApp message',
            '/\bza DM\b/u' => 'za WhatsApp poruku',
            '/\bna DM\b/u' => 'na WhatsApp poruku',
            '/\bDM\b/u' => 'WhatsApp',
        ];

        foreach($patterns as $pattern => $replacement) {
            $value = preg_replace($pattern, $replacement, $value) ?? $value;
        }

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function strip_app_review_internal_visibility_notes(string $value): string {
        if($value === '') {
            return '';
        }

        $meta_note_pattern = '(?:kao\s+)?(?:sporedn(?:o|i|a|e)|sekundarn(?:o|i|a|e)|rezervn(?:o|i|a|e)|opcionaln(?:o|i|a|e)|zadnj(?:e|i|a)|primarn(?:o|i|a|e)|glavn(?:o|i|a|e)|fallback|backup|secondary|primary)(?:\s+(?:put|smjer|korak|opcija|path|route|step|blok|cta|gumb|link))?';

        $value = preg_replace_callback('/\s*\(([^()]*)\)/u', static function($matches) use ($meta_note_pattern) {
            $inner = trim((string) ($matches[1] ?? ''));

            if($inner !== '' && preg_match('/^' . $meta_note_pattern . '$/iu', $inner)) {
                return '';
            }

            return $matches[0];
        }, $value) ?? $value;

        $value = preg_replace('/\s*(?:[-,:]\s*)' . $meta_note_pattern . '\s*$/iu', '', $value) ?? $value;
        $value = preg_replace('/\s+([,.:!?])/u', '$1', $value) ?? $value;
        $value = preg_replace('/\s{2,}/u', ' ', $value) ?? $value;

        return trim($value, " \t\n\r\0\x0B-,:");
    }

    private function normalize_app_review_visible_copy(string $value): string {
        $value = $this->normalize_app_review_channel_copy($value);
        $value = $this->strip_app_review_internal_visibility_notes($value);

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function normalize_app_review_visible_list(array $items): array {
        $normalized_items = [];

        foreach($items as $item) {
            if(!is_scalar($item)) {
                continue;
            }

            $normalized_item = $this->normalize_app_review_visible_copy((string) $item);

            if($normalized_item === '') {
                continue;
            }

            $normalized_items[] = $normalized_item;
        }

        return $normalized_items;
    }

    private function normalize_ai_final_block_plan($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [];
        }

        $normalized = [];
        $allowed_actions = ['move_up', 'move_down', 'keep_top', 'keep_after_primary', 'consider_remove', 'hide_for_now', 'add_block', 'swap_order', 'keep', 'add'];

        foreach($value as $index => $item) {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(!is_array($item)) {
                continue;
            }

            $label = $this->normalize_app_review_visible_copy((string) ($item['label'] ?? ''));
            $block_type = trim((string) ($item['block_type'] ?? $item['type'] ?? ''));
            $reason = trim((string) ($item['reason'] ?? $item['why'] ?? ''));

            if($label === '' || ($block_type === '' && (int) ($item['block_id'] ?? 0) <= 0)) {
                continue;
            }

            $planned_action = trim((string) ($item['planned_action'] ?? $item['action'] ?? 'keep'));
            if(!in_array($planned_action, $allowed_actions, true)) {
                $planned_action = 'keep';
            }

            $normalized[] = [
                'display_order' => max(1, (int) ($item['display_order'] ?? ($index + 1))),
                'block_id' => max(0, (int) ($item['block_id'] ?? 0)),
                'block_type' => $block_type,
                'label' => $label,
                'source' => trim((string) ($item['source'] ?? 'existing')),
                'status' => trim((string) ($item['status'] ?? '')),
                'planned_action' => $planned_action,
                'reason' => $reason,
                'include_on_app' => array_key_exists('include_on_app', $item) ? !empty($item['include_on_app']) : !in_array($planned_action, ['hide_for_now', 'consider_remove'], true),
                'position' => max(0, (int) ($item['position'] ?? 0)),
                'insert_after_block_id' => max(0, (int) ($item['insert_after_block_id'] ?? 0)),
                'insert_after_type' => trim((string) ($item['insert_after_type'] ?? '')),
                'insert_after_label' => $this->normalize_app_review_visible_copy((string) ($item['insert_after_label'] ?? '')),
            ];

            if(count($normalized) >= 24) {
                break;
            }
        }

        usort($normalized, static function(array $a, array $b): int {
            return ((int) ($a['display_order'] ?? 0) <=> (int) ($b['display_order'] ?? 0))
                ?: ((int) ($a['position'] ?? 0) <=> (int) ($b['position'] ?? 0))
                ?: strcmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return $normalized;
    }

    private function normalize_app_review_matching_key(string $value): string {
        $value = mb_strtolower(trim($value));

        if($value === '') {
            return '';
        }

        $value = strtr($value, [
            'č' => 'c',
            'ć' => 'c',
            'đ' => 'd',
            'š' => 's',
            'ž' => 'z',
        ]);

        return preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? '';
    }

    private function app_review_text_has_any(string $value, array $needles): bool {
        $normalized_value = $this->normalize_app_review_matching_key($value);

        if($normalized_value === '') {
            return false;
        }

        foreach($needles as $needle) {
            $normalized_needle = $this->normalize_app_review_matching_key((string) $needle);

            if($normalized_needle !== '' && str_contains($normalized_value, $normalized_needle)) {
                return true;
            }
        }

        return false;
    }

    private function get_contextual_app_review_link_copy_value(string $block_type, string $current_label, string $goal_type): string {
        $current_label = trim($current_label);
        $is_business_offer = $this->app_review_text_has_any($current_label, ['start paket', 'start-paket', 'suradnik', 'partner', 'upis', 'registracija', 'prijava']);
        $is_discount_offer = $this->app_review_text_has_any($current_label, ['web shop', 'webshop', 'shop', 'popust', 'forever living', 'forever webshop', 'kupnja']);

        if($block_type === 'link_forever_shop') {
            if($this->app_review_text_has_any($current_label, ['upis', 'prijava', 'registracija', 'partner', 'suradnik'])) {
                return 'Prijavi se kao Forever partner';
            }

            return 'Postani Forever partner';
        }

        if($this->app_review_text_has_any($current_label, ['partner', 'suradnja', 'suradnik', 'upis', 'registracija', 'prijava'])) {
            if($this->app_review_text_has_any($current_label, ['partner'])) {
                return 'Pogledaj kako postati partner';
            }

            if($this->app_review_text_has_any($current_label, ['upis', 'prijava', 'registracija'])) {
                return 'Pogledaj kako izgleda upis';
            }

            return 'Saznaj kako izgleda suradnja';
        }

        if($block_type === 'link_forever_product' && $is_business_offer) {
            return 'Postani Forever suradnik';
        }

        if(
            $is_discount_offer
            || in_array($block_type, ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)
        ) {
            return 'Pogledaj proizvode s popustom';
        }

        if($this->app_review_text_has_any($current_label, ['proizvod', 'proizvodi']) || $block_type === 'link_forever_product') {
            return 'Pogledaj preporučene proizvode';
        }

        if($this->app_review_text_has_any($current_label, ['whatsapp'])) {
            return 'Pošalji poruku na WhatsApp';
        }

        return match($goal_type) {
            'business' => 'Saznaj kako izgleda suradnja',
            'shop' => 'Pogledaj proizvode i ponudu',
            'activation' => 'Javi se i saznaj više',
            default => 'Pogledaj više detalja',
        };
    }

    private function should_force_contextual_app_review_link_copy(string $suggested_value, string $current_label, string $block_type, string $goal_type): bool {
        $suggested_value = trim($suggested_value);

        if($suggested_value === '') {
            return true;
        }

        if($this->app_review_text_has_any($suggested_value, [
            'saznaj više i otvori sljedeći korak',
            'saznaj vise i otvori sljedeci korak',
            'otvori sljedeći korak',
            'otvori sljedeci korak',
            'sljedeći korak',
            'sljedeci korak',
            'glavni korak',
        ])) {
            return true;
        }

        if($block_type === 'link_forever_shop' && !$this->app_review_text_has_any($suggested_value, ['forever', 'partner', 'suradnik', 'upis', 'prijava', 'registracija'])) {
            return true;
        }

        if($block_type === 'link_forever_product'
            && $this->app_review_text_has_any($current_label, ['start paket', 'start-paket', 'partner', 'suradnik', 'upis', 'prijava', 'registracija'])
            && !$this->app_review_text_has_any($suggested_value, ['suradnik', 'partner', 'upis', 'prijava', 'start'])) {
            return true;
        }

        if($this->app_review_text_has_any($current_label, ['partner', 'suradnja', 'upis', 'prijava', 'registracija'])
            && !$this->app_review_text_has_any($suggested_value, ['partner', 'suradnja', 'upis', 'prijava', 'registracija'])) {
            return true;
        }

        if((
                $this->app_review_text_has_any($current_label, ['web shop', 'webshop', 'shop', 'popust', 'forever living', 'forever webshop', 'kupnja'])
                || in_array($block_type, ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)
            )
            && !$this->app_review_text_has_any($suggested_value, ['shop', 'webshop', 'ponud', 'popust', 'forever', 'kup', 'proizvod']) ) {
            return true;
        }

        if(($this->app_review_text_has_any($current_label, ['proizvod', 'proizvodi']) || $block_type === 'link_forever_product')
            && !$this->app_review_text_has_any($suggested_value, ['proizvod', 'ponud'])) {
            return true;
        }

        if($goal_type === 'business' && $block_type === 'link' && !$this->app_review_text_has_any($suggested_value, ['suradnja', 'partner', 'upis', 'prijava', 'detalj'])) {
            return true;
        }

        return false;
    }

    private function refine_app_review_copy_suggestions(array $copy_suggestions, ?array $selected_app = null, string $goal_type = '', string $owner_name = ''): array {
        if(empty($copy_suggestions)) {
            return [];
        }

        $block_map = [];
        foreach((array) ($selected_app['ordered_block_previews'] ?? []) as $preview) {
            if(!is_array($preview)) {
                continue;
            }

            $block_id = (int) ($preview['block_id'] ?? 0);

            if($block_id <= 0) {
                continue;
            }

            $block_map[$block_id] = [
                'type' => (string) ($preview['type'] ?? ''),
                'label' => (string) ($preview['label'] ?? ''),
            ];
        }

        $refined = [];

        foreach($copy_suggestions as $item) {
            if(!is_array($item)) {
                continue;
            }

            $block_id = (int) ($item['block_id'] ?? 0);
            $block_type = trim((string) ($item['block_type'] ?? ''));
            $current_label = '';

            if($block_id > 0 && isset($block_map[$block_id])) {
                $block_type = trim((string) ($block_map[$block_id]['type'] ?? $block_type));
                $current_label = trim((string) ($block_map[$block_id]['label'] ?? ''));
            }

            $value = trim((string) ($item['value'] ?? ''));
            $role_key = trim((string) ($item['role_key'] ?? ''));

            if($role_key === 'owner_identity' && $owner_name !== '') {
                $item['value'] = $owner_name;
            } elseif(in_array($block_type, ['link', 'link_forever_shop', 'link_forever_product', 'link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)) {
                if($this->should_force_contextual_app_review_link_copy($value, $current_label, $block_type, $goal_type)) {
                    $item['value'] = $this->get_contextual_app_review_link_copy_value($block_type, $current_label, $goal_type);
                }
            }

            $item['value'] = $this->normalize_app_review_visible_copy($this->sanitize_ai_string($item['value'] ?? '', 180));

            if($item['value'] === '') {
                continue;
            }

            $refined[] = $item;
        }

        return array_slice($refined, 0, 8);
    }

    private function get_default_app_review_missing_picker_context(string $block_type): array {
        return match(trim($block_type)) {
            'heading', 'header', 'avatar', 'image', 'paragraph', 'markdown', 'video', 'youtube', 'vimeo' => ['preferred_group' => 'start', 'preferred_goal' => 'trust', 'picker_search' => l('link.biolink.blocks.' . trim($block_type))],
            'lead_funnel' => ['preferred_group' => 'sales', 'preferred_goal' => 'lead_capture', 'picker_search' => 'Funnel'],
            'custom_html_whatsapp' => ['preferred_group' => 'contacts', 'preferred_goal' => 'lead_capture', 'picker_search' => 'WhatsApp'],
            'link_forever_shop' => ['preferred_group' => 'forever', 'preferred_goal' => 'lead_capture', 'picker_search' => l('link.biolink.blocks.' . trim($block_type))],
            'link_forever_product', 'link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo' => ['preferred_group' => 'forever', 'preferred_goal' => 'product_recommendation', 'picker_search' => l('link.biolink.blocks.' . trim($block_type))],
            'link' => ['preferred_group' => 'sales', 'preferred_goal' => 'lead_capture', 'picker_search' => l('link.biolink.blocks.link')],
            default => ['preferred_group' => '', 'preferred_goal' => '', 'picker_search' => l('link.biolink.blocks.' . trim($block_type))],
        };
    }

    private function refine_app_review_missing_block_recommendations(array $recommendations, string $owner_name = ''): array {
        if(empty($recommendations)) {
            return [];
        }

        $owner_key = $this->normalize_app_review_matching_key($owner_name);
        $refined = [];

        foreach($recommendations as $item) {
            if(!is_array($item)) {
                continue;
            }

            $block_type = trim((string) ($item['block_type'] ?? ''));
            if(in_array($block_type, ['video', 'tiktok_video', 'twitter_video', 'vk_video'], true)) {
                $block_type = 'youtube';
                $item['block_type'] = 'youtube';
            }

            if($block_type === '') {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $seed_text = trim((string) (($item['seed_settings']['text'] ?? '') ?: ($item['seed_settings']['name'] ?? '') ?: ''));
            $combined_identity_text = $this->normalize_app_review_matching_key(trim($label . ' ' . $seed_text));

            if($block_type === 'heading' && ($this->app_review_text_has_any($combined_identity_text, ['ime i prezime']) || ($owner_key !== '' && $combined_identity_text !== '' && (str_contains($combined_identity_text, $owner_key) || str_contains($owner_key, $combined_identity_text))))) {
                $item['role_key'] = 'owner_identity';
                $item['allow_existing_type'] = true;
            } elseif($block_type === 'lead_funnel' && empty($item['role_key'])) {
                $item['role_key'] = 'primary_funnel';
            } elseif($block_type === 'custom_html_whatsapp' && empty($item['role_key'])) {
                $item['role_key'] = 'whatsapp_backup';
            } elseif($block_type === 'link_discount' && empty($item['role_key'])) {
                $item['role_key'] = 'core_discount_offer';
            } elseif($block_type === 'link_forever_product' && empty($item['role_key']) && $this->app_review_text_has_any(trim($label . ' ' . $seed_text), ['start paket', 'start-paket', 'partner', 'suradnik', 'upis', 'prijava', 'registracija'])) {
                $item['role_key'] = 'core_business_offer';
            } elseif(in_array($block_type, ['youtube', 'vimeo'], true) && empty($item['role_key'])) {
                $item['role_key'] = 'trust_video';
            }

            $picker_context = $this->get_default_app_review_missing_picker_context($block_type);
            $item['preferred_group'] = (string) (($item['preferred_group'] ?? '') ?: ($picker_context['preferred_group'] ?? ''));
            $item['preferred_goal'] = (string) (($item['preferred_goal'] ?? '') ?: ($picker_context['preferred_goal'] ?? ''));
            $item['picker_search'] = (string) (($item['picker_search'] ?? '') ?: ($picker_context['picker_search'] ?? ''));
            $refined[] = $item;
        }

        return array_slice($refined, 0, 6);
    }

    private function normalize_app_review_channel_list(array $items): array {
        $normalized_items = [];

        foreach($items as $item) {
            if(!is_scalar($item)) {
                continue;
            }

            $normalized_item = $this->normalize_app_review_channel_copy((string) $item);

            if($normalized_item === '') {
                continue;
            }

            $normalized_items[] = $normalized_item;
        }

        return $normalized_items;
    }

    private function normalize_app_review_color_palette($value): array {
        $field_map = [
            'background' => ['background', 'background_color', 'app_background', 'background_hex'],
            'heading' => ['heading', 'heading_color', 'title', 'title_color', 'headline'],
            'text' => ['text', 'text_color', 'body_text', 'text_body', 'content_text'],
            'primary_block_text' => ['primary_block_text', 'main_block_text', 'first_block_text', 'primary_cta_text'],
            'primary_block_background' => ['primary_block_background', 'main_block_background', 'first_block_background', 'primary_cta_background'],
            'primary_block_border' => ['primary_block_border', 'main_block_border', 'first_block_border', 'primary_cta_border'],
            'primary_block_shadow' => ['primary_block_shadow', 'main_block_shadow', 'first_block_shadow', 'primary_cta_shadow'],
            'secondary_blocks_text' => ['secondary_blocks_text', 'other_blocks_text', 'secondary_block_text', 'remaining_blocks_text'],
            'secondary_blocks_background' => ['secondary_blocks_background', 'other_blocks_background', 'secondary_block_background', 'remaining_blocks_background'],
            'secondary_blocks_border' => ['secondary_blocks_border', 'other_blocks_border', 'secondary_block_border', 'remaining_blocks_border'],
            'secondary_blocks_shadow' => ['secondary_blocks_shadow', 'other_blocks_shadow', 'secondary_block_shadow', 'remaining_blocks_shadow'],
        ];

        $normalized = array_fill_keys(array_keys($field_map), '');
        $normalized['legacy_list'] = [];

        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(is_string($value)) {
            $normalized['legacy_list'] = $this->normalize_app_review_channel_list($this->normalize_ai_list($value, 11, 200));
            return $normalized;
        }

        if(!is_array($value)) {
            return $normalized;
        }

        $has_structured_values = false;

        foreach($field_map as $field_key => $aliases) {
            foreach($aliases as $alias) {
                if(!array_key_exists($alias, $value) || !is_scalar($value[$alias])) {
                    continue;
                }

                $normalized_value = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($value[$alias], 200));

                if($normalized_value === '') {
                    continue;
                }

                $normalized[$field_key] = $normalized_value;
                $has_structured_values = true;
                break;
            }
        }

        if(isset($value['legacy_list']) || isset($value['items']) || isset($value['list'])) {
            $legacy_source = $value['legacy_list'] ?? $value['items'] ?? $value['list'] ?? [];
            $normalized['legacy_list'] = $this->normalize_app_review_channel_list($this->normalize_ai_list($legacy_source, 11, 200));
        }

        if(!$has_structured_values) {
            $normalized['legacy_list'] = $this->normalize_app_review_channel_list(array_values(array_filter($value, 'is_scalar')));
        }

        return $normalized;
    }

    private function extract_first_hex_color(string $value): string {
        if(preg_match('/#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})\b/', $value, $matches)) {
            return strtoupper($matches[0]);
        }

        return '';
    }

    private function normalize_ai_css_color_value($value, bool $allow_rgba = false): string {
        if(!is_scalar($value)) {
            return '';
        }

        $value = trim((string) $value);

        if($value === '') {
            return '';
        }

        if($allow_rgba && preg_match('/^rgba?\(\s*[\d.\s,%]+\)$/i', $value)) {
            return preg_replace('/\s+/', ' ', $value) ?? $value;
        }

        return $this->extract_first_hex_color($value);
    }

    private function hex_to_rgb_triplet(string $value): array {
        $hex = $this->extract_first_hex_color($value);

        if($hex === '') {
            return [];
        }

        $hex = ltrim($hex, '#');

        if(strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        } elseif(strlen($hex) === 8) {
            $hex = substr($hex, 0, 6);
        }

        if(strlen($hex) !== 6) {
            return [];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    private function rgb_triplet_to_hex(array $rgb): string {
        if(!isset($rgb['r'], $rgb['g'], $rgb['b'])) {
            return '';
        }

        return sprintf(
            '#%02X%02X%02X',
            max(0, min(255, (int) round($rgb['r']))),
            max(0, min(255, (int) round($rgb['g']))),
            max(0, min(255, (int) round($rgb['b'])))
        );
    }

    private function rgb_triplet_to_hsl(array $rgb): array {
        if(!isset($rgb['r'], $rgb['g'], $rgb['b'])) {
            return [];
        }

        $r = max(0, min(255, (float) $rgb['r'])) / 255;
        $g = max(0, min(255, (float) $rgb['g'])) / 255;
        $b = max(0, min(255, (float) $rgb['b'])) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $h = 0.0;
        $s = 0.0;
        $l = ($max + $min) / 2;

        if($max !== $min) {
            $delta = $max - $min;
            $s = $l > 0.5 ? $delta / (2 - $max - $min) : $delta / ($max + $min);

            switch($max) {
                case $r:
                    $h = ($g - $b) / $delta + ($g < $b ? 6 : 0);
                    break;

                case $g:
                    $h = ($b - $r) / $delta + 2;
                    break;

                default:
                    $h = ($r - $g) / $delta + 4;
                    break;
            }

            $h /= 6;
        }

        return [
            'h' => $h * 360,
            's' => $s,
            'l' => $l,
        ];
    }

    private function hsl_to_rgb_triplet(float $h, float $s, float $l): array {
        $h = fmod(($h < 0 ? $h + 360 : $h), 360) / 360;
        $s = max(0, min(1, $s));
        $l = max(0, min(1, $l));

        if($s == 0.0) {
            $value = (int) round($l * 255);

            return [
                'r' => $value,
                'g' => $value,
                'b' => $value,
            ];
        }

        $q = $l < 0.5 ? $l * (1 + $s) : ($l + $s - ($l * $s));
        $p = 2 * $l - $q;
        $hue_to_rgb = static function(float $p, float $q, float $t): float {
            if($t < 0) {
                $t += 1;
            }

            if($t > 1) {
                $t -= 1;
            }

            if($t < 1 / 6) {
                return $p + ($q - $p) * 6 * $t;
            }

            if($t < 1 / 2) {
                return $q;
            }

            if($t < 2 / 3) {
                return $p + ($q - $p) * (2 / 3 - $t) * 6;
            }

            return $p;
        };

        return [
            'r' => (int) round($hue_to_rgb($p, $q, $h + 1 / 3) * 255),
            'g' => (int) round($hue_to_rgb($p, $q, $h) * 255),
            'b' => (int) round($hue_to_rgb($p, $q, $h - 1 / 3) * 255),
        ];
    }

    private function get_hex_relative_luminance(string $value): ?float {
        $rgb = $this->hex_to_rgb_triplet($value);

        if(empty($rgb)) {
            return null;
        }

        $linearize = static function(float $channel): float {
            $channel /= 255;

            return $channel <= 0.03928
                ? $channel / 12.92
                : pow(($channel + 0.055) / 1.055, 2.4);
        };

        $r = $linearize((float) $rgb['r']);
        $g = $linearize((float) $rgb['g']);
        $b = $linearize((float) $rgb['b']);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private function get_hex_contrast_ratio(string $foreground, string $background): float {
        $foreground_luminance = $this->get_hex_relative_luminance($foreground);
        $background_luminance = $this->get_hex_relative_luminance($background);

        if($foreground_luminance === null || $background_luminance === null) {
            return 0.0;
        }

        $lighter = max($foreground_luminance, $background_luminance);
        $darker = min($foreground_luminance, $background_luminance);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function get_safe_contrast_text_hex(string $background): string {
        $dark = '#0F172A';
        $light = '#F8FAFC';

        return $this->get_hex_contrast_ratio($dark, $background) >= $this->get_hex_contrast_ratio($light, $background)
            ? $dark
            : $light;
    }

    private function soften_ai_theme_hex(string $hex, string $role): string {
        $hex = $this->extract_first_hex_color($hex);

        if($hex === '') {
            return '';
        }

        $signal = $this->get_app_review_color_signal($hex);
        $rgb = $this->hex_to_rgb_triplet($hex);
        $hsl = $this->rgb_triplet_to_hsl($rgb);

        if(empty($signal) || empty($rgb) || empty($hsl)) {
            return $hex;
        }

        $family = (string) ($signal['family'] ?? '');
        $lightness = (string) ($signal['lightness'] ?? '');
        $intensity = (string) ($signal['intensity'] ?? '');
        $is_canary_risk = in_array($family, ['yellow', 'lime'], true) && in_array($lightness, ['light', 'very_light'], true) && $intensity === 'strong';
        $is_neon_risk = $hsl['s'] > 0.78 && $hsl['l'] > 0.66;

        if($role === 'secondary_blocks_background') {
            if($hsl['s'] > 0.42) {
                $hsl['s'] = 0.28;
            }

            if($is_canary_risk) {
                $hsl['h'] = 46;
                $hsl['s'] = 0.22;
                $hsl['l'] = min($hsl['l'], 0.90);
            }
        } elseif(in_array($role, ['background_color', 'gradient_start', 'gradient_end'], true)) {
            if($hsl['s'] > 0.58 && $hsl['l'] > 0.68) {
                $hsl['s'] = 0.34;
                $hsl['l'] = 0.56;
            }

            if($is_canary_risk) {
                $hsl['h'] = 44;
                $hsl['s'] = 0.30;
                $hsl['l'] = 0.50;
            }
        } elseif(in_array($role, ['primary_block_background', 'primary_block_border'], true)) {
            if($is_neon_risk) {
                $hsl['s'] = 0.62;
                $hsl['l'] = 0.52;
            }

            if($is_canary_risk) {
                $hsl['h'] = 42;
                $hsl['s'] = 0.58;
                $hsl['l'] = 0.46;
            }
        }

        return $this->rgb_triplet_to_hex($this->hsl_to_rgb_triplet($hsl['h'], $hsl['s'], $hsl['l']));
    }

    private function get_ai_tinted_surface_hex(string $hex, float $lightness, float $max_saturation = 0.16, float $min_saturation = 0.05): string {
        $hex = $this->extract_first_hex_color($hex);

        if($hex === '') {
            return '';
        }

        $rgb = $this->hex_to_rgb_triplet($hex);
        $hsl = $this->rgb_triplet_to_hsl($rgb);

        if(empty($rgb) || empty($hsl)) {
            return '';
        }

        $hsl['s'] = max($min_saturation, min($max_saturation, (float) ($hsl['s'] ?? 0.0)));
        $hsl['l'] = max(0.0, min(1.0, $lightness));

        return $this->rgb_triplet_to_hex($this->hsl_to_rgb_triplet($hsl['h'], $hsl['s'], $hsl['l']));
    }

    private function is_ai_light_surface_hex(string $hex): bool {
        $hex = $this->extract_first_hex_color($hex);

        if($hex === '') {
            return false;
        }

        $signal = $this->get_app_review_color_signal($hex);

        return in_array((string) ($signal['lightness'] ?? ''), ['light', 'very_light'], true);
    }

    private function is_ai_sterile_canvas_hex(string $hex): bool {
        $hex = $this->extract_first_hex_color($hex);

        if($hex === '') {
            return false;
        }

        $rgb = $this->hex_to_rgb_triplet($hex);
        $hsl = $this->rgb_triplet_to_hsl($rgb);

        if(empty($rgb) || empty($hsl)) {
            return false;
        }

        return ((float) ($hsl['l'] ?? 0.0) >= 0.90) && ((float) ($hsl['s'] ?? 0.0) <= 0.18);
    }

    private function diversify_sterile_goal_first_theme_pack(array $theme_pack): array {
        $primary_background = (string) ($theme_pack['primary_block_background'] ?? '');

        if($primary_background === '') {
            return $theme_pack;
        }

        $background_mode = (string) ($theme_pack['background_mode'] ?? 'color');
        $background_color = (string) ($theme_pack['background_color'] ?? '');
        $gradient_start = (string) ($theme_pack['gradient_start'] ?? '');
        $gradient_end = (string) ($theme_pack['gradient_end'] ?? '');
        $secondary_background = (string) ($theme_pack['secondary_blocks_background'] ?? '');

        $background_is_sterile = $this->is_ai_sterile_canvas_hex($background_color);
        $secondary_is_sterile = $this->is_ai_sterile_canvas_hex($secondary_background);
        $gradient_is_sterile = $this->is_ai_sterile_canvas_hex($gradient_start) && $this->is_ai_sterile_canvas_hex($gradient_end);

        if($background_mode === 'gradient' && $gradient_is_sterile) {
            $theme_pack['gradient_start'] = $this->get_ai_tinted_surface_hex($primary_background, 0.92, 0.18, 0.07) ?: $gradient_start;
            $theme_pack['gradient_end'] = $this->get_ai_tinted_surface_hex($primary_background, 0.975, 0.12, 0.04) ?: $gradient_end;
            $theme_pack['background_color'] = (string) ($theme_pack['gradient_start'] ?? $background_color);

            if($secondary_is_sterile) {
                $theme_pack['secondary_blocks_background'] = $this->get_ai_tinted_surface_hex($primary_background, 0.985, 0.10, 0.04) ?: $secondary_background;
            }
        } elseif($background_mode === 'color' && $background_is_sterile && $secondary_is_sterile) {
            $theme_pack['background_mode'] = 'gradient';
            $theme_pack['background_color'] = $this->get_ai_tinted_surface_hex($primary_background, 0.93, 0.18, 0.07) ?: $background_color;
            $theme_pack['gradient_start'] = $this->get_ai_tinted_surface_hex($primary_background, 0.92, 0.18, 0.07) ?: $background_color;
            $theme_pack['gradient_end'] = $this->get_ai_tinted_surface_hex($primary_background, 0.975, 0.12, 0.04) ?: $secondary_background;
            $theme_pack['secondary_blocks_background'] = $this->get_ai_tinted_surface_hex($primary_background, 0.985, 0.10, 0.04) ?: $secondary_background;
        }

        return $theme_pack;
    }

    private function enforce_goal_first_theme_pack_guardrails(array $theme_pack): array {
        $hex_roles = [
            'background_color',
            'gradient_start',
            'gradient_end',
            'heading_color',
            'text_color',
            'primary_block_text',
            'primary_block_background',
            'primary_block_border',
            'secondary_blocks_text',
            'secondary_blocks_background',
            'secondary_blocks_border',
        ];

        foreach($hex_roles as $role) {
            if(($theme_pack[$role] ?? '') === '') {
                continue;
            }

            if(in_array($role, ['background_color', 'gradient_start', 'gradient_end', 'primary_block_background', 'primary_block_border', 'secondary_blocks_background'], true)) {
                $theme_pack[$role] = $this->soften_ai_theme_hex((string) $theme_pack[$role], $role);
            } else {
                $theme_pack[$role] = $this->extract_first_hex_color((string) $theme_pack[$role]);
            }
        }

        if(
            !empty($theme_pack['primary_block_background'])
            && !empty($theme_pack['secondary_blocks_background'])
            && strtoupper((string) $theme_pack['primary_block_background']) === strtoupper((string) $theme_pack['secondary_blocks_background'])
        ) {
            $primary_signal = $this->get_app_review_color_signal((string) $theme_pack['primary_block_background']);
            $theme_pack['secondary_blocks_background'] = in_array((string) ($primary_signal['lightness'] ?? ''), ['light', 'very_light'], true)
                ? '#F3F6F4'
                : '#111827';
        }

        $theme_pack = $this->diversify_sterile_goal_first_theme_pack($theme_pack);

        $canvas_reference = (string) ($theme_pack['background_color'] ?: ($theme_pack['gradient_start'] ?: $theme_pack['secondary_blocks_background']));

        if($canvas_reference !== '') {
            if($theme_pack['heading_color'] === '' || $this->get_hex_contrast_ratio((string) $theme_pack['heading_color'], $canvas_reference) < 3.8) {
                $theme_pack['heading_color'] = $this->get_safe_contrast_text_hex($canvas_reference);
            }

            if($theme_pack['text_color'] === '' || $this->get_hex_contrast_ratio((string) $theme_pack['text_color'], $canvas_reference) < 3.3) {
                $theme_pack['text_color'] = $this->get_safe_contrast_text_hex($canvas_reference);
            }
        }

        if(!empty($theme_pack['primary_block_background']) && ($theme_pack['primary_block_text'] === '' || $this->get_hex_contrast_ratio((string) $theme_pack['primary_block_text'], (string) $theme_pack['primary_block_background']) < 4.2)) {
            $theme_pack['primary_block_text'] = $this->get_safe_contrast_text_hex((string) $theme_pack['primary_block_background']);
        }

        if(!empty($theme_pack['secondary_blocks_background']) && ($theme_pack['secondary_blocks_text'] === '' || $this->get_hex_contrast_ratio((string) $theme_pack['secondary_blocks_text'], (string) $theme_pack['secondary_blocks_background']) < 4.0)) {
            $theme_pack['secondary_blocks_text'] = $this->get_safe_contrast_text_hex((string) $theme_pack['secondary_blocks_background']);
        }

        if($theme_pack['secondary_blocks_border'] === '' && $theme_pack['secondary_blocks_background'] !== '') {
            $secondary_signal = $this->get_app_review_color_signal((string) $theme_pack['secondary_blocks_background']);
            $theme_pack['secondary_blocks_border'] = in_array((string) ($secondary_signal['lightness'] ?? ''), ['light', 'very_light'], true)
                ? '#CBD5E1'
                : '#334155';
        }

        if($theme_pack['primary_block_border'] === '' && $theme_pack['primary_block_background'] !== '') {
            $theme_pack['primary_block_border'] = (string) $theme_pack['primary_block_background'];
        }

        return $theme_pack;
    }

    private function sync_app_review_color_palette_with_theme_pack(array $color_palette, array $theme_pack): array {
        $fallback_messages = [
            'background' => 'daje mirnu i čitljivu pozadinu za fokus.',
            'heading' => 'drži naslov jasnim i lako uočljivim.',
            'text' => 'održava tekst laganim za čitanje.',
            'primary_block_text' => 'daje jasan kontrast glavnom bloku.',
            'primary_block_background' => 'vizualno ističe glavni korak.',
            'primary_block_border' => 'dodatno naglašava glavni blok bez viška šuma.',
            'primary_block_shadow' => 'dodaje blagi naglasak bez teškog efekta.',
            'secondary_blocks_text' => 'održava ostale blokove čitljivima.',
            'secondary_blocks_background' => 'smiruje sekundarne blokove kako glavni ne bi izgubio fokus.',
            'secondary_blocks_border' => 'blago odvaja sekundarne blokove bez novog naglaska.',
            'secondary_blocks_shadow' => 'ostavlja sekundarne blokove nenametljivima.',
        ];

        $theme_map = [
            'background' => $theme_pack['background_mode'] === 'gradient'
                ? (string) ($theme_pack['gradient_start'] ?? '')
                : (string) ($theme_pack['background_color'] ?? ''),
            'heading' => (string) ($theme_pack['heading_color'] ?? ''),
            'text' => (string) ($theme_pack['text_color'] ?? ''),
            'primary_block_text' => (string) ($theme_pack['primary_block_text'] ?? ''),
            'primary_block_background' => (string) ($theme_pack['primary_block_background'] ?? ''),
            'primary_block_border' => (string) ($theme_pack['primary_block_border'] ?? ''),
            'primary_block_shadow' => (string) ($theme_pack['primary_block_shadow'] ?? ''),
            'secondary_blocks_text' => (string) ($theme_pack['secondary_blocks_text'] ?? ''),
            'secondary_blocks_background' => (string) ($theme_pack['secondary_blocks_background'] ?? ''),
            'secondary_blocks_border' => (string) ($theme_pack['secondary_blocks_border'] ?? ''),
            'secondary_blocks_shadow' => (string) ($theme_pack['secondary_blocks_shadow'] ?? ''),
        ];

        foreach($theme_map as $key => $replacement) {
            if($replacement === '') {
                continue;
            }

            $current_value = (string) ($color_palette[$key] ?? '');

            if($current_value === '') {
                $color_palette[$key] = $replacement . ' ' . ($fallback_messages[$key] ?? '');
                continue;
            }

            if(str_contains($current_value, '#')) {
                $color_palette[$key] = preg_replace('/#(?:[A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})\b/', $replacement, $current_value, 1) ?? $current_value;
            } elseif(!str_starts_with($current_value, $replacement)) {
                $color_palette[$key] = $replacement . ' ' . $current_value;
            }
        }

        return $color_palette;
    }

    private function normalize_app_review_theme_pack($value, array $fallback_color_palette = []): array {
        $normalized = [
            'name' => '',
            'summary' => '',
            'background_mode' => 'color',
            'background_color' => '',
            'gradient_start' => '',
            'gradient_end' => '',
            'gradient_style' => 'current_135deg',
            'heading_color' => '',
            'text_color' => '',
            'primary_block_text' => '',
            'primary_block_background' => '',
            'primary_block_border' => '',
            'primary_block_shadow' => '',
            'secondary_blocks_text' => '',
            'secondary_blocks_background' => '',
            'secondary_blocks_border' => '',
            'secondary_blocks_shadow' => '',
            'font' => '',
            'font_size' => 0,
            'width' => '',
            'block_spacing' => '',
            'hover_animation' => '',
            'migration_note' => '',
        ];

        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(is_array($value)) {
            $available_fonts = array_keys((array) (settings()->links->biolinks_fonts ?? []));
            $normalized['name'] = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($value['name'] ?? $value['theme_name'] ?? '', 120));
            $normalized['summary'] = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($value['summary'] ?? $value['why'] ?? '', 220));
            $background_mode = (string) ($value['background_mode'] ?? $value['mode'] ?? 'color');
            $normalized['background_mode'] = in_array($background_mode, ['color', 'gradient'], true) ? $background_mode : 'color';
            $normalized['gradient_style'] = 'current_135deg';
            $normalized['migration_note'] = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($value['migration_note'] ?? $value['transition_note'] ?? '', 220));

            $font = trim((string) ($value['font'] ?? $value['font_key'] ?? ''));
            if($font !== '' && in_array($font, $available_fonts, true)) {
                $normalized['font'] = $font;
            }

            $font_size = (int) ($value['font_size'] ?? 0);
            if($font_size >= 12 && $font_size <= 22) {
                $normalized['font_size'] = $font_size;
            }

            $width = trim((string) ($value['width'] ?? $value['layout_width'] ?? ''));
            if(in_array($width, ['6', '8', '10', '12'], true)) {
                $normalized['width'] = $width;
            }

            $block_spacing = trim((string) ($value['block_spacing'] ?? $value['spacing'] ?? ''));
            if(in_array($block_spacing, ['1', '2', '3'], true)) {
                $normalized['block_spacing'] = $block_spacing;
            }

            $hover_animation = trim((string) ($value['hover_animation'] ?? $value['hover_style'] ?? ''));
            if(in_array($hover_animation, ['false', 'smooth', 'instant'], true)) {
                $normalized['hover_animation'] = $hover_animation;
            }

            $color_alias_map = [
                'background_color' => ['background_color', 'background', 'color'],
                'gradient_start' => ['gradient_start', 'background_color_one', 'gradient_color_one', 'color_one'],
                'gradient_end' => ['gradient_end', 'background_color_two', 'gradient_color_two', 'color_two'],
                'heading_color' => ['heading_color', 'heading', 'title_color'],
                'text_color' => ['text_color', 'text', 'body_text'],
                'primary_block_text' => ['primary_block_text', 'primary_text_color', 'main_block_text'],
                'primary_block_background' => ['primary_block_background', 'primary_background_color', 'main_block_background'],
                'primary_block_border' => ['primary_block_border', 'primary_border_color', 'main_block_border'],
                'primary_block_shadow' => ['primary_block_shadow', 'primary_shadow', 'main_block_shadow'],
                'secondary_blocks_text' => ['secondary_blocks_text', 'secondary_text_color', 'other_blocks_text'],
                'secondary_blocks_background' => ['secondary_blocks_background', 'secondary_background_color', 'other_blocks_background'],
                'secondary_blocks_border' => ['secondary_blocks_border', 'secondary_border_color', 'other_blocks_border'],
                'secondary_blocks_shadow' => ['secondary_blocks_shadow', 'secondary_shadow', 'other_blocks_shadow'],
            ];

            foreach($color_alias_map as $field_key => $aliases) {
                foreach($aliases as $alias) {
                    if(!array_key_exists($alias, $value)) {
                        continue;
                    }

                    $normalized_value = str_contains($field_key, 'shadow')
                        ? $this->normalize_ai_css_color_value($value[$alias], true)
                        : $this->normalize_ai_css_color_value($value[$alias]);

                    if($normalized_value === '') {
                        continue;
                    }

                    $normalized[$field_key] = $normalized_value;
                    break;
                }
            }
        }

        $fallback_map = [
            'background_color' => 'background',
            'heading_color' => 'heading',
            'text_color' => 'text',
            'primary_block_text' => 'primary_block_text',
            'primary_block_background' => 'primary_block_background',
            'primary_block_border' => 'primary_block_border',
            'primary_block_shadow' => 'primary_block_shadow',
            'secondary_blocks_text' => 'secondary_blocks_text',
            'secondary_blocks_background' => 'secondary_blocks_background',
            'secondary_blocks_border' => 'secondary_blocks_border',
            'secondary_blocks_shadow' => 'secondary_blocks_shadow',
        ];

        foreach($fallback_map as $theme_key => $palette_key) {
            if($normalized[$theme_key] !== '') {
                continue;
            }

            $palette_value = (string) ($fallback_color_palette[$palette_key] ?? '');
            $normalized[$theme_key] = str_contains($theme_key, 'shadow')
                ? $this->normalize_ai_css_color_value($palette_value, true)
                : $this->normalize_ai_css_color_value($palette_value);
        }

        if($normalized['background_mode'] === 'gradient' && ($normalized['gradient_start'] === '' || $normalized['gradient_end'] === '')) {
            $normalized['background_mode'] = 'color';
        }

        if($normalized['background_mode'] === 'color' && $normalized['background_color'] === '') {
            $background_fallback = (string) ($normalized['gradient_start'] ?: '');

            if($background_fallback === '' || $this->is_ai_sterile_canvas_hex($background_fallback)) {
                $secondary_background = (string) ($normalized['secondary_blocks_background'] ?? '');
                $accent_reference = (string) ($normalized['primary_block_background'] ?: ($normalized['secondary_blocks_border'] ?: ''));

                $background_fallback = $this->is_ai_sterile_canvas_hex($secondary_background)
                    ? ($this->get_ai_tinted_surface_hex($accent_reference, 0.93, 0.18, 0.07) ?: $secondary_background)
                    : $secondary_background;
            }

            $normalized['background_color'] = $background_fallback;
        }

        if($normalized['name'] === '') {
            $normalized['name'] = 'AI preporučena tema';
        }

        return $this->enforce_goal_first_theme_pack_guardrails($normalized);
    }

    private function normalize_app_review_primary_block_plan($value, array $fallback_snapshot = []): array {
        $normalized = [
            'block_id' => (int) ($fallback_snapshot['block_id'] ?? 0),
            'block_type' => (string) ($fallback_snapshot['type'] ?? ''),
            'label' => (string) ($fallback_snapshot['label'] ?? ''),
            'reason' => '',
            'emphasis' => 'strong',
            'apply_theme_emphasis' => true,
        ];

        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(is_array($value)) {
            $normalized['block_id'] = (int) ($value['block_id'] ?? $value['primary_block_id'] ?? $normalized['block_id']);
            $normalized['block_type'] = $this->sanitize_ai_string($value['block_type'] ?? $value['type'] ?? $normalized['block_type'], 64);
            $normalized['label'] = $this->normalize_app_review_visible_copy($this->sanitize_ai_string($value['label'] ?? $value['name'] ?? $normalized['label'], 160));
            $normalized['reason'] = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($value['reason'] ?? $value['why'] ?? $value['focus_reason'] ?? '', 220));

            $emphasis = (string) ($value['emphasis'] ?? $value['emphasis_level'] ?? 'strong');
            $normalized['emphasis'] = in_array($emphasis, ['soft', 'balanced', 'strong'], true) ? $emphasis : 'strong';

            if(array_key_exists('apply_theme_emphasis', $value)) {
                $normalized['apply_theme_emphasis'] = filter_var($value['apply_theme_emphasis'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $normalized['apply_theme_emphasis'] = $normalized['apply_theme_emphasis'] ?? true;
            }
        } elseif(is_scalar($value)) {
            $normalized['reason'] = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($value, 220));
        }

        return $normalized;
    }

    private function normalize_app_review_block_patch_pack($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach($value as $item) {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(!is_array($item)) {
                continue;
            }

            $settings = [];

            foreach((array) ($item['settings'] ?? $item['patch'] ?? []) as $setting_key => $setting_value) {
                if(!is_scalar($setting_value)) {
                    continue;
                }

                $normalized_key = preg_replace('/[^a-z0-9_]+/i', '_', (string) $setting_key) ?? '';
                $normalized_key = trim($normalized_key, '_');

                if($normalized_key === '') {
                    continue;
                }

                $settings[$normalized_key] = str_contains($normalized_key, 'color') || str_contains($normalized_key, 'shadow')
                    ? ($this->normalize_ai_css_color_value($setting_value, str_contains($normalized_key, 'shadow')) ?: $this->sanitize_ai_string($setting_value, 120))
                    : $this->sanitize_ai_string($setting_value, 240);
            }

            if(empty($settings)) {
                continue;
            }

            $normalized[] = [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'block_type' => $this->sanitize_ai_string($item['block_type'] ?? $item['type'] ?? '', 64),
                'reason' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($item['reason'] ?? $item['why'] ?? '', 180)),
                'settings' => $settings,
            ];

            if(count($normalized) >= 6) {
                break;
            }
        }

        return $normalized;
    }

    private function normalize_app_review_copy_suggestions($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [];
        }

        $allowed_fields = ['name', 'title', 'button_text', 'thank_you_button_text', 'description', 'text', 'message', 'popup_title', 'popup_subtitle', 'thank_you_title', 'thank_you_text'];
        $allowed_case_styles = ['sentence', 'title', 'upper', 'lower', 'brand'];
        $normalized = [];

        foreach($value as $item) {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(is_scalar($item)) {
                $item = [
                    'field' => 'name',
                    'label' => 'AI prijedlog',
                    'value' => (string) $item,
                ];
            }

            if(!is_array($item)) {
                continue;
            }

            $field = (string) ($item['field'] ?? $item['target_field'] ?? $item['target'] ?? 'name');

            if(!in_array($field, $allowed_fields, true)) {
                $field = 'name';
            }

            $value_text = $this->normalize_app_review_visible_copy($this->sanitize_ai_string($item['value'] ?? $item['text'] ?? '', 180));

            if($value_text === '') {
                continue;
            }

            $case_style = (string) ($item['case_style'] ?? $item['text_case'] ?? 'sentence');

            $normalized[] = [
                'block_id' => (int) ($item['block_id'] ?? 0),
                'block_type' => $this->sanitize_ai_string($item['block_type'] ?? $item['type'] ?? '', 64),
                'role_key' => $this->sanitize_ai_string($item['role_key'] ?? $item['semantic_role'] ?? '', 64),
                'field' => $field,
                'label' => $this->normalize_app_review_visible_copy($this->sanitize_ai_string($item['label'] ?? $item['title'] ?? '', 120)),
                'value' => $value_text,
                'reason' => $this->normalize_app_review_channel_copy($this->sanitize_ai_string($item['reason'] ?? $item['why'] ?? '', 180)),
                'case_style' => in_array($case_style, $allowed_case_styles, true) ? $case_style : 'sentence',
            ];

            if(count($normalized) >= 8) {
                break;
            }
        }

        return $normalized;
    }

    private function normalize_app_review_layout_actions($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [];
        }

        $allowed_actions = ['move_up', 'move_down', 'keep_top', 'keep_after_primary', 'consider_remove', 'hide_for_now', 'add_block', 'swap_order', 'keep'];
        $normalized = [];

        foreach($value as $item) {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(!is_array($item)) {
                continue;
            }

            $action = (string) ($item['action'] ?? '');
            $why = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($item['why'] ?? $item['reason'] ?? '', 180));

            if(!in_array($action, $allowed_actions, true) || $why === '') {
                continue;
            }

            $normalized[] = [
                'action' => $action,
                'block_id' => (int) ($item['block_id'] ?? 0),
                'block_type' => $this->sanitize_ai_string($item['block_type'] ?? $item['type'] ?? '', 64),
                'label' => $this->normalize_app_review_visible_copy($this->sanitize_ai_string($item['label'] ?? $item['name'] ?? '', 120)),
                'why' => $why,
            ];

            if(count($normalized) >= 8) {
                break;
            }
        }

        return $normalized;
    }

    private function normalize_app_review_missing_block_seed_settings($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [];
        }

        $allowed_keys = [
            'name',
            'title',
            'text',
            'message',
            'button_text',
            'description',
            'popup_title',
            'popup_subtitle',
            'thank_you_title',
            'thank_you_text',
            'thank_you_button_text',
            'open_mode',
            'location_url',
            'product_translation_key',
            'product_language_mode',
            'product_language_code',
            'product_fallback_language_code',
            'product_image_url',
        ];
        $normalized = [];

        foreach($allowed_keys as $key) {
            if(!array_key_exists($key, $value) || !is_scalar($value[$key])) {
                continue;
            }

            $clean_value = in_array($key, ['location_url', 'product_translation_key', 'product_language_mode', 'product_language_code', 'product_fallback_language_code', 'product_image_url'], true)
                ? trim((string) $value[$key])
                : $this->normalize_app_review_visible_copy($this->sanitize_ai_string($value[$key], 500));

            if($clean_value === '') {
                continue;
            }

            $normalized[$key] = $clean_value;
        }

        if(array_key_exists('product_blog_post_id', $value)) {
            $product_blog_post_id = (int) ($value['product_blog_post_id'] ?? 0);

            if($product_blog_post_id > 0) {
                $normalized['product_blog_post_id'] = $product_blog_post_id;
            }
        }

        if(array_key_exists('apply_to_all_products', $value)) {
            $normalized['apply_to_all_products'] = (int) !empty($value['apply_to_all_products']);
        }

        return $normalized;
    }

    private function normalize_app_review_missing_block_recommendations($value): array {
        if($value instanceof \stdClass) {
            $value = (array) $value;
        }

        if(!is_array($value)) {
            return [];
        }

        $normalized = [];

        foreach($value as $index => $item) {
            if($item instanceof \stdClass) {
                $item = (array) $item;
            }

            if(!is_array($item)) {
                continue;
            }

            $block_type = $this->sanitize_ai_string($item['block_type'] ?? $item['type'] ?? '', 64);
            $why = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($item['why'] ?? $item['reason'] ?? '', 180));

            if($block_type === '' || $why === '') {
                continue;
            }

            $normalized[] = [
                'recommendation_key' => $this->sanitize_ai_string($item['recommendation_key'] ?? '', 64),
                'block_type' => $block_type,
                'role_key' => $this->sanitize_ai_string($item['role_key'] ?? $item['semantic_role'] ?? '', 64),
                'label' => $this->normalize_app_review_visible_copy($this->sanitize_ai_string($item['label'] ?? $item['name'] ?? '', 120)),
                'why' => $why,
                'priority' => max(1, min(9, (int) ($item['priority'] ?? ($index + 1)))),
                'insert_after_block_id' => max(0, (int) ($item['insert_after_block_id'] ?? 0)),
                'insert_after_type' => $this->sanitize_ai_string($item['insert_after_type'] ?? '', 64),
                'insert_after_label' => $this->normalize_app_review_visible_copy($this->sanitize_ai_string($item['insert_after_label'] ?? '', 120)),
                'allow_existing_type' => !empty($item['allow_existing_type']),
                'preferred_group' => $this->sanitize_ai_string($item['preferred_group'] ?? '', 32),
                'preferred_goal' => $this->sanitize_ai_string($item['preferred_goal'] ?? '', 32),
                'picker_search' => $this->normalize_app_review_visible_copy($this->sanitize_ai_string($item['picker_search'] ?? '', 120)),
                'seed_settings' => $this->normalize_app_review_missing_block_seed_settings($item['seed_settings'] ?? []),
            ];

            if(count($normalized) >= 6) {
                break;
            }
        }

        return $normalized;
    }

    private function build_app_review_final_block_plan(array $block_attribution_payload, array $layout_actions = [], array $ideal_block_order = [], array $missing_block_recommendations = []): array {
        $block_attribution_payload = $this->normalize_app_review_block_attribution_payload($block_attribution_payload);
        $layout_actions = $this->enforce_app_review_signal_safe_layout_actions(
            $this->normalize_app_review_layout_actions($layout_actions),
            $block_attribution_payload
        );
        $ideal_block_order = $this->normalize_app_review_visible_list($ideal_block_order);
        $missing_block_recommendations = $this->normalize_app_review_missing_block_recommendations($missing_block_recommendations);

        $all_blocks = array_values(array_filter((array) ($block_attribution_payload['all_blocks'] ?? []), 'is_array'));

        if(empty($all_blocks) && empty($missing_block_recommendations)) {
            return [];
        }

        usort($all_blocks, static function(array $a, array $b): int {
            return ((int) ($a['position'] ?? 0) <=> (int) ($b['position'] ?? 0))
                ?: ((int) ($a['block_id'] ?? 0) <=> (int) ($b['block_id'] ?? 0));
        });

        $action_map = [];
        foreach($layout_actions as $action) {
            $block_id = (int) ($action['block_id'] ?? 0);

            if($block_id <= 0 || isset($action_map[$block_id])) {
                continue;
            }

            $action_map[$block_id] = [
                'action' => (string) ($action['action'] ?? ''),
                'why' => (string) ($action['why'] ?? ''),
            ];
        }

        $find_matching_block = function(string $item, array $available_blocks): array {
            $item = trim($item);

            if($item === '' || empty($available_blocks)) {
                return [];
            }

            $item_key = $this->normalize_app_review_matching_key($item);
            $word_count = count(array_filter(preg_split('/\s+/u', $item) ?: []));
            $looks_like_name = $word_count >= 2
                && $word_count <= 4
                && !$this->app_review_text_has_any($item, ['avatar', 'fotografija', 'video', 'whatsapp', 'shop', 'webshop', 'proizvod', 'prijava', 'funnel', 'suradnja']);

            $match_first = static function(array $blocks, callable $predicate): array {
                foreach($blocks as $block) {
                    if($predicate($block)) {
                        return $block;
                    }
                }

                return [];
            };

            if($this->app_review_text_has_any($item, ['avatar', 'profilna', 'fotografija', 'fotka', 'slika'])) {
                return $match_first($available_blocks, static fn(array $block): bool => in_array((string) ($block['type'] ?? ''), ['avatar', 'image', 'header'], true));
            }

            if($this->app_review_text_has_any($item, ['ime i prezime', 'puno ime', 'prezime']) || $looks_like_name) {
                $match = $match_first($available_blocks, fn(array $block): bool =>
                    in_array((string) ($block['type'] ?? ''), ['heading', 'paragraph'], true)
                    && (
                        (string) ($block['role'] ?? '') === 'trust_content'
                        || count(array_filter(preg_split('/\s+/u', (string) ($block['label'] ?? '')) ?: [])) >= 2
                    )
                );

                if(!empty($match)) {
                    return $match;
                }
            }

            if($this->app_review_text_has_any($item, ['trust', 'povjerenje', 'uvod', 'kratka poruka', 'kratki naslov', 'odlomak'])) {
                $match = $match_first($available_blocks, static fn(array $block): bool =>
                    (string) ($block['role'] ?? '') === 'trust_content'
                    || in_array((string) ($block['type'] ?? ''), ['paragraph', 'markdown', 'heading'], true)
                );

                if(!empty($match)) {
                    return $match;
                }
            }

            if($this->app_review_text_has_any($item, ['start paket', 'start-paket', 'partner', 'suradnik', 'postani forever', 'upis', 'registracija'])) {
                $match = $match_first($available_blocks, fn(array $block): bool =>
                    in_array((string) ($block['type'] ?? ''), ['link_forever_product', 'link'], true)
                    && $this->app_review_text_has_any((string) ($block['label'] ?? ''), ['start paket', 'start-paket', 'partner', 'suradnik', 'upis', 'registracija'])
                );

                if(!empty($match)) {
                    return $match;
                }
            }

            if($this->app_review_text_has_any($item, ['video', 'vimeo', 'youtube'])) {
                return $match_first($available_blocks, static fn(array $block): bool =>
                    in_array((string) ($block['type'] ?? ''), ['video', 'youtube', 'vimeo'], true)
                    || (string) ($block['role'] ?? '') === 'video'
                );
            }

            if($this->app_review_text_has_any($item, ['prijava', 'funnel', 'formular', 'obrazac', 'suradnja'])) {
                $match = $match_first($available_blocks, static fn(array $block): bool =>
                    (string) ($block['role'] ?? '') === 'lead_capture'
                    || (string) ($block['type'] ?? '') === 'lead_funnel'
                );

                if(!empty($match)) {
                    return $match;
                }
            }

            if($this->app_review_text_has_any($item, ['whatsapp'])) {
                $match = $match_first($available_blocks, static fn(array $block): bool =>
                    (string) ($block['role'] ?? '') === 'whatsapp'
                    || (string) ($block['type'] ?? '') === 'custom_html_whatsapp'
                );

                if(!empty($match)) {
                    return $match;
                }
            }

            if($this->app_review_text_has_any($item, ['društvene', 'drustvene', 'mreže', 'mreze', 'social', 'kontakti'])) {
                $match = $match_first($available_blocks, static fn(array $block): bool =>
                    (string) ($block['role'] ?? '') === 'social_contact'
                    || (string) ($block['type'] ?? '') === 'socials'
                );

                if(!empty($match)) {
                    return $match;
                }
            }

            if($this->app_review_text_has_any($item, ['webshop', 'web shop', 'shop', 'popust', 'forever webshop'])) {
                $match = $match_first($available_blocks, static fn(array $block): bool =>
                    (string) ($block['role'] ?? '') === 'shop'
                    || in_array((string) ($block['type'] ?? ''), ['link_discount', 'link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'], true)
                );

                if(!empty($match)) {
                    return $match;
                }
            }

            if($this->app_review_text_has_any($item, ['proizvod', 'proizvodi'])) {
                $match = $match_first($available_blocks, static fn(array $block): bool =>
                    (string) ($block['role'] ?? '') === 'product'
                    || (string) ($block['type'] ?? '') === 'link_forever_product'
                );

                if(!empty($match)) {
                    return $match;
                }
            }

            return $match_first($available_blocks, fn(array $block): bool => $item_key !== '' && $this->normalize_app_review_matching_key((string) ($block['label'] ?? '')) !== '' && (
                $this->normalize_app_review_matching_key((string) ($block['label'] ?? '')) === $item_key
                || str_contains($this->normalize_app_review_matching_key((string) ($block['label'] ?? '')), $item_key)
                || str_contains($item_key, $this->normalize_app_review_matching_key((string) ($block['label'] ?? '')))
            ));
        };

        $ideal_sequence_ids = [];
        $used_ids = [];

        foreach($ideal_block_order as $item) {
            $available_blocks = array_values(array_filter($all_blocks, static function(array $block) use ($used_ids): bool {
                return !in_array((int) ($block['block_id'] ?? 0), $used_ids, true);
            }));
            $matched_block = $find_matching_block((string) $item, $available_blocks);
            $matched_block_id = (int) ($matched_block['block_id'] ?? 0);

            if($matched_block_id <= 0) {
                continue;
            }

            $ideal_sequence_ids[] = $matched_block_id;
            $used_ids[] = $matched_block_id;
        }

        $hidden_ids = [];
        foreach($all_blocks as $block) {
            $block_id = (int) ($block['block_id'] ?? 0);
            $status = (string) ($block['status'] ?? '');
            $planned_action = (string) (($action_map[$block_id]['action'] ?? ''));

            if(
                $block_id > 0
                && in_array($planned_action, ['hide_for_now', 'consider_remove'], true)
                && !in_array($block_id, $ideal_sequence_ids, true)
                && !in_array($status, ['high_signal', 'contributing', 'supporting'], true)
            ) {
                $hidden_ids[$block_id] = true;
            }
        }

        $included_ids = [];
        foreach($ideal_sequence_ids as $block_id) {
            $included_ids[$block_id] = true;
        }

        foreach($all_blocks as $block) {
            $block_id = (int) ($block['block_id'] ?? 0);
            $status = (string) ($block['status'] ?? '');

            if($block_id <= 0 || isset($included_ids[$block_id]) || isset($hidden_ids[$block_id])) {
                continue;
            }

            if(in_array($status, ['high_signal', 'contributing', 'supporting'], true)) {
                $included_ids[$block_id] = true;
            }
        }

        foreach($all_blocks as $block) {
            $block_id = (int) ($block['block_id'] ?? 0);

            if($block_id <= 0 || isset($included_ids[$block_id]) || isset($hidden_ids[$block_id])) {
                continue;
            }

            $included_ids[$block_id] = true;
        }

        $hidden_plan_rows = [];
        $ordered_plan_rows = [];

        $build_existing_plan_row = function(array $block, bool $include_on_app, bool $is_ideal_anchor = false) use ($action_map): array {
            $block_id = (int) ($block['block_id'] ?? 0);
            $status = (string) ($block['status'] ?? '');
            $planned_action = (string) (($action_map[$block_id]['action'] ?? ''));
            $planned_why = trim((string) (($action_map[$block_id]['why'] ?? '')));

            if($include_on_app) {
                if(in_array($planned_action, ['hide_for_now', 'consider_remove'], true) || $planned_action === '') {
                    $planned_action = in_array($status, ['high_signal', 'contributing'], true)
                        ? 'keep'
                        : ($status === 'supporting' ? 'keep_after_primary' : 'keep');
                }
            } elseif(!in_array($planned_action, ['hide_for_now', 'consider_remove'], true)) {
                $planned_action = 'hide_for_now';
            }

            $reason = $planned_why;
            if($reason === '') {
                if($is_ideal_anchor && $include_on_app) {
                    $reason = 'AI ga izričito zadržava u konačnom rasporedu jer i dalje ima jasnu ulogu u putu korisnika.';
                } elseif(!$include_on_app) {
                    $reason = (string) ($block['reason'] ?? 'Ovaj blok se privremeno miče iz fokusa kako bi narednih 7 dana glavni put bio čišći i mjerljiviji.');
                } elseif($status === 'supporting') {
                    $reason = 'Ovaj blok ostaje kao trust ili prijelazni korak iako nije glavni klik-magnet.';
                } else {
                    $reason = (string) ($block['reason'] ?? 'Ovaj blok ostaje na aplikaciji jer i dalje ima ulogu u rezultatu, povjerenju ili prijelazu prema glavnom koraku.');
                }
            }

            return [
                'block_id' => $block_id,
                'block_type' => (string) ($block['type'] ?? ''),
                'label' => $this->normalize_app_review_visible_copy((string) (($block['label'] ?? '') ?: ($block['type'] ?? 'Blok'))),
                'source' => 'existing',
                'status' => $status,
                'planned_action' => $planned_action,
                'reason' => $this->normalize_app_review_channel_copy($reason),
                'include_on_app' => $include_on_app,
                'position' => max(0, (int) ($block['position'] ?? 0)),
            ];
        };

        $append_missing_rows_for_anchor = function(int $anchor_block_id) use (&$ordered_plan_rows, &$missing_block_recommendations) {
            $matched = [];
            $remaining = [];

            foreach($missing_block_recommendations as $item) {
                $insert_after_block_id = (int) ($item['insert_after_block_id'] ?? 0);

                if($anchor_block_id > 0 && $insert_after_block_id === $anchor_block_id) {
                    $matched[] = $item;
                } else {
                    $remaining[] = $item;
                }
            }

            $missing_block_recommendations = $remaining;

            foreach($matched as $item) {
                $ordered_plan_rows[] = [
                    'block_id' => 0,
                    'block_type' => (string) ($item['block_type'] ?? ''),
                    'label' => $this->normalize_app_review_visible_copy((string) (($item['label'] ?? '') ?: ($item['block_type'] ?? 'Blok'))),
                    'source' => 'missing',
                    'status' => 'missing_recommended',
                    'planned_action' => 'add',
                    'reason' => $this->normalize_app_review_channel_copy((string) ($item['why'] ?? '')),
                    'include_on_app' => true,
                    'position' => 0,
                    'insert_after_block_id' => $insert_after_block_id,
                    'insert_after_type' => (string) ($item['insert_after_type'] ?? ''),
                    'insert_after_label' => (string) ($item['insert_after_label'] ?? ''),
                ];
            }
        };

        foreach($ideal_sequence_ids as $block_id) {
            foreach($all_blocks as $block) {
                if((int) ($block['block_id'] ?? 0) !== $block_id) {
                    continue;
                }

                $ordered_plan_rows[] = $build_existing_plan_row($block, true, true);
                $append_missing_rows_for_anchor($block_id);
                break;
            }
        }

        foreach($all_blocks as $block) {
            $block_id = (int) ($block['block_id'] ?? 0);

            if($block_id <= 0 || in_array($block_id, $ideal_sequence_ids, true) || isset($hidden_ids[$block_id]) || !isset($included_ids[$block_id])) {
                continue;
            }

            $ordered_plan_rows[] = $build_existing_plan_row($block, true, false);
            $append_missing_rows_for_anchor($block_id);
        }

        foreach($missing_block_recommendations as $item) {
            $ordered_plan_rows[] = [
                'block_id' => 0,
                'block_type' => (string) ($item['block_type'] ?? ''),
                'label' => $this->normalize_app_review_visible_copy((string) (($item['label'] ?? '') ?: ($item['block_type'] ?? 'Blok'))),
                'source' => 'missing',
                'status' => 'missing_recommended',
                'planned_action' => 'add',
                'reason' => $this->normalize_app_review_channel_copy((string) ($item['why'] ?? '')),
                'include_on_app' => true,
                'position' => 0,
                'insert_after_block_id' => (int) ($item['insert_after_block_id'] ?? 0),
                'insert_after_type' => (string) ($item['insert_after_type'] ?? ''),
                'insert_after_label' => (string) ($item['insert_after_label'] ?? ''),
            ];
        }

        foreach($all_blocks as $block) {
            $block_id = (int) ($block['block_id'] ?? 0);

            if($block_id <= 0 || !isset($hidden_ids[$block_id])) {
                continue;
            }

            $hidden_plan_rows[] = $build_existing_plan_row($block, false, false);
        }

        $final_plan = array_values(array_filter(array_merge($ordered_plan_rows, $hidden_plan_rows), static function(array $item): bool {
            return trim((string) ($item['label'] ?? '')) !== '';
        }));

        foreach($final_plan as $index => &$item) {
            $item['display_order'] = $index + 1;
        }
        unset($item);

        return array_slice($final_plan, 0, 24);
    }
    /* /Custom code: FC-2026-03-31 */

    private function get_goal_type(array $values): string {
        $primary_goal = (string) ($values['primary_goal'] ?? '');
        $priority_offer = (string) ($values['priority_offer'] ?? '');

        if($primary_goal === 'recruitment' || $priority_offer === 'business_opportunity') {
            return 'business';
        }

        if($primary_goal === 'brand_building' || $priority_offer === 'personal_brand') {
            return 'brand';
        }

        if($primary_goal === 'product_sales' && in_array($priority_offer, ['single_product', 'product_category'], true)) {
            return 'shop';
        }

        if($primary_goal === 'customer_activation') {
            return 'activation';
        }

        return 'hybrid';
    }

    private function get_effective_app_review_goal_type(array $values, string $request_context = '', ?array $selected_app = null): string {
        $base_goal_type = $this->get_goal_type($values);

        if($base_goal_type === 'shop') {
            return 'shop';
        }

        $product_sales_score = 0;
        $context_fragments = array_filter([
            (string) ($values['product_focus'] ?? ''),
            (string) ($values['notes'] ?? ''),
            (string) $request_context,
            (string) ($values['priority_offer'] ?? ''),
            (string) ($values['primary_goal'] ?? ''),
        ]);
        $product_sales_context = implode(' ', $context_fragments);

        if((string) ($values['primary_goal'] ?? '') === 'product_sales') {
            $product_sales_score += 4;
        }

        if(in_array((string) ($values['priority_offer'] ?? ''), ['single_product', 'product_category'], true)) {
            $product_sales_score += 3;
        }

        if(trim((string) ($values['product_focus'] ?? '')) !== '') {
            $product_sales_score += 1;
        }

        if($this->app_review_text_has_any($product_sales_context, [
            'prodaj', 'prodaja', 'proizvod', 'proizvodi', 'webshop', 'web shop', 'shop',
            'popust', 'kup', 'kupnja', 'preporuk', 'aloe', 'gel', 'c9', 'detox',
        ])) {
            $product_sales_score += 2;
        }

        if(!empty($selected_app)) {
            if((int) ($selected_app['forever_blocks'] ?? 0) > 0) {
                $product_sales_score += 1;
            }

            if(!empty($selected_app['conversion_capabilities']['has_shop_links'])) {
                $product_sales_score += 1;
            }
        }

        if($product_sales_score >= 3 && in_array($base_goal_type, ['business', 'hybrid'], true)) {
            return 'shop';
        }

        return $base_goal_type;
    }

    private function get_fcc_start_paket_public_url(): string {
        return rtrim(SITE_URL, '/') . '/blog/start-paket';
    }

    private function get_fcc_start_paket_seed_settings(): array {
        return [
            'name' => 'Postani Forever suradnik',
            'description' => 'Pogledaj kako izgleda start paket i koji je najbolji sljedeći korak za suradnju.',
            'location_url' => $this->get_fcc_start_paket_public_url(),
            'product_translation_key' => 'start-paket',
            'product_language_mode' => 'app',
            'product_fallback_language_code' => 'hr',
        ];
    }

    private function get_fcc_core_block_policy(array $values, string $goal_type = '', string $request_context = ''): array {
        $goal_type = $goal_type !== '' ? $goal_type : $this->get_effective_app_review_goal_type($values, $request_context);

        return [
            'goal_type' => $goal_type,
            'require_discount_offer' => true,
            'require_business_start_paket_offer' => true,
            'require_funnel' => in_array($goal_type, ['shop', 'business', 'hybrid', 'activation'], true),
            'require_whatsapp_backup' => in_array($goal_type, ['shop', 'business', 'hybrid', 'activation'], true),
            'discount_block_type' => 'link_discount',
            'discount_block_label' => 'Pogledaj proizvode s popustom',
            'business_offer_block_type' => 'link_forever_product',
            'business_offer_block_label' => 'Postani Forever suradnik',
            'business_offer_translation_key' => 'start-paket',
            'business_offer_url' => $this->get_fcc_start_paket_public_url(),
            'funnel_preferred_primary' => in_array($goal_type, ['shop', 'business', 'hybrid'], true),
            'funnel_label_shop' => 'Zatraži preporuku i sljedeći korak',
            'funnel_label_business' => 'Prijavi se i saznaj više',
        ];
    }

    private function is_contact_collection_goal(array $values, string $goal_type = ''): bool {
        $goal_type = $goal_type !== '' ? $goal_type : $this->get_goal_type($values);
        $primary_goal = (string) ($values['primary_goal'] ?? '');
        $biggest_blocker = (string) ($values['biggest_blocker'] ?? '');

        if($goal_type === 'business') {
            return true;
        }

        if(in_array($primary_goal, ['recruitment'], true)) {
            return true;
        }

        if(in_array($biggest_blocker, ['no_leads', 'follow_up_unclear'], true)) {
            return true;
        }

        return false;
    }

    private function get_fcc_goal_system_payload(array $values, string $goal_type = ''): array {
        $goal_type = $goal_type !== '' ? $goal_type : $this->get_goal_type($values);
        $primary_goal = (string) ($values['primary_goal'] ?? '');
        $communication_style = (string) ($values['communication_style'] ?? '');
        $priority_offer = (string) ($values['priority_offer'] ?? '');
        $visual_tone_preference = trim((string) ($values['visual_tone_preference'] ?? ''));

        $brand_tone = match($communication_style) {
            'personal_story' => 'topao, osoban i siguran',
            'direct_sales' => 'izravan, jednostavan i čist',
            'educational' => 'stručan, uredan i ozbiljan',
            'testimonial' => 'priča, emocija i povjerenje',
            'soft_brand' => 'premium, smiren i elegantan',
            'recruitment_focus' => 'jasan, ozbiljan i usmjeren na razgovor',
            default => 'jasan, smiren i lako razumljiv',
        };

        $recommended_primary_system = match($goal_type) {
            'business' => 'Funnel + WhatsApp + edukativna thank you stranica',
            'shop' => 'glavni proizvod ili kategorija + povjerenje + jedan jasan sljedeći korak',
            'activation' => 'WhatsApp + Funnel + edukativni sadržaj',
            'brand' => 'povjerenje + video + jedan glavni korak',
            default => 'jedan glavni korak + povjerenje + sekundarni sadržaj',
        };

        $design_direction = match($goal_type) {
            'business' => [
                'mood' => 'ozbiljno, smireno i vjerodostojno',
                'background_role' => 'mirna baza koja drži prvi ekran urednim',
                'primary_role' => 'glavni blok mora biti najjasniji naglasak bez agresije',
                'secondary_role' => 'sekundarni blokovi trebaju ostati povučeni i uredni',
                'accent_energy' => 'umjerena',
                'why' => 'povjerenje za suradnju raste kad aplikacija djeluje čisto, jasno i ozbiljno',
            ],
            'shop' => [
                'mood' => 'svježe, sigurno i uvjerljivo bez sterilnog dojma',
                'background_role' => 'baza treba djelovati uredno i lagano tonirano, ne prazno ni sterilno',
                'primary_role' => 'glavni blok treba jasno voditi prema proizvodu ili kupnji',
                'secondary_role' => 'sekundarni blokovi trebaju podržati odluku bez dodatne buke',
                'accent_energy' => 'umjerena do nešto svježija',
                'why' => 'sigurnost i jasnoća pojačavaju osjećaj povjerenja kod proizvoda',
            ],
            'brand' => [
                'mood' => 'premium, autoritativno i dosljedno',
                'background_role' => 'baza treba djelovati profinjeno i stabilno',
                'primary_role' => 'glavni blok treba djelovati vrijedno i sigurno, ne napadno',
                'secondary_role' => 'ostali blokovi moraju držati isti premium ritam bez novih naglasaka',
                'accent_energy' => 'suptilna do umjerena',
                'why' => 'osobni brend djeluje snažnije kad vizual ostane dosljedan i profinjen',
            ],
            'activation' => [
                'mood' => 'toplo, pristupačno i brzo razumljivo',
                'background_role' => 'baza treba smiriti i olakšati prvi kontakt',
                'primary_role' => 'glavni blok mora jasno potaknuti poruku ili aktivaciju',
                'secondary_role' => 'sekundarni blokovi trebaju biti podrška, ne novi fokus',
                'accent_energy' => 'umjerena do toplija',
                'why' => 'ljudi se lakše aktiviraju kad aplikacija djeluje toplo i jednostavno',
            ],
            default => [
                'mood' => 'jasno, smireno i lako razumljivo',
                'background_role' => 'pozadina mora ostati čista i nenametljiva',
                'primary_role' => 'glavni blok treba odmah reći koji je sljedeći korak',
                'secondary_role' => 'sekundarni blokovi moraju biti mirni i podržavati fokus',
                'accent_energy' => 'umjerena',
                'why' => 'fokus i povjerenje rastu kad aplikacija djeluje uredno i bez viška naglasaka',
            ],
        };

        $design_direction['user_preferred_visual_tone'] = $visual_tone_preference;
        $design_direction['palette_freedom'] = $visual_tone_preference !== ''
            ? 'Poštuj opisani ton ako i dalje pomaže cilju, fokusu i čitljivosti.'
            : 'AI sam bira najučinkovitiju paletu za cilj, publiku i ponudu.';
        $design_direction['guardrails'] = [
            'glavni blok mora biti vizualno najjači, ali ne neon',
            'sekundarni blokovi moraju biti mirniji od glavnog',
            'izbjegavaj fluorescentne i kanarinac tonove',
            'najviše jedna jaka naglašena boja uz mirnu bazu',
            'ako korisnik nije izričito tražio clean ili minimal stil, izbjegavaj ravnu bijelu ili gotovo potpuno bijelu pozadinu za cijelu shop aplikaciju',
            'vizualni smjer mora se mijenjati prema tonu, ponudi i stilu komunikacije; ne vraćaj istu blijedu paletu po navici',
        ];

        $preferred_trust_elements = [
            'osobna fotografija koja djeluje stvarno i toplo',
            'jedna kratka rečenica kome aplikacija pomaže',
            'jedan glavni gumb na vrhu',
            'kratki video koji objašnjava što osoba dobiva',
        ];

        if(in_array($primary_goal, ['recruitment', 'customer_activation'], true) || $goal_type === 'business') {
            $preferred_trust_elements[] = 'Funnel koji skuplja kontakt i vodi prema thank you stranici';
        }

        $funnel_blueprint = match($goal_type) {
            'business' => [
                'kratki video na početku Funnel-a koji objašnjava kome je prilika namijenjena',
                'polja: ime, email i broj telefona',
                'thank you stranica s edukacijom ili drugom FCC aplikacijom za suradnju',
                'mogući poklon: PDF vodič, video objašnjenje ili mini edukacija',
            ],
            'shop' => [
                'kratki video ili tekst koji objašnjava kome je proizvod namijenjen',
                'polja: ime, email i broj telefona',
                'thank you stranica s preporukom proizvoda ili edukativnom FCC aplikacijom',
                'mogući poklon: PDF vodič, mini plan, recepti ili plan korištenja proizvoda',
            ],
            default => [
                'kratki video koji smiruje i gradi povjerenje prije prijave',
                'polja: ime, email i broj telefona',
                'thank you stranica s korisnim sljedećim korakom',
                'mogući poklon: PDF vodič, video ili edukativni sadržaj',
            ],
        };

        return [
            'goal_type' => $goal_type,
            'brand_tone' => $brand_tone,
            'recommended_primary_system' => $recommended_primary_system,
            'preferred_trust_elements' => $preferred_trust_elements,
            'funnel_blueprint' => $funnel_blueprint,
            'design_direction' => $design_direction,
            'forbidden_recommendations' => [
                'nemoj predlagati Save Contact, Contact Collector ni Email Collector kao glavno rješenje ako Funnel može bolje odraditi isti cilj',
                'nemoj predlagati Dodaj na početni zaslon',
                'nemoj davati savjete o veličini gumba jer su gumbi unutar sustava već standardizirani',
            ],
            'fcc_blocks_that_help' => [
                'avatar i naslov',
                'tekst blok za jasno objašnjenje kome pomažeš i što osoba dobiva',
                'video',
                'Funnel',
                'WhatsApp blok ili jasan WhatsApp gumb',
                'AI savjetnik / chatbot kao neutralan pomoćni sloj',
                'Forever prodajni blokovi',
                'blog proizvodi',
            ],
            'priority_offer_hint' => $priority_offer,
        ];
    }

    private function get_goal_first_fallback_theme_seed(array $values, string $goal_type = ''): array {
        $goal_type = $goal_type !== '' ? $goal_type : $this->get_goal_type($values);
        $communication_style = (string) ($values['communication_style'] ?? '');
        $priority_offer = (string) ($values['priority_offer'] ?? '');
        $visual_tone_preference = mb_strtolower(trim((string) ($values['visual_tone_preference'] ?? '')));

        $palette_key = match(true) {
            $visual_tone_preference !== '' && preg_match('/premium|elegan|luksuz|autoritet|profinj/u', $visual_tone_preference) => 'premium_graphite_gold',
            $visual_tone_preference !== '' && preg_match('/topl|prijatelj|osoban|human|mek|njez|zemlj/u', $visual_tone_preference) => 'warm_forest_sand',
            $visual_tone_preference !== '' && preg_match('/cist|čist|moder|minimal|ured|clean/u', $visual_tone_preference) => $goal_type === 'shop' ? 'sage_mist_graphite' : 'trust_slate_blue',
            $goal_type === 'shop' && in_array($communication_style, ['personal_story', 'testimonial'], true) => 'warm_forest_sand',
            $goal_type === 'shop' && ($communication_style === 'direct_sales' || $priority_offer === 'single_product') => 'friendly_emerald_ink',
            $goal_type === 'shop' && in_array($communication_style, ['educational', 'soft_brand'], true) => 'sage_mist_graphite',
            $goal_type === 'shop' && in_array($priority_offer, ['product_category', 'mixed_offer'], true) => 'sage_mist_graphite',
            $goal_type === 'shop' => 'friendly_emerald_ink',
            $goal_type === 'activation' => 'friendly_emerald_ink',
            $goal_type === 'brand' || $communication_style === 'soft_brand' => 'premium_graphite_gold',
            in_array($communication_style, ['personal_story', 'testimonial'], true) => 'warm_forest_sand',
            default => 'trust_slate_blue',
        };

        $palettes = [
            'trust_slate_blue' => [
                'background_mode' => 'gradient',
                'background_color' => '#0F172A',
                'gradient_start' => '#0F172A',
                'gradient_end' => '#111827',
                'heading_color' => '#F8FAFC',
                'text_color' => '#CBD5E1',
                'primary_block_text' => '#FFFFFF',
                'primary_block_background' => '#2563EB',
                'primary_block_border' => '#1D4ED8',
                'primary_block_shadow' => 'rgba(37,99,235,.24)',
                'secondary_blocks_text' => '#F8FAFC',
                'secondary_blocks_background' => '#111827',
                'secondary_blocks_border' => '#334155',
            ],
            'clean_green_slate' => [
                'background_mode' => 'gradient',
                'background_color' => '#E9F2EC',
                'gradient_start' => '#E9F2EC',
                'gradient_end' => '#DCE7E0',
                'heading_color' => '#10261E',
                'text_color' => '#334155',
                'primary_block_text' => '#FFFFFF',
                'primary_block_background' => '#15803D',
                'primary_block_border' => '#166534',
                'primary_block_shadow' => 'rgba(21,128,61,.18)',
                'secondary_blocks_text' => '#10261E',
                'secondary_blocks_background' => '#F4F7F3',
                'secondary_blocks_border' => '#C1D0C5',
            ],
            'premium_graphite_gold' => [
                'background_mode' => 'gradient',
                'background_color' => '#0B1120',
                'gradient_start' => '#0B1120',
                'gradient_end' => '#111827',
                'heading_color' => '#F8FAFC',
                'text_color' => '#CBD5E1',
                'primary_block_text' => '#111827',
                'primary_block_background' => '#B88A2D',
                'primary_block_border' => '#9A741E',
                'primary_block_shadow' => 'rgba(184,138,45,.20)',
                'secondary_blocks_text' => '#F8FAFC',
                'secondary_blocks_background' => '#111827',
                'secondary_blocks_border' => '#374151',
            ],
            'warm_forest_sand' => [
                'background_mode' => 'gradient',
                'background_color' => '#F5EEE3',
                'gradient_start' => '#F5EEE3',
                'gradient_end' => '#EADFCC',
                'heading_color' => '#1F2937',
                'text_color' => '#475569',
                'primary_block_text' => '#FFFFFF',
                'primary_block_background' => '#2F6B5F',
                'primary_block_border' => '#285C52',
                'primary_block_shadow' => 'rgba(47,107,95,.18)',
                'secondary_blocks_text' => '#1F2937',
                'secondary_blocks_background' => '#FBF6EE',
                'secondary_blocks_border' => '#D6C7B2',
            ],
            'sage_mist_graphite' => [
                'background_mode' => 'gradient',
                'background_color' => '#E3EFE8',
                'gradient_start' => '#E3EFE8',
                'gradient_end' => '#D6E3DE',
                'heading_color' => '#10241F',
                'text_color' => '#334155',
                'primary_block_text' => '#FFFFFF',
                'primary_block_background' => '#1D7A65',
                'primary_block_border' => '#165F4F',
                'primary_block_shadow' => 'rgba(29,122,101,.18)',
                'secondary_blocks_text' => '#10241F',
                'secondary_blocks_background' => '#F1F6F2',
                'secondary_blocks_border' => '#B7CCC0',
            ],
            'friendly_emerald_ink' => [
                'background_mode' => 'gradient',
                'background_color' => '#111827',
                'gradient_start' => '#111827',
                'gradient_end' => '#1F2937',
                'heading_color' => '#F8FAFC',
                'text_color' => '#D1D5DB',
                'primary_block_text' => '#FFFFFF',
                'primary_block_background' => '#16A34A',
                'primary_block_border' => '#15803D',
                'primary_block_shadow' => 'rgba(22,163,74,.22)',
                'secondary_blocks_text' => '#F8FAFC',
                'secondary_blocks_background' => '#1F2937',
                'secondary_blocks_border' => '#475569',
            ],
        ];

        return $this->enforce_goal_first_theme_pack_guardrails($palettes[$palette_key] ?? $palettes['trust_slate_blue']);
    }

    private function get_app_review_capability_context(array $capabilities, array $values, string $goal_type = ''): array {
        $goal_type = $goal_type !== '' ? $goal_type : $this->get_goal_type($values);
        $is_contact_goal = $this->is_contact_collection_goal($values, $goal_type);
        $capabilities = array_merge([
            'has_lead_funnel' => false,
            'has_contact_collector' => false,
            'has_email_collector' => false,
            'has_save_contact' => false,
            'has_whatsapp_contact' => false,
            'has_video' => false,
            'has_shop_links' => false,
            'has_chatbot' => false,
        ], $capabilities);

        $available_tools = [];

        if($capabilities['has_lead_funnel']) {
            $available_tools[] = 'Funnel kao glavni put prema prijavi ili kontaktu';
        }

        if($capabilities['has_whatsapp_contact']) {
            $available_tools[] = 'WhatsApp poruka';
        }

        if($capabilities['has_video']) {
            $available_tools[] = 'Video';
        }

        if($capabilities['has_shop_links']) {
            $available_tools[] = 'Forever prodajni linkovi';
        }

        if($capabilities['has_chatbot']) {
            $available_tools[] = 'AI savjetnik kao plutajuci pomocni element';
        }

        $missing_for_contact_goal = [];

        if($is_contact_goal && !$capabilities['has_lead_funnel']) {
            $missing_for_contact_goal[] = 'Nema funnel kao glavni put za kontakt ili prijavu';
        }

        if($is_contact_goal && !$capabilities['has_whatsapp_contact']) {
            $missing_for_contact_goal[] = 'Nema jednostavan put prema WhatsApp poruci';
        }

        if($is_contact_goal && !$capabilities['has_video']) {
            $missing_for_contact_goal[] = 'Nema video uvod koji lakše gradi interes';
        }

        return [
            'is_contact_collection_goal' => $is_contact_goal,
            'preferred_primary_path' => $is_contact_goal ? 'trust_then_lead_funnel' : ($goal_type === 'shop' ? 'shop_or_whatsapp' : 'hybrid'),
            'preferred_first_screen_system' => match(true) {
                $is_contact_goal => 'avatar ili stvarna fotografija + puno ime i prezime + kratka trust poruka + video ili Funnel kao prvi ozbiljan korak',
                $goal_type === 'shop' => 'fotografija ili jasan naslov + kratko objasnjenje + jedan glavni proizvodni ili shop korak',
                $goal_type === 'brand' => 'avatar ili stvarna fotografija + puno ime i prezime + jasan naslov + video + jedan glavni korak',
                default => 'kratak sloj povjerenja na vrhu + jedan glavni korak + miran sekundarni sadrzaj',
            },
            'funnel_role' => $is_contact_goal ? 'first_serious_action_after_trust' : 'optional',
            'trust_before_capture' => $is_contact_goal,
            'available_tools' => $available_tools,
            'missing_for_contact_goal' => $missing_for_contact_goal,
        ];
    }

    private function get_app_review_block_catalog_payload(): array {
        return [
            [
                'type' => 'avatar',
                'best_for' => 'osobni dojam i povjerenje na prvom ekranu',
                'warning' => 'ne vodi sam po sebi prema sljedecem koraku',
            ],
            [
                'type' => 'heading',
                'best_for' => 'jasna poruka kome je aplikacija namijenjena',
                'warning' => 'ako je nejasan ili predug, brzo gubi fokus',
            ],
            [
                'type' => 'paragraph',
                'best_for' => 'kratko objasnjenje koristi i sljedeceg koraka',
                'warning' => 'predug tekst usporava odluku',
            ],
            [
                'type' => 'video',
                'best_for' => 'brze gradjenje povjerenja i objasnjenje ponude',
                'warning' => 'mora imati jasan razlog zasto ga osoba treba pogledati',
            ],
            [
                'type' => 'lead_funnel',
                'best_for' => 'skupljanje kontakata, prijave i ozbiljan prvi korak',
                'warning' => 'treba biti dovoljno visoko i jasno najavljen',
            ],
            [
                'type' => 'custom_html_whatsapp',
                'best_for' => 'brza WhatsApp poruka kad je cilj razgovor ili pomoc pri izboru',
                'warning' => 'ne smije se natjecati s vise jednakih glavnih gumba',
            ],
            [
                'type' => 'link_discount',
                'best_for' => 'jasan prodajni korak prema shopu ili ponudi',
                'warning' => 'ako je previsoko bez povjerenja, moze djelovati preprodajno',
            ],
            [
                'type' => 'link_forever_product',
                'best_for' => 'fokus na jedan proizvod ili preporuku proizvoda',
                'warning' => 'previse proizvoda odjednom rasipa paznju',
            ],
            [
                'type' => 'review',
                'best_for' => 'dokaz i sigurnost prije glavnog koraka',
                'warning' => 'treba biti blizu glavne akcije, ne predaleko',
            ],
            [
                'type' => 'modal_text',
                'best_for' => 'dodatno objasnjenje bez zatrpavanja glavnog ekrana',
                'warning' => 'nije dobar kao prvi glavni korak',
            ],
            [
                'type' => 'link_app_switcher',
                'best_for' => 'prebacivanje na drugu aplikaciju tek kad to stvarno pomaze cilju',
                'warning' => 'na vrhu moze odvesti fokus s glavne akcije',
            ],
        ];
    }

    private function is_profile_complete(array $values): bool {
        return (bool) ($values['primary_goal'] && $values['priority_offer'] && !empty($values['active_channels']) && $values['available_time'] && $values['biggest_blocker'] && $values['communication_style'] && $values['follow_up_readiness'] && $values['weekly_change']);
    }

    private function normalize_single_choice(?string $value, array $allowed): string {
        $value = input_clean($value ?? '', 64);

        return in_array($value, $allowed, true) ? $value : '';
    }

    private function normalize_multiple_choice($values, array $allowed): array {
        if(!is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach($values as $value) {
            $value = input_clean($value, 64);

            if(in_array($value, $allowed, true) && !in_array($value, $normalized, true)) {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }

    private function get_last_30_days_shop_clicks(): int {
        $period_start_datetime = (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00');
                $shop_block_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
                $registration_block_types = ['link_forever_shop'];
                $shop_block_types_sql = "'" . implode("','", $shop_block_types) . "'";
                $registration_block_types_sql = "'" . implode("','", $registration_block_types) . "'";
                $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $shop_block_types_sql, $registration_block_types_sql);

        return (int) (database()->query("SELECT COUNT(*) AS `total`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`user_id` = {$this->user->user_id}
              AND `track_links`.`datetime` >= '{$period_start_datetime}'
              AND `track_links`.`is_unique` = 1
                            AND {$outbound_condition}")->fetch_object()->total ?? 0);
    }

    private function get_weekly_checkin_countdown_days(?string $next_checkin_at): ?int {
        if(!$next_checkin_at) {
            return null;
        }

        try {
            $now = new \DateTimeImmutable();
            $next_checkin = new \DateTimeImmutable($next_checkin_at);

            if($next_checkin <= $now) {
                return 0;
            }

            return (int) ceil(($next_checkin->getTimestamp() - $now->getTimestamp()) / 86400);
        } catch(\Throwable $exception) {
            return null;
        }
    }

    private function normalize_source_value(string $source): string {
        $source = mb_strtolower(trim($source));

        if($source === '') {
            return '';
        }

        if(strpos($source, '://') !== false) {
            $parsed_host = parse_url($source, PHP_URL_HOST);
            if(is_string($parsed_host) && $parsed_host !== '') {
                $source = $parsed_host;
            }
        }

        if(strpos($source, '/') !== false) {
            $source = explode('/', $source)[0];
        }

        return preg_replace('/^www\./', '', $source) ?? $source;
    }

    private function is_internal_source(string $source): bool {
        $source = $this->normalize_source_value($source);

        if($source === '') {
            return false;
        }

        $site_host = parse_url(SITE_URL, PHP_URL_HOST);
        $site_host = is_string($site_host) ? $this->normalize_source_value($site_host) : '';

        if($site_host !== '' && ($source === $site_host || str_ends_with($source, '.' . $site_host))) {
            return true;
        }

        return $source === 'forevercard.club' || str_ends_with($source, '.forevercard.club');
    }

    private function normalize_source_label(string $source): string {
        $source = $this->normalize_source_value($source);

        if($source === '' || in_array($source, ['(direct)', 'direct', 'none', '(none)'], true) || $this->is_internal_source($source)) {
            return 'direct_share';
        }

        if($source === 'qr') return 'qr';
        if($source === 'nfc_card') return 'nfc_card';
        if($source === 'direct_share') return 'direct_share';
        if(strpos($source, 'messenger') !== false) return 'messenger';
        if($source === 'fb' || strpos($source, 'facebook') !== false) return 'facebook';
        if($source === 'ig' || strpos($source, 'instagram') !== false) return 'instagram';
        if(strpos($source, 'whatsapp') !== false || $source === 'wa') return 'whatsapp';
        if($source === 'x' || strpos($source, 'twitter') !== false) return 'x';
        if(strpos($source, 'threads') !== false) return 'threads';
        if(strpos($source, 'tiktok') !== false) return 'tiktok';
        if(strpos($source, 'youtube') !== false || $source === 'youtu.be') return 'youtube';
        if(strpos($source, 'telegram') !== false) return 'telegram';
        if(strpos($source, 'viber') !== false) return 'viber';
        if(strpos($source, 'email') !== false || strpos($source, 'mail') !== false) return 'email';
        if(strpos($source, 'google') !== false || $source === 'gclid') return 'google';
        if(strpos($source, 'linkedin') !== false) return 'linkedin';
        if(strpos($source, 'pinterest') !== false) return 'pinterest';
        if(strpos($source, 'reddit') !== false) return 'reddit';
        if(strpos($source, 'snapchat') !== false) return 'snapchat';
        if(strpos($source, 'teams') !== false) return 'teams';

        return $source;
    }

    private function get_source_label(array $click): string {
        foreach([
            trim((string) ($click['utm_source'] ?? '')),
            trim((string) ($click['referrer_host'] ?? '')),
        ] as $candidate_source) {
            $normalized_source = $this->normalize_source_value($candidate_source);

            if($normalized_source === '' || $this->is_internal_source($normalized_source)) {
                continue;
            }

            return $this->normalize_source_label($normalized_source);
        }

        return 'direct_share';
    }

    private function increment_bucket(array &$buckets, string $key, string $label): void {
        $key = trim($key);
        $label = trim($label);

        if($key === '' || $label === '') {
            return;
        }

        if(!isset($buckets[$key])) {
            $buckets[$key] = ['label' => $label, 'total' => 0];
        }

        $buckets[$key]['total']++;
    }

    private function build_breakdown(array $buckets, int $total, int $limit = 5): array {
        uasort($buckets, static function($a, $b) {
            return (($b['total'] ?? 0) <=> ($a['total'] ?? 0)) ?: (($a['label'] ?? '') <=> ($b['label'] ?? ''));
        });

        $result = [];
        foreach(array_slice($buckets, 0, max(1, $limit), true) as $item) {
            $item_total = (int) ($item['total'] ?? 0);
            $result[] = [
                'label' => (string) ($item['label'] ?? '-'),
                'total' => $item_total,
                'share' => $total > 0 ? round(($item_total / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    private function get_primary_breakdown_label(array $breakdown, string $fallback = '-'): string {
        return !empty($breakdown[0]['label']) ? (string) $breakdown[0]['label'] : $fallback;
    }

    private function humanize_country_label(string $country_label): string {
        $country_label = trim($country_label);

        if($country_label === '' || $country_label === '-') {
            return l('ai_plan.analytics_unknown');
        }

        if(strlen($country_label) === 2) {
            $resolved_country = get_country_from_country_code(strtoupper($country_label));
            if($resolved_country) {
                return $resolved_country;
            }
        }

        return $country_label;
    }

    private function humanize_source_label(string $source_label): string {
        $source_label = trim(mb_strtolower($source_label));

        if($source_label === '' || $source_label === '-') {
            return l('ai_plan.analytics_unknown');
        }

        $map = [
            'direct' => l('ai_plan.analytics_value.source.direct'),
            'unattributed' => l('ai_plan.analytics_value.source.unattributed'),
            'qr' => l('ai_plan.analytics_value.source.qr'),
            'nfc_card' => l('ai_plan.analytics_value.source.nfc_card'),
            'direct_share' => l('ai_plan.analytics_value.source.direct_share'),
            'instagram' => l('ai_plan.analytics_value.source.instagram'),
            'facebook' => l('ai_plan.analytics_value.source.facebook'),
            'messenger' => l('ai_plan.analytics_value.source.messenger'),
            'whatsapp' => l('ai_plan.analytics_value.source.whatsapp'),
            'email' => l('ai_plan.analytics_value.source.email'),
            'google' => l('ai_plan.analytics_value.source.google'),
            'x' => l('ai_plan.analytics_value.source.x'),
            'threads' => l('ai_plan.analytics_value.source.threads'),
            'tiktok' => l('ai_plan.analytics_value.source.tiktok'),
            'youtube' => l('ai_plan.analytics_value.source.youtube'),
            'telegram' => l('ai_plan.analytics_value.source.telegram'),
            'viber' => l('ai_plan.analytics_value.source.viber'),
            'linkedin' => l('ai_plan.analytics_value.source.linkedin'),
            'pinterest' => l('ai_plan.analytics_value.source.pinterest'),
            'reddit' => l('ai_plan.analytics_value.source.reddit'),
            'snapchat' => l('ai_plan.analytics_value.source.snapchat'),
            'teams' => l('ai_plan.analytics_value.source.teams'),
        ];

        return $map[$source_label] ?? ucfirst($source_label);
    }

    private function humanize_medium_label(string $medium_label): string {
        $medium_label = trim(mb_strtolower($medium_label));

        if($medium_label === '' || $medium_label === '-') {
            return l('ai_plan.analytics_unknown');
        }

        $map = [
            'copy' => l('ai_plan.analytics_value.medium.copy'),
            'native_share' => l('ai_plan.analytics_value.medium.native_share'),
            'share_button' => l('ai_plan.analytics_value.medium.share_button'),
            'message' => l('ai_plan.analytics_value.medium.message'),
            'share' => l('ai_plan.analytics_value.medium.share'),
            'qr' => l('ai_plan.analytics_value.medium.qr'),
            'nfc_tap' => l('ai_plan.analytics_value.medium.nfc_tap'),
            'blog_cta_product' => l('ai_plan.analytics_value.medium.blog_cta_product'),
            'blog_cta_business' => l('ai_plan.analytics_value.medium.blog_cta_business'),
        ];

        return $map[$medium_label] ?? ucfirst(str_replace('_', ' ', $medium_label));
    }

    private function humanize_device_label(string $device_label): string {
        $device_label = trim(mb_strtolower($device_label));

        if($device_label === '' || $device_label === '-') {
            return l('ai_plan.analytics_unknown');
        }

        $map = [
            'mobile' => l('ai_plan.analytics_value.device.mobile'),
            'smartphone' => l('ai_plan.analytics_value.device.mobile'),
            'desktop' => l('ai_plan.analytics_value.device.desktop'),
            'tablet' => l('ai_plan.analytics_value.device.tablet'),
        ];

        return $map[$device_label] ?? $device_label;
    }

    private function humanize_language_label(string $language_label): string {
        $language_label = trim(mb_strtolower($language_label));

        if($language_label === '' || $language_label === '-') {
            return l('ai_plan.analytics_unknown');
        }

        if(str_starts_with($language_label, 'hr')) return l('ai_plan.analytics_value.language.hr');
        if(str_starts_with($language_label, 'en')) return l('ai_plan.analytics_value.language.en');
        if(str_starts_with($language_label, 'de')) return l('ai_plan.analytics_value.language.de');
        if(str_starts_with($language_label, 'it')) return l('ai_plan.analytics_value.language.it');

        return $language_label;
    }

    private function humanize_breakdown(array $breakdown, string $type): array {
        $humanized_breakdown = [];

        foreach($breakdown as $item) {
            $label = (string) ($item['label'] ?? '-');

            switch($type) {
                case 'country':
                    $label = $this->humanize_country_label($label);
                    break;

                case 'source':
                    $label = $this->humanize_source_label($label);
                    break;

                case 'device':
                    $label = $this->humanize_device_label($label);
                    break;

                case 'language':
                    $label = $this->humanize_language_label($label);
                    break;

                case 'medium':
                    $label = $this->humanize_medium_label($label);
                    break;
            }

            $humanized_breakdown[] = [
                'label' => $label,
                'total' => (int) ($item['total'] ?? 0),
                'share' => (float) ($item['share'] ?? 0),
            ];
        }

        return $humanized_breakdown;
    }

    private function get_sales_steps_payload(int $user_id, string $period_start_datetime): array {
        $sales_blocks = [];
        $monitored_forever_types = \Altum\Link::get_monitored_forever_outbound_types();
        $sales_step_types = array_unique(array_merge(['lead_funnel'], $monitored_forever_types));
        $sales_step_types_sql = "'" . implode("','", array_map(static function($type) {
            return str_replace("'", "\\'", (string) $type);
        }, $sales_step_types)) . "'";
        $sales_blocks_result = database()->query("SELECT `biolink_block_id`, `type`, `settings`
            FROM `biolinks_blocks`
            WHERE `user_id` = {$user_id}
              AND `type` IN ({$sales_step_types_sql})");

        while($row = $sales_blocks_result->fetch_object()) {
            $settings = json_decode($row->settings ?? '{}');
            if(!$settings instanceof \stdClass) {
                $settings = new \stdClass();
            }
            $block_type = (string) ($row->type ?? '');
            $sales_blocks[(int) $row->biolink_block_id] = [
                'type' => $block_type,
                'name' => $this->get_sales_step_name($block_type, $settings),
                'objective' => $this->get_sales_step_objective($block_type, $settings),
            ];
        }

        $blog_product_medium = \Altum\Link::get_blog_cta_tracking_medium('product');
        $blog_business_medium = \Altum\Link::get_blog_cta_tracking_medium('business');
        $blog_step_clicks = [
            'blog_product' => 0,
            'blog_business' => 0,
        ];
        $blog_step_result = database()->query("SELECT `utm_medium`, COUNT(*) AS `total`
            FROM `track_links`
            WHERE `user_id` = {$user_id}
              AND `is_unique` = 1
              AND `datetime` >= '{$period_start_datetime}'
              AND `utm_medium` IN ('{$blog_product_medium}', '{$blog_business_medium}')
            GROUP BY `utm_medium`");

        while($row = $blog_step_result->fetch_object()) {
            $medium = (string) ($row->utm_medium ?? '');
            if($medium === $blog_product_medium) {
                $blog_step_clicks['blog_product'] = (int) ($row->total ?? 0);
            }

            if($medium === $blog_business_medium) {
                $blog_step_clicks['blog_business'] = (int) ($row->total ?? 0);
            }
        }

        if(empty($sales_blocks) && array_sum($blog_step_clicks) === 0) {
            return [
                'total_funnels' => 0,
                'active_funnels' => 0,
                'unique_clicks' => 0,
                'total_leads' => 0,
                'conversion_rate' => 0.0,
                'top_funnel_name' => '-',
                'top_funnel_objective' => '-',
            ];
        }

        $sales_block_ids = array_keys($sales_blocks);
        $clicks_per_funnel = [];
        if(!empty($sales_block_ids)) {
            $sales_ids_sql = implode(',', array_map('intval', $sales_block_ids));
            $clicks_result = database()->query("SELECT `biolink_block_id`, SUM(`is_unique`) AS `unique_clicks`
                FROM `track_links`
                WHERE `user_id` = {$user_id}
                  AND `biolink_block_id` IN ({$sales_ids_sql})
                  AND `datetime` >= '{$period_start_datetime}'
                GROUP BY `biolink_block_id`");

            while($row = $clicks_result->fetch_object()) {
                $clicks_per_funnel[(int) $row->biolink_block_id] = (int) ($row->unique_clicks ?? 0);
            }
        }

        $leads_per_funnel = [];
        $funnel_ids = [];
        foreach($sales_blocks as $biolink_block_id => $sales_block) {
            if(($sales_block['type'] ?? '') === 'lead_funnel') {
                $funnel_ids[] = $biolink_block_id;
            }
        }

        if(!empty($funnel_ids)) {
            $funnel_ids_sql = implode(',', array_map('intval', $funnel_ids));
            $leads_result = database()->query("SELECT `biolink_block_id`, COUNT(*) AS `total_leads`
                FROM `data`
                WHERE `user_id` = {$user_id}
                  AND `type` = 'lead_funnel'
                  AND `biolink_block_id` IN ({$funnel_ids_sql})
                  AND `datetime` >= '{$period_start_datetime}'
                GROUP BY `biolink_block_id`");

            while($row = $leads_result->fetch_object()) {
                $leads_per_funnel[(int) $row->biolink_block_id] = (int) ($row->total_leads ?? 0);
            }
        }

        $active_funnels = 0;
        $unique_clicks = 0;
        $total_leads = 0;
        $top_funnel_name = '-';
        $top_funnel_objective = '-';
        $top_funnel_score = -1;

        foreach($sales_blocks as $biolink_block_id => $funnel_block) {
            $funnel_clicks = $clicks_per_funnel[$biolink_block_id] ?? 0;
            $funnel_leads = $leads_per_funnel[$biolink_block_id] ?? 0;

            if($funnel_clicks > 0 || $funnel_leads > 0) {
                $active_funnels++;
            }

            $unique_clicks += $funnel_clicks;
            $total_leads += $funnel_leads;

            $funnel_score = ($funnel_leads * 1000) + $funnel_clicks;
            if($funnel_score > $top_funnel_score) {
                $top_funnel_score = $funnel_score;
                $top_funnel_name = $funnel_block['name'] !== '' ? $funnel_block['name'] : 'Lead funnel';
                $top_funnel_objective = $funnel_block['objective'] !== '' ? $funnel_block['objective'] : '-';
            }
        }

        foreach([
            'blog_product' => [
                'name' => l('ai_plan.analytics_step.blog_product'),
                'objective' => l('ai_plan.analytics_step_objective.blog_product'),
            ],
            'blog_business' => [
                'name' => l('ai_plan.analytics_step.blog_business'),
                'objective' => l('ai_plan.analytics_step_objective.blog_business'),
            ],
        ] as $blog_step_key => $blog_step) {
            $blog_clicks = (int) ($blog_step_clicks[$blog_step_key] ?? 0);

            if($blog_clicks <= 0) {
                continue;
            }

            $active_funnels++;
            $unique_clicks += $blog_clicks;

            $step_score = $blog_clicks;
            if($step_score > $top_funnel_score) {
                $top_funnel_score = $step_score;
                $top_funnel_name = $blog_step['name'];
                $top_funnel_objective = $blog_step['objective'];
            }
        }

        return [
            'total_funnels' => count($sales_blocks) + count(array_filter($blog_step_clicks)),
            'active_funnels' => $active_funnels,
            'unique_clicks' => $unique_clicks,
            'total_leads' => $total_leads,
            'conversion_rate' => $unique_clicks ? round(($total_leads / $unique_clicks) * 100, 1) : 0.0,
            'top_funnel_name' => $top_funnel_name,
            'top_funnel_objective' => $top_funnel_objective,
        ];
    }

    private function get_sales_step_name(string $block_type, \stdClass $settings): string {
        $configured_name = trim((string) ($settings->name ?? ''));

        if($configured_name !== '') {
            return $configured_name;
        }

        return match($block_type) {
            'lead_funnel' => 'Lead funnel',
            'link_forever_shop' => 'Forever shop prijava',
            'link_forever_product' => 'Forever proizvod',
            'link_forever_living_bih' => 'Forever webshop',
            'link_forever_living_alb_kosovo' => 'Forever webshop',
            'link_forever_living_albania_kosovo' => 'Forever webshop',
            'link_discount' => 'The Aloe Vera Co. popust',
            default => 'Prodajni korak',
        };
    }

    private function get_sales_step_objective(string $block_type, \stdClass $settings): string {
        $configured_objective = trim((string) ($settings->thank_you_type ?? $settings->open_mode ?? ''));

        if($configured_objective !== '') {
            return $configured_objective;
        }

        return match($block_type) {
            'lead_funnel' => 'Lead funnel',
            'link_forever_shop' => l('ai_plan.analytics_step_objective.forever_registration'),
            'link_forever_product',
            'link_forever_living_bih',
            'link_forever_living_alb_kosovo',
            'link_forever_living_albania_kosovo',
            'link_discount' => l('ai_plan.analytics_step_objective.forever_shop'),
            default => '-',
        };
    }

    private function get_app_structure_position_signals(array $ordered_blocks): array {
        $video_types = ['youtube', 'video', 'tiktok_video', 'vimeo', 'twitter_video', 'vk_video'];
        $webshop_types = ['link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
        $contact_types = ['lead_funnel', 'contact_collector', 'email_collector', 'link_save_contact'];
        $salesish_types = array_merge($contact_types, ['link_forever_shop', 'link_forever_product', 'link_discount', 'link_app_switcher'], $webshop_types);

        $signals = [
            'total_ordered_blocks' => count($ordered_blocks),
            'first_sales_block_type' => '',
            'first_sales_block_label' => '',
            'first_contact_block_type' => '',
            'first_contact_block_label' => '',
            'funnel_position' => 0,
            'video_position' => 0,
            'whatsapp_position' => 0,
            'discount_position' => 0,
            'webshop_position' => 0,
            'registration_position' => 0,
            'product_position' => 0,
            'chatbot_position' => 0,
            'product_blocks_before_video' => 0,
            'product_blocks_before_whatsapp' => 0,
            'sales_blocks_before_whatsapp' => 0,
            'direct_purchase_blocks_before_whatsapp' => 0,
            'has_product_block_before_video' => false,
            'has_product_block_before_whatsapp' => false,
            'has_registration_before_whatsapp' => false,
            'has_discount_before_whatsapp' => false,
            'has_webshop_before_whatsapp' => false,
            'is_funnel_first_sales_block' => false,
            'is_video_before_discount_and_webshop' => false,
            'is_whatsapp_after_funnel' => false,
            'is_whatsapp_immediately_after_funnel' => false,
        ];

        $whatsapp_position = 0;

        foreach($ordered_blocks as $index => $block_preview) {
            $position = $index + 1;
            $type = (string) ($block_preview['type'] ?? '');
            $label = trim((string) ($block_preview['label'] ?? ''));

            if($type === '') {
                continue;
            }

            if($signals['first_sales_block_type'] === '' && in_array($type, $salesish_types, true)) {
                $signals['first_sales_block_type'] = $type;
                $signals['first_sales_block_label'] = $label;
            }

            if($signals['first_contact_block_type'] === '' && ($this->is_app_review_whatsapp_block($type, (object) []) || in_array($type, $contact_types, true))) {
                $signals['first_contact_block_type'] = $type;
                $signals['first_contact_block_label'] = $label;
            }

            if(!$signals['funnel_position'] && $type === 'lead_funnel') {
                $signals['funnel_position'] = $position;
            }

            if(!$signals['video_position'] && in_array($type, $video_types, true)) {
                $signals['video_position'] = $position;
            }

            if(!$signals['whatsapp_position'] && $type === 'custom_html_whatsapp') {
                $signals['whatsapp_position'] = $position;
                $whatsapp_position = $position;
            }

            if(!$signals['discount_position'] && $type === 'link_discount') {
                $signals['discount_position'] = $position;
            }

            if(!$signals['webshop_position'] && in_array($type, $webshop_types, true)) {
                $signals['webshop_position'] = $position;
            }

            if(!$signals['registration_position'] && $type === 'link_forever_shop') {
                $signals['registration_position'] = $position;
            }

            if(!$signals['product_position'] && $type === 'link_forever_product') {
                $signals['product_position'] = $position;
            }

            if(!$signals['chatbot_position'] && in_array($type, ['custom_html_chatbot', 'custom_html_chatbot_pets'], true)) {
                $signals['chatbot_position'] = $position;
            }
        }

        if(!$whatsapp_position && $signals['first_contact_block_type'] === 'custom_html_whatsapp') {
            $whatsapp_position = (int) $signals['whatsapp_position'];
        }

        foreach($ordered_blocks as $index => $block_preview) {
            $position = $index + 1;
            $type = (string) ($block_preview['type'] ?? '');

            if($type === 'link_forever_product') {
                if($signals['video_position'] && $position < $signals['video_position']) {
                    $signals['product_blocks_before_video']++;
                }

                if($whatsapp_position && $position < $whatsapp_position) {
                    $signals['product_blocks_before_whatsapp']++;
                }
            }

            if($whatsapp_position && $position < $whatsapp_position && in_array($type, $salesish_types, true)) {
                $signals['sales_blocks_before_whatsapp']++;
            }

            if($whatsapp_position && $position < $whatsapp_position && ($type === 'link_discount' || $type === 'link_forever_shop' || in_array($type, $webshop_types, true))) {
                $signals['direct_purchase_blocks_before_whatsapp']++;
            }
        }

        $signals['has_product_block_before_video'] = $signals['product_blocks_before_video'] > 0;
        $signals['has_product_block_before_whatsapp'] = $signals['product_blocks_before_whatsapp'] > 0;
        $signals['has_registration_before_whatsapp'] = $signals['registration_position'] > 0 && $whatsapp_position > 0 && $signals['registration_position'] < $whatsapp_position;
        $signals['has_discount_before_whatsapp'] = $signals['discount_position'] > 0 && $whatsapp_position > 0 && $signals['discount_position'] < $whatsapp_position;
        $signals['has_webshop_before_whatsapp'] = $signals['webshop_position'] > 0 && $whatsapp_position > 0 && $signals['webshop_position'] < $whatsapp_position;
        $signals['is_funnel_first_sales_block'] = $signals['first_sales_block_type'] === 'lead_funnel';
        $signals['is_video_before_discount_and_webshop'] = $signals['video_position'] > 0
            && ($signals['discount_position'] === 0 || $signals['video_position'] < $signals['discount_position'])
            && ($signals['webshop_position'] === 0 || $signals['video_position'] < $signals['webshop_position'])
            && ($signals['registration_position'] === 0 || $signals['video_position'] < $signals['registration_position']);
        $signals['is_whatsapp_after_funnel'] = $signals['funnel_position'] > 0 && $whatsapp_position > 0 && $whatsapp_position > $signals['funnel_position'];
        $signals['is_whatsapp_immediately_after_funnel'] = $signals['funnel_position'] > 0 && $whatsapp_position === ($signals['funnel_position'] + 1);

        return $signals;
    }

    private function get_weekly_ai_app_structure_snapshot(?array $selected_app): array {
        if(!$selected_app) {
            return [
                'name' => '',
                'url' => '',
                'ordered_block_previews' => [],
                'position_signals' => [],
            ];
        }

        return [
            'name' => (string) (($selected_app['name'] ?? '') ?: ($selected_app['url'] ?? '')),
            'url' => (string) ($selected_app['url'] ?? ''),
            'ordered_block_previews' => array_values(array_filter((array) ($selected_app['ordered_block_previews'] ?? []), 'is_array')),
            'position_signals' => (array) ($selected_app['position_signals'] ?? []),
        ];
    }

    private function get_app_review_primary_action_block_snapshot(array $selected_app): array {
        foreach((array) ($selected_app['sales_path_preview'] ?? []) as $preview) {
            if(!is_array($preview)) {
                continue;
            }

            return [
                'block_id' => (int) ($preview['block_id'] ?? 0),
                'type' => (string) ($preview['type'] ?? ''),
                'label' => (string) ($preview['label'] ?? ''),
                'style_profile' => (array) ($preview['style_profile'] ?? []),
            ];
        }

        foreach((array) ($selected_app['ordered_block_previews'] ?? []) as $preview) {
            if(!is_array($preview)) {
                continue;
            }

            $style_profile = (array) ($preview['style_profile'] ?? []);

            if(empty($style_profile)) {
                continue;
            }

            return [
                'block_id' => (int) ($preview['block_id'] ?? 0),
                'type' => (string) ($preview['type'] ?? ''),
                'label' => (string) ($preview['label'] ?? ''),
                'style_profile' => $style_profile,
            ];
        }

        return [];
    }

    private function get_app_review_secondary_block_style_samples(array $selected_app, int $limit = 3): array {
        $samples = [];
        $primary_snapshot = $this->get_app_review_primary_action_block_snapshot($selected_app);
        $primary_type = (string) ($primary_snapshot['type'] ?? '');
        $primary_label = (string) ($primary_snapshot['label'] ?? '');

        foreach((array) ($selected_app['ordered_block_previews'] ?? []) as $preview) {
            if(!is_array($preview)) {
                continue;
            }

            $style_profile = (array) ($preview['style_profile'] ?? []);

            if(empty($style_profile)) {
                continue;
            }

            $is_primary_block = $primary_type !== ''
                && (string) ($preview['type'] ?? '') === $primary_type
                && (string) ($preview['label'] ?? '') === $primary_label;

            if($is_primary_block) {
                continue;
            }

            $samples[] = [
                'block_id' => (int) ($preview['block_id'] ?? 0),
                'type' => (string) ($preview['type'] ?? ''),
                'label' => (string) ($preview['label'] ?? ''),
                'style_profile' => $style_profile,
            ];

            if(count($samples) >= $limit) {
                break;
            }
        }

        return $samples;
    }

    private function get_app_review_design_diagnostic(array $selected_app): array {
        $styled_blocks = 0;
        $strong_blocks = 0;
        $busy_blocks = 0;
        $accent_families = [];

        foreach((array) ($selected_app['ordered_block_previews'] ?? []) as $preview) {
            if(!is_array($preview)) {
                continue;
            }

            $style_profile = (array) ($preview['style_profile'] ?? []);

            if(empty($style_profile)) {
                continue;
            }

            $styled_blocks++;

            if((string) ($style_profile['emphasis_strength'] ?? '') === 'strong') {
                $strong_blocks++;
            }

            if((string) ($style_profile['style_complexity'] ?? '') === 'busy') {
                $busy_blocks++;
            }

            foreach(['background_signal', 'border_signal', 'text_signal'] as $signal_key) {
                $family = (string) (($style_profile[$signal_key]['family'] ?? ''));

                if($family !== '' && $family !== 'neutral') {
                    $accent_families[$family] = true;
                }
            }
        }

        $app_visual_profile = (array) ($selected_app['visual_profile'] ?? []);
        foreach(['background_signal', 'gradient_start_signal', 'gradient_end_signal', 'text_signal'] as $signal_key) {
            $family = (string) (($app_visual_profile[$signal_key]['family'] ?? ''));

            if($family !== '' && $family !== 'neutral') {
                $accent_families[$family] = true;
            }
        }

        $accent_family_count = count($accent_families);
        $visual_noise_level = 'calm';
        $recommendation_bias = 'fine_tune';

        if($accent_family_count >= 4 || $busy_blocks >= 3 || $strong_blocks >= 3) {
            $visual_noise_level = 'high';
            $recommendation_bias = 'reset';
        } elseif($accent_family_count >= 2 || $busy_blocks >= 1 || $strong_blocks >= 2) {
            $visual_noise_level = 'medium';
            $recommendation_bias = 'soft_reset';
        }

        $note = match($recommendation_bias) {
            'reset' => 'Trenutni vizual izgleda dovoljno raspršeno da boje i naglaske ne treba čuvati po defaultu. AI smije predložiti jasniji reset.',
            'soft_reset' => 'Trenutni vizual ima više naglasaka ili više stilskih smjerova. AI neka predloži mirniji i fokusiraniji sustav, ne samo kozmetičku doradu.',
            default => 'Trenutni vizual nije glavni problem. AI može zadržati smjer samo ako podržava cilj i ostaje dovoljno jasan.',
        };

        return [
            'styled_blocks' => $styled_blocks,
            'strong_emphasis_blocks' => $strong_blocks,
            'busy_blocks' => $busy_blocks,
            'accent_family_count' => $accent_family_count,
            'visual_noise_level' => $visual_noise_level,
            'recommendation_bias' => $recommendation_bias,
            'note' => $note,
        ];
    }

    private function get_app_structure_payload(int $user_id): array {
        /* Custom code: FC-2026-03-31: load the protected default biolink and avoid non-portable links columns */
        $main_biolink_id = (int) (fc_get_user_main_biolink_id($user_id) ?? 0);
        $apps_result = database()->query("SELECT `link_id`, `url`, `settings`, `is_enabled`, `datetime`, `last_datetime` FROM `links` WHERE `user_id` = {$user_id} AND `type` = 'biolink'");
        /* /Custom code: FC-2026-03-31 */

        $apps = [];
        $link_ids = [];

        if(!$apps_result) {
            return [
                'total_apps' => 0,
                'apps' => [],
                'main_app' => null,
                'top_app_link_id' => 0,
                'top_app_name' => '',
                'top_app_url' => '-',
                'top_app_public_url' => '',
                'top_app_total_blocks' => 0,
                'block_mix' => [],
                'priority_blocks' => [],
            ];
        }

        while($row = $apps_result->fetch_object()) {
            $link_id = (int) ($row->link_id ?? 0);

            if(!$link_id) {
                continue;
            }

            $apps[$link_id] = [
                'link_id' => $link_id,
                'name' => '',
                'url' => (string) ($row->url ?? ''),
                'public_url' => !empty($row->url) ? url((string) $row->url) : '',
                'visual_profile' => $this->get_app_review_visual_profile($this->decode_biolink_block_settings($row->settings ?? null)),
                'is_enabled' => isset($row->is_enabled) ? (bool) $row->is_enabled : true,
                'datetime' => $row->datetime ?? null,
                'last_datetime' => $row->last_datetime ?? null,
                'total_blocks' => 0,
                'forever_blocks' => 0,
                'funnel_blocks' => 0,
                'social_blocks' => 0,
                'content_blocks' => 0,
                'primary_visual_url' => '',
                'primary_visual_type' => '',
                'primary_visual_scope' => 'none',
                'first_screen_blocks' => [],
                'sales_path_preview' => [],
                'ordered_block_previews' => [],
                'visual_segments' => $this->get_default_app_review_visual_segments(),
                'conversion_capabilities' => [
                    'has_lead_funnel' => false,
                    'has_contact_collector' => false,
                'has_email_collector' => false,
                'has_save_contact' => false,
                'has_whatsapp_contact' => false,
                'has_video' => false,
                'has_shop_links' => false,
                'has_chatbot' => false,
            ],
        ];
            $link_ids[] = $link_id;
        }

        if(empty($link_ids)) {
            return [
                'total_apps' => 0,
                'apps' => [],
                'main_app' => null,
                'top_app_link_id' => 0,
                'top_app_name' => '',
                'top_app_url' => '-',
                'top_app_public_url' => '',
                'top_app_total_blocks' => 0,
                'block_mix' => [],
                'priority_blocks' => [],
            ];
        }

        $block_counts = [];
        $priority_block_types = [
            'socials',
            'heading',
            'paragraph',
            'image',
            'avatar',
            'lead_funnel',
            'link_forever_shop',
            'link_forever_product',
            'link_discount',
            'link_app_switcher',
            'youtube',
            'tiktok_video',
        ];
        $priority_blocks = array_fill_keys($priority_block_types, 0);

        $link_ids_sql = implode(',', array_map('intval', $link_ids));
        $blocks_result = database()->query("SELECT `biolink_block_id`, `link_id`, `type`, `location_url`, `settings`, `order`
            FROM `biolinks_blocks`
            WHERE `user_id` = {$user_id} AND `link_id` IN ({$link_ids_sql})
              AND `is_enabled` = 1
            ORDER BY `link_id` ASC, `order` ASC, `biolink_block_id` ASC");

        if($blocks_result) {
            while($row = $blocks_result->fetch_object()) {
                $link_id = (int) ($row->link_id ?? 0);
                $type = (string) ($row->type ?? '');
                $settings = $this->decode_biolink_block_settings($row->settings ?? null);

                if($type === '' || !isset($apps[$link_id])) {
                    continue;
                }

                $apps[$link_id]['total_blocks']++;
                $block_counts[$type] = ($block_counts[$type] ?? 0) + 1;

                if(isset($priority_blocks[$type])) {
                    $priority_blocks[$type]++;
                }

                if(in_array($type, ['heading', 'paragraph', 'image', 'avatar', 'youtube', 'tiktok_video'], true)) {
                    $apps[$link_id]['content_blocks']++;
                }

                if($type === 'socials') {
                    $apps[$link_id]['social_blocks']++;
                }

                if($type === 'lead_funnel') {
                    $apps[$link_id]['funnel_blocks']++;
                    $apps[$link_id]['conversion_capabilities']['has_lead_funnel'] = true;
                }

                if(str_starts_with($type, 'link_forever') || in_array($type, ['link_discount', 'link_app_switcher'], true)) {
                    $apps[$link_id]['forever_blocks']++;
                    $apps[$link_id]['conversion_capabilities']['has_shop_links'] = true;
                }

                if(in_array($type, ['youtube', 'video', 'tiktok_video', 'vimeo', 'twitter_video', 'vk_video'], true)) {
                    $apps[$link_id]['conversion_capabilities']['has_video'] = true;
                }

                if(in_array($type, ['contact_collector'], true)) {
                    $apps[$link_id]['conversion_capabilities']['has_contact_collector'] = true;
                }

                if(in_array($type, ['email_collector'], true)) {
                    $apps[$link_id]['conversion_capabilities']['has_email_collector'] = true;
                }

                if(in_array($type, ['link_save_contact'], true)) {
                    $apps[$link_id]['conversion_capabilities']['has_save_contact'] = true;
                }

                if(in_array($type, ['custom_html_chatbot', 'custom_html_chatbot_pets'], true)) {
                    $apps[$link_id]['conversion_capabilities']['has_chatbot'] = true;
                }

                if($this->is_app_review_whatsapp_block($type, $settings)) {
                    $apps[$link_id]['conversion_capabilities']['has_whatsapp_contact'] = true;
                }

                if($apps[$link_id]['primary_visual_url'] === '' && in_array($type, ['image', 'avatar'], true)) {
                    $visual_url = $this->get_app_review_visual_url_from_block($type, $settings);

                    if($visual_url !== '') {
                        $apps[$link_id]['primary_visual_url'] = $visual_url;
                        $apps[$link_id]['primary_visual_type'] = $type;
                        $apps[$link_id]['primary_visual_scope'] = $type === 'image' ? 'hero_image_only' : 'avatar_only';
                    }
                }

                if(count($apps[$link_id]['first_screen_blocks']) < 6) {
                    $label = $this->get_app_review_block_preview_label($type, $settings);

                    if($label !== '') {
                        $apps[$link_id]['first_screen_blocks'][] = [
                            'type' => $type,
                            'label' => $label,
                        ];
                    }
                }

                $style_profile = $this->get_app_review_block_style_profile($settings);

                if(
                    count($apps[$link_id]['sales_path_preview']) < 5
                    && (
                        $type === 'lead_funnel'
                        || $this->is_app_review_whatsapp_block($type, $settings)
                        || str_starts_with($type, 'link_forever')
                        || in_array($type, ['link_discount', 'link_app_switcher', 'link_save_contact', 'contact_collector', 'email_collector'], true)
                    )
                ) {
                    $apps[$link_id]['sales_path_preview'][] = [
                        'block_id' => (int) ($row->biolink_block_id ?? 0),
                        'type' => $type,
                        'label' => $this->get_app_review_block_preview_label($type, $settings),
                        'location_url' => (string) ($row->location_url ?? ''),
                        'style_profile' => $style_profile,
                    ];
                }

                $apps[$link_id]['ordered_block_previews'][] = [
                    'block_id' => (int) ($row->biolink_block_id ?? 0),
                    'order' => (int) ($row->order ?? 0),
                    'type' => $type,
                    'label' => $this->get_app_review_block_preview_label($type, $settings),
                    'location_url' => (string) ($row->location_url ?? ''),
                    'visual_url' => $this->get_app_review_block_visual_url($type, $settings),
                    'style_profile' => $style_profile,
                ];
            }
        }

        foreach($apps as $link_id => $app) {
            $ordered_blocks = array_values(array_filter((array) ($app['ordered_block_previews'] ?? []), 'is_array'));
            $total_preview_blocks = count($ordered_blocks);
            $segments = $this->get_default_app_review_visual_segments();

            foreach($ordered_blocks as $index => $block_preview) {
                $segment_key = $this->get_app_review_segment_key($index + 1, $total_preview_blocks);

                if(count($segments[$segment_key]['items']) < 6) {
                    $segments[$segment_key]['items'][] = [
                        'type' => (string) ($block_preview['type'] ?? ''),
                        'label' => (string) ($block_preview['label'] ?? ''),
                    ];
                }

                if($segments[$segment_key]['primary_visual_url'] === '' && !empty($block_preview['visual_url'])) {
                    $segments[$segment_key]['primary_visual_url'] = (string) $block_preview['visual_url'];
                }
            }

            if($segments['hero']['primary_visual_url'] === '' && !empty($app['primary_visual_url'])) {
                $segments['hero']['primary_visual_url'] = (string) ($app['primary_visual_url'] ?? '');
            }

            $apps[$link_id]['visual_segments'] = $segments;
            $apps[$link_id]['position_signals'] = $this->get_app_structure_position_signals($ordered_blocks);
        }

        uasort($block_counts, static function($a, $b) {
            return $b <=> $a;
        });

        $top_app = $this->get_main_app($apps, $main_biolink_id) ?? $this->get_default_app_summary($main_biolink_id);
        $block_mix = [];
        $available_apps = [];

        foreach($apps as $app) {
            $available_apps[(int) ($app['link_id'] ?? 0)] = [
                'link_id' => (int) ($app['link_id'] ?? 0),
                'name' => (string) (($app['name'] ?? '') ?: ($app['url'] ?? '')),
                'url' => (string) ($app['url'] ?? ''),
                'public_url' => (string) ($app['public_url'] ?? ''),
                'is_enabled' => (bool) ($app['is_enabled'] ?? true),
                'datetime' => $app['datetime'] ?? null,
                'last_datetime' => $app['last_datetime'] ?? null,
                'total_blocks' => (int) ($app['total_blocks'] ?? 0),
                'forever_blocks' => (int) ($app['forever_blocks'] ?? 0),
                'funnel_blocks' => (int) ($app['funnel_blocks'] ?? 0),
                'social_blocks' => (int) ($app['social_blocks'] ?? 0),
                'content_blocks' => (int) ($app['content_blocks'] ?? 0),
                'visual_profile' => (array) ($app['visual_profile'] ?? []),
                'primary_visual_url' => (string) ($app['primary_visual_url'] ?? ''),
                'primary_visual_type' => (string) ($app['primary_visual_type'] ?? ''),
                'primary_visual_scope' => (string) ($app['primary_visual_scope'] ?? 'none'),
                'first_screen_blocks' => array_values(array_filter((array) ($app['first_screen_blocks'] ?? []), 'is_array')),
                'sales_path_preview' => array_values(array_filter((array) ($app['sales_path_preview'] ?? []), 'is_array')),
                'ordered_block_previews' => array_values(array_filter((array) ($app['ordered_block_previews'] ?? []), 'is_array')),
                'visual_segments' => (array) ($app['visual_segments'] ?? $this->get_default_app_review_visual_segments()),
                'position_signals' => (array) ($app['position_signals'] ?? []),
                'conversion_capabilities' => (array) ($app['conversion_capabilities'] ?? []),
            ];
        }

        foreach(array_slice($block_counts, 0, 8, true) as $type => $total) {
            $block_mix[] = [
                'type' => $type,
                'total' => (int) $total,
            ];
        }

        return [
            'total_apps' => count($apps),
            'apps' => $available_apps,
            'main_app' => $top_app,
            'top_app_link_id' => (int) ($top_app['link_id'] ?? 0),
            'top_app_name' => (string) (($top_app['name'] ?? '') ?: ($top_app['url'] ?? '')),
            'top_app_url' => !empty($top_app['url']) ? (string) $top_app['url'] : '-',
            'top_app_public_url' => (string) ($top_app['public_url'] ?? ''),
            'top_app_total_blocks' => (int) ($top_app['total_blocks'] ?? 0),
            'block_mix' => $block_mix,
            'priority_blocks' => $priority_blocks,
        ];
    }

    private function build_app_review_ai_input(array $values, array $analytics_payload, array $app_structure_payload, int $current_clicks_30d, string $request_context = '', ?array $selected_app = null, array $selected_app_block_attribution = [], ?array $previous_review = null, array $coach_context = []): array {
        $selected_app = $selected_app ?? $this->get_selected_app($app_structure_payload);
        $goal_type = $this->get_effective_app_review_goal_type($values, $request_context, $selected_app);
        $selected_app_block_attribution = !empty($selected_app_block_attribution)
            ? $this->normalize_app_review_block_attribution_payload($selected_app_block_attribution)
            : $this->get_app_review_block_attribution_payload((array) $selected_app);
        $quality_payload = $this->get_app_review_quality_payload($selected_app, $current_clicks_30d);
        $selected_app_capabilities = (array) ($selected_app['conversion_capabilities'] ?? []);
        $capability_context = $this->get_app_review_capability_context($selected_app_capabilities, $values, $goal_type);
        $fcc_goal_system = $this->get_fcc_goal_system_payload($values, $goal_type);
        $fcc_core_block_policy = $this->get_fcc_core_block_policy($values, $goal_type, $request_context);
        $palette_feedback_outcome = $this->get_latest_palette_feedback_for_app(
            $this->get_saved_weekly_outcomes($this->user->preferences ?? null),
            (int) ($selected_app['link_id'] ?? 0),
            (string) ($previous_review['review_key'] ?? ''),
            (string) ($previous_review['generated_at'] ?? '')
        );

        return [
            'user' => [
                'name' => (string) ($this->user->name ?? ''),
                'email' => (string) ($this->user->email ?? ''),
            ],
            'goal_context' => [
                'goal_type' => $goal_type,
                'primary_goal' => !empty($values['primary_goal']) ? l('ai_plan.option.primary_goal.' . $values['primary_goal']) : '',
                'priority_offer' => !empty($values['priority_offer']) ? l('ai_plan.option.priority_offer.' . $values['priority_offer']) : '',
                'biggest_blocker' => !empty($values['biggest_blocker']) ? l('ai_plan.option.biggest_blocker.' . $values['biggest_blocker']) : '',
                'communication_style' => !empty($values['communication_style']) ? l('ai_plan.option.communication_style.' . $values['communication_style']) : '',
                'follow_up_readiness' => !empty($values['follow_up_readiness']) ? l('ai_plan.option.follow_up_readiness.' . $values['follow_up_readiness']) : '',
                'audience_focus' => (string) ($values['audience_focus'] ?? ''),
                'product_focus' => (string) ($values['product_focus'] ?? ''),
                'visual_tone_preference' => (string) ($values['visual_tone_preference'] ?? ''),
                'notes' => (string) ($values['notes'] ?? ''),
            ],
            'ordering_policy' => [
                'trust_first_for_contact_goals' => $this->is_contact_collection_goal($values, $goal_type),
                'preferred_trust_hero' => 'avatar ili stvarna fotografija + puno ime i prezime + kratki trust tekst ili video',
                'primary_conversion_after_trust' => $this->is_contact_collection_goal($values, $goal_type) ? 'lead_funnel_or_whatsapp' : 'best_goal_match',
                'owner_identity_anchor' => (string) ($this->user->name ?? ''),
                'full_name_should_follow_avatar' => $goal_type !== 'shop',
            ],
            'growth_stage' => $current_clicks_30d >= 15 ? 'active_signal' : 'building_signal',
            'analytics_30d' => [
                'webshop_clicks' => $current_clicks_30d,
                'top_source' => (string) ($analytics_payload['top_source_label'] ?? '-'),
                'top_country' => (string) ($analytics_payload['top_country_label'] ?? '-'),
                'top_device' => (string) ($analytics_payload['top_device_label'] ?? '-'),
                'top_language' => (string) ($analytics_payload['top_language_label'] ?? '-'),
                'blog_article_clicks' => (int) ($analytics_payload['blog_article_clicks'] ?? 0),
                'blog_product_clicks' => (int) ($analytics_payload['blog_product_clicks'] ?? 0),
                'blog_business_clicks' => (int) ($analytics_payload['blog_business_clicks'] ?? 0),
            'funnel' => $analytics_payload['funnel'] ?? [],
            ],
            'app_structure' => $app_structure_payload,
            'design_policy' => [
                'target_mode' => 'goal_first_target_theme',
                'current_visual_role' => 'diagnostic_only',
                'reuse_current_palette_only_if' => 'current palette is already calm, cohesive and clearly supports the goal',
                'do_not_clone_current_hex_values' => true,
            ],
            'palette_feedback' => [
                'has_feedback' => (bool) $palette_feedback_outcome,
                'feedback' => (string) ($palette_feedback_outcome['palette_feedback'] ?? ''),
                'feedback_label' => $this->get_palette_feedback_label($palette_feedback_outcome['palette_feedback'] ?? ''),
                'decision' => (string) ($palette_feedback_outcome['palette_decision'] ?? $this->get_palette_feedback_decision($palette_feedback_outcome['palette_feedback'] ?? '')),
                'note' => (string) ($palette_feedback_outcome['palette_feedback_note'] ?? ''),
                'selected_link_id' => max(0, (int) ($palette_feedback_outcome['selected_link_id'] ?? 0)),
                'review_key' => (string) ($palette_feedback_outcome['app_review_review_key'] ?? ''),
                'submitted_at' => (string) ($palette_feedback_outcome['submitted_at'] ?? ''),
            ],
            'selected_app' => [
                'link_id' => (int) ($selected_app['link_id'] ?? 0),
                'name' => (string) (($selected_app['name'] ?? '') ?: ($selected_app['url'] ?? '')),
                'url' => (string) ($selected_app['url'] ?? ''),
                'public_url' => (string) ($selected_app['public_url'] ?? ''),
                'total_blocks' => (int) ($selected_app['total_blocks'] ?? 0),
                'forever_blocks' => (int) ($selected_app['forever_blocks'] ?? 0),
                'funnel_blocks' => (int) ($selected_app['funnel_blocks'] ?? 0),
                'social_blocks' => (int) ($selected_app['social_blocks'] ?? 0),
                'content_blocks' => (int) ($selected_app['content_blocks'] ?? 0),
                'conversion_capabilities' => $selected_app_capabilities,
                'conversion_context' => $capability_context,
                'block_attribution' => $selected_app_block_attribution,
                'visual_context' => [
                    'visual_profile' => (array) ($selected_app['visual_profile'] ?? []),
                    'scope' => (string) ($selected_app['primary_visual_scope'] ?? 'none'),
                    'visual_type' => (string) ($selected_app['primary_visual_type'] ?? ''),
                    'primary_visual_url' => (string) ($selected_app['primary_visual_url'] ?? ''),
                    'design_diagnostic' => $this->get_app_review_design_diagnostic($selected_app),
                    'first_screen_blocks' => array_values(array_filter((array) ($selected_app['first_screen_blocks'] ?? []), 'is_array')),
                    'primary_action_block' => $this->get_app_review_primary_action_block_snapshot($selected_app),
                    'secondary_block_style_samples' => $this->get_app_review_secondary_block_style_samples($selected_app),
                    'sales_path_preview' => array_values(array_filter((array) ($selected_app['sales_path_preview'] ?? []), 'is_array')),
                    'ordered_block_previews' => array_values(array_filter((array) ($selected_app['ordered_block_previews'] ?? []), 'is_array')),
                    'visual_segments' => (array) ($selected_app['visual_segments'] ?? $this->get_default_app_review_visual_segments()),
                    'position_signals' => (array) ($selected_app['position_signals'] ?? []),
                ],
            ],
            'selected_app_performance' => $quality_payload['performance'] ?? [],
            'quality_benchmark' => [
                'score' => (int) ($quality_payload['score'] ?? 0),
                'level' => (string) ($quality_payload['level_key'] ?? 'foundation'),
                'summary' => (string) ($quality_payload['summary'] ?? ''),
                'benchmark' => $quality_payload['benchmark'] ?? [],
                'peer_examples' => $quality_payload['peer_examples'] ?? [],
            ],
            'editor_theme_capabilities' => [
                'fonts' => array_keys((array) (settings()->links->biolinks_fonts ?? [])),
                'font_size_min' => 12,
                'font_size_max' => 22,
                'width_options' => ['6', '8', '10', '12'],
                'block_spacing_options' => ['1', '2', '3'],
                'hover_animation_options' => ['false', 'smooth', 'instant'],
            ],
            'fcc_goal_system' => $fcc_goal_system,
            'fcc_core_block_policy' => $fcc_core_block_policy,
            'fcc_block_catalog' => $this->get_app_review_block_catalog_payload(),
            'coach_context' => [
                'has_recent_activity' => !empty($coach_context['has_recent_activity']),
                'last_touch_at' => $coach_context['last_touch_at'] ?? null,
                'conversation_count' => (int) ($coach_context['conversation_count'] ?? 0),
                'summary' => (string) ($coach_context['summary'] ?? ''),
                'recent_topics' => array_values(array_filter((array) ($coach_context['recent_topics'] ?? []), 'is_string')),
                'recent_challenges' => array_values(array_filter((array) ($coach_context['recent_challenges'] ?? []), 'is_string')),
                'recent_user_messages' => array_values(array_filter((array) ($coach_context['recent_user_messages'] ?? []), 'is_string')),
                'recent_assistant_guidance' => array_values(array_filter((array) ($coach_context['recent_assistant_guidance'] ?? []), 'is_string')),
            ],
            'request_context' => $request_context,
        ];
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

    private function compare_app_review_signal_rows(array $a, array $b): int {
        return (($b['weighted_signal_score'] ?? 0) <=> ($a['weighted_signal_score'] ?? 0))
            ?: (($b['shop_contacts_30d'] ?? 0) <=> ($a['shop_contacts_30d'] ?? 0))
            ?: (($b['whatsapp_contacts_30d'] ?? 0) <=> ($a['whatsapp_contacts_30d'] ?? 0))
            ?: (($b['contact_captures_30d'] ?? $this->get_app_review_contact_captures_30d($b)) <=> ($a['contact_captures_30d'] ?? $this->get_app_review_contact_captures_30d($a)))
            ?: (($b['product_clicks_30d'] ?? 0) <=> ($a['product_clicks_30d'] ?? 0))
            ?: ((string) ($a['url'] ?? '') <=> (string) ($b['url'] ?? ''));
    }

    private function get_app_review_performance_snapshot(array $selected_app): array {
        $link_id = (int) ($selected_app['link_id'] ?? 0);

        if(!$link_id) {
            return [
                'shop_contacts_30d' => 0,
                'whatsapp_contacts_30d' => 0,
                'product_clicks_30d' => 0,
                'funnel_registrations_30d' => 0,
                'ai_chat_leads_30d' => 0,
                'contact_captures_30d' => 0,
                'weighted_signal_score' => 0,
            ];
        }

        $period_30d_start = (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00');
        $apps = [
            $link_id => [
                'link_id' => $link_id,
                'user_id' => (int) ($selected_app['user_id'] ?? $this->user->user_id),
                'url' => (string) ($selected_app['url'] ?? ''),
                'public_url' => (string) ($selected_app['public_url'] ?? ''),
            ],
        ];
        $apps = $this->enrich_app_review_signal_snapshots($apps, $period_30d_start);

        return $apps[$link_id] ?? [
            'shop_contacts_30d' => 0,
            'whatsapp_contacts_30d' => 0,
            'product_clicks_30d' => 0,
            'funnel_registrations_30d' => 0,
            'ai_chat_leads_30d' => 0,
            'contact_captures_30d' => 0,
            'weighted_signal_score' => 0,
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
        $link_ids = [];

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
                'qualified_user_shop_contacts_30d' => (int) ($qualified_users[(int) ($row->user_id ?? 0)] ?? 0),
            ];
            $link_ids[] = $link_id;
        }

        if(empty($link_ids)) {
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

        $selected_performance = $this->get_app_review_performance_snapshot($selected_app);
        $peer_examples = [];

        foreach($benchmark_apps as $app) {
            if($this->compare_app_review_signal_rows($app, $selected_performance) <= 0) {
                continue;
            }

            $peer_examples[] = [
                'label' => (string) (($app['url'] ?? '') ?: '-'),
                'url' => (string) ($app['url'] ?? ''),
                'public_url' => (string) ($app['public_url'] ?? ''),
                'shop_contacts_30d' => (int) ($app['shop_contacts_30d'] ?? 0),
                'whatsapp_contacts_30d' => (int) ($app['whatsapp_contacts_30d'] ?? 0),
                'product_clicks_30d' => (int) ($app['product_clicks_30d'] ?? 0),
                'funnel_registrations_30d' => (int) ($app['funnel_registrations_30d'] ?? 0),
                'ai_chat_leads_30d' => (int) ($app['ai_chat_leads_30d'] ?? 0),
                'contact_captures_30d' => (int) ($app['contact_captures_30d'] ?? $this->get_app_review_contact_captures_30d($app)),
                'weighted_signal_score' => (int) ($app['weighted_signal_score'] ?? 0),
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

    private function get_app_review_quality_payload(array $selected_app, int $current_clicks_30d): array {
        $benchmark_payload = $this->get_app_review_benchmark_payload($selected_app);
        $benchmark = (array) ($benchmark_payload['benchmark'] ?? []);
        $peer_examples = (array) ($benchmark_payload['peer_examples'] ?? []);
        $performance = $this->get_app_review_performance_snapshot($selected_app);

        $quality_payload = $this->get_app_review_quality_payload_from_benchmark($performance, $benchmark);

        return [
            'score' => (int) ($quality_payload['score'] ?? 0),
            'level_key' => (string) ($quality_payload['level_key'] ?? 'foundation'),
            'level_label' => (string) ($quality_payload['level_label'] ?? l('ai_plan.app_review_quality_level.foundation')),
            'summary' => (string) ($quality_payload['summary'] ?? ''),
            'benchmark' => $benchmark,
            'performance' => $performance,
            'comparisons' => (array) ($quality_payload['comparisons'] ?? []),
            'peer_examples' => $peer_examples,
        ];
    }

    private function get_app_review_quality_payload_from_benchmark(array $performance, array $benchmark): array {
        $benchmark = !empty($benchmark) ? $benchmark : $this->get_default_app_review_benchmark();
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

        $comparisons = [
            [
                'label' => l('ai_plan.app_review_quality_metric_shop_contacts'),
                'current' => (int) ($performance['shop_contacts_30d'] ?? 0),
                'target' => (int) ($benchmark['shop_contacts_30d'] ?? 0),
                'format' => 'number',
            ],
            [
                'label' => l('ai_plan.app_review_quality_metric_whatsapp_contacts'),
                'current' => (int) ($performance['whatsapp_contacts_30d'] ?? 0),
                'target' => (int) ($benchmark['whatsapp_contacts_30d'] ?? 0),
                'format' => 'number',
            ],
            [
                'label' => l('ai_plan.app_review_quality_metric_product_clicks'),
                'current' => (int) ($performance['product_clicks_30d'] ?? 0),
                'target' => (int) ($benchmark['product_clicks_30d'] ?? 0),
                'format' => 'number',
            ],
            [
                'label' => l('ai_plan.app_review_quality_metric_funnel_contacts'),
                'current' => $performance_contact_captures,
                'target' => $benchmark_contact_captures,
                'format' => 'number',
            ],
        ];

        return [
            'score' => $score,
            'level_key' => $level_key,
            'level_label' => l('ai_plan.app_review_quality_level.' . $level_key),
            'summary' => l('ai_plan.app_review_quality_summary.' . $level_key),
            'comparisons' => $comparisons,
        ];
    }

    private function is_top_apps_plan_eligible(): bool {
        if(\Altum\Authentication::is_admin()) {
            return true;
        }

        $plan_id = (int) ($this->user->plan_id ?? 0);
        if($plan_id !== 5) {
            return false;
        }

        $expiration = trim((string) ($this->user->plan_expiration_date ?? ''));

        return $expiration === '' || $expiration === '0000-00-00 00:00:00' || $expiration >= get_date();
    }

    private function validate_app_review_response(array $review, ?array $selected_app = null): array {
        $headline = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['headline'] ?? $review['title'] ?? '', 140));
        $summary = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['summary'] ?? $review['overview'] ?? '', 600));
        $biggest_bottleneck = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['biggest_bottleneck'] ?? $review['main_problem'] ?? '', 220));
        $top_recommendation = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['top_recommendation'] ?? $review['power_move'] ?? '', 320));
        $weekly_focus = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['weekly_focus'] ?? $review['next_focus'] ?? '', 240));
        $priority_actions = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['priority_actions'] ?? $review['quick_wins'] ?? [], 4, 200));
        $ideal_block_order = $this->normalize_app_review_visible_list($this->normalize_ai_list($review['ideal_block_order'] ?? $review['recommended_block_order'] ?? [], 8, 120));
        $design_notes = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['design_notes'] ?? $review['visual_notes'] ?? $review['color_advice'] ?? [], 5, 220));
        $keep_doing = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['keep_doing'] ?? $review['strengths_to_keep'] ?? [], 4, 180));
        $first_move = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['first_move'] ?? $review['do_first'] ?? $top_recommendation, 180));
        $next_move = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['next_move'] ?? $review['do_next'] ?? $weekly_focus, 180));
        $do_not_touch = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['do_not_touch'] ?? $review['dont_break'] ?? ($keep_doing[0] ?? ''), 180));
        $funnel_blueprint = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['funnel_blueprint'] ?? $review['funnel_plan'] ?? $review['lead_flow'] ?? [], 4, 220));
        $color_palette = $this->normalize_app_review_color_palette($review['color_palette'] ?? $review['color_direction'] ?? $review['palette'] ?? []);
        $trust_builders = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['trust_builders'] ?? $review['trust_elements'] ?? $review['trust_plan'] ?? [], 5, 200));
        $primary_action_fallback = $selected_app ? $this->get_app_review_primary_action_block_snapshot($selected_app) : [];
        $theme_pack = $this->normalize_app_review_theme_pack($review['theme_pack'] ?? $review['design_system'] ?? [], $color_palette);
        $color_palette = $this->sync_app_review_color_palette_with_theme_pack($color_palette, $theme_pack);
        $primary_block_plan = $this->normalize_app_review_primary_block_plan($review['primary_block_plan'] ?? $review['main_block_plan'] ?? [], $primary_action_fallback);
        $block_patch_pack = $this->normalize_app_review_block_patch_pack($review['block_patch_pack'] ?? $review['block_overrides'] ?? []);
        $copy_suggestions = $this->normalize_app_review_copy_suggestions($review['copy_suggestions'] ?? $review['text_suggestions'] ?? []);
        $layout_actions = $this->normalize_app_review_layout_actions($review['layout_actions'] ?? $review['layout_plan'] ?? []);
        $missing_block_recommendations = $this->normalize_app_review_missing_block_recommendations($review['missing_block_recommendations'] ?? $review['recommended_missing_blocks'] ?? []);

        if($headline === '' || $summary === '' || $top_recommendation === '' || empty($priority_actions) || empty($ideal_block_order)) {
            throw new \Exception(l('ai_plan.app_review_error_invalid_response'));
        }

        return [
            'headline' => $headline,
            'summary' => $summary,
            'biggest_bottleneck' => $biggest_bottleneck,
            'top_recommendation' => $top_recommendation,
            'weekly_focus' => $weekly_focus,
            'priority_actions' => $priority_actions,
            'ideal_block_order' => $ideal_block_order,
            'design_notes' => $design_notes,
            'keep_doing' => $keep_doing,
            'funnel_blueprint' => $funnel_blueprint,
            'color_palette' => $color_palette,
            'trust_builders' => $trust_builders,
            'first_move' => $first_move,
            'next_move' => $next_move,
            'do_not_touch' => $do_not_touch,
            'theme_pack' => $theme_pack,
            'primary_block_plan' => $primary_block_plan,
            'block_patch_pack' => $block_patch_pack,
            'copy_suggestions' => $copy_suggestions,
            'layout_actions' => $layout_actions,
            'missing_block_recommendations' => $missing_block_recommendations,
        ];
    }

    private function build_emergency_app_review(array $values, array $analytics_payload, ?array $selected_app, int $current_clicks_30d, string $request_context = '', array $quality_payload = [], array $selected_app_block_attribution = []): array {
        $selected_app = $selected_app ?? $this->get_default_app_summary();
        $goal_type = $this->get_effective_app_review_goal_type($values, $request_context, $selected_app);
        $is_contact_goal = $this->is_contact_collection_goal($values, $goal_type);
        $core_block_policy = $this->get_fcc_core_block_policy($values, $goal_type, $request_context);
        $ordered_blocks = array_values(array_filter((array) ($selected_app['ordered_block_previews'] ?? []), 'is_array'));
        $position_signals = (array) ($selected_app['position_signals'] ?? []);
        $visual_profile = (array) ($selected_app['visual_profile'] ?? []);
        $design_diagnostic = $this->get_app_review_design_diagnostic($selected_app);
        $primary_snapshot = $this->get_app_review_primary_action_block_snapshot($selected_app);
        $selected_app_capabilities = (array) ($selected_app['conversion_capabilities'] ?? []);
        $capability_context = $this->get_app_review_capability_context($selected_app_capabilities, $values, $goal_type);
        $fcc_goal_system = $this->get_fcc_goal_system_payload($values, $goal_type);
        $fallback_theme_seed = $this->get_goal_first_fallback_theme_seed($values, $goal_type);
        $quality_summary = (string) ($quality_payload['summary'] ?? l('ai_plan.app_review_quality_summary.foundation'));
        $quality_level = (string) ($quality_payload['level_key'] ?? 'foundation');
        $app_name = (string) (($selected_app['name'] ?? '') ?: ($selected_app['url'] ?? 'aplikacija'));
        $app_block_attribution = !empty($selected_app_block_attribution)
            ? $this->normalize_app_review_block_attribution_payload($selected_app_block_attribution)
            : $this->get_app_review_block_attribution_payload((array) $selected_app);
        $first_focus_risk_block = (array) (($app_block_attribution['focus_risk_blocks'][0] ?? []));

        $get_first_block_by_types = function(array $types) use ($ordered_blocks): array {
            foreach($ordered_blocks as $preview) {
                $type = (string) ($preview['type'] ?? '');

                if(in_array($type, $types, true)) {
                    return $preview;
                }
            }

            return [];
        };

        $clean_label = function(string $value): string {
            return $this->normalize_app_review_visible_copy($this->sanitize_ai_string($value, 160));
        };

        $video_block = $get_first_block_by_types(['youtube', 'video', 'tiktok_video', 'vimeo', 'twitter_video', 'vk_video']);
        $discount_block = $get_first_block_by_types(['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo']);
        $business_offer_block = [];
        foreach($ordered_blocks as $preview) {
            $type = (string) ($preview['type'] ?? '');
            $label = trim((string) ($preview['label'] ?? ''));
            $location_url = trim((string) ($preview['location_url'] ?? ''));

            if($type !== 'link_forever_product') {
                continue;
            }

            if($this->app_review_text_has_any($label . ' ' . $location_url, ['start paket', 'start-paket', 'partner', 'suradnik', 'upis', 'prijava', 'registracija']) || str_contains($location_url, '/blog/start-paket')) {
                $business_offer_block = $preview;
                break;
            }
        }
        $shop_block = $discount_block ?: $get_first_block_by_types(['link_forever_shop', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo', 'link_discount']);
        $hero_visual_block = $get_first_block_by_types(['avatar', 'image']);
        $trust_block = $get_first_block_by_types(['avatar', 'image', 'heading', 'paragraph', 'markdown']);
        $trust_copy_block = $get_first_block_by_types(['paragraph', 'markdown']);
        $socials_block = $get_first_block_by_types(['socials']);
        $whatsapp_block = $get_first_block_by_types(['custom_html_whatsapp']);
        $chatbot_block = $get_first_block_by_types(['custom_html_chatbot', 'custom_html_chatbot_pets']);
        $funnel_block = $get_first_block_by_types(['lead_funnel']);
        $owner_name = $clean_label((string) ($this->user->name ?? ''));
        $identity_heading_block = [];
        $identity_heading_index = null;
        $normalize_compare = static function(string $value): string {
            return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(trim($value))) ?? '';
        };
        $owner_name_compare = $normalize_compare($owner_name);
        $has_owner_name_block = false;
        $core_funnel_label = (string) (($goal_type === 'shop' ? ($core_block_policy['funnel_label_shop'] ?? '') : ($core_block_policy['funnel_label_business'] ?? '')) ?: 'Prijavi se i saznaj više');
        $core_discount_label = (string) ($core_block_policy['discount_block_label'] ?? 'Pogledaj proizvode s popustom');
        $core_business_offer_label = (string) ($core_block_policy['business_offer_block_label'] ?? 'Postani Forever suradnik');

        foreach($ordered_blocks as $index => $preview) {
            $type = (string) ($preview['type'] ?? '');

            if($identity_heading_index === null && $type === 'heading') {
                $identity_heading_block = $preview;
                $identity_heading_index = $index;
            }

            if($owner_name_compare === '' || !in_array($type, ['heading', 'paragraph', 'markdown'], true)) {
                continue;
            }

            $label_compare = $normalize_compare((string) ($preview['label'] ?? ''));

            if($label_compare !== '' && (str_contains($label_compare, $owner_name_compare) || str_contains($owner_name_compare, $label_compare))) {
                $has_owner_name_block = true;
                break;
            }
        }

        $needs_owner_identity_anchor = $owner_name !== '' && !$has_owner_name_block && ($is_contact_goal || $goal_type === 'brand' || $goal_type === 'activation');

        $headline = match($goal_type) {
            'business' => 'Najveći pomak za ovu FCC aplikaciju sada je jasniji put do prijave',
            'shop' => 'Najveći pomak za ovu FCC aplikaciju sada je kraći i sigurniji put do proizvoda',
            'brand' => 'Najveći pomak za ovu FCC aplikaciju sada je više povjerenja na prvom ekranu',
            'activation' => 'Najveći pomak za ovu FCC aplikaciju sada je lakši prijelaz u razgovor',
            default => 'Najveći pomak za ovu FCC aplikaciju sada je jasniji prvi korak',
        };

        $biggest_bottleneck = match(true) {
            $needs_owner_identity_anchor => 'Na vrhu aplikacije nedostaje jasno ime i prezime osobe kojoj bi posjetitelj trebao vjerovati.',
            $is_contact_goal && empty($selected_app_capabilities['has_lead_funnel']) => 'Aplikacija još nema dovoljno jasan korak kojim ozbiljan interes postaje stvarna prijava ili kontakt.',
            !empty($first_focus_risk_block['label']) => 'Jedan od ranih blokova uzima fokus, a ne pomaže dovoljno prema glavnom cilju aplikacije.',
            ((int) ($selected_app['total_blocks'] ?? 0)) >= 9 => 'Na vrhu aplikacije osoba prebrzo dobiva previše izbora pa glavni smjer nije dovoljno jasan.',
            default => 'Prvi ekran još ne vodi dovoljno jasno prema jednom glavnom sljedećem koraku.',
        };

        $top_recommendation = match(true) {
            $needs_owner_identity_anchor => 'Odmah ispod avatara istakni puno ime i prezime kako bi osoba odmah znala kome vjeruje prije videa, Funnel-a ili linkova.',
            $is_contact_goal && empty($selected_app_capabilities['has_lead_funnel']) => 'Dodaj Funnel kao glavni korak odmah nakon sloja povjerenja i veži cijelu aplikaciju oko tog jednog ulaza.',
            $is_contact_goal && empty($selected_app_capabilities['has_video']) => 'Dodaj kratak video prije glavnog kontaktnog koraka kako bi osoba brže razumjela tko si i zašto vrijedi kliknuti dalje.',
            ((int) ($selected_app['total_blocks'] ?? 0)) >= 9 => 'Smanji broj ranih izbora i ostavi jedan glavni blok koji odmah vodi prema najvažnijem cilju aplikacije.',
            !empty($primary_snapshot['label']) => 'Pojačaj glavni blok "' . $clean_label((string) ($primary_snapshot['label'] ?? '')) . '" i smiri sve što mu prerano uzima pažnju.',
            default => 'Na vrhu aplikacije pojačaj povjerenje i odmah nakon toga pokaži jedan glavni korak bez dodatnog šuma.',
        };

        $weekly_focus = $needs_owner_identity_anchor
            ? 'Ovaj tjedan fokus neka bude jasan trust sloj na vrhu: avatar, ime i prezime, kratka poruka i tek onda glavni sljedeći korak.'
            : ($is_contact_goal
                ? 'Ovaj tjedan fokus neka bude povjerenje na vrhu i jedan jasan put do prijave ili razgovora.'
                : 'Ovaj tjedan fokus neka bude jasniji prvi ekran i jedan glavni sljedeći korak bez raspršivanja.');

        $priority_actions = array_values(array_filter(array_unique([
            $top_recommendation,
            $needs_owner_identity_anchor
                ? 'Odmah ispod avatara pokaži puno ime i prezime kako bi posjetitelj u prvim sekundama znao tko stoji iza aplikacije.'
                : '',
            empty($selected_app_capabilities['has_video'])
                ? 'Ako nemaš video, dodaj kratki video od 30 do 60 sekundi koji jednostavno objašnjava kome pomažeš i što osoba dobiva.'
                : 'Zadrži video blizu vrha aplikacije i neka odmah podržava glavni korak umjesto da stoji odvojeno bez jasnog nastavka.',
            ((int) ($selected_app['total_blocks'] ?? 0)) >= 9 || !empty($first_focus_risk_block['label'])
                ? 'Spusti niže ili privremeno ugasi blokove koji rano odvlače pažnju, posebno prije glavnog koraka.'
                : 'Ostavi rani dio aplikacije mirnim i čitljivim, bez previše paralelnih izbora.',
            $is_contact_goal && empty($selected_app_capabilities['has_whatsapp_contact'])
                ? 'Dodaj i WhatsApp blok kao jednostavan drugi put do razgovora za ljude koji ne žele odmah kroz prijavu.'
                : 'Ako koristiš WhatsApp, neka bude jasan nastavak nakon glavnog koraka, a ne novi izvor kaosa na vrhu.',
            empty($discount_block['block_id'])
                ? 'Na svakoj FCC aplikaciji zadrži i jasan blok za proizvode s popustom jer je to srce prodajnog dijela sustava.'
                : 'Blok za proizvode s popustom zadrži aktivnim i smjesti ga tako da podržava glavni cilj umjesto da mu konkurira.',
            empty($business_offer_block['block_id'])
                ? 'Dodaj i blok "Postani Forever suradnik" koji vodi na Start Paket jer je to ključni business korak u FCC sustavu.'
                : 'Blok "Postani Forever suradnik" neka ostane aktivan kao stalni business put prema Start Paketu.',
        ])));
        $priority_actions = array_slice($priority_actions, 0, 4);

        $ideal_block_order = $goal_type === 'shop'
            ? array_filter([
                $hero_visual_block ? $clean_label((string) ($hero_visual_block['label'] ?? '')) : 'Avatar ili stvarna fotografija',
                $owner_name !== '' ? $owner_name : 'Ime i prezime',
                $trust_copy_block ? $clean_label((string) ($trust_copy_block['label'] ?? '')) : 'Kratka trust poruka',
                $video_block ? $clean_label((string) ($video_block['label'] ?? '')) : 'Kratki video',
                $funnel_block ? $clean_label((string) ($funnel_block['label'] ?? '')) : $core_funnel_label,
                $discount_block ? $clean_label((string) ($discount_block['label'] ?? '')) : $core_discount_label,
                $whatsapp_block ? $clean_label((string) ($whatsapp_block['label'] ?? '')) : 'Pošalji poruku na WhatsApp',
                $business_offer_block ? $clean_label((string) ($business_offer_block['label'] ?? '')) : $core_business_offer_label,
            ])
            : array_filter([
                $hero_visual_block ? $clean_label((string) ($hero_visual_block['label'] ?? '')) : 'Avatar ili stvarna fotografija',
                $owner_name !== '' ? $owner_name : 'Ime i prezime',
                $trust_copy_block ? $clean_label((string) ($trust_copy_block['label'] ?? '')) : 'Kratka trust poruka',
                $video_block ? $clean_label((string) ($video_block['label'] ?? '')) : 'Kratki video',
                $funnel_block ? $clean_label((string) ($funnel_block['label'] ?? '')) : $core_funnel_label,
                $business_offer_block ? $clean_label((string) ($business_offer_block['label'] ?? '')) : $core_business_offer_label,
                $whatsapp_block ? $clean_label((string) ($whatsapp_block['label'] ?? '')) : 'Pošalji poruku na WhatsApp',
                $discount_block ? $clean_label((string) ($discount_block['label'] ?? '')) : $core_discount_label,
            ]);

        $ideal_block_order = array_values(array_slice(array_unique(array_filter(array_map(static fn($item) => trim((string) $item), $ideal_block_order))), 0, 8));

        if(count($ideal_block_order) < 5) {
            foreach($ordered_blocks as $preview) {
                $label = $clean_label((string) ($preview['label'] ?? ''));

                if($label === '' || in_array($label, $ideal_block_order, true)) {
                    continue;
                }

                $ideal_block_order[] = $label;

                if(count($ideal_block_order) >= 5) {
                    break;
                }
            }
        }

        $design_notes = array_values(array_filter(array_unique([
            'Koristi mirnu pozadinu i jedan jasni glavni naglasak kako bi prvi ekran djelovao ozbiljno i čitljivo.',
            $needs_owner_identity_anchor
                ? 'Odmah ispod avatara ili fotografije stavi puno ime i prezime jer to ubrzava prepoznavanje i povjerenje.'
                : '',
            !empty($design_diagnostic['visual_noise_level']) && $design_diagnostic['visual_noise_level'] !== 'calm'
                ? 'Trenutni vizual ima više naglasaka nego što treba, zato boje i blokove treba smiriti u jedan dosljedan sustav.'
                : 'Vizual neka ostane čist i miran, bez dodatnih boja i sjena koje ne pojačavaju glavni korak.',
            !empty($video_block)
                ? 'Video neka ostane blizu vrha i odmah objasni tko si, kome pomažeš i što osoba treba napraviti dalje.'
                : 'Ako dodaješ video, neka bude kratak i neka odmah gradi povjerenje prije glavnog koraka.',
            'Glavni blok mora imati najjači kontrast, a ostali blokovi trebaju biti smireniji i manje napadni.',
        ])));
        $design_notes = array_slice($design_notes, 0, 4);

        $keep_doing = array_values(array_filter(array_unique([
            $quality_summary,
            !empty($trust_block) ? 'Zadrži sloj povjerenja koji već postoji na vrhu i samo ga učini jasnijim.' : '',
            !empty($video_block) ? 'Zadrži video kao alat povjerenja ako vodi prema jasnom sljedećem koraku.' : '',
        ])));
        $keep_doing = array_slice($keep_doing, 0, 4);

        $funnel_blueprint = array_slice(array_values(array_filter(array_map(function($item) {
            return $this->sanitize_ai_string((string) $item, 220);
        }, (array) ($fcc_goal_system['funnel_blueprint'] ?? [])))), 0, 4);

        $trust_builders = array_slice(array_values(array_filter(array_unique(array_map(function($item) {
            return $this->sanitize_ai_string((string) $item, 180);
        }, (array) ($fcc_goal_system['preferred_trust_elements'] ?? []))))), 0, 4);

        if($needs_owner_identity_anchor) {
            array_unshift($trust_builders, 'Avatar ili fotografija uz puno ime i prezime daje brz osjećaj da iza aplikacije stoji stvarna osoba.');
            $trust_builders = array_slice(array_values(array_unique(array_filter($trust_builders))), 0, 4);
        }

        $theme_pack = [
            'name' => 'AI preporučena tema',
            'summary' => $quality_summary,
            'background_mode' => (string) ($fallback_theme_seed['background_mode'] ?? 'color'),
            'background_color' => (string) ($fallback_theme_seed['background_color'] ?? '#0F172A'),
            'gradient_start' => (string) ($fallback_theme_seed['gradient_start'] ?? '#0F172A'),
            'gradient_end' => (string) ($fallback_theme_seed['gradient_end'] ?? '#111827'),
            'gradient_style' => 'current_135deg',
            'heading_color' => (string) ($fallback_theme_seed['heading_color'] ?? '#F8FAFC'),
            'text_color' => (string) ($fallback_theme_seed['text_color'] ?? '#CBD5E1'),
            'primary_block_text' => (string) ($fallback_theme_seed['primary_block_text'] ?? '#FFFFFF'),
            'primary_block_background' => (string) ($fallback_theme_seed['primary_block_background'] ?? '#2563EB'),
            'primary_block_border' => (string) ($fallback_theme_seed['primary_block_border'] ?? '#1D4ED8'),
            'primary_block_shadow' => (string) ($fallback_theme_seed['primary_block_shadow'] ?? 'rgba(37,99,235,.24)'),
            'secondary_blocks_text' => (string) ($fallback_theme_seed['secondary_blocks_text'] ?? '#F8FAFC'),
            'secondary_blocks_background' => (string) ($fallback_theme_seed['secondary_blocks_background'] ?? '#111827'),
            'secondary_blocks_border' => (string) ($fallback_theme_seed['secondary_blocks_border'] ?? '#334155'),
            'secondary_blocks_shadow' => 'rgba(15,23,42,.12)',
            'font' => in_array((string) ($visual_profile['font'] ?? ''), array_keys((array) (settings()->links->biolinks_fonts ?? [])), true) ? (string) ($visual_profile['font'] ?? '') : '',
            'font_size' => 16,
            'width' => in_array((string) ($visual_profile['width'] ?? ''), ['6', '8', '10', '12'], true) ? (string) ($visual_profile['width'] ?? '') : '8',
            'block_spacing' => in_array((string) ($visual_profile['block_spacing'] ?? ''), ['1', '2', '3'], true) ? (string) ($visual_profile['block_spacing'] ?? '') : '2',
            'hover_animation' => 'smooth',
            'migration_note' => 'Prvo uskladi pozadinu, glavni blok i tekst na vrhu, a zatim smiri ostale blokove da podrže isti cilj.',
        ];
        $theme_pack = $this->enforce_goal_first_theme_pack_guardrails($theme_pack);

        $color_palette = [
            'background' => ($theme_pack['background_mode'] === 'gradient' ? $theme_pack['gradient_start'] : $theme_pack['background_color']) . ' daje mirnu pozadinu koja pomaže fokusu i ozbiljnijem dojmu.',
            'heading' => $theme_pack['heading_color'] . ' pojačava čitljivost naslova i prvi dojam povjerenja.',
            'text' => $theme_pack['text_color'] . ' drži glavni tekst jasnim i laganim za čitanje.',
            'primary_block_text' => $theme_pack['primary_block_text'] . ' daje čist kontrast za glavni blok i glavni klik.',
            'primary_block_background' => $theme_pack['primary_block_background'] . ' ističe glavni korak bez vizualnog kaosa.',
            'primary_block_border' => $theme_pack['primary_block_border'] . ' dodatno naglašava glavni blok i drži ga jasnim.',
            'primary_block_shadow' => $theme_pack['primary_block_shadow'] . ' daje blagi naglasak glavnom bloku bez teškog efekta.',
            'secondary_blocks_text' => $theme_pack['secondary_blocks_text'] . ' održava ostale blokove čitljivima i urednima.',
            'secondary_blocks_background' => $theme_pack['secondary_blocks_background'] . ' smiruje ostatak aplikacije kako glavni blok ne bi izgubio fokus.',
            'secondary_blocks_border' => $theme_pack['secondary_blocks_border'] . ' blago odvaja sekundarne blokove bez novog šuma.',
            'secondary_blocks_shadow' => $theme_pack['secondary_blocks_shadow'] . ' drži sekundarne blokove lagano odvojenima i nenametljivima.',
        ];

        if(!empty($core_block_policy['funnel_preferred_primary'])) {
            $primary_block_plan = !empty($funnel_block['block_id'])
                ? [
                    'block_id' => (int) ($funnel_block['block_id'] ?? 0),
                    'block_type' => 'lead_funnel',
                    'label' => $clean_label((string) ($funnel_block['label'] ?? $core_funnel_label)),
                    'reason' => 'Funnel treba biti glavni blok jer u FCC sustavu najjasnije vodi prema preporuci, prijavi ili ozbiljnom sljedećem koraku.',
                    'emphasis' => 'strong',
                    'apply_theme_emphasis' => true,
                ]
                : [
                    'block_id' => 0,
                    'block_type' => 'lead_funnel',
                    'label' => $core_funnel_label,
                    'reason' => 'Funnel treba biti glavni blok jer u FCC sustavu najjasnije vodi prema preporuci, prijavi ili ozbiljnom sljedećem koraku.',
                    'emphasis' => 'strong',
                    'apply_theme_emphasis' => true,
                ];
        } else {
            $primary_block_plan = !empty($primary_snapshot)
                ? [
                    'block_id' => (int) ($primary_snapshot['block_id'] ?? 0),
                    'block_type' => (string) ($primary_snapshot['type'] ?? ''),
                    'label' => $clean_label((string) ($primary_snapshot['label'] ?? '')),
                    'reason' => 'Ovaj blok je najbliži glavnom sljedećem koraku i treba dobiti najjači naglasak.',
                    'emphasis' => 'strong',
                    'apply_theme_emphasis' => true,
                ]
                : [
                    'block_id' => 0,
                    'block_type' => (string) ($shop_block['type'] ?? 'heading'),
                    'label' => $clean_label((string) ($shop_block['label'] ?? 'Glavni korak')) ?: 'Glavni korak',
                    'reason' => 'Za ovaj cilj aplikacija treba jedan jasan glavni blok koji odmah vodi prema sljedećem koraku.',
                    'emphasis' => 'strong',
                    'apply_theme_emphasis' => true,
                ];
        }

        $copy_suggestions = [];

        if($needs_owner_identity_anchor && !empty($identity_heading_block['block_id']) && $identity_heading_index !== null && $identity_heading_index <= 3) {
            $copy_suggestions[] = [
                'block_id' => (int) ($identity_heading_block['block_id'] ?? 0),
                'block_type' => 'heading',
                'role_key' => 'owner_identity',
                'field' => 'text',
                'label' => 'Ime i prezime',
                'value' => $owner_name,
                'reason' => 'Puno ime i prezime odmah ispod avatara najbrže gradi prepoznavanje i povjerenje.',
                'case_style' => 'title',
            ];
        }

        foreach($ordered_blocks as $preview) {
            $type = (string) ($preview['type'] ?? '');
            $block_id = (int) ($preview['block_id'] ?? 0);

            if($block_id <= 0) {
                continue;
            }

            if(in_array($type, ['youtube', 'vimeo', 'video'], true)) {
                $copy_suggestions[] = [
                    'block_id' => $block_id,
                    'block_type' => $type,
                    'field' => 'title',
                    'label' => 'Video naslov',
                    'value' => $is_contact_goal ? 'Pogledaj u 60 sekundi: je li ovo za tebe?' : 'Pogledaj kratko: što ovdje dobivaš?',
                    'reason' => 'Video treba odmah reći zašto vrijedi ostati i gledati dalje.',
                    'case_style' => 'sentence',
                ];
            } elseif($type === 'custom_html_whatsapp') {
                $copy_suggestions[] = [
                    'block_id' => $block_id,
                    'block_type' => $type,
                    'field' => 'title',
                    'label' => 'WhatsApp naslov',
                    'value' => 'Pošalji poruku na WhatsApp',
                    'reason' => 'Naslov treba odmah reći koji je sljedeći korak bez dodatnog objašnjavanja.',
                    'case_style' => 'sentence',
                ];
            } elseif(in_array($type, ['link_discount', 'link_forever_shop', 'link_forever_product', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo', 'link'], true)) {
                $contextual_link_value = $this->get_contextual_app_review_link_copy_value($type, (string) ($preview['label'] ?? ''), $goal_type);
                $copy_suggestions[] = [
                    'block_id' => $block_id,
                    'block_type' => $type,
                    'field' => 'name',
                    'label' => 'Naziv gumba',
                    'value' => $contextual_link_value,
                    'reason' => 'Naziv gumba treba biti jasan i čist, bez internog objašnjenja u zagradi.',
                    'case_style' => 'sentence',
                ];
            } elseif($type === 'lead_funnel') {
                $copy_suggestions[] = [
                    'block_id' => $block_id,
                    'block_type' => $type,
                    'field' => 'name',
                    'label' => 'Naziv Funnel gumba',
                    'value' => $core_funnel_label,
                    'reason' => 'Glavni kontaktni korak treba zvučati sigurno i jednostavno.',
                    'case_style' => 'sentence',
                ];
            }

            if(count($copy_suggestions) >= 4) {
                break;
            }
        }

        $layout_actions = [];
        $push_layout_action = function(string $action, array $block, string $why) use (&$layout_actions, $clean_label) {
            $block_id = (int) ($block['block_id'] ?? 0);

            if($block_id <= 0) {
                return;
            }

            foreach($layout_actions as $existing_action) {
                if((int) ($existing_action['block_id'] ?? 0) === $block_id) {
                    return;
                }
            }

            $layout_actions[] = [
                'action' => $action,
                'block_id' => $block_id,
                'block_type' => (string) ($block['type'] ?? ''),
                'label' => $clean_label((string) ($block['label'] ?? '')),
                'why' => $why,
            ];
        };

        if($is_contact_goal) {
            $push_layout_action('keep_top', $hero_visual_block, 'Profilna fotografija ili avatar trebaju ostati prvi trust signal na vrhu aplikacije.');
            $push_layout_action('keep_top', $identity_heading_block, 'Puno ime i prezime trebaju odmah slijediti iza avatara kako bi osoba odmah znala kome vjeruje.');
            $push_layout_action('keep_top', $trust_copy_block, 'Kratka trust poruka treba ostati iznad videa i glavnog koraka kako bi pojasnila kome je aplikacija namijenjena.');
            $push_layout_action('keep_top', $video_block, 'Video treba ostati visoko jer brzo gradi povjerenje prije prijave ili razgovora.');
            $push_layout_action('keep_after_primary', $whatsapp_block, 'WhatsApp treba biti odmah nakon glavnog koraka kao jednostavan rezervni put do razgovora.');
            $push_layout_action('keep_after_primary', $business_offer_block, 'Business korak prema Start Paketu treba ostati aktivan i odmah ispod glavnog koraka ili rezervnog kontakta.');
            $push_layout_action('keep_after_primary', $discount_block, 'Blok za proizvode s popustom treba ostati aktivan i jasno vidljiv niže od glavnog koraka.');
            $push_layout_action('keep_after_primary', $socials_block, 'Društvene mreže i kontakti trebaju doći nakon glavnog koraka, ne prije njega.');

            foreach($ordered_blocks as $preview) {
                $type = (string) ($preview['type'] ?? '');
                $label = trim((string) ($preview['label'] ?? ''));
                $is_product_or_shop_block = in_array($type, ['link_forever_shop', 'link_discount', 'link_forever_product'], true)
                    || ($type === 'link' && $this->app_review_text_has_any($label, ['proizvod', 'proizvodi', 'shop', 'webshop', 'popust']));

                if($is_product_or_shop_block) {
                    $push_layout_action('move_down', $preview, 'Prodajni i webshop blokovi trebaju doći nakon trust sloja i glavnog kontaktnog koraka.');
                }
            }

        } else {
            $push_layout_action('keep_top', $hero_visual_block, 'Profilna fotografija ili avatar trebaju ostati prvi trust signal na vrhu aplikacije.');
            $push_layout_action('keep_top', $identity_heading_block, 'Puno ime i prezime trebaju odmah slijediti iza avatara kako bi osoba odmah znala kome vjeruje.');
            $push_layout_action('keep_top', $trust_copy_block, 'Kratka trust poruka treba ostati iznad prodajnog dijela kako bi posjetitelj razumio kome vjeruje.');
            $push_layout_action('keep_top', $video_block, 'Video treba ostati visoko jer brzo objašnjava što osoba ovdje dobiva.');
            $push_layout_action('keep_after_primary', $discount_block, 'Blok za proizvode s popustom treba ostati aktivan kao glavni prodajni put.');
            $push_layout_action('keep_after_primary', $whatsapp_block, 'WhatsApp treba ostati odmah nakon glavnog koraka kao brza pomoć pri izboru.');
            $push_layout_action('keep_after_primary', $business_offer_block, 'Business korak prema Start Paketu treba ostati prisutan i na prodajnim aplikacijama, ali niže od glavnog prodajnog fokusa.');
        }

        if(!empty($first_focus_risk_block['block_id'])) {
            $push_layout_action('move_down', $first_focus_risk_block, 'Ovaj blok sada prerano uzima pažnju i treba doći kasnije u aplikaciji.');
        }

        $missing_block_recommendations = [];

        if($needs_owner_identity_anchor && (empty($identity_heading_block['block_id']) || $identity_heading_index === null || $identity_heading_index > 3)) {
            $missing_block_recommendations[] = [
                'block_type' => 'heading',
                'role_key' => 'owner_identity',
                'label' => $owner_name !== '' ? $owner_name : 'Ime i prezime',
                'why' => 'Puno ime i prezime odmah ispod avatara pomaže da osoba odmah zna kome vjeruje prije svih drugih koraka.',
                'priority' => 1,
                'insert_after_block_id' => max(0, (int) ($hero_visual_block['block_id'] ?? 0)),
                'insert_after_type' => (string) ($hero_visual_block['type'] ?? ''),
                'insert_after_label' => $clean_label((string) ($hero_visual_block['label'] ?? '')),
                'allow_existing_type' => true,
                'preferred_group' => 'start',
                'preferred_goal' => 'trust',
                'picker_search' => 'Naslov',
                'seed_settings' => [
                    'text' => $owner_name !== '' ? $owner_name : 'Ime i prezime',
                ],
            ];
        }

        if(!empty($core_block_policy['require_funnel']) && empty($selected_app_capabilities['has_lead_funnel'])) {
            $missing_block_recommendations[] = [
                'block_type' => 'lead_funnel',
                'role_key' => 'primary_funnel',
                'label' => $core_funnel_label,
                'why' => $goal_type === 'shop'
                    ? 'Funnel mora biti prisutan i na prodajnoj FCC aplikaciji jer preko njega mozes voditi preporuku proizvoda, izbor i ozbiljan sljedeci korak.'
                    : 'Ovdje nedostaje jasan glavni korak koji pretvara interes u stvarnu prijavu ili ostavljen kontakt.',
                'priority' => $needs_owner_identity_anchor ? 2 : 1,
                'insert_after_block_id' => max(0, (int) ($video_block['block_id'] ?? $trust_block['block_id'] ?? 0)),
                'insert_after_type' => (string) ($video_block['type'] ?? $trust_block['type'] ?? ''),
                'insert_after_label' => $clean_label((string) ($video_block['label'] ?? $trust_block['label'] ?? '')),
                'preferred_group' => 'sales',
                'preferred_goal' => 'lead_capture',
                'picker_search' => 'Funnel',
                'seed_settings' => [
                    'name' => $core_funnel_label,
                    'popup_title' => $goal_type === 'shop' ? 'Zatraži preporuku i sljedeći korak' : 'Prijava za poslovnu suradnju',
                    'popup_subtitle' => $goal_type === 'shop' ? 'Ostavi podatke i dobij najjednostavniji sljedeći korak za pravu preporuku proizvoda.' : 'Ostavi podatke i dobij sljedeći korak bez lutanja.',
                    'thank_you_title' => 'Prijava je zaprimljena',
                    'thank_you_text' => 'Uskoro dobivaš sljedeći korak i jasniji pregled što dalje.',
                    'thank_you_button_text' => 'Nastavi dalje',
                ],
            ];
        }

        if($is_contact_goal && empty($selected_app_capabilities['has_video'])) {
            $missing_block_recommendations[] = [
                'block_type' => 'youtube',
                'role_key' => 'trust_video',
                'label' => 'Kratki video',
                'why' => 'Kratki video prije prijave ili WhatsApp poruke brzo objašnjava tko si, kome pomažeš i zašto vrijedi ostati.',
                'priority' => $needs_owner_identity_anchor ? 2 : 1,
                'insert_after_block_id' => max(0, (int) ($trust_copy_block['block_id'] ?? $hero_visual_block['block_id'] ?? 0)),
                'insert_after_type' => (string) ($trust_copy_block['type'] ?? $hero_visual_block['type'] ?? ''),
                'insert_after_label' => $clean_label((string) ($trust_copy_block['label'] ?? $hero_visual_block['label'] ?? '')),
                'preferred_group' => 'start',
                'preferred_goal' => 'trust',
                'picker_search' => l('link.biolink.blocks.youtube'),
                'seed_settings' => [
                    'title' => 'Pogledaj u 60 sekundi: je li ovo za tebe?',
                ],
            ];
        }

        if(!empty($core_block_policy['require_whatsapp_backup']) && empty($selected_app_capabilities['has_whatsapp_contact'])) {
            $missing_block_recommendations[] = [
                'block_type' => 'custom_html_whatsapp',
                'role_key' => 'whatsapp_backup',
                'label' => 'WhatsApp poruka',
                'why' => 'Drugi jednostavan put do razgovora pomaže ljudima koji ne žele odmah kroz prijavu.',
                'priority' => 2,
                'insert_after_block_id' => max(0, (int) ($funnel_block['block_id'] ?? 0)),
                'insert_after_type' => (string) ($funnel_block['type'] ?? ''),
                'insert_after_label' => $clean_label((string) ($funnel_block['label'] ?? '')),
                'preferred_group' => 'contacts',
                'preferred_goal' => 'lead_capture',
                'picker_search' => 'WhatsApp',
                'seed_settings' => [
                    'title' => 'Pošalji poruku na WhatsApp',
                    'message' => 'Javi se ako želiš kratko pojašnjenje prije sljedećeg koraka.',
                ],
            ];
        }

        if(!empty($core_block_policy['require_discount_offer']) && empty($discount_block['block_id'])) {
            $missing_block_recommendations[] = [
                'block_type' => 'link_discount',
                'role_key' => 'core_discount_offer',
                'label' => $core_discount_label,
                'why' => 'Blok za proizvode s popustom mora biti prisutan na svakoj FCC aplikaciji jer je to srce prodajnog dijela sustava.',
                'priority' => $goal_type === 'shop' ? 2 : 4,
                'insert_after_block_id' => max(0, (int) ($funnel_block['block_id'] ?? $whatsapp_block['block_id'] ?? 0)),
                'insert_after_type' => (string) ($funnel_block['type'] ?? $whatsapp_block['type'] ?? ''),
                'insert_after_label' => $clean_label((string) ($funnel_block['label'] ?? $whatsapp_block['label'] ?? '')),
                'preferred_group' => 'forever',
                'preferred_goal' => 'product_recommendation',
                'picker_search' => l('link.biolink.blocks.link_discount'),
                'seed_settings' => [
                    'name' => $core_discount_label,
                    'apply_to_all_products' => 1,
                ],
            ];
        }

        if(!empty($core_block_policy['require_business_start_paket_offer']) && empty($business_offer_block['block_id'])) {
            $missing_block_recommendations[] = [
                'block_type' => 'link_forever_product',
                'role_key' => 'core_business_offer',
                'label' => $core_business_offer_label,
                'why' => 'Blok "Postani Forever suradnik" mora biti prisutan na svakoj FCC aplikaciji jer vodi na Start Paket i cuva business put kroz referral sustav.',
                'priority' => $goal_type === 'shop' ? 4 : 2,
                'insert_after_block_id' => max(0, (int) ($whatsapp_block['block_id'] ?? $funnel_block['block_id'] ?? 0)),
                'insert_after_type' => (string) ($whatsapp_block['type'] ?? $funnel_block['type'] ?? ''),
                'insert_after_label' => $clean_label((string) ($whatsapp_block['label'] ?? $funnel_block['label'] ?? '')),
                'preferred_group' => 'forever',
                'preferred_goal' => 'lead_capture',
                'picker_search' => l('link.biolink.blocks.link_forever_product'),
                'seed_settings' => $this->get_fcc_start_paket_seed_settings(),
            ];
        }

        if(empty($selected_app_capabilities['has_chatbot'])) {
            $chatbot_context = implode(' ', array_filter([
                (string) ($values['main_offer'] ?? ''),
                (string) ($values['priority_offer'] ?? ''),
                (string) ($values['notes'] ?? ''),
                $app_name,
            ]));
            $preferred_chatbot_type = $this->app_review_text_has_any($chatbot_context, ['ljubim', 'ljubimac', 'ljubimci', 'pas', 'psi', 'mack', 'mačk', 'pet', 'pets', 'animal'])
                ? 'custom_html_chatbot_pets'
                : 'custom_html_chatbot';

            $missing_block_recommendations[] = [
                'block_type' => $preferred_chatbot_type,
                'role_key' => 'floating_ai_assistant',
                'label' => $preferred_chatbot_type === 'custom_html_chatbot_pets' ? 'AI savjetnik za ljubimce' : 'AI savjetnik za preporuku proizvoda',
                'why' => 'AI savjetnik je jedan od glavnih FCC Pro benefita i treba biti aktivan kao plutajuci popup alat koji pomaže oko preporuke proizvoda i vodi prema pravim linkovima.',
                'priority' => 1,
                'insert_after_block_id' => max(0, (int) ($socials_block['block_id'] ?? $whatsapp_block['block_id'] ?? $funnel_block['block_id'] ?? 0)),
                'insert_after_type' => (string) ($socials_block['type'] ?? $whatsapp_block['type'] ?? $funnel_block['type'] ?? ''),
                'insert_after_label' => $clean_label((string) ($socials_block['label'] ?? $whatsapp_block['label'] ?? $funnel_block['label'] ?? '')),
                'preferred_group' => 'forever',
                'preferred_goal' => 'product_recommendation',
                'picker_search' => l('link.biolink.blocks.' . $preferred_chatbot_type),
                'seed_settings' => [],
            ];
        }

        $visible_missing_block_recommendations = array_slice($missing_block_recommendations, 0, 6);
        $has_visible_chatbot_recommendation = false;
        foreach($visible_missing_block_recommendations as $item) {
            if(in_array((string) ($item['block_type'] ?? ''), ['custom_html_chatbot', 'custom_html_chatbot_pets'], true)) {
                $has_visible_chatbot_recommendation = true;
                break;
            }
        }

        if(!$has_visible_chatbot_recommendation) {
            foreach($missing_block_recommendations as $item) {
                if(!in_array((string) ($item['block_type'] ?? ''), ['custom_html_chatbot', 'custom_html_chatbot_pets'], true)) {
                    continue;
                }

                array_pop($visible_missing_block_recommendations);
                $visible_missing_block_recommendations[] = $item;
                break;
            }
        }

        return [
            'headline' => $headline,
            'summary' => $quality_summary . ' Za ' . $app_name . ' sada najveći pomak dolazi iz jasnijeg vrha, mirnijeg rasporeda i jednog glavnog koraka.',
            'biggest_bottleneck' => $biggest_bottleneck,
            'top_recommendation' => $top_recommendation,
            'weekly_focus' => $weekly_focus,
            'first_move' => $priority_actions[0] ?? $top_recommendation,
            'next_move' => $priority_actions[1] ?? 'Nakon glavne promjene pojačaj sljedeći blok koji podržava isti cilj.',
            'do_not_touch' => !empty($video_block) ? 'Ne uklanjaj sloj povjerenja na vrhu, samo ga pojednostavni.' : 'Ne dodaj nove rane distrakcije prije glavnog koraka.',
            'priority_actions' => $priority_actions,
            'ideal_block_order' => $ideal_block_order,
            'design_notes' => $design_notes,
            'keep_doing' => $keep_doing,
            'funnel_blueprint' => $funnel_blueprint,
            'color_palette' => $color_palette,
            'trust_builders' => $trust_builders,
            'theme_pack' => $theme_pack,
            'fcc_core_block_policy' => $core_block_policy,
            'primary_block_plan' => $primary_block_plan,
            'block_patch_pack' => [],
            'copy_suggestions' => $copy_suggestions,
            'layout_actions' => $layout_actions,
            'missing_block_recommendations' => array_values($visible_missing_block_recommendations),
        ];
    }

    private function generate_app_review(array $values, array $analytics_payload, array $app_structure_payload, int $current_clicks_30d, string $request_context = '', ?array $selected_app = null, ?array $previous_review = null, array $coach_context = []): array {
        $credentials = $this->get_ai_credentials();

        if($credentials['api_key'] === '') {
            if($credentials['needs_personal_key']) {
                throw new \Exception(sprintf(l('account_preferences.error_message.aix.openai_api_key'), '<a href="' . url('account-preferences') . '"><strong>' . l('account_preferences.menu') . '</strong></a>'));
            }

            throw new \Exception(l('ai_plan.ai_error_missing_api_key'));
        }

        $selected_app = $selected_app ?? $this->get_selected_app($app_structure_payload) ?? $this->get_default_app_summary();
        $selected_app_block_attribution = $this->get_app_review_block_attribution_payload($selected_app);
        $review_generated_at = get_date();
        $ai_input = $this->build_app_review_ai_input($values, $analytics_payload, $app_structure_payload, $current_clicks_30d, $request_context, $selected_app, $selected_app_block_attribution, $previous_review, $coach_context);
        $ai_input = $this->sanitize_utf8_for_json($ai_input);
        $quality_payload = $this->get_app_review_quality_payload($selected_app, $current_clicks_30d);
        $selected_link_additional = $this->get_link_additional_by_id((int) ($selected_app['link_id'] ?? 0));
        $evolution_memory = $this->normalize_app_review_evolution_memory($selected_link_additional['fcc_ai_evolution_memory'] ?? []);
        $evolution_payload = $this->get_app_review_evolution_payload((array) ($quality_payload['performance'] ?? []), $previous_review, $evolution_memory, $review_generated_at, $selected_app_block_attribution);
        $evolution_payload = $this->sanitize_utf8_for_json($evolution_payload);
        $current_app_state_payload = $this->get_ai_bundle_freshness_payload(
            $selected_link_additional,
            (string) (($selected_app['last_datetime'] ?? '') ?: ($selected_app['datetime'] ?? '')),
            (string) ($previous_review['generated_at'] ?? '')
        );
        $current_app_state_payload = $this->sanitize_utf8_for_json($current_app_state_payload);
        $supports_image_input = $this->model_supports_image_input((string) ($credentials['model'] ?? ''));

        if($supports_image_input) {
            $live_visual_payload = $this->capture_app_review_live_visual_payload((string) ($ai_input['selected_app']['public_url'] ?? ''));

            if((string) ($live_visual_payload['scope'] ?? 'none') !== 'none') {
                $ai_input['selected_app']['visual_context']['scope'] = (string) ($live_visual_payload['scope'] ?? 'rendered_live_app');
                $ai_input['selected_app']['visual_context']['visual_type'] = (string) ($live_visual_payload['visual_type'] ?? 'rendered_live_app');
                $ai_input['selected_app']['visual_context']['primary_visual_url'] = (string) ($live_visual_payload['primary_visual_url'] ?? '');
                $ai_input['selected_app']['visual_context']['visual_segments'] = (array) ($live_visual_payload['visual_segments'] ?? $this->get_default_app_review_visual_segments());
            }
        }

        $selected_visual_url = (string) ($ai_input['selected_app']['visual_context']['primary_visual_url'] ?? '');
        $selected_visual_scope = (string) ($ai_input['selected_app']['visual_context']['scope'] ?? 'none');
        $selected_visual_segments = (array) ($ai_input['selected_app']['visual_context']['visual_segments'] ?? []);
        $user_prompt = implode("\n\n", [
            !empty($evolution_payload['has_previous_review'])
                ? 'Na temelju cilja korisnika, postojece strukture aplikacije, vizualnog ulaza ako postoji, prethodne analize iste glavne FCC aplikacije, evolution memorije i novih mjernih podataka napravi nadogradnju analize iste aplikacije.'
                : 'Na temelju cilja korisnika, postojece strukture aplikacije, vizualnog ulaza ako postoji i link analitike napravi pocetnu AI analizu glavne FCC aplikacije.',
            'Vrati samo JSON s kljucevima: headline, summary, biggest_bottleneck, top_recommendation, weekly_focus, first_move, next_move, do_not_touch, priority_actions, ideal_block_order, design_notes, keep_doing, funnel_blueprint, color_palette, trust_builders, theme_pack, primary_block_plan, block_patch_pack, copy_suggestions, layout_actions, missing_block_recommendations.',
            'Pravila:',
            '- Ovo je faza 1 FCC AI sustava. Sve preporuke moraju biti vezane samo uz glavnu, zasticenu FCC aplikaciju korisnika.',
            '- Pisi vrlo jednostavno, toplo i potpuno. Sve mora razumjeti pocetnik koji nema internet ili marketinsko znanje.',
            '- Nemoj zvucati kao programer, dizajner ili analiticar. Nemoj koristiti strucne izraze koje pocetnik ne razumije.',
            '- Smijes koristiti rijec Funnel jer je to stvarni naziv gumba i funkcije u FCC-u.',
            '- Svako polje mora biti kratko, jasno i dovrseno. Bez polurecenica i bez rezanja misli na pola.',
            '- biggest_bottleneck, top_recommendation i weekly_focus neka budu najvise 2 kratke recenice.',
            '- first_move, next_move i do_not_touch neka budu vrlo kratki, motivirajuci i odmah primjenjivi.',
            '- summary neka bude kratak pregled od najvise 4 recenice.',
            '- priority_actions mora imati 3 do 4 vrlo konkretne preporuke koje odmah govore sto promijeniti i zasto ce to pomoci.',
            '- ideal_block_order mora imati 5 do 8 kratkih stavki i mora slijediti najbolji red za cilj korisnika.',
            '- ideal_block_order mora biti u skladu sa stvarnim finalnim rasporedom. Ako neki blok ostaje aktivan i ima jasnu ulogu u rezultatu, povjerenju ili prijelazu, nemoj ga mentalno izbaciti iz plana dok ga istovremeno zadrzavas kroz druge preporuke.',
            '- Ako predlazes da se postojeci blok privremeno makne iz fokusa, to mora biti jasno objasnjeno kroz layout_actions reason ili through block_attribution signal, kako bi admin i korisnik razumjeli zasto se to radi narednih 7 dana.',
            '- design_notes mora imati 2 do 5 konkretnih savjeta za boje, tekst blokove, video, kontrast i vizualni dojam.',
            '- color_palette mora biti objekt s kljucevima: background, heading, text, primary_block_text, primary_block_background, primary_block_border, primary_block_shadow, secondary_blocks_text, secondary_blocks_background, secondary_blocks_border, secondary_blocks_shadow.',
            '- Svaka vrijednost unutar color_palette mora biti jedna kratka recenica s konkretnim hex kodom i kratkim razlogom zasto ta boja odgovara cilju i dojmu aplikacije.',
            '- theme_pack mora biti strojno citljiv objekt za editor, bez recenica u bojama. Koristi kljuceve: name, summary, background_mode, background_color, gradient_start, gradient_end, gradient_style, heading_color, text_color, primary_block_text, primary_block_background, primary_block_border, primary_block_shadow, secondary_blocks_text, secondary_blocks_background, secondary_blocks_border, secondary_blocks_shadow, font, font_size, width, block_spacing, hover_animation, migration_note.',
            '- U theme_pack bojama vrati samo stvarne CSS vrijednosti. Za boje koristi hex. Za shadow smijes koristiti rgba. gradient_style uvijek vrati kao "current_135deg" ako je mode = gradient.',
            '- Ako je background_mode = color, popuni background_color. Ako je background_mode = gradient, popuni gradient_start i gradient_end prema trenutnom FCC sustavu pocetak/kraj gradijenta, ne top/bottom.',
            '- Uvijek postuj FCC core block policy iz inputa. Svaka glavna FCC aplikacija mora imati aktivan blok za proizvode s popustom, blok "Postani Forever suradnik" koji vodi na Start Paket, Funnel i WhatsApp rezervni put. AI smije odluciti redoslijed i tekst, ali ne smije izbaciti te blokove iz plana.',
            '- Ako je goal_type shop ili request_context jasno govori o prodaji proizvoda, slozi aplikaciju product-first: trust sloj, video po potrebi, Funnel kao glavni blok, zatim proizvodi s popustom, zatim WhatsApp, a business Start Paket niže kao dodatni put.',
            '- Ako je goal_type business, recruitment ili partnership, slozi aplikaciju business-first: trust sloj, video po potrebi, Funnel kao glavni blok, zatim Start Paket / Postani Forever suradnik, zatim WhatsApp, a blok za proizvode s popustom ostavi aktivan malo niže.',
            '- Za business/start paket put koristi link_forever_product blok sa seed_settings koji ciljaju Start Paket. Taj blok treba imati jasan naziv poput "Postani Forever suradnik".',
            '- primary_block_plan mora biti objekt s kljucevima: block_id, block_type, label, reason, emphasis, apply_theme_emphasis. Ako ne znas block_id, vrati 0 ali vrati type i label.',
            '- Ako vracas font u theme_pack, koristi samo vrijednosti iz editor_theme_capabilities. font_size, width, block_spacing i hover_animation takoder moraju pratiti samo dopustene editor vrijednosti.',
            '- copy_suggestions mora biti lista kratkih AI prijedloga za naslove i gumbe samo za blokove koji vec postoje i koji se stvarno mogu urediti kroz editor. Svaka stavka neka ima: block_id, block_type, field, label, value, reason, case_style. Po potrebi smijes dodati role_key ako preporuka ima jasnu ulogu poput owner_identity, primary_funnel ili whatsapp_backup.',
            '- Za copy_suggestions field koristi samo postojece editor fieldove: name, title, button_text, description, text, message, popup_title, popup_subtitle, thank_you_title, thank_you_text ili thank_you_button_text.',
            '- Ako je blok partner, suradnja, upis, registracija, webshop, popust ili proizvod, naziv mora jasno zadrzati tu stvarnu namjenu. Nemoj davati genericke nazive poput "Saznaj vise i otvori sljedeci korak".',
            '- Interna strategijska oznaka kao (sporedno), (rezervno), (zadnje), (primarno), (kao rezervni put) i slicne napomene nikad ne smiju biti dio vidljivog naziva, naslova, gumba, ideal_block_order stavke, label polja, value polja ni seed_settings vrijednosti.',
            '- Ako zelis objasniti da je nesto sporedno, rezervno ili sekundarno, to smijes napisati samo u reason ili why, nikada u label, value, name, title, button_text, text, message, insert_after_label ni seed_settings.',
            '- Nemoj stavljati copy_suggestions na custom_html, code, iframe, divider, loading ni slicne napredne ili strukturne blokove.',
            '- layout_actions mora biti lista kratkih odluka za raspored blokova. Svaka stavka neka ima: action, block_id, block_type, label, why.',
            '- missing_block_recommendations koristi samo za blokove koji trenutno ne postoje u aplikaciji ili kada postoji isti tehnicki tip bloka, ali nedostaje bas ta uloga na pravom mjestu. Svaka stavka neka ima: block_type, label, why, priority, insert_after_block_id, insert_after_type, insert_after_label, seed_settings. Po potrebi smijes dodati role_key i allow_existing_type=true.',
            '- U missing_block_recommendations seed_settings koristi samo ova polja ako su stvarno korisna: name, title, text, message, button_text, description, popup_title, popup_subtitle, thank_you_title, thank_you_text, thank_you_button_text, open_mode, location_url, product_blog_post_id, product_translation_key, product_language_mode, product_language_code, product_fallback_language_code, product_image_url, apply_to_all_products.',
            '- Ako preporucujes video kao missing_block_recommendation, koristi youtube ili vimeo blok, nikad genericki video. Ako nisi siguran, koristi youtube.',
            '- block_patch_pack koristi samo kad poseban blok, posebno Funnel ili nestandardni blok, treba vlastite boje ili tekstualni naglasak. Svaka stavka neka ima: block_id, block_type, reason, settings.',
            '- trust_builders mora imati 3 do 5 kratkih savjeta kako aplikacija moze djelovati sigurnije, ozbiljnije i uvjerljivije.',
            '- funnel_blueprint mora imati 3 do 4 kratke stavke i jasno reci kako sloziti Funnel ako Funnel ima smisla za cilj korisnika.',
            '- keep_doing neka bude kratko i ohrabrujuce: sto vec radi dobro i ne treba kvariti.',
            '- Ovo nije opci poslovni plan, nego pregled kako poboljsati FCC aplikaciju za cilj koji je korisnik upisao.',
            '- Nemoj korisniku dati 20 opcija. Jedna glavna preporuka mora biti najjaca i jasno odvojena.',
            '- Nemoj preporucivati Dodaj na pocetni zaslon.',
            '- Nemoj preporucivati Save Contact, Contact Collector ni Email Collector kao glavno rjesenje.',
            '- Nemoj davati savjete o velicini gumba jer su gumbi u sustavu vec standardizirani.',
            '- Chatbot ili AI savjetnik nije klasicni gumb u redoslijedu blokova. On se pojavljuje kao mala ikonica u donjem desnom kutu i otvara popup preko ekrana.',
            '- Ako aplikacija ima chatbot ili AI savjetnik za ljude ili ljubimce, tretiraj ga kao neutralan pomocni sloj koji ne smeta fokusu i ne racunaj ga kao problem u prioritetu glavnih gumba ili redoslijedu blokova.',
            '- Ako aplikacija vec ima chatbot, nikad nemoj predlagati gasenje, skrivanje, uklanjanje ni spustanje u smislu da nestane iz aplikacije. Taj blok mora ostati aktivan.',
            '- Ako aplikacija nema chatbot ili AI savjetnik, dodaj ga u missing_block_recommendations kao floating popup benefit. Koristi custom_html_chatbot za opci proizvodni savjetnik, a custom_html_chatbot_pets za pet ili animal kontekst.',
            '- Chatbot mozes spomenuti kao koristan dodatak za preporuku proizvoda i usmjeravanje na linkove s popustom, ali ga nemoj isticati kao glavnu prepreku niti kao glavni prvi korak.',
            '- Kad predlazes kontakt, koristi formulaciju "pošalji poruku na WhatsApp" ili "WhatsApp poruka".',
            '- Ako je cilj skupljanje kontakata, suradnja ili prijava ljudi, Funnel tretiraj kao glavni i najbolji alat za prvi ozbiljan korak, ali ne automatski kao prvi vidljivi blok na vrhu.',
            '- Ako je cilj skupljanje kontakata i aplikacija nema Funnel, jedna od prvih preporuka mora biti ugradnja Funnel-a.',
            '- Ako preporucujes Funnel, nemoj predlagati pitanja. Funnel u FCC-u moze imati video, ime, email, broj telefona i thank you stranicu.',
            '- Ako preporucujes Funnel, provjeri ima li aplikacija video. Ako ga nema, preporuci kratak video prije ili unutar Funnel-a.',
            '- Za business, recruitment i slicne trust-first ciljeve razlikuj prvi sloj povjerenja od prvog konverzijskog koraka. Na vrhu su cesto ucinkovitiji avatar ili stvarna fotografija, puno ime i prezime, kratki trust tekst i po potrebi kratak video. Funnel tada ide odmah nakon tog trust sloja kao prvi ozbiljan korak.',
            '- Nemoj stavljati Lead Funnel kao prvu vidljivu stvar na vrhu ako prethodno nema dovoljno povjerenja, osim ako signal i kontekst jasno pokazuju da je promet vec topao i osoba korisnika vec dobro poznaje.',
            '- Ako user.name postoji i aplikacija predstavlja osobu, puno ime i prezime tretiraj kao obavezni dio trust sloja odmah nakon avatara ili stvarne fotografije.',
            '- Ako na vrhu aplikacije nema jasnog bloka s punim imenom i prezimenom vlasnika, preporuci heading blok s imenom prije trust teksta, videa i Funnel-a.',
            '- Ako ideal_block_order slazes za osobni brand, suradnju ili prijavu, u pravilu prvo razmisli o ovome: avatar ili stvarna fotografija, puno ime i prezime, kratki trust tekst, zatim video ili Funnel. Tek nakon toga idu rezervni linkovi, socials i shop.',
            '- U funnel_blueprint smijes preporuciti PDF knjigu, plan, mini vodič, edukaciju ili preusmjeravanje na drugu FCC aplikaciju kao poklon ili sljedeci korak.',
            '- Ako je cilj regrutacija, Funnel ili thank you stranica mogu voditi na edukativnu FCC aplikaciju za suradnju.',
            '- Ako je cilj proizvod, Funnel moze voditi na preporuku proizvoda, plan prehrane, vjezbe, detox ili korisni poklon prije kupnje.',
            '- Ako aplikacija vec ima Funnel ili WhatsApp, reci jesu li dovoljno vidljivi i jesu li pravi prvi korak.',
            '- Ako postoje previse jednaki glavni smjerovi na vrhu, jasno reci koji jedan mora biti glavni prvi korak.',
            '- Ako su prodajni linkovi previsoko, a cilj je kontakt ili suradnja, reci da trebaju ici nakon povjerenja, videa ili Funnel-a.',
            '- U obzir uzmi goal_context: publiku, glavni cilj, prioritetnu ponudu, stil komunikacije i biljeske iz upitnika. Preporuke moraju biti uskladjene s tim identitetom.',
            '- Ako goal_context.visual_tone_preference postoji, tretiraj ga kao opis željenog dojma, ne kao točan HEX zahtjev. Ako ne postoji, samostalno odaberi najučinkovitiju paletu za cilj, publiku i ponudu.',
            '- Za dizajn kreni goal-first. Prvo odredi sto bi najbolje pomoglo rezultatu za ovaj cilj, publiku i ponudu. Tek nakon toga pogledaj trenutni dizajn da das migration_note i da izbjegnes nepotreban kaos pri prijelazu.',
            '- Nemoj cuvati lose, krestave ili nepovezane postojece boje samo zato sto vec postoje. Ako trenutni dizajn odmaže fokusu, predlozi jasan reset prema boljem smjeru.',
            '- design_policy i visual_context.design_diagnostic su glavni signal kako tretirati trenutni izgled. Ako recommendation_bias kaze reset ili soft_reset, nemoj kopirati postojece boje ni isti vizualni smjer.',
            '- Koristi fcc_block_catalog da procijenis koji blokovi imaju smisla za cilj, koji smetaju fokusu i sto bi vrijedilo dodati, pomaknuti ili ugasiti.',
            '- selected_app.block_attribution pokazuje koji blokovi trenutno donose signal, a koji su visoko postavljeni bez rezultata. To koristi kao stvarni dokaz sto pomaze, a sto odmaze.',
            '- Ako selected_app.block_attribution pokazuje da blok ima status high_signal ili contributing, nemoj predlagati hide_for_now ni consider_remove za taj blok. Takav blok smijes zadrzati, doraditi ili pomaknuti, ali ne gasiti.',
            '- Ako blok nema jak klik signal, ali ima trust ili prijelaznu ulogu poput videa, imena vlasnika, uvodnog trust teksta, WhatsApp rezervnog puta ili drustvenog dokaza, radije ga zadrzi i pomakni nego da ga gasis odmah na prvu.',
            '- Ako je blok focus_risk ili critical_focus_risk, radije prvo predlozi move_down i jasniji fokus. Gasenje ili skrivanje koristi samo za ocite viskove bez signala.',
            '- Ako predlazes tekst blokove, reci jednostavno sto trebaju poruciti: kome je aplikacija namijenjena, sto osoba dobiva i koji je sljedeci korak.',
            '- Glavni blok u color_palette tretiraj kao prvi i najvazniji prodajni ili kontaktni blok koji vodi osobu na sljedeci korak.',
            '- Ostale blokove u color_palette tretiraj kao ostatak blokova koji dolaze poslije glavnog koraka i moraju ostati citljivi, mirni i uskladjeni.',
            '- Za boje koristi konkretne hex kodove i kratko objasni zasto te boje pomazu bas ovom cilju, ovom prioritetu i ovom tonu brenda.',
            '- fcc_goal_system.design_direction daje smjer dojma i guardrailse, ali ne daje unaprijed zadane HEX boje. Boje biraj samostalno.',
            '- Za shop ili product-first aplikacije nemoj automatski zavrsiti na ravnoj bijeloj ili gotovo potpuno bijeloj pozadini. Ako korisnik nije izričito trazio clean ili minimal izgled, koristi toniranu bazu ili nenapadni gradijent koji djeluje profesionalnije i pomaže fokusu.',
            '- Ako vise aplikacija ima slican prodajni cilj, nemoj po navici vracati gotovo istu blijedu paletu. Vizualni smjer prilagodi tonu komunikacije, ponudi, publici i trust sloju konkretne aplikacije.',
            '- Sekundarni blokovi trebaju biti neutralni ili blago tonirani. Ne smiju biti vizualno glasniji od glavnog bloka.',
            '- Izbjegavaj neon, fluorescentne tonove, kanarinac zutu i preveliku saturaciju koja djeluje neprofesionalno.',
            '- U color_palette i design_notes analiziraj pozadinu aplikacije, naslov, tekst, glavni blok i ostale blokove.',
            '- U obzir uzmi goal_context, fcc_goal_system, visual_profile, primary_action_block, secondary_block_style_samples i fcc_block_catalog. Trenutni dizajn je sekundarni kontekst, ne glavni izvor istine.',
            '- visual_profile i style profili sluze za dijagnozu kaosa, kontrasta i razine naglasaka. Ne tretiraj ih kao izvor palete koju treba zadrzati.',
            '- Screenshot ili visual_context nikad ne smije biti razlog da theme_pack ponovi iste postojece HEX boje, osim ako analytics, evolution i design_diagnostic jasno pokazu da je trenutna paleta vec mirna, fokusirana i ucinkovita.',
            '- Ako je nova theme_pack paleta gotovo ista kao trenutni vizual, to je dopusteno samo kad je to opravdano stvarnim rezultatom i treba biti objasnjeno u migration_note. Inace promijeni barem pozadinu ili glavni blok u bolji goal-first smjer.',
            '- U evolution_payload gledaj sto je prije bilo preporuceno, sto je stvarno primijenjeno i kakav je bio ishod nakon 7 ili 30 dana.',
            '- Ako evolution_payload sadrzi block_summary, koristi ga da prepoznas koji su blokovi pojacali signal, a koji su oslabili ili ostali bez rezultata.',
            '- Ako theme_applied_at, primary_applied_at ili layout_applied_at nedostaju, tretiraj to kao preporuku koja jos nije stvarno provedena.',
            '- Ako palette_feedback.has_feedback = true, to je stvarna ljudska povratna informacija o tome je li paleta korisniku sjela ili nije.',
            '- Ako palette_feedback.decision = keep i rezultati nisu losi, zadrzi isti osnovni vizualni smjer i predlozi samo finu doradu.',
            '- Ako palette_feedback.decision = refine, zadrzi osnovni mood palete, ali popravi kontrast, hijerarhiju i fokus glavnog bloka.',
            '- Ako palette_feedback.decision = replace, slobodno predlozi novi vizualni smjer i novu paletu ako ce to bolje pomoci cilju aplikacije.',
            '- Ako palette_feedback.decision = hold, nemoj zakljuciti da je paleta losa samo po performansu jer nije stvarno bila primijenjena.',
            '- Ako palette_feedback.note postoji, uzmi ga kao dodatni dokaz sto korisniku djeluje preglasno, prehladno, pretamno ili neusklađeno.',
            '- Ako je nesto primijenjeno i 7d ili 30d rezultat nije donio pomak, predlozi novi jasan korak umjesto ponavljanja istog savjeta.',
            '- Ako je nakon primjene vidljiv rast, zadrzi smjer i predlozi finu doradu, ne potpuni reset.',
            '- Ako coach_context.has_recent_activity = true, uzmi zadnje coach teme, blokade i preporuke kao dodatni signal iz stvarne komunikacije s korisnikom. Ugradi ih kad su u skladu s analyticsom, ciljem i live aplikacijom.',
            '- coach_context je sekundarni kontekst. Ako je u sukobu s realnim signalom i stvarnom live aplikacijom, prednost imaju analytics, block_attribution i trenutačna aplikacija.',
            '- Ako review_mode.current_app_state.has_changes_since_recommendation = true, to znaci da je korisnik vec doradjivao aplikaciju nakon zadnje AI preporuke. Tretiraj trenutnu live aplikaciju kao vazecu bazu za daljnju evoluciju, ne kao gresku, odstupanje ni razlog za reset.',
            '- Kad je trenutna live aplikacija novija od zadnje preporuke, default je incremental refinement: zadrzi osnovni vizualni smjer, boje i strukturu ako ne postoje jaki dokazi da to steti fokusu, povjerenju ili rezultatu.',
            '- U takvoj situaciji radije predlozi 1 do 3 manje, vrlo precizne nadogradnje s najvecim ucinkom nego novi veliki redesign. Novi reset palete, teme ili rasporeda koristi samo ako analytics, block_attribution, evolution i design_diagnostic jasno pokazu da je sadasnji smjer los.',
            '- Ako korisnik nakon zadnje analize promijeni tekst, raspored, fotografiju ili boju, to smijes tumaciti kao valjanu korisnicku iteraciju. Sljedeca analiza treba unaprijediti tu verziju aplikacije, ne vracati je na staru preporuku.',
            '- U trust_builders reci kako vise povjerenja grade fotografija, video, jedan glavni korak, edukacija, Funnel i jasan redoslijed blokova.',
            (!empty($evolution_payload['has_previous_review'])
                ? '- Ovo nije nova analiza od nule. Ovo je nadogradnja prethodne analize iste aplikacije. Ukratko reci sto je bolje nego prosli put, sto i dalje koci rezultat i koji je sada novi najbolji sljedeci korak.'
                : '- Ovo je prva analiza ove glavne aplikacije. Postavi jasnu pocetnu bazu, glavni fokus i najvazniji prvi redoslijed blokova.'),
            '- Ako dobijes vizual, koristi ga za komentare o fotografiji, dojmu, kontrastu i prvom ekranu, ali ne za kopiranje postojecih boja.',
            '- Ako je visual_context.scope = rendered_live_app, tretiraj to kao stvarni screenshot live FCC aplikacije i komentiraj raspored, fotografiju, citljivost i prvi dojam.',
            '- Ako je visual_context.scope = hero_image_only ili avatar_only, nemoj tvrditi da si pregledao cijelu live aplikaciju.',
            '- Ako nema vizuala, oslanjaj se samo na strukturu, redoslijed blokova i analitiku.',
            'Input JSON: ' . json_encode([
                'current_review_input' => $ai_input,
                'review_mode' => array_merge($evolution_payload, [
                    'current_app_state' => $current_app_state_payload,
                ]),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
        ]);
        $user_message_content = [
            [
                'type' => 'text',
                'text' => $user_prompt,
            ],
        ];

        if($supports_image_input) {
            $added_image_urls = [];

            foreach(['hero', 'middle', 'bottom'] as $segment_key) {
                $segment_payload = (array) ($selected_visual_segments[$segment_key] ?? []);
                $segment_visual_url = (string) ($segment_payload['primary_visual_url'] ?? '');

                if($segment_visual_url === '' || isset($added_image_urls[$segment_visual_url])) {
                    continue;
                }

                $user_message_content[] = [
                    'type' => 'text',
                    'text' => (string) (($segment_payload['title'] ?? $this->get_app_review_segment_title($segment_key)) . ': pregled ovog dijela aplikacije.'),
                ];
                $user_message_content[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $segment_visual_url,
                        'detail' => 'high',
                    ],
                ];

                $added_image_urls[$segment_visual_url] = true;
            }

            if(empty($added_image_urls) && $selected_visual_url !== '' && $selected_visual_scope !== 'none') {
                $user_message_content[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $selected_visual_url,
                        'detail' => 'high',
                    ],
                ];
            }
        }

        $response = Request::post(
            'https://api.openai.com/v1/chat/completions',
            [
                'Authorization' => 'Bearer ' . get_random_line_from_text($credentials['api_key']),
                'Content-Type' => 'application/json',
            ],
            Body::json([
                'model' => $credentials['model'],
                'response_format' => [
                    'type' => 'json_object',
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Pisi iskljucivo na hrvatskom. Ti si vrhunski mentor za FCC aplikacije. Tvoj posao nije komplicirati, nego pocetniku jednostavno reci sto da promijeni prvo, sto da ostavi i kako da aplikacija jasnije vodi prema sljedecem koraku. Pisi kratko, toplo, jasno i potpuno. Bez tehnickog jezika, bez marketinskog slenga, bez polurecenica. Vrati samo valjan JSON bez markdowna i bez dodatnih kljuceva.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $user_message_content,
                    ],
                ],
                'max_completion_tokens' => 2600,
            ])
        );

        if($response->code >= 400) {
            throw new \Exception($response->body->error->message ?? l('ai_plan.ai_error_request_failed'));
        }

        $content = trim((string) ($response->body->choices[0]->message->content ?? ''));

        if(substr($content, 0, 3) === '```') {
            $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);
        }

        $decoded_review = $this->extract_json_from_text($content);
        $used_fallback_review = false;

        if(!is_array($decoded_review)) {
            $review = $this->build_emergency_app_review($values, $analytics_payload, $selected_app, $current_clicks_30d, $request_context, $quality_payload, $selected_app_block_attribution);
            $used_fallback_review = true;
        } else {
            try {
                $review = $this->validate_app_review_response($decoded_review, $selected_app);
            } catch(\Throwable $exception) {
                $review = $this->build_emergency_app_review($values, $analytics_payload, $selected_app, $current_clicks_30d, $request_context, $quality_payload, $selected_app_block_attribution);
                $used_fallback_review = true;
            }
        }

        $review['copy_suggestions'] = $this->refine_app_review_copy_suggestions(
            (array) ($review['copy_suggestions'] ?? []),
            $selected_app,
            $this->get_effective_app_review_goal_type($values, $request_context, $selected_app),
            (string) ($this->user->name ?? '')
        );
        $review['layout_actions'] = $this->enforce_app_review_signal_safe_layout_actions(
            (array) ($review['layout_actions'] ?? []),
            $selected_app_block_attribution
        );
        $review['missing_block_recommendations'] = $this->refine_app_review_missing_block_recommendations(
            (array) ($review['missing_block_recommendations'] ?? []),
            (string) ($this->user->name ?? '')
        );

        $review['generated_at'] = $review_generated_at;
        $review['review_key'] = $review_generated_at;
        $review['model'] = $used_fallback_review ? 'fallback_local' : $credentials['model'];
        $review['selected_link_id'] = (int) ($selected_app['link_id'] ?? 0);
        $review['selected_app_url'] = (string) ($selected_app['url'] ?? '');
        $review['selected_app_name'] = (string) (($selected_app['name'] ?? '') ?: ($selected_app['url'] ?? ''));
        $review['request_context'] = $request_context;
        $review['goal_type'] = $this->get_effective_app_review_goal_type($values, $request_context, $selected_app);
        $review['fcc_core_block_policy'] = $this->get_fcc_core_block_policy($values, $review['goal_type'], $request_context);
        $review['growth_stage'] = $current_clicks_30d >= 15 ? 'active_signal' : 'building_signal';
        $review['quality_score'] = (int) ($quality_payload['score'] ?? 0);
        $review['quality_level'] = (string) ($quality_payload['level_key'] ?? 'foundation');
        $review['performance_snapshot'] = (array) ($quality_payload['performance'] ?? []);
        $review['block_attribution_snapshot'] = $selected_app_block_attribution;
        $review['signal_protection_summary'] = $this->build_app_review_signal_protection_summary($selected_app_block_attribution, (array) ($review['layout_actions'] ?? []));
        $review['analysis_mode'] = !empty($evolution_payload['has_previous_review']) ? 'evolution' : 'initial';
        $review['coach_context_snapshot'] = [
            'has_recent_activity' => !empty($coach_context['has_recent_activity']),
            'last_touch_at' => $coach_context['last_touch_at'] ?? null,
            'summary' => (string) ($coach_context['summary'] ?? ''),
            'recent_topics' => array_values(array_slice((array) ($coach_context['recent_topics'] ?? []), 0, 3)),
            'recent_challenges' => array_values(array_slice((array) ($coach_context['recent_challenges'] ?? []), 0, 3)),
            'recent_assistant_guidance' => array_values(array_slice((array) ($coach_context['recent_assistant_guidance'] ?? []), 0, 3)),
        ];

        return $review;
    }

    private function get_analytics_payload(int $user_id, int $current_clicks_30d): array {
        $period_start_datetime = (new \DateTimeImmutable())->sub(new \DateInterval('P29D'))->format('Y-m-d 00:00:00');
                $shop_block_types = ['link_discount', 'link_forever_living_bih', 'link_forever_living_alb_kosovo', 'link_forever_living_albania_kosovo'];
                $registration_block_types = ['link_forever_shop'];
                $shop_block_types_sql = "'" . implode("','", $shop_block_types) . "'";
                $registration_block_types_sql = "'" . implode("','", $registration_block_types) . "'";
                $outbound_condition = \Altum\Link::get_forever_outbound_click_condition_sql('`track_links`', '`biolinks_blocks`', $shop_block_types_sql, $registration_block_types_sql);
                $blog_product_medium = \Altum\Link::get_blog_cta_tracking_medium('product');
                $blog_business_medium = \Altum\Link::get_blog_cta_tracking_medium('business');

        $country_buckets = [];
        $source_buckets = [];
        $medium_buckets = [];
        $device_buckets = [];
        $language_buckets = [];
        $clicks_result = database()->query("SELECT `country_code`, `browser_language`, `device_type`, `referrer_host`, `utm_source`, `utm_medium`
            FROM `track_links`
            LEFT JOIN `biolinks_blocks` ON `track_links`.`biolink_block_id` = `biolinks_blocks`.`biolink_block_id`
            WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
              AND `track_links`.`is_unique` = 1
              AND `track_links`.`user_id` = {$user_id}
                            AND {$outbound_condition}");

        while($click_row = $clicks_result->fetch_assoc()) {
            $country_code = strtoupper(trim((string) ($click_row['country_code'] ?? '')));
            $browser_language = trim((string) ($click_row['browser_language'] ?? ''));
            $device_type = trim((string) ($click_row['device_type'] ?? ''));
            $source_label = $this->get_source_label($click_row);
            $medium_label = trim((string) ($click_row['utm_medium'] ?? ''));

            $this->increment_bucket($country_buckets, $country_code, $country_code);
            $this->increment_bucket($language_buckets, mb_strtolower($browser_language), $browser_language);
            $this->increment_bucket($source_buckets, mb_strtolower($source_label), $source_label);
            $this->increment_bucket($medium_buckets, mb_strtolower($medium_label), $medium_label);
            $this->increment_bucket($device_buckets, mb_strtolower($device_type), $device_type);
        }

        $top_countries = $this->humanize_breakdown($this->build_breakdown($country_buckets, $current_clicks_30d, 3), 'country');
        $top_sources = $this->humanize_breakdown($this->build_breakdown($source_buckets, $current_clicks_30d, 3), 'source');
        $top_mediums = $this->humanize_breakdown($this->build_breakdown($medium_buckets, $current_clicks_30d, 3), 'medium');
        $top_devices = $this->humanize_breakdown($this->build_breakdown($device_buckets, $current_clicks_30d, 3), 'device');
        $top_languages = $this->humanize_breakdown($this->build_breakdown($language_buckets, $current_clicks_30d, 3), 'language');

        $blog_signal_summary = database()->query("SELECT
            SUM(CASE WHEN `track_links`.`utm_medium` = '{$blog_product_medium}' THEN 1 ELSE 0 END) AS `product_clicks`,
            SUM(CASE WHEN `track_links`.`utm_medium` = '{$blog_business_medium}' THEN 1 ELSE 0 END) AS `business_clicks`
            FROM `track_links`
            WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
              AND `track_links`.`is_unique` = 1
              AND `track_links`.`user_id` = {$user_id}")->fetch_object();

        $top_blog_article = database()->query("SELECT `blog_posts`.`title`, `blog_posts`.`url`, `track_links`.`utm_medium`, COUNT(*) AS `total`
            FROM `track_links`
            LEFT JOIN `blog_posts` ON `blog_posts`.`blog_post_id` = CAST(SUBSTRING_INDEX(`track_links`.`utm_campaign`, ':', -1) AS UNSIGNED)
            WHERE `track_links`.`datetime` >= '{$period_start_datetime}'
              AND `track_links`.`is_unique` = 1
              AND `track_links`.`user_id` = {$user_id}
              AND `track_links`.`utm_medium` IN ('{$blog_product_medium}', '{$blog_business_medium}')
            GROUP BY `blog_posts`.`blog_post_id`, `track_links`.`utm_medium`
            ORDER BY `total` DESC, `blog_posts`.`blog_post_id` DESC
            LIMIT 1")->fetch_object();

        $blog_product_clicks = (int) ($blog_signal_summary->product_clicks ?? 0);
        $blog_business_clicks = (int) ($blog_signal_summary->business_clicks ?? 0);

        return [
            'top_country_label' => $this->get_primary_breakdown_label($top_countries),
            'top_source_label' => $this->get_primary_breakdown_label($top_sources, l('ai_plan.analytics_value.source.unattributed')),
            'top_medium_label' => $this->get_primary_breakdown_label($top_mediums, l('ai_plan.analytics_unknown')),
            'top_device_label' => $this->get_primary_breakdown_label($top_devices),
            'top_language_label' => $this->get_primary_breakdown_label($top_languages),
            'top_countries' => $top_countries,
            'top_sources' => $top_sources,
            'top_mediums' => $top_mediums,
            'top_devices' => $top_devices,
            'top_languages' => $top_languages,
            'blog_article_clicks' => $blog_product_clicks + $blog_business_clicks,
            'blog_product_clicks' => $blog_product_clicks,
            'blog_business_clicks' => $blog_business_clicks,
            'top_blog_article_title' => !empty($top_blog_article->title) ? (string) $top_blog_article->title : '-',
            'top_blog_article_url' => !empty($top_blog_article->url) ? (string) $top_blog_article->url : '',
            'top_blog_article_type' => !empty($top_blog_article->utm_medium) && $top_blog_article->utm_medium === $blog_business_medium ? 'business' : 'product',
            'funnel' => $this->get_sales_steps_payload($user_id, $period_start_datetime),
        ];
    }

    private function get_user_signal_summary_payload(array $values, array $analytics_payload, int $current_clicks_30d, bool $is_weekly_plan_eligible): array {
        $top_source = trim((string) ($analytics_payload['top_source_label'] ?? ''));
        $top_country = trim((string) ($analytics_payload['top_country_label'] ?? ''));
        $top_device = trim((string) ($analytics_payload['top_device_label'] ?? ''));
        $active_funnels = (int) ($analytics_payload['funnel']['active_funnels'] ?? 0);
        $total_funnels = (int) ($analytics_payload['funnel']['total_funnels'] ?? 0);
        $goal = (string) ($values['primary_goal'] ?? '');

        $signal_label = l('ai_plan.signal_summary.signal_label');
        $signal_text = l('ai_plan.signal_summary.signal_text_default');

        if($current_clicks_30d <= 0) {
            $signal_text = l('ai_plan.signal_summary.signal_text_zero');
        } elseif(!$is_weekly_plan_eligible) {
            $signal_text = sprintf(l('ai_plan.signal_summary.signal_text_building'), nr($current_clicks_30d), 15);
        } else {
            $signal_text = sprintf(l('ai_plan.signal_summary.signal_text_ready'), nr($current_clicks_30d));
        }

        $action_label = l('ai_plan.signal_summary.action_label');
        $action_text = l('ai_plan.signal_summary.action_text_default');

        if(!$is_weekly_plan_eligible) {
            if(in_array($top_source, [l('ai_plan.analytics_value.source.direct'), l('ai_plan.analytics_value.source.unattributed'), l('ai_plan.analytics_value.source.direct_share')], true)) {
                $action_text = l('ai_plan.signal_summary.action_text_direct');
            } elseif($goal === 'recruitment') {
                $action_text = l('ai_plan.signal_summary.action_text_recruitment');
            } else {
                $action_text = l('ai_plan.signal_summary.action_text_building');
            }
        } elseif($active_funnels > 0) {
            $action_text = sprintf(l('ai_plan.signal_summary.action_text_funnel_live'), nr($active_funnels), nr($total_funnels));
        } elseif($goal === 'product_sales') {
            $action_text = l('ai_plan.signal_summary.action_text_sales_ready');
        } else {
            $action_text = l('ai_plan.signal_summary.action_text_weekly_ready');
        }

        $watch_label = l('ai_plan.signal_summary.watch_label');
        $watch_text = l('ai_plan.signal_summary.watch_text_default');

        if($top_device === l('ai_plan.analytics_value.device.mobile')) {
            $watch_text = l('ai_plan.signal_summary.watch_text_mobile');
        }

        if($active_funnels > 0 && $current_clicks_30d < 15) {
            $watch_text = l('ai_plan.signal_summary.watch_text_funnel_early');
        } elseif($top_country !== '' && $top_country !== l('ai_plan.analytics_unknown')) {
            $watch_text = sprintf(l('ai_plan.signal_summary.watch_text_country'), $top_country);
        }

        return [
            [
                'label' => $signal_label,
                'text' => $signal_text,
            ],
            [
                'label' => $action_label,
                'text' => $action_text,
            ],
            [
                'label' => $watch_label,
                'text' => $watch_text,
            ],
        ];
    }

    private function get_adaptive_question(array $values, int $current_clicks_30d, array $analytics_payload): array {
        if($current_clicks_30d < 15) {
            return [
                'key' => 'traffic_unlock',
                'label' => l('ai_plan.adaptive_question.traffic_unlock'),
                'placeholder' => l('ai_plan.adaptive_placeholder.traffic_unlock'),
            ];
        }

        if(in_array((string) ($values['biggest_blocker'] ?? ''), ['no_leads', 'no_sales'], true) || (int) ($analytics_payload['funnel']['total_funnels'] ?? 0) > 0) {
            return [
                'key' => 'conversion_gap',
                'label' => l('ai_plan.adaptive_question.conversion_gap'),
                'placeholder' => l('ai_plan.adaptive_placeholder.conversion_gap'),
            ];
        }

        if((string) ($values['primary_goal'] ?? '') === 'recruitment') {
            return [
                'key' => 'recruitment_message',
                'label' => l('ai_plan.adaptive_question.recruitment_message'),
                'placeholder' => l('ai_plan.adaptive_placeholder.recruitment_message'),
            ];
        }

        if(in_array('instagram_story', (array) ($values['active_channels'] ?? []), true) || in_array('instagram_reel', (array) ($values['active_channels'] ?? []), true)) {
            return [
                'key' => 'content_angle',
                'label' => l('ai_plan.adaptive_question.content_angle'),
                'placeholder' => l('ai_plan.adaptive_placeholder.content_angle'),
            ];
        }

        return [
            'key' => 'practical_constraint',
            'label' => l('ai_plan.adaptive_question.practical_constraint'),
            'placeholder' => l('ai_plan.adaptive_placeholder.practical_constraint'),
        ];
    }

    private function sanitize_ai_string($value, int $max_length = 320): string {
        if(!is_scalar($value)) {
            return '';
        }

        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = trim($value);

        if($value === '' || mb_strlen($value) <= $max_length) {
            return $value;
        }

        $truncated = trim(mb_substr($value, 0, $max_length));
        $minimum_backtrack_position = (int) floor($max_length * 0.55);

        foreach(['. ', '! ', '? ', '. ', '.”', '!”', '?”'] as $needle) {
            $position = mb_strrpos($truncated, $needle);

            if($position !== false && $position >= $minimum_backtrack_position) {
                return trim(mb_substr($truncated, 0, $position + 1));
            }
        }

        foreach(['.', '!', '?'] as $needle) {
            $position = mb_strrpos($truncated, $needle);

            if($position !== false && $position >= $minimum_backtrack_position) {
                return trim(mb_substr($truncated, 0, $position + 1));
            }
        }

        $space_position = mb_strrpos($truncated, ' ');

        if($space_position !== false && $space_position >= $minimum_backtrack_position) {
            return rtrim(mb_substr($truncated, 0, $space_position)) . '…';
        }

        return rtrim($truncated) . '…';
    }

    private function normalize_ai_list($value, int $max_items = 5, int $max_length = 220): array {
        if(is_string($value)) {
            $value = preg_split('/\r\n|\r|\n|•|-\s+|\d+\.\s+/', $value) ?: [];
        }

        if(!is_array($value)) {
            return [];
        }

        $items = [];

        foreach($value as $item) {
            $normalized_item = $this->sanitize_ai_string($item, $max_length);

            if($normalized_item === '') {
                continue;
            }

            $items[] = $normalized_item;

            if(count($items) >= $max_items) {
                break;
            }
        }

        return $items;
    }

    private function extract_json_from_text(string $content): ?array {
        $decoded_content = json_decode($content, true);

        if(is_array($decoded_content)) {
            return $decoded_content;
        }

        $array_start = strpos($content, '[');
        $array_end = strrpos($content, ']');

        if($array_start !== false && $array_end !== false && $array_end > $array_start) {
            $array_candidate = substr($content, $array_start, $array_end - $array_start + 1);
            $decoded_array_candidate = json_decode($array_candidate, true);

            if(is_array($decoded_array_candidate)) {
                return $decoded_array_candidate;
            }
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

    private function get_ai_debug_error(string $content, string $message_key = 'ai_plan.ai_error_invalid_response'): \Exception {
        $message = l($message_key);

        if(\Altum\Authentication::is_admin()) {
            $snippet = $this->sanitize_ai_string($content, 450);
            if($snippet !== '') {
                $message .= ' [' . $snippet . ']';
            }
        }

        return new \Exception($message);
    }

    private function unwrap_weekly_ai_plan_payload(array $plan): array {
        if(array_is_list($plan)) {
            foreach($plan as $item) {
                if(is_array($item)) {
                    return $this->unwrap_weekly_ai_plan_payload($item);
                }
            }
        }

        foreach(['plan', 'weekly_plan', 'data', 'result'] as $key) {
            if(isset($plan[$key]) && is_array($plan[$key])) {
                return $this->unwrap_weekly_ai_plan_payload($plan[$key]);
            }
        }

        return $plan;
    }

    private function build_fallback_daily_plan(array $plan, string $focus, string $summary, array $priority_channels, array $content_ideas, array $coach_ideas, array $do_not_do): array {
        $tasks_pool = [];

        $summary_steps = preg_split('/(?<=[\.!?])\s+/', $summary) ?: [];
        foreach($summary_steps as $summary_step) {
            $summary_step = $this->sanitize_ai_string($summary_step, 180);

            if($summary_step !== '') {
                $tasks_pool[] = $summary_step;
            }
        }

        foreach($priority_channels as $priority_channel) {
            $priority_channel = $this->sanitize_ai_string($priority_channel, 80);

            if($priority_channel !== '') {
                $tasks_pool[] = 'Objavi sadrzaj ili poziv na akciju kroz kanal: ' . $priority_channel;
            }
        }

        foreach($content_ideas as $idea) {
            $tasks_pool[] = 'Objavi ili pripremi: ' . $idea;
        }

        foreach($coach_ideas as $coach_idea) {
            $coach_idea = $this->sanitize_ai_string($coach_idea, 180);

            if($coach_idea !== '') {
                $tasks_pool[] = $coach_idea;
            }
        }

        $next_steps = $this->normalize_ai_list($plan['next_steps'] ?? $plan['action_steps'] ?? $plan['tasks'] ?? [], 7, 180);
        foreach($next_steps as $step) {
            $tasks_pool[] = $step;
        }

        if($focus !== '') {
            $tasks_pool[] = 'Drzi fokus na: ' . $focus;
        }

        if(!empty($do_not_do)) {
            $tasks_pool[] = 'Izbjegni: ' . implode(', ', array_slice($do_not_do, 0, 2));
        }

        $tasks_pool = array_values(array_unique(array_filter($tasks_pool)));

        if(empty($tasks_pool)) {
            return [];
        }

        $day_labels = ['Dan 1', 'Dan 2', 'Dan 3', 'Dan 4', 'Dan 5', 'Dan 6', 'Dan 7'];
        $fallback_plan = [];

        for($index = 0; $index < 7; $index++) {
            $task_a = $tasks_pool[$index % count($tasks_pool)];
            $task_b = $tasks_pool[($index + 1) % count($tasks_pool)];

            $fallback_plan[] = [
                'day' => $day_labels[$index],
                'title' => $focus !== '' ? $focus : 'Praktican tjedni korak',
                'tasks' => array_values(array_unique([$task_a, $task_b])),
            ];
        }

        return $fallback_plan;
    }

    private function build_weekly_text_fallback(array $candidates, int $max_length = 320): string {
        $parts = [];

        foreach($candidates as $candidate) {
            if(is_object($candidate)) {
                $candidate = (array) $candidate;
            }

            if(is_array($candidate)) {
                $candidate = implode(' ', $this->normalize_ai_list($candidate, 2, min(180, $max_length)));
            }

            $candidate = $this->sanitize_ai_string($candidate, $max_length);

            if($candidate === '') {
                continue;
            }

            $parts[] = $candidate;

            if(count($parts) >= 2) {
                break;
            }
        }

        if(empty($parts)) {
            return '';
        }

        return $this->sanitize_ai_string(implode(' ', array_values(array_unique($parts))), $max_length);
    }

    private function normalize_daily_plan_tasks($value): array {
        if(is_object($value)) {
            $value = (array) $value;
        }

        $tasks = $this->normalize_ai_list($value, 4, 180);

        if(!empty($tasks)) {
            return $tasks;
        }

        if(!is_array($value)) {
            return [];
        }

        $possible_task_fields = [
            'task',
            'task_one',
            'task_two',
            'task_three',
            'task_1',
            'task_2',
            'task_3',
            'step',
            'step_one',
            'step_two',
            'step_three',
            'todo',
            'to_do',
            'action',
            'action_one',
            'action_two',
            'action_three',
            'next_step',
        ];

        $collected_tasks = [];

        foreach($possible_task_fields as $field) {
            if(!isset($value[$field])) {
                continue;
            }

            if(is_array($value[$field]) || is_object($value[$field])) {
                $collected_tasks = array_merge($collected_tasks, $this->normalize_ai_list($value[$field], 4, 180));
                continue;
            }

            $collected_tasks[] = $value[$field];
        }

        return $this->normalize_ai_list($collected_tasks, 4, 180);
    }

    private function normalize_daily_plan($value): array {
        if(is_object($value)) {
            $value = (array) $value;
        }

        if(is_array($value) && isset($value['days']) && is_array($value['days'])) {
            $value = $value['days'];
        }

        if(!is_array($value)) {
            return [];
        }

        $normalized_days = [];

        foreach($value as $index => $day_plan) {
            if(is_object($day_plan)) {
                $day_plan = (array) $day_plan;
            }

            $day = is_string($index) && !is_numeric($index)
                ? $this->sanitize_ai_string($index, 40)
                : '';

            if(is_scalar($day_plan)) {
                $title = $this->sanitize_ai_string($day_plan, 120);

                if($title === '') {
                    continue;
                }

                $normalized_days[] = [
                    'day' => $day !== '' ? $day : ('Dan ' . (count($normalized_days) + 1)),
                    'title' => $title,
                    'tasks' => [$title],
                ];

                if(count($normalized_days) >= 7) {
                    break;
                }

                continue;
            }

            if(!is_array($day_plan)) {
                continue;
            }

            $tasks = $this->normalize_daily_plan_tasks(
                $day_plan['tasks']
                ?? $day_plan['actions']
                ?? $day_plan['steps']
                ?? $day_plan['checklist']
                ?? $day_plan['todo']
                ?? $day_plan['to_do']
                ?? []
            );
            $title = $this->sanitize_ai_string($day_plan['title'] ?? $day_plan['focus'] ?? $day_plan['headline'] ?? $day_plan['main_task'] ?? $day_plan['main_focus'] ?? '', 120);
            $day = $this->sanitize_ai_string($day_plan['day'] ?? $day_plan['label'] ?? $day ?: ('Dan ' . ($index + 1)), 40);

            if($title === '' && !empty($tasks)) {
                $title = $tasks[0];
            }

            if(empty($tasks) && $title !== '') {
                $tasks = [$title];
            }

            if($title === '' || empty($tasks)) {
                continue;
            }

            $normalized_days[] = [
                'day' => $day,
                'title' => $title,
                'tasks' => $tasks,
            ];

            if(count($normalized_days) >= 7) {
                break;
            }
        }

        return $normalized_days;
    }

    private function get_ai_credentials(): array {
        $personal_api_key = trim((string) ($this->user->preferences->openai_api_key ?? ''));
        $shared_api_key = trim((string) (settings()->aix->openai_api_key ?? settings()->main->openai_api_key ?? ''));
        $api_key = $this->user->plan_settings->exclusive_personal_api_keys ? $personal_api_key : $shared_api_key;
        $model = fc_get_resolved_openai_model(settings()->main->openai_model ?? '');

        return [
            'api_key' => $api_key,
            'model' => $model,
            'needs_personal_key' => (bool) $this->user->plan_settings->exclusive_personal_api_keys,
        ];
    }

    private function get_option_labels(array $values, array $weekly_checkin): array {
        $channels = [];
        foreach((array) ($values['active_channels'] ?? []) as $channel) {
            $channels[] = l('ai_plan.option.active_channels.' . $channel);
        }

        return [
            'primary_goal' => $values['primary_goal'] ? l('ai_plan.option.primary_goal.' . $values['primary_goal']) : '',
            'priority_offer' => $values['priority_offer'] ? l('ai_plan.option.priority_offer.' . $values['priority_offer']) : '',
            'active_channels' => $channels,
            'available_time' => $values['available_time'] ? l('ai_plan.option.available_time.' . $values['available_time']) : '',
            'biggest_blocker' => $values['biggest_blocker'] ? l('ai_plan.option.biggest_blocker.' . $values['biggest_blocker']) : '',
            'communication_style' => $values['communication_style'] ? l('ai_plan.option.communication_style.' . $values['communication_style']) : '',
            'follow_up_readiness' => $values['follow_up_readiness'] ? l('ai_plan.option.follow_up_readiness.' . $values['follow_up_readiness']) : '',
            'weekly_change' => $values['weekly_change'] ? l('ai_plan.option.weekly_change.' . $values['weekly_change']) : '',
            'weekly_priority' => $weekly_checkin['weekly_priority'] ? l('ai_plan.option.weekly_priority.' . $weekly_checkin['weekly_priority']) : '',
            'content_commitment' => $weekly_checkin['content_commitment'] ? l('ai_plan.option.content_commitment.' . $weekly_checkin['content_commitment']) : '',
            'follow_up_volume' => $weekly_checkin['follow_up_volume'] ? l('ai_plan.option.follow_up_volume.' . $weekly_checkin['follow_up_volume']) : '',
            'ai_need' => $weekly_checkin['ai_need'] ? l('ai_plan.option.ai_need.' . $weekly_checkin['ai_need']) : '',
            'weekly_energy' => $weekly_checkin['weekly_energy'] ? l('ai_plan.option.weekly_energy.' . $weekly_checkin['weekly_energy']) : '',
        ];
    }

    private function get_strategy_guardrails(array $values, array $weekly_checkin, array $analytics_payload, array $app_structure_payload, ?array $previous_cycle_context = null, array $mentor_guidance = []): array {
        $guardrails = [];
        $current_clicks_30d = (int) ($analytics_payload['webshop_clicks'] ?? 0);
        $top_source = mb_strtolower((string) ($analytics_payload['top_source_label'] ?? ''));
        $top_device = mb_strtolower((string) ($analytics_payload['top_device_label'] ?? ''));
        $total_apps = (int) ($app_structure_payload['total_apps'] ?? 0);
        $top_app_total_blocks = (int) ($app_structure_payload['top_app_total_blocks'] ?? 0);
        $active_funnels = (int) ($analytics_payload['funnel']['active_funnels'] ?? 0);
        $previous_outcome = $previous_cycle_context['outcome'] ?? null;

        if($current_clicks_30d < 20) {
            $guardrails[] = 'Signal je jos slab. Fokus tjedna ne smije biti sirok plan nego jedna jasna putanja do kvalitetnog klika.';
        }

        if($total_apps > 2 || $top_app_total_blocks >= 10) {
            $guardrails[] = 'Struktura izgleda siroko ili pretrpano. Velika je sansa da ljudi imaju previse izbora prije glavne ponude.';
        }

        if($active_funnels > 0 && $current_clicks_30d < 40) {
            $guardrails[] = 'Funnel postoji, ali mozda dolazi prerano u odnosu na kolicinu interesa. Prvo treba uciniti ulaz jednostavnijim i jasnijim.';
        }

        if((int) ($analytics_payload['blog_article_clicks'] ?? 0) > 0) {
            $guardrails[] = 'Blog clanak vec dovodi stvarne klikove. Plan mora to tretirati kao prodajni alat: koristi clanak u follow-upu, privatnim porukama i story objavama umjesto da sve objasnjavas od nule.';
        }

        if(
            in_array($top_source, [
                mb_strtolower(l('ai_plan.analytics_value.source.direct')),
                mb_strtolower(l('ai_plan.analytics_value.source.unattributed')),
                mb_strtolower(l('ai_plan.analytics_value.source.direct_share')),
            ], true)
            || str_contains($top_source, 'sami')
            || str_contains($top_source, 'bez druge mreze')
            || str_contains($top_source, 'without another network')
        ) {
            $guardrails[] = 'Vecina dolazaka nije iz stabilnog kanala nego usputno. Zato su vazniji jasna poruka i ponovljiv format, a ne sirok raspon aktivnosti.';
        }

        if(str_contains($top_device, 'mobitel') || str_contains($top_device, 'phone')) {
            $guardrails[] = 'Sve mora biti doneseno za ekran mobitela: prva poruka, prvi klik i prvi izbor moraju biti ociti u par sekundi.';
        }

        if(($weekly_checkin['weekly_energy'] ?? '') === 'low') {
            $guardrails[] = 'Osoba nema energije za kompleksan tjedan. Plan mora rezati sve sto nije nuzno i ostaviti samo jedan glavni ritam koji se moze ponoviti.';
        }

        if(in_array((string) ($values['biggest_blocker'] ?? ''), ['no_clicks', 'no_leads', 'no_sales'], true)) {
            $guardrails[] = 'Glavni problem nije kolicina ideja nego prijelaz u sljedeci korak. AI mora reci sto konkretno ne stvara dovoljno jasnu odluku kod osobe koja gleda.';
        }

        if(($weekly_checkin['content_commitment'] ?? '') === 'stories_daily' || ($values['available_time'] ?? '') === 'story_only') {
            $guardrails[] = 'Najvjerojatniji winning format su story i kratki lagani formati, ne teska produkcija.';
        }

        if(is_array($previous_outcome)) {
            $completion_level = (string) ($previous_outcome['completion_level'] ?? '');
            $best_response = $this->sanitize_ai_string($previous_outcome['best_response'] ?? '', 220);
            $main_blocker_now = $this->sanitize_ai_string($previous_outcome['main_blocker_now'] ?? '', 220);
            $next_adjustment = $this->sanitize_ai_string($previous_outcome['next_adjustment'] ?? '', 220);
            $palette_decision = (string) ($previous_outcome['palette_decision'] ?? $this->get_palette_feedback_decision($previous_outcome['palette_feedback'] ?? ''));
            $palette_feedback_note = $this->sanitize_ai_string($previous_outcome['palette_feedback_note'] ?? '', 220);

            if(in_array($completion_level, ['low_execution', 'not_started'], true)) {
                $guardrails[] = 'Prosli tjedan izvedba je bila slaba. Novi plan mora biti osjetno jednostavniji, uz manje obveza i jednu glavnu pobjedu koju osoba realno moze zatvoriti.';
            }

            if($best_response !== '') {
                $guardrails[] = 'Ono sto je prosli tjedan najbolje reagiralo ne smije se izgubiti. Novi plan treba to pojacati umjesto da opet krece od nule.';
            }

            if($main_blocker_now !== '') {
                $guardrails[] = 'Najveci trenutni zastoj je vec poznat. Plan mora rezati upravo taj zastoj, a ne otvarati novu sirinu.';
            }

            if($next_adjustment !== '') {
                $guardrails[] = 'Korisnik je vec rekao sto zeli prilagoditi. Ako je to razumno, plan to mora ugraditi kao stvarnu korekciju, ne ignorirati.';
            }

            if($palette_decision === 'keep') {
                $guardrails[] = 'Korisniku je paleta sjela. Nemoj bez jakog razloga mijenjati cijeli vizualni smjer, nego ga doradi mirno i precizno.';
            } elseif($palette_decision === 'refine') {
                $guardrails[] = 'Paleta je uglavnom dobra, ali trazi doradu. Novi plan smije predlagati finu korekciju kontrasta, hijerarhije i fokusa, bez potpunog reseta.';
            } elseif($palette_decision === 'replace') {
                $guardrails[] = 'Korisniku paleta nije sjela. Novi plan smije traziti novi smjer boja ako to i signal i cilj podrzavaju.';
            } elseif($palette_decision === 'hold') {
                $guardrails[] = 'Paleta nije stvarno primijenjena. Nemoj je proglasiti losom samo po rezultatu dok nije bila stvarno aktivna na aplikaciji.';
            }

            if($palette_feedback_note !== '') {
                $guardrails[] = 'Korisnik je ostavio i konkretan komentar o paleti. To uzmi kao korektiv dojma, ne samo kao estetsku napomenu.';
            }
        }

        if(!empty($mentor_guidance['has_guidance'])) {
            $guardrails[] = 'Postoji dodatna procjena mentora iz direktnog kontakta. Uzmi je kao korektiv realnosti i fokusa, ali je ne smijes slijepo staviti iznad stvarnog signala iz ponasanja i rezultata.';
        }

        return array_values(array_unique($guardrails));
    }

    private function build_weekly_ai_plan_input(array $values, array $weekly_checkin, array $analytics_payload, array $app_structure_payload, array $adaptive_question, ?array $previous_cycle_context = null, array $mentor_guidance = [], ?array $latest_app_review = null, array $coach_context = []): array {
        $labels = $this->get_option_labels($values, $weekly_checkin);
        $analytics_payload['webshop_clicks'] = (int) $this->get_last_30_days_shop_clicks();
        $goal_type = $this->get_goal_type($values);
        $fcc_goal_system = $this->get_fcc_goal_system_payload($values, $goal_type);
        $previous_outcome = $previous_cycle_context['outcome'] ?? null;
        $previous_plan = $previous_cycle_context['plan'] ?? null;
        $previous_checkin = $previous_cycle_context['checkin'] ?? null;
        $focus_app_context = $this->get_weekly_ai_focus_app_context($app_structure_payload, $latest_app_review, $previous_plan);
        $main_app_snapshot = (array) ($focus_app_context['current_structure'] ?? $this->get_weekly_ai_app_structure_snapshot($this->get_main_app_for_review($app_structure_payload)));

        return [
            'user' => [
                'name' => (string) ($this->user->name ?? ''),
                'email' => (string) ($this->user->email ?? ''),
            ],
            'profile' => [
                'primary_goal' => $labels['primary_goal'],
                'priority_offer' => $labels['priority_offer'],
                'active_channels' => $labels['active_channels'],
                'available_time' => $labels['available_time'],
                'biggest_blocker' => $labels['biggest_blocker'],
                'communication_style' => $labels['communication_style'],
                'follow_up_readiness' => $labels['follow_up_readiness'],
                'weekly_change' => $labels['weekly_change'],
                'audience_focus' => (string) ($values['audience_focus'] ?? ''),
                'product_focus' => (string) ($values['product_focus'] ?? ''),
                'visual_tone_preference' => (string) ($values['visual_tone_preference'] ?? ''),
                'notes' => (string) ($values['notes'] ?? ''),
            ],
            'weekly_checkin' => [
                'submitted_at' => (string) ($weekly_checkin['submitted_at'] ?? ''),
                'weekly_priority' => $labels['weekly_priority'],
                'content_commitment' => $labels['content_commitment'],
                'follow_up_volume' => $labels['follow_up_volume'],
                'ai_need' => $labels['ai_need'],
                'weekly_energy' => $labels['weekly_energy'],
                'weekly_context' => (string) ($weekly_checkin['weekly_context'] ?? ''),
                'adaptive_question' => (string) ($adaptive_question['label'] ?? ''),
                'adaptive_answer' => (string) ($weekly_checkin['adaptive_answer'] ?? ''),
            ],
            'analytics_30d' => [
                'webshop_clicks' => (int) $analytics_payload['webshop_clicks'],
                'top_country' => (string) ($analytics_payload['top_country_label'] ?? '-'),
                'top_source' => (string) ($analytics_payload['top_source_label'] ?? '-'),
                'top_device' => (string) ($analytics_payload['top_device_label'] ?? '-'),
                'top_language' => (string) ($analytics_payload['top_language_label'] ?? '-'),
                'blog_article_clicks' => (int) ($analytics_payload['blog_article_clicks'] ?? 0),
                'blog_product_clicks' => (int) ($analytics_payload['blog_product_clicks'] ?? 0),
                'blog_business_clicks' => (int) ($analytics_payload['blog_business_clicks'] ?? 0),
                'top_blog_article_title' => (string) ($analytics_payload['top_blog_article_title'] ?? '-'),
                'top_blog_article_type' => (string) ($analytics_payload['top_blog_article_type'] ?? 'product'),
                'top_countries' => $analytics_payload['top_countries'] ?? [],
                'top_sources' => $analytics_payload['top_sources'] ?? [],
                'funnel' => $analytics_payload['funnel'] ?? [],
            ],
            'app_structure' => $app_structure_payload,
            'main_app_structure' => $main_app_snapshot,
            'weekly_focus_app' => $focus_app_context,
            'fcc_goal_system' => $fcc_goal_system,
            'previous_cycle' => [
                'has_previous_checkin' => (bool) $previous_checkin,
                'checkin' => $previous_checkin ? [
                    'submitted_at' => (string) ($previous_checkin['submitted_at'] ?? ''),
                    'weekly_priority' => !empty($previous_checkin['weekly_priority']) ? l('ai_plan.option.weekly_priority.' . $previous_checkin['weekly_priority']) : '',
                    'content_commitment' => !empty($previous_checkin['content_commitment']) ? l('ai_plan.option.content_commitment.' . $previous_checkin['content_commitment']) : '',
                    'follow_up_volume' => !empty($previous_checkin['follow_up_volume']) ? l('ai_plan.option.follow_up_volume.' . $previous_checkin['follow_up_volume']) : '',
                    'ai_need' => !empty($previous_checkin['ai_need']) ? l('ai_plan.option.ai_need.' . $previous_checkin['ai_need']) : '',
                    'weekly_energy' => !empty($previous_checkin['weekly_energy']) ? l('ai_plan.option.weekly_energy.' . $previous_checkin['weekly_energy']) : '',
                    'weekly_context' => (string) ($previous_checkin['weekly_context'] ?? ''),
                    'adaptive_answer' => (string) ($previous_checkin['adaptive_answer'] ?? ''),
                ] : null,
                'plan' => $previous_plan ? [
                    'headline' => (string) ($previous_plan['headline'] ?? ''),
                    'focus' => (string) ($previous_plan['focus'] ?? ''),
                    'power_move' => (string) ($previous_plan['power_move'] ?? ''),
                    'brutal_truth' => (string) ($previous_plan['brutal_truth'] ?? ''),
                ] : null,
                'outcome' => $previous_outcome ? [
                    'completion_level' => $this->get_completion_level_label($previous_outcome['completion_level'] ?? ''),
                    'best_response' => (string) ($previous_outcome['best_response'] ?? ''),
                    'main_blocker_now' => (string) ($previous_outcome['main_blocker_now'] ?? ''),
                    'biggest_lesson' => (string) ($previous_outcome['biggest_lesson'] ?? ''),
                    'next_adjustment' => (string) ($previous_outcome['next_adjustment'] ?? ''),
                    'palette_feedback' => $this->get_palette_feedback_label($previous_outcome['palette_feedback'] ?? ''),
                    'palette_feedback_note' => (string) ($previous_outcome['palette_feedback_note'] ?? ''),
                    'palette_decision' => (string) ($previous_outcome['palette_decision'] ?? $this->get_palette_feedback_decision($previous_outcome['palette_feedback'] ?? '')),
                ] : null,
            ],
            'mentor_context' => [
                'has_guidance' => (bool) ($mentor_guidance['has_guidance'] ?? false),
                'guidance' => (string) ($mentor_guidance['guidance'] ?? ''),
                'weight' => 'secondary',
                'visibility' => 'admin_only',
            ],
            'coach_context' => [
                'has_recent_activity' => !empty($coach_context['has_recent_activity']),
                'last_touch_at' => $coach_context['last_touch_at'] ?? null,
                'conversation_count' => (int) ($coach_context['conversation_count'] ?? 0),
                'summary' => (string) ($coach_context['summary'] ?? ''),
                'recent_topics' => array_values(array_filter((array) ($coach_context['recent_topics'] ?? []), 'is_string')),
                'recent_challenges' => array_values(array_filter((array) ($coach_context['recent_challenges'] ?? []), 'is_string')),
                'recent_user_messages' => array_values(array_filter((array) ($coach_context['recent_user_messages'] ?? []), 'is_string')),
                'recent_assistant_guidance' => array_values(array_filter((array) ($coach_context['recent_assistant_guidance'] ?? []), 'is_string')),
            ],
            'strategy_guardrails' => $this->get_strategy_guardrails($values, $weekly_checkin, $analytics_payload, $app_structure_payload, $previous_cycle_context, $mentor_guidance),
        ];
    }

    private function validate_weekly_ai_plan_response(array $plan): array {
        $plan = $this->unwrap_weekly_ai_plan_payload($plan);

        $headline = $this->build_weekly_text_fallback([
            $plan['headline'] ?? '',
            $plan['title'] ?? '',
            $plan['focus'] ?? '',
            $plan['main_focus'] ?? '',
            $plan['power_move'] ?? '',
            $plan['leverage_move'] ?? '',
        ], 140);
        $summary = $this->build_weekly_text_fallback([
            $plan['summary'] ?? '',
            $plan['overview'] ?? '',
            $plan['executive_summary'] ?? '',
            $plan['coach_intro'] ?? '',
            $plan['intro'] ?? '',
            $plan['opening_note'] ?? '',
            $plan['why_this_week'] ?? '',
            $plan['strategic_explanation'] ?? '',
        ], 900);
        $focus = $this->build_weekly_text_fallback([
            $plan['focus'] ?? '',
            $plan['main_focus'] ?? '',
            $headline,
            $plan['power_move'] ?? '',
        ], 180);
        $coach_intro = $this->build_weekly_text_fallback([
            $plan['coach_intro'] ?? '',
            $plan['intro'] ?? '',
            $plan['opening_note'] ?? '',
            $summary,
        ], 420);
        $brutal_truth = $this->sanitize_ai_string($plan['brutal_truth'] ?? $plan['hard_truth'] ?? $plan['uncomfortable_truth'] ?? '', 320);
        $power_move = $this->sanitize_ai_string($plan['power_move'] ?? $plan['leverage_move'] ?? $plan['best_move'] ?? '', 320);
        $why_this_week = $this->build_weekly_text_fallback([
            $plan['why_this_week'] ?? '',
            $plan['strategic_explanation'] ?? '',
            $plan['reasoning'] ?? '',
            $summary,
        ], 500);
        $encouragement = $this->build_weekly_text_fallback([
            $plan['encouragement'] ?? '',
            $plan['mindset_note'] ?? '',
            $plan['closing_note'] ?? '',
        ], 320);
        $priority_channels = $this->normalize_ai_list($plan['priority_channels'] ?? $plan['channels'] ?? [], 4, 80);
        $content_ideas = $this->normalize_ai_list($plan['content_ideas'] ?? $plan['content_plan'] ?? $plan['content_suggestions'] ?? [], 5, 180);
        $coach_ideas = $this->normalize_ai_list($plan['coach_ideas'] ?? $plan['next_move_ideas'] ?? $plan['recommendation_ideas'] ?? [], 5, 180);

        if(empty($coach_ideas)) {
            $legacy_follow_up_idea = $this->sanitize_ai_string($plan['follow_up_script'] ?? $plan['message_script'] ?? $plan['dm_script'] ?? '', 180);

            if($legacy_follow_up_idea !== '') {
                $coach_ideas = [$legacy_follow_up_idea];
            }
        }

        $do_not_do = $this->normalize_ai_list($plan['do_not_do'] ?? $plan['avoid'] ?? $plan['warnings'] ?? [], 4, 180);
        $daily_plan = $this->normalize_daily_plan($plan['daily_plan'] ?? $plan['week_plan'] ?? $plan['seven_day_plan'] ?? $plan['days'] ?? $plan['daily_actions'] ?? []);

        if(count($daily_plan) < 3) {
            $fallback_daily_plan = $this->build_fallback_daily_plan(
                $plan,
                $focus,
                $summary !== '' ? $summary : $coach_intro,
                $priority_channels,
                $content_ideas,
                $coach_ideas,
                $do_not_do
            );

            if(count($fallback_daily_plan) >= 3) {
                $daily_plan = $fallback_daily_plan;
            }
        }

        if($headline === '' || $summary === '' || count($daily_plan) < 3) {
            throw new \Exception(l('ai_plan.ai_error_invalid_response'));
        }

        return [
            'headline' => $headline,
            'summary' => $summary,
            'focus' => $focus,
            'coach_intro' => $coach_intro,
            'brutal_truth' => $brutal_truth,
            'power_move' => $power_move,
            'why_this_week' => $why_this_week,
            'encouragement' => $encouragement,
            'priority_channels' => $priority_channels,
            'content_ideas' => $content_ideas,
            'coach_ideas' => $coach_ideas,
            'do_not_do' => $do_not_do,
            'daily_plan' => $daily_plan,
        ];
    }

    private function build_emergency_weekly_ai_plan(array $values, array $weekly_checkin, array $analytics_payload, array $app_structure_payload, ?array $previous_cycle_context = null, array $mentor_guidance = [], ?array $latest_app_review = null, array $coach_context = []): array {
        $labels = $this->get_option_labels($values, $weekly_checkin);
        $goal_type = $this->get_goal_type($values);
        $guardrails = $this->get_strategy_guardrails($values, $weekly_checkin, $analytics_payload, $app_structure_payload, $previous_cycle_context, $mentor_guidance);
        $main_app = $this->get_weekly_plan_focus_app($app_structure_payload, $latest_app_review) ?? $this->get_main_app_for_review($app_structure_payload);
        $main_app_total_blocks = (int) ($main_app['total_blocks'] ?? $app_structure_payload['top_app_total_blocks'] ?? 0);
        $active_funnels = (int) ($analytics_payload['funnel']['active_funnels'] ?? 0);
        $current_clicks_30d = (int) ($analytics_payload['webshop_clicks'] ?? 0);
        $is_low_energy = (($weekly_checkin['weekly_energy'] ?? '') === 'low');
        $is_contact_goal = $this->is_contact_collection_goal($values, $goal_type);

        $focus = match($goal_type) {
            'business' => 'Učini put do prijave i prvog ozbiljnog kontakta potpuno jasnim',
            'shop' => 'Učini put do proizvoda i prvog klika kraćim i sigurnijim',
            'brand' => 'Pojačaj povjerenje prije nego osoba donese prvi klik',
            'activation' => 'Pretvori postojeći interes u stvarni sljedeći korak',
            default => 'Pojednostavni glavni put kroz aplikaciju i komunikaciju',
        };

        $headline = match($goal_type) {
            'business' => 'Tjedan jasnijeg prvog koraka za suradnju',
            'shop' => 'Tjedan čišćeg puta do proizvoda',
            'brand' => 'Tjedan jačeg povjerenja i jasnije poruke',
            'activation' => 'Tjedan pretvaranja interesa u stvarni odgovor',
            default => 'Tjedan jednog jasnog pomaka',
        };

        $summary_parts = [
            'Ovaj tjedan nemoj širiti fokus. Najviše će pomoći da osoba koja dođe na tvoju aplikaciju odmah razumije što je glavni sljedeći korak i zašto vrijedi kliknuti baš njega.',
        ];

        if($main_app_total_blocks >= 9) {
            $summary_parts[] = 'Aplikacija trenutno vjerojatno nudi previše izbora prerano, pa plan ide prema čišćem vrhu i manje distrakcije.';
        }

        if($is_contact_goal && $active_funnels === 0) {
            $summary_parts[] = 'Za tvoj cilj nedostaje jednostavan korak za prijavu ili ostavljanje kontakta, zato to treba postati glavni potez tjedna.';
        } elseif($current_clicks_30d < 15) {
            $summary_parts[] = 'Signal je još slab, zato plan mora prvo stvoriti jasniji i ponovljiv ulaz prije širenja aktivnosti.';
        }

        if($is_low_energy) {
            $summary_parts[] = 'Energija je ograničena, zato sve svodimo na jedan ritam koji je realno izvediv bez pritiska.';
        }

        $summary = $this->sanitize_ai_string(implode(' ', array_slice($summary_parts, 0, 3)), 900);
        $coach_intro = $this->sanitize_ai_string('Ne treba ti više ideja nego jasniji prvi korak. Ovaj plan reže višak i drži fokus na jednoj promjeni koja može najbrže popraviti rezultat. Kad aplikacija postane jednostavnija, lakše ćeš vidjeti što stvarno radi.', 420);

        $brutal_truth = $main_app_total_blocks >= 9
            ? 'Kad osoba prebrzo vidi previše opcija, najčešće ne odabere ništa.'
            : ($is_contact_goal && $active_funnels === 0
                ? 'Ako tražiš ozbiljan kontakt, a ne nudiš jasan korak za prijavu, interes lako ostaje bez nastavka.'
                : 'Rezultat sada više koči nejasan prvi korak nego manjak truda.');

        $power_move = $is_contact_goal && $active_funnels === 0
            ? 'Dodaj Funnel kao glavni prvi korak odmah nakon videa ili prvog uvoda i pojednostavni sve što mu oduzima fokus.'
            : ($main_app_total_blocks >= 9
                ? 'Smanji broj ranih izbora i ostavi jedan glavni blok koji vodi prema najvažnijem sljedećem koraku.'
                : 'Pojačaj prvi blok, tekst i redoslijed tako da osoba u par sekundi vidi što treba napraviti dalje.');

        $why_this_week = $this->sanitize_ai_string('Ovaj fokus ima smisla jer najbrže popravlja ono gdje ljudi sada zapinju: previše širine, premalo jasnoće ili preslab prijelaz u stvarni kontakt. Kad središ prvi korak, sve ostale aktivnosti dobivaju više smisla i veći učinak.', 500);
        $encouragement = $this->sanitize_ai_string('Ne pokušavaj ovaj tjedan pobijediti širinom. Pobijedi jasnoćom, redom i jednim dobrim sljedećim korakom.', 320);

        $priority_channels = array_slice(array_values(array_filter((array) ($labels['active_channels'] ?? []))), 0, 3);
        if(empty($priority_channels)) {
            $priority_channels = ['Tvoja glavna FCC aplikacija'];
        }

        $content_ideas = [];
        if(in_array('Instagram Story', $priority_channels, true) || in_array('Instagram story', $priority_channels, true)) {
            $content_ideas[] = 'Kratki story koji vodi samo na jedan glavni blok ili Funnel, bez dodatnog objašnjavanja.';
        }
        $content_ideas[] = $goal_type === 'shop'
            ? 'Jedna jasna objava ili story koja pokazuje zašto baš taj proizvod ili ponuda vrijedi otvoriti.'
            : 'Jedan kratak sadržaj koji jasno kaže kome pomažeš i što osoba dobiva ako klikne dalje.';
        $content_ideas[] = 'Jedna jednostavna follow-up objava ili podsjetnik koji vraća ljude na isti glavni korak, bez otvaranja novih tema.';
        $content_ideas = array_slice(array_values(array_unique(array_filter($content_ideas))), 0, 5);

        $coach_ideas = [];
        if($is_contact_goal && $active_funnels === 0) {
            $coach_ideas[] = 'Dodaj Funnel i stavi ga u prvi plan umjesto da očekuješ da se ozbiljni ljudi sami snađu kroz više različitih blokova.';
        }
        if($main_app_total_blocks >= 9) {
            $coach_ideas[] = 'Sve što ne gura glavni cilj naprijed ovaj tjedan spusti niže ili privremeno ugasi.';
        }
        if($current_clicks_30d > 0 && (int) ($analytics_payload['blog_article_clicks'] ?? 0) > 0) {
            $coach_ideas[] = 'Ako članak već dobiva klikove, koristi ga kao alat povjerenja i veži ga uz jedan jasan sljedeći korak.';
        }
        if($is_low_energy) {
            $coach_ideas[] = 'Održi isti jednostavan ritam cijeli tjedan umjesto da svaki dan smišljaš novi smjer.';
        }
        if(!empty($coach_context['summary'])) {
            $coach_ideas[] = 'Zadrži kontinuitet s onim što je Coach već prepoznao: ' . fcc_ai_excerpt((string) $coach_context['summary'], 170);
        }
        $coach_ideas = array_slice(array_values(array_unique(array_filter(array_merge($coach_ideas, array_slice($guardrails, 0, 2))))), 0, 5);

        $do_not_do = [
            'Ne dodavaj nove blokove, ideje i teme koje ne hrane glavni cilj ovog tjedna.',
            'Ne objašnjavaj previše prerano ako prvi korak još nije potpuno jasan.',
            'Ne mjeri uspjeh količinom aktivnosti nego time koliko je aplikacija postala jednostavnija i jasnija.',
        ];
        $do_not_do = array_slice(array_values(array_unique(array_filter(array_merge($do_not_do, array_slice($guardrails, 0, 1))))), 0, 4);

        $daily_plan = $this->build_fallback_daily_plan(
            [
                'next_steps' => array_values(array_unique(array_filter(array_merge([$power_move], $coach_ideas, $content_ideas)))),
            ],
            $focus,
            $summary,
            $priority_channels,
            $content_ideas,
            $coach_ideas,
            $do_not_do
        );

        return [
            'headline' => $headline,
            'summary' => $summary,
            'focus' => $focus,
            'coach_intro' => $coach_intro,
            'brutal_truth' => $brutal_truth,
            'power_move' => $power_move,
            'why_this_week' => $why_this_week,
            'encouragement' => $encouragement,
            'priority_channels' => $priority_channels,
            'content_ideas' => $content_ideas,
            'coach_ideas' => $coach_ideas,
            'do_not_do' => $do_not_do,
            'daily_plan' => $daily_plan,
            'coach_context_snapshot' => [
                'has_recent_activity' => !empty($coach_context['has_recent_activity']),
                'last_touch_at' => $coach_context['last_touch_at'] ?? null,
                'summary' => (string) ($coach_context['summary'] ?? ''),
                'recent_topics' => array_values(array_slice((array) ($coach_context['recent_topics'] ?? []), 0, 3)),
                'recent_challenges' => array_values(array_slice((array) ($coach_context['recent_challenges'] ?? []), 0, 3)),
            ],
        ];
    }

    private function generate_weekly_ai_plan(array $values, array $weekly_checkin, array $weekly_checkins, array $weekly_plans, array $weekly_outcomes, array $analytics_payload, array $app_structure_payload, array $adaptive_question, array $app_reviews = [], array $coach_context = []): array {
        $credentials = $this->get_ai_credentials();

        if($credentials['api_key'] === '') {
            if($credentials['needs_personal_key']) {
                throw new \Exception(sprintf(l('account_preferences.error_message.aix.openai_api_key'), '<a href="' . url('account-preferences') . '"><strong>' . l('account_preferences.menu') . '</strong></a>'));
            }

            throw new \Exception(l('ai_plan.ai_error_missing_api_key'));
        }

        $previous_cycle_context = $this->get_previous_weekly_cycle_context($weekly_checkins, $weekly_plans, $weekly_outcomes, $weekly_checkin);
        $mentor_guidance = $this->get_mentor_ai_guidance($this->user->preferences ?? null);
        $latest_app_review = $this->get_latest_app_review($app_reviews);
        $ai_input = $this->build_weekly_ai_plan_input($values, $weekly_checkin, $analytics_payload, $app_structure_payload, $adaptive_question, $previous_cycle_context, $mentor_guidance, $latest_app_review, $coach_context);
        $ai_input = $this->sanitize_utf8_for_json($ai_input);
        $supports_image_input = $this->model_supports_image_input((string) ($credentials['model'] ?? ''));
        $focus_visual_context = (array) ($ai_input['weekly_focus_app']['visual_context'] ?? []);
        $selected_visual_url = (string) ($focus_visual_context['primary_visual_url'] ?? '');
        $selected_visual_scope = (string) ($focus_visual_context['scope'] ?? 'none');
        $selected_visual_segments = (array) ($focus_visual_context['visual_segments'] ?? []);
        $user_prompt = implode("\n\n", [
            'Na temelju tjednog check-ina, profila korisnika, analytics signala i postojece strukture aplikacije izradi konkretan 7-dnevni akcijski plan.',
            'Vrati samo JSON s kljucevima: headline, summary, focus, coach_intro, brutal_truth, power_move, why_this_week, encouragement, priority_channels, content_ideas, coach_ideas, do_not_do, daily_plan.',
            'Pravila:',
            '- Pisi kao vrhunski strategist-coach, ne kao robot. Recenice neka budu prirodne, ali zakljucci moraju biti inteligentni, jasni i jaki.',
            '- Nemoj davati slabe, predvidljive ili genericne savjete poput javi se nekim ljudima, budi aktivniji, objavi nesto ili pokusaj jos malo. Ako ne mozes biti pametniji od toga, plan nije dovoljno dobar.',
            '- Nemoj koristiti programerske, analiticke ni marketinske izraze koje pocetnik ne razumije, kao sto su direct, mobile, desktop, CTR, CTA, conversion rate i slicno.',
            '- Smijes koristiti rijec Funnel jer je to stvarni naziv gumba i funkcije u FCC-u.',
            '- Ako podatak moras spomenuti, prevedi ga u jednostavan svakodnevni jezik. Primjer: umjesto direct reci da ljudi najcesce dolaze sami, bez druge mreze ili oglasa. Umjesto mobile reci da vecina gleda preko mobitela. Umjesto CTA reci jasan poziv sto osoba treba napraviti dalje.',
            '- Rjesenja moraju biti logicna, jednostavna i laka za pratiti osobi koja nema iskustva s marketingom, ali moraju biti pametna i imati jasan razlog zasto bas to donosi rezultat.',
            '- Nemoj sastavljati gotove poruke, skripte, DM predloske ni tekstove za slanje. Fokus mora biti na idejama, smjeru, prioritetima i jednostavnim coaching prijedlozima.',
            '- coach_intro neka bude kratak uvod od 2 do 4 recenice koji osobi objasnjava sto vidis i zasto ovaj tjedan ne treba raditi previse nego tocno ono sto najvise pomaze.',
            '- brutal_truth neka bude jedna kratka, korisna i pomalo neugodna istina koju osoba treba cuti da prestane raditi stvari koje joj trenutno ne donose rezultat.',
            '- power_move neka bude jedan najjaci potez tjedna. Ne lista, nego jedna odluka ili promjena koja najvise pomice rezultat.',
            '- why_this_week neka objasni logiku plana na ljudski nacin: zasto je bas ovaj fokus dobar s obzirom na signal, kanale, publiku i trenutnu energiju.',
            '- encouragement neka bude kratka zavrsna coach poruka koja daje smjer i mirnocu, bez umjetnog hypea.',
            '- coach_ideas neka bude polje od 3 do 5 kratkih, jakih i neocitih coaching ideja. To ne smiju biti gotove poruke za kopiranje, nego smjerovi koji pokazuju zrelije razmisljanje od prosjecnog savjeta.',
            '- daily_plan mora biti polje od 7 dana, a svaki dan mora imati kljuceve day, title i tasks.',
            '- title za svaki dan neka zvuci kao smisleni dnevni fokus, ne kao mehanicki label.',
            '- tasks mora biti polje kratkih i konkretnih zadataka koje osoba stvarno moze napraviti taj dan, ali neka budu napisani prirodnim coaching jezikom, ne tehnickim ili robotskim tonom.',
            '- Ako predlazes da se osoba nekome javi, to smije biti samo kada postoji jasan razlog iz konteksta i mora biti precizno receno kome i zasto, bez generickog javi se ljudima.',
            '- Plan mora uzeti u obzir energiju, raspolozive kanale, ograniceno vrijeme, follow-up spremnost, top izvore i stvarne blokove/aplikacije koje korisnik vec ima.',
            '- Ako previous_cycle.outcome postoji, novi plan mora jasno pokazati da je naucio iz proslog tjedna: pojacaj ono sto je dalo najbolji odgovor, skrati ili ukloni ono gdje je zapelo i ugradi korisnikovu vlastitu prilagodbu za iduci tjedan.',
            '- Ako je prosli tjedan izvedba bila slaba ili plan nije ni krenuo, nemoj samo ponoviti isti pristup. Novi plan mora biti jednostavniji, uzi i realniji za osobu.',
            '- Ako je u proslog tjedna nesto dobro reagiralo, nemoj to zanemariti. Novi plan treba se nasloniti na taj signal umjesto da ide u potpuno novi smjer bez razloga.',
            '- Ako mentor_context.has_guidance postoji, uzmi to kao dodatnu procjenu mentora iz direktnog kontakta s osobom. To nije glavni orijentir, ali treba poostrinti realnost plana, razinu fokusa i procjenu discipline kada ima smisla.',
            '- Mentor smjernica je admin-only i sekundarni signal. Nemoj je slijediti slijepo ako je u ocitom sukobu sa stvarnim podacima, ali je koristi kad treba procijeniti je li osobi potreban strozi, uzi ili jednostavniji plan.',
            '- Ako coach_context.has_recent_activity = true, novi plan mora vidjeti kontinuitet s onim što je Coach već komunicirao. Ugradi zadnje teme, prepreke i preporuke kad pomažu realnijem i jačem planu.',
            '- coach_context je sekundarni signal iz stvarne komunikacije. Ako je u sukobu s realnim signalom, analytics i trenutačna live aplikacija imaju prednost.',
            '- weekly_focus_app.current_structure i main_app_structure opisuju stvarnu trenutnu live aplikaciju koju ovaj tjedan treba komentirati. To ima prednost nad starim planovima i starim analizama.',
            '- latest_app_review i previous_cycle sluze samo kao povijest. Nemoj tvrditi da nesto trenutno postoji, nedostaje ili je na vrhu samo zato sto je bilo spomenuto u starijoj analizi ili starom planu.',
            '- Ako weekly_focus_app.current_state_against_latest_review.has_changes_since_recommendation = true ili weekly_focus_app.current_state_against_previous_plan.has_changes_since_recommendation = true, to znaci da je aplikacija mijenjana nakon zadnje analize ili zadnjeg plana. U tom slucaju trenutna live aplikacija je izvor istine, a stare preporuke su samo povijest.',
            '- Ako weekly_focus_app.current_structure.position_signals i ordered_block_previews pokazuju da video, Funnel, WhatsApp ili business blok vec postoje, nemoj pisati kao da ih nema.',
            '- Ako korisnik vec ima Funnel ili Forever blokove, procijeni jesu li sada korisni ili samo kompliciraju put. Ako nema dovoljno strukture, reci sto prvo treba postaviti. Ako ima previse strukture, reci sto treba maknuti ili spustiti.',
            '- Ako input sadrzi main_app_structure.position_signals i ordered_block_previews, koristi njih kao izvor istine za stvarni redoslijed blokova. Nemoj reci da je nesto na vrhu, prvi blok ili prije videa ako to nije izricito potvrdeno tim signalima.',
            '- Ako je cilj skupljanje kontakata, regrutacija ili prijava, plan smije i treba preporuciti Funnel kao glavni tjedni fokus i glavni prvi korak aplikacije.',
            '- Nemoj preporucivati Save Contact, Contact Collector, Email Collector ni Dodaj na pocetni zaslon.',
            '- Chatbot ili AI savjetnik nije klasicni gumb u glavnom redoslijedu blokova. On je neutralan pomocni sloj koji se otvara iz male ikonice i ne treba ga tretirati kao prepreku fokusu.',
            '- Ako aplikacija ima chatbot, smijes ga spomenuti kao pomocni alat za preporuku proizvoda i usmjeravanje, ali ga nemoj isticati kao glavni problem niti kao glavni prvi korak.',
            '- Ako preporucujes Funnel, imaj na umu da Funnel u FCC-u moze imati video, ime, email, broj telefona i thank you stranicu.',
            '- Ako cilj trazi vise povjerenja, preporuci i video, jasan tekst blok i jednostavan redoslijed blokova na vrhu aplikacije.',
            '- U coach_ideas i dnevnim zadacima smijes predloziti i konkretne promjene na aplikaciji: Funnel, tekst blok, video, boje, jedan glavni gumb, drugi redoslijed blokova ili jacu WhatsApp logiku.',
            '- Ako statistika pokazuje interes, ali nema prijava, kontkata ili WhatsApp koraka, plan mora reci kako da aplikacija bolje pretvori interes u stvarni kontakt.',
            '- Ako nema dovoljno signala, plan i dalje mora biti konkretan, ali s fokusom na stvaranje kvalitetnog signala, ne na genericke savjete.',
            '- Izbjegavaj pretrpavanje. Plan treba djelovati kao da coach zna sto je najvaznije, a ne kao da zeli ugurati 30 zadataka.',
            '- Ako dobijes vizual weekly_focus_app.visual_context, koristi ga da potvrdis prvi ekran, trenutni raspored i ton aplikacije prije nego zakljucis da nesto nedostaje ili da je zastarjelo.',
            '- Nemoj koristiti markdown, code blockove ni dodatne kljuceve.',
            'Input JSON: ' . json_encode($ai_input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
        ]);
        $user_message_content = [
            [
                'type' => 'text',
                'text' => $user_prompt,
            ],
        ];

        if($supports_image_input) {
            $added_image_urls = [];

            foreach(['hero', 'middle', 'bottom'] as $segment_key) {
                $segment_payload = (array) ($selected_visual_segments[$segment_key] ?? []);
                $segment_visual_url = (string) ($segment_payload['primary_visual_url'] ?? '');

                if($segment_visual_url === '' || isset($added_image_urls[$segment_visual_url])) {
                    continue;
                }

                $user_message_content[] = [
                    'type' => 'text',
                    'text' => (string) (($segment_payload['title'] ?? $this->get_app_review_segment_title($segment_key)) . ': pregled ovog dijela aplikacije za weekly plan.'),
                ];
                $user_message_content[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $segment_visual_url,
                        'detail' => 'high',
                    ],
                ];

                $added_image_urls[$segment_visual_url] = true;
            }

            if(empty($added_image_urls) && $selected_visual_url !== '' && $selected_visual_scope !== 'none') {
                $user_message_content[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $selected_visual_url,
                        'detail' => 'high',
                    ],
                ];
            }
        }

        $response = Request::post(
            'https://api.openai.com/v1/chat/completions',
            [
                'Authorization' => 'Bearer ' . get_random_line_from_text($credentials['api_key']),
                'Content-Type' => 'application/json',
            ],
            Body::json([
                'model' => $credentials['model'],
                'response_format' => [
                    'type' => 'json_object',
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Pisi iskljucivo na hrvatskom. Ti si elitni growth coach i strateg za Forever Card Club suradnike. Nisi motivacijski chatbot nego netko tko vidi gdje curi rezultat, reze visak i bira jedan potez s najvecim leverageom. Budi iskren, ostar kad treba, ali koristan i izvediv. Vrati samo valjan JSON bez markdowna i bez dodatnih kljuceva.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $user_message_content,
                    ],
                ],
            ])
        );

        if($response->code >= 400) {
            throw new \Exception($response->body->error->message ?? l('ai_plan.ai_error_request_failed'));
        }

        $content = trim((string) ($response->body->choices[0]->message->content ?? ''));

        if(substr($content, 0, 3) === '```') {
            $content = preg_replace('/^```[a-zA-Z0-9_-]*\s*/', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);
            $content = trim($content);
        }

        $decoded_plan = $this->extract_json_from_text($content);
        $used_fallback_plan = false;

        if(!is_array($decoded_plan)) {
            $plan = $this->build_emergency_weekly_ai_plan($values, $weekly_checkin, $analytics_payload, $app_structure_payload, $previous_cycle_context, $mentor_guidance, $latest_app_review, $coach_context);
            $used_fallback_plan = true;
        } else {
            try {
                $plan = $this->validate_weekly_ai_plan_response($decoded_plan);
            } catch(\Throwable $exception) {
                $plan = $this->build_emergency_weekly_ai_plan($values, $weekly_checkin, $analytics_payload, $app_structure_payload, $previous_cycle_context, $mentor_guidance, $latest_app_review, $coach_context);
                $used_fallback_plan = true;
            }
        }
        $plan['checkin_submitted_at'] = $weekly_checkin['submitted_at'] ?? get_date();
        $plan['generated_at'] = get_date();
        $plan['model'] = $used_fallback_plan ? 'fallback_local' : $credentials['model'];
        $plan['coach_context_snapshot'] = [
            'has_recent_activity' => !empty($coach_context['has_recent_activity']),
            'last_touch_at' => $coach_context['last_touch_at'] ?? null,
            'summary' => (string) ($coach_context['summary'] ?? ''),
            'recent_topics' => array_values(array_slice((array) ($coach_context['recent_topics'] ?? []), 0, 3)),
            'recent_challenges' => array_values(array_slice((array) ($coach_context['recent_challenges'] ?? []), 0, 3)),
            'recent_assistant_guidance' => array_values(array_slice((array) ($coach_context['recent_assistant_guidance'] ?? []), 0, 3)),
        ];

        return $plan;
    }

    public function index() {
        \Altum\Authentication::guard();

        if(!\Altum\Authentication::is_admin() && empty($this->user->plan_settings->ai_growth_plan_is_enabled ?? false)) {
            Alerts::add_error(l('global.info_message.plan_feature_no_access') . (settings()->payment->is_enabled ? ' <a href="' . url('plan') . '" class="font-weight-bold text-reset">' . l('global.info_message.plan_upgrade') . '.</a>' : null));
            redirect('account-plan');
        }

        $is_app_review_page = (\Altum\Router::$controller_key ?? '') === 'ai-app-review';
        $self_route = 'ai-plan';

        $preferences = $this->get_preferences_object();
        $options = $this->get_form_options();
        $weekly_options = $this->get_weekly_form_options();
        $values = $this->get_saved_values($preferences);
        $weekly_values = $this->get_default_weekly_values();
        $weekly_checkins = $this->get_saved_weekly_checkins($preferences);
        $latest_weekly_checkin = $this->get_latest_weekly_checkin($weekly_checkins);
        $weekly_plans = $this->get_saved_weekly_plans($preferences);
        $weekly_outcomes = $this->get_saved_weekly_outcomes($preferences);
        $app_reviews = $this->get_saved_app_reviews($preferences);
        $latest_app_review_any = $this->get_latest_app_review($app_reviews);
        $app_review_job_status = $this->get_app_review_job_status($preferences);
        $current_clicks_30d = $this->get_last_30_days_shop_clicks();
        $analytics_payload = $this->get_analytics_payload($this->user->user_id, $current_clicks_30d);
        $app_structure_payload = $this->get_app_structure_payload($this->user->user_id);
        $adaptive_question = $this->get_adaptive_question($values, $current_clicks_30d, $analytics_payload);
        $latest_weekly_plan = $this->get_latest_weekly_plan($weekly_plans, $latest_weekly_checkin);
        $latest_weekly_outcome = $this->get_latest_weekly_outcome($weekly_outcomes, $latest_weekly_checkin);
        $latest_pending_outcome_plan = $this->get_latest_plan_missing_outcome($weekly_plans, $weekly_outcomes);
        $latest_pending_outcome = $this->get_weekly_outcome_for_plan($weekly_outcomes, $latest_pending_outcome_plan);
        $previous_weekly_cycle_context = $this->get_previous_weekly_cycle_context($weekly_checkins, $weekly_plans, $weekly_outcomes, $latest_weekly_checkin);
        $feedback_loop_payload = $this->get_feedback_loop_payload($previous_weekly_cycle_context);
        $coach_context_payload = $this->get_internal_coach_context_payload((int) $this->user->user_id);

        $recovered_weekly_plan_generated = false;

        if($latest_weekly_checkin && !$latest_weekly_plan) {
            $mentor_guidance = $this->get_mentor_ai_guidance($this->user->preferences ?? null);
            $recovered_plan = $this->build_recovery_weekly_plan($values, $latest_weekly_checkin, $analytics_payload, $app_structure_payload, $previous_weekly_cycle_context, $mentor_guidance, $latest_app_review_any, $coach_context_payload, 'fallback_recovery');

            $weekly_plans = $this->upsert_weekly_plan($weekly_plans, $recovered_plan);
            $preferences->leader_ai_weekly_plans = $weekly_plans;
            $recovered_weekly_plan_generated = true;

            $this->persist_ai_plan_preferences($preferences);

            $latest_weekly_plan = $this->get_latest_weekly_plan($weekly_plans, $latest_weekly_checkin);
            $latest_pending_outcome_plan = $this->get_latest_plan_missing_outcome($weekly_plans, $weekly_outcomes);
            $latest_pending_outcome = $this->get_weekly_outcome_for_plan($weekly_outcomes, $latest_pending_outcome_plan);
            $previous_weekly_cycle_context = $this->get_previous_weekly_cycle_context($weekly_checkins, $weekly_plans, $weekly_outcomes, $latest_weekly_checkin);
            $feedback_loop_payload = $this->get_feedback_loop_payload($previous_weekly_cycle_context);
        }

        $has_weekly_limits_bypass = \Altum\Authentication::is_admin();
        $ai_growth_access_payload = $this->get_ai_growth_access_payload($preferences, $app_structure_payload, $current_clicks_30d);
        $app_review_access_payload = (array) ($ai_growth_access_payload['app_review'] ?? []);
        $weekly_access_payload = (array) ($ai_growth_access_payload['weekly'] ?? []);
        if($recovered_weekly_plan_generated && !empty($weekly_access_payload['uses_starter_credit'])) {
            $preferences = $this->consume_ai_growth_starter_credit($preferences, 'weekly_plan');
            $this->persist_ai_plan_preferences($preferences);
            $this->user->preferences = $preferences;
            $ai_growth_access_payload = $this->get_ai_growth_access_payload($preferences, $app_structure_payload, $current_clicks_30d);
            $app_review_access_payload = (array) ($ai_growth_access_payload['app_review'] ?? []);
            $weekly_access_payload = (array) ($ai_growth_access_payload['weekly'] ?? []);
        }
        $growth_signal_30d = (int) ($ai_growth_access_payload['growth_signal_30d'] ?? 0);
        $growth_signal_7d = (int) ($ai_growth_access_payload['growth_signal_7d'] ?? 0);
        $cooldown_payload = $this->get_weekly_cooldown_payload($latest_weekly_checkin, (int) ($weekly_access_payload['cooldown_days'] ?? 0));
        $app_review_cooldown_payload = $this->get_cooldown_payload_by_days($latest_app_review_any['generated_at'] ?? null, (int) ($app_review_access_payload['cooldown_days'] ?? 0));
        $is_profile_complete = $this->is_profile_complete($values);
        $is_weekly_plan_eligible = $has_weekly_limits_bypass || !empty($weekly_access_payload['has_access']);
        $is_weekly_submission_locked = $has_weekly_limits_bypass ? false : $cooldown_payload['is_locked'];
        $weekly_next_checkin_at = $has_weekly_limits_bypass ? null : $cooldown_payload['next_checkin_at'];
        $is_app_review_locked = $has_weekly_limits_bypass ? false : (!empty($app_review_access_payload['can_generate']) ? $app_review_cooldown_payload['is_locked'] : true);
        $app_review_next_at = $has_weekly_limits_bypass ? null : $app_review_cooldown_payload['next_checkin_at'];
        $app_review_countdown_days = $this->get_weekly_checkin_countdown_days($app_review_next_at);

        /* Defensive unlock: if countdown already hit 0, do not keep the app review visually or functionally locked. */
        if($is_app_review_locked && $app_review_countdown_days === 0) {
            $is_app_review_locked = false;
            $app_review_next_at = null;
            $app_review_countdown_days = null;
        }
        $is_profile_complete_for_weekly = $has_weekly_limits_bypass || $is_profile_complete;
        $is_app_review_accessible = $has_weekly_limits_bypass || ($is_profile_complete && !empty($app_review_access_payload['has_access']));
        $app_review_locked_reason = !$is_profile_complete
            ? l('ai_plan.app_review_locked_entry_tooltip')
            : (!empty($app_review_access_payload['has_access'])
                ? ''
                : ($ai_growth_access_payload['is_pro']
                    ? sprintf(l('ai_plan.app_review_locked_signal'), 15, nr($growth_signal_30d))
                    : l('ai_plan.app_review_locked_pro')));
        $app_review_context = input_clean($_POST['app_review_context'] ?? '', 800);
        $requested_selected_app_id = (int) ($_POST['app_review_selected_link_id'] ?? ($_GET['app_review_selected_link_id'] ?? 0));
        $requested_app_review_generated_at = input_clean($_GET['app_review_generated_at'] ?? '', 32);
        $requested_plan_generated_at = input_clean($_GET['plan_generated_at'] ?? '', 32);
        $requested_plan_history_only = isset($_GET['plan_history']) && $_GET['plan_history'] === '1';

        if($is_app_review_page && empty($_POST) && !isset($_GET['app_review_status'])) {
            $redirect_query = $_GET;
            $redirect_query['section'] = 'app_review';
            redirect('ai-plan' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
        }

        if(!$requested_selected_app_id && !empty($latest_app_review_any['selected_link_id']) && !empty($app_review_access_payload['can_select_any_app'])) {
            $requested_selected_app_id = (int) ($latest_app_review_any['selected_link_id'] ?? 0);
        }

        if(!empty($_GET['app_review_done'])) {
            Alerts::add_success(l('ai_plan.app_review_success_message'));
        }

        if((($is_app_review_page || (($_GET['section'] ?? '') === 'app_review')) && !$is_app_review_accessible)) {
            Alerts::add_info($app_review_locked_reason ?: l('ai_plan.app_review_locked_entry_tooltip'));
            redirect('ai-plan?section=profile#ai-plan-profile-start');
        }

        if(isset($_GET['app_review_status'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status' => (string) ($app_review_job_status['status'] ?? 'idle'),
                'pending' => (string) ($app_review_job_status['status'] ?? 'idle') === 'pending',
                'job_id' => (string) ($app_review_job_status['job_id'] ?? ''),
                'error_message' => (string) ($app_review_job_status['error_message'] ?? ''),
                'latest_generated_at' => $latest_app_review_any['generated_at'] ?? null,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            die();
        }

        if(!empty($_GET['app_review_processing'])) {
            $job_status_value = (string) ($app_review_job_status['status'] ?? 'idle');

            if($job_status_value === 'completed') {
                $preferences = $this->set_app_review_job_status($preferences, [
                    'status' => 'idle',
                    'job_id' => '',
                    'started_at' => null,
                    'completed_at' => null,
                    'selected_link_id' => (int) ($app_review_job_status['selected_link_id'] ?? 0),
                    'error_message' => '',
                ]);
                $this->persist_ai_plan_preferences($preferences);

                redirect('ai-plan?section=app_review&app_review_done=1');
            }

            if($job_status_value === 'failed') {
                Alerts::add_error((string) ($app_review_job_status['error_message'] ?? l('ai_plan.ai_error_request_failed')));

                $preferences = $this->set_app_review_job_status($preferences, [
                    'status' => 'idle',
                    'job_id' => '',
                    'started_at' => null,
                    'completed_at' => null,
                    'selected_link_id' => (int) ($app_review_job_status['selected_link_id'] ?? 0),
                    'error_message' => '',
                ]);
                $this->persist_ai_plan_preferences($preferences);

                redirect('ai-plan?section=app_review');
            }
        }

        $selected_app = !empty($app_review_access_payload['can_select_any_app'])
            ? $this->get_selected_app($app_structure_payload, $requested_selected_app_id)
            : $this->get_main_app_for_review($app_structure_payload);

        $selected_app = $selected_app ?? $this->get_main_app_for_review($app_structure_payload);
        $selected_app_id = (int) ($selected_app['link_id'] ?? 0);
        $app_review_block_attribution_payload = $this->get_app_review_block_attribution_payload((array) $selected_app);
        $app_review_history = $this->get_app_reviews_for_link($app_reviews, $selected_app_id);
        $selected_app_review = $this->get_latest_app_review_for_link($app_reviews, $selected_app_id);
        $history_app_review = $this->get_app_review_for_link_by_generated_at($app_reviews, $selected_app_id, $requested_app_review_generated_at);
        $latest_app_review = ($is_app_review_page || (($_GET['section'] ?? '') === 'app_review'))
            ? (
                $history_app_review
                ?? $selected_app_review
                ?? $latest_app_review_any
            )
            : ($selected_app_review ?? $latest_app_review_any);
        $app_review_editor_action_review = $selected_app_review;

        if(!$app_review_editor_action_review && !empty($latest_app_review) && (int) ($latest_app_review['selected_link_id'] ?? 0) === $selected_app_id) {
            $app_review_editor_action_review = $latest_app_review;
        }

        $selected_weekly_plan = $this->get_weekly_plan_by_generated_at($weekly_plans, $requested_plan_generated_at);
        $display_weekly_plan = $requested_plan_history_only ? null : ($selected_weekly_plan ?? $latest_weekly_plan);
        $display_weekly_outcome = $this->get_weekly_outcome_for_plan($weekly_outcomes, $display_weekly_plan);
        $app_review_quality_payload = $this->get_app_review_quality_payload($selected_app, $current_clicks_30d);
        $app_review_evolution_display_payload = $this->get_app_review_display_evolution_payload($selected_app_id, (array) ($app_review_quality_payload['performance'] ?? []), $app_review_block_attribution_payload);
        $signal_summary_payload = $this->get_user_signal_summary_payload($values, $analytics_payload, $growth_signal_30d, $is_weekly_plan_eligible);
        $available_app_options = [];

        foreach(($app_structure_payload['apps'] ?? []) as $app_option) {
            $available_app_options[] = [
                'link_id' => (int) ($app_option['link_id'] ?? 0),
                'label' => (string) (($app_option['name'] ?? '') ?: ($app_option['url'] ?? '')),
                'url' => (string) ($app_option['url'] ?? ''),
                'public_url' => (string) ($app_option['public_url'] ?? ''),
            ];
        }

        if(!empty($_POST)) {
            if(isset($_POST['save_profile'])) {
                $values['primary_goal'] = $this->normalize_single_choice($_POST['primary_goal'] ?? null, $options['primary_goal']);
                $values['priority_offer'] = $this->normalize_single_choice($_POST['priority_offer'] ?? null, $options['priority_offer']);
                $values['active_channels'] = $this->normalize_multiple_choice($_POST['active_channels'] ?? [], $options['active_channels']);
                $values['available_time'] = $this->normalize_single_choice($_POST['available_time'] ?? null, $options['available_time']);
                $values['biggest_blocker'] = $this->normalize_single_choice($_POST['biggest_blocker'] ?? null, $options['biggest_blocker']);
                $values['communication_style'] = $this->normalize_single_choice($_POST['communication_style'] ?? null, $options['communication_style']);
                $values['follow_up_readiness'] = $this->normalize_single_choice($_POST['follow_up_readiness'] ?? null, $options['follow_up_readiness']);
                $values['weekly_change'] = $this->normalize_single_choice($_POST['weekly_change'] ?? null, $options['weekly_change']);
                $values['audience_focus'] = input_clean($_POST['audience_focus'] ?? '', 120);
                $values['product_focus'] = input_clean($_POST['product_focus'] ?? '', 120);
                $values['visual_tone_preference'] = input_clean($_POST['visual_tone_preference'] ?? '', 160);
                $values['notes'] = input_clean($_POST['notes'] ?? '', 1000);

                if(!$values['primary_goal']) Alerts::add_field_error('primary_goal', l('ai_plan.error.primary_goal'));
                if(!$values['priority_offer']) Alerts::add_field_error('priority_offer', l('ai_plan.error.priority_offer'));
                if(empty($values['active_channels'])) Alerts::add_field_error('active_channels', l('ai_plan.error.active_channels'));
                if(!$values['available_time']) Alerts::add_field_error('available_time', l('ai_plan.error.available_time'));
                if(!$values['biggest_blocker']) Alerts::add_field_error('biggest_blocker', l('ai_plan.error.biggest_blocker'));
                if(!$values['communication_style']) Alerts::add_field_error('communication_style', l('ai_plan.error.communication_style'));
                if(!$values['follow_up_readiness']) Alerts::add_field_error('follow_up_readiness', l('ai_plan.error.follow_up_readiness'));
                if(!$values['weekly_change']) Alerts::add_field_error('weekly_change', l('ai_plan.error.weekly_change'));

                if(!\Altum\Csrf::check()) {
                    Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                }

                if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                    $preferences->leader_ai_profile = (object) [
                        'primary_goal' => $values['primary_goal'],
                        'priority_offer' => $values['priority_offer'],
                        'active_channels' => $values['active_channels'],
                        'available_time' => $values['available_time'],
                        'biggest_blocker' => $values['biggest_blocker'],
                        'communication_style' => $values['communication_style'],
                        'follow_up_readiness' => $values['follow_up_readiness'],
                        'weekly_change' => $values['weekly_change'],
                        'audience_focus' => $values['audience_focus'],
                        'product_focus' => $values['product_focus'],
                        'visual_tone_preference' => $values['visual_tone_preference'],
                        'notes' => $values['notes'],
                        'updated_at' => get_date(),
                        'phase' => 1,
                    ];

                    db()->where('user_id', $this->user->user_id)->update('users', [
                        'preferences' => json_encode($preferences),
                    ]);

                    cache()->deleteItemsByTag('user_id=' . $this->user->user_id);
                    cache()->deleteItem('user?user_id=' . $this->user->user_id);

                    \Altum\Logger::users($this->user->user_id, 'ai_plan.profile_updated');

                    Alerts::add_success(l('ai_plan.success_message'));

                    redirect('ai-plan?section=app_review#ai-plan-app-review');
                }
            }

            if(isset($_POST['save_weekly_checkin'])) {
                $weekly_values['weekly_priority'] = $this->normalize_single_choice($_POST['weekly_priority'] ?? null, $weekly_options['weekly_priority']);
                $weekly_values['content_commitment'] = $this->normalize_single_choice($_POST['content_commitment'] ?? null, $weekly_options['content_commitment']);
                $weekly_values['follow_up_volume'] = $this->normalize_single_choice($_POST['follow_up_volume'] ?? null, $weekly_options['follow_up_volume']);
                $weekly_values['ai_need'] = $this->normalize_single_choice($this->normalize_ai_need_value($_POST['ai_need'] ?? null), $weekly_options['ai_need']);
                $weekly_values['weekly_energy'] = $this->normalize_single_choice($_POST['weekly_energy'] ?? null, $weekly_options['weekly_energy']);
                $weekly_values['weekly_context'] = input_clean($_POST['weekly_context'] ?? '', 800);
                $weekly_values['adaptive_question_key'] = (string) ($adaptive_question['key'] ?? '');
                $weekly_values['adaptive_answer'] = input_clean($_POST['adaptive_answer'] ?? '', 800);

                if(!$weekly_values['weekly_priority']) Alerts::add_field_error('weekly_priority', l('ai_plan.error.weekly_priority'));
                if(!$weekly_values['content_commitment']) Alerts::add_field_error('content_commitment', l('ai_plan.error.content_commitment'));
                if(!$weekly_values['follow_up_volume']) Alerts::add_field_error('follow_up_volume', l('ai_plan.error.follow_up_volume'));
                if(!$weekly_values['ai_need']) Alerts::add_field_error('ai_need', l('ai_plan.error.ai_need'));
                if(!$weekly_values['weekly_energy']) Alerts::add_field_error('weekly_energy', l('ai_plan.error.weekly_energy'));
                if(!$weekly_values['adaptive_answer']) Alerts::add_field_error('adaptive_answer', l('ai_plan.error.adaptive_answer'));

                if(!\Altum\Csrf::check()) {
                    Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                }

                if(!$is_profile_complete_for_weekly) {
                    Alerts::add_error(l('ai_plan.weekly_locked_profile'));
                }

                if(!$is_weekly_plan_eligible) {
                    Alerts::add_error($ai_growth_access_payload['is_pro']
                        ? sprintf(l('ai_plan.weekly_locked_signal'), 15, nr($growth_signal_30d))
                        : l('ai_plan.weekly_locked_pro'));
                }

                if($is_weekly_submission_locked) {
                    Alerts::add_error(sprintf(l('ai_plan.weekly_locked_cooldown'), \Altum\Date::get($cooldown_payload['next_checkin_at'], 2)));
                }

                if($latest_pending_outcome_plan) {
                    Alerts::add_error('Prvo zatvori prethodni tjedan i spremi rezultat prije novog tjednog unosa.');
                }

                if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                    $new_checkin = [
                        'weekly_priority' => $weekly_values['weekly_priority'],
                        'content_commitment' => $weekly_values['content_commitment'],
                        'follow_up_volume' => $weekly_values['follow_up_volume'],
                        'ai_need' => $weekly_values['ai_need'],
                        'weekly_energy' => $weekly_values['weekly_energy'],
                        'weekly_context' => $weekly_values['weekly_context'],
                        'adaptive_question_key' => $weekly_values['adaptive_question_key'],
                        'adaptive_answer' => $weekly_values['adaptive_answer'],
                        'submitted_at' => get_date(),
                    ];

                    array_unshift($weekly_checkins, $new_checkin);
                    $weekly_checkins = array_slice($weekly_checkins, 0, 12);
                    $preferences->leader_ai_weekly_checkins = $weekly_checkins;
                    $checkin_persisted = $this->persist_ai_plan_preferences($preferences);
                    $this->user->preferences = $preferences;

                    if(!$checkin_persisted) {
                        Alerts::add_error(l('ai_plan.preferences_persist_failed_message'));
                        redirect('ai-plan?section=weekly');
                    }

                    $ai_plan_generated = false;
                    $used_weekly_plan_fallback = false;

                    try {
                        $new_weekly_plan = $this->generate_weekly_ai_plan($values, $new_checkin, $weekly_checkins, $weekly_plans, $weekly_outcomes, $analytics_payload, $app_structure_payload, $adaptive_question, $app_reviews, $coach_context_payload);
                        $ai_plan_generated = true;
                    } catch(\Throwable $exception) {
                        $mentor_guidance = $this->get_mentor_ai_guidance($this->user->preferences ?? null);
                        $new_weekly_plan = $this->build_recovery_weekly_plan($values, $new_checkin, $analytics_payload, $app_structure_payload, $this->get_previous_weekly_cycle_context($weekly_checkins, $weekly_plans, $weekly_outcomes, $new_checkin), $mentor_guidance, $this->get_latest_app_review($app_reviews), $coach_context_payload, 'fallback_after_exception');
                        $ai_plan_generated = true;
                        $used_weekly_plan_fallback = true;

                        \Altum\Logger::users($this->user->user_id, 'ai_plan.weekly_plan_fallback_after_exception');
                    }

                    if($ai_plan_generated) {
                        $weekly_plans = $this->upsert_weekly_plan($weekly_plans, $new_weekly_plan);
                        $preferences->leader_ai_weekly_plans = $weekly_plans;

                        if(!empty($weekly_access_payload['uses_starter_credit'])) {
                            $preferences = $this->consume_ai_growth_starter_credit($preferences, 'weekly_plan');
                        }

                        $plan_persisted = $this->persist_ai_plan_preferences($preferences);
                        $this->user->preferences = $preferences;

                        if(!$plan_persisted) {
                            Alerts::add_error(l('ai_plan.preferences_persist_failed_message'));
                            redirect('ai-plan?section=plan');
                        }
                    }

                    \Altum\Logger::users($this->user->user_id, 'ai_plan.weekly_checkin_saved');

                    Alerts::add_success($ai_plan_generated ? l('ai_plan.weekly_success_message_phase_3') : l('ai_plan.weekly_success_message'));

                    if($used_weekly_plan_fallback) {
                        Alerts::add_info(l('ai_plan.weekly_fallback_message'));
                    }

                    redirect('ai-plan?section=plan');
                }
            }

            if(isset($_POST['generate_app_review'])) {
                if(!\Altum\Csrf::check()) {
                    Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                }

                if(!$is_profile_complete_for_weekly) {
                    Alerts::add_error(l('ai_plan.app_review_locked_profile'));
                }

                if(empty($app_review_access_payload['can_generate'])) {
                    Alerts::add_error($ai_growth_access_payload['is_pro']
                        ? sprintf(l('ai_plan.app_review_locked_signal'), 15, nr($growth_signal_30d))
                        : l('ai_plan.app_review_locked_pro'));
                }

                if(!empty($app_review_access_payload['can_generate']) && $is_app_review_locked) {
                    Alerts::add_error(sprintf(l('ai_plan.app_review_locked_cooldown'), \Altum\Date::get($app_review_cooldown_payload['next_checkin_at'], 2)));
                }

                if($app_review_access_payload['can_select_any_app']) {
                    if(!$selected_app_id) {
                        Alerts::add_error(l('ai_plan.app_review_app_required'));
                    }
                } else {
                    $selected_app = $this->get_main_app_for_review($app_structure_payload);
                    $selected_app_id = (int) ($selected_app['link_id'] ?? 0);
                }

                if(!$selected_app_id) {
                    Alerts::add_error(l('ai_plan.app_review_app_required'));
                }

                if(!Alerts::has_errors()) {
                    $generated_review_at = '';
                    $previous_app_review = $this->get_latest_app_review_for_link($app_reviews, $selected_app_id);

                    try {
                        $new_app_review = $this->generate_app_review($values, $analytics_payload, $app_structure_payload, $current_clicks_30d, $app_review_context, $selected_app, $previous_app_review, $coach_context_payload);
                        $app_reviews = $this->upsert_app_review($app_reviews, $new_app_review);
                        $preferences->leader_ai_app_reviews = $app_reviews;
                        $preferences = $this->sync_app_review_assets_to_editor($selected_app_id, $new_app_review, $preferences);
                        if(!empty($app_review_access_payload['uses_starter_credit'])) {
                            $preferences = $this->consume_ai_growth_starter_credit($preferences, 'app_review');
                        }
                        $preferences = $this->set_app_review_job_status($preferences, [
                            'status' => 'idle',
                            'job_id' => '',
                            'started_at' => null,
                            'completed_at' => null,
                            'selected_link_id' => $selected_app_id,
                            'error_message' => '',
                        ]);
                        $app_review_persisted = $this->persist_ai_plan_preferences($preferences);

                        if(!$app_review_persisted) {
                            \Altum\Logger::users($this->user->user_id, 'ai_plan.app_review_persist_failed');
                            Alerts::add_error(l('ai_plan.app_review_persist_failed_message'));
                        } else {
                            $generated_review_at = (string) ($new_app_review['generated_at'] ?? '');
                            \Altum\Logger::users($this->user->user_id, 'ai_plan.app_review_generated');
                            Alerts::add_success(l('ai_plan.app_review_success_message'));
                        }
                    } catch(\Throwable $exception) {
                        $preferences = $this->set_app_review_job_status($preferences, [
                            'status' => 'idle',
                            'job_id' => '',
                            'started_at' => null,
                            'completed_at' => null,
                            'selected_link_id' => $selected_app_id,
                            'error_message' => '',
                        ]);
                        $this->persist_ai_plan_preferences($preferences);
                        Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('ai_plan.ai_error_request_failed'));
                    }

                    $redirect_query = [];

                    if($selected_app_id && !empty($app_review_access_payload['can_select_any_app'])) {
                        $redirect_query['app_review_selected_link_id'] = $selected_app_id;
                    }

                    if($generated_review_at !== '') {
                        $redirect_query['app_review_generated_at'] = $generated_review_at;
                    }

                    $redirect_query['section'] = 'app_review';
                    redirect('ai-plan' . (!empty($redirect_query) ? '?' . http_build_query($redirect_query) : ''));
                }
            }

            if(isset($_POST['regenerate_ai_plan'])) {
                if(!\Altum\Csrf::check()) {
                    Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                } else {
                    Alerts::add_info(l('ai_plan.plan_refresh_requires_weekly'));
                    redirect('ai-plan');
                }
            }

            if(isset($_POST['save_weekly_outcome'])) {
                $allowed_completion_levels = ['strong_progress', 'partial_progress', 'low_execution', 'not_started'];
                $allowed_palette_feedback = ['love_keep', 'good_refine', 'new_direction', 'not_applied'];
                $completion_level = $this->normalize_single_choice($_POST['completion_level'] ?? null, $allowed_completion_levels);
                $best_response = input_clean($_POST['best_response'] ?? '', 800);
                $main_blocker_now = input_clean($_POST['main_blocker_now'] ?? '', 800);
                $biggest_lesson = input_clean($_POST['biggest_lesson'] ?? '', 800);
                $next_adjustment = input_clean($_POST['next_adjustment'] ?? '', 800);
                $palette_feedback = $this->normalize_single_choice($_POST['palette_feedback'] ?? null, $allowed_palette_feedback);
                $palette_feedback_note = input_clean($_POST['palette_feedback_note'] ?? '', 500);

                if(!$completion_level) Alerts::add_field_error('completion_level', l('ai_plan.error.completion_level'));
                if(!$best_response) Alerts::add_field_error('best_response', l('ai_plan.error.best_response'));
                if(!$main_blocker_now) Alerts::add_field_error('main_blocker_now', l('ai_plan.error.main_blocker_now'));
                if(!$biggest_lesson) Alerts::add_field_error('biggest_lesson', l('ai_plan.error.biggest_lesson'));
                if(!$next_adjustment) Alerts::add_field_error('next_adjustment', l('ai_plan.error.next_adjustment'));
                if(!$palette_feedback) Alerts::add_field_error('palette_feedback', l('ai_plan.error.palette_feedback'));

                if(!\Altum\Csrf::check()) {
                    Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                } elseif(!$latest_pending_outcome_plan && (!$latest_weekly_checkin || !$latest_weekly_plan)) {
                    Alerts::add_error(l('ai_plan.outcome_locked'));
                }

                if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                    $target_plan_generated_at = input_clean($_POST['outcome_plan_generated_at'] ?? '', 32);
                    $target_checkin_submitted_at = input_clean($_POST['outcome_checkin_submitted_at'] ?? '', 32);
                    $target_weekly_plan = $target_plan_generated_at !== ''
                        ? $this->get_weekly_plan_by_generated_at($weekly_plans, $target_plan_generated_at)
                        : ($latest_pending_outcome_plan ?? $latest_weekly_plan);

                    if(!$target_weekly_plan) {
                        Alerts::add_error(l('ai_plan.outcome_locked'));
                    }
                }

                if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                    $target_checkin_submitted_at = $target_checkin_submitted_at !== ''
                        ? $target_checkin_submitted_at
                        : (string) ($target_weekly_plan['checkin_submitted_at'] ?? '');
                    $target_selected_link_id = max(0, (int) ($_POST['outcome_selected_link_id'] ?? 0));
                    $target_app_review_generated_at = input_clean($_POST['outcome_app_review_generated_at'] ?? '', 32);
                    $target_app_review_review_key = input_clean($_POST['outcome_app_review_review_key'] ?? '', 64);
                    if($target_app_review_review_key === '' && $target_app_review_generated_at !== '') {
                        $target_app_review_review_key = $target_app_review_generated_at;
                    }

                    $new_outcome = [
                        'checkin_submitted_at' => $target_checkin_submitted_at !== '' ? $target_checkin_submitted_at : null,
                        'plan_generated_at' => (string) ($target_weekly_plan['generated_at'] ?? ''),
                        'selected_link_id' => $target_selected_link_id,
                        'app_review_generated_at' => $target_app_review_generated_at !== '' ? $target_app_review_generated_at : null,
                        'app_review_review_key' => $target_app_review_review_key,
                        'completion_level' => $completion_level,
                        'best_response' => $best_response,
                        'main_blocker_now' => $main_blocker_now,
                        'biggest_lesson' => $biggest_lesson,
                        'next_adjustment' => $next_adjustment,
                        'palette_feedback' => $palette_feedback,
                        'palette_feedback_note' => $palette_feedback_note,
                        'palette_decision' => $this->get_palette_feedback_decision($palette_feedback),
                        'submitted_at' => get_date(),
                    ];

                    $weekly_outcomes = array_values(array_filter($weekly_outcomes, static function($outcome) use ($new_outcome) {
                        $same_checkin = (string) ($outcome['checkin_submitted_at'] ?? '') !== '' && (string) ($outcome['checkin_submitted_at'] ?? '') === (string) ($new_outcome['checkin_submitted_at'] ?? '');
                        $same_plan = (string) ($outcome['plan_generated_at'] ?? '') !== '' && (string) ($outcome['plan_generated_at'] ?? '') === (string) ($new_outcome['plan_generated_at'] ?? '');

                        return !$same_checkin && !$same_plan;
                    }));

                    array_unshift($weekly_outcomes, $new_outcome);
                    $weekly_outcomes = array_slice($weekly_outcomes, 0, 12);
                    $preferences->leader_ai_weekly_outcomes = $weekly_outcomes;

                    db()->where('user_id', $this->user->user_id)->update('users', [
                        'preferences' => json_encode($preferences),
                    ]);

                    cache()->deleteItemsByTag('user_id=' . $this->user->user_id);
                    cache()->deleteItem('user?user_id=' . $this->user->user_id);

                    \Altum\Logger::users($this->user->user_id, 'ai_plan.weekly_outcome_saved');
                    Alerts::add_success(l('ai_plan.outcome_saved'));

                    $outcome_redirect = (!$is_weekly_submission_locked && $is_weekly_plan_eligible) ? 'ai-plan?section=weekly' : 'ai-plan?section=plan';
                    redirect($outcome_redirect);
                }
            }
        }

        $data = [
            'feature_is_available' => true,
            'is_app_review_page' => $is_app_review_page,
            'self_route' => $self_route,
            'app_review_page_url' => url('ai-plan?section=app_review#ai-plan-app-review'),
            'app_review_is_accessible' => $is_app_review_accessible,
            'app_review_locked_reason' => $app_review_locked_reason,
            'values' => $values,
            'options' => $options,
            'weekly_values' => $weekly_values,
            'weekly_options' => $weekly_options,
            'weekly_checkins' => $weekly_checkins,
            'latest_weekly_checkin' => $latest_weekly_checkin,
            'weekly_plans' => $weekly_plans,
            'latest_weekly_plan' => $latest_weekly_plan,
            'display_weekly_plan' => $display_weekly_plan,
            'weekly_outcomes' => $weekly_outcomes,
            'latest_weekly_outcome' => $latest_weekly_outcome,
            'latest_pending_outcome_plan' => $latest_pending_outcome_plan,
            'latest_pending_outcome' => $latest_pending_outcome,
            'display_weekly_outcome' => $display_weekly_outcome,
            'plan_active_generated_at' => (string) ($display_weekly_plan['generated_at'] ?? ''),
            'plan_history_only' => $requested_plan_history_only,
            'app_reviews' => $app_reviews,
            'app_review_history' => $app_review_history,
            'latest_app_review' => $latest_app_review,
            'app_review_job_status' => $app_review_job_status,
            'selected_app_review' => $selected_app_review,
            'history_app_review' => $history_app_review,
            'app_review_context' => $app_review_context,
            'app_review_access_payload' => $app_review_access_payload,
            'ai_growth_access_payload' => $ai_growth_access_payload,
            'has_admin_testing_access' => $has_weekly_limits_bypass,
            'app_review_available_apps' => $available_app_options,
            'app_review_selected_link_id' => $selected_app_id,
            'app_review_active_generated_at' => (string) ($latest_app_review['generated_at'] ?? ''),
            'app_review_selected_app' => $selected_app,
            'app_review_quality_payload' => $app_review_quality_payload,
            'app_review_evolution_payload' => $app_review_evolution_display_payload,
            'app_review_block_attribution_payload' => $app_review_block_attribution_payload,
            'app_review_editor_actions' => $this->get_app_review_editor_actions_payload($selected_app_id, $app_review_editor_action_review),
            'feedback_loop_payload' => $feedback_loop_payload,
            'current_clicks_30d' => $current_clicks_30d,
            'growth_signal_30d' => $growth_signal_30d,
            'growth_signal_7d' => $growth_signal_7d,
            'coach_mode_payload' => fcc_ai_get_internal_coach_mode_payload($this->user, \Altum\Language::$code ?? 'hr'),
            'analytics_payload' => $analytics_payload,
            'signal_summary_payload' => $signal_summary_payload,
            'app_structure_payload' => $app_structure_payload,
            'adaptive_question' => $adaptive_question,
            'is_weekly_plan_eligible' => $is_weekly_plan_eligible,
            'is_profile_complete' => $is_profile_complete,
            'is_profile_complete_for_weekly' => $is_profile_complete_for_weekly,
            'weekly_is_locked' => $is_weekly_submission_locked,
            'weekly_next_checkin_at' => $weekly_next_checkin_at,
            'weekly_countdown_days' => $this->get_weekly_checkin_countdown_days($weekly_next_checkin_at),
            'app_review_is_locked' => $is_app_review_locked,
            'app_review_next_at' => $app_review_next_at,
            'app_review_countdown_days' => $app_review_countdown_days,
            'app_review_status_url' => url('ai-plan?section=app_review&app_review_status=1'),
            'phases' => [
                ['number' => 1, 'title_key' => 'ai_plan.phase_1_title', 'text_key' => 'ai_plan.phase_1_text', 'is_active' => false],
                ['number' => 2, 'title_key' => 'ai_plan.phase_2_title', 'text_key' => 'ai_plan.phase_2_text', 'is_active' => false],
                ['number' => 3, 'title_key' => 'ai_plan.phase_3_title', 'text_key' => 'ai_plan.phase_3_text', 'is_active' => true],
                ['number' => 4, 'title_key' => 'ai_plan.phase_4_title', 'text_key' => 'ai_plan.phase_4_text', 'is_active' => false],
            ],
        ];

        $view = new \Altum\View('ai-plan/index', (array) $this);
        $this->add_view_content('content', $view->run($data));
    }
}
/* /Custom code: FC-2026-03-31 */
