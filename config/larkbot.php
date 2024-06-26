<?php

return [
    /** The default bot will be used */
    'default_bot' => 'default',

    'base_path' => 'https://open.larksuite.com/open-apis',
    
    'connect_timeout' => 2,

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
