<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Functions;

class IsEmpty extends AbstractFunction
{
    protected string $identifier = 'empty';

    public function getCallback(): callable
    {
        // SQLite has no boolean type, a PHP bool would be returned as the text '1' or ''
        return static fn ($s) => (int) ($s === '' || $s === null);
    }
}
