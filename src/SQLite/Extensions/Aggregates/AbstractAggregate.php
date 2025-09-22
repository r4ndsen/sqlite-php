<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Aggregates;

use r4ndsen\SQLite\Traits\ExtensionTrait;

abstract class AbstractAggregate implements AggregateInterface
{
    use ExtensionTrait;
}
