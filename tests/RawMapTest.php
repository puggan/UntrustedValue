<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class RawMapTest extends TestCase
{
    public function testListRawMapReturnsTheResults(): void
    {
        self::assertSame(
            [1, 2],
            new UntrustedValue(['x' => '1', 'y' => '2'])->listRawMap(static fn(UntrustedValue $item): int => $item->int(false)),
        );
    }

    public function testArrayRawMapKeepsKeys(): void
    {
        self::assertSame(
            ['id' => 'id=7', 'name' => 'name=x'],
            new UntrustedValue((object) ['id' => 7, 'name' => 'x'])->arrayRawMap(
                static fn(UntrustedValue $item, int|string $key): string => $key . '=' . $item->string(false),
            ),
        );
    }

    public function testResultsAreNotWrappedOrUnwrapped(): void
    {
        $results = new UntrustedValue(['1'])->listRawMap(static fn(UntrustedValue $item): UntrustedStrictValue => $item->asInt(false));

        self::assertSame(1, $results[0]->value);
    }

    public function testYieldRawMapKeepsKeysAndRunsLazily(): void
    {
        $calls = 0;
        $generator = new UntrustedValue(['a' => 1, 'b' => 2])->yieldRawMap(static function (UntrustedValue $item) use (&$calls): int {
            $calls++;

            return $item->int(false) * 10;
        });

        self::assertSame(0, $calls);
        self::assertSame(['a' => 10, 'b' => 20], iterator_to_array($generator));
        self::assertSame(2, $calls);
    }

    public function testMapperExceptionsComeThrough(): void
    {
        try {
            new UntrustedValue(['5', '500'], 'ages')->listRawMap(static fn(UntrustedValue $item): int => $item->asInt(false)->range(0, 150)->int());
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('ages.1', $exception->path);
        }
    }

    public function testValueIsCheckedWhenCalled(): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedValue('abc')->yieldRawMap(static fn(UntrustedValue $item): mixed => $item->value);
    }

    public function testNullGivesNothingWhenNullable(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertSame([], $null->listRawMap(static fn(UntrustedValue $item): mixed => $item->value));
        self::assertSame([], $null->arrayRawMap(static fn(UntrustedValue $item): mixed => $item->value));
    }
}
