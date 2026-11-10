<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class AsIntTest extends TestCase
{
    public function testWrapsTheConvertedInt(): void
    {
        $int = new UntrustedValue('42', 'user.age')->asInt();

        self::assertSame(42, $int->value);
        self::assertSame('user.age', $int->path);
    }

    public function testStrictValueOnlyWrapsInts(): void
    {
        self::assertSame(42, new UntrustedStrictValue(42)->asInt()->value);

        $this->expectException(WrongTypeException::class);
        new UntrustedStrictValue('42')->asInt();
    }

    public function testWrapsNullWhenNullable(): void
    {
        self::assertTrue(UntrustedValue::fromJson('null')->asInt()->isNull());
    }

    public function testThrowsForNullWhenNotNullable(): void
    {
        $this->expectException(WrongTypeException::class);
        UntrustedValue::fromJson('null')->asInt(nullable: false);
    }

    public function testThrowsWhenNotConvertible(): void
    {
        try {
            new UntrustedValue('abc', 'user.age')->asInt();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('non-numeric-string', $exception->givenType);
            self::assertSame('user.age', $exception->path);
        }
    }
}
