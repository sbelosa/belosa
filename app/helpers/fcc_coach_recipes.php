<?php
defined('ALTUMCODE') || die();

/** Preserve word boundaries in names such as Garlic-Thyme and ignore trademarks. */
function fcc_coach_product_identity(string $name): string {
    $name=str_replace(['™','®','©'],'', $name);
    return trim(preg_replace('/\s+/u',' ',preg_replace('/[^\p{L}\p{N} ]/u',' ',fcc_coach_normalize($name))));
}

/** Only usage tied to an exact, reviewed official source in the pinned catalog. */
function fcc_coach_official_usage(array $row): array {
    $empty=['instructions'=>[], 'source'=>null];
    if(($row['advisor_status']??'')!=='recommendable' || ($row['claim_review_status']??'')!=='approved'
        || ($row['safety_review_status']??'')!=='complete') return $empty;
    foreach($row['source_refs']??[] as $source) {
        if(($source['source_type']??'')!=='official_product_source' || ($source['review_status']??'')!=='approved'
            || ($source['identity_status']??'')!=='verified_exact' || !empty($source['quarantine_reasons'])
            || ($source['market_code']??'')!=='hr' || ($source['language_code']??'')!=='hr'
            || empty($source['section_sha256']['usage']) || empty($source['verified_usage_count'])) continue;
        $instructions=array_values(array_filter(array_map(static fn($text)=>trim(preg_replace('/^UPUTA ZA UPORABU\s*/u','',html_entity_decode(strip_tags((string)$text),ENT_QUOTES,'UTF-8'))), $row['usage_facts']??[])));
        return ['instructions'=>$instructions, 'source'=>array_intersect_key($source,array_flip(['source_url','accessed_at','market_code','language_code','review_status','identity_status']))];
    }
    return $empty;
}

/** This is an audit of the imported source, not a medical approval or a live production check. */
function fcc_coach_recipe_inventory(): array {
    static $inventory;
    if($inventory!==null) return $inventory;
    $matrix=fcc_ai_get_product_advisor_recommendation_matrix();
    $catalog=fcc_coach_catalog();
    $inventory=[];
    $normalize=static fn($name)=>fcc_coach_product_identity((string)$name);
    foreach($matrix as $key=>$entry) {
        $titles=array_values(array_unique(array_filter(array_merge([(string)($entry['primary_product']??'')],$entry['support_products']??[]))));
        $products=[];
        foreach($titles as $title) {
            $row=null;
            foreach($catalog as $candidate) {
                foreach(array_merge([$candidate['canonical_name']],$candidate['aliases']??[]) as $alias) {
                    if($normalize($title)===$normalize($alias)) { $row=$candidate; break 2; }
                }
            }
            $products[]=['title'=>$title,'catalog_product'=>$row['product_key']??null,
                'official_usage'=>fcc_coach_official_usage($row??[]),
                'safety_notes'=>$row['mandatory_safety_notes']??[],
                'legacy_usage'=>fcc_ai_get_public_product_routine_note_by_titles([$title],'hr')];
        }
        $inventory[]=['key'=>(string)$key,'title'=>(string)($entry['label']['hr']??$key),
            'patterns'=>$entry['patterns']??[], 'products'=>$products,
            'original_note'=>(string)($entry['opening_note']['hr']??''),
            'original_lines'=>$entry['recommendation_lines']['hr']??[],
            'original_monthly'=>(string)($entry['monthly_quantity_note']['hr']??''),
            'review_status'=>'requires_review',
            'source'=>'app/helpers/fcc_ai.php: fcc_ai_get_product_advisor_recommendation_matrix',
            'source_hash'=>hash('sha256',json_encode($entry,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)),
        ];
    }
    return $inventory;
}

