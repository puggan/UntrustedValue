<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class PropertyTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<AbstractUntrustedValue<mixed>>}>
     */
    public static function classes(): iterable
    {
        yield 'loose' => [UntrustedValue::class];
        yield 'strict' => [UntrustedStrictValue::class];
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     */
    #[DataProvider('classes')]
    public function testReadsObjectProperties(string $class): void
    {
        $age = new $class((object) ['age' => 42], 'user')->property('age');

        self::assertInstanceOf($class, $age);
        self::assertSame(42, $age->value);
        self::assertSame('user.age', $age->path);
    }

    /**
     * @param class-string<AbstractUntrustedValue<mixed>> $class
     */
    #[DataProvider('classes')]
    public function testReadsArrayKeys(string $class): void
    {
        $age = new $class(['age' => 42], 'user')->property('age');

        self::assertInstanceOf($class, $age);
        self::assertSame(42, $age->value);
        self::assertSame('user.age', $age->path);
    }

    public function testPathStartsWithTheNameAtTheRoot(): void
    {
        self::assertSame('user', UntrustedValue::fromJson('{"user":{}}')->property('user')->path);
    }

    public function testNestsPaths(): void
    {
        $json = UntrustedValue::fromJson('{"user":{"tags":["a","b"]}}');
        $tag = $json->property('user')->property('tags')->property('1');

        self::assertSame('b', $tag->value);
        self::assertSame('user.tags.1', $tag->path);
    }

    public function testKeepsNullPropertyValues(): void
    {
        $age = UntrustedValue::fromJson('{"age":null}')->property('age');

        self::assertTrue($age->isNull());
    }

    public function testOnlyReadsPublicProperties(): void
    {
        $object = new class {
            public int $visible = 1;
            private int $hidden = 2;

            public function hidden(): int
            {
                return $this->hidden;
            }
        };

        self::assertSame(1, new UntrustedValue($object)->property('visible')->value);
        self::assertNull(new UntrustedValue($object)->property('hidden', false)->value);
    }

    public function testMissingRequiredPropertyThrows(): void
    {
        try {
            UntrustedValue::fromJson('{"user":{}}')->property('user')->property('age');
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('required', $exception->filter);
            self::assertSame('user.age', $exception->path);
            self::assertSame('Missing required property user.age', $exception->getMessage());
        }
    }

    public function testMissingOptionalPropertyGivesNull(): void
    {
        $age = UntrustedStrictValue::fromJson('{"user":{}}')->property('user')->property('age', false);

        self::assertInstanceOf(UntrustedStrictValue::class, $age);
        self::assertTrue($age->isNull());
        self::assertSame('user.age', $age->path);
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function notContainers(): iterable
    {
        yield 'null' => [null, 'null'];
        yield 'int' => [42, 'int'];
        yield 'string' => ['abc', 'string'];
        yield 'bool' => [true, 'bool'];
    }

    #[DataProvider('notContainers')]
    public function testThrowsWhenTheValueHasNoProperties(mixed $value, string $givenType): void
    {
        try {
            new UntrustedValue($value, 'user')->property('age', false);
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('object|array', $exception->expectedType);
            self::assertSame($givenType, $exception->givenType);
            self::assertSame('user', $exception->path);
        }
    }
}
