<?php

return [

'ai' => [
        'provider' => env('AI_PROVIDER', 'groq'),
        'chat_max_tokens' => max(1024, (int) env('AI_CHAT_MAX_TOKENS', 8192)),
        'structured_retries' => max(1, (int) env('AI_STRUCTURED_RETRIES', 3)),
        'structured_retry_base_ms' => max(0, (int) env('AI_STRUCTURED_RETRY_BASE_MS', 1000)),
    ],

    'groq' => [
        'key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
        'vision_model' => env('GROQ_VISION_MODEL', 'qwen/qwen3.8-27b'),
        'url' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'),
        'structured_strict' => env('GROQ_STRUCTURED_STRICT', true),
        'report_max_tokens' => (int) env('GROQ_REPORT_MAX_TOKENS', 8192),
    ],

    'google_ai' => [
        'key' => env('GOOGLE_API_KEY'),
        'model' => env('GOOGLE_MODEL', 'gemini-2.5-flash'),
        'url' => env('GOOGLE_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],

    'reniec' => [
        'dni_usuario' => env('RENIEC_DNI_USUARIO'),
        'ruc_usuario' => env('RENIEC_RUC_USUARIO'),
        'password' => env('RENIEC_PASSWORD'),
        'consultar_url' => env('RENIEC_CONSULTAR_URL', 'https://ws2.pide.gob.pe/Rest/RENIEC/Consultar?out=json'),
        'actualizar_url' => env('RENIEC_ACTUALIZAR_URL', 'https://ws2.pide.gob.pe/Rest/RENIEC/Actualizar?out=json'),
        'connect_timeout' => (int) env('RENIEC_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('RENIEC_TIMEOUT', 15),
    ],

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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
