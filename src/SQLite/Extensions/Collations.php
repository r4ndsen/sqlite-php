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
        $identifier = $collation->getIdentifier();

        if (trim($identifier) === '') {
            throw new InvalidCollationException('Failed to create collation: ' . $identifier);
        }

        $res = $this->registerCollation(
            $identifier,
            $collation->getCallback()
        );

        if ($res === false) {
            throw new InvalidCollationException('Failed to create collation: ' . $identifier);
        }
    }

    public function registerDefaults(): void
    {
        $this->add(new Collations\NaturalCompare());
        $this->add(new Collations\NumericalCompare());
    }

    protected function registerCollation(string $identifier, callable $callback): bool
    {
        return $this->conn->createCollation($identifier, $callback);
    }
}
