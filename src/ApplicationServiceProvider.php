<?php

declare(strict_types=1);

namespace App;

use PhoneBurner\Pinch\Component\App\App;
use PhoneBurner\Pinch\Component\App\ServiceProvider;
use PhoneBurner\Pinch\Component\Cache\Lock\LockFactory;
use PhoneBurner\Pinch\Framework\App\Config\AppConfigStruct;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @codeCoverageIgnore
 */
class ApplicationServiceProvider implements ServiceProvider
{
    public static function bind(): array
    {
        return [AppConfigStruct::class => ApplicationConfigStruct::class];
    }

    #[\Override]
    public static function register(App $app): void
    {
        $app->set(
            ApplicationConfigStruct::class,
            static fn(App $app): ApplicationConfigStruct => $app->config->get('app'),
        );

        $app->set(
            ApplicationRouteProvider::class,
            static fn(App $app): ApplicationRouteProvider => new ApplicationRouteProvider(),
        );

        $app->set(
            ApplicationScheduleProvider::class,
            static fn(App $app): ApplicationScheduleProvider => new ApplicationScheduleProvider(
                $app->get(CacheItemPoolInterface::class),
                $app->get(LockFactory::class),
                $app->get(EventDispatcher::class),
                $app->get(LoggerInterface::class),
            ),
        );
    }
}
