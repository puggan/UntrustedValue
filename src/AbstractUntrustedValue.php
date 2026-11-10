<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * Wraps a value from an untrusted source, and hands it back as a type-safe value. The subclasses
 * decide how strict int(), float() and string() are: UntrustedValue converts compatible values,
 * UntrustedStrictValue only accepts the exact type.
 *
 * @template-covariant TValue The type of the raw value. Only describes what was wrapped, it is not checked.
 */
abstract class AbstractUntrustedValue implements \JsonSerializable
{
    /**
     * @param TValue $value The raw value from the untrusted source.
     * @param string $path Where in the input the value was found, e.g. "user.age". Empty for the root.
     */
    final public function __construct(
        public readonly mixed $value,
        public readonly string $path = '',
    ) {}

    /**
     * Decodes a JSON string into a new value of the called class. JSON objects become \stdClass
     * objects. Invalid JSON throws a WrongTypeException, with the \JsonException as its previous
     * exception.
     *
     * @return static<mixed>
     *
     * @throws WrongTypeException When the string is not valid JSON.
     */
    public static function fromJson(string $json): static
    {
        try {
            return new static(json_decode($json, false, 512, JSON_THROW_ON_ERROR));
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
     * Returns the value as an int. See UntrustedValue and UntrustedStrictValue for what is accepted.
     *
     * The return type is UntrustedValue's, which covers UntrustedStrictValue's narrower one too.
     *
     * @return (
     *     $nullable is false
     *     ? (TValue is int ? TValue : int)
     *     : (TValue is int|null ? TValue : (TValue is scalar|array|object|resource ? int : int|null))
     * )
     *
     * @throws WrongTypeException When the value can't be returned as an int, or is null when not allowed.
     */
    abstract public function int(bool $nullable = true): ?int;

    /**
     * Returns the value as a float. See UntrustedValue and UntrustedStrictValue for what is accepted.
     *
     * The return type is UntrustedValue's, which covers UntrustedStrictValue's narrower one too.
     *
     * @return (
     *     $nullable is false
     *     ? (TValue is float ? TValue : float)
     *     : (TValue is float|null ? TValue : (TValue is scalar|array|object|resource ? float : float|null))
     * )
     *
     * @throws WrongTypeException When the value can't be returned as a float, or is null when not allowed.
     */
    abstract public function float(bool $nullable = true): ?float;

    /**
     * Returns the value as a string. See UntrustedValue and UntrustedStrictValue for what is accepted.
     *
     * The return type is UntrustedValue's, which covers UntrustedStrictValue's narrower one too.
     *
     * @return (
     *     $nullable is false
     *     ? (TValue is string ? TValue : string)
     *     : (TValue is string|null ? TValue : (TValue is scalar|array|object|resource ? string : string|null))
     * )
     *
     * @throws WrongTypeException When the value can't be returned as a string, or is null when not allowed.
     */
    abstract public function string(bool $nullable = true): ?string;

    /**
     * This value as an UntrustedStrictValue, where int(), float() and string() only accept the
     * exact type. Returns the same object when it already is one, otherwise a copy with the same
     * value and path.
     *
     * @return UntrustedStrictValue<TValue>
     */
    abstract public function asStrict(): UntrustedStrictValue;

    /**
     * This value as an UntrustedValue, where int(), float() and string() also convert compatible
     * values. Returns the same object when it already is one, otherwise a copy with the same value
     * and path.
     *
     * @return UntrustedValue<TValue>
     */
    abstract public function asLoose(): UntrustedValue;

    /**
     * The value converted by int(), wrapped as an UntrustedStrictValue with the same path, so it
     * can be checked further while it is known to be an int. The wrapped type follows int()'s
     * return type, which UntrustedStrictValue narrows further.
     *
     * @return (
     *     $nullable is false
     *     ? UntrustedStrictValue<(TValue is int ? TValue : int)>
     *     : UntrustedStrictValue<(TValue is int|null ? TValue : (TValue is scalar|array|object|resource ? int : int|null))>
     * )
     *
     * @throws WrongTypeException When int() throws.
     */
    abstract public function asInt(bool $nullable = true): UntrustedStrictValue;

    /**
     * The value converted by float(), wrapped as an UntrustedStrictValue with the same path, so it
     * can be checked further while it is known to be a float. The wrapped type follows float()'s
     * return type, which is the same in both classes.
     *
     * @return (
     *     $nullable is false
     *     ? UntrustedStrictValue<(TValue is float ? TValue : float)>
     *     : UntrustedStrictValue<(TValue is float|null ? TValue : (TValue is scalar|array|object|resource ? float : float|null))>
     * )
     *
     * @throws WrongTypeException When float() throws.
     */
    public function asFloat(bool $nullable = true): UntrustedStrictValue
    {
        return new UntrustedStrictValue($this->float(nullable: $nullable), $this->path);
    }

    /**
     * The value converted by string(), wrapped as an UntrustedStrictValue with the same path, so it
     * can be checked further while it is known to be a string. The wrapped type follows string()'s
     * return type, which UntrustedStrictValue narrows further.
     *
     * @return (
     *     $nullable is false
     *     ? UntrustedStrictValue<(TValue is string ? TValue : string)>
     *     : UntrustedStrictValue<(TValue is string|null ? TValue : (TValue is scalar|array|object|resource ? string : string|null))>
     * )
     *
     * @throws WrongTypeException When string() throws.
     */
    abstract public function asString(bool $nullable = true): UntrustedStrictValue;

    /**
     * The value as a list, an array with keys 0, 1, 2, … in order: this object when it already is
     * one, or a new object of the same class and path holding the values of another array
     * (array_values()) or the public property values of an object. An empty array is a list. Null
     * returns this object too when $nullable.
     *
     * @return (
     *     $nullable is false
     *     ? static<(TValue is list<mixed>|scalar|null|resource ? list<mixed>&TValue : list<mixed>)>
     *     : static<(TValue is list<mixed>|scalar|null|resource ? list<mixed>&TValue : list<mixed>)|(null&TValue)>
     * )
     *
     * @throws WrongTypeException When the value is neither an array nor an object, or is null when not allowed.
     */
    public function asList(bool $nullable = true): static
    {
        if ($this->valueIsNull()) {
            if (!$nullable) {
                throw $this->wrongType('list');
            }

            return $this;
        }

        if ($this->valueIsList()) {
            return $this;
        }

        if (is_array($this->value)) {
            return new static(array_values($this->value), $this->path);
        }

        if (is_object($this->value)) {
            return new static(array_values(get_object_vars($this->value)), $this->path);
        }

        throw $this->wrongType('list');
    }

    /**
     * The value as an array: this object when it already is one, or a new object of the same class
     * and path holding an object's properties cast to an array. Null returns this object too when
     * $nullable.
     *
     * @return (
     *     $nullable is false
     *     ? static<(TValue is array|scalar|null|resource ? array<mixed>&TValue : array<mixed>)>
     *     : static<(TValue is array|scalar|null|resource ? array<mixed>&TValue : array<mixed>)|(null&TValue)>
     * )
     *
     * @throws WrongTypeException When the value is neither an array nor an object, or is null when not allowed.
     */
    public function asArray(bool $nullable = true): static
    {
        if ($this->valueIsNull()) {
            if (!$nullable) {
                throw $this->wrongType('array');
            }

            return $this;
        }

        if ($this->valueIsArray()) {
            return $this;
        }

        if (!is_object($this->value)) {
            throw $this->wrongType('array');
        }

        return new static((array) $this->value, $this->path);
    }

    /**
     * The value as an object: this object when it already is one, or a new object of the same class
     * and path holding a non-list array cast to a \stdClass. A list has no property names, so it is
     * rejected, and so is an empty array, which counts as a list. Null returns this object too when
     * $nullable.
     *
     * @return (
     *     $nullable is false
     *     ? static<(TValue is object|scalar|null|resource ? object&TValue : (object&TValue)|\stdClass)>
     *     : static<(TValue is object|scalar|null|resource ? object&TValue : (object&TValue)|\stdClass)|(null&TValue)>
     * )
     *
     * @throws WrongTypeException When the value is a list, neither an array nor an object, or is null when not allowed.
     */
    public function asObject(bool $nullable = true): static
    {
        if ($this->valueIsNull()) {
            if (!$nullable) {
                throw $this->wrongType('object');
            }

            return $this;
        }

        if ($this->valueIsObject()) {
            return $this;
        }

        if (!is_array($this->value)) {
            throw $this->wrongType('object');
        }

        if (array_is_list($this->value)) {
            throw $this->wrongType('object', 'list');
        }

        return new static((object) $this->value, $this->path);
    }

    /**
     * Returns the raw value, unchanged and unchecked.
     *
     * @return TValue
     */
    public function raw(): mixed
    {
        return $this->value;
    }
    /**
     * Whether int() with the same $nullable would return an int instead of throwing.
     */
    public function isInt(bool $nullable = true): bool
    {
        try {
            $this->int($nullable);

            return true;
        } catch (WrongTypeException) {
            return false;
        }
    }
    /**
     * Whether float() with the same $nullable would return a float instead of throwing.
     */
    public function isFloat(bool $nullable = true): bool
    {
        try {
            $this->float($nullable);

            return true;
        } catch (WrongTypeException) {
            return false;
        }
    }
    /**
     * Whether string() with the same $nullable would return a string instead of throwing.
     */
    public function isString(bool $nullable = true): bool
    {
        try {
            $this->string($nullable);

            return true;
        } catch (WrongTypeException) {
            return false;
        }
    }
    /**
     * Whether the value is null.
     */
    public function isNull(): bool
    {
        return $this->value === null;
    }
    /**
     * Whether the value is an int, float, string or bool, or null when $nullable.
     */
    public function isScalar(bool $nullable = true): bool
    {
        if ($this->value === null) {
            return $nullable;
        }

        return is_scalar($this->value);
    }
    /**
     * Whether the value is an object, or null when $nullable.
     */
    public function isObject(bool $nullable = true): bool
    {
        if ($this->value === null) {
            return $nullable;
        }

        return is_object($this->value);
    }
    /**
     * Whether the value is an array, list or not, or null when $nullable.
     */
    public function isArray(bool $nullable = true): bool
    {
        if ($this->value === null) {
            return $nullable;
        }

        return is_array($this->value);
    }
    /**
     * Whether the value is a list, an array with keys 0, 1, 2, … in order, or null when $nullable.
     * An empty array is a list.
     */
    public function isList(bool $nullable = true): bool
    {
        if ($this->value === null) {
            return $nullable;
        }

        if (!is_array($this->value)) {
            return false;
        }

        return array_is_list($this->value);
    }
    /**
     * Whether the value can have named properties: an object, or an array that is not a list.
     * An empty array is a list, so it does not count. Null passes when $nullable.
     */
    public function hasProperties(bool $nullable = true): bool
    {
        if ($this->value === null) {
            return $nullable;
        }

        if (is_object($this->value)) {
            return true;
        }

        if (!is_array($this->value)) {
            return false;
        }

        return !array_is_list($this->value);
    }
    /**
     * Serializes to the raw value, unchanged.
     *
     * @return TValue
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }
    /**
     * Whether the value is null. When it is, PHPStan narrows this object's TValue to match.
     *
     * @phpstan-assert-if-true static<null&TValue> $this
     */
    private function valueIsNull(): bool
    {
        return $this->value === null;
    }

    /**
     * Whether the value is a list. When it is, PHPStan narrows this object's TValue to match.
     *
     * @phpstan-assert-if-true static<list<mixed>&TValue> $this
     */
    private function valueIsList(): bool
    {
        return is_array($this->value) && array_is_list($this->value);
    }

    /**
     * Whether the value is an array. When it is, PHPStan narrows this object's TValue to match.
     *
     * @phpstan-assert-if-true static<array<mixed>&TValue> $this
     */
    private function valueIsArray(): bool
    {
        return is_array($this->value);
    }

    /**
     * Whether the value is an object. When it is, PHPStan narrows this object's TValue to match.
     *
     * @phpstan-assert-if-true static<object&TValue> $this
     */
    private function valueIsObject(): bool
    {
        return is_object($this->value);
    }

    /**
     * The given type defaults to get_debug_type() of the value. Pass one when there is a more
     * precise description, e.g. "non-numeric-string".
     */
    protected function wrongType(string $expectedType, ?string $givenType = null): WrongTypeException
    {
        $givenType ??= get_debug_type($this->value);

        return new WrongTypeException(
            $this->path === ''
                ? sprintf('Expected %s, got %s', $expectedType, $givenType)
                : sprintf('Expected %s at %s, got %s', $expectedType, $this->path, $givenType),
            $expectedType,
            $givenType,
            $this->path,
        );
    }
}
