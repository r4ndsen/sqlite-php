# Fixed insert tables

A fixed insert table keeps the schema stable—new columns are not created after
initialisation. Use it when you expect consistent keys and want predictable
insertion order.

## Creating a fixed insert table
```php
$tableName = 'data';
$table = $this->SQLite->getTable($tableName)->getFixedInsertTable();
```

## Usage
```php
$table->push(['Ä ' => 'foo']);
$table->push(['rowid' => 2, ' ä' => 'bar']);
$table->push(['ä' => 'baz']);      // auto-increment to 3
$table->push(['rowid' => 99, ' ä' => 'voo']);
$table->push(['Ä' => 'doo']);      // auto-increment to 100

$table->exists(); // true
count($table);    // 5

$table->fetchPairs("select rowid, * from $table");
/*
    [
        '1'   => 'foo',
        '2'   => 'bar',
        '3'   => 'baz',
        '99'  => 'voo',
        '100' => 'doo',
    ]
*/
```
