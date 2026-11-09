<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

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
    public function testRawReturnsTheValue(mixed $value): void
    {
        self::assertSame($value, new UntrustedValue($value)->raw());
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

    public function testFromJsonDecodesObjectsToStdClass(): void
    {
        $value = UntrustedValue::fromJson('{"user":{"age":42,"tags":["a","b"]}}')->value;

        self::assertInstanceOf(\stdClass::class, $value);
        self::assertEquals((object) ['user' => (object) ['age' => 42, 'tags' => ['a', 'b']]], $value);
    }

    public function testFromJsonDecodesScalars(): void
    {
        self::assertSame('text', UntrustedValue::fromJson('"text"')->value);
        self::assertNull(UntrustedValue::fromJson('null')->value);
    }

    public function testFromJsonWrapsInvalidJsonInWrongTypeException(): void
    {
        try {
            UntrustedValue::fromJson('{"broken":');
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('json', $exception->expectedType);
            self::assertSame('string', $exception->givenType);
            self::assertSame('', $exception->path);
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
            self::assertStringStartsWith('Invalid JSON: ', $exception->getMessage());
        }
    }

    public function testPathDefaultsToEmpty(): void
    {
        self::assertSame('', new UntrustedValue(42)->path);
    }

    public function testKeepsThePath(): void
    {
        self::assertSame('user.age', new UntrustedValue(42, 'user.age')->path);
    }

    public function testPathIsReadonly(): void
    {
        self::assertTrue(new \ReflectionProperty(UntrustedValue::class, 'path')->isReadOnly());
    }

    public function testFromJsonStartsAtTheRootPath(): void
    {
        self::assertSame('', UntrustedValue::fromJson('{}')->path);
    }
}
