<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class IterateTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<AbstractUntrustedValue<mixed>>}>
     */
    public static function classes(): iterable
    {
        yield 'loose' => [UntrustedValue::class];
        yield 'strict' => [UntrustedStrictValue::class];
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     * @dataProvider classes
     */
    public function testYieldListYieldsEachItem(string $class): void
    {
        $items = [];
        foreach ((new $class(['a', 'b'], 'tags'))->yieldList() as $index => $item) {
            self::assertInstanceOf($class, $item);
            $items[$index] = [$item->value, $item->path];
        }

        self::assertSame([['a', 'tags.0'], ['b', 'tags.1']], $items);
    }

    public function testYieldListUsesAsList(): void
    {
        $values = [];
        foreach ((new UntrustedValue(['x' => 1, 'y' => 2]))->yieldList() as $index => $item) {
            $values[$index] = $item->value;
        }

        self::assertSame([1, 2], $values);
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     * @dataProvider classes
     */
    public function testYieldArrayYieldsKeys(string $class): void
    {
        $items = [];
        foreach ((new $class((object) ['id' => 7, 'name' => 'x'], 'user'))->yieldArray() as $key => $item) {
            self::assertInstanceOf($class, $item);
            $items[] = [$key, $item->value, $item->path];
        }

        self::assertSame([['id', 7, 'user.id'], ['name', 'x', 'user.name']], $items);
    }

    public function testPathsStartWithTheKeyAtTheRoot(): void
    {
        self::assertSame(['0', '1'], array_map(
            static fn(UntrustedValue $item): string => $item->path,
            UntrustedValue::fromJson('[1, 2]')->list(),
        ));
    }

    public function testListAndArrayCollectTheItems(): void
    {
        $untrusted = UntrustedValue::fromJson('{"b":"2","a":"1"}');

        self::assertSame([2, 1], array_map(static fn(UntrustedValue $item): int => $item->int(false), $untrusted->list()));
        self::assertSame(['b' => 2, 'a' => 1], array_map(static fn(UntrustedValue $item): int => $item->int(false), $untrusted->array()));
    }

    public function testNullGivesNothingWhenNullable(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertSame([], $null->list());
        self::assertSame([], $null->array());
        self::assertSame([], iterator_to_array($null->yieldList()));
    }

    public function testNullThrowsWhenNotNullable(): void
    {
        $this->expectException(WrongTypeException::class);
        UntrustedValue::fromJson('null')->list(false);
    }

    public function testYieldThrowsWhenCalledNotWhenIterated(): void
    {
        $this->expectException(WrongTypeException::class);
        (new UntrustedValue('abc'))->yieldList();
    }

    public function testYieldArrayThrowsWhenCalled(): void
    {
        $this->expectException(WrongTypeException::class);
        (new UntrustedValue(42))->yieldArray();
    }

    public function testYieldListOnlySeesPublicProperties(): void
    {
        $object = new class {
            public int $first = 1;
            protected int $hidden = 2;
            public string $second = 'b';
        };

        self::assertSame([1, 'b'], array_map(
            static fn(UntrustedValue $item): mixed => $item->value,
            (new UntrustedValue($object))->list(),
        ));
    }

    public function testIteratesTraversables(): void
    {
        $iterator = new UntrustedValue(new \ArrayIterator(['a' => 1, 'b' => 2]), 'data');

        self::assertSame(['data.0', 'data.1'], array_map(static fn(UntrustedValue $item): string => $item->path, $iterator->list()));
        self::assertSame(['a' => 1, 'b' => 2], array_map(static fn(UntrustedValue $item): mixed => $item->value, $iterator->array()));

        $generator = (static function (): \Generator {
            yield 'x' => 10;
            yield 'y' => 20;
        })();
        self::assertSame(['x' => 10, 'y' => 20], array_map(
            static fn(UntrustedValue $item): mixed => $item->value,
            (new UntrustedValue($generator))->array(),
        ));
    }

    public function testKeysThatAreNotIntOrStringShowTheirTypeInThePath(): void
    {
        $generator = (static function (): \Generator {
            yield new \stdClass() => 'value';
        })();

        $paths = [];
        foreach ((new UntrustedValue($generator, 'user'))->yieldArray() as $key => $item) {
            self::assertInstanceOf(\stdClass::class, $key);
            $paths[] = $item->path;
        }

        self::assertSame(['user.[stdClass]'], $paths);
    }

    public function testArrayThrowsForKeysThatAreNotIntOrString(): void
    {
        $generator = (static function (): \Generator {
            yield 1.5 => 'value';
        })();

        try {
            (new UntrustedValue($generator, 'data'))->array();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('array-key', $exception->expectedType);
            self::assertSame('float', $exception->givenType);
            self::assertSame('data.[float]', $exception->path);
        }
    }
}
