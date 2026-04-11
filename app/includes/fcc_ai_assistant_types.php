<?php
defined('ALTUMCODE') || die();

return [
    'coach' => [
        'label' => l('fcc_ai.assistant.coach.label'),
        'description' => l('fcc_ai.assistant.coach.description'),
        'icon' => 'fas fa-life-ring',
        'is_public' => false,
        'default_scope' => 'internal_coach',
        'allowed_scopes' => ['internal_coach'],
        'supports_lead_capture' => false,
        'supports_blog_continuation' => false,
    ],
    'product_advisor' => [
        'label' => l('fcc_ai.assistant.product_advisor.label'),
        'description' => l('fcc_ai.assistant.product_advisor.description'),
        'icon' => 'fas fa-comments',
        'is_public' => true,
        'default_scope' => 'public_app',
        'allowed_scopes' => ['public_app', 'public_blog'],
        'supports_lead_capture' => true,
        'supports_blog_continuation' => true,
    ],
    'pets_advisor' => [
        'label' => l('fcc_ai.assistant.pets_advisor.label'),
        'description' => l('fcc_ai.assistant.pets_advisor.description'),
        'icon' => 'fas fa-paw',
        'is_public' => true,
        'default_scope' => 'public_app',
        'allowed_scopes' => ['public_app', 'public_blog'],
        'supports_lead_capture' => true,
        'supports_blog_continuation' => true,
    ],
];
