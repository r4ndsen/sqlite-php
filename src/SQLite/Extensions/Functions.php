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
        $res = $this->conn->createFunction(
            $function->getIdentifier(),
            $function->getCallback()
        );

        if ($res === false) {
            throw new InvalidFunctionException('Failed to create function: ' . $function->getIdentifier());
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
}
