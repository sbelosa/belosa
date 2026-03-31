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

    private function get_preferences_object() {
        $preferences = $this->user->preferences ?? new \stdClass();

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
        $preferences = $this->get_preferences_object();
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

            $normalized[] = [
                'generated_at' => $review['generated_at'] ?? null,
                'model' => (string) ($review['model'] ?? ''),
                'selected_link_id' => (int) ($review['selected_link_id'] ?? 0),
                'selected_app_url' => (string) ($review['selected_app_url'] ?? ''),
                'selected_app_name' => (string) ($review['selected_app_name'] ?? ''),
                'request_context' => (string) ($review['request_context'] ?? ''),
                'goal_type' => (string) ($review['goal_type'] ?? ''),
                'growth_stage' => (string) ($review['growth_stage'] ?? ''),
                'headline' => $this->normalize_app_review_channel_copy((string) ($review['headline'] ?? '')),
                'summary' => $this->normalize_app_review_channel_copy((string) ($review['summary'] ?? '')),
                'biggest_bottleneck' => $this->normalize_app_review_channel_copy((string) ($review['biggest_bottleneck'] ?? '')),
                'top_recommendation' => $this->normalize_app_review_channel_copy((string) ($review['top_recommendation'] ?? '')),
                'weekly_focus' => $this->normalize_app_review_channel_copy((string) ($review['weekly_focus'] ?? '')),
                'priority_actions' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['priority_actions'] ?? []), 'is_scalar'))),
                'ideal_block_order' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['ideal_block_order'] ?? []), 'is_scalar'))),
                'design_notes' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['design_notes'] ?? []), 'is_scalar'))),
                'keep_doing' => $this->normalize_app_review_channel_list(array_values(array_filter((array) ($review['keep_doing'] ?? []), 'is_scalar'))),
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

    private function get_weekly_cooldown_payload(?array $latest_weekly_checkin): array {
        if(empty($latest_weekly_checkin['submitted_at'])) {
            return [
                'is_locked' => false,
                'next_checkin_at' => null,
            ];
        }

        try {
            $submitted_at = new \DateTimeImmutable((string) $latest_weekly_checkin['submitted_at']);
            $next_checkin_at = $submitted_at->add(new \DateInterval('P7D'));
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
        /* Custom code: FC-2026-03-31: allow admins continuous testing access to app analysis */
        if(\Altum\Authentication::is_admin()) {
            return [
                'is_admin_testing' => true,
                'is_pro_daily' => true,
                'cooldown_days' => 0,
                'can_select_any_app' => true,
                'plan_label_key' => 'ai_plan.app_review_plan_admin',
            ];
        }
        /* /Custom code: FC-2026-03-31 */

        $is_plan5 = (string) ($this->user->plan_id ?? '') === '5';

        return [
            'is_admin_testing' => false,
            'is_pro_daily' => $is_plan5,
            'cooldown_days' => $is_plan5 ? 1 : 7,
            'can_select_any_app' => $is_plan5,
            'plan_label_key' => $is_plan5 ? 'ai_plan.app_review_plan_pro' : 'ai_plan.app_review_plan_beginner',
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
            return 'direct';
        }

        if($source === 'fb' || strpos($source, 'facebook') !== false) return 'facebook';
        if($source === 'ig' || strpos($source, 'instagram') !== false) return 'instagram';
        if(strpos($source, 'whatsapp') !== false || $source === 'wa') return 'whatsapp';
        if(strpos($source, 'tiktok') !== false) return 'tiktok';
        if(strpos($source, 'youtube') !== false || $source === 'youtu.be') return 'youtube';
        if(strpos($source, 'telegram') !== false) return 'telegram';
        if(strpos($source, 'viber') !== false) return 'viber';
        if(strpos($source, 'google') !== false || $source === 'gclid') return 'google';
        if(strpos($source, 'linkedin') !== false) return 'linkedin';

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

        return 'direct';
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
            'instagram' => l('ai_plan.analytics_value.source.instagram'),
            'facebook' => l('ai_plan.analytics_value.source.facebook'),
            'whatsapp' => l('ai_plan.analytics_value.source.whatsapp'),
            'google' => l('ai_plan.analytics_value.source.google'),
            'tiktok' => l('ai_plan.analytics_value.source.tiktok'),
            'youtube' => l('ai_plan.analytics_value.source.youtube'),
            'telegram' => l('ai_plan.analytics_value.source.telegram'),
            'viber' => l('ai_plan.analytics_value.source.viber'),
            'linkedin' => l('ai_plan.analytics_value.source.linkedin'),
        ];

        return $map[$source_label] ?? ucfirst($source_label);
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
            }

            $humanized_breakdown[] = [
                'label' => $label,
                'total' => (int) ($item['total'] ?? 0),
                'share' => (float) ($item['share'] ?? 0),
            ];
        }

        return $humanized_breakdown;
    }

    private function get_funnel_payload(int $user_id, string $period_start_datetime): array {
        $funnel_blocks = [];
        $funnel_blocks_result = database()->query("SELECT `biolink_block_id`, `settings`
            FROM `biolinks_blocks`
            WHERE `user_id` = {$user_id}
              AND `type` = 'lead_funnel'");

        while($row = $funnel_blocks_result->fetch_object()) {
            $settings = json_decode($row->settings ?? '{}');
            $funnel_blocks[(int) $row->biolink_block_id] = [
                'name' => trim((string) ($settings->name ?? 'Lead funnel')),
                'objective' => trim((string) ($settings->thank_you_type ?? $settings->open_mode ?? '')),
            ];
        }

        if(empty($funnel_blocks)) {
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

        $funnel_ids_sql = implode(',', array_map('intval', array_keys($funnel_blocks)));
        $clicks_per_funnel = [];
        $clicks_result = database()->query("SELECT `biolink_block_id`, SUM(`is_unique`) AS `unique_clicks`
            FROM `track_links`
            WHERE `user_id` = {$user_id}
              AND `biolink_block_id` IN ({$funnel_ids_sql})
              AND `datetime` >= '{$period_start_datetime}'
            GROUP BY `biolink_block_id`");

        while($row = $clicks_result->fetch_object()) {
            $clicks_per_funnel[(int) $row->biolink_block_id] = (int) ($row->unique_clicks ?? 0);
        }

        $leads_per_funnel = [];
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

        $active_funnels = 0;
        $unique_clicks = 0;
        $total_leads = 0;
        $top_funnel_name = '-';
        $top_funnel_objective = '-';
        $top_funnel_score = -1;

        foreach($funnel_blocks as $biolink_block_id => $funnel_block) {
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

        return [
            'total_funnels' => count($funnel_blocks),
            'active_funnels' => $active_funnels,
            'unique_clicks' => $unique_clicks,
            'total_leads' => $total_leads,
            'conversion_rate' => $unique_clicks ? round(($total_leads / $unique_clicks) * 100, 1) : 0.0,
            'top_funnel_name' => $top_funnel_name,
            'top_funnel_objective' => $top_funnel_objective,
        ];
    }

    private function get_app_structure_payload(int $user_id): array {
        /* Custom code: FC-2026-03-31: load the protected default biolink and avoid non-portable links columns */
        $main_biolink_id = (int) (db()->where('user_id', $user_id)->getValue('users_biolinks', 'biolink_id') ?? 0);
        $apps_result = database()->query("SELECT `link_id`, `url`, `is_enabled`, `datetime`, `last_datetime` FROM `links` WHERE `user_id` = {$user_id} AND `type` = 'biolink'");
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
                'is_enabled' => isset($row->is_enabled) ? (bool) $row->is_enabled : true,
                'datetime' => $row->datetime ?? null,
                'last_datetime' => $row->last_datetime ?? null,
                'total_blocks' => 0,
                'forever_blocks' => 0,
                'funnel_blocks' => 0,
                'social_blocks' => 0,
                'content_blocks' => 0,
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
        $blocks_result = database()->query("SELECT `link_id`, `type` FROM `biolinks_blocks` WHERE `user_id` = {$user_id} AND `link_id` IN ({$link_ids_sql})");

        if($blocks_result) {
            while($row = $blocks_result->fetch_object()) {
                $link_id = (int) ($row->link_id ?? 0);
                $type = (string) ($row->type ?? '');

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
                }

                if(str_starts_with($type, 'link_forever') || in_array($type, ['link_discount', 'link_app_switcher'], true)) {
                    $apps[$link_id]['forever_blocks']++;
                }
            }
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
            ],
            'request_context' => $request_context,
        ];
    }

    private function validate_app_review_response(array $review): array {
        $headline = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['headline'] ?? $review['title'] ?? '', 140));
        $summary = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['summary'] ?? $review['overview'] ?? '', 600));
        $biggest_bottleneck = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['biggest_bottleneck'] ?? $review['main_problem'] ?? '', 220));
        $top_recommendation = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['top_recommendation'] ?? $review['power_move'] ?? '', 320));
        $weekly_focus = $this->normalize_app_review_channel_copy($this->sanitize_ai_string($review['weekly_focus'] ?? $review['next_focus'] ?? '', 240));
        $priority_actions = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['priority_actions'] ?? $review['quick_wins'] ?? [], 4, 200));
        $ideal_block_order = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['ideal_block_order'] ?? $review['recommended_block_order'] ?? [], 8, 120));
        $design_notes = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['design_notes'] ?? $review['visual_notes'] ?? $review['color_advice'] ?? [], 4, 180));
        $keep_doing = $this->normalize_app_review_channel_list($this->normalize_ai_list($review['keep_doing'] ?? $review['strengths_to_keep'] ?? [], 4, 180));

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
        ];
    }

    private function generate_app_review(array $values, array $analytics_payload, array $app_structure_payload, int $current_clicks_30d, string $request_context = '', ?array $selected_app = null): array {
        $credentials = $this->get_ai_credentials();

        if($credentials['api_key'] === '') {
            if($credentials['needs_personal_key']) {
                throw new \Exception(sprintf(l('account_preferences.error_message.aix.openai_api_key'), '<a href="' . url('account-preferences') . '"><strong>' . l('account_preferences.menu') . '</strong></a>'));
            }

            throw new \Exception(l('ai_plan.ai_error_missing_api_key'));
        }

        $selected_app = $selected_app ?? $this->get_selected_app($app_structure_payload) ?? $this->get_default_app_summary();
        $ai_input = $this->build_app_review_ai_input($values, $analytics_payload, $app_structure_payload, $current_clicks_30d, $request_context, $selected_app);

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
                        'content' => 'Pisi iskljucivo na hrvatskom. Ti si elitni strategist za FCC aplikacije. Tvoj posao nije opisivati podatke nego reci kako sloziti bolju aplikaciju za konkretan cilj korisnika. Budi konkretan, jasan i praktican. Vrati samo valjan JSON bez markdowna i bez dodatnih kljuceva.'
                    ],
                    [
                        'role' => 'user',
                        'content' => implode("\n\n", [
                            'Na temelju cilja korisnika, postojece strukture aplikacije i link analitike napravi jednu tjednu AI analizu same FCC aplikacije.',
                            'Vrati samo JSON s kljucevima: headline, summary, biggest_bottleneck, top_recommendation, weekly_focus, priority_actions, ideal_block_order, design_notes, keep_doing.',
                            'Pravila:',
                            '- Ovo nije cijeli poslovni plan nego review aplikacije i kako je poboljsati.',
                            '- priority_actions mora imati 3 do 4 kratke i konkretne preporuke po prioritetu.',
                            '- ideal_block_order mora biti polje od 5 do 8 kratkih stavki koje opisuju najbolji redoslijed blokova za trenutni cilj aplikacije.',
                            '- design_notes mora dati 2 do 4 konkretna savjeta za boje, kontrast, vizualnu hijerarhiju i kolicinu elemenata. Nemoj biti generican.',
                            '- keep_doing mora navesti 2 do 4 stvari koje ne treba kvariti jer vec rade dobro.',
                            '- Ako korisnik ima malo klikova, fokus stavi na to kako aplikacija lakse dolazi do prvih kvalitetnih klikova.',
                            '- Ako korisnik vec ima signal, fokus stavi na to kako aplikacija bolje pretvara interes u sljedeci korak.',
                            '- Nemoj koristiti strucne izraze koje pocetnik ne razumije. Umjesto CTA reci jasan sljedeci korak ili poziv na akciju. Umjesto funnel reci putanja ili koraci. Umjesto conversion reci prijelaz u sljedeci korak.',
                            '- Nemoj korisniku dati 20 opcija. Jedna glavna preporuka mora biti najjaca i jasno odvojena.',
                            '- Kad predlazes javljanje ili kontakt, nemoj govoriti DM, inbox ni privatna poruka. Koristi formulaciju "pošalji poruku na WhatsApp" ili "WhatsApp poruka".',
                            '- Ako je aplikacija vise direct-sale nego lead-gen, nemoj forsirati lead formu kao jedino ispravno rjesenje.',
                            '- Ako boje, kontrast ili prevelik broj jednakih blokova stvaraju konfuziju, reci to jasno i jednostavno.',
                            '- Ako je trenutni raspored dobar za osobni intro prije prodajnog bloka, nemoj gurati prodajni blok umjetno previsoko.',
                            'Input JSON: ' . json_encode($ai_input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
        $device_buckets = [];
        $language_buckets = [];
        $clicks_result = database()->query("SELECT `country_code`, `browser_language`, `device_type`, `referrer_host`, `utm_source`
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

            $this->increment_bucket($country_buckets, $country_code, $country_code);
            $this->increment_bucket($language_buckets, mb_strtolower($browser_language), $browser_language);
            $this->increment_bucket($source_buckets, mb_strtolower($source_label), $source_label);
            $this->increment_bucket($device_buckets, mb_strtolower($device_type), $device_type);
        }

        $top_countries = $this->humanize_breakdown($this->build_breakdown($country_buckets, $current_clicks_30d, 3), 'country');
        $top_sources = $this->humanize_breakdown($this->build_breakdown($source_buckets, $current_clicks_30d, 3), 'source');
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
            'top_source_label' => $this->get_primary_breakdown_label($top_sources, l('ai_plan.analytics_value.source.direct')),
            'top_device_label' => $this->get_primary_breakdown_label($top_devices),
            'top_language_label' => $this->get_primary_breakdown_label($top_languages),
            'top_countries' => $top_countries,
            'top_sources' => $top_sources,
            'top_devices' => $top_devices,
            'top_languages' => $top_languages,
            'blog_article_clicks' => $blog_product_clicks + $blog_business_clicks,
            'blog_product_clicks' => $blog_product_clicks,
            'blog_business_clicks' => $blog_business_clicks,
            'top_blog_article_title' => !empty($top_blog_article->title) ? (string) $top_blog_article->title : '-',
            'top_blog_article_url' => !empty($top_blog_article->url) ? (string) $top_blog_article->url : '',
            'top_blog_article_type' => !empty($top_blog_article->utm_medium) && $top_blog_article->utm_medium === $blog_business_medium ? 'business' : 'product',
            'funnel' => $this->get_funnel_payload($user_id, $period_start_datetime),
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

        return mb_substr($value, 0, $max_length);
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

        if(str_contains($top_source, 'sami') || str_contains($top_source, 'bez druge mreze') || str_contains($top_source, 'without another network')) {
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
                            '- Nemoj koristiti programerske, analiticke ni marketinske izraze koje pocetnik ne razumije, kao sto su direct, mobile, desktop, CTR, CTA, funnel, conversion rate i slicno.',
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
                            '- Ako korisnik vec ima funnel ili Forever blokove, procijeni jesu li sada korisni ili samo kompliciraju put. Ako nema dovoljno strukture, reci sto prvo treba postaviti. Ako ima previse strukture, reci sto treba maknuti ili spustiti.',
                            '- Ako nema dovoljno signala, plan i dalje mora biti konkretan, ali s fokusom na stvaranje kvalitetnog signala, ne na genericke savjete.',
                            '- Izbjegavaj pretrpavanje. Plan treba djelovati kao da coach zna sto je najvaznije, a ne kao da zeli ugurati 30 zadataka.',
                            '- Nemoj koristiti markdown, code blockove ni dodatne kljuceve.',
                            'Input JSON: ' . json_encode($ai_input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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

        /* Custom code: FC-2026-03-31: Temporary admin-only rollout for next step flow */
        if(!\Altum\Authentication::is_admin()) {
            if(!empty($_POST)) {
                Alerts::add_info(l('ai_plan.unavailable_notice'));
            }

            $view = new \Altum\View('ai-plan/index', (array) $this);
            $this->add_view_content('content', $view->run([
                'feature_is_available' => false,
            ]));

            return;
        }
        /* /Custom code: FC-2026-03-31 */

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
        $latest_app_review = $this->get_latest_app_review($app_reviews);
        $current_clicks_30d = $this->get_last_30_days_shop_clicks();
        $analytics_payload = $this->get_analytics_payload($this->user->user_id, $current_clicks_30d);
        $app_structure_payload = $this->get_app_structure_payload($this->user->user_id);
        $adaptive_question = $this->get_adaptive_question($values, $current_clicks_30d, $analytics_payload);
        $latest_weekly_plan = $this->get_latest_weekly_plan($weekly_plans, $latest_weekly_checkin);
        $latest_weekly_outcome = $this->get_latest_weekly_outcome($weekly_outcomes, $latest_weekly_checkin);
        $previous_weekly_cycle_context = $this->get_previous_weekly_cycle_context($weekly_checkins, $weekly_plans, $weekly_outcomes, $latest_weekly_checkin);
        $feedback_loop_payload = $this->get_feedback_loop_payload($previous_weekly_cycle_context);
        $has_weekly_limits_bypass = \Altum\Authentication::is_admin();
        $app_review_access_payload = $this->get_app_review_plan_access_payload();
        $cooldown_payload = $this->get_weekly_cooldown_payload($latest_weekly_checkin);
        $app_review_cooldown_payload = $this->get_cooldown_payload_by_days($latest_app_review['generated_at'] ?? null, (int) $app_review_access_payload['cooldown_days']);
        $is_profile_complete = $this->is_profile_complete($values);
        $is_weekly_plan_eligible = $has_weekly_limits_bypass || $current_clicks_30d >= 15;
        $is_weekly_submission_locked = $has_weekly_limits_bypass ? false : $cooldown_payload['is_locked'];
        $weekly_next_checkin_at = $has_weekly_limits_bypass ? null : $cooldown_payload['next_checkin_at'];
        $is_app_review_locked = $has_weekly_limits_bypass ? false : $app_review_cooldown_payload['is_locked'];
        $app_review_next_at = $has_weekly_limits_bypass ? null : $app_review_cooldown_payload['next_checkin_at'];
        $is_profile_complete_for_weekly = $has_weekly_limits_bypass || $is_profile_complete;
        $app_review_context = input_clean($_POST['app_review_context'] ?? '', 800);
        $requested_selected_app_id = (int) ($_POST['app_review_selected_link_id'] ?? 0);

        if(!$requested_selected_app_id && !empty($latest_app_review['selected_link_id']) && !empty($app_review_access_payload['can_select_any_app'])) {
            $requested_selected_app_id = (int) ($latest_app_review['selected_link_id'] ?? 0);
        }

        $selected_app = $this->get_selected_app($app_structure_payload, $requested_selected_app_id);

        if(!$app_review_access_payload['can_select_any_app']) {
            $selected_app = $this->get_selected_app($app_structure_payload, (int) ($app_structure_payload['top_app_link_id'] ?? 0));
        }

        $selected_app = $selected_app ?? $this->get_selected_app($app_structure_payload);
        $selected_app_id = (int) ($selected_app['link_id'] ?? 0);
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

                    redirect('ai-plan');
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
                    Alerts::add_error(sprintf(l('ai_plan.weekly_locked_signal'), 15, nr($current_clicks_30d)));
                }

                if($is_weekly_submission_locked) {
                    Alerts::add_error(sprintf(l('ai_plan.weekly_locked_cooldown'), \Altum\Date::get($cooldown_payload['next_checkin_at'], 2)));
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

                    redirect('ai-plan');
                }
            }

            if(isset($_POST['generate_app_review'])) {
                if(!\Altum\Csrf::check()) {
                    Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                }

                if(!$is_profile_complete_for_weekly) {
                    Alerts::add_error(l('ai_plan.app_review_locked_profile'));
                }

                if($is_app_review_locked) {
                    Alerts::add_error(sprintf(l('ai_plan.app_review_locked_cooldown'), \Altum\Date::get($app_review_cooldown_payload['next_checkin_at'], 2)));
                }

                if($app_review_access_payload['can_select_any_app']) {
                    if(!$selected_app_id) {
                        Alerts::add_error(l('ai_plan.app_review_app_required'));
                    }
                } else {
                    $selected_app = $this->get_selected_app($app_structure_payload, (int) ($app_structure_payload['top_app_link_id'] ?? 0));
                    $selected_app_id = (int) ($selected_app['link_id'] ?? 0);
                }

                if(!$selected_app_id) {
                    Alerts::add_error(l('ai_plan.app_review_app_required'));
                }

                if(!Alerts::has_errors()) {
                    try {
                        $new_app_review = $this->generate_app_review($values, $analytics_payload, $app_structure_payload, $current_clicks_30d, $app_review_context, $selected_app);
                        $app_reviews = $this->upsert_app_review($app_reviews, $new_app_review);
                        $preferences->leader_ai_app_reviews = $app_reviews;

                        db()->where('user_id', $this->user->user_id)->update('users', [
                            'preferences' => json_encode($preferences),
                        ]);

                        cache()->deleteItemsByTag('user_id=' . $this->user->user_id);
                        cache()->deleteItem('user?user_id=' . $this->user->user_id);

                        \Altum\Logger::users($this->user->user_id, 'ai_plan.app_review_generated');
                        Alerts::add_success(l('ai_plan.app_review_success_message'));
                    } catch(\Throwable $exception) {
                        Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('ai_plan.ai_error_request_failed'));
                    }

                    redirect('ai-plan');
                }
            }

            if(isset($_POST['regenerate_ai_plan'])) {
                if(!\Altum\Csrf::check()) {
                    Alerts::add_error(l('global.error_message.invalid_csrf_token'));
                } elseif(!$latest_weekly_checkin) {
                    Alerts::add_error(l('ai_plan.ai_plan_empty'));
                } else {
                    try {
                        $new_weekly_plan = $this->generate_weekly_ai_plan($values, $latest_weekly_checkin, $weekly_checkins, $weekly_plans, $weekly_outcomes, $analytics_payload, $app_structure_payload, $adaptive_question);
                        $weekly_plans = $this->upsert_weekly_plan($weekly_plans, $new_weekly_plan);
                        $preferences->leader_ai_weekly_plans = $weekly_plans;

                        db()->where('user_id', $this->user->user_id)->update('users', [
                            'preferences' => json_encode($preferences),
                        ]);

                        cache()->deleteItemsByTag('user_id=' . $this->user->user_id);
                        cache()->deleteItem('user?user_id=' . $this->user->user_id);

                        \Altum\Logger::users($this->user->user_id, 'ai_plan.weekly_plan_regenerated');
                        Alerts::add_success(l('ai_plan.ai_plan_regenerated'));
                    } catch(\Throwable $exception) {
                        Alerts::add_error($this->sanitize_ai_string($exception->getMessage(), 280) ?: l('ai_plan.ai_error_request_failed'));
                    }

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
                } elseif(!$latest_weekly_checkin || !$latest_weekly_plan) {
                    Alerts::add_error(l('ai_plan.outcome_locked'));
                }

                if(!Alerts::has_field_errors() && !Alerts::has_errors()) {
                    $new_outcome = [
                        'checkin_submitted_at' => $latest_weekly_checkin['submitted_at'] ?? get_date(),
                        'completion_level' => $completion_level,
                        'best_response' => $best_response,
                        'main_blocker_now' => $main_blocker_now,
                        'biggest_lesson' => $biggest_lesson,
                        'next_adjustment' => $next_adjustment,
                        'submitted_at' => get_date(),
                    ];

                    $weekly_outcomes = array_values(array_filter($weekly_outcomes, static function($outcome) use ($new_outcome) {
                        return (string) ($outcome['checkin_submitted_at'] ?? '') !== (string) ($new_outcome['checkin_submitted_at'] ?? '');
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

                    redirect('ai-plan');
                }
            }
        }

        $data = [
            'feature_is_available' => true,
            'values' => $values,
            'options' => $options,
            'weekly_values' => $weekly_values,
            'weekly_options' => $weekly_options,
            'weekly_checkins' => $weekly_checkins,
            'latest_weekly_checkin' => $latest_weekly_checkin,
            'weekly_plans' => $weekly_plans,
            'latest_weekly_plan' => $latest_weekly_plan,
            'weekly_outcomes' => $weekly_outcomes,
            'latest_weekly_outcome' => $latest_weekly_outcome,
            'app_reviews' => $app_reviews,
            'latest_app_review' => $latest_app_review,
            'app_review_context' => $app_review_context,
            'app_review_access_payload' => $app_review_access_payload,
            'has_admin_testing_access' => $has_weekly_limits_bypass,
            'app_review_available_apps' => $available_app_options,
            'app_review_selected_link_id' => $selected_app_id,
            'app_review_selected_app' => $selected_app,
            'feedback_loop_payload' => $feedback_loop_payload,
            'current_clicks_30d' => $current_clicks_30d,
            'analytics_payload' => $analytics_payload,
            'app_structure_payload' => $app_structure_payload,
            'adaptive_question' => $adaptive_question,
            'is_weekly_plan_eligible' => $is_weekly_plan_eligible,
            'is_profile_complete' => $is_profile_complete_for_weekly,
            'weekly_is_locked' => $is_weekly_submission_locked,
            'weekly_next_checkin_at' => $weekly_next_checkin_at,
            'weekly_countdown_days' => $this->get_weekly_checkin_countdown_days($weekly_next_checkin_at),
            'app_review_is_locked' => $is_app_review_locked,
            'app_review_next_at' => $app_review_next_at,
            'app_review_countdown_days' => $this->get_weekly_checkin_countdown_days($app_review_next_at),
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