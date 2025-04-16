<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

enum OnConflict
{
    case ABORT;
    case FAIL;
    case IGNORE;
    case REPLACE;
    case ROLLBACK;

    public function String(): string
    {
        return 'ON CONFLICT ' . $this->name;
    }
}
