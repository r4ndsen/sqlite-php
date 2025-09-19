<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use r4ndsen\SQLite\Traits\ExecTrait;

final class Transaction
{
    use ExecTrait;

    private bool $active = false;

    public function __construct(protected Connection $conn)
    {
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function begin(): self
    {
        if ($this->active === false) {
            $this->exec('begin');
            $this->active = true;
        }

        return $this;
    }

    public function commit(): self
    {
        if ($this->active === true) {
            $this->exec('commit');
            $this->active = false;
        }

        return $this;
    }
}
