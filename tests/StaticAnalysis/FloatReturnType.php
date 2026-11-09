<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the conditional return type of float().
 */
function floatReturnType(UntrustedValue $value): void
{
    assertType('float|null', $value->float());
    assertType('float|null', $value->float(true));
    assertType('float|null', $value->float(strict: true));
    assertType('float', $value->float(false));
    assertType('float', $value->float(nullable: false, strict: true));
}
