<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Aggregates;

class GroupConcat extends AbstractAggregate
{
    public function getCallback(): callable
    {
        return function (?array $context, int $rowId, $string, $delimiter = ',') {
            $context ??= [
                'delimiter' => $delimiter,
                'data'      => [],
            ];

            $this->setContextValue($context, $string);

            return $context;
        };
    }

    public function getFinalCallback(): callable
    {
        return static fn ($context) => implode($context['delimiter'], $context['data']);
    }

    protected function setContextValue(array &$context, mixed $value): void
    {
        $context['data'][] = $value;
    }
}
