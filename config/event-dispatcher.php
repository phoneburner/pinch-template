<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Component\App\Event\ApplicationBootstrap;
use PhoneBurner\Pinch\Component\App\Event\ApplicationTeardown;
use PhoneBurner\Pinch\Component\Logging\LogLevel;
use PhoneBurner\Pinch\Component\MessageBus\Event\InvokableMessageHandlingComplete;
use PhoneBurner\Pinch\Component\MessageBus\Event\InvokableMessageHandlingFailed;
use PhoneBurner\Pinch\Component\MessageBus\Event\InvokableMessageHandlingStarting;
use PhoneBurner\Pinch\Framework\EventDispatcher\Config\EventDispatcherConfigStruct;
use PhoneBurner\Pinch\Framework\MessageBus\EventListener\LogFailedInvokableMessageHandlingAttempt;
use PhoneBurner\Pinch\Framework\MessageBus\EventListener\LogWorkerMessageFailedEvent;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleSignalEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Messenger\Event\SendMessageToTransportsEvent;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageRetriedEvent;
use Symfony\Component\Messenger\Event\WorkerMessageSkipEvent;
use Symfony\Component\Messenger\Event\WorkerRateLimitedEvent;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Event\WorkerStartedEvent;
use Symfony\Component\Messenger\Event\WorkerStoppedEvent;
use Symfony\Component\Scheduler\Event\FailureEvent;
use Symfony\Component\Scheduler\Event\PostRunEvent;
use Symfony\Component\Scheduler\Event\PreRunEvent;

return [
    'event_dispatcher' => new EventDispatcherConfigStruct(
        event_dispatch_log_level: LogLevel::Debug,
        event_failure_log_level: LogLevel::Warning,
        listeners: [
            // Application Lifecycle Events
            ApplicationBootstrap::class => [],
            ApplicationTeardown::class => [],

            // Message Bus Events
            SendMessageToTransportsEvent::class => [],
            WorkerStartedEvent::class => [],
            WorkerRunningEvent::class => [],
            WorkerStoppedEvent::class => [],
            WorkerMessageReceivedEvent::class => [],
            WorkerMessageHandledEvent::class => [],
            WorkerMessageSkipEvent::class => [],
            WorkerMessageFailedEvent::class => [
                LogWorkerMessageFailedEvent::class,
            ],
            WorkerMessageRetriedEvent::class => [],
            WorkerRateLimitedEvent::class => [],

            // Scheduler Events
            PreRunEvent::class => [],
            PostRunEvent::class => [],
            FailureEvent::class => [],

            // Queue Job Events
            InvokableMessageHandlingStarting::class => [],
            InvokableMessageHandlingComplete::class => [],
            InvokableMessageHandlingFailed::class => [
                LogFailedInvokableMessageHandlingAttempt::class,
            ],

            // Console Events
            ConsoleCommandEvent::class => [],
            ConsoleErrorEvent::class => [],
            ConsoleSignalEvent::class => [],
            ConsoleTerminateEvent::class => [],

            // Application Events & Listeners
        ],
    ),
];
