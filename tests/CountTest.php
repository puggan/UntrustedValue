<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class CountTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, int}>
     */
    public static function countable(): iterable
    {
        yield 'empty array' => [[], 0];
        yield 'list' => [[1, 2, 3], 3];
        yield 'map' => [['a' => 1, 'b' => 2], 2];
        yield 'ArrayIterator' => [new \ArrayIterator([1, 2]), 2];
        yield 'stdClass' => [(object) ['a' => 1], 1];
    }

    #[DataProvider('countable')]
    public function testReturnsThisInRange(mixed $value, int $count): void
    {
        $untrusted = new UntrustedValue($value);

        self::assertSame($untrusted, $untrusted->count($count, $count));
        self::assertSame($untrusted, $untrusted->count());
    }

    public function testTooFewThrows(): void
    {
        try {
            new UntrustedValue([], 'tags')->count(min: 1);
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('minCount', $exception->filter);
            self::assertSame('tags', $exception->path);
            self::assertSame('Expected at least 1 item at tags', $exception->getMessage());
        }
    }

    public function testTooManyThrows(): void
    {
        try {
            new UntrustedValue([1, 2, 3], 'tags')->count(2);
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('maxCount', $exception->filter);
            self::assertSame('Expected at most 2 items at tags', $exception->getMessage());
        }
    }

    public function testCountsOnlyPublicProperties(): void
    {
        $object = new class {
            public int $a = 1;
            private int $hidden = 2;

            public function hidden(): int
            {
                return $this->hidden;
            }
        };

        $this->expectException(InvalidTypeException::class);
        new UntrustedValue($object)->count(min: 2);
    }

    public function testCollectsAGeneratorSoItCanStillBeUsed(): void
    {
        $tags = new UntrustedValue(['a', '', 'b'], 'tags')
            ->filter(static fn(UntrustedValue $tag): bool => $tag->string(false) !== '')
            ->count(10, 1);

        self::assertSame(['a', 'b'], $tags->listString(false));
        self::assertSame(['tags.0', 'tags.2'], array_map(static fn(UntrustedValue $tag): string => $tag->path, $tags->list()));
    }

    public function testCountingAnEmptyFilterResultFails(): void
    {
        $this->expectException(InvalidTypeException::class);
        new UntrustedValue(['', ''])->filter(static fn(UntrustedValue $tag): bool => $tag->string(false) !== '')->count(min: 1);
    }

    public function testNullPasses(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertSame($null, $null->count(min: 1));
    }

    public function testScalarThrows(): void
    {
        try {
            new UntrustedValue('abc', 'tags')->count();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('object|array', $exception->expectedType);
            self::assertSame('tags', $exception->path);
        }
    }

    public function testPluralInMessages(): void
    {
        $this->expectExceptionMessage('Expected at most 1 item at tags');
        new UntrustedValue([1, 2], 'tags')->count(1);
    }
}
