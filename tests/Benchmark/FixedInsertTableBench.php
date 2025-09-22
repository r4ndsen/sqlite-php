<?php

namespace r4ndsen\Benchmark;

use PhpBench\Attributes\Assert;
use PhpBench\Attributes\BeforeMethods;
use r4ndsen\SQLite;

class FixedInsertTableBench
{
    private SQLite\Table\FixedInsert $table;

    public function setUp(): void
    {
        $this->table = (new SQLite())->getTable('table')->getFixedInsertTable();
    }

    #[BeforeMethods('setUp')]
    #[Assert('mode(variant.time.avg) < 6 us')]
    public function bench_insert(): void
    {
        $this->table->push(['foo' => 'bar']);
    }
}
