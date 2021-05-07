<?php

namespace JsonMapper\Cache;

use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
    /** @var array */
    private $cache = [];

    public function get($key, $default = null)
    {
        self::ensureKeyArgumentIsValidSingleKey($key);

        return $this->cache[$key] ?? $default;
    }

    public function set($key, $value, $ttl = null): bool
    {
        self::ensureKeyArgumentIsValidSingleKey($key);

        $this->cache[$key] = $value;

        return true;
    }

    public function delete($key): bool
    {
        self::ensureKeyArgumentIsValidSingleKey($key);

        unset($this->cache[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];

        return true;
    }

    public function getMultiple($keys, $default = null)
    {
        self::ensureKeyArgumentIsValidSetOfKeys($keys);

        $values = [];
        $keys = (array) $keys;
        array_walk($keys, function ($key) use ($default, &$values) {
            $values[$key] = $this->cache[$key] ?? $default;
        });

        return $values;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        if (! is_iterable($values)) {
            throw InvalidArgumentException::forCacheKey($values);
        }

        $values = (array) $values;
        self::ensureKeyArgumentIsValidSetOfKeys(array_keys($values));

        $this->cache = array_merge($this->cache, $values);

        return true;
    }

    public function deleteMultiple($keys): bool
    {
        self::ensureKeyArgumentIsValidSetOfKeys($keys);

        $keys = (array) $keys;
        array_walk($keys, function ($key) {
            unset($this->cache[$key]);
        });

        return true;
    }

    /**
     * @param string $key
     */
    public function has($key): bool
    {
        self::ensureKeyArgumentIsValidSingleKey($key);

        return array_key_exists($key, $this->cache);
    }

    /**
     * @param mixed $key
     */
    private static function ensureKeyArgumentIsValidSingleKey($key): void
    {
        if (is_string($key)) {
            return;
        }

        throw InvalidArgumentException::forCacheKey($key);
    }

    /**
     * @param mixed $keys
     */
    private static function ensureKeyArgumentIsValidSetOfKeys($keys): void
    {
        if (! is_iterable($keys)) {
            throw InvalidArgumentException::forCacheKey($keys);
        }

        $keys = (array) $keys;
        array_walk($keys, static function ($key) {
            self::ensureKeyArgumentIsValidSingleKey($key);
        });
    }
}
