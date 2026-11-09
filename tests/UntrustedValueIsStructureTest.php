<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;

final class UntrustedValueIsStructureTest extends TestCase
{
    /**
     * Value, then the expected isObject(), isArray(), isList() and hasProperties().
     *
     * @return iterable<string, array{mixed, bool, bool, bool, bool}>
     */
    public static function values(): iterable
    {
        yield 'stdClass' => [new \stdClass(), true, false, false, true];
        yield 'object from json' => [UntrustedValue::fromJson('{"a":1}')->value, true, false, false, true];
        yield 'empty array' => [[], false, true, true, false];
        yield 'list' => [[1, 2, 3], false, true, true, false];
        yield 'map' => [['a' => 1], false, true, false, true];
        yield 'list out of order' => [[1 => 'b', 0 => 'a'], false, true, false, true];
        yield 'list with gap' => [[0 => 'a', 2 => 'c'], false, true, false, true];
        yield 'int' => [42, false, false, false, false];
        yield 'string' => ['abc', false, false, false, false];
        yield 'bool' => [true, false, false, false, false];
    }

    #[DataProvider('values')]
    public function testChecksTheStructure(mixed $value, bool $object, bool $array, bool $list, bool $properties): void
    {
        $untrusted = new UntrustedValue($value);

        self::assertSame($object, $untrusted->isObject());
        self::assertSame($array, $untrusted->isArray());
        self::assertSame($list, $untrusted->isList());
        self::assertSame($properties, $untrusted->hasProperties());

        self::assertSame($object, $untrusted->isObject(nullable: false));
        self::assertSame($array, $untrusted->isArray(nullable: false));
        self::assertSame($list, $untrusted->isList(nullable: false));
        self::assertSame($properties, $untrusted->hasProperties(nullable: false));
    }

    public function testNullPassesByDefault(): void
    {
        $null = new UntrustedValue(null);

        self::assertTrue($null->isObject());
        self::assertTrue($null->isArray());
        self::assertTrue($null->isList());
        self::assertTrue($null->hasProperties());
    }

    public function testNullFailsWhenNotNullable(): void
    {
        $null = new UntrustedValue(null);

        self::assertFalse($null->isObject(nullable: false));
        self::assertFalse($null->isArray(nullable: false));
        self::assertFalse($null->isList(nullable: false));
        self::assertFalse($null->hasProperties(nullable: false));
    }
}
