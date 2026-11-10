<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check what foreach gives.
 *
 * @param UntrustedValue<mixed> $loose
 * @param UntrustedStrictValue<mixed> $strict
 */
function foreachType(UntrustedValue $loose, UntrustedStrictValue $strict): void
{
    foreach ($loose as $key => $item) {
        assertType('mixed', $key);
        assertType('Puggan\UntrustedValue\UntrustedValue<mixed>', $item);
    }

    foreach ($strict as $item) {
        assertType('Puggan\UntrustedValue\UntrustedStrictValue<mixed>', $item);
    }
}
