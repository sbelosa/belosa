<?php

/** Offline translations for product education. Product identity and market stay unchanged. */
function fcc_product_education_i18n_languages(): array {
    return ['hr' => 'Hrvatski', 'en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français',
        'es' => 'Español', 'sl' => 'Slovenščina', 'sr' => 'Srpski, latinica',
        'cnr' => 'Crnogorski, latinica', 'sq' => 'Shqip', 'hu' => 'Magyar',
        'ro' => 'Română', 'pl' => 'Polski', 'sv' => 'Svenska'];
}

function fcc_product_education_i18n_locale(?string $language = null): string {
    if($language === null && class_exists('Altum\\Language') && isset(\Altum\Language::$code)) {
        $language = \Altum\Language::$code;
    }
    $language = trim((string) ($language ?? 'hr'));
    if(function_exists('fcc_locale')) {
        $resolved = fcc_locale($language);
        if(isset(fcc_product_education_i18n_languages()[$resolved])) return $resolved;
    }
    $normalized = function_exists('mb_strtolower') ? mb_strtolower($language, 'UTF-8') : strtolower($language);
    $aliases = ['hrvatski' => 'hr', 'croatian' => 'hr', 'english' => 'en', 'deutsch' => 'de',
        'german' => 'de', 'français' => 'fr', 'french' => 'fr', 'español' => 'es', 'spanish' => 'es',
        'slovenščina' => 'sl', 'slovenski' => 'sl', 'slovenian' => 'sl', 'srpski' => 'sr',
        'serbian' => 'sr', 'crnogorski' => 'cnr', 'montenegrin' => 'cnr', 'shqip' => 'sq',
        'albanian' => 'sq', 'magyar' => 'hu', 'hungarian' => 'hu', 'română' => 'ro',
        'romana' => 'ro', 'romanian' => 'ro', 'polski' => 'pl', 'polish' => 'pl',
        'svenska' => 'sv', 'swedish' => 'sv'];
    $code = $aliases[$normalized] ?? explode('-', str_replace('_', '-', $normalized))[0];
    return isset(fcc_product_education_i18n_languages()[$code]) ? $code : 'hr';
}

function fcc_product_education_i18n_directory(): string {
    if(defined('FCC_PRODUCT_EDUCATION_LOCALE_DIR')) return rtrim((string) FCC_PRODUCT_EDUCATION_LOCALE_DIR, '/');
    return dirname(__DIR__) . '/config/fcc_product_education_locales';
}

function fcc_product_education_i18n_catalog(?string $language = null): array {
    static $cache = [];
    $locale = fcc_product_education_i18n_locale($language);
    if(isset($cache[$locale])) return $cache[$locale];
    if($locale === 'hr') return $cache[$locale] = [];
    $file = fcc_product_education_i18n_directory() . '/' . $locale . '.json';
    if(!is_file($file)) return $cache[$locale] = [];
    $catalog = json_decode((string) file_get_contents($file), true);
    if(!is_array($catalog) || ($catalog['schema'] ?? '') !== 'fcc_product_education_locale_v1'
        || ($catalog['locale'] ?? '') !== $locale || ($catalog['source_locale'] ?? '') !== 'hr'
        || !is_array($catalog['translations'] ?? null)) return $cache[$locale] = [];
    return $cache[$locale] = array_filter($catalog['translations'], static fn($value): bool => is_string($value) && trim($value) !== '');
}

/** Exact source hashes avoid collisions between sentences with different meanings. */
function fcc_product_education_t(string $source, ?string $language = null, array $replace = []): string {
    $locale = fcc_product_education_i18n_locale($language);
    $trimmed = trim($source);
    if($locale === 'hr' || $trimmed === '') return $replace ? strtr($source, $replace) : $source;
    $catalog = fcc_product_education_i18n_catalog($locale);
    $translated = $catalog[hash('sha256', $trimmed)] ?? null;
    if(is_string($translated)) {
        $offset = strpos($source, $trimmed);
        $translated = substr($source, 0, $offset) . $translated . substr($source, $offset + strlen($trimmed));
    } else {
        // The legacy dictionary may already cover navigation, but never makes a remote request.
        $translated = function_exists('fcc_t') ? fcc_t($source, [], $locale) : $source;
    }
    return $replace ? strtr($translated, $replace) : $translated;
}

