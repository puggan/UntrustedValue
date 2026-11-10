<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the types of the raw map methods.
 *
 * @param UntrustedValue<mixed> $loose
 * @param UntrustedStrictValue<mixed> $strict
 */
function rawMapType(UntrustedValue $loose, UntrustedStrictValue $strict): void
{
    assertType('Generator<mixed, int, mixed, void>', $loose->yieldRawMap(static fn(UntrustedValue $item) => $item->int(false)));
    assertType('list<string|null>', $loose->listRawMap(static fn(UntrustedValue $item) => $item->string()));
    assertType('array<float>', $strict->arrayRawMap(static fn(UntrustedStrictValue $item) => $item->float(false)));
    assertType(
        'list<Puggan\UntrustedValue\UntrustedStrictValue<int>>',
        $loose->listRawMap(static fn(UntrustedValue $item) => $item->asInt(false)),
    );
}
