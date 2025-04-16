<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use BadMethodCallException;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use r4ndsen\SQLite\ColumnFactory\ColumnFactory;
use r4ndsen\SQLite\ColumnFactory\ColumnFactoryInterface;
use r4ndsen\SQLite\Exception\CreateTableFailedException;
use r4ndsen\SQLite\Exception\DeleteColumnException;
use r4ndsen\SQLite\Exception\FeatureNotSupportedException;
use r4ndsen\SQLite\Exception\QueryException;
use r4ndsen\SQLite\Exception\RenameColumnException;
use r4ndsen\SQLite\Exception\SyntaxErrorException;
use r4ndsen\SQLite\Exception\TableAlreadyCreatedException;
use r4ndsen\SQLite\Exception\TableDoesNotExistException;
use r4ndsen\SQLite\Table\DynamicInsert as DynamicInsertTable;
use r4ndsen\SQLite\Table\FixedInsert as FixedInsertTable;
use SQLite3;
use Stringable;
use Traversable;

/** @implements IteratorAggregate<array> */
class Table implements Countable, IteratorAggregate, Stringable
{
    use Traits\PragmaTrait;
    use Traits\QueryTrait;

    protected ColumnFactoryInterface $columnFactory;

    /**
     * Contains a cached array of the current columns the table has.
     *
     * @var Column[]
     */
    protected array $columns = [];

    /**
     * Holds columns to create the table.
     *
     * @var Column[]|CreateColumnInterface[]
     */
    protected array $createColumns = [];

    /**
     * Holds column constraints like unique and primary keys.
     *
     * @var TableConstraint[]
     */
    protected array $createConstraints = [];

    /** Utilized by the push() method */
    protected ?PreparedStatement $preparedStatement = null;

    /** Indicate whether to use transactions for prepared statements */
    protected bool $withTransaction = false;

    public function __construct(
        protected Connection $conn,
        protected string $name,
    ) {
        $this->name = trim($name);
    }

    public function __toString(): string
    {
        return self::backtickIdentifier($this->name);
    }

    /**
     * @throws QueryException
     * @throws TableDoesNotExistException
     */
    public function addColumn(Column $column): bool
    {
        if ($this->columnExists($column->getLower())) {
            return false;
        }

        $this->columns = [];

        return $this->exec(
            sprintf(
                'alter table %s add column %s',
                $this,
                $column->getCreateStatement()
            )
        );
    }

    public function addConstraint(TableConstraint $constraint): static
    {
        $this->createConstraints[] = $constraint;

        return $this;
    }

    public function addCreateColumn(CreateColumnInterface $column): static
    {
        $this->createColumns[] = $column;

        return $this;
    }

    /**
     * @throws BadMethodCallException
     * @throws TableDoesNotExistException
     * @throws QueryException
     */
    public function addIndex(Index $index): bool
    {
        return $this->exec(
            sprintf(
                $index->getCreateStatement(),
                $this
            )
        );
    }

    public function columnExists(string $name): bool
    {
        return Column::ROWID === $name || isset($this->columns()[mb_strtolower(trim($name), 'UTF-8')]);
    }

    /**
     * @return Column[]
     *
     * @throws TableDoesNotExistException
     */
    public function columns(): array
    {
        return $this->columns ?: $this->schema();
    }

    public function commit(): static
    {
        $this->preparedStatement = null;

        return $this;
    }

    public function count(): int
    {
        if (!$this->hasData()) {
            return 0;
        }

        return $this->querySingle('select count(*) from ' . $this);
    }

    /**
     * @throws CreateTableFailedException
     * @throws TableAlreadyCreatedException
     */
    public function create(): bool
    {
        if ($this->exists()) {
            throw new TableAlreadyCreatedException($this->name);
        }

        try {
            return $this->exec(
                sprintf(
                    'create table %s (%s)',
                    $this,
                    $this->buildCreateStatement()
                )
            );
        } catch (QueryException $e) {
            throw new CreateTableFailedException($e->getMessage());
        }
    }

    /**
     * @throws TableAlreadyCreatedException
     * @throws QueryException
     */
    public function createFromArray(array $columnNames): bool
    {
        if ($this->exists()) {
            throw new TableAlreadyCreatedException($this->name);
        }

        $this->createColumns = [];

        foreach (array_unique($columnNames) as $columnName) {
            if (Column::ROWID === $columnName) {
                continue;
            }
            $this->addCreateColumn($this->getColumnFactory()->createColumn((string) $columnName));
        }

        return $this->create();
    }

    /** @throws QueryException */
    public function createIfNotExists(): bool
    {
        return $this->exec(
            sprintf(
                'create table if not exists %s (%s)',
                $this,
                $this->buildCreateStatement()
            )
        );
    }

    /**
     * @throws TableDoesNotExistException
     * @throws FeatureNotSupportedException
     * @throws DeleteColumnException
     */
    public function deleteColumn(Column $column): bool
    {
        $this->columns = [];

        try {
            return $this->exec(
                sprintf(
                    'alter table %s drop column %s',
                    $this,
                    $column
                )
            );
        } catch (SyntaxErrorException $e) {
            throw new FeatureNotSupportedException('Drop column syntax can be used from SQLite3 3.35.0 - your version is: ' . SQLite3::version()['versionString'], previous: $e);
        } catch (QueryException $e) {
            throw DeleteColumnException::from($e);
        }
    }

