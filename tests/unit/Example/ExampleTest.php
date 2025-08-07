<?php

declare(strict_types=1);

namespace App\Tests\Unit\Example;

use App\Tests\Unit\TestSupport\HasApplicationLifecycle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    use HasApplicationLifecycle;

    #[Test]
    #[DataProvider('providesExampleHappyPath')]
    public function exampleHappyPath(bool $expected, int $value): void
    {
        self::assertSame($expected, (bool)$value);
    }

    public static function providesExampleHappyPath(): \Generator
    {
        yield [true, 1];
        yield [false, 0];
    }
}
