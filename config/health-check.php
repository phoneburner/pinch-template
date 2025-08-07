<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Framework\HealthCheck\ComponentHealthChecks\MySqlHealthCheckService;
use PhoneBurner\Pinch\Framework\HealthCheck\ComponentHealthChecks\PhpRuntimeHealthCheckService;
use PhoneBurner\Pinch\Framework\HealthCheck\ComponentHealthChecks\RedisHealthCheckService;
use PhoneBurner\Pinch\Framework\HealthCheck\Config\HealthCheckConfigStruct;

return [
    'health_check' => new HealthCheckConfigStruct(
        services: [
            PhpRuntimeHealthCheckService::class,
            MySqlHealthCheckService::class,
            RedisHealthCheckService::class,
        ],
    ),
];
