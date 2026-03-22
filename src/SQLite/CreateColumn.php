<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

interface CreateColumn
{
    public function getCreateStatement(): string;
}
