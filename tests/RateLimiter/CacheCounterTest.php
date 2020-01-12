<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

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

        $result = $counter->incrementAndGetResult();
        $this->assertEquals(2, $result->getLimit());
        $this->assertEquals(1, $result->getRemaining());
        $this->assertEquals(2500, $result->getReset());
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

        $result = $counter->incrementAndGetResult();
        $this->assertEquals(10, $result->getLimit());
        $this->assertEquals(0, $result->getRemaining());
        $this->assertEquals(61000, $result->getReset());
    }

    /**
     * @test
     */
    public function invalidIdArgument(): void
    {
        $this->expectException(\LogicException::class);
        (new CacheCounter(10, 60, new ArrayCache()))->incrementAndGetResult();
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
