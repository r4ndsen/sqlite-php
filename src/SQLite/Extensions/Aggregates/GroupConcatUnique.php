<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Aggregates;

class GroupConcatUnique extends GroupConcat
{
    public function getFinalCallback(): callable
    {
        return static fn (?array $context) => $context === null || $context['data'] === [] ? null : implode($context['delimiter'], array_keys($context['data']));
    }

    protected function setContextValue(array &$context, mixed $value): void
    {
        // floats cannot be used as array keys without losing their decimals
        $context['data'][\is_float($value) ? (string) $value : $value] = true;
    }
}
