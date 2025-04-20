<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use PHPUnit\Framework\Attributes\Test;

final class TransactionTest extends TestCase
{
    #[Test]
    public function it_should_multiple_transactions(): void
    {
        $t = new Transaction($this->SQLite->getConnection());
        $t2 = new Transaction($this->SQLite->getConnection());

        self::assertFalse($this->getPropertyValue($t, 'active'));
        self::assertFalse($this->getPropertyValue($t2, 'active'));

        $t->begin();
        self::assertTrue($this->getPropertyValue($t, 'active'));

        $t2->begin(); // runs into exception
        self::assertTrue($this->getPropertyValue($t2, 'active'));

        $t->commit();
        self::assertFalse($this->getPropertyValue($t, 'active'));

        $t2->commit(); // runs into exception
        self::assertFalse($this->getPropertyValue($t2, 'active'));
    }

    #[Test]
    public function it_should_transaction_integration_testing(): void
    {
        $id = Column::createIntegerColumn('id');
        $content = Column::createDefaultColumn('content');

        $table = $this->SQLite->getTable('test');
        $table
            ->addCreateColumn($id)
            ->addCreateColumn($content)
            ->createIfNotExists()
        ;

        $preparedstatement = $this->SQLite->prepare('insert into `test` (id, content) values (?, ?)');
        $preparedstatement->activateTransaction();
        $preparedstatement->setCommitModulo(1);

        $preparedstatement->bind(['1', 'foo']);
        $preparedstatement->bind(['2', 'bar']);
        $preparedstatement->commit();
        self::assertCount(2, $table);
        $preparedstatement->deactivateTransaction();

        $preparedstatement->bind(['3', 'baz']);
        self::assertCount(3, $table);
    }

    #[Test]
    public function it_should_transaction_integration_testing2(): void
    {
        $id = Column::createIntegerColumn('id');
        $content = Column::createDefaultColumn('content');

        $table = $this->SQLite->getTable('test');
        $table
            ->addCreateColumn($id)
            ->addCreateColumn($content)
            ->createIfNotExists()
        ;

        $preparedstatement = $this->SQLite->prepare('insert into `test` (id, content) values (?, ?)');
        $preparedstatement->activateTransaction();
        $preparedstatement->setCommitModulo(10);

        $preparedstatement->bind(['1', 'foo']);
        $preparedstatement->bind(['2', 'bar']);
        $preparedstatement->deactivateTransaction(); // auto commit
        self::assertCount(2, $table);

        $preparedstatement->bind(['3', 'baz']);
        self::assertCount(3, $table);
    }
}
