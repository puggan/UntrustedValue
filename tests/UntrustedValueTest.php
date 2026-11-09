<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedValue;

final class UntrustedValueTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        self::assertInstanceOf(UntrustedValue::class, new UntrustedValue());
    }
}
