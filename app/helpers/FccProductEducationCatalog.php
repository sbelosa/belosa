<?php

namespace Altum\Helpers;

defined('ALTUMCODE') || die();

/** Read the current V2 publication and expose only reviewed product evidence. */
final class FccProductEducationCatalog {
    private object $repository;
    private \DateTimeImmutable $now;
    private string $catalogStatus = 'unavailable';
    private string $catalogNotice = '';

    public function __construct(?object $repository = null, ?\DateTimeImmutable $now = null) {
        $this->repository = $repository ?? new FccProductCatalogRepository();
        $this->now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    /** Only getCatalog selects the validated, currently published runtime. */
    public function publishedCatalog(string $languageCode = 'hr', string $marketCode = 'hr'): array {
        $this->catalogStatus = 'unavailable';
        $this->catalogNotice = 'Aktualni katalog trenutačno nije dostupan. Pitanja i lekcije ostaju dostupni za učenje, a sastav proizvoda treba provjeriti u aktualnoj službenoj deklaraciji.';

        try {
            if(!$this->repository->schemaIsReady()) {
                return [];
            }

            $entries = $this->repository->getCatalog($languageCode, $marketCode);
            if(!is_array($entries) || !$entries) {
                return [];
            }

            $this->catalogStatus = 'ready';
            $this->catalogNotice = '';
            return $entries;
        } catch(\Throwable $exception) {
            error_log('FCC product education catalog unavailable: ' . get_class($exception));
            return [];
        }
    }

    /** Keep every study identity even when a current declaration is unavailable. */
    public function studyCards(array $studyProducts, string $languageCode = 'hr', string $marketCode = 'hr'): array {
        $published = $this->publishedCatalog($languageCode, $marketCode);
        $byKey = [];
        $duplicateKeys = [];
        foreach($published as $entry) {
            if(!is_array($entry) || !is_string($entry['product_key'] ?? null)) {
                continue;
            }
            $key = $entry['product_key'];
            if(isset($byKey[$key])) {
                $duplicateKeys[$key] = true;
            }
            $byKey[$key] = $entry;
        }

        $cards = [];
        $verifiedCount = 0;
        foreach($studyProducts as $studyProduct) {
            if(!is_array($studyProduct) || !is_string($studyProduct['product_key'] ?? null)) {
                continue;
            }
            $key = $studyProduct['product_key'];
            $entry = isset($duplicateKeys[$key]) ? [] : ($byKey[$key] ?? []);
            $card = $this->studyCard($studyProduct, $entry, $languageCode, $marketCode);
            $verifiedCount += $card['catalog_verified'] ? 1 : 0;
            $cards[] = $card;
        }

        if($this->catalogStatus === 'ready' && $verifiedCount < count($cards)) {
            $this->catalogStatus = $verifiedCount > 0 ? 'partial' : 'unverified';
            $this->catalogNotice = 'Službeni podaci prikazuju se samo uz potvrđen identitet i aktualne pregledane izvore za hrvatski katalog. Ostale kartice služe učenju, uz provjeru aktualne deklaracije.';
        }

        return $cards;
    }

    /** Deterministic card projection, also used by the review tests. */
    public function studyCard(array $study, array $entry, string $languageCode = 'hr', string $marketCode = 'hr'): array {
        $card = $study;
        $card['catalog_verified'] = false;
        $card['catalog_status'] = 'unverified';
        $card['official_url'] = '';
        $card['source_refs'] = [];
        $card['ingredient_facts'] = [];
        $card['approved_support_facts'] = [];
        $card['usage_facts'] = [];
        $card['safety_notes'] = [];
        $card['share_information_url'] = '';
        $card['shop_share_url'] = '';
        $card['catalog_revision_id'] = null;
        $card['source_freshness_status'] = 'unknown';
        $card['claim_review_status'] = 'unknown';
        $card['safety_review_status'] = 'unknown';

        if(!$entry || ($entry['product_key'] ?? null) !== ($study['product_key'] ?? null)) {
            return $card;
        }

        if(($entry['advisor_status'] ?? '') === 'blocked'
            || ($entry['availability_status'] ?? '') === 'blocked'
            || in_array(($entry['identity_status'] ?? ''), ['identity_conflict', 'blocked', 'unverified', 'quarantined'], true)
            || in_array(($entry['review_status'] ?? ''), ['quarantined', 'rejected'], true)) {
            $card['catalog_status'] = 'blocked';
            return $card;
        }

        if(!in_array(($entry['advisor_status'] ?? ''), ['recommendable', 'lookup_only'], true)) {
            return $card;
        }

        if(($entry['language_code'] ?? null) !== $languageCode || ($entry['market_code'] ?? null) !== $marketCode) {
            $card['catalog_status'] = 'scope_mismatch';
            return $card;
        }

        if(($entry['source_freshness_status'] ?? '') !== 'fresh') {
            $card['catalog_status'] = ($entry['source_freshness_status'] ?? '') === 'stale' ? 'stale' : 'unverified';
            return $card;
        }

        $refs = [];
        foreach(($entry['source_refs'] ?? []) as $ref) {
            if(!is_array($ref) || ($ref['source_type'] ?? '') !== 'official_product_source'
                || ($ref['review_status'] ?? '') !== 'approved'
                || !in_array(($ref['identity_status'] ?? ''), ['verified_exact', 'verified_reviewed'], true)
                || ($ref['product_key'] ?? '') !== ($study['product_key'] ?? '')
                || ($ref['language_code'] ?? '') !== $languageCode
                || ($ref['market_code'] ?? '') !== $marketCode
                || !$this->freshDate($ref['accessed_at'] ?? null)) {
                continue;
            }

            $sourceUrl = $this->officialUrl($ref['source_url'] ?? null, $marketCode);
            if($sourceUrl === '') {
                continue;
            }

            $refs[] = [
                'source_type' => 'official_product_source',
                'source_id' => is_string($ref['source_id'] ?? null) ? $ref['source_id'] : '',
                'product_key' => $study['product_key'],
                'language_code' => $languageCode,
                'market_code' => $marketCode,
                'source_url' => $sourceUrl,
                'url' => $sourceUrl,
                'title' => 'Pregledani službeni izvor za ' . (string) ($study['name'] ?? $study['product_key']),
                'accessed_at' => $ref['accessed_at'],
                'review_status' => 'approved',
                'identity_status' => $ref['identity_status'],
                'verified_fact_count' => max(0, (int) ($ref['verified_fact_count'] ?? 0)),
                'verified_usage_count' => max(0, (int) ($ref['verified_usage_count'] ?? 0)),
                'verified_safety_count' => max(0, (int) ($ref['verified_safety_count'] ?? 0)),
                'mandatory_safety_count' => max(0, (int) ($ref['mandatory_safety_count'] ?? 0)),
                'claim_review_status' => $ref['claim_review_status'] ?? 'unknown',
            ];
        }

        if(!$refs) {
            return $card;
        }

        $factCount = array_sum(array_column($refs, 'verified_fact_count'));
        $usageCount = array_sum(array_column($refs, 'verified_usage_count'));
        $safetyCount = array_sum(array_column($refs, 'verified_safety_count'));
        $mandatorySafetyCount = array_sum(array_column($refs, 'mandatory_safety_count'));
        if($factCount + $usageCount + $safetyCount + $mandatorySafetyCount === 0) {
            return $card;
        }

        $card['catalog_verified'] = true;
        $card['catalog_status'] = 'verified';
        $card['source_refs'] = $refs;
        $card['source_freshness_status'] = 'fresh';
        $card['catalog_revision_id'] = (int) ($entry['revision_id'] ?? 0);
        if(is_string($entry['canonical_name'] ?? null) && trim($entry['canonical_name']) !== '') {
            $card['name'] = trim($entry['canonical_name']);
        }
        if($factCount > 0) {
            $card['ingredient_facts'] = $this->textList($entry['ingredient_facts'] ?? []);
        }
        if($usageCount > 0) {
            $card['usage_facts'] = $this->textList($entry['usage_facts'] ?? []);
        }
        if($safetyCount > 0 || $mandatorySafetyCount > 0) {
            $card['safety_notes'] = array_values(array_unique(array_merge(
                $this->textList($entry['safety_notes'] ?? []),
                $this->textList($entry['mandatory_safety_notes'] ?? [])
            )));
        }
        $card['safety_review_status'] = is_string($entry['safety_review_status'] ?? null) ? $entry['safety_review_status'] : 'unknown';
        if(($entry['claim_review_status'] ?? '') === 'approved'
            && in_array('approved', array_column($refs, 'claim_review_status'), true)) {
            $card['claim_review_status'] = 'approved';
            $card['approved_support_facts'] = $this->textList($entry['approved_support_facts'] ?? []);
        }

        $card['official_url'] = $this->officialUrl($entry['official_url'] ?? null, $marketCode);
        if($card['official_url'] === '') {
            $card['official_url'] = $refs[0]['source_url'];
        }

        return $card;
    }

    public function notice(): string { return $this->catalogNotice; }
    public function status(): string { return $this->catalogStatus; }

    private function textList($value): array {
        if(!is_array($value)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map(static function($item): string {
            return is_string($item) ? preg_replace('/\s*[–—]\s*|\s+-\s+/u', ', ', trim($item)) : '';
        }, $value), static fn(string $item): bool => $item !== '')));
    }

    private function freshDate($value): bool {
        if(!is_string($value) || $value === '') {
            return false;
        }
        try {
            $date = new \DateTimeImmutable($value);
            return $date >= $this->now->modify('-90 days') && $date <= $this->now->modify('+1 day');
        } catch(\Throwable $exception) {
            return false;
        }
    }

    private function officialUrl($value, string $marketCode): string {
        if(!is_string($value) || $marketCode !== 'hr' || !filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }
        $parts = parse_url($value);
        if(!is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return '';
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = strtolower((string) ($parts['path'] ?? ''));
        if($host === 'foreverliving.com' && str_starts_with($path, '/shop/hrv/') && str_contains($path, '/products/')) {
            return $value;
        }
        if($host === 'cdn.foreverliving.com' && str_starts_with($path, '/content/marketingconfigurablecontent/') && str_contains($path, '/hrv/')) {
            return $value;
        }
        return '';
    }
}
