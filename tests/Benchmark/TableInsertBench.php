<?php

namespace r4ndsen\Benchmark;

use PhpBench\Attributes\Assert;
use PhpBench\Attributes\BeforeMethods;
use r4ndsen\SQLite;

class TableInsertBench
{
    private SQLite\Table $table;

    public function setUp(): void
    {
        $this->table = (new SQLite())->getTable('table');
        $this->table->createFromArray(['foo']);
    }

    #[BeforeMethods('setUp')]
    #[Assert('mode(variant.time.avg) < 6 us')]
    public function bench_insert_with_keys(): void
    {
        $this->table->push(['foo' => 'bar']);
    }

    #[BeforeMethods('setUp')]
    #[Assert('mode(variant.time.avg) < 6 us')]
    public function bench_insert_without_keys(): void
    {
        $this->table->pushWithoutKeys(['bar']);
    }
}
