<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of yieldFlatMap().
 *
 * @param UntrustedValue<mixed> $value
 */
function flatMapType(UntrustedValue $value): void
{
    assertType(
        'Generator<mixed, Puggan\UntrustedValue\UntrustedValue<mixed>, mixed, void>',
        $value->yieldFlatMap(static fn(UntrustedValue $item) => $item->property('tags')),
    );
    assertType(
        'Generator<int, int, mixed, void>',
        $value->yieldFlatMap(static fn(UntrustedValue $item): array => [1, 2]),
    );
}
