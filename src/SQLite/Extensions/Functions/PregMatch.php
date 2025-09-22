<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Functions;

class PregMatch extends Regex
{
    protected string $identifier = 'preg_match';

    public function getCallback(): callable
    {
        return fn (string $regularExpression, $value) => (int) ($this->validRegex($regularExpression) && preg_match($regularExpression, (string) $value));
    }
}
