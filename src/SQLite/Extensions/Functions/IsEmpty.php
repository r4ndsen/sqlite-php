<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Functions;

class IsEmpty extends AbstractFunction
{
    protected string $identifier = 'empty';

    public function getCallback(): callable
    {
        return static fn ($s) => $s === '' || $s === null;
    }
}
