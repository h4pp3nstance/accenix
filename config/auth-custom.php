<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for OAuth authentication with Identity Server
    |
    */
    'oauth' => [
        'client_id' => env('IS_CLIENT_ID'),
        'client_secret' => env('IS_CLIENT_SECRET'),
        'auth_url' => env('AUTH_URL'),
        'token_url' => env('IS_TOKEN_URL'),
        'logout_url' => env('IS_LOGOUT_URL'),
        'default_scopes' => ['openid', 'profile', 'roles', 'internal_login', 'SYSTEM'],
        'app_id' => env('IS_ID'),
        'redirect_uri' => env('REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SCIM API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SCIM user management API
    |
    */
    'scim' => [
        'user_url' => env('USER_URL'),
        'token_url' => env('SCIM_TOKEN_URL'),
        'client_id' => env('IS_CLIENT_ID'),
        'client_secret' => env('IS_CLIENT_SECRET'),
        'verify_ssl' => env('SCIM_VERIFY_SSL', true),
        'timeout' => env('SCIM_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Default routes for authentication flow
    |
    */
    'routes' => [
        'dashboard' => env('AUTH_DASHBOARD_ROUTE', 'dashboard-dev'),
        'login' => env('AUTH_LOGIN_ROUTE', '/'),
        'profile_view' => env('AUTH_PROFILE_VIEW', 'profile'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Management
    |--------------------------------------------------------------------------
    |
    | Configuration for token handling and refresh
    |
    */
    'tokens' => [
        'refresh_threshold' => env('TOKEN_REFRESH_THRESHOLD', 300), // 5 minutes
        'max_refresh_attempts' => env('TOKEN_MAX_REFRESH_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for authentication
    |
    */
    'security' => [
        'max_login_attempts' => env('AUTH_MAX_LOGIN_ATTEMPTS', 5),
        'lockout_duration' => env('AUTH_LOCKOUT_DURATION', 900), // 15 minutes
        'session_lifetime' => env('AUTH_SESSION_LIFETIME', 7200), // 2 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for profile photo uploads
    |
    */
    'uploads' => [
        'profile_photos' => [
            'disk' => 'public',
            'path' => 'img-users',
            'max_size' => 2048, // KB
            'allowed_mimes' => ['jpeg', 'jpg', 'png', 'gif', 'webp'],
            'dimensions' => [
                'min_width' => 100,
                'min_height' => 100,
                'max_width' => 2000,
                'max_height' => 2000,
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for authentication logging
    |
    */
    'logging' => [
        'enabled' => env('AUTH_LOGGING_ENABLED', true),
        'channel' => env('AUTH_LOG_CHANNEL', 'stack'),
        'level' => env('AUTH_LOG_LEVEL', 'info'),
    ]
];
