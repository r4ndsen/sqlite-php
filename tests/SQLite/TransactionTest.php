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

        $this->SQLite->getTable('test')
            ->addCreateColumn($id)
            ->addCreateColumn($content)
            ->createIfNotExists()
        ;

        $preparedStatement = $this->SQLite->prepare('insert into `test` (id, content) values (?, ?)');
        $preparedStatement->activateTransaction();
        $preparedStatement->setCommitModulo(1);

        $preparedStatement->bind(['1', 'foo']);
        $preparedStatement->bind(['2', 'bar']);
        $preparedStatement->commit();
        self::assertCount(2, $this->SQLite->getTable('test'));
        $preparedStatement->deactivateTransaction();

        $preparedStatement->bind(['3', 'baz']);
        self::assertCount(3, $this->SQLite->getTable('test'));
    }

    #[Test]
    public function it_should_transaction_integration_testing2(): void
    {
        $id = Column::createIntegerColumn('id');
        $content = Column::createDefaultColumn('content');

        $this->SQLite->getTable('test')
            ->addCreateColumn($id)
            ->addCreateColumn($content)
            ->createIfNotExists()
        ;

        $preparedStatement = $this->SQLite->prepare('insert into `test` (id, content) values (?, ?)');
        $preparedStatement->activateTransaction();
        $preparedStatement->setCommitModulo(10);

        $preparedStatement->bind(['1', 'foo']);
        $preparedStatement->bind(['2', 'bar']);
        $preparedStatement->deactivateTransaction(); // auto commit
        self::assertCount(2, $this->SQLite->getTable('test'));

        $preparedStatement->bind(['3', 'baz']);
        self::assertCount(3, $this->SQLite->getTable('test'));
    }
}
