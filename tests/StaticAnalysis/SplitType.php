<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of split().
 *
 * @param UntrustedValue<mixed> $mixed
 * @param UntrustedStrictValue<string> $string
 */
function splitType(UntrustedValue $mixed, UntrustedStrictValue $string): void
{
    assertType('Puggan\UntrustedValue\UntrustedValue<list<string>|null>', $mixed->split(','));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<list<string>>', $string->split(','));
}
