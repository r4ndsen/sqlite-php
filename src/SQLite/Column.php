<?php

declare(strict_types=0);

namespace r4ndsen\SQLite;

use BadMethodCallException;
use InvalidArgumentException;
use r4ndsen\SQLite\Traits\EscapeTrait;
use Stringable;

final class Column implements CreateColumnInterface, Stringable
{
    use EscapeTrait;

    public const DEFAULT_VALUE = '';
    public const NOT_NULL = 'NOT NULL';
    public const ROWID = 'rowid';

    /** Column id, will be populated by the table the column is placed in */
    private int $columnId;

    private ?string $maybeNull = null;

    /** Primary key flag, will be populated by the table the column is placed in */
    private bool $pk;

    public function __construct(
        private readonly string $name,
        public readonly ColumnType $type = ColumnType::TEXT,
        public readonly mixed $defaultValue = null,
    ) {
    }

    public function __debugInfo(): array
    {
        return [
            'column id'     => $this->getColumnId(),
            'name'          => $this->name,
            'type'          => $this->type->name,
            'not null'      => (int) ($this->maybeNull !== null),
            'default value' => $this->defaultValue,
            'primary key'   => (int) $this->getPk(),
        ];
    }

    public function __toString(): string
    {
        return $this->getEscaped();
    }

    public function allowNull(): self
    {
        $this->maybeNull = null;

        return $this;
    }

    public static function createBlobColumn(string $name, ?string $defaultValue = null): self
    {
        return new self($name, ColumnType::BLOB, $defaultValue);
    }

    public static function createDefaultColumn(string $name): self
    {
        return self::createTextColumn($name);
    }

    public static function createFloatColumn(string $name, ?float $defaultValue = null): self
    {
        return new self($name, ColumnType::REAL, $defaultValue);
    }

    public static function createFromSchema(array $row): self
    {
        $defaultValueKey = 'dflt_value';

        if (!\array_key_exists($defaultValueKey, $row)) {
            throw new InvalidArgumentException(sprintf('mandatory key "%s" is missing', $defaultValueKey));
        }

        if ($row[$defaultValueKey] === 'null') {
            $defaultValue = null;
        } else {
            $defaultValue = preg_replace("#^'|'$#", '', (string) $row[$defaultValueKey]);
        }

        $column = new self(
            $row['name'],
            ColumnType::fromString($row['type'] ?? ''),
            $defaultValue,
        );
        $column->setColumnId($row['cid']);
        $column->setPk((bool) $row['pk']);

        if ($row['notnull'] === 1) {
            $column->disallowNull();
        }

        return $column;
    }

    public static function createIntegerColumn(string $name, ?int $defaultValue = null): self
    {
        return new self($name, ColumnType::INTEGER, $defaultValue);
    }

    public static function createNumericColumn(string $name, ?string $defaultValue = self::DEFAULT_VALUE): self
    {
        return new self($name, ColumnType::NUMERIC, $defaultValue);
    }

    public static function createTextColumn(string $name, ?string $defaultValue = ''): self
    {
        return new self($name, ColumnType::TEXT, $defaultValue);
    }

    public function disallowNull(): self
    {
        if ($this->defaultValue === null) {
            throw new BadMethodCallException('Cannot disallow NULL if the default value is NULL');
        }
        $this->maybeNull = self::NOT_NULL;

        return $this;
    }

    public function getColumnId(): ?int
    {
        return $this->columnId ?? null;
    }

    public function getCreateStatement(): string
    {
        return rtrim(
            sprintf(
                '%s %s default %s %s',
                $this->getTrimmedEscaped(),
                $this->type->name,
                $this->defaultValue === null ? 'null' : sprintf("'%s'", $this->defaultValue),
                $this->maybeNull
            )
        );
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function getEscaped(): string
    {
        return self::backtickIdentifier($this->getRaw());
    }

    public function getLower(): string
    {
        return mb_strtolower($this->getRaw(), 'UTF-8');
    }

    public function getLowerTrimmedEscaped(): string
    {
        return mb_strtolower($this->getTrimmedEscaped(), 'UTF-8');
    }

    public function getPk(): ?bool
    {
        return $this->pk ?? null;
    }

    public function getPlain(): string
    {
        return $this->getTrimmed();
    }

    public function getRaw(): string
    {
        return $this->name;
    }

    public function getTrimmed(): string
    {
        return trim($this->getRaw());
    }

    public function getTrimmedEscaped(): string
    {
        return self::backtickIdentifier($this->getTrimmed());
    }

    public function getTrimmedLower(): string
    {
        return mb_strtolower($this->getTrimmed(), 'UTF-8');
    }

    public function getType(): ColumnType
    {
        return $this->type;
    }

    private function setColumnId(int $id): self
    {
        $this->columnId = $id;

        return $this;
    }

    private function setPk(bool $pk): self
    {
        $this->pk = $pk;

        return $this;
    }
}
