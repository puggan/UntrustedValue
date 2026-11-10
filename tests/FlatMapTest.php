<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class FlatMapTest extends TestCase
{
    public function testFlattensNestedUntrustedValuesWithPaths(): void
    {
        $users = UntrustedValue::fromJson('[{"tags":["a","b"]},{"tags":[]},{"tags":["c"]}]');

        $paths = [];
        foreach ($users->yieldFlatMap(static fn(UntrustedValue $user): UntrustedValue => $user->property('tags')) as $tag) {
            $paths[$tag->string(false)] = $tag->path;
        }

        self::assertSame(['a' => '0.tags.0', 'b' => '0.tags.1', 'c' => '2.tags.0'], $paths);
    }

    public function testFlattensPlainIterablesAsTheyAre(): void
    {
        $flat = (new UntrustedValue([1, 2, 3]))->yieldFlatMap(
            static fn(UntrustedValue $item): array => array_fill(0, $item->int(false), $item->int(false)),
        );

        self::assertSame([1, 2, 2, 3, 3, 3], iterator_to_array($flat, false));
    }

    public function testKeysComeFromEachResult(): void
    {
        $flat = (new UntrustedValue(['x', 'y']))->yieldFlatMap(
            static fn(UntrustedValue $item, mixed $key): \Generator => yield $item->string(false) => $key,
        );

        self::assertSame(['x' => 0, 'y' => 1], iterator_to_array($flat));
    }

    public function testRunsLazilyButChecksTheValueWhenCalled(): void
    {
        $calls = 0;
        $flat = (new UntrustedValue([1, 2]))->yieldFlatMap(static function () use (&$calls): array {
            $calls++;

            return [];
        });
        self::assertSame(0, $calls);
        iterator_to_array($flat, false);
        self::assertSame(2, $calls);

        $this->expectException(WrongTypeException::class);
        (new UntrustedValue('abc'))->yieldFlatMap(static fn(): array => []);
    }

    public function testNullGivesNothingUnlessNotNullable(): void
    {
        self::assertSame([], iterator_to_array(UntrustedValue::fromJson('null')->yieldFlatMap(static fn(): array => [1]), false));

        $this->expectException(WrongTypeException::class);
        UntrustedValue::fromJson('null')->yieldFlatMap(static fn(): array => [1], false);
    }
}
