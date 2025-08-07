<?php

declare(strict_types=1);

namespace App\Example\Event;

final readonly class ExampleEvent
{
    public function __construct(public string $message)
    {
    }
}
