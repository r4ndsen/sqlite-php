<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

trait ExtensionTrait
{
    protected string $identifier;

    public function getCallback(): callable
    {
        return $this->identifier;
    }

    public function getIdentifier(): string
    {
        if (!isset($this->identifier)) {
            $parts = explode('\\', static::class);
            $this->identifier = lcfirst((string) end($parts));
        }

        return $this->identifier;
    }
}
