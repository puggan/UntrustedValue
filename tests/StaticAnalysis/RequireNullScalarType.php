<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check that requireNull() and requireScalar() narrow TValue.
 *
 * @param UntrustedValue<mixed> $mixed
 * @param UntrustedStrictValue<int<0, 5>|list<int>|null> $smallIntOrList
 */
function requireNullScalarType(UntrustedValue $mixed, UntrustedStrictValue $smallIntOrList): void
{
    assertType('Puggan\UntrustedValue\UntrustedValue<null>', $mixed->requireNull());
    assertType('Puggan\UntrustedValue\UntrustedValue<bool|float|int|string|null>', $mixed->requireScalar());
    assertType('Puggan\UntrustedValue\UntrustedValue<bool|float|int|string>', $mixed->requireScalar(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<null>', $smallIntOrList->requireNull());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, 5>|null>', $smallIntOrList->requireScalar());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, 5>>', $smallIntOrList->requireScalar(false));
}
