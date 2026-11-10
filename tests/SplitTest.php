<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class SplitTest extends TestCase
{
    public function testSplitsIntoAListWithPaths(): void
    {
        $ids = new UntrustedValue('123,456,789', 'ids')->split(',');

        self::assertSame(['123', '456', '789'], $ids->value);
        self::assertSame('ids', $ids->path);
        self::assertSame([123, 456, 789], $ids->listInt(false));
    }

    public function testItemErrorsPointAtTheItem(): void
    {
        try {
            new UntrustedValue('1,x,3', 'ids')->split(',')->listInt(false);
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('ids.1', $exception->path);
        }
    }

    public function testEmptyStringGivesAnEmptyList(): void
    {
        self::assertSame([], new UntrustedValue('')->split(',')->value);
    }

    public function testKeepsEmptyParts(): void
    {
        self::assertSame(['a', '', 'b', ''], new UntrustedValue('a,,b,')->split(',')->value);
    }

    public function testMultiCharacterSeparator(): void
    {
        self::assertSame(['a', 'b'], new UntrustedValue('a, b')->split(', ')->value);
    }

    public function testNullReturnsThis(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertSame($null, $null->split(','));
    }

    public function testLooseValueSplitsConvertedScalars(): void
    {
        self::assertSame(['4', '5'], new UntrustedValue(4.5)->split('.')->value);
    }

    public function testStrictValueRequiresAString(): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedStrictValue(45)->split(',');
    }

    public function testKeepsTheClass(): void
    {
        self::assertSame(UntrustedStrictValue::class, new UntrustedStrictValue('a')->split(',')::class);
    }
}
