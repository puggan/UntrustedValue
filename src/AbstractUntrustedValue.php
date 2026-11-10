<?php

declare(strict_types=1);

namespace Puggan\UntrustedValue;

/**
 * Wraps a value from an untrusted source, and hands it back as a type-safe value. The subclasses
 * decide how strict int(), float(), string(), bool() and the methods built on them are:
 * UntrustedValue converts compatible values, UntrustedStrictValue only accepts the exact type.
 *
 * @template-covariant TValue The type of the raw value. Only describes what was wrapped, it is not checked.
 *
 * @implements \IteratorAggregate<mixed, AbstractUntrustedValue<mixed>>
 */
abstract class AbstractUntrustedValue implements \IteratorAggregate, \JsonSerializable
{
    /**
     * The raw value from the untrusted source.
     *
     * @var TValue
     */
    public readonly mixed $value;

    /**
     * Wraps $value. When $value already is an untrusted value, its raw value is taken instead, so a
     * value is never wrapped twice; the path is still the one given here, and the class is the one
     * constructed, so new UntrustedStrictValue($loose) makes a strict value.
     *
     * $value is not a promoted property, as a promoted readonly property is assigned before the
     * constructor body could unwrap it.
     *
     * @param TValue|AbstractUntrustedValue<TValue> $value The raw value from the untrusted source.
     * @param string $path Where in the input the value was found, e.g. "user.age". Empty for the root.
     */
    final public function __construct(
        mixed $value,
        public readonly string $path = '',
    ) {
        $this->value = $value instanceof self ? $value->value : $value;
    }

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
        return new static(self::decodeJson($json, ''));
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
     * Returns the value as a bool. See UntrustedValue and UntrustedStrictValue for what is accepted.
     *
     * The return type is UntrustedValue's, which covers UntrustedStrictValue's narrower one too.
     *
     * @return (
     *     $nullable is false
     *     ? (TValue is bool ? TValue : bool)
     *     : (TValue is bool|null ? TValue : (TValue is scalar|array|object|resource ? bool : bool|null))
     * )
     *
     * @throws WrongTypeException When the value can't be returned as a bool, or is null when not allowed.
     */
    abstract public function bool(bool $nullable = true): ?bool;

    /**
     * The value as a case of the backed enum $enum, found with tryFrom(). The value is first read
     * with int() or string(), matching the enum's backing type, so UntrustedValue accepts "2" for
     * an int-backed case while UntrustedStrictValue requires the exact type.
     *
     * @template TEnum of \BackedEnum
     *
     * @param class-string<TEnum> $enum
     *
     * @return ($nullable is false ? TEnum : TEnum|null)
     *
     * @throws WrongTypeException When the value can't be read as the backing type, or is null when not allowed.
     * @throws InvalidTypeException When the value is not one of the enum's backing values.
     */
    public function enum(string $enum, bool $nullable = true): ?\BackedEnum
    {
        if ($this->value === null) {
            if (!$nullable) {
                throw $this->wrongType($enum);
            }

            return null;
        }

        // No new \ReflectionEnum($enum)->getBackingType(): that syntax needs PHP 8.4, src/ supports 8.1.
        $reflection = new \ReflectionEnum($enum);
        $backingValue = (string) $reflection->getBackingType() === 'int'
            ? $this->int(false)
            : $this->string(false);

        return $enum::tryFrom($backingValue) ?? throw $this->invalid('enum', sprintf(
            'one of %s',
            implode(', ', array_map(static fn(\BackedEnum $case): string => var_export($case->value, true), $enum::cases())),
        ));
    }

    /**
     * The value as a date, parsed with DateTimeImmutable::createFromFormat() in $format. The value
     * is read with string(), so UntrustedStrictValue requires a string. Unlike createFromFormat()
     * itself, a date that only parses by rolling over, like 2026-02-30 becoming 2026-03-02, is
     * rejected, as is anything else PHP warns about.
     *
     * Fields the format leaves out are taken from the current time, as createFromFormat() does;
     * start the format with "!" to reset them instead, like "!Y-m-d" for midnight.
     *
     * @return ($nullable is false ? \DateTimeImmutable : \DateTimeImmutable|null)
     *
     * @throws WrongTypeException When string() can't return a string, or the value is null when not allowed.
     * @throws InvalidTypeException When the string is not a valid date in $format.
     */
    public function date(string $format, bool $nullable = true, ?\DateTimeZone $timezone = null): ?\DateTimeImmutable
    {
        if ($this->value === null) {
            if (!$nullable) {
                throw $this->wrongType('date');
            }

            return null;
        }

        $date = \DateTimeImmutable::createFromFormat($format, $this->string(false), $timezone);
        $errors = \DateTimeImmutable::getLastErrors();

        // PHP 8.2+ returns false when there is nothing to report, 8.1 an array of zero counts.
        $failed = $errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);

        if ($date === false || $failed) {
            throw $this->invalid('date', sprintf('a date in the format %s', $format));
        }

