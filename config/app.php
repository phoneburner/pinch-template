<?php

declare(strict_types=1);

use App\ApplicationConfigStruct;
use PhoneBurner\Pinch\Component\Cryptography\Symmetric\SharedKey;

use function PhoneBurner\Pinch\Framework\env;

return [
    'app' => new ApplicationConfigStruct(
        name: 'Pinch Framework',
        key: SharedKey::tryImport((string)env('PINCH_APP_KEY')),
    ),
];
