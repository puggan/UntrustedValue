<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

final class OneOfTest extends TestCase
{
    public function testReturnsThisForAnAllowedValue(): void
    {
        $order = new UntrustedValue('desc', 'order');

        self::assertSame($order, $order->oneOf(['asc', 'desc']));
    }

    public function testComparesStrictly(): void
    {
        try {
            (new UntrustedValue(self::untyped('1'), 'page.size'))->oneOf([1, 2]);
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('oneOf', $exception->filter);
            self::assertSame('page.size', $exception->path);
            self::assertSame('Expected one of 1, 2 at page.size', $exception->getMessage());
        }

        self::assertSame(2, (new UntrustedValue('2'))->asInt(false)->oneOf([1, 2])->int(false));
    }

    public function testMessageListsTheAllowedValues(): void
    {
        $this->expectExceptionMessage("Expected one of 'asc', 'desc', true, null, stdClass");
        (new UntrustedStrictValue(self::untyped('up')))->oneOf(['asc', 'desc', true, null, new \stdClass()]);
    }

    public function testNullPasses(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertSame($null, $null->oneOf(['asc']));
    }

    public function testKeysOfAllowedAreIgnored(): void
    {
        $sort = new UntrustedValue('name');

        self::assertSame($sort, $sort->oneOf(['by-name' => 'name', 'by-date' => 'date']));
    }

    /**
     * Hides the value's type from PHPStan, which otherwise reports calls that can only throw.
     */
    private static function untyped(mixed $value): mixed
    {
        return $value;
    }
}
