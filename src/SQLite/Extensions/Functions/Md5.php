<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions\Functions;

class Md5 extends AbstractFunction
{
    public function getCallback(): callable
    {
        return static fn ($value) => md5((string) $value);
    }
}
