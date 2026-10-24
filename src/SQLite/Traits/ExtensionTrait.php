<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Traits;

trait ExtensionTrait
{
    protected string $identifier;

    // By default the PHP function named like the identifier is used
    public function getCallback(): callable
    {
        /** @phpstan-ignore return.type */
        return $this->getIdentifier();
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
