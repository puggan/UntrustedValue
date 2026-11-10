<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedValueException;

final class InvalidTypeExceptionTest extends TestCase
{
    public function testExposesItsValues(): void
    {
        $exception = new InvalidTypeException('Not an email', 'email', 'user.email');

        self::assertSame('Not an email', $exception->getMessage());
        self::assertSame('email', $exception->filter);
        self::assertSame('user.email', $exception->path);
    }

    public function testCanBeCaughtAsUntrustedValueException(): void
    {
        self::assertInstanceOf(UntrustedValueException::class, new InvalidTypeException('', '', ''));
    }

    public function testIsARuntimeException(): void
    {
        self::assertInstanceOf(\RuntimeException::class, new InvalidTypeException('', '', ''));
    }

    public function testKeepsThePreviousException(): void
    {
        $previous = new \LogicException('inner');

        self::assertSame($previous, (new InvalidTypeException('', '', '', $previous))->getPrevious());
    }

    public function testSerializesToJson(): void
    {
        $exception = new InvalidTypeException('Not an email', 'email', 'user.email');

        self::assertSame(
            '{"message":"Not an email","filter":"email","path":"user.email"}',
            json_encode($exception, JSON_THROW_ON_ERROR),
        );
    }
}
