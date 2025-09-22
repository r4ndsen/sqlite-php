<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Collations;

class NaturalCompare extends AbstractCollation
{
    protected string $identifier = 'NATURAL_CMP';

    /**
     * This function implements a comparison algorithm that orders alphanumeric strings
     * in the way a human being would, this is described as a "natural ordering".
     * Note that this comparison is case-sensitive.
     */
    public function getCallback(): callable
    {
        return 'strnatcmp';
    }
}
