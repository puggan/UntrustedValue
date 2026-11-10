<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of trim().
 *
 * @param UntrustedValue<mixed> $mixed
 * @param UntrustedValue<non-empty-string> $nonEmptyString
 * @param UntrustedStrictValue<int|non-empty-string|null> $nullableIntOrString
 * @param UntrustedValue<int<0, 5>> $smallInt
 * @param UntrustedValue<array{id: int}> $shape
 */
function trimType(
    UntrustedValue $mixed,
    UntrustedValue $nonEmptyString,
    UntrustedStrictValue $nullableIntOrString,
    UntrustedValue $smallInt,
    UntrustedValue $shape,
): void {
    // Every type, so the same as mixed.
    assertType('Puggan\UntrustedValue\UntrustedValue<array|bool|float|int|object|resource|string|null>', $mixed->trim());
    assertType('Puggan\UntrustedValue\UntrustedValue<string>', $nonEmptyString->trim());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int|string|null>', $nullableIntOrString->trim());
    assertType('Puggan\UntrustedValue\UntrustedValue<int<0, 5>>', $smallInt->trim());
    assertType('Puggan\UntrustedValue\UntrustedValue<array{id: int}>', $shape->trim());
}
