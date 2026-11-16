<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of filter().
 *
 * @param UntrustedValue<list<int>> $loose
 * @param UntrustedStrictValue<mixed> $strict
 */
function filterType(UntrustedValue $loose, UntrustedStrictValue $strict): void
{
    assertType('Puggan\UntrustedValue\UntrustedValue<Puggan\UntrustedValue\LazyIterable<mixed, Puggan\UntrustedValue\UntrustedValue<mixed>>>', $loose->filter(static fn(UntrustedValue $item): bool => true));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<Puggan\UntrustedValue\LazyIterable<mixed, Puggan\UntrustedValue\UntrustedStrictValue<mixed>>>', $strict->filter(static fn(UntrustedStrictValue $item): bool => true));
    assertType('list<int>', $loose->filter(static fn(UntrustedValue $item): bool => true)->listInt(false));
}
