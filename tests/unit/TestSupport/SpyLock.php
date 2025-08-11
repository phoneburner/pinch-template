<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestSupport;

use PhoneBurner\Pinch\Component\Cache\Lock\Lock;
use PhoneBurner\Pinch\Component\Cache\Lock\SharedLockMode;
use PhoneBurner\Pinch\Time\Interval\TimeInterval;

class SpyLock implements Lock
{
    public function __construct(
        public TimeInterval|null $ttl = null,
        public bool $acquire = true,
        public bool $acquired = true,
        public bool $released = false,
        public bool $refreshed = false,
    ) {
    }

    #[\Override]
    public function acquire(
        bool $blocking = false,
        int $timeout_seconds = 30,
        int $delay_microseconds = 25000,
        SharedLockMode $mode = SharedLockMode::Write,
    ): bool {
        return $this->acquire;
    }

    #[\Override]
    public function release(): void
    {
        $this->released = true;
    }

    #[\Override]
    public function refresh(TimeInterval|null $ttl = null): void
    {
        $this->refreshed = true;
    }

    #[\Override]
    public function acquired(): bool
    {
        return $this->acquired;
    }

    #[\Override]
    public function ttl(): TimeInterval|null
    {
        return $this->ttl;
    }
}
