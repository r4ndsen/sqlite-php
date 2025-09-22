# r4ndsen\\SQLite\\Table

Retrieve table helpers either with `SQLite::getTable()` or via the magic
property accessor.

```php
$sqlite = new r4ndsen\SQLite();

// Both lines return the same Table instance
$dataTable = $sqlite->getTable('data');
$dataTable = $sqlite->data;
```

## Creating tables

```php
use r4ndsen\SQLite\Column;

$dataTable->addCreateColumn(Column::createIntegerColumn('id'));
$dataTable->addCreateColumn(Column::createTextColumn('title', null));
$dataTable->create();
```

### Adding constraints

```php
use r4ndsen\SQLite\Column;
use r4ndsen\SQLite\TableConstraint;
use r4ndsen\SQLite\OnConflict;

$titleColumn = Column::createDefaultColumn('title');

$constraint = (new TableConstraint())
    ->addIndexedColumn($titleColumn)
    ->uniqueKey();
$constraint->onConflict = OnConflict::IGNORE;

$dataTable->addConstraint($constraint);
$dataTable->create();
```

```sql
create table `data` (
    `id` INTEGER default '',
    `title` TEXT default null,
    UNIQUE (`title`) ON CONFLICT IGNORE
)
```

## Table conveniences

Iterate over rows or materialise them as needed:

```php
foreach ($dataTable as $row) {
    print_r($row);
}

$data = $dataTable->toArray();
```

Count rows without writing SQL:

```php
$count = count($dataTable); // SELECT count(*) FROM data
```

## Using tables inline

Tables implement `Stringable`, so you can embed them directly in SQL strings and
benefit from automatic identifier quoting.

```php
$table = $sqlite->getTable('bulk inserts');
$result = $sqlite->fetchOne("SELECT rowid, * FROM $table");
```

```sql
select rowid, * from `bulk inserts`
```
