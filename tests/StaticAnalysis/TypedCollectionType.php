<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the types of the typed collection methods.
 *
 * @param UntrustedValue<mixed> $value
 */
function typedCollectionType(UntrustedValue $value): void
{
    assertType('Generator<mixed, int|null, mixed, void>', $value->yieldInt());
    assertType('Generator<mixed, int, mixed, void>', $value->yieldInt(false));
    assertType('list<float|null>', $value->listFloat());
    assertType('list<float>', $value->listFloat(false));
    assertType('array<string|null>', $value->arrayString());
    assertType('array<string>', $value->arrayString(false));
    assertType('list<bool|float|int|string|null>', $value->listScalar());
    assertType('array<bool|float|int|string>', $value->arrayScalar(false));
    assertType('Generator<mixed, bool|float|int|string, mixed, void>', $value->yieldScalar(false));
}
