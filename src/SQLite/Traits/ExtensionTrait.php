<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

trait ExtensionTrait
{
    protected string $identifier;

    /** @return callable-string|callable */
    public function getCallback(): callable
    {
        // @phpstan-ignore return.type
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
