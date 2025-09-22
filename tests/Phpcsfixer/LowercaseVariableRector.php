<?php

namespace r4ndsen\Phpcsfixer;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use Rector\Rector\AbstractRector;

class LowercaseVariableRector extends AbstractRector
{
    public function getNodeTypes(): array
    {
        return [Variable::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof Variable) {
            return null;
        }

        $nodeName = $this->getName($node);
        if ($nodeName === null) {
            return null;
        }

        $newName = lcfirst($nodeName);
        if ($newName === $nodeName) {
            return null;
        }

        $node->name = $newName;

        return $node;
    }
}
