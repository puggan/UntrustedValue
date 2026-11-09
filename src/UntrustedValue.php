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
     * @param bool $strict The default strict mode for int(), float() and string().
     */
    public function __construct(
        public readonly mixed $value,
        public readonly string $path = '',
        public readonly bool $strict = false,
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
     * Returns the raw value, unchanged and unchecked.
     */
    public function raw(): mixed
    {
        return $this->value;
    }

    /**
     * Returns the value as an int.
     *
     * Strict mode ($strict, or the constructor's $strict when null) only accepts an int. Otherwise these are converted too:
     * - a float within 0.001 above a whole number, like 42.0 or 42.0004;
     * - a numeric string (see is_numeric()), like "42", " 42", "+42", "042" or "1e3",
     *   following the same rule once it is read as a number.
     * Either way the result has to fit in an int.
     *
     * @return ($nullable is false ? int : ?int)
     *
     * @throws WrongTypeException When the value is not an int, or null when allowed, and cannot be converted.
     */
    public function int(bool $nullable = true, ?bool $strict = null): ?int
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

        if ($strict ?? $this->strict) {
            throw $this->wrongType('int');
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
     * Ints are always accepted, as JSON numbers like 1 decode to int, and are widened to float.
     * Strict mode ($strict, or the constructor's $strict when null) accepts nothing else. Otherwise a numeric string (see is_numeric()), like "4.2",
     * " 4.2" or "1e3", is converted too, as long as it gives a finite float.
     *
     * @return ($nullable is false ? float : ?float)
     *
     * @throws WrongTypeException When the value is not a float, or null when allowed, and cannot be converted.
     */
    public function float(bool $nullable = true, ?bool $strict = null): ?float
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

        if ($strict ?? $this->strict) {
            throw $this->wrongType('float');
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
     * Strict mode ($strict, or the constructor's $strict when null) only accepts a string. Otherwise an int, or a finite float, is converted too,
     * the way PHP casts it, like "42", "4.2" or "1.0E+25". Bools are never converted.
     *
     * @return ($nullable is false ? string : ?string)
     *
     * @throws WrongTypeException When the value is not a string, or null when allowed, and cannot be converted.
     */
    public function string(bool $nullable = true, ?bool $strict = null): ?string
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

        if ($strict ?? $this->strict) {
            throw $this->wrongType('string');
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
     * Whether int() with the same arguments would return an int instead of throwing.
     */
    public function isInt(bool $nullable = true, ?bool $strict = null): bool
    {
        try {
            $this->int($nullable, $strict);

            return true;
        } catch (WrongTypeException) {
            return false;
        }
    }

    /**
     * Whether float() with the same arguments would return a float instead of throwing.
     */
    public function isFloat(bool $nullable = true, ?bool $strict = null): bool
    {
        try {
            $this->float($nullable, $strict);

            return true;
        } catch (WrongTypeException) {
            return false;
        }
    }

    /**
     * Whether string() with the same arguments would return a string instead of throwing.
     */
    public function isString(bool $nullable = true, ?bool $strict = null): bool
    {
        try {
            $this->string($nullable, $strict);

            return true;
        } catch (WrongTypeException) {
            return false;
        }
    }

    /**
     * Serializes to the raw value, unchanged.
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
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

    /**
     * The given type defaults to get_debug_type() of the value. Pass one when there is a more
     * precise description, e.g. "non-numeric-string".
     */
    private function wrongType(string $expectedType, ?string $givenType = null): WrongTypeException
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
