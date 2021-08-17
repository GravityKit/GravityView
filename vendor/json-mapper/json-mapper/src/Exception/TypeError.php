<?php

declare(strict_types=1);

namespace JsonMapper\Exception;

class TypeError extends \TypeError
{
    /** @param mixed $object */
    public static function forObjectArgument(string $method, $object, int $argumentNumber): TypeError
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        return new TypeError(sprintf(
            '%s(): Argument #%d ($object) must be of type object, %s given, called in %s on line %d',
            $method,
            $argumentNumber,
            gettype($object),
            $trace[1]['file'],
            $trace[1]['line']
        ));
    }
}
