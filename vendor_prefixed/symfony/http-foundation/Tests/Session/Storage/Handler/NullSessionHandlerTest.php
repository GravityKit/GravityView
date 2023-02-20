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
use GravityKit\GravityView\Symfony\Component\HttpFoundation\Session\Session;
use GravityKit\GravityView\Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;
use GravityKit\GravityView\Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

/**
 * Test class for NullSessionHandler.
 *
 * @author Drak <drak@zikula.org>
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class NullSessionHandlerTest extends TestCase
{
    public function testSaveHandlers()
    {
        $this->getStorage();
        $this->assertEquals('user', ini_get('session.save_handler'));
    }

    public function testSession()
    {
        session_id('nullsessionstorage');
        $storage = $this->getStorage();
        $session = new Session($storage);
        $this->assertNull($session->get('something'));
        $session->set('something', 'unique');
        $this->assertEquals('unique', $session->get('something'));
    }

    public function testNothingIsPersisted()
    {
        session_id('nullsessionstorage');
        $storage = $this->getStorage();
        $session = new Session($storage);
        $session->start();
        $this->assertEquals('nullsessionstorage', $session->getId());
        $this->assertNull($session->get('something'));
    }

    public function getStorage()
    {
        return new NativeSessionStorage([], new NullSessionHandler());
    }
}
