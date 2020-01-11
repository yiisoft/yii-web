<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Yii\Web\RateLimiter\CacheCounter;

final class CacheCounterTest extends TestCase
{
    /**
     * @test
     */
    public function limitNotExhausted(): void
    {
        $counter = new CacheCounter(2, 5, new ArrayCache());
        $counter->setId('key');

        $this->assertFalse($counter->limitIsReached());
    }

    /**
     * @test
     */
    public function limitIsExhausted(): void
    {
        $cache = new ArrayCache();
        $cache->set(CacheCounter::ID_PREFIX . 'key', (time() * 1000) + 55000);

        $counter = new CacheCounter(10, 60, $cache);
        $counter->setId('key');

        $this->assertTrue($counter->limitIsReached());
    }

    /**
     * @test
     */
    public function invalidIdArgument(): void
    {
        $this->expectException(RuntimeException::class);
        (new CacheCounter(10, 60, new ArrayCache()))->limitIsReached();
    }

    /**
     * @test
     */
    public function invalidLimitArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CacheCounter(0, 60, new ArrayCache());
    }

    /**
     * @test
     */
    public function invalidPeriodArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new CacheCounter(10, 0, new ArrayCache());
    }
}
