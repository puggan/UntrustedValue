<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class ForeachTest extends TestCase
{
    public function testForeachGivesKeysAndWrappedItems(): void
    {
        $items = [];
        foreach (UntrustedValue::fromJson('{"user":{"id":"7","name":"x"}}')->property('user') as $key => $item) {
            $items[] = [$key, $item->value, $item->path];
        }

        self::assertSame([['id', '7', 'user.id'], ['name', 'x', 'user.name']], $items);
    }

    public function testItemsKeepTheClass(): void
    {
        foreach (new UntrustedStrictValue(['a']) as $item) {
            self::assertSame(UntrustedStrictValue::class, $item::class);
        }

        $this->expectException(WrongTypeException::class);
        foreach (new UntrustedStrictValue(['1']) as $item) {
            $item->int();
        }
    }

    public function testNullGivesAnEmptyLoop(): void
    {
        $count = 0;
        foreach (UntrustedValue::fromJson('null') as $item) {
            $count++;
        }

        self::assertSame(0, $count);
    }

    public function testScalarThrowsWhenTheLoopStarts(): void
    {
        $untrusted = new UntrustedValue('abc', 'tags');

        try {
            foreach ($untrusted as $item) {
                self::fail('Expected no items');
            }
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('array', $exception->expectedType);
            self::assertSame('tags', $exception->path);
        }
    }

    public function testWorksWithIteratorFunctions(): void
    {
        $untrusted = new UntrustedValue(['a' => 1, 'b' => 2]);

        self::assertSame(2, iterator_count($untrusted));
        self::assertSame(['a', 'b'], array_keys(iterator_to_array($untrusted)));
    }
}
