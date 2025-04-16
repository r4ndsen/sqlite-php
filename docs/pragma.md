# Pragma Settings

Pragma settings can be accessed by getting the pragma "table" and accessing the settings via magic `__get()`. Some methods are provided on setting and getting special fields:

```php
use r4ndsen\SQLite\Pragma;

$pragma->setPageSize(int $size);
$pragma->getPageSize(): int;

$pragma->setCacheSize(int $size);
$pragma->getCacheSize(): int;
```

### Example Usage:

```php
use r4ndsen\SQLite\Pragma\JournalMode;
use r4ndsen\SQLite\Pragma\Synchronous;

$sqlite = new r4ndsen\SQLite;

$pragma = $sqlite->pragma;

var_dump($pragma->journal_mode);
var_dump($pragma->getCacheSize());

$pragma->journal_mode = 'MEMORY';
$pragma->setJournalMode(JournalMode::OFF);

$pragma->setSynchronous(Synchronous::EXTRA);
$pragma->getSynchronous()->value; // 4

$pragma->synchronous = 2; // Synchronous::FULL
$pragma->getSynchronous()->value; // 2
```
