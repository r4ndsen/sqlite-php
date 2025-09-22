<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use r4ndsen\SQLite\Exception\InvalidFunctionException;
use r4ndsen\SQLite\Extensions\Functions\FunctionInterface;

class Functions extends AbstractExtension
{
    /** @throws InvalidFunctionException */
    public function add(FunctionInterface $function): void
    {
        $identifier = $function->getIdentifier();

        if (trim($identifier) === '') {
            throw new InvalidFunctionException('Failed to create function: identifier must not be empty');
        }

        $res = $this->registerFunction(
            $identifier,
            $function->getCallback()
        );

        if ($res === false) {
            throw new InvalidFunctionException('Failed to create function: ' . $identifier);
        }
    }

    public function registerDefaults(): void
    {
        $this->add(new Functions\Sprintf());
        $this->add(new Functions\Md5());
        $this->add(new Functions\IsEmpty());
        $this->add(new Functions\PregMatch());
        $this->add(new Functions\PregReplace());
    }

    protected function registerFunction(string $identifier, callable $callback): bool
    {
        return $this->conn->createFunction($identifier, $callback);
    }
}
