<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

use Generator;
use r4ndsen\SQLite\Exception\SQLiteException;
use r4ndsen\SQLite\PreparedStatement;
use r4ndsen\SQLite\QueryParser;
use ReflectionClass;
use ReflectionException;
use SQLite3Result;
use stdClass;

/** @template T of object */
trait QueryTrait
{
    use ExecTrait;

    public function changes(): int
    {
        return $this->conn->changes();
    }

    /**
     * the entire result set as array.
     *
     * @throws SQLiteException
     */
    public function fetchAll(string $sql, array $bind = []): array
    {
        return iterator_to_array($this->yieldAll($sql, $bind));
    }

    /**
     * the values in the first selected column as an array.
     *
     * @throws SQLiteException
     */
    public function fetchCol(string $sql, array $bind = []): array
    {
        return iterator_to_array($this->yieldCol($sql, $bind));
    }

    /**
     * the first row of the result set as an object of given class.
     *
     * @param class-string<T> $class
     *
     * @return T|null
     *
     * @throws ReflectionException
     */
    public function fetchInstance(string $sql, array $bind = [], string $class = stdClass::class): ?object
    {
        foreach ($this->yieldInstances($sql, $bind, $class) as $instance) {
            return $instance;
        }

        return null;
    }

    /**
     * @param class-string<T> $class
     *
     * @return T[]
     *
     * @throws ReflectionException
     * @throws SQLiteException
     */
    public function fetchInstances(string $sql, array $bind = [], string $class = stdClass::class): array
    {
        return iterator_to_array($this->yieldInstances($sql, $bind, $class));
    }

    /**
     * the first row of the result set as an object of given class.
     *
     * @param class-string<T> $class
     *
     * @return T|null
     *
     * @throws ReflectionException
     * @throws SQLiteException
     */
    public function fetchObject(string $sql, array $bind = [], string $class = stdClass::class, array $cArgs = []): ?object
    {
        foreach ($this->yieldObjects($sql, $bind, $class, $cArgs) as $object) {
            return $object;
        }

        return null;
    }

    /**
     * an array of objects created from the selected fields.
     *
     * @param class-string<T> $class the class of the objects you want to receive
     *
     * @return T[]
     *
     * @throws SQLiteException
     * @throws ReflectionException
     */
    public function fetchObjects(string $sql, array $bind = [], string $class = stdClass::class, array $cArgs = []): array
    {
        return iterator_to_array($this->yieldObjects($sql, $bind, $class, $cArgs));
    }

    /**
     * the first row as an associative array where the keys are the column names.
     *
     * @throws SQLiteException
     */
    public function fetchOne(string $sql, array $bind = []): ?array
    {
        foreach ($this->yieldAll($sql, $bind) as $row) {
            return $row;
        }

        return null;
    }

    /**
     * returns a key-value pair from first result row. the first and second columns as key and value.
     *
     * @throws SQLiteException
     */
    public function fetchPair(string $sql, array $bind = []): array
    {
        foreach ($this->yieldPairs($sql, $bind) as $key => $value) {
            return [$key => $value];
        }

        return [];
    }

    /**
     * each result is a key-value pair from the first and second columns.
     *
     * @throws SQLiteException
     */
    public function fetchPairs(string $sql, array $bind = []): array
    {
        return iterator_to_array($this->yieldPairs($sql, $bind), true);
    }

    public function fetchPlain(string $sql, array $bind = []): array
    {
        return iterator_to_array($this->yieldPlain($sql, $bind), false);
    }

    /**
     * the value of the first row in the first column.
     *
     * @return float|int|string|null
     *
     * @throws SQLiteException
     */
    public function fetchValue(string $sql, array $bind = [])
    {
        foreach ($this->yieldCol($sql, $bind) as $value) {
            return $value;
        }

        return null;
    }

    public function lastInsertRowID(): int
    {
        return $this->conn->lastInsertRowID();
    }

    /**
     * create a prepared statement and bind associative params to it.
     *
     * @return SQLite3Result The result of the executed statement
     *
     * @throws SQLiteException
     */
    public function perform(string $sql, array $bind = []): SQLite3Result
    {
        $parser = new QueryParser($sql, $bind);
        $sql = $parser->getStatement();
        $bind = $parser->getValues();

        return $this->prepare($sql)->bindAssoc($bind);
    }

    /** create a prepared statement from a sql string. */
    public function prepare(string $sql): PreparedStatement
    {
        return new PreparedStatement($this->conn, $sql);
    }

    /** @throws SQLiteException */
    public function query(string $sql): SQLite3Result
    {
        return $this->conn->query($sql);
    }

