# Exceptions

All exceptions thrown for database errors extend
`r4ndsen\SQLite\Exception\SQLiteException`, which extends `RuntimeException`.
Its public `errorCode` property holds the extended result code of SQLite as
`r4ndsen\SQLite\ErrorCode` enum, when there is one.

```php
use r4ndsen\SQLite\ErrorCode;
use r4ndsen\SQLite\Exception\SQLiteException;

try {
    $sqlite->exec("insert into tasks (id) values (1)");
} catch (SQLiteException $e) {
    if ($e->errorCode === ErrorCode::SQLITE_CONSTRAINT_PRIMARYKEY) {
        // ...
    }
}
```

The more specific exceptions include:

| Exception | Thrown when |
| --- | --- |
| `QueryException` | a statement fails; `getQuery()` returns the SQL |
| `SyntaxErrorException` | the SQL has a syntax error |
| `UniqueConstraintException` | a unique or primary key constraint fails |
| `TableDoesNotExistException` | the table does not exist; `getTable()` names it |
| `ColumnDoesNotExistException` | the column does not exist; `getColumn()` names it |
| `MissingParameterException` | a placeholder has no bound value |
| `DatabaseException` | the database cannot be opened or is not a database |
| `DatabaseMalformedException` | the database file is corrupt |
| `AttachedDatabaseException` | attaching, detaching or getting an attached database fails |
