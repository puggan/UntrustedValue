<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
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

    /**
     * @dataProvider values
     */
    public function testExposesTheValue(mixed $value): void
    {
        self::assertSame($value, (new UntrustedValue($value))->value);
    }

    /**
     * @dataProvider values
     */
    public function testRawReturnsTheValue(mixed $value): void
    {
        self::assertSame($value, (new UntrustedValue($value))->raw());
    }

    /**
     * @dataProvider values
     */
    public function testSerializesToTheValue(mixed $value): void
    {
        self::assertSame(
            json_encode($value, JSON_THROW_ON_ERROR),
            json_encode(new UntrustedValue($value), JSON_THROW_ON_ERROR),
        );
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
        self::assertSame('', (new UntrustedValue(42))->path);
    }

    public function testKeepsThePath(): void
    {
        self::assertSame('user.age', (new UntrustedValue(42, 'user.age'))->path);
    }

    public function testFromJsonStartsAtTheRootPath(): void
    {
        self::assertSame('', UntrustedValue::fromJson('{}')->path);
    }

    public function testStrictValueOnlyAcceptsExactTypes(): void
    {
        $strictString = new UntrustedStrictValue('42');
        $strictInt = new UntrustedStrictValue(42);

        self::assertSame('string', self::wrongTypeFrom([$strictString, 'int'])->givenType);
        self::assertSame('string', self::wrongTypeFrom([$strictString, 'float'])->givenType);
        self::assertSame('int', self::wrongTypeFrom([$strictInt, 'string'])->givenType);
    }

    private static function wrongTypeFrom(callable $call): WrongTypeException
    {
        try {
            $call();
        } catch (WrongTypeException $exception) {
            return $exception;
        }

        self::fail('Expected a WrongTypeException');
    }

    public function testFromJsonOnTheStrictClassGivesAStrictValue(): void
    {
        $strict = UntrustedStrictValue::fromJson('"42"');

        self::assertSame('string', self::wrongTypeFrom([$strict, 'int'])->givenType);
    }

    public function testUnwrapsAnUntrustedValue(): void
    {
        $inner = new UntrustedValue(['a' => 1], 'inner.path');
        $outer = new UntrustedValue($inner, 'outer.path');

        self::assertSame(['a' => 1], $outer->value);
        self::assertSame('outer.path', $outer->path);
    }

    public function testUnwrapsAcrossClasses(): void
    {
        $strict = new UntrustedStrictValue(new UntrustedValue('42'));

        self::assertSame('42', $strict->value);
        self::assertSame('string', self::wrongTypeFrom([$strict, 'int'])->givenType);
    }

    public function testUnwrapsNestedLayers(): void
    {
        self::assertSame(7, (new UntrustedValue(new UntrustedValue(new UntrustedStrictValue(7))))->value);
    }

    public function testItemsOfAWrappedIteratorAreNotWrappedTwiceAndKeepTheirPath(): void
    {
        $generator = (static function (): \Generator {
            yield 'a' => new UntrustedStrictValue(1, 'somewhere.else');
        })();

        foreach (new UntrustedValue($generator, 'list') as $key => $item) {
            self::assertSame(1, $item->value);
            self::assertSame('somewhere.else', $item->path);
            self::assertSame(UntrustedValue::class, $item::class);
        }
    }
}
