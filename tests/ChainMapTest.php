<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class ChainMapTest extends TestCase
{
    public function testMapsWithKeysAndKeepsPaths(): void
    {
        $mapped = new UntrustedValue(['a' => '1', 'b' => '2'], 'n')->map(static fn(UntrustedValue $item): int => $item->int(false) * 10);

        $items = [];
        foreach ($mapped as $key => $item) {
            $items[] = [$key, $item->value, $item->path];
        }

        self::assertSame('n', $mapped->path);
        self::assertSame([['a', 10, 'n.a'], ['b', 20, 'n.b']], $items);
    }

    public function testWrappedResultsBecomeTheClassOfTheChain(): void
    {
        $mapped = new UntrustedValue(['1', '2'])->map(static fn(UntrustedValue $item): UntrustedStrictValue => $item->asInt(false));

        self::assertSame([1, 2], $mapped->listInt(false));
        self::assertSame([UntrustedValue::class, UntrustedValue::class], array_map(get_class(...), $mapped->list()));
    }

    public function testChainsWithFilter(): void
    {
        $result = UntrustedValue::fromJson('[{"age":"12"},{"age":"70"},{"age":"35"}]')
            ->map(static fn(UntrustedValue $user): UntrustedValue => $user->property('age'))
            ->filter(static fn(UntrustedValue $age): bool => $age->int(false) >= 18)
            ->map(static fn(UntrustedValue $age): int => $age->int(false) + 1)
            ->listInt(false);

        self::assertSame([71, 36], $result);
    }

    public function testErrorsPointAtTheOriginalItem(): void
    {
        try {
            UntrustedValue::fromJson('{"users":[{"age":"5"},{"age":"500"}]}')
                ->property('users')
                ->map(static fn(UntrustedValue $user): UntrustedValue => $user->property('age'))
                ->map(static fn(UntrustedValue $age): UntrustedStrictValue => $age->asInt(false)->range(0, 150))
                ->list();
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('users.1.age', $exception->path);
        }
    }

    public function testPathsSurviveTheChain(): void
    {
        $ages = UntrustedValue::fromJson('{"users":[{"age":"x"}]}')
            ->property('users')
            ->map(static fn(UntrustedValue $user): UntrustedValue => $user->property('age'));

        try {
            $ages->listInt();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('users.0.age', $exception->path);
        }
    }

    public function testPlainResultsGetTheirItemsPath(): void
    {
        $paths = array_map(
            static fn(UntrustedValue $item): string => $item->path,
            new UntrustedValue(['a' => 1], 'n')->map(static fn(UntrustedValue $item): string => 'x')->list(),
        );

        self::assertSame(['n.a'], $paths);
    }

    public function testKeepsTheClassAndRunsLazily(): void
    {
        $calls = 0;
        $mapped = new UntrustedStrictValue([1, 2])->map(static function (UntrustedStrictValue $item) use (&$calls): string {
            $calls++;

            return (string) $item->int(false);
        });

        self::assertSame(UntrustedStrictValue::class, $mapped::class);
        self::assertSame(0, $calls);
        self::assertSame(['1', '2'], $mapped->listString(false));
        self::assertSame(2, $calls);
    }

    public function testValueIsCheckedWhenCalled(): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedValue('abc')->map(static fn(): int => 1);
    }

    public function testCanBeIteratedMoreThanOnce(): void
    {
        $calls = 0;
        $mapped = new UntrustedValue(['1', '2'], 'n')->map(static function (UntrustedValue $item) use (&$calls): int {
            $calls++;

            return $item->int(false) * 10;
        });

        $first = $mapped->listInt(false);
        $second = $mapped->listInt(false);
        self::assertSame([10, 20], $first);
        self::assertSame($first, $second);
        self::assertSame(['n.0', 'n.1'], array_map(static fn(UntrustedValue $item): string => $item->path, $mapped->list()));
        self::assertSame(6, $calls, 'Each iteration runs the mapper again.');
    }
}
