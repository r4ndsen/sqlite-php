# r4ndsen / sqlite-php
High-level helpers for SQLite3 with batteries-included query utilities, schema
introspection, and pragma management.

[![Latest Stable Version](https://img.shields.io/packagist/v/r4ndsen/sqlite-php.svg)](https://packagist.org/packages/r4ndsen/sqlite-php)
[![Build Status](https://github.com/r4ndsen/sqlite-php/actions/workflows/php.yml/badge.svg?branch=main)](https://github.com/r4ndsen/sqlite-php/actions/workflows/php.yml)
[![Coverage Status](https://coveralls.io/repos/github/r4ndsen/sqlite-php/badge.svg?branch=main)](https://coveralls.io/github/r4ndsen/sqlite-php?branch=main)

## Requirements
- PHP 8.2 or newer
- `ext-sqlite3` and `ext-mbstring`

## Installation

### Composer
```bash
composer require r4ndsen/sqlite-php
```

### From source
```bash
git clone https://github.com/r4ndsen/sqlite-php.git
cd sqlite-php
composer install
```

## Quick start
```php
<?php

use r4ndsen\SQLite;
use r4ndsen\SQLite\Column;

$sqlite = new SQLite(':memory:');

$tasks = $sqlite->getTable('tasks');
$tasks
    ->addCreateColumn(Column::createIntegerColumn('id'))
    ->addCreateColumn(Column::createTextColumn('title')->disallowNull())
    ->addCreateColumn(Column::createIntegerColumn('is_done', 0))
    ->createIfNotExists();

$tasks->getDynamicInsertTable()
    ->push(['id' => 1, 'title' => 'Write docs'])
    ->push(['id' => 2, 'title' => 'Tag release', 'is_done' => 1])
    ->commit();

$openTasks = $sqlite->fetchPairs(
    'select id, title from tasks where is_done = :is_done order by id',
    ['is_done' => 0],
);
// [1 => 'Write docs']

foreach ($tasks as $row) {
    // Table implements IteratorAggregate, so you can iterate rows directly
}

$sqlite->validate(); // Runs PRAGMA integrity_check and throws on corruption
```

Head over to the [documentation](docs/index.md) for more detail on tables,
column helpers, pragmas, and query convenience methods.

## Development tooling

All make targets run from the project root:

```bash
make test        # PHPUnit against the current PHP version
make tests       # PHPUnit against the Docker matrix (PHP 8.2–8.5)
make coverage    # Generates HTML coverage and prints the report path
make infection   # Mutation testing (requires Xdebug)
make stan        # PHPStan static analysis
```

## License
r4ndsen/sqlite-php is licensed under the MIT License. See
[LICENSE](LICENSE) for details.

## Changelog
See [CHANGELOG](CHANGELOG.md) for release notes.
