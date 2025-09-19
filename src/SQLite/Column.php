<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use BadMethodCallException;
use r4ndsen\SQLite\Traits\EscapeTrait;
use Stringable;

final class Column implements CreateColumnInterface, Stringable
{
    use EscapeTrait;

    public const NOT_NULL = 'NOT NULL';
    public const ROWID = 'rowid';

    /** Column id, will be populated by the table the column is placed in */
    private int $columnId;

    private ?string $defaultValue;

    /** Primary key flag, will be populated by the table the column is placed in */
    private bool $isPrimaryKey;

    private ?string $maybeNull = null;

    public function __construct(
        private readonly string $name,
        public readonly ColumnType $type = ColumnType::TEXT,
        string|Stringable|int|float|bool|null $defaultValue = null,
    ) {
        $this->defaultValue = self::normalizeDefaultValue($defaultValue);
    }

    public function __debugInfo(): array
    {
        return [
            'column id'     => $this->getColumnId(),
            'name'          => $this->name,
            'type'          => $this->type->name,
            'not null'      => (int) ($this->maybeNull !== null),
            'default value' => $this->defaultValue,
            'primary key'   => (int) $this->getIsPrimaryKey(),
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

    public static function createBlobColumn(string $name, string|Stringable|int|float|bool|null $defaultValue = null): self
    {
        return new self($name, ColumnType::BLOB, $defaultValue);
    }

    public static function createDefaultColumn(string $name): self
    {
        return self::createTextColumn($name);
    }

    public static function createFloatColumn(string $name, string|Stringable|int|float|bool|null $defaultValue = null): self
    {
        return new self($name, ColumnType::REAL, $defaultValue);
    }

    public static function createFromSchema(ColumnSchema $schema): self
    {
        $column = new self(
            $schema->name,
            $schema->type,
            $schema->defaultValue,
        );
        $column->setColumnId($schema->columnId);
        $column->setIsPrimaryKey($schema->isPrivateKey);

        if ($schema->notNull) {
            $column->disallowNull();
        }

        return $column;
    }

    public static function createIntegerColumn(string $name, string|Stringable|int|float|bool|null $defaultValue = null): self
    {
        return new self($name, ColumnType::INTEGER, $defaultValue);
    }

    public static function createNumericColumn(string $name, string|Stringable|int|float|bool|null $defaultValue = ''): self
    {
        return new self($name, ColumnType::NUMERIC, $defaultValue);
    }

    public static function createTextColumn(string $name, string|Stringable|int|float|bool|null $defaultValue = ''): self
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
        $defaultSql = 'null';
        if ($this->defaultValue !== null) {
            $defaultSql = sprintf("'%s'", self::escapeString($this->defaultValue));
        }

        return rtrim(
            sprintf(
                '%s %s default %s %s',
                $this->getTrimmedEscaped(),
                $this->type->name,
                $defaultSql,
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
        return self::backtickIdentifier($this->name);
    }

    public function getIsPrimaryKey(): ?bool
    {
        return $this->isPrimaryKey ?? null;
    }

    public function getLower(): string
    {
        return mb_strtolower($this->name, 'UTF-8');
    }

    public function getLowerTrimmedEscaped(): string
    {
        return mb_strtolower($this->getTrimmedEscaped(), 'UTF-8');
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
        return trim($this->name);
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

    private static function normalizeDefaultValue(string|Stringable|int|float|bool|null $defaultValue): ?string
    {
        if ($defaultValue === null) {
            return null;
        }

        if ($defaultValue instanceof Stringable) {
            return (string) $defaultValue;
        }

        if (\is_string($defaultValue)) {
            return $defaultValue;
        }

        if (\is_bool($defaultValue)) {
            return (string) (int) $defaultValue;
        }

        return (string) $defaultValue;
    }

    private function setColumnId(int $id): self
    {
        $this->columnId = $id;

        return $this;
    }

    private function setIsPrimaryKey(bool $isPrimaryKey): self
    {
        $this->isPrimaryKey = $isPrimaryKey;

        return $this;
    }
}
