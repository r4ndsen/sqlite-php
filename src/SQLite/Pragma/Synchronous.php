<?php

namespace r4ndsen\SQLite\Pragma;

use InvalidArgumentException;

/** @see https://www.sqlite.org/pragma.html#pragma_synchronous */
enum Synchronous: int
{
    case EXTRA = 3;
    case FULL = 2;
    case NORMAL = 1;
    case OFF = 0;

    /** @throws InvalidArgumentException */
    public static function fromString(string|int $input): self
    {
        if (is_numeric($input)) {
            return array_find(
                self::cases(),
                static fn (self $case) => $case->value === (int) $input
            ) ?? throw new InvalidArgumentException('Invalid value for pragma.' . Constant::SYNCHRONOUS . ': ' . $input);
        }

        return array_find(
            self::cases(),
            static fn (self $case) => $case->name === strtoupper($input)
        ) ?? throw new InvalidArgumentException('Invalid value for pragma.' . Constant::SYNCHRONOUS . ': ' . $input);
    }
}
