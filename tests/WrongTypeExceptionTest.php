<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\WrongTypeException;

final class WrongTypeExceptionTest extends TestCase
{
    public function testExposesItsValues(): void
    {
        $exception = new WrongTypeException('Expected int', 'int', 'string', 'user.age');

        self::assertSame('Expected int', $exception->getMessage());
        self::assertSame('int', $exception->expectedType);
        self::assertSame('string', $exception->givenType);
        self::assertSame('user.age', $exception->path);
    }

    public function testIsARuntimeException(): void
    {
        self::assertInstanceOf(\RuntimeException::class, new WrongTypeException('', '', '', ''));
    }

    public function testKeepsThePreviousException(): void
    {
        $previous = new \LogicException('inner');

        self::assertSame($previous, new WrongTypeException('', '', '', '', $previous)->getPrevious());
    }

    public function testSerializesToJson(): void
    {
        $exception = new WrongTypeException('Expected int', 'int', 'string', 'user.age');

        self::assertSame(
            '{"message":"Expected int","expectedType":"int","givenType":"string","path":"user.age"}',
            json_encode($exception, JSON_THROW_ON_ERROR),
        );
    }
}
