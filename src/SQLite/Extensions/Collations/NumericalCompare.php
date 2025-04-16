<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Collations;

class NumericalCompare extends AbstractCollation
{
    protected string $identifier = 'NUMERICAL_CMP';

    public function getCallback(): callable
    {
        return static fn ($a, $b) => $a <=> $b;
    }
}
