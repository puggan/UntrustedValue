<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the TValue template type.
 *
 * @param UntrustedValue<array{id: int}> $shaped
 * @param UntrustedStrictValue<array{id: int}> $strictShaped
 * @param UntrustedValue<mixed> $unknown
 */
function templateType(UntrustedValue $shaped, UntrustedStrictValue $strictShaped, UntrustedValue $unknown, string $json): void
{
    assertType('array{id: int}', $shaped->raw());
    assertType('array{id: int}', $shaped->jsonSerialize());
    assertType('array{id: int}', $shaped->value);
    assertType('array{id: int}', $strictShaped->raw());
    assertType('mixed', $unknown->raw());
    assertType('Puggan\UntrustedValue\UntrustedValue<mixed>', UntrustedValue::fromJson($json));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<mixed>', UntrustedStrictValue::fromJson($json));
    assertType('mixed', UntrustedValue::fromJson($json)->raw());
    assertType('Puggan\UntrustedValue\UntrustedValue<42>', new UntrustedValue(42));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<42>', new UntrustedStrictValue(42));
    assertType('42', new UntrustedValue(42)->raw());
    assertType('Puggan\UntrustedValue\UntrustedValue<array{id: int}>', new UntrustedValue($shaped));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<array{id: int}>', new UntrustedStrictValue($shaped, 'x'));
    assertType("'text'", new UntrustedStrictValue('text')->raw());
}
