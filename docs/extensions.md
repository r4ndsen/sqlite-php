# SQL functions, collations and aggregates

Every `SQLite` object registers a few PHP-backed extensions on its connection.

## Functions

| Function | Result |
| --- | --- |
| `preg_match(pattern, value)` | `1` if the regular expression matches, otherwise `0` |
| `preg_replace(pattern, replacement, value[, limit])` | `value` with the matches replaced |
| `sprintf(format, ...values)` | the formatted string |
| `md5(value)` | the MD5 hash as hex string |
| `empty(value)` | `1` for `''` and `NULL`, otherwise `0` |

An invalid regular expression never matches and leaves the value unchanged.

```php
$sqlite->fetchCol("select title from tasks where preg_match('/^release/i', title)");
```

## Collations

`NATURAL_CMP` sorts like `strnatcmp()` (`a9` before `a10`), `NUMERICAL_CMP`
compares with PHP's `<=>`.

```php
$sqlite->fetchCol('select title from tasks order by title collate NATURAL_CMP');
```

## Aggregates

| Aggregate | Result |
| --- | --- |
| `groupConcat(value[, delimiter])` | the values joined by the delimiter (default `,`) |
| `groupConcatUnique(value[, delimiter])` | like `groupConcat()`, without duplicates |
| `first(value)` | the first value of the group |
| `last(value)` | the last value of the group |

Like SQLite's `group_concat()`, the concat aggregates skip `NULL` and return
`NULL` for an empty group.

## Registering your own

Implement `FunctionInterface`, `CollationInterface` or `AggregateInterface`, or
extend the matching abstract class, and add it to the connection:

```php
use r4ndsen\SQLite\Extensions\Functions;
use r4ndsen\SQLite\Extensions\Functions\AbstractFunction;

final class Slug extends AbstractFunction
{
    public function getCallback(): callable
    {
        return static fn (?string $value) => $value === null ? null : strtolower(trim(preg_replace('/\W+/', '-', $value), '-'));
    }
}

(new Functions($sqlite->getConnection()))->add(new Slug()); // registered as slug()
```

Extensions belong to the connection. `SQLite::refresh()` registers the built-in
ones again; register your own again after a refresh.
