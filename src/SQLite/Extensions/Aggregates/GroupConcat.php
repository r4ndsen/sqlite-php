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

            // like the native group_concat(), null values are skipped
            if ($string !== null) {
                $this->setContextValue($context, $string);
            }

            return $context;
        };
    }

    public function getFinalCallback(): callable
    {
        return static fn (?array $context) => $context === null || $context['data'] === [] ? null : implode($context['delimiter'], $context['data']);
    }

    protected function setContextValue(array &$context, mixed $value): void
    {
        $context['data'][] = $value;
    }
}
