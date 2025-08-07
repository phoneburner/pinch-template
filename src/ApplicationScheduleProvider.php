<?php

declare(strict_types=1);

namespace App;

use App\Example\Message\ExampleMessage;
use PhoneBurner\Pinch\Component\Cache\CacheKey;
use PhoneBurner\Pinch\Component\Cache\Lock\LockFactory;
use PhoneBurner\Pinch\Framework\MessageBus\Transport;
use PhoneBurner\Pinch\Time\TimeInterval\TimeInterval;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ProxyAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Lock\LockInterface;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Event\FailureEvent;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

use function PhoneBurner\Pinch\Type\narrow;

/**
 * @codeCoverageIgnore
 */
#[AsSchedule('app')]
class ApplicationScheduleProvider implements ScheduleProviderInterface
{
    private Schedule|null $schedule = null;

    public static function getName(): string
    {
        return 'default';
    }

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LockFactory $lock_factory,
        private readonly EventDispatcher $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function getSchedule(): Schedule
    {
        return $this->schedule ??= $this->configure();
    }

    private function configure(): Schedule
    {
        $key = CacheKey::make('scheduler', self::getName());

        return new Schedule($this->dispatcher)->with(
            RecurringMessage::cron('@daily', new RedispatchMessage(
                new ExampleMessage('Scheduled Message'),
                Transport::ASYNC,
            )),
        )->lock(narrow(LockInterface::class, $this->lock_factory->make($key, new TimeInterval(seconds: 60))))
        ->stateful(new ProxyAdapter($this->cache))
        ->processOnlyLastMissedRun(true)
        ->onFailure(function (FailureEvent $event): void {
            $this->logger->error('Failed to dispatch scheduled message "{message}"', [
                'message' => $event->getMessage(),
                'exception' => $event->getError(),
            ]);
        });
    }
}
