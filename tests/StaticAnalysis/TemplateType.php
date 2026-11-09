<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the TValue template type.
 *
 * @param UntrustedValue<array{id: int}> $shaped
 * @param UntrustedValue<mixed> $unknown
 */
function templateType(UntrustedValue $shaped, UntrustedValue $unknown, string $json): void
{
    assertType('array{id: int}', $shaped->raw());
    assertType('array{id: int}', $shaped->jsonSerialize());
    assertType('array{id: int}', $shaped->value);
    assertType('mixed', $unknown->raw());
    assertType('Puggan\UntrustedValue\UntrustedValue<mixed>', UntrustedValue::fromJson($json));
    assertType('mixed', UntrustedValue::fromJson($json)->raw());
    assertType('int', new UntrustedValue(42)->raw());
    assertType('string', new UntrustedValue('text')->raw());
}
