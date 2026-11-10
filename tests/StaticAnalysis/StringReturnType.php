<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the conditional return type of string().
 *
 * @param UntrustedValue<mixed> $value
 * @param UntrustedStrictValue<mixed> $strict
 * @param AbstractUntrustedValue<mixed> $either
 */
function stringReturnType(UntrustedValue $value, UntrustedStrictValue $strict, AbstractUntrustedValue $either): void
{
    assertType('string|null', $value->string());
    assertType('string|null', $value->string(true));
    assertType('string', $value->string(false));
    assertType('string|null', $strict->string());
    assertType('string', $strict->string(nullable: false));
    assertType('string|null', $either->string());
    assertType('string', $either->string(false));
}
