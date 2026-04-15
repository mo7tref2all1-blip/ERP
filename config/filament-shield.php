<?php

return [
    'shield_resource' => [
        'should_register_navigation' => true,
        'navigation_sort' => -1,
        'navigation_badge' => true,
        'navigation_group' => true,
        'is_globally_searchable' => false,
        'show_model_path' => true,
        'generate_for_all_panels' => false,
        'panel_ids' => ['admin'],
    ],

    'auth_provider_model' => [
        'fqcn' => 'App\Models\User',
    ],

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    'filament_user' => [
        'enabled' => false,
        'name' => 'filament_user',
    ],

    'permission_prefixes' => [
        'resource' => [
            'view',
            'view_any',
            'create',
            'update',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
        ],
        'page' => 'page',
        'widget' => 'widget',
    ],

    'entities' => [
        'pages' => true,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => false,
    ],

    'generator' => [
        'option' => 'policies_and_permissions',
    ],

    'exclude' => [
        'enabled' => true,
        'pages' => ['Dashboard'],
        'widgets' => [
            'AccountWidget',
            'FilamentInfoWidget',
        ],
        'resources' => [],
    ],

    'discovery' => [
        'discover_all_resources' => false,
        'discover_all_widgets' => false,
        'discover_all_pages' => false,
    ],
];
