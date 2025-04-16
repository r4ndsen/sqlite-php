<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Exception;

use Throwable;

class ViewConstraintException extends SQLiteException
{
    public function __construct(public readonly string $viewName, string $message, Throwable $previous)
    {
        parent::__construct($message, $previous->getCode(), $previous);
    }

    public function getViewName(): string
    {
        return $this->viewName;
    }
}
