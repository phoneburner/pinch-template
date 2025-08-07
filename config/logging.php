<?php

declare(strict_types=1);

use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LogglyFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use PhoneBurner\Pinch\Component\Logging\LogLevel;
use PhoneBurner\Pinch\Framework\Logging\Config\LoggingConfigStruct;
use PhoneBurner\Pinch\Framework\Logging\Config\LoggingHandlerConfigStruct;
use PhoneBurner\Pinch\Framework\Logging\Monolog\Handler\ResettableLogglyHandler;
use PhoneBurner\Pinch\Framework\Logging\Monolog\Processor\EnvironmentProcessor;
use PhoneBurner\Pinch\Framework\Logging\Monolog\Processor\LogTraceProcessor;
use PhoneBurner\Pinch\Framework\Logging\Monolog\Processor\PhoneNumberProcessor;
use PhoneBurner\Pinch\Framework\Logging\Monolog\Processor\PsrMessageInterfaceProcessor;

use function PhoneBurner\Pinch\Framework\env;
use function PhoneBurner\Pinch\Framework\path;
use function PhoneBurner\Pinch\Framework\stage;

return [
    'logging' => new LoggingConfigStruct(
        channel: env('PINCH_PSR3_LOG_CHANNEL'),
        processors: [
            PsrMessageInterfaceProcessor::class,
            PhoneNumberProcessor::class,
            EnvironmentProcessor::class,
            LogTraceProcessor::class,
            PsrLogMessageProcessor::class, // must be after any processors that mutate context
        ],
        // Configure Handlers By Build Stage
        handlers: stage(
            [
                new LoggingHandlerConfigStruct(
                    handler_class: ResettableLogglyHandler::class,
                    handler_options: [
                        'token' => (string)env('PINCH_LOGGLY_TOKEN'),
                    ],
                    formatter_class: LogglyFormatter::class,
                    level: LogLevel::instance(env('PINCH_PSR3_LOG_LEVEL', LogLevel::Info)),
                ),
                new LoggingHandlerConfigStruct(
                    handler_class: SlackWebhookHandler::class,
                    handler_options: [
                        'webhook_url' => (string)env('PINCH_SLACK_WEBHOOK_URL'),
                        'channel' => (string)env('PINCH_SLACK_DEFAULT_CHANNEL'),
                    ],
                    formatter_class: LogglyFormatter::class,
                    level: LogLevel::Critical,
                ),
            ],
            [
                new LoggingHandlerConfigStruct(
                    handler_class: StreamHandler::class,
                    handler_options: [
                        'stream' => \sys_get_temp_dir() . '/pinch/pinch.log',
                    ],
                    formatter_class: JsonFormatter::class,
                    level: LogLevel::instance(env('PINCH_PSR3_LOG_LEVEL', LogLevel::Debug)),
                ),
                new LoggingHandlerConfigStruct(
                    handler_class: RotatingFileHandler::class,
                    handler_options: [
                        'filename' => path('/storage/logs/pinch.log'),
                        'max_files' => 7,
                    ],
                    formatter_class: JsonFormatter::class,
                    level: LogLevel::instance(env('PINCH_PSR3_LOG_LEVEL', LogLevel::Debug)),
                ),
            ],
        ),
    ),
];
