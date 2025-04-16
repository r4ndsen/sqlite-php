<?php

namespace r4ndsen\SQLite\Pragma;

use InvalidArgumentException;

/** @see https:// www.sqlite.org/pragma.html#pragma_journal_mode */
enum JournalMode
{
    case DELETE;
    case MEMORY;
    case OFF;
    case PERSIST;
    case TRUNCATE;
    case WAL;

    /** @throws InvalidArgumentException */
    public static function fromString(string $input): self
    {
        foreach (self::cases() as $case) {
            if ($case->name === strtoupper($input)) {
                return $case;
            }
        }

        throw new InvalidArgumentException('Invalid value for pragma.' . Constant::JOURNAL_MODE . ': ' . $input);
    }
}
