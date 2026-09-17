<?php

return [
    'username' => env('DIGIFLAZZ_USERNAME'),

    'mode' => env('DIGIFLAZZ_MODE', 'development'),

    'development_key' => env('DIGIFLAZZ_DEVELOPMENT_KEY'),

    'production_key' => env('DIGIFLAZZ_PRODUCTION_KEY'),

    'base_url' => env('DIGIFLAZZ_BASE_URL', 'https://api.digiflazz.com/v1'),

    'webhook_secret' => env('DIGIFLAZZ_WEBHOOK_SECRET'),

    'timeout' => (int) env('DIGIFLAZZ_TIMEOUT', 30),
];
