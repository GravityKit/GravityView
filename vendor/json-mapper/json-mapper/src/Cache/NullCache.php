<?php

namespace JsonMapper\Cache;

use Psr\SimpleCache\CacheInterface;

class NullCache implements CacheInterface
{
    public function get($key, $default = null)
    {
        return $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        return true;
    }

    public function delete($key): bool
    {
        return true;
    }

    public function clear(): bool
    {
        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        if (! is_iterable($keys)) {
            throw InvalidArgumentException::forCacheKey($keys);
        }

        $keys = (array) $keys;
        $values = array_fill(0, count($keys), $default);

        return (array) array_combine($keys, $values);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        return true;
    }

    public function deleteMultiple($keys): bool
    {
        return true;
    }

    public function has($key): bool
    {
        return false;
    }
}
