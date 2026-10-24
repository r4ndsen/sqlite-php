[Unreleased]: https://github.com/r4ndsen/sqlite-php/compare/1.0.4...HEAD
[1.0.4]: https://github.com/r4ndsen/sqlite-php/compare/1.0.3...1.0.4
[1.0.3]: https://github.com/r4ndsen/sqlite-php/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/r4ndsen/sqlite-php/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/r4ndsen/sqlite-php/compare/1.0.0...1.0.1
[1.0.0]: https://github.com/r4ndsen/sqlite-php/commits/1.0.0

# Changelog

All notable changes to this project will be listed in this file.

## [Unreleased]

## [1.0.4] - 2026-10-24

### Changed
- The test suite merges tests that only differed in their input into data
  providers and drops duplicates and tests that only exercised `SQLite3`
  itself, going from 322 to 275 test methods. Tests that passed silently when
  no exception was thrown now fail. Coverage and mutation score stay at 100%.

## [1.0.3] - 2026-10-24

### Changed
- The development tools require their current releases: PHP-CS-Fixer 3.95,
  PHPBench 1.7, PHPStan 2.3 and Rector 2.7. Infection 0.35 is used on PHP 8.3
  and later, PHP 8.2 keeps Infection 0.31. All mutants of Infection 0.35 are
  killed.

## [1.0.2] - 2026-10-24

### Changed
- `attach()` leaves the check for an identifier already in use to SQLite. The
  message stays the same, the `AttachedDatabaseException` now carries SQLite's
  error as previous exception.
- Lowering the commit modulo of a prepared statement below the number of
  executions since the last commit commits on the next execution, instead of
  waiting for the next multiple of the modulo.
- The Infection configuration is `infection.json5.dist`, so the ignored
  equivalent mutants can name their reason. All mutants are killed.

### Fixed
- Tests no longer share fixed database file names, which made parallel
  mutation testing runs kill or spare mutants at random.

## [1.0.1] - 2026-10-24

### Changed
- Bound values keep their type: integers and booleans are bound as `INTEGER`,
  floats as `REAL`, `null` as `NULL`. Before, everything was bound as `TEXT`,
  so `false` did not match `0` and untyped columns stored numbers as text.
- The query parser follows SQLite's quoting rules. A backslash no longer
  escapes quotes, comments and `[identifiers]` are skipped, and `?NNN`
  placeholders work. Placeholders that cannot be bound throw a
  `MissingParameterException` instead of being bound as `NULL`.
- `Table::push()` inserts keyed rows by column name. Rows without keys are
  bound in the column order of the previous row.
- Dynamic and fixed insert tables leave missing columns out of the insert, so
  SQLite applies the column defaults. Adding a column no longer commits the
  running transaction.
- Unnamed single-column indexes are named `<table>_<column>` instead of
  `<column>`, because index names are unique per database.
  `Index::getCreateStatement()` takes the table as argument. Indexes created
  with the old name stay in existing databases.
- `fetchInstance()` and `fetchInstances()` fill classes without a constructor
  (like `stdClass`) by property. Hydrating abstract classes, interfaces, enums
  or (for `fetchInstance()`) classes with a private constructor throws an
  `InvalidArgumentException` instead of returning `null`.
- `ColumnType::fromString()` follows SQLite's affinity rules: an empty type is
  `BLOB`, unknown types are `NUMERIC`.
- `Column::disallowNull()` works without a default value.
  `Column::createNumericColumn()` defaults to `NULL`.
- `groupConcat()` and `groupConcatUnique()` skip `NULL` and return `NULL` for
  empty groups, like `group_concat()`. `empty()` returns `1` or `0`.
- `getAttached()` opens the separate connection on first use and throws for
  in-memory databases.
- `ColumnSchema::$isPrivateKey` is renamed to `$isPrimaryKey`.

### Added
- `Transaction::rollback()`.
- `SQLite::getPragma()` is public, `isset($sqlite->table)` checks whether the
  table exists.
- `setPageSize()` accepts 512.
- Documentation for transactions, attached databases, the SQL extensions and
  exceptions.

### Fixed
- `count()` and `hasData()` for tables whose rows have rowid 0 and for
  `WITHOUT ROWID` tables; `exists()` is case-insensitive.
- Index conditions containing `%` no longer fail.
- `refresh()` keeps the custom functions, collations and aggregates and the
  tables use the reopened connection.
- `DOUBLE` columns are recognised as `REAL`.
- New columns with surrounding whitespace in their name can be inserted into.
- Null bytes are kept when binding blobs.
- Unique constraint violations raised by `exec()` throw a
  `UniqueConstraintException`.
- `getAllTables()` no longer returns SQLite's internal tables.

### Removed
- The unused `symfony/polyfill-php83` and `symfony/polyfill-php85`
  dependencies and the Psalm configuration.

## [1.0.0] - 2025-09-22

The first release
