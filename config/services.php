<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'timeout' => (int) env('GEMINI_TIMEOUT', 30),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'timeout' => (int) env('OPENAI_TIMEOUT', 30),
    ],

    'google_translate' => [
        'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
        'timeout' => (int) env('GOOGLE_TRANSLATE_TIMEOUT', 30),
    ],

    'google_analytics' => [
        'measurement_id' => env('GOOGLE_ANALYTICS_MEASUREMENT_ID', 'G-TG66FPTB0Z'),
    ],

    'whatsapp' => [
        'enabled' => filter_var(env('WHATSAPP_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v21.0'),
        'recipient' => env('WHATSAPP_RECIPIENT'),
        'default_language' => env('WHATSAPP_DEFAULT_LANGUAGE', 'ka'),
        'timeout' => (int) env('WHATSAPP_TIMEOUT', 15),
        'templates' => [
            'call_request' => env('WHATSAPP_TEMPLATE_CALL_REQUEST', 'call_request_alert'),
            'new_order' => env('WHATSAPP_TEMPLATE_NEW_ORDER', 'new_order_alert'),
            'contact_message' => env('WHATSAPP_TEMPLATE_CONTACT_MESSAGE', 'new_contact_message'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | NVIDIA NIM
    |--------------------------------------------------------------------------
    |
    | API keys can be obtained from https://build.nvidia.com/
    |
    */
    'nim' => [
        'api_key' => env('NVIDIA_NIM_API_KEY'),
        'model' => env('NIM_MODEL', 'nim-rag-1b'),
        'base_url' => env('NIM_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        'timeout' => (int) env('NIM_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenRouter
    |--------------------------------------------------------------------------
    |
    | API keys can be obtained from https://openrouter.ai/keys
    |
    */
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'openrouter/router'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Provider Selection
    |--------------------------------------------------------------------------
    |
    | Choose which provider to use for translation and SEO generation.
    |
    */
    'translation' => [
        'provider' => env('TRANSLATION_PROVIDER', 'gemini'),
    ],

    'seo' => [
        'provider' => env('SEO_PROVIDER', 'openrouter'),
    ],

    'frontend' => [
        'revalidate_url' => env('FRONTEND_REVALIDATE_URL'),
        'revalidate_secret' => env('FRONTEND_REVALIDATE_SECRET'),
    ],

];