function fcc_product_education_i18n_text_fields(): array {
    return array_fill_keys(['title', 'name', 'topic', 'category', 'summary', 'description', 'intro',
        'text', 'paragraphs', 'points', 'sections', 'takeaways', 'study_questions', 'questions',
        'prompt', 'options', 'explanation', 'answer_explanation', 'feedback', 'learning_intro',
        'practical_learning_intro', 'identity_story', 'distinctive_fact', 'why_this_product',
        'key_factors', 'ingredients', 'how_it_works', 'why_it_matters', 'usage_note', 'usage_guide',
        'steps', 'reading_labels', 'depth', 'identity', 'distinctive', 'audience', 'active_components',
        'context', 'role', 'evidence', 'boundary', 'when_refer', 'safety_notes', 'practical_takeaways',
        'practical_study_questions', 'conversation_example', 'practical_conversation_example',
        'display_title', 'education_title', 'education_context', 'application', 'practical_application',
        'combination_explanation', 'product_roles', 'practical_product_roles', 'learner_takeaway',
        'takeaway', 'critical_notes', 'consultation_note', 'use_logic', 'use_logic_title',
        'combination_heading', 'support_heading', 'rationale', 'next_steps', 'study_checks',
        'applicable_when', 'decision_questions', 'not_this_scenario', 'refer_when', 'evidence_note',
        'evidence_level', 'review_note', 'remaining_question', 'monthly_quantity_note',
        'composition_source_facts', 'composition_source_title', 'required_checks', 'reason',
        'reasons', 'resolution_note', 'suggested_wording', 'possible_use_topic', 'what_is_disputed',
        'suggested_resolution', 'proposed_wording', 'resolution', 'current_wording', 'identity_note',
        'bundle_note', 'goal', 'checks', 'facts', 'author_resolution', 'replacement_note',
        'availability_note', 'problem', 'photo_note', 'ingredient_facts', 'usage_facts',
        'mandatory_safety', 'approved_support_facts', 'catalog_notice', 'usage_texts',
        'declared_usage_text', 'pack_label', 'label', 'message', 'success_message', 'error_message',
        'aliases', 'recommendation_lines', 'display_usage_texts', 'image_alt', 'image_caption', 'age', 'pending',
        'why_relevant', 'proposed_scope'], true);
}

