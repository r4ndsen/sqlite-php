# Fixed Insert Table

A Fixed Insert Table is similar to a Dynamic Insert Table but does not allow creating new columns after the table has been created

### Creating a Fixed Insert Table
```php
$tableName = 'data';
$table = $this->SQLite->getTable($tableName)->getFixedInsertTable();
```

### Usage

```php
$table->push(['Ä ' => 'foo']);
$table->push(['rowid' => 2, ' ä' => 'bar']);
$table->push(['ä' => 'baz']); // auto increment to 3
$table->push(['rowid' => 99, ' ä' => 'voo']);
$table->push(['Ä' => 'doo']); // auto increment to 100

$table->exists(); // true
count($table); // 5

$table->fetchPairs("select rowid, * from $table");
/*
    array(
        '1'   => 'foo',
        '2'   => 'bar',
        '3'   => 'baz',
        '99'  => 'voo',
        '100' => 'doo',
    )
*/
```
