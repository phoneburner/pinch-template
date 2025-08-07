<?php

declare(strict_types=1);

use PhoneBurner\Pinch\Component\Cryptography\Symmetric\SharedKey;
use PhoneBurner\Pinch\Component\I18n\IsoLocale;
use PhoneBurner\Pinch\Framework\App\Config\AppConfigStruct;
use PhoneBurner\Pinch\Time\TimeZone\Tz;

use function PhoneBurner\Pinch\Framework\env;

return [
    'app' => new AppConfigStruct(
        name: 'Pinch Framework',
        key: SharedKey::tryImport((string)env('PINCH_APP_KEY')),
        timezone: Tz::Utc,
        locale: IsoLocale::EN_US,
    ),
];
