<?php

require_once __DIR__ . '/fcc_product_education_practice.php';

/** Pure content, progress and quiz functions for FCC product education. */
function fcc_product_education_content(): array {
    static $content = null;

    if($content === null) {
        $content = require __DIR__ . '/../config/fcc_product_education.php';
        // Extend the catalogue while keeping the original lesson identities and learning profiles.
        $productFile = __DIR__ . '/../config/fcc_product_education_products.php';
        if(is_file($productFile)) {
            $master = require $productFile;
            $originalProducts = array_column($content['products'], null, 'product_key');
            $masterProducts = [];
            foreach((array) ($master['products'] ?? []) as $product) {
                if(!is_array($product) || !is_string($product['product_key'] ?? null)) continue;
                $masterProducts[$product['product_key']] = $product;
            }
            foreach($content['products'] as &$product) {
                $metadata = $masterProducts[$product['product_key']] ?? [];
                $product = array_replace($metadata, $product);
                foreach(['category', 'catalog_scope', 'route', 'family_type', 'identity_note', 'provenance', 'inventory_reviewed_at'] as $field) {
                    if(array_key_exists($field, $metadata)) $product[$field] = $metadata[$field];
                }
            }
            unset($product);
            foreach($masterProducts as $key => $product) {
                if(!isset($originalProducts[$key])) $content['products'][] = $product;
            }
            $content['product_source'] = (array) ($master['source'] ?? []);
            $content['sources'] = array_replace($content['sources'], (array) ($master['sources'] ?? []));
        }
        $profileFile = __DIR__ . '/../config/fcc_product_education_profiles.php';
        if(is_file($profileFile)) {
            $profiles = require $profileFile;
            foreach($content['products'] as &$product) {
                $profile = $profiles['products'][$product['product_key']] ?? null;
                if(is_array($profile)) {
                    // Profiles enrich learning without replacing identity, current facts or access.
                    foreach(['learning_intro', 'active_components', 'use_cases', 'conversation_example', 'takeaways', 'source_ids'] as $field) {
                        if(array_key_exists($field, $profile)) $product[$field] = $profile[$field];
                    }
                }
            }
            unset($product);
            foreach(['needs', 'routines'] as $section) {
                if(!empty($profiles[$section]) && is_array($profiles[$section])) $content[$section] = $profiles[$section];
            }
            $content['sources'] = array_replace($content['sources'], (array) ($profiles['sources'] ?? []));
        }
        // Dated product updates enrich education without altering the historical matrix.
        $updateFile = __DIR__ . '/../config/fcc_product_education_updates.php';
        if(is_file($updateFile)) {
            $updates = require $updateFile;
            foreach($content['products'] as &$product) {
                $profile = $updates['products'][$product['product_key']] ?? null;
                if(is_array($profile)) $product = array_replace($product, $profile);
            }
            unset($product);
            $content['sources'] = array_replace($content['sources'], (array) ($updates['sources'] ?? []));
            $content['product_updates_at'] = $updates['updated_at'] ?? '';
        }
        $content['editorial_review'] = ['issues' => [], 'proposals' => [], 'missing_topics' => [], 'sources' => []];
        $reviewFile = __DIR__ . '/../config/fcc_product_education_review.php';
        if(is_file($reviewFile)) {
            $content['editorial_review'] = array_replace($content['editorial_review'], (array) (require $reviewFile));
            $content['sources'] = array_replace($content['sources'], (array) ($content['editorial_review']['sources'] ?? []));
        }
        $applicationFile = __DIR__ . '/../config/fcc_product_education_applications.php';
        if(is_file($applicationFile)) {
            $applications = require $applicationFile;
            $content['editorial_review']['proposals'] = array_merge($content['editorial_review']['proposals'], (array) ($applications['proposals'] ?? []));
            $content['sources'] = array_replace($content['sources'], (array) ($applications['sources'] ?? []));
            $content['application_review_gaps'] = (array) ($applications['blocked'] ?? []);
        }
        // Load reviewed scenario explanations and any separately authored new learning cases.
        $scenarioUpdates = ['entries' => [], 'issue_resolutions' => [], 'sources' => [], 'product_learning' => [], 'proposals' => []];
        foreach(['fcc_product_education_scenarios.php', 'fcc_product_education_safety_updates.php'] as $scenarioName) {
            $scenarioFile = __DIR__ . '/../config/' . $scenarioName;
            if(!is_file($scenarioFile)) continue;
            $scenarioData = (array) (require $scenarioFile);
            foreach(['entries', 'issue_resolutions', 'sources', 'product_learning'] as $field) {
                $scenarioUpdates[$field] = array_replace($scenarioUpdates[$field], (array) ($scenarioData[$field] ?? []));
            }
            $scenarioUpdates['proposals'] = array_merge($scenarioUpdates['proposals'], (array) ($scenarioData['proposals'] ?? []));
        }
        $content['sources'] = array_replace($content['sources'], $scenarioUpdates['sources']);
        $content['editorial_review']['proposals'] = array_merge($content['editorial_review']['proposals'], $scenarioUpdates['proposals']);
        $publicationFile = __DIR__ . '/../config/fcc_product_education_publication.php';
        $publication = is_file($publicationFile) ? (array) (require $publicationFile) : [];
        $authorFile = __DIR__ . '/../config/fcc_product_education_author_updates.php';
        $authorUpdates = is_file($authorFile) ? (array) (require $authorFile) : [];
        $practicalScenariosFile = __DIR__ . '/../config/fcc_product_education_practical_scenarios.php';
        $practicalScenarios = is_file($practicalScenariosFile) ? (array) (require $practicalScenariosFile) : [];
        $practicalApplicationsFile = __DIR__ . '/../config/fcc_product_education_practical_applications.php';
        $practicalApplications = is_file($practicalApplicationsFile) ? (array) (require $practicalApplicationsFile) : [];
        $partnerLearningFile = __DIR__ . '/../config/fcc_product_education_partner_learning.php';
        $partnerLearning = is_file($partnerLearningFile) ? (array) (require $partnerLearningFile) : [];
        $productDepth = ['products' => [], 'sources' => []];
        $mastery = ['entries' => [], 'sources' => []];
        foreach(['nutrition', 'care', 'everyday'] as $depthGroup) {
            $depthFile = __DIR__ . '/../config/fcc_product_education_depth_' . $depthGroup . '.php';
            if(!is_file($depthFile)) continue;
            $depthData = (array) (require $depthFile);
            foreach(['products', 'sources'] as $field) {
                $productDepth[$field] = array_replace($productDepth[$field], (array) ($depthData[$field] ?? []));
            }
            $content['product_depth_updated_at'] = $depthData['updated_at'] ?? '';
            $masteryFile = __DIR__ . '/../config/fcc_product_education_mastery_' . $depthGroup . '.php';
            if(is_file($masteryFile)) {
                $masteryData = (array) (require $masteryFile);
                foreach(['entries', 'sources'] as $field) $mastery[$field] = array_replace($mastery[$field], (array) ($masteryData[$field] ?? []));
            }
        }
        $content['sources'] = array_replace($content['sources'], (array) ($practicalScenarios['sources'] ?? []), (array) ($practicalApplications['sources'] ?? []), (array) ($partnerLearning['sources'] ?? []));
        $content['sources'] = array_replace($content['sources'], $productDepth['sources']);
        $content['sources'] = array_replace($content['sources'], $mastery['sources']);
        $content['publication_decisions'] = $publication;
        $content['sources'] = array_replace($content['sources'], (array) ($authorUpdates['sources'] ?? []));
        $scenarioUpdates['issue_resolutions'] = array_replace($scenarioUpdates['issue_resolutions'], (array) ($authorUpdates['issue_resolutions'] ?? []));
        $productIndex = array_column($content['products'], null, 'product_key');
        foreach((array) ($authorUpdates['products'] ?? []) as $key => $overrides) {
            if(!is_array($overrides)) continue;
            $productIndex[$key] = array_replace($productIndex[$key] ?? ['product_key' => $key], $overrides);
        }
        foreach((array) ($publication['product_overrides'] ?? []) as $key => $overrides) {
            if(isset($productIndex[$key]) && is_array($overrides)) $productIndex[$key] = array_replace($productIndex[$key], $overrides);
        }
        foreach($productIndex as &$product) {
            if(in_array(($product['catalog_scope'] ?? ''), ['legacy_only', 'source_only'], true)) $product['education_visibility'] = 'archive';
        }
        unset($product);
        $content['products'] = array_values($productIndex);
        foreach($content['editorial_review']['proposals'] as &$proposal) {
            $proposal = array_replace($proposal, (array) ($authorUpdates['proposal_updates'][$proposal['id']] ?? []));
            $practicalProposal = (array) ($practicalApplications['entries'][$proposal['id']] ?? []);
            if(!empty($practicalProposal['application'])) {
                $proposal['previous_educational_wording'] = $proposal['suggested_wording'] ?? '';
                $proposal['suggested_wording'] = $practicalProposal['application'];
                $proposal['rationale'] = $practicalProposal['combination_explanation'] ?? '';
                if(!empty($practicalProposal['title'])) $proposal['title'] = $practicalProposal['title'];
                $proposal['source_ids'] = array_values(array_unique(array_merge((array) ($proposal['source_ids'] ?? []), (array) ($practicalProposal['source_ids'] ?? []))));
            }
            if(!empty($publication['approval']['proposals'])) $proposal['author_approved_at'] = $publication['approval']['approved_at'];
        }
        unset($proposal);
        $extendedFile = __DIR__ . '/../config/fcc_product_education_extended_lessons.php';
        $extended = is_file($extendedFile) ? (array) (require $extendedFile) : [];
        $content['lessons'] = array_merge($content['lessons'], (array) ($extended['lessons'] ?? []));
        $content['sources'] = array_replace($content['sources'], (array) ($extended['sources'] ?? []));
        $content['editorial_review']['completed_topics'] = [];
        $completedTopicIds = (array) ($extended['completed_topic_ids'] ?? []);
        $remainingTopics = [];
        foreach($content['editorial_review']['missing_topics'] as $topic) {
            if(in_array($topic['id'], $completedTopicIds, true)) {
                $topic['status'] = 'lesson_published';
                foreach($content['lessons'] as $lesson) {
                    if(($lesson['topic_id'] ?? '') === $topic['id']) $topic['lesson_id'] = $lesson['id'];
                }
                $content['editorial_review']['completed_topics'][] = $topic;
            } else $remainingTopics[] = $topic;
        }
        $content['editorial_review']['missing_topics'] = $remainingTopics;
        $content['educational_entries'] = [];
        $productNames = array_column($content['products'], 'name', 'product_key');
        foreach($content['editorial_review']['proposals'] as $proposal) {
            if(($proposal['status'] ?? '') !== 'education_ready' || ($proposal['publication_type'] ?? '') !== 'educational_case') continue;
            $primaryKeys = (array) ($proposal['primary_product_keys'] ?? [$proposal['primary_product_key'] ?? '']);
            $references = [];
            foreach(array_unique(array_merge($primaryKeys, (array) ($proposal['support_product_keys'] ?? []))) as $key) {
                if(!isset($productNames[$key])) continue;
                $references[] = ['name' => $productNames[$key], 'product_key' => $key, 'role' => in_array($key, $primaryKeys, true) ? 'primary' : 'support'];
            }
            $content['educational_entries'][] = fcc_product_education_practical_entry([
                'id' => $proposal['id'], 'title' => $proposal['title'], 'display_title' => $proposal['title'],
                'display_mode' => 'educational_case', 'source_type' => 'editorial_education',
                'published_at' => $proposal['published_at'] ?? '', 'products' => $references,
                'context' => $proposal['suggested_wording'] ?? '', 'rationale' => $proposal['rationale'] ?? '',
                'study_checks' => (array) ($proposal['required_checks'] ?? []),
                'evidence_level' => $proposal['evidence_level'] ?? '', 'source_ids' => (array) ($proposal['source_ids'] ?? []),
                'related_entry_ids' => (array) ($proposal['related_entry_ids'] ?? []),
            ], (array) ($practicalApplications['entries'][$proposal['id']] ?? []));
        }
        $content['recommendation_entries'] = [];
        $content['recommendation_source'] = [];
        $recommendationFile = __DIR__ . '/../config/fcc_product_education_recommendations.php';
        if(is_file($recommendationFile)) {
            $recommendations = require $recommendationFile;
            $content['recommendation_source'] = (array) ($recommendations['source'] ?? []);
            $displayTitles = [
                'digestive_routine_support' => 'Gastritis, nadutost i probavna rutina',
                'acid_reflux_support' => 'Žgaravica i refluks',
                'immunity_support_routine' => 'Imunitet i svakodnevna nutritivna rutina',
            ];
            $reviewGroups = [
                [
                    'ids' => ['thrombosis_support'],
                    'note' => 'Izvorni zapis sadrži dozu uz trombozu. Taj dio gradiva sačuvan je za urednički i stručni pregled.',
                ],
                [
                    'ids' => ['oncology_support_routine', 'post_chemotherapy_recovery_support', 'therapy_digestive_support'],
                    'note' => 'Izvorni zapis uključuje probiotik uz osjetljiv medicinski kontekst. Taj dio gradiva traži stručnu provjeru prikladnosti.',
                ],
                [
                    'ids' => ['allergy_low_immunity_support', 'seasonal_allergy_support', 'skin_allergy_arctic_support', 'allergy_fatigue_support', 'skin_allergy_inside_out_support', 'allergic_cough_support'],
                    'note' => 'Izvorni zapis uključuje pčelinje proizvode u alergijskom kontekstu. Taj dio gradiva traži stručni pregled prije primjene.',
                ],
                [
                    'ids' => ['children_daily_vitamins_support', 'child_immunity_support', 'child_digestive_support', 'child_low_appetite_support', 'child_low_energy_support', 'child_cold_support'],
                    'note' => 'Izvorni zapis navodi količine bez određene dobi djeteta. Taj dio gradiva traži stručnu provjeru za dječju primjenu.',
                ],
                [
                    'ids' => ['postpartum_weight_support'],
                    'note' => 'Izvorni zapis uključuje oralnu aloe nakon poroda bez podatka o dojenju. Taj dio gradiva traži stručnu provjeru.',
                ],
            ];
            $reviewNotes = [];
            foreach($reviewGroups as $group) {
                foreach($group['ids'] as $id) $reviewNotes[$id] = $group['note'];
            }
            $entryIssues = [];
            foreach((array) ($content['editorial_review']['issues'] ?? []) as $issue) {
                foreach((array) ($issue['entry_ids'] ?? []) as $id) {
                    $entryIssues[$id][] = $issue;
                }
            }
            foreach((array) ($recommendations['entries'] ?? []) as $entry) {
                if(!is_array($entry) || ($entry['scope'] ?? '') !== 'human'
                    || ($entry['source_type'] ?? '') !== 'author_material'
                    || ($entry['editorial_status'] ?? '') !== 'source_transcription') continue;
                $entry['display_title'] = $displayTitles[$entry['id'] ?? ''] ?? $entry['title'];
                $entry['display_mode'] = isset($reviewNotes[$entry['id']]) ? 'archive_review' : 'source_material';
                if(isset($reviewNotes[$entry['id']])) $entry['review_note'] = $reviewNotes[$entry['id']];
                foreach($entryIssues[$entry['id']] ?? [] as $issue) {
                    $mode = $issue['display_mode'] ?? 'source_note';
                    if($mode === 'archive_review') $entry['display_mode'] = 'archive_review';
                    elseif($entry['display_mode'] !== 'archive_review') $entry['display_mode'] = 'source_note';
                    $note = trim((string) ($issue['what_is_disputed'] ?? ''));
                    if($note !== '') $entry['review_note'] = trim(($entry['review_note'] ?? '') . ' ' . $note);
                    $entry['review_issue_ids'][] = $issue['id'];
                }
                $content['recommendation_entries'][] = $entry;
            }
        }
        // A separate learning layer preserves the exact source transcription and graph.
        foreach($content['recommendation_entries'] as &$entry) {
            $originalRefinement = (array) ($scenarioUpdates['entries'][$entry['id']] ?? []);
            $authorRefinement = (array) ($authorUpdates['entry_learning_updates'][$entry['id']] ?? []);
            $refinement = array_replace($originalRefinement, $authorRefinement);
            $refinement['product_roles'] = array_replace((array) ($originalRefinement['product_roles'] ?? []), (array) ($authorRefinement['product_roles'] ?? []));
            $refinement['source_ids'] = array_values(array_unique(array_merge((array) ($originalRefinement['source_ids'] ?? []), (array) ($authorRefinement['source_ids'] ?? []))));
            if(!is_array($refinement)) continue;
            foreach(['education_title', 'education_context', 'product_roles', 'applicable_when', 'decision_questions', 'next_steps', 'not_this_scenario', 'refer_when', 'evidence_note', 'education_status'] as $field) {
                if(array_key_exists($field, $refinement)) $entry[$field] = $refinement[$field];
            }
            $entry['education_source_ids'] = (array) ($refinement['source_ids'] ?? []);
            if(!empty($entry['education_title'])) $entry['display_title'] = $entry['education_title'];
        }
        unset($entry);
        // Current display links never rewrite the archived source product references.
        foreach($content['recommendation_entries'] as &$entry) {
            $entry['display_products'] = [];
            foreach((array) ($entry['products'] ?? []) as $reference) {
                $update = $authorUpdates['reference_updates'][$reference['name'] ?? '']
                    ?? $authorUpdates['reference_updates'][$reference['product_key'] ?? ''] ?? null;
                if(($reference['product_key'] ?? '') === 'forever-fiber') {
                    $update = ['product_key' => 'forever-fiber-fusion', 'name' => 'Forever Fiber Fusion',
                        'resolution_note' => 'Aktualni Fiber Fusion zamjenjuje povijesni Forever Fiber prema potvrdi u edukacijskom gradivu.'];
                }
                if(is_array($update) && isset($productIndex[$update['product_key'] ?? ''])) {
                    $reference['original_name'] = $reference['name'];
                    $reference['original_product_key'] = $reference['product_key'] ?? null;
                    $reference['product_key'] = $update['product_key'];
                    $reference['name'] = $update['name'] ?? $productIndex[$update['product_key']]['name'];
                    $reference['resolution_note'] = $update['resolution_note'] ?? '';
                }
                $entry['display_products'][] = $reference;
            }
        }
        unset($entry);
        $withdrawnEntryIds = [];
        foreach($content['editorial_review']['issues'] as $issue) {
            if(in_array($issue['id'], (array) ($publication['withdrawn_issue_ids'] ?? []), true)) {
                $withdrawnEntryIds = array_merge($withdrawnEntryIds, (array) ($issue['entry_ids'] ?? []));
            }
        }
        $withdrawnEntryIds = array_values(array_unique($withdrawnEntryIds));
        foreach($content['recommendation_entries'] as &$entry) {
            $entry['publication_status'] = in_array($entry['id'], $withdrawnEntryIds, true) ? 'withdrawn_by_author' : 'published';
            if($entry['publication_status'] === 'published') {
                $entry = fcc_product_education_practical_entry($entry, (array) ($practicalScenarios['entries'][$entry['id']] ?? []));
            }
        }
        unset($entry);
        foreach(['recommendation_entries', 'educational_entries'] as $entrySection) {
            foreach($content[$entrySection] as &$entry) {
                if(($entry['publication_status'] ?? '') === 'withdrawn_by_author') continue;
                if(isset($mastery['entries'][$entry['id']])) {
                    $masteryPractice = (array) $mastery['entries'][$entry['id']];
                    unset($masteryPractice['critical_notes']);
                    $entry = fcc_product_education_practical_entry($entry, $masteryPractice);
                    $entry['individual_editorial_review'] = true;
                }
                $entry = fcc_product_education_depth_entry($entry, $productDepth['products']);
            }
            unset($entry);
        }
        $content['published_recommendation_entries'] = array_values(array_filter($content['recommendation_entries'], static fn(array $entry): bool => $entry['publication_status'] === 'published'));
        $currentEntries = array_column($content['recommendation_entries'], null, 'id');
        foreach($content['editorial_review']['issues'] as &$issue) {
            if(in_array($issue['id'], (array) ($publication['withdrawn_issue_ids'] ?? []), true)) {
                $issue['previous_status'] = $issue['status'] ?? '';
                $issue['status'] = 'withdrawn_by_author';
                $issue['resolution'] = 'Autor je zatražio da se ova stavka i pripadajući izvorni primjeri ne objave u edukaciji.';
                $issue['remaining_question'] = '';
                $issue['current_wording'] = '';
                continue;
            }
            $resolution = $scenarioUpdates['issue_resolutions'][$issue['id']] ?? null;
            if(!is_array($resolution)) continue;
            $issue['previous_status'] = $issue['status'] ?? '';
            $issue['status'] = $resolution['status'] ?? 'needs_evidence_review';
            $issue['resolution'] = (string) ($resolution['resolution'] ?? '');
            $issue['remaining_question'] = (string) ($resolution['remaining_question'] ?? '');
            $issue['current_wording'] = (string) ($resolution['current_wording'] ?? '');
            if($issue['current_wording'] === '') {
                foreach((array) ($issue['entry_ids'] ?? []) as $entryId) {
                    if(!empty($currentEntries[$entryId]['education_context'])) {
                        $issue['current_wording'] = $currentEntries[$entryId]['education_context'];
                        break;
                    }
                }
            }
            $issue['source_ids'] = array_values(array_unique(array_merge((array) ($issue['source_ids'] ?? []), (array) ($resolution['source_ids'] ?? []))));
        }
        unset($issue);
        foreach($content['products'] as &$product) {
            foreach(['child_usage'] as $field) {
                if(!in_array('I04', (array) ($publication['withdrawn_issue_ids'] ?? []), true) && isset($scenarioUpdates['product_learning'][$product['product_key']][$field])) $product[$field] = $scenarioUpdates['product_learning'][$product['product_key']][$field];
            }
            $product['author_recommendations'] = [];
            foreach($content['published_recommendation_entries'] as $entry) {
                foreach((array) ($entry['display_products'] ?? $entry['products'] ?? []) as $reference) {
                    if(($reference['product_key'] ?? null) === $product['product_key']) {
                        $product['author_recommendations'][] = $entry;
                        break;
                    }
                }
            }
            $product['draft_proposals'] = [];
            $product['education_applications'] = [];
            foreach($content['educational_entries'] as $entry) {
                foreach($entry['products'] as $reference) {
                    if($reference['product_key'] === $product['product_key']) {
                        $product['education_applications'][] = $entry;
                        break;
                    }
                }
            }
            $product['review_issue_ids'] = [];
            foreach($product['author_recommendations'] as $entry) {
                foreach((array) ($entry['review_issue_ids'] ?? []) as $issueId) {
                    foreach($content['editorial_review']['issues'] as $issue) {
                        if($issue['id'] === $issueId && $issue['status'] === 'needs_evidence_review') $product['review_issue_ids'][] = $issueId;
                    }
                }
            }
            $product['review_issue_ids'] = array_values(array_unique($product['review_issue_ids']));
            foreach($content['lessons'] as $lesson) {
                if(in_array($product['product_key'], (array) ($lesson['product_keys'] ?? []), true)) $product['lesson_ids'][] = $lesson['id'];
            }
            $product['lesson_ids'] = array_values(array_unique((array) ($product['lesson_ids'] ?? [])));
            foreach($content['editorial_review']['proposals'] as $proposal) {
                if(($proposal['status'] ?? '') === 'education_ready') continue;
                if(($proposal['primary_product_key'] ?? '') === $product['product_key']
                    || in_array($product['product_key'], (array) ($proposal['support_product_keys'] ?? []), true)) {
                    $product['draft_proposals'][] = $proposal;
                }
            }
            $priority = ['digestive_routine_support', 'acid_reflux_support', 'immunity_support_routine'];
            usort($product['author_recommendations'], static function(array $left, array $right) use ($priority): int {
                if(empty($left['education_context']) && empty($right['education_context']) && ($left['display_mode'] === 'archive_review') !== ($right['display_mode'] === 'archive_review')) {
                    return ($left['display_mode'] === 'archive_review') <=> ($right['display_mode'] === 'archive_review');
                }
                $leftRank = array_search($left['id'], $priority, true);
                $rightRank = array_search($right['id'], $priority, true);
                return ($leftRank === false ? count($priority) : $leftRank)
                    <=> ($rightRank === false ? count($priority) : $rightRank);
            });
        }
        unset($product);
        foreach($content['products'] as &$product) {
            $product = fcc_product_education_practical_product($product);
            $partnerProfile = array_replace((array) ($partnerLearning['products'][$product['product_key']] ?? []),
                (array) ($practicalApplications['products'][$product['product_key']] ?? []));
            foreach(['practical_learning_intro', 'practical_conversation_example', 'practical_takeaways', 'practical_study_questions', 'topic',
                'active_components', 'composition_source_facts', 'composition_source_status', 'composition_source_title', 'composition_source_metadata'] as $field) {
                if(array_key_exists($field, $partnerProfile)) {
                    $product[$field] = $partnerProfile[$field];
                }
            }
            $product['source_ids'] = array_values(array_unique(array_merge((array) ($product['source_ids'] ?? []),
                (array) ($partnerProfile['source_ids'] ?? []))));
            $depthProfile = (array) ($productDepth['products'][$product['product_key']] ?? []);
            if($depthProfile) {
                $product['learning_depth'] = $depthProfile;
                foreach(['practical_learning_intro', 'practical_takeaways', 'practical_study_questions'] as $field) {
                    if(array_key_exists($field, $depthProfile)) $product[$field] = $depthProfile[$field];
                }
                if(!empty($depthProfile['conversation_example'])) $product['practical_conversation_example'] = $depthProfile['conversation_example'];
                $product['source_ids'] = array_values(array_unique(array_merge($product['source_ids'],
                    (array) ($depthProfile['source_ids'] ?? []))));
            }
        }
        unset($product);
        $coverageFile = __DIR__ . '/../config/fcc_product_education_coverage.php';
        $coverage = is_file($coverageFile) ? (array) (require $coverageFile) : [];
        $coverage['author_resolved_references'] = (array) ($authorUpdates['resolved_references'] ?? []);
        $content['coverage'] = fcc_product_education_coverage($content, $coverage);
        $content['description'] = 'Upoznaj kome preporučiti proizvode, zašto su njihovi sastojci važni i koje se kombinacije preporučuju uz određeni problem. Nauči jasno objasniti preporuku kupcu.';
    }

    return $content;
}

