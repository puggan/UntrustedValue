<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the types of the iteration methods.
 *
 * @param UntrustedValue<mixed> $loose
 * @param UntrustedStrictValue<mixed> $strict
 */
function iterateType(UntrustedValue $loose, UntrustedStrictValue $strict): void
{
    assertType('Generator<int, Puggan\UntrustedValue\UntrustedValue<mixed>, mixed, void>', $loose->yieldList());
    assertType('Generator<mixed, Puggan\UntrustedValue\UntrustedStrictValue<mixed>, mixed, void>', $strict->yieldArray());
    assertType('list<Puggan\UntrustedValue\UntrustedValue<mixed>>', $loose->list());
    assertType('array<Puggan\UntrustedValue\UntrustedStrictValue<mixed>>', $strict->array());

    foreach ($strict->yieldList() as $item) {
        assertType('Puggan\UntrustedValue\UntrustedStrictValue<mixed>', $item);
    }
}
