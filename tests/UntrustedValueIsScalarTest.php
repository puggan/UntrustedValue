<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;

final class UntrustedValueIsScalarTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function values(): iterable
    {
        yield 'int' => [42, true];
        yield 'float' => [4.2, true];
        yield 'string' => ['abc', true];
        yield 'empty string' => ['', true];
        yield 'bool' => [false, true];
        yield 'array' => [[1], false];
        yield 'empty array' => [[], false];
        yield 'object' => [new \stdClass(), false];
    }

    #[DataProvider('values')]
    public function testIsScalar(mixed $value, bool $expected): void
    {
        self::assertSame($expected, new UntrustedValue($value)->isScalar());
        self::assertSame($expected, new UntrustedValue($value)->isScalar(nullable: false));
    }

    #[DataProvider('values')]
    public function testIsNotNull(mixed $value, bool $isScalar): void
    {
        self::assertFalse(new UntrustedValue($value)->isNull());
    }

    public function testNull(): void
    {
        $null = new UntrustedValue(null);

        self::assertTrue($null->isNull());
        self::assertTrue($null->isScalar());
        self::assertFalse($null->isScalar(nullable: false));
    }
}
