<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class CsvTest extends TestCase
{
    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function records(): iterable
    {
        yield 'plain' => ['a,b,c', ['a', 'b', 'c']];
        yield 'quoted separator' => ['a,"b,c",d', ['a', 'b,c', 'd']];
        yield 'doubled quotes' => ['"say ""hi""",x', ['say "hi"', 'x']];
        yield 'empty fields' => ['a,,""', ['a', '', '']];
        yield 'spaces kept' => [' a , b ', [' a ', ' b ']];
        yield 'backslash is literal' => ['"a\\",b', ['a\\', 'b']];
        yield 'single field' => ['only', ['only']];
    }

    /**
     * @param list<string> $expected
     */
    #[DataProvider('records')]
    public function testParsesARecord(string $record, array $expected): void
    {
        self::assertSame($expected, new UntrustedValue($record)->csv()->value);
    }

    public function testOtherSeparatorAndEnclosure(): void
    {
        self::assertSame(['a', 'b;c'], new UntrustedValue("a;'b;c'")->csv(';', "'")->value);
    }

    public function testEmptyStringGivesAnEmptyList(): void
    {
        self::assertSame([], new UntrustedValue('')->csv()->value);
    }

    public function testItemsGetPaths(): void
    {
        try {
            new UntrustedValue('1,"2",x', 'row')->csv()->listInt(false);
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('row.2', $exception->path);
        }
    }

    public function testNullReturnsThis(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertSame($null, $null->csv());
    }

    public function testStrictValueRequiresAString(): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedStrictValue(12)->csv();
    }

    public function testKeepsTheClassAndPath(): void
    {
        $fields = new UntrustedStrictValue('a,b', 'row')->csv();

        self::assertSame(UntrustedStrictValue::class, $fields::class);
        self::assertSame('row', $fields->path);
    }
}
