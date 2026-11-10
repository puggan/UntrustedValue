<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class JsonTest extends TestCase
{
    public function testDecodesAJsonStringFieldKeepingThePath(): void
    {
        $id = UntrustedValue::fromJson('{"payload":"{\"id\":\"7\"}"}')->property('payload')->json()->property('id');

        self::assertSame(7, $id->int(false));
        self::assertSame('payload.id', $id->path);
    }

    public function testObjectsBecomeStdClass(): void
    {
        self::assertInstanceOf(\stdClass::class, new UntrustedValue('{"a":1}')->json()->value);
    }

    public function testInvalidJsonNamesThePath(): void
    {
        try {
            new UntrustedValue('{"broken":', 'payload')->json();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('json', $exception->expectedType);
            self::assertSame('payload', $exception->path);
            self::assertStringStartsWith('Invalid JSON at payload: ', $exception->getMessage());
            self::assertInstanceOf(\JsonException::class, $exception->getPrevious());
        }
    }

    public function testNullReturnsThis(): void
    {
        $null = UntrustedValue::fromJson('null');

        self::assertSame($null, $null->json());
    }

    public function testKeepsTheClass(): void
    {
        self::assertSame(UntrustedStrictValue::class, new UntrustedStrictValue('[1]')->json()::class);
    }

    public function testStrictValueRequiresAString(): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedStrictValue(42)->json();
    }

    public function testLooseValueDecodesAConvertedScalar(): void
    {
        self::assertSame(42, new UntrustedValue(42)->json()->value);
    }

    public function testFromJsonMessageIsUnchanged(): void
    {
        $this->expectExceptionMessageMatches('/^Invalid JSON: /');
        UntrustedValue::fromJson('{');
    }
}
