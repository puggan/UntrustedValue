<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * Wraps a value from an untrusted source. int(), float() and string() also convert compatible
 * values, like the string "42" to the int 42. See UntrustedStrictValue for exact types only.
 *
 * @template TValue The type of the raw value. Only describes what was wrapped, it is not checked.
 *
 * @extends AbstractUntrustedValue<TValue>
 */
final class UntrustedValue extends AbstractUntrustedValue
{
    /**
     * @return UntrustedStrictValue<TValue> A copy with the same value and path.
     */
    public function asStrict(): UntrustedStrictValue
    {
        return new UntrustedStrictValue($this->value, $this->path);
    }

    /**
     * @return $this
     */
    public function asLoose(): UntrustedValue
    {
        return $this;
    }

    /**
     * Returns the value as an int.
     *
     * Besides an int, these are converted too:
     * - a float within 0.001 above a whole number, like 42.0 or 42.0004;
     * - a numeric string (see is_numeric()), like "42", " 42", "+42", "042" or "1e3",
     *   following the same rule once it is read as a number.
     * Either way the result has to fit in an int.
     *
     * The return type follows TValue where it can: a TValue that is already an int (or ?int when
     * nullable) is returned as is, and otherwise the result is int, or ?int when null is both
     * allowed and possible.
     *
     * @return (
     *     $nullable is false
     *     ? (TValue is int ? TValue : int)
     *     : (TValue is int|null ? TValue : (TValue is scalar|array|object|resource ? int : int|null))
     * )
     *
     * @throws WrongTypeException When the value is not an int, or null when allowed, and cannot be converted.
     */
    public function int(bool $nullable = true): ?int
    {
        if ($this->value === null) {
            if (!$nullable) {
                throw $this->wrongType('int');
            }

            return null;
        }

        if (!is_scalar($this->value)) {
            throw $this->wrongType('int');
        }

        if (is_int($this->value)) {
            return $this->value;
        }

        if (is_float($this->value)) {
            return $this->floatToInt($this->value);
        }

        if (!is_string($this->value)) {
            throw $this->wrongType('int');
        }

        if (!is_numeric($this->value)) {
            throw $this->wrongType('int', 'non-numeric-string');
        }

        $number = +$this->value;

        return is_int($number) ? $number : $this->floatToInt($number);
    }
    /**
     * Returns the value as a float.
     *
     * Ints are widened to float. A numeric string (see is_numeric()), like "4.2", " 4.2" or "1e3",
     * is converted too, as long as it gives a finite float.
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
     * @throws WrongTypeException When the value is not a float, or null when allowed, and cannot be converted.
     */
    public function float(bool $nullable = true): ?float
    {
        if ($this->value === null) {
            if (!$nullable) {
                throw $this->wrongType('float');
            }

            return null;
        }

        if (!is_scalar($this->value)) {
            throw $this->wrongType('float');
        }

        if (is_float($this->value)) {
            return $this->value;
        }

        if (is_int($this->value)) {
            return (float) $this->value;
        }

        if (!is_string($this->value)) {
            throw $this->wrongType('float');
        }

        if (!is_numeric($this->value)) {
            throw $this->wrongType('float', 'non-numeric-string');
        }

        $float = (float) $this->value;

        if (!is_finite($float)) {
            throw $this->wrongType('float', 'non-finite-numeric-string');
        }

        return $float;
    }
    /**
     * Returns the value as a string.
     *
     * Besides a string, an int or a finite float is converted too, the way PHP casts it, like "42",
     * "4.2" or "1.0E+25". Bools are never converted.
     *
     * The return type follows TValue where it can: a TValue that is already a string (or ?string
     * when nullable) is returned as is, and otherwise the result is string, or ?string when null is
     * both allowed and possible.
     *
     * @return (
     *     $nullable is false
     *     ? (TValue is string ? TValue : string)
     *     : (TValue is string|null ? TValue : (TValue is scalar|array|object|resource ? string : string|null))
     * )
     *
     * @throws WrongTypeException When the value is not a string, or null when allowed, and cannot be converted.
     */
    public function string(bool $nullable = true): ?string
    {
        if ($this->value === null) {
            if (!$nullable) {
                throw $this->wrongType('string');
            }

            return null;
        }

        if (!is_scalar($this->value)) {
            throw $this->wrongType('string');
        }

        if (is_string($this->value)) {
            return $this->value;
        }

        if (is_bool($this->value)) {
            throw $this->wrongType('string');
        }

        if (is_float($this->value) && !is_finite($this->value)) {
            throw $this->wrongType('string', 'non-finite-float');
        }

        return (string) $this->value;
    }
    /**
     * The float as an int, when it is within 0.001 above a whole number that fits in an int.
     * The float is either the value itself, or a numeric string value read as a number.
     *
     * @throws WrongTypeException When it is not.
     */
    private function floatToInt(float $float): int
    {
        $source = is_string($this->value) ? 'numeric-string' : 'float';

        // Casting NaN, INF or an out-of-range float to int warns and gives a wrapped-around number,
        // so those are rejected before the cast.
        if (!is_finite($float)) {
            throw $this->wrongType('int', "non-finite-{$source}");
        }

        if ($float < PHP_INT_MIN) {
            throw $this->wrongType('int', "out-of-range-{$source}");
        }

        if ($float >= -(float) PHP_INT_MIN) {
            throw $this->wrongType('int', "out-of-range-{$source}");
        }

        $int = (int) $float;

        if (abs($float - $int) >= 0.001) {
            throw $this->wrongType('int', "fractional-{$source}");
        }

        return $int;
    }
}
