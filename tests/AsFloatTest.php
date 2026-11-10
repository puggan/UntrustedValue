<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class AsFloatTest extends TestCase
{
    public function testWrapsTheConvertedFloat(): void
    {
        $float = (new UntrustedValue('4.2', 'item.price'))->asFloat();

        self::assertSame(4.2, $float->value);
        self::assertSame('item.price', $float->path);
    }

    public function testStrictValueWidensInts(): void
    {
        self::assertSame(42.0, (new UntrustedStrictValue(42))->asFloat()->value);
    }

    public function testStrictValueRejectsStrings(): void
    {
        $this->expectException(WrongTypeException::class);
        (new UntrustedStrictValue('4.2'))->asFloat();
    }

    public function testWrapsNullWhenNullable(): void
    {
        self::assertTrue(UntrustedValue::fromJson('null')->asFloat()->isNull());
    }

    public function testThrowsForNullWhenNotNullable(): void
    {
        $this->expectException(WrongTypeException::class);
        UntrustedValue::fromJson('null')->asFloat(nullable: false);
    }
}
