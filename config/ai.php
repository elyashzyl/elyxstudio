<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Provider for quiz generation
    |--------------------------------------------------------------------------
    | auto: use Gemini when GEMINI_API_KEY is set, else OpenAI when
    | OPENAI_API_KEY is set, else the built-in offline generator.
    | Explicit values: gemini, openai, heuristic.
    */
    'provider' => env('AI_PROVIDER', 'auto'),

    'gemini_api_key' => env('GEMINI_API_KEY'),
    'gemini_model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),

    'openai_api_key' => env('OPENAI_API_KEY'),
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'openai_base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),

    'omniroute_api_key' => env('OMNIROUTE_API_KEY'),
    'omniroute_base_url' => env('OMNIROUTE_BASE_URL', 'http://admin-omniroute-cec098-100-104-13-10.sslip.io/v1'),
    'omniroute_model' => env('OMNIROUTE_MODEL', 'agy/gemini-3.7-flash-high'),

    'ninerouter_api_key' => env('NINEROUTER_API_KEY', env('ROUTER9_API_KEY', env('9ROUTER_API_KEY'))),
    'ninerouter_base_url' => env('NINEROUTER_BASE_URL', env('ROUTER9_BASE_URL', env('9ROUTER_BASE_URL', 'http://localhost:20128/v1'))),
    'ninerouter_model' => env('NINEROUTER_MODEL', env('ROUTER9_MODEL', env('9ROUTER_MODEL', 'auto'))),

    'max_source_chars' => env('AI_MAX_SOURCE_CHARS', 30000),
    'request_timeout' => 60,

    /*
    |--------------------------------------------------------------------------
    | AI Autonomous System Manager
    |--------------------------------------------------------------------------
    */
    'manager' => [
        'enabled' => env('AI_MANAGER_ENABLED', false),
        'mode' => env('AI_MANAGER_MODE', 'advisory'), // advisory, autonomous
        'interval_minutes' => env('AI_MANAGER_INTERVAL_MINUTES', 15),
        'auto_grade' => env('AI_MANAGER_AUTO_GRADE', true),
        'self_heal_files' => env('AI_MANAGER_SELF_HEAL_FILES', true),
        'anomaly_detection' => env('AI_MANAGER_ANOMALY_DETECTION', true),
        'game_reopen' => env('AI_MANAGER_GAME_REOPEN', true),
        'confidence_threshold' => env('AI_MANAGER_CONFIDENCE_THRESHOLD', 0.85),
    ],
];
