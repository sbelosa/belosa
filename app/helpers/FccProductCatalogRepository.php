<?php

namespace Altum\Helpers;

defined('ALTUMCODE') || die();

/**
 * Shared, revisioned product catalog used by FCC Product Advisor V2.
 *
 * The repository deliberately does not mutate legacy blog_posts rows. Legacy
 * data is imported into an immutable draft revision and published atomically.
 */
final class FccProductCatalogRepository {

    /** @var array<string, array|null> */
    private array $runtimeRevisionCache = [];

    private const IMPORTER_VERSION = 'fcc_catalog_v2_2026_08_26.14';
    private const REVISION_TABLE = 'fcc_product_catalog_revisions';
    private const ENTRY_TABLE = 'fcc_product_catalog_entries';
    private const RELATIONSHIP_TABLE = 'fcc_product_catalog_relationships';
    private const RUN_TABLE = 'fcc_product_advisor_runs';

    private const JSON_LIST_FIELDS = [
        'aliases',
        'use_cases',
        'ingredient_facts',
        'approved_support_facts',
        'usage_facts',
        'safety_notes',
        'allergens_contains',
        'safety_flags',
        'mandatory_safety_notes',
        'suitability_flags',
        'offer_variants',
        'source_refs',
    ];

    private const ALLOWED_ROUTES = [
        'oral',
        'topical',
        'oral_care',
        'program',
        'mixed',
        'unknown',
    ];

    private const ALLOWED_ADVISOR_STATUSES = [
        'recommendable',
        'lookup_only',
        'blocked',
    ];

    private const ALLOWED_AVAILABILITY_STATUSES = [
        'available',
        'unknown',
        'blocked',
    ];

    private const ALLOWED_OFFER_VARIANT_STATUSES = [
        'available', 'sold_out', 'unknown', 'blocked',
    ];

    private const ALLOWED_RELATIONSHIP_TYPES = [
        'complements',
        'alternative',
        'sequence',
        'program_member',
    ];

    private const ALLOWED_RELATIONSHIP_REVIEW_STATUSES = [
        'approved',
        'candidate',
        'quarantined',
        'rejected',
    ];

    private const ALLOWED_RELATIONSHIP_BLOCKERS = [
        'allergen_conflict',
        'medication_or_therapy',
        'pregnancy_or_breastfeeding',
        'under_18',
        'duplicate_micronutrients',
        'required_safety_unknown',
        'product_unavailable',
    ];

    private const ALLOWED_ALLERGEN_KEYS = [
        'soy', 'fish', 'milk', 'egg', 'tree_nuts', 'gluten', 'bee_products',
    ];

    private const ALLOWED_SAFETY_FLAGS = [
        'caffeine', 'green_tea', 'pregnancy_warning', 'breastfeeding_warning',
        'under_18_warning', 'medication_consult', 'empty_stomach_warning',
        'iron_child_poisoning', 'external_use_only', 'avoid_eye_contact',
        'damaged_seal', 'dose_limit', 'laxative_warning',
    ];

    private const ALLOWED_SUITABILITY_FLAGS = ['vegan', 'vegetarian', 'gluten_free'];

    /* Recommendation evidence is re-audited at least quarterly. A stale row
     * remains visible for identity lookup but is removed from recommendation
     * and relationship execution without taking the entire V2 engine down. */
    private const RECOMMENDATION_SOURCE_MAX_AGE_DAYS = 90;
    private const RECOMMENDATION_SOURCE_WARNING_AGE_DAYS = 30;

    private const GENERIC_ALIASES = [
        'aloe', 'gel', 'sun', 'move', 'daily', 'focus', 'fiber', 'fibre',
        'kids', 'lotion', 'cream', 'krema', 'vitamin', 'program', 'paket',
    ];

    private const MARKET_PATH_PREFIXES = [
        'hr' => '/shop/hrv/',
        'si' => '/shop/svn/',
        'rs' => '/shop/scg/',
        'at' => '/shop/aut/',
        'au' => '/shop/aus/',
        'ca' => '/shop/can/',
        'de' => '/shop/deu/',
        'ie' => '/shop/irl/',
        'lu' => '/shop/lux/',
        'nl' => '/shop/nld/',
        'no' => '/shop/nor/',
        'pl' => '/shop/pol/',
        'se' => '/shop/swe/',
        'gb' => '/shop/gbr/',
        'us' => '/shop/usa/',
        'qa' => '/shop/qat/',
        'ch' => '/shop/che/',
        'ae' => '/shop/are/',
    ];

    private const FORBIDDEN_OFFER_TOKENS = [
        'shaker',
        'geschenkpapier',
        'gift-wrap',
        'gift wrap',
        'gift_wrap',
        'giftwrap',
        'wrapping-paper',
        'wrapping paper',
        'wrapping_paper',
        'catalogue',
        'catalog-book',
        'booklet',
    ];

