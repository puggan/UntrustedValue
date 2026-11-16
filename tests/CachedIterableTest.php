<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\CachedIterable;
use Puggan\UntrustedValue\UntrustedValue;

final class CachedIterableTest extends TestCase
{
    public function testConstructorCachesAGenerator(): void
    {
        $untrusted = new UntrustedValue((static function (): \Generator {
            yield 'a' => '1';
            yield 'b' => '2';
        })(), 'n');

        self::assertInstanceOf(CachedIterable::class, $untrusted->raw());

        $first = $untrusted->arrayInt(false);
        $second = $untrusted->arrayInt(false);
        self::assertSame(['a' => 1, 'b' => 2], $first);
        self::assertSame($first, $second);
        self::assertSame([1, 2], $untrusted->filter(static fn(): bool => true)->listInt(false));
        self::assertSame(['a' => 1, 'b' => 2], $untrusted->asArray()->arrayInt(false));
    }

    public function testSliceTwiceOnAnInfiniteGenerator(): void
    {
        $pulled = 0;
        $naturals = new UntrustedValue((static function () use (&$pulled): \Generator {
            for ($n = 1; ; $n++) {
                $pulled++;
                yield $n;
            }
        })());

        self::assertSame([1, 2, 3], $naturals->slice(0, 3)->listInt(false));
        self::assertSame(3, $pulled);
        self::assertSame([1, 2, 3, 4, 5], $naturals->slice(0, 5)->listInt(false));
        self::assertSame(5, $pulled, 'Only the two new items are read from the generator.');
        self::assertSame([9, 10], $naturals->slice(8, 2)->listInt(false));
        self::assertSame(10, $pulled);
    }

    public function testNestedLoopsShareTheCache(): void
    {
        $cached = new CachedIterable((static function (): \Generator {
            yield 1;
            yield 2;
        })());

        $pairs = [];
        foreach ($cached as $a) {
            foreach ($cached as $b) {
                $pairs[] = "{$a}{$b}";
            }
        }

        self::assertSame(['11', '12', '21', '22'], $pairs);
    }

    public function testKeepsAnyKeysIncludingRepeats(): void
    {
        $object = new \stdClass();
        $cached = new CachedIterable((static function () use ($object): \Generator {
            yield 'k' => 1;
            yield 'k' => 2;
            yield $object => 3;
        })());

        foreach ([1, 2] as $round) {
            $seen = [];
            foreach ($cached as $key => $item) {
                $seen[] = [$key, $item];
            }
            self::assertSame([['k', 1], ['k', 2], [$object, 3]], $seen, "round {$round}");
        }
    }

    public function testRethrowsTheGeneratorsExceptionEveryTime(): void
    {
        $failure = new \RuntimeException('disk error');
        $cached = new CachedIterable((static function () use ($failure): \Generator {
            yield 1;
            yield 2;

            throw $failure;
        })());

        foreach ([1, 2] as $round) {
            $seen = [];
            try {
                foreach ($cached as $item) {
                    $seen[] = $item;
                }
                self::fail('Expected the generator\'s exception');
            } catch (\RuntimeException $exception) {
                self::assertSame($failure, $exception, "round {$round}");
                self::assertSame([1, 2], $seen, "round {$round}");
            }
        }
    }

    public function testAFinishedGeneratorGivesNoItems(): void
    {
        $generator = (static function (): \Generator {
            yield 1;
        })();
        foreach ($generator as $item) {
        }

        self::assertSame([], iterator_to_array(new CachedIterable($generator)));
    }
}
