<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class LengthTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, int|null, int}>
     */
    public static function inRange(): iterable
    {
        yield 'no limits' => ['anything', null, 0];
        yield 'empty with no limits' => ['', null, 0];
        yield 'at max' => ['abc', 3, 0];
        yield 'at min' => ['abc', null, 3];
        yield 'between' => ['abcd', 5, 3];
        yield 'counts characters, not bytes' => ['Thorbjörn', 9, 9];
        yield 'int in loose class' => [700, 3, 3];
        yield 'null' => [null, 3, 1];
    }

    /**
     * @dataProvider inRange
     */
    public function testReturnsThisInRange(mixed $value, ?int $max, int $min): void
    {
        $untrusted = new UntrustedValue($value);

        self::assertSame($untrusted, $untrusted->length($max, $min));
    }

    public function testTooShortThrows(): void
    {
        try {
            (new UntrustedValue('ab', 'user.name'))->length(min: 3);
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('minLength', $exception->filter);
            self::assertSame('user.name', $exception->path);
            self::assertSame('Expected at least 3 characters at user.name', $exception->getMessage());
        }
    }

    public function testTooLongThrows(): void
    {
        try {
            (new UntrustedValue('Thorbjörn', 'user.name'))->length(8);
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('maxLength', $exception->filter);
            self::assertSame('Expected at most 8 characters at user.name', $exception->getMessage());
        }
    }

    public function testStrictValueRequiresAString(): void
    {
        $this->expectException(WrongTypeException::class);
        (new UntrustedStrictValue(700))->length(3);
    }

    public function testValuesWithoutAStringThrow(): void
    {
        $this->expectException(WrongTypeException::class);
        (new UntrustedValue(['abc']))->length(3);
    }

    public function testSingularInMessages(): void
    {
        $this->expectExceptionMessage('Expected at least 1 character at name');
        (new UntrustedValue('', 'name'))->length(min: 1);
    }
}
