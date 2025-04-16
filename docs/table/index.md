# r4ndsen\SQLite\Table

Tables can be accessed via `->getTable()` or magic `__get()` on the SQLite Object

```php
$sqlite = new r4ndsen\SQLite;

// Access to table "data"
$dataTable = $sqlite->getTable('data');
$dataTable = $sqlite->data;
```

#### Creating a basic table

```php
use r4ndsen\SQLite\Column;

$dataTable->addCreateColumn(Column::createIntegerColumn('id'));
$dataTable->addCreateColumn(Column::createTextColumn('title', null));
$dataTable->create();
```

#### Create a table with constraints

```php
use r4ndsen\SQLite\Column;
use r4ndsen\SQLite\TableConstraint;
use r4ndsen\SQLite\OnConflict;

$titleColumn = Column::createDefaultColumn('title');

$constraint = new TableConstraint;
$constraint->addIndexedColumn($titleColumn);
$constraint->uniqueKey();
$constraint->onConflict = OnConflict::IGNORE;

$dataTable->addConstraint($constraint);
$dataTable->create();
```

```sql
create table `data` (`id` INTEGER default '', `title` TEXT default null, UNIQUE (`title`) ON CONFLICT IGNORE)
```

## Table Convenience

#### Iterate over all rows:

```php
foreach ($dataTable as $row) {
    print_r($row);
}

$data = $dataTable->toArray();
foreach ($data as $row) {
    print_r($row);
}
```

and check its row count

```php
$count = count($dataTable); // select count(*) from data
```

## Using the Table as part of a query

```php
$table = $sqlite->getTable('bulk inserts');
$sqlite->fetchOne("select rowid, * from $table");
```

```sql
select rowid, * from `bulk inserts`
```
