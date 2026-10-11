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

use Altum\Captcha;
use Altum\Response;

defined('ALTUMCODE') || die();

class FccAiPublic extends Controller {

    public function index() {
        $this->status();
    }

    public function status() {
        fcc_ai_ensure_tables();

        try {
            $productAdvisorV2 = (new \Altum\Helpers\FccProductAdvisorV2())->getStatus();
        } catch(\Throwable $exception) {
            $productAdvisorV2 = [
                'feature_enabled' => false,
                'schema_ready' => false,
                'ready' => false,
                'engine' => 'fcc_product_advisor_v2',
            ];
        }

        $assistant_types = [];
        foreach(fcc_ai_get_public_assistant_types() as $assistant_type => $definition) {
            $assistant_types[$assistant_type] = [
                'label' => (string) ($definition['label'] ?? $assistant_type),
                'description' => (string) ($definition['description'] ?? ''),
                'supports_lead_capture' => (bool) ($definition['supports_lead_capture'] ?? false),
                'supports_blog_continuation' => (bool) ($definition['supports_blog_continuation'] ?? false),
            ];
        }

        Response::json([
            'version' => 'phase2',
            'tables_ready' => fcc_ai_tables_ready(),
            'assistant_types' => $assistant_types,
            'continuation_storage_key' => fcc_ai_get_public_storage_key(),
            'context_storage_key' => fcc_ai_get_public_context_storage_key(),
            'lead_capture_type' => 'ai_chat_lead',
            'provider' => [
                'openai_ready' => fcc_ai_get_openai_api_key() !== '',
                'default_model' => fcc_ai_resolve_assistant_model(),
            ],
            'product_advisor_v2' => $productAdvisorV2,
        ]);
    }

    public function conversation() {
        if(empty($_POST)) {
            die();
        }

        try {
            $state = fcc_ai_create_or_resume_public_conversation([
                'assistant_type' => input_clean((string) ($_POST['assistant_type'] ?? ''), 32),
                'scope' => input_clean((string) ($_POST['scope'] ?? ''), 32),
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'link_id' => (int) ($_POST['link_id'] ?? 0),
                'visitor_key' => input_clean((string) ($_POST['visitor_key'] ?? ''), 64),
            ]);

            Response::json('FCC AI conversation ready.', 'success', self::sanitizeProductCardHydrationV2($state));
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }

    public function message() {
        if(empty($_POST)) {
            die();
        }

        try {
            $result = fcc_ai_handle_public_message([
                'assistant_type' => input_clean((string) ($_POST['assistant_type'] ?? ''), 32),
                'scope' => input_clean((string) ($_POST['scope'] ?? ''), 32),
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'link_id' => (int) ($_POST['link_id'] ?? 0),
                'blog_post_id' => (int) ($_POST['blog_post_id'] ?? 0),
                'language' => input_clean((string) ($_POST['language'] ?? ''), 16),
                'source_context' => input_clean((string) ($_POST['source_context'] ?? ''), 255),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 128),
                'source_page_title' => input_clean((string) ($_POST['source_page_title'] ?? ''), 255),
                'source_page_section' => input_clean((string) ($_POST['source_page_section'] ?? ''), 128),
                'market_code' => $this->getRequestMarketCode(),
                'visitor_key' => input_clean((string) ($_POST['visitor_key'] ?? ''), 64),
                'message' => input_clean((string) ($_POST['message'] ?? ''), 4000),
            ]);

            Response::json('FCC AI odgovor je spreman.', 'success', self::sanitizeProductCardHydrationV2($result));
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }

    public function feedback() {
        if(empty($_POST)) {
            die();
        }

        try {
            $result = fcc_ai_capture_public_message_feedback([
                'assistant_type' => input_clean((string) ($_POST['assistant_type'] ?? ''), 32),
                'scope' => input_clean((string) ($_POST['scope'] ?? ''), 32),
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'link_id' => (int) ($_POST['link_id'] ?? 0),
                'blog_post_id' => (int) ($_POST['blog_post_id'] ?? 0),
                'language' => input_clean((string) ($_POST['language'] ?? ''), 16),
                'source_context' => input_clean((string) ($_POST['source_context'] ?? ''), 255),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 128),
                'source_page_title' => input_clean((string) ($_POST['source_page_title'] ?? ''), 255),
                'source_page_section' => input_clean((string) ($_POST['source_page_section'] ?? ''), 128),
                'market_code' => $this->getRequestMarketCode(),
                'visitor_key' => input_clean((string) ($_POST['visitor_key'] ?? ''), 64),
                'message_id' => (int) ($_POST['message_id'] ?? 0),
                'feedback_type' => input_clean((string) ($_POST['feedback_type'] ?? ''), 16),
                'reason' => input_clean((string) ($_POST['reason'] ?? ''), 32),
                'note' => input_clean((string) ($_POST['note'] ?? ''), 1000),
            ]);

            Response::json('Feedback je spremljen.', 'success', self::sanitizeProductCardHydrationV2($result));
        } catch(\Throwable $exception) {
            Response::json($exception->getMessage(), 'error');
        }
    }

