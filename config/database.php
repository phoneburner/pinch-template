<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Component\Cache\CacheDriver;
use PhoneBurner\Pinch\Framework\Database\Config\AmpqConfigStruct;
use PhoneBurner\Pinch\Framework\Database\Config\AmpqConnectionConfigStruct;
use PhoneBurner\Pinch\Framework\Database\Config\DatabaseConfigStruct;
use PhoneBurner\Pinch\Framework\Database\Config\DoctrineConfigStruct;
use PhoneBurner\Pinch\Framework\Database\Config\DoctrineConnectionConfigStruct;
use PhoneBurner\Pinch\Framework\Database\Config\DoctrineEntityManagerConfigStruct;
use PhoneBurner\Pinch\Framework\Database\Config\DoctrineMigrationsConfigStruct;
use PhoneBurner\Pinch\Framework\Database\Config\RedisConfigStruct;
use PhoneBurner\Pinch\Framework\Database\Config\RedisConnectionConfigStruct;

use function PhoneBurner\Pinch\Framework\env;
use function PhoneBurner\Pinch\Framework\path;

return [
    'database' => new DatabaseConfigStruct(
        ampq: new AmpqConfigStruct(
            connections: [
                'default' => new AmpqConnectionConfigStruct(
                    host: (string)env('PINCH_RABBITMQ_HOST', 'rabbitmq'),
                    port: (int)env('PINCH_RABBITMQ_PORT', 5672),
                    user: (string)env('PINCH_RABBITMQ_USER', 'guest'),
                    password: (string)env('PINCH_RABBITMQ_PASS', 'guest'),
                ),
            ],
        ),
        redis: new RedisConfigStruct(
            connections: [
                'default' => new RedisConnectionConfigStruct(
                    host: (string)env('PINCH_REDIS_HOST', 'redis'),
                    port: (int)env('PINCH_REDIS_PORT', 6379),
                ),
            ],
            timeout: 5,
        ),
        doctrine: new DoctrineConfigStruct(
            connections: [
                'default' => new DoctrineConnectionConfigStruct(
                    host: (string)env('PINCH_MYSQL_HOST', 'mysql'),
                    port: (int)env('PINCH_MYSQL_PORT', 3306),
                    dbname: (string)env('PINCH_MYSQL_NAME', 'pinch'),
                    user: (string)env('PINCH_MYSQL_USER', 'pinch'),
                    password: (string)env('PINCH_MYSQL_PASS', 'pinch'),
                    entity_manager: new DoctrineEntityManagerConfigStruct(
                        entity_paths: [path('/src/')],
                        cache_path: path('/storage/doctrine/default/'),
                        metadata_cache_driver: CacheDriver::instance(env('PINCH_DOCTRINE_METADATA_CACHE_DRIVER', CacheDriver::File, CacheDriver::Memory)),
                        query_cache_driver: CacheDriver::instance(env('PINCH_DOCTRINE_QUERY_CACHE_DRIVER', CacheDriver::File, CacheDriver::Memory)),
                        result_cache_driver: CacheDriver::instance(env('PINCH_DOCTRINE_RESULT_CACHE_DRIVER', CacheDriver::Remote, CacheDriver::Memory)),
                        entity_cache_driver: CacheDriver::instance(env('PINCH_DOCTRINE_ENTITY_CACHE_DRIVER', CacheDriver::Remote, CacheDriver::Memory)),
                        event_subscribers: [],
                        mapped_field_types: [],
                    ),
                    migrations: new DoctrineMigrationsConfigStruct(
                        table_storage: [
                            'table_name' => 'doctrine_migration_versions',
                        ],
                        migrations_paths: [
                            'PhoneBurner\Pinch\Migrations' => path('/database/migrations'),
                        ],
                    ),
                    server_version: env('PINCH_MYSQL_SERVER_VERSION', '8.0.36'),
                    enable_logging: env('PINCH_DOCTRINE_ENABLE_LOGGING', false),
                ),
            ],
            types: [],
        ),
    ),
];
