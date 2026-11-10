<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\WrongTypeException;

final class TypedCollectionTest extends TestCase
{
    public function testListsConvertEachItem(): void
    {
        $json = UntrustedValue::fromJson('{"a":"1","b":2.0,"c":null}');

        self::assertSame([1, 2, null], $json->listInt());
        self::assertSame([1.0, 2.0, null], $json->listFloat());
        self::assertSame(['1', '2', null], $json->listString());
        self::assertSame(['1', 2.0, null], $json->listScalar());
    }

    public function testArraysKeepKeys(): void
    {
        $json = UntrustedValue::fromJson('{"a":"1","b":2}');

        self::assertSame(['a' => 1, 'b' => 2], $json->arrayInt());
        self::assertSame(['a' => 1.0, 'b' => 2.0], $json->arrayFloat());
        self::assertSame(['a' => '1', 'b' => '2'], $json->arrayString());
        self::assertSame(['a' => '1', 'b' => 2], $json->arrayScalar());
    }

    public function testGeneratorsKeepKeysAndConvertLazily(): void
    {
        $generator = new UntrustedValue(['a' => '1', 'b' => 'x'])->yieldInt();

        self::assertSame('a', $generator->key());
        self::assertSame(1, $generator->current());

        $this->expectException(WrongTypeException::class);
        $generator->next();
    }

    public function testAllYieldMethodsKeepKeys(): void
    {
        $untrusted = new UntrustedValue(['x' => 1]);

        self::assertSame(['x' => 1], iterator_to_array($untrusted->yieldInt()));
        self::assertSame(['x' => 1.0], iterator_to_array($untrusted->yieldFloat()));
        self::assertSame(['x' => '1'], iterator_to_array($untrusted->yieldString()));
        self::assertSame(['x' => 1], iterator_to_array($untrusted->yieldScalar()));
    }

    public function testNullItemsThrowWhenNotNullable(): void
    {
        try {
            new UntrustedValue([1, null], 'ids')->listInt(false);
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('ids.1', $exception->path);
            self::assertSame('null', $exception->givenType);
        }
    }

    public function testStrictItemsStayStrict(): void
    {
        try {
            new UntrustedStrictValue([1, '2'], 'ids')->listInt();
            self::fail('Expected a WrongTypeException');
        } catch (WrongTypeException $exception) {
            self::assertSame('ids.1', $exception->path);
            self::assertSame('string', $exception->givenType);
        }
    }

    public function testScalarRejectsStructures(): void
    {
        $this->expectException(WrongTypeException::class);
        new UntrustedValue([1, [2]])->listScalar();
    }

    public function testNullContainerThrows(): void
    {
        $null = UntrustedValue::fromJson('null');

        foreach (['yieldInt', 'listFloat', 'arrayString', 'listScalar'] as $method) {
            try {
                $null->{$method}();
                self::fail("{$method}() should have thrown");
            } catch (WrongTypeException $exception) {
                self::assertSame('null', $exception->givenType);
            }
        }
    }
}
