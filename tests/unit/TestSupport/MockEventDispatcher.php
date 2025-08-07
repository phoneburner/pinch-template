<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestSupport;

use Psr\EventDispatcher\EventDispatcherInterface;

class MockEventDispatcher implements EventDispatcherInterface
{
    public function __construct(public array $dispatched = [])
    {
    }

    #[\Override]
    public function dispatch(object $event): object
    {
        return $this->dispatched[] = $event;
    }
}
