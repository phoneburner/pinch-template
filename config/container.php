<?php

declare(strict_types=1);

use App\ApplicationServiceProvider;
use PhoneBurner\Pinch\Framework\Container\Config\ContainerConfigStruct;

return [
    'container' => new ContainerConfigStruct(
        enable_deferred_service_registration: true,
        service_providers: [
            ApplicationServiceProvider::class,
        ],
    ),
];
