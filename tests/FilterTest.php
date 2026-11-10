<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class FilterTest extends TestCase
{
    /**
     * @param AbstractUntrustedValue<mixed> $untrusted
     *
     * @return list<array{mixed, mixed, string}>
     */
    private static function collect(AbstractUntrustedValue $untrusted): array
    {
        $items = [];
        foreach ($untrusted as $key => $item) {
            $items[] = [$key, $item->value, $item->path];
        }

        return $items;
    }

    public function testKeepsMatchingItemsWithTheirKeysAndPaths(): void
    {
        $filtered = (new UntrustedValue(['a' => 1, 'b' => 2, 'c' => 3], 'n'))->filter(
            static fn(UntrustedValue $item): bool => $item->int(false) % 2 === 1,
        );

        self::assertSame('n', $filtered->path);
        self::assertSame([['a', 1, 'n.a'], ['c', 3, 'n.c']], self::collect($filtered));
    }

    public function testHoldsAGeneratorOfTheWrappedItems(): void
    {
        $filtered = (new UntrustedValue(['a' => 1, 'b' => 2], 'n'))->filter(static fn(UntrustedValue $item): bool => true);

        self::assertSame(['n.a', 'n.b'], array_values(array_map(
            static fn(UntrustedValue $item): string => $item->path,
            iterator_to_array($filtered->raw()),
        )));
    }

    public function testCanBeChained(): void
    {
        $result = UntrustedValue::fromJson('[5, "x", 12, 7, 30]')
            ->filter(static fn(UntrustedValue $item): bool => $item->isInt(false))
            ->filter(static fn(UntrustedValue $item): bool => $item->int(false) > 6)
            ->listInt(false);

        self::assertSame([12, 7, 30], $result);
    }

    public function testPathsKeepTheOriginalKeysEvenWhenRenumbered(): void
    {
        $filtered = (new UntrustedValue([10, 11, 12, 13], 'n'))->filter(static fn(UntrustedValue $item): bool => $item->int(false) > 11);
        self::assertSame([[2, 12, 'n.2'], [3, 13, 'n.3']], self::collect($filtered));

        $paths = [];
        foreach ((new UntrustedValue([10, 11, 12, 13], 'n'))->filter(static fn(UntrustedValue $item): bool => $item->int(false) > 11)->yieldList() as $index => $item) {
            $paths[$index] = $item->path;
        }
        self::assertSame(['n.2', 'n.3'], $paths);
    }

    public function testCallbackGetsTheKey(): void
    {
        $filtered = UntrustedValue::fromJson('{"keep":1,"drop":2}')->filter(
            static fn(UntrustedValue $item, mixed $key): bool => $key === 'keep',
        );

        self::assertSame([['keep', 1, 'keep']], self::collect($filtered));
    }

    public function testKeepsTheClass(): void
    {
        $filtered = (new UntrustedStrictValue(['1', '2']))->filter(static fn(UntrustedStrictValue $item): bool => true);

        self::assertSame(UntrustedStrictValue::class, $filtered::class);

        $this->expectException(WrongTypeException::class);
        $filtered->listInt();
    }

    public function testRunsLazilyButChecksTheValueWhenCalled(): void
    {
        $calls = 0;
        $filtered = (new UntrustedValue([1, 2]))->filter(static function () use (&$calls): bool {
            $calls++;

            return true;
        });
        self::assertSame(0, $calls);
        $filtered->list();
        self::assertSame(2, $calls);

        $this->expectException(WrongTypeException::class);
        (new UntrustedValue('abc'))->filter(static fn(): bool => true);
    }

    public function testNullGivesNoItems(): void
    {
        self::assertSame([], self::collect(UntrustedValue::fromJson('null')->filter(static fn(): bool => true)));
    }
}
