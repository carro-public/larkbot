<?php

return [
    /** The default bot will be used */
    'default_bot' => 'default',

    'base_path' => 'https://open.larksuite.com/open-apis',
    
    'connection_options' => [
        'connect_timeout' => env('LARKBOT_CONNECT_TIMEOUT', 2),
        'retries' => env('LARKBOT_CONNECT_RETRIES', 3),
        'backoff' => env('LARKBOT_CONNECT_RETRIES_BACKOFF', 5000),
    ],

    /** All bot credentials */
    'bots' => [
        'default' => [
            'app_id' => env('LARK_BOT_APP_ID'),
            'app_secret' => env('LARK_BOT_APP_SECRET'),
            'allowed_domain_names' => explode(',', env('LARK_BOT_ALLOWED_DOMAIN_NAMES', '')),
        ]
    ],
    
    'skippable_error_codes' => env('LARK_BOT_SKIPPABLE_ERROR_CODES', '230001'),
];
