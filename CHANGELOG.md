# Changelog

All notable changes to this project. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project uses [semantic versioning](https://semver.org/); before 1.0, a minor version can
change behaviour.

## [0.2.0] - Unreleased

### Added

- `bool()`, `isBool()`, `asBool()` and `listBool()`, `arrayBool()`, `yieldBool()`. The loose class
  also accepts `1`/`0` and `"1"`, `"true"`, `"yes"`, `"on"` / `"0"`, `"false"`, `"no"`, `"off"`.
- `enum()`, reading a backed enum case with the value converted to its backing type.
- `date()`, parsing with a format and rejecting dates PHP would roll over, like 2026-02-30.
- `json()`, decoding a field that holds a JSON string, with the paths continuing into it.
- `split()` and `csv()`, for comma-separated fields; `csv()` handles quotes.
- `count()` and `oneOf()` validators.
- `isIterable()`.
- `filter()`, `filterValid()` and `map()`, which return a new value holding a lazy generator, so they
  chain, and the items keep their paths. `filterValid()` keeps the items a callback doesn't throw
  an `UntrustedValueException` for.
- `yieldFlatMap()`.
- `foreach` over any value, through `IteratorAggregate`, giving what `yieldArray()` gives.
- `docs/methods.md`, a reference for every method, and `docs/examples.md` with recipes.

### Changed

- `asList()` and `asArray()` on an iterator or generator now give its items, instead of the
  object's internal properties.
- `asArray()` on a plain object now gives only its public properties. It used to cast with
  `(array)`, which also gave protected and private properties, under keys like `"\0*\0name"`. A
  `\stdClass`, as from JSON, gives the same result as before.
- Passing an untrusted value to the constructor takes its raw value, so a value is never wrapped
  twice.
- When iterating a value whose items already are untrusted values, as from `filter()` or `map()`,
  each item keeps its own path instead of getting one from its key.
- `length()` and `count()` messages use the singular for 1: "at least 1 character".
- `count()` reports the expected type `object|array`, like `property()`.
- The methods are ordered in the source as in the README, and the docblocks describe callbacks,
  `@throws` and null handling more precisely.

## [0.1.1] - 2026-11-10

### Changed

- The PHP requirement is lowered from `^8.5` to `^8.1`. The library code ran unchanged on PHP 8.1
  to 8.5 and the 8.6 release candidate.

## [0.1.0] - 2026-11-10

The first release.

### Added

- `UntrustedValue`, which converts compatible values, and `UntrustedStrictValue`, which only accepts
  exact types, sharing `AbstractUntrustedValue`, with `asStrict()` and `asLoose()` to switch.
- A path on every value, carried into every error.
- `fromJson()`, `raw()` and `jsonSerialize()`.
- Getters: `int()`, `float()`, `string()`.
- Checks: `isInt()`, `isFloat()`, `isString()`, `isNull()`, `isScalar()`, `isObject()`, `isArray()`,
  `isList()`, `isMap()`, `hasProperty()`, `hasProperties()`.
- Requires: `requireNull()`, `requireScalar()`, `requireArray()`, `requireObject()`,
  `requireList()`, `requireMap()`, `requireProperty()`.
- Conversions: `asInt()`, `asFloat()`, `asString()`, `asList()`, `asArray()`, `asObject()`.
- Validators: `range()`, `length()`, `match()`, `notMatch()`.
- Changes: `trim()`, `replace()`, `default()`, `blankDefault()`.
- Nested values: `property()`, `list()`, `array()`, `yieldList()`, `yieldArray()`, the map methods,
  and the typed collections like `listInt()`.
- `WrongTypeException` and `InvalidTypeException`, both `UntrustedValueException`s that serialize to
  JSON.
- A PHPStan template, `UntrustedValue<TValue>`, narrowed along the chain.

[0.2.0]: https://github.com/puggan/UntrustedValue/compare/v0.1.1...main
[0.1.1]: https://github.com/puggan/UntrustedValue/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/puggan/UntrustedValue/releases/tag/v0.1.0