    public function product_card_click() {
        if(empty($_POST)) {
            die();
        }

        try {
            $result = self::captureProductCardClickV2($_POST, [
                'request_method' => (string) ($_SERVER['REQUEST_METHOD'] ?? ''),
                'content_type' => (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''),
                'sec_fetch_site' => (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''),
                'origin' => (string) ($_SERVER['HTTP_ORIGIN'] ?? ''),
                'referer' => (string) ($_SERVER['HTTP_REFERER'] ?? ''),
            ]);

            Response::json('Klik na preporučeni proizvod je zabilježen.', 'success', [
                'accepted' => true,
                'deduplicated' => !empty($result['deduplicated']),
            ]);
        } catch(\Throwable $exception) {
            /* Keep this response generic: the public endpoint must not become a conversation/card oracle. */
            Response::json('Klik nije prihvaćen.', 'error');
        }
    }

    public function advisor_action() {
        if(($_SERVER['REQUEST_METHOD']??'')!=='POST')die();
        try {
            require_once APP_PATH.'helpers/fcc_public_advisor.php';
            Response::json('Action recorded.','success',fcc_advisor_capture_action($_POST));
        } catch(\Throwable $e){Response::json('Action unavailable.','error');}
    }

    public function lead() {
        if(empty($_POST)) {
            die();
        }

        if(settings()->captcha->biolink_is_enabled && settings()->captcha->type != 'basic' && !(new Captcha())->is_valid()) {
            Response::json(l('global.error_message.invalid_captcha'), 'error');
        }

        if(!array_key_exists('consent_contact', $_POST) || (string) $_POST['consent_contact'] !== '1') {
            Response::json('Potrebna je potvrda da suradnik smije kontaktirati posjetitelja.', 'error');
        }

        if(array_key_exists('lead_type', $_POST) && trim((string) $_POST['lead_type']) !== '') {
            Response::json('Kontakt nije prihvaćen.', 'error');
        }

        try {
            $result = fcc_ai_capture_public_lead([
                'assistant_type' => input_clean((string) ($_POST['assistant_type'] ?? ''), 32),
                'scope' => input_clean((string) ($_POST['scope'] ?? ''), 32),
                'conversation_public_id' => input_clean((string) ($_POST['conversation_public_id'] ?? ''), 64),
                'link_id' => (int) ($_POST['link_id'] ?? 0),
                'blog_post_id' => (int) ($_POST['blog_post_id'] ?? 0),
                'language' => input_clean((string) ($_POST['language'] ?? ''), 16),
                'source_context' => input_clean((string) ($_POST['source_context'] ?? ''), 255),
                'source_page_url' => input_clean((string) ($_POST['source_page_url'] ?? ''), 2048),
                'source_page_slug' => input_clean((string) ($_POST['source_page_slug'] ?? ''), 128),
                'source_page_title' => input_clean((string) ($_POST['source_page_title'] ?? ''), 255),
                'source_page_section' => input_clean((string) ($_POST['source_page_section'] ?? ''), 128),
                'market_code' => $this->getRequestMarketCode(),
                'visitor_key' => input_clean((string) ($_POST['visitor_key'] ?? ''), 64),
                'lead_offer_id' => input_clean((string) ($_POST['lead_offer_id'] ?? ''), 64),
                'consent_contact' => (string) ($_POST['consent_contact'] ?? ''),
                'name' => input_clean((string) ($_POST['name'] ?? ''), 128),
                'email' => input_clean_email((string) ($_POST['email'] ?? '')),
                'phone' => input_clean((string) ($_POST['phone'] ?? ''), 32),
                'phone_country_code' => input_clean((string) ($_POST['phone_country_code'] ?? 'HR'), 8),
                'preferred_contact_channel' => input_clean((string) ($_POST['preferred_contact_channel'] ?? 'whatsapp'), 16),
                'country_code' => input_clean((string) ($_POST['country_code'] ?? ''), 8),
            ]);

            Response::json('Kontakt je uz vašu privolu poslan vlasniku ove aplikacije.', 'success', $result);
        } catch(\Throwable $exception) {
            /* Keep owner, offer and replay checks from becoming a public oracle. */
            Response::json('Kontakt nije prihvaćen. Provjerite podatke i privolu pa pokušajte ponovno.', 'error');
        }
    }