    /**
     * True only when all three additive V2 tables and their consumer-critical
     * columns exist. This prevents a partial migration from looking healthy.
     */
    public function schemaIsReady(): bool {
        $required = [
            self::REVISION_TABLE => [
                'revision_id', 'revision_key', 'status', 'source_type', 'source_hash', 'bundle_hash',
                'entry_count', 'lookup_only_count', 'blocked_offer_count',
                'summary_json', 'validation_json', 'imported_by_user_id',
                'published_by_user_id', 'created_at', 'updated_at', 'published_at',
            ],
            self::ENTRY_TABLE => [
                'entry_id', 'revision_id', 'product_key', 'language_code',
                'canonical_name', 'normalized_name', 'sku', 'category_key',
                'route', 'advisor_status', 'availability_status', 'aliases',
                'use_cases', 'ingredient_facts', 'approved_support_facts',
                'usage_facts', 'safety_notes', 'allergens_contains', 'safety_flags',
                'mandatory_safety_notes', 'safety_review_status', 'suitability_flags',
                'suitability_review_status', 'claim_review_status', 'offer_variants', 'market_offers', 'blog_post_id',
                'article_url', 'source_refs', 'source_hash', 'created_at', 'updated_at',
            ],
            self::RELATIONSHIP_TABLE => [
                'relationship_id', 'revision_id', 'source_product_key',
                'target_product_key', 'language_code', 'market_code',
                'relationship_type', 'goal_keys', 'evidence_summary', 'reason_text',
                'source_refs', 'safety_gate', 'sequence_order', 'source_content_hash',
                'source_hash', 'review_status', 'accessed_at', 'created_at',
            ],
            self::RUN_TABLE => [
                'advisor_run_id', 'revision_id', 'request_id',
                'fcc_ai_conversation_id', 'fcc_ai_message_id', 'user_message_id', 'user_id',
                'language_code', 'market_code', 'market_source', 'query_hash',
                'decision_mode', 'confidence', 'selected_product_keys',
                'candidate_snapshot', 'reason_codes', 'guardrail_flags',
                'provider_output_accepted', 'provider_candidate', 'replay_snapshot',
                'provider', 'model', 'prompt_version', 'schema_version',
                'duration_ms', 'meta', 'created_at',
            ],
        ];
        $tables = array_keys($required);
        $placeholders = implode(',', array_fill(0, count($tables), '?'));
        $rows = $this->fetchAll(
            "SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name IN ({$placeholders})",
            $tables
        );

        $found = [];
        foreach($rows as $row) {
            $table = (string) ($row['table_name'] ?? '');
            $column = (string) ($row['column_name'] ?? '');
            if($table !== '' && $column !== '') {
                $found[$table][$column] = true;
            }
        }

        foreach($required as $table => $columns) {
            foreach($columns as $column) {
                if(empty($found[$table][$column])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns the single current published revision, or null when V2 is not live.
     */
    public function getPublishedRevision(): ?array {
        if(!$this->schemaIsReady()) {
            return null;
        }

        $row = $this->fetchOne(
            'SELECT * FROM `' . self::REVISION_TABLE . '` WHERE `status` = ? ORDER BY `published_at` DESC, `revision_id` DESC LIMIT 1',
            ['published']
        );

        return $row ? $this->hydrateRevision($row) : null;
    }

    /**
     * Runtime selector used by the advisor. A published flag alone is not
     * sufficient: the selected immutable snapshot must still pass the current
     * validator, and an optional deploy pin must match exactly.
     */
    public function getRuntimeRevision(
        ?int $expectedRevisionId = null,
        ?string $expectedRevisionKey = null,
        ?string $expectedBundleHash = null
    ): ?array {
        if(!$this->schemaIsReady()) {
            return null;
        }

        $expectedRevisionId = $expectedRevisionId !== null ? max(0, $expectedRevisionId) : null;
        $expectedRevisionKey = trim((string) $expectedRevisionKey);
        $expectedBundleHash = mb_strtolower(trim((string) $expectedBundleHash));
        if($expectedBundleHash !== '' && preg_match('/^[a-f0-9]{64}$/D', $expectedBundleHash) !== 1) {
            return null;
        }
        $cacheKey = ($expectedRevisionId ?: 0) . '|' . $expectedRevisionKey . '|' . $expectedBundleHash;
        if(array_key_exists($cacheKey, $this->runtimeRevisionCache)) {
            return $this->runtimeRevisionCache[$cacheKey];
        }

        $where = ['`status` = ?'];
        $parameters = ['published'];
        if($expectedRevisionId) {
            $where[] = '`revision_id` = ?';
            $parameters[] = $expectedRevisionId;
        }
        if($expectedRevisionKey !== '') {
            $where[] = '`revision_key` = ?';
            $parameters[] = $expectedRevisionKey;
        }
        if($expectedBundleHash !== '') {
            $where[] = '`bundle_hash` = ?';
            $parameters[] = $expectedBundleHash;
        }

        $row = $this->fetchOne(
            'SELECT * FROM `' . self::REVISION_TABLE . '` WHERE ' . implode(' AND ', $where)
                . ' ORDER BY `published_at` DESC, `revision_id` DESC LIMIT 1',
            $parameters
        );
        if(!$row) {
            $this->runtimeRevisionCache[$cacheKey] = null;
            return null;
        }

        $revision = $this->hydrateRevision($row);
        $validation = $this->validateRevision((int) $revision['revision_id']);
        if(empty($validation['valid'])) {
            $this->runtimeRevisionCache[$cacheKey] = null;
            return null;
        }

        $revision['runtime_validation'] = $validation;
        $this->runtimeRevisionCache[$cacheKey] = $revision;
        return $revision;
    }

    /**
     * Convenience lookup used by CLI tooling and deployment checks.
     */
    public function getRevisionByKey(string $revisionKey): ?array {
        if(!$this->schemaIsReady()) {
            return null;
        }

        $row = $this->fetchOne(
            'SELECT * FROM `' . self::REVISION_TABLE . '` WHERE `revision_key` = ? LIMIT 1',
            [$revisionKey]
        );

        return $row ? $this->hydrateRevision($row) : null;
    }

    public function getRevisionById(int $revisionId): ?array {
        if(!$this->schemaIsReady() || $revisionId <= 0) {
            return null;
        }

        $row = $this->fetchOne(
            'SELECT * FROM `' . self::REVISION_TABLE . '` WHERE `revision_id` = ? LIMIT 1',
            [$revisionId]
        );

        return $row ? $this->hydrateRevision($row) : null;
    }

    /**
     * Validate the reviewed official-source manifest without importing it.
     * Candidate/quarantined records are reported, while an invalid approved
     * record is always a hard error.
     */
    public function validateOfficialSourceManifest(): array {
        try {
            $manifest = $this->loadOfficialSourcesManifest();
        } catch(\Throwable $exception) {
            return [
                'valid' => false,
                'hard_errors' => ['manifest_load_failed:' . $exception->getMessage()],
                'warnings' => [],
                'counts' => [],
            ];
        }

        $legacyRows = $this->loadLegacyRows();
        [$productRows] = $this->splitLegacyRows($legacyRows);
        $groups = $this->groupLegacyProductRows($productRows);
        $expectations = $this->augmentOfficialSourceExpectations(
            $this->buildOfficialSourceExpectations($groups),
            $manifest['canonical_products']
        );
        $expectations = $this->augmentOfficialExpectationsWithIdentityMigrations(
            $expectations,
            $manifest['identity_migrations']
        );
        $hardErrors = [];
        $warnings = [];
        $coveredProducts = [];
        $approvedProducts = [];
        $primaryApproved = [];
        $counts = [
            'legacy_canonical_products' => count($groups),
            'official_only_canonical_products' => count($manifest['canonical_products']),
            'canonical_products' => count($expectations),
            'source_records' => count($manifest['sources']),
            'products_with_source_record' => 0,
            'approved_source_records' => 0,
            'candidate_source_records' => 0,
            'quarantined_source_records' => 0,
            'approved_products' => 0,
            'approved_complete_fact_records' => 0,
            'identity_migrations' => count($manifest['identity_migrations']),
            'approved_identity_migrations' => 0,
            'relationship_records' => count($manifest['relationships']),
            'approved_relationships' => 0,
            'claim_review_records' => (int) ($manifest['claim_reviews']['record_count'] ?? 0),
            'applied_claim_reviews' => (int) ($manifest['claim_reviews']['applied_count'] ?? 0),
            'materialized_inventory_families' => 0,
            'materialized_inventory_offers' => 0,
            'materialized_available_families' => 0,
            'materialized_sold_out_only_families' => 0,
            'materialized_retired_or_orphaned' => 0,
        ];
        if($counts['claim_review_records'] !== $counts['applied_claim_reviews']) {
            $hardErrors[] = 'official_claim_reviews:not_all_reviews_bound_to_exact_source';
        }

        $inventoryInspection = $this->inspectOfficialInventoryReconciliation(
            (array) ($manifest['inventory'] ?? []),
            $groups,
            $manifest['canonical_products']
        );
        foreach((array) ($inventoryInspection['errors'] ?? []) as $inventoryError) {
            $hardErrors[] = 'official_inventory:' . $inventoryError;
        }
        foreach([
            'materialized_inventory_families', 'materialized_inventory_offers',
            'materialized_available_families', 'materialized_sold_out_only_families',
            'materialized_retired_or_orphaned',
        ] as $inventoryCountKey) {
            $counts[$inventoryCountKey] = (int) ($inventoryInspection['counts'][$inventoryCountKey] ?? 0);
        }

        foreach($manifest['canonical_products'] as $index => $canonicalProduct) {
            $canonicalErrors = $this->officialCanonicalProductErrors($canonicalProduct, $groups);
            $label = 'official_canonical:' . $index . ':' . (string) ($canonicalProduct['product_key'] ?? '');
            if((string) ($canonicalProduct['review_status'] ?? '') === 'approved') {
                foreach($canonicalErrors as $error) {
                    $hardErrors[] = $label . ':' . $error;
                }
            } else {
                foreach($canonicalErrors as $error) {
                    $warnings[] = $label . ':' . $error;
                }
            }
        }

        foreach($manifest['identity_migrations'] as $index => $identityMigration) {
            $migrationErrors = $this->officialIdentityMigrationErrors($identityMigration, $groups);
            $label = 'official_identity_migration:' . $index . ':' . (string) ($identityMigration['product_key'] ?? '');
            if((string) ($identityMigration['review_status'] ?? '') === 'approved') {
                $counts['approved_identity_migrations']++;
                foreach($migrationErrors as $error) {
                    $hardErrors[] = $label . ':' . $error;
                }
            } else {
                foreach($migrationErrors as $error) {
                    $warnings[] = $label . ':' . $error;
                }
            }
        }

        foreach($manifest['sources'] as $record) {
            $label = implode('|', [
                (string) ($record['product_key'] ?? ''),
                (string) ($record['language_code'] ?? ''),
                (string) ($record['market_code'] ?? ''),
                (string) ($record['source_id'] ?? ''),
            ]);
            $coveredProducts[(string) ($record['product_key'] ?? '')] = true;
            $errors = $this->officialSourceIdentityErrors($record, $expectations);
            $reviewStatus = (string) ($record['review_status'] ?? 'candidate');
            if($reviewStatus === 'approved') {
                $counts['approved_source_records']++;
                if($errors) {
                    foreach($errors as $error) {
                        $hardErrors[] = 'official_source:' . $label . ':' . $error;
                    }
                    continue;
                }
                $approvedProducts[(string) $record['product_key']] = true;
                if(($record['is_primary_for_language'] ?? false) === true) {
                    $primaryKey = $record['product_key'] . '|' . $record['language_code'];
                    if(isset($primaryApproved[$primaryKey])) {
                        $hardErrors[] = 'official_source:' . $label . ':multiple_primary_sources_for_language';
                    }
                    $primaryApproved[$primaryKey] = true;
                }
                if($this->officialFactRecordIsComplete($record)) {
                    $counts['approved_complete_fact_records']++;
                }
            } elseif($reviewStatus === 'candidate') {
                $counts['candidate_source_records']++;
                foreach($errors as $error) {
                    $warnings[] = 'official_source_candidate:' . $label . ':' . $error;
                }
            } elseif($reviewStatus === 'quarantined' || $reviewStatus === 'rejected') {
                $counts['quarantined_source_records']++;
                if(empty($record['quarantine_reasons']) && empty($errors)) {
                    $warnings[] = 'official_source_quarantine_without_reason:' . $label;
                }
            } else {
                $hardErrors[] = 'official_source:' . $label . ':invalid_review_status';
            }
        }

        foreach($manifest['relationships'] as $index => $relationship) {
            $relationshipErrors = $this->officialRelationshipManifestErrors($relationship, $expectations);
            $label = 'official_relationship:' . $index . ':'
                . (string) ($relationship['source_product_key'] ?? '') . ':'
                . (string) ($relationship['target_product_key'] ?? '');
            if((string) ($relationship['review_status'] ?? '') === 'approved') {
                $counts['approved_relationships']++;
                foreach($relationshipErrors as $error) {
                    $hardErrors[] = $label . ':' . $error;
                }
            } else {
                foreach($relationshipErrors as $error) {
                    $warnings[] = $label . ':' . $error;
                }
            }
        }

        $counts['products_with_source_record'] = count(array_filter(
            array_keys($coveredProducts),
            static fn(string $productKey): bool => $productKey !== ''
        ));
        $counts['approved_products'] = count($approvedProducts);
        $missingSourceCount = max(0, count($expectations) - $counts['products_with_source_record']);
        if($missingSourceCount > 0) {
            $warnings[] = 'canonical_products_without_official_source:' . $missingSourceCount;
        }
        $missingApprovedCount = max(0, count($expectations) - $counts['approved_products']);
        if($missingApprovedCount > 0) {
            $warnings[] = 'canonical_products_without_approved_official_source:' . $missingApprovedCount;
        }

        return [
            'valid' => empty($hardErrors),
            'schema_version' => (string) ($manifest['schema_version'] ?? ''),
            'manifest_hash' => hash('sha256', $this->canonicalJson($manifest)),
            'hard_errors' => array_values(array_unique($hardErrors)),
            'warnings' => array_values(array_unique($warnings)),
            'counts' => $counts,
        ];
    }

    /**
     * Stable consumer contract: a list of associative catalog entries.
     *
     * Localization fallback is explicit (requested -> hr -> en -> first). Market
     * offers never fall back: only the exact requested market may supply a URL.
     */
    public function getCatalog(
        string $languageCode,
        string $marketCode,
        ?int $expectedRevisionId = null,
        ?string $expectedRevisionKey = null,
        ?string $expectedBundleHash = null
    ): array {
        $revision = $this->getRuntimeRevision(
            $expectedRevisionId,
            $expectedRevisionKey,
            $expectedBundleHash
        );

        if(!$revision) {
            return [];
        }

        return $this->materializeCatalog($revision, $languageCode, $marketCode);
    }

    /**
     * Read-only audit loader for an explicitly selected immutable snapshot.
     * Draft/archived revisions are accepted only after the current validator
     * passes; customer-facing runtime must continue to use getCatalog().
     */
    public function getCatalogForValidatedRevision(
        string $languageCode,
        string $marketCode,
        int $revisionId
    ): array {
        $revision = $this->getRevisionById($revisionId);
        if(!$revision) {
            return [];
        }
        $validation = $this->validateRevision($revisionId);
        if(empty($validation['valid'])) {
            return [];
        }

        return $this->materializeCatalog($revision, $languageCode, $marketCode);
    }

    private function materializeCatalog(array $revision, string $languageCode, string $marketCode): array {

        $languageCode = $this->normalizeLanguageCode($languageCode);
        $marketCode = $this->normalizeMarketCode($marketCode);
        $rows = $this->fetchAll(
            'SELECT * FROM `' . self::ENTRY_TABLE . '` WHERE `revision_id` = ? ORDER BY `product_key` ASC, `language_code` ASC, `entry_id` ASC',
            [(int) $revision['revision_id']]
        );
        $relationshipRows = $this->fetchAll(
            'SELECT * FROM `' . self::RELATIONSHIP_TABLE . '` WHERE `revision_id` = ? AND `language_code` = ? AND `market_code` = ? AND `review_status` = ? ORDER BY `source_product_key` ASC, `sequence_order` ASC, `relationship_id` ASC',
            [(int) $revision['revision_id'], $languageCode, $marketCode, 'approved']
        );
        $relationshipsBySource = [];
        $relationshipIdentityOwners = [];
        foreach($relationshipRows as $relationshipRow) {
            if(!$this->isAccessedAtFresh(
                (string) ($relationshipRow['accessed_at'] ?? ''),
                self::RECOMMENDATION_SOURCE_MAX_AGE_DAYS
            )) {
                continue;
            }
            $sourceProductKey = trim((string) ($relationshipRow['source_product_key'] ?? ''));
            if($sourceProductKey !== '') {
                $relationshipsBySource[$sourceProductKey][] = $this->hydrateRelationship($relationshipRow);
            }
        }

        $grouped = [];
        foreach($rows as $row) {
            $productKey = (string) ($row['product_key'] ?? '');
            if($productKey === '') {
                continue;
            }
            $grouped[$productKey][] = $row;
        }

        $catalog = [];
        foreach($grouped as $productRows) {
            $selected = $this->selectLocalizedEntry($productRows, $languageCode);
            if(!$selected) {
                continue;
            }

            $entry = $this->hydrateCatalogEntry(
                $selected,
                (int) $revision['revision_id'],
                $marketCode,
                $languageCode
            );
            $selectedLanguageCode = $this->normalizeLanguageCode((string) ($selected['language_code'] ?? ''));
            $entry['relationships'] = $selectedLanguageCode === $languageCode
                ? array_values($relationshipsBySource[(string) ($entry['product_key'] ?? '')] ?? [])
                : [];
            $catalog[] = $entry;
        }

        $catalog = $this->applyMarketMatcherAliases($catalog);

        usort($catalog, static function(array $left, array $right): int {
            $nameOrder = strcasecmp((string) ($left['canonical_name'] ?? ''), (string) ($right['canonical_name'] ?? ''));
            return $nameOrder !== 0 ? $nameOrder : strcmp((string) ($left['product_key'] ?? ''), (string) ($right['product_key'] ?? ''));
        });

        return array_values($catalog);
    }

    /**
     * Validates an immutable revision. Imported invalid URLs are already blocked;
     * an URL still marked available must pass every strict market check here.
     */
    public function validateRevision(int $revisionId): array {
        $revisionId = max(0, $revisionId);
        $revision = $revisionId > 0 && $this->schemaIsReady()
            ? $this->fetchOne('SELECT * FROM `' . self::REVISION_TABLE . '` WHERE `revision_id` = ? LIMIT 1', [$revisionId])
            : null;

        if(!$revision) {
            return [
                'valid' => false,
                'revision_id' => $revisionId,
                'revision_key' => '',
                'hard_errors' => ['revision_not_found'],
                'warnings' => [],
                'counts' => [
                    'entries' => 0,
                    'products' => 0,
                    'lookup_only' => 0,
                    'recommendable' => 0,
                    'blocked_entries' => 0,
                    'available_offers' => 0,
                    'blocked_offers' => 0,
                    'missing_sku' => 0,
                    'missing_structured_facts' => 0,
                    'unknown_route' => 0,
                    'missing_article_url' => 0,
                    'without_available_offer' => 0,
                    'unverified_sku' => 0,
                    'verified_fact_entries' => 0,
                    'without_verified_facts' => 0,
                    'relationships' => 0,
                ],
            ];
        }

        $rows = $this->fetchAll(
            'SELECT * FROM `' . self::ENTRY_TABLE . '` WHERE `revision_id` = ? ORDER BY `product_key`, `language_code`, `entry_id`',
            [$revisionId]
        );
        $relationshipRows = $this->fetchAll(
            'SELECT * FROM `' . self::RELATIONSHIP_TABLE . '` WHERE `revision_id` = ? ORDER BY `source_product_key`, `language_code`, `market_code`, `relationship_type`, `relationship_id`',
            [$revisionId]
        );
        $hardErrors = [];
        $warnings = [];
        $aliasOwners = [];
        $offerUrlOwners = [];
        $availableOfferKeys = [];
        $blockedOfferKeys = [];
        $productLanguages = [];
        $productRoutes = [];
        $productGelKinds = [];
        $blogPostOwners = [];
        $counts = [
            'entries' => count($rows),
            'products' => 0,
            'lookup_only' => 0,
            'recommendable' => 0,
            'blocked_entries' => 0,
            'available_offers' => 0,
            'blocked_offers' => 0,
            'missing_sku' => 0,
            'missing_structured_facts' => 0,
            'unknown_route' => 0,
            'missing_article_url' => 0,
            'without_available_offer' => 0,
            'unverified_sku' => 0,
            'verified_fact_entries' => 0,
            'without_verified_facts' => 0,
            'relationships' => 0,
        ];

        $revisionKey = trim((string) ($revision['revision_key'] ?? ''));
        $revisionSourceHash = mb_strtolower(trim((string) ($revision['source_hash'] ?? '')));
        $storedBundleHash = mb_strtolower(trim((string) ($revision['bundle_hash'] ?? '')));
        if(!$this->isValidRevisionKey($revisionKey)) {
            $hardErrors[] = 'revision_metadata:invalid_revision_key';
        }
        if(!preg_match('/^[a-f0-9]{64}$/', $revisionSourceHash)) {
            $hardErrors[] = 'revision_metadata:invalid_source_hash';
        }
        if((int) ($revision['entry_count'] ?? -1) !== count($rows)) {
            $hardErrors[] = 'revision_metadata:entry_count_mismatch';
        }
        if(!preg_match('/^[a-f0-9]{64}$/', $storedBundleHash)) {
            $hardErrors[] = 'revision_metadata:invalid_bundle_hash';
        } else {
            $expectedBundleHash = $this->revisionBundleHash($rows, $relationshipRows);
            if(!hash_equals($storedBundleHash, $expectedBundleHash)) {
                $hardErrors[] = 'revision_metadata:bundle_hash_mismatch';
            }
        }

        $summaryValid = true;
        $revisionSummary = $this->decodeJsonObject($revision['summary_json'] ?? null, $summaryValid);
        if(!$summaryValid || !$revisionSummary) {
            $hardErrors[] = 'revision_metadata:invalid_summary_json';
        } else {
            if((int) ($revisionSummary['entries'] ?? -1) !== count($rows)) {
                $hardErrors[] = 'revision_metadata:summary_entry_count_mismatch';
            }
            if((string) ($revisionSummary['importer_version'] ?? '') !== self::IMPORTER_VERSION) {
                /* The stored bundle remains immutable and valid across a code
                 * deployment. Compatibility drift is visible to operations,
                 * but must not create a deploy-order outage or destroy the
                 * ability to roll back to a previously validated artifact. */
                $warnings[] = 'revision_metadata:importer_version_differs_from_runtime';
            }
            $storedManifestHash = mb_strtolower(trim((string) ($revisionSummary['official_sources_manifest_hash'] ?? '')));
            if(!preg_match('/^[a-f0-9]{64}$/', $storedManifestHash)) {
                $hardErrors[] = 'revision_metadata:invalid_official_manifest_hash';
            } else {
                try {
                    $currentManifestHash = hash('sha256', $this->canonicalJson($this->loadOfficialSourcesManifest()));
                    if(!hash_equals($storedManifestHash, $currentManifestHash)) {
                        $warnings[] = 'revision_metadata:official_manifest_differs_from_runtime';
                    }
                } catch(\Throwable $exception) {
                    $warnings[] = 'revision_metadata:current_official_manifest_unavailable';
                }
            }
        }

        if(empty($rows)) {
            $hardErrors[] = 'revision_has_no_entries';
        }

        foreach($rows as $row) {
            $entryId = (int) ($row['entry_id'] ?? 0);
            $productKey = trim((string) ($row['product_key'] ?? ''));
            $languageCode = $this->normalizeLanguageCode((string) ($row['language_code'] ?? ''));
            $canonicalName = trim((string) ($row['canonical_name'] ?? ''));
            $route = trim((string) ($row['route'] ?? ''));
            $advisorStatus = trim((string) ($row['advisor_status'] ?? ''));
            $availabilityStatus = trim((string) ($row['availability_status'] ?? ''));
            $safetyReviewStatus = trim((string) ($row['safety_review_status'] ?? 'unknown'));
            $suitabilityReviewStatus = trim((string) ($row['suitability_review_status'] ?? 'unknown'));
            $claimReviewStatus = trim((string) ($row['claim_review_status'] ?? 'unknown'));
            $entryLabel = 'entry:' . $entryId . ':' . ($productKey !== '' ? $productKey : 'missing-key') . ':' . ($languageCode !== '' ? $languageCode : 'missing-language');

            if($productKey === '' || !$this->isValidProductKey($productKey)) {
                $hardErrors[] = $entryLabel . ':invalid_product_key';
            }
            if($languageCode === '') {
                $hardErrors[] = $entryLabel . ':missing_language_code';
            }
            if($canonicalName === '') {
                $hardErrors[] = $entryLabel . ':missing_canonical_name';
            }
            if(!in_array($route, self::ALLOWED_ROUTES, true)) {
                $hardErrors[] = $entryLabel . ':invalid_route';
            }
            if(!in_array($advisorStatus, self::ALLOWED_ADVISOR_STATUSES, true)) {
                $hardErrors[] = $entryLabel . ':invalid_advisor_status';
            }
            if(!in_array($availabilityStatus, self::ALLOWED_AVAILABILITY_STATUSES, true)) {
                $hardErrors[] = $entryLabel . ':invalid_availability_status';
            }
            if(!in_array($safetyReviewStatus, ['complete', 'unknown'], true)) {
                $hardErrors[] = $entryLabel . ':invalid_safety_review_status';
            }
            if(!in_array($suitabilityReviewStatus, ['complete', 'unknown'], true)) {
                $hardErrors[] = $entryLabel . ':invalid_suitability_review_status';
            }
            if(!in_array($claimReviewStatus, ['approved', 'unknown'], true)) {
                $hardErrors[] = $entryLabel . ':invalid_claim_review_status';
            }
            if(!preg_match('/^[a-f0-9]{64}$/', (string) ($row['source_hash'] ?? ''))) {
                $hardErrors[] = $entryLabel . ':invalid_source_hash';
            }

            $decoded = [];
            foreach(self::JSON_LIST_FIELDS as $field) {
                $validJson = true;
                $decoded[$field] = $this->decodeJsonList($row[$field] ?? null, $validJson);
                if(!$validJson) {
                    $hardErrors[] = $entryLabel . ':invalid_json:' . $field;
                }
            }

            $validOffersJson = true;
            $marketOffers = $this->decodeJsonObject($row['market_offers'] ?? null, $validOffersJson);
            if(!$validOffersJson) {
                $hardErrors[] = $entryLabel . ':invalid_json:market_offers';
            }

            $expectedSourceHash = hash('sha256', $this->canonicalJson([
                'product_key' => $productKey,
                'language_code' => $languageCode,
                'canonical_name' => $canonicalName,
                'normalized_name' => (string) ($row['normalized_name'] ?? ''),
                'sku' => (string) ($row['sku'] ?? ''),
                'category_key' => (string) ($row['category_key'] ?? ''),
                'route' => $route,
                'advisor_status' => $advisorStatus,
                'availability_status' => $availabilityStatus,
                'aliases' => $decoded['aliases'],
                'use_cases' => $decoded['use_cases'],
                'ingredient_facts' => $decoded['ingredient_facts'],
                'approved_support_facts' => $decoded['approved_support_facts'],
                'usage_facts' => $decoded['usage_facts'],
                'safety_notes' => $decoded['safety_notes'],
                'allergens_contains' => $decoded['allergens_contains'],
                'safety_flags' => $decoded['safety_flags'],
                'mandatory_safety_notes' => $decoded['mandatory_safety_notes'],
                'safety_review_status' => $safetyReviewStatus,
                'suitability_flags' => $decoded['suitability_flags'],
                'suitability_review_status' => $suitabilityReviewStatus,
                'claim_review_status' => $claimReviewStatus,
                'offer_variants' => $decoded['offer_variants'],
                'market_offers' => $marketOffers,
                'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
                'article_url' => (string) ($row['article_url'] ?? ''),
                'source_refs' => $decoded['source_refs'],
            ]));
            $storedSourceHash = (string) ($row['source_hash'] ?? '');
            if(preg_match('/^[a-f0-9]{64}$/', $storedSourceHash) && !hash_equals($storedSourceHash, $expectedSourceHash)) {
                $hardErrors[] = $entryLabel . ':source_hash_content_mismatch';
            }

            if($productKey !== '' && $languageCode !== '') {
                $productLanguages[$productKey][$languageCode] = true;
                $productRoutes[$productKey][$languageCode] = $route;
            }

            $blogPostId = (int) ($row['blog_post_id'] ?? 0);
            if($blogPostId > 0) {
                $ownerKey = $productKey . '|' . $languageCode;
                if(isset($blogPostOwners[$blogPostId]) && $blogPostOwners[$blogPostId] !== $ownerKey) {
                    $hardErrors[] = $entryLabel . ':blog_post_assigned_to_multiple_products:' . $blogPostId;
                } else {
                    $blogPostOwners[$blogPostId] = $ownerKey;
                }
            }

            $sku = $this->normalizeSku((string) ($row['sku'] ?? ''));
            $skuVerification = $this->verifySkuAgainstMarketOffers($sku, $marketOffers);
            if($sku === '') {
                $counts['missing_sku']++;
            } elseif(!$skuVerification['verified']) {
                $counts['unverified_sku']++;
            }
            if($route === 'unknown') {
                $counts['unknown_route']++;
            }
            if(trim((string) ($row['article_url'] ?? '')) === '') {
                $counts['missing_article_url']++;
            }

            $factsComplete = !empty($decoded['use_cases'])
                && (!empty($decoded['ingredient_facts']) || !empty($decoded['approved_support_facts']))
                && !empty($decoded['usage_facts'])
                && !empty($decoded['safety_notes']);
            if(!$factsComplete) {
                $counts['missing_structured_facts']++;
            }
            foreach($decoded['allergens_contains'] as $allergen) {
                if(!in_array((string) $allergen, self::ALLOWED_ALLERGEN_KEYS, true)) {
                    $hardErrors[] = $entryLabel . ':invalid_allergen_key:' . (string) $allergen;
                }
            }
            foreach($decoded['safety_flags'] as $safetyFlag) {
                if(!in_array((string) $safetyFlag, self::ALLOWED_SAFETY_FLAGS, true)) {
                    $hardErrors[] = $entryLabel . ':invalid_safety_flag:' . (string) $safetyFlag;
                }
            }
            foreach($decoded['suitability_flags'] as $suitabilityFlag) {
                if(!in_array((string) $suitabilityFlag, self::ALLOWED_SUITABILITY_FLAGS, true)) {
                    $hardErrors[] = $entryLabel . ':invalid_suitability_flag:' . (string) $suitabilityFlag;
                }
            }
            if($suitabilityReviewStatus !== 'complete' && $decoded['suitability_flags']) {
                $hardErrors[] = $entryLabel . ':unknown_suitability_exposes_flags';
            }
            $safetyCorpus = $this->normalizeOfficialEvidenceText(implode(' ', $decoded['safety_notes']));
            foreach($decoded['mandatory_safety_notes'] as $mandatorySafetyNote) {
                if(!str_contains($safetyCorpus, $this->normalizeOfficialEvidenceText((string) $mandatorySafetyNote))) {
                    $hardErrors[] = $entryLabel . ':mandatory_safety_note_not_in_safety_notes';
                }
            }
            $offerVariantKeys = [];
            foreach($decoded['offer_variants'] as $offerVariant) {
                if(!is_array($offerVariant)) {
                    $hardErrors[] = $entryLabel . ':invalid_offer_variant_shape';
                    continue;
                }
                $offerKey = trim((string) ($offerVariant['offer_key'] ?? ''));
                $variantMarket = $this->normalizeMarketCode((string) ($offerVariant['market_code'] ?? ''));
                $variantUrl = (string) ($offerVariant['official_url'] ?? '');
                if($offerKey === '' || isset($offerVariantKeys[$offerKey])) {
                    $hardErrors[] = $entryLabel . ':missing_or_duplicate_offer_variant_key';
                }
                $offerVariantKeys[$offerKey] = true;
                if(
                    (string) ($offerVariant['review_status'] ?? '') !== 'approved'
                    || !in_array((string) ($offerVariant['availability_status'] ?? ''), self::ALLOWED_OFFER_VARIANT_STATUSES, true)
                    || !$this->validateOfferUrl($variantMarket, $variantUrl)['valid']
                    || !preg_match('/^[a-f0-9]{64}$/', (string) ($offerVariant['content_sha256'] ?? ''))
                    || !$this->validAccessedAt((string) ($offerVariant['accessed_at'] ?? ''))
                ) {
                    $hardErrors[] = $entryLabel . ':invalid_or_unreviewed_offer_variant:' . $offerKey;
                }
            }

            $hasVerifiedFactEvidence = false;
            foreach($decoded['source_refs'] as $sourceRef) {
                if(!is_array($sourceRef)) {
                    continue;
                }
                $sourceType = (string) ($sourceRef['source_type'] ?? '');
                if($sourceType === 'verified_facts_overlay') {
                    if(
                        (string) ($sourceRef['product_key'] ?? '') !== $productKey
                        || $this->normalizeLanguageCode((string) ($sourceRef['language_code'] ?? '')) !== $languageCode
                    ) {
                        $hardErrors[] = $entryLabel . ':verified_fact_overlay_identity_mismatch';
                        continue;
                    }
                    if(!preg_match('/^[a-f0-9]{64}$/', (string) ($sourceRef['overlay_hash'] ?? ''))) {
                        $hardErrors[] = $entryLabel . ':verified_fact_overlay_hash_invalid';
                        continue;
                    }
                } elseif($sourceType === 'official_product_source') {
                    $sourceMarketCode = $this->normalizeMarketCode((string) ($sourceRef['market_code'] ?? ''));
                    $sourceUrl = (string) ($sourceRef['source_url'] ?? '');
                    $canonicalOfferUrl = $this->normalizeOfferUrl((string) ($sourceRef['canonical_offer_url'] ?? ''));
                    $canonicalOfferBinding = (string) ($sourceRef['canonical_offer_binding'] ?? '');
                    $expectedCanonicalOfferUrl = isset($marketOffers[$sourceMarketCode]) && is_array($marketOffers[$sourceMarketCode])
                        ? $this->normalizeOfferUrl((string) ($marketOffers[$sourceMarketCode]['official_url'] ?? ''))
                        : '';
                    $officialUrlValidation = $this->validateOfficialEvidenceUrl(
                        $sourceUrl,
                        $sourceMarketCode
                    );
                    if(
                        (string) ($sourceRef['product_key'] ?? '') !== $productKey
                        || $this->normalizeLanguageCode((string) ($sourceRef['language_code'] ?? '')) !== $languageCode
                        || $sourceMarketCode === ''
                    ) {
                        $hardErrors[] = $entryLabel . ':official_fact_source_identity_mismatch';
                        continue;
                    }
                    if(
                        $canonicalOfferUrl === ''
                        || $expectedCanonicalOfferUrl === ''
                        || !hash_equals($expectedCanonicalOfferUrl, $canonicalOfferUrl)
                        || $canonicalOfferBinding === ''
                        || !hash_equals(
                            $this->officialEvidenceCanonicalOfferBinding(
                                $sourceUrl,
                                $canonicalOfferUrl,
                                $sourceMarketCode,
                                (string) ($sourceRef['page_item_code'] ?? '')
                            ),
                            $canonicalOfferBinding
                        )
                    ) {
                        $hardErrors[] = $entryLabel . ':official_fact_source_canonical_offer_mismatch';
                        continue;
                    }
                    if(
                        (string) ($sourceRef['review_status'] ?? '') !== 'approved'
                        || !in_array((string) ($sourceRef['identity_status'] ?? ''), ['verified_exact', 'verified_reviewed'], true)
                        || !$officialUrlValidation['valid']
                        || !preg_match('/^[a-f0-9]{64}$/', (string) ($sourceRef['content_sha256'] ?? ''))
                        || !$this->validAccessedAt((string) ($sourceRef['accessed_at'] ?? ''))
                    ) {
                        $hardErrors[] = $entryLabel . ':official_fact_source_not_reviewed_or_invalid';
                        continue;
                    }
                    if(!$this->isAccessedAtFresh(
                        (string) ($sourceRef['accessed_at'] ?? ''),
                        self::RECOMMENDATION_SOURCE_WARNING_AGE_DAYS
                    )) {
                        $warnings[] = $entryLabel . ':official_fact_source_refresh_due';
                    }
                } else {
                    continue;
                }

                $hasVerifiedFactEvidence = (int) ($sourceRef['verified_use_case_count'] ?? 0) > 0
                    && (int) ($sourceRef['verified_fact_count'] ?? 0) > 0
                    && (int) ($sourceRef['verified_usage_count'] ?? 0) > 0
                    && (int) ($sourceRef['verified_safety_count'] ?? 0) > 0;
                if($hasVerifiedFactEvidence) {
                    break;
                }
            }
            if($hasVerifiedFactEvidence) {
                $counts['verified_fact_entries']++;
            } else {
                $counts['without_verified_facts']++;
            }

            $hasAvailableOffer = false;
            foreach($marketOffers as $marketCode => $offer) {
                if(!is_array($offer)) {
                    $hardErrors[] = $entryLabel . ':invalid_offer_shape:' . $marketCode;
                    continue;
                }

                $offerStatus = (string) ($offer['availability_status'] ?? 'unknown');
                if(!in_array($offerStatus, self::ALLOWED_AVAILABILITY_STATUSES, true)) {
                    $hardErrors[] = $entryLabel . ':invalid_offer_status:' . $marketCode;
                    continue;
                }

                if($offerStatus === 'available') {
                    $offerValidation = $this->validateOfferUrl((string) $marketCode, (string) ($offer['official_url'] ?? ''));
                    if(!$offerValidation['valid']) {
                        $hardErrors[] = $entryLabel . ':available_offer_failed_validation:' . $marketCode . ':' . implode(',', $offerValidation['errors']);
                    } else {
                        $normalizedOfferUrl = $this->normalizeOfferUrl((string) ($offer['official_url'] ?? ''));
                        $offerOwnerKey = $marketCode . '|' . $normalizedOfferUrl;
                        if(isset($offerUrlOwners[$offerOwnerKey]) && $offerUrlOwners[$offerOwnerKey] !== $productKey) {
                            $hardErrors[] = $entryLabel . ':offer_url_assigned_to_multiple_products:' . $marketCode . ':' . $offerUrlOwners[$offerOwnerKey];
                        } else {
                            $offerUrlOwners[$offerOwnerKey] = $productKey;
                        }
                        $availableOfferKeys[$productKey . '|' . $marketCode] = true;
                        $hasAvailableOffer = true;
                    }
                } elseif($offerStatus === 'blocked') {
                    $blockedOfferKeys[$productKey . '|' . $marketCode] = true;
                    if(trim((string) ($offer['official_url'] ?? '')) !== '') {
                        $hardErrors[] = $entryLabel . ':blocked_offer_exposes_url:' . $marketCode;
                    }
                }
            }
            if(!$hasAvailableOffer) {
                $counts['without_available_offer']++;
            }

            $skuMatcherAliasPresent = false;
            if($sku !== '') {
                $normalizedSkuCode = $this->normalizeProductCode($sku);
                foreach($decoded['aliases'] as $storedAlias) {
                    if($this->normalizeProductCode((string) $storedAlias) === $normalizedSkuCode) {
                        $skuMatcherAliasPresent = true;
                        break;
                    }
                }
                if($skuMatcherAliasPresent && !$skuVerification['verified']) {
                    $hardErrors[] = $entryLabel . ':unverified_sku_used_as_matcher_alias:' . $sku;
                }
            }

            $aliases = array_values(array_unique(array_merge([$canonicalName], $decoded['aliases'])));
            $hasGelAlias = false;
            $hasGellyAlias = false;
            foreach($aliases as $alias) {
                $normalizedAlias = $this->normalizeSearchText((string) $alias);
                if($normalizedAlias === '') {
                    continue;
                }

                if(in_array($normalizedAlias, self::GENERIC_ALIASES, true)) {
                    $hardErrors[] = $entryLabel . ':unsafe_generic_alias:' . $normalizedAlias;
                }

                $aliasOwnerKey = $languageCode . '|' . $normalizedAlias;
                if(isset($aliasOwners[$aliasOwnerKey]) && $aliasOwners[$aliasOwnerKey] !== $productKey) {
                    $hardErrors[] = $entryLabel . ':alias_collision:' . $normalizedAlias . ':' . $aliasOwners[$aliasOwnerKey];
                } else {
                    $aliasOwners[$aliasOwnerKey] = $productKey;
                }

                $withoutBrand = trim((string) preg_replace('/^forever\s+/u', '', $normalizedAlias));
                $hasGellyAlias = $hasGellyAlias || preg_match('/(^|\s)aloe vera gelly($|\s)/u', $withoutBrand) === 1;
                $hasGelAlias = $hasGelAlias || preg_match('/(^|\s)aloe vera gel($|\s)/u', $withoutBrand) === 1;
            }

            if($hasGelAlias && $hasGellyAlias) {
                $hardErrors[] = $entryLabel . ':gel_gelly_alias_collision';
            }
            if($hasGelAlias) {
                $productGelKinds[$productKey]['gel'] = true;
            }
            if($hasGellyAlias) {
                $productGelKinds[$productKey]['gelly'] = true;
            }

            if($advisorStatus === 'recommendable') {
                $counts['recommendable']++;
                if($route === 'unknown') {
                    $hardErrors[] = $entryLabel . ':recommendable_route_unknown';
                }
                if(!$factsComplete) {
                    $hardErrors[] = $entryLabel . ':recommendable_missing_approved_facts';
                }
                if(!$hasVerifiedFactEvidence) {
                    $hardErrors[] = $entryLabel . ':recommendable_without_exact_reviewed_fact_source';
                }
                if(!$hasAvailableOffer) {
                    $hardErrors[] = $entryLabel . ':recommendable_without_available_offer';
                }
                if($safetyReviewStatus !== 'complete') {
                    $hardErrors[] = $entryLabel . ':recommendable_without_complete_warning_review';
                }
                if($claimReviewStatus !== 'approved') {
                    $hardErrors[] = $entryLabel . ':recommendable_without_approved_claim_review';
                }
            } elseif($advisorStatus === 'lookup_only') {
                $counts['lookup_only']++;
            } else {
                $counts['blocked_entries']++;
            }
        }

        foreach($relationshipRows as $relationshipRow) {
            $relationshipId = (int) ($relationshipRow['relationship_id'] ?? 0);
            $sourceProductKey = trim((string) ($relationshipRow['source_product_key'] ?? ''));
            $targetProductKey = trim((string) ($relationshipRow['target_product_key'] ?? ''));
            $languageCode = $this->normalizeLanguageCode((string) ($relationshipRow['language_code'] ?? ''));
            $marketCode = $this->normalizeMarketCode((string) ($relationshipRow['market_code'] ?? ''));
            $relationshipType = trim((string) ($relationshipRow['relationship_type'] ?? ''));
            $relationshipLabel = 'relationship:' . $relationshipId . ':' . $sourceProductKey . ':' . $targetProductKey;
            $relationshipIdentity = implode('|', [
                $sourceProductKey,
                $targetProductKey,
                $languageCode,
                $marketCode,
                $relationshipType,
            ]);
            if(isset($relationshipIdentityOwners[$relationshipIdentity])) {
                $hardErrors[] = $relationshipLabel . ':duplicate_semantic_relationship:'
                    . $relationshipIdentityOwners[$relationshipIdentity];
            } else {
                $relationshipIdentityOwners[$relationshipIdentity] = $relationshipId;
            }

            $goalKeysValid = true;
            $goalKeys = $this->decodeJsonList($relationshipRow['goal_keys'] ?? null, $goalKeysValid);
            $sourceRefsValid = true;
            $sourceRefs = $this->decodeJsonList($relationshipRow['source_refs'] ?? null, $sourceRefsValid);
            $safetyGateValid = true;
            $safetyGate = $this->decodeJsonObject($relationshipRow['safety_gate'] ?? null, $safetyGateValid);

            if(!$goalKeysValid || empty($goalKeys)) {
                $hardErrors[] = $relationshipLabel . ':missing_or_invalid_goal_keys';
            }
            if(!$sourceRefsValid || empty($sourceRefs)) {
                $hardErrors[] = $relationshipLabel . ':missing_or_invalid_source_refs';
            }
            if(!$safetyGateValid || empty($safetyGate)) {
                $hardErrors[] = $relationshipLabel . ':missing_or_invalid_safety_gate';
            }
            if(!in_array($relationshipType, self::ALLOWED_RELATIONSHIP_TYPES, true)) {
                $hardErrors[] = $relationshipLabel . ':invalid_relationship_type';
            }
            if((string) ($relationshipRow['review_status'] ?? '') !== 'approved') {
                $hardErrors[] = $relationshipLabel . ':non_approved_relationship_persisted';
            }
            if(
                $sourceProductKey === ''
                || $targetProductKey === ''
                || $sourceProductKey === $targetProductKey
                || empty($productLanguages[$sourceProductKey][$languageCode])
                || empty($productLanguages[$targetProductKey][$languageCode])
            ) {
                $hardErrors[] = $relationshipLabel . ':relationship_identity_or_language_mismatch';
            }
            if(
                $marketCode === ''
                || empty($availableOfferKeys[$sourceProductKey . '|' . $marketCode])
                || empty($availableOfferKeys[$targetProductKey . '|' . $marketCode])
            ) {
                $hardErrors[] = $relationshipLabel . ':relationship_market_offer_missing';
            }
            if(trim((string) ($relationshipRow['evidence_summary'] ?? '')) === '') {
                $hardErrors[] = $relationshipLabel . ':missing_evidence_summary';
            }
            if(trim((string) ($relationshipRow['reason_text'] ?? '')) === '') {
                $hardErrors[] = $relationshipLabel . ':missing_reason_text';
            }
            if(
                ($safetyGate['requires_goal_match'] ?? null) !== true
                || ($safetyGate['allow_if_safety_unknown'] ?? null) !== false
                || !is_bool($safetyGate['allow_general_combination'] ?? null)
                || !isset($safetyGate['blocked_if'])
                || !is_array($safetyGate['blocked_if'])
            ) {
                $hardErrors[] = $relationshipLabel . ':safety_gate_not_fail_closed';
            }
            $blockedIf = is_array($safetyGate['blocked_if'] ?? null)
                ? $this->cleanStringList($safetyGate['blocked_if'])
                : [];
            foreach($blockedIf as $blocker) {
                if(!in_array($blocker, self::ALLOWED_RELATIONSHIP_BLOCKERS, true)) {
                    $hardErrors[] = $relationshipLabel . ':invalid_safety_blocker:' . $blocker;
                }
            }
            foreach(['allergen_conflict', 'required_safety_unknown', 'product_unavailable'] as $requiredBlocker) {
                if(!in_array($requiredBlocker, $blockedIf, true)) {
                    $hardErrors[] = $relationshipLabel . ':missing_required_safety_blocker:' . $requiredBlocker;
                }
            }
            if(($safetyGate['allow_general_combination'] ?? false) === true) {
                $sourceRoute = (string) ($productRoutes[$sourceProductKey][$languageCode] ?? 'unknown');
                $targetRoute = (string) ($productRoutes[$targetProductKey][$languageCode] ?? 'unknown');
                if($relationshipType !== 'complements' || $sourceRoute !== 'oral' || $targetRoute !== 'oral') {
                    $hardErrors[] = $relationshipLabel . ':general_combination_only_for_reviewed_oral_complements';
                }
            }

            $sequenceOrder = (int) ($relationshipRow['sequence_order'] ?? 0);
            if($relationshipType === 'sequence' && $sequenceOrder <= 0) {
                $hardErrors[] = $relationshipLabel . ':sequence_order_required';
            }
            if($relationshipType !== 'sequence' && $sequenceOrder < 0) {
                $hardErrors[] = $relationshipLabel . ':invalid_sequence_order';
            }
            if(!preg_match('/^[a-f0-9]{64}$/', (string) ($relationshipRow['source_content_hash'] ?? ''))) {
                $hardErrors[] = $relationshipLabel . ':invalid_source_content_hash';
            }
            if(!$this->validAccessedAt((string) ($relationshipRow['accessed_at'] ?? ''))) {
                $hardErrors[] = $relationshipLabel . ':invalid_accessed_at';
            }

            $hasExactOfficialRelationshipSource = false;
            foreach($sourceRefs as $sourceRef) {
                if(!is_array($sourceRef) || (string) ($sourceRef['source_type'] ?? '') !== 'official_product_relationship') {
                    continue;
                }
                $sourceRefMarket = $this->normalizeMarketCode((string) ($sourceRef['market_code'] ?? ''));
                $sourceUrlValidation = $this->validateOfficialEvidenceUrl(
                    (string) ($sourceRef['source_url'] ?? ''),
                    $sourceRefMarket
                );
                if(
                    $this->normalizeLanguageCode((string) ($sourceRef['language_code'] ?? '')) === $languageCode
                    && $sourceRefMarket === $marketCode
                    && $sourceUrlValidation['valid']
                    && preg_match('/^[a-f0-9]{64}$/', (string) ($sourceRef['content_sha256'] ?? ''))
                    && hash_equals(
                        (string) ($relationshipRow['source_content_hash'] ?? ''),
                        (string) ($sourceRef['content_sha256'] ?? '')
                    )
                    && in_array($targetProductKey, (array) ($sourceRef['named_target_product_keys'] ?? []), true)
                    && $this->validAccessedAt((string) ($sourceRef['accessed_at'] ?? ''))
                ) {
                    $hasExactOfficialRelationshipSource = true;
                    break;
                }
            }
            if(!$hasExactOfficialRelationshipSource) {
                $hardErrors[] = $relationshipLabel . ':missing_exact_official_relationship_source';
            }

            $expectedRelationshipHash = hash('sha256', $this->canonicalJson([
                'source_product_key' => $sourceProductKey,
                'target_product_key' => $targetProductKey,
                'language_code' => $languageCode,
                'market_code' => $marketCode,
                'relationship_type' => $relationshipType,
                'goal_keys' => $goalKeys,
                'evidence_summary' => (string) ($relationshipRow['evidence_summary'] ?? ''),
                'reason_text' => (string) ($relationshipRow['reason_text'] ?? ''),
                'source_refs' => $sourceRefs,
                'safety_gate' => $safetyGate,
                'sequence_order' => $sequenceOrder,
                'source_content_hash' => (string) ($relationshipRow['source_content_hash'] ?? ''),
                'review_status' => (string) ($relationshipRow['review_status'] ?? ''),
                'accessed_at' => (string) ($relationshipRow['accessed_at'] ?? ''),
            ]));
            $storedRelationshipHash = (string) ($relationshipRow['source_hash'] ?? '');
            if(
                !preg_match('/^[a-f0-9]{64}$/', $storedRelationshipHash)
                || !hash_equals($storedRelationshipHash, $expectedRelationshipHash)
            ) {
                $hardErrors[] = $relationshipLabel . ':relationship_source_hash_mismatch';
            }

            $counts['relationships']++;
        }

        $counts['available_offers'] = count($availableOfferKeys);
        $counts['blocked_offers'] = count($blockedOfferKeys);
        $counts['products'] = count($productLanguages);
        foreach($productGelKinds as $productKey => $kinds) {
            if(!empty($kinds['gel']) && !empty($kinds['gelly'])) {
                $hardErrors[] = 'product:' . $productKey . ':gel_gelly_identity_collision';
            }
        }
        $missingEnglish = 0;
        $missingCroatian = 0;
        foreach($productLanguages as $languages) {
            if(empty($languages['en'])) {
                $missingEnglish++;
            }
            if(empty($languages['hr'])) {
                $missingCroatian++;
            }
        }

        if($counts['lookup_only'] > 0) {
            $warnings[] = 'lookup_only_entries:' . $counts['lookup_only'];
        }
        if($counts['blocked_offers'] > 0) {
            $warnings[] = 'blocked_legacy_offers:' . $counts['blocked_offers'];
        }
        if($counts['missing_sku'] > 0) {
            $warnings[] = 'entries_missing_sku:' . $counts['missing_sku'];
        }
        if($counts['missing_structured_facts'] > 0) {
            $warnings[] = 'entries_missing_structured_facts:' . $counts['missing_structured_facts'];
        }
        if($counts['unknown_route'] > 0) {
            $warnings[] = 'entries_with_unknown_route:' . $counts['unknown_route'];
        }
        if($counts['missing_article_url'] > 0) {
            $warnings[] = 'entries_missing_article_url:' . $counts['missing_article_url'];
        }
        if($counts['without_available_offer'] > 0) {
            $warnings[] = 'entries_without_available_offer:' . $counts['without_available_offer'];
        }
        if($counts['unverified_sku'] > 0) {
            $warnings[] = 'entries_with_unverified_sku_matcher_disabled:' . $counts['unverified_sku'];
        }
        if($counts['without_verified_facts'] > 0) {
            $warnings[] = 'entries_without_exact_verified_fact_overlay:' . $counts['without_verified_facts'];
        }
        if($missingEnglish > 0) {
            $warnings[] = 'products_missing_en_localization:' . $missingEnglish;
        }
        if($missingCroatian > 0) {
            $warnings[] = 'products_missing_hr_localization:' . $missingCroatian;
        }

        $hardErrors = array_values(array_unique($hardErrors));
        $warnings = array_values(array_unique($warnings));

        return [
            'valid' => empty($hardErrors),
            'revision_id' => $revisionId,
            'revision_key' => (string) ($revision['revision_key'] ?? ''),
            'source_hash' => (string) ($revision['source_hash'] ?? ''),
            'hard_errors' => $hardErrors,
            'warnings' => $warnings,
            'counts' => $counts,
        ];
    }

    /**
     * Publishes a validated revision and archives any older published snapshot.
     */
    public function publishRevision(int $revisionId, int $actorUserId = 0): array {
        $now = $this->now();
        \db()->startTransaction();

        try {
            $revision = $this->fetchOne(
                'SELECT * FROM `' . self::REVISION_TABLE . '` WHERE `revision_id` = ? FOR UPDATE',
                [$revisionId]
            );

            if(!$revision) {
                throw new \RuntimeException(fcc_t('Catalog revision disappeared before publish.'));
            }

            if(!in_array((string) ($revision['status'] ?? ''), ['draft', 'archived', 'published'], true)) {
                throw new \RuntimeException(fcc_t('Catalog revision status is not publishable.'));
            }

            /* Lock the immutable snapshot before validating it. The parent row
             * lock also prevents concurrent FK child inserts until commit. */
            $this->fetchAll(
                'SELECT `entry_id` FROM `' . self::ENTRY_TABLE . '` WHERE `revision_id` = ? FOR UPDATE',
                [$revisionId]
            );
            $this->fetchAll(
                'SELECT `relationship_id` FROM `' . self::RELATIONSHIP_TABLE . '` WHERE `revision_id` = ? FOR UPDATE',
                [$revisionId]
            );

            $validation = $this->validateRevision($revisionId);
            if(empty($validation['valid'])) {
                \db()->rollback();
                return [
                    'published' => false,
                    'revision_id' => $revisionId,
                    'validation' => $validation,
                ];
            }
            $validationJson = $this->encodeJson($validation);

            $this->execute(
                'UPDATE `' . self::REVISION_TABLE . '` SET `status` = ?, `updated_at` = ? WHERE `status` = ? AND `revision_id` <> ?',
                ['archived', $now, 'published', $revisionId]
            );
            $this->execute(
                'UPDATE `' . self::REVISION_TABLE . '` SET `status` = ?, `validation_json` = ?, `published_by_user_id` = ?, `published_at` = COALESCE(`published_at`, ?), `updated_at` = ? WHERE `revision_id` = ?',
                ['published', $validationJson, $actorUserId > 0 ? $actorUserId : null, $now, $now, $revisionId]
            );

            \db()->commit();
        } catch(\Throwable $exception) {
            \db()->rollback();
            throw $exception;
        }

        $this->runtimeRevisionCache = [];

        return [
            'published' => true,
            'revision_id' => $revisionId,
            'revision' => $this->getPublishedRevision(),
            'validation' => $validation,
        ];
    }

    /**
     * Imports a legacy blog/webshop snapshot into an immutable catalog revision.
     */
    public function importLegacySnapshot(string $revisionKey, bool $publish = false, int $actorUserId = 0): array {
        if(!$this->schemaIsReady()) {
            throw new \RuntimeException(fcc_t('FCC catalog V2 schema is not ready. Apply scripts/enable_fcc_product_advisor_v2_2026_08_25.sql first.'));
        }

        $revisionKey = trim($revisionKey);
        if(!$this->isValidRevisionKey($revisionKey)) {
            throw new \InvalidArgumentException(fcc_t('Revision key must contain 1-128 letters, digits, dots, underscores, colons or hyphens.'));
        }

        $legacyRows = $this->loadLegacyRows();
        if(empty($legacyRows)) {
            throw new \RuntimeException(fcc_t('No published legacy product articles with webshop links were found.'));
        }

        $verifiedFactsOverlay = $this->loadVerifiedFactsOverlay();
        $verifiedFactsOverlayHash = hash('sha256', $this->canonicalJson($verifiedFactsOverlay));
        $officialSourcesManifest = $this->loadOfficialSourcesManifest();
        $officialSourcesManifestHash = hash('sha256', $this->canonicalJson($officialSourcesManifest));
        $officialManifestValidation = $this->validateOfficialSourceManifest();
        if(!$officialManifestValidation['valid']) {
            throw new \RuntimeException(
                'Official FCC source manifest has hard errors: '
                . implode('; ', array_slice((array) ($officialManifestValidation['hard_errors'] ?? []), 0, 20))
            );
        }
        $snapshotHash = hash('sha256', $this->canonicalJson([
            'importer_version' => self::IMPORTER_VERSION,
            'legacy_rows' => $legacyRows,
            'verified_facts_overlay' => $verifiedFactsOverlay,
            'official_sources_manifest' => $officialSourcesManifest,
        ]));
        $existing = $this->getRevisionByKey($revisionKey);
        if($existing) {
            if(!hash_equals((string) ($existing['source_hash'] ?? ''), $snapshotHash)) {
                throw new \RuntimeException(fcc_t('Revision key already exists for a different legacy snapshot. Use a new revision key.'));
            }

            $validation = $this->validateRevision((int) $existing['revision_id']);
            $publishResult = null;
            if($publish && ($existing['status'] ?? '') !== 'published' && $validation['valid']) {
                $publishResult = $this->publishRevision((int) $existing['revision_id'], $actorUserId);
            }

            return [
                'imported' => false,
                'reused' => true,
                'revision' => $this->getRevisionByKey($revisionKey),
                'validation' => $validation,
                'publish' => $publishResult,
            ];
        }

        [$productRows, $contentRows] = $this->splitLegacyRows($legacyRows);
        $groups = $this->groupLegacyProductRows($productRows);
        $contentLinks = $this->mapContentRowsToProductGroups($contentRows, $groups);
        $overlayCoverage = $this->inspectVerifiedOverlayCoverage($verifiedFactsOverlay, $groups);
        $officialSourcesByProduct = [];
        foreach($officialSourcesManifest['sources'] as $officialSource) {
            $officialSourcesByProduct[(string) ($officialSource['product_key'] ?? '')][] = $officialSource;
        }
        $identityMigrationsByProduct = [];
        foreach($officialSourcesManifest['identity_migrations'] as $identityMigration) {
            if((string) ($identityMigration['review_status'] ?? '') !== 'approved') {
                continue;
            }
            $migrationProductKey = (string) ($identityMigration['product_key'] ?? '');
            if($migrationProductKey !== '') {
                $identityMigrationsByProduct[$migrationProductKey] = $identityMigration;
            }
        }
        $inventoryFamiliesByProduct = [];
        foreach((array) ($officialSourcesManifest['inventory']['inventory_families'] ?? []) as $inventoryFamily) {
            if(!is_array($inventoryFamily)) {
                continue;
            }
            $inventoryProductKey = trim((string) ($inventoryFamily['product_key'] ?? ''));
            if($inventoryProductKey !== '') {
                $inventoryFamiliesByProduct[$inventoryProductKey] = $inventoryFamily;
            }
        }
        $retiredInventoryByProduct = [];
        foreach((array) ($officialSourcesManifest['inventory']['retired_or_orphaned'] ?? []) as $retiredRecord) {
            if(!is_array($retiredRecord)) {
                continue;
            }
            $retiredProductKey = trim((string) ($retiredRecord['product_key'] ?? ''));
            if($retiredProductKey !== '') {
                $retiredInventoryByProduct[$retiredProductKey] = $retiredRecord;
            }
        }
        $entries = [];
        $importWarnings = array_merge(
            $overlayCoverage['warnings'],
            (array) ($officialManifestValidation['warnings'] ?? [])
        );
        $skippedRows = [];

        foreach($groups as $productKey => $groupRows) {
            $built = $this->buildEntriesForGroup(
                $productKey,
                $groupRows,
                $contentLinks[$productKey] ?? [],
                $verifiedFactsOverlay[$productKey] ?? [],
                $officialSourcesByProduct[$productKey] ?? [],
                $identityMigrationsByProduct[$productKey] ?? null,
                $inventoryFamiliesByProduct[$productKey] ?? null,
                $retiredInventoryByProduct[$productKey] ?? null,
                (string) ($officialSourcesManifest['inventory']['normalized_snapshot_sha256'] ?? '')
            );
            foreach($built['entries'] as $entry) {
                $entries[] = $entry;
            }
            $importWarnings = array_merge($importWarnings, $built['warnings']);
            $skippedRows = array_merge($skippedRows, $built['skipped_rows']);
        }
        $officialOnlyEntries = 0;
        foreach($officialSourcesManifest['canonical_products'] as $canonicalProduct) {
            if((string) ($canonicalProduct['review_status'] ?? '') !== 'approved') {
                continue;
            }
            $entry = $this->buildOfficialCanonicalEntry(
                $canonicalProduct,
                $officialSourcesByProduct[(string) ($canonicalProduct['product_key'] ?? '')] ?? []
            );
            if($entry !== null) {
                $entries[] = $entry;
                $officialOnlyEntries++;
            }
        }

        if(empty($entries)) {
            throw new \RuntimeException(fcc_t('Legacy snapshot did not produce any canonical catalog entries.'));
        }

        $now = $this->now();
        $summary = [
            'importer_version' => self::IMPORTER_VERSION,
            'verified_facts_overlay_hash' => $verifiedFactsOverlayHash,
            'official_sources_manifest_hash' => $officialSourcesManifestHash,
            'official_source_schema_version' => (string) ($officialSourcesManifest['schema_version'] ?? ''),
            'official_source_counts' => (array) ($officialManifestValidation['counts'] ?? []),
            'verified_overlay_products' => count($verifiedFactsOverlay),
            'verified_overlay_localizations' => (int) $overlayCoverage['total_localizations'],
            'matched_verified_overlay_localizations' => (int) $overlayCoverage['matched_localizations'],
            'unmatched_verified_overlay_localizations' => (int) $overlayCoverage['unmatched_localizations'],
            'legacy_rows' => count($legacyRows),
            'product_rows' => count($productRows),
            'content_rows' => count($contentRows),
            'product_groups' => count($groups),
            'approved_identity_migrations' => count($identityMigrationsByProduct),
            'materialized_inventory_families' => count($inventoryFamiliesByProduct),
            'retired_or_orphaned_legacy_families' => count($retiredInventoryByProduct),
            'official_only_entries' => $officialOnlyEntries,
            'entries' => count($entries),
            'warnings' => array_values(array_unique($importWarnings)),
            'skipped_rows' => array_values($skippedRows),
        ];

        \db()->startTransaction();
        try {
            $this->execute(
                'INSERT INTO `' . self::REVISION_TABLE . '` (`revision_key`, `status`, `source_type`, `source_hash`, `bundle_hash`, `entry_count`, `summary_json`, `imported_by_user_id`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $revisionKey,
                    'draft',
                    'legacy_blog_posts',
                    $snapshotHash,
                    null,
                    count($entries),
                    $this->encodeJson($summary),
                    $actorUserId > 0 ? $actorUserId : null,
                    $now,
                    $now,
                ]
            );
            $revisionId = (int) \database()->insert_id;
            if($revisionId <= 0) {
                throw new \RuntimeException(fcc_t('Catalog revision insert did not return an ID.'));
            }

            foreach($entries as $entry) {
                $this->insertCatalogEntry($revisionId, $entry, $now);
            }
            foreach($officialSourcesManifest['relationships'] as $relationship) {
                if((string) ($relationship['review_status'] ?? '') === 'approved') {
                    $this->insertCatalogRelationship($revisionId, $relationship, $now);
                }
            }

            \db()->commit();
        } catch(\Throwable $exception) {
            \db()->rollback();
            throw $exception;
        }

        /*
         * Calculate the immutable digest only after the write transaction is
         * committed. FCC installations may use a separate read connection,
         * which cannot see the new rows while the write transaction is open.
         */
        $bundleHash = $this->calculateRevisionBundleHash($revisionId);
        $this->execute(
            'UPDATE `' . self::REVISION_TABLE . '` SET `bundle_hash` = ? WHERE `revision_id` = ?',
            [$bundleHash, $revisionId]
        );

        $summary['bundle_hash'] = $bundleHash ?? '';
        $validation = $this->validateRevision($revisionId);
        $summary['validation'] = $validation;
        $this->execute(
            'UPDATE `' . self::REVISION_TABLE . '` SET `lookup_only_count` = ?, `blocked_offer_count` = ?, `summary_json` = ?, `validation_json` = ?, `updated_at` = ? WHERE `revision_id` = ?',
            [
                (int) ($validation['counts']['lookup_only'] ?? 0),
                (int) ($validation['counts']['blocked_offers'] ?? 0),
                $this->encodeJson($summary),
                $this->encodeJson($validation),
                $this->now(),
                $revisionId,
            ]
        );

        $publishResult = null;
        if($publish && $validation['valid']) {
            $publishResult = $this->publishRevision($revisionId, $actorUserId);
        }

        return [
            'imported' => true,
            'reused' => false,
            'revision' => $this->getRevisionByKey($revisionKey),
            'summary' => $summary,
            'validation' => $validation,
            'publish' => $publishResult,
        ];
    }

    private function hydrateRevision(array $row): array {
        $summaryValid = true;
        $validationValid = true;

        return [
            'revision_id' => (int) ($row['revision_id'] ?? 0),
            'revision_key' => (string) ($row['revision_key'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
            'source_type' => (string) ($row['source_type'] ?? ''),
            'source_hash' => (string) ($row['source_hash'] ?? ''),
            'bundle_hash' => (string) ($row['bundle_hash'] ?? ''),
            'entry_count' => (int) ($row['entry_count'] ?? 0),
            'lookup_only_count' => (int) ($row['lookup_only_count'] ?? 0),
            'blocked_offer_count' => (int) ($row['blocked_offer_count'] ?? 0),
            'summary' => $this->decodeJsonObject($row['summary_json'] ?? null, $summaryValid),
            'validation' => $this->decodeJsonObject($row['validation_json'] ?? null, $validationValid),
            'imported_by_user_id' => (int) ($row['imported_by_user_id'] ?? 0),
            'published_by_user_id' => (int) ($row['published_by_user_id'] ?? 0),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
            'published_at' => (string) ($row['published_at'] ?? ''),
        ];
    }

    /**
     * Runtime relationship contract consumed by the advisor core. Only exact
     * language/market approved rows reach this hydrator.
     */
    private function hydrateRelationship(array $row): array {
        $goalKeysValid = true;
        $sourceRefsValid = true;
        $safetyGateValid = true;

        return [
            'relation_id' => (int) ($row['relationship_id'] ?? 0),
            'relation_type' => (string) ($row['relationship_type'] ?? ''),
            'target_product_key' => (string) ($row['target_product_key'] ?? ''),
            'goal_keys' => $this->decodeJsonList($row['goal_keys'] ?? null, $goalKeysValid),
            'evidence_summary' => (string) ($row['evidence_summary'] ?? ''),
            'reason_text' => (string) ($row['reason_text'] ?? ''),
            'source_refs' => $this->decodeJsonList($row['source_refs'] ?? null, $sourceRefsValid),
            'market_code' => (string) ($row['market_code'] ?? ''),
            'safety_gate' => $this->decodeJsonObject($row['safety_gate'] ?? null, $safetyGateValid),
            'sequence_order' => (int) ($row['sequence_order'] ?? 0),
        ];
    }

    private function hydrateCatalogEntry(
        array $row,
        int $revisionId,
        string $marketCode,
        string $requestedLanguageCode = ''
    ): array {
        $decoded = [];
        foreach(self::JSON_LIST_FIELDS as $field) {
            $valid = true;
            $decoded[$field] = $this->decodeJsonList($row[$field] ?? null, $valid);
        }

        $validOffers = true;
        $marketOffers = $this->decodeJsonObject($row['market_offers'] ?? null, $validOffers);
        $decoded['offer_variants'] = array_values(array_filter(
            $decoded['offer_variants'],
            fn($variant): bool => is_array($variant)
                && $this->normalizeMarketCode((string) ($variant['market_code'] ?? '')) === $marketCode
                && (string) ($variant['review_status'] ?? '') === 'approved'
        ));
        $offer = $marketCode !== '' && isset($marketOffers[$marketCode]) && is_array($marketOffers[$marketCode])
            ? $marketOffers[$marketCode]
            : null;
        $availabilityStatus = $offer && in_array((string) ($offer['availability_status'] ?? ''), self::ALLOWED_AVAILABILITY_STATUSES, true)
            ? (string) $offer['availability_status']
            : 'unknown';
        $advisorStatus = (string) ($row['advisor_status'] ?? 'lookup_only');
        if($advisorStatus === 'blocked') {
            $availabilityStatus = 'blocked';
        } elseif($advisorStatus === 'recommendable' && $availabilityStatus !== 'available') {
            /* Recommendation readiness is always scoped to the exact market. */
            $advisorStatus = 'lookup_only';
        }
        $officialUrl = $availabilityStatus === 'available'
            ? trim((string) ($offer['official_url'] ?? ''))
            : '';
        $selectedLanguageCode = $this->normalizeLanguageCode((string) ($row['language_code'] ?? ''));
        $isLanguageFallback = $requestedLanguageCode !== '' && $selectedLanguageCode !== $requestedLanguageCode;
        $officialFactMarkets = [];
        $freshOfficialFactMarkets = [];
        foreach($decoded['source_refs'] as $sourceRef) {
            if(
                is_array($sourceRef)
                && (string) ($sourceRef['source_type'] ?? '') === 'official_product_source'
                && (string) ($sourceRef['review_status'] ?? '') === 'approved'
                && (
                    (int) ($sourceRef['verified_fact_count'] ?? 0) > 0
                    || (int) ($sourceRef['verified_usage_count'] ?? 0) > 0
                    || (int) ($sourceRef['verified_safety_count'] ?? 0) > 0
                    || (string) ($sourceRef['safety_review_status'] ?? 'unknown') === 'complete'
                )
            ) {
                $factMarket = $this->normalizeMarketCode((string) ($sourceRef['market_code'] ?? ''));
                if($factMarket !== '') {
                    $officialFactMarkets[$factMarket] = true;
                    if($this->isAccessedAtFresh(
                        (string) ($sourceRef['accessed_at'] ?? ''),
                        self::RECOMMENDATION_SOURCE_MAX_AGE_DAYS
                    )) {
                        $freshOfficialFactMarkets[$factMarket] = true;
                    }
                }
            }
        }
        $isOfficialFactMarketMismatch = !empty($officialFactMarkets) && empty($officialFactMarkets[$marketCode]);
        $isOfficialFactStale = !empty($officialFactMarkets[$marketCode]) && empty($freshOfficialFactMarkets[$marketCode]);
        if($isLanguageFallback) {
            /* Display fallback is allowed; verified factual content is not. */
            foreach(['use_cases', 'ingredient_facts', 'approved_support_facts', 'usage_facts', 'safety_notes', 'allergens_contains', 'safety_flags', 'mandatory_safety_notes'] as $field) {
                $decoded[$field] = [];
            }
            $safetyReviewStatus = 'unknown';
            $suitabilityReviewStatus = 'unknown';
            $claimReviewStatus = 'unknown';
            $decoded['suitability_flags'] = [];
            $decoded['aliases'] = [(string) ($row['canonical_name'] ?? '')];
            if($advisorStatus !== 'blocked') {
                $advisorStatus = 'lookup_only';
            }
        } elseif($isOfficialFactMarketMismatch || $isOfficialFactStale) {
            foreach(['use_cases', 'ingredient_facts', 'approved_support_facts', 'usage_facts', 'safety_notes', 'allergens_contains', 'safety_flags', 'mandatory_safety_notes'] as $field) {
                $decoded[$field] = [];
            }
            $safetyReviewStatus = 'unknown';
            $suitabilityReviewStatus = 'unknown';
            $claimReviewStatus = 'unknown';
            $decoded['suitability_flags'] = [];
            if($advisorStatus !== 'blocked') {
                $advisorStatus = 'lookup_only';
            }
        }

        $sourceFreshnessStatus = $isOfficialFactMarketMismatch || empty($officialFactMarkets[$marketCode])
            ? 'unknown'
            : ($isOfficialFactStale ? 'stale' : 'fresh');

        return [
            'product_key' => (string) ($row['product_key'] ?? ''),
            'canonical_name' => (string) ($row['canonical_name'] ?? ''),
            'sku' => (string) ($row['sku'] ?? ''),
            'category_key' => (string) ($row['category_key'] ?? ''),
            'route' => (string) ($row['route'] ?? 'unknown'),
            'advisor_status' => $advisorStatus,
            'availability_status' => $availabilityStatus,
            'aliases' => $decoded['aliases'],
            'use_cases' => $decoded['use_cases'],
            'ingredient_facts' => $decoded['ingredient_facts'],
            'approved_support_facts' => $decoded['approved_support_facts'],
            'usage_facts' => $decoded['usage_facts'],
            'safety_notes' => $decoded['safety_notes'],
            'allergens_contains' => $decoded['allergens_contains'],
            'safety_flags' => $decoded['safety_flags'],
            'mandatory_safety_notes' => $decoded['mandatory_safety_notes'],
            'safety_review_status' => $safetyReviewStatus ?? (string) ($row['safety_review_status'] ?? 'unknown'),
            'suitability_flags' => $decoded['suitability_flags'],
            'suitability_review_status' => $suitabilityReviewStatus ?? (string) ($row['suitability_review_status'] ?? 'unknown'),
            'claim_review_status' => $claimReviewStatus ?? (string) ($row['claim_review_status'] ?? 'unknown'),
            'source_freshness_status' => $sourceFreshnessStatus,
            'offer_variants' => $decoded['offer_variants'],
            'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
            'article_url' => (string) ($row['article_url'] ?? ''),
            'official_url' => $officialUrl,
            'source_refs' => $decoded['source_refs'],
            'source_hash' => (string) ($row['source_hash'] ?? ''),
            'language_code' => $selectedLanguageCode,
            'market_code' => $marketCode,
            'revision_id' => $revisionId,
        ];
    }

    private function selectLocalizedEntry(array $rows, string $languageCode): ?array {
        if(empty($rows)) {
            return null;
        }

        $priority = array_values(array_unique(array_filter([$languageCode, 'hr', 'en'])));
        foreach($priority as $candidateLanguage) {
            foreach($rows as $row) {
                if($this->normalizeLanguageCode((string) ($row['language_code'] ?? '')) === $candidateLanguage) {
                    return $row;
                }
            }
        }

        return $rows[0];
    }

    /**
     * Load the reviewed fact overlay as an exact product_key/language map.
     * Canonical match signals are retained for audit only and never used to
     * guess an identity or language mapping.
     */
    private function loadVerifiedFactsOverlay(): array {
        $appPath = defined('APP_PATH') ? (string) APP_PATH : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $path = rtrim($appPath, '/\\') . '/config/fcc_product_catalog_verified_facts.php';
        if(!is_file($path)) {
            throw new \RuntimeException('Verified FCC product facts overlay is missing: ' . $path);
        }

        $raw = require $path;
        if(!is_array($raw)) {
            throw new \RuntimeException(fcc_t('Verified FCC product facts overlay must return an array.'));
        }

        $overlay = [];
        $stringListFields = [
            'canonical_match_signals', 'aliases', 'use_cases', 'ingredient_facts',
            'approved_support_facts', 'usage_facts', 'safety_notes',
        ];

        foreach($raw as $productKey => $languages) {
            $productKey = trim((string) $productKey);
            if($productKey === '' || !is_array($languages)) {
                throw new \RuntimeException(fcc_t('Verified facts overlay contains an invalid product entry.'));
            }

            foreach($languages as $languageCode => $record) {
                $languageCode = $this->normalizeLanguageCode((string) $languageCode);
                if($languageCode === '' || !is_array($record)) {
                    throw new \RuntimeException('Verified facts overlay contains an invalid language record for ' . $productKey . '.');
                }
                if(isset($overlay[$productKey][$languageCode])) {
                    throw new \RuntimeException('Verified facts overlay contains a duplicate exact key: ' . $productKey . '|' . $languageCode . '.');
                }

                $normalized = [];
                foreach($stringListFields as $field) {
                    if(isset($record[$field]) && !is_array($record[$field])) {
                        throw new \RuntimeException('Verified facts field must be a list: ' . $productKey . '|' . $languageCode . '|' . $field . '.');
                    }
                    $normalized[$field] = $this->cleanStringList($record[$field] ?? []);
                }

                if(isset($record['source_refs']) && !is_array($record['source_refs'])) {
                    throw new \RuntimeException('Verified facts source_refs must be a list: ' . $productKey . '|' . $languageCode . '.');
                }
                $normalized['source_refs'] = [];
                foreach((array) ($record['source_refs'] ?? []) as $sourceRef) {
                    if(!is_array($sourceRef)) {
                        throw new \RuntimeException('Verified facts source reference must be an object: ' . $productKey . '|' . $languageCode . '.');
                    }
                    $normalized['source_refs'][] = $sourceRef;
                }

                $overlay[$productKey][$languageCode] = $normalized;
            }
            ksort($overlay[$productKey], SORT_STRING);
        }

        ksort($overlay, SORT_STRING);
        return $overlay;
    }

    /**
     * Load official evidence without guessing identity. Records stay keyed by
     * exact product/language/market and keep their review state; only approved
     * records are eligible for fact or relationship import.
     */
    private function loadOfficialSourcesManifest(): array {
        $appPath = defined('APP_PATH') ? (string) APP_PATH : dirname(__DIR__) . DIRECTORY_SEPARATOR;
        $path = rtrim($appPath, '/\\') . '/config/fcc_product_catalog_official_sources.php';
        if(!is_file($path)) {
            throw new \RuntimeException('Official FCC product source manifest is missing: ' . $path);
        }

        $raw = require $path;
        if(
            !is_array($raw)
            || !is_array($raw['canonical_products'] ?? [])
            || !is_array($raw['sources'] ?? null)
            || !is_array($raw['relationships'] ?? null)
        ) {
            throw new \RuntimeException(fcc_t('Official FCC product source manifest must return sources and relationships lists.'));
        }

        $inventoryMetadata = [];
        $canonicalProductRecords = (array) ($raw['canonical_products'] ?? []);
        $identityMigrationRecords = (array) ($raw['identity_migrations'] ?? []);
        $relationshipRecords = (array) ($raw['relationships'] ?? []);
        $claimReviewMetadata = [];
        $claimReviewRecords = [];
        $inventoryPath = rtrim($appPath, '/\\') . '/config/fcc_product_catalog_official_inventory.php';
        if(is_file($inventoryPath)) {
            $inventory = require $inventoryPath;
            if(!is_array($inventory) || !is_array($inventory['canonical_products'] ?? null)) {
                throw new \RuntimeException(fcc_t('Official FCC inventory manifest must return canonical_products.'));
            }
            $canonicalProductRecords = array_merge($canonicalProductRecords, $inventory['canonical_products']);
            $identityMigrationRecords = array_merge(
                $identityMigrationRecords,
                (array) ($inventory['identity_migrations'] ?? [])
            );
            $inventoryMetadata = array_diff_key($inventory, [
                'canonical_products' => true,
                'identity_migrations' => true,
            ]);
        }
        $relationshipsPath = rtrim($appPath, '/\\') . '/config/fcc_product_catalog_official_relationships.php';
        if(is_file($relationshipsPath)) {
            $relationshipManifest = require $relationshipsPath;
            if(!is_array($relationshipManifest) || !is_array($relationshipManifest['relationships'] ?? null)) {
                throw new \RuntimeException(fcc_t('Official FCC relationship manifest must return relationships.'));
            }
            $relationshipRecords = array_merge($relationshipRecords, $relationshipManifest['relationships']);
        }
        $claimReviewsPath = rtrim($appPath, '/\\') . '/config/fcc_product_catalog_claim_reviews.php';
        if(is_file($claimReviewsPath)) {
            $claimReviews = require $claimReviewsPath;
            if(!is_array($claimReviews) || !is_array($claimReviews['records'] ?? null)) {
                throw new \RuntimeException(fcc_t('Official FCC claim-review manifest must return records.'));
            }
            $policyVersion = trim((string) ($claimReviews['policy_version'] ?? ''));
            if($policyVersion !== 'fcc-product-facts-compliance-v1') {
                throw new \RuntimeException(fcc_t('Unsupported FCC claim-review policy version.'));
            }
            foreach($claimReviews['records'] as $recordKey => $claimReview) {
                if(!is_array($claimReview)) {
                    throw new \RuntimeException('Official FCC claim review must be an object: ' . (string) $recordKey . '.');
                }
                $claimProductKey = trim((string) ($claimReview['product_key'] ?? ''));
                if(
                    !$this->isValidProductKey($claimProductKey)
                    || !hash_equals((string) $recordKey, $claimProductKey)
                    || isset($claimReviewRecords[$claimProductKey])
                ) {
                    throw new \RuntimeException('Invalid or duplicate FCC claim-review product key: ' . $claimProductKey . '.');
                }
                $claimReviewRecords[$claimProductKey] = $claimReview;
            }
            $claimReviewMetadata = [
                'schema_version' => trim((string) ($claimReviews['schema_version'] ?? '')),
                'policy_version' => $policyVersion,
                'reviewed_at' => trim((string) ($claimReviews['reviewed_at'] ?? '')),
                'record_count' => count($claimReviewRecords),
                'config_hash' => hash('sha256', $this->canonicalJson($claimReviews)),
            ];
        }

        $canonicalProducts = [];
        foreach($canonicalProductRecords as $index => $record) {
            if(!is_array($record)) {
                throw new \RuntimeException('Official canonical product must be an object at index ' . $index . '.');
            }
            $productKey = trim((string) ($record['product_key'] ?? ''));
            $languageCode = $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''));
            if(!$this->isValidProductKey($productKey) || $languageCode === '') {
                throw new \RuntimeException('Official canonical product has invalid identity at index ' . $index . '.');
            }
            $offerVariants = [];
            foreach((array) ($record['offer_variants'] ?? []) as $variantIndex => $variant) {
                if(!is_array($variant)) {
                    throw new \RuntimeException('Official offer variant must be an object: ' . $productKey . ':' . $variantIndex . '.');
                }
                $offerVariants[] = [
                    'offer_key' => trim((string) ($variant['offer_key'] ?? '')),
                    'market_code' => $this->normalizeMarketCode((string) ($variant['market_code'] ?? '')),
                    'item_code' => $this->normalizeProductCode((string) ($variant['item_code'] ?? '')),
                    'identity_slug' => $this->slugify((string) ($variant['identity_slug'] ?? '')),
                    'identity_review_note' => trim((string) ($variant['identity_review_note'] ?? '')),
                    'package_label' => trim((string) ($variant['package_label'] ?? '')),
                    'official_url' => $this->normalizeOfferUrl((string) ($variant['official_url'] ?? '')),
                    'availability_status' => trim((string) ($variant['availability_status'] ?? 'unknown')),
                    'is_primary' => ($variant['is_primary'] ?? false) === true,
                    'accessed_at' => trim((string) ($variant['accessed_at'] ?? '')),
                    'content_sha256' => mb_strtolower(trim((string) ($variant['content_sha256'] ?? ''))),
                    'review_status' => trim((string) ($variant['review_status'] ?? 'candidate')),
                ];
            }
            $sourceRefs = [];
            foreach((array) ($record['source_refs'] ?? []) as $sourceRef) {
                if(!is_array($sourceRef)) {
                    throw new \RuntimeException('Official canonical source_refs must contain objects: ' . $productKey . '.');
                }
                $sourceRefs[] = $sourceRef;
            }
            $canonicalProducts[] = [
                'product_key' => $productKey,
                'language_code' => $languageCode,
                'canonical_name' => trim((string) ($record['canonical_name'] ?? '')),
                'sku' => $this->normalizeSku((string) ($record['sku'] ?? '')),
                'category_key' => $this->slugify((string) ($record['category_key'] ?? '')),
                'route' => trim((string) ($record['route'] ?? 'unknown')),
                'aliases' => $this->cleanStringList($record['aliases'] ?? []),
                'review_status' => trim((string) ($record['review_status'] ?? 'candidate')),
                'offer_variants' => $offerVariants,
                'source_refs' => $sourceRefs,
            ];
        }

        $sources = [];
        $sourceIds = [];
        $appliedClaimReviews = [];
        foreach($raw['sources'] as $index => $record) {
            if(!is_array($record)) {
                throw new \RuntimeException('Official source record must be an object at index ' . $index . '.');
            }
            $productKey = trim((string) ($record['product_key'] ?? ''));
            $languageCode = $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''));
            $marketCode = $this->normalizeMarketCode((string) ($record['market_code'] ?? ''));
            if(!$this->isValidProductKey($productKey) || $languageCode === '' || $marketCode === '') {
                throw new \RuntimeException('Official source record has invalid exact identity at index ' . $index . '.');
            }

            $normalized = [
                'source_id' => trim((string) ($record['source_id'] ?? '')),
                'product_key' => $productKey,
                'language_code' => $languageCode,
                'market_code' => $marketCode,
                'source_type' => trim((string) ($record['source_type'] ?? 'official_product_page')),
                'source_url' => trim((string) ($record['source_url'] ?? '')),
                'accessed_at' => trim((string) ($record['accessed_at'] ?? '')),
                'content_sha256' => mb_strtolower(trim((string) ($record['content_sha256'] ?? ''))),
                'evidence_sha256' => mb_strtolower(trim((string) ($record['evidence_sha256'] ?? ''))),
                'review_status' => trim((string) ($record['review_status'] ?? 'candidate')),
                'review_scope' => trim((string) ($record['review_scope'] ?? 'none')),
                'identity_status' => trim((string) ($record['identity_status'] ?? 'unverified')),
                'identity_review_note' => trim((string) ($record['identity_review_note'] ?? '')),
                'page_name' => trim((string) ($record['page_name'] ?? '')),
                'page_item_code' => $this->normalizeProductCode((string) ($record['page_item_code'] ?? '')),
                'page_category' => trim((string) ($record['page_category'] ?? '')),
                'page_route' => trim((string) ($record['page_route'] ?? 'unknown')),
                'route_override_review_note' => trim((string) ($record['route_override_review_note'] ?? '')),
                'is_primary_for_language' => ($record['is_primary_for_language'] ?? false) === true,
                'replaces_legacy_offer_url' => ($record['replaces_legacy_offer_url'] ?? false) === true,
                'replacement_review_note' => trim((string) ($record['replacement_review_note'] ?? '')),
                'aliases' => $this->cleanStringList($record['aliases'] ?? []),
                'use_cases' => $this->cleanStringList($record['use_cases'] ?? []),
                'ingredient_facts' => $this->cleanOfficialEvidenceList($record['ingredient_facts'] ?? []),
                'approved_support_facts' => $this->cleanOfficialEvidenceList($record['approved_support_facts'] ?? []),
                'usage_facts' => $this->cleanOfficialEvidenceList($record['usage_facts'] ?? []),
                'safety_notes' => $this->cleanOfficialEvidenceList($record['safety_notes'] ?? []),
                'allergens_contains' => $this->cleanStringList($record['allergens_contains'] ?? []),
                'safety_flags' => $this->cleanStringList($record['safety_flags'] ?? []),
                'mandatory_safety_notes' => $this->cleanOfficialEvidenceList($record['mandatory_safety_notes'] ?? []),
                'safety_review_status' => trim((string) ($record['safety_review_status'] ?? 'unknown')),
                'suitability_flags' => $this->cleanStringList($record['suitability_flags'] ?? []),
                'suitability_review_status' => trim((string) ($record['suitability_review_status'] ?? 'unknown')),
                'claim_review_status' => trim((string) ($record['claim_review_status'] ?? 'unknown')),
                'label_evidence' => [],
                'quarantine_reasons' => $this->cleanStringList($record['quarantine_reasons'] ?? []),
                'source_refs' => [],
            ];
            if(isset($record['label_evidence']) && !is_array($record['label_evidence'])) {
                throw new \RuntimeException('Official label_evidence must be an object: ' . $productKey . '|' . $languageCode . '|' . $marketCode . '.');
            }
            $labelEvidence = is_array($record['label_evidence'] ?? null) ? $record['label_evidence'] : [];
            $sectionHashes = is_array($labelEvidence['section_sha256'] ?? null)
                ? $labelEvidence['section_sha256']
                : [];
            $normalized['label_evidence'] = [
                'extractor_version' => trim((string) ($labelEvidence['extractor_version'] ?? '')),
                'usage_text' => trim((string) ($labelEvidence['usage_text'] ?? '')),
                'ingredients_text' => trim((string) ($labelEvidence['ingredients_text'] ?? '')),
                'required_safety_suffixes' => $this->cleanOfficialEvidenceList($labelEvidence['required_safety_suffixes'] ?? []),
                'safety_extraction_status' => trim((string) ($labelEvidence['safety_extraction_status'] ?? '')),
                'section_sha256' => [
                    'usage' => mb_strtolower(trim((string) ($sectionHashes['usage'] ?? ''))),
                    'ingredients' => mb_strtolower(trim((string) ($sectionHashes['ingredients'] ?? ''))),
                ],
            ];
            foreach((array) ($record['source_refs'] ?? []) as $sourceRef) {
                if(!is_array($sourceRef)) {
                    throw new \RuntimeException('Official source_refs must contain objects: ' . $productKey . '|' . $languageCode . '|' . $marketCode . '.');
                }
                $normalized['source_refs'][] = $sourceRef;
            }
            if(isset($claimReviewRecords[$productKey])) {
                $claimReview = $claimReviewRecords[$productKey];
                $claimErrors = [];
                $claimLanguage = $this->normalizeLanguageCode((string) ($claimReview['language_code'] ?? ''));
                $claimMarket = $this->normalizeMarketCode((string) ($claimReview['market_code'] ?? ''));
                $claimUrl = $this->normalizeOfferUrl((string) ($claimReview['source_url'] ?? ''));
                $claimContentHash = mb_strtolower(trim((string) ($claimReview['content_sha256'] ?? '')));
                $claimEvidenceHash = mb_strtolower(trim((string) ($claimReview['evidence_sha256'] ?? '')));
                $claimSections = is_array($claimReview['section_sha256'] ?? null)
                    ? $claimReview['section_sha256']
                    : [];
                if($claimLanguage !== $languageCode || $claimMarket !== $marketCode) {
                    $claimErrors[] = 'language_or_market_mismatch';
                }
                if($claimUrl === '' || !hash_equals($this->normalizeOfferUrl($normalized['source_url']), $claimUrl)) {
                    $claimErrors[] = 'source_url_mismatch';
                }
                if(!hash_equals((string) $normalized['content_sha256'], $claimContentHash)) {
                    $claimErrors[] = 'content_sha256_mismatch';
                }
                if(!hash_equals((string) $normalized['evidence_sha256'], $claimEvidenceHash)) {
                    $claimErrors[] = 'evidence_sha256_mismatch';
                }
                foreach(['usage', 'ingredients'] as $section) {
                    $baseSectionHash = mb_strtolower(trim((string) ($normalized['label_evidence']['section_sha256'][$section] ?? '')));
                    $claimSectionHash = mb_strtolower(trim((string) ($claimSections[$section] ?? '')));
                    if(
                        !preg_match('/^[a-f0-9]{64}$/', $claimSectionHash)
                        || !hash_equals($baseSectionHash, $claimSectionHash)
                    ) {
                        $claimErrors[] = 'section_sha256_mismatch:' . $section;
                    }
                }
                if(
                    (string) ($claimReview['review_status'] ?? '') !== 'approved'
                    || (string) ($claimReview['review_scope'] ?? '') !== 'complete_recommendation_facts'
                    || (string) ($claimReview['policy_version'] ?? '') !== 'fcc-product-facts-compliance-v1'
                ) {
                    $claimErrors[] = 'approval_scope_or_policy_invalid';
                }
                if(
                    (string) ($normalized['review_status'] ?? '') !== 'approved'
                    || !in_array((string) ($normalized['identity_status'] ?? ''), ['verified_exact', 'verified_reviewed'], true)
                    || (string) ($normalized['safety_review_status'] ?? '') !== 'complete'
                ) {
                    $claimErrors[] = 'base_identity_or_safety_not_approved';
                }
                if(
                    trim((string) ($claimReview['accessed_at'] ?? '')) !== (string) $normalized['accessed_at']
                    || !$this->validAccessedAt((string) ($claimReview['accessed_at'] ?? ''))
                ) {
                    $claimErrors[] = 'accessed_at_mismatch';
                }
                $reviewedUseCases = $this->cleanStringList($claimReview['use_cases'] ?? []);
                $reviewedSupportFacts = $this->cleanOfficialEvidenceList($claimReview['approved_support_facts'] ?? []);
                if(!$reviewedUseCases || !$reviewedSupportFacts) {
                    $claimErrors[] = 'reviewed_claim_facts_missing';
                }
                if($claimErrors) {
                    throw new \RuntimeException(
                        'FCC claim review does not match exact official source ' . $productKey . ': '
                        . implode(',', array_values(array_unique($claimErrors)))
                    );
                }
                $normalized['use_cases'] = $reviewedUseCases;
                $normalized['approved_support_facts'] = $reviewedSupportFacts;
                $normalized['review_scope'] = 'complete_recommendation_facts';
                $normalized['claim_review_status'] = 'approved';
                $normalized['source_refs'][] = [
                    'source_type' => 'official_claim_review',
                    'config' => 'app/config/fcc_product_catalog_claim_reviews.php',
                    'policy_version' => 'fcc-product-facts-compliance-v1',
                    'language_code' => $languageCode,
                    'market_code' => $marketCode,
                    'source_url' => $claimUrl,
                    'content_sha256' => $claimContentHash,
                    'evidence_sha256' => $claimEvidenceHash,
                    'section_sha256' => [
                        'usage' => (string) ($claimSections['usage'] ?? ''),
                        'ingredients' => (string) ($claimSections['ingredients'] ?? ''),
                    ],
                    'accessed_at' => (string) ($claimReview['accessed_at'] ?? ''),
                    'reviewed_at' => (string) ($claimReviewMetadata['reviewed_at'] ?? ''),
                ];
                $appliedClaimReviews[$productKey] = true;
            }
            if($normalized['source_id'] === '') {
                $normalized['source_id'] = substr(hash('sha256', $this->canonicalJson([
                    $productKey, $languageCode, $marketCode, $normalized['source_type'], $normalized['source_url'],
                ])), 0, 24);
            }
            if(isset($sourceIds[$normalized['source_id']])) {
                throw new \RuntimeException('Duplicate official source_id: ' . $normalized['source_id'] . '.');
            }
            $sourceIds[$normalized['source_id']] = true;
            $sources[] = $normalized;
        }
        $missingClaimReviews = array_diff(array_keys($claimReviewRecords), array_keys($appliedClaimReviews));
        if($missingClaimReviews) {
            sort($missingClaimReviews, SORT_STRING);
            throw new \RuntimeException(
                'FCC claim reviews have no exact official source record: ' . implode(',', $missingClaimReviews)
            );
        }

        $identityMigrations = [];
        foreach($identityMigrationRecords as $index => $record) {
            if(!is_array($record)) {
                throw new \RuntimeException('Official identity migration must be an object at index ' . $index . '.');
            }
            $variants = [];
            foreach((array) ($record['offer_variants'] ?? []) as $variant) {
                if(!is_array($variant)) {
                    throw new \RuntimeException('Official identity migration offer variant must be an object at index ' . $index . '.');
                }
                $variants[] = [
                    'offer_key' => trim((string) ($variant['offer_key'] ?? '')),
                    'market_code' => $this->normalizeMarketCode((string) ($variant['market_code'] ?? '')),
                    'item_code' => $this->normalizeProductCode((string) ($variant['item_code'] ?? '')),
                    'identity_slug' => $this->slugify((string) ($variant['identity_slug'] ?? '')),
                    'identity_review_note' => trim((string) ($variant['identity_review_note'] ?? '')),
                    'package_label' => trim((string) ($variant['package_label'] ?? '')),
                    'official_url' => $this->normalizeOfferUrl((string) ($variant['official_url'] ?? '')),
                    'availability_status' => trim((string) ($variant['availability_status'] ?? 'unknown')),
                    'is_primary' => ($variant['is_primary'] ?? false) === true,
                    'accessed_at' => trim((string) ($variant['accessed_at'] ?? '')),
                    'content_sha256' => mb_strtolower(trim((string) ($variant['content_sha256'] ?? ''))),
                    'review_status' => trim((string) ($variant['review_status'] ?? 'candidate')),
                ];
            }
            $identityMigrations[] = [
                'product_key' => trim((string) ($record['product_key'] ?? '')),
                'language_code' => $this->normalizeLanguageCode((string) ($record['language_code'] ?? '')),
                'market_code' => $this->normalizeMarketCode((string) ($record['market_code'] ?? '')),
                'current_canonical_name' => trim((string) ($record['current_canonical_name'] ?? '')),
                'legacy_item_code' => $this->normalizeProductCode((string) ($record['legacy_item_code'] ?? '')),
                'current_item_code' => $this->normalizeProductCode((string) ($record['current_item_code'] ?? '')),
                'review_status' => trim((string) ($record['review_status'] ?? 'candidate')),
                'review_note' => trim((string) ($record['review_note'] ?? '')),
                'offer_variants' => $variants,
                'source_refs' => array_values(array_filter((array) ($record['source_refs'] ?? []), 'is_array')),
            ];
        }

        $relationships = [];
        foreach($relationshipRecords as $index => $record) {
            if(!is_array($record)) {
                throw new \RuntimeException('Official relationship record must be an object at index ' . $index . '.');
            }
            $safetyGate = is_array($record['safety_gate'] ?? null) ? $record['safety_gate'] : [];
            if(!array_key_exists('allow_general_combination', $safetyGate)) {
                $safetyGate['allow_general_combination'] = false;
            }
            $relationships[] = [
                'source_product_key' => trim((string) ($record['source_product_key'] ?? '')),
                'target_product_key' => trim((string) ($record['target_product_key'] ?? '')),
                'language_code' => $this->normalizeLanguageCode((string) ($record['language_code'] ?? '')),
                'market_code' => $this->normalizeMarketCode((string) ($record['market_code'] ?? '')),
                'relationship_type' => trim((string) ($record['relationship_type'] ?? '')),
                'goal_keys' => $this->cleanStringList($record['goal_keys'] ?? []),
                'evidence_summary' => trim((string) ($record['evidence_summary'] ?? '')),
                'reason_text' => trim((string) ($record['reason_text'] ?? '')),
                'source_refs' => array_values(array_filter((array) ($record['source_refs'] ?? []), 'is_array')),
                'safety_gate' => $safetyGate,
                'sequence_order' => max(0, (int) ($record['sequence_order'] ?? 0)),
                'source_content_hash' => mb_strtolower(trim((string) ($record['source_content_hash'] ?? ''))),
                'review_status' => trim((string) ($record['review_status'] ?? 'candidate')),
                'accessed_at' => trim((string) ($record['accessed_at'] ?? '')),
            ];
        }

        usort($sources, static fn(array $left, array $right): int => strcmp(
            implode('|', [$left['product_key'], $left['language_code'], $left['market_code'], $left['source_id']]),
            implode('|', [$right['product_key'], $right['language_code'], $right['market_code'], $right['source_id']])
        ));
        usort($canonicalProducts, static fn(array $left, array $right): int => strcmp(
            implode('|', [$left['product_key'], $left['language_code']]),
            implode('|', [$right['product_key'], $right['language_code']])
        ));
        usort($relationships, static fn(array $left, array $right): int => strcmp(
            implode('|', [$left['source_product_key'], $left['target_product_key'], $left['language_code'], $left['market_code'], $left['relationship_type']]),
            implode('|', [$right['source_product_key'], $right['target_product_key'], $right['language_code'], $right['market_code'], $right['relationship_type']])
        ));
        usort($identityMigrations, static fn(array $left, array $right): int => strcmp(
            implode('|', [$left['product_key'], $left['language_code'], $left['market_code']]),
            implode('|', [$right['product_key'], $right['language_code'], $right['market_code']])
        ));

        return [
            'schema_version' => trim((string) ($raw['schema_version'] ?? '')),
            'generated_at' => trim((string) ($raw['generated_at'] ?? '')),
            'policy' => is_array($raw['policy'] ?? null) ? $raw['policy'] : [],
            'inventory' => $inventoryMetadata,
            'claim_reviews' => array_merge($claimReviewMetadata, [
                'applied_count' => count($appliedClaimReviews),
            ]),
            'canonical_products' => $canonicalProducts,
            'sources' => $sources,
            'identity_migrations' => $identityMigrations,
            'relationships' => $relationships,
        ];
    }

    private function buildOfficialSourceExpectations(array $groups): array {
        $expectations = [];
        foreach($groups as $productKey => $rows) {
            $rowsByLanguage = [];
            foreach($rows as $row) {
                $languageCode = $this->normalizeLanguageCode((string) ($row['language_code'] ?? $row['language'] ?? ''));
                if($languageCode === '') {
                    continue;
                }
                if(!isset($rowsByLanguage[$languageCode]) || $this->legacyRowIsNewer($row, $rowsByLanguage[$languageCode])) {
                    $rowsByLanguage[$languageCode] = $row;
                }
            }

            $marketBuild = $this->buildMarketOffers($rows);
            $marketUrls = [];
            foreach($marketBuild['offers'] as $marketCode => $offer) {
                if(is_array($offer) && ($offer['availability_status'] ?? '') === 'available') {
                    $marketUrls[$marketCode] = $this->normalizeOfferUrl((string) ($offer['official_url'] ?? ''));
                }
            }
            $expectations[$productKey] = [
                'route' => $this->resolveGroupRoute($rows),
                'market_urls' => $marketUrls,
                'languages' => [],
            ];
            foreach($rowsByLanguage as $languageCode => $row) {
                $expectations[$productKey]['languages'][$languageCode] = [
                    'canonical_name' => trim((string) ($row['title'] ?? '')),
                    'category_key' => $this->slugify((string) ($row['category_url'] ?? '')),
                    'legacy_sku' => $this->normalizeSku((string) ($row['sku'] ?? '')),
                ];
            }
        }
        return $expectations;
    }

    private function augmentOfficialSourceExpectations(array $expectations, array $canonicalProducts): array {
        foreach($canonicalProducts as $record) {
            $productKey = (string) ($record['product_key'] ?? '');
            $languageCode = $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''));
            if($productKey === '' || $languageCode === '') {
                continue;
            }
            if(!isset($expectations[$productKey])) {
                $expectations[$productKey] = [
                    'route' => (string) ($record['route'] ?? 'unknown'),
                    'market_urls' => [],
                    'languages' => [],
                ];
            }
            $expectations[$productKey]['languages'][$languageCode] = [
                'canonical_name' => (string) ($record['canonical_name'] ?? ''),
                'category_key' => (string) ($record['category_key'] ?? ''),
                'legacy_sku' => (string) ($record['sku'] ?? ''),
            ];
            foreach((array) ($record['offer_variants'] ?? []) as $variant) {
                if(
                    !is_array($variant)
                    || (string) ($variant['review_status'] ?? '') !== 'approved'
                    || (string) ($variant['availability_status'] ?? '') !== 'available'
                    || ($variant['is_primary'] ?? false) !== true
                ) {
                    continue;
                }
                $marketCode = $this->normalizeMarketCode((string) ($variant['market_code'] ?? ''));
                if($marketCode !== '') {
                    $expectations[$productKey]['market_urls'][$marketCode] = $this->normalizeOfferUrl((string) ($variant['official_url'] ?? ''));
                }
            }
        }
        ksort($expectations, SORT_STRING);
        return $expectations;
    }

    /**
     * A reviewed identity migration replaces only the exact language/market
     * identity expected from the legacy family. It never supplies claims or
     * safety facts and therefore cannot make an entry recommendable.
     */
    private function augmentOfficialExpectationsWithIdentityMigrations(array $expectations, array $migrations): array {
        foreach($migrations as $record) {
            if((string) ($record['review_status'] ?? '') !== 'approved') {
                continue;
            }
            $productKey = trim((string) ($record['product_key'] ?? ''));
            $languageCode = $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''));
            $marketCode = $this->normalizeMarketCode((string) ($record['market_code'] ?? ''));
            if(
                $productKey === ''
                || $languageCode === ''
                || $marketCode === ''
                || !isset($expectations[$productKey])
            ) {
                continue;
            }

            $existingLanguage = is_array($expectations[$productKey]['languages'][$languageCode] ?? null)
                ? $expectations[$productKey]['languages'][$languageCode]
                : [];
            $existingLanguage['canonical_name'] = trim((string) ($record['current_canonical_name'] ?? ''));
            $existingLanguage['legacy_sku'] = $this->normalizeProductCode((string) ($record['legacy_item_code'] ?? ''));
            $existingLanguage['current_sku'] = $this->normalizeProductCode((string) ($record['current_item_code'] ?? ''));
            $expectations[$productKey]['languages'][$languageCode] = $existingLanguage;

            foreach((array) ($record['offer_variants'] ?? []) as $variant) {
                if(
                    !is_array($variant)
                    || (string) ($variant['review_status'] ?? '') !== 'approved'
                    || ($variant['is_primary'] ?? false) !== true
                    || (string) ($variant['availability_status'] ?? '') !== 'available'
                    || $this->normalizeMarketCode((string) ($variant['market_code'] ?? '')) !== $marketCode
                ) {
                    continue;
                }
                $expectations[$productKey]['market_urls'][$marketCode] = $this->normalizeOfferUrl(
                    (string) ($variant['official_url'] ?? '')
                );
            }
        }

        ksort($expectations, SORT_STRING);
        return $expectations;
    }

    /** @return list<string> */
    private function officialIdentityMigrationErrors(array $record, array $legacyGroups): array {
        $errors = [];
        $productKey = trim((string) ($record['product_key'] ?? ''));
        $languageCode = $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''));
        $marketCode = $this->normalizeMarketCode((string) ($record['market_code'] ?? ''));
        $legacyCode = $this->normalizeProductCode((string) ($record['legacy_item_code'] ?? ''));
        $currentCode = $this->normalizeProductCode((string) ($record['current_item_code'] ?? ''));

        if(!$this->isValidProductKey($productKey) || !isset($legacyGroups[$productKey])) {
            $errors[] = 'legacy_product_key_not_found';
        }
        if($languageCode === '' || $marketCode === '') {
            $errors[] = 'exact_language_and_market_required';
        }
        if(trim((string) ($record['current_canonical_name'] ?? '')) === '') {
            $errors[] = 'current_canonical_name_required';
        }
        if($legacyCode === '' || $currentCode === '') {
            $errors[] = 'legacy_and_current_item_codes_required';
        }
        if(trim((string) ($record['review_note'] ?? '')) === '') {
            $errors[] = 'identity_review_note_required';
        }
        if(!in_array((string) ($record['review_status'] ?? ''), self::ALLOWED_RELATIONSHIP_REVIEW_STATUSES, true)) {
            $errors[] = 'invalid_review_status';
        }

        if(isset($legacyGroups[$productKey])) {
            $legacyCodes = [];
            $languageFound = false;
            foreach((array) $legacyGroups[$productKey] as $legacyRow) {
                $candidateCode = $this->normalizeProductCode((string) ($legacyRow['sku'] ?? ''));
                if($candidateCode !== '') {
                    $legacyCodes[$candidateCode] = true;
                }
                $candidateLanguage = $this->normalizeLanguageCode((string) ($legacyRow['language_code'] ?? $legacyRow['language'] ?? ''));
                if($candidateLanguage === $languageCode) {
                    $languageFound = true;
                }
            }
            if(!$languageFound) {
                $errors[] = 'migration_language_not_in_legacy_family';
            }
            if($legacyCode !== '' && !isset($legacyCodes[$legacyCode])) {
                $errors[] = 'legacy_item_code_does_not_match_legacy_family';
            }
        }

        $offerKeys = [];
        $primaryCount = 0;
        foreach((array) ($record['offer_variants'] ?? []) as $variant) {
            if(!is_array($variant)) {
                $errors[] = 'invalid_offer_variant';
                continue;
            }
            $offerKey = trim((string) ($variant['offer_key'] ?? ''));
            $variantMarket = $this->normalizeMarketCode((string) ($variant['market_code'] ?? ''));
            $variantCode = $this->normalizeProductCode((string) ($variant['item_code'] ?? ''));
            $url = (string) ($variant['official_url'] ?? '');
            if($offerKey === '' || isset($offerKeys[$offerKey])) {
                $errors[] = 'missing_or_duplicate_offer_key';
            }
            $offerKeys[$offerKey] = true;
            if($variantMarket !== $marketCode) {
                $errors[] = 'offer_market_not_exact_migration_market:' . $offerKey;
            }
            if(!in_array((string) ($variant['availability_status'] ?? ''), self::ALLOWED_OFFER_VARIANT_STATUSES, true)) {
                $errors[] = 'invalid_offer_status:' . $offerKey;
            }
            if((string) ($variant['review_status'] ?? '') !== 'approved') {
                $errors[] = 'migration_offer_not_approved:' . $offerKey;
            }
            $urlValidation = $this->validateOfferUrl($variantMarket, $url);
            if(!$urlValidation['valid']) {
                $errors[] = 'invalid_offer_url:' . $offerKey . ':' . implode(',', $urlValidation['errors']);
            }
            $urlSignals = $this->extractOfficialProductCodeSignals($url);
            if($variantCode === '' || !in_array($variantCode, $urlSignals, true)) {
                $urlPath = rawurldecode((string) (parse_url($url, PHP_URL_PATH) ?? ''));
                $urlTail = $this->slugify((string) basename(rtrim($urlPath, '/')));
                if(
                    (string) ($variant['identity_slug'] ?? '') === ''
                    || !hash_equals((string) ($variant['identity_slug'] ?? ''), $urlTail)
                    || trim((string) ($variant['identity_review_note'] ?? '')) === ''
                ) {
                    $errors[] = 'offer_item_code_or_reviewed_slug_not_in_url:' . $offerKey;
                }
            }
            if(!preg_match('/^[a-f0-9]{64}$/', (string) ($variant['content_sha256'] ?? ''))) {
                $errors[] = 'invalid_offer_content_hash:' . $offerKey;
            }
            if(!$this->validAccessedAt((string) ($variant['accessed_at'] ?? ''))) {
                $errors[] = 'invalid_offer_accessed_at:' . $offerKey;
            }
            if(($variant['is_primary'] ?? false) === true) {
                $primaryCount++;
                if((string) ($variant['availability_status'] ?? '') !== 'available') {
                    $errors[] = 'primary_migration_offer_must_be_available';
                }
                if($variantCode !== $currentCode) {
                    $errors[] = 'primary_offer_item_code_not_current_item_code';
                }
            }
        }
        if($primaryCount !== 1) {
            $errors[] = 'exactly_one_primary_offer_required';
        }

        $hasInventoryRef = false;
        foreach((array) ($record['source_refs'] ?? []) as $sourceRef) {
            if(
                is_array($sourceRef)
                && (string) ($sourceRef['source_type'] ?? '') === 'official_inventory_reconciliation'
                && $this->normalizeMarketCode((string) ($sourceRef['market_code'] ?? $marketCode)) === $marketCode
                && $this->normalizeLanguageCode((string) ($sourceRef['language_code'] ?? $languageCode)) === $languageCode
                && $this->validAccessedAt((string) ($sourceRef['accessed_at'] ?? ''))
                && preg_match('/^[a-f0-9]{64}$/', (string) ($sourceRef['content_sha256'] ?? ''))
            ) {
                $hasInventoryRef = true;
                break;
            }
        }
        if(!$hasInventoryRef) {
            $errors[] = 'exact_official_inventory_source_ref_required';
        }

        return array_values(array_unique($errors));
    }

    /**
     * Proves that the locked HR inventory is a materialized reconciliation,
     * rather than a header containing unverified 113/96 totals.
     *
     * @return array{errors:list<string>,counts:array<string,int>}
     */
    private function inspectOfficialInventoryReconciliation(
        array $inventory,
        array $legacyGroups,
        array $canonicalProducts
    ): array {
        $errors = [];
        $families = is_array($inventory['inventory_families'] ?? null)
            ? $inventory['inventory_families']
            : [];
        $retired = is_array($inventory['retired_or_orphaned'] ?? null)
            ? $inventory['retired_or_orphaned']
            : [];
        $familyKeys = [];
        $offerUrls = [];
        $availableFamilies = 0;
        $soldOutOnlyFamilies = 0;
        $offerCount = 0;

        $snapshotHash = mb_strtolower(trim((string) ($inventory['normalized_snapshot_sha256'] ?? '')));
        if(!preg_match('/^[a-f0-9]{64}$/', $snapshotHash)) {
            $errors[] = 'normalized_snapshot_sha256_required';
        }
        if(!preg_match('/^[a-f0-9]{64}$/', mb_strtolower(trim((string) ($inventory['raw_snapshot_sha256'] ?? ''))))) {
            $errors[] = 'raw_snapshot_sha256_required';
        }

        foreach($families as $index => $family) {
            if(!is_array($family)) {
                $errors[] = 'family_not_object:' . $index;
                continue;
            }
            $productKey = trim((string) ($family['product_key'] ?? ''));
            if(!$this->isValidProductKey($productKey) || isset($familyKeys[$productKey])) {
                $errors[] = 'invalid_or_duplicate_family_key:' . $productKey;
                continue;
            }
            $familyKeys[$productKey] = true;
            $offers = is_array($family['offers'] ?? null) ? $family['offers'] : [];
            if(!$offers || (int) ($family['offer_count'] ?? -1) !== count($offers)) {
                $errors[] = 'family_offer_count_mismatch:' . $productKey;
            }
            $hasAvailableOffer = false;
            foreach($offers as $offerIndex => $offer) {
                if(!is_array($offer)) {
                    $errors[] = 'offer_not_object:' . $productKey . ':' . $offerIndex;
                    continue;
                }
                $status = (string) ($offer['availability'] ?? '');
                $url = $this->normalizeOfferUrl((string) ($offer['official_url'] ?? ''));
                if(!in_array($status, ['available', 'sold_out'], true)) {
                    $errors[] = 'invalid_inventory_offer_status:' . $productKey . ':' . $offerIndex;
                }
                if($status === 'available') {
                    $hasAvailableOffer = true;
                }
                $urlValidation = $this->validateOfferUrl('hr', $url);
                if(!$urlValidation['valid']) {
                    $errors[] = 'invalid_inventory_offer_url:' . $productKey . ':' . $offerIndex;
                }
                if($url === '' || isset($offerUrls[$url])) {
                    $errors[] = 'missing_or_duplicate_inventory_offer_url:' . $productKey . ':' . $offerIndex;
                }
                $offerUrls[$url] = true;
                $itemCode = $this->normalizeProductCode((string) ($offer['item_code'] ?? ''));
                if($itemCode !== '' && !in_array($itemCode, $this->extractOfficialProductCodeSignals($url), true)) {
                    $errors[] = 'inventory_offer_item_code_not_in_url:' . $productKey . ':' . $itemCode;
                }
                $offerCount++;
            }
            $declaredAvailable = ($family['available'] ?? null) === true;
            if($declaredAvailable !== $hasAvailableOffer) {
                $errors[] = 'family_availability_mismatch:' . $productKey;
            }
            if($hasAvailableOffer) {
                $availableFamilies++;
            } else {
                $soldOutOnlyFamilies++;
            }
        }

        $retiredKeys = [];
        foreach($retired as $record) {
            if(!is_array($record)) {
                $errors[] = 'retired_record_not_object';
                continue;
            }
            $productKey = trim((string) ($record['product_key'] ?? ''));
            if(
                !$this->isValidProductKey($productKey)
                || !isset($legacyGroups[$productKey])
                || isset($familyKeys[$productKey])
                || isset($retiredKeys[$productKey])
            ) {
                $errors[] = 'invalid_retired_or_orphaned_identity:' . $productKey;
            }
            if((string) ($record['status'] ?? '') !== 'retired_or_orphaned') {
                $errors[] = 'invalid_retired_or_orphaned_status:' . $productKey;
            }
            if(!hash_equals($snapshotHash, mb_strtolower(trim((string) ($record['snapshot_sha256'] ?? ''))))) {
                $errors[] = 'retired_snapshot_hash_mismatch:' . $productKey;
            }
            $retiredKeys[$productKey] = true;
        }

        $officialOnlyKeys = [];
        foreach($canonicalProducts as $record) {
            if((string) ($record['review_status'] ?? '') === 'approved') {
                $officialOnlyKeys[(string) ($record['product_key'] ?? '')] = true;
            }
            foreach((array) ($record['source_refs'] ?? []) as $sourceRef) {
                if(
                    is_array($sourceRef)
                    && (string) ($sourceRef['source_type'] ?? '') === 'official_inventory_reconciliation'
                    && !hash_equals($snapshotHash, mb_strtolower(trim((string) ($sourceRef['snapshot_sha256'] ?? ''))))
                ) {
                    $errors[] = 'canonical_source_snapshot_hash_mismatch:' . (string) ($record['product_key'] ?? '');
                }
            }
        }
        $expectedCurrentKeys = $officialOnlyKeys;
        foreach(array_keys($legacyGroups) as $legacyKey) {
            if(!isset($retiredKeys[$legacyKey])) {
                $expectedCurrentKeys[$legacyKey] = true;
            }
        }
        $actualKeys = array_keys($familyKeys);
        $expectedKeys = array_keys($expectedCurrentKeys);
        sort($actualKeys, SORT_STRING);
        sort($expectedKeys, SORT_STRING);
        if($actualKeys !== $expectedKeys) {
            $errors[] = 'materialized_family_keys_do_not_match_current_catalog';
        }

        $computedCounts = [
            'materialized_inventory_families' => count($familyKeys),
            'materialized_inventory_offers' => $offerCount,
            'materialized_available_families' => $availableFamilies,
            'materialized_sold_out_only_families' => $soldOutOnlyFamilies,
            'materialized_retired_or_orphaned' => count($retiredKeys),
        ];
        $declaredCounts = [
            'canonical_family_count' => $computedCounts['materialized_inventory_families'],
            'unique_offer_count' => $computedCounts['materialized_inventory_offers'],
            'available_family_count' => $computedCounts['materialized_available_families'],
            'sold_out_only_family_count' => $computedCounts['materialized_sold_out_only_families'],
        ];
        foreach($declaredCounts as $declaredKey => $computedValue) {
            if((int) ($inventory[$declaredKey] ?? -1) !== $computedValue) {
                $errors[] = 'declared_count_mismatch:' . $declaredKey;
            }
        }

        return [
            'errors' => array_values(array_unique($errors)),
            'counts' => $computedCounts,
        ];
    }

    /** @return list<string> */
    private function officialCanonicalProductErrors(array $record, array $legacyGroups): array {
        $errors = [];
        $productKey = (string) ($record['product_key'] ?? '');
        $languageCode = $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''));
        $route = (string) ($record['route'] ?? 'unknown');
        if(!$this->isValidProductKey($productKey)) {
            $errors[] = 'invalid_product_key';
        }
        if(isset($legacyGroups[$productKey])) {
            $errors[] = 'official_only_product_collides_with_legacy_canonical';
        }
        if($languageCode === '' || trim((string) ($record['canonical_name'] ?? '')) === '') {
            $errors[] = 'language_or_name_missing';
        }
        if(!in_array($route, self::ALLOWED_ROUTES, true) || $route === 'unknown') {
            $errors[] = 'invalid_route';
        }
        if(!in_array((string) ($record['review_status'] ?? ''), self::ALLOWED_RELATIONSHIP_REVIEW_STATUSES, true)) {
            $errors[] = 'invalid_review_status';
        }
        $primaryByMarket = [];
        $offerKeys = [];
        foreach((array) ($record['offer_variants'] ?? []) as $variant) {
            if(!is_array($variant)) {
                $errors[] = 'invalid_offer_variant';
                continue;
            }
            $offerKey = (string) ($variant['offer_key'] ?? '');
            $marketCode = $this->normalizeMarketCode((string) ($variant['market_code'] ?? ''));
            $itemCode = $this->normalizeProductCode((string) ($variant['item_code'] ?? ''));
            $url = (string) ($variant['official_url'] ?? '');
            if($offerKey === '' || isset($offerKeys[$offerKey])) {
                $errors[] = 'missing_or_duplicate_offer_key';
            }
            $offerKeys[$offerKey] = true;
            if($marketCode === '' || !in_array((string) ($variant['availability_status'] ?? ''), self::ALLOWED_OFFER_VARIANT_STATUSES, true)) {
                $errors[] = 'invalid_offer_market_or_status:' . $offerKey;
            }
            $urlValidation = $this->validateOfferUrl($marketCode, $url);
            if(!$urlValidation['valid']) {
                $errors[] = 'invalid_offer_url:' . $offerKey . ':' . implode(',', $urlValidation['errors']);
            }
            $urlCodeSignals = $this->extractOfficialProductCodeSignals($url);
            if($itemCode === '' || !in_array($itemCode, $urlCodeSignals, true)) {
                $urlPath = rawurldecode((string) (parse_url($url, PHP_URL_PATH) ?? ''));
                $urlTail = $this->slugify((string) basename(rtrim($urlPath, '/')));
                if(
                    (string) ($variant['identity_slug'] ?? '') === ''
                    || !hash_equals((string) ($variant['identity_slug'] ?? ''), $urlTail)
                    || trim((string) ($variant['identity_review_note'] ?? '')) === ''
                ) {
                    $errors[] = 'offer_item_code_or_reviewed_slug_not_in_url:' . $offerKey;
                }
            }
            if(!preg_match('/^[a-f0-9]{64}$/', (string) ($variant['content_sha256'] ?? ''))) {
                $errors[] = 'invalid_offer_content_hash:' . $offerKey;
            }
            if(!$this->validAccessedAt((string) ($variant['accessed_at'] ?? ''))) {
                $errors[] = 'invalid_offer_accessed_at:' . $offerKey;
            }
            if((string) ($variant['review_status'] ?? '') === 'approved' && ($variant['is_primary'] ?? false) === true) {
                if(isset($primaryByMarket[$marketCode])) {
                    $errors[] = 'multiple_primary_offers_for_market:' . $marketCode;
                }
                $primaryByMarket[$marketCode] = true;
            }
        }
        if(empty($primaryByMarket)) {
            $errors[] = 'approved_primary_offer_required';
        }
        $hasInventoryRef = false;
        foreach((array) ($record['source_refs'] ?? []) as $sourceRef) {
            if(
                is_array($sourceRef)
                && (string) ($sourceRef['source_type'] ?? '') === 'official_inventory_reconciliation'
                && $this->validAccessedAt((string) ($sourceRef['accessed_at'] ?? ''))
                && preg_match('/^[a-f0-9]{64}$/', (string) ($sourceRef['content_sha256'] ?? ''))
            ) {
                $hasInventoryRef = true;
                break;
            }
        }
        if(!$hasInventoryRef) {
            $errors[] = 'official_inventory_source_ref_required';
        }
        return array_values(array_unique($errors));
    }

    /** @return list<string> */
    private function officialSourceIdentityErrors(array $record, array $expectations): array {
        $errors = [];
        $productKey = (string) ($record['product_key'] ?? '');
        $languageCode = $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''));
        $marketCode = $this->normalizeMarketCode((string) ($record['market_code'] ?? ''));
        $sourceType = (string) ($record['source_type'] ?? '');
        $sourceUrl = (string) ($record['source_url'] ?? '');
        $expected = $expectations[$productKey] ?? null;
        $expectedLanguage = is_array($expected) ? ($expected['languages'][$languageCode] ?? null) : null;

        if(!is_array($expected)) {
            $errors[] = 'product_key_not_in_canonical_catalog';
        }
        if(!is_array($expectedLanguage)) {
            $errors[] = 'language_not_in_canonical_catalog';
        }
        if(!in_array($sourceType, ['official_product_page', 'official_label_pdf', 'official_program_guide'], true)) {
            $errors[] = 'invalid_source_type';
        }
        if(!in_array((string) ($record['review_status'] ?? ''), self::ALLOWED_RELATIONSHIP_REVIEW_STATUSES, true)) {
            $errors[] = 'invalid_review_status';
        }

        $sourceValidation = $this->validateOfficialEvidenceUrl($sourceUrl, $marketCode);
        if(!$sourceValidation['valid']) {
            $errors[] = 'invalid_official_source_url:' . implode(',', $sourceValidation['errors']);
        }
        if(!preg_match('/^[a-f0-9]{64}$/', (string) ($record['content_sha256'] ?? ''))) {
            $errors[] = 'invalid_content_sha256';
        }
        if(!preg_match('/^[a-f0-9]{64}$/', (string) ($record['evidence_sha256'] ?? ''))) {
            $errors[] = 'invalid_evidence_sha256';
        }
        if(!$this->validAccessedAt((string) ($record['accessed_at'] ?? ''))) {
            $errors[] = 'invalid_accessed_at';
        }

        if($sourceType === 'official_product_page' && is_array($expected)) {
            $expectedMarketUrl = (string) ($expected['market_urls'][$marketCode] ?? '');
            if($expectedMarketUrl === '' || $this->normalizeOfferUrl($sourceUrl) !== $expectedMarketUrl) {
                if(
                    ($record['replaces_legacy_offer_url'] ?? false) !== true
                    || trim((string) ($record['replacement_review_note'] ?? '')) === ''
                ) {
                    $errors[] = 'source_url_not_exact_catalog_market_offer';
                }
            }
            $pageName = trim((string) ($record['page_name'] ?? ''));
            $pageItemCode = $this->normalizeProductCode((string) ($record['page_item_code'] ?? ''));
            $pageCategory = trim((string) ($record['page_category'] ?? ''));
            $pageRoute = trim((string) ($record['page_route'] ?? ''));
            if($pageName === '') {
                $errors[] = 'official_page_name_missing';
            }
            if($pageItemCode === '') {
                $errors[] = 'official_page_item_code_missing';
            }
            if($pageCategory === '') {
                $errors[] = 'official_page_category_missing';
            }
            if($pageRoute === '' || $pageRoute === 'unknown' || !in_array($pageRoute, self::ALLOWED_ROUTES, true)) {
                $errors[] = 'official_page_route_missing';
            } elseif((string) ($expected['route'] ?? '') !== $pageRoute) {
                if(
                    (string) ($record['identity_status'] ?? '') !== 'verified_reviewed'
                    || trim((string) ($record['identity_review_note'] ?? '')) === ''
                    || trim((string) ($record['route_override_review_note'] ?? '')) === ''
                ) {
                    $errors[] = 'official_page_route_conflicts_with_catalog';
                }
            }

            $identityStatus = (string) ($record['identity_status'] ?? '');
            if(!in_array($identityStatus, ['verified_exact', 'verified_reviewed'], true)) {
                $errors[] = 'identity_not_verified';
            }
            if($identityStatus === 'verified_exact' && is_array($expectedLanguage)) {
                $catalogName = $this->normalizeOfficialIdentityName((string) ($expectedLanguage['canonical_name'] ?? ''));
                $officialName = $this->normalizeOfficialIdentityName($pageName);
                if($catalogName === '' || !hash_equals($catalogName, $officialName)) {
                    $errors[] = 'official_page_name_not_exact';
                }
            }
            if($identityStatus === 'verified_reviewed' && trim((string) ($record['identity_review_note'] ?? '')) === '') {
                $errors[] = 'reviewed_identity_requires_note';
            }

            $urlSignals = $this->extractOfficialProductCodeSignals($sourceUrl);
            if($pageItemCode !== '' && !in_array($pageItemCode, $urlSignals, true)) {
                $errors[] = 'official_page_item_code_not_in_url';
            }
        }

        $safetyRoute = trim((string) ($record['page_route'] ?? ''));
        if(!in_array($safetyRoute, self::ALLOWED_ROUTES, true) || $safetyRoute === 'unknown') {
            $safetyRoute = (string) ($expected['route'] ?? 'unknown');
        }
        foreach($this->officialSourceSafetyExtractionErrors($record, $safetyRoute) as $safetyError) {
            $errors[] = $safetyError;
        }

        return array_values(array_unique($errors));
    }

    /**
     * An approved label fact must prove that a complete warning-bearing suffix
     * was moved out of usage/ingredients and into safety_notes. This catches
     * partial sentence extraction such as Marine Collagen restrictions and the
     * Vitolize Men warning block that previously leaked into ingredients.
     *
     * @return list<string>
     */
    private function officialSourceSafetyExtractionErrors(array $record, string $route): array {
        $errors = [];
        $hasFactPayload = false;
        foreach(['aliases', 'use_cases', 'ingredient_facts', 'approved_support_facts', 'usage_facts', 'safety_notes'] as $field) {
            if(!empty($record[$field])) {
                $hasFactPayload = true;
                break;
            }
        }
        if(!$hasFactPayload) {
            if(!in_array((string) ($record['safety_review_status'] ?? 'unknown'), ['unknown', 'complete'], true)) {
                return ['invalid_safety_review_status'];
            }
            return [];
        }

        $labelEvidence = is_array($record['label_evidence'] ?? null) ? $record['label_evidence'] : [];
        if(!$labelEvidence) {
            return ['label_evidence_required_for_fact_payload'];
        }
        if((string) ($labelEvidence['extractor_version'] ?? '') !== 'fcc-label-safety-suffix-v1') {
            $errors[] = 'unsupported_label_safety_extractor';
        }
        if(
            (string) ($record['review_status'] ?? '') === 'approved'
            && !in_array((string) ($record['review_scope'] ?? ''), ['identity_and_label_facts', 'complete_recommendation_facts'], true)
        ) {
            $errors[] = 'approved_fact_payload_requires_review_scope';
        }

        $usageRaw = (string) ($labelEvidence['usage_text'] ?? '');
        $ingredientsRaw = (string) ($labelEvidence['ingredients_text'] ?? '');
        if(preg_match('/(?:prikaži\s+više|prikazi\s+vise|show\s+more|read\s+more)/iu', $usageRaw . ' ' . $ingredientsRaw) === 1) {
            $errors[] = 'label_evidence_contains_truncation_marker';
        }
        $usageSplit = $this->splitOfficialSafetySuffix($usageRaw);
        $ingredientsSplit = $this->splitOfficialSafetySuffix($ingredientsRaw);
        $derivedSuffixes = $this->cleanOfficialEvidenceList(array_filter([
            $usageSplit['safety_suffix'],
            $ingredientsSplit['safety_suffix'],
        ], static fn(string $value): bool => $value !== ''));
        $declaredSuffixes = $this->cleanOfficialEvidenceList($labelEvidence['required_safety_suffixes'] ?? []);
        $normalizeList = function(array $items): array {
            $normalized = array_values(array_unique(array_map(
                fn(string $item): string => $this->normalizeOfficialEvidenceText($item),
                $items
            )));
            sort($normalized, SORT_STRING);
            return $normalized;
        };
        if($normalizeList($derivedSuffixes) !== $normalizeList($declaredSuffixes)) {
            $errors[] = 'declared_safety_suffixes_do_not_match_raw_sections';
        }

        $expectedStatus = $derivedSuffixes ? 'complete' : 'not_present';
        if((string) ($labelEvidence['safety_extraction_status'] ?? '') !== $expectedStatus) {
            $errors[] = 'safety_extraction_status_mismatch';
        }
        if(in_array($route, ['oral', 'mixed'], true) && $derivedSuffixes && $expectedStatus !== 'complete') {
            $errors[] = 'oral_safety_suffix_extraction_incomplete';
        }
        $storedSafetyReviewStatus = (string) ($record['safety_review_status'] ?? 'unknown');
        $expectedSafetyReviewStatus = $derivedSuffixes ? 'complete' : 'unknown';
        if($storedSafetyReviewStatus !== $expectedSafetyReviewStatus) {
            $errors[] = 'safety_review_status_mismatch';
        }
        if((string) ($record['review_status'] ?? '') === 'approved' && $storedSafetyReviewStatus !== 'complete') {
            $errors[] = 'approved_fact_payload_requires_complete_warning_review';
        }

        $sectionHashes = is_array($labelEvidence['section_sha256'] ?? null) ? $labelEvidence['section_sha256'] : [];
        foreach(['usage' => $usageRaw, 'ingredients' => $ingredientsRaw] as $section => $rawText) {
            $expectedHash = hash('sha256', $this->normalizeOfficialEvidenceText($rawText));
            $storedHash = mb_strtolower(trim((string) ($sectionHashes[$section] ?? '')));
            if(!preg_match('/^[a-f0-9]{64}$/', $storedHash) || !hash_equals($expectedHash, $storedHash)) {
                $errors[] = 'label_section_hash_mismatch:' . $section;
            }
        }

        $factSections = [
            'usage' => ['expected' => $usageSplit['fact_text'], 'facts' => (array) ($record['usage_facts'] ?? [])],
            'ingredients' => ['expected' => $ingredientsSplit['fact_text'], 'facts' => (array) ($record['ingredient_facts'] ?? [])],
        ];
        foreach($factSections as $section => $factSection) {
            $expectedFact = $this->normalizeOfficialEvidenceText((string) $factSection['expected']);
            $actualFacts = $this->cleanOfficialEvidenceList($factSection['facts']);
            $actualNormalized = array_map(
                fn(string $fact): string => $this->normalizeOfficialEvidenceText($fact),
                $actualFacts
            );
            if($expectedFact !== '' && !in_array($expectedFact, $actualNormalized, true)) {
                $errors[] = 'label_fact_prefix_missing:' . $section;
            }
            foreach($actualFacts as $fact) {
                if($this->splitOfficialSafetySuffix($fact)['marker_found']) {
                    $errors[] = 'safety_block_leaked_into_' . $section . '_facts';
                    break;
                }
            }
        }

        $safetyCorpus = $this->normalizeOfficialEvidenceText(implode(' ', (array) ($record['safety_notes'] ?? [])));
        $mandatorySafetyCorpus = $this->normalizeOfficialEvidenceText(implode(' ', (array) ($record['mandatory_safety_notes'] ?? [])));
        foreach($derivedSuffixes as $suffix) {
            $normalizedSuffix = $this->normalizeOfficialEvidenceText($suffix);
            if($normalizedSuffix === '' || !str_contains($safetyCorpus, $normalizedSuffix)) {
                $errors[] = 'safety_notes_missing_complete_section_suffix';
            }
            if($normalizedSuffix === '' || !str_contains($mandatorySafetyCorpus, $normalizedSuffix)) {
                $errors[] = 'mandatory_safety_notes_missing_complete_section_suffix';
            }
        }

        $allergens = $this->cleanStringList($record['allergens_contains'] ?? []);
        foreach($allergens as $allergen) {
            if(!in_array($allergen, self::ALLOWED_ALLERGEN_KEYS, true)) {
                $errors[] = 'invalid_allergen_key:' . $allergen;
            }
        }
        $expectedAllergens = $this->detectOfficialAllergens($ingredientsRaw);
        sort($allergens, SORT_STRING);
        sort($expectedAllergens, SORT_STRING);
        if($allergens !== $expectedAllergens) {
            $errors[] = 'structured_allergens_do_not_match_label_evidence';
        }

        $safetyFlags = $this->cleanStringList($record['safety_flags'] ?? []);
        foreach($safetyFlags as $safetyFlag) {
            if(!in_array($safetyFlag, self::ALLOWED_SAFETY_FLAGS, true)) {
                $errors[] = 'invalid_safety_flag:' . $safetyFlag;
            }
        }
        $expectedSafetyFlags = $this->detectOfficialSafetyFlags(implode(' ', [
            $usageRaw,
            $ingredientsRaw,
            implode(' ', (array) ($record['safety_notes'] ?? [])),
        ]));
        sort($safetyFlags, SORT_STRING);
        sort($expectedSafetyFlags, SORT_STRING);
        if($safetyFlags !== $expectedSafetyFlags) {
            $errors[] = 'structured_safety_flags_do_not_match_label_evidence';
        }

        $suitabilityStatus = (string) ($record['suitability_review_status'] ?? 'unknown');
        $suitabilityFlags = $this->cleanStringList($record['suitability_flags'] ?? []);
        if(!in_array($suitabilityStatus, ['complete', 'unknown'], true)) {
            $errors[] = 'invalid_suitability_review_status';
        }
        foreach($suitabilityFlags as $suitabilityFlag) {
            if(!in_array($suitabilityFlag, self::ALLOWED_SUITABILITY_FLAGS, true)) {
                $errors[] = 'invalid_suitability_flag:' . $suitabilityFlag;
            }
        }
        if($suitabilityStatus === 'unknown' && $suitabilityFlags) {
            $errors[] = 'unknown_suitability_cannot_expose_flags';
        }
        if($suitabilityStatus === 'complete') {
            $hasSuitabilityEvidence = false;
            foreach((array) ($record['source_refs'] ?? []) as $sourceRef) {
                if(
                    is_array($sourceRef)
                    && (string) ($sourceRef['source_type'] ?? '') === 'official_suitability_evidence'
                    && $this->officialReviewRefMatchesSourceRecord($sourceRef, $record)
                ) {
                    $hasSuitabilityEvidence = true;
                    break;
                }
            }
            if(!$hasSuitabilityEvidence) {
                $errors[] = 'complete_suitability_requires_exact_official_evidence';
            }
        }
        $claimReviewStatus = (string) ($record['claim_review_status'] ?? 'unknown');
        if(!in_array($claimReviewStatus, ['approved', 'unknown'], true)) {
            $errors[] = 'invalid_claim_review_status';
        }
        if($claimReviewStatus === 'approved') {
            if(
                (string) ($record['review_scope'] ?? '') !== 'complete_recommendation_facts'
                || empty($record['use_cases'])
                || (empty($record['ingredient_facts']) && empty($record['approved_support_facts']))
            ) {
                $errors[] = 'approved_claim_requires_complete_recommendation_scope';
            }
            $hasClaimEvidence = false;
            foreach((array) ($record['source_refs'] ?? []) as $sourceRef) {
                if(
                    is_array($sourceRef)
                    && (string) ($sourceRef['source_type'] ?? '') === 'official_claim_review'
                    && $this->officialReviewRefMatchesSourceRecord($sourceRef, $record)
                ) {
                    $hasClaimEvidence = true;
                    break;
                }
            }
            if(!$hasClaimEvidence) {
                $errors[] = 'approved_claim_requires_exact_official_evidence';
            }
        }

        return array_values(array_unique($errors));
    }

    /**
     * Claim/suitability approval is bound to this exact source record. A hash
     * copied from another market, language, URL or parser section cannot unlock
     * recommendation eligibility.
     */
    private function officialReviewRefMatchesSourceRecord(array $sourceRef, array $record): bool {
        $recordUrl = $this->normalizeOfferUrl((string) ($record['source_url'] ?? ''));
        $refUrl = $this->normalizeOfferUrl((string) ($sourceRef['source_url'] ?? ''));
        $recordContentHash = mb_strtolower(trim((string) ($record['content_sha256'] ?? '')));
        $refContentHash = mb_strtolower(trim((string) ($sourceRef['content_sha256'] ?? '')));
        $recordEvidenceHash = mb_strtolower(trim((string) ($record['evidence_sha256'] ?? '')));
        $refEvidenceHash = mb_strtolower(trim((string) ($sourceRef['evidence_sha256'] ?? '')));
        if(
            $recordUrl === ''
            || $refUrl === ''
            || !hash_equals($recordUrl, $refUrl)
            || $this->normalizeLanguageCode((string) ($sourceRef['language_code'] ?? ''))
                !== $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''))
            || $this->normalizeMarketCode((string) ($sourceRef['market_code'] ?? ''))
                !== $this->normalizeMarketCode((string) ($record['market_code'] ?? ''))
            || !preg_match('/^[a-f0-9]{64}$/', $recordContentHash)
            || !hash_equals($recordContentHash, $refContentHash)
            || !preg_match('/^[a-f0-9]{64}$/', $recordEvidenceHash)
            || !hash_equals($recordEvidenceHash, $refEvidenceHash)
            || !$this->validAccessedAt((string) ($sourceRef['accessed_at'] ?? ''))
        ) {
            return false;
        }

        $recordSections = is_array($record['label_evidence']['section_sha256'] ?? null)
            ? $record['label_evidence']['section_sha256']
            : [];
        $refSections = is_array($sourceRef['section_sha256'] ?? null)
            ? $sourceRef['section_sha256']
            : [];
        foreach(['usage', 'ingredients'] as $section) {
            $recordHash = mb_strtolower(trim((string) ($recordSections[$section] ?? '')));
            $refHash = mb_strtolower(trim((string) ($refSections[$section] ?? '')));
            if(!preg_match('/^[a-f0-9]{64}$/', $recordHash) || !hash_equals($recordHash, $refHash)) {
                return false;
            }
        }

        return true;
    }

    /** @return array{fact_text:string,safety_suffix:string,marker_found:bool} */
    private function splitOfficialSafetySuffix(string $text): array {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if($text === '') {
            return ['fact_text' => '', 'safety_suffix' => '', 'marker_found' => false];
        }
        $matched = preg_match(
            '/\b(?:napomena|upozorenje|upozorenja|oprez|warning|warnings|caution|precaution|note)\s*:/iu',
            $text,
            $matches,
            PREG_OFFSET_CAPTURE
        );
        if($matched !== 1) {
            return ['fact_text' => $text, 'safety_suffix' => '', 'marker_found' => false];
        }
        $offset = (int) ($matches[0][1] ?? 0);
        return [
            'fact_text' => trim(substr($text, 0, $offset)),
            'safety_suffix' => trim(substr($text, $offset)),
            'marker_found' => true,
        ];
    }

    private function normalizeOfficialEvidenceText(string $text): string {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $text)), 'UTF-8');
    }

    /** @return list<string> */
    private function detectOfficialAllergens(string $ingredientsText): array {
        $normalized = $this->normalizeOfficialEvidenceText($ingredientsText);
        $patterns = [
            'soy' => '/\b(soj\p{L}*|soy)\b/iu',
            /* Keep riboflavin out: only explicit Croatian fish forms or English fish count. */
            'fish' => '/\b(riba|ribe|ribi|ribu|ribom|ribama|ribl\p{L}*|fish(?:es)?)\b/iu',
            'milk' => '/\b(mlijeko|mliječni|mlijecni|milk|whey|sirutka)\b/iu',
            'egg' => '/\b(jaje|jaja|egg|albumin)\b/iu',
            'tree_nuts' => '/\b(orašast|orasast|orah|badem|lješnjak|ljesnjak|nut|nuts|almond|hazelnut|walnut)\b/iu',
            'gluten' => '/\b(gluten|pšenic\p{L}*|psenic\p{L}*|ječam|jecam|wheat|barley)\b/iu',
            'bee_products' => '/\b(pčelin\p{L}*|pcelin\p{L}*|propolis\p{L}*|pelud\p{L}*|med|honey|bee pollen|royal jelly)\b/iu',
        ];
        $allergens = [];
        foreach($patterns as $key => $pattern) {
            if(preg_match($pattern, $normalized) === 1) {
                $allergens[] = $key;
            }
        }
        return $allergens;
    }

    /** @return list<string> */
    private function detectOfficialSafetyFlags(string $evidenceText): array {
        $normalized = $this->normalizeOfficialEvidenceText($evidenceText);
        $patterns = [
            'caffeine' => '/\b(kofein|caffeine|guarana)\b/iu',
            'green_tea' => '/\b(zeleni čaj|zelenog čaja|green tea|camellia sinensis)\b/iu',
            'pregnancy_warning' => '/\b(trudnic|trudnoć|trudnoc|pregnan)\p{L}*/iu',
            'breastfeeding_warning' => '/\b(dojilj|dojenje|dojite|breastfeed|nursing)\p{L}*/iu',
            'under_18_warning' => '/\b(mlađ\p{L}*\s+od\s+18|ispod\s+18|under\s+18)\b/iu',
            'medication_consult' => '/\b(lijek|terapij|liječnik|lijecnik|doctor|medication|medical condition)\p{L}*/iu',
            'empty_stomach_warning' => '/\b(prazan želudac|prazan zeludac|empty stomach)\b/iu',
            'iron_child_poisoning' => '/\b(željez|zeljez|iron)\p{L}*.*\b(trovan|poison)\p{L}*/iu',
            'external_use_only' => '/\b(samo za vanjsku uporabu|external use only)\b/iu',
            'avoid_eye_contact' => '/\b(očima|ocima|eye contact|eyes)\b/iu',
            'damaged_seal' => '/\b(zaštitna folija|zastitna folija|pečat|pecat|seal).*\b(oštećen|ostecen|nedostaje|damaged|missing)\p{L}*/iu',
            'dose_limit' => '/\b(ne smiju se prekoračiti|ne smije se prekoračiti|do not exceed)\b/iu',
            'laxative_warning' => '/\b(laksativ\p{L}*|laxative\p{L}*)\b/iu',
        ];
        $flags = [];
        foreach($patterns as $key => $pattern) {
            if(preg_match($pattern, $normalized) === 1) {
                $flags[] = $key;
            }
        }
        return $flags;
    }

    /** @return list<string> */
    private function officialRelationshipManifestErrors(array $record, array $expectations): array {
        $errors = [];
        $sourceProductKey = trim((string) ($record['source_product_key'] ?? ''));
        $targetProductKey = trim((string) ($record['target_product_key'] ?? ''));
        $languageCode = $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''));
        $marketCode = $this->normalizeMarketCode((string) ($record['market_code'] ?? ''));
        $relationshipType = (string) ($record['relationship_type'] ?? '');

        if($sourceProductKey === '' || $targetProductKey === '' || $sourceProductKey === $targetProductKey) {
            $errors[] = 'invalid_source_or_target';
        }
        foreach([$sourceProductKey, $targetProductKey] as $productKey) {
            if(empty($expectations[$productKey]['languages'][$languageCode])) {
                $errors[] = 'product_or_language_not_in_catalog:' . $productKey;
            }
            if(empty($expectations[$productKey]['market_urls'][$marketCode])) {
                $errors[] = 'product_not_available_in_exact_market:' . $productKey;
            }
        }
        if(!in_array($relationshipType, self::ALLOWED_RELATIONSHIP_TYPES, true)) {
            $errors[] = 'invalid_relationship_type';
        }
        if(empty($record['goal_keys'])) {
            $errors[] = 'goal_keys_required';
        }
        if(trim((string) ($record['evidence_summary'] ?? '')) === '' || trim((string) ($record['reason_text'] ?? '')) === '') {
            $errors[] = 'evidence_and_reason_required';
        }
        $safetyGate = is_array($record['safety_gate'] ?? null) ? $record['safety_gate'] : [];
        if(
            ($safetyGate['requires_goal_match'] ?? null) !== true
            || ($safetyGate['allow_if_safety_unknown'] ?? null) !== false
            || !is_bool($safetyGate['allow_general_combination'] ?? null)
            || !isset($safetyGate['blocked_if'])
            || !is_array($safetyGate['blocked_if'])
        ) {
            $errors[] = 'safety_gate_not_fail_closed';
        }
        $blockedIf = is_array($safetyGate['blocked_if'] ?? null)
            ? $this->cleanStringList($safetyGate['blocked_if'])
            : [];
        foreach($blockedIf as $blocker) {
            if(!in_array($blocker, self::ALLOWED_RELATIONSHIP_BLOCKERS, true)) {
                $errors[] = 'invalid_safety_blocker:' . $blocker;
            }
        }
        foreach(['allergen_conflict', 'required_safety_unknown', 'product_unavailable'] as $requiredBlocker) {
            if(!in_array($requiredBlocker, $blockedIf, true)) {
                $errors[] = 'missing_required_safety_blocker:' . $requiredBlocker;
            }
        }
        if(($safetyGate['allow_general_combination'] ?? false) === true) {
            $sourceRoute = (string) ($expectations[$sourceProductKey]['route'] ?? 'unknown');
            $targetRoute = (string) ($expectations[$targetProductKey]['route'] ?? 'unknown');
            if($relationshipType !== 'complements' || $sourceRoute !== 'oral' || $targetRoute !== 'oral') {
                $errors[] = 'general_combination_only_for_reviewed_oral_complements';
            }
        }
        if($relationshipType === 'sequence' && (int) ($record['sequence_order'] ?? 0) <= 0) {
            $errors[] = 'sequence_order_required';
        }
        if(!preg_match('/^[a-f0-9]{64}$/', (string) ($record['source_content_hash'] ?? ''))) {
            $errors[] = 'invalid_source_content_hash';
        }
        if(!$this->validAccessedAt((string) ($record['accessed_at'] ?? ''))) {
            $errors[] = 'invalid_accessed_at';
        }
        $hasExactSource = false;
        foreach((array) ($record['source_refs'] ?? []) as $sourceRef) {
            if(!is_array($sourceRef) || (string) ($sourceRef['source_type'] ?? '') !== 'official_product_relationship') {
                continue;
            }
            $sourceMarket = $this->normalizeMarketCode((string) ($sourceRef['market_code'] ?? ''));
            $sourceValidation = $this->validateOfficialEvidenceUrl((string) ($sourceRef['source_url'] ?? ''), $sourceMarket);
            if(
                $this->normalizeLanguageCode((string) ($sourceRef['language_code'] ?? '')) === $languageCode
                && $sourceMarket === $marketCode
                && $sourceValidation['valid']
                && preg_match('/^[a-f0-9]{64}$/', (string) ($sourceRef['content_sha256'] ?? ''))
                && hash_equals((string) ($record['source_content_hash'] ?? ''), (string) ($sourceRef['content_sha256'] ?? ''))
                && in_array($targetProductKey, (array) ($sourceRef['named_target_product_keys'] ?? []), true)
                && $this->validAccessedAt((string) ($sourceRef['accessed_at'] ?? ''))
            ) {
                $hasExactSource = true;
                break;
            }
        }
        if(!$hasExactSource) {
            $errors[] = 'exact_official_relationship_source_required';
        }

        return array_values(array_unique($errors));
    }

    private function normalizeOfficialIdentityName(string $value): string {
        $value = str_replace(['™', '®', '℠'], ' ', $value);
        $value = $this->normalizeSearchText($value);
        $tokens = preg_split('/\s+/u', $value) ?: [];
        $tokens = array_values(array_filter($tokens, static fn(string $token): bool => !in_array(
            $token,
            ['forever', 'living', 'products', 'product', 'by'],
            true
        )));
        return implode(' ', $tokens);
    }

    private function officialFactRecordIsComplete(array $record): bool {
        return !empty($record['use_cases'])
            && (!empty($record['ingredient_facts']) || !empty($record['approved_support_facts']))
            && !empty($record['usage_facts'])
            && !empty($record['safety_notes'])
            && (string) ($record['safety_review_status'] ?? 'unknown') === 'complete'
            && (string) ($record['claim_review_status'] ?? 'unknown') === 'approved';
    }

    private function validAccessedAt(string $value): bool {
        if(trim($value) === '') {
            return false;
        }
        try {
            $date = new \DateTimeImmutable($value);
            $minimum = new \DateTimeImmutable('2020-01-01T00:00:00Z');
            $maximum = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->modify('+1 day');
            return $date >= $minimum && $date <= $maximum;
        } catch(\Throwable $exception) {
            return false;
        }
    }

    private function isAccessedAtFresh(string $value, int $maximumAgeDays): bool {
        if(!$this->validAccessedAt($value) || $maximumAgeDays < 1) {
            return false;
        }
        try {
            $date = new \DateTimeImmutable($value);
            $minimum = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
                ->modify('-' . $maximumAgeDays . ' days');
            return $date >= $minimum;
        } catch(\Throwable $exception) {
            return false;
        }
    }

    /** @return array{valid:bool,errors:list<string>} */
    private function validateOfficialEvidenceUrl(string $url, string $marketCode): array {
        $url = trim($url);
        $marketCode = $this->normalizeMarketCode($marketCode);
        if($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'errors' => ['invalid_url']];
        }
        $parts = parse_url($url);
        $scheme = mb_strtolower((string) ($parts['scheme'] ?? ''));
        $host = mb_strtolower((string) ($parts['host'] ?? ''));
        $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
        $errors = [];
        if($scheme !== 'https') {
            $errors[] = 'https_required';
        }
        if($host === 'foreverliving.com') {
            $offerValidation = $this->validateOfferUrl($marketCode, $url);
            if(!$offerValidation['valid']) {
                $errors = array_merge($errors, $offerValidation['errors']);
            }
            if(!str_contains(mb_strtolower($path), '/products/')) {
                $errors[] = 'official_page_is_not_product_detail';
            }
        } elseif($host === 'cdn.foreverliving.com') {
            if(!str_starts_with(mb_strtolower($path), '/content/marketingconfigurablecontent/')) {
                $errors[] = 'unsupported_official_cdn_path';
            }
            if($marketCode === 'hr' && !str_contains(mb_strtolower($path), '/hrv/')) {
                $errors[] = 'official_cdn_market_mismatch';
            }
        } else {
            $errors[] = 'unapproved_official_host';
        }

        return ['valid' => empty($errors), 'errors' => array_values(array_unique($errors))];
    }

    private function inspectVerifiedOverlayCoverage(array $overlay, array $groups): array {
        $groupLanguages = [];
        foreach($groups as $productKey => $rows) {
            foreach($rows as $row) {
                $languageCode = $this->normalizeLanguageCode((string) ($row['language_code'] ?? $row['language'] ?? ''));
                if($languageCode !== '') {
                    $groupLanguages[$productKey][$languageCode] = true;
                }
            }
        }

        $total = 0;
        $matched = 0;
        $warnings = [];
        foreach($overlay as $productKey => $languages) {
            foreach($languages as $languageCode => $_) {
                $total++;
                if(!empty($groupLanguages[$productKey][$languageCode])) {
                    $matched++;
                } elseif(!isset($groups[$productKey])) {
                    $warnings[] = 'verified_overlay_product_not_in_legacy:' . $productKey . ':' . $languageCode;
                } else {
                    $warnings[] = 'verified_overlay_language_not_in_legacy:' . $productKey . ':' . $languageCode;
                }
            }
        }

        return [
            'total_localizations' => $total,
            'matched_localizations' => $matched,
            'unmatched_localizations' => $total - $matched,
            'warnings' => $warnings,
        ];
    }

    /**
     * Load only published legacy rows that carry at least one shop link. Optional
     * legacy columns are selected defensively for older installations.
     */
    private function loadLegacyRows(): array {
        if(!$this->tableExists('blog_posts') || !$this->columnExists('blog_posts', 'webshop_links')) {
            return [];
        }

        $optional = [
            'search_aliases' => "''",
            'shop_context' => "''",
            'sku' => "''",
            'keywords' => "''",
            'description' => "''",
            'content' => "''",
            'last_datetime' => 'NULL',
            'datetime' => 'NULL',
        ];
        $select = [
            '`p`.`blog_post_id`',
            '`p`.`blog_posts_category_id`',
            '`p`.`url`',
            '`p`.`title`',
            '`p`.`language`',
            '`p`.`webshop_links`',
        ];

        foreach($optional as $column => $fallback) {
            $select[] = $this->columnExists('blog_posts', $column)
                ? '`p`.`' . $column . '`'
                : $fallback . ' AS `' . $column . '`';
        }

        $hasCategories = $this->tableExists('blog_posts_categories');
        if($hasCategories) {
            $select[] = '`c`.`url` AS `category_url`';
            $select[] = '`c`.`title` AS `category_title`';
        } else {
            $select[] = "'' AS `category_url`";
            $select[] = "'' AS `category_title`";
        }

        $sql = 'SELECT ' . implode(', ', $select)
            . ' FROM `blog_posts` `p` '
            . ($hasCategories ? 'LEFT JOIN `blog_posts_categories` `c` ON `c`.`blog_posts_category_id` = `p`.`blog_posts_category_id` ' : '')
            . "WHERE `p`.`is_published` = 1 AND COALESCE(`p`.`webshop_links`, '') NOT IN ('', '{}') "
            . 'ORDER BY `p`.`url` ASC, `p`.`language` ASC, `p`.`blog_post_id` ASC';
        $rows = $this->fetchAll($sql);

        $normalized = [];
        foreach($rows as $row) {
            $categoryUrl = trim((string) ($row['category_url'] ?? ''));
            if(in_array($categoryUrl, ['forever-card-club', 'forever-proizvodi'], true)) {
                continue;
            }

            $normalized[] = [
                'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
                'blog_posts_category_id' => (int) ($row['blog_posts_category_id'] ?? 0),
                'category_url' => $categoryUrl,
                'category_title' => trim((string) ($row['category_title'] ?? '')),
                'url' => trim((string) ($row['url'] ?? '')),
                'title' => trim((string) ($row['title'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'keywords' => trim((string) ($row['keywords'] ?? '')),
                'search_aliases' => trim((string) ($row['search_aliases'] ?? '')),
                'shop_context' => trim((string) ($row['shop_context'] ?? '')),
                'content' => trim((string) ($row['content'] ?? '')),
                'language' => trim((string) ($row['language'] ?? '')),
                'language_code' => $this->normalizeLanguageCode((string) ($row['language'] ?? '')),
                'webshop_links' => trim((string) ($row['webshop_links'] ?? '')),
                'sku' => trim((string) ($row['sku'] ?? '')),
                'datetime' => (string) ($row['datetime'] ?? ''),
                'last_datetime' => (string) ($row['last_datetime'] ?? ''),
            ];
        }

        return $normalized;
    }

    private function splitLegacyRows(array $rows): array {
        $productRows = [];
        $contentRows = [];

        foreach($rows as $row) {
            if($this->isContentOnlyLegacyRow($row)) {
                $contentRows[] = $row;
            } else {
                $productRows[] = $row;
            }
        }

        return [$productRows, $contentRows];
    }

    private function isContentOnlyLegacyRow(array $row): bool {
        $haystack = $this->normalizeSearchText(implode(' ', [
            (string) ($row['title'] ?? ''),
            str_replace('-', ' ', (string) ($row['url'] ?? '')),
        ]));

        if($haystack === '') {
            return true;
        }

        return preg_match('/(^|\s)(iskustv\p{L}*|experience|testimonial|review|recenz\p{L}*|vodic|guide|how to|moja prica|my story)(\s|$)/u', $haystack) === 1;
    }

    /**
     * Same slug is the existing HR/EN translation contract. Product_key is the
     * stable slug-derived identity; distinct slugs are never merged merely due
     * to a weak title substring or absent SKU.
     */
    private function groupLegacyProductRows(array $rows): array {
        $groups = [];
        $identityKeys = [];
        $keyOrigins = [];

        foreach($rows as $row) {
            $sourceSlug = mb_strtolower(trim((string) ($row['url'] ?? ''), " \t\n\r\0\x0B/"));
            $slug = $this->slugify($sourceSlug);
            if($slug === '') {
                continue;
            }

            $normalizedTitle = $this->normalizeSearchText((string) ($row['title'] ?? ''));
            $gelIdentity = '';
            if(preg_match('/(^|\s)aloe vera gelly($|\s)/u', $normalizedTitle)) {
                $gelIdentity = '|gelly';
            } elseif(preg_match('/(^|\s)aloe vera gel($|\s)/u', $normalizedTitle)) {
                $gelIdentity = '|gel';
            }
            $sourceIdentity = $sourceSlug . $gelIdentity;

            if(isset($identityKeys[$sourceIdentity])) {
                $productKey = $identityKeys[$sourceIdentity];
            } else {
                $productKey = $this->canonicalProductKey($slug, (string) ($row['title'] ?? ''));
                if(isset($keyOrigins[$productKey]) && $keyOrigins[$productKey] !== $sourceIdentity) {
                    $productKey = rtrim(mb_substr($productKey, 0, 119), '-') . '-' . substr(hash('sha256', $sourceIdentity), 0, 8);
                }
                $identityKeys[$sourceIdentity] = $productKey;
                $keyOrigins[$productKey] = $sourceIdentity;
            }
            $groups[$productKey][] = $row;
        }

        ksort($groups);
        return $groups;
    }

    /**
     * Link experience/guide articles to a canonical product by exact SKU first,
     * then shared normalized official URLs. Unresolved content is reported but
     * never promoted to a product.
     */
    private function mapContentRowsToProductGroups(array $contentRows, array $groups): array {
        $groupSignals = [];
        foreach($groups as $productKey => $rows) {
            $skus = [];
            $urls = [];
            $tokens = [];
            foreach($rows as $row) {
                $sku = $this->normalizeSku((string) ($row['sku'] ?? ''));
                if($sku !== '') {
                    $skus[$sku] = true;
                }
                foreach($this->decodeLegacyWebshopLinks((string) ($row['webshop_links'] ?? '')) as $url) {
                    $normalizedUrl = $this->normalizeOfferUrl((string) $url);
                    if($normalizedUrl !== '') {
                        $urls[$normalizedUrl] = true;
                    }
                }
                foreach($this->meaningfulProductTokens((string) ($row['title'] ?? '')) as $token) {
                    $tokens[$token] = true;
                }
            }
            $groupSignals[$productKey] = ['skus' => $skus, 'urls' => $urls, 'tokens' => $tokens];
        }

        $links = [];
        foreach($contentRows as $contentRow) {
            $contentSku = $this->normalizeSku((string) ($contentRow['sku'] ?? ''));
            $contentUrls = [];
            foreach($this->decodeLegacyWebshopLinks((string) ($contentRow['webshop_links'] ?? '')) as $url) {
                $normalizedUrl = $this->normalizeOfferUrl((string) $url);
                if($normalizedUrl !== '') {
                    $contentUrls[$normalizedUrl] = true;
                }
            }
            $contentTokens = array_fill_keys($this->meaningfulProductTokens((string) ($contentRow['title'] ?? '')), true);
            $bestProductKey = '';
            $bestScore = 0;

            foreach($groupSignals as $productKey => $signals) {
                $score = 0;
                if($contentSku !== '' && isset($signals['skus'][$contentSku])) {
                    $score += 100;
                }
                $score += count(array_intersect_key($contentUrls, $signals['urls'])) * 20;
                $score += count(array_intersect_key($contentTokens, $signals['tokens'])) * 3;

                if($score > $bestScore || ($score === $bestScore && $score > 0 && strcmp($productKey, $bestProductKey) < 0)) {
                    $bestScore = $score;
                    $bestProductKey = $productKey;
                }
            }

            if($bestProductKey !== '' && $bestScore >= 20) {
                $links[$bestProductKey][] = $contentRow;
            }
        }

        return $links;
    }

    /**
     * Materialize a reviewed current-shop product that has no legacy blog row.
     * It enters as lookup_only unless the same exact-language official source
     * later supplies the full recommendation fact contract.
     */
    private function buildOfficialCanonicalEntry(array $record, array $officialSourceRecords): ?array {
        $productKey = (string) ($record['product_key'] ?? '');
        $languageCode = $this->normalizeLanguageCode((string) ($record['language_code'] ?? ''));
        $canonicalName = trim((string) ($record['canonical_name'] ?? ''));
        if($productKey === '' || $languageCode === '' || $canonicalName === '') {
            return null;
        }

        $marketOffers = [];
        $offerVariants = [];
        foreach((array) ($record['offer_variants'] ?? []) as $variant) {
            if(!is_array($variant) || (string) ($variant['review_status'] ?? '') !== 'approved') {
                continue;
            }
            $offerVariants[] = $variant;
            $marketCode = $this->normalizeMarketCode((string) ($variant['market_code'] ?? ''));
            if($marketCode === '' || ($variant['is_primary'] ?? false) !== true) {
                continue;
            }
            $marketOffers[$marketCode] = [
                'availability_status' => (string) ($variant['availability_status'] ?? 'unknown'),
                'official_url' => (string) ($variant['official_url'] ?? ''),
                'source_blog_post_ids' => [],
                'source_official_inventory_keys' => [(string) ($variant['offer_key'] ?? '')],
                'source_hash' => hash('sha256', $this->canonicalJson($variant)),
            ];
        }
        ksort($marketOffers, SORT_STRING);

        $expectations = $this->augmentOfficialSourceExpectations([], [$record]);
        $approvedSources = [];
        foreach($officialSourceRecords as $sourceRecord) {
            if(
                (string) ($sourceRecord['review_status'] ?? '') === 'approved'
                && empty($this->officialSourceIdentityErrors($sourceRecord, $expectations))
            ) {
                $approvedSources[] = $sourceRecord;
            }
        }
        $primarySource = null;
        foreach($approvedSources as $sourceRecord) {
            if(
                $this->normalizeLanguageCode((string) ($sourceRecord['language_code'] ?? '')) === $languageCode
                && ($sourceRecord['is_primary_for_language'] ?? false) === true
            ) {
                $primarySource = $sourceRecord;
                break;
            }
        }

        $facts = [
            'use_cases' => [],
            'ingredient_facts' => [],
            'approved_support_facts' => [],
            'usage_facts' => [],
            'safety_notes' => [],
        ];
        if(is_array($primarySource)) {
            foreach(array_keys($facts) as $field) {
                $facts[$field] = in_array($field, ['ingredient_facts', 'usage_facts', 'safety_notes'], true)
                    ? $this->cleanOfficialEvidenceList($primarySource[$field] ?? [])
                    : $this->cleanStringList($primarySource[$field] ?? []);
            }
        }
        $claimReviewStatus = is_array($primarySource)
            && (string) ($primarySource['claim_review_status'] ?? 'unknown') === 'approved'
            && (string) ($primarySource['review_scope'] ?? '') === 'complete_recommendation_facts'
            ? 'approved'
            : 'unknown';
        if($claimReviewStatus !== 'approved') {
            $facts['use_cases'] = [];
            $facts['approved_support_facts'] = [];
        }
        $sku = $this->normalizeSku((string) ($record['sku'] ?? ''));
        $skuVerification = $this->verifySkuAgainstMarketOffers($sku, $marketOffers);
        $aliases = $this->cleanStringList(array_merge(
            [$canonicalName],
            (array) ($record['aliases'] ?? []),
            $sku !== '' && $skuVerification['verified'] ? [$sku] : []
        ));
        $allergensContains = is_array($primarySource)
            ? $this->cleanStringList($primarySource['allergens_contains'] ?? [])
            : [];
        $safetyFlags = is_array($primarySource)
            ? $this->cleanStringList($primarySource['safety_flags'] ?? [])
            : [];
        $mandatorySafetyNotes = is_array($primarySource)
            ? $this->cleanOfficialEvidenceList($primarySource['mandatory_safety_notes'] ?? [])
            : [];
        $safetyReviewStatus = is_array($primarySource)
            && (string) ($primarySource['safety_review_status'] ?? 'unknown') === 'complete'
            ? 'complete'
            : 'unknown';
        $suitabilityReviewStatus = is_array($primarySource)
            && (string) ($primarySource['suitability_review_status'] ?? 'unknown') === 'complete'
            ? 'complete'
            : 'unknown';
        $suitabilityFlags = $suitabilityReviewStatus === 'complete'
            ? $this->cleanStringList($primarySource['suitability_flags'] ?? [])
            : [];
        $availabilityStatus = !empty(array_filter(
            $marketOffers,
            static fn(array $offer): bool => (string) ($offer['availability_status'] ?? '') === 'available'
        )) ? 'available' : 'unknown';
        $shopReferenceUrl = '';
        foreach($marketOffers as $offer) {
            if((string) ($offer['availability_status'] ?? '') === 'available') {
                $shopReferenceUrl = (string) ($offer['official_url'] ?? '');
                break;
            }
        }
        /* Official shop URLs are purchase destinations, not knowledge articles.
         * They may only surface through the guarded recommendation card/FBO
         * path; lookup-only records must never bypass that gate as suggestions. */
        $articleUrl = '';
        $reviewedFacts = $facts;
        $reviewedFacts['source_refs'] = is_array($primarySource) ? [$primarySource] : [];
        $advisorStatus = $this->entryIsRecommendationComplete(
            (string) ($record['route'] ?? 'unknown'),
            $marketOffers,
            $facts,
            $reviewedFacts,
            $shopReferenceUrl,
            $safetyReviewStatus,
            $claimReviewStatus,
            is_array($primarySource) ? (string) ($primarySource['market_code'] ?? '') : ''
        ) ? 'recommendable' : 'lookup_only';

        $sourceRefs = array_values((array) ($record['source_refs'] ?? []));
        $sourceRefs[] = [
            'source_type' => 'official_canonical_product',
            'config' => 'app/config/fcc_product_catalog_official_sources.php',
            'product_key' => $productKey,
            'language_code' => $languageCode,
            'record_hash' => hash('sha256', $this->canonicalJson($record)),
        ];
        foreach($approvedSources as $sourceRecord) {
            $sourceMarketCode = $this->normalizeMarketCode((string) ($sourceRecord['market_code'] ?? ''));
            $canonicalOfferUrl = isset($marketOffers[$sourceMarketCode]) && is_array($marketOffers[$sourceMarketCode])
                ? $this->normalizeOfferUrl((string) ($marketOffers[$sourceMarketCode]['official_url'] ?? ''))
                : '';
            $normalizedEvidenceUrl = $this->normalizeOfferUrl((string) ($sourceRecord['source_url'] ?? ''));
            $sourceRefs[] = [
                'source_type' => 'official_product_source',
                'config' => 'app/config/fcc_product_catalog_official_sources.php',
                'source_id' => (string) ($sourceRecord['source_id'] ?? ''),
                'product_key' => $productKey,
                'language_code' => $languageCode,
                'market_code' => $sourceMarketCode,
                'source_url' => (string) ($sourceRecord['source_url'] ?? ''),
                'canonical_offer_url' => $canonicalOfferUrl,
                'canonical_offer_binding' => $this->officialEvidenceCanonicalOfferBinding(
                    $normalizedEvidenceUrl,
                    $canonicalOfferUrl,
                    $sourceMarketCode,
                    (string) ($sourceRecord['page_item_code'] ?? '')
                ),
                'accessed_at' => (string) ($sourceRecord['accessed_at'] ?? ''),
                'content_sha256' => (string) ($sourceRecord['content_sha256'] ?? ''),
                'evidence_sha256' => (string) ($sourceRecord['evidence_sha256'] ?? ''),
                'section_sha256' => is_array($sourceRecord['label_evidence']['section_sha256'] ?? null)
                    ? $sourceRecord['label_evidence']['section_sha256']
                    : [],
                'review_status' => 'approved',
                'identity_status' => (string) ($sourceRecord['identity_status'] ?? ''),
                'verified_use_case_count' => count((array) ($sourceRecord['use_cases'] ?? [])),
                'verified_fact_count' => count((array) ($sourceRecord['ingredient_facts'] ?? []))
                    + count((array) ($sourceRecord['approved_support_facts'] ?? [])),
                'verified_usage_count' => count((array) ($sourceRecord['usage_facts'] ?? [])),
                'verified_safety_count' => count((array) ($sourceRecord['safety_notes'] ?? [])),
                'allergens_contains_count' => count((array) ($sourceRecord['allergens_contains'] ?? [])),
                'safety_flags_count' => count((array) ($sourceRecord['safety_flags'] ?? [])),
                'mandatory_safety_count' => count((array) ($sourceRecord['mandatory_safety_notes'] ?? [])),
                'safety_review_status' => (string) ($sourceRecord['safety_review_status'] ?? 'unknown'),
                'suitability_review_status' => (string) ($sourceRecord['suitability_review_status'] ?? 'unknown'),
                'claim_review_status' => (string) ($sourceRecord['claim_review_status'] ?? 'unknown'),
                'record_hash' => hash('sha256', $this->canonicalJson($sourceRecord)),
            ];
        }

        $entry = [
            'product_key' => $productKey,
            'language_code' => $languageCode,
            'canonical_name' => $canonicalName,
            'normalized_name' => $this->normalizeSearchText($canonicalName),
            'sku' => $sku,
            'category_key' => (string) ($record['category_key'] ?? ''),
            'route' => (string) ($record['route'] ?? 'unknown'),
            'advisor_status' => $advisorStatus,
            'availability_status' => $availabilityStatus,
            'aliases' => $aliases,
            'use_cases' => $facts['use_cases'],
            'ingredient_facts' => $facts['ingredient_facts'],
            'approved_support_facts' => $facts['approved_support_facts'],
            'usage_facts' => $facts['usage_facts'],
            'safety_notes' => $facts['safety_notes'],
            'allergens_contains' => $allergensContains,
            'safety_flags' => $safetyFlags,
            'mandatory_safety_notes' => $mandatorySafetyNotes,
            'safety_review_status' => $safetyReviewStatus,
            'suitability_flags' => $suitabilityFlags,
            'suitability_review_status' => $suitabilityReviewStatus,
            'claim_review_status' => $claimReviewStatus,
            'offer_variants' => $offerVariants,
            'market_offers' => $marketOffers,
            'blog_post_id' => 0,
            'article_url' => $articleUrl,
            'source_refs' => $sourceRefs,
        ];
        $entry['source_hash'] = hash('sha256', $this->canonicalJson($entry));
        return $entry;
    }

    /**
     * Converts the locked category inventory into runtime offer variants and
     * deterministically selects a family primary. Package/promo variants remain
     * variants; they never become separate recommendation identities.
     *
     * @return array{variants:list<array>,primary:?array}
     */
    private function buildInventoryOfferVariants(
        array $family,
        string $preferredItemCode,
        string $snapshotHash
    ): array {
        $variants = [];
        $defaultCode = $this->normalizeProductCode((string) ($family['default_item_code'] ?? ''));
        $preferredItemCode = $this->normalizeProductCode($preferredItemCode);
        foreach((array) ($family['offers'] ?? []) as $index => $offer) {
            if(!is_array($offer)) {
                continue;
            }
            $itemCode = $this->normalizeProductCode((string) ($offer['item_code'] ?? ''));
            if($itemCode === '' && $defaultCode !== '') {
                $itemCode = $defaultCode;
            }
            $officialUrl = $this->normalizeOfferUrl((string) ($offer['official_url'] ?? ''));
            $availabilityStatus = (string) ($offer['availability'] ?? '') === 'available'
                ? 'available'
                : 'sold_out';
            $identitySlug = '';
            $identityReviewNote = '';
            if($itemCode === '' || !in_array($itemCode, $this->extractOfficialProductCodeSignals($officialUrl), true)) {
                $urlPath = rawurldecode((string) (parse_url($officialUrl, PHP_URL_PATH) ?? ''));
                $identitySlug = $this->slugify((string) basename(rtrim($urlPath, '/')));
                $identityReviewNote = 'Reviewed umbrella offer from locked exact-market HR inventory reconciliation.';
            }
            $offerKeyToken = ($itemCode !== '' ? mb_strtolower($itemCode) : 'umbrella')
                . '-' . substr(hash('sha256', $officialUrl), 0, 10);
            $variant = [
                'offer_key' => 'hr-' . $offerKeyToken . '-' . ((int) $index + 1),
                'market_code' => 'hr',
                'item_code' => $itemCode,
                'identity_slug' => $identitySlug,
                'identity_review_note' => $identityReviewNote,
                'package_label' => trim((string) ($offer['package_label'] ?? $offer['name'] ?? '')),
                'official_url' => $officialUrl,
                'availability_status' => $availabilityStatus,
                'is_primary' => false,
                'accessed_at' => '2026-08-25T20:46:00+00:00',
                'content_sha256' => hash('sha256', $snapshotHash . '|' . $this->canonicalJson($offer)),
                'review_status' => 'approved',
                '_primary_score' => 0,
            ];
            if($availabilityStatus === 'available') {
                $variant['_primary_score'] += 100;
            }
            if($preferredItemCode !== '' && $itemCode === $preferredItemCode) {
                $variant['_primary_score'] += 1000;
            } elseif($defaultCode !== '' && $itemCode === $defaultCode) {
                $variant['_primary_score'] += 800;
            }
            $variantName = $this->normalizeSearchText((string) ($offer['name'] ?? ''));
            $package = $this->normalizeSearchText((string) ($offer['package_label'] ?? ''));
            if(preg_match('/\b(?:4pack|promo|12 x|tripack)\b/u', $variantName . ' ' . $package) !== 1) {
                $variant['_primary_score'] += 50;
            }
            $variant['_primary_score'] -= strlen($itemCode);
            $variants[] = $variant;
        }

        usort($variants, static function(array $left, array $right): int {
            $scoreCompare = ((int) ($right['_primary_score'] ?? 0)) <=> ((int) ($left['_primary_score'] ?? 0));
            return $scoreCompare !== 0
                ? $scoreCompare
                : strcmp((string) ($left['offer_key'] ?? ''), (string) ($right['offer_key'] ?? ''));
        });
        if(isset($variants[0])) {
            $variants[0]['is_primary'] = true;
        }
        foreach($variants as &$variant) {
            unset($variant['_primary_score']);
        }
        unset($variant);

        $primary = $variants[0] ?? null;
        usort($variants, static fn(array $left, array $right): int => strcmp(
            (string) ($left['offer_key'] ?? ''),
            (string) ($right['offer_key'] ?? '')
        ));
        return ['variants' => $variants, 'primary' => $primary];
    }

    /** @return list<array> */
    private function mergeApprovedOfferVariants(array $baseVariants, array $reviewedVariants): array {
        $merged = [];
        foreach($baseVariants as $variant) {
            if(!is_array($variant)) {
                continue;
            }
            $key = $this->normalizeOfferUrl((string) ($variant['official_url'] ?? ''))
                . '|' . $this->normalizeProductCode((string) ($variant['item_code'] ?? ''));
            $merged[$key] = $variant;
        }
        $reviewedHasPrimary = !empty(array_filter(
            $reviewedVariants,
            static fn($variant): bool => is_array($variant) && ($variant['is_primary'] ?? false) === true
        ));
        if($reviewedHasPrimary) {
            foreach($merged as &$variant) {
                $variant['is_primary'] = false;
            }
            unset($variant);
        }
        foreach($reviewedVariants as $variant) {
            if(!is_array($variant)) {
                continue;
            }
            $key = $this->normalizeOfferUrl((string) ($variant['official_url'] ?? ''))
                . '|' . $this->normalizeProductCode((string) ($variant['item_code'] ?? ''));
            $merged[$key] = array_merge($merged[$key] ?? [], $variant);
        }
        $variants = array_values($merged);
        usort($variants, static fn(array $left, array $right): int => strcmp(
            (string) ($left['offer_key'] ?? ''),
            (string) ($right['offer_key'] ?? '')
        ));
        return $variants;
    }

    private function buildEntriesForGroup(
        string $productKey,
        array $rows,
        array $contentRows,
        array $verifiedFactsByLanguage = [],
        array $officialSourceRecords = [],
        ?array $identityMigration = null,
        ?array $inventoryFamily = null,
        ?array $retiredInventoryRecord = null,
        string $inventorySnapshotHash = ''
    ): array {
        $warnings = [];
        $skippedRows = [];
        $rowsByLanguage = [];

        foreach($rows as $row) {
            $languageCode = $this->normalizeLanguageCode((string) ($row['language_code'] ?? $row['language'] ?? ''));
            $languageCode = $languageCode !== '' ? $languageCode : 'und';

            if(!isset($rowsByLanguage[$languageCode])) {
                $rowsByLanguage[$languageCode] = $row;
                continue;
            }

            $existing = $rowsByLanguage[$languageCode];
            if($this->legacyRowIsNewer($row, $existing)) {
                $skippedRows[] = [
                    'blog_post_id' => (int) ($existing['blog_post_id'] ?? 0),
                    'reason' => 'duplicate_language_row_replaced_by_newer_row',
                    'product_key' => $productKey,
                    'language_code' => $languageCode,
                ];
                $rowsByLanguage[$languageCode] = $row;
            } else {
                $skippedRows[] = [
                    'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
                    'reason' => 'duplicate_language_row_ignored',
                    'product_key' => $productKey,
                    'language_code' => $languageCode,
                ];
            }
        }

        $skus = [];
        foreach($rows as $row) {
            $sku = $this->normalizeSku((string) ($row['sku'] ?? ''));
            if($sku !== '') {
                $skus[$sku] = true;
            }
        }
        $skuValues = array_map(static fn($value): string => (string) $value, array_keys($skus));
        sort($skuValues);
        $sku = count($skuValues) === 1 ? $skuValues[0] : '';
        $identityConflict = count($skuValues) > 1;
        if($identityConflict) {
            $warnings[] = $productKey . ':conflicting_skus:' . implode(',', $skuValues);
        }

        $legacySku = $sku;
        $marketBuild = $this->buildMarketOffers($rows);
        $marketOffers = $marketBuild['offers'];
        $warnings = array_merge($warnings, $marketBuild['warnings']);
        $preferredInventoryCode = is_array($identityMigration)
            ? $this->normalizeProductCode((string) ($identityMigration['current_item_code'] ?? $sku))
            : $this->normalizeProductCode($sku);
        $inventoryOfferBuild = is_array($inventoryFamily)
            ? $this->buildInventoryOfferVariants($inventoryFamily, $preferredInventoryCode, $inventorySnapshotHash)
            : ['variants' => [], 'primary' => null];
        $catalogOfferVariants = (array) ($inventoryOfferBuild['variants'] ?? []);
        if(is_array($inventoryOfferBuild['primary'] ?? null)) {
            $primaryInventoryOffer = $inventoryOfferBuild['primary'];
            $primaryInventoryUrl = $this->normalizeOfferUrl((string) ($primaryInventoryOffer['official_url'] ?? ''));
            $primaryInventoryStatus = (string) ($primaryInventoryOffer['availability_status'] ?? 'unknown');
            $marketOffers['hr'] = [
                'availability_status' => $primaryInventoryStatus === 'available' ? 'available' : 'blocked',
                'official_url' => $primaryInventoryStatus === 'available' ? $primaryInventoryUrl : '',
                'source_blog_post_ids' => [],
                'source_official_ids' => ['official-inventory:' . $productKey],
                'source_hash' => hash('sha256', $this->canonicalJson($primaryInventoryOffer)),
            ];
        }
        $migrationOfferVariants = [];
        $migrationLanguageCode = '';
        $migrationLegacyCode = '';
        if(is_array($identityMigration) && (string) ($identityMigration['review_status'] ?? '') === 'approved') {
            $migrationLanguageCode = $this->normalizeLanguageCode((string) ($identityMigration['language_code'] ?? ''));
            $migrationLegacyCode = $this->normalizeProductCode((string) ($identityMigration['legacy_item_code'] ?? ''));
            $currentCode = $this->normalizeProductCode((string) ($identityMigration['current_item_code'] ?? ''));
            if($currentCode !== '') {
                $sku = $currentCode;
                $identityConflict = false;
            }
            foreach((array) ($identityMigration['offer_variants'] ?? []) as $variant) {
                if(!is_array($variant) || (string) ($variant['review_status'] ?? '') !== 'approved') {
                    continue;
                }
                $variantMarket = $this->normalizeMarketCode((string) ($variant['market_code'] ?? ''));
                $variantUrl = $this->normalizeOfferUrl((string) ($variant['official_url'] ?? ''));
                $variantStatus = (string) ($variant['availability_status'] ?? 'unknown');
                if($variantMarket === '' || !$this->validateOfferUrl($variantMarket, $variantUrl)['valid']) {
                    continue;
                }
                $migrationOfferVariants[] = $variant;
                if(($variant['is_primary'] ?? false) !== true) {
                    continue;
                }
                $marketOffers[$variantMarket] = [
                    'availability_status' => $variantStatus === 'available' ? 'available' : 'blocked',
                    'official_url' => $variantUrl,
                    'source_blog_post_ids' => [],
                    'source_official_ids' => ['identity-migration:' . $productKey],
                    'source_hash' => hash('sha256', $this->canonicalJson($variant)),
                ];
            }
            $warnings[] = $productKey . ':official_identity_migration_applied:'
                . ($legacySku !== '' ? $legacySku : 'none') . '->' . ($sku !== '' ? $sku : 'none');
        }
        if($migrationOfferVariants) {
            $catalogOfferVariants = $this->mergeApprovedOfferVariants(
                $catalogOfferVariants,
                $migrationOfferVariants
            );
        }
        $groupExpectations = $this->buildOfficialSourceExpectations([$productKey => $rows]);
        if(is_array($identityMigration)) {
            $groupExpectations = $this->augmentOfficialExpectationsWithIdentityMigrations(
                $groupExpectations,
                [$identityMigration]
            );
        }
        $approvedOfficialSources = [];
        foreach($officialSourceRecords as $officialSourceRecord) {
            $identityErrors = $this->officialSourceIdentityErrors($officialSourceRecord, $groupExpectations);
            $reviewStatus = (string) ($officialSourceRecord['review_status'] ?? 'candidate');
            if($reviewStatus === 'approved' && empty($identityErrors)) {
                $approvedOfficialSources[] = $officialSourceRecord;
                continue;
            }
            $warnings[] = $productKey . ':official_source_' . $reviewStatus . ':'
                . (string) ($officialSourceRecord['source_id'] ?? '') . ':'
                . ($identityErrors ? implode(',', $identityErrors) : implode(',', (array) ($officialSourceRecord['quarantine_reasons'] ?? [])));
        }

        foreach($approvedOfficialSources as $officialSourceRecord) {
            if(
                (string) ($officialSourceRecord['source_type'] ?? '') !== 'official_product_page'
                || ($officialSourceRecord['replaces_legacy_offer_url'] ?? false) !== true
            ) {
                continue;
            }
            $replacementMarket = $this->normalizeMarketCode((string) ($officialSourceRecord['market_code'] ?? ''));
            $replacementUrl = $this->normalizeOfferUrl((string) ($officialSourceRecord['source_url'] ?? ''));
            $replacementValidation = $this->validateOfferUrl($replacementMarket, $replacementUrl);
            if($replacementMarket === '' || $replacementUrl === '' || !$replacementValidation['valid']) {
                continue;
            }
            $marketOffers[$replacementMarket] = [
                'availability_status' => 'available',
                'official_url' => $replacementUrl,
                'source_blog_post_ids' => [],
                'source_official_ids' => [(string) ($officialSourceRecord['source_id'] ?? '')],
                'source_hash' => hash('sha256', $replacementMarket . '|' . $replacementUrl . '|official-reviewed-replacement'),
            ];
            $warnings[] = $productKey . ':legacy_market_offer_replaced_by_reviewed_official_source:' . $replacementMarket;
        }
        $retiredOrOrphaned = is_array($retiredInventoryRecord)
            && (string) ($retiredInventoryRecord['status'] ?? '') === 'retired_or_orphaned';
        if($retiredOrOrphaned) {
            $retiredUrl = $this->normalizeOfferUrl((string) ($retiredInventoryRecord['legacy_url'] ?? ''));
            $marketOffers['hr'] = [
                'availability_status' => 'blocked',
                'official_url' => '',
                'source_blog_post_ids' => [],
                'source_official_ids' => ['retired-or-orphaned:' . $productKey],
                'source_hash' => hash('sha256', $this->canonicalJson($retiredInventoryRecord)),
            ];
            $catalogOfferVariants = [];
            $warnings[] = $productKey . ':retired_or_orphaned_current_hr_inventory';
        }
        $skuVerification = $this->verifySkuAgainstMarketOffers($sku, $marketOffers);
        if($sku !== '' && !$skuVerification['verified']) {
            $warningReason = $skuVerification['signals'] ? 'official_code_mismatch' : 'official_code_missing';
            $warnings[] = $productKey . ':sku_matcher_alias_disabled:' . $warningReason
                . ':legacy=' . $sku
                . ':signals=' . ($skuVerification['signals'] ? implode(',', $skuVerification['signals']) : 'none');
        } elseif($sku !== '' && !empty($skuVerification['conflicting_signals'])) {
            $warnings[] = $productKey . ':sku_confirmed_with_conflicting_official_codes:'
                . implode(',', $skuVerification['conflicting_signals']);
        }
        $aggregateAvailability = 'unknown';
        if(!empty(array_filter($marketOffers, static fn(array $offer): bool => ($offer['availability_status'] ?? '') === 'available'))) {
            $aggregateAvailability = 'available';
        } elseif(!empty(array_filter($marketOffers, static fn(array $offer): bool => ($offer['availability_status'] ?? '') === 'blocked'))) {
            $aggregateAvailability = 'blocked';
        }

        /* The reconciled official inventory is the identity authority for the
         * current product category. Legacy blog rows use translated/free-form
         * category slugs (for example `napitci`) which are unsuitable as a
         * stable retrieval filter and previously caused current HR products to
         * drift between category keys. */
        $categoryKey = '';
        if(is_array($inventoryFamily)) {
            foreach((array) ($inventoryFamily['listed_categories'] ?? []) as $listedCategory) {
                $candidate = $this->slugify((string) $listedCategory);
                if($candidate !== '') {
                    $categoryKey = $candidate;
                    break;
                }
            }
        }
        if($categoryKey === '') {
            foreach($rows as $row) {
                $candidate = $this->slugify((string) ($row['category_url'] ?? ''));
                if($candidate !== '') {
                    $categoryKey = $candidate;
                    break;
                }
            }
        }

        $route = $this->resolveGroupRoute($rows);
        foreach($approvedOfficialSources as $officialSourceRecord) {
            $reviewedRoute = trim((string) ($officialSourceRecord['page_route'] ?? ''));
            if(
                ($officialSourceRecord['is_primary_for_language'] ?? false) === true
                && (string) ($officialSourceRecord['identity_status'] ?? '') === 'verified_reviewed'
                && trim((string) ($officialSourceRecord['identity_review_note'] ?? '')) !== ''
                && trim((string) ($officialSourceRecord['route_override_review_note'] ?? '')) !== ''
                && in_array($reviewedRoute, self::ALLOWED_ROUTES, true)
                && $reviewedRoute !== 'unknown'
            ) {
                $route = $reviewedRoute;
                $warnings[] = $productKey . ':reviewed_official_route_override:' . $reviewedRoute;
                break;
            }
        }
        $baseSourceRefs = [];
        foreach($rows as $row) {
            $baseSourceRefs[] = $this->legacyBlogSourceRef($row, 'product_article');
        }
        foreach($contentRows as $contentRow) {
            $baseSourceRefs[] = $this->legacyBlogSourceRef($contentRow, 'experience_or_guide');
        }
        foreach($marketBuild['blocked_sources'] as $blockedSource) {
            $baseSourceRefs[] = $blockedSource;
        }
        if($sku !== '') {
            $baseSourceRefs[] = [
                'source_type' => 'sku_identity_check',
                'legacy_sku' => $legacySku,
                'current_sku' => $sku,
                'status' => (string) $skuVerification['status'],
                'official_product_code_signals' => array_values($skuVerification['signals']),
                'market_product_code_signals' => $skuVerification['market_signals'],
                'conflicting_signals' => array_values($skuVerification['conflicting_signals']),
                'matcher_alias_enabled' => (bool) $skuVerification['verified'],
            ];
        }
        if(is_array($identityMigration) && (string) ($identityMigration['review_status'] ?? '') === 'approved') {
            $baseSourceRefs[] = [
                'source_type' => 'official_identity_migration',
                'config' => 'app/config/fcc_product_catalog_official_inventory.php',
                'product_key' => $productKey,
                'language_code' => $migrationLanguageCode,
                'market_code' => (string) ($identityMigration['market_code'] ?? ''),
                'legacy_item_code' => $migrationLegacyCode,
                'current_item_code' => $sku,
                'review_note' => (string) ($identityMigration['review_note'] ?? ''),
                'record_hash' => hash('sha256', $this->canonicalJson($identityMigration)),
            ];
        }
        if(is_array($inventoryFamily)) {
            $baseSourceRefs[] = [
                'source_type' => 'official_inventory_family',
                'config' => 'app/config/fcc_product_catalog_official_inventory.php',
                'product_key' => $productKey,
                'market_code' => 'hr',
                'snapshot_sha256' => $inventorySnapshotHash,
                'family_hash' => hash('sha256', $this->canonicalJson($inventoryFamily)),
                'offer_count' => count((array) ($inventoryFamily['offers'] ?? [])),
                'available' => ($inventoryFamily['available'] ?? false) === true,
            ];
        }
        if($retiredOrOrphaned) {
            $baseSourceRefs[] = array_merge($retiredInventoryRecord, [
                'source_type' => 'official_inventory_retired_or_orphaned',
                'config' => 'app/config/fcc_product_catalog_official_inventory.php',
            ]);
        }

        $entries = [];
        foreach($rowsByLanguage as $languageCode => $row) {
            $canonicalName = trim((string) ($row['title'] ?? ''));
            if(
                is_array($identityMigration)
                && (string) ($identityMigration['review_status'] ?? '') === 'approved'
                && $languageCode === $migrationLanguageCode
                && trim((string) ($identityMigration['current_canonical_name'] ?? '')) !== ''
            ) {
                $canonicalName = trim((string) $identityMigration['current_canonical_name']);
            }
            if($canonicalName === '') {
                $warnings[] = $productKey . ':' . $languageCode . ':missing_title';
                continue;
            }

            $verifiedFacts = isset($verifiedFactsByLanguage[$languageCode]) && is_array($verifiedFactsByLanguage[$languageCode])
                ? $verifiedFactsByLanguage[$languageCode]
                : [];
            $primaryOfficialSource = null;
            foreach($approvedOfficialSources as $officialSourceRecord) {
                if(
                    $this->normalizeLanguageCode((string) ($officialSourceRecord['language_code'] ?? '')) === $languageCode
                    && ($officialSourceRecord['is_primary_for_language'] ?? false) === true
                ) {
                    $primaryOfficialSource = $officialSourceRecord;
                    break;
                }
            }
            $officialFacts = [];
            if(is_array($primaryOfficialSource)) {
                foreach(['aliases', 'use_cases', 'ingredient_facts', 'approved_support_facts', 'usage_facts', 'safety_notes'] as $field) {
                    $officialFacts[$field] = in_array($field, ['ingredient_facts', 'usage_facts', 'safety_notes'], true)
                        ? $this->cleanOfficialEvidenceList($primaryOfficialSource[$field] ?? [])
                        : $this->cleanStringList($primaryOfficialSource[$field] ?? []);
                }
                $officialFacts['source_refs'] = [$primaryOfficialSource];
            }
            $claimReviewStatus = is_array($primaryOfficialSource)
                && (string) ($primaryOfficialSource['claim_review_status'] ?? 'unknown') === 'approved'
                && (string) ($primaryOfficialSource['review_scope'] ?? '') === 'complete_recommendation_facts'
                ? 'approved'
                : 'unknown';
            if($claimReviewStatus !== 'approved') {
                $verifiedFacts['use_cases'] = [];
                $verifiedFacts['approved_support_facts'] = [];
                $officialFacts['use_cases'] = [];
                $officialFacts['approved_support_facts'] = [];
            }
            /* Runtime facts are official-current only. Historical PDF/blog
             * overlays remain auditable source refs but never fill a missing
             * current usage, ingredient, safety or recommendation field. */
            $reviewedFacts = [
                'aliases' => $this->cleanStringList($officialFacts['aliases'] ?? []),
                'use_cases' => $this->cleanStringList($officialFacts['use_cases'] ?? []),
                'ingredient_facts' => $this->cleanOfficialEvidenceList($officialFacts['ingredient_facts'] ?? []),
                'approved_support_facts' => $this->cleanStringList($officialFacts['approved_support_facts'] ?? []),
                'usage_facts' => $this->cleanOfficialEvidenceList($officialFacts['usage_facts'] ?? []),
                'safety_notes' => $this->cleanOfficialEvidenceList($officialFacts['safety_notes'] ?? []),
                'source_refs' => array_values((array) ($officialFacts['source_refs'] ?? [])),
            ];
            $aliases = $this->buildAliases(
                $canonicalName,
                $sku,
                (string) ($row['search_aliases'] ?? ''),
                (bool) $skuVerification['verified'],
                (array) ($reviewedFacts['aliases'] ?? [])
            );
            if($migrationLegacyCode !== '' && $migrationLegacyCode !== $this->normalizeProductCode($sku)) {
                $aliases = array_values(array_filter(
                    $aliases,
                    fn(string $alias): bool => $this->normalizeProductCode($alias) !== $migrationLegacyCode
                ));
            }
            $facts = $reviewedFacts;
            $allergensContains = is_array($primaryOfficialSource)
                ? $this->cleanStringList($primaryOfficialSource['allergens_contains'] ?? [])
                : [];
            $safetyFlags = is_array($primaryOfficialSource)
                ? $this->cleanStringList($primaryOfficialSource['safety_flags'] ?? [])
                : [];
            $mandatorySafetyNotes = is_array($primaryOfficialSource)
                ? $this->cleanOfficialEvidenceList($primaryOfficialSource['mandatory_safety_notes'] ?? [])
                : [];
            $safetyReviewStatus = is_array($primaryOfficialSource)
                && (string) ($primaryOfficialSource['safety_review_status'] ?? 'unknown') === 'complete'
                ? 'complete'
                : 'unknown';
            $suitabilityReviewStatus = is_array($primaryOfficialSource)
                && (string) ($primaryOfficialSource['suitability_review_status'] ?? 'unknown') === 'complete'
                ? 'complete'
                : 'unknown';
            $suitabilityFlags = $suitabilityReviewStatus === 'complete'
                ? $this->cleanStringList($primaryOfficialSource['suitability_flags'] ?? [])
                : [];
            $articleUrl = $this->buildArticleUrl((string) ($row['url'] ?? ''), $languageCode);
            $advisorStatus = ($identityConflict || $retiredOrOrphaned) ? 'blocked' : 'lookup_only';

            if(!$identityConflict && !$retiredOrOrphaned && $this->entryIsRecommendationComplete(
                $route,
                $marketOffers,
                $facts,
                $reviewedFacts,
                $articleUrl,
                $safetyReviewStatus,
                $claimReviewStatus,
                is_array($primaryOfficialSource) ? (string) ($primaryOfficialSource['market_code'] ?? '') : ''
            )) {
                $advisorStatus = 'recommendable';
            }

            $sourceRefs = $baseSourceRefs;
            $sourceRefs[] = [
                'source_type' => 'legacy_localization',
                'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
                'language_code' => $languageCode,
                'description_hash' => hash('sha256', (string) ($row['description'] ?? '')),
                'keywords_hash' => hash('sha256', (string) ($row['keywords'] ?? '')),
                'shop_context_present' => trim((string) ($row['shop_context'] ?? '')) !== '',
            ];
            if($verifiedFacts) {
                foreach((array) ($verifiedFacts['source_refs'] ?? []) as $verifiedSourceRef) {
                    if(!is_array($verifiedSourceRef)) {
                        continue;
                    }
                    $verifiedSourceRef['relation_type'] = 'verified_fact_overlay';
                    $verifiedSourceRef['product_key'] = $productKey;
                    $verifiedSourceRef['language_code'] = $languageCode;
                    $sourceRefs[] = $verifiedSourceRef;
                }

                $sourceRefs[] = [
                    'source_type' => 'verified_facts_overlay',
                    'config' => 'app/config/fcc_product_catalog_verified_facts.php',
                    'product_key' => $productKey,
                    'language_code' => $languageCode,
                    'verified_use_case_count' => count((array) ($verifiedFacts['use_cases'] ?? [])),
                    'verified_fact_count' => count((array) ($verifiedFacts['ingredient_facts'] ?? []))
                        + count((array) ($verifiedFacts['approved_support_facts'] ?? [])),
                    'verified_usage_count' => count((array) ($verifiedFacts['usage_facts'] ?? [])),
                    'verified_safety_count' => count((array) ($verifiedFacts['safety_notes'] ?? [])),
                    'overlay_hash' => hash('sha256', $this->canonicalJson($verifiedFacts)),
                ];
            }

            foreach($officialSourceRecords as $officialSourceRecord) {
                if($this->normalizeLanguageCode((string) ($officialSourceRecord['language_code'] ?? '')) !== $languageCode) {
                    continue;
                }
                $sourceMarketCode = $this->normalizeMarketCode((string) ($officialSourceRecord['market_code'] ?? ''));
                $canonicalOfferUrl = isset($marketOffers[$sourceMarketCode]) && is_array($marketOffers[$sourceMarketCode])
                    ? $this->normalizeOfferUrl((string) ($marketOffers[$sourceMarketCode]['official_url'] ?? ''))
                    : '';
                $normalizedEvidenceUrl = $this->normalizeOfferUrl((string) ($officialSourceRecord['source_url'] ?? ''));
                $isApprovedOfficialSource = (string) ($officialSourceRecord['review_status'] ?? '') === 'approved'
                    && empty($this->officialSourceIdentityErrors($officialSourceRecord, $groupExpectations));
                $sourceRefs[] = [
                    'source_type' => $isApprovedOfficialSource
                        ? 'official_product_source'
                        : 'official_product_source_candidate',
                    'config' => 'app/config/fcc_product_catalog_official_sources.php',
                    'source_id' => (string) ($officialSourceRecord['source_id'] ?? ''),
                    'product_key' => $productKey,
                    'language_code' => $languageCode,
                    'market_code' => $sourceMarketCode,
                    'source_url' => (string) ($officialSourceRecord['source_url'] ?? ''),
                    'canonical_offer_url' => $canonicalOfferUrl,
                    'canonical_offer_binding' => $this->officialEvidenceCanonicalOfferBinding(
                        $normalizedEvidenceUrl,
                        $canonicalOfferUrl,
                        $sourceMarketCode,
                        (string) ($officialSourceRecord['page_item_code'] ?? '')
                    ),
                    'accessed_at' => (string) ($officialSourceRecord['accessed_at'] ?? ''),
                    'content_sha256' => (string) ($officialSourceRecord['content_sha256'] ?? ''),
                    'evidence_sha256' => (string) ($officialSourceRecord['evidence_sha256'] ?? ''),
                    'section_sha256' => is_array($officialSourceRecord['label_evidence']['section_sha256'] ?? null)
                        ? $officialSourceRecord['label_evidence']['section_sha256']
                        : [],
                    'review_status' => (string) ($officialSourceRecord['review_status'] ?? ''),
                    'identity_status' => (string) ($officialSourceRecord['identity_status'] ?? ''),
                    'page_name' => (string) ($officialSourceRecord['page_name'] ?? ''),
                    'page_item_code' => (string) ($officialSourceRecord['page_item_code'] ?? ''),
                    'page_category' => (string) ($officialSourceRecord['page_category'] ?? ''),
                    'page_route' => (string) ($officialSourceRecord['page_route'] ?? ''),
                    'route_override_review_note' => (string) ($officialSourceRecord['route_override_review_note'] ?? ''),
                    'verified_use_case_count' => $isApprovedOfficialSource ? count((array) ($officialSourceRecord['use_cases'] ?? [])) : 0,
                    'verified_fact_count' => $isApprovedOfficialSource
                        ? count((array) ($officialSourceRecord['ingredient_facts'] ?? []))
                            + count((array) ($officialSourceRecord['approved_support_facts'] ?? []))
                        : 0,
                    'verified_usage_count' => $isApprovedOfficialSource ? count((array) ($officialSourceRecord['usage_facts'] ?? [])) : 0,
                    'verified_safety_count' => $isApprovedOfficialSource ? count((array) ($officialSourceRecord['safety_notes'] ?? [])) : 0,
                    'allergens_contains_count' => $isApprovedOfficialSource ? count((array) ($officialSourceRecord['allergens_contains'] ?? [])) : 0,
                    'safety_flags_count' => $isApprovedOfficialSource ? count((array) ($officialSourceRecord['safety_flags'] ?? [])) : 0,
                    'mandatory_safety_count' => $isApprovedOfficialSource ? count((array) ($officialSourceRecord['mandatory_safety_notes'] ?? [])) : 0,
                    'safety_review_status' => $isApprovedOfficialSource
                        ? (string) ($officialSourceRecord['safety_review_status'] ?? 'unknown')
                        : 'unknown',
                    'suitability_review_status' => $isApprovedOfficialSource
                        ? (string) ($officialSourceRecord['suitability_review_status'] ?? 'unknown')
                        : 'unknown',
                    'claim_review_status' => $isApprovedOfficialSource
                        ? (string) ($officialSourceRecord['claim_review_status'] ?? 'unknown')
                        : 'unknown',
                    'quarantine_reasons' => array_values((array) ($officialSourceRecord['quarantine_reasons'] ?? [])),
                    'record_hash' => hash('sha256', $this->canonicalJson($officialSourceRecord)),
                ];
            }

            $entry = [
                'product_key' => $productKey,
                'language_code' => $languageCode,
                'canonical_name' => $canonicalName,
                'normalized_name' => $this->normalizeSearchText($canonicalName),
                'sku' => $sku,
                'category_key' => $categoryKey,
                'route' => $route,
                'advisor_status' => $advisorStatus,
                'availability_status' => $aggregateAvailability,
                'aliases' => $aliases,
                'use_cases' => $facts['use_cases'],
                'ingredient_facts' => $facts['ingredient_facts'],
                'approved_support_facts' => $facts['approved_support_facts'],
                'usage_facts' => $facts['usage_facts'],
                'safety_notes' => $facts['safety_notes'],
                'allergens_contains' => $allergensContains,
                'safety_flags' => $safetyFlags,
                'mandatory_safety_notes' => $mandatorySafetyNotes,
                'safety_review_status' => $safetyReviewStatus,
                'suitability_flags' => $suitabilityFlags,
                'suitability_review_status' => $suitabilityReviewStatus,
                'claim_review_status' => $claimReviewStatus,
                'offer_variants' => $catalogOfferVariants,
                'market_offers' => $marketOffers,
                'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
                'article_url' => $articleUrl,
                'source_refs' => $sourceRefs,
            ];
            $entry['source_hash'] = hash('sha256', $this->canonicalJson($entry));
            $entries[] = $entry;
        }

        return [
            'entries' => $entries,
            'warnings' => array_values(array_unique($warnings)),
            'skipped_rows' => $skippedRows,
        ];
    }

    private function buildMarketOffers(array $rows): array {
        $candidates = [];
        foreach($rows as $row) {
            foreach($this->decodeLegacyWebshopLinks((string) ($row['webshop_links'] ?? '')) as $marketCode => $url) {
                $marketCode = $this->normalizeMarketCode((string) $marketCode);
                $url = trim((string) $url);
                if($marketCode === '' || $url === '') {
                    continue;
                }

                $candidates[$marketCode][] = [
                    'url' => $url,
                    'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
                    'language_code' => $this->normalizeLanguageCode((string) ($row['language_code'] ?? $row['language'] ?? '')),
                ];
            }
        }

        $offers = [];
        $warnings = [];
        $blockedSources = [];

        foreach($candidates as $marketCode => $marketCandidates) {
            $valid = [];
            $invalid = [];

            foreach($marketCandidates as $candidate) {
                $cleanUrl = $this->stripReferralParameters((string) $candidate['url']);
                $validation = $this->validateOfferUrl($marketCode, $cleanUrl);
                $candidate['normalized_url'] = $this->normalizeOfferUrl($cleanUrl);
                $candidate['clean_url'] = $cleanUrl;
                $candidate['errors'] = $validation['errors'];

                if($validation['valid']) {
                    $valid[$candidate['normalized_url']][] = $candidate;
                } else {
                    $invalid[] = $candidate;
                }
            }

            if(count($valid) === 1) {
                $normalizedUrl = (string) array_key_first($valid);
                $selected = $valid[$normalizedUrl][0];
                $offers[$marketCode] = [
                    'availability_status' => 'available',
                    'official_url' => (string) $selected['clean_url'],
                    'source_blog_post_ids' => array_values(array_unique(array_map(
                        static fn(array $candidate): int => (int) $candidate['blog_post_id'],
                        $valid[$normalizedUrl]
                    ))),
                    'source_hash' => hash('sha256', $marketCode . '|' . $normalizedUrl),
                ];
            } elseif(count($valid) > 1) {
                $warnings[] = 'offer_conflict:' . $marketCode . ':' . implode('|', array_keys($valid));
                $offers[$marketCode] = [
                    'availability_status' => 'blocked',
                    'official_url' => '',
                    'validation_errors' => ['conflicting_market_urls'],
                    'source_hash' => hash('sha256', $marketCode . '|conflict|' . implode('|', array_keys($valid))),
                ];
                foreach($valid as $urlCandidates) {
                    $invalid = array_merge($invalid, $urlCandidates);
                }
            } else {
                $reasonCodes = [];
                foreach($invalid as $candidate) {
                    $reasonCodes = array_merge($reasonCodes, (array) ($candidate['errors'] ?? []));
                }
                $reasonCodes = array_values(array_unique($reasonCodes));
                $offers[$marketCode] = [
                    'availability_status' => 'blocked',
                    'official_url' => '',
                    'validation_errors' => $reasonCodes ?: ['legacy_offer_invalid'],
                    'source_hash' => hash('sha256', $marketCode . '|blocked|' . implode('|', $reasonCodes)),
                ];
            }

            foreach($invalid as $candidate) {
                $blockedSources[] = [
                    'source_type' => 'blocked_legacy_offer',
                    'market_code' => $marketCode,
                    'blog_post_id' => (int) ($candidate['blog_post_id'] ?? 0),
                    'language_code' => (string) ($candidate['language_code'] ?? ''),
                    'original_url' => (string) ($candidate['url'] ?? ''),
                    'reason_codes' => array_values(array_unique((array) ($candidate['errors'] ?? ['conflicting_market_urls']))),
                ];
            }
        }

        ksort($offers);
        return [
            'offers' => $offers,
            'warnings' => array_values(array_unique($warnings)),
            'blocked_sources' => $blockedSources,
        ];
    }

    /**
     * A legacy SKU is a matching alias only after an exact code signal from at
     * least one valid official product URL. It remains stored as product data
     * even when this identity check fails.
     */
    private function buildAliases(
        string $canonicalName,
        string $sku,
        string $legacyAliases,
        bool $includeSkuAlias,
        array $verifiedAliases = []
    ): array {
        $aliases = [$canonicalName];
        if($sku !== '' && $includeSkuAlias) {
            $aliases[] = $sku;
        }

        foreach(preg_split('/[\r\n,;]+/u', $legacyAliases) ?: [] as $alias) {
            $alias = trim(strip_tags((string) $alias));
            if(
                $alias !== ''
                && ($includeSkuAlias || $this->normalizeProductCode($alias) !== $this->normalizeProductCode($sku))
            ) {
                $aliases[] = $alias;
            }
        }
        foreach($verifiedAliases as $verifiedAlias) {
            if(is_scalar($verifiedAlias)) {
                $aliases[] = trim((string) $verifiedAlias);
            }
        }

        $safe = [];
        $seen = [];
        foreach($aliases as $alias) {
            $alias = trim((string) $alias);
            $normalized = $this->normalizeSearchText($alias);
            if($alias === '' || $normalized === '' || isset($seen[$normalized])) {
                continue;
            }
            if(in_array($normalized, self::GENERIC_ALIASES, true)) {
                continue;
            }
            if(mb_strlen($normalized) < 3 && !preg_match('/^[0-9a-z]+$/i', $normalized)) {
                continue;
            }

            $seen[$normalized] = true;
            $safe[] = $alias;
        }

        return $safe;
    }

    /** @return array{verified:bool,status:string,signals:array,market_signals:array,conflicting_signals:array} */
    private function verifySkuAgainstMarketOffers(string $sku, array $marketOffers): array {
        $normalizedSku = $this->normalizeProductCode($sku);
        $signals = [];
        $marketSignals = [];

        foreach($marketOffers as $marketCode => $offer) {
            if(!is_array($offer) || ($offer['availability_status'] ?? '') !== 'available') {
                continue;
            }

            $offerSignals = $this->extractOfficialProductCodeSignals((string) ($offer['official_url'] ?? ''));
            if($offerSignals) {
                $marketSignals[(string) $marketCode] = $offerSignals;
            }
            foreach($offerSignals as $signal) {
                $signals['code:' . $signal] = $signal;
            }
        }

        $signals = array_values($signals);
        sort($signals, SORT_STRING);
        $verified = $normalizedSku !== '' && in_array($normalizedSku, $signals, true);
        $conflictingSignals = $verified
            ? array_values(array_filter($signals, static fn(string $signal): bool => $signal !== $normalizedSku))
            : $signals;

        return [
            'verified' => $verified,
            'status' => $verified
                ? ($conflictingSignals ? 'confirmed_with_conflicting_signals' : 'confirmed')
                : ($signals ? 'mismatch' : 'no_official_product_code'),
            'signals' => $signals,
            'market_signals' => $marketSignals,
            'conflicting_signals' => $conflictingSignals,
        ];
    }

    /**
     * Matching codes are market-scoped. A globally valid legacy SKU (for
     * example 715 in AU) is removed from HR aliases when the exact HR product
     * URL says 815. The exact market code is added only when unambiguous.
     */
    private function applyMarketMatcherAliases(array $catalog): array {
        $codesByProduct = [];
        $ownersByCode = [];

        foreach($catalog as $entry) {
            $productKey = (string) ($entry['product_key'] ?? '');
            $officialUrl = (string) ($entry['official_url'] ?? '');
            $codes = $officialUrl !== '' ? $this->extractOfficialProductCodeSignals($officialUrl) : [];
            $codesByProduct[$productKey] = $codes;
            foreach($codes as $code) {
                $ownersByCode[$code][$productKey] = true;
            }
        }

        foreach($catalog as &$entry) {
            $productKey = (string) ($entry['product_key'] ?? '');
            $canonicalName = (string) ($entry['canonical_name'] ?? '');
            $canonicalMatcher = $this->normalizeSearchText($canonicalName);
            $skuCode = $this->normalizeProductCode((string) ($entry['sku'] ?? ''));
            $marketCodes = $codesByProduct[$productKey] ?? [];
            $aliases = [];

            foreach((array) ($entry['aliases'] ?? []) as $alias) {
                $alias = (string) $alias;
                $aliasCode = $this->normalizeProductCode($alias);
                if(
                    $skuCode !== ''
                    && $aliasCode === $skuCode
                    && !in_array($skuCode, $marketCodes, true)
                    && $this->normalizeSearchText($alias) !== $canonicalMatcher
                ) {
                    continue;
                }
                $aliases[] = $alias;
            }

            foreach($marketCodes as $marketCode) {
                if(count($ownersByCode[$marketCode] ?? []) === 1) {
                    $aliases[] = $marketCode;
                }
            }
            $entry['aliases'] = $this->cleanStringList($aliases);
        }
        unset($entry);

        return array_values($catalog);
    }

    private function extractOfficialProductCodeSignals(string $url): array {
        if($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return [];
        }

        $parts = parse_url($url);
        $pathSegments = array_values(array_filter(
            explode('/', rawurldecode((string) ($parts['path'] ?? ''))),
            static fn(string $segment): bool => $segment !== ''
        ));
        $signals = [];

        if($pathSegments) {
            $lastSegment = (string) end($pathSegments);
            if(preg_match('/^([a-z0-9]{1,32})(?:[-_]|$)/i', $lastSegment, $matches)) {
                $signal = $this->normalizeProductCode((string) ($matches[1] ?? ''));
                if($this->isProductCodeSignal($signal)) {
                    $signals['code:' . $signal] = $signal;
                }
            }
        }

        if(!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            $queryKeys = [
                'sku', 'productid', 'product_id', 'productcode', 'product_code',
                'itemno', 'item_no', 'itemnumber', 'item_number', 'code',
            ];
            foreach($query as $key => $value) {
                if(!in_array(mb_strtolower((string) $key), $queryKeys, true) || is_array($value)) {
                    continue;
                }
                $signal = $this->normalizeProductCode((string) $value);
                if($this->isProductCodeSignal($signal)) {
                    $signals['code:' . $signal] = $signal;
                }
            }
        }

        $signals = array_values($signals);
        sort($signals, SORT_STRING);
        return $signals;
    }

    private function normalizeProductCode(string $value): string {
        $value = mb_strtoupper(trim($value));
        return preg_replace('/[^A-Z0-9]+/', '', $value) ?? '';
    }

    private function isProductCodeSignal(string $value): bool {
        return $value !== ''
            && strlen($value) <= 32
            && preg_match('/^[A-Z0-9]+$/', $value) === 1
            && preg_match('/[0-9]/', $value) === 1;
    }

    private function extractStructuredFacts(string $shopContext): array {
        $decoded = json_decode($shopContext, true);
        $context = is_array($decoded) ? $decoded : [];
        $facts = [
            'use_cases' => $this->cleanStringList($context['ideal_for'] ?? []),
            'ingredient_facts' => [],
            'approved_support_facts' => [],
            'usage_facts' => [],
            'safety_notes' => [],
        ];

        foreach((array) ($context['summary_cards'] ?? []) as $card) {
            if(is_object($card)) {
                $card = (array) $card;
            }
            if(!is_array($card)) {
                continue;
            }
            $label = trim(strip_tags((string) ($card['label'] ?? '')));
            $value = trim(strip_tags((string) ($card['value'] ?? '')));
            if($label === '' || $value === '') {
                continue;
            }
            $fact = $label . ': ' . $value;
            $normalizedLabel = $this->normalizeSearchText($label);

            if($this->containsWholePhrase($normalizedLabel, ['ingredient', 'ingredients', 'sastojak', 'sastojci', 'composition', 'sastav'])) {
                $facts['ingredient_facts'][] = $fact;
            } elseif($this->containsWholePhrase($normalizedLabel, ['usage', 'use', 'uporaba', 'koristenje', 'doziranje', 'serving'])) {
                $facts['usage_facts'][] = $fact;
            } elseif($this->containsWholePhrase($normalizedLabel, ['warning', 'caution', 'oprez', 'upozorenje', 'allergen', 'alergen'])) {
                $facts['safety_notes'][] = $fact;
            } else {
                $facts['approved_support_facts'][] = $fact;
            }
        }

        foreach($this->cleanStringList($context['quick_checks'] ?? []) as $check) {
            $normalized = $this->normalizeSearchText($check);
            if($this->containsWholePhrase($normalized, ['warning', 'caution', 'oprez', 'upozorenje', 'allergen', 'alergen', 'doctor', 'lijecnik', 'trudnoca', 'pregnancy'])) {
                $facts['safety_notes'][] = $check;
            }
        }

        foreach((array) ($context['faq'] ?? []) as $faq) {
            if(is_object($faq)) {
                $faq = (array) $faq;
            }
            if(!is_array($faq)) {
                continue;
            }
            $question = trim(strip_tags((string) ($faq['question'] ?? '')));
            $answer = trim(strip_tags((string) ($faq['answer'] ?? '')));
            if($question === '' || $answer === '') {
                continue;
            }
            $fact = $question . ': ' . $answer;
            $normalized = $this->normalizeSearchText($question);
            if($this->containsWholePhrase($normalized, ['how', 'use', 'usage', 'kako', 'uporaba', 'doziranje', 'koliko'])) {
                $facts['usage_facts'][] = $fact;
            } elseif($this->containsWholePhrase($normalized, ['safe', 'safety', 'warning', 'oprez', 'sigurno', 'trudnoca', 'pregnancy', 'allergen', 'alergen'])) {
                $facts['safety_notes'][] = $fact;
            }
        }

        foreach($facts as $key => $values) {
            $facts[$key] = $this->cleanStringList($values);
        }

        return $facts;
    }

    private function mergeFactSets(array $legacyFacts, array $verifiedFacts): array {
        $merged = [];
        foreach(['use_cases', 'ingredient_facts', 'approved_support_facts', 'usage_facts', 'safety_notes'] as $field) {
            $values = array_merge(
                (array) ($legacyFacts[$field] ?? []),
                (array) ($verifiedFacts[$field] ?? [])
            );
            $merged[$field] = in_array($field, ['ingredient_facts', 'usage_facts', 'safety_notes'], true)
                ? $this->cleanOfficialEvidenceList($values)
                : $this->cleanStringList($values);
        }

        return $merged;
    }

    /**
     * Field-level provenance precedence. A current reviewed label replaces a
     * stale declaration/dose/warning field wholesale, while empty fields may be
     * completed by an older exact-product/exact-language reviewed source.
     */
    private function preferFactFields(array $baseFacts, array $preferredFacts): array {
        $result = [];
        foreach(['use_cases', 'ingredient_facts', 'approved_support_facts', 'usage_facts', 'safety_notes'] as $field) {
            $values = !empty($preferredFacts[$field])
                ? (array) $preferredFacts[$field]
                : (array) ($baseFacts[$field] ?? []);
            $result[$field] = in_array($field, ['ingredient_facts', 'usage_facts', 'safety_notes'], true)
                ? $this->cleanOfficialEvidenceList($values)
                : $this->cleanStringList($values);
        }
        return $result;
    }

    private function verifiedFactRecordIsEligible(array $verifiedFacts): bool {
        return !empty($verifiedFacts['use_cases'])
            && (!empty($verifiedFacts['ingredient_facts']) || !empty($verifiedFacts['approved_support_facts']))
            && !empty($verifiedFacts['usage_facts'])
            && !empty($verifiedFacts['safety_notes'])
            && !empty($verifiedFacts['source_refs']);
    }

    private function entryIsRecommendationComplete(
        string $route,
        array $marketOffers,
        array $facts,
        array $verifiedFacts,
        string $articleUrl,
        string $safetyReviewStatus = 'unknown',
        string $claimReviewStatus = 'unknown',
        string $requiredMarketCode = ''
    ): bool {
        if($route === 'unknown' || $articleUrl === '') {
            return false;
        }
        if($safetyReviewStatus !== 'complete') {
            return false;
        }
        if($claimReviewStatus !== 'approved') {
            return false;
        }

        $requiredMarketCode = $this->normalizeMarketCode($requiredMarketCode);
        $hasAvailableOffer = $requiredMarketCode !== ''
            ? (string) ($marketOffers[$requiredMarketCode]['availability_status'] ?? '') === 'available'
            : !empty(array_filter(
                $marketOffers,
                static fn(array $offer): bool => ($offer['availability_status'] ?? '') === 'available'
            ));

        return $hasAvailableOffer
            && $this->verifiedFactRecordIsEligible($verifiedFacts)
            && !empty($facts['use_cases'])
            && (!empty($facts['ingredient_facts']) || !empty($facts['approved_support_facts']))
            && !empty($facts['usage_facts'])
            && !empty($facts['safety_notes']);
    }

    private function resolveGroupRoute(array $rows): string {
        $routes = [];
        foreach($rows as $row) {
            $route = $this->inferRoute($row);
            if($route !== 'unknown') {
                $routes[$route] = true;
            }
        }

        if(count($routes) > 1) {
            return 'mixed';
        }
        if(count($routes) === 1) {
            return (string) array_key_first($routes);
        }

        return 'unknown';
    }

    private function inferRoute(array $row): string {
        $title = $this->normalizeSearchText((string) ($row['title'] ?? ''));
        $slug = $this->normalizeSearchText(str_replace('-', ' ', (string) ($row['url'] ?? '')));
        $category = $this->normalizeSearchText(str_replace(['-', '/'], ' ', (string) ($row['category_url'] ?? '')));
        $haystack = trim($title . ' ' . $slug . ' ' . $category);

        if($this->containsWholePhrase($haystack, ['c9', 'clean 9', 'dx4', 'f15', 'vital5', 'vital 5'])) {
            return 'program';
        }
        if($this->containsWholePhrase($haystack, ['toothgel', 'tooth gel', 'toothpaste', 'pasta za zube', 'forever bright'])) {
            return 'oral_care';
        }
        if($this->containsWholePhrase($category, ['njega koze', 'skin care', 'osobna njega', 'personal care', 'kozmetika'])) {
            return 'topical';
        }
        if($this->containsWholePhrase($haystack, [
            'gelly', 'creme', 'cream', 'krema', 'lotion', 'losion', 'shampoo',
            'sampon', 'conditioner', 'soap', 'sapun', 'deodorant', 'sunscreen',
            'scrub', 'serum', 'cleanser', 'mask', 'skin oil', 'first spray',
            'msm gel', 'cooling lotion',
        ])) {
            return 'topical';
        }
        if($this->containsWholePhrase($category, [
            'napitci', 'drinks', 'dodaci prehrani', 'supplements', 'regulacija tezine',
            'weight management', 'pcelinji proizvodi', 'bee products',
        ])) {
            return 'oral';
        }

        return 'unknown';
    }

    private function legacyBlogSourceRef(array $row, string $relationType): array {
        return [
            'source_type' => 'blog_post',
            'relation_type' => $relationType,
            'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
            'language_code' => $this->normalizeLanguageCode((string) ($row['language_code'] ?? $row['language'] ?? '')),
            'translation_key' => (string) ($row['url'] ?? ''),
            'source_hash' => hash('sha256', $this->canonicalJson([
                'blog_post_id' => (int) ($row['blog_post_id'] ?? 0),
                'title' => (string) ($row['title'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
                'keywords' => (string) ($row['keywords'] ?? ''),
                'search_aliases' => (string) ($row['search_aliases'] ?? ''),
                'shop_context' => (string) ($row['shop_context'] ?? ''),
                'webshop_links' => (string) ($row['webshop_links'] ?? ''),
                'sku' => (string) ($row['sku'] ?? ''),
                'last_datetime' => (string) ($row['last_datetime'] ?? ''),
            ])),
        ];
    }

    private function insertCatalogEntry(int $revisionId, array $entry, string $now): void {
        $this->execute(
            'INSERT INTO `' . self::ENTRY_TABLE . '` ('
            . '`revision_id`, `product_key`, `language_code`, `canonical_name`, `normalized_name`, `sku`, `category_key`, `route`, `advisor_status`, `availability_status`, '
            . '`aliases`, `use_cases`, `ingredient_facts`, `approved_support_facts`, `usage_facts`, `safety_notes`, '
            . '`allergens_contains`, `safety_flags`, `mandatory_safety_notes`, `safety_review_status`, '
            . '`suitability_flags`, `suitability_review_status`, `claim_review_status`, `offer_variants`, `market_offers`, '
            . '`blog_post_id`, `article_url`, `source_refs`, `source_hash`, `created_at`, `updated_at`'
            . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $revisionId,
                (string) ($entry['product_key'] ?? ''),
                (string) ($entry['language_code'] ?? ''),
                (string) ($entry['canonical_name'] ?? ''),
                (string) ($entry['normalized_name'] ?? ''),
                (string) ($entry['sku'] ?? ''),
                (string) ($entry['category_key'] ?? ''),
                (string) ($entry['route'] ?? 'unknown'),
                (string) ($entry['advisor_status'] ?? 'lookup_only'),
                (string) ($entry['availability_status'] ?? 'unknown'),
                $this->encodeJson(array_values((array) ($entry['aliases'] ?? []))),
                $this->encodeJson(array_values((array) ($entry['use_cases'] ?? []))),
                $this->encodeJson(array_values((array) ($entry['ingredient_facts'] ?? []))),
                $this->encodeJson(array_values((array) ($entry['approved_support_facts'] ?? []))),
                $this->encodeJson(array_values((array) ($entry['usage_facts'] ?? []))),
                $this->encodeJson(array_values((array) ($entry['safety_notes'] ?? []))),
                $this->encodeJson(array_values((array) ($entry['allergens_contains'] ?? []))),
                $this->encodeJson(array_values((array) ($entry['safety_flags'] ?? []))),
                $this->encodeJson(array_values((array) ($entry['mandatory_safety_notes'] ?? []))),
                (string) ($entry['safety_review_status'] ?? 'unknown'),
                $this->encodeJson(array_values((array) ($entry['suitability_flags'] ?? []))),
                (string) ($entry['suitability_review_status'] ?? 'unknown'),
                (string) ($entry['claim_review_status'] ?? 'unknown'),
                $this->encodeJson(array_values((array) ($entry['offer_variants'] ?? []))),
                $this->encodeJson((array) ($entry['market_offers'] ?? [])),
                (int) ($entry['blog_post_id'] ?? 0) ?: null,
                (string) ($entry['article_url'] ?? ''),
                $this->encodeJson(array_values((array) ($entry['source_refs'] ?? []))),
                (string) ($entry['source_hash'] ?? ''),
                $now,
                $now,
            ]
        );
    }

    private function insertCatalogRelationship(int $revisionId, array $relationship, string $now): void {
        $accessedAt = (new \DateTimeImmutable((string) ($relationship['accessed_at'] ?? 'now')))
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d H:i:s');
        $normalized = [
            'source_product_key' => (string) ($relationship['source_product_key'] ?? ''),
            'target_product_key' => (string) ($relationship['target_product_key'] ?? ''),
            'language_code' => (string) ($relationship['language_code'] ?? ''),
            'market_code' => (string) ($relationship['market_code'] ?? ''),
            'relationship_type' => (string) ($relationship['relationship_type'] ?? ''),
            'goal_keys' => array_values((array) ($relationship['goal_keys'] ?? [])),
            'evidence_summary' => (string) ($relationship['evidence_summary'] ?? ''),
            'reason_text' => (string) ($relationship['reason_text'] ?? ''),
            'source_refs' => array_values((array) ($relationship['source_refs'] ?? [])),
            'safety_gate' => (array) ($relationship['safety_gate'] ?? []),
            'sequence_order' => max(0, (int) ($relationship['sequence_order'] ?? 0)),
            'source_content_hash' => (string) ($relationship['source_content_hash'] ?? ''),
            'review_status' => 'approved',
            'accessed_at' => $accessedAt,
        ];
        $sourceHash = hash('sha256', $this->canonicalJson($normalized));

        $this->execute(
            'INSERT INTO `' . self::RELATIONSHIP_TABLE . '` ('
            . '`revision_id`, `source_product_key`, `target_product_key`, `language_code`, `market_code`, `relationship_type`, '
            . '`goal_keys`, `evidence_summary`, `reason_text`, `source_refs`, `safety_gate`, `sequence_order`, '
            . '`source_content_hash`, `source_hash`, `review_status`, `accessed_at`, `created_at`'
            . ') VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $revisionId,
                $normalized['source_product_key'],
                $normalized['target_product_key'],
                $normalized['language_code'],
                $normalized['market_code'],
                $normalized['relationship_type'],
                $this->encodeJson($normalized['goal_keys']),
                $normalized['evidence_summary'],
                $normalized['reason_text'],
                $this->encodeJson($normalized['source_refs']),
                $this->encodeJson($normalized['safety_gate']),
                $normalized['sequence_order'] > 0 ? $normalized['sequence_order'] : null,
                $normalized['source_content_hash'],
                $sourceHash,
                $normalized['review_status'],
                $normalized['accessed_at'],
                $now,
            ]
        );
    }

    /**
     * Immutable digest of the materialized revision. Auto-increment IDs are
     * deliberately excluded; stable identities plus their content hashes are
     * sufficient to detect any row insertion, deletion or mutation.
     */
    private function revisionBundleHash(array $entryRows, array $relationshipRows): string {
        $entries = array_map(static fn(array $row): array => [
            'product_key' => (string) ($row['product_key'] ?? ''),
            'language_code' => (string) ($row['language_code'] ?? ''),
            'source_hash' => mb_strtolower(trim((string) ($row['source_hash'] ?? ''))),
        ], $entryRows);
        usort($entries, static fn(array $left, array $right): int => strcmp(
            $left['product_key'] . '|' . $left['language_code'],
            $right['product_key'] . '|' . $right['language_code']
        ));

        $relationships = array_map(static fn(array $row): array => [
            'source_product_key' => (string) ($row['source_product_key'] ?? ''),
            'target_product_key' => (string) ($row['target_product_key'] ?? ''),
            'language_code' => (string) ($row['language_code'] ?? ''),
            'market_code' => (string) ($row['market_code'] ?? ''),
            'relationship_type' => (string) ($row['relationship_type'] ?? ''),
            'source_hash' => mb_strtolower(trim((string) ($row['source_hash'] ?? ''))),
        ], $relationshipRows);
        usort($relationships, static fn(array $left, array $right): int => strcmp(
            implode('|', array_values($left)),
            implode('|', array_values($right))
        ));

        return hash('sha256', $this->canonicalJson([
            'entries' => $entries,
            'relationships' => $relationships,
        ]));
    }

    private function calculateRevisionBundleHash(int $revisionId): string {
        $entryRows = $this->fetchAll(
            'SELECT `product_key`, `language_code`, `source_hash` FROM `' . self::ENTRY_TABLE . '` WHERE `revision_id` = ? ORDER BY `product_key`, `language_code`',
            [$revisionId]
        );
        $relationshipRows = $this->fetchAll(
            'SELECT `source_product_key`, `target_product_key`, `language_code`, `market_code`, `relationship_type`, `source_hash` FROM `' . self::RELATIONSHIP_TABLE . '` WHERE `revision_id` = ? ORDER BY `source_product_key`, `target_product_key`, `language_code`, `market_code`, `relationship_type`',
            [$revisionId]
        );

        return $this->revisionBundleHash($entryRows, $relationshipRows);
    }

    /**
     * Return the explicit, auditable relation between an evidence URL and the
     * locked inventory offer. Non-exact matches are accepted only for the same
     * Forever market, exact final item/slug and exact reviewed item code.
     */
    private function officialEvidenceCanonicalOfferBinding(
        string $sourceUrl,
        string $canonicalOfferUrl,
        string $marketCode,
        string $reviewedItemCode
    ): string {
        $sourceUrl = $this->normalizeOfferUrl($sourceUrl);
        $canonicalOfferUrl = $this->normalizeOfferUrl($canonicalOfferUrl);
        if($sourceUrl === '' || $canonicalOfferUrl === '') {
            return '';
        }
        if(hash_equals($sourceUrl, $canonicalOfferUrl)) {
            return 'exact_url';
        }
        if(
            !$this->validateOfferUrl($marketCode, $sourceUrl)['valid']
            || !$this->validateOfferUrl($marketCode, $canonicalOfferUrl)['valid']
        ) {
            return '';
        }

        $sourcePath = mb_strtolower(rawurldecode((string) (parse_url($sourceUrl, PHP_URL_PATH) ?? '')));
        $canonicalPath = mb_strtolower(rawurldecode((string) (parse_url($canonicalOfferUrl, PHP_URL_PATH) ?? '')));
        $productMarker = '/products/';
        $sourcePosition = strpos($sourcePath, $productMarker);
        $canonicalPosition = strpos($canonicalPath, $productMarker);
        if($sourcePosition === false || $canonicalPosition === false) {
            return '';
        }
        $sourceTail = trim(substr($sourcePath, $sourcePosition + strlen($productMarker)), '/');
        $canonicalTail = trim(substr($canonicalPath, $canonicalPosition + strlen($productMarker)), '/');
        $sourceSegments = array_values(array_filter(explode('/', $sourceTail), 'strlen'));
        $canonicalSegments = array_values(array_filter(explode('/', $canonicalTail), 'strlen'));
        $reviewedItemCode = $this->normalizeProductCode($reviewedItemCode);
        $identityMatches = $reviewedItemCode !== ''
            && in_array($reviewedItemCode, $this->extractOfficialProductCodeSignals($sourceUrl), true)
            && in_array($reviewedItemCode, $this->extractOfficialProductCodeSignals($canonicalOfferUrl), true);
        if(!$identityMatches) {
            return '';
        }

        if(
            count($sourceSegments) === 1
            && count($canonicalSegments) === 2
            && hash_equals((string) $sourceSegments[0], (string) $canonicalSegments[1])
        ) {
            return 'same_item_one_category_segment_v1';
        }

        /*
         * Forever can list the same exact SKU under two official categories
         * (for example weight-management and nutritional). This is narrower
         * than an alias: both URLs must contain the same reviewed SKU and the
         * identical final item/slug; only the category segment may differ.
         */
        if(
            count($sourceSegments) === 2
            && count($canonicalSegments) === 2
            && hash_equals((string) $sourceSegments[1], (string) $canonicalSegments[1])
            && !hash_equals((string) $sourceSegments[0], (string) $canonicalSegments[0])
        ) {
            return 'same_item_reviewed_category_alias_v1';
        }

        return '';
    }

    /**
     * Strict static offer validation. Network reachability belongs to a separate
     * verifier; this gate catches wrong products, copied market paths and unsafe
     * legacy referral URLs before they can be published as available.
     */
    private function validateOfferUrl(string $marketCode, string $url): array {
        $marketCode = $this->normalizeMarketCode($marketCode);
        $url = trim($url);
        $errors = [];

        if($marketCode === '') {
            $errors[] = 'invalid_market_code';
        }
        if($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'invalid_url';
            return ['valid' => false, 'errors' => $errors];
        }

        $parts = parse_url($url);
        $scheme = mb_strtolower((string) ($parts['scheme'] ?? ''));
        $host = mb_strtolower((string) ($parts['host'] ?? ''));
        $host = preg_replace('/^www\./', '', $host) ?: $host;
        $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
        $normalizedUrl = mb_strtolower(rawurldecode($url));

        if($scheme !== 'https') {
            $errors[] = 'offer_must_use_https';
        }
        foreach(self::FORBIDDEN_OFFER_TOKENS as $token) {
            if(str_contains($normalizedUrl, $token)) {
                $errors[] = 'known_non_product_destination:' . $token;
            }
        }

        if($marketCode === 'ba') {
            if($host !== 'flpshop.ba') {
                $errors[] = 'market_host_mismatch';
            }
        } elseif($marketCode === 'al') {
            if($host !== 'foreveralbania.com') {
                $errors[] = 'market_host_mismatch';
            }
        } elseif(isset(self::MARKET_PATH_PREFIXES[$marketCode])) {
            if($host !== 'foreverliving.com') {
                $errors[] = 'market_host_mismatch';
            }
            if(!str_starts_with(mb_strtolower($path), self::MARKET_PATH_PREFIXES[$marketCode])) {
                $errors[] = 'market_path_mismatch';
            }
        } else {
            $errors[] = 'unsupported_market';
        }

        if(!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            $queryKeys = array_map('mb_strtolower', array_keys($query));
            foreach(['fboid', 'id', 'uniqueextrefid', 'referraluuid', 'discountconfigtype'] as $referralKey) {
                if(in_array($referralKey, $queryKeys, true)) {
                    $errors[] = 'stored_url_contains_referral_parameter:' . $referralKey;
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => array_values(array_unique($errors)),
        ];
    }

    private function decodeLegacyWebshopLinks(string $json): array {
        $decoded = json_decode($json, true);
        if(!is_array($decoded)) {
            return [];
        }

        $links = [];
        foreach($decoded as $marketCode => $url) {
            $marketCode = $this->normalizeMarketCode((string) $marketCode);
            $url = trim((string) $url);
            if($marketCode !== '' && $url !== '') {
                $links[$marketCode] = $url;
            }
        }

        return $links;
    }

    private function stripReferralParameters(string $url): string {
        $url = trim($url);
        if($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        $parts = parse_url($url);
        $query = [];
        if(!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
        }

        foreach(array_keys($query) as $key) {
            if(in_array(mb_strtolower((string) $key), ['fboid', 'id', 'uniqueextrefid', 'referraluuid', 'discountconfigtype'], true)) {
                unset($query[$key]);
            }
        }

        $result = (string) ($parts['scheme'] ?? 'https') . '://' . (string) ($parts['host'] ?? '');
        if(isset($parts['port'])) {
            $result .= ':' . (int) $parts['port'];
        }
        $result .= (string) ($parts['path'] ?? '');
        if($query) {
            $result .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        return $result;
    }

    private function normalizeOfferUrl(string $url): string {
        $url = $this->stripReferralParameters($url);
        if($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        $scheme = mb_strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = mb_strtolower((string) ($parts['host'] ?? ''));
        $host = preg_replace('/^www\./', '', $host) ?: $host;
        $path = rtrim((string) ($parts['path'] ?? ''), '/');
        $query = [];
        if(!empty($parts['query'])) {
            parse_str((string) $parts['query'], $query);
            ksort($query);
        }

        return $scheme . '://' . $host . $path . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
    }

    private function buildArticleUrl(string $translationKey, string $languageCode): string {
        $translationKey = trim($translationKey, " \t\n\r\0\x0B/");
        if($translationKey === '' || !defined('SITE_URL')) {
            return '';
        }

        $siteUrl = rtrim((string) SITE_URL, '/') . '/';
        $prefix = $languageCode !== '' ? trim($languageCode, '/') . '/' : '';
        return $siteUrl . $prefix . 'blog/' . $translationKey;
    }

    private function canonicalProductKey(string $slug, string $title): string {
        $key = $this->slugify($slug);
        $normalizedTitle = $this->normalizeSearchText($title);

        /* Explicit identity guard: topical Gelly may never collapse into drink Gel. */
        if(str_contains($normalizedTitle, 'aloe vera gelly') && !str_contains($key, 'gelly')) {
            $key = rtrim((string) preg_replace('/-gel$/', '', $key), '-') . '-gelly';
        }

        return mb_substr($key, 0, 128);
    }

    private function normalizeSearchText(string $value): string {
        $value = trim($value);
        if($value === '') {
            return '';
        }

        if(class_exists('Normalizer')) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_KC);
            if(is_string($normalized)) {
                $value = $normalized;
            }
        }

        $value = mb_strtolower(str_replace(['™', '®', '©'], '', $value));
        $value = strtr($value, [
            'č' => 'c', 'ć' => 'c', 'đ' => 'd', 'š' => 's', 'ž' => 'z',
            'ä' => 'a', 'ö' => 'o', 'ü' => 'u', 'ß' => 'ss',
        ]);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function slugify(string $value): string {
        $value = str_replace(['/', '_'], '-', $value);
        $value = $this->normalizeSearchText($value);
        if(function_exists('iconv')) {
            $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if(is_string($transliterated) && $transliterated !== '') {
                $value = mb_strtolower($transliterated);
            }
        }
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
        $value = preg_replace('/-+/', '-', $value) ?? $value;
        return trim($value, '-');
    }

    private function normalizeLanguageCode(string $language): string {
        $language = trim($language);
        if($language === '') {
            return '';
        }

        $normalized = mb_strtolower(str_replace('_', '-', $language));
        $map = [
            'hrvatski' => 'hr',
            'croatian' => 'hr',
            'english' => 'en',
            'engleski' => 'en',
            'slovenian' => 'sl',
            'slovenski' => 'sl',
            'bulgarian' => 'bg',
            'bugarski' => 'bg',
            'svenska'=>'sv','swedish'=>'sv','magyar'=>'hu','hungarian'=>'hu','polski'=>'pl','polish'=>'pl','română'=>'ro','romana'=>'ro','romanian'=>'ro',
        ];
        if(isset($map[$normalized])) {
            return $map[$normalized];
        }

        if(preg_match('/^[a-z]{2,3}(?:-[a-z0-9]{2,8})?$/', $normalized)) {
            return (string) explode('-', $normalized, 2)[0];
        }

        return '';
    }

    private function normalizeMarketCode(string $marketCode): string {
        $marketCode = mb_strtolower(trim($marketCode));
        if($marketCode === 'uk') {
            return 'gb';
        }
        if($marketCode === 'xk' || $marketCode === 'me') {
            return 'al';
        }

        return preg_match('/^[a-z]{2}$/', $marketCode) ? $marketCode : '';
    }

    private function normalizeSku(string $sku): string {
        $sku = mb_strtoupper(trim($sku));
        $sku = preg_replace('/[^A-Z0-9._-]+/', '', $sku) ?? '';
        return mb_substr($sku, 0, 64);
    }

    private function meaningfulProductTokens(string $value): array {
        $stop = [
            'forever', 'living', 'products', 'product', 'proizvod', 'proizvodi',
            'the', 'and', 'with', 'za', 'od', 's', 'sa', 'detox', 'program',
            'iskustvo', 'experience', 'guide', 'vodic',
        ];
        $tokens = preg_split('/\s+/u', $this->normalizeSearchText($value)) ?: [];
        $tokens = array_values(array_filter($tokens, static function(string $token) use ($stop): bool {
            return !in_array($token, $stop, true) && (mb_strlen($token) >= 3 || preg_match('/^[a-z][0-9]+$/', $token));
        }));

        return array_values(array_unique($tokens));
    }

    private function containsWholePhrase(string $normalizedHaystack, array $phrases): bool {
        $normalizedHaystack = ' ' . trim($normalizedHaystack) . ' ';
        foreach($phrases as $phrase) {
            $normalizedPhrase = $this->normalizeSearchText((string) $phrase);
            if($normalizedPhrase !== '' && str_contains($normalizedHaystack, ' ' . $normalizedPhrase . ' ')) {
                return true;
            }
        }

        return false;
    }

    private function cleanStringList($values): array {
        if(is_string($values)) {
            $values = preg_split('/[\r\n]+/u', $values) ?: [];
        }
        if(!is_array($values)) {
            return [];
        }

        $clean = [];
        $seen = [];
        foreach($values as $value) {
            if(is_array($value) || is_object($value)) {
                continue;
            }
            $value = trim(strip_tags((string) $value));
            $key = $this->normalizeSearchText($value);
            if($value === '' || $key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = mb_substr($value, 0, 1000);
        }

        return $clean;
    }

    /**
     * Official label sections can legitimately exceed the 1,000-character UI
     * fact limit (multi-product programs and long ingredient declarations).
     * Preserve those sections for byte-stable safety validation.
     */
    private function cleanOfficialEvidenceList($values): array {
        if(is_string($values)) {
            $values = preg_split('/[\r\n]+/u', $values) ?: [];
        }
        if(!is_array($values)) {
            return [];
        }

        $clean = [];
        $seen = [];
        foreach($values as $value) {
            if(is_array($value) || is_object($value)) {
                continue;
            }
            $value = trim(strip_tags((string) $value));
            $key = $this->normalizeOfficialEvidenceText($value);
            if($value === '' || $key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $clean[] = mb_substr($value, 0, 50000);
        }

        return $clean;
    }

    private function legacyRowIsNewer(array $candidate, array $current): bool {
        $candidateDate = trim((string) ($candidate['last_datetime'] ?? ''));
        if($candidateDate === '') {
            $candidateDate = trim((string) ($candidate['datetime'] ?? ''));
        }

        $currentDate = trim((string) ($current['last_datetime'] ?? ''));
        if($currentDate === '') {
            $currentDate = trim((string) ($current['datetime'] ?? ''));
        }

        if($candidateDate !== $currentDate) {
            return $candidateDate > $currentDate;
        }

        return (int) ($candidate['blog_post_id'] ?? 0) > (int) ($current['blog_post_id'] ?? 0);
    }

    private function isValidRevisionKey(string $revisionKey): bool {
        return preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $revisionKey) === 1;
    }

    private function isValidProductKey(string $productKey): bool {
        return preg_match('/^[a-z0-9][a-z0-9-]{0,127}$/', $productKey) === 1;
    }

    /**
     * Decode an ordered JSON list. Associative objects are rejected so a bad
     * import cannot silently change the public catalog contract.
     */
    private function decodeJsonList($value, bool &$valid): array {
        $valid = true;

        if($value === null || $value === '') {
            return [];
        }

        if(is_string($value)) {
            try {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch(\JsonException $exception) {
                $valid = false;
                return [];
            }
        }

        if(!is_array($value) || !$this->arrayIsList($value)) {
            $valid = false;
            return [];
        }

        return array_values($value);
    }

    /**
     * Decode a JSON object. Empty JSON arrays are accepted as empty objects
     * because MariaDB JSON values do not retain an empty object PHP type.
     */
    private function decodeJsonObject($value, bool &$valid): array {
        $valid = true;

        if($value === null || $value === '') {
            return [];
        }

        if(is_string($value)) {
            try {
                $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            } catch(\JsonException $exception) {
                $valid = false;
                return [];
            }
        }

        if(!is_array($value)) {
            $valid = false;
            return [];
        }

        if($value !== [] && $this->arrayIsList($value)) {
            $valid = false;
            return [];
        }

        return $value;
    }

    private function encodeJson($value): string {
        try {
            return json_encode(
                $value,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
            );
        } catch(\JsonException $exception) {
            throw new \RuntimeException('Catalog JSON encoding failed: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Stable JSON representation used for revision and per-entry hashes.
     */
    private function canonicalJson($value): string {
        return $this->encodeJson($this->canonicalizeValue($value));
    }

    private function canonicalizeValue($value) {
        if(!is_array($value)) {
            return $value;
        }

        if($this->arrayIsList($value)) {
            return array_map(fn($item) => $this->canonicalizeValue($item), array_values($value));
        }

        ksort($value, SORT_STRING);
        foreach($value as $key => $item) {
            $value[$key] = $this->canonicalizeValue($item);
        }

        return $value;
    }

    private function arrayIsList(array $value): bool {
        $index = 0;
        foreach($value as $key => $_) {
            if($key !== $index) {
                return false;
            }
            $index++;
        }

        return true;
    }

    private function tableExists(string $table): bool {
        $row = $this->fetchOne(
            'SELECT 1 AS `present` FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
            [$table]
        );

        return isset($row['present']);
    }

    private function columnExists(string $table, string $column): bool {
        $row = $this->fetchOne(
            'SELECT 1 AS `present` FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
            [$table, $column]
        );

        return isset($row['present']);
    }

    private function fetchAll(string $sql, array $parameters = []): array {
        $statement = $this->prepareAndExecute($sql, $parameters);
        $result = $statement->get_result();

        if($result === false) {
            $message = $statement->error !== '' ? $statement->error : 'result set unavailable';
            $statement->close();
            throw new \RuntimeException('Catalog query failed: ' . $message);
        }

        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        $statement->close();

        return is_array($rows) ? $rows : [];
    }

    private function fetchOne(string $sql, array $parameters = []): ?array {
        $rows = $this->fetchAll($sql, $parameters);
        return $rows[0] ?? null;
    }

    private function execute(string $sql, array $parameters = []): bool {
        $statement = $this->prepareAndExecute($sql, $parameters);
        $statement->close();
        return true;
    }

    private function prepareAndExecute(string $sql, array $parameters): \mysqli_stmt {
        $connection = \database();
        $statement = $connection->prepare($sql);

        if(!$statement) {
            throw new \RuntimeException('Catalog query preparation failed: ' . $connection->error);
        }

        if($parameters !== []) {
            $types = '';
            $bound = [];
            foreach(array_values($parameters) as $index => $parameter) {
                if(is_int($parameter) || is_bool($parameter)) {
                    $types .= 'i';
                    $bound[$index] = (int) $parameter;
                } elseif(is_float($parameter)) {
                    $types .= 'd';
                    $bound[$index] = $parameter;
                } else {
                    $types .= 's';
                    $bound[$index] = $parameter === null ? null : (string) $parameter;
                }
            }

            $references = [$types];
            foreach($bound as $index => &$parameter) {
                $references[] = &$parameter;
            }
            unset($parameter);

            if(!call_user_func_array([$statement, 'bind_param'], $references)) {
                $message = $statement->error;
                $statement->close();
                throw new \RuntimeException('Catalog query parameter binding failed: ' . $message);
            }
        }

        if(!$statement->execute()) {
            $message = $statement->error;
            $statement->close();
            throw new \RuntimeException('Catalog query execution failed: ' . $message);
        }

        return $statement;
    }

    private function now(): string {
        return date('Y-m-d H:i:s');
    }
}