/** Derive graph coverage from the actual loaded content, retaining identity review notes. */
function fcc_product_education_coverage(array $content, array $audit = []): array {
    $products = array_column((array) ($content['products'] ?? []), null, 'product_key');
    $entries = (array) ($content['recommendation_entries'] ?? []);
    $mapped = [];
    $unresolved = [];
    foreach($entries as $entry) {
        foreach((array) ($entry['products'] ?? []) as $reference) {
            $key = $reference['product_key'] ?? null;
            if(is_string($key) && isset($products[$key])) $mapped[$key] = true;
            else {
                $name = (string) ($reference['name'] ?? '');
                $unresolved[$name] ??= ['name' => $name, 'entry_ids' => [], 'reason' => 'Izvorni naziv još nema potvrđenu karticu proizvoda.'];
                $unresolved[$name]['entry_ids'][] = $entry['id'];
            }
        }
    }
    foreach((array) ($audit['unresolved_references'] ?? []) as $reference) {
        $name = (string) ($reference['name'] ?? '');
        if($name === '') continue;
        $unresolved[$name] = array_replace($unresolved[$name] ?? [], $reference);
    }
    foreach($unresolved as &$reference) {
        $reference['entry_ids'] = array_values(array_unique((array) ($reference['entry_ids'] ?? [])));
    }
    unset($reference);
    $resolvedReferences = [];
    foreach((array) ($audit['author_resolved_references'] ?? []) as $name => $resolution) {
        if(isset($unresolved[$name])) $resolvedReferences[] = array_replace($unresolved[$name], (array) $resolution);
        unset($unresolved[$name]);
    }
    $gapNotes = array_column((array) ($audit['unmapped_products'] ?? []), null, 'product_key');
    $unmapped = [];
    foreach($products as $key => $product) {
        if(isset($mapped[$key])) continue;
        $unmapped[] = array_replace([
            'product_key' => $key, 'name' => $product['name'], 'category' => $product['category'] ?? '',
            'reason' => 'U izvornoj matrici nema preporuke za ovaj proizvod.',
            'possible_use_topic' => '',
        ], $gapNotes[$key] ?? []);
    }
    $allMapped = [];
    foreach((array) ($content['published_recommendation_entries'] ?? $entries) as $entry) {
        foreach((array) ($entry['display_products'] ?? $entry['products'] ?? []) as $reference) {
            if(isset($products[$reference['product_key'] ?? ''])) $allMapped[$reference['product_key']] = true;
        }
    }
    foreach((array) ($content['educational_entries'] ?? []) as $entry) {
        foreach((array) ($entry['products'] ?? []) as $reference) {
            if(isset($products[$reference['product_key'] ?? ''])) $allMapped[$reference['product_key']] = true;
        }
    }
    $withoutApplications = [];
    foreach($products as $key => $product) {
        if(!empty($product['use_cases']) || (($product['education_visibility'] ?? '') === 'archive' && isset($mapped[$key]))) $allMapped[$key] = true;
        if(isset($allMapped[$key])) continue;
        $withoutApplications[] = array_replace(['product_key' => $key, 'name' => $product['name'], 'category' => $product['category'] ?? '', 'possible_use_topic' => ''], $gapNotes[$key] ?? []);
    }
    return array_replace($audit, [
        'summary' => array_replace((array) ($audit['summary'] ?? []), [
            'product_count' => count($products), 'recommendation_count' => count($entries),
            'scope_counts' => array_count_values(array_column($products, 'catalog_scope')),
            'source_only_family_count' => count(array_filter($products, static fn(array $product): bool => ($product['catalog_scope'] ?? '') === 'source_only')),
            'bundle_count' => count(array_filter($products, static fn(array $product): bool => ($product['catalog_scope'] ?? '') === 'bundle')),
            'mapped_product_count' => count($mapped), 'unmapped_product_count' => count($unmapped),
            'unresolved_reference_count' => count($unresolved),
            'archive_review_count' => count(array_filter($entries, static fn(array $entry): bool => ($entry['display_mode'] ?? '') === 'archive_review')),
            'issue_count' => count($content['editorial_review']['issues'] ?? []),
            'refined_scenario_count' => count(array_filter($entries, static fn(array $entry): bool => !empty($entry['education_context']) && ($entry['publication_status'] ?? 'published') === 'published')),
            'resolved_issue_count' => count(array_filter($content['editorial_review']['issues'] ?? [], static fn(array $issue): bool => ($issue['status'] ?? '') === 'resolved_education')),
            'uncertain_issue_count' => count(array_filter($content['editorial_review']['issues'] ?? [], static fn(array $issue): bool => !in_array(($issue['status'] ?? ''), ['resolved_education', 'withdrawn_by_author'], true))),
            'withdrawn_issue_count' => count(array_filter($content['editorial_review']['issues'] ?? [], static fn(array $issue): bool => ($issue['status'] ?? '') === 'withdrawn_by_author')),
            'withdrawn_scenario_count' => count(array_filter($entries, static fn(array $entry): bool => ($entry['publication_status'] ?? '') === 'withdrawn_by_author')),
            'lesson_count' => count($content['lessons'] ?? []),
            'completed_topic_count' => count($content['editorial_review']['completed_topics'] ?? []),
            'proposal_count' => count($content['editorial_review']['proposals'] ?? []),
            'published_application_count' => count($content['educational_entries'] ?? []),
            'products_with_application_count' => count($allMapped),
            'products_without_application_count' => count($withoutApplications),
            'missing_topic_count' => count($content['editorial_review']['missing_topics'] ?? []),
        ]),
        'unmapped_products' => $unmapped, 'unresolved_references' => array_values($unresolved),
        'resolved_references' => $resolvedReferences,
        'products_without_applications' => $withoutApplications,
    ]);
}

