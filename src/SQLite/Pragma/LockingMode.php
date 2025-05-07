<?php

namespace r4ndsen\SQLite\Pragma;

use InvalidArgumentException;

/** @see https://sqlite.org/pragma.html#pragma_locking_mode */
enum LockingMode
{
    case EXCLUSIVE;
    case NORMAL;

    /** @throws InvalidArgumentException */
    public static function fromString(string $input): self
    {
        return array_find(
            self::cases(),
            static fn (self $case) => $case->name === strtoupper($input)
        ) ?? throw new InvalidArgumentException('Invalid value for pragma.' . Constant::LOCKING_MODE . ': ' . $input);
    }
}
