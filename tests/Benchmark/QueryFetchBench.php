<?php

declare(strict_types=1);

namespace r4ndsen\Benchmark;

use PhpBench\Attributes\Assert;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Revs;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Column;

final class QueryFetchBench
{
    private SQLite $sqlite;

    public function setUp(): void
    {
        $this->sqlite = new SQLite();
        $table = $this->sqlite->getTable('bench_query');
        $table->drop();
        $table
            ->addCreateColumn(Column::createIntegerColumn('id'))
            ->addCreateColumn(Column::createTextColumn('payload'))
            ->create();

        $table->withTransaction(true);
        foreach (range(0, 24) as $i) {
            $table->push([
                'id'      => $i,
                'payload' => 'value-' . $i,
            ]);
        }
        $table->commit();
    }

    #[BeforeMethods('setUp')]
    #[Revs(1000)]
    #[Assert('mode(variant.time.avg) < 120 us')]
    public function bench_fetch_all(): void
    {
        $this->sqlite->fetchAll('select * from `bench_query`');
    }

    #[BeforeMethods('setUp')]
    #[Revs(2000)]
    #[Assert('mode(variant.time.avg) < 60 us')]
    public function bench_fetch_pairs(): void
    {
        $this->sqlite->fetchPairs('select id, payload from `bench_query`');
    }

    #[BeforeMethods('setUp')]
    #[Revs(2000)]
    #[Assert('mode(variant.time.avg) < 30 us')]
    public function bench_fetch_value(): void
    {
        $this->sqlite->fetchValue('select count(*) from `bench_query`');
    }
}
