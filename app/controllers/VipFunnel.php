<?php
/*
 * Copyright (c) 2026 AltumCode (https://altumcode.com/)
 *
 * This software is licensed exclusively by AltumCode and is sold only via https://altumcode.com/.
 * Unauthorized distribution, modification, or use of this software without a valid license is not permitted and may be subject to applicable legal actions.
 */

namespace Altum\Controllers;

use Altum\Alerts;
use Altum\Meta;
use Altum\Title;

defined('ALTUMCODE') || die();

class VipFunnel extends Controller {

    public function index() {
        $user_id = (int) ($this->params[0] ?? 0);
        $requested_slug = query_clean((string) ($this->params[1] ?? ''));
        $requested_step_id = input_clean((string) ($_GET['step'] ?? ''), 128);
        $state = vip_funnel_get_public_step_state($user_id, $requested_step_id, $requested_slug);

        if(!$state) {
            throw_404();
        }

        vip_funnel_apply_owner_referral_cookies($user_id);

        if($requested_slug === '' || $requested_slug !== ($state['slug'] ?? '')) {
            header('Location: ' . $state['canonical_url']);
            die();
        }

        if(!empty($_POST)) {
            if(!empty($_POST['vf_track_event'])) {
                vip_funnel_process_public_tracking($state, $_POST);
                http_response_code(204);
                die();
            }

            $submission = vip_funnel_process_public_submission($state, $_POST);

            if(!empty($submission['success']) && !empty($submission['redirect_url'])) {
                header('Location: ' . $submission['redirect_url']);
                die();
            }

            if(empty($submission['success'])) {
                Alerts::add_error($submission['message'] ?? l('global.error_message.basic'));
            }
        }

        $run_id = vip_funnel_get_or_create_public_run($state);
        vip_funnel_log_public_event($state, 'view', (string) ($state['page_key'] ?? 'landing'), [], 0, $run_id);
        vip_funnel_log_public_block_views($state, $run_id);

        $payload = $state['payload'] ?? [];
        $title = trim((string) ($payload['overview']['headline'] ?? ($payload['funnel']['name'] ?? 'VIP Funnel 2.0')));
        $description = trim((string) ($state['active']['summary'] ?? ($payload['overview']['subheadline'] ?? '')));
        $page_surface = is_array($state['page_surface'] ?? null) ? $state['page_surface'] : [];
        $landing_surface = is_array($payload['landing_page'] ?? null) ? $payload['landing_page'] : [];
        $social_image = $this->resolve_social_image_url([
            $page_surface['seo_image_url'] ?? '',
            $landing_surface['seo_image_url'] ?? '',
        ]);

        Title::set($title !== '' ? $title : 'VIP Funnel 2.0');
        Meta::set_description(string_truncate($description !== '' ? $description : $title, 160));
        Meta::set_canonical_url($state['canonical_url']);
        if($social_image !== '') {
            Meta::set_social_image($social_image);
            foreach($this->get_social_image_meta_tags($social_image, $title) as $meta_key => $meta_value) {
                Meta::$opengraph[$meta_key] = $meta_value;
            }
            Meta::$twitter['twitter:image:alt'] = string_truncate($title !== '' ? $title : 'VIP Funnel 2.0', 420);
        }

        $view = new \Altum\View('vip-funnel-public/index', (array) $this);
        $this->add_view_content('content', $view->run([
            'state' => $state,
        ]));
    }

    private function resolve_social_image_url(array $candidates): string {
        foreach($candidates as $candidate_social_image) {
            $social_image = $this->normalize_social_image_url($candidate_social_image);

            if($social_image !== '') {
                return $social_image;
            }
        }

        return '';
    }

    private function normalize_social_image_url($value): string {
        $social_image = trim((string) $value);

        if($social_image === '') {
            return '';
        }

        $social_image = preg_replace('~[\x00-\x1F\x7F]+~', '', $social_image);
        $social_image = str_replace(['"', '<', '>'], '', $social_image);

        if(str_starts_with($social_image, '//')) {
            $social_image = 'https:' . $social_image;
        } else if(!preg_match('~^https?://~i', $social_image)) {
            $social_image = url(ltrim($social_image, '/'));
        }

        $url_parts = parse_url($social_image);

        if(!is_array($url_parts)) {
            return '';
        }

        if(empty($url_parts['scheme']) || empty($url_parts['host']) || !in_array(mb_strtolower($url_parts['scheme']), ['http', 'https'], true)) {
            return '';
        }

        $site_parts = parse_url(SITE_URL);
        if(
            mb_strtolower($url_parts['scheme']) === 'http'
            && mb_strtolower($site_parts['scheme'] ?? '') === 'https'
            && mb_strtolower($url_parts['host']) === mb_strtolower($site_parts['host'] ?? '')
        ) {
            $social_image = 'https://' . substr($social_image, 7);
        }

        $extension = mb_strtolower(pathinfo((string) ($url_parts['path'] ?? ''), PATHINFO_EXTENSION));

        if(in_array($extension, ['svg', 'avif'], true)) {
            return '';
        }

        return $social_image;
    }

    private function get_social_image_meta_tags(string $social_image, string $title): array {
        $image_type = $this->get_social_image_mime_type($social_image);
        $meta_tags = [
            'og:image:secure_url' => preg_match('~^https://~i', $social_image) ? $social_image : '',
            'og:image:width' => '1200',
            'og:image:height' => '630',
            'og:image:alt' => string_truncate($title !== '' ? $title : 'VIP Funnel 2.0', 420),
        ];

        if($image_type !== '') {
            $meta_tags['og:image:type'] = $image_type;
        }

        return array_filter($meta_tags);
    }

    private function get_social_image_mime_type(string $social_image): string {
        $extension = mb_strtolower(pathinfo((string) (parse_url($social_image, PHP_URL_PATH) ?? ''), PATHINFO_EXTENSION));

        return [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ][$extension] ?? '';
    }
}
