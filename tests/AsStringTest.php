<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class AsStringTest extends TestCase
{
    public function testWrapsTheConvertedString(): void
    {
        $string = new UntrustedValue(42, 'user.name')->asString();

        self::assertSame('42', $string->value);
        self::assertSame('user.name', $string->path);
    }

    public function testStrictValueOnlyWrapsStrings(): void
    {
        self::assertSame('abc', new UntrustedStrictValue('abc')->asString()->value);

        $this->expectException(WrongTypeException::class);
        new UntrustedStrictValue(42)->asString();
    }

    public function testWrapsNullWhenNullable(): void
    {
        self::assertTrue(UntrustedValue::fromJson('null')->asString()->isNull());
    }

    public function testThrowsForNullWhenNotNullable(): void
    {
        $this->expectException(WrongTypeException::class);
        UntrustedValue::fromJson('null')->asString(nullable: false);
    }
}
