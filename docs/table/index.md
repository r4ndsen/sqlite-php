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
use r4ndsen\SQLite\OnConflict;
use r4ndsen\SQLite\TableConstraint;

$titleColumn = Column::createTextColumn('title', null);

$constraint = (new TableConstraint())
    ->addIndexedColumn($titleColumn)
    ->uniqueKey();
$constraint->onConflict = OnConflict::IGNORE;

$dataTable
    ->addCreateColumn(Column::createIntegerColumn('id'))
    ->addCreateColumn($titleColumn)
    ->addConstraint($constraint)
    ->create();
```

```sql
create table `data` (
    `id` INTEGER default null,
    `title` TEXT default null,
    unique (`title`) on conflict ignore
)
```

`Column::disallowNull()` adds `NOT NULL`. Without a default value, inserts then
have to provide a value for that column.

## Indexes

```php
use r4ndsen\SQLite\Column;
use r4ndsen\SQLite\Index;

$dataTable->addIndex(new Index(Column::createTextColumn('title')));
// create index if not exists `data_title` on `data` (`title`)

$dataTable->addIndex(
    (new Index(Column::createIntegerColumn('id'), Column::createTextColumn('title')))
        ->setName('data_id_title')
        ->setUnique()
);
```

An index on a single column is named `<table>_<column>` unless you set a name.
Indexes on several columns need a name.

## Inserting rows

`push()` inserts a row whose keys are the column names. Columns you leave out
get their default value from SQLite. A row without keys is bound in the column
order of the row pushed before it.

```php
$dataTable
    ->push(['id' => 1, 'title' => 'Moby Dick'])
    ->push([2, 'It'])
    ->push(['title' => 'Dune'])
    ->commit();
```

`pushWithoutKeys()` binds the values in the order of the table columns. Use
`withTransaction(true)` to wrap the inserts in a transaction; see
[transactions](../transactions.md).

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
$count = count($dataTable); // select count(*) from data
```

## Using tables inline

Tables implement `Stringable`, so you can embed them directly in SQL strings and
benefit from automatic identifier quoting.

```php
$table = $sqlite->getTable('bulk inserts');
$result = $sqlite->fetchOne("select rowid, * from $table");
```

```sql
select rowid, * from `bulk inserts`
```
