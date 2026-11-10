# UntrustedValue

Turn untrusted input values (request data, decoded JSON, environment variables, …) into
type-safe values, with optional validation. Every check knows where in the input the value came
from, so errors point at the exact field, like `tags.2`.

## Installation

```bash
composer require puggan/untrusted-value
```

Requires PHP 8.1 or later, with ext-mbstring.

PHP 8.1 is the floor because the wrapped value and its path are `readonly` promoted constructor
properties, which PHP 8.1 introduced. The test suite passes on PHP 8.1 through 8.5, and on the
8.6 release candidate.

## Example

A DTO that hydrates itself from untrusted data:

```php
use Puggan\UntrustedValue\UntrustedValue;

final readonly class UserDto
{
    /**
     * @param list<string> $tags
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?int $age,
        public array $tags,
    ) {}

    /**
     * @param UntrustedValue<mixed> $data
     */
    public static function hydrate(UntrustedValue $data): self
    {
        return new self(
            id: $data->property('id')->asInt(false)->range(min: 1)->int(),
            name: $data->property('name')->trim()->length(100, 1)->string(false),
            email: $data->property('email')->trim()->match('/^[^@\s]+@[^@\s]+$/')->string(false),
            age: $data->property('age', false)->asInt()->range(0, 150)->int(),
            tags: $data->property('tags', false)->default([])->listString(false),
        );
    }
}
```

Each chain reads a property, checks or converts it, and ends in a getter that returns a plain PHP
value. PHPStan knows the result types: `->asInt(false)->range(min: 1)->int()` is an `int`, not an
`?int`.

Use it, and turn any problem into an error response:

```php
use Puggan\UntrustedValue\UntrustedValueException;

try {
    $user = UserDto::hydrate(UntrustedValue::fromJson($requestBody));
} catch (UntrustedValueException $exception) {
    http_response_code(422);
    echo json_encode(['error' => $exception]);
}
```

Every exception the library throws for a bad value is an `UntrustedValueException`, and serializes
to JSON:

| Input | Error |
|---|---|
| `{"age": "old", …}` | `{"message": "Expected int at age, got non-numeric-string", "expectedType": "int", "givenType": "non-numeric-string", "path": "age"}` |
| `{"age": 200, …}` | `{"message": "Expected at most 150 at age", "filter": "max", "path": "age"}` |
| `{…}` without `name` | `{"message": "Missing required property name", "filter": "required", "path": "name"}` |
| `{"tags": ["a", 3, null], …}` | `{"message": "Expected string at tags.2, got null", "expectedType": "string", "givenType": "null", "path": "tags.2"}` |

`WrongTypeException` means the value has the wrong type; `InvalidTypeException` means it has the
right type but failed a check.

## Loose and strict

`UntrustedValue` converts compatible values: the string `"42"` is accepted by `int()`, and `3` by
`string()`, as `"3"`. `UntrustedStrictValue` only accepts the exact type (except that `float()` also
widens an int). Both share everything else through `AbstractUntrustedValue`, and switch with
`asStrict()` and `asLoose()`.

Values read from a strict value, with `property()`, `list()` and the like, are strict too.

## Methods

Most methods take `bool $nullable = true`: null is accepted, and passes through, unless it is
`false`.

| Kind | Methods |
|---|---|
| Get a plain value | `int()`, `float()`, `string()`, `enum()`, `raw()` |
| Check | `isInt()`, `isFloat()`, `isString()`, `isNull()`, `isScalar()`, `isIterable()`, `isArray()`, `isObject()`, `isList()`, `isMap()`, `hasProperty()`, `hasProperties()` |
| Require, else throw | `requireNull()`, `requireScalar()`, `requireArray()`, `requireObject()`, `requireList()`, `requireMap()`, `requireProperty()` |
| Convert | `asInt()`, `asFloat()`, `asString()`, `asList()`, `asArray()`, `asObject()`, `asStrict()`, `asLoose()` |
| Validate | `range()`, `length()`, `match()`, `notMatch()` |
| Change | `trim()`, `replace()`, `default()`, `blankDefault()` |
| Read nested values | `property()`, `list()`, `array()`, `yieldList()`, `yieldArray()`, or `foreach` directly |
| Map nested values | `listMap()`, `arrayMap()`, `yieldMap()`, and `listRawMap()`, `arrayRawMap()`, `yieldRawMap()` without wrapping |
| Typed collections | `listInt()`, `arrayInt()`, `yieldInt()`, and the same for `Float`, `String` and `Scalar` |

The require, convert, validate and change methods return the same object when there is nothing to
change, and keep the path otherwise. The wrapped type is tracked as a PHPStan template,
`UntrustedValue<TValue>`, and narrowed along the chain where possible.

## Development

The tests and development tools need PHP 8.4 or later; the tests use its `new Foo()->bar()` syntax.

```bash
composer test     # PHPUnit
composer phpstan  # PHPStan, max level
composer cs       # PHP-CS-Fixer check (PER-CS)
composer cs-fix   # PHP-CS-Fixer fix
composer check    # all of the above
```

## License

MIT, see [LICENSE](LICENSE).
