<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TikTok Scraper Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the TikTok Scraper
    | Laravel package. You can customize the behavior of the scraper,
    | caching, rate limiting, and other settings here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the HTTP client settings for making requests to TikTok.
    | You can set timeouts, retries, and other HTTP-related options.
    |
    */
    'http' => [
        'timeout' => env('TIKTOK_SCRAPER_TIMEOUT', 30),
        'connect_timeout' => env('TIKTOK_SCRAPER_CONNECT_TIMEOUT', 10),
        'retries' => env('TIKTOK_SCRAPER_RETRIES', 3),
        'retry_delay' => env('TIKTOK_SCRAPER_RETRY_DELAY', 1000), // milliseconds
        'user_agent' => env(
            'TIKTOK_SCRAPER_USER_AGENT',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36'
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for scraped data to improve performance
    | and reduce the number of requests to TikTok servers.
    |
    */
    'cache' => [
        'enabled' => env('TIKTOK_SCRAPER_CACHE_ENABLED', true),
        'store' => env('TIKTOK_SCRAPER_CACHE_STORE', null), // null uses default cache store
        'ttl' => env('TIKTOK_SCRAPER_CACHE_TTL', 3600), // seconds (1 hour)
        'prefix' => env('TIKTOK_SCRAPER_CACHE_PREFIX', 'tiktok_scraper'),
        'tags' => env('TIKTOK_SCRAPER_CACHE_TAGS', 'tiktok,scraper'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting to prevent overwhelming TikTok servers
    | and avoid potential IP blocking.
    |
    */
    'rate_limiting' => [
        'enabled' => env('TIKTOK_SCRAPER_RATE_LIMIT_ENABLED', true),
        'max_attempts' => env('TIKTOK_SCRAPER_RATE_LIMIT_MAX_ATTEMPTS', 60),
        'decay_seconds' => env('TIKTOK_SCRAPER_RATE_LIMIT_DECAY', 60),
        'prefix' => env('TIKTOK_SCRAPER_RATE_LIMIT_PREFIX', 'tiktok_scraper_rate_limit'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure URL validation and filtering settings.
    |
    */
    'validation' => [
        'strict_url_validation' => env('TIKTOK_SCRAPER_STRICT_URL_VALIDATION', true),
        'allowed_domains' => [
            'tiktok.com',
            'www.tiktok.com',
            'm.tiktok.com',
            'vm.tiktok.com',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging behavior for the TikTok scraper.
    |
    */
    'logging' => [
        'enabled' => env('TIKTOK_SCRAPER_LOGGING_ENABLED', true),
        'channel' => env('TIKTOK_SCRAPER_LOG_CHANNEL', 'stack'),
        'level' => env('TIKTOK_SCRAPER_LOG_LEVEL', 'info'),
        'log_requests' => env('TIKTOK_SCRAPER_LOG_REQUESTS', false),
        'log_responses' => env('TIKTOK_SCRAPER_LOG_RESPONSES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for background scraping jobs.
    |
    */
    'queue' => [
        'enabled' => env('TIKTOK_SCRAPER_QUEUE_ENABLED', false),
        'connection' => env('TIKTOK_SCRAPER_QUEUE_CONNECTION', 'default'),
        'queue' => env('TIKTOK_SCRAPER_QUEUE_NAME', 'tiktok-scraper'),
        'timeout' => env('TIKTOK_SCRAPER_QUEUE_TIMEOUT', 60),
        'max_tries' => env('TIKTOK_SCRAPER_QUEUE_MAX_TRIES', 3),
        'backoff' => env('TIKTOK_SCRAPER_QUEUE_BACKOFF', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Processing Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how scraped data should be processed and formatted.
    |
    */
    'data_processing' => [
        'sanitize_output' => env('TIKTOK_SCRAPER_SANITIZE_OUTPUT', true),
        'include_raw_data' => env('TIKTOK_SCRAPER_INCLUDE_RAW_DATA', false),
        'date_format' => env('TIKTOK_SCRAPER_DATE_FORMAT', 'Y-m-d H:i:s'),
        'timezone' => env('TIKTOK_SCRAPER_TIMEZONE', 'UTC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    |
    | Configure which events should be dispatched during scraping operations.
    |
    */
    'events' => [
        'dispatch_events' => env('TIKTOK_SCRAPER_DISPATCH_EVENTS', true),
        'video_scraped' => env('TIKTOK_SCRAPER_EVENT_VIDEO_SCRAPED', true),
        'scraping_failed' => env('TIKTOK_SCRAPER_EVENT_SCRAPING_FAILED', true),
        'rate_limit_hit' => env('TIKTOK_SCRAPER_EVENT_RATE_LIMIT_HIT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development & Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options useful during development and debugging.
    |
    */
    'debug' => [
        'enabled' => env('TIKTOK_SCRAPER_DEBUG', false),
        'dump_html' => env('TIKTOK_SCRAPER_DEBUG_DUMP_HTML', false),
        'dump_json' => env('TIKTOK_SCRAPER_DEBUG_DUMP_JSON', false),
        'fake_responses' => env('TIKTOK_SCRAPER_FAKE_RESPONSES', false),
    ],
];
