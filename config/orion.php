<?php

return [
    'namespaces' => [
        'models' => 'App\\Models\\',
        'controllers' => 'App\\Http\\Controllers\\',
    ],
    'auth' => [
        'guard' => 'api',
    ],
    'specs' => [
        'info' => [
            'title' => env('APP_NAME'),
            'description' => null,
            'terms_of_service' => null,
            'contact' => [
                'name' => null,
                'url' => null,
                'email' => null,
            ],
            'license' => [
                'name' => null,
                'url' => null,
            ],
            'version' => '1.0.0',
        ],
        'servers' => [
            ['url' => env('APP_URL').'/api', 'description' => 'Default Environment'],
        ],
        'tags' => [],
    ],
    'transactions' => [
        'enabled' => false,
    ],
    'search' => [
        'case_sensitive' => true, // TODO: set to "false" by default in 3.0 release
        /*
         |--------------------------------------------------------------------------
         | Max Nested Depth
         |--------------------------------------------------------------------------
         |
         | This value is the maximum depth of nested filters.
         | You will most likely need this to be maximum at 1, but
         | you can increase this number, if necessary. Please
         | be aware that the depth generate dynamic rules and can slow
         | your application if someone sends a request with thousands of nested
         | filters.
         |
         */
        'max_nested_depth' => 1,
    ],

    'search_links' => [
        'driver' => env('ORION_SEARCH_LINKS_DRIVER', 'filesystem'),
        'id_prefix' => 'srch_',
        'id_length' => 12,
        'ttl' => env('ORION_SEARCH_LINKS_TTL', 86400),
        'filesystem' => [
            'path' => storage_path('framework/orion/search-links'),
        ],
        'database' => [
            'connection' => null,
            'table' => 'orion_search_links',
        ],
        'redis' => [
            'cache_store' => 'redis',
            'key_prefix' => 'orion:search-links:',
        ],
        'payload_keys' => [
            'aggregates',
            'filters',
            'includes',
            'limit',
            'scopes',
            'search',
            'sort',
        ],
        'query_keys' => [
            'include',
            'only_trashed',
            'with_avg',
            'with_count',
            'with_exists',
            'with_max',
            'with_min',
            'with_sum',
            'with_trashed',
        ],
    ],

    'use_validated' => false,

    'route_discovery' => [
        'enabled' => false,
        'paths' => [
            app_path('Http/Controllers/Api'),
        ],
        'route_prefix' => 'api',
        'route_name_prefix' => 'api',
        'route_middleware' => [
            // Add custom middleware here - eg: 'auth:sanctum',
        ],
    ],
];