/** Photo coverage belongs to the rendered product cards after trusted image enrichment. */
function fcc_product_education_photo_coverage(array $coverage, array $products): array {
    $coverage['missing_photos'] = [];
    $coverage['archived_missing_photos'] = [];
    foreach($products as $product) {
        if(!empty($product['image_url'])) continue;
        if(($product['photo_requirement'] ?? '') === 'variable_bundle' && ($product['catalog_scope'] ?? '') === 'bundle') continue;
        $field = ($product['education_visibility'] ?? '') === 'archive' ? 'archived_missing_photos' : 'missing_photos';
        $coverage[$field][] = [
            'product_key' => $product['product_key'], 'name' => $product['name'],
            'reason' => 'Za ovaj identitet nije dostupna provjerena fotografija u FCC zbirci.',
        ];
    }
    $coverage['summary']['missing_photo_count'] = count($coverage['missing_photos']);
    return $coverage;
}

/** Copy the preferences container without changing unrelated settings. */
function fcc_product_education_preferences_object($preferences): object {
    if(is_string($preferences)) {
        $preferences = json_decode($preferences);
    }

    if(is_object($preferences)) {
        return (object) get_object_vars($preferences);
    }

    return is_array($preferences) ? (object) $preferences : new \stdClass();
}

function fcc_product_education_array($value): array {
    return is_object($value) ? get_object_vars($value) : (is_array($value) ? $value : []);
}

