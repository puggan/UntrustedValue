<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class UntrustedValueStringTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function strings(): iterable
    {
        yield 'empty' => [''];
        yield 'text' => ['hello'];
        yield 'numeric' => ['42'];
        yield 'whitespace' => [" \t\n"];
        yield 'unicode' => ['Thorbjörn'];
    }

    /**
     * @return iterable<string, array{int|float, string}>
     */
    public static function convertible(): iterable
    {
        yield 'int' => [42, '42'];
        yield 'int negative' => [-7, '-7'];
        yield 'int zero' => [0, '0'];
        yield 'float' => [4.2, '4.2'];
        yield 'float whole' => [42.0, '42'];
        yield 'float negative zero' => [-0.0, '-0'];
        yield 'float large' => [1e25, '1.0E+25'];
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function notConvertible(): iterable
    {
        yield 'true' => [true, 'bool'];
        yield 'false' => [false, 'bool'];
        yield 'array' => [['a'], 'array'];
        yield 'object' => [new \stdClass(), 'stdClass'];
        yield 'float nan' => [NAN, 'non-finite-float'];
        yield 'float inf' => [INF, 'non-finite-float'];
        yield 'float -inf' => [-INF, 'non-finite-float'];
    }

    /**
     * @dataProvider strings
     */
    public function testReturnsStrings(string $value): void
    {
        self::assertSame($value, (new UntrustedValue($value))->string());
        self::assertSame($value, (new UntrustedStrictValue($value))->string());
    }

    /**
     * @dataProvider convertible
     */
    public function testConvertsWhenNotStrict(int|float $value, string $expected): void
    {
        self::assertSame($expected, (new UntrustedValue($value))->string());
    }

    /**
     * @dataProvider convertible
     */
    public function testRejectsConversionWhenStrict(int|float $value, string $nonStrictResult): void
    {
        $this->expectException(WrongTypeException::class);
        (new UntrustedStrictValue($value))->string();
    }

    /**
     * @dataProvider notConvertible
     */
    public function testRejectsOtherValues(mixed $value, string $givenType): void
    {
        try {
            (new UntrustedValue($value, 'user.name'))->string();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('string', $exception->expectedType);
            self::assertSame($givenType, $exception->givenType);
            self::assertSame('user.name', $exception->path);
            self::assertSame("Expected string at user.name, got {$givenType}", $exception->getMessage());
        }
    }

    public function testNullIsAllowedByDefault(): void
    {
        self::assertNull(UntrustedValue::fromJson('null')->string());
        self::assertNull(UntrustedStrictValue::fromJson('null')->string());
    }

    public function testNullIsRejectedWhenNotNullable(): void
    {
        try {
            (new UntrustedValue(null))->string(nullable: false);
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('null', $exception->givenType);
            self::assertSame('Expected string, got null', $exception->getMessage());
        }
    }
}
