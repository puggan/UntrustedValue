<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the conditional return type of int().
 *
 * @param UntrustedValue<mixed> $value
 * @param UntrustedStrictValue<mixed> $strict
 * @param AbstractUntrustedValue<mixed> $either
 */
function intReturnType(UntrustedValue $value, UntrustedStrictValue $strict, AbstractUntrustedValue $either): void
{
    assertType('int|null', $value->int());
    assertType('int|null', $value->int(true));
    assertType('int', $value->int(false));
    assertType('int|null', $strict->int());
    assertType('int', $strict->int(nullable: false));
    assertType('int|null', $either->int());
    assertType('int', $either->int(false));
}
