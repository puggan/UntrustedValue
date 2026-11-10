<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

final class RangeTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, int|float|string|null, int|float|string|null}>
     */
    public static function inRange(): iterable
    {
        yield 'between' => [3, 0, 5];
        yield 'at min' => [0, 0, 5];
        yield 'at max' => [5, 0, 5];
        yield 'no bounds' => [-100, null, null];
        yield 'only min' => [100, 0, null];
        yield 'only max' => [-100, null, 5];
        yield 'float' => [0.5, 0, 1];
        yield 'float bounds' => [1, 0.5, 1.5];
        yield 'string' => ['m', 'a', 'z'];
        yield 'null' => [null, 1, 5];
        yield 'null below a negative max' => [null, null, -1];
    }

    #[DataProvider('inRange')]
    public function testReturnsThisInRange(mixed $value, int|float|string|null $min, int|float|string|null $max): void
    {
        $untrusted = new UntrustedStrictValue($value);

        self::assertSame($untrusted, $untrusted->range($min, $max));
    }

    public function testBelowMinThrows(): void
    {
        try {
            new UntrustedValue(-1, 'user.age')->range(0, 150);
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('min', $exception->filter);
            self::assertSame('user.age', $exception->path);
            self::assertSame('Expected at least 0 at user.age', $exception->getMessage());
        }
    }

    public function testAboveMaxThrows(): void
    {
        try {
            new UntrustedValue(151, 'user.age')->range(0, 150);
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('max', $exception->filter);
            self::assertSame('user.age', $exception->path);
            self::assertSame('Expected at most 150 at user.age', $exception->getMessage());
        }
    }

    public function testMessageAtTheRoot(): void
    {
        $this->expectExceptionMessage('Expected at most 1.5');
        new UntrustedValue(2)->range(max: 1.5);
    }

    public function testChainsAfterAConversion(): void
    {
        self::assertSame(42, UntrustedValue::fromJson('"42"')->asInt(false)->range(0, 150)->int());
    }
}