    /**
     * Revalidate persisted cards before they are sent back to the browser.
     * Old messages remain readable, but a stale card never remains actionable.
     */
    private static function sanitizeProductCardHydrationV2(array $details, ?array $runtime_context = null): array {
        $has_product_card = false;
        foreach((array) ($details['messages'] ?? []) as $message) {
            if(is_array($message) && is_array($message['meta']['product_card'] ?? null)) {
                $has_product_card = true;
                break;
            }
        }
        if(!$has_product_card && is_array($details['reply']['meta']['product_card'] ?? null)) {
            $has_product_card = true;
        }
        if(!$has_product_card) {
            return $details;
        }

        if($runtime_context === null) {
            try {
                $runtime_context = self::buildProductCardRuntimeContextV2($details);
            } catch(\Throwable $exception) {
                $runtime_context = [];
            }
        }

        if(is_array($details['messages'] ?? null)) {
            foreach($details['messages'] as &$message) {
                if(!is_array($message) || !is_array($message['meta'] ?? null) || !is_array($message['meta']['product_card'] ?? null)) {
                    continue;
                }
                if(!self::productCardSnapshotIsCurrentV2($message['meta']['product_card'], $message['meta'], $runtime_context)) {
                    unset($message['meta']['product_card']);
                }
            }
            unset($message);
        }

        if(is_array($details['reply']['meta']['product_card'] ?? null)) {
            if(!self::productCardSnapshotIsCurrentV2($details['reply']['meta']['product_card'], $details['reply']['meta'], $runtime_context)) {
                unset($details['reply']['meta']['product_card']);
            }
        }

        return $details;
    }

