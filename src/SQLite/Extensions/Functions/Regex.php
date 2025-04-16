<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Functions;

abstract class Regex extends AbstractFunction
{
    final protected function validRegex(string $regexString): bool
    {
        /** @var array<string, bool> $regexCache */
        static $regexCache = [];

        return $regexCache[$regexString] ??= @preg_match($regexString, '') !== false;
    }
}
