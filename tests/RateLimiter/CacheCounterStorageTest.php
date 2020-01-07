<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Yii\Web\RateLimiter\CacheCounterStorage;

final class CacheCounterStorageTest extends TestCase
{
    /**
     * @test
     */
    public function getCounterNonExistValue(): void
    {
        $storage = new CacheCounterStorage(new ArrayCache());
        $this->assertEquals(0, $storage->get('test'));
    }

    /**
     * @test
     */
    public function getCounterExistValue(): void
    {
        $cache = new ArrayCache();
        $cache->set('test', 100);

        $storage = new CacheCounterStorage($cache);
        $this->assertEquals(100, $storage->get('test'));
    }

    /**
     * @test
     */
    public function setCounterValue(): void
    {
        $cache = new ArrayCache();
        $storage = new CacheCounterStorage($cache);

        $storage->set('test', 1000, 10);

        $this->assertEquals(1000, $cache->get('test'));
    }

    /**
     * @test
     */
    public function hasCounterValueExist(): void
    {
        $cache = new ArrayCache();
        $cache->set('test', 10);
        $storage = new CacheCounterStorage($cache);

        $this->assertTrue($storage->has('test'));
    }

    /**
     * @test
     */
    public function hasCounterValueNotExist(): void
    {
        $storage = new CacheCounterStorage(new ArrayCache());
        $this->assertFalse($storage->has('test'));
    }
}
