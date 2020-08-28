<?php
/**
 * phpDocumentor
 *
 * PHP Version 5.3
 *
 * @author    Mike van Riel <mike.vanriel@naenius.com>
 * @copyright 2010-2012 Mike van Riel / Naenius (http://www.naenius.com)
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Reflection;

use PhpParser\Error;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;

/**
 * The source code traverser that scans the given source code and transforms
 * it into tokens.
 *
 * @author  Mike van Riel <mike.vanriel@naenius.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link    http://phpdoc.org
 */
class Traverser
{
    /**
     * List of visitors to apply upon traversing.
     *
     * @see traverse()
     *
     * @var \PhpParser\NodeVisitorAbstract[]
     */
    public $visitors = array();

    /**
     * Traverses the given contents and builds an AST.
     *
     * @param string $contents The source code of the file that is to be scanned
     *
     * @return void
     */
    public function traverse($contents)
    {
        try {
            $this->createTraverser()->traverse(
                $this->createParser()->parse($contents)
            );
        } catch (Error $e) {
            echo 'Parse Error: ', $e->getMessage();
        }
    }

    /**
     * Adds a visitor object to the traversal process.
     *
     * With visitors it is possible to extend the traversal process and
     * modify the found tokens.
     *
     * @param \PhpParser\NodeVisitor $visitor
     *
     * @return void
     */
    public function addVisitor(\PhpParser\NodeVisitor $visitor)
    {
        $this->visitors[] = $visitor;
    }

    /**
     * Creates a parser object using our own Lexer.
     *
     * @return Parser
     */
    protected function createParser()
    {
        return new Parser(new Lexer());
    }

    /**
     * Creates a new traverser object and adds visitors.
     *
     * @return NodeTraverser
     */
    protected function createTraverser()
    {
        $node_traverser = new NodeTraverser();
        $node_traverser->addVisitor(new NameResolver());

        foreach ($this->visitors as $visitor) {
            $node_traverser->addVisitor($visitor);
        }

        return $node_traverser;
    }
}
