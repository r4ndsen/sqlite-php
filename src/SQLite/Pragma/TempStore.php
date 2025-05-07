<?php

namespace r4ndsen\SQLite\Pragma;

use InvalidArgumentException;

/** @see https://sqlite.org/pragma.html#pragma_locking_mode */
enum TempStore: int
{
    case DEFAULT = 0;
    case FILE = 1;
    case MEMORY = 2;

    /** @throws InvalidArgumentException */
    public static function fromString(string|int $input): self
    {
        if (is_numeric($input)) {
            return array_find(
                self::cases(),
                static fn (self $case) => $case->value === (int) $input
            ) ?? throw new InvalidArgumentException('Invalid value for pragma.' . Constant::TEMP_STORE . ': ' . $input);
        }

        return array_find(
            self::cases(),
            static fn (self $case) => $case->name === strtoupper($input)
        ) ?? throw new InvalidArgumentException('Invalid value for pragma.' . Constant::TEMP_STORE . ': ' . $input);
    }
}
