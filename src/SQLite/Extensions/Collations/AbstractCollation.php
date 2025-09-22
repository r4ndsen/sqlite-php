<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Collations;

use r4ndsen\SQLite\Traits\ExtensionTrait;

abstract class AbstractCollation implements CollationInterface
{
    use ExtensionTrait;
}
