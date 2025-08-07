<?php

declare(strict_types=1);

namespace App\Example\Message;

use PhoneBurner\Pinch\Component\MessageBus\Message\InvokableMessage;
use Psr\Log\LoggerInterface;

class ExampleMessage implements InvokableMessage
{
    public function __construct(public readonly string $message = 'Example Job Message')
    {
    }

    public function getMessage(): string
    {
        return $this->message . ' ' . \date('Y-m-d H:i:s');
    }

    public function __invoke(LoggerInterface $logger): void
    {
        $logger->notice($this->getMessage());
    }
}
