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
     * Like yieldArray(), but each item is passed to $mapper, with its key, and the result is
     * yielded instead. A result that is not an AbstractUntrustedValue is wrapped in a new object of
     * this class with the item's path. The value is checked when this is called; $mapper runs
     * during iteration.
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
     * Like list(), but with each item passed to $mapper, with its index, as yieldMap() does.
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
     * Like array(), but with each item passed to $mapper, with its key, as yieldMap() does.
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
     * Like yieldMap(), but $mapper's results are yielded as they are, without wrapping.
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
     * Like listMap(), but $mapper's results are returned as they are, without wrapping.
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
     * Like arrayMap(), but $mapper's results are returned as they are, without wrapping.
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
     * Whether the value has the property: a public property of an object, or a key of an array.
     * Other values have no properties, so it is false for them.
     */
    public function hasProperty(string $name): bool
    {
        $properties = $this->properties();

        return $properties !== null && array_key_exists($name, $properties);
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

            $name = is_int($key) || is_string($key) ? (string) $key : '[' . get_debug_type($key) . ']';

            yield $key => new static($item, $this->childPath($name));
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

    private function missingProperty(string $name): InvalidTypeException
    {
        $path = $this->childPath($name);

        return new InvalidTypeException(sprintf('Missing required property %s', $path), 'required', $path);
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
     * Whether the value is a string. PHPStan narrows this object's TValue to match either way.
     *
     * @phpstan-assert-if-true static<string&TValue> $this
     * @phpstan-assert-if-false static<(int|float|bool|array|object|resource|null)&TValue> $this
     */
    private function valueIsString(): bool
    {
        return is_string($this->value);
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
