<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * Wraps a value from an untrusted source. int(), float(), string() and bool() only accept the
 * exact type, except that float() widens an int. See UntrustedValue for converting compatible
 * values too.
 *
 * @template-covariant TValue The type of the raw value. Only describes what was wrapped, it is not checked.
 *
 * @extends AbstractUntrustedValue<TValue>
 */
final class UntrustedStrictValue extends AbstractUntrustedValue
{
    /**
     * @return $this
     */
    public function asStrict(): UntrustedStrictValue
    {
        return $this;
    }

    /**
     * @return UntrustedValue<TValue> A copy with the same value and path.
     */
    public function asLoose(): UntrustedValue
    {
        return new UntrustedValue($this->value, $this->path);
    }

    /**
     * The value, when it is an int, wrapped as a new UntrustedStrictValue with the same path.
     *
     * @return ($nullable is false ? UntrustedStrictValue<int&TValue> : UntrustedStrictValue<(int|null)&TValue>)
     *
     * @throws WrongTypeException When int() throws.
     */
    public function asInt(bool $nullable = true): UntrustedStrictValue
    {
        return new UntrustedStrictValue($this->int(nullable: $nullable), $this->path);
    }

    /**
     * The value, when it is a string, wrapped as a new UntrustedStrictValue with the same path.
     *
     * @return ($nullable is false ? UntrustedStrictValue<string&TValue> : UntrustedStrictValue<(string|null)&TValue>)
     *
     * @throws WrongTypeException When string() throws.
     */
    public function asString(bool $nullable = true): UntrustedStrictValue
    {
        return new UntrustedStrictValue($this->string(nullable: $nullable), $this->path);
    }

    /**
     * The value, when it is a bool, wrapped as a new UntrustedStrictValue with the same path.
     *
     * @return ($nullable is false ? UntrustedStrictValue<bool&TValue> : UntrustedStrictValue<(bool|null)&TValue>)
     *
     * @throws WrongTypeException When bool() throws.
     */
    public function asBool(bool $nullable = true): UntrustedStrictValue
    {
        return new UntrustedStrictValue($this->bool(nullable: $nullable), $this->path);
    }

    /**
     * Returns the value as an int. Only an int is accepted.
     *
     * @return ($nullable is false ? int&TValue : (int|null)&TValue)
     *
     * @throws WrongTypeException When the value is not an int, or is null when not allowed.
     */
    public function int(bool $nullable = true): ?int
    {
        if ($this->value === null) {
            if (!$nullable) {
                throw $this->wrongType('int');
            }

            // The value itself rather than null, so PHPStan sees it is part of TValue.
            return $this->value;
        }

        if (!is_int($this->value)) {
            throw $this->wrongType('int');
        }

        return $this->value;
    }

    /**
     * Returns the value as a float. Ints are accepted too, as JSON numbers like 1 decode to int,
     * and are widened to float.
     *
     * The return type follows TValue where it can: a TValue that is already a float (or ?float when
     * nullable) is returned as is, and otherwise the result is float, or ?float when null is both
     * allowed and possible.
     *
     * @return (
     *     $nullable is false
     *     ? (TValue is float ? TValue : float)
     *     : (TValue is float|null ? TValue : (TValue is scalar|array|object|resource ? float : float|null))
     * )
     *
     * @throws WrongTypeException When the value is neither a float nor an int, or is null when not allowed.
     */
    public function float(bool $nullable = true): ?float
    {
        if ($this->value === null) {
            if (!$nullable) {
                throw $this->wrongType('float');
            }

            return null;
        }

        if (is_float($this->value)) {
            return $this->value;
        }

        if (!is_int($this->value)) {
            throw $this->wrongType('float');
        }

        return (float) $this->value;
    }

    /**
     * Returns the value as a string. Only a string is accepted.
     *
     * @return ($nullable is false ? string&TValue : (string|null)&TValue)
     *
     * @throws WrongTypeException When the value is not a string, or is null when not allowed.
     */
    public function string(bool $nullable = true): ?string
    {
        if ($this->value === null) {
            if (!$nullable) {
                throw $this->wrongType('string');
            }

            // The value itself rather than null, so PHPStan sees it is part of TValue.
            return $this->value;
        }

        if (!is_string($this->value)) {
            throw $this->wrongType('string');
        }

        return $this->value;
    }

    /**
     * Returns the value as a bool. Only a bool is accepted.
     *
     * @return ($nullable is false ? bool&TValue : (bool|null)&TValue)
     *
     * @throws WrongTypeException When the value is not a bool, or is null when not allowed.
     */
    public function bool(bool $nullable = true): ?bool
    {
        if ($this->value === null) {
            if (!$nullable) {
                throw $this->wrongType('bool');
            }

            // The value itself rather than null, so PHPStan sees it is part of TValue.
            return $this->value;
        }

        if (!is_bool($this->value)) {
            throw $this->wrongType('bool');
        }

        return $this->value;
    }
}
