<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 28-April-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityView\Psr\Log;

/**
 * Describes a logger-aware instance.
 */
interface LoggerAwareInterface
{
    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger);
}