/** Countable UI units are grammar, never a change to the label dose or package calculation. */
function fcc_product_education_i18n_counted($count, string $unit, ?string $language = null): string {
    if(!is_numeric($count)) return '';
    $locale = fcc_product_education_i18n_locale($language);
    $forms = [
        'hr' => ['tablet' => ['tableta', 'tablete', 'tableta'], 'capsule' => ['kapsula', 'kapsule', 'kapsula'],
            'gummy' => ['gumeni bombon', 'gumena bombona', 'gumenih bombona'], 'lozenge' => ['pastila', 'pastile', 'pastila'],
            'sachet' => ['vrećica', 'vrećice', 'vrećica'], 'stick' => ['stick', 'sticka', 'stickova'], 'pack' => ['pakiranje', 'pakiranja', 'pakiranja']],
        'en' => ['tablet' => ['tablet', 'tablets'], 'capsule' => ['capsule', 'capsules'], 'gummy' => ['gummy', 'gummies'],
            'lozenge' => ['lozenge', 'lozenges'], 'sachet' => ['sachet', 'sachets'], 'stick' => ['stick', 'sticks'], 'pack' => ['pack', 'packs']],
        'de' => ['tablet' => ['Tablette', 'Tabletten'], 'capsule' => ['Kapsel', 'Kapseln'], 'gummy' => ['Fruchtgummi', 'Fruchtgummis'],
            'lozenge' => ['Lutschpastille', 'Lutschpastillen'], 'sachet' => ['Beutel', 'Beutel'], 'stick' => ['Stick', 'Sticks'], 'pack' => ['Packung', 'Packungen']],
        'fr' => ['tablet' => ['comprimé', 'comprimés'], 'capsule' => ['gélule', 'gélules'], 'gummy' => ['bonbon gélifié', 'bonbons gélifiés'],
            'lozenge' => ['pastille', 'pastilles'], 'sachet' => ['sachet', 'sachets'], 'stick' => ['stick', 'sticks'], 'pack' => ['boîte', 'boîtes']],
        'es' => ['tablet' => ['comprimido', 'comprimidos'], 'capsule' => ['cápsula', 'cápsulas'], 'gummy' => ['gominola', 'gominolas'],
            'lozenge' => ['pastilla', 'pastillas'], 'sachet' => ['sobre', 'sobres'], 'stick' => ['stick', 'sticks'], 'pack' => ['envase', 'envases']],
        'sl' => ['tablet' => ['tableta', 'tableti', 'tablete', 'tablet'], 'capsule' => ['kapsula', 'kapsuli', 'kapsule', 'kapsul'],
            'gummy' => ['gumijasti bonbon', 'gumijasta bonbona', 'gumijasti bonboni', 'gumijastih bonbonov'],
            'lozenge' => ['pastila', 'pastili', 'pastile', 'pastil'], 'sachet' => ['vrečica', 'vrečici', 'vrečice', 'vrečic'],
            'stick' => ['stick', 'sticka', 'sticki', 'stickov'], 'pack' => ['pakiranje', 'pakiranji', 'pakiranja', 'pakiranj']],
        'sr' => ['tablet' => ['tableta', 'tablete', 'tableta'], 'capsule' => ['kapsula', 'kapsule', 'kapsula'],
            'gummy' => ['gumeni bombon', 'gumena bombona', 'gumenih bombona'], 'lozenge' => ['pastila', 'pastile', 'pastila'],
            'sachet' => ['kesica', 'kesice', 'kesica'], 'stick' => ['stick', 'sticka', 'stickova'], 'pack' => ['pakovanje', 'pakovanja', 'pakovanja']],
        'cnr' => ['tablet' => ['tableta', 'tablete', 'tableta'], 'capsule' => ['kapsula', 'kapsule', 'kapsula'],
            'gummy' => ['gumeni bombon', 'gumena bombona', 'gumenih bombona'], 'lozenge' => ['pastila', 'pastile', 'pastila'],
            'sachet' => ['kesica', 'kesice', 'kesica'], 'stick' => ['stick', 'sticka', 'stickova'], 'pack' => ['pakovanje', 'pakovanja', 'pakovanja']],
        'sq' => ['tablet' => ['tabletë', 'tableta'], 'capsule' => ['kapsulë', 'kapsula'], 'gummy' => ['karamele xhelatinoze', 'karamele xhelatinoze'],
            'lozenge' => ['pastilë', 'pastila'], 'sachet' => ['qese', 'qese'], 'stick' => ['stick', 'stick'], 'pack' => ['paketë', 'paketa']],
        'hu' => ['tablet' => ['tabletta'], 'capsule' => ['kapszula'], 'gummy' => ['gumicukor'], 'lozenge' => ['pasztilla'],
            'sachet' => ['tasak'], 'stick' => ['stick'], 'pack' => ['csomag']],
        'ro' => ['tablet' => ['tabletă', 'tablete'], 'capsule' => ['capsulă', 'capsule'], 'gummy' => ['bomboană gumată', 'bomboane gumate'],
            'lozenge' => ['pastilă', 'pastile'], 'sachet' => ['plic', 'plicuri'], 'stick' => ['stick', 'stickuri'], 'pack' => ['cutie', 'cutii']],
        'pl' => ['tablet' => ['tabletka', 'tabletki', 'tabletek', 'tabletki'], 'capsule' => ['kapsułka', 'kapsułki', 'kapsułek', 'kapsułki'],
            'gummy' => ['żelek', 'żelki', 'żelków', 'żelka'], 'lozenge' => ['pastylka', 'pastylki', 'pastylek', 'pastylki'],
            'sachet' => ['saszetka', 'saszetki', 'saszetek', 'saszetki'], 'stick' => ['stick', 'sticki', 'sticków', 'sticka'],
            'pack' => ['opakowanie', 'opakowania', 'opakowań', 'opakowania']],
        'sv' => ['tablet' => ['tablett', 'tabletter'], 'capsule' => ['kapsel', 'kapslar'], 'gummy' => ['gelébit', 'gelébitar'],
            'lozenge' => ['sugtablett', 'sugtabletter'], 'sachet' => ['påse', 'påsar'], 'stick' => ['stick', 'sticks'], 'pack' => ['förpackning', 'förpackningar']],
    ];
    $value = abs((float) $count);
    $whole = floor($value) === $value;
    $last = (int) $value % 10;
    $lastTwo = (int) $value % 100;
    $index = $value === 1.0 ? 0 : 1;
    if(in_array($locale, ['hr', 'sr', 'cnr'], true)) $index = $whole && $last === 1 && $lastTwo !== 11 ? 0 : ($whole && $last >= 2 && $last <= 4 && ($lastTwo < 12 || $lastTwo > 14) ? 1 : 2);
    elseif($locale === 'sl') $index = !$whole ? 2 : ($lastTwo === 1 ? 0 : ($lastTwo === 2 ? 1 : (in_array($lastTwo, [3, 4], true) ? 2 : 3)));
    elseif($locale === 'pl') $index = !$whole ? 3 : ($value === 1.0 ? 0 : ($last >= 2 && $last <= 4 && ($lastTwo < 12 || $lastTwo > 14) ? 1 : 2));
    elseif($locale === 'hu') $index = 0;
    $label = $forms[$locale][$unit][$index] ?? '';
    if($locale === 'ro' && $whole && $value >= 20 && ($lastTwo === 0 || $lastTwo >= 20)) $label = 'de ' . $label;
    $decimal = $locale === 'en' ? '.' : ',';
    $quantity = rtrim(rtrim(number_format((float) $count, 2, $decimal, ''), '0'), $decimal);
    return trim($quantity . ' ' . $label);
}

