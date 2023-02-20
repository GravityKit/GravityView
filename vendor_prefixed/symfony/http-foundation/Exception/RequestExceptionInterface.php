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

namespace GravityKit\GravityView\Symfony\Component\HttpFoundation\Exception;

/**
 * Interface for Request exceptions.
 *
 * Exceptions implementing this interface should trigger an HTTP 400 response in the application code.
 */
interface RequestExceptionInterface
{
}
