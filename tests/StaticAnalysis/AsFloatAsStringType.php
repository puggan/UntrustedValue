<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return types of asFloat() and asString().
 *
 * @param UntrustedValue<mixed> $loose
 * @param UntrustedStrictValue<mixed> $strict
 * @param UntrustedStrictValue<0.5|1.5|int> $strictHalvesOrInt
 * @param UntrustedValue<0.5|1.5> $looseHalves
 * @param UntrustedStrictValue<int|non-empty-string> $strictIntOrNonEmptyString
 * @param UntrustedStrictValue<int|non-empty-string|null> $strictNullableIntOrNonEmptyString
 * @param UntrustedValue<int|non-empty-string> $looseIntOrNonEmptyString
 * @param UntrustedValue<non-empty-string> $looseNonEmptyString
 * @param AbstractUntrustedValue<non-empty-string> $eitherNonEmptyString
 */
function asFloatAsStringType(
    UntrustedValue $loose,
    UntrustedStrictValue $strict,
    UntrustedStrictValue $strictHalvesOrInt,
    UntrustedValue $looseHalves,
    UntrustedStrictValue $strictIntOrNonEmptyString,
    UntrustedStrictValue $strictNullableIntOrNonEmptyString,
    UntrustedValue $looseIntOrNonEmptyString,
    UntrustedValue $looseNonEmptyString,
    AbstractUntrustedValue $eitherNonEmptyString,
): void {
    // asFloat(): the same in both classes, as strict float() still widens ints.
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<float|null>', $loose->asFloat());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<float>', $loose->asFloat(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<float|null>', $strict->asFloat());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<float>', $strictHalvesOrInt->asFloat(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<0.5|1.5>', $looseHalves->asFloat(false));

    // asString(): strict keeps only the string part of TValue.
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<string|null>', $loose->asString());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<string>', $loose->asString(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<string|null>', $strict->asString());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<non-empty-string>', $strictIntOrNonEmptyString->asString(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<non-empty-string>', $strictIntOrNonEmptyString->asString());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<non-empty-string|null>', $strictNullableIntOrNonEmptyString->asString());
    assertType('non-empty-string', $strictIntOrNonEmptyString->asString(false)->string());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<string>', $looseIntOrNonEmptyString->asString(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<non-empty-string>', $looseNonEmptyString->asString(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<non-empty-string>', $eitherNonEmptyString->asString());
}
