<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Aggregates;

class First extends AbstractAggregate
{
    public function getCallback(): callable
    {
        return static fn ($context, int $rowId, $value, ...$values) => $context ?? ['value' => $value];
    }

    public function getFinalCallback(): callable
    {
        return static fn ($context, int $rowId) => $context['value'] ?? null;
    }
}
