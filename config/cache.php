<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Component\Cache\CacheDriver;
use PhoneBurner\Pinch\String\Serialization\Serializer;
use Symfony\Component\Lock\Store\RedisStore;

use function PhoneBurner\Pinch\Framework\env;

return [
    'cache' => [
        'lock' => [
            'store_driver' => RedisStore::class,
        ],
        'drivers' => [
            CacheDriver::Remote->value => [
                'serializer' => env('PINCH_REMOTE_CACHE_SERIALIZER', Serializer::Igbinary, Serializer::Php),
            ],
            CacheDriver::File->value => [

            ],
            CacheDriver::Memory->value => [

            ],
        ],
    ],
];
