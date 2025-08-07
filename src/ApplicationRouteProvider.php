<?php

declare(strict_types=1);

namespace App;

use App\Example\Middleware\ExampleRequestAuthenticator;
use PhoneBurner\Pinch\Component\Http\Domain\ContentType;
use PhoneBurner\Pinch\Component\Http\Routing\Definition\RouteDefinition;
use PhoneBurner\Pinch\Component\Http\Routing\Domain\StaticFile;
use PhoneBurner\Pinch\Component\Http\Routing\RouteProvider;
use PhoneBurner\Pinch\Framework\HealthCheck\RequestHandler\HealthCheckRequestHandler;
use PhoneBurner\Pinch\Framework\HealthCheck\RequestHandler\ReadyCheckRequestHandler;
use PhoneBurner\Pinch\Framework\Http\Middleware\RestrictToNonProductionEnvironments;
use PhoneBurner\Pinch\Framework\Http\RequestHandler\CspViolationReportRequestHandler;
use PhoneBurner\Pinch\Framework\Http\RequestHandler\ErrorRequestHandler;
use PhoneBurner\Pinch\Framework\Http\RequestHandler\LogoutRequestHandler;
use PhoneBurner\Pinch\Framework\Http\RequestHandler\PhpInfoRequestHandler;
use PhoneBurner\Pinch\Framework\Http\Session\Middleware\EnableHttpSession;

use function PhoneBurner\Pinch\Framework\path;

/**
 * @codeCoverageIgnore
 */
class ApplicationRouteProvider implements RouteProvider
{
    #[\Override]
    public function __invoke(): array
    {
        return [
            RouteDefinition::file('/', new StaticFile(
                path('/resources/views/welcome.html'),
                ContentType::HTML,
            ))->withMiddleware(EnableHttpSession::class),

            RouteDefinition::all('/logout')
                ->withHandler(LogoutRequestHandler::class)
                ->withMiddleware(EnableHttpSession::class)
                ->withName('logout'),

            RouteDefinition::get('/phpinfo')
                ->withHandler(PhpInfoRequestHandler::class)
                ->withMiddleware(RestrictToNonProductionEnvironments::class),

            RouteDefinition::file('/docs', new StaticFile(
                path('/resources/views/openapi.html'),
                ContentType::HTML,
            ))->withMiddleware(ExampleRequestAuthenticator::class),

            RouteDefinition::file('/openapi.json', new StaticFile(
                path('/resources/views/openapi.json'),
                ContentType::JSON,
            ))->withMiddleware(ExampleRequestAuthenticator::class),

            RouteDefinition::post('/csp')
                ->withHandler(CspViolationReportRequestHandler::class),

            RouteDefinition::get('/errors[/{error}]')
                ->withHandler(ErrorRequestHandler::class),

            RouteDefinition::get(ReadyCheckRequestHandler::DEFAULT_ENDPOINT)
                ->withHandler(ReadyCheckRequestHandler::class),

            RouteDefinition::get(HealthCheckRequestHandler::DEFAULT_ENDPOINT)
                ->withHandler(HealthCheckRequestHandler::class)
                ->withMiddleware(ExampleRequestAuthenticator::class),
        ];
    }
}
