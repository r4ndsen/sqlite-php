<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use r4ndsen\SQLite\ColumnFactory\ColumnFactory;
use r4ndsen\SQLite\ColumnFactory\ColumnFactoryInterface;
use r4ndsen\SQLite\Exception\ColumnDoesNotExistException;
use r4ndsen\SQLite\Exception\CreateTableFailedException;
use r4ndsen\SQLite\Exception\DeleteColumnException;
use r4ndsen\SQLite\Exception\FeatureNotSupportedException;
use r4ndsen\SQLite\Exception\QueryException;
use r4ndsen\SQLite\Exception\RenameColumnException;
use r4ndsen\SQLite\Exception\SQLiteException;
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

    // Upper limit of insert statements kept prepared at the same time
    private const int MAX_PREPARED_STATEMENTS = 100;

    protected ColumnFactoryInterface $columnFactory;

    /**
     * Contains the current columns the table has.
     *
     * @var Column[]
     */
    protected array $columns = [];

    /**
     * Holds columns to create the table.
     *
     * @var Column[]|CreateColumn[]
     */
    protected array $createColumns = [];

    /**
     * Holds column constraints like unique and primary keys.
     *
     * @var TableConstraint[]
     */
    protected array $createConstraints = [];

    /**
     * Prepared insert statements utilized by push() and pushWithoutKeys(),
     * by their serialized column list or by their number of values respectively.
     *
     * @var array<int|string, PreparedStatement>
     */
    protected array $preparedStatements = [];

    /**
     * Columns of the last row passed to push(), rows without keys are bound in this order.
     *
     * @var list<int|string>
     */
    protected array $pushColumns = [];

    // Indicate whether to use transactions for prepared statements
    protected bool $withTransaction = false;

    /** @var list<int|string> */
    private array $lastInsertColumns = [];

    private string $lastInsertKey = '';

    public function __construct(
        private Connection $conn,
        private string $name,
    ) {
        $this->name = trim($name);
    }

    public function __toString(): string
    {
        return self::backtickIdentifier($this->name);
    }

    /** @throws SQLiteException */
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

    public function addCreateColumn(CreateColumn $column): static
    {
        $this->createColumns[] = $column;

        return $this;
    }

    /** @throws SQLiteException */
    public function addIndex(Index $index): bool
    {
        return $this->exec($index->getCreateStatement($this));
    }

    public function columnExists(string $name): bool
    {
        return Column::ROWID === $name || isset($this->columns()[mb_strtolower(trim($name), 'UTF-8')]);
    }

    /** @return  array<string, Column> */
    public function columns(): array
    {
        return $this->columns ?: $this->schema();
    }

    public function commit(): static
    {
        foreach ($this->preparedStatements as $preparedStatement) {
            $preparedStatement->commit();
        }

        $this->preparedStatements = [];
        $this->pushColumns = [];

        return $this;
    }

    /** @return int<0, max> */
    public function count(): int
    {
        try {
            return max(0, (int) $this->fetchValue('select count(*) from ' . $this));
        } catch (TableDoesNotExistException) {
            return 0;
        }
    }

    /**
     * @throws CreateTableFailedException
     * @throws TableAlreadyCreatedException
     * @throws SQLiteException
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
     * @throws SQLiteException
     */
    public function createFromArray(array $columnNames): bool
    {
        $this->createColumns = [];

        foreach (array_unique($columnNames) as $columnName) {
            if (Column::ROWID === $columnName) {
                continue;
            }
            $this->addCreateColumn($this->getColumnFactory()->createColumn((string) $columnName));
        }

        return $this->create();
    }

    /** @throws SQLiteException */
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
     * @throws SQLiteException
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

    /** @throws SQLiteException */
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
                    `name` = ? collate nocase";

        return (bool) $this->fetchValue($sql, [$this->name]);
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

        foreach ($this->columns() as $column) {
            if ($column->getTrimmedLower() === $search) {
                return $column;
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

    /** @throws SQLiteException */
    public function getIterator(): Traversable
    {
        yield from $this->fetchAll('select * from ' . $this);
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
        $sql = sprintf('select max(%s) from %s', Column::ROWID, $this);

        try {
            return (int) $this->fetchValue($sql);
        } catch (TableDoesNotExistException|ColumnDoesNotExistException) {
            // the table does not exist or has no rowid
            return 0;
        }
    }

    /**
     * Inserts a row, the keys are the column names.
     * A row without keys is bound in the column order of the row pushed before it.
     *
     * @throws SQLiteException
     */
    public function push(array $data): static
    {
        $columns = array_keys($data);

        if ($columns !== $this->pushColumns) {
            if ($data !== [] && array_is_list($data) && \count($data) === \count($this->pushColumns)) {
                $columns = $this->pushColumns;
            } else {
                $this->pushColumns = $columns;
            }
        }

        return $this->insert($columns, $data);
    }

    /**
     * Inserts the values in the order of the table columns.
     *
     * @throws SQLiteException
     */
    public function pushWithoutKeys(array $data): static
    {
        $key = \count($data);

        $this->preparedStatements[$key] ??= $this->prepareInsert(
            sprintf(
                'insert into %s values (%s)',
                $this,
                rtrim(str_repeat('?,', $key), ',')
            )
        );

        $this->preparedStatements[$key]->bind($data);

        return $this;
    }

    /**
     * @throws SQLiteException
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
     * fetches the columns again (resets the cache)
     *
     * @return array<string, Column>
     */
    public function schema(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $this->columns = [];
        foreach ($this->getPragma()->getTableColumnSchemas($this) as $columnSchema) {
            $column = Column::createFromSchema($columnSchema);
            $this->columns[$column->getLower()] = $column;
        }

        return $this->columns;
    }

    public function setColumnFactory(ColumnFactoryInterface $columnFactory): static
    {
        $this->columnFactory = $columnFactory;

        return $this;
    }

    /** @throws SQLiteException */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /** @throws SQLiteException */
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

    /**
     * @throws InvalidArgumentException
     * @throws SQLiteException
     */
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

    /** @throws SQLiteException */
    protected function hasRows(): bool
    {
        return (bool) $this->fetchValue('select exists(select 1 from ' . $this . ')');
    }

    /**
     * Inserts the values into the given columns, columns left out get their default value.
     *
     * @param list<int|string> $columns
     * @param array<mixed>     $values  bound in their order, the keys are ignored
     *
     * @throws SQLiteException
     */
    protected function insert(array $columns, array $values): static
    {
        if ($columns !== $this->lastInsertColumns || !isset($this->preparedStatements[$this->lastInsertKey])) {
            $key = serialize($columns);

            if (!isset($this->preparedStatements[$key])) {
                if ($columns === []) {
                    $sql = sprintf('insert into %s default values', $this);
                } else {
                    $sql = sprintf(
                        'insert into %s (%s) values (%s)',
                        $this,
                        implode(',', array_map(static fn ($column) => Column::createDefaultColumn((string) $column), $columns)),
                        rtrim(str_repeat('?,', \count($columns)), ',')
                    );
                }

                $this->preparedStatements[$key] = $this->prepareInsert($sql);
            }

            $this->lastInsertColumns = $columns;
            $this->lastInsertKey = $key;
        }

        $this->preparedStatements[$this->lastInsertKey]->bind($values);

        return $this;
    }

    private function prepareInsert(string $sql): PreparedStatement
    {
        if (\count($this->preparedStatements) >= self::MAX_PREPARED_STATEMENTS) {
            $pushColumns = $this->pushColumns;
            $this->commit();
            $this->pushColumns = $pushColumns;
        }

        $preparedStatement = new PreparedStatement($this->conn, $sql);

        if ($this->withTransaction) {
            $preparedStatement->activateTransaction();
        }

        return $preparedStatement;
    }
}
