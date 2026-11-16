<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to show what TValue becomes for a generator.
 *
 * @param \Generator<string, int, mixed, void> $generator
 */
function generatorType(\Generator $generator): void
{
    assertType('Puggan\UntrustedValue\UntrustedValue<Generator<string, int, mixed, void>>', new UntrustedValue($generator));
}
