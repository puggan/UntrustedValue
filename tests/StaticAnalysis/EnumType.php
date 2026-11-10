<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\Tests\Fixtures\Priority;
use Puggan\UntrustedValue\Tests\Fixtures\Suit;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return type of enum().
 *
 * @param UntrustedValue<mixed> $value
 */
function enumType(UntrustedValue $value): void
{
    assertType('Puggan\UntrustedValue\Tests\Fixtures\Suit|null', $value->enum(Suit::class));
    assertType('Puggan\UntrustedValue\Tests\Fixtures\Priority', $value->enum(Priority::class, false));
}
