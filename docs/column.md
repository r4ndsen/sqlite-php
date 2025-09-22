# r4ndsen\\SQLite\\Column

The `Column` utility normalizes identifiers and gives you pre-escaped variants
that can be reused when building SQL statements.

### Examples

```php
$columnName = ' SENÕR ';

$columnObject = r4ndsen\SQLite\Column::createDefaultColumn($columnName);

$columnObject->getRaw(); // ' SENÕR '
$columnObject->getPlain(); // 'SENÕR'
$columnObject->__toString();  // '` SENÕR `'

$columnObject->getTrimmed(); // 'SENÕR'
$columnObject->getTrimmedLower(); // 'senõr'
$columnObject->getTrimmedEscaped(); // '`SENÕR`'

$columnObject->getLower(); // ' senõr '
$columnObject->getLowerTrimmedEscaped(); // '`senõr`'
```

Reach for the trimmed/escaped methods whenever you need to compose SQL fragments
(indexes, constraints, etc.) and rely on the lowercase variants when you need a
consistent lookup key independent of input casing.
