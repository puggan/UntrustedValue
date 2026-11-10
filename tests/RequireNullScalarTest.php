<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class RequireNullScalarTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function values(): iterable
    {
        yield 'null' => [null];
        yield 'int' => [42];
        yield 'float' => [4.2];
        yield 'string' => ['abc'];
        yield 'empty string' => [''];
        yield 'bool' => [false];
        yield 'array' => [[1]];
        yield 'object' => [new \stdClass()];
    }

    /**
     * @dataProvider values
     */
    public function testRequireNullMatchesIsNull(mixed $value): void
    {
        $untrusted = new UntrustedValue($value, 'data');

        try {
            self::assertSame($untrusted, $untrusted->requireNull());
            self::assertTrue($untrusted->isNull(), 'requireNull() should have thrown');
        } catch (WrongTypeException $exception) {
            self::assertFalse($untrusted->isNull(), 'requireNull() should not have thrown');
            self::assertSame('null', $exception->expectedType);
            self::assertSame('data', $exception->path);
        }
    }

    /**
     * @dataProvider values
     */
    public function testRequireScalarMatchesIsScalar(mixed $value): void
    {
        $untrusted = new UntrustedValue($value, 'data');

        foreach ([true, false] as $nullable) {
            try {
                self::assertSame($untrusted, $untrusted->requireScalar($nullable));
                self::assertTrue($untrusted->isScalar($nullable), 'requireScalar() should have thrown');
            } catch (WrongTypeException $exception) {
                self::assertFalse($untrusted->isScalar($nullable), 'requireScalar() should not have thrown');
                self::assertSame('scalar', $exception->expectedType);
                self::assertSame('data', $exception->path);
            }
        }
    }
}