/** Only current content identities and valid server generated results count. */
function fcc_product_education_progress($preferences, array $content): array {
    $preferences = fcc_product_education_preferences_object($preferences);
    $saved = fcc_product_education_array($preferences->fcc_product_education ?? null);
    $records = fcc_product_education_array($saved['lessons'] ?? null);
    $version = (string) ($content['version'] ?? '');
    $completed = [];
    $next_lesson_id = null;
    $lessons = is_array($content['lessons'] ?? null) ? $content['lessons'] : [];

    foreach($lessons as $lesson) {
        if(!is_array($lesson) || !is_string($lesson['id'] ?? null)) {
            continue;
        }

        $id = $lesson['id'];
        $record = fcc_product_education_array($records[$id] ?? null);
        $score = $record['score'] ?? null;
        $completed_at = $record['completed_at'] ?? null;
        $date_is_valid = is_string($completed_at)
            && preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})?$/D', $completed_at)
            && strtotime($completed_at) !== false;

        if(is_int($score) && $score >= 80 && $score <= 100
            && ($record['version'] ?? null) === $version && $date_is_valid) {
            $completed[$id] = [
                'score' => $score,
                'completed_at' => $completed_at,
                'version' => $version,
            ];
        } elseif($next_lesson_id === null) {
            $next_lesson_id = $id;
        }
    }

    $product_keys = [];
    foreach(($content['products'] ?? []) as $product) {
        if(is_array($product) && is_string($product['product_key'] ?? null)) {
            $product_keys[$product['product_key']] = true;
        }
    }

    $favorites = [];
    foreach(fcc_product_education_array($saved['favorites'] ?? null) as $product_key) {
        if(is_string($product_key) && isset($product_keys[$product_key])) {
            $favorites[$product_key] = true;
        }
    }

    $total = count($lessons);
    $completed_count = count($completed);

    return [
        'completed' => $completed,
        'favorites' => array_keys($favorites),
        'completed_count' => $completed_count,
        'percent' => $total > 0 ? (int) round($completed_count / $total * 100) : 0,
        'next_lesson_id' => $next_lesson_id,
    ];
}

