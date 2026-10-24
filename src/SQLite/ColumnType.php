<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

enum ColumnType
{
    case BLOB;
    case INTEGER;
    case NUMERIC;
    case REAL;
    case TEXT;

    /**
     * Determines the column affinity like SQLite does.
     *
     * @see https://www.sqlite.org/datatype3.html#determination_of_column_affinity
     */
    public static function fromString(string $type): self
    {
        $lower = strtolower($type);

        return match (true) {
            str_contains($lower, 'int') => self::INTEGER,
            str_contains($lower, 'char'),
            str_contains($lower, 'clob'),
            str_contains($lower, 'text') => self::TEXT,
            str_contains($lower, 'blob'),
            trim($lower) === '' => self::BLOB,
            str_contains($lower, 'real'),
            str_contains($lower, 'floa'),
            str_contains($lower, 'doub') => self::REAL,
            default                      => self::NUMERIC,
        };
    }
}
