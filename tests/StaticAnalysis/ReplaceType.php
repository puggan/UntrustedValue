<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the types of replace(), default() and blankDefault().
 *
 * @param UntrustedStrictValue<int|null> $nullableInt
 * @param UntrustedValue<string> $string
 */
function replaceType(UntrustedStrictValue $nullableInt, UntrustedValue $string): void
{
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int>', $nullableInt->default(1));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int|string>', $nullableInt->default('none'));
    assertType('Puggan\UntrustedValue\UntrustedValue<string|null>', $string->blankDefault());
    assertType('Puggan\UntrustedValue\UntrustedValue<int|string>', $string->replace('N/A', 0));
    assertType('int', $nullableInt->default(1)->int());
}
