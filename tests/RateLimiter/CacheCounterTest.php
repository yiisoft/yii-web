<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Yii\Web\RateLimiter\Counter;

final class CacheCounterTest extends TestCase
{
    /**
     * @test
     */
    public function limitNotExhausted(): void
    {
        $counter = new Counter(2, 5, new ArrayCache());
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
        $cache->set(Counter::ID_PREFIX . 'key', (time() * 1000) + 55000);

        $counter = new Counter(10, 60, $cache);
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
        (new Counter(10, 60, new ArrayCache()))->incrementAndGetResult();
    }

    /**
     * @test
     */
    public function invalidLimitArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Counter(0, 60, new ArrayCache());
    }

    /**
     * @test
     */
    public function invalidPeriodArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Counter(10, 0, new ArrayCache());
    }
}