    public function drop(): bool
    {
        $this->columns = [];

        return $this->exec('drop table if exists ' . $this);
    }

    public function exists(): bool
    {
        $sql = "select
                    `name`
                from
                    `sqlite_master`
                where
                    `type` = 'table' and
                    `name` = ?";

        return (bool) $this->fetchValue($sql, [$this->name]);
    }

    /** @throws TableDoesNotExistException */
    public function fetchAll(): Traversable
    {
        yield from $this->fetch('select * from ' . $this);
    }

    public function getColumnByColumnId(int $id): ?Column
    {
        foreach ($this->columns() as $column) {
            if ($column->getColumnId() === $id) {
                return $column;
            }
        }

        return null;
    }

    public function getColumnByName(string $name): ?Column
    {
        $search = mb_strtolower(trim($name), 'UTF-8');

        foreach ($this->columns() as $Column) {
            if ($Column->getTrimmedLower() === $search) {
                return $Column;
            }
        }

        return null;
    }

    public function getDynamicInsertTable(): DynamicInsertTable
    {
        return new DynamicInsertTable($this->conn, $this->name);
    }

    public function getFixedInsertTable(): FixedInsertTable
    {
        return new FixedInsertTable($this->conn, $this->name);
    }

    /** @throws TableDoesNotExistException */
    public function getIterator(): Traversable
    {
        yield from $this->fetchAll();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function hasData(): bool
    {
        return $this->exists() && $this->hasRows();
    }

    public function maxRow(): int
    {
        try {
            return (int) $this->querySingle('select max(`rowid`) from ' . $this);
        } catch (TableDoesNotExistException) {
            return 0;
        }
    }

    public function push(array $data): static
    {
        if ($this->preparedStatement === null) {
            $columns = array_map(
                static fn ($columnName) => Column::createDefaultColumn((string) $columnName),
                array_keys($data)
            );

            $sql = sprintf(
                'insert into %s (%s) values (%s)',
                $this,
                implode(',', $columns),
                implode(',', array_fill(0, \count($columns), '?'))
            );

            $this->preparedStatement = new PreparedStatement($this->conn, $sql);

            if ($this->withTransaction) {
                $this->preparedStatement->activateTransaction();
            }
        }

        $this->preparedStatement->bind(array_values($data));

        return $this;
    }

    /** @throws Exception\BindValueException */
    public function pushWithoutKeys(array $data): static
    {
        if ($this->preparedStatement === null) {
            $sql = sprintf(
                'insert into %s values (%s)',
                $this,
                implode(',', array_fill(0, \count($data), '?'))
            );

            $this->preparedStatement = new PreparedStatement($this->conn, $sql);

            if ($this->withTransaction) {
                $this->preparedStatement->activateTransaction();
            }
        }

        $this->preparedStatement->bind(array_values($data));

        return $this;
    }

    /**
     * @throws TableDoesNotExistException
     * @throws RenameColumnException
     */
    public function renameColumn(Column $from, Column $to): bool
    {
        $this->columns = [];

        try {
            return $this->exec(
                sprintf(
                    'alter table %s rename column %s to %s',
                    $this,
                    $from,
                    $to
                )
            );
        } catch (QueryException $e) {
            throw RenameColumnException::from($e);
        }
    }

    /**
     * fetches the columns again (resets the cache).
     *
     * @return Column[]
     */
    public function schema(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $this->columns = [];
        foreach ($this->getPragma()->tableInfo($this->name) as $row) {
            $column = Column::createFromSchema($row);
            $this->columns[$column->getLower()] = $column;
        }

        return $this->columns;
    }

    public function setColumnFactory(ColumnFactoryInterface $columnFactory): static
    {
        $this->columnFactory = $columnFactory;

        return $this;
    }

    /** @throws TableDoesNotExistException */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /** @throws TableDoesNotExistException */
    public function truncate(): bool
    {
        return $this->exec('delete from ' . $this);
    }

    /**
     * Use transactions for prepared statements.
     * Highly useful for bulk inserts.
     */
    public function withTransaction(bool $active): static
    {
        $this->withTransaction = $active;

        return $this;
    }

    protected function buildCreateStatement(): string
    {
        if (!$this->createColumns) {
            throw new InvalidArgumentException('No columns specified. Use addCreateColumn()');
        }

        $createStatement = [];
        foreach ($this->createColumns as $createColumn) {
            $createStatement[] = $createColumn->getCreateStatement();
        }

        foreach ($this->createConstraints as $createConstraint) {
            $createStatement[] = $createConstraint->getCreateStatement();
        }

        return implode(', ', $createStatement);
    }

    protected function getColumnFactory(): ColumnFactoryInterface
    {
        return $this->columnFactory ??= new ColumnFactory();
    }

    /** @throws TableDoesNotExistException */
    protected function hasRows(): bool
    {
        return (bool) $this->querySingle(
            sprintf(
                'select %s from %s limit 1',
                Column::ROWID,
                $this
            )
        );
    }
}
