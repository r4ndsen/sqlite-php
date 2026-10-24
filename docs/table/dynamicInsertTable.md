# Dynamic insert tables

Use a dynamic insert table when incoming rows do not share the same structure.
The helper expands the schema automatically so every column you `push()` ends up
in the destination table. Column names are normalised case-insensitively—the
first occurrence wins.

```php
$sqlite = new r4ndsen\SQLite();
$dataTable = $sqlite->data->getDynamicInsertTable();

$dataTable
    ->push(['id' => 1, 'title' => 'Moby Dick'])
    ->push(['ID ' => 2, 'link' => 'https://github.com'])
    ->push(['title' => 'IT', 'link' => 'https://www.stephenking.com']);
```

| id | title | link |
| --- | --- | --- |
| 1 | Moby Dick |  |
| 2 |  | https://github.com |
|  | IT | https://www.stephenking.com |

New columns are created as `TEXT` with an empty string as default value, which
is why the missing values above are empty. Columns missing in a row are left out
of the insert, so existing tables keep their own defaults, including expressions
like `CURRENT_TIMESTAMP`.

Dynamic insert tables write inside a transaction, which is committed every
10,000 rows, on `commit()` and when the table object is destroyed. Call
`commit()` before reading the data from another connection. See
[transactions](../transactions.md).