/** Structural checks are repeated at release audit, independently of the translation process. */
function fcc_product_education_i18n_translation_issues(array $entry, string $translation, ?string $locale = null): array {
    if(trim($translation) === '') return ['empty_translation'];
    $source = (string) ($entry['text'] ?? '');
    $issues = [];
    $patterns = ['numbers' => '/\d+(?:[.,]\d+)?/u',
        'placeholders' => '/%(?:\d+\$)?[sdif]|\{[A-Za-z_][A-Za-z0-9_.]*\}|:[A-Za-z_][A-Za-z0-9_]*|\$\{[^}]+\}/u',
        'urls' => '~https?://[^\s<>"\']+|[A-Za-z0-9._%+]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}~u',
        'markup' => '/<[^>]+>/u'];
    foreach($patterns as $name => $pattern) {
        preg_match_all($pattern, $source, $original);
        preg_match_all($pattern, $translation, $translated);
        $a = array_count_values($original[0]);
        $b = array_count_values($translated[0]);
        ksort($a);
        ksort($b);
        if($a !== $b) $issues[] = $name;
    }
    foreach((array) ($entry['protected_terms'] ?? []) as $term) if(substr_count($source, $term) !== substr_count($translation, $term)) {
        $issues[] = 'protected_term';
        break;
    }
    if(preg_match('/(?:\s[–—]\s|\n\s*[-–—]\s)/u', $translation)) $issues[] = 'dash_punctuation';
    if(in_array($locale, ['en','de','fr','es','sq','hu','ro','pl','sv'], true) && mb_strlen($source) >= 180 && mb_strlen($translation) >= 180) {
        preg_match_all('/[^\W\d_]+/u', mb_strtolower($source), $originalWords);
        preg_match_all('/[^\W\d_]+/u', mb_strtolower($translation), $translatedWords);
        $a = $originalWords[0];
        $b = $translatedWords[0];
        $functionWords = ['i','u','je','se','za','s','na','od','do','uz','koji','koja','koje','koju','kako','kada','jer','zato',
            'dok','nego','odnosno','prema','također','već','nije','nisu','njegov','njegova','njegove','ovaj','ova','ovo','što','ako','bez'];
        if(min(count($a), count($b)) >= 24 && count(array_intersect($functionWords, array_unique($b))) >= 4) {
            $sourceCounts = array_count_values($a);
            $translatedCounts = array_count_values($b);
            $copied = 0;
            foreach($sourceCounts as $word => $count) $copied += min($count, $translatedCounts[$word] ?? 0);
            // A few cosmetic changes do not turn an otherwise copied Croatian paragraph into a translation.
            if(2 * $copied / (count($a) + count($b)) >= .94) $issues[] = 'wrong_language_unchanged_croatian';
        }
    }
    return $issues;
}

