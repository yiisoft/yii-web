<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Yii\Web\RateLimiter\CacheStorage;

final class CacheStorageTest extends TestCase
{
    /**
     * @test
     */
    public function getCounterNonExistValue(): void
    {
        $storage = new CacheStorage(new ArrayCache());
        $this->assertEquals(0, $storage->getCounterValue('test'));
    }

    /**
     * @test
     */
    public function getCounterExistValue(): void
    {
        $cache = new ArrayCache();
        $cache->set('test', 100);

        $storage = new CacheStorage($cache);
        $this->assertEquals(100, $storage->getCounterValue('test'));
    }

    /**
     * @test
     */
    public function setCounterValue(): void
    {
        $cache = new ArrayCache();
        $storage = new CacheStorage($cache);

        $storage->setCounterValue('test', 1000, 10);

        $this->assertEquals(1000, $cache->get('test'));
    }

    /**
     * @test
     */
    public function hasCounterValueExist(): void
    {
        $cache = new ArrayCache();
        $cache->set('test', 10);
        $storage = new CacheStorage($cache);

        $this->assertTrue($storage->hasCounterValue('test'));
    }

    /**
     * @test
     */
    public function hasCounterValueNotExist(): void
    {
        $storage = new CacheStorage(new ArrayCache());
        $this->assertFalse($storage->hasCounterValue('test'));
    }
}
