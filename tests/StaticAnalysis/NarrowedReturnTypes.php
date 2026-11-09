<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check how int(), float() and string() narrow their
 * return types using TValue.
 *
 * @param UntrustedValue<mixed> $mixed
 * @param UntrustedValue<int<0, max>> $positiveInt
 * @param UntrustedValue<?int> $nullableInt
 * @param UntrustedValue<int|string> $intOrString
 * @param UntrustedValue<int|string|null> $nullableIntOrString
 * @param UntrustedValue<non-empty-string> $nonEmptyString
 * @param UntrustedValue<?float> $nullableFloat
 */
function narrowedReturnTypes(
    UntrustedValue $mixed,
    UntrustedValue $positiveInt,
    UntrustedValue $nullableInt,
    UntrustedValue $intOrString,
    UntrustedValue $nullableIntOrString,
    UntrustedValue $nonEmptyString,
    UntrustedValue $nullableFloat,
    bool $unknown,
): void {
    // int(): non-strict nullable, non-strict not nullable, strict nullable, strict not nullable
    assertType('int|null', $mixed->int());
    assertType('int', $mixed->int(false));
    assertType('int|null', $mixed->int(strict: true));
    assertType('int', $mixed->int(false, true));

    assertType('int<0, max>', $positiveInt->int());
    assertType('int<0, max>', $positiveInt->int(false));
    assertType('int<0, max>', $positiveInt->int(strict: true));
    assertType('int<0, max>', $positiveInt->int(false, true));

    assertType('int|null', $nullableInt->int());
    assertType('int', $nullableInt->int(false));
    assertType('int|null', $nullableInt->int(strict: true));
    assertType('int', $nullableInt->int(false, true));

    assertType('int', $intOrString->int());
    assertType('int', $intOrString->int(false));
    assertType('int', $intOrString->int(strict: true));
    assertType('int', $intOrString->int(false, true));

    assertType('int|null', $nullableIntOrString->int());
    assertType('int', $nullableIntOrString->int(false, true));

    assertType('int', $nonEmptyString->int());
    assertType('*NEVER*', $nonEmptyString->int(strict: true));

    // An unknown $strict could be null, falling back to the constructor, so it uses the non-strict types.
    assertType('int<0, max>', $positiveInt->int(strict: $unknown));
    assertType('int', $mixed->int(false, $unknown));

    // string()
    assertType('string|null', $mixed->string());
    assertType('string', $mixed->string(false));
    assertType('non-empty-string', $nonEmptyString->string());
    assertType('non-empty-string', $nonEmptyString->string(false, true));
    assertType('string', $positiveInt->string());
    assertType('*NEVER*', $positiveInt->string(strict: true));
    assertType('string', $intOrString->string());
    assertType('string', $intOrString->string(strict: true));
    assertType('string|null', $nullableIntOrString->string(strict: true));

    // float()
    assertType('float|null', $mixed->float());
    assertType('float', $mixed->float(false));
    assertType('float|null', $nullableFloat->float());
    assertType('float', $nullableFloat->float(false));
    assertType('float', $positiveInt->float());
    assertType('float', $positiveInt->float(strict: true));
    assertType('float', $intOrString->float(strict: true));
}
