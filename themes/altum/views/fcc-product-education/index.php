<?php defined('ALTUMCODE') || die() ?>
<?php
require_once dirname(__DIR__, 4) . '/app/helpers/fcc_product_education_i18n.php';
$fpe_language = fcc_product_education_i18n_locale($data->language ?? null);
$fpe_t = static fn(string $text, array $replace = []): string => fcc_product_education_t($text, $fpe_language, $replace);
$fpe_escape = static fn($value): string => htmlspecialchars($fpe_t((string) $value), ENT_QUOTES, 'UTF-8');
$fpe_source_title = static function($value) use ($fpe_t): string {
    $title = (string) $value;
    $prefix = 'Pregledani službeni izvor za ';
    return str_starts_with($title, $prefix)
        ? $fpe_t('Pregledani službeni izvor za {product}', ['{product}' => substr($title, strlen($prefix))])
        : $title;
};
ob_start();
$fpe_conversation_paragraphs = static function(string $text) use ($fpe_escape): void {
    $paragraphs = preg_split('/\R\s*\R/u', trim($text)) ?: [];
    foreach($paragraphs as $index => $paragraph) {
        if($index > 0) echo "\n\n";
        ?><p><?= $fpe_escape($paragraph) ?></p><?php
    }
};
$fpe_content = (array) ($data->content ?? []);
$fpe_progress = (array) ($data->progress ?? []);
$fpe_lessons = (array) ($fpe_content['lessons'] ?? []);
$fpe_products = (array) ($data->products ?? $fpe_content['products'] ?? []);
$fpe_sources = (array) ($fpe_content['sources'] ?? []);
$fpe_recommendation_entries = array_merge((array) ($fpe_content['educational_entries'] ?? []), (array) ($fpe_content['published_recommendation_entries'] ?? $fpe_content['recommendation_entries'] ?? []));
$fpe_coverage = (array) ($fpe_content['coverage'] ?? []);
$fpe_editorial_review = (array) ($fpe_content['editorial_review'] ?? []);
$fpe_issue_index = array_column(array_values(array_filter((array) ($fpe_editorial_review['issues'] ?? []), static fn(array $issue): bool => ($issue['status'] ?? '') !== 'withdrawn_by_author')), null, 'id');
$fpe_completed = (array) ($fpe_progress['completed'] ?? []);
$fpe_favorites = (array) ($fpe_progress['favorites'] ?? []);
$fpe_section = (string) ($data->section ?? 'learn');
$fpe_is_admin = ($data->is_admin ?? false) === true;
$fpe_lesson = !empty($data->selected_lesson) ? (array) $data->selected_lesson : null;
$fpe_product = !empty($data->selected_product) ? (array) $data->selected_product : null;
$fpe_selected_entry = !empty($data->selected_recommendation) ? (array) $data->selected_recommendation : null;
$fpe_browse_query = (array) ($data->browse_query ?? []);
$fpe_quiz_result = !empty($data->quiz_result) ? (array) $data->quiz_result : null;
$fpe_quiz_answers = (array) ($data->quiz_answers ?? []);
$fpe_percent = max(0, min(100, (int) ($fpe_progress['percent'] ?? 0)));
$fpe_base_url = url('fcc-product-education');
$fpe_link = static fn(array $query = []): string => $fpe_base_url . ($query ? '?' . http_build_query($query) : '');
$fpe_external_url = static function($value): string {
    $value = trim((string) $value);
    $parsed = parse_url($value);
    return is_array($parsed) && strtolower((string) ($parsed['scheme'] ?? '')) === 'https' && !empty($parsed['host']) && empty($parsed['user']) && empty($parsed['pass']) ? $value : '';
};
$fpe_public_url = static function($value): string {
    $value = trim((string) $value);
    if($value === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) return '';
    $parsed = parse_url($value);
    $origin = parse_url(defined('SITE_URL') ? SITE_URL : url());
    if(!is_array($parsed) || !is_array($origin) || !empty($parsed['user']) || !empty($parsed['pass'])) return '';
    $scheme = strtolower((string) ($parsed['scheme'] ?? ''));
    $origin_scheme = strtolower((string) ($origin['scheme'] ?? ''));
    if(!in_array($scheme, ['http', 'https'], true) || $scheme !== $origin_scheme) return '';
    if(strtolower((string) ($parsed['host'] ?? '')) !== strtolower((string) ($origin['host'] ?? ''))) return '';
    $port = (int) ($parsed['port'] ?? ($scheme === 'https' ? 443 : 80));
    $origin_port = (int) ($origin['port'] ?? ($origin_scheme === 'https' ? 443 : 80));
    return $port === $origin_port ? $value : '';
};
$fpe_image_url = static function($value) use ($fpe_public_url, $fpe_external_url): string {
    $value = trim((string) $value);
    if($value === '' || preg_match('/[\x00-\x20\x7f\\\\]/', $value)) return '';
    if(str_starts_with($value, '/') && !str_starts_with($value, '//')) {
        if(str_contains(rawurldecode($value), '..')) return '';
        $value = rtrim(defined('SITE_URL') ? SITE_URL : url(), '/') . $value;
    }
    $same_origin = $fpe_public_url($value);
    if($same_origin !== '') return $same_origin;
    $external = $fpe_external_url($value);
    $host = strtolower((string) (parse_url($external, PHP_URL_HOST) ?? ''));
    return in_array($host, ['foreverliving.com', 'www.foreverliving.com', 'cdn.foreverliving.com', 'thealoeveraco.shop', 'www.thealoeveraco.shop'], true) ? $external : '';
};
$fpe_date = static function($value): string {
    if(!$value) return '';
    try { return (new \DateTimeImmutable((string) $value))->format('d.m.Y.'); }
    catch(\Throwable $error) { return ''; }
};
$fpe_lesson_index = [];
foreach($fpe_lessons as $item) $fpe_lesson_index[(string) ($item['id'] ?? '')] = $item;
$fpe_product_index = [];
foreach($fpe_products as $item) $fpe_product_index[(string) ($item['product_key'] ?? '')] = $item;
$fpe_recommendation_index = [];
foreach($fpe_recommendation_entries as $item) $fpe_recommendation_index[(string) ($item['id'] ?? $item['source_key'] ?? '')] = $item;
$fpe_next_lesson = $fpe_lesson_index[(string) ($fpe_progress['next_lesson_id'] ?? '')] ?? null;
$fpe_navigation = ['learn' => 'Učenje', 'products' => 'Upoznaj proizvode', 'needs' => 'Prema problemu', 'routines' => 'Kombinacije', 'favorites' => 'Moji favoriti'];
if($fpe_is_admin) $fpe_navigation['review'] = 'Za pregled';
$fpe_scope_labels = ['current_inventory' => 'U aktualnom katalogu', 'legacy_only' => 'Povijesni proizvod', 'bundle' => 'Paket proizvoda', 'source_only' => 'U izvornim materijalima'];
$fpe_scope_badge = static function(array $product) use ($fpe_scope_labels, $fpe_escape): void {
    $label = $fpe_scope_labels[(string) ($product['catalog_scope'] ?? '')] ?? '';
    if($label !== ''): ?><span class="fpe-scope-badge"><?= $fpe_escape($label) ?></span><?php endif;
};
$fpe_source_links = static function(array $source_ids) use ($fpe_sources, $fpe_escape, $fpe_t, $fpe_source_title, $fpe_external_url, $fpe_date): void {
    $items = [];
    foreach($source_ids as $source_id) {
        $source = $fpe_sources[$source_id] ?? null;
        if(!is_array($source)) continue;
        $source_url = $fpe_external_url($source['url'] ?? '');
        if($source_url === '' && empty($source['path'])) continue;
        $items[] = [$source, $source_url];
    }
    if(!$items) return;
    ?>
    <div class="fpe-sources">
        <h3>Izvori za daljnje učenje</h3>
        <ol>
            <?php foreach($items as [$source, $source_url]): ?>
                <li><?php if($source_url !== ''): ?><a href="<?= $fpe_escape($source_url) ?>" target="_blank" rel="noopener noreferrer"><?= $fpe_escape($fpe_source_title($source['title'] ?? 'Izvor')) ?><span class="sr-only">, otvara novu karticu</span></a><?php else: ?><span><?= $fpe_escape($fpe_source_title($source['title'] ?? 'Izvor')) ?></span><span class="fpe-source-date">Sačuvani lokalni materijal</span><?php endif ?><?php $source_date = $fpe_date($source['accessed_at'] ?? $source['reviewed_at'] ?? $source['checked_at'] ?? ''); ?><?php if($source_date !== ''): ?><span class="fpe-source-date"><?= $fpe_escape($fpe_t('Provjereno {date}', ['{date}' => $source_date])) ?></span><?php endif ?></li>
            <?php endforeach ?>
        </ol>
    </div>
    <?php
};
$fpe_related_lessons = static function(array $lesson_ids) use ($fpe_lesson_index, $fpe_escape, $fpe_link): void {
    foreach($lesson_ids as $lesson_id) {
        $lesson = $fpe_lesson_index[$lesson_id] ?? null;
        if(!$lesson) continue;
        ?><a class="fpe-related-link" href="<?= $fpe_escape($fpe_link(['section' => 'learn', 'lesson' => $lesson_id])) ?>"><i class="fas fa-book-open" aria-hidden="true"></i> <?= $fpe_escape($lesson['title']) ?></a><?php
    }
};
$fpe_product_reference = static function(string $product_key) use ($fpe_product_index, $fpe_escape, $fpe_link, $fpe_image_url, $fpe_scope_badge): void {
    $product = $fpe_product_index[$product_key] ?? null;
    if(!$product) return;
    $image_url = $fpe_image_url($product['image_url'] ?? '');
    ?><a class="fpe-related-link fpe-product-reference" href="<?= $fpe_escape($fpe_link(['section' => 'products', 'product' => $product_key])) ?>"><?php if($image_url !== ''): ?><img src="<?= $fpe_escape($image_url) ?>" alt="" width="56" height="56" loading="lazy" decoding="async"><?php endif ?><span><?= $fpe_escape($product['name']) ?><?php $fpe_scope_badge($product); ?></span></a><?php
};
$fpe_entry_references = static function(array $entry_ids) use ($fpe_recommendation_index, $fpe_escape, $fpe_link): void {
    if(!$entry_ids) return;
    $entries = array_filter(array_map(static fn($id) => $fpe_recommendation_index[(string) $id] ?? null, $entry_ids));
    if(!$entries) return;
    ?><div class="fpe-review-records"><span>Povezani primjeri za učenje</span><div><?php foreach($entries as $entry): ?><a href="<?= $fpe_escape($fpe_link(['section' => 'needs', 'entry' => (string) $entry['id']])) ?>"><?= $fpe_escape($entry['display_title'] ?? $entry['title'] ?? '') ?></a><?php endforeach ?></div></div><?php
};
$fpe_contributions = static function(array $contributions) use ($fpe_product_index, $fpe_escape, $fpe_product_reference): void {
    if(!$contributions) return;
    ?><div class="fpe-contributions"><h4>Uloga pojedinog proizvoda</h4><?php foreach($contributions as $contribution): ?><?php $contribution_product = $fpe_product_index[$contribution['product_key'] ?? ''] ?? null; if(!$contribution_product) continue; ?><div class="fpe-contribution"><?php $fpe_product_reference((string) $contribution_product['product_key']); ?><p><?= $fpe_escape($contribution['role'] ?? '') ?></p></div><?php endforeach ?></div><?php
};
$fpe_author_note = static function(): void {
    ?><div class="fpe-author-note"><span class="fpe-badge">Preporuke i kombinacije proizvoda</span><p>Izvorne preporuke prenose zabilježena iskustva Forever korisnika iz dostavljenih materijala. Uz svaku preporuku pronađi proizvode, razlog njihova odabira i primjer razgovora s kupcem. Dodatne primjene označene su zasebno.</p></div><?php
};
$fpe_author_product = static function(array $product) use ($fpe_product_index, $fpe_escape, $fpe_image_url, $fpe_link, $fpe_scope_badge): void {
    $key = is_string($product['product_key'] ?? null) ? $product['product_key'] : '';
    $study_product = $fpe_product_index[$key] ?? null;
    $name = (string) ($product['name'] ?? '');
    $image_url = $study_product ? $fpe_image_url($study_product['image_url'] ?? '') : '';
    if($study_product): ?><a class="fpe-author-product" href="<?= $fpe_escape($fpe_link(['section' => 'products', 'product' => $key])) ?>"><?php if($image_url !== ''): ?><img src="<?= $fpe_escape($image_url) ?>" alt="" width="38" height="38" loading="lazy" decoding="async"><?php endif ?><span><?= $fpe_escape($name) ?><?php $fpe_scope_badge($study_product); ?></span></a><?php else: ?><span class="fpe-author-product is-unlinked"><?= $fpe_escape($name) ?></span><?php endif;
};
$fpe_entry_search = static function(array $entry): string {
    $values = [$entry['id'] ?? '', $entry['source_key'] ?? '', $entry['display_title'] ?? '', $entry['title'] ?? '', $entry['original_patterns'] ?? [], $entry['display_products'] ?? $entry['products'] ?? []];
    if(!empty($entry['practical_application'])) foreach(['practical_application', 'combination_explanation', 'practical_product_roles', 'learner_takeaway', 'conversation_example', 'ingredient_learning', 'use_logic'] as $field) $values[] = $entry[$field] ?? [];
    if(!empty($entry['education_context'])) {
        foreach(['education_context', 'product_roles', 'applicable_when', 'decision_questions', 'next_steps', 'not_this_scenario'] as $field) $values[] = $entry[$field] ?? [];
    } elseif(($entry['display_mode'] ?? '') !== 'archive_review') { $values[] = $entry['context'] ?? ''; $values[] = $entry['recommendation_lines'] ?? []; }
    $parts = [];
    array_walk_recursive($values, static function($value) use (&$parts): void { if(is_string($value)) $parts[] = $value; });
    return implode(' ', $parts);
};
$fpe_author_entries = static function(array $entries, string $collection_id, bool $compact = false, bool $search_enabled = true, bool $detail = false) use ($fpe_escape, $fpe_t, $fpe_conversation_paragraphs, $fpe_author_product, $fpe_source_links, $fpe_entry_references, $fpe_issue_index, $fpe_link, $fpe_entry_search, $fpe_is_admin): void {
    $limit = $compact ? 12 : 24;
    $heading_tag = $detail ? 'h3' : 'h5';
    ?>
    <div class="fpe-author-collection" data-fpe-recommendation-collection data-fpe-recommendation-limit="<?= $fpe_escape($limit) ?>">
        <?php if($search_enabled): ?><div class="fpe-search fpe-author-search"><label for="<?= $fpe_escape($collection_id) ?>-search">Pronađi problem, proizvod ili kombinaciju</label><div><i class="fas fa-search" aria-hidden="true"></i><input type="search" id="<?= $fpe_escape($collection_id) ?>-search" data-fpe-recommendation-search placeholder="Na primjer: gastritis, zglobovi, aloe" autocomplete="off"></div><p class="fpe-muted" data-fpe-recommendation-count aria-live="polite"><?= $fpe_escape($fpe_t('{count} zapisa za učenje', ['{count}' => (string) count($entries)])) ?></p></div><?php endif ?>
        <div class="fpe-author-entries <?= $compact ? 'is-compact' : '' ?>">
            <?php foreach($entries as $entry): ?>
                <?php
                $archive_review = ($entry['display_mode'] ?? '') === 'archive_review';
                $source_note = ($entry['display_mode'] ?? '') === 'source_note';
                $educational_case = ($entry['display_mode'] ?? '') === 'educational_case';
                $refined_case = !empty($entry['education_context']);
                $practical_case = !empty($entry['practical_application']);
                $user_experience = !$educational_case && ($entry['source_type'] ?? '') === 'author_material';
                $entry_products = (array) ($entry['display_products'] ?? $entry['products'] ?? []);
                $primary_products = array_values(array_filter($entry_products, static fn($product) => ($product['role'] ?? '') === 'primary'));
                $support_products = array_values(array_filter($entry_products, static fn($product) => ($product['role'] ?? '') !== 'primary'));
                ?>
                <article class="fpe-author-entry <?= $archive_review && !$refined_case ? 'is-archive' : '' ?>" data-fpe-recommendation-entry data-fpe-individually-reviewed="<?= !empty($entry['individual_editorial_review']) ? '1' : '0' ?>" data-fpe-recommendation-id="<?= $fpe_escape($entry['id'] ?? '') ?>" data-fpe-recommendation-source="<?= $fpe_escape($entry['source_type'] ?? '') ?>" data-fpe-recommendation-text="<?= $fpe_escape($fpe_entry_search($entry)) ?>">
                    <?php if($user_experience): ?><span class="fpe-badge fpe-ready-badge">Preporuka proizvoda</span><?php elseif($refined_case): ?><span class="fpe-badge fpe-ready-badge">Praktični vodič za suradnika</span><?php elseif($archive_review): ?><span class="fpe-badge">Arhivski zapis za pregled</span><?php endif ?>
                    <?php if($educational_case): ?><span class="fpe-badge fpe-ready-badge"><?= $fpe_escape($fpe_t('Primjer primjene za učenje, {id}', ['{id}' => (string) $entry['id']])) ?></span><?php endif ?>
                    <?php if($detail): ?><h2><?= $fpe_escape($entry['display_title'] ?? $entry['title'] ?? '') ?></h2><?php else: ?><h4><?= $fpe_escape($entry['display_title'] ?? $entry['title'] ?? '') ?></h4><?php endif ?>
                    <?php if(!empty($entry['critical_notes'])): ?><div class="fpe-critical-notes"><<?= $heading_tag ?>>Važna napomena za ovu situaciju</<?= $heading_tag ?>><ol><?php foreach((array) $entry['critical_notes'] as $note): ?><li><?= $fpe_escape($note) ?></li><?php endforeach ?></ol></div><?php endif ?>
                    <?php if($practical_case): ?><div class="fpe-practical-application" <?= $detail ? 'id="fpe-entry-application"' : '' ?>><<?= $heading_tag ?>>Kome preporučiti i kod kojeg problema</<?= $heading_tag ?>><p class="fpe-application-text"><?= $fpe_escape($entry['practical_application']) ?></p></div><?php else: ?>
                        <?php if($educational_case && !empty($entry['context'])): ?><p class="fpe-application-text"><?= $fpe_escape($entry['context']) ?></p><?php endif ?>
                        <?php if($refined_case): ?><p class="fpe-application-text"><?= $fpe_escape($entry['education_context']) ?></p><?php endif ?>
                    <?php endif ?>
                    <div class="fpe-author-groups" <?= $detail ? 'id="fpe-entry-products"' : '' ?>>
                        <?php if($primary_products): ?><div class="fpe-author-primary"><<?= $heading_tag ?>><?= $archive_review && !$refined_case ? 'Glavni proizvod u izvornom zapisu' : (($entry['combination_kind'] ?? '') === 'alternatives' ? 'Proizvodi za odabir' : (count($primary_products) > 1 ? 'Glavni proizvodi' : 'Glavni proizvod')) ?></<?= $heading_tag ?>><div class="fpe-author-product-list"><?php foreach($primary_products as $entry_product) $fpe_author_product((array) $entry_product); ?></div></div><?php endif ?>
                        <?php if($support_products): ?><div class="fpe-author-support"><<?= $heading_tag ?>><?= $fpe_escape($entry['support_heading'] ?? ($archive_review && !$refined_case ? 'Ostali proizvodi izvornog zapisa' : (['alternatives' => 'Srodni proizvodi za usporedbu', 'sequence' => 'Povezani proizvodi u primjeni'][$entry['combination_kind'] ?? ''] ?? 'Proizvodi u kombinaciji'))) ?></<?= $heading_tag ?>><div class="fpe-author-product-list"><?php foreach($support_products as $entry_product) $fpe_author_product((array) $entry_product); ?></div></div><?php endif ?>
                    </div>
                    <?php if($practical_case): ?>
                        <div class="fpe-scenario-roles fpe-practical-combination" <?= $detail ? 'id="fpe-entry-explanation"' : '' ?>><<?= $heading_tag ?>><?= $fpe_escape($entry['combination_heading'] ?? (count($entry_products) === 1 ? 'Zašto ovaj proizvod odgovara primjeni' : (['alternatives' => 'Razlike koje vode odabir', 'sequence' => 'Smisao povezane primjene'][$entry['combination_kind'] ?? ''] ?? 'Smisao ove kombinacije'))) ?></<?= $heading_tag ?>><?php if(!empty($entry['combination_explanation'])): ?><p><?= $fpe_escape($entry['combination_explanation']) ?></p><?php endif ?>
                            <?php foreach($entry_products as $reference):
                                $role_keys = array_unique([$reference['product_key'] ?? '', $reference['name'] ?? '', $reference['original_product_key'] ?? '', $reference['original_name'] ?? '']);
                                $role_text = implode(' ', array_unique(array_intersect_key((array) ($entry['practical_product_roles'] ?? []), array_flip($role_keys))));
                                if($role_text === '') continue;
                                $ingredient_learning = (array) ($entry['ingredient_learning'][$reference['product_key'] ?? ''] ?? []);
                            ?><p><strong><?= $fpe_escape($reference['name'] ?? '') ?>:</strong> <?= $fpe_escape($role_text) ?></p>
                            <?php if($ingredient_learning): ?><div class="fpe-role-learning" data-fpe-role-learning="<?= $fpe_escape($reference['product_key']) ?>">
                                <a class="fpe-text-link" href="<?= $fpe_escape($fpe_link(['section' => 'products', 'product' => $reference['product_key']]) . '#fpe-product-ingredients') ?>">Upoznaj sastojke i posebnost proizvoda <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                            </div><?php endif ?>
                            <?php endforeach ?>
                        </div>
                        <?php if(!empty($entry['use_logic'])): ?><section class="fpe-use-logic" data-fpe-combination-kind="<?= $fpe_escape($entry['combination_kind'] ?? '') ?>"><<?= $heading_tag ?>><?= $fpe_escape($entry['use_logic_title'] ?? (['complementary' => 'Kako se uloge nadopunjuju', 'alternatives' => 'Kako odabrati između proizvoda', 'sequence' => 'Redoslijed primjene', 'single' => 'Primjena u svakodnevnom izboru'][$entry['combination_kind'] ?? ''] ?? 'Logika primjene')) ?></<?= $heading_tag ?>><ol><?php foreach((array) $entry['use_logic'] as $logic): ?><li><?= $fpe_escape($logic) ?></li><?php endforeach ?></ol></section><?php endif ?>
                        <?php if(!empty($entry['learner_takeaway'])): ?><div class="fpe-practical-takeaway"><<?= $heading_tag ?>>Zapamti</<?= $heading_tag ?>><?php if(is_array($entry['learner_takeaway'])): ?><ol><?php foreach($entry['learner_takeaway'] as $takeaway): ?><li><?= $fpe_escape($takeaway) ?></li><?php endforeach ?></ol><?php else: ?><p><?= $fpe_escape($entry['learner_takeaway']) ?></p><?php endif ?></div><?php endif ?>
                        <?php if(!empty($entry['conversation_example'])): ?><section class="fpe-combination-conversation" <?= $detail ? 'id="fpe-entry-conversation"' : '' ?>><<?= $heading_tag ?>>Kako preporučiti kupcu</<?= $heading_tag ?>><blockquote><?php $fpe_conversation_paragraphs($entry['conversation_example']); ?></blockquote></section><?php endif ?>
                        <?php if(!empty($entry['consultation_note'])): ?><p class="fpe-consultation-note"><i class="fas fa-comments" aria-hidden="true"></i><span><?= $fpe_escape($entry['consultation_note']) ?></span></p><?php endif ?>
                        <details class="fpe-source-disclosure fpe-scenario-details"><summary>Izvori i podloga preporuke</summary>
                            <?php if($refined_case && empty($entry['individual_editorial_review']) && !empty($entry['evidence_note'])): ?><p class="fpe-scenario-evidence"><?= $fpe_escape($entry['evidence_note']) ?></p><?php endif ?>
                            <?php if($educational_case && empty($entry['individual_editorial_review']) && !empty($entry['evidence_level'])): ?><p><?= $fpe_escape($entry['evidence_level']) ?></p><?php endif ?>
                            <?php foreach((array) ($entry['review_issue_ids'] ?? []) as $issue_id): $entry_issue = $fpe_issue_index[$issue_id] ?? []; if(!$fpe_is_admin || ($entry_issue['status'] ?? '') === 'resolved_education' || empty($entry_issue['remaining_question'])) continue; ?><p class="fpe-scenario-question"><a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $issue_id)) ?>"><?= $fpe_escape($fpe_t('Otvoreno pitanje {id}: {question}', ['{id}' => (string) $issue_id, '{question}' => (string) $entry_issue['remaining_question']])) ?></a></p><?php endforeach ?>
                            <?php $fpe_source_links((array) ($entry['education_source_ids'] ?? $entry['source_ids'] ?? [])); $fpe_entry_references((array) ($entry['related_entry_ids'] ?? [])); ?>
                            <p class="fpe-author-source-key"><?= $fpe_escape($fpe_t('Poveznica na izvorni zapis: {id}', ['{id}' => (string) ($entry['source_key'] ?? $entry['id'])])) ?></p>
                        </details>
                    <?php elseif($refined_case): ?>
                        <?php if(!empty($entry['product_roles'])): ?><div class="fpe-scenario-roles"><<?= $heading_tag ?>>Zašto su proizvodi povezani</<?= $heading_tag ?>><?php foreach($entry_products as $reference): $role_keys = array_unique([$reference['product_key'] ?? '', $reference['name'] ?? '', $reference['original_product_key'] ?? '', $reference['original_name'] ?? '']); $role_text = implode(' ', array_unique(array_intersect_key($entry['product_roles'], array_flip($role_keys)))); if($role_text === '') continue; ?><p><strong><?= $fpe_escape($reference['name'] ?? '') ?>:</strong> <?= $fpe_escape($role_text) ?></p><?php endforeach ?></div><?php endif ?>
                        <?php if(!empty($entry['next_steps'])): ?><div class="fpe-scenario-steps"><<?= $heading_tag ?>>Kako objasniti preporuku</<?= $heading_tag ?>><ol><?php foreach((array) $entry['next_steps'] as $step): ?><li><?= $fpe_escape($step) ?></li><?php endforeach ?></ol></div><?php endif ?>
                        <details class="fpe-source-disclosure fpe-scenario-details"><summary>Pitanja, precizna primjena i izvori</summary>
                            <?php foreach(['applicable_when' => 'Kada ovaj primjer odgovara situaciji', 'decision_questions' => 'Što pitati prije odabira', 'not_this_scenario' => 'Razlikuj od drugih situacija', 'refer_when' => 'Kada uključiti zdravstvenog stručnjaka'] as $field => $heading): if(empty($entry[$field])) continue; ?><<?= $heading_tag ?>><?= $fpe_escape($heading) ?></<?= $heading_tag ?>><ol><?php foreach((array) $entry[$field] as $item): ?><li><?= $fpe_escape($item) ?></li><?php endforeach ?></ol><?php endforeach ?>
                            <?php if(!empty($entry['evidence_note'])): ?><p class="fpe-scenario-evidence"><strong>Kako čitati podlogu preporuke:</strong> <?= $fpe_escape($entry['evidence_note']) ?></p><?php endif ?>
                            <?php foreach((array) ($entry['review_issue_ids'] ?? []) as $issue_id): $entry_issue = $fpe_issue_index[$issue_id] ?? []; if(!$fpe_is_admin || ($entry_issue['status'] ?? '') === 'resolved_education' || empty($entry_issue['remaining_question'])) continue; ?><p class="fpe-scenario-question"><a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-' . preg_replace('/[^a-zA-Z0-9_\-]/', '', $issue_id)) ?>"><?= $fpe_escape($fpe_t('Otvoreno pitanje {id}: {question}', ['{id}' => (string) $issue_id, '{question}' => (string) $entry_issue['remaining_question']])) ?></a></p><?php endforeach ?>
                            <?php $fpe_source_links((array) ($entry['education_source_ids'] ?? [])); ?>
                            <p class="fpe-author-source-key"><?= $fpe_escape($fpe_t('Poveznica na izvorni zapis: {id}', ['{id}' => (string) ($entry['source_key'] ?? $entry['id'])])) ?></p>
                        </details>
                    <?php elseif($educational_case): ?>
                        <details class="fpe-source-disclosure"><summary>Produbi razumijevanje</summary>
                            <?php if(!empty($entry['rationale'])): ?><p><?= $fpe_escape($entry['rationale']) ?></p><?php endif ?>
                            <?php if(!empty($entry['study_checks'])): ?><<?= $heading_tag ?>>Što proučiti pri osobnom odabiru</<?= $heading_tag ?>><ol><?php foreach($entry['study_checks'] as $check): ?><li><?= $fpe_escape($check) ?></li><?php endforeach ?></ol><?php endif ?>
                            <?php if(!empty($entry['evidence_level'])): ?><p><strong>Podloga:</strong> <?= $fpe_escape($entry['evidence_level']) ?></p><?php endif ?>
                            <?php $fpe_source_links((array) ($entry['source_ids'] ?? [])); $fpe_entry_references((array) ($entry['related_entry_ids'] ?? [])); ?>
                        </details>
                    <?php elseif($archive_review): ?>
                        <?php if(!empty($entry['review_note'])): ?><p class="fpe-archive-review-note"><?= $fpe_escape($entry['review_note']) ?></p><?php endif ?>
                        <?php if(!empty($entry['source_key'])): ?><p class="fpe-author-source-key">Izvorni zapis: <span><?= $fpe_escape($entry['source_key']) ?></span></p><?php endif ?>
                    <?php else: ?>
                        <details class="fpe-author-reason"><summary>Izvorno obrazloženje preporuke</summary><div>
                            <?php if($source_note && !empty($entry['review_note'])): ?><p class="fpe-source-review-note"><strong>Urednička bilješka:</strong> <?= $fpe_escape($entry['review_note']) ?></p><?php endif ?>
                            <?php if(!empty($entry['display_title']) && ($entry['display_title'] !== ($entry['title'] ?? ''))): ?><p class="fpe-author-raw-title">Izvorni naziv: <?= $fpe_escape($entry['title'] ?? '') ?></p><?php endif ?>
                            <?php if($source_note): ?><blockquote class="fpe-source-quotation"><?php endif ?>
                            <?php if(!empty($entry['context'])): ?><p><?= $fpe_escape($entry['context']) ?></p><?php endif ?>
                            <?php if(!empty($entry['recommendation_lines'])): ?><ol><?php foreach((array) $entry['recommendation_lines'] as $recommendation_line): ?><li><?= $fpe_escape($recommendation_line) ?></li><?php endforeach ?></ol><?php endif ?>
                            <?php if(!empty($entry['monthly_quantity_note'])): ?><div class="fpe-original-frame"><<?= $heading_tag ?>>Okvir iz izvornog zapisa</<?= $heading_tag ?>><p><?= $fpe_escape($entry['monthly_quantity_note']) ?></p></div><?php endif ?>
                            <?php if($source_note): ?></blockquote><?php endif ?>
                            <?php if(!empty($entry['source_key'])): ?><p class="fpe-author-source-key">Izvorni zapis: <span><?= $fpe_escape($entry['source_key']) ?></span></p><?php endif ?>
                        </div></details>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
        </div>
        <?php if($search_enabled): ?><p class="fpe-empty" data-fpe-recommendation-empty hidden>Nema zapisa za ovaj pojam. Pokušaj drugim nazivom problema ili proizvoda.</p><button class="fpe-button fpe-button-secondary fpe-author-more" type="button" data-fpe-recommendation-more hidden>Prikaži više zapisa</button><?php endif ?>
    </div>
    <?php
};
$fpe_kind_labels = ['complementary' => 'Kombinacija proizvoda', 'alternatives' => 'Odabir između proizvoda', 'sequence' => 'Redoslijed primjene', 'single' => 'Jedan proizvod'];
$fpe_recommendation_browser = static function(array $entries, string $section, string $collection_id) use ($fpe_escape, $fpe_t, $fpe_link, $fpe_entry_search, $fpe_author_product, $fpe_kind_labels, $fpe_browse_query): void {
    $kind_counts = array_count_values(array_column($entries, 'combination_kind'));
    ?>
    <div class="fpe-recommendation-browser" data-fpe-recommendation-collection data-fpe-recommendation-limit="12" data-fpe-browser-section="<?= $fpe_escape($section) ?>">
        <div class="fpe-browser-toolbar">
            <div class="fpe-search fpe-author-search"><label for="<?= $fpe_escape($collection_id) ?>-search">Pronađi problem, proizvod ili sastojak</label><div><i class="fas fa-search" aria-hidden="true"></i><input type="search" id="<?= $fpe_escape($collection_id) ?>-search" data-fpe-recommendation-search value="<?= $fpe_escape($fpe_browse_query['q'] ?? '') ?>" placeholder="Na primjer: gastritis, zglobovi, aloe" autocomplete="off" maxlength="200"></div></div>
            <div class="fpe-kind-filters" role="group" aria-label="Vrsta preporuke">
                <button type="button" data-fpe-kind-filter="" aria-pressed="<?= empty($fpe_browse_query['kind']) ? 'true' : 'false' ?>">Sve <span><?= count($entries) ?></span></button>
                <?php foreach($fpe_kind_labels as $kind => $label): if(empty($kind_counts[$kind])) continue; ?><button type="button" data-fpe-kind-filter="<?= $fpe_escape($kind) ?>" aria-pressed="<?= ($fpe_browse_query['kind'] ?? '') === $kind ? 'true' : 'false' ?>"><?= $fpe_escape($label) ?> <span><?= (int) $kind_counts[$kind] ?></span></button><?php endforeach ?>
            </div>
            <p class="fpe-browser-results fpe-muted" data-fpe-recommendation-count aria-live="polite" aria-atomic="true"><?= $fpe_escape($fpe_t('{count} preporuka za učenje', ['{count}' => (string) count($entries)])) ?></p>
        </div>
        <div class="fpe-summary-grid">
            <?php foreach($entries as $entry):
                $id = (string) $entry['id'];
                $entry_products = (array) ($entry['display_products'] ?? $entry['products'] ?? []);
                $entry_url = $fpe_link(array_merge(['section' => $section, 'entry' => $id], $fpe_browse_query)) . '#fpe-recommendation-detail';
                $title = (string) ($entry['display_title'] ?? $entry['title'] ?? '');
            ?>
                <article class="fpe-summary-card" tabindex="-1" id="<?= $fpe_escape('fpe-card-' . $id) ?>" data-fpe-recommendation-entry data-fpe-recommendation-id="<?= $fpe_escape($id) ?>" data-fpe-recommendation-kind="<?= $fpe_escape($entry['combination_kind'] ?? '') ?>" data-fpe-recommendation-source="<?= $fpe_escape($entry['source_type'] ?? '') ?>" data-fpe-recommendation-text="<?= $fpe_escape($fpe_entry_search($entry)) ?>">
                    <div class="fpe-summary-topline"><span class="fpe-badge fpe-ready-badge"><?= $fpe_escape($fpe_kind_labels[$entry['combination_kind'] ?? ''] ?? 'Preporuka proizvoda') ?></span><span><?= $fpe_escape($fpe_t(count($entry_products) === 1 ? '{count} proizvod' : '{count} proizvoda', ['{count}' => (string) count($entry_products)])) ?></span></div>
                    <h3><a class="fpe-summary-title" data-fpe-open-entry href="<?= $fpe_escape($entry_url) ?>"><?= $fpe_escape($title) ?></a></h3>
                    <p class="fpe-summary-description"><?= $fpe_escape($entry['practical_application'] ?? $entry['education_context'] ?? $entry['context'] ?? '') ?></p>
                    <div class="fpe-summary-products fpe-author-product-list" aria-label="Proizvodi u preporuci"><?php foreach(array_slice($entry_products, 0, 3) as $reference) $fpe_author_product((array) $reference); ?><?php if(count($entry_products) > 3): ?><span class="fpe-summary-extra"><?= $fpe_escape($fpe_t('Još {count} u preporuci', ['{count}' => (string) (count($entry_products) - 3)])) ?></span><?php endif ?></div>
                    <div class="fpe-summary-footer"><?php if(!empty($entry['critical_notes'])): ?><span class="fpe-summary-note"><i class="fas fa-info-circle" aria-hidden="true"></i> Važna napomena u preporuci</span><?php else: ?><span class="fpe-muted">Primjena i objašnjenje</span><?php endif ?><a class="fpe-text-link" data-fpe-open-entry href="<?= $fpe_escape($entry_url) ?>" aria-label="<?= $fpe_escape($fpe_t('Otvori preporuku: {title}', ['{title}' => $title])) ?>">Otvori preporuku <i class="fas fa-arrow-right" aria-hidden="true"></i></a></div>
                </article>
            <?php endforeach ?>
        </div>
        <div class="fpe-browser-empty" data-fpe-recommendation-empty hidden><i class="fas fa-search" aria-hidden="true"></i><h3>Nema preporuka za ovu pretragu</h3><p>Pokušaj drugim nazivom problema ili proizvoda, ili prikaži sve preporuke.</p><button type="button" class="fpe-button fpe-button-secondary" data-fpe-recommendation-reset>Prikaži sve preporuke</button></div>
        <button class="fpe-button fpe-button-secondary fpe-author-more" type="button" data-fpe-recommendation-more hidden>Prikaži još preporuka <i class="fas fa-chevron-down" aria-hidden="true"></i></button>
    </div>
    <?php
};
$fpe_recommendation_detail = static function(array $entry, array $entries, string $section) use ($fpe_escape, $fpe_t, $fpe_link, $fpe_author_entries, $fpe_kind_labels, $fpe_browse_query, $fpe_entry_search): void {
    $normalize = static function(string $text): string {
        $text = mb_strtolower($text, 'UTF-8');
        if(class_exists('Normalizer')) $text = \Normalizer::normalize($text, \Normalizer::FORM_D) ?: $text;
        $text = preg_replace('/\p{Mn}+/u', '', $text) ?? $text;
        return strtr($text, ['č' => 'c', 'ć' => 'c', 'š' => 's', 'ž' => 'z', 'đ' => 'd', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a', 'ö' => 'o', 'ô' => 'o', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'í' => 'i', 'ñ' => 'n']);
    };
    $query = $normalize((string) ($fpe_browse_query['q'] ?? ''));
    $kind = (string) ($fpe_browse_query['kind'] ?? '');
    $matches = array_values(array_filter($entries, static fn(array $item): bool => ($kind === '' || ($item['combination_kind'] ?? '') === $kind) && ($query === '' || str_contains($normalize($fpe_entry_search($item)), $query))));
    $position = array_search($entry['id'], array_column($matches, 'id'), true);
    $previous = $position !== false && $position > 0 ? $matches[$position - 1] : null;
    $next = $position !== false && $position + 1 < count($matches) ? $matches[$position + 1] : null;
    $back_url = $fpe_link(array_merge(['section' => $section], $fpe_browse_query)) . '#fpe-card-' . $entry['id'];
    $detail_url = static fn(array $item): string => $fpe_link(array_merge(['section' => $section, 'entry' => $item['id']], $fpe_browse_query)) . '#fpe-recommendation-detail';
    ?>
    <main class="fpe-recommendation-detail" id="fpe-recommendation-detail">
        <div class="fpe-detail-toolbar"><a class="fpe-text-link" href="<?= $fpe_escape($back_url) ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> <?= $section === 'needs' ? 'Natrag na preporuke prema problemu' : 'Natrag na kombinacije' ?></a><div class="fpe-detail-pager"><?php if($position !== false): ?><span><?= $fpe_escape($fpe_t('{shown} od {total}', ['{shown}' => (string) ($position + 1), '{total}' => (string) count($matches)])) ?></span><?php endif ?><?php if($previous): ?><a class="fpe-button fpe-button-secondary" href="<?= $fpe_escape($detail_url($previous)) ?>" aria-label="<?= $fpe_escape($fpe_t('Prethodna preporuka: {title}', ['{title}' => (string) ($previous['display_title'] ?? $previous['title'])])) ?>"><i class="fas fa-chevron-left" aria-hidden="true"></i> Prethodna</a><?php endif ?><?php if($next): ?><a class="fpe-button fpe-button-secondary" href="<?= $fpe_escape($detail_url($next)) ?>" aria-label="<?= $fpe_escape($fpe_t('Sljedeća preporuka: {title}', ['{title}' => (string) ($next['display_title'] ?? $next['title'])])) ?>">Sljedeća <i class="fas fa-chevron-right" aria-hidden="true"></i></a><?php endif ?></div></div>
        <nav class="fpe-detail-jumps" aria-label="Brzi pristup dijelovima preporuke"><?php foreach(['application' => 'Kome preporučiti', 'products' => 'Proizvodi', 'explanation' => 'Zašto ova preporuka', 'conversation' => 'Razgovor s kupcem'] as $anchor => $label): ?><a href="<?= $fpe_escape($fpe_link(array_merge(['section' => $section, 'entry' => $entry['id']], $fpe_browse_query)) . '#fpe-entry-' . $anchor) ?>"><?= $fpe_escape($label) ?></a><?php endforeach ?></nav>
        <?php $fpe_author_entries([$entry], 'fpe-selected-recommendation', true, false, true); ?>
        <a class="fpe-text-link fpe-detail-back-bottom" href="<?= $fpe_escape($back_url) ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> Natrag na popis preporuka</a>
    </main>
    <?php
};
$fpe_quantity = static fn($value): string => is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',') : '';
$fpe_counted = static fn($count, string $unit): string => fcc_product_education_i18n_counted($count, $unit, $fpe_language);
$fpe_package_plans = static function(array $packaging, array $usage_guide = []) use ($fpe_escape, $fpe_t, $fpe_external_url, $fpe_date, $fpe_quantity, $fpe_counted, $fpe_source_links): void {
    $plans = (array) ($packaging['plans'] ?? []);
    if(!empty($usage_guide['steps']) && !array_filter($plans, static fn(array $plan): bool => ($plan['declared_daily_count'] ?? null) !== null)) {
        ?><section class="fpe-profile-section fpe-packaging fpe-usage-guide" id="fpe-package-plan"><h3><?= $fpe_escape($usage_guide['title'] ?? 'Kako se koristi') ?></h3><p><?= $fpe_escape($usage_guide['intro'] ?? '') ?></p><ol><?php foreach((array) $usage_guide['steps'] as $step): ?><li><?= $fpe_escape($step) ?></li><?php endforeach ?></ol>
            <details class="fpe-source-disclosure"><summary>Službene upute i pakiranje</summary><?php $fpe_source_links((array) ($usage_guide['source_ids'] ?? [])); ?>
                <?php foreach($plans as $plan): ?><p><strong><?= $fpe_escape($plan['pack_label'] ?? '') ?></strong><?php if(!empty($plan['declared_usage_text'])): ?>: <?= $fpe_escape($plan['declared_usage_text']) ?><?php endif ?></p><?php endforeach ?>
                <?php $usage_notes = []; foreach((array) ($packaging['usage_sources'] ?? []) as $usage_source) { if(($usage_source['review_status'] ?? '') !== 'approved') continue; foreach((array) ($usage_source['safety_notes'] ?? []) as $note) if(is_string($note) && trim($note) !== '') $usage_notes[] = trim($note); } if($usage_notes): ?><ol><?php foreach(array_unique($usage_notes) as $note): ?><li><?= $fpe_escape($note) ?></li><?php endforeach ?></ol><?php endif ?>
            </details>
        </section><?php return;
    }
    ?><section class="fpe-profile-section fpe-packaging" id="fpe-package-plan"><span class="fpe-eyebrow">Praktično planiranje</span><h3>Količine za 30 dana</h3><p>Usporedi veličinu pakiranja, dnevnu uporabu i koliko pakiranja pokriva odabrano razdoblje.</p>
    <?php if(!$plans): ?><p class="fpe-muted">Za ovaj identitet još nema potvrđene veličine aktualnog pakiranja. Kada se poveže točno pakiranje, ovdje će biti dostupan obračun.</p><?php else: ?>
    <div class="fpe-pack-grid"><?php foreach($plans as $plan):
        $count = $plan['declared_daily_count'] ?? null;
        $blocked = !empty($plan['continuous_period_blocked']);
        $source_url = $fpe_external_url($plan['declared_usage_source']['source_url'] ?? $plan['source_url'] ?? '');
        $source_date = $fpe_date(($plan['declared_usage_source']['last_verified_at'] ?? '') ?: ($plan['declared_usage_source']['source_date'] ?? $plan['source_date'] ?? ''));
        ?><article class="fpe-pack-card">
            <div class="fpe-card-topline"><h4><?= $fpe_escape($plan['pack_label'] ?: ($plan['name'] ?? 'Pakiranje')) ?></h4><?php if(($plan['availability'] ?? '') !== 'available'): ?><span class="fpe-badge">Dostupnost za provjeru</span><?php elseif($count !== null && !$blocked && empty($plan['discrepancy'])): ?><span class="fpe-badge fpe-ready-badge">Pakiranje za 30 dana</span><?php endif ?></div>
            <?php if(!empty($plan['declared_usage_text'])): ?><p class="fpe-label-use"><strong>Prema uputi:</strong> <?= $fpe_escape($plan['declared_usage_text']) ?></p><?php else: ?>
                <?php $usage_shown = false; foreach((array) ($packaging['usage_sources'] ?? []) as $usage_source): if(($usage_source['review_status'] ?? '') !== 'approved') continue; foreach((array) ($usage_source['display_usage_texts'] ?? $usage_source['usage_texts'] ?? []) as $usage_text): if(!is_string($usage_text) || trim($usage_text) === '') continue; $usage_shown = true; ?><p class="fpe-label-use"><strong>Prema uputi:</strong> <?= $fpe_escape($usage_text) ?></p><?php endforeach; if($usage_shown) break; endforeach ?>
            <?php endif ?>
            <?php if($count !== null): ?><dl class="fpe-pack-facts">
                <div><dt>Dnevna količina iz upute</dt><dd><?= $fpe_escape($fpe_counted($count, $plan['unit'])) ?></dd></div>
                <div><dt>Trajanje jednog pakiranja</dt><dd><?= $fpe_escape($fpe_t($blocked ? '{days} dana, matematički' : '{days} dana', ['{days}' => $fpe_quantity($plan['full_days_per_pack'] ?? $plan['days_per_pack'])])) ?></dd></div>
                <?php if($blocked): ?>
                    <div><dt>Najduže navedeno neprekidno razdoblje</dt><dd><?= $fpe_escape($fpe_t('{days} dana', ['{days}' => (string) $plan['allowed_period_days']])) ?></dd></div>
                    <div><dt>Potrošnja u tom razdoblju</dt><dd><?= $fpe_escape($fpe_counted($plan['consumed_in_allowed_period'], $plan['unit'])) ?></dd></div>
                    <div><dt>Preostaje nakon tog razdoblja</dt><dd><?= $fpe_escape($fpe_counted($plan['leftover_after_allowed_period'], $plan['unit'])) ?></dd></div>
                <?php else: ?>
                    <div><dt>Za 30 dana prema uputi</dt><dd><?= $fpe_escape($fpe_counted($plan['packs_needed_for_30'], 'pack')) ?></dd></div>
                    <div><dt>Potrošnja za 30 dana</dt><dd><?= $fpe_escape($fpe_counted($plan['consumed_for_30'], $plan['unit'])) ?></dd></div>
                    <div><dt>Preostaje iz kupljenih pakiranja</dt><dd><?= $fpe_escape($fpe_counted($plan['leftover_after_30'], $plan['unit'])) ?></dd></div>
                <?php endif ?>
            </dl><?php endif ?>
            <?php if($count === null || $blocked || !empty($plan['discrepancy']) || ($plan['availability'] ?? '') !== 'available'): ?><p class="fpe-pack-note"><?= $fpe_escape($plan['reason'] ?? '') ?></p><?php endif ?>
            <?php
            $safety_notes = [];
            foreach((array) ($packaging['usage_sources'] ?? []) as $usage_source) {
                if(($usage_source['review_status'] ?? '') !== 'approved') continue;
                foreach((array) ($usage_source['safety_notes'] ?? []) as $note) if(is_string($note) && trim($note) !== '') $safety_notes[] = trim($note);
            }
            $safety_notes = array_values(array_unique($safety_notes));
            $safety_notes = array_values(array_filter($safety_notes, static function(string $note) use ($safety_notes): bool {
                foreach($safety_notes as $other) if($other !== $note && str_contains($other, $note)) return false;
                return true;
            }));
            ?>
            <?php if($safety_notes): ?><details class="fpe-source-disclosure fpe-professional-notes"><summary>Važne napomene uz uporabu</summary><ol><?php foreach($safety_notes as $note): ?><li><?= $fpe_escape($note) ?></li><?php endforeach ?></ol></details><?php endif ?>
            <?php if(($plan['package_average_per_day'] ?? null) !== null): ?><details class="fpe-source-disclosure"><summary>Kako se računa jedno pakiranje za 30 dana</summary><p><?= $fpe_escape($fpe_t('{count} ÷ 30 = {quantity} dnevno, kao matematički prosjek pakiranja. Dnevna uporaba i raspored uzimanja dolaze iz upute proizvoda.', ['{count}' => (string) $plan['count'], '{quantity}' => $fpe_counted($plan['package_average_per_day'], $plan['unit'])])) ?></p></details><?php endif ?>
            <?php if($source_url !== ''): ?><a class="fpe-text-link" href="<?= $fpe_escape($source_url) ?>" target="_blank" rel="noopener noreferrer">Izvor pakiranja i uporabe <span class="sr-only">, otvara novu karticu</span></a><?php if($source_date !== ''): ?><span class="fpe-source-date"><?= $fpe_escape($fpe_t('Izvor pregledan {date}', ['{date}' => $source_date])) ?></span><?php endif ?><?php endif ?>
        </article><?php endforeach ?></div><?php endif ?></section><?php
};
$fpe_product_cards = static function(array $products, bool $favorite_view = false, string $grid_id = 'fpe-product-grid') use ($fpe_escape, $fpe_t, $fpe_link, $fpe_favorites, $fpe_image_url, $fpe_scope_badge): void {
    ?>
    <div class="fpe-product-grid" id="<?= $fpe_escape($grid_id) ?>">
        <?php foreach($products as $product): ?>
            <?php
            $key = (string) ($product['product_key'] ?? '');
            $is_favorite = in_array($key, $fpe_favorites, true);
            $category = trim((string) ($product['category'] ?? ''));
            $search_parts = [(string) ($product['name'] ?? ''), (string) ($product['topic'] ?? ''), $category, (string) ($product['practical_learning_intro'] ?? $product['learning_intro'] ?? '')];
            foreach(['ingredient_facts', 'composition_source_facts', 'active_components', 'use_cases', 'learning_depth'] as $search_field) {
                $search_values = (array) ($product[$search_field] ?? []);
                array_walk_recursive($search_values, static function($value) use (&$search_parts): void { if(is_string($value)) $search_parts[] = $value; });
            }
            foreach(array_merge((array) ($product['education_applications'] ?? []), (array) ($product['author_recommendations'] ?? [])) as $author_entry) {
                $author_search_values = [$author_entry['display_title'] ?? '', $author_entry['title'] ?? '', $author_entry['original_patterns'] ?? [], $author_entry['display_products'] ?? $author_entry['products'] ?? [], $author_entry['education_context'] ?? '', $author_entry['product_roles'] ?? []];
                array_walk_recursive($author_search_values, static function($value) use (&$search_parts): void { if(is_string($value)) $search_parts[] = $value; });
            }
            $search_text = mb_strtolower(implode(' ', $search_parts));
            $image_url = $fpe_image_url($product['image_url'] ?? '');
            ?>
            <article class="fpe-product-card" data-product-search="<?= $fpe_escape($search_text) ?>" data-product-category="<?= $fpe_escape($category) ?>">
                <a class="fpe-product-photo" href="<?= $fpe_escape($fpe_link(['section' => 'products', 'product' => $key])) ?>" aria-label="<?= $fpe_escape($fpe_t('Upoznaj proizvod: {product}', ['{product}' => (string) ($product['name'] ?? $key)])) ?>"><?php if($image_url !== ''): ?><img src="<?= $fpe_escape($image_url) ?>" alt="<?= $fpe_escape($product['image_alt'] ?? $product['name'] ?? '') ?>" width="<?= $fpe_escape(max(1, (int) ($product['image_width'] ?? 600))) ?>" height="<?= $fpe_escape(max(1, (int) ($product['image_height'] ?? 600))) ?>" loading="lazy" decoding="async"><?php else: ?><span><?= $fpe_escape($product['photo_note'] ?? 'Fotografija povijesnog pakiranja nije dostupna.') ?></span><?php endif ?></a>
                <div class="fpe-card-topline"><span class="fpe-eyebrow"><?= $fpe_escape($product['topic'] ?? ($category ?: 'Proizvod')) ?></span><?php $fpe_scope_badge($product); ?></div>
                <h3><a href="<?= $fpe_escape($fpe_link(['section' => 'products', 'product' => $key])) ?>"><?= $fpe_escape($product['name'] ?? $key) ?></a></h3>
                <p class="fpe-card-description"><?= $fpe_escape($product['practical_learning_intro'] ?? $product['learning_intro'] ?? 'Upoznaj primjenu proizvoda i kombinacije za svakodnevne potrebe.') ?></p>
                <?php $product_topics = array_slice(array_merge((array) ($product['author_recommendations'] ?? []), (array) ($product['education_applications'] ?? [])), 0, 3); if($product_topics): ?><div class="fpe-card-topics"><?php foreach($product_topics as $case): ?><span><?= $fpe_escape($case['display_title'] ?? $case['title'] ?? '') ?></span><?php endforeach ?></div><?php endif ?>
                <div class="fpe-card-actions">
                    <a class="fpe-text-link" href="<?= $fpe_escape($fpe_link(['section' => 'products', 'product' => $key])) ?>">Prouči proizvod <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    <form method="post" action="<?= $fpe_escape($fpe_link(['section' => $favorite_view ? 'favorites' : 'products'])) ?>">
                        <input type="hidden" name="token" value="<?= $fpe_escape(\Altum\Csrf::get()) ?>">
                        <input type="hidden" name="action" value="favorite">
                        <input type="hidden" name="product_key" value="<?= $fpe_escape($key) ?>">
                        <button type="submit" class="fpe-favorite" aria-label="<?= $fpe_escape(($is_favorite ? 'Ukloni iz favorita: ' : 'Spremi u favorite: ') . (string) ($product['name'] ?? $key)) ?>" aria-pressed="<?= $is_favorite ? 'true' : 'false' ?>"><i class="<?= $is_favorite ? 'fas' : 'far' ?> fa-bookmark" aria-hidden="true"></i></button>
                    </form>
                </div>
            </article>
        <?php endforeach ?>
    </div>
    <?php
};
?>

<div class="container fpe-page <?= $fpe_selected_entry ? 'is-recommendation-detail' : (in_array($fpe_section, ['needs', 'routines'], true) ? 'is-recommendation-browser' : '') ?>">
    <?= \Altum\Alerts::output_alerts() ?>
    <header class="fpe-hero">
        <div class="fpe-hero-content">
            <span class="fpe-hero-eyebrow"><i class="fas fa-graduation-cap" aria-hidden="true"></i> Forever edukacija <span>Interna edukacija</span></span>
            <h1><?= $fpe_escape($fpe_content['title'] ?? 'Znanje o proizvodima') ?></h1>
            <p><?= $fpe_escape($fpe_content['description'] ?? 'Razumij sastav proizvoda, poveži znanje o prehrani i nauči odgovorno razgovarati s korisnicima.') ?></p>
        </div>
        <div class="fpe-hero-progress">
            <div class="fpe-progress-heading"><span>Tvoj napredak</span><strong><?= $fpe_escape($fpe_percent) ?>%</strong></div>
            <progress value="<?= $fpe_escape($fpe_percent) ?>" max="100" aria-label="Napredak edukacije"><?= $fpe_escape($fpe_percent) ?>%</progress>
            <p><?= $fpe_escape($fpe_t('Završene lekcije: {completed} od {total}', ['{completed}' => (string) (int) ($fpe_progress['completed_count'] ?? 0), '{total}' => (string) count($fpe_lessons)])) ?></p>
        </div>
    </header>

    <nav class="fpe-navigation" aria-label="Dijelovi edukacije">
        <?php foreach($fpe_navigation as $nav_key => $nav_label): ?>
            <a href="<?= $fpe_escape($fpe_link(['section' => $nav_key])) ?>" class="<?= $nav_key === $fpe_section ? 'is-active' : '' ?>" <?= $nav_key === $fpe_section ? 'aria-current="page"' : '' ?>><?= $fpe_escape($nav_label) ?><?php if($nav_key === 'favorites' && count($fpe_favorites) > 0): ?><span class="fpe-nav-count"><?= $fpe_escape(count($fpe_favorites)) ?></span><?php endif ?></a>
        <?php endforeach ?>
    </nav>

    <?php if($fpe_lesson): ?>
        <div class="fpe-detail-layout">
            <main class="fpe-reading-panel">
                <a class="fpe-back-link" href="<?= $fpe_escape($fpe_link(['section' => 'learn'])) ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> Sve lekcije</a>
                <div class="fpe-detail-heading"><span class="fpe-eyebrow"><?= $fpe_escape($fpe_t('{minutes} minuta učenja', ['{minutes}' => (string) (int) ($fpe_lesson['minutes'] ?? 5)])) ?></span><?php if(isset($fpe_completed[$fpe_lesson['id']])): ?><span class="fpe-badge is-completed"><i class="fas fa-check" aria-hidden="true"></i> Završeno</span><?php endif ?></div>
                <h2><?= $fpe_escape($fpe_lesson['title'] ?? '') ?></h2>
                <p class="fpe-reading-intro"><?= $fpe_escape($fpe_lesson['summary'] ?? '') ?></p>
                <?php foreach((array) ($fpe_lesson['sections'] ?? []) as $lesson_section): ?>
                    <section class="fpe-lesson-section">
                        <h3><?= $fpe_escape($lesson_section['title'] ?? '') ?></h3>
                        <?php foreach((array) ($lesson_section['paragraphs'] ?? []) as $paragraph): ?><p><?= $fpe_escape($paragraph) ?></p><?php endforeach ?>
                        <?php if(!empty($lesson_section['points'])): ?><ol><?php foreach((array) $lesson_section['points'] as $point): ?><li><?= $fpe_escape($point) ?></li><?php endforeach ?></ol><?php endif ?>
                    </section>
                <?php endforeach ?>
                <?php if(!empty($fpe_lesson['takeaways'])): ?>
                    <section class="fpe-takeaways"><h3><i class="far fa-lightbulb" aria-hidden="true"></i> Što ponijeti iz ove lekcije</h3><ol><?php foreach((array) $fpe_lesson['takeaways'] as $takeaway): ?><li><?= $fpe_escape($takeaway) ?></li><?php endforeach ?></ol></section>
                <?php endif ?>
                <?php $fpe_source_links((array) ($fpe_lesson['source_ids'] ?? [])); ?>

                <?php if(!empty($fpe_lesson['quiz'])): ?>
                    <section class="fpe-quiz" aria-labelledby="fpe-quiz-title">
                        <span class="fpe-eyebrow">Primijeni naučeno</span><h3 id="fpe-quiz-title">Provjera znanja</h3>
                        <p>Odaberi jedan odgovor na svako pitanje. Napredak se sprema nakon uspješne provjere.</p>
                        <?php if($fpe_quiz_result): ?>
                            <div class="fpe-quiz-result <?= !empty($fpe_quiz_result['passed']) ? 'is-passed' : '' ?>" role="status">
                                <strong><?= !empty($fpe_quiz_result['passed']) ? 'Lekcija je uspješno završena.' : 'Pročitaj objašnjenja pa pokušaj ponovno.' ?></strong>
                                <p><?= $fpe_escape($fpe_t('{correct} od {total} točnih odgovora, {score}%.', ['{correct}' => (string) (int) ($fpe_quiz_result['correct'] ?? 0), '{total}' => (string) (int) ($fpe_quiz_result['total'] ?? 0), '{score}' => (string) (int) ($fpe_quiz_result['score'] ?? 0)])) ?></p>
                                <?php if(empty($fpe_quiz_result['valid'])): ?><p>Odgovori na sva pitanja kako bismo mogli provjeriti tvoje znanje.</p><?php endif ?>
                            </div>
                        <?php endif ?>
                        <form method="post" action="<?= $fpe_escape($fpe_link(['section' => 'learn', 'lesson' => $fpe_lesson['id']])) ?>#fpe-quiz-title">
                            <input type="hidden" name="token" value="<?= $fpe_escape(\Altum\Csrf::get()) ?>">
                            <input type="hidden" name="action" value="quiz">
                            <input type="hidden" name="lesson_id" value="<?= $fpe_escape($fpe_lesson['id']) ?>">
                            <?php foreach((array) $fpe_lesson['quiz'] as $question_number => $question): ?>
                                <?php $question_id = (string) ($question['id'] ?? ''); $question_feedback = $fpe_quiz_result['feedback'][$question_id] ?? null; ?>
                                <fieldset class="fpe-question">
                                    <legend><span><?= $fpe_escape($question_number + 1) ?>.</span> <?= $fpe_escape($question['prompt'] ?? '') ?></legend>
                                    <?php foreach((array) ($question['options'] ?? []) as $option_id => $option_label): ?>
                                        <label class="fpe-answer"><input type="radio" name="answers[<?= $fpe_escape($question_id) ?>]" value="<?= $fpe_escape($option_id) ?>" <?= (string) ($fpe_quiz_answers[$question_id] ?? '') === (string) $option_id ? 'checked' : '' ?> required><span><?= $fpe_escape($option_label) ?></span></label>
                                    <?php endforeach ?>
                                    <?php if($question_feedback !== null): ?>
                                        <?php $feedback_text = is_array($question_feedback) ? (string) ($question_feedback['explanation'] ?? $question_feedback['message'] ?? $question['explanation'] ?? '') : (string) $question_feedback; ?>
                                        <?php if($feedback_text !== ''): ?><p class="fpe-question-feedback"><?= $fpe_escape($feedback_text) ?></p><?php endif ?>
                                    <?php endif ?>
                                </fieldset>
                            <?php endforeach ?>
                            <button type="submit" class="fpe-button"><?= $fpe_quiz_result ? 'Ponovno provjeri odgovore' : 'Provjeri odgovore' ?><i class="fas fa-check" aria-hidden="true"></i></button>
                        </form>
                        <?php if(!empty($fpe_quiz_result['passed']) && $fpe_next_lesson && $fpe_next_lesson['id'] !== $fpe_lesson['id']): ?><a class="fpe-next-link" href="<?= $fpe_escape($fpe_link(['section' => 'learn', 'lesson' => $fpe_next_lesson['id']])) ?>">Nastavi na sljedeću lekciju <i class="fas fa-arrow-right" aria-hidden="true"></i></a><?php endif ?>
                    </section>
                <?php endif ?>
            </main>
            <aside class="fpe-study-aside" aria-label="Sadržaj edukacije">
                <div class="fpe-aside-panel"><span class="fpe-eyebrow">Tvoj put učenja</span><h3>Lekcije</h3><ol class="fpe-lesson-navigation"><?php foreach($fpe_lessons as $lesson): ?><li><a href="<?= $fpe_escape($fpe_link(['section' => 'learn', 'lesson' => $lesson['id']])) ?>" <?= $lesson['id'] === $fpe_lesson['id'] ? 'aria-current="page"' : '' ?>><span><?= $fpe_escape($lesson['title']) ?></span><?php if(isset($fpe_completed[$lesson['id']])): ?><i class="fas fa-check-circle" aria-label="Završeno"></i><?php endif ?></a></li><?php endforeach ?></ol></div>
                <?php if(!empty($fpe_lesson['product_keys'])): ?><div class="fpe-aside-panel"><span class="fpe-eyebrow">Poveži sa sastavom</span><h3>Proizvodi u lekciji</h3><?php foreach((array) $fpe_lesson['product_keys'] as $product_key): ?><?php $related_product = $fpe_product_index[$product_key] ?? null; if(!$related_product) continue; ?><a class="fpe-related-link" href="<?= $fpe_escape($fpe_link(['section' => 'products', 'product' => $product_key])) ?>"><?= $fpe_escape($related_product['name']) ?></a><?php endforeach ?></div><?php endif ?>
            </aside>
        </div>
    <?php elseif($fpe_product): ?>
        <?php $fpe_product_key = (string) ($fpe_product['product_key'] ?? ''); $fpe_product_verified = !empty($fpe_product['catalog_verified']); $fpe_is_favorite = in_array($fpe_product_key, $fpe_favorites, true); $product_depth = (array) ($fpe_product['learning_depth'] ?? []); $reading_labels = (array) ($product_depth['reading_labels'] ?? []); ?>
        <div class="fpe-detail-layout">
            <main class="fpe-reading-panel">
                <a class="fpe-back-link" href="<?= $fpe_escape($fpe_link(['section' => 'products'])) ?>"><i class="fas fa-arrow-left" aria-hidden="true"></i> Svi proizvodi</a>
                <div class="fpe-product-introduction">
                    <div><div class="fpe-card-topline"><span class="fpe-eyebrow"><?= $fpe_escape($fpe_product['topic'] ?? $fpe_product['category'] ?? 'Proizvod') ?></span><?php $fpe_scope_badge($fpe_product); ?></div><h2><?= $fpe_escape($fpe_product['name'] ?? $fpe_product_key) ?></h2><h3><?= $fpe_escape($reading_labels['intro'] ?? 'Kome odgovara ovaj proizvod') ?></h3><p><?= $fpe_escape($fpe_product['practical_learning_intro'] ?? $fpe_product['learning_intro'] ?? 'Nauči u kojim se situacijama ovaj proizvod koristi i kako se povezuje s drugim proizvodima.') ?></p></div>
                    <?php $product_image_url = $fpe_image_url($fpe_product['image_url'] ?? ''); ?>
                    <figure class="fpe-detail-photo"><?php if($product_image_url !== ''): ?><img src="<?= $fpe_escape($product_image_url) ?>" alt="<?= $fpe_escape($fpe_product['image_alt'] ?? $fpe_product['name'] ?? '') ?>" width="<?= $fpe_escape(max(1, (int) ($fpe_product['image_width'] ?? 600))) ?>" height="<?= $fpe_escape(max(1, (int) ($fpe_product['image_height'] ?? 600))) ?>" decoding="async"><?php if(!empty($fpe_product['image_caption'])): ?><figcaption><?= $fpe_escape($fpe_product['image_caption']) ?></figcaption><?php endif ?><?php else: ?><p><?= $fpe_escape($fpe_product['photo_note'] ?? 'Fotografija povijesnog pakiranja nije dostupna.') ?></p><?php endif ?></figure>
                </div>
                <?php if(!empty($fpe_product['author_recommendations'])): ?><p class="fpe-origin-note" data-fpe-product-origin>Izvorne preporuke za ovaj proizvod temelje se na zabilježenim iskustvima Forever korisnika iz dostavljenih materijala. Dodatne primjene imaju zasebno navedene izvore.</p><?php endif ?>
                <?php if(!empty($fpe_product['availability_note'])): ?><div class="fpe-author-note"><span class="fpe-badge"><?= ($fpe_product['availability_status'] ?? '') === 'discontinued' ? 'Više nije u ponudi' : 'Identitet proizvoda' ?></span><p><?= $fpe_escape($fpe_product['availability_note']) ?></p></div><?php endif ?>
                <?php if(!empty($fpe_product['replacement_product_key'])): ?><div class="fpe-product-update"><h3>Aktualni proizvod za ovu temu</h3><p><?= $fpe_escape($fpe_product['replacement_note'] ?? '') ?></p><?php $fpe_product_reference((string) $fpe_product['replacement_product_key']); ?></div><?php endif ?>
                <?php if($product_depth): ?>
                    <section class="fpe-profile-section fpe-product-depth" id="fpe-product-ingredients" aria-labelledby="fpe-depth-title">
                        <span class="fpe-eyebrow">Sastav i prednosti proizvoda</span>
                        <h3 id="fpe-depth-title"><?= $fpe_escape($reading_labels['depth'] ?? 'Što ovaj proizvod izdvaja') ?></h3>
                        <?php if(!empty($product_depth['key_factors'])): ?><div class="fpe-key-factor-grid" aria-label="Ključne razlike proizvoda"><?php foreach((array) $product_depth['key_factors'] as $factor): ?><article class="fpe-key-factor"><h4><?= $fpe_escape($factor['title'] ?? '') ?></h4><p><?= $fpe_escape($factor['explanation'] ?? '') ?></p><?php if(!empty($factor['source_ids'])): ?><details class="fpe-source-disclosure"><summary>Izvor informacije</summary><?php $fpe_source_links((array) $factor['source_ids']); ?></details><?php endif ?></article><?php endforeach ?></div><?php endif ?>
                        <h4><?= $fpe_escape($reading_labels['identity'] ?? 'Upoznaj sastav i primjenu') ?></h4>
                        <p><?= $fpe_escape($product_depth['identity_story'] ?? '') ?></p>
                        <div class="fpe-distinctive-fact"><span><?= $fpe_escape($reading_labels['distinctive'] ?? 'Zanimljivost o proizvodu') ?></span><p><?= $fpe_escape($product_depth['distinctive_fact'] ?? '') ?></p></div>
                        <h4><?= $fpe_escape($reading_labels['audience'] ?? 'Kome ga preporučiti i zašto') ?></h4>
                        <p><?= $fpe_escape($product_depth['why_this_product'] ?? '') ?></p>
                        <?php if(!empty($product_depth['ingredients'])): ?>
                            <h4><?= $fpe_escape($reading_labels['ingredients'] ?? (($fpe_product['category_key'] ?? '') === 'combo-paks' ? 'Što donosi svaki proizvod u paketu' : 'Ključni sastojci i što donose')) ?></h4>
                            <div class="fpe-component-grid fpe-depth-ingredients">
                                <?php foreach((array) $product_depth['ingredients'] as $ingredient): ?><article class="fpe-component fpe-depth-ingredient">
                                    <h5><?= $fpe_escape($ingredient['name'] ?? '') ?></h5>
                                    <p><?= $fpe_escape($ingredient['how_it_works'] ?? '') ?></p>
                                    <p class="fpe-ingredient-application"><?= $fpe_escape($ingredient['why_it_matters'] ?? '') ?></p>
                                    <?php if(!empty($ingredient['source_ids'])): ?><details class="fpe-source-disclosure"><summary>Pročitaj više o sastojku</summary><?php $fpe_source_links((array) $ingredient['source_ids']); ?></details><?php endif ?>
                                </article><?php endforeach ?>
                            </div>
                        <?php endif ?>
                        <?php if(!empty($product_depth['source_ids'])): ?><details class="fpe-source-disclosure"><summary>Više o proizvodu i sastojcima</summary><?php $fpe_source_links((array) $product_depth['source_ids']); ?></details><?php endif ?>
                    </section>
                <?php endif ?>
                <?php if(!empty($product_depth['usage_guide'])) $fpe_package_plans((array) ($fpe_product['package_plans'] ?? []), (array) $product_depth['usage_guide']); ?>
                <?php if(!empty($fpe_product['child_usage'])): $child_usage = (array) $fpe_product['child_usage']; ?><section class="fpe-profile-section fpe-child-usage"><h3><?= $fpe_escape($child_usage['title'] ?? 'Dobna uporaba') ?></h3><p><?= $fpe_escape($child_usage['text'] ?? '') ?></p><div class="fpe-pack-grid"><?php foreach((array) ($child_usage['rows'] ?? []) as $row): ?><article class="fpe-pack-card"><span class="fpe-badge"><?= $fpe_escape($row['age'] ?? '') ?></span><h4><?= $fpe_escape($fpe_t('{quantity} dnevno', ['{quantity}' => $fpe_counted($row['daily_count'], 'tablet')])) ?></h4><p><?= $fpe_escape($fpe_t('Kutija od 120 tableta traje {days} dana. Za 30 dana potrošnja je {consumed}, uz ostatak {remaining}.', ['{days}' => (string) $row['days_per_pack'], '{consumed}' => $fpe_counted($row['consumed_in_30_days'], 'tablet'), '{remaining}' => $fpe_counted($row['remaining_in_30_days'], 'tablet')])) ?></p></article><?php endforeach ?></div><p><?= $fpe_escape($child_usage['pending'] ?? '') ?></p><details class="fpe-source-disclosure"><summary>Izvor dobnih uputa</summary><?php $fpe_source_links((array) ($child_usage['source_ids'] ?? [])); ?></details></section><?php endif ?>
                <?php if(!empty($fpe_product['author_recommendations'])): ?><?php $product_author_entries = (array) $fpe_product['author_recommendations']; ?><section class="fpe-profile-section fpe-product-recommendations" aria-labelledby="fpe-author-product-title"><h3 id="fpe-author-product-title">Preporuke i kombinacije za ovaj proizvod</h3><?php $fpe_author_entries(array_slice($product_author_entries, 0, 3), 'fpe-author-product-preview', true, false); ?><?php if(count($product_author_entries) > 3): ?><details class="fpe-declaration fpe-all-product-recommendations"><summary><?= $fpe_escape($fpe_t('Sve preporuke povezane s ovim proizvodom ({count})', ['{count}' => (string) count($product_author_entries)])) ?></summary><div class="fpe-declaration-content"><?php $fpe_author_entries($product_author_entries, 'fpe-author-product', true); ?></div></details><?php endif ?></section><?php endif ?>
                <?php if(!empty($fpe_product['education_applications'])): ?><section class="fpe-profile-section fpe-current-applications"><span class="fpe-eyebrow">Dodatne preporuke i primjene</span><h3>Primjene za učenje</h3><?php $fpe_author_entries((array) $fpe_product['education_applications'], 'fpe-current-product', true, false); ?></section><?php endif ?>
                <?php if($fpe_is_admin && !empty($fpe_product['draft_proposals'])): ?><section class="fpe-profile-section"><h3>Prijedlozi primjena za ovaj proizvod</h3><p>Pripremljeni su nacrti za uredničku potvrdu.</p><?php foreach((array) $fpe_product['draft_proposals'] as $proposal): ?><a class="fpe-related-link" href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-' . preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $proposal['id'])) ?>"><?= $fpe_escape($proposal['id'] . ': ' . $proposal['title']) ?></a><?php endforeach ?></section><?php elseif(empty($fpe_product['author_recommendations']) && empty($fpe_product['education_applications'])): ?><p class="fpe-muted">Za ovaj proizvod još dopunjavamo preporuke i primjene.<?php if($fpe_is_admin): ?> <a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-coverage') ?>">Otvori pregled dopuna.</a><?php endif ?></p><?php endif ?>
                <?php if($fpe_is_admin && !empty($fpe_product['review_issue_ids'])): ?><p class="fpe-muted"><a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-issues') ?>">Pregled dovršenih dorada i konkretnih otvorenih pitanja.</a></p><?php endif ?>
                <?php $composition_facts = array_values(array_filter((array) ($fpe_product['composition_source_facts'] ?? []), static fn($fact) => is_string($fact) && trim($fact) !== '')); ?>
                <?php if($composition_facts && (!$product_depth || ($fpe_product['composition_source_status'] ?? '') === 'official_source_checked')): ?><section class="fpe-profile-section fpe-composition-source" aria-labelledby="fpe-composition-source-title"><h3 id="fpe-composition-source-title"><?= ($fpe_product['composition_source_status'] ?? '') === 'official_source_checked' ? 'Sastav aktualnog proizvoda' : 'Sastav u edukacijskom materijalu' ?></h3><ol><?php foreach($composition_facts as $composition_fact): ?><li><?= $fpe_escape($composition_fact) ?></li><?php endforeach ?></ol><?php if(($fpe_product['composition_source_status'] ?? '') !== 'official_source_checked'): ?><p class="fpe-muted">Preuzeto iz FCC edukacijskog profila. Aktualnu formulu usporedi s pakiranjem.</p><?php endif ?><?php if(is_string($fpe_product['composition_source_title'] ?? null) && $fpe_product['composition_source_title'] !== ''): ?><p class="fpe-muted"><?= $fpe_escape($fpe_t('Izvor: {title}', ['{title}' => (string) $fpe_product['composition_source_title']])) ?></p><?php endif ?><?php $composition_date = $fpe_date($fpe_product['composition_source_metadata']['checked_at'] ?? $fpe_product['composition_source_metadata']['snapshot_date'] ?? ''); ?><?php if($composition_date !== ''): ?><p class="fpe-muted"><?= $fpe_escape($fpe_t('Snimka izvora od {date}', ['{date}' => $composition_date])) ?></p><?php endif ?></section><?php endif ?>
                <?php if(!empty($fpe_product['active_components'])): ?>
                    <?php if($product_depth): ?><details class="fpe-declaration fpe-additional-components"><summary>Dodatni podaci o sastavu i istraživanjima</summary><?php endif ?>
                    <section class="fpe-profile-section" aria-labelledby="fpe-components-title"><span class="fpe-eyebrow">Od sastava do razumijevanja</span><h3 id="fpe-components-title">Ključni sastojci i njihova uloga</h3><div class="fpe-component-grid">
                        <?php foreach((array) $fpe_product['active_components'] as $component): ?><article class="fpe-component"><h4><?= $fpe_escape($component['name'] ?? '') ?></h4><p><?= $fpe_escape($component['explanation'] ?? '') ?></p><?php if(!empty($component['evidence']) || !empty($component['source_ids'])): ?><details class="fpe-source-disclosure"><summary>Istraživanja i izvori</summary><?php if(!empty($component['evidence'])): ?><p class="fpe-research-note"><?= $fpe_escape($component['evidence']) ?></p><?php endif ?><?php $fpe_source_links((array) ($component['source_ids'] ?? [])); ?></details><?php endif ?></article><?php endforeach ?>
                    </div></section>
                    <?php if($product_depth): ?></details><?php endif ?>
                <?php endif ?>
                <?php if(empty($fpe_product['practical_learning_intro']) && !empty($fpe_product['use_cases'])): ?>
                    <details class="fpe-declaration fpe-education-details"><summary>Dodatna edukacija o potrebama i istraživanjima</summary><div class="fpe-declaration-content">
                        <?php foreach((array) $fpe_product['use_cases'] as $case_number => $case): ?><article class="fpe-use-case"><div class="fpe-case-heading"><span><?= $fpe_escape($case_number + 1) ?></span><h4><?= $fpe_escape($case['title'] ?? '') ?></h4></div><?php if(!empty($case['context'])): ?><p><?= $fpe_escape($case['context']) ?></p><?php endif ?><?php if(!empty($case['role'])): ?><div class="fpe-case-role"><h5>Kako razmotriti ovaj proizvod</h5><p><?= $fpe_escape($case['role']) ?></p></div><?php endif ?><?php if(!empty($case['evidence']) || !empty($case['boundary']) || !empty($case['when_refer']) || !empty($case['source_ids'])): ?><details class="fpe-source-disclosure fpe-professional-notes"><summary>Stručne bilješke</summary><?php if(!empty($case['evidence'])): ?><p><?= $fpe_escape($case['evidence']) ?></p><?php endif ?><?php if(!empty($case['boundary'])): ?><p><?= $fpe_escape($case['boundary']) ?></p><?php endif ?><?php if(!empty($case['when_refer'])): ?><p><strong>Kada uključiti stručnjaka:</strong> <?= $fpe_escape($case['when_refer']) ?></p><?php endif ?><?php $fpe_source_links((array) ($case['source_ids'] ?? [])); ?></details><?php endif ?><?php if(!empty($case['questions'])): ?><details class="fpe-case-questions"><summary>Pitanja koja produbljuju razumijevanje</summary><ol><?php foreach((array) $case['questions'] as $question): ?><li><?= $fpe_escape($question) ?></li><?php endforeach ?></ol></details><?php endif ?></article><?php endforeach ?>
                    </div></details>
                <?php endif ?>
                <?php if(!empty($fpe_product['package_plans']) && empty($product_depth['usage_guide'])) $fpe_package_plans((array) $fpe_product['package_plans']); ?>
                <?php if(!empty($product_depth['usage_note'])): ?><section class="fpe-product-usage-note" aria-label="Važno uz uporabu"><h3>Važno uz uporabu</h3><p><?= $fpe_escape($product_depth['usage_note']) ?></p></section><?php endif ?>
                <?php $product_conversation_example = (string) ($fpe_product['practical_conversation_example'] ?? $fpe_product['conversation_example'] ?? ''); if($product_conversation_example !== ''): ?><section class="fpe-conversation-example" id="fpe-product-conversation"><span class="fpe-eyebrow">Primjer razgovora s kupcem</span><h3>Kako preporučiti ovaj proizvod</h3><blockquote><?php $fpe_conversation_paragraphs($product_conversation_example); ?></blockquote></section><?php endif ?>
                <?php $product_takeaways = (array) ($fpe_product['practical_takeaways'] ?? $fpe_product['takeaways'] ?? []); if($product_takeaways): ?><section class="fpe-takeaways"><h3>Zapamti o ovom proizvodu</h3><ol><?php foreach($product_takeaways as $takeaway): ?><li><?= $fpe_escape($takeaway) ?></li><?php endforeach ?></ol></section><?php endif ?>
                <?php $product_study_questions = (array) ($fpe_product['practical_study_questions'] ?? $fpe_product['study_questions'] ?? []); if($product_study_questions): ?><section class="fpe-takeaways fpe-product-questions" id="fpe-product-questions"><h3>Provjeri razumiješ li preporuku</h3><ol><?php foreach($product_study_questions as $study_question): ?><li><?= $fpe_escape($study_question) ?></li><?php endforeach ?></ol></section><?php endif ?>
                <?php if(!empty($fpe_product['source_verified_at'])): ?><p class="fpe-muted"><?= $fpe_escape($fpe_t('Edukativni zapis pregledan {date} Detaljni izvori nalaze se uz sastojke i plan pakiranja.', ['{date}' => $fpe_date($fpe_product['source_verified_at'])])) ?></p><?php endif ?>
                <details class="fpe-declaration"><summary>Službena deklaracija, uporaba i upozorenja</summary><div class="fpe-declaration-content">
                    <p class="fpe-product-status"><span class="fpe-status-dot <?= $fpe_product_verified ? 'is-verified' : '' ?>" aria-hidden="true"></span><?= $fpe_product_verified ? 'Službeni podaci za učenje' : 'Aktualna deklaracija još nije potvrđena u ovoj kartici.' ?></p>
                    <?php foreach(['ingredient_facts' => 'Sastav prema deklaraciji', 'approved_support_facts' => 'Činjenice iz pregledanog izvora', 'usage_facts' => 'Uporaba prema deklaraciji', 'safety_notes' => 'Upozorenja i ograničenja'] as $fact_key => $fact_title): ?>
                        <?php $facts = $fpe_product_verified ? (array) ($fpe_product[$fact_key] ?? []) : []; if(!$facts) continue; ?>
                        <section class="fpe-lesson-section"><h3><?= $fpe_escape($fact_title) ?></h3><ol><?php foreach($facts as $fact): ?><li><?= $fpe_escape($fact) ?></li><?php endforeach ?></ol></section>
                    <?php endforeach ?>
                    <?php $official_url = $fpe_external_url($fpe_product['official_url'] ?? ''); ?>
                    <?php if($official_url !== ''): ?><a class="fpe-button fpe-button-secondary" href="<?= $fpe_escape($official_url) ?>" target="_blank" rel="noopener noreferrer">Otvori službenu deklaraciju <i class="fas fa-external-link-alt" aria-hidden="true"></i><span class="sr-only">, otvara novu karticu</span></a><?php elseif(!$fpe_product_verified): ?><p class="fpe-muted">Prije razgovora o uporabi provjeri točan sastav i upute na deklaraciji proizvoda.</p><?php endif ?>
                </div></details>
                <?php
                $product_source_ids = array_values(array_filter((array) ($fpe_product['source_ids'] ?? []), static fn($source_id) => isset($fpe_sources[$source_id]) && ($fpe_external_url($fpe_sources[$source_id]['url'] ?? '') !== '' || !empty($fpe_sources[$source_id]['path']))));
                $source_refs = array_values(array_filter((array) ($fpe_product['source_refs'] ?? []), static fn($source_ref) => is_array($source_ref) && $fpe_external_url($source_ref['source_url'] ?? $source_ref['url'] ?? '') !== ''));
                ?>
                <?php if($product_source_ids || $source_refs): ?><details class="fpe-declaration"><summary>Izvori i podaci za daljnje proučavanje</summary><div class="fpe-declaration-content"><?php $fpe_source_links($product_source_ids); ?><?php if($source_refs): ?><div class="fpe-sources"><h3>Izvori deklaracije proizvoda</h3><ol><?php foreach($source_refs as $source_ref): ?><?php $ref_url = $fpe_external_url($source_ref['source_url'] ?? $source_ref['url'] ?? ''); ?><li><a href="<?= $fpe_escape($ref_url) ?>" target="_blank" rel="noopener noreferrer"><?= $fpe_escape($fpe_source_title($source_ref['title'] ?? 'Službeni izvor za proizvod')) ?><span class="sr-only">, otvara novu karticu</span></a><?php $ref_date = $fpe_date($source_ref['accessed_at'] ?? $source_ref['reviewed_at'] ?? ''); ?><?php if($ref_date !== ''): ?><span class="fpe-source-date"><?= $fpe_escape($fpe_t('Provjereno {date}', ['{date}' => $ref_date])) ?></span><?php endif ?></li><?php endforeach ?></ol></div><?php endif ?></div></details><?php endif ?>
            </main>
            <aside class="fpe-study-aside" aria-label="Povezano učenje">
                <div class="fpe-aside-panel"><span class="fpe-eyebrow">Tvoja zbirka</span><h3>Sačuvaj za ponavljanje</h3><p>Favoriti ti olakšavaju povratak na proizvode koje upravo proučavaš.</p><form method="post" action="<?= $fpe_escape($fpe_link(['section' => 'products', 'product' => $fpe_product_key])) ?>"><input type="hidden" name="token" value="<?= $fpe_escape(\Altum\Csrf::get()) ?>"><input type="hidden" name="action" value="favorite"><input type="hidden" name="product_key" value="<?= $fpe_escape($fpe_product_key) ?>"><button type="submit" class="fpe-button fpe-button-secondary" aria-pressed="<?= $fpe_is_favorite ? 'true' : 'false' ?>"><i class="<?= $fpe_is_favorite ? 'fas' : 'far' ?> fa-bookmark" aria-hidden="true"></i> <?= $fpe_is_favorite ? 'Ukloni iz favorita' : 'Spremi u favorite' ?></button></form></div>
                <?php $share_information_url = $fpe_public_url($fpe_product['share_information_url'] ?? ''); $shop_share_url = $fpe_public_url($fpe_product['shop_share_url'] ?? ''); ?>
                <section class="fpe-aside-panel fpe-sharing" aria-labelledby="fpe-sharing-title">
                    <span class="fpe-eyebrow">Za tvoj razgovor</span><h3 id="fpe-sharing-title">Javne informacije</h3>
                    <?php if($share_information_url !== ''): ?>
                        <p>Podijeli javni članak o proizvodu. Tvoj napredak, kvizovi i interni primjeri ostaju u edukaciji.</p>
                        <div class="fpe-share-actions">
                            <a class="fpe-button fpe-button-secondary" href="<?= $fpe_escape($share_information_url) ?>" target="_blank" rel="noopener noreferrer">Otvori informacije <i class="fas fa-external-link-alt" aria-hidden="true"></i><span class="sr-only">, otvara novu karticu</span></a>
                            <button class="fpe-button fpe-button-secondary" type="button" data-fpe-copy="<?= $fpe_escape($share_information_url) ?>"><i class="far fa-copy" aria-hidden="true"></i> Kopiraj poveznicu</button>
                            <button class="fpe-button fpe-button-secondary" type="button" data-fpe-share="<?= $fpe_escape($share_information_url) ?>" data-fpe-share-title="<?= $fpe_escape($fpe_product['name'] ?? '') ?>" hidden><i class="fas fa-share-alt" aria-hidden="true"></i> Podijeli informacije</button>
                        </div>
                        <label class="fpe-share-url-label" for="fpe-public-product-url">Javna poveznica za dijeljenje</label><input class="fpe-share-url" id="fpe-public-product-url" type="url" value="<?= $fpe_escape($share_information_url) ?>" readonly>
                        <p class="fpe-share-message" id="fpe-share-message" role="status" aria-live="polite"></p>
                    <?php else: ?>
                        <p>Javna poveznica za ovaj proizvod još nije potvrđena. Koristi karticu za učenje, a informacije za dijeljenje provjeri sa sponzorom.</p>
                    <?php endif ?>
                    <?php if($shop_share_url !== ''): ?><a class="fpe-button" href="<?= $fpe_escape($shop_share_url) ?>" target="_blank" rel="noopener noreferrer">Ponuda i kupnja <i class="fas fa-external-link-alt" aria-hidden="true"></i><span class="sr-only">, otvara novu karticu</span></a><p class="fpe-share-caption">Na javnom članku odabire se tržište i otvara pripadajuća ponuda.</p><?php endif ?>
                </section>
                <?php if($fpe_is_admin && (!empty($fpe_product['draft_proposals']) || !empty($fpe_product['review_issue_ids']))): ?><section class="fpe-aside-panel"><span class="fpe-eyebrow">Daljnje uređivanje</span><h3>Prijedlozi za ovaj proizvod</h3><?php if(!empty($fpe_product['draft_proposals'])): ?><p><?= $fpe_escape($fpe_t('{count} nacrta čeka uredničku potvrdu.', ['{count}' => (string) count((array) $fpe_product['draft_proposals'])])) ?></p><?php endif ?><?php if(!empty($fpe_product['review_issue_ids'])): ?><p><?= $fpe_escape($fpe_t('{count} tema traži dodatni pregled izvornika.', ['{count}' => (string) count((array) $fpe_product['review_issue_ids'])])) ?></p><?php endif ?><a class="fpe-text-link" href="<?= $fpe_escape($fpe_link(['section' => 'review'])) ?>">Otvori pregled edukacije <i class="fas fa-arrow-right" aria-hidden="true"></i></a></section><?php endif ?>
                <?php if(!empty($fpe_product['lesson_ids'])): ?><div class="fpe-aside-panel"><span class="fpe-eyebrow">Nauči osnove</span><h3>Povezane lekcije</h3><?php $fpe_related_lessons((array) $fpe_product['lesson_ids']); ?></div><?php endif ?>
            </aside>
        </div>
    <?php elseif($fpe_section === 'learn'): ?>
        <main>
            <div class="fpe-overview-top">
                <section class="fpe-next-panel"><span class="fpe-eyebrow">Uči svojim ritmom</span><h2><?= $fpe_next_lesson ? 'Tvoja sljedeća lekcija' : 'Sve lekcije su završene' ?></h2><p><?= $fpe_escape($fpe_next_lesson['title'] ?? 'Vrati se na lekcije i kartice proizvoda kad ti zatreba ponavljanje.') ?></p><a class="fpe-button" href="<?= $fpe_escape($fpe_link(['section' => 'learn', 'lesson' => $fpe_next_lesson['id'] ?? ($fpe_lessons[0]['id'] ?? '')])) ?>"><?= $fpe_next_lesson ? 'Nastavi učiti' : 'Ponovi gradivo' ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a></section>
                <aside class="fpe-reference-panel"><i class="fas fa-seedling fpe-reference-icon" aria-hidden="true"></i><h2>Znanje koje koristiš u razgovoru</h2><p>Počni s probavom i sastavom. Zatim nauči kako provjeriti proizvod i objasniti ga jasnim, odgovornim riječima.</p><a class="fpe-text-link" href="<?= $fpe_escape($fpe_link(['section' => 'products'])) ?>">Otvori kartice proizvoda <i class="fas fa-arrow-right" aria-hidden="true"></i></a></aside>
            </div>
            <?php /* Custom code: FC-2026-10-11: Starter path with the ten products collaborators share most often. */
            $fpe_starter_config = is_file(APP_PATH . 'config/fcc_product_education_starter.php') ? (require APP_PATH . 'config/fcc_product_education_starter.php') : [];
            $fpe_products_by_key = [];
            foreach($fpe_products as $fpe_listed_product) { if(($fpe_listed_product['education_visibility'] ?? '') !== 'archive') $fpe_products_by_key[(string) ($fpe_listed_product['product_key'] ?? '')] = $fpe_listed_product; }
            $fpe_starter_products = [];
            foreach((array) ($fpe_starter_config['product_keys'] ?? []) as $fpe_starter_key) { if(isset($fpe_products_by_key[$fpe_starter_key])) $fpe_starter_products[] = $fpe_products_by_key[$fpe_starter_key]; }
            if($fpe_starter_products): ?>
            <section class="fpe-starter" id="fpe-starter" aria-labelledby="fpe-starter-title"><div class="fpe-section-heading"><div><span class="fpe-eyebrow">Za početak</span><h2 id="fpe-starter-title"><?= $fpe_escape($fpe_t('Kreni od ovih {count} proizvoda', ['{count}' => (string) count($fpe_starter_products)])) ?></h2><p>Ove proizvode suradnici FCC-a najčešće dijele s kupcima. Kad ih upoznaš, lakše ćeš razumjeti i ostalu ponudu.</p></div></div>
            <?php $fpe_product_cards($fpe_starter_products, false, 'fpe-starter-grid'); ?></section>
            <?php endif; /* /Custom code: FC-2026-10-11 */ ?>
            <div class="fpe-section-heading"><div><span class="fpe-eyebrow">Osnove i primjena</span><h2>Tvoj program učenja</h2></div><span class="fpe-muted"><?= $fpe_escape($fpe_t('{count} lekcije za učenje i ponavljanje', ['{count}' => (string) count($fpe_lessons)])) ?></span></div>
            <div class="fpe-course-grid">
                <?php foreach($fpe_lessons as $lesson_number => $lesson): ?>
                    <?php $lesson_completed = isset($fpe_completed[$lesson['id']]); ?>
                    <article class="fpe-course-card"><div class="fpe-card-topline"><span class="fpe-lesson-number"><?= $fpe_escape(str_pad((string) ($lesson_number + 1), 2, '0', STR_PAD_LEFT)) ?></span><span class="fpe-badge <?= $lesson_completed ? 'is-completed' : '' ?>"><?= $fpe_escape($lesson_completed ? 'Završeno' : $fpe_t('{minutes} min', ['{minutes}' => (string) (int) ($lesson['minutes'] ?? 5)])) ?></span></div><h3><a href="<?= $fpe_escape($fpe_link(['section' => 'learn', 'lesson' => $lesson['id']])) ?>"><?= $fpe_escape($lesson['title']) ?></a></h3><p><?= $fpe_escape($lesson['summary'] ?? '') ?></p><a class="fpe-text-link" href="<?= $fpe_escape($fpe_link(['section' => 'learn', 'lesson' => $lesson['id']])) ?>"><?= $lesson_completed ? 'Ponovi lekciju' : 'Otvori lekciju' ?> <i class="fas fa-arrow-right" aria-hidden="true"></i></a></article>
                <?php endforeach ?>
            </div>
        </main>
    <?php elseif(in_array($fpe_section, ['products', 'favorites'], true)): ?>
        <?php $available_products = $fpe_section === 'favorites' ? array_values(array_filter($fpe_products, static fn($product) => in_array((string) ($product['product_key'] ?? ''), $fpe_favorites, true))) : $fpe_products;
        $visible_products = array_values(array_filter($available_products, static fn(array $product): bool => ($product['education_visibility'] ?? '') !== 'archive'));
        $archived_products = array_values(array_filter($available_products, static fn(array $product): bool => ($product['education_visibility'] ?? '') === 'archive')); ?>
        <?php $product_categories = []; foreach($visible_products as $listed_product) { $category = trim((string) ($listed_product['category'] ?? '')); if($category !== '') $product_categories[$category] = $category; } natcasesort($product_categories); ?>
        <main><div class="fpe-section-heading"><div><span class="fpe-eyebrow">Sastojci, potrebe i primjena znanja</span><h2><?= $fpe_section === 'favorites' ? 'Moji spremljeni proizvodi' : 'Upoznaj proizvode' ?></h2><p><?= $fpe_section === 'favorites' ? 'Tvoja osobna zbirka za učenje i ponavljanje.' : 'Nauči što proizvod sadrži, kome ga preporučiti i kako objasniti njegovu primjenu kupcu.' ?></p><?php if($fpe_section === 'products'): /* Custom code: FC-2026-10-11 */ ?><p><a class="fpe-text-link" href="<?= $fpe_escape($fpe_link(['section' => 'learn']) . '#fpe-starter') ?>">Prvi put ovdje? Kreni od 10 najčešće dijeljenih proizvoda <i class="fas fa-arrow-right" aria-hidden="true"></i></a></p><?php endif /* /Custom code: FC-2026-10-11 */ ?></div></div>
            <?php if($visible_products): ?><div class="fpe-product-filters"><div class="fpe-search"><label for="fpe-product-search">Pronađi proizvod, sastojak ili tegobu</label><div><i class="fas fa-search" aria-hidden="true"></i><input type="search" id="fpe-product-search" placeholder="Na primjer: gastritis, aloe, vlakna" autocomplete="off"></div></div><?php if($product_categories): ?><div class="fpe-category-filter"><label for="fpe-product-category">Kategorija proizvoda</label><select id="fpe-product-category" data-is-not-custom-select><option value="">Sve kategorije</option><?php foreach($product_categories as $category): ?><option value="<?= $fpe_escape($category) ?>"><?= $fpe_escape($category) ?></option><?php endforeach ?></select></div><?php endif ?></div><p id="fpe-search-count" class="fpe-muted" aria-live="polite"><?= $fpe_escape($fpe_t('{count} proizvoda za učenje', ['{count}' => (string) count($visible_products)])) ?></p><?php $fpe_product_cards($visible_products, $fpe_section === 'favorites'); ?><p class="fpe-empty" id="fpe-search-empty" hidden>Nema proizvoda za ovaj pojam i odabranu kategoriju. Pokušaj drugim nazivom ili odaberi sve kategorije.</p><button class="fpe-button fpe-button-secondary fpe-product-more" type="button" id="fpe-product-more" hidden>Prikaži više proizvoda</button>
            <?php else: ?><div class="fpe-empty"><i class="far fa-bookmark" aria-hidden="true"></i><h3>Izgradi svoju zbirku za učenje</h3><p>Spremi proizvode koje želiš bolje upoznati. Ovdje ćeš ih lako pronaći za ponavljanje.</p><a class="fpe-button" href="<?= $fpe_escape($fpe_link(['section' => 'products'])) ?>">Pregledaj proizvode</a></div><?php endif ?>
            <?php if(!empty($data->catalog_notice)): ?><details class="fpe-declaration"><summary>O podacima iz službenih deklaracija</summary><div class="fpe-declaration-content"><p class="fpe-muted"><?= $fpe_escape($data->catalog_notice) ?></p></div></details><?php endif ?>
            <?php if($archived_products): ?><details class="fpe-declaration"><summary><?= $fpe_escape($fpe_t('Povijesni proizvodi i ranije preporuke ({count})', ['{count}' => (string) count($archived_products)])) ?></summary><div class="fpe-declaration-content"><p>Ovi zapisi čuvaju nazive i kombinacije iz ranijih materijala. Dostupnost i aktualna zamjena navedene su uz karticu.</p><?php $fpe_product_cards($archived_products, $fpe_section === 'favorites', 'fpe-archived-product-grid'); ?></div></details><?php endif ?>
        </main>
    <?php elseif(in_array($fpe_section, ['needs', 'routines'], true)): ?>
        <?php $browse_entries = $fpe_section === 'needs' ? $fpe_recommendation_entries : array_values(array_filter($fpe_recommendation_entries, static fn($entry) => count((array) ($entry['products'] ?? [])) > 1)); ?>
        <?php if($fpe_selected_entry): $fpe_recommendation_detail($fpe_selected_entry, $browse_entries, $fpe_section); else: ?>
            <main><div class="fpe-section-heading"><div><span class="fpe-eyebrow"><?= $fpe_section === 'needs' ? 'Problemi i preporuke proizvoda' : 'Primjena povezanih proizvoda' ?></span><h2><?= $fpe_section === 'needs' ? 'Preporuke prema problemu' : 'Preporučene kombinacije proizvoda' ?></h2><p><?= $fpe_section === 'needs' ? 'Pronađi preporuku povezanu s određenim problemom ili potrebom. Otvori je i saznaj kome je namijenjena, zašto su proizvodi odabrani i kako to objasniti kupcu.' : 'Pronađi proizvode koji se nadopunjuju, usporedi različite izbore i upoznaj redoslijed primjene. Otvori preporuku za objašnjenje sastojaka i primjer razgovora s kupcem.' ?></p></div></div><details class="fpe-browser-intro"><summary>O ovoj edukaciji</summary><?php $fpe_author_note(); ?></details><?php if($browse_entries): $fpe_recommendation_browser($browse_entries, $fpe_section, $fpe_section === 'needs' ? 'fpe-author-problems' : 'fpe-author-combinations'); else: ?><p class="fpe-muted">Preporuke trenutačno nisu dostupne.</p><?php endif ?></main>
        <?php endif ?>
    <?php elseif($fpe_section === 'review' && $fpe_is_admin): ?>
        <?php
        $coverage_summary = (array) ($fpe_coverage['summary'] ?? []);
        $review_issues = array_values(array_filter((array) ($fpe_editorial_review['issues'] ?? []), static fn(array $issue): bool => ($issue['status'] ?? '') !== 'withdrawn_by_author'));
        $resolved_issues = array_values(array_filter($review_issues, static fn(array $issue): bool => ($issue['status'] ?? '') === 'resolved_education'));
        $pending_issues = array_values(array_filter($review_issues, static fn(array $issue): bool => ($issue['status'] ?? '') !== 'resolved_education'));
        $review_proposals = (array) ($fpe_editorial_review['proposals'] ?? []);
        $missing_topics = (array) ($fpe_editorial_review['missing_topics'] ?? []);
        $completed_topics = (array) ($fpe_editorial_review['completed_topics'] ?? []);
        $unmapped_products = (array) ($fpe_coverage['unmapped_products'] ?? []);
        $without_applications = (array) ($fpe_coverage['products_without_applications'] ?? []);
        $unresolved_references = (array) ($fpe_coverage['unresolved_references'] ?? []);
        $missing_photos = (array) ($fpe_coverage['missing_photos'] ?? []);
        $review_metrics = ['product_count' => 'Proizvodi u gradivu', 'refined_scenario_count' => 'Scenariji s praktičnim vodičem', 'resolved_issue_count' => 'Dovršene dorade', 'uncertain_issue_count' => 'Konkretna otvorena pitanja', 'published_application_count' => 'Dodatne obrađene primjene', 'products_without_application_count' => 'Proizvodi za dopunu primjena', 'unresolved_reference_count' => 'Nazivi za razjašnjenje', 'missing_photo_count' => 'Fotografije za dopunu'];
        $severity_labels = ['critical' => 'Prioritet za pregled', 'high' => 'Prioritet za pregled', 'medium' => 'Dodatna provjera', 'low' => 'Urednička dorada'];
        $evidence_labels = ['hypothesis' => 'Radna pretpostavka', 'conceptual' => 'Obrazloženje za raspravu', 'ingredient_level' => 'Podaci o sastojcima', 'ingredient_evidence' => 'Podaci o sastojcima', 'limited' => 'Ograničeni podaci', 'preliminary' => 'Početni podaci', 'needs_review' => 'Potrebna provjera izvora'];
        $review_anchor = static fn($value): string => 'fpe-review-' . preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $value);
        ?>
        <main class="fpe-review">
            <div class="fpe-section-heading"><div><span class="fpe-eyebrow">Potpunost gradiva</span><h2>Pregled gradiva i potvrđenih dorada</h2><p>Pregled povezanih proizvoda, obrađenih primjena, deklariranih količina i objavljenih lekcija.</p></div></div>
            <div class="fpe-review-metrics">
                <?php foreach($review_metrics as $metric_key => $metric_label): ?><div class="fpe-review-metric"><strong><?= $fpe_escape(array_key_exists($metric_key, $coverage_summary) ? max(0, (int) $coverage_summary[$metric_key]) : 'Još nije prebrojeno') ?></strong><span><?= $fpe_escape($metric_label) ?></span></div><?php endforeach ?>
            </div>
            <nav class="fpe-review-jump" aria-label="Sadržaj pregleda"><a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-issues') ?>">Otvorena pitanja <span><?= $fpe_escape(count($pending_issues)) ?></span></a><a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-resolved') ?>">Dovršene dorade <span><?= $fpe_escape(count($resolved_issues)) ?></span></a><a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-proposals') ?>">Obrađene primjene <span><?= $fpe_escape(count($review_proposals)) ?></span></a><a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-coverage') ?>">Dopuna zbirke</a><a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-topics') ?>">Nove lekcije <span><?= $fpe_escape(count($completed_topics)) ?></span></a><a href="<?= $fpe_escape($fpe_link(['section' => 'review']) . '#fpe-review-packaging') ?>">Količine za 30 dana</a></nav>
            <section class="fpe-review-section" aria-labelledby="fpe-review-issues">
                <div class="fpe-section-heading"><div><span class="fpe-eyebrow">Završna provjera</span><h2 id="fpe-review-issues"><?= $pending_issues ? 'Što još treba konkretno razjasniti' : 'Sve dogovorene dorade su obrađene' ?></h2><?php if($pending_issues): ?><p>Ovdje su točno navedena preostala pitanja za doradu.</p><?php else: ?><p>Izvorne bilješke ostaju u povijesti dovršenih dorada.</p><?php endif ?></div></div>
                <?php if(!$pending_issues): ?><p>Sve evidentirane dorade edukativnog prikaza su dovršene.</p><?php endif ?>
                <?php foreach([['items' => $pending_issues, 'resolved' => false], ['items' => $resolved_issues, 'resolved' => true]] as $review_group): if(!$review_group['items']) continue; ?>
                <?php if($review_group['resolved']): ?><details class="fpe-declaration fpe-resolved-issues" id="fpe-review-resolved"><summary><?= $fpe_escape($fpe_t('Dovršene dorade gradiva ({count})', ['{count}' => (string) count($resolved_issues)])) ?></summary><div class="fpe-declaration-content"><?php endif ?>
                <div class="fpe-review-grid">
                    <?php foreach($review_group['items'] as $issue): ?>
                        <article class="fpe-review-card" id="<?= $fpe_escape($review_anchor($issue['id'] ?? '')) ?>">
                            <div class="fpe-review-card-top"><span class="fpe-review-id">ID: <?= $fpe_escape($issue['id'] ?? '') ?></span><span class="fpe-badge <?= $review_group['resolved'] ? 'fpe-ready-badge' : '' ?>"><?= $review_group['resolved'] ? 'Dorađeno u edukaciji' : 'Izdvojeno konkretno pitanje' ?></span></div>
                            <h3><?= $fpe_escape($issue['title'] ?? 'Zapis za pregled') ?></h3>
                            <?php if(!empty($issue['original_mapping'])): ?><div class="fpe-review-products"><h4>Proizvodi izvornog zapisa</h4><div class="fpe-author-product-list"><?php foreach((array) $issue['original_mapping'] as $original_product): ?><?php $fpe_author_product((array) $original_product); ?><?php endforeach ?></div></div><?php endif ?>
                            <?php if(!empty($issue['resolution'])): ?><div class="fpe-review-copy"><h4>Što je sada napravljeno</h4><p><?= $fpe_escape($issue['resolution']) ?></p></div><?php endif ?>
                            <?php if(!empty($issue['remaining_question'])): ?><div class="fpe-review-copy fpe-open-question"><h4>Preostalo pitanje</h4><p><?= $fpe_escape($issue['remaining_question']) ?></p></div><?php endif ?>
                            <?php if(!empty($issue['current_wording'])): ?><div class="fpe-review-wording"><h4>Tekst u edukaciji</h4><blockquote><?= $fpe_escape($issue['current_wording']) ?></blockquote></div><?php endif ?>
                            <details class="fpe-source-disclosure"><summary>Ranija bilješka i trag izmjene</summary><?php foreach(['what_is_disputed' => 'Ranije izdvojeno', 'suggested_resolution' => 'Raniji prijedlog'] as $field => $heading): if(empty($issue[$field])) continue; ?><h4><?= $fpe_escape($heading) ?></h4><p><?= $fpe_escape($issue[$field]) ?></p><?php endforeach ?></details>
                            <?php $fpe_entry_references((array) ($issue['entry_ids'] ?? [])); ?>
                            <?php if(!empty($issue['source_ids'])): ?><details class="fpe-source-disclosure"><summary>Izvori za pregled</summary><?php $fpe_source_links((array) $issue['source_ids']); ?></details><?php endif ?>
                        </article>
                    <?php endforeach ?>
                </div><?php if($review_group['resolved']): ?></div></details><?php endif ?><?php endforeach ?>
            </section>
            <section class="fpe-review-section" aria-labelledby="fpe-review-proposals">
                <div class="fpe-section-heading"><div><span class="fpe-eyebrow">Potvrđeno i dostupno u edukaciji</span><h2 id="fpe-review-proposals">Obrađene primjene proizvoda</h2><p>Sve pripremljene primjene potvrđene su za edukaciju. Otvori ih na karticama proizvoda i u pregledu prema problemu. ID ostaje dostupan za buduće dopune.</p></div></div>
                <?php if($review_proposals): ?><div class="fpe-review-grid">
                    <?php foreach($review_proposals as $proposal): ?>
                        <article class="fpe-review-card fpe-proposal-card" id="<?= $fpe_escape($review_anchor($proposal['id'] ?? '')) ?>">
                            <div class="fpe-review-card-top"><span class="fpe-review-id">ID: <?= $fpe_escape($proposal['id'] ?? '') ?></span><span class="fpe-badge <?= ($proposal['status'] ?? '') === 'education_ready' ? 'fpe-ready-badge' : 'fpe-draft-badge' ?>"><?= ($proposal['status'] ?? '') === 'education_ready' ? 'U edukaciji' : 'Nacrt za doradu' ?></span></div>
                            <h3><?= $fpe_escape($proposal['title'] ?? 'Prijedlog za edukaciju') ?></h3>
                            <?php if(!empty($proposal['problem'])): ?><p class="fpe-review-problem"><?= $fpe_escape($proposal['problem']) ?></p><?php endif ?>
                            <?php if(!empty($proposal['primary_product_key'])): ?><div class="fpe-review-products"><h4>Glavni proizvodi za proučavanje</h4><?php foreach((array) ($proposal['primary_product_keys'] ?? [$proposal['primary_product_key']]) as $primary_key) $fpe_product_reference((string) $primary_key); ?></div><?php endif ?>
                            <?php if(!empty($proposal['support_product_keys'])): ?><div class="fpe-review-products"><h4>Proizvodi za razmatranje u kombinaciji</h4><?php foreach((array) $proposal['support_product_keys'] as $proposal_product_key) $fpe_product_reference((string) $proposal_product_key); ?></div><?php endif ?>
                            <?php if(!empty($proposal['rationale'])): ?><div class="fpe-review-copy"><h4>Zašto razmatramo ovu primjenu</h4><p><?= $fpe_escape($proposal['rationale']) ?></p></div><?php endif ?>
                            <?php if(!empty($proposal['suggested_wording'])): ?><div class="fpe-review-wording"><h4>Edukativni tekst</h4><blockquote><?= $fpe_escape($proposal['suggested_wording']) ?></blockquote></div><?php endif ?>
                            <?php if(!empty($proposal['required_checks'])): ?><div class="fpe-review-copy"><h4>Što proučiti pri osobnom odabiru</h4><ol><?php foreach((array) $proposal['required_checks'] as $required_check): ?><li><?= $fpe_escape($required_check) ?></li><?php endforeach ?></ol></div><?php endif ?>
                            <?php if(!empty($proposal['evidence_level'])): ?><p class="fpe-review-evidence"><strong>Podloga prijedloga:</strong> <?= $fpe_escape($evidence_labels[(string) $proposal['evidence_level']] ?? $proposal['evidence_level']) ?></p><?php endif ?>
                            <?php if(!empty($proposal['source_ids'])): ?><details class="fpe-source-disclosure"><summary>Istraživanja i izvori prijedloga</summary><?php $fpe_source_links((array) $proposal['source_ids']); ?></details><?php endif ?>
                        </article>
                    <?php endforeach ?>
                </div><?php else: ?><p class="fpe-muted">Dodatne primjene još nisu pripremljene.</p><?php endif ?>
            </section>
            <section class="fpe-review-section" aria-labelledby="fpe-review-coverage">
                <div class="fpe-section-heading"><div><span class="fpe-eyebrow">Dopuna i povezivanje</span><h2 id="fpe-review-coverage">Što još nedostaje u zbirci</h2></div></div>
                <details class="fpe-declaration"><summary><?= $fpe_escape($fpe_t('Proizvodi kojima još treba povezati primjenu ({count})', ['{count}' => (string) count($without_applications)])) ?></summary><div class="fpe-declaration-content"><p>Nema povezane izvorne preporuke ni nove obrađene primjene. Svaka kartica ostaje dostupna za upoznavanje sastava i namjene.</p><?php foreach($without_applications as $gap): ?><div class="fpe-review-gap"><?php $fpe_product_reference((string) $gap['product_key']); ?><?php if(!empty($gap['possible_use_topic'])): ?><p><?= $fpe_escape($gap['possible_use_topic']) ?></p><?php endif ?></div><?php endforeach ?></div></details>
                <details class="fpe-declaration"><summary><?= $fpe_escape($fpe_t('Proizvodi bez izvorne preporuke ({count})', ['{count}' => (string) count($unmapped_products)])) ?></summary><div class="fpe-declaration-content">
                    <?php if($unmapped_products): ?><div class="fpe-review-grid fpe-review-gap-grid"><?php foreach($unmapped_products as $unmapped_product): ?><article class="fpe-review-gap"><?php if(isset($fpe_product_index[(string) ($unmapped_product['product_key'] ?? '')])): ?><?php $fpe_product_reference((string) $unmapped_product['product_key']); ?><?php else: ?><h3><?= $fpe_escape($unmapped_product['name'] ?? '') ?></h3><?php endif ?><?php if(!empty($unmapped_product['category'])): ?><span class="fpe-eyebrow"><?= $fpe_escape($unmapped_product['category']) ?></span><?php endif ?><?php if(!empty($unmapped_product['reason'])): ?><p><?= $fpe_escape($unmapped_product['reason']) ?></p><?php endif ?><?php if(!empty($unmapped_product['possible_use_topic'])): ?><p><strong>Tema za proučavanje:</strong> <?= $fpe_escape($unmapped_product['possible_use_topic']) ?></p><?php endif ?></article><?php endforeach ?></div><?php else: ?><p class="fpe-muted">Nema izdvojenih proizvoda bez izvorne preporuke.</p><?php endif ?>
                </div></details>
                <details class="fpe-declaration"><summary><?= $fpe_escape($fpe_t('Nazivi proizvoda koje treba razjasniti ({count})', ['{count}' => (string) count($unresolved_references)])) ?></summary><div class="fpe-declaration-content">
                    <?php if($unresolved_references): ?><div class="fpe-review-grid fpe-review-gap-grid"><?php foreach($unresolved_references as $unresolved_reference): ?><article class="fpe-review-gap"><h3><?= $fpe_escape($unresolved_reference['name'] ?? '') ?></h3><?php if(!empty($unresolved_reference['reason'])): ?><p><?= $fpe_escape($unresolved_reference['reason']) ?></p><?php endif ?><?php if(!empty($unresolved_reference['source_product_key'])): ?><?php $fpe_product_reference((string) $unresolved_reference['source_product_key']); ?><?php endif ?><?php $fpe_entry_references((array) ($unresolved_reference['entry_ids'] ?? [])); ?></article><?php endforeach ?></div><?php else: ?><p class="fpe-muted">Nema izdvojenih naziva za razjašnjenje.</p><?php endif ?>
                </div></details>
                <details class="fpe-declaration"><summary><?= $fpe_escape($fpe_t('Fotografije za dopunu ({count})', ['{count}' => (string) count($missing_photos)])) ?></summary><div class="fpe-declaration-content">
                    <?php if($missing_photos): ?><p class="fpe-muted">Za ove proizvode treba povezati fotografiju koja odgovara točnom nazivu i izdanju proizvoda.</p><div class="fpe-review-photo-gaps"><?php foreach($missing_photos as $missing_photo): ?><?php if(isset($fpe_product_index[(string) ($missing_photo['product_key'] ?? '')])): ?><?php $fpe_product_reference((string) $missing_photo['product_key']); ?><?php else: ?><span><?= $fpe_escape($missing_photo['name'] ?? '') ?></span><?php endif ?><?php endforeach ?></div><?php else: ?><p class="fpe-muted">Sve pojedinačne kartice u glavnom katalogu imaju povezanu fotografiju. Kod početnog paketa fotografije proučavaj na zasebnim karticama sastavnih proizvoda prema sadržaju odabrane varijante.</p><?php endif ?>
                </div></details>
                <?php $archived_photos = (array) ($fpe_coverage['archived_missing_photos'] ?? []); if($archived_photos): ?>
                <details class="fpe-declaration"><summary><?= $fpe_escape($fpe_t('Povijesna pakiranja bez potvrđene fotografije ({count})', ['{count}' => (string) count($archived_photos)])) ?></summary><div class="fpe-declaration-content"><p>Ove kartice čuvaju povijesni naziv i izvornu poveznicu. Fotografiju aktualne formule prouči na njezinoj zasebnoj kartici.</p><div class="fpe-review-photo-gaps"><?php foreach($archived_photos as $archived_photo): ?><?php $fpe_product_reference((string) $archived_photo['product_key']); ?><?php endforeach ?></div></div></details>
                <?php endif ?>
            </section>
            <?php $packaging_review = (array) ($fpe_content['packaging_review'] ?? []); if($packaging_review): ?>
            <section class="fpe-review-section" aria-labelledby="fpe-review-packaging">
                <div class="fpe-section-heading"><div><span class="fpe-eyebrow">Pakiranje i deklarirana uporaba</span><h2 id="fpe-review-packaging">Pregled količina za 30 dana</h2><p>Dnevna količina prati uputu proizvoda. Trajanje pakiranja i potrebe za 30 dana računaju se iz te količine.</p></div></div>
                <p><?= $fpe_escape($fpe_t('{count} proizvoda ima brojčani obračun iz pregledane dnevne upute.', ['{count}' => (string) count((array) ($packaging_review['calculated'] ?? []))])) ?></p>
                <details class="fpe-declaration"><summary><?= $fpe_escape($fpe_t('Potvrđena uporaba prema deklaraciji i stvarno trajanje pakiranja ({count})', ['{count}' => (string) count((array) ($packaging_review['differences'] ?? []))])) ?></summary><div class="fpe-declaration-content"><div class="fpe-review-grid"><?php foreach((array) ($packaging_review['differences'] ?? []) as $pack_product): ?><article class="fpe-review-gap"><?php $fpe_product_reference((string) $pack_product['product_key']); ?><?php foreach((array) ($pack_product['plans'] ?? []) as $pack_plan): if(empty($pack_plan['discrepancy']) && empty($pack_plan['continuous_period_blocked'])) continue; ?><p><strong><?= $fpe_escape($pack_plan['pack_label']) ?>:</strong> <?= $fpe_escape(rtrim($pack_plan['declared_usage_text'] ?? $pack_plan['reason'] ?? '', '. ')) ?>. <?= $fpe_escape($fpe_t(!empty($pack_plan['continuous_period_blocked']) ? 'Sadržaj pakiranja matematički pokriva {days} dana.' : 'Pakiranje prema toj količini traje {days} dana.', ['{days}' => $fpe_quantity($pack_plan['full_days_per_pack'] ?? $pack_plan['days_per_pack'])])) ?><?php if(!empty($pack_plan['continuous_period_blocked'])): ?> <?= $fpe_escape($pack_plan['reason'] ?? '') ?><?php endif ?></p><?php endforeach ?></article><?php endforeach ?></div></div></details>
                <details class="fpe-declaration"><summary><?= $fpe_escape($fpe_t('Namjene i pakiranja bez fiksnog brojčanog obračuna ({count})', ['{count}' => (string) count((array) ($packaging_review['incomplete'] ?? []))])) ?></summary><div class="fpe-declaration-content"><p>Ovdje su kozmetika, napitci s promjenjivom porcijom, paketi, povijesni proizvodi i identiteti bez pouzdane dnevne upute. Njihov sadržaj ne pretvaramo u pretpostavljenu oralnu dozu.</p><?php foreach((array) ($packaging_review['incomplete'] ?? []) as $pack_product): ?><div class="fpe-review-gap"><?php $fpe_product_reference((string) $pack_product['product_key']); ?><p><?= $fpe_escape($pack_product['plans'][0]['reason'] ?? 'Nedostaje točno aktualno pakiranje za obračun.') ?></p></div><?php endforeach ?></div></details>
            </section><?php endif ?>
            <section class="fpe-review-section" aria-labelledby="fpe-review-topics">
                <div class="fpe-section-heading"><div><span class="fpe-eyebrow">Širi kontekst znanja</span><h2 id="fpe-review-topics">Nove lekcije u edukaciji</h2><p>Svaka obrađena tema ima objašnjenja, praktičnu vježbu i provjeru znanja.</p></div></div>
                <?php if($completed_topics): ?><div class="fpe-review-grid"><?php foreach($completed_topics as $topic): $topic_lesson = $fpe_lesson_index[$topic['lesson_id'] ?? ''] ?? null; if(!$topic_lesson) continue; ?><article class="fpe-review-card" id="<?= $fpe_escape($review_anchor($topic['id'])) ?>"><span class="fpe-review-id">ID: <?= $fpe_escape($topic['id']) ?></span><span class="fpe-badge fpe-ready-badge">Lekcija je dostupna</span><h3><?= $fpe_escape($topic_lesson['title']) ?></h3><p><?= $fpe_escape($topic_lesson['summary']) ?></p><a class="fpe-button fpe-button-secondary" href="<?= $fpe_escape($fpe_link(['section' => 'learn', 'lesson' => $topic_lesson['id']])) ?>">Otvori lekciju <i class="fas fa-book-open" aria-hidden="true"></i></a><?php $fpe_entry_references((array) ($topic['related_entry_ids'] ?? [])); ?></article><?php endforeach ?></div><?php endif ?>
                <?php if($missing_topics): ?><div class="fpe-review-grid"><?php foreach($missing_topics as $missing_topic): ?><article class="fpe-review-card" id="<?= $fpe_escape($review_anchor($missing_topic['id'] ?? '')) ?>"><span class="fpe-review-id">ID: <?= $fpe_escape($missing_topic['id'] ?? '') ?></span><h3><?= $fpe_escape($missing_topic['title'] ?? '') ?></h3><?php if(!empty($missing_topic['why_relevant'])): ?><p><?= $fpe_escape($missing_topic['why_relevant']) ?></p><?php endif ?><?php if(!empty($missing_topic['proposed_scope'])): ?><div class="fpe-review-copy"><h4>Što bi tema obuhvatila</h4><p><?= $fpe_escape($missing_topic['proposed_scope']) ?></p></div><?php endif ?><?php $fpe_entry_references((array) ($missing_topic['related_entry_ids'] ?? [])); ?><?php if(!empty($missing_topic['source_ids'])): ?><details class="fpe-source-disclosure"><summary>Izvori za ovu temu</summary><?php $fpe_source_links((array) $missing_topic['source_ids']); ?></details><?php endif ?></article><?php endforeach ?></div><?php elseif(!$completed_topics): ?><p class="fpe-muted">Dodatne teme još nisu izdvojene.</p><?php endif ?>
            </section>
        </main>
    <?php endif ?>
    <footer class="fpe-footer"><i class="fas fa-graduation-cap" aria-hidden="true"></i><p>Interna edukacija za suradnike o primjeni proizvoda, njihovom kombiniranju i jasnom prenošenju preporuka kupcima.</p></footer>
