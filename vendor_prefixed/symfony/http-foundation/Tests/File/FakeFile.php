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

namespace GravityKit\GravityView\Symfony\Component\HttpFoundation\Tests\File;

use GravityKit\GravityView\Symfony\Component\HttpFoundation\File\File as OrigFile;

class FakeFile extends OrigFile
{
    private $realpath;

    public function __construct($realpath, $path)
    {
        $this->realpath = $realpath;
        parent::__construct($path, false);
    }

    public function isReadable()
    {
        return true;
    }

    public function getRealpath()
    {
        return $this->realpath;
    }

    public function getSize()
    {
        return 42;
    }

    public function getMTime()
    {
        return time();
    }
}
