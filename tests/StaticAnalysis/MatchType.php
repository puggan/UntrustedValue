<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check that match() and notMatch() keep the type.
 *
 * @param UntrustedValue<int<0, 999>> $smallInt
 * @param UntrustedStrictValue<non-empty-string> $nonEmptyString
 */
function matchType(UntrustedValue $smallInt, UntrustedStrictValue $nonEmptyString): void
{
    assertType('Puggan\\UntrustedValue\\UntrustedValue<int<0, 999>>', $smallInt->match('/^7/'));
    assertType('Puggan\\UntrustedValue\\UntrustedStrictValue<non-empty-string>', $nonEmptyString->notMatch('/a/'));
}
