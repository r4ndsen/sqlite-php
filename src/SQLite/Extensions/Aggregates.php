<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use r4ndsen\SQLite\Extensions\Aggregates\AggregateInterface;

class Aggregates extends AbstractExtension
{
    public function add(AggregateInterface $aggregate): self
    {
        $this->conn->createAggregate(
            $aggregate->getIdentifier(),
            $aggregate->getCallback(),
            $aggregate->getFinalCallback()
        );

        return $this;
    }

    public function registerDefaults(): self
    {
        $this->add(new Aggregates\GroupConcat());
        $this->add(new Aggregates\GroupConcatUnique());
        $this->add(new Aggregates\First());
        $this->add(new Aggregates\Last());

        return $this;
    }
}
