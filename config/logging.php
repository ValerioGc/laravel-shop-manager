<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

use function Laravel\Prompts\error;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that is utilized to write
    | messages to your logs. The value provided here should match one of
    | the channels present in the list of "channels" configured below.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    |
    | This option controls the log channel that should be used to log warnings
    | regarding deprecated PHP and library features. This allows you to get
    | your application ready for upcoming major versions of dependencies.
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => env('LOG_DEPRECATIONS_TRACE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Laravel
    | utilizes the Monolog PHP logging library, which includes a variety
    | of powerful log handlers and formatters that you're free to use.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog", "custom", "stack"
    |
    */
    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', env('LOG_STACK', 'single')),
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'public' => [
            'driver' => 'daily',
            'path' => storage_path('logs/public_website.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 3,
        ],

        'cache' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cache/cache.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 2,
        ],
        
        'compression' => [
            'driver' => 'daily',
            'path' => storage_path('logs/cache/response_compression.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 2,
        ],

        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security/policy.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 3,
        ],

        'authentication' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security/authentication.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7,
        ],

        'trash_scheduler' => [
            'driver' => 'daily',
            'path' => storage_path('logs/entity/products/empty_trash_scheduler.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 29,
        ],

        'user_profile' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security/user_profile.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 5,
        ],

        'faqs' => [
            'driver' => 'daily',
            'path' => storage_path('logs/entity/faqs/faqs.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 5,
        ],

        'products' => [
            'driver' => 'daily',
            'path' => storage_path('logs/entity/products/products.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 5,
        ],

        'config' => [
            'driver' => 'daily',
            'path' => storage_path('logs/fe_config/fe_config.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 5,
        ],

        'shows' => [
            'driver' => 'daily',
            'path' => storage_path('logs/entity/shows/shows.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 5,
        ],

        'search' => [
            'driver' => 'daily',
            'path' => storage_path('logs/search/search.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 5,
        ],

        'categories' => [
            'driver' => 'daily',
            'path' => storage_path('logs/entity/categories/categories.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 5,
        ],

        'contacts' => [
            'driver' => 'daily',
            'path' => storage_path('logs/entity/contacts/contacts.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 5,
        ],
        
        'conditions' => [
            'driver' => 'daily',
            'path' => storage_path('logs/entity/conditions/conditions.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 5,
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => env('LOG_SYSLOG_FACILITY', LOG_USER),
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],
    ]
];