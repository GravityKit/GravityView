<?php namespace MikeMcLin\WpPassword\Facades;

use Illuminate\Support\Facades\Facade;

class WpPassword extends Facade
{

    protected static function getFacadeAccessor()
    {
        return 'MikeMcLin\WpPassword\Contracts\WpPassword';
    }

}