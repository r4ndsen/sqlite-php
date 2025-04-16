# r4ndsen\SQLite\Column

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

# TODO

```
