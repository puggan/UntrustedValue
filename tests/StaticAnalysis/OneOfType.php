<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check that oneOf() narrows TValue.
 *
 * @param UntrustedValue<mixed> $mixed
 * @param UntrustedStrictValue<string> $string
 */
function oneOfType(UntrustedValue $mixed, UntrustedStrictValue $string): void
{
    // A method template widens literals, so the allowed values narrow TValue to their type.
    assertType('Puggan\UntrustedValue\UntrustedValue<string|null>', $mixed->oneOf(['asc', 'desc']));
    assertType('string', $string->oneOf(['asc', 'desc'])->string());
    assertType('Puggan\UntrustedValue\UntrustedValue<int|null>', $mixed->oneOf([1, 2]));
}
