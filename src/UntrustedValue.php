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
     * @param string $path Where in the input the value was found, e.g. "user.age". Empty for the root.
     */
    public function __construct(
        public readonly mixed $value,
        public readonly string $path = '',
    ) {}

    /**
     * Decodes a JSON string into an UntrustedValue. JSON objects become \stdClass objects.
     * Invalid JSON throws a WrongTypeException, with the \JsonException as its previous exception.
     *
     * @throws WrongTypeException When the string is not valid JSON.
     */
    public static function fromJson(string $json): self
    {
        try {
            return new self(json_decode($json, false, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException $exception) {
            throw new WrongTypeException(
                'Invalid JSON: ' . $exception->getMessage(),
                'json',
                'string',
                '',
                $exception,
            );
        }
    }

    /**
     * Serializes to the raw value, unchanged.
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
}