    private static function buildProductCardRuntimeContextV2(array $details): array {
        $config = require APP_PATH . 'config/fcc_ai_product_advisor_v2.php';
        $feature = is_array($config['feature'] ?? null) ? $config['feature'] : [];
        $configured_revision_id = (int) ($feature['catalog_revision_id'] ?? 0);
        $configured_revision_key = trim((string) ($feature['catalog_revision_key'] ?? ''));
        $configured_bundle_hash = mb_strtolower(trim((string) ($feature['catalog_bundle_hash'] ?? '')));
        if(preg_match('/^[a-f0-9]{64}$/D', $configured_bundle_hash) !== 1) {
            return [];
        }
        $repository = new \Altum\Helpers\FccProductCatalogRepository();
        $revision = $repository->getRuntimeRevision(
            $configured_revision_id > 0 ? $configured_revision_id : null,
            $configured_revision_key !== '' ? $configured_revision_key : null,
            $configured_bundle_hash
        );
        if(!$revision) {
            return [];
        }

        $conversation_id = (int) ($details['conversation_id'] ?? 0);
        $conversation = $conversation_id > 0 ? fcc_ai_get_conversation_by_id($conversation_id) : null;
        if(!$conversation && !empty($details['conversation_public_id'])) {
            $conversation = fcc_ai_get_conversation_by_public_id((string) $details['conversation_public_id']);
        }
        if(!$conversation || (int) ($conversation->user_id ?? 0) <= 0 || (int) ($conversation->link_id ?? 0) <= 0) {
            return [];
        }

        $user = db()->where('user_id', (int) $conversation->user_id)->getOne('users');
        $link = db()->where('link_id', (int) $conversation->link_id)->getOne('links');
        if(!$user || !$link || (int) ($user->status ?? 0) !== 1 || (int) ($link->user_id ?? 0) !== (int) $user->user_id) {
            return [];
        }
        if(!\Altum\Helpers\FccProductAdvisorV2::isFeatureEnabled((int) $user->user_id)) {
            return [];
        }
        $advisor = new \Altum\Helpers\FccProductAdvisorV2();

        $forever_id = self::getValidatedProductCardFboIdV2($user);
        if($forever_id === '') {
            return [];
        }
        $language_code = mb_strtolower(trim((string) ($details['language'] ?? $conversation->language ?? '')));
        $language_code = fcc_ai_normalize_language($language_code);
        $language_code = in_array($language_code, array_values(\Altum\Language::$active_languages), true) ? $language_code : '';
        if($language_code === '') {
            return [];
        }

        $cards = [];
        foreach((array) ($details['messages'] ?? []) as $message) {
            if(is_array($message['meta']['product_card'] ?? null)) {
                $cards[] = $message['meta']['product_card'];
            }
        }
        if(is_array($details['reply']['meta']['product_card'] ?? null)) {
            $cards[] = $details['reply']['meta']['product_card'];
        }

        $products = [];
        $catalog_by_market = [];
        $supported_scopes = [];
        $discount_params_by_market = [];
        foreach($cards as $card) {
            $market_code = mb_strtolower(trim((string) ($card['market_code'] ?? '')));
            $product_key = mb_strtolower(trim((string) ($card['product_key'] ?? '')));
            if(preg_match('/^[a-z]{2}$/D', $market_code) !== 1 || $product_key === '') {
                continue;
            }
            $scope_key = $language_code . '|' . $market_code;
            if(!$advisor->supportsScope($language_code, $market_code)) {
                continue;
            }
            $supported_scopes[$scope_key] = true;
            $product_index_key = $market_code . '|' . $product_key;
            if(isset($products[$product_index_key])) {
                continue;
            }
            if(!array_key_exists($market_code, $catalog_by_market)) {
                $catalog_by_market[$market_code] = $repository->getCatalog(
                    $language_code,
                    $market_code,
                    (int) $revision['revision_id'],
                    (string) $revision['revision_key'],
                    $configured_bundle_hash
                );
            }
            foreach((array) $catalog_by_market[$market_code] as $catalog_row) {
                if((string) ($catalog_row['product_key'] ?? '') !== $product_key) {
                    continue;
                }

                $expected_url = '';
                if(
                    (string) ($catalog_row['advisor_status'] ?? '') === 'recommendable'
                    && (string) ($catalog_row['availability_status'] ?? '') === 'available'
                    && trim((string) ($catalog_row['official_url'] ?? '')) !== ''
                ) {
                    if(!array_key_exists($market_code, $discount_params_by_market)) {
                        $discount_params_by_market[$market_code] = \Altum\Link::get_main_biolink_discount_query_params((int) $user->user_id, $market_code);
                    }
                    $built_url = \Altum\Link::build_forever_destination_url(
                        (string) $catalog_row['official_url'],
                        $forever_id,
                        $market_code,
                        $discount_params_by_market[$market_code]
                    );
                    $expected_url = is_string($built_url) ? $built_url : '';
                }

                $products[$product_index_key] = [
                    'product_key' => $product_key,
                    'canonical_name' => (string) ($catalog_row['canonical_name'] ?? ''),
                    'advisor_status' => (string) ($catalog_row['advisor_status'] ?? ''),
                    'availability_status' => (string) ($catalog_row['availability_status'] ?? ''),
                    'official_url' => $expected_url,
                ];
                break;
            }
        }

        return [
            'feature_enabled' => true,
            'partner_fbo_id_valid' => true,
            'revision_id' => (int) $revision['revision_id'],
            'revision_key' => (string) $revision['revision_key'],
            'supported_scopes' => $supported_scopes,
            'products' => $products,
        ];
    }

    private static function getValidatedProductCardFboIdV2(object $user): string {
        $preferences = $user->preferences ?? null;
        if(is_string($preferences)) {
            $preferences = json_decode($preferences);
        }
        $preferences_meta = is_object($preferences) ? ($preferences->meta ?? null) : null;
        $forever_id = is_object($preferences_meta) && function_exists('forever_business_normalize_fbo_id')
            ? \forever_business_normalize_fbo_id($preferences_meta->foreverId ?? '')
            : '';

        return preg_match('/^\d{12}$/D', $forever_id) === 1 ? $forever_id : '';
    }

