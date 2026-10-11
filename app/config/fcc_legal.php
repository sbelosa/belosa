<?php
/** Public operator data only. Do not put credentials or member offers here. */
return [
    'version' => '2026-09-26.1',
    'updated' => '2026-09-26',
    'operator' => [
        'name' => 'BS International',
        'address' => 'Ivana Gorana Kovačića 15, Velika Mlaka, Hrvatska',
        'registration_id' => '15435222026',
        'email' => 'info@forevercard.club',
        'identity_confirmed' => true,
    ],
    'team' => 'Stjepan i Snježana Beloša',
    'team_role' => 'Diamond Sapphire manageri u Forever Living Products',
    'webinar_retention_days' => 90,
    // Enable only after the operator verifies the actual production inventory.
    'production_review' => [
        'processors_and_transfers' => false,
        'retention_and_backups' => false,
        'member_agreements' => false,
    ],
];
