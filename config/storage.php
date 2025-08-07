<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Framework\Storage\Config\LocalFilesystemConfigStruct;
use PhoneBurner\Pinch\Framework\Storage\Config\S3FilesystemConfigStruct;
use PhoneBurner\Pinch\Framework\Storage\Config\StorageConfigStruct;
use PhoneBurner\Pinch\Framework\Storage\StorageDriver;

use function PhoneBurner\Pinch\Framework\env;
use function PhoneBurner\Pinch\Framework\path;

return [
    'storage' => new StorageConfigStruct(
        default: env('PINCH_DEFAULT_STORAGE_ADAPTER', StorageDriver::LOCAL),
        drivers: [
            StorageDriver::LOCAL => new LocalFilesystemConfigStruct(path('/storage/app')),
            StorageDriver::S3 => new S3FilesystemConfigStruct(
                client: [
                    'credentials' => [
                        'key' => (string)env('PINCH_AWS_S3_ACCESS_KEY_ID'),
                        'secret' => (string)env('PINCH_AWS_S3_SECRET_ACCESS_KEY'),
                    ],
                    'region' => (string)env('PINCH_AWS_S3_DEFAULT_REGION', 'us-west-1'),
                    'signature' => 'v4',
                    'version' => 'latest',
                ],
                bucket_name: (string)env('PINCH_AWS_S3_BUCKET_NAME'),
                prefix: (string)env('PINCH_AWS_S3_PATH_PREFIX'),
            ),
        ],
    ),
];
