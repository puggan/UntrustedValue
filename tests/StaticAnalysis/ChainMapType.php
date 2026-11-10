<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of map().
 *
 * @param UntrustedValue<mixed> $loose
 * @param UntrustedStrictValue<mixed> $strict
 */
function chainMapType(UntrustedValue $loose, UntrustedStrictValue $strict): void
{
    assertType('Puggan\UntrustedValue\UntrustedValue<Generator<mixed, Puggan\UntrustedValue\UntrustedValue<int>, mixed, void>>', $loose->map(static fn(UntrustedValue $item): int => 1));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<Generator<mixed, Puggan\UntrustedValue\UntrustedStrictValue<int>, mixed, void>>', $strict->map(static fn(UntrustedStrictValue $item) => $item->asInt(false)));
    assertType('list<int>', $loose->map(static fn(UntrustedValue $item): int => 1)->filter(static fn(UntrustedValue $item): bool => true)->listInt(false));
}
