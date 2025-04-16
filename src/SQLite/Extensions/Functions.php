<?php

declare(strict_types=1);

namespace r4ndsen\SQLite\Extensions;

use r4ndsen\SQLite\Extensions\Functions\FunctionInterface;

class Functions extends AbstractExtension
{
    public function add(FunctionInterface $function): self
    {
        $this->conn->createFunction(
            $function->getIdentifier(),
            $function->getCallback()
        );

        return $this;
    }

    public function registerDefaults(): self
    {
        $this->add(new Functions\Sprintf());
        $this->add(new Functions\Md5());
        $this->add(new Functions\IsEmpty());
        $this->add(new Functions\PregMatch());
        $this->add(new Functions\PregReplace());

        return $this;
    }
}
