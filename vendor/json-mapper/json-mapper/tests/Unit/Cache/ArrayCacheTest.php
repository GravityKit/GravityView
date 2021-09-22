<?php

namespace JsonMapper\Tests\Unit\Cache;

use JsonMapper\Cache\ArrayCache;
use JsonMapper\Cache\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ArrayCacheTest extends TestCase
{
    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testCanRetrieveSingleValueStoredInCache(): void
    {
        $cache = new ArrayCache();
        $value = new \stdClass();
        $key = __FUNCTION__;

        $cache->set($key, $value);

        self::assertTrue($cache->has($key));
        self::assertSame($value, $cache->get($key));
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testReturnsDefaultIfKeyIsNotInCache(): void
    {
        $cache = new ArrayCache();
        $default = new \stdClass();
        $key = __FUNCTION__;

        self::assertFalse($cache->has($key));
        self::assertSame($default, $cache->get($key, $default));
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testWhenFetchingFromCacheWithInvalidKeyItThrowsAnException(): void
    {
        $cache = new ArrayCache();

        $this->expectException(InvalidArgumentException::class);
        $cache->get(new \stdClass());
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testWhenStoringIntoCacheWithInvalidKeyItThrowsAnException(): void
    {
        $cache = new ArrayCache();

        $this->expectException(InvalidArgumentException::class);
        $cache->set(new \stdClass(), null);
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testDeleteFromCacheRemovesIt(): void
    {
        $cache = new ArrayCache();
        $value = new \stdClass();
        $key = __FUNCTION__;
        $cache->set($key, $value);

        $cache->delete($key);

        self::assertFalse($cache->has($key));
        self::assertNull($cache->get($key));
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testWhenDeletingFromCacheWithInvalidKeyItThrowsAnException(): void
    {
        $cache = new ArrayCache();

        $this->expectException(InvalidArgumentException::class);
        $cache->delete(new \stdClass());
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testClearCacheRemovesAllItems(): void
    {
        $cache = new ArrayCache();
        $value = new \stdClass();
        $keyOne = __FUNCTION__;
        $keyTwo = __CLASS__;
        $cache->set($keyOne, $value);
        $cache->set($keyTwo, $value);

        $cache->clear();

        self::assertFalse($cache->has($keyOne));
        self::assertFalse($cache->has($keyTwo));
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testWhenSeeingIfCacheHasKeyWithInvalidKeyItThrowsAnException(): void
    {
        $cache = new ArrayCache();

        $this->expectException(InvalidArgumentException::class);
        $cache->has(new \stdClass());
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testCanRetrieveMultipleKeysFromCache(): void
    {
        $cache = new ArrayCache();
        $value = new \stdClass();
        $data = [__NAMESPACE__ => $value, __CLASS__ => $value, __FUNCTION__ => $value];

        $cache->setMultiple($data);
        $result = $cache->getMultiple([__NAMESPACE__, __CLASS__, __FUNCTION__]);

        self::assertSame($result, [
            __NAMESPACE__ => $value,
            __CLASS__ => $value,
            __FUNCTION__ => $value,
        ]);
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testCanDeleteMultipleKeysFromCache(): void
    {
        $cache = new ArrayCache();
        $value = new \stdClass();
        $data = [__NAMESPACE__ => $value, __CLASS__ => $value, __FUNCTION__ => $value];

        $cache->setMultiple($data);
        $cache->deleteMultiple([__NAMESPACE__, __CLASS__]);

        self::assertFalse($cache->has(__NAMESPACE__));
        self::assertFalse($cache->has(__CLASS__));
        self::assertTrue($cache->has(__FUNCTION__));
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testWhenStoringMultipleIntoCacheWithInvalidKeyItThrowsAnException(): void
    {
        $cache = new ArrayCache();

        $this->expectException(InvalidArgumentException::class);
        $cache->setMultiple(new \stdClass());
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testWhenRetrievingMultipleFromCacheWithInvalidKeyItThrowsAnException(): void
    {
        $cache = new ArrayCache();

        $this->expectException(InvalidArgumentException::class);
        $cache->getMultiple(new \stdClass());
    }

    /**
     * @covers \JsonMapper\Cache\ArrayCache
     */
    public function testWhenDeletingMultipleFromCacheWithInvalidKeyItThrowsAnException(): void
    {
        $cache = new ArrayCache();

        $this->expectException(InvalidArgumentException::class);
        $cache->deleteMultiple(new \stdClass());
    }
}