    private static function productCardSnapshotIsCurrentV2(array $card, array $message_meta, array $runtime_context): bool {
        $product_key = mb_strtolower(trim((string) ($card['product_key'] ?? '')));
        $product_name = trim((string) ($card['product_name'] ?? ''));
        $market_code = mb_strtolower(trim((string) ($card['market_code'] ?? '')));
        $revision_id = (int) ($card['catalog_revision_id'] ?? 0);
        $revision_key = trim((string) ($card['catalog_revision_key'] ?? ''));
        $official_url = trim((string) ($card['official_url'] ?? ''));
        $catalog_product = $runtime_context['products'][$market_code . '|' . $product_key] ?? null;
        $language_code = mb_strtolower(trim((string) ($message_meta['language'] ?? '')));
        $language_code = fcc_ai_normalize_language($language_code);
        $language_code = in_array($language_code, array_values(\Altum\Language::$active_languages), true) ? $language_code : '';
        $validation = $message_meta['validation'] ?? null;
        $selected_product_keys = array_map(
            static fn($key): string => mb_strtolower(trim((string) $key)),
            is_array($message_meta['selected_product_keys'] ?? null) ? $message_meta['selected_product_keys'] : []
        );

        return ($runtime_context['feature_enabled'] ?? null) === true
            && ($runtime_context['partner_fbo_id_valid'] ?? null) === true
            && ($runtime_context['supported_scopes'][$language_code . '|' . $market_code] ?? null) === true
            && ($card['v2'] ?? null) === true
            && ($card['recommendation_eligible'] ?? null) === true
            && ($card['advisor_status'] ?? '') === 'recommendable'
            && ($card['shop_available'] ?? null) === true
            && ($message_meta['engine'] ?? '') === 'fcc_product_advisor_v2'
            && ($message_meta['decision'] ?? '') === 'answer'
            && ($message_meta['safety_level'] ?? '') === 'routine'
            && is_array($validation)
            && ($validation['valid'] ?? null) === true
            && in_array($product_key, $selected_product_keys, true)
            && $revision_id > 0
            && $revision_id === (int) ($runtime_context['revision_id'] ?? 0)
            && $revision_id === (int) ($message_meta['catalog_revision_id'] ?? 0)
            && $revision_key !== ''
            && hash_equals((string) ($runtime_context['revision_key'] ?? ''), $revision_key)
            && hash_equals((string) ($message_meta['catalog_revision_key'] ?? ''), $revision_key)
            && $market_code !== ''
            && mb_strtolower(trim((string) ($message_meta['market_code'] ?? ''))) === $market_code
            && is_array($catalog_product)
            && (string) ($catalog_product['advisor_status'] ?? '') === 'recommendable'
            && (string) ($catalog_product['availability_status'] ?? '') === 'available'
            && hash_equals((string) ($catalog_product['product_key'] ?? ''), $product_key)
            && $product_name !== ''
            && hash_equals((string) ($catalog_product['canonical_name'] ?? ''), $product_name)
            && $official_url !== ''
            && hash_equals((string) ($catalog_product['official_url'] ?? ''), $official_url);
    }

