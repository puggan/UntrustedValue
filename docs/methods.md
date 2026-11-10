# Methods

Every method, grouped the way the source is ordered. For recipes, see [examples](examples.md).

- [Conventions](#conventions)
- [Construction](#construction)
- [Get a plain value](#get-a-plain-value)
- [Check](#check)
- [Require](#require)
- [Convert](#convert)
- [Validate](#validate)
- [Change](#change)
- [Read nested values](#read-nested-values)
- [Filter and map](#filter-and-map)
- [Typed collections](#typed-collections)
- [Errors](#errors)

## Conventions

These hold for every group below, so the sections only mention exceptions to them.

**Two classes.** `UntrustedValue` is loose: its getters convert compatible values, so `int()` accepts
`"42"`. `UntrustedStrictValue` is strict: its getters only accept the exact type, except that
`float()` also widens an int, as JSON numbers like `1` decode to int. Everything else is shared in
`AbstractUntrustedValue`, which is the type to use when a function accepts either. Methods that
return a new object return the same class as the one they were called on, so a strict value's
properties and items are strict too. The exceptions are `asInt()`, `asFloat()`, `asString()` and
`asBool()`, which always return an `UntrustedStrictValue`, and `asStrict()` and `asLoose()`, which
switch between the two.

**Paths.** Every value knows where in the input it came from: `""` at the root, then `user`,
`user.tags`, `user.tags.2`. Every error carries the path of the value that failed, so a 422
response can point at the exact field.

**`$nullable`.** Most methods take `bool $nullable = true`. With the default, null is accepted and
passes through: getters return `null`, `is*()` return `true`, `require*()` and `as*()` return the
object unchanged. With `false`, null fails like any other wrong type. Validators and changes, like
`range()`, `length()`, `trim()` and `split()`, have no parameter and let null through, so
`->property('age', false)->asInt()->range(0, 150)->int()` reads an optional age. The one exception is
`match()`, which needs a string to match.

**Same object or new.** Checks, requires and validators return the same object. Conversions and
changes return the same object when there is nothing to change, and otherwise a new object with the
same class and path. Values are never wrapped twice: `new UntrustedValue($untrusted)` takes the
raw value out of `$untrusted`.

**Laziness.** Methods starting with `yield`, and `filter()`, `filterValid()` and `map()`, are lazy:
the value is checked when you call them, but items are converted, and callbacks run, while
iterating. The result can only be iterated once, as it is a generator.

**PHPStan.** The wrapped type is a template, `UntrustedValue<TValue>`, inferred from the
constructor and narrowed along the chain: after `->requireList(false)` it is a `list<mixed>`, after
`->asInt(false)` an `int`. Nothing checks `TValue` at runtime; a docblock saying
`UntrustedValue<int>` only describes what you expect.

## Construction

| Method | Does |
|---|---|
| `new UntrustedValue($value, string $path = '')` | Wraps `$value`. An untrusted value passed in is unwrapped. |
| `new UntrustedStrictValue($value, string $path = '')` | The same, strict. |
| `UntrustedValue::fromJson(string $json)` | Decodes JSON, with objects as `\stdClass`, at the root path. Works on either class. |

```php
$data = UntrustedValue::fromJson($requestBody);
$query = new UntrustedValue($_GET);
$header = new UntrustedValue($request->getHeaderLine('X-Page'), 'header.X-Page');
```

## Get a plain value

These end a chain and return a plain PHP value.

| Method | Returns | `UntrustedValue` also converts | `UntrustedStrictValue` |
|---|---|---|---|
| `int(bool $nullable = true)` | `int` | numeric strings like `"42"`, `" 42"`, `"1e3"`; floats within 0.001 above a whole number, like `42.0` | `int` only |
| `float(bool $nullable = true)` | `float` | ints, numeric strings like `"4.2"`; must be finite | `float`, and `int` widened |
| `string(bool $nullable = true)` | `string` | ints and finite floats, cast like `"42"`, `"4.2"`; never bools | `string` only |
| `bool(bool $nullable = true)` | `bool` | `1`/`0`, and `"1"`, `"true"`, `"yes"`, `"on"` / `"0"`, `"false"`, `"no"`, `"off"` in any case | `bool` only |
| `enum(class-string $enum, bool $nullable = true)` | a case of the backed enum | reads the value with `int()` or `string()`, matching the backing type | the same, strictly |
| `date(string $format, bool $nullable = true, ?\DateTimeZone $timezone = null)` | `\DateTimeImmutable` | reads the value with `string()` | the same, strictly |
| `raw()` | the value as it is | | |

The return type narrows: `int(false)` is `int`, `int()` is `int|null`, and when `TValue` already is
an int type like `int<0, 150>`, that is returned.

`date()` rejects dates PHP would roll over, like `2026-02-30` becoming March 2. Start the format with
`!`, like `!Y-m-d`, so the time isn't taken from now.

```php
$page = $query->property('page', false)->int() ?? 1;
$sort = $query->property('sort')->enum(Sort::class, false);
$since = $query->property('since', false)->date('!Y-m-d');
```

## Check

These return a `bool` and never throw.

| Method | True when |
|---|---|
| `isInt()`, `isFloat()`, `isString()`, `isBool()` | the matching getter with the same `$nullable` would succeed, so they follow the class |
| `isNull()` | the value is null |
| `isScalar()` | `is_scalar()` |
| `isIterable()` | `is_iterable()`: an array or a `Traversable`; a `\stdClass` is not |
| `isObject()`, `isArray()` | `is_object()`, `is_array()` |
| `isList()` | an array with keys 0, 1, 2, … in order; `[]` is a list |
| `isMap()` | an array that is not a list |
| `hasProperties()` | an object or a map |
| `hasProperty(string $name)` | an object with that public property, or an array with that key |

All but `isNull()` and `hasProperty()` take `bool $nullable = true`.

## Require

Like the matching `is*()`, but return the object, or throw a `WrongTypeException`. The object's
`TValue` narrows, so PHPStan knows the shape afterwards.

`requireNull()`, `requireScalar()`, `requireArray()`, `requireObject()`, `requireList()`,
`requireMap()`, `requireProperty(string $name)`.

```php
$tags = $data->property('tags')->requireList(false);   // UntrustedValue<list<mixed>>
```

`requireProperty()` throws an `InvalidTypeException` with filter `required` when the property is
missing, the same as `property()`.

## Convert

| Method | Returns |
|---|---|
| `asStrict()`, `asLoose()` | the value as the other class, or the same object |
| `asInt()`, `asFloat()`, `asString()`, `asBool()` | an `UntrustedStrictValue` holding the converted value, with the same path, so later checks know its type |
| `asList()` | a list: the same object, or the values of another array, an iterator's items, or an object's public property values |
| `asArray()` | an array: the same object, an iterator's items with keys, or an object's public properties |
| `asObject()` | an object: the same object, or a map cast to `\stdClass`; a list throws |
| `json()` | a value holding a JSON string, decoded, with the same path, so fields get paths like `payload.id` |

`asInt()` and friends are how you chain on a converted value:

```php
$age = $user->property('age')->asInt(false)->range(0, 150)->int();
```

Reading an iterator with `asList()` or `asArray()` uses it up. Only public properties are ever read
from objects, so protected and private ones never leak out.

## Validate

These return the same object, or throw an `InvalidTypeException` with the filter name below. Null
passes.

| Method | Fails when | Filter |
|---|---|---|
| `range(int\|float\|string\|null $min = null, …$max = null)` | the value is below `$min` or above `$max`, by PHP's `<` and `>` | `min`, `max` |
| `length(?int $max = null, int $min = 0)` | `string()` of the value is shorter or longer, counted with `mb_strlen()` | `minLength`, `maxLength` |
| `count(?int $max = null, int $min = 0)` | there are fewer or more items | `minCount`, `maxCount` |
| `oneOf(array $allowed)` | the value is not identical (`===`) to one of `$allowed` | `oneOf` |
| `match(string $regexp)` | `string()` of the value doesn't match | `match` |
| `notMatch(string $regexp)` | `string()` of the value matches | `notMatch` |

- **No type checks:** `range()` and `oneOf()` don't check or convert the type, so convert first:
  `->asInt(false)->range(1, 100)`.
- **Values without a string:** `match()` throws a `WrongTypeException` when there is no string to
  check. `notMatch()` lets such values through, as they can't match.
- **Invalid patterns:** a regexp that doesn't compile throws `\InvalidArgumentException`, since that
  is a bug, not bad input.
- **Counting iterators:** `count()` on an iterator collects it into an array first, so the result can
  still be used, and returns a new object.

## Change

| Method | Does |
|---|---|
| `trim()` | `trim()` on a string value; other values pass unchanged |
| `replace(mixed $from, mixed $to)` | `$to` when the value is identical to `$from` |
| `default(mixed $defaultValue)` | replaces null; the type loses its null |
| `blankDefault(mixed $defaultValue = null)` | replaces `''`; `trim()` first to include whitespace-only strings |
| `split(string $separator)` | `explode()` of `string()`, a list of strings; `''` gives `[]` |
| `csv(string $separator = ',', string $enclosure = '"', string $escape = '')` | one CSV record from `string()`, with quoted fields; `''` gives `[]` |

```php
$ids = $query->property('ids', false)->split(',')->listInt(false);   // "3,5,8" → [3, 5, 8]
$note = $data->property('note')->trim()->blankDefault()->string();   // "   " → null
```

## Read nested values

Each item comes wrapped in the same class, with its path.

| Method | Gives |
|---|---|
| `property(string $name, bool $requiredKey = true)` | one property or array key; a missing one throws, or gives null with `false` |
| `list(bool $nullable = true)` | every item, numbered 0, 1, 2, … |
| `array(bool $nullable = true)` | every item with its key |
| `yieldList()`, `yieldArray()` | the same, lazily |
| `foreach ($value as $key => $item)` | the same as `yieldArray()` |

Arrays give their items, iterators what they yield, and other objects their public properties. Null
gives no items unless `$nullable` is false.

## Filter and map

`filter()`, `filterValid()` and `map()` return a new object holding a lazy generator, so they chain,
and the items keep their paths:

```php
$names = $users
    ->filterValid(fn(UntrustedValue $user) => $user->property('age')->asInt(false))
    ->filter(fn(UntrustedValue $user) => $user->property('age')->int(false) >= 18)
    ->map(fn(UntrustedValue $user) => $user->property('name'))
    ->listString(false);
```

| Method | Callback | Keeps |
|---|---|---|
| `filter(callable $callback)` | `$callback($item, $key): bool` | the items it returns true for |
| `filterValid(callable $validator)` | `$validator($item, $key)`, return value ignored | the items it doesn't throw an `UntrustedValueException` for; other exceptions come through |
| `map(callable $mapper)` | `$mapper($item, $key): mixed` | its results |

The other map methods collect or yield right away instead of chaining. "Wrapped" means a plain
result is wrapped with the item's path, while an untrusted result is kept as it is:

| Method | Gives | Callback gets |
|---|---|---|
| `listMap()`, `arrayMap()`, `yieldMap()` | wrapped results | `$item` and its index (list) or key |
| `listRawMap()`, `arrayRawMap()`, `yieldRawMap()` | results as they are | the same |
| `yieldFlatMap()` | the items of each result, one by one (`yield from`) | `$item`, `$key` |

`$item` is always the item wrapped in the same class with its own path. Exceptions a callback throws
come through, during iteration for the lazy methods.

## Typed collections

`listInt()`, `arrayInt()`, `yieldInt()`, and the same for `Float`, `String`, `Bool` and `Scalar`.
They convert every item with the matching getter, so the class decides how strict it is. `$nullable`
is about null *items*, which are kept by default. A null *container* always throws.

```php
$ids = $data->property('ids')->listInt(false);   // list<int>; a bad item throws with its path
```

## Errors

Every exception about a bad value is an `UntrustedValueException`, which has a `path` and serializes
to JSON.

| Exception | Means | Fields |
|---|---|---|
| `WrongTypeException` | the value has the wrong type | `expectedType`, `givenType`, `path` |
| `InvalidTypeException` | the value has the right type, but failed a check | `filter`, `path` |

`givenType` is usually `get_debug_type()` of the value. These more precise ones also occur:

| Given type | Means |
|---|---|
| `non-numeric-string` | `int()` or `float()` on a string like `"abc"` |
| `fractional-float`, `fractional-numeric-string` | `int()` on `4.2` or `"4.2"` |
| `out-of-range-float`, `out-of-range-numeric-string` | `int()` on a number too big for an int |
| `non-finite-float`, `non-finite-numeric-string` | NaN or INF, like `"1e999"` |
| `non-boolean-string`, `non-boolean-int` | `bool()` on `"maybe"` or `2` |
| `list`, `map` | `requireMap()` or `asObject()` on a list, `requireList()` on a map |

Invalid JSON, from `fromJson()` or `json()`, has the expected type `json` and the given type
`string`; an iterator key that is neither an int nor a string has the expected type `array-key`.

The filters are `min`, `max`, `minLength`, `maxLength`, `minCount`, `maxCount`, `oneOf`, `match`,
`notMatch`, `enum`, `date` and `required`.

Mistakes in your own code are not `UntrustedValueException`s and are never caught by the library,
for example an invalid pattern for `match()`.
