<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the conditional return type of float().
 *
 * @param UntrustedValue<mixed> $value
 * @param UntrustedStrictValue<mixed> $strict
 * @param AbstractUntrustedValue<mixed> $either
 */
function floatReturnType(UntrustedValue $value, UntrustedStrictValue $strict, AbstractUntrustedValue $either): void
{
    assertType('float|null', $value->float());
    assertType('float|null', $value->float(true));
    assertType('float', $value->float(false));
    assertType('float|null', $strict->float());
    assertType('float', $strict->float(nullable: false));
    assertType('float|null', $either->float());
    assertType('float', $either->float(false));
}