    /**
     * Capture a V2 product-card click exclusively from the persisted server snapshot.
     *
     * The visitor_key is an unguessable per-link bearer secret stored by the widget. Together with
     * Fetch Metadata checks it provides CSRF resistance without enabling PHP sessions on public links.
     */
    private static function captureProductCardClickV2(array $payload, array $request_context, ?array $runtime_context = null): array {
        fcc_ai_ensure_tables();

        $allowed_fields = [
            'assistant_type',
            'scope',
            'conversation_public_id',
            'link_id',
            'visitor_key',
            'message_id',
            'product_key',
            'catalog_revision_id',
            'catalog_revision_key',
        ];

        foreach($payload as $field => $value) {
            if(!is_string($field) || !in_array($field, $allowed_fields, true) || !is_scalar($value)) {
                throw new \RuntimeException(fcc_t('Invalid product-card telemetry payload.'));
            }
        }

        foreach($allowed_fields as $field) {
            if(!array_key_exists($field, $payload)) {
                throw new \RuntimeException(fcc_t('Incomplete product-card telemetry payload.'));
            }
        }

        $request_method = strtoupper(trim((string) ($request_context['request_method'] ?? '')));
        $content_type = mb_strtolower(trim((string) ($request_context['content_type'] ?? '')));
        $sec_fetch_site = mb_strtolower(trim((string) ($request_context['sec_fetch_site'] ?? '')));
        if($request_method !== 'POST' || !str_starts_with($content_type, 'application/x-www-form-urlencoded')) {
            throw new \RuntimeException(fcc_t('Invalid product-card telemetry request.'));
        }
        if(!in_array($sec_fetch_site, ['', 'same-origin', 'same-site', 'none', 'cross-site'], true)) {
            throw new \RuntimeException(fcc_t('Invalid product-card telemetry request context.'));
        }

        $assistant_type = trim((string) $payload['assistant_type']);
        $scope = trim(mb_strtolower((string) $payload['scope']));
        $conversation_public_id = trim((string) $payload['conversation_public_id']);
        $visitor_key = trim((string) $payload['visitor_key']);
        $product_key = trim(mb_strtolower((string) $payload['product_key']));
        $revision_key = trim((string) $payload['catalog_revision_key']);

        if(
            $assistant_type !== 'product_advisor'
            || !in_array($scope, ['public_app', 'public_blog'], true)
            || preg_match('/^[a-zA-Z0-9_-]{16,64}$/D', $conversation_public_id) !== 1
            || preg_match('/^[a-zA-Z0-9_-]{16,64}$/D', $visitor_key) !== 1
            || preg_match('/^[a-z0-9][a-z0-9-]{0,127}$/D', $product_key) !== 1
            || preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,127}$/D', $revision_key) !== 1
        ) {
            throw new \RuntimeException(fcc_t('Invalid product-card telemetry identity.'));
        }

        foreach(['link_id', 'message_id', 'catalog_revision_id'] as $integer_field) {
            $raw_value = $payload[$integer_field];
            if(
                (!is_int($raw_value) && (!is_string($raw_value) || !ctype_digit($raw_value)))
                || (int) $raw_value <= 0
            ) {
                throw new \RuntimeException(fcc_t('Invalid product-card telemetry identifier.'));
            }
        }

        $link_id = (int) $payload['link_id'];
        $message_id = (int) $payload['message_id'];
        $revision_id = (int) $payload['catalog_revision_id'];
        $context = fcc_ai_resolve_public_owner_context([
            'conversation_public_id' => $conversation_public_id,
            'link_id' => $link_id,
            'visitor_key' => $visitor_key,
            'assistant_type' => $assistant_type,
            'scope' => $scope,
        ]);

        if(!$context || empty($context['conversation']) || empty($context['link']) || empty($context['user'])) {
            throw new \RuntimeException(fcc_t('Product-card telemetry scope was not found.'));
        }

        $conversation = $context['conversation'];
        if(
            (int) ($conversation->link_id ?? 0) !== $link_id
            || (string) ($conversation->assistant_type ?? '') !== $assistant_type
            || (string) ($conversation->scope ?? '') !== $scope
            || (string) ($conversation->status ?? '') !== 'active'
            || !hash_equals((string) ($conversation->public_id ?? ''), $conversation_public_id)
            || !hash_equals((string) ($conversation->visitor_key ?? ''), $visitor_key)
        ) {
            throw new \RuntimeException(fcc_t('Product-card telemetry scope mismatch.'));
        }

        self::assertProductCardClickOrigin($conversation, $request_context, $sec_fetch_site);

        $message = db()
            ->where('fcc_ai_message_id', $message_id)
            ->where('fcc_ai_conversation_id', (int) $conversation->fcc_ai_conversation_id)
            ->getOne('fcc_ai_messages', [
                'fcc_ai_message_id',
                'fcc_ai_conversation_id',
                'role',
                'message_type',
                'meta',
            ]);
        if(!$message || (string) ($message->role ?? '') !== 'assistant' || (string) ($message->message_type ?? '') !== 'chat') {
            throw new \RuntimeException(fcc_t('Product-card telemetry message was not found.'));
        }

        $message_meta = json_decode((string) ($message->meta ?? ''), true);
        if(!is_array($message_meta)) {
            throw new \RuntimeException(fcc_t('Product-card telemetry snapshot is invalid.'));
        }

