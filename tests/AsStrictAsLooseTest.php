<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests;

use PHPUnit\Framework\TestCase;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

final class AsStrictAsLooseTest extends TestCase
{
    public function testLooseAsLooseIsTheSameObject(): void
    {
        $loose = new UntrustedValue('42', 'user.age');

        self::assertSame($loose, $loose->asLoose());
    }

    public function testStrictAsStrictIsTheSameObject(): void
    {
        $strict = new UntrustedStrictValue('42', 'user.age');

        self::assertSame($strict, $strict->asStrict());
    }

    public function testLooseAsStrictCopiesValueAndPath(): void
    {
        $value = new \stdClass();
        $strict = (new UntrustedValue($value, 'user'))->asStrict();

        self::assertSame($value, $strict->value);
        self::assertSame('user', $strict->path);
    }

    public function testStrictAsLooseCopiesValueAndPath(): void
    {
        $value = new \stdClass();
        $loose = (new UntrustedStrictValue($value, 'user'))->asLoose();

        self::assertSame($value, $loose->value);
        self::assertSame('user', $loose->path);
    }

    public function testSwitchingChangesHowValuesAreConverted(): void
    {
        $loose = new UntrustedValue('42');

        self::assertSame(42, $loose->int());
        self::assertFalse($loose->asStrict()->isInt());
        self::assertSame(42, $loose->asStrict()->asLoose()->int());
    }
}
