<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the conditional return type of int().
 */
function intReturnType(UntrustedValue $value): void
{
    assertType('int|null', $value->int());
    assertType('int|null', $value->int(true));
    assertType('int|null', $value->int(strict: true));
    assertType('int', $value->int(false));
    assertType('int', $value->int(nullable: false, strict: true));
}
