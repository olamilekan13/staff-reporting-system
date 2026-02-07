<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The current version of the API. This is used in route prefixes and
    | can be referenced throughout the application.
    |
    */

    'version' => env('API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for API responses.
    |
    */

    'pagination' => [
        'default_limit' => env('API_PAGINATION_LIMIT', 15),
        'max_limit' => env('API_PAGINATION_MAX_LIMIT', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for API endpoints. The 'requests' value is the
    | maximum number of requests allowed within the 'minutes' time window.
    |
    */

    'rate_limits' => [
        'default' => [
            'requests' => env('API_RATE_LIMIT', 60),
            'minutes' => 1,
        ],
        'auth' => [
            'requests' => env('API_AUTH_RATE_LIMIT', 5),
            'minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Settings
    |--------------------------------------------------------------------------
    |
    | Configure default response behavior.
    |
    */

    'response' => [
        'include_debug' => env('API_DEBUG', false),
    ],

];
