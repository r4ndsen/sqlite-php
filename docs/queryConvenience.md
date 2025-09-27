# Query Convenience

Most read operations accept the same two arguments: the SQL string and an
optional array of bound values. Named placeholders can be passed with or without
leading colons; both styles work.

```php
$sql  = 'select * from `table` where foo = :foo and bar = :bar';
$bind = ['foo' => 'baz', 'bar' => 'dib'];
```

## Eager helpers

`fetchValue()` returns the value of the first row in the first column.
```php
$result = $sqlite->fetchValue($sql, $bind);
```

`fetchAll()` returns an array of associative rows keyed by column name.
```php
$result = $sqlite->fetchAll($sql, $bind);
```

`fetchInstance()` maps the first row to a class by calling its constructor with
column values as positional arguments.
```php
$dto = $sqlite->fetchInstance($sql, $bind, TaskDto::class);
```

`fetchInstances()` does the same but returns an array of constructed objects.
```php
$dtos = $sqlite->fetchInstances($sql, $bind, TaskDto::class);
```

`fetchObject()` hydrates the first row into an object, assigning properties by
name. Supply constructor arguments via the fourth parameter when needed.
```php
$result = $sqlite->fetchObject($sql, $bind, ClassName::class, ['ctor_arg']);
```

`fetchObjects()` mirrors `fetchObject()` but returns an array of hydrated
objects.
```php
$result = $sqlite->fetchObjects($sql, $bind, ClassName::class, ['ctor_arg']);
```

`fetchOne()` returns the first row as an associative array keyed by column
names.
```php
$result = $sqlite->fetchOne($sql, $bind);
```

`fetchPairs()` returns an associative array where the first selected column is
used as the key and the second column as the value.
```php
$result = $sqlite->fetchPairs($sql, $bind);
```

`fetchPair()` is a shorthand that returns the first key/value pair.
```php
$result = $sqlite->fetchPair($sql, $bind);
```

`fetchCol()` returns a flat array containing the first selected column.
```php
$result = $sqlite->fetchCol($sql, $bind);
```

`fetchPlain()` returns numeric arrays for each row instead of associative rows.
```php
$result = $sqlite->fetchPlain($sql, $bind);
```

## Lazy generators

`yieldAll()` behaves like `fetchAll()` but yields rows lazily.
```php
foreach ($sqlite->yieldAll($sql, $bind) as $row) {
    // ...
}
```

`yieldCol()` yields the first column lazily.
```php
foreach ($sqlite->yieldCol($sql, $bind) as $value) {
    // ...
}
```

`yieldObjects()` lazily hydrates objects in the same fashion as `fetchObjects()`.
```php
foreach ($sqlite->yieldObjects($sql, $bind, ClassName::class, ['ctor_arg']) as $object) {
    // ...
}
```

`yieldInstances()` constructs objects via their native constructor and yields
them one by one.
```php
foreach ($sqlite->yieldInstances($sql, $bind, TaskDto::class) as $dto) {
    // ...
}
```

`yieldPairs()` yields key/value pairs from the first two columns.
```php
foreach ($sqlite->yieldPairs($sql, $bind) as $key => $value) {
    // ...
}
```

`yieldPlain()` yields numeric arrays, mirroring `fetchPlain()`.
```php
foreach ($sqlite->yieldPlain($sql, $bind) as [$id, $name]) {
    // ...
}
```
