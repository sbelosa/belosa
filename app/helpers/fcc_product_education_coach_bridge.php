<?php

require_once __DIR__ . '/fcc_product_education_coach.php';
require_once __DIR__ . '/fcc_product_education_sharing.php';

/** Product education is an independent, data backed lane of the internal Coach. */
function fcc_product_education_coach_bridge_reply(string $message, array $context = []): ?array {
    /* Custom code: FC-2026-10-13: Legacy cards serve a narrow educational lookup only. */
    if(!empty($context['mentor_enabled'])) return null;
    if(fcc_product_education_coach_business_request($message) || fcc_product_education_coach_platform_request($message)) return null;
    if(function_exists('fcc_coach_topic_domain') && in_array(fcc_coach_topic_domain($message), ['business','app','support'], true)) return null;
    $query=fcc_product_education_coach_normalize($message);
    if(preg_match('/\b(?:napisi|sastavi|pripremi|slozi|posalji|pitanj\w*|suradni\w*|saradni\w*|sodelav\w*|write|draft|message|questions|schreib\w*|nachricht|ecris|mensaje|sporocil\w*)\b/u',$query)) return null;
    $safetyText=$message;
    if(!empty($context['conversation_id']) && function_exists('fcc_ai_get_conversation_messages')) {
        foreach(fcc_ai_get_conversation_messages((int)$context['conversation_id'],8) as $turn) if(($turn['role']??'')==='user') $safetyText.="\n".(string)$turn['content'];
    }
    if(function_exists('fcc_coach_product_context_from_catalog') && !empty(fcc_coach_product_context_from_catalog([],[],$safetyText,'')['medical_context'])) return null;
    if(preg_match('/dijabet|diabet|bubreg|bubrez|kidney|ledvic|trudn|pregnan|dojenj|terapij|lijekov|lekov|cancer|oncolog|onkolog|dijete|djeca|decu|children|child/iu',$safetyText)) return null;
    /* /Custom code: FC-2026-10-13 */
    if(function_exists('fcc_coach_requested_market')) {
        $market = fcc_coach_requested_market($message);
        if($market !== '' && $market !== 'HR') return null;
    }
    // Retain the existing Coach's response to personal urgent or allergy situations.
    if(preg_match('/bol.{0,15}(?:prsima|chest)|chest\s+pain|krv.{0,20}povra|vomit.{0,20}blood|crna stolica|black\s+stool|gubitak svijesti|190\s*\/\s*125|(?:alergič|alergij|alergic|allergic|allergy).{0,35}(?:ribu|ribe|fish|propolis|pelud|pčel|pcel|bee|pollen)|kemoterap|\bkemo\b|\bchemo\b|chemotherap|transplant|dijaliz|dialysis/iu', $message)) return null;
    $language = (string) ($context['language'] ?? 'hr');
    $selection = fcc_product_education_coach_context($message, $language);
    if(empty($selection['relevant']) && fcc_product_education_coach_bridge_followup($message)) {
        $anchor = '';
        $hasPreviousAssistant = false;
        $conversationId = (int) ($context['conversation_id'] ?? 0);
        if($conversationId > 0 && function_exists('fcc_ai_get_conversation_messages')) {
            foreach(array_reverse(fcc_ai_get_conversation_messages($conversationId, 8)) as $turn) {
                if(($turn['role'] ?? '') !== 'assistant') continue;
                $hasPreviousAssistant = true;
                $meta = (array) ($turn['meta'] ?? []);
                if(($meta['product_education']['intent'] ?? '') === 'product') $anchor = implode(' ', (array) ($meta['product_education']['product_keys'] ?? []));
                elseif(!empty($meta['product_education']['recommendation_ids'][0])) $anchor = (string) $meta['product_education']['recommendation_ids'][0];
                elseif(!empty($meta['product_education']['product_keys'][0])) $anchor = (string) $meta['product_education']['product_keys'][0];
                // A new business answer ends the product thread.
                break;
            }
        }
        if($anchor === '' && !$hasPreviousAssistant) {
            $page = (array) ($context['page'] ?? []);
            if(str_contains((string) ($page['url'] ?? ''), '/fcc-product-education')) {
                parse_str((string) parse_url($page['url'], PHP_URL_QUERY), $query);
                $candidate = $query['product'] ?? $query['entry'] ?? '';
                if(is_string($candidate) && preg_match('/^[a-zA-Z0-9_-]{1,120}$/D', $candidate)) $anchor = $candidate;
            }
        }
        if($anchor !== '') $selection = fcc_product_education_coach_context($anchor, $language);
    }
    if(empty($selection['relevant'])) return null;

    $t = static fn(string $text): string => function_exists('fcc_product_education_t')
        ? fcc_product_education_t($text, $language) : $text;
    $paragraphs = [];
    $recommendations = (array) ($selection['recommendations'] ?? []);
    $products = (array) ($selection['products'] ?? []);
    if(($selection['intent'] ?? '') === 'product') {
        $direct = (array) ($selection['direct_product_keys'] ?? []);
        $products = array_values(array_filter($products, static fn(array $product): bool => in_array($product['product_key'] ?? '', $direct, true)));
    } elseif($recommendations) {
        $keys = array_column((array) ($recommendations[0]['display_products'] ?? $recommendations[0]['products'] ?? []), 'product_key');
        $products = array_values(array_filter($products, static fn(array $product): bool => in_array($product['product_key'] ?? '', $keys, true)));
    }
    $isUsageQuestion = fcc_product_education_coach_is_usage_question($message);
    if($recommendations && ($selection['intent'] ?? '') !== 'product') {
        // One clearly selected case at a time prevents mixing alternative combinations.
        $recommendation = $recommendations[0];
        if(($selection['intent'] ?? '') === 'combination') {
            $paragraphs[] = implode(' + ', array_values(array_filter(array_column($products, 'name'))));
            $paragraphs[] = $t('Zašto ova kombinacija');
        }
        $paragraphs[] = (string) ($recommendation['display_title'] ?? $recommendation['title'] ?? '');
        $paragraphs[] = (string) ($recommendation['practical_application'] ?? $recommendation['context'] ?? '');
        $paragraphs[] = (string) ($recommendation['combination_explanation'] ?? $recommendation['rationale'] ?? '');
        foreach((array) ($recommendation['practical_product_roles'] ?? []) as $key => $role) {
            $product = null;
            foreach($products as $candidate) if(($candidate['product_key'] ?? '') === $key) $product = $candidate;
            $paragraphs[] = ($product ? $product['name'] . ': ' : '') . $role;
        }
        foreach((array) ($recommendation['use_logic'] ?? []) as $step) $paragraphs[] = (string) $step;
    } else {
        foreach($products as $product) {
            $depth = (array) ($product['learning_depth'] ?? []);
            $paragraphs[] = (string) ($product['name'] ?? '');
            $paragraphs[] = (string) ($product['practical_learning_intro'] ?? $depth['identity_story'] ?? $product['learning_intro'] ?? '');
            if(!empty($depth['distinctive_fact'])) $paragraphs[] = $depth['distinctive_fact'];
            foreach(array_slice((array) ($depth['ingredients'] ?? []), 0, 2) as $ingredient) {
                $paragraphs[] = ($ingredient['name'] ?? '') . ': ' . ($ingredient['how_it_works'] ?? '') . ' ' . ($ingredient['why_it_matters'] ?? '');
            }
            foreach((array) ($depth['usage_guide']['steps'] ?? []) as $step) $paragraphs[] = (string) $step;
        }
        foreach(array_slice($recommendations, 0, 3) as $recommendation) {
            $paragraphs[] = (string) ($recommendation['display_title'] ?? '') . ': ' . (string) ($recommendation['practical_application'] ?? '');
        }
    }
    $usageParagraphs = [];
    if($isUsageQuestion) foreach($products as $product) {
        foreach((array) ($product['declared_directions'] ?? []) as $direction) $usageParagraphs[] = (string) $product['name'] . ': ' . $direction;
        if(!empty($product['learning_depth']['usage_note'])) $usageParagraphs[] = (string) $product['learning_depth']['usage_note'];
    }
    $paragraphs = array_merge($paragraphs, $usageParagraphs);
    $notes = in_array($selection['intent'] ?? '', ['problem', 'combination'], true) ? (array) ($recommendations[0]['critical_notes'] ?? []) : [];
    foreach($notes as $note) $paragraphs[] = (string) $note;
    $paragraphs[] = $t('Za dodatna pitanja obrati se svojem sponzoru, timskoj grupi ili službenom liječniku savjetniku u Foreveru.');
    $paragraphs = array_values(array_unique(array_filter(array_map('trim', $paragraphs))));
    $guidance = implode("\n\n", $paragraphs);
    $customerMessage = fcc_product_education_coach_customer_text($selection);
    if($usageParagraphs) $customerMessage .= "\n\n" . implode("\n\n", array_values(array_unique($usageParagraphs)));
    $customerMessage = fcc_product_education_coach_customer_clean($customerMessage);
    $userId = (int) ($context['user_id'] ?? 0);
    if($userId > 0 && function_exists('fcc_product_education_share_targets')) {
        try {
            $currentProducts = array_values(array_filter($products, static fn(array $product): bool => ($product['education_visibility'] ?? '') !== 'archive'
                && ($product['catalog_scope'] ?? '') !== 'legacy_only'
                && ($product['availability_status'] ?? '') !== 'discontinued'
                && empty($product['package_plans']['replacement_product_key'])));
            $publicProducts = fcc_product_education_share_targets($currentProducts, $userId);
            $publicUrls = [];
            foreach($publicProducts as $product) {
                $url = (string) ($product['share_information_url'] ?? '');
                if($url !== '' && !str_contains($url, 'fcc-product-education')) $publicUrls[] = $url;
            }
            if($publicUrls) $customerMessage .= "\n\n" . implode("\n", array_unique($publicUrls));
        } catch(\Throwable $exception) {
            error_log('FCC Coach product sharing unavailable: ' . get_class($exception));
        }
    }
    $links = array_values(array_filter((array) ($selection['internal_links'] ?? []), static fn($link): bool => is_array($link) && !empty($link['title']) && !empty($link['url'])));
    foreach($links as &$link) {
        $path = ltrim((string) $link['url'], '/');
        if(function_exists('url')) $link['url'] = url($path);
        elseif(defined('SITE_URL')) $link['url'] = rtrim(SITE_URL, '/') . '/' . $path;
    }
    unset($link);
    return [
        'content' => $guidance,
        'guidance' => $guidance,
        'recipient_message' => '',
        'language' => $language,
        'reply_mode' => 'product_education',
        'force_local_preview' => true,
        'next_step' => '',
        'knowledge_suggestions' => array_slice($links, 0, 3),
        'product_education_share' => null,
        'product_education' => [
            'version' => (string) ($selection['version'] ?? ''),
            'intent' => (string) ($selection['intent'] ?? ''),
            'product_keys' => array_values(array_filter(array_column($products, 'product_key'), 'is_string')),
            'recommendation_ids' => array_values(array_filter(array_column($recommendations, 'id'), 'is_string')),
        ],
    ];
}

/** Followups may retain a product subject, never an unrelated business subject. */
function fcc_product_education_coach_bridge_followup(string $message): bool {
    if(fcc_product_education_coach_business_request($message) || fcc_product_education_coach_platform_request($message)
        || preg_match('/tjedn|plan rasta/iu', $message)) return false;
    return fcc_product_education_coach_is_followup($message)
        || fcc_product_education_coach_facts_request($message)
        || (bool) preg_match('/sojev|strain/iu', $message);
}
