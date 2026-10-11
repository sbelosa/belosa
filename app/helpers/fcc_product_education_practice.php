<?php

/** Project practical learning without changing historical records or manufacturer instructions. */
function fcc_product_education_practical_entry(array $entry, array $practice): array {
    if(trim((string) ($practice['application'] ?? '')) === '') return $entry;
    foreach([
        'application' => 'practical_application',
        'combination_explanation' => 'combination_explanation',
        'product_roles' => 'practical_product_roles',
        'takeaway' => 'learner_takeaway',
        'conversation_example' => 'conversation_example',
        'critical_notes' => 'critical_notes',
        'combination_kind' => 'combination_kind',
        'use_logic' => 'use_logic',
        'use_logic_title' => 'use_logic_title',
        'combination_heading' => 'combination_heading',
        'support_heading' => 'support_heading',
    ] as $from => $to) {
        if(array_key_exists($from, $practice)) $entry[$to] = $practice[$from];
    }
    $entry['critical_notes'] = (array) ($entry['critical_notes'] ?? []);
    $entry['consultation_note'] = 'Za dodatna pitanja o ovoj kombinaciji obrati se svojem sponzoru, timskoj grupi ili službenom liječniku savjetniku u Foreveru.';
    $sourceField = ($entry['source_type'] ?? '') === 'author_material' ? 'education_source_ids' : 'source_ids';
    $entry[$sourceField] = array_values(array_unique(array_merge((array) ($entry[$sourceField] ?? []), (array) ($practice['source_ids'] ?? []))));
    if(!empty($practice['title'])) {
        $entry['display_title'] = $practice['title'];
        if(($entry['source_type'] ?? '') === 'author_material') $entry['education_title'] = $practice['title'];
    }
    if(($entry['source_type'] ?? '') === 'editorial_education') {
        $entry['previous_educational_context'] = $entry['context'] ?? '';
        $entry['context'] = $entry['practical_application'];
        $entry['rationale'] = $entry['combination_explanation'] ?? '';
    }
    return $entry;
}

/** Link the exact displayed formula to ingredient learning without changing recommendations. */
function fcc_product_education_depth_entry(array $entry, array $profiles): array {
    $entry['ingredient_learning'] = [];
    foreach((array) ($entry['display_products'] ?? $entry['products'] ?? []) as $reference) {
        $key = $reference['product_key'] ?? null;
        if(!is_string($key) || !isset($profiles[$key])) continue;
        $ingredients = array_values(array_filter((array) ($profiles[$key]['ingredients'] ?? []),
            static fn($ingredient): bool => is_array($ingredient) && !empty($ingredient['name']) && !empty($ingredient['how_it_works'])));
        if($ingredients) $entry['ingredient_learning'][$key] = array_slice($ingredients, 0, 2);
    }
    return $entry;
}

/** A product card teaches the product role and links back to its complete combinations. */
function fcc_product_education_practical_product(array $product): array {
    $key = (string) ($product['product_key'] ?? '');
    $cases = array_values(array_filter(array_merge(
        (array) ($product['author_recommendations'] ?? []),
        (array) ($product['education_applications'] ?? [])
    ), static fn(array $entry): bool => !empty($entry['practical_application'])));
    if(!$cases) {
        // A standalone use case remains useful for products with no published combination.
        if(!empty($product['use_cases'])) {
            $case = $product['use_cases'][0];
            $product['practical_learning_intro'] = trim((string) ($case['context'] ?? '') . ' ' . (string) ($case['role'] ?? ''));
            $product['practical_conversation_example'] = (string) ($product['conversation_example'] ?? '');
        }
        return $product;
    }
    $roles = [];
    $titles = [];
    foreach($cases as $case) {
        $role = (string) ($case['practical_product_roles'][$key] ?? '');
        if($role !== '') $roles[$role] = $role;
        $titles[(string) ($case['display_title'] ?? $case['title'] ?? '')] = true;
    }
    $firstRole = (string) (array_values($roles)[0] ?? '');
    $topics = array_slice(array_filter(array_keys($titles)), 0, 3);
    $product['practical_learning_intro'] = trim($firstRole . ($topics ? ' Povezane primjene: ' . implode('; ', $topics) . '.' : ''));
    $product['practical_conversation_example'] = (string) ($cases[0]['conversation_example'] ?? '');
    $product['practical_takeaways'] = array_slice(array_values($roles), 0, 2);
    $product['practical_study_questions'] = [];
    $intros = [
        'forever-aloe-vera-gel' => 'Forever Aloe Vera Gel je napitak s unutarnjim gelom aloe. Preporučuje se uz Active Pro B kod gastritisa, refluksa i nadutosti, a s ImmuBlendom i Absorbent C kao prehrambena dopuna za imunitet. Svaka kombinacija u nastavku objašnjava primjenu i zasebnu ulogu proizvoda.',
        'forever-active-pro-b' => 'Forever Active Pro B donosi probiotičke kulture i prebiotičke sastojke. Preporučuje se uz Aloe Vera Gel kod probavnih tegoba, a s Fiber Fusionom kada je potreban veći unos vlakana. Pro B donosi bakterijske kulture, Fiber vlakna, a Gel unutarnji gel aloe.',
        'forever-fiber-fusion' => 'Forever Fiber Fusion dopunjuje dnevni unos vlakana gotovim žele stickom. U probavnim kombinacijama ima ulogu izvora vlakana, dok Active Pro B donosi bakterijske kulture, a Aloe Vera Gel aloe napitak. Ovaj aktualni proizvod zamjenjuje povijesni Forever Fiber u preporukama.',
    ];
    if(isset($intros[$key])) $product['practical_learning_intro'] = $intros[$key];
    return $product;
}
