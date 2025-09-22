<?php

declare(strict_types=0);

namespace r4ndsen\SQLite;

enum ColumnType
{
    case BLOB;
    case INTEGER;
    case NUMERIC;
    case REAL;
    case TEXT;

    /** @see https://www.sqlite.org/datatype3.html */
    public static function fromString(string $type): self
    {
        $lower = strtolower($type);

        return match (true) {
            $lower === 'numeric',
            str_starts_with($lower, 'date'),
            str_starts_with($lower, 'bool'),
            str_starts_with($lower, 'decimal') => self::NUMERIC,
            str_starts_with($lower, 'int'),
            str_ends_with($lower, 'int') => self::INTEGER,
            str_contains($lower, 'real'),
            str_contains($lower, 'floa'),
            str_contains($lower, 'doab') => self::REAL,
            str_contains($lower, 'blob') => self::BLOB,
            default                      => self::TEXT,
        };
    }
}
