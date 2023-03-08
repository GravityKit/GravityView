<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Symfony\Component\HttpFoundation\Tests\Session\Storage\Handler;

use PHPUnit\Framework\TestCase;
use GravityKit\GravityView\Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeSessionHandler;

/**
 * Test class for NativeSessionHandler.
 *
 * @author Drak <drak@zikula.org>
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @group legacy
 */
class NativeSessionHandlerTest extends TestCase
{
    /**
     * @expectedDeprecation The Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeSessionHandler class is deprecated since Symfony 3.4 and will be removed in 4.0. Use the \SessionHandler class instead.
     */
    public function testConstruct()
    {
        $handler = new NativeSessionHandler();

        $this->assertInstanceOf('SessionHandler', $handler);
        $this->assertTrue($handler instanceof NativeSessionHandler);
    }
}
