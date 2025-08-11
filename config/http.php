<?php

declare(strict_types=1);

use App\ApplicationRouteProvider;
use PhoneBurner\Pinch\Component\Http\Response\Exceptional\TransformerStrategies\JsonResponseTransformerStrategy;
use PhoneBurner\Pinch\Component\Http\Routing\RequestHandler\NotFoundRequestHandler;
use PhoneBurner\Pinch\Framework\Http\Config\HttpConfigStruct;
use PhoneBurner\Pinch\Framework\Http\Config\RoutingConfigStruct;
use PhoneBurner\Pinch\Framework\Http\Config\SessionConfigStruct;
use PhoneBurner\Pinch\Framework\Http\Cookie\Middleware\ManageCookies;
use PhoneBurner\Pinch\Framework\Http\Middleware\CatchExceptionalResponses;
use PhoneBurner\Pinch\Framework\Http\Middleware\EvaluateWrappedResponseFactories;
use PhoneBurner\Pinch\Framework\Http\Middleware\TransformHttpExceptionResponses;
use PhoneBurner\Pinch\Framework\Http\Routing\Middleware\AttachRouteToRequest;
use PhoneBurner\Pinch\Framework\Http\Routing\Middleware\DispatchRouteMiddleware;
use PhoneBurner\Pinch\Framework\Http\Routing\Middleware\DispatchRouteRequestHandler;
use PhoneBurner\Pinch\Framework\Http\Session\SessionHandlerType;
use PhoneBurner\Pinch\Time\Interval\TimeInterval;

use function PhoneBurner\Pinch\Framework\env;
use function PhoneBurner\Pinch\Framework\path;

return [
    'http' => new HttpConfigStruct(
        exceptional_response_default_transformer: JsonResponseTransformerStrategy::class,
        logout_redirect_url: '/',
        routing: new RoutingConfigStruct(
            enable_cache: (bool)env('PINCH_ENABLE_ROUTE_CACHE', true, false),
            cache_path: path('/storage/bootstrap/routes.cache.php'),
            route_providers: [
                ApplicationRouteProvider::class,
            ],
            fallback_handler: NotFoundRequestHandler::class,
        ),
        session: new SessionConfigStruct(
            SessionHandlerType::Redis,
            new TimeInterval(hours: 1),
            lock_sessions: true,
            encrypt: false,
            compress: false,
            encoding: null,
            add_xsrf_token_cookie: true,
        ),
        middleware: [
            TransformHttpExceptionResponses::class,
            CatchExceptionalResponses::class,
            ManageCookies::class,
            EvaluateWrappedResponseFactories::class,
            AttachRouteToRequest::class,
            DispatchRouteMiddleware::class,
            DispatchRouteRequestHandler::class,
        ],
    ),
];
