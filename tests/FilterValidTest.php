<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

final class FilterValidTest extends TestCase
{
    public function testKeepsItemsTheValidatorAccepts(): void
    {
        $users = UntrustedValue::fromJson('[{"age":"30"},{"age":"old"},{"age":"200"},{}, {"age":"18"}]')
            ->filterValid(static function (UntrustedValue $user): void {
                $user->property('age')->asInt(false)->range(0, 150);
            });

        self::assertSame(['0', '4'], array_map(static fn(UntrustedValue $user): string => $user->path, $users->list()));
    }

    public function testKeepsTheOriginalItemNotTheValidatorResult(): void
    {
        $kept = new UntrustedValue(['5', 'x'], 'n')->filterValid(static fn(UntrustedValue $item): UntrustedStrictValue => $item->asInt(false));

        $items = $kept->list();
        self::assertCount(1, $items);
        self::assertSame('5', $items[0]->value);
        self::assertSame('n.0', $items[0]->path);
    }

    public function testValidatorGetsTheKey(): void
    {
        $seen = [];
        new UntrustedValue(['a' => 1, 'b' => 2])->filterValid(static function (UntrustedValue $item, mixed $key) use (&$seen): void {
            $seen[] = $key;
        })->list();

        self::assertSame(['a', 'b'], $seen);
    }

    public function testOtherExceptionsComeThrough(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UntrustedValue(['a'])->filterValid(static fn(UntrustedValue $item): UntrustedValue => $item->match('/broken'))->list();
    }

    public function testChains(): void
    {
        self::assertSame(
            [2, 3],
            new UntrustedValue(['1', 'x', '2', '3', 'y'])
                ->filterValid(static fn(UntrustedValue $item): ?int => $item->int())
                ->filter(static fn(UntrustedValue $item): bool => $item->int(false) > 1)
                ->listInt(false),
        );
    }

    public function testKeepsTheClass(): void
    {
        self::assertSame(UntrustedStrictValue::class, new UntrustedStrictValue([1])->filterValid(static fn(): mixed => null)::class);
    }
}
