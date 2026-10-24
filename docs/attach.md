# Attached databases

Attach another database file under a schema name and query both in one
statement:

```php
$sqlite = new r4ndsen\SQLite('main.sqlite');
$sqlite->attach('archive.sqlite', 'archive');

$sqlite->fetchAll('select * from tasks union all select * from archive.tasks');

$sqlite->detach('archive');
```

As with SQLite's `attach`, the file is created if it does not exist yet.

`getAttached()` returns a separate `SQLite` object for the attached file, for
example to use the table helpers on it. It opens a second connection, so writes
through both connections at the same time can block each other. In-memory
databases cannot be opened a second time; for those `getAttached()` throws an
`AttachedDatabaseException`.

```php
$archive = $sqlite->getAttached('archive');
$archive->getTable('tasks')->count();
```

Attachments belong to the connection: after `SQLite::refresh()` they have to be
attached again.
