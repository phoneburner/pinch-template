<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Component\MessageBus\Handler\InvokableMessageHandler;
use PhoneBurner\Pinch\Component\MessageBus\Message\InvokableMessage;
use PhoneBurner\Pinch\Component\MessageBus\MessageBus;
use PhoneBurner\Pinch\Framework\Database\Doctrine\ConnectionFactory;
use PhoneBurner\Pinch\Framework\Database\Redis\RedisManager;
use PhoneBurner\Pinch\Framework\HttpClient\Webhook\Message\HalResourceWebhookDeliveryMessage;
use PhoneBurner\Pinch\Framework\HttpClient\Webhook\Message\SimpleEventWebhookDeliveryMessage;
use PhoneBurner\Pinch\Framework\HttpClient\Webhook\MessageHandler\WebhookDeliveryMessageHandler;
use PhoneBurner\Pinch\Framework\MessageBus\Config\BusConfigStruct;
use PhoneBurner\Pinch\Framework\MessageBus\Config\MessageBusConfigStruct;
use PhoneBurner\Pinch\Framework\MessageBus\Config\TransportConfigStruct;
use PhoneBurner\Pinch\Framework\MessageBus\Transport;
use PhoneBurner\Pinch\Framework\MessageBus\TransportFactory\DoctrineTransportFactory;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Console\Messenger\RunCommandMessageHandler;
use Symfony\Component\Mailer\Messenger\MessageHandler as EmailMessageHandler;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\DoctrineTransport;
use Symfony\Component\Messenger\Bridge\Redis\Transport\RedisTransport;
use Symfony\Component\Messenger\Handler\RedispatchMessageHandler;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\Middleware\DispatchAfterCurrentBusMiddleware;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Process\Messenger\RunProcessMessage;
use Symfony\Component\Process\Messenger\RunProcessMessageHandler;

return [
    'message_bus' => new MessageBusConfigStruct(
        bus: [
            MessageBus::DEFAULT => new BusConfigStruct(
                middleware: [
                    DispatchAfterCurrentBusMiddleware::class,
                    SendMessageMiddleware::class,
                    HandleMessageMiddleware::class,
                ],
            ),
        ],
        handlers: [
            InvokableMessage::class => [
                InvokableMessageHandler::class,
            ],
            SendEmailMessage::class => [
                EmailMessageHandler::class,
            ],
            RedispatchMessage::class => [
                RedispatchMessageHandler::class,
            ],
            RunCommandMessage::class => [
                RunCommandMessageHandler::class,
            ],
            RunProcessMessage::class => [
                RunProcessMessageHandler::class,
            ],
            SimpleEventWebhookDeliveryMessage::class => [
                WebhookDeliveryMessageHandler::class,
            ],
            HalResourceWebhookDeliveryMessage::class => [
                WebhookDeliveryMessageHandler::class,
            ],
        ],
        routing: [ // messages not mapped to a transport are handled synchronously.
            InvokableMessage::class => [Transport::ASYNC],
            SendEmailMessage::class => [Transport::ASYNC],
            RedispatchMessage::class => [Transport::ASYNC],
            SimpleEventWebhookDeliveryMessage::class => [Transport::ASYNC],
            HalResourceWebhookDeliveryMessage::class => [Transport::ASYNC],
        ],
        senders: [
            Transport::ASYNC => new TransportConfigStruct(
                class: RedisTransport::class,
                connection: RedisManager::DEFAULT,
                options: [
                    'stream' => 'messages',
                    'group' => 'pinch',
                    'consumer' => null, // use hostname
                ],
            ),
        ],
        receivers: [
            Transport::ASYNC => new TransportConfigStruct(
                class: RedisTransport::class,
                connection: RedisManager::DEFAULT,
                options: [
                    'stream' => 'messages',
                    'group' => 'pinch',
                    'consumer' => null, // use hostname
                ],
            ),
        ],
        failure_senders: [
            Transport::ASYNC => new TransportConfigStruct(
                class: DoctrineTransport::class,
                connection: ConnectionFactory::DEFAULT,
                options: [
                    'table_name' => DoctrineTransportFactory::DEFAULT_FAILED_MESSAGE_TABLE,
                    'queue_name' => 'failed',
                    'redeliver_timeout' => 3600,
                ],
            ),
        ],
        retry_strategy: [
            Transport::ASYNC => [
                'class' => MultiplierRetryStrategy::class,
                'params' => [
                    'max_retries' => 3,
                    'delay' => 1000,
                    'multiplier' => 2,
                ],
            ],
        ],
    ),
];
