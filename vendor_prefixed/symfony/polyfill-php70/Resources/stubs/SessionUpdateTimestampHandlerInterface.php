<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 09-November-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

interface GravityKit_GravityView_SessionUpdateTimestampHandlerInterface
{
    /**
     * Checks if a session identifier already exists or not.
     *
     * @param string $key
     *
     * @return bool
     */
    public function validateId($key);

    /**
     * Updates the timestamp of a session when its data didn't change.
     *
     * @param string $key
     * @param string $val
     *
     * @return bool
     */
    public function updateTimestamp($key, $val);
}
