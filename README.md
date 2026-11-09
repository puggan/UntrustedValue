# UntrustedValue

Turn untrusted input values (request data, decoded JSON, environment variables, …) into
type-safe values, with optional validation.

> Work in progress, the API is not designed yet.

## Installation

```bash
composer require puggan/untrusted-value
```

Requires PHP 8.5 or later.

## Development

```bash
composer test     # PHPUnit
composer phpstan  # PHPStan, max level
composer cs       # PHP-CS-Fixer check (PER-CS)
composer cs-fix   # PHP-CS-Fixer fix
composer check    # all of the above
```

## License

MIT, see [LICENSE](LICENSE).
