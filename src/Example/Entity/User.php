<?php

declare(strict_types=1);

namespace App\Example\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use PhoneBurner\Pinch\Framework\Database\Doctrine\Orm\GeneratedValueStrategy;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    #[ORM\GeneratedValue(strategy: GeneratedValueStrategy::IDENTITY)]
    public readonly int $id;

    public function __construct(
        #[ORM\Column(type: Types::STRING, length: 255, nullable: false)]
        public string $username,
        #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: false)]
        public \DateTimeImmutable $date_added = new \DateTimeImmutable(),
    ) {
    }
}