/** Grade option identities against the trusted lesson definition. */
function fcc_product_education_grade_quiz(array $lesson, array $answers): array {
    $questions = is_array($lesson['quiz'] ?? null) ? $lesson['quiz'] : [];
    $total = count($questions);
    $result = ['valid' => false, 'passed' => false, 'correct' => 0, 'total' => $total, 'score' => 0, 'feedback' => []];

    if($total === 0 || count($answers) !== $total) {
        return $result;
    }

    $question_ids = [];
    foreach($questions as $question) {
        if(!is_array($question) || !is_string($question['id'] ?? null)
            || isset($question_ids[$question['id']]) || !is_array($question['options'] ?? null)
            || !is_string($question['correct_option'] ?? null)
            || !array_key_exists($question['correct_option'], $question['options'])) {
            return $result;
        }

        $id = $question['id'];
        $question_ids[$id] = true;
        if(!array_key_exists($id, $answers) || !is_string($answers[$id])
            || !array_key_exists($answers[$id], $question['options'])) {
            return $result;
        }
    }

    foreach($answers as $id => $answer) {
        if(!is_string($id) || !isset($question_ids[$id])) {
            return $result;
        }
    }

    $result['valid'] = true;
    foreach($questions as $question) {
        $correct = $answers[$question['id']] === $question['correct_option'];
        $result['correct'] += $correct ? 1 : 0;
        $result['feedback'][] = [
            'question_id' => $question['id'],
            'correct' => $correct,
            'explanation' => (string) ($question['explanation'] ?? ''),
        ];
    }

    $result['score'] = (int) round($result['correct'] / $total * 100);
    $result['passed'] = $result['correct'] / $total >= 0.8;

    return $result;
}

