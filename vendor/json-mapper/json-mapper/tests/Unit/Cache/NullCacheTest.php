<?php

declare(strict_types=1);

namespace JsonMapper\Tests\Unit\Cache;

use JsonMapper\Cache\InvalidArgumentException;
use JsonMapper\Cache\NullCache;
use PHPUnit\Framework\TestCase;

class NullCacheTest extends TestCase
{
    /**
     * @covers \JsonMapper\Cache\NullCache
     */
    public function testDoestStoreAnythingInCache(): void
    {
        $cache = new NullCache();

        $cache->set(__FUNCTION__, new \stdClass());

        self::assertFalse($cache->has(__FUNCTION__));
        self::assertNull($cache->get(__FUNCTION__));
    }

    /**
     * @covers \JsonMapper\Cache\NullCache
     */
    public function testDeletionOfKeyStillLeavesCacheEmpty(): void
    {
        $cache = new NullCache();

        $cache->delete(__FUNCTION__);

        self::assertNull($cache->get(__FUNCTION__));
    }

    /**
     * @covers \JsonMapper\Cache\NullCache
     */
    public function testClearingOfCacheStillLeavesCacheEmpty(): void
    {
        $cache = new NullCache();

        $cache->clear();

        self::assertNull($cache->get(__FUNCTION__));
    }

    /**
     * @covers \JsonMapper\Cache\NullCache
     */
    public function testCanSetMultipleKeysFromCacheButAlwaysReturnsNull(): void
    {
        $cache = new NullCache();
        $value = new \stdClass();
        $data = [__NAMESPACE__ => $value, __CLASS__ => $value, __FUNCTION__ => $value];

        $cache->setMultiple($data);
        $result = $cache->getMultiple([__NAMESPACE__, __CLASS__, __FUNCTION__]);

        self::assertSame($result, [
            __NAMESPACE__ => null,
            __CLASS__ => null,
            __FUNCTION__ => null,
        ]);
    }

    /**
     * @covers \JsonMapper\Cache\NullCache
     */
    public function testCanDeleteMultipleKeysFromCache(): void
    {
        $cache = new NullCache();
        $value = new \stdClass();
        $data = [__NAMESPACE__ => $value, __CLASS__ => $value, __FUNCTION__ => $value];

        $cache->deleteMultiple($data);
        $result = $cache->getMultiple([__NAMESPACE__, __CLASS__, __FUNCTION__]);

        self::assertSame($result, [
            __NAMESPACE__ => null,
            __CLASS__ => null,
            __FUNCTION__ => null,
        ]);
    }

    /**
     * @covers \JsonMapper\Cache\NullCache
     */
    public function testWhenRetrievingMultipleFromCacheWithInvalidKeyItThrowsAnException(): void
    {
        $cache = new NullCache();

        $this->expectException(InvalidArgumentException::class);
        $cache->getMultiple(new \stdClass());
    }
}
