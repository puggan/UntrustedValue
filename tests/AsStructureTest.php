<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class AsStructureTest extends TestCase
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
     */
    #[DataProvider('classes')]
    public function testAsListReturnsThisForLists(string $class): void
    {
        foreach ([[], [1, 2, 3]] as $list) {
            $untrusted = new $class($list, 'items');

            self::assertSame($untrusted, $untrusted->asList());
        }
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     */
    #[DataProvider('classes')]
    public function testAsListTakesTheValuesOfOtherArrays(string $class): void
    {
        foreach ([[['a' => 1, 'b' => 2], [1, 2]], [[1 => 'b', 0 => 'a'], ['b', 'a']], [[3 => 'x'], ['x']]] as [$array, $expected]) {
            $list = new $class($array, 'items')->asList();

            self::assertInstanceOf($class, $list);
            self::assertSame($expected, $list->value);
            self::assertSame('items', $list->path);
        }
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     */
    #[DataProvider('classes')]
    public function testAsListTakesThePublicPropertyValuesOfObjects(string $class): void
    {
        $object = new class {
            public int $first = 1;
            protected int $hidden = 2;
            public string $second = 'b';
        };

        $list = new $class($object, 'items')->asList();

        self::assertInstanceOf($class, $list);
        self::assertSame([1, 'b'], $list->value);
        self::assertSame('items', $list->path);
    }

    #[DataProvider('scalars')]
    public function testAsListThrowsForOtherValues(mixed $value, string $givenType): void
    {
        $exception = self::wrongTypeFrom(static fn() => new UntrustedValue($value, 'items')->asList());

        self::assertSame('list', $exception->expectedType);
        self::assertSame($givenType, $exception->givenType);
        self::assertSame('items', $exception->path);
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     */
    #[DataProvider('classes')]
    public function testAsArrayReturnsThisForArrays(string $class): void
    {
        foreach ([[], [1, 2], ['a' => 1]] as $array) {
            $untrusted = new $class($array);

            self::assertSame($untrusted, $untrusted->asArray());
        }
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     */
    #[DataProvider('classes')]
    public function testAsArrayCastsObjects(string $class): void
    {
        $array = new $class((object) ['id' => 7, 'name' => 'x'], 'user')->asArray();

        self::assertInstanceOf($class, $array);
        self::assertSame(['id' => 7, 'name' => 'x'], $array->value);
        self::assertSame('user', $array->path);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function scalars(): iterable
    {
        yield 'int' => [42, 'int'];
        yield 'string' => ['abc', 'string'];
        yield 'bool' => [true, 'bool'];
    }

    #[DataProvider('scalars')]
    public function testAsArrayThrowsForOtherValues(mixed $value, string $givenType): void
    {
        $exception = self::wrongTypeFrom(static fn() => new UntrustedValue($value)->asArray());

        self::assertSame('array', $exception->expectedType);
        self::assertSame($givenType, $exception->givenType);
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     */
    #[DataProvider('classes')]
    public function testAsObjectReturnsThisForObjects(string $class): void
    {
        $untrusted = new $class(new \DateTimeImmutable());

        self::assertSame($untrusted, $untrusted->asObject());
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     */
    #[DataProvider('classes')]
    public function testAsObjectCastsNonListArrays(string $class): void
    {
        $object = new $class(['id' => 7], 'user')->asObject();

        self::assertInstanceOf($class, $object);
        self::assertEquals((object) ['id' => 7], $object->value);
        self::assertSame('user', $object->path);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function notObjects(): iterable
    {
        yield 'list' => [[1, 2], 'list'];
        yield 'empty array' => [[], 'list'];
        yield from self::scalars();
    }

    #[DataProvider('notObjects')]
    public function testAsObjectThrowsForOtherValues(mixed $value, string $givenType): void
    {
        $exception = self::wrongTypeFrom(static fn() => new UntrustedValue($value)->asObject());

        self::assertSame('object', $exception->expectedType);
        self::assertSame($givenType, $exception->givenType);
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     */
    #[DataProvider('classes')]
    public function testNullReturnsThisWhenNullable(string $class): void
    {
        $null = new $class(null, 'user');

        self::assertSame($null, $null->asList());
        self::assertSame($null, $null->asArray());
        self::assertSame($null, $null->asObject());
    }

    public function testNullThrowsWhenNotNullable(): void
    {
        $null = new UntrustedValue(null, 'user');

        foreach (['list' => $null->asList(...), 'array' => $null->asArray(...), 'object' => $null->asObject(...)] as $type => $method) {
            $exception = self::wrongTypeFrom(static fn() => $method(false));

            self::assertSame($type, $exception->expectedType);
            self::assertSame('null', $exception->givenType);
            self::assertSame('user', $exception->path);
        }
    }

    /**
     * @return iterable<string, array{\Traversable<mixed, mixed>, array<array-key, mixed>}>
     */
    public static function traversables(): iterable
    {
        yield 'generator' => [(static function (): \Generator {
            yield 'a' => 1;
            yield 'b' => 2;
        })(), ['a' => 1, 'b' => 2]];
        yield 'ArrayIterator' => [new \ArrayIterator([3 => 'x', 5 => 'y']), [3 => 'x', 5 => 'y']];
        yield 'IteratorAggregate' => [new class implements \IteratorAggregate {
            public string $notAnItem = 'property';

            public function getIterator(): \Generator
            {
                yield 'first' => 'item';
            }
        }, ['first' => 'item']];
    }

    /**
     * @param \Traversable<mixed, mixed> $traversable
     * @param array<array-key, mixed> $expected
     */
    #[DataProvider('traversables')]
    public function testAsListTakesTheItemsOfATraversable(\Traversable $traversable, array $expected): void
    {
        $list = new UntrustedStrictValue($traversable, 'items')->asList();

        self::assertSame(array_values($expected), $list->value);
        self::assertSame('items', $list->path);
        self::assertSame(UntrustedStrictValue::class, $list::class);
    }

    /**
     * @param \Traversable<mixed, mixed> $traversable
     * @param array<array-key, mixed> $expected
     */
    #[DataProvider('traversables')]
    public function testAsArrayTakesTheItemsOfATraversableWithKeys(\Traversable $traversable, array $expected): void
    {
        $array = new UntrustedValue($traversable, 'items')->asArray();

        self::assertSame($expected, $array->value);
        self::assertSame('items', $array->path);
    }

    public function testAsArrayKeepsTheLastItemForARepeatedKey(): void
    {
        $generator = (static function (): \Generator {
            yield 'k' => 1;
            yield 'k' => 2;
        })();

        self::assertSame(['k' => 2], new UntrustedValue($generator)->asArray()->value);
    }

    public function testAsArrayThrowsForKeysThatAreNotIntOrString(): void
    {
        $generator = (static function (): \Generator {
            yield new \stdClass() => 'value';
        })();

        $exception = self::wrongTypeFrom(static fn() => new UntrustedValue($generator, 'items')->asArray());

        self::assertSame('array-key', $exception->expectedType);
        self::assertSame('stdClass', $exception->givenType);
        self::assertSame('items', $exception->path);
    }

    public function testAsObjectKeepsATraversable(): void
    {
        $untrusted = new UntrustedValue(new \ArrayIterator([1]));

        self::assertSame($untrusted, $untrusted->asObject());
    }

    public function testAsArrayOnlyTakesPublicProperties(): void
    {
        $object = new class {
            public int $visible = 1;
            protected int $protected = 2;
            private int $private = 3;

            public function sum(): int
            {
                return $this->protected + $this->private;
            }
        };

        self::assertSame(['visible' => 1], new UntrustedValue($object)->asArray()->value);
    }

    private static function wrongTypeFrom(callable $call): WrongTypeException
    {
        try {
            $call();
        } catch (WrongTypeException $exception) {
            return $exception;
        }

        self::fail('Expected a WrongTypeException');
    }
}
