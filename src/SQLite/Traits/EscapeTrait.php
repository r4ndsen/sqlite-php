<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

use SQLite3;

trait EscapeTrait
{
    public function backtick(string $string): string
    {
        return self::backtickString($string);
    }

    // Backticks an identifier and escapes it
    public static function backtickIdentifier(string $identifier): string
    {
        return self::backtickString(self::escapeIdentifier($identifier));
    }

    // Backticks a value
    public static function backtickString(string $string): string
    {
        return sprintf('`%s`', $string);
    }

    public function escape(string $string): string
    {
        return self::escapeString($string);
    }

    // Escape column and or table names used in statements
    public static function escapeIdentifier(string $identifier): string
    {
        return str_replace('`', '``', $identifier);
    }

    // Alias of escape()
    public static function escapeString(string $string): string
    {
        return SQLite3::escapeString($string);
    }
}
