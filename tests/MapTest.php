<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class MapTest extends TestCase
{
    public function testListMapWrapsPlainResultsWithTheItemPath(): void
    {
        $items = (new UntrustedValue(['1', '2'], 'numbers'))->listMap(static fn(UntrustedValue $item): int => $item->int(false) * 2);

        self::assertSame([UntrustedValue::class, UntrustedValue::class], array_map('get_class', $items));
        self::assertSame([2, 4], array_map(static fn(UntrustedValue $item): mixed => $item->value, $items));
        self::assertSame(['numbers.0', 'numbers.1'], array_map(static fn(UntrustedValue $item): string => $item->path, $items));
    }

    public function testKeepsResultsThatAreAlreadyWrapped(): void
    {
        $items = (new UntrustedValue(['1', '2'], 'numbers'))->listMap(static fn(UntrustedValue $item): UntrustedStrictValue => $item->asInt(false));

        self::assertSame([UntrustedStrictValue::class, UntrustedStrictValue::class], array_map('get_class', $items));
        self::assertSame([1, 2], array_map(static fn(UntrustedStrictValue $item): mixed => $item->value, $items));
    }

    public function testKeepsWrappedResultsOfTheSameClass(): void
    {
        $mapped = (new UntrustedValue([' a ', 'b'], 'tags'))->listMap(static fn(UntrustedValue $item): UntrustedValue => $item->trim());

        self::assertSame(['a', 'b'], array_map(static fn(UntrustedValue $item): mixed => $item->value, $mapped));
        self::assertSame(['tags.0', 'tags.1'], array_map(static fn(UntrustedValue $item): string => $item->path, $mapped));
    }

    public function testMapperGetsTheKey(): void
    {
        $value = (object) ['id' => 7, 'name' => 'x'];

        self::assertSame(
            ['id' => 'id=7', 'name' => 'name=x'],
            array_map(
                static fn(AbstractUntrustedValue $item): mixed => $item->value,
                (new UntrustedValue($value))->arrayMap(static fn(UntrustedValue $item, int|string $key): string => $key . '=' . $item->string(false)),
            ),
        );

        self::assertSame(
            ['0:a', '1:b'],
            array_map(
                static fn(AbstractUntrustedValue $item): mixed => $item->value,
                (new UntrustedValue(['x' => 'a', 'y' => 'b']))->listMap(static fn(UntrustedValue $item, int $index): string => $index . ':' . $item->string(false)),
            ),
        );
    }

    public function testYieldMapKeepsKeysAndRunsLazily(): void
    {
        $calls = 0;
        $generator = (new UntrustedValue(['a' => 1, 'b' => 2], 'data'))->yieldMap(static function (UntrustedValue $item) use (&$calls): int {
            $calls++;

            return $item->int(false) + 10;
        });

        self::assertSame(0, $calls);

        $results = [];
        foreach ($generator as $key => $item) {
            $results[] = [$key, $item->value, $item->path];
        }

        self::assertSame(2, $calls);
        self::assertSame([['a', 11, 'data.a'], ['b', 12, 'data.b']], $results);
    }

    public function testMapperExceptionsComeThrough(): void
    {
        try {
            (new UntrustedValue(['5', '500'], 'ages'))->listMap(static fn(UntrustedValue $item): UntrustedStrictValue => $item->asInt(false)->range(0, 150));
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('ages.1', $exception->path);
        }
    }

    public function testValueIsCheckedWhenCalled(): void
    {
        $this->expectException(WrongTypeException::class);
        (new UntrustedValue('abc'))->yieldMap(static fn(UntrustedValue $item): mixed => $item->value);
    }

    public function testNullGivesNothingWhenNullable(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertSame([], $null->listMap(static fn(UntrustedValue $item): mixed => $item->value));
        self::assertSame([], $null->arrayMap(static fn(UntrustedValue $item): mixed => $item->value));
    }
}
