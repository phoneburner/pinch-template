<?php

declare(strict_types=1);

namespace App\Tests\Unit\TestSupport;

use Doctrine\DBAL\Connection;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PhoneBurner\Pinch\Component\App\ServiceContainer;
use PhoneBurner\Pinch\Component\Configuration\Context;
use PhoneBurner\Pinch\Component\Logging\LogTrace;
use PhoneBurner\Pinch\Framework\App\App;
use PhoneBurner\Pinch\Framework\Configuration\EnvironmentLoader;
use PhoneBurner\Pinch\Framework\Http\HttpKernel;
use PhoneBurner\Pinch\Framework\MessageBus\Container\MessageBusContainer;
use PhoneBurner\Pinch\Framework\MessageBus\TransportFactory;
use PhoneBurner\Pinch\Uuid\Uuid;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @phpstan-require-extends TestCase
 */
trait HasApplicationLifecycle
{
    protected ServiceContainer|null $services = null;

    #[Before]
    protected function bootstrapApplication(): void
    {
        $environment = EnvironmentLoader::instance();
        if ($environment->context !== Context::Test) {
            throw new \LogicException('This trait is only for use in test contexts.');
        }

        $this->services = App::bootstrap($environment)->services;
        $this->services->get(Connection::class)->beginTransaction();
        $this->services->set(LogTrace::class, new LogTrace(Uuid::nil()));
        $this->services->set(EmitterInterface::class, new MockEmitter());
        $this->services->set(EventDispatcherInterface::class, new MockEventDispatcher());
        $this->services->set(
            TransportFactory::class,
            static fn(App $app): TransportFactory => new ForceSyncTransportFactory(
                $app->get(MessageBusContainer::class),
            ),
        );
    }

    #[After]
    protected function teardownApplication(): void
    {
        $this->services?->get(Connection::class)->rollBack();
        $this->services = null;
        App::teardown();
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->container()->get(HttpKernel::class)->run($request);
        $emitter = $this->container()->get(EmitterInterface::class);

        self::assertInstanceOf(MockEmitter::class, $emitter);
        self::assertInstanceOf(ResponseInterface::class, $emitter->response);

        return $emitter->response;
    }

    protected function container(): ServiceContainer
    {
        return $this->services ?? throw new \RuntimeException('Container not initialized');
    }
}
