<?php
/**
 * @license MIT
 *
 * Modified by gravityview on 20-February-2023 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace GravityKit\GravityView\Foundation\ThirdParty\Illuminate\Support\Facades;

use GravityKit\GravityView\Foundation\ThirdParty\Illuminate\Contracts\Auth\Access\Gate as GateContract;

/**
 * @see \GravityKit\GravityView\Foundation\ThirdParty\Illuminate\Contracts\Auth\Access\Gate
 */
class Gate extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return GateContract::class;
    }
}
