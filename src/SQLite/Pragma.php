<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use InvalidArgumentException;
use r4ndsen\SQLite\Exception\SQLiteException;
use r4ndsen\SQLite\Pragma\Constant;
use r4ndsen\SQLite\Pragma\Encoding;
use r4ndsen\SQLite\Pragma\JournalMode;
use r4ndsen\SQLite\Pragma\LockingMode;
use r4ndsen\SQLite\Pragma\Synchronous;
use r4ndsen\SQLite\Pragma\TempStore;
use r4ndsen\SQLite\Traits\QueryTrait;
use TypeError;

final class Pragma implements Constant
{
    use QueryTrait;

    public function __construct(private Connection $conn)
    {
    }

    public function __get(string $key): mixed
    {
        return $this->querySingle(
            sprintf(
                "pragma '%s'",
                $this->escape(trim($key, ' \'"'))
            )
        );
    }

    /** we need to actually load it to make sure it hasn't been changed by outside logic in the meantime. */
    public function __isset(string $key): bool
    {
        return $this->__get($key) !== null;
    }

    /**
     * @throws InvalidArgumentException
     * @throws TypeError
     */
    public function __set(string $key, mixed $value): void
    {
        if (strtolower($key) === Constant::SYNCHRONOUS) {
            $value instanceof Synchronous || $value = Synchronous::fromString($value);
            $this->setSynchronous($value);
        } elseif (strtolower($key) === Constant::JOURNAL_MODE) {
            $value instanceof JournalMode || $value = JournalMode::fromString($value);
            $this->setJournalMode($value);
        } elseif (strtolower($key) === Constant::LOCKING_MODE) {
            $value instanceof LockingMode || $value = LockingMode::fromString($value);
            $this->setLockingMode($value);
        } elseif (strtolower($key) === Constant::TEMP_STORE) {
            $value instanceof TempStore || $value = TempStore::fromString($value);
            $this->setTempStore($value);
        } elseif (strtolower($key) === Constant::ENCODING) {
            $value instanceof Encoding || $value = Encoding::fromString($value);
            $this->setEncoding($value);
        } else {
            $this->set($key, $value);
        }
    }

    public function getCacheSize(): int
    {
        // @phpstan-ignore cast.int
        return (int) $this->__get(Constant::CACHE_SIZE);
    }

    /** returns a list of attached databases. */
    public function getDatabaseList(): array
    {
        return $this->fetchObjects('pragma database_list');
    }

    public function getEncoding(): Encoding
    {
        return Encoding::fromString(
            $this->__get(Constant::ENCODING)
        );
    }

    public function getJournalMode(): JournalMode
    {
        return JournalMode::fromString(
            $this->__get(Constant::JOURNAL_MODE)
        );
    }

    public function getLockingMode(): LockingMode
    {
        return LockingMode::fromString(
            $this->__get(Constant::LOCKING_MODE)
        );
    }

    public function getPageSize(): int
    {
        // @phpstan-ignore cast.int
        return (int) $this->__get(Constant::PAGE_SIZE);
    }

    public function getSynchronous(): Synchronous
    {
        return Synchronous::from(
            $this->__get(Constant::SYNCHRONOUS)
        );
    }

    /** @return ColumnSchema[] */
    public function getTableColumnSchemas(Table $table): array
    {
        // @phpstan-ignore return.type
        return $this->fetchInstances(
            sql: sprintf('pragma table_info(%s)', $table),
            class: ColumnSchema::class
        );
    }

    /** @return string[] */
    public function getTableNames(): array
    {
        return $this->fetchCol("select `name` from `sqlite_master` where `type` = 'table'");
    }

    public function getTempStore(): TempStore
    {
        return TempStore::fromString(
            $this->__get(Constant::TEMP_STORE)
        );
    }

    /** @see https://www.sqlite.org/pragma.html#pragma_integrity_check */
    public function integrityCheck(): ?string
    {
        // @phpstan-ignore return.type
        return $this->__get(Constant::INTEGRITY_CHECK) ?: null;
    }

    /** @see https://www.sqlite.org/pragma.html#pragma_quick_check */
    public function quickCheck(): ?string
    {
        // @phpstan-ignore return.type
        return $this->__get(Constant::QUICK_CHECK) ?: null;
    }

    public function setCacheSize(int $size): self
    {
        $this->__set(Constant::CACHE_SIZE, $size);

        return $this;
    }

    public function setEncoding(Encoding $mode): self
    {
        return $this->set(Constant::ENCODING, $mode->value);
    }

    public function setJournalMode(JournalMode $mode): self
    {
        return $this->set(Constant::JOURNAL_MODE, $mode->name);
    }

    public function setLockingMode(LockingMode $mode): self
    {
        return $this->set(Constant::LOCKING_MODE, $mode->name);
    }

    /**
     * Query or set the page size of the database.
     * The page size must be a power of two between 512 and 65536 inclusive.
     *
     * @see https://www.sqlite.org/pragma.html#pragma_page_size
     *
     * @throws SQLiteException
     */
    public function setPageSize(int $size): self
    {
        // 1024 .. 65536
        $valid = [2 << 9, 2 << 10, 2 << 11, 2 << 12, 2 << 13, 2 << 14, 2 << 15];
        if (!\in_array($size, $valid, true)) {
            throw new SQLiteException(Constant::PAGE_SIZE . ' must be in (' . implode(', ', $valid) . ')');
        }

        $this->__set(Constant::PAGE_SIZE, $size);

        return $this;
    }

    public function setSynchronous(Synchronous $mode): self
    {
        return $this->set(Constant::SYNCHRONOUS, $mode->value);
    }

    public function setTempStore(TempStore $mode): self
    {
        return $this->set(Constant::TEMP_STORE, $mode->name);
    }

    private function set(string $key, string|int $value): self
    {
        $sql = sprintf(
            "pragma '%s' = '%s'",
            $this->escape(trim($key, ' \'"')),
            $this->escape((string) $value),
        );

        $this->exec($sql);

        return $this;
    }
}
