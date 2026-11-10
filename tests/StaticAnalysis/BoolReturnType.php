<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return types of bool() and asBool().
 *
 * @param UntrustedValue<mixed> $value
 * @param UntrustedStrictValue<mixed> $strict
 * @param AbstractUntrustedValue<mixed> $either
 * @param UntrustedStrictValue<bool|string> $boolOrString
 * @param UntrustedValue<true> $true
 */
function boolReturnType(
    UntrustedValue $value,
    UntrustedStrictValue $strict,
    AbstractUntrustedValue $either,
    UntrustedStrictValue $boolOrString,
    UntrustedValue $true,
): void {
    assertType('bool|null', $value->bool());
    assertType('bool', $value->bool(false));
    assertType('bool|null', $strict->bool());
    assertType('bool', $strict->bool(false));
    assertType('bool|null', $either->bool());
    assertType('bool', $boolOrString->bool());
    assertType('true', $true->bool());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<bool>', $value->asBool(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<bool>', $boolOrString->asBool());
}
