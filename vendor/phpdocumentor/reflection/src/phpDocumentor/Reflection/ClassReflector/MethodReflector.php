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

namespace phpDocumentor\Reflection\ClassReflector;

use phpDocumentor\Reflection\FunctionReflector;
use PhpParser\Node\Stmt\Class_;

class MethodReflector extends FunctionReflector
{
    /** @var \PhpParser\Node\Stmt\ClassMethod */
    protected $node;

    /**
     * Returns the visibility for this item.
     *
     * The returned value should match either of the following:
     *
     * * public
     * * protected
     * * private
     *
     * If a method has no visibility set in the class definition this method
     * will return 'public'.
     *
     * @return string
     */
    public function getVisibility()
    {
        if ($this->node->type & Class_::MODIFIER_PROTECTED) {
            return 'protected';
        }
        if ($this->node->type & Class_::MODIFIER_PRIVATE) {
            return 'private';
        }

        return 'public';
    }

    /**
     * Returns whether this method is static.
     *
     * @return bool
     */
    public function isAbstract()
    {
        return (bool) ($this->node->type & Class_::MODIFIER_ABSTRACT);
    }

    /**
     * Returns whether this method is static.
     *
     * @return bool
     */
    public function isStatic()
    {
        return (bool) ($this->node->type & Class_::MODIFIER_STATIC);
    }

    /**
     * Returns whether this method is final.
     *
     * @return bool
     */
    public function isFinal()
    {
        return (bool) ($this->node->type & Class_::MODIFIER_FINAL);
    }
}
