<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * Wraps a value from an untrusted source, and hands it back as a type-safe value,
 * optionally validated.
 */
final class UntrustedValue implements \JsonSerializable
{
    /**
     * @param mixed $value The raw value from the untrusted source.
     */
    public function __construct(
        public readonly mixed $value,
    ) {}

    /**
     * Serializes to the raw value, unchanged.
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
