<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class UntrustedValueBoolTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function convertible(): iterable
    {
        foreach (['1', 'true', 'TRUE', 'True', 'yes', 'Yes', 'on', 'ON'] as $string) {
            yield "string {$string}" => [$string, true];
        }
        foreach (['0', 'false', 'FALSE', 'no', 'NO', 'off', 'Off'] as $string) {
            yield "string {$string}" => [$string, false];
        }
        yield 'int 1' => [1, true];
        yield 'int 0' => [0, false];
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function notConvertible(): iterable
    {
        yield 'empty string' => ['', 'non-boolean-string'];
        yield 'other string' => ['maybe', 'non-boolean-string'];
        yield 'padded string' => [' true', 'non-boolean-string'];
        yield 'int 2' => [2, 'non-boolean-int'];
        yield 'int -1' => [-1, 'non-boolean-int'];
        yield 'float' => [1.0, 'float'];
        yield 'array' => [[true], 'array'];
        yield 'object' => [new \stdClass(), 'stdClass'];
    }

    public function testReturnsBools(): void
    {
        foreach ([true, false] as $bool) {
            self::assertSame($bool, (new UntrustedValue($bool))->bool());
            self::assertSame($bool, (new UntrustedStrictValue($bool))->bool());
        }
    }

    /**
     * @dataProvider convertible
     */
    public function testLooseConverts(mixed $value, bool $expected): void
    {
        self::assertSame($expected, (new UntrustedValue($value))->bool());
        self::assertTrue((new UntrustedValue($value))->isBool());
    }

    /**
     * @dataProvider convertible
     */
    public function testStrictRejectsConversions(mixed $value, bool $looseResult): void
    {
        self::assertFalse((new UntrustedStrictValue($value))->isBool());

        $this->expectException(WrongTypeException::class);
        (new UntrustedStrictValue($value))->bool();
    }

    /**
     * @dataProvider notConvertible
     */
    public function testRejectsOtherValues(mixed $value, string $givenType): void
    {
        try {
            (new UntrustedValue($value, 'agree'))->bool();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('bool', $exception->expectedType);
            self::assertSame($givenType, $exception->givenType);
            self::assertSame("Expected bool at agree, got {$givenType}", $exception->getMessage());
        }
        self::assertFalse((new UntrustedValue($value))->isBool());
    }

    public function testNull(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertNull($null->bool());
        self::assertTrue($null->isBool());
        self::assertFalse($null->isBool(false));
        self::assertNull(UntrustedStrictValue::fromJson('null')->bool());

        $this->expectException(WrongTypeException::class);
        $null->bool(false);
    }

    public function testAsBoolWrapsTheConvertedBool(): void
    {
        $agree = (new UntrustedValue('yes', 'agree'))->asBool(false);

        self::assertTrue($agree->value);
        self::assertSame('agree', $agree->path);
        self::assertSame(UntrustedStrictValue::class, $agree::class);

        $this->expectException(WrongTypeException::class);
        (new UntrustedStrictValue('yes'))->asBool();
    }
}
