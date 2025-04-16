<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Collations;

interface CollationInterface
{
    public function getCallback(): callable;

    public function getIdentifier(): string;
}