        $product_card = $message_meta['product_card'] ?? null;
        $validation = $message_meta['validation'] ?? null;
        $selected_product_keys = $message_meta['selected_product_keys'] ?? null;
        if(
            !is_array($product_card)
            || !is_array($validation)
            || !is_array($selected_product_keys)
            || ($message_meta['engine'] ?? '') !== 'fcc_product_advisor_v2'
            || ($message_meta['decision'] ?? '') !== 'answer'
            || ($message_meta['safety_level'] ?? '') !== 'routine'
            || ($validation['valid'] ?? null) !== true
            || ($product_card['v2'] ?? null) !== true
            || ($product_card['recommendation_eligible'] ?? null) !== true
            || ($product_card['advisor_status'] ?? '') !== 'recommendable'
            || ($product_card['shop_available'] ?? null) !== true
        ) {
            throw new \RuntimeException(fcc_t('Product-card telemetry snapshot is not eligible.'));
        }

        $server_product_key = trim(mb_strtolower((string) ($product_card['product_key'] ?? '')));
        $server_revision_id = (int) ($product_card['catalog_revision_id'] ?? 0);
        $server_revision_key = trim((string) ($product_card['catalog_revision_key'] ?? ''));
        $server_market_code = trim(mb_strtolower((string) ($product_card['market_code'] ?? '')));
        $server_product_name = trim((string) ($product_card['product_name'] ?? ''));
        $server_official_url = trim((string) ($product_card['official_url'] ?? ''));
        if(
            $server_product_key === ''
            || $server_revision_id <= 0
            || $server_revision_key === ''
            || preg_match('/^[a-z]{2}$/D', $server_market_code) !== 1
            || $server_product_name === ''
            || mb_strlen($server_product_name) > 160
            || $server_official_url === ''
            || !hash_equals($server_product_key, $product_key)
            || $server_revision_id !== $revision_id
            || !hash_equals($server_revision_key, $revision_key)
            || (int) ($message_meta['catalog_revision_id'] ?? 0) !== $server_revision_id
            || !hash_equals((string) ($message_meta['catalog_revision_key'] ?? ''), $server_revision_key)
            || mb_strtolower(trim((string) ($message_meta['market_code'] ?? ''))) !== $server_market_code
            || !in_array($server_product_key, array_map(static fn($key) => mb_strtolower(trim((string) $key)), $selected_product_keys), true)
        ) {
            throw new \RuntimeException(fcc_t('Product-card telemetry snapshot mismatch.'));
        }

        if($runtime_context === null) {
            $runtime_context = self::buildProductCardRuntimeContextV2([
                'conversation_id' => (int) $conversation->fcc_ai_conversation_id,
                'conversation_public_id' => (string) $conversation->public_id,
                'language' => (string) ($message_meta['language'] ?? $conversation->language ?? ''),
                'messages' => [[
                    'meta' => $message_meta,
                ]],
            ]);
        }
        if(!self::productCardSnapshotIsCurrentV2($product_card, $message_meta, $runtime_context)) {
            throw new \RuntimeException(fcc_t('Product-card telemetry snapshot is stale.'));
        }

        $conversation_id = (int) $conversation->fcc_ai_conversation_id;
        $visitor_fingerprint = hash('sha256', $visitor_key);
        $recent_events = db()
            ->where('fcc_ai_conversation_id', $conversation_id)
            ->where('event_type', 'product_card_click_v2')
            ->where('datetime', date('Y-m-d H:i:s', time() - 3600), '>=')
            ->orderBy('fcc_ai_event_id', 'DESC')
            ->get('fcc_ai_events', 60, ['fcc_ai_event_id', 'meta', 'datetime']);

        foreach((array) $recent_events as $recent_event) {
            $recent_meta = json_decode((string) ($recent_event->meta ?? ''), true);
            if(
                is_array($recent_meta)
                && (int) ($recent_meta['message_id'] ?? 0) === $message_id
                && (string) ($recent_meta['product_key'] ?? '') === $server_product_key
                && (int) ($recent_meta['catalog_revision_id'] ?? 0) === $server_revision_id
                && (string) ($recent_meta['catalog_revision_key'] ?? '') === $server_revision_key
                && (string) ($recent_meta['visitor_fingerprint'] ?? '') === $visitor_fingerprint
                && strtotime((string) ($recent_event->datetime ?? '1970-01-01 00:00:00')) >= time() - 3
            ) {
                return [
                    'accepted' => true,
                    'deduplicated' => true,
                ];
            }
        }