/** The Coach sees provenance and topic, never unchecked disease doses or efficacy claims. */
function fcc_coach_recipe_context(string $text, int $uid=0, string $ref=''): array {
    $normalized=fcc_coach_normalize(fcc_german_query_equivalents($text));
    $matches=[];
    $documentQuestion=(bool)preg_match('/receptur|recepti|recipe|protokol|sto (?:pise|sadrzi)|koji su proizvodi navedeni/u',$normalized);
    $posts=[];$postSlugs=[];
    if($uid>0 && ($user=fcc_journey_user($uid))) foreach(fcc_partner_rows('SELECT blog_post_id,title,url FROM blog_posts WHERE is_published=1 AND language=?',[(string)$user->language]) as $post) { $posts[(int)$post['blog_post_id']]=$post; $postSlugs[$post['url']]=$post; }
    $catalog=fcc_coach_catalog();
    $market=fcc_coach_requested_market($text);
    $constraints=fcc_ai_v2_user_product_constraints($text);
    foreach(fcc_coach_recipe_inventory() as $entry) {
        $matched='';
        foreach($entry['patterns']??[] as $pattern) {
            $pattern=fcc_coach_normalize((string)$pattern);
            if(mb_strlen($pattern)>=4 && fcc_ai_v2_contains_phrase($normalized,$pattern) && mb_strlen($pattern)>mb_strlen($matched)) $matched=$pattern;
        }
        // A related source is not a diagnosis. Raised enzymes alone do not establish
        // fatty liver, and the original source's broad synonyms must not imply it.
        $relatedOnly=false;
        if($entry['key']==='fatty_liver_support') {
            $precise=(bool)preg_match('/masn\p{L}*\s+jetr\p{L}*|fatty liver|\b(?:masld|nafld|mash|nash)\b/u',$normalized);
            if(!$matched && preg_match('/\b(?:jetr\p{L}*|jert[au])\b/u',$normalized)) $matched='jetra';
            $relatedOnly=!$precise;
        }
        if($matched==='') continue;
        $match=['key'=>$entry['key'],'topic'=>$entry['title'],'status'=>'experience_note_requires_professional_review',
            'match_scope'=>$relatedOnly ? 'related_topic_not_diagnosis' : 'source_keyword',
            'source_hash'=>$entry['source_hash'], 'recommendation_allowed'=>false,
            'products_listed_in_record'=>array_column($entry['products'],'title'), 'product_information'=>[]];
        // Expose identity and owner links separately from advice. Do not leak the
        // original therapeutic claims, monthly quantities or outdated usage notes.
        foreach($entry['products'] as $product) {
            $row=$catalog[$product['catalog_product']??'']??null;
            $post=$row ? ($postSlugs[$row['product_key']]??$posts[(int)$row['blog_post_id']]??null) : null;
            $reviewed=$row && ($row['advisor_status']??'')==='recommendable' && ($row['claim_review_status']??'')==='approved' && ($row['safety_review_status']??'')==='complete';
            $blocked=($row['advisor_status']??'')==='blocked';
            $localFacts=$market==='' || $market==='HR';
            $match['product_information'][]=[
                'source_name'=>$product['title'], 'title'=>$row['canonical_name']??$product['title'],
                'identity_verified'=>(bool)$row, 'recommendation_allowed'=>false,
                'referral_url'=>$post && !$blocked && $ref!=='' ? url('blog/'.$post['url'].'?'.http_build_query(['ref'=>$ref])) : null,
                'declared_product_type'=>$reviewed && $localFacts ? $row['use_cases'] : [],
                'constraint_conflicts'=>$row ? fcc_ai_v2_product_constraint_conflicts($row,$constraints) : [],
                'status'=>$blocked ? 'blocked' : ($reviewed && $localFacts ? 'reviewed_identity_and_type_only' : 'source_name_only'),
            ];
        }
        $matches[]=$match;
    }
    return ['available_records'=>count(fcc_coach_recipe_inventory()),'source'=>'Existing FCC source snapshot. Current production content has not been verified.',
        'document_question'=>$documentQuestion,
        'purpose'=>'Identify existing experience notes, not evidence of clinical efficacy or a personalised regimen.',
        'matches'=>array_slice($matches,0,4),
        'usage_policy'=>'Only label_usage from the reviewed product packet can supply general use instructions for the explicit market. Old monthly quantities and legacy combinations are not approved dosage instructions.'];
}