    /**
     * Returns the value of the first column of results or an array of the entire first row (if entireRow is TRUE).
     * If the query is valid but no results are returned, then NULL will be returned if entireRow is FALSE, otherwise an empty array is returned.
     * Invalid or failing queries will return FALSE.
     *
     * @see fetchValue() for a more advanced usage
     *
     * @return array|bool|float|int|string|null
     *
     * @throws SQLiteException
     */
    public function querySingle(string $sql, bool $entireRow = false): mixed
    {
        return $this->conn->querySingle($sql, $entireRow);
    }

    /**
     * yields each result as associative array.
     *
     * @return Generator<array>
     *
     * @throws SQLiteException
     */
    public function yieldAll(string $sql, array $bind = []): Generator
    {
        $result = $this->perform($sql, $bind);

        yield from $this->fetchArray($result, SQLITE3_ASSOC);
    }

    /**
     * yields the values in the first column.
     *
     * @throws SQLiteException
     */
    public function yieldCol(string $sql, array $bind = []): Generator
    {
        foreach ($this->yieldAll($sql, $bind) as $row) {
            yield current($row);
        }
    }

    /**
     * produces instances of given class calling the classes native constructor.
     *
     * @param string          $sql   the sql query
     * @param array           $bind  the params you want to bind to your query
     * @param class-string<T> $class the class of the objects you want to receive
     *
     * @return Generator<T|null>
     *
     * @throws ReflectionException
     * @throws SQLiteException
     */
    public function yieldInstances(string $sql, array $bind = [], string $class = stdClass::class): Generator
    {
        $reflectionClass = new ReflectionClass($class);

        if (!$reflectionClass->isInstantiable()) {
            return yield;
        }

        // check whether a constructor is defined
        if ($reflectionClass->getConstructor() === null) {
            foreach ($this->yieldAll($sql, $bind) as $ignored) {
                yield new $class();
            }
        } else {
            foreach ($this->yieldAll($sql, $bind) as $row) {
                yield $reflectionClass->newInstanceArgs(array_values($row));
            }
        }
    }

    /**
     * @param string          $sql   the sql query
     * @param array           $bind  the params you want to bind to your query
     * @param class-string<T> $class the class of the objects you want to receive
     * @param array           $cArgs constructor arguments to add when instantiating
     *
     * @return Generator<T|null>
     *
     * @throws ReflectionException
     * @throws SQLiteException
     */
    public function yieldObjects(string $sql, array $bind = [], string $class = stdClass::class, array $cArgs = []): Generator
    {
        $reflectionClass = new ReflectionClass($class);

        if ($reflectionClass->isAbstract()) {
            return yield; // cannot be instantiated
        }

        foreach ($this->yieldAll($sql, $bind) as $row) {
            if ($reflectionClass->isInstantiable()) {
                $object = $reflectionClass->newInstanceArgs($cArgs);
            } else {
                $constructor = $reflectionClass->getConstructor();
                $constructor?->setAccessible(true);
                $object = $reflectionClass->newInstanceWithoutConstructor();

                $constructor?->invokeArgs($object, $cArgs);
            }

            foreach ($row as $key => $value) {
                if ($reflectionClass->hasProperty((string) $key)) {
                    $property = $reflectionClass->getProperty($key);
                    $property->setAccessible(true);
                    $property->setValue($object, $value);
                } else {
                    try {
                        // @phpstan-ignore argument.type
                        set_error_handler(static fn () => null, E_DEPRECATED);
                        $object->{$key} = $value;
                    } finally {
                        restore_error_handler();
                    }
                }
            }

            yield $object;
        }
    }

    /**
     * each result is a key-value pair from the first and second column.
     *
     * @throws SQLiteException
     */
    public function yieldPairs(string $sql, array $bind = []): Generator
    {
        foreach ($this->yieldPlain($sql, $bind) as $row) {
            yield $row[0] => $row[1] ?? null;
        }
    }

    /**
     * yields all returned values without column names.
     *
     * @throws SQLiteException
     */
    public function yieldPlain(string $sql, array $bind = []): Generator
    {
        $res = $this->perform($sql, $bind);

        yield from $this->fetchArray($res, SQLITE3_NUM);
    }

    /** yields the result of an SQLite3Result object as an array per row */
    private function fetchArray(SQLite3Result $result, int $mode = SQLITE3_ASSOC): Generator
    {
        while (($row = $result->fetchArray($mode)) !== false) {
            yield $row;
        }
        $result->finalize();
    }
}