        if(count((array) $recent_events) >= 60) {
            throw new \RuntimeException(fcc_t('Product-card telemetry rate limit exceeded.'));
        }

        $snapshot_fingerprint = hash('sha256', json_encode([
            'message_id' => $message_id,
            'product_key' => $server_product_key,
            'catalog_revision_id' => $server_revision_id,
            'catalog_revision_key' => $server_revision_key,
            'market_code' => $server_market_code,
            'recommendation_eligible' => true,
            'advisor_status' => 'recommendable',
            'shop_available' => true,
        ], JSON_UNESCAPED_SLASHES));

        fcc_ai_log_event([
            'fcc_ai_conversation_id' => $conversation_id,
            'user_id' => (int) ($conversation->user_id ?? 0),
            'assistant_type' => $assistant_type,
            'event_type' => 'product_card_click_v2',
            'link_id' => $link_id,
            'blog_post_id' => (int) ($conversation->blog_post_id ?? 0),
            'meta' => [
                'telemetry_version' => 2,
                'message_id' => $message_id,
                'product_key' => $server_product_key,
                'catalog_revision_id' => $server_revision_id,
                'catalog_revision_key' => $server_revision_key,
                'market_code' => $server_market_code,
                'scope' => $scope,
                'visitor_fingerprint' => $visitor_fingerprint,
                'snapshot_fingerprint' => $snapshot_fingerprint,
            ],
        ]);

        return [
            'accepted' => true,
            'deduplicated' => false,
        ];
    }

    private static function assertProductCardClickOrigin(object $conversation, array $request_context, string $sec_fetch_site): void {
        if($sec_fetch_site !== 'cross-site') {
            return;
        }

        $request_origin = trim((string) ($request_context['origin'] ?? ''));
        if($request_origin === '') {
            $request_origin = trim((string) ($request_context['referer'] ?? ''));
        }

        $conversation_meta = fcc_ai_to_array($conversation->meta ?? []);
        $source_page_url = trim((string) ($conversation_meta['source_page_url'] ?? ''));
        $normalized_request_origin = self::normalizeWebOrigin($request_origin);
        $normalized_source_origin = self::normalizeWebOrigin($source_page_url);
        if(
            $normalized_request_origin === ''
            || $normalized_source_origin === ''
            || !hash_equals($normalized_source_origin, $normalized_request_origin)
        ) {
            throw new \RuntimeException(fcc_t('Cross-site product-card telemetry origin mismatch.'));
        }
    }

    private static function normalizeWebOrigin(string $url): string {
        $parts = parse_url(trim($url));
        if(!is_array($parts)) {
            return '';
        }

        $scheme = mb_strtolower((string) ($parts['scheme'] ?? ''));
        $host = mb_strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        $port = isset($parts['port']) ? (int) $parts['port'] : null;
        if(!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return '';
        }

        $default_port = $scheme === 'https' ? 443 : 80;
        return $scheme . '://' . $host . ($port && $port !== $default_port ? ':' . $port : '');
    }

    private function getRequestMarketCode(): string {
        /*
         * The widget renders this value from the FCC page configuration. Keep
         * the product/catalog scope tied to that page instead of browser
         * language, which is only a presentation preference.
         */
        $declared_market_value = $_POST['market_code'] ?? '';
        $declared_market_code = is_scalar($declared_market_value)
            ? mb_strtolower(trim((string) $declared_market_value))
            : '';
        if(preg_match('/^[a-z]{2}$/D', $declared_market_code) === 1) {
            return $declared_market_code;
        }

        $trusted_country_code = \Altum\Link::get_trusted_forever_request_country_code();
        $trusted_market_code = mb_strtolower(trim((string) $trusted_country_code));
        if(preg_match('/^[a-z]{2}$/D', $trusted_market_code) === 1) {
            return $trusted_market_code;
        }

        if(\Altum\Helpers\FccProductAdvisorV2::isTrustedLocalEnvironment()) {
            return 'hr';
        }

        return '';
    }
}
