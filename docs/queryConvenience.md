# Query Convenience

```php
$sql  = 'SELECT * FROM `table` WHERE foo = :foo AND bar = :bar';
$bind = ['foo' => 'baz', 'bar' => 'dib'];
```

`fetchValue()` returns the value of the first row in the first column

```php
$result = $sqlite->fetchValue($sql, $bind);
```

`fetchAll()` returns an associative array of all rows where the key is the
first column, and the row arrays are keyed on the column names
```php
$result = $sqlite->fetchAll($sql, $bind);
```

`fetchObject()` returns the first row as an object of your choosing; the
columns are mapped to object properties. an optional 4th parameter array
provides constructor arguments when instantiating the object.
```php
$result = $sqlite->fetchObject($sql, $bind, 'ClassName', ['ctor_arg_1']);
```

`fetchObjects()` returns an array of objects of your choosing; the
columns are mapped to object properties. an optional 4th parameter array
provides constructor arguments when instantiating the object.
```php
$result = $sqlite->fetchObjects($sql, $bind, 'ClassName', ['ctor_arg_1']);
```

`fetchOne()` returns the first row as an associative array where the keys
are the column names
```php
$result = $sqlite->fetchOne($sql, $bind);
```

`fetchPairs()` returns an associative array where each key is the first
column and each value is the second column
```php
$result = $sqlite->fetchPairs($sql, $bind);
```

`fetchPair()` returns the first entry of fetchPairs()
```php
$result = $sqlite->fetchPair($sql, $bind);
```

`fetchCol()` returns the values in the first selected column as an array
```php
$result = $sqlite->fetchCol($sql, $bind);
```

like `fetchAll()`, each row is an associative array
```php
foreach ($sqlite->yieldAll($sql, $bind) as $row) {
    // ...
}
```

like `fetchCol()`, each result is a value from the first column
```php
foreach ($sqlite->yieldCol($sql, $bind) as $val) {
    // ...
}
```

like `fetchObjects()`, each result is an object; pass an optional
class name and optional array of constructor arguments.
```php
$class = MyClassName::class;
$args = ['arg0', 'arg1', 'arg2'];
foreach ($sqlite->yieldObjects($sql, $bind, $class, $args) as $object) {
    ...
}
```
like `fetchPairs()`, each result is a key-value pair from the
first and second columns
```php
foreach ($sqlite->yieldPairs($sql, $bind) as $key => $val) {
    ...
}
```
