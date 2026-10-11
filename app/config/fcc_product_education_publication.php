<?php

/* Explicit author decisions control the published learning surface, not the source archive. */
return [
    'updated_at' => '2026-10-07',
    'withdrawn_issue_ids' => ['I01', 'I02', 'I04', 'I05', 'I06'],
    'product_overrides' => [
        'fab-forever-active-boost' => [
            'image_lookup_key' => 'forever-fab-x',
            'image_lookup_item_code' => '822',
        ],
        'start-paket' => [
            'photo_requirement' => 'variable_bundle',
            'photo_note' => 'Sadržaj početnog paketa ovisi o tržištu i odabranoj varijanti. Fotografije sastavnih proizvoda proučavaju se nakon odabira točnog paketa.',
        ],
    ],
    'approval' => [
        'proposals' => 'all_prepared_educational_cases',
        'topics' => 'all_sixteen_prepared_topics',
        'approved_at' => '2026-10-07',
        'source' => 'Izričita autorova potvrda u razgovoru.',
    ],
];