        return $date;
    }

    /**
     * Returns the raw value, unchanged and unchecked. For a filter() or map() result, that is the
     * generator of wrapped items.
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
     * Whether bool() with the same $nullable would return a bool instead of throwing.
     */
    public function isBool(bool $nullable = true): bool
    {
        try {
            $this->bool($nullable);

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
     * Whether the value is iterable (see is_iterable()): an array or a Traversable object, or null
     * when $nullable. A plain object like a \stdClass is not iterable, even though foreach over this
     * object, or yieldList(), still reads its public properties.
     */
    public function isIterable(bool $nullable = true): bool
    {
        if ($this->value === null) {
            return $nullable;
        }

        return is_iterable($this->value);
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
     * Whether the value is a map: an array that is not a list. An empty array is a list, so it is
     * not a map. Null passes when $nullable.
     */
    public function isMap(bool $nullable = true): bool
    {
        if ($this->value === null) {
            return $nullable;
        }

        if (!is_array($this->value)) {
            return false;
        }

        return !array_is_list($this->value);
    }

    /**
     * Whether the value has the property: a public property of an object, or a key of an array.
     * Other values have no properties, so it is false for them.
     */
    public function hasProperty(string $name): bool
    {
        $properties = $this->properties();

        return $properties !== null && array_key_exists($name, $properties);
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
     * This object, when the value is null.
     *
     * @return static<null&TValue>
     *
     * @throws WrongTypeException When the value is not null.
     */
    public function requireNull(): static
    {
        if ($this->valueIsNull()) {
            return $this;
        }

        throw $this->wrongType('null');
    }

    /**
     * This object, when isScalar() with the same $nullable is true.
     *
     * @return ($nullable is false ? static<scalar&TValue> : static<(scalar|null)&TValue>)
     *
     * @throws WrongTypeException When the value is not an int, float, string or bool, or is null when not allowed.
     */
    public function requireScalar(bool $nullable = true): static
    {
        if ($this->valueIsNull()) {
            if (!$nullable) {
                throw $this->wrongType('scalar');
            }

            return $this;
        }

        if ($this->valueIsScalar()) {
            return $this;
        }

        throw $this->wrongType('scalar');
    }

    /**
     * This object, when isArray() with the same $nullable is true.
     *
     * @return ($nullable is false ? static<array<mixed>&TValue> : static<(array<mixed>|null)&TValue>)
     *
     * @throws WrongTypeException When the value is not an array, or is null when not allowed.
     */
    public function requireArray(bool $nullable = true): static
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

        throw $this->wrongType('array');
    }

    /**
     * This object, when isObject() with the same $nullable is true.
     *
     * @return ($nullable is false ? static<object&TValue> : static<(object|null)&TValue>)
     *
     * @throws WrongTypeException When the value is not an object, or is null when not allowed.
     */
    public function requireObject(bool $nullable = true): static
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

        throw $this->wrongType('object');
    }

    /**
     * This object, when isList() with the same $nullable is true.
     *
     * @return ($nullable is false ? static<list<mixed>&TValue> : static<(list<mixed>|null)&TValue>)
     *
     * @throws WrongTypeException When the value is not a list, or is null when not allowed.
     */
    public function requireList(bool $nullable = true): static
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

        throw $this->wrongType('list', is_array($this->value) ? 'map' : null);
    }

    /**
     * This object, when isMap() with the same $nullable is true. PHPStan has no type for an array
     * that is not a list, so TValue is only narrowed to its array part.
     *
     * @return ($nullable is false ? static<array<mixed>&TValue> : static<(array<mixed>|null)&TValue>)
     *
     * @throws WrongTypeException When the value is not a map, or is null when not allowed.
     */
    public function requireMap(bool $nullable = true): static
    {
        if ($this->valueIsNull()) {
            if (!$nullable) {
                throw $this->wrongType('map');
            }

            return $this;
        }

        if ($this->valueIsList()) {
            throw $this->wrongType('map', 'list');
        }

        if ($this->valueIsArray()) {
            return $this;
        }

        throw $this->wrongType('map');
    }

    /**
     * This object, when hasProperty() is true.
     *
     * @return $this
     *
     * @throws WrongTypeException When the value is neither an object nor an array.
     * @throws InvalidTypeException When the property is missing.
     */
    public function requireProperty(string $name): static
    {
        $properties = $this->properties() ?? throw $this->wrongType('object|array');

        if (!array_key_exists($name, $properties)) {
            throw $this->missingProperty($name);
        }

        return $this;
    }

    /**
     * This value as an UntrustedStrictValue, where int(), float(), string(), bool() and the methods
     * built on them only accept the exact type. Returns the same object when it already is one,
     * otherwise a copy with the same value and path.
     *
     * @return UntrustedStrictValue<TValue>
     */
    abstract public function asStrict(): UntrustedStrictValue;

    /**
     * This value as an UntrustedValue, where int(), float(), string(), bool() and the methods built
     * on them also convert compatible values. Returns the same object when it already is one,
     * otherwise a copy with the same value and path.
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
     * The value converted by bool(), wrapped as an UntrustedStrictValue with the same path. The
     * wrapped type follows bool()'s return type, which UntrustedStrictValue narrows further.
     *
     * @return (
     *     $nullable is false
     *     ? UntrustedStrictValue<(TValue is bool ? TValue : bool)>
     *     : UntrustedStrictValue<(TValue is bool|null ? TValue : (TValue is scalar|array|object|resource ? bool : bool|null))>
     * )
     *
     * @throws WrongTypeException When bool() throws.
     */
    abstract public function asBool(bool $nullable = true): UntrustedStrictValue;

    /**
     * The value as a list, an array with keys 0, 1, 2, … in order: this object when it already is
     * one, or a new object of the same class and path holding the values of another array
     * (array_values()), the items of a Traversable object (like an iterator or generator), or the
     * public property values of another object. An empty array is a list. Null returns this object
     * too when $nullable. A generator can only be iterated once, so converting one uses it up.
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

        if ($this->value instanceof \Traversable) {
            return new static(iterator_to_array($this->value, false), $this->path);
        }

        if (is_object($this->value)) {
            return new static(array_values(get_object_vars($this->value)), $this->path);
        }

        throw $this->wrongType('list');
    }

    /**
     * The value as an array: this object when it already is one, or a new object of the same class
     * and path holding the items of a Traversable object (like an iterator or generator) with their
     * keys, or the public properties of another object (get_object_vars(), so protected and private
     * ones never leak out). Null returns this object too when $nullable. A generator can only be
     * iterated once, so converting one uses it up.
     *
     * @return (
     *     $nullable is false
     *     ? static<(TValue is array|scalar|null|resource ? array<mixed>&TValue : array<mixed>)>
     *     : static<(TValue is array|scalar|null|resource ? array<mixed>&TValue : array<mixed>)|(null&TValue)>
     * )
     *
     * @throws WrongTypeException When the value is neither an array nor an object, is null when not allowed, or a Traversable yields a key that is not an int or string.
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

        if ($this->value instanceof \Traversable) {
            return new static($this->traversableToArray($this->value), $this->path);
        }

        if (!is_object($this->value)) {
            throw $this->wrongType('array');
        }

        return new static(get_object_vars($this->value), $this->path);
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
     * The value decoded as JSON, for a field that holds a JSON string, as a new object of the same
     * class and path, so the decoded fields get paths like "payload.id". JSON objects become
     * \stdClass objects, as in fromJson(). The value is read with string(), so
     * UntrustedStrictValue requires a string. Null returns this object.
     *
     * @return static<mixed>
     *
     * @throws WrongTypeException When string() can't return a string, or the string is not valid JSON.
     */
    public function json(): static
    {
        if ($this->valueIsNull()) {
            return $this;
        }

        return new static(self::decodeJson($this->string(false), $this->path), $this->path);
    }

    /**
     * This object, when the value is not below $min and not above $max. A null bound is not
     * checked, and a null value passes. The comparison is PHP's < and >, so the value's type is not
     * checked here; convert it first, e.g. with asInt().
     *
     * @return $this
     *
     * @throws InvalidTypeException When the value is below $min or above $max.
     */
    public function range(int|float|string|null $min = null, int|float|string|null $max = null): static
    {
        if ($this->value === null) {
            return $this;
        }

        if ($min !== null && $this->value < $min) {
            throw $this->invalid('min', sprintf('at least %s', $min));
        }

        if ($max !== null && $this->value > $max) {
            throw $this->invalid('max', sprintf('at most %s', $max));
        }

        return $this;
    }

    /**
     * This object, when string() of the value is between $min and $max characters long, both
     * inclusive. A null $max means no upper limit. Characters are counted with mb_strlen(), so
     * "Thorbjörn" is 9 long, not 10. In UntrustedValue an int like 700 is measured as "700".
     * Null has no length to check, so it passes.
     *
     * @return $this
     *
     * @throws WrongTypeException When string() can't return a string or null.
     * @throws InvalidTypeException When the string is shorter than $min or longer than $max.
     */
    public function length(?int $max = null, int $min = 0): static
    {
        $string = $this->string();

        if ($string === null) {
            return $this;
        }

        $length = mb_strlen($string);

        if ($length < $min) {
            throw $this->invalid('minLength', sprintf('at least %d characters', $min));
        }

        if ($max !== null && $length > $max) {
            throw $this->invalid('maxLength', sprintf('at most %d characters', $max));
        }

        return $this;
    }

    /**
     * This object, when the value has between $min and $max items, both inclusive. A null $max
     * means no upper limit. Arrays and Countable objects are counted with count(), and other
     * objects by their public properties. Null has no count to check, so it passes.
     *
     * Counting a generator or other iterator that is not Countable uses it up, so for those the
     * items are first collected into an array (keys must be int or string), and a new object of the
     * same class and path holding that array is returned. Items that already are untrusted values,
     * as from filter() or map(), keep their own paths.
     *
     * @return $this|static<array<array-key, mixed>>
     *
     * @throws WrongTypeException When the value is neither an array nor an object, or an iterator yields a key that is not an int or string.
     * @throws InvalidTypeException When there are fewer than $min or more than $max items.
     */
    public function count(?int $max = null, int $min = 0): static
    {
        if ($this->value === null) {
            return $this;
        }

        $counted = $this;
        if (is_array($this->value) || $this->value instanceof \Countable) {
            $count = count($this->value);
        } elseif ($this->value instanceof \Traversable) {
            $items = $this->traversableToArray($this->value);
            $counted = new static($items, $this->path);
            $count = count($items);
        } elseif (is_object($this->value)) {
            $count = count(get_object_vars($this->value));
        } else {
            throw $this->wrongType('array|object');
        }

        if ($count < $min) {
            throw $this->invalid('minCount', sprintf('at least %d items', $min));
        }

        if ($max !== null && $count > $max) {
            throw $this->invalid('maxCount', sprintf('at most %d items', $max));
        }

        return $counted;
    }

    /**
     * This object, when the value is identical (===) to one of $allowed. The value is not converted
     * first, so in UntrustedValue "1" does not match 1; convert it first, e.g. ->asInt()->oneOf().
     * Null has nothing to check, so it passes. TValue narrows to the type of the allowed values;
     * PHPStan widens literals like 'asc' to string for a method template.
     *
     * @template TAllowed
     *
     * @param array<array-key, TAllowed> $allowed
     *
     * @return static<(TAllowed&TValue)|(null&TValue)>
     *
     * @throws InvalidTypeException When the value is not one of $allowed.
     */
    public function oneOf(array $allowed): static
    {
        if ($this->valueIsNull()) {
            return $this;
        }

        if ($this->valueIsOneOf($allowed)) {
            return $this;
        }

        throw $this->invalid('oneOf', sprintf(
            'one of %s',
            implode(', ', array_map(
                static fn(mixed $option): string => is_scalar($option) ? var_export($option, true) : get_debug_type($option),
                $allowed,
            )),
        ));
    }

    /**
     * This object, when string() of the value matches $regexp (see preg_match()). So in
     * UntrustedValue an int like 700 is checked as "700", while UntrustedStrictValue requires a
     * string. Null is rejected.
     *
     * @return $this
     *
     * @throws WrongTypeException When string() can't return a string.
     * @throws InvalidTypeException When the string does not match, or PCRE fails on it.
     * @throws \InvalidArgumentException When $regexp is not a valid pattern.
     */
    public function match(string $regexp): static
    {
        if (!$this->pregMatch($this->string(false), $regexp, 'match')) {
            throw $this->invalid('match', sprintf('a match for %s', $regexp));
        }

        return $this;
    }

    /**
     * This object, when string() of the value does not match $regexp (see preg_match()). So in
     * UntrustedValue an int like 700 is checked as "700". A value string() can't return a string
     * for, like null, an array or, in UntrustedStrictValue, an int, can't match, so it passes.
     *
     * @return $this
     *
     * @throws InvalidTypeException When the string matches, or PCRE fails on it.
     * @throws \InvalidArgumentException When $regexp is not a valid pattern.
     */
    public function notMatch(string $regexp): static
    {
        if (!$this->isString(false)) {
            return $this;
        }

        if ($this->pregMatch($this->string(false), $regexp, 'notMatch')) {
            throw $this->invalid('notMatch', sprintf('no match for %s', $regexp));
        }

        return $this;
    }

    /**
     * The value with whitespace trimmed from both ends, as trim() does. Returns this object when
     * the value is not a string or has nothing to trim, otherwise a new object of the same class
     * and path.
     *
     * A string TValue becomes plain string, as trimming can change it, e.g. to an empty string.
     *
     * @return static<(
     *     TValue is int|float|bool|array|object|resource|null
     *     ? TValue
     *     : ((int|float|bool|array|object|resource|null)&TValue)|string
     * )>
     */
    public function trim(): static
    {
        if (!$this->valueIsString()) {
            return $this;
        }

        $trimmed = trim($this->value);

        if ($trimmed === $this->value) {
            return $this;
        }

        return new static($trimmed, $this->path);
    }

    /**
     * A new object of the same class and path holding $to, when the value is identical (===) to
     * $from, otherwise this object.
     *
     * @template TTo
     *
     * @param TTo $to
     *
     * @return static<TValue|TTo>
     */
    public function replace(mixed $from, mixed $to): static
    {
        if ($this->value !== $from && $this->keepsTValue()) {
            return $this;
        }

        return new static($to, $this->path);
    }

    /**
     * Replaces null with $defaultValue, as replace(null, $defaultValue) does.
     *
     * @template TDefault
     *
     * @param TDefault $defaultValue
     *
     * @return static<((int|float|string|bool|array<mixed>|object|resource)&TValue)|TDefault>
     */
    public function default(mixed $defaultValue): static
    {
        if ($this->valueIsNotNull()) {
            return $this;
        }

        return new static($defaultValue, $this->path);
    }

    /**
     * Replaces an empty string with $defaultValue, as replace('', $defaultValue) does. A string of
     * only whitespace is not empty; use trim() first for that.
     *
     * @template TDefault
     *
     * @param TDefault $defaultValue
     *
     * @return static<TValue|TDefault>
     */
    public function blankDefault(mixed $defaultValue = null): static
    {
        return $this->replace('', $defaultValue);
    }

    /**
     * The value split on $separator (see explode()), as a new object of the same class and path
     * holding a list of strings, so items get paths like "ids.2". The value is read with string(),
     * so UntrustedStrictValue requires a string. An empty string gives an empty list rather than
     * explode()'s [''], and null returns this object. For quoted fields, use csv().
     *
     * @param non-empty-string $separator
     *
     * @return static<list<string>|(null&TValue)>
     *
     * @throws WrongTypeException When string() can't return a string.
     */
    public function split(string $separator): static
    {
        if ($this->valueIsNull()) {
            return $this;
        }

        $string = $this->string(false);

        return new static($string === '' ? [] : explode($separator, $string), $this->path);
    }

    /**
     * The value parsed as one CSV record (see str_getcsv()), as a new object of the same class and
     * path holding a list of strings, so items get paths like "row.2". Unlike split(), a field in
     * $enclosure can hold the separator, and a doubled $enclosure inside it is one literal one, like
     * "say ""hi""". The value is read with string(), so UntrustedStrictValue requires a string. An
     * empty string gives an empty list rather than str_getcsv()'s [null], and null returns this
     * object.
     *
     * $escape defaults to '', as RFC 4180 has no escape character and PHP 8.4 deprecates relying on
     * str_getcsv()'s old "\\" default. $separator and $enclosure must be one character each.
     *
     * @return static<list<string>|(null&TValue)>
     *
     * @throws WrongTypeException When string() can't return a string.
     */
    public function csv(string $separator = ',', string $enclosure = '"', string $escape = ''): static
    {
        if ($this->valueIsNull()) {
            return $this;
        }

        $string = $this->string(false);

        if ($string === '') {
            return new static([], $this->path);
        }

        $fields = array_map(
            static fn(?string $field): string => $field ?? '',
            str_getcsv($string, $separator, $enclosure, $escape),
        );

        return new static($fields, $this->path);
    }

    /**
     * A property of an object value, or a key of an array value, as a new object of the same class.
     * Its path is this path plus the name, like "user.age".
     *
     * Only public properties count. A missing property throws when $requiredKey, and otherwise
     * gives a null value.
     *
     * @return static<mixed>
     *
     * @throws WrongTypeException When the value is neither an object nor an array.
     * @throws InvalidTypeException When the property is missing and $requiredKey is true.
     */
    public function property(string $name, bool $requiredKey = true): static
    {
        $properties = $this->properties() ?? throw $this->wrongType('object|array');

        if (array_key_exists($name, $properties)) {
            return new static($properties[$name], $this->childPath($name));
        }

        if ($requiredKey) {
            throw $this->missingProperty($name);
        }

        return new static(null, $this->childPath($name));
    }

    /**
     * The items of yieldList() as a list.
     *
     * @return list<static<mixed>>
     *
     * @throws WrongTypeException When yieldList() throws.
     */
    public function list(bool $nullable = true): array
    {
        return iterator_to_array($this->yieldList($nullable), false);
    }

    /**
     * The items of yieldArray() as an array with the same keys. A later item with the same key
     * replaces an earlier one, which only happens with a Traversable object.
     *
     * @return array<array-key, static<mixed>>
     *
     * @throws WrongTypeException When yieldArray() throws, or yields a key that is not an int or string.
     */
    public function array(bool $nullable = true): array
    {
        $array = [];
        foreach ($this->yieldArray($nullable) as $key => $item) {
            if (!is_int($key) && !is_string($key)) {
                throw $item->wrongType('array-key', get_debug_type($key));
            }

            $array[$key] = $item;
        }

        return $array;
    }

    /**
     * Each item of an array or object value, indexed 0, 1, 2, …, as a new object of the same class
     * whose path is this path plus the index, like "tags.0". The value is iterated directly, so an
     * array or \stdClass is not copied: an array gives its items, a Traversable object (like an
     * iterator) what it yields, and another object its public property values. The value is
     * checked when this is called, not when iteration starts. Null gives no items when $nullable.
     *
     * @return \Generator<int, static<mixed>, mixed, void>
     *
     * @throws WrongTypeException When the value is neither an array nor an object, or is null when not allowed.
     */
    public function yieldList(bool $nullable = true): \Generator
    {
        return $this->yieldItems($this->iterable('list', $nullable), false);
    }

    /**
     * Like yieldList(), but yields the original keys, and the paths use them, like "user.name". A
     * Traversable object can yield keys of any type; one that is not an int or string shows its
     * type in the path, like "user.[stdClass]".
     *
     * @return \Generator<mixed, static<mixed>, mixed, void>
     *
     * @throws WrongTypeException When the value is neither an array nor an object, or is null when not allowed.
     */
    public function yieldArray(bool $nullable = true): \Generator
    {
        return $this->yieldItems($this->iterable('array', $nullable), true);
    }

    /**
     * A new object of the same class and path, holding a generator of the items of yieldArray()
     * that $callback keeps. Calls $callback($item, $key) for each one: $item is the item, wrapped
     * in this class with its own path, and $key its original key, which an iterator can make any
     * type. An item is kept when $callback returns true; an exception it throws comes through.
     *
     * The result can be chained: iterating it, or calling list(), listInt(), map() and the like on
     * it, gives the kept items with their own paths, so errors still point into the original input.
     * asList() on the result numbers them 0, 1, 2. The value is checked when this is called;
     * $callback runs when the result is iterated, which can only happen once. Null gives no items.
     *
     * @param callable(static<mixed>, mixed): bool $callback
     *
     * @return static<\Generator<mixed, static<mixed>, mixed, void>>
     *
     * @throws WrongTypeException When yieldArray() throws.
     */
    public function filter(callable $callback): static
    {
        return new static($this->filterItems($this->yieldArray(), $callback), $this->path);
    }

    /**
     * Like filter(), but keeps the items $validator does not throw an UntrustedValueException for.
     * Calls $validator($item, $key) for each item of yieldArray(): $item is the item, wrapped in
     * this class with its own path, and $key its original key, which an iterator can make any type.
     * Its return value is ignored, so it can be a void method that just checks, like fn($user) =>
     * $user->property('age')->asInt(false)->range(0, 150), and the original item is kept, not what
     * it returns.
     *
     * Other exceptions are not caught, so a bug in $validator or an invalid pattern given to
     * match() still comes through. The result chains and runs like filter()'s.
     *
     * @param callable(static<mixed>, mixed): mixed $validator
     *
     * @return static<\Generator<mixed, static<mixed>, mixed, void>>
     *
     * @throws WrongTypeException When yieldArray() throws.
     */
    public function filterValid(callable $validator): static
    {
        return $this->filter(static function (self $item, mixed $key) use ($validator): bool {
            try {
                $validator($item, $key);

                return true;
            } catch (UntrustedValueException) {
                return false;
            }
        });
    }

    /**
     * A new object of the same class and path, holding a generator of $mapper's result for each
     * item of yieldArray(), with the original keys. Calls $mapper($item, $key) for each one: $item
     * is the item, wrapped in this class with its own path, and $key its original key, which an
     * iterator can make any type. A plain result is wrapped with the item's path; an untrusted
     * value, like $item->property('age'), keeps its own, so errors report "users.1.age". An
     * exception $mapper throws comes through.
     *
     * The result can be chained like filter()'s: iterating it, or calling list(), listInt(),
     * filter() and the like on it, gives each result with its path, and the class of the new object
     * decides strictness from then on. The value is checked when this is called; $mapper runs when
     * the result is iterated, which can only happen once. Null gives no items.
     *
     * @template TResult
     *
     * @param callable(static<mixed>, mixed): TResult $mapper
     *
     * @return static<\Generator<mixed, (TResult is AbstractUntrustedValue<mixed> ? TResult : static<TResult>), mixed, void>>
     *
     * @throws WrongTypeException When yieldArray() throws.
     */
    public function map(callable $mapper): static
    {
        return new static($this->mapItems($this->yieldArray(), $mapper), $this->path);
    }

    /**
     * Yields $mapper's result for each item of yieldArray(), with the item's key. Calls
     * $mapper($item, $key) for each one: $item is the item, wrapped in this class with its own
     * path, and $key its original key, which an iterator can make any type. A plain result is
     * wrapped in a new object of this class with the item's path; an untrusted value is yielded as
     * it is.
     *
     * The value is checked when this is called; $mapper runs during iteration, and an exception it
     * throws comes through then. Null gives no items when $nullable.
     *
     * @template TResult
     *
     * @param callable(static<mixed>, mixed): TResult $mapper
     *
     * @return \Generator<mixed, (TResult is AbstractUntrustedValue<mixed> ? TResult : static<TResult>), mixed, void>
     *
     * @throws WrongTypeException When yieldArray() throws.
     */
    public function yieldMap(callable $mapper, bool $nullable = true): \Generator
    {
        return $this->mapItems($this->yieldArray($nullable), $mapper);
    }

    /**
     * A list of $mapper's result for each item, numbered 0, 1, 2, …. Calls $mapper($item, $index)
     * for each item of yieldList(): $item is the item, wrapped in this class with its own path, and
     * $index its position, from 0. A plain result is wrapped in a new object of this class with the
     * item's path; an untrusted value is kept as it is. An exception $mapper throws comes through.
     * Null gives an empty list when $nullable.
     *
     * @template TResult
     *
     * @param callable(static<mixed>, int): TResult $mapper
     *
     * @return list<(TResult is AbstractUntrustedValue<mixed> ? TResult : static<TResult>)>
     *
     * @throws WrongTypeException When yieldList() throws.
     */
    public function listMap(callable $mapper, bool $nullable = true): array
    {
        return iterator_to_array($this->mapItems($this->yieldList($nullable), $mapper), false);
    }

    /**
     * An array of $mapper's result for each item, with the item's key. Calls $mapper($item, $key)
     * for each item of array(): $item is the item, wrapped in this class with its own path, and
     * $key its original key, an int or string. A plain result is wrapped in a new object of this
     * class with the item's path; an untrusted value is kept as it is. An exception $mapper throws
     * comes through. Null gives an empty array when $nullable.
     *
     * @template TResult
     *
     * @param callable(static<mixed>, array-key): TResult $mapper
     *
     * @return array<array-key, (TResult is AbstractUntrustedValue<mixed> ? TResult : static<TResult>)>
     *
     * @throws WrongTypeException When yieldArray() throws, or yields a key that is not an int or string.
     */
    public function arrayMap(callable $mapper, bool $nullable = true): array
    {
        $array = [];
        foreach ($this->array($nullable) as $key => $item) {
            $array[$key] = $this->mapItem($item, $key, $mapper);
        }

        return $array;
    }

    /**
     * Yields $mapper's result for each item of yieldArray() as it is, without wrapping, with the
     * item's key. Calls $mapper($item, $key) for each one: $item is the item, wrapped in this class
     * with its own path, and $key its original key, which an iterator can make any type.
     *
     * The value is checked when this is called; $mapper runs during iteration, and an exception it
     * throws comes through then. Null gives no items when $nullable.
     *
     * @template TResult
     *
     * @param callable(static<mixed>, mixed): TResult $mapper
     *
     * @return \Generator<mixed, TResult, mixed, void>
     *
     * @throws WrongTypeException When yieldArray() throws.
     */
    public function yieldRawMap(callable $mapper, bool $nullable = true): \Generator
    {
        return $this->mapRawItems($this->yieldArray($nullable), $mapper);
    }

    /**
     * A list of $mapper's result for each item as it is, without wrapping, numbered 0, 1, 2, ….
     * Calls $mapper($item, $index) for each item of yieldList(): $item is the item, wrapped in this
     * class with its own path, and $index its position, from 0. An exception $mapper throws comes
     * through. Null gives an empty list when $nullable.
     *
     * @template TResult
     *
     * @param callable(static<mixed>, int): TResult $mapper
     *
     * @return list<TResult>
     *
     * @throws WrongTypeException When yieldList() throws.
     */
    public function listRawMap(callable $mapper, bool $nullable = true): array
    {
        return iterator_to_array($this->mapRawItems($this->yieldList($nullable), $mapper), false);
    }

    /**
     * An array of $mapper's result for each item as it is, without wrapping, with the item's key.
     * Calls $mapper($item, $key) for each item of array(): $item is the item, wrapped in this class
     * with its own path, and $key its original key, an int or string. An exception $mapper throws
     * comes through. Null gives an empty array when $nullable.
     *
     * @template TResult
     *
     * @param callable(static<mixed>, array-key): TResult $mapper
     *
     * @return array<array-key, TResult>
     *
     * @throws WrongTypeException When yieldArray() throws, or yields a key that is not an int or string.
     */
    public function arrayRawMap(callable $mapper, bool $nullable = true): array
    {
        $array = [];
        foreach ($this->array($nullable) as $key => $item) {
            $array[$key] = $mapper($item, $key);
        }

        return $array;
    }

    /**
     * Yields the items of $mapper's result for each item of yieldArray(), one by one (yield from).
     * Calls $mapper($item, $key) for each one: $item is the item, wrapped in this class with its
     * own path, and $key its original key, which an iterator can make any type. It must return an
     * iterable: another untrusted value, like $item->property('tags'), yields its items wrapped
     * with their paths, and a plain array its values as they are.
     *
     * Keys come from each result, so they repeat across items; use iterator_to_array($generator,
     * false) to collect them. The value is checked when this is called; $mapper runs during
     * iteration, and an exception it throws comes through then. Null gives no items when $nullable.
     *
     * @template TKey
     * @template TResult
     *
     * @param callable(static<mixed>, mixed): iterable<TKey, TResult> $mapper
     *
     * @return \Generator<TKey, TResult, mixed, void>
     *
     * @throws WrongTypeException When yieldArray() throws.
     */
    public function yieldFlatMap(callable $mapper, bool $nullable = true): \Generator
    {
        return $this->flatMapItems($this->yieldArray($nullable), $mapper);
    }

    /**
     * Yields each item of yieldArray(), with its key, as an int from int() with the same $nullable,
     * so null items are kept unless $nullable is false. Unlike yieldArray(), a null value throws
     * rather than giving no items. Items are converted during iteration, so a bad item throws then.
     *
     * @return ($nullable is false ? \Generator<mixed, int, mixed, void> : \Generator<mixed, int|null, mixed, void>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object; a bad item throws during iteration.
     */
    public function yieldInt(bool $nullable = true): \Generator
    {
        return $this->yieldRawMap(static fn(self $item): ?int => $item->int($nullable), false);
    }

    /**
     * Each item of list(), as an int from int() with the same $nullable, so null items are kept
     * unless $nullable is false. Unlike list(), a null value throws rather than giving an empty
     * list.
     *
     * @return ($nullable is false ? list<int> : list<int|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function listInt(bool $nullable = true): array
    {
        return $this->listRawMap(static fn(self $item): ?int => $item->int($nullable), false);
    }

    /**
     * Each item of array(), with its key, as an int from int() with the same $nullable, so null
     * items are kept unless $nullable is false. Unlike array(), a null value throws rather than
     * giving an empty array.
     *
     * @return ($nullable is false ? array<array-key, int> : array<array-key, int|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function arrayInt(bool $nullable = true): array
    {
        return $this->arrayRawMap(static fn(self $item): ?int => $item->int($nullable), false);
    }

    /**
     * Yields each item of yieldArray(), with its key, as a float from float() with the same
     * $nullable, so null items are kept unless $nullable is false. Unlike yieldArray(), a null
     * value throws rather than giving no items. Items are converted during iteration, so a bad item
     * throws then.
     *
     * @return ($nullable is false ? \Generator<mixed, float, mixed, void> : \Generator<mixed, float|null, mixed, void>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object; a bad item throws during iteration.
     */
    public function yieldFloat(bool $nullable = true): \Generator
    {
        return $this->yieldRawMap(static fn(self $item): ?float => $item->float($nullable), false);
    }

    /**
     * Each item of list(), as a float from float() with the same $nullable, so null items are kept
     * unless $nullable is false. Unlike list(), a null value throws rather than giving an empty
     * list.
     *
     * @return ($nullable is false ? list<float> : list<float|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function listFloat(bool $nullable = true): array
    {
        return $this->listRawMap(static fn(self $item): ?float => $item->float($nullable), false);
    }

    /**
     * Each item of array(), with its key, as a float from float() with the same $nullable, so null
     * items are kept unless $nullable is false. Unlike array(), a null value throws rather than
     * giving an empty array.
     *
     * @return ($nullable is false ? array<array-key, float> : array<array-key, float|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function arrayFloat(bool $nullable = true): array
    {
        return $this->arrayRawMap(static fn(self $item): ?float => $item->float($nullable), false);
    }

    /**
     * Yields each item of yieldArray(), with its key, as a string from string() with the same
     * $nullable, so null items are kept unless $nullable is false. Unlike yieldArray(), a null
     * value throws rather than giving no items. Items are converted during iteration, so a bad item
     * throws then.
     *
     * @return ($nullable is false ? \Generator<mixed, string, mixed, void> : \Generator<mixed, string|null, mixed, void>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object; a bad item throws during iteration.
     */
    public function yieldString(bool $nullable = true): \Generator
    {
        return $this->yieldRawMap(static fn(self $item): ?string => $item->string($nullable), false);
    }

    /**
     * Each item of list(), as a string from string() with the same $nullable, so null items are
     * kept unless $nullable is false. Unlike list(), a null value throws rather than giving an
     * empty list.
     *
     * @return ($nullable is false ? list<string> : list<string|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function listString(bool $nullable = true): array
    {
        return $this->listRawMap(static fn(self $item): ?string => $item->string($nullable), false);
    }

    /**
     * Each item of array(), with its key, as a string from string() with the same $nullable, so
     * null items are kept unless $nullable is false. Unlike array(), a null value throws rather
     * than giving an empty array.
     *
     * @return ($nullable is false ? array<array-key, string> : array<array-key, string|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function arrayString(bool $nullable = true): array
    {
        return $this->arrayRawMap(static fn(self $item): ?string => $item->string($nullable), false);
    }

    /**
     * Yields each item of yieldArray(), with its key, as a bool from bool() with the same
     * $nullable, so null items are kept unless $nullable is false. Unlike yieldArray(), a null
     * value throws rather than giving no items. Items are converted during iteration, so a bad item
     * throws then.
     *
     * @return ($nullable is false ? \Generator<mixed, bool, mixed, void> : \Generator<mixed, bool|null, mixed, void>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object; a bad item throws during iteration.
     */
    public function yieldBool(bool $nullable = true): \Generator
    {
        return $this->yieldRawMap(static fn(self $item): ?bool => $item->bool($nullable), false);
    }

    /**
     * Each item of list(), as a bool from bool() with the same $nullable, so null items are kept
     * unless $nullable is false. Unlike list(), a null value throws rather than giving an empty
     * list.
     *
     * @return ($nullable is false ? list<bool> : list<bool|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function listBool(bool $nullable = true): array
    {
        return $this->listRawMap(static fn(self $item): ?bool => $item->bool($nullable), false);
    }

    /**
     * Each item of array(), with its key, as a bool from bool() with the same $nullable, so null
     * items are kept unless $nullable is false. Unlike array(), a null value throws rather than
     * giving an empty array.
     *
     * @return ($nullable is false ? array<array-key, bool> : array<array-key, bool|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function arrayBool(bool $nullable = true): array
    {
        return $this->arrayRawMap(static fn(self $item): ?bool => $item->bool($nullable), false);
    }

    /**
     * Yields each item of yieldArray(), with its key, as a scalar from requireScalar() with the
     * same $nullable, so null items are kept unless $nullable is false. Unlike yieldArray(), a null
     * value throws rather than giving no items. Items are converted during iteration, so a bad item
     * throws then.
     *
     * @return ($nullable is false ? \Generator<mixed, scalar, mixed, void> : \Generator<mixed, scalar|null, mixed, void>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object; a bad item throws during iteration.
     */
    public function yieldScalar(bool $nullable = true): \Generator
    {
        return $this->yieldRawMap(static fn(self $item): int|float|string|bool|null => $item->requireScalar($nullable)->value, false);
    }

    /**
     * Each item of list(), as a scalar from requireScalar() with the same $nullable, so null items
     * are kept unless $nullable is false. Unlike list(), a null value throws rather than giving an
     * empty list.
     *
     * @return ($nullable is false ? list<scalar> : list<scalar|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function listScalar(bool $nullable = true): array
    {
        return $this->listRawMap(static fn(self $item): int|float|string|bool|null => $item->requireScalar($nullable)->value, false);
    }

    /**
     * Each item of array(), with its key, as a scalar from requireScalar() with the same $nullable,
     * so null items are kept unless $nullable is false. Unlike array(), a null value throws rather
     * than giving an empty array.
     *
     * @return ($nullable is false ? array<array-key, scalar> : array<array-key, scalar|null>)
     *
     * @throws WrongTypeException When the value is null or neither an array nor an object, or an item can't be converted.
     */
    public function arrayScalar(bool $nullable = true): array
    {
        return $this->arrayRawMap(static fn(self $item): int|float|string|bool|null => $item->requireScalar($nullable)->value, false);
    }

    /**
     * Makes the value usable in foreach, giving what yieldArray() gives: each item with its key,
     * as a new object of the same class whose path is this path plus the key. Null gives no items.
     * PHP calls this when the foreach starts, so that is when a value that is neither an array nor
     * an object throws.
     *
     * @return \Generator<mixed, static<mixed>, mixed, void>
     *
     * @throws WrongTypeException When the value is neither an array, an object nor null.
     */
    public function getIterator(): \Generator
    {
        return $this->yieldArray();
    }

    /**
     * Serializes to the raw value, unchanged. A filter() or map() result holds a generator, which
     * json_encode() turns into {}; collect it with list() first.
     *
     * @return TValue
     */
    public function jsonSerialize(): mixed
    {
        return $this->value;
    }

    /**
     * What yieldList() and yieldArray() iterate: an array or a Traversable object itself, the
     * public properties of another object, or an empty array for null when $nullable.
     * get_object_vars() shares a \stdClass's property table rather than copying it.
     *
     * @return iterable<mixed, mixed>
     *
     * @throws WrongTypeException When the value is neither an array nor an object, or is null when not allowed.
     */
    private function iterable(string $expectedType, bool $nullable): iterable
    {
        if ($this->value === null && $nullable) {
            return [];
        }

        if (is_iterable($this->value)) {
            return $this->value;
        }

        if (is_object($this->value)) {
            return get_object_vars($this->value);
        }

        throw $this->wrongType($expectedType);
    }

    /**
     * Each item of $items as a new object of this class with the path of its key, keyed as in
     * $items, or numbered 0, 1, 2, … unless $keepKeys. An item that already is an untrusted value
     * keeps its own path.
     *
     * @param iterable<mixed, mixed> $items
     *
     * @return \Generator<mixed, static<mixed>, mixed, void>
     */
    private function yieldItems(iterable $items, bool $keepKeys): \Generator
    {
        $index = 0;
        foreach ($items as $key => $item) {
            if (!$keepKeys) {
                $key = $index++;
            }

            // An item that already is an untrusted value, as from filter() or map(), keeps its own
            // path, so errors still point into the original input.
            if ($item instanceof AbstractUntrustedValue) {
                yield $key => new static($item, $item->path);

                continue;
            }

            $name = is_int($key) || is_string($key) ? (string) $key : '[' . get_debug_type($key) . ']';

            yield $key => new static($item, $this->childPath($name));
        }
    }

    /**
     * The items $callback returns true for, with their keys.
     *
     * @param \Generator<mixed, static<mixed>, mixed, void> $items
     * @param callable(static<mixed>, mixed): bool $callback
     *
     * @return \Generator<mixed, static<mixed>, mixed, void>
     */
    private function filterItems(\Generator $items, callable $callback): \Generator
    {
        foreach ($items as $key => $item) {
            if ($callback($item, $key)) {
                yield $key => $item;
            }
        }
    }

    /**
     * $mapper's result for each item, with its key, wrapped as mapItem() does.
     *
     * @template TKey
     * @template TResult
     *
     * @param \Generator<TKey, static<mixed>, mixed, void> $items
     * @param callable(static<mixed>, TKey): TResult $mapper
     *
     * @return \Generator<TKey, (TResult is AbstractUntrustedValue<mixed> ? TResult : static<TResult>), mixed, void>
     */
    private function mapItems(\Generator $items, callable $mapper): \Generator
    {
        foreach ($items as $key => $item) {
            yield $key => $this->mapItem($item, $key, $mapper);
        }
    }

    /**
     * $mapper's result for the item, wrapped in this class with the item's path unless it already
     * is an AbstractUntrustedValue.
     *
     * @template TKey
     * @template TResult
     *
     * @param static<mixed> $item
     * @param TKey $key
     * @param callable(static<mixed>, TKey): TResult $mapper
     *
     * @return (TResult is AbstractUntrustedValue<mixed> ? TResult : static<TResult>)
     */
    private function mapItem(self $item, mixed $key, callable $mapper): self
    {
        $result = $mapper($item, $key);

        if ($result instanceof AbstractUntrustedValue) {
            return $result;
        }

        return new static($result, $item->path);
    }

    /**
     * $mapper's result for each item, with its key, as it is.
     *
     * @template TKey
     * @template TResult
     *
     * @param \Generator<TKey, static<mixed>, mixed, void> $items
     * @param callable(static<mixed>, TKey): TResult $mapper
     *
     * @return \Generator<TKey, TResult, mixed, void>
     */
    private function mapRawItems(\Generator $items, callable $mapper): \Generator
    {
        foreach ($items as $key => $item) {
            yield $key => $mapper($item, $key);
        }
    }

    /**
     * The items of $mapper's result for each item, one by one (yield from).
     *
     * @template TKey
     * @template TResult
     *
     * @param \Generator<mixed, static<mixed>, mixed, void> $items
     * @param callable(static<mixed>, mixed): iterable<TKey, TResult> $mapper
     *
     * @return \Generator<TKey, TResult, mixed, void>
     */
    private function flatMapItems(\Generator $items, callable $mapper): \Generator
    {
        foreach ($items as $key => $item) {
            yield from $mapper($item, $key);
        }
    }

    /**
     * The items of $traversable with their keys. A later item with the same key replaces an earlier
     * one. Unlike iterator_to_array(), a key that is not an int or string throws a
     * WrongTypeException rather than a TypeError.
     *
     * @param \Traversable<mixed, mixed> $traversable
     *
     * @return array<array-key, mixed>
     *
     * @throws WrongTypeException When a key is not an int or string.
     */
    private function traversableToArray(\Traversable $traversable): array
    {
        $array = [];
        foreach ($traversable as $key => $item) {
            if (!is_int($key) && !is_string($key)) {
                throw $this->wrongType('array-key', get_debug_type($key));
            }

            $array[$key] = $item;
        }

        return $array;
    }

    /**
     * $json decoded, with JSON objects as \stdClass objects.
     *
     * @throws WrongTypeException When $json is not valid JSON, with the \JsonException as its previous exception.
     */
    private static function decodeJson(string $json, string $path): mixed
    {
        try {
            return json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new WrongTypeException(
                $path === ''
                    ? 'Invalid JSON: ' . $exception->getMessage()
                    : sprintf('Invalid JSON at %s: %s', $path, $exception->getMessage()),
                'json',
                'string',
                $path,
                $exception,
            );
        }
    }

    /**
     * The public properties of an object value, or an array value itself, or null for other values.
     *
     * @return array<array-key, mixed>|null
     */
    private function properties(): ?array
    {
        if (is_object($this->value)) {
            return get_object_vars($this->value);
        }

        if (is_array($this->value)) {
            return $this->value;
        }

        return null;
    }

    /**
     * The path of a property of this value, like "user.age", or just the name at the root.
     */
    private function childPath(string $name): string
    {
        return $this->path === '' ? $name : $this->path . '.' . $name;
    }

    /**
     * Whether $subject matches $regexp. An invalid pattern is the caller's mistake, so it
     * throws an \InvalidArgumentException. Other PCRE errors, like an exhausted backtrack limit or
     * invalid UTF-8 with the u modifier, come from the value, so they throw an InvalidTypeException
     * for $filter.
     *
     * @throws InvalidTypeException When PCRE fails on the value.
     * @throws \InvalidArgumentException When $regexp is not a valid pattern.
     */
    private function pregMatch(string $subject, string $regexp, string $filter): bool
    {
        error_clear_last();
        $result = @preg_match($regexp, $subject);

        if ($result !== false) {
            return $result === 1;
        }

        if (preg_last_error() === PREG_INTERNAL_ERROR) {
            throw new \InvalidArgumentException(error_get_last()['message'] ?? preg_last_error_msg());
        }

        throw $this->invalid($filter, sprintf('a value PCRE can check against %s (%s)', $regexp, preg_last_error_msg()));
    }

    /**
     * A WrongTypeException for this value and path, expecting $expectedType.
     *
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

    /**
     * An InvalidTypeException for a filter the value failed, with a message like
     * "Expected at least 0 at user.age".
     */
    private function invalid(string $filter, string $expected): InvalidTypeException
    {
        return new InvalidTypeException(
            $this->path === ''
                ? sprintf('Expected %s', $expected)
                : sprintf('Expected %s at %s', $expected, $this->path),
            $filter,
            $this->path,
        );
    }

    /**
     * An InvalidTypeException with filter "required" for the missing property $name, at its path.
     */
    private function missingProperty(string $name): InvalidTypeException
    {
        $path = $this->childPath($name);

        return new InvalidTypeException(sprintf('Missing required property %s', $path), 'required', $path);
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
     * Whether the value is not null. When it is not, PHPStan narrows this object's TValue to match.
     *
     * @phpstan-assert-if-true static<(int|float|string|bool|array<mixed>|object|resource)&TValue> $this
     */
    private function valueIsNotNull(): bool
    {
        return $this->value !== null;
    }

    /**
     * Whether the value is an int, float, string or bool. When it is, PHPStan narrows this
     * object's TValue to match.
     *
     * @phpstan-assert-if-true static<scalar&TValue> $this
     */
    private function valueIsScalar(): bool
    {
        return is_scalar($this->value);
    }

    /**
     * Whether the value is a string. PHPStan narrows this object's TValue to match either way.
     *
     * @phpstan-assert-if-true static<string&TValue> $this
     * @phpstan-assert-if-true string $this->value
     * @phpstan-assert-if-false static<(int|float|bool|array|object|resource|null)&TValue> $this
     */
    private function valueIsString(): bool
    {
        return is_string($this->value);
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
     * Whether the value is identical to one of $allowed. When it is, PHPStan narrows this object's
     * TValue to match.
     *
     * @template TAllowed
     *
     * @param array<array-key, TAllowed> $allowed
     *
     * @phpstan-assert-if-true static<TAllowed&TValue> $this
     */
    private function valueIsOneOf(array $allowed): bool
    {
        return in_array($this->value, $allowed, true);
    }

    /**
     * Always true. PHPStan only lets $this widen to a static<TValue|TOther> return type after an
     * assertion has typed it as static<TValue>, so replace() checks this before returning $this.
     *
     * @phpstan-assert-if-true static<TValue> $this
     */
    private function keepsTValue(): bool
    {
        return true;
    }
}
