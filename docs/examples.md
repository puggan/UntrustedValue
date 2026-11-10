# Examples

Recipes for common kinds of untrusted input. The outputs shown are what the code returns. For what
each method does, see [methods](methods.md).

- [A DTO from a JSON body](#a-dto-from-a-json-body)
- [Query strings and forms](#query-strings-and-forms)
- [A strict JSON API](#a-strict-json-api)
- [Nested lists](#nested-lists)
- [Filtering and mapping lists](#filtering-and-mapping-lists)
- [JSON inside JSON](#json-inside-json)
- [CSV fields](#csv-fields)
- [Small checks](#small-checks)
- [Errors as a 422 response](#errors-as-a-422-response)
- [Collecting every error, not just the first](#collecting-every-error-not-just-the-first)

All examples assume:

```php
use Puggan\UntrustedValue\UntrustedStrictValue;
use Puggan\UntrustedValue\UntrustedValue;
use Puggan\UntrustedValue\UntrustedValueException;
```

## A DTO from a JSON body

Give the DTO a static `hydrate()` that reads each field in one chain. See the [README](../README.md#example)
for a complete one, and its error output.

## Query strings and forms

Query strings and form posts only send strings, so use the loose `UntrustedValue`, which converts
`"2"` to `2` and `"on"` to `true`. Fields are usually optional, so read them with
`property($name, false)`, which gives null for a missing one, and fill in a default.

```php
enum Sort: string { case Name = 'name'; case Date = 'date'; }

$query = new UntrustedValue($_GET);   // ['page' => '2', 'ids' => '3,5,8', 'sort' => 'date', 'active' => 'on', 'since' => '2026-11-01']

$page   = $query->property('page', false)->default(1)->asInt(false)->range(min: 1)->int();     // 2
$limit  = $query->property('limit', false)->default(20)->asInt(false)->range(1, 100)->int();   // 20, missing
$ids    = $query->property('ids', false)->split(',')->listInt(false);                          // [3, 5, 8]
$sort   = $query->property('sort', false)->default('name')->enum(Sort::class, false);          // Sort::Date
$active = $query->property('active', false)->default(false)->bool(false);                      // true
$since  = $query->property('since', false)->date('!Y-m-d');                                    // 2026-11-01 00:00, or null
```

`?page=0` fails with:

```json
{"message": "Expected at least 1 at page", "filter": "min", "path": "page"}
```

## A strict JSON API

A JSON client can send real numbers and bools, so `UntrustedStrictValue` can insist on the exact
type: `"7"` is not an id. `float()` still accepts an int, as JSON has no separate float literal for
`19`.

```php
$body = UntrustedStrictValue::fromJson('{"id": 7, "price": 19, "tags": ["a", "b"], "note": null}');

$body->property('id')->int(false);            // 7
$body->property('price')->float(false);       // 19.0
$body->property('tags')->listString(false);   // ['a', 'b']
$body->property('note')->string();            // null
```

`{"id": "7"}` fails with:

```json
{"message": "Expected int at id, got string", "expectedType": "int", "givenType": "string", "path": "id"}
```

## Nested lists

Read a list of objects with `listRawMap()`, which hands you each item with its own path and keeps
what you return:

```php
$order = UntrustedValue::fromJson('{"lines": [{"sku": "A1", "qty": "2"}, {"sku": "B2", "qty": "x"}]}');

$lines = $order->property('lines')->listRawMap(fn(UntrustedValue $line) => [
    'sku' => $line->property('sku')->string(false),
    'qty' => $line->property('qty')->asInt(false)->range(min: 1)->int(),
]);
```

The second line's quantity fails, and the path says exactly where:

```json
{"message": "Expected int at lines.1.qty, got non-numeric-string", "expectedType": "int", "givenType": "non-numeric-string", "path": "lines.1.qty"}
```

With only the first line, `$lines` is `[['sku' => 'A1', 'qty' => 2]]`.

## Filtering and mapping lists

`filterValid()`, `filter()` and `map()` chain. Each item keeps the path it had in the input, so an
error after a `map()` still names the original field.

```php
$users = UntrustedValue::fromJson('[
    {"name": "Ada", "age": "36"}, {"name": "Bo", "age": "12"},
    {"name": "Cy", "age": "old"}, {"name": "Di", "age": "70"}
]');

$adultNames = $users
    ->filterValid(fn(UntrustedValue $user) => $user->property('age')->asInt(false))   // drops Cy
    ->filter(fn(UntrustedValue $user) => $user->property('age')->int(false) >= 18)    // drops Bo
    ->map(fn(UntrustedValue $user) => $user->property('name'))
    ->listString(false);                                                              // ['Ada', 'Di']
```

Without the `filterValid()`, Cy's age fails at its own path:

```php
$users->map(fn(UntrustedValue $user) => $user->property('age'))->listInt(false);
```

```json
{"message": "Expected int at 2.age, got non-numeric-string", "expectedType": "int", "givenType": "non-numeric-string", "path": "2.age"}
```

A `filter()` or `map()` result is a generator, so it can be iterated only once: build the chain and
end it with one `list…()`, `array…()` or `foreach`.

To flatten, `yieldFlatMap()` yields every item of each result:

```php
$tags = UntrustedValue::fromJson('[{"tags": ["a", "b"]}, {"tags": ["c"]}]')
    ->yieldFlatMap(fn(UntrustedValue $user) => $user->property('tags')->listString(false));

iterator_to_array($tags, false);   // ['a', 'b', 'c']
```

## JSON inside JSON

Some APIs put a JSON document in a string field. `json()` decodes it, and the paths continue into
it:

```php
$event = UntrustedValue::fromJson('{"type": "order", "payload": "{\"id\": \"42\"}"}');

$event->property('payload')->json()->property('id')->int(false);   // 42
```

Broken inner JSON fails at the field:

```json
{"message": "Invalid JSON at payload: Syntax error", "expectedType": "json", "givenType": "string", "path": "payload"}
```

## CSV fields

`split()` is enough for simple lists like `?ids=3,5,8`. For fields that can contain the separator,
`csv()` handles quotes:

```php
new UntrustedValue('42,"Smith, Ann","say ""hi"""')->csv()->listString(false);
// ['42', 'Smith, Ann', 'say "hi"']
```

## Small checks

```php
new UntrustedValue('desc')->oneOf(['asc', 'desc'])->string(false);   // 'desc'
new UntrustedValue('   ')->trim()->blankDefault()->string();         // null
```

Each of these fails, with the error shown below it:

```php
new UntrustedValue('ab-12', 'code')->match('/^[A-Z]{2}-\d+$/');
new UntrustedValue('  ', 'name')->trim()->length(50, 1);
new UntrustedValue([], 'tags')->count(10, 1);
new UntrustedValue('2026-02-30', 'birthday')->date('!Y-m-d');
```

```json
{"message": "Expected a match for /^[A-Z]{2}-\\d+$/ at code", "filter": "match", "path": "code"}
{"message": "Expected at least 1 character at name", "filter": "minLength", "path": "name"}
{"message": "Expected at least 1 item at tags", "filter": "minCount", "path": "tags"}
{"message": "Expected a date in the format !Y-m-d at birthday", "filter": "date", "path": "birthday"}
```

The last one is a real date check: PHP's own `createFromFormat()` would quietly turn it into
2026-03-02.

## Errors as a 422 response

Every exception about bad input is an `UntrustedValueException`, which serializes to JSON, so one
`catch` turns any of them into a response:

```php
try {
    $user = UserDto::hydrate(UntrustedValue::fromJson($requestBody));
} catch (UntrustedValueException $exception) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode(['error' => $exception]);
}
```

Mistakes in your own code, like an invalid pattern given to `match()`, are other exceptions, so they
don't end up as a 422.

## Collecting every error, not just the first

A chain stops at the first problem. To report every invalid field at once, check each field
separately and collect the exceptions:

```php
/**
 * @param UntrustedValue<mixed> $data
 * @param array<string, callable(UntrustedValue<mixed>): mixed> $checks
 *
 * @return list<UntrustedValueException>
 */
function errorsOf(UntrustedValue $data, array $checks): array
{
    $errors = [];
    foreach ($checks as $name => $check) {
        try {
            $check($data->property($name));
        } catch (UntrustedValueException $exception) {
            $errors[] = $exception;
        }
    }

    return $errors;
}

$errors = errorsOf(UntrustedValue::fromJson('{"age": "x", "email": "nope"}'), [
    'age' => fn(UntrustedValue $age) => $age->asInt(false),
    'email' => fn(UntrustedValue $email) => $email->match('/@/'),
    'name' => fn(UntrustedValue $name) => $name->string(false),
]);
```

The three messages are:

```text
Expected int at age, got non-numeric-string
Expected a match for /@/ at email
Missing required property name
```
