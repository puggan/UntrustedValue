<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

final class UntrustedValueIsTypeTest extends TestCase
{
    /**
     * Value, then the expected isInt(), isFloat() and isString() results, non-strict then strict.
     *
     * @return iterable<string, array{mixed, array{bool, bool}, array{bool, bool}, array{bool, bool}}>
     */
    public static function values(): iterable
    {
        yield 'int' => [42, [true, true], [true, true], [true, false]];
        yield 'float whole' => [42.0, [true, false], [true, true], [true, false]];
        yield 'float fraction' => [4.2, [false, false], [true, true], [true, false]];
        yield 'float inf' => [INF, [false, false], [true, true], [false, false]];
        yield 'numeric string' => ['42', [true, false], [true, false], [true, true]];
        yield 'decimal string' => ['4.2', [false, false], [true, false], [true, true]];
        yield 'text' => ['abc', [false, false], [false, false], [true, true]];
        yield 'bool' => [true, [false, false], [false, false], [false, false]];
        yield 'array' => [[1], [false, false], [false, false], [false, false]];
        yield 'object' => [new \stdClass(), [false, false], [false, false], [false, false]];
    }

    /**
     * @param array{bool, bool} $int
     * @param array{bool, bool} $float
     * @param array{bool, bool} $string
     */
    #[DataProvider('values')]
    public function testMatchesTheConversionMethods(mixed $value, array $int, array $float, array $string): void
    {
        $untrusted = new UntrustedValue($value);
        $strict = new UntrustedStrictValue($value);

        self::assertSame($int, [$untrusted->isInt(), $strict->isInt()]);
        self::assertSame($float, [$untrusted->isFloat(), $strict->isFloat()]);
        self::assertSame($string, [$untrusted->isString(), $strict->isString()]);
    }

    public function testNullIsAcceptedByDefault(): void
    {
        $null = new UntrustedValue(null);

        self::assertTrue($null->isInt());
        self::assertTrue($null->isFloat());
        self::assertTrue($null->isString());
    }

    public function testNullIsRejectedWhenNotNullable(): void
    {
        $null = new UntrustedValue(null);

        self::assertFalse($null->isInt(nullable: false));
        self::assertFalse($null->isFloat(nullable: false));
        self::assertFalse($null->isString(nullable: false));
    }
}
