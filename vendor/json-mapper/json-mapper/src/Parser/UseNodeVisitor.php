<?php

declare(strict_types=1);

namespace JsonMapper\Parser;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt;

class UseNodeVisitor extends NodeVisitorAbstract
{
    /** @var array|string[] */
    private $imports = [];

    /**
     * @return null
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Stmt\Use_) {
            foreach ($node->uses as $use) {
                $this->imports[] = $use->name->toString();
            }
        } elseif ($node instanceof Stmt\GroupUse) {
            foreach ($node->uses as $use) {
                $this->imports[] = $node->prefix . '\\' . $use->name;
            }
        }

        return null;
    }

    public function getImports(): array
    {
        return $this->imports;
    }
}
