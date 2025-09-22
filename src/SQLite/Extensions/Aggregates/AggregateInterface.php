<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Aggregates;

interface AggregateInterface
{
    public function getCallback(): callable;

    public function getFinalCallback(): callable;

    public function getIdentifier(): string;
}
