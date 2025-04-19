<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use r4ndsen\SQLite\Exception\QueryException;
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
            try {
                $this->exec('begin');
            } catch (QueryException) {
            }
            $this->active = true;
        }

        return $this;
    }

    public function commit(): self
    {
        if ($this->active === true) {
            try {
                $this->exec('commit');
            } catch (QueryException) {
            }
            $this->active = false;
        }

        return $this;
    }
}
