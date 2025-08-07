<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestSupport;

use PhoneBurner\Pinch\Component\Configuration\ConfigStruct;
use PhoneBurner\Pinch\Component\MessageBus\MessageBus;
use PhoneBurner\Pinch\Framework\MessageBus\Container\MessageBusContainer;
use PhoneBurner\Pinch\Framework\MessageBus\TransportFactory;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Transport\Sync\SyncTransport;
use Symfony\Component\Messenger\Transport\TransportFactory as SymfonyTransportFactory;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function PhoneBurner\Pinch\Type\narrow;

class ForceSyncTransportFactory extends SymfonyTransportFactory implements TransportFactory
{
    public function __construct(private readonly MessageBusContainer $message_bus_locator)
    {
    }

    #[\Override]
    public function make(ConfigStruct|array $config): TransportInterface
    {
        return new SyncTransport(
            narrow(MessageBusInterface::class, $this->message_bus_locator->get($config['bus'] ?? MessageBus::DEFAULT)),
        );
    }
}
