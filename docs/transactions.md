# Transactions

Each connection has one `Transaction` object. `begin()`, `commit()` and
`rollback()` only act when needed, so calling them twice is safe.

```php
$tx = $sqlite->getConnection()->getTransaction();

$tx->begin();

try {
    $sqlite->exec("update tasks set is_done = 1 where id = 1");
    $sqlite->exec("delete from tasks where is_done = 1 and id <> 1");
    $tx->commit();
} catch (Throwable $e) {
    $tx->rollback();

    throw $e;
}
```

## Bulk inserts

Tables can wrap their inserts in that transaction, which makes bulk inserts a
lot faster. Dynamic and fixed insert tables do this by default.

```php
$table = $sqlite->getTable('tasks')->withTransaction(true);

foreach ($rows as $row) {
    $table->push($row);
}

$table->commit();
```

The transaction is committed every 10,000 inserts per statement, on
`commit()`, and when the table object is destroyed. Rows inserted before an
error are therefore kept. Use `PreparedStatement::setCommitModulo()` when you
prepare statements yourself and need a different batch size:

```php
$statement = $sqlite->prepare('insert into tasks (title) values (?)')
    ->activateTransaction()
    ->setCommitModulo(1_000);

foreach ($titles as $title) {
    $statement->bind([$title]);
}

$statement->commit();
```

`SQLite::vacuum()` and `SQLite::refresh()` commit an open transaction first.