function fcc_product_education_i18n_excluded_fields(): array {
    return array_fill_keys(['id', 'key', 'product_key', 'original_product_key', 'source_key',
        'sku', 'slug', 'route', 'kind', 'combination_kind', 'status', 'priority', 'version', 'schema',
        'source', 'source_type', 'source_ids', 'education_source_ids', 'related_entry_ids',
        'lesson_ids', 'provenance', 'origin', 'product_source', 'original_patterns', 'pattern',
        'original_name', 'current_canonical_name', 'canonical_name', 'original_recommendations',
        'recommended_product_keys', 'required_product_keys', 'replacement_product_key',
        'catalog_scope', 'family_type', 'mode', 'unit', 'portion_unit', 'basis', 'retrieval_mode',
        'review_scope', 'market_code', 'language_code', 'locale', 'language', 'hash',
        'url', 'href', 'src', 'image', 'path', 'accessed_at', 'checked_at', 'reviewed_at', 'updated_at',
        'inventory_reviewed_at', 'source_date', 'source_status', 'availability', 'claim_review_status',
        'safety_review_status', 'review_status', 'identity_status', 'source_freshness_status',
        'declaration_status', 'quantity_calculation_status', 'display_product_keys'], true);
}

/** All dynamic map keys and numeric values stay intact; only declared copy fields are translated. */
function fcc_product_education_i18n_is_text(string $key, string $value, array $parent, bool $inText = false): bool {
    if(isset(fcc_product_education_i18n_excluded_fields()[$key])
        || preg_match('/(?:_id|_ids|_key|_keys|_url|_urls|_version|_at|_hash|_status)$/D', $key)) return false;
    $value = trim($value);
    if($value === '' || preg_match('~^(?:https?://|mailto:|/[^ ]|#|data:|plugin://)~i', $value)
        || preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T].*)?$/D', $value)
        || preg_match('/^[A-Z]?[0-9]+$/D', $value)
        || preg_match('/^[a-z][a-z0-9]*(?:[_-][a-z0-9]+)+$/D', $value)) return false;
    if($key === 'name' && (isset($parent['product_key']) || isset($parent['sku']))) return false;
    if($key === 'role' && in_array($value, ['primary', 'support', 'optional', 'alternative'], true)) return false;
    return $inText || isset(fcc_product_education_i18n_text_fields()[$key]);
}

function fcc_product_education_localize_content(array $content, ?string $language = null): array {
    $locale = fcc_product_education_i18n_locale($language);
    if($locale === 'hr') return $content;
    $walk = static function(array $data, bool $inText = false) use (&$walk, $locale): array {
        foreach($data as $key => $value) {
            $field = (string) $key;
            if(isset(fcc_product_education_i18n_excluded_fields()[$field])
                || preg_match('/(?:_id|_ids|_key|_keys|_url|_urls|_version|_at|_hash|_status)$/D', $field)) continue;
            $nestedText = $inText || isset(fcc_product_education_i18n_text_fields()[$field]);
            if(is_array($value)) $data[$key] = $walk($value, $nestedText);
            elseif(is_string($value) && fcc_product_education_i18n_is_text($field, $value, $data, $inText)) {
                $data[$key] = str_contains($value, '<') ? fcc_product_education_localize_html($value, $locale) : fcc_product_education_t($value, $locale);
            }
        }
        return $data;
    };
    return $walk($content);
}

