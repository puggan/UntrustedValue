<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class UntrustedValueFloatTest extends TestCase
{
    /**
     * @return iterable<string, array{int|float, float}>
     */
    public static function numbers(): iterable
    {
        yield 'float' => [4.2, 4.2];
        yield 'float negative' => [-4.2, -4.2];
        yield 'float zero' => [0.0, 0.0];
        yield 'float inf' => [INF, INF];
        yield 'int' => [42, 42.0];
        yield 'int negative' => [-7, -7.0];
        yield 'int max' => [PHP_INT_MAX, (float) PHP_INT_MAX];
    }

    /**
     * @return iterable<string, array{string, float}>
     */
    public static function numericStrings(): iterable
    {
        yield 'decimal' => ['4.2', 4.2];
        yield 'integer' => ['42', 42.0];
        yield 'negative' => ['-4.2', -4.2];
        yield 'plus sign' => ['+4.2', 4.2];
        yield 'leading dot' => ['.5', 0.5];
        yield 'leading whitespace' => [' 4.2', 4.2];
        yield 'trailing whitespace' => ['4.2 ', 4.2];
        yield 'exponent' => ['1e3', 1000.0];
        yield 'negative exponent' => ['1e-3', 0.001];
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function notConvertible(): iterable
    {
        yield 'bool' => [true, 'bool'];
        yield 'array' => [[1.5], 'array'];
        yield 'object' => [new \stdClass(), 'stdClass'];
        yield 'empty string' => ['', 'non-numeric-string'];
        yield 'text' => ['abc', 'non-numeric-string'];
        yield 'hex string' => ['0x1A', 'non-numeric-string'];
        yield 'comma decimal' => ['4,2', 'non-numeric-string'];
        yield 'string infinite' => ['1e999', 'non-finite-numeric-string'];
        yield 'string negative infinite' => ['-1e999', 'non-finite-numeric-string'];
    }

    #[DataProvider('numbers')]
    public function testReturnsNumbers(int|float $value, float $expected): void
    {
        self::assertSame($expected, new UntrustedValue($value)->float());
        self::assertSame($expected, new UntrustedStrictValue($value)->float());
    }

    public function testReturnsNan(): void
    {
        self::assertNan(new UntrustedStrictValue(NAN)->float());
    }

    #[DataProvider('numericStrings')]
    public function testConvertsWhenNotStrict(string $value, float $expected): void
    {
        self::assertSame($expected, new UntrustedValue($value)->float());
    }

    #[DataProvider('numericStrings')]
    public function testRejectsConversionWhenStrict(string $value, float $nonStrictResult): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedStrictValue($value)->float();
    }

    #[DataProvider('notConvertible')]
    public function testRejectsOtherValues(mixed $value, string $givenType): void
    {
        try {
            new UntrustedValue($value, 'item.price')->float();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('float', $exception->expectedType);
            self::assertSame($givenType, $exception->givenType);
            self::assertSame('item.price', $exception->path);
            self::assertSame("Expected float at item.price, got {$givenType}", $exception->getMessage());
        }
    }

    public function testNullIsAllowedByDefault(): void
    {
        self::assertNull(UntrustedValue::fromJson('null')->float());
        self::assertNull(UntrustedStrictValue::fromJson('null')->float());
    }

    public function testNullIsRejectedWhenNotNullable(): void
    {
        try {
            new UntrustedValue(null)->float(nullable: false);
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('null', $exception->givenType);
            self::assertSame('Expected float, got null', $exception->getMessage());
        }
    }
}
