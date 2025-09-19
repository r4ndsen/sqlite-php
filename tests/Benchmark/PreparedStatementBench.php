<?php

declare(strict_types=1);

namespace r4ndsen\Benchmark;

use PhpBench\Attributes\Assert;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Revs;
use r4ndsen\SQLite;
use r4ndsen\SQLite\Column;

final class PreparedStatementBench
{
    private SQLite\PreparedStatement $associative;
    private SQLite\PreparedStatement $positional;
    private SQLite $sqlite;
    private SQLite\PreparedStatement $transactional;

    public function setUp(): void
    {
        $this->sqlite = new SQLite();
        $table = $this->sqlite->getTable('bench_prepared');
        $table->drop();
        $table
            ->addCreateColumn(Column::createDefaultColumn('payload'))
            ->create();

        $this->positional = $this->sqlite->prepare('insert into `bench_prepared` (payload) values (?)');
        $this->associative = $this->sqlite->prepare('insert into `bench_prepared` (payload) values (:payload)');
        $this->transactional = $this->sqlite->prepare('insert into `bench_prepared` (payload) values (?)');
        $this->transactional->activateTransaction()->setCommitModulo(1000);
    }

    #[BeforeMethods('setUp')]
    #[Revs(50000)]
    #[Assert('mode(variant.time.avg) < 15 us')]
    public function bench_bind_associative(): void
    {
        $this->associative->bindAssoc(['payload' => 'value']);
    }

    #[BeforeMethods('setUp')]
    #[Revs(50000)]
    #[Assert('mode(variant.time.avg) < 12 us')]
    public function bench_bind_positional(): void
    {
        $this->positional->bind(['value']);
    }

    #[BeforeMethods('setUp')]
    #[Revs(50000)]
    #[Assert('mode(variant.time.avg) < 18 us')]
    public function bench_bind_with_transaction(): void
    {
        $this->transactional->bind(['value']);
    }
}
