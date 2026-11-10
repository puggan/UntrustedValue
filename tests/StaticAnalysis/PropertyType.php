<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check that property() keeps the class.
 *
 * @param UntrustedValue<array{id: int}> $loose
 * @param UntrustedStrictValue<\stdClass> $strict
 * @param AbstractUntrustedValue<mixed> $either
 */
function propertyType(UntrustedValue $loose, UntrustedStrictValue $strict, AbstractUntrustedValue $either): void
{
    assertType('Puggan\UntrustedValue\UntrustedValue<mixed>', $loose->property('id'));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<mixed>', $strict->property('id', false));
    assertType('Puggan\UntrustedValue\AbstractUntrustedValue<mixed>', $either->property('id'));
}
