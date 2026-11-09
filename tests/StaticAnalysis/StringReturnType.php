<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the conditional return type of string().
 */
function stringReturnType(UntrustedValue $value): void
{
    assertType('string|null', $value->string());
    assertType('string|null', $value->string(true));
    assertType('string|null', $value->string(strict: true));
    assertType('string', $value->string(false));
    assertType('string', $value->string(nullable: false, strict: true));
}
