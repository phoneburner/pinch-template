<?php

declare(strict_types=1);

namespace App\Tests\Behat;

use App\Tests\Unit\TestSupport\ForceSyncTransportFactory;
use App\Tests\Unit\TestSupport\MockEmitter;
use App\Tests\Unit\TestSupport\MockEventDispatcher;
use Behat\Hook\AfterScenario;
use Behat\Hook\BeforeScenario;
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
use PHPUnit\Framework\Assert;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait HasApplicationLifecycle
{
    private ServiceContainer|null $services = null;

    private ResponseInterface|null $response = null;

    #[BeforeScenario]
    public function bootApp(): void
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

    #[AfterScenario]
    public function teardownApplication(): void
    {
        $this->services?->get(Connection::class)->rollBack();
        $this->services = null;
        App::teardown();
    }

    protected function container(): ServiceContainer
    {
        return $this->services ?? throw new \RuntimeException('Container not initialized');
    }

    protected function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->container()->get(HttpKernel::class)->run($request);
        $emitter = $this->container()->get(EmitterInterface::class);

        Assert::assertInstanceOf(MockEmitter::class, $emitter);
        Assert::assertInstanceOf(ResponseInterface::class, $emitter->response);

        return $emitter->response;
    }

    protected function response(): ResponseInterface
    {
        return $this->response ?? throw new \RuntimeException('Response not initialized');
    }
}
