# Pragma Settings

Access pragma settings through the `pragma` magic property or via
`SQLite::getPragma()`. The wrapper provides dedicated getters and setters for
commonly tuned options.

```php
use r4ndsen\SQLite;

$sqlite = new SQLite();
$pragma = $sqlite->pragma; // Same as $sqlite->getPragma()

$pragma->setPageSize(4_096);
$pageSize = $pragma->getPageSize();

$pragma->setCacheSize(2_000);
$cacheSize = $pragma->getCacheSize();
```

## Example usage

```php
use r4ndsen\SQLite;
use r4ndsen\SQLite\Pragma\JournalMode;
use r4ndsen\SQLite\Pragma\Synchronous;

$sqlite = new SQLite();
$pragma = $sqlite->pragma;

$pragma->journal_mode;          // lazy fetch via magic property
$pragma->getCacheSize();        // explicit getter

$pragma->journal_mode = 'MEMORY';
$pragma->setJournalMode(JournalMode::OFF);

$pragma->setSynchronous(Synchronous::EXTRA);
$pragma->getSynchronous()->value; // 4

$pragma->synchronous = 2;          // Equivalent to Synchronous::FULL
$pragma->getSynchronous()->value;  // 2
```
