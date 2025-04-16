<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Functions;

interface FunctionInterface
{
    public function getCallback(): callable;

    public function getIdentifier(): string;
}
