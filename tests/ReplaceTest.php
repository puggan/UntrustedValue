<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

final class ReplaceTest extends TestCase
{
    public function testReplacesAnIdenticalValue(): void
    {
        $replaced = new UntrustedStrictValue('N/A', 'user.age')->replace('N/A', null);

        self::assertInstanceOf(UntrustedStrictValue::class, $replaced);
        self::assertNull($replaced->value);
        self::assertSame('user.age', $replaced->path);
    }

    public function testComparesStrictly(): void
    {
        $untrusted = new UntrustedValue('0');

        self::assertSame($untrusted, $untrusted->replace(0, 'zero'));
        self::assertSame($untrusted, $untrusted->replace(false, 'zero'));
        self::assertSame('zero', $untrusted->replace('0', 'zero')->value);
    }

    public function testDefaultReplacesNull(): void
    {
        $value = UntrustedValue::fromJson('{"page":null}')->property('page')->default(1);

        self::assertSame(1, $value->value);
        self::assertSame('page', $value->path);
    }

    public function testDefaultKeepsOtherValues(): void
    {
        foreach (['', 0, false, [], '5'] as $value) {
            $untrusted = new UntrustedValue($value);

            self::assertSame($untrusted, $untrusted->default(1));
        }
    }

    public function testDefaultWorksWithOptionalProperties(): void
    {
        self::assertSame(20, UntrustedValue::fromJson('{}')->property('limit', false)->default(20)->int(false));
    }

    public function testBlankDefaultReplacesTheEmptyString(): void
    {
        self::assertNull(new UntrustedValue('')->blankDefault()->value);
        self::assertSame('none', new UntrustedValue('')->blankDefault('none')->value);
    }

    public function testBlankDefaultKeepsOtherValues(): void
    {
        foreach ([' ', '0', null, 0, false] as $value) {
            $untrusted = new UntrustedValue($value);

            self::assertSame($untrusted, $untrusted->blankDefault());
        }
    }

    public function testBlankDefaultAfterTrim(): void
    {
        self::assertNull(new UntrustedValue('   ')->trim()->blankDefault()->value);
    }
}
