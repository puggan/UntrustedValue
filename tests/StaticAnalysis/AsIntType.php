<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of asInt().
 *
 * @param UntrustedValue<mixed> $loose
 * @param UntrustedStrictValue<mixed> $strict
 */
function asIntType(UntrustedValue $loose, UntrustedStrictValue $strict): void
{
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int|null>', $loose->asInt());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int>', $loose->asInt(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int>', $strict->asInt(nullable: false));
    assertType('int', $loose->asInt(false)->int());
    assertType('int|null', $loose->asInt()->int());
}
