<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use r4ndsen\SQLite\Exception\InvalidCollationException;
use r4ndsen\SQLite\Extensions\Collations\CollationInterface;

class Collations extends AbstractExtension
{
    /** @throws InvalidCollationException */
    public function add(CollationInterface $collation): void
    {
        $res = $this->conn->createCollation(
            $collation->getIdentifier(),
            $collation->getCallback()
        );

        if ($res === false) {
            throw new InvalidCollationException('Failed to create collation: ' . $collation->getIdentifier());
        }
    }

    public function registerDefaults(): void
    {
        $this->add(new Collations\NaturalCompare());
        $this->add(new Collations\NumericalCompare());
    }
}
