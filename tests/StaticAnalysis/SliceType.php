<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of slice().
 *
 * @param UntrustedValue<mixed> $loose
 * @param UntrustedStrictValue<mixed> $strict
 */
function sliceType(UntrustedValue $loose, UntrustedStrictValue $strict): void
{
    assertType('Puggan\UntrustedValue\UntrustedValue<Puggan\UntrustedValue\LazyIterable<mixed, Puggan\UntrustedValue\UntrustedValue<mixed>>>', $loose->slice(0, 3));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<Puggan\UntrustedValue\LazyIterable<mixed, Puggan\UntrustedValue\UntrustedStrictValue<mixed>>>', $strict->slice(2));
    assertType('list<int>', $loose->slice(0, 3)->listInt(false));
}
