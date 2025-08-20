<?php

declare(strict_types=1);

namespace App;

use PhoneBurner\Pinch\Component\Configuration\Struct\ConfigStructArrayAccess;
use PhoneBurner\Pinch\Component\Cryptography\Asymmetric\AsymmetricAlgorithm;
use PhoneBurner\Pinch\Component\Cryptography\Symmetric\SharedKey;
use PhoneBurner\Pinch\Component\Cryptography\Symmetric\SymmetricAlgorithm;
use PhoneBurner\Pinch\Component\I18n\IsoLocale;
use PhoneBurner\Pinch\Framework\App\Config\AppConfigStruct;
use PhoneBurner\Pinch\Framework\App\ErrorHandling\ErrorHandler;
use PhoneBurner\Pinch\Framework\App\ErrorHandling\ExceptionHandler;
use PhoneBurner\Pinch\Framework\App\ErrorHandling\NullErrorHandler;
use PhoneBurner\Pinch\Framework\App\ErrorHandling\NullExceptionHandler;
use PhoneBurner\Pinch\Time\TimeZone\Tz;

class ApplicationConfigStruct implements AppConfigStruct
{
    use ConfigStructArrayAccess;

    /**
     * @param class-string<ErrorHandler> $uncaught_error_handler
     * @param class-string<ExceptionHandler> $uncaught_exception_handler
     */
    public function __construct(
        public string $name,
        #[\SensitiveParameter] public SharedKey|null $key,
        public Tz $timezone = Tz::Utc,
        public IsoLocale $locale = IsoLocale::EN_US,
        public SymmetricAlgorithm $symmetric_algorithm = SymmetricAlgorithm::Aegis256,
        public AsymmetricAlgorithm $asymmetric_algorithm = AsymmetricAlgorithm::X25519Aegis256,
        public string $uncaught_error_handler = NullErrorHandler::class,
        public string $uncaught_exception_handler = NullExceptionHandler::class,
    ) {
    }

    public function __serialize(): array
    {
        return [
            $this->name,
            $this->key?->export(),
            $this->timezone,
            $this->locale,
            $this->symmetric_algorithm,
            $this->asymmetric_algorithm,
            $this->uncaught_error_handler,
            $this->uncaught_exception_handler,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->__construct(
            $data[0],
            $data[1] ? SharedKey::import($data[1]) : null,
            $data[2],
            $data[3],
            $data[4],
            $data[5],
            $data[6],
            $data[7],
        );
    }
}
