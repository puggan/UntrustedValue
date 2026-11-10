<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check that range() keeps the type.
 *
 * @param UntrustedStrictValue<int<0, 5>> $smallInt
 * @param UntrustedValue<mixed> $mixed
 */
function rangeType(UntrustedStrictValue $smallInt, UntrustedValue $mixed): void
{
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, 5>>', $smallInt->range(1, 4));
    assertType('Puggan\UntrustedValue\UntrustedValue<mixed>', $mixed->range('a'));
}
