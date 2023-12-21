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
    
    'skip_invalid_receiver_error' => env('LARK_BOT_SKIP_INVALID_RECEIVER', true),
];
