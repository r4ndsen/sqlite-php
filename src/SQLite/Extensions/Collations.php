<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use r4ndsen\SQLite\Extensions\Collations\CollationInterface;

class Collations extends AbstractExtension
{
    public function add(CollationInterface $collation): self
    {
        $this->conn->createCollation(
            $collation->getIdentifier(),
            $collation->getCallback()
        );

        return $this;
    }

    public function registerDefaults(): self
    {
        $this->add(new Collations\NaturalCompare());
        $this->add(new Collations\NumericalCompare());

        return $this;
    }
}
