<?php

namespace JsonMapper\Cache;

class InvalidArgumentException extends \InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException
{
    /** @var mixed */
    private $invalidArgument;

    /**
     * @param mixed $key
     */
    public static function forCacheKey($key): self
    {
        $e = new self('An invalid cache key was provided.');
        $e->invalidArgument = $key;

        return $e;
    }

    /**
     * @return mixed
     */
    public function getInvalidArgument()
    {
        return $this->invalidArgument;
    }
}
