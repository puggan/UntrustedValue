<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class RequireStructureTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function values(): iterable
    {
        yield 'object' => [new \stdClass()];
        yield 'empty array' => [[]];
        yield 'list' => [[1, 2, 3]];
        yield 'map' => [['a' => 1]];
        yield 'list out of order' => [[1 => 'b', 0 => 'a']];
        yield 'int' => [42];
        yield 'string' => ['abc'];
        yield 'bool' => [true];
        yield 'null' => [null];
    }

    /**
     * Each require*() returns $this exactly when its is*() is true, for both values of $nullable.
     */
    #[DataProvider('values')]
    public function testMatchesTheIsMethods(mixed $value): void
    {
        $untrusted = new UntrustedValue($value, 'data');

        foreach (['Array', 'Object', 'List', 'Map'] as $type) {
            foreach ([true, false] as $nullable) {
                $expected = $untrusted->{'is' . $type}($nullable);

                try {
                    $result = $untrusted->{'require' . $type}($nullable);
                    self::assertTrue($expected, "require{$type}() should have thrown");
                    self::assertSame($untrusted, $result);
                } catch (WrongTypeException $exception) {
                    self::assertFalse($expected, "require{$type}() should not have thrown");
                    self::assertSame(lcfirst($type), $exception->expectedType);
                    self::assertSame('data', $exception->path);
                }
            }
        }
    }

    public function testListAndMapNameEachOtherAsGivenType(): void
    {
        $map = new UntrustedValue(['a' => 1]);
        $list = new UntrustedValue([1]);

        try {
            $map->requireList();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('map', $exception->givenType);
        }

        try {
            $list->requireMap();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('list', $exception->givenType);
        }
    }
}
