<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\LazyIterable;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class SliceTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int|null}>
     */
    public static function slices(): iterable
    {
        yield 'first 3' => [0, 3];
        yield 'skip 2' => [2, null];
        yield 'middle' => [1, 2];
        yield 'past the end' => [10, 2];
        yield 'length past the end' => [3, 10];
        yield 'zero length' => [1, 0];
        yield 'everything' => [0, null];
    }

    #[DataProvider('slices')]
    public function testMatchesArraySlice(int $offset, ?int $length): void
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4, 'e' => 5];

        self::assertSame(
            array_slice($array, $offset, $length, true),
            array_map(static fn(UntrustedValue $item): mixed => $item->value, new UntrustedValue($array)->slice($offset, $length)->array()),
        );
    }

    public function testKeepsKeysAndPaths(): void
    {
        $paths = array_map(static fn(UntrustedValue $item): string => $item->path, new UntrustedValue([10, 11, 12, 13], 'n')->slice(1, 2)->array());

        self::assertSame([1 => 'n.1', 2 => 'n.2'], $paths);
    }

    public function testChainsAndCanBeIteratedMoreThanOnce(): void
    {
        $slice = new UntrustedValue(['1', 'x', '2', '3', '4'], 'n')
            ->filterValid(static fn(UntrustedValue $item): int => $item->int(false))
            ->slice(1, 2);

        $first = $slice->listInt(false);
        $second = $slice->listInt(false);
        self::assertSame([2, 3], $first);
        self::assertSame($first, $second);
        self::assertSame([2, 3], $slice->asList()->listInt(false));
    }

    public function testStopsReadingAnInfiniteSource(): void
    {
        $pulled = 0;
        $naturals = new LazyIterable(static function () use (&$pulled): \Generator {
            for ($n = 1; ; $n++) {
                $pulled++;
                yield $n;
            }
        });

        self::assertSame([1, 2, 3], new UntrustedValue($naturals)->slice(0, 3)->listInt(false));
        self::assertSame(3, $pulled);
        self::assertSame([5, 6], new UntrustedValue($naturals)->slice(4, 2)->listInt(false));
    }

    public function testKeepsTheClass(): void
    {
        self::assertSame(UntrustedStrictValue::class, new UntrustedStrictValue([1, 2])->slice(0, 1)::class);
    }

    public function testNegativeValuesThrow(): void
    {
        foreach ([[-1, null], [0, -1]] as [$offset, $length]) {
            try {
                new UntrustedValue([1, 2])->slice($offset, $length);
                self::fail('Expected an InvalidArgumentException');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('non-negative', $exception->getMessage());
            }
        }
    }

    public function testValueIsCheckedWhenCalled(): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedValue('abc')->slice(0, 1);
    }

    public function testNullGivesNoItems(): void
    {
        self::assertSame([], UntrustedValue::fromJson('null')->slice(0, 3)->list());
    }
}
