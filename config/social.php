<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Social publishing
    |--------------------------------------------------------------------------
    |
    | Page and Instagram business IDs are optional. When they are empty the
    | publisher resolves them from the Graph API using the access token.
    |
    */

    'access_token' => env('SOCIAL_MEDIA_ACCESS_TOKEN'),

    'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v22.0'),

    'facebook' => [
        'app_id' => env('FACEBOOK_APP_ID'),
        'page_id' => env('FACEBOOK_PAGE_ID'),
    ],

    'instagram' => [
        'app_id' => env('INSTAGRAM_APP_ID'),
        'business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID'),
    ],

    'search' => [
        'serper_key' => env('SERPER_API_KEY'),
        'tavily_key' => env('TAVILY_API_KEY'),
    ],

    'planner' => [
        'batch_size' => 3,
        'max_posts' => 72,
        'max_failures' => 3,
    ],

];
