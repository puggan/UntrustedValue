<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of date().
 *
 * @param UntrustedValue<mixed> $value
 */
function dateType(UntrustedValue $value): void
{
    assertType('DateTimeImmutable|null', $value->date('!Y-m-d'));
    assertType('DateTimeImmutable', $value->date('!Y-m-d', false));
}
