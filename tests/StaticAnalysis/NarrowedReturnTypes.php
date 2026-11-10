<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check how int(), float() and string() narrow their
 * return types using TValue, in both classes.
 *
 * @param UntrustedValue<mixed> $mixed
 * @param UntrustedStrictValue<mixed> $strictMixed
 * @param UntrustedValue<int<0, max>> $positiveInt
 * @param UntrustedStrictValue<int<0, max>> $strictPositiveInt
 * @param UntrustedValue<?int> $nullableInt
 * @param UntrustedStrictValue<?int> $strictNullableInt
 * @param UntrustedValue<int|string> $intOrString
 * @param UntrustedStrictValue<int|string> $strictIntOrString
 * @param UntrustedValue<int|string|null> $nullableIntOrString
 * @param UntrustedStrictValue<int|string|null> $strictNullableIntOrString
 * @param UntrustedValue<non-empty-string> $nonEmptyString
 * @param UntrustedStrictValue<non-empty-string> $strictNonEmptyString
 * @param UntrustedValue<?float> $nullableFloat
 * @param AbstractUntrustedValue<int<0, max>> $eitherPositiveInt
 * @param AbstractUntrustedValue<int|string> $eitherIntOrString
 */
function narrowedReturnTypes(
    UntrustedValue $mixed,
    UntrustedStrictValue $strictMixed,
    UntrustedValue $positiveInt,
    UntrustedStrictValue $strictPositiveInt,
    UntrustedValue $nullableInt,
    UntrustedStrictValue $strictNullableInt,
    UntrustedValue $intOrString,
    UntrustedStrictValue $strictIntOrString,
    UntrustedValue $nullableIntOrString,
    UntrustedStrictValue $strictNullableIntOrString,
    UntrustedValue $nonEmptyString,
    UntrustedStrictValue $strictNonEmptyString,
    UntrustedValue $nullableFloat,
    AbstractUntrustedValue $eitherPositiveInt,
    AbstractUntrustedValue $eitherIntOrString,
): void {
    // int(): nullable, then not nullable; UntrustedValue, then UntrustedStrictValue
    assertType('int|null', $mixed->int());
    assertType('int', $mixed->int(false));
    assertType('int|null', $strictMixed->int());
    assertType('int', $strictMixed->int(false));

    assertType('int<0, max>', $positiveInt->int());
    assertType('int<0, max>', $positiveInt->int(false));
    assertType('int<0, max>', $strictPositiveInt->int());
    assertType('int<0, max>', $strictPositiveInt->int(false));

    assertType('int|null', $nullableInt->int());
    assertType('int', $nullableInt->int(false));
    assertType('int|null', $strictNullableInt->int());
    assertType('int', $strictNullableInt->int(false));

    assertType('int', $intOrString->int());
    assertType('int', $intOrString->int(false));
    assertType('int', $strictIntOrString->int());
    assertType('int', $strictIntOrString->int(false));

    assertType('int|null', $nullableIntOrString->int());
    assertType('int|null', $strictNullableIntOrString->int());
    assertType('int', $strictNullableIntOrString->int(false));

    assertType('int', $nonEmptyString->int());

    // Typed as the abstract class, the UntrustedValue types apply.
    assertType('int<0, max>', $eitherPositiveInt->int());
    assertType('int', $eitherIntOrString->int());

    // string()
    assertType('string|null', $mixed->string());
    assertType('string', $mixed->string(false));
    assertType('non-empty-string', $nonEmptyString->string());
    assertType('non-empty-string', $strictNonEmptyString->string(false));
    assertType('string', $positiveInt->string());
    assertType('string', $intOrString->string());
    assertType('string', $strictIntOrString->string());
    assertType('string|null', $strictNullableIntOrString->string());

    // float()
    assertType('float|null', $mixed->float());
    assertType('float', $mixed->float(false));
    assertType('float|null', $nullableFloat->float());
    assertType('float', $nullableFloat->float(false));
    assertType('float', $positiveInt->float());
    assertType('float', $strictPositiveInt->float());
    assertType('float', $strictIntOrString->float());
}
