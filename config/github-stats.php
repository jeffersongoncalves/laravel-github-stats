<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GitHub Username
    |--------------------------------------------------------------------------
    |
    | The GitHub username to fetch stats for. All endpoints are locked to this
    | username only — this service is private by design.
    |
    */
    'username' => env('GITHUB_USERNAME', 'jeffersongoncalves'),

    /*
    |--------------------------------------------------------------------------
    | GitHub Personal Access Token
    |--------------------------------------------------------------------------
    |
    | Create at: https://github.com/settings/tokens/new
    | Required scopes: read:user
    | Optional scope: repo (to include private repo stats)
    |
    */
    'token' => env('GITHUB_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTLs (in seconds)
    |--------------------------------------------------------------------------
    |
    | data_ttl: How long to cache raw GitHub API responses (default: 4 hours)
    | svg_ttl: How long to cache rendered SVG cards (default: 1 hour)
    |
    */
    'cache' => [
        'data_ttl' => (int) env('GITHUB_STATS_DATA_TTL', 14400),
        'svg_ttl' => (int) env('GITHUB_STATS_SVG_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub API URLs
    |--------------------------------------------------------------------------
    */
    'graphql_url' => 'https://api.github.com/graphql',
    'rest_url' => 'https://api.github.com',

    /*
    |--------------------------------------------------------------------------
    | Default Card Settings
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'theme' => env('GITHUB_STATS_THEME', 'tokyonight'),
        'hide_border' => false,
        'show_icons' => true,
        'langs_count' => 8,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The prefix for all GitHub Stats routes. Default: 'api'
    | This means routes will be: /api/stats, /api/streak, etc.
    |
    */
    'route_prefix' => env('GITHUB_STATS_ROUTE_PREFIX', 'api'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | Additional middleware to apply to GitHub Stats routes.
    |
    */
    'middleware' => [],
];