/** Build a preferences update. Persistence and authentication belong to the caller. */
function fcc_product_education_update_preferences($preferences, array $content, string $action, array $input, string $now): array {
    $updated = fcc_product_education_preferences_object($preferences);
    $response = ['ok' => false, 'error' => null, 'preferences' => $updated, 'result' => []];
    $progress = fcc_product_education_progress($updated, $content);
    $version = (string) ($content['version'] ?? '');

    if($action === 'quiz') {
        $lesson_id = $input['lesson_id'] ?? null;
        $lesson = null;
        foreach(($content['lessons'] ?? []) as $candidate) {
            if(is_string($lesson_id) && ($candidate['id'] ?? null) === $lesson_id) {
                $lesson = $candidate;
                break;
            }
        }

        if(!$lesson) {
            $response['error'] = 'Odabrana lekcija nije dostupna.';
            return $response;
        }

        if(!is_array($input['answers'] ?? null)) {
            $response['error'] = 'Odgovori na sva pitanja kviza.';
            return $response;
        }

        $grade = fcc_product_education_grade_quiz($lesson, $input['answers']);
        $response['result'] = $grade;
        if(!$grade['valid']) {
            $response['error'] = 'Odgovori na sva pitanja ponuđenim odgovorima.';
            return $response;
        }

        if($grade['passed']) {
            if(!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}(?:Z|[+-]\d{2}:\d{2})?$/D', $now)
                || strtotime($now) === false) {
                $response['error'] = 'Rezultat se trenutačno ne može spremiti.';
                return $response;
            }

            $existing = $progress['completed'][$lesson_id] ?? null;
            $progress['completed'][$lesson_id] = [
                'score' => max($grade['score'], (int) ($existing['score'] ?? 0)),
                'completed_at' => $existing['completed_at'] ?? $now,
                'version' => $version,
            ];
        }
    } elseif($action === 'favorite') {
        $product_key = $input['product_key'] ?? null;
        $known = false;
        foreach(($content['products'] ?? []) as $product) {
            if(is_string($product_key) && ($product['product_key'] ?? null) === $product_key) {
                $known = true;
                break;
            }
        }

        if(!$known) {
            $response['error'] = 'Odabrani proizvod nije dio ove edukacije.';
            return $response;
        }

        $is_favorite = !in_array($product_key, $progress['favorites'], true);
        if($is_favorite) {
            $progress['favorites'][] = $product_key;
        } else {
            $progress['favorites'] = array_values(array_filter($progress['favorites'], static function($key) use ($product_key): bool {
                return $key !== $product_key;
            }));
        }

        $response['result'] = ['product_key' => $product_key, 'is_favorite' => $is_favorite];
    } else {
        $response['error'] = 'Odabrana radnja nije dostupna.';
        return $response;
    }

    $updated->fcc_product_education = (object) [
        'version' => $version,
        'lessons' => (object) $progress['completed'],
        'favorites' => array_values($progress['favorites']),
    ];
    $response['ok'] = true;
    $response['preferences'] = $updated;

    return $response;
}
