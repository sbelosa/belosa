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
        if(\Altum\Authentication::is_admin()) {
            return true;
        }

        if(empty($this->user->plan_settings->ai_growth_plan_is_enabled ?? false)) {
            return false;
        }

        $plan_expiration_date = (string) ($this->user->plan_expiration_date ?? '');

        if($plan_expiration_date === '') {
            return true;
        }

        try {
            return (new \DateTimeImmutable($plan_expiration_date)) >= (new \DateTimeImmutable());
        } catch(\Throwable $exception) {
            return false;
        }
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
        $manual_tier = (string) ($access_settings['manual_tier'] ?? '');
        $manual_unlocked_at = (string) ($access_settings['manual_unlocked_at'] ?? '');

        if($manual_tier === '' || $manual_unlocked_at === '') {
            return '';
        }

        try {
            $expires_at = (new \DateTimeImmutable($manual_unlocked_at))->modify('+30 days');
            return $expires_at >= (new \DateTimeImmutable()) ? $manual_tier : '';
        } catch(\Throwable $exception) {
            return '';
        }
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
                'completion_level' => (string) ($outcome['completion_level'] ?? ''),
                'best_response' => (string) ($outcome['best_response'] ?? ''),
                'main_blocker_now' => (string) ($outcome['main_blocker_now'] ?? ''),
                'biggest_lesson' => (string) ($outcome['biggest_lesson'] ?? ''),
                'next_adjustment' => (string) ($outcome['next_adjustment'] ?? ''),
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
        ];
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

            $normalized[] = [
                'generated_at' => $review['generated_at'] ?? null,
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
                'ideal_block_order' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['ideal_block_order'] ?? []), 'is_scalar'))),
                'design_notes' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['design_notes'] ?? []), 'is_scalar'))),
                'keep_doing' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['keep_doing'] ?? []), 'is_scalar'))),
                'funnel_blueprint' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['funnel_blueprint'] ?? []), 'is_scalar'))),
                'color_palette' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['color_palette'] ?? []), 'is_scalar'))),
                'trust_builders' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['trust_builders'] ?? []), 'is_scalar'))),
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

    private function persist_ai_plan_preferences(\stdClass $preferences): void {
        db()->where('user_id', $this->user->user_id)->update('users', [
            'preferences' => json_encode($preferences),
        ]);

        cache()->deleteItemsByTag('user_id=' . $this->user->user_id);
        cache()->deleteItem('user?user_id=' . $this->user->user_id);
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

    private function get_ai_growth_signal_payload(array $app_structure_payload, int $current_clicks_30d): array {
        $main_app = $this->get_main_app_for_review($app_structure_payload) ?? [];
        $performance = $this->get_app_review_performance_snapshot($main_app);
        $shop_contacts = (int) ($performance['shop_contacts_30d'] ?? $current_clicks_30d);
        $whatsapp_contacts = (int) ($performance['whatsapp_contacts_30d'] ?? 0);
        $funnel_registrations = (int) ($performance['funnel_registrations_30d'] ?? 0);
        $growth_signal_30d = $shop_contacts + $whatsapp_contacts + $funnel_registrations;

        return [
            'growth_signal_30d' => $growth_signal_30d,
            'shop_contacts_30d' => $shop_contacts,
            'whatsapp_contacts_30d' => $whatsapp_contacts,
            'funnel_registrations_30d' => $funnel_registrations,
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
                'growth_signal_30d' => 999,
                'signal_breakdown' => [
                    'shop_contacts_30d' => 999,
                    'whatsapp_contacts_30d' => 999,
                    'funnel_registrations_30d' => 999,
                ],
                'starter' => [
                    'app_review_used' => 0,
                    'weekly_plan_used' => 0,
                    'app_review_remaining' => 1,
                    'weekly_plan_remaining' => 1,
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
        $manual_tier = $this->get_active_manual_ai_tier($access_settings);
        $has_active_signal = $growth_signal_30d >= 15;
        $has_vip_signal = $growth_signal_30d >= 50;

        $tier = 'none';
        if($is_pro) {
            $tier = 'pro_start';

            if($has_vip_signal || $manual_tier === 'pro_vip') {
                $tier = 'pro_vip';
            } elseif($has_active_signal || in_array($manual_tier, ['pro_active', 'pro_vip'], true)) {
                $tier = 'pro_active';
            }
        }

        $starter_app_review_remaining = $is_pro ? max(0, 1 - (int) ($access_settings['starter_app_review_used'] ?? 0)) : 0;
        $starter_weekly_remaining = $is_pro ? max(0, 1 - (int) ($access_settings['starter_weekly_plan_used'] ?? 0)) : 0;
        $has_recurring_weekly = in_array($tier, ['pro_active', 'pro_vip'], true);
        $has_recurring_app_review = in_array($tier, ['pro_active', 'pro_vip'], true);
        $app_review_cooldown_days = $tier === 'pro_vip' ? 7 : ($tier === 'pro_active' ? 14 : 0);

        return [
            'tier' => $tier,
            'is_admin_testing' => false,
            'is_pro' => $is_pro,
            'growth_signal_30d' => $growth_signal_30d,
            'signal_breakdown' => [
                'shop_contacts_30d' => (int) ($signal_payload['shop_contacts_30d'] ?? 0),
                'whatsapp_contacts_30d' => (int) ($signal_payload['whatsapp_contacts_30d'] ?? 0),
                'funnel_registrations_30d' => (int) ($signal_payload['funnel_registrations_30d'] ?? 0),
            ],
            'starter' => [
                'app_review_used' => (int) ($access_settings['starter_app_review_used'] ?? 0),
                'weekly_plan_used' => (int) ($access_settings['starter_weekly_plan_used'] ?? 0),
                'app_review_remaining' => $starter_app_review_remaining,
                'weekly_plan_remaining' => $starter_weekly_remaining,
            ],
            'weekly' => [
                'has_access' => $is_pro && ($starter_weekly_remaining > 0 || $has_recurring_weekly),
                'uses_starter_credit' => $is_pro && $starter_weekly_remaining > 0 && !$has_recurring_weekly,
                'is_recurring' => $has_recurring_weekly,
                'cooldown_days' => $has_recurring_weekly ? 7 : 0,
            ],
            'app_review' => [
                'has_access' => $is_pro,
                'can_generate' => $is_pro && ($starter_app_review_remaining > 0 || $has_recurring_app_review),
                'uses_starter_credit' => $is_pro && $starter_app_review_remaining > 0 && !$has_recurring_app_review,
                'is_recurring' => $has_recurring_app_review,
                'cooldown_days' => $app_review_cooldown_days,
                'can_select_any_app' => $is_pro && $has_recurring_app_review && $multi_app_mode_enabled,
                'multi_app_mode_enabled' => $multi_app_mode_enabled,
                'is_phase_one_main_app_mode' => !$multi_app_mode_enabled || !$has_recurring_app_review,
                'plan_label_key' => $tier === 'pro_vip'
                    ? 'ai_plan.app_review_plan_vip'
                    : ($tier === 'pro_active' ? 'ai_plan.app_review_plan_active' : 'ai_plan.app_review_plan_beginner'),
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

    private function get_app_review_evolution_payload(array $current_performance, ?array $previous_review = null): array {
        $default_snapshot = [
            'shop_contacts_30d' => 0,
            'whatsapp_contacts_30d' => 0,
            'product_clicks_30d' => 0,
            'funnel_registrations_30d' => 0,
            'weighted_signal_score' => 0,
        ];

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
                'current_snapshot' => array_merge($default_snapshot, $current_performance),
                'changes' => [],
            ];
        }

        $previous_snapshot = array_merge($default_snapshot, (array) ($previous_review['performance_snapshot'] ?? []));
        $current_snapshot = array_merge($default_snapshot, $current_performance);
        $metric_labels = [
            'shop_contacts_30d' => 'shop',
            'whatsapp_contacts_30d' => 'whatsapp',
            'product_clicks_30d' => 'blog_products',
            'funnel_registrations_30d' => 'funnel_contacts',
            'weighted_signal_score' => 'total_signal',
        ];
        $changes = [];

        foreach($metric_labels as $metric_key => $label) {
            $previous_value = (int) ($previous_snapshot[$metric_key] ?? 0);
            $current_value = (int) ($current_snapshot[$metric_key] ?? 0);
            $delta = $current_value - $previous_value;

            $changes[] = [
                'metric' => $label,
                'previous' => $previous_value,
                'current' => $current_value,
                'delta' => $delta,
                'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'same'),
            ];
        }

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

        return [
            'background_type' => $background_type !== '' ? $background_type : 'default',
            'background_summary' => $background_summary,
            'background_value' => $background_value,
            'background_color_one' => (string) ($settings->background_color_one ?? ''),
            'background_color_two' => (string) ($settings->background_color_two ?? ''),
            'text_color' => (string) ($settings->text_color ?? ''),
            'font' => (string) ($settings->font ?? ''),
            'block_spacing' => (string) ($settings->block_spacing ?? ''),
            'width' => (string) ($settings->width ?? ''),
            'background_blur' => (int) ($settings->background_blur ?? 0),
            'background_brightness' => (int) ($settings->background_brightness ?? 100),
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

    private function calculate_app_review_weighted_signal_score(array $signals): int {
        $shop_contacts = (int) ($signals['shop_contacts_30d'] ?? 0);
        $whatsapp_contacts = (int) ($signals['whatsapp_contacts_30d'] ?? 0);
        $product_clicks = (int) ($signals['product_clicks_30d'] ?? 0);
        $funnel_registrations = (int) ($signals['funnel_registrations_30d'] ?? 0);

        return $shop_contacts + $whatsapp_contacts + $product_clicks + ($funnel_registrations * 2);
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

        foreach($apps as &$app) {
            $app['weighted_signal_score'] = $this->calculate_app_review_weighted_signal_score($app);
        }
        unset($app);

        return $apps;
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

        $brand_tone = match($communication_style) {
            'warm_personal' => 'topao, osoban i siguran',
            'direct_simple' => 'izravan, jednostavan i čist',
            'expert_structured' => 'stručan, uredan i ozbiljan',
            'storytelling' => 'priča, emocija i povjerenje',
            default => 'jasan, smiren i lako razumljiv',
        };

        $recommended_primary_system = match($goal_type) {
            'business' => 'Funnel + WhatsApp + edukativna thank you stranica',
            'shop' => 'glavni proizvod ili kategorija + povjerenje + jedan jasan sljedeći korak',
            'activation' => 'WhatsApp + Funnel + edukativni sadržaj',
            'brand' => 'povjerenje + video + jedan glavni korak',
            default => 'jedan glavni korak + povjerenje + sekundarni sadržaj',
        };

        $color_strategy = match($goal_type) {
            'business' => [
                'background' => '#0F172A',
                'surface' => '#111827',
                'primary_button' => '#14B8A6',
                'secondary_button' => '#38BDF8',
                'button_text' => '#FFFFFF',
                'heading' => '#F8FAFC',
                'body_text' => '#CBD5E1',
                'shadow' => 'rgba(20,184,166,.28)',
                'why' => 'ozbiljan i čist dojam koji pomaže povjerenju za suradnju i prijavu',
            ],
            'shop' => [
                'background' => '#F8FAFC',
                'surface' => '#FFFFFF',
                'primary_button' => '#16A34A',
                'secondary_button' => '#0EA5A4',
                'button_text' => '#FFFFFF',
                'heading' => '#0F172A',
                'body_text' => '#334155',
                'shadow' => 'rgba(22,163,74,.20)',
                'why' => 'čist i svjež dojam koji pojačava sigurnost kod preporuke proizvoda',
            ],
            'brand' => [
                'background' => '#0B1120',
                'surface' => '#111827',
                'primary_button' => '#C89B3C',
                'secondary_button' => '#E2E8F0',
                'button_text' => '#111111',
                'heading' => '#F8FAFC',
                'body_text' => '#CBD5E1',
                'shadow' => 'rgba(200,155,60,.22)',
                'why' => 'premium i autoritativan dojam koji jača osobni brend i ozbiljnost',
            ],
            'activation' => [
                'background' => '#111827',
                'surface' => '#1F2937',
                'primary_button' => '#25D366',
                'secondary_button' => '#14B8A6',
                'button_text' => '#FFFFFF',
                'heading' => '#F8FAFC',
                'body_text' => '#CBD5E1',
                'shadow' => 'rgba(37,211,102,.22)',
                'why' => 'topao i siguran dojam koji pomaže ljudima da lakše pošalju poruku',
            ],
            default => [
                'background' => '#0F172A',
                'surface' => '#111827',
                'primary_button' => '#14B8A6',
                'secondary_button' => '#38BDF8',
                'button_text' => '#FFFFFF',
                'heading' => '#F8FAFC',
                'body_text' => '#CBD5E1',
                'shadow' => 'rgba(20,184,166,.24)',
                'why' => 'jasan i smiren izgled koji pomaže fokusu i povjerenju',
            ],
        };

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
            'color_strategy' => $color_strategy,
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
            'preferred_primary_path' => $is_contact_goal ? 'lead_funnel' : ($goal_type === 'shop' ? 'shop_or_whatsapp' : 'hybrid'),
            'available_tools' => $available_tools,
            'missing_for_contact_goal' => $missing_for_contact_goal,
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

    private function get_app_structure_payload(int $user_id): array {
        /* Custom code: FC-2026-03-31: load the protected default biolink and avoid non-portable links columns */
        $main_biolink_id = (int) (\Altum\Link::get_user_main_biolink_id($user_id) ?? 0);
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
        $blocks_result = database()->query("SELECT `biolink_block_id`, `link_id`, `type`, `settings`, `order`
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
                        'type' => $type,
                        'label' => $this->get_app_review_block_preview_label($type, $settings),
                    ];
                }

                $apps[$link_id]['ordered_block_previews'][] = [
                    'type' => $type,
                    'label' => $this->get_app_review_block_preview_label($type, $settings),
                    'visual_url' => $this->get_app_review_block_visual_url($type, $settings),
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

    private function build_app_review_ai_input(array $values, array $analytics_payload, array $app_structure_payload, int $current_clicks_30d, string $request_context = '', ?array $selected_app = null): array {
        $goal_type = $this->get_goal_type($values);
        $selected_app = $selected_app ?? $this->get_selected_app($app_structure_payload);
        $quality_payload = $this->get_app_review_quality_payload($selected_app, $current_clicks_30d);
        $selected_app_capabilities = (array) ($selected_app['conversion_capabilities'] ?? []);
        $capability_context = $this->get_app_review_capability_context($selected_app_capabilities, $values, $goal_type);
        $fcc_goal_system = $this->get_fcc_goal_system_payload($values, $goal_type);

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
                'notes' => (string) ($values['notes'] ?? ''),
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
                'visual_context' => [
                    'visual_profile' => (array) ($selected_app['visual_profile'] ?? []),
                    'scope' => (string) ($selected_app['primary_visual_scope'] ?? 'none'),
                    'visual_type' => (string) ($selected_app['primary_visual_type'] ?? ''),
                    'primary_visual_url' => (string) ($selected_app['primary_visual_url'] ?? ''),
                    'first_screen_blocks' => array_values(array_filter((array) ($selected_app['first_screen_blocks'] ?? []), 'is_array')),
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
            'fcc_goal_system' => $fcc_goal_system,
            'request_context' => $request_context,
        ];
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

    private function compare_app_review_signal_rows(array $a, array $b): int {
        return (($b['weighted_signal_score'] ?? 0) <=> ($a['weighted_signal_score'] ?? 0))
            ?: (($b['shop_contacts_30d'] ?? 0) <=> ($a['shop_contacts_30d'] ?? 0))
            ?: (($b['whatsapp_contacts_30d'] ?? 0) <=> ($a['whatsapp_contacts_30d'] ?? 0))
            ?: (($b['funnel_registrations_30d'] ?? 0) <=> ($a['funnel_registrations_30d'] ?? 0))
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
                'current' => (int) ($performance['funnel_registrations_30d'] ?? 0),
                'target' => (int) ($benchmark['funnel_registrations_30d'] ?? 0),
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

    private function validate_app_review_response(array $review): array {
        $headline = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['headline'] ?? $review['title'] ?? '', 140));
        $summary = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['summary'] ?? $review['overview'] ?? '', 600));
        $biggest_bottleneck = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['biggest_bottleneck'] ?? $review['main_problem'] ?? '', 220));
        $top_recommendation = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['top_recommendation'] ?? $review['power_move'] ?? '', 320));
        $weekly_focus = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['weekly_focus'] ?? $review['next_focus'] ?? '', 240));
        $priority_actions = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['priority_actions'] ?? $review['quick_wins'] ?? [], 4, 200));
        $ideal_block_order = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['ideal_block_order'] ?? $review['recommended_block_order'] ?? [], 8, 120));
        $design_notes = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['design_notes'] ?? $review['visual_notes'] ?? $review['color_advice'] ?? [], 5, 220));
        $keep_doing = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['keep_doing'] ?? $review['strengths_to_keep'] ?? [], 4, 180));
        $first_move = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['first_move'] ?? $review['do_first'] ?? $top_recommendation, 180));
        $next_move = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['next_move'] ?? $review['do_next'] ?? $weekly_focus, 180));
        $do_not_touch = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['do_not_touch'] ?? $review['dont_break'] ?? ($keep_doing[0] ?? ''), 180));
        $funnel_blueprint = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['funnel_blueprint'] ?? $review['funnel_plan'] ?? $review['lead_flow'] ?? [], 4, 220));
        $color_palette = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['color_palette'] ?? $review['color_direction'] ?? $review['palette'] ?? [], 6, 200));
        $trust_builders = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['trust_builders'] ?? $review['trust_elements'] ?? $review['trust_plan'] ?? [], 5, 200));

        if($headline === '' || $summary === '' || $top_recommendation === '' || empty($priority_actions) || empty($ideal_block_order)) {
            throw new \Exception(l('ai_plan.ai_error_invalid_response'));
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
        ];
    }

    private function generate_app_review(array $values, array $analytics_payload, array $app_structure_payload, int $current_clicks_30d, string $request_context = '', ?array $selected_app = null, ?array $previous_review = null): array {
        $credentials = $this->get_ai_credentials();

        if($credentials['api_key'] === '') {
            if($credentials['needs_personal_key']) {
                throw new \Exception(sprintf(l('account_preferences.error_message.aix.openai_api_key'), '<a href="' . url('account-preferences') . '"><strong>' . l('account_preferences.menu') . '</strong></a>'));
            }

            throw new \Exception(l('ai_plan.ai_error_missing_api_key'));
        }

        $selected_app = $selected_app ?? $this->get_selected_app($app_structure_payload) ?? $this->get_default_app_summary();
        $ai_input = $this->build_app_review_ai_input($values, $analytics_payload, $app_structure_payload, $current_clicks_30d, $request_context, $selected_app);
        $ai_input = $this->sanitize_utf8_for_json($ai_input);
        $quality_payload = $this->get_app_review_quality_payload($selected_app, $current_clicks_30d);
        $evolution_payload = $this->get_app_review_evolution_payload((array) ($quality_payload['performance'] ?? []), $previous_review);
        $evolution_payload = $this->sanitize_utf8_for_json($evolution_payload);
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
                ? 'Na temelju cilja korisnika, postojece strukture aplikacije, vizualnog ulaza ako postoji, prethodne analize iste glavne FCC aplikacije i novih mjernih podataka napravi nadogradnju analize iste aplikacije.'
                : 'Na temelju cilja korisnika, postojece strukture aplikacije, vizualnog ulaza ako postoji i link analitike napravi pocetnu AI analizu glavne FCC aplikacije.',
            'Vrati samo JSON s kljucevima: headline, summary, biggest_bottleneck, top_recommendation, weekly_focus, first_move, next_move, do_not_touch, priority_actions, ideal_block_order, design_notes, keep_doing, funnel_blueprint, color_palette, trust_builders.',
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
            '- design_notes mora imati 2 do 5 konkretnih savjeta za boje, tekst blokove, video, kontrast i vizualni dojam.',
            '- color_palette mora imati 4 do 6 kratkih stavki s konkretnim hex kodovima za pozadinu, naslov, glavni gumb, tekst na gumbu, sekundarni gumb i sjenu ako ima smisla.',
            '- trust_builders mora imati 3 do 5 kratkih savjeta kako aplikacija moze djelovati sigurnije, ozbiljnije i uvjerljivije.',
            '- funnel_blueprint mora imati 3 do 4 kratke stavke i jasno reci kako sloziti Funnel ako Funnel ima smisla za cilj korisnika.',
            '- keep_doing neka bude kratko i ohrabrujuce: sto vec radi dobro i ne treba kvariti.',
            '- Ovo nije opci poslovni plan, nego pregled kako poboljsati FCC aplikaciju za cilj koji je korisnik upisao.',
            '- Nemoj korisniku dati 20 opcija. Jedna glavna preporuka mora biti najjaca i jasno odvojena.',
            '- Nemoj preporucivati Dodaj na pocetni zaslon.',
            '- Nemoj preporucivati Save Contact, Contact Collector ni Email Collector kao glavno rjesenje.',
            '- Nemoj davati savjete o velicini gumba jer su gumbi u sustavu vec standardizirani.',
            '- Chatbot ili AI savjetnik nije klasicni gumb u redoslijedu blokova. On se pojavljuje kao mala ikonica u donjem desnom kutu i otvara popup preko ekrana.',
            '- Ako aplikacija ima chatbot, tretiraj ga kao neutralan pomocni sloj koji ne smeta fokusu i ne racunaj ga kao problem u prioritetu glavnih gumba ili redoslijedu blokova.',
            '- Chatbot mozes spomenuti kao koristan dodatak za preporuku proizvoda i usmjeravanje na linkove s popustom, ali ga nemoj isticati kao glavnu prepreku niti kao glavni prvi korak.',
            '- Kad predlazes kontakt, koristi formulaciju "pošalji poruku na WhatsApp" ili "WhatsApp poruka".',
            '- Ako je cilj skupljanje kontakata, suradnja ili prijava ljudi, Funnel tretiraj kao glavni i najbolji alat za prvi ozbiljan korak.',
            '- Ako je cilj skupljanje kontakata i aplikacija nema Funnel, jedna od prvih preporuka mora biti ugradnja Funnel-a.',
            '- Ako preporucujes Funnel, nemoj predlagati pitanja. Funnel u FCC-u moze imati video, ime, email, broj telefona i thank you stranicu.',
            '- Ako preporucujes Funnel, provjeri ima li aplikacija video. Ako ga nema, preporuci kratak video prije ili unutar Funnel-a.',
            '- U funnel_blueprint smijes preporuciti PDF knjigu, plan, mini vodič, edukaciju ili preusmjeravanje na drugu FCC aplikaciju kao poklon ili sljedeci korak.',
            '- Ako je cilj regrutacija, Funnel ili thank you stranica mogu voditi na edukativnu FCC aplikaciju za suradnju.',
            '- Ako je cilj proizvod, Funnel moze voditi na preporuku proizvoda, plan prehrane, vjezbe, detox ili korisni poklon prije kupnje.',
            '- Ako aplikacija vec ima Funnel ili WhatsApp, reci jesu li dovoljno vidljivi i jesu li pravi prvi korak.',
            '- Ako postoje previse jednaki glavni smjerovi na vrhu, jasno reci koji jedan mora biti glavni prvi korak.',
            '- Ako su prodajni linkovi previsoko, a cilj je kontakt ili suradnja, reci da trebaju ici nakon povjerenja, videa ili Funnel-a.',
            '- U obzir uzmi goal_context: publiku, glavni cilj, prioritetnu ponudu, stil komunikacije i biljeske iz upitnika. Preporuke moraju biti uskladjene s tim identitetom.',
            '- Ako predlazes tekst blokove, reci jednostavno sto trebaju poruciti: kome je aplikacija namijenjena, sto osoba dobiva i koji je sljedeci korak.',
            '- Za boje koristi konkretne hex kodove i kratko objasni zasto te boje pomazu bas ovom cilju i ovom tonu brenda.',
            '- U color_palette i design_notes analiziraj pozadinu, boju naslova, boju teksta, boju gumba, boju teksta na gumbu i sjenu gumba.',
            '- U trust_builders reci kako vise povjerenja grade fotografija, video, jedan glavni korak, edukacija, Funnel i jasan redoslijed blokova.',
            (!empty($evolution_payload['has_previous_review'])
                ? '- Ovo nije nova analiza od nule. Ovo je nadogradnja prethodne analize iste aplikacije. Ukratko reci sto je bolje nego prosli put, sto i dalje koci rezultat i koji je sada novi najbolji sljedeci korak.'
                : '- Ovo je prva analiza ove glavne aplikacije. Postavi jasnu pocetnu bazu, glavni fokus i najvazniji prvi redoslijed blokova.'),
            '- Ako dobijes vizual, koristi ga za komentare o fotografiji, dojmu, kontrastu i prvom ekranu.',
            '- Ako je visual_context.scope = rendered_live_app, tretiraj to kao stvarni screenshot live FCC aplikacije i komentiraj raspored, fotografiju, citljivost i prvi dojam.',
            '- Ako je visual_context.scope = hero_image_only ili avatar_only, nemoj tvrditi da si pregledao cijelu live aplikaciju.',
            '- Ako nema vizuala, oslanjaj se samo na strukturu, redoslijed blokova i analitiku.',
            'Input JSON: ' . json_encode([
                'current_review_input' => $ai_input,
                'review_mode' => $evolution_payload,
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

        if(!is_array($decoded_review)) {
            throw $this->get_ai_debug_error($content);
        }

        try {
            $review = $this->validate_app_review_response($decoded_review);
        } catch(\Throwable $exception) {
            throw $this->get_ai_debug_error($content);
        }

        $review['generated_at'] = get_date();
        $review['model'] = $credentials['model'];
        $review['selected_link_id'] = (int) ($selected_app['link_id'] ?? 0);
        $review['selected_app_url'] = (string) ($selected_app['url'] ?? '');
        $review['selected_app_name'] = (string) (($selected_app['name'] ?? '') ?: ($selected_app['url'] ?? ''));
        $review['request_context'] = $request_context;
        $review['goal_type'] = $this->get_goal_type($values);
        $review['growth_stage'] = $current_clicks_30d >= 15 ? 'active_signal' : 'building_signal';
        $review['quality_score'] = (int) ($quality_payload['score'] ?? 0);
        $review['quality_level'] = (string) ($quality_payload['level_key'] ?? 'foundation');
        $review['performance_snapshot'] = (array) ($quality_payload['performance'] ?? []);
        $review['analysis_mode'] = !empty($evolution_payload['has_previous_review']) ? 'evolution' : 'initial';

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

    private function get_ai_debug_error(string $content): \Exception {
        $message = l('ai_plan.ai_error_invalid_response');

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

    private function normalize_daily_plan($value): array {
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

            if(!is_array($day_plan)) {
                continue;
            }

            $tasks = $this->normalize_ai_list($day_plan['tasks'] ?? $day_plan['actions'] ?? $day_plan['steps'] ?? [], 4, 180);
            $title = $this->sanitize_ai_string($day_plan['title'] ?? $day_plan['focus'] ?? $day_plan['headline'] ?? '', 120);
            $day = $this->sanitize_ai_string($day_plan['day'] ?? $day_plan['label'] ?? ('Dan ' . ($index + 1)), 40);

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
        $model = trim((string) (settings()->main->openai_model ?? 'gpt-4o'));

        return [
            'api_key' => $api_key,
            'model' => $model !== '' ? $model : 'gpt-4o',
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
        }

        if(!empty($mentor_guidance['has_guidance'])) {
            $guardrails[] = 'Postoji dodatna procjena mentora iz direktnog kontakta. Uzmi je kao korektiv realnosti i fokusa, ali je ne smijes slijepo staviti iznad stvarnog signala iz ponasanja i rezultata.';
        }

        return array_values(array_unique($guardrails));
    }

    private function build_weekly_ai_plan_input(array $values, array $weekly_checkin, array $analytics_payload, array $app_structure_payload, array $adaptive_question, ?array $previous_cycle_context = null, array $mentor_guidance = []): array {
        $labels = $this->get_option_labels($values, $weekly_checkin);
        $analytics_payload['webshop_clicks'] = (int) $this->get_last_30_days_shop_clicks();
        $goal_type = $this->get_goal_type($values);
        $fcc_goal_system = $this->get_fcc_goal_system_payload($values, $goal_type);
        $main_app_snapshot = $this->get_weekly_ai_app_structure_snapshot($this->get_main_app_for_review($app_structure_payload));
        $previous_outcome = $previous_cycle_context['outcome'] ?? null;
        $previous_plan = $previous_cycle_context['plan'] ?? null;
        $previous_checkin = $previous_cycle_context['checkin'] ?? null;

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
                ] : null,
            ],
            'mentor_context' => [
                'has_guidance' => (bool) ($mentor_guidance['has_guidance'] ?? false),
                'guidance' => (string) ($mentor_guidance['guidance'] ?? ''),
                'weight' => 'secondary',
                'visibility' => 'admin_only',
            ],
            'strategy_guardrails' => $this->get_strategy_guardrails($values, $weekly_checkin, $analytics_payload, $app_structure_payload, $previous_cycle_context, $mentor_guidance),
        ];
    }

    private function validate_weekly_ai_plan_response(array $plan): array {
        $plan = $this->unwrap_weekly_ai_plan_payload($plan);

        $headline = $this->sanitize_ai_string($plan['headline'] ?? $plan['title'] ?? '', 140);
        $summary = $this->sanitize_ai_string($plan['summary'] ?? $plan['overview'] ?? $plan['executive_summary'] ?? '', 900);
        $focus = $this->sanitize_ai_string($plan['focus'] ?? $plan['main_focus'] ?? $headline, 180);
        $coach_intro = $this->sanitize_ai_string($plan['coach_intro'] ?? $plan['intro'] ?? $plan['opening_note'] ?? '', 420);
        $brutal_truth = $this->sanitize_ai_string($plan['brutal_truth'] ?? $plan['hard_truth'] ?? $plan['uncomfortable_truth'] ?? '', 320);
        $power_move = $this->sanitize_ai_string($plan['power_move'] ?? $plan['leverage_move'] ?? $plan['best_move'] ?? '', 320);
        $why_this_week = $this->sanitize_ai_string($plan['why_this_week'] ?? $plan['strategic_explanation'] ?? $plan['reasoning'] ?? '', 500);
        $encouragement = $this->sanitize_ai_string($plan['encouragement'] ?? $plan['mindset_note'] ?? $plan['closing_note'] ?? '', 320);
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
        $daily_plan = $this->normalize_daily_plan($plan['daily_plan'] ?? $plan['week_plan'] ?? $plan['seven_day_plan'] ?? $plan['days'] ?? []);

        if(empty($daily_plan)) {
            $daily_plan = $this->build_fallback_daily_plan($plan, $focus, $summary, $priority_channels, $content_ideas, $coach_ideas, $do_not_do);
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

    private function generate_weekly_ai_plan(array $values, array $weekly_checkin, array $weekly_checkins, array $weekly_plans, array $weekly_outcomes, array $analytics_payload, array $app_structure_payload, array $adaptive_question): array {
        $credentials = $this->get_ai_credentials();

        if($credentials['api_key'] === '') {
            if($credentials['needs_personal_key']) {
                throw new \Exception(sprintf(l('account_preferences.error_message.aix.openai_api_key'), '<a href="' . url('account-preferences') . '"><strong>' . l('account_preferences.menu') . '</strong></a>'));
            }

            throw new \Exception(l('ai_plan.ai_error_missing_api_key'));
        }

        $previous_cycle_context = $this->get_previous_weekly_cycle_context($weekly_checkins, $weekly_plans, $weekly_outcomes, $weekly_checkin);
        $mentor_guidance = $this->get_mentor_ai_guidance($this->user->preferences ?? null);
        $ai_input = $this->build_weekly_ai_plan_input($values, $weekly_checkin, $analytics_payload, $app_structure_payload, $adaptive_question, $previous_cycle_context, $mentor_guidance);
        $ai_input = $this->sanitize_utf8_for_json($ai_input);

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
                        'content' => implode("\n\n", [
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
                            '- Ako korisnik vec ima Funnel ili Forever blokove, procijeni jesu li sada korisni ili samo kompliciraju put. Ako nema dovoljno strukture, reci sto prvo treba postaviti. Ako ima previse strukture, reci sto treba maknuti ili spustiti.',
                            '- Ako input sadrzi main_app_structure.position_signals i ordered_block_previews, koristi njih kao izvor istine za stvarni redoslijed blokova. Nemoj reci da je nesto na vrhu, prvi blok ili prije videa ako to nije izricito potvrdeno tim signalima.',
                            '- Ako je cilj skupljanje kontakata, regrutacija ili prijava, plan smije i treba preporuciti Funnel kao glavni tjedni fokus i glavni prvi korak aplikacije.',
                            '- Nemoj preporucivati Save Contact, Contact Collector, Email Collector ni Dodaj na pocetni zaslon.',
                            '- Chatbot ili AI savjetnik nije klasicni gumb u glavnom redoslijedu blokova. On je neutralan pomocni sloj koji se otvara iz male ikonice i ne treba ga tretirati kao prepreku fokusu.',
                            '- Ako aplikacija ima chatbot, smijes ga spomenuti kao pomocni alat za preporuku proizvoda i usmjeravanje, ali ga nemoj isticati kao glavni problem niti kao glavni prvi korak.',
                            '- Ako preporucujes Funnel, imaj na umu da Funnel u FCC-u moze imati video, ime, email, broj telefona i thank you stranicu.',
                            '- Ako cilj trazi vise povjerenja, preporuci i video, jasan tekst blok i jednostavan redoslijed blokova na vrhu aplikacije.',
                            '- U coach_ideas i dnevnim zadacima smijes predloziti i konkretne promjene na aplikaciji: Funnel, tekst blok, video, boje, jedan glavni gumb, drugi redoslijed blokova ili jacu WhatsApp logiku.',
                            '- Ako statistikа pokazuje interes, ali nema prijava, kontkata ili WhatsApp koraka, plan mora reci kako da aplikacija bolje pretvori interes u stvarni kontakt.',
                            '- Ako nema dovoljno signala, plan i dalje mora biti konkretan, ali s fokusom na stvaranje kvalitetnog signala, ne na genericke savjete.',
                            '- Izbjegavaj pretrpavanje. Plan treba djelovati kao da coach zna sto je najvaznije, a ne kao da zeli ugurati 30 zadataka.',
                            '- Nemoj koristiti markdown, code blockove ni dodatne kljuceve.',
                            'Input JSON: ' . json_encode($ai_input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE),
                        ])
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

        if(!is_array($decoded_plan)) {
            throw $this->get_ai_debug_error($content);
        }

        try {
            $plan = $this->validate_weekly_ai_plan_response($decoded_plan);
        } catch(\Throwable $exception) {
            throw $this->get_ai_debug_error($content);
        }
        $plan['checkin_submitted_at'] = $weekly_checkin['submitted_at'] ?? get_date();
        $plan['generated_at'] = get_date();
        $plan['model'] = $credentials['model'];

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
        $has_weekly_limits_bypass = \Altum\Authentication::is_admin();
        $ai_growth_access_payload = $this->get_ai_growth_access_payload($preferences, $app_structure_payload, $current_clicks_30d);
        $app_review_access_payload = (array) ($ai_growth_access_payload['app_review'] ?? []);
        $weekly_access_payload = (array) ($ai_growth_access_payload['weekly'] ?? []);
        $growth_signal_30d = (int) ($ai_growth_access_payload['growth_signal_30d'] ?? 0);
        $cooldown_payload = $this->get_weekly_cooldown_payload($latest_weekly_checkin, (int) ($weekly_access_payload['cooldown_days'] ?? 0));
        $app_review_cooldown_payload = $this->get_cooldown_payload_by_days($latest_app_review_any['generated_at'] ?? null, (int) ($app_review_access_payload['cooldown_days'] ?? 0));
        $is_profile_complete = $this->is_profile_complete($values);
        $is_weekly_plan_eligible = $has_weekly_limits_bypass || !empty($weekly_access_payload['has_access']);
        $is_weekly_submission_locked = $has_weekly_limits_bypass ? false : $cooldown_payload['is_locked'];
        $weekly_next_checkin_at = $has_weekly_limits_bypass ? null : $cooldown_payload['next_checkin_at'];
        $is_app_review_locked = $has_weekly_limits_bypass ? false : (!empty($app_review_access_payload['can_generate']) ? $app_review_cooldown_payload['is_locked'] : true);
        $app_review_next_at = $has_weekly_limits_bypass ? null : $app_review_cooldown_payload['next_checkin_at'];
        $is_profile_complete_for_weekly = $has_weekly_limits_bypass || $is_profile_complete;
        $is_app_review_accessible = $has_weekly_limits_bypass || ($is_profile_complete && !empty($app_review_access_payload['has_access']));
        $app_review_locked_reason = !$is_profile_complete
            ? l('ai_plan.app_review_locked_entry_tooltip')
            : (!empty($app_review_access_payload['has_access']) ? '' : l('ai_plan.app_review_locked_pro'));
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
        $selected_weekly_plan = $this->get_weekly_plan_by_generated_at($weekly_plans, $requested_plan_generated_at);
        $display_weekly_plan = $requested_plan_history_only ? null : ($selected_weekly_plan ?? $latest_weekly_plan);
        $display_weekly_outcome = $this->get_weekly_outcome_for_plan($weekly_outcomes, $display_weekly_plan);
        $app_review_quality_payload = $this->get_app_review_quality_payload($selected_app, $current_clicks_30d);
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

                    redirect('ai-plan?section=app_review');
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

                    $ai_plan_generated = false;

                    try {
                        $new_weekly_plan = $this->generate_weekly_ai_plan($values, $new_checkin, $weekly_checkins, $weekly_plans, $weekly_outcomes, $analytics_payload, $app_structure_payload, $adaptive_question);
                        $weekly_plans = $this->upsert_weekly_plan($weekly_plans, $new_weekly_plan);
                        $preferences->leader_ai_weekly_plans = $weekly_plans;
                        if(!empty($weekly_access_payload['uses_starter_credit'])) {
                            $preferences = $this->consume_ai_growth_starter_credit($preferences, 'weekly_plan');
                        }
                        $ai_plan_generated = true;
                    } catch(\Throwable $exception) {
                        Alerts::add_info($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('ai_plan.ai_error_request_failed'));
                    }

                    db()->where('user_id', $this->user->user_id)->update('users', [
                        'preferences' => json_encode($preferences),
                    ]);

                    cache()->deleteItemsByTag('user_id=' . $this->user->user_id);
                    cache()->deleteItem('user?user_id=' . $this->user->user_id);

                    \Altum\Logger::users($this->user->user_id, 'ai_plan.weekly_checkin_saved');

                    Alerts::add_success($ai_plan_generated ? l('ai_plan.weekly_success_message_phase_3') : l('ai_plan.weekly_success_message'));

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
                        $new_app_review = $this->generate_app_review($values, $analytics_payload, $app_structure_payload, $current_clicks_30d, $app_review_context, $selected_app, $previous_app_review);
                        $generated_review_at = (string) ($new_app_review['generated_at'] ?? '');
                        $app_reviews = $this->upsert_app_review($app_reviews, $new_app_review);
                        $preferences->leader_ai_app_reviews = $app_reviews;
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
                        $this->persist_ai_plan_preferences($preferences);

                        \Altum\Logger::users($this->user->user_id, 'ai_plan.app_review_generated');
                        Alerts::add_success(l('ai_plan.app_review_success_message'));
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

                    $redirect_query['app_review_done'] = 1;

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
                $completion_level = $this->normalize_single_choice($_POST['completion_level'] ?? null, $allowed_completion_levels);
                $best_response = input_clean($_POST['best_response'] ?? '', 800);
                $main_blocker_now = input_clean($_POST['main_blocker_now'] ?? '', 800);
                $biggest_lesson = input_clean($_POST['biggest_lesson'] ?? '', 800);
                $next_adjustment = input_clean($_POST['next_adjustment'] ?? '', 800);

                if(!$completion_level) Alerts::add_field_error('completion_level', l('ai_plan.error.completion_level'));
                if(!$best_response) Alerts::add_field_error('best_response', l('ai_plan.error.best_response'));
                if(!$main_blocker_now) Alerts::add_field_error('main_blocker_now', l('ai_plan.error.main_blocker_now'));
                if(!$biggest_lesson) Alerts::add_field_error('biggest_lesson', l('ai_plan.error.biggest_lesson'));
                if(!$next_adjustment) Alerts::add_field_error('next_adjustment', l('ai_plan.error.next_adjustment'));

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

                    $new_outcome = [
                        'checkin_submitted_at' => $target_checkin_submitted_at !== '' ? $target_checkin_submitted_at : null,
                        'plan_generated_at' => (string) ($target_weekly_plan['generated_at'] ?? ''),
                        'completion_level' => $completion_level,
                        'best_response' => $best_response,
                        'main_blocker_now' => $main_blocker_now,
                        'biggest_lesson' => $biggest_lesson,
                        'next_adjustment' => $next_adjustment,
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
            'app_review_page_url' => url('ai-plan?section=app_review'),
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
            'feedback_loop_payload' => $feedback_loop_payload,
            'current_clicks_30d' => $current_clicks_30d,
            'growth_signal_30d' => $growth_signal_30d,
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
            'app_review_countdown_days' => $this->get_weekly_checkin_countdown_days($app_review_next_at),
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
