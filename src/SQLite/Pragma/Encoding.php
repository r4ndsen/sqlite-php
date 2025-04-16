<?php

namespace r4ndsen\SQLite\Pragma;

use InvalidArgumentException;

/** @see https://sqlite.org/pragma.html#pragma_encoding */
enum Encoding: string
{
    case UTF16 = 'UTF-16';
    case UTF16BE = 'UTF-16be';
    case UTF16LE = 'UTF-16le';
    case UTF8 = 'UTF-8';

    /** @throws InvalidArgumentException */
    public static function fromString(string $input): self
    {
        $input = strtolower($input);

        foreach (self::cases() as $case) {
            if (strtolower($case->name) === $input || strtolower($case->value) === $input) {
                return $case;
            }
        }

        throw new InvalidArgumentException('Invalid value for pragma.' . Constant::ENCODING . ': ' . $input);
    }
}
