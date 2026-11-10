# UntrustedValue

Turn untrusted input values (request data, decoded JSON, environment variables, …) into
type-safe values, with optional validation. Every check knows where in the input the value came
from, so errors point at the exact field, like `tags.2`.

## Installation

```bash
composer require puggan/untrusted-value
```

Requires PHP 8.1 or later, with ext-mbstring.

PHP 8.1 is the floor because the wrapped value and its path are `readonly` properties, which PHP
8.1 introduced. The test suite passes on PHP 8.1 through 8.5, and on the 8.6 release candidate.

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

## More examples

Query strings and forms only send strings, so the loose `UntrustedValue` converts them:

```php
$query = new UntrustedValue($_GET);   // ?page=2&ids=3,5,8&active=on

$page = $query->property('page', false)->default(1)->asInt(false)->range(min: 1)->int();   // 2
$ids = $query->property('ids', false)->split(',')->listInt(false);                         // [3, 5, 8]
$active = $query->property('active', false)->default(false)->bool(false);                  // true
```

Lists can be filtered and mapped in a chain, and every item keeps its path, so errors still point
into the input:

```php
$adultNames = $users
    ->filterValid(fn(UntrustedValue $user) => $user->property('age')->asInt(false))
    ->filter(fn(UntrustedValue $user) => $user->property('age')->int(false) >= 18)
    ->map(fn(UntrustedValue $user) => $user->property('name'))
    ->listString(false);
```

[More examples](https://github.com/puggan/UntrustedValue/blob/main/docs/examples.md): strict JSON APIs, nested lists, JSON inside JSON, CSV fields,
422 responses and collecting every error.

## Loose and strict

`UntrustedValue` converts compatible values: `int()` accepts `"42"`, `bool()` accepts `"on"`.
`UntrustedStrictValue` only accepts the exact type, except that `float()` also widens an int, which
suits JSON APIs. Both share everything else through `AbstractUntrustedValue`, values read from a
strict value are strict too, and `asStrict()` and `asLoose()` switch between them.

## Methods

| Group | Methods |
|---|---|
| [Get a plain value](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md#get-a-plain-value) | `int()`, `float()`, `string()`, `bool()`, `enum()`, `date()`, `raw()` |
| [Check](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md#check) | `isInt()`, `isList()`, `isMap()`, `hasProperty()` and the other `is*()` |
| [Require](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md#require) | `requireList()`, `requireMap()`, `requireProperty()` and the other `require*()` |
| [Convert](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md#convert) | `asInt()`, `asFloat()`, `asString()`, `asBool()`, `asList()`, `asArray()`, `asObject()`, `json()`, `asStrict()`, `asLoose()` |
| [Validate](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md#validate) | `range()`, `length()`, `count()`, `oneOf()`, `match()`, `notMatch()` |
| [Change](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md#change) | `trim()`, `replace()`, `default()`, `blankDefault()`, `split()`, `csv()` |
| [Read nested values](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md#read-nested-values) | `property()`, `list()`, `array()`, `yieldList()`, `yieldArray()`, `foreach` |
| [Filter and map](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md#filter-and-map) | `filter()`, `filterValid()`, `map()`, and `listMap()`, `listRawMap()`, `yieldFlatMap()` and the like |
| [Typed collections](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md#typed-collections) | `listInt()`, `arrayString()`, `yieldBool()` and the like |

Most methods take `bool $nullable = true`, so null passes through unless you say otherwise. The
wrapped type is tracked as a PHPStan template, `UntrustedValue<TValue>`, and narrowed along the
chain. The [method reference](https://github.com/puggan/UntrustedValue/blob/main/docs/methods.md) has every method, the conventions they share, and all
error types and filters.

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
