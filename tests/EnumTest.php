<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\Tests\Fixtures\Priority;
use Puggan\UntrustedValue\Tests\Fixtures\Suit;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class EnumTest extends TestCase
{
    public function testFindsStringBackedCases(): void
    {
        self::assertSame(Suit::Hearts, new UntrustedStrictValue('H')->enum(Suit::class));
    }

    public function testFindsIntBackedCases(): void
    {
        self::assertSame(Priority::High, new UntrustedStrictValue(2)->enum(Priority::class));
    }

    public function testLooseValueConvertsToTheBackingType(): void
    {
        self::assertSame(Priority::High, new UntrustedValue('2')->enum(Priority::class));
        self::assertSame(Priority::Low, new UntrustedValue(1.0)->enum(Priority::class));
    }

    public function testStrictValueRequiresTheBackingType(): void
    {
        try {
            new UntrustedStrictValue('2', 'priority')->enum(Priority::class);
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('int', $exception->expectedType);
            self::assertSame('string', $exception->givenType);
            self::assertSame('priority', $exception->path);
        }
    }

    public function testUnknownValueThrows(): void
    {
        try {
            new UntrustedValue('X', 'card.suit')->enum(Suit::class);
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('enum', $exception->filter);
            self::assertSame('card.suit', $exception->path);
            self::assertSame("Expected one of 'H', 'S' at card.suit", $exception->getMessage());
        }
    }

    public function testUnknownIntValueThrows(): void
    {
        $this->expectExceptionMessage('Expected one of 1, 2');
        new UntrustedValue(3)->enum(Priority::class);
    }

    public function testNullIsAllowedByDefault(): void
    {
        self::assertNull(UntrustedValue::fromJson('null')->enum(Suit::class));
    }

    public function testNullThrowsWhenNotNullable(): void
    {
        try {
            UntrustedValue::fromJson('null')->enum(Suit::class, false);
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame(Suit::class, $exception->expectedType);
            self::assertSame('null', $exception->givenType);
        }
    }

    public function testValuesWithoutTheBackingTypeThrow(): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedValue(['H'])->enum(Suit::class);
    }
}
