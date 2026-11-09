<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class UntrustedValueIntTest extends TestCase
{
    /**
     * @return iterable<string, array{int}>
     */
    public static function ints(): iterable
    {
        yield 'zero' => [0];
        yield 'positive' => [42];
        yield 'negative' => [-7];
        yield 'max' => [PHP_INT_MAX];
        yield 'min' => [PHP_INT_MIN];
    }

    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function convertible(): iterable
    {
        yield 'string zero' => ['0', 0];
        yield 'string positive' => ['42', 42];
        yield 'string negative' => ['-7', -7];
        yield 'string max' => [(string) PHP_INT_MAX, PHP_INT_MAX];
        yield 'string min' => [(string) PHP_INT_MIN, PHP_INT_MIN];
        yield 'string plus sign' => ['+42', 42];
        yield 'string leading zero' => ['042', 42];
        yield 'string minus zero' => ['-0', 0];
        yield 'string leading whitespace' => [' 42', 42];
        yield 'string trailing whitespace' => ['42 ', 42];
        yield 'string exponent' => ['1e3', 1000];
        yield 'string whole decimal' => ['42.0', 42];
        yield 'float whole' => [42.0, 42];
        yield 'float just above whole' => [42.0009, 42];
        yield 'float negative just below whole' => [-42.0009, -42];
        yield 'float negative zero' => [-0.0, 0];
        yield 'float min' => [(float) PHP_INT_MIN, PHP_INT_MIN];
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function notConvertible(): iterable
    {
        yield 'bool' => [true, 'bool'];
        yield 'array' => [[1], 'array'];
        yield 'object' => [new \stdClass(), 'stdClass'];
        yield 'empty string' => ['', 'non-numeric-string'];
        yield 'text' => ['abc', 'non-numeric-string'];
        yield 'hex string' => ['0x1A', 'non-numeric-string'];
        yield 'decimal string' => ['4.2', 'fractional-numeric-string'];
        yield 'string overflow' => ['9223372036854775808', 'out-of-range-numeric-string'];
        yield 'string negative overflow' => ['-1e19', 'out-of-range-numeric-string'];
        yield 'string infinite' => ['1e999', 'non-finite-numeric-string'];
        yield 'float fraction' => [4.2, 'fractional-float'];
        yield 'float just below whole' => [41.9999, 'fractional-float'];
        yield 'float above tolerance' => [42.002, 'fractional-float'];
        yield 'float overflow' => [-(float) PHP_INT_MIN, 'out-of-range-float'];
        yield 'float far overflow' => [1e19, 'out-of-range-float'];
        yield 'float negative overflow' => [-1e19, 'out-of-range-float'];
        yield 'float nan' => [NAN, 'non-finite-float'];
        yield 'float inf' => [INF, 'non-finite-float'];
        yield 'float -inf' => [-INF, 'non-finite-float'];
    }

    #[DataProvider('ints')]
    public function testReturnsInts(int $value): void
    {
        self::assertSame($value, new UntrustedValue($value)->int());
        self::assertSame($value, new UntrustedValue($value)->int(strict: true));
    }

    #[DataProvider('convertible')]
    public function testConvertsWhenNotStrict(mixed $value, int $expected): void
    {
        self::assertSame($expected, new UntrustedValue($value)->int());
    }

    #[DataProvider('convertible')]
    public function testRejectsConversionWhenStrict(mixed $value, int $nonStrictResult): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedValue($value)->int(strict: true);
    }

    #[DataProvider('notConvertible')]
    public function testRejectsOtherValues(mixed $value, string $givenType): void
    {
        try {
            new UntrustedValue($value, 'user.age')->int();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('int', $exception->expectedType);
            self::assertSame($givenType, $exception->givenType);
            self::assertSame('user.age', $exception->path);
            self::assertSame("Expected int at user.age, got {$givenType}", $exception->getMessage());
        }
    }

    public function testNullIsAllowedByDefault(): void
    {
        self::assertNull(new UntrustedValue(null)->int());
        self::assertNull(new UntrustedValue(null)->int(strict: true));
    }

    public function testNullIsRejectedWhenNotNullable(): void
    {
        try {
            new UntrustedValue(null)->int(nullable: false);
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('null', $exception->givenType);
            self::assertSame('', $exception->path);
            self::assertSame('Expected int, got null', $exception->getMessage());
        }
    }
}
