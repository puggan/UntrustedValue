<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;

final class UntrustedValueTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed}>
     */
    public static function values(): iterable
    {
        yield 'null' => [null];
        yield 'bool' => [true];
        yield 'int' => [42];
        yield 'float' => [1.5];
        yield 'string' => ['anything'];
        yield 'list' => [[1, 2, 3]];
        yield 'map' => [['user' => ['age' => '42']]];
    }

    #[DataProvider('values')]
    public function testExposesTheValue(mixed $value): void
    {
        self::assertSame($value, new UntrustedValue($value)->value);
    }

    #[DataProvider('values')]
    public function testSerializesToTheValue(mixed $value): void
    {
        self::assertSame(
            json_encode($value, JSON_THROW_ON_ERROR),
            json_encode(new UntrustedValue($value), JSON_THROW_ON_ERROR),
        );
    }

    public function testValueIsReadonly(): void
    {
        self::assertTrue(new \ReflectionProperty(UntrustedValue::class, 'value')->isReadOnly());
    }
}
