<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Aggregates;

class GroupConcatUnique extends GroupConcat
{
    public function getFinalCallback(): callable
    {
        return static fn ($context) => implode($context['delimiter'], array_keys($context['data']));
    }

    protected function setContextValue(array &$context, mixed $value): void
    {
        $context['data'][$value] = true;
    }
}
