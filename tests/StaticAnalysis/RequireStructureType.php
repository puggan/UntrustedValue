<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check that require*() narrow TValue.
 *
 * @param UntrustedValue<mixed> $mixed
 * @param UntrustedStrictValue<list<int>|array<string, int>|\stdClass|null> $structure
 */
function requireStructureType(UntrustedValue $mixed, UntrustedStrictValue $structure): void
{
    assertType('Puggan\UntrustedValue\UntrustedValue<array<mixed>|null>', $mixed->requireArray());
    assertType('Puggan\UntrustedValue\UntrustedValue<array<mixed>>', $mixed->requireArray(false));
    assertType('Puggan\UntrustedValue\UntrustedValue<object>', $mixed->requireObject(false));
    assertType('Puggan\UntrustedValue\UntrustedValue<list<mixed>>', $mixed->requireList(false));
    assertType('Puggan\UntrustedValue\UntrustedValue<array<mixed>>', $mixed->requireMap(false));

    assertType('Puggan\UntrustedValue\UntrustedStrictValue<array<int<0, max>|string, int>|null>', $structure->requireArray());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<stdClass>', $structure->requireObject(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<list<int>|null>', $structure->requireList());
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<array<int<0, max>|string, int>>', $structure->requireMap(false));
}
