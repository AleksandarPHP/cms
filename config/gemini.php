<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gemini
    |--------------------------------------------------------------------------
    |
    | Text generation uses Claude. This key is only for image generation.
    | GEMINI_API_KEY is preferred. AI_API_KEY remains as a fallback.
    |
    */

    'api_key' => env('GEMINI_API_KEY', env('AI_API_KEY')),

    'model' => env('GEMINI_MODEL', 'gemini-3.8-flash'),

    'image_model' => env('GEMINI_IMAGE_MODEL', 'gemini-3.1-flash-image'),

    'timeout' => (int) env('GEMINI_TIMEOUT', 90),

];
