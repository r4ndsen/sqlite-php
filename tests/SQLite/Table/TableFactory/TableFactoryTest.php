<?php

namespace r4ndsen\SQLite\Table\TableFactory;

use PHPUnit\Framework\Attributes\Test;
use r4ndsen\SQLite\Table;
use r4ndsen\SQLite\TestCase;

class TableFactoryTest extends TestCase
{
    #[Test]
    public function it_should_create_custom_table(): void
    {
        $custom = new class($this->SQLite->getConnection()) extends DefaultTableFactory {
            protected function createTable(string $tableName): Table
            {
                return parent::createTable('fun_' . $tableName);
            }
        };

        $this->SQLite->setTableFactory($custom);

        self::assertFalse($this->SQLite->getTable('fun_data')->exists());
        $this->SQLite->data->getDynamicInsertTable()->push(['id' => 1]);
        self::assertFalse($this->SQLite->getTable('fun_data')->exists());
        self::assertTrue($this->SQLite->getTable('data')->exists());

        self::assertSame(1, $this->SQLite->fetchValue('select count(*) from fun_data'));
    }
}
