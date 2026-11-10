<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;

final class IsIterableTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function values(): iterable
    {
        yield 'empty array' => [[], true];
        yield 'list' => [[1, 2], true];
        yield 'map' => [['a' => 1], true];
        yield 'ArrayIterator' => [new \ArrayIterator([1]), true];
        yield 'generator' => [(static fn(): \Generator => yield 1)(), true];
        yield 'untrusted value' => [new UntrustedValue([1]), true];
        yield 'stdClass' => [new \stdClass(), false];
        yield 'string' => ['abc', false];
        yield 'int' => [42, false];
    }

    /**
     * @dataProvider values
     */
    public function testChecksIsIterable(mixed $value, bool $expected): void
    {
        self::assertSame($expected, (new UntrustedValue($value))->isIterable());
        self::assertSame($expected, (new UntrustedValue($value))->isIterable(false));
    }

    public function testNullPassesWhenNullable(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertTrue($null->isIterable());
        self::assertFalse($null->isIterable(false));
    }
}
