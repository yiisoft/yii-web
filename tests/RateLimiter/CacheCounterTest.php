<?php

namespace Yiisoft\Yii\Web\Tests\RateLimiter;

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
        $cache->set('key', time() + 55);

        $counter = new CacheCounter(10, 60, $cache);
        $counter->setId('key');

        $this->assertTrue($counter->limitIsReached());
    }
}
