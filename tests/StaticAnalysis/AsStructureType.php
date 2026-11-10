<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue\Tests\StaticAnalysis;

use function PHPStan\Testing\assertType;

use Puggan\UntrustedValue\AbstractUntrustedValue;
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;

/**
 * Never run, only analysed by PHPStan, to check the return types of asList(), asArray() and
 * asObject().
 *
 * @param UntrustedValue<mixed> $mixed
 * @param UntrustedStrictValue<list<int>|string> $listOrString
 * @param UntrustedValue<array{id: int}> $shape
 * @param AbstractUntrustedValue<\DateTimeImmutable|int> $dateOrInt
 * @param UntrustedValue<\DateTimeImmutable|array{id: int}> $dateOrShape
 * @param UntrustedValue<list<int>|null> $nullableList
 */
function asStructureType(
    UntrustedValue $mixed,
    UntrustedStrictValue $listOrString,
    UntrustedValue $shape,
    AbstractUntrustedValue $dateOrInt,
    UntrustedValue $dateOrShape,
    UntrustedValue $nullableList,
): void {
    assertType('Puggan\UntrustedValue\UntrustedValue<list<mixed>|null>', $mixed->asList());
    assertType('Puggan\UntrustedValue\UntrustedValue<list<mixed>>', $mixed->asList(false));
    assertType('Puggan\UntrustedValue\UntrustedValue<array|null>', $mixed->asArray());
    assertType('Puggan\UntrustedValue\UntrustedValue<array>', $mixed->asArray(false));
    assertType('Puggan\UntrustedValue\UntrustedValue<object|null>', $mixed->asObject());
    assertType('Puggan\UntrustedValue\UntrustedValue<object>', $mixed->asObject(false));

    // Null is only kept when TValue can be null.
    assertType('Puggan\UntrustedValue\UntrustedValue<list<int>|null>', $nullableList->asList());
    assertType('Puggan\UntrustedValue\UntrustedValue<list<int>>', $nullableList->asList(false));

    assertType('Puggan\UntrustedValue\UntrustedStrictValue<list<int>>', $listOrString->asList());
    assertType('Puggan\UntrustedValue\UntrustedValue<list<mixed>>', $shape->asList());
    assertType('Puggan\UntrustedValue\UntrustedValue<list<mixed>>', $dateOrShape->asList(false));
    assertType('Puggan\UntrustedValue\UntrustedStrictValue<list<int>>', $listOrString->asArray());

    assertType('Puggan\UntrustedValue\UntrustedValue<array{id: int}>', $shape->asArray());
    assertType('Puggan\UntrustedValue\UntrustedValue<stdClass>', $shape->asObject());

    assertType('Puggan\UntrustedValue\AbstractUntrustedValue<DateTimeImmutable>', $dateOrInt->asObject());

    assertType('Puggan\UntrustedValue\UntrustedValue<array<mixed>>', $dateOrShape->asArray());
    assertType('Puggan\UntrustedValue\UntrustedValue<DateTimeImmutable|stdClass>', $dateOrShape->asObject());
}
