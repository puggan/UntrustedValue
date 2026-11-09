<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check that int(), float() and string() return TValue
 * when it already has the right type, and fall back otherwise.
 *
 * @param UntrustedValue<int<0, max>> $positiveInt
 * @param UntrustedValue<float> $float
 * @param UntrustedValue<non-empty-string> $nonEmptyString
 * @param UntrustedValue<int|string> $intOrString
 * @param UntrustedValue<?int> $nullableInt
 */
function narrowedReturnTypes(
    UntrustedValue $positiveInt,
    UntrustedValue $float,
    UntrustedValue $nonEmptyString,
    UntrustedValue $intOrString,
    UntrustedValue $nullableInt,
): void {
    assertType('int<0, max>', $positiveInt->int());
    assertType('int<0, max>', $positiveInt->int(false));
    assertType('float', $positiveInt->float());
    assertType('string|null', $positiveInt->string());
    assertType('string', $positiveInt->string(false));

    assertType('float', $float->float());
    assertType('int|null', $float->int());

    assertType('non-empty-string', $nonEmptyString->string());
    assertType('int|null', $nonEmptyString->int());

    assertType('int|null', $intOrString->int());
    assertType('int', $intOrString->int(false));
    assertType('string|null', $intOrString->string());

    assertType('int|null', $nullableInt->int());
    assertType('int', $nullableInt->int(false));
}
