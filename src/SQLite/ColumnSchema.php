<?php

declare(strict_types=0);

namespace r4ndsen\SQLite;

/** @internal */
final readonly class ColumnSchema
{
    public ?string $defaultValue;
    public bool $isPrivateKey;
    public bool $notNull;
    public ColumnType $type;

    public function __construct(
        public int $columnId,
        public string $name,
        string $type,
        int $notNull,
        ?string $defaultValue,
        int $primaryKey,
    ) {
        $this->type = ColumnType::fromString($type);
        $this->isPrivateKey = $primaryKey === 1;
        $this->notNull = $notNull === 1;

        if ($defaultValue === 'null' || $defaultValue === null) {
            $this->defaultValue = null;
        } else {
            $this->defaultValue = preg_replace("#^'|'$#", '', $defaultValue);
        }
    }
}
