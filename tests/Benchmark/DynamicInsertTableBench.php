<?php

namespace r4ndsen\Benchmark;

use PhpBench\Attributes\Assert;
use PhpBench\Attributes\BeforeMethods;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Pragma\JournalMode;

class DynamicInsertTableBench
{
    public const DATA = [
        'col1A' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod',
        'col1B' => 'tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,',
        'col1C' => 'quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo',
        'col1D' => 'consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse',
        'col1E' => 'cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non',
        'col1F' => 'proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
        'col2A' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod',
        'col2B' => 'tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,',
        'col2C' => 'quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo',
        'col2D' => 'consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse',
        'col2E' => 'cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non',
        'col2F' => 'proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
        'col3A' => 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod',
        'col3B' => 'tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,',
        'col3C' => 'quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo',
        'col3D' => 'consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse',
        'col3E' => 'cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non',
        'col3F' => 'proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    ];

    private SQLite $SQLite;
    private SQLite\Table\DynamicInsert $table;

    public function setUp(): void
    {
        $this->SQLite = new SQLite();
        $this->table = $this->SQLite->getTable('table')->getDynamicInsertTable();
        $this->SQLite->getConnection()->createFunction('emptyCallbackNullFist', static fn ($s) => null === $s || '' === $s);
        $this->SQLite->getConnection()->createFunction('emptyCallbackStringFirst', static fn ($s) => '' === $s || null === $s);
    }

    #[BeforeMethods(['setUp', 'bench_insert_with_pragma_all'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_callback_native_coalesce(): void
    {
        $this->SQLite->querySingle("select * from `table` where coalesce(col1A,'') = ''");
    }

    #[BeforeMethods(['setUp', 'bench_insert_with_pragma_all'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_callback_native_empty_string_first(): void
    {
        $this->SQLite->querySingle("select * from `table` where col1A = '' or col1A is null");
    }

    #[BeforeMethods(['setUp', 'bench_insert_with_pragma_all'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_callback_native_null_first(): void
    {
        $this->SQLite->querySingle("select * from `table` where col1A is null or col1A = ''");
    }

    #[BeforeMethods(['setUp', 'bench_insert_with_pragma_all'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_empty_callback(): void
    {
        $this->SQLite->querySingle('select * from `table` where emptyCallbackNullFist(col1A)');
    }

    #[BeforeMethods(['setUp', 'bench_insert_with_pragma_all'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_empty_callback_2(): void
    {
        $this->SQLite->querySingle('select * from `table` where emptyCallbackStringFirst(col1A)');
    }

    #[Assert('mode(variant.time.avg) < 50 us')]
    #[BeforeMethods('setUp')]
    public function bench_insert_no_pragma(): void
    {
        $this->table->push(self::DATA);
    }

    #[BeforeMethods(['setUp', 'pragma_setting_locking_mode', 'pragma_setting_mmap_size', 'pragma_setting_journal_mode'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_insert_with_pragma_all(): void
    {
        $this->table->push(self::DATA);
    }

    #[BeforeMethods(['setUp', 'pragma_setting_locking_mode', 'pragma_setting_mmap_size'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_insert_with_pragma_locking_mode_and_mmap(): void
    {
        $this->table->push(self::DATA);
    }

    #[BeforeMethods(['setUp', 'pragma_setting_locking_mode'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_insert_with_pragma_only_locking_mode(): void
    {
        $this->table->push(self::DATA);
    }

    #[BeforeMethods(['setUp',  'pragma_setting_mmap_size'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_insert_with_pragma_only_mmap(): void
    {
        $this->table->push(self::DATA);
    }

    #[BeforeMethods(['setUp', 'pragma_setting_journal_mode'])]
    #[Assert('mode(variant.time.avg) < 50 us')]
    public function bench_insert_with_pragma_only_wal(): void
    {
        $this->table->push(self::DATA);
    }

    public function pragma_setting_journal_mode(): void
    {
        $this->SQLite->pragma->setJournalMode(JournalMode::WAL);
    }

    public function pragma_setting_locking_mode(): void
    {
        $this->SQLite->pragma->locking_mode = 'EXCLUSIVE';
    }

    public function pragma_setting_mmap_size(): void
    {
        $this->SQLite->pragma->mmap_size = 468_435_456;
    }
}
