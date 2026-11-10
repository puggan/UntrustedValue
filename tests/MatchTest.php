<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\InvalidTypeException;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class MatchTest extends TestCase
{
    public function testMatchReturnsThisOnAMatch(): void
    {
        $untrusted = new UntrustedStrictValue('abc-123');

        self::assertSame($untrusted, $untrusted->match('/^[a-z]+-\d+$/'));
    }

    public function testNotMatchReturnsThisWithoutAMatch(): void
    {
        $untrusted = new UntrustedStrictValue('abc');

        self::assertSame($untrusted, $untrusted->notMatch('/\d/'));
    }

    public function testMatchThrowsWithoutAMatch(): void
    {
        try {
            new UntrustedValue('abc', 'user.code')->match('/^\d+$/');
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('match', $exception->filter);
            self::assertSame('user.code', $exception->path);
            self::assertSame('Expected a match for /^\d+$/ at user.code', $exception->getMessage());
        }
    }

    public function testNotMatchThrowsOnAMatch(): void
    {
        try {
            new UntrustedValue('<script>', 'comment')->notMatch('/[<>]/');
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('notMatch', $exception->filter);
            self::assertSame('comment', $exception->path);
            self::assertSame('Expected no match for /[<>]/ at comment', $exception->getMessage());
        }
    }

    public function testLooseValueChecksTheConvertedString(): void
    {
        $untrusted = new UntrustedValue(700);

        self::assertSame($untrusted, $untrusted->match('/^7/'));
        self::assertSame($untrusted, $untrusted->notMatch('/^8/'));

        $float = new UntrustedValue(4.5);
        self::assertSame($float, $float->match('/^4\.5$/'));
    }

    public function testStrictMatchRequiresAString(): void
    {
        try {
            new UntrustedStrictValue(self::untyped(700))->match('/^7/');
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('string', $exception->expectedType);
            self::assertSame('int', $exception->givenType);
        }
    }

    public function testMatchThrowsWithoutAString(): void
    {
        foreach ([null, true, ['a'], new \stdClass()] as $value) {
            try {
                new UntrustedValue(self::untyped($value))->match('/a/');
                self::fail('Expected a WrongTypeException');
            } catch (WrongTypeException $exception) {
                self::assertSame('string', $exception->expectedType);
            }
        }
    }

    public function testNotMatchPassesWithoutAString(): void
    {
        foreach ([null, true, [], ['7'], new \stdClass()] as $value) {
            $untrusted = new UntrustedValue(self::untyped($value));

            self::assertSame($untrusted, $untrusted->notMatch('/^7/'));
        }

        $strictInt = new UntrustedStrictValue(700);
        self::assertSame($strictInt, $strictInt->notMatch('/^7/'));
    }

    public function testInvalidPatternThrowsInvalidArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No ending delimiter');
        new UntrustedValue('abc')->match('/abc');
    }

    public function testPcreFailureOnTheValueThrowsInvalidType(): void
    {
        try {
            new UntrustedValue("\xFF", 'name')->match('/./u');
            self::fail('Expected an InvalidTypeException');
        } catch (InvalidTypeException $exception) {
            self::assertSame('match', $exception->filter);
            self::assertSame('name', $exception->path);
            self::assertStringContainsString('Malformed UTF-8', $exception->getMessage());
        }
    }

    /**
     * Hides the value's type from PHPStan, which otherwise reports calls that can only throw.
     */
    private static function untyped(mixed $value): mixed
    {
        return $value;
    }
}
