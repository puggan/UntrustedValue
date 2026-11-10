<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check that asStrict() and asLoose() keep TValue.
 *
 * @param UntrustedValue<int<0, max>> $loose
 * @param UntrustedStrictValue<int<0, max>> $strict
 * @param AbstractUntrustedValue<int<0, max>> $either
 */
function asStrictAsLooseType(UntrustedValue $loose, UntrustedStrictValue $strict, AbstractUntrustedValue $either): void
{
    assertType('Puggan\UntrustedValue\UntrustedValue<int<0, max>>', $loose->asLoose());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, max>>', $loose->asStrict());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, max>>', $strict->asStrict());
    assertType('Puggan\UntrustedValue\UntrustedValue<int<0, max>>', $strict->asLoose());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, max>>', $either->asStrict());
    assertType('Puggan\UntrustedValue\UntrustedValue<int<0, max>>', $either->asLoose());
}