</div>

<?php ob_start() ?>
<link href="<?= $fpe_escape(ASSETS_FULL_URL . 'css/fcc-product-education.css?v=' . PRODUCT_CODE . '&fpe=20261011v17palette') ?>" rel="stylesheet" media="screen,print">
<?php \Altum\Event::add_content(ob_get_clean(), 'head') ?>
<?php if(in_array($fpe_section, ['products', 'favorites'], true) && !$fpe_product): ?>
<?php ob_start() ?>
<script>
(() => {
    const input = document.getElementById('fpe-product-search');
    const grid = document.getElementById('fpe-product-grid');
    if (!input || !grid) return;
    const cards = Array.from(grid.querySelectorAll('[data-product-search]'));
    const category = document.getElementById('fpe-product-category');
    const count = document.getElementById('fpe-search-count');
    const empty = document.getElementById('fpe-search-empty');
    const more = document.getElementById('fpe-product-more');
    const normalize = value => value.toLocaleLowerCase('hr').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
    const pageSize = 24;
    let limit = pageSize;
    const render = () => {
        const query = normalize(input.value.trim());
        const selectedCategory = category ? category.value : '';
        const matches = cards.filter(card => normalize(card.dataset.productSearch || '').includes(query) && (!selectedCategory || card.dataset.productCategory === selectedCategory));
        const shown = new Set(matches.slice(0, limit));
        cards.forEach(card => { card.hidden = !shown.has(card); });
        if (count) count.textContent = <?= json_encode($fpe_t('Prikazano {shown} od {total} pronađenih proizvoda'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>.replace('{shown}', shown.size).replace('{total}', matches.length);
        if (empty) empty.hidden = matches.length !== 0;
        if (more) more.hidden = shown.size >= matches.length;
    };
    input.addEventListener('input', () => { limit = pageSize; render(); });
    if (category) category.addEventListener('change', () => { limit = pageSize; render(); });
    if (more) more.addEventListener('click', () => { limit += pageSize; render(); });
    render();
})();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>
<?php if(in_array($fpe_section, ['needs', 'routines'], true) || ($fpe_product && !empty($fpe_product['author_recommendations']))): ?>
<?php ob_start() ?>
<script>
(() => {
    const normalize = value => value.toLocaleLowerCase('hr').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
    document.querySelectorAll('[data-fpe-recommendation-collection]').forEach(collection => {
        const input = collection.querySelector('[data-fpe-recommendation-search]');
        const entries = Array.from(collection.querySelectorAll('[data-fpe-recommendation-entry]'));
        const count = collection.querySelector('[data-fpe-recommendation-count]');
        const empty = collection.querySelector('[data-fpe-recommendation-empty]');
        const more = collection.querySelector('[data-fpe-recommendation-more]');
        if (!input || !entries.length) return;
        const browser = collection.hasAttribute('data-fpe-browser-section');
        const filters = Array.from(collection.querySelectorAll('[data-fpe-kind-filter]'));
        const reset = collection.querySelector('[data-fpe-recommendation-reset]');
        const parameters = new URLSearchParams(window.location.search);
        let selectedKind = browser && filters.some(button => button.dataset.fpeKindFilter === parameters.get('kind')) ? parameters.get('kind') : '';
        const pageSize = Math.max(1, Number(collection.dataset.fpeRecommendationLimit) || 24);
        const requestedLimit = browser ? Number(parameters.get('shown')) : 0;
        let limit = Number.isSafeInteger(requestedLimit) && requestedLimit > 0 ? Math.min(entries.length, Math.max(pageSize, requestedLimit)) : pageSize;
        const returnId = browser && window.location.hash.startsWith('#fpe-card-') ? window.location.hash.slice(1) : '';
        let restoreReturn = Boolean(returnId);
        const render = () => {
            const query = normalize(input.value.trim());
            const matches = entries.filter(entry => normalize(entry.dataset.fpeRecommendationText || '').includes(query) && (!selectedKind || entry.dataset.fpeRecommendationKind === selectedKind));
            if (restoreReturn) {
                const returnPosition = matches.findIndex(entry => entry.id === returnId);
                if (returnPosition >= 0) limit = Math.max(limit, Math.ceil((returnPosition + 1) / pageSize) * pageSize);
            }
            const shown = new Set(matches.slice(0, limit));
            entries.forEach(entry => { entry.hidden = !shown.has(entry); });
            filters.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.fpeKindFilter === selectedKind)));
            if (count) count.textContent = (browser ? <?= json_encode($fpe_t('Prikazano {shown} od {total} preporuka'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?> : <?= json_encode($fpe_t('Prikazano {shown} od {total} pronađenih zapisa'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>).replace('{shown}', shown.size).replace('{total}', matches.length);
            if (empty) empty.hidden = matches.length !== 0;
            if (more) more.hidden = shown.size >= matches.length;
            if (browser) {
                const current = new URL(window.location.href);
                if (input.value.trim()) current.searchParams.set('q', input.value.trim()); else current.searchParams.delete('q');
                if (selectedKind) current.searchParams.set('kind', selectedKind); else current.searchParams.delete('kind');
                if (limit > pageSize) current.searchParams.set('shown', String(limit)); else current.searchParams.delete('shown');
                window.history.replaceState(null, '', current);
                collection.querySelectorAll('[data-fpe-open-entry]').forEach(anchor => {
                    const target = new URL(anchor.href);
                    ['q', 'kind', 'shown'].forEach(key => { if (current.searchParams.has(key)) target.searchParams.set(key, current.searchParams.get(key)); else target.searchParams.delete(key); });
                    anchor.href = target.href;
                });
            }
            if (restoreReturn) {
                restoreReturn = false;
                const target = entries.find(entry => entry.id === returnId && !entry.hidden);
                if (target) requestAnimationFrame(() => { target.focus({ preventScroll: true }); target.scrollIntoView({ block: 'center' }); });
            }
        };
        input.addEventListener('input', () => { limit = pageSize; render(); });
        filters.forEach(button => button.addEventListener('click', () => { selectedKind = button.dataset.fpeKindFilter; limit = pageSize; render(); }));
        if (reset) reset.addEventListener('click', () => { input.value = ''; selectedKind = ''; limit = pageSize; render(); input.focus(); });
        if (more) more.addEventListener('click', () => { limit += pageSize; render(); });
        render();
    });
})();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>
<?php if($fpe_product): ?>
<?php ob_start() ?>
<script>
(() => {
    const copyButton = document.querySelector('[data-fpe-copy]');
    const shareButton = document.querySelector('[data-fpe-share]');
    const message = document.getElementById('fpe-share-message');
    const publicUrl = document.getElementById('fpe-public-product-url');
    const announce = text => { if (message) message.textContent = text; };
    if (copyButton) copyButton.addEventListener('click', async () => {
        const url = copyButton.dataset.fpeCopy;
        if (!url) return;
        try {
            if (!navigator.clipboard || !navigator.clipboard.writeText) throw new Error('clipboard_unavailable');
            await navigator.clipboard.writeText(url);
            announce(<?= json_encode($fpe_t('Javna poveznica je kopirana.'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>);
        } catch (error) {
            if (publicUrl) { publicUrl.focus(); publicUrl.select(); }
            announce(<?= json_encode($fpe_t('Poveznica je označena. Kopiraj je naredbom za kopiranje na svojem uređaju.'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>);
        }
    });
    if (shareButton && typeof navigator.share === 'function') {
        shareButton.hidden = false;
        shareButton.addEventListener('click', async () => {
            const url = shareButton.dataset.fpeShare;
            if (!url) return;
            try {
                await navigator.share({ title: shareButton.dataset.fpeShareTitle || <?= json_encode($fpe_t('Informacije o proizvodu'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>, url });
                announce(<?= json_encode($fpe_t('Poveznica je predana odabranoj aplikaciji za dijeljenje.'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>);
            } catch (error) {
                if (error.name !== 'AbortError') announce(<?= json_encode($fpe_t('Dijeljenje nije dostupno. Možeš kopirati javnu poveznicu.'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>);
            }
        });
    }
})();
</script>
<?php \Altum\Event::add_content(ob_get_clean(), 'javascript') ?>
<?php endif ?>

<?php echo fcc_product_education_localize_html(ob_get_clean(), $fpe_language) ?>
