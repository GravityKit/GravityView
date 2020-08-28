<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Tests\Constraints;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Valid;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ValidTest extends TestCase
{
    /**
     * @expectedException \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testRejectGroupsOption()
    {
        new Valid(array('groups' => 'foo'));
    }
}
