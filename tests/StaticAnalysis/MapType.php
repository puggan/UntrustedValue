<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the types of yieldMap(), listMap() and arrayMap().
 *
 * @param UntrustedValue<mixed> $loose
 * @param UntrustedStrictValue<mixed> $strict
 */
function mapType(UntrustedValue $loose, UntrustedStrictValue $strict): void
{
    assertType(
        'Generator<mixed, Puggan\UntrustedValue\UntrustedStrictValue<int>, mixed, void>',
        $loose->yieldMap(static fn(UntrustedValue $item) => $item->asInt(false)),
    );
    assertType(
        'list<Puggan\UntrustedValue\UntrustedValue<int>>',
        $loose->listMap(static fn(UntrustedValue $item) => $item->int(false)),
    );
    assertType(
        'array<Puggan\UntrustedValue\UntrustedStrictValue<string>>',
        $strict->arrayMap(static fn(UntrustedStrictValue $item, int|string $key) => (string) $key),
    );
}
