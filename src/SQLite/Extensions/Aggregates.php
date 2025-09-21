<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use r4ndsen\SQLite\Exception\InvalidAggregateException;
use r4ndsen\SQLite\Extensions\Aggregates\AggregateInterface;

class Aggregates extends AbstractExtension
{
    /** @throws InvalidAggregateException */
    public function add(AggregateInterface $aggregate): void
    {
        $identifier = $aggregate->getIdentifier();

        if (trim($identifier) === '') {
            throw new InvalidAggregateException('Failed to create aggregate: ' . $identifier);
        }

        $res = $this->registerAggregate(
            $identifier,
            $aggregate->getCallback(),
            $aggregate->getFinalCallback()
        );

        if ($res === false) {
            throw new InvalidAggregateException('Failed to create aggregate: ' . $identifier);
        }
    }

    public function registerDefaults(): void
    {
        $this->add(new Aggregates\GroupConcat());
        $this->add(new Aggregates\GroupConcatUnique());
        $this->add(new Aggregates\First());
        $this->add(new Aggregates\Last());
    }

    protected function registerAggregate(string $identifier, callable $callback, callable $finalCallback): bool
    {
        return $this->conn->createAggregate($identifier, $callback, $finalCallback);
    }
}
