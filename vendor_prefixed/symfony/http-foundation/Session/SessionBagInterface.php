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

namespace GravityKit\GravityView\Symfony\Component\HttpFoundation\Session;

/**
 * Session Bag store.
 *
 * @author Drak <drak@zikula.org>
 */
interface SessionBagInterface
{
    /**
     * Gets this bag's name.
     *
     * @return string
     */
    public function getName();

    /**
     * Initializes the Bag.
     */
    public function initialize(array &$array);

    /**
     * Gets the storage key for this bag.
     *
     * @return string
     */
    public function getStorageKey();

    /**
     * Clears out data from bag.
     *
     * @return mixed Whatever data was contained
     */
    public function clear();
}
