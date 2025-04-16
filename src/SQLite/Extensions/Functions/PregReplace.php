<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Functions;

class PregReplace extends Regex
{
    protected string $identifier = 'preg_replace';

    public function getCallback(): callable
    {
        return function (string $s, string $r, $value, int $limit = -1) {
            if (!$this->validRegex($s)) {
                return $value;
            }

            return preg_replace($s, $r, (string) $value, $limit);
        };
    }
}
