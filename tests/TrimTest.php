<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

final class TrimTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function untrimmed(): iterable
    {
        yield 'leading' => ['  abc', 'abc'];
        yield 'trailing' => ["abc\n", 'abc'];
        yield 'both' => [" \t abc \r\n", 'abc'];
        yield 'only whitespace' => [' ', ''];
        yield 'null byte' => ["abc\0", 'abc'];
    }

    /**
     * @dataProvider untrimmed
     */
    public function testTrimsIntoANewObject(string $value, string $expected): void
    {
        $untrusted = new UntrustedStrictValue($value, 'user.name');
        $trimmed = $untrusted->trim();

        self::assertNotSame($untrusted, $trimmed);
        self::assertInstanceOf(UntrustedStrictValue::class, $trimmed);
        self::assertSame($expected, $trimmed->value);
        self::assertSame('user.name', $trimmed->path);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function unchanged(): iterable
    {
        yield 'trimmed string' => ['abc'];
        yield 'inner whitespace' => ['a b'];
        yield 'empty string' => [''];
        yield 'int' => [42];
        yield 'null' => [null];
        yield 'array' => [[' a ']];
        yield 'object' => [new \stdClass()];
    }

    /**
     * @dataProvider unchanged
     */
    public function testReturnsThisWhenThereIsNothingToTrim(mixed $value): void
    {
        $untrusted = new UntrustedValue($value);

        self::assertSame($untrusted, $untrusted->trim());
    }
}
