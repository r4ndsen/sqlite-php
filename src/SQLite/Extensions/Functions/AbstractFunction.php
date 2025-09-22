<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Functions;

use r4ndsen\SQLite\Traits\ExtensionTrait;

abstract class AbstractFunction implements FunctionInterface
{
    use ExtensionTrait;
}
