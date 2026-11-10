<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class DateTest extends TestCase
{
    public function testParsesADate(): void
    {
        $date = (new UntrustedValue('2026-11-10', 'birthday'))->date('!Y-m-d', false);

        self::assertSame('2026-11-10 00:00:00', $date->format('Y-m-d H:i:s'));
    }

    public function testParsesWithATimezone(): void
    {
        $date = (new UntrustedValue('2026-11-10 12:30'))->date('Y-m-d H:i', false, new \DateTimeZone('Europe/Stockholm'));

        self::assertSame('2026-11-10T12:30:00+01:00', $date->format(\DATE_ATOM));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDates(): iterable
    {
        yield 'february 30' => ['2026-02-30'];
        yield 'month 13' => ['2026-13-01'];
        yield 'not a date' => ['tomorrow'];
        yield 'trailing text' => ['2026-11-10x'];
        yield 'wrong format' => ['10/11/2026'];
        yield 'empty' => [''];
    }

    /**
     * @dataProvider invalidDates
     */
    public function testRejectsInvalidDates(string $value): void
    {
        try {
            (new UntrustedValue($value, 'birthday'))->date('!Y-m-d');
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('date', $exception->filter);
            self::assertSame('birthday', $exception->path);
            self::assertSame('Expected a date in the format !Y-m-d at birthday', $exception->getMessage());
        }
    }

    public function testLeapDayIsValidInALeapYear(): void
    {
        self::assertSame('2028-02-29', (new UntrustedValue('2028-02-29'))->date('!Y-m-d', false)->format('Y-m-d'));
    }

    public function testNull(): void
    {
        self::assertNull(UntrustedValue::fromJson('null')->date('!Y-m-d'));

        $this->expectException(WrongTypeException::class);
        UntrustedValue::fromJson('null')->date('!Y-m-d', false);
    }

    public function testStrictValueRequiresAString(): void
    {
        $this->expectException(WrongTypeException::class);
        (new UntrustedStrictValue(20261110))->date('Ymd');
    }

    public function testLooseValueReadsAnInt(): void
    {
        self::assertSame('2026-11-10', (new UntrustedValue(20261110))->date('!Ymd', false)->format('Y-m-d'));
    }
}