/** Localize text without reserializing markup or touching identifiers, URLs or data attributes. */
function fcc_product_education_localize_html(string $html, ?string $language = null): string {
    $locale = fcc_product_education_i18n_locale($language);
    if($locale === 'hr' || $html === '') return $html;
    $parts = preg_split('/(<(?:"[^"]*"|\'[^\']*\'|[^\'">])*>)/s', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if(!is_array($parts)) return $html;
    $protected = [];
    foreach($parts as &$part) {
        if($part === '') continue;
        if($part[0] === '<') {
            if(preg_match('~^</?\s*(script|style|textarea|code|pre)\b~i', $part, $match)) {
                $tag = strtolower($match[1]);
                if(preg_match('~^</~', $part)) array_pop($protected);
                elseif(!str_ends_with(trim($part), '/>')) $protected[] = $tag;
                continue;
            }
            if($protected || str_starts_with($part, '<!--')) continue;
            $part = (string) preg_replace_callback('/(\s)(alt|title|aria-label|placeholder)\s*=\s*(["\'])(.*?)\3/is', static function(array $match) use ($locale): string {
                $source = html_entity_decode($match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $translated = fcc_product_education_t($source, $locale);
                return $translated === $source ? $match[0] : $match[1] . $match[2] . '=' . $match[3] . htmlspecialchars($translated, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . $match[3];
            }, $part);
        } elseif(!$protected) {
            $source = html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $translated = fcc_product_education_t($source, $locale);
            if($translated !== $source) $part = htmlspecialchars($translated, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
    }
    unset($part);
    return implode('', $parts);
}

function fcc_product_education_i18n_manifest(array $content, array $ui = [], array $extra = []): array {
    $strings = [];
    $fields = [];
    $occurrences = 0;
    $add = static function(string $source, string $path, string $field) use (&$strings, &$fields, &$occurrences): void {
        $source = trim($source);
        if($source === '') return;
        ++$occurrences;
        $fields[$field] = ($fields[$field] ?? 0) + 1;
        $hash = hash('sha256', $source);
        if(!isset($strings[$hash])) $strings[$hash] = ['text' => $source, 'occurrences' => 0, 'paths' => []];
        ++$strings[$hash]['occurrences'];
        if(count($strings[$hash]['paths']) < 3) $strings[$hash]['paths'][] = $path;
    };
    $walk = static function(array $data, string $path, bool $inText = false, string $copyField = '') use (&$walk, $add): void {
        foreach($data as $key => $value) {
            $field = (string) $key;
            if(isset(fcc_product_education_i18n_excluded_fields()[$field])
                || preg_match('/(?:_id|_ids|_key|_keys|_url|_urls|_version|_at|_hash|_status)$/D', $field)) continue;
            $declared = isset(fcc_product_education_i18n_text_fields()[$field]);
            $nextCopyField = $declared ? $field : $copyField;
            $nextPath = $path . '.' . $field;
            if(is_array($value)) $walk($value, $nextPath, $inText || $declared, $nextCopyField);
            elseif(is_string($value) && fcc_product_education_i18n_is_text($field, $value, $data, $inText)) {
                if(str_contains($value, '<')) {
                    foreach(fcc_product_education_i18n_html_strings($value) as $index => $text) $add($text, $nextPath . '.html.' . $index, $nextCopyField ?: $field);
                } else $add($value, $nextPath, $nextCopyField ?: $field);
            }
        }
    };
    $walk($content, 'content');
    $walk($extra, 'extra');
    foreach($ui as $path => $text) if(is_string($text)) $add($text, 'ui.' . $path, 'ui');
    ksort($strings);
    arsort($fields);
    $characters = array_map(static fn(array $entry): int => function_exists('mb_strlen') ? mb_strlen($entry['text'], 'UTF-8') : strlen($entry['text']), $strings);
    $protected = ['Forever Living Products', 'Forever Card Club', 'FCCA', 'FCC', '4Core', 'CoQ10', 'EPA', 'DHA', 'FOS', 'CFU', 'TRPM8'];
    foreach((array) ($content['products'] ?? []) as $product) if(is_string($product['name'] ?? null) && trim($product['name']) !== '') $protected[] = $product['name'];
    $protected = array_values(array_unique($protected));
    foreach($strings as &$entry) $entry['protected_terms'] = array_values(array_filter($protected, static fn(string $term): bool => str_contains($entry['text'], $term)));
    unset($entry);
    return ['schema' => 'fcc_product_education_source_v1', 'source_locale' => 'hr',
        'source_hash' => hash('sha256', implode("\n", array_keys($strings))),
        'languages' => fcc_product_education_i18n_languages(),
        'summary' => ['unique_strings' => count($strings), 'occurrences' => $occurrences,
            'characters' => array_sum($characters), 'max_characters' => $characters ? max($characters) : 0,
            'products' => count((array) ($content['products'] ?? [])),
            'lessons' => count((array) ($content['lessons'] ?? [])),
            'recommendations' => count((array) ($content['published_recommendation_entries'] ?? [])) + count((array) ($content['educational_entries'] ?? [])),
            'field_occurrences' => $fields], 'strings' => $strings];
}

function fcc_product_education_i18n_html_strings(string $html): array {
    $html = (string) preg_replace('~<(script|style|textarea|code|pre)\b[^>]*>.*?</\1\s*>~is', '', $html);
    $strings = [];
    preg_match_all('/\s(?:alt|title|aria-label|placeholder)\s*=\s*(["\'])(.*?)\1/is', $html, $attributes);
    foreach($attributes[2] ?? [] as $value) if(trim($value) !== '') $strings[] = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    foreach(preg_split('/<[^>]*>/s', $html) ?: [] as $value) if(trim($value) !== '' && preg_match('/\p{L}/u', $value)) $strings[] = html_entity_decode(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return array_values(array_unique($strings));
}

/** Static inventory only: PHP is tokenized, never executed. */
function fcc_product_education_i18n_ui_strings(string $file): array {
    if(!is_file($file)) return [];
    $strings = [];
    $inlineHtml = '';
    foreach(token_get_all((string) file_get_contents($file)) as $index => $token) {
        if(!is_array($token)) continue;
        if($token[0] === T_INLINE_HTML) {
            $inlineHtml .= $token[1];
        } elseif($token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $literal = $token[1];
            $value = $literal[0] === "'" ? str_replace(["\\'", "\\\\"], ["'", "\\"], substr($literal, 1, -1)) : stripcslashes(substr($literal, 1, -1));
            $value = trim($value);
            if(preg_match('/<[a-zA-Z][^>]*>/', $value)) {
                foreach(fcc_product_education_i18n_html_strings($value) as $i => $text) $strings[basename($file) . '.' . $index . '.literal-html.' . $i] = $text;
                continue;
            }
            if($value === '' || !preg_match('/\p{L}/u', $value) || preg_match('~^(?:https?://|/|#|data:)~i', $value)
                || preg_match('/(?:[\\\\<>]|\.php\b|\.json\b|^fcc_|\bfpe-[a-z]|^[a-z0-9]+(?:[_-][a-z0-9]+)+$)/u', $value)) continue;
            if(preg_match('/[čćžšđČĆŽŠĐ ]/u', $value) || preg_match('/^[A-ZČĆŽŠĐ][a-zA-ZčćžšđČĆŽŠĐ]{2,}$/u', $value)) $strings[basename($file) . '.' . $index . '.literal'] = $value;
        }
    }
    foreach(fcc_product_education_i18n_html_strings($inlineHtml) as $i => $text) $strings[basename($file) . '.html.' . $i] = $text;
    preg_match_all('~<script\b[^>]*>(.*?)</script\s*>~is', $inlineHtml, $scripts);
    foreach($scripts[1] ?? [] as $index => $script) {
        preg_match_all('/(["\'])([^"\'\\n]{2,500})\1/u', $script, $literals);
        foreach($literals[2] ?? [] as $i => $text) {
            // A regex containing đ can make adjacent JS quotes look like prose.
            if(preg_match('/(?:\.replace\(|^\)\.)/u', $text)) continue;
            if(preg_match('/[čćžšđČĆŽŠĐ]/u', $text) || preg_match('/^[A-Z][a-z]+(?: [a-zA-Z ]+)+[.!?:]?$/D', $text)) $strings[basename($file) . '.script.' . $index . '.' . $i] = trim($text);
        }
    }
    return $strings;
}
