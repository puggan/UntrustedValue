<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of asInt().
 *
 * @param UntrustedValue<mixed> $loose
 * @param UntrustedStrictValue<mixed> $strict
 * @param UntrustedStrictValue<string|int<0, 5>> $strictSmallIntOrString
 * @param UntrustedStrictValue<string|int<0, 5>|null> $strictNullableSmallIntOrString
 * @param UntrustedValue<string|int<0, 5>> $looseSmallIntOrString
 * @param UntrustedValue<int<0, 5>> $looseSmallInt
 * @param AbstractUntrustedValue<int<0, 5>> $eitherSmallInt
 */
function asIntType(
    UntrustedValue $loose,
    UntrustedStrictValue $strict,
    UntrustedStrictValue $strictSmallIntOrString,
    UntrustedStrictValue $strictNullableSmallIntOrString,
    UntrustedValue $looseSmallIntOrString,
    UntrustedValue $looseSmallInt,
    AbstractUntrustedValue $eitherSmallInt,
): void {
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int|null>', $loose->asInt());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int>', $loose->asInt(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int|null>', $strict->asInt());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int>', $strict->asInt(nullable: false));

    // Strict: only the int part of TValue can come through.
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, 5>>', $strictSmallIntOrString->asInt(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, 5>>', $strictSmallIntOrString->asInt());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, 5>|null>', $strictNullableSmallIntOrString->asInt());
    assertType('int<0, 5>', $strictSmallIntOrString->asInt(false)->int());

    // Loose: a string can convert to any int, so TValue is only kept when it is all int.
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int>', $looseSmallIntOrString->asInt(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, 5>>', $looseSmallInt->asInt(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<int<0, 5>>', $eitherSmallInt->asInt());
}

/**
 * TValue is covariant, so a narrower value fits where a wider one is expected.
 *
 * @param UntrustedStrictValue<int<0, 5>> $smallInt
 *
 * @return UntrustedStrictValue<int>
 */
function covariantTValue(UntrustedStrictValue $smallInt): UntrustedStrictValue
{
    return $smallInt;
}
